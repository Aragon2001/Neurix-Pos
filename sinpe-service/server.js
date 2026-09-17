/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

// ── Servicio SINPE Móvil del Facturador ───────────────────────────────────────
// Vigila la bandeja de Gmail configurada en Configuración > SINPE Móvil y guarda
// cada pago SINPE detectado en tec_sinpe_transactions (MySQL, misma base que el
// Facturador). No sirve nada al público: solo expone endpoints internos que
// consume Settings.php (estado/conectar/desconectar/banco/vigilancia). El POS
// (Pos.php) NO llama a este servicio — lee tec_sinpe_transactions directo de la
// base para la lista en tiempo real del modal de pago, así el checkout no se
// cae si este proceso está reiniciando.
//
// Arranca solo, sin depender de ningún navegador abierto: si ya hay un
// refresh_token guardado y la vigilancia está activa, retoma la vigilancia al
// iniciar el proceso (ver arranqueAutomatico() al final del archivo).
import express from "express";
import cors from "cors";
import fetch from "node-fetch";

import { extractSinpeData } from "./extractor.js";
import { listaBancos, BANCOS, remitenteValidoSinpe } from "./bancos.js";
import * as store from "./store.js";
import * as oauth from "./oauth.js";
import { cifrar, descifrar } from "./crypto.js";
import {
  PORT, BIND_HOST, SERVICE_TOKEN, CORS_ORIGINS, JSON_LIMIT, EN_PRODUCCION,
  GMAIL_QUERY, RETURN_URL, advertirConfig,
} from "./config.js";

const app = express();

app.use(cors({
  origin(origin, cb) {
    if (!origin || CORS_ORIGINS.length === 0 || CORS_ORIGINS.includes(origin)) return cb(null, true);
    cb(new Error(`Origen no permitido por CORS: ${origin}`));
  },
}));
app.use(express.json({ limit: JSON_LIMIT }));
// El Facturador llama con application/x-www-form-urlencoded, no con JSON.
app.use(express.urlencoded({ extended: false, limit: JSON_LIMIT }));

// El testigo compartido separa al Facturador de cualquier otro que alcance el
// puerto. `/oauth/callback` queda fuera: lo abre Google en el navegador y no
// puede llevarlo; su defensa es el `state` de OAuth.
app.use((req, res, next) => {
  if (!SERVICE_TOKEN) return next();
  if (req.path.startsWith("/oauth/")) return next();

  const enviado = req.get("X-Service-Token") || req.query.token || req.body?.token || "";
  if (enviado === SERVICE_TOKEN) return next();

  return res.status(401).json({ ok: false, error: "Testigo de servicio invalido" });
});

// ── Estado en memoria (solo el access_token vive acá; todo lo demás está en BD) ─
let accessToken = null;
let accessTokenExp = 0; // epoch ms
let watching = false;
let watchTimer = null;
let ultimaRevision = null;
let revisando = false;

// Perfil de Google de la cuenta conectada. Solo el correo se guarda en BD; el
// nombre y la foto se piden a Google y viven en memoria, asi que tras reiniciar
// el servicio se recuperan solos en la primera consulta de /api/estado.
let perfil = null;
let perfilProximoIntento = 0; // epoch ms — evita reintentar en cada poll si Google falla

class AuthError extends Error {}

// ── Helpers de Gmail ───────────────────────────────────────────────────────────
async function googleGet(url, token) {
  const r = await fetch(url, { headers: { Authorization: `Bearer ${token}` } });
  if (r.status === 401 || r.status === 403) throw new AuthError("Token de Gmail expirado o inválido");
  if (!r.ok) throw new Error(`Google respondió ${r.status}`);
  return r.json();
}

async function gmail(path, token, params = {}) {
  const url = new URL(`https://gmail.googleapis.com/gmail/v1/users/me/${path}`);
  for (const [k, v] of Object.entries(params)) if (v != null) url.searchParams.set(k, v);
  return googleGet(url.toString(), token);
}

const b64 = (data) => Buffer.from(data, "base64url").toString("utf8");

/** Fecha local en el formato que acepta un DATETIME de MySQL. */
function fechaMySQL(d) {
  const p = (n) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ` +
         `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

function cuerpoDelMensaje(detail) {
  let plain = "", html = "";
  const walk = (part) => {
    if (!part) return;
    if (part.mimeType === "text/plain" && part.body?.data) plain += b64(part.body.data);
    if (part.mimeType === "text/html" && part.body?.data) html += b64(part.body.data);
    for (const p of part.parts || []) walk(p);
  };
  if (detail.payload?.body?.data) plain += b64(detail.payload.body.data);
  walk(detail.payload);

  if (plain.trim()) return plain;
  if (html.trim()) {
    return html
      .replace(/<style[\s\S]*?<\/style>/gi, " ")
      .replace(/<script[\s\S]*?<\/script>/gi, " ")
      .replace(/<br\s*\/?>/gi, "\n")
      .replace(/<\/(p|div|tr|td|h\d)>/gi, "\n")
      .replace(/<[^>]+>/g, " ")
      .replace(/&nbsp;/g, " ").replace(/&amp;/g, "&").replace(/&#\d+;/g, " ")
      .replace(/[ \t]+/g, " ").replace(/\n\s*\n+/g, "\n");
  }
  return detail.snippet || "";
}

const header = (detail, name) =>
  (detail.payload?.headers || []).find(h => h.name.toLowerCase() === name.toLowerCase())?.value || "";

// ── Token de acceso: se renueva solo con el refresh_token guardado ────────────
/** Devuelve un access_token válido, renovándolo si hace falta. Null si no hay conexión. */
async function tokenValido() {
  if (accessToken && Date.now() < accessTokenExp - 60_000) return accessToken;

  const cfg = await store.getSettings();
  const refreshToken = descifrar(cfg?.refresh_token_enc);
  if (!refreshToken) return null;

  const nuevo = await oauth.refreshAccessToken(refreshToken);
  accessToken = nuevo;
  accessTokenExp = Date.now() + 55 * 60 * 1000; // Google da ~1h; renovamos con margen
  return accessToken;
}

// ── Perfil de la cuenta conectada ────────────────────────────────────────────
/**
 * Nombre y foto de la cuenta de Gmail conectada, para pintarlos en Ajustes.
 * Nunca lanza: /api/estado es lo unico que le dice a la pantalla si el servicio
 * responde, y no puede caerse porque Google no conteste.
 */
async function perfilConectado(cfg) {
  if (!cfg?.gmail_email) { perfil = null; return null; }

  // Si se reconecto con otra cuenta, el perfil en memoria ya no corresponde.
  if (perfil && perfil.email !== cfg.gmail_email) perfil = null;
  if (perfil) return perfil;

  if (Date.now() < perfilProximoIntento) return { email: cfg.gmail_email, nombre: null, foto: null };

  try {
    const token = await tokenValido();
    if (!token) throw new Error("sin token");
    const p = await oauth.perfilGoogle(token);
    perfil = guardarPerfil(p, cfg.gmail_email);
    return perfil;
  } catch {
    perfilProximoIntento = Date.now() + 5 * 60 * 1000;
    return { email: cfg.gmail_email, nombre: null, foto: null };
  }
}

/** Normaliza la respuesta de userinfo a los tres campos que usa la pantalla. */
function guardarPerfil(p, emailPorDefecto) {
  perfilProximoIntento = 0;
  perfil = {
    email:  p?.email || emailPorDefecto || null,
    nombre: p?.name || null,
    foto:   p?.picture || null,
  };
  return perfil;
}

// ── Procesar un mensaje de Gmail → tec_sinpe_transactions ────────────────────
async function procesarMensaje(id, token, bancoId) {
  const detail = await gmail(`messages/${id}`, token, { format: "full" });
  const body = cuerpoDelMensaje(detail);
  const dateHeader = header(detail, "Date");

  const from = header(detail, "From");
  const d = extractSinpeData(body, dateHeader, bancoId, from);
  if (!d.esSinpe || !d.monto) return false;

  // Sin comprobante no se puede conciliar; suele indicar que el correo no es
  // un aviso de SINPE sino otro movimiento de la cuenta.
  if (!d.comprobante) return false;

  // Otros correos del mismo banco describen el mismo movimiento y lo duplicarian.
  if (!remitenteValidoSinpe(d.banco || d.bancoUsado, from)) return false;

  return store.guardarTransaccion({
    email_id: id,
    comprobante: d.comprobante,
    nombre: d.nombre,
    telefono: d.telefono,
    monto: d.monto,
    // Ver fechaMySQL(): toISOString() daria UTC y un formato invalido.
    fecha: d.fecha || fechaMySQL(new Date(dateHeader || Date.now())),
    descripcion: d.descripcion,
    banco: d.banco,
  });
}

async function revisarNuevos() {
  if (revisando) return;
  const cfg = await store.getSettings();
  if (!cfg?.activo) return;

  revisando = true;
  try {
    const token = await tokenValido();
    if (!token) {
      console.log("⚠ No hay refresh_token guardado — conectá Gmail desde Configuración > SINPE Móvil");
      detenerVigilancia();
      return;
    }

    const lista = await gmail("messages", token, { q: `${GMAIL_QUERY} newer_than:2d`, maxResults: 25 });
    ultimaRevision = new Date().toISOString();

    let nuevos = 0;
    for (const m of lista.messages || []) {
      if (await store.idConocido(m.id)) continue;
      if (await procesarMensaje(m.id, token, cfg.banco)) nuevos++;
    }
    await store.saveSettings({ last_check: fechaMySQL(new Date()) });
    if (nuevos) console.log(`🔔 ${nuevos} SINPE nuevo(s) guardado(s)`);
  } catch (e) {
    if (e instanceof AuthError) {
      // El refresh_token dejó de servir (fue revocado desde la cuenta de Google,
      // o venció por inactividad de más de 6 meses). Hay que reconectar Gmail
      // manualmente desde Configuración.
      console.log("⚠ El refresh_token ya no es válido — hay que reconectar Gmail desde Configuración");
      await store.saveSettings({ activo: 0 });
      detenerVigilancia();
    } else {
      console.log("⚠ Error revisando Gmail:", e.message);
    }
  } finally {
    revisando = false;
  }
}

// ── Importacion historica ─────────────────────────────────────────────────────
// La vigilancia normal solo cubre `newer_than:2d`. Esto pagina el historial
// completo de Gmail en segundo plano; el avance se consulta por /api/estado.
let importacion = null; // { corriendo, dias, revisados, guardados, omitidos, error, inicio, fin }

async function importarHistorico(dias) {
  if (importacion?.corriendo) return importacion;

  const cfg = await store.getSettings();
  if (!cfg?.gmail_email) throw new Error("No hay una cuenta de Gmail conectada");

  importacion = {
    corriendo: true, dias, revisados: 0, guardados: 0, omitidos: 0,
    error: null, inicio: new Date().toISOString(), fin: null,
  };

  (async () => {
    try {
      const token = await tokenValido();
      if (!token) throw new Error("No hay un token de Gmail valido");

      // Sin `newer_than` Gmail devuelve el historial entero de la cuenta.
      const q = dias > 0 ? `${GMAIL_QUERY} newer_than:${dias}d` : GMAIL_QUERY;
      let pageToken = null;

      do {
        const lista = await gmail("messages", token, { q, maxResults: 100, pageToken });
        for (const m of lista.messages || []) {
          importacion.revisados++;
          if (await store.idConocido(m.id)) { importacion.omitidos++; continue; }
          try {
            if (await procesarMensaje(m.id, token, cfg.banco)) importacion.guardados++;
            else importacion.omitidos++;
          } catch (e) {
            // Un correo ilegible no debe abortar el resto de la importacion.
            importacion.omitidos++;
            console.log(`   · mensaje ${m.id} omitido: ${e.message}`);
          }
        }
        pageToken = lista.nextPageToken || null;
        if (pageToken) console.log(`   … ${importacion.revisados} revisados, ${importacion.guardados} guardados`);
      } while (pageToken);

      console.log(`✅ Importacion terminada: ${importacion.guardados} SINPE guardados de ${importacion.revisados} correos revisados`);
    } catch (e) {
      importacion.error = e.message;
      console.log("⚠ Importacion historica fallida:", e.message);
    } finally {
      importacion.corriendo = false;
      importacion.fin = new Date().toISOString();
    }
  })();

  return importacion;
}

function iniciarVigilancia(segundos) {
  detenerVigilancia();
  watching = true;
  watchTimer = setInterval(revisarNuevos, Math.max(5, segundos || 15) * 1000);
  console.log(`👁  Vigilancia SINPE activa cada ${segundos}s`);
  revisarNuevos();
}

function detenerVigilancia() {
  if (watchTimer) clearInterval(watchTimer);
  watchTimer = null;
  watching = false;
}

// ── Rutas: estado (lo consume Settings.php para pintar el tab SINPE) ─────────
app.get("/health", (req, res) => res.json({ ok: true }));

app.get("/api/estado", async (req, res) => {
  const cfg = await store.getSettings();
  const stats = await store.statsRecientes();
  res.json({
    conectado: !!cfg?.gmail_email,
    gmailEmail: cfg?.gmail_email || null,
    perfil: await perfilConectado(cfg),
    activo: !!cfg?.activo,
    watching,
    banco: cfg?.banco || "auto",
    intervalo: cfg?.intervalo || 15,
    ultimaRevision,
    lastCheck: cfg?.last_check || null,
    stats,
    importacion,
  });
});

app.get("/api/bancos", (req, res) => res.json({ bancos: listaBancos() }));

// ── Rutas: conexión con Gmail (offline, con refresh_token) ───────────────────
app.get("/oauth2/authurl", (req, res) => {
  res.json({ url: oauth.buildAuthUrl() });
});

app.get("/oauth2/callback", async (req, res) => {
  const { code, error } = req.query;
  if (error) return res.redirect(`${RETURN_URL}?sinpe=error&motivo=${encodeURIComponent(error)}`);
  if (!code) return res.redirect(`${RETURN_URL}?sinpe=error&motivo=sin_codigo`);

  try {
    const tokens = await oauth.exchangeCodeForTokens(code);
    if (!tokens.refresh_token) {
      // Pasa si el usuario ya había autorizado antes y Google no reemite el
      // refresh_token. Con access_type=offline+prompt=consent no debería pasar,
      // pero si pasa, hay que revocar el acceso previo desde
      // https://myaccount.google.com/permissions y reintentar.
      return res.redirect(`${RETURN_URL}?sinpe=error&motivo=sin_refresh_token`);
    }
    const datosPerfil = await oauth.perfilGoogle(tokens.access_token);
    guardarPerfil(datosPerfil);

    await store.saveSettings({
      gmail_email: datosPerfil.email || null,
      refresh_token_enc: cifrar(tokens.refresh_token),
      activo: 1,
    });
    accessToken = tokens.access_token;
    accessTokenExp = Date.now() + (tokens.expires_in || 3300) * 1000;

    const cfg = await store.getSettings();
    iniciarVigilancia(cfg.intervalo);

    res.redirect(`${RETURN_URL}?sinpe=conectado`);
  } catch (e) {
    console.log("⚠ Error en /oauth2/callback:", e.message);
    res.redirect(`${RETURN_URL}?sinpe=error&motivo=${encodeURIComponent(e.message)}`);
  }
});

// POST /api/importar { dias } — dias=0: historial completo.
// Responde de inmediato; el avance se sigue por /api/estado.
app.post("/api/importar", async (req, res) => {
  const dias = Math.max(0, parseInt((req.body || {}).dias, 10) || 0);
  try {
    const estado = await importarHistorico(dias);
    res.json({ ok: true, importacion: estado });
  } catch (e) {
    res.status(400).json({ error: e.message });
  }
});

app.post("/api/desconectar", async (req, res) => {
  detenerVigilancia();
  accessToken = null;
  perfil = null;
  perfilProximoIntento = 0;
  await store.limpiarConexion();
  res.json({ ok: true });
});

// ── Rutas: configuración (banco / intervalo / activar-pausar) ────────────────
app.post("/api/banco", async (req, res) => {
  const { banco } = req.body;
  if (!BANCOS[banco]) return res.status(400).json({ error: `Banco desconocido: ${banco}` });
  await store.saveSettings({ banco });
  res.json({ ok: true, banco });
});

// Por HTTP todo llega como texto y la cadena "0" es truthy en JS.
function aBooleano(v) {
  if (typeof v === "boolean") return v;
  if (v == null) return null;
  const s = String(v).trim().toLowerCase();
  if (["1", "true", "on", "si", "sí"].includes(s)) return true;
  if (["0", "false", "off", "no", ""].includes(s)) return false;
  return null;
}

app.post("/api/vigilancia", async (req, res) => {
  const { activar, intervalo } = req.body || {};
  const cambios = {};
  const n = parseInt(intervalo, 10);
  if (Number.isFinite(n)) cambios.intervalo = Math.min(300, Math.max(5, n));
  const act = aBooleano(activar);
  if (act !== null) cambios.activo = act ? 1 : 0;
  const cfg = await store.saveSettings(cambios);

  if (cfg.activo) {
    const token = await tokenValido().catch(() => null);
    if (!token) return res.status(400).json({ error: "No hay una cuenta de Gmail conectada" });
    iniciarVigilancia(cfg.intervalo);
  } else {
    detenerVigilancia();
  }
  res.json({ ok: true, activo: !!cfg.activo, intervalo: cfg.intervalo });
});

// ── Arranque ───────────────────────────────────────────────────────────────────
async function arranqueAutomatico() {
  const cfg = await store.getSettings();
  if (cfg?.activo && cfg?.refresh_token_enc) {
    console.log("👁  Retomando vigilancia SINPE guardada (sin necesidad de abrir ningún navegador)…");
    iniciarVigilancia(cfg.intervalo);
  } else {
    console.log("ℹ  Sin vigilancia activa — conectá Gmail desde Configuración > SINPE Móvil.");
  }
}

app.listen(PORT, BIND_HOST, async () => {
  advertirConfig();
  console.log(`✅ Servicio SINPE corriendo en http://${BIND_HOST}:${PORT} (${EN_PRODUCCION ? "producción" : "desarrollo"})`);
  // Sin las tablas de SINPE el arranque falla, pero el servicio debe seguir
  // escuchando para poder responder con un error legible.
  try {
    await arranqueAutomatico();
  } catch (e) {
    console.error("⚠  No se pudo retomar la vigilancia guardada:", e.message);
  }
});

process.on("SIGTERM", async () => { detenerVigilancia(); await store.cerrarPool(); process.exit(0); });
process.on("SIGINT", async () => { detenerVigilancia(); await store.cerrarPool(); process.exit(0); });

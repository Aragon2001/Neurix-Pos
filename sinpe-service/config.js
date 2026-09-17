/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

// ── Configuración centralizada del servicio SINPE ────────────────────────────
// Este servicio es el "motor" que vigila Gmail y guarda las transacciones SINPE
// Móvil directamente en la base de datos MySQL del Facturador (NeurixBD), en las
// tablas tec_sinpe_transactions y tec_sinpe_settings (ver migración versionPOS 61
// en app/core/MY_Controller.php).
//
// Toda la configuración sensible o dependiente del entorno se lee de variables
// de entorno. Copiá `.env.example` a `.env` y completá los valores — nunca se
// sube al repositorio (`.env` ya está en el .gitignore del proyecto).
//
// Node 20.6+ carga `.env` con la bandera `--env-file-if-exists=.env` (ver
// package.json, scripts start/dev).

function env(clave, porDefecto) {
  const v = process.env[clave];
  return v == null || v === "" ? porDefecto : v;
}

function envNum(clave, porDefecto) {
  const n = parseInt(process.env[clave], 10);
  return Number.isFinite(n) ? n : porDefecto;
}

function envReq(clave) {
  const v = process.env[clave];
  if (v == null || v === "") {
    throw new Error(
      `Falta la variable de entorno obligatoria ${clave}. Copiá .env.example a .env y completala.`
    );
  }
  return v;
}

// Puerto de escucha del servicio. Corre solo en localhost — NO debe exponerse
// directamente a internet (ver README.md, sección "Seguridad").
export const PORT = envNum("PORT", 3001);

// El servicio solo lo llama el Facturador, que corre en la misma maquina: sin
// esto escucha en todas las interfaces y queda expuesto a la red del negocio.
export const BIND_HOST = env("BIND_HOST", "127.0.0.1");

// Testigo compartido con el Facturador. Vacio = sin exigencia, que es lo que
// habia hasta ahora; en cuanto se define, toda peticion tiene que traerlo.
export const SERVICE_TOKEN = env("SERVICE_TOKEN", "");

export const NODE_ENV = env("NODE_ENV", "development");
export const EN_PRODUCCION = NODE_ENV === "production";

// ── CORS ──────────────────────────────────────────────────────────────────────
// El único cliente HTTP de este servicio es el propio Facturador (llamadas de
// servidor a servidor desde Settings.php/Pos.php vía cURL) y el navegador del
// admin cuando hace click en "Conectar con Gmail" (redirección de página
// completa, no fetch — por eso CORS casi no aplica, pero se deja preparado por
// si en el futuro se llama con fetch desde el navegador).
export const CORS_ORIGINS = env("CORS_ORIGINS", "")
  .split(",")
  .map(s => s.trim())
  .filter(Boolean);

// ── Base de datos MySQL (la misma que usa el Facturador) ─────────────────────
export const DB_HOST = env("DB_HOST", "127.0.0.1");
export const DB_PORT = envNum("DB_PORT", 3306);
export const DB_USER = env("DB_USER", "root");
export const DB_PASSWORD = env("DB_PASSWORD", "");
export const DB_NAME = env("DB_NAME", "NeurixBD");
// Prefijo de tablas de CodeIgniter (app/config/database.php → 'dbprefix').
export const DB_PREFIX = env("DB_PREFIX", "tec_");

// ── OAuth de Gmail (flujo offline, con refresh_token) ─────────────────────────
// Cliente OAuth "Web application" del proveedor del sistema (scope gmail.readonly).
// El negocio que instala el servicio solo autoriza su cuenta de Gmail desde
// Ajustes → SINPE Móvil; no ingresa estas credenciales. Se pueden sobrescribir
// por entorno si hiciera falta un cliente propio.
const GOOGLE_CLIENT_ID_DEFECTO = "";
const GOOGLE_CLIENT_SECRET_DEFECTO = "";

export function googleClientId() { return env("GOOGLE_CLIENT_ID", GOOGLE_CLIENT_ID_DEFECTO); }
export function googleClientSecret() { return env("GOOGLE_CLIENT_SECRET", GOOGLE_CLIENT_SECRET_DEFECTO); }
// Debe coincidir EXACTO (incluyendo http/https y el path) con lo registrado en
// Google Cloud Console como "Authorized redirect URI".
export function googleRedirectUri() {
  return env("GOOGLE_REDIRECT_URI", `http://127.0.0.1:${PORT}/oauth2/callback`);
}

// A dónde redirigir al navegador cuando termina el flujo de conexión con Gmail
// (la pantalla de Configuración > SINPE Móvil del Facturador).
export const RETURN_URL = env("RETURN_URL", "http://localhost/settings#tab-sinpe");

// Clave con la que se cifra el refresh_token antes de guardarlo en la base
// (AES-256-GCM, ver crypto.js). Debe ser 32 bytes en base64.
// Generarla una vez con: node -e "console.log(require('crypto').randomBytes(32).toString('base64'))"
export function tokenKey() { return envReq("SINPE_TOKEN_KEY"); }

// ── Consulta de Gmail ─────────────────────────────────────────────────────────
export const GMAIL_QUERY = env(
  "GMAIL_QUERY",
  'SINPE OR "sinpe movil" OR "transferencia sinpe" OR "ha recibido" OR colones'
);

export const JSON_LIMIT = env("JSON_LIMIT", "1mb");

export function advertirConfig(log = console) {
  if (EN_PRODUCCION && !SERVICE_TOKEN) {
    log.warn("⚠  SERVICE_TOKEN está vacío: cualquiera que alcance el puerto puede operar el servicio.");
  }
  if (BIND_HOST !== "127.0.0.1" && BIND_HOST !== "localhost") {
    log.warn(`⚠  El servicio escucha en ${BIND_HOST}, no solo en loopback.`);
  }
  if (EN_PRODUCCION && CORS_ORIGINS.length === 0) {
    log.warn("⚠  CORS_ORIGINS está vacío en producción.");
  }
}

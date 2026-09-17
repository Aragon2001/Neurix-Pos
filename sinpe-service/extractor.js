/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

// ── Motor de extracción SINPE ────────────────────────────────────────────────
import { patronesPara, detectarBanco } from "./bancos.js";

/**
 * Parsea montos en formato costarricense, que llega de dos maneras según el banco:
 *   "3,950.00"  → 3950     (coma = miles, punto = decimal)
 *   "3.950,00"  → 3950     (punto = miles, coma = decimal)
 *   "3.950"     → 3950     (punto = miles)
 *   "3.95"      → 3.95     (punto = decimal)
 */
export function parseMonto(raw) {
  if (raw == null) return null;
  let s = String(raw).trim().replace(/\s/g, "");
  const lastComma = s.lastIndexOf(",");
  const lastDot = s.lastIndexOf(".");

  if (lastComma > -1 && lastDot > -1) {
    if (lastComma > lastDot) s = s.replace(/\./g, "").replace(",", ".");  // 3.950,00
    else s = s.replace(/,/g, "");                                          // 3,950.00
  } else if (lastComma > -1) {
    if (/,\d{1,2}$/.test(s)) s = s.replace(",", ".");                      // 3950,00
    else s = s.replace(/,/g, "");                                          // 3,950
  } else if (lastDot > -1) {
    if (/\.\d{3}$/.test(s)) s = s.replace(/\./g, "");                      // 3.950 → miles
  }

  const n = parseFloat(s);
  return Number.isFinite(n) && n > 0 ? n : null;
}

function primerMatch(text, patrones, transform = (m) => m[1].trim()) {
  for (const re of patrones) {
    const m = text.match(re);
    if (m && m[1]) {
      const v = transform(m);
      if (v) return v;
    }
  }
  return null;
}

const MESES = {
  enero: 1, febrero: 2, marzo: 3, abril: 4, mayo: 5, junio: 6,
  julio: 7, agosto: 8, septiembre: 9, setiembre: 9, octubre: 10, noviembre: 11, diciembre: 12,
};

/**
 * Fecha en 'YYYY-MM-DD HH:MM:SS' y hora local, formato que acepta un DATETIME
 * de MySQL. No usar toISOString(): produce UTC y un sufijo que MySQL rechaza.
 */
function formatearFechaMySQL(d) {
  const p = (n) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ` +
         `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

function extraerFecha(text, emailDate) {
  const patrones = [
    /(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?/,
    /(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})\s+(\d{1,2}):(\d{2})/,
    /(\d{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\s+(?:de\s+)?(\d{4})/i,
  ];
  for (const re of patrones) {
    const m = text.match(re);
    if (!m) continue;
    try {
      let d;
      if (MESES[m[2]?.toLowerCase()]) {
        d = new Date(parseInt(m[3]), MESES[m[2].toLowerCase()] - 1, parseInt(m[1]));
      } else if (m[1].length === 4) {
        d = new Date(`${m[1]}-${m[2].padStart(2, "0")}-${m[3].padStart(2, "0")}T${m[4].padStart(2, "0")}:${m[5]}:00`);
      } else {
        d = new Date(`${m[3]}-${m[2].padStart(2, "0")}-${m[1].padStart(2, "0")}T${m[4].padStart(2, "0")}:${m[5]}:00`);
      }
      if (!isNaN(d.getTime())) return formatearFechaMySQL(d);
    } catch { /* sigue con el próximo patrón */ }
  }
  if (emailDate) {
    const d = new Date(emailDate);
    if (!isNaN(d.getTime())) return formatearFechaMySQL(d);
  }
  return null;
}

export function esCorreoSinpe(text) {
  return (
    /sinpe\s*m[oó]vil/i.test(text) ||
    /transferencia\s*sinpe/i.test(text) ||
    /ha\s+recibido.*colones/i.test(text) ||
    /pago\s*sinpe/i.test(text) ||
    /monto\s*recibido/i.test(text) ||
    /comprobante\s*de\s*(pago|transferencia)/i.test(text) ||
    /\bsinpe\b/i.test(text)
  );
}

/**
 * Extrae los datos de un correo SINPE usando el formato del banco indicado.
 * @param {string} body      Cuerpo del correo en texto plano.
 * @param {string} emailDate Header Date del correo (fallback de fecha).
 * @param {string} bancoId   'auto' o el id de un banco de bancos.js
 * @param {string} from      Remitente, ayuda a identificar el banco.
 */
export function extractSinpeData(body, emailDate, bancoId = "auto", from = "") {
  const text = body || "";
  if (!esCorreoSinpe(text)) return { esSinpe: false };

  const bancoDetectado = detectarBanco(text, from);
  // En 'auto' usamos el banco detectado; si el usuario fijó uno, mandan sus patrones.
  const banco = bancoId && bancoId !== "auto" ? bancoId : (bancoDetectado || "auto");

  const monto = primerMatch(text, patronesPara("monto", banco), (m) => parseMonto(m[1]));

  const nombre = primerMatch(text, patronesPara("nombre", banco), (m) =>
    m[1].replace(/_/g, " ").trim().replace(/\s+/g, " ").replace(/[.,;:]+$/, "")
  );

  let comprobante = primerMatch(text, patronesPara("comprobante", banco), (m) =>
    m[1].replace(/\s+/g, "").toUpperCase()
  );

  const telefono = primerMatch(text, patronesPara("telefono", banco), (m) =>
    m[1].replace(/[\s\-]/g, "")
  );

  const descripcion = primerMatch(text, patronesPara("descripcion", banco));
  const fecha = extraerFecha(text, emailDate);

  return {
    esSinpe: true,
    banco: bancoDetectado || (bancoId !== "auto" ? bancoId : null),
    bancoUsado: banco,
    comprobante: comprobante || null,
    nombre,
    telefono,
    monto,
    fecha,
    descripcion,
  };
}

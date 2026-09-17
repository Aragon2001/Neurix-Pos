/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

// ── Persistencia en MySQL (la misma base del Facturador) ─────────────────────
// Reemplaza al db.js original del sinpe-analyzer (que usaba SQLite propia).
// Escribe directamente en tec_sinpe_transactions y tec_sinpe_settings, creadas
// por la migración versionPOS 61 de app/core/MY_Controller.php — este servicio
// NUNCA crea ni altera tablas, solo lee/escribe filas. Si esas tablas no
// existen todavía, es que el Facturador no corrió la migración: arrancar el
// Facturador (o correr `php index.php migrate` si existe) antes que este
// servicio.
//
// Simplificación deliberada respecto al sinpe-analyzer original: acá solo se
// soporta UNA cuenta de Gmail conectada a la vez (fila única en
// tec_sinpe_settings, id_sinpe_settings = 1). El negocio tiene una sola cuenta
// SINPE, no hace falta el multi-cuenta del proyecto original.
import mysql from "mysql2/promise";
import { DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME, DB_PREFIX } from "./config.js";

export const pool = mysql.createPool({
  host: DB_HOST,
  port: DB_PORT,
  user: DB_USER,
  password: DB_PASSWORD,
  database: DB_NAME,
  waitForConnections: true,
  connectionLimit: 5,
  dateStrings: true,
});

const t = (nombre) => `\`${DB_PREFIX}${nombre}\``;

// ── Settings (fila única) ─────────────────────────────────────────────────────
export async function getSettings() {
  const [rows] = await pool.query(`SELECT * FROM ${t("sinpe_settings")} WHERE id_sinpe_settings = 1`);
  return rows[0] || null;
}

export async function saveSettings(cambios) {
  const columnas = ["gmail_email", "refresh_token_enc", "banco", "intervalo", "activo", "last_check"];
  const sets = [];
  const valores = [];
  for (const c of columnas) {
    if (Object.prototype.hasOwnProperty.call(cambios, c)) {
      sets.push(`${c} = ?`);
      valores.push(cambios[c]);
    }
  }
  if (!sets.length) return getSettings();
  sets.push("updated_at = NOW()");
  await pool.query(
    `UPDATE ${t("sinpe_settings")} SET ${sets.join(", ")} WHERE id_sinpe_settings = 1`,
    valores
  );
  return getSettings();
}

export async function limpiarConexion() {
  return saveSettings({ gmail_email: null, refresh_token_enc: null, activo: 0 });
}

// ── Transacciones ─────────────────────────────────────────────────────────────
/**
 * Inserta una transacción SINPE nueva. Si ya existe una fila con el mismo
 * email_id (mismo correo de Gmail ya procesado antes), no la duplica ni pisa
 * su estado — el estado ('pendiente'/'usado') lo controla solo el POS.
 * Devuelve true si insertó una fila nueva, false si ya existía.
 */
export async function guardarTransaccion(p) {
  const [res] = await pool.query(
    `INSERT INTO ${t("sinpe_transactions")}
      (email_id, comprobante, nombre, telefono, monto, fecha, banco, descripcion, estado, created_at)
     VALUES (?,?,?,?,?,?,?,?, 'pendiente', NOW())
     ON DUPLICATE KEY UPDATE email_id = email_id`,
    [
      p.email_id, p.comprobante ?? null, p.nombre ?? null, p.telefono ?? null,
      p.monto ?? null, p.fecha ?? null, p.banco ?? null, p.descripcion ?? null,
    ]
  );
  return res.affectedRows === 1; // 1 = insertó · 0 (o 2 por el UPDATE no-op) = ya existía
}

export async function idConocido(emailId) {
  const [rows] = await pool.query(
    `SELECT 1 FROM ${t("sinpe_transactions")} WHERE email_id = ? LIMIT 1`,
    [emailId]
  );
  return rows.length > 0;
}

export async function statsRecientes() {
  const [[r]] = await pool.query(`
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
      SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) AS hoy,
      COALESCE(SUM(monto), 0) AS monto_total
    FROM ${t("sinpe_transactions")}
  `);
  return r;
}

export async function cerrarPool() {
  await pool.end();
}

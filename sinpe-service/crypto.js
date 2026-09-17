/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

// ── Cifrado del refresh_token de Gmail en reposo ──────────────────────────────
// El refresh_token es una credencial de larga duración: quien lo tenga puede
// leer los correos de Gmail de la cuenta conectada hasta que se revoque
// manualmente. Se guarda cifrado con AES-256-GCM en tec_sinpe_settings, nunca
// en texto plano — aunque la tabla no sea de acceso público, es la práctica
// correcta para un secreto de este tipo.
import crypto from "node:crypto";
import { tokenKey } from "./config.js";

function claveBuffer() {
  const buf = Buffer.from(tokenKey(), "base64");
  if (buf.length !== 32) {
    throw new Error(
      "SINPE_TOKEN_KEY debe ser una clave de 32 bytes en base64. Generala con: " +
      `node -e "console.log(require('crypto').randomBytes(32).toString('base64'))"`
    );
  }
  return buf;
}

/** Devuelve un string "iv:tag:datos" (todo en base64), listo para guardar en TEXT. */
export function cifrar(textoPlano) {
  const iv = crypto.randomBytes(12);
  const cipher = crypto.createCipheriv("aes-256-gcm", claveBuffer(), iv);
  const datos = Buffer.concat([cipher.update(String(textoPlano), "utf8"), cipher.final()]);
  const tag = cipher.getAuthTag();
  return [iv.toString("base64"), tag.toString("base64"), datos.toString("base64")].join(":");
}

export function descifrar(valorCifrado) {
  if (!valorCifrado) return null;
  const [ivB64, tagB64, datosB64] = String(valorCifrado).split(":");
  if (!ivB64 || !tagB64 || !datosB64) return null;
  const decipher = crypto.createDecipheriv("aes-256-gcm", claveBuffer(), Buffer.from(ivB64, "base64"));
  decipher.setAuthTag(Buffer.from(tagB64, "base64"));
  const datos = Buffer.concat([
    decipher.update(Buffer.from(datosB64, "base64")),
    decipher.final(),
  ]);
  return datos.toString("utf8");
}

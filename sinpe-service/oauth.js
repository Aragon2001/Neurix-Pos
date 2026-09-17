/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

// ── OAuth de Gmail en modo offline (con refresh_token) ────────────────────────
// A diferencia del sinpe-analyzer original (que usaba Google Identity Services
// del lado del navegador y solo obtenía un access_token de ~1h, dependiente de
// una pestaña abierta para renovarlo), acá se usa el flujo "Authorization Code"
// clásico de servidor: el usuario autoriza una vez desde Configuración, Google
// redirige a /oauth2/callback con un código, y ese código se cambia por un
// access_token + un refresh_token de larga duración. El refresh_token permite
// renovar el acceso solo, sin depender de ningún navegador abierto.
//
// Requiere un cliente OAuth de tipo "Web application" (con Client Secret) en
// Google Cloud Console — ver README.md para el paso a paso.
import fetch from "node-fetch";
import { googleClientId, googleClientSecret, googleRedirectUri } from "./config.js";

const SCOPES = [
  "https://www.googleapis.com/auth/gmail.readonly",
  "openid",
  "email",
  "profile",
].join(" ");

/** URL a la que hay que mandar al navegador para que el usuario autorice acceso. */
export function buildAuthUrl(state) {
  const url = new URL("https://accounts.google.com/o/oauth2/v2/auth");
  url.searchParams.set("client_id", googleClientId());
  url.searchParams.set("redirect_uri", googleRedirectUri());
  url.searchParams.set("response_type", "code");
  url.searchParams.set("scope", SCOPES);
  // access_type=offline + prompt=consent es lo que garantiza que Google entregue
  // un refresh_token (sin 'prompt=consent', si el usuario ya autorizó antes,
  // Google a veces omite el refresh_token en el intercambio).
  url.searchParams.set("access_type", "offline");
  url.searchParams.set("prompt", "consent");
  url.searchParams.set("include_granted_scopes", "true");
  if (state) url.searchParams.set("state", state);
  return url.toString();
}

async function tokenRequest(params) {
  const r = await fetch("https://oauth2.googleapis.com/token", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(params),
  });
  const data = await r.json();
  if (!r.ok) {
    throw new Error(`Google OAuth respondió ${r.status}: ${data.error_description || data.error || "error desconocido"}`);
  }
  return data;
}

/** Cambia el "code" del callback por { access_token, refresh_token, expires_in }. */
export async function exchangeCodeForTokens(code) {
  return tokenRequest({
    code,
    client_id: googleClientId(),
    client_secret: googleClientSecret(),
    redirect_uri: googleRedirectUri(),
    grant_type: "authorization_code",
  });
}

/** Pide un access_token nuevo a partir del refresh_token guardado. */
export async function refreshAccessToken(refreshToken) {
  const data = await tokenRequest({
    refresh_token: refreshToken,
    client_id: googleClientId(),
    client_secret: googleClientSecret(),
    grant_type: "refresh_token",
  });
  return data.access_token;
}

export async function perfilGoogle(accessToken) {
  const r = await fetch("https://www.googleapis.com/oauth2/v3/userinfo", {
    headers: { Authorization: `Bearer ${accessToken}` },
  });
  if (!r.ok) return {};
  return r.json();
}

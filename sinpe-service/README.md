# Servicio SINPE

Proceso Node que vigila una casilla de Gmail, extrae los avisos de SINPE Móvil y
los escribe en la base del Facturador (`tec_sinpe_transactions`).

El POS **no depende de este proceso para cobrar**: lee la tabla directo. Lo que sí
depende es que aparezcan pagos nuevos. Con el servicio detenido, la caja solo ve
lo que se registró antes.

---

## Arranque

```bash
cd www/sinpe-service
npm install     # solo la primera vez
npm start
```

Escucha en `http://127.0.0.1:3001` y **solo en localhost**. No debe exponerse a
internet: no tiene autenticación propia, confía en que quien lo llama es el
Facturador corriendo en la misma máquina.

### Que sobreviva a un reinicio

Ese `npm start` muere al cerrar la terminal o al reiniciar la computadora, y
entonces dejan de registrarse pagos sin ningún aviso. Para dejarlo permanente:

```bash
npm install -g pm2
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup          # una sola vez; deja el arranque automático en el sistema
```

En Windows, `pm2 startup` no basta por sí solo: hay que agregar además
`pm2-windows-startup` (`npm i -g pm2-windows-startup && pm2-startup install`).

Para comprobar que está arriba:

```bash
curl http://127.0.0.1:3001/api/estado
```

La pantalla **Ajustes → SINPE Móvil** avisa en rojo cuando el proceso no responde.

### Que avise solo cuando se caiga

Ese aviso en rojo solo lo ve quien entre a Ajustes. El Facturador trae un
vigilante que manda un correo la primera vez que el servicio deja de responder
y otro cuando vuelve — entre medio no repite nada:

```bash
php index.php sinpe vigilante
```

Se corre desde la raíz del Facturador. Dejalo en el Programador de tareas de
Windows cada 10 o 15 minutos (o en un `cron` en Linux). El correo sale a
`email_emisor`, o a `default_email` si el primero está vacío, y cada cambio de
estado queda además en `tec_audit_log` como `sinpe_caido` / `sinpe_restablecido`.

---

## Configuración

Todo sale de variables de entorno. Copiá `.env.example` a `.env` y completalo;
`.env` no se sube al repositorio.

| Variable | Para qué |
|---|---|
| `PORT` | Puerto de escucha (3001 por omisión) |
| `DB_*` | La misma base que usa el Facturador |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Opcional. El cliente OAuth del proveedor ya viene en `config.js`; solo se definen para usar un cliente propio |
| `GOOGLE_REDIRECT_URI` | Opcional. Por defecto `http://127.0.0.1:<PORT>/oauth2/callback`; si se define, exacto con lo registrado en Google |
| `RETURN_URL` | A dónde vuelve el navegador al terminar de conectar |
| `SINPE_TOKEN_KEY` | Clave AES-256 con la que se cifra el refresh token |
| `GMAIL_QUERY` | Filtro de búsqueda en Gmail |

`SINPE_TOKEN_KEY` se genera una sola vez:

```bash
node -e "console.log(require('crypto').randomBytes(32).toString('base64'))"
```

Si se cambia, el refresh token guardado deja de poder descifrarse y hay que
volver a conectar la cuenta de Gmail.

---

## El cliente OAuth de Google

El cliente OAuth es del proveedor del sistema y ya viene embebido en `config.js`
(`GOOGLE_CLIENT_ID_DEFECTO` / `GOOGLE_CLIENT_SECRET_DEFECTO`). El negocio que
instala el servicio **no crea ni ingresa nada**: solo autoriza su cuenta de Gmail
desde *Ajustes → SINPE Móvil* en el Facturador.

El alcance que se pide es `gmail.readonly`: el servicio lee, nunca envía ni borra.
La cuenta de Gmail que se conecta debe estar como *usuario de prueba* en la
pantalla de consentimiento del proyecto del proveedor mientras la app no esté
publicada (en modo prueba el refresh token caduca a los 7 días).

Para usar un cliente OAuth propio: crealo en Google Cloud Console como tipo
**Web application** (con Client Secret), registrá `http://127.0.0.1:3001/oauth2/callback`
en *Authorized redirect URIs* y definí `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`
en el `.env`.

---

## Cómo conserva el acceso

Usa el flujo *Authorization Code* de servidor: el administrador autoriza una vez
desde Configuración, Google devuelve un `refresh_token` de larga duración y el
servicio renueva el `access_token` solo. No hace falta dejar ninguna pestaña
abierta.

El `refresh_token` se guarda cifrado con AES-256-GCM (`crypto.js`).

---

## API interna

Todas las rutas son de consumo del Facturador, no del navegador del cajero.

| Método | Ruta | Para qué |
|---|---|---|
| GET | `/api/estado` | Estado, cuenta conectada, estadísticas |
| GET | `/oauth2/authurl` | URL a la que mandar el navegador para autorizar |
| GET | `/oauth2/callback` | Retorno de Google con el código |
| POST | `/api/desconectar` | Olvida el refresh token |
| POST | `/api/importar` | Importa histórico (`dias`, 0 = todo) |
| POST | `/api/banco` | Formato de banco a usar al extraer |
| POST | `/api/vigilancia` | Activa/pausa la vigilancia y su intervalo |

# QZ Tray — impresión sin el aviso "An anonymous request…"

## Qué pasaba

QZ Tray abría su ventana nativa en **cada** petición y el check
*"Remember this decision"* no servía. El detalle de la petición
(*View request details*) mostraba el motivo real:

| Campo       | Valor                |
|-------------|----------------------|
| Signature   | **Missing**          |
| Validity    | **Invalid**          |
| Common Name | An anonymous request |
| Fingerprint | **UNKNOWN REQUEST**  |

No era el certificado TLS de `localhost` ni el navegador: QZ Tray **no valida
el certificado del sitio web, valida la firma digital de cada petición**. Una
petición sin firmar es anónima y no tiene huella, así que QZ no tiene nada que
"recordar" — por eso volvía a preguntar siempre.

## Cómo quedó (no hay pasos manuales)

**En el servidor — automático.** La primera vez que una caja abre el POS, el
servidor genera solo su par certificado + llave en `files/certificados/qz/`
(`app/libraries/Qzcert.php`). La llave privada nunca sale de ahí: al navegador
solo viajan el certificado público y la firma ya calculada de cada llamada.

**En cada caja — un solo archivo.** El botón *"Descargar e instalar"* del
overlay del POS ya no baja un instalador genérico: descarga
`posprint/qz_installer`, un `.bat` generado por el servidor que trae la
dirección de ESE POS escrita adentro y que, al ejecutarse:

1. instala QZ Tray si falta (`qz.sh/install.ps1`),
2. descarga el certificado del POS y lo deja como `override.crt` dentro de la
   carpeta de QZ Tray — ese archivo es el que hace que QZ confíe sin preguntar,
3. reinicia QZ Tray.

El cajero solo abre el archivo descargado y acepta el aviso de administrador
de Windows. No hay que escribir direcciones ni copiar certificados.

Si el paso 2 falla (por ejemplo, sin red al momento de instalar), el instalador
avisa y sigue: el POS funciona igual y QZ Tray pregunta **una sola vez**, y ahí
sí el *"Remember this decision"* se guarda de verdad, porque ya hay firma y
huella.

## Cómo queda el flujo en cada impresión

1. El navegador pide `posprint/qz_certificate` y se lo entrega a QZ Tray.
2. Por cada llamada (`printers.find`, `print`, apertura de cajón) QZ pide
   firmar una cadena con la llamada, sus parámetros y un *timestamp*.
3. `posprint/qz_sign` la firma con SHA-512 usando la llave privada (exige
   sesión iniciada) y devuelve la firma en base64.
4. QZ Tray valida firma + certificado. Con el certificado en `override.crt`,
   imprime sin preguntar nada.

Si el servidor todavía no pudo generar el par (por permisos de escritura, por
ejemplo), el POS se degrada solo al modo sin firma anterior en vez de quedarse
colgado.

## Operación manual (solo si hace falta)

```bash
php tools/qz/generar-certificado-qz.php --info     # ver el certificado actual
php tools/qz/generar-certificado-qz.php --force    # regenerarlo
php tools/qz/generar-certificado-qz.php --force --cn="NeurixPOS"
```

- `--cn` es el nombre que QZ Tray muestra como titular de la petición (por
  defecto `Neurix POS`). Cambiarlo genera otro certificado: hay que volver a
  ejecutar el instalador en cada caja para reponer `override.crt`.
- `themes/default/assets/confiar-qz-tray.bat` reinstala solo el permiso en una
  caja que ya tiene QZ Tray (pregunta la dirección del POS).
- Instalar el permiso a mano: copiar `files/certificados/qz/digital-certificate.txt`
  a `C:\Program Files\QZ Tray\override.crt` y reiniciar QZ Tray.

Variables opcionales de `.env` (solo si se quieren otras rutas o nombres):

```
QZ_CERT_DIR=      QZ_CERT_PATH=     QZ_KEY_PATH=
QZ_KEY_PASS=      QZ_CERT_CN=       QZ_CERT_ORG=      QZ_OPENSSL_CONF=
```

## Problemas frecuentes

| Síntoma | Causa | Solución |
|---|---|---|
| Sigue diciendo *Signature: Missing* | El servidor no pudo generar el par | Ver `app/logs/`; revisar permisos de escritura en `files/certificados/` y `extension=openssl` en php.ini |
| *No se pudo generar la llave privada* en el log | PHP en Windows no encuentra `openssl.cnf` | `QZ_OPENSSL_CONF=C:\laragon\bin\php\php-8.x\extras\ssl\openssl.cnf` en `.env` |
| *Signature: Invalid* | Hora desfasada en la caja (el timestamp firmado caduca) | Sincronizar la hora de Windows |
| Pregunta una vez por caja | El `override.crt` no se instaló | Volver a ejecutar el instalador como administrador |
| Dejó de firmar tras un rato | Sesión del POS vencida (`qz_sign` exige sesión) | Volver a iniciar sesión |
| Se regeneró el certificado | Las cajas tienen el `override.crt` viejo | Reejecutar el instalador en cada caja |

# QZ Tray — quitar el aviso "An anonymous request wants to access connected printers"

## Qué está pasando

QZ Tray abre su ventana nativa en **cada** petición y el check
*"Remember this decision"* no sirve de nada. En el detalle de la petición
(*View request details*) se ve el motivo real:

| Campo       | Valor              |
|-------------|--------------------|
| Signature   | **Missing**        |
| Validity    | **Invalid**        |
| Common Name | An anonymous request |
| Fingerprint | **UNKNOWN REQUEST** |

No es el certificado TLS de `localhost` ni el navegador: QZ Tray no valida el
certificado del sitio web, valida **la firma digital de cada petición**. Una
petición sin firmar es anónima y no tiene huella, así que QZ no tiene nada que
"recordar" — por eso vuelve a preguntar siempre, aunque se marque la casilla.

La solución soportada por QZ es firmar las peticiones con un certificado
propio: la llave privada se queda en el servidor del POS y el navegador solo
recibe el certificado público y la firma ya calculada de cada llamada.

## Instalación (una sola vez, en el servidor)

```bash
php tools/qz/generar-certificado-qz.php
```

Genera en `files/certificados/qz/`:

- `digital-certificate.txt` — certificado público (lo sirve el POS).
- `private-key.pem` — llave privada. **No se copia a las terminales ni se
  versiona**; solo la usa el servidor desde `PosPrint::qz_sign()`.

Verificar que quede publicado:

```
http://<direccion-del-pos>/posprint/qz_certificate
```

Debe devolver el bloque `-----BEGIN CERTIFICATE-----`.

> En Windows (Laragon/XAMPP) PHP a veces no encuentra `openssl.cnf`. Si el
> script lo reporta, repetir con
> `--openssl-conf="C:\laragon\bin\php\php-8.x\extras\ssl\openssl.cnf"`.

Rutas alternativas (opcional) vía `.env`:

```
QZ_CERT_PATH=/ruta/digital-certificate.txt
QZ_KEY_PATH=/ruta/private-key.pem
QZ_KEY_PASS=   # solo si la llave está protegida por contraseña
```

## Confiar el certificado (una sola vez, en cada terminal)

Con solo firmar, QZ Tray ya pregunta **una vez por terminal** y ahí sí el
*"Remember this decision"* se guarda de verdad. Para que no pregunte ni esa
vez, se instala el certificado como `override.crt` de QZ Tray:

1. Descargar `themes/default/assets/confiar-qz-tray.bat` en la terminal.
2. Ejecutarlo **como administrador** y escribir la dirección del POS.
3. El script descarga el certificado, lo copia como `override.crt` dentro de
   la carpeta de QZ Tray y reinicia QZ Tray.

Manual, si se prefiere: copiar el contenido de `digital-certificate.txt` a
`C:\Program Files\QZ Tray\override.crt` y reiniciar QZ Tray.

## Cómo queda el flujo

1. El navegador pide `posprint/qz_certificate` y se lo entrega a QZ Tray.
2. Por cada llamada (`printers.find`, `print`, apertura de cajón) QZ pide
   firmar una cadena que incluye la llamada, sus parámetros y un *timestamp*.
3. `posprint/qz_sign` la firma con SHA-512 usando la llave privada (requiere
   sesión iniciada) y devuelve la firma en base64.
4. QZ Tray valida firma + certificado: si el certificado está en
   `override.crt`, ejecuta sin preguntar nada.

Si el certificado todavía no existe, el POS sigue funcionando en modo sin
firma (el comportamiento anterior, con el diálogo nativo): no rompe las
instalaciones que aún no lo hayan generado.

## Problemas frecuentes

| Síntoma | Causa | Solución |
|---|---|---|
| Sigue diciendo *Signature: Missing* | El servidor no tiene el par generado o `openssl` deshabilitado en PHP | Ejecutar el generador y revisar `extension=openssl` en `php.ini` |
| *Signature: Invalid* | La terminal tiene la hora desfasada (el timestamp firmado caduca) | Sincronizar la hora de Windows |
| Pregunta una vez por terminal | El certificado no está como `override.crt` | Ejecutar `confiar-qz-tray.bat` como administrador |
| Dejó de firmar tras un rato | Sesión del POS vencida (`qz_sign` exige sesión) | Volver a iniciar sesión en el POS |
| Se cambió el certificado | `override.crt` viejo en las terminales | Volver a ejecutar `confiar-qz-tray.bat` en cada una |

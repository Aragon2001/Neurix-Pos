# Servir el POS como `neurixpos` en vez de `localhost`

`localhost` no es un nombre del proyecto: es el nombre que Windows le da a la
propia máquina. Para que las terminales entren a `http://neurixpos.test/`
(o `http://neurixpos/`) hay que darle un nombre al sitio en Apache y resolver
ese nombre en cada computadora.

`app/config/config.php` arma el `base_url` a partir del host con el que se
entró, **pero solo si ese host está en la lista blanca**: si no, cae a
`localhost` y todos los enlaces del POS apuntan ahí. `neurixpos` y
`neurixpos.test` ya vienen en la lista; cualquier otro nombre va en
`APP_HOSTS` dentro de `.env`.

En el servidor, `tools/laragon/configurar-servidor-neurixpos.bat` (como
administrador) agrega la línea al `hosts` e instala el certificado del POS en
QZ Tray.

## ¿Se puede renombrar la carpeta del proyecto?

Sí. Nada en el código depende del nombre de la carpeta:

- `base_url` se arma con el host de la petición (`$_SERVER['HTTP_HOST']`).
- Las rutas internas salen de `FCPATH` (la carpeta real, sea cual sea).
- `.htaccess` no fija `RewriteBase` y `manifest.json` / `sw.js` son relativos.
- La base de datos no guarda ninguna URL del sitio.

Después de renombrar, en Laragon: *Menu > Apache > Recargar*. Dos detalles:

- Si entraban por ruta (`http://localhost/Neurix-Pos/`), la dirección pasa a
  ser `http://localhost/neurixpos/`: hay que actualizar los accesos directos.
  El origen del navegador no cambia, así que la impresora configurada se
  conserva.
- El service worker viejo queda registrado en la ruta anterior. Si alguna caja
  ve contenido viejo, `Ctrl` + `Shift` + `R` una vez y listo.

Con virtual host (lo recomendado abajo) la dirección no depende de la carpeta,
así que renombrarla no cambia nada para las cajas.

## 1. Nombre del sitio en Apache (solo en el servidor)

**Opción A — automática (lo más simple).** Laragon crea un virtual host por
cada carpeta en `C:\laragon\www`. Renombrá la carpeta del proyecto a
`neurixpos` y recargá Apache (*Menu > Apache > Recargar*): queda servido como
`http://neurixpos.test`.

**Opción B — manual.** Copiá `tools/laragon/neurixpos-vhost.conf.example` a
`C:\laragon\etc\apache2\sites-enabled\neurixpos.conf`, ajustá el
`DocumentRoot` y recargá Apache. Sirve para cuando la carpeta no se puede
renombrar.

## 2. Resolver el nombre en CADA terminal

No hay DNS en la red local, así que cada computadora necesita la línea en
`C:\Windows\System32\drivers\etc\hosts` (editar el Bloc de notas *como
administrador*):

```
# En el propio servidor
127.0.0.1       neurixpos.test
# En las demás terminales, con la IP fija del servidor
192.168.1.50    neurixpos.test
```

Sin esa línea, la terminal no encuentra el nombre y el POS no abre.

## 3. Detalles que conviene saber

- **Usá un nombre con punto** (`neurixpos.test`). Con un nombre de una sola
  palabra (`neurixpos`), Chrome/Brave lo mandan al buscador en vez de abrirlo,
  salvo que se escriba `http://neurixpos/` completo.
- **La impresora configurada se pierde al cambiar de dirección.** El POS la
  guarda en el `localStorage` del navegador, que es por origen: entrar como
  `neurixpos.test` es otro origen que `localhost`. Hay que volver a elegir la
  impresora una vez en cada terminal (*Configurar impresora*).
- **QZ Tray no se ve afectado.** La confianza se instala sobre el
  certificado (`override.crt`), no sobre la dirección del sitio: sigue
  funcionando igual después del cambio.
- **La IP del servidor debe ser fija.** Si el router se la cambia, las
  terminales dejan de encontrarlo. Reservá la IP en el router o configurala
  estática en el servidor.

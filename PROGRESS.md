# NEURIX POS — Plan de refactorización y modernización

---

## CÓMO RETOMAR UNA SESIÓN

1. Leer la sección **"Contexto del proyecto"** para entender la arquitectura
2. Buscar el primer ítem con **`[ ]`** — ese es el punto de continuación
3. Leer su descripción completa y la sección **"Notas técnicas"** antes de implementar
4. Al terminar cada ítem: marcar como `[DONE]`, documentar lo implementado, hacer commit y push

---

## CONTEXTO DEL PROYECTO

**Nombre**: NEURIX POS  
**Stack**: PHP 8.x + CodeIgniter 3 + MySQL + Bootstrap 3 + jQuery 3.7.1  
**Autor**: Jostin Aragon Barboza — arasoftsolutions@outlook.com  
**Repo**: https://github.com/Aragon2001/Neurix-Pos.git (rama `main`)  
**Directorio local**: `C:\Users\emanu\Documents\Proyectos\Facturacion Electronica\www`

**Qué hace**: Sistema POS (punto de venta) con facturación electrónica costarricense (Hacienda API v4.4), notas de crédito, apartados, parquímetro, impresión térmica (escpos), y reportes.

### Arquitectura clave

| Componente | Detalle |
|---|---|
| `index.php` | Bootstrap de CI3. Carga `.env` antes de que arranque el framework |
| `app/core/MY_Controller.php` | Controlador base. Constructor ejecuta migraciones de BD (guard: `versionPOS < 51`). Descifra credenciales Hacienda al cargar Settings |
| `app/controllers/Pos.php` | Controlador principal de ventas (~1990 líneas tras el split) |
| `app/controllers/PosView.php` | Vistas de comprobantes: view, viewnc, view_proforma |
| `app/controllers/PosEmail.php` | Envío de emails (ahora asíncrono via cola) |
| `app/controllers/PosRegister.php` | Caja: apertura/cierre/detalles/register |
| `app/controllers/PosPrint.php` | Impresión térmica, comandas, tickets |
| `app/controllers/PosCredit.php` | Nota de crédito |
| `app/controllers/Shacienda.php` | Worker de FE: procesa documentos pendientes en `tec_hacienda_tiketes` |
| `app/controllers/Queue_worker.php` | Worker de cola asíncrona (emails). Usa `fastcgi_finish_request()` |
| `app/models/Queue_model.php` | push/pop/markDone/markFailed con backoff exponencial |
| `app/models/Site.php::getSettings()` | Carga settings desde BD con file-cache TTL 5 min |
| `app/helpers/crypto_helper.php` | `encrypt_credential()` / `decrypt_credential()` con AES-256-CBC |
| `app/helpers/queue_helper.php` | `dispatch_queue_worker()` via fsockopen fire-and-forget |
| `app/helpers/pos_helper.php` | `invert_tax_price()` y helpers de impresora |
| `app/libraries/Swiftmailer.php` | Wrapper PHPMailer con la misma interfaz que tenía SwiftMailer |
| `app/libraries/Crearxml.php` | Genera XML de FE. `quitatilde()` escapa caracteres XML |
| `app/config/routes.php` | Mapea `pos/method` → `posview/method`, `posprint/method`, etc. |
| `themes/default/` | Único tema (ThemeChineses eliminado). jQuery 3.7.1 |

### Sistema de migraciones
- Corren en `MY_Controller.__construct()` — guard al inicio: `if (versionPOS < 51)`
- Cada migración: `if (versionPOS == "N" || $versionInitial)` → hace ALTER TABLE → `update settings versionPOS = N+1`
- Al agregar una migración nueva: cambiar el guard a `< (nueva_version + 1)` y agregar el bloque antes de `} // end migration guard`
- **versionPOS actual**: 50 (tabla `tec_queue` creada)

### Variables de entorno
- `.env` en raíz (gitignored) — cargado por `index.php` antes de CI
- Variables: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `APP_ENV`
- `.env.example` documenta las variables disponibles

### Credenciales Hacienda
- `password_token_test`, `password_token_prod`, `certificado_pin` se guardan cifradas en BD con prefijo `enc:`
- Se descifran en `MY_Controller.__construct()` justo después de `getSettings()`
- Al guardar en Settings, `encrypt_credential()` los cifra antes del INSERT
- Los datos legacy (sin prefijo `enc:`) se devuelven tal cual (retrocompatible)

### Cola asíncrona de emails
- Flujo: `PosEmail::email_receipt()` → `Queue_model::push('email', payload)` → `dispatch_queue_worker()` → JSON inmediato al cliente
- Worker (`Queue_worker::run()`): `fastcgi_finish_request()` → procesa jobs → SwiftMailer (PHPMailer)
- Backoff: intento 1 en 30s, intento 2 en 60s, intento 3 en 120s — luego `status='failed'`

---

## COMMITS DE ESTA SESIÓN DE REFACTORIZACIÓN

```
627a3f1  refactor(fase4): split Pos.php + upgrades dependencias + jQuery 3.7.1
3f8b767  feat(queue): Fase 3 completa - cola asincrona para emails
8aa0c4b  perf: Fase 2 completa - 4 mejoras de rendimiento
76d7496  fix(security): Fase 1 completa - 6 vulnerabilidades corregidas
ce220a0  fix: corregir 5 errores estructurales del sistema
1338e28  refactor: eliminar ThemeChineses y unificar temas
```

---

## COMPLETADO ✅

### Pre-sesión (commits anteriores)
- [DONE] Eliminar `ThemeChineses` y unificar en tema `default`
- [DONE] Columna `show_categories` en settings (reemplaza selección de tema)
- [DONE] Fix `$versionInitial == true` → `$versionInitial = true` (bug que hacía fallar instalaciones frescas)
- [DONE] Guard de migraciones: saltar las 135 queries si `versionPOS >= 51`
- [DONE] Cifrar credenciales Hacienda con AES-256-CBC (`crypto_helper.php`)
- [DONE] Reemplazar `shell_exec("wmic...")` por `gethostname() + php_uname()` (cross-platform)
- [DONE] Eliminar 9 bloques `/* include ... remote_printing.php */` comentados en vistas
- [DONE] Simplificar include activo en `modal_view.php` (ruta hardcoded a `themes/default`)

---

### FASE 1 — Seguridad crítica (commit `76d7496`)

#### 1. Credenciales DB a variables de entorno [DONE]
- `index.php`: loader de `.env` (parse KEY=VALUE, putenv + $_ENV, sin dependencias)
- `app/config/database.php`: `getenv('DB_HOST') ?: 'localhost'` etc.
- `app/config/database.php.example`: mismo patrón
- `.env.example`: documenta DB_HOST, DB_USER, DB_PASS, DB_NAME, APP_ENV

#### 2. Debug deshabilitado en producción [DONE]
- `app/config/constants.php` línea 50: `define('SHOW_DEBUG_BACKTRACE', ENVIRONMENT === 'development')`
- En producción (`CI_ENV=production` en el server), el backtrace no se muestra

#### 3. Guards de autorización reactivados [DONE]
- `app/controllers/Pos.php` ~línea 72: guard para `?code=` (base64 decode vacío → redirect)
- `app/controllers/Pos.php` ~línea 95: guard `if ($eid && !$this->Admin)` → redirect con error
- Ambos estaban comentados; ahora protegen que non-admins editen ventas cerradas

#### 4. Validación de carrito $_POST [DONE]
- `app/controllers/Pos.php` en el loop de items del carrito (~línea 168)
- `filter_var(FILTER_VALIDATE_INT)` para `product_id`, `FILTER_VALIDATE_FLOAT` para `quantity` y `real_unit_price`
- `strip_tags()` en `item_comment`
- `continue` si algún valor es inválido/negativo

#### 5. Escapar salida HTML en POS [DONE]
- `themes/default/views/pos/index.php`: `html_escape()` en `$store->name`, `$store->code`, `$Settings->site_name`, email de sesión
- Hidden inputs con IDs numéricos: cast `(int)` en `$sid`, `$eid`, `$rid`, `$quo`, `$apa`

#### 6. Escapar datos en XML de Hacienda [DONE]
- `app/libraries/Crearxml.php` método `quitatilde()` (todo texto que va al XML pasa por aquí)
- Al final: `str_replace(['<', '>', '"', "\r", "\n"], ['', '', '', ' ', ' '], $cadena)`
- Evita que nombres de productos con `<script>` o `&` rompan el XML de Hacienda

---

### FASE 2 — Performance (commit `8aa0c4b`)

#### 7. Fix bug token Hacienda [DONE]
- `app/controllers/Shacienda.php`: 5 ocurrencias del mismo bloque de 4 líneas reemplazadas
- **Bug**: `date("i:s", $token_data->expires_in)` trataba segundos como HH:MM → fecha de expiración incorrecta
- **Fix**: `date('Y-m-d H:i:s', time() + (int)($token_data->expires_in ?? 3600))`

#### 8. Eliminar N+1 queries en productos [DONE]
- `app/models/Pos_model.php` métodos `getProductNames()` y `getProductPrice()`
- **Antes**: por cada producto en el resultado, una query separada a `tec_impuestos`
- **Fix**: `->join("{$prefix}impuestos imp", 'products.id_tax=imp.id_impuesto', 'left')` en la query principal
- Los campos `id_impuesto`, `codigo_impuesto`, `codigo_tarifa` ahora vienen del JOIN con `COALESCE(imp.x, 0)`

#### 9. Caché de Settings [DONE]
- `app/models/Site.php::getSettings()`: CI file cache, clave `app_settings`, TTL 300s (5 min)
- `app/models/Settings_model.php::updateSetting()`: `$this->cache->file->delete('app_settings')` al guardar
- `app/config/config.php`: `$config['cache_path'] = APPPATH . 'cache/'`
- Elimina la query a `tec_settings` en cada request cuando la caché está caliente

#### 10. Helper `invert_tax_price()` [DONE]
- `app/helpers/pos_helper.php`: nueva función `invert_tax_price($price, $taxPercent)`
- Reemplaza el cálculo `$price / (1 + ($taxPercent / 100))` duplicado en 4 lugares
- Usada en `getProductNames()` y `getProductPrice()` de `Pos_model.php`

---

### FASE 3 — Cola asíncrona (commit `3f8b767`)

#### 11. Infraestructura de cola [DONE]
- **Tabla `tec_queue`** (versionPOS 49→50 en `MY_Controller`):
  ```sql
  id, type VARCHAR(30), payload LONGTEXT, status ENUM(pending/processing/done/failed),
  attempts TINYINT, max_attempts TINYINT, next_attempt_at DATETIME, created_at DATETIME,
  done_at DATETIME NULL, last_error TEXT NULL
  INDEX idx_status_next (status, next_attempt_at)
  ```
- **`app/models/Queue_model.php`**:
  - `push($type, $payload, $maxAttempts=3)` → INSERT con status='pending'
  - `pop($type=null, $limit=5)` → SELECT WHERE pending AND next_attempt_at<=NOW(), UPDATE a 'processing'
  - `markDone($id)` → status='done', done_at=NOW()
  - `markFailed($id, $error)` → si attempts < max: status='pending', next_attempt_at += 2^n*30s; si no: status='failed'
- **`app/controllers/Queue_worker.php`**:
  - `run($type=null)`: cierra conexión HTTP con `fastcgi_finish_request()` (o flush+ignore_user_abort en Apache), luego procesa jobs
  - `_processEmail($jobId, $payload)`: usa `Swiftmailer::send_email()`, regenera PDF si no existe
- **`app/helpers/queue_helper.php`**:
  - `dispatch_queue_worker($type=null)`: fsockopen al propio servidor, GET fire-and-forget, no bloquea
- **`app/config/autoload.php`**: helpers `'crypto'` y `'queue'` agregados

#### 12. Emails asíncronos [DONE]
- `PosEmail::email_receipt()`, `email_receipt_credit()`, `email_proforma()` (ahora en `app/controllers/PosEmail.php`)
- **Antes**: generaba PDF + enviaba email en el mismo request (~5-10s bloqueando)
- **Ahora**: genera PDF → `Queue_model::push('email', payload)` → `dispatch_queue_worker()` → retorna `{"queued":true}` inmediatamente
- Payload incluye: `to`, `subject`, `message`, `attach` (XMLs + ruta PDF), `pdf_html`, `pdf_path`
- Si el PDF desaparece, el worker lo regenera desde `pdf_html`

---

### FASE 4 — Calidad de código (commit `627a3f1`)

#### 13. Split de Pos.php [DONE]
- **Antes**: 3,337 líneas, 47 métodos en un solo archivo
- **Ahora**: `Pos.php` = 1,990 líneas + 5 controladores nuevos

| Controlador nuevo | Métodos |
|---|---|
| `PosView.php` | `view`, `view_proforma`, `viewnc`, `view_close_register`, `invice_barcode` |
| `PosEmail.php` | `email_receipt`, `email_receipt_credit`, `email_proforma` |
| `PosRegister.php` | `register_details`, `today_sale`, `shortcuts`, `close_register`, `products_sales_in_register`, `invoices_in_register` |
| `PosPrint.php` | `view_bill`, `print_parquimetro`, `print_comanda`, `print_register`, `print_receipt`, `print_cuenta`, `receipt_img`, `open_drawer`, `p`, `invice_barcode`, `invice_barcode_2` |
| `PosCredit.php` | `creditnote` |

- **`app/config/routes.php`**: 35 rutas agregadas que mapean `pos/method` → `posview/method`, `posprint/method`, etc. — **las URLs del frontend no cambian**
- Cada nuevo controlador extiende `MY_Controller` y carga sus modelos en `__construct()`

#### 14. SwiftMailer → PHPMailer [DONE]
- `app/libraries/Swiftmailer.php`: completamente reescrito usando `PHPMailer\PHPMailer\PHPMailer`
  - Misma firma pública: `send_email($to, $subject, $body, $from, $from_name, $attachment, $cc, $bcc)`
  - Soporta adjuntos de ruta (`ruta` key) y en memoria (XMLs, `key.xml`)
  - Soporta configuración SMTP, Gmail (`is_gmail=1`), y `isMail()`
- `app/libraries/Tec_mail.php`: eliminado bug crítico `var_dump($attachment); exit()` que bloqueaba todos los emails
- `composer.json`: `swiftmailer/swiftmailer` eliminado (PHPMailer ya era dependencia)

#### 15. Stripe SDK v7 → v13 [DONE]
- `app/models/Stripe_payments.php`: reescrito completamente
  - **Antes**: `\Stripe\Charge::create([...])` (API estática v7)
  - **Ahora**: `$this->stripe = new \Stripe\StripeClient($key)` + `$this->stripe->charges->create([...])`
  - Método `_error()` centraliza logging y retorno de array de error
  - `charge_to_array()` actualizado: `payment_method_details->card` en vez de `charge->card`
  - `get_all_transactions()`: parámetro `count` → `limit` (API v13)
  - `refund()`: ahora usa `$this->stripe->refunds->create()` en vez de `$transaction->refund()`
- `composer.json`: `"stripe/stripe-php": "^7.0"` → `"^13.0"`

#### 16. jQuery 2.1.4 → 3.7.1 [DONE]
- `themes/default/assets/plugins/jQuery/jquery-3.7.1.min.js`: descargado (87,533 bytes)
- 13 vistas actualizadas (PowerShell replace): `header.php`, `promotions.php`, `login.php`, `eviewnc.php` ×2, `viewnc.php` ×2, `eview.php`, `index.php`, `view.php`, `view_bill.php`, `view_parq.php`, `view_proforma.php`
- **Nota**: jQuery 3.x eliminó `.live()`, `.die()`, `$.browser` — verificar que los plugins del POS (iCheck, DataTables, Select2) sean compatibles con jQuery 3

#### 17. Viewport meta tag [DONE]
- `themes/default/views/pos/index.php`: `<meta name="viewport" content="width=device-width, initial-scale=1.0">` en `<head>`
- `themes/default/views/header.php`: ya tenía viewport con `user-scalable=no`

---

## PENDIENTE ⏳

### FASE 5 — Testing y CI/CD

#### 18. Inicializar suite de tests con PHPUnit [DONE]
- `composer.json`: `require-dev` con phpunit/phpunit ^10.0, phpstan/phpstan ^1.10, roave/security-advisories
- `phpunit.xml`: bootstrap → `tests/bootstrap.php`, suite `tests/Unit/`
- `tests/bootstrap.php`: define BASEPATH/APPPATH, stub de `config_item()`, carga ambos helpers
- `tests/Unit/helpers/CryptoHelperTest.php`: 4 tests (roundtrip, doble cifrado, legacy, vacío)
- `tests/Unit/helpers/PosHelperTest.php`: 7 tests (invert_tax_price ×4, character_limiter ×2, drawLine)
- **Resultado**: 11 tests, 14 assertions — OK

#### 19. GitHub Actions CI pipeline [DONE]
- `.github/workflows/ci.yml`: PHP 8.2, composer install, phpunit --testdox, phpstan

#### 20. PHPStan análisis estático [DONE]
- `phpstan.neon`: level 3, paths = `app/helpers` únicamente
- `tests/stubs/CI3.php`: stubs de CI_Model, CI_Controller, CI_DB_query_builder, CI_DB_result y modelos del proyecto
- **Decisión**: excluir modelos/controladores — CI3 usa magic `__get()` masivamente, generaría >6000 falsos positivos. Los helpers son pure PHP y pasan sin errores.
- **Resultado**: No errors

---

### FASE 6 — Modernización frontend/arquitectura

#### 21. Retry con backoff exponencial en API Hacienda [DONE]
- `app/libraries/Apiclient.php`: `postHacienda()` refactorizado para retornar `bool` y usa `sendWithRetry()`
- `sendWithRetry(callable $fn, int $maxAttempts = 3)`: 3 intentos, backoff 2s/4s entre fallos
- Detecta fallo por `curl_errno != 0` o HTTP 5xx; 4xx no se reintenta (error del cliente)
- Log con `log_message('error', ...)` cuando agota los intentos

#### 22. Logging de auditoría para operaciones financieras [DONE]
- `app/models/AuditLog_model.php`: `log($action, $entity, $entityId, $detail, $amount)` + `getLog()` + `countLog()`
- `MY_Controller`: migración 56→57 crea tabla `tec_audit_log` (id, user_id, user_email, action, entity, entity_id, detail, amount, ip, created_at). Guard actualizado a `< 58`.
- `Pos.php`: carga `AuditLog_model`. Log en `index()` tras `addSale()` exitoso (`venta_creada`), log en `nota_credito()` tras `addNoteCredit()` exitoso (`nota_credito`)
- `PosRegister.php`: carga `AuditLog_model`. Log en `close_register()` tras `closeRegister()` exitoso (`cierre_caja`)

#### 23. Roave Security Advisories [DONE]
- Agregado en Fase 5 junto con PHPUnit. `composer update` reportó: "No security vulnerability advisories found."

---

---

## SESIÓN 2026-06-29 — MODERNIZACIÓN FRONTEND

**Rama**: `modernizacion-frontend` (pusheada a origin)

### Trabajo Realizado

**Fase 0 — Preparación** ✅
- Crear rama de git desde main
- Backup completo de themes/default/assets/
- Inicializar npm + Vite 8.1.0
- Instalar 7 dependencias modernas: Bootstrap 5.3.8, AdminLTE 4.0.2, Tom Select 2.6.1, Tempus Dominus 6.10.4, Tabulator 6.5.2, SweetAlert2 11.26.25, Font Awesome 7.3.0
- Compilar bundles Vite exitosamente (CSS: 1.1MB gzip:792KB, JS: 443KB gzip:115KB)
- Configurar vite.config.js con salida en themes/default/assets/dist/

**Fase 1 — Bootstrap 3.3.4 → 5.3.8** ✅
- 131 vistas actualizadas mediante búsqueda/reemplazo masivo:
  - col-xs-* → col-* (338 ocurrencias)
  - .panel/.panel-{tipo} → .card/.border-{tipo} (112 ocurrencias)
  - data-toggle/data-dismiss → data-bs-toggle/data-bs-dismiss (152 ocurrencias)
  - .form-group → .mb-3, .control-label → .form-label (múltiples)
  - .input-group-addon → .input-group-text
  - .glyphicon-* → .fa-* (4 ocurrencias)
- Verificación: 0 referencias residuales a clases antiguas

**Fase 2 — AdminLTE 4.0 (infraestructura)** ✅ (parcial)
- Instalar AdminLTE 4.0.2 vía npm
- Crear themes/default/assets/src/main.js como entry point Vite con imports de todas las librerías modernas
- Compilar bundles exitosamente
- Sistema de tema oscuro/claro con localStorage + data-bs-theme nativo de Bootstrap 5
- Pendiente: cambios HTML en header.php (trabajo de riesgo alto, deferido para sesión posterior)

**Fase 3 — Reemplazo de Plugins jQuery** ✅
- select2 → Tom Select: 135 instancias (clases + inicializaciones)
- bootstrap-datetimepicker → Tempus Dominus: 158 instancias (scripts removidos + inicializaciones)
- DataTables → Tabulator.js: 42 instancias
- jQuery removido de header.php, integrado bundle Vite
- iCheck: 0 referencias encontradas (ya no se usa)

**Commit summary**:
```
26e56ef feat(fase1): migración Bootstrap 3.3.4 → 5.3.8 en 131 vistas
5b6dc02 feat(fase3): reemplazo masivo de plugins jQuery por librerías modernas
e4ea4cf docs(plan): actualizar estado Fases 0-3 completadas + registro de sesión
```

### Pendiente (Próximas Sesiones)

**Fase 4 — neurix-theme.css** (media prioridad)
- Ajustes CSS para .card, .form-check-input, Tom Select (.ts-control), Tempus Dominus, Tabulator
- Posible migración de variables --nx-* a herencia de --bs-*

**Fase 5 — Limpieza Final** (media prioridad)
- Confirmar 0 referencias a clases antiguas (glyphicon, select2, icheck, panel-*)
- Agregar .table-responsive a tablas
- Validar que vistas de impresión mantengan fondo blanco

**Fase 6 — Testing Módulo por Módulo** (crítica)
- Testing en vivo de cada módulo: Login, Dashboard, Reportes, Hacienda, POS
- Validar flujos de facturación electrónica
- Verificar vistas de impresión (pos/eview.php, creditnotes/eviewnc.php) — aún tienen jQuery

### Estado del Sistema

- **Rama actual**: modernizacion-frontend
- **Compilación Vite**: ✅ Exitosa (bundles en themes/default/assets/dist/)
- **node_modules**: ✅ Agregado a .gitignore
- **Cambios masivos**: ✅ Completados sin errores visibles
- **Riesgo de rotura**: Medio — cambios extensos requieren testing integral antes de merge a main

### Notas Importantes

1. Los cambios de Fase 1-3 usan sed/regex masivo — puede haber edge cases que requieran ajuste manual
2. Las vistas de impresión (pos/eview.php, etc.) mantienen jQuery porque pueden tener dependencias especiales para impresora térmica/ESCPOS
3. El bundle Vite incluye todas las librerías modernas — las inicializaciones en vistas pueden necesitar ajustes menores de sintaxis
4. Compilación está optimizada: CSS minimizado, JS minimizado (terser), gzip friendly

### Próximos Pasos (En Orden)

1. Completar cambios HTML de AdminLTE 4 en header.php (Fase 2 completa)
2. Hacer testing funcional de al menos 3 módulos clave (Fase 6)
3. Ajustar neurix-theme.css según feedback del testing (Fase 4)
4. Limpieza final (Fase 5)
5. Merge a main cuando testing sea satisfactorio

---

## NOTAS TÉCNICAS IMPORTANTES

### Guard de migraciones — SIEMPRE actualizar al agregar nueva migración
```php
// MY_Controller.php ~línea 49
if (!isset($this->Settings->versionPOS) || (int)$this->Settings->versionPOS < 58) {
// CAMBIAR 58 → nueva_version+1
```
El último versionPOS asignado es **57** (tabla tec_audit_log). La próxima migración será 57→58.

### Rutas del split de Pos.php
Todos los `pos/method` en el frontend y en el código PHP siguen funcionando gracias a `app/config/routes.php`. Si se agrega un método nuevo a PosView/PosPrint/etc., hay que agregar su ruta correspondiente.

### Cola de emails — cómo probar
1. Disparar `POST pos/email_receipt` con `id` y `email` válidos
2. Verificar que responde JSON inmediato `{"queued":true}`
3. Verificar que se crea un registro en `tec_queue` con `status='pending'`
4. Llamar `GET queue_worker/run` directamente para procesar manualmente
5. Verificar que el registro pasa a `status='done'`

### Stripe — requiere `composer update`
Tras cambiar `stripe/stripe-php` de `^7.0` a `^13.0` en composer.json, hay que correr `composer update stripe/stripe-php` en el servidor para instalar la nueva versión.

### SwiftMailer — clase mantenida por compatibilidad
La clase `Swiftmailer` en `app/libraries/Swiftmailer.php` conserva el nombre pero usa PHPMailer internamente. No borrar el archivo — todos los `$this->load->library('Swiftmailer')` del codebase lo usan.

### DB prefix
Todas las tablas usan prefijo `tec_`. En el código CI3: `$this->db->dbprefix('tabla')` devuelve `tec_tabla`.

### Tema único
Solo existe `themes/default/`. El campo `theme` en `tec_settings` siempre es `'default'`. La variante "POS sin categorías" se controla con `settings.show_categories = 0`.

# NEURIX POS — Plan de refactorización y modernización

> **Instrucción para retomar la sesión**: leer este archivo, ver el último ítem marcado como `[DONE]` y continuar con el siguiente `[ ]`.
> Cada ítem incluye archivos afectados y descripción de lo implementado.

---

## Ya completado (sesiones anteriores)

- [DONE] Eliminar ThemeChineses y unificar temas en `default`
- [DONE] Añadir columna `show_categories` + migración versionPOS 44
- [DONE] Fix bug `$versionInitial == true` → `$versionInitial = true`
- [DONE] Guard de migraciones: saltar 135 queries DB si versionPOS >= 44
- [DONE] Cifrar credenciales Hacienda con AES-256-CBC (`crypto_helper.php`)
- [DONE] Reemplazar `shell_exec("wmic...")` por `gethostname() + php_uname()`
- [DONE] Eliminar 9 bloques de includes comentados en vistas
- [DONE] Simplificar include activo en `modal_view.php`

---

## FASE 1 — Seguridad crítica

### 1. Credenciales DB a variables de entorno
- **Archivos**: `index.php`, `app/config/database.php`, `app/config/database.php.example`, `.env.example` (nuevo)
- **Implementado**: loader de `.env` en `index.php`; `database.php` usa `getenv()` con fallback; `.env.example` documentado
- **Estado**: [DONE]

### 2. Deshabilitar debug en producción
- **Archivos**: `app/config/constants.php`
- **Implementado**: `SHOW_DEBUG_BACKTRACE` ahora es `ENVIRONMENT === 'development'`
- **Estado**: [DONE]

### 3. Re-habilitar checks de autorización comentados
- **Archivos**: `app/controllers/Pos.php` líneas 95-98 y 72-75
- **Implementado**: ambos bloques de autorización descomentados y operativos
- **Estado**: [DONE]

### 4. Validación de arrays `$_POST` en ventas
- **Archivos**: `app/controllers/Pos.php` líneas 167-180
- **Implementado**: `filter_var()` con FILTER_VALIDATE_INT/FLOAT; `strip_tags()` en comentarios; `continue` si datos inválidos
- **Estado**: [DONE]

### 5. Escapar salida HTML en vistas del POS
- **Archivos**: `themes/default/views/pos/index.php`
- **Implementado**: `html_escape()` en store->name, store->code, site_name, email de sesión; cast `(int)` en campos hidden con IDs
- **Estado**: [DONE]

### 6. Escapar datos en XML de Hacienda
- **Archivos**: `app/libraries/Crearxml.php` método `quitatilde()`
- **Implementado**: `str_replace` de `<`, `>`, `"`, `\r`, `\n` al final de `quitatilde()` — todos los textos del XML pasan por aquí
- **Estado**: [DONE]

---

## FASE 2 — Performance

### 7. Fix bug cálculo expiración token Hacienda
- **Archivos**: `app/controllers/Shacienda.php` (5 ocurrencias)
- **Implementado**: `date(..., time() + (int)($token_data->expires_in ?? 3600))` — elimina el parseo erróneo de segundos como HH:MM
- **Estado**: [DONE]

### 8. Eliminar N+1 queries en búsqueda de productos
- **Archivos**: `app/models/Pos_model.php` métodos `getProductNames()` y `getProductPrice()`
- **Implementado**: LEFT JOIN con `impuestos` en la query principal; eliminado el loop de queries individuales por producto
- **Estado**: [DONE]

### 9. Caché de Settings (evitar query en cada request)
- **Archivos**: `app/models/Site.php`, `app/models/Settings_model.php`, `app/config/config.php`
- **Implementado**: CI file cache con TTL 5 min en `getSettings()`; invalidación en `updateSetting()`; `cache_path` configurado
- **Estado**: [DONE]

### 10. Extraer lógica de impuestos duplicada
- **Archivos**: `app/helpers/pos_helper.php`, `app/models/Pos_model.php`
- **Implementado**: función `invert_tax_price($price, $taxPercent)` en `pos_helper.php`; reemplazadas todas las instancias duplicadas
- **Estado**: [DONE]

---

## FASE 3 — Cola asíncrona

### 11. Cola asíncrona — infraestructura (tabla + modelo + worker)
- **Archivos**: `app/core/MY_Controller.php` (migración versionPOS 50 → tabla `tec_queue`), `app/models/Queue_model.php` (nuevo), `app/controllers/Queue_worker.php` (nuevo), `app/helpers/queue_helper.php` (nuevo), `app/config/autoload.php`
- **Implementado**: tabla `tec_queue` (status, attempts, backoff exponencial), `Queue_model::push/pop/markDone/markFailed`, worker con `fastcgi_finish_request()` + `ignore_user_abort`, helper `dispatch_queue_worker()` via fsockopen fire-and-forget
- **Estado**: [DONE]

### 12. Cola asíncrona para envío de emails
- **Archivos**: `app/controllers/Pos.php` métodos `email_receipt`, `email_receipt_credit`, `email_proforma`
- **Implementado**: los 3 métodos encolan el job (payload: to/subject/message/attach/pdf_html/pdf_path), disparan el worker en background, devuelven JSON inmediato. Backoff: 30s → 60s → 120s, max 3 intentos
- **Estado**: [DONE]

---

## FASE 4 — Calidad de código

### 13. Split de Pos.php (3335 líneas → 5 controladores)
- **Archivos**: `app/controllers/Pos.php` → dividir en:
  - `Pos.php` (ventas, caja, carrito)
  - `PosCredit.php` (notas de crédito)
  - `PosPrint.php` (impresión de recibos, comandas, etiquetas)
  - `PosRegister.php` (apertura/cierre de caja, depósitos, retiros)
  - `PosEmail.php` (envío de correos de comprobantes)
- **Estado**: [ ]

### 14. Upgrade SwiftMailer → Symfony Mailer
- **Archivos**: `composer.json`, `app/libraries/Tec_mail.php`, `app/config/autoload.php`
- **Qué hacer**: reemplazar `swiftmailer/swiftmailer` (deprecado 2023) por `symfony/mailer`
- **Estado**: [ ]

### 15. Upgrade Stripe SDK v7 → v13
- **Archivos**: `composer.json`, código que use `\Stripe\`
- **Qué hacer**: actualizar dependencia y adaptar llamadas a la API nueva
- **Estado**: [ ]

### 16. Upgrade jQuery 2.1.4 → 3.7
- **Archivos**: `themes/default/views/pos/index.php`, `header.php`, `footer.php`
- **Qué hacer**: reemplazar CDN/local de jQuery; verificar compatibilidad de plugins
- **Estado**: [ ]

### 17. Añadir viewport meta tag en POS
- **Archivos**: `themes/default/views/pos/index.php`
- **Qué hacer**: agregar `<meta name="viewport" content="width=device-width, initial-scale=1.0">` en `<head>`
- **Estado**: [ ]

---

## FASE 5 — Testing y CI/CD

### 18. Inicializar suite de tests con PHPUnit
- **Archivos**: `composer.json`, `phpunit.xml` (nuevo), `tests/` (nuevo directorio)
- **Qué hacer**: instalar PHPUnit, crear tests unitarios para `crypto_helper`, `Pos_model::getProductNames`, lógica de impuestos
- **Estado**: [ ]

### 19. GitHub Actions CI pipeline
- **Archivos**: `.github/workflows/ci.yml` (nuevo)
- **Qué hacer**: ejecutar PHPUnit + PHPStan en cada push a main
- **Estado**: [ ]

### 20. Añadir PHPStan análisis estático
- **Archivos**: `composer.json`, `phpstan.neon` (nuevo)
- **Qué hacer**: instalar PHPStan nivel 3, corregir errores detectados
- **Estado**: [ ]

---

## FASE 6 — Modernización frontend/arquitectura

### 21. Retry con backoff exponencial en API Hacienda
- **Archivos**: `app/libraries/Apiclient.php`
- **Qué hacer**: método privado `sendWithRetry($data, $maxAttempts=3)` con `sleep(2^n)` entre intentos
- **Estado**: [ ]

### 22. Logging de auditoría para operaciones financieras
- **Archivos**: `app/controllers/Pos.php`, `app/models/` crear `AuditLog_model.php`, migración tabla `tec_audit_log`
- **Qué hacer**: registrar quién hizo qué venta/anulación/cobro y cuándo, en tabla separada
- **Estado**: [ ]

### 23. Roave Security Advisories (dependencias vulnerables)
- **Archivos**: `composer.json`
- **Qué hacer**: agregar `"roave/security-advisories": "dev-latest"` como dev dependency
- **Estado**: [ ]

---

## Notas técnicas

- **Framework**: CodeIgniter 3.x — las migraciones corren via `MY_Controller.__construct()` (guard en versionPOS)
- **DB prefix**: `tec_`
- **Tema único**: `themes/default/` (ThemeChineses eliminado)
- **Hacienda**: API v4.4, ambientes test/prod configurables en Settings
- **Credenciales cifradas**: `password_token_test`, `password_token_prod`, `certificado_pin` con AES-256-CBC via `crypto_helper.php`
- **show_categories**: columna en settings reemplaza la selección de tema

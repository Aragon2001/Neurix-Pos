# 
Registro de cambios — Modernización 2026

Historial completo del trabajo de limpieza, cumplimiento Hacienda v4.4 y actualización de dependencias realizado en junio 2026.

---

## Fase 1 — Limpieza y seguridad

### Decisión de base de trabajo
Se confirmó que `app/` en la raíz es la copia más reciente y completa (30 controladores, 19 modelos, librerías de firma actualizadas). La carpeta `parqueo/` era una copia autónoma de un desarrollo anterior para un cliente de estacionamientos.

### Eliminados — raíz del proyecto

| Eliminado | Motivo |
|---|---|
| `parqueo/` (223 MB) | Copia vieja de cliente de parqueos, código desactualizado |
| `application/` | Carpeta CI vacía con `controllers.rar` sin descomprimir |
| `lib/` | Duplicado exacto de `system/` (núcleo CI) |
| `SPOS4/` | Directorio vacío, residuo de instalación |
| `50x.html` | Página de error genérica del servidor |
| `fdfd.exe`, `getPendientes.exe`, `sincPos.exe` | Utilidades .NET de escritorio |
| `licence.exe`, `package` | Binario licenciador duplicado |
| `web.config` | Configuración IIS, objetivo es Linux/Apache |
| `~$ditoria_y_Hoja_de_Ruta.docx` | Archivo temporal de Word |

### Eliminados — dentro de `app/`

| Eliminado | Motivo |
|---|---|
| `controllers/ApiclientControxzdfsfaller.php` | Copia con typo del controlador real |
| `controllers/Shacienda.php_` | Backup de 2002 líneas con extensión rota |
| `controllers/Crearxml.php` | Duplicado de librería, no registrado en rutas |
| `libraries/Apiclient.php-viejo` | Backup viejo explícitamente nombrado |
| `libraries/Crearxml.php-viejo` | Ídem |
| `libraries/Datatables.php_` | Extensión rota, código muerto |
| `libraries/Dockerfile` | Archivo Docker mal ubicado en carpeta de librerías PHP |

### Creados

**`index.php`** (raíz) — Punto de arranque de CodeIgniter. Apunta a `app/` y `system/`. Arranca en modo `production` por defecto; para desarrollo local definir `CI_ENV=development`.

**`.gitignore`** — Excluye: `conf.env`, `app/config/database.php`, logs, caché, `uploads/`, certificados `.p12`/`.pem`, `.idea/`, `.claude/`.

### Correcciones de seguridad

**SQL Injection — `app/models/Hacienda_model.php`**
- Funciones `ccsctv()` (línea 14) y `ccsctvfec()` (línea 29): variable `$terminal_pos` concatenada directo al string SQL.
- Solución: query binding con parámetros `?` — CodeIgniter escapa automáticamente.

**Contraseña hardcodeada — `app/controllers/Shacienda.php`**
- Eliminadas líneas 539–1078: función deshabilitada `cargamasivadexmldesdeemail__` con credenciales IMAP en texto plano (`factura@superviquezcr.com` / `8314579kh$`).
- La función operativa equivalente ya existía y lee credenciales desde `$this->Settings`.
- El archivo pasó de 2002 a 1462 líneas.

**IP de servidor anterior — `app/config/config.php`**
- Eliminada línea comentada `//http://201.207.85.142:85/` y la lógica `gethostbyname()` que podía fallar.
- Reemplazado por: `$config['base_url'] = 'http://'.$_SERVER['HTTP_HOST'].'/';`

---

## Fase 2 — Actualización a Hacienda v4.4

Obligatorio desde el 1 de septiembre de 2025. El sistema generaba XML con esquemas v4.2/v4.3; ningún comprobante era válido ante Hacienda.

### Esquemas XML actualizados — `app/libraries/Crearxml.php`

| Documento | Antes | Ahora |
|---|---|---|
| FacturaElectronica | v4.3 (cdn) | **v4.4** (cdn) |
| TiqueteElectronico | v4.3 (cdn) | **v4.4** (cdn) |
| FacturaElectronicaCompra | v4.3 (cdn) | **v4.4** (cdn) |
| NotaCreditoElectronica | v4.2 (tribunet.hacienda.go.cr) | **v4.4** (cdn) |
| NotaDebitoElectronica | v4.2 (tribunet.hacienda.go.cr) | **v4.4** (cdn) |
| MensajeReceptor | v4.3 (cdn) | **v4.4** (cdn) |

16 URLs de namespaces y schema locations actualizadas. 0 referencias a v4.2 o v4.3 restantes.

### Nuevos métodos de pago (`getInvoice` y `getFEC`)

- Código `08` — SINPE Móvil
- Código `09` — Plataformas digitales (PayPal, etc.)
- Bug corregido: `$payment == 'Cheque'` comparaba el array completo en vez de `$payment['paid_by']`

### NotaCreditoElectronica — refactorización estructural completa

La v4.2 de NotaCredito tenía formato XML diferente al resto de comprobantes. Cambios:

- `<CodigoActividad>` agregado después de `<Clave>` (obligatorio en v4.4)
- Añadida query a tabla `impuestos` por producto para obtener `codigo_tarifa`
- `<Codigo>` en LineaDetalle → `<CodigoComercial>` (renombrado en v4.3+)
- `<Impuesto>` ahora incluye `<CodigoTarifa>` e `<ImpuestoNeto>`
- `<MontoDescuento>` suelto → envuelto en `<Descuento><NaturalezaDescuento>`
- `<ResumenFactura>`: `<CodigoMoneda>/<TipoCambio>` ahora dentro de `<CodigoTipoMoneda>`
- Todos los subtotales siempre presentes (antes condicionales por monto > 0)
- Eliminado bloque `<Normativa>` (no existe en v4.3/v4.4)

### Endpoints API actualizados — `app/libraries/Apiclient.php`

| Ambiente | Antes | Ahora |
|---|---|---|
| Producción comprobantes | `api.comprobanteselectronicos.go.cr/recepcion/v1/comprobantes` | `api.hacienda.go.cr/fe/ae` |
| Producción recepción | `api.comprobanteselectronicos.go.cr/recepcion/v1/recepcion` | `api.hacienda.go.cr/fe/ae` |
| Sandbox comprobantes | `api.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/comprobantes` | `api-sandbox.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/comprobantes` |

---

## Fase 3 — Actualización de dependencias

### `composer.json`

| Paquete | Antes | Ahora | Motivo |
|---|---|---|---|
| `php` | `^7.1.3` | `>=7.4` | CI3 corre en 7.4+, permite PHP 8.x |
| `phpmailer/phpmailer` | `^6.0` | `^6.9` | CVE-2018-19296 y otros parches |
| `mpdf/mpdf` | `^7.1` | `^8.2` | API idéntica, múltiples CVEs en 7.x |
| `swiftmailer/swiftmailer` | `5.1.*@dev` | `^6.3` | 5.x tiene inyección de cabeceras; 6.3 = última versión del paquete |
| `stripe/stripe-php` | `^6.6` | `^7.0` | Actualización de seguridad; API Charge sigue compatible hasta v11 |
| `zendframework/zend-barcode` | `^2.7` | ~~eliminado~~ | Proyecto renombrado a Laminas |
| `laminas/laminas-barcode` | — | `^2.12` | Sucesor directo de zend-barcode |

### Código adaptado

**`app/libraries/Tec_barcode.php`**
- `use Zend\Barcode\Barcode` → `use Laminas\Barcode\Barcode`

**`app/libraries/Swiftmailer.php`** y **`app/controllers/Swiftmailer.php`**
- SwiftMailer 6.x eliminó los constructores estáticos `::newInstance()`:
  - `Swift_SmtpTransport::newInstance(...)` → `new Swift_SmtpTransport(...)`
  - `Swift_Mailer::newInstance(...)` → `new Swift_Mailer(...)`
  - `Swift_Message::newInstance(...)` → `new Swift_Message(...)`
- Eliminado `dd(sadasd)` del controlador (función de debug Laravel inexistente en CI3)
- Separador de rutas en adjuntos: `'\\'` → `DIRECTORY_SEPARATOR` (portabilidad Linux/Windows)

### Para activar los cambios

Ejecutar en el servidor:
```bash
composer update
```

---

## Fase 4 — Segunda auditoría post-limpieza (junio 2026)

### Firmador XML actualizado a v4.4 — `app/libraries/Firmadocr.php`

El generador (`Crearxml.php`) ya estaba en v4.4, pero el firmador XAdES seguía apuntando a namespaces v4.2 de `tribunet.hacienda.go.cr`. Corregido:

| Documento | Antes | Ahora |
|---|---|---|
| FacturaElectronica | `tribunet.hacienda.go.cr/…/v4.2/facturaElectronica` | `cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica` |
| NotaDebitoElectronica | `…/v4.2/notaDebitoElectronica` | `…/v4.4/notaDebitoElectronica` |
| NotaCreditoElectronica | `…/v4.2/notaCreditoElectronica` | `…/v4.4/notaCreditoElectronica` |
| TiqueteElectronico | `…/v4.2/tiqueteElectronico` | `…/v4.4/tiqueteElectronico` |
| MensajeReceptor | `…/v4.2/mensajeReceptor` | `…/v4.4/mensajeReceptor` |

15 referencias v4.2 corregidas. Adicionalmente se agregó soporte para tipo `08` (FacturaElectronicaCompra) que faltaba en ambos bloques del firmador.

### Dependencias instaladas — `composer update`

Ejecutado con Laragon PHP 8.3.30 + Composer 2.9.4. Versiones efectivamente instaladas en `vendor/`:

| Paquete | Antes (real en vendor/) | Ahora |
|---|---|---|
| `swiftmailer/swiftmailer` | 5.1.0 | **6.3.0** |
| `phpmailer/phpmailer` | 6.0.5 | **6.12.0** |
| `stripe/stripe-php` | 6.10.4 | **7.128.0** |
| `mpdf/mpdf` | 7.1.5 | **8.3.1** |
| `zend-barcode` | 2.7.0 | **eliminado → laminas-barcode 2.16.0** |
| `mike42/escpos-php` | 2.0.2 | **2.2** |
| `setasign/fpdi` | 1.6.2 | **2.6.8** (dependencia de mpdf 8.x) |

`composer.lock` regenerado. Sin vulnerabilidades de seguridad reportadas.

> Nota: `swiftmailer/swiftmailer` está marcado como abandonado por su autor. Migración a `symfony/mailer` queda para la fase de actualización de stack.

### Campo `codigo_actividad` del receptor — migración BD y XML

**Migración:** `files/updates/db_updates/4_0_20.sql`
```sql
ALTER TABLE `customers` ADD `codigo_actividad` VARCHAR(6) NULL DEFAULT NULL AFTER `business_name`;
```

**Código:** `app/libraries/Crearxml.php` — en los métodos `getInvoice()` y `getNotaCredito()`, el bloque `<Receptor>` ahora incluye `<CodigoActividad>` cuando el campo está presente en el cliente:
```xml
<CodigoActividad>620100</CodigoActividad>
```
Posición en el XML: después de `<NombreComercial>`, antes de `<Telefono>`, conforme al esquema v4.4. El campo es opcional: si `codigo_actividad` está vacío/nulo no se emite el elemento (compatibilidad retroactiva con clientes sin actividad registrada).

---

## Pendientes para fases futuras

### `getNotaDebito()` reescrita — `app/libraries/Crearxml.php`

La función era código muerto: usaba `\DB::table()` de Laravel y 11 constantes PHP inexistentes en CI3 (`COMPANYNAME`, `CASAMATRIZ`, `TERMINALPOS`, `TIPO_PERSONA`, `ID_COMPANY_CONF`, `PROVINCIA`, `CANTON`, `DISTRITO`, `BARRIO`, `CADDRESS`, `TELEFONOS_COMPANY_CONF`, `EMAIL_COMPANY_CONF`, `EMAIL_COMPANY_CONF`, `TAX_COMPANY_CONF`). Reescrita completamente:

| Aspecto | Antes | Ahora |
|---|---|---|
| Método | `static` | Instancia (`$this`) |
| Firma | `($id, $motivo)` — consultaba BD internamente | `($invoice, $itemsInvoices, $referencia, $otrostextos)` — mismo patrón que `getNotaCredito` |
| Queries | `\DB::table(...)` Laravel | `$this->db->...` CI3 |
| Datos empresa | Constantes inexistentes | `$this->Settings->...` |
| Receptor | Tabla `proveedores` (sistema externo) | `customers_model->getCustomerByID()` |
| Tipo ID receptor | Códigos texto ("C.I.", "C.J.") | Códigos numéricos ("01", "02") |
| Consecutivo | `CASAMATRIZ`/`TERMINALPOS` constantes | `hacienda_model->ccsctv('02')` |
| XML `<CodigoActividad>` | Ausente | Incluido desde `$invoice['id_actividad']` |
| XML `<LineaDetalle>` | v4.1 (`<Codigo>`, sin `CodigoTarifa`) | v4.4 (`<CodigoComercial>`, `<CodigoTarifa>`, `<ImpuestoNeto>`) |
| XML `<ResumenFactura>` | `<CodigoMoneda>/<TipoCambio>` sueltos | Envueltos en `<CodigoTipoMoneda>` |
| Totalizadores | 4 (solo Mercancías) | 8 (Serv+Mercancias, Grav+Exento) |
| Retorno | `array($invo, $key, $consecutive)` indexado | `['xml'=>, 'clave'=>, 'consecutivo'=>, 'fecha_emision'=>]` con claves |

> **Nota de integración:** La función ahora espera datos pre-consultados por el controlador (igual que `getNotaCredito`). El controlador de Notas de Débito y la tabla `hacienda_nd` en BD aún están pendientes de crear cuando se implemente el flujo completo.

### REP tipo 09 implementado

**Migración:** `files/updates/db_updates/4_0_21.sql` — nueva tabla `tec_hacienda_rep` para almacenar REPs firmados y sus respuestas de Hacienda.

**Archivos modificados/creados:**

| Archivo | Cambio |
|---|---|
| `app/libraries/Crearxml.php` | Nuevo método `getREP($payment, $sale, $referencia)` |
| `app/libraries/Firmadocr.php` | Soporte tipo `09` — namespace `reciboPago` v4.4 en firma y cierre `</ReciboPago>` |
| `app/models/Hacienda_model.php` | 6 métodos nuevos: `ccsctv_rep`, `getREP`, `insertxmlREP`, `insertHaciendaREP`, `getPendientesREP`, `xmlFirmadoREP`, `MarcaEnviadoREP` |
| `app/controllers/Shacienda.php` | Nuevo método `generarREP($payment_id)` |

**Estructura del XML generado:**
```
ReciboPago (v4.4) → Clave → CodigoActividad → NumeroConsecutivo →
FechaEmision → Emisor → [Receptor opcional] → CondicionVenta(02) →
MedioPago → InformacionReferencia → ResumenFactura(CodigoTipoMoneda + TotalComprobante)
```

**Flujo de uso:**
1. Se registra un pago (`payments` table) sobre una venta a crédito cuya factura esté `aceptado` en Hacienda.
2. Se llama a `Shacienda/generarREP?payment_id={id}` (o desde la vista de detalle del pago).
3. El controlador genera el XML, lo firma con el `.p12`, lo almacena en `tec_hacienda_rep`, y lo envía al endpoint TRIBU-CR.

**`MedioPago` mapping:**

| `payments.paid_by` | Código v4.4 |
|---|---|
| `cash` | `01` Efectivo |
| `CC` | `02` Tarjeta |
| `Cheque` | `03` Cheque |
| `TransDep` | `04` Transferencia |
| `SINPE` | `08` SINPE Móvil |
| `digital` | `09` Plataforma digital |
| otros | `99` Otros |

**UI:** botón "Generar REP (Hacienda)" agregado en `themes/*/views/sales/payment_note.php`. Visible solo cuando la factura está `aceptado` en Hacienda y aún no existe REP para ese pago. Una vez generado muestra badge verde "REP generado". El controlador `Sales::payment_note` fue actualizado para pasar `$hacienda` y `$rep_exists` a la vista. El botón tiene `class="no-print"` — no aparece al imprimir el recibo.

### Pendientes de implementación

- **CABYS 2025** — verificar que los códigos de producto en la BD usen el catálogo CABYS actualizado.

### Pendientes de verificación con Hacienda

- Confirmar flujo de autenticación del endpoint `api.hacienda.go.cr/fe/ae` con credenciales TRIBU-CR antes de ir a producción.
- Verificar que el nuevo endpoint sandbox siga activo.

### Migración mayor (largo plazo)

- PHP 8.3 + Laravel 11 o CodeIgniter 4
- CI3 no tiene soporte de seguridad activo
- PHP 7.4 lleva sin parches desde noviembre 2022

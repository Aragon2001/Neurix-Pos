# Bases de Datos — Neurix POS

## Descripción

Neurix POS incluye **dos archivos SQL**, ambos con el **mismo esquema completo**
(equivalente a `versionPOS=60`, es decir, con todas las columnas y tablas que el
sistema de auto-migración de `app/core/MY_Controller.php` agregaría en una
instalación vieja) y el **mismo catálogo geográfico de Costa Rica 100% completo**.
Solo se diferencian en los datos de negocio:

### 1. **NeurixBD-Inicial.sql** — Para Producción
**Uso:** Instalación en servidor de producción, arranque limpio

**Contenido:**
- ✅ Estructura completa de las 61 tablas del sistema
- ✅ Catálogo geográfico CR completo: 7 provincias, 82 cantones, 488 distritos, 497 barrios
- ✅ Catálogo de tarifas de impuesto Hacienda v4.4 (IVA 13/8/4/2/1/0%, exento, ISC)
- ✅ Datos **mínimos obligatorios**: grupo admin, usuario administrador, tienda principal
- ❌ **Sin datos de negocio**: 0 productos, 0 clientes, 0 ventas

```bash
mysql -u root -p < NeurixBD-Inicial.sql
```

**Usuario admin por defecto:**
- Email: `admin@neurix.local`
- Contraseña: `Neurix2026!` (cámbiala en producción)

---

### 2. **database_ejemplo.sql** — Para Desarrollo/Testing
**Uso:** Ambiente de desarrollo, testing, demostraciones, análisis de gráficas/reportes

**Contenido (además de la misma estructura y geografía completa):**
- ✅ 24 productos en 6 categorías — incluye **2 agotados** y **3 con stock bajo**
  (para probar alertas de inventario)
- ✅ 16 clientes (Cliente de Contado + 15 con cédula, mezcla de físicas y jurídicas)
- ✅ 3 proveedores
- ✅ **~131 ventas distribuidas en los últimos 12 meses** con estados variados
  (pagada/parcial/pendiente) y métodos de pago variados (efectivo/tarjeta/SINPE/cheque)
- ✅ ~130 documentos Hacienda (tiquetes/facturas) con estados variados
  (aceptado/procesando/rechazado/error) para poblar las estadísticas de FE
- ✅ ~18 compras y ~30 gastos mensuales — alimenta la gráfica financiera de 12 meses
- ✅ 12 cajas registradoras con historial (una por mes) + 1 caja abierta

```bash
mysql -u root -p < database_ejemplo.sql
```

**Usuario admin:** `admin@example.com` / `admin123`

---

## Comparación Rápida

| Aspecto | NeurixBD-Inicial.sql | database_ejemplo.sql |
|--------|:---:|:---:|
| Estructura (61 tablas) | ✅ Completa | ✅ Completa |
| Geografía CR completa | ✅ 7/82/488/497 | ✅ 7/82/488/497 |
| Catálogo de impuestos | ✅ 11 tarifas | ✅ 11 tarifas |
| Productos | ❌ 0 | ✅ 24 (2 agotados, 3 bajo stock) |
| Clientes | ❌ 0 | ✅ 16 |
| Ventas | ❌ 0 | ✅ ~131 (12 meses) |
| Compras / Gastos | ❌ 0 | ✅ ~18 / ~30 |
| Documentos Hacienda | ❌ 0 | ✅ ~130 (variados) |
| Ambiente recomendado | **PRODUCCIÓN** | Desarrollo/Testing/Demo |

---

## Procedimiento de Instalación

### En Producción

```bash
mysql -u root -p < NeurixBD-Inicial.sql
```

Luego en `app/config/database.php` apunta a la base `NeurixBD`, entra con
`admin@neurix.local` / `Neurix2026!`, y en **Configuración > Ajustes**:
- Cambia la contraseña del admin de inmediato
- Completa datos de emisor (cédula, nombre comercial, teléfono, provincia/cantón/distrito/barrio)
- Configura credenciales Hacienda (TRIBU-CR) y carga el certificado .p12
- Cambia `ambiente` de `test` a `prod` solo cuando confirmes las credenciales

### En Desarrollo

```bash
mysql -u root -p < database_ejemplo.sql
```

Entra con `admin@example.com` / `admin123` y ya tienes ventas, productos con
stock bajo/agotado, y 12 meses de historial para probar Dashboard y Reportes
sin necesidad de cargar datos manualmente.

---

## Hallazgos corregidos en esta revisión (julio 2026)

El esquema original de ambos archivos se había reconstruido leyendo el código
fuente manualmente (no existía un `.sql` de referencia), y quedaron varias
tablas/columnas desalineadas con lo que el código realmente consulta. Se
corrigieron todas verificando **en vivo contra el servidor** (login real,
recorrido de +25 páginas autenticadas):

- `tec_users`: faltaban `last_ip_address`, `avatar`, `gender`, `hora_inicio`, `hora_fin`
- `tec_hacienda_tiketes` / `tec_hacienda_fec`: faltaban `sale_id`, `xml`, `xml_sign`,
  `xml_hacienda`, `fecha_emision`, `id_hacienda`, `mail` — bloqueaba Ventas, Reportes y Dashboard
- `tec_payments`: faltaban `paid_by`, `customer_id`, `cheque_no`, `cc_*`, `pos_paid`,
  `pos_balance`, `transaction_id`, `currency`, `reference` — **bloqueaba el cierre de
  venta del POS en cada transacción**
- `tec_purchases`, `tec_purchase_items`, `tec_expenses`, `tec_impuestos`, `tec_ubicaciones`:
  tablas completas ausentes (Dashboard financiero y alta de productos las requieren)
- `tec_registers`, `tec_sale_items`, `tec_quotes`, `tec_documentoshacienda`, `tec_products`,
  `tec_printers`, `tec_suspended_sales`: columnas faltantes puntuales
- `tec_provincia_cr` / `tec_canton_cr` / `tec_distrito_cr` / `tec_barrio_cr`: los nombres
  de columna no coincidían con las consultas reales (`codigo_provincia`/`nombre_provincia`,
  `nombre_canton`, `nombre_distrito`, `nombre_barrio` — ver `Facturascompras.php` y `FEC_model.php`)
- Hash de contraseña del admin en `NeurixBD-Inicial.sql`: estaba en formato bcrypt, pero
  el sistema usa `hash_method='sha1'` con salt embebido (`app/config/ion_auth.php`) — corregido
- `versionPOS`: estaba fijado en `43`; el código real llega hasta la versión `60`.
  Se actualizó a `60` en ambos archivos para que coincida con el esquema ya completo
  y el sistema no intente re-ejecutar migraciones parciales

Además se corrigió un bug de sintaxis PHP preexistente en
`themes/default/views/settings/index.php:206` (comillas duplicadas en
`lang(''desbloquear_confirm'')`) que rompía por completo la página de Configuración.

**Todo esto se verificó importando ambos archivos en MySQL limpio y navegando
la aplicación real vía HTTP** (login, POS, Dashboard, Reportes, Configuración,
Productos, Clientes, Proveedores, Compras — más de 25 rutas), no solo revisando el SQL.

---

## Notas Importantes

⚠️ **Ambiente**
- Ambas bases vienen con `ambiente='test'` por defecto (sandbox de Hacienda)
- Para producción, cambiar a `ambiente='prod'` solo después de confirmar credenciales TRIBU-CR

🔐 **Seguridad**
- Cambiar las contraseñas por defecto inmediatamente
- Usar HTTPS en producción

💾 **Backups**
- `mysqldump -u root -p NeurixBD > backup.sql` antes de cualquier actualización

---

**Versión:** 1.1.0
**Última actualización:** julio 2026

# Rediseño de Tablas — Neurix POS (nx-tables)

**Fecha:** 2026-07-02
**Módulo piloto:** Productos ([themes/default/views/products/index.php](themes/default/views/products/index.php))
**Sistema de diseño:** [themes/default/assets/src/nx-tables.css](themes/default/assets/src/nx-tables.css) (namespace `.nxt-*`)

---

## 1. Mejoras de diseño implementadas

### 1.1 Visual / UX

| Mejora | Descripción |
|---|---|
| **KPIs de resumen** | Tarjetas superiores con barra de acento por color: productos activos, valor de inventario a costo (solo admin), stock bajo (según `alert_quantity`) y agotados. Se calculan en vivo sobre los datos cargados. |
| **Tarjeta glassmorphism** | La tabla vive dentro de una tarjeta con blur, borde sutil y sombra, coherente con el dashboard y el POS rediseñados. |
| **Toolbar integrada** | Búsqueda instantánea (nombre/código, sin recargar), chips de filtro por categoría generados dinámicamente desde los datos, y contador «X de Y productos». |
| **Avatares de producto** | Miniatura de la imagen del producto si existe; si no, avatar con iniciales y gradiente de color estable por categoría (paleta de 10 pares). Clic en la imagen abre zoom en modal. |
| **Badges de categoría** | Píldoras con punto de color y fondo translúcido (`color-mix`), color asignado por categoría. |
| **Stock visual** | Cantidad + estado (OK / Bajo / Agotado) + barra de progreso relativa al mínimo de reorden (`alert_quantity × 3`). Los servicios muestran «—» (no manejan stock). |
| **Precios inteligentes** | Si hay precio de oferta se muestra el precio normal tachado y la oferta en verde. Formato de moneda según configuración del sistema (símbolo, decimales, separadores). |
| **Margen calculado** | Solo admin: `(precio − costo) / precio`, con semáforo ≥30 % verde, 15–29 % ámbar, <15 % rojo. |
| **Acciones como iconos** | Botones de icono (ver, código de barras, etiqueta, editar, eliminar) con hover tintado por severidad; eliminar pasa por el confirm global de SweetAlert2 (`data-confirm`). |
| **Ordenamiento visual** | Columnas Stock, Costo y Precio ordenables con indicador ↕/↑/↓. |
| **Paginación cliente** | 25 filas por página con ventana de 7 páginas; contador «Mostrando a–b de n». |
| **Exportar CSV** | Botón Exportar genera CSV (UTF-8 con BOM, compatible Excel) respetando los filtros activos. Sustituye a los botones copy/excel/pdf de DataTables. |
| **Dark / Light** | Todo el sistema usa las variables de `neurix-theme-vars.css`, por lo que responde automáticamente al toggle de tema existente. |
| **Estados vacíos** | Spinner durante la carga y mensaje claro cuando la búsqueda no arroja resultados. |
| **Responsive** | KPIs en grid auto-fit, toolbar con wrap, tabla con scroll horizontal y encabezado sticky; contador se oculta en móvil. Respeta `prefers-reduced-motion`. |

### 1.2 Técnicas

- **Vanilla JS, sin jQuery ni DataTables.** La página anterior dependía de `$(document).ready` y de la API de DataTables (`new Tabulator({...ajax, columns, render...})`), librerías que **ya no se cargan** desde la migración a Vite/AdminLTE 4 — es decir, el listado estaba roto. La nueva vista usa `fetch` + render propio.
- **Compatibilidad con el backend existente.** Se consume el mismo endpoint `products/get_products/{store_id}` (librería Ignited Datatables) enviando el protocolo DataTables mínimo (`draw`, `length=-1`, `search`, `columns[0]`) con el token CSRF, y se recibe todo el catálogo de una vez para filtrar/ordenar/paginar en cliente.
- **Seguridad:** todo dato dinámico pasa por `esc()` (escape HTML) antes de inyectarse; enlaces de acciones construidos con el `pid` numérico.
- **i18n completa:** todas las etiquetas nuevas tienen clave en español, inglés y chino (bloque «Rediseño de listados» al final de cada `app_lang.php`).

### 1.3 Cambios en archivos

| Archivo | Cambio |
|---|---|
| `themes/default/assets/src/nx-tables.css` | **Nuevo.** Sistema de diseño reusable `.nxt-*` para todos los listados. |
| `themes/default/assets/src/main.js` | Importa `nx-tables.css`. **Fix importante:** ahora expone `window.bootstrap` — antes nunca se asignaba, por lo que cualquier `bootstrap.Modal` programático (incluidos los guards de `pos-core.js`) fallaba silenciosamente. |
| `themes/default/views/products/index.php` | Reescrito por completo con el nuevo diseño. |
| `app/controllers/Products.php` | `get_products()`: conserva `pid` en la respuesta (la vista construye las acciones en cliente), añade `alert_quantity` y expone `ubicacion` también a usuarios no-admin. |
| `app/language/{spanish,english,chinese}/app_lang.php` | 19 claves nuevas (`exportar`, `stock_bajo`, `valor_inventario`, etc.). |
| `vite.config.js` | El plugin de copia ahora replica `src/nx-sidebar.css → dist/css/nx-sidebar.css` en cada build (ver §3). |

---

## 2. Guía: cómo migrar otra página al diseño nx-tables

1. **Estructura HTML** (todo ya estilado por `www.min.css`, no requiere CSS nuevo):
   ```html
   <div class="nxt-head">…título + .nxt-btn…</div>
   <div class="nxt-kpis">…tarjetas .nxt-kpi (opcional)…</div>
   <div class="nxt-card">
     <div class="nxt-toolbar">.nxt-search + .nxt-chips + .nxt-count</div>
     <div class="nxt-table-wrap"><table class="nxt-table">…</table></div>
     <div class="nxt-foot">info + .nxt-pages</div>
   </div>
   ```
2. **Datos:** replicar el patrón de `products/index.php` — `fetch` POST al endpoint `get_*` existente con CSRF + protocolo DataTables mínimo, y render en cliente.
3. **Celdas ricas disponibles:** `.nxt-ent` (avatar+nombre), `.nxt-code`, `.nxt-cat` (badge con `--cat-c`), `.nxt-stock` (+`s-ok|s-low|s-out`), `.nxt-price/.nxt-old/.nxt-offer`, `.nxt-margin (mid|low)`, `.nxt-dim-mono`, `.nxt-actions > .nxt-icon-btn (warn|danger)`.
4. **No usar jQuery ni `new Tabulator` con configuración DataTables** — esa API ya no existe en el bundle.
5. Si el listado puede superar ~5 000 filas, cambiar a paginación server-side (enviar `start/length/search` reales al endpoint en cada página).

---

## 3. Incidencias detectadas durante la implementación

- **`dist/css/nx-sidebar.css` no sobrevivía a los builds.** Vite compila con `emptyOutDir: true` y ese archivo (estilos premium de header/sidebar/footer enlazado por `header.php`) solo existía en `dist/`, sin copia en `src/` ni en git. Un `npm run build` lo eliminaba. **Solución:** se recuperó el archivo completo, ahora vive en `themes/default/assets/src/nx-sidebar.css` (⚠️ **pendiente de commitear**) y `vite.config.js` lo copia a `dist/` en cada build.
- **`dist/css/neurix-theme.css` está huérfano.** Está en git pero ninguna vista lo referencia (fue absorbido por `neurix-theme-vars.css` dentro del bundle) y cada build lo borra, dejando el working tree con un «deleted». Recomendación: eliminarlo del repositorio (`git rm`).
- **`window.bootstrap` no existía** (ver §1.3) — corregido en `main.js`.
- **Los listados legacy están rotos** desde la migración del frontend: dependen de `$` (jQuery) y de DataTables, que ya no se cargan. La auditoría siguiente es, por tanto, también la lista de páginas pendientes de reparar.
- **El service worker servía CSS/JS viejos para siempre.** `sw.js` cacheaba `.css`/`.js` con estrategia cache-first, por lo que ningún build llegaba a los navegadores que ya habían visitado el sistema («se ve sin diseño» tras desplegar). **Solución:** (1) CSS/JS pasan a network-first con caché solo como fallback offline y se subió la versión del caché a `neurix-pos-v1.1` para purgar el viejo; (2) los `<link>`/`<script>` de `header.php`, `pos/index.php` y `auth/login.php` llevan ahora `?v=<filemtime>` (cache-busting automático por build). Tras desplegar, basta recargar la página dos veces (o Ctrl+Shift+R) para que el SW nuevo tome control.

---

## 4. Auditoría: páginas del sistema que usan tablas de datos

40 vistas usan el patrón legacy (`new Tabulator` + config DataTables + jQuery). Todas deberían migrarse al diseño `nxt-*` siguiendo la receta del §2. Prioridad sugerida según uso diario en un minisúper/licorera:

### Prioridad ALTA (operación diaria)

| Vista | Endpoint de datos | Notas |
|---|---|---|
| ~~`products/index.php`~~ | `products/get_products` | ✅ **Migrada (piloto)** |
| `sales/index.php` | `sales/get_sales` | Listado principal de ventas/facturas. KPIs sugeridos: ventas del día, monto, pendientes Hacienda. |
| `customers/index.php` | `customers/get_customers` | Avatar con iniciales; badge por tipo de cédula. |
| `pos/sales` (vía `sales/*`) | — | Verificar variantes `opened.php`, `apartado.php`, `proforma.php`. |
| `categories/index.php` | `categories/get_categories` | Tabla simple, migración rápida. |
| `suppliers/index.php` | `suppliers/get_suppliers` | Igual a clientes. |

### Prioridad MEDIA (gestión y facturación electrónica)

| Vista | Notas |
|---|---|
| `purchases/index.php` | Compras. |
| `purchases/expenses.php` | Gastos. |
| `facturascompras/index.php` | FE de compras — badge de estado Hacienda (aceptado/rechazado) con `.nxt-cat`. |
| `creditnotes/index.php` | Notas de crédito. |
| `debitnotes/index.php` | Notas de débito. |
| `cargadocumentos/index.php` | Carga de documentos XML. |
| `products/list_prices.php` | Listas de precios. |
| `gift_cards/index.php` | Tarjetas regalo. |
| `waiting_tables/index.php` | Mesas en espera. |
| `auth/index.php` | Usuarios. |

### Prioridad MEDIA-BAJA (reportes — 16 vistas)

`reports/daily.php`, `reports/monthly.php`, `reports/monthly_fec.php`, `reports/monthly_sale_tax.php`, `reports/sales.php`, `reports/sale_fe.php`, `reports/payments.php`, `reports/products.php`, `reports/products_quantity.php`, `reports/registers.php`, `reports/custumer_credits.php`, `reports/shipping_credits.php`, `reports/compraselectronicas.php`, `reports/inventory_adjustment.php`, `reports/missing_inventory.php`, `reports/model_d151.php`, `reports/alerts.php`

> En reportes conviene conservar el rango de fechas actual y añadir KPIs de totales arriba (ya se calculan en servidor en varios casos).

### Prioridad BAJA (configuración)

| Vista | Notas |
|---|---|
| `settings/stores.php` y `stores.php` | Tiendas (vista duplicada — unificar). |
| `settings/printers.php` | Impresoras. |
| `settings/shipping_method.php` | Métodos de envío. |
| `settings/actividad.php` | Actividades económicas. |

### Fuera de alcance del rediseño de listados

- Tablas de **detalle/impresión** (`pos/view.php`, `sales/view.php`, `*/invoice.php`, etiquetas, tiquetes): son documentos, mantienen su estilo de impresión.
- `products/view.php`, `gift_cards/view.php`: tablas descriptivas dentro de modales.

---

## 5. Estado de compilación

```
npm run build  →  OK (vite 8)
dist/css/www.min.css   (incluye nx-tables.css)
dist/css/nx-sidebar.css (copiado desde src en cada build)
dist/js/main.min.js    (expone window.bootstrap)
```
Lint PHP: `Products.php`, `products/index.php` y los 3 `app_lang.php` sin errores de sintaxis.

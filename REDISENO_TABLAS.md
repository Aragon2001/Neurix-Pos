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

## 4. Auditoría y estado de migración — ✅ COMPLETADA (2026-07-03)

Las 40 vistas que usaban el patrón legacy (`new Tabulator` + config DataTables + jQuery, roto desde la migración a Vite) fueron migradas al diseño `nxt-*`. La migración masiva se hizo con un **motor JS reusable**: [`themes/default/assets/src/nx-table.js`](themes/default/assets/src/nx-table.js) (`window.NxTable`), que genera toolbar + tabla + paginación + export CSV + fila de totales desde una configuración declarativa, re-estiliza el HTML de acciones que generan los controladores (sin tocar el backend) y restaura el comportamiento global de `data-toggle="ajax-modal"` / `"ajax"` en vanilla JS.

### Migradas — operación diaria

| Vista | Particularidades |
|---|---|
| `products/index.php` | Piloto: KPIs de inventario, chips por categoría, barra de stock, margen. |
| `sales/index.php` | KPIs (facturado, cobrado, saldo, pendientes Hacienda), chips por estado Hacienda, totales, modal de cambio de estado (admin), botones XML. |
| `customers/index.php` / `suppliers/index.php` | Avatar + chips por tipo de cédula. |
| `categories/index.php` | Miniatura de imagen con zoom. |
| `sales/opened.php`, `sales/apartado.php`, `sales/proforma.php` | Totales al pie; apartados con chips de estado de pago. |

### Migradas — gestión y FE

`purchases/index.php` (adjuntos), `purchases/expenses.php`, `facturascompras/index.php` (igual a ventas), `creditnotes/index.php`, `debitnotes/index.php`, `cargadocumentos/index.php` (**incluye la zona de carga XML drag&drop reescrita en vanilla + modal de resultados**), `products/list_prices.php`, `gift_cards/index.php`, `waiting_tables/index.php`, `auth/index.php` (usa el modo `data:` local de NxTable — tabla server-rendered sin endpoint).

### Migradas — reportes (16)

`daily`, `monthly`, `monthly_fec`, `monthly_sale_tax`, `sales`, `sale_fe`, `payments`, `products`, `products_quantity`, `registers`, `custumer_credits`, `shipping_credits`, `compraselectronicas`, `inventory_adjustment`, `missing_inventory`, `model_d151`, `alerts`.

Notas de los reportes:
- Los formularios de filtro se conservaron (restylados dentro de `.nxt-card`) y los campos de fecha pasaron de TempusDominus (cuya inicialización estaba rota) a **`<input type="date">` nativos** — mismo formato `YYYY-MM-DD` que espera el backend.
- Los `footerCallback` de DataTables se reemplazaron por la opción `totals:` de NxTable (suma sobre lo filtrado).
- `custumer_credits` y `shipping_credits` conservan el **formulario de abono a deuda**; la lógica jQuery (selección de facturas con checkbox, suma de balances, toggle de campos según método de pago) se reescribió en vanilla JS — antes estaba rota por falta de jQuery.
- `alerts` reimplementa "agregar a orden de compra" (spoitems en localStorage) con fetch + toast de SweetAlert2.

### Migradas — configuración

`settings/stores.php`, `stores.php`, `settings/printers.php`, `settings/shipping_method.php`, `settings/actividad.php`.

### Fuera de alcance del rediseño de listados

- Tablas de **detalle/impresión** (`pos/view.php`, `sales/view.php`, `*/invoice.php`, etiquetas, tiquetes): son documentos, mantienen su estilo de impresión.
- `products/view.php`, `gift_cards/view.php`: tablas descriptivas dentro de modales.

---

## 5. API de NxTable (referencia rápida)

```js
new NxTable({
  el: '#nxtList',                       // contenedor vacío; NxTable genera todo el markup
  url: SITE + 'modulo/get_x',           // endpoint Ignited-Datatables (POST + CSRF)
  csrf: { name, hash },
  data: [...],                          // alternativa a url: datos locales (tabla estática)
  columns: [{ key, label, className, width, sortable:'num'|'str',
              render(r), exportValue(r), actions:true, noExport:true }],
  search: ['campo1', 'campo2'],         // búsqueda instantánea client-side
  chips: { key, all, label(v), sort },  // filtro por chips generado de los datos
  totals: ['total', 'paid'],            // fila de totales sobre lo filtrado
  perPage: 25, unit: 'ventas', exportName: 'ventas',
  onData(rows) { ... },                 // hook para KPIs
  map(raw) { ... },                     // normalizar fila cruda
  i18n: { searchPlaceholder, loading, empty, showing, of, all, totals }
})
// Helpers: NxTable.esc, .money, .num, .qty, .badge(texto, tono), .entity(nombre, meta, seed)
// Instancia: t.load() (recargar), t.exportCSV(), t.filtered()
```

Tonos de badge: `ok` (verde), `info` (azul), `warn` (ámbar), `err` (rojo), `muted`, `violet`, `orange`.

---

## 6. Estado de compilación

```
npm run build  →  OK (vite 8, 104 módulos)
dist/css/www.min.css   (incluye nx-tables.css)
dist/css/nx-sidebar.css (copiado desde src en cada build)
dist/js/main.min.js    (expone window.bootstrap y window.NxTable)
```
Lint PHP de las 40 vistas migradas + controladores + 3 `app_lang.php`: sin errores. Se añadieron ~60 claves de idioma nuevas en español, inglés y chino (bloques al final de cada `app_lang.php`).

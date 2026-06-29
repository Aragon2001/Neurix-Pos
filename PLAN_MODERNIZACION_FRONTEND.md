# Plan de modernización del frontend

> Objetivo: reemplazar todo el stack visual por la tecnología equivalente más moderna disponible (sin frameworks tipo React — eso ya se evaluó y descartado en `Analisis_Migracion_React.docx`). Este documento está pensado para que un agente de código (Claude Code) lo ejecute fase por fase, marcando cada casilla al completarla.
>
> Base de partida confirmada por auditoría (ver `Auditoria_Modernizacion_Interfaz.docx`): 141 vistas, 17 módulos, Bootstrap 3.3.4, AdminLTE2, jQuery 3.7.1, Font Awesome 4, select2, iCheck, bootstrap-datetimepicker (Eonasdan, abandonado), DataTables (jQuery), capa de diseño propia `neurix-theme.css` (368 reglas, variables CSS, modo oscuro/claro).

## Matriz de remplazo tecnológico

| # | Tecnología actual | Estado | Remplazo recomendado | Por qué |
|---|---|---|---|---|
| 1 | Bootstrap 3.3.4 (2015) | Sin soporte | **Bootstrap 5.3.8** | Última versión estable. No depende de jQuery. Usa variables CSS — compatible con el enfoque que ya usa `neurix-theme.css`. |
| 2 | AdminLTE 2 (clases `skin-blue`, `sidebar-mini`) | Sin soporte | **AdminLTE 4.0** (mayo 2026) | Reescritura completa sobre Bootstrap 5.3, TypeScript puro, sin jQuery, modo oscuro nativo con variables CSS, RTL, accesibilidad mejorada. Es el sucesor directo y oficial de AdminLTE2. |
| 3 | jQuery 3.7.1 (cargado en las 141 vistas) | Innecesario con BS5/AdminLTE4 | **Eliminarlo** | Bootstrap 5 y AdminLTE4 funcionan en JS nativo. Quitar jQuery reduce peso de página y elimina una dependencia global que hoy no protege contra conflictos de versión. |
| 4 | iCheck (checkboxes/radios) | Abandonado (~2015) | **Quitarlo, usar `.form-check`/`.form-switch` de Bootstrap 5** | BS5 ya trae checkboxes/radios/switches estilizados de forma nativa; el plugin deja de ser necesario. |
| 5 | select2 (jQuery) | Activo pero dependiente de jQuery | **Tom Select** | Sin dependencias, mismo conjunto de funciones (búsqueda, tags, carga remota), mantenido activamente. |
| 6 | bootstrap-datetimepicker (Eonasdan, BS3) | Repositorio archivado/sin soporte | **Tempus Dominus v6** | Es el sucesor oficial del mismo autor. Sin dependencias (ni jQuery, ni moment, ni Bootstrap obligatorio). |
| 7 | DataTables (jQuery) | Activo pero dependiente de jQuery | **Tabulator.js** | Cero dependencias, mismo nivel de funciones (orden, filtro, paginación, exportar), mejor rendimiento con tablas grandes (útil en Reportes). |
| 8 | SweetAlert2 | Ya moderno | **Mantener, solo actualizar versión** | Ya no depende de jQuery ni de Bootstrap; no hace falta remplazarlo. |
| 9 | Font Awesome 4 (sintaxis `fa fa-x`, sufijo `-o`) | Descontinuada | **Font Awesome 6** | FA4 renombró/retiró iconos en FA5/6 (ej. `fa-file-pdf-o` → `fa-file-pdf`, `fa-money` → `fa-money-bill`, `fa-dollar` → `fa-dollar-sign`, `fa-cloud-upload` → `fa-cloud-upload-alt`). Requiere mapeo de clases, no solo cambiar el CSS. |
| 10 | Glyphicons (2 archivos) | Descontinuado desde BS4 | **Font Awesome 6** | Ya casi no se usan; terminar de eliminarlos en los 2 archivos restantes. |
| 11 | Sin build tool (assets servidos sueltos) | — | **Vite** | Tom Select, Tabulator y Tempus Dominus se distribuyen como módulos ES; Vite los empaqueta y minimiza sin complicar el despliegue en Apache/Laragon. |
| 12 | CodeIgniter 3.1.9 / PHP 8.3 | CI3 sin soporte desde 2024; PHP 8.1 EOL desde dic. 2025 (el proyecto ya corre en PHP 8.3, que sí tiene soporte hasta dic. 2026) | **CodeIgniter 4.7.x** (a futuro, fuera de este plan) | Es una reescritura, no una actualización — ya se documentó el costo/riesgo en `Analisis_Migracion_React.docx`. Se deja como Fase 7, opcional y posterior a todo lo anterior. |

## Cómo trabajar este plan

Cada fase es independiente y verificable. Antes de empezar una fase: crear una rama de git (`git checkout -b fase-N-nombre`). Al terminar: probar manualmente (no hay tests automatizados en el proyecto) y hacer commit. No avanzar a la siguiente fase con la anterior a medias.

Orden de riesgo: los módulos de **Hacienda** (`Shacienda.php`) y **Punto de Venta** (`Pos.php` y vistas `pos/*`) se migran **al final** de cada fase, ya con el patrón probado en módulos de menor riesgo.

---

## Fase 0 — Preparación

- [x] Crear rama `modernizacion-frontend` desde `main`.
- [x] Hacer backup de `themes/default/assets/` completo.
- [x] Inicializar `package.json` y Vite en la raíz del proyecto (`npm init -y && npm install vite --save-dev`).
- [x] Instalar dependencias nuevas: `npm install bootstrap@5.3.8 @eonasdan/tempus-dominus tom-select tabulator-tables sweetalert2 @fortawesome/fontawesome-free`.
- [x] Definir convención de carga de assets compilados (carpeta `themes/default/assets/dist/` ya existe y se usa para `neurix-theme.css`; reutilizarla para los bundles de Vite).
- [x] Documentar en este archivo, debajo de esta fase, qué versión exacta quedó instalada de cada paquete (`npm list`).

### ✅ Fase 0 Completada (2026-06-29)
**Versiones instaladas**:
- vite: 8.1.0
- bootstrap: 5.3.8
- @eonasdan/tempus-dominus: 6.10.4
- tom-select: 2.6.1
- tabulator-tables: 6.5.2
- sweetalert2: 11.26.25
- @fortawesome/fontawesome-free: 7.3.0

**Archivos creados**:
- `package.json` — configuración npm
- `vite.config.js` — configuración de Vite con salida en `themes/default/assets/dist/`
- Backup: `themes/default/assets.backup.20260629-HHMMSS/`

**Estado**: ✅ Listo para Fase 1

## Fase 1 — Bootstrap 3 → Bootstrap 5 (mecánico, por módulo)

Cambios de clase más frecuentes a aplicar en las 141 vistas (buscar y remplazar, revisando cada caso):

| Bootstrap 3 | Bootstrap 5 |
|---|---|
| `col-xs-*` | `col-*` |
| `col-md-*` / `col-lg-*` | igual (se mantienen) |
| `.panel`, `.panel-heading`, `.panel-body`, `.panel-footer` | `.card`, `.card-header`, `.card-body`, `.card-footer` |
| `.well` | `.card` o `.p-3.bg-body-secondary` |
| `.list-group-item` | igual (se mantiene, revisar utilidades internas) |
| `.thumbnail` | `.card` con `.card-img-top` |
| `.nav-pills > li > a` | `.nav-link` dentro de `.nav-item` |
| `data-toggle="modal"` | `data-bs-toggle="modal"` |
| `data-dismiss="modal"` | `data-bs-dismiss="modal"` |
| `.form-group` | `.mb-3` |
| `.control-label` | `.form-label` |
| `.input-group-addon` | `.input-group-text` |
| `.hidden-xs`, `.visible-xs`, etc. | `.d-none .d-md-block`, etc. (utilidades de display) |
| `.glyphicon-*` | `.fa-*` (Font Awesome, ver Fase 3) |

Checklist por módulo (orden de menor a mayor riesgo):

- [ ] Dashboard / raíz (`Site.php`, `Dashboard.php`, `stores.php`)
- [ ] Categorías
- [ ] Proveedores
- [ ] Clientes
- [ ] Notas de crédito / débito
- [ ] Compras (`purchases/*`, `facturascompras/*`)
- [ ] Carga de documentos
- [ ] Productos
- [ ] Configuración (`settings/*`)
- [ ] Ventas (`sales/*`)
- [ ] **Reportes (`reports/*`)** — aquí vive el hallazgo principal de la auditoría anterior: 14 de 18 vistas usan `.panel` sin estilo en modo oscuro. Al migrar a `.card` quedan automáticamente cubiertas por `neurix-theme.css` si se extiende su selector (ver Fase 4).
- [ ] Autenticación (`auth/*`, incluyendo `profile.php` y `create_user.php`)
- [ ] `promotions.php` (hoy es una plantilla pública totalmente desconectada del sistema de diseño — reconstruir usando los componentes BS5/Neurix, no solo traducir clases).
- [ ] Punto de Venta (`pos/*`) — **al final**, validar impresión de tickets/facturas (`pos/eview.php`, `pos/eviewnc.php` deben mantener fondo blanco fijo, no tema oscuro).

## Fase 2 — AdminLTE 2 → AdminLTE 4 (esqueleto)

- [ ] Reemplazar `header.php`: clases de `<body>` (`skin-blue fixed sidebar-mini` → sintaxis de layout AdminLTE4), estructura del `<nav>` y `.main-sidebar`.
- [ ] Adaptar el sidebar (`.sidebar-menu`, `.treeview`, `.treeview-menu`) a los componentes equivalentes de AdminLTE4.
- [ ] Revisar el script anti-FOUC de tema oscuro (`localStorage.getItem('nx-theme')`) — AdminLTE4 ya trae su propio sistema de modo oscuro vía `data-bs-theme`; decidir si se usa el nativo de AdminLTE4 o se mantiene el de Neurix superpuesto (recomendado: migrar las variables `--nx-*` para que extiendan las variables de AdminLTE4 en vez de duplicar el mecanismo).
- [ ] Unificar `<meta name="viewport">` en todas las vistas (hoy `header.php`/`login.php` usan `user-scalable=no` y `auth/reset_password.php` usa `user-scalable=yes` — dejar un solo valor consistente).

## Fase 3 — Reemplazo de plugins JS/jQuery

- [ ] **Font Awesome 4 → 6**: mapear y remplazar nombres de íconos (no es un cambio 1 a 1; ej. `fa-file-pdf-o`→`fa-file-pdf`, `fa-money`→`fa-money-bill`, `fa-dollar`→`fa-dollar-sign`, `fa-cloud-upload`→`fa-cloud-upload-alt`, todo sufijo `-o` se elimina). Hacer un script de búsqueda/remplazo con la tabla de equivalencias oficial de FA antes de tocar las vistas a mano.
- [ ] **select2 → Tom Select**: revisar cada `select2()` en los `<script>` embebidos de las vistas (sobre todo en `products`, `sales`, `purchases`, `pos`) y reescribir la inicialización con la API de Tom Select.
- [ ] **bootstrap-datetimepicker → Tempus Dominus v6**: mismo patrón, revisar cada vista con selector de fecha/hora (reportes con filtro de fechas son las más comunes).
- [ ] **DataTables → Tabulator.js**: empezar por las tablas de Reportes (más beneficiadas por el rendimiento de Tabulator) y seguir con `products/index.php`, `customers/index.php`, `sales/index.php`.
- [ ] **iCheck → eliminar**: quitar el plugin y dejar que Bootstrap 5 estilice los checkboxes/radios nativos (`.form-check-input`).
- [ ] **Quitar `<script src=".../jquery-3.7.1.min.js">` de todas las vistas** una vez que las dependencias 1-5 ya no requieran jQuery. Buscar también cualquier `$(...)` suelto en `<script>` embebidos y reescribirlo en JS nativo (`document.querySelector`, `addEventListener`, `fetch` en vez de `$.ajax`).
- [ ] Borrar del repositorio los archivos sin uso: `jQuery-2.1.4.min.js` (no referenciado en ninguna vista, confirmado en auditoría), y las carpetas de los plugins reemplazados (`select2/`, `iCheck/`, `bootstrap-datetimepicker/`, `datatables/` antiguas).

## Fase 4 — Ajustar `neurix-theme.css`

- [ ] Añadir reglas para `.card`/`.card-header`/`.card-body` (remplazan a `.panel*`) — esto resuelve de un solo cambio el hallazgo de la auditoría sobre el módulo de Reportes.
- [ ] Añadir reglas para `.form-check-input`, `.form-switch` (remplazan a iCheck).
- [ ] Añadir reglas para los contenedores de Tom Select (`.ts-control`, `.ts-dropdown`) — equivalente a las reglas que hoy existen para `.select2-container`.
- [ ] Añadir reglas para Tempus Dominus (`.tempus-dominus-widget`) — equivalente a `.bootstrap-datetimepicker-widget` actual.
- [ ] Añadir reglas para Tabulator (`.tabulator`, `.tabulator-row`, `.tabulator-header`) — equivalente a `.dataTables_wrapper` actual.
- [ ] Revisar si conviene migrar las variables `--nx-*` para heredar de las variables nativas de Bootstrap 5 / AdminLTE 4 (`--bs-*`) en vez de mantener un sistema paralelo — reduce duplicación a futuro.

## Fase 5 — Limpieza final

- [ ] Confirmar 0 referencias a `glyphicon`, `select2`, `icheck`, `data-toggle` (sin `bs-`), `panel-*` en todo `themes/default/views`.
- [ ] Agregar `.table-responsive` a las vistas con tablas que aún no lo tienen (~81 de 131, según auditoría).
- [ ] Verificar que `pos/eview.php`, `pos/eviewnc.php`, `creditnotes/eviewnc.php` mantengan fondo blanco fijo para impresión (no deben heredar modo oscuro).

## Fase 6 — Validación módulo por módulo

No hay pruebas automatizadas en el proyecto, así que cada módulo se prueba a mano antes de pasar al siguiente:

- [ ] Login / recuperación de contraseña
- [ ] Dashboard
- [ ] Cada módulo de la Fase 1, en el mismo orden
- [ ] **Hacienda**: emitir una factura electrónica de prueba de cada tipo (factura, nota de crédito, nota de débito) y confirmar que el flujo de firma/envío a Hacienda (`Shacienda.php`) no se vio afectado por ningún cambio de frontend.
- [ ] **Punto de Venta**: ciclo completo (abrir caja, vender, cobrar, imprimir, cerrar caja) en al menos dos navegadores/dispositivos.

## Fase 7 — (Opcional, fuera de alcance de este plan) Backend

CodeIgniter 3 ya no recibe soporte. Migrar a CodeIgniter 4 es una reescritura completa (no hay compatibilidad hacia atrás), con el mismo nivel de esfuerzo/riesgo ya documentado para una migración a React en `Analisis_Migracion_React.docx`. Se recomienda abordarlo como un proyecto separado, después de estabilizar el frontend, y nunca junto con Hacienda en producción sin un entorno de pruebas paralelo.

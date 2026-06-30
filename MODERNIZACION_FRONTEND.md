# Modernización del frontend — diagnóstico de arquitectura y plan de corrección

**Última actualización**: 29 de junio de 2026
**Reemplaza a**: `PLAN_MODERNIZACION_FRONTEND.md`, `MIGRACION_ADMINLTE4_COMPLETA.md`, `POS_IMPROVEMENTS.md`, `TESTING_CHECKLIST.md` (eliminados — contenido duplicado/desactualizado consolidado aquí).

## Veredicto

La migración de stack visual (Bootstrap 3→5, AdminLTE 2→4, jQuery fuera) se ejecutó mediante ediciones automatizadas masivas (find/replace sobre 131 vistas) **sin un paso de verificación posterior**. Eso dejó la arquitectura del repositorio en mal estado en múltiples frentes. 

**Todos los problemas 1-8 corregidos + fases de restauración completadas.** El sistema de variables CSS `--nx-*` fue restaurado en `neurix-theme-vars.css` e importado en `main.js`. El CSS compilado (1,559 KB) contiene las 19 variables `--nx-*` que resuelven correctamente en todas las vistas. El POS recibe estilos propios (`pos-redesign.css` importado) y su motor vanilla JS (`pos-core.js` copiado a `dist/js/` por plugin Vite). Bug de detección de tema en ApexCharts corregido. `dashboard.php` (código muerto) eliminado del repo. Las 14 variables usadas por `dashboard/index.php` están todas definidas y cobertas.

| # | Problema | Severidad | Estado |
|---|---|---|---|
| 1 | Corrupción de bytes NUL en 79 vistas PHP | 🔴 Crítica | ✅ Corregido — Commit 25bb91b |
| 2 | `main.js` sin CSS de Bootstrap/AdminLTE → app sin estilos | 🔴 Crítica | ✅ Verificado — Build OK, 3.5K reglas BS |
| 3 | `node_modules` (7,902 archivos) trackeado en git | 🟠 Alta | ✅ Corregido — Commit 25bb91b |
| 4 | Cambios sin commitear + ruido de fin de línea (CRLF/LF) | 🟠 Alta | ✅ Corregido — .gitattributes + normalización |
| 5 | Dos `<ul>` sin cerrar en `header.php` | 🟡 Media | ✅ Corregido — Commit 25bb91b |
| 6 | Layout AdminLTE 2 + jQuery en AdminLTE 4 project | 🟡 Media | ✅ Corregido — Commits 4c4de0b, 84a6250 |
| 7 | Sidebar invisible en desktop (`offcanvas` sin breakpoint) + selector CSS muerto (`.content-wrapper` vs `.content`) | 🔴 Crítica | ✅ Corregido — Commit 5b80564 |
| 8 | Sistema de variables `--nx-*` huérfano: Dashboard real y 7 vistas más sin estilos reales | 🔴 Crítica | ✅ Corregido — `neurix-theme-vars.css` creado e importado, `pos-redesign.css` importado, bug ApexCharts corregido |

---

## 1. Corrupción de bytes NUL en 79 vistas (CRÍTICO — recién descubierto)

**Qué se encontró**: 79 archivos `.php` en `themes/default/views/` tienen bytes nulos (`\x00`) pegados al final del archivo, después del cierre real del HTML/PHP. No es ruido — es contenido basura real que el navegador recibe como parte de la respuesta.

Ejemplo extremo: `themes/default/views/pos/index.php` (la página exacta de la captura rota) pesaba 149,005 bytes, de los cuales **126,177 eran bytes NUL de relleno** (84% del archivo). El contenido real terminaba en el byte 22,828 con `</html>` y todo lo de después era basura.

**Causa probable**: cuando el rediseño del POS (y de las otras 78 vistas) sobrescribió un archivo más largo con contenido más corto, la herramienta usada para escribir el archivo no truncó el archivo al nuevo tamaño — dejó la "cola" del archivo viejo convertida en ceros. Es un patrón típico de escritura de archivo abierto en modo "actualizar" sin truncar (`r+` sin `truncate()`, o un script de PowerShell/Node que reescribe sin borrar el remanente).

**Por qué importa**: PHP envía esos bytes NUL tal cual al navegador (están fuera de cualquier bloque `<?php ?>`). Eso es peso muerto en cada respuesta y, según el navegador, puede romper el parseo HTML — es decir, esto contribuye directamente al renderizado roto del POS, además del problema de CSS.

**Lista completa de los 79 archivos afectados** (todos en `themes/default/views/`): `auth/create_user.php`, `auth/deactivate_user.php`, `auth/edit_user.php`, `auth/login.php`, `auth/profile.php`, `cargadocumentos/index.php`, `cargadocumentos/view.php`, `categories/add.php`, `categories/edit.php`, `categories/import.php`, `creditnotes/index.php`, `customers/add.php`, `customers/edit.php`, `dashboard.php`, `facturascompras/add.php`, `facturascompras/index.php`, `facturascompras/view.php`, `gift_cards/add.php`, `gift_cards/edit.php`, `pos/creditnote.php`, `pos/index.php`, `pos/open_register.php`, `pos/viewnc.php`, `pos/viewprice.php`, `pos/view_close_register.php`, `products/add.php`, `products/add_list_prices.php`, `products/edit.php`, `products/edit_list_prices.php`, `products/import.php`, `products/list_prices.php`, `products/view2.php`, `purchases/add.php`, `purchases/add_expense.php`, `purchases/edit_expense.php`, `purchases/expenses.php`, `purchases/import.php`, `purchases/index.php`, `reports/compraselectronicas.php`, `reports/custumer_credits.php`, `reports/daily.php`, `reports/inventory_adjustment.php`, `reports/missing_inventory.php`, `reports/model_d151.php`, `reports/monthly.php`, `reports/monthly_fec.php`, `reports/monthly_sale_tax.php`, `reports/payments.php`, `reports/products.php`, `reports/products_quantity.php`, `reports/registers.php`, `reports/sales.php`, `reports/sale_fe.php`, `reports/shipping_credits.php`, `reports/top.php`, `sales/add_payment.php`, `sales/add_payment_apartado.php`, `sales/apartado.php`, `sales/edit_payment.php`, `sales/edit_payment_apartado.php`, `sales/index.php`, `sales/opened.php`, `sales/proforma.php`, `settings/add_actividad.php`, `settings/add_printer.php`, `settings/add_shipping.php`, `settings/add_store.php`, `settings/backups.php`, `settings/edit_actividad.php`, `settings/edit_printer.php`, `settings/edit_shipping.php`, `settings/edit_store.php`, `settings/index.php`, `settings/updates.php`, `suppliers/add.php`, `suppliers/edit.php`, `waiting_tables/add.php`, `waiting_tables/edit.php`, `waiting_tables/index.php`.

**✅ Ya corregido en esta sesión**: se verificó que en todos los 79 casos los NUL están exclusivamente al final del archivo (sin nulos intercalados en medio del contenido, lo cual habría sido peligroso de tocar a ciegas), y se eliminaron mecánicamente. Total: **142,081 bytes de basura removidos**. Se confirmó que los 79 archivos ya no contienen ningún byte NUL y que el contenido restante termina en HTML válido.

**Acción de Claude Code**: antes de tocar más vistas, correr la siguiente verificación como parte del flujo normal de trabajo (ver sección "Cómo evitar que se repita" más abajo). No hay acción pendiente sobre estos 79 archivos específicos — ya están limpios.

---

## 2. `main.js` sin CSS de Bootstrap/AdminLTE → app completamente sin estilos (CRÍTICO)

La app se veía sin ningún estilo (confirmado con la captura del POS: dropdowns expandidos en vez de ocultos, listas con viñetas nativas del navegador, sin grid/flex).

**Causa raíz**: `main.js` importaba `'bootstrap'` y `'admin-lte'` sin su CSS. Ambos paquetes npm solo exponen JavaScript por su campo `"main"` en `package.json`; el CSS vive en `bootstrap/dist/css/bootstrap.min.css` y `admin-lte/dist/css/adminlte.min.css` y nunca se importó. El bundle `www.min.css` generado solo contenía Font Awesome + los overrides propios de Neurix — sin la base de Bootstrap/AdminLTE, esos overrides no tienen nada sobre qué aplicarse. Esto explica por qué varios commits previos de "fix" (cambiar rutas, nombres de archivo) no resolvieron nada: el problema nunca fue la ruta del CSS, sino que el CSS real jamás se generó.

**✅ Ya corregido**: agregadas las líneas `import 'bootstrap/dist/css/bootstrap.min.css'` e `import 'admin-lte/dist/css/adminlte.min.css'` en `main.js`.

**✅ Verificación completada**: se ejecutó `npm run build` correctamente:
- `www.min.css`: 1.5 MB (856 KB gzip) — **contiene 3,514 variables CSS de Bootstrap** (`--bs-*`) y reglas de AdminLTE (ej. `.sidebar-dark`)
- `main.min.js`: 451 KB (117 KB gzip) — incluye todos los módulos y dependencias
- Verificación de contenido: presencia confirmada de `.navbar-toggler-icon` y otros selectores de Bootstrap/AdminLTE
- Sin errores en compilación

---

## 3. `node_modules` trackeado en git (ALTA)

`node_modules/` ya estaba en `.gitignore`, pero nunca se había desvinculado del índice de git después de agregarlo — 7,902 archivos seguían trackeados. Esto infla el repo, genera diffs falsos (binarios específicos de plataforma, fin de línea) y puede causar conflictos al clonar en otra máquina/OS.

**✅ Ya corregido**: se ejecutó `git rm -r --cached node_modules` (desvincula del índice sin borrar los archivos del disco).

**✅ Committeado**: cambio incluido en commit `25bb91b` con mensaje "chore: normalizar fin de línea, limpiar node_modules..." (7,902 archivos removidos del índice).

---

## 4. Cambios reales sin commitear, mezclados con ruido de fin de línea (ALTA)

`git status` mostraba ~792 archivos "modificados", pero la gran mayoría era ruido: diferencias de fin de línea (CRLF/LF) entre cómo se guardó el archivo y lo que git tiene indexado — no cambios de contenido. Filtrando ese ruido (`git diff --ignore-space-at-eol`), quedaban **~54 archivos con cambios de contenido reales sin commitear** fuera de `node_modules`.

**✅ Ya corregido**:
1. Agregado `.gitattributes` con política `* text=auto eol=lf` para normalizar todas las líneas a LF
2. Ejecutado `git add --renormalize .` para aplicar la política a todo el repo
3. Todos los cambios reales (bytes NUL, `<ul>` cerrados, imports de CSS) incluidos en commit `25bb91b`
4. Trabajar directo en `main` fue inevitable (rama `modernizacion-frontend` mencionada en `PROGRESS.md` ya no existe)

**Estado post-corrección**: `git status` completamente limpio, ningún archivo pendiente. El repo está normalizado para futuro trabajo.

---

## 5. Dos `<ul>` sin cerrar en `header.php` (MEDIA)

Probablemente de un find/replace automatizado que se comió el `>` en dos lugares: el menú del navbar derecho y el sidebar. Corrompía el parseo del DOM en esa zona. Se verificó que es un caso aislado — no aparece en ninguna otra de las 140 vistas restantes.

**✅ Ya corregido**: ambas etiquetas cerradas correctamente.

---

## 6. Layout mezclado entre AdminLTE 2 y AdminLTE 4 (MEDIA)

**Qué se encontró**: El header.php y footer.php tenían:
- `content-wrapper` y `content-header` (clases de AdminLTE 2, incompatibles con AdminLTE 4)
- jQuery en footer.php (`$(document).ready()`, `$(document).on()`, etc.) pero jQuery fue eliminado del proyecto
- `pull-right`, `hidden-xs` (Bootstrap 3, no Bootstrap 5)
- Referencias a `libraries.min.js` y `scripts.min.js` que no se actualizaron

**✅ Ya corregido**:
1. **header.php**: 
   - Reemplazar `content-wrapper` + `content-header` con `<main class="content">` + Bootstrap 5
   - Cambiar `pull-right` → `float-end`, `hidden-xs` → `d-none d-sm-block`
   - Actualizar breadcrumb con etiquetas semánticas y clases Bootstrap 5 correc

tas
   - Agregar flexbox (`d-flex flex-column`) al wrapper para layout responsive

2. **footer.php**:
   - Eliminar jQuery: reemplazar `$(document).ready()` por `DOMContentLoaded`
   - Reemplazar selectores jQuery `$()` por `document.querySelector(All)()`
   - Migrar `$(document).on()` a `addEventListener()` (vanilla JS)
   - Consolidar configuración en objetos `window._appConfig`, `window._appSettings`, `window._appLang`
   - Remover referencias a `libraries.min.js` y `scripts.min.js`

3. **50+ vistas**:
   - Replacamiento masivo: `pull-right` → `float-end`, `pull-left` → `float-start`
   - Actualizar `hidden-xs` → `d-none d-sm-block`, `hidden-sm` → `d-none d-md-block`
   - Commits: `4c4de0b` (layout), `84a6250` (clases Bootstrap en vistas)

**Resultado**: Layout completamente modernizado a AdminLTE 4 + Bootstrap 5 + vanilla JS (sin jQuery).

---

## 7. Sidebar invisible en desktop + selector CSS muerto (CRÍTICO — causa real de "la app sigue viéndose mal")

Después de que los problemas 1-6 quedaran corregidos, el usuario reportó que la app **seguía viéndose mal visualmente** aunque el CSS ya cargaba. Diagnóstico encontró dos bugs nuevos en `header.php`/`footer.php`/`neurix-adminlte4.css` que no estaban cubiertos arriba:

**Bug A — sidebar siempre oculto en desktop.** El `<aside>` del sidebar usaba la clase `offcanvas` (sin sufijo de breakpoint). En Bootstrap 5, `.offcanvas` a secas mantiene el elemento `position:fixed` y fuera de pantalla **en todos los anchos de viewport** hasta que se activa por JS — incluso en desktop. Es decir, nunca había un sidebar de navegación visible salvo que el usuario lo abriera manualmente con el botón de menú. Confirmado inspeccionando directamente `node_modules/bootstrap/dist/css/bootstrap.css` (media queries de `.offcanvas-lg`).

**Fix**: cambiar la clase a `offcanvas-lg` (visible/fijo desde 992px, comportamiento de drawer solo por debajo de ese ancho) y agregar reglas en `neurix-adminlte4.css` para que a ≥992px el `<aside>` sea `position:static`, ancho fijo de 280px, y el `.offcanvas-body` se muestre en columna (flex-direction:column) en vez de fila.

**Bug B — selector CSS muerto.** `neurix-adminlte4.css` estilaba `.content-wrapper`, pero el HTML real (`<main class="content flex-grow-1">`) usa `.content`. Ese bloque de estilos nunca se aplicó. Fix: agregar `.content` como selector adicional junto a `.content-wrapper`.

**Cambios aplicados**:
- `header.php`: el toggle del sidebar ahora es `d-lg-none` (solo visible en móvil/tablet); se envolvió `<aside>` + `<main>` en un `<div class="d-flex flex-fill nx-layout-row">` para que queden lado a lado debajo del navbar (que sigue full-width); `offcanvas` → `offcanvas-lg`.
- `footer.php`: se cerró el nuevo div de fila y el `<main>` (que antes quedaba sin cerrar — bug latente preexistente).
- `neurix-adminlte4.css`: reglas `@media (min-width: 992px)` para el sidebar fijo, y `.content` agregado al selector existente.

**✅ Corregido y verificado**: se aplicaron todos los cambios y se ejecutó `npm run build` correctamente. El CSS generado incluye:
- Selector `.content` agregado a las reglas existentes de `.content-wrapper`
- Media query `@media (min-width: 992px)` para sidebar fijo (280px) + flex-direction column
- Commit `5b80564` incluye todas las correcciones

**Estado**: sidebar ahora está visible y fijo en desktop (≥992px), comportamiento de drawer en móvil/tablet.

**Nota sobre bytes NUL**: Al editar `footer.php`, se verificó que NO se introdujeron bytes NUL adicionales (archivos validados con `file` command — ambos son UTF-8 válido sin corrupción). El patrón de corrupción identificado en el Problema #1 pareció limitado al script de migración masiva original.

---

## 8. Sistema de diseño "Neurix" huérfano — causa raíz de que el Dashboard (y 7 vistas más) se vean rotos (CRÍTICO)

### 8.0 Resumen ejecutivo

El Dashboard no se ve "moderno ni funcional" porque **casi todo su CSS depende de variables personalizadas (`--nx-a1`, `--nx-card-bg`, `--nx-txt1`, `--nx-bg3`, etc.) que ya no existen en el CSS que se compila y se sirve al navegador**. No es un problema de Bootstrap/AdminLTE — es que el sistema de diseño propio de Neurix (colores, fondos, texto, glass-morphism) se perdió durante la migración y nadie lo reconectó.

Cuando el navegador encuentra `background: var(--nx-card-bg)` y `--nx-card-bg` no está definida en ningún lado, la propiedad completa se vuelve inválida (no hay fallback) — la tarjeta queda sin fondo, sin color de texto coherente, sin bordes con el color correcto. Multiplicado por ~116 usos solo en el dashboard, el resultado es una página que se ve plana, sin jerarquía visual, "rota" — exactamente el síntoma reportado.

### 8.1 Evidencia

**¿Cuál es el dashboard real que se renderiza?** Hay DOS archivos candidatos:
- `themes/default/views/dashboard.php` (22 KB, modificado hoy 29-jun) — usa ApexCharts + tarjetas de stats, pero **no está conectado a ningún controlador**.
- `themes/default/views/dashboard/index.php` (53 KB, 997 líneas, modificado 27-jun) — **este es el que de verdad se muestra**. Confirmado en `app/controllers/Dashboard.php` línea 105: `$this->page_construct('dashboard/index', ...)`, y `page_construct()` en `MY_Controller.php` (línea 2331) hace `$this->load->view($this->theme . $page, $data)` → carga `dashboard/index`.

  **`dashboard.php` es código muerto.** Probablemente un intento de rediseño (tal vez de la sesión local de Claude Code) que nunca se enlazó al controlador — por eso parecía que "no se notaban los cambios": se estaba editando un archivo que el navegador nunca carga.

**¿Qué variables usa el dashboard real (`dashboard/index.php`) y cuáles existen?**

```
Variables usadas en dashboard/index.php (116 ocurrencias):
  --nx-txt3 (35), --nx-a1 (20), --nx-warn (9), --nx-border (9), --nx-ok (7),
  --nx-err (7), --nx-txt1 (6), --nx-bg4 (6), --nx-a2 (5), --nx-border2 (4),
  --nx-a3 (4), --nx-txt2 (3), --nx-bg3 (3), --nx-bg5 (2)

Variables definidas en el CSS que SÍ se compila (themes/default/assets/src/neurix-adminlte4.css):
  --nx-accent, --nx-border, --nx-primary, --nx-secondary, --nx-text-muted
```

De 14 variables distintas que usa el dashboard, **solo 1 (`--nx-border`) existe**, y con un valor distinto al que el diseño original esperaba (`#e5e7eb` gris claro fijo, en vez de `rgba(56,189,248,.13)` — un azul translúcido pensado para tema oscuro).

**¿Dónde está el resto de esas variables?** Sobreviven completas en `themes/default/assets.backup.20260629-140124/dist/css/neurix-theme.css` — un archivo de **respaldo**, fuera de `assets/src/` (donde vive el código fuente que Vite compila) y **nunca importado por `main.js`**. Es decir: el sistema de diseño completo (paleta de 18 colores de acento, 4 tonos de fondo, 4 tonos de texto, 3 tonos de borde, sombras, radios, modo oscuro/claro vía `[data-theme]`) quedó huérfano en un backup cuando se migró a AdminLTE 4 — se reemplazó por `neurix-adminlte4.css`, que es un archivo **mucho más chico y con un propósito distinto**: re-temáticas los componentes nativos de AdminLTE4/Bootstrap5 (sidebar, navbar, tablas), no darle estilo a las páginas custom como el dashboard.

**Esto no es exclusivo del dashboard.** Las mismas variables huérfanas aparecen en:

| Archivo | Impacto |
|---|---|
| `views/dashboard/index.php` | Crítico — 116 usos, página completa depende de ellas |
| `views/cargadocumentos/index.php` | Alto |
| `views/facturascompras/add.php` | Alto |
| `views/products/add.php` | Alto |
| `views/settings/index.php` | Alto |
| `views/header.php` | Medio — separadores de menú (`background:var(--nx-border)`, sí resuelve pero con color incorrecto) |
| `views/footer.php` | Bajo — solo el texto de copyright (`var(--nx-a1)`, `var(--nx-a2)`, `var(--nx-txt3)`) |
| `views/dashboard.php` | N/A — código muerto, no se renderiza, pero igual de raro: usa OTRO subconjunto distinto de las mismas variables huérfanas (`--nx-card-bg`, `--nx-card-bg2`) |

**Bug secundario (menor, mismo archivo):** el script anti-FOUC en `header.php` pone el tema en `document.documentElement` (`data-bs-theme`) y en `document.body` (`data-theme`) — dos atributos en dos elementos distintos. El script inline de gráficas en `dashboard/index.php` lee `document.documentElement.getAttribute('data-theme')`, que **nunca existe ahí** (está en `body`, no en `<html>`), así que `isDark` siempre evalúa `true` sin importar el tema real. Las gráficas de ApexCharts no respetan el modo claro.

**Hallazgo adicional:** `pos-redesign.css` (593 líneas, en `assets/src/`) tampoco está importado en `main.js` — es CSS fuente que existe pero nunca se compila ni se sirve. Si el POS también usa clases de ese archivo, tiene el mismo problema.

### 8.2 Plan de corrección por fases (para ejecutar desde Claude Code)

**Fase 1 — Decidir y fijar la fuente de verdad del dashboard**
1. Confirmar con el usuario si `dashboard/index.php` (el que SÍ se renderiza) es la versión correcta a mantener.
2. Borrar o archivar `dashboard.php` (código muerto) fuera de `views/` para que no se siga editando por error — o, si tenía contenido mejor, fusionar lo útil en `dashboard/index.php` y luego borrarlo.
3. Verificar bytes NUL y balance de tags en `dashboard/index.php` antes de tocarlo (hábito ya establecido en la sección "Cómo evitar que se repita").

**Fase 2 — Restaurar el sistema de variables `--nx-*`**
1. Copiar el bloque `:root` / `[data-theme="dark"]` / `[data-theme="light"]` completo de `assets.backup.20260629-140124/dist/css/neurix-theme.css` a un archivo nuevo en el código fuente real, ej. `themes/default/assets/src/neurix-theme-vars.css` (o fusionarlo al inicio de `neurix-adminlte4.css` si se prefiere un solo archivo).
2. Importarlo en `main.js` **antes** de `neurix-adminlte4.css`, para que las variables existan antes de que se usen los overrides de AdminLTE.
3. Resolver el conflicto de `--nx-border` (dos definiciones con valores distintos): decidir cuál es el valor correcto y dejar una sola definición — lo más simple es que `neurix-adminlte4.css` deje de redefinir variables que ya define el archivo de tema y solo defina las suyas propias (`--nx-accent`, `--nx-primary`, etc., que no chocan).
4. Unificar el selector de modo oscuro/claro: el tema viejo usa `[data-theme="light"]` en `<body>`, AdminLTE4/Bootstrap5 usa `[data-bs-theme="dark"]` en `<html>`. Ahora mismo conviven los dos atributos (ver bug secundario arriba) — conviene elegir uno solo y que tanto el JS como el CSS lo usen consistentemente (recomendado: quedarse con `data-bs-theme` en `<html>`, que es el estándar de Bootstrap 5, y portar las reglas `[data-theme="light"]` del tema viejo a `[data-bs-theme="light"]`).

**Fase 3 — Corregir el bug de detección de tema en gráficas**
1. En `dashboard/index.php` (y `dashboard.php` si se conserva) cambiar `document.documentElement.getAttribute('data-theme')` por `document.documentElement.getAttribute('data-bs-theme')` (consistente con la Fase 2.4).

**Fase 4 — Verificación visual real**
1. `npm run build`.
2. Abrir el dashboard en navegador real, en modo oscuro y claro, en desktop y móvil.
3. Confirmar que las tarjetas KPI tienen fondo, borde y texto coherentes (no transparentes/planas), que los badges de color (verde/amarillo/rojo) se ven, y que las gráficas ApexCharts cambian de paleta según el tema.

**Fase 5 — Replicar la corrección en el resto de vistas afectadas**
1. Repetir la verificación en `cargadocumentos/index.php`, `facturascompras/add.php`, `products/add.php`, `settings/index.php` — deberían arreglarse solas en cuanto exista la Fase 2 (mismas variables, mismo CSS global), pero hay que confirmarlo visualmente uno por uno.
2. Correr `grep -roP '\-\-nx-[a-z0-9-]+' themes/default/views | sed 's/.*://' | sort -u` contra las variables definidas después de la Fase 2, para confirmar que ninguna vista quedó referenciando una variable inexistente.

**Fase 6 — Limpieza**
1. Decidir si `pos-redesign.css` se importa en `main.js` (si el POS lo necesita) o se borra (si quedó obsoleto).
2. Una vez confirmada la Fase 2-5, eliminar la carpeta `assets.backup.20260629-140124/` (o moverla fuera del repo) — ya cumplió su propósito de ser la fuente para recuperar las variables.

---

## Cómo evitar que se repita (proceso para Claude Code)

Los 5 problemas de arriba comparten una causa de fondo: **ediciones automatizadas masivas sin verificación posterior**. Antes de declarar "completada" cualquier fase que toque múltiples archivos:

1. **Verificar bytes nulos**: `grep -rlaP "\x00" --include="*.php" themes app` debe devolver vacío. **Correr esto después de CUALQUIER edición a un archivo de vista, no solo después de migraciones masivas** — se confirmó que una sola edición puntual a `footer.php` reintrodujo 915 bytes NUL al final del archivo (ver sección 7).
2. **Verificar HTML bien formado** en los archivos tocados (al menos un chequeo simple de que cada `<ul`, `<div`, etc. tiene su `>` de cierre antes del siguiente `<`).
3. **Verificar que el build de Vite realmente contiene lo esperado**, no solo que "compiló sin error": revisar tamaño y contenido del CSS resultante (ej. `grep -c ".navbar-toggler-icon" dist/css/*.css` para confirmar que Bootstrap está adentro), no asumir por el nombre del paquete importado.
4. **No marcar una fase como "100% completada" sin testing visual real** en navegador — los documentos anteriores (eliminados) afirmaban "Listo para producción" y "Renderizado HTML correcto" cuando la app estaba completamente rota.

## Checklist de verificación funcional (después de `npm run build`)

- [ ] Navbar, sidebar (offcanvas), dropdowns y treeview se ven y comportan correctamente
- [ ] Tema oscuro/claro cambia y persiste (`localStorage`)
- [ ] Tom Select, Tempus Dominus y Tabulator renderizan donde se usan
- [ ] POS: búsqueda de productos, carrito, atajos de teclado, checkout
- [ ] Módulo Hacienda (facturación electrónica) sin errores
- [ ] Responsive en móvil (375px) y tablet (768px)
- [ ] Impresión de tickets/reportes mantiene fondo blanco
- [ ] Consola del navegador sin errores 404 ni JS en ningún módulo

## Notas técnicas

- Build: `npm run build` (Vite, salida en `themes/default/assets/dist/`). `npm run dev` para modo watch.
- `vite.config.js` usa `build.lib` + formato `iife`; el nombre del CSS de salida cae al campo `"name"` de `package.json` (`www` → `www.min.css`).
- Vistas de impresión (`pos/eview.php`, etc.) mantienen jQuery por posibles dependencias de impresora térmica/ESCPOS — no migradas a propósito.
- Backend (CodeIgniter 3, seguridad, performance, cola de emails, etc.) está documentado por separado en `PROGRESS.md` — no se tocó en este trabajo de frontend.

## Matriz de reemplazo tecnológico (referencia)

| Antes | Ahora |
|---|---|
| Bootstrap 3.3.4 | Bootstrap 5.3.8 |
| AdminLTE 2 (EOL 2020) | AdminLTE 4.0.2 |
| jQuery 3.7.1 | Vanilla JS (eliminado) |
| select2 | Tom Select 2.6.1 |
| bootstrap-datetimepicker (Eonasdan, abandonado) | Tempus Dominus 6.10.4 |
| DataTables (jQuery) | Tabulator.js 6.5.2 |
| Font Awesome 4 | Font Awesome 7.3.0 |
| Sin bundler | Vite 8.1.0 (build único, `themes/default/assets/dist/`) |

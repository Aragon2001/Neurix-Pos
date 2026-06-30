# Prompt para Claude Code — Ajustes del POS por fases

> Pega este documento completo (o fase por fase) en tu sesión de Claude Code dentro del repo. Cada fase es independiente y verificable por separado. No marques nada como "completado" sin probarlo en el navegador.

## Contexto ya investigado (no volver a auditar esto)

- **Cliente duplicado + actividad económica**: en la vista actual (`themes/default/views/pos/index.php`) ya NO existe el campo `actividad_id` — fue eliminado en la migración. Pero `app/controllers/Pos.php` (líneas ~144 y ~1866) sigue leyendo `$this->input->post('actividad_id')` **sin fallback**, así que `id_actividad` se está guardando en `NULL` en cada venta. Existe ya un setting `default_actividad` (`app/controllers/Settings.php`, UI en `themes/default/views/settings/index.php` líneas ~348-354) pensado exactamente para esto.
- **Tabla de impuestos de Hacienda**: ya existe la tabla `impuestos` (columnas `id_impuesto`, `codigo_impuesto`, `codigo_tarifa`, `tasa_impuesto`, `descripcion_impuesto`), poblada en `app/core/MY_Controller.php` (~líneas 2026-2068) con las tarifas oficiales (13%, 8%, 4%, 2%, 1%, exento, ISC, etc.). Métodos disponibles: `Site_model->getAllImpuestos()`, `getImpuestosByID($id)`.
- **CABYS**: ya existe `app/controllers/Hacienda_proxy.php::cabys()` — proxy en vivo contra `api.hacienda.go.cr/fe/cabys`, con caché en tabla `tec_hacienda_cache`. Ya se consume en `themes/default/views/products/add.php` (autocompletado por código o texto, devuelve código + descripción + % impuesto). `tec_products.cabys` ya existe como columna. No hay catálogo CABYS local completo — todo es consulta en vivo + caché, y así debe seguir.
- **Tipos de documento de cliente**: `tec_customers.cf1` (tipo, 01=Cédula Física, 02=Jurídica, 03=DIMEX, 04=NITE, 05=Pasaporte) y `cf2` (número, UNIQUE). Ya implementado en `themes/default/views/customers/add.php`.
- **Autobúsqueda de cliente por cédula**: ya existe `Hacienda_proxy.php::ae($cedula)` (consulta al padrón de Hacienda, cachea 24h en `tec_hacienda_cache`) y ya se usa en `customers/add.php` (blur del campo número → autocompleta nombre). **Falta conectarlo al modal de alta rápida de cliente dentro del POS** — hoy ese modal no lo usa.
- **Columnas de producto**: `tec_products.code`, `.name`, `.cost`, `.price`, `.category_id`, `.id_tax` (FK a `impuestos.id_impuesto`), `.cabys`.
- **Moneda**: solo existe `Settings->currency_prefix` (un string tipo "CRC"/"USD"), sin tipo de cambio ni doble-display. La función de "ver todo en USD y CRC a la vez" es nueva, no existe infraestructura previa — hay que decidir de dónde sale el tipo de cambio (campo manual en Settings es lo más simple y confiable, ya que Hacienda no exige una fuente específica para esto).
- **Productos que no cargan**: la carga real es vía `pos-core.js` → `fetch(base_url + 'pos/ajaxproducts?category_id=...')` contra `Pos.php::ajaxproducts()` (línea ~1692), que sí existe y genera botones `.product`. No se encontró un bug obvio de selector roto. Hipótesis más probable: el fallo está relacionado con el `actividad_id` NULL de arriba, o algo se rompe en cascada. **Primer paso de esta fase: abrir el POS en el navegador con la consola de Network abierta y confirmar si `pos/ajaxproducts` devuelve 200 con HTML, o un error 500/404** — actuar según lo que se observe ahí, no asumir.

---

## FASE 1 — Actividad económica automática (ya no se selecciona)

1. En `app/controllers/Pos.php`, en las dos líneas donde se lee `actividad_id` del POST (~144 y ~1866), agregar fallback: `$actividad_id = $this->input->post('actividad_id') ?: $this->Settings->default_actividad;`
2. Confirmar que la vista del POS ya no envía ningún campo `actividad_id` (ya parece estar así, pero verificarlo).
3. Probar: crear una venta y confirmar en la base de datos que `id_actividad` ya no queda en `NULL`.

## FASE 2 — Diagnóstico real de "no cargan productos"

1. Abrir `/pos` en navegador real, con DevTools → Network abierto.
2. Ver si la llamada a `pos/ajaxproducts` responde 200, y si el HTML que devuelve realmente contiene los productos.
3. Si responde error, revisar el log de PHP (`app/logs/`) para el stack trace exacto.
4. Corregir la causa real encontrada (no adivinar). Si después de la Fase 1 el problema desaparece, documentarlo como relacionado.

## FASE 3 — Idioma dinámico en "Sales details"

1. Buscar el texto literal "Sales details" (o similar en inglés) en las vistas del POS (`grep -rn "Sales details" themes/default/views/`).
2. Reemplazarlo por `<?= lang('sales_details'); ?>` (o el key que corresponda), agregando la traducción correspondiente en `app/language/spanish/app_lang.php` y `app/language/english/app_lang.php` si no existe ya.
3. Repetir el mismo chequeo para cualquier otro texto fijo en inglés/español dentro de las vistas del POS — todo debe pasar por `lang()`.

## FASE 4 — Flujo de pago y precio por producto

1. El selector de método de pago debe aparecer únicamente al hacer clic en "Pagar" (modal/paso de checkout), no antes, no visible todo el tiempo en la pantalla principal del POS.
2. La opción de "precio normal" vs "precio oferta" debe ser **por producto individual** en el momento de agregarlo/editarlo en el carrito (ej. un toggle o botón en la línea de cada producto del carrito), no un switch global que afecte a todos los productos de la venta.

## FASE 5 — Autofocus permanente en la búsqueda de productos

1. El input de búsqueda de productos del POS debe recuperar el foco automáticamente: al cargar la pantalla, después de agregar un producto, después de cerrar cualquier modal, y tras cualquier acción que pueda robarle el foco — para que un lector de código de barras (que simplemente "escribe" y presiona Enter) siempre funcione sin que el usuario tenga que hacer clic en el campo primero.
2. Verificar que ningún modal ni dropdown deje el foco atrapado después de cerrarse.

## FASE 6 — Alta rápida de cliente con datos de Hacienda

1. En el modal de alta rápida de cliente del POS, agregar el selector de tipo de documento usando los mismos 5 tipos que ya existen en `customers/add.php` (01 Cédula Física, 02 Jurídica, 03 DIMEX, 04 NITE, 05 Pasaporte) — reusar el mismo HTML/lógica, no reinventarlo.
2. Conectar el campo de número de documento a `Hacienda_proxy::ae($cedula)` (mismo endpoint que ya usa `customers/add.php`) para autocompletar el nombre al perder el foco o tras una pausa de tecleo (debounce), igual que ya funciona en esa otra vista.
3. Manejar los mismos casos de error que ya maneja `Hacienda_proxy.php` (404 = no encontrado, 429 = rate limit, etc.) con feedback visual claro al usuario (toast/alerta), sin bloquear que el usuario complete el nombre manualmente si Hacienda no responde.

## FASE 7 — Toggle de impresión automática

1. El ícono de impresora en la parte superior del POS debe ser un switch on/off persistente (guardar en `localStorage` o en una preferencia de usuario en BD) que controle si, al completar una venta, la factura se imprime automáticamente sin pedir confirmación.

## FASE 8 — Ampliar el panel de detalle de productos del carrito

1. El contenedor donde se listan los productos agregados a la venta actual necesita más alto/ancho — actualmente los botones de acción de más abajo (cobrar, etc.) quedan a media pantalla o tapados cuando hay varios productos en el carrito. Ajustar el layout (flex/grid, `max-height` + scroll interno en la lista de productos, en vez de empujar el resto de la pantalla) para que los botones de acción permanezcan siempre visibles y accesibles sin necesidad de scroll de toda la página.

## FASE 9 — Menú de atajos en la parte superior

1. Mover la referencia de atajos de teclado que hoy está fija en la parte inferior de la pantalla del POS a un botón en la barra superior (ej. ícono de teclado) que al hacer clic despliegue un panel/popover con la lista de atajos — en vez de ocupar espacio fijo permanente en la pantalla.

## FASE 10 — Contraste y colores en modo claro

1. Revisar el POS completo con `data-bs-theme="light"` activo y listar todos los elementos donde el texto o los íconos pierdan contraste/legibilidad contra el fondo (común cuando el CSS fue diseñado primero para modo oscuro y el modo claro solo invierte variables sin revisar cada caso).
2. Corregir cada caso encontrado con reglas explícitas `[data-bs-theme="light"] .clase { color: ...; background: ...; }` donde haga falta, en vez de depender únicamente de las variables genéricas.

## FASE 11 — Producto rápido (ad-hoc, no se guarda en inventario)

Nuevo botón "Producto rápido" en el POS, que abre un formulario para agregar una línea a la venta actual **sin tocar la tabla `tec_products`** (es exclusivo de esa factura). Campos y comportamiento:

1. **Nombre** (texto libre, obligatorio).
2. **Código CABYS** (obligatorio — Hacienda exige CABYS por línea en factura electrónica). Botón "Buscar CABYS" que abre una lista/modal usando el mismo endpoint ya existente `Hacienda_proxy::cabys()` (búsqueda por código o por texto/nombre). La lista debe mostrar, por cada resultado: código, descripción, e impuesto/tarifa asociada que devuelva la API. Cada fila tiene un botón "Aplicar código CABYS" que rellena el código (y precarga el impuesto sugerido, editable) en el formulario del producto rápido.
3. **Cantidad** (numérico, obligatorio).
4. **Costo** (numérico). Al escribir el costo, si el precio aún no fue editado manualmente por el usuario, autocompletar el precio con el mismo valor (costo = precio inicial, ej. costo 6,000 → precio 6,000 por defecto, pero editable).
5. **Precio** (numérico, editable independientemente del costo).
6. **Switch "¿Lleva IVA?"**. Si está apagado, no se aplica impuesto a la línea. Si está encendido:
   - Mostrar un selector con la lista de impuestos de la tabla `impuestos` (`Site_model->getAllImpuestos()`) — mismo catálogo que ya usa el alta de productos normal.
   - Al lado, mostrar la tarifa (`tasa_impuesto`) correspondiente al impuesto seleccionado (se actualiza automáticamente al cambiar el selector).
7. **Cálculo automático**: precio con impuesto = precio + (precio × tasa_impuesto / 100). Mostrar ambos valores (precio sin impuesto y precio final con impuesto) en el formulario antes de confirmar, y actualizar en tiempo real al cambiar cantidad/precio/impuesto.
8. Al confirmar, la línea se agrega al carrito de la venta actual igual que cualquier otro producto (cantidad × precio con impuesto, con su código CABYS, nombre e impuesto asociado), pero marcada internamente como "producto ad-hoc" para que el backend de generación de factura electrónica (`Crearxml.php`) la incluya correctamente con su CABYS aunque no exista `product_id` real en `tec_products`. Revisar `app/models/Pos_model.php` (cómo se guardan las líneas de venta) para definir si se necesita una columna nueva (ej. `tec_sales_items.is_adhoc` o similar) o si basta con guardar nombre/cabys/impuesto directamente en la línea sin requerir FK a producto.

## FASE 12 — Doble visualización de moneda + modo 100% USD

1. El total de la venta (y subtotales relevantes) debe mostrarse siempre en colones (₡) y dólares ($) simultáneamente. Como no existe tipo de cambio en el sistema hoy, agregar un campo de tipo de cambio en Settings (`app/controllers/Settings.php` + UI), editable manualmente por el administrador (Hacienda no exige una fuente automática específica para esto, así que un campo manual es válido y simple).
2. Agregar un botón/switch en el POS para elegir el "modo de moneda" de la venta actual: cobrar 100% en dólares (todos los montos y el total se calculan y muestran en USD como moneda principal) o mantener colones como principal (con USD como referencia). Verificar cómo `Settings->currency_prefix` y `Stripe_payments.php` ya manejan moneda para no duplicar lógica — reusar esa infraestructura donde sea posible.

## FASE 13 — Botón de caja (apertura/cierre) + historial de movimientos

1. Agregar un botón en la barra superior del POS para abrir/cerrar caja (revisar si ya existe esta funcionalidad en otra parte del sistema — buscar "open_register"/"close_register" en `Pos.php` y rutas relacionadas, ya que aparecen referencias a `pos/open_register` y `view_close_register` en el menú lateral — si ya existe el flujo, solo hace falta exponer un acceso directo desde la pantalla del POS en vez de tener que ir al menú).
2. Agregar acceso a "Movimientos de caja" / historial desde ese mismo botón o uno adyacente (revisar `reports/registers` si ya cubre esto).

## FASE 14 — Accesos rápidos a historiales

1. Agregar accesos directos en la barra superior del POS hacia: historial de ventas/facturas (`sales`) e historial de proformas (`sales/proforma`) — ya existen esas rutas/controladores, solo falta el acceso rápido visible desde la pantalla del POS.

---

## Orden recomendado de ejecución

Fases 1 y 2 primero (son bugs activos que rompen facturación/ventas). Luego 3, 5, 7, 9, 10, 13, 14 (cambios de UI relativamente aislados y rápidos). Luego 4 y 8 (requieren tocar el flujo de carrito/checkout). Al final 6, 11 y 12 (las más grandes: nueva integración Hacienda en el modal de cliente, producto ad-hoc con CABYS, y doble moneda). Probar visualmente en navegador después de cada fase — no encadenar varias fases sin verificar la anterior.

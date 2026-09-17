/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

/* ═══════════════════════════════════════════════════════════════════════════
   NxCompra — ventana de un documento recibido de un proveedor.

   Reusa la hoja de NxDoc (.nxd-*) y agrega la suya (.nxc-*) para gestionarlo:
   registrarlo como compra o como gasto, relacionar cada línea con un producto
   y responder a Hacienda.

       NxCompra.abrir(idDocumento, { alCambiar: () => tabla.reload() })

   Todo sale de cargadocumentos/documento/<id>. El servidor propone destino,
   producto y condición del IVA y vuelve a validar todo al confirmar: la
   pantalla solo recoge decisiones.
   ═══════════════════════════════════════════════════════════════════════════ */

const BASE = () => window.base_url || ''

/* Rótulos: la vista los publica en window._nxcLang; acá vive el respaldo. */
const RESPALDO = {
  cargando: 'Cargando…',
  cerrar: 'Cerrar',
  error_carga: 'No se pudo cargar el documento.',
  tab_documento: 'Documento',
  tab_lineas: 'Líneas',
  tab_gestion: 'Gestionar',
  tab_xml: 'XML recibido',
  tab_receptor: 'Mensaje receptor',
  tab_respuesta: 'Respuesta de Hacienda',
  emisor: 'Proveedor',
  comprobante: 'Comprobante',
  totales: 'Totales',
  consecutivo: 'Consecutivo',
  clave: 'Clave',
  fecha: 'Fecha de emisión',
  moneda: 'Moneda',
  identificacion: 'Identificación',
  correo: 'Correo',
  telefono: 'Teléfono',
  gravado: 'Gravado',
  exento: 'Exento',
  impuesto: 'Impuesto',
  total: 'Total',
  venta_neta: 'Venta neta',
  codigo: 'Código',
  descripcion: 'Descripción',
  cantidad: 'Cant.',
  precio: 'Precio',
  descuento: 'Desc.',
  tarifa: 'Tarifa',
  subtotal: 'Subtotal',
  sin_lineas: 'El comprobante no trae líneas de detalle.',
  sin_xml: 'No hay XML guardado.',
  copiar: 'Copiar',
  copiado: 'Copiado',
  descargar_xml: 'Descargar XML',
  buscar_xml: 'Buscar en el XML…',
  // Gestión
  proveedor: 'Proveedor',
  producto: 'Producto',
  razon_social: 'Razón social',
  alias: 'Nombre comercial',
  alias_ph: 'Cómo lo conocen en la tienda',
  alias_ayuda: 'Se muestra en compras, gastos e informes en lugar de la razón social.',
  registrar_como: 'Registrar como',
  como_compra: 'Compra de mercadería',
  como_compra_ayuda: 'Va al listado de Compras y suma existencias.',
  como_gasto: 'Gasto',
  como_gasto_ayuda: 'Va al listado de Gastos. No toca inventario.',
  categoria_gasto: 'Categoría del gasto',
  seleccione: 'Seleccione…',
  lineas_titulo: 'Líneas del documento',
  destino_inventario: 'Inventario',
  destino_gasto: 'Gasto',
  destino_activo: 'Activo',
  destino_ignorar: 'No registrar',
  destino_elegir: 'Elegir destino…',
  activo_ayuda: 'Se registra como gasto en «Activos y equipo».',
  ignorar_ayuda: 'Esta línea no se registra en ningún lado.',
  buscar_producto: 'Buscar producto por nombre o código…',
  sin_resultados: 'Ningún producto coincide.',
  crear_producto: 'Crear producto',
  cambiar: 'Cambiar',
  conf_auto_codigo: 'Aprendido',
  conf_auto_descripcion: 'Aprendido',
  conf_auto_barras: 'Código de barras',
  conf_sugerida: 'Sugerido: revisar',
  conf_guardada: 'Guardado',
  conf_nueva: 'Nuevo',
  factor: 'Unidades por %s',
  factor_ayuda: 'Si el proveedor vende por caja y usted por unidad, indique cuántas trae.',
  entran: 'Entran %s unidades · costo %s c/u',
  actualizar_precio: 'Actualizar precio de venta',
  precio_actual: 'Actual %s',
  precio_sugerido: 'Sugerido con el margen del producto',
  nuevo_nombre: 'Nombre',
  nuevo_codigo: 'Código',
  nuevo_categoria: 'Categoría',
  nuevo_tarifa: 'Tarifa de IVA',
  nuevo_cabys: 'CABYS',
  nuevo_precio: 'Precio de venta',
  guardar_producto: 'Guardar producto',
  cancelar: 'Cancelar',
  respuesta_hacienda: 'Respuesta a Hacienda',
  msg_aceptar: 'Aceptar',
  msg_parcial: 'Aceptar parcialmente',
  msg_rechazar: 'Rechazar',
  rechazo_ayuda: 'Un documento rechazado no se registra en compras, gastos ni inventario.',
  condicion_impuesto: 'Condición del IVA',
  impuesto_acreditar: 'IVA a acreditar',
  gasto_aplicable: 'Gasto aplicable',
  sin_impuesto: 'El documento no trae impuesto: no hay condición que declarar.',
  detalle_mensaje: 'Motivo',
  detalle_ph: 'Obligatorio al rechazar o aceptar en parte (5 a 160 caracteres)',
  ya_respondido: 'La respuesta a Hacienda ya se envió. Solo falta registrarlo.',
  confirmar_aceptar: 'Registrar y aceptar',
  confirmar_parcial: 'Registrar y aceptar en parte',
  confirmar_rechazar: 'Rechazar documento',
  confirmar_registrar: 'Registrar',
  confirmando: 'Procesando…',
  reenviar: 'Reenviar a Hacienda',
  resumen: '%s a inventario · %s a gasto · %s sin registrar',
  pendientes: '%s por resolver',
  falta_destino: 'Elija a dónde va la línea.',
  falta_producto: 'Relacione la línea con un producto.',
  falta_categoria: 'Elija la categoría del gasto.',
  hay_pendientes: 'Hay líneas sin resolver. Revise las marcadas.',
  hecho_registrado: 'Documento registrado.',
  hecho_compra: 'Compra #%s creada.',
  hecho_gastos: '%s gasto(s) registrado(s).',
  hecho_rechazado: 'Documento rechazado.',
  envio_confirmado: 'Hacienda respondió: %s.',
  envio_pendiente: 'La respuesta quedó firmada; Hacienda todavía no confirma (%s). Puede reenviarla desde aquí.',
  gestionado_titulo: 'Documento gestionado',
  gestionado_por: 'Registrado por %s el %s.',
  ver_compra: 'Ver compra',
  ver_gastos: 'Ver gastos',
  nota_credito_ayuda: 'Es una nota de crédito: lo que se registre resta existencias o gasto.',
}

function t (clave) {
  const L = window._nxcLang || {}
  return L[clave] != null && L[clave] !== '' ? L[clave] : (RESPALDO[clave] != null ? RESPALDO[clave] : clave)
}

function tf (clave, ...args) {
  let i = 0
  return String(t(clave)).replace(/%s/g, () => (args[i++] != null ? args[i - 1] : ''))
}

/* ── Formato ── */
const esc = s => String(s == null ? '' : s)
  .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;').replace(/'/g, '&#39;')

const money = n => (window.NxTable ? window.NxTable.money(n) : (parseFloat(n) || 0).toFixed(2))
const num = n => (parseFloat(n) || 0).toLocaleString('es-CR', { maximumFractionDigits: 4 })
const f = v => { const x = parseFloat(v); return Number.isFinite(x) ? x : 0 }
const r2 = v => Math.round(v * 100) / 100

const TONOS = {
  ok: 'var(--nx-ok)', info: 'var(--nx-info)', warn: 'var(--nx-warn)',
  err: 'var(--nx-err)', muted: 'var(--nx-txt3)', violet: 'var(--nx-violet)',
  orange: 'var(--nx-orange)',
}
const tono = k => TONOS[k] || TONOS.muted

function badge (texto, k) {
  return window.NxTable ? window.NxTable.badge(texto, k) : esc(texto)
}

/* ── Iconos (Tabler, trazo) ── */
const ico = (d, tam = 16) =>
  `<svg width="${tam}" height="${tam}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`

const I = {
  doc: '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>',
  code: '<path d="M7 8l-4 4l4 4"/><path d="M17 8l4 4l-4 4"/><path d="M14 4l-4 16"/>',
  check: '<path d="M5 12l5 5l10 -10"/>',
  lista: '<path d="M9 6l11 0"/><path d="M9 12l11 0"/><path d="M9 18l11 0"/><path d="M5 6l0 .01"/><path d="M5 12l0 .01"/><path d="M5 18l0 .01"/>',
  tienda: '<path d="M3 21l18 0"/><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/><path d="M5 21l0 -10.15"/><path d="M19 21l0 -10.15"/>',
  suma: '<path d="M5 4l14 0l-7 8l7 8l-14 0"/>',
  copiar: '<path d="M8 8m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"/><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2"/>',
  descarga: '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 11l5 5l5 -5"/><path d="M12 4l0 12"/>',
  gira: '<path d="M12 3a9 9 0 1 0 9 9"/>',
  alerta: '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M12 3l9 16h-18z"/>',
  info: '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01"/><path d="M11 12h1v4h1"/>',
  vacio: '<path d="M3 3l18 18"/><circle cx="12" cy="12" r="9"/>',
  lupa: '<circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/>',
  send: '<path d="M10 14l11 -11"/><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1z"/>',
  caja: '<path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12l0 9"/><path d="M12 12l-8 -4.5"/>',
  recibo: '<path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/><path d="M9 7h6"/><path d="M9 11h6"/>',
  mas: '<path d="M12 5l0 14"/><path d="M5 12l14 0"/>',
  engrane: '<path d="M10.3 4.3c.4 -1.8 3 -1.8 3.4 0a1.7 1.7 0 0 0 2.6 1.1c1.5 -.9 3.3 .8 2.4 2.4a1.7 1.7 0 0 0 1 2.5c1.8 .4 1.8 3 0 3.4a1.7 1.7 0 0 0 -1 2.6c.9 1.5 -.9 3.3 -2.4 2.4a1.7 1.7 0 0 0 -2.6 1c-.4 1.8 -3 1.8 -3.4 0a1.7 1.7 0 0 0 -2.5 -1c-1.6 .9 -3.3 -.9 -2.4 -2.4a1.7 1.7 0 0 0 -1.1 -2.6c-1.8 -.4 -1.8 -3 0 -3.4a1.7 1.7 0 0 0 1.1 -2.5c-.9 -1.6 .8 -3.3 2.4 -2.4c1 .6 2.3 .1 2.5 -1.1z"/><circle cx="12" cy="12" r="3"/>',
}

/* ═══════════════════════════ ESTADO DEL COMPONENTE ═══════════════════════════ */

let capa = null
let refs = {}
let vista = { id: null, datos: null, tab: 'documento', xml: {}, alCambiar: null, ocupado: false, g: null, aviso: null }

/* ═══════════════════════════ ARMADO DE LA VENTANA ═══════════════════════════ */

function montar () {
  if (capa) return

  capa = document.createElement('div')
  capa.className = 'nxd-capa'
  capa.setAttribute('role', 'dialog')
  capa.setAttribute('aria-modal', 'true')
  capa.innerHTML = `
    <div class="nxd-caja">
      <div class="nxd-cab">
        <div class="nxd-cab-icono">${ico(I.doc, 21)}</div>
        <div class="nxd-cab-txt">
          <div class="nxd-cab-tit" data-r="titulo">${esc(t('cargando'))}</div>
          <div class="nxd-cab-sub" data-r="sub"></div>
        </div>
        <button type="button" class="nxd-x" data-r="cerrar" aria-label="${esc(t('cerrar'))}">&times;</button>
      </div>
      <div class="nxd-tabs" data-r="tabs"></div>
      <div class="nxd-cuerpo" data-r="cuerpo"></div>
      <div class="nxd-pie" data-r="pie"></div>
    </div>`

  document.body.appendChild(capa)

  refs = {
    titulo: capa.querySelector('[data-r="titulo"]'),
    sub: capa.querySelector('[data-r="sub"]'),
    tabs: capa.querySelector('[data-r="tabs"]'),
    cuerpo: capa.querySelector('[data-r="cuerpo"]'),
    pie: capa.querySelector('[data-r="pie"]'),
  }

  capa.querySelector('[data-r="cerrar"]').addEventListener('click', cerrar)
  capa.addEventListener('mousedown', e => { if (e.target === capa) cerrar() })
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && capa.classList.contains('abierta')) cerrar()
  })

  // Delegados una sola vez: la pestaña Gestionar se repinta por partes.
  refs.cuerpo.addEventListener('change', e => { if (vista.tab === 'gestion') alCambiarCampo(e) })
  refs.cuerpo.addEventListener('input', e => { if (vista.tab === 'gestion') alEscribir(e) })
  refs.cuerpo.addEventListener('click', e => { if (vista.tab === 'gestion') alPulsar(e) })
}

function abrir (id, opciones) {
  montar()
  vista = { id, datos: null, tab: null, xml: {}, alCambiar: (opciones || {}).alCambiar, ocupado: false, g: null, aviso: null }

  capa.classList.add('abierta')
  document.body.classList.add('nxd-bloqueado')

  refs.titulo.textContent = t('cargando')
  refs.sub.textContent = '#' + id
  refs.tabs.innerHTML = ''
  refs.pie.innerHTML = ''
  refs.cuerpo.innerHTML = `<div class="nxd-cargando">${ico(I.gira, 18)} ${esc(t('cargando'))}</div>`

  cargar()
}

function cerrar () {
  if (!capa) return
  capa.classList.remove('abierta')
  document.body.classList.remove('nxd-bloqueado')
}

function cargar () {
  fetch(`${BASE()}cargadocumentos/documento/${encodeURIComponent(vista.id)}`, {
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json().then(j => ({ ok: r.ok, j })))
    .then(({ ok, j }) => {
      if (!ok || j.error) throw new Error(j.error || t('error_carga'))
      vista.datos = j
      iniciarGestion()
      if (!vista.tab) vista.tab = j.gestion.puede ? 'gestion' : 'documento'
      pintar()
    })
    .catch(err => {
      refs.cuerpo.innerHTML = `<div class="nxd-vacio">${ico(I.alerta, 34)}${esc(err.message || t('error_carga'))}</div>`
    })
}

/* ═══════════════════════════════ PINTADO ═══════════════════════════════ */

function pintar () {
  const d = vista.datos
  const est = d.aceptacion.estado

  refs.titulo.innerHTML = esc(d.doc.tipo || t('tab_documento')) + ' ' + badge(est.etiqueta, est.tono)

  const alias = d.gestion.proveedor.alias
  const partes = []
  if (d.doc.consecutivo) partes.push(`<span class="nxt-code">${esc(d.doc.consecutivo)}</span>`)
  partes.push(`<span>${esc(alias || d.emisor.nombre)}</span>`)
  partes.push(`<span>${esc(d.doc.fecha)}</span>`)
  partes.push(`<strong style="color:var(--nx-txt1)">${money(d.totales.total)}</strong>`)
  refs.sub.innerHTML = partes.join('<span style="opacity:.4">·</span>')

  pintarTabs()
  irA(vista.tab)
}

function pintarTabs () {
  const d = vista.datos

  const defs = [
    { k: 'gestion', txt: t('tab_gestion'), ico: I.engrane, pin: d.gestion.puede ? (conteo().pendientes || null) : null },
    { k: 'documento', txt: t('tab_documento'), ico: I.doc },
    { k: 'lineas', txt: t('tab_lineas'), ico: I.lista, pin: d.lineas.length || null },
    { k: 'xml', txt: t('tab_xml'), ico: I.code, off: !d.xml.compra },
    { k: 'receptor', txt: t('tab_receptor'), ico: I.send, off: !d.xml.receptor },
    { k: 'respuesta', txt: t('tab_respuesta'), ico: I.check, off: !d.xml.respuesta },
  ]

  refs.tabs.innerHTML = defs.map(x =>
    `<button type="button" class="nxd-tab${x.k === vista.tab ? ' activa' : ''}" data-tab="${x.k}"${x.off ? ' disabled' : ''}>` +
    ico(x.ico, 15) + esc(x.txt) +
    (x.pin ? `<span class="nxd-tab-pin" data-r="pin-${x.k}">${x.pin}</span>` : '') +
    '</button>').join('')

  refs.tabs.querySelectorAll('.nxd-tab').forEach(b => {
    b.addEventListener('click', () => irA(b.dataset.tab))
  })
}

function irA (tab) {
  vista.tab = tab
  refs.tabs.querySelectorAll('.nxd-tab').forEach(b => b.classList.toggle('activa', b.dataset.tab === tab))
  refs.cuerpo.scrollTop = 0

  pintarPie()
  if (tab === 'gestion') return panelGestion()
  if (tab === 'documento') return panelDocumento()
  if (tab === 'lineas') return panelLineas()
  return panelXml(tab === 'xml' ? 'compra' : tab)
}

/* ── Pestaña: documento ── */
function panelDocumento () {
  const d = vista.datos

  refs.cuerpo.innerHTML = `
    <div class="nxd-panel activo">
      <div class="nxd-grid">
        ${tarjetaEmisor(d.emisor)}
        ${tarjetaComprobante(d.doc)}
        ${tarjetaTotales(d.totales)}
      </div>
    </div>`
  activarCopias()
}

function tarjetaEmisor (e) {
  const filas = [
    e.cedula ? [t('identificacion'), e.cedula, true] : null,
    e.correo ? [t('correo'), e.correo] : null,
    e.telefono ? [t('telefono'), e.telefono] : null,
  ].filter(Boolean)
  const alias = vista.datos.gestion.proveedor.alias

  return `<div class="nxd-tarjeta">
    <div class="nxd-tarjeta-tit">${ico(I.tienda, 14)}${esc(t('emisor'))}</div>
    <div class="nxd-nombre">${esc(alias || e.nombre)}</div>
    ${alias ? `<div class="nxc-sub">${esc(e.nombre)}</div>` : ''}
    ${filas.map(x => `<dl class="nxd-kv"><dt>${esc(x[0])}</dt><dd${x[2] ? ' class="mono"' : ''}>${esc(x[1])}</dd></dl>`).join('')}
  </div>`
}

function tarjetaComprobante (c) {
  const moneda = c.moneda + (c.tipo_cambio && c.tipo_cambio !== 1 ? ' · ' + num(c.tipo_cambio) : '')

  return `<div class="nxd-tarjeta">
    <div class="nxd-tarjeta-tit">${ico(I.doc, 14)}${esc(t('comprobante'))}</div>
    <dl class="nxd-kv"><dt>${esc(t('consecutivo'))}</dt><dd class="mono">${esc(c.consecutivo || '—')}</dd></dl>
    <dl class="nxd-kv"><dt>${esc(t('fecha'))}</dt><dd>${esc(c.fecha || '—')}</dd></dl>
    <dl class="nxd-kv"><dt>${esc(t('moneda'))}</dt><dd class="mono">${esc(moneda)}</dd></dl>
    ${c.clave ? `<div class="nxd-clave">
      <code>${esc(c.clave)}</code>
      <button type="button" class="nxd-mini js-copiar" data-txt="${esc(c.clave)}" title="${esc(t('copiar'))}">${ico(I.copiar, 14)}</button>
    </div>` : ''}
  </div>`
}

function tarjetaTotales (v) {
  const filas = [
    [t('gravado'), v.gravado],
    [t('exento'), v.exento],
    [t('venta_neta'), v.venta_neta],
    [t('impuesto'), v.impuesto],
  ]

  return `<div class="nxd-tarjeta">
    <div class="nxd-tarjeta-tit">${ico(I.suma, 14)}${esc(t('totales'))}</div>
    ${filas.map(x => `<dl class="nxd-kv"><dt>${esc(x[0])}</dt><dd class="mono">${money(x[1])}</dd></dl>`).join('')}
    <dl class="nxd-kv"><dt><b>${esc(t('total'))}</b></dt>
      <dd class="mono"><b style="color:var(--nx-txt1);font-size:15px">${money(v.total)}</b></dd></dl>
  </div>`
}

/* ── Pestaña: líneas ── */
function panelLineas () {
  const lineas = vista.datos.lineas

  if (!lineas.length) {
    refs.cuerpo.innerHTML = `<div class="nxd-panel activo"><div class="nxd-bloque">
      <div class="nxd-vacio">${ico(I.vacio, 34)}${esc(t('sin_lineas'))}</div></div></div>`
    return
  }

  const filas = lineas.map(l => `<tr>
    <td class="mono">${esc(l.codigo || '—')}</td>
    <td><div class="nxd-linea-nom">${esc(l.nombre)}</div>${l.cabys ? `<div class="nxc-sub mono">CABYS ${esc(l.cabys)}</div>` : ''}</td>
    <td class="num mono">${num(l.cantidad)}${l.unidad ? ' <span style="opacity:.6">' + esc(l.unidad) + '</span>' : ''}</td>
    <td class="num mono">${money(l.precio)}</td>
    <td class="num mono">${l.descuento ? money(l.descuento) : '—'}</td>
    <td class="num mono">${l.tarifa ? num(l.tarifa) + '%' : '—'}</td>
    <td class="num mono">${money(l.impuesto)}</td>
    <td class="num mono"><b>${money(l.total)}</b></td>
  </tr>`).join('')

  refs.cuerpo.innerHTML = `
    <div class="nxd-panel activo">
      <div class="nxd-bloque">
        <div class="nxd-bloque-cab">${ico(I.lista, 14)}${esc(t('tab_lineas'))}
          <span class="nxd-etq">${lineas.length}</span></div>
        <div class="nxd-scroll-x">
          <table class="nxd-tabla">
            <thead><tr>
              <th>${esc(t('codigo'))}</th><th>${esc(t('descripcion'))}</th>
              <th class="num">${esc(t('cantidad'))}</th><th class="num">${esc(t('precio'))}</th>
              <th class="num">${esc(t('descuento'))}</th><th class="num">${esc(t('tarifa'))}</th>
              <th class="num">${esc(t('impuesto'))}</th><th class="num">${esc(t('total'))}</th>
            </tr></thead>
            <tbody>${filas}</tbody>
          </table>
        </div>
      </div>
    </div>`
}

/* ═══════════════════════════════ GESTIÓN ═══════════════════════════════ */

function iniciarGestion () {
  const G = vista.datos.gestion
  const lineas = {}
  G.lineas.forEach(l => {
    lineas[l.id] = {
      destino: l.destino || '',
      producto: l.producto,
      factor: l.relacion.factor || 1,
      categoria: l.categoria_gasto_id || 0,
      confianza: l.relacion.confianza,
      origen: l.relacion.origen,
      aplicarPrecio: false,
      precio: '',
      precioTocado: false,
      panel: l.producto ? null : 'buscar',
      resultados: null,
      termino: '',
    }
  })
  vista.g = {
    como: G.registrar_como || 'compra',
    categoria: G.categoria_gasto_id || 0,
    alias: G.proveedor.alias || '',
    mensaje: '1',
    condicion: G.condicion || '',
    acreditar: '',
    detalle: '',
    lineas,
    marcar: false,
  }
}

const lineaDatos = id => vista.datos.gestion.lineas.find(l => String(l.id) === String(id))

/** Destino efectivo: registrado como gasto, lo que no se ignora ni es activo va a gasto. */
function destinoDe (s) {
  if (vista.g.como === 'gasto') return ['ignorar', 'activo'].includes(s.destino) ? s.destino : 'gasto'
  return s.destino
}

function categoriaDe (s) {
  return vista.g.como === 'gasto' ? vista.g.categoria : s.categoria
}

function faltaEn (s) {
  const d = destinoDe(s)
  if (!d) return 'falta_destino'
  if (d === 'inventario' && !s.producto) return 'falta_producto'
  if (d === 'gasto' && !categoriaDe(s)) return 'falta_categoria'
  return ''
}

function conteo () {
  const c = { inventario: 0, gasto: 0, ignorar: 0, pendientes: 0 }
  if (!vista.g) return c
  Object.values(vista.g.lineas).forEach(s => {
    const d = destinoDe(s)
    if (faltaEn(s)) c.pendientes++
    if (d === 'inventario') c.inventario++
    else if (d === 'gasto' || d === 'activo') c.gasto++
    else if (d === 'ignorar') c.ignorar++
  })
  return c
}

/** Parte del IVA que no se acredita y por eso entra al costo (misma regla del servidor). */
function noAcreditable () {
  const G = vista.datos.gestion
  const c = vista.g.condicion
  if (c === '04') return 1
  if (c === '02' && G.impuesto > 0) return Math.min(Math.max(1 - f(vista.g.acreditar) / G.impuesto, 0), 1)
  return 0
}

function costoUnitario (l, s) {
  const base = l.costo_sin_iva + (l.costo_con_iva - l.costo_sin_iva) * noAcreditable()
  return base / Math.max(f(s.factor) || 1, 0.0001)
}

function precioSugerido (costo, p) {
  if (!p || costo <= 0) return 0
  const m = f(p.margen)
  if (m > 0) {
    let x = costo * (1 + m / 100)
    if (String(p.tax_method) === '0') x *= 1 + f(p.tax) / 100
    return r2(x)
  }
  if (f(p.cost) > 0 && f(p.price) > 0) return r2(costo * f(p.price) / f(p.cost))
  return 0
}

function montos () {
  const G = vista.datos.gestion
  const g = vista.g
  if (g.mensaje === '3' || !g.condicion || G.impuesto <= 0) return null
  if (g.condicion === '02') {
    const a = Math.min(Math.max(f(g.acreditar), 0), G.impuesto)
    return { acreditar: a, gasto: G.total - a }
  }
  return G.montos[g.condicion] || null
}

function panelGestion () {
  const d = vista.datos
  const G = d.gestion
  const g = vista.g

  if (!G.puede) {
    refs.cuerpo.innerHTML = `<div class="nxd-panel activo">${bandaGestionado()}</div>`
    return
  }

  const rechazo = G.fiscal_pendiente && g.mensaje === '3'
  const ops = (lista, sel, vacio) => (vacio ? `<option value="">${esc(vacio)}</option>` : '') +
    lista.map(o => `<option value="${esc(o.id)}"${String(o.id) === String(sel) ? ' selected' : ''}>${esc(o.nombre)}</option>`).join('')

  refs.cuerpo.innerHTML = `
    <div class="nxd-panel activo nxc">
      <div data-r="aviso">${vista.aviso || ''}</div>
      ${!G.fiscal_pendiente ? `<div class="nxd-banda" style="--banda-c:${tono('info')}">${ico(I.info, 18)}<div><div class="nxd-banda-txt">${esc(t('ya_respondido'))}</div></div></div>` : ''}
      ${G.es_nota_credito ? `<div class="nxd-banda" style="--banda-c:${tono('warn')}">${ico(I.alerta, 18)}<div><div class="nxd-banda-txt">${esc(t('nota_credito_ayuda'))}</div></div></div>` : ''}

      <div class="nxc-dos">
        <div class="nxd-bloque">
          <div class="nxd-bloque-cab"><span>${ico(I.tienda, 14)} ${esc(t('proveedor'))}</span></div>
          <div class="nxc-pad">
            <div class="nxd-campo">
              <label>${esc(t('alias'))}</label>
              <input type="text" maxlength="150" data-g="alias" value="${esc(g.alias)}" placeholder="${esc(t('alias_ph'))}">
              <div class="nxd-ayuda">${esc(t('alias_ayuda'))}</div>
            </div>
            <dl class="nxd-kv"><dt>${esc(t('razon_social'))}</dt><dd>${esc(G.proveedor.nombre)}</dd></dl>
            <dl class="nxd-kv"><dt>${esc(t('identificacion'))}</dt><dd class="mono">${esc(G.proveedor.cedula)}</dd></dl>
          </div>
        </div>

        ${G.fiscal_pendiente ? bloqueFiscal() : ''}
      </div>

      <div data-r="registro"${rechazo ? ' hidden' : ''}>
        <div class="nxd-bloque">
          <div class="nxd-bloque-cab"><span>${ico(I.recibo, 14)} ${esc(t('registrar_como'))}</span></div>
          <div class="nxc-pad">
            <div class="nxc-seg" role="radiogroup">
              ${opcionComo('compra', I.caja, t('como_compra'), t('como_compra_ayuda'))}
              ${opcionComo('gasto', I.recibo, t('como_gasto'), t('como_gasto_ayuda'))}
            </div>
            <div class="nxd-campo nxc-cat-doc" data-r="cat-doc"${g.como === 'gasto' ? '' : ' hidden'}>
              <label>${esc(t('categoria_gasto'))}</label>
              <select data-g="categoria">${ops(G.opciones.categorias_gasto.filter(c => c.clave !== 'activos'), g.categoria, t('seleccione'))}</select>
            </div>
          </div>
        </div>

        <div class="nxd-bloque">
          <div class="nxd-bloque-cab">
            <span>${ico(I.lista, 14)} ${esc(t('lineas_titulo'))}</span>
            <span class="nxc-conteo" data-r="conteo"></span>
          </div>
          <div data-r="lineas">${G.lineas.map(l => filaLinea(l)).join('')}</div>
        </div>
      </div>
    </div>`

  pintarConteo()
}

function opcionComo (valor, icono, titulo, ayuda) {
  const activa = vista.g.como === valor
  return `<button type="button" class="nxc-seg-op${activa ? ' activa' : ''}" data-a="como" data-v="${valor}" role="radio" aria-checked="${activa}">
    ${ico(icono, 20)}<span><b>${esc(titulo)}</b><small>${esc(ayuda)}</small></span></button>`
}

function bloqueFiscal () {
  const G = vista.datos.gestion
  const g = vista.g
  const msg = [['1', t('msg_aceptar'), 'ok'], ['2', t('msg_parcial'), 'warn'], ['3', t('msg_rechazar'), 'err']]
  const conds = G.opciones.condiciones

  return `<div class="nxd-bloque">
    <div class="nxd-bloque-cab"><span>${ico(I.send, 14)} ${esc(t('respuesta_hacienda'))}</span></div>
    <div class="nxc-pad">
      <div class="nxc-msg">
        ${msg.map(m => `<button type="button" class="nxc-msg-op${g.mensaje === m[0] ? ' activa' : ''}" style="--op-c:${tono(m[2])}" data-a="mensaje" data-v="${m[0]}">${esc(m[1])}</button>`).join('')}
      </div>
      <div data-r="fiscal-detalle">
        ${g.mensaje === '3'
          ? `<div class="nxd-ayuda nxc-aviso-rechazo">${esc(t('rechazo_ayuda'))}</div>`
          : (G.impuesto > 0
            ? `<div class="nxd-campo">
                <label>${esc(t('condicion_impuesto'))}</label>
                <select data-g="condicion">${Object.keys(conds).map(k => `<option value="${k}"${k === g.condicion ? ' selected' : ''}>${k} · ${esc(conds[k])}</option>`).join('')}</select>
              </div>
              <div class="nxd-campo" data-r="acreditar"${g.condicion === '02' ? '' : ' hidden'}>
                <label>${esc(t('impuesto_acreditar'))}</label>
                <input type="number" step="0.01" min="0" max="${G.impuesto}" data-g="acreditar" value="${esc(g.acreditar)}">
              </div>
              <div class="nxc-montos" data-r="montos">${htmlMontos()}</div>`
            : `<div class="nxd-ayuda">${esc(t('sin_impuesto'))}</div>`)}
      </div>
      <div class="nxd-campo" style="margin-top:12px">
        <label>${esc(t('detalle_mensaje'))} <span class="nxc-contador" data-r="contador">${g.detalle.length}/160</span></label>
        <textarea rows="2" maxlength="160" data-g="detalle" placeholder="${esc(t('detalle_ph'))}">${esc(g.detalle)}</textarea>
      </div>
    </div>
  </div>`
}

function htmlMontos () {
  const m = montos()
  if (!m) return ''
  return `<div><span>${esc(t('impuesto_acreditar'))}</span><b>${money(m.acreditar)}</b></div>
    <div><span>${esc(t('gasto_aplicable'))}</span><b>${money(m.gasto)}</b></div>`
}

/* ── Una línea ── */

function filaLinea (l) {
  const s = vista.g.lineas[l.id]
  const d = destinoDe(s)
  const falta = vista.g.marcar ? faltaEn(s) : ''
  const comoGasto = vista.g.como === 'gasto'

  const opciones = (comoGasto ? ['gasto', 'activo', 'ignorar'] : ['inventario', 'gasto', 'activo', 'ignorar'])
    .map(v => `<option value="${v}"${d === v ? ' selected' : ''}>${esc(t('destino_' + v))}</option>`).join('')

  const meta = [
    '#' + l.numero,
    l.codigo && l.codigo !== '0' ? `${esc(t('codigo'))} <span class="mono">${esc(l.codigo)}</span>` : null,
    l.cabys ? `CABYS <span class="mono">${esc(l.cabys)}</span>` : null,
    `<span class="mono">${num(l.cantidad)}</span> ${esc(l.unidad || '')}`,
  ].filter(Boolean).join(' · ')

  return `<div class="nxc-linea${falta ? ' falta' : ''}" data-l="${l.id}">
    <div class="nxc-linea-info">
      <div class="nxd-linea-nom">${esc(l.nombre)}</div>
      <div class="nxc-sub">${meta}</div>
      <div class="nxc-sub"><span class="mono">${money(l.subtotal)}</span> + ${esc(t('impuesto'))} <span class="mono">${money(l.impuesto)}</span></div>
    </div>
    <div class="nxc-linea-destino">
      <select data-f="destino" class="nxc-sel${d ? '' : ' vacio'}">${d ? '' : `<option value="" selected>${esc(t('destino_elegir'))}</option>`}${opciones}</select>
      ${falta ? `<div class="nxc-falta">${esc(t(falta))}</div>` : ''}
    </div>
    <div class="nxc-linea-det">${detalleLinea(l, s, d)}</div>
  </div>`
}

function detalleLinea (l, s, d) {
  const G = vista.datos.gestion

  if (d === 'gasto') {
    if (vista.g.como === 'gasto') return `<div class="nxc-sub">${esc(t('destino_gasto'))} · <b class="mono">${money(l.total)}</b></div>`
    return `<select data-f="categoria" class="nxc-sel">
      <option value="">${esc(t('categoria_gasto'))}…</option>
      ${G.opciones.categorias_gasto.filter(c => c.clave !== 'activos').map(c => `<option value="${c.id}"${String(c.id) === String(s.categoria) ? ' selected' : ''}>${esc(c.nombre)}</option>`).join('')}
    </select>`
  }
  if (d === 'activo') return `<div class="nxc-sub">${esc(t('activo_ayuda'))}</div>`
  if (d === 'ignorar') return `<div class="nxc-sub">${esc(t('ignorar_ayuda'))}</div>`
  if (d !== 'inventario') return ''

  if (s.panel === 'crear') return formCrear(l, s)
  if (!s.producto) return buscador(l, s)

  const p = s.producto
  const conf = {
    auto: [t('conf_auto_' + (s.origen || 'codigo')), 'ok'],
    sugerida: [t('conf_sugerida'), 'warn'],
    guardada: [t('conf_guardada'), 'info'],
    nueva: [t('conf_nueva'), 'violet'],
    manual: null,
  }[s.confianza]
  const costo = costoUnitario(l, s)
  const unidades = l.cantidad * (f(s.factor) || 1)
  const sugerido = precioSugerido(costo, p)
  if (!s.precioTocado) s.precio = sugerido ? String(sugerido) : ''

  return `<div class="nxc-prod">
      <div class="nxc-prod-nom">
        <b>${esc(p.name)}</b> <span class="nxt-code">${esc(p.code)}</span>
        ${conf ? badge(conf[0], conf[1]) : ''}
      </div>
      <button type="button" class="nxd-btn nxc-btn-sm" data-a="cambiar">${esc(t('cambiar'))}</button>
    </div>
    <div class="nxc-fila">
      <label class="nxc-inline" title="${esc(t('factor_ayuda'))}">
        ${esc(tf('factor', l.unidad || 'unidad'))}
        <input type="number" step="any" min="0.0001" data-f="factor" value="${esc(s.factor)}">
      </label>
      <span class="nxc-sub" data-r="entran">${esc(tf('entran', num(unidades), money(costo)))}</span>
    </div>
    <div class="nxc-fila">
      <label class="nxc-inline nxc-chk">
        <input type="checkbox" data-f="aplicarPrecio"${s.aplicarPrecio ? ' checked' : ''}>
        ${esc(t('actualizar_precio'))}
      </label>
      <input type="number" step="0.01" min="0" class="nxc-precio" data-f="precio" value="${esc(s.precio)}"${s.aplicarPrecio ? '' : ' disabled'} title="${esc(t('precio_sugerido'))}">
      <span class="nxc-sub">${esc(tf('precio_actual', money(p.price)))}</span>
    </div>`
}

function buscador (l, s) {
  const res = s.resultados
  return `<div class="nxc-busca">
      <div class="nxd-busca">${ico(I.lupa, 15)}<input type="search" data-f="buscar" value="${esc(s.termino)}" placeholder="${esc(t('buscar_producto'))}" autocomplete="off"></div>
      <button type="button" class="nxd-btn nxc-btn-sm" data-a="crear">${ico(I.mas, 14)}${esc(t('crear_producto'))}</button>
    </div>
    <div class="nxc-resultados" data-r="resultados">${res ? htmlResultados(res) : ''}</div>`
}

function htmlResultados (res) {
  if (!res.length) return `<div class="nxc-sub nxc-pad-s">${esc(t('sin_resultados'))}</div>`
  return res.map((p, i) => `<button type="button" class="nxc-res" data-a="elegir" data-i="${i}">
    <span><b>${esc(p.name)}</b> <span class="nxt-code">${esc(p.code)}</span></span>
    <span class="mono">${money(p.price)}</span></button>`).join('')
}

function formCrear (l, s) {
  const G = vista.datos.gestion
  const tarifa = (G.opciones.tarifas.find(x => Math.abs(x.tasa - l.tarifa) < 0.001) || G.opciones.tarifas[0] || {}).codigo
  const n = s.nuevo || (s.nuevo = {
    name: l.nombre.slice(0, 150),
    code: l.codigo_barras || '',
    category_id: '',
    codigo_tarifa: tarifa,
    cabys: l.cabys || '',
    price: '',
  })
  const campo = (k, rotulo, extra = '') => `<label class="nxd-campo"><span class="nxc-rot">${esc(rotulo)}</span><input data-n="${k}" value="${esc(n[k])}" ${extra}></label>`

  return `<div class="nxc-crear">
    ${campo('name', t('nuevo_nombre'), 'maxlength="150"')}
    <div class="nxc-tres">
      ${campo('code', t('nuevo_codigo'), 'maxlength="50"')}
      ${campo('cabys', t('nuevo_cabys'), 'maxlength="13" inputmode="numeric"')}
      ${campo('price', t('nuevo_precio'), 'type="number" step="0.01" min="0"')}
    </div>
    <div class="nxd-dos">
      <label class="nxd-campo"><span class="nxc-rot">${esc(t('nuevo_categoria'))}</span>
        <select data-n="category_id"><option value="">${esc(t('seleccione'))}</option>
        ${G.opciones.categorias_producto.map(c => `<option value="${c.id}"${String(c.id) === String(n.category_id) ? ' selected' : ''}>${esc(c.nombre)}</option>`).join('')}</select></label>
      <label class="nxd-campo"><span class="nxc-rot">${esc(t('nuevo_tarifa'))}</span>
        <select data-n="codigo_tarifa">${G.opciones.tarifas.map(x => `<option value="${esc(x.codigo)}"${x.codigo === n.codigo_tarifa ? ' selected' : ''}>${esc(x.nombre)}</option>`).join('')}</select></label>
    </div>
    <div class="nxc-err" data-r="crear-error"></div>
    <div class="nxc-fila">
      <button type="button" class="nxd-btn nxd-btn-pri nxc-btn-sm" data-a="guardar-producto">${esc(t('guardar_producto'))}</button>
      <button type="button" class="nxd-btn nxc-btn-sm" data-a="cancelar-crear">${esc(t('cancelar'))}</button>
    </div>
  </div>`
}

/* ── Repintado parcial ── */

function repintarLinea (id) {
  const el = refs.cuerpo.querySelector(`.nxc-linea[data-l="${id}"]`)
  if (!el) return
  const foco = document.activeElement && el.contains(document.activeElement) ? document.activeElement.dataset.f : null
  el.outerHTML = filaLinea(lineaDatos(id))
  if (foco) {
    const nuevo = refs.cuerpo.querySelector(`.nxc-linea[data-l="${id}"] [data-f="${foco}"]`)
    if (nuevo) { nuevo.focus(); if (nuevo.setSelectionRange && nuevo.type === 'search') nuevo.setSelectionRange(nuevo.value.length, nuevo.value.length) }
  }
  pintarConteo()
}

function repintarLineas () {
  const cont = refs.cuerpo.querySelector('[data-r="lineas"]')
  if (cont) cont.innerHTML = vista.datos.gestion.lineas.map(l => filaLinea(l)).join('')
  pintarConteo()
}

function pintarConteo () {
  const c = conteo()
  const el = refs.cuerpo.querySelector('[data-r="conteo"]')
  if (el) {
    el.innerHTML = esc(tf('resumen', c.inventario, c.gasto, c.ignorar)) +
      (c.pendientes ? ' ' + badge(tf('pendientes', c.pendientes), 'warn') : '')
  }
  const pin = refs.tabs.querySelector('[data-r="pin-gestion"]')
  if (pin) { pin.textContent = c.pendientes; pin.hidden = !c.pendientes }
}

/* ── Eventos ── */

function sDe (e) {
  const fila = e.target.closest('.nxc-linea')
  return fila ? { id: fila.dataset.l, s: vista.g.lineas[fila.dataset.l], fila } : null
}

function alCambiarCampo (e) {
  const g = vista.g
  const el = e.target

  if (el.dataset.g === 'condicion') {
    g.condicion = el.value
    refs.cuerpo.querySelector('[data-r="acreditar"]').hidden = g.condicion !== '02'
    actualizarMontos()
    repintarLineas()
    return
  }
  if (el.dataset.g === 'categoria') {
    g.categoria = parseInt(el.value, 10) || 0
    repintarLineas()
    return
  }

  const x = sDe(e)
  if (!x) return
  if (el.dataset.f === 'destino') {
    x.s.destino = el.value
    repintarLinea(x.id)
  } else if (el.dataset.f === 'categoria') {
    x.s.categoria = parseInt(el.value, 10) || 0
    repintarLinea(x.id)
  } else if (el.dataset.f === 'aplicarPrecio') {
    x.s.aplicarPrecio = el.checked
    repintarLinea(x.id)
  } else if (el.dataset.n) {
    x.s.nuevo[el.dataset.n] = el.value
  }
}

let temporizador = null

function alEscribir (e) {
  const g = vista.g
  const el = e.target

  if (el.dataset.g === 'alias') { g.alias = el.value; return }
  if (el.dataset.g === 'detalle') {
    g.detalle = el.value
    const c = refs.cuerpo.querySelector('[data-r="contador"]')
    if (c) c.textContent = `${el.value.length}/160`
    return
  }
  if (el.dataset.g === 'acreditar') {
    g.acreditar = el.value
    actualizarMontos()
    Object.keys(g.lineas).forEach(id => { if (destinoDe(g.lineas[id]) === 'inventario') actualizarCosto(id) })
    return
  }

  const x = sDe(e)
  if (!x) return
  if (el.dataset.n) { x.s.nuevo[el.dataset.n] = el.value; return }
  if (el.dataset.f === 'factor') { x.s.factor = el.value; actualizarCosto(x.id); return }
  if (el.dataset.f === 'precio') { x.s.precio = el.value; x.s.precioTocado = true; return }
  if (el.dataset.f === 'buscar') {
    x.s.termino = el.value
    clearTimeout(temporizador)
    temporizador = setTimeout(() => buscarProducto(x.id, el.value), 260)
  }
}

/** Costo y precio sugerido sin repintar la fila, para no perder el foco del campo. */
function actualizarCosto (id) {
  const l = lineaDatos(id)
  const s = vista.g.lineas[id]
  const fila = refs.cuerpo.querySelector(`.nxc-linea[data-l="${id}"]`)
  if (!fila || !s.producto) return
  const costo = costoUnitario(l, s)
  const entran = fila.querySelector('[data-r="entran"]')
  if (entran) entran.textContent = tf('entran', num(l.cantidad * (f(s.factor) || 1)), money(costo))
  if (!s.precioTocado) {
    const sug = precioSugerido(costo, s.producto)
    s.precio = sug ? String(sug) : ''
    const inp = fila.querySelector('[data-f="precio"]')
    if (inp) inp.value = s.precio
  }
}

function actualizarMontos () {
  const el = refs.cuerpo.querySelector('[data-r="montos"]')
  if (el) el.innerHTML = htmlMontos()
}

function alPulsar (e) {
  const b = e.target.closest('[data-a]')
  if (!b) return
  const g = vista.g
  const a = b.dataset.a

  if (a === 'como') {
    g.como = b.dataset.v
    refs.cuerpo.querySelectorAll('.nxc-seg-op').forEach(o => {
      o.classList.toggle('activa', o.dataset.v === g.como)
      o.setAttribute('aria-checked', o.dataset.v === g.como)
    })
    refs.cuerpo.querySelector('[data-r="cat-doc"]').hidden = g.como !== 'gasto'
    repintarLineas()
    return
  }
  if (a === 'mensaje') {
    g.mensaje = b.dataset.v
    const scroll = refs.cuerpo.scrollTop
    panelGestion()
    refs.cuerpo.scrollTop = scroll
    pintarPie()
    return
  }

  const x = sDe(e)
  if (!x) return
  if (a === 'cambiar') {
    Object.assign(x.s, { producto: null, confianza: 'ninguna', panel: 'buscar', aplicarPrecio: false, precioTocado: false })
    repintarLinea(x.id)
    const inp = refs.cuerpo.querySelector(`.nxc-linea[data-l="${x.id}"] [data-f="buscar"]`)
    if (inp) inp.focus()
  } else if (a === 'elegir') {
    const p = x.s.resultados[parseInt(b.dataset.i, 10)]
    Object.assign(x.s, { producto: p, confianza: 'manual', panel: null, resultados: null, precioTocado: false })
    repintarLinea(x.id)
  } else if (a === 'crear') {
    x.s.panel = 'crear'
    repintarLinea(x.id)
  } else if (a === 'cancelar-crear') {
    x.s.panel = 'buscar'
    repintarLinea(x.id)
  } else if (a === 'guardar-producto') {
    guardarProducto(x.id, b)
  }
}

function buscarProducto (id, termino) {
  const s = vista.g.lineas[id]
  if (!termino.trim()) {
    s.resultados = null
    const cont = refs.cuerpo.querySelector(`.nxc-linea[data-l="${id}"] [data-r="resultados"]`)
    if (cont) cont.innerHTML = ''
    return
  }
  fetch(`${BASE()}cargadocumentos/buscar_producto?term=${encodeURIComponent(termino)}`, {
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(j => {
      if (s.termino !== termino) return
      s.resultados = j.productos || []
      const cont = refs.cuerpo.querySelector(`.nxc-linea[data-l="${id}"] [data-r="resultados"]`)
      if (cont) cont.innerHTML = htmlResultados(s.resultados)
    })
    .catch(() => {})
}

function guardarProducto (id, boton) {
  const l = lineaDatos(id)
  const s = vista.g.lineas[id]
  const cuerpo = new URLSearchParams()
  Object.keys(s.nuevo).forEach(k => cuerpo.set(k, s.nuevo[k]))
  cuerpo.set('unit', l.unidad || 'Unid')
  cuerpo.set('cost', r2(costoUnitario(l, { factor: 1 })))
  if (window.CSRF_NAME) cuerpo.set(window.CSRF_NAME, window.CSRF_HASH)

  boton.disabled = true
  enviar('cargadocumentos/crear_producto', cuerpo)
    .then(j => {
      Object.assign(s, { producto: j.producto, confianza: 'nueva', panel: null, factor: 1, precioTocado: false })
      repintarLinea(id)
    })
    .catch(err => {
      boton.disabled = false
      const el = refs.cuerpo.querySelector(`.nxc-linea[data-l="${id}"] [data-r="crear-error"]`)
      if (el) el.textContent = err.message
    })
}

/* ═══════════════════════════════ PIE Y ACCIONES ═══════════════════════════════ */

function pintarPie () {
  const d = vista.datos
  const G = d.gestion
  const enGestion = vista.tab === 'gestion' && G.puede
  const est = d.aceptacion.estado.clave
  const reenviable = !G.fiscal_pendiente && d.xml.receptor && ['procesando', 'error', 'recibido', '5'].includes(est) && !!window._nxcAdmin

  let principal = ''
  if (enGestion) {
    const clave = !G.fiscal_pendiente ? 'confirmar_registrar'
      : ({ 1: 'confirmar_aceptar', 2: 'confirmar_parcial', 3: 'confirmar_rechazar' })[vista.g.mensaje]
    const clase = vista.g.mensaje === '3' && G.fiscal_pendiente ? 'nxd-btn nxd-btn-peli' : 'nxd-btn nxd-btn-pri'
    principal = `<button type="button" class="${clase}" data-a="confirmar">${ico(vista.g.mensaje === '3' ? I.vacio : I.check, 15)}${esc(t(clave))}</button>`
  }

  refs.pie.innerHTML = `
    ${principal}
    ${reenviable ? `<button type="button" class="nxd-btn nxd-btn-avi" data-a="reenviar">${ico(I.send, 15)}${esc(t('reenviar'))}</button>` : ''}
    <a class="nxd-btn" href="${esc(BASE())}cargadocumentos/descargar_xml/${esc(vista.id)}/compra">
      ${ico(I.descarga, 15)}<span class="lbl">${esc(t('descargar_xml'))}</span></a>
    <div class="nxd-pie-fin">
      <button type="button" class="nxd-btn" data-a="cerrar">${esc(t('cerrar'))}</button>
    </div>`

  refs.pie.querySelectorAll('[data-a]').forEach(b => {
    b.addEventListener('click', () => accion(b.dataset.a, b))
  })
}

function accion (que, boton) {
  if (que === 'cerrar') return cerrar()
  if (vista.ocupado) return
  if (que === 'confirmar') return confirmar(boton)
  if (que === 'reenviar') return reenviar(boton)
}

function enviar (ruta, cuerpo) {
  return fetch(`${BASE()}${ruta}`, {
    method: 'POST',
    body: cuerpo,
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.text().then(txt => {
      let j = null
      try { j = JSON.parse(txt) } catch (e) { throw new Error(t('error_carga')) }
      if (!r.ok || j.error) throw new Error(j.error || t('error_carga'))
      return j
    }))
}

function avisar (tipo, lineas) {
  vista.aviso = `<div class="nxd-banda" style="--banda-c:${tono(tipo)}">
    ${ico(tipo === 'err' ? I.alerta : (tipo === 'ok' ? I.check : I.info), 18)}
    <div>${[].concat(lineas).filter(Boolean).map(x => `<div class="nxd-banda-txt">${esc(x)}</div>`).join('')}</div></div>`
  const el = refs.cuerpo.querySelector('[data-r="aviso"]')
  if (el) el.innerHTML = vista.aviso
  refs.cuerpo.scrollTop = 0
}

function confirmar (boton) {
  const G = vista.datos.gestion
  const g = vista.g
  const rechazo = G.fiscal_pendiente && g.mensaje === '3'

  if (!rechazo && conteo().pendientes) {
    g.marcar = true
    repintarLineas()
    avisar('err', t('hay_pendientes'))
    return
  }

  const datos = {
    registrar_como: g.como,
    categoria_gasto_id: g.categoria,
    alias: g.alias,
    mensaje: g.mensaje,
    condicion: g.condicion,
    acreditar: g.acreditar,
    detalle: g.detalle,
    lineas: G.lineas.map(l => {
      const s = g.lineas[l.id]
      return {
        id: l.id,
        destino: destinoDe(s),
        product_id: s.producto ? s.producto.id : 0,
        factor: f(s.factor) || 1,
        categoria_gasto_id: categoriaDe(s),
        aplicar_precio: s.aplicarPrecio,
        precio: f(s.precio),
      }
    }),
  }

  const cuerpo = new URLSearchParams()
  cuerpo.set('gestion', JSON.stringify(datos))
  // window.CSRF_HASH lo mantiene al día main.js con la cabecera X-CSRF-Token.
  if (window.CSRF_NAME) cuerpo.set(window.CSRF_NAME, window.CSRF_HASH)

  vista.ocupado = true
  const antes = boton.innerHTML
  boton.disabled = true
  boton.innerHTML = ico(I.gira, 15) + esc(t('confirmando'))

  enviar(`cargadocumentos/gestionar/${encodeURIComponent(vista.id)}`, cuerpo)
    .then(j => {
      const r = j.resultado || {}
      const lineas = [rechazo ? t('hecho_rechazado') : t('hecho_registrado')]
      if (r.compra) lineas.push(tf('hecho_compra', r.compra))
      if (r.gastos) lineas.push(tf('hecho_gastos', r.gastos))
      let tipo = 'ok'
      if (j.envio) {
        if (j.envio.confirmado) {
          lineas.push(tf('envio_confirmado', j.envio.estado.etiqueta))
        } else {
          lineas.push(tf('envio_pendiente', j.envio.estado.etiqueta))
          tipo = 'warn'
        }
      }
      vista.tab = 'gestion'
      if (typeof vista.alCambiar === 'function') vista.alCambiar()
      vista.aviso = null
      recargarConAviso(tipo, lineas)
    })
    .catch(err => {
      boton.disabled = false
      boton.innerHTML = antes
      avisar('err', String(err.message).split('\n'))
    })
    .finally(() => { vista.ocupado = false })
}

function reenviar (boton) {
  const cuerpo = new URLSearchParams()
  if (window.CSRF_NAME) cuerpo.set(window.CSRF_NAME, window.CSRF_HASH)
  vista.ocupado = true
  boton.disabled = true
  enviar(`cargadocumentos/reenviar/${encodeURIComponent(vista.id)}`, cuerpo)
    .then(j => {
      if (typeof vista.alCambiar === 'function') vista.alCambiar()
      recargarConAviso(j.envio.confirmado ? 'ok' : 'warn',
        j.envio.confirmado ? tf('envio_confirmado', j.envio.estado.etiqueta) : tf('envio_pendiente', j.envio.estado.etiqueta))
    })
    .catch(err => { boton.disabled = false; avisar('err', err.message) })
    .finally(() => { vista.ocupado = false })
}

/** Vuelve a pedir el documento y deja el resultado a la vista en Gestionar. */
function recargarConAviso (tipo, lineas) {
  vista.tab = 'gestion'
  const aviso = () => avisar(tipo, lineas)
  fetch(`${BASE()}cargadocumentos/documento/${encodeURIComponent(vista.id)}`, {
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(j => { vista.datos = j; iniciarGestion(); pintar(); aviso() })
    .catch(aviso)
}

function bandaGestionado () {
  const d = vista.datos
  const G = d.gestion
  const hecho = G.gestionado
  const estado = d.aceptacion.estado

  if (!hecho) {
    return `<div data-r="aviso">${vista.aviso || ''}</div>
      <div class="nxd-banda" style="--banda-c:${tono('muted')}">${ico(I.info, 18)}<div><div class="nxd-banda-txt">${esc(G.motivo)}</div></div></div>`
  }

  const enlaces = []
  if (hecho.compra) enlaces.push(`<a class="nxd-btn nxc-btn-sm" href="${esc(BASE())}purchases/view/${hecho.compra}" data-toggle="ajax-modal">${ico(I.caja, 14)}${esc(t('ver_compra'))} #${hecho.compra}</a>`)
  if (hecho.gastos) enlaces.push(`<a class="nxd-btn nxc-btn-sm" href="${esc(BASE())}purchases/expenses">${ico(I.recibo, 14)}${esc(t('ver_gastos'))} (${hecho.gastos})</a>`)

  const lineas = G.lineas.map(l => {
    const destino = l.destino || 'ignorar'
    return `<tr><td>${esc(l.nombre)}</td><td>${badge(t('destino_' + destino), { inventario: 'ok', gasto: 'info', activo: 'violet', ignorar: 'muted' }[destino])}</td>
      <td>${l.producto ? `${esc(l.producto.name)} <span class="nxt-code">${esc(l.producto.code)}</span>${l.relacion.factor && l.relacion.factor !== 1 ? ` × ${num(l.relacion.factor)}` : ''}` : '—'}</td>
      <td class="num mono">${money(l.total)}</td></tr>`
  }).join('')

  return `<div data-r="aviso">${vista.aviso || ''}</div>
    <div class="nxd-banda" style="--banda-c:${tono(G.estado === 'rechazado' ? 'err' : 'ok')}">
      ${ico(I.check, 18)}
      <div style="min-width:0">
        <div class="nxd-banda-tit">${esc(t('gestionado_titulo'))} · ${badge(estado.etiqueta, estado.tono)}</div>
        <div class="nxd-banda-txt">${esc(tf('gestionado_por', hecho.por || '—', hecho.en || '—'))}</div>
        ${enlaces.length ? `<div class="nxc-fila" style="margin-top:8px">${enlaces.join('')}</div>` : ''}
      </div>
    </div>
    ${G.estado !== 'rechazado' ? `<div class="nxd-bloque"><div class="nxd-scroll-x"><table class="nxd-tabla">
      <thead><tr><th>${esc(t('descripcion'))}</th><th>${esc(t('registrar_como'))}</th><th>${esc(t('producto'))}</th><th class="num">${esc(t('total'))}</th></tr></thead>
      <tbody>${lineas}</tbody></table></div></div>` : ''}`
}

/* ── Pestañas de XML ── */
function panelXml (cual) {
  const rotulo = cual === 'compra' ? t('tab_xml') : (cual === 'receptor' ? t('tab_receptor') : t('tab_respuesta'))

  refs.cuerpo.innerHTML = `
    <div class="nxd-panel activo">
      <div class="nxd-visor-barra">
        <div class="nxd-busca">
          ${ico(I.lupa, 15)}
          <input type="search" data-r="q" placeholder="${esc(t('buscar_xml'))}" autocomplete="off">
        </div>
        <button type="button" class="nxd-btn js-copiar" data-r="copiar">${ico(I.copiar, 15)}<span class="lbl">${esc(t('copiar'))}</span></button>
        <a class="nxd-btn" href="${esc(BASE())}cargadocumentos/descargar_xml/${esc(vista.id)}/${esc(cual)}">
          ${ico(I.descarga, 15)}<span class="lbl">${esc(t('descargar_xml'))}</span>
        </a>
      </div>
      <pre class="nxd-xml" data-r="xml"><div class="nxd-cargando">${ico(I.gira, 18)} ${esc(rotulo)}…</div></pre>
    </div>`

  const pre = refs.cuerpo.querySelector('[data-r="xml"]')

  const listo = texto => {
    if (!texto) {
      pre.innerHTML = `<div class="nxd-vacio">${ico(I.vacio, 34)}${esc(t('sin_xml'))}</div>`
      return
    }
    pre.innerHTML = colorearXml(texto)
    activarBusqueda(pre, texto)
  }

  if (vista.xml[cual] !== undefined) return listo(vista.xml[cual])

  fetch(`${BASE()}cargadocumentos/documento_xml/${encodeURIComponent(vista.id)}/${encodeURIComponent(cual)}`, {
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(j => { vista.xml[cual] = j.xml || ''; listo(vista.xml[cual]) })
    .catch(() => { pre.innerHTML = `<div class="nxd-vacio">${esc(t('error_carga'))}</div>` })
}

/**
 * Colorea un XML para leerlo en pantalla.
 *
 * El resaltado se aplica sobre el texto ya escapado: hacerlo sobre el original
 * dejaría el marcado del propio comprobante como HTML de la página.
 */
function colorearXml (texto) {
  return esc(texto)
    .replace(/(&lt;\?[\s\S]*?\?&gt;)|(&lt;!--[\s\S]*?--&gt;)/g, '<span class="d">$&</span>')
    .replace(/(&lt;\/?)([\w:.-]+)/g, '$1<span class="t">$2</span>')
    .replace(/([\w:.-]+)=(&quot;.*?&quot;)/g, '<span class="a">$1</span>=<span class="v">$2</span>')
}

function activarBusqueda (pre, texto) {
  const q = refs.cuerpo.querySelector('[data-r="q"]')
  const copiar = refs.cuerpo.querySelector('[data-r="copiar"]')

  if (copiar) {
    copiar.addEventListener('click', () => {
      navigator.clipboard.writeText(texto).then(() => {
        const l = copiar.querySelector('.lbl')
        if (!l) return
        const antes = l.textContent
        l.textContent = t('copiado')
        setTimeout(() => { l.textContent = antes }, 1400)
      })
    })
  }

  if (!q) return
  q.addEventListener('input', () => {
    const v = q.value.trim()
    if (!v) { pre.innerHTML = colorearXml(texto); return }
    const re = new RegExp('(' + v.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi')
    pre.innerHTML = colorearXml(texto).replace(
      new RegExp('(?![^<]*>)' + re.source, 'gi'),
      '<mark>$1</mark>'
    )
    const primero = pre.querySelector('mark')
    if (primero) primero.scrollIntoView({ block: 'center' })
  })
}

function activarCopias () {
  refs.cuerpo.querySelectorAll('.js-copiar[data-txt]').forEach(b => {
    b.addEventListener('click', () => {
      navigator.clipboard.writeText(b.dataset.txt).then(() => {
        b.classList.add('ok')
        setTimeout(() => b.classList.remove('ok'), 1200)
      })
    })
  })
}

/* ═══════════════════════════════ API PÚBLICA ═══════════════════════════════ */

const NxCompra = { abrir, cerrar }
window.NxCompra = NxCompra
export default NxCompra

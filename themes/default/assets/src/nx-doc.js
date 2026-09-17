/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

/* ═══════════════════════════════════════════════════════════════════════════
   NX-DOC.JS — Ventana de detalle de un comprobante (diseño .nxd-* de nx-doc.css)

   Todo lo que se puede hacer con una venta, sin salir del listado: ver el
   documento, el XML firmado, la respuesta de Hacienda y las notas asociadas;
   reenviarlo, consultarlo, imprimirlo, mandarlo por correo o por WhatsApp y
   arrancar una nota de crédito o de débito.

   Uso en una vista:
     NxDoc.abrir(saleId, { alCambiar: () => tabla.reload() })

   La vista debe publicar antes:
     window._nxdCsrf = { name: '...', hash: '...' }
     window._nxdLang = { ...claves de idioma... }   // opcional, hay respaldo
   ═══════════════════════════════════════════════════════════════════════════ */

const BASE = () => window.base_url || ''

/* ── Rótulos: la vista los pasa traducidos; acá vive el respaldo ── */
const RESPALDO = {
  titulo: 'Detalle del comprobante',
  tab_documento: 'Documento',
  tab_xml: 'XML firmado',
  tab_respuesta: 'Respuesta de Hacienda',
  tab_notas: 'Notas y recibos',
  tab_bitacora: 'Bitácora',
  doc_nota_credito: 'Nota de crédito electrónica',
  cargando: 'Cargando…',
  error_carga: 'No se pudo cargar el detalle de la venta.',
  reenviar: 'Reintentar envío',
  reenviando: 'Enviando a Hacienda…',
  reenvio_ok: 'Comprobante enviado. Estado actual: %s.',
  reenvio_error: 'No se pudo enviar el comprobante.',
  consultar: 'Consultar estado',
  imprimir_original: 'Imprimir original',
  imprimir_tiquete: 'Imprimir tiquete',
  descargar_pdf: 'Descargar PDF',
  descargar_xml: 'Descargar XML',
  descargar_acuse: 'Descargar acuse',
  enviar_correo: 'Enviar por correo',
  enviar_whatsapp: 'Enviar por WhatsApp',
  nota_credito: 'Nota de crédito',
  corregir: 'Corregir factura',
  nota_debito: 'Nota de débito',
  devolucion: 'Devolución de mercancía',
  anular_factura: 'Anular factura',
  marcar_anulado: 'Marcar como anulado',
  rehacer: 'Rehacer en el POS',
  grupo_hacienda: 'Hacienda',
  grupo_envio: 'Envío al cliente',
  grupo_impresion: 'Impresión y archivos',
  grupo_ajustes: 'Cobros y ajustes',
  copiar: 'Copiar',
  copiado: 'Copiado al portapapeles',
  clave: 'Clave',
  desglose_clave: 'Desglose de la clave',
  clave_pais: 'País',
  clave_fecha: 'Fecha',
  clave_cedula: 'Cédula del emisor',
  clave_casa: 'Casa matriz',
  clave_terminal: 'Terminal',
  clave_tipo: 'Tipo',
  clave_numero: 'Número',
  clave_situacion: 'Situación',
  clave_seguridad: 'Código de seguridad',
  sin_xml: 'Este comprobante todavía no tiene XML firmado.',
  sin_respuesta: 'Hacienda todavía no ha respondido este comprobante.',
  sin_notas: 'No hay notas de crédito, notas de débito ni recibos de pago asociados.',
  sin_bitacora: 'Sin movimientos registrados para esta venta.',
  sin_lineas: 'Esta venta no tiene líneas de detalle.',
  sin_pagos: 'Esta venta no tiene pagos registrados.',
  buscar_xml: 'Buscar en el documento…',
  coincidencias: 'coincidencias',
  impresora_no_configurada: 'Esta computadora no tiene impresora elegida. Configúrela desde el POS.',
  qz_desconectado: 'QZ Tray no está corriendo en esta computadora.',
  impresion_error: 'No se pudo imprimir el tiquete.',
  impresion_ok: 'Tiquete enviado a la impresora.',
  destinatarios: 'Destinatarios',
  agregar_destinatario: 'Escriba un correo y presione Enter para agregarlo.',
  sin_destinatarios: 'Sin destinatarios.',
  correo_enviado: 'Enviado a %s destinatario(s).',
  correo_fallido: 'No se pudo enviar a: %s',
  correo_invalido: 'Correo no válido: %s',
  whatsapp_sin_telefono: 'El cliente no tiene teléfono registrado. Escriba el número al que enviar.',
  whatsapp_texto: 'Le compartimos su comprobante electrónico %s por %s emitido el %s.',
  confirmar_anular: '¿Marcar este comprobante como anulado?',
  confirmar_reenviar: '¿Generar y enviar este comprobante a Hacienda?',
  confirmar_reemitir: 'Este comprobante fue rechazado. Volver a enviarlo exige emitirlo con un consecutivo nuevo. ¿Continuar?',
  emisor: 'Emisor',
  receptor: 'Receptor',
  cajero: 'Cajero',
  linea_cabys: 'CABYS',
  linea_unitario: 'Precio unitario',
  linea_iva: 'IVA',
  totales: 'Resumen de totales',
  forma_pago: 'Forma de pago',
  saldo: 'Saldo pendiente',
  vuelto: 'Vuelto',
  mensaje_hacienda: 'Mensaje de Hacienda',
  detalle_mensaje: 'Detalle',
  monto_impuesto: 'Impuesto declarado',
  total_declarado: 'Total declarado',
  actividad: 'Actividad económica',
  no_disponible: 'No disponible',
  cerrar: 'Cerrar',
  cantidad: 'Cantidad',
  descripcion: 'Descripción',
  total: 'Total',
  pagado: 'Pagado',
  descuento: 'Descuento',
  subtotal: 'Subtotal',
  fecha: 'Fecha',
  nota: 'Nota',
  referencia: 'Referencia',
  monto: 'Monto',
  consecutivo: 'Consecutivo',
  tienda: 'Tienda',
  identificacion: 'Identificación',
  correo: 'Correo',
  telefono: 'Teléfono',
  acciones: 'Acciones',
  mas_acciones: 'Más acciones',
  anu_titulo: 'Anular factura',
  anu_motivo_ph: 'Por qué se anula',
  anu_ok: 'Factura anulada.',
  anu_estado_firme: 'Anulada',
  anu_estado_pendiente: 'Anulación pendiente',
  anu_estado_fallida: 'Nota rechazada',
  anu_reintentar_nota: 'Reintentar la nota',
  anu_reintentando: 'Emitiendo la nota…',
  anu_reintento_ok: 'Nota %s: %s.',
  anu_titulo_fiscal: 'Anular con nota de crédito',
  anu_titulo_interna: 'Anular internamente',
  anu_explica_fiscal: 'Esta factura fue aceptada por Hacienda y es inmutable. Al confirmar se emite una <strong>nota de crédito por el 100%</strong> (código 01), se firma y se envía de una vez. La factura no se borra: queda registrada como anulada.',
  anu_explica_interna: 'Este comprobante nunca fue aceptado por Hacienda. Al confirmar se saca de circulación, los productos vuelven al inventario y la venta deja de contabilizarse. No se borra: queda en Facturas anuladas.',
  anu_motivo: 'Justificación',
  anu_motivo_ayuda: 'Queda en la bitácora y viaja a Hacienda como razón de la nota de crédito.',
  anu_motivo_requerido: 'Escriba la justificación de la anulación (al menos 5 caracteres).',
  anu_devuelve: '¿Hay que devolver dinero?',
  anu_devuelve_ayuda: 'Esta factura es de otro día. Indique si se le devuelve dinero al cliente y cuánto.',
  anu_monto: 'Monto a devolver',
  anu_medio: '¿Cómo se devuelve?',
  anu_medio_efectivo: 'Efectivo de la caja',
  anu_medio_sinpe: 'SINPE Móvil',
  anu_medio_transferencia: 'Transferencia',
  anu_medio_tarjeta: 'Reverso de tarjeta',
  anu_pin: 'PIN del cajón',
  anu_pin_ayuda: 'Sale dinero de la caja: se necesita el PIN de un administrador. El movimiento queda en la bitácora y en el cierre.',
  anu_sin_pin_configurado: 'Ningún administrador tiene PIN de cajón configurado. Póngalo en Ajustes.',
  anu_sin_caja: 'No hay una caja abierta, así que la devolución no queda en ningún cierre.',
  anu_confirmar: 'Sí, anular la factura',
  anu_fallida: 'No se pudo anular la factura.',
  anu_ok_fiscal: 'Factura anulada. Nota de crédito %s: %s.',
  anu_ok_interna: 'Factura anulada. Los productos volvieron al inventario.',
  anu_ya_anulada: 'Anulada',
  anu_anulada_el: 'Anulada el %s por %s',
  anu_devolvio: 'Se devolvieron %s',
  anu_sin_devolucion: 'Sin devolución de dinero',
  anu_ver_nota: 'Ver la nota de crédito',
  anu_tipo_fiscal: 'Con nota de crédito',
  anu_tipo_interna: 'Interna',
  anu_cajon_abierto: 'Cajón abierto para entregar el dinero.',
  ambiente_pruebas: 'Ambiente de pruebas: los comprobantes de este ambiente no tienen validez tributaria.',
}

function t (clave) {
  const mapa = window._nxdLang || {}
  return mapa[clave] != null ? mapa[clave] : (RESPALDO[clave] || clave)
}

/** Sustituye los %s de un rótulo por los argumentos, en orden. */
function tf (clave, ...args) {
  let i = 0
  return String(t(clave)).replace(/%s/g, () => {
    const valor = args[i]
    i += 1
    return valor == null ? '' : String(valor)
  })
}

/* ── Formato ── */
const esc = s => String(s == null ? '' : s)
  .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;').replace(/'/g, '&#39;')

const money = n => (window.NxTable ? window.NxTable.money(n) : (parseFloat(n) || 0).toFixed(2))
const qty = n => (window.NxTable ? window.NxTable.qty(n) : String(n))

const TONOS = {
  ok: 'var(--nx-emerald)', info: 'var(--nx-a1)', warn: 'var(--nx-amber)',
  err: 'var(--nx-err)', muted: 'var(--nx-slate)', violet: 'var(--nx-violet)',
  orange: 'var(--nx-orange)',
}
const tono = k => TONOS[k] || TONOS.muted

function badge (texto, k) {
  return `<span class="nxt-cat" style="--cat-c:${tono(k)}">${esc(texto)}</span>`
}

function aviso (tipo, mensaje) {
  if (typeof window.nxAlerta === 'function') return window.nxAlerta(tipo, mensaje)
  if (window.Swal) return window.Swal.fire({ icon: tipo === 'ok' ? 'success' : tipo, text: mensaje })
  return null
}

/* ── Iconos (Tabler, trazo) ── */
const ico = (d, tam = 16) =>
  `<svg width="${tam}" height="${tam}" viewBox="0 0 24 24" fill="none" stroke="currentColor" ` +
  `stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`

const I = {
  doc: '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1-2-2v-14a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/>',
  code: '<path d="M7 8l-4 4l4 4"/><path d="M17 8l4 4l-4 4"/><path d="M14 4l-4 16"/>',
  check: '<path d="M9 12l2 2l4-4"/><circle cx="12" cy="12" r="9"/>',
  notas: '<path d="M9 5h-2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-12a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6M9 16h4"/>',
  reloj: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  send: '<path d="M10 14l11-11"/><path d="M21 3l-6.5 18a.55.55 0 0 1-1 0l-3.5-7l-7-3.5a.55.55 0 0 1 0-1z"/>',
  print: '<path d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-14a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9v-4a2 2 0 0 0-2-2h-6a2 2 0 0 0-2 2v4"/><rect x="7" y="13" width="10" height="8" rx="2"/>',
  mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6l9-6"/>',
  wa: '<path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9l-5.05.9"/><path d="M9 10a.5.5 0 0 0 1 0v-1a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>',
  pdf: '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 12v-7a2 2 0 0 1 2-2h7l5 5v4"/><path d="M5 18h1.5a1.5 1.5 0 0 0 0-3h-1.5v6"/><path d="M17 18h2M17 21v-6h2"/><path d="M11 15v6h1a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-1z"/>',
  descarga: '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><path d="M7 11l5 5l5-5"/><path d="M12 4v12"/>',
  copiar: '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8v-2a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>',
  masc: '<circle cx="12" cy="12" r="9"/><path d="M9 12h6M12 9v6"/>',
  menos: '<circle cx="12" cy="12" r="9"/><path d="M9 12h6"/>',
  ban: '<circle cx="12" cy="12" r="9"/><path d="M5.7 5.7l12.6 12.6"/>',
  repetir: '<path d="M4 12v-3a3 3 0 0 1 3-3h13m-3-3l3 3l-3 3"/><path d="M20 12v3a3 3 0 0 1-3 3h-13m3 3l-3-3l3-3"/>',
  gira: '<path d="M20 11a8.1 8.1 0 0 0-15.5-2m-.5-4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>',
  lupa: '<circle cx="10" cy="10" r="7"/><path d="M21 21l-6-6"/>',
  alerta: '<path d="M12 9v4M12 17h.01"/><path d="M10.24 3.96l-8.13 14.05a2 2 0 0 0 1.73 3h16.32a2 2 0 0 0 1.73-3l-8.13-14.05a2 2 0 0 0-3.52 0z"/>',
  info: '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>',
  user: '<circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>',
  tienda: '<path d="M3 21h18"/><path d="M5 21v-10l-2-3h18l-2 3v10"/><path d="M9 21v-6h6v6"/>',
  dinero: '<circle cx="12" cy="12" r="3"/><rect x="2" y="6" width="20" height="12" rx="2"/>',
  lista: '<path d="M9 6h11M9 12h11M9 18h11"/><path d="M5 6h.01M5 12h.01M5 18h.01"/>',
  suma: '<path d="M3 17l6-6l4 4l8-8"/><path d="M14 7h7v7"/>',
  vacio: '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1-2-2v-14a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M9 14l6 0"/>',
}

/* ═══════════════════════════ ESTADO DEL COMPONENTE ═══════════════════════════ */

let capa = null
let refs = {}
let vista = { id: null, datos: null, tab: 'documento', xml: {}, destinos: [], alCambiar: null, ocupado: false }

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
    caja: capa.querySelector('.nxd-caja'),
    titulo: capa.querySelector('[data-r="titulo"]'),
    sub: capa.querySelector('[data-r="sub"]'),
    tabs: capa.querySelector('[data-r="tabs"]'),
    cuerpo: capa.querySelector('[data-r="cuerpo"]'),
    pie: capa.querySelector('[data-r="pie"]'),
  }

  capa.querySelector('[data-r="cerrar"]').addEventListener('click', cerrar)
  capa.addEventListener('mousedown', e => { if (e.target === capa) cerrar() })
  document.addEventListener('keydown', e => {
    if (e.key !== 'Escape' || !capa.classList.contains('abierta')) return
    // Se cierra de afuera hacia adentro: primero la ventana apilada, luego el
    // menú de acciones, y solo al final el detalle.
    if (sobre) { cerrarSobre(); return }
    const menu = capa.querySelector('.nxd-menu.abierto')
    if (menu) { menu.classList.remove('abierto'); return }
    cerrar()
  })
  // Un clic fuera del menú de acciones lo cierra.
  document.addEventListener('click', e => {
    if (!capa) return
    capa.querySelectorAll('.nxd-menu.abierto').forEach(m => {
      if (!m.parentNode.contains(e.target)) m.classList.remove('abierto')
    })
  })
}

function abrir (id, opciones) {
  montar()
  vista = { id, datos: null, tab: 'documento', xml: {}, destinos: [], alCambiar: (opciones || {}).alCambiar, ocupado: false }

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
  cerrarSobre()
  if (!capa) return
  capa.classList.remove('abierta')
  document.body.classList.remove('nxd-bloqueado')
}

function cargar () {
  fetch(`${BASE()}sales/documento/${encodeURIComponent(vista.id)}`, {
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json().then(j => ({ ok: r.ok, j })))
    .then(({ ok, j }) => {
      if (!ok || j.error) throw new Error(j.error || t('error_carga'))
      vista.datos = j
      pintar()
    })
    .catch(err => {
      refs.cuerpo.innerHTML = `<div class="nxd-vacio">${ico(I.alerta, 34)}${esc(err.message || t('error_carga'))}</div>`
    })
}

/* ═══════════════════════════════ PINTADO ═══════════════════════════════ */

/**
 * Como se identifica el comprobante en pantalla.
 *
 * Una venta anulada con nota de credito se muestra por la nota que la
 * compensa; la anulacion interna no emite nada y conserva su propio tipo.
 */
function etiquetaComprobante () {
  const d = vista.datos
  if (d.anulacion && d.anulacion.tipo === 'fiscal') return t('doc_nota_credito')
  return d.hacienda.existe ? d.hacienda.tipo_etiqueta : t('tab_documento')
}

function pintar () {
  const d = vista.datos
  const h = d.hacienda
  const est = h.estado

  refs.titulo.innerHTML = esc(etiquetaComprobante()) + ' ' + badge(est.etiqueta, est.tono)

  const partes = []
  if (h.consecutivo) partes.push(`<span class="nxt-code">${esc(h.consecutivo)}</span>`)
  partes.push(`<span>#${esc(d.venta.id)}</span>`)
  partes.push(`<span>${esc(d.venta.fecha)}</span>`)
  partes.push(`<strong style="color:var(--nx-txt1)">${money(d.venta.gran_total)}</strong>`)
  refs.sub.innerHTML = partes.join('<span style="opacity:.4">·</span>')

  pintarTabs()
  pintarPie()
  irA(vista.tab)
}

function pintarTabs () {
  const d = vista.datos
  const notas = d.relacionados.nc.length + d.relacionados.nd.length + d.relacionados.rep.length

  const defs = [
    { k: 'documento', txt: t('tab_documento'), ico: I.doc },
    { k: 'xml', txt: t('tab_xml'), ico: I.code, off: !d.hacienda.tiene_xml },
    { k: 'respuesta', txt: t('tab_respuesta'), ico: I.check, off: !d.hacienda.tiene_respuesta },
    { k: 'notas', txt: t('tab_notas'), ico: I.notas, pin: notas || null },
    { k: 'bitacora', txt: t('tab_bitacora'), ico: I.reloj },
  ]

  refs.tabs.innerHTML = defs.map(x =>
    `<button type="button" class="nxd-tab${x.k === vista.tab ? ' activa' : ''}" data-tab="${x.k}"${x.off ? ' disabled' : ''}>` +
    ico(x.ico, 15) + esc(x.txt) +
    (x.pin ? `<span class="nxd-tab-pin">${x.pin}</span>` : '') +
    '</button>').join('')

  refs.tabs.querySelectorAll('.nxd-tab').forEach(b => {
    b.addEventListener('click', () => irA(b.dataset.tab))
  })
}

function irA (tab) {
  vista.tab = tab
  refs.tabs.querySelectorAll('.nxd-tab').forEach(b => b.classList.toggle('activa', b.dataset.tab === tab))
  refs.cuerpo.scrollTop = 0

  if (tab === 'documento') return panelDocumento()
  if (tab === 'xml') return panelXml('firmado')
  if (tab === 'respuesta') return panelXml('respuesta')
  if (tab === 'notas') return panelNotas()
  if (tab === 'bitacora') return panelBitacora()
}

/**
 * Aviso de venta anulada.
 *
 * Es lo primero que hay que ver al abrirla, y lo único que se repite del
 * encabezado es el estado de la anulación, que no está en ningún otro lado.
 */
function bandaAnulacion (a) {
  if (!a) return ''

  const e = a.estado || { tono: 'muted', etiqueta: t('anu_ya_anulada'), firme: true, nota: '' }
  const partes = [tf('anu_anulada_el', a.fecha, a.usuario), a.motivo]
  if (a.devolucion > 0) partes.push(tf('anu_devolvio', money(a.devolucion)) + (a.medio ? ' · ' + a.medio : ''))

  return `<div class="nxd-banda" style="--banda-c:${tono(e.tono)}">
    ${ico(e.firme ? I.ban : I.alerta, 19)}
    <div style="min-width:0">
      <div class="nxd-banda-tit">${esc(e.etiqueta)}</div>
      <div class="nxd-banda-txt">${esc(partes.join(' · '))}</div>
      ${e.firme ? '' : `<div class="nxd-banda-txt">${esc(e.nota)}</div>`}
      ${a.cn_id ? `<div style="margin-top:8px;display:flex;gap:7px;flex-wrap:wrap">
        <a class="nxd-btn" style="padding:6px 11px;font-size:12px"
           href="${esc(BASE())}creditnotes/viewnc/${esc(a.cn_id)}" target="_blank" rel="noopener">
          ${ico(I.notas, 14)}${esc(t('doc_nota_credito'))}${a.consecutivo_nota ? ' · ' + esc(a.consecutivo_nota) : ''}</a>
        ${e.reintentable ? `<button type="button" class="nxd-btn nxd-btn-avi" style="padding:6px 11px;font-size:12px"
           data-a="reintentar_nota">${ico(I.gira, 14)}${esc(t('anu_reintentar_nota'))}</button>` : ''}
      </div>` : ''}
    </div>
  </div>`
}

/* ── Pestaña: documento ── */
function panelDocumento () {
  const d = vista.datos
  const v = d.venta
  const h = d.hacienda
  const est = h.estado

  const pruebas = d.emisor.ambiente !== 'prod'
    ? `<div class="nxd-banda" style="--banda-c:var(--nx-orange)">${ico(I.alerta, 18)}
         <div><div class="nxd-banda-txt">${esc(t('ambiente_pruebas'))}</div></div></div>`
    : ''

  refs.cuerpo.innerHTML = `
    <div class="nxd-panel activo">
      ${pruebas}
      ${bandaAnulacion(d.anulacion)}
      ${est.clave === 'aceptado' || d.anulacion ? '' : `<div class="nxd-banda" style="--banda-c:${tono(est.tono)}">
        ${ico(est.tono === 'err' ? I.alerta : I.info, 19)}
        <div><div class="nxd-banda-txt">${esc(est.nota)}</div></div>
      </div>`}

      <div data-r="panel"></div>

      <div class="nxd-grid">
        ${tarjetaReceptor(d.cliente)}
        ${tarjetaEmisor(d.emisor)}
        ${tarjetaComprobante(h)}
      </div>

      ${bloqueLineas(d.lineas)}

      <div class="nxd-cols">
        <div>
          ${bloquePagos(d.pagos)}
          ${v.nota ? `<div class="nxd-bloque"><div class="nxd-bloque-cab">${ico(I.lista, 14)}${esc(t('nota'))}</div>
            <div style="padding:13px 16px;font-size:13px;color:var(--nx-txt2);line-height:1.55">${esc(v.nota)}</div></div>` : ''}
        </div>
        <div class="nxd-bloque">
          <div class="nxd-bloque-cab">${ico(I.suma, 14)}${esc(t('totales'))}</div>
          <div style="padding:13px 16px">${tablaTotales(v)}</div>
        </div>
      </div>
    </div>`
  activarCopias()
  // El reintento de la nota se pinta dentro de la banda de anulación.
  refs.cuerpo.querySelectorAll('[data-a]').forEach(b => {
    b.addEventListener('click', ev => accion(b.dataset.a, ev, b))
  })
}

function tarjetaReceptor (c) {
  const filas = [
    c.ident ? [c.ident_tipo || t('identificacion'), c.ident, true] : null,
    c.email ? [t('correo'), c.email] : null,
    c.telefono ? [t('telefono'), c.telefono] : null,
  ].filter(Boolean)

  return `<div class="nxd-tarjeta">
    <div class="nxd-tarjeta-tit">${ico(I.user, 14)}${esc(t('receptor'))}</div>
    <div class="nxd-nombre">${esc(c.nombre)}</div>
    ${filas.map(f => `<dl class="nxd-kv"><dt>${esc(f[0])}</dt><dd${f[2] ? ' class="mono"' : ''}>${esc(f[1])}</dd></dl>`).join('')}
  </div>`
}

function tarjetaEmisor (e) {
  const filas = [
    e.cedula ? [t('clave_cedula'), e.cedula, true] : null,
    e.actividad ? [t('actividad'), e.actividad, true] : null,
    e.tienda ? [t('tienda'), e.tienda] : null,
    e.cajero ? [t('cajero'), e.cajero] : null,
  ].filter(Boolean)

  return `<div class="nxd-tarjeta">
    <div class="nxd-tarjeta-tit">${ico(I.tienda, 14)}${esc(t('emisor'))}</div>
    <div class="nxd-nombre">${esc(e.nombre || e.razon)}</div>
    ${filas.map(f => `<dl class="nxd-kv"><dt>${esc(f[0])}</dt><dd${f[2] ? ' class="mono"' : ''}>${esc(f[1])}</dd></dl>`).join('')}
  </div>`
}

function tarjetaComprobante (h) {
  if (!h.existe) {
    return `<div class="nxd-tarjeta">
      <div class="nxd-tarjeta-tit">${ico(I.doc, 14)}${esc(t('tab_documento'))}</div>
      <div style="font-size:13px;color:var(--nx-txt3)">${esc(t('sin_xml'))}</div>
    </div>`
  }

  const p = h.clave_partes
  const trozos = p ? [
    [t('clave_pais'), p.pais], [t('clave_fecha'), p.fecha], [t('clave_cedula'), p.cedula],
    [t('clave_casa'), p.casa_matriz], [t('clave_terminal'), p.terminal],
    [t('clave_tipo'), p.tipo_doc], [t('clave_numero'), p.numero],
    [t('clave_situacion'), p.situacion], [t('clave_seguridad'), p.seguridad],
  ] : []

  return `<div class="nxd-tarjeta">
    <div class="nxd-tarjeta-tit">${ico(I.doc, 14)}${esc(etiquetaComprobante())}</div>
    <dl class="nxd-kv"><dt>${esc(t('consecutivo'))}</dt><dd class="mono">${esc(h.consecutivo || '—')}</dd></dl>
    <dl class="nxd-kv"><dt>${esc(t('fecha'))}</dt><dd>${esc(h.fecha_emision || '—')}</dd></dl>
    ${h.clave ? `<div class="nxd-clave">
      <code>${esc(h.clave)}</code>
      <button type="button" class="nxd-mini js-copiar" data-txt="${esc(h.clave)}" title="${esc(t('copiar'))}">${ico(I.copiar, 14)}</button>
    </div>` : ''}
    ${trozos.length ? `<div class="nxd-desglose">${trozos.map(x =>
      `<div><span>${esc(x[0])}</span><b>${esc(x[1])}</b></div>`).join('')}</div>` : ''}
  </div>`
}

function bloqueLineas (lineas) {
  if (!lineas.length) {
    return `<div class="nxd-bloque"><div class="nxd-vacio">${esc(t('sin_lineas'))}</div></div>`
  }

  const filas = lineas.map(l => {
    const etqs = []
    if (l.cabys) etqs.push(`<span class="nxd-etq" style="--etq-c:var(--nx-violet)">${esc(t('linea_cabys'))} ${esc(l.cabys)}</span>`)
    etqs.push(`<span class="nxd-etq" style="--etq-c:${l.tasa > 0 ? 'var(--nx-emerald)' : 'var(--nx-slate)'}">${esc(t('linea_iva'))} ${l.tasa}%</span>`)
    if (l.cod_tarifa) etqs.push(`<span class="nxd-etq">${esc(l.cod_impuesto)}-${esc(l.cod_tarifa)}</span>`)
    if (l.descuento > 0) etqs.push(`<span class="nxd-etq" style="--etq-c:var(--nx-amber)">−${money(l.descuento)}</span>`)

    return `<tr>
      <td class="num" style="width:70px">${qty(l.cantidad)}${l.unidad ? ` <span style="color:var(--nx-txt4);font-size:11px">${esc(l.unidad)}</span>` : ''}</td>
      <td>
        <div class="nxd-linea-nom">${esc(l.nombre)}</div>
        ${l.codigo ? `<div class="nxd-item-meta">${esc(l.codigo)}</div>` : ''}
        ${l.comentario ? `<div class="nxd-item-meta">${esc(l.comentario)}</div>` : ''}
        <div class="nxd-linea-meta">${etqs.join('')}</div>
      </td>
      <td class="num">${money(l.unitario)}</td>
      <td class="num" style="color:var(--nx-txt3)">${money(l.impuesto)}</td>
      <td class="num"><strong style="color:var(--nx-txt1)">${money(l.total)}</strong></td>
    </tr>`
  }).join('')

  return `<div class="nxd-bloque">
    <div class="nxd-bloque-cab">${ico(I.lista, 14)}${esc(t('descripcion'))}<span style="margin-left:auto;text-transform:none;letter-spacing:0">${lineas.length}</span></div>
    <div class="nxd-scroll-x">
      <table class="nxd-tabla">
        <thead><tr>
          <th class="num">${esc(t('cantidad'))}</th>
          <th>${esc(t('descripcion'))}</th>
          <th class="num">${esc(t('linea_unitario'))}</th>
          <th class="num">${esc(t('linea_iva'))}</th>
          <th class="num">${esc(t('total'))}</th>
        </tr></thead>
        <tbody>${filas}</tbody>
      </table>
    </div>
  </div>`
}

function bloquePagos (pagos) {
  if (!pagos.length) {
    return `<div class="nxd-bloque"><div class="nxd-bloque-cab">${ico(I.dinero, 14)}${esc(t('forma_pago'))}</div>
      <div class="nxd-vacio" style="padding:26px">${esc(t('sin_pagos'))}</div></div>`
  }

  const filas = pagos.map(p => `<tr>
    <td>
      <div class="nxd-linea-nom">${esc(p.etiqueta)}</div>
      <div class="nxd-linea-meta"><span class="nxd-etq" style="--etq-c:var(--nx-a1)">${esc(p.codigo)}</span></div>
    </td>
    <td>${p.referencia ? `<span class="nxt-code">${esc(p.referencia)}</span>` : '<span style="color:var(--nx-txt4)">—</span>'}
      ${p.vuelto > 0 ? `<div class="nxd-item-meta">${esc(t('vuelto'))}: ${money(p.vuelto)}</div>` : ''}</td>
    <td class="num"><strong style="color:var(--nx-txt1)">${money(p.monto)}</strong></td>
  </tr>`).join('')

  return `<div class="nxd-bloque">
    <div class="nxd-bloque-cab">${ico(I.dinero, 14)}${esc(t('forma_pago'))}</div>
    <div class="nxd-scroll-x">
      <table class="nxd-tabla" style="min-width:380px">
        <thead><tr><th>${esc(t('forma_pago'))}</th><th>${esc(t('referencia'))}</th><th class="num">${esc(t('monto'))}</th></tr></thead>
        <tbody>${filas}</tbody>
      </table>
    </div>
  </div>`
}

function tablaTotales (v) {
  const f = []
  f.push([t('subtotal'), money(v.subtotal)])
  if (v.descuento) f.push([t('descuento'), '−' + money(v.descuento)])
  if (v.exoneracion) f.push(['Exoneración', '−' + money(v.exoneracion)])
  f.push([t('linea_iva'), money(v.impuesto)])
  if (v.redondeo) f.push(['Redondeo', money(v.redondeo)])

  return `<table class="nxd-tot">
    ${f.map(x => `<tr><td>${esc(x[0])}</td><td>${x[1]}</td></tr>`).join('')}
    <tr class="granda"><td>${esc(t('total'))}</td><td>${money(v.gran_total)}</td></tr>
    <tr><td>${esc(t('pagado'))} <span style="opacity:.7">· ${esc(v.estado_etiqueta)}</span></td><td>${money(v.pagado)}</td></tr>
    ${v.saldo > 0.009 ? `<tr class="saldo"><td>${esc(t('saldo'))}</td><td>${money(v.saldo)}</td></tr>` : ''}
  </table>`
}

/* ── Pestaña: XML firmado / respuesta ── */
function panelXml (cual) {
  const d = vista.datos
  const resumen = cual === 'respuesta' ? resumenRespuesta(d.hacienda.respuesta) : ''

  refs.cuerpo.innerHTML = `
    <div class="nxd-panel activo">
      ${resumen}
      <div class="nxd-visor-barra">
        <div class="nxd-busca">
          ${ico(I.lupa, 15)}
          <input type="search" data-r="q" placeholder="${esc(t('buscar_xml'))}" autocomplete="off">
        </div>
        <span class="nxd-conteo" data-r="conteo">0 / 0</span>
        <button type="button" class="nxd-mini" data-r="prev" title="↑">${ico('<path d="M6 15l6-6l6 6"/>', 14)}</button>
        <button type="button" class="nxd-mini" data-r="next" title="↓">${ico('<path d="M6 9l6 6l6-6"/>', 14)}</button>
        <button type="button" class="nxd-btn js-copiar" data-r="copiar">${ico(I.copiar, 15)}<span class="lbl">${esc(t('copiar'))}</span></button>
        <a class="nxd-btn" href="${esc(BASE())}sales/descargar_xml/${esc(d.venta.id)}/${cual}">
          ${ico(I.descarga, 15)}<span class="lbl">${esc(cual === 'respuesta' ? t('descargar_acuse') : t('descargar_xml'))}</span>
        </a>
      </div>
      <pre class="nxd-xml" data-r="xml"><div class="nxd-cargando">${ico(I.gira, 18)} ${esc(t('cargando'))}</div></pre>
    </div>`

  const pre = refs.cuerpo.querySelector('[data-r="xml"]')

  const listo = texto => {
    if (!texto) {
      pre.innerHTML = `<div class="nxd-vacio">${ico(I.vacio, 34)}${esc(cual === 'respuesta' ? t('sin_respuesta') : t('sin_xml'))}</div>`
      return
    }
    armarVisor(pre, texto)
  }

  if (vista.xml[cual] !== undefined) return listo(vista.xml[cual])

  fetch(`${BASE()}sales/documento_xml/${encodeURIComponent(d.venta.id)}/${cual}`, {
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

/** Pinta el XML coloreado en el visor y deja lista la búsqueda. */
function armarVisor (pre, texto) {
  const base = colorearXml(texto)
  pre.innerHTML = base

  const q = refs.cuerpo.querySelector('[data-r="q"]')
  const conteo = refs.cuerpo.querySelector('[data-r="conteo"]')
  const copiar = refs.cuerpo.querySelector('[data-r="copiar"]')
  copiar.dataset.txt = texto
  activarCopias()

  let marcas = []
  let foco = -1

  const enfocar = i => {
    if (!marcas.length) return
    foco = (i + marcas.length) % marcas.length
    marcas.forEach((m, k) => m.classList.toggle('foco', k === foco))
    marcas[foco].scrollIntoView({ block: 'center', behavior: 'smooth' })
    conteo.textContent = `${foco + 1} / ${marcas.length}`
  }

  const buscar = () => {
    const termino = q.value.trim()
    if (!termino) {
      pre.innerHTML = base
      marcas = []; foco = -1
      conteo.textContent = '0 / 0'
      return
    }
    // Se busca sobre el HTML ya coloreado, así que el término se escapa igual
    // que el contenido (buscar "<Clave>" tiene que encontrar &lt;Clave&gt;) y
    // se salta lo que esté dentro de una etiqueta, o un término como "span"
    // rompería el marcado.
    const patron = esc(termino).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    const re = new RegExp('(' + patron + ')(?![^<]*>)', 'gi')
    pre.innerHTML = base.replace(re, '<mark>$1</mark>')
    marcas = Array.prototype.slice.call(pre.querySelectorAll('mark'))
    conteo.textContent = `${marcas.length ? 1 : 0} / ${marcas.length}`
    if (marcas.length) enfocar(0)
  }

  let reloj = null
  q.addEventListener('input', () => { clearTimeout(reloj); reloj = setTimeout(buscar, 180) })
  q.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); enfocar(foco + (e.shiftKey ? -1 : 1)) }
  })
  refs.cuerpo.querySelector('[data-r="next"]').addEventListener('click', () => enfocar(foco + 1))
  refs.cuerpo.querySelector('[data-r="prev"]').addEventListener('click', () => enfocar(foco - 1))
  q.focus()
}

function resumenRespuesta (r) {
  if (!r) return ''
  const bien = /acept/i.test(r.estado || '')
  const filas = [
    r.estado ? [t('mensaje_hacienda'), badge(r.estado, bien ? 'ok' : 'err')] : null,
    r.total ? [t('total_declarado'), money(r.total)] : null,
    r.impuesto ? [t('monto_impuesto'), money(r.impuesto)] : null,
  ].filter(Boolean)

  const detalle = (r.detalle || '').trim()

  return `<div class="nxd-bloque">
    <div class="nxd-bloque-cab">${ico(bien ? I.check : I.alerta, 14)}${esc(t('mensaje_hacienda'))}</div>
    <div style="padding:13px 16px">
      ${filas.map(f => `<dl class="nxd-kv"><dt>${esc(f[0])}</dt><dd>${f[1]}</dd></dl>`).join('')}
      ${detalle && detalle !== '.' ? `<div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--nx-border2)">
        <div class="nxd-tarjeta-tit" style="margin-bottom:6px">${esc(t('detalle_mensaje'))}</div>
        <div style="font:400 12px/1.6 'Cascadia Code',Consolas,monospace;color:var(--nx-txt2);white-space:pre-wrap;max-height:220px;overflow:auto">${esc(detalle)}</div>
      </div>` : ''}
    </div>
  </div>`
}

/* ── Pestaña: notas y recibos ── */
function panelNotas () {
  const r = vista.datos.relacionados
  const grupos = [
    ['nc', t('nota_credito'), r.nc],
    ['nd', t('nota_debito'), r.nd],
    ['rep', 'REP', r.rep],
  ].filter(g => g[2].length)

  if (!grupos.length) {
    refs.cuerpo.innerHTML = `<div class="nxd-panel activo"><div class="nxd-vacio">${ico(I.vacio, 34)}${esc(t('sin_notas'))}</div></div>`
    return
  }

  refs.cuerpo.innerHTML = `<div class="nxd-panel activo">${grupos.map(g => `
    <div class="nxd-bloque">
      <div class="nxd-bloque-cab">${ico(I.notas, 14)}${esc(g[1])}<span style="margin-left:auto;text-transform:none">${g[2].length}</span></div>
      ${g[2].map(n => `<a class="nxd-item" href="${esc(n.url)}" target="_blank" rel="noopener">
        <div style="min-width:0">
          <div class="nxd-item-tit">${esc(n.consecutivo || '#' + n.id)}</div>
          <div class="nxd-item-meta">${esc(n.fecha)}${n.motivo ? ' · ' + esc(n.motivo) : ''}</div>
        </div>
        <div class="nxd-item-fin">
          ${n.total ? `<div style="font-weight:700;color:var(--nx-txt1)">${money(n.total)}</div>` : ''}
          <div style="margin-top:4px">${badge(n.estado.etiqueta, n.estado.tono)}</div>
        </div>
      </a>`).join('')}
    </div>`).join('')}</div>`
}

/* ── Pestaña: bitácora ── */
function panelBitacora () {
  refs.cuerpo.innerHTML = `<div class="nxd-panel activo"><div class="nxd-cargando">${ico(I.gira, 18)} ${esc(t('cargando'))}</div></div>`

  fetch(`${BASE()}sales/bitacora/${encodeURIComponent(vista.datos.venta.id)}`, {
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(j => {
      const m = j.movimientos || []
      if (!m.length) {
        refs.cuerpo.innerHTML = `<div class="nxd-panel activo"><div class="nxd-vacio">${ico(I.reloj, 34)}${esc(t('sin_bitacora'))}</div></div>`
        return
      }
      refs.cuerpo.innerHTML = `<div class="nxd-panel activo"><div class="nxd-bloque">
        <div class="nxd-bloque-cab">${ico(I.reloj, 14)}${esc(t('tab_bitacora'))}</div>
        ${m.map(x => `<div class="nxd-item">
          <div style="min-width:0">
            <div class="nxd-item-tit">${esc(x.accion)}</div>
            <div class="nxd-item-meta">${esc(x.detalle || '')}</div>
          </div>
          <div class="nxd-item-fin">
            <div style="font-size:12px;color:var(--nx-txt3)">${esc(x.fecha)}</div>
            <div class="nxd-item-meta">${esc(x.usuario || '')}</div>
          </div>
        </div>`).join('')}
      </div></div>`
    })
    .catch(() => {
      refs.cuerpo.innerHTML = `<div class="nxd-panel activo"><div class="nxd-vacio">${esc(t('error_carga'))}</div></div>`
    })
}

/* ═══════════════════════════ BARRA DE ACCIONES ═══════════════════════════ */

function pintarPie () {
  const d = vista.datos
  const a = d.acciones
  const e = d.enlaces

  const principal = a.reenviar
    ? `<button type="button" class="nxd-btn nxd-btn-pri" data-a="reenviar">${ico(I.send, 15)}${esc(t('reenviar'))}</button>`
    : (a.consultar
      ? `<button type="button" class="nxd-btn nxd-btn-pri" data-a="consultar">${ico(I.gira, 15)}${esc(t('consultar'))}</button>`
      : '')

  const menu = [
    ['rot', t('grupo_envio')],
    ['op', 'correo', I.mail, t('enviar_correo')],
    ['op', 'whatsapp', I.wa, t('enviar_whatsapp')],
    ['rot', t('grupo_ajustes')],
    a.corregir ? ['op', 'corregir', I.repetir, t('corregir')] : null,
    ['op', 'rehacer', I.repetir, t('rehacer')],
    a.anular ? ['peli', 'anular', I.ban, t('anular_factura')] : null,
    ['rot', t('grupo_impresion')],
    ['op', 'pdf', I.pdf, t('descargar_pdf')],
    a.xml ? ['op', 'xml', I.code, t('descargar_xml')] : null,
    a.acuse ? ['op', 'acuse', I.check, t('descargar_acuse')] : null,
  ].filter(Boolean)

  refs.pie.innerHTML = `
    ${principal}
    <button type="button" class="nxd-btn" data-a="imprimir">${ico(I.print, 15)}<span class="lbl">${esc(t('imprimir_original'))}</span></button>
    <button type="button" class="nxd-btn" data-a="tiquete">${ico(I.print, 15)}<span class="lbl">${esc(t('imprimir_tiquete'))}</span></button>
    <a class="nxd-btn" href="${esc(e.pdf)}">${ico(I.pdf, 15)}<span class="lbl">${esc(t('descargar_pdf'))}</span></a>
    <div class="nxd-pie-fin">
      <div class="nxd-menu-caja">
        <button type="button" class="nxd-btn" data-a="menu" aria-haspopup="true">${ico('<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>', 16)}<span class="lbl">${esc(t('mas_acciones'))}</span></button>
        <div class="nxd-menu">
          ${menu.map(x => x[0] === 'rot'
            ? `<div class="nxd-menu-rot">${esc(x[1])}</div>`
            : `<button type="button" class="nxd-menu-op${x[0] === 'peli' ? ' peli' : ''}" data-a="${x[1]}">${ico(x[2], 16)}${esc(x[3])}</button>`
          ).join('')}
        </div>
      </div>
      <button type="button" class="nxd-btn" data-a="cerrar">${esc(t('cerrar'))}</button>
    </div>`

  refs.pie.querySelectorAll('[data-a]').forEach(b => {
    b.addEventListener('click', ev => accion(b.dataset.a, ev, b))
  })
}

function accion (que, ev, boton) {
  const d = vista.datos
  const e = d.enlaces

  if (que === 'menu') {
    ev.stopPropagation()
    refs.pie.querySelector('.nxd-menu').classList.toggle('abierto')
    return
  }
  // Elegida una opción, el menú sobra.
  const menu = refs.pie.querySelector('.nxd-menu.abierto')
  if (menu) menu.classList.remove('abierto')


  switch (que) {
    case 'cerrar': return cerrar()
    case 'reenviar': return reenviar(boton)
    case 'consultar': return consultar(boton)
    case 'anular': return abrirAnulacion()
    case 'reintentar_nota': return reintentarNota(boton)
    case 'imprimir': return imprimirOriginal(e.original)
    case 'tiquete': return imprimirTiquete(e.tiquete)
    case 'correo': return abrirCorreo()
    case 'whatsapp': return enviarWhatsapp()
    case 'pdf': return abrirEnlace(e.pdf)
    case 'xml': return abrirEnlace(e.xml)
    case 'acuse': return abrirEnlace(e.acuse)
    case 'corregir': return abrirEnlace(e.corregir, true)
    case 'rehacer': return abrirEnlace(e.rehacer, true)
    default: return undefined
  }
}

/** Navega o descarga; `mismaPestana` sale del listado y por eso cierra antes. */
function abrirEnlace (url, mismaPestana) {
  if (mismaPestana) { cerrar(); window.location.href = url; return }
  window.open(url, '_blank', 'noopener')
}

/* ═══════════════════════════ ACCIONES DE HACIENDA ═══════════════════════════ */

/**
 * Pone el token CSRF vigente en el cuerpo del envío.
 *
 * `csrf_regenerate` rota el token en cada POST, así que el que la vista dejó
 * impreso en window._nxdCsrf vence en cuanto el listado pide sus filas — y el
 * primer envío del modal ya salía rechazado. main.js mantiene al día
 * window.CSRF_HASH con la cabecera X-CSRF-Token de cada respuesta AJAX.
 */
function csrf (body) {
  const c = window._nxdCsrf || {}
  const nombre = window.CSRF_NAME || c.name
  const valor = window.CSRF_HASH || c.hash
  if (nombre && valor) body.set(nombre, valor)
  return body
}

function ocupar (boton, texto) {
  vista.ocupado = true
  if (!boton) return () => { vista.ocupado = false }
  const antes = boton.innerHTML
  boton.disabled = true
  boton.innerHTML = ico(I.gira, 15) + esc(texto)
  boton.querySelector('svg').style.animation = 'nxd-gira 1s linear infinite'
  return () => { vista.ocupado = false; boton.disabled = false; boton.innerHTML = antes }
}

function confirmar (texto) {
  if (!window.Swal) return Promise.resolve(window.confirm(texto))
  return window.Swal.fire({
    title: texto, icon: 'question', showCancelButton: true,
    confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar',
    confirmButtonColor: '#0369a1', cancelButtonColor: '#6b7280', reverseButtons: true,
  }).then(r => r.isConfirmed)
}

function reenviar (boton) {
  if (vista.ocupado) return
  const reemite = vista.datos.acciones.reemite

  confirmar(reemite ? t('confirmar_reemitir') : t('confirmar_reenviar')).then(ok => {
    if (!ok) return
    const soltar = ocupar(boton, t('reenviando'))

    fetch(`${BASE()}sales/reenviar/${encodeURIComponent(vista.id)}`, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: csrf(new URLSearchParams()),
    })
      .then(r => r.json().then(j => ({ ok: r.ok, j })))
      .then(({ ok: bien, j }) => {
        soltar()
        if (!bien || j.error) {
          aviso('error', j.error || t('reenvio_error'))
          return
        }
        aviso(j.estado.tono === 'ok' ? 'ok' : 'info', tf('reenvio_ok', j.estado.etiqueta))
        refrescar(j.hacienda)
      })
      .catch(() => { soltar(); aviso('error', t('reenvio_error')) })
  })
}

function consultar (boton) {
  if (vista.ocupado) return
  const soltar = ocupar(boton, t('cargando'))

  fetch(`${BASE()}sales/consultar/${encodeURIComponent(vista.id)}`, {
    method: 'POST', credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: csrf(new URLSearchParams()),
  })
    .then(r => r.json().then(j => ({ ok: r.ok, j })))
    .then(({ ok, j }) => {
      soltar()
      if (!ok || j.error) { aviso('error', j.error || t('reenvio_error')); return }
      aviso('info', j.estado.etiqueta + ' — ' + j.estado.nota)
      refrescar(j.hacienda)
    })
    .catch(() => { soltar(); aviso('error', t('reenvio_error')) })
}

/* ═══════════════════════════ VENTANA APILADA ═══════════════════════════ */

let sobre = null

/**
 * Abre una ventana encima del detalle.
 *
 * Lo que se decide aparte va aparte: meter el formulario dentro del detalle
 * obligaba a bajar por media pantalla para encontrarlo.
 */
function abrirSobre ({ titulo, sub, tono, cuerpo, acciones }) {
  cerrarSobre()

  sobre = document.createElement('div')
  sobre.className = 'nxd-capa nxd-sobre abierta'
  sobre.innerHTML = `
    <div class="nxd-caja">
      <div class="nxd-cab">
        <div class="nxd-cab-icono"${tono ? ` style="background:${tono}"` : ''}>${ico(I.ban, 21)}</div>
        <div class="nxd-cab-txt">
          <div class="nxd-cab-tit">${esc(titulo)}</div>
          ${sub ? `<div class="nxd-cab-sub">${sub}</div>` : ''}
        </div>
        <button type="button" class="nxd-x" data-r="x" aria-label="${esc(t('cerrar'))}">&times;</button>
      </div>
      <div class="nxd-cuerpo" data-r="cuerpo">${cuerpo}</div>
      <div class="nxd-pie" data-r="pie">${acciones}</div>
    </div>`

  document.body.appendChild(sobre)
  sobre.querySelector('[data-r="x"]').addEventListener('click', cerrarSobre)
  sobre.addEventListener('mousedown', e => { if (e.target === sobre) cerrarSobre() })

  return {
    caja: sobre,
    $: s => sobre.querySelector(`[data-r="${s}"]`),
  }
}

function cerrarSobre () {
  if (sobre) { sobre.remove(); sobre = null }
}

/* ═══════════════════════════ ANULACIÓN ═══════════════════════════ */

/**
 * Anular una venta.
 *
 * El servidor decide si hace falta nota de crédito; acá solo se pide lo que no
 * puede deducir: el motivo y qué pasó con el dinero.
 */
function abrirAnulacion () {
  fetch(`${BASE()}sales/info_anulacion/${encodeURIComponent(vista.id)}`, {
    credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json().then(j => ({ ok: r.ok, j })))
    .then(({ ok, j }) => {
      if (!ok || j.error) throw new Error(j.error || t('error_carga'))
      if (!j.permitida) { aviso('warn', j.motivo_bloqueo); return }
      pintarAnulacion(j)
    })
    .catch(err => aviso('error', err.message || t('error_carga')))
}

function pintarAnulacion (info) {
  const d = vista.datos
  const sugerido = info.efectivo > 0 ? info.efectivo : info.pagado
  const medios = [
    ['efectivo', t('anu_medio_efectivo')],
    ['sinpe', t('anu_medio_sinpe')],
    ['transferencia', t('anu_medio_transferencia')],
    ['tarjeta', t('anu_medio_tarjeta')],
  ]

  const w = abrirSobre({
    titulo: t('anu_titulo'),
    sub: `${d.hacienda.consecutivo ? `<span class="nxt-code">${esc(d.hacienda.consecutivo)}</span>` : `<span>#${esc(d.venta.id)}</span>`}`,
    cuerpo: `
      <div class="nxd-resumen">
        <span>${esc(d.cliente.nombre)}</span>
        <b>${money(d.venta.gran_total)}</b>
      </div>

      <div class="nxd-campo">
        <label for="nxd-anu-motivo">${esc(t('anu_motivo'))}</label>
        <textarea id="nxd-anu-motivo" data-r="motivo" rows="2" placeholder="${esc(t('anu_motivo_ph'))}"></textarea>
      </div>

      <label class="nxd-check">
        <input type="checkbox" data-r="devuelve">
        <span>${esc(t('anu_devuelve'))}</span>
      </label>

      <div data-r="dinero" style="display:none">
        <div class="nxd-dos">
          <div class="nxd-campo">
            <label for="nxd-anu-monto">${esc(t('anu_monto'))}</label>
            <input type="number" id="nxd-anu-monto" data-r="monto" step="0.01" min="0"
                   max="${info.gran_total}" value="${sugerido.toFixed(2)}">
          </div>
          <div class="nxd-campo">
            <label for="nxd-anu-medio">${esc(t('anu_medio'))}</label>
            <select id="nxd-anu-medio" data-r="medio">
              ${medios.map(m => `<option value="${m[0]}"${m[0] === 'efectivo' && info.efectivo > 0 ? ' selected' : ''}>${esc(m[1])}</option>`).join('')}
            </select>
          </div>
        </div>
        <div class="nxd-campo" data-r="pinbox" style="display:none">
          <label for="nxd-anu-pin">${esc(t('anu_pin'))}</label>
          <input type="password" id="nxd-anu-pin" data-r="pin" class="pin" inputmode="numeric" autocomplete="off">
          ${info.pin_configurado ? '' : `<div class="nxd-ayuda" style="color:var(--nx-err)">${esc(t('anu_sin_pin_configurado'))}</div>`}
          ${info.caja_abierta ? '' : `<div class="nxd-ayuda" style="color:var(--nx-amber)">${esc(t('anu_sin_caja'))}</div>`}
        </div>
      </div>`,
    acciones: `
      <div class="nxd-pie-fin">
        <button type="button" class="nxd-btn" data-r="cancelar">${esc(t('cerrar'))}</button>
        <button type="button" class="nxd-btn nxd-btn-peli" data-r="ok">${ico(I.ban, 15)}${esc(t('anu_confirmar'))}</button>
      </div>`,
  })

  const devuelve = w.$('devuelve')
  const dinero = w.$('dinero')
  const medio = w.$('medio')
  const pinbox = w.$('pinbox')

  const refrescarCampos = () => {
    dinero.style.display = devuelve.checked ? '' : 'none'
    pinbox.style.display = (devuelve.checked && medio.value === 'efectivo') ? '' : 'none'
  }
  devuelve.addEventListener('change', refrescarCampos)
  medio.addEventListener('change', refrescarCampos)
  refrescarCampos()

  w.$('cancelar').addEventListener('click', cerrarSobre)
  w.$('ok').addEventListener('click', function () { confirmarAnulacion(w, this) })
  w.$('motivo').focus()
}

function confirmarAnulacion (w, boton) {
  const motivo = (w.$('motivo').value || '').trim()
  if (motivo.length < 5) { aviso('error', t('anu_motivo_requerido')); w.$('motivo').focus(); return }

  const devuelve = w.$('devuelve').checked
  const cuerpo = csrf(new URLSearchParams())
  cuerpo.set('motivo', motivo)
  cuerpo.set('devuelve_dinero', devuelve ? '1' : '0')
  if (devuelve) {
    cuerpo.set('monto_devuelto', String(parseFloat(w.$('monto').value) || 0))
    cuerpo.set('medio_devolucion', w.$('medio').value)
    if (w.$('medio').value === 'efectivo') cuerpo.set('pin', w.$('pin').value || '')
  }

  const soltar = ocupar(boton, t('cargando'))

  fetch(`${BASE()}sales/anular/${encodeURIComponent(vista.id)}`, {
    method: 'POST', credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: cuerpo,
  })
    .then(r => r.json().then(j => ({ ok: r.ok, j })))
    .then(({ ok, j }) => {
      soltar()
      if (!ok || j.error) { aviso('error', j.error || t('anu_fallida')); return }

      if (j.bytes_cajon) abrirCajon(j.bytes_cajon)

      const est = j.anulacion && j.anulacion.estado
      if (est && !est.firme) {
        aviso('warn', est.etiqueta + '. ' + est.nota)
      } else {
        aviso('ok', t('anu_ok'))
      }

      // La venta ya no está en el listado: no hay nada que seguir mirando.
      cerrarSobre()
      cerrar()
      if (typeof vista.alCambiar === 'function') vista.alCambiar()
    })
    .catch(() => { soltar(); aviso('error', t('anu_fallida')) })
}

/** Vuelve a emitir la nota cuando la anulación quedó pendiente o rechazada. */
function reintentarNota (boton) {
  const soltar = ocupar(boton, t('anu_reintentando'))

  fetch(`${BASE()}sales/reintentar_nota/${encodeURIComponent(vista.id)}`, {
    method: 'POST', credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: csrf(new URLSearchParams()),
  })
    .then(r => r.json().then(j => ({ ok: r.ok, j })))
    .then(({ ok, j }) => {
      soltar()
      if (!ok || j.error) { aviso('error', j.error || t('anu_nc_fallida')); return }
      const est = j.anulacion && j.anulacion.estado
      aviso(est && est.firme ? 'ok' : 'warn', tf('anu_reintento_ok', j.nota.consecutivo, j.nota.estado.etiqueta))
      if (j.anulacion) vista.datos.anulacion = j.anulacion
      pintar()
      if (typeof vista.alCambiar === 'function') vista.alCambiar()
    })
    .catch(() => { soltar(); aviso('error', t('anu_nc_fallida')) })
}

/** Manda a QZ Tray el pulso que abre el cajón para entregar el dinero. */
function abrirCajon (bytes) {
  const qz = window.qz
  if (!qz) { aviso('warn', t('qz_desconectado')); return }

  let impresora = ''
  try { impresora = localStorage.getItem('nx-qz-printer') || '' } catch (err) { impresora = '' }
  if (!impresora) { aviso('warn', t('impresora_no_configurada')); return }

  const conectar = window.nxQzConectar ? window.nxQzConectar() : qz.websocket.connect()
  conectar
    .then(() => qz.print(qz.configs.create(impresora), [
      { type: 'raw', format: 'command', flavor: 'base64', data: bytes },
    ]))
    .then(() => aviso('ok', t('anu_cajon_abierto')))
    .catch(() => aviso('warn', t('qz_desconectado')))
}

/** Reemplaza el bloque de Hacienda y repinta, sin volver a pedir todo. */
function refrescar (hacienda) {
  vista.datos.hacienda = hacienda
  vista.xml = {}
  // Un comprobante que cambio de estado cambia tambien lo que se puede hacer.
  fetch(`${BASE()}sales/documento/${encodeURIComponent(vista.id)}`, {
    credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(j => { if (!j.error) { vista.datos = j; pintar() } })
    .catch(() => pintar())
    .then(() => { if (typeof vista.alCambiar === 'function') vista.alCambiar() })
}

/* ═══════════════════════════ IMPRESIÓN ═══════════════════════════ */

/**
 * Imprime el comprobante con el diálogo del navegador.
 *
 * Se carga en un marco oculto en vez de abrir la página: así la acción no saca
 * al usuario del listado y el comprobante sale con su propia hoja de impresión.
 */
function imprimirOriginal (url) {
  const viejo = document.getElementById('nxd-marco')
  if (viejo) viejo.remove()

  const marco = document.createElement('iframe')
  marco.id = 'nxd-marco'
  marco.style.cssText = 'position:fixed;width:0;height:0;border:0;left:-9999px;top:0'
  marco.src = url
  marco.addEventListener('load', () => {
    try {
      marco.contentWindow.focus()
      marco.contentWindow.print()
    } catch (err) {
      window.open(url, '_blank', 'noopener')
    }
  })
  document.body.appendChild(marco)
}

function imprimirTiquete (url) {
  const qz = window.qz
  if (!qz) { aviso('warn', t('qz_desconectado')); return }

  let impresora = ''
  try { impresora = localStorage.getItem('nx-qz-printer') || '' } catch (err) { impresora = '' }
  if (!impresora) { aviso('warn', t('impresora_no_configurada')); return }

  const conectar = window.nxQzConectar ? window.nxQzConectar() : qz.websocket.connect()

  conectar
    // QZ Tray corre en la computadora del cajero: si no está instalado o está
    // apagado no hay nada que reintentar, y decir "no se pudo imprimir" manda
    // a buscar el problema donde no está.
    .catch(() => { throw new Error('qz') })
    .then(() => fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } }))
    .then(r => r.json())
    .then(res => {
      if (!res || res.status !== 1 || !res.bytes) throw new Error('bytes')
      return qz.print(qz.configs.create(impresora), [
        { type: 'raw', format: 'command', flavor: 'base64', data: res.bytes },
      ])
    })
    .then(() => aviso('ok', t('impresion_ok')))
    .catch(err => aviso('error', err && err.message === 'qz' ? t('qz_desconectado') : t('impresion_error')))
}

/* ═══════════════════════════ ENVÍO AL CLIENTE ═══════════════════════════ */

const RE_CORREO = /^[^@\s]+@[^@\s]+\.[^@\s]+$/

function abrirCorreo () {
  if (vista.tab !== 'documento') irA('documento')
  const caja = refs.cuerpo.querySelector('[data-r="panel"]')
  if (!caja) return

  const inicial = vista.datos.cliente.email
  vista.destinos = inicial && RE_CORREO.test(inicial) ? [inicial] : []

  caja.innerHTML = `
    <div class="nxd-correo">
      <div class="nxd-tarjeta-tit" style="margin:0">${ico(I.mail, 14)}${esc(t('enviar_correo'))}</div>
      <div class="nxd-correo-fila">
        <input type="email" data-r="correo-in" placeholder="contacto@ejemplo.com" autocomplete="off">
        <button type="button" class="nxd-btn" data-r="correo-add">${ico(I.masc, 15)}</button>
        <button type="button" class="nxd-btn nxd-btn-pri" data-r="correo-go">${ico(I.send, 15)}${esc(t('enviar_correo'))}</button>
      </div>
      <div class="nxd-ayuda">${esc(t('agregar_destinatario'))}</div>
      <div class="nxd-chips" data-r="correo-chips"></div>
    </div>`

  const input = caja.querySelector('[data-r="correo-in"]')
  const chips = caja.querySelector('[data-r="correo-chips"]')

  const pintarChips = () => {
    chips.innerHTML = vista.destinos.length
      ? vista.destinos.map((c, i) => `<span class="nxd-chip">${esc(c)}<button type="button" data-i="${i}">&times;</button></span>`).join('')
      : `<span class="nxd-ayuda">${esc(t('sin_destinatarios'))}</span>`
    chips.querySelectorAll('button[data-i]').forEach(b => {
      b.addEventListener('click', () => { vista.destinos.splice(+b.dataset.i, 1); pintarChips() })
    })
  }

  const agregar = () => {
    const v = input.value.trim().replace(/[;,]+$/, '')
    if (!v) return false
    if (!RE_CORREO.test(v)) { aviso('error', tf('correo_invalido', v)); return false }
    if (vista.destinos.indexOf(v) === -1) vista.destinos.push(v)
    input.value = ''
    pintarChips()
    return true
  }

  caja.querySelector('[data-r="correo-add"]').addEventListener('click', agregar)
  input.addEventListener('keydown', ev => {
    if (ev.key === 'Enter' || ev.key === ',' || ev.key === ';') { ev.preventDefault(); agregar() }
  })
  caja.querySelector('[data-r="correo-go"]').addEventListener('click', function () {
    if (input.value.trim() && !agregar()) return
    if (!vista.destinos.length) { aviso('error', t('sin_destinatarios')); return }
    enviarCorreo(this)
  })

  pintarChips()
  caja.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  input.focus()
}

function enviarCorreo (boton) {
  const soltar = ocupar(boton, t('cargando'))
  const destinos = vista.destinos.slice()
  let bien = 0
  const fallidos = []

  const uno = correo => {
    const cuerpo = csrf(new URLSearchParams())
    cuerpo.set('email', correo)
    cuerpo.set('id', String(vista.datos.venta.id))
    return fetch(vista.datos.enlaces.correo, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: cuerpo,
    })
      // Sin comprobar r.ok, un 403 de CSRF pasaría por envío exitoso.
      .then(r => { if (!r.ok) throw new Error('http_' + r.status); bien++ })
      .catch(() => fallidos.push(correo))
  }

  Promise.all(destinos.map(uno)).then(() => {
    soltar()
    if (bien) aviso('ok', tf('correo_enviado', bien))
    if (fallidos.length) aviso('error', tf('correo_fallido', fallidos.join(', ')))
  })
}

function enviarWhatsapp () {
  const d = vista.datos
  const texto = tf('whatsapp_texto',
    d.hacienda.consecutivo || '#' + d.venta.id,
    money(d.venta.gran_total),
    d.venta.fecha)

  const abrirCon = numero => {
    const limpio = String(numero || '').replace(/\D/g, '')
    if (!limpio) return
    window.open(`https://wa.me/${limpio}?text=${encodeURIComponent(texto)}`, '_blank', 'noopener')
  }

  const tel = String(d.cliente.telefono || '').replace(/\D/g, '')
  if (tel) return abrirCon(tel.length <= 8 ? d.cliente.cod_pais + tel : tel)

  if (!window.Swal) return abrirCon(window.prompt(t('whatsapp_sin_telefono'), ''))

  window.Swal.fire({
    title: t('enviar_whatsapp'),
    text: t('whatsapp_sin_telefono'),
    input: 'tel',
    inputPlaceholder: '50688887777',
    showCancelButton: true,
    confirmButtonColor: '#0369a1',
  }).then(r => { if (r.isConfirmed) abrirCon(r.value) })
}

/* ═══════════════════════════ PORTAPAPELES ═══════════════════════════ */

function activarCopias () {
  capa.querySelectorAll('.js-copiar').forEach(b => {
    if (b.dataset.listo) return
    b.dataset.listo = '1'
    b.addEventListener('click', () => {
      const texto = b.dataset.txt || ''
      const marcar = () => {
        b.classList.add('ok')
        aviso('ok', t('copiado'))
        setTimeout(() => b.classList.remove('ok'), 1400)
      }
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(texto).then(marcar).catch(() => copiarViejo(texto, marcar))
      } else {
        copiarViejo(texto, marcar)
      }
    })
  })
}

/** Respaldo para http:// sin contexto seguro, que es como corre en el local. */
function copiarViejo (texto, alTerminar) {
  const ta = document.createElement('textarea')
  ta.value = texto
  ta.style.cssText = 'position:fixed;left:-9999px;top:0'
  document.body.appendChild(ta)
  ta.select()
  try { document.execCommand('copy'); alTerminar() } catch (err) { /* sin portapapeles */ }
  ta.remove()
}

/* ═══════════════════════════════ SALIDA ═══════════════════════════════ */

const NxDoc = { abrir, cerrar }
window.NxDoc = NxDoc
export { NxDoc }

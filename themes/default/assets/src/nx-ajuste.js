/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

/* ═══════════════════════════════════════════════════════════════════════════
   NX-AJUSTE.JS — Correccion de una factura emitida (diseño .nxa-* de nx-ajuste.css)

   El cajero edita las lineas y la pantalla pregunta al servidor, en cada
   cambio, que documento saldria. Aca no se decide nada: el tipo, los montos y
   los bloqueos los calcula ajuste_helper.php y esto solo los pinta.

   La vista publica antes:
     window._nxaConfig = { venta: <id>, csrf: {name, hash} }
     window._nxaLang   = { ...claves... }   // opcional, hay respaldo
   ═══════════════════════════════════════════════════════════════════════════ */

const BASE = () => window.base_url || ''

const RESPALDO = {
  titulo: 'Corregir factura',
  cargando: 'Cargando…',
  error_carga: 'No se pudo cargar la factura.',
  original: 'Documento original',
  consecutivo: 'Consecutivo',
  clave: 'Clave',
  total_original: 'Total original',
  total_actual: 'Total actual',
  diferencia: 'Diferencia',
  tipo_operacion: 'Tipo de operación',
  saldo_ajustable: 'Saldo por corregir',
  lineas: 'Líneas del documento',
  cantidad: 'Cantidad',
  precio: 'Precio',
  descuento: 'Descuento',
  importe: 'Importe',
  quitar: 'Quitar la línea',
  devolver: 'Devolver la línea',
  agregar_ph: 'Buscar un producto para agregarlo…',
  sin_lineas: 'El documento quedó sin líneas: la corrección es una anulación total.',
  motivo: 'Justificación',
  motivo_ph: 'Por qué se corrige la factura',
  motivo_ayuda: 'Queda en la bitácora y viaja a Hacienda dentro de <Razon>.',
  codigo: 'Código de referencia',
  emitir: 'Emitir documento',
  emitiendo: 'Emitiendo…',
  sin_cambios: 'Sin cambios',
  nota_credito: 'Nota de crédito',
  nota_debito: 'Nota de débito',
  anulacion: 'Anulación total',
  desglose: 'Qué cambió',
  eliminada: 'Línea eliminada',
  agregada: 'Línea agregada',
  modificada: 'Línea modificada',
  confirmar_credito: 'La modificación reduce el valor de la factura original en %s. ¿Desea continuar?',
  confirmar_debito: 'La modificación aumenta el valor de la factura original en %s. ¿Desea continuar?',
  confirmar_anulacion: 'Eliminó todos los ítems. Se emitirá una nota de crédito por el 100 % que deja la factura sin efecto. ¿Desea continuar?',
  emitido: '%s %s emitida. Estado: %s.',
  ver_documento: 'Ver el documento',
  motivo_requerido: 'Escriba la justificación de la corrección (al menos 5 caracteres).',
  nota_cubre: 'La nota declara %s; el efecto neto del cambio es %s.',
  sin_resultados: 'Producto no encontrado.',
}

const t = c => {
  const m = window._nxaLang || {}
  return m[c] != null ? m[c] : (RESPALDO[c] || c)
}
const tf = (c, ...a) => { let i = 0; return String(t(c)).replace(/%s/g, () => a[i++] ?? '') }

const esc = s => String(s == null ? '' : s)
  .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;').replace(/'/g, '&#39;')

const money = n => (window.NxTable ? window.NxTable.money(n) : (parseFloat(n) || 0).toFixed(2))
const num = n => Math.round((parseFloat(n) || 0) * 10000) / 10000

const ico = (d, tam = 16) =>
  `<svg width="${tam}" height="${tam}" viewBox="0 0 24 24" fill="none" stroke="currentColor" ` +
  `stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`

const I = {
  lista: '<path d="M9 6h11M9 12h11M9 18h11"/><path d="M5 6h.01M5 12h.01M5 18h.01"/>',
  gira: '<path d="M20 11a8.1 8.1 0 0 0-15.5-2m-.5-4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>',
  x: '<path d="M18 6 6 18M6 6l12 12"/>',
  volver: '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/>',
  alerta: '<path d="M12 9v4M12 17h.01"/><path d="M10.24 3.96l-8.13 14.05a2 2 0 0 0 1.73 3h16.32a2 2 0 0 0 1.73-3l-8.13-14.05a2 2 0 0 0-3.52 0z"/>',
  send: '<path d="M10 14l11-11"/><path d="M21 3l-6.5 18a.55.55 0 0 1-1 0l-3.5-7l-7-3.5a.55.55 0 0 1 0-1z"/>',
  suma: '<path d="M3 17l6-6l4 4l8-8"/><path d="M14 7h7v7"/>',
}

/* Los tonos del veredicto son los mismos que usa el resto del sistema. */
const TONO = {
  SIN_CAMBIOS: 'var(--nx-slate)',
  NOTA_CREDITO: 'var(--nx-err)',
  NOTA_DEBITO: 'var(--nx-emerald)',
  ANULACION: 'var(--nx-orange)',
}

/* ═══════════════════════════ ESTADO ═══════════════════════════ */

const S = {
  venta: null,
  originales: [],   // lineas tal como se facturaron, congeladas
  edicion: [],      // lo que el cajero tiene en pantalla
  veredicto: null,
  permiso: null,
  codigos: {},
  refs: {},
  pidiendo: null,
  ocupado: false,
}

/* ═══════════════════════════ ARRANQUE ═══════════════════════════ */

function iniciar () {
  const raiz = document.getElementById('nxaApp')
  if (!raiz || !window._nxaConfig) return

  raiz.innerHTML = `
    <div data-r="orig"></div>
    <div class="nxa-cols">
      <div>
        <div class="nxa-buscar" data-r="buscar" hidden>
          <input type="text" data-r="q" placeholder="${esc(t('agregar_ph'))}" autocomplete="off">
          <div class="nxa-sug" data-r="sug"></div>
        </div>
        <div class="nxa-bloque">
          <div class="nxa-bloque-cab">${ico(I.lista, 14)}${esc(t('lineas'))}</div>
          <div data-r="lineas"><div class="nxa-cargando">${ico(I.gira, 18)} ${esc(t('cargando'))}</div></div>
        </div>
      </div>
      <div data-r="panel"></div>
    </div>`

  S.refs = {
    orig: raiz.querySelector('[data-r="orig"]'),
    lineas: raiz.querySelector('[data-r="lineas"]'),
    buscar: raiz.querySelector('[data-r="buscar"]'),
    q: raiz.querySelector('[data-r="q"]'),
    sug: raiz.querySelector('[data-r="sug"]'),
    panel: raiz.querySelector('[data-r="panel"]'),
  }

  enlazarBuscador()
  cargar()
}

function cargar () {
  fetch(`${BASE()}ajuste/documento/${window._nxaConfig.venta}`, {
    credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json().then(j => ({ ok: r.ok, j })))
    .then(({ ok, j }) => {
      if (!ok || j.error) throw new Error(j.error || t('error_carga'))
      S.venta = j.venta
      S.permiso = j.permiso
      S.codigos = j.codigos || {}
      S.originales = j.lineas
      // La edicion arranca como copia del original: comparar algo contra si
      // mismo tiene que dar "sin cambios", y ese es el primer veredicto.
      S.edicion = j.lineas.map(l => Object.assign({}, l, { fuera: false, nueva: false }))
      pintarOriginal(j)
      pintarLineas()
      previsualizar()
    })
    .catch(err => {
      S.refs.lineas.innerHTML = `<div class="nxa-vacio">${esc(err.message || t('error_carga'))}</div>`
    })
}

/* ═══════════════════════════ PINTADO ═══════════════════════════ */

function pintarOriginal (d) {
  const v = d.venta
  const e = d.estado
  const dato = (r, val, mono) =>
    `<div class="nxa-orig-b"><div class="nxa-orig-r">${esc(r)}</div>
     <div class="nxa-orig-v${mono ? ' mono' : ''}">${esc(val || '—')}</div></div>`

  S.refs.orig.innerHTML = `<div class="nxa-orig">
    ${dato(t('consecutivo'), v.consecutivo, true)}
    ${dato(t('clave'), v.clave, true)}
    ${dato(t('total_original'), money(v.grand_total))}
    ${dato(t('saldo_ajustable'), money(d.saldo.ajustable))}
    <div class="nxa-orig-fin">
      <span class="nxt-cat" style="--cat-c:var(--nx-${e.tono === 'ok' ? 'emerald' : e.tono === 'err' ? 'err' : 'a1'})">${esc(e.etiqueta)}</span>
    </div>
  </div>`

  if (!d.permiso.puede) {
    S.refs.buscar.hidden = true
  }
}

function pintarLineas () {
  if (!S.edicion.length) {
    S.refs.lineas.innerHTML = `<div class="nxa-vacio">${ico(I.alerta, 30)}<div style="margin-top:8px">${esc(t('sin_lineas'))}</div></div>`
    S.refs.buscar.hidden = !S.permiso || !S.permiso.puede
    return
  }

  const filas = S.edicion.map((l, i) => {
    const orig = l.linea_origen_id
      ? S.originales.find(o => o.linea_origen_id === l.linea_origen_id)
      : null
    const cambiada = orig && !l.fuera && (
      num(orig.quantity) !== num(l.quantity) ||
      num(orig.unit_price) !== num(l.unit_price) ||
      num(orig.item_discount) !== num(l.item_discount)
    )
    const clase = l.fuera ? 'fuera' : (l.nueva ? 'nueva' : (cambiada ? 'cambiada' : ''))
    const bloq = l.fuera ? ' disabled' : ''
    const neto = Math.max(0, num(l.unit_price) - (num(l.quantity) > 0 ? num(l.item_discount) / num(l.quantity) : 0))

    return `<tr class="${clase}" data-i="${i}">
      <td>
        <div class="nxa-nom">${esc(l.product_name)}</div>
        <div class="nxa-meta">${esc(l.product_code)}${l.cabys ? ' · CABYS ' + esc(l.cabys) : ''}${l.tax ? ' · ' + esc(l.tax) : ''}</div>
      </td>
      <td class="num"><input class="nxa-in" type="number" min="0" step="0.01" data-c="quantity" value="${num(l.quantity)}"${bloq}></td>
      <td class="num"><input class="nxa-in" type="number" min="0" step="0.01" data-c="unit_price" value="${num(l.unit_price)}"${bloq}></td>
      <td class="num"><input class="nxa-in" type="number" min="0" step="0.01" data-c="item_discount" value="${num(l.item_discount)}"${bloq}></td>
      <td class="num" data-r="importe">${money(num(l.quantity) * neto)}</td>
      <td class="num">
        <button type="button" class="nxa-mini${l.fuera ? ' volver' : ''}" data-a="${l.fuera ? 'volver' : 'quitar'}"
          title="${esc(l.fuera ? t('devolver') : t('quitar'))}">${ico(l.fuera ? I.volver : I.x, 15)}</button>
      </td>
    </tr>`
  }).join('')

  S.refs.lineas.innerHTML = `<div style="overflow-x:auto"><table class="nxa-tabla">
    <thead><tr>
      <th>${esc(t('descripcion') || 'Descripción')}</th>
      <th class="num">${esc(t('cantidad'))}</th>
      <th class="num">${esc(t('precio'))}</th>
      <th class="num">${esc(t('descuento'))}</th>
      <th class="num">${esc(t('importe'))}</th>
      <th></th>
    </tr></thead>
    <tbody>${filas}</tbody>
  </table></div>`

  S.refs.buscar.hidden = !S.permiso || !S.permiso.puede
  enlazarLineas()
}

function enlazarLineas () {
  S.refs.lineas.querySelectorAll('input[data-c]').forEach(inp => {
    inp.addEventListener('input', () => {
      const fila = inp.closest('tr')
      const l = S.edicion[+fila.dataset.i]
      l[inp.dataset.c] = Math.max(0, parseFloat(inp.value) || 0)
      // El importe se refresca en el acto; el veredicto lo decide el servidor.
      const neto = Math.max(0, num(l.unit_price) - (num(l.quantity) > 0 ? num(l.item_discount) / num(l.quantity) : 0))
      fila.querySelector('[data-r="importe"]').textContent = money(num(l.quantity) * neto)
      fila.classList.add('cambiada')
      programar()
    })
  })

  S.refs.lineas.querySelectorAll('[data-a]').forEach(b => {
    b.addEventListener('click', () => {
      const i = +b.closest('tr').dataset.i
      const l = S.edicion[i]
      // Una linea agregada se descarta; una del original se marca fuera para
      // poder devolverla sin volver a cargar la factura.
      if (l.nueva && !l.fuera) { S.edicion.splice(i, 1) } else { l.fuera = !l.fuera }
      pintarLineas()
      programar()
    })
  })
}

/* ═══════════════════════════ VEREDICTO ═══════════════════════════ */

let temporizador = null

function programar () {
  clearTimeout(temporizador)
  temporizador = setTimeout(previsualizar, 350)
}

/** Lo que se manda: solo lo editable. Lo fiscal lo relee el servidor. */
function cuerpoLineas () {
  return S.edicion.filter(l => !l.fuera).map(l => ({
    linea_origen_id: l.linea_origen_id || 0,
    product_id: l.product_id,
    quantity: num(l.quantity),
    unit_price: num(l.unit_price),
    item_discount: num(l.item_discount),
  }))
}

function csrf (body) {
  const c = (window._nxaConfig && window._nxaConfig.csrf) || {}
  const nombre = window.CSRF_NAME || c.name
  const valor = window.CSRF_HASH || c.hash
  if (nombre && valor) body.set(nombre, valor)
  return body
}

function previsualizar () {
  const body = csrf(new FormData())
  body.set('lineas_json', JSON.stringify(cuerpoLineas()))

  // Una respuesta vieja no puede pisar a una nueva.
  const mio = Symbol('peticion')
  S.pidiendo = mio

  fetch(`${BASE()}ajuste/previsualizar/${window._nxaConfig.venta}`, {
    method: 'POST', credentials: 'same-origin', body,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(j => { if (S.pidiendo === mio) { S.veredicto = j; pintarPanel() } })
    .catch(() => { if (S.pidiendo === mio) pintarPanel(true) })
}

function pintarPanel (fallo) {
  const v = S.veredicto
  if (!v || fallo) {
    S.refs.panel.innerHTML = `<div class="nxa-panel"><div class="nxa-panel-cuerpo">
      <div class="nxa-vacio">${esc(t('error_carga'))}</div></div></div>`
    return
  }

  const tipo = v.tipo
  const color = TONO[tipo] || TONO.SIN_CAMBIOS
  const dif = v.delta.total
  const sentido = dif < 0 ? 'baja' : (dif > 0 ? 'sube' : '')
  const puede = S.permiso && S.permiso.puede && !v.bloqueo && tipo !== 'SIN_CAMBIOS'

  const rotuloTipo = {
    SIN_CAMBIOS: t('sin_cambios'), NOTA_CREDITO: t('nota_credito'),
    NOTA_DEBITO: t('nota_debito'), ANULACION: t('anulacion'),
  }[tipo] || tipo

  const avisos = (v.avisos || []).map(a => bloqueAviso(
    a === 'ajuste_aviso_mixto'
      ? tf('nota_cubre', money(v.total_nota), money(Math.abs(dif)))
      : t(a === 'ajuste_aviso_anulacion' ? 'confirmar_anulacion' : a),
    a === 'ajuste_aviso_mixto' ? 'var(--nx-orange)' : 'var(--nx-amber)'
  )).join('')

  const bloqueo = v.bloqueo
    ? bloqueAviso(v.bloqueo.mensaje_texto || textoBloqueo(v.bloqueo.clave), 'var(--nx-err)')
    : ''

  S.refs.panel.innerHTML = `<div class="nxa-panel">
    <div class="nxa-panel-cab">${ico(I.suma, 15)}${esc(t('tipo_operacion'))}</div>
    <div class="nxa-panel-cuerpo">
      <dl class="nxa-kv"><dt>${esc(t('total_original'))}</dt><dd>${money(v.original.total)}</dd></dl>
      <dl class="nxa-kv"><dt>${esc(t('total_actual'))}</dt><dd>${money(v.nuevo.total)}</dd></dl>
      <dl class="nxa-kv fuerte ${sentido}"><dt>${esc(t('diferencia'))}</dt>
        <dd>${dif > 0 ? '+' : ''}${money(dif)}</dd></dl>

      <div class="nxa-veredicto" style="--ver-c:${color}">
        <div class="nxa-veredicto-r">${esc(t('tipo_operacion'))}</div>
        <div class="nxa-veredicto-t">${esc(rotuloTipo)}</div>
        ${tipo !== 'SIN_CAMBIOS'
          ? `<div class="nxa-veredicto-s">${esc(t('importe'))}: <strong>${money(v.total_nota)}</strong></div>`
          : ''}
      </div>

      ${bloqueo}${avisos}
      ${desglose(v)}

      ${puede ? formulario(v) : ''}
    </div>
  </div>`

  if (puede) {
    S.refs.panel.querySelector('[data-a="emitir"]').addEventListener('click', emitir)
  }
}

function bloqueAviso (texto, color) {
  return `<div class="nxa-aviso" style="--av-c:${color}">${ico(I.alerta, 15)}<div>${esc(texto)}</div></div>`
}

function textoBloqueo (clave) {
  const m = {
    sin_cambios: t('sin_cambios'),
    sin_efecto: 'No existen diferencias económicas que requieran una nota.',
    excede_saldo: 'La corrección supera lo que queda por acreditar de esta factura.',
  }
  return m[clave] || clave
}

function desglose (v) {
  if (!v.cambios || !v.cambios.length) return ''
  const filas = v.cambios.map(c => {
    const r = { eliminada: t('eliminada'), agregada: t('agregada'), modificada: t('modificada') }[c.tipo] || c.tipo
    const s = c.monto < 0 ? 'baja' : 'sube'
    return `<div class="nxa-cambio"><span>${esc(c.linea)} <span style="color:var(--nx-txt4)">· ${esc(r)}</span></span>
      <b class="${s}">${c.monto > 0 ? '+' : ''}${money(c.monto)}</b></div>`
  }).join('')

  return `<div class="nxa-cambios">
    <div class="nxa-veredicto-r" style="margin-bottom:6px">${esc(t('desglose'))}</div>${filas}</div>`
}

function formulario (v) {
  // La anulacion no ofrece codigo: siempre es el 01 y no hay nada que elegir.
  const opciones = v.tipo === 'ANULACION' ? null : Object.keys(S.codigos)
    .map(k => `<option value="${esc(k)}"${k === v.codigo_referencia ? ' selected' : ''}>${esc(S.codigos[k])}</option>`)
    .join('')

  return `
    ${opciones ? `<div class="nxa-campo">
      <label for="nxaCodigo">${esc(t('codigo'))}</label>
      <select id="nxaCodigo" data-r="codigo">${opciones}</select>
    </div>` : ''}
    <div class="nxa-campo">
      <label for="nxaMotivo">${esc(t('motivo'))}</label>
      <textarea id="nxaMotivo" data-r="motivo" rows="3" placeholder="${esc(t('motivo_ph'))}"></textarea>
      <div class="nxa-ayuda">${esc(t('motivo_ayuda'))}</div>
    </div>
    <button type="button" class="nxa-btn${v.tipo === 'ANULACION' ? ' peli' : ''}" data-a="emitir">
      ${ico(I.send, 15)}${esc(t('emitir'))}
    </button>`
}

/* ═══════════════════════════ EMISION ═══════════════════════════ */

function confirmar (v) {
  const dif = money(Math.abs(v.delta.total))
  const texto = v.tipo === 'ANULACION' ? t('confirmar_anulacion')
    : (v.tipo === 'NOTA_CREDITO' ? tf('confirmar_credito', dif) : tf('confirmar_debito', dif))

  if (!window.Swal) return Promise.resolve(window.confirm(texto))
  return window.Swal.fire({
    title: { NOTA_CREDITO: t('nota_credito'), NOTA_DEBITO: t('nota_debito'), ANULACION: t('anulacion') }[v.tipo],
    text: texto, icon: v.tipo === 'ANULACION' ? 'warning' : 'question',
    showCancelButton: true, confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar',
    confirmButtonColor: v.tipo === 'ANULACION' ? '#dc2626' : '#0369a1',
    cancelButtonColor: '#6b7280', reverseButtons: true,
  }).then(r => r.isConfirmed)
}

function emitir () {
  if (S.ocupado || !S.veredicto) return

  const motivo = (S.refs.panel.querySelector('[data-r="motivo"]') || {}).value || ''
  if (motivo.trim().length < 5) {
    return aviso('warning', t('motivo_requerido'))
  }
  const codigo = (S.refs.panel.querySelector('[data-r="codigo"]') || {}).value || ''

  confirmar(S.veredicto).then(ok => {
    if (!ok) return

    S.ocupado = true
    const boton = S.refs.panel.querySelector('[data-a="emitir"]')
    const antes = boton.innerHTML
    boton.disabled = true
    boton.innerHTML = ico(I.gira, 15) + esc(t('emitiendo'))

    const body = csrf(new FormData())
    body.set('lineas_json', JSON.stringify(cuerpoLineas()))
    body.set('motivo', motivo.trim())
    if (codigo) body.set('codigo_referencia', codigo)

    fetch(`${BASE()}ajuste/emitir/${window._nxaConfig.venta}`, {
      method: 'POST', credentials: 'same-origin', body,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(r => r.json().then(j => ({ ok: r.ok, j })))
      .then(({ ok, j }) => {
        S.ocupado = false
        if (!ok || j.error) {
          boton.disabled = false
          boton.innerHTML = antes
          if (j.veredicto) { S.veredicto = j.veredicto; pintarPanel() }
          return aviso('error', j.error || t('error_carga'))
        }
        anunciar(j)
      })
      .catch(() => {
        S.ocupado = false
        boton.disabled = false
        boton.innerHTML = antes
        aviso('error', t('error_carga'))
      })
  })
}

function anunciar (j) {
  const n = j.nota
  const texto = tf('emitido', n.documento === 'ND' ? t('nota_debito') : t('nota_credito'),
    n.consecutivo, n.estado.etiqueta)

  if (!window.Swal) { window.location.href = n.url; return }
  window.Swal.fire({
    icon: 'success', title: texto,
    showCancelButton: true, confirmButtonText: t('ver_documento'), cancelButtonText: 'Cerrar',
    confirmButtonColor: '#0369a1', reverseButtons: true,
  }).then(r => {
    window.location.href = r.isConfirmed ? n.url : `${BASE()}sales`
  })
}

function aviso (tipo, mensaje) {
  if (typeof window.nxAlerta === 'function') return window.nxAlerta(tipo, mensaje)
  if (window.Swal) return window.Swal.fire({ icon: tipo, text: mensaje })
  return window.alert(mensaje)
}

/* ═══════════════════════ BUSCADOR DE PRODUCTOS ═══════════════════════ */

/*
   Se comporta como el del POS y usa su mismo endpoint (pos/suggestions), para
   que el cajero no tenga que aprender otra busqueda: un resultado unico entra
   solo —asi funciona el lector de barras—, varios abren la lista, y cualquier
   caracter tecleado fuera de un campo cae aca.
*/

let temporizadorBusqueda = null

function enlazarBuscador () {
  const caja = S.refs.q

  caja.addEventListener('input', () => {
    clearTimeout(temporizadorBusqueda)
    const term = caja.value.trim()
    if (!term) { cerrarSugerencias(); return }
    temporizadorBusqueda = setTimeout(() => buscar(term, false), 300)
  })

  caja.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault()
      clearTimeout(temporizadorBusqueda)
      const term = caja.value.trim()
      if (!term) return
      // Con la lista abierta, Enter toma el primero; si no, busca y decide.
      const primero = S.refs.sug.querySelector('button')
      if (primero) { primero.click(); return }
      buscar(term, true)
      return
    }
    if (e.key === 'ArrowDown') {
      const primero = S.refs.sug.querySelector('button')
      if (primero) { primero.focus(); e.preventDefault() }
      return
    }
    if (e.key === 'Escape') { cerrarSugerencias(); caja.value = '' }
  })

  S.refs.sug.addEventListener('keydown', e => {
    const items = Array.from(S.refs.sug.querySelectorAll('button'))
    const i = items.indexOf(document.activeElement)
    if (e.key === 'ArrowDown' && i < items.length - 1) { items[i + 1].focus(); e.preventDefault() }
    else if (e.key === 'ArrowUp') { (i > 0 ? items[i - 1] : caja).focus(); e.preventDefault() }
    else if (e.key === 'Escape') { cerrarSugerencias(); caja.focus(); caja.value = '' }
  })

  document.addEventListener('click', e => {
    if (!S.refs.buscar.contains(e.target)) cerrarSugerencias()
  })

  capturarEscaner(caja)
}

/**
 * El lector de barras teclea como una persona, y el cajero rara vez deja el
 * cursor en la busqueda: cualquier caracter escrito fuera de un campo se
 * redirige aca, igual que en el POS.
 */
function capturarEscaner (caja) {
  document.addEventListener('keydown', e => {
    if (e.altKey || e.ctrlKey || e.metaKey || e.key.length !== 1) return
    if (S.refs.buscar.hidden) return

    const act = document.activeElement
    if (act === caja) return
    if (act && (act.isContentEditable || act.tagName === 'INPUT' ||
                act.tagName === 'TEXTAREA' || act.tagName === 'SELECT')) return

    caja.focus()
  })
}

function cerrarSugerencias () {
  S.refs.sug.innerHTML = ''
}

/** @param {boolean} confirmado true cuando el cajero apreto Enter */
function buscar (term, confirmado) {
  fetch(`${BASE()}pos/suggestions?term=${encodeURIComponent(term)}`, {
    credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then(r => r.json())
    .then(datos => {
      // Sin resultados el POS devuelve una fila con id 0, no una lista vacia.
      const vacio = !Array.isArray(datos) || !datos.length || !datos[0].item_id
      if (vacio) {
        cerrarSugerencias()
        if (confirmado) {
          aviso('warning', t('sin_resultados'))
          S.refs.q.value = ''
        }
        return
      }
      // Entra solo cuando no hay nada que elegir: el codigo completo del
      // lector, o lo que el cajero confirmo con Enter. Escribiendo a mano se
      // sugiere, aunque haya un unico resultado.
      const exacto = String(datos[0].codigo || '').toLowerCase() === term.toLowerCase()
      if (datos.length === 1 && (confirmado || exacto)) { agregar(datos[0]); return }
      mostrarSugerencias(datos)
    })
    .catch(() => cerrarSugerencias())
}

function mostrarSugerencias (datos) {
  S.refs.sug.innerHTML = datos.map((p, i) => {
    const ex = Math.round((Number(p.existencias) || 0) * 1000) / 1000
    return `<button type="button" data-i="${i}"${p.ubicacion ? ` title="${esc(p.ubicacion)}"` : ''}>
      <span>${esc(p.nombre)}<span style="color:var(--nx-txt4)"> · ${esc(p.codigo)}</span></span>
      <span class="p">${esc(p.precio_fmt || money(p.precio))}
        <span style="color:var(--nx-txt4);font-weight:500"> · ${ex}</span></span>
    </button>`
  }).join('')

  S.refs.sug.querySelectorAll('button').forEach(b => {
    b.addEventListener('click', () => agregar(datos[+b.dataset.i]))
  })
}

/**
 * Agrega el producto al documento en edicion.
 *
 * El precio que se pinta es el de la ficha sin impuesto —el mismo que el
 * servidor va a releer al comparar—, no el `precio` de la sugerencia, que ya
 * viene con el impuesto sumado.
 */
function agregar (p) {
  const fila = p.row || {}
  const neto = parseFloat(fila.real_unit_price ?? fila.price ?? p.precio) || 0

  S.edicion.push({
    linea_origen_id: 0,
    product_id: parseInt(p.item_id, 10) || 0,
    product_code: p.codigo,
    product_name: p.nombre,
    quantity: 1,
    unit_price: neto,
    item_discount: 0,
    // La tarifa no viaja en la sugerencia: la resuelve el servidor desde
    // tec_impuestos al comparar, y es la que acaba en el XML.
    tax: '',
    cabys: fila.cabys || '',
    unit_of_measurement: p.unidad || 'Unid',
    nueva: true, fuera: false,
  })

  S.refs.q.value = ''
  cerrarSugerencias()
  pintarLineas()
  programar()
  S.refs.q.focus()
}

document.addEventListener('DOMContentLoaded', iniciar)

export { iniciar }

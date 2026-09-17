/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

/* ═══════════════════════════════════════════════════════════════════════════
   NX-REPORTES.JS — Cliente del Centro de Inteligencia

   No se bundlea: se copia tal cual de src/ a dist/, como pos-core.js, para que
   editarlo no obligue a rehacer el build entero.

   Todo lo que pinta sale del mismo documento que el servidor manda al PDF y al
   Excel. La pantalla no recalcula nada: si sumara por su cuenta volveríamos a
   tener tres verdades para el mismo período.
   ═══════════════════════════════════════════════════════════════════════════ */

;(function () {
  'use strict'

  var S = function () { return window._appSettings || {} }

  /* ── Formato, tomado de la configuración del sistema ── */
  function num (x, d) {
    var n = parseFloat(x); if (isNaN(n)) n = 0
    if (d === undefined || d === null) d = parseInt(S().decimals || 2, 10)
    var ts = (S().thousands_sep == 0 ? ' ' : (S().thousands_sep || ','))
    var ds = S().decimals_sep || '.'
    var p = Math.abs(n).toFixed(d).split('.')
    p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, ts)
    return (n < 0 ? '-' : '') + p[0] + (p[1] ? ds + p[1] : '')
  }
  function money (x) {
    var sym = S().symbol || ''
    var v = num(x, parseInt(S().decimals || 2, 10))
    return S().display_symbol == 2 ? v + sym : sym + v
  }
  function esc (s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;')
  }

  /**
   * Aplica el formato que declaró la columna en el servidor.
   *
   * Es la misma tabla de formatos que usan el PDF y el Excel: la columna dice
   * `moneda` una vez y las tres salidas la pintan igual.
   */
  function celda (valor, formato) {
    if (valor === null || valor === undefined || valor === '') return ''
    switch (formato) {
      case 'moneda':     return money(valor)
      case 'numero':     return num(valor, 2)
      case 'entero':     return num(valor, 0)
      case 'porcentaje': return num(valor, 2) + ' %'
      case 'fecha':      return fecha(valor, false)
      case 'fechahora':  return fecha(valor, true)
      case 'estado':     return badge(valor)
      case 'texto':      return '<span class="nxt-code">' + esc(valor) + '</span>'
      default:           return esc(valor)
    }
  }

  function fecha (v, conHora) {
    var d = new Date(String(v).replace(' ', 'T'))
    if (isNaN(d.getTime())) return esc(v)
    var p = function (n) { return String(n).padStart(2, '0') }
    var s = p(d.getDate()) + '/' + p(d.getMonth() + 1) + '/' + d.getFullYear()
    return conHora ? s + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) : s
  }

  var TONOS = {
    aceptado: 'ok', anulado: 'muted', rechazado: 'err', error: 'err',
    procesando: 'warn', pendiente: 'warn', 'Sin Estado': 'muted', '(sin enviar)': 'muted',
    critico: 'err', 'Crítico': 'err', alto: 'orange', Alto: 'orange',
    medio: 'warn', Medio: 'warn', bajo: 'info', Bajo: 'info'
  }
  function badge (v) {
    if (!v) return ''
    var t = TONOS[v] || 'muted'
    return window.NxTable ? window.NxTable.badge(v, t) : esc(v)
  }

  /** Alineación de la columna, según su formato. */
  function alinea (formato) {
    if (['moneda', 'numero', 'entero', 'porcentaje'].indexOf(formato) >= 0) return 'num'
    if (['fecha', 'fechahora', 'estado'].indexOf(formato) >= 0) return 'ctr'
    return ''
  }

  /* ═════════════════════ FILTROS ═════════════════════ */

  var Filtros = {
    form: null,

    iniciar: function (onAplicar) {
      this.form = document.getElementById('nxrFiltros')
      if (!this.form) return
      var self = this

      this.form.addEventListener('submit', function (e) {
        e.preventDefault()
        onAplicar()
      })
      this.form.addEventListener('reset', function () {
        // El reset del navegador es asíncrono respecto al listener.
        setTimeout(function () { onAplicar() }, 0)
      })

      this.form.querySelectorAll('.nxr-atajo').forEach(function (b) {
        b.addEventListener('click', function () {
          self.rango(b.dataset.rango)
          self.form.querySelectorAll('.nxr-atajo').forEach(function (x) { x.classList.remove('on') })
          b.classList.add('on')
          onAplicar()
        })
      })
    },

    /** Rellena las dos fechas con un rango de uso frecuente. */
    rango: function (clave) {
      var hoy = new Date()
      var a, b
      var iso = function (d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') +
               '-' + String(d.getDate()).padStart(2, '0')
      }
      switch (clave) {
        case 'hoy':     a = b = hoy; break
        case 'ayer':    a = b = new Date(hoy.getTime() - 86400000); break
        case 'semana':  a = new Date(hoy.getTime() - 6 * 86400000); b = hoy; break
        case 'mes':     a = new Date(hoy.getFullYear(), hoy.getMonth(), 1); b = hoy; break
        case 'mes_ant':
          a = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1)
          // Día 0 del mes actual es el último del anterior: evita el 31 de febrero.
          b = new Date(hoy.getFullYear(), hoy.getMonth(), 0)
          break
        case 'anio':    a = new Date(hoy.getFullYear(), 0, 1); b = hoy; break
        default: return
      }
      var d = this.form.querySelector('[name=start_date]')
      var h = this.form.querySelector('[name=end_date]')
      if (d) d.value = iso(a)
      if (h) h.value = iso(b)
    },

    /**
     * Cuerpo del POST.
     *
     * El token se toma de `window.CSRF_HASH`, no del que imprimió la vista:
     * `csrf_regenerate` está activo y el valor original sirve una sola vez, así
     * que la segunda consulta de la misma pantalla moriría con 403.
     */
    cuerpo: function (extra) {
      var b = new URLSearchParams()
      var nombre = window._nxrCsrfName
      if (this.form) {
        new FormData(this.form).forEach(function (v, k) {
          if (k === nombre) return          // el token se pone al final, ya fresco
          if (String(v).trim() !== '') b.append(k, v)
        })
      }
      if (nombre) b.set(nombre, token())
      Object.keys(extra || {}).forEach(function (k) { b.set(k, extra[k]) })
      return b
    },

    /** Los mismos filtros como cadena de consulta, para abrir PDF o Excel. */
    query: function () {
      var b = this.cuerpo({})
      b.delete(window._nxrCsrfName)
      return b.toString()
    },

    /** Barra de estado. El texto se acota: el detalle va en el panel de error. */
    estado: function (txt, tono) {
      var e = document.getElementById('nxrEstado')
      if (!e) return
      var t = String(txt || '')
      e.textContent = t.length > 140 ? t.slice(0, 140) + '…' : t
      e.title = t
      e.className = 'nxr-estado' + (tono ? ' ' + tono : '')
    }
  }

  /* ═════════════════════ RED ═════════════════════ */

  /**
   * Token vigente.
   *
   * `csrf_regenerate` está activo: el valor que imprimió la vista sirve una
   * sola vez. Se prefiere el que dejó la última respuesta y solo se cae al del
   * formulario en la primera petición de la pantalla.
   */
  function token () {
    if (window.CSRF_HASH) return window.CSRF_HASH
    var i = document.querySelector('input[name="' + window._nxrCsrfName + '"]')
    return i ? i.value : ''
  }

  /**
   * Guarda el token que devolvió el servidor.
   *
   * `main.js` ya envuelve `window.fetch` para hacerlo, pero este módulo no puede
   * depender de que ese envoltorio esté instalado antes: si la primera petición
   * sale sin él, la segunda va con un token gastado y muere con 403.
   */
  function guardarToken (r) {
    var nuevo = r && r.headers && r.headers.get('X-CSRF-Token')
    if (!nuevo) return
    window.CSRF_HASH = nuevo
    document.querySelectorAll('input[name="' + window._nxrCsrfName + '"]')
      .forEach(function (i) { i.value = nuevo })
  }

  /* Una petición a la vez: dos en vuelo comparten token y una de las dos
     se encuentra con que ya se regeneró. */
  var enVuelo = Promise.resolve()

  /**
   * POST que siempre acaba en un objeto o en un error legible.
   *
   * Sin comprobar `response.ok` un 403 de CSRF o un 500 pasan por respuesta
   * válida y la pantalla dice que todo salió bien con la tabla vacía.
   *
   * @param {Function} cuerpo función que arma el cuerpo en el momento del envío,
   *                          para que tome el token ya refrescado por la
   *                          petición anterior y no uno capturado antes
   */
  function pedir (url, cuerpo) {
    var armar = typeof cuerpo === 'function' ? cuerpo : function () { return cuerpo }

    var salida = enVuelo.then(function () { return enviar(url, armar, true) })
    // La cola no se rompe cuando una petición falla.
    enVuelo = salida.catch(function () {})
    return salida
  }

  /**
   * Pide un token vigente.
   *
   * Una respuesta 403 no trae la cabecera `X-CSRF-Token` —CSRF falla antes de
   * llegar al controlador— y la cookie es `httponly`, así que no queda otra
   * fuente. Este `GET` no consume ningún token y devuelve el actual.
   */
  function renovarToken () {
    return fetch(window._nxrBase + 'reportes/token', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    }).then(guardarToken).catch(function () {})
  }

  function enviar (url, armar, reintentar) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: armar().toString(),
      credentials: 'same-origin'
    }).then(function (r) {
      guardarToken(r)
      return r.text().then(function (txt) {
        // El 403 de CSRF llega como la pantalla de error de CI, no como JSON, y
        // sin cabecera con la que refrescarse: hay que ir a buscar el token.
        if (r.status === 403 && reintentar) {
          return renovarToken().then(function () { return enviar(url, armar, false) })
        }

        var j
        try { j = JSON.parse(txt) } catch (e) {
          throw new Error(r.ok
            ? 'El servidor respondió algo que no es JSON.'
            : mensajeHttp(r.status, txt))
        }
        if (!r.ok) throw new Error(j.motivo || j.error || ('HTTP ' + r.status))
        if (j && j.error) throw new Error(j.error)
        return j
      })
    })
  }

  /** Mensaje corto: volcar la página de error entera en la barra no dice nada. */
  function mensajeHttp (estado, cuerpo) {
    if (estado === 403) return 'La sesión caducó o el token no era válido. Recargá la pantalla.'
    if (estado === 401) return 'La sesión se cerró. Volvé a entrar.'
    if (estado === 404) return 'El informe solicitado no existe.'

    var t = document.createElement('div')
    t.innerHTML = String(cuerpo || '').replace(/<(script|style)[\s\S]*?<\/\1>/gi, '')
    var texto = (t.textContent || '').replace(/\s+/g, ' ').trim()
    return 'HTTP ' + estado + (texto ? ' — ' + texto.slice(0, 180) : '')
  }

  /* ═════════════════════ COMPONENTES ═════════════════════ */

  function kpis (lista) {
    if (!lista || !lista.length) return ''
    return '<div class="nxt-kpis">' + lista.map(function (k) {
      return '<div class="nxt-kpi' + (k.tono ? ' nxr-' + k.tono : '') + '">' +
        '<div class="nxt-kpi-label">' + esc(k.etiqueta) + '</div>' +
        '<div class="nxt-kpi-value">' + esc(k.texto) + '</div>' +
        (k.pie ? '<div class="nxt-kpi-sub">' + esc(k.pie) + '</div>' : '') +
        '</div>'
    }).join('') + '</div>'
  }

  function confiabilidad (c) {
    if (!c) return ''
    return '<div class="nxr-conf nxr-' + esc(c.tono) + '">' +
      '<div class="nxr-conf-v">' + num(c.pct, 1) + '<small>%</small></div>' +
      '<div class="nxr-conf-t"><b>Índice de confiabilidad</b><p>' + esc(c.resumen) + '</p>' +
      (c.descuentos && c.descuentos.length
        ? '<ul class="nxr-conf-d">' + c.descuentos.map(function (d) {
            return '<li><span>−' + num(d.descuento, 1) + '</span> ' + esc(d.titulo) +
                   ' <em>(' + d.hallazgos + ')</em></li>'
          }).join('') + '</ul>'
        : '') +
      '</div></div>'
  }

  function analisis (lista) {
    if (!lista || !lista.length) return ''
    return '<div class="nxr-analisis"><h4>Lectura del período</h4><ul>' +
      lista.map(function (t) { return '<li>' + esc(t) + '</li>' }).join('') + '</ul></div>'
  }

  /**
   * Tabla del informe.
   *
   * La fila de totales usa lo que mandó el servidor, no una suma del navegador:
   * es la garantía de que la pantalla, el PDF y el Excel muestren el mismo pie.
   */
  function tabla (doc, filtroTexto) {
    var cols = doc.columnas || []
    var filas = doc.filas || []

    if (filtroTexto) {
      var q = filtroTexto.toLowerCase()
      filas = filas.filter(function (r) {
        return cols.some(function (c) {
          return String(r[c.clave] == null ? '' : r[c.clave]).toLowerCase().indexOf(q) >= 0
        })
      })
    }

    var th = cols.map(function (c) {
      return '<th class="' + alinea(c.formato) + '">' + esc(c.titulo) + '</th>'
    }).join('')

    var tb = filas.length
      ? filas.map(function (r) {
          return '<tr>' + cols.map(function (c) {
            return '<td class="' + alinea(c.formato) + '">' + celda(r[c.clave], c.formato) + '</td>'
          }).join('') + '</tr>'
        }).join('')
      : '<tr><td colspan="' + cols.length + '" class="nxr-vacio">' +
        'El período y los filtros seleccionados no devolvieron ningún registro.</td></tr>'

    // Con la búsqueda puesta la fila de totales dejaría de corresponder al pie
    // del servidor; se recalcula sobre lo visible y se dice que es un subtotal.
    var tf = ''
    var totales = doc.totales || {}
    if (Object.keys(totales).length) {
      var propio = filtroTexto ? {} : totales
      if (filtroTexto) {
        cols.forEach(function (c) {
          if (!c.total) return
          propio[c.clave] = filas.reduce(function (a, r) { return a + (parseFloat(r[c.clave]) || 0) }, 0)
        })
      }
      var primera = true
      tf = '<tfoot><tr>' + cols.map(function (c) {
        var v
        if (Object.prototype.hasOwnProperty.call(propio, c.clave)) {
          v = celda(propio[c.clave], c.formato === 'entero' ? 'entero' : 'moneda')
        } else {
          v = primera ? (filtroTexto ? 'SUBTOTAL' : 'TOTALES') : ''
        }
        primera = false
        return '<td class="' + alinea(c.formato) + '">' + v + '</td>'
      }).join('') + '</tr></tfoot>'
    }

    return '<div class="nxt-card"><div class="nxt-table-wrap">' +
      '<table class="nxt-table"><thead><tr>' + th + '</tr></thead><tbody>' + tb + '</tbody>' + tf +
      '</table></div><div class="nxt-foot"><span class="nxt-foot-info">' +
      num(filas.length, 0) + ' de ' + num((doc.filas || []).length, 0) + ' registros</span>' +
      '<span class="nxr-folio">' + esc(doc.folio || '') + '</span></div></div>'
  }

  function semaforos (lista) {
    if (!lista || !lista.length) return ''
    return '<div class="nxr-sem">' + lista.map(function (s) {
      return '<a class="nxr-sem-i nxr-' + esc(s.tono) + '" href="' + esc(window._nxrBase + s.ir) + '">' +
        '<span class="nxr-sem-ico">' + s.icono + '</span>' +
        '<span class="nxr-sem-a">' + esc(s.area) + '</span>' +
        '<span class="nxr-sem-d">' + esc(s.detalle) + '</span></a>'
    }).join('') + '</div>'
  }

  function integridad (i) {
    if (!i) return ''
    return '<div class="nxt-card"><div class="nxt-table-wrap"><table class="nxt-table">' +
      '<thead><tr><th></th><th>Comparación</th><th class="num">Valor A</th><th class="num">Valor B</th>' +
      '<th class="num">Diferencia</th><th>Qué significa</th></tr></thead><tbody>' +
      i.lineas.map(function (l) {
        return '<tr><td class="ctr">' + l.icono + '</td><td><b>' + esc(l.concepto) + '</b><br>' +
          '<small class="nxr-dim">' + esc(l.a_texto) + ' · ' + esc(l.b_texto) + '</small></td>' +
          '<td class="num">' + money(l.a) + '</td><td class="num">' + money(l.b) + '</td>' +
          '<td class="num nxr-' + esc(l.tono) + '">' + money(l.diferencia) + '</td>' +
          '<td><small>' + esc(l.nota) + '</small></td></tr>'
      }).join('') +
      '</tbody></table></div><div class="nxt-foot"><span class="nxt-foot-info">' +
      i.cuadran + ' de ' + i.total + ' comparaciones cuadran · integridad ' + num(i.pct, 1) + ' %' +
      '</span></div></div>'
  }

  function anomalias (lista, limite) {
    if (!lista || !lista.length) {
      return '<div class="nxr-ok-vacio">🟢 No se detectaron anomalías en el período.</div>'
    }
    var muestra = limite ? lista.slice(0, limite) : lista
    return '<div class="nxt-card"><div class="nxt-table-wrap"><table class="nxt-table">' +
      '<thead><tr><th>Nivel</th><th>Anomalía</th><th>Detalle</th><th>Documento</th>' +
      '<th class="num">Monto</th><th>Acción recomendada</th></tr></thead><tbody>' +
      muestra.map(function (a) {
        return '<tr><td>' + badge(a.nivel, a.tono) + '</td>' +
          '<td><b>' + esc(a.titulo) + '</b><br><small class="nxr-dim">' + esc(a.causa) + '</small></td>' +
          '<td>' + esc(a.descripcion) + '</td>' +
          '<td><span class="nxt-code">' + esc(a.documento) + '</span></td>' +
          '<td class="num">' + money(a.monto) + '</td>' +
          '<td><small>' + esc(a.accion) + '</small></td></tr>'
      }).join('') +
      '</tbody></table></div><div class="nxt-foot"><span class="nxt-foot-info">' +
      'Mostrando ' + muestra.length + ' de ' + lista.length + ' hallazgos</span></div></div>'
    }

  /* ── Gráfico de barras en SVG, sin librerías ── */
  function barras (filas, clave, valor, titulo, formato) {
    if (!filas || !filas.length) return ''
    var max = Math.max.apply(null, filas.map(function (r) { return Math.abs(parseFloat(r[valor]) || 0) }))
    if (!max) return ''
    return '<div class="nxr-graf"><h4>' + esc(titulo) + '</h4>' +
      filas.map(function (r) {
        var v = parseFloat(r[valor]) || 0
        var p = Math.max(1, Math.abs(v) / max * 100)
        return '<div class="nxr-barra">' +
          '<span class="nxr-barra-l" title="' + esc(r[clave]) + '">' + esc(r[clave]) + '</span>' +
          '<span class="nxr-barra-t"><i style="width:' + p.toFixed(2) + '%"></i></span>' +
          '<span class="nxr-barra-v">' + (formato === 'entero' ? num(v, 0) : money(v)) + '</span>' +
          '</div>'
      }).join('') + '</div>'
  }

  window.NxReportes = {
    Filtros: Filtros, pedir: pedir, esc: esc, money: money, num: num, fecha: fecha,
    celda: celda, badge: badge, alinea: alinea,
    kpis: kpis, tabla: tabla, analisis: analisis, confiabilidad: confiabilidad,
    semaforos: semaforos, integridad: integridad, anomalias: anomalias, barras: barras
  }
})()

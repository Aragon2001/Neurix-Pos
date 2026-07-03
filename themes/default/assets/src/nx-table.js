/* ═══════════════════════════════════════════════════════════════════════════
   NX-TABLE.JS — Motor reusable de listados Neurix (diseño .nxt-* de nx-tables.css)
   Reemplaza el patrón legacy jQuery + DataTables/Tabulator de las vistas.

   Uso en una vista:
     new NxTable({
       el: '#miLista',                    // contenedor vacío
       url: '.../modulo/get_x',           // endpoint Ignited-Datatables (POST)
       csrf: { name: '...', hash: '...' },
       columns: [
         { key:'name', label:'Nombre', render: r => ..., sortable:'str' },
         { key:'total', label:'Total', className:'num', sortable:'num',
           render: r => NxTable.money(r.total) },
         { key:'Actions', label:'Acciones', actions:true },   // re-estiliza HTML del server
       ],
       search: ['name','code'],           // claves para el buscador
       chips: { key:'status', all:'Todas', label: v => ... }, // filtro por chips (opcional)
       totals: ['total','paid'],          // fila de totales sobre lo filtrado (opcional)
       perPage: 25,
       unit: 'ventas',                    // para el contador
       exportName: 'ventas',
       onData: rows => {...},             // hook para KPIs
       map: raw => raw,                   // transformar fila cruda (opcional)
     })
   ═══════════════════════════════════════════════════════════════════════════ */

const S = () => window._appSettings || {}

/* ── Formateadores según configuración del sistema ── */
function fmtNum (x, d) {
  let n = parseFloat(x); if (isNaN(n)) n = 0
  if (d === undefined || d === null) d = parseInt(S().decimals || 2)
  const ts = (S().thousands_sep == 0 ? ' ' : (S().thousands_sep || ','))
  const ds = S().decimals_sep || '.'
  const parts = Math.abs(n).toFixed(d).split('.')
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ts)
  return (n < 0 ? '-' : '') + parts[0] + (parts[1] ? ds + parts[1] : '')
}
function fmtMoney (x) {
  const sym = S().symbol || ''
  const v = fmtNum(x, parseInt(S().decimals || 2))
  return S().display_symbol == 2 ? v + sym : sym + v
}
function fmtQty (x) { return fmtNum(x, parseInt(S().qty_decimals || 0)) }

function esc (s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;')
}

/* Badge de estado: NxTable.badge('Pagado','ok') → píldora coloreada */
const BADGE_COLORS = {
  ok:    'var(--nx-emerald)',
  info:  'var(--nx-a1)',
  warn:  'var(--nx-amber)',
  err:   'var(--nx-err)',
  muted: 'var(--nx-slate)',
  violet:'var(--nx-violet)',
  orange:'var(--nx-orange)',
}
function badge (text, tone) {
  const c = BADGE_COLORS[tone] || BADGE_COLORS.muted
  return `<span class="nxt-cat" style="--cat-c:${c}">${esc(text)}</span>`
}

/* Celda entidad (avatar iniciales + nombre + meta) */
const AV_PALETTE = [
  ['#0ea5e9', '#38bdf8'], ['#9333ea', '#c084fc'], ['#d97706', '#fbbf24'],
  ['#6366f1', '#a5b4fc'], ['#ea580c', '#fb923c'], ['#059669', '#34d399'],
  ['#db2777', '#f472b6'], ['#0d9488', '#2dd4bf'], ['#dc2626', '#f87171'],
  ['#4f46e5', '#818cf8'],
]
function initials (s) {
  const w = String(s || '').trim().split(/\s+/).filter(x => /^[A-Za-zÁÉÍÓÚÑáéíóúñ0-9]/.test(x))
  return (w.slice(0, 2).map(x => x[0]).join('') || String(s || '??').slice(0, 2)).toUpperCase()
}
function hashColor (s) {
  let h = 0; const str = String(s || '')
  for (let i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) | 0
  return AV_PALETTE[Math.abs(h) % AV_PALETTE.length]
}
function entity (name, meta, colorKey) {
  const [a, b] = hashColor(colorKey || name)
  return `<div class="nxt-ent"><div class="nxt-avatar" style="--av1:${a};--av2:${b}">${esc(initials(name))}</div>` +
    `<div><div class="nxt-ent-name">${esc(name)}</div>${meta ? `<div class="nxt-ent-meta">${esc(meta)}</div>` : ''}</div></div>`
}

/* ── Re-estiliza el HTML de acciones que genera el servidor (btn-xs + fa) ── */
function restyleActions (html) {
  if (!html) return ''
  const tpl = document.createElement('template')
  tpl.innerHTML = html
  const out = []
  tpl.content.querySelectorAll('a').forEach(a => {
    let tone = ''
    const cls = a.className || ''
    if (/btn-warning/.test(cls)) tone = ' warn'
    if (/btn-danger/.test(cls)) tone = ' danger'
    const icon = a.querySelector('i')
    const attrs = []
    for (const at of a.attributes) {
      if (at.name === 'class' || at.name === 'style') continue
      attrs.push(`${at.name}="${esc(at.value)}"`)
    }
    out.push(`<a class="nxt-icon-btn${tone}" ${attrs.join(' ')}>${icon ? icon.outerHTML : ''}</a>`)
  })
  return `<div class="nxt-actions">${out.join('')}</div>`
}

/* ── Handler global para data-toggle="ajax" / "ajax-modal" (legacy) ── */
let modalHandlerInstalled = false
function installModalHandler () {
  if (modalHandlerInstalled) return
  modalHandlerInstalled = true
  document.addEventListener('click', e => {
    const a = e.target.closest('[data-toggle="ajax"],[data-toggle="ajax-modal"],[data-toggle="ajax2"]')
    if (!a || !a.href) return
    e.preventDefault()
    fetch(a.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.text())
      .then(htm => {
        const modal = document.getElementById('myModal')
        if (!modal) return
        modal.innerHTML = htm
        window.bootstrap.Modal.getOrCreateInstance(modal).show()
        // ejecutar <script> embebidos (innerHTML no los ejecuta)
        modal.querySelectorAll('script').forEach(old => {
          const s = document.createElement('script')
          if (old.src) s.src = old.src; else s.textContent = old.textContent
          old.replaceWith(s)
        })
      })
  })
}

/* ══════════════════════════ CLASE PRINCIPAL ══════════════════════════ */
class NxTable {
  constructor (opts) {
    this.o = Object.assign({
      perPage: 25, columns: [], search: [], unit: '', exportName: 'export',
      i18n: {},
    }, opts)
    this.i18n = Object.assign({
      searchPlaceholder: 'Buscar…',
      loading: 'Cargando datos…',
      empty: 'No hay resultados que coincidan con la búsqueda.',
      showing: 'Mostrando', of: 'de', all: 'Todas', totals: 'Totales',
    }, opts.i18n || {})
    this.data = []
    this.query = ''
    this.chip = '*'
    this.sortK = null
    this.sortDir = 1
    this.page = 1
    this.root = typeof this.o.el === 'string' ? document.querySelector(this.o.el) : this.o.el
    if (!this.root) return
    installModalHandler()
    this.buildSkeleton()
    this.load()
  }

  /* ── markup base dentro del contenedor ── */
  buildSkeleton () {
    const cols = this.o.columns
    const ths = cols.map((c, i) => {
      const cl = [c.className || '', c.sortable ? 'sortable' : ''].join(' ').trim()
      return `<th${cl ? ` class="${cl}"` : ''}${c.width ? ` style="width:${c.width}"` : ''} data-i="${i}">` +
        `${esc(c.label || '')}${c.sortable ? ' <span class="arrow">↕</span>' : ''}</th>`
    }).join('')
    this.root.innerHTML =
      `<div class="nxt-card">
        <div class="nxt-toolbar">
          <div class="nxt-search">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="7"/><path d="M21 21l-6-6"/></svg>
            <input type="search" placeholder="${esc(this.i18n.searchPlaceholder)}" autocomplete="off">
          </div>
          <div class="nxt-chips" style="display:none"></div>
          <span class="nxt-count"></span>
        </div>
        <div class="nxt-table-wrap">
          <table class="nxt-table" style="min-width:${this.o.minWidth || '900px'}">
            <thead><tr>${ths}</tr></thead>
            <tbody></tbody>
            <tfoot></tfoot>
          </table>
          <div class="nxt-loading">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
            ${esc(this.i18n.loading)}
          </div>
          <div class="nxt-empty" style="display:none">${esc(this.i18n.empty)}</div>
        </div>
        <div class="nxt-foot"><span class="nxt-foot-info"></span><div class="nxt-pages"></div></div>
      </div>`

    this.$ = sel => this.root.querySelector(sel)
    this.$('.nxt-search input').addEventListener('input', e => {
      this.query = e.target.value.toLowerCase().trim(); this.page = 1; this.render()
    })
    this.$('.nxt-chips').addEventListener('click', e => {
      const b = e.target.closest('.nxt-chip'); if (!b) return
      this.root.querySelectorAll('.nxt-chip').forEach(c => c.classList.remove('active'))
      b.classList.add('active'); this.chip = b.dataset.v; this.page = 1; this.render()
    })
    this.root.querySelectorAll('th.sortable').forEach(th => {
      th.addEventListener('click', () => {
        const i = parseInt(th.dataset.i)
        const c = this.o.columns[i]
        const k = c.sortKey || c.key
        this.sortDir = (this.sortK === k) ? -this.sortDir : 1
        this.sortK = k; this.sortType = c.sortable
        this.root.querySelectorAll('th.sortable .arrow').forEach(a => { a.textContent = '↕' })
        th.querySelector('.arrow').textContent = this.sortDir === 1 ? '↑' : '↓'
        this.page = 1; this.render()
      })
    })
  }

  /* ── fetch: protocolo DataTables mínimo, todo el dataset ── */
  load () {
    /* datos locales (tabla server-rendered sin endpoint) */
    if (Array.isArray(this.o.data)) {
      let rows = this.o.data
      if (this.o.map) rows = rows.map(this.o.map)
      this.data = rows
      this.$('.nxt-loading').style.display = 'none'
      if (this.o.chips) this.buildChips()
      if (this.o.onData) this.o.onData(rows)
      this.render()
      return
    }
    const body = new URLSearchParams()
    if (this.o.csrf) body.append(this.o.csrf.name, this.o.csrf.hash)
    body.append('draw', '1'); body.append('start', '0'); body.append('length', '-1')
    body.append('search[value]', ''); body.append('search[regex]', 'false')
    body.append('columns[0][data]', this.o.columns[0] && this.o.columns[0].key || 'id')
    body.append('columns[0][name]', ''); body.append('columns[0][searchable]', 'false')
    body.append('columns[0][orderable]', 'false')
    body.append('columns[0][search][value]', ''); body.append('columns[0][search][regex]', 'false')

    fetch(this.o.url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: body.toString(),
      credentials: 'same-origin',
    })
      .then(r => r.json())
      .then(json => {
        let rows = json.data || []
        if (this.o.map) rows = rows.map(this.o.map)
        this.data = rows
        this.$('.nxt-loading').style.display = 'none'
        if (this.o.chips) this.buildChips()
        if (this.o.onData) this.o.onData(rows)
        this.render()
      })
      .catch(err => {
        this.$('.nxt-loading').innerHTML = `<span style="color:var(--nx-err)">Error: ${esc(err.message)}</span>`
      })
  }

  buildChips () {
    const cfg = this.o.chips
    const box = this.$('.nxt-chips')
    const values = [...new Set(this.data.map(r => r[cfg.key]).filter(v => v != null && v !== ''))]
    if (cfg.sort !== false) values.sort((a, b) => String(a).localeCompare(String(b)))
    box.style.display = ''
    box.innerHTML = `<button class="nxt-chip active" data-v="*">${esc(cfg.all || this.i18n.all)}</button>` +
      values.map(v => `<button class="nxt-chip" data-v="${esc(v)}">${esc(cfg.label ? cfg.label(v) : v)}</button>`).join('')
  }

  filtered () {
    const q = this.query
    const cfg = this.o.chips
    let list = this.data.filter(r =>
      (this.chip === '*' || !cfg || String(r[cfg.key]) === this.chip) &&
      (!q || this.o.search.some(k => String(r[k] == null ? '' : r[k]).toLowerCase().includes(q)))
    )
    if (this.sortK) {
      const k = this.sortK; const dir = this.sortDir
      list = list.slice().sort((a, b) => {
        if (this.sortType === 'num') return ((parseFloat(a[k]) || 0) - (parseFloat(b[k]) || 0)) * dir
        return String(a[k] == null ? '' : a[k]).localeCompare(String(b[k] == null ? '' : b[k])) * dir
      })
    }
    return list
  }

  render () {
    const list = this.filtered()
    const pages = Math.max(1, Math.ceil(list.length / this.o.perPage))
    if (this.page > pages) this.page = pages
    const slice = list.slice((this.page - 1) * this.o.perPage, this.page * this.o.perPage)

    this.$('tbody').innerHTML = slice.map(r => {
      const rowAttr = this.o.rowAttr ? this.o.rowAttr(r) : ''
      return `<tr ${rowAttr}>` + this.o.columns.map(c => {
        let content
        if (c.actions) content = restyleActions(r[c.key])
        else if (c.render) content = c.render(r)
        else content = esc(r[c.key])
        return `<td${c.className ? ` class="${c.className}"` : ''}>${content == null ? '' : content}</td>`
      }).join('') + '</tr>'
    }).join('')

    /* fila de totales (sobre lo FILTRADO, no solo la página) */
    const tfoot = this.$('tfoot')
    if (this.o.totals && this.o.totals.length && list.length) {
      tfoot.innerHTML = '<tr>' + this.o.columns.map((c, i) => {
        if (this.o.totals.includes(c.key)) {
          const sum = list.reduce((a, r) => a + (parseFloat(r[c.key]) || 0), 0)
          return `<td class="num" style="font-weight:700;color:var(--nx-txt1);border-top:1px solid var(--nx-border)">${fmtMoney(sum)}</td>`
        }
        return `<td style="border-top:1px solid var(--nx-border)">${i === 0 ? `<span style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:var(--nx-txt3)">${esc(this.i18n.totals)}</span>` : ''}</td>`
      }).join('') + '</tr>'
    } else tfoot.innerHTML = ''

    this.$('.nxt-empty').style.display = list.length ? 'none' : 'block'
    this.$('.nxt-count').textContent = `${list.length} ${this.i18n.of} ${this.data.length} ${this.o.unit}`
    this.$('.nxt-foot-info').textContent =
      `${this.i18n.showing} ${list.length ? (this.page - 1) * this.o.perPage + 1 : 0}–${Math.min(this.page * this.o.perPage, list.length)} ${this.i18n.of} ${list.length}`
    this.renderPager(pages)
  }

  renderPager (pages) {
    const box = this.$('.nxt-pages')
    box.innerHTML = ''
    const btn = (label, target, opts = {}) => {
      const b = document.createElement('button')
      b.className = 'nxt-page-btn' + (opts.active ? ' active' : '')
      b.innerHTML = label
      if (opts.disabled) b.disabled = true
      else b.addEventListener('click', () => { this.page = target; this.render() })
      box.appendChild(b)
    }
    btn('‹', this.page - 1, { disabled: this.page <= 1 })
    let from = Math.max(1, this.page - 3); const to = Math.min(pages, from + 6)
    from = Math.max(1, to - 6)
    for (let i = from; i <= to; i++) btn(i, i, { active: i === this.page })
    btn('›', this.page + 1, { disabled: this.page >= pages })
  }

  /* Exportar CSV de lo filtrado — usa labels/keys de columnas sin `actions` */
  exportCSV () {
    const cols = this.o.columns.filter(c => !c.actions && !c.noExport && c.key)
    const rows = this.filtered()
    const csv = [cols.map(c => c.label).join(';')].concat(
      rows.map(r => cols.map(c => `"${String((c.exportValue ? c.exportValue(r) : r[c.key]) == null ? '' : (c.exportValue ? c.exportValue(r) : r[c.key])).replace(/"/g, '""')}"`).join(';'))
    ).join('\r\n')
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' })
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = `${this.o.exportName}_${new Date().toISOString().slice(0, 10)}.csv`
    a.click()
    URL.revokeObjectURL(a.href)
  }
}

/* Helpers estáticos */
NxTable.esc = esc
NxTable.num = fmtNum
NxTable.money = fmtMoney
NxTable.qty = fmtQty
NxTable.badge = badge
NxTable.entity = entity
NxTable.initials = initials

window.NxTable = NxTable
export { NxTable }

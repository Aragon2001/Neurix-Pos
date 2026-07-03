<?php (defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('documents_upload'); ?>
        <small><?= lang('list_results'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <button class="nxt-btn nxt-btn-ghost" id="nxtExport" type="button">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
            <?= lang('exportar'); ?>
        </button>
        <label class="nxt-btn" for="nxtFiles" style="cursor:pointer">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1"/><path d="M9 15l3 -3l3 3"/><path d="M12 12l0 9"/></svg>
            <?= lang('cargar_xml'); ?>
        </label>
        <input type="file" id="nxtFiles" accept=".xml" multiple style="display:none">
    </div>
</div>

<!-- Zona de arrastre -->
<div class="nxt-card" id="nxtDrop" style="margin-bottom:20px;padding:26px;text-align:center;color:var(--nx-txt3);border-style:dashed;cursor:pointer;transition:border-color .2s,color .2s">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="opacity:.6;margin-bottom:6px"><path d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1"/><path d="M9 15l3 -3l3 3"/><path d="M12 12l0 9"/></svg>
    <div style="font-size:13.5px"><?= lang('arrastre_xml_aqui'); ?></div>
</div>

<div id="nxtList"></div>

<!-- Modal de resultados de carga -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang('resultado_carga'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ol id="resultadoslist" style="overflow-y:auto;max-height:300px;padding-left:18px"></ol>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var EST = {
        aceptado:   ['<?= lang('aceptado'); ?>', 'ok'],
        recibido:   ['<?= lang('recibido'); ?>', 'warn'],
        procesando: ['<?= lang('procesando'); ?>', 'info'],
        rechazado:  ['<?= lang('rechazado'); ?>', 'err'],
        error:      ['<?= lang('error'); ?>', 'err'],
        '5':        ['<?= lang('enviado_hacienda'); ?>', 'info']
    };
    function estLabel(v) { return (EST[v] || ['<?= lang('no_procesado'); ?>', 'muted'])[0]; }

    var t = new NxTable({
        el: '#nxtList',
        url: '<?= site_url('cargadocumentos/get_purchases_h'); ?>',
        csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' },
        minWidth: '1280px',
        unit: '<?= lang('documents_upload'); ?>'.toLowerCase(),
        exportName: 'documentos_recibidos',
        search: ['documento', 'ConsecutivoDocEmisor', 'nombre_emisor', 'NumeroCedulaEmisor', 'FechaEmisionDoc'],
        chips: { key: 'Estatus', all: '<?= lang('todas'); ?>', label: estLabel, sort: false },
        totals: ['MontoTotalImpuesto', 'TotalFactura'],
        map: function (r) { if (r.Estatus == null || r.Estatus === '') r.Estatus = 'noproc'; return r; },
        columns: [
            { key: 'documento', label: '<?= lang('documento_label'); ?>', render: function (r) {
                return r.documento ? NxTable.badge(r.documento, 'violet') : '—';
            } },
            { key: 'ConsecutivoDocEmisor', label: '<?= lang('consecutive'); ?>', sortable: 'str', render: function (r) {
                return r.ConsecutivoDocEmisor ? '<span class="nxt-code">' + NxTable.esc(r.ConsecutivoDocEmisor) + '</span>' : '—';
            } },
            { key: 'FechaEmisionDoc', label: '<?= lang('fecha_emision_label'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.FechaEmisionDoc) + '</span>';
            } },
            { key: 'nombre_emisor', label: '<?= lang('supplier'); ?>', sortable: 'str', render: function (r) {
                return '<span class="nxt-ent-name">' + NxTable.esc(r.nombre_emisor) + '</span>' +
                    (r.NumeroCedulaEmisor ? '<div class="nxt-ent-meta">' + NxTable.esc(r.NumeroCedulaEmisor) + '</div>' : '');
            } },
            { key: 'CodigoMoneda', label: '<?= lang('moneda'); ?>', render: function (r) {
                var tc = parseFloat(r.TipoCambio);
                return '<span class="nxt-dim-mono">' + NxTable.esc(r.CodigoMoneda || '—') + (tc && tc !== 1 ? ' · ' + NxTable.num(tc) : '') + '</span>';
            } },
            { key: 'MontoTotalImpuesto', label: '<?= lang('tax'); ?>', className: 'num', sortable: 'num', render: function (r) {
                return '<span class="nxt-cost">' + NxTable.money(r.MontoTotalImpuesto) + '</span>';
            } },
            { key: 'TotalFactura', label: '<?= lang('total'); ?>', className: 'num', sortable: 'num', render: function (r) {
                return '<span class="nxt-price">' + NxTable.money(r.TotalFactura) + '</span>';
            } },
            { key: 'Estatus', label: '<?= lang('status'); ?>', render: function (r) {
                var s = EST[r.Estatus] || ['<?= lang('no_procesado'); ?>', 'muted'];
                return NxTable.badge(s[0], s[1]);
            }, exportValue: function (r) { return estLabel(r.Estatus); } },
            { key: 'status_hacienda', label: 'XML', actions: true },
            { key: 'Actions', label: '<?= lang('actions'); ?>', actions: true, width: '130px' }
        ],
        i18n: {
            searchPlaceholder: '<?= lang('buscar_ph'); ?>',
            loading: '<?= lang('loading_data_from_server'); ?>',
            empty: '<?= lang('sin_resultados'); ?>',
            showing: '<?= lang('mostrando'); ?>', of: '<?= lang('de'); ?>', all: '<?= lang('todas'); ?>',
            totals: '<?= lang('total'); ?>'
        }
    });
    document.getElementById('nxtExport').addEventListener('click', function () { t.exportCSV(); });

    /* ── Carga de XML (input + drag & drop) → POST file_0..file_n al index ── */
    function startUpload(files) {
        if (!files || !files.length) return;
        var fd = new FormData();
        var i;
        for (i = 0; i < files.length; i++) fd.append('file_' + i, files[i]);
        fd.append('nbr_files', i);
        fd.append('<?= $this->security->get_csrf_token_name(); ?>', '<?= $this->security->get_csrf_hash(); ?>');
        fetch('<?= site_url('cargadocumentos'); ?>', {
            method: 'POST', body: fd, credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.text(); })
        .then(function (html) {
            document.getElementById('resultadoslist').innerHTML = html;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('uploadModal')).show();
            t.load();
        });
    }
    document.getElementById('nxtFiles').addEventListener('change', function () {
        startUpload(this.files); this.value = '';
    });
    var drop = document.getElementById('nxtDrop');
    drop.addEventListener('click', function () { document.getElementById('nxtFiles').click(); });
    drop.addEventListener('dragover', function (e) { e.preventDefault(); drop.style.borderColor = 'var(--nx-a1)'; drop.style.color = 'var(--nx-a1)'; });
    drop.addEventListener('dragleave', function () { drop.style.borderColor = ''; drop.style.color = ''; });
    drop.addEventListener('drop', function (e) {
        e.preventDefault(); drop.style.borderColor = ''; drop.style.color = '';
        startUpload(e.dataTransfer.files);
    });
});
</script>

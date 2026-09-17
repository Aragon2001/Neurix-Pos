<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('ajuste_titulo'); ?>
        <small><?= lang('sale_id'); ?> #<?= (int) $venta->id; ?> · <?= $this->tec->hrld($venta->date); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('pos/view/' . $venta->id); ?>" target="_blank" rel="noopener">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0-4 0"/><path d="M21 12c-2.4 4-5.4 6-9 6s-6.6-2-9-6c2.4-4 5.4-6 9-6s6.6 2 9 6"/></svg>
            <?= lang('view'); ?>
        </a>
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('sales'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M5 12l6 6M5 12l6-6"/></svg>
            <?= lang('sales'); ?>
        </a>
    </div>
</div>

<?php if (!$permiso['puede']): ?>
<div class="nxa-aviso" style="--av-c:var(--nx-err);margin-bottom:16px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.24 3.96l-8.13 14.05a2 2 0 0 0 1.73 3h16.32a2 2 0 0 0 1.73-3l-8.13-14.05a2 2 0 0 0-3.52 0z"/></svg>
    <div><?= lang($permiso['motivo']); ?></div>
</div>
<?php endif; ?>

<div id="nxaApp"></div>

<script>
window._nxaConfig = {
    venta: <?= (int) $venta->id; ?>,
    csrf: { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' }
};
window._nxaLang = <?= json_encode(array(
    'titulo'         => lang('ajuste_titulo'),
    'cargando'       => lang('loading_data_from_server'),
    'error_carga'    => lang('ajuste_fallo_emision'),
    'consecutivo'    => lang('consecutive'),
    'clave'          => lang('nxd_clave'),
    'total_original' => lang('ajuste_total_original'),
    'total_actual'   => lang('ajuste_total_actual'),
    'diferencia'     => lang('ajuste_diferencia'),
    'tipo_operacion' => lang('ajuste_tipo_operacion'),
    'saldo_ajustable' => lang('ajuste_saldo_ajustable'),
    'descripcion'    => lang('description'),
    'cantidad'       => lang('quantity'),
    'precio'         => lang('price'),
    'descuento'      => lang('discount'),
    'importe'        => lang('total'),
    'motivo'         => lang('ajuste_motivo'),
    'motivo_requerido' => lang('ajuste_motivo_requerido'),
    'codigo'         => lang('ajuste_codigo_referencia'),
    'sin_cambios'    => lang('ajuste_sin_cambios'),
    'nota_credito'   => lang('ajuste_nota_credito'),
    'nota_debito'    => lang('ajuste_nota_debito'),
    'anulacion'      => lang('ajuste_anulacion'),
    'confirmar_credito'  => lang('ajuste_confirmar_credito'),
    'confirmar_debito'   => lang('ajuste_confirmar_debito'),
    'confirmar_anulacion' => lang('ajuste_confirmar_anulacion'),
), JSON_UNESCAPED_UNICODE); ?>;
</script>

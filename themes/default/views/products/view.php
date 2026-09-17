<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$hayImagen = !empty($product->image) && $product->image !== 'noimage.png' && $product->image !== 'no_image.png';
$tipoTono = array('standard' => 'info', 'combo' => 'violet', 'digital' => 'orange')[$product->type] ?? 'muted';
?>

<div class="modal-dialog modal-lg nxp-dialog">
    <div class="modal-content nxp-content">
        <div class="modal-header nxp-head">
            <div class="nxp-head-id">
                <div class="nxp-thumb">
                    <?php if ($hayImagen) { ?>
                        <img src="<?= base_url() ?>uploads/<?= html_escape($product->image) ?>" alt="">
                    <?php } else { ?>
                        <i class="fa fa-cube"></i>
                    <?php } ?>
                </div>
                <div>
                    <h4 class="modal-title nxp-title" id="myModalLabel"><?= html_escape($product->name); ?></h4>
                    <div class="nxp-subtitle">
                        <span class="nxp-code"><?= html_escape($product->code); ?></span>
                        <span class="nxt-cat" style="--cat-c:var(--nx-<?= $tipoTono === 'violet' ? 'violet' : ($tipoTono === 'orange' ? 'orange' : 'a1') ?>)"><?= lang($product->type); ?></span>
                    </div>
                </div>
            </div>
            <div class="nxp-head-actions">
                <button type="button" class="nxt-icon-btn" data-bs-dismiss="modal" aria-hidden="true" title="<?= lang('close') ?>"><i class="fa fa-times"></i></button>
            </div>
        </div>

        <div class="modal-body nxp-body">
            <div class="nxp-hero">
                <div class="nxp-image">
                    <?php if ($hayImagen) { ?>
                        <img id="pr-image" src="<?= base_url() ?>uploads/<?= html_escape($product->image) ?>" alt="<?= html_escape($product->name); ?>">
                    <?php } else { ?>
                        <i class="fa fa-cube"></i>
                    <?php } ?>
                </div>

                <div class="nxp-kpis">
                    <div class="nxp-kpi" style="--kpi-c:var(--nx-emerald)">
                        <div class="nxp-kpi-label"><?= lang('price'); ?></div>
                        <div class="nxp-kpi-value"><?= $this->tec->formatMoney($product->price) ?></div>
                    </div>
                    <?php if ($Admin) { ?>
                    <div class="nxp-kpi" style="--kpi-c:var(--nx-amber)">
                        <div class="nxp-kpi-label"><?= lang('cost'); ?></div>
                        <div class="nxp-kpi-value"><?= $this->tec->formatMoney($product->cost) ?></div>
                    </div>
                    <?php } ?>
                    <?php if ($product->type == 'standard') { ?>
                    <div class="nxp-kpi" style="--kpi-c:var(--nx-a1)">
                        <div class="nxp-kpi-label"><?= lang('quantity'); ?></div>
                        <div class="nxp-kpi-value"><?= $this->tec->formatNumber($product->quantity); ?></div>
                    </div>
                    <?php } ?>
                    <div class="nxp-kpi" style="--kpi-c:var(--nx-indigo)">
                        <div class="nxp-kpi-label"><?= lang('tax_rate'); ?></div>
                        <div class="nxp-kpi-value"><?= html_escape($product->tax); ?></div>
                        <div class="nxp-kpi-sub"><?= $product->tax_method == 0 ? lang('inclusive') : lang('exclusive'); ?></div>
                    </div>
                </div>
            </div>

            <div class="nxp-facts">
                <div class="nxp-fact">
                    <span class="nxp-fact-label"><?= lang("category"); ?></span>
                    <span class="nxp-fact-value"><?= html_escape($category->name . ' (' . $category->code . ')'); ?></span>
                </div>
                <div class="nxp-fact">
                    <span class="nxp-fact-label"><?= lang("unit_of_measurement"); ?></span>
                    <span class="nxp-fact-value"><?= html_escape($product->unit_of_measurement ?: '—'); ?></span>
                </div>
                <div class="nxp-fact">
                    <span class="nxp-fact-label"><?= lang("codigo_cabys"); ?></span>
                    <span class="nxp-fact-value"><?= html_escape($product->cabys ?: '—'); ?></span>
                </div>
                <div class="nxp-fact">
                    <span class="nxp-fact-label"><?= lang("ubicacion"); ?></span>
                    <span class="nxp-fact-value"><?= html_escape($product->ubicacion ?: '—'); ?></span>
                </div>
                <div class="nxp-fact">
                    <span class="nxp-fact-label"><?= lang("barcode_symbology"); ?></span>
                    <span class="nxp-fact-value"><?= html_escape($product->barcode_symbology ?: '—'); ?></span>
                </div>
                <?php if ($product->type == 'standard') { ?>
                <div class="nxp-fact">
                    <span class="nxp-fact-label"><?= lang("alert_quantity"); ?></span>
                    <span class="nxp-fact-value"><?= $this->tec->formatNumber($product->alert_quantity); ?></span>
                </div>
                <?php } ?>
                <?php if ($product->offer_price > 0) { ?>
                <div class="nxp-fact">
                    <span class="nxp-fact-label"><?= lang("offer_price"); ?></span>
                    <span class="nxp-fact-value"><?= $this->tec->formatMoney($product->offer_price); ?></span>
                </div>
                <?php } ?>
                <?php if ($Admin) { ?>
                <div class="nxp-fact">
                    <span class="nxp-fact-label"><?= lang("margen"); ?></span>
                    <span class="nxp-fact-value"><?= $this->tec->formatNumber($product->margen); ?>%</span>
                </div>
                <?php } ?>
            </div>

            <?php if ($product->type == 'combo' && $combo_items) { ?>
            <div class="nxp-section">
                <div class="nxp-section-title"><?= lang('combo_items') ?></div>
                <div class="nxt-table-wrap nxp-combo-wrap">
                    <table class="nxt-table">
                        <thead>
                            <tr>
                                <th><?= lang('product_name') ?></th>
                                <th class="num"><?= lang('quantity') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($combo_items as $combo_item) { ?>
                            <tr>
                                <td><?= html_escape($combo_item->name); ?> <span class="nxp-combo-code"><?= html_escape($combo_item->code); ?></span></td>
                                <td class="num"><?= $this->tec->formatNumber($combo_item->qty); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php } ?>

            <?php if ($product->details) { ?>
            <div class="nxp-section">
                <div class="nxp-section-title"><?= lang('product_details') ?></div>
                <div class="nxp-details"><?= $product->details ?></div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<style>
.nxp-dialog{max-width:640px}
.nxp-content{background:var(--nx-card-bg);border:1px solid var(--nx-border);border-radius:var(--nx-radius-lg);overflow:hidden}

.nxp-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px;border-bottom:1px solid var(--nx-border);background:var(--nx-card-bg2)}
.nxp-head-id{display:flex;align-items:center;gap:14px;min-width:0}
.nxp-thumb{width:46px;height:46px;border-radius:12px;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--nx-bg5);border:1px solid var(--nx-border);color:var(--nx-txt4);font-size:18px}
.nxp-thumb img{width:100%;height:100%;object-fit:cover}
.nxp-title{margin:0;font-size:17px;font-weight:700;letter-spacing:-.01em;color:var(--nx-txt1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:360px}
.nxp-subtitle{display:flex;align-items:center;gap:9px;margin-top:4px}
.nxp-code{font-size:12px;color:var(--nx-txt4);font-variant-numeric:tabular-nums}
.nxp-head-actions{display:flex;gap:8px;flex-shrink:0}

.nxp-body{padding:20px}
.nxp-hero{display:flex;gap:18px;margin-bottom:20px}
.nxp-image{width:130px;height:130px;flex-shrink:0;border-radius:var(--nx-radius);overflow:hidden;background:var(--nx-bg5);border:1px solid var(--nx-border);display:flex;align-items:center;justify-content:center;color:var(--nx-txt4);font-size:34px}
.nxp-image img{width:100%;height:100%;object-fit:cover}

.nxp-kpis{flex:1;display:grid;grid-template-columns:repeat(2,1fr);gap:10px;min-width:0}
.nxp-kpi{position:relative;overflow:hidden;background:var(--nx-card-bg2);border:1px solid var(--nx-border);border-radius:10px;padding:10px 13px}
.nxp-kpi::before{content:"";position:absolute;inset:0 auto 0 0;width:3px;background:var(--kpi-c,var(--nx-a1))}
.nxp-kpi-label{font-size:10.5px;text-transform:uppercase;letter-spacing:.07em;color:var(--nx-txt3)}
.nxp-kpi-value{font-size:17px;font-weight:700;margin-top:3px;color:var(--nx-txt1);font-variant-numeric:tabular-nums;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nxp-kpi-sub{font-size:11px;color:var(--nx-txt4);margin-top:1px}

.nxp-facts{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px 16px;padding:14px 16px;background:var(--nx-card-bg2);border:1px solid var(--nx-border);border-radius:var(--nx-radius);margin-bottom:8px}
.nxp-fact{display:flex;flex-direction:column;gap:3px;min-width:0}
.nxp-fact-label{font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--nx-txt4)}
.nxp-fact-value{font-size:13.5px;color:var(--nx-txt2);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.nxp-section{margin-top:18px}
.nxp-section-title{font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--nx-txt3);margin-bottom:9px}
.nxp-combo-wrap{border:1px solid var(--nx-border);border-radius:var(--nx-radius)}
.nxp-combo-code{color:var(--nx-txt4);font-size:12px;margin-left:6px}
.nxp-details{background:var(--nx-card-bg2);border:1px solid var(--nx-border);border-radius:var(--nx-radius);padding:14px 16px;color:var(--nx-txt2);font-size:13.5px;line-height:1.55}

@media (max-width:520px){
  .nxp-hero{flex-direction:column}
  .nxp-image{width:100%;height:150px}
  .nxp-kpis{grid-template-columns:1fr 1fr}
  .nxp-facts{grid-template-columns:1fr}
  .nxp-title{max-width:220px}
}
</style>

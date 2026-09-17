<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>

<div class="modal-dialog modal-lg nxp-dialog">
    <div class="modal-content nxp-content">
        <div class="modal-header nxp-head">
            <div class="nxp-head-id">
                <div class="nxp-thumb nxp-thumb-err"><i class="fa fa-exclamation-triangle"></i></div>
                <div>
                    <h4 class="modal-title nxp-title" id="myModalLabel"><?= lang('error_busqueda_producto'); ?></h4>
                </div>
            </div>
            <div class="nxp-head-actions">
                <button type="button" class="nxt-icon-btn" data-bs-dismiss="modal" aria-hidden="true" title="<?= lang('close') ?>"><i class="fa fa-times"></i></button>
            </div>
        </div>

        <div class="modal-body nxp-body">
            <div class="nxp-details">
                <?= lang('producto_no_encontrado'); ?>
                <br />
                <?= lang('producto_sin_codigo_msg'); ?>
            </div>
        </div>
    </div>
</div>

<style>
.nxp-dialog{max-width:520px}
.nxp-content{background:var(--nx-card-bg);border:1px solid var(--nx-border);border-radius:var(--nx-radius-lg);overflow:hidden}
.nxp-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:18px 20px;border-bottom:1px solid var(--nx-border);background:var(--nx-card-bg2)}
.nxp-head-id{display:flex;align-items:center;gap:14px;min-width:0}
.nxp-thumb{width:46px;height:46px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:var(--nx-bg5);border:1px solid var(--nx-border);color:var(--nx-txt4);font-size:18px}
.nxp-thumb-err{color:var(--nx-amber);background:color-mix(in srgb,var(--nx-amber) 14%,transparent);border-color:color-mix(in srgb,var(--nx-amber) 35%,transparent)}
.nxp-title{margin:0;font-size:16px;font-weight:700;letter-spacing:-.01em;color:var(--nx-txt1)}
.nxp-head-actions{display:flex;gap:8px;flex-shrink:0}
.nxp-body{padding:20px}
.nxp-details{background:var(--nx-card-bg2);border:1px solid var(--nx-border);border-radius:var(--nx-radius);padding:14px 16px;color:var(--nx-txt2);font-size:13.5px;line-height:1.55}
</style>

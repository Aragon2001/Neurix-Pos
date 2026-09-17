<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Etiquetas y códigos de barras. Una sola pantalla: la plantilla decide qué
 * manda en el rótulo —el precio o el código— y el papel decide cómo sale.
 */
$icono = function ($paths, $size = 15) {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
         . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
};
$ico_barra  = '<path d="M4 7v-1a2 2 0 0 1 2 -2h2"/><path d="M4 17v1a2 2 0 0 0 2 2h2"/><path d="M16 4h2a2 2 0 0 1 2 2v1"/><path d="M16 20h2a2 2 0 0 0 2 -2v-1"/><path d="M5 11h1v2h-1z"/><path d="M10 11l0 2"/><path d="M14 11h1v2h-1z"/><path d="M19 11l0 2"/>';
$ico_flecha = '<path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>';
$ico_print  = '<path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2"/><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4"/><path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z"/>';
$ico_borrar = '<path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>';
$ico_mas    = '<path d="M12 5v14M5 12h14"/>';

/* Cada plantilla se ilustra con un boceto del rótulo, no con un icono. */
$plantillas = array(
    'precio' => array(
        lang('etq_plantilla_precio'), lang('etq_plantilla_precio_ayuda'),
        '<span class="b b-nom"></span><span class="b b-precio"></span><span class="b b-bc"></span>'
    ),
    'codigo' => array(
        lang('etq_plantilla_codigo'), lang('etq_plantilla_codigo_ayuda'),
        '<span class="b b-nom"></span><span class="b b-bc b-bc-alto"></span><span class="b b-cod"></span>'
    ),
    'minima' => array(
        lang('etq_plantilla_minima'), lang('etq_plantilla_minima_ayuda'),
        '<span class="b b-bc b-bc-max"></span><span class="b b-cod"></span>'
    ),
);
?>

<div class="nxt-head">
    <div class="nxt-title">
        <?= lang('etiquetas_codigos'); ?>
        <small><?= lang('etq_sub'); ?></small>
    </div>
    <div class="nxt-head-actions">
        <a class="nxt-btn nxt-btn-ghost" href="<?= site_url('products'); ?>">
            <?= $icono($ico_flecha); ?> <?= lang('products'); ?>
        </a>
        <button type="button" class="nxt-btn" id="etqImprimir" disabled>
            <?= $icono($ico_print); ?> <?= lang('print'); ?>
        </button>
    </div>
</div>

<div class="etq-layout">

    <!-- ═══ Columna de controles ═══ -->
    <div class="etq-panel">

        <div id="etqAviso" class="nxf-note" style="display:none;"></div>

        <!-- ── 1. Productos ── -->
        <div class="nxf-card">
            <div class="nxf-card-head">
                <span class="nxf-step">1</span>
                <div class="nxf-card-title">
                    <?= lang('etq_paso_productos'); ?>
                    <small><?= lang('etq_paso_productos_ayuda'); ?></small>
                </div>
            </div>
            <div class="nxf-card-body">

                <div class="etq-buscador" id="etqBuscador">
                    <div class="etq-buscador-caja">
                        <span class="etq-buscador-ico"><?= $icono($ico_barra, 17); ?></span>
                        <input type="text" id="etqBuscar" autocomplete="off" spellcheck="false"
                               placeholder="<?= lang('etq_buscar_ph'); ?>">
                    </div>
                    <div id="etqSug" class="etq-ac"></div>
                </div>

                <div class="etq-cat">
                    <select id="etqCategoria" class="nxf-select">
                        <option value=""><?= lang('etq_categoria'); ?></option>
                        <?php foreach (($categorias ?: array()) as $c) { ?>
                            <option value="<?= (int) $c->id; ?>"><?= html_escape($c->name); ?></option>
                        <?php } ?>
                    </select>
                    <button type="button" class="nxf-btn nxf-btn-ghost nxf-btn-sm" id="etqAddCat" disabled>
                        <?= $icono($ico_mas, 14); ?> <?= lang('etq_categoria_agregar'); ?>
                    </button>
                </div>

                <div class="etq-lista-head" id="etqListaHead" style="display:none;">
                    <span id="etqTotal"></span>
                    <div class="etq-todos">
                        <input type="number" id="etqCopiasTodos" min="1" max="200" value="1" aria-label="<?= lang('etq_copias'); ?>">
                        <button type="button" class="etq-mini" id="etqAplicarTodos"><?= lang('etq_aplicar'); ?></button>
                        <button type="button" class="etq-mini etq-mini-err" id="etqVaciar" title="<?= lang('etq_vaciar'); ?>">
                            <?= $icono($ico_borrar, 13); ?>
                        </button>
                    </div>
                </div>

                <div class="etq-lista" id="etqLista"></div>

                <div class="etq-vacio" id="etqVacio">
                    <?= $icono($ico_barra, 28); ?>
                    <p><?= lang('etq_lista_vacia'); ?></p>
                </div>
            </div>
        </div>

        <!-- ── 2. Diseño del rótulo ── -->
        <div class="nxf-card">
            <div class="nxf-card-head">
                <span class="nxf-step">2</span>
                <div class="nxf-card-title">
                    <?= lang('etq_paso_diseno'); ?>
                    <small><?= lang('etq_paso_diseno_ayuda'); ?></small>
                </div>
            </div>
            <div class="nxf-card-body">

                <div class="etq-plantillas" id="etqPlantillas">
                    <?php foreach ($plantillas as $clave => $p) { ?>
                    <button type="button" class="etq-plantilla<?= $clave === 'precio' ? ' activo' : ''; ?>" data-plantilla="<?= $clave; ?>">
                        <span class="etq-boceto"><?= $p[2]; ?></span>
                        <strong><?= html_escape($p[0]); ?></strong>
                        <small><?= html_escape($p[1]); ?></small>
                    </button>
                    <?php } ?>
                </div>

                <label class="nxf-label" style="margin-top:18px;"><?= lang('etq_contenido'); ?></label>
                <div class="etq-toggles" id="etqToggles">
                    <label class="etq-tg" data-dep="negocio"><input type="checkbox" id="verNegocio"><span><?= lang('etq_ver_negocio'); ?></span></label>
                    <label class="etq-tg" data-dep="nombre"><input type="checkbox" id="verNombre" checked><span><?= lang('etq_ver_nombre'); ?></span></label>
                    <label class="etq-tg" data-dep="codigo"><input type="checkbox" id="verCodigo" checked><span><?= lang('etq_ver_codigo'); ?></span></label>
                    <label class="etq-tg" data-dep="precio"><input type="checkbox" id="verPrecio" checked><span><?= lang('etq_ver_precio'); ?></span></label>
                    <label class="etq-tg" data-dep="precio"><input type="checkbox" id="verIva" checked><span><?= lang('etq_precio_iva'); ?></span></label>
                    <label class="etq-tg"><input type="checkbox" id="verCorte"><span><?= lang('etq_lineas_corte'); ?></span></label>
                </div>

                <label class="nxf-label" style="margin-top:18px;"><?= lang('etq_barras'); ?></label>
                <div class="nxf-grid">
                    <div class="nxf-field sp-5">
                        <input type="number" id="etqBarra" class="nxf-input" min="5" max="60" step="1" value="12">
                        <div class="nxf-hint"><?= lang('etq_alto_codigo'); ?></div>
                    </div>
                    <div class="nxf-field sp-7">
                        <div class="etq-seg" id="etqAjuste">
                            <button type="button" data-ajuste="none" class="activo"><?= lang('etq_ajuste_ancho'); ?></button>
                            <button type="button" data-ajuste="meet"><?= lang('etq_ajuste_proporcional'); ?></button>
                        </div>
                        <div class="nxf-hint"><?= lang('etq_ajuste_ayuda'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── 3. Papel y tamaño ── -->
        <div class="nxf-card">
            <div class="nxf-card-head">
                <span class="nxf-step">3</span>
                <div class="nxf-card-title">
                    <?= lang('etq_paso_papel'); ?>
                    <small><?= lang('etq_paso_papel_ayuda'); ?></small>
                </div>
            </div>
            <div class="nxf-card-body">

                <div class="etq-seg" id="etqPapel">
                    <button type="button" data-papel="hoja" class="activo"><?= lang('etq_papel_hoja'); ?></button>
                    <button type="button" data-papel="rollo"><?= lang('etq_papel_rollo'); ?></button>
                </div>
                <div class="nxf-hint" id="etqPapelAyuda"></div>

                <div class="nxf-grid" style="margin-top:14px;">
                    <div class="nxf-field sp-12">
                        <label class="nxf-label" for="etqPreset"><?= lang('etq_preset'); ?></label>
                        <select id="etqPreset" class="nxf-select"></select>
                    </div>
                    <div class="nxf-field sp-6">
                        <label class="nxf-label" for="etqAncho"><?= lang('etq_ancho'); ?></label>
                        <input type="number" id="etqAncho" class="nxf-input" min="20" max="210" step="1">
                    </div>
                    <div class="nxf-field sp-6">
                        <label class="nxf-label" for="etqAlto"><?= lang('etq_alto'); ?></label>
                        <input type="number" id="etqAlto" class="nxf-input" min="12" max="150" step="1">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ Vista previa ═══ -->
    <div class="etq-preview">
        <div class="etq-preview-head">
            <span><?= lang('etq_vista_previa'); ?></span>
            <span class="etq-preview-info" id="etqInfo"></span>
        </div>
        <div class="etq-papel">
            <div class="etq-hoja" id="etqHoja"></div>
            <div class="etq-preview-vacia" id="etqPreviaVacia"><?= lang('etq_vista_vacia'); ?></div>
        </div>
    </div>
</div>

<style id="etqPagina"></style>

<style>
    .etq-layout{display:grid;grid-template-columns:minmax(340px,410px) 1fr;gap:18px;align-items:start}
    @media (max-width:1080px){.etq-layout{grid-template-columns:1fr}}
    .etq-panel{display:flex;flex-direction:column;gap:16px;min-width:0}

    /* ── Buscador ── */
    .etq-buscador{position:relative}
    .etq-buscador-caja{
        display:flex;align-items:center;gap:10px;padding:11px 13px;border-radius:11px;
        border:1px solid var(--nx-border);background:var(--nx-input-bg);transition:var(--nx-transition);
    }
    .etq-buscador-caja:focus-within{border-color:var(--nx-border3);background:var(--nx-input-focus);box-shadow:0 0 0 3px rgba(56,189,248,.14)}
    .etq-buscador-ico{color:var(--nx-txt4);display:flex}
    .etq-buscador-caja:focus-within .etq-buscador-ico{color:var(--nx-a1)}
    .etq-buscador-caja input{flex:1;min-width:0;background:none;border:0;outline:0;color:var(--nx-txt1);font:400 14.5px inherit;font-family:inherit}
    .etq-buscador-caja input::placeholder{color:var(--nx-txt4)}
    .etq-ac{
        display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:40;
        border:1px solid var(--nx-border3);border-radius:12px;overflow-x:hidden;overflow-y:auto;max-height:320px;
        background:var(--nx-modal-bg);backdrop-filter:blur(18px);box-shadow:var(--nx-shadow-lg);
    }
    .etq-ac.abierto{display:block}
    .etq-ac-item{display:grid;grid-template-columns:1fr auto;align-items:center;gap:10px;padding:10px 13px;cursor:pointer;border-bottom:1px solid var(--nx-border2)}
    .etq-ac-item:last-child{border-bottom:0}
    .etq-ac-item.marcada,.etq-ac-item:hover{background:var(--nx-active-bg)}
    .etq-ac-nom{min-width:0;display:flex;flex-direction:column;gap:2px}
    .etq-ac-nom b{font-size:13.5px;font-weight:600;color:var(--nx-txt1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .etq-ac-nom span{font:400 11.5px ui-monospace,SFMono-Regular,Menlo,monospace;color:var(--nx-txt4)}
    .etq-ac-nom mark{background:rgba(56,189,248,.22);color:inherit;border-radius:3px;padding:0 1px}
    .etq-ac-precio{font-size:12.5px;font-weight:600;color:var(--nx-txt3);font-variant-numeric:tabular-nums;white-space:nowrap}
    .etq-ac-nada{padding:14px;text-align:center;font-size:13px;color:var(--nx-txt4)}

    /* ── Categoría ── */
    .etq-cat{display:flex;gap:8px;margin-top:12px}
    .etq-cat .nxf-select{flex:1;min-width:0}
    .etq-cat .nxf-btn-sm{white-space:nowrap}

    /* ── Lista de productos elegidos ── */
    .etq-lista-head{
        display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;
        margin-top:16px;padding-bottom:9px;border-bottom:1px solid var(--nx-border);
        font-size:12px;color:var(--nx-txt3);
    }
    .etq-lista-head b{color:var(--nx-txt1)}
    .etq-todos{display:flex;align-items:center;gap:6px}
    .etq-todos input{
        width:56px;text-align:right;padding:4px 7px;border-radius:7px;font:600 12px inherit;font-family:inherit;
        border:1px solid var(--nx-border);background:var(--nx-input-bg);color:var(--nx-txt1);outline:0;
    }
    .etq-mini{
        display:inline-flex;align-items:center;gap:5px;border:1px solid var(--nx-border);background:var(--nx-card-bg2);
        color:var(--nx-txt3);font:600 11.5px inherit;font-family:inherit;padding:5px 10px;border-radius:7px;
        cursor:pointer;transition:var(--nx-transition);
    }
    .etq-mini:hover{border-color:var(--nx-border3);color:var(--nx-txt1)}
    .etq-mini-err:hover{border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.10);color:var(--nx-err)}

    .etq-lista{display:flex;flex-direction:column}
    .etq-fila{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--nx-border2)}
    .etq-fila:last-child{border-bottom:0}
    .etq-fila-nom{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px}
    .etq-fila-nom b{font-size:13px;font-weight:600;color:var(--nx-txt1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .etq-fila-nom span{font:400 11px ui-monospace,SFMono-Regular,Menlo,monospace;color:var(--nx-txt4)}
    .etq-pasos{display:flex;align-items:center;border:1px solid var(--nx-border);border-radius:8px;overflow:hidden;background:var(--nx-input-bg)}
    .etq-pasos button{
        width:26px;height:28px;border:0;background:none;color:var(--nx-txt3);cursor:pointer;
        font:700 14px inherit;font-family:inherit;line-height:1;transition:var(--nx-transition);
    }
    .etq-pasos button:hover{background:var(--nx-active-bg);color:var(--nx-a1)}
    .etq-pasos input{
        width:44px;height:28px;text-align:center;border:0;border-left:1px solid var(--nx-border);
        border-right:1px solid var(--nx-border);background:none;color:var(--nx-txt1);outline:0;
        font:700 12.5px inherit;font-family:inherit;font-variant-numeric:tabular-nums;
    }
    .etq-quitar{
        display:grid;place-items:center;width:26px;height:26px;border-radius:7px;flex:0 0 auto;
        background:none;border:1px solid transparent;color:var(--nx-txt4);cursor:pointer;transition:var(--nx-transition);
    }
    .etq-quitar:hover{border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.10);color:var(--nx-err)}
    .etq-vacio{display:flex;flex-direction:column;align-items:center;gap:9px;padding:28px 12px;color:var(--nx-txt4)}
    .etq-vacio p{margin:0;font-size:12.5px;text-align:center}

    /* ── Plantillas: el botón es un boceto del rótulo ── */
    .etq-plantillas{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
    .etq-plantilla{
        display:flex;flex-direction:column;align-items:stretch;gap:7px;padding:11px;border-radius:12px;
        border:1px solid var(--nx-border);background:var(--nx-card-bg2);cursor:pointer;
        transition:var(--nx-transition);text-align:left;
    }
    .etq-plantilla:hover{border-color:var(--nx-border3)}
    .etq-plantilla strong{font-size:12.5px;font-weight:600;color:var(--nx-txt2);line-height:1.2}
    .etq-plantilla small{font-size:10.5px;color:var(--nx-txt4);line-height:1.35}
    .etq-plantilla.activo{border-color:var(--nx-border3);background:var(--nx-active-bg)}
    .etq-plantilla.activo strong{color:var(--nx-a1)}
    .etq-boceto{
        display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;
        height:46px;padding:5px;border-radius:7px;background:var(--nx-bg4);
        border:1px solid var(--nx-border2);color:var(--nx-txt4);
    }
    .etq-plantilla.activo .etq-boceto{color:var(--nx-a1)}
    .etq-boceto .b{display:block;border-radius:1px;background:currentColor}
    .etq-boceto .b-nom{width:72%;height:3px}
    .etq-boceto .b-cod{width:50%;height:2px}
    .etq-boceto .b-precio{width:56%;height:9px;border-radius:2px}
    .etq-boceto .b-bc{width:78%;height:7px;background:none;
        background-image:repeating-linear-gradient(90deg,currentColor 0 1px,transparent 1px 3px)}
    .etq-boceto .b-bc-alto{height:15px}
    .etq-boceto .b-bc-max{height:24px}

    /* ── Segmentado y casillas ── */
    .etq-seg{display:flex;border:1px solid var(--nx-border);border-radius:10px;overflow:hidden;background:var(--nx-card-bg2)}
    .etq-seg button{
        flex:1;border:0;background:none;color:var(--nx-txt3);cursor:pointer;padding:9px 12px;
        font:600 12.5px inherit;font-family:inherit;transition:var(--nx-transition);
    }
    .etq-seg button + button{border-left:1px solid var(--nx-border)}
    .etq-seg button:hover{color:var(--nx-txt1)}
    .etq-seg button.activo{background:var(--nx-active-bg);color:var(--nx-a1)}
    .etq-toggles{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:7px}
    .etq-tg{
        display:flex;align-items:center;gap:8px;padding:8px 11px;border-radius:9px;cursor:pointer;
        border:1px solid var(--nx-border);background:var(--nx-card-bg2);transition:var(--nx-transition);
    }
    .etq-tg:hover{border-color:var(--nx-border3)}
    .etq-tg input{accent-color:var(--nx-a1);width:15px;height:15px;cursor:pointer;flex:0 0 auto}
    .etq-tg span{font-size:12.5px;color:var(--nx-txt2)}
    .etq-tg.marcado{border-color:var(--nx-border3);background:var(--nx-active-bg)}
    .etq-tg.marcado span{color:var(--nx-a1)}
    .etq-tg.inerte{opacity:.38;pointer-events:none}

    /* ── Vista previa ── */
    .etq-preview{
        position:sticky;top:14px;background:var(--nx-card-bg);border:1px solid var(--nx-border);
        border-radius:var(--nx-radius-lg);backdrop-filter:blur(16px);box-shadow:var(--nx-shadow-sm);overflow:hidden;
    }
    .etq-preview-head{
        display:flex;align-items:center;justify-content:space-between;gap:10px;padding:13px 18px;
        border-bottom:1px solid var(--nx-border);font-size:11.5px;font-weight:700;text-transform:uppercase;
        letter-spacing:.08em;color:var(--nx-txt3);
    }
    .etq-preview-info{text-transform:none;letter-spacing:0;font-weight:400;font-size:12px;color:var(--nx-txt4);font-variant-numeric:tabular-nums}
    .etq-papel{padding:20px;max-height:calc(100vh - 190px);overflow:auto;background:var(--nx-bg4)}
    .etq-preview-vacia{padding:44px 20px;text-align:center;font-size:13px;color:var(--nx-txt4)}

    /* ── El rótulo. En pantalla es papel: blanco y tinta negra, sin tema. ── */
    .etq-hoja{
        display:flex;flex-wrap:wrap;align-content:flex-start;gap:2mm;
        background:#fff;padding:4mm;border-radius:6px;box-shadow:0 10px 34px rgba(0,0,0,.35);
        width:max-content;max-width:100%;margin:0 auto;
    }
    .etq-hoja:empty{display:none}
    .etq-rotulo{
        box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:center;
        padding:1.2mm 1.4mm;overflow:hidden;color:#000;background:#fff;
        width:var(--etq-w);height:var(--etq-h);
        font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;text-align:center;line-height:1.12;
    }
    .etq-hoja.corte .etq-rotulo{outline:1px dashed #c4c4c4;outline-offset:-1px}
    .etq-rotulo > * + *{margin-top:.7mm}

    .etq-negocio{
        font-size:calc(1.9mm * var(--etq-k));font-weight:700;letter-spacing:.3mm;text-transform:uppercase;
        color:#555;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    }
    .etq-nombre{
        font-size:calc(2.7mm * var(--etq-k));font-weight:700;letter-spacing:-.01em;max-width:100%;
        display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
    }
    .etq-bc{width:100%;line-height:0}
    .etq-bc img{display:block;width:100%;height:var(--etq-bc)}
    .etq-codigo{
        font-family:ui-monospace,"SFMono-Regular","Roboto Mono",Menlo,Consolas,monospace;
        font-size:calc(2.1mm * var(--etq-k));font-weight:500;letter-spacing:.22mm;
        font-variant-numeric:tabular-nums;white-space:nowrap;max-width:100%;overflow:hidden;
    }
    .etq-precio{font-weight:800;letter-spacing:-.02em;font-variant-numeric:tabular-nums;white-space:nowrap}
    .p-precio .etq-precio{font-size:calc(5.4mm * var(--etq-k))}
    .p-codigo .etq-precio{font-size:calc(3.1mm * var(--etq-k))}
    .p-minima .etq-codigo{font-size:calc(2.4mm * var(--etq-k));letter-spacing:.3mm}

    /* ── Impresión ── */
    @media print{
        body{background:#fff!important}
        body > *{display:none!important}
        body > .etq-hoja{
            display:flex!important;box-shadow:none;border-radius:0;padding:0;margin:0;
            width:100%;max-width:none;background:#fff;
        }
        body > .etq-hoja.rollo{display:block!important}
        body > .etq-hoja.rollo .etq-rotulo{break-after:page;page-break-after:always}
        body > .etq-hoja.rollo .etq-rotulo:last-child{break-after:auto;page-break-after:auto}
        .etq-rotulo{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    }
</style>

<script>
(function () {
    'use strict';

    var URL_BUSCAR = '<?= site_url('products/buscar_etiquetas'); ?>';
    var URL_CAT    = '<?= site_url('products/productos_categoria'); ?>/';
    var URL_BC     = '<?= site_url('products/barcode_img'); ?>';
    var NEGOCIO    = <?= json_encode(isset($nombre_negocio) ? $nombre_negocio : $Settings->site_name); ?>;
    var LIMITE     = 1000;

    var T = {
        total:      <?= json_encode(lang('etq_total')); ?>,
        info:       <?= json_encode(lang('etq_info_hoja')); ?>,
        sinRes:     <?= json_encode(lang('no_match_found')); ?>,
        errorRed:   <?= json_encode(lang('inv_error_red')); ?>,
        limite:     <?= json_encode(lang('etq_limite')); ?>,
        ayudaHoja:  <?= json_encode(lang('etq_papel_hoja_ayuda')); ?>,
        ayudaRollo: <?= json_encode(lang('etq_papel_rollo_ayuda')); ?>,
        libre:      <?= json_encode(lang('etq_preset_libre')); ?>
    };

    // Medidas en milimetros: son las de los rollos y las hojas que se venden.
    var PRESETS = [
        { id: '50x25',  w: 50,   h: 25,   txt: '50 × 25 mm' },
        { id: '38x25',  w: 38,   h: 25,   txt: '38 × 25 mm' },
        { id: '40x30',  w: 40,   h: 30,   txt: '40 × 30 mm' },
        { id: '60x40',  w: 60,   h: 40,   txt: '60 × 40 mm' },
        { id: '70x37',  w: 70,   h: 37,   txt: '70 × 37 mm' },
        { id: '100x50', w: 100,  h: 50,   txt: '100 × 50 mm' },
        { id: '63x38',  w: 63.5, h: 38.1, txt: '63,5 × 38,1 mm (A4)' }
    ];

    // Qué parte se dibuja en cada plantilla y en qué orden.
    var PLANTILLAS = {
        precio: ['negocio', 'nombre', 'precio', 'barras', 'codigo'],
        codigo: ['negocio', 'nombre', 'barras', 'codigo', 'precio'],
        minima: ['barras', 'codigo']
    };

    var lineas = [];
    var sugerencias = [];
    var marcada = -1;
    var temporizador = null;
    var papel = 'hoja';
    var plantilla = 'precio';
    var ajuste = 'none';

    var $buscar   = document.getElementById('etqBuscar');
    var $sug      = document.getElementById('etqSug');
    var $lista    = document.getElementById('etqLista');
    var $listaHd  = document.getElementById('etqListaHead');
    var $vacio    = document.getElementById('etqVacio');
    var $total    = document.getElementById('etqTotal');
    var $hoja     = document.getElementById('etqHoja');
    var $previaV  = document.getElementById('etqPreviaVacia');
    var $info     = document.getElementById('etqInfo');
    var $aviso    = document.getElementById('etqAviso');
    var $imprimir = document.getElementById('etqImprimir');
    var $preset   = document.getElementById('etqPreset');
    var $ancho    = document.getElementById('etqAncho');
    var $alto     = document.getElementById('etqAlto');
    var $barra    = document.getElementById('etqBarra');
    var $cat      = document.getElementById('etqCategoria');
    var $addCat   = document.getElementById('etqAddCat');
    var $pagina   = document.getElementById('etqPagina');

    function esc(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function resaltar(texto, term) {
        var t = esc(texto);
        if (!term) { return t; }
        var i = t.toLowerCase().indexOf(esc(term).toLowerCase());
        if (i === -1) { return t; }
        return t.slice(0, i) + '<mark>' + t.slice(i, i + term.length) + '</mark>' + t.slice(i + term.length);
    }

    function avisar(texto, tono) {
        if (!texto) { $aviso.style.display = 'none'; $aviso.className = 'nxf-note'; return; }
        $aviso.className = 'nxf-note nxf-note-' + (tono || 'info');
        $aviso.textContent = texto;
        $aviso.style.display = '';
    }

    function marcado(id) { return document.getElementById(id).checked; }

    /* ── Presets de tamaño ── */
    function llenarPresets() {
        $preset.innerHTML = PRESETS.map(function (p) {
            return '<option value="' + p.id + '">' + p.txt + '</option>';
        }).join('') + '<option value="libre">' + esc(T.libre) + '</option>';
        $preset.value = '50x25';
        aplicarPreset();
    }

    function aplicarPreset() {
        var p = PRESETS.filter(function (x) { return x.id === $preset.value; })[0];
        if (!p) { return; }
        $ancho.value = p.w;
        $alto.value = p.h;
    }

    function medidas() {
        var h = Math.max(12, Math.min(150, parseFloat($alto.value) || 25));
        return {
            w: Math.max(20, Math.min(210, parseFloat($ancho.value) || 50)),
            h: h,
            bc: Math.max(5, Math.min(60, parseFloat($barra.value) || 12)),
            // La tipografía sigue al rótulo: 25 mm de alto es la referencia.
            k: Math.max(0.7, Math.min(2.4, h / 25))
        };
    }

    /* ── Recordar la última configuración usada ──
       Cada negocio imprime siempre el mismo tamaño de rótulo; sin esto, cada
       visita a la pantalla vuelve al ancho completo por defecto y el código
       de barras sale desproporcionado contra la etiqueta real. */
    var CLAVE_CONFIG = 'nx-etq-config';

    function guardarConfig() {
        try {
            localStorage.setItem(CLAVE_CONFIG, JSON.stringify({
                plantilla: plantilla,
                ajuste: ajuste,
                papel: papel,
                preset: $preset.value,
                ancho: $ancho.value,
                alto: $alto.value,
                barra: $barra.value,
                toggles: {
                    verNegocio: marcado('verNegocio'),
                    verNombre: marcado('verNombre'),
                    verCodigo: marcado('verCodigo'),
                    verPrecio: marcado('verPrecio'),
                    verIva: marcado('verIva'),
                    verCorte: marcado('verCorte')
                }
            }));
        } catch (e) { /* almacenamiento no disponible: no hay nada que recordar */ }
    }

    function leerConfigGuardada() {
        try {
            var crudo = localStorage.getItem(CLAVE_CONFIG);
            return crudo ? JSON.parse(crudo) : null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Aplica la configuración guardada sobre los valores por defecto que ya
     * dejaron `llenarPresets()` y el HTML. Se llama una sola vez, al arrancar.
     */
    function aplicarConfigGuardada() {
        var cfg = leerConfigGuardada();
        if (!cfg) { return; }

        if (cfg.plantilla && PLANTILLAS[cfg.plantilla]) {
            plantilla = cfg.plantilla;
            document.querySelectorAll('#etqPlantillas .etq-plantilla').forEach(function (x) {
                x.classList.toggle('activo', x.dataset.plantilla === plantilla);
            });
        }
        if (cfg.ajuste === 'none' || cfg.ajuste === 'meet') {
            ajuste = cfg.ajuste;
            document.querySelectorAll('#etqAjuste button').forEach(function (x) {
                x.classList.toggle('activo', x.dataset.ajuste === ajuste);
            });
        }
        if (cfg.papel === 'hoja' || cfg.papel === 'rollo') {
            papel = cfg.papel;
            document.querySelectorAll('#etqPapel button').forEach(function (x) {
                x.classList.toggle('activo', x.dataset.papel === papel);
            });
            document.getElementById('etqPapelAyuda').textContent = papel === 'rollo' ? T.ayudaRollo : T.ayudaHoja;
        }
        if (cfg.toggles) {
            Object.keys(cfg.toggles).forEach(function (id) {
                var el = document.getElementById(id);
                if (el) { el.checked = !!cfg.toggles[id]; }
            });
        }
        if (cfg.barra) { $barra.value = cfg.barra; }

        // El tamaño se restaura al final: si el preset guardado ya no existe
        // en la lista, "libre" cae directo al ancho/alto que se guardaron.
        if (cfg.preset && $preset.querySelector('option[value="' + cfg.preset + '"]')) {
            $preset.value = cfg.preset;
        } else if (cfg.ancho || cfg.alto) {
            $preset.value = 'libre';
        }
        if ($preset.value === 'libre') {
            if (cfg.ancho) { $ancho.value = cfg.ancho; }
            if (cfg.alto)  { $alto.value  = cfg.alto; }
        } else {
            aplicarPreset();
        }
    }

    /* ── Lista de productos ── */
    function agregar(p, copias) {
        var ya = -1;
        lineas.forEach(function (l, i) { if (l.id === p.id) { ya = i; } });
        if (ya !== -1) {
            lineas[ya].copias += (copias || 1);
            pintarLista();
            return;
        }
        p.copias = copias || 1;
        lineas.push(p);
        pintarLista();
    }

    function totalEtiquetas() {
        return lineas.reduce(function (n, l) { return n + (l.copias || 0); }, 0);
    }

    function pintarLista() {
        $lista.innerHTML = lineas.map(function (l, i) {
            return '<div class="etq-fila">'
                 + '<span class="etq-fila-nom"><b>' + esc(l.name) + '</b><span>' + esc(l.code) + '</span></span>'
                 + '<span class="etq-pasos">'
                 +   '<button type="button" data-menos="' + i + '" aria-label="-">&minus;</button>'
                 +   '<input type="number" min="1" max="200" value="' + l.copias + '" data-copias="' + i + '">'
                 +   '<button type="button" data-mas="' + i + '" aria-label="+">+</button>'
                 + '</span>'
                 + '<button type="button" class="etq-quitar" data-quitar="' + i + '" aria-label="x">'
                 +   '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>'
                 + '</button></div>';
        }).join('');

        var total = totalEtiquetas();
        $listaHd.style.display = lineas.length ? '' : 'none';
        $vacio.style.display = lineas.length ? 'none' : '';
        $total.innerHTML = T.total.replace('%1', '<b>' + total + '</b>').replace('%2', '<b>' + lineas.length + '</b>');
        $imprimir.disabled = total === 0;

        pintarHoja();
    }

    /* ── El rótulo ── */
    function urlBarras(l) {
        // El SVG se estira al alto que pida la etiqueta, así que el alto de la
        // petición es fijo: cambiarlo solo gastaría otra descarga.
        return URL_BC + '?code=' + encodeURIComponent(l.code)
             + '&sym=' + encodeURIComponent(l.simbologia)
             + '&h=100&fit=' + ajuste;
    }

    /**
     * El código en texto, agrupado como en la etiqueta de fábrica: EAN-13 en
     * 1-6-6, EAN-8 en 4-4 y UPC-A en 1-5-5-1. Así se lee de un vistazo y se
     * dicta sin perder la cuenta.
     */
    function codigoLegible(l) {
        var c = String(l.code || '');
        if (!/^[0-9]+$/.test(c)) { return c; }
        if (l.simbologia === 'ean13' && c.length === 13) {
            return c.slice(0, 1) + ' ' + c.slice(1, 7) + ' ' + c.slice(7);
        }
        if (l.simbologia === 'ean8' && c.length === 8) {
            return c.slice(0, 4) + ' ' + c.slice(4);
        }
        if (l.simbologia === 'upca' && c.length === 12) {
            return c.slice(0, 1) + ' ' + c.slice(1, 6) + ' ' + c.slice(6, 11) + ' ' + c.slice(11);
        }
        return c;
    }

    function rotulo(l) {
        var partes = '';
        PLANTILLAS[plantilla].forEach(function (parte) {
            if (parte === 'negocio' && marcado('verNegocio')) {
                partes += '<div class="etq-negocio">' + esc(NEGOCIO) + '</div>';
            } else if (parte === 'nombre' && marcado('verNombre')) {
                partes += '<div class="etq-nombre">' + esc(l.name) + '</div>';
            } else if (parte === 'barras') {
                partes += '<div class="etq-bc"><img src="' + esc(urlBarras(l)) + '" alt="' + esc(l.code) + '"></div>';
            } else if (parte === 'codigo' && marcado('verCodigo')) {
                partes += '<div class="etq-codigo">' + esc(codigoLegible(l)) + '</div>';
            } else if (parte === 'precio' && marcado('verPrecio')) {
                partes += '<div class="etq-precio">' + esc(marcado('verIva') ? l.precio_iva_fmt : l.precio_fmt) + '</div>';
            }
        });
        return '<div class="etq-rotulo">' + partes + '</div>';
    }

    function pintarHoja() {
        var m = medidas();
        var total = totalEtiquetas();

        $hoja.style.setProperty('--etq-w', m.w + 'mm');
        $hoja.style.setProperty('--etq-h', m.h + 'mm');
        $hoja.style.setProperty('--etq-bc', m.bc + 'mm');
        $hoja.style.setProperty('--etq-k', m.k);
        $hoja.className = 'etq-hoja p-' + plantilla
                        + (marcado('verCorte') ? ' corte' : '')
                        + (papel === 'rollo' ? ' rollo' : '');

        if (total > LIMITE) {
            avisar(T.limite.replace('%s', LIMITE), 'warn');
        } else if ($aviso.classList.contains('nxf-note-warn')) {
            avisar('');
        }

        var html = '';
        var puestas = 0;
        lineas.forEach(function (l) {
            var uno = rotulo(l);
            for (var i = 0; i < l.copias && puestas < LIMITE; i++, puestas++) { html += uno; }
        });

        $hoja.innerHTML = html;
        $previaV.style.display = total ? 'none' : '';
        $info.textContent = total
            ? T.info.replace('%1', puestas).replace('%2', m.w + ' × ' + m.h + ' mm')
            : '';

        // @page no admite var(): la regla se reescribe cuando cambia el papel.
        $pagina.textContent = papel === 'rollo'
            ? '@media print{@page{size:' + m.w + 'mm ' + m.h + 'mm;margin:0}}'
            : '@media print{@page{margin:6mm}}';
    }

    /* ── Autosugerencia ── */
    function cerrarSug() {
        sugerencias = []; marcada = -1;
        $sug.classList.remove('abierto');
        $sug.innerHTML = '';
    }

    function pintarSug(term) {
        if (!sugerencias.length) {
            $sug.innerHTML = '<div class="etq-ac-nada">' + esc(T.sinRes) + '</div>';
            $sug.classList.add('abierto');
            return;
        }
        $sug.innerHTML = sugerencias.map(function (p, i) {
            return '<div class="etq-ac-item' + (i === marcada ? ' marcada' : '') + '" data-sug="' + i + '">'
                 + '<span class="etq-ac-nom"><b>' + resaltar(p.name, term) + '</b><span>' + resaltar(p.code, term) + '</span></span>'
                 + '<span class="etq-ac-precio">' + esc(p.precio_iva_fmt) + '</span></div>';
        }).join('');
        $sug.classList.add('abierto');
    }

    function buscar(term, autoAgregar) {
        fetch(URL_BUSCAR + '?term=' + encodeURIComponent(term), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) {
            if (!r.ok) { throw new Error(r.status); }
            return r.json();
        })
        .then(function (d) {
            var lista = d.productos || [];
            if ((d.exacto || autoAgregar) && lista.length === 1) {
                cerrarSug();
                $buscar.value = '';
                agregar(lista[0]);
                return;
            }
            sugerencias = lista;
            marcada = lista.length ? 0 : -1;
            pintarSug(term);
        })
        .catch(function () { avisar(T.errorRed, 'err'); });
    }

    $buscar.addEventListener('input', function () {
        var v = $buscar.value.trim();
        clearTimeout(temporizador);
        if (v.length < 2) { cerrarSug(); return; }
        temporizador = setTimeout(function () { buscar(v, false); }, 200);
    });

    $buscar.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (!sugerencias.length) { return; }
            e.preventDefault();
            marcada = (marcada + (e.key === 'ArrowDown' ? 1 : -1) + sugerencias.length) % sugerencias.length;
            pintarSug($buscar.value.trim());
            var m = $sug.querySelector('.marcada');
            if (m) { m.scrollIntoView({ block: 'nearest' }); }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(temporizador);
            if (marcada >= 0 && sugerencias[marcada]) {
                var p = sugerencias[marcada];
                cerrarSug();
                $buscar.value = '';
                agregar(p);
            } else if ($buscar.value.trim()) {
                buscar($buscar.value.trim(), true);
            }
        } else if (e.key === 'Escape') {
            cerrarSug();
        }
    });

    $sug.addEventListener('mousedown', function (e) {
        var fila = e.target.closest('[data-sug]');
        if (!fila) { return; }
        e.preventDefault();
        var p = sugerencias[parseInt(fila.dataset.sug, 10)];
        cerrarSug();
        $buscar.value = '';
        agregar(p);
        $buscar.focus();
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('etqBuscador').contains(e.target)) { cerrarSug(); }
    });

    /* ── Categoría completa ── */
    $cat.addEventListener('change', function () { $addCat.disabled = !$cat.value; });

    $addCat.addEventListener('click', function () {
        if (!$cat.value) { return; }
        $addCat.disabled = true;
        fetch(URL_CAT + encodeURIComponent($cat.value), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) {
            if (!r.ok) { throw new Error(r.status); }
            return r.json();
        })
        .then(function (d) {
            (d.productos || []).forEach(function (p) { agregar(p, 1); });
            $addCat.disabled = false;
        })
        .catch(function () { avisar(T.errorRed, 'err'); $addCat.disabled = false; });
    });

    /* ── Edición de la lista ── */
    $lista.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b) { return; }
        if (b.dataset.quitar !== undefined) { lineas.splice(parseInt(b.dataset.quitar, 10), 1); }
        else if (b.dataset.mas !== undefined) { lineas[b.dataset.mas].copias = Math.min(200, lineas[b.dataset.mas].copias + 1); }
        else if (b.dataset.menos !== undefined) { lineas[b.dataset.menos].copias = Math.max(1, lineas[b.dataset.menos].copias - 1); }
        else { return; }
        pintarLista();
    });

    $lista.addEventListener('input', function (e) {
        var i = e.target.closest('input[data-copias]');
        if (!i) { return; }
        var n = parseInt(i.value, 10);
        lineas[i.dataset.copias].copias = isNaN(n) ? 1 : Math.max(1, Math.min(200, n));
        // Repintar la lista movería el foco: solo se rehace la hoja.
        $total.innerHTML = T.total.replace('%1', '<b>' + totalEtiquetas() + '</b>').replace('%2', '<b>' + lineas.length + '</b>');
        pintarHoja();
    });

    document.getElementById('etqAplicarTodos').addEventListener('click', function () {
        var n = Math.max(1, Math.min(200, parseInt(document.getElementById('etqCopiasTodos').value, 10) || 1));
        lineas.forEach(function (l) { l.copias = n; });
        pintarLista();
    });

    document.getElementById('etqVaciar').addEventListener('click', function () {
        lineas = [];
        avisar('');
        pintarLista();
        $buscar.focus();
    });

    /* ── Diseño ── */
    // Las casillas que la plantilla no dibuja se apagan en vez de mentir.
    function refrescarToggles() {
        var usa = PLANTILLAS[plantilla];
        document.querySelectorAll('#etqToggles .etq-tg').forEach(function (t) {
            var dep = t.dataset.dep;
            t.classList.toggle('inerte', !!dep && usa.indexOf(dep) === -1);
            t.classList.toggle('marcado', t.querySelector('input').checked);
        });
    }

    document.getElementById('etqPlantillas').addEventListener('click', function (e) {
        var b = e.target.closest('[data-plantilla]');
        if (!b || b.dataset.plantilla === plantilla) { return; }
        plantilla = b.dataset.plantilla;
        this.querySelectorAll('.etq-plantilla').forEach(function (x) { x.classList.toggle('activo', x === b); });
        refrescarToggles();
        pintarHoja();
        guardarConfig();
    });

    document.querySelectorAll('#etqToggles input').forEach(function (c) {
        c.addEventListener('change', function () { refrescarToggles(); pintarHoja(); guardarConfig(); });
    });

    document.getElementById('etqAjuste').addEventListener('click', function (e) {
        var b = e.target.closest('[data-ajuste]');
        if (!b) { return; }
        ajuste = b.dataset.ajuste;
        this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('activo', x === b); });
        pintarHoja();
        guardarConfig();
    });

    /* ── Papel y tamaño ── */
    document.getElementById('etqPapel').addEventListener('click', function (e) {
        var b = e.target.closest('[data-papel]');
        if (!b) { return; }
        papel = b.dataset.papel;
        this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('activo', x === b); });
        document.getElementById('etqPapelAyuda').textContent = papel === 'rollo' ? T.ayudaRollo : T.ayudaHoja;
        pintarHoja();
        guardarConfig();
    });

    $preset.addEventListener('change', function () {
        if ($preset.value !== 'libre') { aplicarPreset(); }
        pintarHoja();
        guardarConfig();
    });

    [$ancho, $alto, $barra].forEach(function (el) {
        el.addEventListener('input', function () {
            if (el !== $barra) { $preset.value = 'libre'; }
            pintarHoja();
            guardarConfig();
        });
    });

    /* ── Impresión: la hoja sale del panel para no heredar su recorte ── */
    var anclaHoja = $hoja.parentNode;

    window.addEventListener('beforeprint', function () {
        if ($hoja.parentNode !== document.body) { document.body.appendChild($hoja); }
    });
    window.addEventListener('afterprint', function () {
        if ($hoja.parentNode !== anclaHoja) { anclaHoja.insertBefore($hoja, anclaHoja.firstChild); }
    });

    $imprimir.addEventListener('click', function () {
        if (!totalEtiquetas()) { return; }
        window.print();
    });

    /* ── Arranque ── */
    document.getElementById('etqPapelAyuda').textContent = T.ayudaHoja;
    llenarPresets();
    aplicarConfigGuardada();
    refrescarToggles();
    pintarLista();

    <?php if (!empty($precargado)) { ?>
    agregar(<?= json_encode($precargado); ?>, 1);
    <?php } else { ?>
    $buscar.focus();
    <?php } ?>
})();
</script>

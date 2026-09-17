<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

$simbolo   = $Settings->symbol ?? '₡';
$cajero    = trim($this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name'));
$tienda    = isset($store->name) ? $store->name : '';
$sugeridos = [0, 10000, 20000, 50000, 100000];
?>

<style>
.nx-ar-wrap { max-width: 620px; margin: 24px auto 40px; }

.nx-ar-card {
    background: var(--nx-bg3); border: 1px solid var(--nx-border);
    border-radius: 16px; overflow: hidden;
}
.nx-ar-head {
    display: flex; align-items: center; gap: 14px;
    padding: 20px 24px; border-bottom: 1px solid var(--nx-border2);
}
.nx-ar-ico {
    width: 46px; height: 46px; border-radius: 13px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; background: rgba(34,197,94,.13); color: var(--nx-ok);
}
.nx-ar-ico.locked { background: rgba(234,179,8,.13); color: var(--nx-warn); }
.nx-ar-head h2 { margin: 0; font-size: 17px; font-weight: 700; color: var(--nx-txt1); }
.nx-ar-head p  { margin: 3px 0 0; font-size: 12.5px; color: var(--nx-txt3); }

.nx-ar-meta {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1px; background: var(--nx-border2);
    border-bottom: 1px solid var(--nx-border2);
}
.nx-ar-meta div { background: var(--nx-bg3); padding: 12px 24px; }
.nx-ar-meta span { display: block; font-size: 10.5px; text-transform: uppercase;
    letter-spacing: .06em; color: var(--nx-txt3); margin-bottom: 3px; }
.nx-ar-meta strong { font-size: 13.5px; font-weight: 600; color: var(--nx-txt1); }

.nx-ar-body { padding: 24px; }
.nx-ar-label { display: block; font-size: 13px; font-weight: 600;
    color: var(--nx-txt2); margin-bottom: 8px; }

.nx-ar-money { position: relative; }
.nx-ar-money .sym {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    font-size: 22px; font-weight: 600; color: var(--nx-txt3); pointer-events: none;
}
.nx-ar-money input {
    width: 100%; padding: 14px 16px 14px 46px;
    background: var(--nx-bg4); border: 1px solid var(--nx-border);
    border-radius: 12px; color: var(--nx-txt1);
    font-size: 26px; font-weight: 700;
    outline: none; transition: border-color .18s, box-shadow .18s;
}
.nx-ar-money input:focus {
    border-color: var(--nx-a1);
    box-shadow: 0 0 0 3px rgba(56,189,248,.18);
}
.nx-ar-money input.invalido { border-color: var(--nx-err); }

.nx-ar-hint { display: block; margin-top: 7px; font-size: 12px; color: var(--nx-txt3); }
.nx-ar-hint.error { color: var(--nx-err); }

.nx-ar-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 14px; }
.nx-ar-chip {
    padding: 6px 14px; border-radius: 20px; cursor: pointer;
    font-size: 12.5px; font-weight: 600; font-variant-numeric: tabular-nums;
    background: transparent; color: var(--nx-txt2);
    border: 1px solid var(--nx-border); transition: all .18s;
}
.nx-ar-chip:hover { color: var(--nx-a1); border-color: var(--nx-a1); }

.nx-ar-foot { display: flex; gap: 10px; margin-top: 24px; }
.nx-ar-btn {
    flex: 1; padding: 13px 20px; border-radius: 12px; border: 1px solid transparent;
    font-size: 14px; font-weight: 700; cursor: pointer; text-align: center;
    text-decoration: none !important; transition: filter .18s, opacity .18s;
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
}
.nx-ar-btn.primario { background: var(--nx-ok); color: #04220f; }
.nx-ar-btn.primario:hover { filter: brightness(1.08); }
.nx-ar-btn.primario[disabled] { opacity: .55; cursor: progress; }
.nx-ar-btn.neutro { flex: 0 0 auto; background: transparent;
    border-color: var(--nx-border); color: var(--nx-txt2); }
.nx-ar-btn.neutro:hover { border-color: var(--nx-border3); color: var(--nx-txt1); }

.nx-ar-aviso { padding: 24px; text-align: center; }
.nx-ar-aviso p { color: var(--nx-txt2); font-size: 14px; margin: 0 0 18px; line-height: 1.55; }

[data-theme="light"] .nx-ar-btn.primario { color: #fff; }
[data-theme="light"] .nx-ar-money input:focus { box-shadow: 0 0 0 3px rgba(3,105,161,.16); }
</style>

<div class="nx-ar-wrap">
    <div class="nx-ar-card">

        <div class="nx-ar-head">
            <div class="nx-ar-ico <?= $open_cash ? '' : 'locked' ?>">
                <i class="fa <?= $open_cash ? 'fa-inbox' : 'fa-lock' ?>"></i>
            </div>
            <div>
                <h2><?= lang('apertura_caja_titulo'); ?></h2>
                <p><?= $open_cash ? lang('apertura_caja_desc') : lang('apertura_bloqueada_desc'); ?></p>
            </div>
        </div>

        <div class="nx-ar-meta">
            <div>
                <span><?= lang('user'); ?></span>
                <strong><?= html_escape($cajero !== '' ? $cajero : '—'); ?></strong>
            </div>
            <?php if ($tienda !== ''): ?>
            <div>
                <span><?= lang('apertura_tienda'); ?></span>
                <strong><?= html_escape($tienda); ?></strong>
            </div>
            <?php endif; ?>
            <div>
                <span><?= lang('date'); ?></span>
                <strong><?= date('d/m/Y H:i'); ?></strong>
            </div>
        </div>

        <?php if ($open_cash): ?>
            <?php echo form_open_multipart('pos/open_register', ['role' => 'form', 'id' => 'open-register-form']); ?>
            <div class="nx-ar-body">
                <label class="nx-ar-label" for="cash_in_hand"><?= lang('cash_in_hand'); ?></label>

                <div class="nx-ar-money">
                    <span class="sym"><?= html_escape($simbolo); ?></span>
                    <input type="text" inputmode="decimal" autocomplete="off" autofocus
                           id="cash_in_hand" name="cash_in_hand" value="" placeholder="0">
                </div>
                <span class="nx-ar-hint" id="cash_in_hand_hint"><?= lang('apertura_monto_ayuda'); ?></span>

                <div class="nx-ar-chips">
                    <?php foreach ($sugeridos as $monto): ?>
                        <button type="button" class="nx-ar-chip" data-monto="<?= $monto; ?>">
                            <?= html_escape($simbolo . number_format($monto, 0, ',', ' ')); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="nx-ar-foot">
                    <button type="button" class="nx-ar-btn primario" id="open_register">
                        <i class="fa fa-unlock-alt"></i> <?= lang('aperturar_caja'); ?>
                    </button>
                </div>
            </div>
            <?php echo form_close(); ?>
        <?php else: ?>
            <div class="nx-ar-aviso">
                <p><?= lang('no_puede_aperturar'); ?></p>
                <a href="<?= site_url('welcome'); ?>" class="nx-ar-btn neutro">
                    <i class="fa fa-arrow-left"></i> <?= lang('apertura_volver'); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
(function () {
    var campo = document.getElementById('cash_in_hand');
    var boton = document.getElementById('open_register');
    var forma = document.getElementById('open-register-form');
    var ayuda = document.getElementById('cash_in_hand_hint');

    if (!campo || !boton || !forma) { return; }

    var AYUDA = ayuda ? ayuda.textContent : '';
    var ERROR = <?= json_encode(lang('apertura_monto_invalido')); ?>;

    /* El controlador valida 'numeric': un separador de miles lo haria fallar. */
    function valido() {
        var v = campo.value.trim().replace(',', '.');
        return v !== '' && !isNaN(v) && parseFloat(v) >= 0;
    }

    function marcar(ok) {
        campo.classList.toggle('invalido', !ok);
        if (!ayuda) { return; }
        ayuda.classList.toggle('error', !ok);
        ayuda.textContent = ok ? AYUDA : ERROR;
    }

    campo.addEventListener('input', function () {
        if (campo.classList.contains('invalido')) { marcar(valido()); }
    });

    campo.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); boton.click(); }
    });

    Array.prototype.forEach.call(document.querySelectorAll('.nx-ar-chip'), function (chip) {
        chip.addEventListener('click', function () {
            campo.value = chip.dataset.monto;
            marcar(true);
            campo.focus();
        });
    });

    boton.addEventListener('click', function () {
        if (!valido()) { marcar(false); campo.focus(); return; }
        campo.value = campo.value.trim().replace(',', '.');
        boton.disabled = true;
        forma.submit();
    });
})();
</script>

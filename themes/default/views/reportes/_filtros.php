<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) or exit('No direct script access allowed');

/**
 * Barra de filtros común a todo el módulo.
 *
 * Es una sola pieza a propósito: cuando cada informe dibujaba su propio
 * formulario, unos campos llegaban al controlador y otros se quedaban en
 * pantalla sin efecto. Acá el `name` de cada campo es exactamente la clave que
 * `Reporte_model::filtro()` reconoce, así que un filtro dibujado es un filtro
 * que llega.
 *
 * `$campos` limita cuáles se muestran; sin él salen todos.
 */
$campos    = isset($campos) ? $campos : null;
$catalogos = isset($catalogos) ? $catalogos : array();
$ambitos   = isset($ambitos) ? $ambitos : array();

$ver = function ($k) use ($campos) {
    return $campos === null || in_array($k, $campos, true);
};
$hoy   = date('Y-m-d');
$desde = date('Y-m-01');
?>

<form id="nxrFiltros" class="nxr-filtros" autocomplete="off">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
           value="<?= $this->security->get_csrf_hash(); ?>">

    <div class="nxr-fgrid">
        <?php if ($ver('fechas')): ?>
        <label class="nxr-f">
            <span>Desde</span>
            <input type="date" name="start_date" value="<?= $desde; ?>">
        </label>
        <label class="nxr-f">
            <span>Hasta</span>
            <input type="date" name="end_date" value="<?= $hoy; ?>">
        </label>
        <?php endif; ?>

        <?php if ($ver('ambito') && $ambitos): ?>
        <label class="nxr-f nxr-f-2">
            <span>Ámbito
                <i class="nxr-ayuda" tabindex="0"
                   title="Decide qué comprobantes entran. Una factura anulada con nota de crédito sale de la gestión interna pero sigue en la declaración fiscal, porque es su nota la que la compensa.">?</i>
            </span>
            <select name="ambito">
                <?php foreach ($ambitos as $k => $a): ?>
                    <option value="<?= $k; ?>"><?= html_escape($a['etiqueta']); ?> — <?= html_escape($a['ayuda']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>

        <?php
        $selects = array(
            'store_id'    => array('Sucursal',      'sucursales'),
            'register_id' => array('Caja',          'cajas'),
            'user_id'     => array('Usuario',       'usuarios'),
            'customer_id' => array('Cliente',       'clientes'),
            'category_id' => array('Categoría',     'categorias'),
            'product_id'  => array('Producto',      'productos'),
            'paid_by'     => array('Medio de pago', 'medios'),
            'estado'      => array('Estado',        'estados'),
            'tipo_doc'    => array('Tipo doc.',     'tipos_doc'),
            'tarifa'      => array('Tarifa',        'tarifas'),
        );
        foreach ($selects as $name => $cfg):
            if (!$ver($name) || empty($catalogos[$cfg[1]])) {
                continue;
            }
            ?>
            <label class="nxr-f">
                <span><?= $cfg[0]; ?></span>
                <select name="<?= $name; ?>">
                    <option value="">Todos</option>
                    <?php foreach ($catalogos[$cfg[1]] as $o): ?>
                        <option value="<?= html_escape($o['id']); ?>">
                            <?= html_escape(trim($o['name']) !== '' ? $o['name'] : $o['id']); ?>
                            <?php if (!empty($o['cf2'])): ?>· <?= html_escape($o['cf2']); ?><?php endif; ?>
                            <?php if (!empty($o['code'])): ?>· <?= html_escape($o['code']); ?><?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endforeach; ?>

        <?php if ($ver('identificacion')): ?>
        <label class="nxr-f">
            <span>Identificación</span>
            <input type="text" name="identificacion" placeholder="Cédula del cliente">
        </label>
        <?php endif; ?>

        <?php if ($ver('documento')): ?>
        <label class="nxr-f">
            <span>Documento</span>
            <input type="text" name="documento" placeholder="Consecutivo o clave">
        </label>
        <?php endif; ?>

        <?php if ($ver('cabys')): ?>
        <label class="nxr-f">
            <span>CABYS</span>
            <input type="text" name="cabys" placeholder="Código o prefijo">
        </label>
        <?php endif; ?>

        <?php if ($ver('monto')): ?>
        <label class="nxr-f">
            <span>Monto desde</span>
            <input type="number" name="monto_min" step="0.01" placeholder="0">
        </label>
        <label class="nxr-f">
            <span>Monto hasta</span>
            <input type="number" name="monto_max" step="0.01" placeholder="Sin tope">
        </label>
        <?php endif; ?>
    </div>

    <div class="nxr-facciones">
        <button type="submit" class="nxt-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="10" cy="10" r="7"/><path d="M21 21l-6-6"/></svg>
            Aplicar
        </button>
        <button type="reset" class="nxt-btn nxt-btn-ghost">Limpiar</button>
        <span class="nxr-atajos">
            <button type="button" class="nxr-atajo" data-rango="hoy">Hoy</button>
            <button type="button" class="nxr-atajo" data-rango="ayer">Ayer</button>
            <button type="button" class="nxr-atajo" data-rango="semana">Semana</button>
            <button type="button" class="nxr-atajo" data-rango="mes">Mes</button>
            <button type="button" class="nxr-atajo" data-rango="mes_ant">Mes anterior</button>
            <button type="button" class="nxr-atajo" data-rango="anio">Año</button>
        </span>
        <span class="nxr-estado" id="nxrEstado"></span>
    </div>
</form>

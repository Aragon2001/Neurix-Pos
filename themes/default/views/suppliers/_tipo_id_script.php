<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Afloja los campos obligatorios del proveedor segun su tipo de identificacion.
 * Comparten los mismos id el alta y la edicion de proveedores y el modal de la
 * factura de compra.
 */
?>
<script>
(function () {
    'use strict';

    var tipo = document.getElementById('tcedula');
    if (!tipo) { return; }

    // Ni el "Extranjero No Domiciliado" ni el "No Contribuyente" estan inscritos
    // ante Hacienda: no tienen actividad economica, ni ubicacion en el pais, ni
    // se les puede exigir contacto (Anexos v4.4, nota 4).
    var SIN_PADRON = ['05', '06'];
    var CAMPOS = ['txtCodActEco', 'codigo_provincia', 'codigo_canton',
                  'codigo_distrito', 'codigo_barrio', 'txtTel'];

    var aviso = document.createElement('div');
    aviso.className = 'alert alert-info';
    aviso.style.cssText = 'display:none;font-size:.85rem;margin-top:8px;';
    aviso.textContent = <?= json_encode(lang('no_contribuyente_ayuda')); ?>;
    if (tipo.parentNode) { tipo.parentNode.appendChild(aviso); }

    function aplicar() {
        var suelto = SIN_PADRON.indexOf(tipo.value) !== -1;

        CAMPOS.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) { return; }
            if (suelto) { el.removeAttribute('required'); } else { el.setAttribute('required', 'required'); }
        });

        aviso.style.display = (tipo.value === '06') ? 'block' : 'none';
    }

    tipo.addEventListener('change', aplicar);
    aplicar();
})();
</script>

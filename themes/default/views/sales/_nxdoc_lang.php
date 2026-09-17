<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed');

/* =====================================================================
 | Rotulos y token para window.NxDoc (nx-doc.js).
 |
 | El componente trae respaldo en espanol, asi que aca solo viajan las
 | claves: lo que decide el idioma es app_lang.php, no el JavaScript.
 |
 | Incluir en cualquier vista que abra el detalle de un comprobante.
 * =================================================================== */

$nxd_claves = array(
    // Ventana
    'titulo', 'cerrar', 'cargando', 'error_carga', 'no_disponible', 'doc_nota_credito',
    // Pestanas
    'tab_documento', 'tab_xml', 'tab_respuesta', 'tab_notas', 'tab_bitacora',
    // Acciones
    'reenviar', 'reenviando', 'reenvio_ok', 'reenvio_error',
    'imprimir_original', 'imprimir_tiquete', 'descargar_pdf', 'descargar_xml', 'descargar_acuse',
    'enviar_correo', 'enviar_whatsapp', 'nota_credito', 'nota_debito', 'devolucion',
    'anular_factura', 'marcar_anulado', 'rehacer', 'corregir',
    'grupo_hacienda', 'grupo_envio', 'grupo_impresion', 'grupo_ajustes',
    'confirmar_anular', 'confirmar_reenviar', 'aceptado_inmutable',
    // Clave
    'copiar', 'copiado', 'clave', 'desglose_clave',
    'clave_pais', 'clave_fecha', 'clave_cedula', 'clave_casa', 'clave_terminal',
    'clave_tipo', 'clave_numero', 'clave_situacion', 'clave_seguridad',
    // Vacios
    'sin_xml', 'sin_respuesta', 'sin_notas', 'sin_bitacora', 'sin_lineas', 'sin_pagos',
    // Visor
    'buscar_xml', 'coincidencias',
    // Impresion
    'impresora_no_configurada', 'qz_desconectado', 'impresion_error', 'impresion_ok',
    // Correo y WhatsApp
    'destinatarios', 'agregar_destinatario', 'sin_destinatarios',
    'correo_enviado', 'correo_fallido', 'correo_invalido',
    'whatsapp_sin_telefono', 'whatsapp_texto',
    // Documento
    'emisor', 'receptor', 'cajero', 'linea_cabys', 'linea_unitario', 'linea_iva',
    'totales', 'forma_pago', 'saldo', 'vuelto', 'actividad',
    'mensaje_hacienda', 'detalle_mensaje', 'monto_impuesto', 'total_declarado',
);

// Rotulos del panel de anulacion; van con su nombre completo porque los
// comparte la pantalla de Facturas anuladas.
$nxd_claves_anu = array(
    'titulo_fiscal', 'titulo_interna', 'explica_fiscal', 'explica_interna',
    'motivo', 'motivo_ayuda', 'motivo_requerido',
    'devuelve', 'devuelve_ayuda', 'monto', 'medio',
    'medio_efectivo', 'medio_sinpe', 'medio_transferencia', 'medio_tarjeta',
    'pin', 'pin_ayuda', 'sin_pin_configurado', 'sin_caja',
    'confirmar', 'fallida', 'ok_fiscal', 'ok_interna',
    'ya_anulada', 'anulada_el', 'devolvio', 'sin_devolucion', 'ver_nota',
    'tipo_fiscal', 'tipo_interna', 'cajon_abierto',
    'titulo', 'motivo_ph', 'ok', 'estado',
    'estado_firme', 'estado_firme_nota', 'estado_pendiente', 'estado_pendiente_nota',
    'estado_fallida', 'estado_fallida_nota',
    'reintentar_nota', 'reintentando', 'reintento_ok',
);

$nxd_lang = array();
foreach ($nxd_claves as $clave) {
    $nxd_lang[$clave] = lang('nxd_' . $clave);
}
foreach ($nxd_claves_anu as $clave) {
    $nxd_lang['anu_' . $clave] = lang('anu_' . $clave);
}

// Rotulos generales que el componente reusa con su nombre corto.
$nxd_lang = array_merge($nxd_lang, array(
    'cantidad'       => lang('quantity'),
    'descripcion'    => lang('description'),
    'total'          => lang('total'),
    'pagado'         => lang('paid'),
    'subtotal'       => lang('subtotal'),
    'descuento'      => lang('discount'),
    'fecha'          => lang('date'),
    'nota'           => lang('note'),
    'referencia'     => lang('reference'),
    'monto'          => lang('amount'),
    'consecutivo'    => lang('consecutive'),
    'tienda'         => lang('store'),
    'identificacion' => lang('identificacion'),
    'correo'         => lang('email'),
    'telefono'       => lang('phone'),
    'acciones'       => lang('actions'),
    'mas_acciones'   => lang('nxd_mas_acciones'),
    'consultar'      => lang('nxd_consultar'),
    'ambiente_pruebas' => lang('ambiente_pruebas_aviso'),
    'confirmar_reemitir' => lang('nxd_confirmar_reemitir'),
));
?>
<script>
window._nxdCsrf = { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' };
window._nxdLang = <?= json_encode($nxd_lang, JSON_UNESCAPED_UNICODE); ?>;
</script>

<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
(defined('BASEPATH')) OR exit('No direct script access allowed'); ?>
<?php
/* =====================================================================
 | Datos de marca del emisor, comunes a todos los comprobantes.
 |
 | Resuelve en variables listas para pintar:
 |   $emisor_nombre  nombre comercial (o razon social, o tienda)
 |   $emisor_razon   razon social, si difiere del anterior
 |   $logo_file      archivo de logo en uploads/, '' si no hay
 |   $monograma      iniciales de respaldo cuando no hay logo
 |   $doc_titulo     rotulo del tipo de documento
 |   $ident_label    etiqueta del tipo de identificacion del cliente
 |
 | Antes de incluirlo se puede fijar $doc_titulo_forzado para los
 | documentos que no se deducen de $hacienda->tipo_doc (proforma,
 | nota de debito, tiquete de parquimetro...).
 * =================================================================== */

if (!function_exists('nx_linea_comprobante')) {
    /**
     * Normaliza una linea de comprobante.
     *
     * Las tablas de lineas no comparten nombres de columna:
     *   tec_sale_items         unit_price / net_unit_price / item_tax / subtotal
     *   tec_note_credits_items unit_price / price          / item_tax   (sin subtotal)
     *   tec_note_debits_items  unit_price                  / item_tax   (sin subtotal)
     * Esta funcion devuelve siempre las mismas claves para que la plantilla
     * de lineas sirva para facturas, notas y proformas por igual.
     *
     * @param object $row
     * @return array{precio: float, subtotal: float, gravado: bool, iva: string, unidad: string}
     */
    function nx_linea_comprobante($row) {
        $cant = isset($row->quantity) ? (float) $row->quantity : 0;

        $tasa = tasa_iva_linea($row);

        // Precio unitario: se toma el primer campo con valor util
        $precio = null;
        foreach (array('net_unit_price', 'unit_price', 'product_unit_price') as $c) {
            if (isset($row->$c) && $row->$c !== null && (float) $row->$c != 0) {
                $precio = (float) $row->$c;
                break;
            }
        }

        // Subtotal de la linea
        $subtotal = null;
        foreach (array('subtotal', 'price') as $c) {
            if (isset($row->$c) && $row->$c !== null && (float) $row->$c != 0) {
                $subtotal = (float) $row->$c;
                break;
            }
        }

        // Completar el que falte a partir del otro
        if ($precio === null && $subtotal !== null && $cant != 0) {
            $precio = $subtotal / $cant;
        }
        if ($subtotal === null) {
            $subtotal = ($precio !== null ? $precio : 0) * $cant;
        }
        if ($precio === null) { $precio = 0; }

        // Mostrar el unitario con impuesto incluido cuando la linea es gravada
        if ($tasa > 0 && $cant != 0 && abs(($precio * $cant) - $subtotal) > 0.01) {
            $precio = $precio * (1 + ($tasa / 100));
        }

        return array(
            'precio'   => $precio,
            'subtotal' => $subtotal,
            'gravado'  => $tasa > 0,
            'iva'      => etiqueta_iva($tasa),
            'unidad'   => !empty($row->unit_of_measurement) ? (string) $row->unit_of_measurement : '',
        );
    }
}

$emisor_nombre = !empty($Settings->nombre_comercial)
    ? $Settings->nombre_comercial
    : (!empty($Settings->nombre_emisor)
        ? $Settings->nombre_emisor
        : (!empty($store->name) ? $store->name : @$Settings->site_name));

$emisor_razon = !empty($Settings->nombre_emisor) ? $Settings->nombre_emisor : '';

// Logo: primero el de la tienda, luego el general. Solo si el archivo existe.
$logo_file = '';
foreach (array(@$store->logo, @$Settings->logo, @$store->image) as $candidate) {
    if (!empty($candidate) && is_file(FCPATH . 'uploads/' . $candidate)) {
        $logo_file = $candidate;
        break;
    }
}

// Sin logo se arma un monograma con las iniciales del emisor.
$monograma = '';
if (!$logo_file) {
    $palabras = preg_split('/\s+/', trim(preg_replace('/[^\p{L}\s]/u', ' ', $emisor_nombre)));
    foreach ($palabras as $p) {
        if ($p !== '' && mb_strlen($monograma) < 2) {
            $monograma .= mb_strtoupper(mb_substr($p, 0, 1));
        }
    }
    if ($monograma === '') { $monograma = 'FE'; }
}

// Rotulo del tipo de documento
if (!empty($doc_titulo_forzado)) {
    $doc_titulo = $doc_titulo_forzado;
} else {
    $doc_titulo = lang('invoice');
    if (!empty($Settings->fe) && $Settings->fe == 1 && !empty($hacienda)) {
        $td = (string) @$hacienda->tipo_doc;
        if ($td === '4' || $td === '04')      { $doc_titulo = lang('electronic_bill'); }
        else if ($td === '1' || $td === '01') { $doc_titulo = 'Factura Electrónica'; }
        else if ($td === '0' || $td === '00') { $doc_titulo = 'Nota de Crédito Electrónica'; }
    } else if (empty($Settings->fe) || $Settings->fe != 1) {
        $doc_titulo = lang('regimen_simplificado');
    }
}

// Etiqueta del tipo de identificacion del cliente
$ident_labels = array(
    '01' => lang('Cedula Identidad'),
    '02' => lang('Cedula Juridica'),
    '03' => lang('Dimex'),
    '04' => lang('NITE'),
);
$ident_label = isset($ident_labels[@$customer->cf1]) ? $ident_labels[$customer->cf1] : '';

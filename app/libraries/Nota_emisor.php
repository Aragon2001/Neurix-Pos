<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Emisor de notas de credito y de debito sobre una venta.
 *
 * Es el unico camino por el que sale una nota: guarda, arma el XML, lo firma,
 * lo manda a Hacienda y devuelve como quedo. La anulacion del detalle de venta
 * y el ajuste desde el POS entran los dos por aca, para que no haya dos
 * pipelines fiscales que se puedan desincronizar.
 *
 * Las lineas llegan normalizadas por el motor (ajuste_helper.php) o crudas de
 * la venta; _fila() las traduce a lo que espera cada tabla.
 */
class Nota_emisor
{
    /** @var CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('pos_model');
        $this->CI->load->model('hacienda_model');
        $this->CI->load->helper('pos');
        $this->CI->load->library('Crearxml', NULL, 'Crearxml');
    }

    /* ═══════════════════════════ API ═══════════════════════════ */

    /**
     * Emite una nota de credito sobre la venta.
     *
     * @param  object $venta   fila de tec_sales
     * @param  object $hac     fila de tec_hacienda_tiketes de esa venta
     * @param  array  $lineas  lineas de la nota (normalizadas o crudas)
     * @param  string $codigo  codigo de referencia v4.4 ('01' anula, '06' devolucion)
     * @param  string $motivo  razon; viaja a Hacienda dentro de <Razon>
     * @return array|false
     */
    public function credito($venta, $hac, array $lineas, $codigo, $motivo)
    {
        if (!$lineas) {
            return false;
        }

        $tot  = $this->_totales($lineas);
        $data = $this->_cabecera($venta, $motivo, $tot, array(
            'type_nc'           => codigo_referencia_valido($codigo, '06'),
            'product_discount'  => $tot['descuento'],
            'order_discount_id' => null,
            'order_discount'    => 0,
            'product_tax'       => $tot['impuesto'],
            'order_tax_id'      => null,
            'order_tax'         => 0,
            'rounding'          => 0,
            'paid'              => 0,
            'status'            => $venta->payment_status,
        ));

        // La cabecera lleva claves que solo necesita el XML —id_actividad no es
        // columna de note_credits— y CI inserta el arreglo tal cual.
        $creada = $this->CI->pos_model->addNoteCredit(
            $this->_soloColumnas($data, 'note_credits'),
            $this->_filas($lineas, 'note_credits_items'), $data['type_nc'], null
        );
        if (!$creada) {
            return false;
        }
        $id_cn = $creada['id_cn'];

        $nota = $this->CI->Crearxml->getNotaCredito($data, $this->_paraXml($lineas), $hac, null);
        if (!$nota) {
            return false;
        }
        $nota['id_cn'] = $id_cn;
        $nota['xml_sign'] = $this->_firmar($nota['xml'], '03');

        $this->CI->hacienda_model->insertxmlCN($nota);

        return array(
            'documento'   => 'NC',
            'id'          => $id_cn,
            'consecutivo' => $nota['consecutivo'],
            'clave'       => $nota['clave'],
            'total'       => $tot['total'],
            'estado'      => $this->_enviar('CN', $id_cn),
            'url'         => site_url('creditnotes/viewnc/' . $id_cn),
        );
    }

    /**
     * Emite una nota de debito sobre la venta.
     *
     * @see credito() mismos parametros; el codigo por omision es el 04
     */
    public function debito($venta, $hac, array $lineas, $codigo, $motivo)
    {
        if (!$lineas) {
            return false;
        }

        $tot  = $this->_totales($lineas);
        $data = $this->_cabecera($venta, $motivo, $tot, array(
            'motivo_nd'    => codigo_referencia_valido($codigo, '04'),
            'type_nd'      => codigo_referencia_valido($codigo, '04'),
            'id_actividad' => isset($venta->id_actividad) && $venta->id_actividad
                ? $venta->id_actividad : $this->CI->Settings->default_actividad,
            'status'       => $venta->payment_status,
        ));

        // note_debits no tiene las columnas de descuento y de impuesto por
        // separado que si tiene note_credits.
        unset($data['total_items'], $data['total_quantity'], $data['note'], $data['motivo']);

        $id_nd = $this->CI->pos_model->addNoteDebit(
            $this->_soloColumnas($data, 'note_debits'),
            $this->_filas($lineas, 'note_debits_items')
        );
        if (!$id_nd) {
            return false;
        }

        $nd = $this->CI->pos_model->getDebitNoteByID($id_nd);
        $nota = $this->CI->Crearxml->getNotaDebito((array) $nd, $this->_paraXml($lineas), $hac, array());
        if (!$nota) {
            return false;
        }

        $this->CI->hacienda_model->insertxmlND(array(
            'nd_id'            => $id_nd,
            'sale_id'          => (int) $venta->id,
            'clave'            => $nota['clave'],
            'consecutivo'      => $nota['consecutivo'],
            'fecha_emision'    => $nota['fecha_emision'],
            'estatus_hacienda' => 'pendiente',
            'xml'              => $nota['xml'],
            'xml_sign'         => $this->_firmar($nota['xml'], '02'),
            'mail'             => 0,
        ));

        return array(
            'documento'   => 'ND',
            'id'          => $id_nd,
            'consecutivo' => $nota['consecutivo'],
            'clave'       => $nota['clave'],
            'total'       => $tot['total'],
            'estado'      => $this->_enviar('ND', $id_nd),
            'url'         => site_url('debitnotes/viewnd/' . $id_nd),
        );
    }

    /* ═══════════════════════ ARMADO DE DATOS ═══════════════════════ */

    /** Cabecera comun a las dos notas. */
    private function _cabecera($venta, $motivo, array $tot, array $propias)
    {
        return array_merge(array(
            'sale_id'        => (int) $venta->id,
            'date'           => date('Y-m-d H:i:s'),
            'customer_id'    => (int) $venta->customer_id,
            'customer_name'  => $venta->customer_name,
            'total'          => $tot['subtotal'],
            'total_discount' => $tot['descuento'],
            'total_tax'      => $tot['impuesto'],
            'grand_total'    => $tot['total'],
            'total_items'    => $tot['lineas'],
            'total_quantity' => $tot['cantidad'],
            'created_by'     => (int) $this->CI->session->userdata('user_id'),
            'store_id'       => (int) $venta->store_id,
            'note'           => $motivo,
            'motivo'         => mb_substr((string) $motivo, 0, 255),
            'hold_ref'       => mb_substr((string) $motivo, 0, 180),
            'id_actividad'   => isset($venta->id_actividad) && $venta->id_actividad
                ? $venta->id_actividad : $this->CI->Settings->default_actividad,
        ), $propias);
    }

    /**
     * Totales de la nota, calculados sobre sus propias lineas.
     *
     * No se copian de la venta: la nota puede llevar solo una parte.
     */
    private function _totales(array $lineas)
    {
        $subtotal = 0.0; $impuesto = 0.0; $descuento = 0.0; $cantidad = 0.0;

        foreach ($lineas as $l) {
            $f = $this->_fila($l);
            $subtotal  += (float) $f['subtotal'];
            $impuesto  += (float) $f['item_tax'];
            $descuento += (float) $f['item_discount'];
            $cantidad  += (float) $f['quantity'];
        }

        return array(
            'subtotal'  => round($subtotal, 4),
            'descuento' => round($descuento, 4),
            'impuesto'  => round($impuesto, 4),
            'total'     => round($subtotal + $impuesto, 4),
            'cantidad'  => round($cantidad, 4),
            'lineas'    => count($lineas),
        );
    }

    /**
     * Traduce una linea a las claves que usan las tablas y Crearxml.
     *
     * Acepta la forma del motor de ajuste ('cantidad', 'neto', 'tasa') y la de
     * tec_sale_items, para que quien llame no tenga que convertir.
     */
    private function _fila($l)
    {
        $l = (array) $l;
        if (!isset($l['cantidad'])) {
            $l = ajuste_linea($l);
        }

        $cantidad = (float) $l['cantidad'];
        $neto     = (float) $l['neto'];
        $bruto    = (float) $l['bruto'];
        $subtotal = round($cantidad * $neto, 4);

        return array(
            'product_id'          => (int) $l['product_id'],
            'product_code'        => (string) $l['codigo'],
            'product_name'        => (string) $l['nombre'],
            'quantity'            => $cantidad,
            'unit_price'          => $bruto,
            // Crearxml lee real_unit_price para el PrecioUnitario de la linea.
            'real_unit_price'     => $bruto,
            'net_unit_price'      => $neto,
            'price'               => $subtotal,
            'subtotal'            => $subtotal,
            'discount'            => '0',
            'item_discount'       => round($cantidad * max(0, $bruto - $neto), 4),
            // La nota se declara como se declaro la venta: con la tarifa
            // agregada al precio, o con la porcion contenida prorrateada.
            'tax'                 => $this->_tax($l),
            'item_tax'            => $l['tasa'] > 0
                ? round($subtotal * ($l['tasa'] / 100), 4)
                : round($subtotal * (isset($l['imp_ratio']) ? (float) $l['imp_ratio'] : 0), 4),
            'id_tax'              => (int) $l['id_tax'],
            'cost'                => (float) $l['costo'],
            'comment'             => (string) $l['comentario'],
            'unit_of_measurement' => (string) $l['unidad'],
            'cabys'               => (string) $l['cabys'],
            'codigo_impuesto'     => (string) $l['cod_impuesto'],
            'codigo_tarifa'       => (string) $l['cod_tarifa'],
        );
    }

    /** La tarifa tal como se facturo; si no viaja, la que calculo el motor. */
    private function _tax($l)
    {
        if (!empty($l['tax_txt'])) {
            return (string) $l['tax_txt'];
        }
        return $l['tasa'] > 0 ? $l['tasa'] . '%' : '';
    }

    /** Las lineas como las quiere Crearxml: con codigo_impuesto y codigo_tarifa. */
    private function _paraXml(array $lineas)
    {
        return array_map(array($this, '_fila'), $lineas);
    }

    /**
     * Las lineas recortadas a las columnas que la tabla realmente tiene.
     *
     * La linea de ajuste a tanto alzado —una correccion de precio o de
     * descuento— va sin product_id: addNoteCredit devuelve existencias por cada
     * linea con producto, y ahi no volvio mercancia, solo bajo el importe. El
     * XML si conserva el producto, porque lo arma _paraXml() aparte.
     */
    private function _filas(array $lineas, $tabla)
    {
        $out = array();
        foreach ($lineas as $l) {
            $fila = $this->_fila($l);
            if (!empty($l['ajuste_parcial'])) {
                $fila['product_id'] = 0;
            }
            $out[] = $this->_soloColumnas($fila, $tabla);
        }
        return $out;
    }

    /**
     * Recorta un arreglo a las columnas existentes de la tabla.
     *
     * CI inserta el arreglo tal cual: una clave de mas —codigo_tarifa, que solo
     * necesita el XML— tumba el INSERT entero sin decir cual sobraba.
     */
    private function _soloColumnas(array $datos, $tabla)
    {
        $columnas = array_flip($this->CI->db->list_fields($this->CI->db->dbprefix($tabla)));
        return array_intersect_key($datos, $columnas);
    }

    /* ═══════════════════════ FIRMA Y ENVIO ═══════════════════════ */

    /** @param string $tipo codigo de comprobante ('03' NC, '02' ND) */
    private function _firmar($xml, $tipo)
    {
        $certificado = './files/certificados/' . $this->CI->Settings->ambiente . '/'
            . $this->CI->Settings->certificado_ced . '.p12';

        if (!file_exists($certificado)) {
            return null;
        }

        $this->CI->load->library('firmar', NULL, 'firmar');
        try {
            $firmado = $this->CI->firmar->firmar(
                $certificado, $this->CI->Settings->certificado_pin, $xml, $tipo
            );
            return $firmado ? base64_decode($firmado) : null;
        } catch (\Throwable $e) {
            log_message('error', 'Nota_emisor: no se pudo firmar la nota: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Manda la nota a Hacienda y devuelve como quedo.
     *
     * @param string $doc 'CN' o 'ND'
     */
    private function _enviar($doc, $id)
    {
        $fila = $doc === 'CN'
            ? $this->CI->hacienda_model->getCN($id)
            : $this->CI->hacienda_model->getND($id);

        if (!$fila || trim((string) $fila->xml_sign) === '') {
            return estado_hacienda_info($fila ? $fila->estatus_hacienda : 'pendiente');
        }

        $this->CI->load->library('Apiclient', NULL, 'ApiClient');
        $this->CI->ApiClient->getTokenH();

        $respuesta = $this->CI->ApiClient->send_invoice(array(
            'xml'           => $fila->xml,
            'xml_sign'      => $fila->xml_sign,
            'clave'         => $fila->clave,
            'consecutivo'   => $fila->consecutivo,
            'fecha_emision' => $fila->fecha_emision,
        ));
        $this->CI->ApiClient->CloseTokenH();

        $mensaje = isset($respuesta['mensajeHacienda']->respuestaxml)
            ? base64_decode($respuesta['mensajeHacienda']->respuestaxml) : '';
        $estatus = isset($respuesta['mensajeHacienda']->indestado)
            ? $respuesta['mensajeHacienda']->indestado : 'Sin Estado';

        $guardar = array(
            'xml_sign'         => isset($respuesta['xml_firmado'])
                ? base64_decode($respuesta['xml_firmado']) : $fila->xml_sign,
            'xml_hacienda'     => $mensaje,
            'estatus_hacienda' => $estatus,
        );

        if ($doc === 'CN') {
            $this->CI->hacienda_model->insertHaciendaCN($guardar, $fila->clave);
        } else {
            $this->CI->hacienda_model->insertHaciendaND($guardar, $fila->clave);
        }

        return estado_hacienda_info($estatus);
    }
}

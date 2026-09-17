<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Correo de una venta con su comprobante, XML firmado y respuesta de Hacienda.
 *
 * Una factura puede quedar aceptada en tres lugares: la tanda de Shacienda, la
 * consulta y el reenvio desde Ventas. Los tres llaman a enviarSiAceptada(), asi
 * que el correo sale en cuanto se sabe, no la proxima vez que alguien abra el POS.
 */
class Correo_comprobante
{
    public function __get($var)
    {
        return get_instance()->$var;
    }

    /**
     * Encola el correo al cliente si la venta ya fue aceptada y no se envio.
     *
     * La marca `mail` se reclama con un UPDATE condicionado antes de encolar:
     * la tanda del POS y una consulta desde Ventas pueden coincidir, y sin eso
     * el cliente recibiria dos correos.
     *
     * @return string 'encolado' | 'sin_correo' | 'no_aplica'
     */
    public function enviarSiAceptada($sale_id)
    {
        $this->load->model('hacienda_model');
        $this->db->where('sale_id', (int) $sale_id)
            ->where('estatus_hacienda', 'aceptado')
            ->where('mail', '0')
            ->update('hacienda_tiketes', array('mail' => '1'));
        if ($this->db->affected_rows() < 1) {
            return 'no_aplica';
        }

        $venta = $this->pos_model_cargado()->getSaleByID($sale_id);
        $cliente = $venta ? $this->pos_model->getCustomerByID($venta->customer_id) : null;
        $para = $cliente ? trim((string) $cliente->email) : '';
        if ($para === '' || !filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return 'sin_correo';
        }

        if (!$this->encolar($sale_id, $para)) {
            // No se pudo armar: se libera la marca para que la proxima tanda lo reintente.
            $this->hacienda_model->MarcaEnviado($sale_id, '0');
            return 'no_aplica';
        }
        return 'encolado';
    }

    /** Arma el comprobante y lo deja en la cola de correo. @return bool */
    public function encolar($sale_id, $para)
    {
        try {
            $this->load->model('hacienda_model');
            $datos = $this->_datos($sale_id);
            if (!$datos) {
                return false;
            }

            $recibo = $this->load->view($this->theme . 'pos/view', $datos, TRUE);
            $recibo = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $recibo);

            $hac   = $datos['hacienda'];
            $firma = $this->hacienda_model->xmlFirmado($sale_id);
            $resp  = $this->hacienda_model->xmlMensaje($sale_id);
            $clave = !empty($hac->clave) ? $hac->clave : (string) $sale_id;
            $consecutivo = (string) ($hac->consecutivo ?? '');
            $tipos = tipos_comprobante();

            $mensaje = $this->cuerpo(array(
                'tipo'        => $tipos[substr($consecutivo, 8, 2)] ?? lang('correo_tipo_fe'),
                'cliente'     => $datos['customer']->name ?? '',
                'consecutivo' => $consecutivo,
                'clave'       => $clave,
                'fecha'       => $this->tec->hrld($datos['inv']->date ?? ''),
                'total'       => $this->tec->formatMoney($datos['inv']->grand_total ?? 0),
                'adjuntos'    => array(lang('correo_adjunto_pdf'), lang('correo_adjunto_xml'), lang('correo_adjunto_respuesta')),
                'nota'        => lang('correo_nota_fe'),
            ));

            $html = $this->tec->pdf_html($recibo);
            $pdf  = sys_get_temp_dir() . '/T4_' . $clave . '.pdf';
            $mpdf = new \Mpdf\Mpdf(array(
                'tempDir' => sys_get_temp_dir(), 'CSSselectMedia' => 'screen', 'format' => 'A4',
                'margin_left' => 12, 'margin_right' => 12, 'margin_top' => 12, 'margin_bottom' => 12,
            ));
            $mpdf->WriteHTML($html);
            $mpdf->Output($pdf, 'F');

            $this->load->model('queue_model');
            $this->queue_model->push(Queue_model::TYPE_EMAIL, array(
                'to'       => $para,
                'subject'  => lang('email_subject') . ' - ' . $this->Settings->site_name,
                'message'  => $mensaje,
                'attach'   => array(
                    'T4_' . $clave => $firma ? $firma->xml_sign : '',
                    'M4_' . $clave => $resp ? $resp->xml_hacienda : '',
                    'ruta'         => $pdf,
                ),
                'pdf_html' => $html,
                'pdf_path' => $pdf,
            ));
            dispatch_queue_worker(Queue_model::TYPE_EMAIL);
            return true;
        } catch (\Throwable $e) {
            log_message('error', '[Correo_comprobante] venta ' . (int) $sale_id . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cuerpo del correo que acompana a un comprobante.
     *
     * La vista de impresion no sirve de cuerpo: sus estilos viven en un <style>
     * del <head> que Gmail y Outlook descartan. El comprobante viaja como PDF.
     *
     * @param array $datos tipo, consecutivo, clave, fecha, total, cliente, adjuntos, nota, intro
     */
    public function cuerpo(array $datos)
    {
        $cliente  = trim((string) ($datos['cliente'] ?? ''));
        $contacto = array_filter(array(
            trim((string) $this->Settings->telefono_emisor),
            trim((string) $this->Settings->email_emisor),
        ));

        return $this->load->view($this->theme . 'email/comprobante', array('correo' => array(
            'emisor'        => $this->Settings->nombre_comercial ?: $this->Settings->site_name,
            'cedula_emisor' => trim((string) $this->Settings->cedula_emisor),
            'tipo'          => $datos['tipo'],
            'saludo'        => $cliente !== '' ? sprintf(lang('correo_saludo_nombre'), $cliente) : lang('correo_saludo'),
            'intro'         => $datos['intro'] ?? lang('correo_intro'),
            'consecutivo'   => $datos['consecutivo'] ?? '',
            'clave'         => $datos['clave'] ?? '',
            'fecha'         => $datos['fecha'] ?? '',
            'total'         => $datos['total'] ?? '',
            'adjuntos'      => $datos['adjuntos'] ?? array(),
            'nota'          => $datos['nota'] ?? '',
            'contacto'      => implode('  ·  ', $contacto),
        )), TRUE);
    }

    private function pos_model_cargado()
    {
        $this->load->model('pos_model');
        return $this->pos_model;
    }

    /** Lo que la vista del comprobante necesita. */
    private function _datos($sale_id)
    {
        $inv = $this->pos_model_cargado()->getSaleByID($sale_id);
        if (!$inv) {
            return null;
        }
        $this->load->helper('text');
        $hacienda = $this->hacienda_model->getInvoice($sale_id);

        return array_merge((array) get_instance()->data, array(
            'rows'           => $this->pos_model->getAllSaleItems($sale_id),
            'customer'       => $this->pos_model->getCustomerByID($inv->customer_id),
            'inv'            => $inv,
            'sid'            => $sale_id,
            'noprint'        => null,
            'page_title'     => lang('invoice'),
            'modal'          => false,
            'payments'       => $this->pos_model->getAllSalePayments($sale_id),
            'created_by'     => $this->site->getUser($inv->created_by),
            'hacienda'       => $hacienda,
            'store'          => $this->site->getStoreByID($inv->store_id),
            // El pie usa el QR; el codigo de barras PNG necesita la extension gd.
            'invoicebarcode' => null,
            'invoiceqr'      => !empty($hacienda->consecutivo) ? $this->tec->qrcode($hacienda->consecutivo, 4) : '',
        ));
    }
}

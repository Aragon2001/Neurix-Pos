<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

class PosEmail extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('pos_model');
        $this->load->model('hacienda_model');
        $this->load->model('queue_model');
    }

    /**
     * Cuerpo del correo que acompana a un comprobante.
     *
     * La vista de impresion no sirve de cuerpo: sus estilos viven en un <style>
     * del <head> que Gmail y Outlook descartan. Esta plantilla va aparte y el
     * comprobante completo viaja como PDF adjunto.
     *
     * @param array $datos tipo, consecutivo, clave, fecha, total, cliente, adjuntos, nota
     */
    private function _cuerpo_correo(array $datos)
    {
        $this->load->library('correo_comprobante');
        return $this->correo_comprobante->cuerpo($datos);
    }

    function email_receipt_credit($credit_id = NULL, $to = NULL) {
        $this->load->model('hacienda_model');
        
        if ($this->input->post('id')) {
            $credit_id = $this->input->post('id');
        }
        if ($this->input->post('email')) {
            $to = $this->input->post('email');
        }
        if (!$credit_id || !$to) {
            die();
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $invAux = $this->pos_model->getCreditNoteByID($credit_id);
        $sale_id = $invAux->sale_id;
        if($this->hacienda_model->getCN($credit_id)->estatus_hacienda =="aceptado"){
            $inv = $this->pos_model->getCreditNoteByID($credit_id);
            $this->tec->view_rights($inv->created_by);
            $this->load->helper('text');
            $this->data['rows'] = $this->pos_model->getAllCreditNoteItems($credit_id);
            $this->data['customer'] = $this->pos_model->getCustomerByID($inv->customer_id);
            $this->data['inv'] = $inv;
            $this->data['sid'] = $sale_id;
            $this->data['noprint'] = NULL;
            $this->data['page_title'] = lang('invoice');
            $this->data['modal'] = false;
            $this->data['payments'] = $this->pos_model->getAllSalePayments($sale_id);
            $this->data['credit_note'] = $invAux;
            $this->data['created_by'] = $this->site->getUser($inv->created_by);
            $this->data['hacienda'] = $this->hacienda_model->getCN($credit_id);
            $this->data['hacienda']->tipo_doc = "0";
            // El pie usa el QR; el codigo de barras PNG necesita la extension gd.
            $this->data['invoicebarcode'] = null;
            // La vista del comprobante necesita la tienda (logo y pie de recibo)
            // y usa el QR como identificacion interna.
            $this->data['store'] = $this->site->getStoreByID($inv->store_id);
            $this->data['invoiceqr'] = isset($this->data['hacienda']->consecutivo)
                ? $this->tec->qrcode($this->data['hacienda']->consecutivo, 4)
                : '';

            $receipt  = $this->load->view($this->theme . 'creditnotes/viewnc', $this->data, TRUE);
            $receipt  = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
            $subject  = lang('email_subject') . ' - ' . $this->Settings->site_name;

            $xml_sign     = $this->hacienda_model->xmlFirmadoCN($credit_id)->xml_sign;
            $xml_hacienda = $this->hacienda_model->xmlMensajeCN($credit_id)->xml_hacienda;
            $clave        = $this->hacienda_model->getClaveCN($credit_id)->clave;

            $message = $this->_cuerpo_correo([
                'tipo'        => lang('correo_tipo_nc'),
                'intro'       => lang('correo_intro_nc'),
                'cliente'     => $this->data['customer']->name ?? '',
                'consecutivo' => $this->data['hacienda']->consecutivo ?? '',
                'clave'       => $clave,
                'fecha'       => $this->tec->hrld($inv->date ?? ''),
                'total'       => $this->tec->formatMoney($inv->grand_total ?? 0),
                'adjuntos'    => [lang('correo_adjunto_pdf'), lang('correo_adjunto_xml'), lang('correo_adjunto_respuesta')],
                'nota'        => lang('correo_nota_fe'),
            ]);

            $this->data['tipo_documento'] = "Nota de Credito Electronica";
            // El adjunto reutiliza la vista con estilos; creditnotes/invoice
            // apuntaba a una hoja de estilo inexistente y salia sin formato.
            $html    = $this->tec->pdf_html($receipt);
            $pdfPath = sys_get_temp_dir() . '/T4_' . $clave . '.pdf';

            $mpdf = new \Mpdf\Mpdf(array(
                'tempDir'       => sys_get_temp_dir(),
            'CSSselectMedia' => 'screen',
                'format'        => 'A4',
                'margin_left'   => 12,
                'margin_right'  => 12,
                'margin_top'    => 12,
                'margin_bottom' => 12,
            ));
            $mpdf->WriteHTML($html);
            $mpdf->Output($pdfPath, 'F');

            $attach = [
                'T4_' . $clave => $xml_sign,
                'M4_' . $clave => $xml_hacienda,
                'ruta'         => $pdfPath,
            ];

            $this->load->model('queue_model');
            $this->queue_model->push(Queue_model::TYPE_EMAIL, [
                'to'       => $to,
                'subject'  => $subject,
                'message'  => $message,
                'attach'   => $attach,
                'pdf_html' => $html,
                'pdf_path' => $pdfPath,
            ]);
            dispatch_queue_worker(Queue_model::TYPE_EMAIL);
            echo json_encode(['msg' => lang('email_success'), 'queued' => true]);
        } else {
            echo json_encode(['msg' => 'El estado de la factura no se encuentra aceptada']);
        }
    }

    function email_receipt($sale_id = NULL, $to = NULL) {
        if ($this->input->post('id')) {
            $sale_id = $this->input->post('id');
        }
        if ($this->input->post('email')) {
            $to = $this->input->post('email');
        }
        if (!$sale_id || !$to) {
            die();
        }
        $inv = $this->pos_model->getSaleByID($sale_id);
        $this->tec->view_rights($inv->created_by);

        $this->load->library('correo_comprobante');
        if (!$this->correo_comprobante->encolar($sale_id, $to)) {
            echo json_encode(['msg' => lang('email_failed'), 'queued' => false]);
            return;
        }
        echo json_encode(['msg' => lang('email_success'), 'queued' => true]);
    }

    function email_proforma($sale_id = NULL, $to = NULL) {
        $attach = array();
        $this->load->model('hacienda_model');

        if ($this->input->post('id')) {
            $sale_id = $this->input->post('id');
        }
        if ($this->input->post('email')) {
            $to = $this->input->post('email');
        }
        if (!$sale_id || !$to) {
            die();
        }


        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv = $this->pos_model->getQuoteByID($sale_id);
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows'] = $this->pos_model->getAllQuoteItems($sale_id);
        $this->data['customer'] = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['inv'] = $inv;
        $this->data['sid'] = $sale_id;
        $this->data['noprint'] = NULL;
        $this->data['page_title'] = "Proforma";
        $this->data['modal'] = false;
        $this->data['payments'] = null;
        $this->data['created_by'] = $this->site->getUser($inv->created_by);
        $this->data['hacienda'] = $this->hacienda_model->getInvoice($sale_id);
        // El pie usa el QR; el codigo de barras PNG necesita la extension gd.
        $this->data['invoicebarcode'] = null;
        $this->data['store'] = $this->site->getStoreByID($inv->store_id);
        $this->data['invoiceqr'] = isset($this->data['hacienda']->consecutivo)
            ? $this->tec->qrcode($this->data['hacienda']->consecutivo, 4)
            : '';

        $receipt  = $this->load->view($this->theme . 'pos/view_proforma', $this->data, TRUE);
        $receipt  = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
        $subject  = 'Proforma - ' . $this->Settings->site_name;

        $message = $this->_cuerpo_correo([
            'tipo'     => lang('correo_tipo_proforma'),
            'intro'    => lang('correo_intro_proforma'),
            'cliente'  => $this->data['customer']->name ?? '',
            'fecha'    => $this->tec->hrld($inv->date ?? ''),
            'total'    => $this->tec->formatMoney($inv->grand_total ?? 0),
            'adjuntos' => [lang('correo_adjunto_pdf')],
            'nota'     => lang('correo_nota_proforma'),
        ]);
        $pdfPath  = sys_get_temp_dir() . '/Proforma_' . $sale_id . '.pdf';

        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($receipt);
        $mpdf->Output($pdfPath, 'F');

        $attach = ['ruta' => $pdfPath];

        $this->load->model('queue_model');
        $this->queue_model->push(Queue_model::TYPE_EMAIL, [
            'to'       => $to,
            'subject'  => $subject,
            'message'  => $message,
            'attach'   => $attach,
            'pdf_html' => $receipt,
            'pdf_path' => $pdfPath,
        ]);
        dispatch_queue_worker(Queue_model::TYPE_EMAIL);
        echo json_encode(['msg' => lang('email_success'), 'queued' => true]);
    }

}
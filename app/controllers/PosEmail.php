<?php
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
            $this->data['invoicebarcode'] = $this->invice_barcode($this->data['hacienda']->consecutivo, 'code128', 60);

            $receipt  = $this->load->view($this->theme . 'creditnotes/viewnc', $this->data, TRUE);
            $message  = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
            $subject  = lang('email_subject') . ' - ' . $this->Settings->site_name;

            $xml_sign     = $this->hacienda_model->xmlFirmadoCN($credit_id)->xml_sign;
            $xml_hacienda = $this->hacienda_model->xmlMensajeCN($credit_id)->xml_hacienda;
            $clave        = $this->hacienda_model->getClaveCN($credit_id)->clave;

            $this->data['tipo_documento'] = "Nota de Credito Electronica";
            $html    = $this->load->view($this->theme . 'creditnotes/invoice', $this->data, true);
            $pdfPath = sys_get_temp_dir() . '/T4_' . $clave . '.pdf';

            $mpdf = new \Mpdf\Mpdf();
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
        // if($this->hacienda_model->getInvoice($sale_id)->estatus_hacienda =="aceptado"){
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv = $this->pos_model->getSaleByID($sale_id);
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows'] = $this->pos_model->getAllSaleItems($sale_id);
        $this->data['customer'] = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['inv'] = $inv;
        $this->data['sid'] = $sale_id;
        $this->data['noprint'] = NULL;
        $this->data['page_title'] = lang('invoice');
        $this->data['modal'] = false;
        $this->data['payments'] = $this->pos_model->getAllSalePayments($sale_id);
        $this->data['created_by'] = $this->site->getUser($inv->created_by);
        $this->data['hacienda'] = $this->hacienda_model->getInvoice($sale_id);
        $this->data['invoicebarcode'] = $this->invice_barcode($this->data['hacienda']->consecutivo, 'code128', 60);


        $receipt  = $this->load->view($this->theme . 'pos/view', $this->data, TRUE);
        $message  = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
        $subject  = lang('email_subject') . ' - ' . $this->Settings->site_name;

        $haciendaRow  = $this->hacienda_model->xmlFirmado($sale_id);
        $mensajeRow   = $this->hacienda_model->xmlMensaje($sale_id);
        $claveRow     = $this->hacienda_model->getClave($sale_id);
        $xml_sign     = $haciendaRow ? $haciendaRow->xml_sign    : '';
        $xml_hacienda = $mensajeRow  ? $mensajeRow->xml_hacienda : '';
        $clave        = $claveRow    ? $claveRow->clave           : (string)$sale_id;

        $this->data['tipo_documento'] = "Tiquete Electronico";
        $html     = $this->load->view($this->theme . 'pos/invoice', $this->data, true);
        $pdfPath  = sys_get_temp_dir() . '/T4_' . $clave . '.pdf';

        $mpdf = new \Mpdf\Mpdf();
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
        $this->data['invoicebarcode'] = $this->invice_barcode($this->data['hacienda']->consecutivo, 'code128', 60);


        $receipt  = $this->load->view($this->theme . 'pos/view_proforma', $this->data, TRUE);
        $message  = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
        $subject  = 'Proforma - ' . $this->Settings->site_name;
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
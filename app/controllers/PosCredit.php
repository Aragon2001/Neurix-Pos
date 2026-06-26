<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PosCredit extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('hacienda_model');
    }

    function creditnote() {
        if ($this->input->post()) {
            $consecutivo = $this->input->post('nfactura');
            if (strlen($consecutivo) < 10) {
                $consecutivo = $this->Settings->casa_matriz . $this->Settings->terminal_pos . '04' . str_pad($consecutivo, 10, "0", STR_PAD_LEFT);
            }
            $nota_credito_tipo = $this->input->post('tipo_nc');
            $hacienda = $this->hacienda_model->getInvoicebyConsecutivo($consecutivo);

            if (isset($hacienda->sale_id)) {
                redirect('/pos/?code=' . base64_encode($hacienda->sale_id . ' ' . $nota_credito_tipo), 'refresh');
            } else {
                redirect('/pos', 'refresh');
            }
        }

        $this->load->view($this->theme . 'pos/creditnote', $this->data);
    }
}
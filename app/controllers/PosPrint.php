<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PosPrint extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('pos_model');
        $this->load->model('hacienda_model');
    }

    function view_bill() {
        $this->load->view($this->theme . 'pos/view_bill', $this->data);
    }

    function print_parquimetro($datos, $products, $did, $otrostextos) {
        $entrada = explode(" ", date('h:i:s a d/m/Y', strtotime($datos['date'])));
        if ($datos) {

            $info = array(
                (object) array('label' => lang('Entrada: '), 'value' => $entrada[0] . $entrada[1] . ' ' . $entrada[2]),
                (object) array('label' => lang('Placa del Vehiculo: '), 'value' => $datos['hold_ref'])
            );


            $data = (object) array(
                        'headingTiquete' => "Tiquete de estacionamiento",
                        'infoTiquete' => $info
            );
        }
        $store = $this->site->getStoreByID($this->session->userdata('store_id'));
        $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        if ($printer) {
            $this->load->library('escpos');
            $this->escpos->load($printer);
            $this->escpos->print_data($data, $store);
        }
    }

    function print_comanda($datos, $did) {
        if ($datos) {
            $items = $this->pos_model->getSuspendedSaleItems($did);
            $entrada = explode(" ", date('h:i:s a d/m/Y', strtotime($datos['date'])));
            $user = $this->pos_model->getUser($datos['created_by']);

            $info = array(
                (object) array('label' => lang('HORA Y FECHA: '), 'value' => $entrada[0] . $entrada[1] . ' ' . $entrada[2]),
                (object) array('label' => lang('MESONERO'), 'value' => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'),
                (object) array('label' => lang('IDENTIFICACION MESA: '), 'value' => $datos['hold_ref'])
            );

            $reg_totals = array();
            array_push($reg_totals, (object) array('label' => 'line', 'value' => ''));

            foreach ($items as $it) {
                $qty = $it->quantity - $it->qty_enviado;
                if ($qty > 0 || $qty < 0) {
                    if ($qty < 0) {
                        array_push($reg_totals, (object) array('label' => $it->product_name . "(No Ordenar)", 'value' => $this->tec->formatMoney($qty)));
                    } else {
                        array_push($reg_totals, (object) array('label' => $it->product_name, 'value' => $this->tec->formatMoney($qty)));
                    }
                    $this->pos_model->impresoComanda($it->id, $it->quantity);
                }
            }
            array_push($reg_totals, (object) array('label' => 'line', 'value' => ''));

            $data = (object) array(
                        'heading' => "Comanda a Cocina",
                        'info' => $info,
                        'totals' => $reg_totals
            );
        }
        // $this->tec->print_arrays($data);
        if (count($reg_totals) > 2) {
            $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
            if ($printer && $printer->type != "web") {
                $this->load->library('escpos');
                $this->escpos->load($printer);
                $this->escpos->print_data($data);
            }
        }
    }

    function close_register($user_id = NULL) {

        $this->data['printer'] = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        if (!$this->Admin) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->form_validation->set_rules('total_cash', lang("total_cash"), 'trim|required|numeric');
        $this->form_validation->set_rules('total_cheques', lang("total_cheques"), 'trim|required|numeric');
        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $user_register = $user_id ? $this->pos_model->registerData($user_id) : NULL;
                $rid = $user_register ? $user_register->id : $this->session->userdata('register_id');
                $register_open_time = $user_register ? $user_register->date : $this->session->userdata('register_open_time');
                $user_id = $user_register ? $user_register->user_id : $this->session->userdata('user_id');
                $cash_in_hand = $user_register ? $user_register->cash_in_hand : $this->session->userdata('cash_in_hand');
                $ccsales = $this->pos_model->getRegisterCCSales($register_open_time, $user_id);
                $Totaldepositos = $this->pos_model->getDepositos($register_open_time, $user_id);
                $cashsales = $this->pos_model->getRegisterCashSales($register_open_time, $user_id);
                $expenses = $this->pos_model->getRegisterExpenses($register_open_time, $user_id);
                $chsales = $this->pos_model->getRegisterChSales($register_open_time, $user_id);
                $notecredits = $this->pos_model->getRegisterNCSales($register_open_time, $user_id);
                $gravadas1 = $this->pos_model->getRegisterSalesGrav1($register_open_time, $user_id);
                $gravadas2 = $this->pos_model->getRegisterSalesGrav2($register_open_time, $user_id);
                $gravadas3 = $this->pos_model->getRegisterSalesGrav3($register_open_time, $user_id);
                $gravadas4 = $this->pos_model->getRegisterSalesGrav4($register_open_time, $user_id);
                $gravadas5 = $this->pos_model->getRegisterSalesGrav5($register_open_time, $user_id);
                $gravadas6 = $this->pos_model->getRegisterSalesGrav6($register_open_time, $user_id);
                $gravadas7 = $this->pos_model->getRegisterSalesGrav7($register_open_time, $user_id);
                $gravadas8 = $this->pos_model->getRegisterSalesGrav8($register_open_time, $user_id);
                $gravadas9 = $this->pos_model->getRegisterSalesGrav9($register_open_time, $user_id);
                $gravadas10 = $this->pos_model->getRegisterSalesGrav10($register_open_time, $user_id);
                $gravadas11 = $this->pos_model->getRegisterSalesGrav11($register_open_time, $user_id);
                $gravadas12 = $this->pos_model->getRegisterSalesGrav12($register_open_time, $user_id);
                $gravadas13 = $this->pos_model->getRegisterSalesGrav13($register_open_time, $user_id);
                $exentas = $this->pos_model->getRegisterSalesExce($register_open_time, $user_id);
                $creditos = $this->pos_model->getRegisterSalesCredit($register_open_time, $user_id);
                $ccsalesApart = $this->pos_model->getRegisterCCSalesApart($register_open_time, $user_id);
                $cashsalesApart = $this->pos_model->getRegisterCashSalesApart($register_open_time, $user_id);
                $cashsalesTips = $this->pos_model->getRegisterTips($register_open_time, $user_id);
                $total_cash = ($cashsales->total ? ($cashsales->total + $cash_in_hand) : $cash_in_hand);
                $total_cash = $total_cash + (isset($cashsalesApart->total) ? $cashsalesApart->total : 0);
                $total_cash = $total_cash + (isset($Totaldepositos->total) ? $Totaldepositos->total : 0);
                $total_cash -= ($expenses->total ? $expenses->total : 0);
                $Totalccsales = $ccsales->total ? $ccsales->total : 0;
                $Totalccsales = $Totalccsales + (isset($cashsalesApart->total) ? $cashsalesApart->total : 0);
            } else {
                $rid = $this->session->userdata('register_id');
                $user_id = $this->session->userdata('user_id');
                $register_open_time = $this->session->userdata('register_open_time');
                $cash_in_hand = $this->session->userdata('cash_in_hand');
                $ccsales = $this->pos_model->getRegisterCCSales($register_open_time);
                $Totaldepositos = $this->pos_model->getDepositos($register_open_time);
                $cashsales = $this->pos_model->getRegisterCashSales($register_open_time);
                $expenses = $this->pos_model->getRegisterExpenses($register_open_time);
                $chsales = $this->pos_model->getRegisterChSales($register_open_time);
                $notecredits = $this->pos_model->getRegisterNCSales($register_open_time);
                $gravadas1 = $this->pos_model->getRegisterSalesGrav1($register_open_time);
                $gravadas2 = $this->pos_model->getRegisterSalesGrav2($register_open_time);
                $gravadas3 = $this->pos_model->getRegisterSalesGrav3($register_open_time);
                $gravadas4 = $this->pos_model->getRegisterSalesGrav4($register_open_time);
                $gravadas5 = $this->pos_model->getRegisterSalesGrav5($register_open_time);
                $gravadas6 = $this->pos_model->getRegisterSalesGrav6($register_open_time);
                $gravadas7 = $this->pos_model->getRegisterSalesGrav7($register_open_time);
                $gravadas8 = $this->pos_model->getRegisterSalesGrav8($register_open_time);
                $gravadas9 = $this->pos_model->getRegisterSalesGrav9($register_open_time);
                $gravadas10 = $this->pos_model->getRegisterSalesGrav10($register_open_time);
                $gravadas11 = $this->pos_model->getRegisterSalesGrav11($register_open_time);
                $gravadas12 = $this->pos_model->getRegisterSalesGrav12($register_open_time);
                $gravadas13 = $this->pos_model->getRegisterSalesGrav13($register_open_time);
                $exentas = $this->pos_model->getRegisterSalesExce($register_open_time);
                $creditos = $this->pos_model->getRegisterSalesCredit($register_open_time);
                $ccsalesApart = $this->pos_model->getRegisterCCSalesApart($register_open_time);
                $cashsalesApart = $this->pos_model->getRegisterCashSalesApart($register_open_time);
                $cashsalesTips = $this->pos_model->getRegisterTips($register_open_time);
                $total_cash = ($cashsales->total ? ($cashsales->total + $cash_in_hand) : $cash_in_hand);
                $total_cash = $total_cash + (isset($cashsalesApart->total) ? $cashsalesApart->total : 0);
                $total_cash = $total_cash + (isset($Totaldepositos->total) ? $Totaldepositos->total : 0);
                $total_cash -= ($expenses->total ? $expenses->total : 0);
                $Totalccsales = $ccsales->total ? $ccsales->total : 0;
                $Totalccsales = $Totalccsales + $ccsalesApart->paid;
            }
            if (isset($notecredits->total)) {
                $ncredits = $notecredits->total;
            } else {
                $ncredits = 0;
            }
            $data = array(
                'date' => $register_open_time,
                'total_cash' => $total_cash - $ncredits,
                'total_cash_submitted' => $this->input->post('total_cash_submitted'),
                'total_cc' => $Totalccsales,
                'total_cc_submitted' => $this->input->post('total_cc_submitted'),
                'total_cc_slips_submitted' => $this->input->post('total_cc_slips_submitted'),
                'total_cheques' => $chsales->total_cheques,
                'total_cheques_submitted' => $this->input->post('total_cheques_submitted'),
                'note' => $this->input->post('note'),
                'cash_in_hand' => $cash_in_hand,
                'cash_sale' => $cashsales->total,
                'cc_sale' => $ccsales->total,
                'TotalDepositos' => $Totaldepositos->total,
                'total_sales' => ($ccsales->total + $cashsales->total)- $ncredits,
                'total_credits_sales' => $creditos->total,
                'grand_total_sales' => ($creditos->total + $ccsales->total + $cashsales->total)- $ncredits,
                'total_gravadas1' => $gravadas1->total,
                'total_gravadas2' => $gravadas2->total,
                'total_gravadas3' => $gravadas3->total,
                'total_gravadas4' => $gravadas4->total,
                'total_gravadas5' => $gravadas5->total,
                'total_gravadas6' => $gravadas6->total,
                'total_gravadas7' => $gravadas7->total,
                'total_gravadas8' => $gravadas8->total,
                'total_gravadas9' => $gravadas9->total,
                'total_gravadas10' => $gravadas10->total,
                'total_gravadas11' => $gravadas11->total,
                'total_gravadas12' => $gravadas12->total,
                'total_gravadas13' => $gravadas13->total,
                'total_impuesto1' => $gravadas1->total - $gravadas1->total / 1.01,
                'total_impuesto2' => $gravadas2->total - $gravadas2->total / 1.02,
                'total_impuesto3' => $gravadas3->total - $gravadas3->total / 1.03,
                'total_impuesto4' => $gravadas4->total - $gravadas4->total / 1.04,
                'total_impuesto5' => $gravadas5->total - $gravadas5->total / 1.05,
                'total_impuesto6' => $gravadas6->total - $gravadas6->total / 1.06,
                'total_impuesto7' => $gravadas7->total - $gravadas7->total / 1.07,
                'total_impuesto8' => $gravadas8->total - $gravadas8->total / 1.08,
                'total_impuesto9' => $gravadas9->total - $gravadas9->total / 1.09,
                'total_impuesto10' => $gravadas10->total - $gravadas10->total / 1.10,
                'total_impuesto11' => $gravadas11->total - $gravadas11->total / 1.11,
                'total_impuesto12' => $gravadas12->total - $gravadas12->total / 1.12,
                'total_impuesto13' => $gravadas13->total - $gravadas13->total / 1.13,
                'total_exentas' => $exentas->total,
                'tot_exentas_gravadas' => $_POST["tot_exentas_gravadas"],
                'total_notecredits' => @$notecredits->total ? @$notecredits->total : "0.00",
                'total_expenses' => $expenses->total ? $expenses->total : "0.00",
                'status' => 'close',
                'transfer_opened_bills' => $this->input->post('transfer_opened_bills'),
                'closed_at' => date('Y-m-d H:i:s'),
                'closed_by' => $this->session->userdata('user_id'),
                'cashsalesApart' => isset($cashsalesApart->total) ? $cashsalesApart->total : 0,
                'ccsalesApart' => isset($ccsalesApart->total) ? $ccsalesApart->total : 0,
                'ccsalesTips' => isset($cashsalesTips->total) ? $cashsalesTips->total : 0,
            );
        } elseif ($this->input->post('close_register')) {
            $this->session->set_flashdata('error', (validation_errors() ? validation_errors() : $this->session->flashdata('error')));
            redirect("pos");
        }

        if ($this->form_validation->run() == true && $this->pos_model->closeRegister($rid, $user_id, $data)) {
            $this->print_register(null, $data);
            $this->session->unset_userdata('register_id');
            $this->session->unset_userdata('cash_in_hand');
            $this->session->unset_userdata('register_open_time');
            $this->session->set_flashdata('message', lang("register_closed"));

            redirect("welcome");
        } else {
            if ($this->Admin) {
                $user_register = $user_id ? $this->pos_model->registerData($user_id) : NULL;
                $register_open_time = $user_register ? $user_register->date : $this->session->userdata('register_open_time');
                $this->data['cash_in_hand'] = $user_register ? $user_register->cash_in_hand : NULL;
                $this->data['register_open_time'] = $user_register ? $register_open_time : NULL;
            } else {
                $register_open_time = $this->session->userdata('register_open_time');
                $this->data['cash_in_hand'] = NULL;
                $this->data['register_open_time'] = NULL;
            }
            $credit = $this->pos_model->getRegisterNCSales($register_open_time);
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['ccsales'] = $this->pos_model->getRegisterCCSales($register_open_time, $user_id);
            $this->data['cashsales'] = $this->pos_model->getRegisterCashSales($register_open_time, $user_id);
            $this->data['chsales'] = $this->pos_model->getRegisterChSales($register_open_time, $user_id);
            $this->data['ccsalesApart'] = $this->pos_model->getRegisterCCSalesApart($register_open_time, $user_id);
            $this->data['ccsalesTips'] = $this->pos_model->getRegisterTips($register_open_time, $user_id);
            $this->data['cashsalesApart'] = $this->pos_model->getRegisterCashSalesApart($register_open_time, $user_id);
            $this->data['other_sales'] = $this->pos_model->getRegisterOtherSales($register_open_time, $user_id);
            $this->data['gcsales'] = $this->pos_model->getRegisterGCSales($register_open_time, $user_id);
            $this->data['stripesales'] = $this->pos_model->getRegisterStripeSales($register_open_time, $user_id);
            $this->data['totalsales'] = $this->data['cashsales']->total + $this->data['ccsales']->total - (isset($credit->total) ?$credit->total:0);
            $this->data['expenses'] = $this->pos_model->getRegisterExpenses($register_open_time);
            $this->data['users'] = $this->tec->getUsers($user_id);
            $this->data['suspended_bills'] = $this->pos_model->getSuspendedsales($user_id);
            $this->data['notecredits'] = $credit;
            $this->data['gravadas1'] = $this->pos_model->getRegisterSalesGrav1($register_open_time);
            $this->data['gravadas2'] = $this->pos_model->getRegisterSalesGrav2($register_open_time);
            $this->data['gravadas3'] = $this->pos_model->getRegisterSalesGrav3($register_open_time);
            $this->data['gravadas4'] = $this->pos_model->getRegisterSalesGrav4($register_open_time);
            $this->data['gravadas5'] = $this->pos_model->getRegisterSalesGrav5($register_open_time);
            $this->data['gravadas6'] = $this->pos_model->getRegisterSalesGrav6($register_open_time);
            $this->data['gravadas7'] = $this->pos_model->getRegisterSalesGrav7($register_open_time);
            $this->data['gravadas8'] = $this->pos_model->getRegisterSalesGrav8($register_open_time);
            $this->data['gravadas9'] = $this->pos_model->getRegisterSalesGrav9($register_open_time);
            $this->data['gravadas10'] = $this->pos_model->getRegisterSalesGrav10($register_open_time);
            $this->data['gravadas11'] = $this->pos_model->getRegisterSalesGrav11($register_open_time);
            $this->data['gravadas12'] = $this->pos_model->getRegisterSalesGrav12($register_open_time);
            $this->data['gravadas13'] = $this->pos_model->getRegisterSalesGrav13($register_open_time);
            $this->data['exentas'] = $this->pos_model->getRegisterSalesExce($register_open_time);
            $this->data['creditos'] = $this->pos_model->getRegisterSalesCredit($register_open_time);
            $this->data['user_id'] = $user_id;
            $this->data['Totaldepositos'] = $this->pos_model->getDepositos($register_open_time, $user_id);
            $this->load->view($this->theme . 'pos/close_register', $this->data);
        }
    }

    function print_register($re = NULL, $datos = null) {  

        if ($datos) {

            $user = $this->pos_model->getUser($datos['closed_by']);


            $info = array(
                (object) array('label' => lang('opened_at'), 'value' => $this->tec->hrld($datos['date'])),
                (object) array('label' => lang('cash_in_hand'), 'value' => $datos['cash_in_hand']),
                (object) array('label' => lang('user'), 'value' => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'),
                (object) array('label' => 'Cierre al dia', 'value' => $this->tec->hrld(date($datos['closed_at'])))
            );

            $diferenciaEfectivo = $datos['total_cash'] - $datos['total_cash_submitted'];

            if ($this->Settings->enable_detail_caschier == "0") {
                $reg_totals = array(
                    (object) array('label' => 'line', 'value' => ''),
                    (object) array('label' => lang('cash_in_hand'), 'value' => $this->tec->formatMoney($datos['cash_in_hand'] ? $datos['cash_in_hand'] : '0.00')),
                    (object) array('label' => 'line', 'value' => ''),
                    (object) array('label' => lang('total_cash_submitted'), 'value' => $this->tec->formatMoney($datos['total_cash_submitted'] ? $datos['total_cash_submitted'] : '0.00'))
                );

                if (($datos['total_cash_submitted'] - $datos['total_cash']) < 0) {
                    array_push($reg_totals, (object) array('label' => 'Diferencia en efectivo', 'value' => $this->tec->formatMoney($datos['total_cash_submitted'] ? ($datos['total_cash_submitted'] - $datos['total_cash']) : '0.00'))
                    );
                }
                array_push($reg_totals, (object) array('label' => lang('total_cc_slips'), 'value' => $this->tec->formatMoney($datos['total_cc_submitted'] ? $datos['total_cc_submitted'] : '0.00'))
                );
                if ($datos['total_cc_submitted'] - $datos['cc_sale'] < 0) {
                    array_push($reg_totals, (object) array('label' => 'Diferencia en tarjeta', 'value' => $this->tec->formatMoney($datos['total_cash_submitted'] ? ($datos['total_cc_submitted'] - $datos['cc_sale']) : '0.00'))
                    );
                }
            } elseif ($this->Settings->enable_detail_caschier == "1") {

                if ($datos['total_gravadas1']) {
                    $gravadas1 = (object) array('label' => 'Ventas Gravadas con 1%', 'value' => $this->tec->formatMoney($datos['total_gravadas1'] ? $datos['total_gravadas1'] : '0.00'));
                    $totalimpuesto1 = (object) array('label' => 'Total impuesto del 1%', 'value' => $this->tec->formatMoney($datos['total_impuesto1'] ? $datos['total_impuesto1'] : '0.00'));
                }
                if ($datos['total_gravadas2']) {
                    $gravadas2 = (object) array('label' => 'Ventas Gravadas con 2%', 'value' => $this->tec->formatMoney($datos['total_gravadas2'] ? $datos['total_gravadas2'] : '0.00'));
                    $totalimpuesto2 = (object) array('label' => 'Total impuesto del 2%', 'value' => $this->tec->formatMoney($datos['total_impuesto2'] ? $datos['total_impuesto2'] : '0.00'));
                }
                if ($datos['total_gravadas3']) {
                    $gravadas3 = (object) array('label' => 'Ventas Gravadas con 3%', 'value' => $this->tec->formatMoney($datos['total_gravadas3'] ? $datos['total_gravadas3'] : '0.00'));
                    $totalimpuesto3 = (object) array('label' => 'Total impuesto del 3%', 'value' => $this->tec->formatMoney($datos['total_impuesto3'] ? $datos['total_impuesto3'] : '0.00'));
                }
                if ($datos['total_gravadas4']) {
                    $gravadas4 = (object) array('label' => 'Ventas Gravadas con 4%', 'value' => $this->tec->formatMoney($datos['total_gravadas4'] ? $datos['total_gravadas4'] : '0.00'));
                    $totalimpuesto4 = (object) array('label' => 'Total impuesto del 4%', 'value' => $this->tec->formatMoney($datos['total_impuesto4'] ? $datos['total_impuesto4'] : '0.00'));
                }
                if ($datos['total_gravadas5']) {
                    $gravadas5 = (object) array('label' => 'Ventas Gravadas con 5%', 'value' => $this->tec->formatMoney($datos['total_gravadas5'] ? $datos['total_gravadas5'] : '0.00'));
                    $totalimpuesto5 = (object) array('label' => 'Total impuesto del 5%', 'value' => $this->tec->formatMoney($datos['total_impuesto5'] ? $datos['total_impuesto5'] : '0.00'));
                }
                if ($datos['total_gravadas6']) {
                    $gravadas6 = (object) array('label' => 'Ventas Gravadas con 6%', 'value' => $this->tec->formatMoney($datos['total_gravadas6'] ? $datos['total_gravadas6'] : '0.00'));
                    $totalimpuesto6 = (object) array('label' => 'Total impuesto del 6%', 'value' => $this->tec->formatMoney($datos['total_impuesto6'] ? $datos['total_impuesto6'] : '0.00'));
                }
                if ($datos['total_gravadas7']) {
                    $gravadas7 = (object) array('label' => 'Ventas Gravadas con 7%', 'value' => $this->tec->formatMoney($datos['total_gravadas7'] ? $datos['total_gravadas7'] : '0.00'));
                    $totalimpuesto7 = (object) array('label' => 'Total impuesto del 7%', 'value' => $this->tec->formatMoney($datos['total_impuesto7'] ? $datos['total_impuesto7'] : '0.00'));
                }
                if ($datos['total_gravadas8']) {
                    $gravadas8 = (object) array('label' => 'Ventas Gravadas con 8%', 'value' => $this->tec->formatMoney($datos['total_gravadas8'] ? $datos['total_gravadas8'] : '0.00'));
                    $totalimpuesto8 = (object) array('label' => 'Total impuesto del 8%', 'value' => $this->tec->formatMoney($datos['total_impuesto8'] ? $datos['total_impuesto8'] : '0.00'));
                }
                if ($datos['total_gravadas9']) {
                    $gravadas9 = (object) array('label' => 'Ventas Gravadas con 9%', 'value' => $this->tec->formatMoney($datos['total_gravadas9'] ? $datos['total_gravadas9'] : '0.00'));
                    $totalimpuesto9 = (object) array('label' => 'Total impuesto del 9%', 'value' => $this->tec->formatMoney($datos['total_impuesto9'] ? $datos['total_impuesto9'] : '0.00'));
                }
                if ($datos['total_gravadas10']) {
                    $gravadas10 = (object) array('label' => 'Ventas Gravadas con 10%', 'value' => $this->tec->formatMoney($datos['total_gravadas10'] ? $datos['total_gravadas10'] : '0.00'));
                    $totalimpuesto10 = (object) array('label' => 'Total impuesto del 10%', 'value' => $this->tec->formatMoney($datos['total_impuesto10'] ? $datos['total_impuesto10'] : '0.00'));
                }
                if ($datos['total_gravadas11']) {
                    $gravadas11 = (object) array('label' => 'Ventas Gravadas con 11%', 'value' => $this->tec->formatMoney($datos['total_gravadas11'] ? $datos['total_gravadas11'] : '0.00'));
                    $totalimpuesto11 = (object) array('label' => 'Total impuesto del 11%', 'value' => $this->tec->formatMoney($datos['total_impuesto11'] ? $datos['total_impuesto11'] : '0.00'));
                }
                if ($datos['total_gravadas12']) {
                    $gravadas12 = (object) array('label' => 'Ventas Gravadas con 12%', 'value' => $this->tec->formatMoney($datos['total_gravadas12'] ? $datos['total_gravadas12'] : '0.00'));
                    $totalimpuesto12 = (object) array('label' => 'Total impuesto del 12%', 'value' => $this->tec->formatMoney($datos['total_impuesto12'] ? $datos['total_impuesto12'] : '0.00'));
                }
                if ($datos['total_gravadas13']) {
                    $gravadas13 = (object) array('label' => 'Ventas Gravadas con 13%', 'value' => $this->tec->formatMoney($datos['total_gravadas13'] ? $datos['total_gravadas13'] : '0.00'));
                    $totalimpuesto13 = (object) array('label' => 'Total impuesto del 13%', 'value' => $this->tec->formatMoney($datos['total_impuesto13'] ? $datos['total_impuesto13'] : '0.00'));
                }
                $reg_totals = array(
                    (object) array('label' => 'line', 'value' => ''),
                    (object) array('label' => lang('cash_in_hand'), 'value' => $this->tec->formatMoney($datos['cash_in_hand'] ? $datos['cash_in_hand'] : '0.00')),
                    (object) array('label' => 'line', 'value' => ''),
                    (object) array('label' => lang('cash_sale'), 'value' => $this->tec->formatMoney($datos['cash_sale'] ? $datos['cash_sale'] : '0.00')),
                    (object) array('label' => lang('cc_sale'), 'value' => $this->tec->formatMoney($datos['cc_sale'] ? $datos['cc_sale'] : '0.00')),
                    (object) array('label' => lang('total_sales'), 'value' => $this->tec->formatMoney($datos['total_sales'] ? $datos['total_sales'] : '0.00')),
                    (object) array('label' => 'line', 'value' => ''),
                    (object) array('label' => lang('total_credits_sales'), 'value' => $this->tec->formatMoney($datos['total_credits_sales'] ? $datos['total_credits_sales'] : '0.00')),
                    (object) array('label' => lang('grand_total'), 'value' => $this->tec->formatMoney($datos['grand_total_sales'] ? $datos['grand_total_sales'] : '0.00')),
                    (object) array('label' => 'line', 'value' => ''),
                    isset($gravadas1) ? $gravadas1 : '',
                    isset($totalimpuesto1) ? $totalimpuesto1 : '',
                    isset($gravadas2) ? $gravadas2 : '',
                    isset($totalimpuesto2) ? $totalimpuesto2 : '',
                    isset($gravadas3) ? $gravadas3 : '',
                    isset($totalimpuesto3) ? $totalimpuesto3 : '',
                    isset($gravadas4) ? $gravadas4 : '',
                    isset($totalimpuesto4) ? $totalimpuesto4 : '',
                    isset($gravadas5) ? $gravadas5 : '',
                    isset($totalimpuesto5) ? $totalimpuesto5 : '',
                    isset($gravadas6) ? $gravadas6 : '',
                    isset($totalimpuesto6) ? $totalimpuesto6 : '',
                    isset($gravadas7) ? $gravadas7 : '',
                    isset($totalimpuesto7) ? $totalimpuesto7 : '',
                    isset($gravadas8) ? $gravadas8 : '',
                    isset($totalimpuesto8) ? $totalimpuesto8 : '',
                    isset($gravadas9) ? $gravadas9 : '',
                    isset($totalimpuesto9) ? $totalimpuesto9 : '',
                    isset($gravadas10) ? $gravadas10 : '',
                    isset($totalimpuesto10) ? $totalimpuesto10 : '',
                    isset($gravadas11) ? $gravadas11 : '',
                    isset($totalimpuesto11) ? $totalimpuesto11 : '',
                    isset($gravadas12) ? $gravadas12 : '',
                    isset($totalimpuesto12) ? $totalimpuesto12 : '',
                    isset($gravadas13) ? $gravadas13 : '',
                    isset($totalimpuesto13) ? $totalimpuesto13 : '',
                    (object) array('label' => 'Ventas Excentas', 'value' => $this->tec->formatMoney($datos['total_exentas'] ? $datos['total_exentas'] : '0.00')),
                    (object) array('label' => 'Excentas + Gravadas', 'value' => $this->tec->formatMoney($datos['tot_exentas_gravadas'] ? $datos['tot_exentas_gravadas'] : '0.00')),
                    (object) array('label' => 'line', 'value' => ''),
                    (object) array('label' => lang('credit_notes'), 'value' => $this->tec->formatMoney($datos['total_notecredits'] ? $datos['total_notecredits'] : '0.00')),
                    (object) array('label' => lang('Gastos / Retiros'), 'value' => $this->tec->formatMoney($datos['total_expenses'] ? $datos['total_expenses'] : '0.00')),
                    (object) array('label' => lang('Depositos'), 'value' => $this->tec->formatMoney($datos['TotalDepositos'] ? $datos['TotalDepositos'] : '0.00')),
                    (object) array('label' => "Efectivo de Apartados", 'value' => $this->tec->formatMoney($datos['cashsalesApart'] ? $datos['cashsalesApart'] : '0.00')),
                    (object) array('label' => "Tarjetas de Apartados", 'value' => $this->tec->formatMoney($datos['ccsalesApart'] ? $datos['ccsalesApart'] : '0.00')),
                    (object) array('label' => 'line', 'value' => ''),
                    $this->Settings->propina_enable == '1' ? (object) array('label' => lang('Total servicio ' . $this->Settings->propina_rate) . '%', 'value' => $this->tec->formatMoney($datos['ccsalesTips'] ? $datos['ccsalesTips'] : '0.00')) : '',
                    (object) array('label' => lang('total_cash'), 'value' => $this->tec->formatMoney($datos['total_cash'] ? $datos['total_cash'] : '0.00')),
                    (object) array('label' => lang('Total en tarjetas'), 'value' => $this->tec->formatMoney((int) ($datos['cc_sale'] ? $datos['cc_sale'] : 0) + (int) ($datos['ccsalesApart'] ? $datos['ccsalesApart'] : 0))),
                    (object) array('label' => 'line', 'value' => ''),
                    (object) array('label' => lang('total_cash_submitted'), 'value' => $this->tec->formatMoney($datos['total_cash_submitted'] ? $datos['total_cash_submitted'] : '0.00')),
                    (object) array('label' => 'Diferencia en efectivo', 'value' => $this->tec->formatMoney($datos['total_cash_submitted'] ? ($datos['total_cash_submitted'] - $datos['total_cash']) : '0.00')),
                    (object) array('label' => lang('total_cc_slips'), 'value' => $this->tec->formatMoney($datos['total_cc_submitted'] ? $datos['total_cc_submitted'] : '0.00')),
                    (object) array('label' => 'Diferencia en tarjeta', 'value' => $this->tec->formatMoney($datos['total_cc_submitted'] ? ($datos['total_cc_submitted'] - ($datos['cc_sale'] + $datos['ccsalesApart'])) : '0.00'))
                );
            }
            $data = (object) array(
                        'heading' => lang('register_details'),
                        'info' => $info,
                        'totals' => $reg_totals
            );
        }
        // $this->tec->print_arrays($data);
        if ($re == 1) {
            return $data;
        } elseif ($re == 2) {
            
        } else {
            $store = $this->site->getStoreByID($this->session->userdata('store_id'));
            $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
            if ($printer && $printer->type != "web") {
                $this->load->library('escpos');
                $this->escpos->load($printer);
                $this->escpos->print_data($data, $store);
            }
        }
    }

    function print_receipt($id, $open_drawer = false, $type_document = 1, $haciendaInvo = null) {
        $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));

        if ($printer && $printer->type == "web") {
            if ($type_document == 3) {
                $redirect_to = 'pos/viewnc/' . $id;
                redirect($redirect_to);
            } else if ($type_document == 1) {
                $redirect_to = 'pos/view/' . $id;
                redirect($redirect_to);
            }
        } else {
            if ($type_document == 3) {
                $sale = $this->pos_model->getCreditNoteByID($id);
                $sale->hacienda = $this->hacienda_model->getCN($id);
                // $sale->hacienda->tipo_doc = "3";
                $sale->type_doc = lang("elect_credit_note");
                $sale->footerhacienda = $this->Settings->footer_hacienda_nc;
                $items = $this->pos_model->getAllCreditNotesItems($id);
            } else if ($type_document == 1) {
                $sale = $this->pos_model->getSaleByID($id);
                $sale->hacienda = $this->hacienda_model->getInvoice($id);
                $sale->type_doc = lang('electronic_bill');
                $sale->footerhacienda = $this->Settings->footer_hacienda_fe;
                $items = $this->pos_model->getAllSaleItems($id);
            } else if ($type_document == 20) {
                $sale = $this->pos_model->getApartadoSalesID($id);
                $sale->hacienda = null;
                $sale->type_doc = "Recibo de apartado";
                $sale->footerhacienda = $this->Settings->footer_apartado;
                $items = $this->pos_model->getApartadoSaleItems($id);
            } else if ($type_document == 21) {
                $sale = $this->pos_model->getQuotesSalesID($id);
                $sale->hacienda = null;
                $sale->type_doc = "Proforma";
                $sale->footerhacienda = $this->Settings->footer_apartado;
                $items = $this->pos_model->getQuotesSaleItems($id);
            } else if ($type_document == 22) {
                $sale = $this->pos_model->getSuspendedSaleByID($id);
                $sale->hacienda = null;
                $sale->type_doc = "Recibo de estacionamiento";
                $items = $this->pos_model->getSuspendedSaleItems($id);
            } else if ($type_document == 23) {
                $sale = $this->pos_model->getSuspendedSaleByID($id);
                $sale->hacienda = null;
                $sale->type_doc = "Comanda Cocina";
                $items = $this->pos_model->getSuspendedSaleItems($id);
            }
            if ($type_document != 20 and $type_document != 21 and $type_document != 22) {
                $sale->invice_barcode = $this->invice_barcode_2($sale->hacienda->consecutivo, 'code128', 60);
                $payments = $this->pos_model->getAllSalePayments($id);
            } else {
                $sale->invice_barcode = "";
                $payments = $this->pos_model->getAllApartadoPayments($id);
            }

            $sale->customer = $this->pos_model->getCustomerByID($sale->customer_id);

            $store = $this->site->getStoreByID($sale->store_id);
            $created_by = $this->site->getUser($sale->created_by);
            $sale->haciendaInvo = $haciendaInvo;
            $this->load->library('escpos');
            $this->escpos->load($printer);
            $this->escpos->print_receipt($store, $sale, $items, $payments, $created_by, $open_drawer);
        }
    }

    function print_cuenta($id, $open_drawer = false, $type_document = 1, $haciendaInvo = null) {
        $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        $sale = $this->pos_model->getSuspendedSaleByID($id);
        $sale->hacienda = null;
        $sale->type_doc = "Comanda Cocina";
        $items = $this->pos_model->getSuspendedSaleItems($id);
        $sale->customer = $this->pos_model->getCustomerByID($sale->customer_id);
        $store = $this->site->getStoreByID($sale->store_id);
        $created_by = $this->site->getUser($sale->created_by);
        $payments = null;
        $sale->haciendaInvo = $haciendaInvo;
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->print_receipt_suspended($store, $sale, $items, $payments, $created_by, $open_drawer);
    }

    function receipt_img() {

        $data = $this->input->post('img', TRUE);
        $filename = date('Y-m-d-H-i-s-') . uniqid() . '.png';
        $cd = !empty($this->input->post('cd')) ? true : false;
        $imgData = str_replace(' ', '+', $data);
        $imgData = base64_decode($imgData);
        file_put_contents('files/receipts/' . $filename, $imgData);
        $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->print_img($filename, $cd);
    }

    function open_drawer() {
        $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        if (!$printer) return;
        $printer->ip = $this->Settings->ip_printer;
        $printer->nombrecompartido = $this->Settings->nombrecompartido;
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->open_drawer();
    }

    function p($bo = 'order') {

        $date = date('Y-m-d H:i:s');
        $customer_id = $this->input->post('customer_id');
        $customer_details = $this->pos_model->getCustomerByID($customer_id);
        $customer = $customer_details->name;
        $note = $this->tec->clear_tags($this->input->post('spos_note'));

        $total = 0;
        $product_tax = 0;
        $order_tax = 0;
        $product_discount = 0;
        $order_discount = 0;
        $percentage = '%';
        $i = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
        for ($r = 0; $r < $i; $r++) {
            $item_id = $_POST['product_id'][$r];
            $real_unit_price = $this->tec->formatDecimal($_POST['real_unit_price'][$r]);
            $item_quantity = $_POST['quantity'][$r];
            $item_comment = $_POST['item_comment'][$r];
            $item_ordered = $_POST['item_was_ordered'][$r];
            $item_discount = isset($_POST['product_discount'][$r]) ? $_POST['product_discount'][$r] : '0';

            if (isset($item_id) && isset($real_unit_price) && isset($item_quantity)) {
                $product_details = $this->site->getProductByID($item_id);
                if ($product_details) {
                    $product_name = $product_details->name;
                    $product_code = $product_details->code;
                    $product_cost = $product_details->cost;
                } else {
                    $product_name = $_POST['product_name'][$r];
                    $product_code = $_POST['product_code'][$r];
                    $product_cost = 0;
                }
                if (!$this->Settings->overselling) {
                    if ($product_details->type == 'standard') {
                        if ($product_details->quantity < $item_quantity) {
                            $this->session->set_flashdata('error', lang("quantity_low") . ' (' .
                                    lang('name') . ': ' . $product_details->name . ' | ' .
                                    lang('ordered') . ': ' . $item_quantity . ' | ' .
                                    lang('available') . ': ' . $product_details->quantity .
                                    ')');
                            redirect("pos");
                        }
                    } elseif ($product_details->type == 'combo') {
                        $combo_items = $this->pos_model->getComboItemsByPID($product->id);
                        foreach ($combo_items as $combo_item) {
                            $cpr = $this->site->getProductByID($combo_item->id);
                            if ($cpr->quantity < $item_quantity) {
                                $this->session->set_flashdata('error', lang("quantity_low") . ' (' .
                                        lang('name') . ': ' . $cpr->name . ' | ' .
                                        lang('ordered') . ': ' . $item_quantity . ' x ' . $combo_item->qty . ' = ' . $item_quantity * $combo_item->qty . ' | ' .
                                        lang('available') . ': ' . $cpr->quantity .
                                        ') ' . $product_details->name);
                                redirect("pos");
                            }
                        }
                    }
                }
                $unit_price = $real_unit_price;

                $pr_discount = 0;
                if (isset($item_discount)) {
                    $discount = $item_discount;
                    $dpos = strpos($discount, $percentage);
                    if ($dpos !== false) {
                        $pds = explode("%", $discount);
                        $pr_discount = $this->tec->formatDecimal((($unit_price * (Float) ($pds[0])) / 100), 4);
                    } else {
                        $pr_discount = $this->tec->formatDecimal($discount);
                    }
                }
                $unit_price = $this->tec->formatDecimal(($unit_price - $pr_discount), 4);
                $item_net_price = $unit_price;
                $pr_item_discount = $this->tec->formatDecimal(($pr_discount * $item_quantity), 4);
                $product_discount += $pr_item_discount;

                $pr_item_tax = 0;
                $item_tax = 0;
                $tax = "";
                if (isset($product_details->tax) && $product_details->tax != 0) {

                    if ($product_details && $product_details->tax_method == 1) {
                        $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / 100), 4);
                        $tax = $product_details->tax . "%";
                    } else {
                        $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / (100 + $product_details->tax)), 4);
                        $tax = $product_details->tax . "%";
                        $item_net_price -= $item_tax;
                    }

                    $pr_item_tax = $this->tec->formatDecimal(($item_tax * $item_quantity), 4);
                }

                $product_tax += $pr_item_tax;
                $subtotal = (($item_net_price * $item_quantity) + $pr_item_tax);

                $products[] = (object) array(
                            'product_id' => $item_id,
                            'quantity' => $item_quantity,
                            'unit_price' => $unit_price,
                            'net_unit_price' => $item_net_price,
                            'discount' => $item_discount,
                            'comment' => $item_comment,
                            'item_discount' => $pr_item_discount,
                            'tax' => $tax,
                            'item_tax' => $pr_item_tax,
                            'subtotal' => $subtotal,
                            'real_unit_price' => $real_unit_price,
                            'cost' => $product_cost,
                            'product_code' => $product_code,
                            'product_name' => $product_name,
                            'ordered' => $item_ordered,
                );

                $total += $item_net_price * $item_quantity;
            }
        }
        if (empty($products)) {
            $this->form_validation->set_rules('product', lang("order_items"), 'required');
        } else {
            krsort($products);
        }

        if ($this->input->post('order_discount')) {
            $order_discount_id = $this->input->post('order_discount');
            $opos = strpos($order_discount_id, $percentage);
            if ($opos !== false) {
                $ods = explode("%", $order_discount_id);
                $order_discount = $this->tec->formatDecimal(((($total + $product_tax) * (Float) ($ods[0])) / 100), 4);
            } else {
                $order_discount = $this->tec->formatDecimal($order_discount_id);
            }
        } else {
            $order_discount_id = NULL;
        }
        $total_discount = $this->tec->formatDecimal(($order_discount + $product_discount), 4);

        if ($this->input->post('order_tax')) {
            $order_tax_id = $this->input->post('order_tax');
            $opos = strpos($order_tax_id, $percentage);
            if ($opos !== false) {
                $ots = explode("%", $order_tax_id);
                $order_tax = $this->tec->formatDecimal(((($total + $product_tax - $order_discount) * (Float) ($ots[0])) / 100), 4);
            } else {
                $order_tax = $this->tec->formatDecimal($order_tax_id);
            }
        } else {
            $order_tax_id = NULL;
            $order_tax = 0;
        }

        $total_tax = $this->tec->formatDecimal(($product_tax + $order_tax), 4);
        $grand_total = $this->tec->formatDecimal(($this->tec->formatDecimal($total) + $total_tax - $order_discount), 4);
        $paid = 0;
        $round_total = $this->tec->roundNumber($grand_total, $this->Settings->rounding);
        $rounding = $this->tec->formatDecimal(($round_total - $grand_total));

        $data = (object) array('date' => $date,
                    'customer_id' => $customer_id,
                    'customer_name' => $customer,
                    'total' => $this->tec->formatDecimal($total),
                    'product_discount' => $this->tec->formatDecimal($product_discount, 4),
                    'order_discount_id' => $order_discount_id,
                    'order_discount' => $order_discount,
                    'total_discount' => $total_discount,
                    'product_tax' => $this->tec->formatDecimal($product_tax, 4),
                    'order_tax_id' => $order_tax_id,
                    'order_tax' => $order_tax,
                    'total_tax' => $total_tax,
                    'grand_total' => $grand_total,
                    'total_items' => $this->input->post('total_items'),
                    'total_quantity' => $this->input->post('total_quantity'),
                    'rounding' => $rounding,
                    'paid' => $paid,
                    'created_by' => $this->session->userdata('user_id'),
                    'note' => $note,
                    'hold_ref' => $this->input->post('hold_ref'),
        );

        // $this->tec->print_arrays($data, $products);
        $store = $this->site->getStoreByID($this->session->userdata('store_id'));
        $created_by = $this->site->getUser($this->session->userdata('user_id'));

        if ($bo == 'bill') {
            $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
            $this->load->library('escpos');
            $this->escpos->load($printer);
            $this->escpos->print_receipt($store, $data, $products, false, $created_by, false, true);
        } else {
            $order_printers = json_decode($this->Settings->order_printers);
            $this->load->library('escpos');
            foreach ($order_printers as $printer_id) {
                $printer = $this->site->getPrinterByID($printer_id);
                $this->escpos->load($printer);
                $this->escpos->print_order($store, $data, $products, $created_by);
            }
        }
    }


    function invice_barcode($id_invoice = NULL, $bcs = 'code128', $height = 60) {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        return $this->tec->barcode($id_invoice, $bcs, $height);
    }

    function invice_barcode_2($id_invoice = NULL, $bcs = 'code128', $height = 60) {
        if ($this->input->get('code')) {
            $product_code = $this->input->get('code');
        }
        return $this->tec->barcode64($id_invoice, $bcs, $height);
    }
}
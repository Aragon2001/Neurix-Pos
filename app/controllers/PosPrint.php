<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
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
        $this->encolar_ticket_qz($data, $store);
    }

    function close_register($user_id = NULL) {

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
                    $this->Settings->propina_enable == '1' ? (object) array('label' => lang('total_servicio') . ' ' . $this->Settings->propina_rate . '%', 'value' => $this->tec->formatMoney($datos['ccsalesTips'] ? $datos['ccsalesTips'] : '0.00')) : '',
                    (object) array('label' => lang('total_cash'), 'value' => $this->tec->formatMoney($datos['total_cash'] ? $datos['total_cash'] : '0.00')),
                    (object) array('label' => lang('total_en_tarjetas'), 'value' => $this->tec->formatMoney((int) ($datos['cc_sale'] ? $datos['cc_sale'] : 0) + (int) ($datos['ccsalesApart'] ? $datos['ccsalesApart'] : 0))),
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
            $this->encolar_ticket_qz($data, $store);
        }
    }

    /**
     * Gathers sale/items/payments/store/created_by for a receipt, shared by
     * print_receipt() (legacy direct-connector printing) and receipt_bytes()
     * (QZ Tray flow: bytes generated here, printed by the browser).
     */
    private function _load_receipt_context($id, $type_document, $haciendaInvo) {
        if ($type_document == 3) {
            $sale = $this->pos_model->getCreditNoteByID($id);
            $sale->hacienda = $this->hacienda_model->getCN($id);
            $sale->type_doc = lang("elect_credit_note");
            $sale->footerhacienda = $this->Settings->footer_hacienda_nc;
            $items = $this->pos_model->getAllCreditNotesItems($id);
            // La NC debe referenciar la factura original en el ticket físico
            // (obligatorio para Hacienda); derivarla aquí si no vino dada.
            if (!$haciendaInvo) {
                $haciendaInvo = $this->hacienda_model->getInvoice($sale->sale_id);
            }
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
        }
        if ($type_document != 20 and $type_document != 21 and $type_document != 22) {
            $sale->invice_barcode = $this->invice_barcode_2($sale->hacienda->consecutivo, 'code128', 60);
            $payments = $this->pos_model->getAllSalePayments($id);
        } else {
            $sale->invice_barcode = "";
            $payments = $this->pos_model->getAllApartadoPayments($id);
        }

        $sale->customer = $this->pos_model->getCustomerByID($sale->customer_id);
        $sale->haciendaInvo = $haciendaInvo;

        return array(
            'store' => $this->site->getStoreByID($sale->store_id),
            'sale' => $sale,
            'items' => $items,
            'payments' => $payments,
            'created_by' => $this->site->getUser($sale->created_by),
        );
    }

    function print_receipt($id, $open_drawer = false, $type_document = 1, $haciendaInvo = null) {
        $ctx = $this->_load_receipt_context($id, $type_document, $haciendaInvo);
        $this->load->library('escpos');
        $this->escpos->loadBuffer();
        $this->escpos->print_receipt($ctx['store'], $ctx['sale'], $ctx['items'], $ctx['payments'], $ctx['created_by'], $open_drawer);
        $this->encolar_bytes_qz($this->escpos->getBufferedData());
    }

    /**
     * Renders a receipt to raw ESC/POS bytes (base64) instead of sending it
     * to a physical connector, so the browser can print it via QZ Tray on
     * whichever printer this terminal has configured locally.
     */
    function receipt_bytes($id, $type_document = 1, $haciendaInvo = null) {
        if (!$this->session->userdata('user_id')) {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['status' => 0]));
        }
        $ctx = $this->_load_receipt_context($id, $type_document, $haciendaInvo);
        $this->load->library('escpos');
        $this->escpos->loadBuffer();
        // El ancho del papel es del puesto que imprime: lo manda el navegador.
        $this->escpos->setCaracteres($this->input->get('cpl'));
        $this->escpos->print_receipt($ctx['store'], $ctx['sale'], $ctx['items'], $ctx['payments'], $ctx['created_by'], false);
        echo json_encode(['status' => 1, 'bytes' => $this->escpos->getBufferedData()]);
    }

    /**
     * GET posprint/receipt_preview/<id>?cpl=42
     * Lineas del tiquete tal como se imprimirian, para la vista previa. Sin id se
     * usa la ultima venta de la tienda.
     */
    function receipt_preview($id = null) {
        if (!$this->session->userdata('user_id')) {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['status' => 0]));
        }
        if (!$id) {
            $ultima = $this->db->select('id')->order_by('id', 'DESC')
                ->get_where('sales', ['store_id' => $this->session->userdata('store_id')], 1)->row();
            $id = $ultima ? $ultima->id : null;
        }
        if (!$id || !$this->pos_model->getSaleByID($id)) {
            return $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 0, 'lineas' => []]));
        }

        $ctx = $this->_load_receipt_context($id, 1, null);
        $this->load->library('escpos');
        $this->escpos->loadPreview();
        $this->escpos->setCaracteres($this->input->get('cpl'));
        $this->escpos->print_receipt($ctx['store'], $ctx['sale'], $ctx['items'], $ctx['payments'], $ctx['created_by'], false);

        $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode([
            'status'     => 1,
            'venta'      => (int) $id,
            'caracteres' => $this->escpos->char_per_line,
            'lineas'     => $this->escpos->getPreview(),
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Devuelve la cuenta de una mesa como bytes ESC/POS (base64) para que el
     * navegador la imprima por QZ Tray.
     */
    function cuenta_bytes($id) {
        if (!$this->session->userdata('user_id')) {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['status' => 0]));
        }
        $sale = $this->pos_model->getSuspendedSaleByID($id);
        $sale->hacienda = null;
        $sale->type_doc = "Cuenta";
        $items = $this->pos_model->getSuspendedSaleItems($id);
        $sale->customer = $this->pos_model->getCustomerByID($sale->customer_id);
        $store = $this->site->getStoreByID($sale->store_id);
        $created_by = $this->site->getUser($sale->created_by);
        $sale->haciendaInvo = null;
        $this->load->library('escpos');
        $this->escpos->loadBuffer();
        $this->escpos->print_receipt_suspended($store, $sale, $items, null, $created_by, false);
        echo json_encode(['status' => 1, 'bytes' => $this->escpos->getBufferedData()]);
    }

    /**
     * Cash-drawer pulse for the routine "open drawer to give change" button
     * shown right after a sale — no PIN required, same as the legacy
     * open_drawer() behavior. The PIN-gated quick command lives in
     * verify_drawer_pin(), used by the standalone POS toolbar button.
     */
    /**
     * Entrega los tickets que quedaron pendientes de imprimir y vacia la cola.
     * Se vacia aunque la impresion falle, para que un ticket viejo no vuelva a
     * salir en cada pagina que abra el cajero.
     */
    function cola_bytes() {
        if (!$this->session->userdata('user_id')) {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['status' => 0]));
        }
        $cola = $this->session->userdata('qz_cola');
        $this->session->unset_userdata('qz_cola');
        echo json_encode(['status' => 1, 'bytes' => is_array($cola) ? array_values($cola) : []]);
    }

    function drawer_bytes() {
        if (!$this->session->userdata('user_id')) {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['status' => 0]));
        }
        $this->load->library('escpos');
        $this->escpos->loadBuffer();
        $this->escpos->open_drawer();
        echo json_encode(['status' => 1, 'bytes' => $this->escpos->getBufferedData()]);
    }

    /**
     * Validates a cash-drawer PIN against every admin user with one
     * configured, and returns the ESC/POS pulse bytes for QZ Tray to print
     * on success. Every attempt (success or failure) is audited via
     * AuditLog_model so any admin's PIN usage is traceable.
     */
    function verify_drawer_pin() {
        if (!$this->session->userdata('user_id')) {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['status' => 0]));
        }
        $this->load->model('AuditLog_model', 'audit_log');
        $pin = trim((string) $this->input->post('pin'));
        $matched = null;
        if ($pin !== '') {
            foreach ($this->site->getAdminUsersWithDrawerPin() as $admin) {
                if (password_verify($pin, $admin->drawer_pin)) {
                    $matched = $admin;
                    break;
                }
            }
        }

        if ($matched) {
            $this->audit_log->log('drawer_open_success', 'drawer', (int) $this->session->userdata('user_id'), 'PIN de ' . $matched->username);
            $this->load->library('escpos');
            $this->escpos->loadBuffer();
            $this->escpos->open_drawer();
            echo json_encode(['status' => 1, 'bytes' => $this->escpos->getBufferedData()]);
        } else {
            $this->audit_log->log('drawer_open_failed', 'drawer', (int) $this->session->userdata('user_id'), 'PIN invalido');
            echo json_encode(['status' => 0, 'msg' => lang('wrong_pin')]);
        }
    }

    /* ──────────────────────────────────────────────────────
       QZ TRAY — FIRMA DE PETICIONES E INSTALADOR POR CAJA
       QZ Tray no valida el certificado TLS del sitio: valida la firma de
       cada petición. Sin firma toda petición es anónima ("An anonymous
       request wants to access connected printers", Fingerprint: UNKNOWN
       REQUEST) y su ventana nativa reaparece siempre, porque no hay huella
       que recordar. El par certificado/llave se genera solo la primera vez
       que se pide (ver libraries/Qzcert.php); no hay paso manual.
    ────────────────────────────────────────────────────── */

    private function qz() {
        $this->load->library('qzcert');
        return $this->qzcert;
    }

    /**
     * Entrega el certificado público que identifica a este POS ante QZ Tray,
     * generándolo en el primer acceso. Sin sesión a propósito: el instalador
     * de cada caja lo descarga antes de que nadie inicie sesión, y un
     * certificado público no es un secreto.
     */
    function qz_certificate() {
        $cert = $this->qz()->certificate();
        if ($cert === '') {
            log_message('error', 'QZ Tray: sin certificado. ' . $this->qzcert->last_error());
        }
        return $this->output
            ->set_content_type('text/plain; charset=utf-8')
            ->set_output($cert);
    }

    /**
     * Firma con SHA-512 la cadena que QZ Tray pide firmar (incluye la
     * llamada, sus parámetros y un timestamp, así que la firma no se puede
     * reutilizar). Requiere sesión: solo el POS puede pedir firmas.
     */
    function qz_sign() {
        $this->output->set_content_type('text/plain; charset=utf-8');
        if (!$this->session->userdata('user_id')) {
            return $this->output->set_status_header(403)->set_output('');
        }
        $signature = $this->qz()->sign((string) $this->input->post('request'));
        if ($signature === '') {
            log_message('error', 'QZ Tray: no se pudo firmar. ' . $this->qzcert->last_error());
        }
        return $this->output->set_output($signature);
    }

    /**
     * Instalador .bat generado para ESTA instalación: trae la dirección del
     * POS ya escrita, instala QZ Tray si falta y deja el certificado como
     * override.crt para que QZ no vuelva a preguntar nunca en esa caja.
     * Es lo que descarga el botón del overlay del POS, de modo que el cajero
     * solo abre el archivo y acepta el aviso de Windows.
     */
    function qz_installer() {
        // base_url() sale de HTTP_HOST, que lo controla el cliente: se limpia
        // antes de escribirlo dentro de un archivo ejecutable.
        $url = preg_replace('/[^A-Za-z0-9\.\-:\/_]/', '', base_url());
        $url = rtrim($url, '/') . '/';
        $bat = $this->load->view('pos/qz_installer_bat', array('pos_url' => $url), true);
        $bat = str_replace(array("\r\n", "\n"), "\r\n", trim($bat)) . "\r\n";

        return $this->output
            ->set_content_type('application/octet-stream')
            ->set_header('Content-Disposition: attachment; filename="instalar-impresion-neurix.bat"')
            ->set_header('Cache-Control: no-store')
            ->set_output($bat);
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
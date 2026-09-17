<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

class PosRegister extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('pos_model');
        $this->load->model('hacienda_model');
        $this->load->model('AuditLog_model', 'audit_log');
        $this->load->library('form_validation');
        $this->load->library('datatables');
    }

    /**
     * Numeros del turno para el reporte de cierre.
     *
     * Es la fuente unica de la pantalla y del correo. No incluye el arqueo:
     * eso lo escribe el cajero y lo recalcula close_register() al guardar.
     *
     * @param int    $user_id cajero dueno de la caja
     * @param string $desde   fecha de apertura de la caja
     */
    /** Cajero cuyo cierre se esta validando, para la regla de la observacion. */
    private $_cajero_del_cierre = 0;

    private function _resumen_turno($user_id, $desde)
    {
        $monto = function ($fila, $campo = 'total') {
            return $fila && isset($fila->$campo) ? (float) $fila->$campo : 0.0;
        };

        $fondo     = (float) $this->session->userdata('cash_in_hand');
        $registro  = $this->pos_model->registerData($user_id);
        if ($registro) {
            $fondo = (float) $registro->cash_in_hand;
        }

        $metodos   = cobros_por_familia($this->pos_model->getRegisterPagosPorMetodo($desde, $user_id));
        $gastos    = $monto($this->pos_model->getRegisterExpenses($desde, $user_id));
        $depositos = $monto($this->pos_model->getDepositos($desde, $user_id));
        $notas     = $monto($this->pos_model->getRegisterNCSales($desde, $user_id));
        $credito   = $monto($this->pos_model->getRegisterSalesCredit($desde, $user_id));
        $apartados = $monto($this->pos_model->getRegisterCashSalesApart($desde, $user_id));
        $anulado   = $this->pos_model->getRegisterAnulaciones($desde, $user_id);

        $cobrado = 0.0;
        foreach ($metodos as $familia) {
            $cobrado += $familia['total'];
        }

        return array(
            'desde'             => $desde,
            'cajero'            => $this->site->getUser($user_id),
            'fondo'             => $fondo,
            'metodos'           => $metodos,
            'cobrado'           => $cobrado,
            'gastos'            => $gastos,
            'depositos'         => $depositos,
            'apartados'         => $apartados,
            'notas_credito'     => $notas,
            'anulaciones'       => (float) $anulado->total,
            'anulaciones_n'     => (int) $anulado->cantidad,
            'anulaciones_detalle' => $this->pos_model->getRegisterAnulacionesDetalle($desde, $user_id),
            'ventas_credito'    => $credito,
            'impuestos'         => $this->pos_model->getRegisterVentasPorImpuesto($desde, $user_id),
            'sinpe'             => $this->pos_model->getRegisterSinpeEntrantes($desde),
            'efectivo_esperado' => $this->_efectivo_esperado($fondo, $metodos['efectivo']['total'], $apartados, $depositos, $gastos, (float) $anulado->total),
        );
    }

    /**
     * Efectivo que deberia estar en la gaveta.
     *
     * Solo entra dinero que pasa por el cajon: el fondo, lo cobrado en
     * efectivo (ventas y apartados) y los depositos, menos los gastos pagados
     * de caja. Lo cobrado por tarjeta, SINPE o transferencia nunca llega a la
     * gaveta, y las notas de credito no guardan con que medio se devolvieron.
     *
     * Lo devuelto en efectivo al anular una factura si salio de la gaveta, y por
     * eso se resta: es la unica devolucion de la que si consta el medio.
     */
    private function _efectivo_esperado($fondo, $efectivo, $apartados, $depositos, $gastos, $anulaciones = 0)
    {
        return (float) $fondo + (float) $efectivo + (float) $apartados + (float) $depositos
             - (float) $gastos - (float) $anulaciones;
    }

    function register_details() {

        $register_open_time = $this->session->userdata('register_open_time');
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['ccsales'] = $this->pos_model->getRegisterCCSales($register_open_time);
        $this->data['cashsales'] = $this->pos_model->getRegisterCashSales($register_open_time);
        $this->data['chsales'] = $this->pos_model->getRegisterChSales($register_open_time);
        $this->data['other_sales'] = $this->pos_model->getRegisterOtherSales($register_open_time);
        $this->data['gcsales'] = $this->pos_model->getRegisterGCSales($register_open_time);
        $this->data['stripesales'] = $this->pos_model->getRegisterStripeSales($register_open_time);
        $this->data['totalsales'] = $this->pos_model->getRegisterSales($register_open_time);
        $this->data['expenses'] = $this->pos_model->getRegisterExpenses($register_open_time);
        $this->load->view($this->theme . 'pos/register_details', $this->data);
    }

    function today_sale() {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect($_SERVER["HTTP_REFERER"]);
        }

        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['ccsales'] = $this->pos_model->getTodayCCSales();
        $this->data['cashsales'] = $this->pos_model->getTodayCashSales();
        $this->data['chsales'] = $this->pos_model->getTodayChSales();
        $this->data['other_sales'] = $this->pos_model->getTodayOtherSales();
        $this->data['gcsales'] = $this->pos_model->getTodayGCSales();
        $this->data['stripesales'] = $this->pos_model->getTodayStripeSales();
        $this->data['totalsales'] = $this->pos_model->getTodaySales();
        // $this->data['expenses'] = $this->pos_model->getTodayExpenses();
        $this->load->view($this->theme . 'pos/today_sale', $this->data);
    }

    function shortcuts() {
        $this->load->view($this->theme . 'pos/shortcuts', $this->data);
    }

    function close_register($user_id = NULL) {

        if (!$this->Admin) {
            $user_id = $this->session->userdata('user_id');
        }
        // Lo unico que el cajero cuenta a mano es el efectivo del cajon.
        $this->form_validation->set_rules('total_cash_submitted', lang('cierre_efectivo_contado'), 'trim|required|numeric');
        $this->_cajero_del_cierre = $user_id;
        $this->form_validation->set_rules('note', lang('cierre_nota'), 'trim|callback_nota_si_falta');
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
                // Mismo criterio que la pantalla: solo lo que pasa por el cajon.
                'total_cash' => $total_cash,
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
                'tot_exentas_gravadas' => (float) $this->input->post('tot_exentas_gravadas'),
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
            $this->audit_log->log('cierre_caja', 'register', (int) $rid, 'Apertura: ' . ($register_open_time ?? ''), (float) $this->input->post('total_cash_submitted'));
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
            $this->data['resumen'] = $this->_resumen_turno($user_id, $register_open_time);
            $this->load->view($this->theme . 'pos/close_register', $this->data);
        }
    }


    /**
     * Un faltante hay que explicarlo: si lo contado no llega a lo que dice el
     * sistema, la observacion deja de ser opcional.
     */
    public function nota_si_falta($nota)
    {
        $user_id  = $this->_cajero_del_cierre ?: (int) $this->session->userdata('user_id');
        $registro = $this->pos_model->registerData($user_id);
        $desde    = $registro ? $registro->date : $this->session->userdata('register_open_time');
        $resumen  = $this->_resumen_turno($user_id, $desde);

        $contado = (float) str_replace(',', '.', (string) $this->input->post('total_cash_submitted'));

        if ($contado + 0.005 < (float) $resumen['efectivo_esperado'] && trim((string) $nota) === '') {
            $this->form_validation->set_message('nota_si_falta', lang('cierre_nota_obligatoria'));
            return FALSE;
        }

        return TRUE;
    }

    /**
     * PDF del cierre, con el mismo contenido que la pantalla.
     *
     * Sirve para las dos acciones: imprimir lo abre en una pestaña y el correo
     * lo manda adjunto. Devuelve la ruta del archivo temporal.
     */
    private function _pdf_cierre(array $resumen)
    {
        $html = $this->load->view(
            $this->theme . 'email/cierre_caja',
            array('r' => $resumen, 'para_pdf' => TRUE, 'Settings' => $this->Settings),
            TRUE
        );

        $ruta = sys_get_temp_dir() . DIRECTORY_SEPARATOR
              . 'cierre_' . date('Ymd_His', strtotime($resumen['desde'] ?: 'now')) . '.pdf';

        $mpdf = new \Mpdf\Mpdf(array(
            'tempDir'        => sys_get_temp_dir(),
            'CSSselectMedia' => 'screen',
            'format'         => 'A4',
            'margin_left'    => 10,
            'margin_right'   => 10,
            'margin_top'     => 10,
            'margin_bottom'  => 12,
        ));
        $mpdf->WriteHTML($html);
        $mpdf->Output($ruta, 'F');

        return $ruta;
    }

    /**
     * GET pos/cierre_pdf — el cierre en PDF, para ver e imprimir.
     *
     * En el sistema solo el tiquete de venta sale solo a la impresora: todo lo
     * demas se imprime desde el visor del navegador.
     */
    public function cierre_pdf()
    {
        $user_id = $this->Admin && $this->input->get('user_id')
            ? (int) $this->input->get('user_id')
            : (int) $this->session->userdata('user_id');

        $registro = $this->pos_model->registerData($user_id);
        $desde    = $registro ? $registro->date : $this->session->userdata('register_open_time');

        $ruta = $this->_pdf_cierre($this->_resumen_turno($user_id, $desde));

        $this->output
            ->set_content_type('application/pdf')
            ->set_header('Content-Disposition: inline; filename="' . basename($ruta) . '"')
            ->set_output(file_get_contents($ruta));

        @unlink($ruta);
    }

    /**
     * POST pos/enviar_cierre — manda el reporte del turno por correo, en PDF.
     *
     * No cierra la caja: es el mismo reporte de la pantalla, para archivarlo.
     * Sale por la cola, igual que los comprobantes.
     */
    public function enviar_cierre()
    {
        $para = trim((string) $this->input->post('correo'));
        if ($para === '') {
            $para = $this->Settings->email_emisor ?: $this->Settings->default_email;
        }

        if (!$para || !filter_var($para, FILTER_VALIDATE_EMAIL)) {
            $this->_json_cierre(array('ok' => false, 'msg' => lang('cierre_correo_falta')), 422);
            return;
        }

        $user_id = $this->Admin && $this->input->post('user_id')
            ? (int) $this->input->post('user_id')
            : (int) $this->session->userdata('user_id');

        $registro = $this->pos_model->registerData($user_id);
        $desde    = $registro ? $registro->date : $this->session->userdata('register_open_time');
        $resumen  = $this->_resumen_turno($user_id, $desde);

        try {
            $pdf = $this->_pdf_cierre($resumen);
        } catch (\Throwable $e) {
            log_message('error', '[Cierre] no se pudo armar el PDF: ' . $e->getMessage());
            $this->_json_cierre(array('ok' => false, 'msg' => lang('cierre_correo_error')), 500);
            return;
        }

        $this->load->model('queue_model');
        $this->queue_model->push(Queue_model::TYPE_EMAIL, array(
            'to'       => $para,
            'subject'  => lang('cierre_asunto') . ' - ' . $this->Settings->site_name,
            'message'  => $this->load->view($this->theme . 'email/cierre_caja', array('r' => $resumen, 'Settings' => $this->Settings), TRUE),
            'attach'   => array('ruta' => $pdf),
            'pdf_path' => $pdf,
        ));
        dispatch_queue_worker(Queue_model::TYPE_EMAIL);

        $this->audit_log->log('cierre_enviado', 'register', $registro ? (int) $registro->id : 0, $para);
        $this->_json_cierre(array('ok' => true, 'msg' => lang('cierre_correo_ok')));
    }

    private function _json_cierre($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Artículos vendidos durante el turno, en una página lista para imprimir.
     *
     * Se arma con una consulta directa: la librería Datatables exige el payload
     * POST de DataTables y esto se abre con un enlace, así que devolvía vacío.
     */
    function products_sales_in_register() {
        $desde   = $this->session->userdata('register_open_time');
        $user_id = $this->session->userdata('user_id');

        $items = $this->db->dbprefix('sale_items');
        $this->db
            ->select("{$items}.product_code AS codigo,
                      {$items}.product_name AS nombre,
                      SUM(COALESCE({$items}.quantity, 0)) AS unidades,
                      SUM(COALESCE({$items}.subtotal, 0)) AS total", FALSE)
            ->join('sales', 'sales.id=' . $items . '.sale_id', 'left')
            ->where('sales.date >', $desde)
            ->where('sales.created_by', $user_id)
            ->group_by(array($items . '.product_code', $items . '.product_name'))
            ->order_by('total', 'DESC');

        $this->data['articulos'] = $this->db->get('sale_items')->result();
        $this->data['desde']     = $desde;
        $this->data['cajero']    = $this->site->getUser($user_id);

        $this->load->view($this->theme . 'pos/articulos_turno', $this->data);
    }

    function invoices_in_register() {
        $start_date = $this->session->userdata('register_open_time');
        $end_date = date('Y-m-d h:i:s');
        $user = $this->session->userdata('user_id');

        $this->load->library('datatables');
        $this->datatables
                ->select("id, date, customer_name, total, total_tax, total_discount, grand_total, paid, (grand_total-paid) as balance, status")
                ->from('sales');
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->unset_column('id');

        if ($user) {
            $this->datatables->where('created_by', $user);
        }
        if ($start_date) {
            $this->datatables->where('date >=', $start_date);
        }
        if ($end_date) {
            $this->datatables->where('date <=', $end_date);
        }
        $result = json_decode($this->datatables->generate());
        if (isset($result->data[0])) {
            $resultado = $result->data[0];
        } else {
            $resultado = FALSE;
        }
    }

}
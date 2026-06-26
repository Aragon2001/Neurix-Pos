<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PosRegister extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('pos_model');
        $this->load->model('hacienda_model');
        $this->load->library('datatables');
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


    function products_sales_in_register() {

        $start_date = $this->session->userdata('register_open_time');
        $end_date = date('Y-m-d h:i:s');
        $user = $this->session->userdata('user_id');


        $this->load->library('datatables');
        $this->datatables
                ->select(
                        $this->db->dbprefix('products') . ".id as id, " .
                        $this->db->dbprefix('products') . ".name, " .
                        $this->db->dbprefix('products') . ".code," .
                        $this->db->dbprefix('product_store_qty') . ".quantity as qty_rest," .
                        $this->db->dbprefix('product_store_qty') . ".qty_fracc as qty_fracc_rest,"
                        . " COALESCE(sum(if (" . $this->db->dbprefix('sale_items') . ".esta_fraccionado < 1, " . $this->db->dbprefix('sale_items') . ".quantity, 0) ), 0) as sold,"
                        . " COALESCE(sum(if (" . $this->db->dbprefix('sale_items') . ".esta_fraccionado > 0, " . $this->db->dbprefix('sale_items') . ".quantity, 0) ), 0) as sold_fracc,"
                        . " ROUND(COALESCE(((sum(" . $this->db->dbprefix('sale_items') . ".subtotal)*" .
                        $this->db->dbprefix('products') . ".tax)/100), 0), 2) as tax, "
                        . "COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".quantity)*" .
                        $this->db->dbprefix('sale_items') . ".cost, 0) as cost, "
                        . "COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".subtotal), 0) as income,"
                        . " ROUND((COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".subtotal), 0)) - COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".quantity)*" . $this->db->dbprefix('sale_items') . ".cost, 0) -COALESCE(((sum(" . $this->db->dbprefix('sale_items') . ".subtotal)*" . $this->db->dbprefix('products') . ".tax)/100), 0), 2)
            as profit", FALSE)
                ->from('sale_items')
                ->join('products', 'sale_items.product_id=products.id', 'left')
                ->join('sales', 'sale_items.sale_id=sales.id', 'left')
                ->join('product_store_qty', 'product_store_qty.product_id=products.id', 'left');
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('sales.store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->group_by('products.id');

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
        if (isset($result->data)) {
            $resultado = $result->data;
        } else {
            $resultado = FALSE;
        }

        echo "<div class='myDivToPrint'>
                


                <div style='width:90%; margin:0 auto; '>
                    <h4> Productos vendidos por cajero: " . $this->session->userdata('first_name') . " "
        . "" . $this->session->userdata('last_name') . " </h4>
                    <h4>Apertura de caja: " . $start_date . "</h4>        
                    <h4>Fecha de Impresion: " . $end_date . "</h4>        
                </div>



                <table style='width:90%; margin:0 auto; '>
                <thead>
                    <tr>
                    <td style='text-align:center;'>Codigo</td>
                    <td style='text-align:center;'>Descripcion</td>
                    <td style='text-align:center;'>Unidades Vendidas</td>";

        if ($this->Settings->enable_fractions == "1") {
            echo "<td style='text-align:center;'>Fraccciones Vendidas</td>";
        }

        echo "<td style='text-align:center;'>Unidades restantes</td>";

        if ($this->Settings->enable_fractions == "1") {
            echo "<td style='text-align:center;'>Fraccciones restantes</td>";
        }

        echo "</tr>
                </thead>
                <tbody>
        ";

        foreach ($resultado as $item) {
            echo "<tr>";
            echo "<td>" . $item->code . "</td>";
            echo "<td>" . $item->name . "</td>";
            echo "<td style='text-align:right;'>" . $item->sold . "</td>";

            if ($this->Settings->enable_fractions == "1") {
                echo "<td style='text-align:right;'>" . $item->sold_fracc . "</td>";
            }

            echo "<td style='text-align:right;'>" . $item->qty_rest . "</td>";

            if ($this->Settings->enable_fractions == "1") {
                echo "<td style='text-align:right;'>" . $item->qty_fracc_rest . "</td>";
            }
            echo "</tr>";
        }

        echo "
                </tbody>
                <tfooter></tfooter>
                </table>

             </div>
        
            <style>
            
              thead,
            tfoot {
                background-color: #3f87a6 !important;
                color: #fff;
            }

            tbody {
                background-color: #e4f0f5 !important;
            }

            caption {
                padding: 10px;
                caption-side: bottom;
            }

            table {
                border-collapse: collapse;
                border: 2px solid rgb(200, 200, 200);
                letter-spacing: 1px;
                font-family: sans-serif;
                font-size: .8rem;
            }

            td,
            th {
                border: 1px solid rgb(190, 190, 190);
                padding: 5px 10px;
            }

            td {
                text-align: center;
            }
            
            @media print {
                .myDivToPrint {
                    background-color: white;
                    height: 100%;
                    width: 100%;
                    position: fixed;
                    top: 0;
                    left: 0;
                    margin: 0;
                    padding: 15px;
                    font-size: 14px;
                    line-height: 18px;
                }
            } 
          
            </style>
             
        <script>
            window.onload = function () {
                window.print();
            }
        </script>     
        
        ";
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
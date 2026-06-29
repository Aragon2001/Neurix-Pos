<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    function __construct() {
        parent::__construct();
        if (!$this->loggedIn) redirect('login');
        if (!$this->Admin)    redirect('/');
        $this->load->model('dashboard_model', 'dash');
    }

    function index() {
        /* ── Resolver período ── */
        $period = $this->input->get('period') ?: 'month';
        $store  = (int)($this->input->get('store') ?: $this->session->userdata('store_id') ?: 0);

        $from = $to = '';
        switch ($period) {
            case 'today':
                $from = $to = date('Y-m-d');
                break;
            case 'week':
                $from = date('Y-m-d', strtotime('monday this week'));
                $to   = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'year':
                $from = date('Y-01-01');
                $to   = date('Y-12-31');
                break;
            case 'custom':
                $from = $this->input->get('from') ?: date('Y-m-01');
                $to   = $this->input->get('to')   ?: date('Y-m-t');
                $period = 'custom';
                break;
            default: // month
                $period = 'month';
                $from   = date('Y-m-01');
                $to     = date('Y-m-t');
        }

        /* Si vienen from/to por GET pero no period=custom, detectar custom */
        if ($this->input->get('from') && $this->input->get('to') && $this->input->get('period') !== 'today'
            && $this->input->get('period') !== 'week' && $this->input->get('period') !== 'year'
            && $this->input->get('period') !== 'month') {
            $from   = $this->input->get('from');
            $to     = $this->input->get('to');
            $period = 'custom';
        }

        $lFrom = date('Y-m-d', strtotime("$from -1 month"));
        $lTo   = date('Y-m-d', strtotime("$to -1 month"));

        $data = [
            /* Contexto del período */
            'period'          => $period,
            'from'            => $from,
            'to'              => $to,
            'current_store'   => $store,

            /* KPI ventas */
            'sales_period'    => $this->dash->getTotalSalesRange($from, $to, $store),
            'sales_last'      => $this->dash->getTotalSalesRange($lFrom, $lTo, $store),
            'sales_status'    => $this->dash->getSalesStatusBreakdown($from, $to, $store),

            /* KPI compras / por pagar */
            'purchases_kpi'   => $this->dash->getTotalPurchasesRange($from, $to, $store),
            /* KPI cuentas × cobrar */
            'accounts_rec'    => $this->dash->getAccountsReceivable($store),

            /* Gráfica financiera */
            'finance_12m'     => $this->dash->getFinance12Months($store),

            /* Facturas electrónicas */
            'fe_stats'        => $this->dash->getFEStats($from, $to),

            /* Inventario */
            'inv_value'       => $this->dash->getInventoryValue($store),
            'inv_by_tax'      => $this->dash->getInventoryTaxBreakdown($store),
            'inv_avg_6m'      => $this->dash->getSalesAvg6Months($store),

            /* Análisis ventas */
            'top_days'        => $this->dash->getTopDays($from, $to, $store),
            'top_products'    => $this->dash->getTopProductsEmp($store, 10),
            'pay_methods'     => $this->dash->getPaymentMethodBreakdown($from, $to, $store),

            /* Listas */
            'low_stock'       => $this->dash->getLowStockProducts($store),
            'stores'          => $this->dash->getStores(),
            'recent_sales'    => $this->dash->getRecentSales($store, 10),
            'top_customers'   => $this->dash->getTopCustomers($store, 8),

            /* Misc */
            'total_customers' => $this->dash->getTotalCustomers(),
            'expenses_kpi'    => $this->dash->getTotalExpensesRange($from, $to, $store),
        ];

        $meta = [
            'page_title' => 'Inicio',
            'bc'         => [['page' => 'Inicio', 'link' => '#']],
            'm'          => 'dashboard',
            'v'          => 'index',
        ];

        $this->page_construct('dashboard/index', array_merge($this->data, $data), $meta);
    }
}

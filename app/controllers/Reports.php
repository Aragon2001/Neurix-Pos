<?php defined('BASEPATH') or exit('No direct script access allowed');

class Reports extends MY_Controller
{

    function __construct()
    {
        parent::__construct();


        if (!$this->loggedIn) {
            redirect('login');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }

        $this->load->model('reports_model');
        $this->load->model('sales_model');
        $this->load->model('hacienda_model');
    }

    function daily_sales($year = NULL, $month = NULL)
    {
        if ($this->input->post('customer')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($this->input->post('customer'), $user, $start_date, $end_date, NULL);
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['page_title'] = $this->lang->line("sales_report");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
        $this->page_construct('reports/daily', $this->data, $meta);
    }

    function get_daily_sales($year = NULL, $month = NULL)
    {
        //        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;

        $this->load->library('datatables');
        $this->datatables
            ->select("sales.id, consecutivo, date, tec_customers.cf2 as identificacion, customer_name, total, total_discount, grand_total, "
                . "(select COALESCE(sum(subtotal),0) from tec_sale_items where sale_id = tec_sales.id and tax = '0') as excento,"
                . "(select COALESCE(sum(subtotal - item_tax),0) from tec_sale_items where sale_id = tec_sales.id and tax > '0') as gravado,"
                . "(select COALESCE(sum(item_tax),0) from tec_sale_items where sale_id = tec_sales.id and tax > '0') as impuesto")
            ->join('tec_customers', 'tec_customers.id = sales.customer_id', 'left')
            ->join('tec_hacienda_tiketes', 'tec_hacienda_tiketes.sale_id=sales.id', 'left')
            ->from('sales');
        // $this->db->group_by('tec_hacienda_tiketes.sale_id');
        // $this->db->where_not_in('tec_hacienda_tiketes.sale_id', array('52354'));
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('sales.store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->unset_column('id');
        //        if ($customer) {
        //            $this->datatables->where('customer_id', $customer);
        //        }
        if ($user) {
            $this->datatables->where('created_by', $user);
        }
        if ($start_date) {
            $this->datatables->where('date >=', $start_date . ' 00:00');
            $this->datatables->where('date <=', $start_date . ' 23:59');
        } else {
            $this->datatables->where('date >=', date('Y-m-d ') . '00:00');
            $this->datatables->where('date <=', date('Y-m-d H:i'));
        }

        echo $this->datatables->generate();
    }

    function monthly_sales($year = NULL)
    {
        if ($this->input->post('customer')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($this->input->post('customer'), $user, $start_date, $end_date, NULL);
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['page_title'] = $this->lang->line("sales_report");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
        $this->page_construct('reports/monthly', $this->data, $meta);
    }

    function get_monthly_sales($year = NULL)
    {
        //        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;

        $this->load->library('datatables');
        $this->datatables
            ->select("sales.id, consecutivo, date, tec_customers.cf2 as identificacion, customer_name, total, total_discount, grand_total, "
                . "(select COALESCE(sum(subtotal),0) from tec_sale_items where sale_id = tec_sales.id and tax = '0') as excento,"
                . "(select COALESCE(sum(subtotal - item_tax),0) from tec_sale_items where sale_id = tec_sales.id and tax > '0') as gravado,"
                . "(select COALESCE(sum(item_tax),0) from tec_sale_items where sale_id = tec_sales.id and tax > '0') as impuesto")
            ->join('tec_customers', 'tec_customers.id = sales.customer_id', 'left')
            ->join('tec_hacienda_tiketes', 'tec_hacienda_tiketes.sale_id=sales.id', 'left')
            ->from('sales');
        // $this->db->group_by('tec_hacienda_tiketes.sale_id');
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('sales.store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->unset_column('id');
        //        if ($customer) {
        //            $this->datatables->where('customer_id', $customer);
        //        }
        if ($user) {
            $this->datatables->where('created_by', $user);
        }
        if ($start_date) {
            $this->datatables->where('date >=', $start_date . '-01 00:00');
            $this->datatables->where('date <=', $start_date . '-31 23:59');
        } else {
            $this->datatables->where('date >=', date('Y-m-') . '01 00:00');
            $this->datatables->where('date <=', date('Y-m-d H:i'));
        }

        echo $this->datatables->generate();
    }

    function compras_electronicas($year = NULL)
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['page_title'] = $this->lang->line("sales_report");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => "Informe de Compras", 'bc' => $bc);
        $this->page_construct('reports/compraselectronicas', $this->data, $meta);
    }

    function get_compras_electronicas($year = NULL)
    {
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;

        $this->load->library('datatables');
        $this->datatables
            ->select("FechaEmisionDoc,Estatus, documento,ConsecutivoDocEmisor,nombre_emisor,correo_emisor,tipo_doc_emisor,NumeroCedulaEmisor,
                       TotalFactura,MontoTotalImpuesto,TotalServGravados,TotalServExentos,TotalMercanciasGravadas,TotalMercanciasExentas,TotalGravado,TotalExento,TotalVenta,TotalVentaNeta ")
            ->from('documentoshacienda');

        // if ($user) {
        //     $this->datatables->where('id_supplier', $user);
        // }
        if ($start_date) {
            $nuevafecha = date("Y-m-d", strtotime($start_date . '-31 23:59' . "+ 1 day"));
            $this->datatables->where('FechaEmisionDoc >=', $start_date . '-01 00:00');
            $this->datatables->where('FechaEmisionDoc <=', $nuevafecha);
        } else {
            $this->datatables->where('FechaEmisionDoc >=', date('Y-m-') . '01 00:00');
            $this->datatables->where('FechaEmisionDoc <=', date('Y-m-d H:i'));
        }

        echo $this->datatables->generate();
    }


    function index()
    {
        if ($this->input->post('customer')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($this->input->post('customer'), $user, $start_date, $end_date, NULL);
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['page_title'] = $this->lang->line("sales_report");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
        $this->page_construct('reports/sales', $this->data, $meta);
    }

    function get_sales()
    {
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;

        $this->load->library('datatables');
        $this->datatables
            ->select("id, date, customer_name, total, total_tax, total_discount, grand_total, paid, (grand_total-paid) as balance, status")
            ->from('sales');
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->unset_column('id');
        if ($customer) {
            $this->datatables->where('customer_id', $customer);
        }
        if ($user) {
            $this->datatables->where('created_by', $user);
        }
        if ($start_date) {
            $this->datatables->where('date >=', $start_date);
        }
        if ($end_date) {
            $this->datatables->where('date <=', $end_date);
        }

        echo $this->datatables->generate();
    }

    function products()
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['products'] = $this->reports_model->getAllProducts();
        $this->data['page_title'] = $this->lang->line("products_report");
        $this->data['page_title'] = $this->lang->line("products_report");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('products_report')));
        $meta = array('page_title' => lang('products_report'), 'bc' => $bc);
        $this->page_construct('reports/products', $this->data, $meta);
    }

    function products_quantity()
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['products'] = $this->reports_model->getAllProducts();
        $this->data['page_title'] = $this->lang->line("products_quantity");
        $this->data['page_title'] = $this->lang->line("products_quantity");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('products_quantity')));
        $meta = array('page_title' => lang('products_quantity'), 'bc' => $bc);
        $this->page_construct('reports/products_quantity', $this->data, $meta);
    }

    function get_products()
    {
        $product = $this->input->get('product') ? $this->input->get('product') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        //COALESCE(sum(".$this->db->dbprefix('sale_items').".quantity)*".$this->db->dbprefix('products').".cost, 0) as cost,
        $this->load->library('datatables');
        $this->datatables
            ->select($this->db->dbprefix('products') . ".id as id, " . $this->db->dbprefix('products') . ".name, " . $this->db->dbprefix('products') . ".code, COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".quantity), 0) as sold,".$this->db->dbprefix('product_store_qty').".quantity, ROUND(COALESCE(((sum(" . $this->db->dbprefix('sale_items') . ".subtotal)*" . $this->db->dbprefix('products') . ".tax)/100), 0), 2) as tax, COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".quantity)*" . $this->db->dbprefix('sale_items') . ".cost, 0) as cost, COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".subtotal), 0) as income, ROUND((COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".subtotal), 0)) - COALESCE(sum(" . $this->db->dbprefix('sale_items') . ".quantity)*" . $this->db->dbprefix('sale_items') . ".cost, 0) -COALESCE(((sum(" . $this->db->dbprefix('sale_items') . ".subtotal)*" . $this->db->dbprefix('products') . ".tax)/100), 0), 2)
            as profit", FALSE)
            ->from('sale_items')
            ->join('products', 'sale_items.product_id=products.id', 'left')
            ->join('product_store_qty', 'product_store_qty.product_id=products.id', 'left')
            ->join('sales', 'sale_items.sale_id=sales.id', 'left');
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('sales.store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->group_by('products.id');

        if ($product) {
            $this->datatables->where('products.id', $product);
        }
        if ($start_date) {
            $this->datatables->where('date >=', $start_date);
        }
        if ($end_date) {
            $this->datatables->where('date <=', $end_date);
        }
        echo $this->datatables->generate();
    }

    function profit($income, $cost, $tax)
    {
        return floatval($income) . " - " . floatval($cost) . " - " . floatval($tax);
    }

    function top_products()
    {
        $this->data['topProducts'] = $this->reports_model->topProducts();
        $this->data['topProducts1'] = $this->reports_model->topProducts1();
        $this->data['topProducts3'] = $this->reports_model->topProducts3();
        $this->data['topProducts12'] = $this->reports_model->topProducts12();
        $this->data['page_title'] = $this->lang->line("top_products");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('top_products')));
        $meta = array('page_title' => lang('top_products'), 'bc' => $bc);
        $this->page_construct('reports/top', $this->data, $meta);
    }

    function registers()
    {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getAllStaff();
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('registers_report')));
        $meta = array('page_title' => lang('registers_report'), 'bc' => $bc);
        $this->page_construct('reports/registers', $this->data, $meta);
    }

    function get_register_logs()
    {
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select("{$this->db->dbprefix('registers')}.id as id, date, closed_at, ({$this->db->dbprefix('users')}.first_name || ' ' || {$this->db->dbprefix('users')}.last_name || '<br>' || {$this->db->dbprefix('users')}.email) as user, cash_in_hand, (total_cc_slips || ' (' || total_cc_slips_submitted || ')') as cc_slips, (total_cheques || ' (' || total_cheques_submitted || ')') as total_cheques, (total_cash || ' (' || total_cash_submitted || ')') as total_cash, note", FALSE);
        } else {
            $this->datatables->select("{$this->db->dbprefix('registers')}.id as id, date, closed_at, CONCAT(" . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . ".last_name, '<br>', " . $this->db->dbprefix('users') . ".email) as user, cash_in_hand, CONCAT(total_cc_slips, ' (', total_cc_slips_submitted, ')') as cc_slips, CONCAT(total_cheques, ' (', total_cheques_submitted, ')') as total_cheques, CONCAT(total_cash, ' (', total_cash_submitted, ')') as total_cash, note ,user_id", FALSE);
        }
        $this->datatables->from("registers")
            ->join('users', 'users.id=registers.user_id', 'left');

        $this->datatables->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('reports/close_register/?user_id=$1&date=$2') . "' title='" . lang("add_payment") . "' class='tip btn btn-primary btn-xs' data-toggle='ajax'><i class='fa fa-print'></i></a> </div></div>", "user_id, date");
        if ($user) {
            $this->datatables->where('registers.user_id', $user);
        }
        if ($start_date) {
            $this->datatables->where('date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
        }
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('registers.store_id', $this->session->userdata('store_id'));
        }
        echo $this->datatables->generate();
    }

    function payments()
    {
        if ($this->input->post('customer')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($this->input->post('customer'), $user, $start_date, $end_date, NULL);
        }
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('payments_report')));
        $meta = array('page_title' => lang('payments_report'), 'bc' => $bc);
        $this->page_construct('reports/payments', $this->data, $meta);
    }

    function get_payments()
    {
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;
        $ref = $this->input->get('payment_ref') ? $this->input->get('payment_ref') : NULL;
        $sale_id = $this->input->get('sale_no') ? $this->input->get('sale_no') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $paid_by = $this->input->get('paid_by') ? $this->input->get('paid_by') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;

        $this->load->library('datatables');
        $this->datatables
            ->select("{$this->db->dbprefix('payments')}.id as id, {$this->db->dbprefix('payments')}.date, {$this->db->dbprefix('payments')}.reference as ref, {$this->db->dbprefix('sales')}.id as sale_no, paid_by, amount")
            ->from('payments')
            ->join('sales', 'payments.sale_id=sales.id', 'left')
            ->group_by('payments.id');

        if ($this->session->userdata('store_id')) {
            $this->datatables->where('payments.store_id', $this->session->userdata('store_id'));
        }
        if ($user) {
            $this->datatables->where('payments.created_by', $user);
        }
        if ($ref) {
            $this->datatables->where('payments.reference', $ref);
        }
        if ($paid_by) {
            $this->datatables->where('payments.paid_by', $paid_by);
        }
        if ($sale_id) {
            $this->datatables->where('sales.id', $sale_id);
        }
        if ($customer) {
            $this->datatables->where('sales.customer_id', $customer);
        }
        if ($start_date) {
            $this->datatables->where($this->db->dbprefix('payments') . '.date BETWEEN "' . $start_date . '" and "' . $end_date . '"');
        }

        echo $this->datatables->generate();
    }

    function alerts()
    {
        $data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('stock_alert');
        $bc = array(array('link' => '#', 'page' => lang('stock_alert')));
        $meta = array('page_title' => lang('stock_alert'), 'bc' => $bc);
        $this->page_construct('reports/alerts', $this->data, $meta);
    }

    function get_alerts()
    {
        $this->load->library('datatables');
        $this->datatables->select($this->db->dbprefix('products') . ".id as id, " . $this->db->dbprefix('products') . ".image as image, " . $this->db->dbprefix('products') . ".code as code, " . $this->db->dbprefix('products') . ".name as pname, type, " . $this->db->dbprefix('categories') . ".name as cname, (CASE WHEN psq.quantity IS NULL THEN 0 ELSE psq.quantity END) as quantity, alert_quantity, tax, tax_method, cost, (CASE WHEN psq.price > 0 THEN psq.price ELSE {$this->db->dbprefix('products')}.price END) as price", FALSE)
            ->from('products')
            ->join('categories', 'categories.id=products.category_id')
            ->join("( SELECT * from {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$this->session->userdata('store_id')}) psq", 'products.id=psq.product_id', 'left')
            ->where("(CASE WHEN psq.quantity IS NULL THEN 0 ELSE psq.quantity END) < {$this->db->dbprefix('products')}.alert_quantity", NULL, FALSE)
            ->group_by('products.id');
        $this->datatables->add_column("Actions", "<div class='text-center'><a href='#' class='btn btn-xs btn-primary ap tip' data-id='$1' title='" . lang('add_to_purcahse_order') . "'><i class='fa fa-plus'></i></a></div>", "id");
        // $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }

    public function credit_customers()
    {
        $this->reports_model->update_deudas();
        if ($this->input->post('amount-paid')) {
            $customer = $this->input->post('customer') ? $this->input->post('customer') : NULL;
            $paid_amount = $this->input->post('amount-paid');
            $where = "";
            if ($this->session->userdata('store_id')) {
                $where .= " AND store_id =" . $this->session->userdata('store_id');
            }
            if ($customer) {
                $where .= " AND customer_id =" . $customer;
            }

            $result = $this->db->query("SELECT id, date, customer_name, total, total_tax, total_discount, grand_total, paid, (grand_total-paid) as balance, status
            FROM " . $this->db->dbprefix('sales') . " WHERE status <>  'paid' " . $where . " AND id_shipping_method IS NULL ORDER BY `date` DESC");
            // $this->datatables
            //     ->select("id, date, customer_name, total, total_tax, total_discount, grand_total, paid, (grand_total-paid) as balance, status")
            //     ->from('sales')
            //     ->where('status <>', 'paid')
            //     ->where('id_shipping_method IS  NULL', null, false);
            // if ($customer) {
            //     $this->datatables->where('customer_id', $customer);
            // }
            // $result = json_decode($this->datatables->generate());
            // dd($result->result());
            foreach ($result->result() as $row) {
                if ($paid_amount > $row->balance) {
                    $p_amount = $row->balance;
                } else {
                    $p_amount = $paid_amount;
                }
                $date = date('Y-m-d H:i:s');
                $payment = array(
                    'date' => $date,
                    'sale_id' => $row->id,
                    'customer_id' => $customer,
                    'reference' => '',
                    'amount' => $p_amount,
                    'paid_by' => $this->input->post('paid_by'),
                    'cheque_no' => $this->input->post('cheque_no'),
                    'gc_no' => $this->input->post('gift_card_no'),
                    'cc_no' => $this->input->post('pcc_no'),
                    'cc_holder' => $this->input->post('pcc_holder'),
                    'cc_month' => $this->input->post('pcc_month'),
                    'cc_year' => $this->input->post('pcc_year'),
                    'cc_type' => $this->input->post('pcc_type'),
                    'note' => $this->input->post('note'),
                    'created_by' => $this->session->userdata('user_id'),
                    'store_id' => $this->session->userdata('store_id'),
                );
                $this->sales_model->addPayment($payment);
                $paid_amount = $paid_amount - $p_amount;
            }
        }
        if ($this->input->post('customer')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($this->input->post('customer'), $user, $start_date, $end_date, NULL);
        }
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['page_title'] = "Informe de clientes de credito";
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => "Informe de clientes de credito"));
        $meta = array('page_title' => "Informe de clientes de credito", 'bc' => $bc);

        $this->page_construct('reports/custumer_credits', $this->data, $meta);
    }

    public function get_credit_customers()
    {

        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;

        $this->load->library('datatables');

        $this->datatables
            ->select("id, date, customer_name, total, total_tax, total_discount, grand_total, paid, (grand_total-paid) as balance, status")
            ->from('sales')
            ->where('status <>', 'paid')
            ->where('id_shipping_method IS  NULL', null, false);;
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->unset_column('id');
        if ($customer) {
            $this->datatables->where('customer_id', $customer);
        }
        if ($user) {
            $this->datatables->where('created_by', $user);
        }
        if ($start_date) {
            $this->datatables->where('date >=', $start_date);
        }
        if ($end_date) {
            $this->datatables->where('date <=', $end_date);
        }

        echo $this->datatables->generate();
    }

    function missing_inventory($year = NULL, $month = NULL)
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = $this->lang->line("sales_report");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
        $meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
        $this->page_construct('reports/missing_inventory', $this->data, $meta);
    }

    function get_missing_inventory()
    {
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $this->load->library('datatables');

        $this->datatables
            ->select("st.product_code as id,st.product_code as product_id,
            CASE
            WHEN p.type != 'combo'
              THEN SUM(st.quantity)
              ELSE
              SUM(st.quantity)/2
            END  AS faltante,
            CASE
              WHEN p.type != 'combo'
                THEN st.product_name
                ELSE
                (SELECT CONCAT('COMBO(',GROUP_CONCAT(NAME),')') FROM tec_products WHERE CODE IN(SELECT item_code FROM tec_combo_items WHERE product_id = p.id) )
              END
             AS producto ,sales.DATE AS emitido")
            ->from('sales')
            ->join('tec_sale_items st', 'st.sale_id = sales.id', 'left')
            ->join('tec_products p', 'p.id = st.product_id', 'left')
            ->join('tec_combo_items ci', 'ci.product_id=p.id', 'left')
            ->group_by('st.product_id');
        $this->db->order_by("sales.DATE", "desc");
        $this->datatables->unset_column('id');
        if ($start_date) {
            $this->datatables->where('date >=', $start_date);
        }
        if ($end_date) {
            $this->datatables->where('date <=', $end_date);
        }
        echo $this->datatables->generate();
    }

    function monthly_fec()
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = $this->lang->line("monthly_fec");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('monthly_fec')));
        $meta = array('page_title' => lang('monthly_fec'), 'bc' => $bc);
        $this->page_construct('reports/monthly_fec', $this->data, $meta);
    }

    function get_monthly_fec()
    {
        ini_set("memory_limit", "-1");
        ini_set('max_input_vars', 8000);
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $hacienda = $this->hacienda_model->getAllFEC($start_date, $end_date);
        $resultados = array();
        $n = 0;
        foreach ($hacienda as $itemsH) {
            $id = $itemsH['sale_id'];
            $identificacion = '';
            $NombreCompleto = '';
            $FechaFactura = '';
            $codigoMoneda = '';
            $tipoCambio = '';
            $creditoCompra = '';
            $consecutivo = '';
            $TotalServGravados = 0.00000;
            $TotalServExentos = 0.00000;
            $TotalServExonerado = 0.00000;
            $TotalMercanciasGravadas = 0.00000;
            $TotalMercanciasExentas = 0.00000;
            $TotalMercExonerada = 0.00000;
            $TotalGravado = 0.00000;
            $TotalExento = 0.00000;
            $TotalExonerado = 0.00000;
            $TotalVenta = 0.00000;
            $TotalDescuentos = 0.00000;
            $TotalVentaNeta = 0.00000;
            $TotalImpuesto = 0.00000;
            $TotalComprobante = 0.00000;
            $tarifa0 = 0.00000;
            $tarifa1 = 0.00000;
            $tarifa2 = 0.00000;
            $tarifa4 = 0.00000;
            $tarifa13 = 0.00000;

            $DOM = new \DOMDocument('1.0', 'utf-8');
            // $DOM->validateOnParse = true;
            libxml_use_internal_errors(true);
            $sxe = simplexml_load_string($itemsH['xml_sign']);
            if ($sxe) {
                $DOM->loadXML($itemsH['xml_sign']);
                $TotalServGravados = $DOM->getElementsByTagName('TotalServGravados')->item(0) ? $DOM->getElementsByTagName('TotalServGravados')->item(0)->nodeValue : '0';
                $TotalServExentos = $DOM->getElementsByTagName('TotalServExentos')->item(0) ? $DOM->getElementsByTagName('TotalServExentos')->item(0)->nodeValue : '0';
                $TotalServExonerado = $DOM->getElementsByTagName('TotalServExonerado')->item(0) ? $DOM->getElementsByTagName('TotalServExonerado')->item(0)->nodeValue : '0';
                $TotalMercanciasGravadas = $DOM->getElementsByTagName('TotalMercanciasGravadas')->item(0) ? $DOM->getElementsByTagName('TotalMercanciasGravadas')->item(0)->nodeValue : '0';
                $TotalMercanciasExentas = $DOM->getElementsByTagName('TotalMercanciasExentas')->item(0) ? $DOM->getElementsByTagName('TotalMercanciasExentas')->item(0)->nodeValue : '0';
                $TotalMercExonerada = $DOM->getElementsByTagName('TotalMercExonerada')->item(0) ? $DOM->getElementsByTagName('TotalMercExonerada')->item(0)->nodeValue : '0';
                $TotalGravado = $DOM->getElementsByTagName('TotalGravado')->item(0) ? $DOM->getElementsByTagName('TotalGravado')->item(0)->nodeValue : '0';
                $TotalExento = $DOM->getElementsByTagName('TotalExento')->item(0) ? $DOM->getElementsByTagName('TotalExento')->item(0)->nodeValue : '0';
                $TotalExonerado = $DOM->getElementsByTagName('TotalExonerado')->item(0) ? $DOM->getElementsByTagName('TotalExonerado')->item(0)->nodeValue : '0';
                $TotalVenta = $DOM->getElementsByTagName('TotalVenta')->item(0) ? $DOM->getElementsByTagName('TotalVenta')->item(0)->nodeValue : '0';
                $TotalDescuentos = $DOM->getElementsByTagName('TotalDescuentos')->item(0) ? $DOM->getElementsByTagName('TotalDescuentos')->item(0)->nodeValue : '0';
                $TotalVentaNeta = $DOM->getElementsByTagName('TotalVentaNeta')->item(0) ? $DOM->getElementsByTagName('TotalVentaNeta')->item(0)->nodeValue : '0';
                $TotalImpuesto = $DOM->getElementsByTagName('TotalImpuesto')->item(0) ? $DOM->getElementsByTagName('TotalImpuesto')->item(0)->nodeValue : '0';
                $TotalComprobante = $DOM->getElementsByTagName('TotalComprobante')->item(0) ? $DOM->getElementsByTagName('TotalComprobante')->item(0)->nodeValue : '0';
                $identificacion = $DOM->getElementsByTagName('Numero')->item(0) ? $DOM->getElementsByTagName('Numero')->item(0)->nodeValue : '0';
                $NombreCompleto = $DOM->getElementsByTagName('Nombre')->item(0) ? $DOM->getElementsByTagName('Nombre')->item(0)->nodeValue : '0';
                $FechaFactura = $DOM->getElementsByTagName('FechaEmision')->item(0) ? $DOM->getElementsByTagName('FechaEmision')->item(0)->nodeValue : '0';
                $codigoMoneda = $DOM->getElementsByTagName('CodigoMoneda')->item(0) ? $DOM->getElementsByTagName('CodigoMoneda')->item(0)->nodeValue : '0';
                $tipoCambio = $DOM->getElementsByTagName('TipoCambio')->item(0) ? $DOM->getElementsByTagName('TipoCambio')->item(0)->nodeValue : '0';
                $consecutivo = $DOM->getElementsByTagName('NumeroConsecutivo')->item(0) ? $DOM->getElementsByTagName('NumeroConsecutivo')->item(0)->nodeValue : '0';
                $creditoCompra = $DOM->getElementsByTagName('PlazoCredito')->item(0) ? $DOM->getElementsByTagName('PlazoCredito')->item(0)->nodeValue : '';
                for ($x = 0; $x <= $DOM->getElementsByTagName('Tarifa')->length; $x++) {
                    $tipoTarifa = $DOM->getElementsByTagName('Tarifa')->item($x) ? $DOM->getElementsByTagName('Tarifa')->item($x)->nodeValue : '0';
                    switch ($tipoTarifa) {
                        case "0":
                            $tarifa0 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                            break;
                        case "1":
                            $tarifa1 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                            break;
                        case "2":
                            $tarifa2 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                            break;
                        case "4":
                            $tarifa4 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                            break;
                        case "13":
                            $tarifa13 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                            break;
                    }
                }
            } else {
                $error = libxml_get_errors()[0];
                $count = 0;
                $msj = '';
                foreach ($error as $e) {
                    if ($count == 3) {
                        $msj = $e;
                    }
                    $count++;
                }
                $this->session->set_flashdata('error', $msj);
            }

            $res =
                [
                    'id' => $id,
                    'identificacion' => $identificacion,
                    'NombreCompleto' => $NombreCompleto,
                    'FechaFactura' => $FechaFactura,
                    'codigoMoneda' => $codigoMoneda,
                    'tipoCambio' => $tipoCambio,
                    'consecutivo' => $consecutivo,
                    'TotalServGravados' => $TotalServGravados,
                    'TotalServExentos' => $TotalServExentos,
                    'TotalMercanciasGravadas' => $TotalMercanciasGravadas,
                    'TotalMercanciasExentas' => $TotalMercanciasExentas,
                    'TotalMercExonerada' => $TotalMercExonerada,
                    'TotalServExonerado' => $TotalServExonerado,
                    'TotalGravado' => $TotalGravado,
                    'TotalExento' => $TotalExento,
                    'TotalExonerado' => $TotalExonerado,
                    'TotalVenta' => $TotalVenta,
                    'TotalDescuentos' => $TotalDescuentos,
                    'TotalVentaNeta' => $TotalVentaNeta,
                    'TotalImpuesto' => $TotalImpuesto,
                    'TotalComprobante' => $TotalComprobante,
                    'tarifa0' => $tarifa0,
                    'tarifa1' => $tarifa1,
                    'tarifa2' => $tarifa2,
                    'tarifa4' => $tarifa4,
                    'tarifa13' => $tarifa13,
                    'creditoCompra' => $creditoCompra
                ];
            array_push($resultados, $res);
            $n++;
        }
        $table =  [
            'recordsTotal' => count($hacienda),
            'draw' =>  0,
            'recordsTotal' => count($hacienda),
            'recordsFiltered' =>  count($hacienda),
            'data' => $resultados
        ];
        echo json_encode($table);
    }

    function monthly_sale_tax()
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = $this->lang->line("monthly_sale_tax");
        $this->data['customers'] = $this->reports_model->getAllCustomers();
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('monthly_sale_tax')));
        $meta = array('page_title' => lang('monthly_sale_tax'), 'bc' => $bc);
        $this->page_construct('reports/monthly_sale_tax', $this->data, $meta);
    }

    function get_monthly_sale_tax()
    {
        ini_set("memory_limit", "-1");
        ini_set('max_input_vars', 8000);
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $hacienda = $this->hacienda_model->getAllSale($start_date, $end_date, $customer);
        $resultados = array();
        $n = 0;
        if ($hacienda) {
            foreach ($hacienda as $itemsH) {
                $is_cn = false;
                if ($this->hacienda_model->isSaleHasCn($itemsH['sale_id'])) {
                    $is_cn = true;
                }
                $id = $itemsH['sale_id'];
                $identificacion = '';
                $NombreCompleto = '';
                $FechaFactura = '';
                $codigoMoneda = '';
                $tipoCambio = '';
                $creditoCompra = '';
                $consecutivo = '';
                $TotalServGravados = 0.00000;
                $TotalServExentos = 0.00000;
                $TotalServExonerado = 0.00000;
                $TotalMercanciasGravadas = 0.00000;
                $TotalMercanciasExentas = 0.00000;
                $TotalMercExonerada = 0.00000;
                $TotalGravado = 0.00000;
                $TotalExento = 0.00000;
                $TotalExonerado = 0.00000;
                $TotalVenta = 0.00000;
                $TotalDescuentos = 0.00000;
                $TotalVentaNeta = 0.00000;
                $TotalImpuesto = 0.00000;
                $TotalComprobante = 0.00000;
                $tarifa0 = 0.00000;
                $tarifa1 = 0.00000;
                $tarifa2 = 0.00000;
                $tarifa4 = 0.00000;
                $tarifa13 = 0.00000;

                $DOM = new \DOMDocument('1.0', 'utf-8');
                // $DOM->validateOnParse = true;
                libxml_use_internal_errors(true);
                $sxe = simplexml_load_string($itemsH['xml_sign']);
                if ($sxe) {
                    $DOM->loadXML($itemsH['xml_sign']);
                    $TotalServGravados = $DOM->getElementsByTagName('TotalServGravados')->item(0) ? $DOM->getElementsByTagName('TotalServGravados')->item(0)->nodeValue : '0';
                    $TotalServExentos = $DOM->getElementsByTagName('TotalServExentos')->item(0) ? $DOM->getElementsByTagName('TotalServExentos')->item(0)->nodeValue : '0';
                    $TotalServExonerado = $DOM->getElementsByTagName('TotalServExonerado')->item(0) ? $DOM->getElementsByTagName('TotalServExonerado')->item(0)->nodeValue : '0';
                    $TotalMercanciasGravadas = $DOM->getElementsByTagName('TotalMercanciasGravadas')->item(0) ? $DOM->getElementsByTagName('TotalMercanciasGravadas')->item(0)->nodeValue : '0';
                    $TotalMercanciasExentas = $DOM->getElementsByTagName('TotalMercanciasExentas')->item(0) ? $DOM->getElementsByTagName('TotalMercanciasExentas')->item(0)->nodeValue : '0';
                    $TotalMercExonerada = $DOM->getElementsByTagName('TotalMercExonerada')->item(0) ? $DOM->getElementsByTagName('TotalMercExonerada')->item(0)->nodeValue : '0';
                    $TotalGravado = $DOM->getElementsByTagName('TotalGravado')->item(0) ? $DOM->getElementsByTagName('TotalGravado')->item(0)->nodeValue : '0';
                    $TotalExento = $DOM->getElementsByTagName('TotalExento')->item(0) ? $DOM->getElementsByTagName('TotalExento')->item(0)->nodeValue : '0';
                    $TotalExonerado = $DOM->getElementsByTagName('TotalExonerado')->item(0) ? $DOM->getElementsByTagName('TotalExonerado')->item(0)->nodeValue : '0';
                    $TotalVenta = $DOM->getElementsByTagName('TotalVenta')->item(0) ? $DOM->getElementsByTagName('TotalVenta')->item(0)->nodeValue : '0';
                    $TotalDescuentos = $DOM->getElementsByTagName('TotalDescuentos')->item(0) ? $DOM->getElementsByTagName('TotalDescuentos')->item(0)->nodeValue : '0';
                    $TotalVentaNeta = $DOM->getElementsByTagName('TotalVentaNeta')->item(0) ? $DOM->getElementsByTagName('TotalVentaNeta')->item(0)->nodeValue : '0';
                    $TotalImpuesto = $DOM->getElementsByTagName('TotalImpuesto')->item(0) ? $DOM->getElementsByTagName('TotalImpuesto')->item(0)->nodeValue : '0';
                    $TotalComprobante = $DOM->getElementsByTagName('TotalComprobante')->item(0) ? $DOM->getElementsByTagName('TotalComprobante')->item(0)->nodeValue : '0';
                    $identificacion = $DOM->getElementsByTagName('Receptor')->item(0) ? $DOM->getElementsByTagName('Numero')->item(1)->nodeValue : 'N/A';
                    $NombreCompleto = $DOM->getElementsByTagName('Receptor')->item(0) ? $DOM->getElementsByTagName('Nombre')->item(1)->nodeValue : 'Cliente de paso';
                    $FechaFactura = $DOM->getElementsByTagName('FechaEmision')->item(0) ? $DOM->getElementsByTagName('FechaEmision')->item(0)->nodeValue : '0';
                    $codigoMoneda = $DOM->getElementsByTagName('CodigoMoneda')->item(0) ? $DOM->getElementsByTagName('CodigoMoneda')->item(0)->nodeValue : '0';
                    $tipoCambio = $DOM->getElementsByTagName('TipoCambio')->item(0) ? $DOM->getElementsByTagName('TipoCambio')->item(0)->nodeValue : '0';
                    $consecutivo = $DOM->getElementsByTagName('NumeroConsecutivo')->item(0) ? $DOM->getElementsByTagName('NumeroConsecutivo')->item(0)->nodeValue : '0';
                    $creditoCompra = $DOM->getElementsByTagName('PlazoCredito')->item(0) ? $DOM->getElementsByTagName('PlazoCredito')->item(0)->nodeValue : '';
                    for ($x = 0; $x <= $DOM->getElementsByTagName('Tarifa')->length; $x++) {
                        $tipoTarifa = $DOM->getElementsByTagName('Tarifa')->item($x) ? $DOM->getElementsByTagName('Tarifa')->item($x)->nodeValue : '0';
                        switch ($tipoTarifa) {
                            case "0":
                                $tarifa0 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                                break;
                            case "1":
                                $tarifa1 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                                break;
                            case "2":
                                $tarifa2 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                                break;
                            case "4":
                                $tarifa4 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                                break;
                            case "13":
                                $tarifa13 += $DOM->getElementsByTagName('Monto')->item($x) ? $DOM->getElementsByTagName('Monto')->item($x)->nodeValue : '0';
                                // dd($tarifa13);  
                                break;
                        }
                    }
                } else {
                    $error = libxml_get_errors()[0];
                    $count = 0;
                    $msj = '';
                    foreach ($error as $e) {
                        if ($count == 3) {
                            $msj = $e;
                        }
                        $count++;
                    }
                    $this->session->set_flashdata('error', $msj);
                }
                if ($is_cn) {

                    $res =
                        [
                            'id' => $id,
                            'identificacion' => $identificacion,
                            'NombreCompleto' => $NombreCompleto,
                            'FechaFactura' => $FechaFactura,
                            'codigoMoneda' => $codigoMoneda,
                            'tipoCambio' => $tipoCambio,
                            'consecutivo' => $consecutivo,
                            'TotalServGravados' => -$TotalServGravados,
                            'TotalServExentos' => -$TotalServExentos,
                            'TotalMercanciasGravadas' => -$TotalMercanciasGravadas,
                            'TotalMercanciasExentas' => -$TotalMercanciasExentas,
                            'TotalMercExonerada' => -$TotalMercExonerada,
                            'TotalServExonerado' => -$TotalServExonerado,
                            'TotalGravado' => -$TotalGravado,
                            'TotalExento' => -$TotalExento,
                            'TotalExonerado' => -$TotalExonerado,
                            'TotalVenta' => -$TotalVenta,
                            'TotalDescuentos' => -$TotalDescuentos,
                            'TotalVentaNeta' => -$TotalVentaNeta,
                            'TotalImpuesto' => -$TotalImpuesto,
                            'TotalComprobante' => -$TotalComprobante,
                            'tarifa0' => -$tarifa0,
                            'tarifa1' => -$tarifa1,
                            'tarifa2' => -$tarifa2,
                            'tarifa4' => -$tarifa4,
                            'tarifa13' => -$tarifa13,
                            'creditoCompra' => $creditoCompra,
                            'tipo' => 'Nota de credito'
                        ];
                    array_push($resultados, $res);
                }
                $res =
                    [
                        'id' => $id,
                        'identificacion' => $identificacion,
                        'NombreCompleto' => $NombreCompleto,
                        'FechaFactura' => $FechaFactura,
                        'codigoMoneda' => $codigoMoneda,
                        'tipoCambio' => $tipoCambio,
                        'consecutivo' => $consecutivo,
                        'TotalServGravados' => $TotalServGravados,
                        'TotalServExentos' => $TotalServExentos,
                        'TotalMercanciasGravadas' => $TotalMercanciasGravadas,
                        'TotalMercanciasExentas' => $TotalMercanciasExentas,
                        'TotalMercExonerada' => $TotalMercExonerada,
                        'TotalServExonerado' => $TotalServExonerado,
                        'TotalGravado' => $TotalGravado,
                        'TotalExento' => $TotalExento,
                        'TotalExonerado' => $TotalExonerado,
                        'TotalVenta' => $TotalVenta,
                        'TotalDescuentos' => $TotalDescuentos,
                        'TotalVentaNeta' => $TotalVentaNeta,
                        'TotalImpuesto' => $TotalImpuesto,
                        'TotalComprobante' => $TotalComprobante,
                        'tarifa0' => $tarifa0,
                        'tarifa1' => $tarifa1,
                        'tarifa2' => $tarifa2,
                        'tarifa4' => $tarifa4,
                        'tarifa13' => $tarifa13,
                        'creditoCompra' => $creditoCompra,
                    ];
                if ($NombreCompleto == "Cliente de paso") {
                    $res['tipo'] = "Tiquete electronico";
                } else {
                    $res['tipo'] = "Factura electronica";
                }

                array_push($resultados, $res);
                $n++;
            }
        }
        $table =  [
            'recordsTotal' => count($hacienda),
            'draw' =>  0,
            'recordsTotal' => count($hacienda),
            'recordsFiltered' =>  count($hacienda),
            'data' => $resultados
        ];
        echo json_encode($table);
    }

    function close_register($register_open_time = null, $user_id = null)
    {
        $register_open_time = $this->input->get('date');
        $user_id = $this->input->get('user_id');
        $register_data = $this->reports_model->getCloseRegister($user_id, $register_open_time);
        $register_close_time = $register_data->closed_at;
        if ($register_close_time == NULL) {
            $this->session->set_flashdata('error', 'La caja aun no se ha cerrado');
            return redirect('reports/registers');
        }
        $this->load->model('pos_model');
        $this->load->library('form_validation');
        $this->data['printer'] = $this->site->getPrinterByID($this->session->userdata('printer_default'));
        $this->form_validation->set_rules('total_cash', lang("total_cash"), 'trim|required|numeric');
        $this->form_validation->set_rules('total_cheques', lang("total_cheques"), 'trim|required|numeric');
        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                //  $user_register = $user_id ? $this->pos_model->registerData($user_id) : NULL;
                // $rid = $user_register ? $user_register->id : $this->session->userdata('register_id');
                // $register_open_time = $user_register ? $user_register->date : $this->session->userdata('register_open_time');
                // $user_id = $user_register ? $user_register->user_id : $this->session->userdata('user_id');
                $cash_in_hand = $register_data->cash_in_hand;
                $ccsales = $this->reports_model->getRegisterCCSales($register_open_time, $register_close_time, $user_id);
                $Totaldepositos = $this->reports_model->getDepositos($register_open_time, $register_close_time, $user_id);
                $cashsales = $this->reports_model->getRegisterCashSales($register_open_time, $register_close_time, $user_id);
                $expenses = $this->reports_model->getRegisterExpenses($register_open_time, $register_close_time, $user_id);
                $chsales = $this->reports_model->getRegisterChSales($register_open_time, $register_close_time, $user_id);
                $notecredits = $this->reports_model->getRegisterNCSales($register_open_time, $register_close_time, $user_id);
                $gravadas1 = $this->reports_model->getRegisterSalesGrav1($register_open_time, $register_close_time, $user_id);
                $gravadas2 = $this->reports_model->getRegisterSalesGrav2($register_open_time, $register_close_time, $user_id);
                $gravadas3 = $this->reports_model->getRegisterSalesGrav3($register_open_time, $register_close_time, $user_id);
                $gravadas4 = $this->reports_model->getRegisterSalesGrav4($register_open_time, $register_close_time, $user_id);
                $gravadas5 = $this->reports_model->getRegisterSalesGrav5($register_open_time, $register_close_time, $user_id);
                $gravadas6 = $this->reports_model->getRegisterSalesGrav6($register_open_time, $register_close_time, $user_id);
                $gravadas7 = $this->reports_model->getRegisterSalesGrav7($register_open_time, $register_close_time, $user_id);
                $gravadas8 = $this->reports_model->getRegisterSalesGrav8($register_open_time, $register_close_time, $user_id);
                $gravadas9 = $this->reports_model->getRegisterSalesGrav9($register_open_time, $register_close_time, $user_id);
                $gravadas10 = $this->reports_model->getRegisterSalesGrav10($register_open_time, $register_close_time, $user_id);
                $gravadas11 = $this->reports_model->getRegisterSalesGrav11($register_open_time, $register_close_time, $user_id);
                $gravadas12 = $this->reports_model->getRegisterSalesGrav12($register_open_time, $register_close_time, $user_id);
                $gravadas13 = $this->reports_model->getRegisterSalesGrav13($register_open_time, $register_close_time, $user_id);
                $exentas = $this->reports_model->getRegisterSalesExce($register_open_time, $register_close_time, $user_id);
                $creditos = $this->reports_model->getRegisterSalesCredit($register_open_time, $register_close_time, $user_id);
                $ccsalesApart = $this->reports_model->getRegisterCCSalesApart($register_open_time, $register_close_time, $user_id);
                $cashsalesApart = $this->reports_model->getRegisterCashSalesApart($register_open_time, $register_close_time, $user_id);
                $cashsalesTips = $this->reports_model->getRegisterTips($register_open_time, $register_close_time, $user_id);
                $total_cash = ($cashsales->total ? ($cashsales->total + $cash_in_hand) : $cash_in_hand);
                $total_cash = $total_cash + (isset($cashsalesApart->total) ? $cashsalesApart->total : 0);
                $total_cash = $total_cash + (isset($Totaldepositos->total) ? $Totaldepositos->total : 0);
                $total_cash -= ($expenses->total ? $expenses->total : 0);
                $Totalccsales = $ccsales->total ? $ccsales->total : 0;
                $Totalccsales = $Totalccsales + (isset($cashsalesApart->total) ? $cashsalesApart->total : 0);
            } else {
                // $rid = $this->session->userdata('register_id');
                // $user_id = $this->session->userdata('user_id');
                // $register_open_time = $this->session->userdata('register_open_time');
                $cash_in_hand = $register_data->cash_in_hand;
                $ccsales = $this->reports_model->getRegisterCCSales($register_open_time, $register_close_time, $user_id);
                $Totaldepositos = $this->reports_model->getDepositos($register_open_time, $register_close_time, $user_id);
                $cashsales = $this->reports_model->getRegisterCashSales($register_open_time, $register_close_time, $user_id);
                $expenses = $this->reports_model->getRegisterExpenses($register_open_time, $register_close_time, $user_id);
                $chsales = $this->reports_model->getRegisterChSales($register_open_time, $register_close_time, $user_id);
                $notecredits = $this->reports_model->getRegisterNCSales($register_open_time, $register_close_time, $user_id);
                $gravadas1 = $this->reports_model->getRegisterSalesGrav1($register_open_time, $register_close_time, $user_id);
                $gravadas2 = $this->reports_model->getRegisterSalesGrav2($register_open_time, $register_close_time, $user_id);
                $gravadas3 = $this->reports_model->getRegisterSalesGrav3($register_open_time, $register_close_time, $user_id);
                $gravadas4 = $this->reports_model->getRegisterSalesGrav4($register_open_time, $register_close_time, $user_id);
                $gravadas5 = $this->reports_model->getRegisterSalesGrav5($register_open_time, $register_close_time, $user_id);
                $gravadas6 = $this->reports_model->getRegisterSalesGrav6($register_open_time, $register_close_time, $user_id);
                $gravadas7 = $this->reports_model->getRegisterSalesGrav7($register_open_time, $register_close_time, $user_id);
                $gravadas8 = $this->reports_model->getRegisterSalesGrav8($register_open_time, $register_close_time, $user_id);
                $gravadas9 = $this->reports_model->getRegisterSalesGrav9($register_open_time, $register_close_time, $user_id);
                $gravadas10 = $this->reports_model->getRegisterSalesGrav10($register_open_time, $register_close_time, $user_id);
                $gravadas11 = $this->reports_model->getRegisterSalesGrav11($register_open_time, $register_close_time, $user_id);
                $gravadas12 = $this->reports_model->getRegisterSalesGrav12($register_open_time, $register_close_time, $user_id);
                $gravadas13 = $this->reports_model->getRegisterSalesGrav13($register_open_time, $register_close_time, $user_id);
                $exentas = $this->reports_model->getRegisterSalesExce($register_open_time, $register_close_time, $user_id);
                $creditos = $this->reports_model->getRegisterSalesCredit($register_open_time, $register_close_time, $user_id);
                $ccsalesApart = $this->reports_model->getRegisterCCSalesApart($register_open_time, $register_close_time, $user_id);
                $cashsalesApart = $this->reports_model->getRegisterCashSalesApart($register_open_time, $register_close_time, $user_id);
                $cashsalesTips = $this->reports_model->getRegisterTips($register_open_time, $register_close_time, $user_id);
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
                'total_cash_submitted' => $register_data->total_cash_submitted,
                'total_cc' => $Totalccsales,
                'total_cc_submitted' => $register_data->total_cc_submitted,
                'total_cc_slips_submitted' => $register_data->total_cc_slips_submitted,
                'total_cheques' => $chsales->total_cheques,
                'total_cheques_submitted' => $register_data->total_cheques_submitted,
                'note' => $register_data->note,
                'cash_in_hand' => $register_data->cash_in_hand,
                'cash_sale' => $cashsales->total,
                'cc_sale' => $ccsales->total,
                'TotalDepositos' => $Totaldepositos->total,
                'total_sales' => ($ccsales->total + $cashsales->total) - $ncredits,
                'total_credits_sales' => $creditos->total,
                'grand_total_sales' => ($creditos->total + $ccsales->total + $cashsales->total) - $ncredits,
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
                'tot_exentas_gravadas' => $register_data->tot_exentas_gravadas,
                'total_notecredits' => @$notecredits->total ? @$notecredits->total : "0.00",
                'total_expenses' => $expenses->total ? $expenses->total : "0.00",
                'status' => 'close',
                'transfer_opened_bills' => $register_data->transfer_opened_bills,
                'closed_at' => $register_close_time,
                'closed_by' => $user_id,
                'cashsalesApart' => isset($cashsalesApart->total) ? $cashsalesApart->total : 0,
                'ccsalesApart' => isset($ccsalesApart->total) ? $ccsalesApart->total : 0,
                'ccsalesTips' => isset($cashsalesTips->total) ? $cashsalesTips->total : 0,
            );
        } elseif ($this->input->post('close_register')) {
            $this->session->set_flashdata('error', (validation_errors() ? validation_errors() : $this->session->flashdata('error')));
            redirect("reports/registers");
        }

        if ($this->form_validation->run() == true) {
            $this->print_register(null, $data);
            $this->session->unset_userdata('register_id');
            $this->session->unset_userdata('cash_in_hand');
            $this->session->unset_userdata('register_open_time');
            $this->session->set_flashdata('message', 'Impresión realizada con exito');

            redirect("reports/registers");
        } else {

            $credit = $this->reports_model->getRegisterNCSales($register_open_time, $register_close_time, $user_id);
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['ccsales'] = $this->reports_model->getRegisterCCSales($register_open_time, $register_close_time, $user_id);
            $this->data['cashsales'] = $this->reports_model->getRegisterCashSales($register_open_time, $register_close_time, $user_id);
            $this->data['chsales'] = $this->reports_model->getRegisterChSales($register_open_time, $register_close_time, $user_id);
            $this->data['ccsalesApart'] = $this->reports_model->getRegisterCCSalesApart($register_open_time, $register_close_time, $user_id);
            $this->data['ccsalesTips'] = $this->reports_model->getRegisterTips($register_open_time, $register_close_time, $user_id);
            $this->data['cashsalesApart'] = $this->reports_model->getRegisterCashSalesApart($register_open_time, $register_close_time, $user_id);
            $this->data['other_sales'] = $this->reports_model->getRegisterOtherSales($register_open_time, $register_close_time, $user_id);
            $this->data['gcsales'] = $this->reports_model->getRegisterGCSales($register_open_time, $register_close_time, $user_id);
            $this->data['stripesales'] = $this->reports_model->getRegisterStripeSales($register_open_time, $register_close_time, $user_id);
            $this->data['totalsales'] = ($this->data['cashsales']->total + $this->data['ccsales']->total) - (isset($credit->total) ?$credit->total:0);
            $this->data['expenses'] = $this->reports_model->getRegisterExpenses($register_open_time, $register_close_time, $user_id);
            $this->data['users'] = $this->tec->getUsers($user_id);
            $this->data['suspended_bills'] = $this->reports_model->getSuspendedsales($user_id);
            $this->data['notecredits'] = $credit;
            $this->data['gravadas1'] = $this->reports_model->getRegisterSalesGrav1($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas2'] = $this->reports_model->getRegisterSalesGrav2($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas3'] = $this->reports_model->getRegisterSalesGrav3($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas4'] = $this->reports_model->getRegisterSalesGrav4($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas5'] = $this->reports_model->getRegisterSalesGrav5($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas6'] = $this->reports_model->getRegisterSalesGrav6($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas7'] = $this->reports_model->getRegisterSalesGrav7($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas8'] = $this->reports_model->getRegisterSalesGrav8($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas9'] = $this->reports_model->getRegisterSalesGrav9($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas10'] = $this->reports_model->getRegisterSalesGrav10($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas11'] = $this->reports_model->getRegisterSalesGrav11($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas12'] = $this->reports_model->getRegisterSalesGrav12($register_open_time, $register_close_time, $user_id);
            $this->data['gravadas13'] = $this->reports_model->getRegisterSalesGrav13($register_open_time, $register_close_time, $user_id);
            $this->data['exentas'] = $this->reports_model->getRegisterSalesExce($register_open_time, $register_close_time, $user_id);
            $this->data['creditos'] = $this->reports_model->getRegisterSalesCredit($register_open_time, $register_close_time, $user_id);
            $this->data['user_id'] = $user_id;
            $this->data['cash_in_hand'] = $register_data->cash_in_hand;
            $this->data['is_report'] = "S";
            $this->data['register_open_time'] = $register_open_time;
            $this->data['user_id'] = $user_id;
            $this->data['Totaldepositos'] = $this->reports_model->getDepositos($register_open_time, $register_close_time, $user_id);
            // dd($user_id."/".$register_open_time);

            $this->load->view($this->theme . 'pos/close_register', $this->data);
        }
    }

    function print_register($re = NULL, $datos = null)
    {
        $this->load->model('pos_model');
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
                    array_push(
                        $reg_totals,
                        (object) array('label' => 'Diferencia en efectivo', 'value' => $this->tec->formatMoney($datos['total_cash_submitted'] ? ($datos['total_cash_submitted'] - $datos['total_cash']) : '0.00'))
                    );
                }
                array_push(
                    $reg_totals,
                    (object) array('label' => lang('total_cc_slips'), 'value' => $this->tec->formatMoney($datos['total_cc_submitted'] ? $datos['total_cc_submitted'] : '0.00'))
                );
                if ($datos['total_cc_submitted'] - $datos['cc_sale'] < 0) {
                    array_push(
                        $reg_totals,
                        (object) array('label' => 'Diferencia en tarjeta', 'value' => $this->tec->formatMoney($datos['total_cash_submitted'] ? ($datos['total_cc_submitted'] - $datos['cc_sale']) : '0.00'))
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
        } elseif ($re == 2) { } else {
            $store = $this->site->getStoreByID($this->session->userdata('store_id'));
            $printer = $this->site->getPrinterByID($this->session->userdata('printer_default'));
            if ($printer->type != "web") {
                $this->load->library('escpos');
                $this->escpos->load($printer);
                $this->escpos->print_data($data, $store);
            }
        }
    }

    public function inventory_adjustment()
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = $this->lang->line("inventory_adjustment");
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('inventory_adjustment')));
        $meta = array('page_title' => lang('inventory_adjustment'), 'bc' => $bc);
        $this->page_construct('reports/inventory_adjustment', $this->data, $meta);
    }

    public function get_inventory_adjustment()
    {

        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        $end_date = $this->input->get('start_date') ? $this->input->get('end_date') : NULL;
        $user = $this->input->get('user') ? $this->input->get('user') : NULL;

        $this->load->library('datatables');
        $mov_inv = $this->db->dbprefix('mov_inventario');
        $this->datatables
            ->select($mov_inv . ".id_movimiento AS id_movimiento, 
            CASE 
                WHEN " . $mov_inv . ".tipo_mov = 1 
                    THEN 'Aumento en inventario'
                WHEN " . $mov_inv . ".tipo_mov = 0
                    THEN 'Disminucion en inventario'
                WHEN " . $mov_inv . ".tipo_mov = 3
                    THEN 'Cambio de Precio'
            END AS tipo_mov, " . $mov_inv . ".descripcion_mov AS descripcion_mov,
            " . $mov_inv . ".quantity_mov AS quantity_mov," . $mov_inv . ".qty_fracc_mov AS qty_fracc_mov,
            CASE
            WHEN p.type != 'combo'
              THEN p.name
            WHEN p.type = 'combo'
              THEN (SELECT CONCAT('COMBO(',GROUP_CONCAT(NAME),')') FROM tec_products WHERE CODE IN(SELECT item_code FROM tec_combo_items WHERE product_id = p.id) )
            ELSE
              CONCAT('No se encuentra producto id [', " . $mov_inv . ".id_product,']')
            END AS product_name, u.username AS user, " . $mov_inv . ".precio_ant AS precio_ant, " . $mov_inv . ".precio_act AS precio_act ,
            " . $mov_inv . ".fecha_mov AS fecha_mov")
            ->from('mov_inventario')
            ->join($this->db->dbprefix('products') . ' p', 'p.id = ' . $mov_inv . '.id_product', 'left')
            ->join($this->db->dbprefix('users') . ' u', 'u.id = ' . $mov_inv . '.id_usuario', 'left');


        if ($start_date) {
            $this->datatables->where($mov_inv . '.fecha_mov >=', $start_date);
        }
        if ($end_date) {
            $this->datatables->where($mov_inv . '.fecha_mov <=', $end_date);
        }

        echo $this->datatables->generate();
    }

    public function credit_shipping()
    {
        $this->reports_model->update_deudas();
        if ($this->input->post('amount-paid')) {
            $paid_amount = $this->input->post('amount-paid');
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') . ' 00:00' : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') . ' 23:59' : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $shipping = $this->input->post('shipping_method') ? $this->input->post('shipping_method') : NULL;
            $sale_checked = $this->input->post('sales_id') ? $this->input->post('sales_id') : NULL;
            $parts = array();
            $where = "";
            if ($start_date && $end_date) {
                $where .= " AND `date` BETWEEN '" . $start_date . "' AND '" . $end_date . "'";
            }
            if ($this->session->userdata('store_id')) {
                $where .= " AND store_id =" . $this->session->userdata('store_id');
            }
            if ($shipping) {
                $where .= " AND id_shipping_method =" . $shipping;
            }
            if ($sale_checked) {
                $sale_checked =  substr($sale_checked, 0, -1);
                $parts = explode(';', $sale_checked);
                $where .= " AND id IN(" . str_replace(']', '', str_replace('[', '', json_encode($parts))) . ")";
            }
            $result = $this->db->query("SELECT id, date,customer_id, customer_name, total, total_tax, total_discount, grand_total, paid, (grand_total-paid) as balance, status
            FROM " . $this->db->dbprefix('sales') . " WHERE status <>  'paid' " . $where . " ORDER BY `date` DESC");
            if ($result->num_rows() > 0) {
                foreach ($result->result() as $row) {
                    if ($paid_amount >= $row->balance) {
                        $p_amount = $row->balance;
                    } else {
                        $p_amount = $paid_amount;
                    }
                    $date = date('Y-m-d H:i:s');
                    $payment = array(
                        'date' => $date,
                        'sale_id' => $row->id,
                        'customer_id' => $row->customer_id,
                        'reference' =>  $this->input->post('reference_note')? $this->input->post('reference_note'):($this->input->post('deposito_ref')? $this->input->post('deposito_ref'):''),
                        'amount' => $p_amount,
                        'paid_by' => $this->input->post('paid_by'),
                        'cheque_no' => $this->input->post('cheque_no'),
                        'gc_no' => $this->input->post('gift_card_no'),
                        'cc_no' => $this->input->post('pcc_no'),
                        'cc_holder' => $this->input->post('pcc_holder'),
                        'cc_month' => $this->input->post('pcc_month'),
                        'cc_year' => $this->input->post('pcc_year'),
                        'cc_type' => $this->input->post('pcc_type'),
                        'note' => $this->input->post('note'),
                        'created_by' => $this->session->userdata('user_id'),
                        'store_id' => $this->session->userdata('store_id'),
                    );
                    $this->sales_model->addPayment($payment);
                    $paid_amount = $paid_amount - $p_amount;
                }
            }
        }

        if ($this->input->post('shipping_method')) {
            $start_date = $this->input->post('start_date') ? $this->input->post('start_date') . ' 00:00' : NULL;
            $end_date = $this->input->post('end_date') ? $this->input->post('end_date') . ' 23:59' : NULL;
            $user = $this->input->post('user') ? $this->input->post('user') : NULL;
            $shipping = $this->input->post('shipping_method') ? $this->input->post('shipping_method') : NULL;
            $customers = $this->reports_model->getCustomerByShipping($shipping);
            $Arraycustomers = array();
            foreach ($customers as $cus) {
                array_push($Arraycustomers, $cus->customer_id);
            }
            $this->data['total_sales'] = $this->reports_model->getTotalCustomerSales($Arraycustomers, $user, $start_date, $end_date, $shipping);
        }
        // $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        // $this->data['customers'] = $this->reports_model->getAllCustomers();
        $this->data['shipping'] = $this->reports_model->getAllShipping();
        // $this->data['users'] = $this->reports_model->getAllStaff();
        $this->data['page_title'] = "Informe de credito en envios";
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => "Informe de credito en envios"));
        $meta = array('page_title' => "Informe de credito en envios", 'bc' => $bc);

        $this->page_construct('reports/shipping_credits', $this->data, $meta);
    }


    public function get_credit_shipping()
    {
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') . ' 00:00' : NULL;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') . ' 23:59' : NULL;
        $shipping = $this->input->get('shipping_method') ? $this->input->get('shipping_method') : NULL;
        // $this->db->save_queries = TRUE;
        $this->load->library('datatables');
        $this->datatables
            ->select("id, date, customer_name, total, total_tax, total_discount, grand_total, paid, (grand_total-paid) as balance, status, id_shipping_method")
            ->from('sales')
            ->where('status <>', 'paid')
            ->where('id_shipping_method IS NOT NULL', null, false);
        $this->datatables->add_column(
            "Actions",
            "<label class='switch'><input type='checkbox' id='$1' onclick='checkbox(this)'><span class='slider round'></span></label>",
            "id"
        );
        if ($this->session->userdata('store_id')) {
            $this->datatables->where('store_id', $this->session->userdata('store_id'));
        }
        $this->datatables->unset_column('id');
        if ($start_date) {
            $this->datatables->where('date >=', $start_date);
        }
        if ($end_date) {
            $this->datatables->where('date <=', $end_date);
        }
        if ($shipping) {
            $this->datatables->where('id_shipping_method =', $shipping);
        }
        echo $this->datatables->generate();
        // dd($this->db->last_query());
    }

    function sale_fe()
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = $this->lang->line("model_d104");
        // $this->data['customers'] = $this->reports_model->getAllCustomers();
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('model_d104')));
        $meta = array('page_title' => lang('model_d104'), 'bc' => $bc);
        $this->page_construct('reports/sale_fe', $this->data, $meta);
    }

    function get_sale_fe()
    {
        // $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : NULL;
        // $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : NULL;
        // $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $resultados = array();
        $n = 0;
        $facturaEle = $this->reports_model->get_all_fe();
        if($facturaEle){
        foreach($facturaEle as $fe){ 
            $res =
                [
                    'name' => $fe['name'],
                    'tax_0' => $fe['tax_0'],
                    'tax_1' => $fe['tax_1'],
                    'tax_2' => $fe['tax_2'],
                    'tax_4' => $fe['tax_4'],
                    'tax_13' => $fe['tax_13'],
                    'subtotal' => $fe['subtotal'],
                    'exonerado' => $fe['exonerado'],
                    'total' => $fe['total'],
                ];
            array_push($resultados, $res);
            $n++;
            }
        
        }
        $table =  [
            'recordsTotal' => count($facturaEle),
            'draw' =>  0,
            'recordsTotal' => count($facturaEle),
            'recordsFiltered' =>  count($facturaEle),
            'data' => $resultados
        ];
        echo json_encode($table);
    }

    function d151()
    {
        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = $this->lang->line("model_d151");
        // $this->data['customers'] = $this->reports_model->getAllCustomers();
        $bc = array(array('link' => '#', 'page' => lang('reports')), array('link' => '#', 'page' => lang('model_d151')));
        $meta = array('page_title' => lang('model_d151'), 'bc' => $bc);
        $this->page_construct('reports/model_d151', $this->data, $meta);
    }

    function get_d151()
    {
        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();
        $fecha = date('Y-m-d');
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : $fecha;
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : $fecha;
        // $customer = $this->input->get('customer') ? $this->input->get('customer') : NULL;
        $resultados = array();
        $n = 0;
        $facturaEle = $this->reports_model->get_d151($start_date,$end_date);
        // dd($facturaEle);
        if($facturaEle){
        foreach($facturaEle as $fe){ 
            $res =
                [
                    'cedula' => $fe['cedula'],
                    'nombre' => $fe['nombre'],
                    'subtotal' => $fe['subtotal'],
                    'CodigoRep' => $fe['CodigoRep'],
                    'Concepto' => $fe['Concepto']
                ];
            array_push($resultados, $res);
            $n++;
            }
        
        }
        $table =  [
            'recordsTotal' => $facturaEle?count($facturaEle):0,
            'draw' =>  0,
            'recordsTotal' => $facturaEle?count($facturaEle):0,
            'recordsFiltered' =>  $facturaEle?count($facturaEle):0,
            'data' => $resultados
        ];
        echo json_encode($table);
    }

}

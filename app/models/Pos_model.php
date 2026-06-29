<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Pos_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }
    public function getProductNames($term, $limit = 10, $sensibility = null) {
        $store_id = $this->session->userdata('store_id');
        $this->db->select("{$this->db->dbprefix('products')}.*, COALESCE(psq.quantity, 0) as quantity, COALESCE(psq.price, 0) as store_price, COALESCE(psq.qty_fracc, 0) as qty_fracc, 0 as esta_fraccionado, COALESCE(imp.id_impuesto, 0) as id_impuesto, COALESCE(imp.codigo_impuesto, 0) as codigo_impuesto, COALESCE(imp.codigo_tarifa, 0) as codigo_tarifa")
                ->join("( SELECT * from {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$store_id}) psq", 'products.id=psq.product_id', 'left')
                ->join("{$this->db->dbprefix('impuestos')} imp", 'products.id_tax=imp.id_impuesto', 'left');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->db->where("(name LIKE '%{$term}%' OR code LIKE '%{$term}%' OR  (name || ' (' || code || ')') LIKE '%{$term}%')");
        } else {
            if ($this->Settings->sensibility_search == 0) {
                $this->db->where("(name LIKE '%{$term}%' OR code LIKE '%{$term}%' OR  concat(name, ' (', code, ')') LIKE '%{$term}%')");
            } elseif ($this->Settings->sensibility_search == 1) {
                $this->db->where("(name LIKE '{$term}%' OR code LIKE '{$term}%' OR  concat(name, ' (', code, ')') LIKE '{$term}%')");
            } elseif ($this->Settings->sensibility_search == 2) {
                $this->db->where("(name LIKE '%{$term}' OR code LIKE '%{$term}' OR  concat(name, ' (', code, ')') LIKE '%{$term}')");
            } elseif ($this->Settings->sensibility_search == 3) {
                $this->db->where("(name LIKE '{$term}' OR code LIKE '{$term}' OR  concat(name, ' (', code, ')') LIKE '{$term}')");
            }
        }
        $this->db->group_by('products.id')->limit($limit);
        $q = $this->db->get('products');
              
        
        if ($q->num_rows() > 0) {

            foreach (($q->result()) as $row) {
                $row->store_price = $row->price;
                if ($row->tax_method == '0') {
                    $row->tax_method  = '1';
                    $row->store_price = invert_tax_price($row->store_price, $row->tax);
                    $row->price       = $row->store_price;
                }
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getProductPrice($term, $limit = 1) {
        $store_id = $this->session->userdata('store_id');
        $this->db->select("{$this->db->dbprefix('products')}.*, COALESCE(psq.quantity, 0) as quantity, COALESCE(psq.price, 0) as store_price, COALESCE(imp.id_impuesto, 0) as id_impuesto, COALESCE(imp.codigo_impuesto, 0) as codigo_impuesto, COALESCE(imp.codigo_tarifa, 0) as codigo_tarifa")
                ->join("( SELECT * from {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$store_id}) psq", 'products.id=psq.product_id', 'left')
                ->join("{$this->db->dbprefix('impuestos')} imp", 'products.id_tax=imp.id_impuesto', 'left');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->db->where("(name LIKE '%{$term}%' OR code LIKE '%{$term}%' OR  (name || ' (' || code || ')') LIKE '%{$term}%')");
        } else {
            $this->db->where("code LIKE '{$term}'");
        }
        $this->db->group_by('products.id')->limit($limit);
        $q = $this->db->get('products');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                if (isset($row->tax_method) && $row->tax_method == '0') {
                    $row->tax_method  = '1';
                    $row->store_price = invert_tax_price($row->store_price, $row->tax);
                    $row->price       = $row->store_price;
                }
                $data = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getTodaySales() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayCCSales() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cc_slips, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'CC');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayCashSales() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'cash');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayRefunds() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS returned', FALSE)
                ->join('return_sales', 'return_sales.id=payments.return_id', 'left')
                ->where('type', 'returned')->where('payments.date >', $date);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayExpenses() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( amount, 0 ) ) AS total', FALSE)
                ->where('date >', $date);

        $q = $this->db->get('expenses');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayCashRefunds() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS returned', FALSE)
                ->join('return_sales', 'return_sales.id=payments.return_id', 'left')
                ->where('type', 'returned')->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'cash');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayChSales() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)
                ->group_start()->where("{$this->db->dbprefix('payments')}.paid_by", 'Cheque')->or_where("{$this->db->dbprefix('payments')}.paid_by", 'cheque')->group_end();

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayOtherSales() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'other');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayGCSales() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'gift_card');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getTodayStripeSales() {
        $date = date('Y-m-d 00:00:00');
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'stripe');

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSales($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date);
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterPartiallyPaid($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as register_sales_credit, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where("{$this->db->dbprefix('sales')}.status", 'partial')
                ->where('payments.date >', $date);
        $this->db->where('payments.created_by', $user_id);
        $q = $this->db->get('payments');

        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesCredit($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
       # $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sales') . '.id) as sales_credit, SUM( COALESCE( grand_total - paid, 0 ) ) AS total, SUM( COALESCE( paid, 0 ) ) AS paid', FALSE)
                ->where("{$this->db->dbprefix('sales')}.status <>", 'paid')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sales');


        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav1($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '1')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav2($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }


        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '2')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav3($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
            //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '3')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

            //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav4($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
            //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '4')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav5($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '5')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav6($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
            //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '6')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav7($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
                //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '7')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

            //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav8($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '8')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
                if ($q->num_rows() > 0) {
                    return $q->row();
                }
                return false;
            }

            public function getRegisterSalesGrav9($date = NULL, $user_id = NULL) {
                if (!$date) {
                    $date = $this->session->userdata('register_open_time');
                }
                if (!$user_id) {
                    $user_id = $this->session->userdata('user_id');
                }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '9')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav10($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '10')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav11($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '11')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav12($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '12')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav13($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_13, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '13')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterTips($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_tips, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.product_code =', '9r091n4')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');
        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesExce($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax =', '0')
                ->where('sales.date >', $date);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');
        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCCSales($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COALESCE(SUM(COALESCE(
        CASE WHEN (pos_balance < 0) 
            THEN COALESCE(amount,0)
            ELSE COALESCE((amount - pos_balance),0) 
        END,0)),0) AS total', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'CC');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');

        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCashSales($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        // $this->db->save_queries = TRUE;
        $this->db->select('COALESCE(SUM(COALESCE(
        CASE WHEN (pos_balance < 0) 
            THEN COALESCE(amount,0)
            ELSE COALESCE((amount - pos_balance),0) 
        END,0)),0) AS total', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'cash');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        // dd($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getDepositos($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COALESCE(SUM(amount),0) AS total', FALSE)
                ->where('deposit.date >', $date);
        $this->db->where('deposit.created_by', $user_id);
        $q = $this->db->get('deposit');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRetiros($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COALESCE(SUM(total),0) AS total', FALSE)
            ->where('purchases.date >', $date);
        $this->db->where('purchases.created_by', $user_id);
        $q = $this->db->get('purchases');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCashSalesApart($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COALESCE(SUM(COALESCE(amount,0)),0) AS total', FALSE)
                ->join('layaway', 'layaway.id=payments_apartado.apartado_id', 'left')
                ->where('payments_apartado.date >', $date)->where("{$this->db->dbprefix('payments_apartado')}.paid_by", 'cash');
        $this->db->where('payments_apartado.created_by', $user_id);

        $q = $this->db->get('payments_apartado');

        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCCSalesApart($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }

        $this->db->select('COALESCE(SUM(COALESCE(amount,0)),0) AS total', FALSE)
                ->join('layaway', 'layaway.id=payments_apartado.apartado_id', 'left')
                ->where('payments_apartado.date >', $date)->where("{$this->db->dbprefix('payments_apartado')}.paid_by", 'CC');
        $this->db->where('payments_apartado.created_by', $user_id);

        $q = $this->db->get('payments_apartado');

        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterRefunds($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS returned', FALSE)
                ->join('return_sales', 'return_sales.id=payments.return_id', 'left')
                ->where('type', 'returned')->where('payments.date >', $date);
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCashRefunds($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS returned', FALSE)
                ->join('return_sales', 'return_sales.id=payments.return_id', 'left')
                ->where('type', 'returned')->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'cash');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterExpenses($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( amount, 0 ) ) AS total', FALSE)
                ->where('date >', $date);
        $this->db->where('created_by', $user_id);

        $q = $this->db->get('expenses');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterChSales($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)
                ->group_start()->where("{$this->db->dbprefix('payments')}.paid_by", 'Cheque')->or_where("{$this->db->dbprefix('payments')}.paid_by", 'cheque')->group_end();
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterOtherSales($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'other');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterGCSales($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'gift_card');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterStripeSales($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $date)->where("{$this->db->dbprefix('payments')}.paid_by", 'stripe');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterNCSales($date = NULL, $user_id = NULL) {
        if (!$date) {
            $date = $this->session->userdata('register_open_time');
        }

        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }

        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total', FALSE)
                ->where('note_credits.date >', $date)
                ->group_by('created_by');
        $this->db->where('note_credits.created_by', $user_id);

        $q = $this->db->get('note_credits');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function products_count($category_id) {
        if ($category_id) {
            $this->db->where('category_id', $category_id);
        }
        return $this->db->count_all_results('products');
    }

    public function fetch_products($category_id, $limit, $start) {
        $this->db->limit($limit, $start);
        if ($category_id) {
            $this->db->where('category_id', $category_id);
        }
        $this->db->order_by("code", "asc");
        $query = $this->db->get("products");

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
                if ($qq->num_rows() > 0) 
                {
                    $im = $qq->row();
                    $row->id_impuesto=$im->id_impuesto;
                    $row->codigo_impuesto=$im->codigo_impuesto;
                    $row->codigo_tarifa=$im->codigo_tarifa;
                }
                else
                {
                    $row->id_impuesto=0;
                    $row->codigo_impuesto=0;
                    $row->codigo_tarifa=0;                
                }

                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function registerData($user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $q = $this->db->get_where('registers', array('user_id' => $user_id, 'status' => 'open'), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function openRegister($data) {
        if ($this->db->insert('registers', $data)) {
            return true;
        }
        return FALSE;
    }

    public function getOpenRegisters() {
        $this->db->select("date, user_id, cash_in_hand, CONCAT(" . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . ".last_name, ' - ', " . $this->db->dbprefix('users') . ".email) as user", FALSE)
                ->join('users', 'users.id=pos_register.user_id', 'left');
        $q = $this->db->get_where('registers', array('status' => 'open'));
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function closeRegister($rid, $user_id, $data, $form = null) {
        if (!$rid) {
            $rid = $this->session->userdata('register_id');
        }
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        if ($data['transfer_opened_bills'] == -1) {
            $this->db->delete('suspended_sales', array('created_by' => $user_id));
        } elseif ($data['transfer_opened_bills'] != 0) {
            $this->db->update('suspended_sales', array('created_by' => $data['transfer_opened_bills']), array('created_by' => $user_id));
        }
        $this->db->update('registers', $data, array('id' => $rid, 'user_id' => $user_id));
        if ($this->db->update('registers', $data, array('id' => $rid, 'user_id' => $user_id))) {
            return true;
        }

        return FALSE;
    }

    public function getLastTodayCloseRegister($user_id) {
        $this->db->like('closed_at', date('Y-m-d'));
        $q = $this->db->get_where('registers', array('user_id' => $user_id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function DisableAuthOpen($user_id) {
        if ($this->db->update('users', array('auth_open' => '0'), array('id' => $user_id))) {
            return true;
        }
    }

    public function getAuthOpen($user_id) {
        $this->db->select('auth_open');
        $q = $this->db->get_where('users', array('id' => $user_id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getCustomerByID($id) {
        $q = $this->db->get_where('customers', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getActividadByID($id) {
        $q = $this->db->get_where('actividadeconomica', array('id_actividad' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }    

    public function getProductByCode($code) {
        $jpsq = "( SELECT product_id, quantity, price from {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$this->session->userdata('store_id')} ) AS PSQ";    

        $this->db->select("{$this->db->dbprefix('products')}.*, COALESCE(PSQ.quantity, 0) as quantity, COALESCE(PSQ.price, {$this->db->dbprefix('products')}.price) as store_price", FALSE)
                ->join($jpsq, 'PSQ.product_id=products.id', 'left');

        $q = $this->db->get_where('products', array('code' => $code), 1);

        if ($q->num_rows() > 0) 
        {
            $row = $q->row();
            if (isset($row->tax_method) and $row->tax_method == '0') {
                $row->tax_method = '1';
                if ($row->tax > 0) {
                    $invertir_impuesto = $row->store_price / (1 + ($row->tax / 100));
                    $row->store_price = number_format($invertir_impuesto, 4, '.', '');
                    $row->price = number_format($invertir_impuesto, 4, '.', '');
                }
            }

            $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $q = $this->db->get_where('impuestos', array('id_impuesto' => $row->id_tax), 1);
            if ($q->num_rows() > 0) 
            {
                $im = $q->row();
                $row->id_impuesto=$im->id_impuesto;
                $row->codigo_impuesto=$im->codigo_impuesto;
                $row->codigo_tarifa=$im->codigo_tarifa;
            }
            else
            {
                $row->id_impuesto=0;
                $row->codigo_impuesto=0;
                $row->codigo_tarifa=0;                
            }

            return $row;
        }
        return FALSE;
    }

    public function addSale($data, $items, $payment = array(), $did = NULL, $payment2 = array(), $payment3 = array(), $payment4 = array(), $otrostextos) {
        if ($this->db->insert('sales', $data)) {
            $sale_id = $this->db->insert_id();
            foreach ($items as $item) {
                unset($item["type"]);
                $item['sale_id'] = $sale_id;

                $editcantidad = false;
                $esta_fraccionado = $item['esta_fraccionado'];
                $quantity_edit = $item['quantity_edit'];
                $qty_fracc_edit = $item['qty_fracc_edit'];
                unset($item['quantity_edit']);
                unset($item['qty_fracc_edit']);
               # $this->db->save_queries = true;
                if ($this->db->insert('sale_items', $item)) {
                    if ($item['product_id'] > 0 && $product = $this->site->getProductByID($item['product_id'])) {
                        if ($product->type == 'standard') {
                            if ($this->Settings->enable_fractions == "1") {
                                if ($esta_fraccionado == "1") {
                                    $datosInventario = $this->getFraccion(array(
                                        'fracionCaja' => $product->caja_fraccionada,
                                        'CajasDisponibles' => $product->quantity,
                                        'FaccionesDisponibles' => $product->qty_fracc,
                                        'CantidadVendidas' => $item['quantity']));
                                    $this->db->update('product_store_qty', $datosInventario, array('product_id' => $product->id, 'store_id' => $data['store_id']));
                                    $editcantidad = true;
                                }
                            }
                            if (!$editcantidad) {
                                $this->db->update('product_store_qty', array('quantity' => ($product->quantity - $item['quantity'])), array('product_id' => $product->id, 'store_id' => $data['store_id']));
                            }
                        } elseif ($product->type == 'combo') {
                            $combo_items = $this->getComboItemsByPID($product->id);
                            foreach ($combo_items as $combo_item) {
                                $cpr = $this->site->getProductByID($combo_item->id);
                                if ($cpr->type == 'standard') {

                                    if ($this->Settings->enable_fractions == "1") {
                                        if ($esta_fraccionado == "1") {

                                            $datosInventario = $this->getFraccion(array(
                                                'fracionCaja' => $product->caja_fraccionada,
                                                'CajasDisponibles' => $product->quantity,
                                                'FaccionesDisponibles' => $product->qty_fracc,
                                                'CantidadVendidas' => $item['quantity']));

                                            $this->db->update('product_store_qty', $datosInventario, array('product_id' => $cpr->id, 'store_id' => $data['store_id']));

                                            $editcantidad = true;
                                        }
                                    }

                                    if (!$editcantidad) {
                                        $qty = $combo_item->qty * $item['quantity'];
                                        $this->db->update('product_store_qty', array('quantity' => ($cpr->quantity - $qty)), array('product_id' => $cpr->id, 'store_id' => $data['store_id']));
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($otrostextos) {
            $this->db->delete('sales_otros_textos', array('sale_id' => $sale_id));
                foreach ($otrostextos as $texto) {
                    $texto['sale_id'] = $sale_id;
                    $this->db->insert('sales_otros_textos', $texto);
                }
            }

            if ($did) {
                $this->db->delete('suspended_sales', array('id' => $did));
                $this->db->delete('suspended_items', array('suspend_id' => $did));
            }
            $msg = array();
            if (!empty($payment)) {
                if ($payment['amount'] > 0) {

                    if ($payment['paid_by'] == 'stripe') {
                        $card_info = array("number" => $payment['cc_no'], "exp_month" => $payment['cc_month'], "exp_year" => $payment['cc_year'], "cvc" => $payment['cc_cvv2'], 'type' => $payment['cc_type']);
                        $result = $this->stripe($payment['amount'], $card_info);
                        if (!isset($result['error']) && !empty($result['transaction_id'])) {
                            $payment['transaction_id'] = $result['transaction_id'];
                            $payment['date'] = $result['created_at'];
                            $payment['amount'] = $result['amount'];
                            $payment['currency'] = $result['currency'];
                            unset($payment['cc_cvv2']);
                            $payment['sale_id'] = $sale_id;
                            $this->db->insert('payments', $payment);
                        } else {
                            $this->db->update('sales', ['paid' => 0, 'status' => 'due'], ['id' => $sale_id]);
                            $msg[] = lang('payment_failed');
                            $msg[] = '<p class="text-danger">' . $result['code'] . ': ' . $result['message'] . '</p>';
                        }
                    } else {
                        if ($payment['paid_by'] == 'gift_card') {
                            $gc = $this->getGiftCardByNO($payment['gc_no']);
                            $this->db->update('gift_cards', array('balance' => ($gc->balance - $payment['amount'])), array('card_no' => $payment['gc_no']));
                        }
                        unset($payment['cc_cvv2']);
                        $payment['sale_id'] = $sale_id;
                        $this->db->insert('payments', $payment);
                    }
                }
            }

            if (!empty($payment2)) {
                if ($payment2['amount'] > 0) {
                    if ($payment2['paid_by'] == 'stripe') {
                        $card_info = array("number" => $payment2['cc_no'], "exp_month" => $payment2['cc_month'], "exp_year" => $payment2['cc_year'], "cvc" => $payment2['cc_cvv2'], 'type' => $payment2['cc_type']);
                        $result = $this->stripe($payment2['amount'], $card_info);
                        if (!isset($result['error']) && !empty($result['transaction_id'])) {
                            $payment2['transaction_id'] = $result['transaction_id'];
                            $payment2['date'] = $result['created_at'];
                            $payment2['amount'] = $result['amount'];
                            $payment2['currency'] = $result['currency'];
                            unset($payment2['cc_cvv2']);
                            $payment2['sale_id'] = $sale_id;
                            $this->db->insert('payments', $payment2);
                        } else {
                            $this->db->update('sales', ['paid' => 0, 'status' => 'due'], ['id' => $sale_id]);
                            $msg[] = lang('payment_failed');
                            $msg[] = '<p class="text-danger">' . $result['code'] . ': ' . $result['message'] . '</p>';
                        }
                    } else {
                        if ($payment2['paid_by'] == 'gift_card') {
                            $gc = $this->getGiftCardByNO($payment2['gc_no']);
                            $this->db->update('gift_cards', array('balance' => ($gc->balance - $payment2['amount'])), array('card_no' => $payment2['gc_no']));
                        }
                        unset($payment2['cc_cvv2']);
                        $payment2['sale_id'] = $sale_id;
                        $this->db->insert('payments', $payment2);
                    }
                }
            }

            if (!empty($payment3)) {
                if ($payment3['amount'] > 0) {
                    if ($payment3['paid_by'] == 'stripe') {
                        $card_info = array("number" => $payment3['cc_no'], "exp_month" => $payment3['cc_month'], "exp_year" => $payment3['cc_year'], "cvc" => $payment3['cc_cvv2'], 'type' => $payment3['cc_type']);
                        $result = $this->stripe($payment3['amount'], $card_info);
                        if (!isset($result['error']) && !empty($result['transaction_id'])) {
                            $payment3['transaction_id'] = $result['transaction_id'];
                            $payment3['date'] = $result['created_at'];
                            $payment3['amount'] = $result['amount'];
                            $payment3['currency'] = $result['currency'];
                            unset($payment3['cc_cvv2']);
                            $payment3['sale_id'] = $sale_id;
                            $this->db->insert('payments', $payment3);
                        } else {
                            $this->db->update('sales', ['paid' => 0, 'status' => 'due'], ['id' => $sale_id]);
                            $msg[] = lang('payment_failed');
                            $msg[] = '<p class="text-danger">' . $result['code'] . ': ' . $result['message'] . '</p>';
                        }
                    } else {
                        if ($payment3['paid_by'] == 'gift_card') {
                            $gc = $this->getGiftCardByNO($payment3['gc_no']);
                            $this->db->update('gift_cards', array('balance' => ($gc->balance - $payment3['amount'])), array('card_no' => $payment3['gc_no']));
                        }
                        unset($payment3['cc_cvv2']);
                        $payment3['sale_id'] = $sale_id;
                        $this->db->insert('payments', $payment3);
                    }
                }
            }

            if (!empty($payment4)) {
                if ($payment4['amount'] > 0) {
                    if ($payment4['paid_by'] == 'stripe') {
                        $card_info = array("number" => $payment4['cc_no'], "exp_month" => $payment4['cc_month'], "exp_year" => $payment4['cc_year'], "cvc" => $payment4['cc_cvv2'], 'type' => $payment4['cc_type']);
                        $result = $this->stripe($payment4['amount'], $card_info);
                        if (!isset($result['error']) && !empty($result['transaction_id'])) {
                            $payment4['transaction_id'] = $result['transaction_id'];
                            $payment4['date'] = $result['created_at'];
                            $payment4['amount'] = $result['amount'];
                            $payment4['currency'] = $result['currency'];
                            unset($payment4['cc_cvv2']);
                            $payment4['sale_id'] = $sale_id;
                            $this->db->insert('payments', $payment4);
                        } else {
                            $this->db->update('sales', ['paid' => 0, 'status' => 'due'], ['id' => $sale_id]);
                            $msg[] = lang('payment_failed');
                            $msg[] = '<p class="text-danger">' . $result['code'] . ': ' . $result['message'] . '</p>';
                        }
                    } else {
                        if ($payment4['paid_by'] == 'gift_card') {
                            $gc = $this->getGiftCardByNO($payment4['gc_no']);
                            $this->db->update('gift_cards', array('balance' => ($gc->balance - $payment4['amount'])), array('card_no' => $payment4['gc_no']));
                        }
                        unset($payment4['cc_cvv2']);
                        $payment4['sale_id'] = $sale_id;
                        $this->db->insert('payments', $payment4);
                    }
                }
            }
            return array('sale_id' => $sale_id, 'message' => $msg);
        }

        return false;
    }

    public function getFraccion($f) {
        $globalFraccionesDisponibles = ($f['CajasDisponibles'] * $f['fracionCaja']) + $f['FaccionesDisponibles'];
        $fraccionesrestantesInventario = $globalFraccionesDisponibles - $f['CantidadVendidas'];
        $cajasRestantes = $fraccionesrestantesInventario / $f['fracionCaja'];
        $cajasRestantes = explode('.', $cajasRestantes);
        $cajasRestantes = $cajasRestantes[0];
        $fracionesEnCajasRestantes = $cajasRestantes * $f['fracionCaja'];

        $fraccionesInventario = $fraccionesrestantesInventario - $fracionesEnCajasRestantes;
        return array('quantity' => $cajasRestantes, 'qty_fracc' => $fraccionesInventario);
    }

    public function addSaleApartado($data, $items, $payment = array(), $did = NULL, $otrostextos) {

        if ($this->db->insert('layaway', $data)) {
            $apartado_id = $this->db->insert_id();


            $this->db->delete('layaway_otros_textos', array('apartado_id' => $apartado_id));
            foreach ($otrostextos as $texto) {
                $texto['apartado_id'] = $apartado_id;
                $this->db->insert('layaway_otros_textos', $texto);
            }

            foreach ($items as $item) {
                $item["tax"] = str_replace("%","",$item["tax"]);
                unset($item["type"]);
                unset($item['quantity_edit']);
                unset($item['qty_fracc_edit']);
                unset($item['esta_fraccionado']);

                $item['apartado_id'] = $apartado_id;
                if ($this->db->insert('layaway_items', $item)) {
                    if ($item['product_id'] > 0 && $product = $this->site->getProductByID($item['product_id'])) {
                        if ($product->type == 'standard') {
                            $this->db->update('product_store_qty', array('quantity' => ($product->quantity - $item['quantity'])), array('product_id' => $product->id, 'store_id' => $data['store_id']));
                        } elseif ($product->type == 'combo') {
                            $combo_items = $this->getComboItemsByPID($product->id);
                            foreach ($combo_items as $combo_item) {
                                $cpr = $this->site->getProductByID($combo_item->id);
                                if ($cpr->type == 'standard') {
                                    $qty = $combo_item->qty * $item['quantity'];
                                    $this->db->update('product_store_qty', array('quantity' => ($cpr->quantity - $qty)), array('product_id' => $cpr->id, 'store_id' => $data['store_id']));
                                }
                            }
                        }
                    }
                }
            }

            $msg = array();
            if (!empty($payment)) {
                if ($payment['paid_by'] == 'stripe') {
                    $card_info = array("number" => $payment['cc_no'], "exp_month" => $payment['cc_month'], "exp_year" => $payment['cc_year'], "cvc" => $payment['cc_cvv2'], 'type' => $payment['cc_type']);
                    $result = $this->stripe($payment['amount'], $card_info);
                    if (!isset($result['error']) && !empty($result['transaction_id'])) {
                        $payment['transaction_id'] = $result['transaction_id'];
                        $payment['date'] = $result['created_at'];
                        $payment['amount'] = $result['amount'];
                        $payment['currency'] = $result['currency'];
                        unset($payment['cc_cvv2']);
                        $payment['apartado_id'] = $apartado_id;
                        $this->db->insert('payments_apartado', $payment);
                    } else {
                        $this->db->update('layaway', ['paid' => 0, 'status' => 'due'], ['id' => $apartado_id]);
                        $msg[] = lang('payment_failed');
                        $msg[] = '<p class="text-danger">' . $result['code'] . ': ' . $result['message'] . '</p>';
                    }
                } else {
                    if ($payment['paid_by'] == 'gift_card') {
                        $gc = $this->getGiftCardByNO($payment['gc_no']);
                        $this->db->update('gift_cards', array('balance' => ($gc->balance - $payment['amount'])), array('card_no' => $payment['gc_no']));
                    }
                    unset($payment['cc_cvv2']);
                    $payment['apartado_id'] = $apartado_id;
                    $this->db->insert('payments_apartado', $payment);
                }
            }

            return array('apartado_id' => $apartado_id, 'message' => $msg);
        }

        return false;
    }

    public function impresoComanda($id, $qty_enviado){
        $this->db->update('suspended_items', array('enviado_cocina' => 1, 'qty_enviado' => $qty_enviado), array('id' => $id));
    }

    public function TransformarApartadoSales($data, $items, $apa, $otrostextos) {
        $apartado_id = $apa;
        $paid = 0;
        if ($this->db->insert('sales', $data)) {
            $sales_id = $this->db->insert_id();

            $this->db->delete('sales_otros_textos', array('sale_id' => $sales_id));
            foreach ($otrostextos as $texto) {
                $texto['sale_id'] = $sales_id;
                $this->db->insert('sales_otros_textos', $texto);
            }

            foreach ($items as $item) {
                unset($item["type"]);
                unset($item["quantity_edit"]);
                unset($item["qty_fracc_edit"]);
                $item['sale_id'] = $sales_id;
                $this->db->insert('sale_items', $item);
            }

            $pagos = $this->db->get_where('payments_apartado', array('apartado_id' => $apartado_id));
            foreach (($pagos->result()) as $pago) {

                $payments = array(
                    'date' => $pago->date,
                    'sale_id' => $sales_id,
                    'customer_id' => $pago->customer_id,
                    'transaction_id' => $pago->transaction_id,
                    'paid_by' => $pago->paid_by,
                    'cheque_no' => $pago->cheque_no,
                    'cc_no' => $pago->cc_no,
                    'cc_holder' => $pago->cc_holder,
                    'cc_month' => $pago->cc_month,
                    'cc_year' => $pago->cc_year,
                    'cc_type' => $pago->cc_type,
                    'amount' => $pago->amount,
                    'currency' => $pago->currency,
                    'created_by' => $pago->created_by,
                    'attachment' => $pago->attachment,
                    'note' => $pago->note,
                    'pos_paid' => $pago->pos_paid,
                    'pos_balance' => $pago->pos_balance,
                    'gc_no' => $pago->gc_no,
                    'reference' => $pago->reference,
                    'updated_by' => $pago->updated_by,
                    'updated_at' => $pago->updated_at,
                    'store_id' => $pago->store_id
                );
                if ($this->db->insert('payments', $payments)) {

                    $paid = $paid + $pago->amount;
                }
                $msg = 'PAGO AGREGADO';
            }
            $this->db->update('sales', ['paid' => $paid, 'status' => 'paid'], ['id' => $sales_id]);

            if (isset($payment['cc_cvv2'])) {

                unset($payment['cc_cvv2']);
            }
            $payment['sale_id'] = $sales_id;

            return array('sales_id' => $sales_id, 'message' => $msg);
        }
        return false;
    }

    public function addNoteCredit($data, $items, $tnc, $otrostextos) {


        if ($this->db->insert('note_credits', $data)) {
            $id_cn = $this->db->insert_id();

            $this->db->delete('note_credits_otros_textos', array('cn_id' => $id_cn));
			if(isset($otrostextos))
			{				
				foreach ($otrostextos as $texto) {
					$texto['cn_id'] = $id_cn;
					$this->db->insert('note_credits_otros_textos', $texto);
				}
			}

            foreach ($items as $item) {
                unset($item["type"]);
                $item['cn_id'] = $id_cn;
                if ($this->db->insert('note_credits_items', $item)) {
                    if ($item['product_name'] == "Producto sin codigo") {
                        $this->db->set('nc_status', '1', FALSE);
                        $this->db->set('nc_qty', "nc_qty +" . $item['quantity'], FALSE);
                        $this->db->where('product_id', $item['product_id']);
                        $this->db->where('sale_id', $data['sale_id']);
                        $this->db->update('sale_items');
                    } else {
                        if ($item['product_id'] > 0 && $product = $this->site->getProductByID($item['product_id'])) {
                            if ($product->type == 'standard') {
                                $this->db->set('nc_status', '1', FALSE);
                                $this->db->set('nc_qty', "nc_qty +" . $item['quantity'], FALSE);
                                $this->db->where('product_id', $item['product_id']);
                                $this->db->where('sale_id', $data['sale_id']);
                                $this->db->update('sale_items');

                                $this->db->update('product_store_qty', array('quantity' => ($product->quantity + $item['quantity'])), array('product_id' => $product->id, 'store_id' => $data['store_id']));
                            } elseif ($product->type == 'combo') {
                                $combo_items = $this->getComboItemsByPID($product->id);
                                foreach ($combo_items as $combo_item) {
                                    $cpr = $this->site->getProductByID($combo_item->id);
                                    if ($cpr->type == 'standard') {
                                        $qty = $combo_item->qty * $item['quantity'];

                                        $this->db->set('nc_status', '1', FALSE);
                                        $this->db->set('nc_qty', "nc_qty +" . $item['quantity'], FALSE);
                                        $this->db->where('product_id', $item['product_id']);
                                        $this->db->where('sale_id', $data['sale_id']);
                                        $this->db->update('sale_items');

                                        $this->db->update('product_store_qty', array('quantity' => ($cpr->quantity + $qty)), array('product_id' => $cpr->id, 'store_id' => $data['store_id']));
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $msg = array();

            return array('id_cn' => $id_cn, 'message' => $msg);
        }

        return false;
    }

    function stripe($amount = 0, $card_info = array(), $desc = '') {
        $this->load->model('stripe_payments');
        // $card_info = array( "number" => "4242424242424242", "exp_month" => 1, "exp_year" => 2016, "cvc" => "314" );
        // $amount = $amount ? $amount*100 : 3000;
        $amount = $amount * 100;
        if ($amount && !empty($card_info)) {
            $token_info = $this->stripe_payments->create_card_token($card_info);
            if (!isset($token_info['error'])) {
                $token = $token_info->id;
                $data = $this->stripe_payments->insert($token, $desc, $amount, $this->Settings->currency_prefix);
                if (!isset($data['error'])) {
                    $result = array('transaction_id' => $data->id,
                        'created_at' => date('Y-m-d H:i:s', $data->created),
                        'amount' => ($data->amount / 100),
                        'currency' => strtoupper($data->currency)
                    );
                    return $result;
                } else {
                    return $data;
                }
            } else {
                return $token_info;
            }
        }
        return false;
    }

    public function updateSale($id, $data, $items) {
        $osale = $this->getSaleByID($id);
        $oitems = $this->getAllSaleItems($id);
        foreach ($oitems as $oitem) {
            $product = $this->site->getProductByID($oitem->product_id, $osale->store_id);
            if ($product->type == 'standard') {
                $this->db->update('product_store_qty', array('quantity' => ($product->quantity + $oitem->quantity)), array('product_id' => $product->id, 'store_id' => $osale->store_id));
            } elseif ($product->type == 'combo') {
                $combo_items = $this->getComboItemsByPID($product->id);
                foreach ($combo_items as $combo_item) {
                    $cpr = $this->site->getProductByID($combo_item->id, $osale->store_id);
                    if ($cpr->type == 'standard') {
                        $qty = $combo_item->qty * $oitem->quantity;
                        $this->db->update('product_store_qty', array('quantity' => ($cpr->quantity + $qty)), array('product_id' => $cpr->id, 'store_id' => $osale->store_id));
                    }
                }
            }
        }

        $data['status'] = $osale->paid > 0 ? 'partial' : ($data['grand_total'] <= $osale->paid ? 'paid' : 'due');

        if ($this->db->update('sales', $data, array('id' => $id)) && $this->db->delete('sale_items', array('sale_id' => $id))) {

            foreach ($items as $item) {
                $item['sale_id'] = $id;
                if ($this->db->insert('sale_items', $item)) {
                    $product = $this->site->getProductByID($item['product_id'], $osale->store_id);
                    if ($product->type == 'standard') {
                        $this->db->update('product_store_qty', array('quantity' => ($product->quantity - $item['quantity'])), array('product_id' => $product->id, 'store_id' => $osale->store_id));
                    } elseif ($product->type == 'combo') {
                        $combo_items = $this->getComboItemsByPID($product->id);
                        foreach ($combo_items as $combo_item) {
                            $cpr = $this->site->getProductByID($combo_item->id, $osale->store_id);
                            if ($cpr->type == 'standard') {
                                $qty = $combo_item->qty * $item['quantity'];
                                $this->db->update('product_store_qty', array('quantity' => ($cpr->quantity - $qty)), array('product_id' => $cpr->id, 'store_id' => $osale->store_id));
                            }
                        }
                    }
                }
            }

            return TRUE;
        }

        return false;
    }

    public function suspendSale($data, $items, $did = NULL, $otrostextos = NULL) {

	   if ($did) {
            unset($data['date']);
            if ($this->db->update('suspended_sales', $data, array('id' => $did)) && $this->db->delete('suspended_items', array('suspend_id' => $did)) && $this->db->delete('suspended_otros_textos', array('suspend_id' => $did))) {
                foreach ($items as $item) {
                    unset($item['cost']);
                    unset($item['type']);
                    unset($item['quantity_edit']);
                    unset($item['qty_fracc_edit']);
                    unset($item['esta_fraccionado']);
                    $item['suspend_id'] = $did;
                    $this->db->insert('suspended_items', $item);
                }
                if ($otrostextos) {
                    foreach ($otrostextos as $texto) {
                        $texto['suspend_id'] = $did;
                        $this->db->insert('suspended_otros_textos', $texto);
                    }
                }
                return $did;
            }
        } else {
            if ($this->db->insert('suspended_sales', $data)) {
                $suspend_id = $this->db->insert_id();
				
                foreach ($items as $item) {
                    unset($item['cost']);
                    unset($item['type']);
                    unset($item['quantity_edit']);
                    unset($item['qty_fracc_edit']);
                    unset($item['esta_fraccionado']);
                    $item['suspend_id'] = $suspend_id;
                    $this->db->insert('suspended_items', $item);
					
                }
                if ($otrostextos) {
                    foreach ($otrostextos as $texto) {
                        $texto['suspend_id'] = $suspend_id;
                        $this->db->insert('suspended_otros_textos', $texto);
                    }
                }
                return $suspend_id;
            }
        }
        return false;
    }

    //    && $this->db->delete('quotes_otros_textos', array('quotes_id' => $did))
    //    foreach ($otrostextos as $texto) {
    //                    $texto['suspend_id'] = $did;
    //                    $this->db->insert('quotes_otros_textos', $texto);
    //                }
    public function quoteSale($data, $items, $quo = NULL, $otrostextos) {
        if ($quo) {
            if ($this->db->update('quotes', $data, array('id' => $quo)) && $this->db->delete('quotes_items', array('quotes_id' => $quo)) && $this->db->delete('quotes_otros_textos', array('quotes_id' => $did))) {
                foreach ($items as $item) {
                    unset($item['cost']);
                    unset($item['type']);
                    unset($item['quantity_edit']);
                    unset($item['qty_fracc_edit']);
                    unset($item['esta_fraccionado']);
                    $item['quotes_id'] = $quo;
                    $this->db->insert('quotes_items', $item);
                }
				
				
                if($otrostextos){
                    foreach ($otrostextos as $texto) {
                        $texto['quotes_id'] = $quo;
                        $this->db->insert('quotes_otros_textos', $texto);
                    }
                }
                return TRUE;
            }
        } else {
            if ($this->db->insert('quotes', $data)) {
                $quotes_id = $this->db->insert_id();
                foreach ($items as $item) {
                    unset($item['cost']);
                    unset($item['type']);

                    unset($item['quantity_edit']);
                    unset($item['qty_fracc_edit']);
                    unset($item['esta_fraccionado']);
                    $item['quotes_id'] = $quotes_id;
					
					#$this->db->save_queries = TRUE;
                    $this->db->insert('quotes_items', $item);

                }
                if($otrostextos){
                    foreach ($otrostextos as $texto) {
                        $texto['quotes_id'] = $quotes_id;
                        $this->db->insert('quotes_otros_textos', $texto);
                    }
                }
                return $quotes_id;
            }
        }
        return false;
    }

    public function getSaleByID($sale_id) {
        $q = $this->db->get_where('sales', array('id' => $sale_id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getFecByID($sale_id) {
        $q = $this->db->get_where('fec', array('id' => $sale_id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getQuoteByID($quotes_id) {
        $q = $this->db->get_where('quotes', array('id' => $quotes_id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getCreditNoteByID($id_cn) {
        $q = $this->db->get_where('tec_note_credits', array('id' => $id_cn), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllSaleItems($sale_id) {
        $j = "(SELECT id, code, name, tax_method from {$this->db->dbprefix('products')}) P";
        $this->db->select("sale_items.*,
            (CASE WHEN {$this->db->dbprefix('sale_items')}.product_code IS NULL THEN {$this->db->dbprefix('products')}.code ELSE {$this->db->dbprefix('sale_items')}.product_code END) as product_code,
            (CASE WHEN {$this->db->dbprefix('sale_items')}.product_name IS NULL THEN {$this->db->dbprefix('products')}.name ELSE {$this->db->dbprefix('sale_items')}.product_name END) as product_name,
            {$this->db->dbprefix('products')}.tax_method as tax_method", FALSE)
                ->join('products', 'products.id=sale_items.product_id', 'left outer')
                ->order_by('sale_items.id');
        $q = $this->db->get_where('sale_items', array('sale_id' => $sale_id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                if (isset($row->tax_method) and $row->tax_method == '0') {
                    $row->tax_method = '1';
                    if ($row->tax > 0) {
                        $invertir_impuesto = @$row->store_price / (1 + ($row->tax / 100));
                        $row->store_price = number_format($invertir_impuesto, 4, '.', '');
                        $row->price = number_format($invertir_impuesto, 4, '.', '');
                    }
                }
				
			$this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
            if ($qq->num_rows() > 0) 
            {
                $im = $qq->row();
                $row->id_impuesto=$im->id_impuesto;
                $row->codigo_impuesto=$im->codigo_impuesto;
                $row->codigo_tarifa=$im->codigo_tarifa;
            }
            else
            {
                $row->id_impuesto=0;
                $row->codigo_impuesto=0;
                $row->codigo_tarifa=0;                
            }

                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllCreditNoteItems($cd_id) {
        // $this->db->save_queries = TRUE;
        $j = "(SELECT id, code, name, tax_method from {$this->db->dbprefix('products')}) P";
        $this->db->select("note_credits_items.*,
            (CASE WHEN {$this->db->dbprefix('note_credits_items')}.product_code IS NULL THEN {$this->db->dbprefix('products')}.code ELSE {$this->db->dbprefix('note_credits_items')}.product_code END) as product_code,
            (CASE WHEN {$this->db->dbprefix('note_credits_items')}.product_name IS NULL THEN {$this->db->dbprefix('products')}.name ELSE {$this->db->dbprefix('note_credits_items')}.product_name END) as product_name,
            {$this->db->dbprefix('products')}.tax_method as tax_method", FALSE)
                ->join('products', 'products.id=note_credits_items.product_id', 'left outer')
                ->order_by('note_credits_items.id');
        $q = $this->db->get_where('note_credits_items', array($this->db->dbprefix("note_credits_items").'.`cn_id`' => $cd_id));
        // dd($this->db->last_query());
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                if (isset($row->tax_method) and $row->tax_method == '0') {
                    $row->tax_method = '1';
                    if ($row->tax > 0) {
                        $invertir_impuesto = @$row->store_price / (1 + ($row->tax / 100));
                        $row->store_price = number_format($invertir_impuesto, 4, '.', '');
                        $row->price = number_format($invertir_impuesto, 4, '.', '');
                    }
                }
				
			$this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
            if ($qq->num_rows() > 0) 
            {
                $im = $qq->row();
                $row->id_impuesto=$im->id_impuesto;
                $row->codigo_impuesto=$im->codigo_impuesto;
                $row->codigo_tarifa=$im->codigo_tarifa;
            }
            else
            {
                $row->id_impuesto=0;
                $row->codigo_impuesto=0;
                $row->codigo_tarifa=0;                
            }

                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllFecItems($sale_id) {
        $this->db->select("fec_items.*,
         {$this->db->dbprefix('fec_items')}.product_code,
            {$this->db->dbprefix('fec_items')}.product_name,
            1 as tax_method", FALSE)
            ->order_by('fec_items.id');
        $q = $this->db->get_where('fec_items', array('sale_id' => $sale_id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
			$this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
            if ($qq->num_rows() > 0) 
            {
                $im = $qq->row();
                $row->id_impuesto=$im->id_impuesto;
                $row->codigo_impuesto=$im->codigo_impuesto;
                $row->codigo_tarifa=$im->codigo_tarifa;
            }
            else
            {
                $row->id_impuesto=0;
                $row->codigo_impuesto=0;
                $row->codigo_tarifa=0;                
            }

                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllQuoteItems($quotes_id) {
        $j = "(SELECT id, code, name, tax_method, id_tax from {$this->db->dbprefix('products')}) P";
        $this->db->select("quotes_items.*,
            (CASE WHEN {$this->db->dbprefix('quotes_items')}.product_code IS NULL THEN {$this->db->dbprefix('products')}.code ELSE {$this->db->dbprefix('quotes_items')}.product_code END) as product_code,
            (CASE WHEN {$this->db->dbprefix('quotes_items')}.product_name IS NULL THEN {$this->db->dbprefix('products')}.name ELSE {$this->db->dbprefix('quotes_items')}.product_name END) as product_name,
            {$this->db->dbprefix('products')}.tax_method as tax_method", FALSE)
                ->join('products', 'products.id=quotes_items.product_id', 'left outer')
                ->order_by('quotes_items.id');
        $q = $this->db->get_where('quotes_items', array('quotes_id' => $quotes_id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                if (isset($row->tax_method) and $row->tax_method == '0') {
                    $row->tax_method = '1';
                    if ($row->tax > 0) {
                        $invertir_impuesto = $row->real_unit_price + $row->item_tax / (1 + ($row->tax / 100));
                        $row->store_price = number_format($invertir_impuesto, 4, '.', '');
                        $row->price = number_format($invertir_impuesto, 4, '.', '');

                    }
                }

                $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
                if ($qq->num_rows() > 0) 
                {
                    $im = $qq->row();
                    $row->id_impuesto=$im->id_impuesto;
                    $row->codigo_impuesto=$im->codigo_impuesto;
                    $row->codigo_tarifa=$im->codigo_tarifa;
                }
                else
                {
                    $row->id_impuesto=0;
                    $row->codigo_impuesto=0;
                    $row->codigo_tarifa=0;                
                }                
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllCreditNotesItems($id_cn) {
        $j = "(SELECT id, code, name, tax_method, id_tax from {$this->db->dbprefix('products')}) P";
        $this->db->select("note_credits_items.*,
            (CASE WHEN {$this->db->dbprefix('note_credits_items')}.product_code IS NULL THEN {$this->db->dbprefix('products')}.code ELSE {$this->db->dbprefix('note_credits_items')}.product_code END) as product_code,
            (CASE WHEN {$this->db->dbprefix('note_credits_items')}.product_name IS NULL THEN {$this->db->dbprefix('products')}.name ELSE {$this->db->dbprefix('note_credits_items')}.product_name END) as product_name,
            {$this->db->dbprefix('products')}.tax_method as tax_method", FALSE)
                ->join('products', 'products.id=note_credits_items.product_id', 'left outer')
                ->order_by('note_credits_items.id');
        $q = $this->db->get_where('note_credits_items', array('cn_id' => $id_cn));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                if (isset($row->tax_method) and $row->tax_method == '0') {
                    $row->tax_method = '1';
                    if ($row->tax > 0) {
                        $invertir_impuesto = @$row->store_price / (1 + ($row->tax / 100));
                        $row->store_price = number_format($invertir_impuesto, 4, '.', '');
                        $row->price = number_format($invertir_impuesto, 4, '.', '');
                    }
                }
                $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
                if ($qq->num_rows() > 0) 
                {
                    $im = $qq->row();
                    $row->id_impuesto=$im->id_impuesto;
                    $row->codigo_impuesto=$im->codigo_impuesto;
                    $row->codigo_tarifa=$im->codigo_tarifa;
                }
                else
                {
                    $row->id_impuesto=0;
                    $row->codigo_impuesto=0;
                    $row->codigo_tarifa=0;                
                }


                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllSalePayments($sale_id) {
        $q = $this->db->get_where('payments', array('sale_id' => $sale_id));
        if ($q->num_rows() > 0) {
         return $q->result();
        }
        return FALSE;
    }

    public function getAllShipping() {
        $q = $this->db->get("shipping_method");
        if ($q->num_rows() > 0) {
         return $q->result();
        }
        return FALSE;
    }

    public function getWaitingTables() {
        $q = $this->db->get_where("waiting_tables",array("status"=>"1"));
        if ($q->num_rows() > 0) {
         return $q->result();
        }
        return FALSE;
    }

    public function getAllFecPayments($sale_id) {
        $q = $this->db->get_where('payments_fec', array('sale_id' => $sale_id));
        if ($q->num_rows() > 0) {
         return $q->result();
        }
        return FALSE;
    }

    public function getSuspendedSaleByID($id) {
        $q = $this->db->get_where('suspended_sales', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getSuspendedSaleItems($id) {
        $q = $this->db->get_where('suspended_items', array('suspend_id' => $id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
                if ($qq->num_rows() > 0) 
                {
                    $im = $qq->row();
                    $row->id_impuesto=$im->id_impuesto;
                    $row->codigo_impuesto=$im->codigo_impuesto;
                    $row->codigo_tarifa=$im->codigo_tarifa;
                }
                else
                {
                    $row->id_impuesto=0;
                    $row->codigo_impuesto=0;
                    $row->codigo_tarifa=0;                
                }
                
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getSuspendedOtrosTextos($id) {
        $q = $this->db->get_where('suspended_otros_textos', array('suspend_id' => $id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getQuotesOtrosTextos($id) {
        $q = $this->db->get_where('quotes_otros_textos', array('quotes_id' => $id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getApartadoOtrosTextos($id) {
        $q = $this->db->get_where('layaway_otros_textos', array('apartado_id' => $id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getSaleOtrosTextos($id) {
        $q = $this->db->get_where('sales_otros_textos', array('sale_id' => $id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getCreditnoteOtrosTextos($id) {
        $q = $this->db->get_where('tec_note_credits_otros_textos', array('cn_id' => $id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getQuotesSaleItems($id) {
        #$this->db->save_queries = TRUE;
        $j = "(SELECT id, code, name, tax_method, id_tax from {$this->db->dbprefix('products')}) P";
        $this->db->select("quotes_items.*,
            (CASE WHEN {$this->db->dbprefix('quotes_items')}.product_code IS NULL THEN {$this->db->dbprefix('products')}.code ELSE {$this->db->dbprefix('quotes_items')}.product_code END) as product_code,
            (CASE WHEN {$this->db->dbprefix('quotes_items')}.product_name IS NULL THEN {$this->db->dbprefix('products')}.name ELSE {$this->db->dbprefix('quotes_items')}.product_name END) as product_name,
            {$this->db->dbprefix('products')}.tax_method as tax_method", FALSE)
            ->join('products', 'products.id=quotes_items.product_id', 'left outer')
            ->order_by('quotes_items.id');
        $q = $this->db->get_where('quotes_items', array('quotes_id' => $id));

        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                
                if (isset($row->tax_method) and $row->tax_method == '0') {
                    $row->tax_method = '1';
                    if ($row->tax > 0) {
                        $invertir_impuesto = @$row->store_price / (1 + ($row->tax / 100));
                        $row->store_price = number_format($invertir_impuesto, 4, '.', '');
                        $row->price = number_format($invertir_impuesto, 4, '.', '');
                    }
                }

                $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
                if ($qq->num_rows() > 0) 
                {
                    $im = $qq->row();
                    $row->id_impuesto=$im->id_impuesto;
                    $row->codigo_impuesto=$im->codigo_impuesto;
                    $row->codigo_tarifa=$im->codigo_tarifa;
                }
                else
                {
                    $row->id_impuesto=0;
                    $row->codigo_impuesto=0;
                    $row->codigo_tarifa=0;                
                }
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getApartadoSaleItems($id) {
        $q = $this->db->get_where('layaway_items', array('apartado_id' => $id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
                if ($qq->num_rows() > 0) 
                {
                    $im = $qq->row();
                    $row->id_impuesto=$im->id_impuesto;
                    $row->codigo_impuesto=$im->codigo_impuesto;
                    $row->codigo_tarifa=$im->codigo_tarifa;
                }
                else
                {
                    $row->id_impuesto=0;
                    $row->codigo_impuesto=0;
                    $row->codigo_tarifa=0;                
                }
                
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllApartadoPayments($id) {
        $q = $this->db->get_where('tec_payments_apartado', array('apartado_id' => $id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getSuspendedSales($user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->order_by('date', 'desc');
        $q = $this->db->get_where('suspended_sales', array('created_by' => $user_id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getQuotesSalesID($id) {
        $q = $this->db->get_where('quotes', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getApartadoSalesID($id) {
        $q = $this->db->get_where('layaway', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getGiftCardByNO($no) {
        $q = $this->db->get_where('gift_cards', array('card_no' => $no), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getComboItemsByPID($product_id) {
        $this->db->select($this->db->dbprefix('products') . '.id as id, ' . $this->db->dbprefix('products') . '.code as code, ' . $this->db->dbprefix('combo_items') . '.quantity as qty, ' . $this->db->dbprefix('products') . '.name as name, ' . $this->db->dbprefix('products') . '.quantity as quantity, ' . $this->db->dbprefix('products') . '.id_tax as id_tax')
                ->join('products', 'products.code=combo_items.item_code', 'left')
                ->group_by('combo_items.id');
        $q = $this->db->get_where('combo_items', array('product_id' => $product_id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                if (isset($row->tax_method) and $row->tax_method == '0') {
                    $row->tax_method = '1';
                    if ($row->tax > 0) {
                        $invertir_impuesto = $row->store_price / (1 + ($row->tax / 100));
                        $row->store_price = number_format($invertir_impuesto, 4, '.', '');
                        $row->price = number_format($invertir_impuesto, 4, '.', '');
                    }
                }

                $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => (!empty($row->id_tax) ? $row->id_tax : 8)), 1);
                if ($qq->num_rows() > 0) 
                {
                    $im = $qq->row();
                    $row->id_impuesto=$im->id_impuesto;
                    $row->codigo_impuesto=$im->codigo_impuesto;
                    $row->codigo_tarifa=$im->codigo_tarifa;
                }
                else
                {
                    $row->id_impuesto=0;
                    $row->codigo_impuesto=0;
                    $row->codigo_tarifa=0;                
                }

                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getUser($id) {
        $q = $this->db->get_where('users', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function UpdatePaid($id_sale, $paid, $status) {
        $this->db->where('id', $id_sale)->update('tec_sales', array('status' => $status, 'paid' => $paid));
    }

    public function getItemssuspended($id){
        $q = $this->db->select("id, product_id,product_name, quantity")->get_where('suspended_items', array('suspend_id' => $id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getPricesByProductId($id_product,$id_price)
    {
        // $this->db->save_queries = TRUE;
        $q = $this->db->select("lista_precios.nombre_l_precio as name, lista_precios.code as code, product_prices.price as price")
        ->join("lista_precios ","lista_precios.id_lista_precios = product_prices.price_group_id","left")
        ->get_where('product_prices', array('product_id' => $id_product ,"price_group_id" => $id_price));
        // dd($this->db->last_query());
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getListPrice()
    {
        $q = $this->db->get("lista_precios");
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function addNoteDebit($data, $items) {
        if ($this->db->insert('note_debits', $data)) {
            $id_nd = $this->db->insert_id();
            foreach ($items as $item) {
                $item['nd_id'] = $id_nd;
                $this->db->insert('note_debits_items', $item);
            }
            return $id_nd;
        }
        return false;
    }

    public function getDebitNoteByID($id_nd) {
        $q = $this->db->get_where($this->db->dbprefix('note_debits'), array('id' => $id_nd), 1);
        if ($q->num_rows() > 0) return $q->row();
        return false;
    }

    public function getAllDebitNotesItems($id_nd) {
        $this->db->select("note_debits_items.*,
            COALESCE(note_debits_items.product_code, products.code) as product_code,
            COALESCE(note_debits_items.product_name, products.name) as product_name,
            products.tax as tax_rate")
            ->join('products', 'products.id = note_debits_items.product_id', 'left outer')
            ->order_by('note_debits_items.id');
        $q = $this->db->get_where('note_debits_items', array($this->db->dbprefix('note_debits_items').'.nd_id' => $id_nd));
        if ($q->num_rows() > 0) return $q->result();
        return array();
    }

}

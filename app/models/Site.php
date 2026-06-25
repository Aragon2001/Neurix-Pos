<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Site extends CI_Model
{

    public function __construct() {
        parent::__construct();
    }

    public function getQtyAlerts() {
        if (!$this->session->userdata('store_id')) {
            return 0;
        }
        $this->db->join("( SELECT (CASE WHEN quantity IS NULL THEN 0 ELSE quantity END) as quantity, product_id from {$this->db->dbprefix('product_store_qty')} WHERE store_id = {$this->session->userdata('store_id')} ) psq", 'products.id=psq.product_id', 'left')
        ->where("psq.quantity < {$this->db->dbprefix('products')}.alert_quantity", NULL, FALSE)
        ->where('products.alert_quantity >', 0);
        return $this->db->count_all_results('products');
    }


	
	 public function getProductQty($id) {
        $q = $this->db->get_where('product_store_qty', array('product_id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
	
	
    public function getProductByID($id, $store_id = NULL) {
        
        if (!$store_id) {
            $store_id = $this->session->userdata('store_id');
        }
        $jpsq = "( SELECT product_id, quantity, qty_fracc,  price from {$this->db->dbprefix('product_store_qty')} WHERE store_id = ".($store_id ? $store_id : "''")." ) AS PSQ";
        $this->db->select("{$this->db->dbprefix('products')}.*, COALESCE(PSQ.quantity, 0) as quantity, COALESCE(PSQ.qty_fracc, 0) as qty_fracc, COALESCE(PSQ.price, {$this->db->dbprefix('products')}.price) as store_price", FALSE)
        ->join($jpsq, 'PSQ.product_id=products.id', 'left');
        
        $q = $this->db->get_where('products', array('products.id' => $id), 1);
        if ($q->num_rows() > 0) {
            $row = $q->row();
            if (isset($row->tax_method) and $row->tax_method == '0') {
                $row->tax_method = '1';
                if ($row->tax > 0) {
                    $invertir_impuesto = $row->price / (1 + ($row->tax / 100));
                    $row->price = number_format($invertir_impuesto, 4, '.', '');
                    $row->store_price = number_format($invertir_impuesto, 4, '.', '');
                }
            }

               $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => $row->id_tax), 1);
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
            return $row;
        }
        return FALSE;
    }

    public function getSettings() {
        
        $q = $this->db->get('settings');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllCustomers() {
        
        $q = $this->db->get('customers');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }


    public function getAllActividades() {
        
        $q = $this->db->get('actividadeconomica');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }


    public function getAllSuppliers() {
        $q = $this->db->get('suppliers');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllUsers() {
        $this->db->select("{$this->db->dbprefix('users')}.id as id, first_name, last_name, {$this->db->dbprefix('users')}.email, company, {$this->db->dbprefix('groups')}.name as group, active, {$this->db->dbprefix('stores')}.name as store")
            ->join('groups', 'users.group_id=groups.id', 'left')
            ->join('stores', 'users.store_id=stores.id', 'left')
            ->group_by('users.id');
        $q = $this->db->get('users');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getUsers() {
        $q = $this->db->get('users');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getUser($id = NULL) {
        if (!$id) {
            $id = $this->session->userdata('user_id');
        }
        $q = $this->db->get_where('users', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllCategories() {
        $this->db->order_by('code');
        $q = $this->db->get('categories');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getCategoryByID($id) {
        $q = $this->db->get_where('categories', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getCategoryByCode($code) {
        $q = $this->db->get_where('categories', array('code' => $code), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllImpuestos() {
        $this->db->order_by('id_impuesto');
        $q = $this->db->get('impuestos');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllListPrices() {
        $this->db->order_by('id_lista_precios');
        $q = $this->db->get_where('lista_precios',array("status_l_precio" => "1"));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getProductPriceById($id) {
        $this->db->select("lista_precios.id_lista_precios as id_lista_precios
        ,lista_precios.nombre_l_precio as nombre_l_precio, product_prices.price as price, product_prices.margen as margen,product_prices.id_product_prices as id_product_prices")
        ->join("lista_precios","lista_precios.id_lista_precios = product_prices.price_group_id","left");
        $q = $this->db->get_where('product_prices',array("product_id" => $id));
        if ($q->num_rows() > 0) {
            // dd($q->result_array());
            return $q->result_array();
        }
        return FALSE;
    }

    public function getImpuestosByID($id) {
        $q = $this->db->get_where('impuestos', array('id_impuesto' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }    

    public function getGiftCard($no) {
        $q = $this->db->get_where('gift_cards', array('card_no' => $no), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getUpcomingEvents() {
        $dt = date('Y-m-d');
        $this->db->where('date >=', $dt)->order_by('date')->limit(5);
        if ($this->Settings->restrict_calendar) {
            $q = $this->db->get_where('calendar', array('user_id' => $this->session->userdata('iser_id')));
        } else {
            $q = $this->db->get('calendar');
        }
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getUserGroup($user_id = NULL) {
        if ($group_id = $this->getUserGroupID($user_id)) {
            $q = $this->db->get_where('groups', array('id' => $group_id), 1);
            if ($q->num_rows() > 0) {
                return $q->row();
            }
        }
        return FALSE;
    }

    public function getUserGroupID($user_id = NULL) {
        if ($user = $this->getUser($user_id)) {
            return $user->group_id;
        }
        return FALSE;
    }
 
    public function getUserSuspenedSales() {
        $user_id = $this->session->userdata('user_id');
        $this->db->select('id, date, customer_name, hold_ref,id_waiting_tables, note')
        ->order_by('id desc');
        //->limit(10);
        $this->db->where('store_id', $this->session->userdata('store_id'));

        if($this->session->userdata('group_id') == "1"){
            $q = $this->db->get('suspended_sales');
        }else{
            $q = $this->db->get_where('suspended_sales', array('created_by' => $user_id));
        }

        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getStoreByID($id = NULL) {
        if ( ! $id) {
            return FALSE;
        }
        $q = $this->db->get_where('stores', array('id' => $id), 1);
        if ( $q->num_rows() > 0 ) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllStores() {
        $q = $this->db->get('stores');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function registerData($user_id)
    {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $q = $this->db->get_where('registers', array('user_id' => $user_id, 'status' => 'open'), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllPrinters() {
        $this->db->order_by('title');
        $q = $this->db->get('printers');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getPrinterByID($id) {
        $q = $this->db->get_where('printers', array('id' => $id), 1);
        if ( $q->num_rows() > 0 ) {
            return $q->row();
        }
        return FALSE;
    }

    public function getCustomerByID($id) {
        
        $q = $this->db->get_where('customers', array('id' => $id), 1);
        if ( $q->num_rows() > 0 ) {
            return $q->row();
        }
        return FALSE;
    }

    public function getGiftCardByID($id) {
        $q = $this->db->get_where('gift_cards', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

}

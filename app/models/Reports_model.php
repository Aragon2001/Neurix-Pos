<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reports_model extends CI_Model
{

    public function __construct() {
        parent::__construct();
    }

    public function getAllProducts() {
        $q = $this->db->get('products');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
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

    public function getCustomerByShipping($id){
        // $this->db->save_queries = TRUE;
        $this->db->select($this->db->dbprefix('sales').".customer_id");
        $this->db->where('id_shipping_method', $id);
        $this->db->group_by('customer_id');
        $q = $this->db->get("sales");
        // dd($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->result();
           }
           return FALSE;
    }

    public function getAllCustomers() {
        $q = $this->db->get('customers');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function topProducts() {
        $m = date('Y-m');
        $this->db->select($this->db->dbprefix('products').".code as product_code, ".$this->db->dbprefix('products').".name as product_name, sum(".$this->db->dbprefix('sale_items').".quantity) as quantity")
        ->join('products', 'products.id=sale_items.product_id', 'left')
        ->join('sales', 'sales.id=sale_items.sale_id', 'left')
        ->order_by("sum(".$this->db->dbprefix('sale_items').".quantity)", 'desc')
        ->group_by('sale_items.product_id')
        ->limit(10)
        ->like($this->db->dbprefix('sales').'.date', $m, 'both');
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sale_items');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function topProducts1() {
        $m = date('Y-m', strtotime('first day of last month'));
        $this->db->select($this->db->dbprefix('products').".code as product_code, ".$this->db->dbprefix('products').".name as product_name, sum(".$this->db->dbprefix('sale_items').".quantity) as quantity")
        ->join('products', 'products.id=sale_items.product_id', 'left')
        ->join('sales', 'sales.id=sale_items.sale_id', 'left')
        ->order_by("sum(".$this->db->dbprefix('sale_items').".quantity)", 'desc')
        ->group_by('sale_items.product_id')
        ->limit(10)
        ->like($this->db->dbprefix('sales').'.date', $m, 'both');
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sale_items');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function topProducts3() {
        $this->db->select($this->db->dbprefix('products').".code as product_code, ".$this->db->dbprefix('products').".name as product_name, sum(".$this->db->dbprefix('sale_items').".quantity) as quantity")
        ->join('products', 'products.id=sale_items.product_id', 'left')
        ->join('sales', 'sales.id=sale_items.sale_id', 'left')
        ->order_by("sum(".$this->db->dbprefix('sale_items').".quantity)", 'desc')
        ->group_by('sale_items.product_id')
        ->limit(10);
        if ($this->db->dbdriver == 'sqlite3') {
            // ->where("date >= datetime('now','-6 month')", NULL, FALSE)
            $this->db->where("{$this->db->dbprefix('sales')}.date >= datetime(date('now','start of month','+1 month','-1 day'), '-3 month')", NULL, FALSE);
        } else {
            $this->db->where($this->db->dbprefix('sales').'.date >= last_day(now()) + interval 1 day - interval 3 month', NULL, FALSE);
        }
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sale_items');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function topProducts12() {
        $this->db->select($this->db->dbprefix('products').".code as product_code, ".$this->db->dbprefix('products').".name as product_name, sum(".$this->db->dbprefix('sale_items').".quantity) as quantity")
        ->join('products', 'products.id=sale_items.product_id', 'left')
        ->join('sales', 'sales.id=sale_items.sale_id', 'left')
        ->order_by("sum(".$this->db->dbprefix('sale_items').".quantity)", 'desc')
        ->group_by('sale_items.product_id')
        ->limit(10);
        if ($this->db->dbdriver == 'sqlite3') {
            // ->where("date >= datetime('now','-6 month')", NULL, FALSE)
            $this->db->where("{$this->db->dbprefix('sales')}.date >= datetime(date('now','start of month','+1 month','-1 day'), '-12 month')", NULL, FALSE);
        } else {
            $this->db->where($this->db->dbprefix('sales').'.date >= last_day(now()) + interval 1 day - interval 12 month', NULL, FALSE);
        }

        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sale_items');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function getDailySales($year, $month) {
        if ($this->db->dbdriver == 'sqlite3') {
            $this->db->select("strftime('%d', date) AS date, COALESCE(sum(product_tax), 0) as product_tax, COALESCE(sum(order_tax), 0) as order_tax, COALESCE(sum(total), 0) as total, COALESCE(sum(grand_total), 0) as grand_total, COALESCE(sum(total_tax), 0) as total_tax, COALESCE(sum(total_discount), 0) as discount, COALESCE(sum(paid), 0) as paid", FALSE);
        } else {
            $this->db->select("DATE_FORMAT( date,  '%d' ) AS date, COALESCE(sum(product_tax), 0) as product_tax, COALESCE(sum(order_tax), 0) as order_tax, COALESCE(sum(total), 0) as total, COALESCE(sum(grand_total), 0) as grand_total, COALESCE(sum(total_tax), 0) as total_tax, COALESCE(sum(total_discount), 0) as discount, COALESCE(sum(paid), 0) as paid", FALSE);
        }
        $this->db->like('date', "{$year}-{$month}", 'after');
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sales');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }


    public function getMonthlySales($year) {
        if ($this->db->dbdriver == 'sqlite3') {
            $this->db->select("strftime('%m', date) AS date, COALESCE(sum(product_tax), 0) as product_tax, COALESCE(sum(order_tax), 0) as order_tax, COALESCE(sum(total), 0) as total, COALESCE(sum(grand_total), 0) as grand_total, COALESCE(sum(total_tax), 0) as tax, COALESCE(sum(total_discount), 0) as discount, COALESCE(sum(paid), 0) as paid", FALSE)
            ->group_by("strftime('%m', date)");
        } else {
            $this->db->select("DATE_FORMAT( date,  '%m' ) AS date, COALESCE(sum(product_tax), 0) as product_tax, COALESCE(sum(order_tax), 0) as order_tax, COALESCE(sum(total), 0) as total, COALESCE(sum(grand_total), 0) as grand_total, COALESCE(sum(total_tax), 0) as tax, COALESCE(sum(total_discount), 0) as discount, COALESCE(sum(paid), 0) as paid", FALSE)
            ->group_by("DATE_FORMAT(date,  '%m')")
            ->order_by("DATE_FORMAT(date,  '%m') ASC");
        }

        $this->db->like('date', "{$year}", 'after');
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sales');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getTotalCustomerSales($customers_id, $user = NULL, $start_date = NULL, $end_date = NULL, $id_shipping_method = NULL) {
        // $this->db->save_queries = TRUE;
        $where = " WHERE id IS NOT NULL";
        if ($start_date && $end_date) {
            $where .= " AND `date` BETWEEN '".$start_date."' AND '".$end_date."'";
            // $this->db->where('date >=', $start_date);
            // $this->db->where('date <=', $end_date);
        }
        if ($user) {
            // $this->db->where('created_by', $user);
            $where .= " AND created_by = ".$user;
        }
        if ($this->session->userdata('store_id')) {
            // $this->db->where('store_id', $this->session->userdata('store_id'));
            $where .= " AND store_id =".$this->session->userdata('store_id');
        }
        if($id_shipping_method){
            // $this->db->where('id_shipping_method', $id_shipping_method);
            $where .= " AND id_shipping_method =".$id_shipping_method;
        }else{
            $where .= " AND id_shipping_method IS NULL";
        }
        $where .= " AND customer_id IN(".str_replace(']','',str_replace('[','',json_encode($customers_id))).")";
        $q= $this->db->query('SELECT COUNT(id) as number, sum(grand_total) as amount,
         SUM(CASE WHEN `status` = "paid" THEN grand_total ELSE paid END) AS paid FROM '.$this->db->dbprefix('sales').$where);
        //  dd($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalSalesforCustomer($customer_id, $user = NULL, $start_date = NULL, $end_date = NULL) {
        if ($start_date && $end_date) {
            $this->db->where('date >=', $start_date);
            $this->db->where('date <=', $end_date);
        }
        if ($user) {
            $this->db->where('created_by', $user);
        }
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q=$this->db->get_where('sales', array('customer_id' => $customer_id));
        return $q->num_rows();
    }

    public function getTotalSalesValueforCustomer($customer_id, $user = NULL, $start_date = NULL, $end_date = NULL) {
        $this->db->select('sum(grand_total) as total');
        if($start_date && $end_date) {
            $this->db->where('date >=', $start_date);
            $this->db->where('date <=', $end_date);
        }
        if($user) {
            $this->db->where('created_by', $user);
        }
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q=$this->db->get_where('sales', array('customer_id' => $customer_id));
        if( $q->num_rows() > 0 ) {
            $s = $q->row();
            return $s->total;
        }
        return FALSE;
    }

    public function getAllStaff() {

        $q = $this->db->get('users');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getTotalSales($start, $end) {
        $this->db->select('count(id) as total, sum(COALESCE(grand_total, 0)) as total_amount, SUM(COALESCE(paid, 0)) as paid, SUM(COALESCE(total_tax, 0)) as tax', FALSE)
            ->where("date >= '{$start}' and date <= '{$end}'", NULL, FALSE);
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('sales');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalPurchases($start, $end) {
        $this->db->select('count(id) as total, sum(COALESCE(total, 0)) as total_amount', FALSE)
            ->where("date >= '{$start}' and date <= '{$end}'", NULL, FALSE);
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('purchases');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalExpenses($start, $end) {
        $this->db->select('count(id) as total, sum(COALESCE(amount, 0)) as total_amount', FALSE)
            ->where("date >= '{$start}' and date <= '{$end}'", NULL, FALSE);
        if ($this->session->userdata('store_id')) {
            $this->db->where('store_id', $this->session->userdata('store_id'));
        }
        $q = $this->db->get('expenses');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function update_deudas(){  
        $this->db->query("UPDATE `tec_sales` SET `status` = 'due' WHERE  `grand_total` > `paid`;");
        $this->db->query("UPDATE `tec_sales` SET `status` = 'paid' WHERE `grand_total` = `paid`;");
        // $this->db->save_queries = TRUE;
        $this->db->where_in('customer_name', ['Cliente de Paso', 'Cliente de paso', 'Cliente de Contado', 'Cliente de contado'])->where('id_shipping_method', NULL)->update('tec_sales', array('status'=>'paid'));
        // dd($this->db->last_query());
    }

    public function get_d151($fecha_inicio,$fecha_final){
        // $this->db->save_queries = TRUE;
        $q = $this->db->query("
        SELECT 
	    /*VENTAS*/
        cedula,nombre,SUM(t.subtotal) subtotal,CodigoRep,Concepto
        FROM 
        ( SELECT 
	  TRIM(c.cf2) AS cedula, TRIM(c.name) AS nombre ,
	  CASE
	    WHEN BINARY si.`unit_of_measurement` IN ('Sp', 'Spe', 'Al', 'Alc', 'Cm', 'I') 
	    THEN 'VI' 
	    ELSE 'V' 
	  END AS CodigoRep,
	  CASE
	    WHEN BINARY si.`unit_of_measurement` IN ('Sp', 'Spe', 'Al', 'Alc', 'Cm', 'I') 
	    THEN 'Ingresos por concepto de intereses (V)' 
	    ELSE 'Ventas de bienes (V)' 
	  END AS Concepto,(si.net_unit_price) AS subtotal
	FROM
	  `tec_sales` s
	  LEFT JOIN `tec_customers` c
	    ON c.id = s.`customer_id`
	  LEFT JOIN `tec_sale_items` si 
	    ON si.sale_id = s.id 
	  LEFT JOIN tec_hacienda_tiketes ht
	    ON ht.sale_id = s.id
	   WHERE ht.estatus_hacienda= 'aceptado'
	     AND DATE(s.date) BETWEEN '".$fecha_inicio."' AND '".$fecha_final."'
	    /*COMPRAS*/
	  UNION
          SELECT  dc.`NumeroCedulaEmisor` AS cedula, dc.nombre_emisor AS nombre ,
          CASE
	    WHEN dci.unit_of_measurement NOT IN ('Sp', 'Spe', 'Al', 'Alc', 'Cm', 'I') 
	    THEN 'C' 
	    WHEN dci.unit_of_measurement = 'Sp' 
	    THEN 'SP' 
	    WHEN dci.unit_of_measurement = 'Al' 
	    THEN 'A' 
	    WHEN dci.unit_of_measurement = 'Alc' 
	    THEN 'A' 
	    WHEN dci.unit_of_measurement = 'Cm' 
	    THEN 'M' 
	    WHEN dci.unit_of_measurement = 'I' 
	    THEN 'I' 
	  END AS CodigoRep,
	  CASE
	    WHEN dci.unit_of_measurement NOT IN ('Sp', 'Spe', 'Al', 'Alc', 'Cm', 'I') 
	    THEN 'Compras (C)' 
	    WHEN dci.unit_of_measurement = 'Sp' 
	    THEN 'Pago de Servicios Profesionales (SP)' 
	    WHEN dci.unit_of_measurement = 'Al' 
	    THEN 'Pago de Alquileres (A)' 
	    WHEN dci.unit_of_measurement = 'Alc' 
	    THEN 'Pago de Alquileres (A)' 
	    WHEN dci.unit_of_measurement = 'Cm' 
	    THEN 'Pago de Comisiones (M)' 
	    WHEN dci.unit_of_measurement = 'I' 
	    THEN 'Pago de Intereses (I)' 
	  END AS Concepto,
          (dci.SubTotal) AS subtotal
          FROM `tec_documentoshacienda` dc
          INNER JOIN `tec_documentositems` dci ON dc.`ConsecutivoDocEmisor` = dci.`consecutivo`
          WHERE dc.Estatus = 'aceptado' AND DATE(dc.Fecha_aceptacion) BETWEEN '".$fecha_inicio."' AND '".$fecha_final."'
          /*FEC*/
	  UNION
          SELECT  s.`cf2` AS cedula, s.`name` AS nombre ,
          CASE
	    WHEN feci.unit_of_measurement NOT IN ('Sp', 'Spe', 'Al', 'Alc', 'Cm', 'I') 
	    THEN 'C' 
	    WHEN feci.unit_of_measurement = 'Sp' 
	    THEN 'SP' 
	    WHEN feci.unit_of_measurement = 'Al' 
	    THEN 'A' 
	    WHEN feci.unit_of_measurement = 'Alc' 
	    THEN 'A' 
	    WHEN feci.unit_of_measurement = 'Cm' 
	    THEN 'M' 
	    WHEN feci.unit_of_measurement = 'I' 
	    THEN 'I' 
	  END AS CodigoRep,
	  CASE
	    WHEN feci.unit_of_measurement NOT IN ('Sp', 'Spe', 'Al', 'Alc', 'Cm', 'I') 
	    THEN 'Compras (C)' 
	    WHEN feci.unit_of_measurement = 'Sp' 
	    THEN 'Pago de Servicios Profesionales (SP)' 
	    WHEN feci.unit_of_measurement = 'Al' 
	    THEN 'Pago de Alquileres (A)' 
	    WHEN feci.unit_of_measurement = 'Alc' 
	    THEN 'Pago de Alquileres (A)' 
	    WHEN feci.unit_of_measurement = 'Cm' 
	    THEN 'Pago de Comisiones (M)' 
	    WHEN feci.unit_of_measurement = 'I' 
	    THEN 'Pago de Intereses (I)' 
	  END AS Concepto,
          (feci.`net_unit_price`) AS subtotal
          FROM `tec_fec` fec
          INNER JOIN `tec_fec_items` feci ON feci.sale_id = fec.id
          INNER JOIN `tec_suppliers` s ON s.id = fec.`customer_id`
          INNER JOIN tec_hacienda_fec hfec ON hfec.sale_id = fec.id
          WHERE hfec.estatus_hacienda = 'aceptado'
          AND DATE(fec.date) BETWEEN '".$fecha_inicio."' AND '".$fecha_final."'
        ) t
         GROUP BY cedula,  Concepto ORDER BY cedula
        ");
        // dd($this->db->last_query());
        if($q!=NULL){
        if ($q->num_rows() > 0) {
            return $q->result_array();
        }
        }
        return FALSE;
    }

    public function get_all_fe(){
        $q = $this->db->query(
        "SELECT 
        `name`,
        id,
        SUM(t.tax_0)   tax_0,
        SUM(t.tax_1)   tax_1,
        SUM(t.tax_2)   tax_2,
        SUM(t.tax_4)   tax_4,
        SUM(t.tax_13)   tax_13,
        SUM(t.exonerado) exonerado,
        SUM(t.subtotal)   subtotal,
        SUM(t.total)   total
        FROM 
        (
          SELECT cu.`name`, cu.id,
         CASE 
           WHEN si.tax = 0
             THEN (si.item_tax)
           ELSE 0
         END AS tax_0,
         CASE 
           WHEN si.tax = 1
             THEN (si.item_tax)
           ELSE 0
         END AS tax_1,
         CASE 
           WHEN si.tax = 2
             THEN (si.item_tax)
           ELSE 0
         END AS tax_2,
         CASE 
           WHEN si.tax = 4
             THEN (si.item_tax)
           ELSE 0
         END AS tax_4,
         CASE 
           WHEN si.tax = 13 
           THEN (si.item_tax)
           ELSE 0
         END AS tax_13,
         (si.item_discount), (si.subtotal - si.item_tax) AS subtotal, (si.subtotal) AS total, (s.MontoExoneracion) as exonerado
          FROM tec_sales s
          INNER JOIN tec_sale_items si ON si.`sale_id` = s.`id`
          INNER JOIN `tec_customers` cu ON cu.`id` = s.`customer_id`
          WHERE s.customer_id IN(SELECT customer_id FROM tec_sales WHERE tipo_doc = 1 GROUP BY customer_id ORDER BY customer_id)
        ) t
        GROUP BY id,`name`");
        if($q!=NULL){
        if ($q->num_rows() > 0) {
            return $q->result_array();
        }
        }
        return FALSE;
    }

// ---------------------------Cierres de Caja Reporte------------------------------------------------------------
    public function getCloseRegister($user_id,$date) {
        $q = $this->db->get_where('registers', array('user_id' => $user_id,'date'=>$date), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    public function getRegisterCCSales($dateOpen = NULL,$dateClose = NULL, $user_id = NULL) {
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
                ->where('payments.date >', $dateOpen)
                ->where('payments.date <', $dateClose)
                ->where("{$this->db->dbprefix('payments')}.paid_by", 'CC');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        // dd($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCashSales($dateOpen = NULL,$dateClose = NULL, $user_id = NULL) {
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
                ->where('payments.date >', $dateOpen)
                ->where('payments.date <', $dateClose)
                ->where("{$this->db->dbprefix('payments')}.paid_by", 'cash');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        // dd($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterChSales($dateOpen = NULL,$dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $dateOpen)
                ->where('payments.date <', $dateClose)
                ->group_start()->where("{$this->db->dbprefix('payments')}.paid_by", 'Cheque')->or_where("{$this->db->dbprefix('payments')}.paid_by", 'cheque')->group_end();
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCCSalesApart($dateOpen = NULL,$dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }

        $this->db->select('COALESCE(SUM(COALESCE(amount,0)),0) AS total', FALSE)
                ->join('layaway', 'layaway.id=payments_apartado.apartado_id', 'left')
                ->where('payments_apartado.date >', $dateOpen)
                ->where('payments_apartado.date <', $dateClose)
                ->where("{$this->db->dbprefix('payments_apartado')}.paid_by", 'CC');
        $this->db->where('payments_apartado.created_by', $user_id);

        $q = $this->db->get('payments_apartado');

        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterTips($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_tips, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.product_code =', '9r091n4')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');
        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    
    public function getRegisterOtherSales($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $dateOpen)
                ->where('payments.date <', $dateClose)->where("{$this->db->dbprefix('payments')}.paid_by", 'other');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterGCSales($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $dateOpen)
                ->where('payments.date <', $dateClose)->where("{$this->db->dbprefix('payments')}.paid_by", 'gift_card');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterStripeSales($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COUNT(' . $this->db->dbprefix('payments') . '.id) as total_cheques, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( amount, 0 ) ) AS paid', FALSE)
                ->join('sales', 'sales.id=payments.sale_id', 'left')
                ->where('payments.date >', $dateOpen)
                ->where('payments.date <', $dateClose)->where("{$this->db->dbprefix('payments')}.paid_by", 'stripe');
        $this->db->where('payments.created_by', $user_id);

        $q = $this->db->get('payments');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterExpenses($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('SUM( COALESCE( amount, 0 ) ) AS total', FALSE)
                ->where('date >', $dateOpen)
                ->where('date <', $dateClose);
        $this->db->where('created_by', $user_id);

        $q = $this->db->get('expenses');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterNCSales($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {

        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }

        $this->db->select('SUM( COALESCE( grand_total, 0 ) ) AS total', FALSE)
                ->where('note_credits.date >', $dateOpen)
                ->where('note_credits.date <', $dateClose)
                ->group_by('created_by');
        $this->db->where('note_credits.created_by', $user_id);

        $q = $this->db->get('note_credits');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav1($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '1')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav2($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }


        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '2')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav3($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
            //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '3')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

            //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav4($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
            //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '4')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav5($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '5')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav6($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
            //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '6')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav7($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
                //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '7')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

            //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav8($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) 
    {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '8')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
                if ($q->num_rows() > 0) {
                    return $q->row();
                }
                return false;
    }

    public function getRegisterSalesGrav9($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
                if (!$user_id) {
                    $user_id = $this->session->userdata('user_id');
                }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '9')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav10($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '10')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav11($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '11')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav12($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_1, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '12')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesGrav13($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav_13, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax', '13')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');

        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesExce($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        //        $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sale_items') . '.id) as sales_grav, SUM( COALESCE( subtotal, 0 ) ) AS total', FALSE)
                ->join('sales', 'sales.id=tec_sale_items.sale_id', 'left')
                ->where('sale_items.tax =', '0')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sale_items');
        //        var_dump($this->db->last_query());
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterSalesCredit($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
       # $this->db->save_queries = TRUE;

        $this->db->select('COUNT(' . $this->db->dbprefix('sales') . '.id) as sales_credit, SUM( COALESCE( grand_total - paid, 0 ) ) AS total, SUM( COALESCE( paid, 0 ) ) AS paid', FALSE)
                ->where("{$this->db->dbprefix('sales')}.status <>", 'paid')
                ->where('sales.date >', $dateOpen)
                ->where('sales.date <', $dateClose);
        $this->db->where('sales.created_by', $user_id);
        $q = $this->db->get('sales');


        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getDepositos($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COALESCE(SUM(amount),0) AS total', FALSE)
                ->where('deposit.date >', $dateOpen)
                ->where('deposit.date <', $dateClose);
        $this->db->where('deposit.created_by', $user_id);
        $q = $this->db->get('deposit');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getRegisterCashSalesApart($dateOpen = NULL, $dateClose = NULL, $user_id = NULL) {
        if (!$user_id) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->db->select('COALESCE(SUM(COALESCE(amount,0)),0) AS total', FALSE)
                ->join('layaway', 'layaway.id=payments_apartado.apartado_id', 'left')
                ->where('payments_apartado.date >', $dateOpen)
                ->where('payments_apartado.date <', $dateClose)->where("{$this->db->dbprefix('payments_apartado')}.paid_by", 'cash');
        $this->db->where('payments_apartado.created_by', $user_id);

        $q = $this->db->get('payments_apartado');

        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
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

}

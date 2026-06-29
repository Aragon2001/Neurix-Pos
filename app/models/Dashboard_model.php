<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model {

    /* ══ VENTAS ══ */

    function getTotalSalesRange($from, $to, $store_id = 0) {
        $this->db->select('COUNT(*) as count,
            COALESCE(SUM(grand_total),0)    as total,
            COALESCE(SUM(paid),0)           as paid,
            COALESCE(SUM(total_tax),0)      as tax,
            COALESCE(SUM(total_discount),0) as discount');
        $this->db->from('sales');
        $this->db->where("DATE(date) >=", $from);
        $this->db->where("DATE(date) <=", $to);
        $this->db->where('is_return', 0);
        if ($store_id) $this->db->where('store_id', $store_id);
        return $this->db->get()->row();
    }

    function getSalesStatusBreakdown($from, $to, $store_id = 0) {
        $this->db->select('status, COUNT(*) as count, COALESCE(SUM(grand_total),0) as total');
        $this->db->from('sales');
        $this->db->where("DATE(date) >=", $from);
        $this->db->where("DATE(date) <=", $to);
        $this->db->where('is_return', 0);
        if ($store_id) $this->db->where('store_id', $store_id);
        $this->db->group_by('status');
        $rows = $this->db->get()->result();
        $out = ['paid' => 0, 'partial' => 0, 'due' => 0, 'overdue' => 0, 'suspend' => 0];
        foreach ($rows as $r) {
            $k = strtolower($r->status);
            if (array_key_exists($k, $out)) $out[$k] = (float)$r->total;
        }
        return $out;
    }

    /* ══ COMPRAS ══ */

    function getTotalPurchasesRange($from, $to, $store_id = 0) {
        $this->db->select('COUNT(*) as count, COALESCE(SUM(total),0) as total');
        $this->db->from('purchases');
        $this->db->where("DATE(date) >=", $from);
        $this->db->where("DATE(date) <=", $to);
        if ($store_id) $this->db->where('store_id', $store_id);
        return $this->db->get()->row();
    }

    /* ══ GASTOS ══ */

    function getTotalExpensesRange($from, $to, $store_id = 0) {
        $this->db->select('COUNT(*) as count, COALESCE(SUM(amount),0) as total');
        $this->db->from('expenses');
        $this->db->where("DATE(date) >=", $from);
        $this->db->where("DATE(date) <=", $to);
        if ($store_id) $this->db->where('store_id', $store_id);
        return $this->db->get()->row();
    }

    /* ══ CUENTAS × COBRAR ══ */

    function getAccountsReceivable($store_id = 0) {
        $this->db->select('COUNT(*) as count,
            COALESCE(SUM(grand_total),0)          as total,
            COALESCE(SUM(paid),0)                 as paid,
            COALESCE(SUM(grand_total - paid),0)   as balance');
        $this->db->from('sales');
        $this->db->where('is_return', 0);
        $this->db->where('grand_total > paid', null, false);
        if ($store_id) $this->db->where('store_id', $store_id);
        return $this->db->get()->row();
    }

    /* ══ ANÁLISIS FINANCIERO 12 MESES ══ */

    function getFinance12Months($store_id = 0) {
        $result = [];
        for ($i = 11; $i >= 0; $i--) {
            $ts   = strtotime("-{$i} months");
            $from = date('Y-m-01', $ts);
            $to   = date('Y-m-t',  $ts);

            $this->db->select('COALESCE(SUM(grand_total),0) as total, COALESCE(SUM(paid),0) as paid');
            $this->db->from('sales');
            $this->db->where("DATE(date) >=", $from);
            $this->db->where("DATE(date) <=", $to);
            $this->db->where('is_return', 0);
            if ($store_id) $this->db->where('store_id', $store_id);
            $s = $this->db->get()->row();

            $this->db->select('COALESCE(SUM(total),0) as total');
            $this->db->from('purchases');
            $this->db->where("DATE(date) >=", $from);
            $this->db->where("DATE(date) <=", $to);
            if ($store_id) $this->db->where('store_id', $store_id);
            $p = $this->db->get()->row();

            $this->db->select('COALESCE(SUM(amount),0) as total');
            $this->db->from('expenses');
            $this->db->where("DATE(date) >=", $from);
            $this->db->where("DATE(date) <=", $to);
            if ($store_id) $this->db->where('store_id', $store_id);
            $e = $this->db->get()->row();

            $sales = (float)$s->total;
            $purch = (float)$p->total;
            $exp   = (float)$e->total;

            $result[] = [
                'month'     => date('M Y', $ts),
                'sales'     => $sales,
                'purchases' => $purch,
                'expenses'  => $exp,
                'profit'    => $sales - $purch - $exp,
                'cobrado'   => (float)$s->paid,
            ];
        }
        return $result;
    }

    /* ══ FACTURAS ELECTRÓNICAS ══ */

    function getFEStats($from, $to) {
        $ht = $this->db->dbprefix('hacienda_tiketes');
        $rows = $this->db->query(
            "SELECT tipo_doc, estatus_hacienda, COUNT(*) as cnt
             FROM `{$ht}`
             WHERE DATE(fecha) >= ? AND DATE(fecha) <= ?
             GROUP BY tipo_doc, estatus_hacienda",
            [$from, $to]
        )->result();

        $out = [
            'total'       => 0,
            'aceptado'    => 0,
            'rechazado'   => 0,
            'procesando'  => 0,
            'error'       => 0,
            'fe_acept'    => 0, // tipo_doc 01 aceptado
            'fe_rech'     => 0, // tipo_doc 01 rechazado
            'tiq_acept'   => 0, // tipo_doc 04 aceptado
            'tiq_rech'    => 0, // tipo_doc 04 rechazado
            'nc_acept'    => 0, // tipo_doc 03 aceptado
            'nd_acept'    => 0, // tipo_doc 06 aceptado
        ];

        foreach ($rows as $r) {
            $cnt    = (int)$r->cnt;
            $status = strtolower($r->estatus_hacienda);
            $tipo   = $r->tipo_doc;

            $out['total'] += $cnt;
            if (array_key_exists($status, $out)) $out[$status] += $cnt;

            if ($tipo === '01' && $status === 'aceptado')  $out['fe_acept']  += $cnt;
            if ($tipo === '01' && $status === 'rechazado') $out['fe_rech']   += $cnt;
            if ($tipo === '04' && $status === 'aceptado')  $out['tiq_acept'] += $cnt;
            if ($tipo === '04' && $status === 'rechazado') $out['tiq_rech']  += $cnt;
            if ($tipo === '03' && $status === 'aceptado')  $out['nc_acept']  += $cnt;
            if ($tipo === '06' && $status === 'aceptado')  $out['nd_acept']  += $cnt;
        }
        return $out;
    }

    /* ══ INVENTARIO ══ */

    function getInventoryValue($store_id = 0) {
        $p = $this->db->dbprefix;
        if ($store_id) {
            return $this->db->query(
                "SELECT COALESCE(SUM(p.cost * psq.quantity),0)   AS cost_val,
                        COALESCE(SUM(psq.price * psq.quantity),0) AS price_val,
                        COALESCE(SUM(p.cost * psq.quantity * (p.tax/100)),0) AS tax_val,
                        COUNT(DISTINCT p.id)                      AS total_products
                 FROM `{$p}products` p
                 JOIN `{$p}product_store_qty` psq ON psq.product_id = p.id
                 WHERE psq.store_id = ? AND p.type = 'standard' AND psq.quantity > 0",
                [$store_id]
            )->row();
        }
        return $this->db->query(
            "SELECT COALESCE(SUM(cost * quantity),0)             AS cost_val,
                    COALESCE(SUM(price * quantity),0)            AS price_val,
                    COALESCE(SUM(cost * quantity * (tax/100)),0) AS tax_val,
                    COUNT(*) AS total_products
             FROM `{$p}products` WHERE type = 'standard' AND quantity > 0"
        )->row();
    }

    function getInventoryTaxBreakdown($store_id = 0) {
        $p = $this->db->dbprefix;
        if ($store_id) {
            return $this->db->query(
                "SELECT p.tax,
                        COALESCE(SUM(p.cost * psq.quantity),0)   AS cost_val,
                        COALESCE(SUM(psq.price * psq.quantity),0) AS price_val,
                        COUNT(DISTINCT p.id) AS products
                 FROM `{$p}products` p
                 JOIN `{$p}product_store_qty` psq ON psq.product_id = p.id
                 WHERE psq.store_id = ? AND p.type = 'standard' AND psq.quantity > 0
                 GROUP BY p.tax ORDER BY p.tax",
                [$store_id]
            )->result();
        }
        return $this->db->query(
            "SELECT tax,
                    COALESCE(SUM(cost * quantity),0)  AS cost_val,
                    COALESCE(SUM(price * quantity),0) AS price_val,
                    COUNT(*) AS products
             FROM `{$p}products` WHERE type = 'standard' AND quantity > 0
             GROUP BY tax ORDER BY tax"
        )->result();
    }

    function getSalesAvg6Months($store_id = 0) {
        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts   = strtotime("-{$i} months");
            $from = date('Y-m-01', $ts);
            $to   = date('Y-m-t',  $ts);

            $this->db->select('COALESCE(SUM(grand_total),0) as total, COUNT(*) as count');
            $this->db->from('sales');
            $this->db->where("DATE(date) >=", $from);
            $this->db->where("DATE(date) <=", $to);
            $this->db->where('is_return', 0);
            if ($store_id) $this->db->where('store_id', $store_id);
            $r = $this->db->get()->row();
            $result[] = ['month' => date('M', $ts), 'total' => (float)$r->total, 'count' => (int)$r->count];
        }
        return $result;
    }

    /* ══ ANÁLISIS DE VENTAS ══ */

    function getTopDays($from, $to, $store_id = 0) {
        $this->db->select("DATE(date) as day, DATE_FORMAT(date,'%a %d') as label,
                           COALESCE(SUM(grand_total),0) as total, COUNT(*) as count");
        $this->db->from('sales');
        $this->db->where("DATE(date) >=", $from);
        $this->db->where("DATE(date) <=", $to);
        $this->db->where('is_return', 0);
        if ($store_id) $this->db->where('store_id', $store_id);
        $this->db->group_by("DATE(date), DATE_FORMAT(date,'%a %d')");
        $this->db->order_by('total', 'DESC');
        $this->db->limit(10);
        return $this->db->get()->result();
    }

    function getTopProductsEmp($store_id = 0, $limit = 10) {
        $this->db->select('si.product_name, si.product_code, SUM(si.quantity) as qty, COALESCE(SUM(si.subtotal),0) as revenue');
        $this->db->from('sale_items si');
        $this->db->join('sales s', 's.id = si.sale_id');
        $this->db->where("DATE(s.date) >=", date('Y-m-01'));
        $this->db->where('s.is_return', 0);
        if ($store_id) $this->db->where('s.store_id', $store_id);
        $this->db->group_by('si.product_code, si.product_name');
        $this->db->order_by('revenue', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    function getPaymentMethodBreakdown($from, $to, $store_id = 0) {
        $this->db->select('p.paid_by, COUNT(*) as count, COALESCE(SUM(p.amount),0) as total');
        $this->db->from('payments p');
        $this->db->join('sales s', 's.id = p.sale_id');
        $this->db->where("DATE(p.date) >=", $from);
        $this->db->where("DATE(p.date) <=", $to);
        if ($store_id) $this->db->where('s.store_id', $store_id);
        $this->db->group_by('p.paid_by');
        $this->db->order_by('total', 'DESC');
        return $this->db->get()->result();
    }

    /* ══ CLIENTES ══ */

    function getTopCustomers($store_id = 0, $limit = 8) {
        $this->db->select('customer_name, customer_id, COUNT(*) as count, COALESCE(SUM(grand_total),0) as total');
        $this->db->from('sales');
        $this->db->where("DATE(date) >=", date('Y-m-01'));
        $this->db->where('is_return', 0);
        $this->db->where('customer_id >', 1);
        if ($store_id) $this->db->where('store_id', $store_id);
        $this->db->group_by('customer_id, customer_name');
        $this->db->order_by('total', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    function getTotalCustomers() {
        $this->db->select('COUNT(*) as count');
        $this->db->from('customers');
        $this->db->where('id >', 1);
        return $this->db->get()->row();
    }

    /* ══ STOCK BAJO ALERTA ══ */

    function getLowStockProducts($store_id = 0) {
        $p = $this->db->dbprefix;
        if ($store_id) {
            return $this->db->query(
                "SELECT p.id, p.code, p.name, p.alert_quantity, psq.quantity AS stock
                 FROM `{$p}products` p JOIN `{$p}product_store_qty` psq ON psq.product_id = p.id
                 WHERE psq.store_id = ? AND p.type != 'service'
                   AND p.alert_quantity > 0 AND psq.quantity <= p.alert_quantity
                 ORDER BY psq.quantity ASC LIMIT 10",
                [$store_id]
            )->result();
        }
        return $this->db->query(
            "SELECT id, code, name, alert_quantity, quantity AS stock FROM `{$p}products`
             WHERE type != 'service' AND alert_quantity > 0 AND quantity <= alert_quantity
             ORDER BY quantity ASC LIMIT 10"
        )->result();
    }

    /* ══ ÚLTIMAS VENTAS ══ */

    function getRecentSales($store_id = 0, $limit = 10) {
        $this->db->select('s.id, s.date, s.customer_name, s.grand_total, s.paid, s.status, h.estatus_hacienda');
        $this->db->from('sales s');
        $this->db->join('hacienda_tiketes h', 'h.sale_id = s.id', 'left');
        $this->db->where('s.is_return', 0);
        if ($store_id) $this->db->where('s.store_id', $store_id);
        $this->db->order_by('s.date', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    /* ══ TIENDAS / PERÍODO ══ */

    function getStores() {
        return $this->db->get('stores')->result();
    }
}

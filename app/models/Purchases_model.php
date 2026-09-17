<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Purchases_model extends CI_Model
{

    public function __construct() {
        parent::__construct();
    }

    public function getProductByID($id) {
        $q = $this->db->get_where('products', array('id' => $id), 1);
        if( $q->num_rows() > 0 ) {         
            return $q->row();
        }
        return FALSE;
    }

    public function getPurchaseByID($id) {
        $q = $this->db->get_where('purchases', array('id' => $id), 1);
        if( $q->num_rows() > 0 ) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllPurchaseItems($purchase_id) {
        $this->db->select('purchase_items.*, products.code as product_code, products.name as product_name')
            ->join('products', 'products.id=purchase_items.product_id', 'left')
            ->group_by('purchase_items.id')
            ->order_by('id', 'asc');
        $q = $this->db->get_where('purchase_items', array('purchase_id' => $purchase_id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    /**
     * Registra la compra, sus lineas y la entrada de inventario.
     *
     * Todo en una transaccion: sin ella un fallo a mitad dejaba la compra sin
     * lineas, o las existencias sumadas sin compra que las respalde.
     */
    public function addPurchase($data, $items) {
        $this->db->trans_begin();

        if (!$this->db->insert('purchases', $data)) {
            $this->db->trans_rollback();
            return false;
        }
        $purchase_id = $this->db->insert_id();

        foreach ($items as $item) {
            $item['purchase_id'] = $purchase_id;
            $price  = isset($item['price'])  ? $item['price']  : null;
            $margin = isset($item['margin']) ? $item['margin'] : null;
            $cost   = $item['cost'];
            unset($item['price'], $item['margin']);

            if (!$this->db->insert('purchase_items', $item)) {
                $this->db->trans_rollback();
                return false;
            }
            $this->sumarExistencia($item['product_id'], $data['store_id'], $item['quantity'], $price);
            $this->updatePrice($price, $margin, $cost, $item['product_id']);
            if (!empty($data['supplier_id'])) {
                // El primer proveedor queda como preferido; despues lo decide la ficha.
                $this->db->where('supplier_id IS NULL', null, false)
                    ->update('products', array('supplier_id' => (int) $data['supplier_id']), array('id' => $item['product_id']));
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return $purchase_id;
    }

    public function updatePrice($price, $margin, $cost, $product_id) {
        $campos = array('cost' => $cost);
        // Cero es un precio valido; "no lo cambies" llega como null.
        if ($price !== null && $price !== '') { $campos['price'] = $price; }
        if ($margin !== null && $margin !== '') { $campos['margen'] = $margin; }
        $this->db->update('products', $campos, array('id' => $product_id));
    }

    /**
     * Suma (o resta, con delta negativo) existencia en una tienda.
     *
     * La aritmetica va dentro de la sentencia: leer en PHP y escribir despues
     * pierde la venta que entre por el POS entre las dos operaciones.
     */
    public function sumarExistencia($product_id, $store_id, $delta, $price = null) {
        $psq = $this->db->dbprefix('product_store_qty');

        if ($this->getStoreQuantity($product_id, $store_id)) {
            $this->db->query(
                "UPDATE `{$psq}` SET quantity = quantity + ? WHERE product_id = ? AND store_id = ?",
                array($delta, $product_id, $store_id)
            );
            if ($price !== null && $price !== '') {
                $this->db->update('product_store_qty', array('price' => $price),
                    array('product_id' => $product_id, 'store_id' => $store_id));
            }
            return;
        }

        $this->db->insert('product_store_qty', array(
            'product_id' => $product_id,
            'store_id'   => $store_id,
            'quantity'   => max((float) $delta, 0),
            'price'      => $price !== null && $price !== '' ? $price : 0,
        ));
    }

    public function getStoreQuantity($product_id, $store_id) {
        $q = $this->db->get_where('product_store_qty', array('product_id' => $product_id, 'store_id' => $store_id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    /**
     * Reemplaza las lineas de una compra ajustando la existencia por diferencia.
     */
    public function updatePurchase($id, $data = NULL, $items = array()) {
        $compra = $this->getPurchaseByID($id);
        if (!$compra) {
            return false;
        }
        $store_id = isset($data['store_id']) ? $data['store_id'] : $compra->store_id;

        $this->db->trans_begin();

        foreach ($this->getAllPurchaseItems($id) as $oitem) {
            $this->sumarExistencia($oitem->product_id, $compra->store_id, -$oitem->quantity);
        }

        $this->db->update('purchases', $data, array('id' => $id));
        $this->db->delete('purchase_items', array('purchase_id' => $id));

        foreach ($items as $item) {
            $item['purchase_id'] = $id;
            $price  = isset($item['price'])  ? $item['price']  : null;
            $margin = isset($item['margin']) ? $item['margin'] : null;
            $cost   = $item['cost'];
            unset($item['price'], $item['margin']);

            if (!$this->db->insert('purchase_items', $item)) {
                $this->db->trans_rollback();
                return false;
            }
            $this->sumarExistencia($item['product_id'], $store_id, $item['quantity'], $price);
            $this->updatePrice($price, $margin, $cost, $item['product_id']);
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    public function deletePurchase($id) {
        $compra = $this->getPurchaseByID($id);
        if (!$compra) {
            return FALSE;
        }

        $this->db->trans_begin();
        foreach ($this->getAllPurchaseItems($id) as $oitem) {
            $this->sumarExistencia($oitem->product_id, $compra->store_id, -$oitem->quantity);
        }
        $this->db->delete('purchase_items', array('purchase_id' => $id));
        $this->db->delete('purchases', array('id' => $id));

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }
        $this->db->trans_commit();
        return TRUE;
    }

    public function getProductByCode($code) {
        $q = $this->db->get_where('products', array('code' => $code), 1);
        return $q->num_rows() > 0 ? $q->row() : FALSE;
    }

    public function getProductNames($term, $limit = 10) {
        // El termino viene del navegador: va escapado, nunca concatenado crudo.
        $t = $this->db->escape_like_str($term);
        if ($this->db->dbdriver == 'sqlite3') {
            $this->db->where("type != 'combo' AND (name LIKE '%{$t}%' ESCAPE '!' OR code LIKE '%{$t}%' ESCAPE '!' OR (name || ' (' || code || ')') LIKE '%{$t}%' ESCAPE '!')", NULL, FALSE);
        } else {
            $this->db->where("type != 'combo' AND (name LIKE '%{$t}%' ESCAPE '!' OR code LIKE '%{$t}%' ESCAPE '!' OR concat(name, ' (', code, ')') LIKE '%{$t}%' ESCAPE '!')", NULL, FALSE);
        }
        $this->db->limit($limit);
        $q = $this->db->get('products');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getExpenseByID($id) {
        $q = $this->db->get_where('expenses', array('id' => $id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function addExpense($data = array()) {
        if ($this->db->insert('expenses', $data)) {
            return true;
        }
        return false;
    }

    public function updateExpense($id, $data = array()) {
        if ($this->db->update('expenses', $data, array('id' => $id))) {
            return true;
        }
        return false;
    }

    public function deleteExpense($id) {
        if ($this->db->delete('expenses', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

}

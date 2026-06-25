<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Products_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function getAllProducts() {
        $q = $this->db->get('products');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function products_count($category_id = NULL) {
        if ($category_id) {
            $this->db->where('category_id', $category_id);
            return $this->db->count_all_results("products");
        } else {
            return $this->db->count_all("products");
        }
    }

    public function fetch_products($limit, $start = null, $category_id = NULL) {
        $this->db->select('name, code, barcode_symbology, price')
                ->limit($limit, $start)->order_by("code", "asc");
        if ($category_id) {
            $this->db->where('category_id', $category_id);
        }
        $q = $this->db->get("products");

        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getProductByCode($code) {
        $q = $this->db->get_where('products', array('code' => $code), 1);
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
            return $row;
        }
        return FALSE;
    }

    public function addProduct($data, $store_quantities, $items = array()) {
        if ($this->db->insert('products', $data)) {
            $product_id = $this->db->insert_id();
            if (!empty($store_quantities)) {
                foreach ($store_quantities as $store_quantity) {
                    $store_quantity['product_id'] = $product_id;
                    $this->db->insert('product_store_qty', $store_quantity);
                }
            }
            if (!empty($items)) {
                foreach ($items as $item) {
                    $item['product_id'] = $product_id;
                    $this->db->insert('combo_items', $item);
                }
            }
            return $product_id;
        }
        return false;
    }

    public function addPrices($data)
    {
        if ($this->db->insert('lista_precios', $data)) 
        {
            return true;
        }
        return false;
    }

    public function deletePrices($id) {
        if ($this->db->delete('lista_precios', array('id_lista_precios' => $id))) {
            return true;
        }
        return FALSE;
    }

    public function getPricesById($id)
    {
        $q = $this->db->get_where('lista_precios', array('id_lista_precios' => $id));
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function updateListPrices($data,$id)
    {
        if ($this->db->update('lista_precios', $data, array('id_lista_precios' => $id))) {
            return true;
        }
        return false;
    }

    public function getUbicacionesbyId($id)
    {
        $q = $this->db->get_where('ubicaciones', array('id_producto' => $id));
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }
	
    public function addUbicaciones($ubicaciones, $productid)
    {
        //        $this->db->save_queries = TRUE;
        $this->db->delete('ubicaciones', array('id_producto' => $productid));
        $this->db->insert_batch('ubicaciones', $ubicaciones);
        $u = '';
        foreach ($ubicaciones as $row){
            $u .= $row['seccion']. '/'.$row['tramo'] .". ";
        }
        $this->db->update('products', array('ubicacion' => $u), array('id' => $productid));
        //        var_dump($this->db->last_query());
        //        dd('asd');

    }
	
    public function add_products($data = array()) {
        if ($this->db->insert_batch('products', $data)) {
            return true;
        }
        return false;
    }

    public function updatePrice($data = array()) {
        if ($this->db->update_batch('products', $data, 'code')) {
            return true;
        }
        return false;
    }

    public function updateProduct($id, $data = array(), $store_quantities = array(), $items = array(), $photo = NULL) {
        if ($photo) {
            $data['image'] = $photo;
        }
        if ($this->db->update('products', $data, array('id' => $id))) {
            if (!empty($store_quantities)) {
                foreach ($store_quantities as $store_quantity) {
                    $store_quantity['product_id'] = $id;
                    $this->setStoreQuantity($store_quantity);
                }
            }
            if (!empty($items)) {
                $this->db->delete('combo_items', array('product_id' => $id));
                foreach ($items as $item) {
                    $item['product_id'] = $id;
                    $this->db->insert('combo_items', $item);
                }
            }
            return true;
        }
        return false;
    }

    public function setStoreQuantity($data) {
        if ($this->getStoreQuantity($data['product_id'], $data['store_id'])) {
            if (isset($data['qty_fracc'])) {
                $this->db->update('product_store_qty', array('quantity' => $data['quantity'], 'qty_fracc' => $data['qty_fracc'], 'price' => $data['price']), array('product_id' => $data['product_id'], 'store_id' => $data['store_id']));
            } else {
                $this->db->update('product_store_qty', array('quantity' => $data['quantity'], 'price' => $data['price']), array('product_id' => $data['product_id'], 'store_id' => $data['store_id']));
            }
        } else {
            $this->db->insert('product_store_qty', $data);
        }
    }

    public function getStoreQuantity($product_id, $store_id = NULL) {
        if (!$store_id) {
            $store_id = $this->session->userdata('store_id') ? $this->session->userdata('store_id') : 1;
        }
        $q = $this->db->get_where('product_store_qty', array('product_id' => $product_id, 'store_id' => $store_id), 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getStoresQuantity($product_id) {
        $q = $this->db->get_where('product_store_qty', array('product_id' => $product_id));
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getComboItemsByPID($product_id) {
        $this->db->select($this->db->dbprefix('products') . '.id as id, ' . $this->db->dbprefix('products') . '.code as code, ' . $this->db->dbprefix('combo_items') . '.quantity as qty, ' . $this->db->dbprefix('products') . '.name as name')
                ->join('products', 'products.code=combo_items.item_code', 'left')
                ->group_by('combo_items.id');
        $q = $this->db->get_where('combo_items', array('product_id' => $product_id));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function deleteProduct($id) {
        $this->db->save_queries = TRUE;
        if ($this->db->delete('products', array('id' => $id)) && $this->db->delete('product_prices', array('product_id' => $id))) {
            return true;
        }
        return FALSE;
    }

    public function getProductNames($term, $limit = 10) {
        if ($this->db->dbdriver == 'sqlite3') {
            $this->db->where("type != 'combo' AND (name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR  (name || ' (' || code || ')') LIKE '%" . $term . "%')");
        } else {
            $this->db->where("type != 'combo' AND (name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR  concat(name, ' (', code, ')') LIKE '%" . $term . "%')");
        }
        $this->db->limit($limit);
        $q = $this->db->get('products');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                if (isset($row->tax_method) and $row->tax_method == '0') {
                $row->tax_method = '1';
                if ($row->tax > 0) {
                    $invertir_impuesto = $row->price / (1 + ($row->tax / 100));
                    $row->price = number_format($invertir_impuesto, 4, '.', '');
                    $row->store_price = number_format($invertir_impuesto, 4, '.', '');
                }
            }
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function AddMovimento($mov) {
        $this->db->insert('mov_inventario', $mov);
    }

    public function CambioPrecio($price, $product_id, $store_id = null) {
        if(!$store_id){
            $store_id = $this->session->userdata('store_id');
        }
        if ($store_qty = $this->getStoreQuantity($product_id, $store_id)) {
            if ($price) {
                $this->db->update('product_store_qty', array('price' => $price), array('product_id' => $product_id, 'store_id' => $store_id));
                $this->db->update('products', array('price' => $price), array('id' => $product_id));
            }
        }
    }

    public function EdicionRapida($quantity, $qtyfracc, $price, $product_id, $store_id = null) {
        if(!$store_id){
            $store_id = $this->session->userdata('store_id');
        }
        
        if ($store_qty = $this->getStoreQuantity($product_id, $store_id)) {
            if ($qtyfracc) {
                $this->db->update('product_store_qty', array('qty_fracc' => $qtyfracc), array('product_id' => $product_id, 'store_id' => $store_id));
            }
            if ($quantity) {
                $this->db->update('product_store_qty', array('quantity' => $quantity), array('product_id' => $product_id, 'store_id' => $store_id));
                $this->db->update('products', array('quantity' => $quantity), array('id' => $product_id));
            }

            if ($price) {
                $this->db->update('product_store_qty', array('price' => $price), array('product_id' => $product_id, 'store_id' => $store_id));
                $this->db->update('products', array('price' => $price), array('id' => $product_id));
            }
        } else {
            $this->db->insert('product_store_qty', array('product_id' => $product_id, 'store_id' => $store_id, 'quantity' => $quantity ? $quantity : 0, 'qty_fracc' => $qtyfracc ? $qtyfracc : 0, 'price' => $price ? $price : 0));
        }
    }

    public function AumentaInventario($quantity, $qtyfracc, $price, $product_id, $store_id = null) {

        if(!$store_id){
            $store_id = $this->session->userdata('store_id');
        }
        
        if ($store_qty = $this->getStoreQuantity($product_id, $store_id)) {
            if ($qtyfracc) {
                $this->db->update('product_store_qty', array('qty_fracc' => ($store_qty->qty_fracc + $qtyfracc)), array('product_id' => $product_id, 'store_id' => $store_id));
            }
            if ($quantity) {
                $this->db->update('product_store_qty', array('quantity' => ($store_qty->quantity + $quantity)), array('product_id' => $product_id, 'store_id' => $store_id));
                $this->db->update('products', array('quantity' => ($store_qty->quantity + $quantity)), array('id' => $product_id));
            }

            if ($price) {
                $this->db->update('product_store_qty', array('price' => $price), array('product_id' => $product_id, 'store_id' => $store_id));
                $this->db->update('products', array('price' => $price), array('id' => $product_id));
            }
        } else {
            $this->db->insert('product_store_qty', array('product_id' => $product_id, 'store_id' => $store_id, 'quantity' => $quantity ? $quantity : 0, 'qty_fracc' => $qtyfracc ? $qtyfracc : 0, 'price' => $price ? $price : 0));
        }
    }

    public function DisminuyeInventario($quantity, $qtyfracc, $price, $product_id, $store_id = null) {
  
        if(!$store_id){
            $store_id = $this->session->userdata('store_id');
        }
        if ($store_qty = $this->getStoreQuantity($product_id, $store_id)) {
            if ($qtyfracc) {
                $this->db->update('product_store_qty', array('qty_fracc' => ($store_qty->qty_fracc - $qtyfracc)), array('product_id' => $product_id, 'store_id' => $store_id));
            }
            if ($quantity) {
                $this->db->update('product_store_qty', array('quantity' => ($store_qty->quantity - $quantity)), array('product_id' => $product_id, 'store_id' => $store_id));
                $this->db->update('products', array('quantity' => ($store_qty->quantity - $quantity)), array('id' => $product_id));
            }

            if ($price) {
                $this->db->update('product_store_qty', array('price' => $price), array('product_id' => $product_id, 'store_id' => $store_id));
                $this->db->update('products', array('price' => $price), array('id' => $product_id));
            }
        } else {
            $this->db->insert('product_store_qty', array('product_id' => $product_id, 'store_id' => $store_id, 'quantity' => $quantity ? $quantity : 0, 'qty_fracc' => $qtyfracc ? $qtyfracc : 0, 'price' => $price ? $price : 0));
        }
    }

}

<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Categories_model extends CI_Model
{

    public function __construct() {
        parent::__construct();
    }

    public function addCategory($data) {
        if ($this->db->insert('categories', $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    public function add_categories($data = array()) {
        if ($this->db->insert_batch('categories', $data)) {
            return true;
        }
        return false;
    }

    public function updateCategory($id, $data = NULL) {
        if ($this->db->update('categories', $data, array('id' => $id))) {
            return true;
        }
        return false;
    }

    public function deleteCategory($id) {
        if ($this->db->delete('categories', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

    /**
     * El codigo es la llave con la que la importacion de productos enlaza su
     * categoria (`getCategoryByCode`), asi que repetirlo deja al importador
     * eligiendo una de las dos al azar.
     *
     * @param int|null $excepto_id la propia categoria, al editarla
     */
    public function codigoUsado($code, $excepto_id = NULL) {
        $this->db->where('code', $code);
        if ($excepto_id) {
            $this->db->where('id !=', (int) $excepto_id);
        }
        return $this->db->count_all_results('categories') > 0;
    }

    /** Productos que quedarian sin categoria si se borrara. */
    public function contarProductos($id) {
        return (int) $this->db->where('category_id', (int) $id)->count_all_results('products');
    }

    /** Numero de productos por categoria, indexado por category_id. */
    public function conteoPorCategoria() {
        $q = $this->db->select('category_id, COUNT(*) as n', FALSE)
                      ->where('category_id IS NOT NULL', NULL, FALSE)
                      ->group_by('category_id')
                      ->get('products');
        $out = array();
        foreach ($q->result() as $r) {
            $out[(int) $r->category_id] = (int) $r->n;
        }
        return $out;
    }

    /**
     * Cifras de la ficha: cuantos productos, cuantas existencias y cuanto vale
     * el inventario de la categoria en la tienda indicada.
     */
    public function resumen($id, $store_id) {
        $psq = $this->db->dbprefix('product_store_qty');
        $q = $this->db->select("COUNT(*) as productos,"
                . " SUM(CASE WHEN {$this->db->dbprefix('products')}.type = 'service' THEN 1 ELSE 0 END) as servicios,"
                . " COALESCE(SUM(e.quantity), 0) as existencias,"
                . " COALESCE(SUM(e.quantity * {$this->db->dbprefix('products')}.cost), 0) as valor_costo,"
                . " COALESCE(SUM(e.quantity * {$this->db->dbprefix('products')}.price), 0) as valor_venta,"
                . " SUM(CASE WHEN COALESCE(e.quantity, 0) <= 0 THEN 1 ELSE 0 END) as sin_existencias", FALSE)
            ->join("( SELECT product_id, SUM(quantity) as quantity FROM `{$psq}`"
                 . " WHERE store_id = " . (int) $store_id . " GROUP BY product_id) e",
                   'products.id = e.product_id', 'left')
            ->where('products.category_id', (int) $id)
            ->get('products');
        return $q->row();
    }

    /** Los productos mas caros de la categoria, para asomarlos en la ficha. */
    public function productosDe($id, $store_id, $limite = 8) {
        $psq = $this->db->dbprefix('product_store_qty');
        return $this->db->select("products.id, products.code, products.name, products.type,"
                . " products.price, products.image, COALESCE(e.quantity, 0) as quantity", FALSE)
            ->join("( SELECT product_id, SUM(quantity) as quantity FROM `{$psq}`"
                 . " WHERE store_id = " . (int) $store_id . " GROUP BY product_id) e",
                   'products.id = e.product_id', 'left')
            ->where('products.category_id', (int) $id)
            ->order_by('products.name', 'ASC')
            ->limit((int) $limite)
            ->get('products')->result();
    }

}

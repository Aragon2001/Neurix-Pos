<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
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

    /**
     * Productos de una categoria con la existencia y el precio de la tienda.
     *
     * Trae el dato de `product_store_qty` en la misma consulta: rotular una
     * gondola entera con una consulta por producto son cientos de viajes.
     */
    public function getProductsByCategory($category_id, $store_id = NULL, $limit = 500)
    {
        $store_id = $store_id ?: (int) ($this->session->userdata('store_id') ?: 1);
        $p   = $this->db->dbprefix('products');
        $psq = $this->db->dbprefix('product_store_qty');

        $this->db->select("{$p}.*, COALESCE(PSQ.quantity, 0) as psq_qty, COALESCE(PSQ.price, {$p}.price) as psq_price", FALSE)
                ->join("( SELECT product_id, quantity, price FROM `{$psq}` WHERE store_id = " . (int) $store_id . " ) AS PSQ", 'PSQ.product_id=products.id', 'left')
                ->where('products.category_id', (int) $category_id)
                ->where('products.type !=', 'combo')
                ->order_by('products.name', 'asc')
                ->limit((int) $limit);

        $q = $this->db->get('products');
        if (!$q->num_rows()) {
            return array();
        }

        $filas = array();
        foreach ($q->result() as $row) {
            // El resto del modelo entrega siempre el precio neto: aqui tambien,
            // o la etiqueta sumaria el impuesto dos veces.
            if (isset($row->tax_method) and $row->tax_method == '0') {
                $row->tax_method = '1';
                if ($row->tax > 0) {
                    $row->price     = number_format($row->price / (1 + ($row->tax / 100)), 4, '.', '');
                    $row->psq_price = number_format($row->psq_price / (1 + ($row->tax / 100)), 4, '.', '');
                }
            }
            $filas[] = $row;
        }
        return $filas;
    }

    public function AddMovimento($mov) {
        $this->db->insert('mov_inventario', $mov);
    }

    /**
     * Aplica un movimiento de inventario y lo deja registrado.
     *
     * Toda la aritmetica va en la sentencia (`quantity = quantity + ?`): leer en
     * PHP y escribir despues pierde la venta que entre por el POS mientras tanto.
     *
     * @param  array $mov producto, tienda y modo:
     *                    'conteo'  la cantidad reemplaza la existencia
     *                    'entrada' la suma
     *                    'salida'  la resta
     *                    'precio'  no toca existencias
     *                    Las claves `quantity`, `qty_fracc`, `price` y `cost`
     *                    valen `null` para "no cambiar": cero es un valor.
     * @return array{ok: bool, error: string, qty_antes: float, qty_despues: float}
     */
    public function aplicarMovimiento(array $mov)
    {
        $modo       = isset($mov['modo']) ? $mov['modo'] : 'conteo';
        $product_id = (int) $mov['product_id'];
        $store_id   = !empty($mov['store_id'])
            ? (int) $mov['store_id']
            : (int) ($this->session->userdata('store_id') ?: 1);

        $cantidad   = $this->_valorOpcional($mov, 'quantity');
        $fracciones = $this->_valorOpcional($mov, 'qty_fracc');
        $precio     = $this->_valorOpcional($mov, 'price');
        $costo      = $this->_valorOpcional($mov, 'cost');

        $fila     = $this->getStoreQuantity($product_id, $store_id);
        $producto = $this->db->get_where('products', array('id' => $product_id), 1)->row();
        if (!$producto) {
            return array('ok' => false, 'error' => 'producto_inexistente', 'qty_antes' => 0, 'qty_despues' => 0);
        }

        $qty_antes  = $fila ? (float) $fila->quantity : 0;
        $costo_ant  = (float) $producto->cost;
        $precio_ant = ($fila && $fila->price !== null) ? (float) $fila->price : (float) $producto->price;

        // El saldo resultante se calcula antes de tocar nada, para poder
        // rechazar el negativo sin haber escrito.
        $qty_despues = $qty_antes;
        if ($modo !== 'precio' && $cantidad !== null) {
            if ($modo === 'conteo') {
                $qty_despues = $cantidad;
            } elseif ($modo === 'entrada') {
                $qty_despues = $qty_antes + $cantidad;
            } elseif ($modo === 'salida') {
                $qty_despues = $qty_antes - $cantidad;
            }
        }
        if ($qty_despues < 0 && empty($mov['permitir_negativo'])) {
            return array('ok' => false, 'error' => 'existencia_negativa', 'qty_antes' => $qty_antes, 'qty_despues' => $qty_despues);
        }

        $psq   = $this->db->dbprefix('product_store_qty');
        $donde = array('product_id' => $product_id, 'store_id' => $store_id);

        if (!$fila) {
            $this->db->insert('product_store_qty', array(
                'product_id' => $product_id,
                'store_id'   => $store_id,
                'quantity'   => $modo === 'precio' ? 0 : max($qty_despues, 0),
                'qty_fracc'  => $fracciones !== null ? $fracciones : 0,
                'price'      => $precio !== null ? $precio : $precio_ant,
            ));
        } else {
            if ($modo !== 'precio' && $cantidad !== null) {
                if ($modo === 'conteo') {
                    $this->db->update('product_store_qty', array('quantity' => $qty_despues), $donde);
                } else {
                    $delta = $modo === 'entrada' ? $cantidad : -$cantidad;
                    $this->db->query(
                        "UPDATE `{$psq}` SET quantity = quantity + ? WHERE product_id = ? AND store_id = ?",
                        array($delta, $product_id, $store_id)
                    );
                }
            }
            if ($fracciones !== null) {
                if ($modo === 'conteo' || $modo === 'precio') {
                    $this->db->update('product_store_qty', array('qty_fracc' => $fracciones), $donde);
                } else {
                    $delta = $modo === 'entrada' ? $fracciones : -$fracciones;
                    $this->db->query(
                        "UPDATE `{$psq}` SET qty_fracc = qty_fracc + ? WHERE product_id = ? AND store_id = ?",
                        array($delta, $product_id, $store_id)
                    );
                }
            }
            if ($precio !== null) {
                $this->db->update('product_store_qty', array('price' => $precio), $donde);
            }
        }

        if ($precio !== null) {
            $this->db->update('products', array('price' => $precio), array('id' => $product_id));
        }

        // Costo promedio ponderado: solo la entrada de mercaderia lo mueve.
        $costo_act = $costo_ant;
        if ($costo !== null) {
            $costo_act = ($modo === 'entrada' && $cantidad > 0 && $qty_antes > 0)
                ? ((($qty_antes * $costo_ant) + ($cantidad * $costo)) / ($qty_antes + $cantidad))
                : $costo;
            $this->db->update('products', array('cost' => round($costo_act, 4)), array('id' => $product_id));
        }

        $this->AddMovimento(array(
            'tipo_mov'        => $this->tipoMovimiento($modo),
            'descripcion_mov' => isset($mov['descripcion_mov']) ? mb_substr((string) $mov['descripcion_mov'], 0, 255) : '',
            'quantity_mov'    => $cantidad !== null ? $cantidad : 0,
            'qty_fracc_mov'   => $fracciones !== null ? $fracciones : 0,
            'id_product'      => $product_id,
            'id_usuario'      => (int) ($this->session->userdata('user_id') ?: 0),
            'precio_ant'      => $precio_ant,
            'precio_act'      => $precio !== null ? $precio : $precio_ant,
            'qty_antes'       => $qty_antes,
            'qty_despues'     => $modo === 'precio' ? $qty_antes : $qty_despues,
            'store_id'        => $store_id,
            'id_sesion'       => isset($mov['id_sesion']) ? $mov['id_sesion'] : null,
            'costo_ant'       => $costo_ant,
            'costo_act'       => round($costo_act, 4),
        ));

        return array(
            'ok'          => true,
            'error'       => '',
            'qty_antes'   => $qty_antes,
            'qty_despues' => $modo === 'precio' ? $qty_antes : $qty_despues,
        );
    }

    /**
     * Cero es un valor valido y cadena vacia no: `if ($x)` descarta los dos.
     */
    private function _valorOpcional(array $mov, $clave)
    {
        if (!array_key_exists($clave, $mov)) {
            return null;
        }
        $v = $mov[$clave];
        if ($v === null || $v === '' || $v === false) {
            return null;
        }
        return (float) $v;
    }

    /**
     * Codigo historico de `mov_inventario`: 0 salida, 1 entrada, 2 conteo,
     * 3 cambio de precio.
     */
    public function tipoMovimiento($modo)
    {
        $mapa = array('salida' => 0, 'entrada' => 1, 'conteo' => 2, 'precio' => 3);
        return isset($mapa[$modo]) ? $mapa[$modo] : 2;
    }

    /**
     * Sesion de conteo completa: o entran todas las lineas, o no entra ninguna.
     *
     * @param  array $lineas cada una con las claves de aplicarMovimiento()
     * @return array{ok: bool, aplicadas: int, errores: array, id_sesion: string}
     */
    public function aplicarSesion(array $lineas, $id_sesion = null)
    {
        $id_sesion = $id_sesion ?: uniqid('inv');
        $errores   = array();

        $this->db->trans_begin();
        foreach ($lineas as $i => $linea) {
            $linea['id_sesion'] = $id_sesion;
            $r = $this->aplicarMovimiento($linea);
            if (!$r['ok']) {
                $errores[] = array(
                    'linea'      => $i,
                    'product_id' => isset($linea['product_id']) ? (int) $linea['product_id'] : 0,
                    'error'      => $r['error'],
                );
            }
        }

        if ($errores || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return array('ok' => false, 'aplicadas' => 0, 'errores' => $errores, 'id_sesion' => $id_sesion);
        }

        $this->db->trans_commit();
        return array('ok' => true, 'aplicadas' => count($lineas), 'errores' => array(), 'id_sesion' => $id_sesion);
    }

    public function CambioPrecio($price, $product_id, $store_id = null) {
        return $this->aplicarMovimiento(array(
            'modo' => 'precio', 'product_id' => $product_id, 'store_id' => $store_id, 'price' => $price,
        ));
    }

    public function EdicionRapida($quantity, $qtyfracc, $price, $product_id, $store_id = null) {
        return $this->aplicarMovimiento(array(
            'modo' => 'conteo', 'product_id' => $product_id, 'store_id' => $store_id,
            'quantity' => $quantity, 'qty_fracc' => $qtyfracc, 'price' => $price,
        ));
    }

    public function AumentaInventario($quantity, $qtyfracc, $price, $product_id, $store_id = null) {
        return $this->aplicarMovimiento(array(
            'modo' => 'entrada', 'product_id' => $product_id, 'store_id' => $store_id,
            'quantity' => $quantity, 'qty_fracc' => $qtyfracc, 'price' => $price,
        ));
    }

    public function DisminuyeInventario($quantity, $qtyfracc, $price, $product_id, $store_id = null) {
        return $this->aplicarMovimiento(array(
            'modo' => 'salida', 'product_id' => $product_id, 'store_id' => $store_id,
            'quantity' => $quantity, 'qty_fracc' => $qtyfracc, 'price' => $price,
        ));
    }

}

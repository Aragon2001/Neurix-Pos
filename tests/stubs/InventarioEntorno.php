<?php

/**
 * Entorno mínimo para probar Products_model sin CodeIgniter.
 *
 * La base falsa guarda las tres tablas en memoria y entiende las dos sentencias
 * incrementales que usa el modelo, que son justamente las que no se pueden
 * verificar mirando el código.
 */

if (!class_exists('CI_Model')) {
    class CI_Model
    {
        public $db;
        public $session;

        public function __construct() {}
    }
}

class FilaFalsa
{
    private $filas;

    public function __construct(array $filas)
    {
        $this->filas = array_values($filas);
    }

    public function num_rows()
    {
        return count($this->filas);
    }

    public function row()
    {
        return $this->filas ? (object) $this->filas[0] : null;
    }

    public function result()
    {
        return array_map(function ($f) { return (object) $f; }, $this->filas);
    }
}

class SesionFalsa
{
    private $datos;

    public function __construct(array $datos = array())
    {
        $this->datos = $datos + array('store_id' => 1, 'user_id' => 7);
    }

    public function userdata($clave)
    {
        return isset($this->datos[$clave]) ? $this->datos[$clave] : null;
    }
}

class BaseFalsa
{
    public $tablas = array(
        'products'          => array(),
        'product_store_qty' => array(),
        'mov_inventario'    => array(),
    );

    /** Copia de las tablas al abrir la transacción, para poder deshacer. */
    private $respaldo = null;
    private $ok = true;

    public function dbprefix($tabla = '')
    {
        return 'tec_' . $tabla;
    }

    private function sinPrefijo($tabla)
    {
        return preg_replace('/^tec_/', '', trim($tabla, '`'));
    }

    private function coincide(array $fila, array $donde)
    {
        foreach ($donde as $k => $v) {
            if (!array_key_exists($k, $fila) || (string) $fila[$k] !== (string) $v) {
                return false;
            }
        }
        return true;
    }

    public function get_where($tabla, $donde = array(), $limite = null)
    {
        $tabla = $this->sinPrefijo($tabla);
        $out = array();
        foreach ($this->tablas[$tabla] as $fila) {
            if ($this->coincide($fila, (array) $donde)) {
                $out[] = $fila;
                if ($limite && count($out) >= $limite) break;
            }
        }
        return new FilaFalsa($out);
    }

    public function insert($tabla, $datos)
    {
        $this->tablas[$this->sinPrefijo($tabla)][] = (array) $datos;
        return true;
    }

    public function update($tabla, $datos, $donde = array())
    {
        $tabla = $this->sinPrefijo($tabla);
        foreach ($this->tablas[$tabla] as $i => $fila) {
            if ($this->coincide($fila, (array) $donde)) {
                $this->tablas[$tabla][$i] = array_merge($fila, (array) $datos);
            }
        }
        return true;
    }

    /** Solo entiende los dos incrementos del modelo. */
    public function query($sql, $binds = array())
    {
        if (preg_match('/UPDATE `?tec_product_store_qty`? SET (quantity|qty_fracc) = \1 \+ \?/i', $sql, $m)) {
            list($delta, $product_id, $store_id) = $binds;
            foreach ($this->tablas['product_store_qty'] as $i => $fila) {
                if ((int) $fila['product_id'] === (int) $product_id && (int) $fila['store_id'] === (int) $store_id) {
                    $this->tablas['product_store_qty'][$i][$m[1]] = (float) $fila[$m[1]] + (float) $delta;
                }
            }
            return true;
        }
        throw new RuntimeException('Sentencia no contemplada por la base falsa: ' . $sql);
    }

    public function trans_begin()
    {
        $this->respaldo = $this->tablas;
        $this->ok = true;
    }

    public function trans_status()
    {
        return $this->ok;
    }

    public function trans_rollback()
    {
        if ($this->respaldo !== null) {
            $this->tablas = $this->respaldo;
        }
        $this->respaldo = null;
    }

    public function trans_commit()
    {
        $this->respaldo = null;
    }
}

require_once dirname(__DIR__, 2) . '/app/models/Products_model.php';

/** Devuelve el modelo con una base falsa ya poblada. */
function inventario_modelo(array $productos, array $existencias)
{
    $modelo = new Products_model();
    $modelo->db = new BaseFalsa();
    $modelo->session = new SesionFalsa();
    $modelo->db->tablas['products'] = $productos;
    $modelo->db->tablas['product_store_qty'] = $existencias;
    return $modelo;
}

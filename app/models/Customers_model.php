<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Customers_model extends CI_Model
{

    public function __construct() {
        parent::__construct();
    }

    public function getCustomerByID($id) {
        $q = $this->db->get_where('customers', array('id' => $id), 1);
        if( $q->num_rows() > 0 ) {
            return $q->row();
        }
        return FALSE;
    }

    /**
     * Busca por identificacion ignorando guiones y espacios: el mismo numero
     * puede haberse guardado como "1-1234-0567" o "112340567".
     *
     * @param int|null $excluir_id  Cliente que no cuenta como duplicado (edicion)
     */
    public function getCustomerByCedula($cf2, $excluir_id = NULL) {
        $cf2 = preg_replace('/[\s-]/', '', (string) $cf2);
        if ($cf2 === '') {
            return FALSE;
        }
        // where(..., FALSE) tampoco escapa el valor, asi que se escapa aparte.
        $this->db->where("REPLACE(REPLACE(cf2,'-',''),' ','') = " . $this->db->escape($cf2), NULL, FALSE);
        if ($excluir_id) {
            $this->db->where('id !=', $excluir_id);
        }
        $q = $this->db->get('customers', 1);
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    /**
     * customers.cf2 es UNIQUE: con db_debug activo un choque contra ese indice
     * pinta la pantalla de error de base de datos en vez de devolver false, y
     * en una peticion AJAX rompe el JSON. El controlador decide que mostrar.
     */
    public function addCustomer($data = array()) {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $ok = $this->db->insert('customers', $data);
        $this->db->db_debug = $debug;

        return $ok ? $this->db->insert_id() : false;
    }

    public function updateCustomer($id, $data = array()) {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $ok = $this->db->update('customers', $data, array('id' => $id));
        $this->db->db_debug = $debug;

        return (bool) $ok;
    }

    public function deleteCustomer($id) {
        if($this->db->delete('customers', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

}

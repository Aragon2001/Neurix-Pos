<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Suppliers_model extends CI_Model
{

    public function __construct() {
        parent::__construct();
    }

    public function getSupplierByID($id) {
        $q = $this->db->get_where('suppliers', array('id' => $id), 1);
        if( $q->num_rows() > 0 ) {
            return $q->row();
        }
        return FALSE;
    }

    /**
     * Proveedor por identificacion, saltandose el que se esta editando.
     *
     * @param string   $cf2     identificacion ya normalizada
     * @param int|null $excluir id que no cuenta como duplicado
     */
    public function getSupplierByCedula($cf2, $excluir = NULL) {
        $cf2 = trim((string) $cf2);
        if ($cf2 === '') {
            return FALSE;
        }
        $this->db->where('cf2', $cf2)->where('deleted', 0);
        if ($excluir) {
            $this->db->where('id !=', (int) $excluir);
        }
        $q = $this->db->get('suppliers', 1);
        return $q->num_rows() > 0 ? $q->row() : FALSE;
    }

    public function addSupplier($data = array()) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = (int) $this->session->userdata('user_id') ?: NULL;
        if($this->db->insert('suppliers', $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    public function updateSupplier($id, $data = array()) {
        if($this->db->update('suppliers', $data, array('id' => $id))) {
            return true;
        }
        return false;
    }

    public function deleteSupplier($id) {
        if($this->db->delete('suppliers', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

    function get_provincia() {
        $q = $this->db->get('provincia_cr');
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;

    }
    

}

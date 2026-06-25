<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Settings_model extends CI_Model
{

    public function __construct() {
        parent::__construct();
    }

    public function updateSetting($data = array()) {

        if($this->db->update('settings', $data, array('setting_id' => 1))) {
            return true;
        }
        return false;
    }

    public function getStoreByID($id) {
        $q = $this->db->get_where('stores', array('id' => $id), 1);
        if( $q->num_rows() > 0 ) {
            return $q->row();
        }
        return FALSE;
    }

    public function addStore($data = array()) {
        if($this->db->insert('stores', $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    public function updateStore($id, $data = array()) {
        if($this->db->update('stores', $data, array('id' => $id))) {
            return true;
        }
        return false;
    }

    public function deleteStore($id) {
        if($this->db->delete('stores', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

    public function getActividadByID($id) {
        $q = $this->db->get_where('actividadeconomica', array('id_actividad' => $id), 1);
        if( $q->num_rows() > 0 ) {
            return $q->row();
        }
        return FALSE;
    }

    public function getShippingByID($id) {
        $q = $this->db->get_where('shipping_method', array('id_shipping_method' => $id), 1);
        if( $q->num_rows() > 0 ) {
            return $q->row();
        }
        return FALSE;
    }

    public function addActividad($data = array()) {
        if($this->db->insert('actividadeconomica', $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    public function addShipping($data = array()) {
        if($this->db->insert('shipping_method', $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    public function updateActividad($id, $data = array()) {
        if($this->db->update('actividadeconomica', $data, array('id_actividad' => $id))) {
            return true;
        }
        return false;
    }

    public function updateShipping($id, $data = array()) {
        if($this->db->update('shipping_method', $data, array('id_shipping_method' => $id))) {
            return true;
        }
        return false;
    }

    public function deleteActividad($id) {
        if($this->db->delete('actividadeconomica', array('id_actividad' => $id))) {
            return true;
        }
        return FALSE;
    }

    public function deleteShipping($id) {
        if($this->db->delete('shipping_method', array('id_shipping_method' => $id))) {
            return true;
        }
        return FALSE;
    }


    public function addPrinter($data = array()) {
        if($this->db->insert('printers', $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    public function updatePrinter($id, $data = array()) {
        if($this->db->update('printers', $data, array('id' => $id))) {
            return true;
        }
        return false;
    }

    public function deletePrinter($id) {
        if($this->db->delete('printers', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

    public function updateWaitingTables($data,$id)
    {
        if ($this->db->update('waiting_tables', $data, array('id_waiting_tables' => $id))) {
            return true;
        }
        return false;
    }

    public function deleteWaitingTables($id) {
        if ($this->db->delete('waiting_tables', array('id_waiting_tables' => $id))) {
            return true;
        }
        return FALSE;
    }

    public function addWaitingTables($data)
    {
        if ($this->db->insert('waiting_tables', $data)) 
        {
            return true;
        }
        return false;
    }

    public function getTableById($id)
    {
        $q = $this->db->get_where('waiting_tables', array('id_waiting_tables' => $id));
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

}

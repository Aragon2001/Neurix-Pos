<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Cash_model extends CI_Model
{

    public function __construct() {
        parent::__construct();

    }

    public function addDeposit($data = array()) {
        if ($this->db->insert('deposit', $data)) {
            return true;
        }
        return false;
    }



}

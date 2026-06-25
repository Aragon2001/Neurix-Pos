<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Hacienda_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function ccsctv($tipo) {
        $terminal_pos = $this->Settings->terminal_pos;
        $query = $this->db->query(
            "SELECT consecutivo FROM tec_hacienda_tiketes WHERE tipo_doc = ? AND SUBSTRING(consecutivo,4,5) = ? ORDER BY consecutivo DESC LIMIT 1",
            array($tipo, $terminal_pos)
        );
        $r = $query->result();
        return @$r[0]->consecutivo;
    }
	
    public function ccsctvcn() {
        $this->db->limit(1);
        $this->db->order_by('consecutivo', 'DESC');
        $query = $this->db->get($this->db->dbprefix('hacienda_cn'));
        return $query->row_array();
    }

    public function ccsctvfec($tipo) {
        $terminal_pos = $this->Settings->terminal_pos;
        $query = $this->db->query(
            "SELECT consecutivo FROM tec_hacienda_fec WHERE tipo_doc = ? AND SUBSTRING(consecutivo,4,5) = ? ORDER BY consecutivo DESC LIMIT 1",
            array($tipo, $terminal_pos)
        );
        $r = $query->result();

        return @$r[0]->consecutivo;
    }

    public function insertxml($data) {
        // $this->db->save_queries = TRUE;
        if($this->getInvoice($data['sale_id']) === false){
            if ($this->db->insert($this->db->dbprefix('hacienda_tiketes'), $data)) {
                return true;
            } else {
                $valid = $this->db->where('consecutivo', $data['consecutivo'])->get($this->db->dbprefix('hacienda_tiketes'))->row_array();
				if($valid)
				{
                    $this->db->update($this->db->dbprefix('hacienda_tiketes'), array('tipo_doc' => substr($valid['consecutivo'], 9, 1)), array('consecutivo' => $data['consecutivo']));
				}
				
			    if ($this->db->update($this->db->dbprefix('hacienda_tiketes'), $data, array('sale_id' => $data['sale_id']))) {
                    return true;
                }
            }
            // var_dump($this->db->last_query());
        }
        return FALSE;
    }

    public function insertxmlCN($data) {
        if($this->getCN($data['id_cn']) === false){
            try {
                if ($this->db->insert($this->db->dbprefix('hacienda_cn'), $data)) {
                    return true;
                }
            } catch (Exception $e) {
                return $e;
            }
         }
        return FALSE;
    }

    public function insertxmlfec($data) {
		// $this->db->save_queries = TRUE;
            if ($this->db->insert($this->db->dbprefix('hacienda_fec'), $data)) {
				
                return true;
            } else {
			
				$valid = $this->db->where('consecutivo', $data['consecutivo'])->get($this->db->dbprefix('hacienda_fec'))->row_array();
				if($valid)
				{
					$this->db->update($this->db->dbprefix('hacienda_fec'), array('tipo_doc' => substr($valid['consecutivo'], 9, 1)), array('consecutivo' => $data['consecutivo']));
				}
				
			    if ($this->db->update($this->db->dbprefix('hacienda_fec'), $data, array('sale_id' => $data['sale_id']))) {
                    return true;
                }
            }
        
        return FALSE;
    }

    public function insertHacienda($data, $clave) {
        if ($this->db->update($this->db->dbprefix('hacienda_tiketes'), $data, array('clave' => $clave))) {
            return true;
        }
        return false;
    }

    public function insertHaciendaCN($data, $clave) {
        if ($this->db->update($this->db->dbprefix('hacienda_cn'), $data, array('clave' => $clave))) {
            return true;
        }
        return false;
    }

    public function insertHaciendaFec($data, $clave) {
        if ($this->db->update($this->db->dbprefix('hacienda_fec'), $data, array('clave' => $clave))) {
            return true;
        }
        return false;
    }

    public function getPendientes() {
        $this->db->where('estatus_hacienda', 'procesando');
        $this->db->or_where('estatus_hacienda', 'Sin Estado');
        $this->db->limit(10);
        $q = $this->db->get($this->db->dbprefix('hacienda_tiketes'));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getPendientesRD() {
        $this->db->where('Estatus', 'procesando');
        $this->db->or_where('Estatus', 'error');
        $this->db->or_where('Estatus', '5');
        $this->db->or_where('Estatus', 'recibido');
        $this->db->limit(10);
        $q = $this->db->get('documentoshacienda');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getPendientesCN() {
        $this->db->where('estatus_hacienda', 'error');
        $this->db->or_where('estatus_hacienda', 'procesando');
        $this->db->or_where('estatus_hacienda', 'Sin Estado');
        $this->db->limit(10);
        $q = $this->db->get('hacienda_cn');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getPendientesFec() 
    {
        // $this->db->save_queries = TRUE;
        $this->db->where('estatus_hacienda', 'procesando');
        $this->db->or_where('estatus_hacienda', 'Sin Estado');
        $this->db->limit(10);
        $q = $this->db->get($this->db->dbprefix('hacienda_fec'));
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            // dd($this->db->last_query());
            return $data;
        }
        return false;
    }

    public function getnoEnviados() {
        $this->db->where(array('mail' => '0', 'estatus_hacienda' => 'aceptado'));
        $this->db->select('sale_id');
        $this->db->limit(10);
        $q = $this->db->get('hacienda_tiketes');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getnoEnviadosRecepcion() {
        $this->db->where('mail', '0');
        $this->db->where('Estatus', 'aceptado');
        $this->db->select('id_documento');
        $this->db->limit(10);
        $q = $this->db->get('documentoshacienda');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function getnoEnviadosCN() {
        $this->db->where(array('mail' => '0', 'estatus_hacienda' => 'aceptado'));
        $this->db->select('id_cn');
        $this->db->limit(10);
        $q = $this->db->get('hacienda_cn');
        if ($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function MarcaEnviado($id, $status) {
        if ($this->db->update($this->db->dbprefix('hacienda_tiketes'), array('mail' => $status), array('sale_id' => $id))) {
            return true;
        }
        return false;
    }

    public function MarcaEnviadoRecepcion($id, $status) {
        if ($this->db->update($this->db->dbprefix('documentoshacienda'), array('mail' => $status), array('id_documento' => $id))) {
            return true;
        }
        return false;
    }

    public function MarcaEnviadoCN($id, $status) {
        if ($this->db->update($this->db->dbprefix('hacienda_cn'), array('mail' => $status), array('id_cn' => $id))) {
            return true;
        }
        return false;
    }

    public function xmlFirmado($id) {
        $this->db->where('sale_id', $id);
        $this->db->select('xml_sign');
        $q = $this->db->get('hacienda_tiketes');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function xmlFirmadoRecepcion($id) {
        $this->db->where('id_documento', $id);
        $this->db->select('xml_firmado');
        $q = $this->db->get('documentoshacienda');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function xmlFirmadoCN($id) {
        $this->db->where('id_cn', $id);
        $this->db->select('xml_sign');
        $q = $this->db->get('hacienda_cn');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function xmlFirmadoFec($id) {
        $this->db->where('sale_id', $id);
        $this->db->select('xml_sign');
        $q = $this->db->get('hacienda_fec');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }
   
    public function xmlMensaje($id) {
        $this->db->where('sale_id', $id);
        $this->db->select('xml_hacienda');
        $q = $this->db->get('hacienda_tiketes');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function xmlMensajeRecepcion($id) {
        $this->db->where('id_documento', $id);
        $this->db->select('xml_hacienda');
        $q = $this->db->get('documentoshacienda');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function xmlMensajeCN($id) {
        $this->db->where('id_cn', $id);
        $this->db->select('xml_hacienda');
        $q = $this->db->get('hacienda_cn');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function xmlMensajeFec($id) {
        $this->db->where('sale_id', $id);
        $this->db->select('xml_hacienda');
        $q = $this->db->get('hacienda_fec');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getClave($id) {
        $this->db->where('sale_id', $id);
        $this->db->select('clave');
        $q = $this->db->get('hacienda_tiketes');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getClaveCN($id) {
        $this->db->where('id_cn', $id);
        $this->db->select('clave');
        $q = $this->db->get('hacienda_cn');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getInvoice($id) {
        $this->db->where('sale_id', $id);
        $q = $this->db->get('hacienda_tiketes');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getCN($id) {
        $this->db->where('id_cn', $id);
        $q = $this->db->get('hacienda_cn');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getFEC($id) {
        $this->db->where('sale_id', $id);
        $q = $this->db->get('hacienda_fec');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getAllFEC($start_date, $end_date) {
        ini_set("memory_limit", "-1");
        ini_set( 'max_input_vars' , 8000 );
        if ($start_date) {
            $this->db->where('fecha_emision >=', $start_date);
        }
        if ($end_date) {
            $this->db->where('fecha_emision <=', $end_date);
        }
        $this->db->where('estatus_hacienda =', 'aceptado');
        $this->db->order_by('fecha_emision','desc');
        if($start_date == null && $end_date == null ){
            $this->db->limit(500);
        }
        // $this->db->limit(400);
        $q = $this->db->get('hacienda_fec');
        if ($q->num_rows() > 0) {
            return $q->result_array();
        }
        return false;
    }

    public function getAllSale($start_date, $end_date,$customer) {
        ini_set("memory_limit", "-1");
        ini_set( 'max_input_vars' , 8000 );
        $where ="";
        $limit =" ";
        if ($start_date) {
            $where .=" AND ht.fecha_emision >='".$start_date."'";
            // $this->db->where('fecha_emision >=', $start_date);
        }
        if ($end_date) {
            // $this->db->where('fecha_emision <=', $end_date);
            $where .=" AND ht.fecha_emision <='".$end_date."'";
        }
        if ($customer) {
            // $this->db->where('customer_id =', $customer);
            $where .=" AND s.customer_id =".$customer;
        } 

        if($start_date == null && $end_date == null && $customer== null ){
            $limit .=" LIMIT 1000";
        }
        // $this->db->save_queries = TRUE;
        $q = $this->db->query("SELECT ht.sale_id, ht.xml_sign FROM `tec_sales`  s
        LEFT JOIN `tec_hacienda_tiketes` ht ON ht.sale_id = s.id
        WHERE ht.estatus_hacienda = 'aceptado' ".$where." ORDER BY ht.fecha_emision DESC".$limit);
        //  dd($this->db->last_query());
        // $this->db->where('estatus_hacienda =', 'aceptado');
        // $this->db->order_by('fecha_emision','desc');
        // $q = $this->db->get('hacienda_tiketes');
        // dd($q->result_array());
        if ($q->num_rows() > 0) {
            return $q->result_array();
        }
        return false;
    }

    public function isSaleHasCn($sale_id){
        $q = $this->db->get_where('note_credits', array('sale_id' => $sale_id), 1);
        if ($q->num_rows() > 0) {
            return true;
        }
        return false;
    }

    public function getInvoicebyConsecutivo($consecutivo) {
        $this->db->where('consecutivo', $consecutivo);
        $q = $this->db->get('hacienda_tiketes');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getCNbyConsecutivo($consecutivo) {
        $this->db->where('consecutivo', $consecutivo);
        $q = $this->db->get('hacienda_cn');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }
    
    public function setDocumento($compra, $suppliers, $item_compra) {
        $this->db->trans_begin();
        $q = $this->db->where('cf2', $suppliers['cf2'])->get($this->db->dbprefix('suppliers'));
        if ($q->num_rows() > 0) {
            $this->db->where('cf2', $suppliers['cf2'])->update($this->db->dbprefix('suppliers'), $suppliers);
            $id_supplier = $q->row()->id;
        } else {
            $this->db->insert($this->db->dbprefix('suppliers'), $suppliers);
            $id_supplier = $this->db->insert_id();
        }

        $q = $this->db->where('ClaveDocEmisor', $compra['ClaveDocEmisor'])->get($this->db->dbprefix('documentoshacienda'));
        if ($q->num_rows() > 0) {
            $this->db->where('ClaveDocEmisor', $compra['ClaveDocEmisor'])->update($this->db->dbprefix('documentoshacienda'), $compra);
            $id_compra = $q->row()->id_documento;
        } else {
            $this->db->insert($this->db->dbprefix('documentoshacienda'), $compra);
            $id_compra = $this->db->insert_id();
        }

        foreach ($item_compra as $itm) {
            $q = $this->db->where('clave', $compra['ClaveDocEmisor'])->where('code', $itm['code'])->where('consecutivo', $itm['consecutivo'])->get($this->db->dbprefix('documentositems'));
            if ($q->num_rows() > 0) {
                $this->db->where('clave', $compra['ClaveDocEmisor'])->where('code', $itm['code'])->where('consecutivo', $itm['consecutivo'])->update($this->db->dbprefix('documentositems'), $itm);
            } else {
                $this->db->insert($this->db->dbprefix('documentositems'), $itm);
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    } 

    public function setRespuesta($data, $datosaceptacion, $id, $firmado) {

        if ($this->db->update($this->db->dbprefix('documentoshacienda'), 
                array(
                    'xml_mensajereceptor' => $data[0], 
                    'consecutivo' => $data[1], 
                    'Mensaje' => $data[2],
                    'Estatus' => 'procesando', 
                    'DetalleMensaje' => $datosaceptacion['DetalleMensaje'], 
                    'CondicionImpuesto' => $datosaceptacion['CondicionImpuesto'], 
                    'MontoTotalImpuestoAcreditar' => $datosaceptacion['MontoTotalImpuestoAcreditar'], 
                    'MontoTotalDeGastoAplicable' => $datosaceptacion['MontoTotalDeGastoAplicable'], 
                    'xml_firmado' => $firmado
                )
                , array('id_documento' => $id))) {
            return true;
        }
        return false;
    }

    public function setRespuestaxmlfirmado($data, $id) {
        if ($this->db->update($this->db->dbprefix('documentoshacienda'), array('xml_firmado' => base64_decode($data)), array('id_documento' => $id))) {
            return true;
        }
        return false;
    }

    public function setRespuestaMensaje($respuesta, $id) {
        if ($this->db->update($this->db->dbprefix('documentoshacienda'), $respuesta, array('id_documento' => $id))) {
            return true;
        }
        return false;
    }

    public function getHaciendaDocByID($id) {
        $this->db->where('id_documento', $id);
        $q = $this->db->get('documentoshacienda');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function getHaciendaDocByClave($clave) {
        $this->db->where('ClaveDocEmisor', $clave);
        $q = $this->db->get('documentoshacienda');
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }
	
    public function getNoXML(){
		$q = $this->db->query("SELECT id FROM `tec_sales` LEFT JOIN `tec_hacienda_tiketes` ON `tec_hacienda_tiketes`.sale_id = tec_sales.id
				WHERE tec_hacienda_tiketes.id_hacienda IS NULL ORDER BY tec_sales.id DESC LIMIT 20");
				 $r = $q->result();
				 return $r;
    }
    
    public function getNoXMLCN(){
		$q = $this->db->query("SELECT tec_note_credits.sale_id, tec_note_credits.id FROM `tec_note_credits` LEFT JOIN `tec_hacienda_cn` ON `tec_hacienda_cn`.id_cn = tec_note_credits.id
				WHERE tec_hacienda_cn.id_hacienda IS NULL ORDER BY tec_note_credits.id DESC LIMIT 1");
				 $r = $q->result();
				 return $r;
	}
		
    public function getNoXMLFec(){
		$q = $this->db->query("SELECT id FROM `tec_fec` LEFT JOIN `tec_hacienda_fec` ON `tec_hacienda_fec`.sale_id = tec_fec.id
				WHERE tec_hacienda_fec.id_hacienda IS NULL ORDER BY tec_fec.id DESC LIMIT 20");
				 $r = $q->result();
				 return $r;
    }
    
    public function getsinXML(){
		$q = $this->db->query("SELECT sale_id as id from tec_hacienda_tiketes where  xml IS NULL OR xml = ''");
				 $r = $q->result();
				 return $r;
    }

    public function getsinXMLCn(){
		$q = $this->db->query("SELECT tec_note_credits.sale_id, tec_note_credits.id FROM `tec_note_credits` LEFT JOIN `tec_hacienda_cn` ON `tec_hacienda_cn`.id_cn = tec_note_credits.id 
                                where  tec_hacienda_cn.xml IS NULL OR tec_hacienda_cn.xml = '' LIMIT 1");
				 $r = $q->result();
				 return $r;
    }
    
    public function getsinXMLFec(){
		$q = $this->db->query("SELECT sale_id AS id FROM tec_hacienda_fec WHERE xml IS NULL OR xml = ''");
                 $r = $q->result();
				 return $r;
	}
	
    public function setTipo($id, $tipo) {

        if ($this->db->update($this->db->dbprefix('hacienda_tiketes'), array('tipo_doc' => $tipo), array('id_hacienda' => $id))) {
            return true;
        }
        return false;
    }

    // --- REP (Recibo Electrónico de Pago, tipo 09) ---

    public function ccsctv_rep() {
        $terminal_pos = $this->Settings->terminal_pos;
        $query = $this->db->query(
            "SELECT consecutivo FROM tec_hacienda_rep WHERE SUBSTRING(consecutivo,4,5) = ? ORDER BY consecutivo DESC LIMIT 1",
            array($terminal_pos)
        );
        $r = $query->result();
        return @$r[0]->consecutivo;
    }

    public function getREP($payment_id) {
        $this->db->where('payment_id', $payment_id);
        $q = $this->db->get($this->db->dbprefix('hacienda_rep'));
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function insertxmlREP($data) {
        if ($this->getREP($data['payment_id']) === false) {
            if ($this->db->insert($this->db->dbprefix('hacienda_rep'), $data)) {
                return true;
            }
        }
        return false;
    }

    public function insertHaciendaREP($data, $clave) {
        if ($this->db->update($this->db->dbprefix('hacienda_rep'), $data, array('clave' => $clave))) {
            return true;
        }
        return false;
    }

    public function getPendientesREP() {
        $this->db->where('estatus_hacienda', 'procesando');
        $this->db->or_where('estatus_hacienda', 'Sin Estado');
        $this->db->limit(10);
        $q = $this->db->get($this->db->dbprefix('hacienda_rep'));
        if ($q->num_rows() > 0) {
            return $q->result();
        }
        return false;
    }

    public function xmlFirmadoREP($payment_id) {
        $this->db->where('payment_id', $payment_id);
        $this->db->select('xml_sign');
        $q = $this->db->get($this->db->dbprefix('hacienda_rep'));
        if ($q->num_rows() > 0) {
            return $q->row();
        }
        return false;
    }

    public function MarcaEnviadoREP($payment_id, $status) {
        if ($this->db->update($this->db->dbprefix('hacienda_rep'), array('mail' => $status), array('payment_id' => $payment_id))) {
            return true;
        }
        return false;
    }

}

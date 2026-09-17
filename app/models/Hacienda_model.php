<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Hacienda_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Ultimo numero emitido para un tipo de comprobante: el mayor entre lo que hay
     * en las tablas locales y el arranque configurado en Ajustes. Sin ese arranque,
     * una instalacion recien migrada volveria a numerar desde 1 y Hacienda rechaza
     * el comprobante por consecutivo ya usado.
     *
     * @param string $tipo        codigo de Hacienda ('01', '03', '09'...)
     * @param string|null $enTabla consecutivo de 20 digitos leido de la tabla
     */
    public function ultimo_consecutivo($tipo, $enTabla) {
        $numero   = $enTabla ? (int) substr($enTabla, 10, 10) : 0;
        $arranque = (int) ($this->Settings->{'consec_inicial_' . $tipo} ?? 0);
        return max($numero, $arranque);
    }

    public function ccsctv($tipo) {
        $terminal_pos = $this->Settings->terminal_pos;
        $tabla = $this->db->dbprefix('hacienda_tiketes');
        $query = $this->db->query(
            "SELECT consecutivo FROM `{$tabla}` WHERE tipo_doc = ? AND SUBSTRING(consecutivo,4,5) = ? ORDER BY consecutivo DESC LIMIT 1",
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
        $tabla = $this->db->dbprefix('hacienda_fec');
        $query = $this->db->query(
            "SELECT consecutivo FROM `{$tabla}` WHERE tipo_doc = ? AND SUBSTRING(consecutivo,4,5) = ? ORDER BY consecutivo DESC LIMIT 1",
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
                    $this->db->update($this->db->dbprefix('hacienda_tiketes'), array('tipo_doc' => substr($valid['consecutivo'], 8, 2)), array('consecutivo' => $data['consecutivo']));
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
					$this->db->update($this->db->dbprefix('hacienda_fec'), array('tipo_doc' => substr($valid['consecutivo'], 8, 2)), array('consecutivo' => $data['consecutivo']));
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
        // 'pendiente' es el valor por defecto de la columna: un comprobante recien
        // insertado nace ahi y sin el nunca entraria en la tanda de envio.
        $this->db->where_in('estatus_hacienda', array('pendiente', 'procesando', 'Sin Estado'));
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
        // 'pendiente' es el valor por defecto de la columna: sin el, una nota de
        // credito recien insertada nunca entraria en la tanda de envio.
        $this->db->where_in('estatus_hacienda', array('pendiente', 'error', 'procesando', 'Sin Estado'));
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
        // 'pendiente' es el valor por defecto de la columna: sin el, una factura de
        // compra recien insertada nunca entraria en la tanda de envio.
        $this->db->where_in('estatus_hacienda', array('pendiente', 'procesando', 'Sin Estado'));
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
        $ventas  = $this->db->dbprefix('sales');
        $tiketes = $this->db->dbprefix('hacienda_tiketes');
        $q = $this->db->query("SELECT ht.sale_id, ht.xml_sign FROM `{$ventas}`  s
        LEFT JOIN `{$tiketes}` ht ON ht.sale_id = s.id
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
	
    /**
     * Ventas que todavia no tienen comprobante.
     *
     * El LEFT JOIN se filtra por sale_id y no por id_hacienda: esa columna la
     * llena Hacienda al responder y esta vacia en todas las filas, asi que como
     * condicion devolveria tambien las ventas que ya tienen comprobante.
     */
    public function getNoXML(){
        $ventas = $this->db->dbprefix('sales');
        $tiketes = $this->db->dbprefix('hacienda_tiketes');
        $q = $this->db->query("SELECT {$ventas}.id FROM `{$ventas}` LEFT JOIN `{$tiketes}` ON `{$tiketes}`.sale_id = {$ventas}.id
				WHERE {$tiketes}.sale_id IS NULL ORDER BY {$ventas}.id DESC LIMIT 20");
				 $r = $q->result();
				 return $r;
    }
    
    public function getNoXMLCN(){
        $notas = $this->db->dbprefix('note_credits');
        $hacienda = $this->db->dbprefix('hacienda_cn');
        $q = $this->db->query("SELECT {$notas}.sale_id, {$notas}.id FROM `{$notas}` LEFT JOIN `{$hacienda}` ON `{$hacienda}`.id_cn = {$notas}.id
				WHERE {$hacienda}.id_cn IS NULL ORDER BY {$notas}.id DESC LIMIT 1");
				 $r = $q->result();
				 return $r;
	}
		
    public function getNoXMLFec(){
        $fec = $this->db->dbprefix('fec');
        $hacienda = $this->db->dbprefix('hacienda_fec');
        $q = $this->db->query("SELECT {$fec}.id FROM `{$fec}` LEFT JOIN `{$hacienda}` ON `{$hacienda}`.sale_id = {$fec}.id
				WHERE {$hacienda}.sale_id IS NULL ORDER BY {$fec}.id DESC LIMIT 20");
				 $r = $q->result();
				 return $r;
    }
    
    public function getsinXML(){
        $tiketes = $this->db->dbprefix('hacienda_tiketes');
		$q = $this->db->query("SELECT sale_id as id FROM `{$tiketes}` WHERE xml IS NULL OR xml = ''");
				 $r = $q->result();
				 return $r;
    }

    public function getsinXMLCn(){
        $notas = $this->db->dbprefix('note_credits');
        $hacienda = $this->db->dbprefix('hacienda_cn');
		$q = $this->db->query("SELECT `{$notas}`.sale_id, `{$notas}`.id FROM `{$notas}` LEFT JOIN `{$hacienda}` ON `{$hacienda}`.id_cn = `{$notas}`.id
                                WHERE `{$hacienda}`.xml IS NULL OR `{$hacienda}`.xml = '' LIMIT 1");
				 $r = $q->result();
				 return $r;
    }
    
    public function getsinXMLFec(){
        $hacienda = $this->db->dbprefix('hacienda_fec');
		$q = $this->db->query("SELECT sale_id AS id FROM `{$hacienda}` WHERE xml IS NULL OR xml = ''");
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
        $tabla = $this->db->dbprefix('hacienda_rep');
        $query = $this->db->query(
            "SELECT consecutivo FROM `{$tabla}` WHERE SUBSTRING(consecutivo,4,5) = ? ORDER BY consecutivo DESC LIMIT 1",
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

    public function getnoEnviadosREP() {
        $this->db->where(array('mail' => '0', 'estatus_hacienda' => 'aceptado'));
        $this->db->select('payment_id');
        $this->db->limit(10);
        $q = $this->db->get($this->db->dbprefix('hacienda_rep'));
        if ($q->num_rows() > 0) {
            return $q->result();
        }
        return false;
    }

    public function xmlMensajeREP($payment_id) {
        $this->db->where('payment_id', $payment_id);
        $this->db->select('xml_hacienda');
        $q = $this->db->get($this->db->dbprefix('hacienda_rep'));
        if ($q->num_rows() > 0) return $q->row();
        return false;
    }

    public function ccsctv_nd() {
        $terminal_pos = $this->Settings->terminal_pos;
        $tabla = $this->db->dbprefix('hacienda_nd');
        $query = $this->db->query(
            "SELECT consecutivo FROM `{$tabla}` WHERE SUBSTRING(consecutivo,4,5) = ? ORDER BY consecutivo DESC LIMIT 1",
            array($terminal_pos)
        );
        $r = $query->result();
        return @$r[0]->consecutivo;
    }

    public function getND($nd_id) {
        $this->db->where('nd_id', $nd_id);
        $q = $this->db->get($this->db->dbprefix('hacienda_nd'));
        if ($q->num_rows() > 0) return $q->row();
        return false;
    }

    public function insertxmlND($data) {
        if ($this->getND($data['nd_id']) === false) {
            if ($this->db->insert($this->db->dbprefix('hacienda_nd'), $data)) return true;
        }
        return false;
    }

    public function insertHaciendaND($data, $clave) {
        return $this->db->update($this->db->dbprefix('hacienda_nd'), $data, array('clave' => $clave));
    }

    public function xmlFirmadoND($nd_id) {
        $this->db->where('nd_id', $nd_id)->select('xml_sign');
        $q = $this->db->get($this->db->dbprefix('hacienda_nd'));
        if ($q->num_rows() > 0) return $q->row();
        return false;
    }

    public function xmlMensajeND($nd_id) {
        $this->db->where('nd_id', $nd_id)->select('xml_hacienda');
        $q = $this->db->get($this->db->dbprefix('hacienda_nd'));
        if ($q->num_rows() > 0) return $q->row();
        return false;
    }

    public function getPendientesND() {
        $this->db->where('estatus_hacienda', 'procesando')->limit(10);
        $q = $this->db->get($this->db->dbprefix('hacienda_nd'));
        if ($q->num_rows() > 0) return $q->result();
        return false;
    }

    public function MarcaEnviadoND($nd_id, $status) {
        return $this->db->update($this->db->dbprefix('hacienda_nd'), array('mail' => $status), array('nd_id' => $nd_id));
    }


    /**
     * Salud de la conexion con Hacienda para el aviso del POS.
     *
     * Hacienda responde en segundos: un comprobante que sigue en pendiente o
     * procesando pasados 15 minutos ya no es demora normal, es que el envio no
     * esta corriendo o que Hacienda no contesta.
     *
     * @return array ok (bool), motivo (clave de idioma) y pendientes (int)
     */
    public function estadoConexion($ambiente, $usuario, $clave, $certificado)
    {
        if (!$usuario || !$clave || !$certificado) {
            return array('ok' => FALSE, 'motivo' => 'hacienda_sin_credenciales', 'pendientes' => 0);
        }

        $t = $this->db->dbprefix('hacienda_tiketes');
        $fila = $this->db->query(
            "SELECT
                SUM(CASE WHEN estatus_hacienda IN ('error','rechazado') THEN 1 ELSE 0 END) AS fallidos,
                SUM(CASE WHEN estatus_hacienda IN ('pendiente','procesando')
                          AND fecha < DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE 0 END) AS estancados
             FROM `{$t}`
             WHERE fecha >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
        )->row();

        $estancados = (int) ($fila->estancados ?? 0);
        $fallidos   = (int) ($fila->fallidos ?? 0);

        if ($estancados > 0) {
            return array('ok' => FALSE, 'motivo' => 'hacienda_sin_respuesta', 'pendientes' => $estancados);
        }
        if ($fallidos > 0) {
            return array('ok' => FALSE, 'motivo' => 'hacienda_con_rechazos', 'pendientes' => $fallidos);
        }
        return array('ok' => TRUE, 'motivo' => 'hacienda_al_dia', 'pendientes' => 0);
    }
}

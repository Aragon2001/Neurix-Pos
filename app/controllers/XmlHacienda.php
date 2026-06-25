<?php

/**
 * Created by PhpStorm.
 * User: Rhonald Brito
 * Date: 4/7/2018
 * Time: 19:36
 */
class Xmlhacienda extends MY_Controller
{
    public function __construct(){
        parent::__construct();
        $this->load->model('hacienda_model');
    }


    public function xmlFirmado($id){
        $xml = $this->hacienda_model->xmlFirmado($id);
        header('Content-Type: application/xml; charset=utf-8');
        echo str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->xml_sign);
    }

    public function xmlFirmadoFec($id){
        $xml = $this->hacienda_model->xmlFirmadoFec($id);
        header('Content-Type: application/xml; charset=utf-8');
        echo str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->xml_sign);
    }

    public function xmlFirmadoCN($id){
        $xml = $this->hacienda_model->xmlFirmadoCN($id);
        header('Content-Type: application/xml; charset=utf-8');
        echo str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->xml_sign);
    }

    public function xmlFirmadoRecepcion($id){
        $xml = $this->hacienda_model->xmlFirmadoRecepcion($id);
        header('Content-Type: application/xml; charset=utf-8');
        echo str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->xml_firmado);
    }

    public function xmlMensaje($id){
        $xml = $this->hacienda_model->xmlMensaje($id);
        header('Content-Type: application/xml; charset=utf-8');
        echo str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->xml_hacienda);
    }

    public function xmlMensajeFec($id){
        $xml = $this->hacienda_model->xmlMensajeFec($id);
        header('Content-Type: application/xml; charset=utf-8');
        echo str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->xml_hacienda);
    }

    public function xmlMensajeCN($id){
        $xml = $this->hacienda_model->xmlMensajeCN($id);
        header('Content-Type: application/xml; charset=utf-8');
        echo str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->xml_hacienda);
    }

    public function xmlMensajeRecepcion($id){
        $xml = $this->hacienda_model->xmlMensajeRecepcion($id);
        header('Content-Type: application/xml; charset=utf-8');
        echo str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->xml_hacienda);
    }
}
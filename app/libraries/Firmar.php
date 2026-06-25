<?php
require(dirname(__FILE__) . '/hacienda/firmador.php');

use Hacienda\Firmador;

class firmar{
	
	function __construct() {		
		}
	public function firmar($certificado, $certificado_pin, $facturaxml)
	{
		
		
		
		$pfx    = $certificado; // Ruta del archivo de la llave criptográfica (*.p12)
        $pin    = $certificado_pin; // PIN de 4 dígitos de la llave criptográfica
        $xml    = $facturaxml; // String XML ó Ruta del archivo XML (comprobante electrónico)
        $ruta   = ''; // Ruta del nuevo arhivo XML cuando se desea guardar en disco

    // Nuevo firmador
    $firmador = new Firmador();

    // Se firma XML y se recibe un string resultado en Base64
    // dd($certificado);
    // $base64 = $firmador->firmarXml($pfx, $pin, $xml, $firmador::TO_BASE64_STRING);
    // var_dump($base64);

    // Se firma XML y se recibe un string resultado en Xml
    $xml_string = $firmador->firmarXml($pfx, $pin, $xml, $firmador::TO_XML_STRING);
    return $xml_string;

    // Se firma XML, se guarda en disco duro ($ruta) y se recibe el número de bytes del archivo guardado. En caso de error se recibe FALSE
    // $archivo = $firmador->firmarXml($pfx, $pin, $xml, $firmador::TO_XML_FILE, $ruta);
    // var_dump($archivo);
	}
}
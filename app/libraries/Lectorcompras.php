<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Lectura de la casilla que recibe las facturas electrónicas de compra.
 *
 * Se conecta por IMAP, baja los adjuntos XML de los correos sin leer, valida que
 * sean comprobantes de Hacienda y los registra en documentoshacienda. Cada correo
 * procesado se marca leído, que es lo que evita volver a importarlo.
 *
 * Vive como librería porque se dispara desde dos lados: a mano desde Ajustes y
 * automáticamente después de enviar comprobantes a Hacienda.
 */
class Lectorcompras
{
    /** Tope de correos por corrida, para que la petición no se eternice. */
    const MAX_CORREOS = 40;

    /** Tope al recorrer la casilla entera, que se pide a mano y puede tardar. */
    const MAX_CORREOS_TODO = 500;

    public function __get($var)
    {
        return get_instance()->$var;
    }

    /**
     * Conecta, importa y devuelve el resumen. Nunca imprime nada ni corta la
     * petición: el detalle queda en el log.
     *
     * Con $todo la búsqueda es ALL en vez de UNSEEN: recorre también los correos
     * ya leídos, que es la única forma de recuperar lo que llegó antes de
     * conectar la casilla. Repetir no duplica nada — cada comprobante se
     * descarta por clave contra los ya registrados.
     *
     * @param bool $todo false = solo correos sin leer; true = la casilla entera
     * @return array{ok:bool,revisados?:int,registrados?:int,repetidos?:int,sin_xml?:int,errores?:array,error?:string}
     */
    public function importar($todo = false)
    {
        $resumen = ['ok' => true, 'revisados' => 0, 'registrados' => 0,
                    'repetidos' => 0, 'sin_xml' => 0, 'errores' => []];

        $imap = $this->conectar();
        if (!is_object($imap)) {
            return ['ok' => false, 'error' => $imap];
        }

        $carpeta = $this->Settings->mail_client_carpeta ?: 'INBOX';
        if ($imap->seleccionar($carpeta) === false) {
            $error = $imap->error();
            $imap->cerrar();
            return ['ok' => false, 'error' => $error];
        }

        $this->load->model('hacienda_model');
        $tope     = $todo ? self::MAX_CORREOS_TODO : self::MAX_CORREOS;
        $mensajes = array_slice($imap->buscar($todo ? 'ALL' : 'UNSEEN'), 0, $tope);

        foreach ($mensajes as $num) {
            $resumen['revisados']++;
            $crudo = $imap->mensaje($num);
            if ($crudo === '') {
                $resumen['errores'][] = sprintf(lang('mail_no_se_pudo_leer'), $num);
                continue;
            }

            $xmls = array_filter(Imapclient::adjuntos($crudo), function ($a) {
                return stripos($a['nombre'], '.xml') !== false || stripos($a['tipo'], 'xml') !== false;
            });

            if (!$xmls) {
                $resumen['sin_xml']++;
                // Se marca leído igual: sin XML no hay nada que reintentar.
                $imap->marcar_leido($num);
                continue;
            }

            $huboError = false;
            foreach ($xmls as $adj) {
                $r = $this->_registrar($adj['contenido']);
                if ($r === 'ok')           { $resumen['registrados']++; }
                elseif ($r === 'repetido') { $resumen['repetidos']++; }
                elseif ($r !== 'ignorado') { $resumen['errores'][] = $r; $huboError = true; }
            }

            // Un correo que falló se deja sin leer para poder reintentarlo.
            if (!$huboError) {
                $imap->marcar_leido($num);
            }
        }

        $imap->cerrar();
        log_message('info', '[Lectorcompras] ' . json_encode($resumen));
        return $resumen;
    }

    /** Conecta y cuenta, sin bajar ni registrar nada. */
    public function probar()
    {
        $imap = $this->conectar();
        if (!is_object($imap)) {
            return ['ok' => false, 'error' => $imap];
        }

        $carpeta = $this->Settings->mail_client_carpeta ?: 'INBOX';
        $total = $imap->seleccionar($carpeta);
        if ($total === false) {
            $error = $imap->error();
            $imap->cerrar();
            return ['ok' => false, 'error' => $error];
        }
        $sinLeer = count($imap->buscar('UNSEEN'));
        $imap->cerrar();

        return ['ok' => true, 'carpeta' => $carpeta, 'total' => $total, 'sin_leer' => $sinLeer];
    }

    /** @return Imapclient|string el cliente conectado, o el mensaje de error */
    public function conectar()
    {
        $host = trim((string) $this->Settings->mail_client_host);
        if ($host === '') {
            return lang('mail_recepcion_sin_host');
        }

        $this->load->library('imapclient');
        $imap = $this->imapclient;

        $cifrado = $this->Settings->mail_client_crypto ?: 'ssl';
        $puerto  = (int) ($this->Settings->mail_client_port ?: ($cifrado === 'ssl' ? 993 : 143));

        if (!$imap->conectar($host, $puerto, $cifrado)) {
            return $imap->error();
        }

        $usuario = (string) $this->Settings->mail_client_user;

        if (($this->Settings->mail_client_auth ?? 'password') === 'oauth_google') {
            try {
                $this->load->library('googlemail');
                $token = $this->googlemail->refrescar($this->Settings->mail_client_oauth_refresh);
            } catch (Exception $e) {
                $imap->cerrar();
                return $e->getMessage();
            }
            $usuario = $this->Settings->mail_client_oauth_email ?: $usuario;
            if (!$imap->login_xoauth2($usuario, $token)) {
                $error = $imap->error();
                $imap->cerrar();
                return $error;
            }
            return $imap;
        }

        if (!$imap->login($usuario, (string) $this->Settings->mail_client_pass)) {
            $error = $imap->error();
            $imap->cerrar();
            return $error;
        }
        return $imap;
    }

    // -----------------------------------------------------------------------
    // Privados
    // -----------------------------------------------------------------------

    /**
     * La tienda a la que se atribuye el documento. Desde un cron no hay sesión,
     * y un store_id nulo deja el documento fuera del listado, que filtra por esa
     * columna: se cae a la primera tienda registrada.
     */
    private function _store_id()
    {
        $id = $this->session->userdata('store_id');
        if (!empty($id)) {
            return $id;
        }
        $fila = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('stores')->row();
        return $fila ? $fila->id : 1;
    }

    /** @return string 'ok' | 'repetido' | 'ignorado' | mensaje de error */
    private function _registrar($xmlCrudo)
    {
        $documento = $this->_parsear($xmlCrudo);
        if (!$documento) {
            return 'ignorado';  // Adjunto XML que no es un comprobante.
        }

        $clave = (string) $documento->Clave;
        if ($clave === '') {
            return lang('mail_xml_sin_clave');
        }
        if ($this->hacienda_model->getHaciendaDocByClave($clave)) {
            return 'repetido';
        }

        $this->load->model('recibidos_model');
        list($compra, $proveedor, $lineas) = compra_mapear_xml($documento, $xmlCrudo, $this->_store_id());

        if (!$this->recibidos_model->guardarDocumento($compra, $proveedor, $lineas)) {
            log_message('error', '[Lectorcompras] no se pudo guardar la clave ' . $clave);
            return sprintf(lang('mail_no_se_pudo_guardar'), $clave);
        }
        return 'ok';
    }

    /** @return SimpleXMLElement|false false si el XML no es un comprobante */
    private function _parsear($xmlCrudo)
    {
        // Un BOM o cualquier basura antes del prolog hace fallar al parser.
        $xmlCrudo = preg_replace('/^[^<]*/', '', $xmlCrudo);

        $previo = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xmlCrudo);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        if (!$doc || (empty($doc->Emisor->Nombre) && empty($doc->Receptor->Nombre))) {
            return false;
        }
        return $doc;
    }
}

<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cliente IMAP mínimo sobre sockets.
 *
 * La extensión imap de PHP no está disponible en este entorno y además quedó
 * deprecada en PHP 8.4 (salió del núcleo a PECL), así que el lector de facturas
 * de compra habla el protocolo directo. Solo implementa lo que hace falta:
 * conectar, autenticar (contraseña o XOAUTH2), buscar, traer el mensaje y
 * marcarlo leído.
 *
 * IMAP es un protocolo de líneas con etiquetas (RFC 3501): cada orden lleva un
 * identificador y la respuesta termina con una línea que empieza con esa misma
 * etiqueta seguida de OK, NO o BAD.
 */
class Imapclient
{
    const TIMEOUT = 30;

    private $sock = null;
    private $seq  = 0;
    private $ultimoError = '';

    public function __destruct()
    {
        $this->cerrar();
    }

    public function error()
    {
        return $this->ultimoError;
    }

    /**
     * @param string $host    ej. imap.gmail.com
     * @param int    $puerto  993 con ssl, 143 con tls o sin cifrar
     * @param string $cifrado 'ssl' | 'tls' | ''
     */
    public function conectar($host, $puerto, $cifrado = 'ssl')
    {
        $esquema = ($cifrado === 'ssl') ? 'ssl://' : 'tcp://';
        $ctx = stream_context_create(array('ssl' => array(
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'SNI_enabled'       => true,
        )));

        $this->sock = @stream_socket_client(
            $esquema . $host . ':' . (int) $puerto,
            $errno, $errstr, self::TIMEOUT, STREAM_CLIENT_CONNECT, $ctx
        );

        if (!$this->sock) {
            $this->ultimoError = sprintf(lang('imap_no_conecta'), $host, (int) $puerto, $errstr ?: $errno);
            return false;
        }
        stream_set_timeout($this->sock, self::TIMEOUT);

        // Saludo del servidor.
        $saludo = fgets($this->sock);
        if (strpos((string) $saludo, '* OK') !== 0) {
            $this->ultimoError = lang('imap_saludo_invalido') . ' ' . trim((string) $saludo);
            return $this->cerrar() && false;
        }

        if ($cifrado === 'tls') {
            $r = $this->_orden('STARTTLS');
            if (!$r['ok']) {
                $this->ultimoError = lang('imap_starttls_rechazado') . ' ' . $r['detalle'];
                return $this->cerrar() && false;
            }
            if (!@stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->ultimoError = lang('imap_starttls_fallo');
                return $this->cerrar() && false;
            }
        }
        return true;
    }

    /** Autenticación clásica con usuario y contraseña (o contraseña de aplicación). */
    public function login($usuario, $clave)
    {
        $r = $this->_orden('LOGIN ' . $this->_literal($usuario) . ' ' . $this->_literal($clave));
        if (!$r['ok']) {
            $this->ultimoError = lang('imap_login_rechazado') . ' ' . $r['detalle'];
        }
        return $r['ok'];
    }

    /**
     * Autenticación XOAUTH2. Ante un fallo el servidor manda un desafío en
     * base64 y espera una línea vacía antes de dar el error definitivo; sin ese
     * paso la conexión queda desincronizada para la orden siguiente.
     */
    public function login_xoauth2($usuario, $access_token)
    {
        $this->load_google();
        $sasl = $this->googlemail->xoauth2($usuario, $access_token);
        $r = $this->_orden('AUTHENTICATE XOAUTH2 ' . $sasl, true);

        if (!$r['ok'] && $r['continuar']) {
            $this->_escribir('');
            $r = $this->_leer_hasta($r['etiqueta']);
        }
        if (!$r['ok']) {
            $this->ultimoError = lang('imap_oauth_rechazado') . ' ' . $r['detalle'];
        }
        return $r['ok'];
    }

    /** @return int|false cantidad de mensajes en la carpeta */
    public function seleccionar($carpeta = 'INBOX')
    {
        $r = $this->_orden('SELECT ' . $this->_literal($carpeta));
        if (!$r['ok']) {
            $this->ultimoError = lang('imap_carpeta_invalida') . ' ' . $r['detalle'];
            return false;
        }
        $total = 0;
        foreach ($r['lineas'] as $linea) {
            if (preg_match('/^\* (\d+) EXISTS/i', $linea, $m)) {
                $total = (int) $m[1];
            }
        }
        return $total;
    }

    /**
     * Números de mensaje que cumplen un criterio IMAP.
     *
     * @param string $criterio ej. 'UNSEEN', 'SINCE 01-Aug-2026', 'ALL'
     * @return array<int>
     */
    public function buscar($criterio = 'UNSEEN')
    {
        $r = $this->_orden('SEARCH ' . $criterio);
        if (!$r['ok']) {
            $this->ultimoError = lang('imap_busqueda_fallo') . ' ' . $r['detalle'];
            return array();
        }
        foreach ($r['lineas'] as $linea) {
            if (stripos($linea, '* SEARCH') === 0) {
                $ids = preg_split('/\s+/', trim(substr($linea, 8)));
                return array_values(array_filter(array_map('intval', $ids)));
            }
        }
        return array();
    }

    /** Mensaje completo en crudo (cabeceras + cuerpo), sin marcarlo leído. */
    public function mensaje($num)
    {
        $r = $this->_orden('FETCH ' . (int) $num . ' BODY.PEEK[]');
        if (!$r['ok']) {
            $this->ultimoError = lang('imap_fetch_fallo') . ' ' . $r['detalle'];
            return '';
        }
        // La respuesta viene como {N}\r\n seguido de N octetos.
        $crudo = implode("\r\n", $r['lineas']);
        if (preg_match('/\{(\d+)\}\r?\n/', $crudo, $m, PREG_OFFSET_CAPTURE)) {
            $inicio = $m[0][1] + strlen($m[0][0]);
            return substr($crudo, $inicio, (int) $m[1][0]);
        }
        return $crudo;
    }

    public function marcar_leido($num)
    {
        $r = $this->_orden('STORE ' . (int) $num . ' +FLAGS (\\Seen)');
        return $r['ok'];
    }

    public function cerrar()
    {
        if ($this->sock) {
            @$this->_orden('LOGOUT');
            @fclose($this->sock);
            $this->sock = null;
        }
        return true;
    }

    // -----------------------------------------------------------------------
    // Análisis MIME
    // -----------------------------------------------------------------------

    /**
     * Adjuntos de un mensaje crudo.
     *
     * @return array<array{nombre:string,tipo:string,contenido:string}>
     */
    public static function adjuntos($crudo)
    {
        list($cabeceras, $cuerpo) = self::_partir($crudo);
        $tipo = self::_cabecera($cabeceras, 'content-type');

        if (!preg_match('/boundary="?([^";\r\n]+)"?/i', $tipo, $m)) {
            return array();  // Mensaje de una sola parte: no trae adjuntos.
        }
        return self::_partes($cuerpo, $m[1]);
    }

    /** Asunto decodificado, para poder filtrar y para el registro. */
    public static function asunto($crudo)
    {
        list($cabeceras) = self::_partir($crudo);
        $asunto = self::_cabecera($cabeceras, 'subject');
        $decodificado = @iconv_mime_decode($asunto, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
        return $decodificado !== false ? $decodificado : $asunto;
    }

    private static function _partes($cuerpo, $boundary)
    {
        $adjuntos = array();
        // El delimitador de cierre puede venir sin salto de linea al final: si el patron
        // exige uno, ese "--BOUNDARY--" queda pegado al ultimo adjunto y lo corrompe.
        $bloques = preg_split('/--' . preg_quote($boundary, '/') . '(?:--)?[ \t]*(?:\r?\n|$)/', $cuerpo);

        foreach ($bloques as $bloque) {
            $bloque = trim($bloque, "\r\n");
            if ($bloque === '') { continue; }

            list($cab, $cuerpoParte) = self::_partir($bloque);
            $tipo = self::_cabecera($cab, 'content-type');

            // Una parte anidada (multipart/mixed dentro de multipart/related) puede
            // esconder el adjunto un nivel más abajo.
            if (stripos($tipo, 'multipart/') === 0 && preg_match('/boundary="?([^";\r\n]+)"?/i', $tipo, $m)) {
                $adjuntos = array_merge($adjuntos, self::_partes($cuerpoParte, $m[1]));
                continue;
            }

            $nombre = self::_nombre_archivo($cab);
            if ($nombre === '') { continue; }

            $codif = strtolower(trim(self::_cabecera($cab, 'content-transfer-encoding')));
            if ($codif === 'base64') {
                // base64_decode ignora la basura en silencio: se limpia antes para que un
                // resto de delimitador no se convierta en bytes de mas.
                $contenido = base64_decode(preg_replace('/[^A-Za-z0-9+\/=]/', '', $cuerpoParte));
            } elseif ($codif === 'quoted-printable') {
                $contenido = quoted_printable_decode($cuerpoParte);
            } else {
                $contenido = $cuerpoParte;
            }

            $adjuntos[] = array(
                'nombre'    => $nombre,
                'tipo'      => trim(strtok($tipo, ';')),
                'contenido' => $contenido,
            );
        }
        return $adjuntos;
    }

    private static function _partir($texto)
    {
        $corte = preg_split('/\r?\n\r?\n/', $texto, 2);
        return array($corte[0], isset($corte[1]) ? $corte[1] : '');
    }

    /** Devuelve una cabecera con sus continuaciones (líneas que empiezan con espacio). */
    private static function _cabecera($cabeceras, $nombre)
    {
        $lineas = preg_split('/\r?\n/', $cabeceras);
        $acumulado = '';
        $dentro = false;
        foreach ($lineas as $linea) {
            if ($dentro && preg_match('/^[ \t]/', $linea)) {
                $acumulado .= ' ' . trim($linea);
                continue;
            }
            if ($dentro) { break; }
            if (stripos($linea, $nombre . ':') === 0) {
                $acumulado = trim(substr($linea, strlen($nombre) + 1));
                $dentro = true;
            }
        }
        return $acumulado;
    }

    private static function _nombre_archivo($cabeceras)
    {
        foreach (array('content-disposition', 'content-type') as $cab) {
            $valor = self::_cabecera($cabeceras, $cab);
            if ($valor === '') { continue; }
            // filename*=UTF-8''nombre.xml  (RFC 2231) o filename="nombre.xml"
            if (preg_match("/(?:file)?name\*=(?:[^']*'')?([^;\r\n]+)/i", $valor, $m)) {
                return trim(rawurldecode($m[1]), '"');
            }
            if (preg_match('/(?:file)?name="?([^";\r\n]+)"?/i', $valor, $m)) {
                $nombre = trim($m[1], '"');
                $dec = @iconv_mime_decode($nombre, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
                return $dec !== false ? $dec : $nombre;
            }
        }
        return '';
    }

    // -----------------------------------------------------------------------
    // Protocolo
    // -----------------------------------------------------------------------

    private function load_google()
    {
        $CI = get_instance();
        if (!isset($CI->googlemail)) {
            $CI->load->library('googlemail');
        }
        $this->googlemail = $CI->googlemail;
    }

    private function _orden($orden, $puedeContinuar = false)
    {
        $etiqueta = 'A' . str_pad(++$this->seq, 4, '0', STR_PAD_LEFT);
        $this->_escribir($etiqueta . ' ' . $orden);
        return $this->_leer_hasta($etiqueta, $puedeContinuar);
    }

    private function _escribir($linea)
    {
        fwrite($this->sock, $linea . "\r\n");
    }

    private function _leer_hasta($etiqueta, $puedeContinuar = false)
    {
        $lineas = array();
        while (true) {
            $linea = fgets($this->sock);
            if ($linea === false) {
                return array('ok' => false, 'detalle' => lang('imap_conexion_cortada'),
                             'lineas' => $lineas, 'etiqueta' => $etiqueta, 'continuar' => false);
            }
            $limpia = rtrim($linea, "\r\n");

            // '+' es un pedido de continuación: el servidor espera más datos.
            if ($puedeContinuar && strpos($limpia, '+') === 0) {
                return array('ok' => false, 'detalle' => trim(substr($limpia, 1)),
                             'lineas' => $lineas, 'etiqueta' => $etiqueta, 'continuar' => true);
            }
            $lineas[] = $limpia;

            if (strpos($limpia, $etiqueta . ' ') === 0) {
                $resto = substr($limpia, strlen($etiqueta) + 1);
                return array(
                    'ok'        => stripos($resto, 'OK') === 0,
                    'detalle'   => trim($resto),
                    'lineas'    => $lineas,
                    'etiqueta'  => $etiqueta,
                    'continuar' => false,
                );
            }
        }
    }

    /** Cadena entre comillas con los escapes que exige IMAP. */
    private function _literal($valor)
    {
        return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $valor) . '"';
    }
}

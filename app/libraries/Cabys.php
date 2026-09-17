<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Consulta del catalogo CABYS de Hacienda para validar un codigo antes de
 * emitir: un codigo de 13 digitos que no existe en el catalogo hace que
 * Hacienda rechace el comprobante entero.
 *
 * Comparte la cache de Hacienda_proxy (tec_hacienda_cache, tipo cabys_codigo).
 */
class Cabys
{
    const URL     = 'https://api.hacienda.go.cr/fe/cabys?codigo=';
    const TTL     = 604800;
    const TIMEOUT = 8;

    public function __get($var)
    {
        return get_instance()->$var;
    }

    /**
     * @return array{existe: bool, descripcion: string, impuesto: float}|null
     *         null si Hacienda no respondio y no hay nada en cache
     */
    public function consultar($codigo)
    {
        $codigo = preg_replace('/\D/', '', (string) $codigo);
        if (strlen($codigo) !== 13) {
            return array('existe' => false, 'descripcion' => '', 'impuesto' => 0.0);
        }

        $datos = $this->_cache($codigo);
        if ($datos === null) {
            $datos = $this->_api($codigo);
            if ($datos === null) {
                return null;
            }
        }

        $fila = null;
        foreach ((array) $datos as $d) {
            if (isset($d['codigo']) && (string) $d['codigo'] === $codigo) {
                $fila = $d;
                break;
            }
        }

        return $fila
            ? array('existe' => true, 'descripcion' => (string) $fila['descripcion'], 'impuesto' => (float) $fila['impuesto'])
            : array('existe' => false, 'descripcion' => '', 'impuesto' => 0.0);
    }

    /**
     * Impuesto del catalogo de impuestos que corresponde a la tarifa del CABYS.
     * El 0 % del CABYS es exento (tarifa 10); el 4 % es la tarifa reducida, no la transitoria.
     *
     * @return object|null fila de tec_impuestos
     */
    public function impuestoDeTarifa($tasa)
    {
        $preferida = array('13' => '08', '8' => '07', '4' => '04', '2' => '03', '1' => '02', '0.5' => '09', '0' => '10');
        $clave = rtrim(rtrim(number_format((float) $tasa, 2, '.', ''), '0'), '.');

        $this->db->where('codigo_impuesto', '01')->where('tasa_impuesto', (float) $tasa);
        $filas = $this->db->get('impuestos')->result();
        foreach ($filas as $f) {
            if (isset($preferida[$clave]) && $f->codigo_tarifa === $preferida[$clave]) {
                return $f;
            }
        }
        return $filas ? $filas[0] : null;
    }

    private function _cache($codigo)
    {
        $fila = $this->db->get_where('hacienda_cache', array('tipo' => 'cabys_codigo', 'clave' => $codigo), 1)->row();
        if (!$fila || (time() - strtotime($fila->fecha)) > (int) $fila->ttl) {
            return null;
        }
        $datos = json_decode($fila->respuesta, true);
        // Una respuesta vacia en cache no se da por buena: el codigo pudo agregarse al catalogo.
        return is_array($datos) && $datos ? $datos : null;
    }

    private function _api($codigo)
    {
        $ch = curl_init(self::URL . $codigo);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => array('Accept: application/json'),
        ));
        $cuerpo = curl_exec($ch);
        $http   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http !== 200) {
            log_message('error', '[Cabys] Hacienda no respondio para ' . $codigo . ' (HTTP ' . $http . ')');
            return null;
        }
        $datos = json_decode((string) $cuerpo, true);
        if (!is_array($datos)) {
            return null;
        }

        if ($datos) {
            $payload = array(
                'tipo' => 'cabys_codigo', 'clave' => $codigo,
                'respuesta' => json_encode($datos, JSON_UNESCAPED_UNICODE),
                'ttl' => self::TTL, 'fecha' => date('Y-m-d H:i:s'),
            );
            $this->db->delete('hacienda_cache', array('tipo' => 'cabys_codigo', 'clave' => $codigo));
            $this->db->insert('hacienda_cache', $payload);
        }
        return $datos;
    }
}

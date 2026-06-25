<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Proxy para la API pública de Hacienda Costa Rica.
 * Centraliza todas las llamadas a api.hacienda.go.cr, aplica caché local
 * y respeta los límites de consumo (100 req/5s, 1200 req/2min).
 * Ninguna llamada a Hacienda sale desde el navegador — solo desde aquí.
 */
class Hacienda_proxy extends MY_Controller
{
    const HACIENDA_BASE    = 'https://api.hacienda.go.cr/fe/';
    const TTL_AE           = 86400;   // 24 h  — situación tributaria
    const TTL_CABYS_CODIGO = 604800;  // 7 días — código exacto
    const TTL_CABYS_Q      = 3600;    // 1 h   — búsqueda por texto
    const CURL_TIMEOUT     = 10;

    function __construct()
    {
        parent::__construct();
        if (!$this->loggedIn) {
            $this->_json(['error' => 'No autorizado'], 401);
        }
    }

    // GET hacienda_proxy/ae/123456789
    public function ae($cedula = '')
    {
        $cedula = preg_replace('/\D/', '', trim($cedula));

        if (strlen($cedula) < 9 || strlen($cedula) > 12) {
            $this->_json(['error' => 'La identificación debe tener entre 9 y 12 dígitos numéricos'], 400);
        }

        $cached = $this->_cache_get('ae', $cedula);
        if ($cached !== null) {
            $this->_json($cached);
        }

        $resp = $this->_hacienda_get('ae?identificacion=' . $cedula);

        if ($resp['code'] === 200) {
            $data = json_decode($resp['body'], true) ?: [];
            $this->_cache_set('ae', $cedula, $data, self::TTL_AE);
            $this->_json($data);
        } elseif ($resp['code'] === 404) {
            $this->_json(['error' => 'Identificación no encontrada en Hacienda. Puede registrar el cliente manualmente.'], 404);
        } elseif ($resp['code'] === 429) {
            $this->_json(['error' => 'Límite de consultas a Hacienda superado. Intente en 10 minutos.'], 429);
        } elseif ($resp['code'] === 400) {
            $this->_json(['error' => 'Parámetro inválido para la API de Hacienda.'], 400);
        } else {
            $this->_json(['error' => 'Hacienda no respondió (HTTP ' . $resp['code'] . '). Registre el cliente manualmente.'], 503);
        }
    }

    // GET hacienda_proxy/cabys?q=texto&top=20
    // GET hacienda_proxy/cabys?codigo=1234567890123
    public function cabys()
    {
        $codigo = trim($this->input->get('codigo', TRUE));
        $q      = trim($this->input->get('q',      TRUE));
        $top    = min(abs((int)$this->input->get('top', TRUE)) ?: 20, 50);

        if ($codigo !== '') {
            $codigo = preg_replace('/\D/', '', $codigo);
            if (strlen($codigo) !== 13) {
                $this->_json(['error' => 'El código CABYS debe tener exactamente 13 dígitos'], 400);
            }
            $cached = $this->_cache_get('cabys_codigo', $codigo);
            if ($cached !== null) {
                $this->_json($cached);
            }
            $resp = $this->_hacienda_get('cabys?codigo=' . $codigo);
            if ($resp['code'] === 200) {
                $data = json_decode($resp['body'], true) ?: [];
                $this->_cache_set('cabys_codigo', $codigo, $data, self::TTL_CABYS_CODIGO);
                $this->_json($data);
            } else {
                $this->_json(['error' => 'Error consultando CABYS (HTTP ' . $resp['code'] . ')'], $resp['code'] ?: 503);
            }
        } elseif ($q !== '') {
            if (mb_strlen($q) < 3) {
                $this->_json(['error' => 'Ingrese al menos 3 caracteres para buscar'], 400);
            }
            $clave  = strtolower($q) . ':' . $top;
            $cached = $this->_cache_get('cabys_q', $clave);
            if ($cached !== null) {
                $this->_json($cached);
            }
            $resp = $this->_hacienda_get('cabys?q=' . urlencode($q) . '&top=' . $top);
            if ($resp['code'] === 200) {
                $raw  = json_decode($resp['body'], true) ?: [];
                // Normalizar: la API puede devolver array raíz o {cabys:[...]}
                $items = isset($raw['cabys']) ? $raw['cabys'] : (isset($raw[0]) ? $raw : []);
                $data  = [];
                foreach ($items as $item) {
                    $data[] = [
                        'codigo'      => $item['codigo']      ?? '',
                        'descripcion' => $item['descripcion'] ?? '',
                        'impuesto'    => $item['impuesto']    ?? 0,
                    ];
                }
                $this->_cache_set('cabys_q', $clave, $data, self::TTL_CABYS_Q);
                $this->_json($data);
            } elseif ($resp['code'] === 429) {
                $this->_json(['error' => 'Límite de consultas a Hacienda superado. Intente en 10 minutos.'], 429);
            } else {
                $this->_json(['error' => 'Error consultando CABYS (HTTP ' . $resp['code'] . ')'], $resp['code'] ?: 503);
            }
        } else {
            $this->_json(['error' => 'Debe indicar "codigo" o "q" como parámetro'], 400);
        }
    }

    // -----------------------------------------------------------------------
    // Privados
    // -----------------------------------------------------------------------

    private function _hacienda_get($endpoint)
    {
        $url = self::HACIENDA_BASE . $endpoint;
        log_message('info', '[HaciendaProxy] GET ' . $url);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::CURL_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body     = curl_exec($ch);
        $code     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($curl_err) {
            log_message('error', '[HaciendaProxy] cURL error para ' . $url . ': ' . $curl_err);
            return ['code' => 0, 'body' => ''];
        }

        log_message('info', '[HaciendaProxy] HTTP ' . $code . ' <- ' . $url);
        return ['code' => $code, 'body' => $body];
    }

    // POST hacienda_proxy/limpiar_cache_cabys
    public function limpiar_cache_cabys()
    {
        if (!$this->Admin) {
            $this->_json(['error' => 'No autorizado'], 403);
        }
        $this->db->like('tipo', 'cabys', 'after');
        $this->db->delete('tec_hacienda_cache');
        $eliminados = $this->db->affected_rows();
        log_message('info', '[HaciendaProxy] Caché CABYS limpiado — ' . $eliminados . ' registros eliminados');
        $this->_json(['ok' => true, 'eliminados' => $eliminados]);
    }

    // -----------------------------------------------------------------------
    // Privados
    // -----------------------------------------------------------------------

    private function _cache_get($tipo, $clave)
    {
        $row = $this->db
            ->get_where('tec_hacienda_cache', ['tipo' => $tipo, 'clave' => $clave], 1)
            ->row();

        if (!$row) return null;

        if ((time() - strtotime($row->fecha)) > (int)$row->ttl) {
            $this->db->delete('tec_hacienda_cache', ['tipo' => $tipo, 'clave' => $clave]);
            return null;
        }

        log_message('debug', '[HaciendaProxy] Cache HIT ' . $tipo . ':' . $clave);
        return json_decode($row->respuesta, true);
    }

    private function _cache_set($tipo, $clave, $data, $ttl)
    {
        $existe = $this->db
            ->get_where('tec_hacienda_cache', ['tipo' => $tipo, 'clave' => $clave], 1)
            ->num_rows();

        $payload = [
            'tipo'      => $tipo,
            'clave'     => $clave,
            'respuesta' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'ttl'       => (int)$ttl,
            'fecha'     => date('Y-m-d H:i:s'),
        ];

        if ($existe) {
            $this->db->update('tec_hacienda_cache', $payload, ['tipo' => $tipo, 'clave' => $clave]);
        } else {
            $this->db->insert('tec_hacienda_cache', $payload);
        }
    }

    private function _json($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}

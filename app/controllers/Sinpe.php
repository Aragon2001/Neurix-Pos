<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Panel de control de Configuración > SINPE Móvil.
 * No hace nada de la vigilancia de Gmail directamente — es un proxy delgado
 * hacia el servicio Node interno (ver www/sinpe-service), que es el que de
 * verdad guarda las transacciones en tec_sinpe_transactions. Este controlador
 * solo expone esos endpoints al navegador del admin, con la misma sesión y
 * permisos del Facturador (el servicio Node no tiene login propio, confía en
 * que solo le habla este servidor por 127.0.0.1 — ver README de sinpe-service).
 */
class Sinpe extends MY_Controller
{
    const CURL_TIMEOUT = 6;

    /** Metodos accesibles a cualquier usuario con sesion, no solo al admin. */
    const ABIERTOS_A_CAJEROS = ['nuevos', 'comprobante', 'ventas_candidatas', 'aplicar'];

    function __construct()
    {
        parent::__construct();
        $this->config->load('sinpe');
        $this->load->model('AuditLog_model', 'audit_log');

        // El vigilante corre desde un cron, sin sesion que revisar.
        if (is_cli()) { return; }

        // En CI3 un `return` en el constructor no evita que el metodo se ejecute.
        if (!$this->loggedIn) { $this->_cortar(['error' => 'No autorizado'], 401); }

        $metodo = strtolower((string) $this->router->fetch_method());
        if (!$this->Admin && !in_array($metodo, self::ABIERTOS_A_CAJEROS, TRUE)) {
            $this->_cortar(['error' => 'No autorizado'], 403);
        }
    }

    /**
     * GET sinpe/nuevos?desde=<id>
     *
     * SINPE sin aplicar posteriores al id indicado. Lee la tabla directo para
     * que los avisos sigan saliendo aunque el servicio Node este caido.
     */
    public function nuevos()
    {
        if (!$this->db->table_exists('sinpe_transactions')) {
            $this->_json(['nuevos' => [], 'ultimo_id' => 0]);
            return;
        }

        $desde = (int) $this->input->get('desde');

        // Con el builder limpio: un select() sin ejecutar se arrastraria al
        // select_max y MySQL lo rechazaria por ONLY_FULL_GROUP_BY.
        $ultimo = (int) ($this->db->select_max('id_sinpe_transaction', 'max_id')
                                  ->get('sinpe_transactions')->row()->max_id ?? 0);

        // En frio (desde=0) solo se entrega el punto de partida, sin avisar.
        $filas = [];
        if ($desde > 0) {
            $filas = $this->db
                ->select('id_sinpe_transaction, comprobante, nombre, telefono, monto, fecha, banco')
                ->where('estado', 'pendiente')
                ->where('id_sinpe_transaction >', $desde)
                ->order_by('id_sinpe_transaction', 'ASC')
                ->limit(10)
                ->get('sinpe_transactions')->result();
        }

        $this->_json([
            'nuevos'    => $filas,
            'ultimo_id' => $ultimo,
        ]);
    }

    /** GET sinpe/comprobante/<id> — colilla imprimible, formato 80 mm. */
    public function comprobante($id = NULL)
    {
        $id = (int) $id;
        if (!$id || !$this->db->table_exists('sinpe_transactions')) { show_404(); return; }

        $tx = $this->db->get_where('sinpe_transactions', ['id_sinpe_transaction' => $id])->row();
        if (!$tx) { show_404(); return; }

        $venta = NULL;
        if ($tx->sale_id) {
            $venta = $this->db->select('id, date, grand_total, customer_name, consecutivo')
                              ->get_where('sales', ['id' => $tx->sale_id])->row();
        }

        $this->load->view($this->theme . 'sinpe/comprobante', [
            'tx'       => $tx,
            'venta'    => $venta,
            'Settings' => $this->Settings,
        ]);
    }

    /**
     * GET sinpe/ventas_candidatas/<id>
     *
     * Ventas del mismo dia que el pago alcanza a cubrir y que no tienen otro
     * SINPE aplicado.
     */
    public function ventas_candidatas($id = NULL)
    {
        $id = (int) $id;
        $tx = $this->db->get_where('sinpe_transactions', ['id_sinpe_transaction' => $id])->row();
        if (!$tx) { $this->_json(['error' => 'Transaccion no encontrada'], 404); return; }

        $st = $this->db->dbprefix('sinpe_transactions');
        $sa = $this->db->dbprefix('sales');
        $ht = $this->db->dbprefix('hacienda_tiketes');

        $ventas = $this->db
            ->select("{$sa}.id, {$sa}.date, {$sa}.customer_name, {$sa}.grand_total,
                      {$sa}.consecutivo, {$ht}.estatus_hacienda, {$ht}.tipo_doc", FALSE)
            ->from('sales')
            ->join('hacienda_tiketes', "{$ht}.sale_id = {$sa}.id", 'left')
            ->where("{$sa}.grand_total <=", (float) $tx->monto + 1)
            ->where("{$sa}.date >=", date('Y-m-d 00:00:00', strtotime($tx->fecha)))
            ->where("{$sa}.date <=", date('Y-m-d 23:59:59', strtotime($tx->fecha)))
            ->where("{$sa}.id NOT IN (SELECT sale_id FROM {$st} WHERE sale_id IS NOT NULL)", NULL, FALSE)
            ->order_by("{$sa}.id", 'DESC')
            ->limit(30)
            ->get()->result();

        foreach ($ventas as $v) {
            $v->corregible = $this->_puede_corregirse($v->estatus_hacienda);
        }

        $this->_json(['transaccion' => $tx, 'ventas' => $ventas]);
    }

    /**
     * POST sinpe/aplicar (sinpe_id, sale_id)
     *
     * Enlaza el pago con la venta y guarda la referencia en tec_payments.
     * El pago se registra siempre; si el comprobante ya fue aceptado por
     * Hacienda es inmutable y la respuesta lo advierte.
     */
    public function aplicar()
    {
        $sinpe_id = (int) $this->input->post('sinpe_id');
        $sale_id  = (int) $this->input->post('sale_id');

        $tx = $this->db->get_where('sinpe_transactions', ['id_sinpe_transaction' => $sinpe_id])->row();
        if (!$tx) { $this->_json(['error' => 'Transaccion SINPE no encontrada'], 404); return; }
        if ($tx->estado === 'usado') {
            $this->_json(['error' => 'Ese pago ya se aplico a la venta #' . $tx->sale_id], 409); return;
        }
        if (!$tx->comprobante) {
            $this->_json(['error' => 'El pago no tiene numero de referencia'], 422); return;
        }

        $venta = $this->db->get_where('sales', ['id' => $sale_id])->row();
        if (!$venta) { $this->_json(['error' => 'Venta no encontrada'], 404); return; }

        $doc = $this->db->select('estatus_hacienda, tipo_doc')
                        ->get_where('hacienda_tiketes', ['sale_id' => $sale_id])->row();
        $estatus = isset($doc->estatus_hacienda) ? $doc->estatus_hacienda : NULL;

        // El WHERE por estado evita que dos cajeros apliquen el mismo comprobante.
        $this->db->where('id_sinpe_transaction', $sinpe_id)
                 ->where('estado', 'pendiente')
                 ->update('sinpe_transactions', ['estado' => 'usado', 'sale_id' => $sale_id]);

        if ($this->db->affected_rows() < 1) {
            $this->_json(['error' => 'Otro usuario acaba de aplicar ese pago'], 409);
            return;
        }

        $this->db->where('sale_id', $sale_id)
                 ->where('paid_by', 'sinpe')
                 ->update('payments', ['reference' => $tx->comprobante]);

        $this->_json([
            'ok'               => TRUE,
            'sale_id'          => $sale_id,
            'referencia'       => $tx->comprobante,
            'estatus_hacienda' => $estatus,
            'corregible'       => $this->_puede_corregirse($estatus),
            'aviso'            => $this->_aviso_hacienda($estatus),
        ]);
    }

    /** Solo admite correccion un comprobante que nunca fue aceptado. */
    private function _puede_corregirse($estatus)
    {
        return in_array($estatus, [NULL, '', 'error', 'rechazado'], TRUE);
    }

    private function _aviso_hacienda($estatus)
    {
        if ($estatus === 'aceptado') {
            return 'El pago quedo registrado, pero la factura ya fue ACEPTADA por Hacienda y un '
                 . 'comprobante aceptado no se puede modificar ni reenviar. Para que la referencia '
                 . 'del SINPE conste en el comprobante habria que anular esa factura con una nota '
                 . 'de credito y emitirla de nuevo.';
        }
        if ($estatus === 'procesando') {
            return 'El pago quedo registrado. La factura esta en proceso en Hacienda: hay que '
                 . 'esperar la respuesta antes de decidir si se puede corregir.';
        }
        if ($estatus === 'error' || $estatus === 'rechazado') {
            return 'El pago quedo registrado. La factura no fue aceptada por Hacienda, asi que al '
                 . 'reenviarla la referencia del SINPE va incluida.';
        }
        return 'El pago quedo registrado. La factura todavia no se ha enviado a Hacienda, asi que '
             . 'la referencia del SINPE va a ir incluida cuando se emita.';
    }

    private function _cortar($data, $status)
    {
        $this->output->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE))
            ->_display();
        exit;
    }

    // GET sinpe/estado
    public function estado()
    {
        $r = $this->_svc_call('GET', '/api/estado');
        $data = json_decode($r['body'], true);
        if ($data === null) {
            // Sin este distintivo la pantalla mostraba "no conectado" y ofrecia el
            // boton de Gmail, que no puede funcionar si el proceso Node no corre.
            $data = ['servicio' => false, 'conectado' => false, 'activo' => false, 'watching' => false,
                      'error' => lang('sinpe_servicio_caido')];
        } else {
            $data['servicio'] = true;
        }
        $this->_json($data);
    }

    // GET sinpe/conectar — redirige el navegador a Google vía el servicio Node
    public function conectar()
    {
        $r = $this->_svc_call('GET', '/oauth2/authurl');
        $data = json_decode($r['body'], true);
        if (!empty($data['url'])) {
            redirect($data['url']);
            return;
        }
        $this->session->set_flashdata('error', lang('sinpe_servicio_caido'));
        redirect('settings');
    }

    // POST sinpe/desconectar
    public function desconectar()
    {
        $r = $this->_svc_call('POST', '/api/desconectar', []);
        $this->_json(json_decode($r['body'], true) ?: ['ok' => false], $r['code'] ?: 503);
    }

    // POST sinpe/importar (dias — 0 = historial completo).
    // Responde de inmediato; el avance se sigue por sinpe/estado.
    public function importar()
    {
        $dias = (int) $this->input->post('dias');
        $r = $this->_svc_call('POST', '/api/importar', ['dias' => $dias < 0 ? 0 : $dias]);
        $this->_json(json_decode($r['body'], true) ?: ['error' => 'Servicio SINPE no disponible'], $r['code'] ?: 503);
    }

    // POST sinpe/banco   (POST: banco)
    public function banco()
    {
        $body = ['banco' => $this->input->post('banco')];
        $r = $this->_svc_call('POST', '/api/banco', $body);
        $this->_json(json_decode($r['body'], true) ?: ['error' => 'Servicio SINPE no disponible'], $r['code'] ?: 503);
    }

    // POST sinpe/vigilancia   (POST: activar=0|1, intervalo)
    public function vigilancia()
    {
        $body = [];
        if ($this->input->post('activar') !== NULL) {
            $body['activar'] = ($this->input->post('activar') == '1' || $this->input->post('activar') === 'true');
        }
        if ($this->input->post('intervalo')) {
            $body['intervalo'] = (int) $this->input->post('intervalo');
        }
        $r = $this->_svc_call('POST', '/api/vigilancia', $body);
        $this->_json(json_decode($r['body'], true) ?: ['error' => 'Servicio SINPE no disponible'], $r['code'] ?: 503);
    }

    /**
     * Vigilante del servicio Node. Pensado para un cron:
     *
     *     php index.php sinpe vigilante
     *
     * El servicio se cae sin avisar y el unico sintoma es que dejan de
     * registrarse pagos. Avisa por correo la primera vez que deja de responder
     * y otra vez cuando vuelve; entre medio no repite nada. El estado anterior
     * sale de tec_audit_log, asi que no hace falta guardarlo aparte.
     */
    public function vigilante()
    {
        $r    = $this->_svc_call('GET', '/api/estado');
        $vivo = $r['code'] === 200 && json_decode($r['body'], true) !== NULL;

        $ultimo      = $this->audit_log->ultimaAccion(['sinpe_caido', 'sinpe_restablecido']);
        $estabaCaido = $ultimo && $ultimo->action === 'sinpe_caido';

        // "vivo" y "estaba caido" coinciden solo cuando el estado cambio.
        if ($vivo !== $estabaCaido) {
            $this->_salida_vigilante($vivo ? 'sin cambios: el servicio responde' : 'sin cambios: el servicio sigue caido');
            return;
        }

        $accion = $vivo ? 'sinpe_restablecido' : 'sinpe_caido';
        $desde  = $ultimo ? $this->tec->hrld($ultimo->created_at) : '';

        $this->audit_log->log($accion, 'sinpe_service', 0, (string) $this->config->item('sinpe_service_url'));
        $this->_avisar_estado_sinpe($vivo, $desde);
        $this->_salida_vigilante($vivo ? 'el servicio volvio, aviso enviado' : 'el servicio no responde, aviso enviado');
    }

    // -----------------------------------------------------------------------
    // Privados
    // -----------------------------------------------------------------------

    /** El vigilante corre por cron: en consola informa, por navegador responde JSON. */
    private function _salida_vigilante($detalle)
    {
        if (is_cli()) {
            echo '[vigilante SINPE] ' . $detalle . PHP_EOL;
            return;
        }
        $this->_json(['detalle' => $detalle]);
    }

    /**
     * Correo de caida o de recuperacion del servicio.
     *
     * Se manda directo y no por la cola: el worker se despacha por HTTP y en
     * consola no hay host que resolver.
     */
    private function _avisar_estado_sinpe($vivo, $desde)
    {
        $para = $this->Settings->email_emisor ?: $this->Settings->default_email;
        if (!$para) {
            log_message('error', '[Sinpe] servicio ' . ($vivo ? 'restablecido' : 'caido') . ' y no hay correo a quien avisar');
            return;
        }

        $asunto = ($vivo ? lang('sinpe_aviso_ok_asunto') : lang('sinpe_aviso_caido_asunto'))
                . ' - ' . $this->Settings->site_name;

        $cuerpo = '<p style="font-family:sans-serif;font-size:14px;color:#0f172a;">'
                . html_escape($vivo ? lang('sinpe_aviso_ok_texto') : lang('sinpe_aviso_caido_texto'))
                . '</p>';

        if ($desde) {
            $cuerpo .= '<p style="font-family:sans-serif;font-size:13px;color:#475569;">'
                     . html_escape(sprintf(lang('sinpe_aviso_desde'), $desde)) . '</p>';
        }

        $this->load->library('Swiftmailer', null, 'Swiftmailer');
        if (!$this->Swiftmailer->send_email($para, $asunto, $cuerpo)) {
            log_message('error', '[Sinpe] no se pudo enviar el aviso de estado a ' . $para);
        }
    }

    private function _svc_call($method, $path, $body = null)
    {
        $base = rtrim((string) $this->config->item('sinpe_service_url'), '/');
        $url  = $base . $path;

        $ch = curl_init($url);
        $cabeceras = [];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::CURL_TIMEOUT,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = http_build_query($body);
            $cabeceras[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $testigo = (string) $this->config->item('sinpe_service_token');
        if ($testigo !== '') {
            $cabeceras[] = 'X-Service-Token: ' . $testigo;
        }
        if ($cabeceras) {
            $opts[CURLOPT_HTTPHEADER] = $cabeceras;
        }
        curl_setopt_array($ch, $opts);
        $out  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', '[Sinpe] cURL error hacia ' . $url . ': ' . $err);
            return ['code' => 0, 'body' => ''];
        }
        return ['code' => $code, 'body' => $out];
    }

    private function _json($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}

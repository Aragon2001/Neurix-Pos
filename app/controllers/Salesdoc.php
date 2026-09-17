<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Detalle de una venta y sus acciones, servido en JSON para el modal del
 * listado de Ventas. Todo lo que hace este controlador ya existia repartido
 * entre Pos, PosView, PosPrint, PosEmail y Shacienda; aca se expone por venta
 * y sin salir de la pantalla.
 */
class Salesdoc extends MY_Controller
{
    /** Marca si ya se emitio la respuesta, para el apagado de emergencia. */
    private $respondido = false;

    /** Solo se vacian los buffers cuando fue este controlador quien los abrio. */
    private $blindado = false;

    function __construct()
    {
        parent::__construct();
        if (!$this->loggedIn) {
            $this->_cortar(array('error' => lang('access_denied')), 401);
        }
        $this->load->model('pos_model');
        $this->load->model('sales_model');
        $this->load->model('hacienda_model');
    }

    /* ═══════════════════════════ LECTURA ═══════════════════════════ */

    /** GET sales/documento/<id> — la venta completa, lista para pintar. */
    public function documento($id = null)
    {
        $venta   = $this->_venta($id);
        $hac     = $this->hacienda_model->getInvoice($venta->id);
        $anulada = $this->_anulacionDe($venta->id);

        $this->_json(array(
            'venta'        => $this->_bloqueVenta($venta),
            'anulacion'    => $anulada ? $this->_bloqueAnulacion($anulada) : null,
            'cliente'      => $this->_bloqueCliente($venta),
            'emisor'       => $this->_bloqueEmisor($venta),
            'lineas'       => $this->_bloqueLineas($venta->id),
            'pagos'        => $this->_bloquePagos($venta->id),
            'hacienda'     => $this->_bloqueHacienda($hac),
            'relacionados' => $this->_bloqueRelacionados($venta->id),
            'acciones'     => $this->_bloqueAcciones($hac, $anulada),
            'enlaces'      => $this->_bloqueEnlaces($venta->id),
        ));
    }

    /**
     * GET sales/documento_xml/<id>/<cual> — el XML como texto, para la pestaña.
     *
     * @param string $cual 'firmado' (lo que se envio) o 'respuesta' (el acuse)
     */
    public function documento_xml($id = null, $cual = 'firmado')
    {
        $venta = $this->_venta($id);
        $hac   = $this->hacienda_model->getInvoice($venta->id);

        $xml = $this->_xmlDe($hac, $cual);
        if ($xml === '') {
            $this->_json(array(
                'xml'   => '',
                'aviso' => $cual === 'respuesta' ? lang('nxd_sin_respuesta') : lang('nxd_sin_xml'),
            ));
            return;
        }

        $this->_json(array('xml' => $this->_sangrarXml($xml), 'aviso' => ''));
    }

    /** GET sales/descargar_xml/<id>/<cual> — el mismo XML como archivo. */
    public function descargar_xml($id = null, $cual = 'firmado')
    {
        $venta = $this->_venta($id);
        $hac   = $this->hacienda_model->getInvoice($venta->id);
        $xml   = $this->_xmlDe($hac, $cual);

        if ($xml === '') {
            show_404();
            return;
        }

        // El prefijo es el que ya usan los adjuntos del correo: T para el
        // comprobante firmado, M para el mensaje de respuesta de Hacienda.
        $clave  = !empty($hac->clave) ? $hac->clave : (string) $venta->id;
        $nombre = ($cual === 'respuesta' ? 'M' : 'T') . '_' . $clave . '.xml';

        $this->output
            ->set_content_type('application/xml', 'utf-8')
            ->set_header('Content-Disposition: attachment; filename="' . $nombre . '"')
            ->set_output($xml);
    }

    /** GET sales/bitacora/<id> — movimientos registrados sobre la venta. */
    public function bitacora($id = null)
    {
        $venta = $this->_venta($id);

        if (!$this->db->table_exists('audit_log')) {
            $this->_json(array('movimientos' => array()));
            return;
        }

        $filas = $this->db
            ->where('entity', 'sale')
            ->where('entity_id', (int) $venta->id)
            ->order_by('id', 'DESC')
            ->limit(60)
            ->get($this->db->dbprefix('audit_log'))
            ->result();

        $out = array();
        foreach ($filas as $f) {
            $out[] = array(
                'fecha'   => $f->created_at,
                'accion'  => $f->action,
                'detalle' => $f->detail,
                'monto'   => (float) $f->amount,
                'usuario' => $f->user_email,
            );
        }
        $this->_json(array('movimientos' => $out));
    }

    /* ═══════════════════════════ ACCIONES ═══════════════════════════ */

    /**
     * POST sales/reenviar/<id> — empuja el comprobante hacia Hacienda.
     *
     * Tres caminos distintos segun donde quedo el comprobante:
     *   sin fila            → se genera, se firma y se envia
     *   pendiente / error   → se reenvia la misma clave, que pudo no haber llegado
     *   rechazado           → se vuelve a emitir con clave y consecutivo nuevos,
     *                         porque Hacienda ya registro la anterior y reenviarla
     *                         devuelve el mismo rechazo
     * Un comprobante aceptado o anulado no se toca: es inmutable.
     */
    public function reenviar($id = null)
    {
        $venta = $this->_venta($id);
        $hac   = $this->hacienda_model->getInvoice($venta->id);
        $info  = estado_hacienda_info($hac ? $hac->estatus_hacienda : null);

        if (in_array($info['clave'], array('aceptado', 'parcial'), true)) {
            $this->_json(array('error' => lang('nxd_aceptado_inmutable')), 409);
            return;
        }
        if ($info['clave'] === 'anulado') {
            $this->_json(array('error' => lang('nxd_ya_anulado')), 409);
            return;
        }
        // Volver a emitir mueve el consecutivo y con el la contabilidad.
        $reemitir = ($info['clave'] === 'rechazado');
        if ($reemitir && !$this->Admin) {
            $this->_json(array('error' => lang('access_denied')), 403);
            return;
        }

        // getTokenH() y send_invoice() imprimen y cortan la peticion cuando
        // Hacienda niega las credenciales. Sin esto el modal recibiria ese
        // texto suelto en vez de un JSON y no sabria que decir.
        $this->_blindar_salida();
        set_time_limit(180);

        try {
            if (!$hac || $reemitir || trim((string) $hac->xml) === '') {
                $hac = $this->_emitirComprobante($venta, $hac, $reemitir);
            }
            if (!$hac) {
                $this->_json(array('error' => lang('nxd_reenvio_error')), 500);
                return;
            }

            $hac = $this->_firmarSiFalta($hac);
            if (trim((string) $hac->xml_sign) === '') {
                $this->_json(array('error' => lang('firma_fallida')), 500);
                return;
            }

            $estado = $this->_enviarAHacienda($hac);
        } catch (\Throwable $e) {
            log_message('error', 'Salesdoc: fallo el reenvio de la venta ' . $venta->id . ': ' . $e->getMessage());
            $this->_json(array('error' => lang('nxd_reenvio_error') . ' ' . $e->getMessage()), 500);
            return;
        }

        $this->load->library('correo_comprobante');
        $this->correo_comprobante->enviarSiAceptada($venta->id);

        $this->_auditar('sale_reenviada', $venta, ($reemitir ? 'Reemitida' : 'Reenviada')
            . ' · consecutivo ' . $hac->consecutivo . ' → ' . $estado['clave']);

        $this->_json(array(
            'estado'   => $estado,
            'hacienda' => $this->_bloqueHacienda($this->hacienda_model->getInvoice($venta->id)),
            'reemitido' => $reemitir,
        ));
    }

    /**
     * POST sales/consultar/<id> — le pregunta a Hacienda en que quedo el
     * comprobante, sin volver a enviarlo. Es la accion correcta mientras el
     * estado es 'procesando'.
     */
    public function consultar($id = null)
    {
        $venta = $this->_venta($id);
        $hac   = $this->hacienda_model->getInvoice($venta->id);

        if (!$hac || empty($hac->clave)) {
            $this->_json(array('error' => lang('nxd_sin_xml')), 409);
            return;
        }

        $this->_blindar_salida();
        set_time_limit(120);

        $this->load->library('Apiclient', NULL, 'ApiClient');
        $this->ApiClient->getTokenH();
        $mensaje = $this->ApiClient->getMensajeHacienda($hac->clave);
        $this->ApiClient->CloseTokenH();

        if (isset($mensaje->respuestaxml)) {
            $this->hacienda_model->insertHacienda(array(
                'xml_hacienda'     => base64_decode($mensaje->respuestaxml),
                'estatus_hacienda' => isset($mensaje->indestado) ? $mensaje->indestado : 'procesando',
            ), $hac->clave);
        }

        // Si la consulta es la que descubre la aceptacion, el correo sale ahora
        // y no queda esperando a que alguien abra el POS.
        $this->load->library('correo_comprobante');
        $correo = $this->correo_comprobante->enviarSiAceptada($venta->id);

        $fresco = $this->hacienda_model->getInvoice($venta->id);
        $this->_json(array(
            'correo'   => $correo,
            'estado'   => estado_hacienda_info($fresco ? $fresco->estatus_hacienda : null),
            'hacienda' => $this->_bloqueHacienda($fresco),
        ));
    }

    /**
     * GET sales/info_anulacion/<id> — lo que el dialogo necesita preguntar.
     *
     * Anular una factura aceptada y anular una que Hacienda nunca acepto son
     * dos operaciones distintas, y solo el servidor sabe cual toca.
     */
    public function info_anulacion($id = null)
    {
        $venta = $this->_venta($id);
        $hac   = $this->hacienda_model->getInvoice($venta->id);
        $info  = estado_hacienda_info($hac ? $hac->estatus_hacienda : null);
        $ya    = $this->_anulacionDe($venta->id);

        $efectivo = 0.0;
        foreach ((array) $this->pos_model->getAllSalePayments($venta->id) as $p) {
            if (familia_pago($p->paid_by) === 'efectivo') {
                $efectivo += (float) ($p->pos_paid > 0 ? $p->pos_paid : $p->amount);
            }
        }
        // Lo entregado de mas fue vuelto, no ingreso: no se devuelve dos veces.
        $efectivo = min($efectivo, (float) $venta->grand_total);

        $this->_json(array(
            'ya_anulada'   => (bool) $ya,
            'anulacion'    => $ya ? $this->_bloqueAnulacion($ya) : null,
            'permitida'    => $this->_puedeAnularse($info),
            'motivo_bloqueo' => $this->_porqueNoSePuedeAnular($info),
            // 'fiscal' exige nota de credito; 'interna' no le dice nada a Hacienda.
            'tipo'         => in_array($info['clave'], array('aceptado', 'parcial'), true) ? 'fiscal' : 'interna',
            'estado'       => $info,
            'de_otro_dia'  => date('Y-m-d', strtotime($venta->date)) !== date('Y-m-d'),
            'fecha_venta'  => $venta->date,
            'gran_total'   => (float) $venta->grand_total,
            'pagado'       => (float) $venta->paid,
            'efectivo'     => $efectivo,
            'caja_abierta' => (bool) $this->session->userdata('register_id'),
            // Sin PIN configurado en Ajustes no se puede autorizar la salida de
            // efectivo, y conviene decirlo antes y no como "PIN incorrecto".
            'pin_configurado' => $this->_hayPinDeCajon(),
            'es_admin'     => (bool) $this->Admin,
        ));
    }

    /**
     * POST sales/anular/<id> — anula la venta.
     *
     * Un comprobante aceptado por Hacienda es inmutable: la unica forma de
     * anularlo es emitir una nota de credito por el 100% con codigo de
     * referencia 01, que es lo que se hace aca sin pasar por el punto de venta,
     * porque en una anulacion no hay nada que elegir.
     *
     * Si nunca fue aceptado no hay nada que decirle a Hacienda: la anulacion es
     * interna y solo saca el comprobante de circulacion.
     *
     * La venta no se borra nunca. Queda con su fila en tec_sale_anulaciones,
     * que guarda quien la anulo, cuando, por que, y que paso con el dinero.
     */
    public function anular($id = null)
    {
        $venta = $this->_venta($id);

        if (!$this->Admin) {
            $this->_json(array('error' => lang('access_denied')), 403);
            return;
        }
        if ($this->_anulacionDe($venta->id)) {
            $this->_json(array('error' => lang('nxd_ya_anulado')), 409);
            return;
        }

        $motivo = trim((string) $this->input->post('motivo'));
        if (mb_strlen($motivo) < 5) {
            $this->_json(array('error' => lang('anu_motivo_requerido')), 422);
            return;
        }

        $hac  = $this->hacienda_model->getInvoice($venta->id);
        $info = estado_hacienda_info($hac ? $hac->estatus_hacienda : null);
        if (!$this->_puedeAnularse($info)) {
            $this->_json(array('error' => $this->_porqueNoSePuedeAnular($info)), 409);
            return;
        }

        $devuelve = (string) $this->input->post('devuelve_dinero') === '1';
        $monto    = round((float) $this->input->post('monto_devuelto'), 2);
        if ($devuelve && $monto <= 0) {
            $this->_json(array('error' => lang('anu_monto_requerido')), 422);
            return;
        }
        if ($monto > (float) $venta->grand_total + 0.01) {
            $this->_json(array('error' => lang('anu_monto_excede')), 422);
            return;
        }

        // El cajon solo se abre para entregar efectivo, y solo con PIN: es
        // dinero saliendo de la caja de alguien mas.
        $medio  = (string) $this->input->post('medio_devolucion');
        $enCaja = $devuelve && $medio === 'efectivo';
        $bytesCajon = null;
        $pinOk = false;
        if ($enCaja) {
            if (!$this->_hayPinDeCajon()) {
                $this->_json(array('error' => lang('anu_sin_pin_configurado')), 409);
                return;
            }
            $pinOk = $this->_pinDelCajonValido((string) $this->input->post('pin'));
            if (!$pinOk) {
                $this->_auditar('anulacion_pin_fallido', $venta, 'PIN invalido al intentar anular');
                $this->_json(array('error' => lang('wrong_pin')), 403);
                return;
            }
            $bytesCajon = $this->_bytesAbrirCajon();
        }

        $fiscal = in_array($info['clave'], array('aceptado', 'parcial'), true);

        $this->_blindar_salida();
        set_time_limit(180);

        $cn = null;
        try {
            if ($fiscal) {
                $cn = $this->_emitirNotaDeAnulacion($venta, $hac, $motivo);
                if (!$cn) {
                    $this->_json(array('error' => lang('anu_nc_fallida')), 500);
                    return;
                }
            } else {
                // addNoteCredit ya devuelve la existencia; sin nota hay que hacerlo aca.
                $this->_devolverExistencias($venta);
                $this->db->update($this->db->dbprefix('hacienda_tiketes'),
                    array('estatus_hacienda' => 'anulado'), array('sale_id' => (int) $venta->id));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Salesdoc: fallo la anulacion de la venta ' . $venta->id . ': ' . $e->getMessage());
            $this->_json(array('error' => lang('anu_fallida') . ' ' . $e->getMessage()), 500);
            return;
        }

        $anulacion = array(
            'sale_id'          => (int) $venta->id,
            'cn_id'            => $cn ? (int) $cn['id_cn'] : null,
            'tipo'             => $fiscal ? 'fiscal' : 'interna',
            'codigo_ref'       => $fiscal ? '01' : null,
            'motivo'           => mb_substr($motivo, 0, 255),
            'devuelve_dinero'  => $devuelve ? 1 : 0,
            'monto_devuelto'   => $devuelve ? $monto : 0,
            'medio_devolucion' => $devuelve ? $medio : null,
            'pin_verificado'   => $pinOk ? 1 : 0,
            'cajon_abierto'    => $bytesCajon ? 1 : 0,
            'register_id'      => $this->session->userdata('register_id') ?: null,
            'store_id'         => (int) $venta->store_id,
            'created_by'       => (int) $this->session->userdata('user_id'),
            'created_at'       => date('Y-m-d H:i:s'),
            'ip'               => $this->input->ip_address(),
        );
        $this->db->insert($this->db->dbprefix('sale_anulaciones'), $anulacion);

        $rastro = ($fiscal ? 'Nota de credito ' . ($cn['consecutivo'] ?? '') : 'Anulacion interna')
            . ' · ' . $motivo
            . ($devuelve ? ' · devuelve ' . $this->tec->formatMoney($monto) . ' en ' . $medio : ' · sin devolucion de dinero');
        $this->_auditar('venta_anulada', $venta, $rastro);

        $this->_json(array(
            'tipo'       => $anulacion['tipo'],
            'nota'       => $cn,
            'anulacion'  => $this->_bloqueAnulacion((object) $anulacion),
            'bytes_cajon' => $bytesCajon,
            'hacienda'   => $this->_bloqueHacienda($this->hacienda_model->getInvoice($venta->id)),
        ));
    }

    /* ─────────────────── apoyo de la anulacion ─────────────────── */

    /** Fila de anulacion de una venta, si ya fue anulada. */
    private function _anulacionDe($sale_id)
    {
        if (!$this->db->table_exists('sale_anulaciones')) {
            return false;
        }
        $q = $this->db->where('sale_id', (int) $sale_id)
            ->order_by('id', 'DESC')->limit(1)
            ->get($this->db->dbprefix('sale_anulaciones'));
        return $q->num_rows() ? $q->row() : false;
    }

    private function _bloqueAnulacion($a)
    {
        $quien = $this->site->getUser($a->created_by);

        // La anulacion fiscal solo esta firme cuando Hacienda acepta la nota;
        // el estado no se guarda aparte, se lee de la nota misma.
        $nota = $a->cn_id ? $this->hacienda_model->getCN((int) $a->cn_id) : false;

        return array(
            'tipo'       => $a->tipo,
            'motivo'     => $a->motivo,
            'fecha'      => $a->created_at,
            'usuario'    => $quien ? trim($quien->first_name . ' ' . $quien->last_name) : '',
            'devolucion' => (float) $a->monto_devuelto,
            'medio'      => (string) $a->medio_devolucion,
            'cn_id'      => $a->cn_id ? (int) $a->cn_id : null,
            'consecutivo_nota' => $nota ? (string) $nota->consecutivo : '',
            'estado'     => estado_anulacion_info($a->tipo, $nota ? $nota->estatus_hacienda : null),
        );
    }

    /**
     * POST sales/reintentar_nota/<id> — vuelve a emitir la nota de anulacion.
     *
     * Se usa cuando la anulacion quedo pendiente o la nota salio rechazada. Una
     * nota rechazada no se reenvia: Hacienda ya registro su clave, asi que se
     * emite otra con consecutivo nuevo, igual que con las facturas.
     */
    public function reintentar_nota($id = null)
    {
        $venta = $this->_venta($id);

        if (!$this->Admin) {
            $this->_json(array('error' => lang('access_denied')), 403);
            return;
        }

        $anulacion = $this->_anulacionDe($venta->id);
        if (!$anulacion || $anulacion->tipo !== 'fiscal') {
            $this->_json(array('error' => lang('anu_fallida')), 409);
            return;
        }

        $nota   = $anulacion->cn_id ? $this->hacienda_model->getCN((int) $anulacion->cn_id) : false;
        $estado = estado_anulacion_info($anulacion->tipo, $nota ? $nota->estatus_hacienda : null);
        if (!$estado['reintentable']) {
            $this->_json(array('error' => $estado['nota']), 409);
            return;
        }

        $this->_blindar_salida();
        set_time_limit(180);

        try {
            $hac = $this->hacienda_model->getInvoice($venta->id);
            $rechazada = estado_hacienda_info($nota ? $nota->estatus_hacienda : null)['clave'];

            if (in_array($rechazada, array('rechazado', 'error'), true)) {
                // La nota rechazada no se borra: ya consumio su consecutivo y
                // Hacienda tiene su clave registrada. Si se borrara, la nota
                // nueva reusaria ese numero y send_invoice() —que no reenvia lo
                // que ya existe— devolveria otra vez el mismo rechazo.
                $this->_auditar('anulacion_nota_reemitida', $venta,
                    'Reemplaza la nota ' . $nota->consecutivo . ' (' . $rechazada . ')');

                $nueva = $this->_emitirNotaDeAnulacion($venta, $hac, $anulacion->motivo);
                if (!$nueva) {
                    $this->_json(array('error' => lang('anu_nc_fallida')), 500);
                    return;
                }
                $this->db->update($this->db->dbprefix('sale_anulaciones'),
                    array('cn_id' => (int) $nueva['id_cn']), array('id' => (int) $anulacion->id));
                $resultado = $nueva;
            } else {
                // Todavia no llego a Hacienda o quedo a medias: se manda tal cual.
                $resultado = array(
                    'id_cn'       => (int) $anulacion->cn_id,
                    'consecutivo' => $nota->consecutivo,
                    'clave'       => $nota->clave,
                    'estado'      => $this->_enviarNotaCredito((int) $anulacion->cn_id),
                    'url'         => site_url('creditnotes/viewnc/' . (int) $anulacion->cn_id),
                );
            }
        } catch (\Throwable $e) {
            log_message('error', 'Salesdoc: fallo el reintento de la nota de la venta ' . $venta->id . ': ' . $e->getMessage());
            $this->_json(array('error' => lang('anu_nc_fallida')), 500);
            return;
        }

        $fresca = $this->_anulacionDe($venta->id);
        $this->_json(array(
            'nota'      => $resultado,
            'anulacion' => $fresca ? $this->_bloqueAnulacion($fresca) : null,
        ));
    }

    /**
     * Un comprobante en proceso no se puede anular: hasta que Hacienda no
     * responda no se sabe si hay algo que anular.
     */
    private function _puedeAnularse($info)
    {
        return !in_array($info['clave'], array('procesando', 'anulado'), true);
    }

    private function _porqueNoSePuedeAnular($info)
    {
        if ($info['clave'] === 'procesando') { return lang('anu_espere_respuesta'); }
        if ($info['clave'] === 'anulado')    { return lang('nxd_ya_anulado'); }
        return '';
    }

    private function _devolverExistencias($venta)
    {
        foreach ((array) $this->pos_model->getAllSaleItems($venta->id) as $linea) {
            if (!$linea->product_id) { continue; }
            $this->db->set('quantity', 'quantity + ' . (float) $linea->quantity, false)
                ->where('product_id', (int) $linea->product_id)
                ->where('store_id', (int) $venta->store_id)
                ->update($this->db->dbprefix('product_store_qty'));
        }
    }

    /** Hay al menos un administrador con PIN de cajon puesto en Ajustes. */
    private function _hayPinDeCajon()
    {
        return count($this->site->getAdminUsersWithDrawerPin()) > 0;
    }

    /** El PIN del cajon es el de cualquier administrador que tenga uno puesto. */
    private function _pinDelCajonValido($pin)
    {
        $pin = trim((string) $pin);
        if ($pin === '') { return false; }
        foreach ($this->site->getAdminUsersWithDrawerPin() as $admin) {
            if (password_verify($pin, $admin->drawer_pin)) {
                return true;
            }
        }
        return false;
    }

    private function _bytesAbrirCajon()
    {
        $this->load->library('escpos');
        $this->escpos->loadBuffer();
        $this->escpos->open_drawer();
        return $this->escpos->getBufferedData();
    }

    /**
     * Emite la nota de credito que anula la factura, por el 100%.
     *
     * Las lineas se copian tal como se facturaron y no se recalculan contra la
     * ficha del producto: el precio pudo cambiar desde entonces y la nota tiene
     * que cuadrar con el comprobante que anula.
     */
    private function _emitirNotaDeAnulacion($venta, $hac, $motivo)
    {
        $lineas = $this->_lineasDeLaVenta($venta->id);
        if (empty($lineas)) {
            return false;
        }

        $total = 0; $impuesto = 0; $descuento = 0;
        foreach ($lineas as $l) {
            $total     += (float) $l['net_unit_price'] * (float) $l['quantity'];
            $impuesto  += (float) $l['item_tax'];
            $descuento += (float) $l['item_discount'];
        }

        $data = array(
            'type_nc'          => '01',   // Anula documento de referencia (v4.4)
            'sale_id'          => (int) $venta->id,
            'date'             => date('Y-m-d H:i:s'),
            'customer_id'      => (int) $venta->customer_id,
            'customer_name'    => $venta->customer_name,
            'total'            => $this->tec->formatDecimal($total, 4),
            'product_discount' => $this->tec->formatDecimal($descuento, 4),
            'order_discount_id' => null,
            'order_discount'   => 0,
            'total_discount'   => $this->tec->formatDecimal($descuento, 4),
            'product_tax'      => $this->tec->formatDecimal($impuesto, 4),
            'order_tax_id'     => null,
            'order_tax'        => 0,
            'total_tax'        => $this->tec->formatDecimal($impuesto, 4),
            'grand_total'      => $this->tec->formatDecimal($total + $impuesto, 4),
            'total_items'      => count($lineas),
            'total_quantity'   => array_sum(array_column($lineas, 'quantity')),
            'rounding'         => 0,
            'paid'             => 0,
            'status'           => $venta->payment_status,
            'created_by'       => (int) $this->session->userdata('user_id'),
            'note'             => $motivo,
            'motivo'           => mb_substr($motivo, 0, 255),
            'hold_ref'         => mb_substr($motivo, 0, 180),
            'store_id'         => (int) $venta->store_id,
        );

        // codigo_impuesto y codigo_tarifa se derivan de id_tax y los necesita
        // Crearxml, pero no son columnas de note_credits_items: addNoteCredit
        // inserta el arreglo tal cual y el INSERT moria con ellas dentro.
        $paraGuardar = array_map(function ($linea) {
            unset($linea['codigo_impuesto'], $linea['codigo_tarifa']);
            return $linea;
        }, $lineas);

        // addNoteCredit guarda la nota, marca las lineas de la venta y devuelve
        // la existencia al inventario.
        $creada = $this->pos_model->addNoteCredit($data, $paraGuardar, '01', null);
        if (!$creada) {
            return false;
        }
        $id_cn = $creada['id_cn'];

        $this->load->library('Crearxml', NULL, 'Crearxml');
        $nota = $this->Crearxml->getNotaCredito($data, $lineas, $hac, null);
        if (!$nota) {
            return false;
        }
        $nota['id_cn'] = $id_cn;

        $certificado = './files/certificados/' . $this->Settings->ambiente . '/'
            . $this->Settings->certificado_ced . '.p12';
        if (file_exists($certificado)) {
            $this->load->library('firmar', NULL, 'firmar');
            try {
                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $nota['xml']);
                if ($firmado) { $nota['xml_sign'] = $firmado; }
            } catch (\Throwable $e) {
                log_message('error', 'Salesdoc: no se pudo firmar la nota de credito ' . $id_cn . ': ' . $e->getMessage());
            }
        }

        $this->hacienda_model->insertxmlCN($nota);

        $estado = $this->_enviarNotaCredito($id_cn);

        return array(
            'id_cn'       => $id_cn,
            'consecutivo' => $nota['consecutivo'],
            'clave'       => $nota['clave'],
            'estado'      => $estado,
            'url'         => site_url('creditnotes/viewnc/' . $id_cn),
        );
    }

    /** Manda la nota a Hacienda en el acto y devuelve como quedo. */
    private function _enviarNotaCredito($id_cn)
    {
        $fila = $this->hacienda_model->getCN($id_cn);
        if (!$fila || trim((string) $fila->xml_sign) === '') {
            return estado_hacienda_info($fila ? $fila->estatus_hacienda : 'pendiente');
        }

        $this->load->library('Apiclient', NULL, 'ApiClient');
        $this->ApiClient->getTokenH();

        $respuesta = $this->ApiClient->send_invoice(array(
            'xml'           => $fila->xml,
            'xml_sign'      => $fila->xml_sign,
            'clave'         => $fila->clave,
            'consecutivo'   => $fila->consecutivo,
            'fecha_emision' => $fila->fecha_emision,
        ));
        $this->ApiClient->CloseTokenH();

        $mensaje = isset($respuesta['mensajeHacienda']->respuestaxml)
            ? base64_decode($respuesta['mensajeHacienda']->respuestaxml) : '';
        $estatus = isset($respuesta['mensajeHacienda']->indestado)
            ? $respuesta['mensajeHacienda']->indestado : 'Sin Estado';

        $this->hacienda_model->insertHaciendaCN(array(
            'xml_sign'         => isset($respuesta['xml_firmado']) ? base64_decode($respuesta['xml_firmado']) : $fila->xml_sign,
            'xml_hacienda'     => $mensaje,
            'estatus_hacienda' => $estatus,
        ), $fila->clave);

        return estado_hacienda_info($estatus);
    }

    /**
     * Lineas de la venta con las claves que espera addNoteCredit y Crearxml.
     *
     * @return array<int,array<string,mixed>>
     */
    private function _lineasDeLaVenta($sale_id)
    {
        $filas = $this->pos_model->getAllSaleItems($sale_id);
        if (!$filas) {
            return array();
        }

        $out = array();
        foreach ($filas as $r) {
            $out[] = array(
                'product_id'          => (int) $r->product_id,
                'product_code'        => (string) $r->product_code,
                'product_name'        => (string) $r->product_name,
                'quantity'            => (float) $r->quantity,
                'unit_price'          => (float) $r->unit_price,
                'net_unit_price'      => (float) $r->net_unit_price,
                'real_unit_price'     => (float) $r->real_unit_price,
                'price'               => (float) $r->subtotal,
                'subtotal'            => (float) $r->subtotal,
                'discount'            => (string) $r->discount,
                'item_discount'       => (float) $r->item_discount,
                'tax'                 => (string) $r->tax,
                'item_tax'            => (float) $r->item_tax,
                'id_tax'              => (int) $r->id_tax,
                'cost'                => (float) $r->cost,
                'comment'             => (string) $r->comment,
                'unit_of_measurement' => (string) $r->unit_of_measurement,
                'cabys'               => $this->_cabysDeLinea($r),
                'codigo_impuesto'     => isset($r->codigo_impuesto) ? $r->codigo_impuesto : '01',
                'codigo_tarifa'       => isset($r->codigo_tarifa) ? $r->codigo_tarifa : '01',
            );
        }
        return $out;
    }

    /* ═══════════════════════ ARMADO DE BLOQUES ═══════════════════════ */

    private function _bloqueVenta($v)
    {
        $granTotal = (float) $v->grand_total + (float) $v->rounding;

        return array(
            'id'            => (int) $v->id,
            'fecha'         => $v->date,
            'fecha_legible' => $this->tec->hrld($v->date),
            'estado'        => $v->payment_status,
            'estado_etiqueta' => lang($v->payment_status),
            'nota'          => $v->note ? nota_segura($v->note) : '',
            'referencia'    => (string) $v->hold_ref,
            'items'         => (float) $v->total_items,
            'unidades'      => (float) $v->total_quantity,
            'subtotal'      => (float) $v->total,
            'impuesto'      => (float) $v->total_tax,
            'descuento'     => (float) $v->total_discount,
            'exoneracion'   => (float) $v->MontoExoneracion,
            'redondeo'      => (float) $v->rounding,
            'gran_total'    => $granTotal,
            'pagado'        => (float) $v->paid,
            'saldo'         => max(0, $granTotal - (float) $v->paid),
            'moneda'        => (string) $this->Settings->symbol,
        );
    }

    private function _bloqueCliente($v)
    {
        $c = $this->pos_model->getCustomerByID($v->customer_id);
        $tipos = array(
            '01' => lang('Cedula Identidad'), '02' => lang('Cedula Juridica'),
            '03' => lang('Dimex'),            '04' => lang('NITE'),
            '05' => lang('extranjero_no_domiciliado'),
        );

        return array(
            'nombre'    => $c ? $c->name : $v->customer_name,
            'ident'     => $c ? (string) $c->cf2 : '',
            'ident_tipo' => $c && isset($tipos[$c->cf1]) ? $tipos[$c->cf1] : '',
            'email'     => $c ? (string) $c->email : '',
            'telefono'  => $c ? (string) $c->phone : '',
            'cod_pais'  => $c && !empty($c->cod_telefono) ? (string) $c->cod_telefono : '506',
        );
    }

    private function _bloqueEmisor($v)
    {
        $tienda = $this->site->getStoreByID($v->store_id);
        $cajero = $this->site->getUser($v->created_by);

        return array(
            'nombre'    => (string) ($this->Settings->nombre_comercial ?: $this->Settings->nombre_emisor),
            'razon'     => (string) $this->Settings->nombre_emisor,
            'cedula'    => (string) $this->Settings->cedula_emisor,
            'actividad' => (string) $v->id_actividad,
            'tienda'    => $tienda ? $tienda->name : '',
            'cajero'    => $cajero ? trim($cajero->first_name . ' ' . $cajero->last_name) : '',
            'ambiente'  => (string) $this->Settings->ambiente,
        );
    }

    private function _bloqueLineas($sale_id)
    {
        $filas = $this->pos_model->getAllSaleItems($sale_id);
        if (!$filas) {
            return array();
        }

        $out = array();
        foreach ($filas as $r) {
            // `tax` viaja como '13%' o como decimal segun por donde entro la linea.
            $tasa = (float) str_replace('%', '', (string) $r->tax);
            $cant = (float) $r->quantity;
            $base = (float) $r->unit_price * $cant;

            $out[] = array(
                'codigo'    => (string) $r->product_code,
                'nombre'    => (string) $r->product_name,
                'comentario' => isset($r->comment) ? (string) $r->comment : '',
                'cabys'     => $this->_cabysDeLinea($r),
                'cantidad'  => $cant,
                'unidad'    => (string) $r->unit_of_measurement,
                'unitario'  => (float) $r->unit_price,
                'lista'     => (float) $r->real_unit_price,
                'descuento' => (float) $r->item_discount,
                'descuento_etiqueta' => (string) $r->discount,
                'tasa'      => $tasa,
                'impuesto'  => (float) $r->item_tax,
                'cod_impuesto' => isset($r->codigo_impuesto) ? (string) $r->codigo_impuesto : '',
                'cod_tarifa'   => isset($r->codigo_tarifa) ? (string) $r->codigo_tarifa : '',
                'base'      => $base,
                'total'     => (float) $r->subtotal,
            );
        }
        return $out;
    }

    /** El CABYS de la linea; si es un articulo rapido, el de la ficha. */
    private function _cabysDeLinea($r)
    {
        if (!empty($r->cabys)) {
            return (string) $r->cabys;
        }
        $p = $r->product_id ? $this->site->getProductByID($r->product_id) : null;
        return $p && !empty($p->cabys) ? (string) $p->cabys : '';
    }

    private function _bloquePagos($sale_id)
    {
        $filas = $this->pos_model->getAllSalePayments($sale_id);
        if (!$filas) {
            return array();
        }

        $out = array();
        foreach ($filas as $p) {
            $medio = medio_pago_hacienda($p->paid_by);
            $out[] = array(
                'metodo'     => (string) $p->paid_by,
                'codigo'     => $medio['codigo'],
                'etiqueta'   => $medio['etiqueta'],
                'monto'      => (float) ($p->pos_paid !== null && (float) $p->pos_paid > 0 ? $p->pos_paid : $p->amount),
                'vuelto'     => (float) $p->pos_balance > 0 ? (float) $p->pos_balance : 0,
                'referencia' => $this->_referenciaPago($p),
                'nota'       => (string) $p->note,
                'fecha'      => $p->date,
            );
        }
        return $out;
    }

    /** Referencia visible del cobro segun el medio con que entro. */
    private function _referenciaPago($p)
    {
        if (!empty($p->reference))  { return (string) $p->reference; }
        if (!empty($p->cheque_no))  { return lang('cheque_no') . ': ' . $p->cheque_no; }
        if (!empty($p->gc_no))      { return lang('gift_card') . ': ' . $p->gc_no; }
        if (!empty($p->cc_no))      { return 'xxxx xxxx xxxx ' . substr($p->cc_no, -4); }
        if (!empty($p->transaction_id)) { return (string) $p->transaction_id; }
        return '';
    }

    private function _bloqueHacienda($h)
    {
        if (!$h) {
            return array(
                'existe' => false,
                'estado' => estado_hacienda_info(null),
            );
        }

        $tipos = tipos_comprobante();
        $tipo  = str_pad((string) $h->tipo_doc, 2, '0', STR_PAD_LEFT);
        $resp  = resumen_mensaje_hacienda($h->xml_hacienda);

        return array(
            'existe'         => true,
            'tipo_doc'       => $tipo,
            'tipo_etiqueta'  => isset($tipos[$tipo]) ? $tipos[$tipo] : $tipo,
            'consecutivo'    => (string) $h->consecutivo,
            'clave'          => (string) $h->clave,
            'clave_partes'   => leer_clave_hacienda($h->clave) ?: null,
            'fecha_emision'  => (string) $h->fecha_emision,
            'estado'         => estado_hacienda_info($h->estatus_hacienda),
            'estado_crudo'   => (string) $h->estatus_hacienda,
            'tiene_xml'      => trim((string) $h->xml_sign) !== '' || trim((string) $h->xml) !== '',
            'tiene_firma'    => trim((string) $h->xml_sign) !== '',
            'tiene_respuesta' => trim((string) $h->xml_hacienda) !== '',
            'respuesta'      => $resp,
            'correo_enviado' => (string) $h->mail === '1',
        );
    }

    /** Notas de credito, notas de debito y recibos de pago de esta venta. */
    private function _bloqueRelacionados($sale_id)
    {
        $out = array('nc' => array(), 'nd' => array(), 'rep' => array());

        $nc = $this->db->select('nc.id, nc.date, nc.grand_total, nc.motivo, h.consecutivo, h.estatus_hacienda')
            ->from($this->db->dbprefix('note_credits') . ' nc')
            ->join($this->db->dbprefix('hacienda_cn') . ' h', 'h.id_cn = nc.id', 'left')
            ->where('nc.sale_id', (int) $sale_id)
            ->order_by('nc.id', 'DESC')->get()->result();
        foreach ($nc as $r) {
            $out['nc'][] = array(
                'id' => (int) $r->id, 'fecha' => $r->date, 'total' => (float) $r->grand_total,
                'motivo' => (string) $r->motivo, 'consecutivo' => (string) $r->consecutivo,
                'estado' => estado_hacienda_info($r->estatus_hacienda),
                'url' => site_url('creditnotes/viewnc/' . $r->id),
            );
        }

        if ($this->db->table_exists('note_debits')) {
            $nd = $this->db->select('nd.id, nd.date, nd.grand_total, nd.hold_ref, h.consecutivo, h.estatus_hacienda')
                ->from($this->db->dbprefix('note_debits') . ' nd')
                ->join($this->db->dbprefix('hacienda_nd') . ' h', 'h.nd_id = nd.id', 'left')
                ->where('nd.sale_id', (int) $sale_id)
                ->order_by('nd.id', 'DESC')->get()->result();
            foreach ($nd as $r) {
                $out['nd'][] = array(
                    'id' => (int) $r->id, 'fecha' => $r->date, 'total' => (float) $r->grand_total,
                    'motivo' => (string) $r->hold_ref, 'consecutivo' => (string) $r->consecutivo,
                    'estado' => estado_hacienda_info($r->estatus_hacienda),
                    'url' => site_url('debitnotes/viewnd/' . $r->id),
                );
            }
        }

        if ($this->db->table_exists('hacienda_rep')) {
            $rep = $this->db->where('sale_id', (int) $sale_id)
                ->order_by('id', 'DESC')
                ->get($this->db->dbprefix('hacienda_rep'))->result();
            foreach ($rep as $r) {
                $out['rep'][] = array(
                    'id' => (int) $r->payment_id, 'fecha' => $r->fecha_emision, 'total' => 0,
                    'motivo' => '', 'consecutivo' => (string) $r->consecutivo,
                    'estado' => estado_hacienda_info($r->estatus_hacienda),
                    'url' => site_url('XmlHacienda/xmlFirmadoREP/' . $r->payment_id),
                );
            }
        }

        return $out;
    }

    /** Que tiene sentido ofrecer sobre este comprobante, y que no. */
    private function _bloqueAcciones($h, $anulada)
    {
        $info = estado_hacienda_info($h ? $h->estatus_hacienda : null);
        $fe   = ((string) $this->Settings->fe === '1');

        return array(
            'admin'        => (bool) $this->Admin,
            'fe_activa'    => $fe,
            // Una venta anulada no se vuelve a emitir: lo que corresponde es
            // una venta nueva, no revivir el comprobante que se dio de baja.
            'reenviar'     => $fe && $info['corregible'] && !$anulada,
            'reemite'      => $info['clave'] === 'rechazado',
            'consultar'    => $fe && $h && !empty($h->clave) && $info['enviado'],
            // Anular es una sola accion: el servidor decide si toca emitir nota
            // de credito o si basta con sacar de circulacion lo que Hacienda nunca acepto.
            'anular'       => (bool) $this->Admin && !$anulada && $this->_puedeAnularse($info),
            'anulada'      => (bool) $anulada,
            'devolucion'   => $fe && $info['clave'] === 'aceptado' && !$anulada,
            'nota_debito'  => $fe && $info['clave'] === 'aceptado' && !$anulada,
            // Correccion por comparacion: una sola pantalla decide si sale nota
            // de credito, de debito o anulacion segun como quede el documento.
            'corregir'     => $fe && (bool) $this->Admin && !$anulada
                              && ($info['clave'] === 'aceptado' || $info['clave'] === 'parcial'),
            'rehacer'      => true,
            'correo'       => true,
            'xml'          => $h && trim((string) $h->xml_sign) !== '',
            'acuse'        => $h && trim((string) $h->xml_hacienda) !== '',
        );
    }

    private function _bloqueEnlaces($sale_id)
    {
        return array(
            'pdf'         => site_url('pos/pdf/' . $sale_id),
            'original'    => site_url('pos/view/' . $sale_id),
            'tiquete'     => site_url('posprint/receipt_bytes/' . $sale_id . '/1'),
            'xml'         => site_url('sales/descargar_xml/' . $sale_id . '/firmado'),
            'acuse'       => site_url('sales/descargar_xml/' . $sale_id . '/respuesta'),
            'correo'      => site_url('pos/email_receipt'),
            'devolucion'  => site_url('pos/?code=' . base64_encode($sale_id . ' 06')),
            'nota_debito' => site_url('debitnotes/add/' . $sale_id),
            'corregir'    => site_url('ajuste/editar/' . $sale_id),
            'rehacer'     => site_url('pos/?redo=' . $sale_id),
            'pagos'       => site_url('sales/payments/' . $sale_id),
            'agregar_pago' => site_url('sales/add_payment/' . $sale_id),
        );
    }

    /* ═══════════════════════ EMISION Y ENVIO ═══════════════════════ */

    /**
     * Genera el XML del comprobante y lo deja guardado.
     *
     * @param bool $reemitir true cuando ya habia una fila y hay que pisarla con
     *                       una clave nueva (el rechazo no se puede reenviar)
     * @return object|false fila de hacienda_tiketes ya actualizada
     */
    private function _emitirComprobante($v, $anterior, $reemitir)
    {
        $items  = $this->pos_model->getAllSaleItems($v->id);
        $pagos  = $this->pos_model->getAllSalePayments($v->id);
        $textos = $this->db->where('sale_id', (int) $v->id)
            ->get($this->db->dbprefix('sales_otros_textos'))->result_array();

        $this->load->library('Crearxml', NULL, 'Crearxml');
        $comprobante = $this->Crearxml->getInvoice(
            (array) $v,
            json_decode(json_encode($items), true),
            json_decode(json_encode($pagos), true),
            $textos
        );

        if (!$comprobante) {
            return false;
        }

        $fila = array(
            'sale_id'          => (int) $v->id,
            'tipo_doc'         => $comprobante['tipo_doc'],
            'consecutivo'      => $comprobante['consecutivo'],
            'clave'            => $comprobante['clave'],
            'fecha_emision'    => $comprobante['fecha_emision'],
            'xml'              => $comprobante['xml'],
            'xml_sign'         => null,
            'xml_hacienda'     => null,
            'estatus_hacienda' => 'pendiente',
        );

        if ($anterior) {
            // El comprobante viejo no vuelve a existir: queda en la bitacora
            // para que el consecutivo rechazado siga siendo rastreable.
            $this->_auditar('sale_reemitida', $v, 'Reemplaza el consecutivo '
                . $anterior->consecutivo . ' (' . $anterior->estatus_hacienda . ')');
            $this->db->update($this->db->dbprefix('hacienda_tiketes'), $fila, array('sale_id' => (int) $v->id));
        } else {
            $this->db->insert($this->db->dbprefix('hacienda_tiketes'), $fila);
        }

        // El consecutivo tambien vive en la venta: el POS y los reportes lo leen de ahi.
        $this->db->update($this->db->dbprefix('sales'), array(
            'consecutivo' => $comprobante['consecutivo'],
            'clave'       => $comprobante['clave'],
        ), array('id' => (int) $v->id));

        return $this->hacienda_model->getInvoice($v->id);
    }

    private function _firmarSiFalta($h)
    {
        if (trim((string) $h->xml_sign) !== '') {
            return $h;
        }

        $certificado = './files/certificados/' . $this->Settings->ambiente . '/'
            . $this->Settings->certificado_ced . '.p12';
        if (!file_exists($certificado)) {
            log_message('error', 'Salesdoc: no existe el certificado ' . $certificado);
            return $h;
        }

        $this->load->library('firmar', NULL, 'firmar');
        $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $h->xml);
        if ($firmado) {
            $this->hacienda_model->insertHacienda(array('xml_sign' => $firmado), $h->clave);
            $h->xml_sign = $firmado;
        }
        return $h;
    }

    private function _enviarAHacienda($h)
    {
        $this->load->library('Apiclient', NULL, 'ApiClient');
        $this->ApiClient->getTokenH();

        $respuesta = $this->ApiClient->send_invoice(array(
            'xml'           => $h->xml,
            'xml_sign'      => $h->xml_sign,
            'clave'         => $h->clave,
            'consecutivo'   => $h->consecutivo,
            'fecha_emision' => $h->fecha_emision,
        ));

        $this->ApiClient->CloseTokenH();

        $mensaje = '';
        $estatus = 'Sin Estado';
        if (isset($respuesta['mensajeHacienda']->respuestaxml)) {
            $mensaje = base64_decode($respuesta['mensajeHacienda']->respuestaxml);
        }
        if (isset($respuesta['mensajeHacienda']->indestado)) {
            $estatus = $respuesta['mensajeHacienda']->indestado;
        }

        $this->hacienda_model->insertHacienda(array(
            'xml_sign'         => isset($respuesta['xml_firmado']) ? base64_decode($respuesta['xml_firmado']) : $h->xml_sign,
            'xml_hacienda'     => $mensaje,
            'estatus_hacienda' => $estatus,
        ), $h->clave);

        return estado_hacienda_info($estatus);
    }

    /* ═══════════════════════════ APOYO ═══════════════════════════ */

    /**
     * Venta accesible para quien pide, o corte con el error correspondiente.
     *
     * No se usa Tec::view_rights() porque redirige, y una peticion AJAX que
     * recibe HTML de otra pagina no puede explicar que paso.
     */
    private function _venta($id)
    {
        $venta = $id ? $this->pos_model->getSaleByID((int) $id) : false;

        if (!$venta || $venta->store_id != $this->session->userdata('store_id')) {
            $this->_cortar(array('error' => lang('nxd_venta_no_encontrada')), 404);
        }
        if (!$this->Admin && !$this->session->userdata('view_right')
            && $venta->created_by != $this->session->userdata('user_id')) {
            $this->_cortar(array('error' => lang('nxd_acceso_denegado')), 403);
        }
        return $venta;
    }

    /** @param string $cual 'firmado' | 'respuesta' */
    private function _xmlDe($h, $cual)
    {
        if (!$h) {
            return '';
        }
        if ($cual === 'respuesta') {
            return trim((string) $h->xml_hacienda);
        }
        // Sin firma todavia se muestra el XML tal como se genero: es lo que hay.
        $firmado = trim((string) $h->xml_sign);
        return $firmado !== '' ? $firmado : trim((string) $h->xml);
    }

    /** Sangra el XML para leerlo; si viene mal formado se devuelve tal cual. */
    private function _sangrarXml($xml)
    {
        $anterior = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $ok = $doc->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        return $ok ? $doc->saveXML() : $xml;
    }

    private function _auditar($accion, $v, $detalle)
    {
        $this->load->model('AuditLog_model', 'audit_log');
        $this->audit_log->log($accion, 'sale', (int) $v->id, $detalle, (float) $v->grand_total);
    }

    /**
     * Convierte en JSON cualquier corte inesperado de Apiclient.
     *
     * getTokenH() y send_invoice() informan sus fallos con echo + exit. Sin
     * esto el navegador recibiria ese texto suelto con estado 200 y el modal
     * diria que todo salio bien.
     */
    private function _blindar_salida()
    {
        $this->blindado = true;
        ob_start();
        register_shutdown_function(function () {
            if ($this->respondido) {
                return;
            }
            $suelto = trim((string) ob_get_clean());
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8', true, 502);
            }
            echo json_encode(array(
                'error' => $suelto !== '' ? $suelto : lang('nxd_reenvio_error'),
            ), JSON_UNESCAPED_UNICODE);
        });
    }

    private function _json($data, $status = 200)
    {
        $this->respondido = true;
        // Lo que Apiclient haya alcanzado a imprimir no puede ir delante del JSON.
        if ($this->blindado) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $this->blindado = false;
        }
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Responde y corta la peticion.
     *
     * `return` desde el constructor de un controlador CI3 no detiene nada: el
     * metodo pedido se ejecuta igual. Y salir sin `_display()` deja el cuerpo
     * de la respuesta vacio, porque CI no llega a volcar su buffer de salida.
     */
    private function _cortar($data, $status)
    {
        $this->_json($data, $status);
        $this->output->_display();
        exit;
    }
}

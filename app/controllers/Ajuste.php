<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Correccion de una factura emitida.
 *
 * La pantalla del POS carga la factura, el cajero edita las lineas y este
 * controlador compara el resultado contra el original para decidir que
 * documento corresponde. El navegador nunca decide: manda las lineas y recibe
 * el veredicto ya calculado.
 *
 * La factura original no se toca nunca. Lo que se emite es una nota que la
 * referencia (Reglamento de comprobantes electronicos: un comprobante aceptado
 * no se modifica ni se elimina).
 */
class Ajuste extends MY_Controller
{
    private $respondido = false;
    private $blindado   = false;

    public function __construct()
    {
        parent::__construct();
        if (!$this->loggedIn) {
            // La pantalla manda al login; los endpoints responden JSON, porque
            // el navegador espera JSON y una redireccion le llega como HTML.
            if ($this->router->fetch_method() === 'editar') {
                redirect('login');
            }
            $this->_cortar(array('error' => lang('access_denied')), 401);
        }
        $this->load->model('pos_model');
        $this->load->model('hacienda_model');
        $this->load->helper('pos');
        $this->load->helper('ajuste');
    }

    /* ═══════════════════════════ PANTALLA ═══════════════════════════ */

    /**
     * GET ajuste/editar/<id> — la pantalla de correccion.
     *
     * Solo arma el marco; las lineas y el veredicto los pide el JavaScript a
     * documento/ y previsualizar/, que son los que mandan.
     */
    public function editar($id = null)
    {
        $venta = $id ? $this->pos_model->getSaleByID((int) $id) : null;
        if (!$venta) {
            show_404();
        }

        $this->data['venta']   = $venta;
        $this->data['permiso'] = $this->_permiso($venta, $this->hacienda_model->getInvoice($venta->id));
        $this->data['page_title'] = lang('ajuste_titulo');

        $bc = array(
            array('link' => site_url('sales'), 'page' => lang('sales')),
            array('link' => '#', 'page' => lang('ajuste_titulo') . ' #' . $venta->id),
        );
        $this->page_construct('ajuste/editar', $this->data, array(
            'page_title' => lang('ajuste_titulo'), 'bc' => $bc,
        ));
    }

    /* ═══════════════════════════ LECTURA ═══════════════════════════ */

    /**
     * GET ajuste/documento/<id> — la factura original y su margen de correccion.
     *
     * Es lo que la pantalla necesita para arrancar: las lineas tal como se
     * facturaron, lo que ya se corrigio y si se puede corregir mas.
     */
    public function documento($id = null)
    {
        $venta = $this->_venta($id);
        $hac   = $this->hacienda_model->getInvoice($venta->id);
        $notas = $this->_notasDe($venta->id);

        $this->_json(array(
            'venta' => array(
                'id'          => (int) $venta->id,
                'fecha'       => $venta->date,
                'cliente'     => $venta->customer_name,
                'customer_id' => (int) $venta->customer_id,
                'grand_total' => (float) $venta->grand_total,
                'consecutivo' => $hac ? (string) $hac->consecutivo : '',
                'clave'       => $hac ? (string) $hac->clave : '',
                'tipo_doc'    => $hac ? str_pad((string) $hac->tipo_doc, 2, '0', STR_PAD_LEFT) : '',
            ),
            'estado'   => estado_hacienda_info($hac ? $hac->estatus_hacienda : null),
            'lineas'   => $this->_lineasOriginales($venta->id),
            'saldo'    => ajuste_saldo($venta->grand_total, $notas),
            'notas'    => $notas,
            'permiso'  => $this->_permiso($venta, $hac),
            'codigos'  => codigos_referencia_pos(),
        ));
    }

    /**
     * POST ajuste/previsualizar/<id> — que documento saldria con estas lineas.
     *
     * No escribe nada. Es lo que alimenta el indicador en vivo de la pantalla.
     */
    public function previsualizar($id = null)
    {
        $venta = $this->_venta($id);
        $this->_json($this->_comparar($venta));
    }

    /* ═══════════════════════════ EMISION ═══════════════════════════ */

    /**
     * POST ajuste/emitir/<id> — genera, firma y manda la nota que corresponda.
     *
     * Vuelve a comparar de cero contra la base: el veredicto que el navegador
     * mostro es informativo, y entre la previsualizacion y el envio pudo entrar
     * otra nota sobre la misma factura.
     */
    public function emitir($id = null)
    {
        $this->_blindar_salida();

        $venta = $this->_venta($id);
        $hac   = $this->hacienda_model->getInvoice($venta->id);

        $permiso = $this->_permiso($venta, $hac);
        if (!$permiso['puede']) {
            $this->_json(array('error' => lang($permiso['motivo'])), 409);
            return;
        }

        $motivo = trim((string) $this->input->post('motivo'));
        if (mb_strlen($motivo) < 5) {
            $this->_json(array('error' => lang('ajuste_motivo_requerido')), 422);
            return;
        }

        // La factura se bloquea mientras se decide y se emite: dos cajeros
        // corrigiendo la misma factura a la vez acreditarian dos veces.
        $this->db->trans_begin();
        $this->db->query(
            'SELECT id FROM `' . $this->db->dbprefix('sales') . '` WHERE id = ? FOR UPDATE',
            array((int) $venta->id)
        );

        $v = $this->_comparar($venta);

        if ($v['bloqueo']) {
            $this->db->trans_rollback();
            $this->_json(array('error' => lang($v['bloqueo']['mensaje']), 'veredicto' => $v), 409);
            return;
        }

        $codigo = $v['codigo_referencia'];
        if ($v['tipo'] !== 'ANULACION') {
            // El cajero puede afinar el codigo dentro de lo que el motor permite,
            // pero no convertir una anulacion en otra cosa.
            $codigo = codigo_referencia_valido($this->input->post('codigo_referencia'), $codigo);
        }

        $this->load->library('Nota_emisor', NULL, 'nota_emisor');

        try {
            $nota = $v['tipo'] === 'NOTA_DEBITO'
                ? $this->nota_emisor->debito($venta, $hac, $v['lineas_nota'], $codigo, $motivo)
                : $this->nota_emisor->credito($venta, $hac, $v['lineas_nota'], $codigo, $motivo);
        } catch (\Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'Ajuste: fallo la emision sobre la venta ' . $venta->id . ': ' . $e->getMessage());
            $this->_json(array('error' => lang('ajuste_fallo_emision')), 500);
            return;
        }

        if (!$nota) {
            $this->db->trans_rollback();
            $this->_json(array('error' => lang('ajuste_fallo_emision')), 500);
            return;
        }

        $this->_registrar($venta, $v, $nota, $codigo, $motivo);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->_json(array('error' => lang('ajuste_fallo_emision')), 500);
            return;
        }
        $this->db->trans_commit();

        $this->_auditar('ajuste_' . strtolower($v['tipo']), $venta,
            $nota['documento'] . ' ' . $nota['consecutivo'] . ' · ' . $motivo);

        $this->_json(array(
            'ok'        => true,
            'tipo'      => $v['tipo'],
            'nota'      => $nota,
            'veredicto' => $v,
        ));
    }

    /** GET ajuste/historial/<id> — las correcciones que ya tiene la factura. */
    public function historial($id = null)
    {
        $venta = $this->_venta($id);

        $filas = array();
        if ($this->db->table_exists('sale_ajustes')) {
            $filas = $this->db->where('sale_id', (int) $venta->id)
                ->order_by('id', 'DESC')
                ->get($this->db->dbprefix('sale_ajustes'))->result();
        }

        $out = array();
        foreach ($filas as $f) {
            $quien = $this->site->getUser($f->created_by);
            $out[] = array(
                'id'         => (int) $f->id,
                'tipo'       => $f->tipo,
                'documento'  => $f->cn_id ? 'NC' : 'ND',
                'codigo'     => $f->codigo_referencia,
                'motivo'     => $f->motivo,
                'diferencia' => (float) $f->diferencia,
                'total_nota' => (float) $f->total_nota,
                'mixto'      => (bool) $f->mixto,
                'fecha'      => $f->created_at,
                'usuario'    => $quien ? trim($quien->first_name . ' ' . $quien->last_name) : '',
                'url'        => $f->cn_id
                    ? site_url('creditnotes/viewnc/' . $f->cn_id)
                    : site_url('debitnotes/viewnd/' . $f->nd_id),
            );
        }

        $this->_json(array('movimientos' => $out, 'saldo' => ajuste_saldo(
            $venta->grand_total, $this->_notasDe($venta->id)
        )));
    }

    /* ═══════════════════════ NUCLEO DE LA DECISION ═══════════════════════ */

    /**
     * Compara la factura guardada contra las lineas que llegaron del POS.
     *
     * El original sale siempre de la base, nunca del formulario, y de cada
     * linea editada solo se aceptan cantidad, precio y descuento: lo demas
     * —CABYS, tarifa, unidad— se toma de la venta o de la ficha del producto,
     * porque son los datos con que se declaro el comprobante.
     */
    private function _comparar($venta)
    {
        $original = $this->_lineasOriginales($venta->id);
        $editadas = $this->_lineasDelPost($original);

        return ajuste_comparar($original, $editadas, array(
            'total_original' => (float) $venta->grand_total,
            'notas_previas'  => $this->_notasDe($venta->id),
        ));
    }

    /** Las lineas de la venta tal como se facturaron. */
    private function _lineasOriginales($sale_id)
    {
        $out = array();
        foreach ($this->pos_model->getAllSaleItems($sale_id) as $r) {
            $out[] = array(
                'linea_origen_id'     => (int) $r->id,
                'product_id'          => (int) $r->product_id,
                'product_code'        => (string) $r->product_code,
                'product_name'        => (string) $r->product_name,
                'quantity'            => (float) $r->quantity,
                'unit_price'          => (float) $r->unit_price,
                'net_unit_price'      => (float) $r->net_unit_price,
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

    /**
     * Las lineas editadas, reconstruidas desde el servidor.
     *
     * @param array $original lineas de la venta, indexadas por su id de fila
     */
    private function _lineasDelPost(array $original)
    {
        $porId = array();
        foreach ($original as $l) {
            $porId[(int) $l['linea_origen_id']] = $l;
        }

        $crudas = $this->input->post('lineas');
        if (!is_array($crudas)) {
            $crudas = json_decode((string) $this->input->post('lineas_json'), true);
        }
        if (!is_array($crudas)) {
            return array();
        }

        $out = array();
        foreach ($crudas as $c) {
            if (!is_array($c)) {
                continue;
            }
            $cantidad = (float) (isset($c['quantity']) ? $c['quantity'] : 0);
            if ($cantidad <= 0) {
                continue;   // una linea en cero es una linea eliminada
            }

            $origen = (int) (isset($c['linea_origen_id']) ? $c['linea_origen_id'] : 0);
            $base   = isset($porId[$origen]) ? $porId[$origen] : $this->_lineaDeProducto($c);
            if (!$base) {
                continue;
            }

            $base['quantity'] = $cantidad;
            // El precio solo se acepta si viene; si no, manda el facturado.
            if (isset($c['unit_price']) && $c['unit_price'] !== '') {
                $base['unit_price'] = (float) $c['unit_price'];
            }
            $descuento = isset($c['item_discount']) ? (float) $c['item_discount'] : null;
            $base['net_unit_price'] = $descuento !== null
                ? max(0, $base['unit_price'] - ($cantidad > 0 ? $descuento / $cantidad : 0))
                : (isset($porId[$origen]) && !isset($c['unit_price'])
                    ? $base['net_unit_price']
                    : $base['unit_price']);
            $base['item_discount'] = round($cantidad * max(0, $base['unit_price'] - $base['net_unit_price']), 4);

            $out[] = $base;
        }
        return $out;
    }

    /**
     * Una linea nueva: los datos fiscales salen de la ficha, no del formulario.
     *
     * products.tax es el id del impuesto, no el porcentaje: la tarifa, el
     * codigo y el codigo de tarifa viven en tec_impuestos y son los tres que
     * necesita el XML.
     */
    private function _lineaDeProducto($c)
    {
        $id = (int) (isset($c['product_id']) ? $c['product_id'] : 0);
        $p  = $id ? $this->site->getProductByID($id) : null;
        if (!$p) {
            return null;
        }

        $imp = $this->_impuestoDe($p);
        // getProductByID ya invierte el precio cuando el impuesto va incluido.
        $precio = (isset($p->store_price) && (float) $p->store_price > 0)
            ? (float) $p->store_price : (float) $p->price;

        return array(
            'linea_origen_id'     => 0,
            'product_id'          => (int) $p->id,
            'product_code'        => (string) $p->code,
            'product_name'        => (string) $p->name,
            'quantity'            => 0,
            'unit_price'          => $precio,
            'net_unit_price'      => $precio,
            'item_discount'       => 0,
            'tax'                 => $imp['tasa'] > 0 ? $imp['tasa'] . '%' : '',
            'item_tax'            => 0,
            'id_tax'              => (int) (isset($p->id_tax) ? $p->id_tax : 0),
            'cost'                => (float) (isset($p->cost) ? $p->cost : 0),
            'comment'             => '',
            'unit_of_measurement' => (string) (isset($p->unit_of_measurement) ? $p->unit_of_measurement : 'Unid'),
            'cabys'               => (string) (isset($p->cabys) ? $p->cabys : ''),
            'codigo_impuesto'     => $imp['codigo'],
            'codigo_tarifa'       => $imp['tarifa'],
        );
    }

    /** Tarifa y codigos del impuesto que tiene asignado la ficha. */
    private function _impuestoDe($p)
    {
        $defecto = array('tasa' => 0.0, 'codigo' => '01', 'tarifa' => '01');
        if (empty($p->id_tax)) {
            return $defecto;
        }

        $q = $this->db->where('id_impuesto', (int) $p->id_tax)->limit(1)
            ->get($this->db->dbprefix('impuestos'));
        if (!$q->num_rows()) {
            return $defecto;
        }

        $i = $q->row();
        return array(
            'tasa'   => (float) $i->tasa_impuesto,
            'codigo' => (string) $i->codigo_impuesto,
            'tarifa' => (string) $i->codigo_tarifa,
        );
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

    /* ═══════════════════════ ESTADO Y TRAZABILIDAD ═══════════════════════ */

    /**
     * Notas que ya afectaron esta factura, en la forma que consume el motor.
     *
     * Se leen del estado real que devolvio Hacienda: una nota rechazada no
     * corrigio nada y su monto sigue disponible.
     */
    private function _notasDe($sale_id)
    {
        $out = array();

        $nc = $this->db->select('nc.grand_total, h.estatus_hacienda')
            ->from($this->db->dbprefix('note_credits') . ' nc')
            ->join($this->db->dbprefix('hacienda_cn') . ' h', 'h.id_cn = nc.id', 'left')
            ->where('nc.sale_id', (int) $sale_id)->get()->result();
        foreach ($nc as $r) {
            $out[] = array('tipo' => 'NC', 'total' => (float) $r->grand_total,
                'estado' => estado_hacienda_info($r->estatus_hacienda)['clave']);
        }

        if ($this->db->table_exists('note_debits')) {
            $nd = $this->db->select('nd.grand_total, h.estatus_hacienda')
                ->from($this->db->dbprefix('note_debits') . ' nd')
                ->join($this->db->dbprefix('hacienda_nd') . ' h', 'h.nd_id = nd.id', 'left')
                ->where('nd.sale_id', (int) $sale_id)->get()->result();
            foreach ($nd as $r) {
                $out[] = array('tipo' => 'ND', 'total' => (float) $r->grand_total,
                    'estado' => estado_hacienda_info($r->estatus_hacienda)['clave']);
            }
        }

        return $out;
    }

    /**
     * Si esta factura admite una nota, y por que no.
     *
     * Un comprobante rechazado o sin enviar nunca tuvo validez: lo que
     * corresponde es reemitirlo, no acreditarlo. Solo lo aceptado se corrige
     * con una nota.
     */
    private function _permiso($venta, $hac)
    {
        $info = estado_hacienda_info($hac ? $hac->estatus_hacienda : null);

        if ((string) $this->Settings->fe !== '1') {
            return array('puede' => false, 'motivo' => 'ajuste_no_fe', 'estado' => $info);
        }
        if (!$this->Admin) {
            return array('puede' => false, 'motivo' => 'access_denied', 'estado' => $info);
        }
        if ($this->db->table_exists('sale_anulaciones')
            && $this->db->where('sale_id', (int) $venta->id)->count_all_results($this->db->dbprefix('sale_anulaciones'))) {
            return array('puede' => false, 'motivo' => 'ajuste_ya_anulada', 'estado' => $info);
        }
        if ($info['clave'] !== 'aceptado' && $info['clave'] !== 'parcial') {
            return array('puede' => false, 'motivo' => 'ajuste_no_aceptada', 'estado' => $info);
        }

        return array('puede' => true, 'motivo' => '', 'estado' => $info);
    }

    /** Deja la correccion en el historial de la factura. */
    private function _registrar($venta, array $v, array $nota, $codigo, $motivo)
    {
        if (!$this->db->table_exists('sale_ajustes')) {
            return;
        }

        $this->db->insert($this->db->dbprefix('sale_ajustes'), array(
            'sale_id'           => (int) $venta->id,
            'tipo'              => $v['tipo'],
            'tipo_doc'          => $nota['documento'] === 'ND' ? '02' : '03',
            'cn_id'             => $nota['documento'] === 'NC' ? (int) $nota['id'] : null,
            'nd_id'             => $nota['documento'] === 'ND' ? (int) $nota['id'] : null,
            'codigo_referencia' => $codigo,
            'motivo'            => mb_substr($motivo, 0, 255),
            'total_original'    => $v['original']['total'],
            'total_nuevo'       => $v['nuevo']['total'],
            'diferencia'        => $v['delta']['total'],
            'total_nota'        => $v['total_nota'],
            'mixto'             => $v['mixto'] ? 1 : 0,
            'cambios'           => json_encode($v['cambios'], JSON_UNESCAPED_UNICODE),
            'created_by'        => (int) $this->session->userdata('user_id'),
            'created_at'        => date('Y-m-d H:i:s'),
            'ip'                => $this->input->ip_address(),
        ));
    }

    /* ═══════════════════════ APOYO ═══════════════════════ */

    private function _venta($id)
    {
        $venta = $id ? $this->pos_model->getSaleByID((int) $id) : null;
        if (!$venta) {
            $this->_cortar(array('error' => lang('sale_not_found')), 404);
        }
        return $venta;
    }

    private function _auditar($accion, $v, $detalle)
    {
        $this->load->model('AuditLog_model', 'audit_log');
        $this->audit_log->log($accion, 'sale', (int) $v->id, $detalle, (float) $v->grand_total);
    }

    /**
     * Convierte en JSON cualquier corte inesperado de Apiclient.
     *
     * getTokenH() y send_invoice() informan sus fallos con echo + exit, y sin
     * esto el navegador recibiria ese texto suelto con estado 200.
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
                'error' => $suelto !== '' ? $suelto : lang('ajuste_fallo_emision'),
            ), JSON_UNESCAPED_UNICODE);
        });
    }

    private function _json($data, $status = 200)
    {
        $this->respondido = true;
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

    /** `return` desde el constructor de un controlador CI3 no detiene la peticion. */
    private function _cortar($data, $status)
    {
        $this->_json($data, $status);
        $this->output->_display();
        exit;
    }
}

<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Apoyo del POS para no emitir lo que Hacienda va a rechazar: articulos rapidos
 * con CABYS verificado y correccion del CABYS de un producto al agregarlo.
 */
class Ventarapida extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->loggedIn) {
            $this->_json(array('error' => lang('access_denied')), 401);
        }
        $this->load->library('cabys');
    }

    /** GET ventarapida/lista */
    public function lista()
    {
        $filas = $this->db->order_by('nombre', 'ASC')->get_where('articulos_rapidos', array('activo' => 1))->result();
        $this->_json(array('articulos' => array_map(function ($a) {
            return array(
                'id' => (int) $a->id, 'nombre' => $a->nombre, 'cabys' => $a->cabys,
                'cabys_desc' => $a->cabys_desc, 'impuesto' => (float) $a->impuesto,
                'id_tax' => (int) $a->id_tax, 'precio' => $a->precio !== null ? (float) $a->precio : null,
            );
        }, $filas)));
    }

    /** POST ventarapida/guardar — solo Admin. */
    public function guardar()
    {
        $this->_solo_admin();
        $nombre = trim((string) $this->input->post('nombre'));
        if ($nombre === '' || mb_strlen($nombre) > 80) {
            $this->_json(array('error' => lang('producto_nombre_requerido')), 422);
        }
        $info = $this->_verificar((string) $this->input->post('cabys'));
        $impuesto = $this->cabys->impuestoDeTarifa($info['impuesto']);
        $precio = $this->input->post('precio');

        $this->db->insert('articulos_rapidos', array(
            'nombre'     => $nombre,
            'cabys'      => preg_replace('/\D/', '', (string) $this->input->post('cabys')),
            'cabys_desc' => mb_substr($info['descripcion'], 0, 255),
            'impuesto'   => $info['impuesto'],
            'id_tax'     => $impuesto ? (int) $impuesto->id_impuesto : null,
            'precio'     => $precio !== null && $precio !== '' && (float) $precio > 0 ? (float) $precio : null,
            'created_by' => (int) $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $this->_json(array('ok' => true, 'id' => (int) $this->db->insert_id()));
    }

    /** POST ventarapida/borrar/<id> — solo Admin. */
    public function borrar($id = null)
    {
        $this->_solo_admin();
        $this->db->update('articulos_rapidos', array('activo' => 0), array('id' => (int) $id));
        $this->_json(array('ok' => true));
    }

    /**
     * GET ventarapida/verificar_cabys?codigo=
     * `verificado` es false cuando Hacienda no respondio: no se puede afirmar nada.
     */
    public function verificar_cabys()
    {
        $info = $this->cabys->consultar((string) $this->input->get('codigo'));
        $this->_json($info === null
            ? array('verificado' => false)
            : array('verificado' => true) + $info);
    }

    /**
     * POST ventarapida/asignar_cabys
     * Corrige el CABYS de un producto desde el POS. Solo si el que tiene falta o
     * no existe en el catalogo: uno valido no se cambia desde la caja.
     */
    public function asignar_cabys()
    {
        $producto = $this->db->get_where('products', array('id' => (int) $this->input->post('product_id')), 1)->row();
        if (!$producto) {
            $this->_json(array('error' => lang('product_not_found')), 404);
        }

        if (preg_match('/^\d{13}$/', (string) $producto->cabys)) {
            $actual = $this->cabys->consultar($producto->cabys);
            if ($actual === null || $actual['existe']) {
                $this->_json(array('error' => lang('cabys_ya_valido')), 409);
            }
        }

        $codigo = preg_replace('/\D/', '', (string) $this->input->post('cabys'));
        $info = $this->_verificar($codigo);
        $impuesto = $this->cabys->impuestoDeTarifa($info['impuesto']);

        $cambios = array('cabys' => $codigo, 'tax' => (int) round($info['impuesto']));
        if ($impuesto) {
            $cambios['id_tax'] = (int) $impuesto->id_impuesto;
        }
        $this->db->update('products', $cambios, array('id' => $producto->id));

        $this->load->model('AuditLog_model', 'audit_log');
        $this->audit_log->log('producto_cabys_asignado', 'product', (int) $producto->id,
            'CABYS ' . ($producto->cabys ?: '(vacio)') . ' -> ' . $codigo . ' · IVA ' . $info['impuesto'] . '%');

        $this->_json(array('ok' => true, 'cabys' => $codigo, 'tax' => $cambios['tax'],
            'id_tax' => $cambios['id_tax'] ?? null, 'descripcion' => $info['descripcion']));
    }

    /** El CABYS tiene que existir en el catalogo; si Hacienda no responde, no se guarda. */
    private function _verificar($codigo)
    {
        $info = $this->cabys->consultar($codigo);
        if ($info === null) {
            $this->_json(array('error' => lang('cabys_no_verificable')), 503);
        }
        if (!$info['existe']) {
            $this->_json(array('error' => sprintf(lang('cabys_inexistente'), $codigo)), 422);
        }
        return $info;
    }

    private function _solo_admin()
    {
        if (!$this->Admin) {
            $this->_json(array('error' => lang('access_denied')), 403);
        }
    }

    private function _json($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE))
            ->_display();
        exit;
    }
}

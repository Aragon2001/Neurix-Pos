<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Puestos de trabajo del POS.
 *
 * QZ Tray corre en la computadora del cajero, asi que la impresora es una
 * propiedad de la maquina y no del usuario: quien entre en la caja 1 imprime en
 * la impresora de la caja 1. Cada equipo se identifica con un device_id que
 * genera su navegador; la IP se guarda ademas para poder reconocer un equipo
 * que perdio ese identificador (navegador reinstalado, datos borrados).
 */
class Workstation extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
        if (!$this->loggedIn) {
            $this->_json(['error' => lang('access_denied')], 401);
        }
    }

    /**
     * POST workstation/registrar  (device_id)
     * Deja constancia del equipo y devuelve la configuracion que le corresponde.
     */
    public function registrar()
    {
        $device = $this->_device_id();
        if ($device === '') {
            $this->_json(['error' => 'device_id invalido'], 400);
        }

        $ip    = $this->input->ip_address();
        $ahora = date('Y-m-d H:i:s');
        $fila  = $this->db->get_where('pos_workstations', ['device_id' => $device], 1)->row();

        if (!$fila) {
            // Equipo desconocido: si desde esta misma IP ya trabajaba un puesto,
            // hereda su impresora en vez de volver a preguntar.
            $vecino = $this->db->order_by('ultimo_uso', 'DESC')
                ->get_where('pos_workstations', ['ip' => $ip], 1)->row();

            $this->db->insert('pos_workstations', [
                'device_id'  => $device,
                'nombre'     => $this->_nombre_sugerido(),
                'ip'         => $ip,
                'qz_printer' => $vecino->qz_printer ?? null,
                'printer_id' => $vecino->printer_id ?? null,
                'store_id'   => $this->session->userdata('store_id') ?: null,
                'agente'     => substr((string) $this->input->user_agent(), 0, 255),
                'ultimo_uso' => $ahora,
                'creado'     => $ahora,
            ]);
            $fila = $this->db->get_where('pos_workstations', ['device_id' => $device], 1)->row();
        } else {
            $this->db->update('pos_workstations', [
                'ip'         => $ip,
                'agente'     => substr((string) $this->input->user_agent(), 0, 255),
                'ultimo_uso' => $ahora,
            ], ['device_id' => $device]);
            $fila->ip = $ip;
        }

        $this->_json([
            'id'         => (int) $fila->id,
            'nombre'     => $fila->nombre,
            'ip'         => $fila->ip,
            'qz_printer' => $fila->qz_printer,
            'caracteres' => isset($fila->caracteres) && $fila->caracteres ? (int) $fila->caracteres : get_printer_chars_per_line(),
            'impresoras' => json_decode((string) $fila->impresoras, true) ?: [],
            'printer_id' => $fila->printer_id !== null ? (int) $fila->printer_id : null,
        ]);
    }

    /**
     * POST workstation/impresora  (device_id, qz_printer)
     * Guarda la impresora elegida en el modal de configuracion del POS.
     */
    public function impresora()
    {
        $impresora = trim((string) $this->input->post('qz_printer'));
        $valores   = ['qz_printer' => $impresora !== '' ? $impresora : null];
        $caracteres = (int) $this->input->post('caracteres');
        if ($caracteres >= 24 && $caracteres <= 64) {
            $valores['caracteres'] = $caracteres;
        }

        // Desde Ajustes un administrador configura otro puesto, identificado por id;
        // desde el POS cada equipo configura el suyo con su device_id.
        $id = (int) $this->input->post('id');
        if ($id > 0) {
            if (!$this->Admin) {
                $this->_json(['error' => lang('access_denied')], 403);
            }
            $this->db->update('pos_workstations', $valores, ['id' => $id]);
            $this->_json(['ok' => true, 'qz_printer' => $impresora]);
        }

        $device = $this->_device_id();
        if ($device === '') {
            $this->_json(['error' => 'device_id invalido'], 400);
        }

        $valores['ultimo_uso'] = date('Y-m-d H:i:s');
        $this->db->update('pos_workstations', $valores, ['device_id' => $device]);

        if ($this->db->affected_rows() === 0 && !$this->_existe($device)) {
            $this->registrar();  // Todavia no estaba registrado.
        }
        $this->_json(['ok' => true, 'qz_printer' => $impresora]);
    }

    /**
     * POST workstation/impresoras  (device_id, impresoras[])
     * QZ Tray solo puede enumerar las impresoras desde el navegador de la maquina,
     * asi que el POS las reporta y quedan disponibles para elegirlas desde Ajustes.
     */
    public function impresoras()
    {
        $device = $this->_device_id();
        if ($device === '') {
            $this->_json(['error' => 'device_id invalido'], 400);
        }

        $lista = $this->input->post('impresoras');
        if (!is_array($lista)) {
            $this->_json(['error' => 'lista invalida'], 400);
        }
        $lista = array_values(array_filter(array_map(function ($n) {
            return mb_substr(trim((string) $n), 0, 150);
        }, $lista)));

        $this->db->update('pos_workstations',
            ['impresoras' => json_encode($lista, JSON_UNESCAPED_UNICODE), 'ultimo_uso' => date('Y-m-d H:i:s')],
            ['device_id' => $device]);

        $this->_json(['ok' => true, 'cantidad' => count($lista)]);
    }

    /**
     * POST workstation/renombrar  (id, nombre)   — solo administradores
     */
    public function renombrar()
    {
        if (!$this->Admin) {
            $this->_json(['error' => lang('access_denied')], 403);
        }
        $id     = (int) $this->input->post('id');
        $nombre = trim((string) $this->input->post('nombre'));
        if ($id <= 0 || $nombre === '') {
            $this->_json(['error' => 'Datos incompletos'], 400);
        }
        $this->db->update('pos_workstations', ['nombre' => mb_substr($nombre, 0, 100)], ['id' => $id]);
        $this->_json(['ok' => true]);
    }

    /**
     * POST workstation/papel  (id, caracteres)  — solo administradores.
     * Aparte de impresora(): cambiar el ancho no debe tocar la impresora elegida.
     */
    public function papel()
    {
        if (!$this->Admin) {
            $this->_json(['error' => lang('access_denied')], 403);
        }
        $id = (int) $this->input->post('id');
        $caracteres = (int) $this->input->post('caracteres');
        if ($id <= 0 || $caracteres < 24 || $caracteres > 64) {
            $this->_json(['error' => 'Datos incompletos'], 400);
        }
        $this->db->update('pos_workstations', ['caracteres' => $caracteres], ['id' => $id]);
        $this->_json(['ok' => true, 'caracteres' => $caracteres]);
    }

    /**
     * POST workstation/eliminar  (id)   — solo administradores.
     * El equipo se vuelve a registrar solo la proxima vez que abra el POS.
     */
    public function eliminar()
    {
        if (!$this->Admin) {
            $this->_json(['error' => lang('access_denied')], 403);
        }
        $id = (int) $this->input->post('id');
        if ($id <= 0) {
            $this->_json(['error' => 'Datos incompletos'], 400);
        }
        $this->db->delete('pos_workstations', ['id' => $id]);
        $this->_json(['ok' => true]);
    }

    // -----------------------------------------------------------------------
    // Privados
    // -----------------------------------------------------------------------

    private function _existe($device)
    {
        return $this->db->get_where('pos_workstations', ['device_id' => $device], 1)->num_rows() > 0;
    }

    /** Solo se aceptan identificadores con la forma que genera el navegador. */
    private function _device_id()
    {
        $device = (string) $this->input->post('device_id');
        return preg_match('/^[a-f0-9-]{16,64}$/i', $device) ? strtolower($device) : '';
    }

    /** "Caja 1", "Caja 2"... segun cuantos puestos haya ya registrados. */
    private function _nombre_sugerido()
    {
        return sprintf(lang('puesto_nombre_sugerido'), $this->db->count_all('pos_workstations') + 1);
    }

    private function _json($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE))
            ->_display();
        // _display() explicito: CI3 vuelca la salida al terminar el controlador, y
        // este exit no llega ahi. Sin esto la respuesta sale con cuerpo vacio.
        exit;
    }
}

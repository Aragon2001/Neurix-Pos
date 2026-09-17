<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Puerta HTTP del lector de facturas de compra. Toda la lógica vive en la
 * librería Lectorcompras, que también se dispara sola tras enviar comprobantes.
 */
class Correocompras extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        // El cron corre sin sesion que revisar.
        if (!is_cli()) {
            if (!$this->loggedIn) {
                $this->_json(['error' => lang('access_denied')], 401);
            }
            if (!$this->Admin) {
                $this->_json(['error' => lang('access_denied')], 403);
            }
        }
        $this->load->library('lectorcompras');
    }

    /**
     * POST correocompras/importar
     * Con `todo=1` recorre la casilla entera, no solo lo que llegó sin leer.
     */
    public function importar()
    {
        $todo = (string) $this->input->post('todo') === '1';
        $this->_json($this->lectorcompras->importar($todo));
    }

    /** POST correocompras/probar */
    public function probar()
    {
        $this->_json($this->lectorcompras->probar());
    }

    /**
     * Solo por consola:
     *     php index.php correocompras cron
     *
     * Deja la casilla vigilada sin que nadie tenga que entrar a Ajustes. En el
     * Programador de tareas de Windows cada 10 o 15 minutos, o en un cron.
     */
    public function cron()
    {
        if (!is_cli()) {
            show_404();
        }
        if ($this->Settings->mail_client_enabled != '1') {
            echo "La lectura de facturas de compra esta desactivada en Ajustes.\n";
            return;
        }

        $r = $this->lectorcompras->importar();
        if (empty($r['ok'])) {
            echo 'ERROR: ' . $r['error'] . "\n";
            return;
        }
        echo sprintf(
            "revisados=%d registrados=%d repetidos=%d sin_xml=%d\n",
            $r['revisados'], $r['registrados'], $r['repetidos'], $r['sin_xml']
        );
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

<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Settings extends MY_Controller
{

    function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }

        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }

        $this->load->library('form_validation');
        $this->load->model('settings_model');
    }

    function index()
    {



        $this->form_validation->set_rules('site_name', lang('site_name'), 'required');
        $this->form_validation->set_rules('tel', lang('tel'), 'required');
        $this->form_validation->set_rules('language', lang('language'), 'required');
        $this->form_validation->set_rules('currency_prefix', lang('currency_code'), 'required|max_length[3]|min_length[3]');
        $this->form_validation->set_rules('default_discount', lang('default_discount'), 'required');
        $this->form_validation->set_rules('tax_rate', lang('default_tax_rate'), 'required');
        $this->form_validation->set_rules('rows_per_page', lang('rows_per_page'), 'required');
        $this->form_validation->set_rules('display_product', lang('display_product'), 'required');
        $this->form_validation->set_rules('pro_limit', lang('pro_limit'), 'required');
        $this->form_validation->set_rules('display_kb', lang('display_kb'), 'required');
        $this->form_validation->set_rules('default_customer', lang('default_customer'), 'required');
        // Hacienda exige que CodigoActividadEmisor sean 6 caracteres exactos
        // (XSD v4.4); un codigo mas corto sale en el XML y el comprobante se rechaza.
        $this->form_validation->set_rules('default_actividad', lang('default_actividad'), 'required|exact_length[6]');
        // El desplegable ofrece un catalogo cerrado; in_list evita que un POST manual
        // meta cualquier cadena en un formato que despues se pasa a date().
        $this->form_validation->set_rules('dateformat', lang('date_format'), 'required|in_list[' . implode(',', array_keys(formatos_fecha($this->Settings->dateformat))) . ']');
        $this->form_validation->set_rules('timeformat', lang('time_format'), 'required|in_list[' . implode(',', array_keys(formatos_hora($this->Settings->timeformat))) . ']');
        $this->form_validation->set_rules('item_addition', lang('item_addition'), 'required');
        if ($this->input->post('protocol') == 'smtp') {
            $this->form_validation->set_rules('smtp_host', lang('smtp_host'), 'required');
            $this->form_validation->set_rules('smtp_user', lang('smtp_user'), 'required');
            $this->form_validation->set_rules('smtp_pass', lang('smtp_pass'), 'required');
            $this->form_validation->set_rules('smtp_port', lang('smtp_port'), 'required');
        }
        if ($this->input->post('stripe')) {
            $this->form_validation->set_rules('stripe_secret_key', lang('stripe_secret_key'), 'required');
            $this->form_validation->set_rules('stripe_publishable_key', lang('stripe_publishable_key'), 'required');
        }
        // $this->form_validation->set_rules('bill_header', lang('bill_header'), 'required');
        // $this->form_validation->set_rules('bill_footer', lang('bill_footer'), 'required');

        if ($this->form_validation->run() == true) {
            $data = array(
                'site_name' => DEMO ? 'NEURIX POS' : $this->input->post('site_name'),
                'language' => $this->input->post('language'),
                'tel' => $this->input->post('tel'),
                'currency_prefix' => DEMO ? 'USD' : strtoupper($this->input->post('currency_prefix')),
                'default_tax_rate' => $this->input->post('tax_rate'),
                'default_discount' => $this->input->post('default_discount'),
                'tope_descuento' => min(100, max(0, (float) $this->input->post('tope_descuento'))),
                'rows_per_page' => $this->input->post('rows_per_page'),
                'bsty' => $this->input->post('display_product'),
                'pro_limit' => $this->input->post('pro_limit'),
                'display_kb' => $this->input->post('display_kb'),
                'default_category' => $this->input->post('default_category'),
                'default_customer' => $this->input->post('default_customer'),
                'default_actividad' => $this->input->post('default_actividad'),
                'dateformat' => DEMO ? 'jS F Y' : $this->input->post('dateformat'),
                'timeformat' => DEMO ? 'h:i A' : $this->input->post('timeformat'),
                'header' => $this->input->post('bill_header'),
                'footer' => $this->input->post('bill_footer'),
                'default_email' => DEMO ? 'noreply@spos.tecdiary.my' : $this->input->post('default_email'),
                'protocol' => $this->input->post('protocol'),
                'smtp_host' => $this->input->post('smtp_host'),
                'smtp_user' => $this->input->post('smtp_user'),
                'smtp_port' => $this->input->post('smtp_port'),
                'smtp_crypto' => $this->input->post('smtp_crypto'),
                'pin_code' => $this->input->post('pin_code') ? $this->input->post('pin_code') : NULL,
                'focus_add_item' => $this->input->post('focus_add_item'),
                'edit_last_product' => $this->input->post('edit_last_product'),
                'add_customer' => $this->input->post('add_customer'),
                'toggle_category_slider' => $this->input->post('toggle_category_slider'),
                'cancel_sale' => $this->input->post('cancel_sale'),
                'suspend_sale' => $this->input->post('suspend_sale'),
                'finalize_sale' => $this->input->post('finalize_sale'),
                'today_sale' => $this->input->post('today_sale'),
                'open_hold_bills' => $this->input->post('open_hold_bills'),
                'close_register' => $this->input->post('close_register'),
                // 'java_applet' => DEMO ? '0' : $this->input->post('enable_java_applet'),
                'rounding' => $this->input->post('rounding'),
                'item_addition' => $this->input->post('item_addition'),
                'stripe' => $this->input->post('stripe'),
                'stripe_secret_key' => $this->input->post('stripe_secret_key'),
                'stripe_publishable_key' => $this->input->post('stripe_publishable_key'),
                'theme' => 'default',
                'show_categories' => $this->input->post('show_categories') === '0' ? '0' : '1',
                'theme_style' => $this->input->post('theme_style') ? $this->input->post('theme_style') : 'black',
                'after_sale_page' => $this->input->post('after_sale_page'),
                'multi_store' => $this->input->post('multi_store'),
                'overselling' => $this->input->post('overselling'),
                'decimals' => $this->input->post('decimals'),
                'decimals_sep' => $this->input->post('decimals_sep'),
                'thousands_sep' => $this->input->post('thousands_sep'),
                'sac' => $this->input->post('sac'),
                'qty_decimals' => $this->input->post('qty_decimals'),
                'display_symbol' => $this->input->post('display_symbol'),
                'symbol' => $this->input->post('symbol'),
                'auto_print' => $this->input->post('auto_print'),
                'rtl' => $this->input->post('rtl'),
                'print_img' => $this->input->post('print_img'),
                'sensibility_search' => $this->input->post('sensibility_search'),
                'enable_credit' => $this->input->post('enable_credit'),
                'enable_layaway' => $this->input->post('enable_layaway'),
                'enable_quote' => $this->input->post('enable_quote'),
                'enable_auth_open' => $this->input->post('enable_auth_open'),
                'is_shipping' => $this->input->post('is_shipping'),
                'enable_detail_register' => $this->input->post('enable_detail_register'),
                'enable_show_tax' => $this->input->post('enable_show_tax'),
                'enable_fastedition' => $this->input->post('enable_fastedition'),
                'footer_apartado' => $this->input->post('footer_apartado'),
                'user_token_test' => $this->input->post('user_token_test'),
                'password_token_test' => encrypt_credential($this->input->post('password_token_test')),
                'user_token_prod' => $this->input->post('user_token_prod'),
                'password_token_prod' => encrypt_credential($this->input->post('password_token_prod')),
                'certificado_ced_test' => $this->input->post('certificado_ced_test'),
                'certificado_pin_test' => encrypt_credential($this->input->post('certificado_pin_test')),
                'certificado_ced_prod' => $this->input->post('certificado_ced_prod'),
                'certificado_pin_prod' => encrypt_credential($this->input->post('certificado_pin_prod')),
                'cedula_emisor' => $this->input->post('cedula_emisor'),
                'cedula_proveedor_sistemas' => preg_replace('/\D/', '', (string) $this->input->post('cedula_proveedor_sistemas')),
                'tipo_doc_emisor' => $this->input->post('tipo_doc_emisor'),
                'nombre_emisor' => $this->input->post('nombre_emisor'),
                'nombre_comercial' => $this->input->post('nombre_comercial'),
                'email_emisor' => $this->input->post('email_emisor'),
                'telefono_emisor' => $this->input->post('telefono_emisor'),
                'fax_emisor' => $this->input->post('fax_emisor'),
                'cod_provincia' => $this->input->post('cod_provincia'),
                'cod_canton' => $this->input->post('cod_canton'),
                'cod_distrito' => $this->input->post('cod_distrito'),
                'cod_barrio' => $this->input->post('cod_barrio'),
                'block_hacienda' => $this->input->post('block_hacienda'),
                'enable_fractions' => $this->input->post('enable_fractions'),
                'quantity_suggest' => $this->input->post('quantity_suggest'),
                // Con la configuracion de Hacienda bloqueada el selector no se envia:
                // sin este respaldo un guardado devolveria una instalacion en produccion a pruebas.
                'ambiente' => in_array($this->input->post('ambiente'), ['test', 'prod'], TRUE)
                    ? $this->input->post('ambiente')
                    : (($this->Settings->ambiente === 'prod') ? 'prod' : 'test'),
                'mailpath' => $this->input->post('mailpath'),
                'otras_senas' => $this->input->post('otras_senas'),
                'cod_telefono_emisor' => $this->input->post('cod_telefono_emisor'),
                'footer_hacienda_fe' => $this->input->post('footer_hacienda_fe'),
                'footer_hacienda_nc' => $this->input->post('footer_hacienda_nc'),
                'clave_ultima' => preg_replace('/\D/', '', (string) $this->input->post('clave_ultima')),
                'propina_enable' => $this->input->post('propina_enable'),
                'propina_rate' => $this->input->post('propina_rate'),
            );

            // Casa matriz y terminal completan el consecutivo de 20 posiciones.
            // Solo se tocan si el formulario los trajo: guardarlos vacios dejaria
            // el consecutivo corto y todos los comprobantes serian rechazados.
            foreach (array('casa_matriz' => 3, 'terminal_pos' => 5) as $campo => $largo) {
                if ($this->input->post($campo) !== NULL) {
                    $digitos = preg_replace('/\D/', '', (string) $this->input->post($campo));
                    if ($digitos !== '') {
                        $data[$campo] = str_pad($digitos, $largo, '0', STR_PAD_LEFT);
                    }
                }
            }

            // Arranque de la numeracion, un campo por tipo de comprobante.
            foreach (array_keys(tipos_comprobante()) as $tipo) {
                $data['consec_inicial_' . $tipo] = (int) $this->input->post('consec_inicial_' . $tipo);
            }

            if ($this->Settings->block_hacienda == "1") {
                unset($data['user_token_test']);
                unset($data['password_token_test']);
                unset($data['user_token_prod']);
                unset($data['password_token_prod']);
                unset($data['certificado_ced_test']);
                unset($data['certificado_pin_test']);
                unset($data['certificado_ced_prod']);
                unset($data['certificado_pin_prod']);
                unset($data['cedula_emisor']);
                unset($data['cedula_proveedor_sistemas']);
                unset($data['tipo_doc_emisor']);
                unset($data['nombre_emisor']);
                unset($data['nombre_comercial']);
                unset($data['email_emisor']);
                unset($data['telefono_emisor']);
                unset($data['fax_emisor']);
                unset($data['cod_provincia']);
                unset($data['cod_canton']);
                unset($data['cod_distrito']);
                unset($data['cod_barrio']);
                unset($data['otras_senas']);
                unset($data['server_lic']);
                unset($data['num_lic']);
                unset($data['usuario_lic']);
                unset($data['footer_hacienda_fe']);
                unset($data['footer_hacienda_nc']);
                unset($data['clave_ultima']);
                foreach (array_keys(tipos_comprobante()) as $tipo) {
                    unset($data['consec_inicial_' . $tipo]);
                }
                unset($data['block_hacienda']);
            }

            // El par sin sufijo es la copia del ambiente elegido, para el codigo que
            // lee el certificado directo de la tabla sin pasar por MY_Controller.
            if (isset($data['certificado_ced_' . $data['ambiente']])) {
                $data['certificado_ced'] = $data['certificado_ced_' . $data['ambiente']];
                $data['certificado_pin'] = $data['certificado_pin_' . $data['ambiente']];
            }

            if ($this->input->post('smtp_pass')) {
                $data['smtp_pass'] = encrypt_credential($this->input->post('smtp_pass'));
            }

            // Casilla que recibe las facturas de compra. El cliente OAuth es del
            // proveedor (app/config/googlemail.php) y no se edita aca; los tokens
            // los escribe Mailauth al autorizar.
            $data['mail_auth']            = $this->input->post('mail_auth') === 'oauth_google' ? 'oauth_google' : 'password';
            $data['mail_client_auth']     = $this->input->post('mail_client_auth') === 'oauth_google' ? 'oauth_google' : 'password';
            $data['mail_client_enabled']  = (int) $this->input->post('mail_client_enabled');
            $data['mail_client_host']     = $this->input->post('mail_client_host');
            $data['mail_client_port']     = $this->input->post('mail_client_port');
            $data['mail_client_user']     = $this->input->post('mail_client_user');
            $data['mail_client_crypto']   = in_array($this->input->post('mail_client_crypto'), array('ssl', 'tls', ''), TRUE)
                ? $this->input->post('mail_client_crypto') : 'ssl';
            $data['mail_client_carpeta']  = $this->input->post('mail_client_carpeta') ?: 'INBOX';
            if ($this->input->post('mail_client_pass')) {
                $data['mail_client_pass'] = encrypt_credential($this->input->post('mail_client_pass'));
            }

            if (DEMO) {
                $data['site_name'] = 'NEURIX POS';
            } else {
                if (!empty($_FILES['userfile']['size'])) {

                    $this->load->library('upload');
                    $config['upload_path'] = 'uploads/';
                    $config['allowed_types'] = 'gif|jpg|png';
                    $config['max_size'] = '300';
                    $config['max_width'] = '300';
                    $config['max_height'] = '80';
                    $config['overwrite'] = FALSE;
                    $this->upload->initialize($config);

                    if (!$this->upload->do_upload()) {
                        $error = $this->upload->display_errors();
                        $this->session->set_flashdata('message', $error);
                        redirect('settings');
                    }

                    $photo = $this->upload->file_name;
                }
            }
            if (isset($photo)) {
                $data['logo'] = $photo;
            }
        }

        if ($this->form_validation->run() == true && $this->settings_model->updateSetting($data)) {

            $this->load->driver('cache', array('adapter' => 'file'));
            $this->cache->file->delete('app_settings');

            $this->session->set_flashdata('message', lang('setting_updated'));
            redirect('settings');
        } else {

            // Con db_debug apagado un UPDATE fallido no avisa: el motivo solo está en $this->db->error().
            if ($this->input->post() && $this->form_validation->run() === true) {
                $dbError = $this->db->error();
                if (!empty($dbError['message'])) {
                    $this->session->set_flashdata('error', lang('setting_update_failed') . ' ' . $dbError['message']);
                    log_message('error', 'settings: ' . $dbError['message'] . ' | ' . $this->db->last_query());
                }
            }

            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            // getSettings() trae la fila cruda: las credenciales estan cifradas en la
            // tabla y hay que descifrarlas para poder editarlas en el formulario.
            $this->data['settings'] = $this->site->getSettings();
            foreach (array('password_token_test', 'password_token_prod', 'certificado_pin', 'certificado_pin_test',
                           'certificado_pin_prod', 'smtp_pass', 'mail_client_pass') as $campo) {
                $this->data['settings']->$campo = decrypt_credential($this->data['settings']->$campo ?? '');
            }
            $this->data['customers'] = $this->site->getAllCustomers();
            $this->data['actividadeconomica'] = $this->site->getAllActividades();
            $this->data['categories'] = $this->site->getAllCategories();
            $this->data['puestos'] = $this->db->order_by('ultimo_uso', 'DESC')->get('pos_workstations')->result();

            $this->data['provincias'] = $this->db->select('codigo_provincia as codigo, nombre_provincia as nombre')
                ->order_by('nombre_provincia', 'ASC')->get('tec_provincia_cr')->result();
            $this->data['cantones_actuales'] = array();
            $this->data['distritos_actuales'] = array();
            $this->data['barrios_actuales'] = array();
            if (!empty($this->Settings->cod_provincia)) {
                $this->data['cantones_actuales'] = $this->db->select('codigo_canton as codigo, nombre_canton as nombre')
                    ->where('codigo_provincia', $this->Settings->cod_provincia)
                    ->order_by('nombre_canton', 'ASC')->get('tec_canton_cr')->result();
            }
            if (!empty($this->Settings->cod_canton)) {
                $this->data['distritos_actuales'] = $this->db->select('codigo_distrito as codigo, nombre_distrito as nombre')
                    ->where('codigo_provincia', $this->Settings->cod_provincia)
                    ->where('codigo_canton', $this->Settings->cod_canton)
                    ->order_by('nombre_distrito', 'ASC')->get('tec_distrito_cr')->result();
            }
            if (!empty($this->Settings->cod_distrito)) {
                $this->data['barrios_actuales'] = $this->db->select('codigo_barrio as codigo, nombre_barrio as nombre')
                    ->where('codigo_provincia', $this->Settings->cod_provincia)
                    ->where('codigo_canton', $this->Settings->cod_canton)
                    ->where('codigo_distrito', $this->Settings->cod_distrito)
                    ->order_by('nombre_barrio', 'ASC')->get('tec_barrio_cr')->result();
            }

            $this->data['page_title'] = lang('settings');
            $bc = array(array('link' => '#', 'page' => lang('settings')));
            $meta = array('page_title' => lang('settings'), 'bc' => $bc);
            $this->page_construct('settings/index', $this->data, $meta);
        }
    }

    // AJAX: ubicaciones en cascada (provincia -> canton -> distrito -> barrio)
    function get_cantones($codigo_provincia = '')
    {
        $rows = $this->db->select('codigo_canton as codigo, nombre_canton as nombre')
            ->where('codigo_provincia', $codigo_provincia)
            ->order_by('nombre_canton', 'ASC')->get('tec_canton_cr')->result();
        header('Content-Type: application/json');
        echo json_encode($rows);
    }

    function get_distritos($codigo_provincia = '', $codigo_canton = '')
    {
        $rows = $this->db->select('codigo_distrito as codigo, nombre_distrito as nombre')
            ->where('codigo_provincia', $codigo_provincia)
            ->where('codigo_canton', $codigo_canton)
            ->order_by('nombre_distrito', 'ASC')->get('tec_distrito_cr')->result();
        header('Content-Type: application/json');
        echo json_encode($rows);
    }

    function get_barrios($codigo_provincia = '', $codigo_canton = '', $codigo_distrito = '')
    {
        $rows = $this->db->select('codigo_barrio as codigo, nombre_barrio as nombre')
            ->where('codigo_provincia', $codigo_provincia)
            ->where('codigo_canton', $codigo_canton)
            ->where('codigo_distrito', $codigo_distrito)
            ->order_by('nombre_barrio', 'ASC')->get('tec_barrio_cr')->result();
        header('Content-Type: application/json');
        echo json_encode($rows);
    }

    function upload_certificado()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('settings');
        }

        if (!isset($_FILES['certificado_p12']) || $_FILES['certificado_p12']['size'] === 0) {
            $this->session->set_flashdata('error', 'No se seleccionó ningún archivo.');
            redirect('settings#tab-emisor');
        }

        $file     = $_FILES['certificado_p12'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // Cada bloque de la vista manda su propio ambiente: se sube el certificado del
        // ambiente elegido, no el del que este activo al momento de subirlo.
        $ambiente = $this->input->post('ambiente_cert') === 'prod' ? 'prod' : 'test';
        $etiqueta = $ambiente === 'prod' ? lang('produccion') : lang('pruebas_sandbox');
        // El propio archivo define el nombre: es lo unico que el usuario conoce.
        $nombre   = nombre_certificado_seguro(pathinfo($file['name'], PATHINFO_FILENAME));

        if ($ext !== 'p12') {
            $this->session->set_flashdata('error', lang('cert_error_extension'));
            redirect('settings#tab-emisor');
        }

        if ($nombre === '') {
            $this->session->set_flashdata('error', lang('cert_error_nombre'));
            redirect('settings#tab-emisor');
        }

        $destDir = FCPATH . 'files/certificados/' . $ambiente . '/';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destDir . $nombre . '.p12')) {
            $this->session->set_flashdata('error', lang('cert_error_permisos') . ' files/certificados/' . $ambiente . '/');
            redirect('settings#tab-emisor');
        }

        // Queda seleccionado de una vez: subirlo y tener que escribir su nombre aparte
        // era justo el paso que nadie podia adivinar.
        $cambios = array('certificado_ced_' . $ambiente => $nombre);
        if (($this->Settings->ambiente ?? 'test') === $ambiente) {
            $cambios['certificado_ced'] = $nombre;
        }
        $this->db->update('settings', $cambios, array('setting_id' => 1));
        $this->load->driver('cache', array('adapter' => 'file'));
        $this->cache->file->delete('app_settings');

        $this->session->set_flashdata('message', sprintf(lang('cert_subido_ok'), $etiqueta, $nombre . '.p12'));
        redirect('settings#tab-emisor');
    }

    function desbloquear_hacienda()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('settings');
        }
        $this->db->update('settings', array('block_hacienda' => 0), array('setting_id' => 1));
        $this->load->driver('cache', array('adapter' => 'file'));
        $this->cache->file->delete('app_settings');
        $this->session->set_flashdata('message', 'Datos del emisor desbloqueados.');
        redirect('settings#tab-emisor');
    }

    function updates()
    { }

    function install_update($file, $m_version, $version)
    {
        ini_set("memory_limit", "-1");
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect("welcome");
        }
        $this->load->helper('update');
        save_remote_file($file . '.zip');
        $this->tec->unzip('./files/updates/' . $file . '.zip');
        if ($m_version) {
            $this->load->library('migration');
            if (!$this->migration->latest()) {
                $this->session->set_flashdata('error', $this->migration->error_string());
                redirect("settings/updates");
            }
        }
        $this->db->update('settings', array('version' => $version, 'update' => 0), array('setting_id' => 1));
        unlink('./files/updates/' . $file . '.zip');
        $this->session->set_flashdata('success', lang('update_done'));
        redirect("settings/updates");
    }

    function backups()
    {
        ini_set("memory_limit", "-1");
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        $this->data['copias'] = $this->_copias_de_base();
        $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => '#', 'page' => lang('backups')));
        $meta = array('page_title' => lang('backups'), 'bc' => $bc);
        $this->page_construct('settings/backups', $this->data, $meta);
    }

    /**
     * Copias de la base ordenadas de la mas reciente a la mas vieja, con el
     * peso y la fecha ya resueltos: la vista no debe tocar el disco.
     *
     * El nombre lo arma backup_database() como db-backup-on-Y-m-d-H-i-s.txt; si
     * no cuadra con ese molde se cae a la fecha del archivo.
     */
    private function _copias_de_base()
    {
        $copias = array();
        foreach ((array) glob('./files/backups/*.txt') as $ruta) {
            $nombre = basename($ruta, '.txt');
            $fecha  = NULL;
            if (preg_match('/^db-backup-on-(\d{4}-\d{2}-\d{2})-(\d{2})-(\d{2})-(\d{2})$/', $nombre, $m)) {
                $fecha = $m[1] . ' ' . $m[2] . ':' . $m[3] . ':' . $m[4];
            }
            $copias[] = array(
                'nombre' => $nombre,
                'fecha'  => $fecha ? $fecha : date('Y-m-d H:i:s', filemtime($ruta)),
                'bytes'  => (int) filesize($ruta),
            );
        }
        usort($copias, function ($a, $b) {
            return strcmp($b['fecha'], $a['fecha']);
        });
        return $copias;
    }

    function backup_database()
    {
        ini_set("memory_limit", "-1");
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect("welcome");
        }
        $this->load->dbutil();
        $prefs = array(
            'format' => 'txt',
            'filename' => 'spos_db_backup.sql'
        );
        $back = $this->dbutil->backup($prefs);
        $backup = &$back;
        $db_name = 'db-backup-on-' . date("Y-m-d-H-i-s") . '.txt';
        $save = './files/backups/' . $db_name;
        $this->load->helper('file');
        write_file($save, $backup);
        $this->session->set_flashdata('message', lang('db_saved'));
        redirect("settings/backups");
    }

    function backup_files()
    {
        ini_set("memory_limit", "-1");
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect("welcome");
        }
        $name = 'file-backup-' . date("Y-m-d-H-i-s");
        set_time_limit(300);
        $this->tec->zip("./", './files/backups/', $name);
        $this->session->set_flashdata('message', lang('backup_saved'));
        redirect("settings/backups");
        exit();
    }

    function restore_database($dbfile)
    {
        ini_set("memory_limit", "-1");
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect("welcome");
        }
        $this->exigir_token_accion();
        $ruta = './files/backups/' . basename($dbfile) . '.txt';
        if (!is_file($ruta)) {
            $this->session->set_flashdata('error', lang('copia_no_existe'));
            redirect("settings/backups");
        }
        $file = file_get_contents($ruta);
        $this->db->conn_id->multi_query($file);
        $this->db->conn_id->close();
        redirect('logout/db');
    }

    function download_database($dbfile)
    {
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect("welcome");
        }
        $ruta = './files/backups/' . basename($dbfile) . '.txt';
        if (!is_file($ruta)) {
            $this->session->set_flashdata('error', lang('copia_no_existe'));
            redirect("settings/backups");
        }
        $this->load->library('zip');
        $this->zip->read_file($ruta);
        $name = 'db_backup_' . date('Y_m_d_H_i_s') . '.zip';
        $this->zip->download($name);
        exit();
    }

    function download_backup($zipfile)
    {
        ini_set("memory_limit", "-1");
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect("welcome");
        }
        $this->load->helper('download');
        force_download('./files/backups/' . $zipfile . '.zip', NULL);
        exit();
    }

    function restore_backup($zipfile)
    {
        ini_set("memory_limit", "-1");
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect("welcome");
        }
        $file = './files/backups/' . $zipfile . '.zip';
        $this->tec->unzip($file, './');
        $this->session->set_flashdata('success', lang('files_restored'));
        redirect("settings/backups");
        exit();
    }

    function delete_database($dbfile)
    {
        ini_set("memory_limit", "-1");
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect("welcome");
        }
        $this->exigir_token_accion();
        $ruta = './files/backups/' . basename($dbfile) . '.txt';
        if (!is_file($ruta)) {
            $this->session->set_flashdata('error', lang('copia_no_existe'));
            redirect("settings/backups");
        }
        unlink($ruta);
        $this->session->set_flashdata('message', lang('db_deleted'));
        redirect("settings/backups");
    }

    function delete_backup($zipfile)
    {
        ini_set("memory_limit", "-1");
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect("welcome");
        }
        unlink('./files/backups/' . $zipfile . '.zip');
        $this->session->set_flashdata('message', lang('backup_deleted'));
        redirect("settings/backups");
    }

    function stores()
    {

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('stores');
        $bc = array(array('link' => '#', 'page' => lang('stores')));
        $meta = array('page_title' => lang('stores'), 'bc' => $bc);
        $this->page_construct('settings/stores', $this->data, $meta);
    }

    function get_stores()
    {

        $this->load->library('datatables');
        $this->datatables
            ->select("id, name, code, phone, email, address1, city, logo")
            ->from("stores")
            ->add_column("Actions", "<div class='text-center'><a href='" . site_url('settings/edit_store/$1') . "' class='tip' title='" . $this->lang->line("edit_store") . "'><i class='fa fa-edit'></i></a></div>", "id")
            ->unset_column('id');
        // <a href='" . site_url('settings/delete_store/$1') . "' onClick=\"return confirm('". $this->lang->line('alert_x_store') ."')\" class='tip btn btn-danger btn-xs' title='".$this->lang->line("delete_store")."'><i class='fa fa-trash-o'></i></a>
        echo $this->datatables->generate();
    }

    /**
     * Responde si un codigo de tienda esta libre, para que el formulario avise
     * antes de enviar: `stores`.`code` es unico y el rechazo del servidor
     * devuelve la pantalla vacia.
     */
    function codigo_tienda_libre()
    {
        $codigo = trim((string) $this->input->get('code', TRUE));
        $id     = (int) $this->input->get('id', TRUE);

        if ($codigo === '') {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(array('libre' => FALSE)));
            return;
        }

        $this->db->where('code', $codigo);
        if ($id) {
            $this->db->where('id !=', $id);
        }
        $libre = ($this->db->count_all_results('stores') === 0);

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(array('libre' => $libre)));
    }

    function add_store()
    {

        $this->form_validation->set_rules('name', $this->lang->line("name"), 'required');
        $this->form_validation->set_rules('email', $this->lang->line("email_address"), 'valid_email');
        $this->form_validation->set_rules('code', $this->lang->line("code"), 'required|is_unique[stores.code]|min_length[2]|max_length[20]');
        $this->form_validation->set_rules('phone', $this->lang->line("phone"), 'required');

        if ($this->form_validation->run() == true) {

            $data = array(
                'name' => $this->input->post('name'),
                'code' => $this->input->post('code'),
                'email' => $this->input->post('email'),
                'phone' => $this->input->post('phone'),
                'address1' => $this->input->post('address1'),
                'address2' => $this->input->post('address2'),
                'city' => $this->input->post('city'),
                'state' => $this->input->post('state'),
                'postal_code' => $this->input->post('postal_code'),
                'country' => $this->input->post('country'),
                'receipt_header' => $this->input->post('receipt_header'),
                'receipt_footer' => $this->input->post('receipt_footer'),
            );

            if (!empty($_FILES['userfile']['size'])) {

                $this->load->library('upload');

                $config['upload_path'] = 'uploads/';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = '500';
                $config['max_width'] = '300';
                $config['max_height'] = '100';
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);

                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect("settings/add_store");
                }

                $photo = $this->upload->file_name;
                $data['logo'] = $photo;
            }
        }

        if ($this->form_validation->run() == true && $cid = $this->settings_model->addStore($data)) {

            $this->session->set_flashdata('message', $this->lang->line("store_added"));
            redirect("settings/stores");
        } else {
            if ($this->input->is_ajax_request()) {
                echo json_encode(array('status' => 'failed', 'msg' => validation_errors()));
                die();
            }

            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('add_store');
            $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => site_url('settings/stores'), 'page' => lang('stores')), array('link' => '#', 'page' => lang('add_store')));
            $meta = array('page_title' => lang('add_store'), 'bc' => $bc);
            $this->page_construct('settings/add_store', $this->data, $meta);
        }
    }

    function edit_store($id = NULL)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', $this->lang->line('access_denied'));
            redirect('pos');
        }
        if ($this->input->get('id')) {
            $id = $this->input->get('id', TRUE);
        }

        $store = $this->settings_model->getStoreByID($id);
        if ($this->input->post('code') != $store->code) {
            $this->form_validation->set_rules('code', $this->lang->line("code"), 'is_unique[stores.code]');
        }
        $this->form_validation->set_rules('name', $this->lang->line("name"), 'required');
        $this->form_validation->set_rules('email', $this->lang->line("email_address"), 'valid_email');
        $this->form_validation->set_rules('code', $this->lang->line("code"), 'required|min_length[2]|max_length[20]');
        $this->form_validation->set_rules('phone', $this->lang->line("phone"), 'required');

        if ($this->form_validation->run() == true) {

            $data = array(
                'name' => $this->input->post('name'),
                'code' => $this->input->post('code'),
                'email' => $this->input->post('email'),
                'phone' => $this->input->post('phone'),
                'address1' => $this->input->post('address1'),
                'address2' => $this->input->post('address2'),
                'city' => $this->input->post('city'),
                'state' => $this->input->post('state'),
                'postal_code' => $this->input->post('postal_code'),
                'country' => $this->input->post('country'),
                'receipt_header' => $this->input->post('receipt_header'),
                'receipt_footer' => $this->input->post('receipt_footer'),
            );

            if (!empty($_FILES['userfile']['size'])) {

                $this->load->library('upload');

                $config['upload_path'] = 'uploads/';
                $config['allowed_types'] = 'gif|jpg|png';
                $config['max_size'] = '500';
                $config['max_width'] = '300';
                $config['max_height'] = '100';
                $config['overwrite'] = FALSE;
                $config['encrypt_name'] = TRUE;
                $this->upload->initialize($config);

                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect("settings/edit_store/" . $id);
                }

                $photo = $this->upload->file_name;
                $data['logo'] = $photo;
            }
        }

        if ($this->form_validation->run() == true && $this->settings_model->updateStore($id, $data)) {

            $this->session->set_flashdata('message', $this->lang->line("store_updated"));
            redirect("settings/stores");
        } else {

            $this->data['store'] = $store;
            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('edit_store');
            $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => site_url('settings/stores'), 'page' => lang('stores')), array('link' => '#', 'page' => lang('edit_store')));
            $meta = array('page_title' => lang('edit_store'), 'bc' => $bc);
            $this->page_construct('settings/edit_store', $this->data, $meta);
        }
    }

    function delete_store($id = NULL)
    {
        // if (DEMO) {
        //     $this->session->set_flashdata('error', $this->lang->line("disabled_in_demo"));
        //     redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'welcome');
        // }
        // if ($this->input->get('id')) { $id = $this->input->get('id', TRUE); }
        // if ($id == 1) {
        //     $this->session->set_flashdata('error', lang("x_delete_1st_store"));
        //     redirect("settings/stores");
        // }
        // if ($this->settings_model->deleteStore($id)) {
        // $this->session->set_flashdata('message', lang("store_deleted"));
        redirect("settings/stores");
        // }
    }

    function shipping()
    {

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('shipping_method');
        $bc = array(array('link' => '#', 'page' => lang('shipping_method')));
        $meta = array('page_title' => lang('shipping_method'), 'bc' => $bc);
        $this->page_construct('settings/shipping_method', $this->data, $meta);
    }

    function get_shipping()
    {
        $this->load->library('datatables');
        $this->datatables
            ->select("id_shipping_method, name")
            ->from("shipping_method")
            ->add_column("Actions", "<div class='text-center'><a href='" . site_url('settings/edit_shipping/$1') . "' class='tip' title='Modificar'><i class='fa fa-edit'></i></a> <a href='" . site_url('settings/delete_shipping/$1' . '?t=' . $this->token_accion()) . "' data-confirm=\"" . $this->lang->line('alert_x_shipping') . "\" class='tip' title='Eliminar'><i class='fa fa-trash-o'></i></a></div>", "id_shipping_method");
        echo $this->datatables->generate();
    }

    function add_shipping($id = NULL)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', $this->lang->line('access_denied'));
            redirect('pos');
        }
        $this->form_validation->set_rules('name', $this->lang->line("description"), 'required');
        if ($this->form_validation->run() == true) {

            $data = array(
                'name' => $this->input->post('name')
            );
            $this->settings_model->addShipping($data);
            $this->session->set_flashdata('message', $this->lang->line("shipping_updated"));
            redirect("settings/shipping");
        } else {

            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('add_shipping');
            $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => site_url('settings/add_shipping'), 'page' => lang('shipping_method')), array('link' => '#', 'page' => lang('add_shipping')));
            $meta = array('page_title' => lang('add_shipping'), 'bc' => $bc);
            $this->page_construct('settings/add_shipping', $this->data, $meta);
        }
    }

    function edit_shipping($id = NULL)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', $this->lang->line('access_denied'));
            redirect('pos');
        }
        if ($this->input->get('id_shipping_method')) {
            $id = $this->input->get('id_shipping_method', TRUE);
        }

        $shipping = $this->settings_model->getShippingByID($id);
        // $this->form_validation->set_rules('id_shipping_method', $this->lang->line("code_shipping"), 'required');
        $this->form_validation->set_rules('name', $this->lang->line("name"), 'required');

        if ($this->form_validation->run() == true) {

            $data = array(
                'name' => $this->input->post('name')
            );
        }

        if ($this->form_validation->run() == true && $this->settings_model->updateShipping($id, $data)) {

            $this->session->set_flashdata('message', $this->lang->line("shipping_updated"));
            redirect("settings/shipping");
        } else {

            $this->data['shipping'] = $shipping;
            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('edit_shipping');
            $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => site_url('settings/shipping'), 'page' => lang('shipping_method')), array('link' => '#', 'page' => lang('edit_shipping')));
            $meta = array('page_title' => lang('edit_shipping'), 'bc' => $bc);
            $this->page_construct('settings/edit_shipping', $this->data, $meta);
        }
    }

    function delete_shipping($id = NULL)
    {
        if (DEMO) {
            $this->session->set_flashdata('error', $this->lang->line("disabled_in_demo"));
            redirect('pos');
        }

        if ($this->input->get('id_shipping_method')) {
            $id = $this->input->get('id_shipping_method', TRUE);
        }

        if ($this->settings_model->deleteShipping($id)) {
            $this->session->set_flashdata('message', lang("shipping_deleted"));
            redirect("settings/shipping");
        }
    }

    function compruebausers()
    {

        $this->load->library('HttpClient');
        if ($this->input->post('ambiente') == "prod") {
            $authUrl = 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token';
            $clientId = 'api-prod';
        } else {
            $authUrl = 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token';
            $clientId = 'api-stag';
        }


        // El IDP de Hacienda responde invalid_scope si 'scope' viaja en blanco:
        // el parametro se omite, no se manda vacio.
        $body = [
            'client_id' => $clientId,
            'grant_type' => 'password',
            'username' => trim($this->input->post('user')),
            'password' => trim($this->input->post('password')),
        ];

        $this->httpclient->setOptions(
            array(
                'data' => $body,
                'url' => $authUrl,
            )
        );

        try {
            if (!$this->httpclient->post()) {
                echo "Error de conexion con Hacienda: " . $this->httpclient->getErrorMsg();
                return;
            }
            $result = json_decode($this->httpclient->getResults());

            if (!empty($result->access_token)) {
                echo "!!! Usuario y/o Contraseña Validos !!!";
            } else if (isset($result->error_description)) {
                echo "Hacienda rechazo la peticion: " . $result->error_description . " (" . $result->error . ")";
            } else {
                echo "Error: respuesta inesperada de Hacienda.";
            }
        } catch (\Exception $e) {
            echo "Error al consultar a Hacienda: " . $e->getMessage();
        }
    }

    /**
     * Tablas de las que sale el respaldo de XML.
     *
     * La marca del nombre de archivo es el `tipo_doc` de Hacienda (v4.4): 1
     * factura y tiquete, 2 nota de debito, 3 nota de credito, 8 factura de
     * compra, 9 recibo de pago. Cambiarla renombra los archivos del zip.
     */
    private function _fuentes_xml()
    {
        $fuentes = array(
            array('tabla' => 'hacienda_tiketes', 'id' => 'id',    'marca' => '1', 'rotulo' => lang('xml_facturas')),
            array('tabla' => 'hacienda_nd',      'id' => 'id_nd', 'marca' => '2', 'rotulo' => lang('xml_notas_debito')),
            array('tabla' => 'hacienda_cn',      'id' => 'id_cn', 'marca' => '3', 'rotulo' => lang('xml_notas_credito')),
            array('tabla' => 'hacienda_fec',     'id' => 'id',    'marca' => '8', 'rotulo' => lang('xml_compras')),
            array('tabla' => 'hacienda_rep',     'id' => 'id',    'marca' => '9', 'rotulo' => lang('xml_recibos')),
        );

        // Una instalacion vieja puede no tener todas las tablas: la migracion
        // las va agregando y el respaldo no debe caerse por eso.
        return array_values(array_filter($fuentes, function ($f) {
            return $this->db->table_exists($f['tabla']);
        }));
    }

    /**
     * Cuántos comprobantes firmados y cuántas respuestas de Hacienda hay por
     * tipo. Alimenta la pantalla del respaldo; no toca ningún archivo.
     */
    private function _inventario_xml()
    {
        $inventario = array();
        foreach ($this->_fuentes_xml() as $f) {
            $tabla = $this->db->dbprefix($f['tabla']);
            $fila  = $this->db->query(
                "SELECT COUNT(NULLIF(TRIM(COALESCE(xml_sign, '')), '')) AS firmados,
                        COUNT(NULLIF(TRIM(COALESCE(xml_hacienda, '')), '')) AS respuestas
                 FROM `{$tabla}`"
            )->row();

            $inventario[] = array(
                'rotulo'     => $f['rotulo'],
                'firmados'   => (int) $fila->firmados,
                'respuestas' => (int) $fila->respuestas,
            );
        }
        return $inventario;
    }

    function backups_xml()
    {
        // Sin la extension zip el respaldo no se puede armar; la pantalla lo
        // dice en vez de dejar que la descarga muera con un error fatal.
        $this->data['hay_zip'] = class_exists('ZipArchive');
        $this->data['inventario'] = $this->_inventario_xml();
        $bc = array(
            array('link' => site_url('settings'), 'page' => lang('settings')),
            array('link' => site_url('settings/backups'), 'page' => lang('backups')),
            array('link' => '#', 'page' => lang('backup_xmls')),
        );
        $meta = array('page_title' => lang('backup_xmls'), 'bc' => $bc);
        $this->page_construct('settings/backups_xml', $this->data, $meta);
    }

    function getDownloadxml()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(300);

        if (!class_exists('ZipArchive')) {
            $this->session->set_flashdata('error', lang('xml_sin_zip'));
            redirect('settings/backups_xml');
        }

        // El archivo se arma en el directorio temporal, no en la raiz web, y se
        // borra despues de enviarlo: contiene todos los comprobantes firmados.
        $nombre = 'Backup-' . $this->db->database . '-' . date('Ymdhis') . '-XMLs.zip';
        $ruta   = rtrim(sys_get_temp_dir(), "/\\") . DIRECTORY_SEPARATOR . $nombre;

        $zip = new \ZipArchive();
        if ($zip->open($ruta, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            log_message('error', '[Respaldo XML] no se pudo crear ' . $ruta);
            show_error(lang('action_failed'));
            return;
        }

        $total = 0;
        foreach ($this->_fuentes_xml() as $f) {
            $tabla = $this->db->dbprefix($f['tabla']);
            $filas = $this->db->query(
                "SELECT `{$f['id']}` AS id, clave, xml_sign, xml_hacienda FROM `{$tabla}`"
            );

            foreach ($filas->result() as $r) {
                $base = $r->id . '_%s' . $f['marca'] . '_' . $r->clave . '.xml';
                // Cada XML entra por separado: un comprobante sin respuesta de
                // Hacienda no debe arrastrar consigo al firmado.
                foreach (array('T' => $r->xml_sign, 'M' => $r->xml_hacienda) as $letra => $xml) {
                    if (trim((string) $xml) === '') {
                        continue;
                    }
                    $zip->addFromString(sprintf($base, $letra), $xml);
                    $total++;
                }
            }
        }
        $zip->close();

        if (!$total) {
            @unlink($ruta);
            $this->session->set_flashdata('error', lang('xml_sin_comprobantes'));
            redirect('settings/backups_xml');
        }

        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . $nombre);
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        @unlink($ruta);
    }

    function waiting_tables()
    {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = "Mesas";
        $bc = array(array('link' => '#', 'page' => "Mesas"));
        $meta= array('page_title' => "Mesas", 'bc' => $bc);
        $this->page_construct('waiting_tables/index', $this->data, $meta);
    }

    function get_waiting_tables()
    {
        $this->load->library('datatables');
        $this->datatables->select("waiting_tables.id_waiting_tables, waiting_tables.name, waiting_tables.status, users.username as entry_by", FALSE);
        $this->datatables->from('waiting_tables')->group_by('waiting_tables.id_waiting_tables')
        ->join('users', 'users.id = waiting_tables.entry_by', 'left')
        ->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('settings/edit_table/$1') . "' class='tip btn btn-warning btn-xs' title='Editar mesa'><i class='fa fa-edit'></i></a> <a href='" . site_url('settings/delete_table/$1' . '?t=' . $this->token_accion()) . "' data-confirm=\"¿Seguro de eliminar mesa?\" class='tip btn btn-danger btn-xs' title='Mesa eliminada exitosamente'><i class='fa fa-trash-o'></i></a></div></div>", "id_waiting_tables");
        echo $this->datatables->generate();
    }

    function delete_table($id = NULL) {
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }
        if ($this->settings_model->deleteWaitingTables($id)) {
            $this->session->set_flashdata('message', "Mesa eliminada exitosamente");
            redirect('settings/waiting_tables');
        }
    }

    function edit_table($id = NULL)
    {
        // dd("Entro");
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        if($this->input->get('id')){
            $id= $this->input->get('id');
        }
        $this->form_validation->set_rules('name', lang("name"), 'required');
        $this->form_validation->set_rules('id_waiting_tables',"id_waiting_tables", 'required');
        if ($this->form_validation->run() == true) 
        {
            $id=   $this->input->post('id_waiting_tables');
            $data = array(
                "name" => $this->input->post('name'),
                "status" => $this->input->post('status') == null ? 0:1,
                "entry_by" =>  $this->session->userdata('user_id')
            );
           if($this->settings_model->updateWaitingTables($data, $id))
           {
            $this->session->set_flashdata('message', "Editado exitosamente");
            redirect('settings/waiting_tables');
           }
        }
        else
        {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['page_title'] = "Editar mesas";
            $this->data['table']     = $this->settings_model->getTableById($id);
            $bc = array(array('link' => site_url('products'), 'page' => lang('products')), array('link' => '#', 'page' => "Editar mesas"));
            $meta = array('page_title' => "Editar mesas", 'bc' => $bc);
            $this->page_construct('waiting_tables/edit', $this->data, $meta);
        }

    }

    function add_table()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->form_validation->set_rules('name', lang("name"), 'required');
        if ($this->form_validation->run() == true) 
        {
            // dd($this->input->post('status'));   
            $data = array(
                "name" => $this->input->post('name'),
                "status" => $this->input->post('status') == null ? 0:1,
                "entry_by" =>  $this->session->userdata('user_id')
            );
           if($this->settings_model->addWaitingTables($data))
           {
            $this->session->set_flashdata('message', "Agregado exitosamente");
            redirect('settings/waiting_tables');
           }
        }
        else
        {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['page_title'] = "Agregar mesas";
            $bc = array(array('link' => site_url('products'), 'page' => lang('products')), array('link' => '#', 'page' => "Agregar mesas"));
            $meta = array('page_title' => "Agregar mesas", 'bc' => $bc);
            $this->page_construct('waiting_tables/add', $this->data, $meta);
        }
    }
}

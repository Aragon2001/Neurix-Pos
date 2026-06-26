<?php

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
        $this->form_validation->set_rules('default_actividad', lang('default_actividad'), 'required');
        $this->form_validation->set_rules('dateformat', lang('date_format'), 'required');
        $this->form_validation->set_rules('timeformat', lang('time_format'), 'required');
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
                'rows_per_page' => $this->input->post('rows_per_page'),
                'bsty' => $this->input->post('display_product'),
                'pro_limit' => $this->input->post('pro_limit'),
                'display_kb' => $this->input->post('display_kb'),
                'default_category' => $this->input->post('default_category'),
                'default_customer' => $this->input->post('default_customer'),
                'default_actividad' => $this->input->post('default_actividad'),
                'barcode_symbology' => $this->input->post('barcode_symbology'),
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
                // 'receipt_printer' => $this->input->post('receipt_printer'),
                // 'cash_drawer_codes' => $this->input->post('cash_drawer_codes'),
                'focus_add_item' => $this->input->post('focus_add_item'),
                'edit_last_product' => $this->input->post('edit_last_product'),
                'add_customer' => $this->input->post('add_customer'),
                'toggle_category_slider' => $this->input->post('toggle_category_slider'),
                'cancel_sale' => $this->input->post('cancel_sale'),
                'suspend_sale' => $this->input->post('suspend_sale'),
                'print_order' => $this->input->post('print_order'),
                'print_bill' => $this->input->post('print_bill'),
                'finalize_sale' => $this->input->post('finalize_sale'),
                'today_sale' => $this->input->post('today_sale'),
                'open_hold_bills' => $this->input->post('open_hold_bills'),
                'close_register' => $this->input->post('close_register'),
                // 'pos_printers' => $this->input->post('pos_printers'),
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
                'printer' => $this->input->post('receipt_printer'),
                'order_printers' => json_encode($this->input->post('order_printers')),
                'auto_print' => $this->input->post('auto_print'),
                'remote_printing' => DEMO ? 1 : $this->input->post('remote_printing'),
                'local_printers' => $this->input->post('local_printers'),
                'rtl' => $this->input->post('rtl'),
                'print_img' => $this->input->post('print_img'),
                'nombrecompartido' => $this->input->post('nombrecompartido'),
                'ip_printer' => $this->input->post('ip_printer'),
                'sensibility_search' => $this->input->post('sensibility_search'),
                'enable_credit' => $this->input->post('enable_credit'),
                'prt_invo_after' => $this->input->post('prt_invo_after'),
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
                'certificado_ced' => $this->input->post('certificado_ced'),
                'certificado_pin' => encrypt_credential($this->input->post('certificado_pin')),
                'cedula_emisor' => $this->input->post('cedula_emisor'),
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
                'ambiente' => in_array($this->input->post('ambiente'), ['test', 'prod'])
                    ? $this->input->post('ambiente')
                    : 'test',
            );

            if ($this->Settings->block_hacienda == "1") {
                unset($data['user_token_test']);
                unset($data['password_token_test']);
                unset($data['user_token_prod']);
                unset($data['password_token_prod']);
                unset($data['certificado_ced']);
                unset($data['certificado_pin']);
                unset($data['cedula_emisor']);
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
                unset($data['block_hacienda']);
            }

            if ($this->input->post('smtp_pass')) {
                $data['smtp_pass'] = $this->input->post('smtp_pass');
            }

            if (DEMO) {
                $data['site_name'] = 'NEURIX POS';
            } else {
                if ($_FILES['userfile']['size'] > 0) {

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

            $this->session->set_flashdata('message', lang('setting_updated'));
            redirect('settings');
        } else {

            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['settings'] = $this->site->getSettings();
            $this->data['customers'] = $this->site->getAllCustomers();
            $this->data['actividadeconomica'] = $this->site->getAllActividades();
            $this->data['categories'] = $this->site->getAllCategories();
            $this->data['printers'] = $this->site->getAllPrinters();
            $this->data['page_title'] = lang('settings');
            $bc = array(array('link' => '#', 'page' => lang('settings')));
            $meta = array('page_title' => lang('settings'), 'bc' => $bc);
            $this->page_construct('settings/index', $this->data, $meta);
        }
    }

    function upload_certificado()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('settings');
        }

        if (!isset($_FILES['certificado_p12']) || $_FILES['certificado_p12']['size'] === 0) {
            $this->session->set_flashdata('error', 'No se seleccionó ningún archivo.');
            redirect('settings');
        }

        $file     = $_FILES['certificado_p12'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $ambiente = $this->Settings->ambiente ?: 'test';
        $cedula   = trim(str_replace('-', '', $this->Settings->certificado_ced));

        if ($ext !== 'p12') {
            $this->session->set_flashdata('error', 'El archivo debe tener extensión .p12');
            redirect('settings');
        }

        if (empty($cedula)) {
            $this->session->set_flashdata('error', 'Guarde primero el Nombre del Certificado en Ajustes antes de subir el archivo.');
            redirect('settings');
        }

        $destDir  = FCPATH . 'files/certificados/' . $ambiente . '/';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $destFile = $destDir . $cedula . '.p12';

        if (!move_uploaded_file($file['tmp_name'], $destFile)) {
            $this->session->set_flashdata('error', 'No se pudo guardar el certificado. Verifique permisos en files/certificados/' . $ambiente . '/');
            redirect('settings');
        }

        $this->session->set_flashdata('message', 'Certificado subido correctamente a files/certificados/' . $ambiente . '/' . $cedula . '.p12');
        redirect('settings');
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
        $this->data['files'] = glob('./files/backups/*.zip', GLOB_BRACE);
        $this->data['dbs'] = glob('./files/backups/*.txt', GLOB_BRACE);
        $this->data['xmls'] = glob('./files/backups-xml/*.zip', GLOB_BRACE);
        krsort($this->data['files']);
        krsort($this->data['dbs']);
        krsort($this->data['xmls']);
        $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => '#', 'page' => lang('backups')));
        $meta = array('page_title' => lang('backups'), 'bc' => $bc);
        $this->page_construct('settings/backups', $this->data, $meta);
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
        $this->session->set_flashdata('messgae', lang('db_saved'));
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
        $this->session->set_flashdata('messgae', lang('backup_saved'));
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
        $file = file_get_contents('./files/backups/' . $dbfile . '.txt');
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
        $this->load->library('zip');
        $this->zip->read_file('./files/backups/' . $dbfile . '.txt');
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
        unlink('./files/backups/' . $dbfile . '.txt');
        $this->session->set_flashdata('messgae', lang('db_deleted'));
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
        $this->session->set_flashdata('messgae', lang('backup_deleted'));
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
            ->select("id, name, code, phone, email, address1, city")
            ->from("stores")
            ->add_column("Actions", "<div class='text-center'><a href='" . site_url('settings/edit_store/$1') . "' class='tip' title='" . $this->lang->line("edit_store") . "'><i class='fa fa-edit'></i></a></div>", "id")
            ->unset_column('id');
        // <a href='" . site_url('settings/delete_store/$1') . "' onClick=\"return confirm('". $this->lang->line('alert_x_store') ."')\" class='tip btn btn-danger btn-xs' title='".$this->lang->line("delete_store")."'><i class='fa fa-trash-o'></i></a>
        echo $this->datatables->generate();
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

            if ($_FILES['userfile']['size'] > 0) {

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

            if ($_FILES['userfile']['size'] > 0) {

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

    function actividad()
    {

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('actividad');
        $bc = array(array('link' => '#', 'page' => lang('actividad')));
        $meta = array('page_title' => lang('actividad'), 'bc' => $bc);
        $this->page_construct('settings/actividad', $this->data, $meta);
    }


    function get_actividad()
    {

        $this->load->library('datatables');
        $this->datatables
            ->select("id_actividad, descripcion")
            ->from("actividadeconomica")
            ->add_column("Actions", "<div class='text-center'><a href='" . site_url('settings/edit_actividad/$1') . "' class='tip' title='Modificar'><i class='fa fa-edit'></i></a> <a href='" . site_url('settings/delete_actividad/$1') . "' class='tip' title='Eliminar'><i class='fa fa-trash-o'></i></a></div>", "id_actividad");
        // <a href='" . site_url('settings/delete_store/$1') . "' onClick=\"return confirm('". $this->lang->line('alert_x_store') ."')\" class='tip btn btn-danger btn-xs' title='".$this->lang->line("delete_store")."'><i class='fa fa-trash-o'></i></a>
        echo $this->datatables->generate();
    }

    function add_actividad($id = NULL)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', $this->lang->line('access_denied'));
            redirect('pos');
        }
        $this->form_validation->set_rules('id_actividad', $this->lang->line("code_actividad"), 'required');
        $this->form_validation->set_rules('descripcion', $this->lang->line("description"), 'required');
        $json = file_get_contents('https://api.hacienda.go.cr/fe/ae?identificacion=' . $this->Settings->cedula_emisor);
        $obj = json_decode($json);
        if ($this->form_validation->run() == true) {

            $data = array(
                'id_actividad' => $this->input->post('id_actividad'),
                'descripcion' => $this->input->post('descripcion')
            );
            $this->settings_model->addActividad($data);
            $this->session->set_flashdata('message', $this->lang->line("actividad_updated"));
            redirect("settings/actividad");
        } else if (!$this->settings_model->getActividadByID($obj->actividades[0]->codigo)) {
            $data = array(
                'id_actividad' => $obj->actividades[0]->codigo,
                'descripcion'  => $obj->actividades[0]->descripcion
            );
            $this->settings_model->addActividad($data);
            $this->session->set_flashdata('message', $this->lang->line("actividad_updated"));
            redirect("settings/actividad");
        } else {

            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('add_actividad');
            $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => site_url('settings/actividad'), 'page' => lang('actividad')), array('link' => '#', 'page' => lang('add_actividad')));
            $meta = array('page_title' => lang('add_actividad'), 'bc' => $bc);
            $this->page_construct('settings/add_actividad', $this->data, $meta);
        }
    }

    function edit_actividad($id = NULL)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', $this->lang->line('access_denied'));
            redirect('pos');
        }
        if ($this->input->get('id_actividad')) {
            $id = $this->input->get('id_actividad', TRUE);
        }

        $actividad = $this->settings_model->getActividadByID($id);
        $this->form_validation->set_rules('id_actividad', $this->lang->line("code_actividad"), 'required');
        $this->form_validation->set_rules('descripcion', $this->lang->line("description"), 'required');

        if ($this->form_validation->run() == true) {

            $data = array(
                'id_actividad' => $this->input->post('id_actividad'),
                'descripcion' => $this->input->post('descripcion')
            );
        }

        if ($this->form_validation->run() == true && $this->settings_model->updateActividad($id, $data)) {

            $this->session->set_flashdata('message', $this->lang->line("actividad_updated"));
            redirect("settings/actividad");
        } else {

            $this->data['actividad'] = $actividad;
            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('edit_actividad');
            $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => site_url('settings/actividad'), 'page' => lang('actividad')), array('link' => '#', 'page' => lang('edit_actividad')));
            $meta = array('page_title' => lang('edit_actividad'), 'bc' => $bc);
            $this->page_construct('settings/edit_actividad', $this->data, $meta);
        }
    }


    function delete_actividad($id = NULL)
    {
        if (DEMO) {
            $this->session->set_flashdata('error', $this->lang->line("disabled_in_demo"));
            redirect('pos');
        }

        if ($this->input->get('id_actividad')) {
            $id = $this->input->get('id_actividad', TRUE);
        }

        if ($this->settings_model->deleteActividad($id)) {
            $this->session->set_flashdata('message', lang("actividad_deleted"));
            redirect("settings/actividad");
        }
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
            ->add_column("Actions", "<div class='text-center'><a href='" . site_url('settings/edit_shipping/$1') . "' class='tip' title='Modificar'><i class='fa fa-edit'></i></a> <a href='" . site_url('settings/delete_shipping/$1') . "' class='tip' title='Eliminar'><i class='fa fa-trash-o'></i></a></div>", "id_shipping_method");
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

    function printers()
    {
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('printers');
        $bc = array(array('link' => '#', 'page' => lang('printers')));
        $meta = array('page_title' => lang('printers'), 'bc' => $bc);
        $this->page_construct('settings/printers', $this->data, $meta);
    }

    function get_printers()
    {

        $this->load->library('datatables');
        $this->datatables
            ->select("id, title, type, profile, path, ip_address, port")
            ->from("printers")
            ->add_column("Actions", "<div class='text-center'><a href='" . site_url('settings/edit_printer/$1') . "' class='tip btn btn-warning btn-xs' title='" . $this->lang->line("edit_printer") . "'><i class='fa fa-edit'></i></a> <a href='" . site_url('settings/delete_printer/$1') . "' onClick=\"return confirm('" . $this->lang->line('alert_x_printer') . "')\" class='tip btn btn-danger btn-xs' title='" . $this->lang->line("delete_printer") . "'><i class='fa fa-trash-o'></i></a></div>", "id")
            ->unset_column('id');
        echo $this->datatables->generate();
    }

    function add_printer()
    {

        $this->form_validation->set_rules('title', $this->lang->line("title"), 'required');
        $this->form_validation->set_rules('type', $this->lang->line("type"), 'required');
        $this->form_validation->set_rules('profile', $this->lang->line("profile"), 'required');
        $this->form_validation->set_rules('char_per_line', $this->lang->line("char_per_line"), 'required');
        if ($this->input->post('type') == 'windows') {
            $this->form_validation->set_rules('path', $this->lang->line("path"), 'required|is_unique[printers.path]');
        }

        if ($this->form_validation->run() == true) {

            $data = array(
                'title' => $this->input->post('title'),
                'type' => $this->input->post('type'),
                'profile' => $this->input->post('profile'),
                'char_per_line' => $this->input->post('char_per_line'),
                'path' => $this->input->post('path'),
                'ip_address' => $this->input->post('ip_address'),
                'port' => ($this->input->post('type') == 'network') ? $this->input->post('port') : NULL,
            );
        }

        if ($this->form_validation->run() == true && $cid = $this->settings_model->addPrinter($data)) {

            $this->session->set_flashdata('message', $this->lang->line("printer_added"));
            redirect("settings/printers");
        } else {
            if ($this->input->is_ajax_request()) {
                echo json_encode(array('status' => 'failed', 'msg' => validation_errors()));
                die();
            }

            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('add_printer');
            $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => site_url('settings/printers'), 'page' => lang('printers')), array('link' => '#', 'page' => lang('add_printer')));
            $meta = array('page_title' => lang('add_printer'), 'bc' => $bc);
            $this->page_construct('settings/add_printer', $this->data, $meta);
        }
    }

    function edit_printer($id = NULL)
    {

        if ($this->input->get('id')) {
            $id = $this->input->get('id', TRUE);
        }

        $printer = $this->site->getPrinterByID($id);
        $this->form_validation->set_rules('title', $this->lang->line("title"), 'required');
        $this->form_validation->set_rules('type', $this->lang->line("type"), 'required');
        $this->form_validation->set_rules('profile', $this->lang->line("profile"), 'required');
        $this->form_validation->set_rules('char_per_line', $this->lang->line("char_per_line"), 'required');
        if ($this->input->post('type') == 'network') {
            $this->form_validation->set_rules('ip_address', $this->lang->line("ip_address"), 'required');
            if ($this->input->post('ip_address') != $printer->ip_address) {
                $this->form_validation->set_rules('ip_address', $this->lang->line("ip_address"), 'is_unique[printers.ip_address]');
            }
            $this->form_validation->set_rules('port', $this->lang->line("port"), 'required');
        } else {
            $this->form_validation->set_rules('path', $this->lang->line("path"), 'required');
            if ($this->input->post('path') != $printer->path) {
                $this->form_validation->set_rules('path', $this->lang->line("path"), 'is_unique[printers.path]');
            }
        }

        if ($this->form_validation->run() == true) {
            $data = array(
                'title' => $this->input->post('title'),
                'type' => $this->input->post('type'),
                'profile' => $this->input->post('profile'),
                'char_per_line' => $this->input->post('char_per_line'),
                'path' => $this->input->post('path'),
                'ip_address' => $this->input->post('ip_address'),
                'port' => ($this->input->post('type') == 'network') ? $this->input->post('port') : NULL,
            );
        }

        if ($this->form_validation->run() == true && $this->settings_model->updatePrinter($id, $data)) {

            $this->session->set_flashdata('message', $this->lang->line("printer_updated"));
            redirect("settings/printers");
        } else {

            $this->data['printer'] = $printer;
            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['page_title'] = lang('edit_printer');
            $bc = array(array('link' => site_url('settings'), 'page' => lang('settings')), array('link' => site_url('settings/printers'), 'page' => lang('printers')), array('link' => '#', 'page' => lang('edit_printer')));
            $meta = array('page_title' => lang('edit_printer'), 'bc' => $bc);
            $this->page_construct('settings/edit_printer', $this->data, $meta);
        }
    }

    function delete_printer($id = NULL)
    {
        if (DEMO) {
            $this->session->set_flashdata('error', $this->lang->line("disabled_in_demo"));
            redirect('pos');
        }

        if ($this->input->get('id')) {
            $id = $this->input->get('id', TRUE);
        }

        if ($this->settings_model->deletePrinter($id)) {
            $this->session->set_flashdata('message', lang("printer_deleted"));
            redirect("settings/printers");
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


        $body = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'username' => trim($this->input->post('user')),
            'password' => trim($this->input->post('password')),
            'scope' => '',
            'grant_type' => 'password',
            'authorization_grants' => 'password'
        ];

        $this->httpclient->setOptions(
            array(
                'data' => $body,
                'url' => $authUrl,
            )
        );

        try {
            if ($this->httpclient->post()) {
                $result = json_decode($this->httpclient->getResults());
            } else {
                echo $this->httpclient->getErrorMsg();
            }

            if (isset($result->error_description)) {
                echo "Error: Usuario y/o Contraseña invalida compruebe.";
            } else if ($result->access_token) {
                echo "!!! Usuario y/o Contraseña Validos !!!";
            }
        } catch (\Exception $e) {
            echo "Error: Usuario y/o Contraseña invalida compruebe.";
            exit();
        }
    }

    function getDownloadxml()
    {
        $this->load->library('datatables');
        $pre1 = $this->db->dbprefix('sales');
        $pre2 = $this->db->dbprefix('hacienda_tiketes');
        $sql = " SELECT ht.id_hacienda as id ,ht.xml_sign, ht.xml_hacienda, ht.clave FROM " . $pre1 . " s LEFT JOIN " . $pre2 . " ht ON ht.sale_id = s.id";
        $xmls = $this->db->query($sql);
        $files = array();
        if ($xmls) {
            foreach ($xmls->result() as $items) {
                $hacienda = simplexml_load_string($items->xml_hacienda);
                $firmado = simplexml_load_string($items->xml_sign);

                try {
                    $hacienda->asXml(sys_get_temp_dir() . '/' . $items->id . '_M1_' . $items->clave . '.xml');
                    $firmado->asXml(sys_get_temp_dir() . '/' . $items->id . '_T1_' . $items->clave . '.xml');
                    array_push($files, sys_get_temp_dir() . '/' . $items->id . '_M1_' . $items->clave . '.xml', sys_get_temp_dir() . '/' . $items->id . '_T1_' . $items->clave . '.xml');
                } catch (\Throwable $e) { } catch (Exception $ex) { }
            }
        }
        $pre1 = $this->db->dbprefix('note_credits');
        $pre2 = $this->db->dbprefix('hacienda_cn');
        $sql = " SELECT hcn.id_cn as id_cn ,hcn.xml_sign, hcn.xml_hacienda, hcn.clave FROM " . $pre1 . " cn LEFT JOIN " . $pre2 . " hcn ON hcn.id_cn = cn.id";
        $xmls = $this->db->query($sql);
        if ($xmls) {
            foreach ($xmls->result() as $items) {
                $hacienda = simplexml_load_string($items->xml_hacienda);
                $firmado = simplexml_load_string($items->xml_sign);

                try {
                    $hacienda->asXml(sys_get_temp_dir() . '/' . $items->id_cn . '_M3_' . $items->clave . '.xml');
                    $firmado->asXml(sys_get_temp_dir() . '/' . $items->id_cn . '_T3_' . $items->clave . '.xml');
                    array_push($files, sys_get_temp_dir() . '/' . $items->id_cn . '_M3_' . $items->clave . '.xml', sys_get_temp_dir() . '/' . $items->id_cn . '_T3_' . $items->clave . '.xml');
                } catch (\Throwable $e) { } catch (Exception $ex) { }
            }
        }
        $pre1 = $this->db->dbprefix('fec');
        $pre2 = $this->db->dbprefix('hacienda_fec');
        $sql = " SELECT hfec.sale_id as id ,hfec.xml_sign, hfec.xml_hacienda, hfec.clave FROM " . $pre1 . " fec LEFT JOIN " . $pre2 . " hfec ON hfec.sale_id = fec.id";
        $xmls = $this->db->query($sql);
        if ($xmls) {
            foreach ($xmls->result() as $items) {
                $hacienda = simplexml_load_string($items->xml_hacienda);
                $firmado = simplexml_load_string($items->xml_sign);

                try {
                    $hacienda->asXml(sys_get_temp_dir() . '/' . $items->id . '_M8_' . $items->clave . '.xml');
                    $firmado->asXml(sys_get_temp_dir() . '/' . $items->id . '_T8_' . $items->clave . '.xml');
                    array_push($files, sys_get_temp_dir() . '/' . $items->id . '_M8_' . $items->clave . '.xml', sys_get_temp_dir() . '/' . $items->id . '_T8_' . $items->clave . '.xml');
                } catch (\Throwable $e) { } catch (Exception $ex) { }
            }
        }
        $zipname = 'Backup-' . $this->db->database . '-' . date('Ymdhis') . '-XMLs.rar';
        $zip = new \ZipArchive();
        $zip->open($zipname, \ZipArchive::CREATE);
        foreach ($files as $file) {
            $zip->addFromString($file, file_get_contents($file));
            unlink($file);
        }
        $zip->close();
        ob_clean();
        ob_end_flush();
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . pathinfo($zipname, PATHINFO_BASENAME));
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($zipname));
        readfile($zipname);
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
        ->add_column("Actions", "<div class='text-center'><div class='btn-group'><a href='" . site_url('settings/edit_table/$1') . "' class='tip btn btn-warning btn-xs' title='Editar mesa'><i class='fa fa-edit'></i></a> <a href='" . site_url('settings/delete_table/$1') . "' onClick=\"return confirm('¿Seguro de eliminar mesa?')\" class='tip btn btn-danger btn-xs' title='Mesa eliminada exitosamente'><i class='fa fa-trash-o'></i></a></div></div>", "id_waiting_tables");
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

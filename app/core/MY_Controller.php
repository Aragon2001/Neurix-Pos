<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{

    function __construct()
    {
        parent::__construct();

        $this->_cabeceras_seguridad();
        $this->_cabecera_csrf();

        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();


        $this->Settings = $this->site->getSettings();
        $this->Settings->password_token_test = decrypt_credential($this->Settings->password_token_test ?? '');
        $this->Settings->password_token_prod = decrypt_credential($this->Settings->password_token_prod ?? '');
        // certificado_ced / certificado_pin son el par efectivo: quien firma no elige ambiente,
        // lo hereda de aqui. Los sufijados guardan cada ambiente por separado.
        $certPorAmbiente = property_exists($this->Settings, 'certificado_ced_test');
        $this->Settings->certificado_pin_test = decrypt_credential($this->Settings->certificado_pin_test ?? '');
        $this->Settings->certificado_pin_prod = decrypt_credential($this->Settings->certificado_pin_prod ?? '');
        $this->Settings->certificado_pin      = decrypt_credential($this->Settings->certificado_pin ?? '');
        $this->Settings->smtp_pass            = decrypt_credential($this->Settings->smtp_pass ?? '');
        $this->Settings->mail_client_pass     = decrypt_credential($this->Settings->mail_client_pass ?? '');
        $this->Settings->google_client_secret = decrypt_credential($this->Settings->google_client_secret ?? '');
        $this->Settings->mail_oauth_refresh        = decrypt_credential($this->Settings->mail_oauth_refresh ?? '');
        $this->Settings->mail_client_oauth_refresh = decrypt_credential($this->Settings->mail_client_oauth_refresh ?? '');
        if ($certPorAmbiente) {
            $amb = ($this->Settings->ambiente ?? 'test') === 'prod' ? 'prod' : 'test';
            $this->Settings->certificado_ced = $this->Settings->{'certificado_ced_' . $amb} ?? '';
            $this->Settings->certificado_pin = $this->Settings->{'certificado_pin_' . $amb} ?? '';
        }
        if ($spos_language = $this->input->cookie('spos_language', TRUE)) {
            $this->Settings->selected_language = $spos_language;
            $this->config->set_item('language', $spos_language);
            $this->lang->load('app', $spos_language);
        } else {
            $this->Settings->selected_language = $this->Settings->language;
            $this->config->set_item('language', $this->Settings->language);
            $this->lang->load('app', $this->Settings->language);
        }
        $this->Settings->pin_code = $this->Settings->pin_code ? md5($this->Settings->pin_code) : NULL;
        $this->theme = $this->Settings->theme . '/views/';
        $this->data['assets'] = base_url() . 'themes/' . $this->Settings->theme . '/assets/';
        $this->data['token_accion'] = $this->token_accion();
        $this->data['Settings'] = $this->Settings;
        $this->loggedIn = $this->tec->logged_in();
        $this->data['loggedIn'] = $this->loggedIn;
        $this->data['store'] = $this->site->getStoreByID($this->session->userdata('store_id'));
        $this->data['categories'] = $this->site->getAllCategories();
        $this->Admin = $this->tec->in_group('admin') ? TRUE : NULL;
        $this->data['Admin'] = $this->Admin;
        $this->m = strtolower($this->router->fetch_class());
        $this->v = strtolower($this->router->fetch_method());

        $this->data['m'] = $this->m;
        $this->data['v'] = $this->v;

        /* agregar campos */
        $this->load->dbforge();


        if (!isset($this->Settings->versionPOS) || (int)$this->Settings->versionPOS < 102) { // actualizar al versionPOS final de la ultima migracion al agregar nuevas

        $versionInitial = false;
        if (!$this->db->field_exists('versionPOS', 'settings')) {
            $this->dbforge->add_column('settings', array(
                'versionPOS' => array(
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => '1',
                    'null' => FALSE,
                )
            ));
            $versionInitial = true;
        }

        if ($versionInitial) {

            if (!$this->db->field_exists('enable_layaway', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'enable_layaway' => array(
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('enable_show_tax', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'enable_show_tax' => array(
                        'type' => 'varchar',
                        'constraint' => '10',
                        'default' => 'Impuesto',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('enable_quote', 'settings')) {

                $this->dbforge->add_column('settings', array(
                    'enable_quote' => array(
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('business_name', 'customers')) {
                $this->dbforge->add_column('customers', array(
                    'business_name' => array(
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => '0',
                        'after' => 'name',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('auth_open', 'users')) {
                $this->dbforge->add_column('users', array(
                    'auth_open' => array(
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('enable_auth_open', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'enable_auth_open' => array(
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => '1',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('enable_detail_register', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'enable_detail_register' => array(
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => '1',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('enable_detail_caschier', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'enable_detail_caschier' => array(
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => '1',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('total_cc', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_cc' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '1',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('total_cc', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_cc' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('total_cc_submitted', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_cc_submitted' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('cash_sale', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'cash_sale' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('cc_sale', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'cc_sale' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('total_sales', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_sales' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('total_credits_sales', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_credits_sales' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('tot_exentas_gravadas', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'tot_exentas_gravadas' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('grand_total_sales', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'grand_total_sales' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "1" || $versionInitial) {
            if (!$this->db->field_exists('enable_fastedition', 'settings')) {

                $this->dbforge->add_column('settings', array(
                    'enable_fastedition' => array(
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '2'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "2" || $versionInitial) {
            if (!$this->db->field_exists('footer_apartado', 'settings')) {

                $this->dbforge->add_column('settings', array(
                    'footer_apartado' => array(
                        'type' => 'varchar',
                        'constraint' => 180,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '3'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "3" || $versionInitial) {

            if (!$this->db->field_exists('present_caja', 'products')) {
                $this->dbforge->add_column('products', array(
                    'present_caja' => array(
                        'type' => 'tinyint',
                        'constraint' => 1,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('present_fraccion', 'products')) {
                $this->dbforge->add_column('products', array(
                    'present_fraccion' => array(
                        'type' => 'tinyint',
                        'constraint' => 1,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('caja_fraccionada', 'products')) {
                $this->dbforge->add_column('products', array(
                    'caja_fraccionada' => array(
                        'type' => 'int',
                        'constraint' => 11,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('margen', 'products')) {
                $this->dbforge->add_column('products', array(
                    'margen' => array(
                        'type' => 'decimal',
                        'constraint' => 11, 4,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('qty_fracc', 'product_store_qty')) {
                $this->dbforge->add_column('product_store_qty', array(
                    'qty_fracc' => array(
                        'type' => 'int',
                        'constraint' => 11,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('token_post', 'sales')) {
                $this->dbforge->add_column('sales', array(
                    'token_post' => array(
                        'type' => 'varchar',
                        'constraint' => 60,
                        'unique' => TRUE
                    )
                ));
            }

            if (!$this->db->field_exists('token_post', 'suspended_sales')) {
                $this->dbforge->add_column('suspended_sales', array(
                    'token_post' => array(
                        'type' => 'varchar',
                        'constraint' => 60,
                        'unique' => TRUE
                    )
                ));
            }
            if (!$this->db->field_exists('token_post', 'quotes')) {
                $this->dbforge->add_column('quotes', array(
                    'token_post' => array(
                        'type' => 'varchar',
                        'constraint' => 60,
                        'unique' => TRUE
                    )
                ));
            }
            if (!$this->db->field_exists('token_post', 'layaway')) {
                $this->dbforge->add_column('layaway', array(
                    'token_post' => array(
                        'type' => 'varchar',
                        'constraint' => 60,
                        'unique' => TRUE
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '4'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "4" || $versionInitial) {
            if (!$this->db->field_exists('block_hacienda', 'settings')) {

                $this->dbforge->add_column('settings', array(
                    'block_hacienda' => array(
                        'type' => 'tinyint',
                        'constraint' => 1,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '5'));
        }

        if ($this->Settings->versionPOS == "5" || $versionInitial) {
            if (!$this->db->field_exists('enable_fractions', 'settings')) {

                $this->dbforge->add_column('settings', array(
                    'enable_fractions' => array(
                        'type' => 'tinyint',
                        'constraint' => 1,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '6'));
        }

        if ($this->Settings->versionPOS == "6" || $versionInitial) {
            if (!$this->db->field_exists('esta_fraccionado', 'sale_items')) {

                $this->dbforge->add_column('sale_items', array(
                    'esta_fraccionado' => array(
                        'type' => 'tinyint',
                        'constraint' => 1,
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->table_exists('mov_inventario')) {
                $fields = array(
                    'id_movimiento' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'tipo_mov' => array(
                        'type' => 'TINYINT',
                        'constraint' => '1',
                        'null' => FALSE,
                    ),
                    'descripcion_mov' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '255',
                        'default' => '',
                        'null' => FALSE,
                    ),
                    'quantity_mov' => array(
                        'type' => 'decimal',
                        'constraint' => 11, 4,
                        'null' => FALSE,
                    ),
                    'qty_fracc_mov' => array(
                        'type' => 'decimal',
                        'constraint' => 11, 4,
                        'null' => FALSE,
                    ),
                    'id_product' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => FALSE,
                    ),
                    'id_usuario' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => FALSE,
                    ),
                    'precio_ant' => array(
                        'type' => 'decimal',
                        'constraint' => 11, 5,
                        'null' => FALSE,
                    ),
                    'precio_act' => array(
                        'type' => 'decimal',
                        'constraint' => 11, 5,
                        'null' => FALSE,
                    ),
                );
                //
                $this->dbforge->add_key('id_movimiento', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->add_field("`fecha_mov` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP");
                $this->dbforge->create_table('mov_inventario');
            }

            $this->db->update('settings', array('versionPOS' => '7'));
        }
        if ($this->Settings->versionPOS == "7" || $versionInitial) {
            if (!$this->db->field_exists('cashsalesApart', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'cashsalesApart' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('ccsalesApart', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'ccsalesApart' => array(
                        'type' => 'decimal',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '8'));
        }

        if ($this->Settings->versionPOS == "8" || $versionInitial) {
            $this->db->query('ALTER TABLE `tec_customers` ADD UNIQUE INDEX (`cf2`)');
            $this->db->update('settings', array('versionPOS' => '9'));
        }

        if ($this->Settings->versionPOS == "9" || $versionInitial) {
            if (!$this->db->field_exists('clave', 'documentositems')) {
                $this->dbforge->add_column('documentositems', array(
                    'clave' => array(
                        'type' => 'varchar',
                        'constraint' => '50',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '10'));
        }

        if ($this->Settings->versionPOS == "10" || $versionInitial) {
            if (!$this->db->field_exists('quantity_suggest', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'quantity_suggest' => array(
                        'type' => 'int',
                        'constraint' => '100',
                        'default' => '10',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '11'));
        }

        if ($this->Settings->versionPOS == "11" || $versionInitial) {
            if (!$this->db->field_exists('demo', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'demo' => array(
                        'type' => 'int',
                        'constraint' => '1',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('fe', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'fe' => array(
                        'type' => 'int',
                        'constraint' => '1',
                        'default' => '1',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '12'));
        }

        if ($this->Settings->versionPOS == "12" || $versionInitial) {
            if (!$this->db->table_exists('sales_otros_textos')) {
                $fields = array(
                    'id_otro_texto' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'sale_id' => array(
                        'type' => 'TINYINT',
                        'constraint' => '11',
                        'null' => FALSE,
                    ),
                    'titulo_texto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'default' => '',
                        'null' => FALSE,
                    ),
                    'otrotexto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '255',
                        'default' => '',
                        'null' => FALSE,
                    )
                );
                //
                $this->dbforge->add_key('id_otro_texto', TRUE);
                $this->dbforge->add_key('sale_id', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('sales_otros_textos');
            }
            $this->db->update('settings', array('versionPOS' => '13'));
        }

        if ($this->Settings->versionPOS == "13" || $versionInitial) {
            if (!$this->db->table_exists('suspended_otros_textos')) {
                $fields = array(
                    'id_otro_texto' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'suspend_id' => array(
                        'type' => 'TINYINT',
                        'constraint' => '11',
                        'null' => FALSE,
                    ),
                    'titulo_texto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'default' => '',
                        'null' => FALSE,
                    ),
                    'otrotexto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '255',
                        'default' => '',
                        'null' => FALSE,
                    )
                );
                //
                $this->dbforge->add_key('id_otro_texto', TRUE);
                $this->dbforge->add_key('suspend_id', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('suspended_otros_textos');
            }
            $this->db->update('settings', array('versionPOS' => '14'));
        }

        if ($this->Settings->versionPOS == "14" || $versionInitial) {
            if (!$this->db->table_exists('quotes_otros_textos')) {
                $fields = array(
                    'id_otro_texto' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'quotes_id' => array(
                        'type' => 'TINYINT',
                        'constraint' => '11',
                        'null' => FALSE,
                    ),
                    'titulo_texto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'default' => '',
                        'null' => FALSE,
                    ),
                    'otrotexto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '255',
                        'default' => '',
                        'null' => FALSE,
                    )
                );
                //
                $this->dbforge->add_key('id_otro_texto', TRUE);
                $this->dbforge->add_key('quotes_id', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('quotes_otros_textos');
            }

            if (!$this->db->table_exists('layaway_otros_textos')) {
                $fields = array(
                    'id_otro_texto' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'apartado_id' => array(
                        'type' => 'TINYINT',
                        'constraint' => '11',
                        'null' => FALSE,
                    ),
                    'titulo_texto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'default' => '',
                        'null' => FALSE,
                    ),
                    'otrotexto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '255',
                        'default' => '',
                        'null' => FALSE,
                    )
                );
                //
                $this->dbforge->add_key('id_otro_texto', TRUE);
                $this->dbforge->add_key('apartado_id', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('layaway_otros_textos');
            }

            if (!$this->db->table_exists('note_credits_otros_textos')) {
                $fields = array(
                    'id_otro_texto' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'cn_id' => array(
                        'type' => 'TINYINT',
                        'constraint' => '11',
                        'null' => FALSE,
                    ),
                    'titulo_texto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'default' => '',
                        'null' => FALSE,
                    ),
                    'otrotexto' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '255',
                        'default' => '',
                        'null' => FALSE,
                    )
                );
                //
                $this->dbforge->add_key('id_otro_texto', TRUE);
                $this->dbforge->add_key('cn_id', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('note_credits_otros_textos');
            }
            $this->db->update('settings', array('versionPOS' => '15'));
        }

        if ($this->Settings->versionPOS == "15" || $versionInitial) {
            if (!$this->db->field_exists('propina_enable', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'propina_enable' => array(
                        'type' => 'int',
                        'constraint' => '1',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
                $this->dbforge->add_column('settings', array(
                    'propina_rate' => array(
                        'type' => 'int',
                        'constraint' => '2',
                        'default' => '10',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '16'));
        }

        if ($this->Settings->versionPOS == "16" || $versionInitial) {
            if (!$this->db->field_exists('total_gravadas1', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas1' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto1', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto1' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('total_gravadas2', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas2' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto2', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto2' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas3', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas3' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto3', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto3' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas4', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas4' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto4', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto4' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas5', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas5' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto5', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto5' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas6', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas6' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto6', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto6' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas7', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas7' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto7', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto7' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas8', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas8' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto8', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto8' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas9', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas9' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto9', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto9' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas10', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas10' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto10', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto10' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas11', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas11' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto11', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto11' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas12', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas12' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto12', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto12' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }


            if (!$this->db->field_exists('total_gravadas13', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_gravadas13' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('total_impuesto13', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'total_impuesto13' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->field_exists('ccsalesTips', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'ccsalesTips' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,4',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            if (!$this->db->query("SHOW INDEX FROM tec_hacienda_tiketes WHERE Key_name = 'estatus_hacienda'")->result()) {
                $this->db->query('ALTER TABLE `tec_hacienda_tiketes` ADD KEY (`estatus_hacienda`)');
            }

            if (!$this->db->query("SHOW INDEX FROM tec_hacienda_tiketes WHERE Key_name = 'consecutivo'")->result()) {
                $this->db->query('ALTER TABLE `tec_hacienda_tiketes` ADD KEY (`consecutivo`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_hacienda_cn WHERE Key_name = 'estatus_hacienda'")->result()) {
                $this->db->query('ALTER TABLE `tec_hacienda_cn` ADD KEY (`estatus_hacienda`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_hacienda_cn WHERE Key_name = 'id_cn'")->result()) {
                $this->db->query('ALTER TABLE `tec_hacienda_cn` ADD KEY (`id_cn`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_products WHERE Key_name = 'name'")->result()) {
                $this->db->query('ALTER TABLE `tec_products` ADD KEY (`name`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_sale_items WHERE Key_name = 'sale_id'")->result()) {
                $this->db->query('ALTER TABLE `tec_sale_items` ADD KEY (`sale_id`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_sale_items WHERE Key_name = 'product_id'")->result()) {
                $this->db->query('ALTER TABLE `tec_sale_items` ADD KEY (`product_id`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_sale_items WHERE Key_name = 'product_code'")->result()) {
                $this->db->query('ALTER TABLE `tec_sale_items` ADD KEY (`product_code`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_sale_items WHERE Key_name = 'product_code'")->result()) {
                $this->db->query('ALTER TABLE `tec_sale_items` ADD KEY (`product_code`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_sales WHERE Key_name = 'customer_id'")->result()) {
                $this->db->query('ALTER TABLE `tec_sales` ADD KEY (`customer_id`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_sales WHERE Key_name = 'customer_name'")->result()) {
                $this->db->query('ALTER TABLE `tec_sales` ADD KEY (`customer_name`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_sales WHERE Key_name = 'created_by'")->result()) {
                $this->db->query('ALTER TABLE `tec_sales` ADD KEY (`created_by`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_sales WHERE Key_name = 'store_id'")->result()) {
                $this->db->query('ALTER TABLE `tec_sales` ADD KEY (`store_id`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_sales_otros_textos WHERE Key_name = 'sale_id'")->result()) {
                $this->db->query('ALTER TABLE `tec_sales_otros_textos` ADD KEY (`sale_id`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_customers WHERE Key_name = 'name'")->result()) {
                $this->db->query('ALTER TABLE `tec_customers` ADD KEY (`name`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_customers WHERE Key_name = 'cf1'")->result()) {
                $this->db->query('ALTER TABLE `tec_customers` ADD KEY (`cf1`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_customers WHERE Key_name = 'cf2'")->result()) {
                $this->db->query('ALTER TABLE `tec_customers` ADD KEY (`cf2`)');
            }
            if (!$this->db->query("SHOW INDEX FROM tec_customers WHERE Key_name = 'email'")->result()) {
                $this->db->query('ALTER TABLE `tec_customers` ADD KEY (`email`)');
            }
            $this->dbforge->drop_table('cierres');
            $this->db->update('settings', array('versionPOS' => '17'));
        }


        if ($this->Settings->versionPOS == "17" || $versionInitial) {
            if (!$this->db->field_exists('TotalVentaNeta', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'TotalVentaNeta' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('TotalVenta', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'TotalVenta' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('TotalExento', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'TotalExento' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('TotalGravado', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'TotalGravado' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('TotalMercanciasExentas', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'TotalMercanciasExentas' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('TotalMercanciasGravadas', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'TotalMercanciasGravadas' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('TotalServExentos', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'TotalServExentos' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('TotalServGravados', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'TotalServGravados' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            $this->db->update('settings', array('versionPOS' => '18'));
        }

        if ($this->Settings->versionPOS == "18" || $versionInitial) {
            if (!$this->db->field_exists('enable_btn_pay', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'enable_btn_pay' => array(
                        'type' => 'tinyint',
                        'constraint' => '1',
                        'default' => '1',
                        'null' => FALSE,
                    )
                ));
            }

            $this->db->update('settings', array('versionPOS' => '19'));
        }


        if ($this->Settings->versionPOS == "19" || $versionInitial) {
            $this->db->query("CREATE TRIGGER DeleteDuplicados BEFORE INSERT ON tec_sale_items FOR EACH ROW DELETE FROM tec_sales WHERE
	id IN (
		SELECT * FROM (
		SELECT
			MAX(id)
		FROM
			tec_sales
		WHERE customer_name NOT IN ('Cliente de Paso', 'Cliente de paso', 'Cliente de Contado', 'Cliente de contado') AND `status` = 'due'
		GROUP BY
			total,
			DATE_FORMAT(`date`, '%Y-%m-%d %h'),
			customer_name
		HAVING
			(COUNT(total) > 1) AND
			(COUNT(DATE_FORMAT(`date`, '%Y-%m-%d %h')) > 1) AND
			(COUNT(customer_name) > 1)) AS ids
	)");

            $this->db->query("CREATE TRIGGER DeleteDuplicadositems BEFORE INSERT ON tec_payments FOR EACH ROW DELETE FROM `tec_sale_items` WHERE id IN
	(
		SELECT * FROM(
			SELECT `tec_sale_items`.id FROM `tec_sale_items`
			LEFT JOIN `tec_sales` ON `tec_sales`.id = `tec_sale_items`.`sale_id`
			WHERE `tec_sales`.id IS NULL
		) AS ids
	)");

            $this->db->query("CREATE TRIGGER DeleteDuplicadosPayments BEFORE INSERT ON tec_sale_items FOR EACH ROW DELETE FROM `tec_payments` WHERE id IN
	(
		SELECT * FROM(
			SELECT `tec_payments`.id FROM `tec_payments`
			LEFT JOIN `tec_sales` ON `tec_sales`.id = `tec_payments`.`sale_id`
			WHERE `tec_sales`.id IS NULL
		) AS idss
	)");

            $this->db->update('settings', array('versionPOS' => '20'));
        }

        if ($this->Settings->versionPOS == "20" || $versionInitial) {
            if (!$this->db->field_exists('enable_parquimetro', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'enable_parquimetro' => array(
                        'type' => 'tinyint',
                        'constraint' => '1',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            $this->db->update('settings', array('versionPOS' => '21'));
        }

        if ($this->Settings->versionPOS == "21" || $versionInitial) {
            $this->db->query('ALTER TABLE `tec_sale_items` CHANGE `product_name` `product_name` VARCHAR(120) CHARSET utf8 COLLATE utf8_general_ci NULL');
            $this->db->update('settings', array('versionPOS' => '22'));
        }

        if ($this->Settings->versionPOS == "22" || $versionInitial) {
            if (!$this->db->field_exists('CondicionVenta', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'CondicionVenta' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '3',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('MedioPago', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'MedioPago' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '3',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('CodigoMoneda', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'CodigoMoneda' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '4',
                        'default' => '',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('TipoCambio', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'TipoCambio' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('versionPOS' => '23'));
        }

        if ($this->Settings->versionPOS == "23" || $versionInitial) {

            if (!$this->db->table_exists('tec_deposit')) {
                $this->db->query('create table `tec_deposit` (
                    `id` int (11),
                    `date` timestamp ,
                    `reference` varchar (150),
                    `amount` Decimal (27),
                    `note` varchar (3000),
                    `created_by` varchar (165),
                    `store_id` int (11)
                );');
            }

            $this->db->update('settings', array('versionPOS' => '24'));
        }

        if ($this->Settings->versionPOS == "24" || $versionInitial) {

            if (!$this->db->field_exists('TotalDepositos', 'registers')) {
                $this->dbforge->add_column('registers', array(
                    'TotalDepositos' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            $this->db->update('settings', array('versionPOS' => '25'));
        }

        if ($this->Settings->versionPOS == "25" || $versionInitial) {

            $this->db->update('settings', array('versionPOS' => '26'));
        }

        if ($this->Settings->versionPOS == "26" || $versionInitial) {

            if (!$this->db->field_exists('enablebtn_retiro', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'enablebtn_retiro' => array(
                        'type' => 'tinyint',
                        'constraint' => '1',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
                $this->dbforge->add_column('settings', array(
                    'enablebtn_deposito' => array(
                        'type' => 'int',
                        'constraint' => '10',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            $this->db->update('settings', array('versionPOS' => '27'));
        }

        if ($this->Settings->versionPOS == "27" || $versionInitial) {

            if (!$this->db->field_exists('CondicionImpuesto', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'CondicionImpuesto' => array(
                        'type' => 'varchar',
                        'constraint' => '02',
                        'default' => '00',
                        'null' => FALSE,
                    )
                ));

                $this->dbforge->add_column('documentoshacienda', array(
                    'MontoTotalImpuestoAcreditar' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));

                $this->dbforge->add_column('documentoshacienda', array(
                    'MontoTotalDeGastoAplicable' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,5',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

            $this->db->update('settings', array('versionPOS' => '28'));
        }

        if ($this->Settings->versionPOS == "28" || $versionInitial) {
            if (!$this->db->table_exists('tec_fec')) {
                $this->db->query('create table tec_fec LIKE tec_sales');
            }
            if (!$this->db->table_exists('tec_fecItems')) {
                $this->db->query('create table tec_fec_items LIKE tec_sale_items');
            }
            if (!$this->db->table_exists('tec_payments_fec')) {
                $this->db->query('create table tec_payments_fec LIKE tec_payments');
            }
            if (!$this->db->table_exists('tec_hacienda_fec')) {
                $this->db->query('create table tec_hacienda_fec LIKE tec_hacienda_tiketes');
            }
            if (!$this->db->field_exists('codigo_provincia', 'suppliers')) {
                $this->dbforge->add_column('suppliers', array(
                    'codigo_provincia' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '5',
                        'default' => '',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('codigo_canton', 'suppliers')) {
                $this->dbforge->add_column('suppliers', array(
                    'codigo_canton' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '5',
                        'default' => '',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('codigo_distrito', 'suppliers')) {
                $this->dbforge->add_column('suppliers', array(
                    'codigo_distrito' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '5',
                        'default' => '',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('codigo_barrio', 'suppliers')) {
                $this->dbforge->add_column('suppliers', array(
                    'codigo_barrio' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '5',
                        'default' => '',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('dirreccion', 'suppliers')) {
                $this->dbforge->add_column('suppliers', array(
                    'direccion' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '5',
                        'default' => '',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('actividad_economica', 'suppliers')) {
                $this->dbforge->add_column('suppliers', array(
                    'actividad_economica' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '6',
                        'default' => '',
                        'null' => FALSE,
                    )
                ));
            }
            if (!$this->db->field_exists('type', 'fec_items')) {
                $this->dbforge->add_column('fec_items', array(
                    'type' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '45',
                        'default' => '',
                        'null' => FALSE,
                    )
                ));
            }
            $this->db->update('settings', array('footer_hacienda_nc' => 'Autorizado mediante resolución N° DGT-R-033-2019 del 20 de junio del 2019, de la Dirección General de Tributación Directa. Versión 4.3'));
            $this->db->update('settings', array('footer_hacienda_fe' => 'Autorizado mediante resolución N° DGT-R-033-2019 del 20 de junio del 2019, de la Dirección General de Tributación Directa. Versión 4.3'));
            $this->db->update('settings', array('versionPOS' => '29'));
        }
        if ($this->Settings->versionPOS == "29" || $versionInitial) {
            if ($this->db->field_exists('actividad_economica', 'suppliers')) {
                $this->db->query("ALTER TABLE `tec_suppliers`
                CHANGE `direccion` `direccion` VARCHAR(100) CHARSET utf8 COLLATE utf8_general_ci DEFAULT ''  NOT NULL,
                CHANGE `actividad_economica` `actividad_economica` VARCHAR(6) CHARSET utf8 COLLATE utf8_general_ci DEFAULT ''  NOT NULL;");
            }
            $this->db->update('settings', array('versionPOS' => '30'));
        }

        if ($this->Settings->versionPOS == "30" || $versionInitial) {
            if (!$this->db->field_exists('shipping_method', 'sales')) {
                $this->db->query("ALTER TABLE `tec_suppliers`
                CHANGE `direccion` `direccion` VARCHAR(100) CHARSET utf8 COLLATE utf8_general_ci DEFAULT ''  NOT NULL,
                CHANGE `actividad_economica` `actividad_economica` VARCHAR(6) CHARSET utf8 COLLATE utf8_general_ci DEFAULT ''  NOT NULL;");
            }
            $this->db->update('settings', array('versionPOS' => '31'));
        }
        if ($this->Settings->versionPOS == "31" || $versionInitial) {
            if (!$this->db->table_exists('shipping_method')) {
                $fields = array(
                    'id_shipping_method' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'name' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '100',
                        'default' => '',
                        'null' => FALSE,
                    ),
                );
                //
                $this->dbforge->add_key('id_shipping_method', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('shipping_method');
            }
            if (!$this->db->field_exists('id_shipping_method', 'sales')) {
                // $this->db->save_queries = TRUE;
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('sales')."
                ADD COLUMN `id_shipping_method` INT(11) NULL AFTER `tipo_doc`;");
                // dd($this->db->last_query());
            }

            if (!$this->db->field_exists('is_shipping', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `is_shipping` TINYINT(1) DEFAULT 0  NULL AFTER `default_actividad`	");
            }

            $this->db->update('settings', array('versionPOS' => '32'));
        }
        if ($this->Settings->versionPOS == "32" || $versionInitial) {
            if (!$this->db->field_exists('MontoExoneracion', 'quotes')) {
                $this->db->query("ALTER TABLE " . $this->db->dbprefix('quotes') . "
                ADD COLUMN `MontoExoneracion` DECIMAL(25,5) NULL AFTER `id_actividad`;");
            }
            if (!$this->db->field_exists('PorcentajeExoneracion', 'quotes')) {
                $this->db->query("ALTER TABLE " . $this->db->dbprefix('quotes') . "
                ADD COLUMN `PorcentajeExoneracion` INT(3) NULL AFTER `MontoExoneracion`;");
            }
            if (!$this->db->field_exists('FechaEmisionE', 'quotes')) {
                $this->db->query("ALTER TABLE " . $this->db->dbprefix('quotes') . "
                ADD COLUMN `FechaEmisionE` TIMESTAMP DEFAULT CURRENT_TIMESTAMP  NULL AFTER `PorcentajeExoneracion`;");
            }
            if (!$this->db->field_exists('NombreInstitucionE', 'quotes')) {
                $this->db->query("ALTER TABLE " . $this->db->dbprefix('quotes') . "
                ADD COLUMN `NombreInstitucionE` VARCHAR(255) NULL AFTER `FechaEmisionE`;");
            }
            if (!$this->db->field_exists('NumeroDocumentoE', 'quotes')) {
                $this->db->query("ALTER TABLE " . $this->db->dbprefix('quotes') . "
                ADD COLUMN `NumeroDocumentoE` INT(10) NULL AFTER `NombreInstitucionE`;");
            }
            if (!$this->db->field_exists('TipoDocumentoE', 'quotes')) {
                $this->db->query("ALTER TABLE " . $this->db->dbprefix('quotes') . "
                ADD COLUMN `TipoDocumentoE` INT(2) NULL AFTER `NumeroDocumentoE`;");
            }

            $this->db->update('settings', array('versionPOS' => '34'));
        }
        if ($this->Settings->versionPOS == "34" || $versionInitial) {
            $this->db->update('settings', array('server_lic' => 'firma.facturaexpert.net'));
            if (!$this->db->field_exists('id_tax', 'layaway_items')) {
                $this->db->query("ALTER TABLE " . $this->db->dbprefix('layaway_items') . "
                ADD COLUMN `id_tax` INT(11) NULL AFTER `nc_status`;");
            }
            $this->db->update('settings', array('versionPOS' => '35'));
        }
        if ($this->Settings->versionPOS == "35" || $versionInitial) {
            if (!$this->db->table_exists('waiting_tables')) {
                $fields = array(
                    'id_waiting_tables' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'name' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '100',
                        'default' => '',
                        'null' => FALSE,
                    ),
                );
                //
                $this->dbforge->add_key('id_waiting_tables', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('waiting_tables');
            }
            if (!$this->db->field_exists('id_waiting_tables', 'suspended_sales')) {
                // $this->db->save_queries = TRUE;
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('suspended_sales')."
                ADD COLUMN `id_waiting_tables` INT(11) NULL AFTER `hold_ref`;");
                // dd($this->db->last_query());
            }
            $this->db->update('settings', array('versionPOS' => '36'));
        }

        if ($this->Settings->versionPOS == "36" || $versionInitial) {
            if (!$this->db->table_exists('lista_precios')) {
                $this->db->save_queries = TRUE;
                $fields = array(
                    'id_lista_precios' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'nombre_l_precio' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '255',
                        'default' => '',
                        'null' => FALSE,
                    ),
                    'status_l_precio' => array(
                        'type' => 'TINYINT',
                        'constraint' => 4,
                        'default' => '1',
                        'null' => FALSE,
                    ),
                    'entry_by' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'default' => null,
                        'null' => true,
                    ),
                );
                $this->dbforge->add_key('id_lista_precios', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('lista_precios');
            }
            if (!$this->db->table_exists('product_prices')) {
                $fields = array(
                    'id_product_prices' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'product_id' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => FALSE,
                    ),
                    'price_group_id' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'null' => FALSE,
                    ),
                    'price' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    ),
                    'margen' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '25,4',
                        'default' => '0.0000',
                        'null' => FALSE,
                    ),
                );
                //
                $this->dbforge->add_key('id_product_prices', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('product_prices');
            }

            $this->db->update('settings', array('versionPOS' => '37'));
        }
        if ($this->Settings->versionPOS == "37" || $versionInitial)
        {
            if (!$this->db->field_exists('condicion', 'sales')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('sales')."
                ADD COLUMN `condicion` TINYINT(1) DEFAULT 1  NULL AFTER `id_shipping_method`;");
            }
            if (!$this->db->field_exists('condicion', 'fec')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('fec')."
                ADD COLUMN `condicion` TINYINT(1) DEFAULT 1  NULL AFTER `tipo_doc`;");
            }
            if (!$this->db->field_exists('condicion', 'documentoshacienda')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('documentoshacienda')."
                ADD COLUMN `condicion` TINYINT(1) DEFAULT 1  NULL AFTER `MontoTotalDeGastoAplicable`;");
            }
            $this->db->update('settings', array('versionPOS' => '38'));
        }

        if ($this->Settings->versionPOS == "38" || $versionInitial)
        {
            if (!$this->db->field_exists('status', 'waiting_tables')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('waiting_tables')."
                ADD COLUMN `status` TINYINT(1) DEFAULT 1  NULL AFTER `name`;");
            }
            if (!$this->db->field_exists('entry_by', 'waiting_tables')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('waiting_tables')."
                ADD COLUMN `entry_by` int(11)  NULL AFTER `status`;");
            }
            if (!$this->db->field_exists('multiprice_enabled', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `multiprice_enabled` TINYINT(1) DEFAULT 0  NOT NULL AFTER `propina_enable`;");
            }
            $this->db->update('settings', array('versionPOS' => '39'));
        }
        if ($this->Settings->versionPOS == "39" || $versionInitial)
        {
            if (!$this->db->field_exists('code', 'lista_precios')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('lista_precios')."
                ADD COLUMN `code` varchar(120)  NULL AFTER `status_l_precio`;");
            }
            $this->db->update('settings', array('versionPOS' => '40'));
        }

        if ($this->Settings->versionPOS == "40" || $versionInitial)
        {
            if (!$this->db->field_exists('diskdrive_code', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `diskdrive_code` varchar(100)  NULL AFTER `is_shipping`;");
            }
            $serverIdentifier = base64_encode(md5(gethostname() . php_uname('m') . $_SERVER['DOCUMENT_ROOT']));
            $this->db->update('settings', array('diskdrive_code' => $serverIdentifier));
            $this->db->update('settings', array('versionPOS' => '41'));
        }

        if ($this->Settings->versionPOS == "41" || $versionInitial)
        {
            if (!$this->db->field_exists('enabled_tax_split', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `enabled_tax_split` TINYINT(1) DEFAULT 0  NOT NULL AFTER `diskdrive_code`;");
            }
            $this->db->update('settings', array('versionPOS' => '42')); 
        }

        if ($this->Settings->versionPOS == "42" || $versionInitial)
        {
            if (!$this->db->field_exists('enabled_massive_mail', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `enabled_massive_mail` TINYINT(1) DEFAULT 0  NOT NULL AFTER `enabled_tax_split`;");
            }
            if (!$this->db->field_exists('mail_client_host', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `mail_client_host` varchar(120)  NULL AFTER `enabled_massive_mail`;");
            }
            if (!$this->db->field_exists('mail_client_port', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `mail_client_port` varchar(120)  NULL AFTER `mail_client_host`;");
            }
            if (!$this->db->field_exists('mail_client_tipo', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `mail_client_tipo` varchar(120)  NULL AFTER `mail_client_port`;");
            }
            if (!$this->db->field_exists('mail_client_user', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `mail_client_user` varchar(120)  NULL AFTER `mail_client_tipo`;");
            }
            if (!$this->db->field_exists('mail_client_pass', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `mail_client_pass` varchar(120)  NULL AFTER `mail_client_user`;");
            }
            if (!$this->db->field_exists('is_gmail', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `is_gmail` TINYINT(1) DEFAULT 0  NOT NULL AFTER  `mail_client_user`;");
            }
            $this->db->update('settings', array('versionPOS' => '43'));
        }

        if ($this->Settings->versionPOS == "43" || $versionInitial) {
            if (!$this->db->field_exists('show_categories', 'settings')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('settings')."
                ADD COLUMN `show_categories` TINYINT(1) DEFAULT 1 NOT NULL AFTER `enabled_massive_mail`;");
            }
            // Migrar usuarios de ThemeChineses: tenían POS sin categorías
            $this->db->query("UPDATE ".$this->db->dbprefix('settings')."
                SET show_categories = 0, theme = 'default'
                WHERE theme = 'ThemeChineses'");
            $this->db->update('settings', array('versionPOS' => '44'));
        }

        if ($this->Settings->versionPOS == "44" || $versionInitial) {
            if (!$this->db->field_exists('last_ip_address', 'users')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('users')."
                ADD COLUMN `last_ip_address` VARCHAR(45) NULL DEFAULT NULL AFTER `last_login`;");
            }
            $this->db->update('settings', array('versionPOS' => '45'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "45" || $versionInitial) {
            if (!$this->db->field_exists('avatar', 'users')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('users')."
                ADD COLUMN `avatar` VARCHAR(255) NULL DEFAULT NULL AFTER `last_ip_address`;");
            }
            if (!$this->db->field_exists('gender', 'users')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('users')."
                ADD COLUMN `gender` VARCHAR(1) NULL DEFAULT NULL AFTER `avatar`;");
            }
            $this->db->update('settings', array('versionPOS' => '46'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "46" || $versionInitial) {
            if (!$this->db->field_exists('user_id', 'registers')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('registers')."
                ADD COLUMN `user_id` INT(11) NULL DEFAULT NULL AFTER `store_id`;");
            }
            if (!$this->db->field_exists('date', 'registers')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('registers')."
                ADD COLUMN `date` DATETIME NULL DEFAULT NULL AFTER `user_id`;");
            }
            if (!$this->db->field_exists('closed_at', 'registers')) {
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('registers')."
                ADD COLUMN `closed_at` DATETIME NULL DEFAULT NULL AFTER `date`;");
            }
            $this->db->update('settings', array('versionPOS' => '47'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "47" || $versionInitial) {
            // --- tec_suspended_sales: columnas faltantes usadas en SELECT, WHERE e INSERT ---
            $ss = $this->db->dbprefix('suspended_sales');
            if (!$this->db->field_exists('customer_name', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `customer_name` VARCHAR(150) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('note', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `note` TEXT NULL;");
            if (!$this->db->field_exists('store_id', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `store_id` INT(11) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('status', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `status` VARCHAR(10) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('grand_total', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `grand_total` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('paid', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `paid` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('product_discount', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `product_discount` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('order_discount', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `order_discount` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('order_discount_id', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `order_discount_id` INT(11) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('total_discount', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `total_discount` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('product_tax', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `product_tax` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('order_tax', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `order_tax` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('order_tax_id', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `order_tax_id` INT(11) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('total_tax', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `total_tax` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('total_items', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `total_items` DECIMAL(15,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('total_quantity', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `total_quantity` DECIMAL(15,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('rounding', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `rounding` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('id_actividad', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `id_actividad` INT(11) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('tipo_doc', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `tipo_doc` VARCHAR(2) NULL DEFAULT '04';");
            if (!$this->db->field_exists('id_shipping_method', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `id_shipping_method` INT(11) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('MontoExoneracion', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `MontoExoneracion` DECIMAL(25,5) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('PorcentajeExoneracion', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `PorcentajeExoneracion` INT(3) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('TipoDocumentoE', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `TipoDocumentoE` INT(2) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('NombreInstitucionE', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `NombreInstitucionE` VARCHAR(255) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('NumeroDocumentoE', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `NumeroDocumentoE` INT(10) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('FechaEmisionE', 'suspended_sales'))
                $this->db->query("ALTER TABLE {$ss} ADD COLUMN `FechaEmisionE` TIMESTAMP NULL DEFAULT NULL;");

            // --- tec_payments: columnas faltantes usadas en INSERT y WHERE ---
            $pm = $this->db->dbprefix('payments');
            if (!$this->db->field_exists('paid_by', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `paid_by` VARCHAR(30) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('customer_id', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `customer_id` INT(11) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('cheque_no', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `cheque_no` VARCHAR(60) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('cc_no', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `cc_no` VARCHAR(60) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('gc_no', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `gc_no` VARCHAR(60) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('cc_holder', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `cc_holder` VARCHAR(60) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('cc_month', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `cc_month` VARCHAR(2) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('cc_year', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `cc_year` VARCHAR(4) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('cc_type', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `cc_type` VARCHAR(20) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('cc_cvv2', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `cc_cvv2` VARCHAR(4) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('store_id', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `store_id` INT(11) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('pos_paid', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `pos_paid` DECIMAL(25,4) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('pos_balance', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `pos_balance` DECIMAL(25,4) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('transaction_id', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `transaction_id` VARCHAR(100) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('currency', 'payments'))
                $this->db->query("ALTER TABLE {$pm} ADD COLUMN `currency` VARCHAR(3) NULL DEFAULT NULL;");

            // --- tec_products: columna 'ubicacion' usada en suggestions ---
            if (!$this->db->field_exists('ubicacion', 'products'))
                $this->db->query("ALTER TABLE ".$this->db->dbprefix('products')."
                ADD COLUMN `ubicacion` VARCHAR(100) NULL DEFAULT NULL;");

            $this->db->update('settings', array('versionPOS' => '48'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "48" || $versionInitial) {
            // --- tec_sales: columnas faltantes usadas en INSERT (Pos.php) y SELECT (Reports_model) ---
            $sl = $this->db->dbprefix('sales');
            if (!$this->db->field_exists('total_tax', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `total_tax` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('total_discount', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `total_discount` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('product_tax', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `product_tax` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('product_discount', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `product_discount` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('order_tax_id', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `order_tax_id` INT(11) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('order_discount_id', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `order_discount_id` INT(11) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('total_quantity', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `total_quantity` DECIMAL(15,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('rounding', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `rounding` DECIMAL(25,4) NOT NULL DEFAULT 0;");
            if (!$this->db->field_exists('note', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `note` TEXT NULL;");
            if (!$this->db->field_exists('hold_ref', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `hold_ref` VARCHAR(100) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('MontoExoneracion', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `MontoExoneracion` DECIMAL(25,5) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('PorcentajeExoneracion', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `PorcentajeExoneracion` INT(3) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('TipoDocumentoE', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `TipoDocumentoE` INT(2) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('NombreInstitucionE', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `NombreInstitucionE` VARCHAR(255) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('NumeroDocumentoE', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `NumeroDocumentoE` INT(10) NULL DEFAULT NULL;");
            if (!$this->db->field_exists('FechaEmisionE', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `FechaEmisionE` TIMESTAMP NULL DEFAULT NULL;");

            $this->db->update('settings', array('versionPOS' => '49'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "49" || $versionInitial) {
            if (!$this->db->table_exists('queue')) {
                $this->db->query("CREATE TABLE `{$this->db->dbprefix}queue` (
                    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `type`            VARCHAR(30)  NOT NULL,
                    `payload`         LONGTEXT     NOT NULL,
                    `status`          ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
                    `attempts`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
                    `max_attempts`    TINYINT UNSIGNED NOT NULL DEFAULT 3,
                    `next_attempt_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `done_at`         DATETIME NULL DEFAULT NULL,
                    `last_error`      TEXT NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_status_next` (`status`, `next_attempt_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }
            $this->db->update('settings', array('versionPOS' => '50'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "50" || $versionInitial) {
            if (!$this->db->table_exists('impuestos')) {
                // Estructura idéntica al esquema real (posv) — Hacienda CR v4.4
                $this->db->query("CREATE TABLE `{$this->db->dbprefix}impuestos` (
                    `id_impuesto`          INT(10)       NOT NULL AUTO_INCREMENT,
                    `codigo_impuesto`      VARCHAR(12)   DEFAULT NULL,
                    `codigo_tarifa`        VARCHAR(6)    DEFAULT NULL,
                    `tasa_impuesto`        DECIMAL(17,2) DEFAULT NULL,
                    `descripcion_impuesto` VARCHAR(360)  DEFAULT NULL,
                    `status_impuestos`     VARCHAR(3)    DEFAULT NULL,
                    PRIMARY KEY (`id_impuesto`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                $this->db->query("INSERT INTO `{$this->db->dbprefix}impuestos`
                    (`id_impuesto`,`codigo_impuesto`,`codigo_tarifa`,`tasa_impuesto`,`descripcion_impuesto`,`status_impuestos`) VALUES
                    (1,  '01','08', 13, 'Impuesto al Valor Agregado (13%)',              '1'),
                    (2,  '01','07',  8, 'Impuesto al Valor Agregado (transitorio 8%)',   '1'),
                    (3,  '01','06',  4, 'Impuesto al Valor Agregado (Transitorio 4%)',   '1'),
                    (4,  '01','05',  0, 'Impuesto al Valor Agregado (Transitorio 0%)',   '1'),
                    (5,  '01','04',  4, 'Impuesto al Valor Agregado (Tarifa reducida 4%)','1'),
                    (6,  '01','03',  2, 'Impuesto al Valor Agregado (Tarifa reducida 2%)','1'),
                    (7,  '01','02',  1, 'Impuesto al Valor Agregado (Tarifa reducida 1%)','1'),
                    (8,  '01','01',  0, 'Impuesto al Valor Agregado (Exento)',            '1'),
                    (9,  '02','0',   5, 'Impuesto Selectivo de Consumo (5%)',             '1'),
                    (14, '07','0',   0, 'IVA (calculo especial)',                         '1'),
                    (17, '99','0',   0, 'Otros',                                          '1')");
            }
            $this->db->update('settings', array('versionPOS' => '51'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "51" || $versionInitial) {
            // Agrega columnas faltantes si la tabla fue creada con un esquema antiguo
            if (!$this->db->field_exists('tasa_impuesto', 'impuestos'))
                $this->db->query("ALTER TABLE `{$this->db->dbprefix}impuestos`
                    ADD COLUMN `tasa_impuesto` DECIMAL(17,0) NULL DEFAULT NULL");
            if (!$this->db->field_exists('descripcion_impuesto', 'impuestos'))
                $this->db->query("ALTER TABLE `{$this->db->dbprefix}impuestos`
                    ADD COLUMN `descripcion_impuesto` VARCHAR(360) NULL DEFAULT NULL");
            // Corrige datos si v50 corrió con el mapeo incorrecto (id=8 era 13% en vez de Exento)
            $this->db->query("UPDATE `{$this->db->dbprefix}impuestos`
                SET `codigo_tarifa`='01', `tasa_impuesto`=0, `descripcion_impuesto`='Impuesto al Valor Agregado (Exento)'
                WHERE `id_impuesto`=8 AND `codigo_tarifa`='08'");
            $this->db->update('settings', array('versionPOS' => '52'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "52" || $versionInitial) {
            if (!$this->db->table_exists('ubicaciones')) {
                $this->db->query("CREATE TABLE `{$this->db->dbprefix}ubicaciones` (
                    `id`          INT(10)      NOT NULL AUTO_INCREMENT,
                    `id_producto` INT(10)      NOT NULL,
                    `seccion`     VARCHAR(100) DEFAULT NULL,
                    `tramo`       VARCHAR(100) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_id_producto` (`id_producto`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }
            $this->db->update('settings', array('versionPOS' => '53'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "53" || $versionInitial) {
            $this->db->update('settings', array('versionPOS' => '54'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "54" || $versionInitial) {
            if (!$this->db->field_exists('hora_inicio', 'users'))
                $this->db->query("ALTER TABLE `{$this->db->dbprefix}users`
                    ADD COLUMN `hora_inicio` VARCHAR(10) NULL DEFAULT NULL");
            if (!$this->db->field_exists('hora_fin', 'users'))
                $this->db->query("ALTER TABLE `{$this->db->dbprefix}users`
                    ADD COLUMN `hora_fin` VARCHAR(10) NULL DEFAULT NULL");
            $this->db->update('settings', array('versionPOS' => '55'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "55" || $versionInitial) {
            if (!$this->db->field_exists('sale_id', 'hacienda_tiketes'))
                $this->db->query("ALTER TABLE `{$this->db->dbprefix}hacienda_tiketes`
                    ADD COLUMN `sale_id` INT(11) NULL DEFAULT NULL AFTER `id`,
                    ADD KEY `idx_sale_id` (`sale_id`)");
            if ($this->db->table_exists('hacienda_fec') && !$this->db->field_exists('sale_id', 'hacienda_fec'))
                $this->db->query("ALTER TABLE `{$this->db->dbprefix}hacienda_fec`
                    ADD COLUMN `sale_id` INT(11) NULL DEFAULT NULL AFTER `id`,
                    ADD KEY `idx_sale_id` (`sale_id`)");
            $this->db->update('settings', array('versionPOS' => '56'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "56" || $versionInitial) {
            if (!$this->db->table_exists('audit_log')) {
                $this->db->query("CREATE TABLE `{$this->db->dbprefix}audit_log` (
                    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
                    `user_id`    INT(11)      NOT NULL DEFAULT 0,
                    `user_email` VARCHAR(150) NOT NULL DEFAULT '',
                    `action`     VARCHAR(50)  NOT NULL,
                    `entity`     VARCHAR(30)  NOT NULL,
                    `entity_id`  INT(11)      NOT NULL DEFAULT 0,
                    `detail`     TEXT         NULL,
                    `amount`     DECIMAL(15,4) NOT NULL DEFAULT 0,
                    `ip`         VARCHAR(45)  NOT NULL DEFAULT '',
                    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_entity` (`entity`, `entity_id`),
                    KEY `idx_user`   (`user_id`),
                    KEY `idx_date`   (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }
            $this->db->update('settings', array('versionPOS' => '57'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "57" || $versionInitial) {
            $p  = $this->db->dbprefix;
            // --- tec_purchases + tec_purchase_items (tablas ausentes del schema inicial) ---
            if (!$this->db->table_exists('purchases'))
                $this->db->query("CREATE TABLE `{$p}purchases` (
                    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `date`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `reference`   VARCHAR(100) DEFAULT NULL,
                    `supplier_id` INT(11) DEFAULT NULL,
                    `total`       DECIMAL(25,4) NOT NULL DEFAULT 0,
                    `note`        TEXT,
                    `attachment`  VARCHAR(255) DEFAULT NULL,
                    `created_by`  INT(11) DEFAULT NULL,
                    `store_id`    INT(11) DEFAULT 1,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            if (!$this->db->table_exists('purchase_items'))
                $this->db->query("CREATE TABLE `{$p}purchase_items` (
                    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `purchase_id` INT(11) NOT NULL,
                    `product_id`  INT(11) DEFAULT NULL,
                    `quantity`    DECIMAL(25,4) NOT NULL DEFAULT 1,
                    `unit_price`  DECIMAL(25,4) NOT NULL DEFAULT 0,
                    `subtotal`    DECIMAL(25,4) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`),
                    KEY `purchase_id` (`purchase_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            // --- tec_expenses (tabla ausente del schema inicial) ---
            if (!$this->db->table_exists('expenses'))
                $this->db->query("CREATE TABLE `{$p}expenses` (
                    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `date`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `reference`   VARCHAR(100) DEFAULT NULL,
                    `amount`      DECIMAL(25,4) NOT NULL DEFAULT 0,
                    `note`        TEXT,
                    `attachment`  VARCHAR(255) DEFAULT NULL,
                    `created_by`  INT(11) DEFAULT NULL,
                    `category_id` INT(11) DEFAULT NULL,
                    `store_id`    INT(11) DEFAULT 1,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            // --- tec_hacienda_tiketes: columnas para XML firmado y FK ---
            $ht = "{$p}hacienda_tiketes";
            if (!$this->db->field_exists('id_hacienda', 'hacienda_tiketes'))
                $this->db->query("ALTER TABLE `{$ht}` ADD COLUMN `id_hacienda` INT(11) NULL DEFAULT NULL");
            if (!$this->db->field_exists('xml_sign', 'hacienda_tiketes'))
                $this->db->query("ALTER TABLE `{$ht}` ADD COLUMN `xml_sign` LONGTEXT NULL");
            if (!$this->db->field_exists('xml_hacienda', 'hacienda_tiketes'))
                $this->db->query("ALTER TABLE `{$ht}` ADD COLUMN `xml_hacienda` LONGTEXT NULL");
            if (!$this->db->field_exists('fecha_emision', 'hacienda_tiketes'))
                $this->db->query("ALTER TABLE `{$ht}` ADD COLUMN `fecha_emision` DATETIME NULL DEFAULT NULL");
            // --- tec_hacienda_fec: mismas columnas ---
            if ($this->db->table_exists('hacienda_fec')) {
                $hf = "{$p}hacienda_fec";
                if (!$this->db->field_exists('id_hacienda', 'hacienda_fec'))
                    $this->db->query("ALTER TABLE `{$hf}` ADD COLUMN `id_hacienda` INT(11) NULL DEFAULT NULL");
                if (!$this->db->field_exists('xml_sign', 'hacienda_fec'))
                    $this->db->query("ALTER TABLE `{$hf}` ADD COLUMN `xml_sign` LONGTEXT NULL");
                if (!$this->db->field_exists('xml_hacienda', 'hacienda_fec'))
                    $this->db->query("ALTER TABLE `{$hf}` ADD COLUMN `xml_hacienda` LONGTEXT NULL");
                if (!$this->db->field_exists('fecha_emision', 'hacienda_fec'))
                    $this->db->query("ALTER TABLE `{$hf}` ADD COLUMN `fecha_emision` DATETIME NULL DEFAULT NULL");
            }
            // --- tec_payments: columna reference ---
            if (!$this->db->field_exists('reference', 'payments'))
                $this->db->query("ALTER TABLE `{$p}payments` ADD COLUMN `reference` VARCHAR(100) NULL DEFAULT NULL");
            // --- tec_registers: columnas de cierre de caja y totales ---
            $rg = "{$p}registers";
            foreach (['closed_at' => 'DATETIME NULL DEFAULT NULL',
                      'note' => 'TEXT NULL',
                      'total_cc_slips' => 'DECIMAL(25,4) NOT NULL DEFAULT 0',
                      'total_cc_slips_submitted' => 'DECIMAL(25,4) NOT NULL DEFAULT 0',
                      'total_cheques' => 'DECIMAL(25,4) NOT NULL DEFAULT 0',
                      'total_cheques_submitted' => 'DECIMAL(25,4) NOT NULL DEFAULT 0',
                      'total_cash' => 'DECIMAL(25,4) NOT NULL DEFAULT 0',
                      'total_cash_submitted' => 'DECIMAL(25,4) NOT NULL DEFAULT 0'] as $col => $def)
                if (!$this->db->field_exists($col, 'registers'))
                    $this->db->query("ALTER TABLE `{$rg}` ADD COLUMN `{$col}` {$def}");
            // --- tec_customers y tec_suppliers: campos personalizados ---
            foreach (['customers', 'suppliers'] as $t)
                foreach (['cf1' => "VARCHAR(100) NULL DEFAULT NULL",
                          'cf2' => "VARCHAR(100) NULL DEFAULT NULL"] as $col => $def)
                    if (!$this->db->field_exists($col, $t))
                        $this->db->query("ALTER TABLE `{$p}{$t}` ADD COLUMN `{$col}` {$def}");
            // --- tec_quotes: columnas de totales y cliente ---
            $qt = "{$p}quotes";
            foreach (['customer_name' => "VARCHAR(150) NULL DEFAULT NULL",
                      'total_tax' => 'DECIMAL(25,4) NOT NULL DEFAULT 0',
                      'total_discount' => 'DECIMAL(25,4) NOT NULL DEFAULT 0',
                      'grand_total' => 'DECIMAL(25,4) NOT NULL DEFAULT 0'] as $col => $def)
                if (!$this->db->field_exists($col, 'quotes'))
                    $this->db->query("ALTER TABLE `{$qt}` ADD COLUMN `{$col}` {$def}");
            // --- tec_note_credits: columnas de totales y cliente ---
            if ($this->db->table_exists('note_credits')) {
                $nc = "{$p}note_credits";
                foreach (['customer_name' => "VARCHAR(150) NULL DEFAULT NULL",
                          'total_tax' => 'DECIMAL(25,4) NOT NULL DEFAULT 0',
                          'total_discount' => 'DECIMAL(25,4) NOT NULL DEFAULT 0',
                          'grand_total' => 'DECIMAL(25,4) NOT NULL DEFAULT 0'] as $col => $def)
                    if (!$this->db->field_exists($col, 'note_credits'))
                        $this->db->query("ALTER TABLE `{$nc}` ADD COLUMN `{$col}` {$def}");
            }
            // --- tec_sale_items: columnas para reportes fiscales ---
            $si = "{$p}sale_items";
            foreach (['tax' => 'DECIMAL(25,4) NULL DEFAULT NULL',
                      'unit_of_measurement' => 'VARCHAR(50) NULL DEFAULT NULL',
                      'net_unit_price' => 'DECIMAL(25,4) NULL DEFAULT NULL',
                      'cost' => 'DECIMAL(25,4) NULL DEFAULT NULL'] as $col => $def)
                if (!$this->db->field_exists($col, 'sale_items'))
                    $this->db->query("ALTER TABLE `{$si}` ADD COLUMN `{$col}` {$def}");
            // --- tec_documentoshacienda: columnas de documentos recibidos ---
            if ($this->db->table_exists('documentoshacienda')) {
                $dh = "{$p}documentoshacienda";
                foreach ([
                    'id_documento'         => 'INT(11) NULL DEFAULT NULL',
                    'documento'            => 'MEDIUMTEXT NULL',
                    'nombre_emisor'        => 'VARCHAR(255) NULL DEFAULT NULL',
                    'correo_emisor'        => 'VARCHAR(150) NULL DEFAULT NULL',
                    'tipo_doc_emisor'      => 'VARCHAR(20) NULL DEFAULT NULL',
                    'NumeroCedulaEmisor'   => 'VARCHAR(20) NULL DEFAULT NULL',
                    'TotalFactura'         => 'DECIMAL(25,5) NULL DEFAULT NULL',
                    'MontoTotalImpuesto'   => 'DECIMAL(25,5) NULL DEFAULT NULL',
                    'ConsecutivoDocEmisor' => 'VARCHAR(20) NULL DEFAULT NULL',
                    'FechaEmisionDoc'      => 'DATETIME NULL DEFAULT NULL',
                    'Estatus'              => 'VARCHAR(20) NULL DEFAULT NULL',
                    'CodigoMoneda'         => 'VARCHAR(5) NULL DEFAULT NULL',
                    'TipoCambio'           => 'DECIMAL(15,5) NULL DEFAULT NULL',
                    'Fecha_aceptacion'     => 'DATETIME NULL DEFAULT NULL',
                ] as $col => $def)
                    if (!$this->db->field_exists($col, 'documentoshacienda'))
                        $this->db->query("ALTER TABLE `{$dh}` ADD COLUMN `{$col}` {$def}");
            }
            $this->db->update('settings', array('versionPOS' => '58'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "58" || $versionInitial) {
            $s = $this->db->dbprefix('settings');
            if (!$this->db->field_exists('mailpath', 'settings'))
                $this->db->query("ALTER TABLE `{$s}` ADD COLUMN `mailpath` VARCHAR(255) NULL DEFAULT NULL");
            if (!$this->db->field_exists('cash_drawer_codes', 'settings'))
                $this->db->query("ALTER TABLE `{$s}` ADD COLUMN `cash_drawer_codes` VARCHAR(100) NULL DEFAULT NULL");
            $this->db->update('settings', array('versionPOS' => '59'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "59" || $versionInitial) {
            $sl = $this->db->dbprefix('sales');
            if (!$this->db->field_exists('is_return', 'sales'))
                $this->db->query("ALTER TABLE {$sl} ADD COLUMN `is_return` TINYINT(1) NOT NULL DEFAULT 0");
            $this->db->update('settings', array('versionPOS' => '60'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "60" || $versionInitial) {
            $u = $this->db->dbprefix('users');
            if (!$this->db->field_exists('drawer_pin', 'users'))
                $this->db->query("ALTER TABLE `{$u}` ADD COLUMN `drawer_pin` VARCHAR(255) NULL DEFAULT NULL");
            $this->db->update('settings', array('versionPOS' => '61'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "61" || $versionInitial) {
            // Transacciones SINPE Móvil detectadas por el servicio de vigilancia de Gmail
            // (ver /sinpe-service junto a este repo). email_id es el id del mensaje de Gmail:
            // es la clave de negocio real, evita procesar el mismo correo dos veces.
            if (!$this->db->table_exists('sinpe_transactions')) {
                $fields = array(
                    'id_sinpe_transaction' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'email_id' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '64',
                        'null' => FALSE,
                    ),
                    'comprobante' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '60',
                        'null' => TRUE,
                    ),
                    'nombre' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '150',
                        'null' => TRUE,
                    ),
                    'telefono' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '20',
                        'null' => TRUE,
                    ),
                    'monto' => array(
                        'type' => 'DECIMAL',
                        'constraint' => '12,2',
                        'null' => TRUE,
                    ),
                    'fecha' => array(
                        'type' => 'DATETIME',
                        'null' => TRUE,
                    ),
                    'banco' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '40',
                        'null' => TRUE,
                    ),
                    'descripcion' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '255',
                        'null' => TRUE,
                    ),
                    'estado' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '20',
                        'default' => 'pendiente',
                        'null' => FALSE,
                    ),
                    'sale_id' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'null' => TRUE,
                    ),
                    'created_at' => array(
                        'type' => 'DATETIME',
                        'null' => FALSE,
                    ),
                );
                $this->dbforge->add_key('id_sinpe_transaction', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('sinpe_transactions');

                $st = $this->db->dbprefix('sinpe_transactions');
                $this->db->query("ALTER TABLE `{$st}` ADD UNIQUE KEY `email_id` (`email_id`)");
                $this->db->query("ALTER TABLE `{$st}` ADD INDEX `idx_estado_fecha` (`estado`, `fecha`)");
                $this->db->query("ALTER TABLE `{$st}` ADD INDEX `idx_sale_id` (`sale_id`)");
            }

            // Configuración de la vigilancia SINPE (una sola fila, id = 1).
            // El refresh_token de Gmail se guarda cifrado (ver sinpe-service/crypto.js) —
            // nunca en texto plano, aunque esta tabla no sea de acceso público.
            if (!$this->db->table_exists('sinpe_settings')) {
                $fields = array(
                    'id_sinpe_settings' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => TRUE,
                        'auto_increment' => TRUE
                    ),
                    'gmail_email' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '150',
                        'null' => TRUE,
                    ),
                    'refresh_token_enc' => array(
                        'type' => 'TEXT',
                        'null' => TRUE,
                    ),
                    'banco' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '40',
                        'default' => 'auto',
                        'null' => FALSE,
                    ),
                    'intervalo' => array(
                        'type' => 'INT',
                        'constraint' => 11,
                        'default' => 15,
                        'null' => FALSE,
                    ),
                    'activo' => array(
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 0,
                        'null' => FALSE,
                    ),
                    'last_check' => array(
                        'type' => 'DATETIME',
                        'null' => TRUE,
                    ),
                    'updated_at' => array(
                        'type' => 'DATETIME',
                        'null' => TRUE,
                    ),
                );
                $this->dbforge->add_key('id_sinpe_settings', TRUE);
                $this->dbforge->add_field($fields);
                $this->dbforge->create_table('sinpe_settings');
                $this->db->insert('sinpe_settings', array(
                    'id_sinpe_settings' => 1,
                    'banco' => 'auto',
                    'intervalo' => 15,
                    'activo' => 0,
                ));
            }

            $this->db->update('settings', array('versionPOS' => '62'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "62" || $versionInitial) {
            // Certificado y PIN separados por ambiente: Hacienda entrega una llave distinta
            // para stag y otra para prod, y el PIN casi nunca coincide entre las dos.
            $s = $this->db->dbprefix('settings');
            foreach (array('certificado_ced_test', 'certificado_ced_prod', 'certificado_pin_test', 'certificado_pin_prod') as $col) {
                if (!$this->db->field_exists($col, 'settings'))
                    $this->db->query("ALTER TABLE `{$s}` ADD COLUMN `{$col}` VARCHAR(255) NULL DEFAULT NULL");
            }
            // El certificado que ya estaba configurado pertenece al ambiente activo.
            $amb = ($this->Settings->ambiente === 'prod') ? 'prod' : 'test';
            $this->db->query("UPDATE `{$s}` SET `certificado_ced_{$amb}` = `certificado_ced`, `certificado_pin_{$amb}` = `certificado_pin`"
                . " WHERE `certificado_ced_{$amb}` IS NULL AND `certificado_ced` IS NOT NULL AND `certificado_ced` != ''");
            $this->db->update('settings', array('versionPOS' => '63'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "63" || $versionInitial) {
            // certificado_pin nacio con 50 caracteres, insuficientes para un PIN cifrado
            // largo: con STRICT_TRANS_TABLES el guardado entero falla en vez de truncar.
            $s = $this->db->dbprefix('settings');
            $this->db->query("ALTER TABLE `{$s}` MODIFY `certificado_pin` VARCHAR(255) NULL DEFAULT NULL");
            $this->db->update('settings', array('versionPOS' => '64'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "64" || $versionInitial) {
            // Arranque de la numeracion. El consecutivo se deduce del maximo de las
            // tablas locales; al migrar desde otro sistema esas tablas estan vacias y
            // la numeracion volveria a 1, que Hacienda rechaza por consecutivo repetido.
            $s = $this->db->dbprefix('settings');
            foreach (array('01', '02', '03', '04', '08', '09') as $tipo) {
                if (!$this->db->field_exists('consec_inicial_' . $tipo, 'settings'))
                    $this->db->query("ALTER TABLE `{$s}` ADD COLUMN `consec_inicial_{$tipo}` INT UNSIGNED NOT NULL DEFAULT 0");
            }
            if (!$this->db->field_exists('clave_ultima', 'settings'))
                $this->db->query("ALTER TABLE `{$s}` ADD COLUMN `clave_ultima` VARCHAR(50) NULL DEFAULT NULL");
            $this->db->update('settings', array('versionPOS' => '65'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "65" || $versionInitial) {
            // El numero de comprobante ocupa 10 digitos: hasta 9.999.999.999, fuera
            // del alcance de un INT UNSIGNED (4.294.967.295).
            $s = $this->db->dbprefix('settings');
            foreach (array('01', '02', '03', '04', '08', '09') as $tipo) {
                $this->db->query("ALTER TABLE `{$s}` MODIFY `consec_inicial_{$tipo}` BIGINT UNSIGNED NOT NULL DEFAULT 0");
            }
            $this->db->update('settings', array('versionPOS' => '66'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "66" || $versionInitial) {
            // Puestos de trabajo: que impresora usa cada computadora. QZ Tray corre en
            // la maquina del cajero, asi que la eleccion es de la maquina y no del
            // usuario. device_id lo genera el navegador y sobrevive a un cambio de IP;
            // la IP se guarda para reconocer un equipo que perdio su almacenamiento.
            if (!$this->db->table_exists('pos_workstations')) {
                $this->dbforge->add_field(array(
                    'id' => array('type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE),
                    'device_id'   => array('type' => 'VARCHAR', 'constraint' => 64),
                    'nombre'      => array('type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE),
                    'ip'          => array('type' => 'VARCHAR', 'constraint' => 45,  'null' => TRUE),
                    'qz_printer'  => array('type' => 'VARCHAR', 'constraint' => 150, 'null' => TRUE),
                    'printer_id'  => array('type' => 'INT', 'null' => TRUE),
                    'store_id'    => array('type' => 'INT', 'null' => TRUE),
                    'agente'      => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
                    'ultimo_uso'  => array('type' => 'DATETIME', 'null' => TRUE),
                    'creado'      => array('type' => 'DATETIME', 'null' => TRUE),
                ));
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->create_table('pos_workstations');
                $w = $this->db->dbprefix('pos_workstations');
                $this->db->query("ALTER TABLE `{$w}` ADD UNIQUE KEY `device_id` (`device_id`)");
                $this->db->query("ALTER TABLE `{$w}` ADD KEY `ip` (`ip`)");
            }
            $this->db->update('settings', array('versionPOS' => '67'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "67" || $versionInitial) {
            // Correo: autenticacion por contrasena de aplicacion o por OAuth de Google.
            // Google retiro el acceso con la contrasena normal de la cuenta, asi que sin
            // OAuth (o sin una contrasena de aplicacion) ni se envia ni se lee nada.
            $s = $this->db->dbprefix('settings');
            $nuevas = array(
                'mail_auth'                 => "VARCHAR(20) NOT NULL DEFAULT 'password'",
                'mail_oauth_refresh'        => 'VARCHAR(512) NULL DEFAULT NULL',
                'mail_oauth_email'          => 'VARCHAR(150) NULL DEFAULT NULL',
                'mail_client_auth'          => "VARCHAR(20) NOT NULL DEFAULT 'password'",
                'mail_client_oauth_refresh' => 'VARCHAR(512) NULL DEFAULT NULL',
                'mail_client_oauth_email'   => 'VARCHAR(150) NULL DEFAULT NULL',
                'mail_client_crypto'        => "VARCHAR(10) NOT NULL DEFAULT 'ssl'",
                'mail_client_enabled'       => 'TINYINT(1) NOT NULL DEFAULT 0',
                'mail_client_carpeta'       => "VARCHAR(100) NOT NULL DEFAULT 'INBOX'",
                'google_client_id'          => 'VARCHAR(200) NULL DEFAULT NULL',
                'google_client_secret'      => 'VARCHAR(255) NULL DEFAULT NULL',
            );
            foreach ($nuevas as $col => $tipo) {
                if (!$this->db->field_exists($col, 'settings'))
                    $this->db->query("ALTER TABLE `{$s}` ADD COLUMN `{$col}` {$tipo}");
            }
            // Las contrasenas pasan a guardarse cifradas y ya no caben en el ancho viejo.
            $this->db->query("ALTER TABLE `{$s}` MODIFY `smtp_pass` VARCHAR(255) NULL DEFAULT NULL");
            $this->db->query("ALTER TABLE `{$s}` MODIFY `mail_client_pass` VARCHAR(255) NULL DEFAULT NULL");
            $this->db->query("ALTER TABLE `{$s}` MODIFY `mail_client_port` VARCHAR(10) NULL DEFAULT NULL");
            $this->db->update('settings', array('versionPOS' => '68'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "68" || $versionInitial) {
            // Impresoras que reporta cada equipo. QZ Tray solo las puede enumerar desde
            // el navegador de esa maquina, asi que el POS las manda y quedan guardadas
            // para poder elegirlas despues desde Ajustes, en otra computadora.
            if (!$this->db->field_exists('impresoras', 'pos_workstations')) {
                $w = $this->db->dbprefix('pos_workstations');
                $this->db->query("ALTER TABLE `{$w}` ADD COLUMN `impresoras` TEXT NULL DEFAULT NULL");
            }
            $this->db->update('settings', array('versionPOS' => '69'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "69" || $versionInitial) {
            // ProveedorSistemas es obligatorio en los siete comprobantes de la v4.4
            // (Anexos v4.4, datos del encabezado). Se guarda aparte de cedula_emisor
            // porque puede ser la cedula de un tercero que provee el sistema.
            if (!$this->db->field_exists('cedula_proveedor_sistemas', 'settings')) {
                $s = $this->db->dbprefix('settings');
                $this->db->query("ALTER TABLE `{$s}` ADD COLUMN `cedula_proveedor_sistemas` VARCHAR(20) NULL DEFAULT NULL");
            }
            $this->db->update('settings', array('versionPOS' => '70'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "70" || $versionInitial) {
            // El POS no lleva control de lo enviado a cocina: nada lee estas columnas.
            foreach (array('enviado_cocina', 'qty_enviado') as $columna) {
                if ($this->db->field_exists($columna, 'suspended_items')) {
                    $this->dbforge->drop_column('suspended_items', $columna);
                }
            }
            $this->db->update('settings', array('versionPOS' => '71'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "71" || $versionInitial) {
            // Sin estas dos columnas el consecutivo sale de 12 digitos en vez de
            // 20 y la clave de 42 en vez de 50: Hacienda rechaza el comprobante.
            // El valor correcto esta en los consecutivos ya emitidos (casa matriz
            // 3 + terminal 5 + tipo 2 + numero 10), asi que se recupera de ahi
            // antes de caer al 001/00001 de una instalacion nueva.
            if (!$this->db->field_exists('casa_matriz', 'settings')
                || !$this->db->field_exists('terminal_pos', 'settings')) {

                $s        = $this->db->dbprefix('settings');
                $matriz   = '001';
                $terminal = '00001';

                if ($this->db->table_exists('hacienda_tiketes')) {
                    $t = $this->db->dbprefix('hacienda_tiketes');
                    $previo = $this->db->query(
                        "SELECT consecutivo FROM `{$t}` WHERE CHAR_LENGTH(consecutivo) = 20 ORDER BY id DESC LIMIT 1"
                    )->row();
                    if ($previo) {
                        $matriz   = substr($previo->consecutivo, 0, 3);
                        $terminal = substr($previo->consecutivo, 3, 5);
                    }
                }

                if (!$this->db->field_exists('casa_matriz', 'settings')) {
                    $this->db->query("ALTER TABLE `{$s}` ADD COLUMN `casa_matriz` VARCHAR(3) NOT NULL DEFAULT '001'");
                    $this->db->update('settings', array('casa_matriz' => $matriz));
                }
                if (!$this->db->field_exists('terminal_pos', 'settings')) {
                    $this->db->query("ALTER TABLE `{$s}` ADD COLUMN `terminal_pos` VARCHAR(5) NOT NULL DEFAULT '00001'");
                    $this->db->update('settings', array('terminal_pos' => $terminal));
                }
            }

            $this->db->update('settings', array('versionPOS' => '72'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "72" || $versionInitial) {
            // Los atajos guardan la combinacion de teclas ("F3", "ALT+B"). En una
            // columna numerica MySQL las convierte a 0 y el POS pierde los atajos,
            // asi que ademas de cambiar el tipo hay que reponer el valor perdido.
            $atajos = array(
                'focus_add_item'         => 'F3',
                'finalize_sale'          => 'F4',
                'add_customer'           => 'F6',
                'edit_last_product'      => 'F7',
                'toggle_category_slider' => 'F8',
                'cancel_sale'            => 'F9',
                'suspend_sale'           => 'F10',
                'open_hold_bills'        => 'ALT+S',
                'today_sale'             => 'ALT+V',
                'close_register'         => 'ALT+R',
            );
            $s = $this->db->dbprefix('settings');
            $tipos = array();
            foreach ($this->db->field_data('settings') as $col) {
                $tipos[$col->name] = strtolower($col->type);
            }
            foreach ($atajos as $campo => $defecto) {
                if (!isset($tipos[$campo]) || strpos($tipos[$campo], 'char') !== FALSE) {
                    continue;
                }
                $this->db->query("ALTER TABLE `{$s}` MODIFY `{$campo}` VARCHAR(20) NULL DEFAULT NULL");
                $this->db->update('settings', array($campo => $defecto));
            }

            if ($this->db->field_exists('print_order', 'settings')) {
                $this->dbforge->drop_column('settings', 'print_order');
            }

            $this->db->update('settings', array('versionPOS' => '73'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "73" || $versionInitial) {
            // Cada caja imprime por QZ Tray con la impresora que elige su propio
            // navegador: no hay impresoras registradas en el servidor.
            $this->dbforge->drop_table('printers', TRUE);
            $this->db->update('settings', array('versionPOS' => '74'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "74" || $versionInitial) {
            // El POS ya no tiene boton de imprimir la cuenta en curso: su atajo
            // apuntaba a un elemento inexistente.
            if ($this->db->field_exists('print_bill', 'settings')) {
                $this->dbforge->drop_column('settings', 'print_bill');
            }
            $this->db->update('settings', array('versionPOS' => '75'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "75" || $versionInitial) {
            // El codigo de actividad de Hacienda son 6 caracteres y admite punto
            // ("4711.2"): en una columna int se truncaba y el XML salia invalido.
            $this->db->query("ALTER TABLE {$this->db->dbprefix('settings')} MODIFY default_actividad VARCHAR(10) NULL");
            $this->db->query("ALTER TABLE {$this->db->dbprefix('sales')} MODIFY id_actividad VARCHAR(10) NULL");
            $this->db->update('settings', array('versionPOS' => '76'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "76" || $versionInitial) {
            // TipoCambio es obligatorio en el resumen y no admite vacio: facturando
            // en colones vale 1, y con otra moneda es su tipo de cambio al colon.
            if (!$this->db->field_exists('value_changue', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'value_changue' => array('type' => 'DECIMAL', 'constraint' => '14,5', 'null' => FALSE, 'default' => 1)
                ));
            }
            $this->db->update('settings', array('versionPOS' => '77'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "77" || $versionInitial) {
            // Receptor/Ubicacion del comprobante (Anexos v4.4): Provincia, Canton y
            // Distrito son obligatorios dentro del nodo, Barrio es opcional y
            // OtrasSenas admite 250 caracteres.
            $columnas_cliente = array(
                'codigo_provincia'       => array('type' => 'VARCHAR', 'constraint' => 5,   'null' => TRUE),
                'codigo_canton'          => array('type' => 'VARCHAR', 'constraint' => 5,   'null' => TRUE),
                'codigo_distrito'        => array('type' => 'VARCHAR', 'constraint' => 5,   'null' => TRUE),
                'codigo_barrio'          => array('type' => 'VARCHAR', 'constraint' => 5,   'null' => TRUE),
                'otras_senas'            => array('type' => 'VARCHAR', 'constraint' => 250, 'null' => TRUE),
                'otras_senas_extranjero' => array('type' => 'VARCHAR', 'constraint' => 300, 'null' => TRUE),
                'cod_telefono'           => array('type' => 'VARCHAR', 'constraint' => 3,   'null' => TRUE, 'default' => '506'),
                'notas'                  => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            );
            foreach ($columnas_cliente as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'customers')) {
                    $this->dbforge->add_column('customers', array($campo => $definicion));
                }
            }
            // customers.cf2 es UNIQUE y MySQL solo admite NULL repetido: con cadena
            // vacia el segundo cliente sin identificacion choca contra el indice.
            $this->db->query("UPDATE {$this->db->dbprefix('customers')} SET cf2 = NULL WHERE cf2 = ''");
            $this->db->update('settings', array('versionPOS' => '78'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "78" || $versionInitial) {
            // Catalogo de tarifas de IVA de la v4.4. El codigo 01 no es "exento":
            // es la tarifa 0% del articulo 32 del RLIVA, que Hacienda cuenta como
            // NO SUJETA. La exenta es la 10, y faltaban tambien la 09 y la 11.
            $impuestos = $this->db->dbprefix('impuestos');

            // id_impuesto = 0 es el centinela de "sin impuesto" en todo el POS, asi
            // que ninguna tarifa real puede ocupar ese id.
            if ($this->db->query("SELECT 1 FROM {$impuestos} WHERE id_impuesto = 0")->num_rows() > 0) {
                $tope = (int) $this->db->query("SELECT MAX(id_impuesto) AS tope FROM {$impuestos}")->row()->tope;
                $this->db->query("UPDATE {$impuestos} SET id_impuesto = " . ($tope + 1) . " WHERE id_impuesto = 0");
            }
            // La tabla nacio sin AUTO_INCREMENT: un INSERT sin id cae en 0 y el
            // siguiente choca contra la primaria. La tarifa reducida de 0.5% ademas
            // necesita decimales, que un DECIMAL(17,0) redondea a 1.
            $this->db->query("ALTER TABLE {$impuestos}
                MODIFY `tasa_impuesto` DECIMAL(17,2) NULL DEFAULT NULL,
                MODIFY `id_impuesto`   INT(10) NOT NULL AUTO_INCREMENT");

            $this->db->query("UPDATE {$impuestos} SET descripcion_impuesto = 'Impuesto al Valor Agregado (Tarifa 0%, art. 32 RLIVA - no sujeto)' WHERE codigo_impuesto = '01' AND codigo_tarifa = '01'");
            $this->db->query("UPDATE {$impuestos} SET tasa_impuesto = 0.5 WHERE codigo_impuesto = '01' AND codigo_tarifa = '09'");
            $faltantes = array(
                array('codigo_impuesto' => '01', 'codigo_tarifa' => '09', 'tasa_impuesto' => 0.5, 'descripcion_impuesto' => 'Impuesto al Valor Agregado (Tarifa reducida 0.5%)', 'status_impuestos' => 1),
                array('codigo_impuesto' => '01', 'codigo_tarifa' => '10', 'tasa_impuesto' => 0,   'descripcion_impuesto' => 'Impuesto al Valor Agregado (Tarifa Exenta)',       'status_impuestos' => 1),
                array('codigo_impuesto' => '01', 'codigo_tarifa' => '11', 'tasa_impuesto' => 0,   'descripcion_impuesto' => 'Impuesto al Valor Agregado (Tarifa 0% sin derecho a credito)', 'status_impuestos' => 1),
            );
            foreach ($faltantes as $fila) {
                $ya = $this->db->get_where('impuestos', array('codigo_impuesto' => $fila['codigo_impuesto'], 'codigo_tarifa' => $fila['codigo_tarifa']));
                if ($ya->num_rows() === 0) {
                    $this->db->insert('impuestos', $fila);
                }
            }
            $this->db->update('settings', array('versionPOS' => '79'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "79" || $versionInitial) {
            // El CABYS de un articulo rapido no tiene ficha de producto donde
            // vivir: sin esta columna se pierde y la linea sale sin CodigoCABYS.
            if (!$this->db->field_exists('cabys', 'sale_items')) {
                $this->dbforge->add_column('sale_items', array(
                    'cabys' => array('type' => 'VARCHAR', 'constraint' => 13, 'null' => TRUE)
                ));
            }
            $this->db->update('settings', array('versionPOS' => '80'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "80" || $versionInitial) {
            // La carga de facturas de compra escribe columnas que la tabla no
            // tenia: el INSERT fallaba entero y el reporte D-101 consultaba
            // dci.unit_of_measurement, que tampoco existia.
            $cols_doc = array(
                'ClaveDocEmisor'      => array('type' => 'VARCHAR', 'constraint' => 50,  'null' => TRUE),
                'telefono_emisor'     => array('type' => 'VARCHAR', 'constraint' => 30,  'null' => TRUE),
                'Mensaje'             => array('type' => 'TEXT',    'null' => TRUE),
                'DetalleMensaje'      => array('type' => 'TEXT',    'null' => TRUE),
                'xml_compra'          => array('type' => 'LONGTEXT','null' => TRUE),
                'xml_mensajereceptor' => array('type' => 'LONGTEXT','null' => TRUE),
                'store_id'            => array('type' => 'INT', 'constraint' => 11, 'null' => TRUE),
            );
            foreach ($cols_doc as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'documentoshacienda')) {
                    $this->dbforge->add_column('documentoshacienda', array($campo => $definicion));
                }
            }

            $cols_item = array(
                'code'                => array('type' => 'VARCHAR', 'constraint' => 50,  'null' => TRUE),
                'consecutivo'         => array('type' => 'VARCHAR', 'constraint' => 20,  'null' => TRUE),
                'name'                => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
                'cost'                => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
                'type'                => array('type' => 'VARCHAR', 'constraint' => 20,  'null' => TRUE),
                'unit_of_measurement' => array('type' => 'VARCHAR', 'constraint' => 10,  'null' => TRUE),
                'precio_unitario'     => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
                'tarifa_impuesto'     => array('type' => 'DECIMAL', 'constraint' => '12,2', 'null' => TRUE),
                'monto_impuesto'      => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
                'monto_descuento'     => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
                'SubTotal'            => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
                'MontoTotalLinea'     => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
            );
            foreach ($cols_item as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'documentositems')) {
                    $this->dbforge->add_column('documentositems', array($campo => $definicion));
                }
            }

            // El documento se identifica por id_documento en todo el modulo, y
            // las filas viejas lo tienen vacio.
            $dh = $this->db->dbprefix('documentoshacienda');
            $this->db->query("UPDATE `{$dh}` SET id_documento = id WHERE id_documento IS NULL");

            $this->db->update('settings', array('versionPOS' => '81'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "81" || $versionInitial) {
            // La bitacora de inventario guardaba la cantidad del movimiento pero
            // no el saldo, asi que no se podia reconstruir la existencia de una
            // fecha ni saber de que tienda era el movimiento.
            $cols_mov = array(
                'qty_antes'   => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'qty_despues' => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'store_id'    => array('type' => 'INT', 'constraint' => 11, 'null' => FALSE, 'default' => 1),
                'id_sesion'   => array('type' => 'VARCHAR', 'constraint' => 40, 'null' => TRUE),
                'costo_ant'   => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
                'costo_act'   => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
            );
            foreach ($cols_mov as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'mov_inventario')) {
                    $this->dbforge->add_column('mov_inventario', array($campo => $definicion));
                }
            }

            // `descripcion_mov` no admite NULL y el movimiento puede no tener motivo.
            $mv = $this->db->dbprefix('mov_inventario');
            $this->db->query("ALTER TABLE `{$mv}` MODIFY `descripcion_mov` VARCHAR(255) NOT NULL DEFAULT ''");
            $this->db->query("ALTER TABLE `{$mv}` MODIFY `quantity_mov`  DECIMAL(25,4) NOT NULL DEFAULT 0");
            $this->db->query("ALTER TABLE `{$mv}` MODIFY `qty_fracc_mov` DECIMAL(25,4) NOT NULL DEFAULT 0");
            $this->db->query("ALTER TABLE `{$mv}` MODIFY `precio_ant`    DECIMAL(25,5) NOT NULL DEFAULT 0");
            $this->db->query("ALTER TABLE `{$mv}` MODIFY `precio_act`    DECIMAL(25,5) NOT NULL DEFAULT 0");

            $this->db->update('settings', array('versionPOS' => '82'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "82" || $versionInitial) {
            // El envio del acuse de recepcion marca el documento como enviado en
            // esta columna, que la tabla no tenia.
            if (!$this->db->field_exists('mail', 'documentoshacienda')) {
                $this->dbforge->add_column('documentoshacienda', array(
                    'mail' => array('type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 0)
                ));
            }
            $this->db->update('settings', array('versionPOS' => '83'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "83" || $versionInitial) {
            // El proveedor es el receptor de la factura electronica de compra:
            // necesita ubicacion completa, y `direccion` se quedaba en 100
            // caracteres cuando el XML admite 250.
            $cols_prov = array(
                'otras_senas'        => array('type' => 'VARCHAR', 'constraint' => 250, 'null' => TRUE),
                'codigo_pais_tel'    => array('type' => 'VARCHAR', 'constraint' => 3,  'null' => FALSE, 'default' => '506'),
                'contacto_nombre'    => array('type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE),
                'contacto_telefono'  => array('type' => 'VARCHAR', 'constraint' => 20,  'null' => TRUE),
                'plazo_pago_dias'    => array('type' => 'INT', 'constraint' => 11, 'null' => FALSE, 'default' => 0),
                'medio_pago_habitual'=> array('type' => 'VARCHAR', 'constraint' => 2,  'null' => TRUE),
                'cuenta_iban'        => array('type' => 'VARCHAR', 'constraint' => 34, 'null' => TRUE),
                'sinpe_telefono'     => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => TRUE),
                'moneda'             => array('type' => 'VARCHAR', 'constraint' => 3,  'null' => FALSE, 'default' => 'CRC'),
                'notas'              => array('type' => 'TEXT', 'null' => TRUE),
                'activo'             => array('type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 1),
                'created_at'         => array('type' => 'DATETIME', 'null' => TRUE),
                'updated_at'         => array('type' => 'DATETIME', 'null' => TRUE),
                'created_by'         => array('type' => 'INT', 'constraint' => 11, 'null' => TRUE),
            );
            foreach ($cols_prov as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'suppliers')) {
                    $this->dbforge->add_column('suppliers', array($campo => $definicion));
                }
            }

            $sp = $this->db->dbprefix('suppliers');
            $this->db->query("UPDATE `{$sp}` SET otras_senas = direccion WHERE otras_senas IS NULL AND direccion <> ''");

            // Una cedula repetida deja que la factura de compra elija proveedor al azar.
            $this->db->query("UPDATE `{$sp}` SET cf2 = NULL WHERE cf2 = ''");
            $repetidas = $this->db->query("SELECT cf2 FROM `{$sp}` WHERE cf2 IS NOT NULL AND deleted = 0 GROUP BY cf2 HAVING COUNT(*) > 1")->result();
            if (!$repetidas) {
                $this->db->query("ALTER TABLE `{$sp}` ADD UNIQUE KEY `cf2_unica` (`cf2`)");
            } else {
                log_message('error', '[Migracion 84] hay cedulas de proveedor repetidas: el indice unico no se creo.');
            }

            $this->db->update('settings', array('versionPOS' => '84'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "84" || $versionInitial) {
            // La compra guardaba costo y cantidad y nada mas: sin el IVA por
            // linea no hay con que conciliar el D-104, y sin vencimiento no hay
            // cuentas por pagar.
            $cols_compra = array(
                'status'              => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => FALSE, 'default' => 'pendiente'),
                'payment_status'      => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => FALSE, 'default' => 'pendiente'),
                'due_date'            => array('type' => 'DATE', 'null' => TRUE),
                'tax_total'           => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'discount_total'      => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'currency'            => array('type' => 'VARCHAR', 'constraint' => 3, 'null' => FALSE, 'default' => 'CRC'),
                'exchange_rate'       => array('type' => 'DECIMAL', 'constraint' => '25,6', 'null' => FALSE, 'default' => 1),
                'supplier_invoice_no' => array('type' => 'VARCHAR', 'constraint' => 50, 'null' => TRUE),
            );
            foreach ($cols_compra as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'purchases')) {
                    $this->dbforge->add_column('purchases', array($campo => $definicion));
                }
            }

            $cols_linea = array(
                'tax_code'          => array('type' => 'VARCHAR', 'constraint' => 2, 'null' => TRUE),
                'tax_rate'          => array('type' => 'DECIMAL', 'constraint' => '12,2', 'null' => FALSE, 'default' => 0),
                'tax_amount'        => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'quantity_received' => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
            );
            foreach ($cols_linea as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'purchase_items')) {
                    $this->dbforge->add_column('purchase_items', array($campo => $definicion));
                }
            }

            // Las compras ya registradas se dan por recibidas: es lo que
            // significaba `received` antes de que hubiera estados.
            $pc = $this->db->dbprefix('purchases');
            $this->db->query("UPDATE `{$pc}` SET status = 'recibida' WHERE received = 1 AND status = 'pendiente'");

            $this->db->update('settings', array('versionPOS' => '85'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "85" || $versionInitial) {
            // Un POS con varios cajeros necesita saber quien es cada uno y
            // obligar el cambio de la contrasena que le puso el administrador.
            $cols_user = array(
                'cedula'               => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => TRUE),
                'must_change_password' => array('type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 0),
                'password_changed_at'  => array('type' => 'DATETIME', 'null' => TRUE),
                'notes'                => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
                'created_by'           => array('type' => 'INT', 'constraint' => 11, 'null' => TRUE),
            );
            foreach ($cols_user as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'users')) {
                    $this->dbforge->add_column('users', array($campo => $definicion));
                }
            }
            $this->db->update('settings', array('versionPOS' => '86'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "86" || $versionInitial) {
            // La nota de credito nunca pudo crearse: Pos::nota_credito inserta
            // trece columnas que la tabla no tiene y siete en las lineas, asi que
            // el INSERT moria entero. Sin nota de credito no hay forma legal de
            // anular una factura aceptada, que es inmutable.
            $cols_nc = array(
                'type_nc'           => array('type' => 'VARCHAR', 'constraint' => 2, 'null' => TRUE),
                'product_discount'  => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'order_discount_id' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => TRUE),
                'order_discount'    => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'product_tax'       => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'order_tax_id'      => array('type' => 'INT', 'constraint' => 11, 'null' => TRUE),
                'order_tax'         => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'total_items'       => array('type' => 'DECIMAL', 'constraint' => '15,4', 'null' => FALSE, 'default' => 0),
                'total_quantity'    => array('type' => 'DECIMAL', 'constraint' => '15,4', 'null' => FALSE, 'default' => 0),
                'rounding'          => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'status'            => array('type' => 'VARCHAR', 'constraint' => 10, 'null' => TRUE),
                'note'              => array('type' => 'TEXT', 'null' => TRUE),
                'hold_ref'          => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
            );
            foreach ($cols_nc as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'note_credits')) {
                    $this->dbforge->add_column('note_credits', array($campo => $definicion));
                }
            }

            $cols_nci = array(
                'net_unit_price'  => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
                'comment'         => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE),
                'item_discount'   => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'subtotal'        => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                'real_unit_price' => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
                'cost'            => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => TRUE),
                'cabys'           => array('type' => 'VARCHAR', 'constraint' => 13, 'null' => TRUE),
            );
            foreach ($cols_nci as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'note_credits_items')) {
                    $this->dbforge->add_column('note_credits_items', array($campo => $definicion));
                }
            }

            // Anular una factura mueve dinero, existencias y contabilidad. Esta
            // tabla guarda quien lo hizo, cuando, por que, si salio plata de la
            // caja y si se abrio el cajon para entregarla.
            if (!$this->db->table_exists('sale_anulaciones')) {
                $this->dbforge->add_field(array(
                    'id'              => array('type' => 'INT', 'constraint' => 11, 'unsigned' => TRUE, 'auto_increment' => TRUE),
                    'sale_id'         => array('type' => 'INT', 'constraint' => 11, 'null' => FALSE),
                    'cn_id'           => array('type' => 'INT', 'constraint' => 11, 'null' => TRUE),
                    // 'fiscal' = se emitio nota de credito · 'interna' = el comprobante
                    // nunca fue aceptado, asi que no hay nada que decirle a Hacienda.
                    'tipo'            => array('type' => 'VARCHAR', 'constraint' => 10, 'null' => FALSE, 'default' => 'interna'),
                    'codigo_ref'      => array('type' => 'VARCHAR', 'constraint' => 2, 'null' => TRUE),
                    'motivo'          => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => FALSE),
                    'devuelve_dinero' => array('type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 0),
                    'monto_devuelto'  => array('type' => 'DECIMAL', 'constraint' => '25,4', 'null' => FALSE, 'default' => 0),
                    'medio_devolucion' => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => TRUE),
                    'pin_verificado'  => array('type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 0),
                    'cajon_abierto'   => array('type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 0),
                    'register_id'     => array('type' => 'INT', 'constraint' => 11, 'null' => TRUE),
                    'store_id'        => array('type' => 'INT', 'constraint' => 11, 'null' => FALSE, 'default' => 1),
                    'created_by'      => array('type' => 'INT', 'constraint' => 11, 'null' => TRUE),
                    'created_at'      => array('type' => 'DATETIME', 'null' => FALSE),
                    'ip'              => array('type' => 'VARCHAR', 'constraint' => 45, 'null' => TRUE),
                ));
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->add_key('sale_id');
                $this->dbforge->add_key('register_id');
                $this->dbforge->add_key('created_at');
                $this->dbforge->create_table('sale_anulaciones');
            }

            $this->db->update('settings', array('versionPOS' => '87'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "86" || $versionInitial) {
            // Las credenciales guardadas usaban AES-256-CBC con un IV fijo para
            // todas: se regraban con AES-256-GCM e IV por valor.
            $campos = array(
                'password_token_test', 'password_token_prod',
                'certificado_pin', 'certificado_pin_test', 'certificado_pin_prod',
                'smtp_pass', 'mail_client_pass', 'google_client_secret',
                'mail_oauth_refresh', 'mail_client_oauth_refresh',
            );

            $fila = $this->db->get('settings')->row_array();
            $regrabar = array();
            foreach ($campos as $campo) {
                if (!isset($fila[$campo]) || !credencial_es_antigua($fila[$campo])) {
                    continue;
                }
                $claro = decrypt_credential($fila[$campo]);
                if ($claro !== '') {
                    $regrabar[$campo] = encrypt_credential($claro);
                }
            }
            if ($regrabar) {
                // CI exige un WHERE en update(): sin el, la sentencia no sale.
                $this->db->update('settings', $regrabar, array('setting_id' => $fila['setting_id']));
                log_message('info', '[Cripto] credenciales regrabadas: ' . implode(', ', array_keys($regrabar)));
            }

            $this->db->update('settings', array('versionPOS' => '87'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "87" || $versionInitial) {
            // Tope de descuento por linea, comprobado en el servidor. 100 deja
            // el comportamiento anterior: el negocio decide cuanto bajar.
            if (!$this->db->field_exists('tope_descuento', 'settings')) {
                $this->dbforge->add_column('settings', array(
                    'tope_descuento' => array('type' => 'DECIMAL', 'constraint' => '5,2', 'null' => FALSE, 'default' => 100)
                ));
            }
            $this->db->update('settings', array('versionPOS' => '88'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "88" || $versionInitial) {
            // Lo que el cobro necesita saber del cliente y no tenia donde vivir:
            // el plazo que alimenta <PlazoCredito> y con que comprobante y forma
            // de pago abre el modal.
            $cols_cli = array(
                'dias_credito'      => array('type' => 'INT', 'constraint' => 11, 'null' => FALSE, 'default' => 0),
                'tipo_pago_defecto' => array('type' => 'VARCHAR', 'constraint' => 10, 'null' => TRUE),
                'tipo_doc_defecto'  => array('type' => 'VARCHAR', 'constraint' => 2,  'null' => TRUE),
                'codigo_cliente'    => array('type' => 'VARCHAR', 'constraint' => 30, 'null' => TRUE),
                'whatsapp'          => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => TRUE),
            );
            foreach ($cols_cli as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'customers')) {
                    $this->dbforge->add_column('customers', array($campo => $definicion));
                }
            }
            $this->db->update('settings', array('versionPOS' => '89'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "89" || $versionInitial) {
            // Posicion 42 de la clave: 1 normal, 2 contingencia, 3 sin internet.
            // Una factura emitida en contingencia hay que reenviarla cuando
            // vuelve el internet, y sin esta columna no habria como saber cuales.
            if (!$this->db->field_exists('situacion', 'sales')) {
                $this->dbforge->add_column('sales', array(
                    'situacion' => array('type' => 'VARCHAR', 'constraint' => 1, 'null' => FALSE, 'default' => '1')
                ));
            }
            $this->db->update('settings', array('versionPOS' => '90'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "90" || $versionInitial) {
            // El padron devuelve todas las actividades inscritas del cliente y
            // el formulario solo guardaba la elegida: al facturar con otra habia
            // que volver a consultar.
            if (!$this->db->table_exists('customer_actividades')) {
                $ca = $this->db->dbprefix('customer_actividades');
                $this->db->query("CREATE TABLE `{$ca}` (
                    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `customer_id` INT(11) NOT NULL,
                    `codigo`      VARCHAR(6) NOT NULL,
                    `descripcion` VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `cliente_codigo` (`customer_id`, `codigo`),
                    KEY `customer_id` (`customer_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // La actividad que ya tenia cada cliente entra como la primera.
                $cu = $this->db->dbprefix('customers');
                $this->db->query("INSERT IGNORE INTO `{$ca}` (customer_id, codigo)
                    SELECT id, codigo_actividad FROM `{$cu}`
                    WHERE codigo_actividad IS NOT NULL AND codigo_actividad <> ''");
            }

            // Una exoneracion es una resolucion vigente del cliente, no un dato
            // que se teclee venta por venta: se guarda aca y se copia al vender.
            $cols_exo = array(
                'exo_tipo_documento' => array('type' => 'VARCHAR', 'constraint' => 2,   'null' => TRUE),
                'exo_numero'         => array('type' => 'VARCHAR', 'constraint' => 40,  'null' => TRUE),
                'exo_institucion'    => array('type' => 'VARCHAR', 'constraint' => 160, 'null' => TRUE),
                'exo_fecha_emision'  => array('type' => 'DATE', 'null' => TRUE),
                'exo_fecha_vence'    => array('type' => 'DATE', 'null' => TRUE),
                'exo_porcentaje'     => array('type' => 'DECIMAL', 'constraint' => '5,2', 'null' => FALSE, 'default' => 0),
            );
            foreach ($cols_exo as $campo => $definicion) {
                if (!$this->db->field_exists($campo, 'customers')) {
                    $this->dbforge->add_column('customers', array($campo => $definicion));
                }
            }

            $this->db->update('settings', array('versionPOS' => '91'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "91" || $versionInitial) {
            // El numero de la resolucion de exoneracion es una cadena de 3 a 40
            // caracteres (ExoneracionType del XSD) y la columna era INT: un
            // numero como "EX-2026-77" se guardaba como 0 y salia asi en el XML.
            $sa = $this->db->dbprefix('sales');
            $this->db->query("ALTER TABLE `{$sa}`
                MODIFY `TipoDocumentoE`   VARCHAR(2)  NULL,
                MODIFY `NumeroDocumentoE` VARCHAR(40) NULL");

            // <NombreInstitucion> no es texto libre: es un codigo de dos digitos
            // del catalogo del anexo. Lo que hubiera escrito no vale como codigo.
            $this->db->query("UPDATE `{$sa}` SET NombreInstitucionE = NULL
                WHERE NombreInstitucionE IS NOT NULL AND NombreInstitucionE NOT REGEXP '^[0-9]{2}$'");
            $this->db->query("ALTER TABLE `{$sa}` MODIFY `NombreInstitucionE` VARCHAR(2) NULL");

            $this->db->update('settings', array('versionPOS' => '92'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "92" || $versionInitial) {
            // `gender` guardaba 'male'/'female' en un VARCHAR(1): MySQL recortaba
            // a 'm'/'f' y el desplegable del perfil no reconocia el valor propio.
            $u = $this->db->dbprefix('users');
            $this->db->query("ALTER TABLE `{$u}` MODIFY `gender` VARCHAR(6) NULL");
            $this->db->query("UPDATE `{$u}` SET gender = 'male'   WHERE gender = 'm'");
            $this->db->query("UPDATE `{$u}` SET gender = 'female' WHERE gender = 'f'");

            // Los roles del sistema son tres. El grupo 2 se llamaba `customer`
            // porque el POS del que nace esto vendia cuentas a clientes finales.
            $g = $this->db->dbprefix('groups');
            $this->db->query("UPDATE `{$g}` SET name = 'admin', description = 'Administrador' WHERE id = 1");
            $this->db->query("UPDATE `{$g}` SET name = 'cajero', description = 'Cajero' WHERE id = 2");
            if (!$this->db->get_where('groups', array('id' => 3))->num_rows()) {
                $this->db->query("INSERT INTO `{$g}` (id, name, description) VALUES (3, 'supervisor', 'Supervisor')");
            }
            $this->db->query("UPDATE `{$u}` SET group_id = 2 WHERE group_id IS NULL OR group_id NOT IN (1,2,3)");

            // El medio de pago 07 pide cual plataforma y el 99 exige
            // <MedioPagoOtros>; los dos caben en la misma columna.
            if (!$this->db->field_exists('medio_pago_detalle', 'suppliers')) {
                $sp = $this->db->dbprefix('suppliers');
                $this->db->query("ALTER TABLE `{$sp}` ADD COLUMN `medio_pago_detalle` VARCHAR(100) NULL DEFAULT NULL AFTER `medio_pago_habitual`");
            }

            $this->db->update('settings', array('versionPOS' => '93'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "93" || $versionInitial) {
            // Bitacora de informes: sin ella un PDF impreso no se puede rastrear
            // hasta los filtros con que salio, y dos copias del mismo periodo con
            // cifras distintas no se pueden distinguir.
            if (!$this->db->table_exists('report_log')) {
                $rl = $this->db->dbprefix('report_log');
                $this->db->query("CREATE TABLE `{$rl}` (
                    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `folio`       VARCHAR(24)  NOT NULL,
                    `reporte`     VARCHAR(60)  NOT NULL,
                    `titulo`      VARCHAR(160) NULL,
                    `formato`     VARCHAR(10)  NOT NULL DEFAULT 'pantalla',
                    `user_id`     INT          NULL,
                    `store_id`    INT          NULL,
                    `fecha`       DATETIME     NOT NULL,
                    `desde`       DATETIME     NULL,
                    `hasta`       DATETIME     NULL,
                    `ambito`      VARCHAR(12)  NULL,
                    `filtros`     TEXT         NULL,
                    `registros`   INT          NOT NULL DEFAULT 0,
                    `total`       DECIMAL(25,4) NOT NULL DEFAULT 0,
                    `confiabilidad` DECIMAL(5,2) NULL,
                    `version`     VARCHAR(10)  NULL,
                    `ip`          VARCHAR(45)  NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `folio` (`folio`),
                    KEY `fecha` (`fecha`),
                    KEY `reporte` (`reporte`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }

            // Umbral anual por contraparte del D-151. La DGT lo ha movido, asi
            // que es un ajuste y no una constante del codigo.
            if (!$this->db->field_exists('d151_umbral', 'settings')) {
                $st = $this->db->dbprefix('settings');
                $this->db->query("ALTER TABLE `{$st}` ADD COLUMN `d151_umbral` DECIMAL(25,2) NULL DEFAULT 2500000");
            }

            // Los informes filtran y ordenan por fecha de venta sobre decenas de
            // miles de filas; sin indice cada consulta recorre la tabla entera.
            $sa = $this->db->dbprefix('sales');
            $si = $this->db->dbprefix('sale_items');
            foreach (array(
                array($sa, 'idx_sales_date',        '(`date`)'),
                array($sa, 'idx_sales_store_date',  '(`store_id`, `date`)'),
                array($si, 'idx_sitems_sale',       '(`sale_id`)'),
                array($si, 'idx_sitems_product',    '(`product_id`)'),
            ) as $ix) {
                list($tabla, $nombre, $cols) = $ix;
                $existe = $this->db->query("SHOW INDEX FROM `{$tabla}` WHERE Key_name = " . $this->db->escape($nombre));
                if ($existe && $existe->num_rows() === 0) {
                    $this->db->query("ALTER TABLE `{$tabla}` ADD INDEX `{$nombre}` {$cols}");
                }
            }

            $this->db->update('settings', array('versionPOS' => '94'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "94" || $versionInitial) {
            // La nota de debito guardaba menos columnas que la de credito, y
            // Crearxml::getNotaDebito lee real_unit_price y cabys: sin ellas el
            // precio unitario salia en cero y el CABYS dependia de la ficha del
            // producto, que en un ajuste puede haber cambiado desde la venta.
            $ndi = $this->db->dbprefix('note_debits_items');
            foreach (array(
                array('net_unit_price',  'DECIMAL(25,4) NOT NULL DEFAULT 0'),
                array('real_unit_price', 'DECIMAL(25,4) NOT NULL DEFAULT 0'),
                array('subtotal',        'DECIMAL(25,4) NOT NULL DEFAULT 0'),
                array('item_discount',   'DECIMAL(25,4) NOT NULL DEFAULT 0'),
                array('cost',            'DECIMAL(25,4) NOT NULL DEFAULT 0'),
                array('comment',         'VARCHAR(255) NULL'),
                array('cabys',           'VARCHAR(13) NULL'),
            ) as $col) {
                if (!$this->db->field_exists($col[0], 'note_debits_items')) {
                    $this->db->query("ALTER TABLE `{$ndi}` ADD COLUMN `{$col[0]}` {$col[1]}");
                }
            }

            // Las columnas nuevas empiezan vacias: para las notas que ya existen
            // el precio sin impuesto es el mismo unit_price que se guardo.
            $this->db->query("UPDATE `{$ndi}` SET `real_unit_price` = `unit_price`,
                `net_unit_price` = `unit_price`, `subtotal` = `quantity` * `unit_price`
                WHERE `real_unit_price` = 0");

            // Historial de correcciones: que documento ajusto a cual, con que
            // codigo y por cuanto. Una venta puede tener varias, asi que el
            // vinculo no cabe como columna de la venta.
            if (!$this->db->table_exists('sale_ajustes')) {
                $sj = $this->db->dbprefix('sale_ajustes');
                $this->db->query("CREATE TABLE `{$sj}` (
                    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `sale_id`        INT          NOT NULL,
                    `tipo`           VARCHAR(16)  NOT NULL,
                    `tipo_doc`       VARCHAR(2)   NOT NULL,
                    `cn_id`          INT          NULL,
                    `nd_id`          INT          NULL,
                    `codigo_referencia` VARCHAR(2) NOT NULL,
                    `motivo`         VARCHAR(255) NULL,
                    `total_original` DECIMAL(25,4) NOT NULL DEFAULT 0,
                    `total_nuevo`    DECIMAL(25,4) NOT NULL DEFAULT 0,
                    `diferencia`     DECIMAL(25,4) NOT NULL DEFAULT 0,
                    `total_nota`     DECIMAL(25,4) NOT NULL DEFAULT 0,
                    `mixto`          TINYINT(1)   NOT NULL DEFAULT 0,
                    `cambios`        TEXT         NULL,
                    `created_by`     INT          NULL,
                    `created_at`     DATETIME     NOT NULL,
                    `ip`             VARCHAR(45)  NULL,
                    PRIMARY KEY (`id`),
                    KEY `sale_id` (`sale_id`),
                    KEY `created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }

            $this->db->update('settings', array('versionPOS' => '95'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "95" || $versionInitial) {
            // addNoteCredit() marca en la venta cuanto se acredito de cada linea,
            // pero esas columnas solo existian en note_credits_items: el UPDATE
            // moria y con db_debug activo tumbaba la emision entera.
            $si = $this->db->dbprefix('sale_items');
            foreach (array(
                array('nc_status', 'TINYINT(1) NOT NULL DEFAULT 0'),
                array('nc_qty',    'DECIMAL(25,4) NOT NULL DEFAULT 0'),
            ) as $col) {
                if (!$this->db->field_exists($col[0], 'sale_items')) {
                    $this->db->query("ALTER TABLE `{$si}` ADD COLUMN `{$col[0]}` {$col[1]}");
                }
            }

            $this->db->update('settings', array('versionPOS' => '96'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "96" || $versionInitial) {
            // Apiclient::MensajeAprobacion() lee y escribe xml_hacienda sobre
            // documentoshacienda, donde la columna nunca existio: el UPDATE con
            // la respuesta de Hacienda moria y el documento aceptado se quedaba
            // en 'procesando' para siempre.
            $dh = $this->db->dbprefix('documentoshacienda');
            if (!$this->db->field_exists('xml_hacienda', 'documentoshacienda')) {
                $this->db->query("ALTER TABLE `{$dh}` ADD COLUMN `xml_hacienda` LONGTEXT NULL");
            }

            $this->db->update('settings', array('versionPOS' => '97'));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "97" || $versionInitial) {
            // Gestion de documentos recibidos: proveedor, destino de cada linea,
            // relaciones aprendidas por proveedor y gastos con su documento.
            $columnas = array(
                'documentoshacienda' => array(
                    'supplier_id'     => 'INT NULL',
                    'ClaveReferencia' => 'VARCHAR(50) NULL',
                    'gestion_estado'  => 'VARCHAR(20) NULL',
                    'gestionado_por'  => 'INT NULL',
                    'gestionado_en'   => 'DATETIME NULL',
                ),
                'documentositems' => array(
                    'numero_linea'       => 'INT NULL',
                    'codigo_tipo'        => 'VARCHAR(2) NULL',
                    'codigo_barras'      => 'VARCHAR(60) NULL',
                    'cabys'              => 'VARCHAR(13) NULL',
                    'codigo_tarifa'      => 'VARCHAR(2) NULL',
                    'impuesto_neto'      => 'DECIMAL(25,5) NULL',
                    'destino'            => 'VARCHAR(12) NULL',
                    'factor'             => 'DECIMAL(25,4) NULL',
                    'categoria_gasto_id' => 'INT NULL',
                ),
                'suppliers' => array(
                    'destino_habitual'       => 'VARCHAR(12) NULL',
                    'categoria_gasto_id'     => 'INT NULL',
                    'condicion_iva_habitual' => 'VARCHAR(2) NULL',
                ),
                'purchases' => array('documento_id' => 'INT NULL'),
                'expenses'  => array(
                    'supplier_id'  => 'INT NULL',
                    'documento_id' => 'INT NULL',
                    'impuesto'     => 'DECIMAL(25,4) NULL',
                ),
            );
            foreach ($columnas as $tabla => $cols) {
                $t = $this->db->dbprefix($tabla);
                foreach ($cols as $col => $tipo) {
                    if (!$this->db->field_exists($col, $tabla)) {
                        $this->db->query("ALTER TABLE `{$t}` ADD COLUMN `{$col}` {$tipo}");
                    }
                }
            }

            if (!$this->db->table_exists('categorias_gasto')) {
                $cg = $this->db->dbprefix('categorias_gasto');
                $this->db->query("CREATE TABLE `{$cg}` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `clave` VARCHAR(20) NULL,
                    `nombre` VARCHAR(80) NOT NULL,
                    `activo` TINYINT(1) NOT NULL DEFAULT 1,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $base = array(
                    array('servicios', 'Servicios publicos'), array('alquiler', 'Alquiler'),
                    array('mantenimiento', 'Mantenimiento y reparaciones'), array('combustible', 'Combustible y transporte'),
                    array('suministros', 'Papeleria y suministros'), array('profesionales', 'Servicios profesionales'),
                    array('seguros', 'Seguros'), array('publicidad', 'Publicidad'),
                    array('telecom', 'Telefonia e internet'), array('activos', 'Activos y equipo'),
                    array('otros', 'Otros gastos'),
                );
                foreach ($base as $c) {
                    $this->db->insert('categorias_gasto', array('clave' => $c[0], 'nombre' => $c[1]));
                }
            }

            if (!$this->db->table_exists('proveedor_producto')) {
                $pp = $this->db->dbprefix('proveedor_producto');
                $this->db->query("CREATE TABLE `{$pp}` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `supplier_id` INT NOT NULL,
                    `codigo` VARCHAR(60) NOT NULL DEFAULT '',
                    `descripcion` VARCHAR(255) NOT NULL DEFAULT '',
                    `cabys` VARCHAR(13) NULL,
                    `product_id` INT NOT NULL,
                    `factor` DECIMAL(25,4) NOT NULL DEFAULT 1,
                    `usos` INT NOT NULL DEFAULT 0,
                    `ultimo_uso` DATETIME NULL,
                    PRIMARY KEY (`id`),
                    KEY `proveedor_codigo` (`supplier_id`, `codigo`),
                    KEY `proveedor_descripcion` (`supplier_id`, `descripcion`(100))
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }

            // Las lineas ya cargadas perdieron descuento, CABYS y numero de linea
            // al leerse: se vuelven a leer de su XML. field_exists() dejo en cache
            // las columnas anteriores al ALTER y el modelo recorta contra esa lista.
            $this->db->data_cache = array();
            $this->load->model('recibidos_model');
            $dh = $this->db->dbprefix('documentoshacienda');
            foreach ($this->db->query("SELECT id_documento, store_id, xml_compra FROM `{$dh}` WHERE xml_compra IS NOT NULL AND xml_compra <> ''")->result() as $fila) {
                $this->recibidos_model->reprocesar($fila);
            }

            $this->db->update('settings', array('versionPOS' => '98'), array('setting_id' => $this->Settings->setting_id));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "98" || $versionInitial) {
            // Proveedor preferido de cada producto: a quien se le sugiere la compra
            // cuando la existencia no alcanza. Arranca con el de la ultima compra.
            $pr = $this->db->dbprefix('products');
            if (!$this->db->field_exists('supplier_id', 'products')) {
                $this->db->query("ALTER TABLE `{$pr}` ADD COLUMN `supplier_id` INT NULL");
            }
            $pu = $this->db->dbprefix('purchases');
            $pi = $this->db->dbprefix('purchase_items');
            $this->db->query(
                "UPDATE `{$pr}` p
                   JOIN (SELECT pi.product_id, SUBSTRING_INDEX(GROUP_CONCAT(pu.supplier_id ORDER BY pu.date DESC, pu.id DESC), ',', 1) AS supplier_id
                           FROM `{$pi}` pi JOIN `{$pu}` pu ON pu.id = pi.purchase_id
                          WHERE pu.supplier_id IS NOT NULL
                          GROUP BY pi.product_id) u ON u.product_id = p.id
                    SET p.supplier_id = u.supplier_id
                  WHERE p.supplier_id IS NULL"
            );

            $this->db->update('settings', array('versionPOS' => '99'), array('setting_id' => $this->Settings->setting_id));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "99" || $versionInitial) {
            // Articulos rapidos: lo que se vende suelto sin ficha (confites, varios)
            // con su CABYS y su tarifa ya verificados contra el catalogo de Hacienda.
            if (!$this->db->table_exists('articulos_rapidos')) {
                $ar = $this->db->dbprefix('articulos_rapidos');
                $this->db->query("CREATE TABLE `{$ar}` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `nombre` VARCHAR(80) NOT NULL,
                    `cabys` CHAR(13) NOT NULL,
                    `cabys_desc` VARCHAR(255) NULL,
                    `impuesto` DECIMAL(5,2) NOT NULL DEFAULT 0,
                    `id_tax` INT NULL,
                    `precio` DECIMAL(25,4) NULL,
                    `activo` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_by` INT NULL,
                    `created_at` DATETIME NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }

            $this->db->update('settings', array('versionPOS' => '100'), array('setting_id' => $this->Settings->setting_id));
            $versionInitial = true;
        }

        if ($this->Settings->versionPOS == "100" || $versionInitial) {
            // Caracteres por linea del papel de cada puesto: el ancho depende de la
            // impresora de esa computadora. NULL usa el del sistema (42, rollo de 80 mm).
            if (!$this->db->field_exists('caracteres', 'pos_workstations')) {
                $pw = $this->db->dbprefix('pos_workstations');
                $this->db->query("ALTER TABLE `{$pw}` ADD COLUMN `caracteres` TINYINT UNSIGNED NULL");
            }

            $this->db->update('settings', array('versionPOS' => '101'), array('setting_id' => $this->Settings->setting_id));
            $versionInitial = true;
        }

        if ($versionInitial) {
            // El objeto de ajustes en cache es anterior a las columnas recien creadas.
            $this->load->driver('cache', array('adapter' => 'file'));
            $this->cache->file->delete('app_settings');
        }

        } // end migration guard
    }

    /**
     * Testigo para las acciones que se disparan con un enlace.
     *
     * El testigo CSRF de CI solo cubre POST y ademas rota en cada peticion, asi
     * que no sirve para un enlace de la tabla. Este vive lo que dure la sesion y
     * basta para que un enlace ajeno no pueda disparar un borrado.
     */
    protected function token_accion()
    {
        $token = $this->session->userdata('token_accion');
        if (!$token) {
            $token = bin2hex(random_bytes(16));
            $this->session->set_userdata('token_accion', $token);
        }
        return $token;
    }

    /**
     * Corta la peticion si el enlace no trae el testigo de la sesion.
     */
    protected function exigir_token_accion()
    {
        $enviado = (string) ($this->input->get('t') ?: $this->input->post('t'));
        if (!hash_equals((string) $this->token_accion(), $enviado)) {
            $this->session->set_flashdata('error', lang('accion_sin_testigo'));
            redirect($this->_referente_propio() ?: 'welcome');
            exit;
        }
    }

    /**
     * El referente, solo si apunta a este mismo sitio.
     *
     * Devolver al usuario a una direccion ajena convertiria cualquier enlace
     * sin testigo en un salto fuera de la aplicacion.
     *
     * @return string cadena vacia si no hay referente utilizable
     */
    private function _referente_propio()
    {
        $ref = (string) $this->input->server('HTTP_REFERER');
        if ($ref === '') {
            return '';
        }
        $base = rtrim(base_url(), '/') . '/';
        return (strncmp($ref, $base, strlen($base)) === 0) ? $ref : '';
    }

    /**
     * Cabeceras de seguridad, en un solo lugar para todas las pantallas.
     *
     * La politica de contenido sale en modo informe: pasarla a bloqueo exige
     * antes sacar el JavaScript en linea de las vistas (etapa S6).
     */
    /**
     * Devuelve el token CSRF vigente en toda respuesta.
     *
     * `csrf_regenerate` esta activo, asi que cada POST rota el token y el que
     * la pagina lleva impreso queda vencido: el envio siguiente —el de la
     * venta, sin ir mas lejos— se rechaza con "The action you have requested
     * is not allowed". El navegador lo relee de esta cabecera y refresca los
     * campos ocultos sin recargar.
     *
     * No se condiciona a is_ajax_request(): eso mira X-Requested-With, que
     * fetch() no manda, y dejaba sin token nuevo justo a las pantallas que
     * piden por fetch. El valor ya viaja impreso en la pagina, asi que
     * anunciarlo tambien en la cabecera no expone nada nuevo.
     */
    private function _cabecera_csrf()
    {
        if (!is_cli() && !headers_sent() && config_item('csrf_protection')) {
            header('X-CSRF-Token: ' . $this->security->get_csrf_hash());
        }
    }

    private function _cabeceras_seguridad()
    {
        if (is_cli() || headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // HSTS solo tiene sentido sobre HTTPS; anunciarlo por HTTP no hace nada
        // y en un local sin certificado dejaria el sitio inalcanzable.
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        header("Content-Security-Policy-Report-Only: default-src 'self'; "
             . "img-src 'self' data: blob:; "
             . "style-src 'self' 'unsafe-inline'; "
             . "script-src 'self' 'unsafe-inline'; "
             . "connect-src 'self' http://127.0.0.1:3001 ws://127.0.0.1:6441 ws://localhost:8181 wss://localhost:8181; "
             . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    }

    /**
     * Guarda bytes ESC/POS para que el navegador los imprima por QZ Tray.
     *
     * La impresora esta en la computadora del cajero, no en el servidor, asi
     * que el ticket se genera aca y la pagina siguiente lo retira con
     * posprint/cola_bytes.
     */
    protected function encolar_bytes_qz($bytes)
    {
        if (!$bytes) {
            return;
        }
        $cola = $this->session->userdata('qz_cola');
        $cola = is_array($cola) ? $cola : array();
        $cola[] = $bytes;
        $this->session->set_userdata('qz_cola', $cola);
    }

    /**
     * Encola un ticket con la estructura que entiende Escpos::print_data()
     * (encabezado, pares etiqueta/valor y totales).
     *
     * @param object $data  Ticket ya armado
     * @param object $store Sucursal a imprimir en el encabezado, si aplica
     */
    protected function encolar_ticket_qz($data, $store = null)
    {
        $this->load->library('escpos');
        $this->escpos->loadBuffer();
        $this->escpos->print_data($data, $store);
        $this->encolar_bytes_qz($this->escpos->getBufferedData());
    }

    function page_construct($page, $data = array(), $meta = array())
    {
        if (empty($meta)) {
            $meta['page_title'] = $data['page_title'];
        }
        $meta['message'] = isset($data['message']) ? $data['message'] : $this->session->flashdata('message');
        $meta['error'] = isset($data['error']) ? $data['error'] : $this->session->flashdata('error');
        $meta['warning'] = isset($data['warning']) ? $data['warning'] : $this->session->flashdata('warning');
        // Una vez leídos para mostrarse, se eliminan de inmediato de la sesión para
        // que nunca puedan reaparecer en una recarga o navegación posterior.
        $this->session->unset_userdata(array('message', 'error', 'warning'));
        $meta['ip_address'] = $this->input->ip_address();
        $meta['Admin'] = $data['Admin'];
        $meta['loggedIn'] = $data['loggedIn'];
        $meta['Settings'] = $data['Settings'];
        $meta['assets'] = $data['assets'];
        $meta['store'] = $data['store'];
        $meta['suspended_sales'] = $this->site->getUserSuspenedSales();
        $meta['qty_alert_num'] = $this->site->getQtyAlerts();
        $this->load->view($this->theme . 'header', $meta);
        $this->load->view($this->theme . $page, $data);
        $this->load->view($this->theme . 'footer');
    }
}

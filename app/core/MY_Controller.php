<?php

defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{

    function __construct()
    {
        parent::__construct();

        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();


        $this->Settings = $this->site->getSettings();
        $this->Settings->password_token_test = decrypt_credential($this->Settings->password_token_test ?? '');
        $this->Settings->password_token_prod = decrypt_credential($this->Settings->password_token_prod ?? '');
        $this->Settings->certificado_pin     = decrypt_credential($this->Settings->certificado_pin ?? '');
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


        if (!isset($this->Settings->versionPOS) || (int)$this->Settings->versionPOS < 53) { // actualizar a max_version+2 al agregar nuevas migraciones

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

            if (!$this->db->field_exists('enviado_cocina', 'suspended_items')) {
                $this->dbforge->add_column('suspended_items', array(
                    'enviado_cocina' => array(
                        'type' => 'tinyint',
                        'constraint' => '1',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
                $this->dbforge->add_column('suspended_items', array(
                    'qty_enviado' => array(
                        'type' => 'int',
                        'constraint' => '10',
                        'default' => '0',
                        'null' => FALSE,
                    )
                ));
            }

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
                $this->db->query("CREATE TABLE `{$this->db->dbprefix}impuestos` (
                    `id_impuesto`      INT(11) NOT NULL AUTO_INCREMENT,
                    `nombre_impuesto`  VARCHAR(100) NOT NULL DEFAULT '',
                    `codigo_impuesto`  VARCHAR(5)   NOT NULL DEFAULT '01',
                    `codigo_tarifa`    VARCHAR(5)   NOT NULL DEFAULT '08',
                    `tarifa`           DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    PRIMARY KEY (`id_impuesto`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // Tarifas IVA Costa Rica (Hacienda v4.4)
                $this->db->query("INSERT INTO `{$this->db->dbprefix}impuestos`
                    (`id_impuesto`,`nombre_impuesto`,`codigo_impuesto`,`codigo_tarifa`,`tarifa`) VALUES
                    (1,  'Exento',          '01', '01',  0.00),
                    (2,  'IVA 1%',          '01', '02',  1.00),
                    (3,  'IVA 2%',          '01', '03',  2.00),
                    (4,  'IVA 4% Canasta',  '01', '04',  4.00),
                    (5,  'IVA 8%',          '01', '05',  8.00),
                    (6,  'IVA 4%',          '01', '07',  4.00),
                    (7,  'IVA 13%',         '01', '08', 13.00),
                    (8,  'IVA 13% General', '01', '08', 13.00),
                    (9,  'No Sujeto',       '07', '01',  0.00)");
            }
            $this->db->update('settings', array('versionPOS' => '51'));
            $versionInitial = true;
        }

        } // end migration guard
    }

    function page_construct($page, $data = array(), $meta = array())
    {
        if (empty($meta)) {
            $meta['page_title'] = $data['page_title'];
        }
        $meta['message'] = isset($data['message']) ? $data['message'] : $this->session->flashdata('message');
        $meta['error'] = isset($data['error']) ? $data['error'] : $this->session->flashdata('error');
        $meta['warning'] = isset($data['warning']) ? $data['warning'] : $this->session->flashdata('warning');
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

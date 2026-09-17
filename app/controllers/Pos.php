<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!class_exists('PosPrint')) {
    require_once APPPATH . 'controllers/PosPrint.php';
}

class Pos extends PosPrint {

    function __construct() {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        $this->load->helper('pos');
        $this->load->model('pos_model');
        $this->load->model('products_model');
        $this->load->model('customers_model');
        $this->load->model('AuditLog_model', 'audit_log');
        $this->load->library('form_validation');
    }

    function pulse($pin = 0, $on_ms = 120, $off_ms = 240) {
        fwrite($this->fp, self::ESC . "p" . chr($pin + 48) . chr($on_ms / 2) . chr($off_ms / 2));
    }

    /**
     * Lo que el cobro necesita saber del cliente elegido.
     *
     * La decision la toma el servidor —`comprobante_helper`— y el POS solo la
     * pinta: que comprobantes admite, por que no los otros, y cuanto credito le
     * queda. `Pos.php` vuelve a comprobar lo mismo al cobrar.
     */
    function estado_cliente($id = NULL)
    {
        $id = (int) ($id ?: $this->input->get('id'));
        $cliente = $id ? $this->site->getCustomerByID($id) : null;

        if (!$cliente) {
            $this->output->set_status_header(404)
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode(array('error' => lang('customer_add_failed'))));
            return;
        }

        $paso  = (int) $this->Settings->default_customer;
        $tipos = comprobantes_permitidos($cliente, $paso);

        $permitidos = array();
        foreach ($tipos as $codigo => $r) {
            $permitidos[$codigo] = array(
                'ok'     => (bool) $r['ok'],
                'motivo' => $r['motivo'] ? lang($r['motivo']) : '',
            );
        }

        $deuda      = $this->pos_model->deudaCliente($id);
        $disponible = credito_disponible($cliente, $deuda);
        $credito    = puede_vender_a_credito($cliente, 0, $deuda, $this->Settings, $paso);

        $this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode(array(
            'id'            => (int) $cliente->id,
            'nombre'        => $cliente->name,
            'comprobantes'  => $permitidos,
            'credito' => array(
                'habilitado' => credito_habilitado($this->Settings),
                'permitido'  => (bool) $credito['ok'],
                'motivo'     => $credito['motivo'] ? lang($credito['motivo']) : '',
                'limite'     => (float) ($cliente->limitcredit ?? 0),
                'deuda'      => $deuda,
                'disponible' => $disponible,
                'dias'       => plazo_credito_dias($cliente, 30),
            ),
            'defectos' => array(
                'tipo_doc'  => $cliente->tipo_doc_defecto ?? '',
                'tipo_pago' => $cliente->tipo_pago_defecto ?? '',
            ),
        ), JSON_UNESCAPED_UNICODE));
    }

    /**
     * Comprueba contra el catalogo el precio y el descuento que manda el POS.
     *
     * Reune los precios legitimos del producto y delega la decision en
     * `precio_de_linea_admisible()`, que es donde vive la regla.
     *
     * @param  object $producto ficha del producto, o un objeto suelto si es un
     *                          articulo rapido (que no tiene precio de lista)
     * @return array{ok: bool, msg: string, aviso: string}
     */
    private function _revisar_precio($producto, $precio, $descuento)
    {
        if (!$producto || empty($producto->id)) {
            return array('ok' => true, 'msg' => '', 'aviso' => '');
        }

        $precios = array();
        foreach (array('price', 'offer_price', 'store_price') as $campo) {
            if (isset($producto->$campo)) { $precios[] = $producto->$campo; }
        }

        $store_id = (int) ($this->session->userdata('store_id') ?: 1);
        $sq = $this->db->get_where('product_store_qty',
            array('product_id' => $producto->id, 'store_id' => $store_id), 1)->row();
        if ($sq) { $precios[] = $sq->price; }

        foreach ($this->db->get_where('product_prices', array('product_id' => $producto->id))->result() as $lp) {
            $precios[] = $lp->price;
        }

        $tope = isset($this->Settings->tope_descuento) ? $this->Settings->tope_descuento : 100;
        $r = precio_de_linea_admisible($precios, $precio, $descuento, $tope, (bool) $this->Admin);

        if ($r['motivo'] === 'descuento_sobre_tope') {
            return array('ok' => false, 'aviso' => '',
                         'msg' => sprintf(lang('descuento_sobre_tope'),
                                          $this->tec->formatNumber($r['descuento_pct']),
                                          $this->tec->formatNumber($tope)));
        }

        if ($r['motivo'] === 'precio_bajo_lista') {
            if (!$r['ok']) {
                return array('ok' => false, 'aviso' => '',
                             'msg' => sprintf(lang('precio_bajo_lista_bloqueado'),
                                              $producto->name,
                                              $this->tec->formatMoney($precio),
                                              $this->tec->formatMoney($r['minimo'])));
            }
            // El administrador puede vender por debajo, pero queda registrado.
            return array('ok' => true, 'msg' => '',
                         'aviso' => sprintf('%s: lista %s, aplicado %s',
                                            $producto->name,
                                            $this->tec->formatMoney($r['minimo']),
                                            $this->tec->formatMoney($precio)));
        }

        return array('ok' => true, 'msg' => '', 'aviso' => '');
    }

    function index($sid = NULL,$rid = NULL, $eid = NULL, $t_nc = NULL, $quo = NULL, $aparta = NULL, $apa = NULL) {
        ini_set("memory_limit", "-1");
        ini_set( 'max_input_vars' , 4000 );

        $this->data['is_suspender'] = 'N';
        if (!$this->Settings->multi_store) {
            $this->session->set_userdata('store_id', 1);
        }
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect($this->Settings->multi_store ? 'stores' : 'welcome');
        }
        if ($this->input->get('hold')) {
            $sid = $this->input->get('hold');
            $this->data['is_suspender'] = $sid;
        }
        if ($this->input->get('redo')) {
            $rid = $this->input->get('redo');
            $this->data['re_create'] = $rid;
        }
        if ($this->input->get('quotes')) {
            $quo = $this->input->get('quotes');
        }
        if ($this->input->get('aparta')) {
            $apa = $this->input->get('aparta');
        }
        if ($this->input->get('edit')) {
            $eid = $this->input->get('edit');
        }
        if ($this->input->get('code')) {
            $eid = base64_decode($this->input->get('code'));
            $eid = explode(' ', $eid);
            $t_nc = $eid[1];

            if (!$eid) {
                redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'pos');
            }
            $eid = $eid[0];
			if (empty($eid)) {
				$this->session->set_flashdata('error', lang('access_denied'));
				redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'pos');
			}
        }

        if ($this->input->post('eid')) {
            $eid = $this->input->post('eid');
        }
        if ($this->input->post('did')) {
            $did = $this->input->post('did');
        } else {
            $did = NULL;
        }

        if ($this->input->post('quo'))
            $quo = $this->input->post('quo');

        if ($this->input->post('apapost')) {
            $apapost = $this->input->post('apapost');
        } else
            $apapost = -1;

        if ($eid && !$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'pos');
        }
        if (!$this->Settings->default_customer) {
            $this->session->set_flashdata('warning', lang('please_update_settings'));
            redirect('settings');
        }
        $this->data['t_nc'] = $t_nc;
        if (!$this->session->userdata('register_id')) {
            if ($register = $this->pos_model->registerData($this->session->userdata('user_id'))) {
                $register_data = array('register_id' => $register->id, 'cash_in_hand' => $register->cash_in_hand, 'register_open_time' => $register->date);
                $this->session->set_userdata($register_data);
            } else {
                $this->session->set_flashdata('error', lang('register_not_open'));
                redirect('pos/open_register');
            }
        }

        $suspend = $this->input->post('suspend') ? TRUE : FALSE;
        $quote = $this->input->post('quote') ? TRUE : FALSE;
        $apart = $this->input->post('apart') ? TRUE : FALSE;
        $this->form_validation->set_rules('customer_id', lang("customer"), 'trim|required');
        $hold_ref = "";
        $id_table = null;
        /*
         *
         *    Guardando
         *
         */

        if ($this->form_validation->run() == true) {

            $quantity = "quantity";
            $product = "product";
            $unit_cost = "unit_cost";
            $tax_rate = "tax_rate";

            $date = $eid ? $this->input->post('date') : date('Y-m-d H:i:s');

            $customer_id = $this->input->post('customer_id');
            $customer_details = $this->pos_model->getCustomerByID($customer_id);
            $customer = $customer_details->name;

            $actividad_id = $this->input->post('actividad_id') ?: $this->Settings->default_actividad;
            $actividad_details = $this->pos_model->getActividadByID($actividad_id);

            $TipoDocumentoE = $this->input->post('ETipoDocumento');
            $NombreInstitucionE = $this->input->post('ENombreInstitucion');
            $NumeroDocumentoE = $this->input->post('ENumeroDocumento');
            $FechaEmisionE = $this->input->post('EFechaEmision');
            $PorcentajeExoneracion = $this->input->post('PorcentajeExoneracion');
            $id_shipping_method = $this->input->post('shipping_method')?$this->input->post('shipping_method'):null;
            $id_shipping_method = $this->input->post('shipping_method')?$this->input->post('shipping_method'):null;
            $MontoExoneracion = $this->input->post('MontoExoneracion');

            // La exoneracion vigente del cliente se copia a la venta: es una
            // resolucion suya, no un dato que el cajero deba teclear cada vez.
            // Vencida deja de aplicarse sola.
            if (!$NumeroDocumentoE && !empty($customer_details->exo_numero)) {
                $vence = $customer_details->exo_fecha_vence ?? null;
                if (!$vence || $vence >= date('Y-m-d')) {
                    $TipoDocumentoE        = $customer_details->exo_tipo_documento;
                    $NombreInstitucionE    = $customer_details->exo_institucion;
                    $NumeroDocumentoE      = $customer_details->exo_numero;
                    $FechaEmisionE         = $customer_details->exo_fecha_emision;
                    $PorcentajeExoneracion = $customer_details->exo_porcentaje;
                } else {
                    log_message('info', '[POS] exoneracion vencida del cliente ' . $customer_details->id
                        . ' (' . $vence . '): no se aplica.');
                }
            }

            $mesatransId = null;
            $note = $this->tec->clear_tags($this->input->post('spos_note'));

            if ($apart || $quote) {
                if ($customer_details->id == $this->Settings->default_customer) {
                    $this->session->set_flashdata('error', lang('select_customer_for_due'));
                    redirect($_SERVER["HTTP_REFERER"]);
                }
            }


            $total = 0;
            $product_tax = 0;
            $order_tax = 0;
            $product_discount = 0;
            $order_discount = 0;
            $percentage = '%';
            $i = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $item_id       = filter_var($_POST['product_id'][$r] ?? null, FILTER_VALIDATE_INT);
                $item_quantity = filter_var($_POST['quantity'][$r] ?? null, FILTER_VALIDATE_FLOAT);
                $real_unit_price = filter_var($_POST['real_unit_price'][$r] ?? null, FILTER_VALIDATE_FLOAT);
                $item_comment  = strip_tags(trim($_POST['item_comment'][$r] ?? ''));

                if ($item_id === false || $item_id < 0) continue; // 0 = producto ad-hoc
                if ($item_quantity === false || $item_quantity <= 0) continue;
                if ($real_unit_price === false || $real_unit_price < 0) continue;

                $real_unit_price = $this->tec->formatDecimal($real_unit_price);


                $item_quantity_edit = 0;
                $item_qty_fracc_edit = 0;
                $item_esta_fraccionado = 0;

                if ($this->Settings->enable_fractions == "1") {
                    // Un articulo rapido no trae estos campos: no tiene ficha de producto.
                    $item_quantity_edit = (isset($_POST['quantity_edit'][$r]) && $_POST['quantity_edit'][$r] != 'undefined') ? $_POST['quantity_edit'][$r] : 0;
                    $item_qty_fracc_edit = (isset($_POST['qty_fracc_edit'][$r]) && $_POST['qty_fracc_edit'][$r] != 'undefined') ? $_POST['qty_fracc_edit'][$r] : 0;
                    $item_esta_fraccionado = (isset($_POST['esta_fraccionado'][$r]) && $_POST['esta_fraccionado'][$r] != 'undefined') ? $_POST['esta_fraccionado'][$r] : 0;
                }
                $item_discount = isset($_POST['product_discount'][$r]) ? $_POST['product_discount'][$r] : '0';


                if (isset($item_id) && isset($real_unit_price) && isset($item_quantity)) {
                    $product_details = $this->site->getProductByID($item_id);

                    if ($product_details) {
                        if ($this->Settings->enable_parquimetro == "1") {
                            $product_name = $_POST['product_name'][$r];
                        } else {
                            $product_name = $product_details->name;
                        }
                        $product_code = $product_details->code;
                        $product_cost = $product_details->cost;
                    } else {
                        $product_name = $_POST['product_name'][$r];
                        $product_code = $_POST['product_code'][$r];
                        $product_cost = 0;
                    }


                    // El CABYS sale de la ficha del producto. Solo el articulo rapido
                    // lo trae en su codigo: en un producto del catalogo ese codigo es
                    // el de barras, y un EAN-13 pasaria por CABYS y Hacienda lo rechaza.
                    $cabys_linea = $product_details
                        ? ((isset($product_details->cabys) && $product_details->cabys) ? $product_details->cabys : null)
                        : (preg_match('/^\d{13}$/', (string) $product_code) ? $product_code : null);
                    if (!$cabys_linea && !empty($this->Settings->fe) && !$suspend && !$quote) {
                        $this->session->set_flashdata('error', sprintf(lang('producto_sin_cabys'), $product_name));
                        redirect('pos');
                    }
                    // Trece digitos no bastan: un codigo fuera del catalogo tambien se
                    // rechaza. Si Hacienda no responde no se frena la caja.
                    if ($cabys_linea && !empty($this->Settings->fe) && !$suspend && !$quote) {
                        $this->load->library('cabys');
                        $catalogo = $this->cabys->consultar($cabys_linea);
                        if ($catalogo !== null && !$catalogo['existe']) {
                            $this->session->set_flashdata('error', sprintf(lang('producto_cabys_inexistente'), $product_name, $cabys_linea));
                            redirect('pos');
                        }
                    }

                    if ($item_esta_fraccionado != "1") {
                        if (!$this->Settings->overselling) {
                            if ($product_details) {
                                if ($product_details->type == 'standard') {
                                    if ($product_details->quantity < $item_quantity) {
                                        $this->session->set_flashdata('error', lang("quantity_low") . ' (' .
                                                lang('name') . ': ' . $product_details->name . ' | ' .
                                                lang('ordered') . ': ' . $item_quantity . ' | ' .
                                                lang('available') . ': ' . $product_details->quantity .
                                                ')');
                                        redirect("pos");
                                    }
                                } elseif ($product_details->type == 'combo') {
                                    $combo_items = $this->pos_model->getComboItemsByPID($product->id);
                                    foreach ($combo_items as $combo_item) {
                                        $cpr = $this->site->getProductByID($combo_item->id);
                                        if ($cpr->quantity < $item_quantity) {
                                            $this->session->set_flashdata('error', lang("quantity_low") . ' (' .
                                                    lang('name') . ': ' . $cpr->name . ' | ' .
                                                    lang('ordered') . ': ' . $item_quantity . ' x ' . $combo_item->qty . ' = ' . $item_quantity * $combo_item->qty . ' | ' .
                                                    lang('available') . ': ' . $cpr->quantity .
                                                    ') ' . $product_details->name);
                                            redirect("pos");
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // El precio y el descuento llegan del navegador. Se comprueban
                    // contra el catalogo antes de que entren en el comprobante.
                    $revision = $this->_revisar_precio($product_details, $real_unit_price, $item_discount);
                    if (!$revision['ok']) {
                        $this->session->set_flashdata('error', $revision['msg']);
                        redirect('pos');
                    }
                    if ($revision['aviso']) {
                        $this->audit_log->log(
                            'precio_bajo_lista', 'product', (int) $item_id,
                            $revision['aviso'], (float) $real_unit_price
                        );
                    }

                    $unit_price = $real_unit_price;
                    $pr_discount = 0;
                    if (isset($item_discount)) {
                        $discount = $item_discount;
                        $dpos = strpos($discount, $percentage);
                        if ($dpos !== false) {
                            $pds = explode("%", $discount);
                            $pr_discount = $this->tec->formatDecimal((($unit_price * (Float) ($pds[0])) / 100), 4);
                        } else {
                            $pr_discount = $this->tec->formatDecimal($discount);
                        }
                    }
                    $unit_price = $this->tec->formatDecimal(($unit_price - $pr_discount), 4);
                    $item_net_price = $unit_price;
                    $pr_item_discount = $this->tec->formatDecimal(($pr_discount * $item_quantity), 4);
                    $product_discount += $pr_item_discount;

                    $pr_item_tax = 0;
                    $item_tax = 0;
                    $tax = 0;

                    if ($_POST['product_name'][$r] == "Producto sin codigo") {
                        $product_name = $_POST['product_name'][$r];
                        $product_code = "PRSC" . $r;
                        $product_cost = 0;
                        $product_details = new stdClass();

                        $product_details->tax = 0;
                        $product_details->type = "standard";
                        $product_details->unit_of_measurement = "Unid";
                    }
                    if ($_POST['product_code'][$r] == "9r091n4") {
                        $product_name = $_POST['product_name'][$r];
                        $product_code = $_POST['product_code'][$r];
                        $product_cost = 0;
                        $product_details = new stdClass();

                        $product_details->tax = 0;
                        $es_servicio = preg_match('/^\d{13}$/', (string) $product_code) && (int) $product_code[0] >= 5;
                        $product_details->type = $es_servicio ? 'service' : 'standard';
                        $product_details->unit_of_measurement = $es_servicio ? 'Sp' : 'Unid';
                    }

                    // Producto ad-hoc (product_id=0): leer tasa de impuesto desde POST id_tax[]
                    if (!$product_details && isset($_POST['id_tax'][$r]) && (int)$_POST['id_tax'][$r] > 0) {
                        $q_im = $this->db->get_where('impuestos', ['id_impuesto' => (int)$_POST['id_tax'][$r]], 1);
                        if ($q_im->num_rows() > 0) {
                            $im = $q_im->row();
                            $product_details = new stdClass();
                            $product_details->tax         = (float)$im->tasa_impuesto;
                            $product_details->tax_method  = 1;
                            $es_servicio = preg_match('/^\d{13}$/', (string) $product_code) && (int) $product_code[0] >= 5;
                            $product_details->type        = $es_servicio ? 'service' : 'standard';
                            $product_details->unit_of_measurement = $es_servicio ? 'Sp' : 'Unid';
                        }
                    }

                    if (isset($product_details->tax) && $product_details->tax != 0) {

                        if ($product_details && $product_details->tax_method == 1) {
                            $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / 100), 4);
                            $tax = $product_details->tax . "%";
                        } else {
                            $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / (100 + $product_details->tax)), 4);
                            $tax = $product_details->tax . "%";
                            $item_net_price -= $item_tax;
                        }

                        $pr_item_tax = $this->tec->formatDecimal(($item_tax * $item_quantity), 4);
                    }

                    $product_tax += $pr_item_tax;
                    $subtotal = $this->tec->formatDecimal((($item_net_price * $item_quantity) + $pr_item_tax), 4);
                    $id_tax = isset($_POST['id_tax'][$r]) && $_POST['id_tax'][$r] > 0 ? $_POST['id_tax'][$r] : 8;

                    if ($this->Settings->propina_enable == "1") {
                        if ($suspend) {
                            $products[] = array(
                                'type' => $product_details->type,
                                'unit_of_measurement' => $product_details->unit_of_measurement,
                                'product_id' => $item_id,
                                'quantity' => $item_quantity,
                                'unit_price' => $unit_price,
                                'net_unit_price' => $item_net_price,
                                'discount' => $item_discount,
                                'comment' => $item_comment,
                                'item_discount' => $pr_item_discount,
                                'tax' => $tax,
                                'item_tax' => $pr_item_tax,
                                'subtotal' => $subtotal,
                                'real_unit_price' => $real_unit_price,
                                'cost' => $product_cost,
                                'product_code' => $product_code,
                                'product_name' => $product_name,
                                'quantity_edit' => $item_quantity_edit,
                                'qty_fracc_edit' => $item_qty_fracc_edit,
                                'esta_fraccionado' => $item_esta_fraccionado,
                                'id_tax' => $id_tax,
                                'cabys' => $cabys_linea
                            );
                        } else {
                            $products[] = array(
                                'type' => $product_details->type,
                                'unit_of_measurement' => $product_details->unit_of_measurement,
                                'product_id' => $item_id,
                                'quantity' => $item_quantity,
                                'unit_price' => $unit_price,
                                'net_unit_price' => $item_net_price,
                                'discount' => $item_discount,
                                'comment' => $item_comment,
                                'item_discount' => $pr_item_discount,
                                'tax' => $tax,
                                'item_tax' => $pr_item_tax,
                                'subtotal' => $subtotal,
                                'real_unit_price' => $real_unit_price,
                                'cost' => $product_cost,
                                'product_code' => $product_code,
                                'product_name' => $product_name,
                                'quantity_edit' => $item_quantity_edit,
                                'qty_fracc_edit' => $item_qty_fracc_edit,
                                'esta_fraccionado' => $item_esta_fraccionado,
                                'id_tax' => $id_tax,
                                'cabys' => $cabys_linea
                            );
                        }
                    } else {
                        $products[] = array(
                            'type' => $product_details->type,
                            'unit_of_measurement' => $product_details->unit_of_measurement,
                            'product_id' => $item_id,
                            'quantity' => $item_quantity,
                            'unit_price' => $unit_price,
                            'net_unit_price' => $item_net_price,
                            'discount' => $item_discount,
                            'comment' => $item_comment,
                            'item_discount' => $pr_item_discount,
                            'tax' => $tax,
                            'item_tax' => $pr_item_tax,
                            'subtotal' => $subtotal,
                            'real_unit_price' => $real_unit_price,
                            'cost' => $product_cost,
                            'product_code' => $product_code,
                            'product_name' => $product_name,
                            'quantity_edit' => $item_quantity_edit,
                            'qty_fracc_edit' => $item_qty_fracc_edit,
                            'esta_fraccionado' => $item_esta_fraccionado,
                            'id_tax' => $id_tax,
                            'cabys' => $cabys_linea
                        );
                    }

                    $total += $this->tec->formatDecimal(($item_net_price * $item_quantity), 4);
                }
            }


            $otrostextos = null;
            $i = isset($_POST['titulo_texto']) ? sizeof($_POST['titulo_texto']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $otrostextos[] = array(
                    'titulo_texto' => $_POST['titulo_texto'][$r],
                    'otrotexto' => $_POST['otrotexto'][$r]
                );
            }

            if (empty($products)) {
                $this->form_validation->set_rules('product', lang("order_items"), 'required');
            } else {
                krsort($products);
            }

            if ($this->input->post('order_discount')) {
                $order_discount_id = $this->input->post('order_discount');
                $opos = strpos($order_discount_id, $percentage);
                if ($opos !== false) {
                    $ods = explode("%", $order_discount_id);
                    $order_discount = $this->tec->formatDecimal(((($total + $product_tax) * (Float) ($ods[0])) / 100), 4);
                } else {
                    $order_discount = $this->tec->formatDecimal($order_discount_id);
                }
            } else {
                $order_discount_id = NULL;
            }

            $total_discount = $this->tec->formatDecimal(($order_discount + $product_discount), 4);


            if ($this->input->post('order_tax')) {
                $order_tax_id = $this->input->post('order_tax');
                $opos = strpos($order_tax_id, $percentage);
                if ($opos !== false) {
                    $ots = explode("%", $order_tax_id);
                    $order_tax = $this->tec->formatDecimal(((($total + $product_tax - $order_discount) * (Float) ($ots[0])) / 100), 4);
                } else {
                    $order_tax = $this->tec->formatDecimal($order_tax_id);
                }
            } else {
                $order_tax_id = NULL;
                $order_tax = 0;
            }

            $total_tax = $this->tec->formatDecimal(($product_tax + $order_tax), 4);
            $total = $total - $MontoExoneracion;
            $grand_total = $this->tec->formatDecimal(($total + $total_tax - $order_discount), 4);

            $paid = $this->input->post('amount') ? $this->input->post('amount') : 0;
            $paid2 = $this->input->post('amount2') ? $this->input->post('amount2') : 0;
            $paid3 = $this->input->post('amount3') ? $this->input->post('amount3') : 0;
            $paid4 = $this->input->post('amount4') ? $this->input->post('amount4') : 0;
            $paidtotal = $paid + $paid2 + $paid3 + $paid4;
            $round_total = $this->tec->roundNumber($grand_total, $this->Settings->rounding);
            if (!$eid) {
                $status = 'due';
                if ($grand_total > $paidtotal && $paidtotal > 0) {
                    if (abs(number_format($paidtotal - $round_total, 0, '.', '')) < 1) {
                        $status = 'paid';
                    } else {
                        $status = 'partial';
                    }
                } elseif ($grand_total <= $paidtotal) {
                    $status = 'paid';
                }
            }
            $rounding = $this->tec->formatDecimal(($round_total - $grand_total));

            if (!$suspend && !$quote) {
                $queda_debiendo = ($paidtotal + 1.5 < $round_total) && $id_shipping_method == NULL;

                if ($queda_debiendo) {
                    // Al cliente de paso no se le fia: no hay a quien cobrarle.
                    if ($customer_details->id == (int) $this->Settings->default_customer) {
                        $this->session->set_flashdata('error', lang('select_customer_for_due'));
                        redirect('pos');
                    }

                    // Lo que queda debiendo es credito: se comprueba el limite,
                    // la deuda acumulada y que el credito este habilitado.
                    $credito = puede_vender_a_credito(
                        $customer_details,
                        $round_total - $paidtotal,
                        $this->pos_model->deudaCliente($customer_details->id),
                        $this->Settings,
                        (int) $this->Settings->default_customer
                    );
                    if (!$credito['ok']) {
                        $mensaje = lang($credito['motivo']);
                        if ($credito['motivo'] === 'credito_excede_limite') {
                            $mensaje .= ' ' . sprintf(lang('credito_faltante'),
                                                      $this->tec->formatMoney($credito['faltante']),
                                                      $this->tec->formatMoney($credito['disponible']));
                        }
                        $this->session->set_flashdata('error', $mensaje);
                        redirect('pos');
                    }
                }
            }

            $receptor = $customer_details;
            $receptor->pre_id_number = $receptor->cf1;
            $receptor->id_number_proveedor = $receptor->cf2;
            $identificacion = str_replace('-', '', trim($receptor->id_number_proveedor));
            $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));
            $tipo_receptor = $receptor->pre_id_number;
            if (strlen($identificacion) < 12) {
                $dif = 12 - strlen($identificacion);
                $ceros = '';
                for ($ce = 1; $ce <= $dif; $ce++) {
                    $ceros .= '0';
                }
                $identificacion = $ceros . $identificacion;
            }
            $identificacion = substr($identificacion, 0, 12);

            if (isset($receptor->pre_id_number)) {

                switch ($receptor->pre_id_number) {
                    case "01":
                        $tipo_receptor = "01";
                        if (strlen(trim($identifivalid)) < 9 || strlen(trim($identifivalid)) > 9) {
                            $tipo_receptor = "05";
                        }
                        break;
                    case "02":
                        $tipo_receptor = "02";
                        if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                            $tipo_receptor = "05";
                        }
                        break;
                    case "03":
                        $tipo_receptor = "03";
                        if (strlen($identifivalid) < 11 || strlen($identifivalid) > 11) {
                            if (strlen($identifivalid) < 12 || strlen($identifivalid) > 12) {
                                $tipo_receptor = "05";
                            }
                        }
                        break;
                    case "04":
                        $tipo_receptor = "04";
                        if (strlen($identifivalid) < 10 || strlen($identifivalid) > 10) {
                            $tipo_receptor = "05";
                        }
                        break;
                    default:
                        $tipo_receptor = "05";
                        break;
                }
            }


            // Contingencia: el comprobante se emite igual, con la situacion 2 en
            // la posicion 42 de la clave, y se reenvia cuando vuelva el internet.
            $situacion = $this->input->post('situacion') === '2' ? '2' : '1';

            // El cajero elige el comprobante en el cobro, pero la decision la
            // vuelve a tomar el servidor: si el navegador manda un tipo que este
            // cliente no admite, gana la regla (auditoria §16.2).
            $permitidos = comprobantes_permitidos($customer_details, (int) $this->Settings->default_customer);
            $pedido = (string) $this->input->post('tipo_doc');

            if (isset($permitidos[$pedido]) && $permitidos[$pedido]['ok']) {
                $tipodoc = $pedido;
            } else {
                if ($pedido !== '' && isset($permitidos[$pedido])) {
                    log_message('error', '[POS] tipo ' . $pedido . ' rechazado para el cliente '
                        . $customer_details->id . ': ' . $permitidos[$pedido]['motivo']);
                }
                $tipodoc = $permitidos['01']['ok'] ? '01' : '04';
            }
            if ($this->Settings->propina_enable == "1")
            {
                if($did)
                {
                    $mesatransId = $this->input->post('input_transfer_table');
                    // dd($mesatransId);
                }
                // dd($did);
                if($mesatransId && $mesatransId != '')
                {
                    $id_table = $mesatransId;
                }else
                {
                    $id_table = $this->input->post('hold_ref');
                }
                $waitingTable = $this->db->get_where('waiting_tables', array('id_waiting_tables' => $id_table), 1)->row();
                $hold_ref =$waitingTable->name;
            }else
            {
                $hold_ref =  $this->input->post('hold_ref');
            }
            $data = array('date' => $date,
                'customer_id' => $customer_id,
                'token_post' => $this->input->post('token_post'),
                'customer_name' => $customer,
                'total' => $this->tec->formatDecimal($total, 4),
                'product_discount' => $this->tec->formatDecimal($product_discount, 4),
                'order_discount_id' => $order_discount_id,
                'order_discount' => $order_discount,
                'total_discount' => $total_discount,
                'product_tax' => $this->tec->formatDecimal($product_tax, 4),
                'order_tax_id' => $order_tax_id,
                'order_tax' => $order_tax,
                'total_tax' => $total_tax,
                'grand_total' => $grand_total,
                'total_items' => count($products),
                'total_quantity' => array_sum(array_column($products, 'quantity')),
                'rounding' => $rounding,
                'paid' => $paidtotal,
                'status' => $status,
                // La columna traia 'paid' por defecto y nadie la escribia: una
                // venta a credito figuraba como pagada en el listado.
                'payment_status' => $status,
                'created_by' => $this->session->userdata('user_id'),
                'note' => $note,
                'hold_ref' => $hold_ref,
                'id_actividad' => $actividad_id,
                'TipoDocumentoE' => $TipoDocumentoE,
                'NombreInstitucionE' => $NombreInstitucionE,
                'NumeroDocumentoE' => $NumeroDocumentoE,
                'FechaEmisionE' => $FechaEmisionE,
                'PorcentajeExoneracion' => $PorcentajeExoneracion,
                'MontoExoneracion' => $MontoExoneracion,
                'tipo_doc' => $tipodoc,
                'situacion' => $situacion,
                'id_shipping_method' => $id_shipping_method 
            );

            if (!$eid) {
                $data['store_id'] = $this->session->userdata('store_id');
            }
            if (!$eid && !$suspend && !$quote && $paidtotal) {
                if ($this->input->post('paying_gift_card_no')) {
                    $gc = $this->pos_model->getGiftCardByNO($this->input->post('paying_gift_card_no'));
                    if (!$gc || $gc->balance < $amount) {
                        $this->session->set_flashdata('error', lang("incorrect_gift_card"));
                        redirect("pos");
                    }
                }

                $amount = $this->tec->formatDecimal(($paidtotal > $grand_total ? ($paidtotal - $this->input->post('balance_amount')) : $paidtotal), 4);

                $payment = array(
                    'date' => $date,
                    'amount' => $paid,
                    'customer_id' => $customer_id,
                    'paid_by' => $this->input->post('paid_by'),
                    'cheque_no' => $this->input->post('cheque_no'),
                    'cc_no' => $this->input->post('cc_no'),
                    'gc_no' => $this->input->post('paying_gift_card_no'),
                    'cc_holder' => $this->input->post('cc_holder'),
                    'cc_month' => $this->input->post('cc_month'),
                    'cc_year' => $this->input->post('cc_year'),
                    'cc_type' => $this->input->post('cc_type'),
                    'cc_cvv2' => $this->input->post('cc_cvv2'),
                    'created_by' => $this->session->userdata('user_id'),
                    'store_id' => $this->session->userdata('store_id'),
                    'note' => $this->input->post('payment_note'),
                    // Comprobante SINPE elegido en la lista en tiempo real del modal de
                    // pago (ver themes/default/views/pos/index.php → #sinpe_reference).
                    // Vacío para cash/card/transfer o si se digitó el monto a mano.
                    'reference' => $this->input->post('sinpe_reference'),
                    'pos_paid' => $this->tec->formatDecimal($this->input->post('amount'), 4),
                    'pos_balance' => $this->tec->formatDecimal($this->input->post('balance_amount'), 4)
                );

                $payment2 = array(
                    'date' => $date,
                    'amount' => $paid2,
                    'customer_id' => $customer_id,
                    'paid_by' => $this->input->post('paid_by2'),
                    'cheque_no' => $this->input->post('cheque_no'),
                    'cc_no' => $this->input->post('cc_no'),
                    'gc_no' => $this->input->post('paying_gift_card_no'),
                    'cc_holder' => $this->input->post('cc_holder'),
                    'cc_month' => $this->input->post('cc_month'),
                    'cc_year' => $this->input->post('cc_year'),
                    'cc_type' => $this->input->post('cc_type1'),
                    'cc_cvv2' => $this->input->post('cc_cvv2'),
                    'created_by' => $this->session->userdata('user_id'),
                    'store_id' => $this->session->userdata('store_id'),
                    'note' => $this->input->post('payment_note'),
                    'reference' => $this->input->post('freferencia1'),
                    'pos_paid' => $this->tec->formatDecimal($this->input->post('amount2'), 4),
                    'pos_balance' => $this->tec->formatDecimal($this->input->post('balance_amount'), 4)
                );

                $payment3 = array(
                    'date' => $date,
                    'amount' => $paid3,
                    'customer_id' => $customer_id,
                    'paid_by' => $this->input->post('paid_by3'),
                    'cheque_no' => $this->input->post('cheque_no'),
                    'cc_no' => $this->input->post('cc_no'),
                    'gc_no' => $this->input->post('paying_gift_card_no'),
                    'cc_holder' => $this->input->post('cc_holder'),
                    'cc_month' => $this->input->post('cc_month'),
                    'cc_year' => $this->input->post('cc_year'),
                    'cc_type' => $this->input->post('cc_type2'),
                    'cc_cvv2' => $this->input->post('cc_cvv2'),
                    'created_by' => $this->session->userdata('user_id'),
                    'store_id' => $this->session->userdata('store_id'),
                    'note' => $this->input->post('payment_note'),
                    'reference' => $this->input->post('freferencia2'),
                    'pos_paid' => $this->tec->formatDecimal($this->input->post('amount3'), 4),
                    'pos_balance' => $this->tec->formatDecimal($this->input->post('balance_amount'), 4)
                );

                $payment4 = array(
                    'date' => $date,
                    'amount' => $paid4,
                    'customer_id' => $customer_id,
                    'paid_by' => $this->input->post('paid_by4'),
                    'cheque_no' => $this->input->post('cheque_no'),
                    'cc_no' => $this->input->post('cc_no'),
                    'gc_no' => $this->input->post('paying_gift_card_no'),
                    'cc_holder' => $this->input->post('cc_holder'),
                    'cc_month' => $this->input->post('cc_month'),
                    'cc_year' => $this->input->post('cc_year'),
                    'cc_type' => $this->input->post('cc_type3'),
                    'cc_cvv2' => $this->input->post('cc_cvv2'),
                    'created_by' => $this->session->userdata('user_id'),
                    'store_id' => $this->session->userdata('store_id'),
                    'note' => $this->input->post('payment_note'),
                    'reference' => $this->input->post('freferencia3'),
                    'pos_paid' => $this->tec->formatDecimal($this->input->post('amount4'), 4),
                    'pos_balance' => $this->tec->formatDecimal($this->input->post('balance_amount'), 4)
                );
                $data['paid'] = $paidtotal;
            } else {
                $payment = array();
                $payment2 = array();
                $payment3 = array();
                $payment4 = array();
            }
            // $this->tec->print_arrays($data, $products, $payment);
        }

        if ($this->form_validation->run() == true && !empty($products)) {

            $this->load->library('Crearxml', NULL, 'Crearxml');
            $this->load->library('firmar', NULL, 'firmar');
            // dd("Entro");


            if ($suspend) {
                unset($data['id_shipping_method'],$data['status'], $data['rounding'], $data['TipoDocumentoE'], $data['NombreInstitucionE'], $data['NumeroDocumentoE'], $data['FechaEmisionE'], $data['PorcentajeExoneracion'], $data['MontoExoneracion'], $data['tipo_doc'], $data['situacion']);
                
                $data['id_waiting_tables']= $id_table;
                if ($suspend_id = $this->pos_model->suspendSale($data, $products, $did, $otrostextos)) {
                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', lang("sale_saved_to_opened_bill"));
                    if ($this->Settings->enable_parquimetro == "1") {
                        $this->print_parquimetro($data, $products, $did, $otrostextos);
                    }

                    redirect("pos");
                } else {
                    if ($this->Settings->enable_parquimetro == "1") {
                        $this->print_parquimetro($data, $products, $did, $otrostextos);
                    }
                    $this->session->set_flashdata('error', lang("action_failed"));
                    redirect("pos/" . $did);
                }
            } elseif ($quote) {
                unset($data['id_shipping_method'],$data['status'], $data['rounding'], $data['tipo_doc'], $data['situacion']);

                if ($idQuote = $this->pos_model->quoteSale($data, $products, $quo, $otrostextos)) {
                    $msg = "Proforma Agregada correctamente";

                    try {
                        $this->print_receipt($idQuote, true, '21');
                    } catch (Exception $e) {
                        $this->session->set_flashdata('error', "Error inesperado revise la impresora");
                    }

                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', lang("quote_saved_to_opened_bill"));
                    redirect("pos");
                } else {
                    $this->session->set_flashdata('error', lang("action_failed"));
                    redirect("pos/?quotes=" . $quo);
                }
            } elseif ($eid) {

                unset($data['id_shipping_method'],$data['status'], $data['paid'], $data['TipoDocumentoE'], $data['NombreInstitucionE'], $data['NumeroDocumentoE'], $data['FechaEmisionE'], $data['PorcentajeExoneracion'], $data['MontoExoneracion'], $data['tipo_doc'], $data['situacion']);

                if (!$this->Admin) {
                    unset($data['date']);
                }
                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['updated_by'] = $this->session->userdata('user_id');
                if ($this->pos_model->updateSale($eid, $data, $products)) {
                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', lang("sale_updated"));
                    redirect("sales");
                } else {
                    $this->session->set_flashdata('error', lang("action_failed"));
                    redirect("pos/?edit=" . $eid);
                }
            } elseif ($apart) {

                unset($data['id_shipping_method'],$data['TipoDocumentoE'], $data['NombreInstitucionE'], $data['NumeroDocumentoE'], $data['FechaEmisionE'], $data['PorcentajeExoneracion'], $data['MontoExoneracion'], $data['tipo_doc'], $data['situacion']);

                if ($sale = $this->pos_model->addSaleApartado($data, $products, $payment, $did, $otrostextos)) {
                    $msg = lang("apartado_added");

                    try {
                        $this->print_receipt($sale['apartado_id'], true, '20');
                    } catch (Exception $e) {
                        $this->session->set_flashdata('error', "Error inesperado revise la impresora");
                    }

                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', $msg);
                    redirect("pos");
                } else {
                    $this->session->set_flashdata('error', lang("action_failed"));
                    redirect("pos");
                }
            } elseif ($apapost > 0) {
                unset($data['id_shipping_method']);
                if ($sale = $this->pos_model->TransformarApartadoSales($data, $products, $apapost, $otrostextos)) {
                    $msg = lang("sale_added");

                    $pagos = $this->db->get_where('payments_apartado', array('apartado_id' => $apapost));
                    foreach (($pagos->result()) as $pago) {

                        $payments = array(
                            'date' => $pago->date,
                            'sale_id' => $sale['sales_id'],
                            'customer_id' => $pago->customer_id,
                            'transaction_id' => $pago->transaction_id,
                            'paid_by' => $pago->paid_by,
                            'cheque_no' => $pago->cheque_no,
                            'cc_no' => $pago->cc_no,
                            'cc_holder' => $pago->cc_holder,
                            'cc_month' => $pago->cc_month,
                            'cc_year' => $pago->cc_year,
                            'cc_type' => $pago->cc_type,
                            'amount' => $pago->amount,
                            'currency' => $pago->currency,
                            'created_by' => $pago->created_by,
                            'attachment' => $pago->attachment,
                            'note' => $pago->note,
                            'pos_paid' => $pago->pos_paid,
                            'pos_balance' => $pago->pos_balance,
                            'gc_no' => $pago->gc_no,
                            'reference' => $pago->reference,
                            'updated_by' => $pago->updated_by,
                            'updated_at' => $pago->updated_at,
                            'store_id' => $pago->store_id
                        );
                    }
                    $data['id'] = $sale['sales_id'];
                    $facturadigital = $this->Crearxml->getInvoice($data, $products, $payments, $otrostextos);
                    if($facturadigital != null){
                    $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
                        if (file_exists($certificado)) {
                            try {
                                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $facturadigital["xml"]);
                            } catch (Exception $e) {
                                $firmado = false;
                            }
                        } else {
                            $firmado = false;
                        }

                        if ($firmado) {
                            $facturadigital['xml_sign'] = $firmado;
                        }

                        $facturadigital['sale_id'] = $sale['sales_id'];

                        $this->session->unset_userdata("last_sale_id");
                        $this->session->set_userdata("last_sale_id", $sale['sales_id']);
                        $this->session->userdata();

                        $this->hacienda_model->insertxml($facturadigital);

                        $this->session->set_userdata('rmspos', 1);
                        $msg = lang("sale_added");
                        //$msg= $msg.' '.$paid2;

                        if (!empty($sale['message'])) {
                            $msg .= '<br>' . $sale['message'];
                        }

                        $this->session->set_flashdata('message', $msg);
                        }else{
                            $msg .= '<br> Error al crear xml';
                            $this->session->set_flashdata('message', $msg);
                        }
                        redirect("pos");
                    } else {
                        $this->session->set_flashdata('error', lang("action_failed"));
                        redirect("pos");
                    }
            } else {
                if ($sale = $this->pos_model->addSale($data, $products, $payment, $did, $payment2, $payment3, $payment4, $otrostextos)) {
                        $data['id']= $sale['sale_id'];

                        // Conciliación SINPE: si se pagó con un comprobante elegido de la
                        // lista en tiempo real, marcarlo como usado y enlazarlo a esta venta.
                        // El WHERE estado='pendiente' evita que dos ventas usen el mismo
                        // comprobante si dos cajeros lo seleccionaron casi al mismo tiempo —
                        // si ya lo tomó otra venta, esta UPDATE afecta 0 filas y se avisa,
                        // pero la venta actual YA se creó y no se revierte por esto.
                        // El SINPE puede ocupar cualquiera de las cuatro formas de pago.
                        $formas_pago = array(
                            array($this->input->post('paid_by'),  $this->input->post('sinpe_reference')),
                            array($this->input->post('paid_by2'), $this->input->post('freferencia1')),
                            array($this->input->post('paid_by3'), $this->input->post('freferencia2')),
                            array($this->input->post('paid_by4'), $this->input->post('freferencia3')),
                        );

                        $sinpe_refs = array();
                        foreach ($formas_pago as $fp) {
                            if ($fp[0] === 'sinpe' && !empty($fp[1])) {
                                $sinpe_refs[] = $fp[1];
                            }
                        }

                        $sinpe_ref = reset($sinpe_refs) ?: NULL;

                        if ($sinpe_refs && $this->db->table_exists('sinpe_transactions')) {
                            foreach ($sinpe_refs as $ref) {
                                // El WHERE por estado evita que dos ventas tomen el mismo comprobante.
                                $this->db->where('comprobante', $ref);
                                $this->db->where('estado', 'pendiente');
                                $this->db->update('sinpe_transactions', array(
                                    'estado' => 'usado',
                                    'sale_id' => $sale['sale_id'],
                                ));
                                if ($this->db->affected_rows() < 1) {
                                    $this->session->set_flashdata('warning',
                                        'La venta se registró correctamente, pero el comprobante SINPE ' . $ref .
                                        ' ya había sido usado en otra venta — revisar manualmente.');
                                }
                            }
                        }

                        // v4.4 no tiene campo fiscal para la referencia del SINPE:
                        // se deja en <Otros>, de uso comercial.
                        if (!empty($sinpe_refs)) {
                            if (!is_array($otrostextos)) { $otrostextos = array(); }
                            $otrostextos[] = array(
                                'titulo_texto' => 'SINPE',
                                'otrotexto'    => 'Comprobante SINPE Movil: ' . implode(', ', $sinpe_refs),
                            );
                        }

                        // v4.4 declara un <MedioPago> por forma de pago usada.
                        $pagos_del_comprobante = array_values(array_filter(
                            array($payment, $payment2, $payment3, $payment4),
                            function ($pg) { return !empty($pg) && !empty($pg['amount']) && (float) $pg['amount'] > 0; }
                        ));
                        if (empty($pagos_del_comprobante)) { $pagos_del_comprobante = $payment; }

                        $facturadigital = $this->Crearxml->getInvoice($data, $products, $pagos_del_comprobante, $otrostextos);
                        if($facturadigital != null){
                        $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';

                        if (file_exists($certificado)) {
                            try {
                                $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $facturadigital["xml"]);
                            } catch (Exception $e) {
                                $firmado = false;
                            }
                        } else {
                            $firmado = false;
                        }

                        if ($firmado) {
                            $facturadigital['xml_sign'] = $firmado;
                        }

                        $facturadigital['sale_id'] = $sale['sale_id'];

                        $this->session->unset_userdata("last_sale_id");
                        $this->session->set_userdata("last_sale_id", $sale['sale_id']);
                        $this->session->userdata();

                        //$facturadigital=array("xml"=>"","clave"=>"","consecutivo"=>"","fecha_emision"=>"","tipo_doc"=>"");
                        //print_r($facturadigital);               
                        $this->hacienda_model->insertxml($facturadigital);

                        $this->session->set_userdata('rmspos', 1);
                        $msg = lang("sale_added");
                        if (!empty($sale['message'])) {
                            foreach ($sale['message'] as $m) {
                                $msg .= '<br>' . $m;
                            }
                        }

                        $this->session->set_flashdata('message', $msg);
                        // Ajustes > POS > "Pagina despues de la venta" decide si se
                        // vuelve al POS o se abre el comprobante. En la vuelta al POS
                        // esta marca le dice que avise, limpie el carrito y, si la
                        // impresion automatica esta activa, imprima el tiquete.
                        $en_efectivo = in_array('cash', array(
                            $this->input->post('paid_by'),
                            $this->input->post('paid_by2'),
                            $this->input->post('paid_by3'),
                            $this->input->post('paid_by4'),
                        ), TRUE);

                        $this->session->set_flashdata('venta_ok', array(
                            'id'       => (int) $sale['sale_id'],
                            'efectivo' => $en_efectivo,
                        ));
                    }else{
                        // $msg solo se define dentro de la rama de exito
                        $msg = (isset($msg) ? $msg : lang("sale_added")) . '<br> Error al crear xml';
                        $this->session->set_flashdata('message', $msg);
                    }
                        $this->audit_log->log('venta_creada', 'sale', (int)$sale['sale_id'], '', (float)$grand_total);
                        redirect($this->Settings->after_sale_page ? 'pos' : 'pos/view/' . $sale['sale_id']);
                } else {
                    $this->session->set_flashdata('error', lang("action_failed"));
                    redirect("pos");
                }
            }
        } else {
            $total_tax = 0;

            if (isset($sid) && !empty($sid)) {
                $suspended_sale = $this->pos_model->getSuspendedSaleByID($sid);
                // La cuenta pudo cobrarse o borrarse desde otra terminal.
                if (!$suspended_sale) {
                    $this->session->set_flashdata('error', lang('cuenta_suspendida_no_existe'));
                    redirect('pos');
                }
                $inv_items = $this->pos_model->getSuspendedSaleItems($sid);
                $otrostextos = $this->pos_model->getSuspendedOtrosTextos($sid);
                if (!is_array($inv_items)) { $inv_items = array(); }

                $hourdiff = round((strtotime(date('Y-m-d H:i:s')) - strtotime($suspended_sale->date)) / 3600, 1);

                krsort($inv_items);
                $c = rand(100000, 9999999);
                $pc = 1;
                $total_tax = 0;
                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        $row = json_decode('{}');
                        $row->id = 0;
                        $row->tax = 0;
                    }
                    if ($item->product_name == "Producto sin codigo") {

                        $item->product_code = "PRSC" . $pc;
                        $item->product_id = "PRSC" . $pc;
                        $row->id = $pc;
                        $pc = $pc + 1;
                    }

                    //                    $row->name = $item->product_name.' Entrada: '.date('d-m-Y H:i:s', strtotime($suspended_sale->date)). ' Salida: '.date('d-m-Y H:i:s', strtotime($suspended_sale->date));

                    $row->code = $item->product_code;
                    $row->name = $this->Settings->enable_parquimetro == '1' ? $item->product_name . " - IN(" . $suspended_sale->date . ') OUT(' . date('Y-m-d H:i:s') . ')' : $item->product_name;

                    $row->price = $item->real_unit_price;
                    //$row->unit_price = $item->unit_price + ($item->item_discount / $item->quantity) + ($item->item_tax / $item->quantity);
                    $row->unit_price = $item->unit_price;
                    $row->real_unit_price = $item->real_unit_price;

                    //$row->price = $item->net_unit_price + ($item->item_discount / $item->quantity);
                    //$row->unit_price = $item->unit_price + ($item->item_discount / $item->quantity) + ($item->item_tax / $item->quantity);
                    //$row->real_unit_price = $item->real_unit_price;
                    $row->discount = $item->discount;
                    $row->qty = $this->Settings->enable_parquimetro == '1' ? $hourdiff : $item->quantity;
                    $row->comment = $item->comment;
                    $row->ordered = $this->Settings->enable_parquimetro == '1' ? $hourdiff : $item->quantity;
                    $row->id_impuesto = $item->id_impuesto;
                    $row->codigo_impuesto = $item->codigo_impuesto;
                    $row->codigo_tarifa = $item->codigo_tarifa;
                    $combo_items = FALSE;
                    $total_tax += $item->item_tax;
                    $ri = $this->Settings->item_addition ? $row->id : $c;
                    $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'combo_items' => $combo_items);
                    $c++;
                }
                $this->data['total_tax'] = $total_tax;
                $this->data['items'] = json_encode($pr);
                $this->data['sid'] = $sid;
                $this->data['otrostextos'] = $otrostextos;

                //        $this->data['reference_note'] = $suspended_sale->hold_ref;                
                $this->data['suspend_sale'] = $suspended_sale;
                $this->data['message'] = lang('suspended_sale_loaded');
            }
            $total_tax = 0;
            if (isset($rid) && !empty($rid)) {
                $redo_sale = $this->pos_model->getSaleByID($rid);
                $inv_items = $this->pos_model->getAllSaleItems($rid);
                $otrostextos = $this->pos_model->getSaleOtrosTextos($rid);
                $hourdiff = round((strtotime(date('Y-m-d H:i:s')) - strtotime($redo_sale->date)) / 3600, 1);
                $is_tip = false;
                krsort($inv_items);
                $c = rand(100000, 9999999);
                $pc = 1;
                $total_tax = 0;
                $count = 0;
                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        $row = json_decode('{}');
                        $row->id = 0;
                        $row->tax = 0;
                    }
                    if ($item->product_name == "Producto sin codigo") {

                        $item->product_code = "PRSC" . $pc;
                        $item->product_id = "PRSC" . $pc;
                        $row->id = $pc;
                        $pc = $pc + 1;
                    }

                    //                    $row->name = $item->product_name.' Entrada: '.date('d-m-Y H:i:s', strtotime($suspended_sale->date)). ' Salida: '.date('d-m-Y H:i:s', strtotime($suspended_sale->date));

                    $row->code = $item->product_code;
                    $row->name = $this->Settings->enable_parquimetro == '1' ? $item->product_name . " - IN(" . $redo_sale->date . ') OUT(' . date('Y-m-d H:i:s') . ')' : $item->product_name;

                    $row->price = $item->real_unit_price;
                    //$row->unit_price = $item->unit_price + ($item->item_discount / $item->quantity) + ($item->item_tax / $item->quantity);
                    $row->unit_price = $item->unit_price;
                    $row->real_unit_price = $item->real_unit_price;
                    $row->store_price = $item->unit_price;
                    //$row->price = $item->net_unit_price + ($item->item_discount / $item->quantity);
                    //$row->unit_price = $item->unit_price + ($item->item_discount / $item->quantity) + ($item->item_tax / $item->quantity);
                    //$row->real_unit_price = $item->real_unit_price;

                    $row->discount = $item->discount;
                    $row->qty = $this->Settings->enable_parquimetro == '1' ? $hourdiff : $item->quantity;
                    $row->comment = $item->comment;
                    $row->ordered = $this->Settings->enable_parquimetro == '1' ? $hourdiff : $item->quantity;
                    $row->id_impuesto = $item->id_impuesto;
                    $row->codigo_impuesto = $item->codigo_impuesto;
                    $row->codigo_tarifa = $item->codigo_tarifa;
                    $combo_items = FALSE;
                    $total_tax += $item->item_tax;
                    $ri = $this->Settings->item_addition ? $row->id : $c;
                    if( $row->code!= '9r091n4'){
                        $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'combo_items' => $combo_items);
                    }else{
                        $is_tip= true;
                    }
                    $c++;

                }
                $this->data['total_tax'] = $total_tax;
                $this->data['items'] = json_encode($pr);
                $this->data['rid'] = $rid;
                $this->data['otrostextos'] = $otrostextos;
                $this->data['is_tip'] = $is_tip;
                $this->data['reference_note'] = $redo_sale->hold_ref;                
                $this->data['redo_sale'] = $redo_sale;
                $this->data['message'] = lang('re_create');
            }
            if (isset($quo) && !empty($quo)) {
                $quotes_sale = $this->pos_model->getQuotesSalesID($quo);
                $inv_items = $this->pos_model->getQuotesSaleItems($quo);
                $otrostextos = $this->pos_model->getQuotesOtrosTextos($quo);
                
                krsort($inv_items);
                // dd($inv_items);
                $c = rand(100000, 9999999);
                $pc = 1;
                $total_tax = 0;
                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        $row = json_decode('{}');
                        $row->id = 0;
                        $row->tax = 0;
                    }
                    if ($item->product_name == "Producto sin codigo") {
                        $item->product_code = "PRSC" . $pc;
                        $item->product_id = "PRSC" . $pc;
                        $row->id = $pc;
                        $pc = $pc + 1;
                    }
                    $row->code = $item->product_code;
                    $row->name = $item->product_name;
                    //$row->unit_price = $item->unit_price + ($item->item_discount / $item->quantity) + ($item->item_tax / $item->quantity);
                    $row->price = $item->real_unit_price;
                    $row->unit_price = $item->unit_price;
                    $row->real_unit_price = $item->real_unit_price;
                    $row->discount = $item->discount;
                    $row->qty = $item->quantity;
                    $row->comment = $item->comment;
                    $row->ordered = $item->quantity;
                    $row->id_impuesto = $item->id_impuesto;
                    $row->codigo_impuesto = $item->codigo_impuesto;
                    $row->codigo_tarifa = $item->codigo_tarifa;
                    $combo_items = FALSE;
                    $total_tax += $item->item_tax;
                    $ri = $this->Settings->item_addition ? $row->id : $c;
                    $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'combo_items' => $combo_items);
                    $c++;
                }
                $this->data['total_tax'] = $total_tax;
                $this->data['items'] = json_encode($pr);
                $this->data['quo'] = $quo;
                $this->data['quotes_sale'] = $quotes_sale;
                $this->data['otrostextos'] = $otrostextos;
                $this->data['message'] = lang('quotes_sale_loaded');
            }

            if (isset($apa) && !empty($apa)) {
                $apa_sale = $this->pos_model->getApartadoSalesID($apa);
                $inv_items = $this->pos_model->getApartadoSaleItems($apa);
                $otrostextos = $this->pos_model->getApartadoOtrosTextos($apa);
                krsort($inv_items);
                $c = rand(100000, 9999999);
                $pc = 1;
                $total_tax = 0;

                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        $row = json_decode('{}');
                        $row->id = 0;
                        $row->tax = 0;
                    }
                    if ($item->product_name == "Producto sin codigo") {
                        $item->product_code = "PRSC" . $pc;
                        $item->product_id = "PRSC" . $pc;
                        $row->id = $pc;
                        $pc = $pc + 1;
                    }
                    $row->code = $item->product_code;
                    $row->name = $item->product_name;
                    //$row->price = $item->net_unit_price + ($item->item_discount / $item->quantity) + ($item->item_tax / $item->quantity);
                    //$row->unit_price = $item->unit_price + ($item->item_discount / $item->quantity) + ($item->item_tax / $item->quantity);
                    $row->price = $item->real_unit_price;
                    $row->unit_price = $item->unit_price;
                    $row->real_unit_price = $item->real_unit_price;
                    $row->discount = $item->discount;
                    $row->qty = $item->quantity;
                    $row->comment = $item->comment;
                    $row->ordered = $item->quantity;
                    $row->id_impuesto = $item->id_impuesto;
                    $row->codigo_impuesto = $item->codigo_impuesto;
                    $row->codigo_tarifa = $item->codigo_tarifa;
                    $combo_items = FALSE;
                    $total_tax += $item->item_tax;
                    $ri = $this->Settings->item_addition ? $row->id : $c;
                    $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'combo_items' => $combo_items);
                    $c++;
                }

                $this->data['total_tax'] = $total_tax;
                $this->data['items'] = json_encode($pr);
                $this->data['apa'] = $apa_sale->id;
                $this->data['apa_sale'] = $apa_sale;
                $this->data['apa_grand_total'] = $apa_sale->grand_total;
                $this->data['otrostextos'] = $otrostextos;
                $this->data['message'] = lang('apa_sale_loaded');
            }

            if (isset($eid) && !empty($eid)) {
                $sale = $this->pos_model->getSaleByID($eid);
                $inv_items = $this->pos_model->getAllSaleItems($eid);
                $otrostextos = $this->pos_model->getSaleOtrosTextos($eid);
                $is_tip = false;
                krsort($inv_items);
                $c = rand(100000, 9999999);
                $total_tax = 0;
                $pc= 1;
                foreach ($inv_items as $item) {
					
                    if ($item->quantity - $item->nc_qty != '0') {

                        $row = $this->site->getProductByID($item->product_id);
                        if (!$row) {
                            $row = json_decode('{}');
                            $row->id = 0;
                            $row->tax = 0;
                        }
                        if ($item->product_name == "Producto sin codigo") {
    
                            $item->product_code = "PRSC" . $pc;
                            $item->product_id = "PRSC" . $pc;
                            $row->id = $pc;
                            $pc = $pc + 1;
                        }
                        $row->code = $item->product_code;
                        $row->name = $this->Settings->enable_parquimetro == '1' ? $item->product_name . " - IN(" . $redo_sale->date . ') OUT(' . date('Y-m-d H:i:s') . ')' : $item->product_name;
                        $row->price = $item->real_unit_price;
                        $row->unit_price = $item->unit_price;
                        $row->real_unit_price = $item->real_unit_price;
                        $row->store_price = $item->unit_price;
                        $row->discount = $item->discount;
                        $row->qty = $this->Settings->enable_parquimetro == '1' ? $hourdiff : $item->quantity;
                        $row->comment = $item->comment;
                        $row->ordered = $this->Settings->enable_parquimetro == '1' ? $hourdiff : $item->quantity;
                        $row->id_impuesto = $item->id_impuesto;
                        $row->codigo_impuesto = $item->codigo_impuesto;
                        $row->codigo_tarifa = $item->codigo_tarifa;
                        $combo_items = FALSE;
                        $total_tax += $item->item_tax;
                        $ri = $this->Settings->item_addition ? $item->id : $c;
                        if($row->code != '9r091n4'){
                            $pr[$ri] = array('id' => $c, 'item_id' => $item->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'combo_items' => $combo_items);
                        }else{
                            $is_tip = true;
                        }
                        $c++;

                    }
                }
                if (!isset($pr)) {
                    $this->session->set_flashdata('error', "Ya se le aplico la nota de credito a los articulos de esta factura");
                    redirect("pos");
                }
                $this->data['total_tax'] = $total_tax;
                $this->data['items'] = json_encode($pr);
                $this->data['eid'] = $eid;
                $this->data['is_tip'] = $is_tip;
                //$this->data['reference_note'] = $sale->hold_ref;  
                $this->data['otrostextos'] = $otrostextos;
                $this->data['sale'] = $sale;
                $this->data['message'] = lang('sale_loaded');
            }

            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

            $this->data['reference_note'] = isset($sid) && !empty($sid) ? $suspended_sale->id_waiting_tables : (isset($eid) && !empty($eid) ? $sale->hold_ref : (isset($quo) && !empty($quo) ? $quotes_sale->hold_ref : (isset($apa) && !empty($apa) ? $apa_sale->hold_ref : (isset($rid) && !empty($rid) ? $redo_sale->hold_ref : NULL))));

            $this->data['sid'] = isset($sid) && !empty($sid) ? $sid : 0;
            $this->data['rid'] = isset($rid) && !empty($rid) ? $rid : 0;
            $this->data['quo'] = isset($quo) && !empty($quo) ? $quo : 0;
            $this->data['eid'] = isset($eid) && !empty($eid) ? $eid : 0;
            $this->data['apa'] = isset($apa) && !empty($apa) ? $apa : 0;
            $this->data['apa_grand_total'] = isset($apa) && !empty($apa) ? $apa_sale->grand_total : 0;

            $this->data['customers'] = $this->site->getAllCustomers();
            $this->data['actividadeconomica'] = $this->site->getAllActividades();
            // El alta rapida de cliente arma <Ubicacion> del receptor; los niveles
            // siguientes los pide por AJAX conforme se elige.
            $this->data['provincias'] = $this->db->select('codigo_provincia as codigo, nombre_provincia as nombre')
                ->order_by('nombre_provincia', 'ASC')->get('provincia_cr')->result();


            $this->data["tcp"] = $this->pos_model->products_count($this->Settings->default_category);
            $this->data['products'] = $this->ajaxproducts($this->Settings->default_category, 1);
            $this->data['categories'] = $this->site->getAllCategories();
            $this->data['message'] = $this->session->flashdata('message');
            $this->data['suspended_sales'] = $this->site->getUserSuspenedSales();
            // $this->data['quotes_sales'] = $this->site->getUserQuotesSales();

            $this->data['total_tax'] = $total_tax;
            $shipping = null;
            if($this->Settings->is_shipping == 1){
                $shipping = $this->pos_model->getAllShipping();
            }
            $waiting_tables = null;
            if($this->Settings->propina_enable == 1){
                $waiting_tables = $this->pos_model->getWaitingTables();
            }
            $this->data['shipping'] = $shipping;
            $this->data['waiting_tables'] = $waiting_tables;

            // Aviso de Hacienda en la barra del POS: el cajero tiene que ver que
            // los comprobantes estan saliendo antes de seguir cobrando.
            $amb = ($this->Settings->ambiente ?? 'test') === 'prod' ? 'prod' : 'test';
            $this->data['hacienda_estado'] = $this->hacienda_model->estadoConexion(
                $amb,
                $this->Settings->{'user_token_' . $amb} ?? '',
                $this->Settings->{'password_token_' . $amb} ?? '',
                $this->Settings->certificado_ced ?? ''
            );
            $this->data['impuestos_list'] = $this->site->getAllImpuestos();
            $this->data['page_title'] = lang('pos');
            $bc = array(array('link' => '#', 'page' => lang('pos')));
            $meta = array('page_title' => lang('pos'), 'bc' => $bc);

            $this->load->view($this->theme . 'pos/index', $this->data, $meta);
        }
    }

    function nota_credito($sid = NULL, $eid = NULL) {
        if (!$this->Settings->multi_store) {
            $this->session->set_userdata('store_id', 1);
        }
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect($this->Settings->multi_store ? 'stores' : 'welcome');
        }
        if ($this->input->post('eid')) {
            $eid = $this->input->post('eid');
        }
        if ($this->input->post('did')) {
            $did = $this->input->post('did');
        } else {
            $did = NULL;
        }
        // if ($eid && !$this->Admin) {
        //     $this->session->set_flashdata('error', lang('access_denied'));
        //     redirect(isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : 'pos');
        // }
        if (!$this->Settings->default_customer) {
            $this->session->set_flashdata('warning', lang('please_update_settings'));
            redirect('settings');
        }


        if (!$this->session->userdata('register_id')) {
            if ($register = $this->pos_model->registerData($this->session->userdata('user_id'))) {
                $register_data = array('register_id' => $register->id, 'cash_in_hand' => $register->cash_in_hand, 'register_open_time' => $register->date);
                $this->session->set_userdata($register_data);
            } else {
                $this->session->set_flashdata('error', lang('register_not_open'));
                redirect('pos/open_register');
            }
        }


        $this->form_validation->set_rules('customer', lang("customer"), 'trim|required');

        if ($this->form_validation->run() == true) {

            $quantity = "quantity";
            $product = "product";
            $unit_cost = "unit_cost";
            $tax_rate = "tax_rate";

            $date = date('Y-m-d H:i:s');

            $customer_id = $this->input->post('customer_id');
            $customer_details = $this->pos_model->getCustomerByID($customer_id);
            $customer = $customer_details->name;
            $note = $this->tec->clear_tags($this->input->post('spos_note'));

            $total = 0;
            $product_tax = 0;
            $order_tax = 0;
            $product_discount = 0;
            $order_discount = 0;
            $percentage = '%';

            $i = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
            for ($r = 0; $r < $i; $r++) {

                $id_tax = $_POST['id_tax'][$r];
                $item_id = $_POST['product_id'][$r];
                $real_unit_price = $this->tec->formatDecimal($_POST['real_unit_price'][$r]);
                $item_quantity = $_POST['quantity'][$r];
                $item_comment = $_POST['item_comment'][$r];
                $item_discount = isset($_POST['product_discount'][$r]) ? $_POST['product_discount'][$r] : '0';

                $item_quantity = $_POST['quantity'][$r];
                if (isset($item_id) && isset($real_unit_price) && isset($item_quantity)) {
                    $product_details = $this->site->getProductByID($item_id);
                    if ($product_details) {
                        $product_name = $_POST['product_name'][$r];
                        $product_code = $_POST['product_code'][$r];
                        $product_cost = $product_details->cost;
                    } else {
                        $product_name = $_POST['product_name'][$r];
                        $product_code = $_POST['product_code'][$r];
                        $product_cost = 0;
                    }

                    // El CABYS sale de la ficha del producto. Solo el articulo rapido
                    // lo trae en su codigo: en un producto del catalogo ese codigo es
                    // el de barras, y un EAN-13 pasaria por CABYS y Hacienda lo rechaza.
                    $cabys_linea = $product_details
                        ? ((isset($product_details->cabys) && $product_details->cabys) ? $product_details->cabys : null)
                        : (preg_match('/^\d{13}$/', (string) $product_code) ? $product_code : null);
                    if (!$cabys_linea && !empty($this->Settings->fe)) {
                        $this->session->set_flashdata('error', sprintf(lang('producto_sin_cabys'), $product_name));
                        redirect('pos');
                    }
                    // Trece digitos no bastan: un codigo fuera del catalogo tambien se
                    // rechaza. Si Hacienda no responde no se frena la caja.
                    if ($cabys_linea && !empty($this->Settings->fe)) {
                        $this->load->library('cabys');
                        $catalogo = $this->cabys->consultar($cabys_linea);
                        if ($catalogo !== null && !$catalogo['existe']) {
                            $this->session->set_flashdata('error', sprintf(lang('producto_cabys_inexistente'), $product_name, $cabys_linea));
                            redirect('pos');
                        }
                    }

                    if (!$this->Settings->overselling) {
                        if ($product_details->type == 'standard') {
                            if ($product_details->quantity < $item_quantity) {
                                $this->session->set_flashdata('error', lang("quantity_low") . ' (' .
                                        lang('name') . ': ' . $product_details->name . ' | ' .
                                        lang('ordered') . ': ' . $item_quantity . ' | ' .
                                        lang('available') . ': ' . $product_details->quantity .
                                        ')');
                                redirect("pos");
                            }
                        } elseif ($product_details->type == 'combo') {
                            $combo_items = $this->pos_model->getComboItemsByPID($product->id);
                            foreach ($combo_items as $combo_item) {
                                $cpr = $this->site->getProductByID($combo_item->id);
                                if ($cpr->quantity < $item_quantity) {
                                    $this->session->set_flashdata('error', lang("quantity_low") . ' (' .
                                            lang('name') . ': ' . $cpr->name . ' | ' .
                                            lang('ordered') . ': ' . $item_quantity . ' x ' . $combo_item->qty . ' = ' . $item_quantity * $combo_item->qty . ' | ' .
                                            lang('available') . ': ' . $cpr->quantity .
                                            ') ' . $product_details->name);
                                    redirect("pos");
                                }
                            }
                        }
                    }
                    $unit_price = $real_unit_price;

                    $pr_discount = 0;
                    if (isset($item_discount)) {
                        $discount = $item_discount;
                        $dpos = strpos($discount, $percentage);
                        if ($dpos !== false) {
                            $pds = explode("%", $discount);
                            $pr_discount = $this->tec->formatDecimal((($unit_price * (Float) ($pds[0])) / 100), 4);
                        } else {
                            $pr_discount = $this->tec->formatDecimal($discount);
                        }
                    }
                    $unit_price = $this->tec->formatDecimal(($unit_price - $pr_discount), 4);
                    $item_net_price = $unit_price;
                    $pr_item_discount = $this->tec->formatDecimal(($pr_discount * $item_quantity), 4);
                    $product_discount += $pr_item_discount;

                    $pr_item_tax = 0;
                    $item_tax = 0;
                    $tax = "";
                    if (isset($product_details->tax) && $product_details->tax != 0) {

                        if ($product_details && $product_details->tax_method == 1) {
                            $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / 100), 4);
                            $tax = $product_details->tax . "%";
                        } else {
                            $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / (100 + $product_details->tax)), 4);
                            $tax = $product_details->tax . "%";
                            $item_net_price -= $item_tax;
                        }

                        $pr_item_tax = $this->tec->formatDecimal(($item_tax * $item_quantity), 4);
                    }

                    $product_tax += $pr_item_tax;
                    $subtotal = $this->tec->formatDecimal((($item_net_price * $item_quantity) + $pr_item_tax), 4);
                    $products[] = array(
                        'product_id' => $item_id,
                        'quantity' => $item_quantity,
                        'unit_price' => $unit_price,
                        'net_unit_price' => $item_net_price,
                        'discount' => $item_discount,
                        'comment' => $item_comment,
                        'item_discount' => $pr_item_discount,
                        'tax' => $tax,
                        'item_tax' => $pr_item_tax,
                        'subtotal' => $subtotal,
                        'real_unit_price' => $real_unit_price,
                        'cost' => $product_cost,
                        'product_code' => $product_code,
                        'unit_of_measurement' => $product_details->unit_of_measurement,
                        'product_name' => $product_name,
                        'id_tax' => $id_tax,
                        'cabys' => $cabys_linea,
                    );

                    $total += $this->tec->formatDecimal(($item_net_price * $item_quantity), 4);
                }
            }

            $otrostextos = null;
            $i = isset($_POST['titulo_texto']) ? sizeof($_POST['titulo_texto']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $otrostextos[] = array(
                    'titulo_texto' => $_POST['titulo_texto'][$r],
                    'otrotexto' => $_POST['otrotexto'][$r]
                );
            }

            if (empty($products)) {
                $this->form_validation->set_rules('product', lang("order_items"), 'required');
            } else {
                krsort($products);
            }

            if ($this->input->post('order_discount')) {
                $order_discount_id = $this->input->post('order_discount');
                $opos = strpos($order_discount_id, $percentage);
                if ($opos !== false) {
                    $ods = explode("%", $order_discount_id);
                    $order_discount = $this->tec->formatDecimal(((($total + $product_tax) * (Float) ($ods[0])) / 100), 4);
                } else {
                    $order_discount = $this->tec->formatDecimal($order_discount_id);
                }
            } else {
                $order_discount_id = NULL;
            }
            $total_discount = $this->tec->formatDecimal(($order_discount + $product_discount), 4);

            if ($this->input->post('order_tax')) {
                $order_tax_id = $this->input->post('order_tax');
                $opos = strpos($order_tax_id, $percentage);
                if ($opos !== false) {
                    $ots = explode("%", $order_tax_id);
                    $order_tax = $this->tec->formatDecimal(((($total + $product_tax - $order_discount) * (Float) ($ots[0])) / 100), 4);
                } else {
                    $order_tax = $this->tec->formatDecimal($order_tax_id);
                }
            } else {
                $order_tax_id = NULL;
                $order_tax = 0;
            }

            $total_tax = $this->tec->formatDecimal(($product_tax + $order_tax), 4);
            $grand_total = $this->tec->formatDecimal(($total + $total_tax - $order_discount), 4);
            $round_total = $this->tec->roundNumber($grand_total, $this->Settings->rounding);
            $rounding = $this->tec->formatDecimal(($round_total - $grand_total));


            $data = array(
                'type_nc' => $this->input->post('t_nc'),
                'sale_id' => $eid,
                'date' => $date,
                'customer_id' => $customer_id,
                'customer_name' => $customer,
                'total' => $this->tec->formatDecimal($total, 4),
                'product_discount' => $this->tec->formatDecimal($product_discount, 4),
                'order_discount_id' => $order_discount_id,
                'order_discount' => $order_discount,
                'total_discount' => $total_discount,
                'product_tax' => $this->tec->formatDecimal($product_tax, 4),
                'order_tax_id' => $order_tax_id,
                'order_tax' => $order_tax,
                'total_tax' => $total_tax,
                'grand_total' => $grand_total,
                'total_items' => count($products),
                'total_quantity' => array_sum(array_column($products, 'quantity')),
                'rounding' => $rounding,
                'paid' => "",
                'status' => "",
                'created_by' => $this->session->userdata('user_id'),
                'note' => $note,
                'hold_ref' => $this->input->post('hold_ref'),
                'store_id' => $this->session->userdata('store_id')
            );
        }


        if ($this->form_validation->run() == true && !empty($products)) {
            $this->load->library('Crearxml', NULL, 'Crearxml');


            if ($creditnote = $this->pos_model->addNoteCredit($data, $products, $this->input->post('t_nc'), $otrostextos)) {

                $invoice = $this->hacienda_model->getInvoice($eid);

                $sale = $this->pos_model->getSaleByID($eid);
                    if ($sale->status != "paid") {
                        $pagado = $sale->paid + $data['grand_total'];
                        $restante = $sale->grand_total - $pagado;
                        if ($restante < 1) {
                            $estatus = "paid";
                        } else if ($restante > 0) {
                            $estatus = "due";
                        }
                        $this->pos_model->UpdatePaid($eid, $pagado, $estatus);
                    }

                    $NotaCreditodigital = $this->Crearxml->getNotaCredito($data, $products, $invoice, $otrostextos);

                    $this->load->library('firmar', NULL, 'firmar');

                    $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';

                    $firmado = false;
                    if (file_exists($certificado)) {
                        try {
                            $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $NotaCreditodigital["xml"]);
                        } catch (Exception $e) {
                            $firmado = false;
                        }
                    }

                    if ($firmado) {
                        $NotaCreditodigital['xml_sign'] = $firmado;
                    }

                    $NotaCreditodigital['id_cn'] = $creditnote['id_cn'];

                    $this->hacienda_model->insertxmlCN($NotaCreditodigital);

                    $this->session->set_userdata('rmspos', 1);
                    $msg = "Nota de Credito Creada Correctamente";
                    if (!empty($creditnote['message'])) {
                        foreach ($creditnote['message'] as $m) {
                            $msg .= '<br>' . $m;
                        }
                    }
                    $this->session->set_flashdata('message', $msg);
                    $haciendaInvo = $this->hacienda_model->getInvoice($invoice->sale_id);


                    try {
                        $this->print_receipt($creditnote['id_cn'], true, 3, $haciendaInvo);
                    } catch (Exception $e) {
                        $this->session->set_flashdata('error', "Error inesperado revise la impresora");
                    }
                    $this->audit_log->log('nota_credito', 'sale', (int)($eid ?? 0), 'NC #' . $creditnote['id_cn'], (float)($data['grand_total'] ?? 0));
                    $redirect_to = $this->Settings->after_sale_page ? "pos" : "pos/viewnc/" . $creditnote['id_cn'];

                    redirect("pos");
            } else {
                $this->session->set_flashdata('error', lang("action_failed"));
                redirect("pos");
            }
        }
    }

    function get_product($code = NULL) {

        if ($this->input->get('code')) {
            $code = $this->input->get('code');
        }
        $combo_items = FALSE;
        if ($product = $this->pos_model->getProductByCode($code)) {
            unset($product->cost, $product->details);
            $product->qty = 1;
            $product->comment = '';
            $product->discount = '0';
            $product->price = $product->store_price > 0 ? $product->store_price : $product->price;
            $product->real_unit_price = $product->price;
            $product->unit_price = $product->tax ? ($product->price + (($product->price * $product->tax) / 100)) : $product->price;
            if ($product->type == 'combo') {
                $combo_items = $this->pos_model->getComboItemsByPID($product->id);
            }
            echo json_encode(array('id' => str_replace(".", "", microtime(true)), 'item_id' => $product->id, 'label' => $product->name . " (" . $product->code . ")", 'row' => $product, 'combo_items' => $combo_items));
        } else {
            echo NULL;
        }
    }

    function suggestions() {
        $term = $this->input->get('term', TRUE);

        $rows = $this->pos_model->getProductNames($term, $this->Settings->quantity_suggest);
        if (!$rows) {
            echo json_encode(array(array('id' => 0, 'label' => lang('no_match_found'), 'value' => $term)));
            return;
        }

        $pr = array();
        foreach ($rows as $row) {
            unset($row->cost, $row->details);
            $row->qty = 1;
            $row->comment = '';
            $row->discount = '0';
            $row->price = $row->store_price > 0 ? $row->store_price : $row->price;
            $row->real_unit_price = $row->price;
            $row->unit_price = $row->tax ? ($row->price + (($row->price * $row->tax) / 100)) : $row->price;
            $combo_items = FALSE;

            if ($row->type == 'combo') {
                $combo_items = $this->pos_model->getComboItemsByPID($row->id);
            }

            // El detalle viaja en campos aparte: la lista del POS los maqueta,
            // en vez de imprimir una sola linea de texto.
            $pr[] = array(
                'id'          => str_replace(".", "", microtime(true)),
                'item_id'     => $row->id,
                'label'       => $row->name . ' (' . $row->code . ')',
                'nombre'      => $row->name,
                'codigo'      => $row->code,
                'precio'      => (float) $row->unit_price,
                'precio_fmt'  => $this->tec->formatMoney($row->unit_price),
                'unidad'      => $row->unit_of_measurement,
                'existencias' => (float) $row->quantity,
                'ubicacion'   => $row->ubicacion ? $row->ubicacion : '',
                'row'         => $row,
                'combo_items' => $combo_items,
            );
        }
        echo json_encode($pr);
    }

    function registers() {

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['registers'] = $this->pos_model->getOpenRegisters();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('pos'), 'page' => lang('pos')), array('link' => '#', 'page' => lang('open_registers')));
        $meta = array('page_title' => lang('open_registers'), 'bc' => $bc);
        $this->page_construct('pos/registers', $this->data, $meta);
    }

    function open_register() {
        $this->data['open_cash'] = true;

        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang("please_select_store"));
            redirect('stores');
        }

        // Una caja esta abierta o cerrada, nunca abierta dos veces: sin esta
        // revision cada visita a esta pantalla creaba otra fila abierta y el POS
        // volvia a la anterior en cuanto se cerraba una.
        if ($abierta = $this->pos_model->registerData($this->session->userdata('user_id'))) {
            $this->session->set_userdata(array(
                'register_id'        => $abierta->id,
                'cash_in_hand'       => $abierta->cash_in_hand,
                'register_open_time' => $abierta->date,
            ));
            $this->session->set_flashdata('message', lang('register_already_open'));
            redirect('pos');
        }
        $this->form_validation->set_rules('cash_in_hand', lang("cash_in_hand"), 'trim|required|numeric');

        $lastclose = $this->pos_model->getLastTodayCloseRegister($this->session->userdata('user_id'));
        $auth_open = $this->pos_model->getAuthOpen($this->session->userdata('user_id'));

        if ($this->Settings->enable_auth_open == "1") {
            if ($lastclose) {
                if ($auth_open->auth_open == "0") {
                    $this->data['open_cash'] = false;
                }
                if ($auth_open->auth_open == "1") {
                    $this->pos_model->DisableAuthOpen($this->session->userdata('user_id'));
                }
            }
        }

        if ($this->form_validation->run() == true) {
            $data = array('date' => date('Y-m-d H:i:s'),
                'cash_in_hand' => $this->input->post('cash_in_hand'),
                'user_id' => $this->session->userdata('user_id'),
                'store_id' => $this->session->userdata('store_id'),
                'status' => 'open',
            );
        }
        if ($this->form_validation->run() == true && ($rid = $this->pos_model->openRegister($data))) {
            $this->audit_log->log('apertura_caja', 'register', (int) $rid, 'Apertura: ' . $data['date'], (float) $data['cash_in_hand']);
            $this->session->set_flashdata('message', lang("welcome_to_pos"));
            redirect("pos");
        } else {

            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

            $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => '#', 'page' => lang('open_register')));
            $meta = array('page_title' => lang('open_register'), 'bc' => $bc);
            $this->page_construct('pos/open_register', $this->data, $meta);
        }
    }

    function ajaxproducts($category_id = NULL, $return = NULL) {

        if ($this->input->get('category_id')) {
            $category_id = $this->input->get('category_id');
        } elseif (!$category_id) {
            $category_id = $this->Settings->default_category;
        }
        if ($this->input->get('per_page') == 'n') {
            $page = 0;
        } else {
            $page = $this->input->get('per_page');
        }
        if ($this->input->get('tcp') == 1) {
            $tcp = TRUE;
        } else {
            $tcp = FALSE;
        }

        $products = $this->pos_model->fetch_products($category_id, $this->Settings->pro_limit, $page);
        $prods = '';

        if ($products) {
            // Una tarjeta por producto, hijas directas de .pos-product-grid: un
            // contenedor intermedio rompe la rejilla y las apila en columna.
            foreach ($products as $product) {
                $existencia = isset($product->existencia) ? (float) $product->existencia : null;
                $alerta     = isset($product->alert_quantity) ? (float) $product->alert_quantity : 0;

                if ($existencia === null)            { $tono = 'na';  $etq = '—'; }
                elseif ($existencia <= 0)            { $tono = 'out'; $etq = '0'; }
                elseif ($alerta > 0 && $existencia <= $alerta) { $tono = 'low'; $etq = $this->tec->formatQuantity($existencia); }
                else                                 { $tono = 'ok';  $etq = $this->tec->formatQuantity($existencia); }

                $img = '';
                if (!empty($product->image) && is_file(FCPATH . 'uploads/thumbs/' . $product->image)) {
                    $img = '<img src="' . base_url('uploads/thumbs/' . $product->image) . '" alt="">';
                } else {
                    // Sin foto se usan las iniciales del nombre
                    $ini = '';
                    foreach (preg_split('/\s+/', trim($product->name)) as $pal) {
                        if ($pal !== '' && mb_strlen($ini) < 2) { $ini .= mb_strtoupper(mb_substr($pal, 0, 1)); }
                    }
                    $img = '<span class="pp-ini">' . html_escape($ini !== '' ? $ini : '#') . '</span>';
                }

                $prods .= '<button type="button" class="product pos-prod-card"'
                        . ' value="' . html_escape($product->code) . '"'
                        . ' data-code="' . html_escape($product->code) . '"'
                        . ' data-name="' . html_escape($product->name) . '"'
                        . ' title="' . html_escape($product->name) . '">'
                        . '<span class="pp-img">' . $img . '</span>'
                        . '<span class="pp-name">' . html_escape($product->name) . '</span>'
                        . '<span class="pp-code">' . html_escape($product->code) . '</span>'
                        . '<span class="pp-foot">'
                        . '<span class="pp-price">' . $this->tec->formatMoney($product->price) . '</span>'
                        . '<span class="pp-stock ' . $tono . '">' . $etq . '</span>'
                        . '</span>'
                        . '</button>';
            }
        } else {
            $prods = '<div class="pos-grid-empty"><p>' . lang('category_is_empty') . '</p></div>';
        }

        if (!$return) {
            if (!$tcp) {
                echo $prods;
            } else {
                $category_products = $this->pos_model->products_count($category_id);
                header('Content-Type: application/json');
                echo json_encode(array('products' => $prods, 'tcp' => $category_products));
            }
        } else {
            return $prods;
        }
    }

    /**
     * Lista de transacciones SINPE pendientes (aún no usadas en ninguna venta),
     * para la lista en tiempo real del modal de pago cuando se elige "SINPE".
     * Lee tec_sinpe_transactions directo — NO llama al servicio Node (ver
     * www/sinpe-service): así el checkout del POS nunca depende de que ese
     * proceso esté arriba. El servicio Node solo escribe; esto solo lee.
     * GET pos/ajax_sinpe_pending?monto=12500
     *
     * SINPE pendientes de los últimos 2 días que alcanzan a cubrir el monto
     * indicado, del más reciente al más viejo. Sin 'monto' devuelve todos.
     */
    function ajax_sinpe_pending() {
        header('Content-Type: application/json');

        if (!$this->db->table_exists('sinpe_transactions')) {
            // La migración versionPOS 62 todavía no corrió en esta base.
            echo json_encode(array('pendientes' => array()));
            return;
        }

        $monto = $this->input->get('monto');

        $this->db->select('id_sinpe_transaction, comprobante, nombre, telefono, monto, fecha, banco, descripcion');
        $this->db->where('estado', 'pendiente');
        $this->db->where('fecha >=', date('Y-m-d H:i:s', strtotime('-2 days')));

        if ($monto !== NULL && $monto !== '' && (float)$monto > 0) {
            // Margen de un colón para absorber redondeos.
            $this->db->where('monto >=', (float)$monto - 1);
            $this->db->order_by('fecha', 'DESC');
        } else {
            $this->db->order_by('fecha', 'DESC');
        }

        $rows = $this->db->limit(25)->get('sinpe_transactions')->result();

        echo json_encode(array('pendientes' => $rows));
    }




    function promotions() {
        $this->load->view($this->theme . 'promotions', $this->data);
    }

    function stripe_balance() {
        if (!$this->Owner) {
            return FALSE;
        }
        $this->load->model('stripe_payments');
        return $this->stripe_payments->get_balance();
    }

    function language($lang = false) {
        if ($this->input->get('lang')) {
            $lang = $this->input->get('lang');
        }
        //$this->load->helper('cookie');
        $folder = 'app/language/';
        $languagefiles = scandir($folder);
        if (in_array($lang, $languagefiles)) {
            $cookie = array(
                'name' => 'language',
                'value' => $lang,
                'expire' => '31536000',
                'prefix' => 'spos_',
                'secure' => false
            );

            $this->input->set_cookie($cookie);
        }
        redirect($_SERVER["HTTP_REFERER"]);
    }

    function validate_gift_card($no) {
        if ($gc = $this->pos_model->getGiftCardByNO(urldecode($no))) {
            if ($gc->expiry) {
                if ($gc->expiry >= date('Y-m-d')) {
                    echo json_encode($gc);
                } else {
                    echo json_encode(false);
                }
            } else {
                echo json_encode($gc);
            }
        } else {
            echo json_encode(false);
        }
    }




    function consultprice() {
        //$this->data['product'] = $this->pos_model->getProductByCode($code);
        $this->data['modal'] = true;
        $this->load->view($this->theme . 'pos/viewprice', $this->data);
    }

    function getprice() {
        $term = $this->input->get('term', TRUE);

        $rows = $this->pos_model->getProductPrice($term);
        if ($rows) {
            echo json_encode($rows);
        } else {
            echo json_encode(array(array('id' => 0, 'label' => lang('no_match_found'), 'value' => $term)));
        }
    }

    function deposito() {
        echo "deposito";
    }

    function retiro() {

        $cantida = $this->input->post('cantidad');

        echo "retiro " . $cantida;
    }

    function itmsuspend($id)
    {
        $data = $this->pos_model->getItemssuspended($id);
        echo json_encode($data);
    }

    function save_receivable()
    {
        $qty_cuentas = intval($this->input->post("qty_cuentas"));
        $id_suspended = $this->input->post("id_suspended");
        $customer_id = $this->input->post('customer_id');
        $customer_details = $this->pos_model->getCustomerByID($customer_id);
        $customer = $customer_details->name;
        $actividad_id = $this->input->post('actividad_id') ?: $this->Settings->default_actividad;
        $ctaQty = $this->input->post("CtaQty");
        $productos= $this->input->post("idproduct");
        $percentage = '%';
        // dd($qty_cuentas);
        for($x = 1; $x <= $qty_cuentas;$x++ )
        {
            $product_discount=0;
            $total_discount = 0;
            $product_tax = 0;
            $total=0;
            $products = array();
            for($y = 0; $y < count($productos[$x]);$y++ )
            {
                 $id_product = $productos[$x][$y];
                 $qty = floatval($ctaQty[$x][$y]);
                 $suspended_item = $this->db->get_where('suspended_items', array('suspend_id' => $id_suspended,'product_id' => $id_product))->row();
                 $real_unit_price = $suspended_item->real_unit_price;
                 $tax= $suspended_item->tax;
                 $item_discount = $suspended_item->item_discount;
                 $unit_price = $real_unit_price;
                 $pr_discount = 0;
                 if (isset($item_discount)) {
                     $discount = $item_discount;
                     $dpos = strpos($discount, $percentage);
                     if ($dpos !== false) {
                         $pds = explode("%", $discount);
                         $pr_discount = $this->tec->formatDecimal((($unit_price * (Float) ($pds[0])) / 100), 4);
                     } else {
                         $pr_discount = $this->tec->formatDecimal($discount);
                     }
                 }
                 $unit_price = $this->tec->formatDecimal(($unit_price - $pr_discount), 4);
                 $pr_item_discount = $this->tec->formatDecimal(($pr_discount * $qty), 4);
                 $item_net_price = $unit_price;
                 $product_discount += $pr_item_discount;
                 $pr_item_tax = $this->tec->formatDecimal((($item_net_price * $qty)*($tax/100)), 4);
                 $subtotal = $this->tec->formatDecimal((($pr_discount * $qty)+$pr_item_tax), 4);
                 $products[] = array(
                    'unit_of_measurement' => $suspended_item->unit_of_measurement,
                    'product_id' => $id_product ,
                    'quantity' => $qty,
                    'unit_price' => $unit_price,
                    'net_unit_price' => $item_net_price,
                    'discount' => $item_discount,
                    'comment' => $suspended_item->comment,
                    'item_discount' => $pr_item_discount,
                    'tax' => $tax,
                    'item_tax' => $pr_item_tax,
                    'subtotal' => $subtotal,
                    'real_unit_price' => $real_unit_price,
                    'product_code' => $suspended_item->product_code,
                    'product_name' => $suspended_item->product_name,
                    'id_tax' => $suspended_item->id_tax
                );
                // var_dump("<pre>");
                // var_dump($products);
                // var_dump("</pre>");
                //  $total += ($suspended_item->net_unit_price * $qty); 
                 $total += $this->tec->formatDecimal(($item_net_price *$qty), 4);
                 $product_tax += $pr_item_tax;
            }
            $hold_ref= $this->input->post("hold_ref_".$x); 
            $suspended_sales = $this->db->select("hold_ref, store_id, id_waiting_tables")->get_where('suspended_sales', array('id' => $id_suspended))->row();
            $grand_total = $this->tec->formatDecimal(($total + $product_tax -$product_discount), 4);
            $data = array(
                'date'=> date('Y-m-d H:i:s'),
                'customer_id' => $customer_id,
                'token_post' => md5(date('Y-m-d H:i:s')),
                'customer_name' => $customer,
                'total' => $this->tec->formatDecimal($total, 4),
                'product_discount' => $this->tec->formatDecimal($product_discount, 4),
                'order_discount_id' => null,
                'order_discount' => null,
                'total_discount' => $product_discount,
                'product_tax' => $this->tec->formatDecimal($product_tax, 4),
                'order_tax_id' => null,
                'order_tax' => null,
                'total_tax' => $product_tax,
                'grand_total' => $grand_total,
                'total_items' => count($products),
                'total_quantity' => array_sum(array_column($products, 'quantity')),
                'store_id'=>$suspended_sales->store_id,
                'paid' => 0,
                'created_by' => $this->session->userdata('user_id'),
                'note' => $hold_ref,
                'hold_ref' => $suspended_sales->hold_ref,
                'id_waiting_tables'=> $suspended_sales->id_waiting_tables,
                'id_actividad' => $actividad_id,
            );
            $suspend_id = $this->pos_model->suspendSale($data, $products, null, null);
            sleep(1);
        }
        // dd($qty_cuentas);
        $this->db->delete('suspended_items', array('suspend_id' => $id_suspended));
        $this->db->delete('suspended_sales', array('id' => $id_suspended));
        redirect("pos");
    }

    function item_list_prices()
    {
        $rows = $this->pos_model->getListPrice();
        if ($rows) {
            foreach ($rows as $row) {
                $pr[] = array("code"=>$row->code, "name"=>$row->nombre_l_precio, "id_lista_precios" => $row->id_lista_precios);
            }
            echo json_encode($pr);
        } else {
            echo null;
        }
    }

    function get_group_price()
    {
        $id_product = $this->input->get('id_product');
        $id_product_prices = $this->input->get('id_price');
        $rows = $this->pos_model->getPricesByProductId($id_product,$id_product_prices);
        // dd($rows);
        if ($rows) {
            foreach ($rows as $row) {
                $pr[] = array("price"=>$row->price, "name"=>$row->name);
            }
            echo json_encode($pr);
        } else {
            echo null;
        }

    }

}

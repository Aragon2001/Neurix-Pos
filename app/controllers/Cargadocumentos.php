<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cargadocumentos extends MY_Controller {

    /** Documentos electronicos que se admiten como recibidos, por nodo raiz. */
    private $tipos = array(
        'FacturaElectronica'     => 'Factura Electronica',
        'NotaCreditoElectronica' => 'Nota de Credito Electronica',
        'NotaDebitoElectronica'  => 'Nota de Debito Electronica',
        'TiqueteElectronico'     => 'Tiquete Electronico',
    );

    private $respondido = false;
    private $blindado = false;

    public function __construct() {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        $this->load->helper(array('pos', 'compra'));
        $this->load->model(array('pos_model', 'hacienda_model', 'recibidos_model'));
        $this->load->library('form_validation');
    }

    public function index() {
        if ($_FILES) {
            $this->_cargar_archivos();
            return;
        }
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('documents_upload');
        $bc = array(array('link' => '#', 'page' => lang('documents_upload')));
        $meta = array('page_title' => lang('documents_upload'), 'bc' => $bc);
        $this->page_construct('cargadocumentos/index', $this->data, $meta);
    }

    /** Carga por arrastre: responde una lista <li> con el resultado de cada archivo. */
    private function _cargar_archivos() {
        $cedulaPropia = preg_replace('/\D/', '', (string) $this->Settings->cedula_emisor);
        $total = (int) $this->input->post('nbr_files');

        for ($i = 0; $i < $total; $i++) {
            $archivo = $_FILES['file_' . $i] ?? null;
            if (!$archivo) {
                continue;
            }
            $nombre = html_escape($archivo['name']);
            $li = function ($color, $texto) {
                echo '<li style="word-wrap:break-word;color:' . $color . '">' . $texto . '</li>';
            };

            if (strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION)) !== 'xml' || $archivo['error'] !== UPLOAD_ERR_OK) {
                $li('red', sprintf(lang('carga_no_es_xml'), $nombre));
                continue;
            }

            $crudo = (string) @file_get_contents($archivo['tmp_name']);
            // El archivo lo sube el usuario: se parsea con tope de tamano y sin
            // salir a la red a buscar entidades.
            $doc = xml_externo(preg_replace('/^[^<]*/', '', $crudo));
            if (!$doc) {
                $li('red', sprintf(lang('carga_xml_invalido'), $nombre));
                continue;
            }

            $raiz = $doc->getName();
            if ($raiz === 'MensajeHacienda') {
                $li('red', sprintf(lang('carga_es_mensaje_hacienda'), $nombre));
                continue;
            }
            if (!isset($this->tipos[$raiz])) {
                $li('red', sprintf(lang('carga_no_es_comprobante'), $nombre));
                continue;
            }
            if (!isset($doc->Receptor->Identificacion->Numero)) {
                $li('red', sprintf(lang('carga_sin_receptor'), $nombre));
                continue;
            }
            if (preg_replace('/\D/', '', (string) $doc->Receptor->Identificacion->Numero) !== $cedulaPropia) {
                $li('red', sprintf(lang('carga_otro_receptor'), $nombre, html_escape($this->Settings->nombre_emisor), $cedulaPropia));
                continue;
            }
            if ($this->hacienda_model->getHaciendaDocByClave((string) $doc->Clave)) {
                $li('#ff7800', sprintf(lang('carga_repetido'), $nombre));
                continue;
            }

            list($documento, $proveedor, $lineas) = compra_mapear_xml($doc, $crudo, $this->session->userdata('store_id'));
            if ($this->recibidos_model->guardarDocumento($documento, $proveedor, $lineas)) {
                $li('#009688', sprintf(lang('carga_ok'), $nombre));
            } else {
                $li('red', sprintf(lang('carga_no_guardo'), $nombre));
            }
        }
    }

    public function get_purchases_h() {
        $this->load->library('datatables');
        $this->datatables->select("id_documento, MontoTotalImpuesto, documento, nombre_emisor, NumeroCedulaEmisor, TotalFactura, ConsecutivoDocEmisor, FechaEmisionDoc, Estatus, CodigoMoneda, TipoCambio, gestion_estado, suppliers.company as alias");
        $this->datatables->from('documentoshacienda');
        $this->datatables->join('suppliers', 'suppliers.id = documentoshacienda.supplier_id', 'left');
        $this->datatables->where('store_id', $this->session->userdata('store_id'));
        // Una sola accion: la ventana de detalle ya trae los XML y la respuesta.
        $this->datatables->add_column("Actions", "$1", "id_documento");
        echo $this->datatables->generate();
    }

    /**
     * GET cargadocumentos/documento/<id>
     * Todo lo que pinta la ventana de detalle, en una sola respuesta. Los XML
     * van aparte: son kilobytes que casi nunca se miran.
     */
    public function documento($id = null) {
        $d = $this->_documento($id);

        $lineas = $this->recibidos_model->lineas($d->id_documento);

        $this->_json([
            'doc' => [
                'id'          => (int) $d->id_documento,
                'tipo'        => $d->documento,
                'clave'       => $d->ClaveDocEmisor,
                'consecutivo' => $d->ConsecutivoDocEmisor,
                'fecha'       => $d->FechaEmisionDoc,
                'moneda'      => $d->CodigoMoneda ?: 'CRC',
                'tipo_cambio' => (float) $d->TipoCambio,
                'condicion_venta' => $d->CondicionVenta,
                'medio_pago'  => $d->MedioPago,
                'referencia'  => $d->ClaveReferencia,
            ],
            'emisor' => [
                'nombre'   => $d->nombre_emisor,
                'cedula'   => $d->NumeroCedulaEmisor,
                'tipo_doc' => $d->tipo_doc_emisor,
                'correo'   => $d->correo_emisor,
                'telefono' => $d->telefono_emisor,
            ],
            'totales' => [
                'gravado'            => (float) $d->TotalGravado,
                'exento'             => (float) $d->TotalExento,
                'venta'              => (float) $d->TotalVenta,
                'venta_neta'         => (float) $d->TotalVentaNeta,
                'merc_gravadas'      => (float) $d->TotalMercanciasGravadas,
                'merc_exentas'       => (float) $d->TotalMercanciasExentas,
                'serv_gravados'      => (float) $d->TotalServGravados,
                'serv_exentos'       => (float) $d->TotalServExentos,
                'impuesto'           => (float) $d->MontoTotalImpuesto,
                'total'              => (float) $d->TotalFactura,
            ],
            'lineas' => array_map(function ($l) {
                return [
                    'codigo'    => $l->code,
                    'cabys'     => $l->cabys,
                    'nombre'    => $l->name,
                    'cantidad'  => (float) $l->quantity,
                    'unidad'    => $l->unit_of_measurement,
                    'precio'    => (float) $l->precio_unitario,
                    'descuento' => (float) $l->monto_descuento,
                    'tarifa'    => (float) $l->tarifa_impuesto,
                    'impuesto'  => (float) $l->monto_impuesto,
                    'subtotal'  => (float) $l->SubTotal,
                    'total'     => (float) $l->MontoTotalLinea,
                ];
            }, $lineas),
            'aceptacion' => [
                'estado'             => estado_aceptacion_info($d->Estatus),
                'mensaje'            => $d->Mensaje,
                'detalle'            => $d->DetalleMensaje,
                'condicion_impuesto' => $d->CondicionImpuesto,
                'impuesto_acreditar' => (float) $d->MontoTotalImpuestoAcreditar,
                'gasto_aplicable'    => (float) $d->MontoTotalDeGastoAplicable,
                'consecutivo'        => $d->consecutivo,
                'fecha'              => $d->Fecha_aceptacion,
                'editable'           => aceptacion_editable($d->Estatus),
            ],
            'gestion' => $this->_bloque_gestion($d, $lineas),
            'xml' => [
                'compra'    => !empty($d->xml_compra),
                'receptor'  => !empty($d->xml_mensajereceptor),
                'respuesta' => !empty($d->xml_hacienda),
            ],
        ]);
    }

    /**
     * Lo que necesita la pestaña Gestionar: proveedor, cada linea con su destino
     * y producto propuestos, y los montos fiscales de cada condicion.
     */
    private function _bloque_gestion($d, array $lineas) {
        $pendienteFiscal = aceptacion_editable($d->Estatus);
        $estado    = (string) $d->gestion_estado;
        $rechazado = !$pendienteFiscal && (string) $d->Mensaje === '3';
        $prov      = $this->recibidos_model->proveedor($d->supplier_id);
        $tc        = strtoupper((string) $d->CodigoMoneda) === 'CRC' || !$d->CodigoMoneda ? 1.0 : (float) $d->TipoCambio;

        $mapas     = $this->recibidos_model->mapasProveedor($d->supplier_id);
        $porCodigo = $this->recibidos_model->productosPorCodigo(array_merge(array_column($lineas, 'code'), array_column($lineas, 'codigo_barras')));
        $porCabys  = $this->recibidos_model->productosPorCabys(array_column($lineas, 'cabys'));

        $filas = array();
        foreach ($lineas as $l) {
            $la = (array) $l;
            if ($l->destino) {
                $rel = array('product_id' => (int) $l->product_id, 'factor' => (float) ($l->factor ?: 1), 'confianza' => $l->product_id ? 'guardada' : 'ninguna', 'origen' => '');
                $destino = $l->destino;
            } else {
                $rel = compra_relacionar($la, $mapas, $porCodigo, $porCabys);
                $destino = compra_destino_sugerido($la, $rel, $prov ? $prov->destino_habitual : null);
            }
            $cantidad = (float) $l->quantity;
            $filas[] = array(
                'id'        => (int) $l->id,
                'numero'    => (int) $l->numero_linea,
                'codigo'    => $l->code,
                'codigo_barras' => $l->codigo_barras,
                'cabys'     => $l->cabys,
                'nombre'    => $l->name,
                'cantidad'  => $cantidad,
                'unidad'    => $l->unit_of_measurement,
                'subtotal'  => (float) $l->SubTotal,
                'impuesto'  => (float) $l->impuesto_neto,
                'total'     => (float) $l->MontoTotalLinea,
                'tarifa'    => (float) $l->tarifa_impuesto,
                'costo_sin_iva' => $cantidad > 0 ? round((float) $l->SubTotal * $tc / $cantidad, 4) : 0,
                'costo_con_iva' => $cantidad > 0 ? round(((float) $l->SubTotal + (float) $l->impuesto_neto) * $tc / $cantidad, 4) : 0,
                'destino'   => $destino,
                'categoria_gasto_id' => (int) ($l->categoria_gasto_id ?: ($prov ? $prov->categoria_gasto_id : 0)),
                'relacion'  => $rel,
            );
        }

        $productos = $this->recibidos_model->productos(array_map(function ($f) { return $f['relacion']['product_id']; }, $filas));
        foreach ($filas as &$f) {
            $f['producto'] = $productos[$f['relacion']['product_id']] ?? null;
            if (!$f['producto']) {
                $f['relacion'] = array('product_id' => 0, 'factor' => $f['relacion']['factor'], 'confianza' => 'ninguna', 'origen' => '');
            }
        }
        unset($f);

        $impuesto = (float) $d->MontoTotalImpuesto;
        $total    = (float) $d->TotalFactura;
        $condicion = $pendienteFiscal
            ? compra_condicion_sugerida(array_column($filas, 'destino'), $impuesto, $prov ? $prov->condicion_iva_habitual : null)
            : (string) $d->CondicionImpuesto;

        $montos = array();
        foreach (compra_condiciones_iva() as $c) {
            $montos[$c] = compra_montos_fiscales('1', $c, $impuesto, $total);
        }

        $motivo = '';
        if (!$this->Admin) {
            $motivo = 'gestion_solo_admin';
        } elseif ($estado !== '') {
            $motivo = 'gestion_ya_registrada';
        } elseif ($rechazado) {
            $motivo = 'gestion_rechazado_sin_registro';
        }

        $impuestos = $this->db->select('codigo_tarifa, tasa_impuesto, descripcion_impuesto')
            ->where('codigo_impuesto', '01')->where('status_impuestos', '1')
            ->order_by('tasa_impuesto', 'DESC')->get('impuestos')->result();

        // Compra o gasto: lo que el proveedor suele ser; sin historial, un
        // documento de puros servicios es un gasto.
        $destinos = array_column($filas, 'destino');
        $soloServicios = $filas && count(array_filter($filas, function ($f) { return !compra_unidad_es_servicio($f['unidad']); })) === 0;
        if ($prov && $prov->destino_habitual) {
            $registrarComo = $prov->destino_habitual === 'inventario' ? 'compra' : 'gasto';
        } else {
            $registrarComo = ($soloServicios || !in_array('inventario', $destinos, true)) && !in_array('', $destinos, true) ? 'gasto' : 'compra';
        }
        if ($estado !== '' || array_filter(array_column($lineas, 'destino'))) {
            $registrarComo = in_array('inventario', $destinos, true) ? 'compra' : 'gasto';
        }

        return array(
            'puede'            => $motivo === '',
            'registrar_como'   => $registrarComo,
            'categoria_gasto_id' => (int) ($prov ? $prov->categoria_gasto_id : 0),
            'motivo'           => $motivo ? lang($motivo) : '',
            'estado'           => $estado ?: 'pendiente',
            'fiscal_pendiente' => $pendienteFiscal,
            'es_nota_credito'  => stripos((string) $d->documento, 'credito') !== false,
            'moneda'           => $d->CodigoMoneda ?: 'CRC',
            'tipo_cambio'      => $tc,
            'proveedor'        => array(
                'id'     => $prov ? (int) $prov->id : 0,
                'nombre' => $prov ? $prov->name : $d->nombre_emisor,
                'alias'  => $prov ? (string) $prov->company : '',
                'cedula' => $d->NumeroCedulaEmisor,
                'nuevo'  => $prov && $prov->created_at && strtotime($prov->created_at) >= strtotime('-1 day') && !$prov->destino_habitual,
            ),
            'lineas'     => $filas,
            'impuesto'   => $impuesto,
            'total'      => $total,
            'condicion'  => $condicion,
            'montos'     => $montos,
            'gestionado' => $estado === '' ? null : array(
                'por'    => $d->gestionado_por ? $this->_nombre_usuario($d->gestionado_por) : '',
                'en'     => $d->gestionado_en,
                'compra' => ($c = $this->db->select('id')->get_where('purchases', array('documento_id' => $d->id_documento), 1)->row()) ? (int) $c->id : null,
                'gastos' => (int) $this->db->where('documento_id', $d->id_documento)->count_all_results('expenses'),
            ),
            'opciones' => array(
                'mensajes'    => mensajes_receptor(),
                'condiciones' => array_intersect_key(condiciones_impuesto(), array_flip(compra_condiciones_iva())),
                'categorias_gasto' => array_map(function ($c) { return array('id' => (int) $c->id, 'nombre' => $c->nombre, 'clave' => $c->clave); }, $this->recibidos_model->categoriasGasto()),
                'categorias_producto' => array_map(function ($c) { return array('id' => (int) $c->id, 'nombre' => $c->name); }, $this->site->getAllCategories() ?: array()),
                'tarifas' => array_map(function ($t) { return array('codigo' => $t->codigo_tarifa, 'tasa' => (float) $t->tasa_impuesto, 'nombre' => $t->descripcion_impuesto); }, $impuestos),
            ),
        );
    }

    private function _nombre_usuario($id) {
        $u = $this->db->select('first_name, last_name, username')->get_where('users', array('id' => (int) $id), 1)->row();
        return $u ? trim($u->first_name . ' ' . $u->last_name) ?: $u->username : '';
    }

    /** Que columna guarda cada XML del documento. */
    private $xmls = [
        'compra'    => 'xml_compra',
        'receptor'  => 'xml_mensajereceptor',
        'respuesta' => 'xml_hacienda',
    ];

    /**
     * GET cargadocumentos/documento_xml/<id>/<cual>
     * El XML sangrado. cual: compra | receptor | respuesta
     */
    public function documento_xml($id = null, $cual = 'compra') {
        $d = $this->_documento($id);
        $campo = $this->xmls[$cual] ?? 'xml_compra';
        $this->_json(['xml' => xml_sangrado((string) ($d->$campo ?? ''))]);
    }

    /** GET cargadocumentos/descargar_xml/<id>/<cual> — el mismo XML como archivo. */
    public function descargar_xml($id = null, $cual = 'compra') {
        $d = $this->hacienda_model->getHaciendaDocByID((int) $id);
        if (!$d || $d->store_id != $this->session->userdata('store_id')) {
            show_404();
        }

        $campo = $this->xmls[$cual] ?? 'xml_compra';
        $xml   = (string) ($d->$campo ?? '');
        if ($xml === '') {
            show_404();
        }

        $this->load->helper('download');
        force_download(($d->ClaveDocEmisor ?: $d->id_documento) . '-' . $cual . '.xml', $xml);
    }

    /** GET cargadocumentos/buscar_producto?term= — productos para relacionar una linea. */
    public function buscar_producto() {
        $this->_solo_admin();
        $term = trim((string) $this->input->get('term', TRUE));
        if ($term === '') {
            $this->_json(['productos' => []]);
        }

        $this->load->model('purchases_model');
        $exacto = $this->purchases_model->getProductByCode($term);
        $filas  = $exacto ? array($exacto) : ($this->purchases_model->getProductNames($term, 15) ?: array());
        $productos = $this->recibidos_model->productos(array_map(function ($p) { return $p->id; }, $filas));

        $this->_json(['productos' => array_values($productos)]);
    }

    /**
     * POST cargadocumentos/crear_producto
     * Alta rapida desde una linea del proveedor. Exige lo mismo que Hacienda
     * pide para vender el producto: CABYS de 13 digitos, unidad y tarifa.
     */
    public function crear_producto() {
        $this->_solo_admin();

        $nombre = trim((string) $this->input->post('name'));
        $codigo = trim((string) $this->input->post('code'));
        $cabys  = preg_replace('/\D/', '', (string) $this->input->post('cabys'));
        $unidad = trim((string) $this->input->post('unit'));
        $categoria = (int) $this->input->post('category_id');
        $tarifa = $this->db->get_where('impuestos', array('codigo_impuesto' => '01', 'codigo_tarifa' => (string) $this->input->post('codigo_tarifa')), 1)->row();

        $error = '';
        if ($nombre === '' || mb_strlen($nombre) > 150) {
            $error = lang('producto_nombre_requerido');
        } elseif (!preg_match('/^[A-Za-z0-9._-]{2,50}$/', $codigo)) {
            $error = lang('producto_codigo_invalido');
        } elseif ($this->db->where('code', $codigo)->count_all_results('products') > 0) {
            $error = lang('producto_codigo_duplicado');
        } elseif (strlen($cabys) !== 13) {
            $error = lang('producto_cabys_invalido');
        } elseif (!$categoria || !$this->db->where('id', $categoria)->count_all_results('categories')) {
            $error = lang('producto_categoria_requerida');
        } elseif (!$tarifa) {
            $error = lang('producto_tarifa_requerida');
        } elseif ($unidad === '') {
            $error = lang('producto_unidad_requerida');
        }
        if ($error !== '') {
            $this->_json(['error' => $error], 422);
        }

        $costo  = max(0, (float) $this->input->post('cost'));
        $precio = max(0, (float) $this->input->post('price'));
        $data = array(
            'unit_of_measurement' => mb_substr($unidad, 0, 20),
            'type'        => compra_unidad_es_servicio($unidad) ? 'service' : 'standard',
            'code'        => $codigo,
            'name'        => $nombre,
            'category_id' => $categoria,
            'price'       => $precio,
            'cost'        => $costo,
            'margen'      => $costo > 0 && $precio > 0 ? round(($precio - $costo) / $costo * 100, 4) : 0,
            'tax'         => (int) round((float) $tarifa->tasa_impuesto),
            'id_tax'      => (int) $tarifa->id_impuesto,
            'tax_method'  => 1,
            'alert_quantity' => 0,
            'barcode_symbology' => 'CODE128',
            'cabys'       => $cabys,
        );

        $existencias = array();
        foreach ($this->site->getAllStores() ?: array() as $s) {
            $existencias[] = array('store_id' => $s->id, 'quantity' => 0, 'qty_fracc' => 0, 'price' => $precio);
        }

        $this->load->model('products_model');
        $id = $this->products_model->addProduct($data, $existencias);
        if (!$id) {
            $this->_json(['error' => lang('producto_no_guardo')], 500);
        }

        $p = $this->recibidos_model->productos(array($id));
        $this->_json(['producto' => $p[$id]]);
    }

    /**
     * POST cargadocumentos/gestionar/<id>
     *
     * Registra las decisiones sobre el documento y, si todavia no se respondio,
     * emite el mensaje receptor. Lo interno y el mensaje firmado se guardan en
     * la misma transaccion: si la firma falla no queda inventario a medias. El
     * envio a Hacienda va despues y puede reintentarse sin volver a registrar.
     *
     * Del cliente solo se aceptan las decisiones; cantidades, costos, impuestos
     * y la clave se releen de la base.
     */
    public function gestionar($id = null) {
        $this->_solo_admin();

        $datos = json_decode((string) $this->input->post('gestion'), true);
        if (!is_array($datos)) {
            $this->_json(['error' => lang('gestion_datos_invalidos')], 422);
        }
        $this->_documento($id);

        $this->_blindar_salida();
        set_time_limit(180);

        $this->db->trans_begin();
        try {
            $doc = $this->recibidos_model->bloquear($id);
            if ((string) $doc->gestion_estado !== '') {
                throw new DomainException(lang('gestion_ya_registrada'));
            }

            $pendiente = aceptacion_editable($doc->Estatus);
            if (!$pendiente && (string) $doc->Mensaje === '3') {
                throw new DomainException(lang('gestion_rechazado_sin_registro'));
            }

            $lineas  = $this->recibidos_model->lineas($doc->id_documento);
            $entrada = $this->_entrada_lineas($lineas, $datos);
            $impuesto = (float) $doc->MontoTotalImpuesto;

            $fiscal = $pendiente
                ? array(
                    'pendiente' => true,
                    'mensaje'   => (string) ($datos['mensaje'] ?? ''),
                    'condicion' => $impuesto > 0 ? (string) ($datos['condicion'] ?? '') : '',
                    'acreditar' => (float) ($datos['acreditar'] ?? 0),
                    'detalle'   => trim((string) ($datos['detalle'] ?? '')),
                )
                : array(
                    'pendiente' => false,
                    'mensaje'   => (string) $doc->Mensaje,
                    'condicion' => (string) $doc->CondicionImpuesto,
                    'acreditar' => (float) $doc->MontoTotalImpuestoAcreditar,
                );

            $errores = compra_validar_gestion(array_map(function ($l) { return (array) $l; }, $lineas), $entrada, $fiscal, $impuesto);
            if ($errores) {
                throw new DomainException(implode("\n", array_map(function ($e) {
                    return isset($e['linea']) ? sprintf(lang('gestion_error_linea'), $e['linea'], lang($e['clave'])) : lang($e['clave']);
                }, $errores)));
            }

            $montos = $pendiente
                ? compra_montos_fiscales($fiscal['mensaje'], $fiscal['condicion'], $impuesto, (float) $doc->TotalFactura, $fiscal['acreditar'])
                : array('acreditar' => (float) $doc->MontoTotalImpuestoAcreditar, 'gasto' => (float) $doc->MontoTotalDeGastoAplicable);
            $fiscal['acreditar'] = $montos['acreditar'];

            $supplier_id = $doc->supplier_id ?: $this->recibidos_model->asegurarProveedor(array(
                'name' => $doc->nombre_emisor, 'cf1' => $doc->tipo_doc_emisor, 'cf2' => $doc->NumeroCedulaEmisor,
                'phone' => $doc->telefono_emisor, 'email' => $doc->correo_emisor,
            ));
            $habitos = $fiscal['mensaje'] === '3' ? array('destino' => null, 'categoria' => null) : compra_habitos($entrada);
            $this->recibidos_model->actualizarProveedor($supplier_id, $datos['alias'] ?? '', $habitos, $fiscal['condicion']);
            $proveedor = $this->recibidos_model->proveedor($supplier_id);

            $resultado = array('compra' => null, 'gastos' => 0, 'movimientos' => 0);
            if ($fiscal['mensaje'] !== '3') {
                $resultado = $this->recibidos_model->registrar($doc, $lineas, $entrada, $fiscal, $proveedor);
            }

            if ($pendiente) {
                $this->_firmar_mensaje($doc, $fiscal, $montos);
            }

            $this->db->update('documentoshacienda', array(
                'supplier_id'    => $supplier_id,
                'gestion_estado' => $fiscal['mensaje'] === '3' ? 'rechazado' : 'registrado',
                'gestionado_por' => (int) $this->session->userdata('user_id'),
                'gestionado_en'  => date('Y-m-d H:i:s'),
            ), array('id_documento' => $doc->id_documento));

            if ($this->db->trans_status() === FALSE) {
                throw new RuntimeException(lang('gestion_no_guardo'));
            }
            $this->db->trans_commit();
        } catch (DomainException $e) {
            $this->db->trans_rollback();
            $this->_json(['error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', '[Cargadocumentos] gestion del documento ' . (int) $id . ': ' . $e->getMessage());
            $this->_json(['error' => $e->getMessage()], 500);
        }

        $this->load->model('AuditLog_model', 'audit_log');
        $this->audit_log->log('documento_recibido_gestionado', 'documento_recibido', (int) $doc->id_documento,
            'Respuesta ' . $fiscal['mensaje'] . ' · compra ' . ($resultado['compra'] ?: '-') . ' · gastos ' . $resultado['gastos'],
            (float) $doc->TotalFactura);

        $envio = $pendiente ? $this->_enviar_aceptacion($doc->id_documento) : null;

        $this->_json(['ok' => true, 'resultado' => $resultado, 'envio' => $envio]);
    }

    /**
     * POST cargadocumentos/reenviar/<id>
     * Vuelve a mandar el mensaje receptor ya firmado que no llego a Hacienda.
     */
    public function reenviar($id = null) {
        $this->_solo_admin();
        $d = $this->_documento($id);
        if (trim((string) $d->xml_mensajereceptor) === '' || !in_array(strtolower((string) $d->Estatus), array('procesando', 'error', 'recibido', '5'), true)) {
            $this->_json(['error' => lang('gestion_nada_que_reenviar')], 409);
        }
        $this->_blindar_salida();
        set_time_limit(120);
        $this->_json(['ok' => true, 'envio' => $this->_enviar_aceptacion($d->id_documento)]);
    }

    /**
     * Decision del usuario por linea, en el orden de las lineas guardadas.
     *
     * Registrado como gasto, todo el documento va a la categoria elegida salvo
     * las lineas que se marquen para ignorar o como activo.
     */
    private function _entrada_lineas(array $lineas, array $datos) {
        $comoGasto = ($datos['registrar_como'] ?? '') === 'gasto';
        $categoria = (int) ($datos['categoria_gasto_id'] ?? 0);

        $porId = array();
        foreach (($datos['lineas'] ?? array()) as $r) {
            if (isset($r['id'])) {
                $porId[(int) $r['id']] = $r;
            }
        }

        $entrada = array();
        foreach ($lineas as $l) {
            $r = $porId[(int) $l->id] ?? array();
            $entrada[] = array(
                'destino'            => (string) ($r['destino'] ?? ''),
                'product_id'         => (int) ($r['product_id'] ?? 0),
                'factor'             => (float) ($r['factor'] ?? 1),
                'categoria_gasto_id' => (int) ($r['categoria_gasto_id'] ?? 0),
                'aplicar_precio'     => !empty($r['aplicar_precio']),
                'precio'             => (float) ($r['precio'] ?? 0),
            );
            if ($comoGasto) {
                $i = count($entrada) - 1;
                if (!in_array($entrada[$i]['destino'], array('ignorar', 'activo'), true)) {
                    $entrada[$i]['destino'] = 'gasto';
                }
                $entrada[$i]['categoria_gasto_id'] = $categoria;
                $entrada[$i]['product_id'] = 0;
                $entrada[$i]['aplicar_precio'] = false;
            }
        }
        return $entrada;
    }

    /** Arma, firma y guarda el mensaje receptor. Lanza si algo falla. */
    private function _firmar_mensaje($doc, array $fiscal, array $montos) {
        $tipos = array('1' => '05', '2' => '06', '3' => '07');
        $entrada = array(
            'Mensaje'                     => $fiscal['mensaje'],
            'DetalleMensaje'              => $fiscal['detalle'],
            'CondicionImpuesto'           => $fiscal['condicion'],
            'MontoTotalImpuestoAcreditar' => $montos['acreditar'],
            'MontoTotalDeGastoAplicable'  => $montos['gasto'],
            'id_documento'                => $doc->id_documento,
            'numero_consecutivo'          => $this->recibidos_model->siguienteConsecutivoMensaje($tipos[$fiscal['mensaje']]),
            'ClaveDocEmisor'              => $doc->ClaveDocEmisor,
            'NumeroCedulaEmisor'          => $doc->NumeroCedulaEmisor,
            'FechaEmisionDoc'             => $doc->FechaEmisionDoc,
            'MontoTotalImpuesto'          => $doc->MontoTotalImpuesto,
            'TotalFactura'                => $doc->TotalFactura,
        );

        $this->load->library('Crearxml', NULL, 'Crearxml');
        $mensaje = $this->Crearxml->getMensajeReceptor($entrada);

        $certificado = './files/certificados/' . $this->Settings->ambiente . '/' . $this->Settings->certificado_ced . '.p12';
        if (!file_exists($certificado)) {
            throw new RuntimeException(lang('aceptacion_sin_certificado'));
        }
        $this->load->library('firmar', NULL, 'firmar');
        $firmado = $this->firmar->firmar($certificado, $this->Settings->certificado_pin, $mensaje[0], $mensaje[3]);
        if (!$firmado) {
            throw new RuntimeException(lang('aceptacion_firma_fallo'));
        }

        $guardado = $this->hacienda_model->setRespuesta($mensaje, array(
            'DetalleMensaje'              => mb_substr($fiscal['detalle'], 0, 160),
            'CondicionImpuesto'           => $fiscal['condicion'],
            'MontoTotalImpuestoAcreditar' => $montos['acreditar'],
            'MontoTotalDeGastoAplicable'  => $montos['gasto'],
        ), $doc->id_documento, $firmado);
        if (!$guardado) {
            throw new RuntimeException(lang('aceptacion_no_guardo'));
        }
    }

    /**
     * Manda a Hacienda el mensaje receptor ya firmado y devuelve en que quedo.
     * MensajeAprobacion() no pide token: sin getTokenH() el POST sale sin
     * autorizacion y falla en silencio.
     */
    private function _enviar_aceptacion($id_documento) {
        $this->load->library('Apiclient', NULL, 'ApiClient');
        try {
            $this->ApiClient->getTokenH();
            $this->ApiClient->MensajeAprobacion($this->hacienda_model->getHaciendaDocByID($id_documento));
            $this->ApiClient->CloseTokenH();
        } catch (\Throwable $e) {
            log_message('error', '[Cargadocumentos] envio aceptacion: ' . $e->getMessage());
        }

        $fila = $this->hacienda_model->getHaciendaDocByID($id_documento);
        $estado = estado_aceptacion_info($fila->Estatus);
        return array('estado' => $estado, 'confirmado' => in_array($estado['clave'], array('aceptado', 'rechazado', 'parcial'), true));
    }

    private function _documento($id) {
        $d = $this->hacienda_model->getHaciendaDocByID((int) $id);
        if (!$d || $d->store_id != $this->session->userdata('store_id')) {
            $this->_json(['error' => lang('access_denied')], 404);
        }
        return $d;
    }

    private function _solo_admin() {
        if (!$this->Admin) {
            $this->_json(['error' => lang('gestion_solo_admin')], 403);
        }
    }

    /**
     * Convierte en JSON cualquier corte de Apiclient: getTokenH() informa sus
     * fallos con echo + exit y el navegador daria el envio por bueno.
     */
    private function _blindar_salida() {
        $this->blindado = true;
        ob_start();
        register_shutdown_function(function () {
            if ($this->respondido) {
                return;
            }
            $suelto = trim(strip_tags((string) ob_get_clean()));
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8', true, 502);
            }
            echo json_encode(array(
                'error' => lang('gestion_envio_interrumpido') . ($suelto !== '' ? ' ' . $suelto : ''),
            ), JSON_UNESCAPED_UNICODE);
        });
    }

    private function _json($data, $status = 200) {
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
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE))
            ->_display();
        // _display() explicito: CI3 vuelca la salida al terminar el controlador
        // y este exit no llega ahi.
        exit;
    }
}

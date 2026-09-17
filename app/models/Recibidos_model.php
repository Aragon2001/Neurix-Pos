<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Comprobantes que la tienda recibe como receptor: guardado, relacion de sus
 * lineas con productos propios y registro en inventario o gastos.
 */
class Recibidos_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('compra');
    }

    /* ═══════════════════════════ GUARDADO ═══════════════════════════ */

    /**
     * Guarda un comprobante leido con compra_mapear_xml().
     *
     * @return int|false id_documento
     */
    public function guardarDocumento(array $documento, array $proveedor, array $lineas)
    {
        $this->db->trans_begin();

        $documento['supplier_id'] = $this->asegurarProveedor($proveedor);
        $existente = $this->db->get_where('documentoshacienda', array('ClaveDocEmisor' => $documento['ClaveDocEmisor']), 1)->row();

        if ($existente) {
            $id = (int) $existente->id_documento;
            $this->db->update('documentoshacienda', $this->_recortar('documentoshacienda', $documento), array('id_documento' => $id));
        } else {
            $this->db->insert('documentoshacienda', $this->_recortar('documentoshacienda', $documento));
            $id = (int) $this->db->insert_id();
            // Todo el modulo direcciona el documento por id_documento, no por id.
            $this->db->update('documentoshacienda', array('id_documento' => $id), array('id' => $id));
        }

        $this->_guardarLineas($id, $lineas);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return $id;
    }

    /**
     * Vuelve a leer las lineas de un documento ya cargado desde su XML.
     * Conserva lo que el usuario ya decidio sobre cada linea.
     */
    public function reprocesar($fila)
    {
        $xml = preg_replace('/^[^<]*/', '', (string) $fila->xml_compra);
        $doc = $xml !== '' ? xml_externo($xml) : null;
        if (!$doc || !isset($doc->Clave)) {
            return false;
        }

        list($documento, $proveedor, $lineas) = compra_mapear_xml($doc, $fila->xml_compra, $fila->store_id);
        $campos = array_intersect_key($documento, array_flip(array(
            'MedioPago', 'CodigoMoneda', 'TipoCambio', 'ClaveReferencia',
        )));
        $campos['supplier_id'] = $this->asegurarProveedor($proveedor);

        $this->db->update('documentoshacienda', $campos, array('id_documento' => $fila->id_documento));
        $this->_guardarLineas((int) $fila->id_documento, $lineas);
        return true;
    }

    /**
     * Crea el proveedor si no existe. De uno existente solo completa lo vacio:
     * el nombre comercial y los datos que el usuario corrigio no se pisan.
     *
     * @return int|null
     */
    public function asegurarProveedor(array $p)
    {
        $cedula = trim((string) ($p['cf2'] ?? ''));
        if ($cedula === '') {
            return null;
        }

        $actual = $this->db->get_where('suppliers', array('cf2' => $cedula), 1)->row();
        if (!$actual) {
            $this->db->insert('suppliers', $this->_recortar('suppliers', array_merge($p, array(
                'created_at' => date('Y-m-d H:i:s'),
            ))));
            return (int) $this->db->insert_id();
        }

        $faltantes = array();
        foreach (array('name', 'cf1', 'phone', 'email') as $c) {
            if (trim((string) $actual->$c) === '' && trim((string) ($p[$c] ?? '')) !== '') {
                $faltantes[$c] = $p[$c];
            }
        }
        if ($faltantes) {
            $this->db->update('suppliers', $faltantes, array('id' => $actual->id));
        }
        return (int) $actual->id;
    }

    private function _guardarLineas($documento_id, array $lineas)
    {
        $previas = array();
        foreach ($this->lineas($documento_id) as $l) {
            $previas[(int) $l->numero_linea] = $l;
        }

        $this->db->delete('documentositems', array('documento_id' => $documento_id));
        foreach ($lineas as $l) {
            $l['documento_id'] = $documento_id;
            $antes = $previas[(int) $l['numero_linea']] ?? null;
            if ($antes) {
                foreach (array('destino', 'product_id', 'factor', 'categoria_gasto_id') as $c) {
                    $l[$c] = $antes->$c;
                }
            }
            $this->db->insert('documentositems', $this->_recortar('documentositems', $l));
        }
    }

    /**
     * Deja solo las columnas que la tabla tiene: una clave de mas tumba el
     * INSERT sin decir cual sobraba.
     */
    private function _recortar($tabla, array $datos)
    {
        static $campos = array();
        if (!isset($campos[$tabla])) {
            $campos[$tabla] = array_flip($this->db->list_fields($tabla));
        }
        return array_intersect_key($datos, $campos[$tabla]);
    }

    /* ═══════════════════════════ LECTURA ═══════════════════════════ */

    public function lineas($documento_id)
    {
        return $this->db->order_by('numero_linea', 'ASC')->order_by('id', 'ASC')
            ->get_where('documentositems', array('documento_id' => $documento_id))->result();
    }

    public function proveedor($id)
    {
        return $id ? $this->db->get_where('suppliers', array('id' => $id), 1)->row() : null;
    }

    /** Relaciones aprendidas del proveedor, solo hacia productos que siguen existiendo. */
    public function mapasProveedor($supplier_id)
    {
        if (!$supplier_id) {
            return array();
        }
        $pp = $this->db->dbprefix('proveedor_producto');
        $pr = $this->db->dbprefix('products');
        return $this->db->query(
            "SELECT m.id, m.codigo, m.descripcion, m.product_id, m.factor
               FROM `{$pp}` m JOIN `{$pr}` p ON p.id = m.product_id
              WHERE m.supplier_id = ?
              ORDER BY m.ultimo_uso DESC",
            array((int) $supplier_id)
        )->result_array();
    }

    /** @return array productos indexados por codigo */
    public function productosPorCodigo(array $codigos)
    {
        $codigos = array_values(array_unique(array_filter($codigos, 'strlen')));
        if (!$codigos) {
            return array();
        }
        $r = array();
        foreach ($this->db->select('id, code, name')->where_in('code', $codigos)->get('products')->result_array() as $p) {
            $r[$p['code']] = $p;
        }
        return $r;
    }

    /** @return array listas de productos indexadas por CABYS */
    public function productosPorCabys(array $cabys)
    {
        $cabys = array_values(array_unique(array_filter($cabys, 'strlen')));
        if (!$cabys) {
            return array();
        }
        $r = array();
        foreach ($this->db->select('id, name, cabys')->where_in('cabys', $cabys)->get('products')->result_array() as $p) {
            $r[$p['cabys']][] = $p;
        }
        return $r;
    }

    /** @return array productos indexados por id, con lo necesario para sugerir precio */
    public function productos(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return array();
        }
        $r = array();
        $filas = $this->db->select('id, code, name, cost, price, margen, tax, tax_method, cabys, unit_of_measurement')
            ->where_in('id', $ids)->get('products')->result_array();
        foreach ($filas as $p) {
            $r[(int) $p['id']] = $p;
        }
        return $r;
    }

    public function categoriasGasto()
    {
        return $this->db->order_by('nombre', 'ASC')->get_where('categorias_gasto', array('activo' => 1))->result();
    }

    /** Siguiente numero de la serie del mensaje receptor de ese tipo (05, 06 o 07). */
    public function siguienteConsecutivoMensaje($tipo)
    {
        $dh = $this->db->dbprefix('documentoshacienda');
        $fila = $this->db->query(
            "SELECT consecutivo FROM `{$dh}`
              WHERE LENGTH(consecutivo) = 20 AND SUBSTRING(consecutivo, 9, 2) = ? AND SUBSTRING(consecutivo, 4, 5) = ?
              ORDER BY SUBSTRING(consecutivo, 11, 10) DESC LIMIT 1",
            array($tipo, str_pad((string) $this->Settings->terminal_pos, 5, '0', STR_PAD_LEFT))
        )->row();

        $this->load->model('hacienda_model');
        return $this->hacienda_model->ultimo_consecutivo($tipo, $fila ? $fila->consecutivo : null) + 1;
    }

    /** Relee el documento bloqueando la fila hasta el fin de la transaccion. */
    public function bloquear($id)
    {
        $dh = $this->db->dbprefix('documentoshacienda');
        return $this->db->query("SELECT * FROM `{$dh}` WHERE id_documento = ? FOR UPDATE", array((int) $id))->row();
    }

    /* ═══════════════════════════ REGISTRO ═══════════════════════════ */

    /**
     * Aplica las decisiones del usuario: entrada de inventario con su compra,
     * gastos por categoria y relaciones aprendidas. Debe correr dentro de una
     * transaccion abierta por quien llama.
     *
     * @param object $doc       fila de documentoshacienda
     * @param array  $lineas    filas de documentositems, en el orden de $entrada
     * @param array  $entrada   decision por linea (ver compra_validar_gestion)
     * @param array  $fiscal    condicion y acreditar
     * @param object $proveedor fila de suppliers
     * @return array{compra: int|null, gastos: int, movimientos: int}
     * @throws RuntimeException si un movimiento de inventario no se puede aplicar
     */
    public function registrar($doc, array $lineas, array $entrada, array $fiscal, $proveedor)
    {
        $this->load->model('products_model');

        $store_id  = (int) $doc->store_id;
        $user_id   = (int) $this->session->userdata('user_id');
        $esNota    = stripos((string) $doc->documento, 'credito') !== false;
        $tc        = strtoupper((string) $doc->CodigoMoneda) === 'CRC' || !$doc->CodigoMoneda ? 1.0 : (float) $doc->TipoCambio;
        $noAcred   = compra_proporcion_no_acreditable((string) $fiscal['condicion'], (float) $doc->MontoTotalImpuesto, (float) $fiscal['acreditar']);
        $nombre    = trim((string) ($proveedor->company ?: $proveedor->name));
        $rotulo    = ($esNota ? 'Nota de credito ' : 'Compra ') . $doc->ConsecutivoDocEmisor . ' · ' . $nombre;
        $productos = $this->productos(array_column($entrada, 'product_id'));
        $activos   = $this->db->get_where('categorias_gasto', array('clave' => 'activos'), 1)->row();

        $items = array();
        $gastos = array();
        $movimientos = 0;
        $compra = array('total' => 0, 'impuesto' => 0, 'descuento' => 0);

        foreach (array_values($lineas) as $i => $l) {
            $e = $entrada[$i];
            $la = (array) $l;
            $destino = $e['destino'];

            $cambios = array('destino' => $destino, 'product_id' => null, 'factor' => null, 'categoria_gasto_id' => null);

            if ($destino === 'inventario') {
                $pid = (int) $e['product_id'];
                if (!isset($productos[$pid])) {
                    throw new RuntimeException(sprintf(lang('gestion_producto_inexistente'), (int) $l->numero_linea));
                }
                $factor = (float) $e['factor'];
                $costo = compra_costo_linea($la, $noAcred, $factor, $tc);
                $unidades = round((float) $l->quantity * $factor, 4);

                $mov = $esNota
                    ? array('modo' => 'salida', 'permitir_negativo' => true)
                    : array('modo' => 'entrada', 'cost' => $costo['unitario'], 'price' => !empty($e['aplicar_precio']) ? (float) $e['precio'] : null);
                $r = $this->products_model->aplicarMovimiento($mov + array(
                    'product_id'      => $pid,
                    'store_id'        => $store_id,
                    'quantity'        => $unidades,
                    'descripcion_mov' => $rotulo,
                ));
                if (!$r['ok']) {
                    throw new RuntimeException(sprintf(lang('gestion_movimiento_fallo'), (int) $l->numero_linea, $r['error']));
                }
                $movimientos++;

                if (!$esNota) {
                    $items[] = array(
                        'product_id'        => $pid,
                        'quantity'          => $unidades,
                        'cost'              => $costo['unitario'],
                        'subtotal'          => round((float) $l->SubTotal * $tc, 4),
                        'tax_code'          => $l->codigo_tarifa ?: null,
                        'tax_rate'          => (float) $l->tarifa_impuesto,
                        'tax_amount'        => $costo['impuesto'],
                        'quantity_received' => $unidades,
                    );
                    $compra['total']     += ((float) $l->SubTotal + (float) $l->impuesto_neto) * $tc;
                    $compra['impuesto']  += $costo['impuesto'];
                    $compra['descuento'] += (float) $l->monto_descuento * $tc;
                }

                $this->aprender((int) $proveedor->id, $la, $pid, $factor);
                if (!$esNota) {
                    $this->db->where('supplier_id IS NULL', null, false)
                        ->update('products', array('supplier_id' => (int) $proveedor->id), array('id' => $pid));
                }
                $cambios['product_id'] = $pid;
                $cambios['factor'] = $factor;
            } elseif ($destino === 'gasto' || $destino === 'activo') {
                $cat = $destino === 'activo' && $activos ? (int) $activos->id : (int) $e['categoria_gasto_id'];
                if (!isset($gastos[$cat])) {
                    $gastos[$cat] = array('monto' => 0, 'impuesto' => 0);
                }
                $gastos[$cat]['monto']    += ((float) $l->SubTotal + (float) $l->impuesto_neto) * $tc;
                $gastos[$cat]['impuesto'] += (float) $l->impuesto_neto * $tc;
                $cambios['categoria_gasto_id'] = $cat;
            }

            $this->db->update('documentositems', $cambios, array('id' => $l->id));
        }

        $signo = $esNota ? -1 : 1;
        $nota  = mb_substr($nombre . ' · ' . $doc->documento . ' ' . $doc->ClaveDocEmisor, 0, 1000);

        $compra_id = null;
        if ($items) {
            $plazo = (int) ($proveedor->plazo_pago_dias ?? 0);
            $credito = (string) $doc->CondicionVenta === '02';
            $fecha = date('Y-m-d H:i:s', strtotime((string) $doc->FechaEmisionDoc) ?: time());
            $this->db->insert('purchases', array(
                'date'                => $fecha,
                'reference'           => $doc->ConsecutivoDocEmisor,
                'supplier_id'         => (int) $proveedor->id,
                'supplier_invoice_no' => mb_substr((string) $doc->ConsecutivoDocEmisor, 0, 50),
                'note'                => $nota,
                'received'            => 1,
                'status'              => 'recibida',
                'payment_status'      => $credito ? 'pendiente' : 'pagada',
                'due_date'            => $credito && $plazo > 0 ? date('Y-m-d', strtotime($fecha . ' +' . $plazo . ' days')) : null,
                'total'               => round($compra['total'], 4),
                'tax_total'           => round($compra['impuesto'], 4),
                'discount_total'      => round($compra['descuento'], 4),
                'currency'            => 'CRC',
                'exchange_rate'       => 1,
                'created_by'          => $user_id,
                'store_id'            => $store_id,
                'documento_id'        => (int) $doc->id_documento,
            ));
            $compra_id = (int) $this->db->insert_id();
            foreach ($items as $it) {
                $it['purchase_id'] = $compra_id;
                $this->db->insert('purchase_items', $it);
            }
        }

        foreach ($gastos as $cat => $g) {
            $this->db->insert('expenses', array(
                'date'        => date('Y-m-d H:i:s', strtotime((string) $doc->FechaEmisionDoc) ?: time()),
                'reference'   => $doc->ConsecutivoDocEmisor,
                'amount'      => round($signo * $g['monto'], 4),
                'impuesto'    => round($signo * $g['impuesto'], 4),
                'note'        => $nota,
                'created_by'  => $user_id,
                'category_id' => $cat ?: null,
                'store_id'    => $store_id,
                'supplier_id' => (int) $proveedor->id,
                'documento_id' => (int) $doc->id_documento,
            ));
        }

        return array('compra' => $compra_id, 'gastos' => count($gastos), 'movimientos' => $movimientos);
    }

    /**
     * Recuerda a que producto corresponde la linea para la proxima factura del
     * mismo proveedor. Se guarda por codigo y, si no trae, por descripcion.
     */
    public function aprender($supplier_id, array $linea, $product_id, $factor)
    {
        if (!$supplier_id || !$product_id) {
            return;
        }
        $codigo = trim((string) ($linea['code'] ?? ''));
        $codigo = $codigo === '0' ? '' : mb_substr($codigo, 0, 60);
        $descripcion = mb_substr(compra_texto_normalizado($linea['name'] ?? ''), 0, 255);

        $donde = array('supplier_id' => (int) $supplier_id);
        if ($codigo !== '') {
            $donde['codigo'] = $codigo;
        } else {
            $donde['codigo'] = '';
            $donde['descripcion'] = $descripcion;
        }

        $datos = array(
            'descripcion' => $descripcion,
            'cabys'       => mb_substr((string) ($linea['cabys'] ?? ''), 0, 13) ?: null,
            'product_id'  => (int) $product_id,
            'factor'      => (float) $factor,
            'ultimo_uso'  => date('Y-m-d H:i:s'),
        );

        $previo = $this->db->get_where('proveedor_producto', $donde, 1)->row();
        if ($previo) {
            $this->db->set('usos', 'usos + 1', false)->update('proveedor_producto', $datos, array('id' => $previo->id));
        } else {
            $this->db->insert('proveedor_producto', $donde + $datos + array('usos' => 1));
        }
    }

    /** Proveedor: nombre comercial y lo que suele comprarse, para proponerlo la proxima vez. */
    public function actualizarProveedor($id, $alias, array $habitos, $condicion)
    {
        $datos = array('company' => mb_substr(trim((string) $alias), 0, 150) ?: null, 'updated_at' => date('Y-m-d H:i:s'));
        if ($habitos['destino']) {
            $datos['destino_habitual'] = $habitos['destino'];
        }
        if ($habitos['categoria']) {
            $datos['categoria_gasto_id'] = $habitos['categoria'];
        }
        if (in_array($condicion, compra_condiciones_iva(), true)) {
            $datos['condicion_iva_habitual'] = $condicion;
        }
        $this->db->update('suppliers', $datos, array('id' => (int) $id));
    }
}

<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Motor de informes: una consulta, tres salidas.
 *
 * El problema que resuelve es concreto. Cada informe tenía una consulta para la
 * pantalla y, cuando se le añadía una exportación, otra consulta parecida pero
 * no igual; el resultado eran cifras distintas del mismo período según por
 * dónde se mirara. Acá el dato se resuelve **una sola vez** en un documento de
 * informe, y pantalla, PDF y Excel se limitan a pintarlo.
 *
 *      definición + filtro
 *              ↓
 *        Reporte_model            ← la aritmética, una sola vez
 *              ↓
 *      documento de informe       ← filas, totales, KPIs, auditoría
 *              ↓
 *      ┌───────┬───────┬───────┐
 *      │ JSON  │  PDF  │ Excel │
 *      └───────┴───────┴───────┘
 *
 * Los totales los calcula `_totales()` sobre las filas ya resueltas, así que la
 * fila de totales del Excel no puede diferir de la de la pantalla: son la misma
 * suma sobre el mismo arreglo.
 */
class Report_engine
{
    /** @var CI_Controller */
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->helper(array('reportes', 'compra'));
        $this->CI->load->model('reporte_model');
        $this->CI->load->model('auditoria_model');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CATÁLOGO
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Informes disponibles.
     *
     * Cada entrada declara de dónde salen las filas y qué columnas tiene. Con
     * eso el motor ya sabe montar la pantalla, el PDF y el Excel: no hay que
     * escribir una exportación por informe.
     */
    public function catalogo()
    {
        $col = function ($clave, $titulo, $formato = null, $ancho = 24, $total = false) {
            return array('clave' => $clave, 'titulo' => $titulo, 'formato' => $formato,
                         'ancho' => $ancho, 'total' => $total);
        };

        // Columnas comunes a los informes agrupados: el mismo bloque en todos
        // garantiza que «por día» y «por usuario» se lean igual.
        $agrupado = function ($totalizaDocs) use ($col) {
            return array(
                $col('documentos', 'Docs.',     'entero', 14, $totalizaDocs),
                $col('unidades',   'Unidades',  'numero', 16, true),
                $col('base',       'Base',      'moneda', 24, true),
                $col('impuesto',   'IVA',       'moneda', 22, true),
                $col('descuento',  'Descuento', 'moneda', 22, true),
                $col('total',      'Total',     'moneda', 26, true),
                $col('costo',      'Costo',     'moneda', 22, true),
                $col('utilidad',   'Utilidad',  'moneda', 24, true),
                $col('margen',     'Margen',    'porcentaje', 16),
            );
        };

        // Agrupaciones por debajo del comprobante: una venta con tres productos
        // aparece en tres filas, así que sumar su columna de comprobantes daría
        // «372 comprobantes» donde hubo 148. Se muestra el recuento por fila,
        // pero no se totaliza; el número real de comprobantes está en el KPI.
        $porLinea = array('producto', 'categoria', 'cabys', 'tarifa');

        $cat = array();

        foreach ($this->CI->reporte_model->agrupaciones() as $k => $g) {
            $cat['ventas_' . $k] = array(
                'titulo'  => $g['titulo'],
                'unidad'  => $g['unidad'],
                'grupo'   => 'Ventas',
                'origen'  => 'agrupacion',
                'por'     => $k,
                'nota'    => in_array($k, $porLinea, true)
                    ? 'La columna de comprobantes cuenta en cuántos aparece cada ' . rtrim($g['unidad'], 's')
                      . '. No se totaliza porque una venta con varias líneas aparecería más de una vez; '
                      . 'el número real de comprobantes del período es el del indicador de cabecera.'
                    : '',
                'columnas' => array_merge(
                    array($col('nombre', $this->_rotulo($k), $k === 'dia' ? 'fecha' : null, 34)),
                    $agrupado(!in_array($k, $porLinea, true))
                ),
            );
        }

        $cat['ventas_medio_pago'] = array(
            'titulo' => 'Ventas por medio de pago', 'unidad' => 'medios', 'grupo' => 'Ventas',
            'origen' => 'medio_pago',
            'columnas' => array(
                $col('nombre', 'Medio de pago', null, 40),
                $col('documentos', 'Comprobantes', 'entero', 24, true),
                $col('movimientos', 'Movimientos', 'entero', 24, true),
                $col('total', 'Cobrado', 'moneda', 30, true),
            ),
        );

        $cat['ventas_detalle'] = array(
            'titulo' => 'Detalle de comprobantes', 'unidad' => 'comprobantes', 'grupo' => 'Ventas',
            'origen' => 'ventas',
            'columnas' => array(
                $col('date', 'Fecha', 'fechahora', 26),
                $col('consecutivo', 'Consecutivo', 'texto', 34),
                $col('tipo_doc', 'Tipo', null, 12),
                $col('customer_name', 'Cliente', null, 40),
                $col('identificacion', 'Identificación', 'texto', 24),
                $col('usuario', 'Usuario', null, 28),
                $col('medios_pago', 'Medio de pago', null, 26),
                $col('estatus_hacienda', 'Estado', 'estado', 20),
                $col('base', 'Base', 'moneda', 24, true),
                $col('impuesto', 'IVA', 'moneda', 22, true),
                $col('total', 'Total', 'moneda', 26, true),
            ),
        );

        $cat['ventas_lineas'] = array(
            'titulo' => 'Detalle línea a línea', 'unidad' => 'líneas', 'grupo' => 'Ventas',
            'origen' => 'lineas',
            'columnas' => array(
                $col('date', 'Fecha', 'fecha', 20),
                $col('consecutivo', 'Comprobante', 'texto', 32),
                $col('product_code', 'Código', 'texto', 20),
                $col('product_name', 'Producto', null, 40),
                $col('cabys', 'CABYS', 'texto', 20),
                $col('categoria', 'Categoría', null, 24),
                $col('quantity', 'Cant.', 'numero', 14, true),
                $col('net_unit_price', 'P. unitario', 'moneda', 22),
                $col('tarifa', 'Tarifa', 'porcentaje', 14),
                $col('base', 'Base', 'moneda', 22, true),
                $col('impuesto', 'IVA', 'moneda', 20, true),
                $col('total', 'Total', 'moneda', 24, true),
            ),
        );

        $cat['notas_credito'] = array(
            'titulo' => 'Notas de crédito', 'unidad' => 'notas', 'grupo' => 'Fiscal',
            'origen' => 'notas_credito',
            'columnas' => array(
                $col('date', 'Fecha', 'fechahora', 24),
                $col('consecutivo', 'Consecutivo', 'texto', 34),
                $col('consecutivo_origen', 'Documento corregido', 'texto', 34),
                $col('customer_name', 'Cliente', null, 38),
                $col('motivo', 'Motivo', null, 44),
                $col('estatus_hacienda', 'Estado', 'estado', 20),
                $col('total_origen', 'Total original', 'moneda', 26, true),
                $col('grand_total', 'Total nota', 'moneda', 26, true),
            ),
        );

        $cat['notas_debito'] = array(
            'titulo' => 'Notas de débito', 'unidad' => 'notas', 'grupo' => 'Fiscal',
            'origen' => 'notas_debito',
            'columnas' => array(
                $col('date', 'Fecha', 'fechahora', 24),
                $col('consecutivo', 'Consecutivo', 'texto', 34),
                $col('consecutivo_origen', 'Documento corregido', 'texto', 34),
                $col('customer_name', 'Cliente', null, 38),
                $col('estatus_hacienda', 'Estado', 'estado', 20),
                $col('total_tax', 'IVA', 'moneda', 22, true),
                $col('grand_total', 'Total nota', 'moneda', 26, true),
            ),
        );

        $cat['anulaciones'] = array(
            'titulo' => 'Comprobantes anulados', 'unidad' => 'anulaciones', 'grupo' => 'Fiscal',
            'origen' => 'anulaciones',
            'columnas' => array(
                $col('created_at', 'Anulado el', 'fechahora', 24),
                $col('consecutivo', 'Comprobante', 'texto', 32),
                $col('customer_name', 'Cliente', null, 34),
                $col('tipo', 'Tipo', null, 18),
                $col('motivo', 'Justificación', null, 46),
                $col('usuario', 'Autorizó', null, 26),
                $col('consecutivo_nota', 'Nota de crédito', 'texto', 32),
                $col('monto_devuelto', 'Devuelto', 'moneda', 22, true),
                $col('grand_total', 'Total anulado', 'moneda', 26, true),
            ),
        );

        $cat['conciliacion'] = array(
            'titulo' => 'Conciliación con Hacienda', 'unidad' => 'comprobantes', 'grupo' => 'Auditoría',
            'origen' => 'conciliacion',
            'columnas' => array(
                $col('fecha', 'Fecha', 'fechahora', 24),
                $col('consecutivo', 'Consecutivo', 'texto', 32),
                $col('clave', 'Clave', 'texto', 56),
                $col('cliente', 'Cliente', null, 34),
                $col('estado', 'Estado', 'estado', 20),
                $col('total', 'Total', 'moneda', 24, true),
                $col('problemas', 'Diferencias encontradas', null, 60),
            ),
        );

        $cat['anomalias'] = array(
            'titulo' => 'Detector de anomalías', 'unidad' => 'hallazgos', 'grupo' => 'Auditoría',
            'origen' => 'anomalias',
            'columnas' => array(
                $col('nivel', 'Nivel', null, 14),
                $col('titulo', 'Anomalía', null, 40),
                $col('descripcion', 'Detalle', null, 62),
                $col('documento', 'Documento', 'texto', 26),
                $col('fecha', 'Fecha', 'fecha', 20),
                $col('monto', 'Monto', 'moneda', 24),
                $col('causa', 'Causa probable', null, 46),
                $col('accion', 'Acción recomendada', null, 52),
            ),
        );

        // El D-104 trae su propia fila de cierre («DÉBITO FISCAL DEL PERÍODO»):
        // ninguna columna se totaliza o esa fila se sumaría dos veces.
        $cat['reabastecimiento'] = array(
            'titulo' => 'Reabastecimiento sugerido', 'unidad' => 'productos', 'grupo' => 'Inventario',
            'origen' => 'reabastecimiento',
            'nota'   => 'La venta diaria sale de las ventas del período elegido. Se sugiere reponer cuando la '
                      . 'existencia no alcanza el mínimo o no cubre ' . COMPRA_DIAS_ENTREGA . ' días de venta, y la cantidad '
                      . 'cubre ' . COMPRA_DIAS_COBERTURA . ' días más el mínimo, redondeada al empaque del proveedor. '
                      . 'El proveedor es el preferido del producto o el de su última compra.',
            'columnas' => array(
                $col('proveedor', 'Proveedor', null, 30),
                $col('code', 'Código', 'texto', 18),
                $col('nombre', 'Producto', null, 38),
                $col('existencia', 'Existencia', 'numero', 14),
                $col('minimo', 'Mínimo', 'numero', 12),
                $col('venta_diaria', 'Venta/día', 'numero', 12),
                $col('cobertura', 'Cubre', null, 12),
                $col('sugerido', 'Comprar', 'numero', 14),
                $col('empaque', 'Empaque', null, 10),
                $col('costo', 'Último costo', 'moneda', 20),
                $col('estimado', 'Estimado', 'moneda', 22, true),
                $col('alternativa', 'Más barato con', null, 30),
            ),
        );

        $cat['d104'] = array(
            'titulo' => 'D-104 · Resumen de IVA por tarifa', 'unidad' => 'renglones', 'grupo' => 'Fiscal',
            'origen' => 'd104',
            'nota'   => 'El renglón final ya consolida el débito fiscal del período. Las casillas de '
                      . 'crédito fiscal por compras, proporcionalidad y retenciones no se pueden alimentar '
                      . 'desde este sistema y se enumeran aparte en vez de declararse en cero.',
            'columnas' => array(
                $col('concepto', 'Concepto', null, 46),
                $col('base', 'Base imponible', 'moneda', 30),
                $col('impuesto', 'Impuesto', 'moneda', 28),
                $col('documentos', 'Comprobantes', 'entero', 22),
            ),
        );

        $cat['d151'] = array(
            'titulo' => 'D-151 · Clientes, proveedores y gastos', 'unidad' => 'contrapartes', 'grupo' => 'Fiscal',
            'origen' => 'd151',
            'columnas' => array(
                $col('cedula', 'Identificación', 'texto', 24),
                $col('nombre', 'Nombre', null, 48),
                $col('codigo', 'Cód.', null, 12),
                $col('concepto', 'Concepto', null, 42),
                $col('operaciones', 'Ops.', 'entero', 14, true),
                $col('monto', 'Monto', 'moneda', 30, true),
                // `monto` sí se totaliza: es la suma declarable del período.
                $col('situacion', 'Situación', null, 34),
            ),
        );

        return $cat;
    }

    private function _rotulo($k)
    {
        $r = array('dia' => 'Día', 'hora' => 'Hora', 'mes' => 'Mes', 'usuario' => 'Usuario',
                   'sucursal' => 'Sucursal', 'caja' => 'Caja', 'cliente' => 'Cliente',
                   'producto' => 'Producto', 'categoria' => 'Categoría', 'cabys' => 'CABYS',
                   'tarifa' => 'Tarifa', 'tipo_doc' => 'Tipo de documento',
                   'condicion' => 'Condición', 'estado' => 'Estado');
        return isset($r[$k]) ? $r[$k] : ucfirst($k);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CONSTRUCCIÓN DEL DOCUMENTO
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Resuelve un informe completo.
     *
     * @param  string $clave    entrada del catálogo
     * @param  array  $f        filtro normalizado
     * @param  array  $opciones `auditar` incluye anomalías y confiabilidad
     * @return array documento de informe
     */
    public function documento($clave, $f, $opciones = array())
    {
        $cat = $this->catalogo();
        if (!isset($cat[$clave])) {
            throw new InvalidArgumentException('Informe desconocido: ' . $clave);
        }
        $def = $cat[$clave];

        $filas = $this->_filas($def, $f);
        $rm    = $this->CI->reporte_model;
        $res   = $rm->resumen($f);

        $doc = array(
            'clave'     => $clave,
            'titulo'    => $def['titulo'],
            'subtitulo' => $this->_periodoTexto($f),
            'folio'     => rep_folio(),
            'unidad'    => $def['unidad'],
            'meta'      => $this->_meta($f),
            'filtros'   => $this->_filtrosTexto($f),
            'columnas'  => $def['columnas'],
            'filas'     => $filas,
            'totales'   => $this->_totales($def['columnas'], $filas),
            'kpis'      => $this->_kpis($res, $f),
            'resumen'   => $res,
            'analisis'  => $this->_analisis($clave, $def, $filas, $res),
            'auditoria' => array(),
            'confiabilidad' => null,
            'nota'      => (isset($def['nota']) && $def['nota'] ? $def['nota'] . ' ' : '')
                         . 'Las cifras se calculan desde el detalle de líneas, no desde el encabezado de la venta. '
                         . 'Pantalla, PDF y Excel salen de esta misma resolución de datos.',
        );

        if (!empty($opciones['auditar'])) {
            $anom = $this->CI->auditoria_model->anomalias($f, 50);
            $inte = $this->CI->auditoria_model->integridad($f);
            $doc['confiabilidad'] = $this->CI->auditoria_model->confiabilidad($f, $anom, $inte);
            $doc['integridad']    = $inte;
            $doc['auditoria']     = $this->_auditoriaFilas($anom);
        }

        return $doc;
    }

    /** Trae las filas según el origen que declara la definición. */
    private function _filas($def, $f)
    {
        $rm = $this->CI->reporte_model;
        $am = $this->CI->auditoria_model;

        switch ($def['origen']) {
            case 'agrupacion':
                $filas = $rm->ventas_por($f, $def['por']);
                // La tarifa se guarda como número; el informe la rotula.
                if ($def['por'] === 'tarifa') {
                    foreach ($filas as &$r) {
                        $r['nombre'] = rep_tarifa_etiqueta($r['nombre']);
                    }
                }
                return $filas;

            case 'medio_pago':   return $rm->ventas_por_medio_pago($f);
            case 'ventas':       return $rm->ventas($f);
            case 'lineas':       return $rm->lineas($f);
            case 'notas_credito':return $rm->notas_credito($f);
            case 'notas_debito': return $rm->notas_debito($f);
            case 'anulaciones':  return $rm->anulaciones($f);
            case 'conciliacion':
                $c = $am->conciliacion($f);
                return $c['filas'];
            case 'anomalias':    return $am->anomalias($f);
            case 'd104':         return $this->d104($f);
            case 'd151':         return $this->d151($f);
            case 'reabastecimiento': return $rm->reabastecimiento($f);
        }
        return array();
    }

    /**
     * Suma las columnas marcadas como totalizables.
     *
     * Sobre las filas ya resueltas, no sobre una consulta aparte: es lo que hace
     * imposible que el pie del Excel diga algo distinto del pie de la pantalla.
     */
    private function _totales($columnas, $filas)
    {
        $t = array();
        foreach ($columnas as $c) {
            if (empty($c['total'])) {
                continue;
            }
            $s = 0.0;
            foreach ($filas as $r) {
                $s += isset($r[$c['clave']]) ? (float) $r[$c['clave']] : 0.0;
            }
            $t[$c['clave']] = round($s, 2);
        }
        return $t;
    }

    private function _kpis($r, $f)
    {
        $m = function ($v) {
            return $this->moneda($v);
        };
        $n = function ($v, $d = 0) {
            return number_format((float) $v, $d, ',', '.');
        };

        $k = array(
            array('etiqueta' => 'Ventas del período', 'texto' => $m($r['total']),
                  'pie' => $n($r['documentos']) . ' comprobantes'),
            array('etiqueta' => 'Base gravable', 'texto' => $m($r['base']),
                  'pie' => 'IVA ' . $m($r['impuesto'])),
            array('etiqueta' => 'Venta neta', 'texto' => $m($r['neto']),
                  'pie' => 'descontadas notas de crédito'),
            array('etiqueta' => 'Ticket promedio', 'texto' => $m($r['ticket']),
                  'pie' => $n($r['clientes']) . ' clientes'),
            array('etiqueta' => 'Utilidad estimada', 'texto' => $m($r['utilidad']),
                  'pie' => 'margen ' . $n($r['margen'], 2) . ' %',
                  'tono' => $r['utilidad'] >= 0 ? 'ok' : 'err'),
            array('etiqueta' => 'Descuentos', 'texto' => $m($r['descuento']), 'pie' => 'sobre el detalle'),
            array('etiqueta' => 'Notas de crédito', 'texto' => $m($r['nc_total']),
                  'pie' => $n($r['nc_cantidad']) . ' notas',
                  'tono' => $r['nc_cantidad'] > 0 ? 'warn' : ''),
            array('etiqueta' => 'Anulaciones', 'texto' => $n($r['anuladas']),
                  'pie' => $m($r['anuladas_total']),
                  'tono' => $r['anuladas'] > 0 ? 'warn' : ''),
        );

        if ($r['nd_cantidad'] > 0) {
            $k[] = array('etiqueta' => 'Notas de débito', 'texto' => $m($r['nd_total']),
                         'pie' => $n($r['nd_cantidad']) . ' notas');
        }
        return $k;
    }

    /**
     * Lectura del informe en prosa.
     *
     * Un informe que solo entrega números obliga a interpretarlos; estas líneas
     * dicen lo que se ve, con la cifra al lado para que se pueda comprobar.
     */
    private function _analisis($clave, $def, $filas, $res)
    {
        $a = array();
        $m = function ($v) {
            return $this->moneda($v);
        };

        if ($def['origen'] === 'reabastecimiento') {
            if (!$filas) {
                return array('Con la venta del período y los mínimos definidos, ningún producto necesita reponerse.');
            }
            $porProveedor = array();
            foreach ($filas as $r) {
                $porProveedor[$r['proveedor']] = (isset($porProveedor[$r['proveedor']]) ? $porProveedor[$r['proveedor']] : 0) + $r['estimado'];
            }
            arsort($porProveedor);
            $a[] = count($filas) . ' productos por reponer con ' . count($porProveedor) . ' proveedores, por un estimado de '
                 . $m(array_sum($porProveedor)) . '.';
            $mayor = array_key_first($porProveedor);
            $a[] = 'El pedido más grande es con «' . $mayor . '»: ' . $m($porProveedor[$mayor]) . '.';
            $sinVenta = count(array_filter($filas, function ($r) { return $r['cobertura'] === '—'; }));
            if ($sinVenta) {
                $a[] = $sinVenta === 1
                    ? 'Uno de ellos no se vendió en el período: se repone solo hasta su mínimo.'
                    : $sinVenta . ' de ellos no se vendieron en el período: se reponen solo hasta su mínimo.';
            }
            $alt = count(array_filter($filas, function ($r) { return $r['alternativa'] !== ''; }));
            if ($alt) {
                $a[] = ($alt === 1 ? 'En un producto' : 'En ' . $alt . ' productos') . ' otro proveedor cobró menos en su última compra (columna «Más barato con»).';
            }
            return $a;
        }

        if (!$filas) {
            return array('El período y los filtros seleccionados no devolvieron ningún registro.');
        }

        if ($res['documentos'] > 0) {
            $a[] = 'Se emitieron ' . number_format($res['documentos'], 0, ',', '.') . ' comprobantes por '
                 . $m($res['total']) . ', con una base gravable de ' . $m($res['base'])
                 . ' y ' . $m($res['impuesto']) . ' de impuesto.';
        }
        if ($res['nc_total'] > 0 || $res['nd_total'] > 0) {
            $a[] = 'Las notas del período mueven la venta a ' . $m($res['neto'])
                 . ' (' . $m($res['nc_total']) . ' en notas de crédito, ' . $m($res['nd_total']) . ' en notas de débito).';
        }
        if ($res['anuladas'] > 0) {
            $pct = $res['documentos'] > 0 ? round($res['anuladas'] / ($res['documentos'] + $res['anuladas']) * 100, 1) : 0;
            $a[] = 'Se anularon ' . $res['anuladas'] . ' comprobantes por ' . $m($res['anuladas_total'])
                 . ' (' . number_format($pct, 1, ',', '.') . ' % de lo emitido).';
        }

        // Concentración: cuánto pesa el primero de la lista sobre el total.
        if (in_array($def['origen'], array('agrupacion', 'medio_pago'), true) && count($filas) > 1) {
            $tot = array_sum(array_map(function ($r) {
                return (float) (isset($r['total']) ? $r['total'] : 0);
            }, $filas));
            $mej = $filas[0];
            foreach ($filas as $r) {
                if ((float) $r['total'] > (float) $mej['total']) {
                    $mej = $r;
                }
            }
            if ($tot > 0) {
                $a[] = 'El mayor, «' . $mej['nombre'] . '», concentra ' . $m($mej['total'])
                     . ' (' . number_format((float) $mej['total'] / $tot * 100, 1, ',', '.') . ' % del total)'
                     . ' entre ' . count($filas) . ' ' . $def['unidad'] . '.';

                $peor = $filas[0];
                foreach ($filas as $r) {
                    if ((float) $r['total'] < (float) $peor['total']) {
                        $peor = $r;
                    }
                }
                if ($peor !== $mej) {
                    $a[] = 'El menor es «' . $peor['nombre'] . '» con ' . $m($peor['total']) . '.';
                }
            }
        }

        if ($res['margen'] > 0 && $res['costo'] > 0) {
            $a[] = 'La utilidad estimada es ' . $m($res['utilidad']) . ', un margen de '
                 . number_format($res['margen'], 2, ',', '.') . ' % sobre la base gravable. '
                 . 'No descuenta gastos operativos ni líneas facturadas sin costo registrado.';
        }

        return $a;
    }

    private function _auditoriaFilas($anomalias)
    {
        $out = array();
        foreach (array_slice($anomalias, 0, 60) as $a) {
            $out[] = array(
                'nivel'       => rep_niveles_anomalia()[$a['nivel']]['etiqueta'],
                'tono'        => $a['tono'],
                'descripcion' => $a['descripcion'],
                'documento'   => $a['documento'],
                'monto'       => $a['monto'],
                'accion'      => $a['accion'],
            );
        }
        return $out;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  DECLARACIONES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * D-104 · Declaración de IVA.
     *
     * El D-104 es una liquidación, no un listado de facturas: pide el débito
     * fiscal por tarifa, el crédito por compras y los ajustes de las notas. El
     * informe se arma con esa forma y se declara qué queda fuera, porque hay
     * casillas del formulario que este sistema no puede alimentar (crédito por
     * compras, proporcionalidad) y presentarlas en blanco sin decirlo daría a
     * entender que son cero.
     */
    public function d104($f)
    {
        $rm = $this->CI->reporte_model;

        // El D-104 se declara sobre lo aceptado por Hacienda, con las anuladas
        // dentro: es su nota de crédito la que las compensa.
        $ff           = $f;
        $ff['ambito'] = 'fiscal';

        $filas   = array();
        $porTar  = $rm->ventas_por($ff, 'tarifa');
        $debito  = 0.0;
        $baseTot = 0.0;

        foreach ($porTar as $r) {
            $filas[] = array(
                'concepto'   => 'Ventas gravadas — ' . rep_tarifa_etiqueta($r['nombre']),
                'base'       => round((float) $r['base'], 2),
                'impuesto'   => round((float) $r['impuesto'], 2),
                'documentos' => (int) $r['documentos'],
                'seccion'    => 'debito',
            );
            $debito  += (float) $r['impuesto'];
            $baseTot += (float) $r['base'];
        }

        $nc = $rm->notas_credito_resumen($ff);
        $nd = $rm->notas_debito_resumen($ff);

        if ($nc['cantidad']) {
            $filas[] = array(
                'concepto'   => 'Menos: notas de crédito del período',
                'base'       => -round($nc['total'] - $nc['impuesto'], 2),
                'impuesto'   => -round($nc['impuesto'], 2),
                'documentos' => $nc['cantidad'],
                'seccion'    => 'ajuste',
            );
            $debito -= $nc['impuesto'];
        }
        if ($nd['cantidad']) {
            $filas[] = array(
                'concepto'   => 'Más: notas de débito del período',
                'base'       => round($nd['total'] - $nd['impuesto'], 2),
                'impuesto'   => round($nd['impuesto'], 2),
                'documentos' => $nd['cantidad'],
                'seccion'    => 'ajuste',
            );
            $debito += $nd['impuesto'];
        }

        $filas[] = array(
            'concepto'   => 'DÉBITO FISCAL DEL PERÍODO',
            'base'       => round($baseTot, 2),
            'impuesto'   => round($debito, 2),
            'documentos' => null,
            'seccion'    => 'total',
        );

        return $filas;
    }

    /**
     * Casillas del D-104 que este sistema no puede alimentar.
     *
     * Se enumeran en el informe en vez de dejarlas en blanco: una casilla vacía
     * se lee como un cero declarado, y el crédito fiscal por compras no es cero,
     * simplemente no está en esta base.
     */
    public function d104_faltantes()
    {
        return array(
            'Crédito fiscal por compras locales' => 'Requiere las facturas de compra recibidas con su IVA soportado. '
                . 'El módulo de compras electrónicas no está alimentado en esta instalación.',
            'Crédito fiscal por importaciones' => 'El sistema no registra pólizas de desalmacenaje.',
            'Proporcionalidad del crédito' => 'Aplica cuando se realizan operaciones con y sin derecho a crédito; '
                . 'el cálculo depende de información contable fuera del punto de venta.',
            'Retenciones y percepciones' => 'No se registran retenciones de IVA en este sistema.',
            'Exportaciones' => 'No se emitieron comprobantes con condición de venta de exportación en el período.',
        );
    }

    /**
     * D-151 · Declaración anual de clientes, proveedores y gastos.
     *
     * Tres correcciones sobre lo que había, cada una comprobada contra los datos:
     *
     * - El monto sale de la base de la línea completa, no de `net_unit_price`,
     *   que es un precio unitario y omitía la cantidad.
     * - Las ramas se suman con `UNION ALL`. Con `UNION` se descartaban las filas
     *   repetidas —mismo cliente, mismo concepto, mismo importe— y el declarante
     *   perdía esas operaciones sin ningún aviso.
     * - Se declara por contraparte con su umbral: el formulario no pide todas
     *   las operaciones, y lo que queda fuera se muestra con su motivo en vez de
     *   desaparecer.
     */
    public function d151($f)
    {
        $rm  = $this->CI->reporte_model;
        $s   = $rm->tabla('sales');
        $si  = $rm->tabla('sale_items');
        $c   = $rm->tabla('customers');
        $ht  = $rm->tabla('hacienda_tiketes');
        $nc  = $rm->tabla('note_credits');

        $umbral = rep_d151_umbral($this->CI->Settings);
        $bind   = array($f['desde'], $f['hasta'], $f['desde'], $f['hasta']);

        // Las notas de crédito restan de la contraparte: el D-151 declara la
        // operación neta del año, no lo facturado en bruto.
        $sql = "SELECT cedula, nombre, codigo, concepto,
                       SUM(operaciones) operaciones, ROUND(SUM(monto),2) monto
                  FROM (
                    SELECT TRIM(COALESCE(c.cf2,'')) cedula, TRIM(COALESCE(c.name, s.customer_name,'')) nombre,
                           'V' codigo, 'Ventas de bienes y servicios (V)' concepto,
                           1 operaciones,
                           (si.subtotal - COALESCE(si.item_tax,0)) monto
                      FROM `{$s}` s
                      JOIN `{$si}` si ON si.sale_id = s.id
                      LEFT JOIN `{$c}` c ON c.id = s.customer_id
                     WHERE s.date >= ? AND s.date <= ?
                       AND EXISTS (SELECT 1 FROM `{$ht}` h
                                    WHERE h.sale_id = s.id AND h.estatus_hacienda = 'aceptado')
                    UNION ALL
                    SELECT TRIM(COALESCE(c2.cf2,'')) cedula, TRIM(COALESCE(c2.name, n.customer_name,'')) nombre,
                           'V' codigo, 'Ventas de bienes y servicios (V)' concepto,
                           0 operaciones,
                           -(n.grand_total - COALESCE(n.total_tax,0)) monto
                      FROM `{$nc}` n
                      LEFT JOIN `{$c}` c2 ON c2.id = n.customer_id
                     WHERE n.date >= ? AND n.date <= ?
                  ) t
                 GROUP BY cedula, nombre, codigo, concepto
                 ORDER BY monto DESC";

        $q     = $this->CI->db->query($sql, $bind);
        $filas = $q ? $q->result_array() : array();

        foreach ($filas as &$r) {
            $r['monto']       = (float) $r['monto'];
            $r['operaciones'] = (int) $r['operaciones'];

            if ($r['cedula'] === '') {
                $r['situacion'] = 'Excluida: sin identificación';
                $r['declara']   = false;
            } elseif ($r['monto'] < $umbral) {
                $r['situacion'] = 'Excluida: bajo el umbral anual';
                $r['declara']   = false;
            } else {
                $r['situacion'] = 'Se declara';
                $r['declara']   = true;
            }
        }
        unset($r);

        return $filas;
    }

    /**
     * Cuadro previo del D-151: qué entra, qué no y por qué.
     *
     * Lo pide la propia mecánica de la declaración. Sin este cuadro no hay forma
     * de justificar por qué el total del formulario no es el total facturado.
     */
    public function d151_previo($filas)
    {
        $r = array('candidatas' => 0, 'incluidas' => 0, 'excluidas_umbral' => 0,
                   'excluidas_sin_id' => 0, 'monto_total' => 0.0, 'monto_declarado' => 0.0,
                   'monto_excluido' => 0.0, 'umbral' => rep_d151_umbral($this->CI->Settings));

        foreach ($filas as $f) {
            $r['candidatas']++;
            $r['monto_total'] += $f['monto'];
            if ($f['declara']) {
                $r['incluidas']++;
                $r['monto_declarado'] += $f['monto'];
            } else {
                $r['monto_excluido'] += $f['monto'];
                if ($f['cedula'] === '') {
                    $r['excluidas_sin_id']++;
                } else {
                    $r['excluidas_umbral']++;
                }
            }
        }
        foreach (array('monto_total', 'monto_declarado', 'monto_excluido') as $k) {
            $r[$k] = round($r[$k], 2);
        }
        return $r;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SALIDAS
    // ═══════════════════════════════════════════════════════════════════

    /** Documento listo para la pantalla. */
    public function json($doc)
    {
        return array(
            'folio'    => $doc['folio'],
            'titulo'   => $doc['titulo'],
            'subtitulo' => $doc['subtitulo'],
            'columnas' => array_map(function ($c) {
                return array('clave' => $c['clave'], 'titulo' => $c['titulo'],
                             'formato' => $c['formato'], 'total' => !empty($c['total']));
            }, $doc['columnas']),
            'filas'    => $doc['filas'],
            'totales'  => $doc['totales'],
            'kpis'     => $doc['kpis'],
            'analisis' => $doc['analisis'],
            'filtros'  => $doc['filtros'],
            'confiabilidad' => $doc['confiabilidad'],
            'integridad'    => isset($doc['integridad']) ? $doc['integridad'] : null,
            'auditoria'     => $doc['auditoria'],
            'registros'     => count($doc['filas']),
        );
    }

    /** Mismo documento en PDF. */
    public function pdf($doc, $destino = 'I')
    {
        $this->CI->load->library('nx_report_pdf', array('ajustes' => $this->CI->Settings), 'nxpdf');
        return $this->CI->nxpdf->generar($doc, $destino);
    }

    /**
     * Mismo documento en Excel, en las cinco hojas que pide un informe serio.
     *
     * La hoja de parámetros no es decorativa: sin ella un archivo exportado
     * llega a un tercero sin decir de qué período es ni con qué filtros salió,
     * y deja de ser auditable.
     */
    public function excel($doc, $descargar = true)
    {
        $this->CI->load->library('nx_xlsx', array(
            'simbolo' => $this->CI->Settings && isset($this->CI->Settings->symbol)
                ? $this->CI->Settings->symbol : '',
        ), 'nxx');
        $x = $this->CI->nxx;
        $x->meta($doc['titulo'], $this->_empresa());

        /* ── Hoja 1: resumen ─────────────────────────────────────────── */
        $h = $x->hoja('Resumen');
        $x->titulo($h, 'NEURIX POS · ' . $doc['titulo'], 4);
        $x->subtitulo($h, $doc['subtitulo'], 4);
        $x->blanco($h);
        foreach ($doc['meta'] as $k => $v) {
            $x->dato($h, $k, $v);
        }
        $x->dato($h, 'Folio del informe', $doc['folio'], 'texto');
        $x->dato($h, 'Registros', count($doc['filas']), 'entero');
        $x->blanco($h);
        $x->subtitulo($h, 'Indicadores del período', 4);
        foreach ($doc['kpis'] as $k) {
            $x->fila($h, array($k['etiqueta'], $k['texto'], isset($k['pie']) ? $k['pie'] : ''),
                     array('etiqueta', null, null));
        }
        if ($doc['analisis']) {
            $x->blanco($h);
            $x->subtitulo($h, 'Lectura del período', 4);
            foreach ($doc['analisis'] as $t) {
                $x->fila($h, array($t));
            }
        }
        if ($doc['confiabilidad']) {
            $x->blanco($h);
            $x->subtitulo($h, 'Índice de confiabilidad', 4);
            $c = $doc['confiabilidad'];
            $x->fila($h, array('Confiabilidad', $c['pct'] . ' %', $c['resumen']),
                     array('etiqueta', $c['tono'] === 'ok' ? 'ok' : ($c['tono'] === 'warn' ? 'alerta' : 'error'), null));
        }
        $x->anchos($h, array(30, 30, 60));

        /* ── Hoja 2: detalle ─────────────────────────────────────────── */
        $d = $x->hoja('Detalle', array('orientacion' => 'horizontal', 'ajustar' => true,
                                       'pie' => $doc['folio'] . ' · ' . $doc['titulo']));
        $x->cabecera($d, array_map(function ($c) {
            return $c['titulo'];
        }, $doc['columnas']));

        foreach ($doc['filas'] as $r) {
            $vals = array();
            $fmts = array();
            foreach ($doc['columnas'] as $c) {
                $vals[] = isset($r[$c['clave']]) ? $r[$c['clave']] : '';
                $fmts[] = $c['formato'] === 'estado' ? null : $c['formato'];
            }
            $x->fila($d, $vals, $fmts);
        }

        if ($doc['totales']) {
            $vals = array();
            $fmts = array();
            $primera = true;
            foreach ($doc['columnas'] as $c) {
                if (array_key_exists($c['clave'], $doc['totales'])) {
                    $vals[] = $doc['totales'][$c['clave']];
                    $fmts[] = 'moneda';
                } else {
                    $vals[] = $primera ? 'TOTALES' : '';
                    $fmts[] = null;
                }
                $primera = false;
            }
            $x->totales($d, $vals, $fmts);
        }
        $x->anchos($d, array_map(function ($c) {
            return max(10, round($c['ancho'] * 0.55, 1));
        }, $doc['columnas']));

        /* ── Hoja 3: estadísticas ────────────────────────────────────── */
        $e = $x->hoja('Estadísticas');
        $x->titulo($e, 'Estadísticas del período', 3);
        $x->blanco($e);
        $x->cabecera($e, array('Concepto', 'Valor', 'Cómo se calcula'), true, false);
        $conceptos = rep_conceptos();
        $res       = $doc['resumen'];
        $mapa = array('total' => 'total', 'base' => 'base', 'impuesto' => 'impuesto',
                      'descuento' => 'descuento', 'costo' => 'costo', 'utilidad' => 'utilidad',
                      'margen' => 'margen', 'ticket_promedio' => 'ticket');
        foreach ($mapa as $ck => $rk) {
            if (!isset($conceptos[$ck]) || !isset($res[$rk])) {
                continue;
            }
            $x->fila($e, array($conceptos[$ck]['titulo'], $res[$rk], $conceptos[$ck]['formula']),
                     array(null, $ck === 'margen' ? 'numero' : 'moneda', null));
        }
        $x->fila($e, array('Comprobantes', $res['documentos'], 'COUNT(DISTINCT sales.id)'), array(null, 'entero', null));
        $x->fila($e, array('Líneas', $res['lineas'], 'COUNT(sale_items.id)'), array(null, 'entero', null));
        $x->fila($e, array('Clientes distintos', $res['clientes'], 'COUNT(DISTINCT sales.customer_id)'), array(null, 'entero', null));
        $x->fila($e, array('Notas de crédito', $res['nc_total'], 'SUM(note_credits.grand_total)'), array(null, 'moneda', null));
        $x->fila($e, array('Notas de débito', $res['nd_total'], 'SUM(note_debits.grand_total)'), array(null, 'moneda', null));
        $x->fila($e, array('Venta neta', $res['neto'], 'total − notas de crédito + notas de débito'), array(null, 'moneda', null));
        $x->anchos($e, array(32, 20, 70));

        /* ── Hoja 4: auditoría ───────────────────────────────────────── */
        $a = $x->hoja('Auditoría', array('orientacion' => 'horizontal'));
        $x->titulo($a, 'Observaciones de auditoría', 6);
        $x->blanco($a);

        if (isset($doc['integridad'])) {
            $x->subtitulo($a, 'Comparaciones de integridad', 6);
            $x->cabecera($a, array('Comparación', 'Valor A', 'Valor B', 'Diferencia', 'Estado', 'Nota'), false, false);
            foreach ($doc['integridad']['lineas'] as $l) {
                $x->fila($a, array($l['concepto'], $l['a'], $l['b'], $l['diferencia'],
                                   $l['estado'], $l['nota']),
                         array(null, 'moneda', 'moneda', 'moneda',
                               $l['tono'] === 'ok' ? 'ok' : ($l['tono'] === 'warn' ? 'alerta' : 'error'), null));
            }
            $x->blanco($a);
        }

        if ($doc['auditoria']) {
            $x->subtitulo($a, 'Anomalías detectadas', 6);
            $x->cabecera($a, array('Nivel', 'Hallazgo', 'Documento', 'Monto', 'Acción recomendada'), false, true);
            foreach ($doc['auditoria'] as $r) {
                $x->fila($a, array($r['nivel'], $r['descripcion'], $r['documento'], $r['monto'], $r['accion']),
                         array($r['tono'] === 'err' ? 'error' : ($r['tono'] === 'orange' || $r['tono'] === 'warn' ? 'alerta' : null),
                               null, 'texto', 'moneda', null));
            }
        } else {
            $x->fila($a, array('No se ejecutaron comprobaciones de auditoría para este informe, '
                             . 'o no arrojaron hallazgos en el período.'));
        }
        $x->anchos($a, array(14, 60, 24, 16, 60));

        /* ── Hoja 5: parámetros ──────────────────────────────────────── */
        $p = $x->hoja('Parámetros');
        $x->titulo($p, 'Parámetros de generación', 3);
        $x->subtitulo($p, 'Reproduciendo estos filtros se obtiene exactamente este archivo.', 3);
        $x->blanco($p);
        $x->cabecera($p, array('Parámetro', 'Valor'), true, false);
        $x->fila($p, array('Folio', $doc['folio']), array(null, 'texto'));
        $x->fila($p, array('Informe', $doc['titulo']));
        $x->fila($p, array('Período', $doc['subtitulo']));
        foreach ($doc['meta'] as $k => $v) {
            $x->fila($p, array($k, $v));
        }
        foreach ($doc['filtros'] as $k => $v) {
            $x->fila($p, array('Filtro · ' . $k, $v));
        }
        $x->fila($p, array('Registros exportados', count($doc['filas'])), array(null, 'entero'));
        $x->blanco($p);
        $x->subtitulo($p, 'Diccionario de datos aplicado', 3);
        $x->cabecera($p, array('Concepto', 'Fórmula', 'Regla'), false, false);
        foreach (rep_conceptos() as $c) {
            $x->fila($p, array($c['titulo'], $c['formula'], $c['regla']));
        }
        $x->anchos($p, array(30, 46, 90));

        if (!$descargar) {
            return $x->contenido();
        }
        $x->descargar($this->_nombreArchivo($doc) . '.xlsx');
        return null;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  AUXILIARES
    // ═══════════════════════════════════════════════════════════════════

    private function _periodoTexto($f)
    {
        $a = date('d/m/Y', strtotime($f['desde']));
        $b = date('d/m/Y', strtotime($f['hasta']));
        $amb = rep_ambitos();
        return ($a === $b ? $a : 'Del ' . $a . ' al ' . $b)
             . ' · ' . $amb[$f['ambito']]['etiqueta'];
    }

    private function _meta($f)
    {
        $u = $this->CI->session->userdata();
        $nombre = trim((isset($u['first_name']) ? $u['first_name'] : '') . ' '
                     . (isset($u['last_name']) ? $u['last_name'] : ''));
        $amb = rep_ambitos();

        return array(
            'Empresa'   => $this->_empresa(),
            'Generado'  => date('d/m/Y H:i:s'),
            'Usuario'   => $nombre !== '' ? $nombre : (isset($u['username']) ? $u['username'] : '—'),
            'Ámbito'    => $amb[$f['ambito']]['etiqueta'] . ' — ' . $amb[$f['ambito']]['ayuda'],
            'Versión'   => 'Neurix POS ' . ($this->CI->Settings && isset($this->CI->Settings->versionPOS)
                            ? $this->CI->Settings->versionPOS : ''),
        );
    }

    /** Filtros en texto legible; solo los que el usuario puso. */
    private function _filtrosTexto($f)
    {
        $r      = array();
        $rotulo = array(
            'store_id' => 'Sucursal', 'register_id' => 'Caja', 'user_id' => 'Usuario',
            'customer_id' => 'Cliente', 'supplier_id' => 'Proveedor', 'product_id' => 'Producto',
            'category_id' => 'Categoría', 'cabys' => 'CABYS', 'tipo_doc' => 'Tipo de documento',
            'estado' => 'Estado', 'documento' => 'Documento', 'identificacion' => 'Identificación',
            'tarifa' => 'Tarifa', 'condicion' => 'Condición', 'paid_by' => 'Medio de pago',
            'moneda' => 'Moneda', 'monto_min' => 'Monto desde', 'monto_max' => 'Monto hasta',
        );
        foreach ($rotulo as $k => $t) {
            if (isset($f[$k]) && $f[$k] !== null) {
                $r[$t] = is_array($f[$k]) ? implode(', ', $f[$k]) : $f[$k];
            }
        }
        if (!$r) {
            $r['Sin filtros'] = 'Todo el período';
        }
        return $r;
    }

    private function _empresa()
    {
        $s = $this->CI->Settings;
        if (!$s) {
            return 'Neurix POS';
        }
        foreach (array('nombre_comercial', 'nombre_emisor', 'site_name') as $k) {
            if (!empty($s->$k)) {
                return $s->$k;
            }
        }
        return 'Neurix POS';
    }

    private function moneda($v)
    {
        $s = $this->CI->Settings;
        $sim = $s && isset($s->symbol) ? $s->symbol : '';
        $d   = $s && isset($s->decimals) ? (int) $s->decimals : 2;
        return $sim . number_format((float) $v, $d, ',', '.');
    }

    private function _nombreArchivo($doc)
    {
        $n = preg_replace('/[^A-Za-z0-9]+/', '_', $doc['titulo']);
        return trim($n, '_') . '_' . date('Ymd_His');
    }
}

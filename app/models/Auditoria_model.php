<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Comprobaciones de integridad, anomalías y conciliación.
 *
 * Separado de `Reporte_model` a propósito: aquel responde «cuánto se vendió»,
 * este responde «¿es creíble esa cifra?». Mezclarlos lleva a que una consulta
 * de negocio empiece a filtrar lo que no le cuadra, y entonces la
 * inconsistencia deja de verse en vez de resolverse.
 *
 * Ninguna comprobación corrige datos. Detectan y describen; la corrección es
 * siempre una decisión de quien opera el sistema.
 */
class Auditoria_model extends CI_Model
{
    /** @var array<string,string> */
    private $t = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('reportes');

        foreach (array('sales', 'sale_items', 'customers', 'users', 'products', 'categories',
                       'payments', 'registers', 'hacienda_tiketes', 'sale_anulaciones',
                       'note_credits', 'note_debits', 'hacienda_cn', 'hacienda_nd',
                       'product_store_qty', 'mov_inventario') as $x) {
            $this->t[$x] = $this->db->dbprefix($x);
        }
    }

    private function _filas($sql, $bind = array())
    {
        $q = $this->db->query($sql, $bind);
        return $q ? $q->result_array() : array();
    }

    private function _uno($sql, $bind = array(), $col = 'n')
    {
        $r = $this->_filas($sql, $bind);
        return $r && isset($r[0][$col]) ? $r[0][$col] : 0;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ÍNDICE DE INTEGRIDAD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Compara las cifras que el mismo período produce por vías distintas.
     *
     * Un sistema íntegro dice lo mismo mirado desde el detalle de ventas, desde
     * el encabezado, desde los pagos y desde los comprobantes enviados. Cada
     * pareja que no cuadra es una fila con su diferencia y su semáforo; el
     * porcentaje final es cuántas parejas cuadran, sin maquillar.
     *
     * @param  array $f filtro normalizado de `Reporte_model::filtro()`
     */
    public function integridad($f)
    {
        $b = array($f['desde'], $f['hasta']);
        $sucursal = '';
        if ($f['store_id'] !== null && !is_array($f['store_id'])) {
            $sucursal = ' AND s.store_id = ?';
            $b[]      = $f['store_id'];
        }

        // Detalle de líneas: la cifra que este sistema toma por buena.
        $detalle = (float) $this->_uno(
            "SELECT COALESCE(SUM(si.subtotal),0) n
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
              WHERE s.date >= ? AND s.date <= ?{$sucursal}", $b);

        // Encabezado: lo que el POS grabó al cerrar la venta.
        $encabezado = (float) $this->_uno(
            "SELECT COALESCE(SUM(s.grand_total),0) n
               FROM `{$this->t['sales']}` s
              WHERE s.date >= ? AND s.date <= ?{$sucursal}", $b);

        // Cobrado: la suma de los pagos aplicados a esas ventas.
        $cobrado = (float) $this->_uno(
            "SELECT COALESCE(SUM(p.amount),0) n
               FROM `{$this->t['payments']}` p
               JOIN `{$this->t['sales']}` s ON s.id = p.sale_id
              WHERE s.date >= ? AND s.date <= ?{$sucursal}", $b);

        // Declarado: el total de los comprobantes que Hacienda aceptó.
        $bh = $b;
        $declarado = (float) $this->_uno(
            "SELECT COALESCE(SUM(s.grand_total),0) n
               FROM `{$this->t['sales']}` s
              WHERE s.date >= ? AND s.date <= ?{$sucursal}
                AND EXISTS (SELECT 1 FROM `{$this->t['hacienda_tiketes']}` h
                             WHERE h.sale_id = s.id AND h.estatus_hacienda = 'aceptado')", $bh);

        // Aceptado en el período, medido desde el detalle: si no coincide con la
        // anterior, encabezado y líneas discrepan justo en lo declarado.
        $declarado_detalle = (float) $this->_uno(
            "SELECT COALESCE(SUM(si.subtotal),0) n
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
              WHERE s.date >= ? AND s.date <= ?{$sucursal}
                AND EXISTS (SELECT 1 FROM `{$this->t['hacienda_tiketes']}` h2
                             WHERE h2.sale_id = s.id AND h2.estatus_hacienda = 'aceptado')", $bh);

        $pendiente_cobro = round($encabezado - $cobrado, 2);

        $lineas = array(
            array(
                'concepto' => 'Detalle de ventas vs. encabezado',
                'a_texto'  => 'Suma de líneas', 'a' => $detalle,
                'b_texto'  => 'Suma de grand_total', 'b' => $encabezado,
                'nota'     => 'El encabezado se graba al cerrar la venta y las líneas al agregar cada producto. '
                            . 'Una diferencia significa que una línea cambió después de cerrar.',
            ),
            array(
                'concepto' => 'Facturado vs. cobrado',
                'a_texto'  => 'Facturado', 'a' => $encabezado,
                'b_texto'  => 'Cobrado', 'b' => $cobrado,
                'nota'     => $pendiente_cobro > 0
                            ? 'La diferencia es crédito pendiente de cobro, no necesariamente un error.'
                            : 'Cobrado por encima de lo facturado: revisar pagos duplicados o mal aplicados.',
                'tolerar'  => $pendiente_cobro > 0,
            ),
            array(
                'concepto' => 'Declarado a Hacienda vs. su propio detalle',
                'a_texto'  => 'Encabezado de aceptados', 'a' => $declarado,
                'b_texto'  => 'Líneas de aceptados', 'b' => $declarado_detalle,
                'nota'     => 'Los comprobantes aceptados son inmutables: si el detalle ya no cuadra con el '
                            . 'encabezado, el XML enviado y la base dejaron de decir lo mismo.',
            ),
        );

        $cuadran = 0;
        foreach ($lineas as &$l) {
            $dif = round((float) $l['a'] - (float) $l['b'], 2);
            $sem = rep_semaforo($dif, max(abs((float) $l['a']), abs((float) $l['b'])));

            // Un crédito pendiente es una diferencia esperada, no un descuadre:
            // se muestra igual pero no penaliza el índice.
            if (!empty($l['tolerar'])) {
                $sem = array('estado' => 'correcto', 'tono' => 'ok', 'icono' => '🟢', 'pct' => $sem['pct']);
            }

            $l['diferencia'] = $dif;
            $l               = array_merge($l, $sem);
            if ($sem['estado'] === 'correcto') {
                $cuadran++;
            }
        }
        unset($l);

        return array(
            'lineas'      => $lineas,
            'cuadran'     => $cuadran,
            'total'       => count($lineas),
            'pct'         => count($lineas) ? round($cuadran / count($lineas) * 100, 1) : 100.0,
            'diferencias' => count($lineas) - $cuadran,
            'montos'      => array(
                'detalle' => $detalle, 'encabezado' => $encabezado,
                'cobrado' => $cobrado, 'declarado' => $declarado,
            ),
        );
    }

    // ═══════════════════════════════════════════════════════════════════
    //  DETECTOR DE ANOMALÍAS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Recorre las comprobaciones y devuelve una lista plana de hallazgos.
     *
     * Cada hallazgo lleva nivel, qué se detectó, sobre qué documento, cuánto,
     * la causa probable y qué hacer. Un detector que solo dice «hay 14
     * inconsistencias» obliga a buscarlas a mano y nadie lo usa dos veces.
     */
    public function anomalias($f, $limite_por_regla = 100)
    {
        $out = array();
        foreach ($this->reglas() as $clave => $regla) {
            // Una regla que revienta —una columna que este esquema no tiene—
            // no puede llevarse por delante el informe entero: se informa como
            // un hallazgo más, porque una comprobación que no corrió es
            // exactamente lo que no hay que dar por buena en silencio.
            try {
                $filas = call_user_func(array($this, $regla['metodo']), $f, $limite_por_regla);
            } catch (Exception $e) {
                log_message('error', 'Regla de auditoría ' . $clave . ': ' . $e->getMessage());
                $out[] = array(
                    'regla' => $clave, 'nivel' => 'medio', 'tono' => 'warn',
                    'titulo' => 'Comprobación no ejecutada',
                    'causa' => 'La comprobación «' . $regla['titulo'] . '» falló al consultar la base.',
                    'accion' => 'Revisar el registro de la aplicación: el período se evaluó sin esta comprobación.',
                    'id' => 0, 'fecha' => $f['desde'], 'documento' => $clave,
                    'cliente' => '—', 'monto' => 0, 'descripcion' => $e->getMessage(),
                );
                continue;
            }

            foreach ($filas as $r) {
                $out[] = array_merge(array(
                    'regla'     => $clave,
                    'nivel'     => $regla['nivel'],
                    'tono'      => rep_niveles_anomalia()[$regla['nivel']]['tono'],
                    'titulo'    => $regla['titulo'],
                    'causa'     => $regla['causa'],
                    'accion'    => $regla['accion'],
                    'periodica' => !isset($regla['periodica']) || $regla['periodica'],
                ), $r);
            }
        }

        $orden = array('critico' => 0, 'alto' => 1, 'medio' => 2, 'bajo' => 3);
        usort($out, function ($a, $b) use ($orden) {
            $d = $orden[$a['nivel']] - $orden[$b['nivel']];
            return $d !== 0 ? $d : (abs((float) $b['monto']) <=> abs((float) $a['monto']));
        });
        return $out;
    }

    /**
     * Catálogo de comprobaciones.
     *
     * Añadir una regla es añadir una entrada acá y su método: entra sola en el
     * informe, en el PDF, en el Excel y en el índice de confiabilidad.
     */
    public function reglas()
    {
        return array(
            'descuadre_linea' => array(
                'nivel' => 'critico', 'metodo' => '_a_descuadre_linea',
                'titulo' => 'El encabezado no cuadra con el detalle',
                'causa'  => 'Una línea se modificó o se borró después de cerrar la venta.',
                'accion' => 'Comparar el XML enviado con el detalle actual antes de declarar el período.',
            ),
            'sin_detalle' => array(
                'nivel' => 'critico', 'metodo' => '_a_sin_detalle',
                'titulo' => 'Comprobante sin líneas de detalle',
                'causa'  => 'La venta se grabó y el guardado del detalle falló a mitad.',
                'accion' => 'Anular el comprobante y volver a emitirlo con su detalle.',
            ),
            'consecutivo_duplicado' => array(
                'nivel' => 'critico', 'metodo' => '_a_consecutivo_duplicado',
                'titulo' => 'Consecutivo repetido',
                'causa'  => 'El numerador entregó el mismo número dos veces, o se borró un comprobante rechazado.',
                'accion' => 'Hacienda registra la clave al recibirla: el segundo será rechazado. Reemitir con número nuevo.',
            ),
            'tarifa_ausente' => array(
                'nivel' => 'critico', 'metodo' => '_a_tarifa_ausente',
                'titulo' => 'Línea con impuesto cobrado y sin tarifa registrada',
                'causa'  => 'sale_items.tax admite NULL y la línea se grabó sin la tarifa, aunque sí guardó el monto.',
                'accion' => 'El D-104 clasifica el débito fiscal por tarifa: sin ella el impuesto no se puede '
                          . 'asignar a ninguna casilla y quedaría declarado como exento.',
            ),
            'impuesto_descuadrado' => array(
                'nivel' => 'critico', 'metodo' => '_a_impuesto_descuadrado',
                'titulo' => 'El impuesto de la línea no corresponde a su tarifa',
                'causa'  => 'La tarifa del producto cambió después de facturar, o el cálculo se hizo sobre otra base.',
                'accion' => 'Revisar antes de presentar el D-104: el débito fiscal sale de esta columna.',
            ),
            'sin_cabys' => array(
                'nivel' => 'alto', 'metodo' => '_a_sin_cabys',
                'titulo' => 'Línea facturada sin código CABYS',
                'causa'  => 'El producto se dio de alta sin CABYS y se facturó igual.',
                'accion' => 'La v4.4 exige CodigoCABYS en cada línea. Completar la ficha del producto.',
            ),
            'rechazado_sin_reemitir' => array(
                'nivel' => 'alto', 'metodo' => '_a_rechazado_sin_reemitir',
                'titulo' => 'Comprobante rechazado que nunca se reemitió',
                'causa'  => 'La tanda automática no reintenta los rechazados y nadie lo hizo a mano.',
                'accion' => 'Reemitir con clave y consecutivo nuevos: la clave original ya está gastada.',
            ),
            'venta_sin_inventario' => array(
                'nivel' => 'alto', 'metodo' => '_a_venta_sin_inventario',
                'titulo' => 'Venta sin movimiento de inventario',
                'causa'  => 'La rebaja de existencias falló o el producto no controla inventario.',
                'accion' => 'Confirmar la existencia física antes de tomar el inventario valorizado por bueno.',
            ),
            'stock_negativo' => array(
                // Mide la existencia de hoy, no lo que pasó en el período: se
                // informa igual, pero no descuenta del índice de un período que
                // no la causó.
                'periodica' => false,
                'nivel' => 'alto', 'metodo' => '_a_stock_negativo',
                'titulo' => 'Existencia negativa',
                'causa'  => 'Se vendió por encima de la existencia con la sobreventa habilitada.',
                'accion' => 'Ajustar por conteo físico: un negativo falsea el costo y el margen.',
            ),
            'cantidad_negativa' => array(
                'nivel' => 'alto', 'metodo' => '_a_cantidad_negativa',
                'titulo' => 'Cantidad o precio negativo en una línea',
                'causa'  => 'Una devolución registrada como venta en vez de nota de crédito.',
                'accion' => 'Emitir la nota de crédito correspondiente y corregir la línea.',
            ),
            'estado_pago_incoherente' => array(
                'nivel' => 'medio', 'metodo' => '_a_estado_pago_incoherente',
                'titulo' => 'Estado de la venta incoherente con lo cobrado',
                'causa'  => 'El estado se fijó a mano o un pago se anuló sin recalcular la venta.',
                'accion' => 'Recalcular el saldo: el informe de crédito se apoya en esta columna.',
            ),
            'descuento_excesivo' => array(
                'nivel' => 'medio', 'metodo' => '_a_descuento_excesivo',
                'titulo' => 'Descuento por encima del tope',
                'causa'  => 'Descuento aplicado sin autorización o mal tecleado.',
                'accion' => 'Contrastar con la política de descuentos y con quién lo autorizó.',
            ),
            'costo_cero' => array(
                'nivel' => 'medio', 'metodo' => '_a_costo_cero',
                'titulo' => 'Línea vendida con costo cero',
                'causa'  => 'El producto se facturó antes de registrarle costo.',
                'accion' => 'La utilidad de esas líneas sale inflada: completar el costo antes de leer el margen.',
            ),
            'nota_duplicada' => array(
                'nivel' => 'critico', 'metodo' => '_a_nota_duplicada',
                'titulo' => 'Documento acreditado más de una vez',
                'causa'  => 'Se emitió una segunda nota de crédito sobre un comprobante ya acreditado.',
                'accion' => 'Comparar el importe acreditado con el del comprobante: si lo supera, el débito '
                          . 'fiscal del período está rebajado de más y la declaración saldrá mal.',
            ),
            'exceso_notas' => array(
                'nivel' => 'medio', 'metodo' => '_a_exceso_notas',
                'titulo' => 'Concentración de notas de crédito en un usuario',
                'causa'  => 'Puede ser un procedimiento mal entendido o una salida de mercadería sin control.',
                'accion' => 'Revisar los motivos declarados en las notas de ese usuario.',
            ),
            'venta_atipica' => array(
                'nivel' => 'bajo', 'metodo' => '_a_venta_atipica',
                'titulo' => 'Importe muy alejado del habitual',
                'causa'  => 'Puede ser legítimo; también un punto decimal de más al teclear.',
                'accion' => 'Confirmar contra el comprobante impreso.',
            ),
        );
    }

    /** Fragmento de período común a todas las reglas. */
    private function _periodo($f, &$b, $alias = 's')
    {
        $b = array($f['desde'], $f['hasta']);
        $w = "{$alias}.date >= ? AND {$alias}.date <= ?";
        if ($f['store_id'] !== null && !is_array($f['store_id'])) {
            $w  .= " AND {$alias}.store_id = ?";
            $b[] = $f['store_id'];
        }
        return $w;
    }

    private function _a_descuadre_linea($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        return $this->_filas(
            "SELECT s.id, s.date AS fecha, s.consecutivo AS documento, s.customer_name AS cliente,
                    s.grand_total AS encabezado, d.total AS detalle,
                    ROUND(s.grand_total - d.total, 2) AS monto,
                    CONCAT('Encabezado ', ROUND(s.grand_total,2), ' contra detalle ', ROUND(d.total,2)) AS descripcion
               FROM `{$this->t['sales']}` s
               JOIN (SELECT sale_id, SUM(subtotal) total FROM `{$this->t['sale_items']}` GROUP BY sale_id) d
                 ON d.sale_id = s.id
              WHERE {$w} AND ABS(s.grand_total - d.total) > 0.5
              ORDER BY ABS(s.grand_total - d.total) DESC LIMIT " . (int) $lim, $b);
    }

    private function _a_sin_detalle($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        return $this->_filas(
            "SELECT s.id, s.date AS fecha, s.consecutivo AS documento, s.customer_name AS cliente,
                    s.grand_total AS monto,
                    'La venta existe pero no tiene ninguna línea' AS descripcion
               FROM `{$this->t['sales']}` s
              WHERE {$w}
                AND NOT EXISTS (SELECT 1 FROM `{$this->t['sale_items']}` si WHERE si.sale_id = s.id)
              ORDER BY s.date DESC LIMIT " . (int) $lim, $b);
    }

    private function _a_consecutivo_duplicado($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        return $this->_filas(
            "SELECT MIN(s.id) id, MAX(s.date) fecha, s.consecutivo documento,
                    GROUP_CONCAT(s.id ORDER BY s.id SEPARATOR ', ') AS cliente,
                    SUM(s.grand_total) monto,
                    CONCAT(COUNT(*), ' comprobantes comparten el consecutivo ', s.consecutivo) AS descripcion
               FROM `{$this->t['sales']}` s
              WHERE {$w} AND s.consecutivo IS NOT NULL AND s.consecutivo <> ''
              GROUP BY s.consecutivo HAVING COUNT(*) > 1
              ORDER BY COUNT(*) DESC LIMIT " . (int) $lim, $b);
    }

    private function _a_impuesto_descuadrado($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        // Un colón de margen absorbe el redondeo a cinco decimales del XML.
        return $this->_filas(
            "SELECT s.id, s.date AS fecha, s.consecutivo AS documento,
                    si.product_name AS cliente,
                    ROUND(si.item_tax - ((si.subtotal - si.item_tax) * CAST(si.tax AS DECIMAL(6,2)) / 100), 2) AS monto,
                    CONCAT(si.product_name, ': tarifa ', CAST(si.tax AS DECIMAL(6,2)), ' %, impuesto ',
                           ROUND(si.item_tax,2), ' cuando la base ', ROUND(si.subtotal - si.item_tax,2),
                           ' da ', ROUND((si.subtotal - si.item_tax) * CAST(si.tax AS DECIMAL(6,2)) / 100, 2)) AS descripcion
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
              WHERE {$w}
                AND ABS(si.item_tax - ((si.subtotal - si.item_tax) * CAST(si.tax AS DECIMAL(6,2)) / 100)) > 1
              ORDER BY ABS(si.item_tax - ((si.subtotal - si.item_tax) * CAST(si.tax AS DECIMAL(6,2)) / 100)) DESC
              LIMIT " . (int) $lim, $b);
    }

    /**
     * Impuesto cobrado sobre una línea que no dice a qué tarifa corresponde.
     *
     * Es distinto de una línea exenta: la exenta lleva tarifa 0 y monto 0. Acá
     * hay monto y no hay tarifa, así que el informe por tarifa la agruparía bajo
     * un 0 % que no es cierto.
     */
    private function _a_tarifa_ausente($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        return $this->_filas(
            "SELECT MIN(s.id) id, MAX(s.date) fecha, si.product_code AS documento,
                    si.product_name AS cliente, SUM(si.item_tax) monto,
                    CONCAT(COUNT(*), ' líneas de \"', si.product_name, '\" con ',
                           ROUND(SUM(si.item_tax),2), ' de impuesto y la tarifa sin registrar') AS descripcion
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
              WHERE {$w} AND (si.tax IS NULL OR si.tax = '') AND COALESCE(si.item_tax,0) <> 0
              GROUP BY si.product_id, si.product_code, si.product_name
              ORDER BY SUM(si.item_tax) DESC LIMIT " . (int) $lim, $b);
    }

    private function _a_sin_cabys($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        return $this->_filas(
            "SELECT MIN(s.id) id, MAX(s.date) fecha,
                    si.product_code AS documento, si.product_name AS cliente,
                    SUM(si.subtotal) monto,
                    CONCAT(COUNT(*), ' líneas de \"', si.product_name, '\" facturadas sin CABYS') AS descripcion
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
              WHERE {$w} AND (si.cabys IS NULL OR si.cabys = '')
              GROUP BY si.product_id, si.product_code, si.product_name
              ORDER BY SUM(si.subtotal) DESC LIMIT " . (int) $lim, $b);
    }

    private function _a_rechazado_sin_reemitir($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        return $this->_filas(
            "SELECT s.id, s.date AS fecha, s.consecutivo AS documento, s.customer_name AS cliente,
                    s.grand_total AS monto,
                    CONCAT('Estado \"', h.estatus_hacienda, '\" desde ', DATE_FORMAT(s.date,'%d/%m/%Y')) AS descripcion
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['hacienda_tiketes']}` h ON h.id =
                    (SELECT MAX(h2.id) FROM `{$this->t['hacienda_tiketes']}` h2 WHERE h2.sale_id = s.id)
              WHERE {$w} AND h.estatus_hacienda IN ('rechazado','error')
                AND NOT EXISTS (SELECT 1 FROM `{$this->t['sale_anulaciones']}` a WHERE a.sale_id = s.id)
              ORDER BY s.grand_total DESC LIMIT " . (int) $lim, $b);
    }

    /**
     * Unidades vendidas frente a unidades que salieron del inventario.
     *
     * `tec_mov_inventario` no guarda la venta que originó el movimiento —no
     * tiene columna de referencia—, así que el cruce se hace por producto y
     * período, que es lo que la tabla permite comprobar.
     *
     * Cuando el registro de movimientos está vacío en el período, la anomalía
     * es esa y se informa una sola vez: marcar cada venta por separado llenaría
     * el informe de ruido y taparía lo que sí importa.
     */
    private function _a_venta_sin_inventario($f, $lim)
    {
        if (!$this->db->table_exists('mov_inventario')) {
            return array();
        }

        $b   = array($f['desde'], $f['hasta']);
        $mov = (int) $this->_uno(
            "SELECT COUNT(*) n FROM `{$this->t['mov_inventario']}`
              WHERE fecha_mov >= ? AND fecha_mov <= ?", $b);

        $w        = $this->_periodo($f, $bv);
        $vendidos = (int) $this->_uno(
            "SELECT COUNT(DISTINCT si.product_id) n
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
              WHERE {$w}", $bv);

        if ($mov === 0) {
            if ($vendidos === 0) {
                return array();
            }
            return array(array(
                'id' => 0, 'fecha' => $f['desde'], 'documento' => '—',
                'cliente' => 'Registro de movimientos de inventario',
                'monto' => 0,
                'descripcion' => 'Se vendieron ' . $vendidos . ' productos distintos y no se registró '
                               . 'ningún movimiento de inventario en el período: el kardex y el '
                               . 'inventario valorizado no se pueden auditar contra las ventas.',
            ));
        }

        // Con movimientos registrados sí tiene sentido el cruce por producto.
        $bb = array($f['desde'], $f['hasta'], $f['desde'], $f['hasta']);
        return $this->_filas(
            "SELECT v.product_id AS id, ? AS fecha, v.product_code AS documento,
                    v.product_name AS cliente, v.importe AS monto,
                    CONCAT('\"', v.product_name, '\": ', v.unidades, ' unidades vendidas y ',
                           COALESCE(m.unidades,0), ' registradas como salida de inventario') AS descripcion
               FROM (SELECT si.product_id, si.product_code, si.product_name,
                            SUM(si.quantity) unidades, SUM(si.subtotal) importe
                       FROM `{$this->t['sales']}` s
                       JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
                      WHERE s.date >= ? AND s.date <= ?
                      GROUP BY si.product_id, si.product_code, si.product_name) v
               LEFT JOIN (SELECT id_product, SUM(quantity_mov) unidades
                            FROM `{$this->t['mov_inventario']}`
                           WHERE tipo_mov = 2 AND fecha_mov >= ? AND fecha_mov <= ?
                           GROUP BY id_product) m ON m.id_product = v.product_id
              WHERE ABS(v.unidades - COALESCE(m.unidades,0)) > 0.0001
              ORDER BY v.importe DESC LIMIT " . (int) $lim,
            array_merge(array($f['desde']), $bb));
    }

    private function _a_stock_negativo($f, $lim)
    {
        return $this->_filas(
            "SELECT p.id, NULL AS fecha, p.code AS documento, p.name AS cliente,
                    q.quantity AS monto,
                    CONCAT('\"', p.name, '\" con existencia ', q.quantity) AS descripcion
               FROM `{$this->t['product_store_qty']}` q
               JOIN `{$this->t['products']}` p ON p.id = q.product_id
              WHERE q.quantity < 0
              ORDER BY q.quantity ASC LIMIT " . (int) $lim);
    }

    private function _a_cantidad_negativa($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        return $this->_filas(
            "SELECT s.id, s.date AS fecha, s.consecutivo AS documento, si.product_name AS cliente,
                    si.subtotal AS monto,
                    CONCAT(si.product_name, ': cantidad ', si.quantity, ', precio ', si.net_unit_price) AS descripcion
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
              WHERE {$w} AND (si.quantity < 0 OR si.net_unit_price < 0 OR si.subtotal < 0)
              ORDER BY s.date DESC LIMIT " . (int) $lim, $b);
    }

    private function _a_estado_pago_incoherente($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        return $this->_filas(
            "SELECT s.id, s.date AS fecha, s.consecutivo AS documento, s.customer_name AS cliente,
                    ROUND(s.grand_total - COALESCE(s.paid,0), 2) AS monto,
                    CONCAT('Marcada \"', s.payment_status, '\" con ', ROUND(COALESCE(s.paid,0),2),
                           ' pagado de ', ROUND(s.grand_total,2)) AS descripcion
               FROM `{$this->t['sales']}` s
              WHERE {$w}
                AND ((s.payment_status = 'paid' AND COALESCE(s.paid,0) < s.grand_total - 0.5)
                  OR (s.payment_status = 'due'  AND COALESCE(s.paid,0) >= s.grand_total - 0.5))
              ORDER BY ABS(s.grand_total - COALESCE(s.paid,0)) DESC LIMIT " . (int) $lim, $b);
    }

    private function _a_descuento_excesivo($f, $lim)
    {
        $tope = 0.30;
        if ($this->Settings && !empty($this->Settings->tope_descuento)) {
            $tope = ((float) $this->Settings->tope_descuento) / 100;
        }
        $w = $this->_periodo($f, $b);
        $b[] = $tope;
        return $this->_filas(
            "SELECT s.id, s.date AS fecha, s.consecutivo AS documento, si.product_name AS cliente,
                    si.item_discount AS monto,
                    CONCAT(si.product_name, ': descuento ', ROUND(si.item_discount,2), ' sobre ',
                           ROUND(si.subtotal + si.item_discount,2), ' (',
                           ROUND(si.item_discount / NULLIF(si.subtotal + si.item_discount,0) * 100,1), ' %)') AS descripcion
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
              WHERE {$w} AND si.item_discount > 0
                AND si.item_discount / NULLIF(si.subtotal + si.item_discount,0) > ?
              ORDER BY si.item_discount DESC LIMIT " . (int) $lim, $b);
    }

    private function _a_costo_cero($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        return $this->_filas(
            "SELECT MIN(s.id) id, MAX(s.date) fecha, si.product_code AS documento,
                    si.product_name AS cliente, SUM(si.subtotal) monto,
                    CONCAT(COUNT(*), ' líneas de \"', si.product_name, '\" vendidas sin costo registrado') AS descripcion
               FROM `{$this->t['sales']}` s
               JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
              WHERE {$w} AND COALESCE(si.cost,0) <= 0
              GROUP BY si.product_id, si.product_code, si.product_name
              ORDER BY SUM(si.subtotal) DESC LIMIT " . (int) $lim, $b);
    }

    /**
     * Comprobantes con más de una nota de crédito.
     *
     * No siempre es un error —una devolución parcial y luego otra son dos notas
     * legítimas—, así que se informa el acreditado frente al facturado: lo que
     * importa es si entre todas superan el documento que corrigen.
     */
    private function _a_nota_duplicada($f, $lim)
    {
        $b = array($f['desde'], $f['hasta']);
        return $this->_filas(
            "SELECT MIN(n.id) id, MAX(n.date) fecha, s.consecutivo AS documento,
                    n.customer_name AS cliente,
                    ROUND(SUM(n.grand_total) - MAX(s.grand_total), 2) AS monto,
                    CONCAT(COUNT(*), ' notas de crédito por ', ROUND(SUM(n.grand_total),2),
                           ' sobre un comprobante de ', ROUND(MAX(s.grand_total),2)) AS descripcion
               FROM `{$this->t['note_credits']}` n
               JOIN `{$this->t['sales']}` s ON s.id = n.sale_id
              WHERE n.date >= ? AND n.date <= ?
              GROUP BY n.sale_id, s.consecutivo, n.customer_name
             HAVING COUNT(*) > 1
              ORDER BY SUM(n.grand_total) - MAX(s.grand_total) DESC LIMIT " . (int) $lim, $b);
    }

    private function _a_exceso_notas($f, $lim)
    {
        $b = array($f['desde'], $f['hasta']);
        return $this->_filas(
            "SELECT u.id, MAX(n.date) fecha, CONCAT('Usuario #', n.created_by) AS documento,
                    CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS cliente,
                    SUM(n.grand_total) monto,
                    CONCAT(COUNT(*), ' notas de crédito por ', ROUND(SUM(n.grand_total),2),
                           ' en el período') AS descripcion
               FROM `{$this->t['note_credits']}` n
               LEFT JOIN `{$this->t['users']}` u ON u.id = n.created_by
              WHERE n.date >= ? AND n.date <= ?
              GROUP BY n.created_by, u.id, u.first_name, u.last_name
             HAVING COUNT(*) >= 5
              ORDER BY SUM(n.grand_total) DESC LIMIT " . (int) $lim, $b);
    }

    /**
     * Ventas muy alejadas del promedio del período.
     *
     * Se usan tres desviaciones típicas sobre el propio período en vez de un
     * umbral fijo: un negocio que factura millones y otro que factura miles no
     * comparten lo que es «alto», y un umbral en duro solo acierta en uno.
     */
    private function _a_venta_atipica($f, $lim)
    {
        $w = $this->_periodo($f, $b);
        $e = $this->_filas(
            "SELECT AVG(s.grand_total) media, STDDEV_POP(s.grand_total) sigma, COUNT(*) n
               FROM `{$this->t['sales']}` s WHERE {$w}", $b);
        if (!$e || (int) $e[0]['n'] < 20 || (float) $e[0]['sigma'] <= 0) {
            return array();   // sin muestra suficiente el estadístico miente
        }

        $corte = (float) $e[0]['media'] + 3 * (float) $e[0]['sigma'];
        $b2    = $b;
        $b2[]  = $corte;

        return $this->_filas(
            "SELECT s.id, s.date AS fecha, s.consecutivo AS documento, s.customer_name AS cliente,
                    s.grand_total AS monto,
                    CONCAT('Importe ', ROUND(s.grand_total,2), ' frente a una media de "
                           . round((float) $e[0]['media'], 2) . " en el período') AS descripcion
               FROM `{$this->t['sales']}` s
              WHERE {$w} AND s.grand_total > ?
              ORDER BY s.grand_total DESC LIMIT " . (int) $lim, $b2);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CONCILIACIÓN CON HACIENDA
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Confronta lo que el sistema tiene con lo que se envió a Hacienda.
     *
     * No consulta la API: compara la venta contra el comprobante guardado y
     * contra el XML firmado, que es la copia de lo que realmente se mandó. Una
     * diferencia entre la venta y su propio XML es un descuadre que ninguna
     * consulta a Hacienda va a resolver.
     */
    public function conciliacion($f, $limite = 500)
    {
        $w = $this->_periodo($f, $b);
        $filas = $this->_filas(
            "SELECT s.id, s.date, s.consecutivo, s.clave, s.grand_total, s.customer_name,
                    h.id AS h_id, h.consecutivo AS h_consecutivo, h.clave AS h_clave,
                    h.estatus_hacienda, h.fecha_emision,
                    (h.xml_sign IS NOT NULL AND h.xml_sign <> '') AS tiene_xml,
                    (SELECT COUNT(*) FROM `{$this->t['hacienda_tiketes']}` hx WHERE hx.sale_id = s.id) AS comprobantes,
                    (SELECT COUNT(*) FROM `{$this->t['sale_anulaciones']}` a WHERE a.sale_id = s.id) AS anulada
               FROM `{$this->t['sales']}` s
               LEFT JOIN `{$this->t['hacienda_tiketes']}` h ON h.id =
                    (SELECT MAX(h2.id) FROM `{$this->t['hacienda_tiketes']}` h2 WHERE h2.sale_id = s.id)
              WHERE {$w}
              ORDER BY s.date DESC LIMIT " . (int) $limite, $b);

        $out    = array();
        $conteo = array('conciliado' => 0, 'sin_enviar' => 0, 'sin_aceptar' => 0,
                        'descuadre' => 0, 'sin_xml' => 0, 'duplicado' => 0);

        foreach ($filas as $r) {
            $problemas = array();

            if (!$r['h_id']) {
                $problemas[] = 'No existe comprobante electrónico para esta venta';
                $conteo['sin_enviar']++;
            } else {
                if ((int) $r['comprobantes'] > 1) {
                    $problemas[] = 'La venta tiene ' . $r['comprobantes'] . ' comprobantes: se reemitió';
                    $conteo['duplicado']++;
                }
                if (!$r['tiene_xml']) {
                    $problemas[] = 'El comprobante no guardó el XML firmado';
                    $conteo['sin_xml']++;
                }
                if ($r['estatus_hacienda'] !== 'aceptado') {
                    $problemas[] = 'Estado ante Hacienda: ' . ($r['estatus_hacienda'] ?: 'sin respuesta');
                    $conteo['sin_aceptar']++;
                }
                if ($r['h_consecutivo'] && $r['consecutivo'] && $r['h_consecutivo'] !== $r['consecutivo']) {
                    $problemas[] = 'El consecutivo de la venta y el del comprobante no coinciden';
                    $conteo['descuadre']++;
                }
                if ($r['h_clave'] && $r['clave'] && $r['h_clave'] !== $r['clave']) {
                    $problemas[] = 'La clave de la venta y la del comprobante no coinciden';
                    $conteo['descuadre']++;
                }
                // La clave son 50 posiciones fijas: una más corta salió con el
                // consecutivo o la fecha incompletos y Hacienda la rechaza entera.
                if ($r['h_clave'] && strlen($r['h_clave']) !== 50) {
                    $problemas[] = 'La clave mide ' . strlen($r['h_clave']) . ' dígitos en vez de 50';
                    $conteo['descuadre']++;
                }
            }

            if (!$problemas) {
                $conteo['conciliado']++;
            }

            $out[] = array(
                'id'          => $r['id'],
                'fecha'       => $r['date'],
                'consecutivo' => $r['consecutivo'] ?: $r['h_consecutivo'],
                'clave'       => $r['h_clave'] ?: $r['clave'],
                'cliente'     => $r['customer_name'],
                'total'       => (float) $r['grand_total'],
                'estado'      => $r['estatus_hacienda'] ?: '(sin enviar)',
                'anulada'     => (int) $r['anulada'] > 0,
                'conciliado'  => !$problemas,
                'problemas'   => implode('. ', $problemas),
            );
        }

        return array('filas' => $out, 'conteo' => $conteo, 'revisados' => count($out));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ÍNDICE DE CONFIABILIDAD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Confiabilidad del período, de 0 a 100.
     *
     * Parte de 100 y descuenta por cada hallazgo según su peso, acotando lo que
     * puede restar cada regla para que un solo problema repetido mil veces no
     * hunda el índice por sí solo. Nada se oculta para subir el número: el
     * detalle de lo que descontó viaja con el resultado.
     */
    public function confiabilidad($f, $anomalias = null, $integridad = null)
    {
        $anomalias  = $anomalias  === null ? $this->anomalias($f)   : $anomalias;
        $integridad = $integridad === null ? $this->integridad($f)  : $integridad;

        $pesos    = rep_niveles_anomalia();
        $porRegla = array();
        foreach ($anomalias as $a) {
            // Las comprobaciones sobre el estado actual —una existencia negativa
            // de hoy— se informan pero no descuentan: el indice mide si se puede
            // confiar en las cifras de ESTE periodo, y un problema de inventario
            // de hoy no las hace menos ciertas.
            if (isset($a['periodica']) && !$a['periodica']) {
                continue;
            }
            $k = $a['regla'];
            if (!isset($porRegla[$k])) {
                $porRegla[$k] = array('nivel' => $a['nivel'], 'titulo' => $a['titulo'], 'n' => 0);
            }
            $porRegla[$k]['n']++;
        }

        $descuentos = array();
        $total      = 0.0;
        foreach ($porRegla as $k => $r) {
            $peso = $pesos[$r['nivel']]['peso'];
            // Un hallazgo pesa entero; los siguientes de la misma regla suman
            // cada vez menos, hasta el doble del peso base.
            $d = min($peso * 2, $peso * (1 + log($r['n'], 10)));
            $descuentos[] = array('regla' => $k, 'titulo' => $r['titulo'], 'nivel' => $r['nivel'],
                                  'hallazgos' => $r['n'], 'descuento' => round($d, 2));
            $total += $d;
        }

        // Un descuadre de integridad es más grave que cualquier anomalía suelta:
        // significa que dos vistas del mismo período no coinciden.
        if ($integridad['diferencias'] > 0) {
            $d = 10 * $integridad['diferencias'];
            $descuentos[] = array('regla' => 'integridad', 'titulo' => 'Comparaciones de integridad que no cuadran',
                                  'nivel' => 'critico', 'hallazgos' => $integridad['diferencias'],
                                  'descuento' => round($d, 2));
            $total += $d;
        }

        $pct = max(0.0, min(100.0, round(100 - $total, 1)));
        $sem = $pct >= 99 ? 'ok' : ($pct >= 90 ? 'warn' : 'err');

        $n = 0;
        foreach ($anomalias as $a) {
            if (!isset($a['periodica']) || $a['periodica']) {
                $n++;
            }
        }
        $resumen = $n === 0 && $integridad['diferencias'] === 0
            ? 'No se detectaron inconsistencias en el período.'
            : 'Se detectaron ' . $n . ' ' . ($n === 1 ? 'anomalía' : 'anomalías')
              . ($integridad['diferencias'] ? ' y ' . $integridad['diferencias'] . ' comparación(es) de integridad sin cuadrar' : '')
              . '.';

        return array(
            'pct'         => $pct,
            'tono'        => $sem,
            'resumen'     => $resumen,
            'anomalias'   => $n,
            'descuentos'  => $descuentos,
            'integridad'  => $integridad,
        );
    }
}

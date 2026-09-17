<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Fuente única de verdad de los informes.
 *
 * Todo informe —pantalla, PDF y Excel— sale de acá. La razón es que los tres
 * tenían su propia consulta y acababan mostrando cifras distintas del mismo
 * período; con una sola consulta la discrepancia deja de ser posible.
 *
 * Dos reglas que sostienen la exactitud de las sumas:
 *
 * - **Un solo constructor de filtros.** `_aplicar()` traduce el filtro completo
 *   a SQL con parámetros ligados. Un filtro que no pase por ahí es un filtro
 *   dibujado en pantalla que no llega a la consulta.
 * - **Nada de `JOIN` que multiplique.** Unir la venta con sus líneas repite el
 *   encabezado por cada línea, así que lo del encabezado se cuenta con
 *   `COUNT(DISTINCT)` y lo del detalle se suma por línea. Los filtros sobre
 *   tablas de varias filas por venta (pagos, notas) van por `EXISTS`.
 *
 * Las expresiones de base, impuesto, costo y utilidad viven en
 * `reportes_helper.php`; acá solo se componen.
 */
class Reporte_model extends CI_Model
{
    /** @var array<string,string> nombres de tabla ya prefijados */
    private $t = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('reportes');

        foreach (array('sales', 'sale_items', 'customers', 'users', 'products', 'categories',
                       'stores', 'payments', 'registers', 'hacienda_tiketes', 'sale_anulaciones',
                       'note_credits', 'note_credits_items', 'note_debits', 'hacienda_cn',
                       'hacienda_nd', 'suppliers', 'purchases', 'purchase_items',
                       'documentoshacienda', 'documentositems', 'fec', 'fec_items',
                       'hacienda_fec', 'audit_log', 'mov_inventario', 'product_store_qty') as $tabla) {
            $this->t[$tabla] = $this->db->dbprefix($tabla);
        }
    }

    /** Nombre real de una tabla, ya prefijado. */
    public function tabla($nombre)
    {
        return isset($this->t[$nombre]) ? $this->t[$nombre] : $this->db->dbprefix($nombre);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  FILTRO
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Normaliza el filtro que llega por HTTP.
     *
     * Devuelve siempre las mismas claves para que el resto del modelo no tenga
     * que comprobar si existen, y para que el pie de página del PDF y la hoja
     * de parámetros del Excel puedan enumerarlas sin conocer el informe.
     *
     * @param  array|null $in normalmente `$this->input->post()`
     * @return array
     */
    public function filtro($in = null)
    {
        $in = is_array($in) ? $in : array();
        $v  = function ($k) use ($in) {
            $x = isset($in[$k]) ? $in[$k] : null;
            if (is_array($x)) {
                $x = array_values(array_filter(array_map('trim', $x), 'strlen'));
                return $x ?: null;
            }
            $x = trim((string) $x);
            return $x === '' ? null : $x;
        };

        list($desde, $hasta) = rep_rango_fechas($v('start_date'), $v('end_date'));

        $f = array(
            'desde'        => $desde,
            'hasta'        => $hasta,
            'ambito'       => rep_ambito_valido($v('ambito')),
            'store_id'     => $v('store_id'),
            'register_id'  => $v('register_id'),
            'user_id'      => $v('user_id'),
            'customer_id'  => $v('customer_id'),
            'supplier_id'  => $v('supplier_id'),
            'product_id'   => $v('product_id'),
            'category_id'  => $v('category_id'),
            'cabys'        => $v('cabys'),
            'tipo_doc'     => $v('tipo_doc'),
            'estado'       => $v('estado'),
            'documento'    => $v('documento'),
            'identificacion' => $v('identificacion'),
            'tarifa'       => $v('tarifa'),
            'condicion'    => $v('condicion'),
            'paid_by'      => $v('paid_by'),
            'moneda'       => $v('moneda'),
            'monto_min'    => $v('monto_min'),
            'monto_max'    => $v('monto_max'),
        );

        // El cajero solo ve su sucursal: el filtro de pantalla no puede
        // ampliarle el alcance, así que la sesión gana sobre lo que llegue.
        $sesion = $this->session->userdata('store_id');
        if ($sesion) {
            $f['store_id'] = $sesion;
        }

        return $f;
    }

    /**
     * Traduce el filtro a `WHERE` con parámetros ligados.
     *
     * @param  array  $f      filtro normalizado
     * @param  array  $bind   se rellena con los valores a ligar, por referencia
     * @param  string $s      alias de `sales`
     * @param  string $si     alias de `sale_items`, o '' si la consulta no une el detalle
     * @return string fragmento SQL que siempre empieza por una condición válida
     */
    private function _aplicar($f, &$bind, $s = 's', $si = 'si')
    {
        $w    = array('1 = 1');
        $bind = array();

        $w[] = "{$s}.date >= ?";
        $bind[] = $f['desde'];
        $w[] = "{$s}.date <= ?";
        $bind[] = $f['hasta'];

        $ambitos = rep_ambitos();
        $amb     = $ambitos[$f['ambito']];

        // Una venta está anulada si tiene fila en tec_sale_anulaciones, no por
        // su estatus ante Hacienda: en la anulación fiscal el comprobante sigue
        // aceptado y esa columna guarda la respuesta real, no la decisión.
        if (!$amb['anuladas'] && $this->db->table_exists('sale_anulaciones')) {
            $w[] = "NOT EXISTS (SELECT 1 FROM `{$this->t['sale_anulaciones']}` xa WHERE xa.sale_id = {$s}.id)";
        }
        if ($amb['estados']) {
            $marcas = implode(',', array_fill(0, count($amb['estados']), '?'));
            $w[]    = "EXISTS (SELECT 1 FROM `{$this->t['hacienda_tiketes']}` xh "
                    . "WHERE xh.sale_id = {$s}.id AND xh.estatus_hacienda IN ({$marcas}))";
            foreach ($amb['estados'] as $e) {
                $bind[] = $e;
            }
        }

        $directos = array(
            'store_id'    => "{$s}.store_id",
            'register_id' => "{$s}.register_id",
            'user_id'     => "{$s}.created_by",
            'customer_id' => "{$s}.customer_id",
            'tipo_doc'    => "{$s}.tipo_doc",
            'condicion'   => "{$s}.condicion",
        );
        foreach ($directos as $clave => $col) {
            if ($f[$clave] === null) {
                continue;
            }
            if (is_array($f[$clave])) {
                $w[] = $col . ' IN (' . implode(',', array_fill(0, count($f[$clave]), '?')) . ')';
                foreach ($f[$clave] as $x) {
                    $bind[] = $x;
                }
            } else {
                $w[]    = $col . ' = ?';
                $bind[] = $f[$clave];
            }
        }

        if ($f['documento'] !== null) {
            $w[]    = "({$s}.consecutivo LIKE ? OR {$s}.clave LIKE ? OR {$s}.id = ?)";
            $bind[] = '%' . $f['documento'] . '%';
            $bind[] = '%' . $f['documento'] . '%';
            $bind[] = (int) $f['documento'];
        }
        if ($f['identificacion'] !== null) {
            $w[]    = "EXISTS (SELECT 1 FROM `{$this->t['customers']}` xc "
                    . "WHERE xc.id = {$s}.customer_id AND xc.cf2 LIKE ?)";
            $bind[] = '%' . $f['identificacion'] . '%';
        }
        if ($f['estado'] !== null) {
            $w[]    = "EXISTS (SELECT 1 FROM `{$this->t['hacienda_tiketes']}` xh2 "
                    . "WHERE xh2.sale_id = {$s}.id AND xh2.estatus_hacienda = ?)";
            $bind[] = $f['estado'];
        }
        // Una venta puede llevar hasta cuatro pagos: unir la tabla la duplicaría,
        // así que el medio de pago se comprueba con EXISTS.
        if ($f['paid_by'] !== null) {
            $w[]    = "EXISTS (SELECT 1 FROM `{$this->t['payments']}` xp "
                    . "WHERE xp.sale_id = {$s}.id AND xp.paid_by = ?)";
            $bind[] = $f['paid_by'];
        }
        if ($f['monto_min'] !== null) {
            $w[]    = "{$s}.grand_total >= ?";
            $bind[] = (float) $f['monto_min'];
        }
        if ($f['monto_max'] !== null) {
            $w[]    = "{$s}.grand_total <= ?";
            $bind[] = (float) $f['monto_max'];
        }

        // Los filtros de detalle se comprueban contra las líneas. Cuando la
        // consulta ya une el detalle basta la condición directa; cuando no
        // (recuentos de encabezado) hay que ir por EXISTS o el filtro se pierde.
        $detalle = array();
        $bdet    = array();
        if ($f['product_id'] !== null) {
            $detalle[] = '%s.product_id = ?';
            $bdet[]    = $f['product_id'];
        }
        if ($f['cabys'] !== null) {
            $detalle[] = '%s.cabys LIKE ?';
            $bdet[]    = $f['cabys'] . '%';
        }
        if ($f['tarifa'] !== null) {
            $detalle[] = 'CAST(%s.tax AS DECIMAL(6,2)) = ?';
            $bdet[]    = (float) $f['tarifa'];
        }
        if ($f['category_id'] !== null) {
            $detalle[] = "EXISTS (SELECT 1 FROM `{$this->t['products']}` xpr "
                       . 'WHERE xpr.id = %s.product_id AND xpr.category_id = ?)';
            $bdet[]    = $f['category_id'];
        }

        if ($detalle) {
            if ($si !== '') {
                foreach ($detalle as $cond) {
                    $w[] = sprintf($cond, $si);
                }
                foreach ($bdet as $x) {
                    $bind[] = $x;
                }
            } else {
                $sub = array();
                foreach ($detalle as $cond) {
                    $sub[] = sprintf($cond, 'xsi');
                }
                $w[] = "EXISTS (SELECT 1 FROM `{$this->t['sale_items']}` xsi "
                     . "WHERE xsi.sale_id = {$s}.id AND " . implode(' AND ', $sub) . ')';
                foreach ($bdet as $x) {
                    $bind[] = $x;
                }
            }
        }

        return implode(' AND ', $w);
    }

    /** Consulta con parámetros ligados; devuelve siempre un arreglo. */
    private function _filas($sql, $bind = array())
    {
        $q = $this->db->query($sql, $bind);
        return $q ? $q->result_array() : array();
    }

    /** Primera fila de una consulta, o un arreglo de ceros del mismo molde. */
    private function _fila($sql, $bind = array(), $vacio = array())
    {
        $r = $this->_filas($sql, $bind);
        return $r ? $r[0] : $vacio;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  VENTAS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Indicadores del período. Es el resumen que encabeza todos los informes.
     *
     * Los importes se calculan desde el detalle y no desde `sales.grand_total`
     * a propósito: si el encabezado y las líneas discrepan, el informe de
     * integridad tiene que poder verlo en vez de heredar la cifra del
     * encabezado y dar el descuadre por bueno.
     */
    public function resumen($f)
    {
        $e = rep_expr('si');
        $w = $this->_aplicar($f, $bind);

        $sql = "SELECT
                  COUNT(DISTINCT s.id)              AS documentos,
                  COUNT(si.id)                      AS lineas,
                  COUNT(DISTINCT s.customer_id)     AS clientes,
                  COALESCE(SUM(si.quantity),0)      AS unidades,
                  COALESCE(SUM({$e['base']}),0)     AS base,
                  COALESCE(SUM({$e['impuesto']}),0) AS impuesto,
                  COALESCE(SUM({$e['descuento']}),0) AS descuento,
                  COALESCE(SUM({$e['total']}),0)    AS total,
                  COALESCE(SUM({$e['costo']}),0)    AS costo,
                  COALESCE(SUM({$e['utilidad']}),0) AS utilidad
                FROM `{$this->t['sales']}` s
                JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
                WHERE {$w}";

        $r = $this->_fila($sql, $bind, array());
        foreach (array('documentos', 'lineas', 'clientes', 'unidades', 'base', 'impuesto',
                       'descuento', 'total', 'costo', 'utilidad') as $k) {
            $r[$k] = isset($r[$k]) ? (float) $r[$k] : 0.0;
        }

        $r['margen']  = $r['base'] > 0 ? round($r['utilidad'] / $r['base'] * 100, 2) : 0.0;
        $r['ticket']  = $r['documentos'] > 0 ? round($r['total'] / $r['documentos'], 2) : 0.0;

        $nc = $this->notas_credito_resumen($f);
        $nd = $this->notas_debito_resumen($f);
        $an = $this->anulaciones_resumen($f);

        $r['nc_cantidad'] = $nc['cantidad'];
        $r['nc_total']    = $nc['total'];
        $r['nc_impuesto'] = $nc['impuesto'];
        $r['nd_cantidad'] = $nd['cantidad'];
        $r['nd_total']    = $nd['total'];
        $r['nd_impuesto'] = $nd['impuesto'];
        $r['anuladas']    = $an['cantidad'];
        $r['anuladas_total'] = $an['total'];

        // Venta neta: lo facturado menos lo devuelto más lo cobrado de más.
        $r['neto']          = round($r['total'] - $r['nc_total'] + $r['nd_total'], 2);
        $r['impuesto_neto'] = round($r['impuesto'] - $r['nc_impuesto'] + $r['nd_impuesto'], 2);

        return $r;
    }

    /**
     * Detalle de comprobantes del período, una fila por venta.
     *
     * Agrupa exactamente el mismo `JOIN` que `resumen()`: si un filtro de
     * detalle deja fuera unas líneas, el importe de la fila las excluye igual
     * que el indicador de cabecera. Es lo que hace que la suma de la columna
     * cuadre con el KPI y, por tanto, que la pantalla, el PDF y el Excel digan
     * lo mismo.
     *
     * `tec_hacienda_tiketes` se une por la fila más reciente de la venta. Un
     * comprobante reemitido tiene varias y unir a todas duplicaría la venta,
     * que es lo que hacían los informes diario y mensual.
     */
    public function ventas($f, $limite = 0)
    {
        $e = rep_expr('si');
        $w = $this->_aplicar($f, $bind);

        // ONLY_FULL_GROUP_BY está activo: las columnas de las tablas unidas no
        // se consideran dependientes de s.id aunque la relación sea 1:1, así
        // que van todas en el GROUP BY. No cambian la cardinalidad.
        $cols = "s.id, s.date, s.consecutivo, s.clave, s.tipo_doc, s.condicion,
                 s.grand_total, s.paid, s.store_id, s.register_id, s.customer_name,
                 c.cf2, c.cf1, u.first_name, u.last_name, st.name,
                 h.estatus_hacienda, h.fecha_emision";

        $sql = "SELECT
                  s.id, s.date, s.consecutivo, s.clave, s.tipo_doc, s.condicion,
                  s.grand_total AS encabezado_total, s.paid, s.store_id, s.register_id,
                  s.customer_name, c.cf2 AS identificacion, c.cf1 AS tipo_identificacion,
                  CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS usuario,
                  st.name AS sucursal,
                  h.estatus_hacienda, h.fecha_emision,
                  COUNT(si.id)                       AS lineas,
                  COALESCE(SUM(si.quantity),0)       AS unidades,
                  COALESCE(SUM({$e['base']}),0)      AS base,
                  COALESCE(SUM({$e['impuesto']}),0)  AS impuesto,
                  COALESCE(SUM({$e['descuento']}),0) AS descuento,
                  COALESCE(SUM({$e['total']}),0)     AS total,
                  COALESCE(SUM({$e['costo']}),0)     AS costo,
                  COALESCE(SUM({$e['utilidad']}),0)  AS utilidad,
                  (SELECT GROUP_CONCAT(DISTINCT p.paid_by ORDER BY p.paid_by SEPARATOR ', ')
                     FROM `{$this->t['payments']}` p WHERE p.sale_id = s.id) AS medios_pago,
                  (SELECT COUNT(*) FROM `{$this->t['sale_anulaciones']}` a WHERE a.sale_id = s.id) AS anulada,
                  (SELECT COUNT(*) FROM `{$this->t['note_credits']}` n WHERE n.sale_id = s.id) AS notas_credito,
                  (SELECT COUNT(*) FROM `{$this->t['note_debits']}` n2 WHERE n2.sale_id = s.id) AS notas_debito
                FROM `{$this->t['sales']}` s
                JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
                LEFT JOIN `{$this->t['customers']}` c  ON c.id  = s.customer_id
                LEFT JOIN `{$this->t['users']}`     u  ON u.id  = s.created_by
                LEFT JOIN `{$this->t['stores']}`    st ON st.id = s.store_id
                LEFT JOIN `{$this->t['hacienda_tiketes']}` h ON h.id = (
                    SELECT MAX(h2.id) FROM `{$this->t['hacienda_tiketes']}` h2 WHERE h2.sale_id = s.id)
                WHERE {$w}
                GROUP BY {$cols}
                ORDER BY s.date DESC, s.id DESC";

        if ($limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }

        return $this->_filas($sql, $bind);
    }

    /**
     * Detalle línea a línea. Es el nivel al que se rastrea una cifra hasta su
     * origen, así que lleva la venta, el producto y el impuesto de cada renglón.
     */
    public function lineas($f, $limite = 0)
    {
        $e = rep_expr('si');
        $w = $this->_aplicar($f, $bind);

        $sql = "SELECT
                  s.id AS sale_id, s.date, s.consecutivo, s.tipo_doc, s.customer_name,
                  si.id AS linea_id, si.product_id, si.product_code, si.product_name,
                  si.cabys, cat.name AS categoria,
                  si.quantity, si.unit_price, si.net_unit_price,
                  {$e['tarifa']}   AS tarifa,
                  {$e['base']}     AS base,
                  {$e['impuesto']} AS impuesto,
                  {$e['descuento']} AS descuento,
                  {$e['total']}    AS total,
                  {$e['costo']}    AS costo,
                  {$e['utilidad']} AS utilidad
                FROM `{$this->t['sales']}` s
                JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
                LEFT JOIN `{$this->t['products']}`   p   ON p.id   = si.product_id
                LEFT JOIN `{$this->t['categories']}` cat ON cat.id = p.category_id
                WHERE {$w}
                ORDER BY s.date DESC, si.id ASC";

        if ($limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }

        return $this->_filas($sql, $bind);
    }

    /**
     * Agrupación genérica del detalle de ventas.
     *
     * Todos los informes «ventas por X» salen de acá con la misma aritmética,
     * que es lo que evita que «ventas por usuario» y «ventas por día» sumen
     * distinto sobre el mismo período.
     *
     * @param  string $por clave de `agrupaciones()`
     */
    public function ventas_por($f, $por, $limite = 0)
    {
        $g = $this->agrupaciones();
        if (!isset($g[$por])) {
            return array();
        }
        $def = $g[$por];
        $e   = rep_expr('si');
        $w   = $this->_aplicar($f, $bind);

        $sql = "SELECT
                  {$def['clave']}  AS clave,
                  {$def['nombre']} AS nombre,
                  COUNT(DISTINCT s.id)               AS documentos,
                  COALESCE(SUM(si.quantity),0)       AS unidades,
                  COALESCE(SUM({$e['base']}),0)      AS base,
                  COALESCE(SUM({$e['impuesto']}),0)  AS impuesto,
                  COALESCE(SUM({$e['descuento']}),0) AS descuento,
                  COALESCE(SUM({$e['total']}),0)     AS total,
                  COALESCE(SUM({$e['costo']}),0)     AS costo,
                  COALESCE(SUM({$e['utilidad']}),0)  AS utilidad
                FROM `{$this->t['sales']}` s
                JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
                {$def['join']}
                WHERE {$w}
                GROUP BY {$def['clave']}, {$def['nombre']}
                ORDER BY {$def['orden']}";

        if ($limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }

        $filas = $this->_filas($sql, $bind);
        foreach ($filas as &$r) {
            $r['margen'] = (float) $r['base'] > 0
                ? round((float) $r['utilidad'] / (float) $r['base'] * 100, 2) : 0.0;
            $r['ticket'] = (int) $r['documentos'] > 0
                ? round((float) $r['total'] / (int) $r['documentos'], 2) : 0.0;
        }
        return $filas;
    }

    /**
     * Dimensiones por las que se puede agrupar una venta.
     *
     * Añadir una entrada acá crea el informe «ventas por …» completo, con sus
     * totales, su PDF y su Excel, sin escribir otra consulta.
     */
    public function agrupaciones()
    {
        $u = $this->t['users'];
        return array(
            'dia' => array(
                'titulo' => 'Ventas por día', 'unidad' => 'días',
                'clave'  => 'DATE(s.date)', 'nombre' => 'DATE(s.date)',
                'join'   => '', 'orden' => 'clave ASC',
            ),
            'hora' => array(
                'titulo' => 'Ventas por hora', 'unidad' => 'horas',
                'clave'  => 'HOUR(s.date)', 'nombre' => "CONCAT(LPAD(HOUR(s.date),2,'0'),':00')",
                'join'   => '', 'orden' => 'clave ASC',
            ),
            'mes' => array(
                'titulo' => 'Ventas por mes', 'unidad' => 'meses',
                'clave'  => "DATE_FORMAT(s.date,'%Y-%m')", 'nombre' => "DATE_FORMAT(s.date,'%Y-%m')",
                'join'   => '', 'orden' => 'clave ASC',
            ),
            'usuario' => array(
                'titulo' => 'Ventas por usuario', 'unidad' => 'usuarios',
                'clave'  => 's.created_by',
                'nombre' => "CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))",
                'join'   => "LEFT JOIN `{$u}` u ON u.id = s.created_by", 'orden' => 'total DESC',
            ),
            'sucursal' => array(
                'titulo' => 'Ventas por sucursal', 'unidad' => 'sucursales',
                'clave'  => 's.store_id', 'nombre' => 'st.name',
                'join'   => "LEFT JOIN `{$this->t['stores']}` st ON st.id = s.store_id",
                'orden'  => 'total DESC',
            ),
            'caja' => array(
                'titulo' => 'Ventas por caja', 'unidad' => 'cajas',
                'clave'  => 's.register_id', 'nombre' => "CONCAT('Caja #', COALESCE(s.register_id,0))",
                'join'   => '', 'orden' => 'total DESC',
            ),
            'cliente' => array(
                'titulo' => 'Ventas por cliente', 'unidad' => 'clientes',
                'clave'  => 's.customer_id', 'nombre' => 's.customer_name',
                'join'   => '', 'orden' => 'total DESC',
            ),
            'producto' => array(
                'titulo' => 'Ventas por producto', 'unidad' => 'productos',
                'clave'  => 'si.product_id', 'nombre' => 'si.product_name',
                'join'   => '', 'orden' => 'total DESC',
            ),
            'categoria' => array(
                'titulo' => 'Ventas por categoría', 'unidad' => 'categorías',
                'clave'  => 'p.category_id', 'nombre' => "COALESCE(cat.name,'Sin categoría')",
                'join'   => "LEFT JOIN `{$this->t['products']}` p ON p.id = si.product_id "
                          . "LEFT JOIN `{$this->t['categories']}` cat ON cat.id = p.category_id",
                'orden'  => 'total DESC',
            ),
            'cabys' => array(
                'titulo' => 'Ventas por código CABYS', 'unidad' => 'códigos',
                'clave'  => "COALESCE(NULLIF(si.cabys,''),'(sin CABYS)')",
                'nombre' => "COALESCE(NULLIF(si.cabys,''),'(sin CABYS)')",
                'join'   => '', 'orden' => 'total DESC',
            ),
            'tarifa' => array(
                'titulo' => 'Ventas por tarifa de IVA', 'unidad' => 'tarifas',
                'clave'  => 'CAST(si.tax AS DECIMAL(6,2))',
                'nombre' => 'CAST(si.tax AS DECIMAL(6,2))',
                'join'   => '', 'orden' => 'clave ASC',
            ),
            'tipo_doc' => array(
                'titulo' => 'Ventas por tipo de documento', 'unidad' => 'tipos',
                'clave'  => 's.tipo_doc', 'nombre' => 's.tipo_doc',
                'join'   => '', 'orden' => 'total DESC',
            ),
            'condicion' => array(
                'titulo' => 'Ventas por condición de venta', 'unidad' => 'condiciones',
                'clave'  => 's.condicion', 'nombre' => 's.condicion',
                'join'   => '', 'orden' => 'total DESC',
            ),
            'estado' => array(
                'titulo' => 'Ventas por estado ante Hacienda', 'unidad' => 'estados',
                'clave'  => "COALESCE(h.estatus_hacienda,'(sin enviar)')",
                'nombre' => "COALESCE(h.estatus_hacienda,'(sin enviar)')",
                'join'   => "LEFT JOIN `{$this->t['hacienda_tiketes']}` h ON h.id = "
                          . "(SELECT MAX(h9.id) FROM `{$this->t['hacienda_tiketes']}` h9 WHERE h9.sale_id = s.id)",
                'orden'  => 'total DESC',
            ),
        );
    }

    /**
     * Ventas por medio de pago.
     *
     * No entra en `ventas_por()` porque el medio vive en `payments`, que tiene
     * varias filas por venta: agrupar por ahí repartiría el detalle. Se suma el
     * importe realmente cobrado por cada medio, que es la cifra que el cierre de
     * caja tiene que reproducir.
     */
    public function ventas_por_medio_pago($f)
    {
        $w = $this->_aplicar($f, $bind, 's', '');

        $sql = "SELECT p.paid_by AS clave, p.paid_by AS nombre,
                       COUNT(DISTINCT p.sale_id) AS documentos,
                       COUNT(p.id)               AS movimientos,
                       COALESCE(SUM(p.amount),0) AS total
                FROM `{$this->t['payments']}` p
                JOIN `{$this->t['sales']}` s ON s.id = p.sale_id
                WHERE {$w}
                GROUP BY p.paid_by
                ORDER BY total DESC";

        return $this->_filas($sql, $bind);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  NOTAS Y ANULACIONES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Filtro de las notas: comparten cliente, sucursal y usuario con la venta,
     * pero se fechan por la nota, no por la factura que corrigen.
     */
    private function _aplicar_nota($f, &$bind, $n = 'n')
    {
        $w    = array('1 = 1');
        $bind = array();

        $w[]    = "{$n}.date >= ?";
        $bind[] = $f['desde'];
        $w[]    = "{$n}.date <= ?";
        $bind[] = $f['hasta'];

        foreach (array('store_id' => "{$n}.store_id", 'customer_id' => "{$n}.customer_id",
                       'user_id' => "{$n}.created_by") as $clave => $col) {
            if ($f[$clave] !== null && !is_array($f[$clave])) {
                $w[]    = $col . ' = ?';
                $bind[] = $f[$clave];
            }
        }
        return implode(' AND ', $w);
    }

    public function notas_credito($f)
    {
        $w = $this->_aplicar_nota($f, $bind);
        $sql = "SELECT n.id, n.sale_id, n.date, n.consecutivo, n.clave, n.customer_name,
                       n.total, n.total_tax, n.total_discount, n.grand_total,
                       n.motivo, n.type_nc, n.estatus_hacienda,
                       s.consecutivo AS consecutivo_origen, s.grand_total AS total_origen,
                       s.date AS fecha_origen,
                       (SELECT COUNT(*) FROM `{$this->t['sale_anulaciones']}` a
                         WHERE a.cn_id = n.id) AS es_anulacion
                FROM `{$this->t['note_credits']}` n
                LEFT JOIN `{$this->t['sales']}` s ON s.id = n.sale_id
                WHERE {$w}
                ORDER BY n.date DESC, n.id DESC";
        return $this->_filas($sql, $bind);
    }

    public function notas_credito_resumen($f)
    {
        $w = $this->_aplicar_nota($f, $bind);
        $r = $this->_fila(
            "SELECT COUNT(*) cantidad, COALESCE(SUM(n.grand_total),0) total,
                    COALESCE(SUM(n.total_tax),0) impuesto
             FROM `{$this->t['note_credits']}` n WHERE {$w}",
            $bind,
            array('cantidad' => 0, 'total' => 0, 'impuesto' => 0)
        );
        return array('cantidad' => (int) $r['cantidad'], 'total' => (float) $r['total'],
                     'impuesto' => (float) $r['impuesto']);
    }

    public function notas_debito($f)
    {
        $w = $this->_aplicar_nota($f, $bind);
        $sql = "SELECT n.id, n.sale_id, n.date, n.customer_name, n.total, n.total_tax,
                       n.total_discount, n.grand_total, n.motivo_nd, n.type_nd,
                       s.consecutivo AS consecutivo_origen, s.grand_total AS total_origen,
                       hn.consecutivo, hn.clave, hn.estatus_hacienda
                FROM `{$this->t['note_debits']}` n
                LEFT JOIN `{$this->t['sales']}` s ON s.id = n.sale_id
                LEFT JOIN `{$this->t['hacienda_nd']}` hn ON hn.nd_id = n.id
                WHERE {$w}
                ORDER BY n.date DESC, n.id DESC";
        return $this->_filas($sql, $bind);
    }

    public function notas_debito_resumen($f)
    {
        $w = $this->_aplicar_nota($f, $bind);
        $r = $this->_fila(
            "SELECT COUNT(*) cantidad, COALESCE(SUM(n.grand_total),0) total,
                    COALESCE(SUM(n.total_tax),0) impuesto
             FROM `{$this->t['note_debits']}` n WHERE {$w}",
            $bind,
            array('cantidad' => 0, 'total' => 0, 'impuesto' => 0)
        );
        return array('cantidad' => (int) $r['cantidad'], 'total' => (float) $r['total'],
                     'impuesto' => (float) $r['impuesto']);
    }

    /**
     * Compara una nota con el documento que corrige, línea por línea.
     *
     * El efecto real de la nota no lo decide el botón que se pulsó sino la
     * diferencia entre los dos documentos, así que se calcula: qué líneas
     * volvieron, cuáles cambiaron de cantidad o de precio y cuánto queda.
     */
    public function nota_vs_origen($nc_id)
    {
        $nc = $this->_fila(
            "SELECT n.*, s.consecutivo AS consecutivo_origen, s.grand_total AS total_origen,
                    s.date AS fecha_origen
             FROM `{$this->t['note_credits']}` n
             LEFT JOIN `{$this->t['sales']}` s ON s.id = n.sale_id
             WHERE n.id = ?",
            array((int) $nc_id)
        );
        if (!$nc) {
            return null;
        }

        $orig = $this->_filas(
            "SELECT product_id, product_code, product_name, SUM(quantity) quantity,
                    AVG(net_unit_price) precio, SUM(subtotal) total, SUM(item_tax) impuesto
             FROM `{$this->t['sale_items']}` WHERE sale_id = ? GROUP BY product_id, product_code, product_name",
            array((int) $nc['sale_id'])
        );
        // Las lineas de la nota se enlazan por `cn_id`, no por `sale_id`: la
        // columna guarda la nota a la que pertenecen, no la venta de origen.
        $nota = $this->_filas(
            "SELECT product_id, product_code, product_name, SUM(quantity) quantity,
                    AVG(net_unit_price) precio, SUM(subtotal) total, SUM(item_tax) impuesto
             FROM `{$this->t['note_credits_items']}` WHERE cn_id = ? GROUP BY product_id, product_code, product_name",
            array((int) $nc_id)
        );

        $porId = function ($filas) {
            $x = array();
            foreach ($filas as $r) {
                $x[$r['product_id'] . '|' . $r['product_code']] = $r;
            }
            return $x;
        };
        $a = $porId($orig);
        $b = $porId($nota);

        $comparado = array();
        foreach (array_keys($a + $b) as $k) {
            $o = isset($a[$k]) ? $a[$k] : null;
            $d = isset($b[$k]) ? $b[$k] : null;

            $qo = $o ? (float) $o['quantity'] : 0.0;
            $qd = $d ? (float) $d['quantity'] : 0.0;
            $to = $o ? (float) $o['total'] : 0.0;
            $td = $d ? (float) $d['total'] : 0.0;
            $po = $o ? (float) $o['precio'] : 0.0;
            $pd = $d ? (float) $d['precio'] : 0.0;

            if (!$o) {
                $efecto = 'agregado en la nota';
            } elseif (!$d) {
                $efecto = 'no devuelto';
            } elseif (abs($qd - $qo) > 0.0001 && abs($pd - $po) > 0.0001) {
                $efecto = 'cantidad y precio distintos';
            } elseif (abs($qd - $qo) > 0.0001) {
                $efecto = 'devolución parcial';
            } elseif (abs($pd - $po) > 0.0001) {
                $efecto = 'precio distinto al facturado';
            } else {
                $efecto = 'devuelto completo';
            }

            $comparado[] = array(
                'producto'        => $o ? $o['product_name'] : $d['product_name'],
                'codigo'          => $o ? $o['product_code'] : $d['product_code'],
                'cantidad_origen' => $qo, 'cantidad_nota' => $qd, 'cantidad_dif' => round($qd - $qo, 4),
                'precio_origen'   => round($po, 4), 'precio_nota' => round($pd, 4),
                'total_origen'    => round($to, 2), 'total_nota' => round($td, 2),
                'total_dif'       => round($td - $to, 2),
                'efecto'          => $efecto,
            );
        }

        $sumaNota = array_sum(array_column($comparado, 'total_nota'));
        $sumaOrig = array_sum(array_column($comparado, 'total_origen'));

        return array(
            'nota'        => $nc,
            'lineas'      => $comparado,
            'total_anterior' => round($sumaOrig, 2),
            'total_nota'  => round($sumaNota, 2),
            'diferencia'  => round($sumaOrig - $sumaNota, 2),
            // Es total si la nota devuelve exactamente lo facturado; parcial si
            // deja algo fuera. El código de referencia declarado tiene que
            // coincidir con esto o Hacienda ve una cosa y el negocio otra.
            'alcance'     => abs($sumaOrig - $sumaNota) < 0.5 ? 'total' : 'parcial',
        );
    }

    public function anulaciones($f)
    {
        $w    = array('1 = 1');
        $bind = array($f['desde'], $f['hasta']);
        $w[]  = 'a.created_at >= ?';
        $w[]  = 'a.created_at <= ?';
        if ($f['store_id'] !== null && !is_array($f['store_id'])) {
            $w[]    = 'a.store_id = ?';
            $bind[] = $f['store_id'];
        }
        $w = implode(' AND ', $w);

        return $this->_filas(
            "SELECT a.*, s.consecutivo, s.date AS fecha_venta, s.grand_total, s.customer_name,
                    CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS usuario,
                    n.consecutivo AS consecutivo_nota, n.grand_total AS total_nota
             FROM `{$this->t['sale_anulaciones']}` a
             LEFT JOIN `{$this->t['sales']}` s ON s.id = a.sale_id
             LEFT JOIN `{$this->t['users']}` u ON u.id = a.created_by
             LEFT JOIN `{$this->t['note_credits']}` n ON n.id = a.cn_id
             WHERE {$w}
             ORDER BY a.created_at DESC",
            $bind
        );
    }

    public function anulaciones_resumen($f)
    {
        if (!$this->db->table_exists('sale_anulaciones')) {
            return array('cantidad' => 0, 'total' => 0.0);
        }
        $bind = array($f['desde'], $f['hasta']);
        $r = $this->_fila(
            "SELECT COUNT(*) cantidad, COALESCE(SUM(s.grand_total),0) total
             FROM `{$this->t['sale_anulaciones']}` a
             LEFT JOIN `{$this->t['sales']}` s ON s.id = a.sale_id
             WHERE a.created_at >= ? AND a.created_at <= ?",
            $bind,
            array('cantidad' => 0, 'total' => 0)
        );
        return array('cantidad' => (int) $r['cantidad'], 'total' => (float) $r['total']);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CATÁLOGOS DE FILTRO
    // ═══════════════════════════════════════════════════════════════════

    /** Valores que puede tomar cada filtro, para poblar los desplegables. */
    // ═══════════════════════════════════════════════════════════════════
    //  REABASTECIMIENTO
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Productos que conviene reponer y a quien comprarlos.
     *
     * La venta diaria sale de las ventas del periodo con el mismo filtro de los
     * demas informes (anuladas fuera). El proveedor es el preferido del producto
     * o, si no tiene, el de su ultima compra; el costo es el ultimo que ese
     * proveedor cobro y el empaque, el que se aprendio de sus facturas.
     */
    public function reabastecimiento($f)
    {
        $this->load->helper('compra');
        $dias = max(1, (int) round((strtotime($f['hasta']) - strtotime($f['desde'])) / 86400));

        $bind = array();
        $w = $this->_aplicar($f, $bind);
        $vendido = array();
        foreach ($this->_filas("SELECT si.product_id, COALESCE(SUM(si.quantity),0) AS unidades
                                  FROM `{$this->t['sales']}` s JOIN `{$this->t['sale_items']}` si ON si.sale_id = s.id
                                 WHERE {$w} AND si.product_id IS NOT NULL
                                 GROUP BY si.product_id", $bind) as $r) {
            $vendido[(int) $r['product_id']] = (float) $r['unidades'];
        }

        $stock = $f['store_id'] !== null && !is_array($f['store_id'])
            ? "(SELECT COALESCE(SUM(q.quantity),0) FROM `{$this->t['product_store_qty']}` q WHERE q.product_id = p.id AND q.store_id = " . (int) $f['store_id'] . ")"
            : "(SELECT COALESCE(SUM(q.quantity),0) FROM `{$this->t['product_store_qty']}` q WHERE q.product_id = p.id)";
        $bindP = array();
        $wp = array("p.type = 'standard'");
        if ($f['category_id'] !== null && !is_array($f['category_id'])) {
            $wp[] = 'p.category_id = ?';
            $bindP[] = $f['category_id'];
        }
        if ($f['product_id'] !== null && !is_array($f['product_id'])) {
            $wp[] = 'p.id = ?';
            $bindP[] = $f['product_id'];
        }
        $productos = $this->_filas("SELECT p.id, p.code, p.name, p.cost, p.alert_quantity, p.supplier_id, cat.name AS categoria,
                                           {$stock} AS existencia
                                      FROM `{$this->t['products']}` p
                                      LEFT JOIN `{$this->t['categories']}` cat ON cat.id = p.category_id
                                     WHERE " . implode(' AND ', $wp), $bindP);

        // Ultimo costo de cada proveedor por producto, del mas reciente al mas viejo.
        $historial = array();
        foreach ($this->_filas("SELECT pi.product_id, pu.supplier_id, pi.cost
                                  FROM `{$this->t['purchase_items']}` pi
                                  JOIN `{$this->t['purchases']}` pu ON pu.id = pi.purchase_id
                                 WHERE pu.supplier_id IS NOT NULL AND pi.cost > 0
                                 ORDER BY pu.date DESC, pu.id DESC") as $r) {
            $pid = (int) $r['product_id'];
            $sid = (int) $r['supplier_id'];
            if (!isset($historial[$pid][$sid])) {
                $historial[$pid][$sid] = (float) $r['cost'];
            }
        }

        $empaques = array();
        $tpp = $this->db->dbprefix('proveedor_producto');
        if ($this->db->table_exists('proveedor_producto')) {
            foreach ($this->_filas("SELECT supplier_id, product_id, MAX(factor) AS factor FROM `{$tpp}` GROUP BY supplier_id, product_id") as $r) {
                $empaques[(int) $r['product_id']][(int) $r['supplier_id']] = (float) $r['factor'];
            }
        }

        $nombres = array();
        foreach ($this->_filas("SELECT id, COALESCE(NULLIF(company,''), name) AS nombre FROM `{$this->t['suppliers']}`") as $r) {
            $nombres[(int) $r['id']] = $r['nombre'];
        }

        $filas = array();
        foreach ($productos as $p) {
            $pid = (int) $p['id'];
            $costos = isset($historial[$pid]) ? $historial[$pid] : array();
            $sid = $p['supplier_id'] ? (int) $p['supplier_id'] : ($costos ? (int) array_key_first($costos) : 0);
            if ($f['supplier_id'] !== null && !is_array($f['supplier_id']) && $sid !== (int) $f['supplier_id']) {
                continue;
            }

            $empaque = isset($empaques[$pid][$sid]) ? $empaques[$pid][$sid] : 1;
            $r = compra_reorden((float) $p['existencia'], (float) $p['alert_quantity'], isset($vendido[$pid]) ? $vendido[$pid] : 0, $dias, $empaque);
            if (!$r['reponer']) {
                continue;
            }

            $costo = isset($costos[$sid]) ? $costos[$sid] : (float) $p['cost'];
            // Solo se menciona otro proveedor si cobro al menos 2 % menos: diferencias
            // menores suelen ser redondeo o tipo de cambio.
            $alternativa = '';
            $mejor = $costo * 0.98;
            foreach ($costos as $otro => $c) {
                if ($otro !== $sid && $c < $mejor) {
                    $mejor = $c;
                    $alternativa = (isset($nombres[$otro]) ? $nombres[$otro] : '#' . $otro) . ' · ' . number_format($c, 2, ',', '.');
                }
            }

            $filas[] = array(
                'proveedor'    => $sid && isset($nombres[$sid]) ? $nombres[$sid] : 'Sin proveedor',
                'code'         => $p['code'],
                'nombre'       => $p['name'],
                'categoria'    => $p['categoria'],
                'existencia'   => (float) $p['existencia'],
                'minimo'       => (float) $p['alert_quantity'],
                'vendido'      => isset($vendido[$pid]) ? $vendido[$pid] : 0,
                'venta_diaria' => $r['venta_diaria'],
                'cobertura'    => $r['cobertura'] === null ? '—' : number_format($r['cobertura'], 1, ',', '.') . ' d',
                'sugerido'     => $r['sugerido'],
                'empaque'      => $empaque > 1 ? 'x' . rtrim(rtrim(number_format($empaque, 2, '.', ''), '0'), '.') : '',
                'costo'        => round($costo, 2),
                'estimado'     => round($costo * $r['sugerido'], 2),
                'alternativa'  => $alternativa,
                '_cobertura'   => $r['cobertura'] === null ? -1 : $r['cobertura'],
            );
        }

        usort($filas, function ($a, $b) {
            return array($a['proveedor'] === 'Sin proveedor', $a['proveedor'], $a['_cobertura'])
               <=> array($b['proveedor'] === 'Sin proveedor', $b['proveedor'], $b['_cobertura']);
        });
        foreach ($filas as &$x) {
            unset($x['_cobertura']);
        }
        unset($x);

        return $filas;
    }

    public function catalogos()
    {
        return array(
            'clientes'   => $this->_filas("SELECT id, name, cf2 FROM `{$this->t['customers']}` ORDER BY name LIMIT 2000"),
            'proveedores'=> $this->_filas("SELECT id, name, cf2 FROM `{$this->t['suppliers']}` ORDER BY name LIMIT 2000"),
            'usuarios'   => $this->_filas("SELECT id, CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) AS name FROM `{$this->t['users']}` ORDER BY first_name"),
            'sucursales' => $this->_filas("SELECT id, name FROM `{$this->t['stores']}` ORDER BY name"),
            'categorias' => $this->_filas("SELECT id, name FROM `{$this->t['categories']}` ORDER BY name"),
            'productos'  => $this->_filas("SELECT id, name, code FROM `{$this->t['products']}` ORDER BY name LIMIT 3000"),
            'cajas'      => $this->_filas("SELECT DISTINCT register_id AS id, CONCAT('Caja #', register_id) AS name FROM `{$this->t['sales']}` WHERE register_id IS NOT NULL ORDER BY register_id"),
            'medios'     => $this->_filas("SELECT DISTINCT paid_by AS id, paid_by AS name FROM `{$this->t['payments']}` WHERE paid_by IS NOT NULL AND paid_by <> '' ORDER BY paid_by"),
            'estados'    => $this->_filas("SELECT DISTINCT estatus_hacienda AS id, estatus_hacienda AS name FROM `{$this->t['hacienda_tiketes']}` WHERE estatus_hacienda IS NOT NULL ORDER BY estatus_hacienda"),
            'tipos_doc'  => $this->_filas("SELECT DISTINCT tipo_doc AS id, tipo_doc AS name FROM `{$this->t['sales']}` WHERE tipo_doc IS NOT NULL ORDER BY tipo_doc"),
            'tarifas'    => $this->_filas("SELECT DISTINCT CAST(tax AS DECIMAL(6,2)) AS id, CAST(tax AS DECIMAL(6,2)) AS name FROM `{$this->t['sale_items']}` ORDER BY id"),
        );
    }
}

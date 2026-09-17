<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Diccionario de datos de los informes.
 *
 * Es la única definición de qué significa "venta", "base gravable", "impuesto",
 * "descuento", "costo" y "utilidad" en todo el sistema. Cualquier informe que
 * calcule esos conceptos por su cuenta acaba discrepando de los demás, así que
 * las expresiones SQL viven acá y se consumen desde `Reporte_model`.
 *
 * Las columnas de `tec_sale_items` no son obvias y el nombre engaña:
 *
 * | Columna          | Qué guarda                                            |
 * |------------------|-------------------------------------------------------|
 * | `subtotal`       | total de la línea **con impuesto** y ya sin descuento  |
 * | `item_tax`       | impuesto de toda la línea, no unitario                 |
 * | `net_unit_price` | precio **unitario** sin impuesto                       |
 * | `item_discount`  | descuento de toda la línea, ya restado de `subtotal`   |
 * | `cost`           | costo **unitario** congelado al facturar               |
 * | `tax`            | tarifa como texto, a veces con `%` (`'13%'`, `'0'`)    |
 */

if (!function_exists('rep_ambitos')) {
    /**
     * Ámbitos de un informe: deciden qué comprobantes entran.
     *
     * La diferencia no es cosmética. Una factura anulada con nota de crédito
     * sigue **aceptada** ante Hacienda: sale de la gestión interna pero tiene
     * que seguir en la declaración, porque es su nota la que la compensa. Sacar
     * la factura sin sacar la nota descuadra el D-104.
     */
    function rep_ambitos()
    {
        return array(
            'interno' => array(
                'etiqueta' => 'Gestión interna',
                'ayuda'    => 'Lo que el negocio vendió de verdad: excluye las anuladas.',
                'anuladas' => false,
                'estados'  => array(),   // vacío = cualquier estado ante Hacienda
            ),
            'fiscal' => array(
                'etiqueta' => 'Declaración fiscal',
                'ayuda'    => 'Lo que Hacienda tiene: solo aceptados, con las anuladas dentro.',
                'anuladas' => true,
                'estados'  => array('aceptado'),
            ),
            'emitido' => array(
                'etiqueta' => 'Todo lo emitido',
                'ayuda'    => 'Universo completo, incluidos rechazados y errores. Para auditar.',
                'anuladas' => true,
                'estados'  => array(),
            ),
        );
    }
}

if (!function_exists('rep_ambito_valido')) {
    /** Normaliza el ámbito recibido por HTTP; lo desconocido cae en `interno`. */
    function rep_ambito_valido($ambito)
    {
        $a = strtolower(trim((string) $ambito));
        return array_key_exists($a, rep_ambitos()) ? $a : 'interno';
    }
}

if (!function_exists('rep_expr')) {
    /**
     * Expresiones SQL canónicas sobre una línea de venta.
     *
     * @param  string $t alias de `tec_sale_items` en la consulta
     * @return array<string,string>
     */
    function rep_expr($t = 'si')
    {
        return array(
            // subtotal ya trae el impuesto dentro: la base es la resta.
            'base'      => "({$t}.subtotal - COALESCE({$t}.item_tax,0))",
            'impuesto'  => "COALESCE({$t}.item_tax,0)",
            'total'     => "{$t}.subtotal",
            'descuento' => "COALESCE({$t}.item_discount,0)",
            'cantidad'  => "{$t}.quantity",
            // cost es unitario y quedó congelado al facturar: no se relee de la
            // ficha del producto, que pudo cambiar de precio después.
            'costo'     => "(COALESCE({$t}.cost,0) * {$t}.quantity)",
            'utilidad'  => "(({$t}.subtotal - COALESCE({$t}.item_tax,0)) - (COALESCE({$t}.cost,0) * {$t}.quantity))",
            // `tax` es varchar y a veces llega '13%': el cast se queda con el
            // número inicial y descarta el signo.
            'tarifa'    => "CAST({$t}.tax AS DECIMAL(6,2))",
        );
    }
}

if (!function_exists('rep_tarifas_iva')) {
    /**
     * Tarifas de IVA de la v4.4 con su código de tarifa (Anexos, nota 9).
     *
     * El informe agrupa por tarifa y no por código, porque una misma tarifa
     * puede llegar con códigos distintos según el motivo de la reducción.
     */
    function rep_tarifas_iva()
    {
        return array(
            '0'  => array('etiqueta' => 'Exento / 0 %', 'codigo' => '01', 'tono' => 'muted'),
            '1'  => array('etiqueta' => 'Reducida 1 %', 'codigo' => '02', 'tono' => 'info'),
            '2'  => array('etiqueta' => 'Reducida 2 %', 'codigo' => '03', 'tono' => 'info'),
            '4'  => array('etiqueta' => 'Reducida 4 %', 'codigo' => '04', 'tono' => 'violet'),
            '8'  => array('etiqueta' => 'Reducida 8 %', 'codigo' => '06', 'tono' => 'orange'),
            '13' => array('etiqueta' => 'General 13 %', 'codigo' => '08', 'tono' => 'ok'),
        );
    }
}

if (!function_exists('rep_tarifa_etiqueta')) {
    /**
     * Rótulo de una tarifa; las que no están en la tabla se muestran tal cual.
     *
     * Una tarifa ausente **no** es una tarifa del 0 %. `sale_items.tax` admite
     * NULL y muchas líneas lo tienen aunque lleven impuesto cobrado; rotularlas
     * como exentas presentaría impuesto declarado bajo una tarifa cero, que es
     * justo lo que hace que un D-104 no cuadre. Se dice que falta el dato.
     */
    function rep_tarifa_etiqueta($tarifa)
    {
        if ($tarifa === null || $tarifa === '') {
            return '(tarifa no registrada)';
        }
        $t = rtrim(rtrim(number_format((float) $tarifa, 2, '.', ''), '0'), '.');
        if ($t === '' || $t === '-') {
            $t = '0';
        }
        $tabla = rep_tarifas_iva();
        return isset($tabla[$t]) ? $tabla[$t]['etiqueta'] : ($t . ' %');
    }
}

if (!function_exists('rep_conceptos')) {
    /**
     * Definición de cada concepto que aparece en un informe.
     *
     * La pantalla del diccionario la publica tal cual: un administrador tiene
     * que poder leer de dónde sale una cifra sin abrir el código.
     */
    function rep_conceptos()
    {
        return array(
            'venta' => array(
                'titulo'  => 'Venta',
                'formula' => 'Fila de tec_sales con al menos una línea en tec_sale_items.',
                'regla'   => 'Se contabiliza por su fecha de emisión (tec_sales.date), no por la fecha de pago. '
                           . 'En ámbito interno se excluye si tiene fila en tec_sale_anulaciones; en ámbito fiscal '
                           . 'se conserva y es su nota de crédito la que la compensa.',
            ),
            'base' => array(
                'titulo'  => 'Base gravable',
                'formula' => 'SUM(sale_items.subtotal - sale_items.item_tax)',
                'regla'   => 'subtotal ya viene con el impuesto incluido y con el descuento restado. '
                           . 'Nunca se usa net_unit_price: es unitario y omite la cantidad.',
            ),
            'impuesto' => array(
                'titulo'  => 'Impuesto (IVA)',
                'formula' => 'SUM(sale_items.item_tax)',
                'regla'   => 'Se toma lo que se facturó, no se recalcula sobre la tarifa: el comprobante '
                           . 'enviado a Hacienda lleva ese monto y el informe tiene que cuadrar con él.',
            ),
            'descuento' => array(
                'titulo'  => 'Descuento',
                'formula' => 'SUM(sale_items.item_discount)',
                'regla'   => 'Es el descuento de la línea completa y ya está restado de subtotal. '
                           . 'Volver a restarlo del total sería contarlo dos veces.',
            ),
            'total' => array(
                'titulo'  => 'Total',
                'formula' => 'SUM(sale_items.subtotal) = base + impuesto',
                'regla'   => 'Se calcula desde el detalle y no desde sales.grand_total, para que cualquier '
                           . 'diferencia entre encabezado y detalle salga a la luz en vez de taparse.',
            ),
            'costo' => array(
                'titulo'  => 'Costo',
                'formula' => 'SUM(sale_items.cost * sale_items.quantity)',
                'regla'   => 'cost es unitario y quedó congelado al facturar. No se relee de products.cost, '
                           . 'que pudo cambiar después y falsearía el margen histórico.',
            ),
            'utilidad' => array(
                'titulo'  => 'Utilidad estimada',
                'formula' => 'base gravable − costo',
                'regla'   => 'Es estimada: no descuenta gastos operativos ni notas de crédito posteriores. '
                           . 'Las líneas con costo cero no se excluyen, se marcan como anomalía.',
            ),
            'margen' => array(
                'titulo'  => 'Margen',
                'formula' => 'utilidad ÷ base gravable × 100',
                'regla'   => 'Cero cuando la base es cero; nunca una división por cero.',
            ),
            'nota_credito' => array(
                'titulo'  => 'Nota de crédito',
                'formula' => 'SUM(note_credits.grand_total)',
                'regla'   => 'Resta. Con código 01 anula el 100 % del documento referenciado; con código 06 '
                           . 'devuelve solo las líneas que vuelven. Disminuye ventas e impuesto del período '
                           . 'de la nota, no del período de la factura original.',
            ),
            'nota_debito' => array(
                'titulo'  => 'Nota de débito',
                'formula' => 'SUM(note_debits.grand_total)',
                'regla'   => 'Suma. Cobra de más sobre un documento ya emitido y aumenta el débito fiscal.',
            ),
            'anulacion' => array(
                'titulo'  => 'Anulación',
                'formula' => 'Fila en tec_sale_anulaciones',
                'regla'   => 'Es el hecho de negocio, no un estado de Hacienda. Una venta está anulada si '
                           . 'tiene fila ahí, aunque su estatus_hacienda siga en aceptado.',
            ),
            'ticket_promedio' => array(
                'titulo'  => 'Ticket promedio',
                'formula' => 'total ÷ cantidad de comprobantes',
                'regla'   => 'Sobre comprobantes distintos, no sobre líneas.',
            ),
        );
    }
}

if (!function_exists('rep_niveles_anomalia')) {
    /** Niveles del detector de anomalías, de mayor a menor gravedad. */
    function rep_niveles_anomalia()
    {
        return array(
            'critico' => array('etiqueta' => 'Crítico', 'tono' => 'err',    'peso' => 12),
            'alto'    => array('etiqueta' => 'Alto',    'tono' => 'orange', 'peso' => 6),
            'medio'   => array('etiqueta' => 'Medio',   'tono' => 'warn',   'peso' => 3),
            'bajo'    => array('etiqueta' => 'Bajo',    'tono' => 'info',   'peso' => 1),
        );
    }
}

if (!function_exists('rep_semaforo')) {
    /**
     * Semáforo de un indicador contra su tolerancia.
     *
     * @param  float $diferencia diferencia absoluta encontrada
     * @param  float $referencia magnitud contra la que se compara
     * @return array{estado: string, tono: string, icono: string, pct: float}
     */
    function rep_semaforo($diferencia, $referencia)
    {
        $dif = abs((float) $diferencia);
        $ref = abs((float) $referencia);
        $pct = $ref > 0 ? ($dif / $ref) * 100 : ($dif > 0 ? 100 : 0);

        // Medio colón absorbe el redondeo a dos decimales de cualquier suma.
        if ($dif < 0.5) {
            return array('estado' => 'correcto', 'tono' => 'ok', 'icono' => '🟢', 'pct' => 0.0);
        }
        if ($pct < 1) {
            return array('estado' => 'revisar', 'tono' => 'warn', 'icono' => '🟡', 'pct' => round($pct, 4));
        }
        return array('estado' => 'inconsistencia', 'tono' => 'err', 'icono' => '🔴', 'pct' => round($pct, 4));
    }
}

if (!function_exists('rep_folio')) {
    /**
     * Folio único de una generación de informe: `REP-2026-08-27-000145`.
     *
     * El consecutivo lo entrega la tabla de bitácora; sin ella el folio lleva la
     * hora para no repetirse, porque un informe sin folio no se puede rastrear
     * después.
     */
    function rep_folio($consecutivo = null)
    {
        $n = $consecutivo === null ? (int) date('His') : (int) $consecutivo;
        return 'REP-' . date('Y-m-d') . '-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('rep_fecha_valida')) {
    /**
     * Devuelve `YYYY-MM-DD` solo si la fecha existe en el calendario.
     *
     * `checkdate()` es lo que separa un 31 de febrero de una fecha real: sin
     * esta comprobación la cadena viaja hasta MySQL, que responde *Incorrect
     * DATETIME value* y tumba la consulta entera.
     *
     * @return string|null
     */
    function rep_fecha_valida($valor)
    {
        $v = trim((string) $valor);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m)) {
            return null;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1])
            ? $m[1] . '-' . $m[2] . '-' . $m[3]
            : null;
    }
}

if (!function_exists('rep_rango_fechas')) {
    /**
     * Normaliza un rango a `[inicio 00:00:00, fin 23:59:59]`.
     *
     * Sustituye al patrón `$mes . '-31 23:59'` de los informes mensuales, que
     * dejaba fuera de servicio febrero, abril, junio, septiembre y noviembre.
     *
     * Acepta `YYYY-MM` (mes completo), `YYYY` (año completo) y `YYYY-MM-DD`.
     *
     * @return array{0: string, 1: string}
     */
    function rep_rango_fechas($inicio, $fin = null)
    {
        $inicio = trim((string) $inicio);
        $fin    = trim((string) $fin);

        if ($inicio === '') {
            $inicio = date('Y-m-d');
        }

        // Un solo valor en formato mes o año ya describe el rango completo.
        if ($fin === '') {
            if (preg_match('/^\d{4}-\d{2}$/', $inicio)) {
                $d = new DateTime($inicio . '-01');
                return array($d->format('Y-m-d') . ' 00:00:00', $d->format('Y-m-t') . ' 23:59:59');
            }
            if (preg_match('/^\d{4}$/', $inicio)) {
                return array($inicio . '-01-01 00:00:00', $inicio . '-12-31 23:59:59');
            }
            $fin = $inicio;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $inicio)) {
            $inicio = $inicio . '-01';
        }
        if (preg_match('/^\d{4}-\d{2}$/', $fin)) {
            $fin = (new DateTime($fin . '-01'))->format('Y-m-t');
        }

        $ini = rep_fecha_valida($inicio) ?: date('Y-m-d');
        $ter = rep_fecha_valida($fin) ?: $ini;

        if ($ter < $ini) {
            list($ini, $ter) = array($ter, $ini);
        }

        return array($ini . ' 00:00:00', $ter . ' 23:59:59');
    }
}

if (!function_exists('rep_d151_conceptos')) {
    /**
     * Conceptos del D-151 y la marca que los identifica en el catálogo.
     *
     * El sistema los deduce de `unit_of_measurement`, que en esta base no es una
     * unidad de medida sino un marcador de concepto puesto en la ficha del
     * producto. Se documenta acá porque no se deduce del nombre de la columna.
     */
    function rep_d151_conceptos()
    {
        return array(
            'V'  => array('marcas' => array(),            'texto' => 'Ventas de bienes y servicios (V)', 'lado' => 'venta'),
            'C'  => array('marcas' => array(),            'texto' => 'Compras de bienes y servicios (C)', 'lado' => 'compra'),
            'SP' => array('marcas' => array('Sp', 'Spe'), 'texto' => 'Servicios profesionales (SP)',      'lado' => 'compra'),
            'A'  => array('marcas' => array('Al', 'Alc'), 'texto' => 'Alquileres (A)',                    'lado' => 'compra'),
            'M'  => array('marcas' => array('Cm'),        'texto' => 'Comisiones (M)',                    'lado' => 'compra'),
            'I'  => array('marcas' => array('I'),         'texto' => 'Intereses (I)',                     'lado' => 'ambos'),
        );
    }
}

if (!function_exists('rep_d151_umbral')) {
    /**
     * Umbral anual por contraparte del D-151, en colones.
     *
     * La resolución fija un mínimo por debajo del cual la operación no se
     * reporta. Se deja configurable porque la DGT lo ha movido, y el informe
     * muestra qué quedó fuera y por qué en vez de esconderlo.
     */
    function rep_d151_umbral($settings = null)
    {
        $v = is_object($settings) ? ($settings->d151_umbral ?? null) : null;
        $v = (float) ($v ?: 2500000);
        return $v > 0 ? $v : 2500000;
    }
}

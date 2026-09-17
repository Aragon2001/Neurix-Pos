<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/* ═══════════════════════════════════════════════════════════════════════════
   MOTOR DE RECONCILIACION DE COMPROBANTES

   Compara una factura emitida contra el estado en que quedo despues de
   editarla y decide que documento de ajuste corresponde. Es la unica fuente de
   verdad de esa decision: el controlador la consulta y el navegador solo pinta
   lo que ella devuelve.

   No toca la base ni el framework a proposito, para que las reglas se puedan
   probar sueltas (tests/Unit/AjusteDocumentoTest.php).

   Restriccion externa (Reglamento de comprobantes electronicos, art. sobre
   correccion): un comprobante aceptado no se modifica ni se elimina; sus
   efectos se corrigen con una nota que lo referencia. Por eso el motor nunca
   devuelve "editar la factura": siempre devuelve un documento nuevo.
   ═══════════════════════════════════════════════════════════════════════════ */

if (!defined('AJUSTE_EPSILON')) {
    // Las columnas de montos son decimal(25,4): por debajo de medio diezmilesimo
    // dos importes son el mismo numero, no una diferencia.
    define('AJUSTE_EPSILON', 0.00005);
}

/**
 * Normaliza una linea —venga de tec_sale_items, de una nota o del carrito del
 * POS— a la forma unica que compara el motor.
 *
 * @param  array|object $l linea cruda
 * @return array
 */
if (!function_exists('ajuste_linea')) {
    function ajuste_linea($l)
    {
        $l = (array) $l;

        $tomar = function ($claves, $defecto = null) use ($l) {
            foreach ((array) $claves as $k) {
                if (isset($l[$k]) && $l[$k] !== '') {
                    return $l[$k];
                }
            }
            return $defecto;
        };

        $cantidad = (float) $tomar('quantity', 0);
        // net_unit_price es el precio sin impuesto con el descuento ya aplicado:
        // es el unico que cuadra con subtotal en tec_sale_items.
        $neto     = (float) $tomar(array('net_unit_price', 'unit_price'), 0);
        $bruto    = (float) $tomar(array('unit_price', 'net_unit_price'), 0);
        $tasa     = ajuste_tasa($tomar(array('tax', 'tax_rate'), '0'));

        // Hay dos formas de facturar el impuesto y las dos viven en la base:
        // agregado al precio (tax='13%', subtotal = base + impuesto) o ya
        // incluido (tax vacio, item_tax es la porcion contenida). La razon
        // impuesto/base reproduce cualquiera de las dos al prorratear la nota.
        $base      = $cantidad * $neto;
        $imp_ratio = $base > 0 ? ((float) $tomar('item_tax', 0)) / $base : 0.0;

        return array(
            'origen_id'   => (int) $tomar(array('linea_origen_id', 'sale_item_id', 'id'), 0),
            'product_id'  => (int) $tomar('product_id', 0),
            'codigo'      => (string) $tomar('product_code', ''),
            'nombre'      => (string) $tomar('product_name', ''),
            'cabys'       => (string) $tomar('cabys', ''),
            'cantidad'    => $cantidad,
            'neto'        => $neto,
            'bruto'       => $bruto,
            'descuento'   => (float) $tomar('item_discount', 0),
            'tasa'        => $tasa,
            'tax_txt'     => (string) $tomar(array('tax', 'tax_rate'), ''),
            'imp_ratio'   => round($imp_ratio, 8),
            'id_tax'      => (int) $tomar('id_tax', 0),
            'impuesto'    => (float) $tomar('item_tax', 0),
            'unidad'      => (string) $tomar('unit_of_measurement', 'Unid'),
            'comentario'  => (string) $tomar('comment', ''),
            'cod_impuesto' => (string) $tomar('codigo_impuesto', '01'),
            'cod_tarifa'  => (string) $tomar('codigo_tarifa', '01'),
            'costo'       => (float) $tomar('cost', 0),
            'subtotal'    => $cantidad * $neto,
        );
    }
}

/** El porcentaje de impuesto, venga como '13%', '13.00' o numero. */
if (!function_exists('ajuste_tasa')) {
    function ajuste_tasa($tax)
    {
        return (float) str_replace('%', '', (string) $tax);
    }
}

/**
 * Clave con que se aparean las lineas entre los dos documentos.
 *
 * El numero visual de linea no sirve: el usuario borra una del medio y todas
 * las de abajo cambian de posicion. Cuando la linea editada sabe de que fila de
 * la venta salio, esa fila manda; si no, se aparea por producto y precio.
 */
if (!function_exists('ajuste_clave_linea')) {
    function ajuste_clave_linea(array $l)
    {
        if (!empty($l['origen_id'])) {
            return 'o:' . $l['origen_id'];
        }
        return 'p:' . implode('|', array(
            $l['product_id'],
            $l['codigo'],
            $l['cabys'],
            number_format($l['bruto'], 4, '.', ''),
            number_format($l['tasa'], 2, '.', ''),
            $l['unidad'],
        ));
    }
}

/**
 * Totales de un juego de lineas.
 *
 * El impuesto se recalcula sobre el neto en vez de sumar item_tax: la linea
 * editada trae la cantidad nueva y el item_tax viejo, y sumarlo descuadraria
 * el documento contra Hacienda.
 */
if (!function_exists('ajuste_totales')) {
    function ajuste_totales(array $lineas)
    {
        $subtotal = 0.0; $impuesto = 0.0; $descuento = 0.0;

        foreach ($lineas as $l) {
            $base       = $l['cantidad'] * $l['neto'];
            $subtotal  += $base;
            $impuesto  += $base * ($l['tasa'] / 100);
            $descuento += $l['cantidad'] * max(0, $l['bruto'] - $l['neto']);
        }

        return array(
            'subtotal'  => round($subtotal, 4),
            'descuento' => round($descuento, 4),
            'impuesto'  => round($impuesto, 4),
            'total'     => round($subtotal + $impuesto, 4),
        );
    }
}

/**
 * Aparea las lineas de los dos documentos.
 *
 * Devuelve tres grupos: las que estan en ambos (por pares), las que solo estan
 * en el original —eliminadas— y las que solo estan en el modificado —agregadas.
 * Dos lineas del mismo producto y precio se aparean una a una en el orden en
 * que aparecen, que es como las ve el cajero.
 *
 * @return array{pares:array,eliminadas:array,agregadas:array}
 */
if (!function_exists('ajuste_aparear')) {
    function ajuste_aparear(array $original, array $modificado)
    {
        $cubos = array();
        foreach ($original as $i => $l) {
            $cubos[ajuste_clave_linea($l)][] = $i;
        }

        $pares = array(); $agregadas = array(); $usadas = array();

        foreach ($modificado as $l) {
            $clave = ajuste_clave_linea($l);
            if (!empty($cubos[$clave])) {
                $i = array_shift($cubos[$clave]);
                $usadas[$i] = true;
                $pares[] = array('antes' => $original[$i], 'ahora' => $l);
                continue;
            }
            $agregadas[] = $l;
        }

        $eliminadas = array();
        foreach ($original as $i => $l) {
            if (!isset($usadas[$i])) {
                $eliminadas[] = $l;
            }
        }

        return array('pares' => $pares, 'eliminadas' => $eliminadas, 'agregadas' => $agregadas);
    }
}

/** Dos importes son iguales si su diferencia no llega al ultimo decimal guardado. */
if (!function_exists('ajuste_igual')) {
    function ajuste_igual($a, $b)
    {
        return abs((float) $a - (float) $b) < AJUSTE_EPSILON;
    }
}

/**
 * Que cambio en un par de lineas apareadas.
 *
 * @return array lista de campos modificados, vacia si la linea quedo igual
 */
if (!function_exists('ajuste_cambios_de_linea')) {
    function ajuste_cambios_de_linea(array $antes, array $ahora)
    {
        $campos = array(
            'cantidad'  => array($antes['cantidad'],  $ahora['cantidad']),
            'precio'    => array($antes['bruto'],     $ahora['bruto']),
            'descuento' => array($antes['bruto'] - $antes['neto'], $ahora['bruto'] - $ahora['neto']),
            'impuesto'  => array($antes['tasa'],      $ahora['tasa']),
        );

        $out = array();
        foreach ($campos as $campo => $par) {
            if (!ajuste_igual($par[0], $par[1])) {
                $out[] = array('campo' => $campo, 'antes' => $par[0], 'ahora' => $par[1]);
            }
        }
        return $out;
    }
}

/**
 * La linea que va dentro del documento de ajuste.
 *
 * El documento declara la *diferencia*, no el estado final: si de 10 unidades
 * se devuelven 3, la nota lleva 3. Cuando ademas cambio el precio o el
 * descuento no hay una cantidad que represente el ajuste, asi que la linea sale
 * con cantidad 1 y el neto igual a la diferencia de la linea; la tarifa y el
 * CABYS se conservan porque en v4.4 son obligatorios y deben ser los del bien.
 *
 * @param  float $delta diferencia neta de la linea (positiva)
 * @return array linea normalizada
 */
if (!function_exists('ajuste_linea_de_nota')) {
    function ajuste_linea_de_nota(array $base, $cantidad, $neto, $delta = null)
    {
        $l = $base;
        $l['cantidad'] = round((float) $cantidad, 4);
        $l['neto']     = round((float) $neto, 4);
        // El ajuste a tanto alzado ya trae el descuento adentro: sumarle otra vez
        // el descuento unitario del original inflaria el bruto de la nota.
        $l['bruto']    = $delta !== null ? $l['neto'] : $l['neto'] + ($base['bruto'] - $base['neto']);
        $l['subtotal'] = round($l['cantidad'] * $l['neto'], 4);
        $l['impuesto'] = round($l['subtotal'] * ($l['tasa'] / 100), 4);
        $l['descuento'] = round($l['cantidad'] * ($l['bruto'] - $l['neto']), 4);
        if ($delta !== null) {
            $l['ajuste_parcial'] = true;
        }
        return $l;
    }
}

/**
 * Lo que ya se corrigio de una factura.
 *
 * Sin esto, reabrir una factura ya acreditada y volver a comparar contra el
 * original genera la misma nota dos veces. El saldo ajustable es lo que queda
 * del comprobante despues de las notas que ya lo afectaron.
 *
 * @param  float $total_original grand_total de la factura
 * @param  array $notas          filas con 'tipo' ('NC'|'ND') y 'total'; solo
 *                               cuentan las que Hacienda no rechazo
 * @return array
 */
if (!function_exists('ajuste_saldo')) {
    function ajuste_saldo($total_original, array $notas)
    {
        $acreditado = 0.0; $debitado = 0.0;

        foreach ($notas as $n) {
            $n = (array) $n;
            $estado = strtolower((string) (isset($n['estado']) ? $n['estado'] : ''));
            // Una nota rechazada no corrigio nada: su monto sigue disponible.
            if ($estado === 'rechazado' || $estado === 'error' || $estado === 'anulado') {
                continue;
            }
            $monto = (float) (isset($n['total']) ? $n['total'] : 0);
            if (strtoupper((string) $n['tipo']) === 'ND') {
                $debitado += $monto;
            } else {
                $acreditado += $monto;
            }
        }

        $original = (float) $total_original;

        return array(
            'original'   => round($original, 4),
            'acreditado' => round($acreditado, 4),
            'debitado'   => round($debitado, 4),
            'ajustable'  => round($original + $debitado - $acreditado, 4),
        );
    }
}

/**
 * El motor: compara los dos documentos y dice que corresponde emitir.
 *
 * @param  array $original  lineas de la factura tal como se emitio
 * @param  array $modificado lineas despues de editarlas en el POS
 * @param  array $opciones  'total_original', 'notas_previas', 'motivo'
 * @return array veredicto completo, listo para serializar al navegador
 */
if (!function_exists('ajuste_comparar')) {
    function ajuste_comparar(array $original, array $modificado, array $opciones = array())
    {
        $orig = array_map('ajuste_linea', $original);
        $nuev = array_map('ajuste_linea', $modificado);

        $tOrig = ajuste_totales($orig);
        $tNuev = ajuste_totales($nuev);

        $delta = array(
            'subtotal'  => round($tNuev['subtotal']  - $tOrig['subtotal'], 4),
            'descuento' => round($tNuev['descuento'] - $tOrig['descuento'], 4),
            'impuesto'  => round($tNuev['impuesto']  - $tOrig['impuesto'], 4),
            'total'     => round($tNuev['total']     - $tOrig['total'], 4),
        );

        $grupos  = ajuste_aparear($orig, $nuev);
        $cambios = array();
        $baja    = array();   // lineas de nota que reducen el comprobante
        $sube    = array();   // lineas de nota que lo aumentan

        foreach ($grupos['eliminadas'] as $l) {
            $cambios[] = array(
                'tipo' => 'eliminada', 'linea' => $l['nombre'],
                'monto' => -round($l['subtotal'] * (1 + $l['tasa'] / 100), 4),
            );
            $baja[] = ajuste_linea_de_nota($l, $l['cantidad'], $l['neto']);
        }

        foreach ($grupos['agregadas'] as $l) {
            $cambios[] = array(
                'tipo' => 'agregada', 'linea' => $l['nombre'],
                'monto' => round($l['subtotal'] * (1 + $l['tasa'] / 100), 4),
            );
            $sube[] = ajuste_linea_de_nota($l, $l['cantidad'], $l['neto']);
        }

        foreach ($grupos['pares'] as $par) {
            $campos = ajuste_cambios_de_linea($par['antes'], $par['ahora']);
            if (!$campos) {
                continue;
            }

            $antes = $par['antes']['cantidad'] * $par['antes']['neto'];
            $ahora = $par['ahora']['cantidad'] * $par['ahora']['neto'];
            $dif   = round($ahora - $antes, 4);

            $cambios[] = array(
                'tipo'   => 'modificada',
                'linea'  => $par['antes']['nombre'],
                'campos' => $campos,
                'monto'  => round($dif * (1 + $par['antes']['tasa'] / 100), 4),
            );

            if (ajuste_igual($dif, 0)) {
                continue;   // cambio sin efecto economico: no va en la nota
            }

            // Si lo unico que cambio fue la cantidad, la nota lleva las unidades
            // de diferencia al precio original, que es como se lee un devuelto.
            $soloCantidad = count($campos) === 1 && $campos[0]['campo'] === 'cantidad';
            $base = $par['antes'];

            if ($soloCantidad) {
                $linea = ajuste_linea_de_nota($base, abs($par['ahora']['cantidad'] - $par['antes']['cantidad']), $base['neto']);
            } else {
                $linea = ajuste_linea_de_nota($base, 1, abs($dif), abs($dif));
            }

            if ($dif < 0) { $baja[] = $linea; } else { $sube[] = $linea; }
        }

        return ajuste_veredicto($orig, $nuev, $tOrig, $tNuev, $delta, $cambios, $baja, $sube, $opciones);
    }
}

/**
 * Traduce el resultado de la comparacion a un documento concreto.
 *
 * Separado del recorrido de lineas para que las reglas de negocio se lean
 * seguidas y no mezcladas con el apareo.
 */
if (!function_exists('ajuste_veredicto')) {
    function ajuste_veredicto($orig, $nuev, $tOrig, $tNuev, $delta, $cambios, $baja, $sube, array $opciones)
    {
        $total_original = isset($opciones['total_original']) ? (float) $opciones['total_original'] : $tOrig['total'];
        $saldo = ajuste_saldo($total_original, isset($opciones['notas_previas']) ? $opciones['notas_previas'] : array());

        $r = array(
            'tipo'      => 'SIN_CAMBIOS',
            'etiqueta'  => 'ajuste_sin_cambios',
            'codigo_referencia' => null,
            'cambios'   => $cambios,
            'original'  => $tOrig,
            'nuevo'     => $tNuev,
            'delta'     => $delta,
            'saldo'     => $saldo,
            'lineas_nota' => array(),
            'total_nota'  => 0.0,
            'mixto'     => (bool) ($baja && $sube),
            'requiere_confirmacion' => false,
            'bloqueo'   => null,
            'avisos'    => array(),
        );

        // ── Sin cambios: no hay nada que declarar ──
        if (!$cambios) {
            $r['bloqueo'] = array('clave' => 'sin_cambios', 'mensaje' => 'ajuste_msg_sin_cambios');
            return $r;
        }

        // ── El documento quedo sin lineas: anulacion total ──
        if (!$nuev) {
            $r['tipo']     = 'ANULACION';
            $r['etiqueta'] = 'ajuste_anulacion';
            $r['codigo_referencia'] = '01';   // anula documento de referencia
            $r['lineas_nota'] = $baja;
            $r['total_nota']  = ajuste_totales($baja)['total'];
            $r['requiere_confirmacion'] = true;
            $r['mixto'] = false;
            $r['avisos'][] = 'ajuste_aviso_anulacion';
            return $r;
        }

        // ── Cambios que se compensan: no hay diferencia que declarar ──
        // Cubre tanto reordenar una linea sin tocar el importe como cambiar
        // 2 x 5.000 por 1 x 10.000, que fiscalmente deja la factura igual.
        if (ajuste_igual($delta['total'], 0)) {
            $r['bloqueo'] = array('clave' => 'sin_efecto', 'mensaje' => 'ajuste_msg_sin_efecto');
            return $r;
        }

        // El efecto economico neto manda, no cuantas lineas se tocaron: quitar
        // una de 5.000 y agregar otra de 20.000 sube la factura.
        $sentido = $delta['total'] < 0 ? 'baja' : 'sube';
        $lineas  = $sentido === 'baja' ? $baja : $sube;

        $r['tipo']     = $sentido === 'baja' ? 'NOTA_CREDITO' : 'NOTA_DEBITO';
        $r['etiqueta'] = $sentido === 'baja' ? 'ajuste_nota_credito' : 'ajuste_nota_debito';
        $r['codigo_referencia'] = $sentido === 'baja' ? '06' : '04';
        $r['lineas_nota'] = $lineas;
        $r['total_nota']  = ajuste_totales($lineas)['total'];
        $r['requiere_confirmacion'] = true;

        // Una nota de credito por el total exacto de la factura es una anulacion
        // encubierta: se declara como tal para que lleve el codigo 01.
        if ($sentido === 'baja' && ajuste_igual($r['total_nota'], $tOrig['total'])) {
            $r['tipo']     = 'ANULACION';
            $r['etiqueta'] = 'ajuste_anulacion';
            $r['codigo_referencia'] = '01';
            $r['avisos'][] = 'ajuste_aviso_anulacion';
        }

        // ── El ajuste mixto no cabe en un solo documento ──
        if ($r['mixto']) {
            $r['avisos'][] = 'ajuste_aviso_mixto';
        }

        // ── No se puede acreditar mas de lo que queda vivo del comprobante ──
        if ($sentido === 'baja' && $r['total_nota'] - $saldo['ajustable'] > AJUSTE_EPSILON) {
            $r['bloqueo'] = array('clave' => 'excede_saldo', 'mensaje' => 'ajuste_msg_excede_saldo');
        }

        return $r;
    }
}

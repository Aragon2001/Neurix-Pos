<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Gestion de los comprobantes que recibe la tienda como receptor: lectura del
 * XML, relacion de cada linea con un producto propio y reglas del mensaje
 * receptor. Todo es puro para poder probarlo sin base de datos.
 */

/** Destinos que puede tener una linea de un documento recibido. */
if (!function_exists('compra_destinos')) {
    function compra_destinos()
    {
        return array('inventario', 'gasto', 'activo', 'ignorar');
    }
}

/**
 * Condiciones del IVA que ofrece el sistema (Anexos v4.4, nota 18).
 * La 05, proporcionalidad, queda fuera: exige una prorrata que el sistema no lleva.
 */
if (!function_exists('compra_condiciones_iva')) {
    function compra_condiciones_iva()
    {
        return array('01', '02', '03', '04');
    }
}

/** Unidades de medida de servicio del catalogo de Hacienda. */
if (!function_exists('compra_unidad_es_servicio')) {
    function compra_unidad_es_servicio($unidad)
    {
        return in_array(trim((string) $unidad), array('Sp', 'Spe', 'St', 'Os', 'Al', 'Alc', 'Cm', 'I', 'h', 'd', 'min', 's'), true);
    }
}

/** Texto comparable: minusculas, sin tildes y sin signos ni espacios dobles. */
if (!function_exists('compra_texto_normalizado')) {
    function compra_texto_normalizado($texto)
    {
        $t = mb_strtolower(trim((string) $texto), 'UTF-8');
        $t = strtr($t, array(
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        ));
        $t = preg_replace('/[^a-z0-9]+/', ' ', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }
}

/** Un codigo de barras EAN/UPC: solo digitos, de 8 a 14. */
if (!function_exists('compra_es_codigo_barras')) {
    function compra_es_codigo_barras($codigo)
    {
        return (bool) preg_match('/^\d{8,14}$/', trim((string) $codigo));
    }
}

/**
 * Traduce un comprobante recibido a las tres estructuras que guarda el modelo.
 *
 * Lee la v4.4 y conserva la lectura de la v4.3 donde el nodo cambio de lugar:
 * descuento dentro de <Descuento>, codigo en <CodigoComercial> y medio de pago
 * dentro de <ResumenFactura>.
 *
 * @param  SimpleXMLElement $d
 * @return array{0: array, 1: array, 2: array} documento, proveedor y lineas
 */
if (!function_exists('compra_mapear_xml')) {
    function compra_mapear_xml($d, $xmlCrudo, $store_id)
    {
        $tipos = array(
            'TiqueteElectronico'       => 'Tiquete Electronico',
            'NotaCreditoElectronica'   => 'Nota de Credito Electronica',
            'NotaDebitoElectronica'    => 'Nota de Debito Electronica',
            'FacturaElectronicaCompra' => 'Factura Electronica de Compra',
        );
        $resumen = $d->ResumenFactura;

        $medio = '';
        if (isset($resumen->MedioPago->TipoMedioPago)) {
            $medio = (string) $resumen->MedioPago[0]->TipoMedioPago;
        } elseif (isset($d->MedioPago)) {
            $medio = (string) $d->MedioPago[0];
        }

        $documento = array(
            'documento'               => isset($tipos[$d->getName()]) ? $tipos[$d->getName()] : 'Factura Electronica',
            'nombre_emisor'           => trim((string) $d->Emisor->Nombre),
            'tipo_doc_emisor'         => (string) $d->Emisor->Identificacion->Tipo,
            'telefono_emisor'         => (string) $d->Emisor->Telefono->NumTelefono,
            'correo_emisor'           => (string) $d->Emisor->CorreoElectronico,
            'NumeroCedulaEmisor'      => (string) $d->Emisor->Identificacion->Numero,
            'FechaEmisionDoc'         => (string) $d->FechaEmision,
            'ClaveDocEmisor'          => (string) $d->Clave,
            'ConsecutivoDocEmisor'    => (string) $d->NumeroConsecutivo,
            'ClaveReferencia'         => (string) $d->InformacionReferencia->Numero,
            'Mensaje'                 => '',
            'DetalleMensaje'          => '',
            'CondicionVenta'          => (string) $d->CondicionVenta,
            'MedioPago'               => mb_substr($medio, 0, 3),
            'CodigoMoneda'            => (string) $resumen->CodigoTipoMoneda->CodigoMoneda ?: ((string) $resumen->CodigoMoneda ?: 'CRC'),
            'TipoCambio'              => (float) ((string) $resumen->CodigoTipoMoneda->TipoCambio ?: ((string) $resumen->TipoCambio ?: 1)),
            'TotalServGravados'       => (float) $resumen->TotalServGravados,
            'TotalServExentos'        => (float) $resumen->TotalServExentos,
            'TotalMercanciasGravadas' => (float) $resumen->TotalMercanciasGravadas,
            'TotalMercanciasExentas'  => (float) $resumen->TotalMercanciasExentas,
            'TotalGravado'            => (float) $resumen->TotalGravado,
            'TotalExento'             => (float) $resumen->TotalExento,
            'TotalVenta'              => (float) $resumen->TotalVenta,
            'TotalVentaNeta'          => (float) $resumen->TotalVentaNeta,
            'MontoTotalImpuesto'      => (float) $resumen->TotalImpuesto,
            'TotalFactura'            => (float) $resumen->TotalComprobante,
            'xml_compra'              => $xmlCrudo,
            'store_id'                => $store_id,
        );

        $proveedor = array(
            'name'  => $documento['nombre_emisor'],
            'cf1'   => $documento['tipo_doc_emisor'],
            'cf2'   => $documento['NumeroCedulaEmisor'],
            'phone' => $documento['telefono_emisor'],
            'email' => $documento['correo_emisor'],
        );

        $lineas = array();
        $n = 0;
        foreach ($d->DetalleServicio->LineaDetalle as $l) {
            $n++;
            $lineas[] = compra_mapear_linea($l, $n, $documento);
        }

        return array($documento, $proveedor, $lineas);
    }
}

/** @param SimpleXMLElement $l */
if (!function_exists('compra_mapear_linea')) {
    function compra_mapear_linea($l, $posicion, array $documento)
    {
        // Se prefiere el codigo del vendedor (tipo 01): es el que se repite en
        // la proxima factura del mismo proveedor.
        $codigo = '';
        $tipo   = '';
        $barras = '';
        foreach ($l->CodigoComercial as $c) {
            $valor = trim((string) $c->Codigo);
            if ($valor === '') {
                continue;
            }
            if ($codigo === '' || (string) $c->Tipo === '01') {
                $codigo = $valor;
                $tipo   = (string) $c->Tipo;
            }
            if ($barras === '' && compra_es_codigo_barras($valor)) {
                $barras = $valor;
            }
        }
        if ($codigo === '' && isset($l->Codigo->Codigo)) {
            $codigo = trim((string) $l->Codigo->Codigo);
        }

        $descuento = 0.0;
        foreach ($l->Descuento as $x) {
            $descuento += (float) $x->MontoDescuento;
        }
        if (isset($l->MontoDescuento)) {
            $descuento += (float) $l->MontoDescuento;
        }

        $impuesto = 0.0;
        $tarifa   = null;
        $codigoTarifa = '';
        foreach ($l->Impuesto as $i) {
            $impuesto += (float) $i->Monto;
            if ($tarifa === null && (string) $i->Codigo === '01') {
                $tarifa = (float) $i->Tarifa;
                $codigoTarifa = (string) ($i->CodigoTarifaIVA ?: $i->CodigoTarifa);
            }
        }
        $impuestoNeto = isset($l->ImpuestoNeto) ? (float) $l->ImpuestoNeto : $impuesto;

        // Hay emisores que escriben la cantidad con coma decimal.
        $cantidad = (float) str_replace(',', '.', (string) $l->Cantidad);
        $subtotal = (float) $l->SubTotal;
        $unidad   = (string) $l->UnidadMedida;

        return array(
            'numero_linea'        => (int) ((string) $l->NumeroLinea ?: $posicion),
            'code'                => mb_substr($codigo, 0, 50),
            'codigo_tipo'         => $tipo,
            'codigo_barras'       => $barras,
            'cabys'               => (string) ($l->CodigoCABYS ?: $l->Codigo),
            'clave'               => $documento['ClaveDocEmisor'],
            'consecutivo'         => $documento['ConsecutivoDocEmisor'],
            'name'                => mb_substr(trim((string) $l->Detalle), 0, 255),
            'quantity'            => $cantidad,
            'cost'                => $cantidad > 0 ? $subtotal / $cantidad : 0,
            'type'                => compra_unidad_es_servicio($unidad) ? 'service' : 'standard',
            'unit_of_measurement' => mb_substr($unidad, 0, 10),
            'precio_unitario'     => (float) $l->PrecioUnitario,
            'tarifa_impuesto'     => (float) $tarifa,
            'codigo_tarifa'       => $codigoTarifa,
            'monto_impuesto'      => $impuesto,
            'impuesto_neto'       => $impuestoNeto,
            'monto_descuento'     => $descuento,
            'SubTotal'            => $subtotal,
            'MontoTotalLinea'     => (float) $l->MontoTotalLinea,
        );
    }
}

/**
 * Busca a que producto propio corresponde una linea del proveedor.
 *
 * Lo aprendido (codigo o descripcion del mismo proveedor) y el codigo de barras
 * se aplican solos. El CABYS solo sugiere: agrupa productos distintos bajo un
 * mismo codigo y relacionar mal suma existencias al producto equivocado.
 *
 * @param array $linea     code, codigo_barras, name, cabys
 * @param array $mapas     relaciones del proveedor: codigo, descripcion, product_id, factor
 * @param array $porCodigo productos indexados por su codigo
 * @param array $porCabys  listas de productos indexadas por CABYS
 * @return array{product_id: int, factor: float, confianza: string, origen: string}
 */
if (!function_exists('compra_relacionar')) {
    function compra_relacionar(array $linea, array $mapas, array $porCodigo, array $porCabys)
    {
        $vacio  = array('product_id' => 0, 'factor' => 1.0, 'confianza' => 'ninguna', 'origen' => '');
        $codigo = trim((string) ($linea['code'] ?? ''));
        $nombre = compra_texto_normalizado($linea['name'] ?? '');

        if ($codigo !== '' && $codigo !== '0') {
            foreach ($mapas as $m) {
                if ((string) $m['codigo'] === $codigo) {
                    return array('product_id' => (int) $m['product_id'], 'factor' => max((float) $m['factor'], 0.0001), 'confianza' => 'auto', 'origen' => 'codigo');
                }
            }
        }

        if ($nombre !== '') {
            foreach ($mapas as $m) {
                if ((string) $m['descripcion'] === $nombre) {
                    return array('product_id' => (int) $m['product_id'], 'factor' => max((float) $m['factor'], 0.0001), 'confianza' => 'auto', 'origen' => 'descripcion');
                }
            }
        }

        $barras = array_filter(array($linea['codigo_barras'] ?? '', $codigo), 'compra_es_codigo_barras');
        foreach ($barras as $b) {
            if (isset($porCodigo[$b])) {
                return array('product_id' => (int) $porCodigo[$b]['id'], 'factor' => 1.0, 'confianza' => 'auto', 'origen' => 'barras');
            }
        }

        $cabys = (string) ($linea['cabys'] ?? '');
        if ($cabys !== '' && $nombre !== '' && !empty($porCabys[$cabys])) {
            $mejor = null;
            $puntaje = 0.0;
            foreach ($porCabys[$cabys] as $p) {
                similar_text($nombre, compra_texto_normalizado($p['name']), $pct);
                if ($pct > $puntaje) {
                    $puntaje = $pct;
                    $mejor = $p;
                }
            }
            if ($mejor && $puntaje >= 50) {
                return array('product_id' => (int) $mejor['id'], 'factor' => 1.0, 'confianza' => 'sugerida', 'origen' => 'cabys');
            }
        }

        return $vacio;
    }
}

/**
 * Destino con el que arranca una linea. Vacio obliga a elegirlo.
 *
 * @param string $habitual destino que el proveedor suele tener
 */
if (!function_exists('compra_destino_sugerido')) {
    function compra_destino_sugerido(array $linea, array $relacion, $habitual)
    {
        if ($relacion['confianza'] === 'auto') {
            return 'inventario';
        }
        if (in_array($habitual, compra_destinos(), true)) {
            return $habitual;
        }
        if (compra_unidad_es_servicio($linea['unit_of_measurement'] ?? '')) {
            return 'gasto';
        }
        return '';
    }
}

/**
 * Condicion del IVA con la que arranca el documento. Vacia cuando no trae
 * impuesto: el nodo solo aplica si el comprobante lo tiene.
 *
 * @param string[] $destinos destino de cada linea
 */
if (!function_exists('compra_condicion_sugerida')) {
    function compra_condicion_sugerida(array $destinos, $impuesto, $habitual)
    {
        if ((float) $impuesto <= 0) {
            return '';
        }
        if (in_array($habitual, compra_condiciones_iva(), true)) {
            return $habitual;
        }
        $utiles = array_values(array_filter($destinos, function ($x) { return $x !== 'ignorar' && $x !== ''; }));
        if ($utiles && count(array_unique($utiles)) === 1 && $utiles[0] === 'activo') {
            return '03';
        }
        return '01';
    }
}

/**
 * MontoTotalImpuestoAcreditar y MontoTotalDeGastoAplicable (Anexos v4.4, pag. 61).
 * El gasto es siempre lo que queda del total despues del credito.
 *
 * @param string $mensaje 1 acepta, 2 parcial, 3 rechaza
 * @param float  $parcial impuesto a acreditar que declara el usuario, solo en la 02
 * @return array{acreditar: float, gasto: float}
 */
if (!function_exists('compra_montos_fiscales')) {
    function compra_montos_fiscales($mensaje, $condicion, $impuesto, $total, $parcial = 0)
    {
        $impuesto = (float) $impuesto;
        $total    = (float) $total;

        if ((string) $mensaje === '3' || $condicion === '' || $condicion === null) {
            return array('acreditar' => 0.0, 'gasto' => 0.0);
        }

        switch ($condicion) {
            case '02':
                $acreditar = min(max((float) $parcial, 0), $impuesto);
                break;
            case '04':
                $acreditar = 0.0;
                break;
            default:
                $acreditar = $impuesto;
        }

        return array('acreditar' => round($acreditar, 5), 'gasto' => round($total - $acreditar, 5));
    }
}

/**
 * Costo de una linea en colones, por unidad del producto propio.
 *
 * El IVA que se acredita no es costo; el que no, si. En la condicion 02 la
 * parte no acreditable se reparte entre las lineas en proporcion a su impuesto.
 *
 * @param float $proporcionNoAcreditable 0 si todo el IVA se acredita, 1 si nada
 * @return array{unitario: float, total: float, impuesto: float}
 */
if (!function_exists('compra_costo_linea')) {
    function compra_costo_linea(array $linea, $proporcionNoAcreditable, $factor, $tipoCambio)
    {
        $tc       = (float) $tipoCambio > 0 ? (float) $tipoCambio : 1.0;
        $impuesto = (float) $linea['impuesto_neto'];
        $total    = ((float) $linea['SubTotal'] + $impuesto * min(max((float) $proporcionNoAcreditable, 0), 1)) * $tc;
        $unidades = (float) $linea['quantity'] * max((float) $factor, 0.0001);

        return array(
            'unitario' => $unidades > 0 ? round($total / $unidades, 4) : 0.0,
            'total'    => round($total, 4),
            'impuesto' => round($impuesto * $tc, 4),
        );
    }
}

/** Parte del IVA del documento que no se acredita, entre 0 y 1. */
if (!function_exists('compra_proporcion_no_acreditable')) {
    function compra_proporcion_no_acreditable($condicion, $impuesto, $acreditar)
    {
        if ($condicion === '04') {
            return 1.0;
        }
        if ($condicion === '02' && (float) $impuesto > 0) {
            return round(1 - ((float) $acreditar / (float) $impuesto), 6);
        }
        return 0.0;
    }
}

/**
 * Precio de venta que se sugiere al recibir mercaderia. Nunca se aplica solo.
 *
 * Con margen definido se usa la misma formula de la ficha del producto; sin el,
 * se conserva la relacion precio/costo que el producto ya tenia.
 *
 * @param object|array $producto cost, price, margen, tax_method, tax
 */
if (!function_exists('compra_precio_sugerido')) {
    function compra_precio_sugerido($costoUnitario, $producto)
    {
        $p = (array) $producto;
        $costo  = (float) $costoUnitario;
        $margen = (float) ($p['margen'] ?? 0);
        $costoAnterior  = (float) ($p['cost'] ?? 0);
        $precioAnterior = (float) ($p['price'] ?? 0);

        if ($costo <= 0) {
            return 0.0;
        }
        if ($margen > 0) {
            $precio = $costo * (1 + $margen / 100);
            // Precio con impuesto incluido: la ficha guarda el precio final.
            if ((string) ($p['tax_method'] ?? '1') === '0') {
                $precio *= 1 + ((float) ($p['tax'] ?? 0)) / 100;
            }
            return round($precio, 2);
        }
        if ($costoAnterior > 0 && $precioAnterior > 0) {
            return round($costo * $precioAnterior / $costoAnterior, 2);
        }
        return 0.0;
    }
}

/**
 * Revisa lo que el usuario decidio antes de tocar la base.
 *
 * @param array  $lineas  lineas guardadas del documento, en orden
 * @param array  $entrada por linea: destino, product_id, factor, categoria_gasto_id, aplicar_precio, precio
 * @param array  $fiscal  mensaje, condicion, acreditar, detalle, pendiente (bool)
 * @return array lista de errores: clave de idioma y, si aplica, numero de linea
 */
if (!function_exists('compra_validar_gestion')) {
    function compra_validar_gestion(array $lineas, array $entrada, array $fiscal, $impuesto)
    {
        $errores = array();
        $mensaje = (string) ($fiscal['mensaje'] ?? '');

        if (!empty($fiscal['pendiente'])) {
            if (!in_array($mensaje, array('1', '2', '3'), true)) {
                $errores[] = array('clave' => 'aceptacion_mensaje_invalido');
                return $errores;
            }
            $detalle = trim((string) ($fiscal['detalle'] ?? ''));
            $largo = mb_strlen($detalle);
            if ($mensaje !== '1' && $largo === 0) {
                $errores[] = array('clave' => 'aceptacion_detalle_requerido');
            } elseif ($largo > 0 && ($largo < 5 || $largo > 160)) {
                $errores[] = array('clave' => 'gestion_detalle_largo');
            }
            if ($mensaje !== '3' && (float) $impuesto > 0) {
                $condicion = (string) ($fiscal['condicion'] ?? '');
                if (!in_array($condicion, compra_condiciones_iva(), true)) {
                    $errores[] = array('clave' => 'gestion_condicion_requerida');
                } elseif ($condicion === '02') {
                    $a = (float) ($fiscal['acreditar'] ?? 0);
                    if ($a <= 0 || $a >= (float) $impuesto) {
                        $errores[] = array('clave' => 'gestion_parcial_fuera_de_rango');
                    }
                }
            }
        }

        if ($mensaje === '3') {
            return $errores;
        }

        foreach (array_values($lineas) as $i => $l) {
            $e = $entrada[$i] ?? null;
            $n = (int) ($l['numero_linea'] ?? ($i + 1));
            if (!$e || !in_array($e['destino'] ?? '', compra_destinos(), true)) {
                $errores[] = array('clave' => 'gestion_linea_sin_destino', 'linea' => $n);
                continue;
            }
            if ($e['destino'] === 'inventario') {
                if ((int) ($e['product_id'] ?? 0) <= 0) {
                    $errores[] = array('clave' => 'gestion_linea_sin_producto', 'linea' => $n);
                }
                if ((float) ($e['factor'] ?? 0) <= 0) {
                    $errores[] = array('clave' => 'gestion_factor_invalido', 'linea' => $n);
                }
                if (!empty($e['aplicar_precio']) && (float) ($e['precio'] ?? 0) <= 0) {
                    $errores[] = array('clave' => 'gestion_precio_invalido', 'linea' => $n);
                }
            }
            if ($e['destino'] === 'gasto' && (int) ($e['categoria_gasto_id'] ?? 0) <= 0) {
                $errores[] = array('clave' => 'gestion_linea_sin_categoria', 'linea' => $n);
            }
        }

        return $errores;
    }
}

/**
 * Lo que se repite en las decisiones de un documento, para proponerlo en el
 * siguiente del mismo proveedor.
 *
 * @return array{destino: string|null, categoria: int|null}
 */
if (!function_exists('compra_habitos')) {
    function compra_habitos(array $entrada)
    {
        $destinos = array();
        $categorias = array();
        foreach ($entrada as $e) {
            $d = $e['destino'] ?? '';
            if ($d === '' || $d === 'ignorar') {
                continue;
            }
            $destinos[$d] = ($destinos[$d] ?? 0) + 1;
            if ($d === 'gasto' && (int) ($e['categoria_gasto_id'] ?? 0) > 0) {
                $c = (int) $e['categoria_gasto_id'];
                $categorias[$c] = ($categorias[$c] ?? 0) + 1;
            }
        }
        arsort($destinos);
        arsort($categorias);

        return array(
            'destino'   => $destinos ? (string) array_key_first($destinos) : null,
            'categoria' => $categorias ? (int) array_key_first($categorias) : null,
        );
    }
}

/** Dias de venta que debe cubrir una compra sugerida. */
if (!defined('COMPRA_DIAS_COBERTURA')) {
    define('COMPRA_DIAS_COBERTURA', 15);
}
/** Dias que se suponen entre pedir y recibir: por debajo de eso ya es tarde. */
if (!defined('COMPRA_DIAS_ENTREGA')) {
    define('COMPRA_DIAS_ENTREGA', 7);
}

/**
 * Cuanto reponer de un producto segun lo que se vende.
 *
 * Se pide cuando la existencia no alcanza el minimo o no cubre los dias de
 * entrega, y se sugiere lo que falta para cubrir la cobertura mas el minimo,
 * redondeado al empaque en que lo vende el proveedor.
 *
 * @param float $vendido unidades vendidas en el periodo
 * @param int   $dias    dias del periodo
 * @param float $empaque unidades por empaque del proveedor, 1 si vende suelto
 * @return array{venta_diaria: float, cobertura: float|null, punto: float, reponer: bool, sugerido: float}
 */
if (!function_exists('compra_reorden')) {
    function compra_reorden($existencia, $minimo, $vendido, $dias, $empaque = 1)
    {
        $existencia = (float) $existencia;
        $minimo     = max((float) $minimo, 0);
        $diaria     = $dias > 0 ? max((float) $vendido, 0) / $dias : 0.0;
        $punto      = max($minimo, $diaria * COMPRA_DIAS_ENTREGA);

        $reponer = $punto > 0 && $existencia <= $punto;
        $falta   = $diaria * COMPRA_DIAS_COBERTURA + $minimo - $existencia;
        $empaque = max((float) $empaque, 1);
        $sugerido = $reponer && $falta > 0 ? ceil(round($falta / $empaque, 6)) * $empaque : 0.0;

        return array(
            'venta_diaria' => round($diaria, 3),
            'cobertura'    => $diaria > 0 ? round(max($existencia, 0) / $diaria, 1) : null,
            'punto'        => round($punto, 2),
            'reponer'      => $reponer,
            'sugerido'     => (float) $sugerido,
        );
    }
}

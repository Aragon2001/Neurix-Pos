<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reglas de decisión del comprobante y del crédito.
 *
 * Son funciones puras a propósito: reciben la fila del cliente y los ajustes, y
 * devuelven qué se puede emitir y por qué no. La misma respuesta la usa el POS
 * para dibujar los botones y `Pos.php` para volver a comprobarlo al cobrar, que
 * es la única comprobación que cuenta.
 */

if (!function_exists('cliente_facturable')) {
    /**
     * ¿Este cliente admite una factura electrónica?
     *
     * El esquema exige `<Identificacion>` en el receptor de la factura, y el
     * anexo verifica que el número exista en el padrón. Sin nombre o sin cédula
     * bien formada, el comprobante que corresponde es el tiquete.
     *
     * @param  object|array|null $cliente fila de `customers`
     * @return array{ok: bool, motivo: string} `motivo` es una clave de idioma
     */
    function cliente_facturable($cliente)
    {
        $c = (object) (array) $cliente;

        $nombre = trim((string) ($c->name ?? ''));
        if (mb_strlen($nombre) < 3) {
            return array('ok' => false, 'motivo' => 'fact_falta_nombre');
        }
        if (mb_strlen($nombre) > 100) {
            return array('ok' => false, 'motivo' => 'fact_nombre_largo');
        }

        $tipo   = (string) ($c->cf1 ?? '');
        $numero = trim((string) ($c->cf2 ?? ''));

        if ($numero === '') {
            return array('ok' => false, 'motivo' => 'fact_falta_cedula');
        }

        // El 05 "Extranjero No Domiciliado" solo entra en la factura con
        // condición de venta 12, que el POS no maneja (Anexos v4.4, nota 4
        // pie 16); el 06 nunca es receptor de una venta.
        if ($tipo === '05') {
            return array('ok' => false, 'motivo' => 'fact_extranjero_tiquete');
        }
        if ($tipo === '06') {
            return array('ok' => false, 'motivo' => 'fact_no_contribuyente');
        }

        $r = identificacion_valida($tipo, $numero);
        if (!$r['ok']) {
            return array('ok' => false, 'motivo' => $r['error']);
        }

        return array('ok' => true, 'motivo' => '');
    }
}

if (!function_exists('comprobantes_permitidos')) {
    /**
     * Qué comprobantes admite este cliente, y por qué no los demás.
     *
     * @param  object|array|null $cliente        fila de `customers`
     * @param  int               $cliente_paso   id del cliente de paso (ajuste)
     * @return array<string, array{ok: bool, motivo: string}> indexado por tipo
     *         de comprobante ('01' factura, '04' tiquete)
     */
    function comprobantes_permitidos($cliente, $cliente_paso = 1)
    {
        $c  = (object) (array) $cliente;
        $es_paso = ((int) ($c->id ?? 0)) === (int) $cliente_paso;

        // El tiquete admite receptor opcional: siempre se puede emitir.
        $permitidos = array('04' => array('ok' => true, 'motivo' => ''));

        if ($es_paso) {
            $permitidos['01'] = array('ok' => false, 'motivo' => 'fact_cliente_de_paso');
            return $permitidos;
        }

        $permitidos['01'] = cliente_facturable($c);
        return $permitidos;
    }
}

if (!function_exists('credito_habilitado')) {
    /** ¿El negocio vende a crédito? */
    function credito_habilitado($Settings)
    {
        return !empty($Settings->enable_credit) && (string) $Settings->enable_credit !== '0';
    }
}

if (!function_exists('credito_disponible')) {
    /**
     * Cuánto crédito le queda al cliente.
     *
     * @param  object|array|null $cliente fila de `customers`
     * @param  float             $deuda   saldo pendiente, de `Customers::getdeuda()`
     * @return float negativo si ya se pasó del límite
     */
    function credito_disponible($cliente, $deuda = 0)
    {
        $c = (object) (array) $cliente;
        $limite = (float) ($c->limitcredit ?? 0);
        return round($limite - (float) $deuda, 4);
    }
}

if (!function_exists('puede_vender_a_credito')) {
    /**
     * ¿Se puede cobrar esta venta a crédito?
     *
     * @param  object|array|null $cliente
     * @param  float             $monto        de la venta que se quiere fiar
     * @param  float             $deuda        saldo pendiente del cliente
     * @param  object            $Settings     ajustes del sistema
     * @param  int               $cliente_paso id del cliente de paso
     * @return array{ok: bool, motivo: string, disponible: float, faltante: float}
     */
    function puede_vender_a_credito($cliente, $monto, $deuda, $Settings, $cliente_paso = 1)
    {
        $c = (object) (array) $cliente;
        $monto = (float) $monto;

        if (!credito_habilitado($Settings)) {
            return array('ok' => false, 'motivo' => 'credito_deshabilitado', 'disponible' => 0.0, 'faltante' => $monto);
        }

        // Al cliente de paso no se le fía: no hay a quién cobrarle después.
        if (((int) ($c->id ?? 0)) === (int) $cliente_paso) {
            return array('ok' => false, 'motivo' => 'credito_cliente_de_paso', 'disponible' => 0.0, 'faltante' => $monto);
        }

        $disponible = credito_disponible($c, $deuda);

        if ((float) ($c->limitcredit ?? 0) <= 0) {
            return array('ok' => false, 'motivo' => 'credito_sin_limite', 'disponible' => 0.0, 'faltante' => $monto);
        }

        if ($monto > $disponible + 0.001) {
            return array('ok' => false, 'motivo' => 'credito_excede_limite',
                         'disponible' => $disponible,
                         'faltante' => round($monto - $disponible, 4));
        }

        return array('ok' => true, 'motivo' => '', 'disponible' => $disponible, 'faltante' => 0.0);
    }
}

if (!function_exists('plazo_credito_dias')) {
    /**
     * Días de crédito que se declaran en `<PlazoCredito>`.
     *
     * Se emitía como "30 dias" fijo, sin relación con el cliente.
     *
     * @param  object|array|null $cliente
     * @param  int               $por_defecto días cuando el cliente no lo tiene
     */
    function plazo_credito_dias($cliente, $por_defecto = 30)
    {
        $c = (object) (array) $cliente;
        $dias = (int) ($c->dias_credito ?? 0);
        return $dias > 0 ? $dias : (int) $por_defecto;
    }
}

if (!function_exists('tipos_exoneracion')) {
    /**
     * `TipoDocumentoEX1` del comprobante (TipoExoneracionType del XSD v4.4).
     *
     * No es texto libre: Hacienda rechaza cualquier valor fuera de esta lista.
     */
    function tipos_exoneracion()
    {
        return array(
            '01' => 'Compras autorizadas por la Direccion General de Tributacion',
            '02' => 'Ventas exentas a diplomaticos',
            '03' => 'Autorizado por Ley Especial',
            '04' => 'Exenciones Direccion General de Hacienda, autorizacion local generica',
            '05' => 'Exenciones Direccion General de Hacienda, Transitorio V',
            '06' => 'Servicios turisticos inscritos ante el ICT',
            '07' => 'Transitorio XVII (reciclaje y reutilizacion)',
            '08' => 'Exoneracion a Zona Franca',
            '09' => 'Servicios complementarios para la exportacion (art. 11 RLIVA)',
            '10' => 'Organo de las corporaciones municipales',
            '11' => 'Exenciones Direccion General de Hacienda, autorizacion concreta',
            '99' => 'Otros',
        );
    }
}

if (!function_exists('instituciones_exoneracion')) {
    /**
     * `NombreInstitucion` del comprobante: un codigo de dos digitos, no el
     * nombre. Escribir "Ministerio de Hacienda" hacia rechazar el comprobante.
     */
    function instituciones_exoneracion()
    {
        return array(
            '01' => 'Ministerio de Hacienda',
            '02' => 'Ministerio de Relaciones Exteriores y Culto',
            '03' => 'Ministerio de Agricultura y Ganaderia',
            '04' => 'Ministerio de Economia, Industria y Comercio',
            '05' => 'Cruz Roja Costarricense',
            '06' => 'Benemerito Cuerpo de Bomberos de Costa Rica',
            '07' => 'Asociacion Obras del Espiritu Santo',
            '08' => 'Federacion Cruzada Nacional de proteccion al Anciano (Fecrunapa)',
            '09' => 'Escuela de Agricultura de la Region Humeda (EARTH)',
            '10' => 'Instituto Centroamericano de Administracion de Empresas (INCAE)',
            '11' => 'Junta de Proteccion Social (JPS)',
            '12' => 'Autoridad Reguladora de los Servicios Publicos (Aresep)',
            '99' => 'Otros',
        );
    }
}

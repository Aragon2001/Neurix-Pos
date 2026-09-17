<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Crearxml
{

    public function __construct()
    { }

    public function __get($var)
    {
        return get_instance()->$var;
    }

    /**
     * Fecha de un comprobante en el formato dateTime del XSD.
     *
     * MySQL devuelve ' 2026-08-26 00:31:57' con espacio y el esquema exige la T
     * de ISO 8601: sin convertirla, Hacienda rechaza la nota con
     * 'is not a valid value for dateTime' senalando <FechaEmisionIR>.
     *
     * @param string $valor fecha tal como vino de la base
     */
    public function fechaIso($valor)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return date('Y-m-d\TH:i:s');
        }
        $t = strtotime($valor);
        return $t ? date('Y-m-d\TH:i:s', $t) : str_replace(' ', 'T', $valor);
    }

    /**
     * <TipoDocIR>: que clase de documento es el que se referencia.
     *
     * Sale del comprobante referenciado, no de la nota: una nota de credito
     * sobre una factura electronica declara 01 y sobre un tiquete 04. Estaba
     * fijo en 04, asi que toda nota sobre una factura mentia el tipo.
     *
     * @param object $referencia fila de hacienda_tiketes del documento original
     */
    private function tipoDocReferencia($referencia)
    {
        $tipo = isset($referencia->tipo_doc) ? preg_replace('/\D/', '', (string) $referencia->tipo_doc) : '';
        return $tipo !== '' ? str_pad($tipo, 2, '0', STR_PAD_LEFT) : '04';
    }

    /**
     * Traduce paid_by al codigo de TipoMedioPago de Hacienda (Anexos v4.4, nota 6).
     *
     * La tabla vive en medio_pago_hacienda() para que el comprobante y lo que
     * muestra el detalle de la venta no puedan discrepar.
     */
    private function tipoMedioPago($paid_by)
    {
        $medio = medio_pago_hacienda($paid_by);
        return $medio['codigo'];
    }

    /**
     * Cedula del proveedor del sistema de facturacion.
     *
     * Obligatorio en los siete comprobantes de la v4.4 (Anexos v4.4, encabezado).
     * En un desarrollo propio o a la medida el anexo manda declarar la
     * identificacion del mismo obligado tributario, asi que sin ajuste propio
     * se cae a la cedula del emisor.
     */
    private function proveedorSistemas()
    {
        $cedula = isset($this->Settings->cedula_proveedor_sistemas)
            ? trim(str_replace("-", "", (string) $this->Settings->cedula_proveedor_sistemas))
            : '';

        return $cedula !== '' ? $cedula : trim(str_replace("-", "", (string) $this->Settings->cedula_emisor));
    }

    /**
     * Codigo CABYS de una linea de detalle.
     *
     * En la v4.4 el CABYS tiene elemento propio y obligatorio de 13 digitos;
     * antes viajaba como un CodigoComercial de tipo 05. Sin CABYS valido el
     * comprobante se rechaza, asi que un codigo que no calce queda avisado.
     *
     * @param string $cabys    codigo del producto
     * @param string $respaldo codigo de barras, cuando ya es un CABYS de 13 digitos
     */
    /**
     * CABYS efectivo de una linea, en el mismo orden que usa bloqueCodigoCabys().
     *
     * @param object|false $row   ficha del producto, o false si es un articulo rapido
     * @param array        $items renglon de la venta
     */
    private function cabysDeLinea($row, $items)
    {
        foreach (array(is_object($row) ? ($row->cabys ?? '') : '', $items['cabys'] ?? '', $items['product_code'] ?? '') as $candidato) {
            $limpio = preg_replace('/\D/', '', (string) $candidato);
            if (strlen($limpio) === 13) {
                return $limpio;
            }
        }

        return '';
    }

    private function bloqueCodigoCabys(...$candidatos)
    {
        $codigo = '';

        // Un articulo rapido no tiene ficha de producto y su CABYS viaja en la
        // linea de venta; si tampoco esta, queda el codigo con que se registro.
        foreach ($candidatos as $candidato) {
            $limpio = preg_replace('/\D/', '', (string) $candidato);
            if (strlen($limpio) === 13) {
                $codigo = $limpio;
                break;
            }
        }

        if (strlen($codigo) !== 13) {
            log_message('error', 'Crearxml: linea sin codigo CABYS de 13 digitos. '
                . 'Hacienda rechaza el comprobante: revisar el CABYS del producto.');
        }

        return '<CodigoCABYS>' . $codigo . '</CodigoCABYS>';
    }

    /**
     * Nodo Barrio de una ubicacion.
     *
     * En la v4.4 Barrio dejo de ser un codigo de dos digitos y pasa a ser el
     * nombre, opcional, de 5 a 50 caracteres. Un valor mas corto hace que
     * Hacienda rechace el comprobante entero, asi que se omite.
     */
    private function bloqueBarrio($barrio)
    {
        $barrio = trim((string) $barrio);

        return mb_strlen($barrio) >= 5 ? '<Barrio>' . mb_substr($barrio, 0, 50) . '</Barrio>' : '';
    }

    /**
     * <Ubicacion> de una persona del comprobante.
     *
     * Provincia, Canton, Distrito y OtrasSenas son obligatorios dentro del nodo
     * y OtrasSenas exige de 5 a 250 caracteres (UbicacionType). Sin direccion
     * completa se omite entero antes que armar uno invalido.
     *
     * El tipo de identificacion 05 "Extranjero No Domiciliado" no admite
     * ubicacion en el pais (Anexos v4.4, nodo Ubicacion del receptor): esa
     * direccion viaja en OtrasSenasExtranjero.
     *
     * @param object $persona  fila de customers o de suppliers
     * @param string $tipo     tipo de identificacion ya validado
     * @param string $campo    columna con la direccion exacta
     */
    /**
     * Tipo de identificacion que corresponde al receptor.
     *
     * El anexo fija el largo de cada tipo (nota 4). Si el numero no calza con el
     * tipo declarado, el receptor deja de ser declarable como contribuyente de
     * Costa Rica y baja a 05 "Extranjero No Domiciliado", que es lo unico que
     * acepta un documento de forma libre.
     *
     * @param  object $receptor    fila del cliente o del proveedor
     * @param  string $numero      identificacion ya limpia de guiones
     * @param  bool   $admite_06   solo la factura electronica de compra puede
     *                             emitir "No Contribuyente" (nota 4 pie 17)
     * @return string codigo de dos digitos
     */
    private function tipoDeReceptor($receptor, $numero, $admite_06 = false)
    {
        if (!isset($receptor->pre_id_number)) {
            return '05';
        }

        $numero = trim((string) $numero);
        $largo  = strlen($numero);

        switch ($receptor->pre_id_number) {
            case '01':  return $largo === 9  ? '01' : '05';   // cedula fisica
            case '02':  return $largo === 10 ? '02' : '05';   // cedula juridica
            case '03':  return ($largo === 11 || $largo === 12) ? '03' : '05';   // DIMEX
            case '04':  return $largo === 10 ? '04' : '05';   // NITE
            case '06':  return $admite_06 ? '06' : '05';      // No Contribuyente
        }

        return '05';
    }

    /**
     * Id del cliente de paso, segun el ajuste.
     *
     * Estaba escrito como 1 en cuatro puntos del generador mientras Pos.php
     * usaba el ajuste: cambiar el ajuste los dejaba en desacuerdo.
     */
    private function _cliente_de_paso()
    {
        return (int) ($this->Settings->default_customer ?? 1);
    }

    private function bloqueUbicacion($persona, $tipo, $campo = 'otras_senas')
    {
        if ($tipo === '05') {
            return '';
        }

        $provincia = trim((string) ($persona->codigo_provincia ?? ''));
        $canton    = trim((string) ($persona->codigo_canton    ?? ''));
        $distrito  = trim((string) ($persona->codigo_distrito  ?? ''));
        $senas     = $this->quitatilde(trim((string) ($persona->$campo ?? '')));

        if ($provincia === '' || $canton === '' || $distrito === '' || mb_strlen($senas) < 5) {
            return '';
        }

        return '<Ubicacion>
                <Provincia>' . substr($provincia, -1) . '</Provincia>
                <Canton>' . str_pad(substr($canton, -2), 2, '0', STR_PAD_LEFT) . '</Canton>
                <Distrito>' . str_pad(substr($distrito, -2), 2, '0', STR_PAD_LEFT) . '</Distrito>
                ' . $this->bloqueBarrio($this->nombreBarrio($persona)) . '
                <OtrasSenas>' . mb_substr($senas, 0, 250) . '</OtrasSenas>
            </Ubicacion>';
    }

    /** El cliente guarda el codigo del barrio; la v4.4 pide el nombre. */
    private function nombreBarrio($persona)
    {
        if (empty($persona->codigo_barrio)) {
            return '';
        }

        $fila = $this->db->select('nombre_barrio')
            ->where('codigo_provincia', $persona->codigo_provincia)
            ->where('codigo_canton',    $persona->codigo_canton)
            ->where('codigo_distrito',  $persona->codigo_distrito)
            ->where('codigo_barrio',    $persona->codigo_barrio)
            ->get('barrio_cr', 1)->row();

        return $fila ? $fila->nombre_barrio : '';
    }

    /**
     * Direccion en el extranjero. Solo la admite el tipo de identificacion 05
     * "Extranjero No Domiciliado" y de 5 a 300 caracteres (Anexos v4.4).
     */
    private function bloqueSenasExtranjero($persona, $tipo)
    {
        if ($tipo !== '05') {
            return '';
        }
        $senas = $this->quitatilde(trim((string) ($persona->otras_senas_extranjero ?? '')));

        return mb_strlen($senas) >= 5
            ? '<OtrasSenasExtranjero>' . mb_substr($senas, 0, 300) . '</OtrasSenasExtranjero>'
            : '';
    }

    /**
     * <Telefono>. NumTelefono es un entero de 8 a 20 digitos (TelefonoType):
     * un telefono vacio o mas corto invalida el comprobante entero.
     */
    private function bloqueTelefono($numero, $codigo_pais = '506')
    {
        $numero = preg_replace('/\D/', '', (string) $numero);
        $codigo = preg_replace('/\D/', '', (string) $codigo_pais) ?: '506';

        if (strlen($numero) < 8 || strlen($numero) > 20) {
            return '';
        }

        return '<Telefono>
                <CodigoPais>' . substr($codigo, 0, 3) . '</CodigoPais>
                <NumTelefono>' . $numero . '</NumTelefono>
            </Telefono>';
    }

    /**
     * <InformacionReferencia> de la factura electronica de compra.
     *
     * El nodo es obligatorio en este comprobante (Anexos v4.4, tabla de
     * Informacion de Referencia). El codigo 16 "Comprobante de Proveedor No
     * Domiciliado" es exclusivo de la FEC y acompana al emisor tipo 05; para
     * el resto la compra se respalda con el documento fisico del proveedor,
     * que corresponde al codigo 99.
     *
     * @param array  $invoice fila de la compra
     * @param string $tipo    tipo de identificacion del proveedor
     * @param string $fecha   fecha de emision del comprobante
     */
    private function bloqueReferenciaFec($invoice, $tipo, $fecha)
    {
        $numero = trim((string) ($invoice['reference_no'] ?? ''));
        $numero = $numero !== '' ? mb_substr($this->quitatilde($numero), 0, 50) : 'SIN NUMERO';

        $emision = !empty($invoice['date']) ? date('Y-m-d\TH:i:s', strtotime($invoice['date'])) : $fecha;

        $xml = '<InformacionReferencia><TipoDocIR>' . ($tipo === '05' ? '16' : '99') . '</TipoDocIR>';
        if ($tipo !== '05') {
            $xml .= '<TipoDocRefOTRO>' . ($tipo === '06'
                ? 'Compra de bien usado a no contribuyente'
                : 'Comprobante fisico entregado por el proveedor') . '</TipoDocRefOTRO>';
        }
        $xml .= '<Numero>' . $numero . '</Numero>';
        $xml .= '<FechaEmisionIR>' . $this->fechaIso($emision) . '</FechaEmisionIR>';

        return $xml . '</InformacionReferencia>';
    }

    /**
     * ¿El receptor es un extranjero no domiciliado de verdad?
     *
     * Al codigo 05 se llega por dos caminos que no son lo mismo: el cliente
     * registrado como "Extranjero No Domiciliado", y la cedula de Costa Rica que
     * no calzo en longitud y cayo ahi por descarte. Solo el primero puede ir
     * como receptor; el segundo es un dato malo y el comprobante sale sin
     * receptor.
     *
     * El anexo admite el codigo 05 en el receptor del tiquete, la nota de
     * credito y la nota de debito; en la factura solo con condicion de venta 12
     * y en el REP lo rechaza (Anexos v4.4, nota 4 pie 16).
     *
     * @param object $receptor fila de customers
     */
    private function receptorExtranjero($receptor)
    {
        if (empty($receptor) || ($receptor->cf1 ?? '') !== '05') {
            return false;
        }

        // Su documento admite hasta 20 caracteres alfanumericos, y el nombre del
        // receptor exige al menos 3 (ReceptorType).
        $documento = preg_replace('/[^A-Za-z0-9]/', '', (string) ($receptor->cf2 ?? ''));
        $nombre    = trim((string) ($receptor->name ?? ''));

        return $documento !== '' && strlen($documento) <= 20 && mb_strlen($nombre) >= 3;
    }

    /** NombreComercial es opcional pero exige de 3 a 80 caracteres. */
    private function bloqueNombreComercial($nombre)
    {
        $nombre = $this->quitatilde(trim((string) $nombre));

        return mb_strlen($nombre) >= 3
            ? '<NombreComercial>' . mb_substr($nombre, 0, 80) . '</NombreComercial>'
            : '';
    }

    /** El XSD valida el correo contra un patron: uno mal formado rechaza todo. */
    private function bloqueCorreo($email)
    {
        $email = trim((string) $email);

        return ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($email) <= 160)
            ? '<CorreoElectronico>' . $email . '</CorreoElectronico>'
            : '';
    }

    /** Porcentaje que fija el catalogo v4.4 para cada codigo de tarifa de IVA. */
    private const TARIFA_POR_CODIGO = [
        '01' => 0.0, '02' => 1.0, '03' => 2.0, '04' => 4.0, '05' => 0.0, '06' => 4.0,
        '07' => 8.0, '08' => 13.0, '09' => 0.5, '10' => 0.0, '11' => 0.0,
    ];

    /**
     * Codigo de tarifa que corresponde al porcentaje realmente cobrado.
     *
     * Hacienda valida que ambos concuerden ("para el codigo de tarifa '08' la
     * tarifa debe ser 13"). Si el impuesto configurado en el producto y lo que
     * quedo en la linea no coinciden, manda lo cobrado y se corrige el codigo.
     *
     * @param float  $porcentaje tarifa efectiva de la linea
     * @param string $configurado codigo del impuesto asignado al producto
     */
    private function codigoTarifa($porcentaje, $configurado)
    {
        $codigo = str_pad((string) $configurado, 2, '0', STR_PAD_LEFT);
        $tarifa = round((float) $porcentaje, 2);

        if (isset(self::TARIFA_POR_CODIGO[$codigo]) && self::TARIFA_POR_CODIGO[$codigo] === $tarifa) {
            return $codigo;
        }

        // Con 0% hay varios codigos posibles; se conserva el configurado si ya es uno de ellos.
        if ($tarifa === 0.0) {
            return in_array($codigo, array('01', '05', '10', '11'), true) ? $codigo : '01';
        }

        $equivalente = array_search($tarifa, self::TARIFA_POR_CODIGO, true);

        return $equivalente !== false ? $equivalente : $codigo;
    }

    /**
     * Bloque <Descuento> de una linea.
     *
     * La v4.4 exige CodigoDescuento despues del monto y antes de la naturaleza:
     * sin el, el comprobante no valida contra el esquema. Se declara 07,
     * "Descuento Comercial", que es el que corresponde a una rebaja de mostrador.
     * NaturalezaDescuento pide al menos 3 caracteres.
     *
     * @param float  $monto      monto descontado en la linea
     * @param string $naturaleza motivo, normalmente el nombre del producto
     */
    private function bloqueDescuento($monto, $naturaleza)
    {
        $texto = trim($this->quitatilde((string) $naturaleza));
        if (mb_strlen($texto) < 3) {
            $texto = 'Descuento comercial';
        }

        return '<Descuento>'
             . '<MontoDescuento>' . number_format((float) ($monto), 5, '.', '') . '</MontoDescuento>'
             . '<CodigoDescuento>07</CodigoDescuento>'
             . '<NaturalezaDescuento>' . mb_substr($texto, 0, 80) . '</NaturalezaDescuento>'
             . '</Descuento>';
    }

    /**
     * Naturaleza de una linea segun su codigo de tarifa de IVA.
     *
     * El resumen de la v4.4 separa cuatro naturalezas y Hacienda las valida por
     * separado: declarar como exenta una linea no sujeta deja vacio
     * TotalMercNoSujeta y el comprobante se rechaza.
     *
     * Tarifas (Anexos v4.4): 01 es 0% del articulo 32 del RLIVA, 05 transitorio
     * 0% y 11 0% sin derecho a credito, todas no sujetas; 10 es la exenta; el
     * resto grava.
     *
     * @return string 'gravado' | 'exento' | 'nosujeto'
     */
    private function naturalezaLinea($tarifaCodigo)
    {
        $codigo = str_pad((string) $tarifaCodigo, 2, '0', STR_PAD_LEFT);

        if (in_array($codigo, array('01', '05', '11'), true)) {
            return 'nosujeto';
        }
        if ($codigo === '10') {
            return 'exento';
        }

        return 'gravado';
    }

    /**
     * Si la linea es un servicio, para repartir los totales del resumen.
     *
     * Hacienda no mira el tipo de producto del POS: clasifica por el CABYS, que
     * sigue las secciones de la CPC — 0 a 4 son bienes y 5 a 9 servicios. Declarar
     * una mercancia como servicio descuadra TotalMercanciasGravadas y el
     * comprobante se rechaza con el error -111.
     *
     * @param string $cabys codigo CABYS de 13 digitos de la linea
     * @param string $tipo  tipo del producto en el POS, respaldo si no hay CABYS
     */
    private function esServicio($cabys, $tipo = '')
    {
        $codigo = preg_replace('/\D/', '', (string) $cabys);

        if (strlen($codigo) === 13) {
            return (int) $codigo[0] >= 5;
        }

        return $tipo === 'service';
    }

    /**
     * Unidad de medida de una linea.
     *
     * El XSD la limita a un catalogo cerrado y una linea sin unidad sale vacia:
     * Hacienda rechaza el comprobante entero por enumeracion invalida.
     */
    private function unidadMedida($unidad)
    {
        $unidad = trim((string) $unidad);

        return $unidad !== '' ? $unidad : 'Unid';
    }

    /**
     * Desglose de impuestos del resumen, agrupado por codigo y tarifa.
     *
     * Hacienda rechaza con -487 ("posee detalle de Impuesto pero carece del campo
     * Total Desglose Impuestos") cuando las lineas declaran impuesto y el resumen
     * no lo desglosa, aunque el XSD lo marque opcional.
     *
     * @param array $acumulado 'codigo|tarifa' => monto sumado en las lineas
     */
    private function bloqueDesgloseImpuesto(array $acumulado)
    {
        $xml = '';

        foreach ($acumulado as $llave => $monto) {
            list($codigo, $tarifa) = explode('|', $llave);
            $xml .= '<TotalDesgloseImpuesto>'
                  . '<Codigo>' . $codigo . '</Codigo>'
                  . ($tarifa !== '' ? '<CodigoTarifaIVA>' . $tarifa . '</CodigoTarifaIVA>' : '')
                  . '<TotalMontoImpuesto>' . number_format((float) ($monto), 5, '.', '') . '</TotalMontoImpuesto>'
                  . '</TotalDesgloseImpuesto>';
        }

        return $xml;
    }

    /**
     * Tipo de cambio de la moneda del comprobante respecto al colon.
     *
     * Obligatorio en el resumen y de tipo decimal: un valor vacio invalida el
     * comprobante contra el XSD. Facturando en colones siempre es 1.
     */
    private function tipoCambio()
    {
        $valor = (float) ($this->Settings->value_changue ?? 0);

        if ($valor <= 0) {
            $valor = 1;
        }

        return number_format((float) ($valor), 5, '.', '');
    }

    /**
     * Codigos de actividad del encabezado.
     *
     * La v4.4 renombro CodigoActividad a CodigoActividadEmisor y subio el del
     * receptor al encabezado como CodigoActividadReceptor. Cual admite cada
     * comprobante cambia segun el tipo: el tiquete no lleva el del receptor y
     * el recibo de pago no lleva ninguno de los dos (ver los XSD oficiales en
     * files/docs-hacienda/xsd/v4.4).
     *
     * @param string $emisor   codigo del emisor; vacio lo omite
     * @param string $receptor codigo del receptor; vacio lo omite
     */
    private function bloqueCodigoActividad($emisor, $receptor = '')
    {
        $xml = '';

        if (trim((string) $emisor) !== '') {
            $xml .= '<CodigoActividadEmisor>' . trim((string) $emisor) . '</CodigoActividadEmisor>';
        }
        if (trim((string) $receptor) !== '') {
            $xml .= '<CodigoActividadReceptor>' . trim((string) $receptor) . '</CodigoActividadReceptor>';
        }

        return $xml;
    }

    /**
     * Normaliza $payment a lista de pagos.
     *
     * @param mixed $payment Un pago asociativo o una lista de pagos.
     */
    private function listaDePagos($payment)
    {
        if (empty($payment)) {
            return array();
        }
        // Un arreglo asociativo suelto tiene 'paid_by'/'amount' en la raiz.
        if (isset($payment['paid_by']) || isset($payment['amount'])) {
            return array($payment);
        }
        $out = array();
        foreach ($payment as $p) {
            if (is_array($p) && (isset($p['paid_by']) || isset($p['amount']))) {
                $out[] = $p;
            }
        }
        return $out;
    }

    /**
     * Arma el bloque <MedioPago>, ComplexType repetible {1,4} en v4.4.
     *
     * La suma de los TotalMedioPago debe cuadrar con TotalComprobante o Hacienda
     * rechaza el comprobante, por lo que los montos se reparten contra el total
     * de la factura y no contra lo entregado: el excedente es vuelto.
     *
     * @param mixed $payment          Un pago o una lista de pagos.
     * @param float $totalComprobante Total ya calculado del comprobante.
     */
    private function bloqueMedioPago($payment, $totalComprobante)
    {
        $pagos = $this->listaDePagos($payment);
        $total = round((float) $totalComprobante, 5);

        // El anexo exige declarar al menos un medio de pago.
        if (empty($pagos)) {
            return '<MedioPago><TipoMedioPago>99</TipoMedioPago>'
                 . '<MedioPagoOtros>No especificado</MedioPagoOtros>'
                 . '<TotalMedioPago>' . number_format((float) ($total), 5, '.', '') . '</TotalMedioPago>'
                 . '</MedioPago>';
        }

        $pagos = array_slice($pagos, 0, 4);   // tope del anexo

        $restante = $total;
        $lineas   = array();
        foreach ($pagos as $i => $pg) {
            $monto = round((float) (isset($pg['amount']) ? $pg['amount'] : 0), 5);
            if ($monto <= 0 && count($pagos) > 1) {
                continue;
            }
            // La ultima linea absorbe el remanente para que la suma cuadre exacto.
            $esUltima = ($i === count($pagos) - 1);
            $asignado = $esUltima ? $restante : min($monto, $restante);
            if ($asignado < 0) { $asignado = 0; }
            $restante = round($restante - $asignado, 5);

            $lineas[] = array(
                'tipo'  => $this->tipoMedioPago(isset($pg['paid_by']) ? $pg['paid_by'] : ''),
                'monto' => $asignado,
                'otros' => isset($pg['note']) ? trim((string) $pg['note']) : '',
            );
            if ($restante <= 0) { break; }
        }

        if (empty($lineas)) {
            $lineas[] = array('tipo' => '99', 'monto' => $total, 'otros' => '');
        }

        $varios = count($lineas) > 1;
        $xml = '';
        foreach ($lineas as $l) {
            $xml .= '<MedioPago>';
            $xml .= '<TipoMedioPago>' . $l['tipo'] . '</TipoMedioPago>';
            if ($l['tipo'] === '99') {
                // Obligatorio con el codigo 99: entre 3 y 100 caracteres.
                $desc = $l['otros'] !== '' ? $l['otros'] : 'Otro medio de pago';
                $desc = $this->quitatilde(mb_substr($desc, 0, 100));
                if (mb_strlen($desc) < 3) { $desc = 'Otro medio de pago'; }
                $xml .= '<MedioPagoOtros>' . $desc . '</MedioPagoOtros>';
            }
            // Opcional con un solo medio, obligatorio en cuanto hay dos.
            if ($varios || $l['monto'] > 0) {
                $xml .= '<TotalMedioPago>' . number_format((float) ($l['monto']), 5, '.', '') . '</TotalMedioPago>';
            }
            $xml .= '</MedioPago>';
        }
        return $xml;
    }

    public function getInvoice($invoice, $itemsInvoices, $payment, $otrostextos)
    {
        ini_set("memory_limit", "8162M");
        ini_set( 'max_input_vars' , 16000 );
        $this->load->model('customers_model');
        $this->load->model('hacienda_model');
        $sale_id = $invoice['id'];
        $sale_items = $this->db->get_where('sale_items', array('sale_id' => $sale_id))->result();
        $totalItems = count($sale_items);
        $customer_id = $invoice['customer_id'];
        $receptor = $this->customers_model->getCustomerByID($invoice['customer_id']);
        $CodActividad = $invoice['id_actividad'];

        $moneda = $this->Settings->currency_prefix;
        $CondicionVenta = "";
        $PlazoCredito = "";

        if ($invoice['status'] == 'paid' || $invoice['id_shipping_method'] != NULL) {
            $CondicionVenta = '01';
        } else {
            $CondicionVenta = '02';
            // El plazo sale del cliente; 30 dias es lo que se declara cuando el
            // cliente no lo tiene definido.
            $PlazoCredito = plazo_credito_dias($receptor ?? null, 30) . ' dias';
        }
        $tipo_receptor = '05';
        $identificacion = '';

        if ($customer_id != $this->_cliente_de_paso()) {
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
        }

        // El cliente de paso no pasa por el bloque de arriba: sin esto el switch
        // leeria una variable indefinida.
        if (!isset($identifivalid)) {
            $identifivalid = '';
        }

        $tipo_receptor = $this->tipoDeReceptor($receptor, $identifivalid, false);
        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();
        $fecha = date('Y-m-d\TH:i:s');
        $cabeceraticket = '<?xml version="1.0" encoding="UTF-8"?><TiqueteElectronico xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/tiqueteElectronico" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/tiqueteElectronico https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/TiqueteElectronico_V4.4.xsd">';

        $cabecerafactura = '<?xml version="1.0" encoding="UTF-8"?><FacturaElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/FacturaElectronica_V4.4.xsd">';
        $extranjero = $this->receptorExtranjero($receptor);
        // Quien es el cliente de paso lo dice el ajuste, no su nombre: un cliente
        // real llamado "Cliente de contado" salia sin identificar en el
        // comprobante. Y sin cedula no hay factura posible, porque el esquema
        // exige la identificacion del receptor.
        $anonimo = ($customer_id == $this->_cliente_de_paso())
            || trim((string) ($receptor->cf2 ?? '')) === '';

        // El codigo 05 en el receptor de una factura exige condicion de venta 12
        // "Venta Mercancia No Nacionalizada", que el POS no maneja: el extranjero
        // se factura como tiquete, donde el anexo si lo admite y el receptor es
        // opcional (Anexos v4.4, nota 4 pie 16).
        if ($anonimo || $tipo_receptor == '05') {
            $invo = $cabeceraticket;
            $sireceptor = !$anonimo && $extranjero;
            $tipodoc = '04';
        } else {
            $invo = $cabecerafactura;
            $sireceptor = true;
            $tipodoc = '01';
        }


        $consecutivo = $this->hacienda_model->ccsctv($tipodoc);
        $NumConse = $this->hacienda_model->ultimo_consecutivo($tipodoc, $consecutivo);

        $consecutive = $this->generate_consecutive($NumConse + 1, $tipodoc);

        // La clave debe llevar la misma fecha que <FechaEmision> o Hacienda
        // rechaza el comprobante con el error -405.
        $param = [$consecutive, $fecha, isset($invoice['situacion']) ? $invoice['situacion'] : '1'];
        $key = $this->generate_key($param);


        $invo .= '
                  <Clave>' . $key . '</Clave>
                  <ProveedorSistemas>' . $this->proveedorSistemas() . '</ProveedorSistemas>
                  ' . $this->bloqueCodigoActividad($CodActividad, ($sireceptor && $tipodoc == '01') ? ($receptor->codigo_actividad ?? '') : '') . '
                  <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
                  <FechaEmision>' . $fecha . '</FechaEmision>
                  <Emisor>
                    <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
                    <Identificacion>
                      <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                      <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor)) . '</Numero>
                    </Identificacion>
                    <NombreComercial>' . $this->quitatilde(trim($this->Settings->nombre_comercial)) . '</NombreComercial>
                    <Ubicacion>
                      <Provincia>' . substr($this->Settings->cod_provincia, -2) . '</Provincia>
                      <Canton>' . substr(trim($this->Settings->cod_canton), -2) . '</Canton>
                      <Distrito>' . substr($this->Settings->cod_distrito, -2) . '</Distrito>
                      ' . $this->bloqueBarrio($this->Settings->cod_barrio) . '
                      <OtrasSenas>' . trim($this->Settings->otras_senas) . '</OtrasSenas>
                    </Ubicacion>
                    <Telefono>
                      <CodigoPais>' . trim($this->Settings->cod_telefono_emisor) . '</CodigoPais>
                      <NumTelefono>' . trim(str_replace("-", "", $this->Settings->telefono_emisor)) . '</NumTelefono>
                    </Telefono>
                    <CorreoElectronico>' . trim($this->Settings->email_emisor) . '</CorreoElectronico>
                  </Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . trim($this->quitatilde($receptor->name)) . '</Nombre>
            <Identificacion>
                    <Tipo>' . $tipo_receptor . '</Tipo>
                    <Numero>' . $receptor->id_number_proveedor . '</Numero>
            </Identificacion>';

            if ($receptor->business_name) {
                $invo .= '<NombreComercial>' . $this->quitatilde($receptor->business_name) . '</NombreComercial>';
            }

            $invo .= $this->bloqueUbicacion($receptor, $tipo_receptor) . $this->bloqueSenasExtranjero($receptor, $tipo_receptor);

            if (strlen(trim(str_replace("-", "", $receptor->phone))) == 8) {
                $invo .= '
                <Telefono>
                    <CodigoPais>' . (preg_replace('/\D/', '', (string) ($receptor->cod_telefono ?? '')) ?: '506') . '</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $receptor->phone)) . '</NumTelefono>
                </Telefono>';
            }

            if ($receptor->email) {
                $invo .= '<CorreoElectronico>' . $receptor->email . '</CorreoElectronico>';
            }

            $invo .= '</Receptor>';
        }


        $invo .= '<CondicionVenta>' . $CondicionVenta . '</CondicionVenta>';

        if ($PlazoCredito) {
            $invo .= '<PlazoCredito>' . $PlazoCredito . '</PlazoCredito>';
        }

        $NumeroLinea = 0;
        $TotalServGravados = 0.00000;
        $TotalServExentos = 0.00000;
        $TotalServExonerado = 0.00000;
        $TotalServNoSujeto = 0.00000;
        $TotalMercanciasGravadas = 0.00000;
        $TotalMercanciasExentas = 0.00000;
        $TotalMercanciaExonerada = 0.00000;
        $TotalMercNoSujeta = 0.00000;
        $TotalGravado = 0.00000;
        $TotalExento = 0.00000;
        $TotalExonerado = 0.00000;
        $TotalVenta = 0.00000;
        $TotalDescuentos = 0.00000;
        $TotalVentaNeta = 0.00000;
        $TotalImpuesto = 0.00000;
        $TotalComprobante = 0.00000;
        $disAux = 0.00000;
        $is_service = false;
        $MontoCargo = 0.00000;
        // dd($invoice);

        $itemsInvoices = $this->reClacDiscount($itemsInvoices, $invoice);
        $count = 0;
        
        $itemlength = count($itemsInvoices);
        
        $invo .= '<DetalleServicio>';
        if ($itemsInvoices == null || count($itemsInvoices) == 0) {
            return null;
        }
        if ($totalItems != count($itemsInvoices)) {
            return null;
        }
        $i = 0;
        // Impuesto acumulado por codigo y tarifa, para el desglose del resumen.
        $desgloseImp = array();
        foreach ($itemsInvoices as $items) {
            if ($items["product_id"] != "9r091n4" && $items["product_code"] != "9r091n4" ) {
                if (strpos($items["discount"], '%') === false) {
                    $items["item_discount"] = $items["discount"];
                };

                $NumeroLinea = $NumeroLinea + 1;
                $CodigoTipo = "03";
                $CodigoCodigo = $items["product_code"];
                $Cantidad = $items['quantity'];

                if ($items['item_tax']) {
                    $ImpuestoTarifa = (int) str_replace('%', '', $items["tax"]);
                    $calc_imp = $ImpuestoTarifa / 100;
                } else {
                    $calc_imp = 1;
                }

                $Detalle = $items["product_name"];

                $row = $this->site->getProductByID($items['product_id']);
                $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
                // Para productos ad-hoc (sin product_id): usar id_tax guardado en la línea de venta
                $id_tax_lookup = isset($row->id_tax) ? $row->id_tax : (isset($items['id_tax']) && $items['id_tax'] > 0 ? $items['id_tax'] : 8);
                $qq = $this->db->get_where('impuestos', array('id_impuesto' => $id_tax_lookup), 1);
                if ($qq->num_rows() > 0) {
                    $im = $qq->row();
                    $items['id_impuesto'] = $im->id_impuesto;
                    $items['codigo_impuesto'] = $im->codigo_impuesto;
                    $items['codigo_tarifa'] = $im->codigo_tarifa;
                } else {
                    $items['id_impuesto'] = 0;
                    $items['codigo_impuesto'] = 0;
                    $items['codigo_tarifa'] = 0;
                }


                if ($row) {
                    if ($row->tax_method == "1") {
                        $PrecioUnitario = $items["real_unit_price"];
                        $InvertirImpuestoTarifa = 1;
                    } else if ($row->tax_method == "0") {
                        if (!$items['item_tax']) {
                            $PrecioUnitario = $items["real_unit_price"];
                        } else {
                            $InvertirImpuestoTarifa = 1 + (floatval(str_replace('%', '', $items["tax"])) / 100);
                            $PrecioUnitario = number_format((float) ($items["real_unit_price"]), 5, '.', '') / number_format((float) ($InvertirImpuestoTarifa), 5, '.', '');
                        }
                    }
                } else {
                    $PrecioUnitario = $items["real_unit_price"];
                    $InvertirImpuestoTarifa = 1;
                }

                $MontoTotal = number_format((float) ($items["quantity"]), 5, '.', '') * number_format((float) ($PrecioUnitario), 5, '.', '');

                $NaturalezaDescuento = null;
                if ($items["item_discount"] > 0) {
                    $NaturalezaDescuento = $items["product_name"];
                }

                $MontoDescuento = 0;

                if ($items["discount"] > 0) {
                    $disAux = str_replace('%', '', $items["discount"]);
                    $MontoDescuento = number_format((float) ($MontoTotal), 5, '.', '') * (number_format((float) ($disAux), 5, '.', '') / 100);
                }

                $SubTotal = number_format((float) ($MontoTotal), 5, '.', '') - number_format((float) ($MontoDescuento), 5, '.', '');

                $ImpuestoCodigo = $items["codigo_impuesto"];
                $ImpuestoTarifa = (float) str_replace('%', '', (string) ($items["tax"] ?? 0));
                $TarifaCodigo = $this->codigoTarifa($ImpuestoTarifa, $items["codigo_tarifa"]);
                //$ImpuestoMonto =  $items["item_tax"];
                $ImpuestoMonto = number_format((float) ($SubTotal), 5, '.', '') * (number_format((float) ($ImpuestoTarifa), 5, '.', '') / 100);

                $invo .= '<LineaDetalle>
                            <NumeroLinea>' . $NumeroLinea . '</NumeroLinea>
                            ' . $this->bloqueCodigoCabys(is_object($row) ? ($row->cabys ?? '') : '', $items['cabys'] ?? '', $items['product_code'] ?? '') . '
                            <CodigoComercial>
                            <Tipo>' . $CodigoTipo . '</Tipo>
                            <Codigo>' . substr($CodigoCodigo, 0, 19) . '</Codigo>
                            </CodigoComercial>'
                            . '<Cantidad>' . $Cantidad . '</Cantidad>
                            <UnidadMedida>' . $this->unidadMedida($items["unit_of_measurement"] ?? '') . '</UnidadMedida>
                            <Detalle>' . substr($this->quitatilde($Detalle), 0, 80) . '</Detalle>
                            <PrecioUnitario>' . number_format((float) ($PrecioUnitario), 5, '.', '') . '</PrecioUnitario>
                            <MontoTotal>' . number_format((float) ($MontoTotal), 5, '.', '') . '</MontoTotal>';

                if ($MontoDescuento > 0) {
                    $invo .= $this->bloqueDescuento($MontoDescuento, $NaturalezaDescuento);
                }

                $invo .= '<SubTotal>' . number_format((float) ($SubTotal), 5, '.', '') . '</SubTotal>';
                $invo .= '<BaseImponible>' . number_format((float) ($SubTotal), 5, '.', '') . '</BaseImponible>';
                $MontoExoneracion = 0.00000;
                $PorcentajeExoneracion = 0;
                $ImpuestoNeto = 0.00000;

                // Impuesto es obligatorio en cada linea desde la v4.4: una linea
                // exenta lo declara con tarifa 0% (codigo 01) y monto en cero.
                $exenta = ($TarifaCodigo == '01');
                $invo .= '<Impuesto>
                            <Codigo>' . ($exenta ? '01' : $ImpuestoCodigo) . '</Codigo>
                            <CodigoTarifaIVA>' . ($exenta ? '01' : $TarifaCodigo) . '</CodigoTarifaIVA>
                            <Tarifa>' . ($exenta ? '0.00000' : $ImpuestoTarifa) . '</Tarifa>
                            <Monto>' . number_format((float) ($exenta ? 0 : $ImpuestoMonto), 5, '.', '') . '</Monto>';

                if (!$exenta) {
                    if ($invoice['TipoDocumentoE'] && $customer_id != $this->_cliente_de_paso() && $tipo_receptor != '05') {
                        $PorcentajeExoneracion = $invoice['PorcentajeExoneracion'];
                        $MontoExoneracion = number_format((float) ($ImpuestoMonto), 5, '.', '') * (number_format((float) ($PorcentajeExoneracion), 5, '.', '') / 100);
                        $invo .= '
                    <Exoneracion>
                      <TipoDocumentoEX1>' . $invoice['TipoDocumentoE'] . '</TipoDocumentoEX1>
                      <NumeroDocumento>' . $invoice['NumeroDocumentoE'] . '</NumeroDocumento>
                      <NombreInstitucion>' . $this->quitatilde($invoice['NombreInstitucionE']) . '</NombreInstitucion>
                      <FechaEmisionEX>' . $invoice['FechaEmisionE'] . '</FechaEmisionEX>
                      <TarifaExonerada>' . $invoice['PorcentajeExoneracion'] . '</TarifaExonerada>
                      <MontoExoneracion>' . number_format((float) ($MontoExoneracion), 5, '.', '') . '</MontoExoneracion>
                    </Exoneracion>';
                    }

                    $ImpuestoNeto = number_format((float) ($ImpuestoMonto), 5, '.', '') - number_format((float) ($MontoExoneracion), 5, '.', '');
                }

                $invo .= '</Impuesto>';
                $llaveImp = ($exenta ? '01' : $ImpuestoCodigo) . '|' . ($exenta ? '01' : $TarifaCodigo);
                $desgloseImp[$llaveImp] = ($desgloseImp[$llaveImp] ?? 0) + ($exenta ? 0 : (float) $ImpuestoMonto);


                // Los dos son obligatorios en la v4.4 aunque la linea no lleve impuesto.
                $invo .= '<ImpuestoAsumidoEmisorFabrica>0.00000</ImpuestoAsumidoEmisorFabrica>';
                $invo .= '<ImpuestoNeto>' . number_format((float) ($ImpuestoNeto), 5, '.', '') . '</ImpuestoNeto>';

                $MontoTotalLinea = number_format((float) ($SubTotal), 5, '.', '') + number_format((float) ($ImpuestoNeto), 5, '.', '');

                $invo .= '<MontoTotalLinea>' . number_format((float) ($MontoTotalLinea), 5, '.', '') . '</MontoTotalLinea>
                    </LineaDetalle>';
                $natural = $this->naturalezaLinea($TarifaCodigo);
                $exonerado = number_format((float) ($MontoTotal), 5, '.', '') * (number_format((float) ($PorcentajeExoneracion), 5, '.', '') / 100);
                $gravado = number_format((float) ($MontoTotal), 5, '.', '') - $exonerado;

                if ($this->esServicio($this->cabysDeLinea($row, $items), $items['type'] ?? '')) {
                    if ($natural === 'nosujeto') {
                        $TotalServNoSujeto += number_format((float) ($MontoTotal), 5, '.', '');
                    } elseif ($natural === 'exento') {
                        $TotalServExentos = number_format((float) ($TotalServExentos), 5, '.', '') + number_format((float) ($MontoTotal), 5, '.', '');
                    } else {
                        $TotalServGravados = number_format((float) ($TotalServGravados), 5, '.', '') + $gravado;
                        $TotalServExonerado = number_format((float) ($TotalServExonerado), 5, '.', '') + $exonerado;
                    }
                } else {
                    if ($natural === 'nosujeto') {
                        $TotalMercNoSujeta += number_format((float) ($MontoTotal), 5, '.', '');
                    } elseif ($natural === 'exento') {
                        $TotalMercanciasExentas = number_format((float) ($TotalMercanciasExentas), 5, '.', '') + number_format((float) ($MontoTotal), 5, '.', '');
                    } else {
                        $TotalMercanciasGravadas = number_format((float) ($TotalMercanciasGravadas), 5, '.', '') + $gravado;
                        $TotalMercanciaExonerada = number_format((float) ($TotalMercanciaExonerada), 5, '.', '') + $exonerado;
                    }
                }

                $TotalDescuentos = number_format((float) ($TotalDescuentos), 5, '.', '')  + number_format((float) ($MontoDescuento), 5, '.', '');
                $TotalImpuesto = number_format((float) ($TotalImpuesto), 5, '.', '') + number_format((float) ($ImpuestoNeto), 5, '.', '');
                $i = $i + 1;
            } else {
                // --------------------------Otros Cargos------------------------------------------
                $TipoDocumentoOtros = '06';
                $NumeroIdentidadTercero = trim(str_replace("-", "", $this->Settings->cedula_emisor));
                $NombreTercero = trim($receptor->name);
                $DetalleOtros = $items["product_name"];
                $Porcentaje = 10;
                $MontoCargo = $items["net_unit_price"];
                $is_service = true;
                //---------------------------------------------------------------------------------
                $i = $i + 1;
            }
        }
        if ($totalItems != $i) {
            // Devolver null deja la venta sin comprobante y sin rastro: el aviso
            // es lo unico que delata el descuadre entre total_items y las lineas.
            log_message('error', 'Crearxml: la venta declara ' . $totalItems
                . ' articulos pero se procesaron ' . $i . '. No se genera el comprobante.');
            return null;
        }
        $invo .= '</DetalleServicio>';
        // --------------------------Otros Cargos------------------------------------------
        if ($is_service) {
            $invo .= '<OtrosCargos>';
            $invo .= '<TipoDocumento>' . $TipoDocumentoOtros . '</TipoDocumento>';
            $invo .= '<NumeroIdentidadTercero>' . $NumeroIdentidadTercero . '</NumeroIdentidadTercero>';
            $invo .= '<NombreTercero>' . $this->quitatilde($NombreTercero) . '</NombreTercero>';
            $invo .= '<Detalle>' . $this->quitatilde($DetalleOtros) . '</Detalle>';
            $invo .= '<Porcentaje>' . number_format((float) ($Porcentaje), 5, '.', '') . '</Porcentaje>';
            $invo .= '<MontoCargo>' . number_format((float) ($MontoCargo), 5, '.', '') . '</MontoCargo>';
            $invo .= '</OtrosCargos>';
        }
        //---------------------------------------------------------------------------------
        $TotalExento = number_format((float) ($TotalServExentos), 5, '.', '') + number_format((float) ($TotalMercanciasExentas), 5, '.', '');
        $TotalGravado = number_format((float) ($TotalMercanciasGravadas), 5, '.', '') + number_format((float) ($TotalServGravados), 5, '.', '');
        $TotalExonerado = number_format((float) ($TotalServExonerado), 5, '.', '') + number_format((float) ($TotalMercanciaExonerada), 5, '.', '');
        $TotalNoSujeto = number_format((float) ($TotalServNoSujeto), 5, '.', '') + number_format((float) ($TotalMercNoSujeta), 5, '.', '');
        $TotalVenta = number_format((float) ($TotalGravado), 5, '.', '') + number_format((float) ($TotalExento), 5, '.', '') + number_format((float) ($TotalExonerado), 5, '.', '') + number_format((float) ($TotalNoSujeto), 5, '.', '');
        $TotalVentaNeta = number_format((float) ($TotalVenta), 5, '.', '') - number_format((float) ($TotalDescuentos), 5, '.', '');
        $TotalComprobante = number_format((float) ($TotalVentaNeta), 5, '.', '') + number_format((float) ($TotalImpuesto), 5, '.', '') + number_format((float) ($MontoCargo), 5, '.', '');
        $invo .= '
                  <ResumenFactura>
                  <CodigoTipoMoneda>
                    <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
                    <TipoCambio>' . $this->tipoCambio() . '</TipoCambio>
                  </CodigoTipoMoneda>';

        $invo .= '<TotalServGravados>' . number_format((float) ($TotalServGravados), 5, '.', '') . '</TotalServGravados>
        <TotalServExentos>' . number_format((float) ($TotalServExentos), 5, '.', '') . '</TotalServExentos>'
            . '';

        if ($TotalServExonerado > 0) {
            $invo .= '<TotalServExonerado>' . number_format((float) ($TotalServExonerado), 5, '.', '') . '</TotalServExonerado>';
        }
        if ($TotalServNoSujeto > 0) {
            $invo .= '<TotalServNoSujeto>' . number_format((float) ($TotalServNoSujeto), 5, '.', '') . '</TotalServNoSujeto>';
        }

        $invo .= '<TotalMercanciasGravadas>' . number_format((float) ($TotalMercanciasGravadas), 5, '.', '') . '</TotalMercanciasGravadas>
                     <TotalMercanciasExentas>' . number_format((float) ($TotalMercanciasExentas), 5, '.', '') . '</TotalMercanciasExentas>';

        if ($TotalMercanciaExonerada > 0) {
            $invo .= '<TotalMercExonerada>' . number_format((float) ($TotalMercanciaExonerada), 5, '.', '') . '</TotalMercExonerada>';
        }
        if ($TotalMercNoSujeta > 0) {
            $invo .= '<TotalMercNoSujeta>' . number_format((float) ($TotalMercNoSujeta), 5, '.', '') . '</TotalMercNoSujeta>';
        }

        $invo .= '<TotalGravado>' . number_format((float) ($TotalGravado), 5, '.', '') . '</TotalGravado>
                    <TotalExento>' . number_format((float) ($TotalExento), 5, '.', '') . '</TotalExento>';
        if ($TotalMercanciaExonerada > 0 || $TotalServExonerado > 0) {
            $invo .= '<TotalExonerado>' . number_format((float) ($TotalExonerado), 5, '.', '') . '</TotalExonerado>';
        }
        if ($TotalNoSujeto > 0) {
            $invo .= '<TotalNoSujeto>' . number_format((float) ($TotalNoSujeto), 5, '.', '') . '</TotalNoSujeto>';
        }
        $invo .= '<TotalVenta>' . number_format((float) ($TotalVenta), 5, '.', '') . '</TotalVenta>
                    <TotalDescuentos>' . number_format((float) ($TotalDescuentos), 5, '.', '') . '</TotalDescuentos>
                    <TotalVentaNeta>' . number_format((float) ($TotalVentaNeta), 5, '.', '') . '</TotalVentaNeta>
                    ' . $this->bloqueDesgloseImpuesto($desgloseImp) . '<TotalImpuesto>' . number_format((float) ($TotalImpuesto), 5, '.', '') . '</TotalImpuesto>';
        if ($is_service) {
            $invo .=    '<TotalOtrosCargos>' . number_format((float) ($MontoCargo), 5, '.', '') . '</TotalOtrosCargos>';
        }
        $invo .=    '<!--NX_MEDIOPAGO--><TotalComprobante>' . number_format((float) ($TotalComprobante), 5, '.', '') . '</TotalComprobante>
                  </ResumenFactura>';
        if ($otrostextos) {
            $invo .= '<Otros>';
            foreach ($otrostextos as $texto) {
                $texto = (array) $texto;
                $invo .= '<OtroTexto codigo="' . $texto['titulo_texto'] . '" >' . $this->quitatilde($texto['otrotexto']) . '</OtroTexto>';
            }
            $invo .= '</Otros>';
        }

        $invo .= ($tipodoc == '04') ? '</TiqueteElectronico>' : '</FacturaElectronica>';

        // <MedioPago> va antes del detalle pero depende de TotalComprobante:
        // se emitio como marcador y se resuelve aca.
        $invo = str_replace('<!--NX_MEDIOPAGO-->', $this->bloqueMedioPago(isset($payment) ? $payment : (isset($pagoNota) ? $pagoNota : null), $TotalComprobante), $invo);
        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha, 'tipo_doc' => $tipodoc];
    }

    public function getFEC($invoice, $itemsInvoices, $payment, $otrostextos)
    {
        ini_set("memory_limit", "-1");
        $this->load->model('Suppliers_model');
        $this->load->model('hacienda_model');
        $customer_id = $invoice['customer_id'];
        $receptor = $this->Suppliers_model->getSupplierByID($invoice['customer_id']);
        $CodActividad = $invoice['id_actividad'];

        $moneda = $this->Settings->currency_prefix;
        $CondicionVenta = "";
        $PlazoCredito = "";

        if ($invoice['status'] == 'paid') {
            $CondicionVenta = '01';
        } else {
            $CondicionVenta = '02';
            $PlazoCredito = $invoice['paymentmethod'] . ' dias';
        }
        $tipo_receptor = '05';
        $identificacion = '';

        if ($customer_id != $this->_cliente_de_paso()) {
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
        }

        $tipo_receptor = $this->tipoDeReceptor($receptor, $identifivalid, true);
        date_default_timezone_set('America/Costa_Rica');
        date_default_timezone_get();
        $fecha = date('Y-m-d\TH:i:s');

        $cabecerafactura = '<?xml version="1.0" encoding="UTF-8"?><FacturaElectronicaCompra xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronicaCompra" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronicaCompra https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/FacturaElectronicaCompra_V4.4.xsd">';
        // <Receptor> no es opcional en FacturaElectronicaCompra_V4.4.xsd y en este
        // comprobante siempre es el obligado tributario que registra la compra.
        $invo = $cabecerafactura;
        $sireceptor = true;
        $tipodoc = '08';

        // El "No Contribuyente" solo existe para respaldar la compra de bienes
        // usados, y esa es la unica condicion de venta que la admite
        // (Anexos v4.4, nota 4 pie 17 y nota 5 pie 23).
        if ($tipo_receptor === '06') {
            $CondicionVenta = '13';
            $PlazoCredito = '';
        }

        // CodigoActividadEmisor es opcional en la FEC: ni el "No Contribuyente"
        // ni el "Extranjero No Domiciliado" tienen actividad inscrita en el RUT.
        $actividad_emisor = in_array($tipo_receptor, array('05', '06'), true)
            ? ''
            : ($receptor->actividad_economica ?? '');


        $consecutivo = $this->hacienda_model->ccsctvfec($tipodoc);
        $NumConse = $this->hacienda_model->ultimo_consecutivo('08', $consecutivo);

        $consecutive = $this->generate_consecutive_fec($NumConse + 1, '08');

        // La clave debe llevar la misma fecha que <FechaEmision> o Hacienda
        // rechaza el comprobante con el error -405.
        $param = [$consecutive, $fecha];
        $key = $this->generate_key($param);


        $invo .= '
                <Clave>' . $key . '</Clave>
                <ProveedorSistemas>' . $this->proveedorSistemas() . '</ProveedorSistemas>
                ' . $this->bloqueCodigoActividad($actividad_emisor, $CodActividad) . '
                <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
                <FechaEmision>' . $fecha . '</FechaEmision>
                <Emisor>
                    <Nombre>' . trim($this->quitatilde($receptor->name)) . '</Nombre>
                    <Identificacion>
                    <Tipo>' . $tipo_receptor . '</Tipo>
                    <Numero>' . trim(str_replace("-", "", (string) $receptor->cf2)) . '</Numero>
                    </Identificacion>'
                    . $this->bloqueNombreComercial($receptor->company ?? '')
                    . $this->bloqueUbicacion($receptor, $tipo_receptor, 'direccion')
                    . $this->bloqueSenasExtranjero($receptor, $tipo_receptor)
                    . $this->bloqueTelefono($receptor->phone ?? '')
                    . $this->bloqueCorreo($receptor->email ?? '')
                    . '</Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
            <Identificacion>
                    <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                    <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor))  . '</Numero>
            </Identificacion>';
            $invo .= $this->bloqueNombreComercial($this->Settings->nombre_comercial);
            $invo .= $this->bloqueTelefono($this->Settings->telefono_emisor, $this->Settings->cod_telefono_emisor);


            $invo .= $this->bloqueCorreo($this->Settings->email_emisor);

            $invo .= '</Receptor>';
        }


        $invo .= '<CondicionVenta>' . $CondicionVenta . '</CondicionVenta>';

        if ($PlazoCredito) {
            $invo .= '<PlazoCredito>' . $PlazoCredito . '</PlazoCredito>';
        }

        $invo .= '<DetalleServicio>';

        $NumeroLinea = 0;
        $TotalServGravados = 0.00000;
        $TotalServExentos = 0.00000;
        $TotalServExonerado = 0.00000;
        $TotalServNoSujeto = 0.00000;
        $TotalMercanciasGravadas = 0.00000;
        $TotalMercanciasExentas = 0.00000;
        $TotalMercanciaExonerada = 0.00000;
        $TotalMercNoSujeta = 0.00000;
        $TotalGravado = 0.00000;
        $TotalExento = 0.00000;
        $TotalExonerado = 0.00000;
        $TotalVenta = 0.00000;
        $TotalDescuentos = 0.00000;
        $TotalVentaNeta = 0.00000;
        $TotalImpuesto = 0.00000;
        $TotalComprobante = 0.00000;


        $itemsInvoices = $this->reClacDiscount($itemsInvoices, $invoice);
        $count = 0;
        // Impuesto acumulado por codigo y tarifa, para el desglose del resumen.
        $desgloseImp = array();
        foreach ($itemsInvoices as $items) {
            if (strpos($items["discount"], '%') === false) {
                $items["item_discount"] = $items["discount"];
            };

            $NumeroLinea = $NumeroLinea + 1;
            // Tipo de codigo comercial: 01 es el del vendedor, 02 el del comprador.
            $CodigoTipo = (($items['type'] ?? '') === 'service') ? "02" : "01";


            $CodigoCodigo = $items["product_code"];
            $Cantidad = $items['quantity'];

            if ($items['item_tax']) {
                $ImpuestoTarifa = (int) str_replace('%', '', $items["tax"]);
                $calc_imp = $ImpuestoTarifa / 100;
            } else {
                $calc_imp = 1;
            }

            $Detalle = $items["product_name"];

            $row = $this->site->getProductByID($items['product_id']);
            $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $qq = $this->db->get_where('impuestos', array('id_impuesto' => isset($items['id_tax']) ? $items['id_tax'] : 8), 1);
            if ($qq->num_rows() > 0) {
                $im = $qq->row();
                $items['id_impuesto'] = $im->id_impuesto;
                $items['codigo_impuesto'] = $im->codigo_impuesto;
                $items['codigo_tarifa'] = $im->codigo_tarifa;
            } else {
                $items['id_impuesto'] = 0;
                $items['codigo_impuesto'] = 0;
                $items['codigo_tarifa'] = 0;
            }

            // if ($row) {
            //     if ($row->tax_method == "1") {
            //         $PrecioUnitario = $items["real_unit_price"];
            //         $InvertirImpuestoTarifa = 1;
            //     } else if ($row->tax_method == "0") {
            //         if (!$items['item_tax']) {
            //             $PrecioUnitario = $items["real_unit_price"];
            //         } else {
            //             $InvertirImpuestoTarifa = 1 + (floatval(str_replace('%', '', $items["tax"])) / 100);
            //             $PrecioUnitario = number_format((float) ($items["real_unit_price"]), 5, '.', '') / number_format((float) ($InvertirImpuestoTarifa), 5, '.', '');
            //         }
            //     }
            // } else {
                $PrecioUnitario = $items["real_unit_price"];
                $InvertirImpuestoTarifa = 1;
            // }

            $MontoTotal = $items["quantity"] * $PrecioUnitario;

            $NaturalezaDescuento = null;
            if ($items["item_discount"] > 0) {
                $NaturalezaDescuento = $items["product_name"];
            }

            $MontoDescuento = 0;

            if ($items["discount"] > 0) {
                $MontoDescuento = $items['discount'];
            }

            $SubTotal = $MontoTotal - $MontoDescuento;

            $ImpuestoCodigo = $items["codigo_impuesto"];
            $ImpuestoTarifa = (float) str_replace('%', '', (string) ($items["tax"] ?? 0));
            $TarifaCodigo = $this->codigoTarifa($ImpuestoTarifa, $items["codigo_tarifa"]);
            $ImpuestoMonto = $items['item_tax'];

            

            if (!$items['item_tax']) {
            $ImpuestoCodigo = "98";
            $ImpuestoTarifa = "0";
            $ImpuestoMonto = 0;
            } else {
            $ImpuestoCodigo = "01";
            $ImpuestoTarifa = (float) str_replace('%', '', (string) ($items["tax"] ?? 0));
            $ImpuestoMonto = number_format((float) ($SubTotal * str_replace('%', '', $items["tax"]) / 100), 4, '.', '');
            }
            

            $invo .= '<LineaDetalle>
            <NumeroLinea>' . $NumeroLinea . '</NumeroLinea>
            ' . $this->bloqueCodigoCabys(is_object($row) ? ($row->cabys ?? '') : '', $items['cabys'] ?? '', $items['product_code'] ?? '') . '
            <CodigoComercial>
            <Tipo>' . $CodigoTipo . '</Tipo>
            <Codigo>' . substr($CodigoCodigo, 0, 19) . '</Codigo>
            </CodigoComercial>'
            . '<Cantidad>' . $Cantidad . '</Cantidad>
            <UnidadMedida>' . $this->unidadMedida($items["unit_of_measurement"] ?? '') . '</UnidadMedida>
            <Detalle>' . substr($this->quitatilde($Detalle), 0, 80) . '</Detalle>
            <PrecioUnitario>' . number_format((float) ($PrecioUnitario), 5, '.', '') . '</PrecioUnitario>
            <MontoTotal>' . number_format((float) ($MontoTotal), 5, '.', '') . '</MontoTotal>';

            if ($MontoDescuento > 0) {
                $invo .= $this->bloqueDescuento($MontoDescuento, $NaturalezaDescuento);
            }

            $invo .= '<SubTotal>' . number_format((float) ($SubTotal), 5, '.', '') . '</SubTotal>';
                $invo .= '<BaseImponible>' . number_format((float) ($SubTotal), 5, '.', '') . '</BaseImponible>';
            $MontoExoneracion = 0.00000;
            $PorcentajeExoneracion = 0;
            $ImpuestoNeto = 0.00000;

            // Impuesto es obligatorio en cada linea desde la v4.4: una linea
            // exenta lo declara con tarifa 0% (codigo 01) y monto en cero.
            $exenta = ($TarifaCodigo == '01');
            $invo .= '<Impuesto>
                            <Codigo>' . ($exenta ? '01' : $ImpuestoCodigo) . '</Codigo>
                            <CodigoTarifaIVA>' . ($exenta ? '01' : $TarifaCodigo) . '</CodigoTarifaIVA>
                            <Tarifa>' . ($exenta ? '0.00000' : $ImpuestoTarifa) . '</Tarifa>
                            <Monto>' . number_format((float) ($exenta ? 0 : $ImpuestoMonto), 5, '.', '') . '</Monto>';

            if (!$exenta) {
                if (isset($invoice['TipoDocumentoE']) && $invoice['TipoDocumentoE'] != '') {
                    $PorcentajeExoneracion = $invoice['PorcentajeExoneracion'];
                    $MontoExoneracion = number_format((float) ($ImpuestoMonto), 5, '.', '') * (number_format((float) ($PorcentajeExoneracion), 5, '.', '') / 100);
                    $invo .= '
                    <Exoneracion>
                    <TipoDocumentoEX1>' . $invoice['TipoDocumentoE'] . '</TipoDocumentoEX1>
                    <NumeroDocumento>' . $invoice['NumeroDocumentoE'] . '</NumeroDocumento>
                    <NombreInstitucion>' . $this->quitatilde($invoice['NombreInstitucionE']) . '</NombreInstitucion>
                    <FechaEmisionEX>' . $invoice['FechaEmisionE'] . '</FechaEmisionEX>
                    <TarifaExonerada>' . $invoice['PorcentajeExoneracion'] . '</TarifaExonerada>
                    <MontoExoneracion>' . number_format((float) ($MontoExoneracion), 5, '.', '') . '</MontoExoneracion>
                    </Exoneracion>';
                }

                $ImpuestoNeto = number_format((float) ($ImpuestoMonto), 5, '.', '') - number_format((float) ($MontoExoneracion), 5, '.', '');
            }

            $invo .= '</Impuesto>';
                $llaveImp = ($exenta ? '01' : $ImpuestoCodigo) . '|' . ($exenta ? '01' : $TarifaCodigo);
                $desgloseImp[$llaveImp] = ($desgloseImp[$llaveImp] ?? 0) + ($exenta ? 0 : (float) $ImpuestoMonto);


            // La linea de la FEC pasa de Impuesto a ImpuestoNeto: a diferencia de la
            // factura, no lleva ImpuestoAsumidoEmisorFabrica.
                $invo .= '<ImpuestoNeto>' . number_format((float) ($ImpuestoNeto), 5, '.', '') . '</ImpuestoNeto>';

                $MontoTotalLinea = number_format((float) ($SubTotal), 5, '.', '') + number_format((float) ($ImpuestoNeto), 5, '.', '');

            $invo .= '<MontoTotalLinea>' . number_format((float) ($MontoTotalLinea), 5, '.', '') . '</MontoTotalLinea>
                    </LineaDetalle>';
            $natural = $this->naturalezaLinea($TarifaCodigo);
            $exonerado = $MontoTotal * ($PorcentajeExoneracion / 100);

            if ($this->esServicio($this->cabysDeLinea($row, $items), $items['type'] ?? '')) {
                if ($natural === 'nosujeto') {
                    $TotalServNoSujeto = $TotalServNoSujeto + $MontoTotal;
                } elseif ($natural === 'exento') {
                    $TotalServExentos = $TotalServExentos + $MontoTotal;
                } else {
                    $TotalServGravados = $TotalServGravados + ($MontoTotal - $exonerado);
                    $TotalServExonerado = $TotalServExonerado + $exonerado;
                }
            } else {
                if ($natural === 'nosujeto') {
                    $TotalMercNoSujeta = $TotalMercNoSujeta + $MontoTotal;
                } elseif ($natural === 'exento') {
                    $TotalMercanciasExentas = $TotalMercanciasExentas + $MontoTotal;
                } else {
                    $TotalMercanciasGravadas = $TotalMercanciasGravadas + ($MontoTotal - $exonerado);
                    $TotalMercanciaExonerada = $TotalMercanciaExonerada + $exonerado;
                }
            }

            $TotalDescuentos = $TotalDescuentos + $MontoDescuento;
            $TotalImpuesto = $TotalImpuesto + $ImpuestoNeto;
            $count++;
        }

        $TotalExento = $TotalServExentos + $TotalMercanciasExentas;
        $TotalGravado = $TotalMercanciasGravadas + $TotalServGravados;
        $TotalExonerado = $TotalServExonerado + $TotalMercanciaExonerada;
        $TotalVenta = $TotalGravado + $TotalExento + $TotalExonerado;
        $TotalVentaNeta = $TotalVenta - $TotalDescuentos;
        $TotalComprobante = $TotalVentaNeta + $TotalImpuesto;

        $invo .= '</DetalleServicio>';
        $TotalExento = number_format((float) ($TotalServExentos), 5, '.', '') + number_format((float) ($TotalMercanciasExentas), 5, '.', '');
        $TotalGravado = number_format((float) ($TotalMercanciasGravadas), 5, '.', '') + number_format((float) ($TotalServGravados), 5, '.', '');
        $TotalExonerado = number_format((float) ($TotalServExonerado), 5, '.', '') + number_format((float) ($TotalMercanciaExonerada), 5, '.', '');
        $TotalNoSujeto = number_format((float) ($TotalServNoSujeto), 5, '.', '') + number_format((float) ($TotalMercNoSujeta), 5, '.', '');
        $TotalVenta = number_format((float) ($TotalGravado), 5, '.', '') + number_format((float) ($TotalExento), 5, '.', '') + number_format((float) ($TotalExonerado), 5, '.', '') + number_format((float) ($TotalNoSujeto), 5, '.', '');
        $TotalVentaNeta = number_format((float) ($TotalVenta), 5, '.', '') - number_format((float) ($TotalDescuentos), 5, '.', '');
        $TotalComprobante = number_format((float) ($TotalVentaNeta), 5, '.', '') + number_format((float) ($TotalImpuesto), 5, '.', '');
        $invo .= '
                  <ResumenFactura>
                  <CodigoTipoMoneda>
                    <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
                    <TipoCambio>' . $this->tipoCambio() . '</TipoCambio>
                  </CodigoTipoMoneda>';

        $invo .= '<TotalServGravados>' . number_format((float) ($TotalServGravados), 5, '.', '') . '</TotalServGravados>
        <TotalServExentos>' . number_format((float) ($TotalServExentos), 5, '.', '') . '</TotalServExentos>'
            . '';

        if ($TotalServExonerado > 0) {
            $invo .= '<TotalServExonerado>' . number_format((float) ($TotalServExonerado), 5, '.', '') . '</TotalServExonerado>';
        }
        if ($TotalServNoSujeto > 0) {
            $invo .= '<TotalServNoSujeto>' . number_format((float) ($TotalServNoSujeto), 5, '.', '') . '</TotalServNoSujeto>';
        }

        $invo .= '<TotalMercanciasGravadas>' . number_format((float) ($TotalMercanciasGravadas), 5, '.', '') . '</TotalMercanciasGravadas>
                     <TotalMercanciasExentas>' . number_format((float) ($TotalMercanciasExentas), 5, '.', '') . '</TotalMercanciasExentas>';

        if ($TotalMercanciaExonerada > 0) {
            $invo .= '<TotalMercExonerada>' . number_format((float) ($TotalMercanciaExonerada), 5, '.', '') . '</TotalMercExonerada>';
        }
        if ($TotalMercNoSujeta > 0) {
            $invo .= '<TotalMercNoSujeta>' . number_format((float) ($TotalMercNoSujeta), 5, '.', '') . '</TotalMercNoSujeta>';
        }

        $invo .= '<TotalGravado>' . number_format((float) ($TotalGravado), 5, '.', '') . '</TotalGravado>
                    <TotalExento>' . number_format((float) ($TotalExento), 5, '.', '') . '</TotalExento>';
        if ($TotalMercanciaExonerada > 0) {
            $invo .= '<TotalExonerado>' . number_format((float) ($TotalExonerado), 5, '.', '') . '</TotalExonerado>';
        }
        $invo .= '<TotalVenta>' . number_format((float) ($TotalVenta), 5, '.', '') . '</TotalVenta>
                    <TotalDescuentos>' . number_format((float) ($TotalDescuentos), 5, '.', '') . '</TotalDescuentos>
                    <TotalVentaNeta>' . number_format((float) ($TotalVentaNeta), 5, '.', '') . '</TotalVentaNeta>
                    ' . $this->bloqueDesgloseImpuesto($desgloseImp) . '<TotalImpuesto>' . number_format((float) ($TotalImpuesto), 5, '.', '') . '</TotalImpuesto>';
        $invo .=    '<!--NX_MEDIOPAGO--><TotalComprobante>' . number_format((float) ($TotalComprobante), 5, '.', '') . '</TotalComprobante>
                  </ResumenFactura>';

        $invo .= $this->bloqueReferenciaFec($invoice, $tipo_receptor, $fecha);

        if ($otrostextos) {
            $invo .= '<Otros>';
            foreach ($otrostextos as $texto) {
                $texto = (array) $texto;
                $invo .= '<OtroTexto codigo="' . $texto['titulo_texto'] . '" >' . $this->quitatilde($texto['otrotexto']) . '</OtroTexto>';
            }
            $invo .= '</Otros>';
        }

        $invo .= '</FacturaElectronicaCompra>';
        // <MedioPago> va antes del detalle pero depende de TotalComprobante:
        // se emitio como marcador y se resuelve aca.
        $invo = str_replace('<!--NX_MEDIOPAGO-->', $this->bloqueMedioPago(isset($payment) ? $payment : (isset($pagoNota) ? $pagoNota : null), $TotalComprobante), $invo);
        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha, 'tipo_doc' => $tipodoc];
    }

    public function getNotaCredito($invoice, $itemsInvoices, $referencia, $otrostextos)
    {
        $this->load->model('customers_model');
        $this->load->model('hacienda_model');
        $receptor = $this->customers_model->getCustomerByID($invoice['customer_id']);
        $customer_id = $invoice['customer_id'];
        $moneda = $this->Settings->currency_prefix;
        $CondicionVenta = "";
        $PlazoCredito = "";

        if ($invoice['status'] == 'paid') {
            $CondicionVenta = '01';
        } else {
            $CondicionVenta = '02';
            // El plazo sale del cliente; 30 dias es lo que se declara cuando el
            // cliente no lo tiene definido.
            $PlazoCredito = plazo_credito_dias($receptor ?? null, 30) . ' dias';
        }

        // Las notas no tienen cobro propio; se declara Efectivo.
        $pagoNota = array('paid_by' => 'cash');


        // La fecha tiene que existir antes de la clave: generate_key() la incrusta
        // y sin ella la clave sale de 43 digitos en vez de 50.
        date_default_timezone_set('America/Costa_Rica');
        $fecha = date('Y-m-d\TH:i:s');

        $consecutivo = $this->hacienda_model->ccsctvcn();
        $NumConse = $this->hacienda_model->ultimo_consecutivo('03', $consecutivo['consecutivo'] ?? null);

        $consecutive = $this->generate_consecutive($NumConse + 1, '03');

        // La clave debe llevar la misma fecha que <FechaEmision> o Hacienda
        // rechaza el comprobante con el error -405.
        $param = [$consecutive, $fecha];
        $key = $this->generate_key($param);
        $receptor->pre_id_number = $receptor->cf1;
        $receptor->id_number_proveedor = $receptor->cf2;
        $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));
        $identificacion = str_replace('-', '', trim($receptor->id_number_proveedor));

        $tipo_receptor = $this->tipoDeReceptor($receptor, $identifivalid, false);
        // La nota debe coincidir con el comprobante original, y ese ya declara al
        // extranjero como receptor: el anexo admite el codigo 05 en NC y ND.
        $sireceptor = strtolower(trim($receptor->name)) != "cliente de paso"
            && strtolower(trim($receptor->name)) != "cliente de contado"
            && ($tipo_receptor != '05' || $this->receptorExtranjero($receptor));

        $invo = '<?xml version="1.0" encoding="UTF-8"?>
                <NotaCreditoElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaCreditoElectronica" xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaCreditoElectronica https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/NotaCreditoElectronica_V4.4.xsd" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <Clave>' . $key . '</Clave>
                  <ProveedorSistemas>' . $this->proveedorSistemas() . '</ProveedorSistemas>
                  ' . $this->bloqueCodigoActividad('', $sireceptor ? ($receptor->codigo_actividad ?? '') : '') . '
                  <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
                  <FechaEmision>' . $fecha . '</FechaEmision>
                  <Emisor>
                    <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
                    <Identificacion>
                      <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                      <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor)) . '</Numero>
                    </Identificacion>
                    <NombreComercial>' . $this->quitatilde(trim($this->Settings->nombre_comercial)) . '</NombreComercial>
                    <Ubicacion>
                      <Provincia>' . substr($this->Settings->cod_provincia, -2) . '</Provincia>
                      <Canton>' . substr(trim($this->Settings->cod_canton), -2) . '</Canton>
                      <Distrito>' . substr($this->Settings->cod_distrito, -2) . '</Distrito>
                      ' . $this->bloqueBarrio($this->Settings->cod_barrio) . '
                      <OtrasSenas>' . trim($this->Settings->otras_senas) . '</OtrasSenas>
                    </Ubicacion>
                    <Telefono>
                      <CodigoPais>' . trim($this->Settings->cod_telefono_emisor) . '</CodigoPais>
                      <NumTelefono>' . trim(str_replace("-", "", $this->Settings->telefono_emisor)) . '</NumTelefono>
                    </Telefono>
                    <CorreoElectronico>' . trim($this->Settings->email_emisor) . '</CorreoElectronico>
                  </Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . trim($receptor->name) . '</Nombre>
            <Identificacion>
                    <Tipo>' . $tipo_receptor . '</Tipo>
                    <Numero>' . $receptor->id_number_proveedor . '</Numero>
            </Identificacion>';

            if ($receptor->business_name) {
                $invo .= '<NombreComercial>' . $receptor->business_name . '</NombreComercial>';
            }

            $invo .= $this->bloqueUbicacion($receptor, $tipo_receptor) . $this->bloqueSenasExtranjero($receptor, $tipo_receptor);

            if (strlen(trim(str_replace("-", "", $receptor->phone))) == 8) {
                $invo .= '
                <Telefono>
                    <CodigoPais>' . (preg_replace('/\D/', '', (string) ($receptor->cod_telefono ?? '')) ?: '506') . '</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $receptor->phone)) . '</NumTelefono>
                </Telefono>';
            }

            if ($receptor->email) {
                $invo .= '<CorreoElectronico>' . $receptor->email . '</CorreoElectronico>';
            }

            $invo .= '</Receptor>';
        }


        $invo .= '<CondicionVenta>' . $CondicionVenta . '</CondicionVenta>';

        if ($PlazoCredito) {
            $invo .= '<PlazoCredito>' . $PlazoCredito . '</PlazoCredito>';
        }

        $invo .= '<DetalleServicio>';

        $NumeroLinea = 0;
        $TotalServGravados = 0.0000;
        $TotalServExentos = 0.0000;
        $TotalServNoSujeto = 0.0000;
        $TotalMercanciasGravadas = 0.0000;
        $TotalMercanciasExentas = 0.0000;
        $TotalMercNoSujeta = 0.0000;
        $TotalGravado = 0.0000;
        $TotalExento = 0.0000;
        $TotalNoSujeto = 0.0000;
        $TotalVenta = 0.0000;
        $TotalDescuentos = 0.0000;
        $TotalVentaNeta = 0.0000;
        $TotalImpuesto = 0.0000;
        $TotalComprobante = 0.0000;

        $itemsInvoices = $this->reClacDiscount($itemsInvoices, $invoice);

        // Impuesto acumulado por codigo y tarifa, para el desglose del resumen.
        $desgloseImp = array();
        foreach ($itemsInvoices as $items) {

            if (strpos($items["discount"], '%') === false) {
                $items["item_discount"] = $items["discount"];
            };

            $NumeroLinea = $NumeroLinea + 1;
            $CodigoTipo = "03";
            $CodigoCodigo = $items["product_code"];
            $Cantidad = $items['quantity'];

            if ($items['item_tax']) {
                $ImpuestoTarifa = (int) str_replace('%', '', $items["tax"]);
                $calc_imp = $ImpuestoTarifa / 100;
            } else {
                $calc_imp = 1;
            }

            $Detalle = $items["product_name"];

            $row = $this->site->getProductByID($items['product_id']);
            $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $id_tax_lookup3 = isset($row->id_tax) ? $row->id_tax : (isset($items['id_tax']) && $items['id_tax'] > 0 ? $items['id_tax'] : 8);
            $qq = $this->db->get_where('impuestos', array('id_impuesto' => $id_tax_lookup3), 1);
            if ($qq->num_rows() > 0) {
                $im = $qq->row();
                $items['codigo_impuesto'] = $im->codigo_impuesto;
                $items['codigo_tarifa']   = $im->codigo_tarifa;
            } else {
                $items['codigo_impuesto'] = '01';
                $items['codigo_tarifa']   = '08';
            }
            // Un articulo rapido no tiene ficha de producto: su precio ya viene
            // como se cobro. getInvoice() lo contempla desde siempre; aca faltaba
            // y la linea leia tax_method sobre null.
            if ($row) {
                if ($row->tax_method == "1") {
                    $PrecioUnitario = $items["real_unit_price"];
                    $InvertirImpuestoTarifa = 1;
                } else if ($row->tax_method == "0") {
                    if (!$items['item_tax']) {
                        $PrecioUnitario = $items["real_unit_price"];
                    } else {
                        $InvertirImpuestoTarifa = 1 + (floatval(str_replace('%', '', $items["tax"])) / 100);
                        $PrecioUnitario = number_format((float) ($items["real_unit_price"] / $InvertirImpuestoTarifa), 5, '.', '');
                    }
                }
            } else {
                $PrecioUnitario = $items["real_unit_price"];
                $InvertirImpuestoTarifa = 1;
            }
            $MontoTotal = $items["quantity"] * $PrecioUnitario;

            $NaturalezaDescuento = null;
            if ($items["item_discount"] > 0) {
                $NaturalezaDescuento = $items["product_name"];
            }

            $MontoDescuento = 0;

            if ($items["discount"] > 0) {
                $MontoDescuento = $MontoTotal * (str_replace('%', '', $items["discount"]) / 100);
            }

            $SubTotal = $MontoTotal - $MontoDescuento;

            $ImpuestoCodigo = $items['codigo_impuesto'];
            $ImpuestoTarifa = (float) str_replace('%', '', (string) ($items["tax"] ?? 0));
            $TarifaCodigo   = $this->codigoTarifa($ImpuestoTarifa, $items['codigo_tarifa']);
            $ImpuestoMonto  = 0;
            $ImpuestoNeto   = 0;
            if ($TarifaCodigo != '01') {
                $ImpuestoMonto = number_format((float) ($SubTotal * floatval($ImpuestoTarifa) / 100), 5, '.', '');
                $ImpuestoNeto  = $ImpuestoMonto;
            }
            $MontoTotalLinea = number_format((float) ($SubTotal), 5, '.', '') + number_format((float) ($ImpuestoNeto), 5, '.', '');
            $invo .= '<LineaDetalle>
                        <NumeroLinea>' . $NumeroLinea . '</NumeroLinea>
                        ' . $this->bloqueCodigoCabys(is_object($row) ? ($row->cabys ?? '') : '', $items['cabys'] ?? '', $items['product_code'] ?? '') . '
                        <CodigoComercial>
                        <Tipo>' . $CodigoTipo . '</Tipo>
                        <Codigo>' . substr($CodigoCodigo, 0, 19) . '</Codigo>
                        </CodigoComercial>'
                        . '<Cantidad>' . $Cantidad . '</Cantidad>
                        <UnidadMedida>' . $this->unidadMedida($items["unit_of_measurement"] ?? '') . '</UnidadMedida>
                        <Detalle>' . substr($this->quitatilde($Detalle), 0, 80) . '</Detalle>
                        <PrecioUnitario>' . number_format((float) ($PrecioUnitario), 5, '.', '') . '</PrecioUnitario>
                        <MontoTotal>' . number_format((float) ($MontoTotal), 5, '.', '') . '</MontoTotal>';

            if ($MontoDescuento > 0) {
                $invo .= $this->bloqueDescuento($MontoDescuento, $NaturalezaDescuento);
            }

            $invo .= '<SubTotal>' . number_format((float) ($SubTotal), 5, '.', '') . '</SubTotal>';
                $invo .= '<BaseImponible>' . number_format((float) ($SubTotal), 5, '.', '') . '</BaseImponible>';
            // Impuesto es obligatorio en cada linea desde la v4.4: una linea
            // exenta lo declara con tarifa 0% (codigo 01) y monto en cero.
            $exenta = ($TarifaCodigo == '01');
            $invo .= '<Impuesto>
                            <Codigo>' . ($exenta ? '01' : $ImpuestoCodigo) . '</Codigo>
                            <CodigoTarifaIVA>' . ($exenta ? '01' : $TarifaCodigo) . '</CodigoTarifaIVA>
                            <Tarifa>' . ($exenta ? '0.00000' : $ImpuestoTarifa) . '</Tarifa>
                            <Monto>' . number_format((float) ($exenta ? 0 : $ImpuestoMonto), 5, '.', '') . '</Monto>
                        </Impuesto>';
                $llaveImp = ($exenta ? '01' : $ImpuestoCodigo) . '|' . ($exenta ? '01' : $TarifaCodigo);
                $desgloseImp[$llaveImp] = ($desgloseImp[$llaveImp] ?? 0) + ($exenta ? 0 : (float) $ImpuestoMonto);


            // Los dos son obligatorios en la v4.4 aunque la linea no lleve impuesto.
            $invo .= '<ImpuestoAsumidoEmisorFabrica>0.00000</ImpuestoAsumidoEmisorFabrica>';
            $invo .= '<ImpuestoNeto>' . number_format((float) ($ImpuestoNeto), 5, '.', '') . '</ImpuestoNeto>';

            $invo .= '<MontoTotalLinea>' . number_format((float) ($MontoTotalLinea), 5, '.', '') . '</MontoTotalLinea>
                    </LineaDetalle>';

            $natural = $this->naturalezaLinea($TarifaCodigo);

            if ($this->esServicio($this->cabysDeLinea($row, $items), $items['type'] ?? '')) {
                if ($natural === 'nosujeto') {
                    $TotalServNoSujeto = $TotalServNoSujeto + $MontoTotal;
                    $TotalNoSujeto = $TotalNoSujeto + $MontoTotal;
                } elseif ($natural === 'exento') {
                    $TotalServExentos = $TotalServExentos + $MontoTotal;
                    $TotalExento = $TotalExento + $MontoTotal;
                } else {
                    $TotalServGravados = $TotalServGravados + $MontoTotal;
                    $TotalGravado = $TotalGravado + $MontoTotal;
                }
            } else {
                if ($natural === 'nosujeto') {
                    $TotalMercNoSujeta = $TotalMercNoSujeta + $MontoTotal;
                    $TotalNoSujeto = $TotalNoSujeto + $MontoTotal;
                } elseif ($natural === 'exento') {
                    $TotalMercanciasExentas = $TotalMercanciasExentas + $MontoTotal;
                    $TotalExento = $TotalExento + $MontoTotal;
                } else {
                    $TotalMercanciasGravadas = $TotalMercanciasGravadas + $MontoTotal;
                    $TotalGravado = $TotalGravado + $MontoTotal;
                }
            }

            $TotalVenta = $TotalVenta + $MontoTotal;

            $TotalDescuentos = $TotalDescuentos;
            $TotalVentaNeta = $TotalVentaNeta + $SubTotal;
            $TotalImpuesto = $TotalImpuesto + $ImpuestoNeto;
            $TotalDescuentos = $TotalDescuentos + $MontoDescuento;
        }
        $TotalComprobante = $TotalVentaNeta + $TotalImpuesto;

        $invo .= '</DetalleServicio>';
        $invo .= '
                  <ResumenFactura>
                  <CodigoTipoMoneda>
                    <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
                    <TipoCambio>' . $this->tipoCambio() . '</TipoCambio>
                  </CodigoTipoMoneda>
                  <TotalServGravados>' . number_format((float) ($TotalServGravados), 5, '.', '') . '</TotalServGravados>
                  <TotalServExentos>' . number_format((float) ($TotalServExentos), 5, '.', '') . '</TotalServExentos>'
                  . ($TotalServNoSujeto > 0 ? '<TotalServNoSujeto>' . number_format((float) ($TotalServNoSujeto), 5, '.', '') . '</TotalServNoSujeto>' : '') . '
                  <TotalMercanciasGravadas>' . number_format((float) ($TotalMercanciasGravadas), 5, '.', '') . '</TotalMercanciasGravadas>
                  <TotalMercanciasExentas>' . number_format((float) ($TotalMercanciasExentas), 5, '.', '') . '</TotalMercanciasExentas>'
                  . ($TotalMercNoSujeta > 0 ? '<TotalMercNoSujeta>' . number_format((float) ($TotalMercNoSujeta), 5, '.', '') . '</TotalMercNoSujeta>' : '') . '
                  <TotalGravado>' . number_format((float) ($TotalGravado), 5, '.', '') . '</TotalGravado>
                  <TotalExento>' . number_format((float) ($TotalExento), 5, '.', '') . '</TotalExento>'
                  . ($TotalNoSujeto > 0 ? '<TotalNoSujeto>' . number_format((float) ($TotalNoSujeto), 5, '.', '') . '</TotalNoSujeto>' : '') . '
                  <TotalVenta>' . number_format((float) ($TotalVenta), 5, '.', '') . '</TotalVenta>
                  <TotalDescuentos>' . number_format((float) ($TotalDescuentos), 5, '.', '') . '</TotalDescuentos>
                  <TotalVentaNeta>' . number_format((float) ($TotalVentaNeta), 5, '.', '') . '</TotalVentaNeta>
                  ' . $this->bloqueDesgloseImpuesto($desgloseImp) . '<TotalImpuesto>' . number_format((float) ($TotalImpuesto), 5, '.', '') . '</TotalImpuesto>
                  <!--NX_MEDIOPAGO--><TotalComprobante>' . number_format((float) ($TotalComprobante), 5, '.', '') . '</TotalComprobante>
                  </ResumenFactura>
                  <InformacionReferencia>
                      <TipoDocIR>' . $this->tipoDocReferencia($referencia) . '</TipoDocIR>
                      <Numero>' . $referencia->clave . '</Numero>
                      <FechaEmisionIR>' . $this->fechaIso($referencia->fecha_emision) . '</FechaEmisionIR>
                      <Codigo>' . codigo_referencia_valido(isset($invoice['type_nc']) ? $invoice['type_nc'] : '01') . '</Codigo>
                      <Razon>' . $this->quitatilde(mb_substr(trim((string) ($invoice['hold_ref'] ?? '')), 0, 180)) . '</Razon>
                  </InformacionReferencia>';

        if ($otrostextos) {
            $invo .= '<Otros>';
            foreach ($otrostextos as $texto) {
                $texto = (array) $texto;
                $invo .= '<OtroTexto codigo="' . $texto['titulo_texto'] . '" >' . $texto['otrotexto'] . '</OtroTexto>';
            }
            $invo .= '</Otros>';
        }
        $invo .= '    </NotaCreditoElectronica>
                    ';
        // <MedioPago> va antes del detalle pero depende de TotalComprobante:
        // se emitio como marcador y se resuelve aca.
        $invo = str_replace('<!--NX_MEDIOPAGO-->', $this->bloqueMedioPago(isset($payment) ? $payment : (isset($pagoNota) ? $pagoNota : null), $TotalComprobante), $invo);
        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha];
    }

    /**
     * Mensaje receptor: acepta, acepta en parte o rechaza un comprobante recibido.
     *
     * @param array $invoice Mensaje, DetalleMensaje, CondicionImpuesto, los dos montos,
     *                       los datos del comprobante y `numero_consecutivo`, el numero
     *                       que toca dentro de la serie de su tipo (Anexos v4.4, nota 3)
     * @return array{0: string, 1: string, 2: string, 3: string} xml, consecutivo, mensaje, tipo
     */
    public function getMensajeReceptor($invoice)
    {
        $tipos = array('1' => '05', '2' => '06', '3' => '07');
        $mensaje = (string) $invoice['Mensaje'];
        if (!isset($tipos[$mensaje])) {
            throw new InvalidArgumentException('Mensaje receptor invalido: ' . $mensaje);
        }
        $tipoDocumento = $tipos[$mensaje];

        $numero = isset($invoice['numero_consecutivo']) ? $invoice['numero_consecutivo'] : $invoice['id_documento'];
        $consecutive = $this->generate_consecutive($numero, $tipoDocumento);

        // Las cedulas van sin guiones ni ceros a la izquierda (Anexos v4.4, pag. 60).
        $cedula = function ($v) { return ltrim(preg_replace('/\D/', '', (string) $v), '0'); };
        $monto  = function ($v) { return number_format((float) $v, 5, '.', ''); };

        $xml = '<?xml version="1.0" encoding="utf-8"?>
<MensajeReceptor xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/mensajeReceptor" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <Clave>' . trim((string) $invoice['ClaveDocEmisor']) . '</Clave>
  <NumeroCedulaEmisor>' . $cedula($invoice['NumeroCedulaEmisor']) . '</NumeroCedulaEmisor>
  <FechaEmisionDoc>' . $this->fechaIso($invoice['FechaEmisionDoc']) . '</FechaEmisionDoc>
  <Mensaje>' . $mensaje . '</Mensaje>';

        $detalle = trim($this->quitatilde((string) ($invoice['DetalleMensaje'] ?? '')));
        if ($detalle !== '') {
            $xml .= '
  <DetalleMensaje>' . mb_substr($detalle, 0, 160) . '</DetalleMensaje>';
        }

        // Obligatorio cuando el comprobante trae impuesto, y debe coincidir con el suyo.
        $impuesto = (float) ($invoice['MontoTotalImpuesto'] ?? 0);
        if ($impuesto > 0) {
            $xml .= '
  <MontoTotalImpuesto>' . $monto($impuesto) . '</MontoTotalImpuesto>';
        }

        $condicion = (string) ($invoice['CondicionImpuesto'] ?? '');
        if ($mensaje !== '3' && $impuesto > 0 && $condicion !== '' && $condicion !== '00') {
            $actividad = trim((string) ($this->Settings->default_actividad ?? ''));
            if ($actividad !== '') {
                $xml .= '
  <CodigoActividad>' . mb_substr($actividad, 0, 6) . '</CodigoActividad>';
            }
            $xml .= '
  <CondicionImpuesto>' . $condicion . '</CondicionImpuesto>';
            if ((float) $invoice['MontoTotalImpuestoAcreditar'] > 0) {
                $xml .= '
  <MontoTotalImpuestoAcreditar>' . $monto($invoice['MontoTotalImpuestoAcreditar']) . '</MontoTotalImpuestoAcreditar>';
            }
            if ((float) $invoice['MontoTotalDeGastoAplicable'] > 0) {
                $xml .= '
  <MontoTotalDeGastoAplicable>' . $monto($invoice['MontoTotalDeGastoAplicable']) . '</MontoTotalDeGastoAplicable>';
            }
        }

        $xml .= '
  <TotalFactura>' . $monto($invoice['TotalFactura']) . '</TotalFactura>
  <NumeroCedulaReceptor>' . $cedula($this->Settings->cedula_emisor) . '</NumeroCedulaReceptor>
  <NumeroConsecutivoReceptor>' . $consecutive . '</NumeroConsecutivoReceptor>
</MensajeReceptor>';

        return array($xml, $consecutive, $mensaje, $tipoDocumento);
    }

    public function getNotaDebito($invoice, $itemsInvoices, $referencia, $otrostextos)
    {
        $this->load->model('customers_model');
        $this->load->model('hacienda_model');
        $receptor = $this->customers_model->getCustomerByID($invoice['customer_id']);
        $customer_id = $invoice['customer_id'];
        $CodActividad = $invoice['id_actividad'];
        $CondicionVenta = '';
        $PlazoCredito = '';

        if ($invoice['status'] == 'paid') {
            $CondicionVenta = '01';
        } else {
            $CondicionVenta = '02';
            // El plazo sale del cliente; 30 dias es lo que se declara cuando el
            // cliente no lo tiene definido.
            $PlazoCredito = plazo_credito_dias($receptor ?? null, 30) . ' dias';
        }

        // Las notas no tienen cobro propio; se declara Efectivo.
        $pagoNota = array('paid_by' => 'cash');

        // La fecha tiene que existir antes de la clave: generate_key() la incrusta
        // y sin ella la clave sale de 43 digitos en vez de 50.
        date_default_timezone_set('America/Costa_Rica');
        $fecha = date('Y-m-d\TH:i:s');

        $consecutivo = $this->hacienda_model->ccsctv_nd();
        $NumConse = $this->hacienda_model->ultimo_consecutivo('02', $consecutivo);

        $consecutive = $this->generate_consecutive($NumConse + 1, '02');

        $param = [$consecutive, $fecha];
        $key = $this->generate_key($param);

        $receptor->pre_id_number = $receptor->cf1;
        $receptor->id_number_proveedor = $receptor->cf2;
        $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));

        $tipo_receptor = $this->tipoDeReceptor($receptor, $identifivalid, false);
        // Misma regla que la nota de credito: la nota copia al receptor del original.
        $sireceptor = (strtolower(trim($receptor->name)) != "cliente de paso"
            && strtolower(trim($receptor->name)) != "cliente de contado"
            && ($tipo_receptor != '05' || $this->receptorExtranjero($receptor)));

        $invo = '<?xml version="1.0" encoding="UTF-8"?>
        <NotaDebitoElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaDebitoElectronica"
        xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaDebitoElectronica https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/NotaDebitoElectronica_V4.4.xsd"
        xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
        <Clave>' . $key . '</Clave>
        <ProveedorSistemas>' . $this->proveedorSistemas() . '</ProveedorSistemas>
        ' . $this->bloqueCodigoActividad($CodActividad, $sireceptor ? ($receptor->codigo_actividad ?? '') : '') . '
        <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
        <FechaEmision>' . $fecha . '</FechaEmision>
        <Emisor>
            <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
            <Identificacion>
                <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor)) . '</Numero>
            </Identificacion>
            <NombreComercial>' . $this->quitatilde(trim($this->Settings->nombre_comercial)) . '</NombreComercial>
            <Ubicacion>
                <Provincia>' . substr($this->Settings->cod_provincia, -2) . '</Provincia>
                <Canton>' . substr(trim($this->Settings->cod_canton), -2) . '</Canton>
                <Distrito>' . substr($this->Settings->cod_distrito, -2) . '</Distrito>
                ' . $this->bloqueBarrio($this->Settings->cod_barrio) . '
                <OtrasSenas>' . trim($this->Settings->otras_senas) . '</OtrasSenas>
            </Ubicacion>
            <Telefono>
                <CodigoPais>' . trim($this->Settings->cod_telefono_emisor) . '</CodigoPais>
                <NumTelefono>' . trim(str_replace("-", "", $this->Settings->telefono_emisor)) . '</NumTelefono>
            </Telefono>
            <CorreoElectronico>' . trim($this->Settings->email_emisor) . '</CorreoElectronico>
        </Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . trim($this->quitatilde($receptor->name)) . '</Nombre>
            <Identificacion>
                <Tipo>' . $tipo_receptor . '</Tipo>
                <Numero>' . $receptor->id_number_proveedor . '</Numero>
            </Identificacion>';

            if ($receptor->business_name) {
                $invo .= '<NombreComercial>' . $this->quitatilde($receptor->business_name) . '</NombreComercial>';
            }

            $invo .= $this->bloqueUbicacion($receptor, $tipo_receptor) . $this->bloqueSenasExtranjero($receptor, $tipo_receptor);

            if (strlen(trim(str_replace("-", "", $receptor->phone))) == 8) {
                $invo .= '
                <Telefono>
                    <CodigoPais>' . (preg_replace('/\D/', '', (string) ($receptor->cod_telefono ?? '')) ?: '506') . '</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $receptor->phone)) . '</NumTelefono>
                </Telefono>';
            }

            if ($receptor->email) {
                $invo .= '<CorreoElectronico>' . $receptor->email . '</CorreoElectronico>';
            }

            $invo .= '</Receptor>';
        }

        $invo .= '<CondicionVenta>' . $CondicionVenta . '</CondicionVenta>';

        if ($PlazoCredito) {
            $invo .= '<PlazoCredito>' . $PlazoCredito . '</PlazoCredito>';
        }

        $invo .= '<DetalleServicio>';

        $NumeroLinea = 0;
        $TotalServGravados = 0.0000;
        $TotalServExentos = 0.0000;
        $TotalServNoSujeto = 0.0000;
        $TotalMercanciasGravadas = 0.0000;
        $TotalMercanciasExentas = 0.0000;
        $TotalMercNoSujeta = 0.0000;
        $TotalGravado = 0.0000;
        $TotalExento = 0.0000;
        $TotalNoSujeto = 0.0000;
        $TotalVenta = 0.0000;
        $TotalDescuentos = 0.0000;
        $TotalVentaNeta = 0.0000;
        $TotalImpuesto = 0.0000;

        $itemsInvoices = $this->reClacDiscount($itemsInvoices, $invoice);

        // Impuesto acumulado por codigo y tarifa, para el desglose del resumen.
        $desgloseImp = array();
        foreach ($itemsInvoices as $items) {
            if (strpos($items["discount"], '%') === false) {
                $items["item_discount"] = $items["discount"];
            }

            $NumeroLinea++;
            $Cantidad = $items['quantity'];
            $Detalle = $items["product_name"];

            $row = $this->site->getProductByID($items['product_id']);
            $this->db->select("{$this->db->dbprefix('impuestos')}.*", FALSE);
            $id_tax_lookup4 = isset($row->id_tax) ? $row->id_tax : (isset($items['id_tax']) && $items['id_tax'] > 0 ? $items['id_tax'] : 8);
            $qq = $this->db->get_where('impuestos', array('id_impuesto' => $id_tax_lookup4), 1);
            if ($qq->num_rows() > 0) {
                $im = $qq->row();
                $items['codigo_impuesto'] = $im->codigo_impuesto;
                $items['codigo_tarifa']   = $im->codigo_tarifa;
            } else {
                $items['codigo_impuesto'] = '01';
                $items['codigo_tarifa']   = '08';
            }

            // Una linea de nota de debito casi nunca tiene ficha de producto:
            // Debitnotes::create la crea con product_id 0.
            if ($row && $row->tax_method == "1") {
                $PrecioUnitario = $items["real_unit_price"];
            } else {
                if (!$items['item_tax']) {
                    $PrecioUnitario = $items["real_unit_price"];
                } else {
                    $divisor = 1 + (floatval(str_replace('%', '', $items["tax"])) / 100);
                    $PrecioUnitario = number_format((float) ($items["real_unit_price"] / $divisor), 5, '.', '');
                }
            }

            $MontoTotal = $items["quantity"] * $PrecioUnitario;
            $MontoDescuento = 0;
            $NaturalezaDescuento = null;

            if ($items["discount"] > 0) {
                $MontoDescuento = $MontoTotal * (str_replace('%', '', $items["discount"]) / 100);
                $NaturalezaDescuento = $items["product_name"];
            }

            $SubTotal = $MontoTotal - $MontoDescuento;

            $ImpuestoCodigo = $items['codigo_impuesto'];
            $ImpuestoTarifa = (float) str_replace('%', '', (string) ($items["tax"] ?? 0));
            $TarifaCodigo   = $this->codigoTarifa($ImpuestoTarifa, $items['codigo_tarifa']);
            $ImpuestoMonto  = 0;
            $ImpuestoNeto   = 0;
            if ($TarifaCodigo != '01') {
                $ImpuestoMonto = number_format((float) ($SubTotal * floatval($ImpuestoTarifa) / 100), 5, '.', '');
                $ImpuestoNeto  = $ImpuestoMonto;
            }
            $MontoTotalLinea = number_format((float) ($SubTotal), 5, '.', '') + number_format((float) ($ImpuestoNeto), 5, '.', '');

            $invo .= '<LineaDetalle>
                        <NumeroLinea>' . $NumeroLinea . '</NumeroLinea>
                        ' . $this->bloqueCodigoCabys(is_object($row) ? ($row->cabys ?? '') : '', $items['cabys'] ?? '', $items['product_code'] ?? '') . '
                        <CodigoComercial>
                            <Tipo>03</Tipo>
                            <Codigo>' . substr($items["product_code"], 0, 19) . '</Codigo>
                        </CodigoComercial>'
                        . '<Cantidad>' . $Cantidad . '</Cantidad>
                        <UnidadMedida>' . $this->unidadMedida($items["unit_of_measurement"] ?? '') . '</UnidadMedida>
                        <Detalle>' . substr($this->quitatilde($Detalle), 0, 80) . '</Detalle>
                        <PrecioUnitario>' . number_format((float) ($PrecioUnitario), 5, '.', '') . '</PrecioUnitario>
                        <MontoTotal>' . number_format((float) ($MontoTotal), 5, '.', '') . '</MontoTotal>';

            if ($MontoDescuento > 0) {
                $invo .= '<Descuento>
                            <MontoDescuento>' . number_format((float) ($MontoDescuento), 5, '.', '') . '</MontoDescuento>
                            <NaturalezaDescuento>' . substr($this->quitatilde($NaturalezaDescuento), 0, 80) . '</NaturalezaDescuento>
                          </Descuento>';
            }

            $invo .= '<SubTotal>' . number_format((float) ($SubTotal), 5, '.', '') . '</SubTotal>';
                $invo .= '<BaseImponible>' . number_format((float) ($SubTotal), 5, '.', '') . '</BaseImponible>';

            // Impuesto es obligatorio en cada linea desde la v4.4: una linea
            // exenta lo declara con tarifa 0% (codigo 01) y monto en cero.
            $exenta = ($TarifaCodigo == '01');
            $invo .= '<Impuesto>
                            <Codigo>' . ($exenta ? '01' : $ImpuestoCodigo) . '</Codigo>
                            <CodigoTarifaIVA>' . ($exenta ? '01' : $TarifaCodigo) . '</CodigoTarifaIVA>
                            <Tarifa>' . ($exenta ? '0.00000' : $ImpuestoTarifa) . '</Tarifa>
                            <Monto>' . number_format((float) ($exenta ? 0 : $ImpuestoMonto), 5, '.', '') . '</Monto>
                        </Impuesto>';
                $llaveImp = ($exenta ? '01' : $ImpuestoCodigo) . '|' . ($exenta ? '01' : $TarifaCodigo);
                $desgloseImp[$llaveImp] = ($desgloseImp[$llaveImp] ?? 0) + ($exenta ? 0 : (float) $ImpuestoMonto);


            // Los dos son obligatorios en la v4.4 aunque la linea no lleve impuesto.
            $invo .= '<ImpuestoAsumidoEmisorFabrica>0.00000</ImpuestoAsumidoEmisorFabrica>';
            $invo .= '<ImpuestoNeto>' . number_format((float) ($ImpuestoNeto), 5, '.', '') . '</ImpuestoNeto>';

            $invo .= '<MontoTotalLinea>' . number_format((float) ($MontoTotalLinea), 5, '.', '') . '</MontoTotalLinea>
                    </LineaDetalle>';

            $natural = $this->naturalezaLinea($TarifaCodigo);

            if ($this->esServicio($this->cabysDeLinea($row, $items), $items['type'] ?? '')) {
                if ($natural === 'nosujeto') {
                    $TotalServNoSujeto += $MontoTotal;
                    $TotalNoSujeto += $MontoTotal;
                } elseif ($natural === 'exento') {
                    $TotalServExentos += $MontoTotal;
                    $TotalExento += $MontoTotal;
                } else {
                    $TotalServGravados += $MontoTotal;
                    $TotalGravado += $MontoTotal;
                }
            } else {
                if ($natural === 'nosujeto') {
                    $TotalMercNoSujeta += $MontoTotal;
                    $TotalNoSujeto += $MontoTotal;
                } elseif ($natural === 'exento') {
                    $TotalMercanciasExentas += $MontoTotal;
                    $TotalExento += $MontoTotal;
                } else {
                    $TotalMercanciasGravadas += $MontoTotal;
                    $TotalGravado += $MontoTotal;
                }
            }

            $TotalVenta += $MontoTotal;
            $TotalVentaNeta += $SubTotal;
            $TotalImpuesto += $ImpuestoNeto;
            $TotalDescuentos += $MontoDescuento;
        }
        $TotalComprobante = $TotalVentaNeta + $TotalImpuesto;

        $invo .= '</DetalleServicio>
        <ResumenFactura>
          <CodigoTipoMoneda>
            <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
            <TipoCambio>' . $this->tipoCambio() . '</TipoCambio>
          </CodigoTipoMoneda>
          <TotalServGravados>' . number_format((float) ($TotalServGravados), 5, '.', '') . '</TotalServGravados>
          <TotalServExentos>' . number_format((float) ($TotalServExentos), 5, '.', '') . '</TotalServExentos>
          <TotalMercanciasGravadas>' . number_format((float) ($TotalMercanciasGravadas), 5, '.', '') . '</TotalMercanciasGravadas>
          <TotalMercanciasExentas>' . number_format((float) ($TotalMercanciasExentas), 5, '.', '') . '</TotalMercanciasExentas>
          <TotalGravado>' . number_format((float) ($TotalGravado), 5, '.', '') . '</TotalGravado>
          <TotalExento>' . number_format((float) ($TotalExento), 5, '.', '') . '</TotalExento>
          <TotalVenta>' . number_format((float) ($TotalVenta), 5, '.', '') . '</TotalVenta>
          <TotalDescuentos>' . number_format((float) ($TotalDescuentos), 5, '.', '') . '</TotalDescuentos>
          <TotalVentaNeta>' . number_format((float) ($TotalVentaNeta), 5, '.', '') . '</TotalVentaNeta>
          ' . $this->bloqueDesgloseImpuesto($desgloseImp) . '<TotalImpuesto>' . number_format((float) ($TotalImpuesto), 5, '.', '') . '</TotalImpuesto>
          <!--NX_MEDIOPAGO--><TotalComprobante>' . number_format((float) ($TotalComprobante), 5, '.', '') . '</TotalComprobante>
        </ResumenFactura>
        <InformacionReferencia>
          <TipoDocIR>' . $this->tipoDocReferencia($referencia) . '</TipoDocIR>
          <Numero>' . $referencia->clave . '</Numero>
          <FechaEmisionIR>' . $this->fechaIso($referencia->fecha_emision) . '</FechaEmisionIR>
          <Codigo>' . codigo_referencia_valido($invoice['motivo_nd'] ?? '04', '04') . '</Codigo>
          <Razon>' . $this->quitatilde(mb_substr(trim((string) ($invoice['hold_ref'] ?? '')), 0, 180)) . '</Razon>
        </InformacionReferencia>';

        if ($otrostextos) {
            $invo .= '<Otros>';
            foreach ($otrostextos as $texto) {
                $texto = (array) $texto;
                $invo .= '<OtroTexto codigo="' . $texto['titulo_texto'] . '" >' . $texto['otrotexto'] . '</OtroTexto>';
            }
            $invo .= '</Otros>';
        }

        $invo .= '    </NotaDebitoElectronica>';

        // <MedioPago> va antes del detalle pero depende de TotalComprobante:
        // se emitio como marcador y se resuelve aca.
        $invo = str_replace('<!--NX_MEDIOPAGO-->', $this->bloqueMedioPago(isset($payment) ? $payment : (isset($pagoNota) ? $pagoNota : null), $TotalComprobante), $invo);
        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha];
    }

    public function getREP($payment, $sale, $referencia)
    {
        $this->load->model('customers_model');
        $this->load->model('hacienda_model');
        $receptor = $this->customers_model->getCustomerByID($sale['customer_id']);
        $customer_id = $sale['customer_id'];
        $CodActividad = $sale['id_actividad'];

        // La fecha tiene que existir antes de la clave: generate_key() la incrusta
        // y sin ella la clave sale de 43 digitos en vez de 50.
        date_default_timezone_set('America/Costa_Rica');
        $fecha = date('Y-m-d\TH:i:s');

        // El recibo electronico de pago es el tipo 10; el 09 es la factura de
        // exportacion. Como el consecutivo va dentro de la clave, el codigo
        // equivocado hacia rechazable todo REP emitido.
        $consecutivo = $this->hacienda_model->ccsctv_rep();
        $NumConse = $this->hacienda_model->ultimo_consecutivo('10', $consecutivo);
        $consecutive = $this->generate_consecutive($NumConse + 1, '10');

        $param = [$consecutive, $fecha];
        $key   = $this->generate_key($param);

        $receptor->pre_id_number      = $receptor->cf1;
        $receptor->id_number_proveedor = $receptor->cf2;
        $identifivalid = str_replace('-', '', trim($receptor->id_number_proveedor));

        $tipo_receptor = '05';
        $tipo_receptor = $this->tipoDeReceptor($receptor, $identifivalid, false);
        // El REP rechaza el codigo 05 (Anexos v4.4, nota 4 pie 16) y su <Receptor>
        // no es opcional: sin identificacion valida el comprobante no se sostiene.
        $sireceptor = ($customer_id != $this->_cliente_de_paso()
            && $tipo_receptor != '05'
            && strtolower(trim($receptor->name)) != "cliente de paso"
            && strtolower(trim($receptor->name)) != "cliente de contado");

        if (!$sireceptor) {
            log_message('error', 'Crearxml: REP sin receptor declarable para el cliente '
                . $customer_id . '. Hacienda lo rechaza: el pago debe cobrarse a un cliente '
                . 'con identificacion valida de los tipos 01 a 04.');
        }

        // El medio de pago se arma en bloqueMedioPago() a partir de $payment.

        $invo = '<?xml version="1.0" encoding="UTF-8"?>
        <ReciboElectronicoPago
        xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/reciboElectronicoPago"
        xmlns:xsd="http://www.w3.org/2001/XMLSchema"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/reciboElectronicoPago https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/ReciboElectronicoPago_V4.4.xsd">
        <Clave>' . $key . '</Clave>
        <ProveedorSistemas>' . $this->proveedorSistemas() . '</ProveedorSistemas>
        <NumeroConsecutivo>' . $consecutive . '</NumeroConsecutivo>
        <FechaEmision>' . $fecha . '</FechaEmision>
        <Emisor>
            <Nombre>' . $this->quitatilde(trim($this->Settings->nombre_emisor)) . '</Nombre>
            <Identificacion>
                <Tipo>' . $this->Settings->tipo_doc_emisor . '</Tipo>
                <Numero>' . trim(str_replace("-", "", $this->Settings->cedula_emisor)) . '</Numero>
            </Identificacion>
            <NombreComercial>' . $this->quitatilde(trim($this->Settings->nombre_comercial)) . '</NombreComercial>
            <Ubicacion>
                <Provincia>' . substr($this->Settings->cod_provincia, -2) . '</Provincia>
                <Canton>' . substr(trim($this->Settings->cod_canton), -2) . '</Canton>
                <Distrito>' . substr($this->Settings->cod_distrito, -2) . '</Distrito>
                ' . $this->bloqueBarrio($this->Settings->cod_barrio) . '
                <OtrasSenas>' . trim($this->Settings->otras_senas) . '</OtrasSenas>
            </Ubicacion>
            <Telefono>
                <CodigoPais>' . trim($this->Settings->cod_telefono_emisor) . '</CodigoPais>
                <NumTelefono>' . trim(str_replace("-", "", $this->Settings->telefono_emisor)) . '</NumTelefono>
            </Telefono>
            <CorreoElectronico>' . trim($this->Settings->email_emisor) . '</CorreoElectronico>
        </Emisor>';

        if ($sireceptor) {
            $invo .= '<Receptor>
            <Nombre>' . trim($this->quitatilde($receptor->name)) . '</Nombre>
            <Identificacion>
                <Tipo>' . $tipo_receptor . '</Tipo>
                <Numero>' . $receptor->id_number_proveedor . '</Numero>
            </Identificacion>';

            if ($receptor->business_name) {
                $invo .= '<NombreComercial>' . $this->quitatilde($receptor->business_name) . '</NombreComercial>';
            }

            $invo .= $this->bloqueUbicacion($receptor, $tipo_receptor) . $this->bloqueSenasExtranjero($receptor, $tipo_receptor);

            if (strlen(trim(str_replace("-", "", $receptor->phone))) == 8) {
                $invo .= '<Telefono>
                    <CodigoPais>' . (preg_replace('/\D/', '', (string) ($receptor->cod_telefono ?? '')) ?: '506') . '</CodigoPais>
                    <NumTelefono>' . trim(str_replace("-", "", $receptor->phone)) . '</NumTelefono>
                </Telefono>';
            }

            if ($receptor->email) {
                $invo .= '<CorreoElectronico>' . $receptor->email . '</CorreoElectronico>';
            }

            $invo .= '</Receptor>';
        }

        $invo .= '<CondicionVenta>02</CondicionVenta>
        <InformacionReferencia>
            <TipoDocIR>' . str_pad($referencia->tipo_doc, 2, "0", STR_PAD_LEFT) . '</TipoDocIR>
            <Numero>' . $referencia->clave . '</Numero>
            <FechaEmisionIR>' . $this->fechaIso($referencia->fecha_emision) . '</FechaEmisionIR>
            <Codigo>03</Codigo>
            <Razon>Pago de factura</Razon>
        </InformacionReferencia>
        <ResumenFactura>
            <CodigoTipoMoneda>
                <CodigoMoneda>' . $this->Settings->currency_prefix . '</CodigoMoneda>
                <TipoCambio>' . $this->tipoCambio() . '</TipoCambio>
            </CodigoTipoMoneda>
            <!--NX_MEDIOPAGO--><TotalComprobante>' . number_format((float)$payment['amount'], 5, '.', '') . '</TotalComprobante>
        </ResumenFactura>
        </ReciboElectronicoPago>';

        // <MedioPago> va antes del detalle pero depende de TotalComprobante:
        // se emitio como marcador y se resuelve aca. En el recibo de pago el
        // total es el monto cobrado, y la suma de los medios tiene que darlo
        // exacto o Hacienda rechaza el comprobante (Anexos v4.4, nota 6).
        $TotalComprobante = (float) $payment['amount'];
        $invo = str_replace('<!--NX_MEDIOPAGO-->', $this->bloqueMedioPago($payment, $TotalComprobante), $invo);
        return ['xml' => $invo, 'clave' => $key, 'consecutivo' => $consecutive, 'fecha_emision' => $fecha];
    }

    /**
     * Clave de 50 digitos: pais 3 + fecha 6 + cedula 12 + consecutivo 20 +
     * situacion 1 + seguridad 8 (Anexos v4.4, nota 1).
     */
    /**
     * Clave de 50 posiciones del comprobante.
     *
     * @param array $param [0] consecutivo, [1] fecha de emision, [2] situacion:
     *                     1 normal, 2 contingencia, 3 sin internet (posicion 42
     *                     de la clave, anexo v4.4).
     */
    public function generate_key($param = "")
    {
        // La fecha llega como '2026-08-24T10:15:00' o '2026-08-24 10:15:00'.
        $fecha = preg_split('/[ T]/', (string) $param[1]);
        $fecha = explode("-", $fecha[0]);
        $cod_pais = "506";
        $dia = $fecha[2];
        $mes = $fecha[1];
		$year = substr( $fecha[0], -2);
        $situacion = (isset($param[2]) && in_array((string) $param[2], array('1', '2', '3'), true))
            ? (string) $param[2] : "1";
        $cedula = str_pad($this->Settings->cedula_emisor, 12, "0", STR_PAD_LEFT);
        $seguridad = $this->Settings->telefono_emisor;
        $clave = $cod_pais . $dia . $mes . $year . $cedula . $param[0] . $situacion . $seguridad;

        // Una clave corta la rechaza Hacienda entera, y sin este aviso el
        // comprobante sale igual: el fallo solo se nota semanas despues.
        if (strlen($clave) !== 50) {
            log_message('error', 'Crearxml: la clave quedo de ' . strlen($clave)
                . ' digitos en vez de 50. Revisar casa matriz, terminal, cedula del emisor'
                . ' y codigo de seguridad en Ajustes.');
        }

        return $clave;
    }

    public function generate_consecutive($consecutivo, $tdoc = "01")
    {
        // 20 posiciones fijas: casa matriz 3 + terminal 5 + tipo 2 + numero 10.
        // Un ajuste vacio recorta el consecutivo y arrastra la clave con el.
        $numCompElectronico = str_pad($consecutivo, 10, "0", STR_PAD_LEFT);
        $cmatriz = str_pad((string) ($this->Settings->casa_matriz ?? ''), 3, "0", STR_PAD_LEFT);
        $tPOS = str_pad((string) ($this->Settings->terminal_pos ?? ''), 5, "0", STR_PAD_LEFT);
        $tDocumento = $tdoc;
        return $cmatriz . $tPOS . $tDocumento . $numCompElectronico;
    }

    public function generate_consecutive_fec($consecutivo, $tdoc = "08")
    {
        // 20 posiciones fijas: casa matriz 3 + terminal 5 + tipo 2 + numero 10.
        // Un ajuste vacio recorta el consecutivo y arrastra la clave con el.
        $numCompElectronico = str_pad($consecutivo, 10, "0", STR_PAD_LEFT);
        $cmatriz = str_pad((string) ($this->Settings->casa_matriz ?? ''), 3, "0", STR_PAD_LEFT);
        $tPOS = str_pad((string) ($this->Settings->terminal_pos ?? ''), 5, "0", STR_PAD_LEFT);
        $tDocumento = $tdoc;
        return $cmatriz . $tPOS . $tDocumento . $numCompElectronico;
    }

    /** Letras acentuadas y su equivalente sin acento, respetando mayusculas. */
    private const SIN_ACENTO = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A',
        'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ð' => 'D', 'Ñ' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ý' => 'Y', 'Þ' => 'B', 'Ŕ' => 'R',
        'ß' => 's',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a',
        'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd', 'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y', 'þ' => 'b', 'ŕ' => 'r',
    ];

    /**
     * Normaliza el texto que va al comprobante: saca tildes, cambia "&" por "y"
     * y quita lo que rompe el XML.
     *
     * El mapa es UTF-8 directo. Pasar por utf8_decode() no solo esta deprecado
     * (y desaparece en PHP 9): convertia en "r" cualquier caracter fuera de
     * Latin-1 y devolvia las mayusculas acentuadas en minuscula.
     */
    public function quitatilde($cadena)
    {
        $cadena = str_replace('&', 'y', (string) $cadena);
        $cadena = strtr($cadena, self::SIN_ACENTO);

        return str_replace(['<', '>', '"', "\r", "\n"], ['', '', '', ' ', ' '], $cadena);
    }

    public function reClacDiscount($itemsInvoices, $invoice)
    {


        /*
         * real_unit_price =  es el precio real del articulo
         * unit_price = real_unit_price - item_discount
         * net_unit_price = unit_price - item_tax
         * discount = al porcentaje de descuento
         * item_discount = el monto descontado
         * tax = porcentaje de descuento
         * item_tax = net_unit_price * tax / 100
         */

        if ($itemsInvoices) {
            foreach ($itemsInvoices as $row) {
                if ($row["discount"] != "0") {

                    if (strpos($row["discount"], '%') !== false) {
                        $rate_discount = $row["discount"];
                        $PorcentDiscount = (int) str_replace("%", "", $row["discount"]);
                    } else {
                        $t = $row["real_unit_price"] * $row["quantity"];
                        $tdd = $row["item_discount"];
                        $PorcentDiscount = $tdd * 100 / $t;
                        $rate_discount = $PorcentDiscount . '%';
                    }

                    $calc_discount = ($PorcentDiscount / 100);
                    $monto_impuesto = 0;
                    // Sin reiniciarlos, una linea exenta hereda la tarifa de la
                    // linea anterior y sale con impuesto.
                    $taxrate = 0;
                    $calc_inverse_tax = 1;
                    $calc_tax = 0;
                    if ($row['tax'] != "0") {
                        $taxrate = (int) str_replace("%", "", $row['tax']);
                        $calc_inverse_tax = ($taxrate / 100) + 1;
                        $calc_tax = ($taxrate / 100);
                    }

                    $item_discount = $row["real_unit_price"] * $calc_discount;
                    $unit_price = $row["real_unit_price"] - $item_discount;
                    $tax = $taxrate;
                    $item_tax = $unit_price * $calc_tax;
                    $net_unit_price = $unit_price - $item_tax;
                    $discount = $rate_discount;
                    $subtotal = $unit_price * $row["quantity"];


                    $row["item_discount"] = $item_discount;
                    $row["unit_price"] = $unit_price;
                    $row["net_unit_price"] = $net_unit_price;
                    $row["tax"] = $tax;
                    $row["item_tax"] = $item_tax;
                    $row["discount"] = $discount;
                    $row["subtotal"] = $subtotal;
                }
            }
        }
        /*
         * real_unit_price =  es el precio real del articulo
         * unit_price = real_unit_price - item_discount
         * net_unit_price = unit_price - item_tax
         * discount = al porcentaje de descuento
         * item_discount = el monto descontado
         * tax = porcentaje de descuento  
         * item_tax = net_unit_price * tax / 100
         */
        $order_discount_id = $invoice['order_discount_id'];
        if ($order_discount_id) {

            if (strpos($order_discount_id, '%') !== false) {
                $PorcentDiscount = (int) str_replace("%", "", $order_discount_id);
                $rate_discount = $order_discount_id;
            } else {
                $t = $invoice['grand_total'] + $invoice['order_discount_id'];
                $tdd = $order_discount_id;
                $PorcentDiscount = $tdd * 100 / $t;
                $rate_discount = $PorcentDiscount . '%';
            }

            $calc_discount = ($PorcentDiscount / 100);


            foreach ($itemsInvoices as &$row) {
                if ($row["discount"] != "0") {

                    $otrodescuento = ($row["subtotal"] * $calc_discount) / $row["quantity"];
                    $precioInicial = $row["real_unit_price"];
                    $precioFinal = ($row["subtotal"] / $row["quantity"]) - $otrodescuento;

                    $PorcentDiscount = 100 - (($precioFinal * 100) / $precioInicial);
                    $rate_discount = $PorcentDiscount . '%';
                    $calc_discount = ($PorcentDiscount / 100);
                }


                $monto_impuesto = 0;
                if ($row['tax'] != "0") {
                    $taxrate = (int) str_replace("%", "", $row['tax']);
                    $calc_inverse_tax = ($taxrate / 100) + 1;
                    $calc_tax = ($taxrate / 100);
                }

                $monto_impuesto = 0;
                if ($row['tax'] != "0") {
                    $taxrate = (int) str_replace("%", "", $row['tax']);
                    $calc_inverse_tax = ($taxrate / 100) + 1;
                    $calc_tax = ($taxrate / 100);
                }

                $item_discount = $row["real_unit_price"] * $calc_discount;
                $unit_price = $row["real_unit_price"] - $item_discount;
                $tax = $taxrate;
                $item_tax = $unit_price * $calc_tax;
                $net_unit_price = $unit_price - $item_tax;
                $discount = $rate_discount;
                $subtotal = $unit_price * $row["quantity"];


                $row["item_discount"] = $item_discount;
                $row["unit_price"] = $unit_price;
                $row["net_unit_price"] = $net_unit_price;
                $row["tax"] = $tax;
                $row["item_tax"] = $item_tax;
                $row["discount"] = $discount;
                $row["subtotal"] = $subtotal;
            }
        }

        return $itemsInvoices;
    }

    public function tofloat($num)
    {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        }

        return floatval(
            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
                preg_replace("/[^0-9]/", "", substr($num, $sep + 1, strlen($num)))
        );
    }
}




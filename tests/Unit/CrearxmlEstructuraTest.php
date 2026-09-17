<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/stubs/CrearxmlEntorno.php';

/**
 * El XML generado se valida contra los XSD oficiales de Hacienda versionados en
 * files/docs-hacienda/xsd/v4.4.
 *
 * El generador entrega el comprobante sin firmar y `ds:Signature` es obligatoria
 * en el esquema; la firma la agrega Firmadocr después. Para no tener que
 * descontar ese error a mano —libxml no siempre lo nombra igual— se valida
 * contra una copia del esquema con la firma en minOccurs="0". Cualquier error
 * que quede es un fallo real de estructura.
 */
class CrearxmlEstructuraTest extends TestCase
{
    private const XSD = __DIR__ . '/../../files/docs-hacienda/xsd/v4.4';

    /** Errores de esquema del comprobante sin firmar. */
    private function erroresDeEsquema(string $xml, string $esquema): array
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $doc = new DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'El XML generado no está bien formado');

        $doc->schemaValidate($this->esquemaSinFirma($esquema));

        $errores = [];
        foreach (libxml_get_errors() as $e) {
            $errores[trim(preg_replace('/\s+/', ' ', $e->message))] = true;
        }
        libxml_clear_errors();

        return array_keys($errores);
    }

    /**
     * Copia del XSD con ds:Signature opcional, en un directorio temporal que
     * respeta la profundidad del original para que su import relativo de
     * xmldsig-core-schema.xsd siga resolviendo.
     */
    private function esquemaSinFirma(string $esquema): string
    {
        $base = sys_get_temp_dir() . '/nx-xsd-sin-firma';
        $destino = $base . '/v4.4/' . basename(dirname($esquema)) . '/' . basename($esquema);

        if (!is_file($destino)) {
            @mkdir(dirname($destino), 0777, true);
            copy(
                dirname($esquema, 3) . '/xmldsig-core-schema.xsd',
                $base . '/xmldsig-core-schema.xsd'
            );
            file_put_contents($destino, str_replace(
                '<xs:element ref="ds:Signature" minOccurs="1"',
                '<xs:element ref="ds:Signature" minOccurs="0"',
                file_get_contents($esquema)
            ));
        }

        return $destino;
    }

    public function testElTiqueteValidaContraElEsquemaOficial(): void
    {
        $xml = CrearxmlEntorno::preparar()->getInvoice(
            CrearxmlEntorno::venta(),
            [CrearxmlEntorno::item()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        );

        $this->assertSame('04', $xml['tipo_doc'], 'Un cliente sin identificar debe salir como tiquete');
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml['xml'], self::XSD . '/tiqueteElectronico/TiqueteElectronico_V4.4.xsd')
        );
    }

    public function testLaFacturaValidaContraElEsquemaOficial(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $xml = $crearxml->getInvoice($venta, [CrearxmlEntorno::item()], ['amount' => 2260, 'paid_by' => 'cash'], []);

        $this->assertSame('01', $xml['tipo_doc'], 'Un cliente identificado debe salir como factura');
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml['xml'], self::XSD . '/facturaElectronica/FacturaElectronica_V4.4.xsd')
        );
    }

    public function testLaClaveMideCincuentaYElConsecutivoVeinte(): void
    {
        $xml = CrearxmlEntorno::preparar()->getInvoice(
            CrearxmlEntorno::venta(),
            [CrearxmlEntorno::item()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        );

        $this->assertSame(50, strlen($xml['clave']));
        $this->assertSame(20, strlen($xml['consecutivo']));
        $this->assertMatchesRegularExpression('/^\d{50}$/', $xml['clave']);
    }

    /**
     * Sin casa matriz ni terminal configuradas el consecutivo salía de 12
     * posiciones y arrastraba la clave a 42: Hacienda rechaza el comprobante.
     */
    public function testUnAjusteVacioNoAcortaLaClave(): void
    {
        $xml = CrearxmlEntorno::preparar(['casa_matriz' => '', 'terminal_pos' => ''])->getInvoice(
            CrearxmlEntorno::venta(),
            [CrearxmlEntorno::item()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        );

        $this->assertSame(50, strlen($xml['clave']));
        $this->assertSame(20, strlen($xml['consecutivo']));
        $this->assertSame([], CrearxmlEntorno::$avisos, 'El relleno debe evitar el aviso de clave corta');
    }

    public function testElEncabezadoLlevaLosCamposNuevosDeLaV44(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $xml = $crearxml->getInvoice($venta, [CrearxmlEntorno::item()], ['amount' => 2260, 'paid_by' => 'cash'], [])['xml'];

        $this->assertStringContainsString('<ProveedorSistemas>3101123456</ProveedorSistemas>', $xml);
        $this->assertStringContainsString('<CodigoActividadEmisor>620200</CodigoActividadEmisor>', $xml);
        $this->assertStringContainsString('<CodigoActividadReceptor>471100</CodigoActividadReceptor>', $xml);
        $this->assertStringNotContainsString('<CodigoActividad>', $xml, 'La v4.4 ya no define CodigoActividad');
    }

    public function testElTiqueteNoLlevaCodigoDeActividadDelReceptor(): void
    {
        $xml = CrearxmlEntorno::preparar()->getInvoice(
            CrearxmlEntorno::venta(),
            [CrearxmlEntorno::item()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringNotContainsString('CodigoActividadReceptor', $xml);
        $this->assertStringContainsString('<CodigoActividadEmisor>620200</CodigoActividadEmisor>', $xml);
    }

    /** Hacienda rechaza con -405 si la fecha de la clave no es la de FechaEmision. */
    public function testLaFechaDeLaClaveEsLaDeEmision(): void
    {
        $venta = CrearxmlEntorno::venta();
        $venta['date'] = '2020-01-02 08:00:00';  // venta vieja: la clave no debe copiarla

        $xml = CrearxmlEntorno::preparar()->getInvoice(
            $venta,
            [CrearxmlEntorno::item()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        );

        $this->assertSame(50, strlen($xml['clave']));
        $this->assertMatchesRegularExpression(
            '#<FechaEmision>' . preg_quote(substr($xml['fecha_emision'], 0, 10), '#') . '#',
            $xml['xml']
        );

        // Clave: 506 + ddmmaa + ...
        $ddmmaa = substr($xml['clave'], 3, 6);
        $this->assertSame(date('dmy', strtotime($xml['fecha_emision'])), $ddmmaa);
    }

    /** Una linea sin unidad rompe la enumeracion del XSD: debe caer en 'Unid'. */
    public function testUnaLineaSinUnidadDeMedidaNoQuedaVacia(): void
    {
        $item = CrearxmlEntorno::item();
        $item['unit_of_measurement'] = '';

        $xml = CrearxmlEntorno::preparar()->getInvoice(
            CrearxmlEntorno::venta(),
            [$item],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringNotContainsString('<UnidadMedida></UnidadMedida>', $xml);
        $this->assertStringContainsString('<UnidadMedida>Unid</UnidadMedida>', $xml);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/tiqueteElectronico/TiqueteElectronico_V4.4.xsd')
        );
    }

    /** El resumen debe desglosar el impuesto o Hacienda rechaza con -487. */
    public function testElResumenDesglosaElImpuesto(): void
    {
        $xml = CrearxmlEntorno::preparar()->getInvoice(
            CrearxmlEntorno::venta(),
            [CrearxmlEntorno::item()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringContainsString('<TotalDesgloseImpuesto>', $xml);
        $this->assertStringContainsString('<CodigoTarifaIVA>08</CodigoTarifaIVA>', $xml);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/tiqueteElectronico/TiqueteElectronico_V4.4.xsd')
        );
    }

    /** La v4.4 volvio obligatorio <Impuesto> en toda linea, tambien en las exentas. */
    public function testLaLineaExentaDeclaraImpuestoConTarifaCero(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->db->filas['impuestos'] = [
            (object) ['id_impuesto' => 8, 'codigo_impuesto' => '01', 'codigo_tarifa' => '01']
        ];

        $item = CrearxmlEntorno::item();
        $item['tax'] = '0%';
        $item['item_tax'] = 0;
        $item['unit_price'] = 1000;
        $item['subtotal'] = 2000;

        $xml = $crearxml->getInvoice(
            CrearxmlEntorno::venta(),
            [$item],
            ['amount' => 2000, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringContainsString('<CodigoTarifaIVA>01</CodigoTarifaIVA>', $xml);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/tiqueteElectronico/TiqueteElectronico_V4.4.xsd')
        );
    }

    /** TipoCambio es obligatorio y decimal: sin el ajuste debe caer en 1, no en vacio. */
    public function testSinAjusteDeTipoDeCambioSeDeclaraUno(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        unset(CrearxmlEntorno::$ci->Settings->value_changue);

        $xml = $crearxml->getInvoice(
            CrearxmlEntorno::venta(),
            [CrearxmlEntorno::item()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringContainsString('<TipoCambio>1.00000</TipoCambio>', $xml);
        $this->assertStringNotContainsString('<TipoCambio></TipoCambio>', $xml);
    }

    /** Hacienda entrega codigos como "4711.2": el punto cuenta y son 6 caracteres. */
    public function testElCodigoDeActividadConservaElPunto(): void
    {
        $venta = CrearxmlEntorno::venta();
        $venta['id_actividad'] = '4711.2';

        $xml = CrearxmlEntorno::preparar()->getInvoice(
            $venta,
            [CrearxmlEntorno::item()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringContainsString('<CodigoActividadEmisor>4711.2</CodigoActividadEmisor>', $xml);
    }

    /** Receptor/Ubicacion es opcional, pero completo tiene que validar. */
    public function testLaUbicacionDelReceptorValidaContraElEsquema(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::clienteConUbicacion();
        CrearxmlEntorno::$ci->db->filas['barrio_cr'] = [(object) ['nombre_barrio' => 'Amon Centro']];

        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $xml = $crearxml->getInvoice($venta, [CrearxmlEntorno::item()], ['amount' => 2260, 'paid_by' => 'cash'], [])['xml'];

        // El emisor tambien lleva Ubicacion: la del receptor es la segunda.
        $this->assertSame(2, substr_count($xml, '<Ubicacion>'));
        $this->assertStringContainsString('<Barrio>Amon Centro</Barrio>', $xml);
        $this->assertStringContainsString('<OtrasSenas>Del parque 200 metros al sur, local 3</OtrasSenas>', $xml);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/facturaElectronica/FacturaElectronica_V4.4.xsd')
        );
    }

    /** Sin direccion exacta el nodo entero se omite: incompleto Hacienda lo rechaza. */
    public function testSinOtrasSenasNoSeEmiteLaUbicacionDelReceptor(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        $cliente = CrearxmlEntorno::clienteConUbicacion();
        $cliente->otras_senas = '';
        CrearxmlEntorno::$ci->customers_model->cliente = $cliente;

        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $xml = $crearxml->getInvoice($venta, [CrearxmlEntorno::item()], ['amount' => 2260, 'paid_by' => 'cash'], [])['xml'];

        $this->assertSame(1, substr_count($xml, '<Ubicacion>'), 'Solo debe quedar la ubicacion del emisor');
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/facturaElectronica/FacturaElectronica_V4.4.xsd')
        );
    }

    /**
     * El codigo 05 en el receptor de una factura exige condicion de venta 12, que
     * el POS no maneja, pero el anexo si lo admite en el tiquete: el extranjero
     * queda identificado en vez de perderse (Anexos v4.4, nota 4 pie 16).
     *
     * Su direccion no va en <Ubicacion> —no aplica a ese tipo— sino en
     * OtrasSenasExtranjero.
     */
    public function testElExtranjeroSaleComoReceptorDelTiquete(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::clienteExtranjero();

        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $salida = $crearxml->getInvoice($venta, [CrearxmlEntorno::item()], ['amount' => 2260, 'paid_by' => 'cash'], []);

        $this->assertSame('04', $salida['tipo_doc'], 'El tipo 05 no puede salir como factura');
        $this->assertStringContainsString('<Receptor>', $salida['xml']);
        $this->assertStringContainsString('<Tipo>05</Tipo>', $salida['xml']);
        $this->assertStringContainsString('<Numero>AB123456789</Numero>', $salida['xml']);
        $this->assertStringContainsString('<OtrasSenasExtranjero>1200 Brickell Ave, Miami FL</OtrasSenasExtranjero>', $salida['xml']);
        // La unica <Ubicacion> que queda es la del emisor.
        $this->assertSame(1, substr_count($salida['xml'], '<Ubicacion>'));
        // El esquema del tiquete no tiene CodigoActividadReceptor.
        $this->assertStringNotContainsString('CodigoActividadReceptor', $salida['xml']);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/tiqueteElectronico/TiqueteElectronico_V4.4.xsd')
        );
    }

    /**
     * Al codigo 05 tambien se llega por descarte cuando una cedula de Costa Rica
     * no calza en longitud. Ese es un dato malo, no un extranjero: el comprobante
     * sigue saliendo como tiquete anonimo.
     */
    public function testUnaCedulaMalDigitadaNoSeDeclaraComoExtranjera(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        $cliente = CrearxmlEntorno::cliente();
        $cliente->cf1 = '01';
        $cliente->cf2 = '1234';            // una cedula fisica son 9 digitos
        $cliente->id_number_proveedor = '1234';
        CrearxmlEntorno::$ci->customers_model->cliente = $cliente;

        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $salida = $crearxml->getInvoice($venta, [CrearxmlEntorno::item()], ['amount' => 2260, 'paid_by' => 'cash'], []);

        $this->assertSame('04', $salida['tipo_doc']);
        $this->assertStringNotContainsString('<Receptor>', $salida['xml']);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/tiqueteElectronico/TiqueteElectronico_V4.4.xsd')
        );
    }

    /** La nota tiene que declarar al mismo receptor que el comprobante original. */
    public function testLaNotaDeCreditoConservaAlReceptorExtranjero(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::clienteExtranjero();

        $xml = $crearxml->getNotaCredito(
            CrearxmlEntorno::notaCredito(2),
            [CrearxmlEntorno::item()],
            CrearxmlEntorno::referencia(),
            []
        )['xml'];

        $this->assertStringContainsString('<Receptor>', $xml);
        $this->assertStringContainsString('<Tipo>05</Tipo>', $xml);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/notaCreditoElectronica/NotaCreditoElectronica_V4.4.xsd')
        );
    }

    /** La clave de la nota se armaba antes de tener fecha y salia de 43 digitos. */
    public function testLaNotaDeCreditoLlevaUnaClaveDeCincuenta(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $salida = $crearxml->getNotaCredito(
            CrearxmlEntorno::notaCredito(2),
            [CrearxmlEntorno::item()],
            CrearxmlEntorno::referencia(),
            []
        );

        $this->assertMatchesRegularExpression('/^\d{50}$/', $salida['clave']);
        $this->assertSame(20, strlen($salida['consecutivo']));
    }

    public function testLaNotaDeDebitoValidaContraElEsquema(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $salida = $crearxml->getNotaDebito(
            CrearxmlEntorno::notaDebito(2),
            [CrearxmlEntorno::item()],
            CrearxmlEntorno::referencia(),
            []
        );

        $this->assertMatchesRegularExpression('/^\d{50}$/', $salida['clave']);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/notaDebitoElectronica/NotaDebitoElectronica_V4.4.xsd')
        );
    }

    // ── Factura electronica de compra ────────────────────────────────────

    /**
     * El codigo 06 "No Contribuyente" solo existe para respaldar la compra de
     * bienes usados y arrastra la condicion de venta 13 (Anexos v4.4, nota 4
     * pie 17 y nota 5 pie 23).
     */
    public function testLaCompraANoContribuyenteUsaCondicionDeVenta13(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->Suppliers_model->proveedor = CrearxmlEntorno::proveedorNoContribuyente();

        $xml = $crearxml->getFEC(
            CrearxmlEntorno::compra(5),
            [CrearxmlEntorno::itemCompra()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringContainsString('<Tipo>06</Tipo>', $xml);
        $this->assertStringContainsString('<CondicionVenta>13</CondicionVenta>', $xml);
        $this->assertStringNotContainsString('<PlazoCredito>', $xml);
        $this->assertStringNotContainsString('CodigoActividadEmisor', $xml);
        $this->assertStringContainsString('<Receptor>', $xml);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/facturaElectronicaCompra/FacturaElectronicaCompra_V4.4.xsd')
        );
    }

    /** El receptor de una FEC es siempre el obligado tributario y no es opcional. */
    public function testLaCompraAProveedorInscritoValidaContraElEsquema(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->Suppliers_model->proveedor = CrearxmlEntorno::proveedorInscrito();
        CrearxmlEntorno::$ci->db->filas['barrio_cr'] = [(object) ['nombre_barrio' => 'Amon Centro']];

        $xml = $crearxml->getFEC(
            CrearxmlEntorno::compra(6),
            [CrearxmlEntorno::itemCompra()],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringContainsString('<CodigoActividadEmisor>463001</CodigoActividadEmisor>', $xml);
        $this->assertStringContainsString('<CondicionVenta>01</CondicionVenta>', $xml);
        $this->assertStringContainsString('<OtrasSenas>Costado norte del mercado</OtrasSenas>', $xml);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/facturaElectronicaCompra/FacturaElectronicaCompra_V4.4.xsd')
        );
    }
    /**
     * La tarifa 01 es 0% del articulo 32 del RLIVA: Hacienda la cuenta como NO
     * SUJETA, no como exenta, y exige TotalMercNoSujeta y TotalNoSujeto.
     */
    public function testLaTarifaCeroSeDeclaraComoNoSujeta(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->db->filas['impuestos'] = [
            (object) ['id_impuesto' => 8, 'codigo_impuesto' => '01', 'codigo_tarifa' => '01']
        ];
        CrearxmlEntorno::$ci->site->producto->cabys = '2391102020000';

        $item = CrearxmlEntorno::item();
        $item['cabys'] = '2391102020000';
        $item['tax'] = '0%';
        $item['item_tax'] = 0;

        $xml = $crearxml->getInvoice(
            CrearxmlEntorno::venta(),
            [$item],
            ['amount' => 2000, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringContainsString('<TotalMercNoSujeta>2000.00000</TotalMercNoSujeta>', $xml);
        $this->assertStringContainsString('<TotalNoSujeto>2000.00000</TotalNoSujeto>', $xml);
        $this->assertStringContainsString('<TotalMercanciasExentas>0.00000</TotalMercanciasExentas>', $xml);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/tiqueteElectronico/TiqueteElectronico_V4.4.xsd')
        );
    }

    /** Hacienda clasifica bien/servicio por el CABYS: 0 a 4 son bienes, 5 a 9 servicios. */
    public function testElCabysDecideSiLaLineaEsMercanciaOServicio(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->site->producto->cabys = '8311100000000';
        $item = CrearxmlEntorno::item();
        $item['cabys'] = '8311100000000';

        $xml = $crearxml->getInvoice(CrearxmlEntorno::venta(), [$item], ['amount' => 2260, 'paid_by' => 'cash'], [])['xml'];
        $this->assertStringContainsString('<TotalMercanciasGravadas>0.00000</TotalMercanciasGravadas>', $xml);
        $this->assertStringNotContainsString('<TotalServGravados>0.00000</TotalServGravados>', $xml);

        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->site->producto->cabys = '2391102020000';
        $item['cabys'] = '2391102020000';

        $xml = $crearxml->getInvoice(CrearxmlEntorno::venta(), [$item], ['amount' => 2260, 'paid_by' => 'cash'], [])['xml'];
        $this->assertStringContainsString('<TotalServGravados>0.00000</TotalServGravados>', $xml);
        $this->assertStringNotContainsString('<TotalMercanciasGravadas>0.00000</TotalMercanciasGravadas>', $xml);
    }

    /** "Para el codigo de tarifa '08' la tarifa debe ser 13": ambos deben concordar. */
    public function testElCodigoDeTarifaConcuerdaConElPorcentajeCobrado(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->db->filas['impuestos'] = [
            (object) ['id_impuesto' => 8, 'codigo_impuesto' => '01', 'codigo_tarifa' => '08']
        ];

        // La linea cobro 0% aunque el producto tenga configurado el 13%.
        $item = CrearxmlEntorno::item();
        $item['tax'] = '0%';
        $item['item_tax'] = 0;

        $xml = $crearxml->getInvoice(CrearxmlEntorno::venta(), [$item], ['amount' => 2000, 'paid_by' => 'cash'], [])['xml'];

        $this->assertStringNotContainsString('<CodigoTarifaIVA>08</CodigoTarifaIVA>', $xml);
        $this->assertStringContainsString('<CodigoTarifaIVA>01</CodigoTarifaIVA>', $xml);
    }

    /** La v4.4 exige CodigoDescuento dentro de <Descuento>, antes de la naturaleza. */
    public function testElDescuentoLlevaSuCodigo(): void
    {
        $item = CrearxmlEntorno::item();
        $item['discount'] = '100';
        $item['item_discount'] = 100;

        $xml = CrearxmlEntorno::preparar()->getInvoice(
            CrearxmlEntorno::venta(),
            [$item],
            ['amount' => 2147, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringContainsString('<CodigoDescuento>07</CodigoDescuento>', $xml);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/tiqueteElectronico/TiqueteElectronico_V4.4.xsd')
        );
    }
    /**
     * Un articulo rapido no tiene ficha de producto: su CABYS llega como codigo
     * del renglon y de ahi sale tambien si es mercancia o servicio.
     */
    public function testElArticuloRapidoTomaElCabysDeSuCodigo(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->site->producto = false;

        $item = CrearxmlEntorno::item();
        $item['product_id'] = 0;
        $item['product_code'] = '2391102020000';
        $item['unit_of_measurement'] = 'Unid';
        unset($item['cabys'], $item['type']);

        $xml = $crearxml->getInvoice(
            CrearxmlEntorno::venta(),
            [$item],
            ['amount' => 2260, 'paid_by' => 'cash'],
            []
        )['xml'];

        $this->assertStringContainsString('<CodigoCABYS>2391102020000</CodigoCABYS>', $xml);
        $this->assertStringContainsString('<TotalServGravados>0.00000</TotalServGravados>', $xml);
        $this->assertSame([], CrearxmlEntorno::$avisos, 'No debe avisar por CABYS ausente');
        $this->assertSame(
            [],
            $this->erroresDeEsquema($xml, self::XSD . '/tiqueteElectronico/TiqueteElectronico_V4.4.xsd')
        );
    }

    /* ══════════════ InformacionReferencia ══════════════ */

    /**
     * Extrae un nodo del bloque de referencia.
     *
     * Se acota primero a <InformacionReferencia>: <Codigo> tambien existe dentro
     * de <CodigoComercial> de cada linea de detalle.
     */
    private function refDe(string $xml, string $nodo): string
    {
        if (!preg_match('#<InformacionReferencia>(.*?)</InformacionReferencia>#s', $xml, $bloque)) {
            return '';
        }
        return preg_match('#<' . $nodo . '>(.*?)</' . $nodo . '>#s', $bloque[1], $m) ? trim($m[1]) : '';
    }

    /**
     * TipoDocIR describe el documento REFERENCIADO, no la nota.
     *
     * Estaba fijo en 04, asi que toda nota de credito sobre una factura
     * electronica declaraba que referenciaba un tiquete.
     */
    public function testLaNotaDeCreditoDeclaraElTipoDelDocumentoReferenciado(): void
    {
        foreach (['01' => '01', '04' => '04', '1' => '01'] as $enLaTabla => $esperado) {
            $crearxml = CrearxmlEntorno::preparar();
            CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

            $referencia = CrearxmlEntorno::referencia();
            $referencia->tipo_doc = $enLaTabla;

            $xml = $crearxml->getNotaCredito(
                CrearxmlEntorno::notaCredito(2),
                [CrearxmlEntorno::item()],
                $referencia,
                []
            )['xml'];

            $this->assertSame($esperado, $this->refDe($xml, 'TipoDocIR'),
                "Un comprobante tipo '$enLaTabla' debe referenciarse como '$esperado'");
        }
    }

    public function testLaNotaDeDebitoDeclaraElTipoDelDocumentoReferenciado(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $referencia = CrearxmlEntorno::referencia();
        $referencia->tipo_doc = '04';

        $xml = $crearxml->getNotaDebito(
            CrearxmlEntorno::notaDebito(2),
            [CrearxmlEntorno::item()],
            $referencia,
            []
        )['xml'];

        // notaDebito() trae type_nd '01': la nota no puede imponer su propio tipo.
        $this->assertSame('04', $this->refDe($xml, 'TipoDocIR'));
    }

    /**
     * El 03 no existe en CodigoReferenciaType v4.4 y el POS lo ofrecia como
     * "devolucion de mercancia", que en realidad es el 06.
     */
    public function testUnCodigoDeReferenciaInvalidoNoLlegaAlComprobante(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $nota = CrearxmlEntorno::notaCredito(2);
        $nota['type_nc'] = '3';

        $salida = $crearxml->getNotaCredito($nota, [CrearxmlEntorno::item()], CrearxmlEntorno::referencia(), []);

        $this->assertNotSame('03', $this->refDe($salida['xml'], 'Codigo'));
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/notaCreditoElectronica/NotaCreditoElectronica_V4.4.xsd')
        );
    }

    public function testElCodigoDeReferenciaValidoSeRespeta(): void
    {
        foreach (['01', '06', '02', '99'] as $codigo) {
            $crearxml = CrearxmlEntorno::preparar();
            CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

            $nota = CrearxmlEntorno::notaCredito(2);
            $nota['type_nc'] = $codigo;

            $salida = $crearxml->getNotaCredito($nota, [CrearxmlEntorno::item()], CrearxmlEntorno::referencia(), []);

            $this->assertSame($codigo, $this->refDe($salida['xml'], 'Codigo'));
            $this->assertSame(
                [],
                $this->erroresDeEsquema($salida['xml'], self::XSD . '/notaCreditoElectronica/NotaCreditoElectronica_V4.4.xsd'),
                "El codigo de referencia $codigo debe validar contra el esquema"
            );
        }
    }

    /** Razon es obligatoria en cuanto se emite el bloque y tiene tope de largo. */
    public function testLaRazonDeLaNotaNoSeDesborda(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $nota = CrearxmlEntorno::notaCredito(2);
        $nota['hold_ref'] = str_repeat('Motivo muy largo. ', 40);

        $salida = $crearxml->getNotaCredito($nota, [CrearxmlEntorno::item()], CrearxmlEntorno::referencia(), []);

        $this->assertLessThanOrEqual(180, mb_strlen($this->refDe($salida['xml'], 'Razon')));
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/notaCreditoElectronica/NotaCreditoElectronica_V4.4.xsd')
        );
    }

    /* ══════════════ Nota de credito de anulacion ══════════════ */

    /**
     * Linea con las mismas claves que arma Salesdoc::_lineasDeLaVenta().
     *
     * Es el contrato entre la anulacion y el generador: si el controlador deja
     * de mandar una clave, el comprobante sale mal y nadie se entera hasta que
     * Hacienda lo rechaza.
     */
    private function lineaDeAnulacion(array $cambios = []): array
    {
        return array_merge([
            'product_id'          => 11,
            'product_code'        => '7441000000116',
            'product_name'        => 'Jugo Del Valle 1L',
            'quantity'            => 2.0,
            'unit_price'          => 990.0,
            'net_unit_price'      => 990.0,
            'real_unit_price'     => 1100.0,
            'price'               => 1980.0,
            'subtotal'            => 1980.0,
            'discount'            => '10%',
            'item_discount'       => 220.0,
            'tax'                 => '13%',
            'item_tax'            => 257.4,
            'id_tax'              => 1,
            'cost'                => 780.0,
            'comment'             => '',
            'unit_of_measurement' => 'Unid',
            'cabys'               => '2314000990300',
            'codigo_impuesto'     => '01',
            'codigo_tarifa'       => '08',
        ], $cambios);
    }

    /** Cabecera tal como la arma Salesdoc::_emitirNotaDeAnulacion(). */
    private function notaDeAnulacion(array $cambios = []): array
    {
        return array_merge(CrearxmlEntorno::venta(), [
            'type_nc'     => '01',
            'customer_id' => 2,
            'status'      => 'paid',
            'hold_ref'    => 'Anulacion por error de digitacion en el precio',
        ], $cambios);
    }

    /** La anulacion emite la nota por el 100% y con codigo de referencia 01. */
    public function testLaNotaDeAnulacionValidaContraElEsquema(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $salida = $crearxml->getNotaCredito(
            $this->notaDeAnulacion(),
            [$this->lineaDeAnulacion()],
            CrearxmlEntorno::referencia(),
            null
        );

        $this->assertSame('01', $this->refDe($salida['xml'], 'Codigo'),
            'Anular exige el codigo 01, "anula documento de referencia"');
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/notaCreditoElectronica/NotaCreditoElectronica_V4.4.xsd')
        );
    }

    /** Una devolucion de mercancia es la misma nota con codigo 06. */
    public function testLaNotaDeDevolucionValidaContraElEsquema(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $salida = $crearxml->getNotaCredito(
            $this->notaDeAnulacion(['type_nc' => '06', 'hold_ref' => 'El cliente devolvio una unidad']),
            [$this->lineaDeAnulacion(['quantity' => 1.0, 'subtotal' => 990.0, 'price' => 990.0, 'item_tax' => 128.7])],
            CrearxmlEntorno::referencia(),
            null
        );

        $this->assertSame('06', $this->refDe($salida['xml'], 'Codigo'));
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/notaCreditoElectronica/NotaCreditoElectronica_V4.4.xsd')
        );
    }

    /**
     * Sin CABYS de 13 digitos la linea no valida.
     *
     * Manda el de la ficha del producto; el de la linea es el respaldo del
     * articulo rapido, que no tiene ficha donde guardarlo. La anulacion copia
     * ese respaldo desde tec_sale_items para no perderlo.
     */
    public function testLaNotaDeAnulacionArrastraElCabysDeLaLineaSinFicha(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();
        CrearxmlEntorno::$ci->site->producto = null;   // articulo rapido: no hay ficha

        $salida = $crearxml->getNotaCredito(
            $this->notaDeAnulacion(),
            [$this->lineaDeAnulacion()],
            CrearxmlEntorno::referencia(),
            null
        );

        $this->assertStringContainsString('<CodigoCABYS>2314000990300</CodigoCABYS>', $salida['xml']);
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/notaCreditoElectronica/NotaCreditoElectronica_V4.4.xsd')
        );
    }

    /** Con ficha manda el CABYS del producto, no el copiado en la linea. */
    public function testElCabysDeLaFichaTienePrioridad(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $salida = $crearxml->getNotaCredito(
            $this->notaDeAnulacion(),
            [$this->lineaDeAnulacion()],
            CrearxmlEntorno::referencia(),
            null
        );

        $this->assertStringContainsString('<CodigoCABYS>1101010100000</CodigoCABYS>', $salida['xml']);
    }

    /**
     * La nota tiene que cuadrar con el comprobante que anula: se copia el precio
     * facturado, no el que tenga hoy la ficha del producto.
     */
    public function testLaNotaDeAnulacionCuadraConLoFacturado(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        // La ficha del producto cambio de precio despues de la venta.
        CrearxmlEntorno::$ci->site->producto->price = 5000;

        $salida = $crearxml->getNotaCredito(
            $this->notaDeAnulacion(),
            [$this->lineaDeAnulacion()],
            CrearxmlEntorno::referencia(),
            null
        );

        $this->assertStringNotContainsString('5000.00000', $salida['xml'],
            'El precio actual del producto no puede colarse en la nota');
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/notaCreditoElectronica/NotaCreditoElectronica_V4.4.xsd')
        );
    }

    /**
     * FechaEmisionIR es dateTime del XSD: MySQL entrega la fecha con un espacio
     * y Hacienda rechazaba la nota con 'is not a valid value for dateTime'.
     */
    public function testLaFechaDelDocumentoReferenciadoVaEnIso(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::cliente();

        $referencia = CrearxmlEntorno::referencia();
        $referencia->fecha_emision = '2026-08-26 00:31:57';   // como sale de la base

        $salida = $crearxml->getNotaCredito(
            $this->notaDeAnulacion(),
            [$this->lineaDeAnulacion()],
            $referencia,
            null
        );

        $this->assertSame('2026-08-26T00:31:57', $this->refDe($salida['xml'], 'FechaEmisionIR'));
        $this->assertSame(
            [],
            $this->erroresDeEsquema($salida['xml'], self::XSD . '/notaCreditoElectronica/NotaCreditoElectronica_V4.4.xsd')
        );
    }

    /* ───────────────────────── mensaje receptor ───────────────────────── */

    private const XSD_MR = self::XSD . '/mensajeReceptor/MensajeReceptor_V4.4.xsd';

    private function mensajeReceptor(array $x = []): array
    {
        return array_merge([
            'Mensaje'                     => '1',
            'DetalleMensaje'              => '',
            'CondicionImpuesto'           => '01',
            'MontoTotalImpuestoAcreditar' => 1665.35304,
            'MontoTotalDeGastoAplicable'  => 12810.408,
            'id_documento'                => 9,
            'numero_consecutivo'          => 3,
            'ClaveDocEmisor'              => '50601012600310108296900100001010000012345100000001',
            'NumeroCedulaEmisor'          => '3101082969',
            'FechaEmisionDoc'             => '2026-09-01 10:00:00',
            'MontoTotalImpuesto'          => 1665.35304,
            'TotalFactura'                => 14475.76104,
        ], $x);
    }

    public function testLaAceptacionConCreditoValidaContraElEsquema(): void
    {
        $mr = CrearxmlEntorno::preparar(['default_actividad' => '523101'])->getMensajeReceptor($this->mensajeReceptor());

        $this->assertSame('05', $mr[3]);
        $this->assertSame('00100001050000000003', $mr[1], 'El numero sale de la serie de su tipo, no del id del documento');
        $this->assertStringContainsString('<CodigoActividad>523101</CodigoActividad>', $mr[0]);
        $this->assertSame([], $this->erroresDeEsquema($mr[0], self::XSD_MR));
    }

    public function testElRechazoNoDeclaraCondicionNiMontos(): void
    {
        $mr = CrearxmlEntorno::preparar()->getMensajeReceptor($this->mensajeReceptor([
            'Mensaje' => '3', 'DetalleMensaje' => 'Mercaderia no recibida',
        ]));

        $this->assertSame('07', $mr[3]);
        $this->assertStringNotContainsString('CondicionImpuesto', $mr[0]);
        $this->assertStringNotContainsString('MontoTotalImpuestoAcreditar', $mr[0]);
        $this->assertSame([], $this->erroresDeEsquema($mr[0], self::XSD_MR));
    }

    public function testUnDetalleConAmpersandNoRompeElXml(): void
    {
        $mr = CrearxmlEntorno::preparar()->getMensajeReceptor($this->mensajeReceptor([
            'Mensaje' => '2', 'DetalleMensaje' => 'Faltaron tornillos & arandelas <caja 3> ' . str_repeat('x', 200),
            'CondicionImpuesto' => '02', 'MontoTotalImpuestoAcreditar' => 500, 'MontoTotalDeGastoAplicable' => 13975.76104,
        ]));

        $this->assertSame([], $this->erroresDeEsquema($mr[0], self::XSD_MR));
    }

    public function testLasCedulasVanSinCerosNiGuiones(): void
    {
        $mr = CrearxmlEntorno::preparar(['cedula_emisor' => '3-101-123456'])->getMensajeReceptor($this->mensajeReceptor());

        $this->assertStringContainsString('<NumeroCedulaEmisor>3101082969</NumeroCedulaEmisor>', $mr[0]);
        $this->assertStringContainsString('<NumeroCedulaReceptor>3101123456</NumeroCedulaReceptor>', $mr[0]);
    }
}

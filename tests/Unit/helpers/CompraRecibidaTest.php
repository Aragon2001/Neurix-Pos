<?php

use PHPUnit\Framework\TestCase;

/**
 * Gestion de un comprobante recibido: lo que se lee del XML, a que producto se
 * relaciona cada linea y lo que se declara a Hacienda en el mensaje receptor.
 *
 * Un error aca no se ve en pantalla: suma existencias al producto equivocado o
 * deja el inventario valorado con un IVA que se acredito.
 */
class CompraRecibidaTest extends TestCase
{
    private function factura(string $lineas, string $resumenExtra = ''): SimpleXMLElement
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<FacturaElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica">
  <Clave>50601012600310108296900100001010000012345100000001</Clave>
  <NumeroConsecutivo>00100001010000012345</NumeroConsecutivo>
  <FechaEmision>2026-09-01T10:00:00-06:00</FechaEmision>
  <Emisor>
    <Nombre>ALMACENES EL COLONO S.A</Nombre>
    <Identificacion><Tipo>02</Tipo><Numero>3101082969</Numero></Identificacion>
    <CorreoElectronico>fe@colono.cr</CorreoElectronico>
  </Emisor>
  <CondicionVenta>01</CondicionVenta>
  <DetalleServicio>' . $lineas . '</DetalleServicio>
  <ResumenFactura>
    <CodigoTipoMoneda><CodigoMoneda>CRC</CodigoMoneda><TipoCambio>1</TipoCambio></CodigoTipoMoneda>
    <TotalImpuesto>1665.35304</TotalImpuesto>
    <MedioPago><TipoMedioPago>04</TipoMedioPago><TotalMedioPago>14475.76104</TotalMedioPago></MedioPago>
    <TotalComprobante>14475.76104</TotalComprobante>' . $resumenExtra . '
  </ResumenFactura>
</FacturaElectronica>';

        return simplexml_load_string($xml);
    }

    /** Linea tal como la emite un proveedor real en v4.4. */
    private const REPELLO = '<LineaDetalle>
      <NumeroLinea>1</NumeroLinea>
      <CodigoCABYS>3733000000000</CodigoCABYS>
      <CodigoComercial><Tipo>01</Tipo><Codigo>17237</Codigo></CodigoComercial>
      <CodigoComercial><Tipo>04</Tipo><Codigo>7441234567890</Codigo></CodigoComercial>
      <Cantidad>2.000</Cantidad>
      <UnidadMedida>Unid</UnidadMedida>
      <Detalle>REPELLO MURO SECO GRIS 25KG INTACO PT1041</Detalle>
      <PrecioUnitario>6742.32000</PrecioUnitario>
      <MontoTotal>13484.64000</MontoTotal>
      <Descuento><MontoDescuento>674.23200</MontoDescuento><CodigoDescuento>07</CodigoDescuento></Descuento>
      <SubTotal>12810.40800</SubTotal>
      <BaseImponible>12810.40800</BaseImponible>
      <Impuesto><Codigo>01</Codigo><CodigoTarifaIVA>08</CodigoTarifaIVA><Tarifa>13.00</Tarifa><Monto>1665.35304</Monto></Impuesto>
      <ImpuestoAsumidoEmisorFabrica>0</ImpuestoAsumidoEmisorFabrica>
      <ImpuestoNeto>1665.35304</ImpuestoNeto>
      <MontoTotalLinea>14475.76104</MontoTotalLinea>
    </LineaDetalle>';

    /* ───────────────────────── lectura del XML ───────────────────────── */

    public function testElDescuentoDeLaV44SeLeeDentroDeSuNodo(): void
    {
        [, , $lineas] = compra_mapear_xml($this->factura(self::REPELLO), '<x/>', 1);

        $this->assertEqualsWithDelta(674.232, $lineas[0]['monto_descuento'], 0.0001);
    }

    public function testElCostoDeLaLineaNoLlevaElIva(): void
    {
        [, , $lineas] = compra_mapear_xml($this->factura(self::REPELLO), '<x/>', 1);

        // 12 810,408 / 2: el IVA acreditable no es costo.
        $this->assertEqualsWithDelta(6405.204, $lineas[0]['cost'], 0.0001);
    }

    public function testSeGuardanCabysNumeroDeLineaYCodigoDelVendedor(): void
    {
        [, , $lineas] = compra_mapear_xml($this->factura(self::REPELLO), '<x/>', 1);
        $l = $lineas[0];

        $this->assertSame('3733000000000', $l['cabys']);
        $this->assertSame(1, $l['numero_linea']);
        $this->assertSame('17237', $l['code']);
        $this->assertSame('01', $l['codigo_tipo']);
        $this->assertSame('7441234567890', $l['codigo_barras']);
        $this->assertSame('08', $l['codigo_tarifa']);
    }

    public function testElMedioDePagoYLaMonedaSalenDelResumen(): void
    {
        [$doc, $prov] = compra_mapear_xml($this->factura(self::REPELLO), '<x/>', 1);

        $this->assertSame('04', $doc['MedioPago']);
        $this->assertSame('CRC', $doc['CodigoMoneda']);
        $this->assertSame('3101082969', $prov['cf2']);
    }

    public function testLaCantidadConComaDecimalSeLeeBien(): void
    {
        $linea = str_replace('<Cantidad>2.000</Cantidad>', '<Cantidad>2,5</Cantidad>', self::REPELLO);
        [, , $lineas] = compra_mapear_xml($this->factura($linea), '<x/>', 1);

        $this->assertSame(2.5, $lineas[0]['quantity']);
    }

    public function testUnaLineaDeServicioSeMarcaComoTal(): void
    {
        $linea = str_replace('<UnidadMedida>Unid</UnidadMedida>', '<UnidadMedida>Sp</UnidadMedida>', self::REPELLO);
        [, , $lineas] = compra_mapear_xml($this->factura($linea), '<x/>', 1);

        $this->assertSame('service', $lineas[0]['type']);
    }

    /* ───────────────────────── relacion con productos ───────────────────────── */

    private function linea(array $x = array()): array
    {
        return array_merge(array(
            'code' => '17237', 'codigo_barras' => '', 'cabys' => '3733000000000',
            'name' => 'REPELLO MURO SECO GRIS 25KG', 'unit_of_measurement' => 'Unid',
        ), $x);
    }

    public function testLoAprendidoPorCodigoSeAplicaSoloConSuFactor(): void
    {
        $mapas = array(array('codigo' => '17237', 'descripcion' => 'otra cosa', 'product_id' => 40, 'factor' => 24));

        $r = compra_relacionar($this->linea(), $mapas, array(), array());

        $this->assertSame(40, $r['product_id']);
        $this->assertSame(24.0, $r['factor']);
        $this->assertSame('auto', $r['confianza']);
        $this->assertSame('codigo', $r['origen']);
    }

    public function testLaDescripcionSeComparaSinMayusculasTildesNiSignos(): void
    {
        $mapas = array(array('codigo' => '', 'descripcion' => compra_texto_normalizado('Repello muro-seco  gris 25kg'), 'product_id' => 7, 'factor' => 1));

        $r = compra_relacionar($this->linea(array('code' => '0')), $mapas, array(), array());

        $this->assertSame(7, $r['product_id']);
        $this->assertSame('descripcion', $r['origen']);
    }

    public function testElCodigoDeBarrasCoincideConElDelProducto(): void
    {
        $porCodigo = array('7441234567890' => array('id' => 3, 'name' => 'Repello'));

        $r = compra_relacionar($this->linea(array('codigo_barras' => '7441234567890')), array(), $porCodigo, array());

        $this->assertSame(3, $r['product_id']);
        $this->assertSame('barras', $r['origen']);
    }

    public function testUnCodigoCortoDelProveedorNoSeConfundeConUnCodigoPropio(): void
    {
        // «1461» del proveedor no es el producto 1461 de la tienda.
        $porCodigo = array('1461' => array('id' => 9, 'name' => 'Otro'));

        $r = compra_relacionar($this->linea(array('code' => '1461')), array(), $porCodigo, array());

        $this->assertSame(0, $r['product_id']);
    }

    public function testElCabysSoloSugiereNuncaRelacionaSolo(): void
    {
        $porCabys = array('3733000000000' => array(
            array('id' => 5, 'name' => 'Repello gris 25 kg'),
            array('id' => 6, 'name' => 'Cemento blanco'),
        ));

        $r = compra_relacionar($this->linea(array('code' => '')), array(), array(), $porCabys);

        $this->assertSame(5, $r['product_id']);
        $this->assertSame('sugerida', $r['confianza']);
    }

    public function testUnNombreDistintoConElMismoCabysNoSeSugiere(): void
    {
        $porCabys = array('3733000000000' => array(array('id' => 6, 'name' => 'Pegamix ceramica')));

        $r = compra_relacionar($this->linea(array('code' => '')), array(), array(), $porCabys);

        $this->assertSame('ninguna', $r['confianza']);
    }

    public function testElCodigoAprendidoGanaALaDescripcion(): void
    {
        $mapas = array(
            array('codigo' => '', 'descripcion' => compra_texto_normalizado('REPELLO MURO SECO GRIS 25KG'), 'product_id' => 1, 'factor' => 1),
            array('codigo' => '17237', 'descripcion' => 'x', 'product_id' => 2, 'factor' => 1),
        );

        $this->assertSame(2, compra_relacionar($this->linea(), $mapas, array(), array())['product_id']);
    }

    /* ───────────────────────── sugerencias ───────────────────────── */

    public function testUnaLineaRelacionadaVaAInventario(): void
    {
        $rel = array('confianza' => 'auto');
        $this->assertSame('inventario', compra_destino_sugerido($this->linea(), $rel, 'gasto'));
    }

    public function testSinRelacionSeUsaElHabitoDelProveedor(): void
    {
        $rel = array('confianza' => 'ninguna');
        $this->assertSame('gasto', compra_destino_sugerido($this->linea(), $rel, 'gasto'));
    }

    public function testSinRelacionNiHabitoLaMercaderiaSeDejaParaElegir(): void
    {
        $rel = array('confianza' => 'ninguna');
        $this->assertSame('', compra_destino_sugerido($this->linea(), $rel, null));
        $this->assertSame('gasto', compra_destino_sugerido($this->linea(array('unit_of_measurement' => 'Sp')), $rel, null));
    }

    public function testSinImpuestoNoHayCondicionQueDeclarar(): void
    {
        $this->assertSame('', compra_condicion_sugerida(array('inventario'), 0, '01'));
    }

    public function testSoloActivosSugiereBienesDeCapital(): void
    {
        $this->assertSame('03', compra_condicion_sugerida(array('activo', 'ignorar'), 100, null));
        $this->assertSame('01', compra_condicion_sugerida(array('activo', 'gasto'), 100, null));
    }

    /* ───────────────────────── montos fiscales ───────────────────────── */

    public function testCreditoTotalAcreditaTodoElImpuesto(): void
    {
        $m = compra_montos_fiscales('1', '01', 130, 1130);
        $this->assertSame(130.0, $m['acreditar']);
        $this->assertSame(1000.0, $m['gasto']);
    }

    public function testGastoSinCreditoLlevaElTotalComoGasto(): void
    {
        $m = compra_montos_fiscales('1', '04', 130, 1130);
        $this->assertSame(0.0, $m['acreditar']);
        $this->assertSame(1130.0, $m['gasto']);
    }

    public function testElCreditoParcialNoPasaDelImpuesto(): void
    {
        $m = compra_montos_fiscales('1', '02', 130, 1130, 500);
        $this->assertSame(130.0, $m['acreditar']);
    }

    public function testUnRechazoNoDeclaraMontos(): void
    {
        $this->assertSame(array('acreditar' => 0.0, 'gasto' => 0.0), compra_montos_fiscales('3', '01', 130, 1130));
    }

    /* ───────────────────────── costo y precio ───────────────────────── */

    private function lineaCosto(): array
    {
        return array('SubTotal' => 1000, 'impuesto_neto' => 130, 'quantity' => 2);
    }

    public function testElCostoSeRepartePorElFactorDeConversion(): void
    {
        // 2 cajas de 12: 24 unidades a 1000 / 24.
        $c = compra_costo_linea($this->lineaCosto(), 0, 12, 1);
        $this->assertEqualsWithDelta(41.6667, $c['unitario'], 0.0001);
    }

    public function testElIvaNoAcreditableEntraAlCosto(): void
    {
        $c = compra_costo_linea($this->lineaCosto(), 1, 1, 1);
        $this->assertSame(565.0, $c['unitario']);
    }

    public function testUnDocumentoEnDolaresSeCosteaEnColones(): void
    {
        $c = compra_costo_linea($this->lineaCosto(), 0, 1, 510);
        $this->assertSame(255000.0, $c['unitario']);
    }

    public function testLaParteNoAcreditableDelCreditoParcial(): void
    {
        $this->assertSame(0.75, compra_proporcion_no_acreditable('02', 100, 25));
        $this->assertSame(1.0, compra_proporcion_no_acreditable('04', 100, 0));
        $this->assertSame(0.0, compra_proporcion_no_acreditable('01', 100, 100));
    }

    public function testElPrecioSugeridoUsaElMargenDelProducto(): void
    {
        $this->assertSame(1300.0, compra_precio_sugerido(1000, array('margen' => 30, 'tax_method' => '1')));
    }

    public function testConPrecioImpuestoIncluidoLaSugerenciaLoSuma(): void
    {
        $this->assertSame(1469.0, compra_precio_sugerido(1000, array('margen' => 30, 'tax_method' => '0', 'tax' => 13)));
    }

    public function testSinMargenSeConservaLaRelacionPrecioCosto(): void
    {
        $this->assertSame(1500.0, compra_precio_sugerido(1000, array('margen' => 0, 'cost' => 800, 'price' => 1200)));
    }

    /* ───────────────────────── validacion ───────────────────────── */

    private function fiscal(array $x = array()): array
    {
        return array_merge(array('pendiente' => true, 'mensaje' => '1', 'condicion' => '01', 'acreditar' => 0, 'detalle' => ''), $x);
    }

    public function testUnaLineaDeInventarioSinProductoNoPasa(): void
    {
        $e = compra_validar_gestion(
            array(array('numero_linea' => 3)),
            array(array('destino' => 'inventario', 'product_id' => 0, 'factor' => 1)),
            $this->fiscal(), 10
        );

        $this->assertSame('gestion_linea_sin_producto', $e[0]['clave']);
        $this->assertSame(3, $e[0]['linea']);
    }

    public function testUnGastoSinCategoriaNoPasa(): void
    {
        $e = compra_validar_gestion(array(array()), array(array('destino' => 'gasto')), $this->fiscal(), 10);
        $this->assertSame('gestion_linea_sin_categoria', $e[0]['clave']);
    }

    public function testElRechazoExigeMotivoYNoRevisaLineas(): void
    {
        $e = compra_validar_gestion(array(array()), array(), $this->fiscal(array('mensaje' => '3')), 10);

        $this->assertSame(array(array('clave' => 'aceptacion_detalle_requerido')), $e);
    }

    public function testConImpuestoLaCondicionEsObligatoria(): void
    {
        $e = compra_validar_gestion(array(), array(), $this->fiscal(array('condicion' => '05')), 10);
        $this->assertSame('gestion_condicion_requerida', $e[0]['clave']);
    }

    public function testUnDocumentoYaRespondidoSoloRevisaLasLineas(): void
    {
        $e = compra_validar_gestion(
            array(array()),
            array(array('destino' => 'ignorar')),
            array('pendiente' => false, 'mensaje' => '1'), 10
        );

        $this->assertSame(array(), $e);
    }

    public function testElHabitoEsElDestinoMasRepetido(): void
    {
        $h = compra_habitos(array(
            array('destino' => 'gasto', 'categoria_gasto_id' => 4),
            array('destino' => 'gasto', 'categoria_gasto_id' => 4),
            array('destino' => 'inventario'),
            array('destino' => 'ignorar'),
        ));

        $this->assertSame('gasto', $h['destino']);
        $this->assertSame(4, $h['categoria']);
    }

    /* ───────────────────────── reposicion ───────────────────────── */

    public function testSinVentasSeReponeHastaElMinimo(): void
    {
        $r = compra_reorden(2, 10, 0, 30);

        $this->assertTrue($r['reponer']);
        $this->assertSame(8.0, $r['sugerido']);
        $this->assertNull($r['cobertura']);
    }

    public function testConVentasSeCubrenQuinceDiasMasElMinimo(): void
    {
        // 60 en 30 dias = 2 diarias; punto 14; faltan 2*15 + 5 - 10 = 25.
        $r = compra_reorden(10, 5, 60, 30);

        $this->assertSame(2.0, $r['venta_diaria']);
        $this->assertSame(5.0, $r['cobertura']);
        $this->assertTrue($r['reponer']);
        $this->assertSame(25.0, $r['sugerido']);
    }

    public function testElSugeridoSeRedondeaAlEmpaqueDelProveedor(): void
    {
        $this->assertSame(36.0, compra_reorden(10, 5, 60, 30, 12)['sugerido']);
    }

    public function testConExistenciaSuficienteNoSeSugiere(): void
    {
        $r = compra_reorden(100, 5, 60, 30);

        $this->assertFalse($r['reponer']);
        $this->assertSame(0.0, $r['sugerido']);
    }

    public function testUnProductoSinMinimoNiVentasNoSeAlerta(): void
    {
        $this->assertFalse(compra_reorden(0, 0, 0, 30)['reponer']);
    }

    public function testLaExistenciaNegativaTambienSeRepone(): void
    {
        $r = compra_reorden(-3, 5, 0, 30);
        $this->assertSame(8.0, $r['sugerido']);
        $this->assertNull($r['cobertura']);
    }
}

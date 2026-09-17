<?php

use PHPUnit\Framework\TestCase;

/**
 * El motor que decide que documento de ajuste corresponde al comparar una
 * factura con el estado en que quedo despues de editarla.
 *
 * Cada caso es un escenario de caja: lo que el cajero hace en pantalla y el
 * comprobante que Hacienda tiene que recibir. Las reglas de monto son las que
 * mas caro salen si se rompen, porque el XML sale igual y el rechazo llega
 * despues.
 */
class AjusteDocumentoTest extends TestCase
{
    /** Linea de venta con los valores por omision de un producto gravado al 13 %. */
    private function linea(array $x = array()): array
    {
        return array_merge(array(
            'id'                  => 0,
            'product_id'          => 1,
            'product_code'        => 'A',
            'product_name'        => 'Producto A',
            'cabys'               => '1234567890123',
            'quantity'            => 1,
            'unit_price'          => 1000,
            'net_unit_price'      => 1000,
            'item_discount'       => 0,
            'tax'                 => '13%',
            'id_tax'              => 8,
            'unit_of_measurement' => 'Unid',
        ), $x);
    }

    /** Producto sin impuesto, para los escenarios donde el IVA solo estorba. */
    private function exento(int $id, string $nombre, float $monto): array
    {
        return $this->linea(array(
            'id' => $id, 'product_id' => $id, 'product_code' => $nombre,
            'product_name' => $nombre, 'quantity' => 1,
            'unit_price' => $monto, 'net_unit_price' => $monto, 'tax' => '0',
        ));
    }

    /* ───────────────────────── sin cambios ───────────────────────── */

    public function testUnDocumentoSinTocarNoGeneraNada(): void
    {
        $lineas = array($this->linea(array('id' => 1, 'quantity' => 3)));

        $r = ajuste_comparar($lineas, $lineas);

        $this->assertSame('SIN_CAMBIOS', $r['tipo']);
        $this->assertSame('sin_cambios', $r['bloqueo']['clave']);
        $this->assertSame(array(), $r['lineas_nota']);
    }

    public function testReordenarLasLineasNoEsUnCambio(): void
    {
        $a = $this->linea(array('id' => 1, 'product_name' => 'A'));
        $b = $this->linea(array('id' => 2, 'product_name' => 'B', 'unit_price' => 500, 'net_unit_price' => 500));

        $r = ajuste_comparar(array($a, $b), array($b, $a));

        $this->assertSame('SIN_CAMBIOS', $r['tipo']);
    }

    /* ───────────────────────── quitar lineas ───────────────────────── */

    public function testEliminarUnaLineaEsNotaDeCredito(): void
    {
        $a = $this->linea(array('id' => 1, 'quantity' => 1, 'unit_price' => 10000, 'net_unit_price' => 10000));
        $b = $this->linea(array('id' => 2, 'quantity' => 1, 'unit_price' => 5000, 'net_unit_price' => 5000));
        $c = $this->linea(array('id' => 3, 'quantity' => 1, 'unit_price' => 8000, 'net_unit_price' => 8000));

        $r = ajuste_comparar(array($a, $b, $c), array($a, $c));

        $this->assertSame('NOTA_CREDITO', $r['tipo']);
        $this->assertSame('06', $r['codigo_referencia']);
        // La nota lleva solo la linea que se fue, con su monto completo.
        $this->assertCount(1, $r['lineas_nota']);
        $this->assertEqualsWithDelta(5000 * 1.13, $r['total_nota'], 0.0001);
        $this->assertEqualsWithDelta(-5000 * 1.13, $r['delta']['total'], 0.0001);
    }

    public function testEliminarVariasLineasSumaTodasEnLaNota(): void
    {
        $a = $this->linea(array('id' => 1, 'unit_price' => 10000, 'net_unit_price' => 10000));
        $b = $this->linea(array('id' => 2, 'unit_price' => 5000, 'net_unit_price' => 5000));
        $c = $this->linea(array('id' => 3, 'unit_price' => 8000, 'net_unit_price' => 8000));

        $r = ajuste_comparar(array($a, $b, $c), array($a));

        $this->assertSame('NOTA_CREDITO', $r['tipo']);
        $this->assertCount(2, $r['lineas_nota']);
        $this->assertEqualsWithDelta(13000 * 1.13, $r['total_nota'], 0.0001);
    }

    public function testEliminarTodasLasLineasEsUnaAnulacion(): void
    {
        $a = $this->linea(array('id' => 1, 'unit_price' => 10000, 'net_unit_price' => 10000));
        $b = $this->linea(array('id' => 2, 'unit_price' => 5000, 'net_unit_price' => 5000));

        $r = ajuste_comparar(array($a, $b), array());

        $this->assertSame('ANULACION', $r['tipo']);
        // El codigo 01 anula el documento de referencia (Anexos v4.4).
        $this->assertSame('01', $r['codigo_referencia']);
        $this->assertTrue($r['requiere_confirmacion']);
        $this->assertContains('ajuste_aviso_anulacion', $r['avisos']);
        $this->assertEqualsWithDelta(15000 * 1.13, $r['total_nota'], 0.0001);
    }

    public function testLaAnulacionLlevaTodasLasLineasOriginales(): void
    {
        $a = $this->linea(array('id' => 1));
        $b = $this->linea(array('id' => 2));
        $c = $this->linea(array('id' => 3));

        $r = ajuste_comparar(array($a, $b, $c), array());

        $this->assertCount(3, $r['lineas_nota']);
    }

    /* ───────────────────────── agregar lineas ───────────────────────── */

    public function testAgregarUnProductoEsNotaDeDebito(): void
    {
        $a = $this->linea(array('id' => 1, 'unit_price' => 10000, 'net_unit_price' => 10000));
        $b = $this->linea(array('id' => 2, 'unit_price' => 5000, 'net_unit_price' => 5000));
        $c = $this->linea(array('id' => 0, 'product_id' => 9, 'product_code' => 'C',
            'unit_price' => 8000, 'net_unit_price' => 8000));

        $r = ajuste_comparar(array($a, $b), array($a, $b, $c));

        $this->assertSame('NOTA_DEBITO', $r['tipo']);
        $this->assertSame('04', $r['codigo_referencia']);
        $this->assertEqualsWithDelta(8000 * 1.13, $r['total_nota'], 0.0001);
    }

    public function testAgregarVariosProductosSumaTodosEnLaNota(): void
    {
        $a = $this->linea(array('id' => 1, 'unit_price' => 10000, 'net_unit_price' => 10000));
        $c = $this->linea(array('id' => 0, 'product_id' => 9, 'product_code' => 'C',
            'unit_price' => 8000, 'net_unit_price' => 8000));
        $d = $this->linea(array('id' => 0, 'product_id' => 10, 'product_code' => 'D',
            'unit_price' => 2000, 'net_unit_price' => 2000));

        $r = ajuste_comparar(array($a), array($a, $c, $d));

        $this->assertSame('NOTA_DEBITO', $r['tipo']);
        $this->assertCount(2, $r['lineas_nota']);
        $this->assertEqualsWithDelta(10000 * 1.13, $r['total_nota'], 0.0001);
    }

    /* ───────────────────────── cantidades ───────────────────────── */

    public function testReducirLaCantidadEsNotaDeCredito(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 10)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 7)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertSame('NOTA_CREDITO', $r['tipo']);
        // La nota declara la diferencia, no el estado final: 3 unidades.
        $this->assertEqualsWithDelta(3, $r['lineas_nota'][0]['cantidad'], 0.0001);
        $this->assertEqualsWithDelta(1000, $r['lineas_nota'][0]['neto'], 0.0001);
        $this->assertEqualsWithDelta(3 * 1000 * 1.13, $r['total_nota'], 0.0001);
    }

    public function testAumentarLaCantidadEsNotaDeDebito(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 5)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 8)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertSame('NOTA_DEBITO', $r['tipo']);
        $this->assertEqualsWithDelta(3, $r['lineas_nota'][0]['cantidad'], 0.0001);
        $this->assertEqualsWithDelta(3 * 1000 * 1.13, $r['total_nota'], 0.0001);
    }

    /* ───────────────────────── precios ───────────────────────── */

    public function testBajarElPrecioEsNotaDeCredito(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 5000, 'net_unit_price' => 5000)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 4000, 'net_unit_price' => 4000)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertSame('NOTA_CREDITO', $r['tipo']);
        // Un cambio de precio no tiene "unidades de diferencia": la nota sale a
        // tanto alzado por la diferencia neta de la linea.
        $this->assertEqualsWithDelta(1, $r['lineas_nota'][0]['cantidad'], 0.0001);
        $this->assertEqualsWithDelta(2000, $r['lineas_nota'][0]['neto'], 0.0001);
        $this->assertEqualsWithDelta(2000 * 1.13, $r['total_nota'], 0.0001);
    }

    public function testSubirElPrecioEsNotaDeDebito(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 5000, 'net_unit_price' => 5000)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 6000, 'net_unit_price' => 6000)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertSame('NOTA_DEBITO', $r['tipo']);
        $this->assertEqualsWithDelta(2000 * 1.13, $r['total_nota'], 0.0001);
    }

    /* ───────────────────────── descuentos ───────────────────────── */

    public function testAumentarElDescuentoEsNotaDeCredito(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 1000, 'net_unit_price' => 1000)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 1000, 'net_unit_price' => 900)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertSame('NOTA_CREDITO', $r['tipo']);
        $this->assertEqualsWithDelta(200 * 1.13, $r['total_nota'], 0.0001);
    }

    public function testReducirElDescuentoEsNotaDeDebito(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 1000, 'net_unit_price' => 900)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 1000, 'net_unit_price' => 1000)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertSame('NOTA_DEBITO', $r['tipo']);
        $this->assertEqualsWithDelta(200 * 1.13, $r['total_nota'], 0.0001);
    }

    /** La linea a tanto alzado no puede arrastrar el descuento unitario del original. */
    public function testElAjusteATantoAlzadoNoDuplicaElDescuento(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 1000, 'net_unit_price' => 900)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 1000, 'net_unit_price' => 800)));

        $r = ajuste_comparar($antes, $ahora);

        $linea = $r['lineas_nota'][0];
        $this->assertEqualsWithDelta($linea['neto'], $linea['bruto'], 0.0001);
        $this->assertEqualsWithDelta(0, $linea['descuento'], 0.0001);
    }

    /* ───────────────────────── escenarios mixtos ───────────────────────── */

    public function testQuitarYAgregarSeResuelvePorElEfectoNeto(): void
    {
        // A 10.000 + B 5.000 = 15.000  →  A 10.000 + C 20.000 = 30.000
        $a = $this->exento(1, 'A', 10000);
        $b = $this->exento(2, 'B', 5000);
        $c = $this->exento(3, 'C', 20000);

        $r = ajuste_comparar(array($a, $b), array($a, $c));

        // No manda "elimino una linea": manda que la factura sube 15.000.
        $this->assertSame('NOTA_DEBITO', $r['tipo']);
        $this->assertEqualsWithDelta(15000, $r['delta']['total'], 0.0001);
        $this->assertTrue($r['mixto']);
        $this->assertContains('ajuste_aviso_mixto', $r['avisos']);
    }

    public function testElAjusteMixtoAvisaQueLaNotaNoCubreElNeto(): void
    {
        $a = $this->exento(1, 'A', 10000);
        $b = $this->exento(2, 'B', 5000);
        $c = $this->exento(3, 'C', 20000);

        $r = ajuste_comparar(array($a, $b), array($a, $c));

        // La nota de debito solo puede llevar el aumento; la baja de B necesita
        // su propio documento y por eso el motor lo dice en vez de inventarlo.
        $this->assertEqualsWithDelta(20000, $r['total_nota'], 0.0001);
        $this->assertNotEqualsWithDelta($r['delta']['total'], $r['total_nota'], 0.0001);
    }

    public function testUnCambioMixtoQueTerminaBajandoEsNotaDeCredito(): void
    {
        $a = $this->exento(1, 'A', 10000);
        $b = $this->exento(2, 'B', 20000);
        $c = $this->exento(3, 'C', 5000);

        $r = ajuste_comparar(array($a, $b), array($a, $c));

        $this->assertSame('NOTA_CREDITO', $r['tipo']);
        $this->assertEqualsWithDelta(-15000, $r['delta']['total'], 0.0001);
    }

    /* ───────────────────── cambios sin efecto economico ───────────────────── */

    public function testDosPorCincoMilContraUnoPorDiezMilNoGeneraDocumento(): void
    {
        $antes = array($this->linea(array('id' => 0, 'quantity' => 2, 'unit_price' => 5000, 'net_unit_price' => 5000)));
        $ahora = array($this->linea(array('id' => 0, 'quantity' => 1, 'unit_price' => 10000, 'net_unit_price' => 10000)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertSame('SIN_CAMBIOS', $r['tipo']);
        $this->assertSame('sin_efecto', $r['bloqueo']['clave']);
    }

    public function testCambiosQueSeCompensanExactamenteNoGeneranDocumento(): void
    {
        $a = $this->exento(1, 'A', 10000);
        $b = $this->exento(2, 'B', 5000);
        $c = $this->exento(3, 'C', 5000);

        $r = ajuste_comparar(array($a, $b), array($a, $c));

        $this->assertSame('sin_efecto', $r['bloqueo']['clave']);
        $this->assertSame(array(), $r['lineas_nota']);
    }

    /* ───────────────────── credito por el total exacto ───────────────────── */

    public function testUnaNotaPorElTotalExactoSeDeclaraComoAnulacion(): void
    {
        // Bajar la unica linea a cero deja el comprobante sin valor: es una
        // anulacion, y con el codigo 06 Hacienda no la leeria como tal.
        $antes = array($this->linea(array('id' => 1, 'quantity' => 4)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 4, 'unit_price' => 0, 'net_unit_price' => 0)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertSame('ANULACION', $r['tipo']);
        $this->assertSame('01', $r['codigo_referencia']);
    }

    /* ───────────────────────── saldo ajustable ───────────────────────── */

    public function testUnaNotaDeCreditoPreviaConsumeSaldo(): void
    {
        $saldo = ajuste_saldo(11300, array(
            array('tipo' => 'NC', 'total' => 3390, 'estado' => 'aceptado'),
        ));

        $this->assertEqualsWithDelta(3390, $saldo['acreditado'], 0.0001);
        $this->assertEqualsWithDelta(7910, $saldo['ajustable'], 0.0001);
    }

    public function testUnaNotaDeDebitoPreviaAumentaElSaldo(): void
    {
        $saldo = ajuste_saldo(11300, array(
            array('tipo' => 'ND', 'total' => 2000, 'estado' => 'aceptado'),
        ));

        $this->assertEqualsWithDelta(13300, $saldo['ajustable'], 0.0001);
    }

    public function testUnaNotaRechazadaNoConsumeSaldo(): void
    {
        // Hacienda no la acepto: no corrigio nada y su monto sigue disponible.
        $saldo = ajuste_saldo(11300, array(
            array('tipo' => 'NC', 'total' => 3390, 'estado' => 'rechazado'),
        ));

        $this->assertEqualsWithDelta(0, $saldo['acreditado'], 0.0001);
        $this->assertEqualsWithDelta(11300, $saldo['ajustable'], 0.0001);
    }

    public function testNoSePuedeAcreditarDosVecesLaMismaCorreccion(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 10)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 7)));

        $r = ajuste_comparar($antes, $ahora, array(
            'total_original' => 11300,
            'notas_previas'  => array(
                // Ya se acredito todo lo acreditable de esta factura.
                array('tipo' => 'NC', 'total' => 11300, 'estado' => 'aceptado'),
            ),
        ));

        $this->assertSame('excede_saldo', $r['bloqueo']['clave']);
    }

    public function testUnaCorreccionQueCabeEnElSaldoNoSeBloquea(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 10)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 7)));

        $r = ajuste_comparar($antes, $ahora, array(
            'total_original' => 11300,
            'notas_previas'  => array(
                array('tipo' => 'NC', 'total' => 1130, 'estado' => 'aceptado'),
            ),
        ));

        $this->assertNull($r['bloqueo']);
        $this->assertSame('NOTA_CREDITO', $r['tipo']);
    }

    public function testUnaNotaDeDebitoNoSeBloqueaPorSaldo(): void
    {
        // El saldo limita cuanto se puede devolver, no cuanto se puede cobrar de mas.
        $antes = array($this->linea(array('id' => 1, 'quantity' => 5)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 8)));

        $r = ajuste_comparar($antes, $ahora, array(
            'total_original' => 5650,
            'notas_previas'  => array(array('tipo' => 'NC', 'total' => 5650, 'estado' => 'aceptado')),
        ));

        $this->assertSame('NOTA_DEBITO', $r['tipo']);
        $this->assertNull($r['bloqueo']);
    }

    /* ───────────────────────── apareo de lineas ───────────────────────── */

    public function testDosLineasDelMismoProductoSeAparejanUnaAUna(): void
    {
        $l1 = $this->linea(array('id' => 0, 'quantity' => 1));
        $l2 = $this->linea(array('id' => 0, 'quantity' => 1));

        // Se quita una de las dos: la otra tiene que quedar apareada, no contarse
        // como eliminada y agregada a la vez.
        $r = ajuste_comparar(array($l1, $l2), array($l1));

        $this->assertSame('NOTA_CREDITO', $r['tipo']);
        $this->assertCount(1, $r['lineas_nota']);
        $this->assertEqualsWithDelta(1000 * 1.13, $r['total_nota'], 0.0001);
    }

    public function testElApareoNoDependeDeLaPosicion(): void
    {
        $a = $this->linea(array('id' => 1, 'product_name' => 'A'));
        $b = $this->linea(array('id' => 2, 'product_name' => 'B', 'unit_price' => 500, 'net_unit_price' => 500));
        $c = $this->linea(array('id' => 3, 'product_name' => 'C', 'unit_price' => 300, 'net_unit_price' => 300));

        // Se elimina la del medio: las de abajo cambian de indice pero no de identidad.
        $r = ajuste_comparar(array($a, $b, $c), array($c, $a));

        $this->assertCount(1, $r['lineas_nota']);
        $this->assertSame('B', $r['lineas_nota'][0]['nombre']);
    }

    /* ───────────────────── ajuste a tanto alzado ───────────────────── */

    /**
     * Una correccion de precio no devuelve mercancia: la linea se marca para
     * que el emisor la guarde sin producto y el inventario no se mueva.
     */
    public function testElAjusteDePrecioSeMarcaComoParcial(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 5000, 'net_unit_price' => 5000)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 2, 'unit_price' => 4000, 'net_unit_price' => 4000)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertTrue(!empty($r['lineas_nota'][0]['ajuste_parcial']));
    }

    /** Bajar la cantidad si devuelve mercancia: esa linea no va marcada. */
    public function testLaDevolucionDeUnidadesNoSeMarcaComoParcial(): void
    {
        $antes = array($this->linea(array('id' => 1, 'quantity' => 10)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 7)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertArrayNotHasKey('ajuste_parcial', $r['lineas_nota'][0]);
    }

    public function testLaLineaEliminadaTampocoSeMarcaComoParcial(): void
    {
        $a = $this->linea(array('id' => 1));
        $b = $this->linea(array('id' => 2, 'product_name' => 'B'));

        $r = ajuste_comparar(array($a, $b), array($a));

        $this->assertArrayNotHasKey('ajuste_parcial', $r['lineas_nota'][0]);
    }

    /* ─────────────────── impuesto incluido en el precio ─────────────────── */

    /**
     * Con impuesto incluido, `tax` viene vacia y `item_tax` es la porcion
     * contenida en el precio, no un monto que se sume. El total del documento
     * es la base sola, y la nota tiene que declarar el impuesto igual.
     */
    public function testConImpuestoIncluidoElTotalEsLaBaseSola(): void
    {
        $l = $this->linea(array(
            'id' => 1, 'quantity' => 3, 'unit_price' => 650, 'net_unit_price' => 650,
            'tax' => '', 'item_tax' => 224.3363,
        ));

        $r = ajuste_comparar(array($l), array());

        $this->assertEqualsWithDelta(1950, $r['original']['total'], 0.0001);
        $this->assertEqualsWithDelta(0, $r['original']['impuesto'], 0.0001);
    }

    public function testLaLineaGuardaLaRazonDeImpuestoParaProrratearla(): void
    {
        $l = ajuste_linea($this->linea(array(
            'quantity' => 3, 'unit_price' => 650, 'net_unit_price' => 650,
            'tax' => '', 'item_tax' => 224.3363,
        )));

        // 224.3363 / (3 x 650) — la nota parcial la aplica sobre su propia base.
        $this->assertEqualsWithDelta(0.11504425, $l['imp_ratio'], 0.0000001);
        $this->assertSame('', $l['tax_txt']);
    }

    public function testLaRazonDeImpuestoSobreviveAlaLineaDeNota(): void
    {
        $antes = array($this->linea(array(
            'id' => 1, 'quantity' => 4, 'unit_price' => 650, 'net_unit_price' => 650,
            'tax' => '', 'item_tax' => 299.115,
        )));
        $ahora = array($this->linea(array(
            'id' => 1, 'quantity' => 1, 'unit_price' => 650, 'net_unit_price' => 650,
            'tax' => '', 'item_tax' => 299.115,
        )));

        $r = ajuste_comparar($antes, $ahora);
        $nota = $r['lineas_nota'][0];

        $this->assertEqualsWithDelta(3, $nota['cantidad'], 0.0001);
        // La nota conserva como se facturo el impuesto para poder declararlo.
        $this->assertEqualsWithDelta(0.11504423, $nota['imp_ratio'], 0.0000001);
        $this->assertSame('', $nota['tax_txt']);
    }

    public function testLaTarifaAgregadaSigueViajandoComoTexto(): void
    {
        $l = ajuste_linea($this->linea(array('quantity' => 1, 'tax' => '13%', 'item_tax' => 130)));

        $this->assertSame('13%', $l['tax_txt']);
        $this->assertEqualsWithDelta(13, $l['tasa'], 0.0001);
    }

    /* ───────────────────────── coherencia de totales ───────────────────────── */

    public function testElTotalDeLaNotaCuadraConSusLineas(): void
    {
        $antes = array(
            $this->linea(array('id' => 1, 'quantity' => 4, 'unit_price' => 2500, 'net_unit_price' => 2500)),
            $this->linea(array('id' => 2, 'quantity' => 1, 'unit_price' => 7000, 'net_unit_price' => 7000)),
        );
        $ahora = array(
            $this->linea(array('id' => 1, 'quantity' => 1, 'unit_price' => 2500, 'net_unit_price' => 2500)),
        );

        $r = ajuste_comparar($antes, $ahora);

        $suma = 0;
        foreach ($r['lineas_nota'] as $l) {
            $suma += $l['subtotal'] + $l['impuesto'];
        }
        $this->assertEqualsWithDelta($r['total_nota'], $suma, 0.0001);
        // Con cambios en una sola direccion, la nota tiene que valer el delta.
        $this->assertEqualsWithDelta(abs($r['delta']['total']), $r['total_nota'], 0.0001);
    }

    public function testElImpuestoSeRecalculaSobreLaCantidadNueva(): void
    {
        // La linea editada llega con la cantidad nueva y el item_tax viejo: si el
        // motor sumara item_tax en vez de recalcular, la nota saldria descuadrada.
        $antes = array($this->linea(array('id' => 1, 'quantity' => 10, 'item_tax' => 1300)));
        $ahora = array($this->linea(array('id' => 1, 'quantity' => 4,  'item_tax' => 1300)));

        $r = ajuste_comparar($antes, $ahora);

        $this->assertEqualsWithDelta(4 * 1000 * 0.13, $r['nuevo']['impuesto'], 0.0001);
        $this->assertEqualsWithDelta(6 * 1000 * 0.13, abs($r['delta']['impuesto']), 0.0001);
    }

    public function testElDesgloseListaCadaCambio(): void
    {
        $a = $this->linea(array('id' => 1, 'quantity' => 10));
        $b = $this->linea(array('id' => 2, 'product_name' => 'B', 'unit_price' => 500, 'net_unit_price' => 500));

        $r = ajuste_comparar(array($a, $b), array($this->linea(array('id' => 1, 'quantity' => 6))));

        $tipos = array_column($r['cambios'], 'tipo');
        $this->assertContains('modificada', $tipos);
        $this->assertContains('eliminada', $tipos);
        $this->assertCount(2, $r['cambios']);
    }
}

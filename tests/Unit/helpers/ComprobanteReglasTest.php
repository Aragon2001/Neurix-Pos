<?php

use PHPUnit\Framework\TestCase;

/**
 * Qué comprobante admite un cliente y cuándo se le puede fiar.
 *
 * Estas reglas deciden lo que el POS ofrece y lo que `Pos.php` vuelve a
 * comprobar al cobrar; el rechazo de Hacienda llega días después de emitir, así
 * que es acá donde tienen que fallar los casos malos.
 */
class ComprobanteReglasTest extends TestCase
{
    private function cliente(array $campos = array()): object
    {
        return (object) array_merge(array(
            'id' => 2, 'name' => 'Comercial Los Robles SA',
            'cf1' => '02', 'cf2' => '3101987654', 'limitcredit' => 0, 'dias_credito' => 0,
        ), $campos);
    }

    private function ajustes(array $campos = array()): object
    {
        return (object) array_merge(array('enable_credit' => 1), $campos);
    }

    /* ── Facturable ── */

    public function testUnClienteIdentificadoEsFacturable(): void
    {
        $this->assertTrue(cliente_facturable($this->cliente())['ok']);
    }

    public function testSinCedulaNoHayFactura(): void
    {
        $r = cliente_facturable($this->cliente(array('cf2' => '')));
        $this->assertFalse($r['ok']);
        $this->assertSame('fact_falta_cedula', $r['motivo']);
    }

    public function testUnaCedulaJuridicaDeNueveDigitosNoSirve(): void
    {
        $r = cliente_facturable($this->cliente(array('cf2' => '310198765')));
        $this->assertFalse($r['ok']);
        $this->assertSame('ident_juridica_10', $r['motivo']);
    }

    public function testSinNombreNoHayFactura(): void
    {
        $r = cliente_facturable($this->cliente(array('name' => 'AB')));
        $this->assertFalse($r['ok']);
        $this->assertSame('fact_falta_nombre', $r['motivo']);
    }

    public function testElExtranjeroNoDomiciliadoVaEnTiquete(): void
    {
        // El 05 en una factura exige condición de venta 12, que el POS no maneja.
        $r = cliente_facturable($this->cliente(array('cf1' => '05', 'cf2' => 'X1234567')));
        $this->assertFalse($r['ok']);
        $this->assertSame('fact_extranjero_tiquete', $r['motivo']);
    }

    public function testElNoContribuyenteNuncaEsReceptorDeVenta(): void
    {
        $r = cliente_facturable($this->cliente(array('cf1' => '06', 'cf2' => 'NC01')));
        $this->assertFalse($r['ok']);
        $this->assertSame('fact_no_contribuyente', $r['motivo']);
    }

    /* ── Qué comprobantes se ofrecen ── */

    public function testElTiqueteSiempreSePuedeEmitir(): void
    {
        foreach (array($this->cliente(), $this->cliente(array('cf2' => '')), (object) array('id' => 1)) as $c) {
            $this->assertTrue(comprobantes_permitidos($c, 1)['04']['ok']);
        }
    }

    public function testAlClienteDePasoNoSeLeFactura(): void
    {
        $r = comprobantes_permitidos((object) array('id' => 1, 'name' => 'Cliente de paso'), 1);
        $this->assertFalse($r['01']['ok']);
        $this->assertSame('fact_cliente_de_paso', $r['01']['motivo']);
    }

    public function testElClienteDePasoSaleDelAjusteYNoDelUno(): void
    {
        // Con el ajuste en 9, el cliente 2 vuelve a ser facturable.
        $r = comprobantes_permitidos($this->cliente(), 9);
        $this->assertTrue($r['01']['ok']);
    }

    /* ── Crédito ── */

    public function testConElCreditoApagadoNoSeFia(): void
    {
        $r = puede_vender_a_credito($this->cliente(array('limitcredit' => 100000)), 5000, 0,
                                    $this->ajustes(array('enable_credit' => 0)));
        $this->assertFalse($r['ok']);
        $this->assertSame('credito_deshabilitado', $r['motivo']);
    }

    public function testSinLimiteNoSeFia(): void
    {
        $r = puede_vender_a_credito($this->cliente(array('limitcredit' => 0)), 5000, 0, $this->ajustes());
        $this->assertFalse($r['ok']);
        $this->assertSame('credito_sin_limite', $r['motivo']);
    }

    public function testAlClienteDePasoNoSeLeFia(): void
    {
        $r = puede_vender_a_credito((object) array('id' => 1, 'limitcredit' => 999999), 5000, 0, $this->ajustes(), 1);
        $this->assertFalse($r['ok']);
        $this->assertSame('credito_cliente_de_paso', $r['motivo']);
    }

    public function testDentroDelLimiteSeFia(): void
    {
        $r = puede_vender_a_credito($this->cliente(array('limitcredit' => 100000)), 30000, 20000, $this->ajustes());
        $this->assertTrue($r['ok']);
        $this->assertSame(80000.0, $r['disponible']);
    }

    public function testPorEncimaDelLimiteDiceCuantoFalta(): void
    {
        $r = puede_vender_a_credito($this->cliente(array('limitcredit' => 100000)), 95000, 20000, $this->ajustes());

        $this->assertFalse($r['ok']);
        $this->assertSame('credito_excede_limite', $r['motivo']);
        $this->assertSame(80000.0, $r['disponible']);
        $this->assertSame(15000.0, $r['faltante']);
    }

    public function testLaDeudaSeDescuentaDelDisponible(): void
    {
        $this->assertSame(40000.0, credito_disponible($this->cliente(array('limitcredit' => 100000)), 60000));
        $this->assertSame(-5000.0, credito_disponible($this->cliente(array('limitcredit' => 100000)), 105000));
    }

    /* ── Plazo ── */

    public function testElPlazoSaleDelClienteYSiNoDelValorPorDefecto(): void
    {
        $this->assertSame(15, plazo_credito_dias($this->cliente(array('dias_credito' => 15))));
        $this->assertSame(30, plazo_credito_dias($this->cliente()));
        $this->assertSame(8, plazo_credito_dias($this->cliente(), 8));
    }
}

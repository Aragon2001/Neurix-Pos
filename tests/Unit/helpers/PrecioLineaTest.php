<?php

use PHPUnit\Framework\TestCase;

/**
 * Precio y descuento de una línea del POS.
 *
 * El comprobante se firma sobre estos números y el navegador los propone: esta
 * regla es lo único que separa una venta legítima de una peticion armada a mano.
 */
class PrecioLineaTest extends TestCase
{
    private const LISTA = array(1250.0);

    public function testElPrecioDeListaPasa(): void
    {
        $r = precio_de_linea_admisible(self::LISTA, 1250, '0', 100, false);
        $this->assertTrue($r['ok']);
        $this->assertSame('', $r['motivo']);
    }

    public function testUnPrecioMayorAlDeListaPasa(): void
    {
        // Vender más caro no es un fraude contra el negocio.
        $this->assertTrue(precio_de_linea_admisible(self::LISTA, 1500, '0', 100, false)['ok']);
    }

    public function testElCajeroNoPuedeVenderPorDebajoDeLista(): void
    {
        $r = precio_de_linea_admisible(self::LISTA, 1, '0', 100, false);

        $this->assertFalse($r['ok']);
        $this->assertSame('precio_bajo_lista', $r['motivo']);
        $this->assertSame(1250.0, $r['minimo']);
    }

    public function testElAdministradorSiPuedeYQuedaSenalado(): void
    {
        $r = precio_de_linea_admisible(self::LISTA, 1, '0', 100, true);

        $this->assertTrue($r['ok']);
        $this->assertSame('precio_bajo_lista', $r['motivo'], 'sigue marcado para la bitácora');
    }

    public function testElPrecioDeOfertaEsLegitimo(): void
    {
        // El mínimo de los precios legítimos es el que manda.
        $r = precio_de_linea_admisible(array(1250.0, 999.0), 999, '0', 100, false);
        $this->assertTrue($r['ok']);
    }

    public function testUnaListaDePreciosMasBarataEsLegitima(): void
    {
        $r = precio_de_linea_admisible(array(1250.0, 1100.0, 950.0), 950, '0', 100, false);
        $this->assertTrue($r['ok']);
    }

    public function testElRedondeoDelNavegadorNoBloqueaLaVenta(): void
    {
        $r = precio_de_linea_admisible(array(1250.0), 1249.9995, '0', 100, false);
        $this->assertTrue($r['ok'], 'una milésima de tolerancia');
    }

    public function testElDescuentoSobreElTopeSeRechaza(): void
    {
        $r = precio_de_linea_admisible(self::LISTA, 1250, '30%', 10, true);

        $this->assertFalse($r['ok']);
        $this->assertSame('descuento_sobre_tope', $r['motivo']);
        $this->assertSame(30.0, $r['descuento_pct']);
    }

    public function testElDescuentoEnMontoTambienCuentaContraElTope(): void
    {
        // 500 sobre 1250 es 40%.
        $r = precio_de_linea_admisible(self::LISTA, 1250, '500', 10, true);

        $this->assertFalse($r['ok']);
        $this->assertSame('descuento_sobre_tope', $r['motivo']);
        $this->assertEqualsWithDelta(40.0, $r['descuento_pct'], 0.001);
    }

    public function testElDescuentoDentroDelTopePasa(): void
    {
        $this->assertTrue(precio_de_linea_admisible(self::LISTA, 1250, '10%', 10, false)['ok']);
    }

    public function testConTopeCeroNoSeAdmiteNingunDescuento(): void
    {
        $this->assertTrue(precio_de_linea_admisible(self::LISTA, 1250, '0', 0, false)['ok']);
        $this->assertFalse(precio_de_linea_admisible(self::LISTA, 1250, '1%', 0, false)['ok']);
    }

    public function testUnArticuloSinPrecioDeListaNoSeBloquea(): void
    {
        // Artículo rápido: no hay contra qué comparar.
        $r = precio_de_linea_admisible(array(), 1, '0', 100, false);
        $this->assertTrue($r['ok']);
        $this->assertSame(0.0, $r['minimo']);
    }

    public function testLosPreciosEnCeroNoCuentanComoLista(): void
    {
        $r = precio_de_linea_admisible(array(0.0, 1250.0, null), 1250, '0', 100, false);
        $this->assertTrue($r['ok']);
        $this->assertSame(1250.0, $r['minimo']);
    }
}

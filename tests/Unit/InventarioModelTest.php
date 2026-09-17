<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/stubs/InventarioEntorno.php';

/**
 * Reglas del movimiento de inventario: qué saldo queda, qué se rechaza y qué
 * queda registrado. Son las que no se pueden comprobar leyendo el código.
 */
class InventarioModelTest extends TestCase
{
    private function modelo($existencia = 10, $costo = 100, $precio = 150)
    {
        return inventario_modelo(
            array(array('id' => 1, 'cost' => $costo, 'price' => $precio, 'name' => 'Arroz')),
            array(array('product_id' => 1, 'store_id' => 1, 'quantity' => $existencia, 'qty_fracc' => 0, 'price' => $precio))
        );
    }

    private function existencia($modelo)
    {
        return (float) $modelo->db->tablas['product_store_qty'][0]['quantity'];
    }

    public function testEntradaSumaALaExistencia(): void
    {
        $m = $this->modelo(10);
        $r = $m->aplicarMovimiento(array('modo' => 'entrada', 'product_id' => 1, 'quantity' => 4));

        $this->assertTrue($r['ok']);
        $this->assertSame(14.0, $this->existencia($m));
        $this->assertSame(10.0, (float) $r['qty_antes']);
        $this->assertSame(14.0, (float) $r['qty_despues']);
    }

    public function testSalidaRestaDeLaExistencia(): void
    {
        $m = $this->modelo(10);
        $m->aplicarMovimiento(array('modo' => 'salida', 'product_id' => 1, 'quantity' => 3));

        $this->assertSame(7.0, $this->existencia($m));
    }

    public function testConteoReemplazaLaExistencia(): void
    {
        $m = $this->modelo(10);
        $m->aplicarMovimiento(array('modo' => 'conteo', 'product_id' => 1, 'quantity' => 4));

        $this->assertSame(4.0, $this->existencia($m));
    }

    public function testElConteoPuedeDejarLaExistenciaEnCero(): void
    {
        $m = $this->modelo(10);
        $r = $m->aplicarMovimiento(array('modo' => 'conteo', 'product_id' => 1, 'quantity' => 0));

        $this->assertTrue($r['ok']);
        $this->assertSame(0.0, $this->existencia($m));
    }

    public function testElPrecioPuedeQuedarEnCero(): void
    {
        $m = $this->modelo(10);
        $m->aplicarMovimiento(array('modo' => 'precio', 'product_id' => 1, 'price' => 0));

        $this->assertSame(0.0, (float) $m->db->tablas['product_store_qty'][0]['price']);
        $this->assertSame(0.0, (float) $m->db->tablas['products'][0]['price']);
    }

    public function testLaSalidaNoDejaLaExistenciaNegativa(): void
    {
        $m = $this->modelo(2);
        $r = $m->aplicarMovimiento(array('modo' => 'salida', 'product_id' => 1, 'quantity' => 5));

        $this->assertFalse($r['ok']);
        $this->assertSame('existencia_negativa', $r['error']);
        $this->assertSame(2.0, $this->existencia($m), 'la existencia no debe tocarse cuando el movimiento se rechaza');
        $this->assertCount(0, $m->db->tablas['mov_inventario']);
    }

    public function testLaSalidaNegativaSeAdmiteSiSePideExpresamente(): void
    {
        $m = $this->modelo(2);
        $r = $m->aplicarMovimiento(array('modo' => 'salida', 'product_id' => 1, 'quantity' => 5, 'permitir_negativo' => true));

        $this->assertTrue($r['ok']);
        $this->assertSame(-3.0, $this->existencia($m));
    }

    public function testUnMovimientoSinCantidadNoMueveLaExistencia(): void
    {
        $m = $this->modelo(10);
        $m->aplicarMovimiento(array('modo' => 'entrada', 'product_id' => 1, 'price' => 200));

        $this->assertSame(10.0, $this->existencia($m));
        $this->assertSame(200.0, (float) $m->db->tablas['product_store_qty'][0]['price']);
    }

    public function testLaEntradaRecalculaElCostoPromedio(): void
    {
        // 10 a 100 mas 10 a 200 promedia 150.
        $m = $this->modelo(10, 100);
        $m->aplicarMovimiento(array('modo' => 'entrada', 'product_id' => 1, 'quantity' => 10, 'cost' => 200));

        $this->assertSame(150.0, (float) $m->db->tablas['products'][0]['cost']);
    }

    public function testElConteoFijaElCostoSinPromediar(): void
    {
        $m = $this->modelo(10, 100);
        $m->aplicarMovimiento(array('modo' => 'conteo', 'product_id' => 1, 'quantity' => 10, 'cost' => 250));

        $this->assertSame(250.0, (float) $m->db->tablas['products'][0]['cost']);
    }

    public function testElMovimientoGuardaElSaldoAntesYDespues(): void
    {
        $m = $this->modelo(10);
        $m->aplicarMovimiento(array('modo' => 'entrada', 'product_id' => 1, 'quantity' => 5, 'descripcion_mov' => 'Recibido'));

        $mov = $m->db->tablas['mov_inventario'][0];
        $this->assertSame(10.0, (float) $mov['qty_antes']);
        $this->assertSame(15.0, (float) $mov['qty_despues']);
        $this->assertSame(1, (int) $mov['store_id']);
        $this->assertSame(1, (int) $mov['tipo_mov'], 'entrada es el tipo 1');
        $this->assertSame('Recibido', $mov['descripcion_mov']);
    }

    public function testElProductoInexistenteSeRechaza(): void
    {
        $m = $this->modelo(10);
        $r = $m->aplicarMovimiento(array('modo' => 'entrada', 'product_id' => 99, 'quantity' => 1));

        $this->assertFalse($r['ok']);
        $this->assertSame('producto_inexistente', $r['error']);
    }

    public function testElProductoSinFilaDeTiendaLaCrea(): void
    {
        $m = inventario_modelo(
            array(array('id' => 1, 'cost' => 50, 'price' => 80, 'name' => 'Nuevo')),
            array()
        );
        $r = $m->aplicarMovimiento(array('modo' => 'entrada', 'product_id' => 1, 'quantity' => 6));

        $this->assertTrue($r['ok']);
        $this->assertSame(6.0, $this->existencia($m));
    }

    public function testUnaSesionEntraCompletaONoEntra(): void
    {
        $m = inventario_modelo(
            array(
                array('id' => 1, 'cost' => 100, 'price' => 150, 'name' => 'Arroz'),
                array('id' => 2, 'cost' => 100, 'price' => 150, 'name' => 'Frijol'),
            ),
            array(
                array('product_id' => 1, 'store_id' => 1, 'quantity' => 10, 'qty_fracc' => 0, 'price' => 150),
                array('product_id' => 2, 'store_id' => 1, 'quantity' => 1,  'qty_fracc' => 0, 'price' => 150),
            )
        );

        $r = $m->aplicarSesion(array(
            array('modo' => 'salida', 'product_id' => 1, 'quantity' => 2),
            array('modo' => 'salida', 'product_id' => 2, 'quantity' => 9),
        ));

        $this->assertFalse($r['ok']);
        $this->assertSame('existencia_negativa', $r['errores'][0]['error']);
        $this->assertSame(10.0, (float) $m->db->tablas['product_store_qty'][0]['quantity'], 'la primera línea se deshace con la segunda');
        $this->assertCount(0, $m->db->tablas['mov_inventario']);
    }

    public function testUnaSesionValidaAplicaTodasSusLineas(): void
    {
        $m = inventario_modelo(
            array(
                array('id' => 1, 'cost' => 100, 'price' => 150, 'name' => 'Arroz'),
                array('id' => 2, 'cost' => 100, 'price' => 150, 'name' => 'Frijol'),
            ),
            array(
                array('product_id' => 1, 'store_id' => 1, 'quantity' => 10, 'qty_fracc' => 0, 'price' => 150),
                array('product_id' => 2, 'store_id' => 1, 'quantity' => 10, 'qty_fracc' => 0, 'price' => 150),
            )
        );

        $r = $m->aplicarSesion(array(
            array('modo' => 'conteo', 'product_id' => 1, 'quantity' => 8),
            array('modo' => 'entrada', 'product_id' => 2, 'quantity' => 5),
        ));

        $this->assertTrue($r['ok']);
        $this->assertSame(2, $r['aplicadas']);
        $this->assertSame(8.0,  (float) $m->db->tablas['product_store_qty'][0]['quantity']);
        $this->assertSame(15.0, (float) $m->db->tablas['product_store_qty'][1]['quantity']);
        $this->assertCount(2, $m->db->tablas['mov_inventario']);
        $this->assertSame(
            $m->db->tablas['mov_inventario'][0]['id_sesion'],
            $m->db->tablas['mov_inventario'][1]['id_sesion'],
            'las líneas de una sesión comparten identificador'
        );
    }

    public function testLosCodigosDeMovimientoNoCambian(): void
    {
        $m = $this->modelo();
        $this->assertSame(0, $m->tipoMovimiento('salida'));
        $this->assertSame(1, $m->tipoMovimiento('entrada'));
        $this->assertSame(2, $m->tipoMovimiento('conteo'));
        $this->assertSame(3, $m->tipoMovimiento('precio'));
    }
}

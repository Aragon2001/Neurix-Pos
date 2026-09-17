<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class PosHelperTest extends TestCase
{
    // --- invert_tax_price ---

    public function test_invert_tax_price_iva_13(): void
    {
        // precio con IVA 13% incluido → base debe ser ~100
        $result = (float) invert_tax_price(113, 13);
        $this->assertEqualsWithDelta(100.0, $result, 0.0001);
    }

    public function test_invert_tax_price_sin_impuesto(): void
    {
        $result = (float) invert_tax_price(100, 0);
        $this->assertEqualsWithDelta(100.0, $result, 0.0001);
    }

    public function test_invert_tax_price_iva_4(): void
    {
        $result = (float) invert_tax_price(104, 4);
        $this->assertEqualsWithDelta(100.0, $result, 0.0001);
    }

    public function test_invert_tax_price_formato_4_decimales(): void
    {
        $result = invert_tax_price(113, 13);
        $this->assertMatchesRegularExpression('/^\d+\.\d{4}$/', $result, 'Debe tener exactamente 4 decimales');
    }

    // --- character_limiter ---

    public function test_character_limiter_sin_truncar(): void
    {
        $this->assertSame('Hola', character_limiter('Hola', 100));
    }

    public function test_character_limiter_trunca(): void
    {
        $result = character_limiter('Una cadena muy larga para truncar aqui', 10);
        $this->assertLessThanOrEqual(13, strlen($result)); // 10 chars + '...'
    }

    // --- drawLine ---

    public function test_drawLine_longitud_correcta(): void
    {
        $line = drawLine(42);
        $this->assertSame(str_repeat('-', 42) . "\n", $line);
    }

    // --- familia_pago / cobros_por_familia ---

    /**
     * El valor de paid_by cambio con el tiempo: 'credit_card' hasta julio de
     * 2026 y 'card' despues. El cierre tiene que sumar las dos como tarjeta.
     */
    public function test_familia_pago_reconoce_las_variantes_de_tarjeta(): void
    {
        foreach (['CC', 'cc', 'card', 'credit_card', 'debit_card', 'stripe'] as $valor) {
            $this->assertSame('tarjeta', familia_pago($valor), "Falló con '$valor'");
        }
    }

    public function test_familia_pago_reconoce_el_resto_de_metodos(): void
    {
        $this->assertSame('efectivo', familia_pago('cash'));
        $this->assertSame('sinpe', familia_pago('sinpe'));
        $this->assertSame('transferencia', familia_pago('transfer'));
        $this->assertSame('transferencia', familia_pago('transdep'));
        $this->assertSame('cheque', familia_pago('Cheque'));
    }

    /** Lo desconocido cae en "otros": nunca debe desaparecer del cierre. */
    public function test_familia_pago_no_pierde_lo_desconocido(): void
    {
        $this->assertSame('otros', familia_pago('bitcoin'));
        $this->assertSame('otros', familia_pago(''));
        $this->assertSame('otros', familia_pago(null));
    }

    public function test_cobros_por_familia_suma_las_variantes_juntas(): void
    {
        $familias = cobros_por_familia([
            'cash'        => 495724.0,
            'credit_card' => 284557.0,
            'card'        => 15000.0,
            'sinpe'       => 155979.0,
            'cheque'      => 169167.0,
        ]);

        $this->assertSame(495724.0, $familias['efectivo']['total']);
        $this->assertSame(299557.0, $familias['tarjeta']['total'], 'Las dos variantes de tarjeta deben sumarse');
        $this->assertSame(155979.0, $familias['sinpe']['total']);
        $this->assertSame(169167.0, $familias['cheque']['total']);
        $this->assertSame(0.0, $familias['transferencia']['total']);
        $this->assertSame(0.0, $familias['otros']['total']);
    }

    /** Un turno sin cobros devuelve todas las familias en cero, no un arreglo vacío. */
    public function test_cobros_por_familia_sin_movimiento(): void
    {
        $familias = cobros_por_familia([]);

        $this->assertCount(6, $familias);
        foreach ($familias as $familia) {
            $this->assertSame(0.0, $familia['total']);
        }
    }

    /* ── rotulo del IVA de una linea ── */

    public function testElRotuloMuestraElPorcentajeDeLaTarifa(): void
    {
        $this->assertSame('IVA 13%', etiqueta_iva(tasa_iva_linea((object) ['tax' => '13%'])));
        $this->assertSame('IVA 0%', etiqueta_iva(tasa_iva_linea((object) ['tax' => '0'])));
        $this->assertSame('IVA 0,5%', etiqueta_iva(tasa_iva_linea((object) ['tax' => '0.5'])));
    }

    public function testUnaTarifaNulaConImpuestoNoSeRotulaComoCero(): void
    {
        // subtotal lleva el impuesto incluido: 1130 = 1000 de base + 130 de IVA.
        $linea = (object) ['tax' => null, 'item_tax' => 130, 'subtotal' => 1130];
        $this->assertSame('IVA 13%', etiqueta_iva(tasa_iva_linea($linea)));
    }
}

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
}

<?php

use PHPUnit\Framework\TestCase;

/**
 * Formato del número de identificación por tipo (Anexos v4.4, nota 4).
 * Hacienda rechaza el comprobante cuando no calza, y el rechazo llega después
 * de emitir: estas reglas son la única defensa previa.
 */
class IdentificacionTest extends TestCase
{
    public function testCedulaFisicaSonNueveDigitos(): void
    {
        $this->assertTrue(identificacion_valida('01', '702860717')['ok']);
        $this->assertTrue(identificacion_valida('01', '7-0286-0717')['ok'], 'los guiones se descartan');
        $this->assertFalse(identificacion_valida('01', '70286071')['ok'], 'ocho dígitos');
        $this->assertFalse(identificacion_valida('01', '7028607170')['ok'], 'diez dígitos');
        $this->assertFalse(identificacion_valida('01', '002860717')['ok'], 'no lleva cero inicial');
    }

    public function testCedulaJuridicaSonDiezDigitos(): void
    {
        $this->assertTrue(identificacion_valida('02', '3101123456')['ok']);
        $this->assertFalse(identificacion_valida('02', '310112345')['ok']);
        $this->assertSame('ident_juridica_10', identificacion_valida('02', '310112345')['error']);
    }

    public function testDimexSonOnceODoceDigitos(): void
    {
        $this->assertTrue(identificacion_valida('03', '12345678901')['ok']);
        $this->assertTrue(identificacion_valida('03', '123456789012')['ok']);
        $this->assertFalse(identificacion_valida('03', '1234567890')['ok']);
        $this->assertFalse(identificacion_valida('03', '01234567890')['ok'], 'sin ceros iniciales');
    }

    public function testNiteSonDiezDigitos(): void
    {
        $this->assertTrue(identificacion_valida('04', '1234567890')['ok']);
        $this->assertFalse(identificacion_valida('04', '123456789')['ok']);
    }

    public function testExtranjeroYNoContribuyenteAdmitenLetras(): void
    {
        $this->assertTrue(identificacion_valida('05', 'X1234567B')['ok']);
        $this->assertTrue(identificacion_valida('06', 'SIN-CEDULA-01')['ok']);
        $this->assertFalse(identificacion_valida('05', str_repeat('A', 21))['ok'], 'el XSD topa en 20');
    }

    public function testElNumeroVacioSiempreFalla(): void
    {
        foreach (array('01', '02', '03', '04', '05', '06') as $tipo) {
            $r = identificacion_valida($tipo, '');
            $this->assertFalse($r['ok']);
            $this->assertSame('ident_falta_numero', $r['error']);
        }
    }

    public function testUnTipoQueNoExisteSeRechaza(): void
    {
        $this->assertFalse(identificacion_valida('07', '123456789')['ok']);
        $this->assertSame('ident_tipo_invalido', identificacion_valida('07', '123456789')['error']);
    }

    public function testLaNormalizacionDependeDelTipo(): void
    {
        $this->assertSame('702860717', normalizar_identificacion('01', '7-0286-0717'));
        $this->assertSame('X1234567B', normalizar_identificacion('05', 'x-1234567 b'));
        $this->assertSame('3101123456', normalizar_identificacion('02', ' 3-101-123456 '));
    }
}

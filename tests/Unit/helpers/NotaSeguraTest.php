<?php

use PHPUnit\Framework\TestCase;

/**
 * La nota del comprobante la escribe el cajero y la lee cualquiera que abra la
 * venta. Antes se mostraba con `decode_html()`, que devolvía a la vida las
 * etiquetas guardadas como entidades.
 */
class NotaSeguraTest extends TestCase
{
    public function testUnaNotaNormalSeMuestraIgual(): void
    {
        $this->assertSame('Entregar en recepción', nota_segura('Entregar en recepción'));
    }

    public function testLosSaltosDeLineaSeConservan(): void
    {
        $this->assertStringContainsString('<br', nota_segura("Primera\nSegunda"));
    }

    public function testUnaEtiquetaConManejadorNoSobrevive(): void
    {
        // `strip_tags` nunca quitó atributos: el onerror entraba entero.
        $guardado = htmlentities('<img src=x onerror="alert(1)">', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $salida = nota_segura($guardado);

        $this->assertStringNotContainsString('onerror', $salida);
        $this->assertStringNotContainsString('<img', $salida);
    }

    public function testUnEnlaceJavascriptNoSobrevive(): void
    {
        $guardado = htmlentities('<a href="javascript:alert(1)">click</a>', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $salida = nota_segura($guardado);

        $this->assertStringNotContainsString('javascript:', $salida);
        $this->assertStringNotContainsString('<a ', $salida);
        $this->assertStringContainsString('click', $salida, 'el texto sí se conserva');
    }

    public function testUnScriptEscondidoEnEntidadesNoSeEjecuta(): void
    {
        $guardado = '&lt;script&gt;alert(1)&lt;/script&gt;';
        $salida = nota_segura($guardado);

        $this->assertStringNotContainsString('<script', $salida);
        $this->assertStringContainsString('alert(1)', $salida);
    }

    public function testUnaNotaVaciaNoRompe(): void
    {
        $this->assertSame('', nota_segura(''));
        $this->assertSame('', nota_segura(null));
    }
}

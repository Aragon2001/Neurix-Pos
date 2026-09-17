<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/stubs/CrearxmlEntorno.php';

/**
 * `quitatilde()` normaliza todo el texto que llega al comprobante: nombres de
 * producto, de cliente y direcciones. Estas pruebas fijan su contrato.
 */
class CrearxmlTextoTest extends TestCase
{
    private $crearxml;

    protected function setUp(): void
    {
        $this->crearxml = CrearxmlEntorno::preparar();
    }

    public function testQuitaLasTildesSinCambiarLaCaja(): void
    {
        $this->assertSame('Cafe Molido', $this->crearxml->quitatilde('Café Molido'));
        $this->assertSame('Noquis a la Espanola', $this->crearxml->quitatilde('Ñoquis a la Española'));
        $this->assertSame('PINA COLADA', $this->crearxml->quitatilde('PIÑA COLADA'));
        $this->assertSame('Uber Strase', $this->crearxml->quitatilde('Über Straße'));
        $this->assertSame('Acao', $this->crearxml->quitatilde('Ação'));
    }

    /** El ampersand rompe el XML si viaja sin escapar. */
    public function testCambiaElAmpersandPorY(): void
    {
        $this->assertSame('Jose Andres y Cia', $this->crearxml->quitatilde('Jose Andrés & Cía'));
    }

    public function testSacaLoQueRomperiaElXml(): void
    {
        $this->assertSame('bojo/b comillas', $this->crearxml->quitatilde('<b>ojo</b> "comillas"'));
        $this->assertSame('linea1  linea2', $this->crearxml->quitatilde("linea1\r\nlinea2"));
    }

    /**
     * Pasar por utf8_decode() convertia en "r" todo lo que no cupiera en
     * Latin-1: un nombre con un caracter asiatico o un emoji salia mutilado.
     */
    public function testConservaLoQueNoEsLatin1(): void
    {
        $this->assertSame('Ω 中文', $this->crearxml->quitatilde('Ω 中文'));
        $this->assertSame('Cafe 🙂', $this->crearxml->quitatilde('Café 🙂'));
    }

    public function testNoTocaElTextoQueYaEstaLimpio(): void
    {
        $this->assertSame('Arroz 1 kg - 250', $this->crearxml->quitatilde('Arroz 1 kg - 250'));
        $this->assertSame('', $this->crearxml->quitatilde(''));
        $this->assertSame('', $this->crearxml->quitatilde(null));
    }

    /** Ninguna letra acentuada debe sobrevivir al mapa. */
    public function testElMapaCubreTodasLasLetrasAcentuadas(): void
    {
        $acentuadas = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿŔŕ';

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z]+$/',
            $this->crearxml->quitatilde($acentuadas),
            'Quedó alguna letra acentuada sin traducir'
        );
    }
}

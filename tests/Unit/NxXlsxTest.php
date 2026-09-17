<?php

declare(strict_types=1);

namespace Tests\Unit;

use Nx_xlsx;
use PHPUnit\Framework\TestCase;
use ZipLector;

/**
 * Escritor de libros XLSX.
 *
 * El contenedor se escribe a mano porque la extensión `zip` no está cargada en
 * este servidor. Un libro mal armado no da error: Excel dice que el archivo
 * está dañado y no señala dónde, así que estas pruebas comprueban el ZIP entrada
 * por entrada —con su CRC— y luego el contenido de las celdas.
 */
final class NxXlsxTest extends TestCase
{
    private function libro(): Nx_xlsx
    {
        return new Nx_xlsx(array('simbolo' => '₡'));
    }

    /** Escribe un libro mínimo y devuelve sus partes ya descomprimidas. */
    private function partes(Nx_xlsx $x): array
    {
        return ZipLector::abrir($x->contenido());
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CONTENEDOR
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Cada entrada del ZIP tiene que descomprimir con su CRC intacto.
     *
     * `ZipLector` verifica CRC y tamaño de cada una: si la cabecera local y el
     * directorio central no coinciden, falla acá y no en el escritorio de quien
     * abra el archivo.
     */
    public function testElLibroEsUnZipIntegroConTodasSusPartes(): void
    {
        $x = $this->libro();
        $h = $x->hoja('Datos');
        $x->cabecera($h, array('A', 'B'));
        $x->fila($h, array('uno', 2), array(null, 'entero'));

        $p = $this->partes($x);

        foreach (array('[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml',
                       'xl/_rels/workbook.xml.rels', 'xl/styles.xml',
                       'xl/worksheets/sheet1.xml', 'docProps/core.xml', 'docProps/app.xml') as $req) {
            self::assertArrayHasKey($req, $p, "falta la parte «{$req}»");
        }
    }

    /** Toda parte del libro tiene que ser XML válido. */
    public function testTodaLaParteXmlEstaBienFormada(): void
    {
        $x = $this->libro();
        $h = $x->hoja('Datos');
        $x->cabecera($h, array('Texto', 'Número'));
        $x->fila($h, array('con <etiquetas> & "comillas"', 1234.5), array(null, 'moneda'));

        foreach ($this->partes($x) as $ruta => $xml) {
            $doc = new \DOMDocument();
            self::assertTrue($doc->loadXML($xml), "«{$ruta}» no es XML válido");
        }
    }

    public function testCadaHojaAparecEnElLibroConSuNombre(): void
    {
        $x = $this->libro();
        foreach (array('Resumen', 'Detalle', 'Estadísticas', 'Auditoría', 'Parámetros') as $n) {
            $x->hoja($n);
        }
        $p = $this->partes($x);

        self::assertSame(
            array('Resumen', 'Detalle', 'Estadísticas', 'Auditoría', 'Parámetros'),
            ZipLector::hojas($p['xl/workbook.xml'])
        );
        self::assertArrayHasKey('xl/worksheets/sheet5.xml', $p);
    }

    /**
     * Excel rechaza el libro entero si un nombre de pestaña lleva caracteres
     * prohibidos, y no dice cuál era.
     */
    public function testElNombreDeLaPestanaSeSaneaYSeRecorta(): void
    {
        $x = $this->libro();
        $x->hoja('Ventas/2026:[enero]*?');
        $x->hoja(str_repeat('N', 60));

        $n = ZipLector::hojas($this->partes($x)['xl/workbook.xml']);

        self::assertSame('Ventas-2026--enero---', $n[0]);
        self::assertSame(31, mb_strlen($n[1]), 'el nombre no se recortó a 31 caracteres');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CELDAS
    // ═══════════════════════════════════════════════════════════════════

    public function testLaReferenciaDeCeldaSigueLaNumeracionDeExcel(): void
    {
        $x = $this->libro();
        self::assertSame('A1', $x->celda(0, 1));
        self::assertSame('B1', $x->celda(1, 1));
        self::assertSame('Z9', $x->celda(25, 9));
        self::assertSame('AA1', $x->celda(26, 1));
        self::assertSame('AB2', $x->celda(27, 2));
        self::assertSame('BA1', $x->celda(52, 1));
    }

    /**
     * Un número tiene que llegar como número.
     *
     * Los `DECIMAL` de MySQL llegan a PHP como cadena; si se escriben como
     * texto, Excel los alinea a la izquierda y no los suma.
     */
    public function testUnImporteLlegaComoNumeroYNoComoTexto(): void
    {
        $x = $this->libro();
        $h = $x->hoja('D');
        $x->fila($h, array('1234.5600', 99, 'no numérico'), array('moneda', 'entero', null));

        $c = ZipLector::celdas($this->partes($x)['xl/worksheets/sheet1.xml']);

        self::assertSame('numero', $c['A1']['tipo'], 'el decimal de MySQL quedó como texto');
        self::assertSame('1234.56', $c['A1']['v']);
        self::assertSame('numero', $c['B1']['tipo']);
        self::assertSame('texto', $c['C1']['tipo']);
    }

    /**
     * Un consecutivo con ceros a la izquierda tiene que conservarlos.
     *
     * Sin el formato de texto Excel lo interpreta como número y
     * `00100001010000000001` se convierte en notación científica.
     */
    public function testUnConsecutivoConservaSusCerosALaIzquierda(): void
    {
        $x = $this->libro();
        $h = $x->hoja('D');
        $x->fila($h, array('00100001010000000001'), array('texto'));

        $c = ZipLector::celdas($this->partes($x)['xl/worksheets/sheet1.xml']);

        self::assertSame('texto', $c['A1']['tipo']);
        self::assertSame('00100001010000000001', $c['A1']['v']);
        self::assertSame((string) Nx_xlsx::E_TEXTO, $c['A1']['s']);
    }

    /** El escapado tiene que sobrevivir el viaje de ida y vuelta. */
    public function testElTextoConCaracteresDeXmlSobreviveIntacto(): void
    {
        $original = 'Ferretería "El Martillo" & Cía <S.A.> — 100 % ñandú';

        $x = $this->libro();
        $h = $x->hoja('D');
        $x->fila($h, array($original));

        $c = ZipLector::celdas($this->partes($x)['xl/worksheets/sheet1.xml']);
        self::assertSame($original, $c['A1']['v']);
    }

    /**
     * Los caracteres de control por debajo de 0x20 rompen el documento.
     *
     * Excel declara el libro ilegible y no señala la celda, así que se quitan al
     * escribir.
     */
    public function testLosCaracteresDeControlSeDescartan(): void
    {
        $x = $this->libro();
        $h = $x->hoja('D');
        $x->fila($h, array("texto\x00con\x08control\x1F"));

        $p = $this->partes($x);
        $doc = new \DOMDocument();
        self::assertTrue($doc->loadXML($p['xl/worksheets/sheet1.xml']));

        $c = ZipLector::celdas($p['xl/worksheets/sheet1.xml']);
        self::assertSame('textoconcontrol', $c['A1']['v']);
    }

    public function testUnaCeldaVaciaNoLlevaValor(): void
    {
        $x = $this->libro();
        $h = $x->hoja('D');
        $x->fila($h, array('', null, 0), array(null, null, 'entero'));

        $c = ZipLector::celdas($this->partes($x)['xl/worksheets/sheet1.xml']);

        self::assertSame('vacio', $c['A1']['tipo']);
        self::assertSame('vacio', $c['B1']['tipo']);
        self::assertSame('numero', $c['C1']['tipo'], 'un cero es un dato, no una celda vacía');
        self::assertSame('0', $c['C1']['v']);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  FECHAS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * La fecha se convierte al número de serie de Excel sin desfase de zona.
     *
     * Convertirla por el epoch mete la diferencia horaria dentro del número y
     * una venta de las 00:15 en Costa Rica aparece a las 06:15.
     *
     * @dataProvider fechas
     */
    public function testLaFechaSeConvierteAlSerialDeExcelSinDesfase(
        string $entrada,
        string $formato,
        float $serial
    ): void {
        $x = $this->libro();
        $h = $x->hoja('D');
        $x->fila($h, array($entrada), array($formato));

        $c = ZipLector::celdas($this->partes($x)['xl/worksheets/sheet1.xml']);
        self::assertEqualsWithDelta($serial, (float) $c['A1']['v'], 0.0000005);
    }

    public static function fechas(): array
    {
        // Serial de Excel: días desde 1899-12-30, con la fracción del día.
        return [
            'solo fecha'          => ['2026-08-27 15:30:00', 'fecha',     46261.0],
            'fecha y medianoche'  => ['2026-08-27 00:00:00', 'fechahora', 46261.0],
            'fecha y mediodía'    => ['2026-08-27 12:00:00', 'fechahora', 46261.5],
            'fecha y 06:00'       => ['2026-08-27 06:00:00', 'fechahora', 46261.25],
            'inicio de 2026'      => ['2026-01-01 00:00:00', 'fecha',     46023.0],
            'día bisiesto'        => ['2024-02-29 00:00:00', 'fecha',     45351.0],
        ];
    }

    public function testUnaFechaIlegibleSeEscribeComoTextoYNoComoCero(): void
    {
        $x = $this->libro();
        $h = $x->hoja('D');
        $x->fila($h, array('sin fecha'), array('fecha'));

        $c = ZipLector::celdas($this->partes($x)['xl/worksheets/sheet1.xml']);
        self::assertSame('texto', $c['A1']['tipo']);
        self::assertSame('sin fecha', $c['A1']['v']);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  PRESENTACIÓN
    // ═══════════════════════════════════════════════════════════════════

    /** La cabecera deja la fila congelada y el autofiltro puesto. */
    public function testLaCabeceraCongelaLaFilaYPoneElAutofiltro(): void
    {
        $x = $this->libro();
        $h = $x->hoja('D');
        $x->titulo($h, 'Informe', 3);
        $x->cabecera($h, array('A', 'B', 'C'));
        $x->fila($h, array(1, 2, 3));
        $x->fila($h, array(4, 5, 6));

        $xml = $this->partes($x)['xl/worksheets/sheet1.xml'];

        self::assertStringContainsString('state="frozen"', $xml);
        self::assertStringContainsString('topLeftCell="A3"', $xml, 'congela bajo la cabecera, que está en la fila 2');
        self::assertStringContainsString('<autoFilter ref="A2:C4"/>', $xml);
    }

    public function testLaOrientacionYElAjusteDePaginaSeDeclaran(): void
    {
        $x = $this->libro();
        $v = $x->hoja('Vertical');
        $hz = $x->hoja('Horizontal', array('orientacion' => 'horizontal', 'ajustar' => true));
        $x->fila($v, array('x'));
        $x->fila($hz, array('x'));

        $p = $this->partes($x);

        self::assertStringContainsString('orientation="portrait"', $p['xl/worksheets/sheet1.xml']);
        self::assertStringContainsString('orientation="landscape"', $p['xl/worksheets/sheet2.xml']);
        self::assertStringContainsString('fitToPage="1"', $p['xl/worksheets/sheet2.xml']);
        self::assertStringContainsString('fitToWidth="1"', $p['xl/worksheets/sheet2.xml']);
    }

    public function testLosAnchosDeColumnaSeEscriben(): void
    {
        $x = $this->libro();
        $h = $x->hoja('D');
        $x->fila($h, array('a', 'b', 'c'));
        $x->anchos($h, array(30, null, 12.5));

        $xml = $this->partes($x)['xl/worksheets/sheet1.xml'];

        self::assertStringContainsString('<col min="1" max="1" width="30"', $xml);
        self::assertStringContainsString('<col min="3" max="3" width="12.5"', $xml);
        self::assertStringNotContainsString('min="2"', $xml, 'la columna sin ancho no se declara');
    }

    /** El símbolo de moneda entra en el formato numérico, escapado. */
    public function testElSimboloDeMonedaEntraEnElFormato(): void
    {
        $p = $this->partes($this->libro());
        self::assertStringContainsString('₡', $p['xl/styles.xml']);
        self::assertStringContainsString('numFmtId="164"', $p['xl/styles.xml']);
    }

    /** Un libro sin hojas sigue siendo un libro válido. */
    public function testUnLibroSinHojasNoQuedaCorrupto(): void
    {
        $p = $this->partes($this->libro());

        self::assertArrayHasKey('xl/worksheets/sheet1.xml', $p);
        self::assertSame(array('Hoja1'), ZipLector::hojas($p['xl/workbook.xml']));
    }

    /** Un libro con muchas filas se arma sin agotar la memoria ni corromperse. */
    public function testUnLibroGrandeSeArmaCompleto(): void
    {
        $x = $this->libro();
        $h = $x->hoja('Grande');
        $x->cabecera($h, array('id', 'nombre', 'importe'));
        for ($i = 1; $i <= 3000; $i++) {
            $x->fila($h, array($i, 'Fila ' . $i, $i * 1.5), array('entero', null, 'moneda'));
        }

        $p = $this->partes($x);
        $c = ZipLector::celdas($p['xl/worksheets/sheet1.xml']);

        self::assertSame('3000', $c['A3001']['v']);
        self::assertSame('4500', $c['C3001']['v']);
        self::assertStringContainsString('A1:C3001', $p['xl/worksheets/sheet1.xml']);
    }
}

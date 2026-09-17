<?php

declare(strict_types=1);

namespace Tests\Unit\helpers;

use PHPUnit\Framework\TestCase;

/**
 * Diccionario de datos de los informes.
 *
 * Son funciones puras y sostienen la aritmética de todo el módulo: si
 * `rep_rango_fechas()` devuelve un 31 de febrero, la consulta entera falla, y si
 * `rep_tarifa_etiqueta()` llama exento a lo que no lo es, la declaración de IVA
 * sale mal. Se prueban aparte de la base por eso.
 */
final class ReportesHelperTest extends TestCase
{
    // ═══════════════════════════════════════════════════════════════════
    //  RANGOS DE FECHA
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Un mes se expande hasta su último día real.
     *
     * El código anterior concatenaba `'-31'`, y MySQL responde *Incorrect
     * DATETIME value* ante un 2026-02-31: febrero, abril, junio, septiembre y
     * noviembre no se podían consultar.
     *
     * @dataProvider mesesDelAno
     */
    public function testUnMesSeExpandeHastaSuUltimoDiaReal(string $mes, string $ultimo): void
    {
        [$desde, $hasta] = rep_rango_fechas($mes, null);

        self::assertSame($mes . '-01 00:00:00', $desde);
        self::assertSame($mes . '-' . $ultimo . ' 23:59:59', $hasta);
    }

    public static function mesesDelAno(): array
    {
        return [
            'enero (31)'            => ['2026-01', '31'],
            'febrero (28)'          => ['2026-02', '28'],
            'febrero bisiesto (29)' => ['2024-02', '29'],
            'marzo (31)'            => ['2026-03', '31'],
            'abril (30)'            => ['2026-04', '30'],
            'junio (30)'            => ['2026-06', '30'],
            'septiembre (30)'       => ['2026-09', '30'],
            'noviembre (30)'        => ['2026-11', '30'],
            'diciembre (31)'        => ['2026-12', '31'],
        ];
    }

    public function testUnAnoSeExpandeAlAnoCompleto(): void
    {
        self::assertSame(
            ['2026-01-01 00:00:00', '2026-12-31 23:59:59'],
            rep_rango_fechas('2026', null)
        );
    }

    public function testUnDiaSueltoCubreLasVeinticuatroHoras(): void
    {
        self::assertSame(
            ['2026-08-27 00:00:00', '2026-08-27 23:59:59'],
            rep_rango_fechas('2026-08-27', '2026-08-27')
        );
    }

    /** El rango al revés se endereza en vez de devolver cero registros. */
    public function testUnRangoInvertidoSeCorrige(): void
    {
        self::assertSame(
            ['2026-01-01 00:00:00', '2026-03-31 23:59:59'],
            rep_rango_fechas('2026-03-31', '2026-01-01')
        );
    }

    /**
     * Una fecha inexistente no puede llegar a la consulta.
     *
     * `checkdate()` la descarta acá; si viajara hasta MySQL tumbaría la
     * consulta entera y el informe devolvería la página de error de PHP, que es
     * lo que hacía morir a NxTable con «Unexpected token '<'».
     *
     * @dataProvider fechasImposibles
     */
    public function testUnaFechaImposibleNoLlegaALaConsulta(string $fecha): void
    {
        [$desde, $hasta] = rep_rango_fechas($fecha, $fecha);

        self::assertNull(rep_fecha_valida($fecha));
        self::assertSame(date('Y-m-d') . ' 00:00:00', $desde);
        self::assertSame(date('Y-m-d') . ' 23:59:59', $hasta);
    }

    public static function fechasImposibles(): array
    {
        return [
            '31 de febrero'      => ['2026-02-31'],
            '31 de abril'        => ['2026-04-31'],
            '30 de febrero'      => ['2026-02-30'],
            '29 de febrero no bisiesto' => ['2026-02-29'],
            'mes 13'             => ['2026-13-01'],
            'dia cero'           => ['2026-01-00'],
        ];
    }

    public function testUnaFechaRealSeAcepta(): void
    {
        self::assertSame('2024-02-29', rep_fecha_valida('2024-02-29'));
        self::assertSame('2026-08-27', rep_fecha_valida('2026-08-27 15:30:00'));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  TARIFAS
    // ═══════════════════════════════════════════════════════════════════

    /** @dataProvider tarifas */
    public function testCadaTarifaSeRotulaSegunElAnexo($entrada, string $esperado): void
    {
        self::assertSame($esperado, rep_tarifa_etiqueta($entrada));
    }

    public static function tarifas(): array
    {
        return [
            'exenta'                => [0, 'Exento / 0 %'],
            'exenta con decimales'  => ['0.00000', 'Exento / 0 %'],
            'reducida 1'            => [1, 'Reducida 1 %'],
            'reducida 2'            => ['2.00', 'Reducida 2 %'],
            'reducida 4'            => [4, 'Reducida 4 %'],
            'reducida 8'            => [8, 'Reducida 8 %'],
            'general'               => [13, 'General 13 %'],
            'general con decimales' => ['13.00000', 'General 13 %'],
            'general con signo'     => ['13%', 'General 13 %'],
            'no catalogada'         => [7.5, '7.5 %'],
        ];
    }

    /**
     * Una tarifa ausente no es una tarifa del cero por ciento.
     *
     * `sale_items.tax` admite NULL y hay líneas que lo tienen aunque cobren
     * impuesto. Rotularlas como exentas presenta impuesto declarado bajo una
     * tarifa cero y descuadra el D-104.
     */
    public function testUnaTarifaAusenteNoSeConfundeConExenta(): void
    {
        self::assertSame('(tarifa no registrada)', rep_tarifa_etiqueta(null));
        self::assertSame('(tarifa no registrada)', rep_tarifa_etiqueta(''));
        self::assertNotSame(rep_tarifa_etiqueta(null), rep_tarifa_etiqueta(0));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ÁMBITOS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * El ámbito fiscal conserva las anuladas y el interno no.
     *
     * Una factura anulada con nota de crédito sigue aceptada ante Hacienda:
     * sacarla de la declaración sin sacar también su nota descuadraría el
     * período.
     */
    public function testElAmbitoFiscalConservaLasAnuladasYElInternoNo(): void
    {
        $a = rep_ambitos();

        self::assertFalse($a['interno']['anuladas']);
        self::assertTrue($a['fiscal']['anuladas']);
        self::assertSame(['aceptado'], $a['fiscal']['estados']);
        self::assertSame([], $a['interno']['estados'], 'el ámbito interno no filtra por estado');
        self::assertTrue($a['emitido']['anuladas']);
        self::assertSame([], $a['emitido']['estados']);
    }

    public function testUnAmbitoDesconocidoCaeEnInterno(): void
    {
        self::assertSame('interno', rep_ambito_valido('inventado'));
        self::assertSame('interno', rep_ambito_valido(''));
        self::assertSame('interno', rep_ambito_valido(null));
        self::assertSame('fiscal', rep_ambito_valido('FISCAL'));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SEMÁFORO
    // ═══════════════════════════════════════════════════════════════════

    /**
     * El redondeo a dos decimales no puede encender una alarma.
     *
     * Sumar cientos de líneas de cinco decimales deja diferencias de céntimos
     * que no son errores; medio colón las absorbe.
     */
    public function testElRedondeoNoEnciendeLaAlarma(): void
    {
        self::assertSame('correcto', rep_semaforo(0.0, 1000)['estado']);
        self::assertSame('correcto', rep_semaforo(0.49, 1000)['estado']);
        self::assertSame('correcto', rep_semaforo(-0.49, 1000)['estado']);
    }

    public function testUnaDiferenciaPequenaAvisaYUnaGrandeAlerta(): void
    {
        self::assertSame('revisar', rep_semaforo(5, 1000)['estado'], 'medio por ciento');
        self::assertSame('inconsistencia', rep_semaforo(50, 1000)['estado'], 'cinco por ciento');
    }

    /** Sin referencia contra la que medir, cualquier diferencia es total. */
    public function testUnaDiferenciaSinReferenciaEsInconsistencia(): void
    {
        $r = rep_semaforo(10, 0);
        self::assertSame('inconsistencia', $r['estado']);
        self::assertSame(100.0, $r['pct']);
    }

    public function testSinDiferenciaNiReferenciaEstaCorrecto(): void
    {
        self::assertSame('correcto', rep_semaforo(0, 0)['estado']);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  FOLIO
    // ═══════════════════════════════════════════════════════════════════

    public function testElFolioLlevaFechaYConsecutivoDeSeisCifras(): void
    {
        self::assertSame('REP-' . date('Y-m-d') . '-000145', rep_folio(145));
        self::assertSame('REP-' . date('Y-m-d') . '-000001', rep_folio(1));
        self::assertMatchesRegularExpression('/^REP-\d{4}-\d{2}-\d{2}-\d{6}$/', rep_folio());
    }

    // ═══════════════════════════════════════════════════════════════════
    //  EXPRESIONES SQL
    // ═══════════════════════════════════════════════════════════════════

    /**
     * La base gravable descuenta el impuesto del subtotal.
     *
     * `subtotal` viene con el impuesto dentro; el error clásico es tomarlo por
     * la base y declarar de más.
     */
    public function testLaBaseGravableDescuentaElImpuestoDelSubtotal(): void
    {
        $e = rep_expr('si');

        self::assertSame('(si.subtotal - COALESCE(si.item_tax,0))', $e['base']);
        self::assertSame('si.subtotal', $e['total']);
        self::assertStringContainsString('si.quantity', $e['costo'], 'el costo es unitario y se multiplica');
    }

    public function testElAliasDeLaTablaSePropagaATodasLasExpresiones(): void
    {
        foreach (rep_expr('xx') as $clave => $sql) {
            self::assertStringContainsString('xx.', $sql, "la expresión «{$clave}» no usa el alias");
            self::assertStringNotContainsString('si.', $sql);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CONCEPTOS
    // ═══════════════════════════════════════════════════════════════════

    /** Cada concepto publicado tiene que traer su fórmula y su regla. */
    public function testCadaConceptoDocumentaSuFormulaYSuRegla(): void
    {
        $conceptos = rep_conceptos();
        self::assertNotEmpty($conceptos);

        foreach ($conceptos as $clave => $c) {
            self::assertArrayHasKey('titulo', $c, $clave);
            self::assertArrayHasKey('formula', $c, $clave);
            self::assertArrayHasKey('regla', $c, $clave);
            self::assertNotSame('', trim($c['formula']), "«{$clave}» no declara fórmula");
            self::assertGreaterThan(30, mb_strlen($c['regla']), "«{$clave}» no explica su regla");
        }
    }

    public function testLosNivelesDeAnomaliaPesanDeMayorAMenor(): void
    {
        $n = rep_niveles_anomalia();

        self::assertGreaterThan($n['alto']['peso'], $n['critico']['peso']);
        self::assertGreaterThan($n['medio']['peso'], $n['alto']['peso']);
        self::assertGreaterThan($n['bajo']['peso'], $n['medio']['peso']);
    }

    public function testElUmbralDelD151TieneUnValorDeReserva(): void
    {
        self::assertSame(2500000.0, rep_d151_umbral(null));
        self::assertSame(2500000.0, rep_d151_umbral((object) ['d151_umbral' => 0]));
        self::assertSame(3000000.0, rep_d151_umbral((object) ['d151_umbral' => 3000000]));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use DbEntorno;
use PHPUnit\Framework\TestCase;
use ReporteFixture;

/**
 * Integridad, anomalías y conciliación sobre el banco de datos controlado.
 *
 * El escenario lleva a propósito los defectos que estas comprobaciones tienen
 * que encontrar: una existencia negativa, un producto sin CABYS, otro sin costo,
 * un comprobante rechazado que nadie reemitió y una venta a crédito. Si una
 * comprobación deja de detectar el suyo, la prueba lo dice.
 */
final class AuditoriaModelTest extends TestCase
{
    private static $conn;
    private static $auditoria;
    private static $reportes;

    public static function setUpBeforeClass(): void
    {
        self::$conn = DbEntorno::conexion();
        if (!self::$conn) {
            return;
        }
        (new ReporteFixture(self::$conn))->crear()->poblar();
        self::$auditoria = DbEntorno::modelo('Auditoria_model', self::$conn);
        self::$reportes  = DbEntorno::modelo('Reporte_model', self::$conn);
    }

    protected function setUp(): void
    {
        if (!self::$conn) {
            self::markTestSkipped('No hay MySQL disponible para las pruebas de auditoría.');
        }
    }

    private function periodo(): array
    {
        return self::$reportes->filtro(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    }

    /** Hallazgos de una regla concreta. */
    private function de(string $regla): array
    {
        return array_values(array_filter(
            self::$auditoria->anomalias($this->periodo()),
            static fn (array $a): bool => $a['regla'] === $regla
        ));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  INTEGRIDAD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * En un escenario coherente, el detalle y el encabezado cuadran.
     *
     * Es la comprobación que delata que una línea cambió después de cerrar la
     * venta, cuando el comprobante ya se envió y es inmutable.
     */
    public function testElDetalleYElEncabezadoCuadranCuandoLosDatosSonCoherentes(): void
    {
        $i = self::$auditoria->integridad($this->periodo());

        $cuadre = $i['lineas'][0];
        self::assertSame('Detalle de ventas vs. encabezado', $cuadre['concepto']);
        self::assertEqualsWithDelta(0.0, $cuadre['diferencia'], 0.01);
        self::assertSame('correcto', $cuadre['estado']);
    }

    /**
     * Un crédito pendiente no es un descuadre.
     *
     * La venta 5 se facturó por 1.130 y se cobraron 500: la diferencia es saldo
     * por cobrar, se muestra, pero no penaliza el índice.
     */
    public function testUnCreditoPendienteSeMuestraPeroNoPenaliza(): void
    {
        $i = self::$auditoria->integridad($this->periodo());
        $cobro = $i['lineas'][1];

        self::assertSame('Facturado vs. cobrado', $cobro['concepto']);
        self::assertEqualsWithDelta(630.0, $cobro['diferencia'], 0.01, 'los 630 sin cobrar de la venta 5');
        self::assertSame('correcto', $cobro['estado'], 'el crédito pendiente no debe encender la alarma');
    }

    /**
     * Al romper el encabezado de una venta, la comprobación tiene que verlo.
     *
     * Es la prueba de que el semáforo mide algo: se altera el dato, se vuelve a
     * medir y se restituye.
     */
    public function testUnEncabezadoAlteradoEnciendeElSemaforo(): void
    {
        $antes = self::$auditoria->integridad($this->periodo());
        self::assertSame(0, $antes['diferencias']);

        self::$conn->query('UPDATE tec_sales SET grand_total = grand_total + 5000 WHERE id = 1');

        try {
            $roto = self::$auditoria->integridad($this->periodo());

            self::assertGreaterThan(0, $roto['diferencias'], 'el descuadre pasó desapercibido');
            self::assertSame('inconsistencia', $roto['lineas'][0]['estado']);
            // La comparación es detalle menos encabezado: al inflar el
            // encabezado la diferencia sale negativa.
            self::assertEqualsWithDelta(-5000.0, $roto['lineas'][0]['diferencia'], 0.01);
            self::assertLessThan(100.0, $roto['pct']);
        } finally {
            self::$conn->query('UPDATE tec_sales SET grand_total = grand_total - 5000 WHERE id = 1');
        }

        self::assertSame(0, self::$auditoria->integridad($this->periodo())['diferencias'],
            'el escenario no quedó como estaba');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ANOMALÍAS
    // ═══════════════════════════════════════════════════════════════════

    /** Toda regla del catálogo declara nivel, causa y acción recomendada. */
    public function testCadaReglaDeclaraSuNivelSuCausaYSuAccion(): void
    {
        $niveles = array_keys(rep_niveles_anomalia());

        foreach (self::$auditoria->reglas() as $clave => $r) {
            self::assertContains($r['nivel'], $niveles, "«{$clave}» tiene un nivel desconocido");
            self::assertNotSame('', trim($r['titulo']), "«{$clave}» sin título");
            self::assertGreaterThan(20, mb_strlen($r['causa']), "«{$clave}» no explica la causa probable");
            self::assertGreaterThan(20, mb_strlen($r['accion']), "«{$clave}» no dice qué hacer");
        }
    }

    /** Cada hallazgo llega con todo lo que hace falta para actuar sobre él. */
    public function testCadaHallazgoLlegaCompleto(): void
    {
        $a = self::$auditoria->anomalias($this->periodo());
        self::assertNotEmpty($a, 'el escenario tiene defectos y no se detectó ninguno');

        foreach ($a as $h) {
            foreach (['regla', 'nivel', 'tono', 'titulo', 'causa', 'accion',
                      'descripcion', 'documento', 'monto'] as $k) {
                self::assertArrayHasKey($k, $h, "un hallazgo de «{$h['regla']}» no trae «{$k}»");
            }
            self::assertNotSame('', trim((string) $h['descripcion']));
        }
    }

    /** Los hallazgos vienen ordenados por gravedad, no por orden de consulta. */
    public function testLosHallazgosVienenOrdenadosPorGravedad(): void
    {
        $orden = ['critico' => 0, 'alto' => 1, 'medio' => 2, 'bajo' => 3];
        $previo = -1;

        foreach (self::$auditoria->anomalias($this->periodo()) as $h) {
            self::assertGreaterThanOrEqual($previo, $orden[$h['nivel']],
                'un hallazgo grave quedó por debajo de uno leve');
            $previo = $orden[$h['nivel']];
        }
    }

    /**
     * El comprobante rechazado del escenario aparece como pendiente de reemitir.
     *
     * La tanda automática no reintenta los rechazados, así que sin esta
     * comprobación se quedan detenidos para siempre y nadie avisa.
     */
    public function testElComprobanteRechazadoSaleComoPendienteDeReemitir(): void
    {
        $h = $this->de('rechazado_sin_reemitir');

        self::assertCount(1, $h);
        self::assertSame('00100001040000000003', $h[0]['documento']);
        self::assertEqualsWithDelta(1130.0, (float) $h[0]['monto'], 0.01);
    }

    /** La existencia negativa del escenario se detecta. */
    public function testLaExistenciaNegativaSeDetecta(): void
    {
        $h = $this->de('stock_negativo');

        self::assertCount(1, $h);
        self::assertSame('MAR1', $h[0]['documento']);
        self::assertLessThan(0, (float) $h[0]['monto']);
    }

    /** La línea sin CABYS se detecta: la v4.4 lo exige en cada renglón. */
    public function testLaLineaSinCabysSeDetecta(): void
    {
        $h = $this->de('sin_cabys');

        self::assertCount(1, $h, 'solo la venta 5 tiene una línea sin CABYS');
        self::assertSame('ARZ1', $h[0]['documento']);
    }

    /** El producto vendido sin costo se detecta: infla el margen. */
    public function testElProductoVendidoSinCostoSeDetecta(): void
    {
        $h = $this->de('costo_cero');

        self::assertCount(1, $h);
        self::assertSame('PAN1', $h[0]['documento']);
    }

    /**
     * Una venta sin líneas se detecta.
     *
     * Es el caso en que la venta se grabó y el guardado del detalle falló a
     * mitad: el comprobante existe y no dice qué se vendió.
     */
    public function testUnaVentaSinLineasSeDetecta(): void
    {
        self::assertCount(0, $this->de('sin_detalle'), 'el escenario parte sin este defecto');

        self::$conn->query("INSERT INTO tec_sales (id,date,customer_id,customer_name,store_id,
            created_by,grand_total,total,tipo_doc,consecutivo)
            VALUES (99,'2026-03-01 10:00:00',1,'Fantasma',1,1,5000,5000,'01','00100001010000000099')");

        try {
            $h = $this->de('sin_detalle');
            self::assertCount(1, $h);
            self::assertSame('00100001010000000099', $h[0]['documento']);
            self::assertEqualsWithDelta(5000.0, (float) $h[0]['monto'], 0.01);
        } finally {
            self::$conn->query('DELETE FROM tec_sales WHERE id = 99');
        }
    }

    /**
     * Un consecutivo repetido se detecta.
     *
     * Hacienda registra la clave al recibirla: el segundo comprobante con el
     * mismo número es rechazo garantizado.
     */
    public function testUnConsecutivoRepetidoSeDetecta(): void
    {
        self::assertCount(0, $this->de('consecutivo_duplicado'));

        self::$conn->query("INSERT INTO tec_sales (id,date,customer_id,customer_name,store_id,
            created_by,grand_total,total,tipo_doc,consecutivo)
            VALUES (98,'2026-03-02 10:00:00',1,'Repetida',1,1,1000,1000,'01','00100001010000000001')");

        try {
            $h = $this->de('consecutivo_duplicado');
            self::assertCount(1, $h);
            self::assertStringContainsString('00100001010000000001', $h[0]['descripcion']);
        } finally {
            self::$conn->query('DELETE FROM tec_sales WHERE id = 98');
        }
    }

    /**
     * Un impuesto que no corresponde a su tarifa se detecta.
     *
     * Es la comprobación que sostiene el D-104: el débito fiscal sale de esa
     * columna, y si no cuadra con la tarifa la declaración va mal.
     */
    public function testUnImpuestoQueNoCorrespondeASuTarifaSeDetecta(): void
    {
        self::assertCount(0, $this->de('impuesto_descuadrado'));

        self::$conn->query('UPDATE tec_sale_items SET item_tax = 999 WHERE sale_id = 1 AND product_id = 1');

        try {
            self::assertCount(1, $this->de('impuesto_descuadrado'));
        } finally {
            self::$conn->query('UPDATE tec_sale_items SET item_tax = 260 WHERE sale_id = 1 AND product_id = 1');
        }
    }

    /**
     * Una línea con impuesto y sin tarifa se detecta.
     *
     * No es lo mismo que una línea exenta: la exenta lleva tarifa cero y monto
     * cero. Con monto y sin tarifa, el informe por tarifa la agruparía bajo un
     * 0 % que no es cierto.
     */
    public function testUnaLineaConImpuestoYSinTarifaSeDetecta(): void
    {
        self::assertCount(0, $this->de('tarifa_ausente'));

        self::$conn->query('UPDATE tec_sale_items SET tax = NULL WHERE sale_id = 1 AND product_id = 1');

        try {
            $h = $this->de('tarifa_ausente');
            self::assertCount(1, $h);
            self::assertSame('critico', $h[0]['nivel']);
            self::assertEqualsWithDelta(260.0, (float) $h[0]['monto'], 0.01);
        } finally {
            self::$conn->query("UPDATE tec_sale_items SET tax = '13%' WHERE sale_id = 1 AND product_id = 1");
        }
    }

    /** Una regla que falle no puede llevarse por delante el informe entero. */
    public function testUnaReglaQueFallaSeInformaYNoTumbaElResto(): void
    {
        self::$conn->query('ALTER TABLE tec_product_store_qty RENAME TO tec_psq_apartada');

        try {
            $a = self::$auditoria->anomalias($this->periodo());

            $falladas = array_filter($a, static fn ($h) => $h['titulo'] === 'Comprobación no ejecutada');
            self::assertNotEmpty($falladas, 'la comprobación rota no se informó');
            self::assertNotEmpty(
                array_filter($a, static fn ($h) => $h['regla'] === 'rechazado_sin_reemitir'),
                'las demás comprobaciones dejaron de correr'
            );
        } finally {
            self::$conn->query('ALTER TABLE tec_psq_apartada RENAME TO tec_product_store_qty');
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CONCILIACIÓN
    // ═══════════════════════════════════════════════════════════════════

    public function testLaConciliacionSeparaLoQueCuadraDeLoQueNo(): void
    {
        $c = self::$auditoria->conciliacion($this->periodo());

        self::assertSame(5, $c['revisados']);
        self::assertSame(0, $c['conteo']['sin_enviar'], 'las cinco ventas tienen comprobante');
        self::assertSame(1, $c['conteo']['sin_aceptar'], 'solo la rechazada');
        self::assertSame(1, $c['conteo']['sin_xml'], 'la rechazada no guardó el XML firmado');
        self::assertSame(0, $c['conteo']['descuadre']);
        self::assertSame(4, $c['conteo']['conciliado']);
    }

    /**
     * Una venta sin comprobante electrónico aparece como no enviada.
     *
     * Es lo que separa «lo que el negocio registró» de «lo que Hacienda tiene».
     */
    public function testUnaVentaSinComprobanteApareceComoNoEnviada(): void
    {
        self::$conn->query("INSERT INTO tec_sales (id,date,customer_id,customer_name,store_id,
            created_by,grand_total,total,tipo_doc,consecutivo)
            VALUES (97,'2026-03-03 10:00:00',1,'Sin enviar',1,1,2000,2000,'01','00100001010000000097')");

        try {
            $c = self::$auditoria->conciliacion($this->periodo());
            self::assertSame(1, $c['conteo']['sin_enviar']);

            $fila = array_values(array_filter($c['filas'], static fn ($f) => (int) $f['id'] === 97))[0];
            self::assertFalse($fila['conciliado']);
            self::assertStringContainsString('No existe comprobante', $fila['problemas']);
        } finally {
            self::$conn->query('DELETE FROM tec_sales WHERE id = 97');
        }
    }

    /** Una clave que no mide 50 dígitos se señala: Hacienda la rechaza entera. */
    public function testUnaClaveQueNoMideCincuentaDigitosSeSenala(): void
    {
        self::$conn->query("UPDATE tec_hacienda_tiketes SET clave = '123' WHERE sale_id = 1");

        try {
            $c = self::$auditoria->conciliacion($this->periodo());
            $fila = array_values(array_filter($c['filas'], static fn ($f) => (int) $f['id'] === 1))[0];

            self::assertFalse($fila['conciliado']);
            self::assertStringContainsString('3 dígitos en vez de 50', $fila['problemas']);
        } finally {
            self::$conn->query("UPDATE tec_hacienda_tiketes SET clave = '"
                . str_repeat('1', 50) . "' WHERE sale_id = 1");
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CONFIABILIDAD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * El índice no oculta nada: cada descuento viene con su motivo.
     *
     * Un porcentaje sin el desglose de lo que descontó se puede maquillar sin
     * que se note.
     */
    public function testElIndiceDeConfiabilidadDetallaCadaDescuento(): void
    {
        $c = self::$auditoria->confiabilidad($this->periodo());

        self::assertGreaterThanOrEqual(0, $c['pct']);
        self::assertLessThanOrEqual(100, $c['pct']);
        self::assertNotEmpty($c['descuentos'], 'hay anomalías y no se descontó nada');

        foreach ($c['descuentos'] as $d) {
            foreach (['regla', 'titulo', 'nivel', 'hallazgos', 'descuento'] as $k) {
                self::assertArrayHasKey($k, $d);
            }
            self::assertGreaterThan(0, $d['descuento']);
        }
        self::assertContains($c['tono'], ['ok', 'warn', 'err']);
    }

    /** Más anomalías bajan el índice; ninguna lo deja en cien. */
    public function testMasAnomaliasBajanElIndice(): void
    {
        $antes = self::$auditoria->confiabilidad($this->periodo())['pct'];

        self::$conn->query('UPDATE tec_sales SET grand_total = grand_total + 9000 WHERE id = 2');

        try {
            $despues = self::$auditoria->confiabilidad($this->periodo())['pct'];
            self::assertLessThan($antes, $despues, 'un descuadre nuevo no bajó el índice');
        } finally {
            self::$conn->query('UPDATE tec_sales SET grand_total = grand_total - 9000 WHERE id = 2');
        }
    }

    /**
     * Un período sin movimiento tiene confiabilidad plena.
     *
     * La existencia negativa del escenario sigue apareciendo como hallazgo
     * —es real y es de hoy—, pero no descuenta del índice de un período que no
     * la causó: el índice mide si se puede confiar en las cifras de ESE período.
     */
    public function testUnPeriodoSinMovimientoTieneConfiabilidadPlena(): void
    {
        $f = self::$reportes->filtro(['start_date' => '2001-01-01', 'end_date' => '2001-12-31']);
        $c = self::$auditoria->confiabilidad($f);

        self::assertSame(100.0, $c['pct']);
        self::assertSame(0, $c['anomalias'], 'ninguna anomalía del período');
        self::assertSame('ok', $c['tono']);
        self::assertSame([], $c['descuentos']);
        self::assertStringContainsString('No se detectaron', $c['resumen']);
    }

    /**
     * Una comprobación de estado actual se informa aunque no descuente.
     *
     * Ocultarla para que el porcentaje quede limpio sería justo lo contrario de
     * lo que tiene que hacer una auditoría.
     */
    public function testLaExistenciaNegativaSeInformaAunqueNoDescuente(): void
    {
        $f = self::$reportes->filtro(['start_date' => '2001-01-01', 'end_date' => '2001-12-31']);

        $hallazgos = self::$auditoria->anomalias($f);
        $stock = array_values(array_filter($hallazgos, static fn ($h) => $h['regla'] === 'stock_negativo'));

        self::assertCount(1, $stock, 'la existencia negativa dejó de informarse');
        self::assertFalse($stock[0]['periodica'], 'debería estar marcada como estado actual');
        self::assertSame(100.0, self::$auditoria->confiabilidad($f)['pct']);
    }
}

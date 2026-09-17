<?php

declare(strict_types=1);

namespace Tests\Unit;

use DbEntorno;
use PHPUnit\Framework\TestCase;
use ReporteFixture;

/**
 * Aritmética de los informes contra un banco de datos controlado.
 *
 * Las cifras esperadas están calculadas a mano en `ReporteFixture::esperado()`.
 * Probar contra la base real diría si la consulta corre; esto dice si la cifra
 * es la correcta, que es la pregunta del informe.
 *
 * La suite se salta sola cuando no hay MySQL: la comprobación no puede impedir
 * que corra el resto en una máquina sin servidor.
 */
final class ReporteModelTest extends TestCase
{
    private static $conn;
    private static $modelo;

    public static function setUpBeforeClass(): void
    {
        self::$conn = DbEntorno::conexion();
        if (!self::$conn) {
            return;
        }
        (new ReporteFixture(self::$conn))->crear()->poblar();
        self::$modelo = DbEntorno::modelo('Reporte_model', self::$conn);
    }

    protected function setUp(): void
    {
        if (!self::$conn) {
            self::markTestSkipped('No hay MySQL disponible para las pruebas de informes.');
        }
    }

    /** Filtro normalizado a partir de lo que llegaría por HTTP. */
    private function filtro(array $in): array
    {
        return self::$modelo->filtro($in);
    }

    private function febrero(string $ambito = 'interno'): array
    {
        return $this->filtro(['start_date' => '2026-02', 'ambito' => $ambito]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  ÁMBITOS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * El ámbito interno deja fuera la venta anulada.
     *
     * Son cuatro ventas en febrero; la cuarta se anuló con nota de crédito, así
     * que la gestión interna cuenta tres.
     */
    public function testElAmbitoInternoExcluyeLaVentaAnulada(): void
    {
        $e = ReporteFixture::esperado()['febrero_interno'];
        $r = self::$modelo->resumen($this->febrero('interno'));

        self::assertSame(3, (int) $r['documentos']);
        self::assertEqualsWithDelta($e['total'], $r['total'], 0.01);
        self::assertEqualsWithDelta($e['base'], $r['base'], 0.01);
        self::assertEqualsWithDelta($e['impuesto'], $r['impuesto'], 0.01);
        self::assertEqualsWithDelta($e['descuento'], $r['descuento'], 0.01);
        self::assertEqualsWithDelta($e['costo'], $r['costo'], 0.01);
    }

    /**
     * El ámbito fiscal conserva la anulada y descarta la rechazada.
     *
     * Ante Hacienda la anulada sigue aceptada y es su nota de crédito la que la
     * compensa; la rechazada nunca tuvo validez.
     */
    public function testElAmbitoFiscalConservaLaAnuladaYDescartaLaRechazada(): void
    {
        $e = ReporteFixture::esperado()['febrero_fiscal'];
        $r = self::$modelo->resumen($this->febrero('fiscal'));

        self::assertSame(3, (int) $r['documentos']);
        self::assertEqualsWithDelta($e['total'], $r['total'], 0.01);
        self::assertEqualsWithDelta($e['base'], $r['base'], 0.01);
        self::assertEqualsWithDelta($e['impuesto'], $r['impuesto'], 0.01);
    }

    public function testElAmbitoEmitidoIncluyeTodoLoQueSalioDelPunteoDeVenta(): void
    {
        $e = ReporteFixture::esperado()['febrero_emitido'];
        $r = self::$modelo->resumen($this->febrero('emitido'));

        self::assertSame(4, (int) $r['documentos']);
        self::assertEqualsWithDelta($e['total'], $r['total'], 0.01);
    }

    /** Los tres ámbitos tienen que dar tres cifras distintas o no separan nada. */
    public function testLosTresAmbitosNoCoinciden(): void
    {
        $t = [];
        foreach (['interno', 'fiscal', 'emitido'] as $a) {
            $t[$a] = self::$modelo->resumen($this->febrero($a))['total'];
        }
        self::assertCount(3, array_unique($t), 'dos ámbitos devuelven lo mismo: ' . json_encode($t));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  NOTAS Y NETO
    // ═══════════════════════════════════════════════════════════════════

    /**
     * La venta neta descuenta las notas de crédito y suma las de débito.
     *
     * Se fechan por la nota y no por la factura que corrigen, así que la de
     * crédito del 26 y la de débito del 28 caen las dos en febrero.
     */
    public function testLaVentaNetaAjustaPorLasNotasDelPeriodo(): void
    {
        $n = ReporteFixture::esperado()['notas'];
        $r = self::$modelo->resumen($this->febrero('fiscal'));

        self::assertSame($n['nc_cantidad'], (int) $r['nc_cantidad']);
        self::assertEqualsWithDelta($n['nc_total'], $r['nc_total'], 0.01);
        self::assertSame($n['nd_cantidad'], (int) $r['nd_cantidad']);
        self::assertEqualsWithDelta($n['nd_total'], $r['nd_total'], 0.01);

        $esperado = $r['total'] - $n['nc_total'] + $n['nd_total'];
        self::assertEqualsWithDelta($esperado, $r['neto'], 0.01);
        self::assertEqualsWithDelta(
            $r['impuesto'] - $n['nc_impuesto'] + $n['nd_impuesto'],
            $r['impuesto_neto'],
            0.01
        );
    }

    /**
     * La nota se compara con el documento que corrige, no con el botón pulsado.
     *
     * El alcance real —total o parcial— sale de la diferencia entre los dos
     * documentos.
     */
    public function testLaNotaDeCreditoSeCompararaConSuDocumentoDeOrigen(): void
    {
        $r = self::$modelo->nota_vs_origen(1);

        self::assertNotNull($r);
        self::assertSame('total', $r['alcance']);
        self::assertEqualsWithDelta(0.0, $r['diferencia'], 0.01);
        self::assertCount(1, $r['lineas']);
        self::assertSame('devuelto completo', $r['lineas'][0]['efecto']);
    }

    public function testUnaNotaInexistenteDevuelveNulo(): void
    {
        self::assertNull(self::$modelo->nota_vs_origen(9999));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CONSISTENCIA ENTRE VISTAS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * El indicador de cabecera y la suma del detalle no pueden discrepar.
     *
     * Es la regla que sostiene el módulo: el total del KPI, la suma de la
     * columna en pantalla y el pie del Excel son la misma cifra porque salen de
     * la misma resolución de datos.
     *
     * @dataProvider filtrosVariados
     */
    public function testElIndicadorCuadraConLaSumaDelDetalle(array $entrada): void
    {
        $f = $this->filtro($entrada);

        $resumen = self::$modelo->resumen($f);
        $ventas  = self::$modelo->ventas($f);
        $lineas  = self::$modelo->lineas($f);

        $suma = static fn (array $filas, string $k): float
            => round(array_sum(array_map(static fn ($r) => (float) $r[$k], $filas)), 2);

        self::assertSame((int) $resumen['documentos'], count($ventas), 'documentos vs filas de venta');
        self::assertEqualsWithDelta($resumen['total'], $suma($ventas, 'total'), 0.01, 'total vs detalle de ventas');
        self::assertEqualsWithDelta($resumen['total'], $suma($lineas, 'total'), 0.01, 'total vs detalle de líneas');
        self::assertEqualsWithDelta($resumen['base'], $suma($lineas, 'base'), 0.01, 'base vs detalle de líneas');
        self::assertEqualsWithDelta($resumen['impuesto'], $suma($lineas, 'impuesto'), 0.01, 'IVA vs detalle');
        self::assertSame((int) $resumen['lineas'], count($lineas), 'líneas contadas vs devueltas');
    }

    public static function filtrosVariados(): array
    {
        return [
            'sin filtros'          => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31']],
            'febrero'              => [['start_date' => '2026-02']],
            'un solo día'          => [['start_date' => '2026-02-10', 'end_date' => '2026-02-10']],
            'ámbito fiscal'        => [['start_date' => '2026-02', 'ambito' => 'fiscal']],
            'ámbito emitido'       => [['start_date' => '2026-02', 'ambito' => 'emitido']],
            'por cliente'          => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'customer_id' => '2']],
            'por usuario'          => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'user_id' => '2']],
            'por sucursal'         => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'store_id' => '2']],
            'por producto'         => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'product_id' => '1']],
            'por categoría'        => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'category_id' => '2']],
            'por tarifa'           => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'tarifa' => '13']],
            'por medio de pago'    => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'paid_by' => 'sinpe']],
            'por tipo de doc'      => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'tipo_doc' => '04']],
            'por estado'           => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'estado' => 'rechazado', 'ambito' => 'emitido']],
            'por identificación'   => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'identificacion' => '3101']],
            'por CABYS'            => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'cabys' => '23123000001']],
            'por documento'        => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'documento' => '0000000002']],
            'por monto mínimo'     => [['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'monto_min' => '2000']],
            'combinado triple'     => [['start_date' => '2026-02', 'store_id' => '1', 'user_id' => '1', 'ambito' => 'emitido']],
            'sin resultados'       => [['start_date' => '2001-01-01', 'end_date' => '2001-12-31']],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CADA FILTRO TIENE EFECTO
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Un filtro que no cambia el resultado es un filtro dibujado en pantalla.
     *
     * @dataProvider filtrosQueDebenRecortar
     */
    public function testCadaFiltroRecortaDeVerdadElResultado(string $clave, string $valor, int $esperados): void
    {
        $base = ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'ambito' => 'emitido'];

        $todos = self::$modelo->resumen($this->filtro($base));
        $filtrado = self::$modelo->resumen($this->filtro($base + [$clave => $valor]));

        self::assertSame(5, (int) $todos['documentos'], 'el escenario tiene cinco ventas');
        self::assertSame($esperados, (int) $filtrado['documentos'],
            "el filtro «{$clave}={$valor}» no recortó como se esperaba");
        self::assertLessThan((int) $todos['documentos'], (int) $filtrado['documentos'],
            "el filtro «{$clave}» no tuvo ningún efecto");
    }

    public static function filtrosQueDebenRecortar(): array
    {
        return [
            'cliente'        => ['customer_id', '2', 3],
            'usuario'        => ['user_id', '2', 2],
            'sucursal'       => ['store_id', '2', 1],
            'producto'       => ['product_id', '2', 2],
            'categoría'      => ['category_id', '2', 2],
            'tipo documento' => ['tipo_doc', '04', 2],
            'estado'         => ['estado', 'rechazado', 1],
            'medio de pago'  => ['paid_by', 'sinpe', 1],
            'identificación' => ['identificacion', '3101123456', 3],
            'documento'      => ['documento', '0000000003', 1],
            'monto mínimo'   => ['monto_min', '2500', 2],
            'monto máximo'   => ['monto_max', '1500', 2],
            'condición'      => ['condicion', '2', 1],
        ];
    }

    /**
     * La sucursal de la sesión manda sobre la que llegue por el formulario.
     *
     * De lo contrario un usuario limitado a su tienda podría ver las ventas de
     * otra manipulando el filtro.
     */
    public function testLaSucursalDeLaSesionNoSePuedeAmpliarDesdeElFormulario(): void
    {
        $conSesion = DbEntorno::modelo('Reporte_model', self::$conn, ['store_id' => 2]);

        $f = $conSesion->filtro(['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'store_id' => '1']);
        self::assertSame(2, (int) $f['store_id'], 'el filtro de pantalla pisó la sucursal de la sesión');

        $r = $conSesion->resumen($f);
        self::assertSame(1, (int) $r['documentos'], 'se vieron ventas de otra sucursal');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  CASOS LÍMITE
    // ═══════════════════════════════════════════════════════════════════

    /** Un período sin ventas devuelve ceros, no error ni nulos. */
    public function testUnPeriodoVacioDevuelveCerosYNoNulos(): void
    {
        $r = self::$modelo->resumen($this->filtro(['start_date' => '2001-01-01', 'end_date' => '2001-12-31']));

        self::assertSame(0.0, $r['documentos']);
        self::assertSame(0.0, $r['total']);
        self::assertSame(0.0, $r['margen'], 'el margen sin base es cero, no una división por cero');
        self::assertSame(0.0, $r['ticket']);
        self::assertSame([], self::$modelo->ventas($this->filtro(['start_date' => '2001-01-01', 'end_date' => '2001-12-31'])));
    }

    /** Un solo registro se comporta igual que muchos. */
    public function testUnSoloRegistroSeResuelveIgualQueMuchos(): void
    {
        $f = $this->filtro(['start_date' => '2026-02-10', 'end_date' => '2026-02-10']);
        $r = self::$modelo->resumen($f);

        self::assertSame(1.0, $r['documentos']);
        self::assertEqualsWithDelta(2760.00, $r['total'], 0.01);
        self::assertCount(1, self::$modelo->ventas($f));
        self::assertCount(2, self::$modelo->lineas($f), 'la venta tiene dos líneas');
    }

    /**
     * Febrero se consulta sin que la fecha reviente la consulta.
     *
     * Es la regresión del `'-31'`: con el código anterior este período devolvía
     * *Incorrect DATETIME value* y ningún dato.
     */
    public function testFebreroSeConsultaSinReventarLaConsulta(): void
    {
        $r = self::$modelo->resumen($this->filtro(['start_date' => '2026-02']));

        self::assertGreaterThan(0, (int) $r['documentos']);
        self::assertSame('2026-02-28 23:59:59', $this->filtro(['start_date' => '2026-02'])['hasta']);
    }

    /** Una línea exenta suma base y no suma impuesto. */
    public function testUnaLineaExentaSumaBaseYNoImpuesto(): void
    {
        $f = $this->filtro(['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'tarifa' => '0']);
        $r = self::$modelo->resumen($f);

        self::assertEqualsWithDelta(500.00, $r['base'], 0.01);
        self::assertEqualsWithDelta(0.00, $r['impuesto'], 0.01);
    }

    /** El descuento se informa aparte y no se vuelve a restar del total. */
    public function testElDescuentoNoSeRestaDosVecesDelTotal(): void
    {
        $f = $this->filtro(['start_date' => '2026-02-15', 'end_date' => '2026-02-15']);
        $r = self::$modelo->resumen($f);

        self::assertEqualsWithDelta(200.00, $r['descuento'], 0.01);
        self::assertEqualsWithDelta(2034.00, $r['total'], 0.01, 'el subtotal ya venía neto de descuento');
        self::assertEqualsWithDelta($r['base'] + $r['impuesto'], $r['total'], 0.01);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  AGRUPACIONES
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Toda agrupación reparte el mismo total del período.
     *
     * Si «por día» y «por usuario» sumaran distinto, una de las dos estaría
     * duplicando o perdiendo filas.
     *
     * @dataProvider dimensiones
     */
    public function testCadaAgrupacionRepartioElMismoTotal(string $por): void
    {
        $f = $this->filtro(['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'ambito' => 'emitido']);

        $resumen = self::$modelo->resumen($f);
        $grupos  = self::$modelo->ventas_por($f, $por);

        $suma = round(array_sum(array_map(static fn ($r) => (float) $r['total'], $grupos)), 2);
        self::assertEqualsWithDelta($resumen['total'], $suma, 0.01,
            "la agrupación «{$por}» no reparte el total del período");
    }

    public static function dimensiones(): array
    {
        $d = ['dia', 'hora', 'mes', 'usuario', 'sucursal', 'caja', 'cliente',
              'producto', 'categoria', 'cabys', 'tarifa', 'tipo_doc', 'condicion', 'estado'];
        return array_combine($d, array_map(static fn ($x) => [$x], $d));
    }

    public function testUnaAgrupacionDesconocidaDevuelveVacioYNoRevienta(): void
    {
        self::assertSame([], self::$modelo->ventas_por($this->febrero(), 'inventada'));
    }

    /** El medio de pago se suma desde los pagos, no desde la venta. */
    public function testElMedioDePagoSumaLoCobradoYNoLoFacturado(): void
    {
        $f = $this->filtro(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $m = self::$modelo->ventas_por_medio_pago($f);

        $por = [];
        foreach ($m as $r) {
            $por[$r['nombre']] = (float) $r['total'];
        }

        self::assertEqualsWithDelta(2760.00 + 1130.00 + 500.00, $por['cash'], 0.01);
        self::assertEqualsWithDelta(2034.00, $por['sinpe'], 0.01);
        self::assertArrayNotHasKey('card', $por, 'la venta anulada no aporta al cobrado interno');
    }

    /** Los catálogos de filtro traen algo con que poblar los desplegables. */
    public function testLosCatalogosDeFiltroNoLleganVacios(): void
    {
        $c = self::$modelo->catalogos();

        foreach (['clientes', 'usuarios', 'sucursales', 'categorias', 'productos', 'medios', 'estados'] as $k) {
            self::assertArrayHasKey($k, $c);
            self::assertNotEmpty($c[$k], "el catálogo «{$k}» llegó vacío");
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  SEGURIDAD
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Un filtro con carga de inyección se liga como valor, no como SQL.
     *
     * El entorno de pruebas solo sabe ejecutar consultas con parámetros: si el
     * modelo interpolara el valor, la consulta fallaría o devolvería de más.
     *
     * @dataProvider cargasDeInyeccion
     */
    public function testUnFiltroConCargaDeInyeccionSeTrataComoValor(string $clave, string $carga): void
    {
        $f = $this->filtro(['start_date' => '2026-01-01', 'end_date' => '2026-12-31', $clave => $carga]);
        $r = self::$modelo->resumen($f);

        self::assertSame(0.0, $r['documentos'], "«{$carga}» en «{$clave}» devolvió filas");

        // Y la tabla sigue en pie: nada se ejecutó.
        $control = self::$modelo->resumen($this->filtro(['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'ambito' => 'emitido']));
        self::assertSame(5.0, $control['documentos'], 'la carga alteró los datos');
    }

    public static function cargasDeInyeccion(): array
    {
        return [
            'documento con OR'       => ['documento', "' OR '1'='1"],
            'documento con DROP'     => ['documento', "'; DROP TABLE tec_sales; --"],
            'identificación con OR'  => ['identificacion', "' OR 1=1 --"],
            'estado con UNION'       => ['estado', "aceptado' UNION SELECT 1 --"],
            'medio de pago con coma' => ['paid_by', "cash', 'x"],
            'CABYS con comodín'      => ['cabys', "' OR '1'='1"],
        ];
    }

    /** Una fecha imposible no llega a la consulta y no la tumba. */
    public function testUnaFechaImposibleNoTumbaLaConsulta(): void
    {
        $r = self::$modelo->resumen($this->filtro(['start_date' => '2026-02-31', 'end_date' => '2026-02-31']));
        self::assertSame(0.0, $r['documentos']);
    }
}

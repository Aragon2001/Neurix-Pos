<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/stubs/CrearxmlEntorno.php';

/**
 * Las decisiones del generador: qué comprobante sale, con qué código y para
 * quién. No son fallos de estructura —el XML valida contra el XSD igual— sino
 * de contenido, que es lo que Hacienda rechaza después de emitir.
 */
class CrearxmlDecisionTest extends TestCase
{
    /** El código de comprobante ocupa las posiciones 9 y 10 del consecutivo. */
    private function tipoDeConsecutivo(string $consecutivo): string
    {
        return substr($consecutivo, 8, 2);
    }

    public function testElReciboDePagoUsaElTipo10(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::clienteConUbicacion();

        $salida = $crearxml->getREP(
            CrearxmlEntorno::pago(),
            CrearxmlEntorno::venta(),
            CrearxmlEntorno::referenciaREP()
        );

        // El 09 es la factura de exportación; el recibo de pago es el 10.
        $this->assertSame('10', $this->tipoDeConsecutivo($salida['consecutivo']));
    }

    public function testLaClaveDelReciboDePagoMide50(): void
    {
        $crearxml = CrearxmlEntorno::preparar();
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::clienteConUbicacion();

        $salida = $crearxml->getREP(
            CrearxmlEntorno::pago(),
            CrearxmlEntorno::venta(),
            CrearxmlEntorno::referenciaREP()
        );

        $this->assertSame(50, strlen($salida['clave']));
        $this->assertSame(20, strlen($salida['consecutivo']));
        // La clave lleva el consecutivo entero en las posiciones 22 a 41.
        $this->assertStringContainsString($salida['consecutivo'], $salida['clave']);
    }

    public function testElClienteDePasoSaleDelAjusteYNoDelLiteral(): void
    {
        // El ajuste apunta al cliente 9; el 2 pasa a ser un cliente normal.
        $crearxml = CrearxmlEntorno::preparar(['default_customer' => 9]);
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::clienteConUbicacion();

        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $salida = $crearxml->getInvoice(
            $venta,
            [CrearxmlEntorno::item()],
            CrearxmlEntorno::pagos(),
            null
        );

        $this->assertSame('01', $this->tipoDeConsecutivo($salida['consecutivo']), 'cliente identificado ⇒ factura');
    }

    public function testElClienteQueEsElDePasoSaleComoTiquete(): void
    {
        $crearxml = CrearxmlEntorno::preparar(['default_customer' => 2]);
        CrearxmlEntorno::$ci->customers_model->cliente = CrearxmlEntorno::clienteConUbicacion();

        // El ajuste dice que el 2 es el cliente de paso.
        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $salida = $crearxml->getInvoice(
            $venta,
            [CrearxmlEntorno::item()],
            CrearxmlEntorno::pagos(),
            null
        );

        $this->assertSame('04', $this->tipoDeConsecutivo($salida['consecutivo']), 'el id del ajuste ⇒ tiquete');
    }

    public function testUnClienteLlamadoDeContadoYaNoDecideElComprobante(): void
    {
        $crearxml = CrearxmlEntorno::preparar(['default_customer' => 1]);

        // Cliente real, identificado, que se llama así.
        $cliente = CrearxmlEntorno::clienteConUbicacion();
        $cliente->name = 'Cliente de contado';
        CrearxmlEntorno::$ci->customers_model->cliente = $cliente;

        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $salida = $crearxml->getInvoice(
            $venta,
            [CrearxmlEntorno::item()],
            CrearxmlEntorno::pagos(),
            null
        );

        $this->assertSame('01', $this->tipoDeConsecutivo($salida['consecutivo']),
            'el nombre no puede degradar una factura a tiquete');
    }

    public function testUnClienteSinCedulaNoPuedeRecibirFactura(): void
    {
        $crearxml = CrearxmlEntorno::preparar(['default_customer' => 1]);

        $cliente = CrearxmlEntorno::clienteConUbicacion();
        $cliente->cf2 = '';
        $cliente->id_number_proveedor = '';
        CrearxmlEntorno::$ci->customers_model->cliente = $cliente;

        $venta = CrearxmlEntorno::venta();
        $venta['customer_id'] = 2;

        $salida = $crearxml->getInvoice(
            $venta,
            [CrearxmlEntorno::item()],
            CrearxmlEntorno::pagos(),
            null
        );

        // El esquema de la factura exige identificación del receptor: sin ella
        // el comprobante que corresponde es el tiquete.
        $this->assertSame('04', $this->tipoDeConsecutivo($salida['consecutivo']));
    }
}

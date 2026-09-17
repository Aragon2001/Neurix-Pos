<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Los tres helpers que sostienen el detalle de un comprobante: como se rotula
 * un medio de pago, como se lee un estado ante Hacienda y que dice el acuse.
 */
class HaciendaEstadoTest extends TestCase
{
    /* ── medio_pago_hacienda ── */

    /**
     * Los codigos son los de la nota 6 del anexo v4.4 (pag. 70). El 08 y el 09
     * no existen en esa version: si alguno reaparece, es un rechazo seguro.
     */
    public function test_codigos_de_medio_de_pago_de_la_v44(): void
    {
        $esperado = [
            'cash' => '01',
            'card' => '02', 'CC' => '02', 'credit_card' => '02', 'debit_card' => '02', 'stripe' => '02',
            'cheque' => '03',
            'transfer' => '04', 'transdep' => '04',
            'terceros' => '05',
            'sinpe' => '06',
            'digital' => '07', 'plataforma' => '07',
        ];

        foreach ($esperado as $paid_by => $codigo) {
            $this->assertSame($codigo, medio_pago_hacienda($paid_by)['codigo'], "paid_by '$paid_by'");
        }
    }

    public function test_medio_de_pago_desconocido_cae_en_99(): void
    {
        foreach (['gift_card', 'ppp', 'inventado', '', null] as $valor) {
            $this->assertSame('99', medio_pago_hacienda($valor)['codigo']);
        }
    }

    public function test_medio_de_pago_no_distingue_mayusculas_ni_espacios(): void
    {
        $this->assertSame('06', medio_pago_hacienda('  SINPE ')['codigo']);
        $this->assertSame('01', medio_pago_hacienda('CASH')['codigo']);
    }

    public function test_medio_de_pago_siempre_trae_etiqueta(): void
    {
        $medio = medio_pago_hacienda('sinpe');
        $this->assertArrayHasKey('etiqueta', $medio);
        $this->assertNotSame('', $medio['etiqueta']);
    }

    /* ── estado_hacienda_info ── */

    /**
     * `corregible` es lo que decide si se ofrece reenviar. Un comprobante
     * aceptado es inmutable y uno en proceso todavia no se sabe.
     */
    public function test_solo_admite_correccion_lo_que_nunca_fue_aceptado(): void
    {
        foreach ([null, '', 'pendiente', 'Sin Estado', 'rechazado', 'error'] as $estado) {
            $this->assertTrue(estado_hacienda_info($estado)['corregible'], "estado '$estado'");
        }
        foreach (['aceptado', 'procesando', 'recibido', 'anulado', 'aceptado parcialmente'] as $estado) {
            $this->assertFalse(estado_hacienda_info($estado)['corregible'], "estado '$estado'");
        }
    }

    public function test_estados_equivalentes_se_agrupan_bajo_una_sola_clave(): void
    {
        $this->assertSame('procesando', estado_hacienda_info('recibido')['clave']);
        $this->assertSame('pendiente', estado_hacienda_info('Sin Estado')['clave']);
        $this->assertSame('noenviado', estado_hacienda_info(null)['clave']);
        $this->assertSame('noenviado', estado_hacienda_info('')['clave']);
    }

    public function test_estado_no_distingue_mayusculas(): void
    {
        $this->assertSame('aceptado', estado_hacienda_info('ACEPTADO')['clave']);
        $this->assertSame('rechazado', estado_hacienda_info('Rechazado')['clave']);
    }

    public function test_un_estado_desconocido_no_se_pierde_ni_se_da_por_bueno(): void
    {
        $info = estado_hacienda_info('lo-que-sea');
        $this->assertSame('lo-que-sea', $info['clave']);
        $this->assertFalse($info['corregible']);
    }

    public function test_enviado_distingue_lo_que_llego_a_hacienda(): void
    {
        $this->assertFalse(estado_hacienda_info(null)['enviado']);
        $this->assertFalse(estado_hacienda_info('pendiente')['enviado']);
        $this->assertTrue(estado_hacienda_info('rechazado')['enviado']);
        $this->assertTrue(estado_hacienda_info('aceptado')['enviado']);
    }

    public function test_cada_estado_trae_tono_y_nota(): void
    {
        $tonos = ['ok', 'info', 'warn', 'err', 'muted', 'violet', 'orange'];
        foreach ([null, 'pendiente', 'procesando', 'aceptado', 'rechazado', 'error', 'anulado'] as $estado) {
            $info = estado_hacienda_info($estado);
            $this->assertContains($info['tono'], $tonos, "estado '$estado'");
            $this->assertNotSame('', $info['nota'], "estado '$estado'");
        }
    }

    /* ── resumen_mensaje_hacienda ── */

    /** El acuse real de un rechazo del sandbox, recortado. */
    private function acuse(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<MensajeHacienda xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/mensajeHacienda">'
            . '<Clave>50624082600070286071700100001010000000134163663764</Clave>'
            . '<NombreEmisor>DESCONOCIDO</NombreEmisor>'
            . '<TipoIdentificacionEmisor>01</TipoIdentificacionEmisor>'
            . '<NumeroCedulaEmisor>000000000</NumeroCedulaEmisor>'
            . '<Mensaje>3</Mensaje>'
            . '<EstadoMensaje>Rechazado</EstadoMensaje>'
            . '<DetalleMensaje>El comprobante tiene errores.</DetalleMensaje>'
            . '<MontoTotalImpuesto>184.08</MontoTotalImpuesto>'
            . '<TotalFactura>1600.02</TotalFactura>'
            . '</MensajeHacienda>';
    }

    public function test_lee_el_acuse_de_hacienda(): void
    {
        $r = resumen_mensaje_hacienda($this->acuse());

        $this->assertSame('3', $r['mensaje']);
        $this->assertSame('Rechazado', $r['estado']);
        $this->assertSame('El comprobante tiene errores.', $r['detalle']);
        $this->assertSame('184.08', $r['impuesto']);
        $this->assertSame('1600.02', $r['total']);
        $this->assertSame('DESCONOCIDO', $r['emisor']);
    }

    /**
     * Hacienda ha devuelto el mismo documento con prefijo de espacio de nombres.
     * Se busca por nombre local para que ambas formas se lean igual.
     */
    public function test_lee_el_acuse_aunque_venga_con_prefijo(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<mh:MensajeHacienda xmlns:mh="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/mensajeHacienda">'
            . '<mh:Mensaje>1</mh:Mensaje><mh:EstadoMensaje>Aceptado</mh:EstadoMensaje>'
            . '</mh:MensajeHacienda>';

        $r = resumen_mensaje_hacienda($xml);
        $this->assertSame('1', $r['mensaje']);
        $this->assertSame('Aceptado', $r['estado']);
    }

    public function test_acuse_vacio_o_roto_devuelve_null(): void
    {
        $this->assertNull(resumen_mensaje_hacienda(''));
        $this->assertNull(resumen_mensaje_hacienda(null));
        $this->assertNull(resumen_mensaje_hacienda('   '));
        $this->assertNull(resumen_mensaje_hacienda('esto no es xml <'));
    }

    public function test_acuse_sin_los_nodos_esperados_devuelve_cadenas_vacias(): void
    {
        $r = resumen_mensaje_hacienda('<Otra><Cosa>1</Cosa></Otra>');
        $this->assertSame('', $r['estado']);
        $this->assertSame('', $r['detalle']);
    }
}

<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once BASEPATH . 'app/libraries/Qzcert.php';

/**
 * Qzcert firma las peticiones que el POS le hace a QZ Tray. Si la firma deja
 * de validar, QZ Tray vuelve a tratar cada impresión como petición anónima y
 * reaparece su ventana de permiso en cada venta.
 */
class QzcertTest extends TestCase
{
    private string $dir;
    private Qzcert $qz;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/qzcert-test-' . bin2hex(random_bytes(6));
        $this->qz = new Qzcert(['dir' => $this->dir]);
    }

    protected function tearDown(): void
    {
        putenv('QZ_CERT_PATH');
        putenv('QZ_KEY_PATH');
        $this->borrar($this->dir);
    }

    private function borrar(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            is_dir($file) ? $this->borrar($file) : @unlink($file);
        }
        @rmdir($dir);
    }

    public function test_genera_el_par_la_primera_vez_que_se_pide(): void
    {
        $this->assertFalse($this->qz->has_pair());

        $cert = $this->qz->certificate();

        $this->assertStringStartsWith('-----BEGIN CERTIFICATE-----', $cert);
        $this->assertTrue($this->qz->has_pair(), $this->qz->last_error());
        $this->assertFileExists($this->qz->key_path());
    }

    public function test_no_regenera_el_certificado_en_llamadas_siguientes(): void
    {
        // Regenerar en cada petición invalidaría el override.crt ya instalado
        // en cada caja, y QZ Tray volvería a preguntar.
        $primero = $this->qz->certificate();
        $segundo = (new Qzcert(['dir' => $this->dir]))->certificate();

        $this->assertSame($primero, $segundo);
    }

    public function test_la_firma_valida_contra_el_certificado_publico(): void
    {
        $cert = $this->qz->certificate();
        $peticion = 'qz.printers.find{}1789587999000';

        $firma = $this->qz->sign($peticion);

        $this->assertNotSame('', $firma, $this->qz->last_error());
        $this->assertSame(
            1,
            openssl_verify($peticion, base64_decode($firma), openssl_pkey_get_public($cert), 'sha512'),
            'QZ Tray rechazaría esta firma'
        );
    }

    public function test_la_firma_no_sirve_para_otra_peticion(): void
    {
        $cert = $this->qz->certificate();
        $firma = $this->qz->sign('qz.printers.find{}1789587999000');

        $this->assertSame(
            0,
            openssl_verify('qz.print{}1789587999000', base64_decode($firma), openssl_pkey_get_public($cert), 'sha512')
        );
    }

    public function test_sin_llave_no_firma_y_deja_el_motivo(): void
    {
        $vacio = new Qzcert(['dir' => $this->dir . '/inexistente']);

        $this->assertSame('', $vacio->sign('qz.printers.find{}1'));
        $this->assertNotSame('', $vacio->last_error());
    }

    public function test_info_describe_el_certificado_instalado(): void
    {
        $this->assertNull($this->qz->info());

        $this->qz->certificate();
        $info = $this->qz->info();

        $this->assertSame('Neurix POS', $info['cn']);
        $this->assertGreaterThan(date('Y-m-d'), $info['vence']);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}:){19}[0-9A-F]{2}$/', $info['huella']);
    }

    public function test_el_nombre_que_muestra_qz_tray_es_configurable(): void
    {
        $qz = new Qzcert(['dir' => $this->dir, 'cn' => 'NeurixPOS Sucursal Centro']);
        $qz->certificate();

        $this->assertSame('NeurixPOS Sucursal Centro', $qz->info()['cn']);
    }

    public function test_crea_la_carpeta_de_las_rutas_configuradas_a_mano(): void
    {
        // QZ_CERT_PATH/QZ_KEY_PATH pueden apuntar fuera de la carpeta de
        // trabajo; sin crear su carpeta la escritura falla en silencio y el
        // POS vuelve a imprimir sin firma.
        $otra = $this->dir . '/otra/carpeta';
        putenv('QZ_CERT_PATH=' . $otra . '/digital-certificate.txt');
        putenv('QZ_KEY_PATH=' . $otra . '/private-key.pem');
        $qz = new Qzcert(['dir' => $this->dir]);

        $cert = $qz->certificate();

        $this->assertStringStartsWith('-----BEGIN CERTIFICATE-----', $cert, $qz->last_error());
        $this->assertFileExists($otra . '/private-key.pem');
    }
}

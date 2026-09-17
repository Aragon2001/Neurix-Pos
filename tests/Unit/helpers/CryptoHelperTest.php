<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Cifrado de credenciales. Lo que importa acá es que dos valores iguales no
 * produzcan el mismo texto cifrado —el IV es por valor— y que un texto alterado
 * no se descifre en silencio.
 */
class CryptoHelperTest extends TestCase
{
    public function test_roundtrip_cifra_y_descifra(): void
    {
        $original  = 'mi_password_secreto_123';
        $encrypted = encrypt_credential($original);

        $this->assertStringStartsWith('enc2:', $encrypted, 'El valor cifrado usa el formato con IV por valor');
        $this->assertSame($original, decrypt_credential($encrypted));
    }

    public function test_dos_valores_iguales_cifran_distinto(): void
    {
        $a = encrypt_credential('la misma clave');
        $b = encrypt_credential('la misma clave');

        $this->assertNotSame($a, $b, 'Con IV aleatorio, cifrar dos veces no puede dar lo mismo');
        $this->assertSame('la misma clave', decrypt_credential($a));
        $this->assertSame('la misma clave', decrypt_credential($b));
    }

    public function test_no_cifra_valor_ya_cifrado(): void
    {
        $firstPass  = encrypt_credential('valor_plano');
        $secondPass = encrypt_credential($firstPass);

        $this->assertSame($firstPass, $secondPass, 'Cifrar un valor ya cifrado debe devolver el mismo string');
    }

    public function test_un_valor_alterado_no_se_descifra(): void
    {
        $cifrado = encrypt_credential('secreto');
        $crudo   = base64_decode(substr($cifrado, 5));
        $crudo[strlen($crudo) - 1] = chr(ord($crudo[strlen($crudo) - 1]) ^ 0xFF);
        $alterado = 'enc2:' . base64_encode($crudo);

        $this->assertSame('', decrypt_credential($alterado), 'La etiqueta GCM tiene que rechazarlo');
    }

    public function test_lee_el_formato_anterior(): void
    {
        // Cifrado con AES-256-CBC e IV fijo, como lo guardaba la versión previa.
        $key = substr(hash('sha256', config_item('encryption_key')), 0, 32);
        $iv  = substr(hash('sha256', 'neurix_pos_iv'), 0, 16);
        $viejo = 'enc:' . base64_encode(openssl_encrypt('clave_vieja', 'AES-256-CBC', $key, 0, $iv));

        $this->assertSame('clave_vieja', decrypt_credential($viejo));
        $this->assertTrue(credencial_es_antigua($viejo));
        $this->assertFalse(credencial_es_antigua(encrypt_credential('nueva')));
    }

    public function test_devuelve_texto_plano_legacy(): void
    {
        $legacy = 'password_sin_prefijo';
        $this->assertSame($legacy, decrypt_credential($legacy), 'Valores legacy sin prefijo deben devolverse tal cual');
    }

    public function test_valor_vacio_no_se_cifra(): void
    {
        $this->assertSame('', encrypt_credential(''));
        $this->assertSame('', decrypt_credential(''));
    }
}

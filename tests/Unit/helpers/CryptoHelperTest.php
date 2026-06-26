<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CryptoHelperTest extends TestCase
{
    public function test_roundtrip_cifra_y_descifra(): void
    {
        $original  = 'mi_password_secreto_123';
        $encrypted = encrypt_credential($original);

        $this->assertStringStartsWith('enc:', $encrypted, 'El valor cifrado debe empezar con "enc:"');
        $this->assertSame($original, decrypt_credential($encrypted));
    }

    public function test_no_cifra_valor_ya_cifrado(): void
    {
        $original  = 'valor_plano';
        $firstPass = encrypt_credential($original);
        $secondPass = encrypt_credential($firstPass);

        $this->assertSame($firstPass, $secondPass, 'Cifrar un valor ya cifrado debe devolver el mismo string');
    }

    public function test_devuelve_texto_plano_legacy(): void
    {
        $legacy = 'password_sin_prefijo';
        $this->assertSame($legacy, decrypt_credential($legacy), 'Valores legacy sin "enc:" deben devolverse tal cual');
    }

    public function test_valor_vacio_no_se_cifra(): void
    {
        $this->assertSame('', encrypt_credential(''));
        $this->assertSame('', decrypt_credential(''));
    }
}

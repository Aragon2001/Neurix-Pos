<?php

// Stubs mínimos para que los helpers de CI3 carguen sin el framework
define('BASEPATH', __DIR__ . '/../');
define('APPPATH',  __DIR__ . '/../app/');
define('ENVIRONMENT', 'testing');

// config_item() stub — sólo las claves que usan los helpers testeados
if (!function_exists('config_item')) {
    function config_item(string $key): mixed
    {
        $config = [
            'encryption_key' => 'neurix_test_encryption_key_32chr',
        ];
        return $config[$key] ?? null;
    }
}

require_once APPPATH . 'helpers/crypto_helper.php';
require_once APPPATH . 'helpers/pos_helper.php';

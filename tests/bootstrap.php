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

// lang() stub — los helpers rotulan con claves de idioma; en las pruebas
// interesa la clave, no la traducción.
if (!function_exists('lang')) {
    function lang(string $linea, string $id = ''): string
    {
        return $linea;
    }
}

// html_escape() es de CI: los helpers la usan y las pruebas corren sin framework.
if (!function_exists('html_escape')) {
    function html_escape($var): mixed
    {
        if (is_array($var)) {
            return array_map('html_escape', $var);
        }
        return htmlspecialchars((string) $var, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

require_once APPPATH . 'helpers/crypto_helper.php';

require_once APPPATH . 'helpers/pos_helper.php';

require_once APPPATH . 'helpers/comprobante_helper.php';

// Motor de reconciliacion factura vs. documento editado.
require_once APPPATH . 'helpers/ajuste_helper.php';

// Diccionario de datos de los informes: rangos de fecha, tarifas, semaforos.
require_once APPPATH . 'helpers/reportes_helper.php';

// Documentos recibidos: lectura del XML, relacion de lineas y mensaje receptor.
require_once APPPATH . 'helpers/compra_helper.php';

// log_message() es de CI; los modelos de informes lo llaman al capturar el
// fallo de una comprobacion, y sin el la prueba muere donde deberia registrar.
if (!function_exists('log_message')) {
    function log_message(string $nivel, string $mensaje): void
    {
    }
}

// Entorno con base de datos: las pruebas que lo usan se saltan solas cuando no
// hay servidor, para que la suite siga corriendo en una maquina sin MySQL.
require_once __DIR__ . '/stubs/DbEntorno.php';
require_once __DIR__ . '/stubs/ReporteFixture.php';
require_once __DIR__ . '/stubs/ZipLector.php';

// Librerias de salida: no dependen del framework, solo de BASEPATH.
require_once APPPATH . 'libraries/Nx_xlsx.php';

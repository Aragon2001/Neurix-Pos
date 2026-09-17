<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cifrado de credenciales guardadas en la base.
 *
 * Formato actual: `enc2:` + base64(iv | etiqueta | texto cifrado) con
 * AES-256-GCM e IV aleatorio por valor. El formato anterior (`enc:`, AES-256-CBC
 * con IV fijo) se sigue leyendo para no perder lo ya guardado, pero nada nuevo
 * se escribe así: un IV compartido delata qué credenciales son iguales entre sí
 * y no autentica el texto cifrado.
 */

if (!function_exists('nx_clave_cifrado')) {
    /**
     * Llave efectiva de 32 bytes.
     *
     * NX_ENCRYPTION_KEY (variable de entorno) tiene prioridad sobre el archivo
     * de configuración: en un servidor la llave no debe vivir en el repositorio.
     */
    function nx_clave_cifrado()
    {
        $clave = getenv('NX_ENCRYPTION_KEY');
        if (!$clave) {
            $clave = (string) config_item('encryption_key');
        }
        return hash('sha256', $clave, true);
    }
}

if (!function_exists('encrypt_credential')) {
    function encrypt_credential($value)
    {
        if (empty($value)) {
            return $value;
        }
        if (strpos($value, 'enc2:') === 0 || strpos($value, 'enc:') === 0) {
            return $value;   // ya cifrado
        }

        $iv  = random_bytes(12);            // GCM trabaja con 96 bits
        $tag = '';
        $cifrado = openssl_encrypt($value, 'aes-256-gcm', nx_clave_cifrado(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($cifrado === false) {
            log_message('error', '[Cripto] no se pudo cifrar la credencial');
            return $value;
        }

        return 'enc2:' . base64_encode($iv . $tag . $cifrado);
    }
}

if (!function_exists('decrypt_credential')) {
    function decrypt_credential($value)
    {
        if (empty($value)) {
            return $value;
        }

        if (strpos($value, 'enc2:') === 0) {
            $crudo = base64_decode(substr($value, 5), true);
            if ($crudo === false || strlen($crudo) < 29) {
                log_message('error', '[Cripto] credencial enc2 mal formada');
                return '';
            }
            $iv      = substr($crudo, 0, 12);
            $tag     = substr($crudo, 12, 16);
            $cifrado = substr($crudo, 28);

            $claro = openssl_decrypt($cifrado, 'aes-256-gcm', nx_clave_cifrado(), OPENSSL_RAW_DATA, $iv, $tag);
            if ($claro === false) {
                // La etiqueta no cuadra: o la llave cambió o el valor fue alterado.
                log_message('error', '[Cripto] credencial enc2 que no autentica');
                return '';
            }
            return $claro;
        }

        // Formato anterior, solo lectura.
        if (strpos($value, 'enc:') === 0) {
            $key = substr(hash('sha256', config_item('encryption_key')), 0, 32);
            $iv  = substr(hash('sha256', 'neurix_pos_iv'), 0, 16);
            $claro = openssl_decrypt(base64_decode(substr($value, 4)), 'AES-256-CBC', $key, 0, $iv);
            return $claro === false ? '' : $claro;
        }

        return $value;   // texto plano de instalaciones viejas
    }
}

if (!function_exists('credencial_es_antigua')) {
    /** ¿Este valor sigue guardado con el formato anterior? */
    function credencial_es_antigua($value)
    {
        return !empty($value) && strpos($value, 'enc:') === 0;
    }
}

if (!function_exists('llave_cifrado_de_fabrica')) {
    /**
     * La llave que viene en el repositorio no protege nada: cualquiera que
     * tenga el código puede descifrar lo que se guardó con ella.
     */
    function llave_cifrado_de_fabrica()
    {
        return !getenv('NX_ENCRYPTION_KEY')
            && config_item('encryption_key') === 'UM97lQzuVJkPrqpGixDeFImSBh5fL2';
    }
}

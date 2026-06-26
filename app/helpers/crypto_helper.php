<?php

defined('BASEPATH') or exit('No direct script access allowed');

function encrypt_credential($value)
{
    if (empty($value)) return $value;
    if (strpos($value, 'enc:') === 0) return $value; // ya cifrado
    $key = substr(hash('sha256', config_item('encryption_key')), 0, 32);
    $iv  = substr(hash('sha256', 'neurix_pos_iv'), 0, 16);
    return 'enc:' . base64_encode(openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv));
}

function decrypt_credential($value)
{
    if (empty($value) || strpos($value, 'enc:') !== 0) return $value; // texto plano (legacy)
    $key = substr(hash('sha256', config_item('encryption_key')), 0, 32);
    $iv  = substr(hash('sha256', 'neurix_pos_iv'), 0, 16);
    return openssl_decrypt(base64_decode(substr($value, 4)), 'AES-256-CBC', $key, 0, $iv);
}

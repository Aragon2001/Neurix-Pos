<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dispara el worker de cola en background via HTTP (fire-and-forget).
 * No bloquea al llamador: abre socket, envía request GET y cierra sin leer la respuesta.
 */
if (!function_exists('dispatch_queue_worker')) {
    function dispatch_queue_worker($type = null)
    {
        $CI   = &get_instance();
        $path = site_url('queue_worker/run' . ($type ? "/$type" : ''));
        $parts = parse_url($path);
        $host  = $parts['host'];
        $port  = isset($parts['port']) ? (int)$parts['port'] : (($parts['scheme'] === 'https') ? 443 : 80);
        $uri   = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

        $prefix = ($parts['scheme'] === 'https') ? 'ssl://' : '';

        // El destino sale de site_url(), que se arma con la cabecera Host. Aunque
        // config.php ya la filtra, aca se vuelve a comprobar: esta llamada la hace
        // el servidor, y apuntarla a otra maquina la convierte en un explorador de
        // la red interna.
        $permitidos = array_filter(array_map('trim', explode(',', (string) (getenv('APP_HOSTS') ?: ''))));
        $permitidos = array_merge($permitidos, array('localhost', '127.0.0.1', '::1'));
        $es_privada = filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

        if (!in_array($host, $permitidos, true) && !$es_privada) {
            log_message('error', '[Cola] destino no permitido: ' . $host);
            return;
        }
        if ($port !== 80 && $port !== 443 && $port < 1024) {
            log_message('error', '[Cola] puerto no permitido: ' . $port);
            return;
        }

        $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, 2);
        if ($fp) {
            $req = "GET $uri HTTP/1.1\r\n"
                 . "Host: $host\r\n"
                 . "Connection: close\r\n\r\n";
            fwrite($fp, $req);
            fclose($fp);
        }
        // Si fsockopen falla, los jobs quedan en 'pending' y el cron los recogerá
    }
}

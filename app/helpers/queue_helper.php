<?php

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

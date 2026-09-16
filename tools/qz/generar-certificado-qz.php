<?php
/**
 * Genera el par certificado + llave privada con el que este POS firma sus
 * peticiones a QZ Tray.
 *
 * Sin firma, QZ Tray trata cada petición como "An anonymous request" y abre
 * su ventana nativa una y otra vez, sin poder recordar la decisión (la huella
 * de la petición es "UNKNOWN REQUEST", así que no hay nada que recordar).
 *
 * Uso:
 *   php tools/qz/generar-certificado-qz.php
 *   php tools/qz/generar-certificado-qz.php --cn="Neurix POS - Sucursal Centro" --dias=7300
 *
 * Opciones:
 *   --cn=TEXTO            Nombre que QZ Tray mostrará como titular (Common Name)
 *   --org=TEXTO           Organización mostrada en el diálogo de QZ Tray
 *   --dias=N              Vigencia del certificado (por defecto 7300 = 20 años)
 *   --dir=RUTA            Carpeta destino (por defecto files/certificados/qz)
 *   --openssl-conf=RUTA   openssl.cnf a usar (Windows suele necesitarlo)
 *   --force               Sobrescribe un par ya existente
 *
 * La llave privada NUNCA debe copiarse a las terminales ni al navegador:
 * solo el servidor la usa, desde PosPrint::qz_sign().
 */

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se ejecuta desde la linea de comandos.\n");
}
if (!function_exists('openssl_pkey_new')) {
    exit("Falta la extension openssl de PHP. Actívala en php.ini (extension=openssl).\n");
}

$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/i', $arg, $m)) {
        $opts[$m[1]] = isset($m[2]) ? $m[2] : true;
    }
}

$root    = dirname(__DIR__, 2);
$dir     = isset($opts['dir']) && is_string($opts['dir']) ? rtrim($opts['dir'], "/\\") : $root . '/files/certificados/qz';
$cn      = isset($opts['cn']) && is_string($opts['cn']) ? $opts['cn'] : 'Neurix POS';
$org     = isset($opts['org']) && is_string($opts['org']) ? $opts['org'] : 'Neurix POS';
$dias    = isset($opts['dias']) ? max(1, (int) $opts['dias']) : 7300;
$force   = isset($opts['force']);
$certOut = $dir . '/digital-certificate.txt';
$keyOut  = $dir . '/private-key.pem';

if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
    exit("No se pudo crear la carpeta $dir\n");
}
if (!$force && (file_exists($certOut) || file_exists($keyOut))) {
    exit("Ya existe un certificado en $dir\n"
        . "Usa --force para reemplazarlo (tendras que volver a confiar el nuevo certificado en cada terminal).\n");
}

// Windows: PHP no trae openssl.cnf y openssl_pkey_new falla sin el. Se busca
// en las rutas tipicas de Laragon/XAMPP antes de rendirse.
$conf = isset($opts['openssl-conf']) && is_string($opts['openssl-conf']) ? $opts['openssl-conf'] : null;
if (!$conf) {
    $candidatos = array_merge(
        (array) getenv('OPENSSL_CONF'),
        glob('C:/laragon/bin/php/*/extras/ssl/openssl.cnf') ?: [],
        glob('C:/xampp/php/extras/ssl/openssl.cnf') ?: [],
        ['C:/xampp/apache/conf/openssl.cnf', dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf', '/etc/ssl/openssl.cnf']
    );
    foreach ($candidatos as $c) {
        if ($c && is_readable($c)) { $conf = $c; break; }
    }
}
$config = $conf ? ['config' => $conf] : [];

$key = openssl_pkey_new($config + [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
]);
if (!$key) {
    $err = '';
    while ($e = openssl_error_string()) { $err .= "  $e\n"; }
    exit("No se pudo generar la llave privada.\n$err"
        . "En Windows suele faltar openssl.cnf: vuelve a ejecutar con --openssl-conf=\"C:\\laragon\\bin\\php\\php-8.x\\extras\\ssl\\openssl.cnf\"\n"
        . "o genera el par a mano:\n"
        . "  openssl req -x509 -newkey rsa:2048 -days $dias -nodes -keyout private-key.pem -out digital-certificate.txt -subj \"/CN=$cn\"\n");
}

$dn = [
    'countryName'            => 'CR',
    'organizationName'       => $org,
    'organizationalUnitName' => 'Punto de Venta',
    'commonName'             => $cn,
];
$csr  = openssl_csr_new($dn, $key, $config + ['digest_alg' => 'sha256']);
$cert = $csr ? openssl_csr_sign($csr, null, $key, $dias, $config + ['digest_alg' => 'sha256'], random_int(1, PHP_INT_MAX)) : false;
if (!$cert) {
    $err = '';
    while ($e = openssl_error_string()) { $err .= "  $e\n"; }
    exit("No se pudo firmar el certificado.\n$err");
}

openssl_x509_export($cert, $certPem);
openssl_pkey_export($key, $keyPem, null, $config);

file_put_contents($certOut, $certPem);
file_put_contents($keyOut, $keyPem);
@chmod($keyOut, 0600);
@chmod($certOut, 0644);
if (!file_exists($dir . '/index.html')) {
    file_put_contents($dir . '/index.html', "<!DOCTYPE html><title>403</title>Directory access is forbidden.\n");
}

$info = openssl_x509_parse($certPem);
echo "Certificado generado para QZ Tray\n";
echo "  Titular : " . $info['subject']['CN'] . "\n";
echo "  Vence   : " . date('d/m/Y', $info['validTo_time_t']) . "\n";
echo "  Publico : $certOut\n";
echo "  Privada : $keyOut  (NO copiarla a las terminales)\n\n";
echo "Siguientes pasos:\n";
echo "  1. Verifica que el POS lo sirva: <url-del-pos>/posprint/qz_certificate\n";
echo "  2. En CADA terminal con QZ Tray, ejecuta como administrador\n";
echo "     themes/default/assets/confiar-qz-tray.bat  (instala override.crt y reinicia QZ Tray)\n";

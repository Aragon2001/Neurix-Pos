<?php
/**
 * Regenera a mano el par certificado + llave con el que el POS firma sus
 * peticiones a QZ Tray.
 *
 * NO hace falta para una instalación nueva: el POS genera el par solo la
 * primera vez que una caja pide el certificado (app/libraries/Qzcert.php).
 * Este script sirve para regenerarlo (por ejemplo para cambiar el nombre que
 * muestra la ventana de QZ Tray) o para diagnosticar problemas de OpenSSL.
 *
 * Uso:
 *   php tools/qz/generar-certificado-qz.php --info
 *   php tools/qz/generar-certificado-qz.php --force --cn="NeurixPOS"
 *
 * Opciones:
 *   --info                Muestra el certificado actual y no cambia nada
 *   --cn=TEXTO            Nombre que QZ Tray muestra como titular
 *   --org=TEXTO           Organización mostrada en el diálogo de QZ Tray
 *   --dias=N              Vigencia (por defecto 7300 = 20 años)
 *   --dir=RUTA            Carpeta destino (por defecto files/certificados/qz)
 *   --openssl-conf=RUTA   openssl.cnf a usar (Windows suele necesitarlo)
 *   --force               Reemplaza el par existente
 *
 * Ojo: al regenerar cambia la huella, así que hay que volver a ejecutar el
 * instalador en cada caja (o confiar-qz-tray.bat) para reponer override.crt.
 */

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se ejecuta desde la linea de comandos.\n");
}

$root = dirname(__DIR__, 2);
define('BASEPATH', true);
define('FCPATH', $root . '/');
require $root . '/app/libraries/Qzcert.php';

$opts = array();
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/i', $arg, $m)) {
        $opts[$m[1]] = isset($m[2]) ? $m[2] : true;
    }
}
if (isset($opts['openssl-conf']) && is_string($opts['openssl-conf'])) {
    putenv('QZ_OPENSSL_CONF=' . $opts['openssl-conf']);
}

$params = array();
foreach (array('dir', 'cn', 'org') as $k) {
    if (isset($opts[$k]) && is_string($opts[$k])) $params[$k] = $opts[$k];
}
$qz = new Qzcert($params);

if (isset($opts['info'])) {
    $info = $qz->info();
    if (!$info) {
        exit("Todavia no hay certificado en " . $qz->cert_path() . "\n"
            . "Se creara solo la primera vez que una caja abra el POS.\n");
    }
    echo "Certificado actual\n";
    echo "  Titular : {$info['cn']}\n";
    echo "  Vence   : {$info['vence']}\n";
    echo "  Huella  : {$info['huella']}\n";
    echo "  Archivo : " . $qz->cert_path() . "\n";
    exit(0);
}

if ($qz->has_pair() && !isset($opts['force'])) {
    exit("Ya existe un certificado en " . $qz->cert_path() . "\n"
        . "Usa --info para verlo, o --force para reemplazarlo (habra que\n"
        . "reinstalar el permiso en cada caja con el instalador del POS).\n");
}

$dias = isset($opts['dias']) ? max(1, (int) $opts['dias']) : Qzcert::DIAS;
if (!$qz->generate($dias)) {
    echo "ERROR: " . $qz->last_error() . "\n";
    echo "Alternativa manual:\n";
    echo "  openssl req -x509 -newkey rsa:2048 -days $dias -nodes \\\n";
    echo "    -keyout \"" . $qz->key_path() . "\" -out \"" . $qz->cert_path() . "\" -subj \"/CN=Neurix POS\"\n";
    exit(1);
}

$info = $qz->info();
echo "Certificado generado para QZ Tray\n";
echo "  Titular : {$info['cn']}\n";
echo "  Vence   : {$info['vence']}\n";
echo "  Huella  : {$info['huella']}\n";
echo "  Publico : " . $qz->cert_path() . "\n";
echo "  Privada : " . $qz->key_path() . "  (NO copiarla a las cajas)\n\n";
echo "En cada caja: volver a descargar el instalador desde el POS\n";
echo "(boton \"Descargar e instalar\") para reponer el permiso override.crt.\n";

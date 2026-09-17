<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Certificado con el que este POS firma sus peticiones a QZ Tray.
 *
 * QZ Tray no valida el certificado TLS del sitio: valida la firma de cada
 * petición. Sin firma toda petición es anónima ("An anonymous request",
 * Fingerprint: UNKNOWN REQUEST) y su ventana nativa vuelve a aparecer cada
 * vez, porque no hay huella que recordar.
 *
 * El par se genera solo la primera vez que alguien lo necesita — no hace
 * falta ningún paso manual en el servidor. La llave privada nunca sale de
 * aquí: al navegador solo viajan el certificado público y la firma.
 *
 * Esta clase no usa nada de CodeIgniter, así que también se puede cargar
 * desde un script CLI (ver tools/qz/generar-certificado-qz.php).
 */
class Qzcert
{
    /** Vigencia por defecto del certificado autofirmado (20 años). */
    const DIAS = 7300;

    private $dir;
    private $cn;
    private $org;
    private $error = '';

    public function __construct($params = array())
    {
        $root = defined('FCPATH') ? FCPATH : dirname(__DIR__, 2) . '/';
        $dir = isset($params['dir']) ? $params['dir'] : getenv('QZ_CERT_DIR');
        $this->dir = rtrim($dir ? $dir : $root . 'files/certificados/qz', "/\\");
        $cn = isset($params['cn']) ? $params['cn'] : getenv('QZ_CERT_CN');
        $this->cn = $cn ? $cn : 'Neurix POS';
        $org = isset($params['org']) ? $params['org'] : getenv('QZ_CERT_ORG');
        $this->org = $org ? $org : 'Neurix POS';
    }

    public function cert_path()
    {
        $path = getenv('QZ_CERT_PATH');
        return $path ? $path : $this->dir . '/digital-certificate.txt';
    }

    public function key_path()
    {
        $path = getenv('QZ_KEY_PATH');
        return $path ? $path : $this->dir . '/private-key.pem';
    }

    public function last_error()
    {
        return $this->error;
    }

    /** Certificado público en PEM, generándolo si todavía no existe. */
    public function certificate()
    {
        $this->ensure();
        $cert = is_readable($this->cert_path()) ? trim((string) file_get_contents($this->cert_path())) : '';
        return strpos($cert, '-----BEGIN CERTIFICATE-----') === 0 ? $cert : '';
    }

    /** Firma SHA-512 en base64 de la cadena que pide QZ Tray. */
    public function sign($data)
    {
        $key_path = $this->key_path();
        if ($data === '' || !is_readable($key_path) || !function_exists('openssl_sign')) {
            $this->error = 'Sin llave privada legible en ' . $key_path;
            return '';
        }
        $pass = getenv('QZ_KEY_PASS');
        $key = openssl_pkey_get_private(file_get_contents($key_path), $pass === false ? '' : $pass);
        if (!$key) {
            $this->error = 'No se pudo abrir la llave privada: ' . $this->openssl_errors();
            return '';
        }
        $signature = '';
        $ok = openssl_sign($data, $signature, $key, 'sha512');
        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($key);
        }
        if (!$ok) {
            $this->error = 'openssl_sign fallo: ' . $this->openssl_errors();
            return '';
        }
        return base64_encode($signature);
    }

    public function has_pair()
    {
        return is_readable($this->cert_path()) && is_readable($this->key_path());
    }

    /**
     * Genera el par si falta. Idempotente y a prueba de peticiones
     * simultáneas (dos cajas abriendo el POS al mismo tiempo generarían dos
     * certificados distintos si no se serializara con un lock).
     */
    public function ensure()
    {
        if ($this->has_pair()) {
            return true;
        }
        if (!$this->prepare_dir()) {
            return false;
        }

        $lock_path = $this->dir . '/.generating.lock';
        $lock = @fopen($lock_path, 'c');
        if ($lock && flock($lock, LOCK_EX)) {
            // Otra petición pudo haberlo generado mientras esperábamos el lock.
            if (!$this->has_pair()) {
                $this->generate();
            }
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($lock_path);
        } else {
            if ($lock) fclose($lock);
            $this->generate();
        }

        return $this->has_pair();
    }

    /** Genera (o regenera) el par autofirmado. */
    public function generate($dias = self::DIAS)
    {
        if (!function_exists('openssl_pkey_new')) {
            $this->error = 'Falta la extension openssl de PHP (extension=openssl en php.ini).';
            return false;
        }
        if (!$this->prepare_dir()) {
            return false;
        }

        $config = $this->openssl_config();
        $key = openssl_pkey_new($config + array(
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ));
        if (!$key) {
            $this->error = 'No se pudo generar la llave privada: ' . $this->openssl_errors()
                . ' (en Windows suele faltar openssl.cnf; ver QZ_OPENSSL_CONF en README-QZ-TRAY.md)';
            return false;
        }

        $dn = array(
            'countryName'            => 'CR',
            'organizationName'       => $this->org,
            'organizationalUnitName' => 'Punto de Venta',
            'commonName'             => $this->cn,
        );
        $csr = openssl_csr_new($dn, $key, $config + array('digest_alg' => 'sha256'));
        $cert = $csr ? openssl_csr_sign($csr, null, $key, $dias, $config + array('digest_alg' => 'sha256'), random_int(1, PHP_INT_MAX)) : false;
        if (!$cert) {
            $this->error = 'No se pudo firmar el certificado: ' . $this->openssl_errors();
            return false;
        }

        $cert_pem = '';
        $key_pem = '';
        openssl_x509_export($cert, $cert_pem);
        openssl_pkey_export($key, $key_pem, null, $config);

        // La llave se escribe primero y el certificado de último: has_pair()
        // solo es cierto cuando ambos están completos.
        if (@file_put_contents($this->key_path(), $key_pem) === false
            || @file_put_contents($this->cert_path(), $cert_pem) === false) {
            $this->error = 'Sin permiso de escritura en ' . $this->dir;
            return false;
        }
        @chmod($this->key_path(), 0600);
        @chmod($this->cert_path(), 0644);

        return true;
    }

    /** Datos del certificado actual (para pantallas de diagnóstico). */
    public function info()
    {
        $cert = is_readable($this->cert_path()) ? file_get_contents($this->cert_path()) : '';
        $parsed = $cert ? @openssl_x509_parse($cert) : false;
        if (!$parsed) {
            return null;
        }
        return array(
            'cn'      => isset($parsed['subject']['CN']) ? $parsed['subject']['CN'] : '',
            'vence'   => date('Y-m-d', $parsed['validTo_time_t']),
            'huella'  => strtoupper(implode(':', str_split(sha1(base64_decode(preg_replace('/-----[^-]+-----|\s+/', '', $cert))), 2))),
        );
    }

    private function prepare_dir()
    {
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0750, true)) {
            $this->error = 'No se pudo crear la carpeta ' . $this->dir;
            return false;
        }
        $index = $this->dir . '/index.html';
        if (!file_exists($index)) {
            @file_put_contents($index, "<!DOCTYPE html><title>403 Forbidden</title>Directory access is forbidden.\n");
        }
        return true;
    }

    /**
     * PHP en Windows no trae openssl.cnf y openssl_pkey_new falla sin él; se
     * busca en las rutas típicas de Laragon/XAMPP antes de rendirse.
     */
    private function openssl_config()
    {
        $conf = getenv('QZ_OPENSSL_CONF');
        if (!$conf || !is_readable($conf)) {
            $conf = null;
            $candidatos = array_merge(
                array(getenv('OPENSSL_CONF')),
                glob('C:/laragon/bin/php/*/extras/ssl/openssl.cnf') ?: array(),
                glob('C:/xampp/php/extras/ssl/openssl.cnf') ?: array(),
                array(
                    'C:/xampp/apache/conf/openssl.cnf',
                    dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf',
                    '/etc/ssl/openssl.cnf',
                )
            );
            foreach ($candidatos as $c) {
                if ($c && is_readable($c)) { $conf = $c; break; }
            }
        }
        return $conf ? array('config' => $conf) : array();
    }

    private function openssl_errors()
    {
        $msgs = array();
        while ($e = openssl_error_string()) { $msgs[] = $e; }
        return $msgs ? implode(' | ', $msgs) : 'sin detalle de OpenSSL';
    }
}

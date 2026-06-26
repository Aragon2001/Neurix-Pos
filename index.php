<?php
/*
 * CodeIgniter Bootstrap
 * Sistema de Facturación Electrónica CR
 */

ob_start();

// Carga variables de entorno desde .env antes de que CI arranque
$_envFile = __DIR__ . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (strpos(trim($_line), '#') === 0 || strpos($_line, '=') === false) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k);
        $_v = trim($_v, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($_k, $_SERVER) && !array_key_exists($_k, $_ENV)) {
            putenv("$_k=$_v");
            $_ENV[$_k] = $_v;
        }
    }
}
unset($_envFile, $_line, $_k, $_v);

// PHP 8.1+ activa por defecto mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT),
// que convierte cualquier error de consulta (columna/tabla inexistente, etc.) en una
// excepción no controlada -> error 500 en blanco, sin registro en los logs de PHP.
// Este driver de CodeIgniter 3 (2019) no contempla ese comportamiento, así que lo
// desactivamos para volver al comportamiento esperado por el framework (db_debug
// decide qué se muestra, en vez de un fatal error silencioso).
if (function_exists('mysqli_report')) {
	mysqli_report(MYSQLI_REPORT_OFF);
}

define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');

switch (ENVIRONMENT)
{
	case 'development':
		error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_USER_DEPRECATED);
		ini_set('display_errors', 1);
	break;

	case 'testing':
	case 'production':
		ini_set('display_errors', 0);
		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
	break;

	default:
		header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
		echo 'The application environment is not set correctly.';
		exit(1);
}

$system_path      = 'system';
$application_folder = 'app';
$view_folder      = 'themes';

// ----------------------------------------------------------------
// DO NOT EDIT BELOW THIS LINE
// ----------------------------------------------------------------

if (defined('STDIN'))
{
	chdir(dirname(__FILE__));
}

if (($_temp = realpath($system_path)) !== FALSE)
{
	$system_path = $_temp.DIRECTORY_SEPARATOR;
}
else
{
	$system_path = strtr(
		rtrim($system_path, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
	).DIRECTORY_SEPARATOR;
}

if ( ! is_dir($system_path))
{
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your system folder path does not appear to be set correctly. Please open the following file and correct this: '.pathinfo(__FILE__, PATHINFO_BASENAME);
	exit(3);
}

define('SELF',    pathinfo(__FILE__, PATHINFO_BASENAME));
define('BASEPATH', $system_path);
define('FCPATH',  dirname(__FILE__).DIRECTORY_SEPARATOR);
define('SYSDIR',  basename(BASEPATH));

if (is_dir($application_folder))
{
	if (($_temp = realpath($application_folder)) !== FALSE)
	{
		$application_folder = $_temp;
	}
	else
	{
		$application_folder = strtr(
			rtrim($application_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
		);
	}
}
elseif (is_dir(BASEPATH.$application_folder.DIRECTORY_SEPARATOR))
{
	$application_folder = BASEPATH.strtr(
		trim($application_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
	);
}
else
{
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your application folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
	exit(3);
}

define('APPPATH', $application_folder.DIRECTORY_SEPARATOR);

if ( ! isset($view_folder[0]) && is_dir(APPPATH.'views'.DIRECTORY_SEPARATOR))
{
	$view_folder = APPPATH.'views';
}
elseif (is_dir($view_folder))
{
	if (($_temp = realpath($view_folder)) !== FALSE)
	{
		$view_folder = $_temp;
	}
	else
	{
		$view_folder = strtr(
			rtrim($view_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
		);
	}
}
elseif (is_dir(APPPATH.$view_folder.DIRECTORY_SEPARATOR))
{
	$view_folder = APPPATH.strtr(
		trim($view_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR
	);
}
else
{
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your view folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
	exit(3);
}

define('VIEWPATH', $view_folder.DIRECTORY_SEPARATOR);

require_once BASEPATH.'core/CodeIgniter.php';

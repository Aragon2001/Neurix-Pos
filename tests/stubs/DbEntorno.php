<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

/**
 * Entorno mínimo de CI3 sobre mysqli para probar modelos contra una base real.
 *
 * Los modelos de informes son casi todo SQL: probarlos con dobles de la capa de
 * datos comprobaría el andamiaje y no la consulta, que es justo donde estaban
 * los errores. Así que las pruebas hablan con MySQL de verdad y se saltan solas
 * cuando no hay servidor.
 */

if (!class_exists('DbEntornoDB')) {

    /** Resultado de consulta con la interfaz que usan los modelos. */
    class DbEntornoResult
    {
        private $filas;

        public function __construct(array $filas)
        {
            $this->filas = $filas;
        }

        public function result_array()
        {
            return $this->filas;
        }

        public function row_array()
        {
            return $this->filas ? $this->filas[0] : null;
        }

        public function row()
        {
            return $this->filas ? (object) $this->filas[0] : null;
        }

        public function num_rows()
        {
            return count($this->filas);
        }
    }

    /** Lo que `$this->db` necesita ofrecer: consultas ligadas y el prefijo. */
    class DbEntornoDB
    {
        /** @var mysqli */
        public $conn;
        private $prefijo;

        /** @var string[] consultas ejecutadas, para afirmar sobre ellas */
        public $ejecutadas = array();

        public function __construct(mysqli $conn, $prefijo = 'tec_')
        {
            $this->conn    = $conn;
            $this->prefijo = $prefijo;
        }

        public function dbprefix($tabla = '')
        {
            return $this->prefijo . $tabla;
        }

        public function table_exists($tabla)
        {
            $n = $this->conn->real_escape_string($this->prefijo . $tabla);
            $r = $this->conn->query("SHOW TABLES LIKE '{$n}'");
            return $r && $r->num_rows > 0;
        }

        /**
         * Ejecuta con parámetros ligados, igual que `CI_DB::query()` con `?`.
         *
         * Es la única vía: si un modelo interpola un valor en la cadena SQL,
         * acá no hay forma de ligarlo y la prueba de inyección lo delata.
         */
        public function query($sql, $bind = array())
        {
            $this->ejecutadas[] = $sql;

            if (!$bind) {
                $r = $this->conn->query($sql);
                if ($r === false) {
                    throw new RuntimeException('SQL: ' . $this->conn->error . "\n" . $sql);
                }
                return new DbEntornoResult($r === true ? array() : $r->fetch_all(MYSQLI_ASSOC));
            }

            $st = $this->conn->prepare($sql);
            if (!$st) {
                throw new RuntimeException('prepare: ' . $this->conn->error . "\n" . $sql);
            }
            $tipos = '';
            foreach ($bind as $v) {
                $tipos .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
            }
            $st->bind_param($tipos, ...$bind);
            if (!$st->execute()) {
                throw new RuntimeException('execute: ' . $st->error . "\n" . $sql);
            }
            $res = $st->get_result();
            $out = $res ? $res->fetch_all(MYSQLI_ASSOC) : array();
            $st->close();
            return new DbEntornoResult($out);
        }
    }

    /** `$this->session->userdata()`, que el filtro consulta para la sucursal. */
    class DbEntornoSession
    {
        private $datos;

        public function __construct(array $datos = array())
        {
            $this->datos = $datos;
        }

        public function userdata($k = null)
        {
            if ($k === null) {
                return $this->datos;
            }
            return isset($this->datos[$k]) ? $this->datos[$k] : null;
        }
    }

    /** `$this->load->helper()` y `->model()`, reducidos a lo que se usa. */
    class DbEntornoLoader
    {
        public function helper($h)
        {
            foreach ((array) $h as $x) {
                $ruta = APPPATH . 'helpers/' . $x . '_helper.php';
                if (is_file($ruta)) {
                    require_once $ruta;
                }
            }
        }

        public function model($m, $alias = '')
        {
        }

        public function library($l)
        {
        }
    }

    if (!class_exists('CI_Model')) {
        /** Raíz de los modelos de CI3, sin el framework detrás. */
        class CI_Model
        {
            public $db;
            public $session;
            public $load;
            public $Settings;

            public function __construct()
            {
            }
        }
    }
}

/**
 * Fábrica del entorno de pruebas con base de datos.
 *
 * Devuelve `null` cuando no hay servidor, para que la prueba se marque como
 * omitida en vez de fallar en una máquina sin MySQL.
 */
class DbEntorno
{
    /** @var mysqli|null */
    private static $conn = null;
    private static $intentado = false;

    /** @return mysqli|null */
    public static function conexion($base = null)
    {
        if (self::$intentado && $base === null) {
            return self::$conn;
        }
        self::$intentado = true;

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $base = $base ?: (getenv('DB_TEST_NAME') ?: 'neurixbd_test');

        $previo = mysqli_report(MYSQLI_REPORT_OFF);
        $c = @new mysqli($host, $user, $pass);
        if ($c->connect_errno) {
            mysqli_report($previo);
            return self::$conn = null;
        }
        $c->query('CREATE DATABASE IF NOT EXISTS `' . $base . '` DEFAULT CHARACTER SET utf8mb4');
        if (!$c->select_db($base)) {
            mysqli_report($previo);
            return self::$conn = null;
        }
        $c->set_charset('utf8mb4');
        return self::$conn = $c;
    }

    /**
     * Monta el modelo indicado sobre la conexión de pruebas.
     *
     * @param  string $clase       nombre del modelo
     * @param  array  $userdata    lo que devuelve `session->userdata()`
     * @return object
     */
    public static function modelo($clase, mysqli $conn, array $userdata = array())
    {
        require_once APPPATH . 'models/' . $clase . '.php';

        // El constructor del modelo ya usa `$this->db` y `$this->load`, así que
        // el objeto se crea sin construir, se le inyectan las dependencias y
        // recién entonces se ejecuta el constructor.
        $r = new ReflectionClass($clase);
        $m = $r->newInstanceWithoutConstructor();

        $m->db      = new DbEntornoDB($conn, 'tec_');
        $m->session = new DbEntornoSession($userdata);
        $m->load    = new DbEntornoLoader();

        $ctor = $r->getConstructor();
        if ($ctor) {
            $ctor->invoke($m);
        }
        return $m;
    }
}

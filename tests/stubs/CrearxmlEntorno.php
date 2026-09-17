<?php

/**
 * Entorno mínimo para ejecutar Crearxml fuera de CodeIgniter.
 *
 * Crearxml llega a todo por `get_instance()`, así que basta con un objeto que
 * exponga las mismas propiedades. Lo que se estabiliza acá son los datos de
 * entrada; lo que se prueba es el XML que sale.
 */

if (!function_exists('log_message')) {
    function log_message($nivel, $mensaje)
    {
        CrearxmlEntorno::$avisos[] = $nivel . ': ' . $mensaje;
    }
}

class StubResultadoDb
{
    private $filas;

    public function __construct(array $filas = []) { $this->filas = $filas; }
    public function result() { return $this->filas; }
    public function result_array() { return array_map(function ($f) { return (array) $f; }, $this->filas); }
    public function row() { return isset($this->filas[0]) ? $this->filas[0] : null; }
    public function num_rows() { return count($this->filas); }
}

class StubDb
{
    public $filas = [];

    public function dbprefix($tabla = '') { return 'tec_' . $tabla; }
    public function select() { return $this; }
    public function from() { return $this; }
    public function join() { return $this; }
    public function where() { return $this; }
    public function get_where($tabla = '', $donde = null) { return new StubResultadoDb(isset($this->filas[$tabla]) ? $this->filas[$tabla] : []); }
    public function get($tabla = '') { return new StubResultadoDb(isset($this->filas[$tabla]) ? $this->filas[$tabla] : []); }
}

class StubCargador
{
    public function model($m, $alias = null) {}
    public function library($l, $c = null, $n = null) {}
    public function helper($h) {}
}

class StubClientes
{
    public $cliente;
    public function getCustomerByID($id) { return $this->cliente; }
}

class StubHacienda
{
    public $ultimo = 41;
    public function ccsctv($tipo) { return 0; }
    public function ccsctvfec($tipo) { return 0; }
    public function ccsctvcn($tipo = '') { return 0; }
    public function ccsctv_nd($tipo = '') { return 0; }
    public function ccsctv_rep($tipo = '') { return 0; }
    public function ultimo_consecutivo($tipo, $consecutivo) { return $this->ultimo; }
}

class StubProveedores
{
    public $proveedor;
    public function getSupplierByID($id) { return $this->proveedor; }
}

class StubSitio
{
    public $producto;
    public function getProductByID($id) { return $this->producto; }
}

class StubInstanciaCI
{
    public $Settings;
    public $db;
    public $load;
    public $customers_model;
    public $hacienda_model;
    public $Suppliers_model;
    public $site;
}

if (!function_exists('get_instance')) {
    function get_instance() { return CrearxmlEntorno::$ci; }
}

class CrearxmlEntorno
{
    /** @var StubInstanciaCI */
    public static $ci;

    /** @var string[] mensajes que Crearxml mandó a log_message() */
    public static $avisos = [];

    /** Deja el entorno listo y devuelve una instancia de Crearxml. */
    public static function preparar(array $ajustes = [])
    {
        self::$avisos = [];
        $ci = new StubInstanciaCI();
        $ci->db              = new StubDb();
        $ci->load            = new StubCargador();
        $ci->site            = new StubSitio();
        $ci->customers_model = new StubClientes();
        $ci->hacienda_model  = new StubHacienda();
        $ci->Suppliers_model = new StubProveedores();
        $ci->Settings        = (object) array_merge(self::ajustes(), $ajustes);

        $ci->site->producto = (object) [
            'id' => 1, 'code' => 'P1', 'name' => 'Cafe molido', 'cabys' => '1101010100000',
            'unit_of_measurement' => 'Unid', 'tax' => 13, 'type' => 'standard',
            'tax_method' => '0', 'id_tax' => 8,
        ];
        $ci->db->filas['sale_items'] = [(object) self::item()];
        $ci->db->filas['impuestos'] = [(object) ['id_impuesto' => 8, 'codigo_impuesto' => '01', 'codigo_tarifa' => '08']];

        self::$ci = $ci;

        require_once dirname(__DIR__, 2) . '/app/libraries/Crearxml.php';

        return new Crearxml();
    }

    /** Emisor completo y bien configurado. */
    public static function ajustes()
    {
        return [
            'cedula_emisor'             => '3101123456',
            'tipo_doc_emisor'           => '02',
            'nombre_emisor'             => 'Pulperia La Esquina SA',
            'nombre_comercial'          => 'La Esquina',
            'email_emisor'              => 'ventas@ejemplo.cr',
            'telefono_emisor'           => '22220000',
            'cod_telefono_emisor'       => '506',
            'fax_emisor'                => '22220001',
            'cod_provincia'             => '1',
            'cod_canton'                => '01',
            'cod_distrito'              => '01',
            'cod_barrio'                => '01',
            'otras_senas'               => 'Frente al parque',
            'currency_prefix'           => 'CRC',
            'value_changue'             => '1.00000',
            'casa_matriz'               => '001',
            'terminal_pos'              => '00001',
            'cedula_proveedor_sistemas' => '',
            'decimals'                  => 2,
            'enable_fractions'          => '0',
        ];
    }

    public static function item()
    {
        return [
            'id' => 1, 'sale_id' => 7, 'product_id' => 1, 'product_code' => 'P1',
            'product_name' => 'Cafe molido', 'quantity' => 2, 'unit_price' => 1130,
            'real_unit_price' => 1000, 'net_unit_price' => 1000, 'subtotal' => 2260,
            'tax' => '13%', 'tax_rate_id' => 1, 'item_tax' => 260, 'discount' => '0',
            'item_discount' => 0, 'cabys' => '1101010100000', 'unit_of_measurement' => 'Unid',
            'esta_fraccionado' => 0, 'cost' => 700, 'serial_no' => '', 'option_id' => null,
            'order_discount_id' => null, 'descripcion_descuento' => '',
            'PrecioUnitario' => 1000, 'TipoDocumentoE' => '',
        ];
    }

    public static function venta()
    {
        return [
            'id' => 7, 'customer_id' => 1, 'id_actividad' => '620200', 'status' => 'paid',
            'id_shipping_method' => null, 'date' => '2026-08-23 10:15:00',
            'total' => 2000, 'product_discount' => 0, 'order_discount' => 0,
            'order_discount_id' => null, 'total_tax' => 260, 'grand_total' => 2260,
            'paid' => 2260, 'total_items' => 1, 'order_tax' => 0, 'shipping' => 0,
            'reference_no' => 'POS-7', 'TipoDocumentoE' => '',
        ];
    }

    /** Cliente identificado: obliga al generador a emitir una factura, no un tiquete. */
    public static function cliente()
    {
        return (object) [
            'id' => 2, 'name' => 'Comercial Los Robles SA', 'business_name' => 'Los Robles',
            'cf1' => '02', 'cf2' => '3101987654', 'codigo_actividad' => '471100',
            'phone' => '22223333', 'email' => 'compras@robles.cr',
            'pre_id_number' => '02', 'id_number_proveedor' => '3101987654',
        ];
    }

    /** Cliente con la ubicacion completa que exige UbicacionType. */
    public static function clienteConUbicacion()
    {
        return (object) array_merge((array) self::cliente(), [
            'codigo_provincia' => '1',
            'codigo_canton'    => '01',
            'codigo_distrito'  => '01',
            'codigo_barrio'    => '01',
            'otras_senas'      => 'Del parque 200 metros al sur, local 3',
            'cod_telefono'     => '506',
        ]);
    }

    /**
     * Proveedor no contribuyente que vende un bien usado: sin actividad
     * inscrita, sin telefono y sin correo, que es el caso real del codigo 06.
     */
    public static function proveedorNoContribuyente()
    {
        return (object) [
            'id' => 5, 'name' => 'Maria Rodriguez Vargas', 'company' => '',
            'email' => '', 'phone' => '', 'cf1' => '06', 'cf2' => 'NC000123',
            'direccion' => '', 'codigo_provincia' => '', 'codigo_canton' => '',
            'codigo_distrito' => '', 'codigo_barrio' => '', 'actividad_economica' => '',
        ];
    }

    /** Extranjero no domiciliado: sin cedula de Costa Rica y con direccion afuera. */
    public static function clienteExtranjero()
    {
        return (object) array_merge((array) self::clienteConUbicacion(), [
            'name' => 'John Miller',
            'business_name' => '',
            'cf1' => '05',
            'cf2' => 'AB123456789',
            'id_number_proveedor' => 'AB123456789',
            'pre_id_number' => '05',
            'otras_senas_extranjero' => '1200 Brickell Ave, Miami FL',
            'codigo_actividad' => '',
        ]);
    }

    /** Proveedor inscrito y completo, para el camino normal de la FEC. */
    public static function proveedorInscrito()
    {
        return (object) [
            'id' => 6, 'name' => 'Distribuidora Central SA', 'company' => 'DisCentral',
            'email' => 'ventas@discentral.cr', 'phone' => '22224444', 'cf1' => '02',
            'cf2' => '3101555666', 'direccion' => 'Costado norte del mercado',
            'codigo_provincia' => '1', 'codigo_canton' => '01', 'codigo_distrito' => '01',
            'codigo_barrio' => '01', 'actividad_economica' => '463001',
        ];
    }

    /** Nota de credito contra un tiquete ya emitido. */
    public static function notaCredito($customer_id)
    {
        return array_merge(self::venta(), [
            'customer_id' => $customer_id, 'type_nc' => '1', 'hold_ref' => 'Devolucion de mercaderia',
        ]);
    }

    /** Nota de debito contra un comprobante ya emitido. */
    public static function notaDebito($customer_id)
    {
        return array_merge(self::venta(), [
            'customer_id' => $customer_id, 'type_nd' => '01', 'motivo_nd' => '02',
            'hold_ref' => 'Cobro adicional de flete',
        ]);
    }

    /** Comprobante original al que apunta la nota. */
    public static function referencia()
    {
        return (object) [
            'clave' => str_repeat('5', 50),
            'fecha_emision' => '2026-08-23T10:15:00',
            'tipo_doc' => '01',
        ];
    }

    /** Linea de una compra: getFEC lee el tipo del item, no del producto. */
    public static function itemCompra()
    {
        return array_merge(self::item(), ['type' => 'standard']);
    }

    /** Pago con el que se emite un recibo electronico de pago. */
    public static function pago()
    {
        return ['id' => 3, 'sale_id' => 7, 'amount' => 2260, 'paid_by' => 'cash',
                'date' => '2026-08-24 09:00:00', 'reference' => 'REC-3'];
    }

    /** Los pagos tal como se los pasa el POS al generador. */
    public static function pagos()
    {
        return ['amount' => 2260, 'paid_by' => 'cash'];
    }

    /** Factura a credito que el recibo de pago viene a cobrar. */
    public static function referenciaREP()
    {
        return (object) [
            'clave' => str_repeat('7', 50),
            'fecha_emision' => '2026-08-23T10:15:00',
            'tipo_doc' => '01',
        ];
    }

    /** Compra registrada contra un proveedor. */
    public static function compra($proveedor_id)
    {
        return array_merge(self::venta(), [
            'customer_id' => $proveedor_id, 'paymentmethod' => '30',
        ]);
    }
}

<?php
/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

/**
 * Banco de datos controlado para las pruebas de informes.
 *
 * Se construye a mano y con cifras redondas para poder afirmar el resultado
 * exacto. Probar contra la base real diría si la consulta corre, no si la cifra
 * es la correcta, que es la pregunta que importa acá.
 *
 * El escenario cabe en la cabeza a propósito:
 *
 * | Venta | Fecha      | Estado    | Líneas                          | Total   |
 * |-------|------------|-----------|---------------------------------|---------|
 * | 1     | 2026-02-10 | aceptado  | 2×1.000 al 13 % + 1×500 exento  | 2.760   |
 * | 2     | 2026-02-15 | aceptado  | 1×2.000 al 13 %, dto. 200       | 2.034   |
 * | 3     | 2026-02-20 | rechazado | 1×1.000 al 13 %                 | 1.130   |
 * | 4     | 2026-02-25 | aceptado  | 1×3.000 al 13 %  → **anulada**  | 3.390   |
 * | 5     | 2026-04-05 | aceptado  | 1×1.000 al 13 %                 | 1.130   |
 *
 * Más una nota de crédito de 3.390 sobre la 4 y una de débito de 226 sobre la 2.
 */
class ReporteFixture
{
    /** @var mysqli */
    private $c;

    public function __construct(mysqli $c)
    {
        $this->c = $c;
    }

    /** Crea el esquema mínimo desde cero. */
    public function crear()
    {
        $this->borrar();

        $q = function ($sql) {
            if (!$this->c->query($sql)) {
                throw new RuntimeException('fixture: ' . $this->c->error . "\n" . $sql);
            }
        };

        $q("CREATE TABLE tec_sales (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              date DATETIME NOT NULL, customer_id INT NULL, customer_name VARCHAR(150) NULL,
              register_id INT NULL, created_by INT NULL, store_id INT NULL,
              status VARCHAR(10) NULL, payment_status VARCHAR(10) NULL,
              paid DECIMAL(25,4) NULL DEFAULT 0, total DECIMAL(25,4) NOT NULL DEFAULT 0,
              total_tax DECIMAL(25,4) NULL DEFAULT 0, total_discount DECIMAL(25,4) NULL DEFAULT 0,
              grand_total DECIMAL(25,4) NOT NULL DEFAULT 0,
              tipo_doc VARCHAR(2) NULL, consecutivo VARCHAR(20) NULL, clave VARCHAR(50) NULL,
              condicion TINYINT(1) NULL, MontoExoneracion DECIMAL(25,5) NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_sale_items (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              sale_id INT NOT NULL, product_id INT NOT NULL,
              product_code VARCHAR(60) NULL, product_name VARCHAR(120) NULL,
              quantity DECIMAL(25,4) NOT NULL, unit_price DECIMAL(25,4) NOT NULL DEFAULT 0,
              net_unit_price DECIMAL(25,4) NULL, subtotal DECIMAL(25,4) NOT NULL,
              tax VARCHAR(10) NULL, item_tax DECIMAL(25,4) NULL DEFAULT 0,
              item_discount DECIMAL(25,4) NULL DEFAULT 0, cost DECIMAL(25,4) NULL DEFAULT 0,
              cabys VARCHAR(13) NULL, unit_of_measurement VARCHAR(50) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_customers (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(150) NULL, cf1 VARCHAR(2) NULL, cf2 VARCHAR(30) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_users (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              first_name VARCHAR(60) NULL, last_name VARCHAR(60) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_stores (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_products (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(120) NULL, code VARCHAR(60) NULL, category_id INT NULL,
              cost DECIMAL(25,4) NULL DEFAULT 0, alert_quantity DECIMAL(25,4) NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_categories (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_payments (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              sale_id INT NOT NULL, date DATETIME NULL, amount DECIMAL(25,4) NOT NULL,
              paid_by VARCHAR(30) NULL, reference VARCHAR(100) NULL,
              store_id INT NULL, register_id INT NULL, created_by INT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_hacienda_tiketes (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              sale_id INT NOT NULL, tipo_doc VARCHAR(2) NULL, consecutivo VARCHAR(20) NULL,
              clave VARCHAR(50) NULL, fecha_emision DATETIME NULL,
              estatus_hacienda VARCHAR(20) NULL, xml LONGTEXT NULL, xml_sign LONGTEXT NULL,
              xml_hacienda LONGTEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_sale_anulaciones (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              sale_id INT NOT NULL, cn_id INT NULL, tipo VARCHAR(10) NULL,
              codigo_ref VARCHAR(2) NULL, motivo VARCHAR(255) NULL,
              devuelve_dinero TINYINT(1) NULL DEFAULT 0, monto_devuelto DECIMAL(25,4) NULL DEFAULT 0,
              medio_devolucion VARCHAR(30) NULL, store_id INT NULL, created_by INT NULL,
              created_at DATETIME NULL, ip VARCHAR(45) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_note_credits (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              sale_id INT NULL, date DATETIME NOT NULL, customer_id INT NULL,
              customer_name VARCHAR(255) NULL, created_by INT NULL, store_id INT NULL,
              total DECIMAL(25,4) NULL DEFAULT 0, total_tax DECIMAL(25,4) NULL DEFAULT 0,
              total_discount DECIMAL(25,4) NULL DEFAULT 0, grand_total DECIMAL(25,4) NOT NULL DEFAULT 0,
              motivo VARCHAR(255) NULL, consecutivo VARCHAR(20) NULL, clave VARCHAR(50) NULL,
              estatus_hacienda VARCHAR(20) NULL, type_nc VARCHAR(2) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_note_credits_items (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              cn_id INT NOT NULL, product_id INT NULL, product_code VARCHAR(60) NULL,
              product_name VARCHAR(120) NULL, quantity DECIMAL(25,4) NULL,
              net_unit_price DECIMAL(25,4) NULL, subtotal DECIMAL(25,4) NULL,
              item_tax DECIMAL(25,4) NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_note_debits (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              sale_id INT NULL, date DATETIME NOT NULL, customer_id INT NULL,
              customer_name VARCHAR(255) NULL, created_by INT NULL, store_id INT NULL,
              total DECIMAL(25,4) NULL DEFAULT 0, total_tax DECIMAL(25,4) NULL DEFAULT 0,
              total_discount DECIMAL(25,4) NULL DEFAULT 0, grand_total DECIMAL(25,4) NOT NULL DEFAULT 0,
              motivo_nd VARCHAR(2) NULL, type_nd VARCHAR(2) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_hacienda_nd (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              nd_id INT NULL, consecutivo VARCHAR(20) NULL, clave VARCHAR(50) NULL,
              estatus_hacienda VARCHAR(20) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_product_store_qty (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              product_id INT NULL, store_id INT NULL, quantity DECIMAL(25,4) NULL DEFAULT 0,
              price DECIMAL(25,4) NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_mov_inventario (
              id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              tipo_mov TINYINT(1) NULL, descripcion_mov VARCHAR(255) NULL,
              quantity_mov DECIMAL(25,4) NULL, id_product INT NULL,
              fecha_mov DATETIME NULL, store_id INT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_suppliers (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(150) NULL, cf1 VARCHAR(2) NULL, cf2 VARCHAR(30) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $q("CREATE TABLE tec_registers (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              store_id INT NULL, user_id INT NULL, date DATETIME NULL, closed_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        return $this;
    }

    /** Borra el esquema. Se llama al empezar, no al terminar: si una prueba
     *  falla, los datos quedan para poder mirarlos. */
    public function borrar()
    {
        $tablas = array('sales', 'sale_items', 'customers', 'users', 'stores', 'products',
                        'categories', 'payments', 'hacienda_tiketes', 'sale_anulaciones',
                        'note_credits', 'note_credits_items', 'note_debits', 'hacienda_nd',
                        'product_store_qty', 'mov_inventario', 'suppliers', 'registers');
        $this->c->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tablas as $t) {
            $this->c->query('DROP TABLE IF EXISTS tec_' . $t);
        }
        $this->c->query('SET FOREIGN_KEY_CHECKS = 1');
        return $this;
    }

    /** Carga el escenario descrito en la cabecera de la clase. */
    public function poblar()
    {
        $q = function ($sql) {
            if (!$this->c->query($sql)) {
                throw new RuntimeException('fixture: ' . $this->c->error . "\n" . $sql);
            }
        };

        $q("INSERT INTO tec_stores (id,name) VALUES (1,'Sucursal Central'),(2,'Sucursal Sur')");
        $q("INSERT INTO tec_users (id,first_name,last_name) VALUES (1,'Ana','Cajera'),(2,'Beto','Supervisor')");
        $q("INSERT INTO tec_categories (id,name) VALUES (1,'Abarrotes'),(2,'Ferretería')");
        $q("INSERT INTO tec_customers (id,name,cf1,cf2) VALUES
              (1,'Cliente Contado','01','000000000'),
              (2,'Comercial Uno S.A.','02','3101123456'),
              (3,'Sin Cédula','01','')");
        $q("INSERT INTO tec_products (id,name,code,category_id,cost) VALUES
              (1,'Arroz 1kg','ARZ1',1,600),
              (2,'Martillo','MAR1',2,1500),
              (3,'Pan','PAN1',1,0)");

        // ── Venta 1: dos tarifas, aceptada, en efectivo ──
        $q("INSERT INTO tec_sales (id,date,customer_id,customer_name,register_id,created_by,store_id,
              status,payment_status,paid,total,total_tax,total_discount,grand_total,tipo_doc,consecutivo,clave,condicion)
            VALUES (1,'2026-02-10 09:00:00',2,'Comercial Uno S.A.',1,1,1,'paid','paid',
                    2760,2760,260,0,2760,'01','00100001010000000001','" . str_repeat('1', 50) . "',1)");
        $q("INSERT INTO tec_sale_items (sale_id,product_id,product_code,product_name,quantity,
              unit_price,net_unit_price,subtotal,tax,item_tax,item_discount,cost,cabys)
            VALUES (1,1,'ARZ1','Arroz 1kg',2,1130,1000,2260,'13%',260,0,600,'2312300000100'),
                   (1,3,'PAN1','Pan',1,500,500,500,'0',0,0,0,'2312300000200')");
        $q("INSERT INTO tec_payments (sale_id,date,amount,paid_by,store_id,created_by)
            VALUES (1,'2026-02-10 09:00:00',2760,'cash',1,1)");
        $q("INSERT INTO tec_hacienda_tiketes (sale_id,tipo_doc,consecutivo,clave,fecha_emision,estatus_hacienda,xml_sign)
            VALUES (1,'01','00100001010000000001','" . str_repeat('1', 50) . "','2026-02-10 09:00:00','aceptado','<x/>')");

        // ── Venta 2: con descuento, aceptada, SINPE ──
        $q("INSERT INTO tec_sales (id,date,customer_id,customer_name,register_id,created_by,store_id,
              status,payment_status,paid,total,total_tax,total_discount,grand_total,tipo_doc,consecutivo,clave,condicion)
            VALUES (2,'2026-02-15 14:30:00',2,'Comercial Uno S.A.',1,1,1,'paid','paid',
                    2034,2034,234,200,2034,'01','00100001010000000002','" . str_repeat('2', 50) . "',1)");
        $q("INSERT INTO tec_sale_items (sale_id,product_id,product_code,product_name,quantity,
              unit_price,net_unit_price,subtotal,tax,item_tax,item_discount,cost,cabys)
            VALUES (2,2,'MAR1','Martillo',1,2260,1800,2034,'13%',234,200,1500,'2312300000300')");
        $q("INSERT INTO tec_payments (sale_id,date,amount,paid_by,store_id,created_by)
            VALUES (2,'2026-02-15 14:30:00',2034,'sinpe',1,1)");
        $q("INSERT INTO tec_hacienda_tiketes (sale_id,tipo_doc,consecutivo,clave,fecha_emision,estatus_hacienda,xml_sign)
            VALUES (2,'01','00100001010000000002','" . str_repeat('2', 50) . "','2026-02-15 14:30:00','aceptado','<x/>')");

        // ── Venta 3: RECHAZADA, nunca reemitida ──
        $q("INSERT INTO tec_sales (id,date,customer_id,customer_name,register_id,created_by,store_id,
              status,payment_status,paid,total,total_tax,total_discount,grand_total,tipo_doc,consecutivo,clave,condicion)
            VALUES (3,'2026-02-20 11:00:00',1,'Cliente Contado',1,2,1,'paid','paid',
                    1130,1130,130,0,1130,'04','00100001040000000003','" . str_repeat('3', 50) . "',1)");
        $q("INSERT INTO tec_sale_items (sale_id,product_id,product_code,product_name,quantity,
              unit_price,net_unit_price,subtotal,tax,item_tax,item_discount,cost,cabys)
            VALUES (3,1,'ARZ1','Arroz 1kg',1,1130,1000,1130,'13%',130,0,600,'2312300000100')");
        $q("INSERT INTO tec_payments (sale_id,date,amount,paid_by,store_id,created_by)
            VALUES (3,'2026-02-20 11:00:00',1130,'cash',1,2)");
        $q("INSERT INTO tec_hacienda_tiketes (sale_id,tipo_doc,consecutivo,clave,fecha_emision,estatus_hacienda,xml_sign)
            VALUES (3,'04','00100001040000000003','" . str_repeat('3', 50) . "','2026-02-20 11:00:00','rechazado',NULL)");

        // ── Venta 4: aceptada y despues ANULADA con nota de credito ──
        $q("INSERT INTO tec_sales (id,date,customer_id,customer_name,register_id,created_by,store_id,
              status,payment_status,paid,total,total_tax,total_discount,grand_total,tipo_doc,consecutivo,clave,condicion)
            VALUES (4,'2026-02-25 16:00:00',2,'Comercial Uno S.A.',1,1,1,'paid','paid',
                    3390,3390,390,0,3390,'01','00100001010000000004','" . str_repeat('4', 50) . "',1)");
        $q("INSERT INTO tec_sale_items (sale_id,product_id,product_code,product_name,quantity,
              unit_price,net_unit_price,subtotal,tax,item_tax,item_discount,cost,cabys)
            VALUES (4,2,'MAR1','Martillo',1,3390,3000,3390,'13%',390,0,1500,'2312300000300')");
        $q("INSERT INTO tec_payments (sale_id,date,amount,paid_by,store_id,created_by)
            VALUES (4,'2026-02-25 16:00:00',3390,'card',1,1)");
        $q("INSERT INTO tec_hacienda_tiketes (sale_id,tipo_doc,consecutivo,clave,fecha_emision,estatus_hacienda,xml_sign)
            VALUES (4,'01','00100001010000000004','" . str_repeat('4', 50) . "','2026-02-25 16:00:00','aceptado','<x/>')");
        $q("INSERT INTO tec_note_credits (id,sale_id,date,customer_id,customer_name,created_by,store_id,
              total,total_tax,total_discount,grand_total,motivo,consecutivo,estatus_hacienda,type_nc)
            VALUES (1,4,'2026-02-26 10:00:00',2,'Comercial Uno S.A.',1,1,
                    3000,390,0,3390,'Error en el pedido','00100001030000000001','aceptado','01')");
        $q("INSERT INTO tec_note_credits_items (cn_id,product_id,product_code,product_name,quantity,
              net_unit_price,subtotal,item_tax)
            VALUES (1,2,'MAR1','Martillo',1,3000,3390,390)");
        $q("INSERT INTO tec_sale_anulaciones (sale_id,cn_id,tipo,codigo_ref,motivo,devuelve_dinero,
              monto_devuelto,medio_devolucion,store_id,created_by,created_at,ip)
            VALUES (4,1,'fiscal','01','Error en el pedido',1,3390,'efectivo',1,1,'2026-02-26 10:00:00','127.0.0.1')");

        // ── Venta 5: abril, otra sucursal, a credito (cobrada a medias) ──
        $q("INSERT INTO tec_sales (id,date,customer_id,customer_name,register_id,created_by,store_id,
              status,payment_status,paid,total,total_tax,total_discount,grand_total,tipo_doc,consecutivo,clave,condicion)
            VALUES (5,'2026-04-05 08:15:00',3,'Sin Cédula',2,2,2,'due','partial',
                    500,1130,130,0,1130,'04','00100002040000000005','" . str_repeat('5', 50) . "',2)");
        $q("INSERT INTO tec_sale_items (sale_id,product_id,product_code,product_name,quantity,
              unit_price,net_unit_price,subtotal,tax,item_tax,item_discount,cost,cabys)
            VALUES (5,1,'ARZ1','Arroz 1kg',1,1130,1000,1130,'13%',130,0,600,NULL)");
        $q("INSERT INTO tec_payments (sale_id,date,amount,paid_by,store_id,created_by)
            VALUES (5,'2026-04-05 08:15:00',500,'cash',2,2)");
        $q("INSERT INTO tec_hacienda_tiketes (sale_id,tipo_doc,consecutivo,clave,fecha_emision,estatus_hacienda,xml_sign)
            VALUES (5,'04','00100002040000000005','" . str_repeat('5', 50) . "','2026-04-05 08:15:00','aceptado','<x/>')");

        // ── Nota de debito sobre la venta 2 ──
        $q("INSERT INTO tec_note_debits (id,sale_id,date,customer_id,customer_name,created_by,store_id,
              total,total_tax,total_discount,grand_total,motivo_nd,type_nd)
            VALUES (1,2,'2026-02-28 09:00:00',2,'Comercial Uno S.A.',1,1,200,26,0,226,'04','02')");
        $q("INSERT INTO tec_hacienda_nd (nd_id,consecutivo,clave,estatus_hacienda)
            VALUES (1,'00100001020000000001','" . str_repeat('6', 50) . "','aceptado')");

        $q("INSERT INTO tec_product_store_qty (product_id,store_id,quantity,price)
            VALUES (1,1,50,1130),(2,1,-3,3390),(3,1,10,500)");

        return $this;
    }

    /**
     * Cifras esperadas del escenario, calculadas a mano.
     *
     * Están acá y no dentro de cada prueba para que se lean juntas: si una
     * cambia sin que cambie el escenario, es que alguien ajustó la expectativa
     * para que pasara la prueba en vez de arreglar el cálculo.
     */
    public static function esperado()
    {
        return array(
            // Febrero, ámbito interno (sin la 4, que está anulada): ventas 1, 2 y 3.
            'febrero_interno' => array(
                'documentos' => 3,
                'base'       => 2000.00 + 500.00 + 1800.00 + 1000.00,   // 5300
                'impuesto'   => 260.00 + 0.00 + 234.00 + 130.00,         // 624
                'total'      => 2760.00 + 2034.00 + 1130.00,             // 5924
                'descuento'  => 200.00,
                'costo'      => (600 * 2) + 0 + (1500 * 1) + (600 * 1),  // 3300
            ),
            // Febrero, ámbito fiscal (solo aceptados, la anulada incluida): 1, 2 y 4.
            'febrero_fiscal' => array(
                'documentos' => 3,
                'base'       => 2000.00 + 500.00 + 1800.00 + 3000.00,   // 7300
                'impuesto'   => 260.00 + 234.00 + 390.00,                // 884
                'total'      => 2760.00 + 2034.00 + 3390.00,             // 8184
            ),
            // Febrero, todo lo emitido: las cuatro ventas.
            'febrero_emitido' => array(
                'documentos' => 4,
                'total'      => 2760.00 + 2034.00 + 1130.00 + 3390.00,   // 9314
            ),
            'notas' => array('nc_cantidad' => 1, 'nc_total' => 3390.00, 'nc_impuesto' => 390.00,
                             'nd_cantidad' => 1, 'nd_total' => 226.00,  'nd_impuesto' => 26.00),
        );
    }
}

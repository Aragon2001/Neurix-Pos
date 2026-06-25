-- =====================================================================
-- BASE DE DATOS DE EJEMPLO — Sistema POS / Facturación Electrónica CR
-- =====================================================================
-- Generada para poder instalar el proyecto en Laragon y arrancarlo por
-- primera vez con datos de prueba (usuario admin, productos, clientes,
-- ajustes básicos de Hacienda en ambiente de pruebas).
--
-- IMPORTANTE — de dónde viene este esquema:
-- El proyecto NO tenía ningún archivo .sql con el esquema completo
-- (los respaldos de 2018-2019 que sí lo tenían se eliminaron a pedido
-- explícito en la limpieza anterior). Este esquema se reconstruyó
-- leyendo:
--   1) app/core/MY_Controller.php (sistema de auto-migración versionPOS
--      1->43, con CREATE TABLE / ADD COLUMN reales)
--   2) app/controllers/*.php y app/models/*.php (arrays de insert/update)
--   3) app/config/ion_auth.php / Auth_model.php (login)
-- Las tablas marcadas "[núcleo confirmado]" tienen sus columnas
-- verificadas línea por línea contra el código. Las tablas marcadas
-- "[estructura estándar]" son necesarias para que el sistema no truene,
-- pero columnas secundarias se completaron con la estructura típica de
-- este tipo de POS (CodeIgniter 3 / familia SimplePOS) cuando el código
-- no especificaba el nombre exacto. Si el sistema marca un error de
-- "columna desconocida" en producción, lo más probable es que sea en una
-- de estas tablas secundarias.
--
-- Prefijo de tablas: tec_  (igual que app/config/database.php)
-- Base de datos esperada: posv (igual que app/config/database.php)
-- versionPOS se deja en 43 (la última que usa el código) para que el
-- sistema NO intente volver a ejecutar todas las migraciones en el
-- primer request.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE IF NOT EXISTS `posv` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `posv`;

-- =====================================================================
-- SECCIÓN A — AUTENTICACIÓN (Ion Auth)  [núcleo confirmado]
-- Fuente: app/config/ion_auth.php, app/models/Auth_model.php
-- =====================================================================

DROP TABLE IF EXISTS `tec_groups`;
CREATE TABLE `tec_groups` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(20) NOT NULL,
  `description` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_users`;
CREATE TABLE `tec_users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `username` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `salt` VARCHAR(40) DEFAULT NULL,         -- no usado: store_salt=FALSE en ion_auth.php
  `activation_code` VARCHAR(40) DEFAULT NULL,
  `forgotten_password_code` VARCHAR(40) DEFAULT NULL,
  `forgotten_password_time` INT(11) DEFAULT NULL,
  `remember_code` VARCHAR(40) DEFAULT NULL,
  `created_on` INT(11) NOT NULL,
  `last_login` INT(11) DEFAULT NULL,
  `active` TINYINT(1) DEFAULT 1,
  `first_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) DEFAULT NULL,
  `company` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `store_id` INT(11) DEFAULT 1,
  `group_id` INT(11) DEFAULT NULL,
  `auth_open` TINYINT(1) NOT NULL DEFAULT 0,   -- add_column MY_Controller.php:108
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_users_groups`;
CREATE TABLE `tec_users_groups` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `group_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_login_attempts`;
CREATE TABLE `tec_login_attempts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `identity` VARCHAR(100) NOT NULL,
  `time` INT(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN B — CONFIGURACIÓN GLOBAL  [núcleo confirmado]
-- Fuente: app/controllers/Settings.php (líneas 60-159),
-- app/core/MY_Controller.php (versiones 1-43)
-- =====================================================================

DROP TABLE IF EXISTS `tec_settings`;
CREATE TABLE `tec_settings` (
  `setting_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_name` VARCHAR(100) NOT NULL DEFAULT 'SimplePOS',
  `language` VARCHAR(20) NOT NULL DEFAULT 'spanish',
  `selected_language` VARCHAR(20) DEFAULT NULL, -- usado en runtime, no persistido realmente
  `tel` VARCHAR(30) DEFAULT NULL,
  `currency_prefix` VARCHAR(3) NOT NULL DEFAULT 'CRC',
  `default_tax_rate` INT(11) DEFAULT NULL,
  `default_discount` DECIMAL(15,2) DEFAULT 0.00,
  `rows_per_page` INT(11) NOT NULL DEFAULT 25,
  `bsty` VARCHAR(20) DEFAULT 'grid',
  `pro_limit` INT(11) DEFAULT 12,
  `display_kb` TINYINT(1) DEFAULT 0,
  `default_category` INT(11) DEFAULT NULL,
  `default_customer` INT(11) DEFAULT 1,
  `default_actividad` INT(11) DEFAULT NULL,
  `barcode_symbology` VARCHAR(20) DEFAULT 'CODE128',
  `dateformat` VARCHAR(20) NOT NULL DEFAULT 'd-m-Y',
  `timeformat` VARCHAR(20) NOT NULL DEFAULT 'h:i A',
  `header` TEXT,
  `footer` TEXT,
  `default_email` VARCHAR(100) DEFAULT NULL,
  `protocol` VARCHAR(20) DEFAULT NULL,
  `smtp_host` VARCHAR(100) DEFAULT NULL,
  `smtp_user` VARCHAR(100) DEFAULT NULL,
  `smtp_pass` VARCHAR(100) DEFAULT NULL,
  `smtp_port` VARCHAR(10) DEFAULT NULL,
  `smtp_crypto` VARCHAR(10) DEFAULT NULL,
  `pin_code` VARCHAR(100) DEFAULT NULL,   -- MY_Controller.php:26 lo vuelve a hashear con md5 en runtime
  `focus_add_item` TINYINT(1) DEFAULT 1,
  `edit_last_product` TINYINT(1) DEFAULT 0,
  `add_customer` TINYINT(1) DEFAULT 1,
  `toggle_category_slider` TINYINT(1) DEFAULT 1,
  `cancel_sale` TINYINT(1) DEFAULT 1,
  `suspend_sale` TINYINT(1) DEFAULT 1,
  `print_order` TINYINT(1) DEFAULT 0,
  `print_bill` TINYINT(1) DEFAULT 1,
  `finalize_sale` TINYINT(1) DEFAULT 1,
  `today_sale` TINYINT(1) DEFAULT 1,
  `open_hold_bills` TINYINT(1) DEFAULT 1,
  `close_register` TINYINT(1) DEFAULT 1,
  `rounding` TINYINT(1) DEFAULT 0,
  `item_addition` VARCHAR(20) DEFAULT 'add',
  `stripe` TINYINT(1) DEFAULT 0,
  `stripe_secret_key` VARCHAR(150) DEFAULT NULL,
  `stripe_publishable_key` VARCHAR(150) DEFAULT NULL,
  `theme` VARCHAR(50) NOT NULL DEFAULT 'default',
  `theme_style` VARCHAR(20) DEFAULT 'black',
  `after_sale_page` VARCHAR(20) DEFAULT NULL,
  `multi_store` TINYINT(1) DEFAULT 0,
  `overselling` TINYINT(1) DEFAULT 1,
  `decimals` INT(2) DEFAULT 2,
  `decimals_sep` VARCHAR(1) DEFAULT '.',
  `thousands_sep` VARCHAR(1) DEFAULT ',',
  `sac` TINYINT(1) DEFAULT 0,
  `qty_decimals` INT(2) DEFAULT 2,
  `display_symbol` TINYINT(1) DEFAULT 1,
  `symbol` VARCHAR(5) DEFAULT '₡',
  `printer` VARCHAR(100) DEFAULT NULL,
  `order_printers` TEXT,
  `auto_print` TINYINT(1) DEFAULT 0,
  `remote_printing` TINYINT(1) DEFAULT 0,
  `local_printers` TEXT,
  `rtl` TINYINT(1) DEFAULT 0,
  `print_img` TINYINT(1) DEFAULT 0,
  `nombrecompartido` VARCHAR(100) DEFAULT NULL,
  `ip_printer` VARCHAR(45) DEFAULT NULL,
  `sensibility_search` INT(2) DEFAULT 2,
  `enable_credit` TINYINT(1) DEFAULT 0,
  `prt_invo_after` TINYINT(1) DEFAULT 0,
  `logo` VARCHAR(150) DEFAULT NULL,
  `version` VARCHAR(20) DEFAULT '1.0',
  `update` TINYINT(1) DEFAULT 0,
  -- --- columnas agregadas por el sistema de migración versionPOS 1-43 ---
  `versionPOS` TINYINT(1) NOT NULL DEFAULT 43,
  `enable_layaway` TINYINT(1) NOT NULL DEFAULT 0,
  `enable_show_tax` VARCHAR(10) NOT NULL DEFAULT 'Impuesto',
  `enable_quote` TINYINT(1) NOT NULL DEFAULT 0,
  `enable_auth_open` TINYINT(1) NOT NULL DEFAULT 1,
  `enable_detail_register` TINYINT(1) NOT NULL DEFAULT 1,
  `enable_detail_caschier` TINYINT(1) NOT NULL DEFAULT 1,
  `enable_fastedition` TINYINT(1) NOT NULL DEFAULT 0,
  `footer_apartado` VARCHAR(180) NOT NULL DEFAULT '',
  `block_hacienda` TINYINT(1) NOT NULL DEFAULT 1,
  `enable_fractions` TINYINT(1) NOT NULL DEFAULT 0,
  `quantity_suggest` INT(11) NOT NULL DEFAULT 10,
  `demo` TINYINT(1) NOT NULL DEFAULT 0,
  `fe` TINYINT(1) NOT NULL DEFAULT 1,
  `enable_btn_pay` TINYINT(1) NOT NULL DEFAULT 1,
  `enable_parquimetro` TINYINT(1) NOT NULL DEFAULT 0,
  `propina_enable` TINYINT(1) NOT NULL DEFAULT 0,
  `propina_rate` TINYINT(2) NOT NULL DEFAULT 10,
  `enablebtn_retiro` TINYINT(1) NOT NULL DEFAULT 0,
  `enablebtn_deposito` INT(10) NOT NULL DEFAULT 0,
  `is_shipping` TINYINT(1) DEFAULT 0,
  `multiprice_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `diskdrive_code` VARCHAR(100) DEFAULT NULL,
  `enabled_tax_split` TINYINT(1) NOT NULL DEFAULT 0,
  `enabled_massive_mail` TINYINT(1) NOT NULL DEFAULT 0,
  `mail_client_host` VARCHAR(120) DEFAULT NULL,
  `mail_client_port` VARCHAR(120) DEFAULT NULL,
  `mail_client_tipo` VARCHAR(120) DEFAULT NULL,
  `mail_client_user` VARCHAR(120) DEFAULT NULL,
  `mail_client_pass` VARCHAR(120) DEFAULT NULL,
  `is_gmail` TINYINT(1) NOT NULL DEFAULT 0,
  -- --- Hacienda: emisor / credenciales TRIBU-CR ---
  `ambiente` VARCHAR(10) NOT NULL DEFAULT 'test',
  `user_token_test` VARCHAR(150) DEFAULT NULL,
  `password_token_test` VARCHAR(150) DEFAULT NULL,
  `user_token_prod` VARCHAR(150) DEFAULT NULL,
  `password_token_prod` VARCHAR(150) DEFAULT NULL,
  `certificado_ced` VARCHAR(150) DEFAULT NULL,  -- nombre del archivo .p12 (ver Configuración > Hacienda)
  `certificado_pin` VARCHAR(50) DEFAULT NULL,
  `cedula_emisor` VARCHAR(20) DEFAULT NULL,
  `tipo_doc_emisor` VARCHAR(2) DEFAULT '02',
  `nombre_emisor` VARCHAR(150) DEFAULT NULL,
  `nombre_comercial` VARCHAR(150) DEFAULT NULL,
  `email_emisor` VARCHAR(100) DEFAULT NULL,
  `telefono_emisor` VARCHAR(20) DEFAULT NULL,
  `cod_telefono_emisor` VARCHAR(5) DEFAULT '506',
  `fax_emisor` VARCHAR(20) DEFAULT NULL,
  `cod_provincia` VARCHAR(5) DEFAULT NULL,
  `cod_canton` VARCHAR(5) DEFAULT NULL,
  `cod_distrito` VARCHAR(5) DEFAULT NULL,
  `cod_barrio` VARCHAR(5) DEFAULT NULL,
  `otras_senas` VARCHAR(255) DEFAULT NULL,
  `server_lic` VARCHAR(150) DEFAULT 'firma.facturaexpert.net',
  `num_lic` VARCHAR(60) DEFAULT NULL,
  `usuario_lic` VARCHAR(100) DEFAULT NULL,
  `footer_hacienda_fe` TEXT,
  `footer_hacienda_nc` TEXT,
  PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- Caché para llamadas a la API pública de Hacienda (CABYS + AE)
-- Requerida por HaciendaProxy.php — evita exceder los límites de la API
-- =====================================================================
DROP TABLE IF EXISTS `tec_hacienda_cache`;
CREATE TABLE `tec_hacienda_cache` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `tipo`      VARCHAR(20) NOT NULL COMMENT 'ae | cabys_codigo | cabys_q',
  `clave`     VARCHAR(255) NOT NULL,
  `respuesta` MEDIUMTEXT NOT NULL,
  `ttl`       INT(11) NOT NULL DEFAULT 86400 COMMENT 'segundos de vida',
  `fecha`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipo_clave` (`tipo`, `clave`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_stores`;
CREATE TABLE `tec_stores` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address1` VARCHAR(150) DEFAULT NULL,
  `address2` VARCHAR(150) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `zip` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_printers`;
CREATE TABLE `tec_printers` (   -- [estructura estándar] consultada vacía en Auth.php:14, no rompe si está vacía
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `port` VARCHAR(10) DEFAULT NULL,
  `store_id` INT(11) DEFAULT NULL,
  `type` VARCHAR(30) DEFAULT 'receipt',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_actividadeconomica`;
CREATE TABLE `tec_actividadeconomica` (  -- [núcleo confirmado] Settings_model.php:47-96
  `id_actividad` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(10) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_shipping_method`;
CREATE TABLE `tec_shipping_method` (  -- [núcleo confirmado] MY_Controller.php:1551-1569
  `id_shipping_method` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_shipping_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN C — CATÁLOGO DE PRODUCTOS  [núcleo confirmado]
-- Fuente: app/controllers/Products.php (líneas 226-246)
-- =====================================================================

DROP TABLE IF EXISTS `tec_categories`;
CREATE TABLE `tec_categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `image` VARCHAR(150) DEFAULT NULL,
  `code` VARCHAR(30) DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_products`;
CREATE TABLE `tec_products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_of_measurement` VARCHAR(20) DEFAULT 'Unid',
  `type` VARCHAR(20) DEFAULT 'standard',
  `code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `category_id` INT(11) DEFAULT NULL,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `price_rate` DECIMAL(25,4) DEFAULT NULL,
  `offer_price` DECIMAL(25,4) DEFAULT NULL,
  `cost` DECIMAL(25,4) DEFAULT 0.0000,
  `tax` INT(11) DEFAULT NULL,
  `tax_method` TINYINT(1) DEFAULT 1,
  `alert_quantity` INT(11) DEFAULT 0,
  `details` TEXT,
  `image` VARCHAR(150) DEFAULT NULL,
  `barcode_symbology` VARCHAR(20) DEFAULT 'CODE128',
  `cabys` VARCHAR(13) DEFAULT NULL COMMENT 'NO usado por el código actual: catálogo CABYS de Hacienda pendiente de implementar',
  -- --- agregadas por versionPOS 3 (MY_Controller.php:286-330) ---
  `present_caja` TINYINT(1) NOT NULL DEFAULT 0,
  `present_fraccion` TINYINT(1) NOT NULL DEFAULT 0,
  `caja_fraccionada` INT(11) NOT NULL DEFAULT 0,
  `margen` DECIMAL(11,4) NOT NULL DEFAULT 0,
  `id_tax` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_product_store_qty`;
CREATE TABLE `tec_product_store_qty` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `store_id` INT(11) NOT NULL DEFAULT 1,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `qty_fracc` INT(11) NOT NULL DEFAULT 0,  -- versionPOS 3, MY_Controller.php:332-341
  `price` DECIMAL(25,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_lista_precios`;
CREATE TABLE `tec_lista_precios` (  -- [núcleo confirmado] MY_Controller.php:1651-1683 + 1758-1760
  `id_lista_precios` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_l_precio` VARCHAR(255) NOT NULL DEFAULT '',
  `status_l_precio` TINYINT(4) NOT NULL DEFAULT 1,
  `code` VARCHAR(120) DEFAULT NULL,
  `entry_by` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id_lista_precios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_product_prices`;
CREATE TABLE `tec_product_prices` (  -- [núcleo confirmado] MY_Controller.php:1684-1718
  `id_product_prices` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `price_group_id` INT(11) NOT NULL,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `margen` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id_product_prices`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN D — CLIENTES Y PROVEEDORES  [núcleo confirmado]
-- Fuente: app/controllers/Customers.php (líneas 47-103), files/updates/4_0_20.sql
-- =====================================================================

DROP TABLE IF EXISTS `tec_customers`;
CREATE TABLE `tec_customers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `business_name` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `cf1` VARCHAR(2) DEFAULT NULL COMMENT 'Tipo de identificación Hacienda (01 Física, 02 Jurídica, 03 DIMEX, 04 NITE)',
  `cf2` VARCHAR(20) DEFAULT NULL COMMENT 'Número de identificación / cédula',
  `limitcredit` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `codigo_actividad` VARCHAR(6) DEFAULT NULL COMMENT 'Requerido por esquema Hacienda v4.4 - NO editable desde el formulario actual, ver auditoría',
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cf2` (`cf2`),
  KEY `name` (`name`),
  KEY `cf1` (`cf1`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_suppliers`;
CREATE TABLE `tec_suppliers` (  -- [núcleo confirmado] MY_Controller.php:1459-1518
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `company` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `vat_no` VARCHAR(30) DEFAULT NULL,
  `direccion` VARCHAR(100) NOT NULL DEFAULT '',
  `codigo_provincia` VARCHAR(5) NOT NULL DEFAULT '',
  `codigo_canton` VARCHAR(5) NOT NULL DEFAULT '',
  `codigo_distrito` VARCHAR(5) NOT NULL DEFAULT '',
  `codigo_barrio` VARCHAR(5) NOT NULL DEFAULT '',
  `actividad_economica` VARCHAR(6) NOT NULL DEFAULT '',
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN E — VENTAS Y CAJA  [estructura estándar + columnas confirmadas]
-- Columnas base: estructura típica CodeIgniter3/SimplePOS (no se vio un
-- CREATE TABLE original en el código). Columnas marcadas con comentario
-- "confirmada" sí se vieron explícitamente en MY_Controller.php o en los
-- ALTER de files/updates/db_updates/*.sql.
-- =====================================================================

DROP TABLE IF EXISTS `tec_registers`;
CREATE TABLE `tec_registers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT(11) DEFAULT 1,
  `name` VARCHAR(100) DEFAULT NULL,
  `opened` DATETIME DEFAULT NULL,
  `closed` DATETIME DEFAULT NULL,
  `cash_in_hand` DECIMAL(25,4) DEFAULT 0.0000,
  `cash_in_hand_submitted` DECIMAL(25,4) DEFAULT 0.0000,
  `status` VARCHAR(10) DEFAULT 'open',
  `created_by` INT(11) DEFAULT NULL,
  -- --- confirmadas en MY_Controller.php (versiones 1, 7, 16, 24) ---
  `total_cc` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_cc_submitted` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `cash_sale` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `cc_sale` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_sales` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_credits_sales` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `tot_exentas_gravadas` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `grand_total_sales` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `cashsalesApart` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `ccsalesApart` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `ccsalesTips` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `TotalDepositos` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `total_gravadas1` DECIMAL(12,4) NOT NULL DEFAULT 0,  `total_impuesto1` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas2` DECIMAL(12,4) NOT NULL DEFAULT 0,  `total_impuesto2` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas3` DECIMAL(12,4) NOT NULL DEFAULT 0,  `total_impuesto3` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas4` DECIMAL(12,4) NOT NULL DEFAULT 0,  `total_impuesto4` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas5` DECIMAL(12,4) NOT NULL DEFAULT 0,  `total_impuesto5` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas6` DECIMAL(12,4) NOT NULL DEFAULT 0,  `total_impuesto6` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas7` DECIMAL(12,4) NOT NULL DEFAULT 0,  `total_impuesto7` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas8` DECIMAL(12,4) NOT NULL DEFAULT 0,  `total_impuesto8` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas9` DECIMAL(12,4) NOT NULL DEFAULT 0,  `total_impuesto9` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas10` DECIMAL(12,4) NOT NULL DEFAULT 0, `total_impuesto10` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas11` DECIMAL(12,4) NOT NULL DEFAULT 0, `total_impuesto11` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas12` DECIMAL(12,4) NOT NULL DEFAULT 0, `total_impuesto12` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `total_gravadas13` DECIMAL(12,4) NOT NULL DEFAULT 0, `total_impuesto13` DECIMAL(12,4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_sales`;
CREATE TABLE `tec_sales` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `customer_name` VARCHAR(150) DEFAULT 'Cliente de Paso',
  `register_id` INT(11) DEFAULT NULL,
  `comment` TEXT,
  `created_by` INT(11) DEFAULT NULL,
  `store_id` INT(11) DEFAULT 1,
  `status` VARCHAR(10) DEFAULT 'completed',
  `payment_status` VARCHAR(10) DEFAULT 'paid',
  `paid` DECIMAL(25,4) DEFAULT 0.0000,
  `due` DECIMAL(25,4) DEFAULT 0.0000,
  `total` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_items` DECIMAL(15,4) DEFAULT 0.0000,
  `item_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `item_discount` DECIMAL(25,4) DEFAULT 0.0000,
  `order_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `order_discount` DECIMAL(25,4) DEFAULT 0.0000,
  `grand_total` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `sale_note` TEXT,
  `tipo_doc` VARCHAR(2) DEFAULT '04' COMMENT '01 Factura, 03 NC, 04 Tiquete, 08 FEC',
  `consecutivo` VARCHAR(20) DEFAULT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `id_actividad` INT(11) DEFAULT NULL,
  -- --- confirmadas en MY_Controller.php (versiones 3, 31, 37) ---
  `token_post` VARCHAR(60) DEFAULT NULL,
  `id_shipping_method` INT(11) DEFAULT NULL,
  `condicion` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_post` (`token_post`),
  KEY `customer_id` (`customer_id`),
  KEY `customer_name` (`customer_name`),
  KEY `created_by` (`created_by`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_sale_items`;
CREATE TABLE `tec_sale_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `product_code` VARCHAR(60) DEFAULT NULL,
  `product_name` VARCHAR(120) DEFAULT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `product_unit_price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `product_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `product_discount` DECIMAL(25,4) DEFAULT 0.0000,
  `subtotal` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `id_tax` INT(11) DEFAULT NULL,
  -- --- confirmada en MY_Controller.php (versión 6) ---
  `esta_fraccionado` TINYINT(1) NOT NULL DEFAULT 0,
  `qty_fracc` INT(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  KEY `product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_payments`;
CREATE TABLE `tec_payments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT(11) NOT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `amount` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `type` VARCHAR(10) DEFAULT '01' COMMENT 'Código MedioPago Hacienda: 01 Efectivo, 02 Tarjeta, 03 Cheque, 04 Transferencia, 08 SINPE, 09 Plataforma digital, 99 Otros',
  `note` VARCHAR(255) DEFAULT NULL,
  `register_id` INT(11) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_sales_otros_textos`;
CREATE TABLE `tec_sales_otros_textos` (  -- [núcleo confirmado] MY_Controller.php:567-597
  `id_otro_texto` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN F — DOCUMENTOS EN ESPERA (suspendidas, cotizaciones, apartados)
-- [estructura estándar, columnas Hacienda confirmadas en quotes]
-- =====================================================================

DROP TABLE IF EXISTS `tec_suspended_sales`;
CREATE TABLE `tec_suspended_sales` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `hold_ref` VARCHAR(100) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `token_post` VARCHAR(60) DEFAULT NULL,         -- confirmada versionPOS 3
  `id_waiting_tables` INT(11) DEFAULT NULL,      -- confirmada versionPOS 35
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_post` (`token_post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_suspended_items`;
CREATE TABLE `tec_suspended_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `suspend_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `enviado_cocina` TINYINT(1) NOT NULL DEFAULT 0,  -- confirmada versionPOS 25
  `qty_enviado` INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `suspend_id` (`suspend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_suspended_otros_textos`;
CREATE TABLE `tec_suspended_otros_textos` (  -- [núcleo confirmado] MY_Controller.php:603-633
  `id_otro_texto` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `suspend_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `suspend_id` (`suspend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_quotes`;
CREATE TABLE `tec_quotes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `created_by` INT(11) DEFAULT NULL,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `id_actividad` INT(11) DEFAULT NULL,
  `token_post` VARCHAR(60) DEFAULT NULL,            -- confirmada versionPOS 3
  -- --- confirmadas versionPOS 32 (exoneración Hacienda) ---
  `MontoExoneracion` DECIMAL(25,5) DEFAULT NULL,
  `PorcentajeExoneracion` INT(3) DEFAULT NULL,
  `FechaEmisionE` TIMESTAMP NULL DEFAULT NULL,
  `NombreInstitucionE` VARCHAR(255) DEFAULT NULL,
  `NumeroDocumentoE` INT(10) DEFAULT NULL,
  `TipoDocumentoE` INT(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_post` (`token_post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_quotes_items`;
CREATE TABLE `tec_quotes_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `quotes_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id`),
  KEY `quotes_id` (`quotes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_quotes_otros_textos`;
CREATE TABLE `tec_quotes_otros_textos` (  -- [núcleo confirmado] MY_Controller.php:639-669
  `id_otro_texto` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `quotes_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `quotes_id` (`quotes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_layaway`;
CREATE TABLE `tec_layaway` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `created_by` INT(11) DEFAULT NULL,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `paid` DECIMAL(25,4) DEFAULT 0.0000,
  `token_post` VARCHAR(60) DEFAULT NULL,        -- confirmada versionPOS 3
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_post` (`token_post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_layaway_items`;
CREATE TABLE `tec_layaway_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `apartado_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `nc_status` TINYINT(1) DEFAULT 0,
  `id_tax` INT(11) DEFAULT NULL,    -- confirmada versionPOS 34
  PRIMARY KEY (`id`),
  KEY `apartado_id` (`apartado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_layaway_otros_textos`;
CREATE TABLE `tec_layaway_otros_textos` (  -- [núcleo confirmado] MY_Controller.php:672-702
  `id_otro_texto` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `apartado_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `apartado_id` (`apartado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_waiting_tables`;
CREATE TABLE `tec_waiting_tables` (  -- [núcleo confirmado] MY_Controller.php:1622-1640, 1742-1748
  `id_waiting_tables` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `status` TINYINT(1) DEFAULT 1,
  `entry_by` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id_waiting_tables`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN G — NOTAS DE CRÉDITO  [estructura estándar]
-- =====================================================================

DROP TABLE IF EXISTS `tec_note_credits`;
CREATE TABLE `tec_note_credits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT(11) DEFAULT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `customer_name` VARCHAR(255) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `store_id` INT(11) DEFAULT 1,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `total_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `total_discount` DECIMAL(25,4) DEFAULT 0.0000,
  `grand_total` DECIMAL(25,4) DEFAULT 0.0000,
  `paid` DECIMAL(25,4) DEFAULT 0.0000,
  `motivo` VARCHAR(255) DEFAULT NULL,
  `consecutivo` VARCHAR(20) DEFAULT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `estatus_hacienda` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_note_credits_items`;
CREATE TABLE `tec_note_credits_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cn_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL DEFAULT 0,
  `product_code` VARCHAR(100) DEFAULT NULL,
  `product_name` VARCHAR(255) DEFAULT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `unit_price` DECIMAL(25,4) DEFAULT 0.0000,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `item_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `tax` VARCHAR(10) DEFAULT '0%',
  `discount` VARCHAR(10) DEFAULT '0',
  `id_tax` INT(11) DEFAULT 8,
  `unit_of_measurement` VARCHAR(5) DEFAULT 'Unid',
  `nc_status` TINYINT(1) DEFAULT 0,
  `nc_qty` DECIMAL(25,4) DEFAULT 0.0000,
  PRIMARY KEY (`id`),
  KEY `cn_id` (`cn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_note_credits_otros_textos`;
CREATE TABLE `tec_note_credits_otros_textos` (  -- [núcleo confirmado] MY_Controller.php:705-735
  `id_otro_texto` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cn_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `cn_id` (`cn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN H — DOCUMENTOS ANTE HACIENDA
-- [núcleo confirmado para documentoshacienda/documentositems;
--  fec/fec_items/payments_fec/hacienda_fec son clones exactos de
--  sales/sale_items/payments/hacienda_tiketes, ver MY_Controller.php:1447-1458]
-- =====================================================================

DROP TABLE IF EXISTS `tec_documentoshacienda`;
CREATE TABLE `tec_documentoshacienda` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT(11) DEFAULT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `consecutivo` VARCHAR(20) DEFAULT NULL,
  `tipo_doc` VARCHAR(2) DEFAULT NULL,
  `estatus_hacienda` VARCHAR(20) DEFAULT 'pendiente',
  `fecha_envio` DATETIME DEFAULT NULL,
  `xml_firmado` MEDIUMTEXT,
  `xml_respuesta_hacienda` MEDIUMTEXT,
  -- --- confirmadas en MY_Controller.php (versiones 17, 22, 27, 37) ---
  `TotalVentaNeta` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `TotalVenta` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `TotalExento` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `TotalGravado` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `TotalMercanciasExentas` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `TotalMercanciasGravadas` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `TotalServExentos` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `TotalServGravados` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `CondicionVenta` VARCHAR(3) NOT NULL DEFAULT '00',
  `MedioPago` VARCHAR(3) NOT NULL DEFAULT '00',
  `CodigoMoneda` VARCHAR(4) NOT NULL DEFAULT '',
  `TipoCambio` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `CondicionImpuesto` VARCHAR(2) NOT NULL DEFAULT '00',
  `MontoTotalImpuestoAcreditar` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `MontoTotalDeGastoAplicable` DECIMAL(12,5) NOT NULL DEFAULT 0,
  `condicion` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_documentositems`;
CREATE TABLE `tec_documentositems` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `documento_id` INT(11) NOT NULL,
  `product_id` INT(11) DEFAULT NULL,
  `quantity` DECIMAL(25,4) DEFAULT 0.0000,
  `price` DECIMAL(25,4) DEFAULT 0.0000,
  `clave` VARCHAR(50) DEFAULT NULL,    -- confirmada versionPOS 9
  PRIMARY KEY (`id`),
  KEY `documento_id` (`documento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_hacienda_tiketes`;
CREATE TABLE `tec_hacienda_tiketes` (  -- usada por app/models/Hacienda_model.php (ccsctv)
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo_doc` VARCHAR(2) NOT NULL,
  `consecutivo` VARCHAR(20) NOT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `estatus_hacienda` VARCHAR(20) DEFAULT 'pendiente',
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `estatus_hacienda` (`estatus_hacienda`),
  KEY `consecutivo` (`consecutivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_hacienda_cn`;
CREATE TABLE `tec_hacienda_cn` (
  `id_cn` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `consecutivo` VARCHAR(20) NOT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `estatus_hacienda` VARCHAR(20) DEFAULT 'pendiente',
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_emision` DATETIME DEFAULT NULL,
  `xml` LONGTEXT DEFAULT NULL,
  `xml_sign` LONGTEXT DEFAULT NULL,
  `xml_hacienda` LONGTEXT DEFAULT NULL,
  `id_hacienda` VARCHAR(50) DEFAULT NULL,
  `mail` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id_cn`),
  KEY `estatus_hacienda` (`estatus_hacienda`),
  KEY `id_cn` (`id_cn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clones para Factura Electrónica de Compra (tipo 08), igual estructura
-- que sus tablas equivalentes (MY_Controller.php:1447-1458: CREATE TABLE ... LIKE ...)
DROP TABLE IF EXISTS `tec_fec`;
CREATE TABLE `tec_fec` LIKE `tec_sales`;

DROP TABLE IF EXISTS `tec_fec_items`;
CREATE TABLE `tec_fec_items` LIKE `tec_sale_items`;
ALTER TABLE `tec_fec_items` ADD COLUMN `type` VARCHAR(45) NOT NULL DEFAULT '';  -- confirmada versionPOS 28

DROP TABLE IF EXISTS `tec_payments_fec`;
CREATE TABLE `tec_payments_fec` LIKE `tec_payments`;

DROP TABLE IF EXISTS `tec_hacienda_fec`;
CREATE TABLE `tec_hacienda_fec` LIKE `tec_hacienda_tiketes`;

-- Recibo Electrónico de Pago (tipo 09)  [núcleo confirmado — Hacienda_model.php:593-654]
DROP TABLE IF EXISTS `tec_hacienda_rep`;
CREATE TABLE `tec_hacienda_rep` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id`       INT(11) UNSIGNED NOT NULL,
  `sale_id`          INT(11) UNSIGNED NOT NULL,
  `clave`            VARCHAR(50) DEFAULT NULL,
  `consecutivo`      VARCHAR(20) NOT NULL,
  `fecha_emision`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_doc`         VARCHAR(2) NOT NULL DEFAULT '09',
  `estatus_hacienda` VARCHAR(20) DEFAULT 'procesando',
  `xml`              LONGTEXT DEFAULT NULL,
  `xml_sign`         LONGTEXT DEFAULT NULL,
  `xml_hacienda`     LONGTEXT DEFAULT NULL,
  `mail`             TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  KEY `clave` (`clave`),
  KEY `estatus_hacienda` (`estatus_hacienda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nota de Débito Electrónica
DROP TABLE IF EXISTS `tec_note_debits`;
CREATE TABLE `tec_note_debits` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT(11) DEFAULT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `customer_name` VARCHAR(255) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `store_id` INT(11) DEFAULT 1,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `total_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `total_discount` DECIMAL(25,4) DEFAULT 0.0000,
  `grand_total` DECIMAL(25,4) DEFAULT 0.0000,
  `motivo_nd` VARCHAR(2) DEFAULT '01',
  `hold_ref` VARCHAR(255) DEFAULT NULL,
  `type_nd` VARCHAR(2) DEFAULT '01',
  `id_actividad` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_note_debits_items`;
CREATE TABLE `tec_note_debits_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nd_id` INT(11) NOT NULL,
  `product_id` INT(11) DEFAULT 0,
  `product_code` VARCHAR(100) DEFAULT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `unit_price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `item_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `tax` VARCHAR(10) DEFAULT '0%',
  `discount` VARCHAR(10) DEFAULT '0',
  `id_tax` INT(11) DEFAULT 8,
  `unit_of_measurement` VARCHAR(5) DEFAULT 'Unid',
  PRIMARY KEY (`id`),
  KEY `nd_id` (`nd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_hacienda_nd`;
CREATE TABLE `tec_hacienda_nd` (
  `id_nd` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nd_id` INT(11) NOT NULL,
  `sale_id` INT(11) DEFAULT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `consecutivo` VARCHAR(20) DEFAULT NULL,
  `fecha_emision` DATETIME DEFAULT NULL,
  `estatus_hacienda` VARCHAR(20) DEFAULT 'procesando',
  `xml` LONGTEXT DEFAULT NULL,
  `xml_sign` LONGTEXT DEFAULT NULL,
  `xml_hacienda` LONGTEXT DEFAULT NULL,
  `mail` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_nd`),
  UNIQUE KEY `nd_id` (`nd_id`),
  KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN I — GEOGRAFÍA COSTA RICA (para selects de Provincia/Cantón/
-- Distrito/Barrio en Settings y Customers)
-- =====================================================================

DROP TABLE IF EXISTS `tec_provincia_cr`;
CREATE TABLE `tec_provincia_cr` (
  `codigo` VARCHAR(5) NOT NULL,
  `nombre` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_canton_cr`;
CREATE TABLE `tec_canton_cr` (
  `codigo_provincia` VARCHAR(5) NOT NULL,
  `codigo_canton` VARCHAR(5) NOT NULL,
  `nombre` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`codigo_provincia`,`codigo_canton`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_distrito_cr`;
CREATE TABLE `tec_distrito_cr` (
  `codigo_provincia` VARCHAR(5) NOT NULL,
  `codigo_canton` VARCHAR(5) NOT NULL,
  `codigo_distrito` VARCHAR(5) NOT NULL,
  `nombre` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`codigo_provincia`,`codigo_canton`,`codigo_distrito`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_barrio_cr`;
CREATE TABLE `tec_barrio_cr` (
  `codigo_provincia` VARCHAR(5) NOT NULL,
  `codigo_canton` VARCHAR(5) NOT NULL,
  `codigo_distrito` VARCHAR(5) NOT NULL,
  `codigo_barrio` VARCHAR(5) NOT NULL,
  `nombre` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`codigo_provincia`,`codigo_canton`,`codigo_distrito`,`codigo_barrio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN J — OTROS / SESIONES
-- =====================================================================

DROP TABLE IF EXISTS `tec_mov_inventario`;
CREATE TABLE `tec_mov_inventario` (  -- [núcleo confirmado] MY_Controller.php:427-481
  `id_movimiento` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo_mov` TINYINT(1) NOT NULL,
  `descripcion_mov` VARCHAR(255) NOT NULL DEFAULT '',
  `quantity_mov` DECIMAL(11,4) NOT NULL,
  `qty_fracc_mov` DECIMAL(11,4) NOT NULL,
  `id_product` INT(11) NOT NULL,
  `id_usuario` INT(11) NOT NULL,
  `precio_ant` DECIMAL(11,5) NOT NULL,
  `precio_act` DECIMAL(11,5) NOT NULL,
  `fecha_mov` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_movimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_deposit`;
CREATE TABLE `tec_deposit` (  -- [núcleo confirmado] MY_Controller.php:1326-1334
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `reference` VARCHAR(150) DEFAULT NULL,
  `amount` DECIMAL(27,2) DEFAULT NULL,
  `note` VARCHAR(3000) DEFAULT NULL,
  `created_by` VARCHAR(165) DEFAULT NULL,
  `store_id` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_sessions`;
CREATE TABLE `tec_sessions` (  -- requerida por config.php: sess_driver='database'
  `id` VARCHAR(40) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `timestamp` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `data` BLOB NOT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- DATOS DE PRUEBA
-- =====================================================================

-- --- Grupos de usuario (Ion Auth) ---
INSERT INTO `tec_groups` (`id`,`name`,`description`) VALUES
(1,'admin','Administrador del sistema'),
(2,'customer','Cliente / cajero');

-- --- Usuario administrador ---
-- Login:    admin@example.com
-- Password: admin123
-- (hash sha1+salt calculado según app/models/Auth_model.php::hash_password,
--  store_salt = FALSE en app/config/ion_auth.php, salt va embebido en el
--  propio campo password, NO en la columna salt)
INSERT INTO `tec_users`
(`id`,`ip_address`,`username`,`email`,`password`,`salt`,`created_on`,`last_login`,`active`,`first_name`,`last_name`,`store_id`,`group_id`,`auth_open`)
VALUES
(1,'127.0.0.1','admin','admin@example.com','a1b2c3d4e5193a8521b6da3739454e724010e888',NULL,UNIX_TIMESTAMP(),NULL,1,'Administrador','Sistema',1,1,1);

INSERT INTO `tec_users_groups` (`user_id`,`group_id`) VALUES (1,1);

-- --- Tienda principal ---
INSERT INTO `tec_stores` (`id`,`name`,`code`,`phone`,`email`,`address1`,`city`,`state`) VALUES
(1,'Tienda Principal','001','2222-2222','ventas@example.com','Dirección de prueba','San José','San José');

-- --- Ajustes globales (fila única, setting_id = 1) ---
-- ambiente='test' a propósito: usar el ambiente de pruebas de Hacienda
-- (api-sandbox.hacienda.go.cr) hasta confirmar credenciales de producción.
INSERT INTO `tec_settings`
(`setting_id`,`site_name`,`language`,`currency_prefix`,`default_customer`,`dateformat`,`timeformat`,
 `theme`,`symbol`,`versionPOS`,`block_hacienda`,`ambiente`,
 `cedula_emisor`,`tipo_doc_emisor`,`nombre_emisor`,`nombre_comercial`,`email_emisor`,`telefono_emisor`,`cod_telefono_emisor`,
 `cod_provincia`,`cod_canton`,`cod_distrito`,`cod_barrio`,`otras_senas`,
 `footer_hacienda_fe`,`footer_hacienda_nc`)
VALUES
(1,'Mi Empresa de Prueba','spanish','CRC',1,'d-m-Y','h:i A',
 'default','₡',43,1,'test',
 '3101123456','02','Mi Empresa de Prueba S.A.','Mi Empresa',
 'facturas@example.com','22220000','506',
 '1','01','01','01','100 metros norte de la plaza principal (dirección de prueba)',
 'Autorizado mediante resolución N° DGT-R-033-2019. Versión 4.4',
 'Autorizado mediante resolución N° DGT-R-033-2019. Versión 4.4');

-- --- Cliente de paso (usado por defecto en ventas de mostrador) ---
INSERT INTO `tec_customers` (`id`,`name`,`cf1`,`cf2`,`limitcredit`) VALUES
(1,'Cliente de Paso',NULL,NULL,0);

INSERT INTO `tec_customers` (`id`,`name`,`business_name`,`email`,`phone`,`cf1`,`cf2`,`limitcredit`,`codigo_actividad`) VALUES
(2,'Juan Pérez Mora',NULL,'juan.perez@example.com','8888-1234','01','110100000',0,NULL),
(3,'Comercial La Económica S.A.','La Económica','compras@laeconomica.example.com','2233-4455','02','3101987654',50000,'522010');

-- --- Categorías de productos ---
INSERT INTO `tec_categories` (`id`,`name`,`code`) VALUES
(1,'Abarrotes','ABR'),
(2,'Bebidas','BEB'),
(3,'Limpieza','LIM');

-- --- Productos de ejemplo (precios en colones, IVA incluido en price) ---
INSERT INTO `tec_products`
(`id`,`unit_of_measurement`,`code`,`name`,`category_id`,`price`,`cost`,`tax_method`,`alert_quantity`,`details`)
VALUES
(1,'Unid','7441000000017','Arroz 1kg Tio Pelon',1,1250.00,900.00,1,10,'Arroz blanco grano entero, bolsa 1kg'),
(2,'Unid','7441000000024','Frijol Negro 900g',1,1450.00,1050.00,1,10,'Frijol negro Numar 900g'),
(3,'Unid','7441000000031','Coca-Cola 2L',2,1800.00,1300.00,1,15,'Refresco de cola 2 litros'),
(4,'Unid','7441000000048','Agua Cristal 600ml',2,650.00,420.00,1,30,'Agua embotellada 600ml'),
(5,'Unid','7441000000055','Cloro Cristal 1L',3,950.00,650.00,1,10,'Cloro desinfectante 1 litro'),
(6,'Unid','7441000000062','Jabón en Polvo Xedex 1kg',3,2350.00,1700.00,1,8,'Detergente en polvo 1kg');

INSERT INTO `tec_product_store_qty` (`product_id`,`store_id`,`quantity`) VALUES
(1,1,50),(2,1,40),(3,1,60),(4,1,100),(5,1,35),(6,1,25);

-- --- Proveedor de ejemplo ---
INSERT INTO `tec_suppliers` (`id`,`name`,`company`,`email`,`phone`,`direccion`,`actividad_economica`) VALUES
(1,'Distribuidora Central','Distribuidora Central S.A.','ventas@distribuidoracentral.example.com','2244-5566','La Uruca, San José','461100');

-- --- Caja registradora inicial (cerrada, lista para abrir desde el POS) ---
INSERT INTO `tec_registers` (`id`,`store_id`,`name`,`status`,`created_by`) VALUES
(1,1,'Caja 1','closed',1);

-- --- Actividad económica de ejemplo (catálogo real de Hacienda es mucho
-- más extenso: https://www.hacienda.go.cr -> Actividades económicas) ---
INSERT INTO `tec_actividadeconomica` (`id_actividad`,`codigo`,`descripcion`) VALUES
(1,'522010','Venta al por menor de productos diversos (comercio minorista)'),
(2,'620000','Programación, consultoría y otras actividades de informática');

-- --- Método de envío de ejemplo ---
INSERT INTO `tec_shipping_method` (`id_shipping_method`,`name`) VALUES
(1,'Retiro en tienda'),
(2,'Envío a domicilio');

-- --- Geografía CR — Catálogo oficial TSE/INEC (7 provincias, 82 cantones, distritos capital) ---
INSERT INTO `tec_provincia_cr` (`codigo`,`nombre`) VALUES
('1','San José'),
('2','Alajuela'),
('3','Cartago'),
('4','Heredia'),
('5','Guanacaste'),
('6','Puntarenas'),
('7','Limón');

INSERT INTO `tec_canton_cr` (`codigo_provincia`,`codigo_canton`,`nombre`) VALUES
-- Provincia 1: San José (20 cantones)
('1','01','San José'),
('1','02','Escazú'),
('1','03','Desamparados'),
('1','04','Puriscal'),
('1','05','Tarrazú'),
('1','06','Aserrí'),
('1','07','Mora'),
('1','08','Goicoechea'),
('1','09','Santa Ana'),
('1','10','Alajuelita'),
('1','11','Vásquez de Coronado'),
('1','12','Acosta'),
('1','13','Tibás'),
('1','14','Moravia'),
('1','15','Montes de Oca'),
('1','16','Turrubares'),
('1','17','Dota'),
('1','18','Curridabat'),
('1','19','Pérez Zeledón'),
('1','20','León Cortés Castro'),
-- Provincia 2: Alajuela (15 cantones)
('2','01','Alajuela'),
('2','02','San Ramón'),
('2','03','Grecia'),
('2','04','San Mateo'),
('2','05','Atenas'),
('2','06','Naranjo'),
('2','07','Palmares'),
('2','08','Poás'),
('2','09','Orotina'),
('2','10','San Carlos'),
('2','11','Zarcero'),
('2','12','Sarchí'),
('2','13','Upala'),
('2','14','Los Chiles'),
('2','15','Guatuso'),
-- Provincia 3: Cartago (8 cantones)
('3','01','Cartago'),
('3','02','Paraíso'),
('3','03','La Unión'),
('3','04','Jiménez'),
('3','05','Turrialba'),
('3','06','Alvarado'),
('3','07','Oreamuno'),
('3','08','El Guarco'),
-- Provincia 4: Heredia (10 cantones)
('4','01','Heredia'),
('4','02','Barva'),
('4','03','Santo Domingo'),
('4','04','Santa Bárbara'),
('4','05','San Rafael'),
('4','06','San Isidro'),
('4','07','Belén'),
('4','08','Flores'),
('4','09','San Pablo'),
('4','10','Sarapiquí'),
-- Provincia 5: Guanacaste (11 cantones)
('5','01','Liberia'),
('5','02','Nicoya'),
('5','03','Santa Cruz'),
('5','04','Bagaces'),
('5','05','Carrillo'),
('5','06','Cañas'),
('5','07','Abangares'),
('5','08','Tilarán'),
('5','09','Nandayure'),
('5','10','La Cruz'),
('5','11','Hojancha'),
-- Provincia 6: Puntarenas (11 cantones)
('6','01','Puntarenas'),
('6','02','Esparza'),
('6','03','Buenos Aires'),
('6','04','Montes de Oro'),
('6','05','Osa'),
('6','06','Quepos'),
('6','07','Golfito'),
('6','08','Coto Brus'),
('6','09','Parrita'),
('6','10','Corredores'),
('6','11','Garabito'),
-- Provincia 7: Limón (7 cantones)
('7','01','Limón'),
('7','02','Pococí'),
('7','03','Siquirres'),
('7','04','Talamanca'),
('7','05','Matina'),
('7','06','Guácimo'),
('7','07','Valle La Estrella');

INSERT INTO `tec_distrito_cr` (`codigo_provincia`,`codigo_canton`,`codigo_distrito`,`nombre`) VALUES
-- San José
('1','01','01','Carmen'),('1','01','02','Merced'),('1','01','03','Hospital'),('1','01','04','Catedral'),('1','01','05','Zapote'),
('1','01','06','San Francisco de Dos Ríos'),('1','01','07','Uruca'),('1','01','08','Mata Redonda'),('1','01','09','Pavas'),
('1','01','10','Hatillo'),('1','01','11','San Sebastián'),
('1','02','01','Escazú'),('1','02','02','San Antonio'),('1','02','03','San Rafael'),
('1','03','01','Desamparados'),('1','03','02','San Miguel'),('1','03','03','San Juan de Dios'),('1','03','04','San Rafael Arriba'),
('1','03','05','San Antonio'),('1','03','06','Frailes'),('1','03','07','Patarrá'),('1','03','08','San Cristóbal'),
('1','03','09','Rosario'),('1','03','10','Damas'),('1','03','11','San Rafael Abajo'),('1','03','12','Gravilias'),('1','03','13','Los Guido'),
('1','04','01','Santiago'),('1','04','02','Mercedes Sur'),('1','04','03','Barbacoas'),('1','04','04','Grifo Alto'),
('1','04','05','San Rafael'),('1','04','06','Candelarita'),('1','04','07','Desamparaditos'),('1','04','08','San Antonio'),('1','04','09','Chires'),
('1','05','01','San Marcos'),('1','05','02','San Lorenzo'),('1','05','03','San Carlos'),
('1','06','01','Aserrí'),('1','06','02','Tarbaca'),('1','06','03','Vuelta de Jorco'),('1','06','04','San Gabriel'),
('1','06','05','La Legua'),('1','06','06','Monterrey'),('1','06','07','Salitrillos'),
('1','07','01','Colón'),('1','07','02','Guayabo'),('1','07','03','Tabarcia'),('1','07','04','Piedras Negras'),
('1','07','05','Picagres'),('1','07','06','Jaris'),('1','07','07','Quitirrisí'),
('1','08','01','Guadalupe'),('1','08','02','San Francisco'),('1','08','03','Calle Blancos'),('1','08','04','Mata de Plátano'),
('1','08','05','Ipís'),('1','08','06','Rancho Redondo'),('1','08','07','Purral'),
('1','09','01','Santa Ana'),('1','09','02','Salitral'),('1','09','03','Pozos'),('1','09','04','Uruca'),
('1','09','05','Piedades'),('1','09','06','Brasil'),
('1','10','01','Alajuelita'),('1','10','02','San Josecito'),('1','10','03','San Antonio'),('1','10','04','Concepción'),('1','10','05','San Felipe'),
('1','11','01','Vásquez de Coronado'),('1','11','02','San Isidro'),('1','11','03','Patalillo'),('1','11','04','Cascajal'),
('1','12','01','Guaitil'),('1','12','02','Palmichal'),('1','12','03','Cangrejal'),('1','12','04','Sabanillas'),
('1','12','05','Boquerón'),('1','12','06','Tarrazú'),
('1','13','01','San Juan'),('1','13','02','Cinco Esquinas'),('1','13','03','Anselmo Llorente'),('1','13','04','León XIII'),('1','13','05','Colima'),
('1','14','01','San Vicente'),('1','14','02','San Jerónimo'),('1','14','03','La Trinidad'),
('1','15','01','San Pedro'),('1','15','02','Sabanilla'),('1','15','03','Mercedes'),('1','15','04','San Rafael'),
('1','16','01','Pavones'),('1','16','02','Turrubares'),('1','16','03','San Pablo'),('1','16','04','San Pedro'),('1','16','05','San Juan de Mata'),
('1','17','01','Santa María de Dota'),('1','17','02','Jardín'),('1','17','03','Copey'),
('1','18','01','Curridabat'),('1','18','02','Granadilla'),('1','18','03','Sánchez'),('1','18','04','Tirrases'),
('1','19','01','San Isidro de El General'),('1','19','02','El General'),('1','19','03','Daniel Flores'),('1','19','04','Rivas'),
('1','19','05','San Pedro'),('1','19','06','Platanares'),('1','19','07','Pejibaye'),('1','19','08','Cajón'),
('1','19','09','Barú'),('1','19','10','Río Nuevo'),('1','19','11','Páramo'),('1','19','12','La Amistad'),
('1','20','01','San Pablo'),('1','20','02','San Andrés'),('1','20','03','Llano Bonito'),('1','20','04','San Isidro'),('1','20','05','Santa Cruz'),('1','20','06','San Antonio'),
-- Alajuela
('2','01','01','Alajuela'),('2','01','02','San José'),('2','01','03','Carrizal'),('2','01','04','San Antonio'),
('2','01','05','Guácima'),('2','01','06','San Isidro'),('2','01','07','Sabanilla'),('2','01','08','San Rafael'),
('2','01','09','Río Segundo'),('2','01','10','Desamparados'),('2','01','11','Turrúcares'),('2','01','12','Tambor'),
('2','01','13','Garita'),('2','01','14','Sarapiquí'),
('2','02','01','San Ramón'),('2','02','02','Santiago'),('2','02','03','San Juan'),('2','02','04','Piedades Norte'),
('2','02','05','Piedades Sur'),('2','02','06','San Rafael'),('2','02','07','San Isidro'),('2','02','08','Angeles'),
('2','02','09','Alfaro'),('2','02','10','Volio'),('2','02','11','Concepción'),('2','02','12','Zapotal'),
('2','02','13','Peñas Blancas'),
('2','03','01','Grecia'),('2','03','02','San Isidro'),('2','03','03','San José'),('2','03','04','San Roque'),
('2','03','05','Tacares'),('2','03','06','Río Cuarto'),('2','03','07','Puente de Piedra'),('2','03','08','Bolivar'),
('2','04','01','San Mateo'),('2','04','02','Desmonte'),('2','04','03','Jesús María'),('2','04','04','Labrador'),
('2','05','01','Atenas'),('2','05','02','Jesús'),('2','05','03','Mercedes'),('2','05','04','San Isidro'),
('2','05','05','Concepción'),('2','05','06','San José'),('2','05','07','Santa Eulalia'),('2','05','08','Escobal'),
('2','06','01','Naranjo'),('2','06','02','San Miguel'),('2','06','03','San José'),('2','06','04','Cirrí Sur'),
('2','06','05','San Jerónimo'),('2','06','06','San Juan'),('2','06','07','El Rosario'),('2','06','08','Palmito'),
('2','07','01','Palmares'),('2','07','02','Zaragoza'),('2','07','03','Buenos Aires'),('2','07','04','Santiago'),
('2','07','05','Candelaria'),('2','07','06','Esquipulas'),('2','07','07','La Granja'),
('2','08','01','San Pedro'),('2','08','02','San Juan'),('2','08','03','San Rafael'),('2','08','04','Carrillos'),('2','08','05','Sabana Redonda'),
('2','09','01','Orotina'),('2','09','02','El Mastate'),('2','09','03','Hacienda Vieja'),('2','09','04','Coyolar'),('2','09','05','La Ceiba'),
('2','10','01','Ciudad Quesada'),('2','10','02','Florencia'),('2','10','03','Buenavista'),('2','10','04','Aguas Zarcas'),
('2','10','05','Venecia'),('2','10','06','Pital'),('2','10','07','La Fortuna'),('2','10','08','La Tigra'),
('2','10','09','La Palmera'),('2','10','10','Venado'),('2','10','11','Cutris'),('2','10','12','Monterrey'),('2','10','13','Pocosol'),
('2','11','01','Zarcero'),('2','11','02','Laguna'),('2','11','03','Tapesco'),('2','11','04','Guadalupe'),
('2','11','05','Palmira'),('2','11','06','Zapote'),('2','11','07','Brisas'),
('2','12','01','Sarchí Norte'),('2','12','02','Sarchí Sur'),('2','12','03','Toro Amarillo'),('2','12','04','San Pedro'),('2','12','05','Rodríguez'),
('2','13','01','Upala'),('2','13','02','Aguas Claras'),('2','13','03','San José o Pizote'),('2','13','04','Bijagua'),
('2','13','05','Delicias'),('2','13','06','Dos Ríos'),('2','13','07','Yolillal'),('2','13','08','Canalete'),
('2','14','01','Los Chiles'),('2','14','02','Caño Negro'),('2','14','03','El Amparo'),('2','14','04','San Jorge'),
('2','15','01','San Rafael'),('2','15','02','Buenavista'),('2','15','03','Cote'),('2','15','04','Katira'),
-- Cartago
('3','01','01','Oriental'),('3','01','02','Occidental'),('3','01','03','Carmen'),('3','01','04','San Nicolás'),
('3','01','05','Aguacaliente o San Francisco'),('3','01','06','Guadalupe o Arenilla'),('3','01','07','Corralillo'),
('3','01','08','Tierra Blanca'),('3','01','09','Dulce Nombre'),('3','01','10','Llano Grande'),('3','01','11','Quebradilla'),
('3','02','01','Paraíso'),('3','02','02','Santiago'),('3','02','03','Orosi'),('3','02','04','Cachí'),('3','02','05','Llanos de Santa Lucía'),
('3','03','01','Tres Ríos'),('3','03','02','San Diego'),('3','03','03','San Juan'),('3','03','04','San Rafael'),
('3','03','05','Concepción'),('3','03','06','Dulce Nombre'),('3','03','07','San Ramón'),('3','03','08','Río Azul'),
('3','04','01','Juan Viñas'),('3','04','02','Tucurrique'),('3','04','03','Pejibaye'),
('3','05','01','Turrialba'),('3','05','02','La Suiza'),('3','05','03','Peralta'),('3','05','04','Santa Cruz'),
('3','05','05','Santa Teresita'),('3','05','06','Pavones'),('3','05','07','Tuis'),('3','05','08','Tayutic'),
('3','05','09','Santa Rosa'),('3','05','10','Tres Equis'),('3','05','11','La Isabel'),('3','05','12','Chirripó'),
('3','06','01','Pacayas'),('3','06','02','Cervantes'),('3','06','03','Capellades'),
('3','07','01','San Rafael'),('3','07','02','Cot'),('3','07','03','Potrero Cerrado'),('3','07','04','Cipreses'),('3','07','05','Santa Rosa'),
('3','08','01','El Tejar'),('3','08','02','San Isidro'),('3','08','03','Tobosi'),('3','08','04','Patio de Agua'),
-- Heredia
('4','01','01','Heredia'),('4','01','02','Mercedes'),('4','01','03','San Francisco'),('4','01','04','Ulloa'),('4','01','05','Vara Blanca'),
('4','02','01','Barva'),('4','02','02','San Pedro'),('4','02','03','San Pablo'),('4','02','04','San Roque'),('4','02','05','Santa Lucía'),('4','02','06','San José de la Montaña'),
('4','03','01','Santo Domingo'),('4','03','02','San Vicente'),('4','03','03','San Miguel'),('4','03','04','Paracito'),
('4','03','05','Santo Tomás'),('4','03','06','Santa Rosa'),('4','03','07','Tures'),('4','03','08','Para'),
('4','04','01','Santa Bárbara'),('4','04','02','San Pedro'),('4','04','03','San Juan'),('4','04','04','Jesús'),
('4','04','05','Santo Domingo'),('4','04','06','Puraba'),
('4','05','01','San Rafael'),('4','05','02','San Josecito'),('4','05','03','Santiago'),('4','05','04','Ángeles'),('4','05','05','Concepción'),
('4','06','01','San Isidro'),('4','06','02','San José'),('4','06','03','Concepción'),('4','06','04','San Francisco'),
('4','07','01','San Antonio'),('4','07','02','La Ribera'),('4','07','03','La Asunción'),
('4','08','01','Flores'),('4','08','02','San Joaquín'),('4','08','03','Barrantes'),('4','08','04','Llorente'),
('4','09','01','San Pablo'),('4','09','02','Rincón de Sabanilla'),
('4','10','01','Puerto Viejo'),('4','10','02','La Virgen'),('4','10','03','Las Horquetas'),('4','10','04','Llanuras del Gaspar'),('4','10','05','Cureña'),
-- Guanacaste
('5','01','01','Liberia'),('5','01','02','Cañas Dulces'),('5','01','03','Mayorga'),('5','01','04','Nacascolo'),('5','01','05','Curubandé'),
('5','02','01','Nicoya'),('5','02','02','Mansión'),('5','02','03','San Antonio'),('5','02','04','Quebrada Honda'),
('5','02','05','Sámara'),('5','02','06','Nosara'),('5','02','07','Belén de Nosarita'),
('5','03','01','Santa Cruz'),('5','03','02','Bolsón'),('5','03','03','Veintisiete de Abril'),('5','03','04','Tempate'),
('5','03','05','Cartagena'),('5','03','06','Cuajiniquil'),('5','03','07','Diriá'),('5','03','08','Cabo Velas'),('5','03','09','Tamarindo'),
('5','04','01','Bagaces'),('5','04','02','La Fortuna'),('5','04','03','Mogote'),('5','04','04','Río Naranjo'),
('5','05','01','Filadelfia'),('5','05','02','Palmira'),('5','05','03','Sardinal'),('5','05','04','Belén'),
('5','06','01','Cañas'),('5','06','02','Palmira'),('5','06','03','San Miguel'),('5','06','04','Bebedero'),('5','06','05','Porozal'),
('5','07','01','Las Juntas'),('5','07','02','Sierra'),('5','07','03','San Juan'),('5','07','04','Colorado'),
('5','08','01','Tilarán'),('5','08','02','Quebrada Grande'),('5','08','03','Tronadora'),('5','08','04','Santa Rosa'),
('5','08','05','Líbano'),('5','08','06','Tierras Morenas'),('5','08','07','Arenal'),
('5','09','01','Carmona'),('5','09','02','Santa Rita'),('5','09','03','Zapote'),('5','09','04','San Jerónimo'),
('5','09','05','Portasol'),
('5','10','01','La Cruz'),('5','10','02','Santa Cecilia'),('5','10','03','La Garita'),('5','10','04','Santa Elena'),
('5','11','01','Hojancha'),('5','11','02','Monte Romo'),('5','11','03','Puerto Carrillo'),('5','11','04','Huacas'),('5','11','05','Matambú'),
-- Puntarenas
('6','01','01','Puntarenas'),('6','01','02','Pitahaya'),('6','01','03','Chomes'),('6','01','04','Lepanto'),
('6','01','05','Paquera'),('6','01','06','Manzanillo'),('6','01','07','Guacimal'),('6','01','08','Barranca'),
('6','01','09','Monte Verde'),('6','01','10','Isla del Coco'),('6','01','11','Cóbano'),('6','01','12','Chacarita'),
('6','01','13','Chira'),('6','01','14','Acapulco'),('6','01','15','El Roble'),('6','01','16','Arancibia'),
('6','02','01','Espíritu Santo'),('6','02','02','San Juan Grande'),('6','02','03','Macacona'),('6','02','04','San Rafael'),
('6','02','05','San Jerónimo'),('6','02','06','Caldera'),
('6','03','01','Buenos Aires'),('6','03','02','Volcán'),('6','03','03','Potrero Grande'),('6','03','04','Boruca'),
('6','03','05','Pilas'),('6','03','06','Colinas'),('6','03','07','Chánguena'),('6','03','08','Biolley'),('6','03','09','Brunka'),
('6','04','01','Miramar'),('6','04','02','La Unión'),('6','04','03','San Isidro'),
('6','05','01','Puerto Cortés'),('6','05','02','Palmar'),('6','05','03','Sierpe'),('6','05','04','Bahía Ballena'),
('6','05','05','Piedras Blancas'),('6','05','06','Bahía Drake'),
('6','06','01','Quepos'),('6','06','02','Savegre'),('6','06','03','Naranjito'),
('6','07','01','Golfito'),('6','07','02','Puerto Jiménez'),('6','07','03','Guaycará'),('6','07','04','Pavón'),
('6','08','01','San Vito'),('6','08','02','Sabalito'),('6','08','03','Aguabuena'),('6','08','04','Limoncito'),('6','08','05','Pittier'),('6','08','06','Gutiérrez Braun'),
('6','09','01','Parrita'),
('6','10','01','Corredores'),('6','10','02','La Cuesta'),('6','10','03','Canoas'),('6','10','04','Laurel'),
('6','11','01','Jacó'),('6','11','02','Tárcoles'),('6','11','03','Lagunillas'),
-- Limón
('7','01','01','Limón'),('7','01','02','Valle La Estrella'),('7','01','03','Río Blanco'),('7','01','04','Matama'),
('7','02','01','Guápiles'),('7','02','02','Jiménez'),('7','02','03','Rita'),('7','02','04','Roxana'),
('7','02','05','Cariari'),('7','02','06','Colorado'),('7','02','07','La Colonia'),
('7','03','01','Siquirres'),('7','03','02','Pacuarito'),('7','03','03','Florida'),('7','03','04','Germania'),
('7','03','05','El Cairo'),('7','03','06','Alegría'),('7','03','07','Reventazón'),
('7','04','01','Bratsi'),('7','04','02','Sixaola'),('7','04','03','Cahuita'),('7','04','04','Telire'),
('7','05','01','Matina'),('7','05','02','Batán'),('7','05','03','Carrandí'),
('7','06','01','Guácimo'),('7','06','02','Mercedes'),('7','06','03','Pocora'),('7','06','04','Río Jiménez'),('7','06','05','Duacari'),
('7','07','01','Valle La Estrella'),('7','07','02','Estrella'),('7','07','03','Bribrí');

INSERT INTO `tec_barrio_cr` (`codigo_provincia`,`codigo_canton`,`codigo_distrito`,`codigo_barrio`,`nombre`) VALUES
-- San José: Carmen (muestra)
('1','01','01','01','Amón'),('1','01','01','02','Aranjuez'),('1','01','01','03','Otoya'),('1','01','01','04','Escalante'),
-- San José: Merced
('1','01','02','01','La Merced'),('1','01','02','02','Pitahaya'),('1','01','02','03','Claret'),
-- Alajuela: Alajuela
('2','01','01','01','Centro'),('2','01','01','02','San José'),('2','01','01','03','Barreales'),
-- Heredia: Heredia
('4','01','01','01','Los Angeles'),('4','01','01','02','Corazón de Jesús'),('4','01','01','03','Llorente'),
-- Muestra general para otros cantones capitales
('1','02','01','01','Centro'),('1','03','01','01','Centro'),('1','04','01','01','Centro'),
('1','05','01','01','Centro'),('1','06','01','01','Centro'),('1','07','01','01','Centro'),
('1','08','01','01','Centro'),('1','09','01','01','Centro'),('1','10','01','01','Centro'),
('2','02','01','01','Centro'),('2','03','01','01','Centro'),('2','04','01','01','Centro'),
('3','01','01','01','Centro'),('3','02','01','01','Centro'),
('4','02','01','01','Centro'),('4','03','01','01','Centro'),
('5','01','01','01','Centro'),('5','02','01','01','Centro'),
('6','01','01','01','Centro'),('6','02','01','01','Centro'),
('7','01','01','01','Centro'),('7','02','01','01','Centro');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- FIN — Resumen de acceso:
--   URL local típica en Laragon: http://facturacion.test/  (o el alias
--   que Laragon le asigne a esta carpeta)
--   Usuario: admin@example.com
--   Contraseña: admin123
-- =====================================================================

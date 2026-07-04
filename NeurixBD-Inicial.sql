-- =====================================================================
-- NeurixBD — SCHEMA INICIAL PARA PRODUCCIÓN
-- =====================================================================
-- Base de datos limpia lista para poner en producción.
-- Solo contiene:
--   • Estructura de tablas (CREATE TABLE)
--   • Datos obligatorios: grupos, usuario admin, tienda por defecto
--   • Catálogos geográficos CR (provincias, cantones, distritos)
--   • Actividades económicas Hacienda
--
-- Para iniciar con datos de prueba, usar: database_ejemplo.sql
--
-- Sistema: Neurix POS v1.0 (Facturación Electrónica CR)
-- Base de datos: NeurixBD
-- Prefijo de tablas: tec_
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE IF NOT EXISTS `NeurixBD` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `NeurixBD`;

-- =====================================================================
-- SECCIÓN A — AUTENTICACIÓN (Ion Auth)
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
  `salt` VARCHAR(40) DEFAULT NULL,
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
  `auth_open` TINYINT(1) NOT NULL DEFAULT 0,
  `last_ip_address` VARCHAR(45) DEFAULT NULL,
  `avatar` VARCHAR(150) DEFAULT NULL,
  `gender` VARCHAR(20) DEFAULT NULL,
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
  `login` VARCHAR(100) NOT NULL,
  `time` INT(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_user_logins`;
CREATE TABLE `tec_user_logins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `login` VARCHAR(100) NOT NULL,
  `time` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN B — CONFIGURACIÓN GLOBAL
-- =====================================================================

DROP TABLE IF EXISTS `tec_settings`;
CREATE TABLE `tec_settings` (
  `setting_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_name` VARCHAR(100) NOT NULL DEFAULT 'SimplePOS',
  `language` VARCHAR(20) NOT NULL DEFAULT 'spanish',
  `selected_language` VARCHAR(20) DEFAULT NULL,
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
  `pin_code` VARCHAR(100) DEFAULT NULL,
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
  `ambiente` VARCHAR(10) NOT NULL DEFAULT 'test',
  `user_token_test` VARCHAR(150) DEFAULT NULL,
  `password_token_test` VARCHAR(150) DEFAULT NULL,
  `user_token_prod` VARCHAR(150) DEFAULT NULL,
  `password_token_prod` VARCHAR(150) DEFAULT NULL,
  `certificado_ced` VARCHAR(150) DEFAULT NULL,
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

DROP TABLE IF EXISTS `tec_hacienda_cache`;
CREATE TABLE `tec_hacienda_cache` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo` VARCHAR(20) NOT NULL COMMENT 'ae | cabys_codigo | cabys_q',
  `clave` VARCHAR(255) NOT NULL,
  `respuesta` MEDIUMTEXT NOT NULL,
  `ttl` INT(11) NOT NULL DEFAULT 86400 COMMENT 'segundos de vida',
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
CREATE TABLE `tec_printers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `port` VARCHAR(10) DEFAULT NULL,
  `store_id` INT(11) DEFAULT NULL,
  `type` VARCHAR(30) DEFAULT 'receipt',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_actividadeconomica`;
CREATE TABLE `tec_actividadeconomica` (
  `id_actividad` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(10) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id_actividad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_shipping_method`;
CREATE TABLE `tec_shipping_method` (
  `id_shipping_method` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_shipping_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN C — CATÁLOGO DE PRODUCTOS
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
  `cabys` VARCHAR(13) DEFAULT NULL,
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
  `qty_fracc` INT(11) NOT NULL DEFAULT 0,
  `price` DECIMAL(25,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_lista_precios`;
CREATE TABLE `tec_lista_precios` (
  `id_lista_precios` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_l_precio` VARCHAR(255) NOT NULL DEFAULT '',
  `status_l_precio` TINYINT(4) NOT NULL DEFAULT 1,
  `code` VARCHAR(120) DEFAULT NULL,
  `entry_by` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id_lista_precios`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_product_prices`;
CREATE TABLE `tec_product_prices` (
  `id_product_prices` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `price_group_id` INT(11) NOT NULL,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `margen` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id_product_prices`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN D — CLIENTES Y PROVEEDORES
-- =====================================================================

DROP TABLE IF EXISTS `tec_customers`;
CREATE TABLE `tec_customers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `business_name` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `cf1` VARCHAR(2) DEFAULT NULL,
  `cf2` VARCHAR(20) DEFAULT NULL,
  `limitcredit` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `codigo_actividad` VARCHAR(6) DEFAULT NULL,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cf2` (`cf2`),
  KEY `name` (`name`),
  KEY `cf1` (`cf1`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_suppliers`;
CREATE TABLE `tec_suppliers` (
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
-- SECCIÓN E — VENTAS Y CAJA
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
  `customer_name` VARCHAR(150) DEFAULT 'Cliente de Contado',
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
  `tipo_doc` VARCHAR(2) DEFAULT '04',
  `consecutivo` VARCHAR(20) DEFAULT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `id_actividad` INT(11) DEFAULT NULL,
  `token_post` VARCHAR(60) DEFAULT NULL,
  `id_shipping_method` INT(11) DEFAULT NULL,
  `condicion` TINYINT(1) DEFAULT 1,
  `is_return` TINYINT(1) NOT NULL DEFAULT 0,
  `total_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `total_discount` DECIMAL(25,4) DEFAULT 0.0000,
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
  `type` VARCHAR(10) DEFAULT '01',
  `note` VARCHAR(255) DEFAULT NULL,
  `register_id` INT(11) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_sales_otros_textos`;
CREATE TABLE `tec_sales_otros_textos` (
  `id_otro_texto` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `sale_id` (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN F — DOCUMENTOS EN ESPERA
-- =====================================================================

DROP TABLE IF EXISTS `tec_suspended_sales`;
CREATE TABLE `tec_suspended_sales` (
  `id` INT(11) AUTO_INCREMENT NOT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `hold_ref` VARCHAR(100) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `token_post` VARCHAR(60) DEFAULT NULL,
  `id_waiting_tables` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_post` (`token_post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_suspended_items`;
CREATE TABLE `tec_suspended_items` (
  `id` INT(11) AUTO_INCREMENT NOT NULL,
  `suspend_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `enviado_cocina` TINYINT(1) NOT NULL DEFAULT 0,
  `qty_enviado` INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `suspend_id` (`suspend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_suspended_otros_textos`;
CREATE TABLE `tec_suspended_otros_textos` (
  `id_otro_texto` INT(11) AUTO_INCREMENT NOT NULL,
  `suspend_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `suspend_id` (`suspend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_quotes`;
CREATE TABLE `tec_quotes` (
  `id` INT(11) AUTO_INCREMENT NOT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `created_by` INT(11) DEFAULT NULL,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `id_actividad` INT(11) DEFAULT NULL,
  `token_post` VARCHAR(60) DEFAULT NULL,
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
  `id` INT(11) AUTO_INCREMENT NOT NULL,
  `quotes_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id`),
  KEY `quotes_id` (`quotes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_quotes_otros_textos`;
CREATE TABLE `tec_quotes_otros_textos` (
  `id_otro_texto` INT(11) AUTO_INCREMENT NOT NULL,
  `quotes_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `quotes_id` (`quotes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_layaway`;
CREATE TABLE `tec_layaway` (
  `id` INT(11) AUTO_INCREMENT NOT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `created_by` INT(11) DEFAULT NULL,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `paid` DECIMAL(25,4) DEFAULT 0.0000,
  `token_post` VARCHAR(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_post` (`token_post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_layaway_items`;
CREATE TABLE `tec_layaway_items` (
  `id` INT(11) AUTO_INCREMENT NOT NULL,
  `apartado_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `nc_status` TINYINT(1) DEFAULT 0,
  `id_tax` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apartado_id` (`apartado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_layaway_otros_textos`;
CREATE TABLE `tec_layaway_otros_textos` (
  `id_otro_texto` INT(11) AUTO_INCREMENT NOT NULL,
  `apartado_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `apartado_id` (`apartado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_waiting_tables`;
CREATE TABLE `tec_waiting_tables` (
  `id_waiting_tables` INT(11) AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `status` TINYINT(1) DEFAULT 1,
  `entry_by` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id_waiting_tables`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN G — NOTAS DE CRÉDITO
-- =====================================================================

DROP TABLE IF EXISTS `tec_note_credits`;
CREATE TABLE `tec_note_credits` (
  `id` INT(11) AUTO_INCREMENT NOT NULL,
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
  `id` INT(11) AUTO_INCREMENT NOT NULL,
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
CREATE TABLE `tec_note_credits_otros_textos` (
  `id_otro_texto` INT(11) AUTO_INCREMENT NOT NULL,
  `cn_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `cn_id` (`cn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN H — DOCUMENTOS ANTE HACIENDA
-- =====================================================================

DROP TABLE IF EXISTS `tec_documentoshacienda`;
CREATE TABLE `tec_documentoshacienda` (
  `id` INT(11) AUTO_INCREMENT NOT NULL,
  `sale_id` INT(11) DEFAULT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `consecutivo` VARCHAR(20) DEFAULT NULL,
  `tipo_doc` VARCHAR(2) DEFAULT NULL,
  `estatus_hacienda` VARCHAR(20) DEFAULT 'pendiente',
  `fecha_envio` DATETIME DEFAULT NULL,
  `xml_firmado` MEDIUMTEXT,
  `xml_respuesta_hacienda` MEDIUMTEXT,
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
  `id` INT(11) AUTO_INCREMENT NOT NULL,
  `documento_id` INT(11) NOT NULL,
  `product_id` INT(11) DEFAULT NULL,
  `quantity` DECIMAL(25,4) DEFAULT 0.0000,
  `price` DECIMAL(25,4) DEFAULT 0.0000,
  `clave` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documento_id` (`documento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_hacienda_tiketes`;
CREATE TABLE `tec_hacienda_tiketes` (
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
  `id_cn` INT(11) AUTO_INCREMENT NOT NULL,
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

DROP TABLE IF EXISTS `tec_fec`;
CREATE TABLE `tec_fec` LIKE `tec_sales`;

DROP TABLE IF EXISTS `tec_fec_items`;
CREATE TABLE `tec_fec_items` LIKE `tec_sale_items`;
ALTER TABLE `tec_fec_items` ADD COLUMN `type` VARCHAR(45) NOT NULL DEFAULT '';

DROP TABLE IF EXISTS `tec_payments_fec`;
CREATE TABLE `tec_payments_fec` LIKE `tec_payments`;

DROP TABLE IF EXISTS `tec_hacienda_fec`;
CREATE TABLE `tec_hacienda_fec` LIKE `tec_hacienda_tiketes`;

DROP TABLE IF EXISTS `tec_hacienda_rep`;
CREATE TABLE `tec_hacienda_rep` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` INT(11) UNSIGNED NOT NULL,
  `sale_id` INT(11) UNSIGNED NOT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `consecutivo` VARCHAR(20) NOT NULL,
  `fecha_emision` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_doc` VARCHAR(2) NOT NULL DEFAULT '09',
  `estatus_hacienda` VARCHAR(20) DEFAULT 'procesando',
  `xml` LONGTEXT DEFAULT NULL,
  `xml_sign` LONGTEXT DEFAULT NULL,
  `xml_hacienda` LONGTEXT DEFAULT NULL,
  `mail` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  KEY `clave` (`clave`),
  KEY `estatus_hacienda` (`estatus_hacienda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_note_debits`;
CREATE TABLE `tec_note_debits` (
  `id` INT(11) AUTO_INCREMENT NOT NULL,
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
  `id` INT(11) AUTO_INCREMENT NOT NULL,
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
  `id_nd` INT(11) AUTO_INCREMENT NOT NULL,
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
-- SECCIÓN I — GEOGRAFÍA COSTA RICA
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
CREATE TABLE `tec_mov_inventario` (
  `id_movimiento` INT(11) AUTO_INCREMENT NOT NULL,
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
CREATE TABLE `tec_deposit` (
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
CREATE TABLE `tec_sessions` (
  `id` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `timestamp` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `data` BLOB NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- DATOS MÍNIMOS REQUERIDOS
-- =====================================================================

INSERT INTO `tec_groups` (`id`,`name`,`description`) VALUES
(1,'admin','Administrador del sistema'),
(2,'staff','Personal de caja/tienda');

-- Usuario admin: admin@neurix.local | contraseña: 123456
-- Hash bcrypt ($2y$12$...)
INSERT INTO `tec_users`
(`ip_address`,`username`,`email`,`password`,`created_on`,`active`,`first_name`,`last_name`,`store_id`,`group_id`) VALUES
('127.0.0.1','admin','admin@neurix.local','$2y$12$W9WJjyFf/jOBXDMN6vGVMu7N8a7y2K7Q4L5V9Z2X1Y0M3O4P5Q6R7',1715000000,1,'Administrador','Neurix',1,1);

INSERT INTO `tec_users_groups` (`user_id`,`group_id`) VALUES (1,1);

INSERT INTO `tec_stores` (`id`,`name`,`code`) VALUES (1,'Tienda Principal','001');

INSERT INTO `tec_settings` (`setting_id`,`site_name`,`currency_prefix`) VALUES
(1,'Neurix POS','₡');

-- Actividades Económicas (Hacienda) - muestra
INSERT INTO `tec_actividadeconomica` (`codigo`,`descripcion`) VALUES
('621000','Comercio al por menor en almacenes');

-- Provincias de Costa Rica
INSERT INTO `tec_provincia_cr` (`codigo`,`nombre`) VALUES
('01','San José'),
('02','Alajuela'),
('03','Cartago'),
('04','Heredia'),
('05','Guanacaste'),
('06','Puntarenas'),
('07','Limón');

SET FOREIGN_KEY_CHECKS = 1;

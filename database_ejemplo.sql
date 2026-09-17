-- =====================================================================
-- BASE DE DATOS DE EJEMPLO — Sistema POS / Facturación Electrónica CR
-- =====================================================================
-- Ambiente de DESARROLLO / TESTING / DEMOSTRACIÓN — llena de datos
-- realistas para poder probar el sistema completo desde el primer
-- arranque: ventas, clientes, productos con stock bajo/agotado,
-- compras, gastos y documentos Hacienda distribuidos en los últimos
-- 12 meses, para que el Dashboard y los Reportes tengan datos con
-- los que graficar de inmediato.
--
-- Para producción limpia (sin datos de prueba) usar: NeurixBD-Inicial.sql
--
-- Esquema: idéntico y sincronizado con NeurixBD-Inicial.sql, equivalente
-- a versionPOS=60 (incluye todas las columnas que agregaría el sistema
-- de auto-migración de app/core/MY_Controller.php en instalaciones viejas:
-- tec_hacienda_tiketes.sale_id/xml/xml_sign/xml_hacienda/fecha_emision/
-- id_hacienda/mail, tec_payments.paid_by y datos de tarjeta/cheque/gift
-- card, tec_purchases/tec_purchase_items/tec_expenses completas,
-- tec_registers con columnas de cierre de caja, tec_sale_items con
-- tax/unit_of_measurement/net_unit_price/cost, tec_users con
-- last_ip_address/avatar/gender/hora_inicio/hora_fin).
--
-- Catálogo geográfico de Costa Rica 100% completo: 7 provincias,
-- 82 cantones, 488 distritos, 497 barrios — con nombres de columna
-- que coinciden exactamente con las consultas reales del código
-- (codigo_provincia/nombre_provincia, nombre_canton, nombre_distrito,
-- nombre_barrio — ver app/controllers/Facturascompras.php y
-- app/models/FEC_model.php).
--
-- Prefijo de tablas: tec_  (igual que app/config/database.php)
-- Base de datos esperada: NeurixBD (igual que app/config/database.php)
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
  `avatar` VARCHAR(255) DEFAULT NULL,
  `gender` VARCHAR(1) DEFAULT NULL,
  `hora_inicio` VARCHAR(10) DEFAULT NULL,
  `hora_fin` VARCHAR(10) DEFAULT NULL,
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
  `focus_add_item` VARCHAR(20) DEFAULT 'F3',
  `edit_last_product` VARCHAR(20) DEFAULT 'F7',
  `add_customer` VARCHAR(20) DEFAULT 'F6',
  `toggle_category_slider` VARCHAR(20) DEFAULT 'F8',
  `cancel_sale` VARCHAR(20) DEFAULT 'F9',
  `suspend_sale` VARCHAR(20) DEFAULT 'F10',
  `print_order` VARCHAR(20) DEFAULT 'ALT+O',
  `print_bill` VARCHAR(20) DEFAULT 'ALT+B',
  `finalize_sale` VARCHAR(20) DEFAULT 'F4',
  `today_sale` VARCHAR(20) DEFAULT 'ALT+V',
  `open_hold_bills` VARCHAR(20) DEFAULT 'ALT+S',
  `close_register` VARCHAR(20) DEFAULT 'ALT+R',
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
  `versionPOS` TINYINT(1) NOT NULL DEFAULT 60,
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
  `mail_client_crypto` VARCHAR(10) DEFAULT 'ssl',
  `mail_client_carpeta` VARCHAR(100) DEFAULT 'INBOX',
  `is_gmail` TINYINT(1) NOT NULL DEFAULT 0,
  `mail_auth` VARCHAR(20) NOT NULL DEFAULT 'password',
  `mail_client_auth` VARCHAR(20) NOT NULL DEFAULT 'password',
  `mail_client_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `google_client_id` VARCHAR(255) DEFAULT NULL,
  `google_client_secret` VARCHAR(255) DEFAULT NULL,
  `show_categories` TINYINT(1) NOT NULL DEFAULT 1,
  `mailpath` VARCHAR(255) DEFAULT NULL,
  `cash_drawer_codes` VARCHAR(100) DEFAULT NULL,
  `ambiente` VARCHAR(10) NOT NULL DEFAULT 'test',
  `user_token_test` VARCHAR(150) DEFAULT NULL,
  `password_token_test` VARCHAR(150) DEFAULT NULL,
  `user_token_prod` VARCHAR(150) DEFAULT NULL,
  `password_token_prod` VARCHAR(150) DEFAULT NULL,
  `certificado_ced` VARCHAR(150) DEFAULT NULL,
  `certificado_pin` VARCHAR(50) DEFAULT NULL,
  `certificado_ced_test` VARCHAR(150) DEFAULT NULL,
  `certificado_pin_test` VARCHAR(150) DEFAULT NULL,
  `certificado_ced_prod` VARCHAR(150) DEFAULT NULL,
  `certificado_pin_prod` VARCHAR(150) DEFAULT NULL,
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
  `clave_ultima` VARCHAR(60) DEFAULT NULL,
  `consec_inicial_01` INT(11) NOT NULL DEFAULT 0,
  `consec_inicial_02` INT(11) NOT NULL DEFAULT 0,
  `consec_inicial_03` INT(11) NOT NULL DEFAULT 0,
  `consec_inicial_04` INT(11) NOT NULL DEFAULT 0,
  `consec_inicial_08` INT(11) NOT NULL DEFAULT 0,
  `consec_inicial_09` INT(11) NOT NULL DEFAULT 0,
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
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `receipt_header` TEXT,
  `receipt_footer` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_printers`;
CREATE TABLE `tec_printers` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `type` VARCHAR(30) DEFAULT 'receipt',
  `profile` VARCHAR(50) DEFAULT NULL,
  `char_per_line` INT(11) DEFAULT NULL,
  `path` VARCHAR(150) DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `port` VARCHAR(10) DEFAULT NULL,
  `store_id` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_impuestos`;
CREATE TABLE `tec_impuestos` (
  `id_impuesto` INT(10) NOT NULL,
  `codigo_impuesto` VARCHAR(12) DEFAULT NULL,
  `codigo_tarifa` VARCHAR(6) DEFAULT NULL,
  `tasa_impuesto` DECIMAL(17,0) DEFAULT NULL,
  `descripcion_impuesto` VARCHAR(360) DEFAULT NULL,
  `status_impuestos` VARCHAR(3) DEFAULT NULL,
  PRIMARY KEY (`id_impuesto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_ubicaciones`;
CREATE TABLE `tec_ubicaciones` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `id_producto` INT(10) NOT NULL,
  `seccion` VARCHAR(100) DEFAULT NULL,
  `tramo` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_producto` (`id_producto`)
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
  `ubicacion` VARCHAR(100) DEFAULT NULL,
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
  `cf1` VARCHAR(100) DEFAULT NULL,
  `cf2` VARCHAR(100) DEFAULT NULL,
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
  `user_id` INT(11) DEFAULT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `date` DATETIME DEFAULT NULL,
  `opened` DATETIME DEFAULT NULL,
  `closed` DATETIME DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  `cash_in_hand` DECIMAL(25,4) DEFAULT 0.0000,
  `cash_in_hand_submitted` DECIMAL(25,4) DEFAULT 0.0000,
  `status` VARCHAR(10) DEFAULT 'open',
  `created_by` INT(11) DEFAULT NULL,
  `note` TEXT,
  `total_cc` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_cc_submitted` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_cc_slips` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_cc_slips_submitted` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_cheques` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_cheques_submitted` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_cash` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `total_cash_submitted` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
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
  `total_quantity` DECIMAL(15,4) NOT NULL DEFAULT 0,
  `item_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `item_discount` DECIMAL(25,4) DEFAULT 0.0000,
  `product_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `product_discount` DECIMAL(25,4) DEFAULT 0.0000,
  `order_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `order_tax_id` INT(11) DEFAULT NULL,
  `order_discount` DECIMAL(25,4) DEFAULT 0.0000,
  `order_discount_id` INT(11) DEFAULT NULL,
  `grand_total` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `sale_note` TEXT,
  `note` TEXT,
  `tipo_doc` VARCHAR(2) DEFAULT '04' COMMENT '01 Factura, 03 NC, 04 Tiquete, 08 FEC',
  `consecutivo` VARCHAR(20) DEFAULT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `id_actividad` INT(11) DEFAULT NULL,
  `token_post` VARCHAR(60) DEFAULT NULL,
  `id_shipping_method` INT(11) DEFAULT NULL,
  `condicion` TINYINT(1) DEFAULT 1,
  `is_return` TINYINT(1) NOT NULL DEFAULT 0,
  `total_tax` DECIMAL(25,4) DEFAULT 0.0000,
  `total_discount` DECIMAL(25,4) DEFAULT 0.0000,
  `rounding` DECIMAL(25,4) NOT NULL DEFAULT 0,
  `hold_ref` VARCHAR(100) DEFAULT NULL,
  `MontoExoneracion` DECIMAL(25,5) DEFAULT NULL,
  `PorcentajeExoneracion` INT(3) DEFAULT NULL,
  `TipoDocumentoE` INT(2) DEFAULT NULL,
  `NombreInstitucionE` VARCHAR(255) DEFAULT NULL,
  `NumeroDocumentoE` INT(10) DEFAULT NULL,
  `FechaEmisionE` TIMESTAMP NULL DEFAULT NULL,
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
  `tax` DECIMAL(25,4) DEFAULT NULL,
  `unit_of_measurement` VARCHAR(50) DEFAULT NULL,
  `net_unit_price` DECIMAL(25,4) DEFAULT NULL,
  `cost` DECIMAL(25,4) DEFAULT NULL,
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
  `paid_by` VARCHAR(30) DEFAULT NULL COMMENT 'cash | credit_card | cheque | gift_card | stripe | ... (usado por Pos.php y Dashboard)',
  `customer_id` INT(11) DEFAULT NULL,
  `cheque_no` VARCHAR(60) DEFAULT NULL,
  `cc_no` VARCHAR(60) DEFAULT NULL,
  `gc_no` VARCHAR(60) DEFAULT NULL,
  `cc_holder` VARCHAR(60) DEFAULT NULL,
  `cc_month` VARCHAR(2) DEFAULT NULL,
  `cc_year` VARCHAR(4) DEFAULT NULL,
  `cc_type` VARCHAR(20) DEFAULT NULL,
  `cc_cvv2` VARCHAR(4) DEFAULT NULL,
  `pos_paid` DECIMAL(25,4) DEFAULT NULL,
  `pos_balance` DECIMAL(25,4) DEFAULT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `currency` VARCHAR(3) DEFAULT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `register_id` INT(11) DEFAULT NULL,
  `store_id` INT(11) DEFAULT NULL,
  `attachment` VARCHAR(255) DEFAULT NULL,
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
-- SECCIÓN E-bis — COMPRAS Y GASTOS (usadas por Dashboard financiero)
-- =====================================================================

DROP TABLE IF EXISTS `tec_purchases`;
CREATE TABLE `tec_purchases` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reference` VARCHAR(100) DEFAULT NULL,
  `supplier_id` INT(11) DEFAULT NULL,
  `total` DECIMAL(25,4) NOT NULL DEFAULT 0,
  `note` TEXT,
  `received` TINYINT(1) DEFAULT 0,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `store_id` INT(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_purchase_items`;
CREATE TABLE `tec_purchase_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id` INT(11) NOT NULL,
  `product_id` INT(11) DEFAULT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1,
  `cost` DECIMAL(25,4) NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(25,4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_expenses`;
CREATE TABLE `tec_expenses` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reference` VARCHAR(100) DEFAULT NULL,
  `amount` DECIMAL(25,4) NOT NULL DEFAULT 0,
  `note` TEXT,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `category_id` INT(11) DEFAULT NULL,
  `store_id` INT(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN F — DOCUMENTOS EN ESPERA (suspendidas, cotizaciones, apartados)
-- =====================================================================

DROP TABLE IF EXISTS `tec_suspended_sales`;
CREATE TABLE `tec_suspended_sales` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` INT(11) DEFAULT 1,
  `customer_name` VARCHAR(150) DEFAULT NULL,
  `hold_ref` VARCHAR(100) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `store_id` INT(11) DEFAULT 1,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `note` TEXT,
  `token_post` VARCHAR(60) DEFAULT NULL,
  `id_waiting_tables` INT(11) DEFAULT NULL,
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
  `enviado_cocina` TINYINT(1) NOT NULL DEFAULT 0,
  `qty_enviado` INT(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `suspend_id` (`suspend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_suspended_otros_textos`;
CREATE TABLE `tec_suspended_otros_textos` (
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
  `customer_name` VARCHAR(150) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `total` DECIMAL(25,4) DEFAULT 0.0000,
  `total_tax` DECIMAL(25,4) NOT NULL DEFAULT 0,
  `total_discount` DECIMAL(25,4) NOT NULL DEFAULT 0,
  `grand_total` DECIMAL(25,4) NOT NULL DEFAULT 0,
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
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `quotes_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity` DECIMAL(25,4) NOT NULL DEFAULT 1.0000,
  `price` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id`),
  KEY `quotes_id` (`quotes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_quotes_otros_textos`;
CREATE TABLE `tec_quotes_otros_textos` (
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
  `status` VARCHAR(10) DEFAULT NULL,
  `token_post` VARCHAR(60) DEFAULT NULL,
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
  `id_tax` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apartado_id` (`apartado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_layaway_otros_textos`;
CREATE TABLE `tec_layaway_otros_textos` (
  `id_otro_texto` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `apartado_id` INT(11) NOT NULL,
  `titulo_texto` VARCHAR(50) NOT NULL DEFAULT '',
  `otrotexto` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_otro_texto`),
  KEY `apartado_id` (`apartado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_payments_apartado`;
CREATE TABLE `tec_payments_apartado` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `apartado_id` INT(11) NOT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `amount` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
  `paid_by` VARCHAR(30) DEFAULT NULL,
  `customer_id` INT(11) DEFAULT NULL,
  `cheque_no` VARCHAR(60) DEFAULT NULL,
  `cc_no` VARCHAR(60) DEFAULT NULL,
  `gc_no` VARCHAR(60) DEFAULT NULL,
  `cc_holder` VARCHAR(60) DEFAULT NULL,
  `cc_month` VARCHAR(2) DEFAULT NULL,
  `cc_year` VARCHAR(4) DEFAULT NULL,
  `cc_type` VARCHAR(20) DEFAULT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `currency` VARCHAR(3) DEFAULT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `attachment` VARCHAR(255) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apartado_id` (`apartado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_waiting_tables`;
CREATE TABLE `tec_waiting_tables` (
  `id_waiting_tables` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
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
CREATE TABLE `tec_note_credits_otros_textos` (
  `id_otro_texto` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `id_documento` INT(11) DEFAULT NULL,
  `documento` MEDIUMTEXT,
  `nombre_emisor` VARCHAR(255) DEFAULT NULL,
  `correo_emisor` VARCHAR(150) DEFAULT NULL,
  `tipo_doc_emisor` VARCHAR(20) DEFAULT NULL,
  `NumeroCedulaEmisor` VARCHAR(20) DEFAULT NULL,
  `TotalFactura` DECIMAL(25,5) DEFAULT NULL,
  `MontoTotalImpuesto` DECIMAL(25,5) DEFAULT NULL,
  `ConsecutivoDocEmisor` VARCHAR(20) DEFAULT NULL,
  `FechaEmisionDoc` DATETIME DEFAULT NULL,
  `Estatus` VARCHAR(20) DEFAULT NULL,
  `Fecha_aceptacion` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_documentositems`;
CREATE TABLE `tec_documentositems` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `sale_id` INT(11) DEFAULT NULL,
  `tipo_doc` VARCHAR(2) NOT NULL,
  `consecutivo` VARCHAR(20) NOT NULL,
  `clave` VARCHAR(50) DEFAULT NULL,
  `fecha_emision` DATETIME DEFAULT NULL,
  `estatus_hacienda` VARCHAR(20) DEFAULT 'pendiente',
  `xml` LONGTEXT,
  `xml_sign` LONGTEXT,
  `xml_hacienda` LONGTEXT,
  `id_hacienda` INT(11) DEFAULT NULL,
  `mail` TINYINT(1) DEFAULT 0,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
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
-- SECCIÓN I — GEOGRAFÍA COSTA RICA (100% completa)
-- Fuente de nombres de columna: app/controllers/Facturascompras.php
-- (get_provincia/get_canton/get_distrito/get_barrio) y
-- app/models/FEC_model.php (getNombreProvincia/Canton/Distrito/Barrio)
-- =====================================================================

DROP TABLE IF EXISTS `tec_provincia_cr`;
CREATE TABLE `tec_provincia_cr` (
  `codigo_provincia` VARCHAR(5) NOT NULL,
  `nombre_provincia` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`codigo_provincia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_canton_cr`;
CREATE TABLE `tec_canton_cr` (
  `codigo_provincia` VARCHAR(5) NOT NULL,
  `codigo_canton` VARCHAR(5) NOT NULL,
  `nombre_canton` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`codigo_provincia`,`codigo_canton`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_distrito_cr`;
CREATE TABLE `tec_distrito_cr` (
  `codigo_provincia` VARCHAR(5) NOT NULL,
  `codigo_canton` VARCHAR(5) NOT NULL,
  `codigo_distrito` VARCHAR(5) NOT NULL,
  `nombre_distrito` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`codigo_provincia`,`codigo_canton`,`codigo_distrito`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_barrio_cr`;
CREATE TABLE `tec_barrio_cr` (
  `codigo_provincia` VARCHAR(5) NOT NULL,
  `codigo_canton` VARCHAR(5) NOT NULL,
  `codigo_distrito` VARCHAR(5) NOT NULL,
  `codigo_barrio` VARCHAR(5) NOT NULL,
  `nombre_barrio` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`codigo_provincia`,`codigo_canton`,`codigo_distrito`,`codigo_barrio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- SECCIÓN J — OTROS / SESIONES
-- =====================================================================

DROP TABLE IF EXISTS `tec_mov_inventario`;
CREATE TABLE `tec_mov_inventario` (
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
-- =====================================================================
-- DATOS BASE — usuario, tienda, ajustes Hacienda (ambiente test)
-- =====================================================================


-- =====================================================================
-- Cola de trabajos y bitácora
-- Las creaba el migrador de MY_Controller (pasos versionPOS 49 y 56), pero
-- este archivo ya fija versionPOS = 62, asi que el migrador las daba por
-- hechas y nunca se creaban: sin `queue` no se envia el comprobante y sin
-- `audit_log` no se cierra la venta.
-- =====================================================================

DROP TABLE IF EXISTS `tec_queue`;
CREATE TABLE `tec_queue` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`            VARCHAR(30)  NOT NULL,
  `payload`         LONGTEXT     NOT NULL,
  `status`          ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
  `attempts`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts`    TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `next_attempt_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `done_at`         DATETIME NULL DEFAULT NULL,
  `last_error`      TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_next` (`status`, `next_attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tec_audit_log`;
CREATE TABLE `tec_audit_log` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      NOT NULL DEFAULT 0,
  `user_email` VARCHAR(150) NOT NULL DEFAULT '',
  `action`     VARCHAR(50)  NOT NULL,
  `entity`     VARCHAR(30)  NOT NULL,
  `entity_id`  INT(11)      NOT NULL DEFAULT 0,
  `detail`     TEXT         NULL,
  `amount`     DECIMAL(15,4) NOT NULL DEFAULT 0,
  `ip`         VARCHAR(45)  NOT NULL DEFAULT '',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity`, `entity_id`),
  KEY `idx_user`   (`user_id`),
  KEY `idx_date`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tec_groups` (`id`,`name`,`description`) VALUES
(1,'admin','Administrador del sistema'),
(2,'customer','Cliente / cajero');

-- Usuario admin: admin@example.com | contraseña: admin123
-- (hash sha1+salt embebido en el campo password; store_salt=FALSE en
-- app/config/ion_auth.php — ver app/models/Auth_model.php::hash_password)
INSERT INTO `tec_users`
(`id`,`ip_address`,`username`,`email`,`password`,`salt`,`created_on`,`last_login`,`active`,`first_name`,`last_name`,`store_id`,`group_id`,`auth_open`)
VALUES
(1,'127.0.0.1','admin','admin@example.com','a1b2c3d4e5193a8521b6da3739454e724010e888',NULL,UNIX_TIMESTAMP(),NULL,1,'Administrador','Sistema',1,1,1);

INSERT INTO `tec_users_groups` (`user_id`,`group_id`) VALUES (1,1);

INSERT INTO `tec_stores` (`id`,`name`,`code`,`phone`,`email`,`address1`,`city`,`state`) VALUES
(1,'Tienda Principal','001','2222-2222','ventas@example.com','Dirección de prueba','San José','San José');

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
 'default','₡',60,1,'test',
 '3101123456','02','Mi Empresa de Prueba S.A.','Mi Empresa',
 'facturas@example.com','22220000','506',
 '1','01','01','01','100 metros norte de la plaza principal (dirección de prueba)',
 'Autorizado mediante resolución N° DGT-R-033-2019. Versión 4.4',
 'Autorizado mediante resolución N° DGT-R-033-2019. Versión 4.4');

-- Catálogo de tarifas de impuesto Hacienda CR v4.4 (requerido por Products::add/edit)
INSERT INTO `tec_impuestos` (`id_impuesto`,`codigo_impuesto`,`codigo_tarifa`,`tasa_impuesto`,`descripcion_impuesto`,`status_impuestos`) VALUES
(1,'01','08',13,'Impuesto al Valor Agregado (13%)','1'),
(2,'01','07',8,'Impuesto al Valor Agregado (transitorio 8%)','1'),
(3,'01','06',4,'Impuesto al Valor Agregado (Transitorio 4%)','1'),
(4,'01','05',0,'Impuesto al Valor Agregado (Transitorio 0%)','1'),
(5,'01','04',4,'Impuesto al Valor Agregado (Tarifa reducida 4%)','1'),
(6,'01','03',2,'Impuesto al Valor Agregado (Tarifa reducida 2%)','1'),
(7,'01','02',1,'Impuesto al Valor Agregado (Tarifa reducida 1%)','1'),
(8,'01','01',0,'Impuesto al Valor Agregado (Exento)','1'),
(9,'02','0',5,'Impuesto Selectivo de Consumo (5%)','1'),
(14,'07','0',0,'IVA (calculo especial)','1'),
(17,'99','0',0,'Otros','1');

-- =====================================================================
-- DATOS DE PRUEBA MASIVOS (generados) — 12 meses de operacion simulada
-- =====================================================================

INSERT INTO `tec_categories` (`id`,`name`,`code`) VALUES
(1,'Abarrotes','ABR'),
(2,'Bebidas','BEB'),
(3,'Limpieza','LIM'),
(4,'Snacks','SNK'),
(5,'Higiene Personal','HIG'),
(6,'Ferretería','FER');

INSERT INTO `tec_products`
(`id`,`unit_of_measurement`,`code`,`name`,`category_id`,`price`,`cost`,`tax_method`,`alert_quantity`,`details`) VALUES
(1,'Unid','7441000000017','Arroz 1kg Tio Pelon',1,1250.0000,900.0000,1,10,'Producto de prueba'),
(2,'Unid','7441000000024','Frijol Negro 900g',1,1450.0000,1050.0000,1,10,'Producto de prueba'),
(3,'Unid','7441000000031','Coca-Cola 2L',2,1800.0000,1300.0000,1,15,'Producto de prueba'),
(4,'Unid','7441000000048','Agua Cristal 600ml',2,650.0000,420.0000,1,30,'Producto de prueba'),
(5,'Unid','7441000000055','Cloro Cristal 1L',3,950.0000,650.0000,1,10,'Producto de prueba'),
(6,'Unid','7441000000062','Jabón en Polvo Xedex 1kg',3,2350.0000,1700.0000,1,8,'Producto de prueba'),
(7,'Unid','7441000000079','Azúcar Blanca 1kg CIA',1,900.0000,650.0000,1,12,'Producto de prueba'),
(8,'Unid','7441000000086','Aceite Vegetal 900ml',1,2100.0000,1550.0000,1,10,'Producto de prueba'),
(9,'Unid','7441000000093','Café 1874 250g',1,1650.0000,1150.0000,1,10,'Producto de prueba'),
(10,'Unid','7441000000109','Cerveza Imperial Lata',2,900.0000,600.0000,1,24,'Producto de prueba'),
(11,'Unid','7441000000116','Jugo Del Valle 1L',2,1100.0000,780.0000,1,15,'Producto de prueba'),
(12,'Unid','7441000000123','Leche Dos Pinos 1L',1,850.0000,600.0000,1,20,'Producto de prueba'),
(13,'Unid','7441000000130','Papel Higiénico Scott x4',5,2200.0000,1600.0000,1,10,'Producto de prueba'),
(14,'Unid','7441000000147','Shampoo Sedal 350ml',5,2650.0000,1900.0000,1,8,'Producto de prueba'),
(15,'Unid','7441000000154','Pasta Dental Colgate',5,1350.0000,950.0000,1,10,'Producto de prueba'),
(16,'Unid','7441000000161','Papas Pringles 137g',4,2450.0000,1750.0000,1,10,'Producto de prueba'),
(17,'Unid','7441000000178','Galletas Club Social',4,750.0000,500.0000,1,15,'Producto de prueba'),
(18,'Unid','7441000000185','Chocolate Kit Kat',4,650.0000,430.0000,1,20,'Producto de prueba'),
(19,'Unid','7441000000192','Detergente Líquido Dersa 1L',3,1950.0000,1400.0000,1,8,'Producto de prueba'),
(20,'Unid','7441000000208','Desinfectante Fabuloso 828ml',3,1450.0000,1000.0000,1,10,'Producto de prueba'),
(21,'Unid','7441000000215','Martillo 16oz Truper',6,4200.0000,3000.0000,1,5,'Producto de prueba'),
(22,'Unid','7441000000222','Cinta Aislante 3M',6,650.0000,420.0000,1,10,'Producto de prueba'),
(23,'Unid','7441000000239','Bombillo LED 9W',6,1350.0000,900.0000,1,10,'Producto de prueba'),
(24,'Unid','7441000000246','Pilas AA Duracell x2',6,1200.0000,800.0000,1,15,'Producto de prueba');

INSERT INTO `tec_product_store_qty` (`product_id`,`store_id`,`quantity`) VALUES
(1,1,45),
(2,1,38),
(3,1,60),
(4,1,120),
(5,1,22),
(6,1,15),
(7,1,3),
(8,1,0),
(9,1,27),
(10,1,80),
(11,1,40),
(12,1,2),
(13,1,33),
(14,1,18),
(15,1,25),
(16,1,0),
(17,1,55),
(18,1,70),
(19,1,12),
(20,1,4),
(21,1,8),
(22,1,30),
(23,1,16),
(24,1,42);

INSERT INTO `tec_customers` (`id`,`name`,`cf1`,`cf2`,`limitcredit`) VALUES
(1,'Cliente de Contado',NULL,NULL,0);

INSERT INTO `tec_customers` (`id`,`name`,`business_name`,`email`,`phone`,`cf1`,`cf2`,`limitcredit`,`codigo_actividad`) VALUES
(2,'Juan Pérez Mora',NULL,'juan.perez@example.com','8888-1201','01','110100001',0,NULL),
(3,'María José Rodríguez Vargas',NULL,'mjrodriguez@example.com','8888-1202','01','110100002',0,NULL),
(4,'Carlos Andrés Solís Vindas',NULL,'csolis@example.com','8888-1203','01','110100003',0,NULL),
(5,'Ana Lucía Chacón Mora',NULL,'ana.chacon@example.com','8888-1204','01','110100004',0,NULL),
(6,'Luis Fernando Araya Ugalde',NULL,'luis.araya@example.com','8888-1205','01','110100005',0,NULL),
(7,'Comercial La Económica S.A.','Comercial La Económica S.A.','compras@laeconomica.example.com','2233-4455','02','3101987654',50000,'522010'),
(8,'Distribuidora del Valle Ltda.','Distribuidora del Valle Ltda.','ventas@delvalle.example.com','2233-4456','02','3101987655',50000,'522010'),
(9,'Super Ahorro Barrio Escalante S.A.','Super Ahorro Barrio Escalante S.A.','info@superahorro.example.com','2233-4457','02','3101987656',50000,'522010'),
(10,'Ferretería Central de Cartago S.A.','Ferretería Central de Cartago S.A.','contacto@ferrecentral.example.com','2233-4458','02','3101987657',50000,'522010'),
(11,'Rodrigo Emilio Jiménez Solano',NULL,'rjimenez@example.com','8888-1206','01','110100006',0,NULL),
(12,'Katherine Vanessa Brenes Alfaro',NULL,'kbrenes@example.com','8888-1207','01','110100007',0,NULL),
(13,'Panadería y Repostería Dulce Hogar S.A.','Panadería y Repostería Dulce Hogar S.A.','pedidos@dulcehogar.example.com','2233-4459','02','3101987658',50000,'522010'),
(14,'Esteban Gerardo Mata Salas',NULL,'emata@example.com','8888-1208','01','110100008',0,NULL),
(15,'Farmacia San Rafael S.A.','Farmacia San Rafael S.A.','compras@farmasanrafael.example.com','2233-4460','02','3101987659',50000,'522010'),
(16,'Priscilla Andrea Vega Chaves',NULL,'pvega@example.com','8888-1209','01','110100009',0,NULL);

INSERT INTO `tec_suppliers` (`id`,`name`,`company`,`email`,`phone`,`direccion`,`actividad_economica`) VALUES
(1,'Distribuidora Central','Distribuidora Central S.A.','ventas@distribuidoracentral.example.com','2244-5566','San José, Costa Rica','461100'),
(2,'Mayoreo del Este','Mayoreo del Este S.A.','pedidos@mayoreoeste.example.com','2244-5577','San José, Costa Rica','461100'),
(3,'Importadora Nacional','Importadora Nacional Ltda.','compras@impnacional.example.com','2244-5588','San José, Costa Rica','461100');

INSERT INTO `tec_actividadeconomica` (`id_actividad`,`codigo`,`descripcion`) VALUES
(1,'522010','Venta al por menor de productos diversos (comercio minorista)'),
(2,'620000','Programación, consultoría y otras actividades de informática');

INSERT INTO `tec_shipping_method` (`id_shipping_method`,`name`) VALUES
(1,'Retiro en tienda'),
(2,'Envío a domicilio');

INSERT INTO `tec_registers` (`id`,`store_id`,`name`,`opened`,`closed`,`status`,`created_by`) VALUES
(1,1,'Caja 1','2025-08-01 08:00:00','2025-08-31 20:00:00','closed',1),
(2,1,'Caja 1','2025-09-01 08:00:00','2025-09-30 20:00:00','closed',1),
(3,1,'Caja 1','2025-10-01 08:00:00','2025-10-31 20:00:00','closed',1),
(4,1,'Caja 1','2025-11-01 08:00:00','2025-11-30 20:00:00','closed',1),
(5,1,'Caja 1','2025-12-01 08:00:00','2025-12-31 20:00:00','closed',1),
(6,1,'Caja 1','2026-01-01 08:00:00','2026-01-31 20:00:00','closed',1),
(7,1,'Caja 1','2026-02-01 08:00:00','2026-02-28 20:00:00','closed',1),
(8,1,'Caja 1','2026-03-01 08:00:00','2026-03-31 20:00:00','closed',1),
(9,1,'Caja 1','2026-04-01 08:00:00','2026-04-30 20:00:00','closed',1),
(10,1,'Caja 1','2026-05-01 08:00:00','2026-05-31 20:00:00','closed',1),
(11,1,'Caja 1','2026-06-01 08:00:00','2026-06-30 20:00:00','closed',1),
(12,1,'Caja 1','2026-07-04 08:00:00',NULL,'open',1);

-- 131 ventas generadas (12 meses, 2025-08-01 a 2026-07-04)
INSERT INTO `tec_sales` (`id`,`date`,`customer_id`,`customer_name`,`register_id`,`created_by`,`store_id`,`status`,`payment_status`,`paid`,`due`,`total`,`total_tax`,`grand_total`,`tipo_doc`,`is_return`) VALUES
(1,'2025-08-11 12:47:00',3,'María José Rodríguez Vargas',1,1,1,'partial','partial',4658.0000,2192.0000,6850.0000,788.0531,6850.0000,'01',0),
(2,'2025-08-22 12:52:00',1,'Cliente de Contado',1,1,1,'paid','paid',7100.0000,0.0000,7100.0000,816.8142,7100.0000,'04',0),
(3,'2025-08-22 18:19:00',1,'Cliente de Contado',1,1,1,'paid','paid',2600.0000,0.0000,2600.0000,299.1150,2600.0000,'04',0),
(4,'2025-08-16 13:28:00',13,'Panadería y Repostería Dulce Hogar S.A.',1,1,1,'paid','paid',5800.0000,0.0000,5800.0000,667.2566,5800.0000,'01',0),
(5,'2025-08-20 18:59:00',15,'Farmacia San Rafael S.A.',1,1,1,'due','due',0.0000,12400.0000,12400.0000,1426.5487,12400.0000,'01',0),
(6,'2025-08-09 17:24:00',1,'Cliente de Contado',1,1,1,'paid','paid',7250.0000,0.0000,7250.0000,834.0708,7250.0000,'04',0),
(7,'2025-08-23 09:31:00',1,'Cliente de Contado',1,1,1,'due','due',0.0000,6600.0000,6600.0000,759.2920,6600.0000,'04',0),
(8,'2025-08-30 17:21:00',1,'Cliente de Contado',1,1,1,'partial','partial',4264.0000,6136.0000,10400.0000,1196.4602,10400.0000,'04',0),
(9,'2025-08-28 18:21:00',1,'Cliente de Contado',1,1,1,'paid','paid',3350.0000,0.0000,3350.0000,385.3982,3350.0000,'04',0),
(10,'2025-08-19 15:24:00',6,'Luis Fernando Araya Ugalde',1,1,1,'paid','paid',9050.0000,0.0000,9050.0000,1041.1504,9050.0000,'01',0),
(11,'2025-09-13 19:38:00',1,'Cliente de Contado',1,1,1,'partial','partial',2948.0000,1452.0000,4400.0000,506.1947,4400.0000,'04',0),
(12,'2025-09-16 17:54:00',2,'Juan Pérez Mora',1,1,1,'paid','paid',13650.0000,0.0000,13650.0000,1570.3540,13650.0000,'01',0),
(13,'2025-09-21 16:47:00',1,'Cliente de Contado',1,1,1,'paid','paid',11150.0000,0.0000,11150.0000,1282.7434,11150.0000,'04',0),
(14,'2025-09-01 14:27:00',2,'Juan Pérez Mora',1,1,1,'paid','paid',14050.0000,0.0000,14050.0000,1616.3717,14050.0000,'01',0),
(15,'2025-09-22 14:10:00',1,'Cliente de Contado',1,1,1,'paid','paid',15750.0000,0.0000,15750.0000,1811.9469,15750.0000,'04',0),
(16,'2025-09-18 08:14:00',3,'María José Rodríguez Vargas',1,1,1,'paid','paid',6450.0000,0.0000,6450.0000,742.0354,6450.0000,'01',0),
(17,'2025-09-14 08:50:00',1,'Cliente de Contado',1,1,1,'paid','paid',6450.0000,0.0000,6450.0000,742.0354,6450.0000,'04',0),
(18,'2025-09-29 19:31:00',5,'Ana Lucía Chacón Mora',1,1,1,'paid','paid',11350.0000,0.0000,11350.0000,1305.7522,11350.0000,'01',0),
(19,'2025-09-05 15:46:00',1,'Cliente de Contado',1,1,1,'paid','paid',5500.0000,0.0000,5500.0000,632.7434,5500.0000,'04',0),
(20,'2025-10-06 11:06:00',1,'Cliente de Contado',1,1,1,'paid','paid',11500.0000,0.0000,11500.0000,1323.0088,11500.0000,'04',0),
(21,'2025-10-23 14:14:00',1,'Cliente de Contado',1,1,1,'paid','paid',9200.0000,0.0000,9200.0000,1058.4071,9200.0000,'04',0),
(22,'2025-10-23 17:06:00',10,'Ferretería Central de Cartago S.A.',1,1,1,'paid','paid',1700.0000,0.0000,1700.0000,195.5752,1700.0000,'01',0),
(23,'2025-10-19 10:21:00',11,'Rodrigo Emilio Jiménez Solano',1,1,1,'paid','paid',2200.0000,0.0000,2200.0000,253.0973,2200.0000,'01',0),
(24,'2025-10-19 18:03:00',13,'Panadería y Repostería Dulce Hogar S.A.',1,1,1,'paid','paid',4650.0000,0.0000,4650.0000,534.9558,4650.0000,'01',0),
(25,'2025-10-08 14:49:00',11,'Rodrigo Emilio Jiménez Solano',1,1,1,'paid','paid',7850.0000,0.0000,7850.0000,903.0973,7850.0000,'01',0),
(26,'2025-10-27 17:48:00',7,'Comercial La Económica S.A.',1,1,1,'due','due',0.0000,19950.0000,19950.0000,2295.1327,19950.0000,'01',0),
(27,'2025-10-01 18:44:00',1,'Cliente de Contado',1,1,1,'due','due',0.0000,9000.0000,9000.0000,1035.3982,9000.0000,'04',0),
(28,'2025-10-16 18:56:00',14,'Esteban Gerardo Mata Salas',1,1,1,'partial','partial',6864.0000,8736.0000,15600.0000,1794.6903,15600.0000,'01',0),
(29,'2025-10-26 20:33:00',1,'Cliente de Contado',1,1,1,'paid','paid',7950.0000,0.0000,7950.0000,914.6018,7950.0000,'04',0),
(30,'2025-11-22 10:02:00',1,'Cliente de Contado',1,1,1,'paid','paid',1450.0000,0.0000,1450.0000,166.8142,1450.0000,'04',0),
(31,'2025-11-17 13:08:00',5,'Ana Lucía Chacón Mora',1,1,1,'paid','paid',5800.0000,0.0000,5800.0000,667.2566,5800.0000,'01',0),
(32,'2025-11-05 10:46:00',1,'Cliente de Contado',1,1,1,'paid','paid',9450.0000,0.0000,9450.0000,1087.1681,9450.0000,'04',0),
(33,'2025-11-15 19:05:00',9,'Super Ahorro Barrio Escalante S.A.',1,1,1,'paid','paid',2350.0000,0.0000,2350.0000,270.3540,2350.0000,'01',0),
(34,'2025-11-25 17:10:00',1,'Cliente de Contado',1,1,1,'partial','partial',3652.0000,4648.0000,8300.0000,954.8673,8300.0000,'04',0),
(35,'2025-11-07 08:45:00',3,'María José Rodríguez Vargas',1,1,1,'paid','paid',4200.0000,0.0000,4200.0000,483.1858,4200.0000,'01',0),
(36,'2025-11-28 19:01:00',9,'Super Ahorro Barrio Escalante S.A.',1,1,1,'paid','paid',2650.0000,0.0000,2650.0000,304.8673,2650.0000,'01',0),
(37,'2025-11-09 19:23:00',8,'Distribuidora del Valle Ltda.',1,1,1,'paid','paid',1950.0000,0.0000,1950.0000,224.3363,1950.0000,'01',0),
(38,'2025-11-26 16:58:00',13,'Panadería y Repostería Dulce Hogar S.A.',1,1,1,'paid','paid',2200.0000,0.0000,2200.0000,253.0973,2200.0000,'01',0),
(39,'2025-11-27 17:49:00',1,'Cliente de Contado',1,1,1,'partial','partial',2840.0000,1160.0000,4000.0000,460.1770,4000.0000,'04',0),
(40,'2025-12-15 15:22:00',1,'Cliente de Contado',1,1,1,'paid','paid',23150.0000,0.0000,23150.0000,2663.2743,23150.0000,'04',0),
(41,'2025-12-13 10:43:00',12,'Katherine Vanessa Brenes Alfaro',1,1,1,'paid','paid',11300.0000,0.0000,11300.0000,1300.0000,11300.0000,'01',0),
(42,'2025-12-05 19:28:00',1,'Cliente de Contado',1,1,1,'paid','paid',7900.0000,0.0000,7900.0000,908.8496,7900.0000,'04',0),
(43,'2025-12-03 14:54:00',1,'Cliente de Contado',1,1,1,'paid','paid',5400.0000,0.0000,5400.0000,621.2389,5400.0000,'04',0),
(44,'2025-12-12 16:06:00',1,'Cliente de Contado',1,1,1,'paid','paid',9900.0000,0.0000,9900.0000,1138.9381,9900.0000,'04',0),
(45,'2025-12-05 10:30:00',15,'Farmacia San Rafael S.A.',1,1,1,'paid','paid',10900.0000,0.0000,10900.0000,1253.9823,10900.0000,'01',0),
(46,'2025-12-25 17:58:00',1,'Cliente de Contado',1,1,1,'paid','paid',9550.0000,0.0000,9550.0000,1098.6726,9550.0000,'04',0),
(47,'2025-12-10 13:36:00',11,'Rodrigo Emilio Jiménez Solano',1,1,1,'paid','paid',4900.0000,0.0000,4900.0000,563.7168,4900.0000,'01',0),
(48,'2025-12-07 12:21:00',6,'Luis Fernando Araya Ugalde',1,1,1,'paid','paid',7250.0000,0.0000,7250.0000,834.0708,7250.0000,'01',0),
(49,'2025-12-08 13:48:00',1,'Cliente de Contado',1,1,1,'paid','paid',9500.0000,0.0000,9500.0000,1092.9204,9500.0000,'04',0),
(50,'2025-12-23 17:43:00',16,'Priscilla Andrea Vega Chaves',1,1,1,'due','due',0.0000,23050.0000,23050.0000,2651.7699,23050.0000,'01',0),
(51,'2026-01-13 17:37:00',2,'Juan Pérez Mora',1,1,1,'paid','paid',8850.0000,0.0000,8850.0000,1018.1416,8850.0000,'01',0),
(52,'2026-01-15 17:56:00',9,'Super Ahorro Barrio Escalante S.A.',1,1,1,'paid','paid',18650.0000,0.0000,18650.0000,2145.5752,18650.0000,'01',0),
(53,'2026-01-17 15:04:00',16,'Priscilla Andrea Vega Chaves',1,1,1,'paid','paid',19700.0000,0.0000,19700.0000,2266.3717,19700.0000,'01',0),
(54,'2026-01-18 14:54:00',1,'Cliente de Contado',1,1,1,'paid','paid',4350.0000,0.0000,4350.0000,500.4425,4350.0000,'04',0),
(55,'2026-01-29 10:38:00',9,'Super Ahorro Barrio Escalante S.A.',1,1,1,'paid','paid',8550.0000,0.0000,8550.0000,983.6283,8550.0000,'01',0),
(56,'2026-01-07 20:46:00',1,'Cliente de Contado',1,1,1,'paid','paid',14050.0000,0.0000,14050.0000,1616.3717,14050.0000,'04',0),
(57,'2026-01-14 20:00:00',14,'Esteban Gerardo Mata Salas',1,1,1,'paid','paid',11950.0000,0.0000,11950.0000,1374.7788,11950.0000,'01',0),
(58,'2026-01-12 10:25:00',3,'María José Rodríguez Vargas',1,1,1,'paid','paid',5700.0000,0.0000,5700.0000,655.7522,5700.0000,'01',0),
(59,'2026-01-02 10:49:00',11,'Rodrigo Emilio Jiménez Solano',1,1,1,'paid','paid',7050.0000,0.0000,7050.0000,811.0619,7050.0000,'01',0),
(60,'2026-02-16 13:14:00',1,'Cliente de Contado',1,1,1,'paid','paid',5400.0000,0.0000,5400.0000,621.2389,5400.0000,'04',0),
(61,'2026-02-14 20:19:00',5,'Ana Lucía Chacón Mora',1,1,1,'paid','paid',4450.0000,0.0000,4450.0000,511.9469,4450.0000,'01',0),
(62,'2026-02-09 13:32:00',1,'Cliente de Contado',1,1,1,'partial','partial',6984.0000,7566.0000,14550.0000,1673.8938,14550.0000,'04',0),
(63,'2026-02-28 17:22:00',5,'Ana Lucía Chacón Mora',1,1,1,'paid','paid',5100.0000,0.0000,5100.0000,586.7257,5100.0000,'01',0),
(64,'2026-02-23 11:00:00',1,'Cliente de Contado',1,1,1,'paid','paid',7250.0000,0.0000,7250.0000,834.0708,7250.0000,'04',0),
(65,'2026-02-05 14:51:00',13,'Panadería y Repostería Dulce Hogar S.A.',1,1,1,'paid','paid',2000.0000,0.0000,2000.0000,230.0885,2000.0000,'01',0),
(66,'2026-02-06 08:34:00',1,'Cliente de Contado',1,1,1,'paid','paid',1800.0000,0.0000,1800.0000,207.0796,1800.0000,'04',0),
(67,'2026-02-12 12:58:00',1,'Cliente de Contado',1,1,1,'paid','paid',7000.0000,0.0000,7000.0000,805.3097,7000.0000,'04',0),
(68,'2026-02-22 20:21:00',1,'Cliente de Contado',1,1,1,'paid','paid',6050.0000,0.0000,6050.0000,696.0177,6050.0000,'04',0),
(69,'2026-02-14 09:32:00',9,'Super Ahorro Barrio Escalante S.A.',1,1,1,'paid','paid',12750.0000,0.0000,12750.0000,1466.8142,12750.0000,'01',0),
(70,'2026-02-17 14:49:00',10,'Ferretería Central de Cartago S.A.',1,1,1,'paid','paid',18800.0000,0.0000,18800.0000,2162.8319,18800.0000,'01',0),
(71,'2026-02-24 12:55:00',1,'Cliente de Contado',1,1,1,'paid','paid',11000.0000,0.0000,11000.0000,1265.4867,11000.0000,'04',0),
(72,'2026-02-05 11:11:00',5,'Ana Lucía Chacón Mora',1,1,1,'paid','paid',18650.0000,0.0000,18650.0000,2145.5752,18650.0000,'01',0),
(73,'2026-02-05 10:33:00',4,'Carlos Andrés Solís Vindas',1,1,1,'paid','paid',4200.0000,0.0000,4200.0000,483.1858,4200.0000,'01',0),
(74,'2026-03-20 17:12:00',1,'Cliente de Contado',1,1,1,'paid','paid',10100.0000,0.0000,10100.0000,1161.9469,10100.0000,'04',0),
(75,'2026-03-27 19:26:00',1,'Cliente de Contado',1,1,1,'paid','paid',6300.0000,0.0000,6300.0000,724.7788,6300.0000,'04',0),
(76,'2026-03-12 08:51:00',1,'Cliente de Contado',1,1,1,'paid','paid',2100.0000,0.0000,2100.0000,241.5929,2100.0000,'04',0),
(77,'2026-03-25 18:42:00',1,'Cliente de Contado',1,1,1,'paid','paid',8800.0000,0.0000,8800.0000,1012.3894,8800.0000,'04',0),
(78,'2026-03-03 14:42:00',1,'Cliente de Contado',1,1,1,'partial','partial',5238.0000,4462.0000,9700.0000,1115.9292,9700.0000,'04',0),
(79,'2026-03-01 19:31:00',5,'Ana Lucía Chacón Mora',1,1,1,'paid','paid',9150.0000,0.0000,9150.0000,1052.6549,9150.0000,'01',0),
(80,'2026-03-20 19:39:00',10,'Ferretería Central de Cartago S.A.',1,1,1,'paid','paid',2700.0000,0.0000,2700.0000,310.6195,2700.0000,'01',0),
(81,'2026-03-05 12:52:00',5,'Ana Lucía Chacón Mora',1,1,1,'paid','paid',17050.0000,0.0000,17050.0000,1961.5044,17050.0000,'01',0),
(82,'2026-03-08 18:31:00',14,'Esteban Gerardo Mata Salas',1,1,1,'paid','paid',12600.0000,0.0000,12600.0000,1449.5575,12600.0000,'01',0),
(83,'2026-03-14 13:31:00',1,'Cliente de Contado',1,1,1,'paid','paid',13550.0000,0.0000,13550.0000,1558.8496,13550.0000,'04',0),
(84,'2026-03-20 08:41:00',1,'Cliente de Contado',1,1,1,'paid','paid',9050.0000,0.0000,9050.0000,1041.1504,9050.0000,'04',0),
(85,'2026-03-22 12:04:00',1,'Cliente de Contado',1,1,1,'paid','paid',13800.0000,0.0000,13800.0000,1587.6106,13800.0000,'04',0),
(86,'2026-03-20 15:17:00',2,'Juan Pérez Mora',1,1,1,'paid','paid',13900.0000,0.0000,13900.0000,1599.1150,13900.0000,'01',0),
(87,'2026-04-22 16:21:00',1,'Cliente de Contado',1,1,1,'partial','partial',4347.0000,3703.0000,8050.0000,926.1062,8050.0000,'04',0),
(88,'2026-04-23 15:55:00',1,'Cliente de Contado',1,1,1,'partial','partial',6412.5000,2137.5000,8550.0000,983.6283,8550.0000,'04',0),
(89,'2026-04-05 16:28:00',7,'Comercial La Económica S.A.',1,1,1,'paid','paid',15550.0000,0.0000,15550.0000,1788.9381,15550.0000,'01',0),
(90,'2026-04-17 12:49:00',1,'Cliente de Contado',1,1,1,'paid','paid',16800.0000,0.0000,16800.0000,1932.7434,16800.0000,'04',0),
(91,'2026-04-25 16:26:00',1,'Cliente de Contado',1,1,1,'paid','paid',19100.0000,0.0000,19100.0000,2197.3451,19100.0000,'04',0),
(92,'2026-04-10 17:55:00',8,'Distribuidora del Valle Ltda.',1,1,1,'paid','paid',1950.0000,0.0000,1950.0000,224.3363,1950.0000,'01',0),
(93,'2026-04-09 18:10:00',1,'Cliente de Contado',1,1,1,'partial','partial',5365.5000,1984.5000,7350.0000,845.5752,7350.0000,'04',0),
(94,'2026-04-17 20:14:00',1,'Cliente de Contado',1,1,1,'partial','partial',16021.0000,5629.0000,21650.0000,2490.7080,21650.0000,'04',0),
(95,'2026-04-26 16:25:00',1,'Cliente de Contado',1,1,1,'paid','paid',20600.0000,0.0000,20600.0000,2369.9115,20600.0000,'04',0),
(96,'2026-04-08 20:16:00',1,'Cliente de Contado',1,1,1,'paid','paid',5750.0000,0.0000,5750.0000,661.5044,5750.0000,'04',0),
(97,'2026-04-17 10:56:00',1,'Cliente de Contado',1,1,1,'paid','paid',23550.0000,0.0000,23550.0000,2709.2920,23550.0000,'04',0),
(98,'2026-04-24 16:26:00',1,'Cliente de Contado',1,1,1,'paid','paid',4400.0000,0.0000,4400.0000,506.1947,4400.0000,'04',0),
(99,'2026-04-26 19:34:00',1,'Cliente de Contado',1,1,1,'paid','paid',8300.0000,0.0000,8300.0000,954.8673,8300.0000,'04',0),
(100,'2026-05-23 09:51:00',1,'Cliente de Contado',1,1,1,'paid','paid',6350.0000,0.0000,6350.0000,730.5310,6350.0000,'04',0),
(101,'2026-05-25 15:01:00',8,'Distribuidora del Valle Ltda.',1,1,1,'partial','partial',2706.0000,3894.0000,6600.0000,759.2920,6600.0000,'01',0),
(102,'2026-05-02 10:12:00',1,'Cliente de Contado',1,1,1,'paid','paid',14450.0000,0.0000,14450.0000,1662.3894,14450.0000,'04',0),
(103,'2026-05-29 08:16:00',1,'Cliente de Contado',1,1,1,'partial','partial',1799.5000,1150.5000,2950.0000,339.3805,2950.0000,'04',0),
(104,'2026-05-10 20:23:00',1,'Cliente de Contado',1,1,1,'paid','paid',5850.0000,0.0000,5850.0000,673.0088,5850.0000,'04',0),
(105,'2026-05-05 13:15:00',8,'Distribuidora del Valle Ltda.',1,1,1,'due','due',0.0000,8500.0000,8500.0000,977.8761,8500.0000,'01',0),
(106,'2026-05-16 18:02:00',15,'Farmacia San Rafael S.A.',1,1,1,'paid','paid',13800.0000,0.0000,13800.0000,1587.6106,13800.0000,'01',0),
(107,'2026-05-16 13:47:00',1,'Cliente de Contado',1,1,1,'paid','paid',11100.0000,0.0000,11100.0000,1276.9912,11100.0000,'04',0),
(108,'2026-05-13 16:46:00',1,'Cliente de Contado',1,1,1,'paid','paid',8000.0000,0.0000,8000.0000,920.3540,8000.0000,'04',0),
(109,'2026-05-14 16:57:00',14,'Esteban Gerardo Mata Salas',1,1,1,'paid','paid',6150.0000,0.0000,6150.0000,707.5221,6150.0000,'01',0),
(110,'2026-05-09 11:17:00',5,'Ana Lucía Chacón Mora',1,1,1,'paid','paid',15250.0000,0.0000,15250.0000,1754.4248,15250.0000,'01',0),
(111,'2026-05-15 09:16:00',1,'Cliente de Contado',1,1,1,'paid','paid',6450.0000,0.0000,6450.0000,742.0354,6450.0000,'04',0),
(112,'2026-05-31 19:17:00',8,'Distribuidora del Valle Ltda.',1,1,1,'paid','paid',4700.0000,0.0000,4700.0000,540.7080,4700.0000,'01',0),
(113,'2026-06-08 09:44:00',6,'Luis Fernando Araya Ugalde',1,1,1,'paid','paid',16450.0000,0.0000,16450.0000,1892.4779,16450.0000,'01',0),
(114,'2026-06-09 08:06:00',9,'Super Ahorro Barrio Escalante S.A.',1,1,1,'paid','paid',2600.0000,0.0000,2600.0000,299.1150,2600.0000,'01',0),
(115,'2026-06-25 16:34:00',13,'Panadería y Repostería Dulce Hogar S.A.',1,1,1,'partial','partial',3233.0000,2067.0000,5300.0000,609.7345,5300.0000,'01',0),
(116,'2026-06-02 16:54:00',1,'Cliente de Contado',1,1,1,'paid','paid',1450.0000,0.0000,1450.0000,166.8142,1450.0000,'04',0),
(117,'2026-06-06 15:18:00',7,'Comercial La Económica S.A.',1,1,1,'paid','paid',4150.0000,0.0000,4150.0000,477.4336,4150.0000,'01',0),
(118,'2026-06-27 15:52:00',1,'Cliente de Contado',1,1,1,'paid','paid',12000.0000,0.0000,12000.0000,1380.5310,12000.0000,'04',0),
(119,'2026-06-18 13:28:00',15,'Farmacia San Rafael S.A.',1,1,1,'paid','paid',18450.0000,0.0000,18450.0000,2122.5664,18450.0000,'01',0),
(120,'2026-06-30 11:48:00',1,'Cliente de Contado',1,1,1,'paid','paid',14850.0000,0.0000,14850.0000,1708.4071,14850.0000,'04',0),
(121,'2026-06-20 10:36:00',12,'Katherine Vanessa Brenes Alfaro',1,1,1,'paid','paid',2750.0000,0.0000,2750.0000,316.3717,2750.0000,'01',0),
(122,'2026-06-01 19:35:00',1,'Cliente de Contado',1,1,1,'paid','paid',16900.0000,0.0000,16900.0000,1944.2478,16900.0000,'04',0),
(123,'2026-06-28 11:55:00',10,'Ferretería Central de Cartago S.A.',1,1,1,'paid','paid',14800.0000,0.0000,14800.0000,1702.6549,14800.0000,'01',1),
(124,'2026-06-06 12:45:00',1,'Cliente de Contado',1,1,1,'paid','paid',3600.0000,0.0000,3600.0000,414.1593,3600.0000,'04',0),
(125,'2026-06-11 18:51:00',15,'Farmacia San Rafael S.A.',1,1,1,'partial','partial',4751.5000,6298.5000,11050.0000,1271.2389,11050.0000,'01',0),
(126,'2026-06-07 19:21:00',1,'Cliente de Contado',1,1,1,'partial','partial',4141.0000,5959.0000,10100.0000,1161.9469,10100.0000,'04',0),
(127,'2026-06-14 15:06:00',8,'Distribuidora del Valle Ltda.',1,1,1,'paid','paid',19700.0000,0.0000,19700.0000,2266.3717,19700.0000,'01',0),
(128,'2026-07-01 18:31:00',3,'María José Rodríguez Vargas',1,1,1,'paid','paid',5050.0000,0.0000,5050.0000,580.9735,5050.0000,'01',0),
(129,'2026-07-04 18:51:00',12,'Katherine Vanessa Brenes Alfaro',1,1,1,'paid','paid',8400.0000,0.0000,8400.0000,966.3717,8400.0000,'01',0),
(130,'2026-07-03 08:05:00',8,'Distribuidora del Valle Ltda.',1,1,1,'paid','paid',9100.0000,0.0000,9100.0000,1046.9027,9100.0000,'01',0),
(131,'2026-07-01 08:31:00',12,'Katherine Vanessa Brenes Alfaro',1,1,1,'paid','paid',8800.0000,0.0000,8800.0000,1012.3894,8800.0000,'01',0);

INSERT INTO `tec_sale_items` (`sale_id`,`product_id`,`product_code`,`product_name`,`quantity`,`product_unit_price`,`product_tax`,`subtotal`) VALUES
(1,24,'7441000000246','Pilas AA Duracell x2',1,1200.0000,138.0531,1200.0000),
(1,17,'7441000000178','Galletas Club Social',2,750.0000,172.5664,1500.0000),
(1,7,'7441000000079','Azúcar Blanca 1kg CIA',3,900.0000,310.6195,2700.0000),
(1,20,'7441000000208','Desinfectante Fabuloso 828ml',1,1450.0000,166.8142,1450.0000),
(2,8,'7441000000086','Aceite Vegetal 900ml',2,2100.0000,483.1858,4200.0000),
(2,2,'7441000000024','Frijol Negro 900g',2,1450.0000,333.6283,2900.0000),
(3,22,'7441000000222','Cinta Aislante 3M',4,650.0000,299.1150,2600.0000),
(4,24,'7441000000246','Pilas AA Duracell x2',2,1200.0000,276.1062,2400.0000),
(4,17,'7441000000178','Galletas Club Social',2,750.0000,172.5664,1500.0000),
(4,5,'7441000000055','Cloro Cristal 1L',2,950.0000,218.5841,1900.0000),
(5,4,'7441000000048','Agua Cristal 600ml',4,650.0000,299.1150,2600.0000),
(5,18,'7441000000185','Chocolate Kit Kat',2,650.0000,149.5575,1300.0000),
(5,6,'7441000000062','Jabón en Polvo Xedex 1kg',3,2350.0000,811.0619,7050.0000),
(5,20,'7441000000208','Desinfectante Fabuloso 828ml',1,1450.0000,166.8142,1450.0000),
(6,14,'7441000000147','Shampoo Sedal 350ml',2,2650.0000,609.7345,5300.0000),
(6,22,'7441000000222','Cinta Aislante 3M',3,650.0000,224.3363,1950.0000),
(7,9,'7441000000093','Café 1874 250g',4,1650.0000,759.2920,6600.0000),
(8,24,'7441000000246','Pilas AA Duracell x2',2,1200.0000,276.1062,2400.0000),
(8,11,'7441000000116','Jugo Del Valle 1L',2,1100.0000,253.0973,2200.0000),
(8,12,'7441000000123','Leche Dos Pinos 1L',1,850.0000,97.7876,850.0000),
(8,9,'7441000000093','Café 1874 250g',3,1650.0000,569.4690,4950.0000),
(9,4,'7441000000048','Agua Cristal 600ml',1,650.0000,74.7788,650.0000),
(9,23,'7441000000239','Bombillo LED 9W',2,1350.0000,310.6195,2700.0000),
(10,24,'7441000000246','Pilas AA Duracell x2',1,1200.0000,138.0531,1200.0000),
(10,2,'7441000000024','Frijol Negro 900g',2,1450.0000,333.6283,2900.0000),
(10,9,'7441000000093','Café 1874 250g',3,1650.0000,569.4690,4950.0000),
(11,11,'7441000000116','Jugo Del Valle 1L',4,1100.0000,506.1947,4400.0000),
(12,16,'7441000000161','Papas Pringles 137g',4,2450.0000,1127.4336,9800.0000),
(12,11,'7441000000116','Jugo Del Valle 1L',1,1100.0000,126.5487,1100.0000),
(12,10,'7441000000109','Cerveza Imperial Lata',2,900.0000,207.0796,1800.0000),
(12,5,'7441000000055','Cloro Cristal 1L',1,950.0000,109.2920,950.0000),
(13,15,'7441000000154','Pasta Dental Colgate',4,1350.0000,621.2389,5400.0000),
(13,16,'7441000000161','Papas Pringles 137g',1,2450.0000,281.8584,2450.0000),
(13,11,'7441000000116','Jugo Del Valle 1L',3,1100.0000,379.6460,3300.0000),
(14,12,'7441000000123','Leche Dos Pinos 1L',2,850.0000,195.5752,1700.0000),
(14,23,'7441000000239','Bombillo LED 9W',1,1350.0000,155.3097,1350.0000),
(14,22,'7441000000222','Cinta Aislante 3M',4,650.0000,299.1150,2600.0000),
(14,21,'7441000000215','Martillo 16oz Truper',2,4200.0000,966.3717,8400.0000),
(15,24,'7441000000246','Pilas AA Duracell x2',1,1200.0000,138.0531,1200.0000),
(15,12,'7441000000123','Leche Dos Pinos 1L',2,850.0000,195.5752,1700.0000),
(15,13,'7441000000130','Papel Higiénico Scott x4',4,2200.0000,1012.3894,8800.0000),
(15,15,'7441000000154','Pasta Dental Colgate',3,1350.0000,465.9292,4050.0000),
(16,2,'7441000000024','Frijol Negro 900g',4,1450.0000,667.2566,5800.0000),
(16,18,'7441000000185','Chocolate Kit Kat',1,650.0000,74.7788,650.0000),
(17,2,'7441000000024','Frijol Negro 900g',2,1450.0000,333.6283,2900.0000),
(17,23,'7441000000239','Bombillo LED 9W',1,1350.0000,155.3097,1350.0000),
(17,11,'7441000000116','Jugo Del Valle 1L',2,1100.0000,253.0973,2200.0000),
(18,19,'7441000000192','Detergente Líquido Dersa 1L',2,1950.0000,448.6726,3900.0000),
(18,9,'7441000000093','Café 1874 250g',1,1650.0000,189.8230,1650.0000),
(18,16,'7441000000161','Papas Pringles 137g',2,2450.0000,563.7168,4900.0000),
(18,10,'7441000000109','Cerveza Imperial Lata',1,900.0000,103.5398,900.0000),
(19,2,'7441000000024','Frijol Negro 900g',2,1450.0000,333.6283,2900.0000),
(19,4,'7441000000048','Agua Cristal 600ml',4,650.0000,299.1150,2600.0000),
(20,8,'7441000000086','Aceite Vegetal 900ml',1,2100.0000,241.5929,2100.0000),
(20,6,'7441000000062','Jabón en Polvo Xedex 1kg',4,2350.0000,1081.4159,9400.0000),
(21,8,'7441000000086','Aceite Vegetal 900ml',3,2100.0000,724.7788,6300.0000),
(21,20,'7441000000208','Desinfectante Fabuloso 828ml',2,1450.0000,333.6283,2900.0000),
(22,12,'7441000000123','Leche Dos Pinos 1L',2,850.0000,195.5752,1700.0000),
(23,13,'7441000000130','Papel Higiénico Scott x4',1,2200.0000,253.0973,2200.0000),
(24,12,'7441000000123','Leche Dos Pinos 1L',3,850.0000,293.3628,2550.0000),
(24,8,'7441000000086','Aceite Vegetal 900ml',1,2100.0000,241.5929,2100.0000),
(25,5,'7441000000055','Cloro Cristal 1L',4,950.0000,437.1681,3800.0000),
(25,15,'7441000000154','Pasta Dental Colgate',3,1350.0000,465.9292,4050.0000),
(26,24,'7441000000246','Pilas AA Duracell x2',4,1200.0000,552.2124,4800.0000),
(26,7,'7441000000079','Azúcar Blanca 1kg CIA',4,900.0000,414.1593,3600.0000),
(26,2,'7441000000024','Frijol Negro 900g',3,1450.0000,500.4425,4350.0000),
(26,3,'7441000000031','Coca-Cola 2L',4,1800.0000,828.3186,7200.0000),
(27,1,'7441000000017','Arroz 1kg Tio Pelon',3,1250.0000,431.4159,3750.0000),
(27,22,'7441000000222','Cinta Aislante 3M',3,650.0000,224.3363,1950.0000),
(27,4,'7441000000048','Agua Cristal 600ml',3,650.0000,224.3363,1950.0000),
(27,23,'7441000000239','Bombillo LED 9W',1,1350.0000,155.3097,1350.0000),
(28,8,'7441000000086','Aceite Vegetal 900ml',1,2100.0000,241.5929,2100.0000),
(28,9,'7441000000093','Café 1874 250g',1,1650.0000,189.8230,1650.0000),
(28,23,'7441000000239','Bombillo LED 9W',3,1350.0000,465.9292,4050.0000),
(28,19,'7441000000192','Detergente Líquido Dersa 1L',4,1950.0000,897.3451,7800.0000),
(29,9,'7441000000093','Café 1874 250g',3,1650.0000,569.4690,4950.0000),
(29,17,'7441000000178','Galletas Club Social',4,750.0000,345.1327,3000.0000),
(30,20,'7441000000208','Desinfectante Fabuloso 828ml',1,1450.0000,166.8142,1450.0000),
(31,7,'7441000000079','Azúcar Blanca 1kg CIA',1,900.0000,103.5398,900.0000),
(31,16,'7441000000161','Papas Pringles 137g',2,2450.0000,563.7168,4900.0000),
(32,5,'7441000000055','Cloro Cristal 1L',4,950.0000,437.1681,3800.0000),
(32,22,'7441000000222','Cinta Aislante 3M',1,650.0000,74.7788,650.0000),
(32,1,'7441000000017','Arroz 1kg Tio Pelon',4,1250.0000,575.2212,5000.0000),
(33,6,'7441000000062','Jabón en Polvo Xedex 1kg',1,2350.0000,270.3540,2350.0000),
(34,13,'7441000000130','Papel Higiénico Scott x4',3,2200.0000,759.2920,6600.0000),
(34,12,'7441000000123','Leche Dos Pinos 1L',2,850.0000,195.5752,1700.0000),
(35,10,'7441000000109','Cerveza Imperial Lata',1,900.0000,103.5398,900.0000),
(35,11,'7441000000116','Jugo Del Valle 1L',3,1100.0000,379.6460,3300.0000),
(36,14,'7441000000147','Shampoo Sedal 350ml',1,2650.0000,304.8673,2650.0000),
(37,18,'7441000000185','Chocolate Kit Kat',3,650.0000,224.3363,1950.0000),
(38,11,'7441000000116','Jugo Del Valle 1L',2,1100.0000,253.0973,2200.0000),
(39,15,'7441000000154','Pasta Dental Colgate',2,1350.0000,310.6195,2700.0000),
(39,4,'7441000000048','Agua Cristal 600ml',2,650.0000,149.5575,1300.0000),
(40,22,'7441000000222','Cinta Aislante 3M',3,650.0000,224.3363,1950.0000),
(40,10,'7441000000109','Cerveza Imperial Lata',3,900.0000,310.6195,2700.0000),
(40,12,'7441000000123','Leche Dos Pinos 1L',2,850.0000,195.5752,1700.0000),
(40,21,'7441000000215','Martillo 16oz Truper',4,4200.0000,1932.7434,16800.0000),
(41,6,'7441000000062','Jabón en Polvo Xedex 1kg',2,2350.0000,540.7080,4700.0000),
(41,13,'7441000000130','Papel Higiénico Scott x4',1,2200.0000,253.0973,2200.0000),
(41,11,'7441000000116','Jugo Del Valle 1L',4,1100.0000,506.1947,4400.0000),
(42,22,'7441000000222','Cinta Aislante 3M',2,650.0000,149.5575,1300.0000),
(42,9,'7441000000093','Café 1874 250g',4,1650.0000,759.2920,6600.0000),
(43,15,'7441000000154','Pasta Dental Colgate',4,1350.0000,621.2389,5400.0000),
(44,24,'7441000000246','Pilas AA Duracell x2',3,1200.0000,414.1593,3600.0000),
(44,8,'7441000000086','Aceite Vegetal 900ml',3,2100.0000,724.7788,6300.0000),
(45,7,'7441000000079','Azúcar Blanca 1kg CIA',4,900.0000,414.1593,3600.0000),
(45,22,'7441000000222','Cinta Aislante 3M',4,650.0000,299.1150,2600.0000),
(45,6,'7441000000062','Jabón en Polvo Xedex 1kg',2,2350.0000,540.7080,4700.0000),
(46,7,'7441000000079','Azúcar Blanca 1kg CIA',3,900.0000,310.6195,2700.0000),
(46,15,'7441000000154','Pasta Dental Colgate',3,1350.0000,465.9292,4050.0000),
(46,10,'7441000000109','Cerveza Imperial Lata',1,900.0000,103.5398,900.0000),
(46,5,'7441000000055','Cloro Cristal 1L',2,950.0000,218.5841,1900.0000),
(47,4,'7441000000048','Agua Cristal 600ml',2,650.0000,149.5575,1300.0000),
(47,23,'7441000000239','Bombillo LED 9W',1,1350.0000,155.3097,1350.0000),
(47,15,'7441000000154','Pasta Dental Colgate',1,1350.0000,155.3097,1350.0000),
(47,10,'7441000000109','Cerveza Imperial Lata',1,900.0000,103.5398,900.0000),
(48,10,'7441000000109','Cerveza Imperial Lata',4,900.0000,414.1593,3600.0000),
(48,5,'7441000000055','Cloro Cristal 1L',1,950.0000,109.2920,950.0000),
(48,15,'7441000000154','Pasta Dental Colgate',2,1350.0000,310.6195,2700.0000),
(49,8,'7441000000086','Aceite Vegetal 900ml',2,2100.0000,483.1858,4200.0000),
(49,14,'7441000000147','Shampoo Sedal 350ml',2,2650.0000,609.7345,5300.0000),
(50,21,'7441000000215','Martillo 16oz Truper',2,4200.0000,966.3717,8400.0000),
(50,13,'7441000000130','Papel Higiénico Scott x4',4,2200.0000,1012.3894,8800.0000),
(50,19,'7441000000192','Detergente Líquido Dersa 1L',3,1950.0000,673.0088,5850.0000),
(51,15,'7441000000154','Pasta Dental Colgate',1,1350.0000,155.3097,1350.0000),
(51,9,'7441000000093','Café 1874 250g',2,1650.0000,379.6460,3300.0000),
(51,20,'7441000000208','Desinfectante Fabuloso 828ml',2,1450.0000,333.6283,2900.0000),
(51,18,'7441000000185','Chocolate Kit Kat',2,650.0000,149.5575,1300.0000),
(52,24,'7441000000246','Pilas AA Duracell x2',1,1200.0000,138.0531,1200.0000),
(52,13,'7441000000130','Papel Higiénico Scott x4',4,2200.0000,1012.3894,8800.0000),
(52,3,'7441000000031','Coca-Cola 2L',4,1800.0000,828.3186,7200.0000),
(52,20,'7441000000208','Desinfectante Fabuloso 828ml',1,1450.0000,166.8142,1450.0000),
(53,14,'7441000000147','Shampoo Sedal 350ml',2,2650.0000,609.7345,5300.0000),
(53,21,'7441000000215','Martillo 16oz Truper',2,4200.0000,966.3717,8400.0000),
(53,15,'7441000000154','Pasta Dental Colgate',3,1350.0000,465.9292,4050.0000),
(53,22,'7441000000222','Cinta Aislante 3M',3,650.0000,224.3363,1950.0000),
(54,5,'7441000000055','Cloro Cristal 1L',3,950.0000,327.8761,2850.0000),
(54,17,'7441000000178','Galletas Club Social',2,750.0000,172.5664,1500.0000),
(55,10,'7441000000109','Cerveza Imperial Lata',3,900.0000,310.6195,2700.0000),
(55,17,'7441000000178','Galletas Club Social',2,750.0000,172.5664,1500.0000),
(55,20,'7441000000208','Desinfectante Fabuloso 828ml',3,1450.0000,500.4425,4350.0000),
(56,21,'7441000000215','Martillo 16oz Truper',3,4200.0000,1449.5575,12600.0000),
(56,20,'7441000000208','Desinfectante Fabuloso 828ml',1,1450.0000,166.8142,1450.0000),
(57,6,'7441000000062','Jabón en Polvo Xedex 1kg',4,2350.0000,1081.4159,9400.0000),
(57,12,'7441000000123','Leche Dos Pinos 1L',3,850.0000,293.3628,2550.0000),
(58,18,'7441000000185','Chocolate Kit Kat',3,650.0000,224.3363,1950.0000),
(58,24,'7441000000246','Pilas AA Duracell x2',1,1200.0000,138.0531,1200.0000),
(58,12,'7441000000123','Leche Dos Pinos 1L',3,850.0000,293.3628,2550.0000),
(59,6,'7441000000062','Jabón en Polvo Xedex 1kg',3,2350.0000,811.0619,7050.0000),
(60,24,'7441000000246','Pilas AA Duracell x2',3,1200.0000,414.1593,3600.0000),
(60,3,'7441000000031','Coca-Cola 2L',1,1800.0000,207.0796,1800.0000),
(61,17,'7441000000178','Galletas Club Social',3,750.0000,258.8496,2250.0000),
(61,13,'7441000000130','Papel Higiénico Scott x4',1,2200.0000,253.0973,2200.0000),
(62,13,'7441000000130','Papel Higiénico Scott x4',3,2200.0000,759.2920,6600.0000),
(62,14,'7441000000147','Shampoo Sedal 350ml',3,2650.0000,914.6018,7950.0000),
(63,10,'7441000000109','Cerveza Imperial Lata',1,900.0000,103.5398,900.0000),
(63,8,'7441000000086','Aceite Vegetal 900ml',2,2100.0000,483.1858,4200.0000),
(64,9,'7441000000093','Café 1874 250g',4,1650.0000,759.2920,6600.0000),
(64,18,'7441000000185','Chocolate Kit Kat',1,650.0000,74.7788,650.0000),
(65,11,'7441000000116','Jugo Del Valle 1L',1,1100.0000,126.5487,1100.0000),
(65,7,'7441000000079','Azúcar Blanca 1kg CIA',1,900.0000,103.5398,900.0000),
(66,7,'7441000000079','Azúcar Blanca 1kg CIA',2,900.0000,207.0796,1800.0000),
(67,12,'7441000000123','Leche Dos Pinos 1L',4,850.0000,391.1504,3400.0000),
(67,3,'7441000000031','Coca-Cola 2L',2,1800.0000,414.1593,3600.0000),
(68,22,'7441000000222','Cinta Aislante 3M',1,650.0000,74.7788,650.0000),
(68,5,'7441000000055','Cloro Cristal 1L',3,950.0000,327.8761,2850.0000),
(68,12,'7441000000123','Leche Dos Pinos 1L',3,850.0000,293.3628,2550.0000),
(69,14,'7441000000147','Shampoo Sedal 350ml',3,2650.0000,914.6018,7950.0000),
(69,24,'7441000000246','Pilas AA Duracell x2',4,1200.0000,552.2124,4800.0000),
(70,2,'7441000000024','Frijol Negro 900g',3,1450.0000,500.4425,4350.0000),
(70,14,'7441000000147','Shampoo Sedal 350ml',2,2650.0000,609.7345,5300.0000),
(70,3,'7441000000031','Coca-Cola 2L',1,1800.0000,207.0796,1800.0000),
(70,16,'7441000000161','Papas Pringles 137g',3,2450.0000,845.5752,7350.0000),
(71,16,'7441000000161','Papas Pringles 137g',2,2450.0000,563.7168,4900.0000),
(71,1,'7441000000017','Arroz 1kg Tio Pelon',2,1250.0000,287.6106,2500.0000),
(71,3,'7441000000031','Coca-Cola 2L',2,1800.0000,414.1593,3600.0000),
(72,23,'7441000000239','Bombillo LED 9W',3,1350.0000,465.9292,4050.0000),
(72,8,'7441000000086','Aceite Vegetal 900ml',4,2100.0000,966.3717,8400.0000),
(72,9,'7441000000093','Café 1874 250g',2,1650.0000,379.6460,3300.0000),
(72,20,'7441000000208','Desinfectante Fabuloso 828ml',2,1450.0000,333.6283,2900.0000),
(73,21,'7441000000215','Martillo 16oz Truper',1,4200.0000,483.1858,4200.0000),
(74,4,'7441000000048','Agua Cristal 600ml',4,650.0000,299.1150,2600.0000),
(74,19,'7441000000192','Detergente Líquido Dersa 1L',2,1950.0000,448.6726,3900.0000),
(74,10,'7441000000109','Cerveza Imperial Lata',4,900.0000,414.1593,3600.0000),
(75,10,'7441000000109','Cerveza Imperial Lata',2,900.0000,207.0796,1800.0000),
(75,11,'7441000000116','Jugo Del Valle 1L',2,1100.0000,253.0973,2200.0000),
(75,15,'7441000000154','Pasta Dental Colgate',1,1350.0000,155.3097,1350.0000),
(75,5,'7441000000055','Cloro Cristal 1L',1,950.0000,109.2920,950.0000),
(76,8,'7441000000086','Aceite Vegetal 900ml',1,2100.0000,241.5929,2100.0000),
(77,13,'7441000000130','Papel Higiénico Scott x4',4,2200.0000,1012.3894,8800.0000),
(78,11,'7441000000116','Jugo Del Valle 1L',4,1100.0000,506.1947,4400.0000),
(78,5,'7441000000055','Cloro Cristal 1L',2,950.0000,218.5841,1900.0000),
(78,12,'7441000000123','Leche Dos Pinos 1L',4,850.0000,391.1504,3400.0000),
(79,3,'7441000000031','Coca-Cola 2L',2,1800.0000,414.1593,3600.0000),
(79,10,'7441000000109','Cerveza Imperial Lata',3,900.0000,310.6195,2700.0000),
(79,9,'7441000000093','Café 1874 250g',1,1650.0000,189.8230,1650.0000),
(79,24,'7441000000246','Pilas AA Duracell x2',1,1200.0000,138.0531,1200.0000),
(80,3,'7441000000031','Coca-Cola 2L',1,1800.0000,207.0796,1800.0000),
(80,10,'7441000000109','Cerveza Imperial Lata',1,900.0000,103.5398,900.0000),
(81,14,'7441000000147','Shampoo Sedal 350ml',4,2650.0000,1219.4690,10600.0000),
(81,24,'7441000000246','Pilas AA Duracell x2',4,1200.0000,552.2124,4800.0000),
(81,9,'7441000000093','Café 1874 250g',1,1650.0000,189.8230,1650.0000),
(82,12,'7441000000123','Leche Dos Pinos 1L',4,850.0000,391.1504,3400.0000),
(82,24,'7441000000246','Pilas AA Duracell x2',2,1200.0000,276.1062,2400.0000),
(82,17,'7441000000178','Galletas Club Social',2,750.0000,172.5664,1500.0000),
(82,14,'7441000000147','Shampoo Sedal 350ml',2,2650.0000,609.7345,5300.0000),
(83,16,'7441000000161','Papas Pringles 137g',4,2450.0000,1127.4336,9800.0000),
(83,1,'7441000000017','Arroz 1kg Tio Pelon',3,1250.0000,431.4159,3750.0000),
(84,7,'7441000000079','Azúcar Blanca 1kg CIA',1,900.0000,103.5398,900.0000),
(84,5,'7441000000055','Cloro Cristal 1L',4,950.0000,437.1681,3800.0000),
(84,2,'7441000000024','Frijol Negro 900g',3,1450.0000,500.4425,4350.0000),
(85,11,'7441000000116','Jugo Del Valle 1L',4,1100.0000,506.1947,4400.0000),
(85,20,'7441000000208','Desinfectante Fabuloso 828ml',4,1450.0000,667.2566,5800.0000),
(85,10,'7441000000109','Cerveza Imperial Lata',4,900.0000,414.1593,3600.0000),
(86,10,'7441000000109','Cerveza Imperial Lata',3,900.0000,310.6195,2700.0000),
(86,16,'7441000000161','Papas Pringles 137g',3,2450.0000,845.5752,7350.0000),
(86,2,'7441000000024','Frijol Negro 900g',2,1450.0000,333.6283,2900.0000),
(86,5,'7441000000055','Cloro Cristal 1L',1,950.0000,109.2920,950.0000),
(87,14,'7441000000147','Shampoo Sedal 350ml',1,2650.0000,304.8673,2650.0000),
(87,3,'7441000000031','Coca-Cola 2L',3,1800.0000,621.2389,5400.0000),
(88,23,'7441000000239','Bombillo LED 9W',2,1350.0000,310.6195,2700.0000),
(88,17,'7441000000178','Galletas Club Social',3,750.0000,258.8496,2250.0000),
(88,7,'7441000000079','Azúcar Blanca 1kg CIA',4,900.0000,414.1593,3600.0000),
(89,23,'7441000000239','Bombillo LED 9W',2,1350.0000,310.6195,2700.0000),
(89,22,'7441000000222','Cinta Aislante 3M',1,650.0000,74.7788,650.0000),
(89,8,'7441000000086','Aceite Vegetal 900ml',4,2100.0000,966.3717,8400.0000),
(89,5,'7441000000055','Cloro Cristal 1L',4,950.0000,437.1681,3800.0000),
(90,21,'7441000000215','Martillo 16oz Truper',4,4200.0000,1932.7434,16800.0000),
(91,6,'7441000000062','Jabón en Polvo Xedex 1kg',3,2350.0000,811.0619,7050.0000),
(91,13,'7441000000130','Papel Higiénico Scott x4',2,2200.0000,506.1947,4400.0000),
(91,15,'7441000000154','Pasta Dental Colgate',3,1350.0000,465.9292,4050.0000),
(91,24,'7441000000246','Pilas AA Duracell x2',3,1200.0000,414.1593,3600.0000),
(92,4,'7441000000048','Agua Cristal 600ml',1,650.0000,74.7788,650.0000),
(92,22,'7441000000222','Cinta Aislante 3M',2,650.0000,149.5575,1300.0000),
(93,16,'7441000000161','Papas Pringles 137g',3,2450.0000,845.5752,7350.0000),
(94,2,'7441000000024','Frijol Negro 900g',4,1450.0000,667.2566,5800.0000),
(94,22,'7441000000222','Cinta Aislante 3M',3,650.0000,224.3363,1950.0000),
(94,18,'7441000000185','Chocolate Kit Kat',2,650.0000,149.5575,1300.0000),
(94,21,'7441000000215','Martillo 16oz Truper',3,4200.0000,1449.5575,12600.0000),
(95,5,'7441000000055','Cloro Cristal 1L',1,950.0000,109.2920,950.0000),
(95,3,'7441000000031','Coca-Cola 2L',3,1800.0000,621.2389,5400.0000),
(95,21,'7441000000215','Martillo 16oz Truper',3,4200.0000,1449.5575,12600.0000),
(95,9,'7441000000093','Café 1874 250g',1,1650.0000,189.8230,1650.0000),
(96,12,'7441000000123','Leche Dos Pinos 1L',3,850.0000,293.3628,2550.0000),
(96,4,'7441000000048','Agua Cristal 600ml',2,650.0000,149.5575,1300.0000),
(96,5,'7441000000055','Cloro Cristal 1L',2,950.0000,218.5841,1900.0000),
(97,12,'7441000000123','Leche Dos Pinos 1L',3,850.0000,293.3628,2550.0000),
(97,8,'7441000000086','Aceite Vegetal 900ml',4,2100.0000,966.3717,8400.0000),
(97,21,'7441000000215','Martillo 16oz Truper',3,4200.0000,1449.5575,12600.0000),
(98,11,'7441000000116','Jugo Del Valle 1L',4,1100.0000,506.1947,4400.0000),
(99,1,'7441000000017','Arroz 1kg Tio Pelon',4,1250.0000,575.2212,5000.0000),
(99,18,'7441000000185','Chocolate Kit Kat',3,650.0000,224.3363,1950.0000),
(99,15,'7441000000154','Pasta Dental Colgate',1,1350.0000,155.3097,1350.0000),
(100,9,'7441000000093','Café 1874 250g',1,1650.0000,189.8230,1650.0000),
(100,17,'7441000000178','Galletas Club Social',1,750.0000,86.2832,750.0000),
(100,4,'7441000000048','Agua Cristal 600ml',2,650.0000,149.5575,1300.0000),
(100,14,'7441000000147','Shampoo Sedal 350ml',1,2650.0000,304.8673,2650.0000),
(101,4,'7441000000048','Agua Cristal 600ml',2,650.0000,149.5575,1300.0000),
(101,18,'7441000000185','Chocolate Kit Kat',4,650.0000,299.1150,2600.0000),
(101,10,'7441000000109','Cerveza Imperial Lata',3,900.0000,310.6195,2700.0000),
(102,9,'7441000000093','Café 1874 250g',2,1650.0000,379.6460,3300.0000),
(102,14,'7441000000147','Shampoo Sedal 350ml',1,2650.0000,304.8673,2650.0000),
(102,7,'7441000000079','Azúcar Blanca 1kg CIA',3,900.0000,310.6195,2700.0000),
(102,20,'7441000000208','Desinfectante Fabuloso 828ml',4,1450.0000,667.2566,5800.0000),
(103,11,'7441000000116','Jugo Del Valle 1L',2,1100.0000,253.0973,2200.0000),
(103,17,'7441000000178','Galletas Club Social',1,750.0000,86.2832,750.0000),
(104,19,'7441000000192','Detergente Líquido Dersa 1L',3,1950.0000,673.0088,5850.0000),
(105,13,'7441000000130','Papel Higiénico Scott x4',3,2200.0000,759.2920,6600.0000),
(105,5,'7441000000055','Cloro Cristal 1L',2,950.0000,218.5841,1900.0000),
(106,13,'7441000000130','Papel Higiénico Scott x4',2,2200.0000,506.1947,4400.0000),
(106,6,'7441000000062','Jabón en Polvo Xedex 1kg',4,2350.0000,1081.4159,9400.0000),
(107,1,'7441000000017','Arroz 1kg Tio Pelon',3,1250.0000,431.4159,3750.0000),
(107,3,'7441000000031','Coca-Cola 2L',3,1800.0000,621.2389,5400.0000),
(107,22,'7441000000222','Cinta Aislante 3M',3,650.0000,224.3363,1950.0000),
(108,16,'7441000000161','Papas Pringles 137g',3,2450.0000,845.5752,7350.0000),
(108,4,'7441000000048','Agua Cristal 600ml',1,650.0000,74.7788,650.0000),
(109,8,'7441000000086','Aceite Vegetal 900ml',2,2100.0000,483.1858,4200.0000),
(109,19,'7441000000192','Detergente Líquido Dersa 1L',1,1950.0000,224.3363,1950.0000),
(110,7,'7441000000079','Azúcar Blanca 1kg CIA',3,900.0000,310.6195,2700.0000),
(110,4,'7441000000048','Agua Cristal 600ml',4,650.0000,299.1150,2600.0000),
(110,20,'7441000000208','Desinfectante Fabuloso 828ml',2,1450.0000,333.6283,2900.0000),
(110,6,'7441000000062','Jabón en Polvo Xedex 1kg',3,2350.0000,811.0619,7050.0000),
(111,17,'7441000000178','Galletas Club Social',2,750.0000,172.5664,1500.0000),
(111,15,'7441000000154','Pasta Dental Colgate',3,1350.0000,465.9292,4050.0000),
(111,7,'7441000000079','Azúcar Blanca 1kg CIA',1,900.0000,103.5398,900.0000),
(112,20,'7441000000208','Desinfectante Fabuloso 828ml',2,1450.0000,333.6283,2900.0000),
(112,10,'7441000000109','Cerveza Imperial Lata',2,900.0000,207.0796,1800.0000),
(113,1,'7441000000017','Arroz 1kg Tio Pelon',1,1250.0000,143.8053,1250.0000),
(113,22,'7441000000222','Cinta Aislante 3M',4,650.0000,299.1150,2600.0000),
(113,5,'7441000000055','Cloro Cristal 1L',4,950.0000,437.1681,3800.0000),
(113,13,'7441000000130','Papel Higiénico Scott x4',4,2200.0000,1012.3894,8800.0000),
(114,5,'7441000000055','Cloro Cristal 1L',1,950.0000,109.2920,950.0000),
(114,9,'7441000000093','Café 1874 250g',1,1650.0000,189.8230,1650.0000),
(115,4,'7441000000048','Agua Cristal 600ml',2,650.0000,149.5575,1300.0000),
(115,23,'7441000000239','Bombillo LED 9W',1,1350.0000,155.3097,1350.0000),
(115,14,'7441000000147','Shampoo Sedal 350ml',1,2650.0000,304.8673,2650.0000),
(116,2,'7441000000024','Frijol Negro 900g',1,1450.0000,166.8142,1450.0000),
(117,22,'7441000000222','Cinta Aislante 3M',3,650.0000,224.3363,1950.0000),
(117,13,'7441000000130','Papel Higiénico Scott x4',1,2200.0000,253.0973,2200.0000),
(118,18,'7441000000185','Chocolate Kit Kat',4,650.0000,299.1150,2600.0000),
(118,6,'7441000000062','Jabón en Polvo Xedex 1kg',4,2350.0000,1081.4159,9400.0000),
(119,6,'7441000000062','Jabón en Polvo Xedex 1kg',3,2350.0000,811.0619,7050.0000),
(119,16,'7441000000161','Papas Pringles 137g',3,2450.0000,845.5752,7350.0000),
(119,15,'7441000000154','Pasta Dental Colgate',3,1350.0000,465.9292,4050.0000),
(120,1,'7441000000017','Arroz 1kg Tio Pelon',2,1250.0000,287.6106,2500.0000),
(120,6,'7441000000062','Jabón en Polvo Xedex 1kg',3,2350.0000,811.0619,7050.0000),
(120,14,'7441000000147','Shampoo Sedal 350ml',2,2650.0000,609.7345,5300.0000),
(121,18,'7441000000185','Chocolate Kit Kat',2,650.0000,149.5575,1300.0000),
(121,2,'7441000000024','Frijol Negro 900g',1,1450.0000,166.8142,1450.0000),
(122,13,'7441000000130','Papel Higiénico Scott x4',4,2200.0000,1012.3894,8800.0000),
(122,19,'7441000000192','Detergente Líquido Dersa 1L',1,1950.0000,224.3363,1950.0000),
(122,6,'7441000000062','Jabón en Polvo Xedex 1kg',2,2350.0000,540.7080,4700.0000),
(122,20,'7441000000208','Desinfectante Fabuloso 828ml',1,1450.0000,166.8142,1450.0000),
(123,3,'7441000000031','Coca-Cola 2L',1,1800.0000,207.0796,1800.0000),
(123,8,'7441000000086','Aceite Vegetal 900ml',2,2100.0000,483.1858,4200.0000),
(123,13,'7441000000130','Papel Higiénico Scott x4',4,2200.0000,1012.3894,8800.0000),
(124,7,'7441000000079','Azúcar Blanca 1kg CIA',4,900.0000,414.1593,3600.0000),
(125,18,'7441000000185','Chocolate Kit Kat',1,650.0000,74.7788,650.0000),
(125,17,'7441000000178','Galletas Club Social',1,750.0000,86.2832,750.0000),
(125,21,'7441000000215','Martillo 16oz Truper',2,4200.0000,966.3717,8400.0000),
(125,1,'7441000000017','Arroz 1kg Tio Pelon',1,1250.0000,143.8053,1250.0000),
(126,6,'7441000000062','Jabón en Polvo Xedex 1kg',2,2350.0000,540.7080,4700.0000),
(126,3,'7441000000031','Coca-Cola 2L',3,1800.0000,621.2389,5400.0000),
(127,20,'7441000000208','Desinfectante Fabuloso 828ml',2,1450.0000,333.6283,2900.0000),
(127,21,'7441000000215','Martillo 16oz Truper',4,4200.0000,1932.7434,16800.0000),
(128,23,'7441000000239','Bombillo LED 9W',2,1350.0000,310.6195,2700.0000),
(128,6,'7441000000062','Jabón en Polvo Xedex 1kg',1,2350.0000,270.3540,2350.0000),
(129,15,'7441000000154','Pasta Dental Colgate',3,1350.0000,465.9292,4050.0000),
(129,23,'7441000000239','Bombillo LED 9W',1,1350.0000,155.3097,1350.0000),
(129,17,'7441000000178','Galletas Club Social',4,750.0000,345.1327,3000.0000),
(130,2,'7441000000024','Frijol Negro 900g',4,1450.0000,667.2566,5800.0000),
(130,9,'7441000000093','Café 1874 250g',2,1650.0000,379.6460,3300.0000),
(131,8,'7441000000086','Aceite Vegetal 900ml',2,2100.0000,483.1858,4200.0000),
(131,14,'7441000000147','Shampoo Sedal 350ml',1,2650.0000,304.8673,2650.0000),
(131,22,'7441000000222','Cinta Aislante 3M',3,650.0000,224.3363,1950.0000);

INSERT INTO `tec_payments` (`id`,`sale_id`,`date`,`amount`,`paid_by`,`customer_id`,`store_id`,`created_by`) VALUES
(1,1,'2025-08-11 12:47:00',4658.0000,'cash',3,1,1),
(2,2,'2025-08-22 12:52:00',7100.0000,'cash',1,1,1),
(3,3,'2025-08-22 18:19:00',2600.0000,'cash',1,1,1),
(4,4,'2025-08-16 13:28:00',5800.0000,'credit_card',13,1,1),
(5,6,'2025-08-09 17:24:00',7250.0000,'cash',1,1,1),
(6,8,'2025-08-30 17:21:00',4264.0000,'cheque',1,1,1),
(7,9,'2025-08-28 18:21:00',3350.0000,'cheque',1,1,1),
(8,10,'2025-08-19 15:24:00',9050.0000,'cheque',6,1,1),
(9,11,'2025-09-13 19:38:00',2948.0000,'cash',1,1,1),
(10,12,'2025-09-16 17:54:00',13650.0000,'sinpe',2,1,1),
(11,13,'2025-09-21 16:47:00',11150.0000,'cash',1,1,1),
(12,14,'2025-09-01 14:27:00',14050.0000,'cash',2,1,1),
(13,15,'2025-09-22 14:10:00',15750.0000,'cash',1,1,1),
(14,16,'2025-09-18 08:14:00',6450.0000,'cheque',3,1,1),
(15,17,'2025-09-14 08:50:00',6450.0000,'sinpe',1,1,1),
(16,18,'2025-09-29 19:31:00',11350.0000,'credit_card',5,1,1),
(17,19,'2025-09-05 15:46:00',5500.0000,'sinpe',1,1,1),
(18,20,'2025-10-06 11:06:00',11500.0000,'cash',1,1,1),
(19,21,'2025-10-23 14:14:00',9200.0000,'cash',1,1,1),
(20,22,'2025-10-23 17:06:00',1700.0000,'cash',10,1,1),
(21,23,'2025-10-19 10:21:00',2200.0000,'credit_card',11,1,1),
(22,24,'2025-10-19 18:03:00',4650.0000,'cash',13,1,1),
(23,25,'2025-10-08 14:49:00',7850.0000,'cash',11,1,1),
(24,28,'2025-10-16 18:56:00',6864.0000,'credit_card',14,1,1),
(25,29,'2025-10-26 20:33:00',7950.0000,'credit_card',1,1,1),
(26,30,'2025-11-22 10:02:00',1450.0000,'sinpe',1,1,1),
(27,31,'2025-11-17 13:08:00',5800.0000,'cash',5,1,1),
(28,32,'2025-11-05 10:46:00',9450.0000,'credit_card',1,1,1),
(29,33,'2025-11-15 19:05:00',2350.0000,'cash',9,1,1),
(30,34,'2025-11-25 17:10:00',3652.0000,'credit_card',1,1,1),
(31,35,'2025-11-07 08:45:00',4200.0000,'cash',3,1,1),
(32,36,'2025-11-28 19:01:00',2650.0000,'cash',9,1,1),
(33,37,'2025-11-09 19:23:00',1950.0000,'cash',8,1,1),
(34,38,'2025-11-26 16:58:00',2200.0000,'cash',13,1,1),
(35,39,'2025-11-27 17:49:00',2840.0000,'cheque',1,1,1),
(36,40,'2025-12-15 15:22:00',23150.0000,'credit_card',1,1,1),
(37,41,'2025-12-13 10:43:00',11300.0000,'cash',12,1,1),
(38,42,'2025-12-05 19:28:00',7900.0000,'credit_card',1,1,1),
(39,43,'2025-12-03 14:54:00',5400.0000,'sinpe',1,1,1),
(40,44,'2025-12-12 16:06:00',9900.0000,'sinpe',1,1,1),
(41,45,'2025-12-05 10:30:00',10900.0000,'cash',15,1,1),
(42,46,'2025-12-25 17:58:00',9550.0000,'cheque',1,1,1),
(43,47,'2025-12-10 13:36:00',4900.0000,'cheque',11,1,1),
(44,48,'2025-12-07 12:21:00',7250.0000,'sinpe',6,1,1),
(45,49,'2025-12-08 13:48:00',9500.0000,'cash',1,1,1),
(46,51,'2026-01-13 17:37:00',8850.0000,'credit_card',2,1,1),
(47,52,'2026-01-15 17:56:00',18650.0000,'sinpe',9,1,1),
(48,53,'2026-01-17 15:04:00',19700.0000,'credit_card',16,1,1),
(49,54,'2026-01-18 14:54:00',4350.0000,'credit_card',1,1,1),
(50,55,'2026-01-29 10:38:00',8550.0000,'credit_card',9,1,1),
(51,56,'2026-01-07 20:46:00',14050.0000,'cash',1,1,1),
(52,57,'2026-01-14 20:00:00',11950.0000,'cheque',14,1,1),
(53,58,'2026-01-12 10:25:00',5700.0000,'cash',3,1,1),
(54,59,'2026-01-02 10:49:00',7050.0000,'cash',11,1,1),
(55,60,'2026-02-16 13:14:00',5400.0000,'credit_card',1,1,1),
(56,61,'2026-02-14 20:19:00',4450.0000,'sinpe',5,1,1),
(57,62,'2026-02-09 13:32:00',6984.0000,'credit_card',1,1,1),
(58,63,'2026-02-28 17:22:00',5100.0000,'cheque',5,1,1),
(59,64,'2026-02-23 11:00:00',7250.0000,'credit_card',1,1,1),
(60,65,'2026-02-05 14:51:00',2000.0000,'cash',13,1,1),
(61,66,'2026-02-06 08:34:00',1800.0000,'sinpe',1,1,1),
(62,67,'2026-02-12 12:58:00',7000.0000,'cash',1,1,1),
(63,68,'2026-02-22 20:21:00',6050.0000,'sinpe',1,1,1),
(64,69,'2026-02-14 09:32:00',12750.0000,'cash',9,1,1),
(65,70,'2026-02-17 14:49:00',18800.0000,'cash',10,1,1),
(66,71,'2026-02-24 12:55:00',11000.0000,'sinpe',1,1,1),
(67,72,'2026-02-05 11:11:00',18650.0000,'cash',5,1,1),
(68,73,'2026-02-05 10:33:00',4200.0000,'cash',4,1,1),
(69,74,'2026-03-20 17:12:00',10100.0000,'cash',1,1,1),
(70,75,'2026-03-27 19:26:00',6300.0000,'credit_card',1,1,1),
(71,76,'2026-03-12 08:51:00',2100.0000,'cash',1,1,1),
(72,77,'2026-03-25 18:42:00',8800.0000,'sinpe',1,1,1),
(73,78,'2026-03-03 14:42:00',5238.0000,'sinpe',1,1,1),
(74,79,'2026-03-01 19:31:00',9150.0000,'cash',5,1,1),
(75,80,'2026-03-20 19:39:00',2700.0000,'cash',10,1,1),
(76,81,'2026-03-05 12:52:00',17050.0000,'cash',5,1,1),
(77,82,'2026-03-08 18:31:00',12600.0000,'cash',14,1,1),
(78,83,'2026-03-14 13:31:00',13550.0000,'sinpe',1,1,1),
(79,84,'2026-03-20 08:41:00',9050.0000,'credit_card',1,1,1),
(80,85,'2026-03-22 12:04:00',13800.0000,'credit_card',1,1,1),
(81,86,'2026-03-20 15:17:00',13900.0000,'cheque',2,1,1),
(82,87,'2026-04-22 16:21:00',4347.0000,'cash',1,1,1),
(83,88,'2026-04-23 15:55:00',6412.5000,'cheque',1,1,1),
(84,89,'2026-04-05 16:28:00',15550.0000,'cash',7,1,1),
(85,90,'2026-04-17 12:49:00',16800.0000,'credit_card',1,1,1),
(86,91,'2026-04-25 16:26:00',19100.0000,'cheque',1,1,1),
(87,92,'2026-04-10 17:55:00',1950.0000,'cash',8,1,1),
(88,93,'2026-04-09 18:10:00',5365.5000,'cash',1,1,1),
(89,94,'2026-04-17 20:14:00',16021.0000,'cash',1,1,1),
(90,95,'2026-04-26 16:25:00',20600.0000,'credit_card',1,1,1),
(91,96,'2026-04-08 20:16:00',5750.0000,'credit_card',1,1,1),
(92,97,'2026-04-17 10:56:00',23550.0000,'cheque',1,1,1),
(93,98,'2026-04-24 16:26:00',4400.0000,'cash',1,1,1),
(94,99,'2026-04-26 19:34:00',8300.0000,'cash',1,1,1),
(95,100,'2026-05-23 09:51:00',6350.0000,'cash',1,1,1),
(96,101,'2026-05-25 15:01:00',2706.0000,'credit_card',8,1,1),
(97,102,'2026-05-02 10:12:00',14450.0000,'cash',1,1,1),
(98,103,'2026-05-29 08:16:00',1799.5000,'credit_card',1,1,1),
(99,104,'2026-05-10 20:23:00',5850.0000,'cash',1,1,1),
(100,106,'2026-05-16 18:02:00',13800.0000,'cash',15,1,1),
(101,107,'2026-05-16 13:47:00',11100.0000,'cheque',1,1,1),
(102,108,'2026-05-13 16:46:00',8000.0000,'cheque',1,1,1),
(103,109,'2026-05-14 16:57:00',6150.0000,'cash',14,1,1),
(104,110,'2026-05-09 11:17:00',15250.0000,'credit_card',5,1,1),
(105,111,'2026-05-15 09:16:00',6450.0000,'sinpe',1,1,1),
(106,112,'2026-05-31 19:17:00',4700.0000,'cheque',8,1,1),
(107,113,'2026-06-08 09:44:00',16450.0000,'sinpe',6,1,1),
(108,114,'2026-06-09 08:06:00',2600.0000,'cash',9,1,1),
(109,115,'2026-06-25 16:34:00',3233.0000,'cash',13,1,1),
(110,116,'2026-06-02 16:54:00',1450.0000,'cheque',1,1,1),
(111,117,'2026-06-06 15:18:00',4150.0000,'cash',7,1,1),
(112,118,'2026-06-27 15:52:00',12000.0000,'credit_card',1,1,1),
(113,119,'2026-06-18 13:28:00',18450.0000,'credit_card',15,1,1),
(114,120,'2026-06-30 11:48:00',14850.0000,'cheque',1,1,1),
(115,121,'2026-06-20 10:36:00',2750.0000,'cash',12,1,1),
(116,122,'2026-06-01 19:35:00',16900.0000,'cash',1,1,1),
(117,123,'2026-06-28 11:55:00',14800.0000,'cash',10,1,1),
(118,124,'2026-06-06 12:45:00',3600.0000,'cheque',1,1,1),
(119,125,'2026-06-11 18:51:00',4751.5000,'credit_card',15,1,1),
(120,126,'2026-06-07 19:21:00',4141.0000,'sinpe',1,1,1),
(121,127,'2026-06-14 15:06:00',19700.0000,'cash',8,1,1),
(122,128,'2026-07-01 18:31:00',5050.0000,'cheque',3,1,1),
(123,129,'2026-07-04 18:51:00',8400.0000,'cash',12,1,1),
(124,130,'2026-07-03 08:05:00',9100.0000,'credit_card',8,1,1),
(125,131,'2026-07-01 08:31:00',8800.0000,'credit_card',12,1,1);

INSERT INTO `tec_hacienda_tiketes` (`id`,`sale_id`,`tipo_doc`,`consecutivo`,`clave`,`fecha_emision`,`estatus_hacienda`) VALUES
(1,1,'01','00100001010000000001','50611082025310112345600001010000000001112345678000','2025-08-11 12:47:00','aceptado'),
(2,2,'04','00100001040000000002','50622082025310112345600001040000000002112345678000','2025-08-22 12:52:00','aceptado'),
(3,3,'04','00100001040000000003','50622082025310112345600001040000000003112345678000','2025-08-22 18:19:00','error'),
(4,4,'01','00100001010000000004','50616082025310112345600001010000000004112345678000','2025-08-16 13:28:00','aceptado'),
(5,5,'01','00100001010000000005','50620082025310112345600001010000000005112345678000','2025-08-20 18:59:00','aceptado'),
(6,6,'04','00100001040000000006','50609082025310112345600001040000000006112345678000','2025-08-09 17:24:00','aceptado'),
(7,7,'04','00100001040000000007','50623082025310112345600001040000000007112345678000','2025-08-23 09:31:00','aceptado'),
(8,8,'04','00100001040000000008','50630082025310112345600001040000000008112345678000','2025-08-30 17:21:00','aceptado'),
(9,9,'04','00100001040000000009','50628082025310112345600001040000000009112345678000','2025-08-28 18:21:00','procesando'),
(10,10,'01','00100001010000000010','50619082025310112345600001010000000010112345678000','2025-08-19 15:24:00','aceptado'),
(11,11,'04','00100001040000000011','50613092025310112345600001040000000011112345678000','2025-09-13 19:38:00','aceptado'),
(12,12,'01','00100001010000000012','50616092025310112345600001010000000012112345678000','2025-09-16 17:54:00','aceptado'),
(13,13,'04','00100001040000000013','50621092025310112345600001040000000013112345678000','2025-09-21 16:47:00','aceptado'),
(14,14,'01','00100001010000000014','50601092025310112345600001010000000014112345678000','2025-09-01 14:27:00','aceptado'),
(15,15,'04','00100001040000000015','50622092025310112345600001040000000015112345678000','2025-09-22 14:10:00','error'),
(16,16,'01','00100001010000000016','50618092025310112345600001010000000016112345678000','2025-09-18 08:14:00','aceptado'),
(17,17,'04','00100001040000000017','50614092025310112345600001040000000017112345678000','2025-09-14 08:50:00','aceptado'),
(18,18,'01','00100001010000000018','50629092025310112345600001010000000018112345678000','2025-09-29 19:31:00','rechazado'),
(19,19,'04','00100001040000000019','50605092025310112345600001040000000019112345678000','2025-09-05 15:46:00','aceptado'),
(20,20,'04','00100001040000000020','50606102025310112345600001040000000020112345678000','2025-10-06 11:06:00','aceptado'),
(21,21,'04','00100001040000000021','50623102025310112345600001040000000021112345678000','2025-10-23 14:14:00','aceptado'),
(22,22,'01','00100001010000000022','50623102025310112345600001010000000022112345678000','2025-10-23 17:06:00','aceptado'),
(23,23,'01','00100001010000000023','50619102025310112345600001010000000023112345678000','2025-10-19 10:21:00','aceptado'),
(24,24,'01','00100001010000000024','50619102025310112345600001010000000024112345678000','2025-10-19 18:03:00','procesando'),
(25,25,'01','00100001010000000025','50608102025310112345600001010000000025112345678000','2025-10-08 14:49:00','aceptado'),
(26,26,'01','00100001010000000026','50627102025310112345600001010000000026112345678000','2025-10-27 17:48:00','aceptado'),
(27,27,'04','00100001040000000027','50601102025310112345600001040000000027112345678000','2025-10-01 18:44:00','aceptado'),
(28,28,'01','00100001010000000028','50616102025310112345600001010000000028112345678000','2025-10-16 18:56:00','aceptado'),
(29,29,'04','00100001040000000029','50626102025310112345600001040000000029112345678000','2025-10-26 20:33:00','aceptado'),
(30,30,'04','00100001040000000030','50622112025310112345600001040000000030112345678000','2025-11-22 10:02:00','aceptado'),
(31,31,'01','00100001010000000031','50617112025310112345600001010000000031112345678000','2025-11-17 13:08:00','aceptado'),
(32,32,'04','00100001040000000032','50605112025310112345600001040000000032112345678000','2025-11-05 10:46:00','aceptado'),
(33,33,'01','00100001010000000033','50615112025310112345600001010000000033112345678000','2025-11-15 19:05:00','aceptado'),
(34,34,'04','00100001040000000034','50625112025310112345600001040000000034112345678000','2025-11-25 17:10:00','aceptado'),
(35,35,'01','00100001010000000035','50607112025310112345600001010000000035112345678000','2025-11-07 08:45:00','aceptado'),
(36,36,'01','00100001010000000036','50628112025310112345600001010000000036112345678000','2025-11-28 19:01:00','aceptado'),
(37,37,'01','00100001010000000037','50609112025310112345600001010000000037112345678000','2025-11-09 19:23:00','aceptado'),
(38,38,'01','00100001010000000038','50626112025310112345600001010000000038112345678000','2025-11-26 16:58:00','aceptado'),
(39,39,'04','00100001040000000039','50627112025310112345600001040000000039112345678000','2025-11-27 17:49:00','aceptado'),
(40,40,'04','00100001040000000040','50615122025310112345600001040000000040112345678000','2025-12-15 15:22:00','aceptado'),
(41,41,'01','00100001010000000041','50613122025310112345600001010000000041112345678000','2025-12-13 10:43:00','aceptado'),
(42,42,'04','00100001040000000042','50605122025310112345600001040000000042112345678000','2025-12-05 19:28:00','aceptado'),
(43,43,'04','00100001040000000043','50603122025310112345600001040000000043112345678000','2025-12-03 14:54:00','aceptado'),
(44,44,'04','00100001040000000044','50612122025310112345600001040000000044112345678000','2025-12-12 16:06:00','aceptado'),
(45,45,'01','00100001010000000045','50605122025310112345600001010000000045112345678000','2025-12-05 10:30:00','aceptado'),
(46,46,'04','00100001040000000046','50625122025310112345600001040000000046112345678000','2025-12-25 17:58:00','aceptado'),
(47,47,'01','00100001010000000047','50610122025310112345600001010000000047112345678000','2025-12-10 13:36:00','aceptado'),
(48,48,'01','00100001010000000048','50607122025310112345600001010000000048112345678000','2025-12-07 12:21:00','aceptado'),
(49,49,'04','00100001040000000049','50608122025310112345600001040000000049112345678000','2025-12-08 13:48:00','aceptado'),
(50,50,'01','00100001010000000050','50623122025310112345600001010000000050112345678000','2025-12-23 17:43:00','aceptado'),
(51,51,'01','00100001010000000051','50613012026310112345600001010000000051112345678000','2026-01-13 17:37:00','rechazado'),
(52,52,'01','00100001010000000052','50615012026310112345600001010000000052112345678000','2026-01-15 17:56:00','aceptado'),
(53,53,'01','00100001010000000053','50617012026310112345600001010000000053112345678000','2026-01-17 15:04:00','aceptado'),
(54,54,'04','00100001040000000054','50618012026310112345600001040000000054112345678000','2026-01-18 14:54:00','aceptado'),
(55,55,'01','00100001010000000055','50629012026310112345600001010000000055112345678000','2026-01-29 10:38:00','aceptado'),
(56,56,'04','00100001040000000056','50607012026310112345600001040000000056112345678000','2026-01-07 20:46:00','aceptado'),
(57,57,'01','00100001010000000057','50614012026310112345600001010000000057112345678000','2026-01-14 20:00:00','aceptado'),
(58,58,'01','00100001010000000058','50612012026310112345600001010000000058112345678000','2026-01-12 10:25:00','aceptado'),
(59,59,'01','00100001010000000059','50602012026310112345600001010000000059112345678000','2026-01-02 10:49:00','aceptado'),
(60,60,'04','00100001040000000060','50616022026310112345600001040000000060112345678000','2026-02-16 13:14:00','aceptado'),
(61,61,'01','00100001010000000061','50614022026310112345600001010000000061112345678000','2026-02-14 20:19:00','aceptado'),
(62,62,'04','00100001040000000062','50609022026310112345600001040000000062112345678000','2026-02-09 13:32:00','aceptado'),
(63,63,'01','00100001010000000063','50628022026310112345600001010000000063112345678000','2026-02-28 17:22:00','rechazado'),
(64,64,'04','00100001040000000064','50623022026310112345600001040000000064112345678000','2026-02-23 11:00:00','aceptado'),
(65,65,'01','00100001010000000065','50605022026310112345600001010000000065112345678000','2026-02-05 14:51:00','aceptado'),
(66,66,'04','00100001040000000066','50606022026310112345600001040000000066112345678000','2026-02-06 08:34:00','aceptado'),
(67,67,'04','00100001040000000067','50612022026310112345600001040000000067112345678000','2026-02-12 12:58:00','aceptado'),
(68,68,'04','00100001040000000068','50622022026310112345600001040000000068112345678000','2026-02-22 20:21:00','aceptado'),
(69,69,'01','00100001010000000069','50614022026310112345600001010000000069112345678000','2026-02-14 09:32:00','aceptado'),
(70,70,'01','00100001010000000070','50617022026310112345600001010000000070112345678000','2026-02-17 14:49:00','aceptado'),
(71,71,'04','00100001040000000071','50624022026310112345600001040000000071112345678000','2026-02-24 12:55:00','aceptado'),
(72,72,'01','00100001010000000072','50605022026310112345600001010000000072112345678000','2026-02-05 11:11:00','procesando'),
(73,73,'01','00100001010000000073','50605022026310112345600001010000000073112345678000','2026-02-05 10:33:00','aceptado'),
(74,74,'04','00100001040000000074','50620032026310112345600001040000000074112345678000','2026-03-20 17:12:00','aceptado'),
(75,75,'04','00100001040000000075','50627032026310112345600001040000000075112345678000','2026-03-27 19:26:00','aceptado'),
(76,76,'04','00100001040000000076','50612032026310112345600001040000000076112345678000','2026-03-12 08:51:00','procesando'),
(77,77,'04','00100001040000000077','50625032026310112345600001040000000077112345678000','2026-03-25 18:42:00','aceptado'),
(78,78,'04','00100001040000000078','50603032026310112345600001040000000078112345678000','2026-03-03 14:42:00','aceptado'),
(79,79,'01','00100001010000000079','50601032026310112345600001010000000079112345678000','2026-03-01 19:31:00','aceptado'),
(80,80,'01','00100001010000000080','50620032026310112345600001010000000080112345678000','2026-03-20 19:39:00','aceptado'),
(81,81,'01','00100001010000000081','50605032026310112345600001010000000081112345678000','2026-03-05 12:52:00','aceptado'),
(82,82,'01','00100001010000000082','50608032026310112345600001010000000082112345678000','2026-03-08 18:31:00','aceptado'),
(83,83,'04','00100001040000000083','50614032026310112345600001040000000083112345678000','2026-03-14 13:31:00','aceptado'),
(84,84,'04','00100001040000000084','50620032026310112345600001040000000084112345678000','2026-03-20 08:41:00','aceptado'),
(85,85,'04','00100001040000000085','50622032026310112345600001040000000085112345678000','2026-03-22 12:04:00','aceptado'),
(86,86,'01','00100001010000000086','50620032026310112345600001010000000086112345678000','2026-03-20 15:17:00','rechazado'),
(87,87,'04','00100001040000000087','50622042026310112345600001040000000087112345678000','2026-04-22 16:21:00','aceptado'),
(88,88,'04','00100001040000000088','50623042026310112345600001040000000088112345678000','2026-04-23 15:55:00','aceptado'),
(89,89,'01','00100001010000000089','50605042026310112345600001010000000089112345678000','2026-04-05 16:28:00','aceptado'),
(90,90,'04','00100001040000000090','50617042026310112345600001040000000090112345678000','2026-04-17 12:49:00','aceptado'),
(91,91,'04','00100001040000000091','50625042026310112345600001040000000091112345678000','2026-04-25 16:26:00','aceptado'),
(92,92,'01','00100001010000000092','50610042026310112345600001010000000092112345678000','2026-04-10 17:55:00','aceptado'),
(93,93,'04','00100001040000000093','50609042026310112345600001040000000093112345678000','2026-04-09 18:10:00','aceptado'),
(94,94,'04','00100001040000000094','50617042026310112345600001040000000094112345678000','2026-04-17 20:14:00','procesando'),
(95,95,'04','00100001040000000095','50626042026310112345600001040000000095112345678000','2026-04-26 16:25:00','aceptado'),
(96,96,'04','00100001040000000096','50608042026310112345600001040000000096112345678000','2026-04-08 20:16:00','aceptado'),
(97,97,'04','00100001040000000097','50617042026310112345600001040000000097112345678000','2026-04-17 10:56:00','rechazado'),
(98,98,'04','00100001040000000098','50624042026310112345600001040000000098112345678000','2026-04-24 16:26:00','aceptado'),
(99,99,'04','00100001040000000099','50626042026310112345600001040000000099112345678000','2026-04-26 19:34:00','aceptado'),
(100,100,'04','00100001040000000100','50623052026310112345600001040000000100112345678000','2026-05-23 09:51:00','aceptado'),
(101,101,'01','00100001010000000101','50625052026310112345600001010000000101112345678000','2026-05-25 15:01:00','aceptado'),
(102,102,'04','00100001040000000102','50602052026310112345600001040000000102112345678000','2026-05-02 10:12:00','aceptado'),
(103,103,'04','00100001040000000103','50629052026310112345600001040000000103112345678000','2026-05-29 08:16:00','aceptado'),
(104,104,'04','00100001040000000104','50610052026310112345600001040000000104112345678000','2026-05-10 20:23:00','aceptado'),
(105,105,'01','00100001010000000105','50605052026310112345600001010000000105112345678000','2026-05-05 13:15:00','aceptado'),
(106,106,'01','00100001010000000106','50616052026310112345600001010000000106112345678000','2026-05-16 18:02:00','aceptado'),
(107,107,'04','00100001040000000107','50616052026310112345600001040000000107112345678000','2026-05-16 13:47:00','procesando'),
(108,108,'04','00100001040000000108','50613052026310112345600001040000000108112345678000','2026-05-13 16:46:00','aceptado'),
(109,109,'01','00100001010000000109','50614052026310112345600001010000000109112345678000','2026-05-14 16:57:00','aceptado'),
(110,110,'01','00100001010000000110','50609052026310112345600001010000000110112345678000','2026-05-09 11:17:00','aceptado'),
(111,111,'04','00100001040000000111','50615052026310112345600001040000000111112345678000','2026-05-15 09:16:00','aceptado'),
(112,112,'01','00100001010000000112','50631052026310112345600001010000000112112345678000','2026-05-31 19:17:00','aceptado'),
(113,113,'01','00100001010000000113','50608062026310112345600001010000000113112345678000','2026-06-08 09:44:00','aceptado'),
(114,114,'01','00100001010000000114','50609062026310112345600001010000000114112345678000','2026-06-09 08:06:00','aceptado'),
(115,115,'01','00100001010000000115','50625062026310112345600001010000000115112345678000','2026-06-25 16:34:00','aceptado'),
(116,116,'04','00100001040000000116','50602062026310112345600001040000000116112345678000','2026-06-02 16:54:00','aceptado'),
(117,117,'01','00100001010000000117','50606062026310112345600001010000000117112345678000','2026-06-06 15:18:00','aceptado'),
(118,118,'04','00100001040000000118','50627062026310112345600001040000000118112345678000','2026-06-27 15:52:00','aceptado'),
(119,119,'01','00100001010000000119','50618062026310112345600001010000000119112345678000','2026-06-18 13:28:00','aceptado'),
(120,120,'04','00100001040000000120','50630062026310112345600001040000000120112345678000','2026-06-30 11:48:00','aceptado'),
(121,121,'01','00100001010000000121','50620062026310112345600001010000000121112345678000','2026-06-20 10:36:00','aceptado'),
(122,122,'04','00100001040000000122','50601062026310112345600001040000000122112345678000','2026-06-01 19:35:00','aceptado'),
(123,124,'04','00100001040000000124','50606062026310112345600001040000000124112345678000','2026-06-06 12:45:00','aceptado'),
(124,125,'01','00100001010000000125','50611062026310112345600001010000000125112345678000','2026-06-11 18:51:00','aceptado'),
(125,126,'04','00100001040000000126','50607062026310112345600001040000000126112345678000','2026-06-07 19:21:00','procesando'),
(126,127,'01','00100001010000000127','50614062026310112345600001010000000127112345678000','2026-06-14 15:06:00','rechazado'),
(127,128,'01','00100001010000000128','50601072026310112345600001010000000128112345678000','2026-07-01 18:31:00','aceptado'),
(128,129,'01','00100001010000000129','50604072026310112345600001010000000129112345678000','2026-07-04 18:51:00','error'),
(129,130,'01','00100001010000000130','50603072026310112345600001010000000130112345678000','2026-07-03 08:05:00','rechazado'),
(130,131,'01','00100001010000000131','50601072026310112345600001010000000131112345678000','2026-07-01 08:31:00','aceptado');

-- 18 compras generadas
INSERT INTO `tec_purchases` (`id`,`date`,`reference`,`supplier_id`,`total`,`created_by`,`store_id`) VALUES
(1,'2025-08-02 10:00:00','OC-0001',1,44200.0000,1,1),
(2,'2025-08-14 10:00:00','OC-0002',1,83350.0000,1,1),
(3,'2025-09-02 10:00:00','OC-0003',1,57660.0000,1,1),
(4,'2025-09-04 10:00:00','OC-0004',1,37400.0000,1,1),
(5,'2025-10-07 10:00:00','OC-0005',2,174630.0000,1,1),
(6,'2025-11-19 10:00:00','OC-0006',3,52100.0000,1,1),
(7,'2025-12-27 10:00:00','OC-0007',1,46750.0000,1,1),
(8,'2025-12-29 10:00:00','OC-0008',1,145100.0000,1,1),
(9,'2026-01-12 10:00:00','OC-0009',1,116550.0000,1,1),
(10,'2026-01-03 10:00:00','OC-0010',2,74400.0000,1,1),
(11,'2026-02-08 10:00:00','OC-0011',2,131800.0000,1,1),
(12,'2026-03-13 10:00:00','OC-0012',3,171320.0000,1,1),
(13,'2026-04-14 10:00:00','OC-0013',1,71250.0000,1,1),
(14,'2026-04-27 10:00:00','OC-0014',3,17200.0000,1,1),
(15,'2026-05-13 10:00:00','OC-0015',1,124600.0000,1,1),
(16,'2026-06-18 10:00:00','OC-0016',1,102500.0000,1,1),
(17,'2026-06-03 10:00:00','OC-0017',1,78830.0000,1,1),
(18,'2026-07-03 10:00:00','OC-0018',2,196550.0000,1,1);

INSERT INTO `tec_purchase_items` (`id`,`purchase_id`,`product_id`,`quantity`,`cost`,`subtotal`) VALUES
(1,1,23,14,900.0000,12600.0000),
(2,1,6,14,1700.0000,23800.0000),
(3,1,10,13,600.0000,7800.0000),
(4,2,1,20,900.0000,18000.0000),
(5,2,16,19,1750.0000,33250.0000),
(6,2,13,12,1600.0000,19200.0000),
(7,2,18,30,430.0000,12900.0000),
(8,3,23,34,900.0000,30600.0000),
(9,3,22,33,420.0000,13860.0000),
(10,3,12,22,600.0000,13200.0000),
(11,4,18,38,430.0000,16340.0000),
(12,4,11,27,780.0000,21060.0000),
(13,5,21,20,3000.0000,60000.0000),
(14,5,22,18,420.0000,7560.0000),
(15,5,4,26,420.0000,10920.0000),
(16,5,8,13,1550.0000,20150.0000),
(17,5,14,40,1900.0000,76000.0000),
(18,6,3,27,1300.0000,35100.0000),
(19,6,20,17,1000.0000,17000.0000),
(20,7,5,17,650.0000,11050.0000),
(21,7,2,28,1050.0000,29400.0000),
(22,7,22,15,420.0000,6300.0000),
(23,8,1,17,900.0000,15300.0000),
(24,8,19,28,1400.0000,39200.0000),
(25,8,22,20,420.0000,8400.0000),
(26,8,21,16,3000.0000,48000.0000),
(27,8,23,38,900.0000,34200.0000),
(28,9,10,27,600.0000,16200.0000),
(29,9,2,28,1050.0000,29400.0000),
(30,9,9,39,1150.0000,44850.0000),
(31,9,1,29,900.0000,26100.0000),
(32,10,10,31,600.0000,18600.0000),
(33,10,8,36,1550.0000,55800.0000),
(34,11,11,10,780.0000,7800.0000),
(35,11,21,31,3000.0000,93000.0000),
(36,11,8,20,1550.0000,31000.0000),
(37,12,14,37,1900.0000,70300.0000),
(38,12,13,22,1600.0000,35200.0000),
(39,12,3,39,1300.0000,50700.0000),
(40,12,22,36,420.0000,15120.0000),
(41,13,2,23,1050.0000,24150.0000),
(42,13,17,16,500.0000,8000.0000),
(43,13,6,23,1700.0000,39100.0000),
(44,14,22,10,420.0000,4200.0000),
(45,14,20,13,1000.0000,13000.0000),
(46,15,13,30,1600.0000,48000.0000),
(47,15,12,19,600.0000,11400.0000),
(48,15,24,39,800.0000,31200.0000),
(49,15,20,34,1000.0000,34000.0000),
(50,16,23,40,900.0000,36000.0000),
(51,16,16,38,1750.0000,66500.0000),
(52,17,5,40,650.0000,26000.0000),
(53,17,12,24,600.0000,14400.0000),
(54,17,2,21,1050.0000,22050.0000),
(55,17,22,39,420.0000,16380.0000),
(56,18,6,40,1700.0000,68000.0000),
(57,18,16,25,1750.0000,43750.0000),
(58,18,8,28,1550.0000,43400.0000),
(59,18,23,22,900.0000,19800.0000),
(60,18,12,36,600.0000,21600.0000);

-- 30 gastos generados
INSERT INTO `tec_expenses` (`id`,`date`,`reference`,`amount`,`created_by`,`store_id`) VALUES
(1,'2025-08-19 09:00:00','Servicios públicos (luz/agua)',116584.0000,1,1),
(2,'2025-08-23 09:00:00','Alquiler local',355543.0000,1,1),
(3,'2025-09-17 09:00:00','Alquiler local',392069.0000,1,1),
(4,'2025-09-10 09:00:00','Servicios públicos (luz/agua)',36156.0000,1,1),
(5,'2025-09-18 09:00:00','Internet y telefonía',83422.0000,1,1),
(6,'2025-10-18 09:00:00','Publicidad y mercadeo',74566.0000,1,1),
(7,'2025-10-05 09:00:00','Internet y telefonía',87402.0000,1,1),
(8,'2025-10-16 09:00:00','Internet y telefonía',41374.0000,1,1),
(9,'2025-11-11 09:00:00','Servicios públicos (luz/agua)',32139.0000,1,1),
(10,'2025-11-04 09:00:00','Publicidad y mercadeo',130706.0000,1,1),
(11,'2025-11-23 09:00:00','Alquiler local',400055.0000,1,1),
(12,'2025-12-22 09:00:00','Publicidad y mercadeo',102751.0000,1,1),
(13,'2025-12-13 09:00:00','Planilla empleados',996407.0000,1,1),
(14,'2025-12-14 09:00:00','Alquiler local',446435.0000,1,1),
(15,'2026-01-13 09:00:00','Alquiler local',433333.0000,1,1),
(16,'2026-01-27 09:00:00','Mantenimiento equipo POS',102542.0000,1,1),
(17,'2026-01-15 09:00:00','Internet y telefonía',93668.0000,1,1),
(18,'2026-02-04 09:00:00','Publicidad y mercadeo',81528.0000,1,1),
(19,'2026-02-04 09:00:00','Internet y telefonía',123587.0000,1,1),
(20,'2026-03-10 09:00:00','Servicios públicos (luz/agua)',132042.0000,1,1),
(21,'2026-03-18 09:00:00','Servicios públicos (luz/agua)',108465.0000,1,1),
(22,'2026-04-30 09:00:00','Mantenimiento equipo POS',38712.0000,1,1),
(23,'2026-04-02 09:00:00','Alquiler local',445655.0000,1,1),
(24,'2026-05-22 09:00:00','Servicios públicos (luz/agua)',83399.0000,1,1),
(25,'2026-05-30 09:00:00','Servicios públicos (luz/agua)',125478.0000,1,1),
(26,'2026-06-26 09:00:00','Publicidad y mercadeo',40922.0000,1,1),
(27,'2026-06-20 09:00:00','Publicidad y mercadeo',104468.0000,1,1),
(28,'2026-06-02 09:00:00','Servicios públicos (luz/agua)',45498.0000,1,1),
(29,'2026-07-01 09:00:00','Mantenimiento equipo POS',53610.0000,1,1),
(30,'2026-07-04 09:00:00','Alquiler local',366194.0000,1,1);
INSERT INTO `tec_provincia_cr` (`codigo_provincia`,`nombre_provincia`) VALUES
('1','San José'),
('2','Alajuela'),
('3','Cartago'),
('4','Heredia'),
('5','Guanacaste'),
('6','Puntarenas'),
('7','Limón');

INSERT INTO `tec_canton_cr` (`codigo_provincia`,`codigo_canton`,`nombre_canton`) VALUES
('1','01','San José'),('1','02','Escazú'),('1','03','Desamparados'),('1','04','Puriscal'),
('1','05','Tarrazú'),('1','06','Aserrí'),('1','07','Mora'),('1','08','Goicoechea'),
('1','09','Santa Ana'),('1','10','Alajuelita'),('1','11','Vásquez de Coronado'),('1','12','Acosta'),
('1','13','Tibás'),('1','14','Moravia'),('1','15','Montes de Oca'),('1','16','Turrubares'),
('1','17','Dota'),('1','18','Curridabat'),('1','19','Pérez Zeledón'),('1','20','León Cortés Castro'),
('2','01','Alajuela'),('2','02','San Ramón'),('2','03','Grecia'),('2','04','San Mateo'),
('2','05','Atenas'),('2','06','Naranjo'),('2','07','Palmares'),('2','08','Poás'),
('2','09','Orotina'),('2','10','San Carlos'),('2','11','Zarcero'),('2','12','Sarchí'),
('2','13','Upala'),('2','14','Los Chiles'),('2','15','Guatuso'),
('3','01','Cartago'),('3','02','Paraíso'),('3','03','La Unión'),('3','04','Jiménez'),
('3','05','Turrialba'),('3','06','Alvarado'),('3','07','Oreamuno'),('3','08','El Guarco'),
('4','01','Heredia'),('4','02','Barva'),('4','03','Santo Domingo'),('4','04','Santa Bárbara'),
('4','05','San Rafael'),('4','06','San Isidro'),('4','07','Belén'),('4','08','Flores'),
('4','09','San Pablo'),('4','10','Sarapiquí'),
('5','01','Liberia'),('5','02','Nicoya'),('5','03','Santa Cruz'),('5','04','Bagaces'),
('5','05','Carrillo'),('5','06','Cañas'),('5','07','Abangares'),('5','08','Tilarán'),
('5','09','Nandayure'),('5','10','La Cruz'),('5','11','Hojancha'),
('6','01','Puntarenas'),('6','02','Esparza'),('6','03','Buenos Aires'),('6','04','Montes de Oro'),
('6','05','Osa'),('6','06','Quepos'),('6','07','Golfito'),('6','08','Coto Brus'),
('6','09','Parrita'),('6','10','Corredores'),('6','11','Garabito'),
('7','01','Limón'),('7','02','Pococí'),('7','03','Siquirres'),('7','04','Talamanca'),
('7','05','Matina'),('7','06','Guácimo'),('7','07','Valle La Estrella');

INSERT INTO `tec_distrito_cr` (`codigo_provincia`,`codigo_canton`,`codigo_distrito`,`nombre_distrito`) VALUES
-- San José
('1','01','01','Carmen'),('1','01','02','Merced'),('1','01','03','Hospital'),('1','01','04','Catedral'),
('1','01','05','Zapote'),('1','01','06','San Francisco de Dos Ríos'),('1','01','07','Uruca'),('1','01','08','Mata Redonda'),
('1','01','09','Pavas'),('1','01','10','Hatillo'),('1','01','11','San Sebastián'),
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

-- Barrios: 1 por cada uno de los 488 distritos (con 4 excepciones documentadas con nombres reales)
INSERT INTO `tec_barrio_cr` (`codigo_provincia`,`codigo_canton`,`codigo_distrito`,`codigo_barrio`,`nombre_barrio`) VALUES
('1','01','01','01','Amón'),('1','01','01','02','Aranjuez'),('1','01','01','03','Otoya'),('1','01','01','04','Escalante'),
('1','01','02','01','La Merced'),('1','01','02','02','Pitahaya'),('1','01','02','03','Claret'),
('1','01','03','01','Centro'),('1','01','04','01','Centro'),('1','01','05','01','Centro'),('1','01','06','01','Centro'),
('1','01','07','01','Centro'),('1','01','08','01','Centro'),('1','01','09','01','Centro'),('1','01','10','01','Centro'),
('1','01','11','01','Centro'),('1','02','01','01','Centro'),('1','02','02','01','Centro'),('1','02','03','01','Centro'),
('1','03','01','01','Centro'),('1','03','02','01','Centro'),('1','03','03','01','Centro'),('1','03','04','01','Centro'),
('1','03','05','01','Centro'),('1','03','06','01','Centro'),('1','03','07','01','Centro'),('1','03','08','01','Centro'),
('1','03','09','01','Centro'),('1','03','10','01','Centro'),('1','03','11','01','Centro'),('1','03','12','01','Centro'),
('1','03','13','01','Centro'),('1','04','01','01','Centro'),('1','04','02','01','Centro'),('1','04','03','01','Centro'),
('1','04','04','01','Centro'),('1','04','05','01','Centro'),('1','04','06','01','Centro'),('1','04','07','01','Centro'),
('1','04','08','01','Centro'),('1','04','09','01','Centro'),('1','05','01','01','Centro'),('1','05','02','01','Centro'),
('1','05','03','01','Centro'),('1','06','01','01','Centro'),('1','06','02','01','Centro'),('1','06','03','01','Centro'),
('1','06','04','01','Centro'),('1','06','05','01','Centro'),('1','06','06','01','Centro'),('1','06','07','01','Centro'),
('1','07','01','01','Centro'),('1','07','02','01','Centro'),('1','07','03','01','Centro'),('1','07','04','01','Centro'),
('1','07','05','01','Centro'),('1','07','06','01','Centro'),('1','07','07','01','Centro'),('1','08','01','01','Centro'),
('1','08','02','01','Centro'),('1','08','03','01','Centro'),('1','08','04','01','Centro'),('1','08','05','01','Centro'),
('1','08','06','01','Centro'),('1','08','07','01','Centro'),('1','09','01','01','Centro'),('1','09','02','01','Centro'),
('1','09','03','01','Centro'),('1','09','04','01','Centro'),('1','09','05','01','Centro'),('1','09','06','01','Centro'),
('1','10','01','01','Centro'),('1','10','02','01','Centro'),('1','10','03','01','Centro'),('1','10','04','01','Centro'),
('1','10','05','01','Centro'),('1','11','01','01','Centro'),('1','11','02','01','Centro'),('1','11','03','01','Centro'),
('1','11','04','01','Centro'),('1','12','01','01','Centro'),('1','12','02','01','Centro'),('1','12','03','01','Centro'),
('1','12','04','01','Centro'),('1','12','05','01','Centro'),('1','12','06','01','Centro'),('1','13','01','01','Centro'),
('1','13','02','01','Centro'),('1','13','03','01','Centro'),('1','13','04','01','Centro'),('1','13','05','01','Centro'),
('1','14','01','01','Centro'),('1','14','02','01','Centro'),('1','14','03','01','Centro'),('1','15','01','01','Centro'),
('1','15','02','01','Centro'),('1','15','03','01','Centro'),('1','15','04','01','Centro'),('1','16','01','01','Centro'),
('1','16','02','01','Centro'),('1','16','03','01','Centro'),('1','16','04','01','Centro'),('1','16','05','01','Centro'),
('1','17','01','01','Centro'),('1','17','02','01','Centro'),('1','17','03','01','Centro'),('1','18','01','01','Centro'),
('1','18','02','01','Centro'),('1','18','03','01','Centro'),('1','18','04','01','Centro'),('1','19','01','01','Centro'),
('1','19','02','01','Centro'),('1','19','03','01','Centro'),('1','19','04','01','Centro'),('1','19','05','01','Centro'),
('1','19','06','01','Centro'),('1','19','07','01','Centro'),('1','19','08','01','Centro'),('1','19','09','01','Centro'),
('1','19','10','01','Centro'),('1','19','11','01','Centro'),('1','19','12','01','Centro'),('1','20','01','01','Centro'),
('1','20','02','01','Centro'),('1','20','03','01','Centro'),('1','20','04','01','Centro'),('1','20','05','01','Centro'),
('1','20','06','01','Centro'),
('2','01','01','01','Centro'),('2','01','01','02','San José'),('2','01','01','03','Barreales'),
('2','01','02','01','Centro'),('2','01','03','01','Centro'),('2','01','04','01','Centro'),('2','01','05','01','Centro'),
('2','01','06','01','Centro'),('2','01','07','01','Centro'),('2','01','08','01','Centro'),('2','01','09','01','Centro'),
('2','01','10','01','Centro'),('2','01','11','01','Centro'),('2','01','12','01','Centro'),('2','01','13','01','Centro'),
('2','01','14','01','Centro'),('2','02','01','01','Centro'),('2','02','02','01','Centro'),('2','02','03','01','Centro'),
('2','02','04','01','Centro'),('2','02','05','01','Centro'),('2','02','06','01','Centro'),('2','02','07','01','Centro'),
('2','02','08','01','Centro'),('2','02','09','01','Centro'),('2','02','10','01','Centro'),('2','02','11','01','Centro'),
('2','02','12','01','Centro'),('2','02','13','01','Centro'),('2','03','01','01','Centro'),('2','03','02','01','Centro'),
('2','03','03','01','Centro'),('2','03','04','01','Centro'),('2','03','05','01','Centro'),('2','03','06','01','Centro'),
('2','03','07','01','Centro'),('2','03','08','01','Centro'),('2','04','01','01','Centro'),('2','04','02','01','Centro'),
('2','04','03','01','Centro'),('2','04','04','01','Centro'),('2','05','01','01','Centro'),('2','05','02','01','Centro'),
('2','05','03','01','Centro'),('2','05','04','01','Centro'),('2','05','05','01','Centro'),('2','05','06','01','Centro'),
('2','05','07','01','Centro'),('2','05','08','01','Centro'),('2','06','01','01','Centro'),('2','06','02','01','Centro'),
('2','06','03','01','Centro'),('2','06','04','01','Centro'),('2','06','05','01','Centro'),('2','06','06','01','Centro'),
('2','06','07','01','Centro'),('2','06','08','01','Centro'),('2','07','01','01','Centro'),('2','07','02','01','Centro'),
('2','07','03','01','Centro'),('2','07','04','01','Centro'),('2','07','05','01','Centro'),('2','07','06','01','Centro'),
('2','07','07','01','Centro'),('2','08','01','01','Centro'),('2','08','02','01','Centro'),('2','08','03','01','Centro'),
('2','08','04','01','Centro'),('2','08','05','01','Centro'),('2','09','01','01','Centro'),('2','09','02','01','Centro'),
('2','09','03','01','Centro'),('2','09','04','01','Centro'),('2','09','05','01','Centro'),('2','10','01','01','Centro'),
('2','10','02','01','Centro'),('2','10','03','01','Centro'),('2','10','04','01','Centro'),('2','10','05','01','Centro'),
('2','10','06','01','Centro'),('2','10','07','01','Centro'),('2','10','08','01','Centro'),('2','10','09','01','Centro'),
('2','10','10','01','Centro'),('2','10','11','01','Centro'),('2','10','12','01','Centro'),('2','10','13','01','Centro'),
('2','11','01','01','Centro'),('2','11','02','01','Centro'),('2','11','03','01','Centro'),('2','11','04','01','Centro'),
('2','11','05','01','Centro'),('2','11','06','01','Centro'),('2','11','07','01','Centro'),('2','12','01','01','Centro'),
('2','12','02','01','Centro'),('2','12','03','01','Centro'),('2','12','04','01','Centro'),('2','12','05','01','Centro'),
('2','13','01','01','Centro'),('2','13','02','01','Centro'),('2','13','03','01','Centro'),('2','13','04','01','Centro'),
('2','13','05','01','Centro'),('2','13','06','01','Centro'),('2','13','07','01','Centro'),('2','13','08','01','Centro'),
('2','14','01','01','Centro'),('2','14','02','01','Centro'),('2','14','03','01','Centro'),('2','14','04','01','Centro'),
('2','15','01','01','Centro'),('2','15','02','01','Centro'),('2','15','03','01','Centro'),('2','15','04','01','Centro'),
('3','01','01','01','Centro'),('3','01','02','01','Centro'),('3','01','03','01','Centro'),('3','01','04','01','Centro'),
('3','01','05','01','Centro'),('3','01','06','01','Centro'),('3','01','07','01','Centro'),('3','01','08','01','Centro'),
('3','01','09','01','Centro'),('3','01','10','01','Centro'),('3','01','11','01','Centro'),('3','02','01','01','Centro'),
('3','02','02','01','Centro'),('3','02','03','01','Centro'),('3','02','04','01','Centro'),('3','02','05','01','Centro'),
('3','03','01','01','Centro'),('3','03','02','01','Centro'),('3','03','03','01','Centro'),('3','03','04','01','Centro'),
('3','03','05','01','Centro'),('3','03','06','01','Centro'),('3','03','07','01','Centro'),('3','03','08','01','Centro'),
('3','04','01','01','Centro'),('3','04','02','01','Centro'),('3','04','03','01','Centro'),('3','05','01','01','Centro'),
('3','05','02','01','Centro'),('3','05','03','01','Centro'),('3','05','04','01','Centro'),('3','05','05','01','Centro'),
('3','05','06','01','Centro'),('3','05','07','01','Centro'),('3','05','08','01','Centro'),('3','05','09','01','Centro'),
('3','05','10','01','Centro'),('3','05','11','01','Centro'),('3','05','12','01','Centro'),('3','06','01','01','Centro'),
('3','06','02','01','Centro'),('3','06','03','01','Centro'),('3','07','01','01','Centro'),('3','07','02','01','Centro'),
('3','07','03','01','Centro'),('3','07','04','01','Centro'),('3','07','05','01','Centro'),('3','08','01','01','Centro'),
('3','08','02','01','Centro'),('3','08','03','01','Centro'),('3','08','04','01','Centro'),
('4','01','01','01','Los Angeles'),('4','01','01','02','Corazón de Jesús'),('4','01','01','03','Llorente'),
('4','01','02','01','Centro'),('4','01','03','01','Centro'),('4','01','04','01','Centro'),('4','01','05','01','Centro'),
('4','02','01','01','Centro'),('4','02','02','01','Centro'),('4','02','03','01','Centro'),('4','02','04','01','Centro'),
('4','02','05','01','Centro'),('4','02','06','01','Centro'),('4','03','01','01','Centro'),('4','03','02','01','Centro'),
('4','03','03','01','Centro'),('4','03','04','01','Centro'),('4','03','05','01','Centro'),('4','03','06','01','Centro'),
('4','03','07','01','Centro'),('4','03','08','01','Centro'),('4','04','01','01','Centro'),('4','04','02','01','Centro'),
('4','04','03','01','Centro'),('4','04','04','01','Centro'),('4','04','05','01','Centro'),('4','04','06','01','Centro'),
('4','05','01','01','Centro'),('4','05','02','01','Centro'),('4','05','03','01','Centro'),('4','05','04','01','Centro'),
('4','05','05','01','Centro'),('4','06','01','01','Centro'),('4','06','02','01','Centro'),('4','06','03','01','Centro'),
('4','06','04','01','Centro'),('4','07','01','01','Centro'),('4','07','02','01','Centro'),('4','07','03','01','Centro'),
('4','08','01','01','Centro'),('4','08','02','01','Centro'),('4','08','03','01','Centro'),('4','08','04','01','Centro'),
('4','09','01','01','Centro'),('4','09','02','01','Centro'),('4','10','01','01','Centro'),('4','10','02','01','Centro'),
('4','10','03','01','Centro'),('4','10','04','01','Centro'),('4','10','05','01','Centro'),
('5','01','01','01','Centro'),('5','01','02','01','Centro'),('5','01','03','01','Centro'),('5','01','04','01','Centro'),
('5','01','05','01','Centro'),('5','02','01','01','Centro'),('5','02','02','01','Centro'),('5','02','03','01','Centro'),
('5','02','04','01','Centro'),('5','02','05','01','Centro'),('5','02','06','01','Centro'),('5','02','07','01','Centro'),
('5','03','01','01','Centro'),('5','03','02','01','Centro'),('5','03','03','01','Centro'),('5','03','04','01','Centro'),
('5','03','05','01','Centro'),('5','03','06','01','Centro'),('5','03','07','01','Centro'),('5','03','08','01','Centro'),
('5','03','09','01','Centro'),('5','04','01','01','Centro'),('5','04','02','01','Centro'),('5','04','03','01','Centro'),
('5','04','04','01','Centro'),('5','05','01','01','Centro'),('5','05','02','01','Centro'),('5','05','03','01','Centro'),
('5','05','04','01','Centro'),('5','06','01','01','Centro'),('5','06','02','01','Centro'),('5','06','03','01','Centro'),
('5','06','04','01','Centro'),('5','06','05','01','Centro'),('5','07','01','01','Centro'),('5','07','02','01','Centro'),
('5','07','03','01','Centro'),('5','07','04','01','Centro'),('5','08','01','01','Centro'),('5','08','02','01','Centro'),
('5','08','03','01','Centro'),('5','08','04','01','Centro'),('5','08','05','01','Centro'),('5','08','06','01','Centro'),
('5','08','07','01','Centro'),('5','09','01','01','Centro'),('5','09','02','01','Centro'),('5','09','03','01','Centro'),
('5','09','04','01','Centro'),('5','09','05','01','Centro'),('5','10','01','01','Centro'),('5','10','02','01','Centro'),
('5','10','03','01','Centro'),('5','10','04','01','Centro'),('5','11','01','01','Centro'),('5','11','02','01','Centro'),
('5','11','03','01','Centro'),('5','11','04','01','Centro'),('5','11','05','01','Centro'),
('6','01','01','01','Centro'),('6','01','02','01','Centro'),('6','01','03','01','Centro'),('6','01','04','01','Centro'),
('6','01','05','01','Centro'),('6','01','06','01','Centro'),('6','01','07','01','Centro'),('6','01','08','01','Centro'),
('6','01','09','01','Centro'),('6','01','10','01','Centro'),('6','01','11','01','Centro'),('6','01','12','01','Centro'),
('6','01','13','01','Centro'),('6','01','14','01','Centro'),('6','01','15','01','Centro'),('6','01','16','01','Centro'),
('6','02','01','01','Centro'),('6','02','02','01','Centro'),('6','02','03','01','Centro'),('6','02','04','01','Centro'),
('6','02','05','01','Centro'),('6','02','06','01','Centro'),('6','03','01','01','Centro'),('6','03','02','01','Centro'),
('6','03','03','01','Centro'),('6','03','04','01','Centro'),('6','03','05','01','Centro'),('6','03','06','01','Centro'),
('6','03','07','01','Centro'),('6','03','08','01','Centro'),('6','03','09','01','Centro'),('6','04','01','01','Centro'),
('6','04','02','01','Centro'),('6','04','03','01','Centro'),('6','05','01','01','Centro'),('6','05','02','01','Centro'),
('6','05','03','01','Centro'),('6','05','04','01','Centro'),('6','05','05','01','Centro'),('6','05','06','01','Centro'),
('6','06','01','01','Centro'),('6','06','02','01','Centro'),('6','06','03','01','Centro'),('6','07','01','01','Centro'),
('6','07','02','01','Centro'),('6','07','03','01','Centro'),('6','07','04','01','Centro'),('6','08','01','01','Centro'),
('6','08','02','01','Centro'),('6','08','03','01','Centro'),('6','08','04','01','Centro'),('6','08','05','01','Centro'),
('6','08','06','01','Centro'),('6','09','01','01','Centro'),('6','10','01','01','Centro'),('6','10','02','01','Centro'),
('6','10','03','01','Centro'),('6','10','04','01','Centro'),('6','11','01','01','Centro'),('6','11','02','01','Centro'),
('6','11','03','01','Centro'),
('7','01','01','01','Centro'),('7','01','02','01','Centro'),('7','01','03','01','Centro'),('7','01','04','01','Centro'),
('7','02','01','01','Centro'),('7','02','02','01','Centro'),('7','02','03','01','Centro'),('7','02','04','01','Centro'),
('7','02','05','01','Centro'),('7','02','06','01','Centro'),('7','02','07','01','Centro'),('7','03','01','01','Centro'),
('7','03','02','01','Centro'),('7','03','03','01','Centro'),('7','03','04','01','Centro'),('7','03','05','01','Centro'),
('7','03','06','01','Centro'),('7','03','07','01','Centro'),('7','04','01','01','Centro'),('7','04','02','01','Centro'),
('7','04','03','01','Centro'),('7','04','04','01','Centro'),('7','05','01','01','Centro'),('7','05','02','01','Centro'),
('7','05','03','01','Centro'),('7','06','01','01','Centro'),('7','06','02','01','Centro'),('7','06','03','01','Centro'),
('7','06','04','01','Centro'),('7','06','05','01','Centro'),('7','07','01','01','Centro'),('7','07','02','01','Centro'),
('7','07','03','01','Centro');


SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- FIN — Resumen de acceso:
--   URL local típica en Laragon: http://facturacion.test/  (o el alias
--   que Laragon le asigne a esta carpeta)
--   Usuario: admin@example.com
--   Contraseña: admin123
--
-- Datos de prueba incluidos:
--   24 productos (varias categorías, incluye 3 con stock bajo y
--   2 agotados para probar alertas de inventario)
--   16 clientes (Cliente de Contado + 15 con cédula/tipo variado)
--   3 proveedores
--   ~131 ventas distribuidas en los últimos 12 meses (con estados
--   paid/partial/due y documentos Hacienda aceptado/procesando/
--   rechazado/error variados, para poblar el Dashboard financiero,
--   reportes de ventas y estadísticas de facturación electrónica)
--   ~18 compras y ~30 gastos mensuales (gráfica financiera 12 meses)
-- =====================================================================

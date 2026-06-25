CREATE TABLE IF NOT EXISTS `tec_hacienda_rep` (
  `id_hacienda`      INT(11)       NOT NULL AUTO_INCREMENT,
  `payment_id`       INT(11)       NOT NULL,
  `sale_id`          INT(11)       NOT NULL,
  `clave`            VARCHAR(50)   DEFAULT NULL,
  `consecutivo`      VARCHAR(20)   DEFAULT NULL,
  `fecha_emision`    DATETIME      DEFAULT NULL,
  `tipo_doc`         VARCHAR(2)    NOT NULL DEFAULT '09',
  `estatus_hacienda` VARCHAR(20)   NOT NULL DEFAULT 'procesando',
  `xml`              MEDIUMTEXT    DEFAULT NULL,
  `xml_sign`         MEDIUMBLOB    DEFAULT NULL,
  `xml_hacienda`     MEDIUMTEXT    DEFAULT NULL,
  `mail`             TINYINT(1)    NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_hacienda`),
  UNIQUE KEY `uq_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `tec_settings` CHANGE `version` `version` VARCHAR(10) NOT NULL DEFAULT '4.0.21';
UPDATE `tec_settings` SET `version` = '4.0.21' WHERE `setting_id` = 1;

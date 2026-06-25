ALTER TABLE `customers` ADD `codigo_actividad` VARCHAR(6) NULL DEFAULT NULL AFTER `business_name`;
ALTER TABLE `tec_settings` CHANGE `version` `version` VARCHAR(10) NOT NULL DEFAULT '4.0.20';
UPDATE `tec_settings` SET `version` = '4.0.20' WHERE `setting_id` = 1;

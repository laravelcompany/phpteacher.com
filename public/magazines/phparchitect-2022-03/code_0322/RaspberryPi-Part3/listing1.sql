SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `accelerometer_data`;
CREATE TABLE `accelerometer_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created` datetime(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `axis_x` float NOT NULL,
  `axis_y` float NOT NULL,
  `axis_z` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1;
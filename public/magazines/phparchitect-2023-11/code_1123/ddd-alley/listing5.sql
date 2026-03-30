CREATE TABLE `placemarks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `league_id` int unsigned NOT NULL,
  `doc_name` varchar(255) NOT NULL,
  `point_name` varchar(255) NOT NULL,
  `coord_x` varchar(255) NOT NULL,
  `coord_y` varchar(255) NOT NULL,
  `coord_z` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc_name` (`doc_name`,`point_name`),
  KEY `league_id` (`league_id`),
  CONSTRAINT FOREIGN KEY (`league_id`)
        REFERENCES `leagues` (`id`)
) ENGINE=InnoDB;
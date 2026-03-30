CREATE TABLE `email_requests`
(
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `reservation_code` char(32) DEFAULT NULL,
    `reserved_at` timestamp(6) NULL DEFAULT NULL,
    `started` timestamp(6) NULL DEFAULT NULL,
    `completed` timestamp(6) NULL DEFAULT NULL,
    `queue_parameters` mediumtext NOT NULL
            COMMENT 'PHP serialize()',
    `created` datetime NOT NULL
            DEFAULT CURRENT_TIMESTAMP,
    `modified` datetime NOT NULL
            DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `reservation_code` (`reservation_code`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;
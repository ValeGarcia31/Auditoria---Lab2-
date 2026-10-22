CREATE TABLE IF NOT EXISTS `tm_recuperacion` (
  `rec_id` int NOT NULL AUTO_INCREMENT,
  `usu_id` int NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira` datetime NOT NULL,
  `usado` tinyint NOT NULL DEFAULT '0',
  `fech_crea` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rec_id`),
  KEY `idx_tm_recuperacion_token_hash` (`token_hash`),
  KEY `idx_tm_recuperacion_usu_id` (`usu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

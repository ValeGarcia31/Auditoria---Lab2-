CREATE TABLE IF NOT EXISTS `tm_usuario` (
  `usu_id` int NOT NULL AUTO_INCREMENT,
  `usu_nomape` varchar(90) COLLATE utf8mb3_spanish_ci NOT NULL,
  `usu_correo` varchar(50) COLLATE utf8mb3_spanish_ci NOT NULL,
  `usu_pass` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish_ci NOT NULL,
  `fech_crea` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usu_img` varchar(500) COLLATE utf8mb3_spanish_ci DEFAULT NULL,
  `fech_modi` datetime DEFAULT NULL,
  `fech_elim` datetime DEFAULT NULL,
  `fech_acti` datetime DEFAULT NULL,
  `est` int NOT NULL DEFAULT '2',
  PRIMARY KEY (`usu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

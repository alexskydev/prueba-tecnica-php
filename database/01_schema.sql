-- PRUEBA TÉCNICA PHP | Proyecto: Prueba_Narvaez
-- Compatible con MySQL 5.7+/8 y MariaDB 10.4+
-- El usuario de instalación debe poder crear bases de datos y tablas.

CREATE DATABASE IF NOT EXISTS `NombreDB_Narvaez`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `NombreDB_Narvaez`;

CREATE TABLE IF NOT EXISTS `persona` (
  `IdPersona` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `Nombres` VARCHAR(100) NOT NULL,
  `Apellidos` VARCHAR(100) NOT NULL,
  `Identificacion` VARCHAR(20) NOT NULL,
  `Genero` ENUM('M', 'F') NOT NULL,
  `FechaNacimiento` DATE NOT NULL,
  `Contrasena` VARCHAR(255) NOT NULL,
  `Activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`IdPersona`),
  UNIQUE KEY `uq_persona_identificacion` (`Identificacion`),
  KEY `idx_persona_activo` (`Activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

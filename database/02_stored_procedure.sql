-- Ejecute este archivo DESPUÉS de 01_base_datos.sql.
-- Puede importarse con la consola de MySQL o phpMyAdmin.
-- Se crea (o actualiza) persona_respaldo con estructura + datos idénticos a persona.
-- IMPORTANTE: cada ejecución REEMPLAZA el respaldo anterior.

USE `NombreDB_Narvaez`;
DROP PROCEDURE IF EXISTS `sp_clonar_personas`;

DELIMITER $$
CREATE PROCEDURE `sp_clonar_personas`()
BEGIN
    DROP TABLE IF EXISTS `persona_respaldo`;
    CREATE TABLE `persona_respaldo` LIKE `persona`;
    INSERT INTO `persona_respaldo` SELECT * FROM `persona`;
END$$
DELIMITER ;

-- Para probar manualmente:
-- CALL sp_clonar_personas();
-- SELECT * FROM persona_respaldo;

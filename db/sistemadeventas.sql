-- phpMyAdmin SQL Dump
-- version 5.2.1deb1+deb12u1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 11-02-2026 a las 02:38:16
-- Versión del servidor: 10.11.14-MariaDB-0+deb12u2
-- Versión de PHP: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistemadeventas`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`JZ008US00003`@`%` PROCEDURE `limpiar_excepto_usuarios` ()   BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE t VARCHAR(255);

  DECLARE cur CURSOR FOR
    SELECT table_name
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_type = 'BASE TABLE'
      AND table_name <> 'tb_usuarios';

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  SET FOREIGN_KEY_CHECKS = 0;

  OPEN cur;
  loop_tablas: LOOP
    FETCH cur INTO t;
    IF done THEN LEAVE loop_tablas; END IF;

    SET @sql = CONCAT('TRUNCATE TABLE `', t, '`;');
    PREPARE s FROM @sql;
    EXECUTE s;
    DEALLOCATE PREPARE s;
  END LOOP;
  CLOSE cur;

  SET FOREIGN_KEY_CHECKS = 1;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_almacen`
--

CREATE TABLE `tb_almacen` (
  `id_producto` int(11) NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` mediumtext DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `stock_minimo` int(11) DEFAULT NULL,
  `stock_maximo` int(11) DEFAULT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `imagen` mediumtext DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_almacen`
--

INSERT INTO `tb_almacen` (`id_producto`, `codigo`, `nombre`, `descripcion`, `stock`, `stock_minimo`, `stock_maximo`, `precio_compra`, `precio_venta`, `fecha_ingreso`, `imagen`, `id_usuario`, `id_categoria`, `estado`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(55, 'P-00028', 'DEMO PRODUCTO', 'DEMO PRODUCTO PARA VENTA', 14, 2, 15, 12.00, 25.00, '2026-02-02', '6c98d9e9afd62208d736067163282808.png', 2, 10, 1, '2026-02-02 18:29:33', '2026-02-03 19:29:31'),
(56, 'P-00002', 'AROS METALICOS', 'AROS PARA LENTES OFTALMICO', 335, 300, 400, 250.00, 250.00, '2026-02-02', 'b1f492ef459038b16ee42e8a1c789f2c.jpg', 7, 6, 1, '2026-02-02 18:46:37', '2026-02-04 08:37:37'),
(57, 'P-00003', 'AROS DE PASTA', 'AROS DE PASTA', 300, 250, 400, 120.00, 120.00, '2026-02-02', '08020879e2ea956cb335096cb971ab55.webp', 7, 6, 1, '2026-02-02 18:51:24', '2026-02-04 08:39:24'),
(58, 'P-00004', 'ESTUCHE PARA LENTE', 'LENTE', 100, 50, 150, 30.00, 30.00, '2026-02-02', '54dd8605cf9ecec0f2815e770b67c338.jpg', 7, 8, 1, '2026-02-02 18:52:30', '2026-02-04 08:35:53'),
(59, 'P-00005', 'LIQUIDO DE LENTE', 'LIQUIDO DE LENTE', 99, 50, 150, 0.00, 0.00, '2026-02-02', 'defaults/cat_default.png', 2, 5, 1, '2026-02-02 18:53:57', '2026-02-03 04:40:51'),
(69, 'LOF-001', 'Lente oftálmico monofocal', 'Lente monofocal para visión cercana o lejana', 49, 5, 200, 10.00, 35.00, '2026-02-03', NULL, 4, 1, 1, '2026-02-03 04:05:52', '2026-02-03 04:40:51'),
(70, 'LOF-002', 'Lente oftálmico bifocal', 'Lente bifocal con segmento visible', 40, 5, 150, 15.00, 55.00, '2026-02-03', NULL, 4, 1, 1, '2026-02-03 04:05:52', '2026-02-03 04:05:52'),
(71, 'LOF-003', 'Lente oftálmico progresivo', 'Lente progresivo sin línea visible', 998, 0, 0, 120.00, 120.00, '2026-02-03', 'defaults/cat_default.png', 2, 1, 1, '2026-02-03 04:05:52', '2026-02-03 04:37:29'),
(72, 'TRT-BLU-001', 'Filtro Blue Light (Blue Ray)', 'Tratamiento para filtrar luz azul de pantallas digitales', 9999, NULL, NULL, 5.00, 20.00, '2026-02-03', NULL, 4, 1, 1, '2026-02-03 04:05:52', '2026-02-03 04:05:52'),
(73, 'TRT-AR-001', 'Lente oftálmico Anti reflejo', 'Reduce reflejos, mejora la nitidez y estética del lente', 9999, 0, 0, 0.00, 25.00, '2026-02-03', 'defaults/cat_default.png', 7, 1, 1, '2026-02-03 04:05:52', '2026-02-03 20:41:44'),
(74, 'TRT-TRA-001', 'Lentes Transitions', 'Lentes fotocromáticos que se adaptan a la luz solar', 9999, 0, 0, 0.00, 2500.00, '2026-02-03', '37a6ef44bf56c3f14a330347f2a7faf8.jpg', 2, 1, 1, '2026-02-03 04:05:52', '2026-02-02 19:09:59'),
(75, 'MAT-CR39-001', 'Lente CR-39', 'Material plástico estándar, buena calidad óptica y bajo costo', 9999, NULL, NULL, 6.00, 20.00, '2026-02-03', NULL, 4, 1, 1, '2026-02-03 04:05:52', '2026-02-03 04:05:52'),
(76, 'SOL-001', 'Lentes de sol polarizados', 'Lentes de sol con protección UV y polarización', 15, 10, 20, 120.00, 250.00, '2026-02-03', 'defaults/cat_default.png', 2, 3, 1, '2026-02-03 04:05:52', '2026-02-02 19:07:31'),
(77, 'MAT-POLY-001', 'Lente Policarbonato', 'Material resistente a impactos, ideal para niños y uso deportivo', 9999, NULL, NULL, 10.00, 35.00, '2026-02-03', NULL, 4, 1, 1, '2026-02-03 04:05:52', '2026-02-03 04:05:52');

--
-- Disparadores `tb_almacen`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_almacen_del` AFTER DELETE ON `tb_almacen` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_almacen','DELETE',CAST(OLD.`id_producto` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_producto', OLD.`id_producto`, 'codigo', OLD.`codigo`, 'nombre', OLD.`nombre`, 'stock', OLD.`stock`, 'stock_minimo', OLD.`stock_minimo`, 'stock_maximo', OLD.`stock_maximo`, 'precio_compra', OLD.`precio_compra`, 'precio_venta', OLD.`precio_venta`, 'fecha_ingreso', OLD.`fecha_ingreso`, 'id_usuario', OLD.`id_usuario`, 'id_categoria', OLD.`id_categoria`, 'estado', OLD.`estado`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_almacen_ins` AFTER INSERT ON `tb_almacen` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_almacen','INSERT',CAST(NEW.`id_producto` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_producto', NEW.`id_producto`, 'codigo', NEW.`codigo`, 'nombre', NEW.`nombre`, 'stock', NEW.`stock`, 'stock_minimo', NEW.`stock_minimo`, 'stock_maximo', NEW.`stock_maximo`, 'precio_compra', NEW.`precio_compra`, 'precio_venta', NEW.`precio_venta`, 'fecha_ingreso', NEW.`fecha_ingreso`, 'id_usuario', NEW.`id_usuario`, 'id_categoria', NEW.`id_categoria`, 'estado', NEW.`estado`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_almacen_upd` AFTER UPDATE ON `tb_almacen` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_almacen','UPDATE',CAST(NEW.`id_producto` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_producto', OLD.`id_producto`, 'codigo', OLD.`codigo`, 'nombre', OLD.`nombre`, 'stock', OLD.`stock`, 'stock_minimo', OLD.`stock_minimo`, 'stock_maximo', OLD.`stock_maximo`, 'precio_compra', OLD.`precio_compra`, 'precio_venta', OLD.`precio_venta`, 'fecha_ingreso', OLD.`fecha_ingreso`, 'id_usuario', OLD.`id_usuario`, 'id_categoria', OLD.`id_categoria`, 'estado', OLD.`estado`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_producto', NEW.`id_producto`, 'codigo', NEW.`codigo`, 'nombre', NEW.`nombre`, 'stock', NEW.`stock`, 'stock_minimo', NEW.`stock_minimo`, 'stock_maximo', NEW.`stock_maximo`, 'precio_compra', NEW.`precio_compra`, 'precio_venta', NEW.`precio_venta`, 'fecha_ingreso', NEW.`fecha_ingreso`, 'id_usuario', NEW.`id_usuario`, 'id_categoria', NEW.`id_categoria`, 'estado', NEW.`estado`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_auditoria`
--

CREATE TABLE `tb_auditoria` (
  `id_auditoria` bigint(20) UNSIGNED NOT NULL,
  `tabla` varchar(64) NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `pk` varchar(128) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `usuario_email` varchar(120) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`antes`)),
  `despues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`despues`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tb_auditoria`
--

INSERT INTO `tb_auditoria` (`id_auditoria`, `tabla`, `accion`, `pk`, `usuario_id`, `usuario_email`, `ip`, `user_agent`, `fecha`, `antes`, `despues`) VALUES
(1, 'tb_roles', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-01 07:42:32', NULL, '{\"id_rol\": 1, \"rol\": \"Administrador\", \"estado\": \"1\", \"fyh_creacion\": \"2026-02-01 08:42:32\", \"fyh_actualizacion\": \"2026-02-01 08:42:32\"}'),
(2, 'tb_usuarios', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-01 07:43:05', NULL, '{\"id_usuario\": 2, \"nombres\": \"Administrador\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 08:43:05\", \"estado\": \"ACTIVO\"}'),
(3, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 07:43:22', '{\"id_usuario\": 2, \"nombres\": \"Administrador\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 08:43:05\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Administrador\", \"email\": \"admin@devzamora.com\", \"token\": \"d58304fa4e5777aeb42dcb1fca623b8e24ef12bcfc5bf37ef90370e2f6f58a87\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 08:43:22\", \"estado\": \"ACTIVO\"}'),
(4, 'tb_roles', 'UPDATE', '1', NULL, NULL, NULL, NULL, '2026-02-01 07:44:29', '{\"id_rol\": 1, \"rol\": \"Administrador\", \"estado\": \"1\", \"fyh_creacion\": \"2026-02-01 08:42:32\", \"fyh_actualizacion\": \"2026-02-01 08:42:32\"}', '{\"id_rol\": 1, \"rol\": \"ADMINISTRADOR \", \"estado\": \"1\", \"fyh_creacion\": \"2026-02-01 08:42:32\", \"fyh_actualizacion\": \"2026-02-01 08:44:29\"}'),
(5, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 07:45:30', '{\"id_usuario\": 2, \"nombres\": \"Administrador\", \"email\": \"admin@devzamora.com\", \"token\": \"d58304fa4e5777aeb42dcb1fca623b8e24ef12bcfc5bf37ef90370e2f6f58a87\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 08:43:22\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"d58304fa4e5777aeb42dcb1fca623b8e24ef12bcfc5bf37ef90370e2f6f58a87\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 08:45:30\", \"estado\": \"ACTIVO\"}'),
(6, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 07:47:24', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"d58304fa4e5777aeb42dcb1fca623b8e24ef12bcfc5bf37ef90370e2f6f58a87\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 08:45:30\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 08:47:24\", \"estado\": \"ACTIVO\"}'),
(7, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 18:37:17', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 08:47:24\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"856d20b512852b2bf94113b5c46dc264f2067223348b65275a4d33cbe77daf72\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 19:37:17\", \"estado\": \"ACTIVO\"}'),
(8, 'tb_usuarios', 'INSERT', '3', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 18:39:25', NULL, '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 19:39:25\", \"estado\": \"ACTIVO\"}'),
(9, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 18:39:53', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"856d20b512852b2bf94113b5c46dc264f2067223348b65275a4d33cbe77daf72\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 19:37:17\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 19:39:53\", \"estado\": \"ACTIVO\"}'),
(10, 'tb_usuarios', 'UPDATE', '3', NULL, NULL, NULL, NULL, '2026-02-01 18:40:02', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 19:39:25\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"830c0b121b934fd96d1bd9aba3dd3e2a6d7bd82b32943329c143e1e836eab98c\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 19:40:02\", \"estado\": \"ACTIVO\"}'),
(11, 'tb_usuarios', 'UPDATE', '3', NULL, NULL, NULL, NULL, '2026-02-01 18:40:11', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"830c0b121b934fd96d1bd9aba3dd3e2a6d7bd82b32943329c143e1e836eab98c\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 19:40:02\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 19:40:11\", \"estado\": \"ACTIVO\"}'),
(12, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 18:41:27', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 19:39:53\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"94ed0c6b485d707a6875028300fce68fec726af0da3df437b7689eb0a465d85a\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 19:41:27\", \"estado\": \"ACTIVO\"}'),
(13, 'tb_usuarios', 'UPDATE', '3', NULL, NULL, NULL, NULL, '2026-02-01 18:45:04', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 19:40:11\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"b5dd4a3569a98218a27c30fed778e2c5bc97a3d4faf437e99c465af5c9464f08\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 19:45:04\", \"estado\": \"ACTIVO\"}'),
(14, 'tb_permisos', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 1, \"clave\": \"usuarios.ver\", \"descripcion\": \"Ver listado de usuarios\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(15, 'tb_permisos', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 2, \"clave\": \"usuarios.crear\", \"descripcion\": \"Crear usuarios\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(16, 'tb_permisos', 'INSERT', '3', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 3, \"clave\": \"usuarios.editar\", \"descripcion\": \"Editar datos de usuarios\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(17, 'tb_permisos', 'INSERT', '4', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 4, \"clave\": \"usuarios.password\", \"descripcion\": \"Cambiar contraseñas de usuarios\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(18, 'tb_permisos', 'INSERT', '5', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 5, \"clave\": \"usuarios.estado\", \"descripcion\": \"Activar/Desactivar usuarios\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(19, 'tb_permisos', 'INSERT', '6', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 6, \"clave\": \"roles.ver\", \"descripcion\": \"Ver roles\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(20, 'tb_permisos', 'INSERT', '7', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 7, \"clave\": \"cajas.ver\", \"descripcion\": \"Ver caja\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(21, 'tb_permisos', 'INSERT', '8', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 8, \"clave\": \"cajas.aperturar\", \"descripcion\": \"Aperturar caja\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(22, 'tb_permisos', 'INSERT', '9', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 9, \"clave\": \"cajas.movimiento.crear\", \"descripcion\": \"Registrar movimientos de caja\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(23, 'tb_permisos', 'INSERT', '10', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 10, \"clave\": \"cajas.cerrar\", \"descripcion\": \"Cerrar caja\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(24, 'tb_permisos', 'INSERT', '11', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 11, \"clave\": \"cajas.movimiento.anular\", \"descripcion\": \"Anular movimientos de caja\", \"created_at\": \"2026-01-26 10:45:11\"}'),
(25, 'tb_permisos', 'INSERT', '12', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 12, \"clave\": \"almacen.ver\", \"descripcion\": \"Ver productos en almacén\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(26, 'tb_permisos', 'INSERT', '13', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 13, \"clave\": \"almacen.crear\", \"descripcion\": \"Crear productos en almacén\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(27, 'tb_permisos', 'INSERT', '14', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 14, \"clave\": \"almacen.actualizar\", \"descripcion\": \"Actualizar productos en almacén\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(28, 'tb_permisos', 'INSERT', '15', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 15, \"clave\": \"almacen.eliminar\", \"descripcion\": \"Eliminar/Desactivar productos en almacén\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(29, 'tb_permisos', 'INSERT', '16', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 16, \"clave\": \"categorias.ver\", \"descripcion\": \"Ver categorías\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(30, 'tb_permisos', 'INSERT', '17', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 17, \"clave\": \"categorias.crear\", \"descripcion\": \"Crear categorías\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(31, 'tb_permisos', 'INSERT', '18', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 18, \"clave\": \"categorias.actualizar\", \"descripcion\": \"Actualizar categorías\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(32, 'tb_permisos', 'INSERT', '19', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 19, \"clave\": \"categorias.eliminar\", \"descripcion\": \"Eliminar categorías\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(33, 'tb_permisos', 'INSERT', '20', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 20, \"clave\": \"clientes.ver\", \"descripcion\": \"Ver clientes\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(34, 'tb_permisos', 'INSERT', '21', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 21, \"clave\": \"clientes.crear\", \"descripcion\": \"Crear clientes\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(35, 'tb_permisos', 'INSERT', '22', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 22, \"clave\": \"clientes.actualizar\", \"descripcion\": \"Actualizar clientes\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(36, 'tb_permisos', 'INSERT', '23', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 23, \"clave\": \"clientes.eliminar\", \"descripcion\": \"Eliminar/Desactivar clientes\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(37, 'tb_permisos', 'INSERT', '24', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 24, \"clave\": \"proveedores.ver\", \"descripcion\": \"Ver proveedores\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(38, 'tb_permisos', 'INSERT', '25', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 25, \"clave\": \"proveedores.crear\", \"descripcion\": \"Crear proveedores\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(39, 'tb_permisos', 'INSERT', '26', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 26, \"clave\": \"proveedores.actualizar\", \"descripcion\": \"Actualizar proveedores\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(40, 'tb_permisos', 'INSERT', '27', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 27, \"clave\": \"proveedores.eliminar\", \"descripcion\": \"Eliminar/Desactivar proveedores\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(41, 'tb_permisos', 'INSERT', '28', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 28, \"clave\": \"compras.ver\", \"descripcion\": \"Ver compras\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(42, 'tb_permisos', 'INSERT', '29', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 29, \"clave\": \"compras.crear\", \"descripcion\": \"Registrar compras\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(43, 'tb_permisos', 'INSERT', '30', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 30, \"clave\": \"compras.actualizar\", \"descripcion\": \"Actualizar compras\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(44, 'tb_permisos', 'INSERT', '31', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 31, \"clave\": \"compras.eliminar\", \"descripcion\": \"Anular compras\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(45, 'tb_permisos', 'INSERT', '32', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 32, \"clave\": \"ventas.ver\", \"descripcion\": \"Ver ventas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(46, 'tb_permisos', 'INSERT', '33', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 33, \"clave\": \"ventas.crear\", \"descripcion\": \"Registrar ventas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(47, 'tb_permisos', 'INSERT', '34', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 34, \"clave\": \"ventas.actualizar\", \"descripcion\": \"Actualizar ventas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(48, 'tb_permisos', 'INSERT', '35', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 35, \"clave\": \"ventas.eliminar\", \"descripcion\": \"Anular ventas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(49, 'tb_permisos', 'INSERT', '36', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 36, \"clave\": \"citas.ver\", \"descripcion\": \"Ver citas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(50, 'tb_permisos', 'INSERT', '37', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 37, \"clave\": \"citas.crear\", \"descripcion\": \"Crear citas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(51, 'tb_permisos', 'INSERT', '38', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 38, \"clave\": \"citas.actualizar\", \"descripcion\": \"Actualizar citas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(52, 'tb_permisos', 'INSERT', '39', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 39, \"clave\": \"citas.eliminar\", \"descripcion\": \"Cancelar/Eliminar citas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(53, 'tb_permisos', 'INSERT', '40', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 40, \"clave\": \"examenes.ver\", \"descripcion\": \"Ver exámenes optométricos\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(54, 'tb_permisos', 'INSERT', '41', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 41, \"clave\": \"examenes.crear\", \"descripcion\": \"Crear exámenes\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(55, 'tb_permisos', 'INSERT', '42', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 42, \"clave\": \"examenes.actualizar\", \"descripcion\": \"Actualizar exámenes\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(56, 'tb_permisos', 'INSERT', '43', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 43, \"clave\": \"examenes.eliminar\", \"descripcion\": \"Eliminar exámenes\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(57, 'tb_permisos', 'INSERT', '44', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 44, \"clave\": \"recetas.ver\", \"descripcion\": \"Ver recetas ópticas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(58, 'tb_permisos', 'INSERT', '45', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 45, \"clave\": \"recetas.crear\", \"descripcion\": \"Crear recetas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(59, 'tb_permisos', 'INSERT', '46', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 46, \"clave\": \"recetas.actualizar\", \"descripcion\": \"Actualizar recetas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(60, 'tb_permisos', 'INSERT', '47', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 47, \"clave\": \"recetas.eliminar\", \"descripcion\": \"Eliminar recetas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(61, 'tb_permisos', 'INSERT', '48', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 48, \"clave\": \"notas.ver\", \"descripcion\": \"Ver notas del optometrista\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(62, 'tb_permisos', 'INSERT', '49', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 49, \"clave\": \"notas.crear\", \"descripcion\": \"Crear notas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(63, 'tb_permisos', 'INSERT', '50', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 50, \"clave\": \"notas.actualizar\", \"descripcion\": \"Actualizar notas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(64, 'tb_permisos', 'INSERT', '51', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 51, \"clave\": \"notas.eliminar\", \"descripcion\": \"Eliminar notas\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(65, 'tb_permisos', 'INSERT', '52', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 52, \"clave\": \"horario.ver\", \"descripcion\": \"Ver horario laboral\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(66, 'tb_permisos', 'INSERT', '53', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 53, \"clave\": \"horario.crear\", \"descripcion\": \"Crear horario laboral\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(67, 'tb_permisos', 'INSERT', '54', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 54, \"clave\": \"horario.actualizar\", \"descripcion\": \"Actualizar horario laboral\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(68, 'tb_permisos', 'INSERT', '55', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 55, \"clave\": \"horario.eliminar\", \"descripcion\": \"Eliminar horario laboral\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(69, 'tb_permisos', 'INSERT', '56', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 56, \"clave\": \"roles.crear\", \"descripcion\": \"Crear roles\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(70, 'tb_permisos', 'INSERT', '57', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 57, \"clave\": \"roles.actualizar\", \"descripcion\": \"Actualizar roles\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(71, 'tb_permisos', 'INSERT', '58', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 58, \"clave\": \"roles.eliminar\", \"descripcion\": \"Eliminar roles\", \"created_at\": \"2026-01-26 21:18:38\"}'),
(72, 'tb_permisos', 'INSERT', '59', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 59, \"clave\": \"usuarios.actualizar\", \"descripcion\": \"Actualizar usuarios\", \"created_at\": \"2026-01-26 21:23:58\"}'),
(73, 'tb_permisos', 'INSERT', '60', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 60, \"clave\": \"usuarios.eliminar\", \"descripcion\": \"Desactivar usuarios\", \"created_at\": \"2026-01-26 21:23:58\"}'),
(74, 'tb_permisos', 'INSERT', '96', NULL, NULL, NULL, NULL, '2026-02-01 18:48:08', NULL, '{\"id_permiso\": 96, \"clave\": \"*\", \"descripcion\": \"Acceso total (wildcard)\", \"created_at\": \"2026-01-26 23:28:56\"}'),
(75, 'tb_roles', 'UPDATE', '1', NULL, NULL, NULL, NULL, '2026-02-01 18:52:14', '{\"id_rol\": 1, \"rol\": \"ADMINISTRADOR \", \"estado\": \"1\", \"fyh_creacion\": \"2026-02-01 08:42:32\", \"fyh_actualizacion\": \"2026-02-01 08:44:29\"}', '{\"id_rol\": 1, \"rol\": \"ADMINISTRADOR \", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 08:42:32\", \"fyh_actualizacion\": \"2026-02-01 19:52:14\"}'),
(76, 'tb_permisos', 'INSERT', '97', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 97, \"clave\": \"ventas.devoluciones\", \"descripcion\": \"Registrar devoluciones de ventas\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(77, 'tb_permisos', 'INSERT', '98', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 98, \"clave\": \"ventas.pagos\", \"descripcion\": \"Registrar pagos/abonos de ventas a crédito\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(78, 'tb_permisos', 'INSERT', '99', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 99, \"clave\": \"ventas.imprimir\", \"descripcion\": \"Imprimir ticket/recibo de venta\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(79, 'tb_permisos', 'INSERT', '100', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 100, \"clave\": \"ventas.detalle.ver\", \"descripcion\": \"Ver detalle de una venta\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(80, 'tb_permisos', 'INSERT', '101', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 101, \"clave\": \"cajas.reporte\", \"descripcion\": \"Ver reporte/corte de caja\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(81, 'tb_permisos', 'INSERT', '102', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 102, \"clave\": \"cajas.imprimir\", \"descripcion\": \"Imprimir corte de caja\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(82, 'tb_permisos', 'INSERT', '103', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 103, \"clave\": \"cajas.movimiento.ver\", \"descripcion\": \"Ver movimientos de caja\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(83, 'tb_permisos', 'INSERT', '104', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 104, \"clave\": \"clientes.expediente\", \"descripcion\": \"Ver expediente del cliente (resumen/exámenes/recetas/notas)\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(84, 'tb_permisos', 'INSERT', '105', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 105, \"clave\": \"compras.detalle.ver\", \"descripcion\": \"Ver detalle de compra\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(85, 'tb_permisos', 'INSERT', '106', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 106, \"clave\": \"compras.imprimir\", \"descripcion\": \"Imprimir comprobante de compra\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(86, 'tb_permisos', 'INSERT', '107', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 107, \"clave\": \"almacen.stock\", \"descripcion\": \"Ajustar stock manualmente\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(87, 'tb_permisos', 'INSERT', '108', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 108, \"clave\": \"almacen.kardex\", \"descripcion\": \"Ver kardex/movimientos de inventario\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(88, 'tb_permisos', 'INSERT', '109', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 109, \"clave\": \"permisos.ver\", \"descripcion\": \"Ver listado de permisos\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(89, 'tb_permisos', 'INSERT', '110', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 110, \"clave\": \"permisos.asignar\", \"descripcion\": \"Asignar permisos a roles\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(90, 'tb_permisos', 'INSERT', '111', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 111, \"clave\": \"reportes.ventas\", \"descripcion\": \"Ver reportes de ventas\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(91, 'tb_permisos', 'INSERT', '112', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 112, \"clave\": \"reportes.caja\", \"descripcion\": \"Ver reportes de caja\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(92, 'tb_permisos', 'INSERT', '113', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 113, \"clave\": \"reportes.inventario\", \"descripcion\": \"Ver reportes de inventario\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(93, 'tb_permisos', 'INSERT', '114', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 114, \"clave\": \"reportes.compras\", \"descripcion\": \"Ver reportes de compras\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(94, 'tb_permisos', 'INSERT', '115', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 115, \"clave\": \"reportes.clientes\", \"descripcion\": \"Ver reportes de clientes\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(95, 'tb_permisos', 'INSERT', '116', NULL, NULL, NULL, NULL, '2026-02-01 18:52:58', NULL, '{\"id_permiso\": 116, \"clave\": \"auditoria.ver\", \"descripcion\": \"Ver bitácora de auditoría\", \"created_at\": \"2026-02-01 19:52:58\"}'),
(96, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 19:01:34', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"94ed0c6b485d707a6875028300fce68fec726af0da3df437b7689eb0a465d85a\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 19:41:27\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:01:34\", \"estado\": \"ACTIVO\"}'),
(97, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 19:01:40', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:01:34\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"531caef46d5d43ad5f3e58d4b787e4b83c1ec53e4e69f8741998247e474b98be\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:01:40\", \"estado\": \"ACTIVO\"}'),
(98, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 19:15:42', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"531caef46d5d43ad5f3e58d4b787e4b83c1ec53e4e69f8741998247e474b98be\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:01:40\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:15:42\", \"estado\": \"ACTIVO\"}'),
(99, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 19:15:48', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:15:42\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"492ea1a04ac91d885ec60967c8c6361a5bc87dd20dad5727c5b163d29ac0bc01\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:15:48\", \"estado\": \"ACTIVO\"}'),
(100, 'tb_usuarios', 'INSERT', '4', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 19:20:56', NULL, '{\"id_usuario\": 4, \"nombres\": \"Roberto Ruiz\", \"email\": \"roberto@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 20:20:56\", \"fyh_actualizacion\": \"2026-02-01 20:20:56\", \"estado\": \"ACTIVO\"}'),
(101, 'tb_categorias', 'INSERT', '1', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 19:22:37', NULL, '{\"id_categoria\": 1, \"nombre_categoria\": \"LENTES\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 11:22:37\", \"fyh_actualizacion\": \"2026-02-01 20:22:37\"}'),
(102, 'tb_categorias', 'INSERT', '2', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 19:25:22', NULL, '{\"id_categoria\": 2, \"nombre_categoria\": \"EXAMENES\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 11:25:22\", \"fyh_actualizacion\": \"2026-02-01 20:25:22\"}'),
(103, 'tb_citas_bloqueos', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-01 19:36:53', NULL, '{\"id_bloqueo\": 1, \"fecha\": \"2026-08-01\", \"hora_inicio\": \"08:36:00\", \"hora_fin\": \"20:36:00\", \"motivo\": \"FERIADO NACIONAL\", \"activo\": 1, \"fyh_creacion\": \"2026-02-01 20:36:53\", \"fyh_actualizacion\": \"2026-02-01 20:36:53\"}'),
(104, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 19:43:33', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"492ea1a04ac91d885ec60967c8c6361a5bc87dd20dad5727c5b163d29ac0bc01\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:15:48\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:43:33\", \"estado\": \"ACTIVO\"}'),
(105, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 19:50:41', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:43:33\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"34f5b878815e27b21176bd50a99ff1f2a685b75b66957f608513a53076353c42\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:50:41\", \"estado\": \"ACTIVO\"}'),
(106, 'tb_usuarios', 'INSERT', '5', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 19:57:13', NULL, '{\"id_usuario\": 5, \"nombres\": \"wewew\", \"email\": \"desarrollozamora@gmail.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 20:57:13\", \"fyh_actualizacion\": \"2026-02-01 20:57:13\", \"estado\": \"ACTIVO\"}'),
(107, 'tb_usuarios', 'DELETE', '5', NULL, NULL, NULL, NULL, '2026-02-01 19:57:32', '{\"id_usuario\": 5, \"nombres\": \"wewew\", \"email\": \"desarrollozamora@gmail.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 20:57:13\", \"fyh_actualizacion\": \"2026-02-01 20:57:13\", \"estado\": \"ACTIVO\"}', NULL),
(108, 'tb_usuarios', 'INSERT', '6', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 19:58:19', NULL, '{\"id_usuario\": 6, \"nombres\": \"JOUSE JOSUE\", \"email\": \"josue@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 20:58:19\", \"fyh_actualizacion\": \"2026-02-01 20:58:19\", \"estado\": \"ACTIVO\"}'),
(109, 'tb_usuarios', 'DELETE', '6', NULL, NULL, NULL, NULL, '2026-02-01 19:58:30', '{\"id_usuario\": 6, \"nombres\": \"JOUSE JOSUE\", \"email\": \"josue@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 20:58:19\", \"fyh_actualizacion\": \"2026-02-01 20:58:19\", \"estado\": \"ACTIVO\"}', NULL),
(110, 'tb_roles', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-01 20:04:05', NULL, '{\"id_rol\": 2, \"rol\": \"CAJERO\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:04:05\", \"fyh_actualizacion\": \"2026-02-01 21:04:05\"}'),
(111, 'tb_categorias', 'INSERT', '3', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 20:10:25', NULL, '{\"id_categoria\": 3, \"nombre_categoria\": \"LENTES DE SOL\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:10:25\", \"fyh_actualizacion\": \"2026-02-01 21:10:25\"}'),
(112, 'tb_categorias', 'INSERT', '4', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 20:10:40', NULL, '{\"id_categoria\": 4, \"nombre_categoria\": \"LENTES DE CONTACTOS\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:10:40\", \"fyh_actualizacion\": \"2026-02-01 21:10:40\"}'),
(113, 'tb_categorias', 'UPDATE', '1', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 20:10:56', '{\"id_categoria\": 1, \"nombre_categoria\": \"LENTES\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 11:22:37\", \"fyh_actualizacion\": \"2026-02-01 20:22:37\"}', '{\"id_categoria\": 1, \"nombre_categoria\": \"LENTES OFTALMICOS\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 11:22:37\", \"fyh_actualizacion\": \"2026-02-01 12:10:56\"}'),
(114, 'tb_categorias', 'INSERT', '5', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 20:11:12', NULL, '{\"id_categoria\": 5, \"nombre_categoria\": \"PRODUCTOS PARA LIMPIEZA\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:11:12\", \"fyh_actualizacion\": \"2026-02-01 21:11:12\"}'),
(115, 'tb_categorias', 'INSERT', '6', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 20:11:41', NULL, '{\"id_categoria\": 6, \"nombre_categoria\": \"MONTURAS / ARMAZONES\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:11:41\", \"fyh_actualizacion\": \"2026-02-01 21:11:41\"}'),
(116, 'tb_categorias', 'INSERT', '7', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 20:12:21', NULL, '{\"id_categoria\": 7, \"nombre_categoria\": \"SERVICIOS TÉCNICOS\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:21\", \"fyh_actualizacion\": \"2026-02-01 21:12:21\"}'),
(117, 'tb_categorias', 'INSERT', '8', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 20:12:35', NULL, '{\"id_categoria\": 8, \"nombre_categoria\": \"ACCESORIOS\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:35\", \"fyh_actualizacion\": \"2026-02-01 21:12:35\"}'),
(118, 'tb_categorias', 'INSERT', '9', 2, '', '35.149.44.17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 20:12:52', NULL, '{\"id_categoria\": 9, \"nombre_categoria\": \"PRODUCTOS RELACIONADOS CON SALUD VISUAL\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:52\", \"fyh_actualizacion\": \"2026-02-01 21:12:52\"}'),
(119, 'tb_usuarios', 'UPDATE', '3', NULL, NULL, NULL, NULL, '2026-02-01 20:22:34', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"b5dd4a3569a98218a27c30fed778e2c5bc97a3d4faf437e99c465af5c9464f08\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 19:45:04\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"5ad9372b0d68cfd3a2ca2ad73fb5c30f0a509da43014cc70f8411048137fe1fa\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 21:22:34\", \"estado\": \"ACTIVO\"}'),
(120, 'tb_almacen', 'INSERT', '28', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 28, \"codigo\": \"LO-MONO\", \"nombre\": \"Lente Monofocal Básico\", \"stock\": 50, \"stock_minimo\": 10, \"stock_maximo\": 100, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(121, 'tb_almacen', 'INSERT', '29', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 29, \"codigo\": \"LO-ANTIREF\", \"nombre\": \"Lente Antirreflejo\", \"stock\": 40, \"stock_minimo\": 10, \"stock_maximo\": 80, \"precio_compra\": 18.00, \"precio_venta\": 40.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(122, 'tb_almacen', 'INSERT', '30', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 30, \"codigo\": \"LO-BIFO\", \"nombre\": \"Lente Bifocal\", \"stock\": 30, \"stock_minimo\": 5, \"stock_maximo\": 60, \"precio_compra\": 22.00, \"precio_venta\": 55.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(123, 'tb_almacen', 'INSERT', '31', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 31, \"codigo\": \"LO-PROG\", \"nombre\": \"Lente Progresivo\", \"stock\": 20, \"stock_minimo\": 5, \"stock_maximo\": 40, \"precio_compra\": 45.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(124, 'tb_almacen', 'INSERT', '32', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 32, \"codigo\": \"LO-BLUE\", \"nombre\": \"Lente Filtro Azul\", \"stock\": 60, \"stock_minimo\": 15, \"stock_maximo\": 120, \"precio_compra\": 14.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(125, 'tb_almacen', 'INSERT', '33', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 33, \"codigo\": \"EX-VISUAL\", \"nombre\": \"Examen Visual General\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 20.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 2, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(126, 'tb_almacen', 'INSERT', '34', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 34, \"codigo\": \"EX-CONTROL\", \"nombre\": \"Examen de Control\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 10.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 2, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(127, 'tb_almacen', 'INSERT', '35', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 35, \"codigo\": \"LS-NORMAL\", \"nombre\": \"Lentes de Sol UV400\", \"stock\": 40, \"stock_minimo\": 10, \"stock_maximo\": 80, \"precio_compra\": 15.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 3, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(128, 'tb_almacen', 'INSERT', '36', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 36, \"codigo\": \"LS-POLAR\", \"nombre\": \"Lentes de Sol Polarizados\", \"stock\": 30, \"stock_minimo\": 10, \"stock_maximo\": 60, \"precio_compra\": 22.00, \"precio_venta\": 60.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 3, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(129, 'tb_almacen', 'INSERT', '37', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 37, \"codigo\": \"LS-GRAD\", \"nombre\": \"Lentes de Sol Graduados\", \"stock\": 20, \"stock_minimo\": 5, \"stock_maximo\": 40, \"precio_compra\": 35.00, \"precio_venta\": 90.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 3, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(130, 'tb_almacen', 'INSERT', '38', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 38, \"codigo\": \"LC-BLAN\", \"nombre\": \"Lentes de Contacto Blandos\", \"stock\": 100, \"stock_minimo\": 20, \"stock_maximo\": 200, \"precio_compra\": 8.00, \"precio_venta\": 20.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 4, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(131, 'tb_almacen', 'INSERT', '39', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 39, \"codigo\": \"LC-TORIC\", \"nombre\": \"Lentes de Contacto Tóricos\", \"stock\": 60, \"stock_minimo\": 10, \"stock_maximo\": 120, \"precio_compra\": 12.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 4, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(132, 'tb_almacen', 'INSERT', '40', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 40, \"codigo\": \"LC-COLOR\", \"nombre\": \"Lentes de Contacto de Color\", \"stock\": 50, \"stock_minimo\": 10, \"stock_maximo\": 100, \"precio_compra\": 9.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 4, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(133, 'tb_almacen', 'INSERT', '41', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 41, \"codigo\": \"PL-SOL\", \"nombre\": \"Solución Multiuso\", \"stock\": 120, \"stock_minimo\": 30, \"stock_maximo\": 250, \"precio_compra\": 4.00, \"precio_venta\": 9.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 5, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(134, 'tb_almacen', 'INSERT', '42', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 42, \"codigo\": \"PL-GOT\", \"nombre\": \"Gotas Lubricantes\", \"stock\": 80, \"stock_minimo\": 20, \"stock_maximo\": 160, \"precio_compra\": 3.00, \"precio_venta\": 8.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 5, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(135, 'tb_almacen', 'INSERT', '43', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 43, \"codigo\": \"PL-SPRAY\", \"nombre\": \"Spray Limpiador\", \"stock\": 100, \"stock_minimo\": 20, \"stock_maximo\": 200, \"precio_compra\": 2.50, \"precio_venta\": 7.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 5, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(136, 'tb_almacen', 'INSERT', '44', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 44, \"codigo\": \"MO-ACET\", \"nombre\": \"Montura de Acetato\", \"stock\": 60, \"stock_minimo\": 15, \"stock_maximo\": 120, \"precio_compra\": 10.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(137, 'tb_almacen', 'INSERT', '45', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 45, \"codigo\": \"MO-META\", \"nombre\": \"Montura Metálica\", \"stock\": 50, \"stock_minimo\": 15, \"stock_maximo\": 100, \"precio_compra\": 12.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(138, 'tb_almacen', 'INSERT', '46', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 46, \"codigo\": \"MO-NINO\", \"nombre\": \"Montura Infantil\", \"stock\": 40, \"stock_minimo\": 10, \"stock_maximo\": 80, \"precio_compra\": 9.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(139, 'tb_almacen', 'INSERT', '47', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 47, \"codigo\": \"ST-AJUSTE\", \"nombre\": \"Ajuste de Gafas\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 5.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 7, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(140, 'tb_almacen', 'INSERT', '48', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 48, \"codigo\": \"ST-REP\", \"nombre\": \"Reparación Básica\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 12.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 7, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(141, 'tb_almacen', 'INSERT', '49', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 49, \"codigo\": \"ST-LIMP\", \"nombre\": \"Limpieza Ultrasónica\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 6.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 7, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(142, 'tb_almacen', 'INSERT', '50', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 50, \"codigo\": \"AC-EST\", \"nombre\": \"Estuche para Gafas\", \"stock\": 120, \"stock_minimo\": 30, \"stock_maximo\": 240, \"precio_compra\": 2.00, \"precio_venta\": 6.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(143, 'tb_almacen', 'INSERT', '51', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 51, \"codigo\": \"AC-PANO\", \"nombre\": \"Paño de Microfibra\", \"stock\": 200, \"stock_minimo\": 50, \"stock_maximo\": 400, \"precio_compra\": 0.80, \"precio_venta\": 2.50, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(144, 'tb_almacen', 'INSERT', '52', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 52, \"codigo\": \"AC-CORD\", \"nombre\": \"Cordón para Gafas\", \"stock\": 150, \"stock_minimo\": 30, \"stock_maximo\": 300, \"precio_compra\": 1.00, \"precio_venta\": 4.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(145, 'tb_almacen', 'INSERT', '53', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 53, \"codigo\": \"SV-OCULAR\", \"nombre\": \"Protector Ocular\", \"stock\": 50, \"stock_minimo\": 10, \"stock_maximo\": 100, \"precio_compra\": 6.00, \"precio_venta\": 15.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 9, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}'),
(146, 'tb_almacen', 'INSERT', '54', NULL, NULL, NULL, NULL, '2026-02-01 20:26:41', NULL, '{\"id_producto\": 54, \"codigo\": \"SV-VIT\", \"nombre\": \"Vitaminas Oculares\", \"stock\": 40, \"stock_minimo\": 10, \"stock_maximo\": 80, \"precio_compra\": 7.00, \"precio_venta\": 18.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 9, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}');
INSERT INTO `tb_auditoria` (`id_auditoria`, `tabla`, `accion`, `pk`, `usuario_id`, `usuario_email`, `ip`, `user_agent`, `fecha`, `antes`, `despues`) VALUES
(147, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 23:55:33', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"34f5b878815e27b21176bd50a99ff1f2a685b75b66957f608513a53076353c42\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-01 20:50:41\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"4a21f4d5a05a6c01e849f00924830e2da96101ddcb34e9c3403f29a2a3d15355\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-02 00:55:33\", \"estado\": \"ACTIVO\"}'),
(148, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-01 23:56:04', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"4a21f4d5a05a6c01e849f00924830e2da96101ddcb34e9c3403f29a2a3d15355\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-02 00:55:33\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-02 00:56:04\", \"estado\": \"ACTIVO\"}'),
(149, 'tb_usuarios', 'UPDATE', '3', NULL, NULL, NULL, NULL, '2026-02-02 15:11:57', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"5ad9372b0d68cfd3a2ca2ad73fb5c30f0a509da43014cc70f8411048137fe1fa\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-01 21:22:34\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"ac541906668f96305f6470b72e8483352d9f8da2f2407d20d2b7e40fab08e6c7\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-02 16:11:57\", \"estado\": \"ACTIVO\"}'),
(150, 'tb_usuarios', 'UPDATE', '3', NULL, NULL, NULL, NULL, '2026-02-02 18:17:31', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"ac541906668f96305f6470b72e8483352d9f8da2f2407d20d2b7e40fab08e6c7\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-02 16:11:57\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"6e7fd885d41c84b2d4ee1986db8c7c42e947075adf5b255a6e2d5e18d9101930\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-02 19:17:31\", \"estado\": \"ACTIVO\"}'),
(151, 'tb_usuarios', 'UPDATE', '3', NULL, NULL, NULL, NULL, '2026-02-02 18:33:06', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"6e7fd885d41c84b2d4ee1986db8c7c42e947075adf5b255a6e2d5e18d9101930\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-02 19:17:31\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"6517d9a422355146e039697bbae074d5bbe23381dcfe44652569694c010d82b3\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-02 19:33:06\", \"estado\": \"ACTIVO\"}'),
(152, 'tb_usuarios', 'INSERT', '7', 3, '', '152.231.35.216', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-02 18:58:24', NULL, '{\"id_usuario\": 7, \"nombres\": \"Marcela orozco\", \"email\": \"mariamarcela@gmail.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-02 19:58:24\", \"fyh_actualizacion\": \"2026-02-02 19:58:24\", \"estado\": \"ACTIVO\"}'),
(153, 'tb_usuarios', 'UPDATE', '7', NULL, NULL, NULL, NULL, '2026-02-02 19:01:07', '{\"id_usuario\": 7, \"nombres\": \"Marcela orozco\", \"email\": \"mariamarcela@gmail.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-02 19:58:24\", \"fyh_actualizacion\": \"2026-02-02 19:58:24\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 7, \"nombres\": \"Marcela orozco\", \"email\": \"mariamarcela@gmail.com\", \"token\": \"ec3ce92bd7e6fd007e5ffdd847ac890b2e506412fb3974f54d0626d74a2f5b1f\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-02 19:58:24\", \"fyh_actualizacion\": \"2026-02-02 20:01:07\", \"estado\": \"ACTIVO\"}'),
(154, 'tb_usuarios', 'UPDATE', '3', NULL, NULL, NULL, NULL, '2026-02-02 22:04:44', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"6517d9a422355146e039697bbae074d5bbe23381dcfe44652569694c010d82b3\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-02 19:33:06\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 3, \"nombres\": \"Steven Escobar\", \"email\": \"steven@devzamora.com\", \"token\": \"9f4e14595b4b4c57f5d124947efbf07ade42f2abbccb1b8cf5f85c2f55c65f1b\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 19:39:25\", \"fyh_actualizacion\": \"2026-02-02 23:04:44\", \"estado\": \"ACTIVO\"}'),
(155, 'tb_permisos', 'INSERT', '117', NULL, NULL, NULL, NULL, '2026-02-03 01:50:43', NULL, '{\"id_permiso\": 117, \"clave\": \"reportes.ver\", \"descripcion\": \"Acceso a módulo de reportes\", \"created_at\": \"2026-02-03 02:50:43\"}'),
(156, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-03 02:02:22', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-02 00:56:04\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"e248b9b598aab94cfdfc112cd200cb42bd3e340e5aff10852302b75f7f3c3ec2\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 03:02:22\", \"estado\": \"ACTIVO\"}'),
(157, 'tb_usuarios', 'INSERT', '8', 2, '', '186.77.204.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 02:10:38', NULL, '{\"id_usuario\": 8, \"nombres\": \"DEMO\", \"email\": \"demo@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-03 03:10:38\", \"fyh_actualizacion\": \"2026-02-03 03:10:38\", \"estado\": \"ACTIVO\"}'),
(158, 'tb_categorias', 'UPDATE', '6', 2, '', '186.77.204.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 02:11:32', '{\"id_categoria\": 6, \"nombre_categoria\": \"MONTURAS / ARMAZONES\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:11:41\", \"fyh_actualizacion\": \"2026-02-01 21:11:41\"}', '{\"id_categoria\": 6, \"nombre_categoria\": \"MONTURAS / ARMAZONES / MARCOS\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:11:41\", \"fyh_actualizacion\": \"2026-02-02 18:11:32\"}'),
(159, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-03 02:16:09', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"e248b9b598aab94cfdfc112cd200cb42bd3e340e5aff10852302b75f7f3c3ec2\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 03:02:22\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"bf91a7b81e7e3accd7141cd6c283ab24d2898dd944e815fc4e9a6b7f719bc37e\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 03:16:09\", \"estado\": \"ACTIVO\"}'),
(160, 'tb_proveedores', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 02:27:21', NULL, '{\"id_proveedor\": 1, \"nombre_proveedor\": \"demo demo\", \"celular\": \"88888888\", \"telefono\": \"\", \"empresa\": \"demo para pruebas\", \"email\": \"\", \"direccion\": \"demo del otro demo\", \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:27:21\", \"fyh_actualizacion\": \"2026-02-03 03:27:21\"}'),
(161, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-03 02:27:30', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"bf91a7b81e7e3accd7141cd6c283ab24d2898dd944e815fc4e9a6b7f719bc37e\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 03:16:09\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"a034e211bbc17caab5f7edf4a9db3b382e27344123c7bd49b6eac75e339834ae\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 03:27:30\", \"estado\": \"ACTIVO\"}'),
(162, 'tb_proveedores', 'UPDATE', '1', NULL, NULL, NULL, NULL, '2026-02-03 02:27:54', '{\"id_proveedor\": 1, \"nombre_proveedor\": \"demo demo\", \"celular\": \"88888888\", \"telefono\": \"\", \"empresa\": \"demo para pruebas\", \"email\": \"\", \"direccion\": \"demo del otro demo\", \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:27:21\", \"fyh_actualizacion\": \"2026-02-03 03:27:21\"}', '{\"id_proveedor\": 1, \"nombre_proveedor\": \"demo demo\", \"celular\": \"88888888\", \"telefono\": \"\", \"empresa\": \"demo para pruebas\", \"email\": \"demo@demo.com\", \"direccion\": \"demo del otro demo\", \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:27:21\", \"fyh_actualizacion\": \"2026-02-02 18:27:54\"}'),
(163, 'tb_categorias', 'INSERT', '10', 2, '', '186.77.204.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 02:28:29', NULL, '{\"id_categoria\": 10, \"nombre_categoria\": \"DEMO\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-02 18:28:29\", \"fyh_actualizacion\": \"2026-02-03 03:28:29\"}'),
(164, 'tb_almacen', 'INSERT', '55', NULL, NULL, NULL, NULL, '2026-02-03 02:29:33', NULL, '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 12, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-03 03:29:33\"}'),
(165, 'tb_compras', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 02:31:49', NULL, '{\"id_compra\": 1, \"id_producto\": 55, \"nro_compra\": 1, \"fecha_compra\": \"2026-02-02\", \"id_proveedor\": 1, \"comprobante\": \"333434\", \"id_usuario\": 2, \"precio_compra\": 12.00, \"cantidad\": 6, \"estado\": \"ACTIVO\", \"fyh_anulado\": null, \"anulado_por\": null, \"motivo_anulacion\": null, \"fyh_creacion\": \"2026-02-02 18:31:49\", \"fyh_actualizacion\": \"2026-02-03 03:31:49\"}'),
(166, 'tb_almacen', 'UPDATE', '55', NULL, NULL, NULL, NULL, '2026-02-03 02:31:49', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 12, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-03 03:29:33\"}', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 18, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-02 18:31:49\"}'),
(167, 'tb_categorias', 'UPDATE', '6', 2, '', '186.77.204.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 02:36:32', '{\"id_categoria\": 6, \"nombre_categoria\": \"MONTURAS / ARMAZONES / MARCOS\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:11:41\", \"fyh_actualizacion\": \"2026-02-02 18:11:32\"}', '{\"id_categoria\": 6, \"nombre_categoria\": \"AROS / MARCOS\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:11:41\", \"fyh_actualizacion\": \"2026-02-02 18:36:32\"}'),
(168, 'tb_categorias', 'UPDATE', '8', 2, '', '186.77.204.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 02:37:56', '{\"id_categoria\": 8, \"nombre_categoria\": \"ACCESORIOS\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:35\", \"fyh_actualizacion\": \"2026-02-01 21:12:35\"}', '{\"id_categoria\": 8, \"nombre_categoria\": \"ESTUCHE\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:35\", \"fyh_actualizacion\": \"2026-02-02 18:37:56\"}'),
(169, 'tb_categorias', 'UPDATE', '7', 2, '', '186.77.204.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 02:38:18', '{\"id_categoria\": 7, \"nombre_categoria\": \"SERVICIOS TÉCNICOS\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:21\", \"fyh_actualizacion\": \"2026-02-01 21:12:21\"}', '{\"id_categoria\": 7, \"nombre_categoria\": \"REPARACIONES\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:21\", \"fyh_actualizacion\": \"2026-02-02 18:38:18\"}'),
(170, 'tb_almacen', 'DELETE', '28', NULL, NULL, NULL, NULL, '2026-02-03 02:38:34', '{\"id_producto\": 28, \"codigo\": \"LO-MONO\", \"nombre\": \"Lente Monofocal Básico\", \"stock\": 50, \"stock_minimo\": 10, \"stock_maximo\": 100, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(171, 'tb_almacen', 'DELETE', '29', NULL, NULL, NULL, NULL, '2026-02-03 02:38:34', '{\"id_producto\": 29, \"codigo\": \"LO-ANTIREF\", \"nombre\": \"Lente Antirreflejo\", \"stock\": 40, \"stock_minimo\": 10, \"stock_maximo\": 80, \"precio_compra\": 18.00, \"precio_venta\": 40.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(172, 'tb_almacen', 'DELETE', '30', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 30, \"codigo\": \"LO-BIFO\", \"nombre\": \"Lente Bifocal\", \"stock\": 30, \"stock_minimo\": 5, \"stock_maximo\": 60, \"precio_compra\": 22.00, \"precio_venta\": 55.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(173, 'tb_almacen', 'DELETE', '31', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 31, \"codigo\": \"LO-PROG\", \"nombre\": \"Lente Progresivo\", \"stock\": 20, \"stock_minimo\": 5, \"stock_maximo\": 40, \"precio_compra\": 45.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(174, 'tb_almacen', 'DELETE', '32', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 32, \"codigo\": \"LO-BLUE\", \"nombre\": \"Lente Filtro Azul\", \"stock\": 60, \"stock_minimo\": 15, \"stock_maximo\": 120, \"precio_compra\": 14.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(175, 'tb_almacen', 'DELETE', '33', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 33, \"codigo\": \"EX-VISUAL\", \"nombre\": \"Examen Visual General\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 20.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 2, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(176, 'tb_almacen', 'DELETE', '34', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 34, \"codigo\": \"EX-CONTROL\", \"nombre\": \"Examen de Control\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 10.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 2, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(177, 'tb_almacen', 'DELETE', '35', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 35, \"codigo\": \"LS-NORMAL\", \"nombre\": \"Lentes de Sol UV400\", \"stock\": 40, \"stock_minimo\": 10, \"stock_maximo\": 80, \"precio_compra\": 15.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 3, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(178, 'tb_almacen', 'DELETE', '36', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 36, \"codigo\": \"LS-POLAR\", \"nombre\": \"Lentes de Sol Polarizados\", \"stock\": 30, \"stock_minimo\": 10, \"stock_maximo\": 60, \"precio_compra\": 22.00, \"precio_venta\": 60.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 3, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(179, 'tb_almacen', 'DELETE', '37', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 37, \"codigo\": \"LS-GRAD\", \"nombre\": \"Lentes de Sol Graduados\", \"stock\": 20, \"stock_minimo\": 5, \"stock_maximo\": 40, \"precio_compra\": 35.00, \"precio_venta\": 90.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 3, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(180, 'tb_almacen', 'DELETE', '38', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 38, \"codigo\": \"LC-BLAN\", \"nombre\": \"Lentes de Contacto Blandos\", \"stock\": 100, \"stock_minimo\": 20, \"stock_maximo\": 200, \"precio_compra\": 8.00, \"precio_venta\": 20.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 4, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(181, 'tb_almacen', 'DELETE', '39', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 39, \"codigo\": \"LC-TORIC\", \"nombre\": \"Lentes de Contacto Tóricos\", \"stock\": 60, \"stock_minimo\": 10, \"stock_maximo\": 120, \"precio_compra\": 12.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 4, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(182, 'tb_almacen', 'DELETE', '40', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 40, \"codigo\": \"LC-COLOR\", \"nombre\": \"Lentes de Contacto de Color\", \"stock\": 50, \"stock_minimo\": 10, \"stock_maximo\": 100, \"precio_compra\": 9.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 4, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(183, 'tb_almacen', 'DELETE', '41', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 41, \"codigo\": \"PL-SOL\", \"nombre\": \"Solución Multiuso\", \"stock\": 120, \"stock_minimo\": 30, \"stock_maximo\": 250, \"precio_compra\": 4.00, \"precio_venta\": 9.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 5, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(184, 'tb_almacen', 'DELETE', '42', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 42, \"codigo\": \"PL-GOT\", \"nombre\": \"Gotas Lubricantes\", \"stock\": 80, \"stock_minimo\": 20, \"stock_maximo\": 160, \"precio_compra\": 3.00, \"precio_venta\": 8.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 5, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(185, 'tb_almacen', 'DELETE', '43', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 43, \"codigo\": \"PL-SPRAY\", \"nombre\": \"Spray Limpiador\", \"stock\": 100, \"stock_minimo\": 20, \"stock_maximo\": 200, \"precio_compra\": 2.50, \"precio_venta\": 7.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 5, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(186, 'tb_almacen', 'DELETE', '44', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 44, \"codigo\": \"MO-ACET\", \"nombre\": \"Montura de Acetato\", \"stock\": 60, \"stock_minimo\": 15, \"stock_maximo\": 120, \"precio_compra\": 10.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(187, 'tb_almacen', 'DELETE', '45', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 45, \"codigo\": \"MO-META\", \"nombre\": \"Montura Metálica\", \"stock\": 50, \"stock_minimo\": 15, \"stock_maximo\": 100, \"precio_compra\": 12.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(188, 'tb_almacen', 'DELETE', '46', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 46, \"codigo\": \"MO-NINO\", \"nombre\": \"Montura Infantil\", \"stock\": 40, \"stock_minimo\": 10, \"stock_maximo\": 80, \"precio_compra\": 9.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(189, 'tb_almacen', 'DELETE', '47', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 47, \"codigo\": \"ST-AJUSTE\", \"nombre\": \"Ajuste de Gafas\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 5.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 7, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(190, 'tb_almacen', 'DELETE', '48', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 48, \"codigo\": \"ST-REP\", \"nombre\": \"Reparación Básica\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 12.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 7, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(191, 'tb_almacen', 'DELETE', '49', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 49, \"codigo\": \"ST-LIMP\", \"nombre\": \"Limpieza Ultrasónica\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 0.00, \"precio_venta\": 6.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 7, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(192, 'tb_almacen', 'DELETE', '50', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 50, \"codigo\": \"AC-EST\", \"nombre\": \"Estuche para Gafas\", \"stock\": 120, \"stock_minimo\": 30, \"stock_maximo\": 240, \"precio_compra\": 2.00, \"precio_venta\": 6.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(193, 'tb_almacen', 'DELETE', '51', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 51, \"codigo\": \"AC-PANO\", \"nombre\": \"Paño de Microfibra\", \"stock\": 200, \"stock_minimo\": 50, \"stock_maximo\": 400, \"precio_compra\": 0.80, \"precio_venta\": 2.50, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(194, 'tb_almacen', 'DELETE', '52', NULL, NULL, NULL, NULL, '2026-02-03 02:38:35', '{\"id_producto\": 52, \"codigo\": \"AC-CORD\", \"nombre\": \"Cordón para Gafas\", \"stock\": 150, \"stock_minimo\": 30, \"stock_maximo\": 300, \"precio_compra\": 1.00, \"precio_venta\": 4.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(195, 'tb_almacen', 'DELETE', '53', NULL, NULL, NULL, NULL, '2026-02-03 02:38:48', '{\"id_producto\": 53, \"codigo\": \"SV-OCULAR\", \"nombre\": \"Protector Ocular\", \"stock\": 50, \"stock_minimo\": 10, \"stock_maximo\": 100, \"precio_compra\": 6.00, \"precio_venta\": 15.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 9, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(196, 'tb_almacen', 'DELETE', '54', NULL, NULL, NULL, NULL, '2026-02-03 02:38:48', '{\"id_producto\": 54, \"codigo\": \"SV-VIT\", \"nombre\": \"Vitaminas Oculares\", \"stock\": 40, \"stock_minimo\": 10, \"stock_maximo\": 80, \"precio_compra\": 7.00, \"precio_venta\": 18.00, \"fecha_ingreso\": \"2026-02-01\", \"id_usuario\": 2, \"id_categoria\": 9, \"estado\": 1, \"fyh_creacion\": \"2026-02-01 21:26:41\", \"fyh_actualizacion\": \"2026-02-01 21:26:41\"}', NULL),
(197, 'tb_categorias', 'UPDATE', '9', 2, '', '186.77.204.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 02:39:38', '{\"id_categoria\": 9, \"nombre_categoria\": \"PRODUCTOS RELACIONADOS CON SALUD VISUAL\", \"estado\": \"ACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:52\", \"fyh_actualizacion\": \"2026-02-01 21:12:52\"}', '{\"id_categoria\": 9, \"nombre_categoria\": \"PRODUCTOS RELACIONADOS CON SALUD VISUAL\", \"estado\": \"INACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:52\", \"fyh_actualizacion\": \"2026-02-02 18:39:38\"}'),
(198, 'tb_almacen', 'INSERT', '56', NULL, NULL, NULL, NULL, '2026-02-03 02:46:37', NULL, '{\"id_producto\": 56, \"codigo\": \"P-00002\", \"nombre\": \"AROS METALICOS\", \"stock\": 336, \"stock_minimo\": 300, \"stock_maximo\": 600, \"precio_compra\": 250.00, \"precio_venta\": 250.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:46:37\", \"fyh_actualizacion\": \"2026-02-03 03:46:37\"}'),
(199, 'tb_almacen', 'INSERT', '57', NULL, NULL, NULL, NULL, '2026-02-03 02:51:24', NULL, '{\"id_producto\": 57, \"codigo\": \"P-00003\", \"nombre\": \"AROS DE PASTA\", \"stock\": 300, \"stock_minimo\": 250, \"stock_maximo\": 400, \"precio_compra\": 120.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:51:24\", \"fyh_actualizacion\": \"2026-02-03 03:51:24\"}'),
(200, 'tb_almacen', 'INSERT', '58', NULL, NULL, NULL, NULL, '2026-02-03 02:52:30', NULL, '{\"id_producto\": 58, \"codigo\": \"P-00004\", \"nombre\": \"ESTUCHE PARA LENTE\", \"stock\": 100, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 30.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:52:30\", \"fyh_actualizacion\": \"2026-02-03 03:52:30\"}'),
(201, 'tb_almacen', 'INSERT', '59', NULL, NULL, NULL, NULL, '2026-02-03 02:53:57', NULL, '{\"id_producto\": 59, \"codigo\": \"P-00005\", \"nombre\": \"LIQUIDO DE LENTE\", \"stock\": 100, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 0.00, \"precio_venta\": 0.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 5, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:53:57\", \"fyh_actualizacion\": \"2026-02-03 03:53:57\"}'),
(202, 'tb_almacen', 'INSERT', '69', NULL, NULL, NULL, NULL, '2026-02-03 03:05:52', NULL, '{\"id_producto\": 69, \"codigo\": \"LOF-001\", \"nombre\": \"Lente oftálmico monofocal\", \"stock\": 50, \"stock_minimo\": 5, \"stock_maximo\": 200, \"precio_compra\": 10.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}'),
(203, 'tb_almacen', 'INSERT', '70', NULL, NULL, NULL, NULL, '2026-02-03 03:05:52', NULL, '{\"id_producto\": 70, \"codigo\": \"LOF-002\", \"nombre\": \"Lente oftálmico bifocal\", \"stock\": 40, \"stock_minimo\": 5, \"stock_maximo\": 150, \"precio_compra\": 15.00, \"precio_venta\": 55.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}'),
(204, 'tb_almacen', 'INSERT', '71', NULL, NULL, NULL, NULL, '2026-02-03 03:05:52', NULL, '{\"id_producto\": 71, \"codigo\": \"LOF-003\", \"nombre\": \"Lente oftálmico progresivo\", \"stock\": 30, \"stock_minimo\": 5, \"stock_maximo\": 100, \"precio_compra\": 30.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}'),
(205, 'tb_almacen', 'INSERT', '72', NULL, NULL, NULL, NULL, '2026-02-03 03:05:52', NULL, '{\"id_producto\": 72, \"codigo\": \"TRT-BLU-001\", \"nombre\": \"Filtro Blue Light (Blue Ray)\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 5.00, \"precio_venta\": 20.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}'),
(206, 'tb_almacen', 'INSERT', '73', NULL, NULL, NULL, NULL, '2026-02-03 03:05:52', NULL, '{\"id_producto\": 73, \"codigo\": \"TRT-AR-001\", \"nombre\": \"Tratamiento Antirreflejante\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 6.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}'),
(207, 'tb_almacen', 'INSERT', '74', NULL, NULL, NULL, NULL, '2026-02-03 03:05:52', NULL, '{\"id_producto\": 74, \"codigo\": \"TRT-TRA-001\", \"nombre\": \"Lentes Transitions\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 20.00, \"precio_venta\": 75.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}'),
(208, 'tb_almacen', 'INSERT', '75', NULL, NULL, NULL, NULL, '2026-02-03 03:05:52', NULL, '{\"id_producto\": 75, \"codigo\": \"MAT-CR39-001\", \"nombre\": \"Lente CR-39\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 6.00, \"precio_venta\": 20.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}'),
(209, 'tb_almacen', 'INSERT', '76', NULL, NULL, NULL, NULL, '2026-02-03 03:05:52', NULL, '{\"id_producto\": 76, \"codigo\": \"SOL-001\", \"nombre\": \"Lentes de sol polarizados\", \"stock\": 20, \"stock_minimo\": 3, \"stock_maximo\": 80, \"precio_compra\": 20.00, \"precio_venta\": 60.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 3, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}'),
(210, 'tb_almacen', 'INSERT', '77', NULL, NULL, NULL, NULL, '2026-02-03 03:05:52', NULL, '{\"id_producto\": 77, \"codigo\": \"MAT-POLY-001\", \"nombre\": \"Lente Policarbonato\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 10.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}'),
(211, 'tb_almacen', 'UPDATE', '76', NULL, NULL, NULL, NULL, '2026-02-03 03:07:31', '{\"id_producto\": 76, \"codigo\": \"SOL-001\", \"nombre\": \"Lentes de sol polarizados\", \"stock\": 20, \"stock_minimo\": 3, \"stock_maximo\": 80, \"precio_compra\": 20.00, \"precio_venta\": 60.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 3, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}', '{\"id_producto\": 76, \"codigo\": \"SOL-001\", \"nombre\": \"Lentes de sol polarizados\", \"stock\": 15, \"stock_minimo\": 10, \"stock_maximo\": 20, \"precio_compra\": 120.00, \"precio_venta\": 250.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 2, \"id_categoria\": 3, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-02 19:07:31\"}'),
(212, 'tb_almacen', 'UPDATE', '74', NULL, NULL, NULL, NULL, '2026-02-03 03:09:59', '{\"id_producto\": 74, \"codigo\": \"TRT-TRA-001\", \"nombre\": \"Lentes Transitions\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 20.00, \"precio_venta\": 75.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}', '{\"id_producto\": 74, \"codigo\": \"TRT-TRA-001\", \"nombre\": \"Lentes Transitions\", \"stock\": 9999, \"stock_minimo\": 0, \"stock_maximo\": 0, \"precio_compra\": 0.00, \"precio_venta\": 2500.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-02 19:09:59\"}'),
(213, 'tb_almacen', 'UPDATE', '71', NULL, NULL, NULL, NULL, '2026-02-03 03:11:37', '{\"id_producto\": 71, \"codigo\": \"LOF-003\", \"nombre\": \"Lente oftálmico progresivo\", \"stock\": 30, \"stock_minimo\": 5, \"stock_maximo\": 100, \"precio_compra\": 30.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}', '{\"id_producto\": 71, \"codigo\": \"LOF-003\", \"nombre\": \"Lente oftálmico progresivo\", \"stock\": 999, \"stock_minimo\": 0, \"stock_maximo\": 0, \"precio_compra\": 120.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-02 19:11:37\"}'),
(214, 'tb_clientes', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:13:26', NULL, '{\"id_cliente\": 1, \"nombre\": \"DEmo demo\", \"apellido\": \"demo\", \"tipo_documento\": \"Cédula\", \"numero_documento\": \"001113909347N\", \"celular\": \"8888888\", \"email\": \"demo@demo.com\", \"direccion\": \"demo del otro lado\", \"fyh_creacion\": \"2026-02-03 04:13:26\", \"fyh_actualizacion\": \"2026-02-03 04:13:26\"}'),
(215, 'tb_examenes_optometricos', 'INSERT', '1', 2, '', '186.77.204.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 03:18:15', NULL, '{\"id_examen\": 1, \"id_cliente\": 1, \"fecha_examen\": \"2026-02-02\", \"od_esfera\": \"0.25\", \"od_cilindro\": \"0.25\", \"od_eje\": \"1\", \"od_add\": \"0.25\", \"od_prisma\": \"0.25\", \"od_base\": \"in\", \"oi_esfera\": \"0.25\", \"oi_cilindro\": \"0.25\", \"oi_eje\": \"1\", \"oi_add\": \"0.25\", \"oi_prisma\": \"0.25\", \"oi_base\": \"in\", \"pd_lejos\": \"334.00\", \"pd_cerca\": \"34.00\", \"notas_optometrista\": \"Necesita lentes pogresivo\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:18:15\", \"fyh_actualizacion\": \"2026-02-03 04:18:15\"}'),
(216, 'tb_recetas_opticas', 'INSERT', '1', 2, '', '186.77.204.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-03 03:19:41', NULL, '{\"id_receta\": 1, \"id_cliente\": 1, \"id_examen\": 1, \"fecha_receta\": \"2026-02-03\", \"tipo\": \"LENTES\", \"vence_en\": \"2027-02-03\", \"detalle\": \"Receta emitida desde examen 2026-02-02\", \"notas\": \"Necesita lentes pogresivo\", \"id_usuario\": null, \"fyh_creacion\": \"2026-02-03 04:19:41\", \"fyh_actualizacion\": \"2026-02-03 04:19:41\"}'),
(217, 'tb_horario_laboral', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:23:28', NULL, '{\"id_horario\": 1, \"dia_semana\": 1, \"hora_inicio\": \"08:00:00\", \"hora_fin\": \"17:00:00\", \"activo\": 0, \"fyh_creacion\": \"2026-02-03 04:23:28\", \"fyh_actualizacion\": \"2026-02-03 04:23:28\"}'),
(218, 'tb_horario_laboral', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:23:28', NULL, '{\"id_horario\": 2, \"dia_semana\": 2, \"hora_inicio\": \"08:30:00\", \"hora_fin\": \"17:30:00\", \"activo\": 1, \"fyh_creacion\": \"2026-02-03 04:23:28\", \"fyh_actualizacion\": \"2026-02-03 04:23:28\"}'),
(219, 'tb_horario_laboral', 'INSERT', '3', NULL, NULL, NULL, NULL, '2026-02-03 03:23:28', NULL, '{\"id_horario\": 3, \"dia_semana\": 3, \"hora_inicio\": \"08:30:00\", \"hora_fin\": \"17:00:00\", \"activo\": 1, \"fyh_creacion\": \"2026-02-03 04:23:28\", \"fyh_actualizacion\": \"2026-02-03 04:23:28\"}'),
(220, 'tb_horario_laboral', 'INSERT', '4', NULL, NULL, NULL, NULL, '2026-02-03 03:23:28', NULL, '{\"id_horario\": 4, \"dia_semana\": 4, \"hora_inicio\": \"08:00:00\", \"hora_fin\": \"17:00:00\", \"activo\": 1, \"fyh_creacion\": \"2026-02-03 04:23:28\", \"fyh_actualizacion\": \"2026-02-03 04:23:28\"}'),
(221, 'tb_horario_laboral', 'INSERT', '5', NULL, NULL, NULL, NULL, '2026-02-03 03:23:28', NULL, '{\"id_horario\": 5, \"dia_semana\": 5, \"hora_inicio\": \"08:00:00\", \"hora_fin\": \"17:00:00\", \"activo\": 1, \"fyh_creacion\": \"2026-02-03 04:23:28\", \"fyh_actualizacion\": \"2026-02-03 04:23:28\"}'),
(222, 'tb_horario_laboral', 'INSERT', '6', NULL, NULL, NULL, NULL, '2026-02-03 03:23:28', NULL, '{\"id_horario\": 6, \"dia_semana\": 6, \"hora_inicio\": \"08:00:00\", \"hora_fin\": \"17:00:00\", \"activo\": 1, \"fyh_creacion\": \"2026-02-03 04:23:28\", \"fyh_actualizacion\": \"2026-02-03 04:23:28\"}'),
(223, 'tb_horario_laboral', 'INSERT', '7', NULL, NULL, NULL, NULL, '2026-02-03 03:23:28', NULL, '{\"id_horario\": 7, \"dia_semana\": 7, \"hora_inicio\": \"08:00:00\", \"hora_fin\": \"14:00:00\", \"activo\": 1, \"fyh_creacion\": \"2026-02-03 04:23:28\", \"fyh_actualizacion\": \"2026-02-03 04:23:28\"}'),
(224, 'tb_citas', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:24:22', NULL, '{\"id_cita\": 1, \"id_cliente\": 1, \"fecha\": \"2026-02-04\", \"hora_inicio\": \"10:30:00\", \"hora_fin\": \"11:00:00\", \"motivo\": \"EXamen\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:24:22\", \"fyh_actualizacion\": \"2026-02-03 04:24:22\"}'),
(225, 'tb_citas', 'UPDATE', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:26:58', '{\"id_cita\": 1, \"id_cliente\": 1, \"fecha\": \"2026-02-04\", \"hora_inicio\": \"10:30:00\", \"hora_fin\": \"11:00:00\", \"motivo\": \"EXamen\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:24:22\", \"fyh_actualizacion\": \"2026-02-03 04:24:22\"}', '{\"id_cita\": 1, \"id_cliente\": 1, \"fecha\": \"2026-02-04\", \"hora_inicio\": \"13:00:00\", \"hora_fin\": \"13:30:00\", \"motivo\": \"EXamen\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:24:22\", \"fyh_actualizacion\": \"2026-02-03 04:26:58\"}'),
(226, 'tb_citas', 'UPDATE', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:27:12', '{\"id_cita\": 1, \"id_cliente\": 1, \"fecha\": \"2026-02-04\", \"hora_inicio\": \"13:00:00\", \"hora_fin\": \"13:30:00\", \"motivo\": \"EXamen\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:24:22\", \"fyh_actualizacion\": \"2026-02-03 04:26:58\"}', '{\"id_cita\": 1, \"id_cliente\": 1, \"fecha\": \"2026-02-05\", \"hora_inicio\": \"12:30:00\", \"hora_fin\": \"13:00:00\", \"motivo\": \"EXamen\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:24:22\", \"fyh_actualizacion\": \"2026-02-03 04:27:12\"}'),
(227, 'tb_clientes', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:28:39', NULL, '{\"id_cliente\": 2, \"nombre\": \"demo jeff\", \"apellido\": \"zamora\", \"tipo_documento\": \"CED\", \"numero_documento\": \"909090909\", \"celular\": \"8888888\", \"email\": \"demo@demo.com\", \"direccion\": \"demo del otro lado\", \"fyh_creacion\": \"2026-02-03 04:28:39\", \"fyh_actualizacion\": \"2026-02-03 04:28:39\"}'),
(228, 'tb_citas', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:28:46', NULL, '{\"id_cita\": 2, \"id_cliente\": 2, \"fecha\": \"2026-02-04\", \"hora_inicio\": \"08:00:00\", \"hora_fin\": \"08:30:00\", \"motivo\": \"EXamen\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:28:46\", \"fyh_actualizacion\": \"2026-02-03 04:28:46\"}'),
(229, 'tb_cajas', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:29:52', NULL, '{\"id_caja\": 1, \"fecha_apertura\": \"2026-02-03 04:29:52\", \"fecha_cierre\": null, \"usuario_apertura_id\": 2, \"usuario_cierre_id\": null, \"monto_inicial\": 1000.00, \"total_efectivo\": 0.00, \"total_deposito\": 0.00, \"total_credito\": 0.00, \"total_abonos\": 0.00, \"total_ingresos\": 0.00, \"total_egresos\": 0.00, \"monto_cierre_efectivo\": null, \"monto_esperado_efectivo\": 0.00, \"diferencia_efectivo\": 0.00, \"estado\": \"abierta\", \"nota\": \"abrio STEVEN HOY\", \"observacion_cierre\": null, \"efectivo_contado\": null, \"efectivo_esperado\": null, \"diferencia\": null, \"fyh_creacion\": \"2026-02-03 04:29:52\", \"fyh_actualizacion\": \"2026-02-03 04:29:52\"}'),
(230, 'tb_caja_movimientos', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:30:39', NULL, '{\"id_movimiento\": 1, \"id_caja\": 1, \"tipo\": \"egreso\", \"concepto\": \"pago al sistema\", \"metodo_pago\": \"deposito\", \"monto\": 500.00, \"referencia\": \"primerpago\", \"estado\": \"activo\", \"anulado_por\": null, \"anulado_at\": null, \"motivo_anulacion\": null, \"id_movimiento_ajuste\": null, \"fecha\": \"2026-02-03 04:30:39\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:30:39\", \"fyh_actualizacion\": \"2026-02-03 04:30:39\"}'),
(231, 'tb_caja_movimientos', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:31:42', NULL, '{\"id_movimiento\": 2, \"id_caja\": 1, \"tipo\": \"egreso\", \"concepto\": \"galosina steven\", \"metodo_pago\": \"efectivo\", \"monto\": 30.00, \"referencia\": \"para proveedor\", \"estado\": \"activo\", \"anulado_por\": null, \"anulado_at\": null, \"motivo_anulacion\": null, \"id_movimiento_ajuste\": null, \"fecha\": \"2026-02-03 04:31:42\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:31:42\", \"fyh_actualizacion\": \"2026-02-03 04:31:42\"}'),
(232, 'tb_caja_movimientos', 'INSERT', '3', NULL, NULL, NULL, NULL, '2026-02-03 03:33:17', NULL, '{\"id_movimiento\": 3, \"id_caja\": 1, \"tipo\": \"ingreso\", \"concepto\": \"gasolina\", \"metodo_pago\": \"efectivo\", \"monto\": 30.00, \"referencia\": \"roberto me dio de su dinero\", \"estado\": \"activo\", \"anulado_por\": null, \"anulado_at\": null, \"motivo_anulacion\": null, \"id_movimiento_ajuste\": null, \"fecha\": \"2026-02-03 04:33:17\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:33:17\", \"fyh_actualizacion\": \"2026-02-03 04:33:17\"}'),
(233, 'tb_ventas', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:37:29', NULL, '{\"id_venta\": 1, \"nro_venta\": 1, \"fecha_venta\": \"2026-02-03 04:37:29\", \"id_cliente\": 2, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 2650.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 2650.00, \"metodo_pago\": \"efectivo\", \"pagado_inicial\": 2650.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": \"ESTAN LISTO DENTRO DE 7 DIAS\", \"fyh_creacion\": \"2026-02-03 04:37:29\", \"fyh_actualizacion\": \"2026-02-03 04:37:29\"}'),
(234, 'tb_ventas_detalle', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:37:29', NULL, '{\"id_detalle\": 1, \"id_venta\": 1, \"id_producto\": 71, \"cantidad\": 1, \"precio_unitario\": 2500.00, \"descuento_linea\": 0.00, \"total_linea\": 2500.00}'),
(235, 'tb_almacen', 'UPDATE', '71', NULL, NULL, NULL, NULL, '2026-02-03 03:37:29', '{\"id_producto\": 71, \"codigo\": \"LOF-003\", \"nombre\": \"Lente oftálmico progresivo\", \"stock\": 999, \"stock_minimo\": 0, \"stock_maximo\": 0, \"precio_compra\": 120.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-02 19:11:37\"}', '{\"id_producto\": 71, \"codigo\": \"LOF-003\", \"nombre\": \"Lente oftálmico progresivo\", \"stock\": 998, \"stock_minimo\": 0, \"stock_maximo\": 0, \"precio_compra\": 120.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 2, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:37:29\"}'),
(236, 'tb_ventas_detalle', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:37:29', NULL, '{\"id_detalle\": 2, \"id_venta\": 1, \"id_producto\": 56, \"cantidad\": 1, \"precio_unitario\": 120.00, \"descuento_linea\": 0.00, \"total_linea\": 120.00}'),
(237, 'tb_almacen', 'UPDATE', '56', NULL, NULL, NULL, NULL, '2026-02-03 03:37:29', '{\"id_producto\": 56, \"codigo\": \"P-00002\", \"nombre\": \"AROS METALICOS\", \"stock\": 336, \"stock_minimo\": 300, \"stock_maximo\": 600, \"precio_compra\": 250.00, \"precio_venta\": 250.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:46:37\", \"fyh_actualizacion\": \"2026-02-03 03:46:37\"}', '{\"id_producto\": 56, \"codigo\": \"P-00002\", \"nombre\": \"AROS METALICOS\", \"stock\": 335, \"stock_minimo\": 300, \"stock_maximo\": 600, \"precio_compra\": 250.00, \"precio_venta\": 250.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:46:37\", \"fyh_actualizacion\": \"2026-02-03 04:37:29\"}'),
(238, 'tb_ventas_detalle', 'INSERT', '3', NULL, NULL, NULL, NULL, '2026-02-03 03:37:29', NULL, '{\"id_detalle\": 3, \"id_venta\": 1, \"id_producto\": 58, \"cantidad\": 1, \"precio_unitario\": 30.00, \"descuento_linea\": 0.00, \"total_linea\": 30.00}'),
(239, 'tb_almacen', 'UPDATE', '58', NULL, NULL, NULL, NULL, '2026-02-03 03:37:29', '{\"id_producto\": 58, \"codigo\": \"P-00004\", \"nombre\": \"ESTUCHE PARA LENTE\", \"stock\": 100, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 30.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:52:30\", \"fyh_actualizacion\": \"2026-02-03 03:52:30\"}', '{\"id_producto\": 58, \"codigo\": \"P-00004\", \"nombre\": \"ESTUCHE PARA LENTE\", \"stock\": 99, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 30.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:52:30\", \"fyh_actualizacion\": \"2026-02-03 04:37:29\"}'),
(240, 'tb_ventas', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:40:51', NULL, '{\"id_venta\": 2, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 04:40:51\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 1500.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 1500.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 500.00, \"saldo_pendiente\": 1000.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 04:40:51\", \"fyh_actualizacion\": \"2026-02-03 04:40:51\"}'),
(241, 'tb_ventas_detalle', 'INSERT', '4', NULL, NULL, NULL, NULL, '2026-02-03 03:40:51', NULL, '{\"id_detalle\": 4, \"id_venta\": 2, \"id_producto\": 69, \"cantidad\": 1, \"precio_unitario\": 1500.00, \"descuento_linea\": 0.00, \"total_linea\": 1500.00}'),
(242, 'tb_almacen', 'UPDATE', '69', NULL, NULL, NULL, NULL, '2026-02-03 03:40:51', '{\"id_producto\": 69, \"codigo\": \"LOF-001\", \"nombre\": \"Lente oftálmico monofocal\", \"stock\": 50, \"stock_minimo\": 5, \"stock_maximo\": 200, \"precio_compra\": 10.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}', '{\"id_producto\": 69, \"codigo\": \"LOF-001\", \"nombre\": \"Lente oftálmico monofocal\", \"stock\": 49, \"stock_minimo\": 5, \"stock_maximo\": 200, \"precio_compra\": 10.00, \"precio_venta\": 35.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:40:51\"}');
INSERT INTO `tb_auditoria` (`id_auditoria`, `tabla`, `accion`, `pk`, `usuario_id`, `usuario_email`, `ip`, `user_agent`, `fecha`, `antes`, `despues`) VALUES
(243, 'tb_ventas_detalle', 'INSERT', '5', NULL, NULL, NULL, NULL, '2026-02-03 03:40:51', NULL, '{\"id_detalle\": 5, \"id_venta\": 2, \"id_producto\": 58, \"cantidad\": 1, \"precio_unitario\": 0.00, \"descuento_linea\": 0.00, \"total_linea\": 0.00}'),
(244, 'tb_almacen', 'UPDATE', '58', NULL, NULL, NULL, NULL, '2026-02-03 03:40:51', '{\"id_producto\": 58, \"codigo\": \"P-00004\", \"nombre\": \"ESTUCHE PARA LENTE\", \"stock\": 99, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 30.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:52:30\", \"fyh_actualizacion\": \"2026-02-03 04:37:29\"}', '{\"id_producto\": 58, \"codigo\": \"P-00004\", \"nombre\": \"ESTUCHE PARA LENTE\", \"stock\": 98, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 30.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:52:30\", \"fyh_actualizacion\": \"2026-02-03 04:40:51\"}'),
(245, 'tb_ventas_detalle', 'INSERT', '6', NULL, NULL, NULL, NULL, '2026-02-03 03:40:51', NULL, '{\"id_detalle\": 6, \"id_venta\": 2, \"id_producto\": 59, \"cantidad\": 1, \"precio_unitario\": 0.00, \"descuento_linea\": 0.00, \"total_linea\": 0.00}'),
(246, 'tb_almacen', 'UPDATE', '59', NULL, NULL, NULL, NULL, '2026-02-03 03:40:51', '{\"id_producto\": 59, \"codigo\": \"P-00005\", \"nombre\": \"LIQUIDO DE LENTE\", \"stock\": 100, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 0.00, \"precio_venta\": 0.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 5, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:53:57\", \"fyh_actualizacion\": \"2026-02-03 03:53:57\"}', '{\"id_producto\": 59, \"codigo\": \"P-00005\", \"nombre\": \"LIQUIDO DE LENTE\", \"stock\": 99, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 0.00, \"precio_venta\": 0.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 5, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:53:57\", \"fyh_actualizacion\": \"2026-02-03 04:40:51\"}'),
(247, 'tb_ventas_pagos', 'INSERT', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:41:56', NULL, '{\"id_pago\": 1, \"id_venta\": 2, \"id_caja\": 1, \"fecha_pago\": \"2026-02-03 04:41:56\", \"metodo_pago\": \"efectivo\", \"monto\": 200.00, \"referencia\": \"segundo pago\", \"id_usuario\": 2}'),
(248, 'tb_ventas', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:41:56', '{\"id_venta\": 2, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 04:40:51\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 1500.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 1500.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 500.00, \"saldo_pendiente\": 1000.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 04:40:51\", \"fyh_actualizacion\": \"2026-02-03 04:40:51\"}', '{\"id_venta\": 2, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 04:40:51\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 1500.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 1500.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 500.00, \"saldo_pendiente\": 800.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 04:40:51\", \"fyh_actualizacion\": \"2026-02-03 04:41:56\"}'),
(249, 'tb_ventas_pagos', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:44:19', NULL, '{\"id_pago\": 2, \"id_venta\": 2, \"id_caja\": 1, \"fecha_pago\": \"2026-02-03 04:44:19\", \"metodo_pago\": \"efectivo\", \"monto\": 800.00, \"referencia\": \"cancelacion\", \"id_usuario\": 2}'),
(250, 'tb_ventas', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:44:19', '{\"id_venta\": 2, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 04:40:51\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 1500.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 1500.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 500.00, \"saldo_pendiente\": 800.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 04:40:51\", \"fyh_actualizacion\": \"2026-02-03 04:41:56\"}', '{\"id_venta\": 2, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 04:40:51\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 1500.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 1500.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 500.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 04:40:51\", \"fyh_actualizacion\": \"2026-02-03 04:44:19\"}'),
(251, 'tb_ventas', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:44:19', '{\"id_venta\": 2, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 04:40:51\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 1500.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 1500.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 500.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 04:40:51\", \"fyh_actualizacion\": \"2026-02-03 04:44:19\"}', '{\"id_venta\": 2, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 04:40:51\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 1500.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 1500.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 500.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 04:40:51\", \"fyh_actualizacion\": \"2026-02-03 04:44:19\"}'),
(252, 'tb_cajas', 'UPDATE', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:48:27', '{\"id_caja\": 1, \"fecha_apertura\": \"2026-02-03 04:29:52\", \"fecha_cierre\": null, \"usuario_apertura_id\": 2, \"usuario_cierre_id\": null, \"monto_inicial\": 1000.00, \"total_efectivo\": 0.00, \"total_deposito\": 0.00, \"total_credito\": 0.00, \"total_abonos\": 0.00, \"total_ingresos\": 0.00, \"total_egresos\": 0.00, \"monto_cierre_efectivo\": null, \"monto_esperado_efectivo\": 0.00, \"diferencia_efectivo\": 0.00, \"estado\": \"abierta\", \"nota\": \"abrio STEVEN HOY\", \"observacion_cierre\": null, \"efectivo_contado\": null, \"efectivo_esperado\": null, \"diferencia\": null, \"fyh_creacion\": \"2026-02-03 04:29:52\", \"fyh_actualizacion\": \"2026-02-03 04:29:52\"}', '{\"id_caja\": 1, \"fecha_apertura\": \"2026-02-03 04:29:52\", \"fecha_cierre\": \"2026-02-03 04:48:27\", \"usuario_apertura_id\": 2, \"usuario_cierre_id\": 2, \"monto_inicial\": 1000.00, \"total_efectivo\": 3650.00, \"total_deposito\": -500.00, \"total_credito\": 0.00, \"total_abonos\": 1000.00, \"total_ingresos\": 3680.00, \"total_egresos\": 530.00, \"monto_cierre_efectivo\": null, \"monto_esperado_efectivo\": 0.00, \"diferencia_efectivo\": 0.00, \"estado\": \"cerrada\", \"nota\": \"abrio STEVEN HOY\", \"observacion_cierre\": null, \"efectivo_contado\": 4650.00, \"efectivo_esperado\": 4650.00, \"diferencia\": null, \"fyh_creacion\": \"2026-02-03 04:29:52\", \"fyh_actualizacion\": \"2026-02-03 04:48:27\"}'),
(253, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:52:30', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"a034e211bbc17caab5f7edf4a9db3b382e27344123c7bd49b6eac75e339834ae\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 03:27:30\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 04:52:30\", \"estado\": \"ACTIVO\"}'),
(254, 'tb_ventas_pagos', 'DELETE', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:52:46', '{\"id_pago\": 1, \"id_venta\": 2, \"id_caja\": 1, \"fecha_pago\": \"2026-02-03 04:41:56\", \"metodo_pago\": \"efectivo\", \"monto\": 200.00, \"referencia\": \"segundo pago\", \"id_usuario\": 2}', NULL),
(255, 'tb_ventas_pagos', 'DELETE', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:52:46', '{\"id_pago\": 2, \"id_venta\": 2, \"id_caja\": 1, \"fecha_pago\": \"2026-02-03 04:44:19\", \"metodo_pago\": \"efectivo\", \"monto\": 800.00, \"referencia\": \"cancelacion\", \"id_usuario\": 2}', NULL),
(256, 'tb_ventas_detalle', 'DELETE', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:52:56', '{\"id_detalle\": 1, \"id_venta\": 1, \"id_producto\": 71, \"cantidad\": 1, \"precio_unitario\": 2500.00, \"descuento_linea\": 0.00, \"total_linea\": 2500.00}', NULL),
(257, 'tb_ventas_detalle', 'DELETE', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:52:56', '{\"id_detalle\": 2, \"id_venta\": 1, \"id_producto\": 56, \"cantidad\": 1, \"precio_unitario\": 120.00, \"descuento_linea\": 0.00, \"total_linea\": 120.00}', NULL),
(258, 'tb_ventas_detalle', 'DELETE', '3', NULL, NULL, NULL, NULL, '2026-02-03 03:52:56', '{\"id_detalle\": 3, \"id_venta\": 1, \"id_producto\": 58, \"cantidad\": 1, \"precio_unitario\": 30.00, \"descuento_linea\": 0.00, \"total_linea\": 30.00}', NULL),
(259, 'tb_ventas_detalle', 'DELETE', '4', NULL, NULL, NULL, NULL, '2026-02-03 03:52:56', '{\"id_detalle\": 4, \"id_venta\": 2, \"id_producto\": 69, \"cantidad\": 1, \"precio_unitario\": 1500.00, \"descuento_linea\": 0.00, \"total_linea\": 1500.00}', NULL),
(260, 'tb_ventas_detalle', 'DELETE', '5', NULL, NULL, NULL, NULL, '2026-02-03 03:52:56', '{\"id_detalle\": 5, \"id_venta\": 2, \"id_producto\": 58, \"cantidad\": 1, \"precio_unitario\": 0.00, \"descuento_linea\": 0.00, \"total_linea\": 0.00}', NULL),
(261, 'tb_ventas_detalle', 'DELETE', '6', NULL, NULL, NULL, NULL, '2026-02-03 03:52:56', '{\"id_detalle\": 6, \"id_venta\": 2, \"id_producto\": 59, \"cantidad\": 1, \"precio_unitario\": 0.00, \"descuento_linea\": 0.00, \"total_linea\": 0.00}', NULL),
(262, 'tb_ventas', 'DELETE', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:53:05', '{\"id_venta\": 1, \"nro_venta\": 1, \"fecha_venta\": \"2026-02-03 04:37:29\", \"id_cliente\": 2, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 2650.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 2650.00, \"metodo_pago\": \"efectivo\", \"pagado_inicial\": 2650.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": \"ESTAN LISTO DENTRO DE 7 DIAS\", \"fyh_creacion\": \"2026-02-03 04:37:29\", \"fyh_actualizacion\": \"2026-02-03 04:37:29\"}', NULL),
(263, 'tb_ventas', 'DELETE', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:53:05', '{\"id_venta\": 2, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 04:40:51\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 1, \"subtotal\": 1500.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 1500.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 500.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 04:40:51\", \"fyh_actualizacion\": \"2026-02-03 04:44:19\"}', NULL),
(264, 'tb_caja_movimientos', 'DELETE', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:53:36', '{\"id_movimiento\": 1, \"id_caja\": 1, \"tipo\": \"egreso\", \"concepto\": \"pago al sistema\", \"metodo_pago\": \"deposito\", \"monto\": 500.00, \"referencia\": \"primerpago\", \"estado\": \"activo\", \"anulado_por\": null, \"anulado_at\": null, \"motivo_anulacion\": null, \"id_movimiento_ajuste\": null, \"fecha\": \"2026-02-03 04:30:39\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:30:39\", \"fyh_actualizacion\": \"2026-02-03 04:30:39\"}', NULL),
(265, 'tb_caja_movimientos', 'DELETE', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:53:36', '{\"id_movimiento\": 2, \"id_caja\": 1, \"tipo\": \"egreso\", \"concepto\": \"galosina steven\", \"metodo_pago\": \"efectivo\", \"monto\": 30.00, \"referencia\": \"para proveedor\", \"estado\": \"activo\", \"anulado_por\": null, \"anulado_at\": null, \"motivo_anulacion\": null, \"id_movimiento_ajuste\": null, \"fecha\": \"2026-02-03 04:31:42\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:31:42\", \"fyh_actualizacion\": \"2026-02-03 04:31:42\"}', NULL),
(266, 'tb_caja_movimientos', 'DELETE', '3', NULL, NULL, NULL, NULL, '2026-02-03 03:53:36', '{\"id_movimiento\": 3, \"id_caja\": 1, \"tipo\": \"ingreso\", \"concepto\": \"gasolina\", \"metodo_pago\": \"efectivo\", \"monto\": 30.00, \"referencia\": \"roberto me dio de su dinero\", \"estado\": \"activo\", \"anulado_por\": null, \"anulado_at\": null, \"motivo_anulacion\": null, \"id_movimiento_ajuste\": null, \"fecha\": \"2026-02-03 04:33:17\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:33:17\", \"fyh_actualizacion\": \"2026-02-03 04:33:17\"}', NULL),
(267, 'tb_cajas', 'DELETE', '1', NULL, NULL, NULL, NULL, '2026-02-03 03:53:43', '{\"id_caja\": 1, \"fecha_apertura\": \"2026-02-03 04:29:52\", \"fecha_cierre\": \"2026-02-03 04:48:27\", \"usuario_apertura_id\": 2, \"usuario_cierre_id\": 2, \"monto_inicial\": 1000.00, \"total_efectivo\": 3650.00, \"total_deposito\": -500.00, \"total_credito\": 0.00, \"total_abonos\": 1000.00, \"total_ingresos\": 3680.00, \"total_egresos\": 530.00, \"monto_cierre_efectivo\": null, \"monto_esperado_efectivo\": 0.00, \"diferencia_efectivo\": 0.00, \"estado\": \"cerrada\", \"nota\": \"abrio STEVEN HOY\", \"observacion_cierre\": null, \"efectivo_contado\": 4650.00, \"efectivo_esperado\": 4650.00, \"diferencia\": null, \"fyh_creacion\": \"2026-02-03 04:29:52\", \"fyh_actualizacion\": \"2026-02-03 04:48:27\"}', NULL),
(268, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-03 03:54:02', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 04:52:30\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"56e7d14d5021204a84daf90868e9b43fc630367aa53d9abf2b17d331f6de3cfc\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 04:54:02\", \"estado\": \"ACTIVO\"}'),
(269, 'tb_categorias', 'DELETE', '9', NULL, NULL, NULL, NULL, '2026-02-03 04:21:55', '{\"id_categoria\": 9, \"nombre_categoria\": \"PRODUCTOS RELACIONADOS CON SALUD VISUAL\", \"estado\": \"INACTIVO\", \"fyh_creacion\": \"2026-02-01 12:12:52\", \"fyh_actualizacion\": \"2026-02-02 18:39:38\"}', NULL),
(270, 'tb_cajas', 'INSERT', '2', NULL, NULL, NULL, NULL, '2026-02-03 05:04:38', NULL, '{\"id_caja\": 2, \"fecha_apertura\": \"2026-02-02 23:04:38\", \"fecha_cierre\": null, \"usuario_apertura_id\": 2, \"usuario_cierre_id\": null, \"monto_inicial\": 1000.00, \"total_efectivo\": 0.00, \"total_deposito\": 0.00, \"total_credito\": 0.00, \"total_abonos\": 0.00, \"total_ingresos\": 0.00, \"total_egresos\": 0.00, \"monto_cierre_efectivo\": null, \"monto_esperado_efectivo\": 0.00, \"diferencia_efectivo\": 0.00, \"estado\": \"abierta\", \"nota\": null, \"observacion_cierre\": null, \"efectivo_contado\": null, \"efectivo_esperado\": null, \"diferencia\": null, \"fyh_creacion\": \"2026-02-02 23:04:38\", \"fyh_actualizacion\": \"2026-02-02 23:04:38\"}'),
(271, 'tb_caja_movimientos', 'INSERT', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:07:55', NULL, '{\"id_movimiento\": 4, \"id_caja\": 2, \"tipo\": \"ingreso\", \"concepto\": \"abono\", \"metodo_pago\": \"efectivo\", \"monto\": 33.00, \"referencia\": \"primer pago\", \"estado\": \"activo\", \"anulado_por\": null, \"anulado_at\": null, \"motivo_anulacion\": null, \"id_movimiento_ajuste\": null, \"fecha\": \"2026-02-02 23:07:55\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-02 23:07:55\", \"fyh_actualizacion\": \"2026-02-02 23:07:55\"}'),
(272, 'tb_ventas', 'INSERT', '3', NULL, NULL, NULL, NULL, '2026-02-03 05:10:33', NULL, '{\"id_venta\": 3, \"nro_venta\": 1, \"fecha_venta\": \"2026-02-02 23:10:33\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"efectivo\", \"pagado_inicial\": 25.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:10:33\", \"fyh_actualizacion\": \"2026-02-02 23:10:33\"}'),
(273, 'tb_ventas_detalle', 'INSERT', '7', NULL, NULL, NULL, NULL, '2026-02-03 05:10:33', NULL, '{\"id_detalle\": 7, \"id_venta\": 3, \"id_producto\": 55, \"cantidad\": 1, \"precio_unitario\": 25.00, \"descuento_linea\": 0.00, \"total_linea\": 25.00}'),
(274, 'tb_almacen', 'UPDATE', '55', NULL, NULL, NULL, NULL, '2026-02-03 05:10:33', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 18, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-02 18:31:49\"}', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 17, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-02 23:10:33\"}'),
(275, 'tb_ventas', 'INSERT', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:11:47', NULL, '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 15.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:11:47\"}'),
(276, 'tb_ventas_detalle', 'INSERT', '8', NULL, NULL, NULL, NULL, '2026-02-03 05:11:47', NULL, '{\"id_detalle\": 8, \"id_venta\": 4, \"id_producto\": 55, \"cantidad\": 1, \"precio_unitario\": 25.00, \"descuento_linea\": 0.00, \"total_linea\": 25.00}'),
(277, 'tb_almacen', 'UPDATE', '55', NULL, NULL, NULL, NULL, '2026-02-03 05:11:47', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 17, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-02 23:10:33\"}', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 16, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-02 23:11:47\"}'),
(278, 'tb_ventas_pagos', 'INSERT', '3', NULL, NULL, NULL, NULL, '2026-02-03 05:13:59', NULL, '{\"id_pago\": 3, \"id_venta\": 4, \"id_caja\": 2, \"fecha_pago\": \"2026-02-02 23:13:59\", \"metodo_pago\": \"efectivo\", \"monto\": 3.00, \"referencia\": \"primer pago\", \"id_usuario\": 2}'),
(279, 'tb_ventas', 'UPDATE', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:13:59', '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 15.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:11:47\"}', '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 12.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:13:59\"}'),
(280, 'tb_ventas_pagos', 'INSERT', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:18:36', NULL, '{\"id_pago\": 4, \"id_venta\": 4, \"id_caja\": 2, \"fecha_pago\": \"2026-02-02 23:18:36\", \"metodo_pago\": \"efectivo\", \"monto\": 5.00, \"referencia\": \"SEGU pago\", \"id_usuario\": 2}'),
(281, 'tb_ventas', 'UPDATE', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:18:36', '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 12.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:13:59\"}', '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 7.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:18:36\"}'),
(282, 'tb_ventas_pagos', 'INSERT', '5', NULL, NULL, NULL, NULL, '2026-02-03 05:19:29', NULL, '{\"id_pago\": 5, \"id_venta\": 4, \"id_caja\": 2, \"fecha_pago\": \"2026-02-02 23:19:29\", \"metodo_pago\": \"efectivo\", \"monto\": 7.00, \"referencia\": \"cancelar\", \"id_usuario\": 2}'),
(283, 'tb_ventas', 'UPDATE', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:19:29', '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 7.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:18:36\"}', '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:19:29\"}'),
(284, 'tb_ventas', 'UPDATE', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:19:29', '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:19:29\"}', '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:19:29\"}'),
(285, 'tb_ventas_pagos', 'DELETE', '3', NULL, NULL, NULL, NULL, '2026-02-03 05:20:39', '{\"id_pago\": 3, \"id_venta\": 4, \"id_caja\": 2, \"fecha_pago\": \"2026-02-02 23:13:59\", \"metodo_pago\": \"efectivo\", \"monto\": 3.00, \"referencia\": \"primer pago\", \"id_usuario\": 2}', NULL),
(286, 'tb_ventas_pagos', 'DELETE', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:20:39', '{\"id_pago\": 4, \"id_venta\": 4, \"id_caja\": 2, \"fecha_pago\": \"2026-02-02 23:18:36\", \"metodo_pago\": \"efectivo\", \"monto\": 5.00, \"referencia\": \"SEGU pago\", \"id_usuario\": 2}', NULL),
(287, 'tb_ventas_pagos', 'DELETE', '5', NULL, NULL, NULL, NULL, '2026-02-03 05:20:39', '{\"id_pago\": 5, \"id_venta\": 4, \"id_caja\": 2, \"fecha_pago\": \"2026-02-02 23:19:29\", \"metodo_pago\": \"efectivo\", \"monto\": 7.00, \"referencia\": \"cancelar\", \"id_usuario\": 2}', NULL),
(288, 'tb_ventas_detalle', 'DELETE', '7', NULL, NULL, NULL, NULL, '2026-02-03 05:20:48', '{\"id_detalle\": 7, \"id_venta\": 3, \"id_producto\": 55, \"cantidad\": 1, \"precio_unitario\": 25.00, \"descuento_linea\": 0.00, \"total_linea\": 25.00}', NULL),
(289, 'tb_ventas_detalle', 'DELETE', '8', NULL, NULL, NULL, NULL, '2026-02-03 05:20:48', '{\"id_detalle\": 8, \"id_venta\": 4, \"id_producto\": 55, \"cantidad\": 1, \"precio_unitario\": 25.00, \"descuento_linea\": 0.00, \"total_linea\": 25.00}', NULL),
(290, 'tb_ventas', 'DELETE', '3', NULL, NULL, NULL, NULL, '2026-02-03 05:21:00', '{\"id_venta\": 3, \"nro_venta\": 1, \"fecha_venta\": \"2026-02-02 23:10:33\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"efectivo\", \"pagado_inicial\": 25.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:10:33\", \"fyh_actualizacion\": \"2026-02-02 23:10:33\"}', NULL),
(291, 'tb_ventas', 'DELETE', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:21:00', '{\"id_venta\": 4, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-02 23:11:47\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 2, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-02 23:11:47\", \"fyh_actualizacion\": \"2026-02-02 23:19:29\"}', NULL),
(292, 'tb_caja_movimientos', 'DELETE', '4', NULL, NULL, NULL, NULL, '2026-02-03 05:21:16', '{\"id_movimiento\": 4, \"id_caja\": 2, \"tipo\": \"ingreso\", \"concepto\": \"abono\", \"metodo_pago\": \"efectivo\", \"monto\": 33.00, \"referencia\": \"primer pago\", \"estado\": \"activo\", \"anulado_por\": null, \"anulado_at\": null, \"motivo_anulacion\": null, \"id_movimiento_ajuste\": null, \"fecha\": \"2026-02-02 23:07:55\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-02 23:07:55\", \"fyh_actualizacion\": \"2026-02-02 23:07:55\"}', NULL),
(293, 'tb_cajas', 'DELETE', '2', NULL, NULL, NULL, NULL, '2026-02-03 05:21:24', '{\"id_caja\": 2, \"fecha_apertura\": \"2026-02-02 23:04:38\", \"fecha_cierre\": null, \"usuario_apertura_id\": 2, \"usuario_cierre_id\": null, \"monto_inicial\": 1000.00, \"total_efectivo\": 0.00, \"total_deposito\": 0.00, \"total_credito\": 0.00, \"total_abonos\": 0.00, \"total_ingresos\": 0.00, \"total_egresos\": 0.00, \"monto_cierre_efectivo\": null, \"monto_esperado_efectivo\": 0.00, \"diferencia_efectivo\": 0.00, \"estado\": \"abierta\", \"nota\": null, \"observacion_cierre\": null, \"efectivo_contado\": null, \"efectivo_esperado\": null, \"diferencia\": null, \"fyh_creacion\": \"2026-02-02 23:04:38\", \"fyh_actualizacion\": \"2026-02-02 23:04:38\"}', NULL),
(294, 'tb_cajas', 'INSERT', '3', NULL, NULL, NULL, NULL, '2026-02-04 01:15:31', NULL, '{\"id_caja\": 3, \"fecha_apertura\": \"2026-02-03 19:15:31\", \"fecha_cierre\": null, \"usuario_apertura_id\": 2, \"usuario_cierre_id\": null, \"monto_inicial\": 500.00, \"total_efectivo\": 0.00, \"total_deposito\": 0.00, \"total_credito\": 0.00, \"total_abonos\": 0.00, \"total_ingresos\": 0.00, \"total_egresos\": 0.00, \"monto_cierre_efectivo\": null, \"monto_esperado_efectivo\": 0.00, \"diferencia_efectivo\": 0.00, \"estado\": \"abierta\", \"nota\": null, \"observacion_cierre\": null, \"efectivo_contado\": null, \"efectivo_esperado\": null, \"diferencia\": null, \"fyh_creacion\": \"2026-02-03 19:15:31\", \"fyh_actualizacion\": \"2026-02-03 19:15:31\"}'),
(295, 'tb_ventas', 'INSERT', '5', NULL, NULL, NULL, NULL, '2026-02-04 01:15:52', NULL, '{\"id_venta\": 5, \"nro_venta\": 1, \"fecha_venta\": \"2026-02-03 19:15:52\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"efectivo\", \"pagado_inicial\": 25.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:15:52\", \"fyh_actualizacion\": \"2026-02-03 19:15:52\"}'),
(296, 'tb_ventas_detalle', 'INSERT', '9', NULL, NULL, NULL, NULL, '2026-02-04 01:15:52', NULL, '{\"id_detalle\": 9, \"id_venta\": 5, \"id_producto\": 55, \"cantidad\": 1, \"precio_unitario\": 25.00, \"descuento_linea\": 0.00, \"total_linea\": 25.00}'),
(297, 'tb_almacen', 'UPDATE', '55', NULL, NULL, NULL, NULL, '2026-02-04 01:15:52', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 16, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-02 23:11:47\"}', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 15, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-03 19:15:52\"}'),
(298, 'tb_ventas', 'INSERT', '6', NULL, NULL, NULL, NULL, '2026-02-04 01:29:31', NULL, '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 190.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:29:31\"}'),
(299, 'tb_ventas_detalle', 'INSERT', '10', NULL, NULL, NULL, NULL, '2026-02-04 01:29:31', NULL, '{\"id_detalle\": 10, \"id_venta\": 6, \"id_producto\": 55, \"cantidad\": 1, \"precio_unitario\": 200.00, \"descuento_linea\": 0.00, \"total_linea\": 200.00}'),
(300, 'tb_almacen', 'UPDATE', '55', NULL, NULL, NULL, NULL, '2026-02-04 01:29:31', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 15, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-03 19:15:52\"}', '{\"id_producto\": 55, \"codigo\": \"P-00028\", \"nombre\": \"DEMO PRODUCTO\", \"stock\": 14, \"stock_minimo\": 2, \"stock_maximo\": 15, \"precio_compra\": 12.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 10, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:29:33\", \"fyh_actualizacion\": \"2026-02-03 19:29:31\"}'),
(301, 'tb_ventas_pagos', 'INSERT', '6', NULL, NULL, NULL, NULL, '2026-02-04 01:30:34', NULL, '{\"id_pago\": 6, \"id_venta\": 6, \"id_caja\": 3, \"fecha_pago\": \"2026-02-03 19:30:34\", \"metodo_pago\": \"efectivo\", \"monto\": 100.00, \"referencia\": \"primer pago\", \"id_usuario\": 2}'),
(302, 'tb_ventas', 'UPDATE', '6', NULL, NULL, NULL, NULL, '2026-02-04 01:30:34', '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 190.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:29:31\"}', '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 90.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:30:34\"}'),
(303, 'tb_ventas_pagos', 'INSERT', '7', NULL, NULL, NULL, NULL, '2026-02-04 01:32:14', NULL, '{\"id_pago\": 7, \"id_venta\": 6, \"id_caja\": 3, \"fecha_pago\": \"2026-02-03 19:32:14\", \"metodo_pago\": \"efectivo\", \"monto\": 50.00, \"referencia\": \"SEGU pago\", \"id_usuario\": 2}'),
(304, 'tb_ventas', 'UPDATE', '6', NULL, NULL, NULL, NULL, '2026-02-04 01:32:14', '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 90.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:30:34\"}', '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 40.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:32:14\"}'),
(305, 'tb_ventas_pagos', 'INSERT', '8', NULL, NULL, NULL, NULL, '2026-02-04 01:33:29', NULL, '{\"id_pago\": 8, \"id_venta\": 6, \"id_caja\": 3, \"fecha_pago\": \"2026-02-03 19:33:29\", \"metodo_pago\": \"efectivo\", \"monto\": 40.00, \"referencia\": \"tercerpago\", \"id_usuario\": 2}'),
(306, 'tb_ventas', 'UPDATE', '6', NULL, NULL, NULL, NULL, '2026-02-04 01:33:29', '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 40.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:32:14\"}', '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:33:29\"}'),
(307, 'tb_ventas', 'UPDATE', '6', NULL, NULL, NULL, NULL, '2026-02-04 01:33:29', '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:33:29\"}', '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:33:29\"}'),
(308, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-04 01:57:57', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"56e7d14d5021204a84daf90868e9b43fc630367aa53d9abf2b17d331f6de3cfc\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 04:54:02\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 19:57:57\", \"estado\": \"ACTIVO\"}'),
(309, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-04 01:58:04', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 19:57:57\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"84dbaadf664aaac22d73b7a729de91cf341eff38ec4efea62b05596e42693210\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 19:58:04\", \"estado\": \"ACTIVO\"}'),
(310, 'tb_usuarios', 'UPDATE', '7', NULL, NULL, NULL, NULL, '2026-02-04 02:35:50', '{\"id_usuario\": 7, \"nombres\": \"Marcela orozco\", \"email\": \"mariamarcela@gmail.com\", \"token\": \"ec3ce92bd7e6fd007e5ffdd847ac890b2e506412fb3974f54d0626d74a2f5b1f\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-02 19:58:24\", \"fyh_actualizacion\": \"2026-02-02 20:01:07\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 7, \"nombres\": \"Marcela orozco\", \"email\": \"mariamarcela@gmail.com\", \"token\": \"f2eb004d68d81045a333b910d47ccf48480fca6c81a79c058cdfe2391cd09e45\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-02 19:58:24\", \"fyh_actualizacion\": \"2026-02-03 20:35:50\", \"estado\": \"ACTIVO\"}'),
(311, 'tb_almacen', 'UPDATE', '73', NULL, NULL, NULL, NULL, '2026-02-04 02:41:44', '{\"id_producto\": 73, \"codigo\": \"TRT-AR-001\", \"nombre\": \"Tratamiento Antirreflejante\", \"stock\": 9999, \"stock_minimo\": null, \"stock_maximo\": null, \"precio_compra\": 6.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 4, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 04:05:52\"}', '{\"id_producto\": 73, \"codigo\": \"TRT-AR-001\", \"nombre\": \"Lente oftálmico Anti reflejo\", \"stock\": 9999, \"stock_minimo\": 0, \"stock_maximo\": 0, \"precio_compra\": 0.00, \"precio_venta\": 25.00, \"fecha_ingreso\": \"2026-02-03\", \"id_usuario\": 7, \"id_categoria\": 1, \"estado\": 1, \"fyh_creacion\": \"2026-02-03 04:05:52\", \"fyh_actualizacion\": \"2026-02-03 20:41:44\"}'),
(312, 'tb_usuarios', 'UPDATE', '7', NULL, NULL, NULL, NULL, '2026-02-04 14:26:43', '{\"id_usuario\": 7, \"nombres\": \"Marcela orozco\", \"email\": \"mariamarcela@gmail.com\", \"token\": \"f2eb004d68d81045a333b910d47ccf48480fca6c81a79c058cdfe2391cd09e45\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-02 19:58:24\", \"fyh_actualizacion\": \"2026-02-03 20:35:50\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 7, \"nombres\": \"Marcela orozco\", \"email\": \"mariamarcela@gmail.com\", \"token\": \"2e91549898cd9c502cb98d1158b2c80613f5c1080528cd0078e58cf396cf215a\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-02 19:58:24\", \"fyh_actualizacion\": \"2026-02-04 08:26:43\", \"estado\": \"ACTIVO\"}'),
(313, 'tb_almacen', 'UPDATE', '58', NULL, NULL, NULL, NULL, '2026-02-04 14:35:54', '{\"id_producto\": 58, \"codigo\": \"P-00004\", \"nombre\": \"ESTUCHE PARA LENTE\", \"stock\": 98, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 30.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:52:30\", \"fyh_actualizacion\": \"2026-02-03 04:40:51\"}', '{\"id_producto\": 58, \"codigo\": \"P-00004\", \"nombre\": \"ESTUCHE PARA LENTE\", \"stock\": 100, \"stock_minimo\": 50, \"stock_maximo\": 150, \"precio_compra\": 30.00, \"precio_venta\": 30.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 7, \"id_categoria\": 8, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:52:30\", \"fyh_actualizacion\": \"2026-02-04 08:35:53\"}'),
(314, 'tb_almacen', 'UPDATE', '56', NULL, NULL, NULL, NULL, '2026-02-04 14:37:37', '{\"id_producto\": 56, \"codigo\": \"P-00002\", \"nombre\": \"AROS METALICOS\", \"stock\": 335, \"stock_minimo\": 300, \"stock_maximo\": 600, \"precio_compra\": 250.00, \"precio_venta\": 250.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:46:37\", \"fyh_actualizacion\": \"2026-02-03 04:37:29\"}', '{\"id_producto\": 56, \"codigo\": \"P-00002\", \"nombre\": \"AROS METALICOS\", \"stock\": 335, \"stock_minimo\": 300, \"stock_maximo\": 400, \"precio_compra\": 250.00, \"precio_venta\": 250.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 7, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:46:37\", \"fyh_actualizacion\": \"2026-02-04 08:37:37\"}'),
(315, 'tb_almacen', 'UPDATE', '57', NULL, NULL, NULL, NULL, '2026-02-04 14:39:25', '{\"id_producto\": 57, \"codigo\": \"P-00003\", \"nombre\": \"AROS DE PASTA\", \"stock\": 300, \"stock_minimo\": 250, \"stock_maximo\": 400, \"precio_compra\": 120.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 2, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:51:24\", \"fyh_actualizacion\": \"2026-02-03 03:51:24\"}', '{\"id_producto\": 57, \"codigo\": \"P-00003\", \"nombre\": \"AROS DE PASTA\", \"stock\": 300, \"stock_minimo\": 250, \"stock_maximo\": 400, \"precio_compra\": 120.00, \"precio_venta\": 120.00, \"fecha_ingreso\": \"2026-02-02\", \"id_usuario\": 7, \"id_categoria\": 6, \"estado\": 1, \"fyh_creacion\": \"2026-02-02 18:51:24\", \"fyh_actualizacion\": \"2026-02-04 08:39:24\"}'),
(316, 'tb_ventas_pagos', 'DELETE', '6', NULL, NULL, NULL, NULL, '2026-02-04 15:25:12', '{\"id_pago\": 6, \"id_venta\": 6, \"id_caja\": 3, \"fecha_pago\": \"2026-02-03 19:30:34\", \"metodo_pago\": \"efectivo\", \"monto\": 100.00, \"referencia\": \"primer pago\", \"id_usuario\": 2}', NULL),
(317, 'tb_ventas_pagos', 'DELETE', '7', NULL, NULL, NULL, NULL, '2026-02-04 15:25:12', '{\"id_pago\": 7, \"id_venta\": 6, \"id_caja\": 3, \"fecha_pago\": \"2026-02-03 19:32:14\", \"metodo_pago\": \"efectivo\", \"monto\": 50.00, \"referencia\": \"SEGU pago\", \"id_usuario\": 2}', NULL),
(318, 'tb_ventas_pagos', 'DELETE', '8', NULL, NULL, NULL, NULL, '2026-02-04 15:25:12', '{\"id_pago\": 8, \"id_venta\": 6, \"id_caja\": 3, \"fecha_pago\": \"2026-02-03 19:33:29\", \"metodo_pago\": \"efectivo\", \"monto\": 40.00, \"referencia\": \"tercerpago\", \"id_usuario\": 2}', NULL),
(319, 'tb_ventas_detalle', 'DELETE', '9', NULL, NULL, NULL, NULL, '2026-02-04 15:25:22', '{\"id_detalle\": 9, \"id_venta\": 5, \"id_producto\": 55, \"cantidad\": 1, \"precio_unitario\": 25.00, \"descuento_linea\": 0.00, \"total_linea\": 25.00}', NULL),
(320, 'tb_ventas_detalle', 'DELETE', '10', NULL, NULL, NULL, NULL, '2026-02-04 15:25:22', '{\"id_detalle\": 10, \"id_venta\": 6, \"id_producto\": 55, \"cantidad\": 1, \"precio_unitario\": 200.00, \"descuento_linea\": 0.00, \"total_linea\": 200.00}', NULL),
(321, 'tb_ventas', 'DELETE', '5', NULL, NULL, NULL, NULL, '2026-02-04 15:25:33', '{\"id_venta\": 5, \"nro_venta\": 1, \"fecha_venta\": \"2026-02-03 19:15:52\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 25.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 25.00, \"metodo_pago\": \"efectivo\", \"pagado_inicial\": 25.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:15:52\", \"fyh_actualizacion\": \"2026-02-03 19:15:52\"}', NULL),
(322, 'tb_ventas', 'DELETE', '6', NULL, NULL, NULL, NULL, '2026-02-04 15:25:33', '{\"id_venta\": 6, \"nro_venta\": 2, \"fecha_venta\": \"2026-02-03 19:29:31\", \"id_cliente\": 1, \"id_usuario\": 2, \"id_caja\": 3, \"subtotal\": 200.00, \"descuento\": 0.00, \"impuesto\": 0.00, \"total\": 200.00, \"metodo_pago\": \"mixto\", \"pagado_inicial\": 10.00, \"saldo_pendiente\": 0.00, \"estado\": \"activa\", \"nota\": null, \"fyh_creacion\": \"2026-02-03 19:29:31\", \"fyh_actualizacion\": \"2026-02-03 19:33:29\"}', NULL),
(323, 'tb_cajas', 'DELETE', '3', NULL, NULL, NULL, NULL, '2026-02-04 15:25:46', '{\"id_caja\": 3, \"fecha_apertura\": \"2026-02-03 19:15:31\", \"fecha_cierre\": null, \"usuario_apertura_id\": 2, \"usuario_cierre_id\": null, \"monto_inicial\": 500.00, \"total_efectivo\": 0.00, \"total_deposito\": 0.00, \"total_credito\": 0.00, \"total_abonos\": 0.00, \"total_ingresos\": 0.00, \"total_egresos\": 0.00, \"monto_cierre_efectivo\": null, \"monto_esperado_efectivo\": 0.00, \"diferencia_efectivo\": 0.00, \"estado\": \"abierta\", \"nota\": null, \"observacion_cierre\": null, \"efectivo_contado\": null, \"efectivo_esperado\": null, \"diferencia\": null, \"fyh_creacion\": \"2026-02-03 19:15:31\", \"fyh_actualizacion\": \"2026-02-03 19:15:31\"}', NULL),
(324, 'tb_citas', 'INSERT', '3', NULL, NULL, NULL, NULL, '2026-02-04 15:33:26', NULL, '{\"id_cita\": 3, \"id_cliente\": 1, \"fecha\": \"2026-02-12\", \"hora_inicio\": \"08:00:00\", \"hora_fin\": \"08:30:00\", \"motivo\": \"examen visual\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-04 09:33:26\", \"fyh_actualizacion\": \"2026-02-04 09:33:26\"}'),
(325, 'tb_citas', 'DELETE', '1', NULL, NULL, NULL, NULL, '2026-02-04 15:44:25', '{\"id_cita\": 1, \"id_cliente\": 1, \"fecha\": \"2026-02-05\", \"hora_inicio\": \"12:30:00\", \"hora_fin\": \"13:00:00\", \"motivo\": \"EXamen\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:24:22\", \"fyh_actualizacion\": \"2026-02-03 04:27:12\"}', NULL),
(326, 'tb_citas', 'DELETE', '2', NULL, NULL, NULL, NULL, '2026-02-04 15:44:25', '{\"id_cita\": 2, \"id_cliente\": 2, \"fecha\": \"2026-02-04\", \"hora_inicio\": \"08:00:00\", \"hora_fin\": \"08:30:00\", \"motivo\": \"EXamen\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-03 04:28:46\", \"fyh_actualizacion\": \"2026-02-03 04:28:46\"}', NULL),
(327, 'tb_citas', 'DELETE', '3', NULL, NULL, NULL, NULL, '2026-02-04 15:44:25', '{\"id_cita\": 3, \"id_cliente\": 1, \"fecha\": \"2026-02-12\", \"hora_inicio\": \"08:00:00\", \"hora_fin\": \"08:30:00\", \"motivo\": \"examen visual\", \"estado\": \"programada\", \"id_usuario\": 2, \"fyh_creacion\": \"2026-02-04 09:33:26\", \"fyh_actualizacion\": \"2026-02-04 09:33:26\"}', NULL),
(328, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-05 02:15:09', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"84dbaadf664aaac22d73b7a729de91cf341eff38ec4efea62b05596e42693210\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-03 19:58:04\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"064e8b31c9a6dcc698c807c560746cdf878ef37b6207637cfdc09e729cc8aeae\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-04 20:15:09\", \"estado\": \"ACTIVO\"}'),
(329, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-08 07:52:29', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"064e8b31c9a6dcc698c807c560746cdf878ef37b6207637cfdc09e729cc8aeae\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-04 20:15:09\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"4c1ef178edb13e93e649544b0d8b5f227d128705a6e55828e1ad9f3fc2a575f4\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-08 01:52:29\", \"estado\": \"ACTIVO\"}'),
(330, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-08 07:54:02', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"4c1ef178edb13e93e649544b0d8b5f227d128705a6e55828e1ad9f3fc2a575f4\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-08 01:52:29\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-08 01:54:02\", \"estado\": \"ACTIVO\"}'),
(331, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-08 19:35:37', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-08 01:54:02\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"ca18e100f3d5894db4f400a5dfe89613b4e6f45e09ad6be982d02d0af2b348ed\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-08 13:35:37\", \"estado\": \"ACTIVO\"}'),
(332, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-09 18:53:15', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"ca18e100f3d5894db4f400a5dfe89613b4e6f45e09ad6be982d02d0af2b348ed\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-08 13:35:37\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"c0c0bc9c1d37e29f45f224f4070fbfd6a3923cff95cc1ec8d431e158d4dad345\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-09 12:53:15\", \"estado\": \"ACTIVO\"}');
INSERT INTO `tb_auditoria` (`id_auditoria`, `tabla`, `accion`, `pk`, `usuario_id`, `usuario_email`, `ip`, `user_agent`, `fecha`, `antes`, `despues`) VALUES
(333, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-09 18:54:12', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"c0c0bc9c1d37e29f45f224f4070fbfd6a3923cff95cc1ec8d431e158d4dad345\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-09 12:53:15\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-09 12:54:12\", \"estado\": \"ACTIVO\"}'),
(334, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-10 03:43:19', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-09 12:54:12\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"acf764b39dbeb69b6ffd94f880ea6fc2c32212f2e862bcaf4cec54470270fdee\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-09 21:43:19\", \"estado\": \"ACTIVO\"}'),
(335, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-10 21:16:48', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"acf764b39dbeb69b6ffd94f880ea6fc2c32212f2e862bcaf4cec54470270fdee\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-09 21:43:19\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-10 15:16:48\", \"estado\": \"ACTIVO\"}'),
(336, 'tb_usuarios', 'UPDATE', '2', NULL, NULL, NULL, NULL, '2026-02-10 21:40:56', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": null, \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-10 15:16:48\", \"estado\": \"ACTIVO\"}', '{\"id_usuario\": 2, \"nombres\": \"Jefferson Zamora\", \"email\": \"admin@devzamora.com\", \"token\": \"aa215c0dc8bf5c2e3375c575e12ecf72c263fcdb395c01d7b06a53d093e773a5\", \"id_rol\": 1, \"fyh_creacion\": \"2026-02-01 08:43:05\", \"fyh_actualizacion\": \"2026-02-10 15:40:56\", \"estado\": \"ACTIVO\"}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_cajas`
--

CREATE TABLE `tb_cajas` (
  `id_caja` int(11) NOT NULL,
  `fecha_apertura` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `usuario_apertura_id` int(11) NOT NULL,
  `usuario_cierre_id` int(11) DEFAULT NULL,
  `monto_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_deposito` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_credito` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_abonos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_ingresos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_egresos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monto_cierre_efectivo` decimal(10,2) DEFAULT NULL,
  `monto_esperado_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `diferencia_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  `nota` varchar(255) DEFAULT NULL,
  `observacion_cierre` varchar(255) DEFAULT NULL,
  `efectivo_contado` decimal(10,2) DEFAULT NULL,
  `efectivo_esperado` decimal(10,2) DEFAULT NULL,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `tb_cajas`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_cajas_del` AFTER DELETE ON `tb_cajas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_cajas','DELETE',CAST(OLD.`id_caja` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_caja', OLD.`id_caja`, 'fecha_apertura', OLD.`fecha_apertura`, 'fecha_cierre', OLD.`fecha_cierre`, 'usuario_apertura_id', OLD.`usuario_apertura_id`, 'usuario_cierre_id', OLD.`usuario_cierre_id`, 'monto_inicial', OLD.`monto_inicial`, 'total_efectivo', OLD.`total_efectivo`, 'total_deposito', OLD.`total_deposito`, 'total_credito', OLD.`total_credito`, 'total_abonos', OLD.`total_abonos`, 'total_ingresos', OLD.`total_ingresos`, 'total_egresos', OLD.`total_egresos`, 'monto_cierre_efectivo', OLD.`monto_cierre_efectivo`, 'monto_esperado_efectivo', OLD.`monto_esperado_efectivo`, 'diferencia_efectivo', OLD.`diferencia_efectivo`, 'estado', OLD.`estado`, 'nota', OLD.`nota`, 'observacion_cierre', OLD.`observacion_cierre`, 'efectivo_contado', OLD.`efectivo_contado`, 'efectivo_esperado', OLD.`efectivo_esperado`, 'diferencia', OLD.`diferencia`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_cajas_ins` AFTER INSERT ON `tb_cajas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_cajas','INSERT',CAST(NEW.`id_caja` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_caja', NEW.`id_caja`, 'fecha_apertura', NEW.`fecha_apertura`, 'fecha_cierre', NEW.`fecha_cierre`, 'usuario_apertura_id', NEW.`usuario_apertura_id`, 'usuario_cierre_id', NEW.`usuario_cierre_id`, 'monto_inicial', NEW.`monto_inicial`, 'total_efectivo', NEW.`total_efectivo`, 'total_deposito', NEW.`total_deposito`, 'total_credito', NEW.`total_credito`, 'total_abonos', NEW.`total_abonos`, 'total_ingresos', NEW.`total_ingresos`, 'total_egresos', NEW.`total_egresos`, 'monto_cierre_efectivo', NEW.`monto_cierre_efectivo`, 'monto_esperado_efectivo', NEW.`monto_esperado_efectivo`, 'diferencia_efectivo', NEW.`diferencia_efectivo`, 'estado', NEW.`estado`, 'nota', NEW.`nota`, 'observacion_cierre', NEW.`observacion_cierre`, 'efectivo_contado', NEW.`efectivo_contado`, 'efectivo_esperado', NEW.`efectivo_esperado`, 'diferencia', NEW.`diferencia`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_cajas_upd` AFTER UPDATE ON `tb_cajas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_cajas','UPDATE',CAST(NEW.`id_caja` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_caja', OLD.`id_caja`, 'fecha_apertura', OLD.`fecha_apertura`, 'fecha_cierre', OLD.`fecha_cierre`, 'usuario_apertura_id', OLD.`usuario_apertura_id`, 'usuario_cierre_id', OLD.`usuario_cierre_id`, 'monto_inicial', OLD.`monto_inicial`, 'total_efectivo', OLD.`total_efectivo`, 'total_deposito', OLD.`total_deposito`, 'total_credito', OLD.`total_credito`, 'total_abonos', OLD.`total_abonos`, 'total_ingresos', OLD.`total_ingresos`, 'total_egresos', OLD.`total_egresos`, 'monto_cierre_efectivo', OLD.`monto_cierre_efectivo`, 'monto_esperado_efectivo', OLD.`monto_esperado_efectivo`, 'diferencia_efectivo', OLD.`diferencia_efectivo`, 'estado', OLD.`estado`, 'nota', OLD.`nota`, 'observacion_cierre', OLD.`observacion_cierre`, 'efectivo_contado', OLD.`efectivo_contado`, 'efectivo_esperado', OLD.`efectivo_esperado`, 'diferencia', OLD.`diferencia`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_caja', NEW.`id_caja`, 'fecha_apertura', NEW.`fecha_apertura`, 'fecha_cierre', NEW.`fecha_cierre`, 'usuario_apertura_id', NEW.`usuario_apertura_id`, 'usuario_cierre_id', NEW.`usuario_cierre_id`, 'monto_inicial', NEW.`monto_inicial`, 'total_efectivo', NEW.`total_efectivo`, 'total_deposito', NEW.`total_deposito`, 'total_credito', NEW.`total_credito`, 'total_abonos', NEW.`total_abonos`, 'total_ingresos', NEW.`total_ingresos`, 'total_egresos', NEW.`total_egresos`, 'monto_cierre_efectivo', NEW.`monto_cierre_efectivo`, 'monto_esperado_efectivo', NEW.`monto_esperado_efectivo`, 'diferencia_efectivo', NEW.`diferencia_efectivo`, 'estado', NEW.`estado`, 'nota', NEW.`nota`, 'observacion_cierre', NEW.`observacion_cierre`, 'efectivo_contado', NEW.`efectivo_contado`, 'efectivo_esperado', NEW.`efectivo_esperado`, 'diferencia', NEW.`diferencia`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_caja_movimientos`
--

CREATE TABLE `tb_caja_movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `id_caja` int(11) NOT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `concepto` varchar(150) NOT NULL,
  `metodo_pago` enum('efectivo','deposito') NOT NULL DEFAULT 'efectivo',
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `referencia` varchar(100) DEFAULT NULL,
  `estado` enum('activo','anulado') NOT NULL DEFAULT 'activo',
  `anulado_por` int(11) DEFAULT NULL,
  `anulado_at` datetime DEFAULT NULL,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `id_movimiento_ajuste` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `tb_caja_movimientos`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_caja_movimientos_del` AFTER DELETE ON `tb_caja_movimientos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_caja_movimientos','DELETE',CAST(OLD.`id_movimiento` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_movimiento', OLD.`id_movimiento`, 'id_caja', OLD.`id_caja`, 'tipo', OLD.`tipo`, 'concepto', OLD.`concepto`, 'metodo_pago', OLD.`metodo_pago`, 'monto', OLD.`monto`, 'referencia', OLD.`referencia`, 'estado', OLD.`estado`, 'anulado_por', OLD.`anulado_por`, 'anulado_at', OLD.`anulado_at`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'id_movimiento_ajuste', OLD.`id_movimiento_ajuste`, 'fecha', OLD.`fecha`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_caja_movimientos_ins` AFTER INSERT ON `tb_caja_movimientos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_caja_movimientos','INSERT',CAST(NEW.`id_movimiento` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_movimiento', NEW.`id_movimiento`, 'id_caja', NEW.`id_caja`, 'tipo', NEW.`tipo`, 'concepto', NEW.`concepto`, 'metodo_pago', NEW.`metodo_pago`, 'monto', NEW.`monto`, 'referencia', NEW.`referencia`, 'estado', NEW.`estado`, 'anulado_por', NEW.`anulado_por`, 'anulado_at', NEW.`anulado_at`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'id_movimiento_ajuste', NEW.`id_movimiento_ajuste`, 'fecha', NEW.`fecha`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_caja_movimientos_upd` AFTER UPDATE ON `tb_caja_movimientos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_caja_movimientos','UPDATE',CAST(NEW.`id_movimiento` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_movimiento', OLD.`id_movimiento`, 'id_caja', OLD.`id_caja`, 'tipo', OLD.`tipo`, 'concepto', OLD.`concepto`, 'metodo_pago', OLD.`metodo_pago`, 'monto', OLD.`monto`, 'referencia', OLD.`referencia`, 'estado', OLD.`estado`, 'anulado_por', OLD.`anulado_por`, 'anulado_at', OLD.`anulado_at`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'id_movimiento_ajuste', OLD.`id_movimiento_ajuste`, 'fecha', OLD.`fecha`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_movimiento', NEW.`id_movimiento`, 'id_caja', NEW.`id_caja`, 'tipo', NEW.`tipo`, 'concepto', NEW.`concepto`, 'metodo_pago', NEW.`metodo_pago`, 'monto', NEW.`monto`, 'referencia', NEW.`referencia`, 'estado', NEW.`estado`, 'anulado_por', NEW.`anulado_por`, 'anulado_at', NEW.`anulado_at`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'id_movimiento_ajuste', NEW.`id_movimiento_ajuste`, 'fecha', NEW.`fecha`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_categorias`
--

CREATE TABLE `tb_categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(255) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'ACTIVO',
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_categorias`
--

INSERT INTO `tb_categorias` (`id_categoria`, `nombre_categoria`, `estado`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 'LENTES OFTALMICOS', 'ACTIVO', '2026-02-01 11:22:37', '2026-02-01 12:10:56'),
(2, 'EXAMENES', 'ACTIVO', '2026-02-01 11:25:22', '2026-02-01 20:25:22'),
(3, 'LENTES DE SOL', 'ACTIVO', '2026-02-01 12:10:25', '2026-02-01 21:10:25'),
(4, 'LENTES DE CONTACTOS', 'ACTIVO', '2026-02-01 12:10:40', '2026-02-01 21:10:40'),
(5, 'PRODUCTOS PARA LIMPIEZA', 'ACTIVO', '2026-02-01 12:11:12', '2026-02-01 21:11:12'),
(6, 'AROS / MARCOS', 'ACTIVO', '2026-02-01 12:11:41', '2026-02-02 18:36:32'),
(7, 'REPARACIONES', 'ACTIVO', '2026-02-01 12:12:21', '2026-02-02 18:38:18'),
(8, 'ESTUCHE', 'ACTIVO', '2026-02-01 12:12:35', '2026-02-02 18:37:56'),
(10, 'DEMO', 'ACTIVO', '2026-02-02 18:28:29', '2026-02-03 03:28:29');

--
-- Disparadores `tb_categorias`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_categorias_del` AFTER DELETE ON `tb_categorias` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_categorias','DELETE',CAST(OLD.`id_categoria` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_categoria', OLD.`id_categoria`, 'nombre_categoria', OLD.`nombre_categoria`, 'estado', OLD.`estado`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_categorias_ins` AFTER INSERT ON `tb_categorias` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_categorias','INSERT',CAST(NEW.`id_categoria` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_categoria', NEW.`id_categoria`, 'nombre_categoria', NEW.`nombre_categoria`, 'estado', NEW.`estado`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_categorias_upd` AFTER UPDATE ON `tb_categorias` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_categorias','UPDATE',CAST(NEW.`id_categoria` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_categoria', OLD.`id_categoria`, 'nombre_categoria', OLD.`nombre_categoria`, 'estado', OLD.`estado`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_categoria', NEW.`id_categoria`, 'nombre_categoria', NEW.`nombre_categoria`, 'estado', NEW.`estado`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_citas`
--

CREATE TABLE `tb_citas` (
  `id_cita` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `estado` enum('programada','cancelada','atendida') NOT NULL DEFAULT 'programada',
  `id_usuario` int(11) DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `tb_citas`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_citas_del` AFTER DELETE ON `tb_citas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_citas','DELETE',CAST(OLD.`id_cita` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_cita', OLD.`id_cita`, 'id_cliente', OLD.`id_cliente`, 'fecha', OLD.`fecha`, 'hora_inicio', OLD.`hora_inicio`, 'hora_fin', OLD.`hora_fin`, 'motivo', OLD.`motivo`, 'estado', OLD.`estado`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_citas_ins` AFTER INSERT ON `tb_citas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_citas','INSERT',CAST(NEW.`id_cita` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_cita', NEW.`id_cita`, 'id_cliente', NEW.`id_cliente`, 'fecha', NEW.`fecha`, 'hora_inicio', NEW.`hora_inicio`, 'hora_fin', NEW.`hora_fin`, 'motivo', NEW.`motivo`, 'estado', NEW.`estado`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_citas_upd` AFTER UPDATE ON `tb_citas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_citas','UPDATE',CAST(NEW.`id_cita` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_cita', OLD.`id_cita`, 'id_cliente', OLD.`id_cliente`, 'fecha', OLD.`fecha`, 'hora_inicio', OLD.`hora_inicio`, 'hora_fin', OLD.`hora_fin`, 'motivo', OLD.`motivo`, 'estado', OLD.`estado`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_cita', NEW.`id_cita`, 'id_cliente', NEW.`id_cliente`, 'fecha', NEW.`fecha`, 'hora_inicio', NEW.`hora_inicio`, 'hora_fin', NEW.`hora_fin`, 'motivo', NEW.`motivo`, 'estado', NEW.`estado`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_citas_bloqueos`
--

CREATE TABLE `tb_citas_bloqueos` (
  `id_bloqueo` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_citas_bloqueos`
--

INSERT INTO `tb_citas_bloqueos` (`id_bloqueo`, `fecha`, `hora_inicio`, `hora_fin`, `motivo`, `activo`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, '2026-08-01', '08:36:00', '20:36:00', 'FERIADO NACIONAL', 1, '2026-02-01 20:36:53', '2026-02-01 20:36:53');

--
-- Disparadores `tb_citas_bloqueos`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_citas_bloqueos_del` AFTER DELETE ON `tb_citas_bloqueos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_citas_bloqueos','DELETE',CAST(OLD.`id_bloqueo` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_bloqueo', OLD.`id_bloqueo`, 'fecha', OLD.`fecha`, 'hora_inicio', OLD.`hora_inicio`, 'hora_fin', OLD.`hora_fin`, 'motivo', OLD.`motivo`, 'activo', OLD.`activo`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_citas_bloqueos_ins` AFTER INSERT ON `tb_citas_bloqueos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_citas_bloqueos','INSERT',CAST(NEW.`id_bloqueo` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_bloqueo', NEW.`id_bloqueo`, 'fecha', NEW.`fecha`, 'hora_inicio', NEW.`hora_inicio`, 'hora_fin', NEW.`hora_fin`, 'motivo', NEW.`motivo`, 'activo', NEW.`activo`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_citas_bloqueos_upd` AFTER UPDATE ON `tb_citas_bloqueos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_citas_bloqueos','UPDATE',CAST(NEW.`id_bloqueo` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_bloqueo', OLD.`id_bloqueo`, 'fecha', OLD.`fecha`, 'hora_inicio', OLD.`hora_inicio`, 'hora_fin', OLD.`hora_fin`, 'motivo', OLD.`motivo`, 'activo', OLD.`activo`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_bloqueo', NEW.`id_bloqueo`, 'fecha', NEW.`fecha`, 'hora_inicio', NEW.`hora_inicio`, 'hora_fin', NEW.`hora_fin`, 'motivo', NEW.`motivo`, 'activo', NEW.`activo`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_clientes`
--

CREATE TABLE `tb_clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `apellido` varchar(120) NOT NULL,
  `tipo_documento` varchar(30) NOT NULL,
  `numero_documento` varchar(60) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `celular` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_clientes`
--

INSERT INTO `tb_clientes` (`id_cliente`, `nombre`, `apellido`, `tipo_documento`, `numero_documento`, `fecha_nacimiento`, `celular`, `email`, `direccion`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 'DEmo demo', 'demo', 'Cédula', '001113909347N', NULL, '8888888', 'demo@demo.com', 'demo del otro lado', '2026-02-03 04:13:26', '2026-02-03 04:13:26'),
(2, 'demo jeff', 'zamora', 'CED', '909090909', NULL, '8888888', 'demo@demo.com', 'demo del otro lado', '2026-02-03 04:28:39', '2026-02-03 04:28:39');

--
-- Disparadores `tb_clientes`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_clientes_del` AFTER DELETE ON `tb_clientes` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_clientes','DELETE',CAST(OLD.`id_cliente` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_cliente', OLD.`id_cliente`, 'nombre', OLD.`nombre`, 'apellido', OLD.`apellido`, 'tipo_documento', OLD.`tipo_documento`, 'numero_documento', OLD.`numero_documento`, 'fecha_nacimiento', OLD.`fecha_nacimiento`, 'celular', OLD.`celular`, 'email', OLD.`email`, 'direccion', OLD.`direccion`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_clientes_ins` AFTER INSERT ON `tb_clientes` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_clientes','INSERT',CAST(NEW.`id_cliente` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_cliente', NEW.`id_cliente`, 'nombre', NEW.`nombre`, 'apellido', NEW.`apellido`, 'tipo_documento', NEW.`tipo_documento`, 'numero_documento', NEW.`numero_documento`, 'fecha_nacimiento', NEW.`fecha_nacimiento`, 'celular', NEW.`celular`, 'email', NEW.`email`, 'direccion', NEW.`direccion`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_clientes_upd` AFTER UPDATE ON `tb_clientes` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_clientes','UPDATE',CAST(NEW.`id_cliente` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_cliente', OLD.`id_cliente`, 'nombre', OLD.`nombre`, 'apellido', OLD.`apellido`, 'tipo_documento', OLD.`tipo_documento`, 'numero_documento', OLD.`numero_documento`, 'fecha_nacimiento', OLD.`fecha_nacimiento`, 'celular', OLD.`celular`, 'email', OLD.`email`, 'direccion', OLD.`direccion`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_cliente', NEW.`id_cliente`, 'nombre', NEW.`nombre`, 'apellido', NEW.`apellido`, 'tipo_documento', NEW.`tipo_documento`, 'numero_documento', NEW.`numero_documento`, 'fecha_nacimiento', NEW.`fecha_nacimiento`, 'celular', NEW.`celular`, 'email', NEW.`email`, 'direccion', NEW.`direccion`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_compras`
--

CREATE TABLE `tb_compras` (
  `id_compra` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `nro_compra` int(11) NOT NULL,
  `fecha_compra` date NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `comprobante` varchar(255) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `estado` enum('ACTIVO','ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `fyh_anulado` datetime DEFAULT NULL,
  `anulado_por` int(11) DEFAULT NULL,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_compras`
--

INSERT INTO `tb_compras` (`id_compra`, `id_producto`, `nro_compra`, `fecha_compra`, `id_proveedor`, `comprobante`, `id_usuario`, `precio_compra`, `cantidad`, `estado`, `fyh_anulado`, `anulado_por`, `motivo_anulacion`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 55, 1, '2026-02-02', 1, '333434', 2, 12.00, 6, 'ACTIVO', NULL, NULL, NULL, '2026-02-02 18:31:49', '2026-02-03 03:31:49');

--
-- Disparadores `tb_compras`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_compras_del` AFTER DELETE ON `tb_compras` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_compras','DELETE',CAST(OLD.`id_compra` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_compra', OLD.`id_compra`, 'id_producto', OLD.`id_producto`, 'nro_compra', OLD.`nro_compra`, 'fecha_compra', OLD.`fecha_compra`, 'id_proveedor', OLD.`id_proveedor`, 'comprobante', OLD.`comprobante`, 'id_usuario', OLD.`id_usuario`, 'precio_compra', OLD.`precio_compra`, 'cantidad', OLD.`cantidad`, 'estado', OLD.`estado`, 'fyh_anulado', OLD.`fyh_anulado`, 'anulado_por', OLD.`anulado_por`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_compras_ins` AFTER INSERT ON `tb_compras` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_compras','INSERT',CAST(NEW.`id_compra` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_compra', NEW.`id_compra`, 'id_producto', NEW.`id_producto`, 'nro_compra', NEW.`nro_compra`, 'fecha_compra', NEW.`fecha_compra`, 'id_proveedor', NEW.`id_proveedor`, 'comprobante', NEW.`comprobante`, 'id_usuario', NEW.`id_usuario`, 'precio_compra', NEW.`precio_compra`, 'cantidad', NEW.`cantidad`, 'estado', NEW.`estado`, 'fyh_anulado', NEW.`fyh_anulado`, 'anulado_por', NEW.`anulado_por`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_compras_upd` AFTER UPDATE ON `tb_compras` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_compras','UPDATE',CAST(NEW.`id_compra` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_compra', OLD.`id_compra`, 'id_producto', OLD.`id_producto`, 'nro_compra', OLD.`nro_compra`, 'fecha_compra', OLD.`fecha_compra`, 'id_proveedor', OLD.`id_proveedor`, 'comprobante', OLD.`comprobante`, 'id_usuario', OLD.`id_usuario`, 'precio_compra', OLD.`precio_compra`, 'cantidad', OLD.`cantidad`, 'estado', OLD.`estado`, 'fyh_anulado', OLD.`fyh_anulado`, 'anulado_por', OLD.`anulado_por`, 'motivo_anulacion', OLD.`motivo_anulacion`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_compra', NEW.`id_compra`, 'id_producto', NEW.`id_producto`, 'nro_compra', NEW.`nro_compra`, 'fecha_compra', NEW.`fecha_compra`, 'id_proveedor', NEW.`id_proveedor`, 'comprobante', NEW.`comprobante`, 'id_usuario', NEW.`id_usuario`, 'precio_compra', NEW.`precio_compra`, 'cantidad', NEW.`cantidad`, 'estado', NEW.`estado`, 'fyh_anulado', NEW.`fyh_anulado`, 'anulado_por', NEW.`anulado_por`, 'motivo_anulacion', NEW.`motivo_anulacion`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_devoluciones`
--

CREATE TABLE `tb_devoluciones` (
  `id_devolucion` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_caja` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `metodo_pago` enum('efectivo','deposito') NOT NULL DEFAULT 'efectivo',
  `monto_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `referencia` varchar(100) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `tb_devoluciones`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_devoluciones_del` AFTER DELETE ON `tb_devoluciones` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_devoluciones','DELETE',CAST(OLD.`id_devolucion` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_devolucion', OLD.`id_devolucion`, 'id_venta', OLD.`id_venta`, 'id_caja', OLD.`id_caja`, 'fecha', OLD.`fecha`, 'metodo_pago', OLD.`metodo_pago`, 'monto_total', OLD.`monto_total`, 'referencia', OLD.`referencia`, 'motivo', OLD.`motivo`, 'id_usuario', OLD.`id_usuario`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_devoluciones_ins` AFTER INSERT ON `tb_devoluciones` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_devoluciones','INSERT',CAST(NEW.`id_devolucion` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_devolucion', NEW.`id_devolucion`, 'id_venta', NEW.`id_venta`, 'id_caja', NEW.`id_caja`, 'fecha', NEW.`fecha`, 'metodo_pago', NEW.`metodo_pago`, 'monto_total', NEW.`monto_total`, 'referencia', NEW.`referencia`, 'motivo', NEW.`motivo`, 'id_usuario', NEW.`id_usuario`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_devoluciones_upd` AFTER UPDATE ON `tb_devoluciones` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_devoluciones','UPDATE',CAST(NEW.`id_devolucion` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_devolucion', OLD.`id_devolucion`, 'id_venta', OLD.`id_venta`, 'id_caja', OLD.`id_caja`, 'fecha', OLD.`fecha`, 'metodo_pago', OLD.`metodo_pago`, 'monto_total', OLD.`monto_total`, 'referencia', OLD.`referencia`, 'motivo', OLD.`motivo`, 'id_usuario', OLD.`id_usuario`),JSON_OBJECT('id_devolucion', NEW.`id_devolucion`, 'id_venta', NEW.`id_venta`, 'id_caja', NEW.`id_caja`, 'fecha', NEW.`fecha`, 'metodo_pago', NEW.`metodo_pago`, 'monto_total', NEW.`monto_total`, 'referencia', NEW.`referencia`, 'motivo', NEW.`motivo`, 'id_usuario', NEW.`id_usuario`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_devoluciones_detalle`
--

CREATE TABLE `tb_devoluciones_detalle` (
  `id_detalle_dev` int(11) NOT NULL,
  `id_devolucion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monto_linea` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `tb_devoluciones_detalle`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_devoluciones_detalle_del` AFTER DELETE ON `tb_devoluciones_detalle` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_devoluciones_detalle','DELETE',CAST(OLD.`id_detalle_dev` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_detalle_dev', OLD.`id_detalle_dev`, 'id_devolucion', OLD.`id_devolucion`, 'id_producto', OLD.`id_producto`, 'cantidad', OLD.`cantidad`, 'precio_unitario', OLD.`precio_unitario`, 'monto_linea', OLD.`monto_linea`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_devoluciones_detalle_ins` AFTER INSERT ON `tb_devoluciones_detalle` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_devoluciones_detalle','INSERT',CAST(NEW.`id_detalle_dev` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_detalle_dev', NEW.`id_detalle_dev`, 'id_devolucion', NEW.`id_devolucion`, 'id_producto', NEW.`id_producto`, 'cantidad', NEW.`cantidad`, 'precio_unitario', NEW.`precio_unitario`, 'monto_linea', NEW.`monto_linea`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_devoluciones_detalle_upd` AFTER UPDATE ON `tb_devoluciones_detalle` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_devoluciones_detalle','UPDATE',CAST(NEW.`id_detalle_dev` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_detalle_dev', OLD.`id_detalle_dev`, 'id_devolucion', OLD.`id_devolucion`, 'id_producto', OLD.`id_producto`, 'cantidad', OLD.`cantidad`, 'precio_unitario', OLD.`precio_unitario`, 'monto_linea', OLD.`monto_linea`),JSON_OBJECT('id_detalle_dev', NEW.`id_detalle_dev`, 'id_devolucion', NEW.`id_devolucion`, 'id_producto', NEW.`id_producto`, 'cantidad', NEW.`cantidad`, 'precio_unitario', NEW.`precio_unitario`, 'monto_linea', NEW.`monto_linea`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_examenes_optometricos`
--

CREATE TABLE `tb_examenes_optometricos` (
  `id_examen` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `fecha_examen` date NOT NULL,
  `od_esfera` varchar(12) DEFAULT NULL,
  `od_cilindro` varchar(12) DEFAULT NULL,
  `od_eje` varchar(12) DEFAULT NULL,
  `od_add` varchar(12) DEFAULT NULL,
  `od_prisma` varchar(12) DEFAULT NULL,
  `od_base` varchar(12) DEFAULT NULL,
  `oi_esfera` varchar(12) DEFAULT NULL,
  `oi_cilindro` varchar(12) DEFAULT NULL,
  `oi_eje` varchar(12) DEFAULT NULL,
  `oi_add` varchar(12) DEFAULT NULL,
  `oi_prisma` varchar(12) DEFAULT NULL,
  `oi_base` varchar(12) DEFAULT NULL,
  `pd_lejos` varchar(12) DEFAULT NULL,
  `pd_cerca` varchar(12) DEFAULT NULL,
  `notas_optometrista` text DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_examenes_optometricos`
--

INSERT INTO `tb_examenes_optometricos` (`id_examen`, `id_cliente`, `fecha_examen`, `od_esfera`, `od_cilindro`, `od_eje`, `od_add`, `od_prisma`, `od_base`, `oi_esfera`, `oi_cilindro`, `oi_eje`, `oi_add`, `oi_prisma`, `oi_base`, `pd_lejos`, `pd_cerca`, `notas_optometrista`, `id_usuario`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 1, '2026-02-02', '0.25', '0.25', '1', '0.25', '0.25', 'in', '0.25', '0.25', '1', '0.25', '0.25', 'in', '334.00', '34.00', 'Necesita lentes pogresivo', 2, '2026-02-03 04:18:15', '2026-02-03 04:18:15');

--
-- Disparadores `tb_examenes_optometricos`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_examenes_optometricos_del` AFTER DELETE ON `tb_examenes_optometricos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_examenes_optometricos','DELETE',CAST(OLD.`id_examen` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_examen', OLD.`id_examen`, 'id_cliente', OLD.`id_cliente`, 'fecha_examen', OLD.`fecha_examen`, 'od_esfera', OLD.`od_esfera`, 'od_cilindro', OLD.`od_cilindro`, 'od_eje', OLD.`od_eje`, 'od_add', OLD.`od_add`, 'od_prisma', OLD.`od_prisma`, 'od_base', OLD.`od_base`, 'oi_esfera', OLD.`oi_esfera`, 'oi_cilindro', OLD.`oi_cilindro`, 'oi_eje', OLD.`oi_eje`, 'oi_add', OLD.`oi_add`, 'oi_prisma', OLD.`oi_prisma`, 'oi_base', OLD.`oi_base`, 'pd_lejos', OLD.`pd_lejos`, 'pd_cerca', OLD.`pd_cerca`, 'notas_optometrista', OLD.`notas_optometrista`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_examenes_optometricos_ins` AFTER INSERT ON `tb_examenes_optometricos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_examenes_optometricos','INSERT',CAST(NEW.`id_examen` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_examen', NEW.`id_examen`, 'id_cliente', NEW.`id_cliente`, 'fecha_examen', NEW.`fecha_examen`, 'od_esfera', NEW.`od_esfera`, 'od_cilindro', NEW.`od_cilindro`, 'od_eje', NEW.`od_eje`, 'od_add', NEW.`od_add`, 'od_prisma', NEW.`od_prisma`, 'od_base', NEW.`od_base`, 'oi_esfera', NEW.`oi_esfera`, 'oi_cilindro', NEW.`oi_cilindro`, 'oi_eje', NEW.`oi_eje`, 'oi_add', NEW.`oi_add`, 'oi_prisma', NEW.`oi_prisma`, 'oi_base', NEW.`oi_base`, 'pd_lejos', NEW.`pd_lejos`, 'pd_cerca', NEW.`pd_cerca`, 'notas_optometrista', NEW.`notas_optometrista`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_examenes_optometricos_upd` AFTER UPDATE ON `tb_examenes_optometricos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_examenes_optometricos','UPDATE',CAST(NEW.`id_examen` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_examen', OLD.`id_examen`, 'id_cliente', OLD.`id_cliente`, 'fecha_examen', OLD.`fecha_examen`, 'od_esfera', OLD.`od_esfera`, 'od_cilindro', OLD.`od_cilindro`, 'od_eje', OLD.`od_eje`, 'od_add', OLD.`od_add`, 'od_prisma', OLD.`od_prisma`, 'od_base', OLD.`od_base`, 'oi_esfera', OLD.`oi_esfera`, 'oi_cilindro', OLD.`oi_cilindro`, 'oi_eje', OLD.`oi_eje`, 'oi_add', OLD.`oi_add`, 'oi_prisma', OLD.`oi_prisma`, 'oi_base', OLD.`oi_base`, 'pd_lejos', OLD.`pd_lejos`, 'pd_cerca', OLD.`pd_cerca`, 'notas_optometrista', OLD.`notas_optometrista`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_examen', NEW.`id_examen`, 'id_cliente', NEW.`id_cliente`, 'fecha_examen', NEW.`fecha_examen`, 'od_esfera', NEW.`od_esfera`, 'od_cilindro', NEW.`od_cilindro`, 'od_eje', NEW.`od_eje`, 'od_add', NEW.`od_add`, 'od_prisma', NEW.`od_prisma`, 'od_base', NEW.`od_base`, 'oi_esfera', NEW.`oi_esfera`, 'oi_cilindro', NEW.`oi_cilindro`, 'oi_eje', NEW.`oi_eje`, 'oi_add', NEW.`oi_add`, 'oi_prisma', NEW.`oi_prisma`, 'oi_base', NEW.`oi_base`, 'pd_lejos', NEW.`pd_lejos`, 'pd_cerca', NEW.`pd_cerca`, 'notas_optometrista', NEW.`notas_optometrista`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_horario_laboral`
--

CREATE TABLE `tb_horario_laboral` (
  `id_horario` int(11) NOT NULL,
  `dia_semana` tinyint(4) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_horario_laboral`
--

INSERT INTO `tb_horario_laboral` (`id_horario`, `dia_semana`, `hora_inicio`, `hora_fin`, `activo`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 1, '08:00:00', '17:00:00', 0, '2026-02-03 04:23:28', '2026-02-03 04:23:28'),
(2, 2, '08:30:00', '17:30:00', 1, '2026-02-03 04:23:28', '2026-02-03 04:23:28'),
(3, 3, '08:30:00', '17:00:00', 1, '2026-02-03 04:23:28', '2026-02-03 04:23:28'),
(4, 4, '08:00:00', '17:00:00', 1, '2026-02-03 04:23:28', '2026-02-03 04:23:28'),
(5, 5, '08:00:00', '17:00:00', 1, '2026-02-03 04:23:28', '2026-02-03 04:23:28'),
(6, 6, '08:00:00', '17:00:00', 1, '2026-02-03 04:23:28', '2026-02-03 04:23:28'),
(7, 7, '08:00:00', '14:00:00', 1, '2026-02-03 04:23:28', '2026-02-03 04:23:28');

--
-- Disparadores `tb_horario_laboral`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_horario_laboral_del` AFTER DELETE ON `tb_horario_laboral` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_horario_laboral','DELETE',CAST(OLD.`id_horario` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_horario', OLD.`id_horario`, 'dia_semana', OLD.`dia_semana`, 'hora_inicio', OLD.`hora_inicio`, 'hora_fin', OLD.`hora_fin`, 'activo', OLD.`activo`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_horario_laboral_ins` AFTER INSERT ON `tb_horario_laboral` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_horario_laboral','INSERT',CAST(NEW.`id_horario` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_horario', NEW.`id_horario`, 'dia_semana', NEW.`dia_semana`, 'hora_inicio', NEW.`hora_inicio`, 'hora_fin', NEW.`hora_fin`, 'activo', NEW.`activo`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_horario_laboral_upd` AFTER UPDATE ON `tb_horario_laboral` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_horario_laboral','UPDATE',CAST(NEW.`id_horario` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_horario', OLD.`id_horario`, 'dia_semana', OLD.`dia_semana`, 'hora_inicio', OLD.`hora_inicio`, 'hora_fin', OLD.`hora_fin`, 'activo', OLD.`activo`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_horario', NEW.`id_horario`, 'dia_semana', NEW.`dia_semana`, 'hora_inicio', NEW.`hora_inicio`, 'hora_fin', NEW.`hora_fin`, 'activo', NEW.`activo`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_notas_optometrista`
--

CREATE TABLE `tb_notas_optometrista` (
  `id_nota` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `fecha_nota` datetime NOT NULL DEFAULT current_timestamp(),
  `nota` text NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `tb_notas_optometrista`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_notas_optometrista_del` AFTER DELETE ON `tb_notas_optometrista` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_notas_optometrista','DELETE',CAST(OLD.`id_nota` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_nota', OLD.`id_nota`, 'id_cliente', OLD.`id_cliente`, 'fecha_nota', OLD.`fecha_nota`, 'nota', OLD.`nota`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_notas_optometrista_ins` AFTER INSERT ON `tb_notas_optometrista` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_notas_optometrista','INSERT',CAST(NEW.`id_nota` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_nota', NEW.`id_nota`, 'id_cliente', NEW.`id_cliente`, 'fecha_nota', NEW.`fecha_nota`, 'nota', NEW.`nota`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_notas_optometrista_upd` AFTER UPDATE ON `tb_notas_optometrista` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_notas_optometrista','UPDATE',CAST(NEW.`id_nota` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_nota', OLD.`id_nota`, 'id_cliente', OLD.`id_cliente`, 'fecha_nota', OLD.`fecha_nota`, 'nota', OLD.`nota`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_nota', NEW.`id_nota`, 'id_cliente', NEW.`id_cliente`, 'fecha_nota', NEW.`fecha_nota`, 'nota', NEW.`nota`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_permisos`
--

CREATE TABLE `tb_permisos` (
  `id_permiso` int(11) NOT NULL,
  `clave` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tb_permisos`
--

INSERT INTO `tb_permisos` (`id_permiso`, `clave`, `descripcion`, `created_at`) VALUES
(1, 'usuarios.ver', 'Ver listado de usuarios', '2026-01-26 09:45:11'),
(2, 'usuarios.crear', 'Crear usuarios', '2026-01-26 09:45:11'),
(3, 'usuarios.editar', 'Editar datos de usuarios', '2026-01-26 09:45:11'),
(4, 'usuarios.password', 'Cambiar contraseñas de usuarios', '2026-01-26 09:45:11'),
(5, 'usuarios.estado', 'Activar/Desactivar usuarios', '2026-01-26 09:45:11'),
(6, 'roles.ver', 'Ver roles', '2026-01-26 09:45:11'),
(7, 'cajas.ver', 'Ver caja', '2026-01-26 09:45:11'),
(8, 'cajas.aperturar', 'Aperturar caja', '2026-01-26 09:45:11'),
(9, 'cajas.movimiento.crear', 'Registrar movimientos de caja', '2026-01-26 09:45:11'),
(10, 'cajas.cerrar', 'Cerrar caja', '2026-01-26 09:45:11'),
(11, 'cajas.movimiento.anular', 'Anular movimientos de caja', '2026-01-26 09:45:11'),
(12, 'almacen.ver', 'Ver productos en almacén', '2026-01-26 20:18:38'),
(13, 'almacen.crear', 'Crear productos en almacén', '2026-01-26 20:18:38'),
(14, 'almacen.actualizar', 'Actualizar productos en almacén', '2026-01-26 20:18:38'),
(15, 'almacen.eliminar', 'Eliminar/Desactivar productos en almacén', '2026-01-26 20:18:38'),
(16, 'categorias.ver', 'Ver categorías', '2026-01-26 20:18:38'),
(17, 'categorias.crear', 'Crear categorías', '2026-01-26 20:18:38'),
(18, 'categorias.actualizar', 'Actualizar categorías', '2026-01-26 20:18:38'),
(19, 'categorias.eliminar', 'Eliminar categorías', '2026-01-26 20:18:38'),
(20, 'clientes.ver', 'Ver clientes', '2026-01-26 20:18:38'),
(21, 'clientes.crear', 'Crear clientes', '2026-01-26 20:18:38'),
(22, 'clientes.actualizar', 'Actualizar clientes', '2026-01-26 20:18:38'),
(23, 'clientes.eliminar', 'Eliminar/Desactivar clientes', '2026-01-26 20:18:38'),
(24, 'proveedores.ver', 'Ver proveedores', '2026-01-26 20:18:38'),
(25, 'proveedores.crear', 'Crear proveedores', '2026-01-26 20:18:38'),
(26, 'proveedores.actualizar', 'Actualizar proveedores', '2026-01-26 20:18:38'),
(27, 'proveedores.eliminar', 'Eliminar/Desactivar proveedores', '2026-01-26 20:18:38'),
(28, 'compras.ver', 'Ver compras', '2026-01-26 20:18:38'),
(29, 'compras.crear', 'Registrar compras', '2026-01-26 20:18:38'),
(30, 'compras.actualizar', 'Actualizar compras', '2026-01-26 20:18:38'),
(31, 'compras.eliminar', 'Anular compras', '2026-01-26 20:18:38'),
(32, 'ventas.ver', 'Ver ventas', '2026-01-26 20:18:38'),
(33, 'ventas.crear', 'Registrar ventas', '2026-01-26 20:18:38'),
(34, 'ventas.actualizar', 'Actualizar ventas', '2026-01-26 20:18:38'),
(35, 'ventas.eliminar', 'Anular ventas', '2026-01-26 20:18:38'),
(36, 'citas.ver', 'Ver citas', '2026-01-26 20:18:38'),
(37, 'citas.crear', 'Crear citas', '2026-01-26 20:18:38'),
(38, 'citas.actualizar', 'Actualizar citas', '2026-01-26 20:18:38'),
(39, 'citas.eliminar', 'Cancelar/Eliminar citas', '2026-01-26 20:18:38'),
(40, 'examenes.ver', 'Ver exámenes optométricos', '2026-01-26 20:18:38'),
(41, 'examenes.crear', 'Crear exámenes', '2026-01-26 20:18:38'),
(42, 'examenes.actualizar', 'Actualizar exámenes', '2026-01-26 20:18:38'),
(43, 'examenes.eliminar', 'Eliminar exámenes', '2026-01-26 20:18:38'),
(44, 'recetas.ver', 'Ver recetas ópticas', '2026-01-26 20:18:38'),
(45, 'recetas.crear', 'Crear recetas', '2026-01-26 20:18:38'),
(46, 'recetas.actualizar', 'Actualizar recetas', '2026-01-26 20:18:38'),
(47, 'recetas.eliminar', 'Eliminar recetas', '2026-01-26 20:18:38'),
(48, 'notas.ver', 'Ver notas del optometrista', '2026-01-26 20:18:38'),
(49, 'notas.crear', 'Crear notas', '2026-01-26 20:18:38'),
(50, 'notas.actualizar', 'Actualizar notas', '2026-01-26 20:18:38'),
(51, 'notas.eliminar', 'Eliminar notas', '2026-01-26 20:18:38'),
(52, 'horario.ver', 'Ver horario laboral', '2026-01-26 20:18:38'),
(53, 'horario.crear', 'Crear horario laboral', '2026-01-26 20:18:38'),
(54, 'horario.actualizar', 'Actualizar horario laboral', '2026-01-26 20:18:38'),
(55, 'horario.eliminar', 'Eliminar horario laboral', '2026-01-26 20:18:38'),
(56, 'roles.crear', 'Crear roles', '2026-01-26 20:18:38'),
(57, 'roles.actualizar', 'Actualizar roles', '2026-01-26 20:18:38'),
(58, 'roles.eliminar', 'Eliminar roles', '2026-01-26 20:18:38'),
(59, 'usuarios.actualizar', 'Actualizar usuarios', '2026-01-26 20:23:58'),
(60, 'usuarios.eliminar', 'Desactivar usuarios', '2026-01-26 20:23:58'),
(96, '*', 'Acceso total (wildcard)', '2026-01-26 22:28:56'),
(97, 'ventas.devoluciones', 'Registrar devoluciones de ventas', '2026-02-01 18:52:58'),
(98, 'ventas.pagos', 'Registrar pagos/abonos de ventas a crédito', '2026-02-01 18:52:58'),
(99, 'ventas.imprimir', 'Imprimir ticket/recibo de venta', '2026-02-01 18:52:58'),
(100, 'ventas.detalle.ver', 'Ver detalle de una venta', '2026-02-01 18:52:58'),
(101, 'cajas.reporte', 'Ver reporte/corte de caja', '2026-02-01 18:52:58'),
(102, 'cajas.imprimir', 'Imprimir corte de caja', '2026-02-01 18:52:58'),
(103, 'cajas.movimiento.ver', 'Ver movimientos de caja', '2026-02-01 18:52:58'),
(104, 'clientes.expediente', 'Ver expediente del cliente (resumen/exámenes/recetas/notas)', '2026-02-01 18:52:58'),
(105, 'compras.detalle.ver', 'Ver detalle de compra', '2026-02-01 18:52:58'),
(106, 'compras.imprimir', 'Imprimir comprobante de compra', '2026-02-01 18:52:58'),
(107, 'almacen.stock', 'Ajustar stock manualmente', '2026-02-01 18:52:58'),
(108, 'almacen.kardex', 'Ver kardex/movimientos de inventario', '2026-02-01 18:52:58'),
(109, 'permisos.ver', 'Ver listado de permisos', '2026-02-01 18:52:58'),
(110, 'permisos.asignar', 'Asignar permisos a roles', '2026-02-01 18:52:58'),
(111, 'reportes.ventas', 'Ver reportes de ventas', '2026-02-01 18:52:58'),
(112, 'reportes.caja', 'Ver reportes de caja', '2026-02-01 18:52:58'),
(113, 'reportes.inventario', 'Ver reportes de inventario', '2026-02-01 18:52:58'),
(114, 'reportes.compras', 'Ver reportes de compras', '2026-02-01 18:52:58'),
(115, 'reportes.clientes', 'Ver reportes de clientes', '2026-02-01 18:52:58'),
(116, 'auditoria.ver', 'Ver bitácora de auditoría', '2026-02-01 18:52:58'),
(117, 'reportes.ver', 'Acceso a módulo de reportes', '2026-02-03 01:50:43');

--
-- Disparadores `tb_permisos`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_permisos_del` AFTER DELETE ON `tb_permisos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_permisos','DELETE',CAST(OLD.`id_permiso` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_permiso', OLD.`id_permiso`, 'clave', OLD.`clave`, 'descripcion', OLD.`descripcion`, 'created_at', OLD.`created_at`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_permisos_ins` AFTER INSERT ON `tb_permisos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_permisos','INSERT',CAST(NEW.`id_permiso` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_permiso', NEW.`id_permiso`, 'clave', NEW.`clave`, 'descripcion', NEW.`descripcion`, 'created_at', NEW.`created_at`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_permisos_upd` AFTER UPDATE ON `tb_permisos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_permisos','UPDATE',CAST(NEW.`id_permiso` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_permiso', OLD.`id_permiso`, 'clave', OLD.`clave`, 'descripcion', OLD.`descripcion`, 'created_at', OLD.`created_at`),JSON_OBJECT('id_permiso', NEW.`id_permiso`, 'clave', NEW.`clave`, 'descripcion', NEW.`descripcion`, 'created_at', NEW.`created_at`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_proveedores`
--

CREATE TABLE `tb_proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nombre_proveedor` varchar(255) NOT NULL,
  `celular` varchar(50) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `empresa` varchar(255) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `direccion` varchar(255) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_proveedores`
--

INSERT INTO `tb_proveedores` (`id_proveedor`, `nombre_proveedor`, `celular`, `telefono`, `empresa`, `email`, `direccion`, `estado`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 'demo demo', '88888888', '', 'demo para pruebas', 'demo@demo.com', 'demo del otro demo', 1, '2026-02-02 18:27:21', '2026-02-02 18:27:54');

--
-- Disparadores `tb_proveedores`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_proveedores_del` AFTER DELETE ON `tb_proveedores` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_proveedores','DELETE',CAST(OLD.`id_proveedor` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_proveedor', OLD.`id_proveedor`, 'nombre_proveedor', OLD.`nombre_proveedor`, 'celular', OLD.`celular`, 'telefono', OLD.`telefono`, 'empresa', OLD.`empresa`, 'email', OLD.`email`, 'direccion', OLD.`direccion`, 'estado', OLD.`estado`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_proveedores_ins` AFTER INSERT ON `tb_proveedores` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_proveedores','INSERT',CAST(NEW.`id_proveedor` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_proveedor', NEW.`id_proveedor`, 'nombre_proveedor', NEW.`nombre_proveedor`, 'celular', NEW.`celular`, 'telefono', NEW.`telefono`, 'empresa', NEW.`empresa`, 'email', NEW.`email`, 'direccion', NEW.`direccion`, 'estado', NEW.`estado`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_proveedores_upd` AFTER UPDATE ON `tb_proveedores` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_proveedores','UPDATE',CAST(NEW.`id_proveedor` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_proveedor', OLD.`id_proveedor`, 'nombre_proveedor', OLD.`nombre_proveedor`, 'celular', OLD.`celular`, 'telefono', OLD.`telefono`, 'empresa', OLD.`empresa`, 'email', OLD.`email`, 'direccion', OLD.`direccion`, 'estado', OLD.`estado`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_proveedor', NEW.`id_proveedor`, 'nombre_proveedor', NEW.`nombre_proveedor`, 'celular', NEW.`celular`, 'telefono', NEW.`telefono`, 'empresa', NEW.`empresa`, 'email', NEW.`email`, 'direccion', NEW.`direccion`, 'estado', NEW.`estado`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_recetas_opticas`
--

CREATE TABLE `tb_recetas_opticas` (
  `id_receta` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_examen` int(11) DEFAULT NULL,
  `fecha_receta` date NOT NULL,
  `tipo` enum('LENTES','CONTACTO','OTRO') NOT NULL DEFAULT 'LENTES',
  `vence_en` date DEFAULT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_recetas_opticas`
--

INSERT INTO `tb_recetas_opticas` (`id_receta`, `id_cliente`, `id_examen`, `fecha_receta`, `tipo`, `vence_en`, `detalle`, `notas`, `id_usuario`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 1, 1, '2026-02-03', 'LENTES', '2027-02-03', 'Receta emitida desde examen 2026-02-02', 'Necesita lentes pogresivo', NULL, '2026-02-03 04:19:41', '2026-02-03 04:19:41');

--
-- Disparadores `tb_recetas_opticas`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_recetas_opticas_del` AFTER DELETE ON `tb_recetas_opticas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_recetas_opticas','DELETE',CAST(OLD.`id_receta` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_receta', OLD.`id_receta`, 'id_cliente', OLD.`id_cliente`, 'id_examen', OLD.`id_examen`, 'fecha_receta', OLD.`fecha_receta`, 'tipo', OLD.`tipo`, 'vence_en', OLD.`vence_en`, 'detalle', OLD.`detalle`, 'notas', OLD.`notas`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_recetas_opticas_ins` AFTER INSERT ON `tb_recetas_opticas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_recetas_opticas','INSERT',CAST(NEW.`id_receta` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_receta', NEW.`id_receta`, 'id_cliente', NEW.`id_cliente`, 'id_examen', NEW.`id_examen`, 'fecha_receta', NEW.`fecha_receta`, 'tipo', NEW.`tipo`, 'vence_en', NEW.`vence_en`, 'detalle', NEW.`detalle`, 'notas', NEW.`notas`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_recetas_opticas_upd` AFTER UPDATE ON `tb_recetas_opticas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_recetas_opticas','UPDATE',CAST(NEW.`id_receta` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_receta', OLD.`id_receta`, 'id_cliente', OLD.`id_cliente`, 'id_examen', OLD.`id_examen`, 'fecha_receta', OLD.`fecha_receta`, 'tipo', OLD.`tipo`, 'vence_en', OLD.`vence_en`, 'detalle', OLD.`detalle`, 'notas', OLD.`notas`, 'id_usuario', OLD.`id_usuario`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_receta', NEW.`id_receta`, 'id_cliente', NEW.`id_cliente`, 'id_examen', NEW.`id_examen`, 'fecha_receta', NEW.`fecha_receta`, 'tipo', NEW.`tipo`, 'vence_en', NEW.`vence_en`, 'detalle', NEW.`detalle`, 'notas', NEW.`notas`, 'id_usuario', NEW.`id_usuario`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_roles`
--

CREATE TABLE `tb_roles` (
  `id_rol` int(11) NOT NULL,
  `rol` varchar(255) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'ACTIVO',
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_roles`
--

INSERT INTO `tb_roles` (`id_rol`, `rol`, `estado`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 'ADMINISTRADOR ', 'ACTIVO', '2026-02-01 08:42:32', '2026-02-01 19:52:14'),
(2, 'CAJERO', 'ACTIVO', '2026-02-01 12:04:05', '2026-02-01 21:04:05');

--
-- Disparadores `tb_roles`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_roles_del` AFTER DELETE ON `tb_roles` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_roles','DELETE',CAST(OLD.`id_rol` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_rol', OLD.`id_rol`, 'rol', OLD.`rol`, 'estado', OLD.`estado`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_roles_ins` AFTER INSERT ON `tb_roles` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_roles','INSERT',CAST(NEW.`id_rol` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_rol', NEW.`id_rol`, 'rol', NEW.`rol`, 'estado', NEW.`estado`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_roles_upd` AFTER UPDATE ON `tb_roles` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_roles','UPDATE',CAST(NEW.`id_rol` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_rol', OLD.`id_rol`, 'rol', OLD.`rol`, 'estado', OLD.`estado`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_rol', NEW.`id_rol`, 'rol', NEW.`rol`, 'estado', NEW.`estado`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_roles_permisos`
--

CREATE TABLE `tb_roles_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `tb_roles_permisos`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_roles_permisos_del` AFTER DELETE ON `tb_roles_permisos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_roles_permisos','DELETE',CONCAT('id_rol=', CAST(OLD.`id_rol` AS CHAR), ';', 'id_permiso=', CAST(OLD.`id_permiso` AS CHAR)),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_rol', OLD.`id_rol`, 'id_permiso', OLD.`id_permiso`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_roles_permisos_ins` AFTER INSERT ON `tb_roles_permisos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_roles_permisos','INSERT',CONCAT('id_rol=', CAST(NEW.`id_rol` AS CHAR), ';', 'id_permiso=', CAST(NEW.`id_permiso` AS CHAR)),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_rol', NEW.`id_rol`, 'id_permiso', NEW.`id_permiso`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_roles_permisos_upd` AFTER UPDATE ON `tb_roles_permisos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_roles_permisos','UPDATE',CONCAT('id_rol=', CAST(NEW.`id_rol` AS CHAR), ';', 'id_permiso=', CAST(NEW.`id_permiso` AS CHAR)),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_rol', OLD.`id_rol`, 'id_permiso', OLD.`id_permiso`),JSON_OBJECT('id_rol', NEW.`id_rol`, 'id_permiso', NEW.`id_permiso`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_usuarios`
--

CREATE TABLE `tb_usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombres` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_user` mediumtext NOT NULL,
  `token` varchar(100) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tb_usuarios`
--

INSERT INTO `tb_usuarios` (`id_usuario`, `nombres`, `email`, `password_user`, `token`, `id_rol`, `fyh_creacion`, `fyh_actualizacion`, `estado`) VALUES
(2, 'Jefferson Zamora', 'admin@devzamora.com', '$2y$10$uPxvqf.7BBJcKOi9zvByfu0PY/szg0dNv4qfN.QBQZumeJhQToF2S', 'aa215c0dc8bf5c2e3375c575e12ecf72c263fcdb395c01d7b06a53d093e773a5', 1, '2026-02-01 08:43:05', '2026-02-10 15:40:56', 'ACTIVO'),
(3, 'Steven Escobar', 'steven@devzamora.com', '$2y$10$0gNnKj1yOebBkzjQNF46DunSbQXhGAfbT/t1XC9XUcl2AuOTv2gbu', '9f4e14595b4b4c57f5d124947efbf07ade42f2abbccb1b8cf5f85c2f55c65f1b', 1, '2026-02-01 19:39:25', '2026-02-02 23:04:44', 'ACTIVO'),
(4, 'Roberto Ruiz', 'roberto@devzamora.com', '$2y$10$BwE3TJZnxrh7BO2ijf7.6.h3U1XLjWd8C9cQtO90t6aeZAOQZyrqC', NULL, 1, '2026-02-01 20:20:56', '2026-02-01 20:20:56', 'ACTIVO'),
(7, 'Marcela orozco', 'mariamarcela@gmail.com', '$2y$10$n2q5pqE.JZqNNFerLzUfWurFRksLVfh6kIQROqBSR6Z5jjKzCe3c6', '2e91549898cd9c502cb98d1158b2c80613f5c1080528cd0078e58cf396cf215a', 1, '2026-02-02 19:58:24', '2026-02-04 08:26:43', 'ACTIVO'),
(8, 'DEMO', 'demo@devzamora.com', '$2y$10$.fBGm.wl9/EM3/BbFw6gve1ED9q9g0Z6AuoigAzpV/GU0pURTHNea', NULL, 1, '2026-02-03 03:10:38', '2026-02-03 03:10:38', 'ACTIVO');

--
-- Disparadores `tb_usuarios`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_usuarios_del` AFTER DELETE ON `tb_usuarios` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_usuarios','DELETE',CAST(OLD.`id_usuario` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_usuario', OLD.`id_usuario`, 'nombres', OLD.`nombres`, 'email', OLD.`email`, 'token', OLD.`token`, 'id_rol', OLD.`id_rol`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`, 'estado', OLD.`estado`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_usuarios_ins` AFTER INSERT ON `tb_usuarios` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_usuarios','INSERT',CAST(NEW.`id_usuario` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_usuario', NEW.`id_usuario`, 'nombres', NEW.`nombres`, 'email', NEW.`email`, 'token', NEW.`token`, 'id_rol', NEW.`id_rol`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`, 'estado', NEW.`estado`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_usuarios_upd` AFTER UPDATE ON `tb_usuarios` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_usuarios','UPDATE',CAST(NEW.`id_usuario` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_usuario', OLD.`id_usuario`, 'nombres', OLD.`nombres`, 'email', OLD.`email`, 'token', OLD.`token`, 'id_rol', OLD.`id_rol`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`, 'estado', OLD.`estado`),JSON_OBJECT('id_usuario', NEW.`id_usuario`, 'nombres', NEW.`nombres`, 'email', NEW.`email`, 'token', NEW.`token`, 'id_rol', NEW.`id_rol`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`, 'estado', NEW.`estado`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_ventas`
--

CREATE TABLE `tb_ventas` (
  `id_venta` int(11) NOT NULL,
  `nro_venta` int(11) NOT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_caja` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `impuesto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` enum('efectivo','deposito','credito','mixto') NOT NULL,
  `pagado_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('activa','anulada') NOT NULL DEFAULT 'activa',
  `nota` varchar(255) DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fyh_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `tb_ventas`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_ventas_del` AFTER DELETE ON `tb_ventas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_ventas','DELETE',CAST(OLD.`id_venta` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_venta', OLD.`id_venta`, 'nro_venta', OLD.`nro_venta`, 'fecha_venta', OLD.`fecha_venta`, 'id_cliente', OLD.`id_cliente`, 'id_usuario', OLD.`id_usuario`, 'id_caja', OLD.`id_caja`, 'subtotal', OLD.`subtotal`, 'descuento', OLD.`descuento`, 'impuesto', OLD.`impuesto`, 'total', OLD.`total`, 'metodo_pago', OLD.`metodo_pago`, 'pagado_inicial', OLD.`pagado_inicial`, 'saldo_pendiente', OLD.`saldo_pendiente`, 'estado', OLD.`estado`, 'nota', OLD.`nota`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_ventas_ins` AFTER INSERT ON `tb_ventas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_ventas','INSERT',CAST(NEW.`id_venta` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_venta', NEW.`id_venta`, 'nro_venta', NEW.`nro_venta`, 'fecha_venta', NEW.`fecha_venta`, 'id_cliente', NEW.`id_cliente`, 'id_usuario', NEW.`id_usuario`, 'id_caja', NEW.`id_caja`, 'subtotal', NEW.`subtotal`, 'descuento', NEW.`descuento`, 'impuesto', NEW.`impuesto`, 'total', NEW.`total`, 'metodo_pago', NEW.`metodo_pago`, 'pagado_inicial', NEW.`pagado_inicial`, 'saldo_pendiente', NEW.`saldo_pendiente`, 'estado', NEW.`estado`, 'nota', NEW.`nota`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_ventas_upd` AFTER UPDATE ON `tb_ventas` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_ventas','UPDATE',CAST(NEW.`id_venta` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_venta', OLD.`id_venta`, 'nro_venta', OLD.`nro_venta`, 'fecha_venta', OLD.`fecha_venta`, 'id_cliente', OLD.`id_cliente`, 'id_usuario', OLD.`id_usuario`, 'id_caja', OLD.`id_caja`, 'subtotal', OLD.`subtotal`, 'descuento', OLD.`descuento`, 'impuesto', OLD.`impuesto`, 'total', OLD.`total`, 'metodo_pago', OLD.`metodo_pago`, 'pagado_inicial', OLD.`pagado_inicial`, 'saldo_pendiente', OLD.`saldo_pendiente`, 'estado', OLD.`estado`, 'nota', OLD.`nota`, 'fyh_creacion', OLD.`fyh_creacion`, 'fyh_actualizacion', OLD.`fyh_actualizacion`),JSON_OBJECT('id_venta', NEW.`id_venta`, 'nro_venta', NEW.`nro_venta`, 'fecha_venta', NEW.`fecha_venta`, 'id_cliente', NEW.`id_cliente`, 'id_usuario', NEW.`id_usuario`, 'id_caja', NEW.`id_caja`, 'subtotal', NEW.`subtotal`, 'descuento', NEW.`descuento`, 'impuesto', NEW.`impuesto`, 'total', NEW.`total`, 'metodo_pago', NEW.`metodo_pago`, 'pagado_inicial', NEW.`pagado_inicial`, 'saldo_pendiente', NEW.`saldo_pendiente`, 'estado', NEW.`estado`, 'nota', NEW.`nota`, 'fyh_creacion', NEW.`fyh_creacion`, 'fyh_actualizacion', NEW.`fyh_actualizacion`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_ventas_detalle`
--

CREATE TABLE `tb_ventas_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento_linea` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_linea` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `tb_ventas_detalle`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_ventas_detalle_del` AFTER DELETE ON `tb_ventas_detalle` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_ventas_detalle','DELETE',CAST(OLD.`id_detalle` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_detalle', OLD.`id_detalle`, 'id_venta', OLD.`id_venta`, 'id_producto', OLD.`id_producto`, 'cantidad', OLD.`cantidad`, 'precio_unitario', OLD.`precio_unitario`, 'descuento_linea', OLD.`descuento_linea`, 'total_linea', OLD.`total_linea`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_ventas_detalle_ins` AFTER INSERT ON `tb_ventas_detalle` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_ventas_detalle','INSERT',CAST(NEW.`id_detalle` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_detalle', NEW.`id_detalle`, 'id_venta', NEW.`id_venta`, 'id_producto', NEW.`id_producto`, 'cantidad', NEW.`cantidad`, 'precio_unitario', NEW.`precio_unitario`, 'descuento_linea', NEW.`descuento_linea`, 'total_linea', NEW.`total_linea`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_ventas_detalle_upd` AFTER UPDATE ON `tb_ventas_detalle` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_ventas_detalle','UPDATE',CAST(NEW.`id_detalle` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_detalle', OLD.`id_detalle`, 'id_venta', OLD.`id_venta`, 'id_producto', OLD.`id_producto`, 'cantidad', OLD.`cantidad`, 'precio_unitario', OLD.`precio_unitario`, 'descuento_linea', OLD.`descuento_linea`, 'total_linea', OLD.`total_linea`),JSON_OBJECT('id_detalle', NEW.`id_detalle`, 'id_venta', NEW.`id_venta`, 'id_producto', NEW.`id_producto`, 'cantidad', NEW.`cantidad`, 'precio_unitario', NEW.`precio_unitario`, 'descuento_linea', NEW.`descuento_linea`, 'total_linea', NEW.`total_linea`));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_ventas_pagos`
--

CREATE TABLE `tb_ventas_pagos` (
  `id_pago` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_caja` int(11) NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp(),
  `metodo_pago` enum('efectivo','deposito') NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `referencia` varchar(100) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `tb_ventas_pagos`
--
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_ventas_pagos_del` AFTER DELETE ON `tb_ventas_pagos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_ventas_pagos','DELETE',CAST(OLD.`id_pago` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_pago', OLD.`id_pago`, 'id_venta', OLD.`id_venta`, 'id_caja', OLD.`id_caja`, 'fecha_pago', OLD.`fecha_pago`, 'metodo_pago', OLD.`metodo_pago`, 'monto', OLD.`monto`, 'referencia', OLD.`referencia`, 'id_usuario', OLD.`id_usuario`),NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_ventas_pagos_ins` AFTER INSERT ON `tb_ventas_pagos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_ventas_pagos','INSERT',CAST(NEW.`id_pago` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,NULL,JSON_OBJECT('id_pago', NEW.`id_pago`, 'id_venta', NEW.`id_venta`, 'id_caja', NEW.`id_caja`, 'fecha_pago', NEW.`fecha_pago`, 'metodo_pago', NEW.`metodo_pago`, 'monto', NEW.`monto`, 'referencia', NEW.`referencia`, 'id_usuario', NEW.`id_usuario`));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_tb_ventas_pagos_upd` AFTER UPDATE ON `tb_ventas_pagos` FOR EACH ROW BEGIN
  INSERT INTO `tb_auditoria` (`tabla`,`accion`,`pk`,`usuario_id`,`usuario_email`,`ip`,`user_agent`,`antes`,`despues`)
  VALUES ('tb_ventas_pagos','UPDATE',CAST(NEW.`id_pago` AS CHAR),@app_user_id,@app_user_email,@app_ip,@app_ua,JSON_OBJECT('id_pago', OLD.`id_pago`, 'id_venta', OLD.`id_venta`, 'id_caja', OLD.`id_caja`, 'fecha_pago', OLD.`fecha_pago`, 'metodo_pago', OLD.`metodo_pago`, 'monto', OLD.`monto`, 'referencia', OLD.`referencia`, 'id_usuario', OLD.`id_usuario`),JSON_OBJECT('id_pago', NEW.`id_pago`, 'id_venta', NEW.`id_venta`, 'id_caja', NEW.`id_caja`, 'fecha_pago', NEW.`fecha_pago`, 'metodo_pago', NEW.`metodo_pago`, 'monto', NEW.`monto`, 'referencia', NEW.`referencia`, 'id_usuario', NEW.`id_usuario`));
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tb_almacen`
--
ALTER TABLE `tb_almacen`
  ADD PRIMARY KEY (`id_producto`),
  ADD UNIQUE KEY `uq_tb_almacen_codigo` (`codigo`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `ix_tb_almacen_estado` (`estado`);

--
-- Indices de la tabla `tb_auditoria`
--
ALTER TABLE `tb_auditoria`
  ADD PRIMARY KEY (`id_auditoria`),
  ADD KEY `idx_tabla_fecha` (`tabla`,`fecha`),
  ADD KEY `idx_usuario_fecha` (`usuario_id`,`fecha`);

--
-- Indices de la tabla `tb_cajas`
--
ALTER TABLE `tb_cajas`
  ADD PRIMARY KEY (`id_caja`),
  ADD KEY `ix_cajas_estado` (`estado`),
  ADD KEY `ix_cajas_fecha_apertura` (`fecha_apertura`),
  ADD KEY `fk_cajas_usuario_apertura` (`usuario_apertura_id`),
  ADD KEY `fk_cajas_usuario_cierre` (`usuario_cierre_id`),
  ADD KEY `ix_caja_user_open` (`usuario_apertura_id`),
  ADD KEY `ix_caja_user_close` (`usuario_cierre_id`);

--
-- Indices de la tabla `tb_caja_movimientos`
--
ALTER TABLE `tb_caja_movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `ix_mov_caja` (`id_caja`),
  ADD KEY `ix_mov_fecha` (`fecha`),
  ADD KEY `fk_mov_usuario` (`id_usuario`),
  ADD KEY `ix_mov_caja_fecha` (`id_caja`,`fecha`),
  ADD KEY `ix_mov_caja_tipo` (`id_caja`,`tipo`),
  ADD KEY `ix_mov_estado` (`estado`),
  ADD KEY `ix_mov_caja_estado_fecha` (`id_caja`,`estado`,`fecha`),
  ADD KEY `ix_mov_ajuste` (`id_movimiento_ajuste`),
  ADD KEY `ix_anulado_por` (`anulado_por`),
  ADD KEY `idx_caja_mov_estado` (`id_caja`,`estado`),
  ADD KEY `idx_caja_mov_anulado_por` (`anulado_por`);

--
-- Indices de la tabla `tb_categorias`
--
ALTER TABLE `tb_categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `uq_tb_categorias_nombre` (`nombre_categoria`);

--
-- Indices de la tabla `tb_citas`
--
ALTER TABLE `tb_citas`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `idx_citas_fecha` (`fecha`),
  ADD KEY `idx_citas_cliente` (`id_cliente`),
  ADD KEY `fk_citas_usuario` (`id_usuario`);

--
-- Indices de la tabla `tb_citas_bloqueos`
--
ALTER TABLE `tb_citas_bloqueos`
  ADD PRIMARY KEY (`id_bloqueo`),
  ADD KEY `idx_bloq_fecha` (`fecha`);

--
-- Indices de la tabla `tb_clientes`
--
ALTER TABLE `tb_clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `ux_clientes_numero_documento` (`numero_documento`),
  ADD KEY `ix_clientes_apellido` (`apellido`),
  ADD KEY `ix_clientes_nombre` (`nombre`);

--
-- Indices de la tabla `tb_compras`
--
ALTER TABLE `tb_compras`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `idx_compras_estado` (`estado`);

--
-- Indices de la tabla `tb_devoluciones`
--
ALTER TABLE `tb_devoluciones`
  ADD PRIMARY KEY (`id_devolucion`),
  ADD KEY `ix_dev_venta` (`id_venta`),
  ADD KEY `ix_dev_caja` (`id_caja`),
  ADD KEY `ix_dev_fecha` (`fecha`);

--
-- Indices de la tabla `tb_devoluciones_detalle`
--
ALTER TABLE `tb_devoluciones_detalle`
  ADD PRIMARY KEY (`id_detalle_dev`),
  ADD KEY `ix_devdet_dev` (`id_devolucion`),
  ADD KEY `ix_devdet_prod` (`id_producto`);

--
-- Indices de la tabla `tb_examenes_optometricos`
--
ALTER TABLE `tb_examenes_optometricos`
  ADD PRIMARY KEY (`id_examen`),
  ADD KEY `idx_examen_cliente` (`id_cliente`),
  ADD KEY `idx_examen_fecha` (`fecha_examen`),
  ADD KEY `fk_examen_usuario` (`id_usuario`);

--
-- Indices de la tabla `tb_horario_laboral`
--
ALTER TABLE `tb_horario_laboral`
  ADD PRIMARY KEY (`id_horario`),
  ADD UNIQUE KEY `uq_horario_dia` (`dia_semana`);

--
-- Indices de la tabla `tb_notas_optometrista`
--
ALTER TABLE `tb_notas_optometrista`
  ADD PRIMARY KEY (`id_nota`),
  ADD KEY `idx_nota_cliente` (`id_cliente`),
  ADD KEY `fk_nota_usuario` (`id_usuario`);

--
-- Indices de la tabla `tb_permisos`
--
ALTER TABLE `tb_permisos`
  ADD PRIMARY KEY (`id_permiso`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `tb_proveedores`
--
ALTER TABLE `tb_proveedores`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD KEY `idx_proveedores_estado` (`estado`);

--
-- Indices de la tabla `tb_recetas_opticas`
--
ALTER TABLE `tb_recetas_opticas`
  ADD PRIMARY KEY (`id_receta`),
  ADD KEY `idx_receta_cliente` (`id_cliente`),
  ADD KEY `idx_receta_fecha` (`fecha_receta`),
  ADD KEY `fk_receta_examen` (`id_examen`),
  ADD KEY `fk_receta_usuario` (`id_usuario`);

--
-- Indices de la tabla `tb_roles`
--
ALTER TABLE `tb_roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `uq_tb_roles_rol` (`rol`),
  ADD UNIQUE KEY `ux_roles_rol` (`rol`);

--
-- Indices de la tabla `tb_roles_permisos`
--
ALTER TABLE `tb_roles_permisos`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `fk_rp_perm` (`id_permiso`);

--
-- Indices de la tabla `tb_usuarios`
--
ALTER TABLE `tb_usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uq_tb_usuarios_email` (`email`),
  ADD UNIQUE KEY `ux_usuarios_email` (`email`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `tb_ventas`
--
ALTER TABLE `tb_ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD UNIQUE KEY `ux_ventas_nro` (`nro_venta`),
  ADD KEY `ix_ventas_fecha` (`fecha_venta`),
  ADD KEY `ix_ventas_cliente` (`id_cliente`),
  ADD KEY `ix_ventas_caja` (`id_caja`),
  ADD KEY `fk_ventas_usuario` (`id_usuario`);

--
-- Indices de la tabla `tb_ventas_detalle`
--
ALTER TABLE `tb_ventas_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `ix_detalle_venta` (`id_venta`),
  ADD KEY `ix_detalle_producto` (`id_producto`);

--
-- Indices de la tabla `tb_ventas_pagos`
--
ALTER TABLE `tb_ventas_pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `ix_pagos_venta` (`id_venta`),
  ADD KEY `ix_pagos_caja` (`id_caja`),
  ADD KEY `ix_pagos_fecha` (`fecha_pago`),
  ADD KEY `fk_pagos_usuario` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tb_almacen`
--
ALTER TABLE `tb_almacen`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `tb_auditoria`
--
ALTER TABLE `tb_auditoria`
  MODIFY `id_auditoria` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=337;

--
-- AUTO_INCREMENT de la tabla `tb_cajas`
--
ALTER TABLE `tb_cajas`
  MODIFY `id_caja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tb_caja_movimientos`
--
ALTER TABLE `tb_caja_movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tb_categorias`
--
ALTER TABLE `tb_categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `tb_citas`
--
ALTER TABLE `tb_citas`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tb_citas_bloqueos`
--
ALTER TABLE `tb_citas_bloqueos`
  MODIFY `id_bloqueo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tb_clientes`
--
ALTER TABLE `tb_clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tb_compras`
--
ALTER TABLE `tb_compras`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tb_devoluciones`
--
ALTER TABLE `tb_devoluciones`
  MODIFY `id_devolucion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tb_devoluciones_detalle`
--
ALTER TABLE `tb_devoluciones_detalle`
  MODIFY `id_detalle_dev` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tb_examenes_optometricos`
--
ALTER TABLE `tb_examenes_optometricos`
  MODIFY `id_examen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tb_horario_laboral`
--
ALTER TABLE `tb_horario_laboral`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tb_notas_optometrista`
--
ALTER TABLE `tb_notas_optometrista`
  MODIFY `id_nota` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tb_permisos`
--
ALTER TABLE `tb_permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT de la tabla `tb_proveedores`
--
ALTER TABLE `tb_proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tb_recetas_opticas`
--
ALTER TABLE `tb_recetas_opticas`
  MODIFY `id_receta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tb_roles`
--
ALTER TABLE `tb_roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tb_usuarios`
--
ALTER TABLE `tb_usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tb_ventas`
--
ALTER TABLE `tb_ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tb_ventas_detalle`
--
ALTER TABLE `tb_ventas_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `tb_ventas_pagos`
--
ALTER TABLE `tb_ventas_pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tb_almacen`
--
ALTER TABLE `tb_almacen`
  ADD CONSTRAINT `tb_almacen_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `tb_categorias` (`id_categoria`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_almacen_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Filtros para la tabla `tb_cajas`
--
ALTER TABLE `tb_cajas`
  ADD CONSTRAINT `fk_caja_usuario_apertura` FOREIGN KEY (`usuario_apertura_id`) REFERENCES `tb_usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caja_usuario_cierre` FOREIGN KEY (`usuario_cierre_id`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cajas_usuario_apertura` FOREIGN KEY (`usuario_apertura_id`) REFERENCES `tb_usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_cajas_usuario_cierre` FOREIGN KEY (`usuario_cierre_id`) REFERENCES `tb_usuarios` (`id_usuario`);

--
-- Filtros para la tabla `tb_caja_movimientos`
--
ALTER TABLE `tb_caja_movimientos`
  ADD CONSTRAINT `fk_caja_mov_anulado_por` FOREIGN KEY (`anulado_por`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mov_anulado_por` FOREIGN KEY (`anulado_por`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mov_caja` FOREIGN KEY (`id_caja`) REFERENCES `tb_cajas` (`id_caja`),
  ADD CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`);

--
-- Filtros para la tabla `tb_citas`
--
ALTER TABLE `tb_citas`
  ADD CONSTRAINT `fk_citas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `tb_clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_citas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tb_compras`
--
ALTER TABLE `tb_compras`
  ADD CONSTRAINT `tb_compras_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_compras_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_compras_ibfk_4` FOREIGN KEY (`id_proveedor`) REFERENCES `tb_proveedores` (`id_proveedor`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `tb_devoluciones`
--
ALTER TABLE `tb_devoluciones`
  ADD CONSTRAINT `fk_dev_caja` FOREIGN KEY (`id_caja`) REFERENCES `tb_cajas` (`id_caja`),
  ADD CONSTRAINT `fk_dev_venta` FOREIGN KEY (`id_venta`) REFERENCES `tb_ventas` (`id_venta`);

--
-- Filtros para la tabla `tb_devoluciones_detalle`
--
ALTER TABLE `tb_devoluciones_detalle`
  ADD CONSTRAINT `fk_devdet_dev` FOREIGN KEY (`id_devolucion`) REFERENCES `tb_devoluciones` (`id_devolucion`),
  ADD CONSTRAINT `fk_devdet_prod` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`);

--
-- Filtros para la tabla `tb_examenes_optometricos`
--
ALTER TABLE `tb_examenes_optometricos`
  ADD CONSTRAINT `fk_examen_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `tb_clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_examen_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tb_notas_optometrista`
--
ALTER TABLE `tb_notas_optometrista`
  ADD CONSTRAINT `fk_nota_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `tb_clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_nota_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tb_recetas_opticas`
--
ALTER TABLE `tb_recetas_opticas`
  ADD CONSTRAINT `fk_receta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `tb_clientes` (`id_cliente`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_receta_examen` FOREIGN KEY (`id_examen`) REFERENCES `tb_examenes_optometricos` (`id_examen`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_receta_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tb_roles_permisos`
--
ALTER TABLE `tb_roles_permisos`
  ADD CONSTRAINT `fk_rp_perm` FOREIGN KEY (`id_permiso`) REFERENCES `tb_permisos` (`id_permiso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rp_rol` FOREIGN KEY (`id_rol`) REFERENCES `tb_roles` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tb_usuarios`
--
ALTER TABLE `tb_usuarios`
  ADD CONSTRAINT `tb_usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `tb_roles` (`id_rol`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tb_ventas`
--
ALTER TABLE `tb_ventas`
  ADD CONSTRAINT `fk_ventas_caja` FOREIGN KEY (`id_caja`) REFERENCES `tb_cajas` (`id_caja`),
  ADD CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `tb_clientes` (`id_cliente`),
  ADD CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`);

--
-- Filtros para la tabla `tb_ventas_detalle`
--
ALTER TABLE `tb_ventas_detalle`
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`),
  ADD CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`id_venta`) REFERENCES `tb_ventas` (`id_venta`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tb_ventas_pagos`
--
ALTER TABLE `tb_ventas_pagos`
  ADD CONSTRAINT `fk_pagos_caja` FOREIGN KEY (`id_caja`) REFERENCES `tb_cajas` (`id_caja`),
  ADD CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`),
  ADD CONSTRAINT `fk_pagos_venta` FOREIGN KEY (`id_venta`) REFERENCES `tb_ventas` (`id_venta`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

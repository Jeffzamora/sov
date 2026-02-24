-- SOV - Módulo de Configuración (Settings + Sucursales)
-- Ejecutar UNA VEZ en tu BD (MariaDB/MySQL).

CREATE TABLE IF NOT EXISTS tb_settings (
  `key`        VARCHAR(80)  NOT NULL,
  `value`      TEXT         NOT NULL,
  `type`       ENUM('string','int','bool','json') NOT NULL DEFAULT 'string',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` INT          NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tb_sucursales (
  id_sucursal INT NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(120) NOT NULL,
  direccion   VARCHAR(255) NULL,
  telefono    VARCHAR(50)  NULL,
  is_default  TINYINT(1)   NOT NULL DEFAULT 0,
  activo      TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_sucursal),
  KEY idx_sucursales_default (is_default),
  KEY idx_sucursales_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permisos RBAC (opcional, si ya usas tb_permisos/tb_roles_permisos)
-- Nota: ajusta columnas según tu esquema real.
-- INSERT IGNORE INTO tb_permisos (clave, nombre) VALUES
-- ('configuracion.ver', 'Ver configuración'),
-- ('configuracion.editar', 'Editar configuración');

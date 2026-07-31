-- =============================================================================
-- ECOE – Módulo Tarifa Social
-- Script de migración inicial (InnoDB / utf8mb4)
-- Ejecutar desde phpMyAdmin o cliente SQL sobre la base portal_inde_db
-- =============================================================================

USE portal_inde_db;

-- -----------------------------------------------------------------------------
-- 1. Distribuidoras
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ecoe_distribuidoras` (
    `id`     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(120)     NOT NULL,
    `status` TINYINT(1)       NOT NULL DEFAULT 1 COMMENT '1=activa 0=inactiva',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_distribuidora_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registros semilla
INSERT IGNORE INTO `ecoe_distribuidoras` (`nombre`, `status`) VALUES
    ('EEGSA',    1),
    ('ENERGUATE', 1),
    ('DEORSA',   1),
    ('DEOCSA',   1);

-- -----------------------------------------------------------------------------
-- 2. Base de NIS (correlativo de usuarios por distribuidora)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ecoe_nis_base` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `id_usuario`       VARCHAR(30)   NOT NULL COMMENT 'Correlativo / NIS del usuario',
    `activ_economica`  VARCHAR(80)   NOT NULL DEFAULT '' COMMENT 'Clasificación actividad económica',
    `consumo_kwh`      DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT 'Consumo promedio mensual en KWh',
    `distribuidora_id` INT UNSIGNED  NOT NULL,
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_nis_usuario_dist` (`id_usuario`, `distribuidora_id`),
    KEY `idx_nis_distribuidora` (`distribuidora_id`),
    CONSTRAINT `fk_nis_distribuidora`
        FOREIGN KEY (`distribuidora_id`) REFERENCES `ecoe_distribuidoras` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Catálogo de estados del flujo de Tarifa Social
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ecoe_ts_estados` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `nombre`      VARCHAR(60)   NOT NULL,
    `orden_paso`  TINYINT       NOT NULL DEFAULT 0,
    `descripcion` VARCHAR(255)  NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ts_estado_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estados iniciales del flujo
INSERT IGNORE INTO `ecoe_ts_estados` (`nombre`, `orden_paso`, `descripcion`) VALUES
    ('Ingresado',    1, 'Solicitud recibida y pendiente de revisión'),
    ('En Revisión',  2, 'Documentación en proceso de verificación'),
    ('Aprobado',     3, 'Solicitud aprobada para Tarifa Social'),
    ('Rechazado',    4, 'Solicitud rechazada por incumplimiento de requisitos'),
    ('Completado',   5, 'Proceso concluido satisfactoriamente');

-- -----------------------------------------------------------------------------
-- 4. Tickets de solicitud de Tarifa Social
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ecoe_ts_tickets` (
    `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `id_solicitud`      INT UNSIGNED  NOT NULL COMMENT 'ID correlativo interno de la solicitud',
    `codigo_referencia` VARCHAR(40)   NOT NULL COMMENT 'Código único de seguimiento',
    `nombre`            VARCHAR(160)  NOT NULL,
    `direccion`         VARCHAR(255)  NOT NULL,
    `dpi`               VARCHAR(20)   NOT NULL,
    `telefono`          VARCHAR(20)   NOT NULL DEFAULT '',
    `estado_id`         INT UNSIGNED  NOT NULL,
    `fecha_ingreso`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ts_ticket_referencia` (`codigo_referencia`),
    UNIQUE KEY `uq_ts_ticket_solicitud`  (`id_solicitud`),
    KEY `idx_ts_ticket_estado`       (`estado_id`),
    KEY `idx_ts_ticket_dpi`          (`dpi`),
    KEY `idx_ts_ticket_fecha`        (`fecha_ingreso`),
    CONSTRAINT `fk_ts_ticket_estado`
        FOREIGN KEY (`estado_id`) REFERENCES `ecoe_ts_estados` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Adjuntos de tickets
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ecoe_ts_adjuntos` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_id`    INT UNSIGNED NOT NULL,
    `tipo_archivo` ENUM('dpi_frontal','dpi_reverso','factura','fachada1','fachada2') NOT NULL,
    `ruta_archivo` VARCHAR(255) NOT NULL COMMENT 'Ruta relativa desde WRITEPATH',
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ts_adjunto_ticket` (`ticket_id`),
    CONSTRAINT `fk_ts_adjunto_ticket`
        FOREIGN KEY (`ticket_id`) REFERENCES `ecoe_ts_tickets` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

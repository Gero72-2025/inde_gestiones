SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS etcee_estados_mantenimiento (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    clave CHAR(2) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#1A56DB',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_etcee_estado_mantenimiento_nombre (nombre),
    UNIQUE KEY uq_etcee_estado_mantenimiento_clave (clave),
    KEY idx_etcee_estado_mantenimiento_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO etcee_estados_mantenimiento (nombre, clave, color) VALUES
    ('Mantenimiento Programado', 'P', '#1A56DB'),
    ('Mantenimiento No Programado', 'NP', '#0F6D8F'),
    ('Interrupcion Fortuita', 'IF', '#D45A0B'),
    ('Cancelado', 'C', '#B42318'),
    ('Finalizado Programado', 'FP', '#198754'),
    ('Mantenimiento Activo', 'A', '#2D7D6F');

CREATE TABLE IF NOT EXISTS etcee_cortes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    estado ENUM('programado', 'activo', 'finalizado', 'cancelado') NOT NULL DEFAULT 'programado',
    estado_mantenimiento_id BIGINT UNSIGNED NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#1f6feb',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_etcee_cortes_fechas (fecha_inicio, fecha_fin),
    KEY idx_etcee_cortes_estado (estado),
    KEY idx_etcee_cortes_estado_mantenimiento (estado_mantenimiento_id),
    CONSTRAINT fk_etcee_cortes_estado_mantenimiento FOREIGN KEY (estado_mantenimiento_id) REFERENCES etcee_estados_mantenimiento (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS etcee_cortes_ubicaciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    corte_id BIGINT UNSIGNED NOT NULL,
    departamento_id BIGINT UNSIGNED NOT NULL,
    municipio_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_etcee_corte_geo (corte_id, departamento_id, municipio_id),
    KEY idx_etcee_cortes_ubicaciones_corte (corte_id),
    KEY idx_etcee_cortes_ubicaciones_departamento (departamento_id),
    KEY idx_etcee_cortes_ubicaciones_municipio (municipio_id),
    CONSTRAINT fk_etcee_cortes_ubicaciones_corte FOREIGN KEY (corte_id) REFERENCES etcee_cortes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_etcee_cortes_ubicaciones_departamento FOREIGN KEY (departamento_id) REFERENCES cat_departamentos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_etcee_cortes_ubicaciones_municipio FOREIGN KEY (municipio_id) REFERENCES cat_municipios (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

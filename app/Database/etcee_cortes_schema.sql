SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS etcee_cortes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(180) NOT NULL,
    descripcion TEXT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    estado ENUM('programado', 'activo', 'finalizado', 'cancelado') NOT NULL DEFAULT 'programado',
    color VARCHAR(20) NOT NULL DEFAULT '#1f6feb',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_etcee_cortes_fechas (fecha_inicio, fecha_fin),
    KEY idx_etcee_cortes_estado (estado)
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

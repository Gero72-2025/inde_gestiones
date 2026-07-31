SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS portal_inde_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE portal_inde_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS user_permissions;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS upload_logs;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS gerencias;

CREATE TABLE gerencias (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    slug VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_gerencias_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    descripcion TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_permissions_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    gerencia_id BIGINT UNSIGNED NULL,
    username VARCHAR(80) NOT NULL,
    email VARCHAR(160) NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    status ENUM('active', 'inactive', 'locked', 'pending') NOT NULL DEFAULT 'pending',
    google_2fa_secret VARCHAR(64) NULL,
    twofa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at DATETIME NULL,
    password_changed_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_username (username),
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_gerencia_id (gerencia_id),
    CONSTRAINT fk_users_gerencia FOREIGN KEY (gerencia_id) REFERENCES gerencias (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_permissions (
    user_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    is_granted TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_id),
    CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE upload_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    gerencia_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    mime_type VARCHAR(120) NULL,
    tamano_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    registros_procesados INT UNSIGNED NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_upload_logs_gerencia (gerencia_id),
    KEY idx_upload_logs_usuario (usuario_id),
    CONSTRAINT fk_upload_logs_gerencia FOREIGN KEY (gerencia_id) REFERENCES gerencias (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_upload_logs_usuario FOREIGN KEY (usuario_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO gerencias (nombre, descripcion, slug, status)
VALUES ('Gerencia Gero', 'Modulo base de ejemplo para nuevas gerencias.', 'gero', 'active');

INSERT INTO roles (nombre, descripcion)
VALUES
    ('Super Administrador', 'Acceso transversal a todas las gerencias y configuraciones.'),
    ('Administrador de Gerencia', 'Administra contenido y usuarios de una gerencia concreta.'),
    ('Editor', 'Puede editar contenido dentro de su gerencia.'),
    ('Lector', 'Acceso de lectura a la informacion publicada.');

INSERT INTO permissions (nombre, slug, descripcion)
VALUES
    ('Acceso transversal a gerencias', 'gerencias.all.access', 'Permite ingresar a cualquier modulo de gerencia.'),
    ('Acceso total del sistema', 'superadmin.access', 'Permite ignorar restricciones de modulo.'),
    ('Acceso al modulo Gero', 'gerencia.gero.access', 'Permite acceder al modulo de la Gerencia Gero.'),
    ('Gestionar 2FA', 'security.2fa.manage', 'Permite administrar la configuracion de doble factor.');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.slug IN ('gerencias.all.access', 'superadmin.access', 'gerencia.gero.access', 'security.2fa.manage')
WHERE r.nombre = 'Super Administrador';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.slug IN ('gerencia.gero.access', 'security.2fa.manage')
WHERE r.nombre = 'Administrador de Gerencia';

INSERT INTO users (
    gerencia_id,
    username,
    email,
    password,
    first_name,
    last_name,
    status,
    google_2fa_secret,
    twofa_enabled,
    last_login_at,
    password_changed_at
)
SELECT
    g.id,
    'superadmin',
    'admin@portal-inde.local',
    '$2y$10$Emi6M6exgE5v/F4jxzZY5OwWYp6bUE0V8mezCCL7yh.mBxJHGFZi.',
    'Super',
    'Administrador',
    'active',
    NULL,
    0,
    NULL,
    NULL
FROM gerencias g
WHERE g.slug = 'gero';

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.nombre = 'Super Administrador'
WHERE u.username = 'superadmin';

SET FOREIGN_KEY_CHECKS = 1;
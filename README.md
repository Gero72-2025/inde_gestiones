# Portal INDE

Portal web institucional del **Instituto Nacional de Electrificación (INDE)** de Guatemala, construido sobre **CodeIgniter 4**. Ofrece consulta pública multilingüe de beneficiarios y servicios eléctricos, junto con un panel administrativo seguro con control de acceso basado en roles (RBAC) y autenticación en dos factores (2FA/TOTP).

---

## Tabla de Contenidos

- [Requisitos del servidor](#requisitos-del-servidor)
- [Instalación y configuración](#instalación-y-configuración)
- [Arquitectura general](#arquitectura-general)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Módulos](#módulos)
- [Portal Público](#portal-público)
- [Panel de Administración](#panel-de-administración)
- [Autenticación y Seguridad](#autenticación-y-seguridad)
- [RBAC – Control de Acceso por Roles](#rbac--control-de-acceso-por-roles)
- [Filtros HTTP](#filtros-http)
- [Base de Datos y Migraciones](#base-de-datos-y-migraciones)
- [Internacionalización (i18n)](#internacionalización-i18n)
- [Convenciones de desarrollo](#convenciones-de-desarrollo)

---

## Requisitos del servidor

- **PHP 8.2** o superior
- Extensiones PHP: `intl`, `mbstring`, `json`, `mysqlnd`, `libcurl`
- **MySQL 5.7+ / MariaDB 10.4+**
- Servidor web Apache (XAMPP recomendado en desarrollo) o Nginx
- Composer

---

## Instalación y configuración

```bash
# 1. Clonar el repositorio y entrar al directorio
git clone <repo-url> portal-inde
cd portal-inde

# 2. Instalar dependencias PHP
composer install

# 3. Copiar el archivo de entorno y configurar
cp env .env
# Editar .env: credenciales de base de datos, baseURL, CI_ENVIRONMENT, etc.

# 4. Ejecutar migraciones
php spark migrate

# 5. (Opcional) Ejecutar seeders iniciales
php spark db:seed <NombreDelSeeder>
```

El servidor web debe apuntar a la carpeta `public/`. En XAMPP, configurar un Virtual Host o colocar el proyecto en `htdocs/` y acceder por `http://localhost/portal-inde/public/`.

> **Nota XAMPP:** Si PHP no está en el PATH del sistema, usar la ruta absoluta: `C:\xampp\php\php.exe spark migrate`.

---

## Arquitectura general

El proyecto sigue una **arquitectura MVC modular** sobre CodeIgniter 4. Cada dominio funcional vive en su propio módulo bajo `app/Modules/`, con sus propios controladores, modelos, servicios, vistas y rutas. Los módulos se auto-registran gracias a un glob en `app/Config/Routes.php`.

```
Petición HTTP
    │
    ▼
Filtros (Filters)           ← adminAuth, adminPermission, gerenciaAccess,
    │                          activityLog, readOnly, csrf, …
    ▼
Router (Routes.php)         ← Rutas públicas + rutas por módulo
    │
    ▼
Controlador                 ← Extiende AdminBaseController o BaseController
    │
    ├─ RbacService          ← Resuelve perfiles de acceso, roles y permisos
    ├─ Modelos / DB         ← CodeIgniter Query Builder / ORM
    └─ Vistas               ← layout + innerView compuesto por adminView()
```

---

## Estructura del proyecto

```
portal-inde/
├── public/                      # Document root del servidor web
│   └── index.php
├── app/
│   ├── Config/                  # Configuración global (Filters, Routes, Database…)
│   ├── Controllers/             # Controladores globales
│   │   ├── BaseController.php
│   │   └── PublicPortalController.php
│   ├── Filters/                 # Filtros HTTP personalizados
│   │   ├── ActivityLogFilter.php
│   │   ├── AdminAuthFilter.php
│   │   ├── AdminPermissionFilter.php
│   │   ├── GerenciaPermissionFilter.php
│   │   └── PublicReadOnlyFilter.php
│   ├── Language/                # Archivos de traducción (es, en, quc, qeq, cak)
│   ├── Models/                  # Modelos globales (si aplica)
│   ├── Services/
│   │   └── PublicPortalService.php
│   ├── Views/
│   │   └── public/              # Vistas del portal público (layout + páginas)
│   ├── Database/
│   │   ├── Migrations/          # Migraciones CI4
│   │   └── Seeds/
│   └── Modules/
│       ├── Admin/               # Panel de administración
│       ├── Auth/                # Autenticación + 2FA
│       ├── Comunicaciones/      # Módulo de comunicaciones internas
│       ├── Etcee/               # Módulo ETCEE
│       └── Gero/                # Módulo Gerencia (ejemplo/plantilla)
├── system/                      # Core de CodeIgniter 4 (no editar)
├── vendor/                      # Dependencias Composer
└── writable/                    # Logs, caché, sesiones, uploads
```

---

## Módulos

Cada módulo sigue la misma estructura interna:

```
Modules/<Nombre>/
├── Config/
│   └── Routes.php      # Rutas propias del módulo
├── Controllers/
├── Models/
├── Services/
└── Views/
```

Las rutas de todos los módulos se cargan automáticamente desde `app/Config/Routes.php`:

```php
foreach (glob(APPPATH . 'Modules/*/Config/Routes.php') ?: [] as $moduleRoutes) {
    require $moduleRoutes;
}
```

### Módulos disponibles

| Módulo | Descripción |
|--------|-------------|
| `Admin` | Panel de administración completo |
| `Auth` | Login, logout, 2FA TOTP |
| `Comunicaciones` | Gestión de comunicaciones internas |
| `Ecoe` | Empresa de Comercialización de Energía Eléctrica |
| `Etcee` | Módulo ETCEE |
| `Gero` | Módulo base de gerencia (Dashboard + secciones) |

#### ECOE – Tarifa Social

Sub-módulo funcional dentro de `App\Modules\Ecoe`.

| Componente | Ruta / Archivo |
|------------|----------------|
| SQL inicial | `app/Database/ecoe_tarifa_social_schema.sql` |
| Controlador admin | `App\Modules\Ecoe\Controllers\TarifaSocialController` |
| Modelo distribuidoras | `App\Modules\Ecoe\Models\DistribuidoraModel` |
| Modelo NIS | `App\Modules\Ecoe\Models\NisBaseModel` |
| Modelo estados | `App\Modules\Ecoe\Models\TsEstadoModel` |
| Modelo tickets | `App\Modules\Ecoe\Models\TsTicketModel` |
| Modelo adjuntos | `App\Modules\Ecoe\Models\TsAdjuntoModel` |
| Vista admin (tickets) | `app/Modules/Ecoe/Views/ts_index.php` |
| Vista admin (NIS/XLSX) | `app/Modules/Ecoe/Views/ts_nis.php` |
| Vista pública | `app/Views/public/pages/tarifa_social.php` |

**Rutas administrativas:**

| Método | Ruta | Permiso |
|--------|------|---------|
| `GET/POST` | `/gerencias/ecoe/tarifa-social` | `gerencia.ecoe.tarifa_social.access` |
| `GET/POST` | `/gerencias/ecoe/tarifa-social/nis` | `gerencia.ecoe.tarifa_social.access` |
| `GET` | `/gerencias/ecoe/tarifa-social/adjuntos?ticket_id=X` | ídem |
| `GET` | `/gerencias/ecoe/tarifa-social/adjunto/{id}` | ídem |

**Rutas públicas:**

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/consulta/tarifa-social` | Página pública con consulta, solicitud y seguimiento |
| `POST` | `/api/ecoe/ts/consultar-nis` | Consulta correlativo NIS (AJAX cifrado) |
| `POST` | `/api/ecoe/ts/crear-ticket` | Crea solicitud con adjuntos (AJAX cifrado) |
| `POST` | `/api/ecoe/ts/rastrear` | Rastreo por DPI o código de referencia (AJAX cifrado) |

**Archivos subidos:** guardados en `writable/uploads/ecoe/ts/` con nombres UUID aleatorios. Acceso solo mediante ruta autenticada del panel.

**Carga XLSX:** el sistema detecta automáticamente la columna de correlativo/NIS dentro del archivo. Columnas reconocidas: `correlativo`, `nis`, `id_usuario`; opcionales: `activ_economica`, `consumo_kwh`.

---

## Portal Público

Accesible sin autenticación. Gestionado por `PublicPortalController` con el servicio `PublicPortalService`.

### Páginas disponibles

| Ruta | Descripción |
|------|-------------|
| `/` | Página principal / Home |
| `/consulta/beneficiados` | Consulta de beneficiarios (búsqueda por DPI) |
| `/consulta/electrificacion` | Consulta de proyectos de electrificación |
| `/consulta/cortes` | Consulta de cortes del servicio |
| `/consulta/tarifa-social` | Solicitud y seguimiento de Tarifa Social ECOE |

### API pública

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/public/captcha` | Genera CAPTCHA para formularios |
| `POST` | `/api/public/beneficiados` | Consulta beneficiario por DPI |
| `GET` | `/api/public/electrificacion` | Listado de proyectos de electrificación |
| `GET` | `/api/public/cortes` | Listado de cortes vigentes |

El portal aplica el filtro `readOnly` en todas sus rutas para bloquear escrituras no autorizadas.

---

## Panel de Administración

Todas las rutas bajo `/admin/*` requieren autenticación (`adminAuth`) y registro de actividad (`activityLog`). Cada ruta individual exige además el permiso específico mediante `adminPermission:<slug>`.

### Secciones del panel

| Ruta | Controlador | Permiso requerido |
|------|-------------|-------------------|
| `/admin/` | `DashboardController` | `admin.dashboard.view` |
| `/admin/gerencias` | `GerenciasController` | `admin.gerencias.view` |
| `/admin/usuarios` | `UsersController` | `admin.usuarios.view` |
| `/admin/uploads` | `UploadController` | `admin.uploads.view` |
| `/admin/logs` | `LogsController` | `admin.logs.view` |
| `/admin/portal-publico` | `PublicMenuController` | `admin.portal_publico.view` |
| `/admin/roles` | `RolesController` | `admin.roles.view` |
| `/admin/:gerencia` | `GerenciaModuleController` | Acceso por gerencia |

### `AdminBaseController`

Clase base abstracta para todos los controladores del panel. Provee:

- `authProfile()` – Valida la sesión, refresca el perfil RBAC desde la BD y retorna `redirect()` si hay cualquier irregularidad.
- `adminView(string $view, array $data)` – Renderiza el layout del admin inyectando contexto compartido (`isSuperAdmin`, `auth`, `permissions`) en `$innerData` para que las vistas internas lo reciban.
- `encryptedAdminResponse(array $data, int $status)` – Retorna respuestas JSON cifradas para las APIs del panel.

---

## Autenticación y Seguridad

### Flujo de login

1. `GET /login` → formulario de login.
2. `POST /login` → `AuthController::login()` valida credenciales, construye sesión `auth` y redirige.
3. Si el usuario tiene 2FA activo y no ha verificado, es redirigido a `GET /2fa/verify`.
4. `POST /2fa/verify` → `AuthController::verifyTwoFactor()` valida el código TOTP.

### 2FA / TOTP

Implementado en `TotpService` usando las librerías `OTPHP` y `BaconQrCode`. Soporta:

- Generación de secreto TOTP.
- Generación de URI de provisionamiento compatible con Google Authenticator, Authy, etc.
- Generación de código QR en SVG para el proceso de configuración.

Rutas de configuración 2FA:

| Ruta | Descripción |
|------|-------------|
| `GET /2fa/setup` | Vista de configuración 2FA |
| `POST /2fa/setup` | Confirmar y activar 2FA |
| `GET /2fa/verify` | Vista de verificación del código |
| `POST /2fa/verify` | Validar código TOTP |

### Protección de contraseñas

Las contraseñas se almacenan con `password_hash()` (bcrypt). Nunca se guardan en texto plano.

---

## RBAC – Control de Acceso por Roles

El sistema de permisos está centralizado en `RbacService` (`app/Modules/Auth/Services/RbacService.php`).

### Estructura de datos

```
users  ──┬── user_roles ── roles ── role_permissions ── permissions
         └── gerencias
```

### Métodos principales de `RbacService`

| Método | Descripción |
|--------|-------------|
| `getUserAccessProfile(int $userId)` | Retorna el perfil completo: datos de usuario, gerencia, roles y permisos resueltos. |
| `isSuperAdminByPermissions(array $permissions)` | Verifica si el usuario tiene permisos de super administrador. |
| `userCanAccessModule(int $userId, string $moduleSlug)` | Verifica acceso a un módulo de gerencia. |
| `canManageGerenciaId(int $userId, int $gerenciaId)` | Verifica si el usuario puede gestionar una gerencia específica. |
| `getAccessibleGerencias(int $userId)` | Retorna las gerencias accesibles para el usuario. |
| `resolvePermissions(int $userId)` | Consolida todos los permisos del usuario desde sus roles. |

### Slugs de permisos estándar

Los permisos siguen la convención `<area>.<recurso>.<acción>`, por ejemplo:

- `admin.dashboard.view`
- `admin.usuarios.view`
- `admin.roles.view`
- `gerencia.<slug>.access`

---

## Filtros HTTP

Definidos en `app/Filters/` y registrados en `app/Config/Filters.php`.

| Alias | Clase | Función |
|-------|-------|---------|
| `readOnly` | `PublicReadOnlyFilter` | Bloquea escrituras (POST/PUT/DELETE) en el portal público. |
| `adminAuth` | `AdminAuthFilter` | Verifica sesión activa y 2FA completado para el panel admin. |
| `adminPermission` | `AdminPermissionFilter` | Verifica permiso específico (pasado como argumento a la ruta). |
| `gerenciaAccess` | `GerenciaPermissionFilter` | Controla acceso a módulos de gerencia. |
| `activityLog` | `ActivityLogFilter` | Registra automáticamente cada solicitud HTTP en la tabla `activity_logs`. |

---

## Base de Datos y Migraciones

### Migraciones disponibles

| Archivo | Tablas creadas |
|---------|---------------|
| `2026-05-06-120000_CreateActivityLogsTable` | `activity_logs` |
| `2026-05-07-000001_CreatePublicPortalTables` | `public_beneficiarios`, `public_electrificacion`, `public_cortes` |
| `2026-05-07-000002_CreatePublicMenuItemsTable` | `public_menu_items` |

El esquema base (usuarios, gerencias, roles, permisos) se encuentra en `app/Database/inde_core_schema.sql`.

### Tablas principales

| Tabla | Descripción |
|-------|-------------|
| `users` | Usuarios del sistema con `soft delete`, `status` y flag `twofa_enabled`. |
| `gerencias` | Unidades organizacionales con `slug` único. |
| `roles` | Roles definibles por el super administrador. |
| `permissions` | Permisos atómicos identificados por `slug`. |
| `user_roles` | Relación N:M usuarios–roles. |
| `role_permissions` | Relación N:M roles–permisos. |
| `activity_logs` | Auditoría de solicitudes HTTP (usuario, endpoint, método, IP, status). |
| `public_beneficiarios` | Datos de beneficiarios consultables públicamente (DPI hasheado). |
| `public_electrificacion` | Proyectos de electrificación rurales. |
| `public_cortes` | Cortes de servicio eléctrico. |
| `public_menu_items` | Ítems del menú del portal público (gestionable desde el admin). |

---

## Internacionalización (i18n)

El portal público soporta **5 idiomas**:

| Código | Idioma |
|--------|--------|
| `es` | Español (default) |
| `en` | English |
| `quc` | K'iche' |
| `qeq` | Q'eqchi' |
| `cak` | Kaqchikel |

Los archivos de traducción se encuentran en `app/Language/<código>/`. El cambio de idioma se realiza mediante `GET /locale/:segment`, que guarda la preferencia en sesión.

---

## Convenciones de desarrollo

- **Namespace de módulos:** `App\Modules\<Nombre>\<Capa>\<Clase>`
- **Controladores admin:** siempre extender `AdminBaseController` y llamar `$this->authProfile()` al inicio de cada acción.
- **Contexto en vistas:** pasar `isSuperAdmin`, `auth` y `permissions` dentro de `innerData` (no solo en el `$data` del layout), ya que `adminView()` los inyecta en el contexto del inner view.
- **Respuestas API del panel:** usar `$this->encryptedAdminResponse()` para todas las respuestas JSON del panel admin.
- **Permisos:** definir slugs en formato `<área>.<recurso>.<acción>` y proteger cada ruta con `adminPermission:<slug>`.
- **Soft deletes:** los usuarios usan `deleted_at`; nunca hacer `DELETE` directo en producción.
- **Contraseñas:** siempre `password_hash()` / `password_verify()`, nunca texto plano.

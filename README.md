# Portal INDE

Portal institucional del **Instituto Nacional de Electrificación (INDE) de Guatemala**, desarrollado con **CodeIgniter 4**. Combina servicios de consulta pública multilingüe con herramientas internas para la gestión de información y operaciones institucionales.

La aplicación usa una arquitectura MVC modular, con áreas separadas por dominio y una experiencia pública y administrativa integrada.

## Requisitos

- PHP 8.2 o superior y Composer.
- MySQL o MariaDB para la base principal.
- Apache con `mod_rewrite` o Nginx, con `public/` como document root.
- SQL Server y las extensiones PHP `sqlsrv`/`pdo_sqlsrv` solo para las integraciones ECOE que lo requieren.

## Instalación rápida

Configure el entorno y prepare la base de datos siguiendo el [manual técnico](MANUAL_TECNICO.md). Las migraciones actuales crean el esquema core/RBAC y las tablas de los módulos; en una base MySQL vacía, `php spark migrate` realiza el bootstrap. En XAMPP también puede usar `public/auto_installer.php` desde localhost para crear/configurar `.env` y ejecutar migraciones pendientes. El instalador está restringido a conexiones locales.

Después de instalar dependencias y configurar la conexión, ejecute desde la raíz del proyecto:

```bash
composer install
php spark migrate
php spark db:seed CatalogoUbicacionesSeeder
php spark sync:permissions
```

En XAMPP, si PHP no está en `PATH`, use `C:\xampp\php\php.exe` para ejecutar los comandos Spark. El manual técnico describe el orden de inicialización y los requisitos por entorno.

Las actualizaciones de código PHP se despliegan desde Git. Un superadministrador puede usar **Admin > Actualizaciones** (`/admin/update`) para exportar/importar datos seleccionados y recursos físicos; el ZIP excluye por completo archivos `.php`. Antes de importar datos que dependan de un cambio de esquema, despliegue el código y ejecute las migraciones desde **Admin > Migraciones** (`/admin/migraciones`). Consulte el apartado de actualización del [manual técnico](MANUAL_TECNICO.md) y respalde base de datos y cargas antes de cada operación.

## Módulos principales

- **Admin:** administración institucional, usuarios, roles, permisos, auditoría y traducciones dinámicas.
- **Auth:** inicio y cierre de sesión, sesiones y segundo factor TOTP.
- **Comunicaciones:** gestión de comunicaciones internas.
- **ECOE:** Tarifa Social, tarifas mensuales, NIS, distribuidoras, formularios, Grandes Usuarios y EEM.
- **ETCEE:** gestión de cortes, estados de mantenimiento e información geográfica SNI.
- **GERO:** comunidades, electrificación y gestión de cargas de datos.

El gestor de idiomas forma parte del módulo **Admin**; no es un módulo independiente.

## Documentación

- [Manual técnico e instalación](MANUAL_TECNICO.md): arquitectura, configuración, bases de datos, seguridad, migraciones y solución de problemas.
- [Estándares de desarrollo](instruction.md): convenciones y directrices del proyecto.

# Portal INDE

Portal institucional del **Instituto Nacional de Electrificación (INDE) de Guatemala**, desarrollado con **CodeIgniter 4**. Combina servicios de consulta pública multilingüe con herramientas internas para la gestión de información y operaciones institucionales.

La aplicación usa una arquitectura MVC modular, con áreas separadas por dominio y una experiencia pública y administrativa integrada.

## Requisitos

- PHP 8.2 o superior y Composer.
- MySQL o MariaDB para la base principal.
- Apache con `mod_rewrite` o Nginx, con `public/` como document root.
- SQL Server y las extensiones PHP `sqlsrv`/`pdo_sqlsrv` solo para las integraciones ECOE que lo requieren.

## Instalación rápida

Configure el entorno y prepare la base de datos siguiendo el [manual técnico](MANUAL_TECNICO.md). Con la base inicial lista, ejecute desde la raíz del proyecto:

```bash
composer install
php spark migrate
php spark db:seed CatalogoUbicacionesSeeder
php spark sync:permissions
```

En XAMPP, si PHP no está en `PATH`, use `C:\xampp\php\php.exe` para ejecutar los comandos Spark. El manual técnico describe el orden de inicialización y los requisitos por entorno.

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

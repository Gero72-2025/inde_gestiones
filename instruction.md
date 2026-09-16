# INDE Core Manual

## Requisitos base

- PHP 8.1 o superior. El proyecto declara ese minimo en composer.json.
- MariaDB o MySQL con soporte InnoDB y utf8mb4.
- Definir en .env una llave fuerte para `security.ajaxCipherKey` y el emisor de TOTP en `security.totpIssuer`.
- Instalar dependencias Composer para renderizar QR local y usar la libreria TOTP recomendada.

## SQL inicial

1. Importa el archivo `app/Database/inde_core_schema.sql` desde phpMyAdmin o el asistente SQL de cPanel.
2. El script crea la base de datos `portal_inde_db` y un superusuario inicial con rol `Super Administrador`.
3. Credenciales iniciales del superusuario:
    Usuario: `superadmin`
    Correo: `admin@portal-inde.local`
    Password temporal: `AdminPortal2026!`
4. Cambia la contrasena inmediatamente despues del primer acceso y habilita 2FA desde `/2fa/setup`.
5. Si necesitas permisos puntuales adicionales, puedes agregarlos en `user_permissions`.

## Panel administrativo (Fase 2)

- Acceso base: `/admin`
- Vistas administrativas separadas de las vistas publicas en `app/Modules/Admin/Views`.
- Cada gerencia opera como modulo independiente dentro de `app/Modules/{GerenciaNamespace}`.
- Rutas activas por gerencia:
    - `/gerencias/{gerencia}/`
    - `/gerencias/{gerencia}/dashboard`
    - `/gerencias/{gerencia}/modulo/{seccion}`
- El shell visual compartido de las gerencias reutiliza `app/Modules/Admin/Views/layout.php` para mostrar menu lateral, encabezado, usuario actual, boton de cerrar sesion y pie de pagina.

### CRUD y aislamiento por gerencia

- Gestion de gerencias: `/admin/gerencias`
- Gestion de usuarios: `/admin/usuarios`
- Carga masiva: `/admin/uploads`
- Roles y permisos: `/admin/roles`
- Portal publico: `/admin/portal-publico`
- Si un usuario no es super administrador, solo ve y modifica datos de su `gerencia_id`.

### Seguridad del NAV administrativo

- El menu lateral administrativo ya no es fijo: cada opcion se renderiza segun permisos del usuario autenticado.
- Permisos actuales del NAV:
    - `admin.dashboard.view`
    - `admin.gerencias.view`
    - `admin.usuarios.view`
    - `admin.roles.view`
    - `admin.portal_publico.view`
    - `admin.idiomas.view`
    - `admin.logs.view`
    - `admin.uploads.view`
- Las rutas administrativas tambien estan protegidas por permiso con el alias `adminPermission`.
- El `Super Administrador` siempre conserva acceso total porque la sincronizacion enlaza automaticamente esos permisos al rol.

### Carga masiva y trazabilidad

- Archivos permitidos: `.xlsx`, `.xls`, `.csv`
- Tamano maximo configurable en `.env`: `security.uploadMaxSizeKB`
- Destino de archivos: `writable/uploads/gerencias/{gerencia_id}`
- Tabla de trazabilidad: `upload_logs`
    - Campos clave: `id`, `gerencia_id`, `usuario_id`, `nombre_archivo`, `registros_procesados`, `fecha_creacion`

### Descarga segura de archivos

- Los archivos no se exponen por URL directa.
- Se descargan por controlador autenticado: `/admin/uploads/{id}/download`.
- El sistema valida sesion + permisos de gerencia antes de entregar el archivo.

## Agregar una nueva gerencia

La forma recomendada ya no es crear carpetas manualmente.

1. Crea la gerencia desde `/admin/gerencias`.
2. El sistema valida el `slug` y genera automaticamente:
    - Registro en tabla `gerencias`
    - Estructura MVC base en `app/Modules/{GerenciaNamespace}`
    - Archivo `Config/Routes.php`
    - `DashboardController.php`
    - `ModuleController.php`
    - `Models/BaseModuleModel.php`
    - Vistas `dashboard.php` y `module.php`
    - Permisos base de la gerencia
3. Permisos base creados automaticamente al registrar una gerencia:
    - `gerencia.{slug}.access`
    - `gerencia.{slug}.dashboard.access`
    - `gerencia.{slug}.modulo.access`
4. El rol `Super Administrador` recibe automaticamente esos permisos base.
5. Si agregas nuevas rutas o submodulos dentro de la gerencia, luego ejecuta `Sincronizar Modulos` en `/admin/roles` para detectar permisos nuevos declarados en filtros de rutas.

Ejemplo de archivo generado en `app/Modules/Planeacion/Config/Routes.php`:

```php
<?php

$routes->group('gerencias/planeacion', [
    'namespace' => 'App\\Modules\\Planeacion\\Controllers',
], static function ($routes) {
    $routes->get('/', 'DashboardController::index', ['filter' => 'gerenciaAccess:planeacion,gerencia.planeacion.dashboard.access']);
    $routes->get('dashboard', 'DashboardController::index', ['filter' => 'gerenciaAccess:planeacion,gerencia.planeacion.dashboard.access']);
    $routes->get('modulo/(:segment)', 'ModuleController::index/$1', ['filter' => 'gerenciaAccess:planeacion,gerencia.planeacion.modulo.access']);
});
```

### Agregar submodulos y permisos nuevos a una gerencia existente

1. Crea controladores, modelos y vistas dentro del modulo de esa gerencia.
2. Declara las rutas en `app/Modules/{GerenciaNamespace}/Config/Routes.php`.
3. Protege cada ruta con `gerenciaAccess:{slug},{permissionSlug}`.
4. Ejecuta `Sincronizar Modulos` desde `/admin/roles`.
5. La sincronizacion detecta los `permissionSlug` declarados en rutas y los inserta/actualiza en `permissions`.
6. Asigna esos permisos a los roles necesarios desde la pantalla de roles.
7. Vincula usuarios a la gerencia en `users.gerencia_id` y luego asigna sus roles.

## Uso del filtro de seguridad

- Alias configurado: `gerenciaAccess`
- Firma esperada: `gerenciaAccess:{slugGerencia},{permissionSlug}`
- Comportamiento:
  - Rechaza usuarios anonimos y redirige a `/login`.
  - Obliga a completar 2FA si la cuenta lo tiene activado.
  - Verifica que el usuario pertenezca a la gerencia del modulo o que posea un permiso transversal.
  - Comprueba el permiso requerido con soporte para comodines en los slugs.

Ejemplo de ruta protegida:

```php
$routes->get('gerencias/planeacion/reportes', 'ReportesController::index', [
    'filter' => 'gerenciaAccess:planeacion,gerencia.planeacion.reportes.access',
]);
```

## Uso del filtro administrativo por permiso

- Alias configurado: `adminPermission`
- Firma esperada: `adminPermission:{permissionSlug}`
- Ejemplo:

```php
$routes->get('admin/usuarios', 'UsersController::index', [
    'filter' => 'adminPermission:admin.usuarios.view',
]);
```

- Si el usuario no tiene permiso:
    - En vistas normales se redirige a `/admin` con mensaje de error.
    - En endpoints AJAX se responde HTTP `403` con JSON.

## Sincronizacion de modulos y permisos

- Ruta funcional: `/admin/roles` > boton `Sincronizar Modulos`.
- La sincronizacion actual hace tres cosas:
    1. Detecta modulos de gerencia existentes en `app/Modules`.
    2. Inserta o actualiza gerencias faltantes en la tabla `gerencias`.
    3. Detecta permisos declarados en rutas de gerencia y permisos del NAV administrativo, y los inserta/actualiza en `permissions`.
- Adicionalmente enlaza los permisos encontrados al rol `Super Administrador`.

## Portal publico

- Controlador principal: `app/Controllers/PublicPortalController.php`
- Servicio de datos: `app/Services/PublicPortalService.php`
- Layout maestro: `app/Views/public/layout.php`
- Regla de encabezado publico: toda pagina publica principal debe renderizarse dentro de `app/Views/public/layout.php` para conservar encabezado, selector de idioma, navegacion y estilos base.
- Evitar nuevas vistas publicas standalone (por ejemplo `app/Views/public/*.php`) para rutas principales; usar siempre `app/Views/public/pages/*.php` + layout compartido.
- Paginas por seccion:
    - `app/Views/public/pages/home.php`
    - `app/Views/public/pages/beneficiados.php`
    - `app/Views/public/pages/electrificacion.php`
    - `app/Views/public/pages/cortes.php`
- Idiomas soportados en `app/Config/App.php`:
    - `es`
    - `en`
    - `quc`
    - `qeq`
    - `cak`
- El cambio de idioma usa `site_locale` en sesion y `PublicPortalController::resolveLocale()` aplica `setLocale()` por request.
- El menu publico editable se administra desde `/admin/portal-publico`.
- El mensaje de WhatsApp y los labels publicos deben definirse via archivos `app/Language/{locale}/Portal.php`.

### Checklist QA para Paginas Publicas (obligatorio antes de publicar)

1. **Layout y Encabezado:** La pagina renderiza dentro de `app/Views/public/layout.php` (no standalone), mostrando hero, navegacion principal y selector de idioma.
2. **Ruta y Controlador:** La ruta publica apunta a un controlador que arma `innerView` (`app/Views/public/pages/*.php`) y `scriptsView` coherentes.
3. **Navegacion Activa:** El item correspondiente del nav (`home`, `beneficiados`, `electrificacion`, `cortes`) aparece activo segun `page`.
4. **Idioma:** Los textos visibles usan `lang('Portal.*')`; no dejar labels fijos sin internacionalizacion.
5. **Consumo de Datos:** Toda consulta AJAX publica usa endpoints definidos en `window.PortalConfig.endpoints`.
6. **Seguridad Minima:** Si aplica cifrado, el frontend usa `security.ajaxCipherKey` y valida respuestas cifradas de backend.
7. **Responsive:** Validar visualmente en movil y escritorio (header, tablas, formularios y botones flotantes).
8. **Accesibilidad Basica:** Inputs con label, botones con texto claro, contraste suficiente y `aria-label` donde corresponda.
9. **Consistencia de Marca:** Mantener tipografias, paleta y estilos del layout maestro; no introducir otro header paralelo.
10. **Smoke Test:** Probar carga de pagina, filtros principales, cambio de idioma y enlace de WhatsApp sin errores en consola.

## Primer enlace de la app autenticadora

1. El usuario inicia sesion con usuario/correo y contrasena.
2. Si `twofa_enabled = 0`, el usuario puede entrar y luego navegar a `/2fa/setup`.
3. Escanea el QR con una app TOTP compatible. No requiere cuenta de Gmail. Funciona offline despues del enrolamiento.
4. Alternativamente, copia manualmente el secreto base32 en la app autenticadora.
5. Ingresa el primer codigo de 6 digitos para confirmar y guardar `google_2fa_secret` junto con `twofa_enabled = 1`.
6. En futuros logins, el sistema pedira el codigo TOTP despues del password.

Apps recomendadas:

- Aegis Authenticator
- Google Authenticator
- Microsoft Authenticator
- 2FAS

## Respuestas AJAX cifradas

- Usa `BaseController::encryptedJsonResponse()` cuando una accion deba devolver JSON cifrado.
- El metodo empaqueta la respuesta con AES-256-CBC, IV aleatorio y HMAC SHA-256.
- El frontend debe descifrar el campo `payload` usando la misma llave definida en `.env`.

## Dependencias Composer

Instala o actualiza dependencias antes de usar QR local y el proveedor TOTP recomendado:

```bash
composer install
```

Si el entorno local no tiene Composer en PATH, usa el binario correspondiente del servidor o del panel de hosting.

## Estándar de Nuevos Módulos (Gerencias)

Cada nueva funcionalidad de gerencia debe seguir este patrón:
1. **Rutas Propias:** Crear `app/Modules/{Name}/Config/Routes.php` y cargarlo en el Routes.php principal o vía Autoload.
2. **Modelos:** Los modelos deben usar la conexión definida y, de ser necesario, manejar Soft Deletes si los datos son críticos.
3. **Controladores:** - Administrativo: Debe extender de `App\Modules\Admin\Controllers\AdminBaseController`.
   - Público: Debe extender de `App\Controllers\BaseController` y ser solo de lectura.
4. **Vistas:** Las vistas administrativas deben usar el layout `admin_layout` y cargarse mediante `$this->adminView()`.
5. **Navegación obligatoria:** Todo modulo nuevo debe incorporar su acceso de navegación visible (sidebar/menu/atajo) al finalizar la implementación.
6. **Validación de visibilidad:** Tras crear el modulo, verificar con un usuario de prueba que el enlace aparece en el menú según permisos y que la ruta abre el CRUD o dashboard esperado.

### Regla UI obligatoria para CRUD

- Todo CRUD nuevo o refactor de CRUD existente debe usar modal para crear y modal para editar.
- Toda acción de eliminar debe usar modal de confirmación (no usar `window.confirm`).
- Evitar pantallas separadas para formularios de alta/edición cuando el caso sea un CRUD administrativo estándar.

## Modulos Activos

- **ETCEE - Calendario de Cortes:**
    - Controlador Admin: `App\Modules\Etcee\Controllers\CortesController`
    - Modelo: `App\Modules\Etcee\Models\CortesModel`
    - Vista Admin: `app/Modules/Etcee/Views/cortes_admin.php`

- **GERO - Comunidades Electrificacion:**
    - Controlador Admin: `App\Modules\Gero\Controllers\ComunidadesController`
    - Modelo: `App\Modules\Gero\Models\ComunidadesModel`
    - Vista Admin: `app/Modules/Gero/Views/comunidades_index.php`
    - Vista Publica disponible: `app/Views/public/pages/comunidades.php` (renderizada por `app/Views/public/layout.php`)
    - Vista Publica disponible: `app/Views/public/pages/cortes.php` (renderizada por `app/Views/public/layout.php`)

## Convencion de Prefijos de Base de Datos

- Toda tabla nueva del modulo ETCEE debe iniciar con el prefijo `etcee_`.
- Tablas de calendario vigentes:
    - `etcee_cortes`
    - `etcee_cortes_ubicaciones`

- Toda tabla nueva del modulo GERO Comunidades debe iniciar con el prefijo `gero_`.
- Tablas del modulo GERO Comunidades:
    - `gero_comunidades`
    - `gero_comunidades_bitacora`

## Regla de Migraciones para Tablas Nuevas

> **Regla obligatoria sin excepciones:** Toda tabla nueva o cambio de esquema debe crearse y versionarse mediante migraciones de CodeIgniter 4. Queda prohibido ejecutar SQL manual directo en cualquier entorno (desarrollo, staging o produccion).

- Crear la migracion con: `php spark make:migration NombreDescriptivo`
- Aplicar en el entorno: `php spark migrate`
- Revertir si es necesario: `php spark migrate:rollback`
- Cada migracion debe implementar `up()` (crear/alterar) y `down()` (revertir) de forma completa.
- Los archivos de migracion viven en `app/Database/Migrations/` y deben commitearse junto con el codigo del modulo.
- Si una tabla depende de catalogos maestros que pueden no existir en todos los entornos, la migracion debe validar existencia antes de crear llaves foraneas.
- Los scripts `.sql` sueltos (como los de referencia arquitectonica) **no deben ejecutarse directamente**; solo sirven como documentacion o punto de partida para redactar la migracion correspondiente.

## Estándar de Desarrollo para Módulos (CRUD Modal)

Cada módulo funcional de una gerencia debe seguir este patrón para consistencia, usabilidad y mantenibilidad:

### Estructura Base

1. **Tabla de Datos (Index View)**
   - Ruta: `/gerencias/{gerencia}/{modulo}` o `/admin/{gerencia}/{modulo}`
   - Vista que muestre tabla con registros (primeros 100-200 ordenados por relevancia)
   - Debe incluir:
     - Columna ID, nombre/titulo, estado o capa, fecha de creación
     - Badge de estado/categoría con color
     - Botones Editar (abre modal) y Eliminar (confirma en modal)
     - Botón "+ Nuevo Registro" (abre modal vacío para crear)
     - Pagination si existen >200 registros
     - Búsqueda por nombre/texto principal
     - Filtros por estado o categoría (si aplica)

2. **Modal de CRUD**
   - No crear página separada para crear/editar
   - Modal único reutilizable para ambas operaciones (Create y Edit)
   - Dentro del modal:
     - Título dinámico: "Nuevo [Recurso]" o "Editar [Recurso]"
     - Formulario con validación client-side visual
     - Botón Guardar (POST/PUT) y Cancelar
     - Mostrar mensajes de error/éxito tras submit
     - Hidden input con ID para Edit, vacío para Create

3. **Controlador**
   - Método `index()`: renderiza vista con tabla + modal template
   - Método `save()` POST: crea o actualiza según ID
   - Método `delete()` POST: elimina registros
   - Todos los métodos retornan JSON o redirect con flash messages
   - Usar `encryptedJsonResponse()` para respuestas API

4. **Dashboard de Gerencia**
   - Agregar botón/icono visible al nuevo módulo (ej: "Abrir SNI ETCEE")
   - Ubicación: sección de accesos rápidos o submenú de módulos
   - Enlace a la ruta index del módulo

### Flujo de Uso

1. Usuario accede a `/admin/etcee/sni` (tabla visible)
2. Hace clic en "+ Nuevo Registro" → modal se abre vacío
3. Completa datos y clickea Guardar → AJAX POST → flash success → modal se cierra → tabla se recarga
4. Para editar: clickea "Editar" en una fila → modal se abre con datos cargados → edita → Guardar → flash success → modal cierra → tabla actualizada
5. Para eliminar: clickea "Eliminar" → confirmación inline o modal → POST delete → flash success → tabla recargada

### Ejemplo de Implementación (SNI ETCEE)

- Index Route: `/admin/etcee/sni`
- Renderiza: `sni_index.php` con tabla y modal template
- Modal ID: `#sniEditModal`
- Métodos controlador: `index()`, `save()`, `delete()`
- Respuestas: JSON para AJAX, redirect con flash para form tradicional

### Beneficios

- **UX mejorada**: sin cambios de página, feedback inmediato
- **Consistencia**: todos los módulos siguen el mismo patrón
- **Mantenibilidad**: modal reutilizable con mínimas variaciones
- **Accesibilidad**: navegación clara desde dashboard de gerencia
- **Performance**: menos renders, AJAX + DOM updates

## Modulo ECOE – Tarifa Social

### Prefijo de Base de Datos

- Toda tabla del modulo ECOE debe iniciar con el prefijo `ecoe_`.
- Tablas del sub-modulo Tarifa Social:
    - `ecoe_distribuidoras`
    - `ecoe_nis_base`
    - `ecoe_ts_estados`
    - `ecoe_ts_tickets`
    - `ecoe_ts_adjuntos`

### Migracion de Base de Datos

- El esquema de tablas esta documentado en `app/Database/ecoe_tarifa_social_schema.sql` **solo como referencia**.
- Para crear las tablas en cualquier entorno usar exclusivamente migraciones:
  ```bash
  php spark make:migration CreateEcoeTarifaSocialTables
  php spark migrate
  ```
- El archivo `.sql` de referencia NO debe ejecutarse directamente en ningun entorno.

### Almacenamiento de Archivos

- Los adjuntos de Tarifa Social se guardan en: `writable/uploads/ecoe/ts/`
- Los archivos de carga masiva NIS (temporales) se procesan desde: `writable/uploads/ecoe/nis_tmp/` y se eliminan tras el procesamiento.
- Ambas rutas NO estan expuestas publicamente. La descarga de adjuntos requiere sesion administrativa con permiso `gerencia.ecoe.tarifa_social.access`.

### Tipos de Archivo Permitidos (adjuntos de tickets)

- MIME validos: `image/jpeg`, `image/png`, `image/webp`, `application/pdf`
- Extensiones validas: `.jpg`, `.jpeg`, `.png`, `.webp`, `.pdf`
- Tamano maximo por archivo: 5 MB

### Carga Masiva XLSX (Base NIS)

- Controlador: `TarifaSocialController::nisIndex()` + `handleNisUpload()`
- La funcion `parseXlsxNis()` detecta automaticamente la columna de correlativo buscando encabezados que contengan: `correlativo`, `nis`, `id_usuario`.
- Columnas opcionales detectadas: `activ_economica`, `consumo_kwh`.
- Si no se detecta la columna de correlativo se lanza una excepcion con mensaje descriptivo.
- Los registros se insertan o actualizan (upsert) segun el par `(id_usuario, distribuidora_id)`.

### Logica de Codigo de Referencia

- Patron: `TS-{ultimos5DpiDigitos}-{correlativo}-{Ymd}`
- Ejemplo: `TS-12345-NIS001-20260513`

### Regla de Elegibilidad

- Un usuario con `consumo_kwh >= 100` en `ecoe_nis_base` queda **bloqueado** para Tarifa Social.
- El portal publico muestra un modal con `activ_economica` y consumo al detectar bloqueo.

### Flujo del Portal Publico

1. Usuario selecciona distribuidora e ingresa correlativo.
2. AJAX cifrado consulta `POST /api/ecoe/ts/consultar-nis`.
3. Si bloqueado → modal informativo. Si libre → modal con opciones (crear / rastrear).
4. Al crear: formulario con datos personales + 5 tipos de adjunto (3 obligatorios).
5. Envio via `POST /api/ecoe/ts/crear-ticket` (multipart).
6. Al exito se muestra el `codigo_referencia`.
7. Rastreo: columna 3 de la vista o boton del modal, via `POST /api/ecoe/ts/rastrear`.

### Rutas a Declarar en Routes.php (ecoe)

```php
// Admin
$routes->get('gerencias/ecoe/tarifa-social', 'TarifaSocialController::index', [...]);
$routes->post('gerencias/ecoe/tarifa-social', 'TarifaSocialController::index', [...]);
$routes->get('gerencias/ecoe/tarifa-social/adjuntos', 'TarifaSocialController::adjuntosJson', [...]);
$routes->get('gerencias/ecoe/tarifa-social/adjunto/(:num)', 'TarifaSocialController::descargarAdjunto/$1', [...]);
$routes->get('gerencias/ecoe/tarifa-social/nis', 'TarifaSocialController::nisIndex', [...]);
$routes->post('gerencias/ecoe/tarifa-social/nis', 'TarifaSocialController::nisIndex', [...]);

// Publico
$routes->get('consulta/tarifa-social', 'PublicPortalController::tarifaSocialPage');
$routes->post('api/ecoe/ts/consultar-nis', 'App\Modules\Ecoe\Controllers\TarifaSocialController::apiConsultarNis');
$routes->post('api/ecoe/ts/rastrear',      'App\Modules\Ecoe\Controllers\TarifaSocialController::apiRastrear');
$routes->post('api/ecoe/ts/crear-ticket',  'App\Modules\Ecoe\Controllers\TarifaSocialController::apiCrearTicket');
```

### Permisos a Sincronizar

Despues de importar el SQL y desplegar el codigo, ejecutar `Sincronizar Modulos` desde `/admin/roles` para registrar el permiso `gerencia.ecoe.tarifa_social.access` y asignarlo al rol que corresponda.

## Modulo de Idiomas y Accesibilidad

Sistema de traduccion dinamica con autodeteccion, independiente del mecanismo nativo de CodeIgniter (`lang()` + archivos `app/Language`) que ya utilizan las paginas publicas existentes. Este modulo permite editar y descubrir textos desde base de datos sin tocar codigo.

### Tabla `translations`

Creada mediante la migracion `app/Database/Migrations/2026-09-11-000001_CreateTranslationsTable.php`.

| Campo | Tipo | Descripcion |
| --- | --- | --- |
| `id` | BIGINT PK | Identificador autoincremental. |
| `lang_code` | VARCHAR(10) | Codigo de idioma (`es`, `en`, `quc`, `qeq`, `cak`). |
| `translation_key` | VARCHAR(191) | Clave unica de la cadena (ej: `home.hero.title`). |
| `source_text` | VARCHAR(500) | Texto por defecto (`$default`) recibido por `__()` cuando se creo la clave, tal cual (sin normalizar). Agregado en `2026-09-11-000002_AddSourceTextToTranslations.php`. |
| `source_text_normalized` | VARCHAR(500) | Version normalizada de `source_text` (trim, espacios colapsados, sin acentos/diacriticos, minusculas) usada para detectar textos semanticamente identicos entre claves distintas. Agregado en `2026-09-11-000003_AddSourceTextNormalizedToTranslations.php`. |
| `translation_value` | TEXT | Texto traducido para ese idioma. |
| `is_autodiscovered` | TINYINT(1) | `1` si la clave fue detectada automaticamente por `__()` y aun no ha sido revisada por un editor; `0` si fue creada/editada manualmente desde el panel, o si se autocompleto reutilizando una traduccion ya aprobada para el mismo texto origen normalizado. |
| `created_at` / `updated_at` | DATETIME | Timestamps estandar. |

Indice unico compuesto en `(lang_code, translation_key)` para evitar duplicados, mas un indice `(lang_code, source_text(191))` y otro `(lang_code, source_text_normalized(191))` para acelerar la busqueda de reutilizacion.

### Funcion global `__($key, $default = '')`

Definida en `app/Helpers/translation_helper.php` y autoloaded globalmente vía `app/Config/Autoload.php` (`$files`), por lo que esta disponible en cualquier vista o controlador sin necesidad de `helper('translation')`.

Flujo de resolucion (el diccionario global por texto normalizado tiene prioridad sobre la clave exacta):

1. Determina el idioma activo desde `session('site_locale')` (o el `defaultLocale` de `Config\App` si no hay sesion).
2. Normaliza `$default` con `translation_normalize_text()` (trim, colapsar espacios, quitar acentos/diacriticos vía `transliterator_transliterate()` o `iconv()` segun disponibilidad, y convertir a minusculas).
3. Consulta el **diccionario global cacheado** de traducciones aprobadas manualmente para ese idioma (`translations_normalized_map_{locale}`, TTL 1 hora, `TranslationModel::getApprovedNormalizedMapForLocale()`): `source_text_normalized => translation_value`, sin importar que clave origino cada traduccion.
   - Si el texto normalizado ya tiene una traduccion aprobada (`is_autodiscovered = 0`) en cualquier parte de la aplicacion, se retorna ese valor **de inmediato**, y la clave actual (`$key`) queda sincronizada en BD con ese mismo valor (`TranslationModel::upsertResolvedKey()`), creandose o corrigiendose segun haga falta. Esto es lo que permite que traducir "Solicitud" o "Fase 1" una sola vez se aplique automaticamente a cualquier otra clave que use ese mismo texto por defecto.
4. Si no hay coincidencia en el diccionario global, se busca la clave exacta en el mapa cacheado por idioma (`translations_map_{locale}`, `TranslationModel::getAllForLocale()`).
5. Si la clave tampoco existe en BD, se inserta con `is_autodiscovered = 1` y el `$default` recibido (revisando una ultima vez, directo contra BD, si aparecio una traduccion aprobada para el texto normalizado), quedando disponible para su edicion en `/admin/idiomas`.
6. `__()` devuelve siempre el valor efectivo que quedo almacenado (reutilizado del diccionario global o el `$default`), nunca deja la clave "vacia" o silenciosamente pendiente.

### Reutilizacion retroactiva desde el panel administrativo

Cuando un editor guarda manualmente una traduccion en `/admin/idiomas` (`TranslationsController::save()`), si la fila editada tiene un `source_text_normalized` registrado (o se puede derivar normalizando su `source_text`), el sistema busca **todas** las demas claves autodetectadas pendientes (`is_autodiscovered = 1`) del mismo idioma con el mismo texto origen normalizado y las actualiza automaticamente con el mismo valor aprobado (`TranslationModel::propagatePendingByNormalizedSourceText()`), marcandolas como revisadas. Ademas, el diccionario global (`translations_normalized_map_{locale}`) se limpia en cada guardado para que la proxima llamada a `__()` en cualquier vista recoja el valor recien aprobado. Esto evita traducir manualmente el mismo texto (ej. "Fase 1") en cada clave donde se repite, incluso si la capitalizacion, acentos o espacios varian ligeramente entre vistas.

### Unificacion de llaves semanticas

Ademas de la reutilizacion automatica por texto normalizado, las vistas deben preferir una unica clave canonica cuando el texto es exactamente el mismo en distintos lugares (ej. `comunidades.phase.fase_1.short` para cualquier "Fase 1", `comunidades.common.solicitud` para "Solicitud"). Revisar `app/Views/public/layout.php` (diccionario `window.PortalConfig.i18n`) antes de crear una clave nueva para un texto que ya podria existir.

### Regla obligatoria para nuevas vistas publicas

**Toda vista publica nueva que se agregue al portal (bajo `app/Views/public/**`, incluyendo `pages/*.php`, `*_script.php` y cualquier archivo servido por `PublicPortalController` o `PublicController`) debe usar exclusivamente la funcion global `__('seccion.clave_descriptiva', 'Texto por defecto')` para cualquier texto visible al usuario (titulos, parrafos, botones, placeholders, etiquetas de estado/fase, mensajes de error o exito).** Queda prohibido escribir texto plano directamente en el HTML o en cadenas JS que el usuario vea, salvo el propio `$default` que se pasa como segundo argumento de `__()`.

Reglas al elegir la clave:

- Usar el formato `seccion.subseccion.elemento` (ej. `comunidades.hero.title1`, `cortes.detail.close`). La `seccion` debe ser el nombre de la pagina o modulo (`home`, `beneficiados`, `cortes`, `comunidades`, `sni`, `tarifaSocial`, `gu`, `eem`, etc.).
- Antes de crear una clave nueva, revisar si el mismo texto (o uno semanticamente equivalente) ya tiene una clave canonica existente en `app/Views/public/layout.php` (`window.PortalConfig.i18n`) o en otras vistas de `app/Views/public/pages/`; si existe, reutilizar esa clave en lugar de crear una nueva. El sistema de reutilizacion automatica por `source_text_normalized` es una red de seguridad, no un sustituto de elegir bien la clave desde el inicio.
- Para textos dinamicos generados en JavaScript (fases, estados, badges), no hardcodear el texto en el `.js`/`*_script.php`: exponer el texto traducido desde PHP (via `__()`) dentro de `window.PortalConfig.i18n` en `layout.php`, o inyectarlo directamente como atributo/variable renderizada por el servidor, siguiendo el patron ya usado por `comunidades_script.php` y `cortes_script.php`.
- Para valores libres que vienen de base de datos (ej. un `estado` capturado por un usuario administrativo), traducir en el controlador con una clave normalizada por slug del valor (ej. `comunidades.estado.{slug}`), nunca imprimir el valor crudo sin pasar por `__()`.

Uso recomendado en vistas:

```php
<?= esc(__('home.hero.title', 'Bienvenido al Portal INDE')) ?>
```

### Barra de Accesibilidad e Idiomas (top-bar)

- Ubicada en `app/Views/public/layout.php`, justo antes del `<header class="hero">`, presente en todas las vistas publicas que usan ese layout.
- Contiene: selector de idioma (dropdown poblado con `localeOptions`/`currentLocale`, ya provistos por `PublicPortalController::pageData()`), botones de aumento/disminucion de tamano de fuente y alternador de alto contraste (persistidos en `localStorage`).
- El selector de idioma llama por AJAX a `POST /api/public/set-language` (`PublicPortalController::setLanguage()`), que actualiza `session('site_locale')` sin redireccionar, y luego el JS recarga la pagina actual (`window.location.reload()`) manteniendo la ruta.

### Modulo Administrativo "Idiomas" (`/admin/idiomas`)

- Controlador: `App\Modules\Admin\Controllers\TranslationsController` (extiende `AdminBaseController`, mismo patron de CRUD por modales que Gerencias/Usuarios).
- Rutas (`app/Modules/Admin/Config/Routes.php`, filtro `adminPermission:admin.idiomas.view`):
    - `GET /admin/idiomas` – vista con tabla, filtros (idioma, autodetectadas, busqueda por clave/texto) y modales de creacion/edicion.
    - `GET /admin/idiomas/list` – listado JSON cifrado usado por la tabla (soporta `lang`, `search`, `autodiscovered`).
    - `POST /admin/idiomas/save` – crea o actualiza una traduccion (limpia el cache del idioma afectado).
    - `POST /admin/idiomas/delete` – elimina una traduccion (limpia el cache del idioma afectado).
    - `POST /admin/idiomas/clear-cache` – boton "Limpiar Cache de Traducciones": invalida el cache de todos los idiomas soportados para que los cambios se reflejen de inmediato en el portal publico.
- Nuevo permiso de navegacion: `admin.idiomas.view`. Se agrega al listado de permisos sincronizables en `RolesController::syncAdminMenuPermissions()`; despues de desplegar, ejecutar `Sincronizar Modulos` desde `/admin/roles` para registrarlo y enlazarlo al `Super Administrador`.

### Buenas practicas al refactorizar textos estaticos

- Usar `__('modulo.seccion.clave', 'Texto original en espanol')` como segundo argumento obligatorio (sirve de fallback y de valor inicial autodetectado).
- No reemplazar los textos que ya usan `lang('Portal.xxx')` (sistema de idiomas nativo de CodeIgniter con archivos completos por locale) salvo que se decida migrar ese modulo por completo al esquema dinamico.
- Las claves deben ser descriptivas y namespaced por pagina/seccion para evitar colisiones (ej. `comunidades.hero.title1`, `cortes.filter.title`).

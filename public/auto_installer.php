<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$envPath     = $projectRoot . DIRECTORY_SEPARATOR . '.env';
$allowedInstallerIps = ['127.0.0.1', '::1'];
$clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

if (str_starts_with($clientIp, '::ffff:')) {
    $clientIp = substr($clientIp, 7);
}

if (! in_array($clientIp, $allowedInstallerIps, true)) {
    http_response_code(403);
    exit('Acceso denegado. El instalador solo acepta conexiones locales.');
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

session_set_cookie_params([
    'httponly' => true,
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Strict',
]);
session_start();

if (empty($_SESSION['installer_csrf'])) {
    $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
}

function installerEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function installerReadExistingEnv(string $envPath): array
{
    if (! is_file($envPath) || ! is_readable($envPath)) {
        return [];
    }

    $fieldMap = [
        'CI_ENVIRONMENT'            => 'environment',
        'app.baseURL'               => 'base_url',
        'database.default.hostname' => 'db_host',
        'database.default.database' => 'db_name',
        'database.default.username' => 'db_user',
        'database.default.password' => 'db_password',
        'database.default.port'     => 'db_port',
        'database.ecoe.hostname'    => 'ecoe_host',
        'database.ecoe.database'    => 'ecoe_name',
        'database.ecoe.username'    => 'ecoe_user',
        'database.ecoe.password'    => 'ecoe_password',
        'database.ecoe.port'        => 'ecoe_port',
        'security.ajaxCipherKey'    => 'ajax_cipher_key',
        'security.totpIssuer'       => 'totp_issuer',
    ];
    $loadedValues = [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$name, $rawValue] = explode('=', $line, 2);
        $name = trim(str_replace('export ', '', trim($name)));

        if (! isset($fieldMap[$name])) {
            continue;
        }

        $rawValue = trim($rawValue);

        if ($rawValue !== '' && strpbrk($rawValue[0], '"\'') !== false) {
            $quote = $rawValue[0];

            if (strlen($rawValue) < 2 || substr($rawValue, -1) !== $quote) {
                continue;
            }

            $value = substr($rawValue, 1, -1);
            $value = str_replace("\\{$quote}", $quote, $value);
            $value = str_replace('\\\\', '\\', $value);
        } else {
            $value = trim(explode(' #', $rawValue, 2)[0]);
        }

        $loadedValues[$fieldMap[$name]] = $value;
    }

    return $loadedValues;
}

function installerEnvValue(string $value): string
{
    return '"' . str_replace(
        ["\\", '"'],
        ["\\\\", '\\"'],
        $value,
    ) . '"';
}

function installerWriteEnv(string $envPath, string $contents, bool $force): ?string
{
    $currentExists = is_file($envPath);

    if ($currentExists && ! $force) {
        throw new RuntimeException('El archivo .env ya existe. Marca la opción de reconfiguración para reemplazarlo.');
    }

    $temporaryPath = tempnam(dirname($envPath), '.env-install-');

    if ($temporaryPath === false) {
        throw new RuntimeException('No fue posible crear un archivo temporal en la raíz del proyecto.');
    }

    try {
        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
            throw new RuntimeException('No fue posible escribir el archivo temporal de configuración.');
        }

        @chmod($temporaryPath, 0600);
        $backupPath = null;

        if (is_file($envPath)) {
            if (! $force) {
                throw new RuntimeException('El archivo .env apareció durante la instalación; no se sobrescribió.');
            }

            $backupPath = $envPath . '.backup.' . date('YmdHis');
            $suffix = 1;

            while (file_exists($backupPath)) {
                $backupPath = $envPath . '.backup.' . date('YmdHis') . '.' . $suffix++;
            }

            if (! rename($envPath, $backupPath)) {
                throw new RuntimeException('No fue posible respaldar el .env existente; no se modificó.');
            }
        }

        if (! rename($temporaryPath, $envPath)) {
            if ($backupPath !== null) {
                @rename($backupPath, $envPath);
            }

            throw new RuntimeException('No fue posible colocar el nuevo .env en la raíz del proyecto.');
        }

        return $backupPath;
    } finally {
        if (is_file($temporaryPath)) {
            @unlink($temporaryPath);
        }
    }
}

function installerTestMysql(array $settings): array
{
    if (! extension_loaded('mysqli')) {
        throw new RuntimeException('La extensión mysqli de PHP no está habilitada.');
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $serverConnection = @new mysqli(
        $settings['db_host'],
        $settings['db_user'],
        $settings['db_password'],
        '',
        (int) $settings['db_port'],
    );

    if ($serverConnection->connect_errno !== 0) {
        $message = $serverConnection->connect_error ?: 'Error de conexión sin detalle.';
        throw new RuntimeException('No se pudo conectar al servidor MySQL/MariaDB: ' . $message);
    }

    $lookup = $serverConnection->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');

    if ($lookup === false) {
        $message = $serverConnection->error ?: 'No fue posible consultar el catálogo.';
        $serverConnection->close();
        throw new RuntimeException('No se pudo verificar la base de datos: ' . $message);
    }

    $lookup->bind_param('s', $settings['db_name']);
    $lookup->execute();
    $lookup->store_result();
    $databaseExists = $lookup->num_rows > 0;
    $lookup->close();

    if (! $databaseExists) {
        $databaseName = str_replace('`', '``', $settings['db_name']);
        $createSql = 'CREATE DATABASE IF NOT EXISTS `' . $databaseName . '` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci';

        if (! $serverConnection->query($createSql)) {
            $message = $serverConnection->error ?: 'El servidor rechazó la creación.';
            $serverConnection->close();
            throw new RuntimeException('No se pudo crear la base de datos: ' . $message);
        }
    }

    $serverConnection->close();

    $databaseConnection = @new mysqli(
        $settings['db_host'],
        $settings['db_user'],
        $settings['db_password'],
        $settings['db_name'],
        (int) $settings['db_port'],
    );

    if ($databaseConnection->connect_errno !== 0) {
        $message = $databaseConnection->connect_error ?: 'Error de conexión sin detalle.';
        throw new RuntimeException('La base existe, pero no se pudo conectar a ella: ' . $message);
    }

    $databaseConnection->close();
    return [$databaseExists, $databaseExists
        ? 'La base de datos ya existía y la conexión quedó validada.'
        : 'La base de datos se creó con utf8mb4 y la conexión quedó validada.'];
}

function installerRunSparkCommand(string $projectRoot, string $command): array
{
    if (! function_exists('proc_open')) {
        return [1, 'proc_open no está disponible en PHP.'];
    }

    $sparkPath = $projectRoot . DIRECTORY_SEPARATOR . 'spark';

    if (! is_file($sparkPath)) {
        return [1, 'No se encontró el archivo spark.'];
    }

    $phpCli = PHP_BINDIR . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php');

    if (! is_file($phpCli)) {
        $phpCli = PHP_BINARY;
    }

    $process = @proc_open(
        [$phpCli, $sparkPath, $command],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]],
        $pipes,
        $projectRoot,
    );

    if (! is_resource($process)) {
        return [1, 'No fue posible iniciar php spark ' . $command . '.'];
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $exitCode = proc_close($process);
    $output = trim((string) $output);

    return [$exitCode, $output];
}

function installerRunMigrations(string $projectRoot): array
{
    [$beforeExit, $beforeOutput] = installerRunSparkCommand($projectRoot, 'migrate:status');
    [$migrateExit, $migrateOutput] = installerRunSparkCommand($projectRoot, 'migrate');
    [$afterExit, $afterOutput] = installerRunSparkCommand($projectRoot, 'migrate:status');
    $beforeOutput = preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', trim($beforeOutput)) ?? trim($beforeOutput);
    $migrateOutput = preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', trim($migrateOutput)) ?? trim($migrateOutput);
    $afterOutput = preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', trim($afterOutput)) ?? trim($afterOutput);
    $hasMigrationError = preg_match('/Migration failed!|Fatal error:|Uncaught (?:Error|Exception)|(?:Runtime)?Exception:|ErrorException|Error:/i', $migrateOutput) === 1;
    $success = $migrateExit === 0 && $afterExit === 0 && ! $hasMigrationError;
    $details = [];

    if ($beforeOutput !== '') {
        $details[] = "Estado previo (filas con fecha/lote ya estaban aplicadas):\n" . $beforeOutput;
    } elseif ($beforeExit !== 0) {
        $details[] = 'No se pudo consultar migrate:status antes de ejecutar.';
    }

    $details[] = $migrateOutput !== ''
        ? "Salida de php spark migrate (las líneas Running identifican las aplicadas):\n" . $migrateOutput
        : 'php spark migrate no produjo salida.';

    if ($afterOutput !== '') {
        $details[] = "Estado posterior:\n" . $afterOutput;
    } elseif ($afterExit !== 0) {
        $details[] = 'No se pudo consultar migrate:status después de ejecutar.';
    }

    if ($success && $beforeOutput !== '' && $beforeOutput === $afterOutput) {
        $details[] = 'El estado no cambió: no había migraciones pendientes.';
    }

    if (! $success) {
        $details[] = 'La ejecución de migraciones requiere revisión; salida de proceso: ' . $migrateExit . '.';
    }

    return [$success, implode("\n\n", $details)];
}

function installerDisableSelf(): array
{
    $installerPath = __FILE__;
    $disabledPath = $installerPath . '.disabled.' . date('YmdHis');
    $suffix = 1;

    while (file_exists($disabledPath)) {
        $disabledPath = $installerPath . '.disabled.' . date('YmdHis') . '.' . $suffix++;
    }

    if (! @rename($installerPath, $disabledPath)) {
        return [false, 'No se pudo renombrar automáticamente. Elimina o renombra public/auto_installer.php manualmente.'];
    }

    return [true, 'Instalador protegido mediante el nombre ' . basename($disabledPath) . '.'];
}

$defaults = [
    'environment'     => 'development',
    'base_url'        => 'http://localhost/portal-inde/',
    'db_host'         => '127.0.0.1',
    'db_name'         => '',
    'db_user'         => 'root',
    'db_password'     => '',
    'db_port'         => '3306',
    'ecoe_host'       => '',
    'ecoe_name'       => '',
    'ecoe_user'       => '',
    'ecoe_password'   => '',
    'ecoe_port'       => '1433',
    'ajax_cipher_key' => 'base64:' . base64_encode(random_bytes(32)),
    'totp_issuer'     => 'Portal INDE',
];
$existingEnvValues = installerReadExistingEnv($envPath);
$values = array_replace($defaults, $existingEnvValues);
$initialValues = $values;
$errors = [];
$steps = [];
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$envExists = is_file($envPath);
$force = false;
$configChanged = false;
$runMigrations = false;
$disableInstaller = false;

if ($isPost) {
    foreach ($values as $field => $default) {
        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $values[$field] = in_array($field, ['db_password', 'ecoe_password'], true)
                ? $_POST[$field]
                : trim($_POST[$field]);
        }
    }

    if (! hash_equals((string) $_SESSION['installer_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.';
    }

    $force = isset($_POST['force_reconfigure']) && $_POST['force_reconfigure'] === '1';
    $runMigrations = isset($_POST['run_migrations']) && $_POST['run_migrations'] === '1';
    $disableInstaller = isset($_POST['disable_installer']) && $_POST['disable_installer'] === '1';
    $configFields = [
        'environment', 'base_url', 'db_host', 'db_name', 'db_user', 'db_password', 'db_port',
        'ecoe_host', 'ecoe_name', 'ecoe_user', 'ecoe_password', 'ecoe_port', 'ajax_cipher_key', 'totp_issuer',
    ];

    foreach ($configFields as $field) {
        if ($values[$field] !== $initialValues[$field]) {
            $configChanged = true;
            break;
        }
    }

    if (! in_array($values['environment'], ['development', 'production'], true)) {
        $errors[] = 'Selecciona un entorno válido.';
    }

    if (filter_var($values['base_url'], FILTER_VALIDATE_URL) === false
        || ! in_array((string) parse_url($values['base_url'], PHP_URL_SCHEME), ['http', 'https'], true)
        || ! str_ends_with($values['base_url'], '/')) {
        $errors[] = 'La URL base debe ser válida, usar http o https y terminar con /. ';
    }

    if ($values['db_name'] !== ''
        && (strlen($values['db_name']) > 64 || preg_match('/\A[A-Za-z0-9_$-]+\z/', $values['db_name']) !== 1)) {
        $errors[] = 'El nombre de la base debe tener hasta 64 caracteres y contener solo letras, números, guion, guion bajo o signo $. ';
    }

    foreach (['db_host', 'db_name', 'db_user', 'db_port', 'ajax_cipher_key', 'totp_issuer'] as $requiredField) {
        if ($values[$requiredField] === '') {
            $errors[] = 'Completa todos los campos obligatorios.';
            break;
        }
    }

    foreach ($values as $value) {
        if (str_contains($value, "\n") || str_contains($value, "\r") || str_contains($value, "\0")) {
            $errors[] = 'Los valores no pueden contener saltos de línea ni caracteres nulos.';
            break;
        }

        if (preg_match('/\$\{[a-zA-Z0-9_.]+\}/', $value) === 1) {
            $errors[] = 'No uses la secuencia ${...} en los valores; CI4 la interpreta como una variable de entorno.';
            break;
        }
    }

    if (! ctype_digit($values['db_port']) || (int) $values['db_port'] < 1 || (int) $values['db_port'] > 65535) {
        $errors[] = 'El puerto MySQL debe ser un número entre 1 y 65535.';
    }

    $cipherKey = str_starts_with($values['ajax_cipher_key'], 'base64:')
        ? substr($values['ajax_cipher_key'], 7)
        : $values['ajax_cipher_key'];
    $decodedCipherKey = base64_decode($cipherKey, true);

    if ($decodedCipherKey === false || strlen($decodedCipherKey) !== 32) {
        $errors[] = 'La clave AJAX debe ser Base64 y representar exactamente 32 bytes.';
    } else {
        $values['ajax_cipher_key'] = 'base64:' . base64_encode($decodedCipherKey);
    }

    $hasEcoeSettings = $values['ecoe_host'] !== ''
        || $values['ecoe_name'] !== ''
        || $values['ecoe_user'] !== ''
        || $values['ecoe_password'] !== ''
        || $values['ecoe_port'] !== '1433';

    if ($hasEcoeSettings && ($values['ecoe_host'] === '' || $values['ecoe_name'] === '' || $values['ecoe_user'] === '')) {
        $errors[] = 'Para ECOE completa hostname, database y username; la contraseña sí puede quedar vacía.';
    }

    if ($hasEcoeSettings && (! ctype_digit($values['ecoe_port']) || (int) $values['ecoe_port'] < 1 || (int) $values['ecoe_port'] > 65535)) {
        $errors[] = 'El puerto SQL Server debe ser un número entre 1 y 65535.';
    }

    if ($errors === []) {
        $shouldWriteEnv = ! $envExists || $force || $configChanged;
        $shouldRunMigrations = $shouldWriteEnv || $runMigrations;
        $envValues = [
            'CI_ENVIRONMENT'            => $values['environment'],
            'app.baseURL'               => $values['base_url'],
            'database.default.hostname' => $values['db_host'],
            'database.default.database' => $values['db_name'],
            'database.default.username' => $values['db_user'],
            'database.default.password' => $values['db_password'],
            'database.default.DBDriver' => 'MySQLi',
            'database.default.port'     => $values['db_port'],
            'security.ajaxCipherKey'    => $values['ajax_cipher_key'],
            'security.totpIssuer'       => $values['totp_issuer'],
        ];

        if ($hasEcoeSettings) {
            $envValues += [
                'database.ecoe.hostname' => $values['ecoe_host'],
                'database.ecoe.database' => $values['ecoe_name'],
                'database.ecoe.username' => $values['ecoe_user'],
                'database.ecoe.password' => $values['ecoe_password'],
                'database.ecoe.DBDriver' => 'SQLSRV',
                'database.ecoe.port'     => $values['ecoe_port'],
            ];
        }

        $envContents = '';

        if ($shouldWriteEnv) {
            $envContents = "# Configuracion generada por el instalador inicial.\n";

            foreach ($envValues as $name => $value) {
                $envContents .= $name . ' = ' . installerEnvValue($value) . "\n";
            }
        }

        try {
            if ($shouldRunMigrations) {
                [$databaseExisted, $connectionMessage] = installerTestMysql($values);
                $steps[] = [
                    'status' => 'success',
                    'title' => 'Conexión y base de datos principal',
                    'detail' => $connectionMessage . ' La base ' . ($databaseExisted ? 'ya existía.' : 'se creó automáticamente.'),
                ];
            }

            if ($shouldWriteEnv) {
                $backupPath = installerWriteEnv($envPath, $envContents, $force || $configChanged);
                $envExists = true;
                $envMessage = 'Archivo .env actualizado en la raíz del proyecto.';

                if ($backupPath !== null) {
                    $envMessage .= ' Respaldo: ' . basename($backupPath) . '.';
                }

                $steps[] = ['status' => 'success', 'title' => 'Configuración .env', 'detail' => $envMessage];
            } elseif (! $runMigrations) {
                $steps[] = [
                    'status' => 'info',
                    'title' => 'Sin cambios',
                    'detail' => 'No se modificó .env ni se solicitó la ejecución de migraciones.',
                ];
            }

            if ($shouldRunMigrations) {
                [$migrationsOk, $migrationMessage] = installerRunMigrations($projectRoot);
                $steps[] = [
                    'status' => $migrationsOk ? 'success' : 'error',
                    'title' => 'Migraciones de CodeIgniter',
                    'detail' => $migrationMessage,
                    'console' => true,
                ];

                if (! $migrationsOk) {
                    $errors[] = $shouldWriteEnv
                        ? 'La configuración quedó guardada, pero las migraciones requieren atención.'
                        : 'El archivo .env se conservó intacto, pero las migraciones requieren atención.';
                } elseif ($shouldWriteEnv) {
                    $steps[] = [
                        'status' => 'warning',
                        'title' => 'Cuenta administrativa inicial',
                        'detail' => 'La migración core conserva el usuario superadmin definido por el esquema original. Restablece su contraseña antes de exponer el sitio en producción.',
                    ];
                }
            }

                $hasStepErrors = count(array_filter($steps, static fn (array $step): bool => $step['status'] === 'error')) > 0;

                if ($disableInstaller) {
                    if ($errors === [] && ! $hasStepErrors) {
                        [$disabled, $disableMessage] = installerDisableSelf();
                        $steps[] = [
                            'status' => $disabled ? 'success' : 'warning',
                            'title' => 'Protección del instalador',
                            'detail' => $disableMessage,
                        ];

                        if (! $disabled) {
                            $errors[] = $disableMessage;
                        }
                    } else {
                        $steps[] = [
                            'status' => 'warning',
                            'title' => 'Protección del instalador',
                            'detail' => 'No se renombró porque hay pasos con errores; resuélvelos antes de deshabilitarlo.',
                        ];
                    }
                } else {
                    $steps[] = [
                        'status' => 'warning',
                        'title' => 'Protección del instalador',
                        'detail' => 'Cuando termines, usa la opción para renombrarlo o elimina public/auto_installer.php manualmente.',
                    ];
                }

            $values['db_password'] = '';
            $values['ecoe_password'] = '';
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
                $steps[] = ['status' => 'error', 'title' => 'Instalación', 'detail' => $exception->getMessage()];
        }
    }
}

$envExists = is_file($envPath);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Instalación inicial | Portal INDE</title>
    <style>
        :root { color-scheme: light; --ink: #172a35; --muted: #52636b; --line: #cbd5d7; --paper: #f3f6f3; --panel: #fff; --accent: #176b63; --danger: #8c2d25; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px 16px; background: var(--paper); color: var(--ink); font: 16px/1.5 system-ui, sans-serif; }
        main { width: min(860px, 100%); margin: 0 auto; }
        h1 { margin: 0 0 8px; font-size: 1.8rem; }
        h2 { margin: 28px 0 12px; padding-bottom: 8px; border-bottom: 1px solid var(--line); font-size: 1.15rem; }
        p { color: var(--muted); }
        .panel { padding: 24px; background: var(--panel); border: 1px solid var(--line); border-radius: 8px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        label { display: block; margin-bottom: 5px; font-weight: 650; }
        input, select { width: 100%; min-height: 42px; padding: 9px 11px; border: 1px solid #9caeb2; border-radius: 4px; background: #fff; color: var(--ink); font: inherit; }
        input:focus, select:focus { outline: 3px solid #9bd3c9; border-color: var(--accent); }
        .field { min-width: 0; }
        .span { grid-column: 1 / -1; }
        .notice { margin: 18px 0; padding: 13px 15px; border-left: 4px solid var(--accent); background: #e8f2ef; }
        .warning, .errors { border-color: var(--danger); background: #fbefed; }
        .warning { margin-bottom: 18px; padding: 13px 15px; border-left: 4px solid var(--danger); }
        .errors { color: var(--danger); }
        .messages { color: #145c35; border-color: #28794d; background: #eaf5ed; }
        .results { margin: 20px 0; padding: 20px; border: 1px solid var(--line); border-radius: 8px; background: var(--panel); }
        .results h2 { margin-top: 0; }
        .step-list { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
        .step { display: grid; grid-template-columns: 30px minmax(0, 1fr); gap: 10px; padding: 12px; border: 1px solid var(--line); border-radius: 5px; }
        .step-icon { display: grid; width: 26px; height: 26px; place-items: center; border-radius: 50%; background: #e8f2ef; color: var(--accent); font-weight: 800; }
        .step-warning .step-icon { background: #fff4dc; color: #805b00; }
        .step-error .step-icon { background: #fbefed; color: var(--danger); }
        .step-info .step-icon { background: #eef2f4; color: var(--muted); }
        .step-title { margin: 2px 0 5px; font-weight: 750; }
        .step-detail { margin: 0; color: var(--muted); overflow-wrap: anywhere; }
        .step-console { margin: 10px 0 0; padding: 12px; overflow: auto; border-radius: 4px; background: #172a35; color: #e8f2ef; font: 12px/1.55 Consolas, "Courier New", monospace; white-space: pre-wrap; overflow-wrap: anywhere; }
        .checkbox { display: flex; align-items: flex-start; gap: 10px; margin: 20px 0; }
        .checkbox input { width: 18px; min-height: 18px; margin-top: 4px; }
        button { min-height: 44px; padding: 10px 18px; border: 0; border-radius: 4px; background: var(--accent); color: white; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: #0f514b; }
        .small { font-size: .9rem; }
        @media (max-width: 620px) { body { padding: 16px 10px; } .panel { padding: 17px; } .grid { grid-template-columns: 1fr; } .span { grid-column: auto; } }
    </style>
</head>
<body>
<main>
    <h1>Instalación inicial</h1>
    <p>Configura el entorno y las conexiones para este proyecto CodeIgniter 4.</p>

    <?php if ($envExists): ?>
        <div class="warning" role="alert"><strong>El sistema ya parece instalado.</strong> Se encontró un archivo <code>.env</code> en la raíz. Los cambios en los campos actualizarán la configuración y crearán un respaldo. Si solo ejecutas migraciones, el archivo se conservará intacto.</div>
    <?php else: ?>
        <div class="notice">El archivo <code>.env</code> se creará en la raíz del repositorio, fuera de <code>public/</code>.</div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="notice errors" role="alert"><?= installerEscape($error) ?></div>
    <?php endforeach; ?>
    <?php if ($steps !== []): ?>
        <section class="results" aria-labelledby="results-title" aria-live="polite">
            <h2 id="results-title">Resultado del proceso</h2>
            <ol class="step-list">
                <?php foreach ($steps as $step): ?>
                    <?php
                    $status = $step['status'] ?? 'info';
                    $statusPresentation = [
                        'success' => ['icon' => '✓', 'label' => 'Éxito'],
                        'warning' => ['icon' => '!', 'label' => 'Aviso'],
                        'error' => ['icon' => '×', 'label' => 'Error'],
                        'info' => ['icon' => 'i', 'label' => 'Información'],
                    ][$status] ?? ['icon' => 'i', 'label' => 'Información'];
                    ?>
                    <li class="step step-<?= installerEscape($status) ?>">
                        <span class="step-icon" aria-label="<?= installerEscape($statusPresentation['label']) ?>"><?= installerEscape($statusPresentation['icon']) ?></span>
                        <div>
                            <p class="step-title"><?= installerEscape((string) ($step['title'] ?? 'Proceso')) ?></p>
                            <?php if (! empty($step['console'])): ?>
                                <pre class="step-console"><?= installerEscape((string) ($step['detail'] ?? '')) ?></pre>
                            <?php else: ?>
                                <p class="step-detail"><?= installerEscape((string) ($step['detail'] ?? '')) ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endif; ?>

    <form class="panel" method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= installerEscape((string) $_SESSION['installer_csrf']) ?>">

        <h2>Aplicación</h2>
        <div class="grid">
            <div class="field">
                <label for="environment">Entorno</label>
                <select id="environment" name="environment" required>
                    <option value="development"<?= $values['environment'] === 'development' ? ' selected' : '' ?>>Desarrollo</option>
                    <option value="production"<?= $values['environment'] === 'production' ? ' selected' : '' ?>>Producción</option>
                </select>
            </div>
            <div class="field">
                <label for="base_url">URL base</label>
                <input id="base_url" name="base_url" type="url" value="<?= installerEscape($values['base_url']) ?>" placeholder="https://ejemplo.com/" required>
            </div>
        </div>

        <h2>Base de datos principal MySQL/MariaDB</h2>
        <div class="grid">
            <div class="field"><label for="db_host">Hostname</label><input id="db_host" name="db_host" value="<?= installerEscape($values['db_host']) ?>" required></div>
            <div class="field"><label for="db_name">Base de datos</label><input id="db_name" name="db_name" value="<?= installerEscape($values['db_name']) ?>" required></div>
            <div class="field"><label for="db_user">Usuario</label><input id="db_user" name="db_user" value="<?= installerEscape($values['db_user']) ?>" required></div>
            <div class="field"><label for="db_password">Contraseña</label><input id="db_password" name="db_password" type="password" value="<?= installerEscape($values['db_password']) ?>" autocomplete="new-password"></div>
            <div class="field"><label for="db_port">Puerto</label><input id="db_port" name="db_port" type="number" min="1" max="65535" value="<?= installerEscape($values['db_port']) ?>" required></div>
        </div>

        <h2>Base externa ECOE SQL Server <span class="small">(opcional)</span></h2>
        <div class="grid">
            <div class="field"><label for="ecoe_host">Hostname</label><input id="ecoe_host" name="ecoe_host" value="<?= installerEscape($values['ecoe_host']) ?>"></div>
            <div class="field"><label for="ecoe_name">Base de datos</label><input id="ecoe_name" name="ecoe_name" value="<?= installerEscape($values['ecoe_name']) ?>"></div>
            <div class="field"><label for="ecoe_user">Usuario</label><input id="ecoe_user" name="ecoe_user" value="<?= installerEscape($values['ecoe_user']) ?>"></div>
            <div class="field"><label for="ecoe_password">Contraseña</label><input id="ecoe_password" name="ecoe_password" type="password" value="<?= installerEscape($values['ecoe_password']) ?>" autocomplete="new-password"></div>
            <div class="field"><label for="ecoe_port">Puerto SQL Server</label><input id="ecoe_port" name="ecoe_port" type="number" min="1" max="65535" value="<?= installerEscape($values['ecoe_port']) ?>"></div>
        </div>

        <h2>Seguridad</h2>
        <div class="grid">
            <div class="field span"><label for="ajax_cipher_key">Clave de cifrado AJAX (Base64, 32 bytes)</label><input id="ajax_cipher_key" name="ajax_cipher_key" value="<?= installerEscape($values['ajax_cipher_key']) ?>" required></div>
            <div class="field span"><label for="totp_issuer">Emisor TOTP</label><input id="totp_issuer" name="totp_issuer" value="<?= installerEscape($values['totp_issuer']) ?>" required></div>
        </div>

        <label class="checkbox" for="run_migrations">
            <input id="run_migrations" name="run_migrations" type="checkbox" value="1"<?= $runMigrations ? ' checked' : '' ?>>
            <span>Ejecutar migraciones pendientes. Al crear o actualizar el <code>.env</code> se ejecutan automáticamente.</span>
        </label>

        <label class="checkbox" for="disable_installer">
            <input id="disable_installer" name="disable_installer" type="checkbox" value="1"<?= $disableInstaller ? ' checked' : '' ?>>
            <span>Renombrar y deshabilitar este instalador si todos los pasos terminan sin errores.</span>
        </label>

        <?php if ($envExists): ?>
            <label class="checkbox" for="force_reconfigure">
                <input id="force_reconfigure" name="force_reconfigure" type="checkbox" value="1"<?= ($force || $configChanged) ? ' checked' : '' ?>>
                <span>Forzar reconfiguración y reemplazar el <code>.env</code> actual (se conservará una copia de respaldo).</span>
            </label>
        <?php endif; ?>

        <button type="submit">Guardar configuración</button>
    </form>

    <p class="small">Por seguridad, elimina <code>public/auto_installer.php</code> inmediatamente después de completar la instalación. El acceso web está restringido a localhost; para una instalación remota, modifica temporalmente <code>$allowedInstallerIps</code> en este archivo y limita el acceso desde el servidor web.</p>
</main>
</body>
</html>
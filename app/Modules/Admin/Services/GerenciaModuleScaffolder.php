<?php

namespace App\Modules\Admin\Services;

use RuntimeException;

class GerenciaModuleScaffolder
{
    public function moduleExists(string $slug): bool
    {
        $info = $this->resolveModuleInfo($slug);

        return is_dir($info['moduleDir']);
    }

    public function scaffold(string $slug, string $gerenciaNombre): array
    {
        $info = $this->resolveModuleInfo($slug);

        if (is_dir($info['moduleDir'])) {
            throw new RuntimeException('Ya existe un modulo para este slug.');
        }

        $this->ensureDirectory($info['moduleDir'] . DIRECTORY_SEPARATOR . 'Config');
        $this->ensureDirectory($info['moduleDir'] . DIRECTORY_SEPARATOR . 'Controllers');
        $this->ensureDirectory($info['moduleDir'] . DIRECTORY_SEPARATOR . 'Models');
        $this->ensureDirectory($info['moduleDir'] . DIRECTORY_SEPARATOR . 'Views');

        $this->writeFile(
            $info['moduleDir'] . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php',
            $this->routesTemplate($info['slug'], $info['namespaceSegment'])
        );

        $this->writeFile(
            $info['moduleDir'] . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'DashboardController.php',
            $this->dashboardControllerTemplate($info['namespaceSegment'], $info['slug'])
        );

        $this->writeFile(
            $info['moduleDir'] . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'ModuleController.php',
            $this->moduleControllerTemplate($info['namespaceSegment'], $info['slug'])
        );

        $this->writeFile(
            $info['moduleDir'] . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'BaseModuleModel.php',
            $this->modelTemplate($info['namespaceSegment'])
        );

        $this->writeFile(
            $info['moduleDir'] . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'dashboard.php',
            $this->dashboardViewTemplate($gerenciaNombre)
        );

        $this->writeFile(
            $info['moduleDir'] . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'module.php',
            $this->moduleViewTemplate($gerenciaNombre)
        );

        return $info;
    }

    public function resolveModuleInfo(string $slug): array
    {
        $slug = strtolower(trim($slug));
        $namespaceSegment = $this->toNamespaceSegment($slug);

        return [
            'slug' => $slug,
            'namespaceSegment' => $namespaceSegment,
            'moduleDir' => APPPATH . 'Modules' . DIRECTORY_SEPARATOR . $namespaceSegment,
        ];
    }

    private function toNamespaceSegment(string $slug): string
    {
        $parts = preg_split('/[_\-]+/', $slug) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $clean = preg_replace('/[^a-z0-9]/', '', strtolower($part));

            if ($clean === null || $clean === '') {
                continue;
            }

            $normalized[] = ucfirst($clean);
        }

        $segment = implode('', $normalized);

        if ($segment === '') {
            $segment = 'Gerencia';
        }

        if (preg_match('/^[0-9]/', $segment) === 1) {
            $segment = 'G' . $segment;
        }

        if (in_array(strtolower($segment), ['admin', 'auth'], true)) {
            $segment .= 'Module';
        }

        return $segment;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('No fue posible crear el directorio: ' . $directory);
        }
    }

    private function writeFile(string $path, string $content): void
    {
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('No fue posible escribir el archivo: ' . $path);
        }
    }

    private function routesTemplate(string $slug, string $namespaceSegment): string
    {
        return "<?php\n\n"
            . "\$routes->group('gerencias/{$slug}', [\n"
            . "    'namespace' => 'App\\Modules\\{$namespaceSegment}\\Controllers',\n"
            . "], static function (\$routes) {\n"
            . "    \$routes->get('/', 'DashboardController::index', ['filter' => 'gerenciaAccess:{$slug},gerencia.{$slug}.dashboard.access']);\n"
            . "    \$routes->get('dashboard', 'DashboardController::index', ['filter' => 'gerenciaAccess:{$slug},gerencia.{$slug}.dashboard.access']);\n"
            . "    \$routes->get('modulo/(:segment)', 'ModuleController::index/\$1', ['filter' => 'gerenciaAccess:{$slug},gerencia.{$slug}.modulo.access']);\n"
            . "});\n";
    }

    private function dashboardControllerTemplate(string $namespaceSegment, string $slug): string
    {
        return "<?php\n\n"
            . "namespace App\\Modules\\{$namespaceSegment}\\Controllers;\n\n"
            . "use App\\Modules\\GerenciaBaseController;\n\n"
            . "class DashboardController extends GerenciaBaseController\n"
            . "{\n"
            . "    protected string \$moduleSlug = '{$slug}';\n\n"
            . "    public function index(): string\n"
            . "    {\n"
            . "        return \$this->renderModulePage('App\\\\Modules\\\\{$namespaceSegment}\\\\Views\\\\dashboard', [\n"
            . "            'moduleSlug' => \$this->moduleSlug,\n"
            . "        ], 'Dashboard de ' . strtoupper(\$this->moduleSlug));\n"
            . "    }\n"
            . "}\n";
    }

    private function moduleControllerTemplate(string $namespaceSegment, string $slug): string
    {
        return "<?php\n\n"
            . "namespace App\\Modules\\{$namespaceSegment}\\Controllers;\n\n"
            . "use App\\Modules\\GerenciaBaseController;\n\n"
            . "class ModuleController extends GerenciaBaseController\n"
            . "{\n"
            . "    protected string \$moduleSlug = '{$slug}';\n\n"
            . "    public function index(string \$section = 'base'): string\n"
            . "    {\n"
            . "        \$section = strtolower(trim(\$section)) ?: 'base';\n\n"
            . "        return \$this->renderModulePage('App\\\\Modules\\\\{$namespaceSegment}\\\\Views\\\\module', [\n"
            . "            'moduleSlug' => \$this->moduleSlug,\n"
            . "            'section' => \$section,\n"
            . "        ], 'Modulo ' . strtoupper(\$section) . ' - ' . strtoupper(\$this->moduleSlug));\n"
            . "    }\n"
            . "}\n";
    }

    private function modelTemplate(string $namespaceSegment): string
    {
        return "<?php\n\n"
            . "namespace App\\Modules\\{$namespaceSegment}\\Models;\n\n"
            . "use CodeIgniter\\Model;\n\n"
            . "class BaseModuleModel extends Model\n"
            . "{\n"
            . "    protected \$table = '';\n"
            . "    protected \$primaryKey = 'id';\n"
            . "    protected \$returnType = 'array';\n"
            . "    protected \$allowedFields = [];\n"
            . "    protected \$useTimestamps = true;\n"
            . "    protected \$createdField = 'created_at';\n"
            . "    protected \$updatedField = 'updated_at';\n"
            . "}\n";
    }

    private function dashboardViewTemplate(string $gerenciaNombre): string
    {
        $safeTitle = htmlspecialchars($gerenciaNombre, ENT_QUOTES, 'UTF-8');

        return "<section class=\"p-4 p-lg-5 mb-4 rounded-4 text-white\" style=\"background:linear-gradient(135deg,#0f4c81 0%,#1c7c54 100%);\">\n"
            . "    <p class=\"text-uppercase small mb-2 opacity-75\">Modulo de gerencia</p>\n"
            . "    <h1 class=\"display-6 mb-3\">{$safeTitle}</h1>\n"
            . "    <p class=\"mb-0\">Plantilla generada automaticamente. Ya usa menu lateral, encabezado, pie y seguridad por permisos.</p>\n"
            . "</section>\n"
            . "<div class=\"card border-0 shadow-sm\">\n"
            . "    <div class=\"card-body\">\n"
            . "        <p class=\"mb-2\">Usa esta pantalla como dashboard inicial y agrega modulos funcionales en rutas <strong>/gerencias/slug/modulo/*</strong>.</p>\n"
            . "        <a class=\"btn btn-outline-primary\" href=\"<?= site_url('gerencias/' . (\$auth['gerencia_slug'] ?? '')) ?>/modulo/base\">Abrir modulo base</a>\n"
            . "    </div>\n"
            . "</div>\n";
    }

    private function moduleViewTemplate(string $gerenciaNombre): string
    {
        $safeTitle = htmlspecialchars($gerenciaNombre, ENT_QUOTES, 'UTF-8');

        return "<div class=\"card border-0 shadow-sm\">\n"
            . "    <div class=\"card-body p-4\">\n"
            . "        <h1 class=\"h4 mb-3\">{$safeTitle} - Modulo <?= esc(\$section ?? 'base') ?></h1>\n"
            . "        <p class=\"mb-0\">Este es el espacio de trabajo para construir CRUDs, servicios y vistas del submodulo seleccionado.</p>\n"
            . "    </div>\n"
            . "</div>\n";
    }
}

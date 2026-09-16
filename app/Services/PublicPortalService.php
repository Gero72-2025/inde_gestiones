<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class PublicPortalService
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function getGerencias(string $locale): array
    {
        if (! $this->db->tableExists('gerencias')) {
            return [];
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('gerencias'));
        $select = ['id', 'slug', 'nombre'];

        foreach (['nombre_en', 'nombre_quc', 'nombre_qeq', 'nombre_cak'] as $localizedField) {
            if (in_array($localizedField, $fields, true)) {
                $select[] = $localizedField;
            }
        }

        $rows = $this->db->table('gerencias')
            ->select(implode(', ', $select))
            ->where('status', 'active')
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();

        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) ($row['id'] ?? 0),
                'slug' => (string) ($row['slug'] ?? ''),
                'nombre' => $this->localizedValue($row, 'nombre', $locale),
            ];
        }

        return $result;
    }

    public function findBeneficioByDpi(string $dpi, string $locale): ?array
    {
        if (! $this->db->tableExists('public_beneficiarios')) {
            return null;
        }

        $row = $this->db->table('public_beneficiarios')
            ->select('estado_beneficio, estado_beneficio_en, estado_beneficio_quc, estado_beneficio_qeq, estado_beneficio_cak')
            ->where('dpi_hash', hash('sha256', $dpi))
            ->limit(1)
            ->get()
            ->getRowArray();

        if (! is_array($row)) {
            return null;
        }

        return [
            'estado_beneficio' => $this->localizedValue($row, 'estado_beneficio', $locale),
        ];
    }

    public function searchElectrificacion(string $comunidad, string $locale): array
    {
        if (! $this->db->tableExists('public_electrificacion')) {
            return [];
        }

        $builder = $this->db->table('public_electrificacion')
            ->select('id, comunidad, comunidad_en, comunidad_quc, comunidad_qeq, comunidad_cak, fase_actual, fase_actual_en, fase_actual_quc, fase_actual_qeq, fase_actual_cak, departamento, municipio')
            ->groupStart()
            ->like('comunidad', $comunidad)
            ->orLike('comunidad_en', $comunidad)
            ->orLike('comunidad_quc', $comunidad)
            ->orLike('comunidad_qeq', $comunidad)
            ->orLike('comunidad_cak', $comunidad)
            ->groupEnd()
            ->orderBy('comunidad', 'ASC')
            ->limit(25);

        $rows = $builder->get()->getResultArray();
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) ($row['id'] ?? 0),
                'comunidad' => $this->localizedValue($row, 'comunidad', $locale),
                'fase_actual' => $this->localizedValue($row, 'fase_actual', $locale),
                'departamento' => (string) ($row['departamento'] ?? ''),
                'municipio' => (string) ($row['municipio'] ?? ''),
            ];
        }

        return $result;
    }

    public function getDepartamentos(): array
    {
        if (! $this->db->tableExists('cat_departamentos')) {
            return [];
        }

        return $this->db->table('cat_departamentos')
            ->select('id, nombre')
            ->where('status', 'active')
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getMunicipios(int $departamentoId = 0): array
    {
        if (! $this->db->tableExists('cat_municipios')) {
            return [];
        }

        $builder = $this->db->table('cat_municipios')
            ->select('id, departamento_id, nombre')
            ->where('status', 'active')
            ->orderBy('nombre', 'ASC');

        if ($departamentoId > 0) {
            $builder->where('departamento_id', $departamentoId);
        }

        return $builder->get()->getResultArray();
    }

    public function listCortes(string $locale, int $departamentoId = 0, int $municipioId = 0): array
    {
        if (! $this->db->tableExists('etcee_cortes') || ! $this->db->tableExists('etcee_cortes_ubicaciones')) {
            return [];
        }

        $hasDepartamentos = $this->db->tableExists('cat_departamentos');
        $hasMunicipios = $this->db->tableExists('cat_municipios');

        $builder = $this->db->table('etcee_cortes c')
            ->join('etcee_cortes_ubicaciones cu', 'cu.corte_id = c.id', 'inner')
            ->limit(300);

        if ($hasDepartamentos) {
            $builder->join('cat_departamentos d', 'd.id = cu.departamento_id', 'left');
        }

        if ($hasMunicipios) {
            $builder->join('cat_municipios m', 'm.id = cu.municipio_id', 'left');
        }

        $builder->select(
            'c.id, c.titulo AS motivo, c.descripcion, c.estado, c.fecha_inicio, c.fecha_fin'
            . ($hasDepartamentos ? ', d.nombre AS departamento' : ', cu.departamento_id AS departamento')
            . ($hasMunicipios ? ', m.nombre AS municipio' : ', cu.municipio_id AS municipio')
        );

        if ($departamentoId > 0) {
            $builder->where('cu.departamento_id', $departamentoId);
        }

        if ($municipioId > 0) {
            $builder->where('cu.municipio_id', $municipioId);
        }

        $builder->orderBy($hasDepartamentos ? 'd.nombre' : 'cu.departamento_id', 'ASC');
        $builder->orderBy($hasMunicipios ? 'm.nombre' : 'cu.municipio_id', 'ASC');
        $builder->orderBy('c.fecha_inicio', 'ASC');

        $rows = $builder->get()->getResultArray();
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) ($row['id'] ?? 0),
                'departamento' => (string) ($row['departamento'] ?? ''),
                'municipio' => (string) ($row['municipio'] ?? ''),
                'motivo' => (string) ($row['motivo'] ?? ''),
                'descripcion' => (string) ($row['descripcion'] ?? ''),
                'estado' => (string) ($row['estado'] ?? ''),
                'fecha_inicio' => (string) ($row['fecha_inicio'] ?? ''),
                'fecha_fin' => (string) ($row['fecha_fin'] ?? ''),
            ];
        }

        return $result;
    }

    public function listSniGeometrias(string $locale): array
    {
        if (! $this->db->tableExists('etcee_sni_geometrias') || ! $this->db->tableExists('etcee_sni_capas')) {
            return [
                'type' => 'FeatureCollection',
                'features' => [],
            ];
        }

        $rows = $this->db->table('etcee_sni_geometrias g')
            ->select('g.id, g.nombre, g.coordenadas, g.propiedades, g.fecha_registro, c.nombre AS capa_nombre, c.slug AS capa_slug, c.color_default, c.icono_path')
            ->join('etcee_sni_capas c', 'c.id = g.categoria_id', 'inner')
            ->where('c.estado', 'activo')
            ->orderBy('g.fecha_registro', 'DESC')
            ->limit(5000)
            ->get()
            ->getResultArray();

        $features = [];

        foreach ($rows as $row) {
            $geometry = json_decode((string) ($row['coordenadas'] ?? '{}'), true);

            if (! is_array($geometry) || ($geometry['type'] ?? '') === '') {
                continue;
            }

            $properties = json_decode((string) ($row['propiedades'] ?? '{}'), true);
            $properties = is_array($properties) ? $properties : [];

            $properties['id'] = (int) ($row['id'] ?? 0);
            $properties['nombre'] = (string) ($row['nombre'] ?? '');
            $properties['capa_nombre'] = (string) ($row['capa_nombre'] ?? '');
            $properties['capa_slug'] = (string) ($row['capa_slug'] ?? '');
            $properties['color_default'] = (string) ($row['color_default'] ?? '#1f6feb');
            $properties['icono_path'] = (string) ($row['icono_path'] ?? '');
            $properties['icono_url'] = trim((string) ($row['icono_path'] ?? '')) !== ''
                ? base_url((string) $row['icono_path'])
                : '';
            $properties['fecha_registro'] = (string) ($row['fecha_registro'] ?? '');
            $properties['locale'] = $locale;

            $features[] = [
                'type' => 'Feature',
                'geometry' => $geometry,
                'properties' => $properties,
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    public function listSniSimbologias(array $allowedSlugs = []): array
    {
        if (! $this->db->tableExists('etcee_sni_capas')) {
            return [];
        }

        $builder = $this->db->table('etcee_sni_capas')
            ->select('id, nombre, slug, estado, color_default, icono_path')
            ->where('estado', 'activo');

        $normalizedSlugs = array_values(array_unique(array_filter(array_map(
            static fn ($slug): string => trim((string) $slug),
            $allowedSlugs
        ), static fn (string $slug): bool => $slug !== '')));

        if ($normalizedSlugs !== []) {
            $builder->whereIn('slug', $normalizedSlugs);
        } else {
            return [];
        }

        $rows = $builder
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();

        $result = [];

        foreach ($rows as $row) {
            $iconPath = trim((string) ($row['icono_path'] ?? ''));

            $result[] = [
                'id' => (int) ($row['id'] ?? 0),
                'nombre' => (string) ($row['nombre'] ?? ''),
                'slug' => (string) ($row['slug'] ?? ''),
                'estado' => (string) ($row['estado'] ?? 'activo'),
                'color_default' => (string) ($row['color_default'] ?? '#1f6feb'),
                'icono_path' => $iconPath,
                'icono_url' => $iconPath !== '' ? base_url($iconPath) : '',
            ];
        }

        return $result;
    }

    public function getPublicMenuItems(string $locale): array
    {
        if (! $this->db->tableExists('public_menu_items')) {
            return [
                [
                    'id' => 0,
                    'parent_id' => null,
                    'is_dropdown' => 0,
                    'title' => lang('Portal.navBeneficiados', [], $locale),
                    'description' => lang('Portal.beneficiadosHelp', [], $locale),
                    'route_path' => 'consulta/beneficiados',
                    'icon_class' => 'bi-person-check',
                    'background_image_path' => null,
                    'page_key' => 'beneficiados',
                    'children' => [],
                ],
                [
                    'id' => 0,
                    'parent_id' => null,
                    'is_dropdown' => 0,
                    'title' => lang('Portal.navCortes', [], $locale),
                    'description' => lang('Portal.cortesHelp', [], $locale),
                    'route_path' => 'consulta/cortes',
                    'icon_class' => 'bi-plug',
                    'background_image_path' => null,
                    'page_key' => 'cortes',
                    'children' => [],
                ],
                [
                    'id' => 0,
                    'parent_id' => null,
                    'is_dropdown' => 0,
                    'title' => lang('Portal.navSni', [], $locale),
                    'description' => lang('Portal.sniHelp', [], $locale),
                    'route_path' => 'consulta/sni',
                    'icon_class' => 'bi-diagram-3',
                    'background_image_path' => null,
                    'page_key' => 'sni',
                    'children' => [],
                ],
            ];
        }

        $fields = array_map('strtolower', $this->db->getFieldNames('public_menu_items'));
        $select = [
            'id',
            'title',
            'title_en',
            'title_quc',
            'title_qeq',
            'title_cak',
            'description',
            'description_en',
            'description_quc',
            'description_qeq',
            'description_cak',
            'route_path',
            'icon_class',
            'sort_order',
            'is_published',
        ];

        if (in_array('background_image_path', $fields, true)) {
            $select[] = 'background_image_path';
        }

        if (in_array('show_header', $fields, true)) {
            $select[] = 'show_header';
        }

        if (in_array('footer_background_image_path', $fields, true)) {
            $select[] = 'footer_background_image_path';
        }

        if (in_array('show_footer', $fields, true)) {
            $select[] = 'show_footer';
        }

        if (in_array('parent_id', $fields, true)) {
            $select[] = 'parent_id';
        }

        if (in_array('is_dropdown', $fields, true)) {
            $select[] = 'is_dropdown';
        }

        $rows = $this->db->table('public_menu_items')
            ->select(implode(', ', $select))
            ->where('is_published', 1)
            ->orderBy('is_dropdown', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $itemsById = [];
        $orderedIds = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $routePath = ltrim(trim((string) ($row['route_path'] ?? '')), '/');
            $isDropdown = (int) ($row['is_dropdown'] ?? 0) === 1 ? 1 : 0;
            $parentId = max((int) ($row['parent_id'] ?? 0), 0);

            if ($isDropdown === 0 && $routePath === '') {
                continue;
            }

            $itemsById[$id] = [
                'id' => $id,
                'parent_id' => $parentId > 0 ? $parentId : null,
                'is_dropdown' => $isDropdown,
                'title' => $this->localizedValue($row, 'title', $locale),
                'description' => $this->localizedValue($row, 'description', $locale),
                'route_path' => $routePath,
                'icon_class' => trim((string) ($row['icon_class'] ?? 'bi-grid')) ?: 'bi-grid',
                'background_image_path' => trim((string) ($row['background_image_path'] ?? '')) ?: null,
                'show_header' => ! array_key_exists('show_header', $row) || (int) $row['show_header'] !== 0,
                'footer_background_image_path' => trim((string) ($row['footer_background_image_path'] ?? '')) ?: null,
                'show_footer' => ! array_key_exists('show_footer', $row) || (int) $row['show_footer'] !== 0,
                'page_key' => $this->inferPageKey($routePath),
                'children' => [],
            ];

            $orderedIds[] = $id;
        }

        $rootIds = [];

        foreach ($orderedIds as $id) {
            if (! isset($itemsById[$id])) {
                continue;
            }

            $parentId = (int) ($itemsById[$id]['parent_id'] ?? 0);

            if (
                $parentId > 0
                && isset($itemsById[$parentId])
                && (int) ($itemsById[$parentId]['is_dropdown'] ?? 0) === 1
            ) {
                $itemsById[$parentId]['children'][] = $itemsById[$id];
                continue;
            }

            $rootIds[] = $id;
        }

        $result = [];

        foreach ($rootIds as $rootId) {
            if (! isset($itemsById[$rootId])) {
                continue;
            }

            $result[] = $itemsById[$rootId];
        }

        return $result;
    }

    private function inferPageKey(string $routePath): string
    {
        $routePath = strtolower(trim($routePath, '/'));

        return match ($routePath) {
            'consulta/beneficiados' => 'beneficiados',
            'consulta/cortes' => 'cortes',
            'consulta/sni' => 'sni',
            default => trim((string) basename($routePath)) ?: 'home',
        };
    }

    private function localizedValue(array $row, string $baseField, string $locale): string
    {
        $locale = strtolower($locale);

        if ($locale !== 'es') {
            $localizedField = $baseField . '_' . $locale;

            if (array_key_exists($localizedField, $row) && trim((string) $row[$localizedField]) !== '') {
                return (string) $row[$localizedField];
            }
        }

        return (string) ($row[$baseField] ?? '');
    }
}
<?php

namespace App\Controllers;

use App\Services\PublicPortalService;
use App\Modules\Ecoe\Models\DistribuidoraModel;
use App\Modules\Ecoe\Models\EemListadoModel;
use App\Modules\Ecoe\Models\FormularioCampoModel;
use App\Modules\Ecoe\Models\FormularioModel;
use App\Modules\Gero\Models\ComunidadesModel;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;

class PublicPortalController extends BaseController
{
    private PublicPortalService $portalService;

    private const LOCALE_LABELS = [
        'es' => 'Espanol',
        'en' => 'English',
        'quc' => "K'iche'",
        'qeq' => "Q'eqchi'",
        'cak' => 'Kaqchikel',
    ];

    public function initController($request, $response, $logger)
    {
        parent::initController($request, $response, $logger);
        $this->portalService = new PublicPortalService();
    }

    public function index(): string
    {
        return $this->renderPublicPage('home', 'public/pages/home', 'public/pages/empty_script');
    }

    public function beneficiadosPage(): string
    {
        return $this->renderPublicPage('beneficiados', 'public/pages/beneficiados', 'public/pages/beneficiados_script');
    }

    public function comunidadesPage(): string
    {
        return $this->renderPublicPage('comunidades', 'public/pages/comunidades', 'public/pages/comunidades_script');
    }

    public function cortesPage(): string
    {
        return $this->renderPublicPage('cortes', 'public/pages/cortes', 'public/pages/cortes_script');
    }

    public function sniMap(): string
    {
        return $this->renderPublicPage('sni', 'public/pages/sni_public', 'public/pages/sni_public_script');
    }

    public function tarifaSocialPage(): string
    {
        $data            = $this->pageData('tarifa-social');
        $distribuidoraM  = new DistribuidoraModel();

        return view('public/layout', array_merge($data, [
            'pageTitle'      => 'Aporte a la Tarifa Social | ' . lang('Portal.metaTitle', [], $data['currentLocale']),
            'heroTitle'      => 'Aporte a la Tarifa Social',
            'heroLead'       => 'Consulta si recibes aporte a la tarifa social.',
            'innerView'      => 'public/pages/tarifa_social',
            'innerData'      => array_merge($data, [
                'distribuidoras' => $distribuidoraM->listActivas(),
            ]),
            'scriptsView'    => 'public/pages/empty_script',
        ]));
    }

    public function guPage(): string
    {
        return $this->renderEcoeFormsPage('gu', 'Grandes Usuarios', 'Consulta y registra solicitudes de Oferta de Suministro o Quejas / Comentarios.', [
            'GU1',
            'GU2',
        ], 'public/gu_index');
    }

    public function eemPage(): string
    {
        return $this->renderEcoeFormsPage('eem', 'Empresa Eléctrica Municipal', 'Selecciona una empresa y registra el trámite que necesitas.', [
            'EEM1',
            'EEM2',
            'EEM3',
        ], 'public/eem_index');
    }

    private function renderPublicPage(string $page, string $innerView, string $scriptsView): string
    {
        $data = $this->pageData($page);

        $heroLead = match ($page) {
            'beneficiados'   => lang('Portal.beneficiadosHelp', [], $data['currentLocale']),
            'cortes'         => lang('Portal.cortesHelp', [], $data['currentLocale']),
            'sni'            => lang('Portal.sniHelp', [], $data['currentLocale']),
            'tarifa_social', 'tarifa-social'  => 'Consulta si recibes aporte a la tarifa social.',
            default          => lang('Portal.demoSplitNotice', [], $data['currentLocale']),
        };

        $heroTitle = match ($page) {
            'beneficiados'   => lang('Portal.beneficiadosTitle', [], $data['currentLocale']),
            'cortes'         => lang('Portal.cortesTitle', [], $data['currentLocale']),
            'sni'            => lang('Portal.sniTitle', [], $data['currentLocale']),
            'tarifa_social', 'tarifa-social'  => 'Aporte a la Tarifa Social',
            default          => lang('Portal.heroTitle', [], $data['currentLocale']),
        };

        $title = match ($page) {
            'beneficiados'   => lang('Portal.beneficiadosTitle', [], $data['currentLocale']) . ' | ' . lang('Portal.metaTitle', [], $data['currentLocale']),
            'cortes'         => lang('Portal.cortesTitle', [], $data['currentLocale']) . ' | ' . lang('Portal.metaTitle', [], $data['currentLocale']),
            'sni'            => lang('Portal.sniTitle', [], $data['currentLocale']) . ' | ' . lang('Portal.metaTitle', [], $data['currentLocale']),
            'tarifa_social', 'tarifa-social'  => 'Aporte a la Tarifa Social | ' . lang('Portal.metaTitle', [], $data['currentLocale']),
            default          => lang('Portal.metaTitle', [], $data['currentLocale']),
        };

        return view('public/layout', array_merge($data, [
            'pageTitle' => $title,
            'heroTitle' => $heroTitle,
            'heroLead' => $heroLead,
            'innerView' => $innerView,
            'innerData' => $data,
            'scriptsView' => $scriptsView,
        ]));
    }

    private function renderEcoeFormsPage(string $page, string $heroTitle, string $heroLead, array $codes, string $innerView): string
    {
        $data = $this->pageData($page);
        $formularioModel = new FormularioModel();
        $campoModel = new FormularioCampoModel();
        $eemModel = new EemListadoModel();
        $formularios = [];
        $camposPorFormulario = [];

        foreach ($codes as $codigo) {
            $formulario = $formularioModel->findByCodigo($codigo);

            if (! $formulario || (int) ($formulario['estado'] ?? 0) !== 1) {
                continue;
            }

            $formularios[] = $formulario;
            $camposPorFormulario[(string) $formulario['codigo']] = $campoModel->getActiveVisibleFields((int) $formulario['id']);
        }

        return view('public/layout', array_merge($data, [
            'pageTitle' => $heroTitle . ' | ' . lang('Portal.metaTitle', [], $data['currentLocale']),
            'heroTitle' => $heroTitle,
            'heroLead' => $heroLead,
            'innerView' => $innerView,
            'innerData' => array_merge($data, [
                'formularios' => $formularios,
                'camposPorFormulario' => $camposPorFormulario,
                'eemListados' => $eemModel->listActivas(),
            ]),
            'scriptsView' => 'public/pages/empty_script',
        ]));
    }

    private function pageData(string $page): array
    {
        $locale = $this->resolveLocale();

        return [
            'page' => $page,
            'currentLocale' => $locale,
            'localeOptions' => $this->buildLocaleOptions(),
            'gerencias' => $this->portalService->getGerencias($locale),
            'publicMenuItems' => $this->portalService->getPublicMenuItems($locale),
            'captcha' => $this->issueCaptchaChallenge(),
            'ajaxCipherKey' => (string) env('security.ajaxCipherKey', ''),
            'whatsappBySection' => [
                'hero' => (string) env('portal.whatsappGeneral', '50255550001'),
                'beneficiados' => (string) env('portal.whatsappTarifaSocial', '50255550011'),
                'tarifa-social' => (string) env('portal.whatsappTarifaSocial', '50255550011'),
                'tarifa_social' => (string) env('portal.whatsappTarifaSocial', '50255550011'),
                'cortes' => (string) env('portal.whatsappCortes', '50255550033'),
                'sni' => (string) env('portal.whatsappSni', '50255550044'),
            ],
        ];
    }

    public function changeLocale(string $locale)
    {
        $supported = config('App')->supportedLocales;
        $locale = strtolower(trim($locale));

        if (! in_array($locale, $supported, true)) {
            $locale = config('App')->defaultLocale;
        }

        $this->session->set('site_locale', $locale);

        return redirect()->back();
    }

    public function captcha(): ResponseInterface
    {
        return $this->response->setJSON([
            'ok' => true,
            'data' => $this->issueCaptchaChallenge(),
        ]);
    }

    public function beneficiado(): ResponseInterface
    {
        $locale = $this->resolveLocale();

        try {
            $payload = $this->decryptAjaxEnvelopeFromRequest(true);
        } catch (InvalidArgumentException $exception) {
            return $this->encryptedJsonResponse([
                'ok' => false,
                'message' => lang('Portal.invalidEncryptedRequest', [], $locale),
            ], 400);
        }

        $dpi = preg_replace('/\D+/', '', (string) ($payload['dpi'] ?? ''));
        $captchaToken = trim((string) ($payload['captcha_token'] ?? ''));
        $captchaAnswer = preg_replace('/\D+/', '', (string) ($payload['captcha_answer'] ?? ''));

        if (! $this->validateCaptcha($captchaToken, $captchaAnswer)) {
            return $this->encryptedJsonResponse([
                'ok' => false,
                'message' => lang('Portal.invalidCaptcha', [], $locale),
                'captcha' => $this->issueCaptchaChallenge(),
            ], 422);
        }

        if (strlen($dpi) !== 13) {
            return $this->encryptedJsonResponse([
                'ok' => false,
                'message' => lang('Portal.invalidDpi', [], $locale),
            ], 422);
        }

        $beneficio = $this->portalService->findBeneficioByDpi($dpi, $locale);

        if ($beneficio === null) {
            return $this->encryptedJsonResponse([
                'ok' => true,
                'found' => false,
                'message' => lang('Portal.dpiGenericNotFound', [], $locale),
            ]);
        }

        return $this->encryptedJsonResponse([
            'ok' => true,
            'found' => true,
            'message' => lang('Portal.dpiFoundMessage', [], $locale),
            'benefit_status' => $beneficio['estado_beneficio'],
        ]);
    }

    public function consultarComunidadesGero(): ResponseInterface
    {
        try {
            $payload = $this->decryptAjaxEnvelopeFromRequest(true);
        } catch (InvalidArgumentException $exception) {
            return $this->encryptedJsonResponse([
                'ok' => false,
                'data' => [
                    'message' => 'Solicitud cifrada invalida.',
                ],
            ], 400);
        }

        $term = mb_substr(trim((string) ($payload['term'] ?? '')), 0, 120);

        if ($term === '') {
            return $this->encryptedJsonResponse([
                'ok' => false,
                'data' => [
                    'message' => 'Debes ingresar un codigo o nombre de comunidad.',
                ],
            ], 422);
        }

        $model = new ComunidadesModel();
        $items = $model->findByCodeOrName($term);
        $mappingsByDivision = $this->loadLatestMappingsByDivision();

        foreach ($items as &$item) {
            $item = $this->decorateComunidadForLanding($item, $mappingsByDivision);
        }
        unset($item);

        return $this->encryptedJsonResponse([
            'ok' => true,
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    private function loadLatestMappingsByDivision(): array
    {
        $db = db_connect();
        if (! $db->tableExists('gero_comunidades_fuentes') || ! $db->tableExists('gero_comunidades_fuente_mapeo')) {
            return [];
        }

        $latestByDivision = $db->table('gero_comunidades_fuentes')
            ->select('MAX(id) AS id, division')
            ->groupBy('division')
            ->get()
            ->getResultArray();

        if (! is_array($latestByDivision) || $latestByDivision === []) {
            return [];
        }

        $map = [];
        foreach ($latestByDivision as $row) {
            $sourceId = (int) ($row['id'] ?? 0);
            $division = strtoupper(trim((string) ($row['division'] ?? '')));
            if ($sourceId <= 0 || $division === '') {
                continue;
            }

            $mappings = $db->table('gero_comunidades_fuente_mapeo')
                ->select('columna_normalizada, etiqueta_landing, mostrar_landing, fase_clave, usar_para_hito, mostrar_card')
                ->where('fuente_id', $sourceId)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            $map[$division] = is_array($mappings) ? $mappings : [];
        }

        return $map;
    }

    private function decorateComunidadForLanding(array $item, array $mappingsByDivision): array
    {
        $division = strtoupper(trim((string) ($item['division_origen'] ?? '')));
        $mappings = $mappingsByDivision[$division] ?? [];
        $additional = json_decode((string) ($item['campos_adicionales_json'] ?? '{}'), true);
        $additional = is_array($additional) ? $additional : [];

        $baseFieldMap = [
            'codigo_comunidad' => (string) ($item['codigo_comunidad'] ?? ''),
            'numero_snip' => (string) ($item['numero_snip'] ?? ''),
            'nombre_comunidad' => (string) ($item['nombre_comunidad'] ?? ''),
            'municipio' => (string) ($item['municipio'] ?? ''),
            'departamento' => (string) ($item['departamento'] ?? ''),
            'fase_actual' => (string) ($item['fase_actual'] ?? ''),
            'estado_actual' => (string) ($item['estado_actual'] ?? ''),
            'solicitud_firmada' => ((int) ($item['solicitud_firmada'] ?? 0) === 1) ? 'Si' : 'No',
            'estudio_socioeconomico' => ((int) ($item['estudio_socioeconomico'] ?? 0) === 1) ? 'Si' : 'No',
            'snip_aprobado' => ((int) ($item['snip_aprobado'] ?? 0) === 1) ? 'Si' : 'No',
            'licitacion_terminada' => ((int) ($item['licitacion_terminada'] ?? 0) === 1) ? 'Si' : 'No',
            'obra_energizada' => ((int) ($item['obra_energizada'] ?? 0) === 1) ? 'Si' : 'No',
        ];

        $mappedFields = [];
        $milestones = [];
        $cards = ['fase_1' => [], 'fase_2' => [], 'fase_3' => []];

        foreach ($mappings as $mapping) {
            $column = trim((string) ($mapping['columna_normalizada'] ?? ''));
            if ($column === '') {
                continue;
            }

            $label = trim((string) ($mapping['etiqueta_landing'] ?? ''));
            if ($label === '') {
                $label = $this->prettifyLabel($column);
            }

            $value = (string) ($baseFieldMap[$column] ?? $additional[$column] ?? '');
            if ($value === '') {
                continue;
            }

            $phase = trim((string) ($mapping['fase_clave'] ?? ''));
            if (! in_array($phase, ['fase_1', 'fase_2', 'fase_3'], true)) {
                $phase = null;
            }

            if ((int) ($mapping['mostrar_landing'] ?? 0) === 1) {
                $mappedFields[] = [
                    'label' => $label,
                    'value' => $value,
                    'phase' => $phase,
                ];
            }

            if ((int) ($mapping['usar_para_hito'] ?? 0) === 1) {
                $milestones[] = [
                    'label' => $label,
                    'value' => $value,
                    'phase' => $phase,
                ];
            }

            if ((int) ($mapping['mostrar_card'] ?? 0) === 1 && $phase !== null) {
                $cards[$phase][] = [
                    'label' => $label,
                    'value' => $value,
                ];
            }
        }

        $item['landing_dynamic'] = [
            'mapped_fields' => $mappedFields,
            'milestones' => $milestones,
            'cards' => $cards,
            'division' => $division,
        ];

        return $item;
    }

    private function prettifyLabel(string $value): string
    {
        $parts = array_filter(explode('_', mb_strtolower(trim($value))));
        $parts = array_map(static fn(string $part): string => mb_convert_case($part, MB_CASE_TITLE, 'UTF-8'), $parts);

        return trim(implode(' ', $parts));
    }

    public function listarArchivosPublicosComunidades(): ResponseInterface
    {
        $baseDir = FCPATH . 'assets/archivos';

        if (! is_dir($baseDir)) {
            return $this->response->setJSON([
                'ok' => true,
                'data' => [
                    'items' => [],
                ],
            ]);
        }

        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'zip', 'rar', 'txt'];
        $entries = scandir($baseDir) ?: [];
        $items = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
                continue;
            }

            $fullPath = $baseDir . DIRECTORY_SEPARATOR . $entry;
            if (! is_file($fullPath)) {
                continue;
            }

            $extension = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));
            if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            $items[] = [
                'name' => $entry,
                'url' => site_url('assets/archivos/' . rawurlencode($entry)),
                'size' => filesize($fullPath) ?: 0,
                'updated_at' => date('Y-m-d H:i', (int) (filemtime($fullPath) ?: time())),
            ];
        }

        usort($items, static fn(array $a, array $b): int => strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? '')));

        return $this->response->setJSON([
            'ok' => true,
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function cortes(): ResponseInterface
    {
        $locale = $this->resolveLocale();
        $departamento = trim(strip_tags((string) $this->request->getGet('departamento')));
        $municipio = trim(strip_tags((string) $this->request->getGet('municipio')));
        $departamento = mb_substr($departamento, 0, 120);
        $municipio = mb_substr($municipio, 0, 120);

        return $this->response->setJSON([
            'ok' => true,
            'data' => $this->portalService->listCortes($locale, $departamento, $municipio),
        ]);
    }

    public function sniGeometrias(): ResponseInterface
    {
        $locale = $this->resolveLocale();

        try {
            $featureCollection = $this->portalService->listSniGeometrias($locale);
            $features = $featureCollection['features'] ?? [];
            $usedSlugs = [];

            foreach ($features as $feature) {
                $slug = trim((string) ($feature['properties']['capa_slug'] ?? ''));
                if ($slug !== '') {
                    $usedSlugs[$slug] = true;
                }
            }

            return $this->encryptedJsonResponse([
                'ok' => true,
                'data' => [
                    'features' => $features,
                    'simbologias' => $this->portalService->listSniSimbologias(array_keys($usedSlugs)),
                ],
            ]);
        } catch (\Throwable $exception) {
            return $this->encryptedJsonResponse([
                'ok' => false,
                'message' => lang('Portal.sniLoadError', [], $locale),
            ], 500);
        }
    }

    private function resolveLocale(): string
    {
        $supported = config('App')->supportedLocales;
        $candidate = strtolower((string) ($this->request->getGet('lang') ?? $this->session->get('site_locale') ?? config('App')->defaultLocale));

        if (! in_array($candidate, $supported, true)) {
            $candidate = config('App')->defaultLocale;
        }

        $this->session->set('site_locale', $candidate);
        $this->request->setLocale($candidate);

        return $candidate;
    }

    private function buildLocaleOptions(): array
    {
        $supported = config('App')->supportedLocales;
        $options = [];

        foreach ($supported as $locale) {
            $options[] = [
                'code' => $locale,
                'label' => self::LOCALE_LABELS[$locale] ?? strtoupper($locale),
            ];
        }

        return $options;
    }

    private function issueCaptchaChallenge(): array
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);
        $token = bin2hex(random_bytes(16));

        $pool = (array) $this->session->get('public_captcha_pool');

        foreach ($pool as $existingToken => $record) {
            if (! isset($record['expires_at']) || (int) $record['expires_at'] < time()) {
                unset($pool[$existingToken]);
            }
        }

        $pool[$token] = [
            'answer' => (string) ($a + $b),
            'expires_at' => time() + 600,
        ];

        $this->session->set('public_captcha_pool', $pool);

        return [
            'token' => $token,
            'question' => $a . ' + ' . $b . ' = ?',
        ];
    }

    private function validateCaptcha(string $token, string $answer): bool
    {
        if ($token === '' || $answer === '') {
            return false;
        }

        $pool = (array) $this->session->get('public_captcha_pool');
        $record = $pool[$token] ?? null;

        unset($pool[$token]);
        $this->session->set('public_captcha_pool', $pool);

        if (! is_array($record)) {
            return false;
        }

        if ((int) ($record['expires_at'] ?? 0) < time()) {
            return false;
        }

        return hash_equals((string) ($record['answer'] ?? ''), $answer);
    }
}

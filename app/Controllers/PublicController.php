<?php

namespace App\Controllers;

use App\Models\LanguageModel;
use App\Modules\Ecoe\Models\DistribuidoraModel;
use App\Services\PublicPortalService;

class PublicController extends BaseController
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
        $locale = $this->resolveLocale();

        $data = [
            'page' => 'cortes',
            'currentLocale' => $locale,
            'localeOptions' => $this->buildLocaleOptions(),
            'gerencias' => $this->portalService->getGerencias($locale),
            'publicMenuItems' => $this->portalService->getPublicMenuItems($locale),
            'ajaxCipherKey' => (string) env('security.ajaxCipherKey', ''),
            'departamentos' => $this->portalService->getDepartamentos(),
            'whatsappBySection' => [
                'hero' => (string) env('portal.whatsappGeneral', '50255550001'),
                'beneficiados' => (string) env('portal.whatsappTarifaSocial', '50255550011'),
                'electrificacion' => (string) env('portal.whatsappElectrificacion', '50255550022'),
                'cortes' => (string) env('portal.whatsappCortes', '50255550033'),
            ],
        ];

        return view('public/layout', array_merge($data, [
            'pageTitle' => lang('Portal.cortesTitle', [], $locale) . ' | ' . lang('Portal.metaTitle', [], $locale),
            'heroTitle' => lang('Portal.cortesTitle', [], $locale),
            'heroLead' => lang('Portal.cortesHelp', [], $locale),
            'innerView' => 'public/pages/cortes',
            'innerData' => $data,
            'scriptsView' => 'public/pages/cortes_script',
        ]));
    }

    public function legacy(): string
    {
        $locale = $this->resolveLocale();
        $departamentoId = (int) $this->request->getGet('departamento_id');
        $municipioId = (int) $this->request->getGet('municipio_id');

        return view('public/cortes', [
            'currentLocale' => $locale,
            'localeOptions' => $this->buildLocaleOptions(),
            'whatsappBySection' => [
                'hero' => (string) env('portal.whatsappGeneral', '50255550001'),
                'cortes' => (string) env('portal.whatsappCortes', '50255550033'),
            ],
            'initialCortes' => $this->portalService->listCortes($locale, $departamentoId, $municipioId),
        ]);
    }

    private function resolveLocale(): string
    {
        $candidate = strtolower((string) ($this->request->getGet('lang') ?? $this->session->get('site_locale') ?? config('App')->defaultLocale));

        if (! in_array($candidate, $this->availableLocaleCodes(), true)) {
            $candidate = config('App')->defaultLocale;
        }

        $this->session->set('site_locale', $candidate);
        $this->request->setLocale($candidate);

        return $candidate;
    }

    private function buildLocaleOptions(): array
    {
        $options = [];

        $languages = (new LanguageModel())
            ->where('is_active', 1)
            ->where('is_visible', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
        foreach ($languages as $language) {
            $options[] = [
                'code' => (string) $language['code'],
                'label' => (string) $language['name'],
            ];
        }

        if ($options !== []) {
            return $options;
        }

        foreach (config('App')->supportedLocales as $locale) {
            $options[] = [
                'code' => $locale,
                'label' => self::LOCALE_LABELS[$locale] ?? strtoupper($locale),
            ];
        }

        return $options;
    }

    /** @return list<string> */
    private function availableLocaleCodes(): array
    {
        return array_values(array_map('strval', array_column($this->buildLocaleOptions(), 'code')));
    }

    /**
     * Página del chat ECOE flotante integrado en layout principal
     */
    public function ecoeChatPage(): string
    {
        $locale = $this->resolveLocale();
        $distribuidoraM = new DistribuidoraModel();

        $data = [
            'page' => 'ecoe_chat',
            'currentLocale' => $locale,
            'localeOptions' => $this->buildLocaleOptions(),
            'gerencias' => $this->portalService->getGerencias($locale),
            'publicMenuItems' => $this->portalService->getPublicMenuItems($locale),
            'ajaxCipherKey' => (string) env('security.ajaxCipherKey', ''),
            'distribuidoras' => $distribuidoraM->listActivas(),
        ];

        return view('public/layout', array_merge($data, [
            'pageTitle' => 'Asistente ECOE | ' . lang('Portal.metaTitle', [], $locale),
            'heroTitle' => 'Asistente Virtual INDE',
            'heroLead' => 'Consulta sobre Tarifa Social, Grandes Usuarios, EEM y más',
            'innerView' => 'public/pages/ecoe_chat',
            'innerData' => $data,
            'scriptsView' => 'public/pages/empty_script',
        ]));
    }
}

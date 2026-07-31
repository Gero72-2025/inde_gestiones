<!doctype html>
<html lang="<?= esc($currentLocale ?? 'es') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc(lang('Portal.metaDescription')) ?>">
    <meta name="robots" content="index,follow">
    <title><?= esc($pageTitle ?? lang('Portal.metaTitle')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,500;6..72,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #0e2235;
            --brand-a: #0f6d8f;
            --brand-b: #1a936f;
            --brand-c: #f4a340;
            --hero-chip-bg: rgba(255, 255, 255, .24);
            --hero-chip-border: rgba(255, 255, 255, .45);
            --nav-active-bg: #ffd166;
            --nav-active-ink: #3e2f05;
        }

        body {
            font-family: 'Sora', sans-serif;
            color: var(--ink);
            background: linear-gradient(180deg, #eff8ff 0%, #f8fcfb 100%);
        }

        h1, h2, h3, h4 { font-family: 'Newsreader', serif; }

        .hero {
            background:
                radial-gradient(1200px 380px at 20% -5%, rgba(255, 209, 102, .28) 0%, rgba(255, 209, 102, 0) 60%),
                radial-gradient(900px 320px at 85% 0%, rgba(255, 255, 255, .18) 0%, rgba(255, 255, 255, 0) 60%),
                linear-gradient(135deg, #0b4a66 0%, #1b7c79 56%, #2c9f78 100%);
            color: #fff;
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
            box-shadow: 0 20px 38px rgba(7, 49, 71, .22);
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            inset: auto -8% -36% auto;
            width: 340px;
            height: 340px;
            background: radial-gradient(circle at 35% 35%, rgba(255, 209, 102, .22) 0%, rgba(255, 209, 102, 0) 70%);
            pointer-events: none;
        }

        .hero-company-bg {
            position: absolute;
            inset: 0;
            z-index: 1;
            background-position: center;
            background-repeat: no-repeat;
            background-size: clamp(360px, 54vw, 760px);
            opacity: .14;
            pointer-events: none;
        }

        .hero > .container {
            position: relative;
            z-index: 2;
        }

        .header-controls {
            border: 1px solid var(--hero-chip-border);
            background: linear-gradient(180deg, rgba(255, 255, 255, .2) 0%, rgba(255, 255, 255, .1) 100%);
            border-radius: 1.1rem;
            padding: .65rem .75rem;
            backdrop-filter: blur(3px);
        }

        .portal-nav-shell {
            position: relative;
            border: 1px solid rgba(255, 255, 255, .32);
            background: linear-gradient(180deg, rgba(255, 255, 255, .18) 0%, rgba(255, 255, 255, .08) 100%);
            border-radius: 1rem;
            padding: .8rem;
            margin-bottom: 1rem;
            overflow: visible;
        }

        .portal-nav {
            position: relative;
            z-index: 2;
            gap: .55rem;
            margin-bottom: 0;
        }

        .portal-nav .portal-nav-item,
        .portal-nav .portal-nav-parent {
            border: 1px solid var(--hero-chip-border);
            background: var(--hero-chip-bg);
            color: #fff;
            border-radius: 999px;
            padding: .45rem .9rem;
            font-size: .88rem;
            font-weight: 600;
            transition: transform .16s ease, box-shadow .16s ease, background-color .16s ease;
        }

        .portal-nav .portal-nav-item:hover,
        .portal-nav .portal-nav-parent:hover,
        .portal-nav .portal-nav-item:focus-visible,
        .portal-nav .portal-nav-parent:focus-visible {
            border-color: rgba(255, 255, 255, .6);
            background: rgba(255, 255, 255, .34);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(7, 49, 71, .2);
        }

        .portal-nav .portal-nav-item.active,
        .portal-nav .portal-nav-parent.active {
            background: var(--nav-active-bg);
            border-color: var(--nav-active-bg);
            color: var(--nav-active-ink);
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(62, 49, 0, .24);
        }

        .portal-nav-label {
            font-size: .82rem;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .88);
            margin-bottom: .45rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }

        .portal-nav .dropdown-menu {
            border-radius: .95rem;
            border: 1px solid #c7d9e7;
            box-shadow: 0 12px 28px rgba(7, 49, 71, .16);
            padding: .4rem;
            min-width: 14rem;
        }

        .portal-nav .dropdown-item {
            border-radius: .65rem;
            padding: .5rem .65rem;
            font-size: .9rem;
        }

        .portal-nav .dropdown-item.active,
        .portal-nav .dropdown-item:active {
            background: var(--nav-active-bg);
            color: var(--nav-active-ink);
            font-weight: 700;
        }

        .portal-search {
            background: linear-gradient(180deg, rgba(255, 255, 255, .22) 0%, rgba(255, 255, 255, .12) 100%);
            border: 1px solid var(--hero-chip-border);
            border-radius: 1rem;
            padding: .45rem;
            max-width: 780px;
        }

        .portal-search .search-icon {
            border: 0;
            background: transparent;
            color: rgba(255, 255, 255, .9);
            padding: 0 .7rem 0 .75rem;
            font-size: 1.05rem;
        }

        .portal-search .form-control {
            border: 0;
            background: rgba(255, 255, 255, .96);
            border-radius: .8rem;
            min-height: 2.8rem;
            box-shadow: inset 0 1px 3px rgba(13, 68, 96, .1);
            font-size: .98rem;
        }

        .portal-search .form-control:focus {
            box-shadow: 0 0 0 .22rem rgba(26, 147, 111, .3), inset 0 1px 3px rgba(13, 68, 96, .1);
        }

        .portal-search .btn {
            border-radius: .8rem;
            padding-inline: 1.05rem;
            min-height: 2.8rem;
            border: 0;
            background: linear-gradient(180deg, #ffd166 0%, #f4b942 100%);
            color: #3e2f05;
        }

        .portal-search .btn:hover,
        .portal-search .btn:focus-visible {
            background: linear-gradient(180deg, #ffdd8f 0%, #f4c55f 100%);
            color: #2f2504;
        }

        .portal-search-hint {
            margin-top: .45rem;
            color: rgba(255, 255, 255, .95);
            font-size: .82rem;
        }

        .hero h1 {
            text-wrap: balance;
        }

        .hero .lead {
            max-width: 72ch;
        }

        .option-card {
            border: 1px solid #cee0ee;
            border-radius: 1rem;
            box-shadow: 0 10px 20px rgba(13, 57, 86, .08);
        }

        .hero-panel {
            background:
                radial-gradient(900px 360px at 15% 0%, rgba(255, 209, 102, .24) 0%, rgba(255, 209, 102, 0) 58%),
                radial-gradient(760px 280px at 85% 8%, rgba(255, 255, 255, .18) 0%, rgba(255, 255, 255, 0) 60%),
                linear-gradient(135deg, #0b4f8a 0%, #1668af 58%, #2b86d1 100%);
            color: #fff;
            box-shadow: 0 22px 42px rgba(7, 49, 71, .18);
        }

        .eyebrow {
            letter-spacing: .12em;
            text-transform: uppercase;
            font-size: .78rem;
            color: rgba(255, 255, 255, .84);
        }

        .hero-title {
            text-wrap: balance;
            font-size: clamp(2rem, 4vw, 4rem);
            font-weight: 800;
            line-height: 1.02;
            font-family: 'Newsreader', serif;
        }

        .hero-subtitle {
            max-width: 68ch;
            margin-inline: auto;
            font-size: 1.05rem;
            color: rgba(255, 255, 255, .9);
        }

        .search-shell {
            max-width: 820px;
            background: rgba(255, 255, 255, .15);
            border: 1px solid rgba(255, 255, 255, .34);
            border-radius: 1rem;
            padding: .5rem;
            backdrop-filter: blur(4px);
        }

        .search-shell .input-group-text {
            border-radius: .85rem;
            border: 0;
            color: #0b4a66;
        }

        .search-shell .form-control {
            border-radius: .85rem;
            box-shadow: inset 0 1px 3px rgba(13, 68, 96, .08);
        }

        .search-hint {
            font-size: .9rem;
            color: rgba(255, 255, 255, .92);
        }

        .phase-badge {
            border-radius: 999px;
            padding: .65rem .95rem;
            font-size: .9rem;
        }

        .phase-fase_1 { background: #0d6efd; }
        .phase-fase_2 { background: #f0ad4e; color: #3c2c00; }
        .phase-fase_3 { background: #198754; }

        .stepper-wrap {
            position: relative;
            padding-top: 1rem;
        }

        .stepper-line {
            position: absolute;
            top: 2.4rem;
            left: 10%;
            right: 10%;
            height: 4px;
            border-radius: 999px;
            background: linear-gradient(90deg, #d9e9f2 0%, #c8dfd1 100%);
        }

        .stepper-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .step-item {
            text-align: center;
            padding: 1rem .75rem .75rem;
            border-radius: 1rem;
            background: rgba(248, 252, 251, .9);
            border: 1px solid #d8e7ef;
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }

        .step-item.active {
            border-color: #1b7c79;
            box-shadow: 0 14px 24px rgba(27, 124, 121, .16);
            transform: translateY(-2px);
        }

        .step-item.completed {
            opacity: .88;
        }

        .step-dot {
            width: 2.45rem;
            height: 2.45rem;
            margin: 0 auto .65rem;
            border-radius: 999px;
            display: grid;
            place-items: center;
            font-weight: 800;
            color: #0e2235;
            background: #d7f2e6;
        }

        .step-item.active .step-dot {
            background: #ffd166;
        }

        .step-item.completed .step-dot {
            background: #c7f0db;
        }

        .timeline-list .timeline-item {
            border: 1px solid #dce8ef;
            border-radius: .9rem;
            margin-bottom: .7rem;
        }

        .requirement-card {
            border: 1px solid #dce8ef;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fff 0%, #f7fbfd 100%);
        }

        .requirement-list {
            padding-left: 1.1rem;
            margin-bottom: 0;
        }

        .requirement-list li {
            margin-bottom: .5rem;
        }

        .map-card {
            background: #fff;
            border: 1px solid #dce8ef;
            border-radius: .95rem;
            padding: 1rem;
            box-shadow: 0 10px 18px rgba(13, 57, 86, .06);
        }

        .public-card {
            border-radius: 1rem;
        }

        .public-icon-circle {
            width: 2.25rem;
            height: 2.25rem;
            display: inline-grid;
            place-items: center;
            border-radius: 999px;
            background: #eaf6ff;
            color: #0b4a66;
        }

        .public-check-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .public-check-list li {
            display: flex;
            gap: .5rem;
            margin-bottom: .6rem;
        }

        .public-check-list i {
            color: #198754;
            margin-top: .15rem;
        }

        .phase-mini {
            border-radius: 1rem;
            padding: 1rem;
            border: 1px solid #dce8ef;
            background: #fff;
            box-shadow: 0 10px 18px rgba(13, 57, 86, .06);
        }

        .phase-mini-f1 { border-top: 4px solid #0d6efd; }
        .phase-mini-f2 { border-top: 4px solid #f0ad4e; }
        .phase-mini-f3 { border-top: 4px solid #198754; }

        .downloads-list {
            display: grid;
            gap: .75rem;
        }

        .download-item {
            border: 1px solid #dce8ef;
            border-radius: .85rem;
            padding: .85rem 1rem;
            background: #fff;
        }

        @media (max-width: 991.98px) {
            .stepper-line {
                left: 8%;
                right: 8%;
            }

            .stepper-grid {
                grid-template-columns: 1fr;
            }
        }

        .table-shell {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .whatsapp-fab {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            border-radius: 999px;
            z-index: 1200;
            min-width: auto;
        }

        .whatsapp-fab span {
            display: inline-block;
            white-space: nowrap;
        }

        @media (max-width: 576px) {
            .whatsapp-fab {
                padding: 0.65rem 0.75rem;
                gap: 0.5rem;
                min-width: auto;
                justify-content: center;
            }

            .whatsapp-fab span {
                display: none;
            }
        }

        .map-pill {
            border: 1px solid #8bb9cf;
            border-radius: .8rem;
            padding: .45rem .7rem;
            background: #eff9ff;
        }

        @media (max-width: 991.98px) {
            .header-controls {
                padding: .6rem;
            }

            .portal-nav {
                gap: .45rem;
            }

            .portal-nav .portal-nav-item,
            .portal-nav .portal-nav-parent {
                width: 100%;
                text-align: left;
                border-radius: .85rem;
            }

            .portal-search {
                padding: .35rem;
            }
        }
    </style>
</head>
<body>
<?php
    $activeParentBackgroundPath = '';
    foreach (($publicMenuItems ?? []) as $menuItem) {
        $menuChildren = is_array($menuItem['children'] ?? null) ? $menuItem['children'] : [];
        if ((int) ($menuItem['is_dropdown'] ?? 0) !== 1 || $menuChildren === []) {
            continue;
        }

        foreach ($menuChildren as $menuChild) {
            if (($page ?? '') === ($menuChild['page_key'] ?? '')) {
                $activeParentBackgroundPath = trim((string) ($menuItem['background_image_path'] ?? ''));
                break 2;
            }
        }
    }
?>

<header class="hero py-4 py-lg-5">
    <?php if ($activeParentBackgroundPath !== ''): ?>
        <div class="hero-company-bg" style="background-image: url('<?= esc(base_url($activeParentBackgroundPath)) ?>');" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 header-controls">
            <span class="badge text-bg-light text-dark"><?= esc(lang('Portal.headerBadge')) ?></span>
            <div class="d-flex align-items-center gap-2">
                <label class="small fw-semibold" for="localeSwitcher"><?= esc(lang('Portal.languageLabel')) ?></label>
                <select id="localeSwitcher" class="form-select form-select-sm" aria-label="<?= esc(lang('Portal.languageLabel')) ?>">
                    <?php foreach (($localeOptions ?? []) as $locale): ?>
                        <option value="<?= esc($locale['code']) ?>" <?= ($locale['code'] === ($currentLocale ?? 'es')) ? 'selected' : '' ?>>
                            <?= esc($locale['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h1 class="display-5 mb-2"><?= esc($heroTitle ?? lang('Portal.heroTitle')) ?></h1>
        <p class="lead mb-3"><?= esc($heroLead ?? lang('Portal.heroLead')) ?></p>

        <p class="portal-nav-label"><i class="bi bi-compass"></i> Servicios más consultados</p>
        <div class="portal-nav-shell">
            <nav class="portal-nav d-flex flex-wrap gap-2" aria-label="Navegacion publica">
                <a class="btn btn-sm portal-nav-item <?= ($page ?? '') === 'home' ? 'active' : '' ?>" href="<?= esc(site_url('/')) ?>">
                    <i class="bi bi-house-door me-1"></i><?= esc(lang('Portal.navHome')) ?>
                </a>

                <?php foreach (($publicMenuItems ?? []) as $index => $item): ?>
                    <?php
                        $children = is_array($item['children'] ?? null) ? $item['children'] : [];
                        $isDropdown = (int) ($item['is_dropdown'] ?? 0) === 1 && $children !== [];
                        $isParentActive = false;

                        foreach ($children as $child) {
                            if (($page ?? '') === ($child['page_key'] ?? '')) {
                                $isParentActive = true;
                                break;
                            }
                        }

                        if (! $isParentActive && ! $isDropdown) {
                            $isParentActive = ($page ?? '') === ($item['page_key'] ?? '');
                        }
                    ?>

                    <?php if ($isDropdown): ?>
                        <div class="dropdown">
                            <button
                                class="btn btn-sm portal-nav-parent dropdown-toggle <?= $isParentActive ? 'active' : '' ?>"
                                type="button"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                id="publicNavDropdown<?= (int) $index ?>"
                            >
                                <i class="bi <?= esc((string) ($item['icon_class'] ?? 'bi-folder2-open')) ?> me-1"></i>
                                <?= esc((string) ($item['title'] ?? 'Menu')) ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="publicNavDropdown<?= (int) $index ?>">
                                <?php foreach ($children as $child): ?>
                                    <?php $childActive = ($page ?? '') === ($child['page_key'] ?? ''); ?>
                                    <li>
                                        <a class="dropdown-item <?= $childActive ? 'active' : '' ?>" href="<?= esc(site_url((string) ($child['route_path'] ?? ''))) ?>">
                                            <?= esc((string) ($child['title'] ?? '')) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php elseif (trim((string) ($item['route_path'] ?? '')) !== ''): ?>
                        <a class="btn btn-sm portal-nav-item <?= $isParentActive ? 'active' : '' ?>" href="<?= esc(site_url((string) ($item['route_path'] ?? ''))) ?>">
                            <i class="bi <?= esc((string) ($item['icon_class'] ?? 'bi-grid')) ?> me-1"></i>
                            <?= esc((string) ($item['title'] ?? '')) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>

        <?php if (($page ?? '') === 'home'): ?>
            <form id="routeSearchForm" class="input-group input-group-lg portal-search" role="search" aria-label="Busqueda de secciones">
                <span class="input-group-text search-icon"><i class="bi bi-search"></i></span>
                <input id="routeSearchInput" class="form-control" type="search" placeholder="<?= esc(lang('Portal.headerSearchPlaceholder')) ?>" aria-label="<?= esc(lang('Portal.headerSearchPlaceholder')) ?>">
                <button class="btn btn-warning fw-semibold" type="submit"><?= esc(lang('Portal.headerSearchButton')) ?></button>
            </form>
            <p class="portal-search-hint mb-0">Escribe lo que necesitas: factura, beneficiados, cortes o comunidad.</p>
        <?php endif; ?>
    </div>
</header>

<main class="container py-4 py-lg-5">
    <?= view($innerView, $innerData ?? []) ?>
</main>

<?= view('public/pages/chat_bubble', [
    'chatWidgetTitle' => 'Asistente INDE',
    'chatWidgetSubtitle' => 'Responde en tiempo real',
    'chatWidgetPlaceholder' => 'Escribe tu pregunta...',
    'chatWidgetIntro' => '¿Qué empresa necesitas apoyar?',
    'chatWidgetTarifaSocialEndpoint' => site_url('api/ecoe/ts/consultar-nis')
]) ?>

<button id="whatsappFab" class="btn btn-success whatsapp-fab d-flex align-items-center gap-2 px-3 py-2" type="button">
    <i class="bi bi-whatsapp"></i>
    <span><?= esc(lang('Portal.whatsappLabel')) ?></span>
</button>

<script>
    window.PortalConfig = {
        page: <?= json_encode($page ?? 'home') ?>,
        localeEndpoint: <?= json_encode(site_url('locale')) ?>,
        currentBaseUrl: <?= json_encode(site_url()) ?>,
        csrfToken: <?= json_encode(csrf_hash()) ?>,
        csrfName: <?= json_encode(csrf_token()) ?>,
        cipherKey: <?= json_encode((string) ($ajaxCipherKey ?? '')) ?>,
        endpoints: {
            captcha: <?= json_encode(site_url('api/public/captcha')) ?>,
            beneficiados: <?= json_encode(site_url('api/public/beneficiados')) ?>,
            comunidadesArchivos: <?= json_encode(site_url('api/public/comunidades/archivos')) ?>,
            cortes: <?= json_encode(site_url('api/public/cortes')) ?>,
            sniGeometrias: <?= json_encode(site_url('api/public/sni/geometrias')) ?>
        },
        noResults: <?= json_encode(lang('Portal.noResults')) ?>,
        whatsappMessage: <?= json_encode(lang('Portal.whatsappMessage')) ?>,
        whatsappBySection: <?= json_encode($whatsappBySection ?? []) ?>
    };

    document.getElementById('localeSwitcher').addEventListener('change', function () {
        window.location.href = window.PortalConfig.localeEndpoint + '/' + encodeURIComponent(this.value);
    });

    const routeSearchForm = document.getElementById('routeSearchForm');
    if (routeSearchForm) {
        routeSearchForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const q = (document.getElementById('routeSearchInput').value || '').toLowerCase();
            let target = 'consulta/beneficiados';
            if (q.includes('comunidad') || q.includes('gero')) target = 'consulta/comunidades';
            if (q.includes('corte') || q.includes('energia')) target = 'consulta/cortes';
            window.location.href = window.PortalConfig.currentBaseUrl.replace(/\/$/, '') + '/' + target;
        });
    }

    const whatsappFab = document.getElementById('whatsappFab');
    whatsappFab.addEventListener('click', function () {
        const map = window.PortalConfig.whatsappBySection || {};
        const page = window.PortalConfig.page || 'home';
        const number = map[page] || map.hero || '50255550001';
        const message = encodeURIComponent(window.PortalConfig.whatsappMessage || 'Hola, necesito apoyo en el portal publico INDE.');
        window.open('https://wa.me/' + number + '?text=' + message, '_blank', 'noopener');
    });

    // Eliminar toolbarContainer si es inyectado por el Debug Toolbar
    function removeDebugToolbarContainer() {
        const toolbar = document.getElementById('toolbarContainer');
        if (toolbar) {
            toolbar.remove();
        }
    }

    removeDebugToolbarContainer();

    const debugToolbarObserver = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node && node.id === 'toolbarContainer') {
                    node.remove();
                }
            });
        });
    });

    debugToolbarObserver.observe(document.body, { childList: true });
</script>

<?= view($scriptsView ?? 'public/pages/empty_script') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
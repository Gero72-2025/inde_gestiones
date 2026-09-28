<!doctype html>
<html lang="<?= esc($currentLocale ?? 'es') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc(lang('Portal.metaDescription')) ?>">
    <meta name="robots" content="index,follow">
    <title><?= esc($pageTitle ?? lang('Portal.metaTitle')) ?></title>
    <!-- Favicon clásico (ICO o PNG) -->
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/cropped-favicon-32x32.png') ?>">

    <!-- O si prefieres usar un PNG moderno -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/img/cropped-favicon-32x32.png') ?>">

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
            background: #0b4a66;
            color: #fff;
            border-radius: 2rem;
            box-shadow: 0 20px 38px rgba(7, 49, 71, .22);
            position: relative;
            overflow: hidden;
            margin: 10px 50px;
            min-height: 400px;
            display: flex;
            align-items: center;
        }

        .hero > .container {
            width: 100%;
        }

        @media (max-width: 991.98px) {
            .hero {
                margin: 10px 20px;
                min-height: 320px;
            }
        }

        @media (max-width: 575.98px) {
            .hero {
                margin: 10px;
                min-height: auto;
            }
        }

        .hero-company-bg {
            position: absolute;
            inset: 0;
            z-index: 1;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            border-radius: inherit;
            pointer-events: none;
        }

        .hero-scrim {
            position: absolute;
            inset: 0;
            z-index: 2;
            background: linear-gradient(180deg, rgba(6, 26, 38, .55) 0%, rgba(6, 26, 38, .28) 45%, rgba(6, 26, 38, .55) 100%);
            pointer-events: none;
        }

        .hero > .container {
            position: relative;
            z-index: 3;
        }

        .site-footer {
            background: #0b4a66;
            color: #fff;
            border-radius: 2rem;
            box-shadow: 0 20px 38px rgba(7, 49, 71, .22);
            position: relative;
            overflow: hidden;
            margin: 10px 50px;
        }

        .site-footer.has-bg {
            min-height: 400px;
            display: flex;
            align-items: center;
        }

        .site-footer.has-bg > .container {
            width: 100%;
        }

        @media (max-width: 991.98px) {
            .site-footer {
                margin: 10px 20px;
            }

            .site-footer.has-bg {
                min-height: 320px;
            }
        }

        @media (max-width: 575.98px) {
            .site-footer {
                margin: 10px;
            }

            .site-footer.has-bg {
                min-height: auto;
            }
        }

        .footer-company-bg {
            position: absolute;
            inset: 0;
            z-index: 1;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            border-radius: inherit;
            pointer-events: none;
        }

        .footer-scrim {
            position: absolute;
            inset: 0;
            z-index: 2;
            background: linear-gradient(180deg, rgba(6, 26, 38, .55) 0%, rgba(6, 26, 38, .28) 45%, rgba(6, 26, 38, .55) 100%);
            pointer-events: none;
        }

        .portal-nav-wrap {
            margin-top: 1rem;
            margin-bottom: 1rem;
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
            border: 1px solid #d8e6ef;
            background: #fff;
            border-radius: 1rem;
            padding: .8rem;
            box-shadow: 0 10px 20px rgba(13, 57, 86, .08);
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
            border: 1px solid #cfe3ee;
            background: #eef6fb;
            color: var(--brand-a);
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
            border-color: var(--brand-a);
            background: var(--brand-a);
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
            color: var(--ink);
            opacity: .72;
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
            background:#181630;
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

        .top-access-bar {
            background: #181630;
            color: #eaf4fa;
            font-size: .82rem;
            padding: .4rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .top-access-bar .container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: .6rem;
        }

        .top-access-bar .btn-access {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, .28);
            color: #eaf4fa;
            border-radius: .6rem;
            padding: .25rem .55rem;
            font-size: .78rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .top-access-bar .btn-access:hover,
        .top-access-bar .btn-access:focus-visible {
            background: rgba(255, 255, 255, .12);
            color: #fff;
        }

        .top-access-bar .dropdown-menu {
            font-size: .86rem;
            min-width: 10rem;
        }

        .top-access-bar .dropdown-item.active {
            background: var(--brand-a);
            color: #fff;
        }

        body.high-contrast {
            filter: contrast(1.3) grayscale(.15);
        }

        body.high-contrast .hero,
        body.high-contrast .site-footer {
            background: #000 !important;
        }
    </style>
</head>
<body>
<div class="top-access-bar" role="region" aria-label="<?= esc(__('accessibility.bar.label', 'Barra de accesibilidad e idiomas')) ?>">
    <div class="container">
        <div class="dropdown">
            <button
                class="btn-access dropdown-toggle"
                type="button"
                id="topBarLanguageToggle"
                data-bs-toggle="dropdown"
                aria-expanded="false"
            >
                <i class="bi bi-translate"></i>
                <span id="topBarLanguageLabel">
                    <?php
                        $activeLocaleLabel = $currentLocale ?? 'es';
                        foreach (($localeOptions ?? []) as $localeOption) {
                            if (($localeOption['code'] ?? '') === ($currentLocale ?? 'es')) {
                                $activeLocaleLabel = $localeOption['label'] ?? $activeLocaleLabel;
                                break;
                            }
                        }
                        echo esc($activeLocaleLabel);
                    ?>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="topBarLanguageToggle">
                <?php foreach (($localeOptions ?? []) as $localeOption): ?>
                    <li>
                        <a
                            class="dropdown-item js-topbar-language <?= ($localeOption['code'] ?? '') === ($currentLocale ?? 'es') ? 'active' : '' ?>"
                            href="#"
                            data-lang="<?= esc($localeOption['code'] ?? '') ?>"
                        ><?= esc($localeOption['label'] ?? '') ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="btn-access" id="topBarFontIncrease" title="<?= esc(__('accessibility.font.increase', 'Aumentar tamano de texto')) ?>">
            <i class="bi bi-zoom-in"></i> A+
        </button>
        <button type="button" class="btn-access" id="topBarFontDecrease" title="<?= esc(__('accessibility.font.decrease', 'Disminuir tamano de texto')) ?>">
            <i class="bi bi-zoom-out"></i> A-
        </button>
        <button type="button" class="btn-access" id="topBarContrastToggle" title="<?= esc(__('accessibility.contrast.toggle', 'Alternar alto contraste')) ?>">
            <i class="bi bi-circle-half"></i>
        </button>
    </div>
</div>
<script>
(function () {
    var setLanguageUrl = <?= json_encode(site_url('api/public/set-language')) ?>;
    var csrfName = <?= json_encode(csrf_token()) ?>;
    var csrfHash = <?= json_encode(csrf_hash()) ?>;

    document.querySelectorAll('.js-topbar-language').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            var lang = link.getAttribute('data-lang');
            if (!lang) { return; }

            var body = new FormData();
            body.append('lang', lang);
            body.append(csrfName, csrfHash);

            fetch(setLanguageUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: body,
            })
                .then(function (response) { return response.json(); })
                .then(function () { window.location.reload(); })
                .catch(function () { window.location.reload(); });
        });
    });

    var root = document.documentElement;
    var fontStep = parseFloat(localStorage.getItem('portalFontScale') || '1');

    function applyFontScale(scale) {
        fontStep = Math.min(1.4, Math.max(0.85, scale));
        root.style.fontSize = (fontStep * 100) + '%';
        localStorage.setItem('portalFontScale', String(fontStep));
    }

    applyFontScale(fontStep);

    document.getElementById('topBarFontIncrease')?.addEventListener('click', function () { applyFontScale(fontStep + 0.1); });
    document.getElementById('topBarFontDecrease')?.addEventListener('click', function () { applyFontScale(fontStep - 0.1); });

    var contrastOn = localStorage.getItem('portalHighContrast') === '1';
    function applyContrast(on) {
        contrastOn = on;
        document.body.classList.toggle('high-contrast', on);
        localStorage.setItem('portalHighContrast', on ? '1' : '0');
    }
    applyContrast(contrastOn);
    document.getElementById('topBarContrastToggle')?.addEventListener('click', function () { applyContrast(!contrastOn); });
})();
</script>
<?php
    $activeHeaderBackgroundPath = '';
    $activeFooterBackgroundPath = '';
    $activeShowHeader = true;
    $activeShowFooter = true;

    foreach (($publicMenuItems ?? []) as $menuItem) {
        $menuChildren = is_array($menuItem['children'] ?? null) ? $menuItem['children'] : [];

        if (($page ?? '') === ($menuItem['page_key'] ?? '') && trim((string) ($menuItem['route_path'] ?? '')) !== '') {
            $activeHeaderBackgroundPath = trim((string) ($menuItem['background_image_path'] ?? ''));
            $activeFooterBackgroundPath = trim((string) ($menuItem['footer_background_image_path'] ?? ''));
            $activeShowHeader = ! array_key_exists('show_header', $menuItem) || (bool) $menuItem['show_header'];
            $activeShowFooter = ! array_key_exists('show_footer', $menuItem) || (bool) $menuItem['show_footer'];
            break;
        }

        if ((int) ($menuItem['is_dropdown'] ?? 0) !== 1 || $menuChildren === []) {
            continue;
        }

        foreach ($menuChildren as $menuChild) {
            if (($page ?? '') === ($menuChild['page_key'] ?? '')) {
                // Prioridad: imagen propia del hijo; si no tiene, se usa la del padre.
                $childBackgroundPath = trim((string) ($menuChild['background_image_path'] ?? ''));
                $activeHeaderBackgroundPath = $childBackgroundPath !== ''
                    ? $childBackgroundPath
                    : trim((string) ($menuItem['background_image_path'] ?? ''));

                $childFooterBackgroundPath = trim((string) ($menuChild['footer_background_image_path'] ?? ''));
                $activeFooterBackgroundPath = $childFooterBackgroundPath !== ''
                    ? $childFooterBackgroundPath
                    : trim((string) ($menuItem['footer_background_image_path'] ?? ''));

                $activeShowHeader = ! array_key_exists('show_header', $menuChild) || (bool) $menuChild['show_header'];
                $activeShowFooter = ! array_key_exists('show_footer', $menuChild) || (bool) $menuChild['show_footer'];
                break 2;
            }
        }
    }
?>

<?php if ($activeShowHeader): ?>
<header class="hero py-4 py-lg-5">
    <?php if ($activeHeaderBackgroundPath !== ''): ?>
        <div class="hero-company-bg" style="background-image: url('<?= esc(base_url($activeHeaderBackgroundPath)) ?>');" aria-hidden="true"></div>
        <div class="hero-scrim" aria-hidden="true"></div>
    <?php endif; ?>
    <!-- <div class="container">
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

        <?php if (($page ?? '') === 'home'): ?>
            <form id="routeSearchForm" class="input-group input-group-lg portal-search" role="search" aria-label="Busqueda de secciones">
                <span class="input-group-text search-icon"><i class="bi bi-search"></i></span>
                <input id="routeSearchInput" class="form-control" type="search" placeholder="<?= esc(lang('Portal.headerSearchPlaceholder')) ?>" aria-label="<?= esc(lang('Portal.headerSearchPlaceholder')) ?>">
                <button class="btn btn-warning fw-semibold" type="submit"><?= esc(lang('Portal.headerSearchButton')) ?></button>
            </form>
            <p class="portal-search-hint mb-0">Escribe lo que necesitas: factura, beneficiados, cortes o comunidad.</p>
        <?php endif; ?>
    </div> -->
</header>
<?php endif; ?>

<div class="container portal-nav-wrap">
    <div class="portal-nav-shell">
        <nav class="portal-nav d-flex flex-wrap gap-2" aria-label="Navegacion publica">
            <!-- <a class="btn btn-sm portal-nav-item <?= ($page ?? '') === 'home' ? 'active' : '' ?>" href="<?= esc(site_url('/')) ?>">
                <i class="bi bi-house-door me-1"></i><?= esc(lang('Portal.navHome')) ?>
            </a> -->

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
</div>

<main class="container py-4 py-lg-5">
    <?= view($innerView, $innerData ?? []) ?>
</main>

<?php if ($activeShowFooter): ?>
<footer class="site-footer py-4 py-lg-5 mt-4<?= $activeFooterBackgroundPath !== '' ? ' has-bg' : '' ?>">
    <?php if ($activeFooterBackgroundPath !== ''): ?>
        <div class="footer-company-bg" style="background-image: url('<?= esc(base_url($activeFooterBackgroundPath)) ?>');" aria-hidden="true"></div>
        <div class="footer-scrim" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="container position-relative" style="z-index: 3;">
        <p class="mb-0 text-center small">&copy; <?= esc(date('Y')) ?> INDE. Todos los derechos reservados.</p>
    </div>
</footer>
<?php endif; ?>

<?php /* Oculto temporalmente: chat-bubble-shell no se usa en esta fase.
<?= view('public/pages/chat_bubble', [
    'chatWidgetTitle' => 'Asistente INDE',
    'chatWidgetSubtitle' => 'Responde en tiempo real',
    'chatWidgetPlaceholder' => 'Escribe tu pregunta...',
    'chatWidgetIntro' => '¿Qué empresa necesitas apoyar?',
    'chatWidgetTarifaSocialEndpoint' => site_url('api/ecoe/ts/consultar-nis')
]) ?>
*/ ?>

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
            cortesDepartamentos: <?= json_encode(site_url('api/public/cortes/departamentos')) ?>,
            cortesMunicipios: <?= json_encode(site_url('api/public/cortes/municipios')) ?>,
            sniGeometrias: <?= json_encode(site_url('api/public/sni/geometrias')) ?>
        },
        noResults: <?= json_encode(lang('Portal.noResults')) ?>,
        whatsappMessage: <?= json_encode(lang('Portal.whatsappMessage')) ?>,
        whatsappBySection: <?= json_encode($whatsappBySection ?? []) ?>,
        i18n: <?= json_encode([
            'cortes' => [
                'status' => [
                    'activo' => __('cortes.status.activo', 'Activo'),
                    'programado' => __('cortes.status.programado', 'Programado'),
                    'finalizado' => __('cortes.status.finalizado', 'Finalizado'),
                    'cancelado' => __('cortes.status.cancelado', 'Cancelado'),
                ],
                'eventDefaultTitle' => __('cortes.event.defaultTitle', 'Corte de energia'),
                'detailNoLocations' => __('cortes.detail.noLocations', 'Sin ubicaciones registradas'),
                'detailNoDescription' => __('cortes.detail.noDescription', 'Sin descripcion'),
                'allMunicipalities' => __('cortes.filter.allMunicipalities', 'Todos los municipios'),
            ],
            'comunidades' => [
                'phase' => [
                    'fase_1' => __('comunidades.phase.fase_1.full', 'Fase 1 - Solicitud'),
                    'fase_2' => __('comunidades.phase.fase_2.full', 'Fase 2 - Pre-Inversion'),
                    'fase_3' => __('comunidades.phase.fase_3.full', 'Fase 3 - Ejecucion'),
                ],
                'phaseShort' => [
                    'fase_1' => __('comunidades.phase.fase_1.short', 'Fase 1'),
                    'fase_2' => __('comunidades.phase.fase_2.short', 'Fase 2'),
                    'fase_3' => __('comunidades.phase.fase_3.short', 'Fase 3'),
                ],
                'requirements' => [
                    'fase_1' => [
                        __('comunidades.requirements.fase_1.item1', 'Solicitud firmada y sellada por COCODE.'),
                        __('comunidades.requirements.fase_1.item2', 'Listado de usuarios y croquis de ubicacion.'),
                        __('comunidades.requirements.fase_1.item3', 'Coordenadas GTM / UTM y resolucion municipal.'),
                        __('comunidades.requirements.fase_1.item4', 'Clasificacion y registro inicial del expediente.'),
                    ],
                    'fase_2' => [
                        __('comunidades.requirements.fase_2.item1', 'Estudio socioeconomico finalizado.'),
                        __('comunidades.requirements.fase_2.item2', 'Diseno electrico en validacion.'),
                        __('comunidades.requirements.fase_2.item3', 'Gestion ambiental y aprobacion SNIP en curso.'),
                        __('comunidades.requirements.fase_2.item4', 'Verificacion de presupuesto y alcances.'),
                    ],
                    'fase_3' => [
                        __('comunidades.requirements.fase_3.item1', 'Licitacion aprobada y adjudicada.'),
                        __('comunidades.requirements.fase_3.item2', 'Contratista asignado y orden de inicio.'),
                        __('comunidades.requirements.fase_3.item3', 'Plan de ejecucion y supervision activos.'),
                        __('comunidades.requirements.fase_3.item4', 'Cierre tecnico y energizacion programada.'),
                    ],
                ],
                'milestone' => [
                    'registro' => __('comunidades.milestone.registro', 'Registro de solicitud'),
                    'faseActual' => __('comunidades.milestone.faseActual', 'Fase actual'),
                    'estadoActual' => __('comunidades.milestone.estadoActual', 'Estado actual'),
                    'ultimaActualizacion' => __('comunidades.milestone.ultimaActualizacion', 'Ultima actualizacion'),
                    'pendiente' => __('comunidades.milestone.pendiente', 'Pendiente'),
                    'sinActualizar' => __('comunidades.milestone.sinActualizar', 'Sin actualizar'),
                    'campo' => __('comunidades.milestone.campo', 'Campo'),
                ],
                'mappedFields' => [
                    'cardsTitle' => __('comunidades.mappedFields.cardsTitle', 'Cards'),
                    'noFieldsForPhase' => __('comunidades.mappedFields.noFieldsForPhase', 'Sin campos configurados para esta fase.'),
                    'fallbackSolicitud' => __('comunidades.common.solicitud', 'Solicitud'),
                    'fallbackPreinversion' => __('comunidades.common.preinversion', 'Pre-Inversion'),
                    'fallbackEjecucion' => __('comunidades.mappedFields.fallbackEjecucion', 'Ejecucion'),
                    'estadoActiva' => __('comunidades.mappedFields.estadoActiva', 'Activa'),
                    'estadoRegistrada' => __('comunidades.mappedFields.estadoRegistrada', 'Registrada'),
                    'estadoEnProceso' => __('comunidades.mappedFields.estadoEnProceso', 'En proceso'),
                    'estadoPendiente' => __('comunidades.milestone.pendiente', 'Pendiente'),
                    'estadoEnEjecucion' => __('comunidades.mappedFields.estadoEnEjecucion', 'En ejecucion'),
                ],
                'downloads' => [
                    'none' => __('comunidades.downloads.none', 'No hay documentos disponibles en /assets/archivos.'),
                    'availableSuffix' => __('comunidades.downloads.availableSuffix', 'documento(s) disponible(s).'),
                    'loadError' => __('comunidades.downloads.loadError', 'No fue posible cargar los documentos en este momento.'),
                    'fetchError' => __('comunidades.downloads.fetchError', 'Error al cargar documentos. Intenta nuevamente.'),
                    'updatedLabel' => __('comunidades.downloads.updatedLabel', 'Actualizado'),
                    'endpointMissing' => __('comunidades.downloads.endpointMissing', 'No se configuro el endpoint de descargas.'),
                    'defaultName' => __('comunidades.downloads.defaultName', 'Documento'),
                ],
                'search' => [
                    'noResults' => __('comunidades.search.noResults', 'No se encontraron comunidades con ese criterio.'),
                    'resultsFoundSuffix' => __('comunidades.search.resultsFoundSuffix', 'resultado(s). Mostrando el primero.'),
                    'resultsFoundPrefix' => __('comunidades.search.resultsFoundPrefix', 'Se encontraron'),
                ],
            ],
        ], JSON_UNESCAPED_UNICODE) ?>
    };

    const localeSwitcher = document.getElementById('localeSwitcher');
    if (localeSwitcher) {
        localeSwitcher.addEventListener('change', function () {
            window.location.href = window.PortalConfig.localeEndpoint + '/' + encodeURIComponent(this.value);
        });
    }

    const routeSearchForm = document.getElementById('routeSearchForm');
    if (routeSearchForm) {
        routeSearchForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const routeSearchInput = document.getElementById('routeSearchInput');
            const q = (routeSearchInput?.value || '').toLowerCase();
            let target = 'consulta/beneficiados';
            if (q.includes('comunidad') || q.includes('gero')) target = 'consulta/comunidades';
            if (q.includes('corte') || q.includes('energia')) target = 'consulta/cortes';
            window.location.href = window.PortalConfig.currentBaseUrl.replace(/\/$/, '') + '/' + target;
        });
    }

    const whatsappFab = document.getElementById('whatsappFab');
    if (whatsappFab) {
        whatsappFab.addEventListener('click', function () {
            const map = window.PortalConfig.whatsappBySection || {};
            const page = window.PortalConfig.page || 'home';
            const number = map[page] || map.hero || '50255550001';
            const message = encodeURIComponent(window.PortalConfig.whatsappMessage || 'Hola, necesito apoyo en el portal publico INDE.');
            window.open('https://wa.me/' + number + '?text=' + message, '_blank', 'noopener');
        });
    }

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
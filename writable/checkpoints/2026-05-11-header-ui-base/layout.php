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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #0e2235;
            --brand-a: #0f6d8f;
            --brand-b: #1a936f;
            --hero-chip-bg: rgba(255, 255, 255, .14);
            --hero-chip-border: rgba(255, 255, 255, .32);
            --nav-active-bg: #ffc107;
            --nav-active-ink: #3b2f00;
        }

        body {
            font-family: 'Sora', sans-serif;
            color: var(--ink);
            background: linear-gradient(180deg, #eff8ff 0%, #f8fcfb 100%);
        }

        h1, h2, h3, h4 { font-family: 'Newsreader', serif; }

        .hero {
            background: linear-gradient(130deg, #0d4460 0%, #1a936f 100%);
            color: #fff;
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
            box-shadow: 0 20px 38px rgba(7, 49, 71, .2);
        }

        .header-controls {
            border: 1px solid var(--hero-chip-border);
            background: linear-gradient(180deg, rgba(255, 255, 255, .16) 0%, rgba(255, 255, 255, .08) 100%);
            border-radius: 1.1rem;
            padding: .65rem .75rem;
            backdrop-filter: blur(2px);
        }

        .portal-nav {
            gap: .55rem;
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
            background: rgba(255, 255, 255, .25);
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
            background: rgba(255, 255, 255, .14);
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
        }

        .portal-search .form-control:focus {
            box-shadow: 0 0 0 .22rem rgba(26, 147, 111, .3), inset 0 1px 3px rgba(13, 68, 96, .1);
        }

        .portal-search .btn {
            border-radius: .8rem;
            padding-inline: 1.05rem;
            min-height: 2.8rem;
        }

        .portal-search-hint {
            margin-top: .45rem;
            color: rgba(255, 255, 255, .9);
            font-size: .82rem;
        }

        .option-card {
            border: 1px solid #cee0ee;
            border-radius: 1rem;
            box-shadow: 0 10px 20px rgba(13, 57, 86, .08);
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
<header class="hero py-4 py-lg-5">
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
        <p class="lead mb-4"><?= esc($heroLead ?? lang('Portal.heroLead')) ?></p>

        <nav class="portal-nav d-flex flex-wrap gap-2 mb-3" aria-label="Navegacion publica">
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

        <?php if (($page ?? '') === 'home'): ?>
            <form id="routeSearchForm" class="input-group input-group-lg portal-search" role="search" aria-label="Busqueda de secciones">
                <span class="input-group-text search-icon"><i class="bi bi-search"></i></span>
                <input id="routeSearchInput" class="form-control" type="search" placeholder="<?= esc(lang('Portal.headerSearchPlaceholder')) ?>" aria-label="<?= esc(lang('Portal.headerSearchPlaceholder')) ?>">
                <button class="btn btn-warning fw-semibold" type="submit"><?= esc(lang('Portal.headerSearchButton')) ?></button>
            </form>
            <p class="portal-search-hint mb-0">Prueba con: beneficiados, electrificacion, cortes, comunidad.</p>
        <?php endif; ?>
    </div>
</header>

<main class="container py-4 py-lg-5">
    <?= view($innerView, $innerData ?? []) ?>
</main>

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
        cipherKey: <?= json_encode((string) ($ajaxCipherKey ?? '')) ?>,
        endpoints: {
            captcha: <?= json_encode(site_url('api/public/captcha')) ?>,
            beneficiados: <?= json_encode(site_url('api/public/beneficiados')) ?>,
            electrificacion: <?= json_encode(site_url('api/public/electrificacion')) ?>,
            cortes: <?= json_encode(site_url('api/public/cortes')) ?>
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
            if (q.includes('electri') || q.includes('comunidad')) target = 'consulta/electrificacion';
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
</script>

<?= view($scriptsView ?? 'public/pages/empty_script') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

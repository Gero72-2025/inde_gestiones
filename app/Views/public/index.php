<!doctype html>
<html lang="<?= esc($currentLocale ?? 'es') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc(lang('Portal.metaDescription')) ?>">
    <meta name="robots" content="index,follow">
    <title><?= esc(lang('Portal.metaTitle')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/century-gothic.css') ?>" rel="stylesheet">
    <style>
        body { background: linear-gradient(180deg, #eff8ff 0%, #f8fcfb 100%); }
        .hero { background: linear-gradient(130deg, #0d4460 0%, #1a936f 100%); color: #fff; border-bottom-left-radius: 2rem; border-bottom-right-radius: 2rem; }
        .option-card { border: 1px solid #cee0ee; border-radius: 1rem; box-shadow: 0 10px 20px rgba(13, 57, 86, .08); }
    </style>
</head>
<body>
<header class="hero py-4 py-lg-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <span class="badge text-bg-light text-dark"><?= esc(lang('Portal.headerBadge')) ?></span>
            <div class="d-flex align-items-center gap-2">
                <label class="small fw-semibold" for="localeSwitcher"><?= esc(lang('Portal.languageLabel')) ?></label>
                <select id="localeSwitcher" class="form-select form-select-sm" aria-label="<?= esc(lang('Portal.languageLabel')) ?>">
                    <?php foreach (($localeOptions ?? []) as $locale): ?>
                        <option value="<?= esc($locale['code']) ?>" <?= ($locale['code'] === ($currentLocale ?? 'es')) ? 'selected' : '' ?>><?= esc($locale['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h1 class="display-5 mb-3"><?= esc(lang('Portal.heroTitle')) ?></h1>
        <p class="lead mb-4"><?= esc(lang('Portal.demoSplitNotice')) ?></p>

        <form id="routeSearchForm" class="input-group input-group-lg" role="search">
            <input id="routeSearchInput" class="form-control" type="search" placeholder="<?= esc(lang('Portal.headerSearchPlaceholder')) ?>">
            <button class="btn btn-warning fw-semibold" type="submit"><?= esc(lang('Portal.headerSearchButton')) ?></button>
        </form>
    </div>
</header>

<main class="container py-4 py-lg-5">
    <div class="row g-3 g-lg-4">
        <div class="col-12 col-lg-4">
            <article class="option-card bg-white p-4 h-100">
                <h2 class="h4 mb-3"><?= esc(lang('Portal.navBeneficiados')) ?></h2>
                <p class="text-secondary mb-4"><?= esc(lang('Portal.beneficiadosHelp')) ?></p>
                <a class="btn btn-primary" href="<?= esc(site_url('consulta/beneficiados')) ?>"><?= esc(lang('Portal.goSection')) ?></a>
            </article>
        </div>
        <div class="col-12 col-lg-4">
            <article class="option-card bg-white p-4 h-100">
                <h2 class="h4 mb-3"><?= esc(lang('Portal.navComunidades') ?? 'Comunidades') ?></h2>
                <p class="text-secondary mb-4">Consulta el estado de electrificacion por comunidad.</p>
                <a class="btn btn-primary" href="<?= esc(site_url('consulta/comunidades')) ?>"><?= esc(lang('Portal.goSection')) ?></a>
            </article>
        </div>
        <div class="col-12 col-lg-4">
            <article class="option-card bg-white p-4 h-100">
                <h2 class="h4 mb-3"><?= esc(lang('Portal.navCortes')) ?></h2>
                <p class="text-secondary mb-4"><?= esc(lang('Portal.cortesHelp')) ?></p>
                <a class="btn btn-primary" href="<?= esc(site_url('consulta/cortes')) ?>"><?= esc(lang('Portal.goSection')) ?></a>
            </article>
        </div>
    </div>
</main>

<script>
    document.getElementById('localeSwitcher').addEventListener('change', function () {
        window.location.href = '<?= esc(site_url('locale')) ?>/' + encodeURIComponent(this.value);
    });

    document.getElementById('routeSearchForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const q = (document.getElementById('routeSearchInput').value || '').toLowerCase();
        let target = 'consulta/beneficiados';
        if (q.includes('comunidad') || q.includes('gero')) target = 'consulta/comunidades';
        if (q.includes('corte') || q.includes('energia')) target = 'consulta/cortes';
        window.location.href = '<?= esc(site_url()) ?>/' + target;
    });
</script>
</body>
</html>

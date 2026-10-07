<!doctype html>
<html lang="<?= esc($currentLocale ?? 'es') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc(lang('Portal.metaDescription')) ?>">
    <meta name="robots" content="index,follow">
    <title><?= esc(lang('Portal.cortesTitle')) ?> | <?= esc(lang('Portal.metaTitle')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/century-gothic.css') ?>" rel="stylesheet">
    <style>
        body { background: #f4f9fd; }
        .hero { background: #0f6d8f; color: #fff; }
        .table-shell { overflow-x: auto; }
        .table-shell table { min-width: 900px; }
        .map-pill { border: 1px solid #8bb9cf; border-radius: .8rem; padding: .45rem .7rem; background: #eff9ff; }
        .whatsapp-fab { position: fixed; right: 1rem; bottom: 1rem; border-radius: 999px; z-index: 1200; }
    </style>
</head>
<body>
<header class="hero py-3">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-light" href="<?= esc(site_url('/')) ?>"><?= esc(lang('Portal.navHome')) ?></a>
            <a class="btn btn-sm btn-light" href="<?= esc(site_url('consulta/beneficiados')) ?>"><?= esc(lang('Portal.navBeneficiados')) ?></a>
            <a class="btn btn-sm btn-light" href="<?= esc(site_url('consulta/comunidades')) ?>">Comunidades</a>
            <a class="btn btn-sm btn-warning" href="<?= esc(site_url('consulta/cortes')) ?>"><?= esc(lang('Portal.navCortes')) ?></a>
        </div>
        <select id="localeSwitcher" class="form-select form-select-sm" style="width:auto">
            <?php foreach (($localeOptions ?? []) as $locale): ?>
                <option value="<?= esc($locale['code']) ?>" <?= ($locale['code'] === ($currentLocale ?? 'es')) ? 'selected' : '' ?>><?= esc($locale['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</header>

<main class="container py-4">
    <section class="bg-white border rounded-4 p-3 p-lg-4">
        <h1 class="h3 mb-2"><?= esc(lang('Portal.cortesTitle')) ?></h1>
        <p class="text-secondary mb-4"><?= esc(lang('Portal.cortesHelp')) ?></p>

        <form id="cortesForm" class="row g-3 mb-3">
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold" for="departamentoInput"><?= esc(lang('Portal.departmentFilter')) ?></label>
                <input id="departamentoInput" class="form-control" placeholder="<?= esc(lang('Portal.departmentPlaceholder')) ?>">
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold" for="municipioInput"><?= esc(lang('Portal.tableMunicipality')) ?></label>
                <input id="municipioInput" class="form-control" placeholder="Opcional">
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-primary w-100" type="submit"><?= esc(lang('Portal.listButton')) ?></button>
            </div>
        </form>

        <div id="cortesPills" class="d-flex flex-wrap gap-2 mb-3" aria-label="Visual de cortes por ubicación"></div>

        <div class="table-shell">
            <table class="table table-hover align-middle" id="cortesTable">
                <thead>
                <tr>
                    <th><?= esc(lang('Portal.tableDepartment')) ?></th>
                    <th><?= esc(lang('Portal.tableMunicipality')) ?></th>
                    <th><?= esc(lang('Portal.tableReason')) ?></th>
                    <th><?= esc(lang('Portal.tableStatus')) ?></th>
                    <th><?= esc(lang('Portal.tableStart')) ?></th>
                    <th><?= esc(lang('Portal.tableEnd')) ?></th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="6" class="text-center text-secondary"><?= esc(lang('Portal.noResults')) ?></td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<button id="whatsappFab" class="btn btn-success whatsapp-fab d-flex align-items-center gap-2 px-3 py-2" type="button">
    <i class="bi bi-whatsapp"></i><span><?= esc(lang('Portal.whatsappLabel')) ?></span>
</button>

<script>
    const cfg = {
        locale: <?= json_encode(site_url('locale')) ?>,
        endpoint: <?= json_encode(site_url('api/public/cortes')) ?>,
        noResults: <?= json_encode(lang('Portal.noResults')) ?>,
        initialRows: <?= json_encode($initialCortes ?? []) ?>,
        whatsappNumber: <?= json_encode((string) (($whatsappBySection['cortes'] ?? $whatsappBySection['hero'] ?? '50255550001'))) ?>
    };

    document.getElementById('localeSwitcher').addEventListener('change', function () {
        window.location.href = cfg.locale + '/' + encodeURIComponent(this.value);
    });

    document.getElementById('whatsappFab').addEventListener('click', function () {
        const msg = encodeURIComponent('Hola, necesito apoyo en mantenimientos programados.');
        window.open('https://wa.me/' + cfg.whatsappNumber + '?text=' + msg, '_blank', 'noopener');
    });

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderRows(rows) {
        const tbody = document.querySelector('#cortesTable tbody');
        const pills = document.getElementById('cortesPills');

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-secondary">' + escapeHtml(cfg.noResults) + '</td></tr>';
            pills.innerHTML = '';
            return;
        }

        const visualSet = new Set();
        rows.forEach((row) => visualSet.add((row.departamento || '') + ' / ' + (row.municipio || '')));
        pills.innerHTML = Array.from(visualSet).slice(0, 20).map((item) => '<span class="map-pill">' + escapeHtml(item) + '</span>').join('');

        tbody.innerHTML = rows.map((row) => {
            return '<tr>' +
                '<td>' + escapeHtml(row.departamento || '') + '</td>' +
                '<td>' + escapeHtml(row.municipio || '') + '</td>' +
                '<td>' + escapeHtml(row.motivo || '') + '</td>' +
                '<td><span class="badge text-bg-secondary">' + escapeHtml(row.estado || '') + '</span></td>' +
                '<td>' + escapeHtml(row.fecha_inicio || '') + '</td>' +
                '<td>' + escapeHtml(row.fecha_fin || '') + '</td>' +
                '</tr>';
        }).join('');
    }

    async function loadCortes() {
        const departamento = document.getElementById('departamentoInput').value || '';
        const municipio = document.getElementById('municipioInput').value || '';
        const response = await fetch(
            cfg.endpoint + '?departamento=' + encodeURIComponent(departamento) + '&municipio=' + encodeURIComponent(municipio),
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        );
        const result = await response.json();
        renderRows(result.data || []);
    }

    document.getElementById('cortesForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        await loadCortes();
    });

    if (Array.isArray(cfg.initialRows) && cfg.initialRows.length > 0) {
        renderRows(cfg.initialRows);
    } else {
        loadCortes();
    }
</script>
</body>
</html>

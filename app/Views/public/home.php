<!doctype html>
<html lang="<?= esc($currentLocale ?? 'es') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc(lang('Portal.metaDescription')) ?>">
    <meta name="robots" content="index,follow">
    <title><?= esc(lang('Portal.metaTitle')) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/century-gothic.css') ?>" rel="stylesheet">

    <style>
        :root {
            --ink: #0e2235;
            --ink-soft: #36526b;
            --brand-a: #0f6d8f;
            --brand-b: #1a936f;
            --accent: #f2a541;
            --panel: #f6fafc;
            --line: #c7d8e5;
        }

        body {
            color: var(--ink);
            background:
                radial-gradient(circle at 85% -10%, #88d9c620 0, #88d9c600 35%),
                radial-gradient(circle at 5% 40%, #f2a5411f 0, #f2a54100 28%),
                linear-gradient(180deg, #f2f8fc 0%, #f9fdfc 100%);
        }

        h1, h2, h3, h4 {
            letter-spacing: .2px;
        }

        .portal-shell {
            overflow-x: clip;
        }

        .hero {
            background: linear-gradient(130deg, #0d4460 0%, #18687f 42%, #188269 100%);
            color: #fff;
            border-bottom-left-radius: 2rem;
            border-bottom-right-radius: 2rem;
            box-shadow: 0 24px 40px #0731473b;
            position: relative;
        }

        .hero::after {
            content: '';
            position: absolute;
            inset: auto -8% -70px auto;
            width: 260px;
            height: 260px;
            border-radius: 52% 48% 48% 52% / 40% 50% 50% 60%;
            background: #ffffff1f;
            pointer-events: none;
        }

        .glass-card {
            background: #ffffff1a;
            border: 1px solid #ffffff45;
            backdrop-filter: blur(6px);
            border-radius: 1rem;
        }

        .section-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 1rem;
            box-shadow: 0 10px 24px #082a4020;
        }

        .section-chip {
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--brand-a);
            font-weight: 700;
        }

        .sticky-tools {
            position: sticky;
            top: 0;
            z-index: 1020;
            backdrop-filter: blur(10px);
            background: #ffffffcc;
            border-bottom: 1px solid #d6e2ec;
        }

        .table-shell {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table-shell table {
            min-width: 680px;
        }

        .reveal {
            opacity: 0;
            transform: translateY(12px);
            animation: rise .6s ease forwards;
        }

        .reveal.d2 { animation-delay: .08s; }
        .reveal.d3 { animation-delay: .16s; }

        @keyframes rise {
            to {
                opacity: 1;
                transform: none;
            }
        }

        .whatsapp-fab {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 1200;
            border-radius: 999px;
            box-shadow: 0 12px 26px #0f6d8f66;
        }

        .map-pill {
            border: 1px solid #8bb9cf;
            border-radius: .8rem;
            padding: .5rem .75rem;
            background: #eff9ff;
            color: #14405a;
            font-size: .88rem;
        }

        @media (max-width: 768px) {
            .hero {
                border-bottom-left-radius: 1.3rem;
                border-bottom-right-radius: 1.3rem;
            }

            .hero h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
<div class="portal-shell" id="top">
    <header class="hero pb-5" data-whatsapp="hero">
        <div class="container py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <span class="badge text-bg-light text-dark rounded-pill px-3 py-2"><?= esc(lang('Portal.headerBadge')) ?></span>

                <div class="d-flex align-items-center gap-2">
                    <label for="localeSwitcher" class="small fw-semibold mb-0 text-white"><?= esc(lang('Portal.languageLabel')) ?></label>
                    <select id="localeSwitcher" class="form-select form-select-sm" aria-label="<?= esc(lang('Portal.languageLabel')) ?>">
                        <?php foreach (($localeOptions ?? []) as $locale): ?>
                            <option value="<?= esc($locale['code']) ?>" <?= ($locale['code'] === ($currentLocale ?? 'es')) ? 'selected' : '' ?>>
                                <?= esc($locale['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row align-items-center g-4 mt-2">
                <div class="col-12 col-lg-7">
                    <h1 class="display-5 reveal"><?= esc(lang('Portal.heroTitle')) ?></h1>
                    <p class="lead mb-4 reveal d2"><?= esc(lang('Portal.heroLead')) ?></p>

                    <form id="quickSearchForm" class="input-group input-group-lg reveal d3" role="search" aria-label="Portal quick search">
                        <input id="quickSearchInput" type="search" class="form-control" placeholder="<?= esc(lang('Portal.headerSearchPlaceholder')) ?>">
                        <button class="btn btn-warning fw-semibold" type="submit"><?= esc(lang('Portal.headerSearchButton')) ?></button>
                    </form>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="glass-card p-3 p-lg-4">
                        <h2 class="h5 mb-3"><?= esc(lang('Portal.heroCardTitle')) ?></h2>
                        <?php if (! empty($gerencias)): ?>
                            <ul class="list-group list-group-flush rounded-3 overflow-hidden">
                                <?php foreach ($gerencias as $gerencia): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?= esc($gerencia['nombre']) ?></span>
                                        <span class="badge text-bg-dark"><?= esc($gerencia['slug']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="mb-0"><?= esc(lang('Portal.heroCardEmpty')) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="sticky-tools py-2">
        <div class="container d-flex flex-wrap align-items-center gap-2">
            <a class="btn btn-sm btn-outline-primary" href="#beneficiados">DPI</a>
            <a class="btn btn-sm btn-outline-primary" href="#comunidades">Comunidades</a>
            <a class="btn btn-sm btn-outline-primary" href="#cortes"><?= esc(lang('Portal.cortesTitle')) ?></a>
        </div>
    </div>

    <main class="container py-4 py-lg-5">
        <section id="beneficiados" class="section-card p-3 p-lg-4 mb-4 reveal" data-whatsapp="beneficiados">
            <p class="section-chip mb-2">Tarifa Social</p>
            <h2 class="h3 mb-2"><?= esc(lang('Portal.beneficiadosTitle')) ?></h2>
            <p class="text-secondary mb-4"><?= esc(lang('Portal.beneficiadosHelp')) ?></p>

            <form id="dpiForm" class="row g-3" novalidate>
                <?= csrf_field() ?>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold" for="dpiInput"><?= esc(lang('Portal.dpiLabel')) ?></label>
                    <input id="dpiInput" class="form-control" maxlength="13" inputmode="numeric" pattern="[0-9]{13}" placeholder="<?= esc(lang('Portal.dpiPlaceholder')) ?>" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold" for="captchaAnswer"><?= esc(lang('Portal.captchaLabel')) ?></label>
                    <input id="captchaAnswer" class="form-control" inputmode="numeric" required>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                    <span id="captchaQuestion" class="map-pill w-100 text-center" aria-live="polite"><?= esc($captcha['question'] ?? '') ?></span>
                    <button id="captchaRefresh" type="button" class="btn btn-outline-secondary" aria-label="<?= esc(lang('Portal.captchaRefresh')) ?>"><i class="bi bi-arrow-repeat"></i></button>
                </div>
                <input id="captchaToken" type="hidden" value="<?= esc($captcha['token'] ?? '') ?>">
                <div class="col-12">
                    <button class="btn btn-primary px-4" type="submit"><?= esc(lang('Portal.consultButton')) ?></button>
                </div>
            </form>
        </section>

        <section id="comunidades" class="section-card p-3 p-lg-4 mb-4 reveal d2" data-whatsapp="hero">
            <p class="section-chip mb-2">Infraestructura</p>
            <h2 class="h3 mb-2">Comunidades GERO</h2>
            <p class="text-secondary mb-4">Consulta el estado de electrificacion por comunidad.</p>

            <form id="comunidadesForm" class="row g-3">
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold" for="comunidadInput">Codigo o nombre de comunidad</label>
                    <input id="comunidadInput" class="form-control" placeholder="Ej: COD-001 / Comunidad El Mirador" required>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" type="submit">Consultar</button>
                </div>
            </form>

            <div class="table-shell mt-4">
                <table class="table table-striped align-middle" id="comunidadesTable">
                    <thead>
                        <tr>
                            <th><?= esc(lang('Portal.tableCommunity')) ?></th>
                            <th><?= esc(lang('Portal.tablePhase')) ?></th>
                            <th><?= esc(lang('Portal.tableDepartment')) ?></th>
                            <th><?= esc(lang('Portal.tableMunicipality')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4" class="text-center text-secondary"><?= esc(lang('Portal.noResults')) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="cortes" class="section-card p-3 p-lg-4 reveal d3" data-whatsapp="cortes">
            <p class="section-chip mb-2">Continuidad del Servicio</p>
            <h2 class="h3 mb-2"><?= esc(lang('Portal.cortesTitle')) ?></h2>
            <p class="text-secondary mb-4"><?= esc(lang('Portal.cortesHelp')) ?></p>

            <form id="cortesForm" class="row g-3 mb-3">
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold" for="departamentoInput"><?= esc(lang('Portal.departmentFilter')) ?></label>
                    <input id="departamentoInput" class="form-control" placeholder="<?= esc(lang('Portal.departmentPlaceholder')) ?>">
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" type="submit"><?= esc(lang('Portal.listButton')) ?></button>
                </div>
            </form>

            <div id="cortesPills" class="d-flex flex-wrap gap-2 mb-3" aria-label="Visual de cortes por ubicacion"></div>

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

    <footer class="container py-4 text-center text-secondary small">
        <?= esc(lang('Portal.seoFooter')) ?>
    </footer>
</div>

<button id="whatsappFab" class="btn btn-success whatsapp-fab d-flex align-items-center gap-2 px-3 py-2" type="button">
    <i class="bi bi-whatsapp"></i>
    <span><?= esc(lang('Portal.whatsappLabel')) ?></span>
</button>

<div class="modal fade" id="dpiResultModal" tabindex="-1" aria-labelledby="dpiResultLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dpiResultLabel"><?= esc(lang('Portal.modalTitle')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="dpiResultText" class="mb-0"></p>
            </div>
        </div>
    </div>
</div>

<script>
    window.PortalConfig = {
        locale: <?= json_encode($currentLocale ?? 'es') ?>,
        csrfName: <?= json_encode(csrf_token()) ?>,
        csrfToken: <?= json_encode(csrf_hash()) ?>,
        cipherKey: <?= json_encode((string) ($ajaxCipherKey ?? '')) ?>,
        endpoints: {
            locale: <?= json_encode(site_url('locale')) ?>,
            captcha: <?= json_encode(site_url('api/public/captcha')) ?>,
            beneficiados: <?= json_encode(site_url('api/public/beneficiados')) ?>,
            comunidades: <?= json_encode(site_url('api/gero/comunidades/consultar')) ?>,
            cortes: <?= json_encode(site_url('api/public/cortes')) ?>,
        },
        labels: {
            noResults: <?= json_encode(lang('Portal.noResults')) ?>,
        },
        whatsappBySection: <?= json_encode($whatsappBySection ?? []) ?>,
    };

    const localeSwitcher = document.getElementById('localeSwitcher');
    const quickSearchForm = document.getElementById('quickSearchForm');
    const quickSearchInput = document.getElementById('quickSearchInput');
    const dpiForm = document.getElementById('dpiForm');
    const comunidadesForm = document.getElementById('comunidadesForm');
    const cortesForm = document.getElementById('cortesForm');
    const captchaRefreshBtn = document.getElementById('captchaRefresh');
    const captchaQuestionEl = document.getElementById('captchaQuestion');
    const captchaTokenEl = document.getElementById('captchaToken');
    const whatsappFab = document.getElementById('whatsappFab');

    let cryptoCache = null;

    localeSwitcher.addEventListener('change', () => {
        window.location.href = `${window.PortalConfig.endpoints.locale}/${encodeURIComponent(localeSwitcher.value)}`;
    });

    quickSearchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const query = (quickSearchInput.value || '').toLowerCase();
        const map = {
            'benefici': 'beneficiados',
            'dpi': 'beneficiados',
            'comunidad': 'comunidades',
            'gero': 'comunidades',
            'corte': 'cortes',
            'energia': 'cortes',
        };

        let target = 'beneficiados';
        Object.keys(map).forEach((key) => {
            if (query.includes(key)) {
                target = map[key];
            }
        });

        document.getElementById(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    captchaRefreshBtn.addEventListener('click', refreshCaptcha);

    dpiForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const dpi = (document.getElementById('dpiInput').value || '').replace(/\D+/g, '');
        const captchaAnswer = (document.getElementById('captchaAnswer').value || '').replace(/\D+/g, '');
        const captchaToken = captchaTokenEl.value || '';

        if (!window.PortalConfig.cipherKey) {
            showDpiResult('No hay llave de cifrado configurada para esta consulta segura.');
            return;
        }

        const envelope = await encryptEnvelope({
            dpi,
            captcha_answer: captchaAnswer,
            captcha_token: captchaToken,
        });

        const response = await fetch(window.PortalConfig.endpoints.beneficiados, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.PortalConfig.csrfToken,
            },
            body: JSON.stringify(envelope),
        });

        const raw = await response.json();
        const payload = await decryptEnvelope(raw);

        if (payload.captcha && payload.captcha.question) {
            captchaQuestionEl.textContent = payload.captcha.question;
            captchaTokenEl.value = payload.captcha.token;
            document.getElementById('captchaAnswer').value = '';
        }

        showDpiResult(payload.message || 'Consulta procesada.');
    });

    comunidadesForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const comunidad = document.getElementById('comunidadInput').value || '';
        const envelope = await encryptEnvelope({ term: comunidad });
        const response = await fetch(window.PortalConfig.endpoints.comunidades, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.PortalConfig.csrfToken,
            },
            body: JSON.stringify(envelope),
        });

        const raw = await response.json();
        const result = await decryptEnvelope(raw);
        renderComunidades(result?.data?.items || []);
    });

    cortesForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        await loadCortes();
    });

    async function loadCortes() {
        const departamento = document.getElementById('departamentoInput').value || '';
        const response = await fetch(`${window.PortalConfig.endpoints.cortes}?departamento=${encodeURIComponent(departamento)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const result = await response.json();
        renderCortes(result.data || []);
    }

    function renderComunidades(rows) {
        const tbody = document.querySelector('#comunidadesTable tbody');
        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-secondary">${escapeHtml(window.PortalConfig.labels.noResults)}</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.codigo_comunidad || '-')}</td>
                <td><strong>${escapeHtml(row.nombre_comunidad || '')}</strong></td>
                <td>${escapeHtml(row.municipio || '')}</td>
                <td><span class="badge text-bg-info">${escapeHtml(row.fase_actual || '')}</span></td>
                <td>${escapeHtml(row.estado_actual || '')}</td>
            </tr>
        `).join('');
    }

    function renderCortes(rows) {
        const tbody = document.querySelector('#cortesTable tbody');
        const pills = document.getElementById('cortesPills');

        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-secondary">${escapeHtml(window.PortalConfig.labels.noResults)}</td></tr>`;
            pills.innerHTML = '';
            return;
        }

        const visualSet = new Set();
        rows.forEach((row) => visualSet.add(`${row.departamento} / ${row.municipio}`));
        pills.innerHTML = Array.from(visualSet).slice(0, 20).map((item) => `<span class="map-pill">${escapeHtml(item)}</span>`).join('');

        tbody.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(row.departamento || '')}</td>
                <td>${escapeHtml(row.municipio || '')}</td>
                <td>${escapeHtml(row.motivo || '')}</td>
                <td><span class="badge text-bg-secondary">${escapeHtml(row.estado || '')}</span></td>
                <td>${escapeHtml(row.fecha_inicio || '')}</td>
                <td>${escapeHtml(row.fecha_fin || '')}</td>
            </tr>
        `).join('');
    }

    function showDpiResult(message) {
        document.getElementById('dpiResultText').textContent = message;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('dpiResultModal')).show();
    }

    async function refreshCaptcha() {
        const response = await fetch(window.PortalConfig.endpoints.captcha, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const result = await response.json();
        captchaQuestionEl.textContent = result?.data?.question || '';
        captchaTokenEl.value = result?.data?.token || '';
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function b64ToBytes(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes;
    }

    function bytesToB64(bytes) {
        let binary = '';
        for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
        return btoa(binary);
    }

    function concatBytes(...chunks) {
        const total = chunks.reduce((sum, chunk) => sum + chunk.length, 0);
        const merged = new Uint8Array(total);
        let offset = 0;
        chunks.forEach((chunk) => {
            merged.set(chunk, offset);
            offset += chunk.length;
        });
        return merged;
    }

    async function getCryptoMaterials() {
        if (cryptoCache) {
            return cryptoCache;
        }

        let material;
        if (window.PortalConfig.cipherKey.startsWith('base64:')) {
            material = b64ToBytes(window.PortalConfig.cipherKey.substring(7));
        } else {
            material = new TextEncoder().encode(window.PortalConfig.cipherKey);
        }

        if (material.length !== 32) {
            material = new Uint8Array(await crypto.subtle.digest('SHA-256', material));
        }

        const aesKey = await crypto.subtle.importKey('raw', material, 'AES-CBC', false, ['encrypt', 'decrypt']);
        const hmacKey = await crypto.subtle.importKey('raw', material, { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
        cryptoCache = { aesKey, hmacKey };
        return cryptoCache;
    }

    async function encryptEnvelope(payload) {
        const { aesKey, hmacKey } = await getCryptoMaterials();
        const iv = crypto.getRandomValues(new Uint8Array(16));
        const plain = new TextEncoder().encode(JSON.stringify(payload));
        const cipher = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-CBC', iv }, aesKey, plain));
        const mac = new Uint8Array(await crypto.subtle.sign('HMAC', hmacKey, concatBytes(iv, cipher)));

        return {
            encrypted: true,
            algorithm: 'AES-256-CBC',
            payload: bytesToB64(concatBytes(iv, mac, cipher)),
        };
    }

    async function decryptEnvelope(envelope) {
        if (!envelope || !envelope.encrypted) {
            return envelope;
        }

        const { aesKey } = await getCryptoMaterials();
        const bytes = b64ToBytes(envelope.payload || '');
        const iv = bytes.slice(0, 16);
        const cipher = bytes.slice(48);
        const plain = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, aesKey, cipher);
        return JSON.parse(new TextDecoder().decode(plain));
    }

    function setupWhatsappContext() {
        const sectionNumbers = window.PortalConfig.whatsappBySection || {};
        const defaultNumber = sectionNumbers.hero || '50255550001';

        function updateWhatsAppForSection(sectionName) {
            const number = sectionNumbers[sectionName] || defaultNumber;
            whatsappFab.dataset.phone = number;
        }

        whatsappFab.addEventListener('click', () => {
            const number = whatsappFab.dataset.phone || defaultNumber;
            const message = encodeURIComponent('Hola, necesito apoyo con una consulta del portal publico INDE.');
            window.open(`https://wa.me/${number}?text=${message}`, '_blank', 'noopener');
        });

        updateWhatsAppForSection('hero');

        const sections = document.querySelectorAll('section[data-whatsapp], header[data-whatsapp]');
        const observer = new IntersectionObserver((entries) => {
            entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)
                .slice(0, 1)
                .forEach((entry) => {
                    updateWhatsAppForSection(entry.target.dataset.whatsapp || 'hero');
                });
        }, { threshold: [0.25, 0.5, 0.75] });

        sections.forEach((section) => observer.observe(section));
    }

    loadCortes();
    setupWhatsappContext();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

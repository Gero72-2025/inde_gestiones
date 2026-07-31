<!doctype html>
<html lang="<?= esc($currentLocale ?? 'es') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc(lang('Portal.metaDescription')) ?>">
    <meta name="robots" content="index,follow">
    <title><?= esc(lang('Portal.beneficiadosTitle')) ?> | <?= esc(lang('Portal.metaTitle')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f9fd; }
        .table-shell { overflow-x: auto; }
        .hero { background: #0f6d8f; color: #fff; }
        .whatsapp-fab { position: fixed; right: 1rem; bottom: 1rem; border-radius: 999px; z-index: 1200; }
    </style>
</head>
<body>
<header class="hero py-3">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-light" href="<?= esc(site_url('/')) ?>"><?= esc(lang('Portal.navHome')) ?></a>
            <a class="btn btn-sm btn-warning" href="<?= esc(site_url('consulta/beneficiados')) ?>"><?= esc(lang('Portal.navBeneficiados')) ?></a>
            <a class="btn btn-sm btn-light" href="<?= esc(site_url('consulta/comunidades')) ?>">Comunidades</a>
            <a class="btn btn-sm btn-light" href="<?= esc(site_url('consulta/cortes')) ?>"><?= esc(lang('Portal.navCortes')) ?></a>
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
        <h1 class="h3 mb-2"><?= esc(lang('Portal.beneficiadosTitle')) ?></h1>
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
                <span id="captchaQuestion" class="badge text-bg-light border w-100 text-dark py-2"><?= esc($captcha['question'] ?? '') ?></span>
                <button id="captchaRefresh" type="button" class="btn btn-outline-secondary" aria-label="<?= esc(lang('Portal.captchaRefresh')) ?>"><i class="bi bi-arrow-repeat"></i></button>
            </div>
            <input id="captchaToken" type="hidden" value="<?= esc($captcha['token'] ?? '') ?>">
            <div class="col-12">
                <button class="btn btn-primary px-4" type="submit"><?= esc(lang('Portal.consultButton')) ?></button>
            </div>
        </form>
    </section>
</main>

<button id="whatsappFab" class="btn btn-success whatsapp-fab d-flex align-items-center gap-2 px-3 py-2" type="button">
    <i class="bi bi-whatsapp"></i><span><?= esc(lang('Portal.whatsappLabel')) ?></span>
</button>

<div class="modal fade" id="dpiResultModal" tabindex="-1" aria-labelledby="dpiResultLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dpiResultLabel"><?= esc(lang('Portal.modalTitle')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><p id="dpiResultText" class="mb-0"></p></div>
        </div>
    </div>
</div>

<script>
    window.PortalConfig = {
        csrfToken: <?= json_encode(csrf_hash()) ?>,
        cipherKey: <?= json_encode((string) ($ajaxCipherKey ?? '')) ?>,
        endpoints: {
            locale: <?= json_encode(site_url('locale')) ?>,
            captcha: <?= json_encode(site_url('api/public/captcha')) ?>,
            beneficiados: <?= json_encode(site_url('api/public/beneficiados')) ?>
        },
        whatsappNumber: <?= json_encode((string) (($whatsappBySection['beneficiados'] ?? $whatsappBySection['hero'] ?? '50255550001'))) ?>
    };

    document.getElementById('localeSwitcher').addEventListener('change', function () {
        window.location.href = window.PortalConfig.endpoints.locale + '/' + encodeURIComponent(this.value);
    });

    document.getElementById('whatsappFab').addEventListener('click', function () {
        const msg = encodeURIComponent('Hola, necesito apoyo en consulta de tarifa social.');
        window.open('https://wa.me/' + window.PortalConfig.whatsappNumber + '?text=' + msg, '_blank', 'noopener');
    });

    let cryptoCache = null;

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
        if (cryptoCache) return cryptoCache;
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
        return { encrypted: true, algorithm: 'AES-256-CBC', payload: bytesToB64(concatBytes(iv, mac, cipher)) };
    }

    async function decryptEnvelope(envelope) {
        if (!envelope || !envelope.encrypted) return envelope;
        const { aesKey } = await getCryptoMaterials();
        const bytes = b64ToBytes(envelope.payload || '');
        const iv = bytes.slice(0, 16);
        const cipher = bytes.slice(48);
        const plain = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, aesKey, cipher);
        return JSON.parse(new TextDecoder().decode(plain));
    }

    async function refreshCaptcha() {
        const response = await fetch(window.PortalConfig.endpoints.captcha, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const result = await response.json();
        document.getElementById('captchaQuestion').textContent = result?.data?.question || '';
        document.getElementById('captchaToken').value = result?.data?.token || '';
        document.getElementById('captchaAnswer').value = '';
    }

    function showResult(message) {
        document.getElementById('dpiResultText').textContent = message;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('dpiResultModal')).show();
    }

    document.getElementById('captchaRefresh').addEventListener('click', refreshCaptcha);

    document.getElementById('dpiForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const dpi = (document.getElementById('dpiInput').value || '').replace(/\D+/g, '');
        const captchaAnswer = (document.getElementById('captchaAnswer').value || '').replace(/\D+/g, '');
        const captchaToken = document.getElementById('captchaToken').value || '';

        const envelope = await encryptEnvelope({ dpi, captcha_answer: captchaAnswer, captcha_token: captchaToken });
        const response = await fetch(window.PortalConfig.endpoints.beneficiados, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.PortalConfig.csrfToken
            },
            body: JSON.stringify(envelope)
        });

        const raw = await response.json();
        const payload = await decryptEnvelope(raw);

        if (payload.captcha && payload.captcha.question) {
            document.getElementById('captchaQuestion').textContent = payload.captcha.question;
            document.getElementById('captchaToken').value = payload.captcha.token;
            document.getElementById('captchaAnswer').value = '';
        }

        showResult(payload.message || 'Consulta procesada.');
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

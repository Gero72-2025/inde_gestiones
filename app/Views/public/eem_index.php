<?php
$formularios = (array) ($formularios ?? []);
$camposPorFormulario = (array) ($camposPorFormulario ?? []);
$eemListados = (array) ($eemListados ?? []);

$renderField = static function (array $field, string $formCode): string {
    $slug = (string) ($field['slug'] ?? '');
    $type = strtolower((string) ($field['tipo'] ?? 'text'));
    $label = (string) ($field['etiqueta'] ?? $field['nombre'] ?? $slug);
    $help = trim((string) ($field['ayuda'] ?? ''));
    $required = (int) ($field['obligatorio'] ?? 0) === 1 ? 'required' : '';
    $name = esc($slug);

    if ($slug === '' || $slug === 'empresa_nombre') {
        return '';
    }

    return match ($type) {
        'textarea' => '<div class="col-12"><label class="form-label fw-semibold">' . esc($label) . '</label><textarea class="form-control" name="' . $name . '" rows="3" ' . $required . '></textarea>' . ($help !== '' ? '<div class="form-text">' . esc($help) . '</div>' : '') . '</div>',
        'file' => '<div class="col-12"><label class="form-label fw-semibold">' . esc($label) . '</label><input class="form-control" type="file" name="' . $name . '" ' . $required . '>' . ($help !== '' ? '<div class="form-text">' . esc($help) . '</div>' : '') . '</div>',
        default => '<div class="col-12 col-md-6"><label class="form-label fw-semibold">' . esc($label) . '</label><input class="form-control" type="' . esc($type) . '" name="' . $name . '" ' . $required . '>' . ($help !== '' ? '<div class="form-text">' . esc($help) . '</div>' : '') . '</div>',
    };
};

$formRoutes = [
    'EEM1' => 'nueva-conexion',
    'EEM2' => 'gestionar-expediente',
    'EEM3' => 'capacitacion-tecnica',
];

$companyOptions = '';
foreach ($eemListados as $company) {
    $companyOptions .= '<option value="' . (int) ($company['id'] ?? 0) . '">' . esc((string) ($company['nombre'] ?? '')) . '</option>';
}
?>

<style>
    .eem-shell {
        background: linear-gradient(135deg, rgba(15,109,143,.08), rgba(244,163,64,.12));
        border: 1px solid rgba(15,109,143,.12);
        border-radius: 1.5rem;
    }
    .eem-option {
        border: 0;
        border-radius: 1.25rem;
        box-shadow: 0 16px 32px rgba(10, 40, 60, .08);
    }
    .eem-option.active {
        outline: 3px solid rgba(15,109,143,.18);
    }
    .eem-section-hidden { display: none; }
</style>

<div class="eem-shell p-3 p-lg-4 mb-4 animate__animated animate__fadeIn">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <p class="text-uppercase small mb-1 opacity-75"><?= esc(__('eem.hero.eyebrow', 'Empresa Eléctrica Municipal')) ?></p>
            <h2 class="h3 mb-1"><?= esc(__('eem.hero.title', 'Selecciona empresa, trámite y continúa')) ?></h2>
            <p class="mb-0 text-secondary"><?= esc(__('eem.hero.subtitle', 'La constancia se genera automáticamente y el código de referencia queda listo para seguimiento.')) ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge text-bg-dark"><?= esc(__('eem.hero.badge.selector', 'Selector EEM')) ?></span>
            <span class="badge text-bg-warning text-dark"><?= esc(__('eem.hero.badge.constancia', 'Constancia PDF')) ?></span>
            <span class="badge text-bg-success"><?= esc(__('eem.hero.badge.estado', 'Estado en línea')) ?></span>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card eem-option h-100 animate__animated animate__fadeInLeft">
            <div class="card-body p-4">
                <h3 class="h5 mb-3"><?= esc(__('eem.step1.title', '1. Empresa eléctrica')) ?></h3>
                <label class="form-label fw-semibold"><?= esc(__('eem.step1.selectLabel', 'Selecciona una empresa:')) ?></label>
                <select id="eemCompanySelect" class="form-select mb-3">
                    <option value=""><?= esc(__('eem.step1.selectPlaceholder', 'Selecciona...')) ?></option>
                    <?= $companyOptions ?>
                </select>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" type="button" onclick="showEemForm('EEM1')"><?= esc(__('eem.step1.button.eem1', 'Solicitud de Nueva Conexión Punto de Entrega en alta tensión')) ?></button>
                    <button class="btn btn-outline-primary" type="button" onclick="showEemForm('EEM2')"><?= esc(__('eem.step1.button.eem2', 'Gestionar / Seguimiento de Expediente')) ?></button>
                    <button class="btn btn-outline-primary" type="button" onclick="showEemForm('EEM3')"><?= esc(__('eem.step1.button.eem3', 'Requerimiento de Capacitación Técnica para técnicos linieros ')) ?></button>
                </div>
                <hr class="my-4">
                <h3 class="h6 mb-2"><?= esc(__('eem.search.title', 'Buscar estado')) ?></h3>
                <div class="input-group">
                    <input id="eemSearchTerm" type="text" class="form-control" placeholder="<?= esc(__('eem.search.placeholder', 'Código de referencia o DPI')) ?>">
                    <button class="btn btn-dark" type="button" onclick="searchEemStatus()"><?= esc(__('eem.search.button', 'Buscar')) ?></button>
                </div>
                <div id="eemSearchResult" class="small text-secondary mt-3"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        <?php foreach (['EEM1' => __('eem.form.eem1.title', 'Nueva Conexión'), 'EEM2' => __('eem.form.eem2.title', 'Gestionar Expediente'), 'EEM3' => __('eem.form.eem3.title', 'Capacitación Técnica')] as $code => $label): ?>
            <div id="eemForm<?= esc($code) ?>" class="card eem-option eem-section-hidden mb-4 animate__animated animate__fadeInUp">
                <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-uppercase small text-secondary mb-1"><?= esc($code) ?></p>
                        <h3 class="h4 mb-0"><?= esc($label) ?></h3>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="hideEemForm('<?= esc($code) ?>')"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="card-body p-4">
                    <?php if (! empty($formularios)): ?>
                        <form method="post" action="<?= esc(site_url('api/ecoe/eem/' . ($formRoutes[$code] ?? 'estado'))) ?>" enctype="multipart/form-data" target="_blank" class="row g-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="empresa_electrica_id" id="empresaInput<?= esc($code) ?>" value="">
                            <?php foreach (($camposPorFormulario[$code] ?? []) as $field): ?>
                                <?= $renderField((array) $field, $code) ?>
                            <?php endforeach; ?>
                            <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                                <button class="btn btn-outline-secondary" type="button" onclick="hideEemForm('<?= esc($code) ?>')"><?= esc(__('gu.form.cancelButton', 'Cancelar')) ?></button>
                                <button class="btn btn-primary" type="submit"><i class="bi bi-file-earmark-pdf me-1"></i><?= esc(__('eem.form.submitButton', 'Generar constancia')) ?></button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0"><?= esc(__('eem.form.unavailable', 'No hay formularios EEM configurados todavía.')) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function syncCompanyValue() {
    const companySelect = document.getElementById('eemCompanySelect');
    const value = companySelect ? companySelect.value : '';
    ['EEM1', 'EEM2', 'EEM3'].forEach(code => {
        const field = document.getElementById('empresaInput' + code);
        if (field) {
            field.value = value;
        }
    });
}

function showEemForm(code) {
    syncCompanyValue();
    ['EEM1', 'EEM2', 'EEM3'].forEach(other => {
        const el = document.getElementById('eemForm' + other);
        if (el) {
            el.classList.add('eem-section-hidden');
        }
    });
    const target = document.getElementById('eemForm' + code);
    if (target) {
        target.classList.remove('eem-section-hidden');
    }
}

function hideEemForm(code) {
    const target = document.getElementById('eemForm' + code);
    if (target) {
        target.classList.add('eem-section-hidden');
    }
}

async function searchEemStatus() {
    const term = document.getElementById('eemSearchTerm').value.trim();
    const box = document.getElementById('eemSearchResult');
    if (!term) {
        box.innerHTML = '<span class="text-danger">' + <?= json_encode(__('eem.search.errorEmpty', 'Ingresa un código o DPI.')) ?> + '</span>';
        return;
    }

    box.innerHTML = <?= json_encode(__('eem.search.loading', 'Consultando...')) ?>;
    const response = await fetch(<?= json_encode(site_url('api/ecoe/eem/estado')) ?> + '?term=' + encodeURIComponent(term), { credentials: 'same-origin' });
    const data = await response.json();

    if (!data.ok || !Array.isArray(data.records) || data.records.length === 0) {
        box.innerHTML = '<span class="text-danger">' + <?= json_encode(__('eem.search.notFound', 'No se encontró un trámite con ese dato.')) ?> + '</span>';
        return;
    }

    box.innerHTML = data.records.map(record => {
        return '<div class="border rounded-3 p-3 mb-2 bg-light">'
            + '<div class="fw-semibold">' + escapeHtml(record.codigo_referencia || '-') + '</div>'
            + '<div class="small text-secondary">' + escapeHtml(record.formulario_nombre || '-') + '</div>'
            + '<div class="small">' + <?= json_encode(__('eem.search.resultStatusLabel', 'Estado:')) ?> + ' ' + escapeHtml(record.estado_tramite || 'recibido') + '</div>'
            + '<div class="small">DPI: ' + escapeHtml(record.dpi || '-') + '</div>'
            + '</div>';
    }).join('');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

document.getElementById('eemCompanySelect')?.addEventListener('change', syncCompanyValue);
syncCompanyValue();
</script>
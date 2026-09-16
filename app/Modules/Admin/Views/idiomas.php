<?php
$supportedLocales = (array) ($supportedLocales ?? []);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-translate me-2"></i>Idiomas</h2>
</div>

<div class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTraduccionModal">
        <i class="bi bi-plus-lg me-1"></i> Nueva traduccion
    </button>
    <button class="btn btn-outline-danger" id="btnClearTranslationsCache">
        <i class="bi bi-arrow-repeat me-1"></i> Limpiar cache de traducciones
    </button>

    <div class="ms-auto d-flex flex-wrap gap-2">
        <select class="form-select form-select-sm" id="filterLang" style="min-width: 9rem;">
            <option value="">Todos los idiomas</option>
            <?php foreach ($supportedLocales as $locale): ?>
                <option value="<?= esc($locale) ?>"><?= esc(strtoupper($locale)) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select form-select-sm" id="filterAutodiscovered" style="min-width: 11rem;">
            <option value="">Todas las claves</option>
            <option value="1">Solo autodetectadas</option>
        </select>
        <input type="search" class="form-control form-control-sm" id="filterSearch" placeholder="Buscar clave o texto..." style="min-width: 14rem;">
        <button class="btn btn-outline-secondary btn-sm" id="btnFilterTraducciones"><i class="bi bi-search"></i></button>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createTraduccionModal" tabindex="-1" aria-labelledby="createTraduccionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="traduccionForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="createTraduccionModalLabel"><i class="bi bi-translate me-2"></i>Nueva Traduccion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Idioma <span class="text-danger">*</span></label>
                        <select class="form-select" name="lang_code" required>
                            <?php foreach ($supportedLocales as $locale): ?>
                                <option value="<?= esc($locale) ?>"><?= esc(strtoupper($locale)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label fw-semibold">Clave <span class="text-danger">*</span></label>
                        <input class="form-control" name="translation_key" placeholder="ej: home.hero.title" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Texto traducido</label>
                        <textarea class="form-control" name="translation_value" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editTraduccionModal" tabindex="-1" aria-labelledby="editTraduccionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editTraduccionForm" novalidate>
                <input type="hidden" id="editTraduccionId" name="id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTraduccionModalLabel"><i class="bi bi-pencil-square me-2"></i>Editar Traduccion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Idioma <span class="text-danger">*</span></label>
                        <select class="form-select" id="editLangCode" name="lang_code" required>
                            <?php foreach ($supportedLocales as $locale): ?>
                                <option value="<?= esc($locale) ?>"><?= esc(strtoupper($locale)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label fw-semibold">Clave <span class="text-danger">*</span></label>
                        <input class="form-control" id="editTranslationKey" name="translation_key" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Texto traducido</label>
                        <textarea class="form-control" id="editTranslationValue" name="translation_value" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle" id="traduccionesTable">
        <thead>
            <tr>
                <th style="width:60px">ID</th>
                <th style="width:90px">Idioma</th>
                <th>Clave</th>
                <th>Texto</th>
                <th style="width:120px">Origen</th>
                <th style="width:140px">Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
<div id="traduccionesPager" class="d-flex justify-content-between align-items-center mt-2 mb-1"></div>

<script>
let traduccionesData = [];
let traduccionesPage = 1;
const traduccionesPerPage = 15;

function escAttr(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function renderTraducciones() {
    const start = (traduccionesPage - 1) * traduccionesPerPage;
    const page = traduccionesData.slice(start, start + traduccionesPerPage);
    const tbody = document.querySelector('#traduccionesTable tbody');

    if (page.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-secondary py-4">No hay traducciones registradas.</td></tr>';
        renderTraduccionesPager();
        return;
    }

    tbody.innerHTML = page.map(row => {
        const badge = Number(row.is_autodiscovered) === 1 ? 'bg-warning text-dark' : 'bg-success';
        const origen = Number(row.is_autodiscovered) === 1 ? 'Autodetectada' : 'Manual';
        return `<tr>
            <td>${row.id}</td>
            <td><span class="badge bg-secondary">${escAttr(String(row.lang_code || '').toUpperCase())}</span></td>
            <td><code>${escAttr(row.translation_key)}</code></td>
            <td class="text-truncate" style="max-width: 320px;" title="${escAttr(row.translation_value)}">${escAttr(row.translation_value)}</td>
            <td><span class="badge ${badge}">${origen}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1"
                    data-id="${row.id}"
                    data-lang="${escAttr(row.lang_code)}"
                    data-key="${escAttr(row.translation_key)}"
                    data-value="${escAttr(row.translation_value)}"
                    onclick="openEditTraduccion(this)">Editar</button>
                <button class="btn btn-sm btn-outline-danger"
                    data-id="${row.id}"
                    data-key="${escAttr(row.translation_key)}"
                    onclick="deleteTraduccion(this)">Eliminar</button>
            </td>
        </tr>`;
    }).join('');
    renderTraduccionesPager();
}

function renderTraduccionesPager() {
    const total = traduccionesData.length;
    const pages = Math.ceil(total / traduccionesPerPage);
    const pager = document.getElementById('traduccionesPager');
    if (pages <= 1) { pager.innerHTML = ''; return; }
    let btns = '';
    for (let i = 1; i <= pages; i++) {
        btns += `<button class="btn btn-sm ${i === traduccionesPage ? 'btn-primary' : 'btn-outline-secondary'} me-1" onclick="goTraduccionesPage(${i})">${i}</button>`;
    }
    const from = Math.min((traduccionesPage - 1) * traduccionesPerPage + 1, total);
    const to   = Math.min(traduccionesPage * traduccionesPerPage, total);
    pager.innerHTML = `<small class="text-muted">Mostrando ${from}&ndash;${to} de ${total}</small><div>${btns}</div>`;
}

function goTraduccionesPage(p) { traduccionesPage = p; renderTraducciones(); }

async function loadTraducciones() {
    const params = new URLSearchParams();
    const lang = document.getElementById('filterLang').value;
    const search = document.getElementById('filterSearch').value.trim();
    const autodiscovered = document.getElementById('filterAutodiscovered').value;

    if (lang) params.set('lang', lang);
    if (search) params.set('search', search);
    if (autodiscovered) params.set('autodiscovered', autodiscovered);

    const result = await fetchEncrypted(`<?= site_url('admin/idiomas/list') ?>?${params.toString()}`);
    traduccionesData = result?.data?.translations || [];
    traduccionesPage = 1;
    renderTraducciones();
}

function openEditTraduccion(btn) {
    document.getElementById('editTraduccionId').value    = btn.dataset.id;
    document.getElementById('editLangCode').value         = btn.dataset.lang;
    document.getElementById('editTranslationKey').value   = btn.dataset.key;
    document.getElementById('editTranslationValue').value = btn.dataset.value;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editTraduccionModal')).show();
}

async function deleteTraduccion(btn) {
    confirmAction(
        `¿Eliminar la traduccion "${btn.dataset.key}"? Esta accion no se puede deshacer.`,
        async () => {
            const body = new FormData();
            body.append('id', btn.dataset.id);
            const result = await fetchEncrypted('<?= site_url('admin/idiomas/delete') ?>', {
                method: 'POST',
                body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            notify(result?.data?.message || 'Eliminada.', result?.data?.message ? 'success' : 'danger');
            await loadTraducciones();
        },
        { title: 'Eliminar Traduccion', danger: true }
    );
}

document.getElementById('traduccionForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await fetchEncrypted('<?= site_url('admin/idiomas/save') ?>', {
        method: 'POST',
        body: new FormData(e.target),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if ((result?.data?.message || '').toLowerCase().includes('ya existe') || (result?.status && result.status >= 400)) {
        notify(result.data.message, 'danger', 0);
    } else {
        notify(result?.data?.message || 'Traduccion creada correctamente.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('createTraduccionModal'))?.hide();
        e.target.reset();
        await loadTraducciones();
    }
});

document.getElementById('editTraduccionForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await fetchEncrypted('<?= site_url('admin/idiomas/save') ?>', {
        method: 'POST',
        body: new FormData(e.target),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if ((result?.data?.message || '').toLowerCase().includes('ya existe')) {
        notify(result.data.message, 'danger', 0);
    } else {
        notify(result?.data?.message || 'Traduccion actualizada.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('editTraduccionModal'))?.hide();
        await loadTraducciones();
    }
});

document.getElementById('btnFilterTraducciones').addEventListener('click', loadTraducciones);
document.getElementById('filterSearch').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); loadTraducciones(); } });
document.getElementById('filterLang').addEventListener('change', loadTraducciones);
document.getElementById('filterAutodiscovered').addEventListener('change', loadTraducciones);

document.getElementById('btnClearTranslationsCache').addEventListener('click', async () => {
    const result = await fetchEncrypted('<?= site_url('admin/idiomas/clear-cache') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    notify(result?.data?.message || 'Cache limpiado.', 'success');
});

loadTraducciones();
</script>

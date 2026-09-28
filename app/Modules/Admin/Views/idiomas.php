<?php
$languages = (array) ($languages ?? []);
$activeLanguages = array_values(array_filter($languages, static fn (array $language): bool => (int) ($language['is_active'] ?? 0) === 1 && (int) ($language['is_visible'] ?? 0) === 1));
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
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#languagesModal" id="btnManageLanguages">
        <i class="bi bi-list-check me-1"></i> Catalogo de lenguas
    </button>
    <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importTranslationsModal">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Importar y Exportar
    </button>

    <div class="ms-auto d-flex flex-wrap gap-2">
        <select class="form-select form-select-sm" id="filterLang" style="min-width: 9rem;">
            <option value="">Todos los idiomas</option>
            <?php foreach ($activeLanguages as $language): ?>
                <option value="<?= esc($language['code']) ?>"><?= esc($language['name']) ?></option>
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
                            <?php foreach ($activeLanguages as $language): ?>
                                <option value="<?= esc($language['code']) ?>"><?= esc($language['name']) ?></option>
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
                            <?php foreach ($activeLanguages as $language): ?>
                                <option value="<?= esc($language['code']) ?>"><?= esc($language['name']) ?></option>
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

<div class="modal fade" id="languagesModal" tabindex="-1" aria-labelledby="languagesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="languagesModalLabel"><i class="bi bi-list-check me-2"></i>Catalogo de lenguas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="languageForm" class="row g-2 align-items-end mb-3" novalidate>
                    <input type="hidden" name="id" id="languageId">
                    <div class="col-md-3"><label class="form-label">Codigo</label><input class="form-control" name="code" id="languageCode" maxlength="10" required></div>
                    <div class="col-md-5"><label class="form-label">Nombre</label><input class="form-control" name="name" id="languageName" maxlength="100" required></div>
                    <div class="col-md-2"><label class="form-label">Estado</label><select class="form-select" name="is_active" id="languageActive"><option value="1">Activa</option><option value="0">Inactiva</option></select></div>
                    <div class="col-md-2"><label class="form-label">Visibilidad</label><select class="form-select" name="is_visible" id="languageVisible"><option value="1">Visible</option><option value="0">Oculta</option></select></div>
                    <div class="col-md-12 d-flex justify-content-end gap-1"><button class="btn btn-primary" type="submit">Guardar</button><button class="btn btn-outline-secondary" type="button" id="btnResetLanguage">Limpiar</button></div>
                </form>
                <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Codigo</th><th>Nombre</th><th>Estado</th><th>Visibilidad</th><th class="text-end">Acciones</th></tr></thead><tbody id="languagesTableBody"></tbody></table></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importTranslationsModal" tabindex="-1" aria-labelledby="importTranslationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <div class="modal-header"><h5 class="modal-title" id="importTranslationsModalLabel"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Importar y Exportar traducciones</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
            <p class="text-secondary small">La importacion acepta unicamente una plantilla XLSX generada desde este modulo y valida todos sus encabezados antes de modificar datos.</p>
            <div class="d-flex gap-2 mb-3"><a class="btn btn-outline-success" href="<?= site_url('admin/idiomas/export') ?>"><i class="bi bi-download me-1"></i>Exportar XLSX</a></div>
            <form id="importTranslationsForm"><label class="form-label fw-semibold" for="translationFile">Plantilla XLSX</label><input class="form-control" type="file" name="translation_file" id="translationFile" accept=".xlsx,.xls" required><button class="btn btn-primary mt-3" type="submit"><i class="bi bi-upload me-1"></i>Importar</button></form>
        </div>
    </div></div>
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
<div id="traduccionesPager" class="d-flex flex-column align-items-center gap-2 mt-3 mb-1"></div>

<script>
let traduccionesData = [];
let traduccionesPage = 1;
const traduccionesPerPage = 15;
let languagesData = <?= json_encode($languages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

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
    const visible = new Set([1, pages, traduccionesPage - 1, traduccionesPage, traduccionesPage + 1]);
    const ordered = [...visible].filter(page => page >= 1 && page <= pages).sort((a, b) => a - b);
    let btns = '';
    let previous = 0;
    ordered.forEach(page => {
        if (previous && page - previous > 1) btns += '<span class="px-2 text-muted">&hellip;</span>';
        btns += `<button class="btn btn-sm ${page === traduccionesPage ? 'btn-primary' : 'btn-outline-secondary'}" onclick="goTraduccionesPage(${page})">${page}</button>`;
        previous = page;
    });
    const from = Math.min((traduccionesPage - 1) * traduccionesPerPage + 1, total);
    const to   = Math.min(traduccionesPage * traduccionesPerPage, total);
    pager.innerHTML = `<small class="text-muted">Mostrando ${from}&ndash;${to} de ${total}</small><div class="d-flex align-items-center gap-1">${btns}</div>`;
}

function goTraduccionesPage(p) { traduccionesPage = p; renderTraducciones(); }

function renderLanguages() {
    const body = document.getElementById('languagesTableBody');
    body.innerHTML = languagesData.map(language => `<tr>
        <td><code>${escAttr(language.code)}</code></td>
        <td>${escAttr(language.name)}</td>
        <td><span class="badge ${Number(language.is_active) === 1 ? 'bg-success' : 'bg-secondary'}">${Number(language.is_active) === 1 ? 'Activa' : 'Inactiva'}</span></td>
        <td><span class="badge ${Number(language.is_visible) === 1 ? 'bg-primary' : 'bg-warning text-dark'}">${Number(language.is_visible) === 1 ? 'Visible' : 'Oculta'}</span></td>
        <td class="text-end"><button class="btn btn-sm btn-outline-primary me-1" onclick="editLanguage(${Number(language.id)})">Editar</button><button class="btn btn-sm btn-outline-danger" onclick="deleteLanguage(${Number(language.id)})">Eliminar</button></td>
    </tr>`).join('');
}

function editLanguage(id) {
    const language = languagesData.find(item => Number(item.id) === id);
    if (!language) return;
    document.getElementById('languageId').value = language.id;
    document.getElementById('languageCode').value = language.code;
    document.getElementById('languageName').value = language.name;
    document.getElementById('languageActive').value = language.is_active;
    document.getElementById('languageVisible').value = language.is_visible;
}

function resetLanguageForm() {
    document.getElementById('languageForm').reset();
    document.getElementById('languageId').value = '';
}

async function loadLanguages() {
    const result = await fetchEncrypted('<?= site_url('admin/idiomas/languages') ?>');
    languagesData = result?.data?.languages || languagesData;
    renderLanguages();
}

async function deleteLanguage(id) {
    const language = languagesData.find(item => Number(item.id) === id);
    if (!language) return;
    confirmAction(`¿Eliminar la lengua "${language.name}"?`, async () => {
        const body = new FormData();
        body.append('id', id);
        const result = await fetchEncrypted('<?= site_url('admin/idiomas/languages/delete') ?>', { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        notify(result?.data?.message || 'Operacion completada.', result?.status >= 400 ? 'danger' : 'success');
        await loadLanguages();
    }, { title: 'Eliminar lengua', danger: true });
}

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

document.getElementById('btnManageLanguages').addEventListener('click', loadLanguages);
document.getElementById('btnResetLanguage').addEventListener('click', resetLanguageForm);
document.getElementById('languageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await fetchEncrypted('<?= site_url('admin/idiomas/languages/save') ?>', {
        method: 'POST', body: new FormData(e.target), headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (result?.status >= 400 || (result?.data?.message || '').toLowerCase().includes('ya existe')) {
        notify(result?.data?.message || 'No se pudo guardar la lengua.', 'danger', 0);
        return;
    }
    notify(result?.data?.message || 'Lengua guardada.', 'success');
    resetLanguageForm();
    await loadLanguages();
    window.location.reload();
});

document.getElementById('importTranslationsForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await fetchEncrypted('<?= site_url('admin/idiomas/import') ?>', {
        method: 'POST', body: new FormData(e.target), headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (result?.status >= 400 || !result?.data?.message?.toLowerCase().includes('completada')) {
        notify(result?.data?.message || 'No se pudo importar la plantilla.', 'danger', 0);
        return;
    }
    notify(result.data.message, 'success');
    bootstrap.Modal.getInstance(document.getElementById('importTranslationsModal'))?.hide();
    e.target.reset();
    await loadTraducciones();
});

document.getElementById('btnClearTranslationsCache').addEventListener('click', async () => {
    const result = await fetchEncrypted('<?= site_url('admin/idiomas/clear-cache') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    notify(result?.data?.message || 'Cache limpiado.', 'success');
});

loadTraducciones();
renderLanguages();
</script>

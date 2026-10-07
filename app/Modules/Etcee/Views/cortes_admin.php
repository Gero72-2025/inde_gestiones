<style>
/* ── ETCEE Cortes Admin – UI Enhancement ─────────────────────────── */
.etcee-header {
    background: linear-gradient(135deg, #1a56db 0%, #0e3c8a 100%);
    border-radius: 1rem;
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 6px 24px rgba(13,110,253,.22);
}
.filter-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: .75rem;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    margin-bottom: 1.5rem;
}
.filter-card .form-label { font-size: .825rem; }
.table-card { border-radius: .75rem; overflow: hidden; box-shadow: 0 2px 14px rgba(0,0,0,.08); }
.table-card .table { margin-bottom: 0; }
.table-card thead { background: #eef2ff; }
.table-card thead th {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #4a5980;
    border-bottom: 2px solid #d0d7ee;
    padding: .85rem 1rem;
}
.corte-row td { padding: .8rem 1rem; vertical-align: middle; }
.corte-row { transition: background .12s; }
.corte-row:hover td { background: #f0f5ff !important; }
.corte-row td:first-child { border-left: 4px solid transparent; }
.corte-row.estado-programado td:first-child { border-left-color: #0d6efd; }
.corte-row.estado-activo td:first-child { border-left-color: #198754; }
.corte-row.estado-finalizado td:first-child { border-left-color: #6c757d; }
.corte-row.estado-cancelado td:first-child { border-left-color: #dc3545; }
.empty-state { padding: 3rem 1rem; }
.empty-state .empty-icon { font-size: 3rem; color: #ced4da; }
.modal-section-divider {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #6c757d;
    padding-top: .5rem;
    border-top: 1px solid #e9ecef;
    margin-top: .25rem;
    margin-bottom: -.25rem;
}
.modal-section-divider:first-child { border-top: none; padding-top: 0; }
.location-checklist {
    max-height: 200px;
    overflow-y: auto;
    padding: .5rem .75rem;
    border: 1px solid #dee2e6;
    border-radius: .5rem;
    background: #fff;
    scrollbar-gutter: stable;
}
.location-checklist .form-check { margin: 0; padding: .35rem .45rem .35rem 1.9rem; border-radius: .3rem; }
.location-checklist .form-check:hover { background: #f1f6ff; }
.location-checklist .form-check-label { display: block; cursor: pointer; }
.location-checklist-empty { padding: .35rem .45rem; color: #6c757d; font-size: .875rem; }
#corteModal .modal-header {
    background: linear-gradient(90deg, #1a56db 0%, #0e3c8a 100%);
    color: #fff;
    border-radius: .35rem .35rem 0 0;
}
#corteModal .modal-header .btn-close { filter: invert(1); }
#corteModal .modal-header .modal-title i { opacity: .85; }
</style>

<div class="etcee-header d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-white bg-opacity-10 p-3" style="width:3.2rem;height:3.2rem;">
            <i class="bi bi-lightning-charge-fill text-warning fs-4"></i>
        </div>
        <div>
            <h2 class="h4 mb-0 text-white fw-bold">Registro de mantenimientos ETCEE</h2>
            <small class="text-white-50">Gestión de interrupciones y cortes de energía eléctrica</small>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= esc(site_url('admin/etcee/estados-mantenimiento')) ?>" class="btn btn-outline-light shadow-sm fw-semibold"><i class="bi bi-palette2 me-1"></i>Estados</a>
        <button type="button" class="btn btn-outline-light shadow-sm fw-semibold" id="openMaintenanceImportBtn"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Carga masiva</button>
        <button class="btn btn-light shadow-sm px-4 fw-semibold" id="newEventBtn">
            <i class="bi bi-plus-circle-fill me-2 text-primary"></i><span class="text-primary">Nuevo mantenimiento</span>
        </button>
    </div>
</div>

<div class="filter-card">
    <div class="d-flex align-items-center mb-3">
        <i class="bi bi-funnel-fill text-primary me-2"></i>
        <span class="fw-semibold text-secondary text-uppercase" style="font-size:.75rem;letter-spacing:.05em;">Filtros de búsqueda</span>
    </div>
    <div class="row g-3">
        <div class="col-12 col-md-5">
            <label class="form-label fw-semibold" for="filterDepartment"><i class="bi bi-geo-alt me-1 text-primary"></i>Departamento</label>
            <select class="form-select" id="filterDepartment">
                <option value="">Todos</option>
                <?php foreach (($departamentos ?? []) as $departamento): ?>
                    <option value="<?= (int) ($departamento['id'] ?? 0) ?>"><?= esc((string) ($departamento['nombre'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-5">
            <label class="form-label fw-semibold" for="filterMunicipality"><i class="bi bi-pin-map me-1 text-primary"></i>Municipio</label>
            <select class="form-select" id="filterMunicipality">
                <option value="">Todos</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-grid align-items-end">
            <button type="button" class="btn btn-primary" id="applyFiltersBtn"><i class="bi bi-search me-1"></i>Filtrar</button>
        </div>
    </div>
</div>

<div class="card border-0 table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="cortesTable">
                <thead>
                <tr>
                    <th><i class="bi bi-card-text me-1"></i>Título</th>
                    <th><i class="bi bi-circle-fill me-1"></i>Estado</th>
                    <th><i class="bi bi-calendar-event me-1"></i>Inicio</th>
                    <th><i class="bi bi-calendar-check me-1"></i>Fin</th>
                    <th><i class="bi bi-geo-alt me-1"></i>Ubicaciones</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td colspan="6">
                        <div class="empty-state text-center"><i class="bi bi-calendar-x empty-icon d-block mb-2"></i><span class="text-secondary">Sin cortes para mostrar.</span></div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="maintenanceImportModal" tabindex="-1" aria-labelledby="maintenanceImportModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="maintenanceImportModalTitle"><i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Carga masiva de mantenimientos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body d-grid gap-4">
                <section class="border rounded-3 p-3 p-md-4">
                    <h6 class="fw-bold mb-2">1. Descargar plantilla</h6>
                    <p class="text-secondary small mb-3">Usa la hoja “Mantenimientos”. Las fechas deben seguir el formato indicado; separa varios IDs o nombres con punto y coma.</p>
                    <a class="btn btn-outline-success" href="<?= esc(site_url('admin/api/etcee/cortes/template')) ?>"><i class="bi bi-download me-1"></i>Descargar plantilla XLSX</a>
                </section>
                <section class="border rounded-3 p-3 p-md-4">
                    <h6 class="fw-bold mb-2">2. Subir plantilla de mantenimientos</h6>
                    <p class="text-secondary small">Se aceptan XLSX y CSV. Se validarán todas las filas antes de guardar; si alguna es inválida, no se importará ninguna.</p>
                    <form id="maintenanceImportForm" class="vstack gap-3">
                        <div>
                            <label class="form-label fw-semibold" for="maintenanceImportFile">Archivo de mantenimientos</label>
                            <input class="form-control" type="file" id="maintenanceImportFile" name="maintenance_file" accept=".xlsx,.csv" required>
                        </div>
                        <div class="d-flex flex-wrap justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="processMaintenanceImportBtn"><i class="bi bi-cloud-arrow-up me-1"></i>Validar e importar</button>
                        </div>
                    </form>
                    <div id="maintenanceImportFeedback" class="mt-3" role="status" aria-live="polite"></div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="corteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <!-- Agregamos d-flex flex-column h-100 para no romper el layout de Bootstrap -->
            <form id="corteForm" class="d-flex flex-column h-100">
                <input type="hidden" id="eventId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="corteModalTitle"><i class="bi bi-lightning-charge me-2"></i>Nuevo mantenimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><p class="modal-section-divider"><i class="bi bi-info-circle me-1"></i>Información general</p></div>
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold"><i class="bi bi-card-heading me-1 text-primary"></i>Título</label>
                            <input class="form-control" id="tituloInput" placeholder="Ej: Mantenimiento línea norte" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold" for="estadoMantenimientoInput"><i class="bi bi-circle-fill me-1 text-primary"></i>Estado de mantenimiento</label>
                            <select class="form-select" id="estadoMantenimientoInput" required></select>
                        </div>
                        <div class="col-12"><p class="modal-section-divider"><i class="bi bi-calendar-range me-1"></i>Vigencia del corte</p></div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-calendar-event me-1 text-primary"></i>Inicio</label>
                            <input type="datetime-local" class="form-control" id="inicioInput" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-calendar-check me-1 text-danger"></i>Fin</label>
                            <input type="datetime-local" class="form-control" id="finInput" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><i class="bi bi-text-paragraph me-1 text-primary"></i>Descripción</label>
                            <textarea class="form-control" id="descripcionInput" rows="2" placeholder="Detalles del corte (opcional)"></textarea>
                        </div>
                        <div class="col-12"><p class="modal-section-divider"><i class="bi bi-geo-alt me-1"></i>Ubicaciones afectadas</p></div>
                        <div class="col-12 col-md-6">
                            <div class="form-label fw-semibold" id="departmentIdsLabel"><i class="bi bi-map me-1 text-primary"></i>Departamentos <span class="text-secondary fw-normal">(múltiple)</span></div>
                            <div class="location-checklist" id="departmentIdsInput" role="group" aria-labelledby="departmentIdsLabel"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-label fw-semibold" id="municipalityIdsLabel"><i class="bi bi-pin-map me-1 text-primary"></i>Municipios <span class="text-secondary fw-normal">(múltiple)</span></div>
                            <div class="location-checklist" id="municipalityIdsInput" role="group" aria-labelledby="municipalityIdsLabel" aria-live="polite"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-danger d-none me-auto" id="deleteEventBtn">
                        <i class="bi bi-trash me-1"></i>Eliminar
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const endpoints = {
    list: <?= json_encode(site_url('admin/api/etcee/cortes/list')) ?>,
    catalogs: <?= json_encode(site_url('admin/api/etcee/cortes/catalogos')) ?>,
    store: <?= json_encode(site_url('admin/api/etcee/cortes')) ?>,
    updateBase: <?= json_encode(site_url('admin/api/etcee/cortes')) ?>,
    import: <?= json_encode(site_url('admin/api/etcee/cortes/import')) ?>,
};

const importModalElement = document.getElementById('maintenanceImportModal');
const importForm = document.getElementById('maintenanceImportForm');
const importFeedback = document.getElementById('maintenanceImportFeedback');

function showImportFeedback(message, type, errors = []) {
    importFeedback.replaceChildren();
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} mb-0`;

    const summary = document.createElement('p');
    summary.className = 'mb-0';
    summary.textContent = message;
    alert.appendChild(summary);

    if (errors.length > 0) {
        const list = document.createElement('ul');
        list.className = 'mb-0 mt-2';
        errors.forEach((error) => {
            const item = document.createElement('li');
            item.textContent = String(error);
            list.appendChild(item);
        });
        alert.appendChild(list);
    }

    importFeedback.appendChild(alert);
}

document.getElementById('openMaintenanceImportBtn').addEventListener('click', function () {
    importFeedback.replaceChildren();
    window.bootstrap?.Modal.getOrCreateInstance(importModalElement).show();
});

importForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    const button = document.getElementById('processMaintenanceImportBtn');
    button.disabled = true;
    button.textContent = 'Procesando...';

    try {
        const result = await fetchEncrypted(endpoints.import, {
            method: 'POST',
            body: new FormData(importForm),
        });
        const data = result?.data || {};

        if (!result?.ok) {
            showImportFeedback(data.message || 'No fue posible importar el archivo.', 'danger', data.errors || []);
            return;
        }

        showImportFeedback(`${data.message || 'Importación completada.'} ${Number(data.created_count || 0)} registro(s) creado(s).`, 'success');
        importForm.reset();
        await loadRows();
    } catch (error) {
        showImportFeedback('Ocurrió un error al enviar o procesar el archivo. Intenta de nuevo.', 'danger');
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i>Validar e importar';
    }
});

const state = {
    departamentos: <?= json_encode($departamentos ?? []) ?>,
    municipios: <?= json_encode($municipios ?? []) ?>,
    estadosMantenimiento: <?= json_encode($estadosMantenimiento ?? []) ?>,
    events: [],
};

const corteModalEl = document.getElementById('corteModal');
let corteModal = null;

function getCorteModal() {
    if (corteModal) {
        return corteModal;
    }

    if (!window.bootstrap || !window.bootstrap.Modal) {
        notify('Bootstrap no esta disponible todavia. Recarga la pagina e intenta de nuevo.', 'danger', 0);
        return null;
    }

    corteModal = window.bootstrap.Modal.getOrCreateInstance(corteModalEl);

    return corteModal;
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

async function deriveCipherKeys(rawKey) {
    let material;
    if ((rawKey || '').startsWith('base64:')) {
        material = b64ToBytes(rawKey.substring(7));
    } else {
        material = new TextEncoder().encode(rawKey || '');
    }

    if (material.length !== 32) {
        material = new Uint8Array(await crypto.subtle.digest('SHA-256', material));
    }

    const aesKey = await crypto.subtle.importKey('raw', material, 'AES-CBC', false, ['encrypt']);
    const hmacKey = await crypto.subtle.importKey('raw', material, { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);

    return { aesKey, hmacKey };
}

async function encryptEnvelope(payload) {
    const { aesKey, hmacKey } = await deriveCipherKeys(window.AdminCipher?.key || '');
    const iv = crypto.getRandomValues(new Uint8Array(16));
    const plain = new TextEncoder().encode(JSON.stringify(payload || {}));
    const cipherBuffer = await crypto.subtle.encrypt({ name: 'AES-CBC', iv }, aesKey, plain);
    const cipher = new Uint8Array(cipherBuffer);

    const macInput = new Uint8Array(iv.length + cipher.length);
    macInput.set(iv, 0);
    macInput.set(cipher, iv.length);

    const macBuffer = await crypto.subtle.sign('HMAC', hmacKey, macInput);
    const mac = new Uint8Array(macBuffer);

    const finalPayload = new Uint8Array(iv.length + mac.length + cipher.length);
    finalPayload.set(iv, 0);
    finalPayload.set(mac, iv.length);
    finalPayload.set(cipher, iv.length + mac.length);

    return {
        encrypted: true,
        algorithm: 'AES-256-CBC',
        payload: bytesToB64(finalPayload),
    };
}

async function postEncrypted(url, payload) {
    const envelope = await encryptEnvelope(payload);

    return fetchEncrypted(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(envelope),
    });
}

function fillDepartmentOptions(selectEl, selectedIds = []) {
    const selectedSet = new Set((selectedIds || []).map((value) => String(value)));
    selectEl.innerHTML = state.departamentos.map((departamento) => {
        const id = String(departamento.id || '');
        return `<option value="${id}" ${selectedSet.has(id) ? 'selected' : ''}>${departamento.nombre || ''}</option>`;
    }).join('');
}

function fillMunicipalityOptions(selectEl, departmentIds = [], selectedIds = [], { includeAll = false } = {}) {
    const deptSet = new Set((departmentIds || []).map((value) => String(value)));
    const selectedSet = new Set((selectedIds || []).map((value) => String(value)));

    if (!includeAll && deptSet.size === 0) {
        selectEl.innerHTML = '<option value="" disabled selected>Selecciona un departamento primero</option>';
        selectEl.disabled = true;
        return;
    }

    selectEl.disabled = false;

    const available = state.municipios.filter((municipio) => {
        if (deptSet.size === 0) return true;
        return deptSet.has(String(municipio.departamento_id || ''));
    });

    const optionsHtml = available.map((municipio) => {
        const id = String(municipio.id || '');
        const departamento = state.departamentos.find((item) => String(item.id) === String(municipio.departamento_id || ''));
        const label = `${municipio.nombre || ''} (${departamento?.nombre || 'N/A'})`;
        return `<option value="${id}" ${selectedSet.has(id) ? 'selected' : ''}>${label}</option>`;
    }).join('');

    if (includeAll) {
        selectEl.innerHTML = `<option value="">Todos</option>${optionsHtml}`;
        return;
    }

    selectEl.innerHTML = optionsHtml;
}

function selectedCheckboxValues(containerEl) {
    return Array.from(containerEl.querySelectorAll('input[type="checkbox"]:checked'))
        .map((checkbox) => Number(checkbox.value))
        .filter((value) => value > 0);
}

function renderLocationChecklist(containerEl, options, selectedIds, idPrefix, labelForOption, emptyMessage) {
    containerEl.replaceChildren();

    if (options.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'location-checklist-empty';
        empty.textContent = emptyMessage;
        containerEl.appendChild(empty);
        return;
    }

    const selectedSet = new Set(selectedIds.map((id) => String(id)));
    const fragment = document.createDocumentFragment();

    options.forEach((option) => {
        const value = String(option.id || '');
        const inputId = `${idPrefix}-${value}`;
        const wrapper = document.createElement('div');
        wrapper.className = 'form-check';

        const checkbox = document.createElement('input');
        checkbox.className = 'form-check-input';
        checkbox.type = 'checkbox';
        checkbox.id = inputId;
        checkbox.value = value;
        checkbox.checked = selectedSet.has(value);

        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.htmlFor = inputId;
        label.textContent = labelForOption(option);

        wrapper.append(checkbox, label);
        fragment.appendChild(wrapper);
    });

    containerEl.appendChild(fragment);
}

function fillDepartmentChecklist(selectedIds = []) {
    renderLocationChecklist(
        document.getElementById('departmentIdsInput'),
        state.departamentos,
        selectedIds,
        'department-option',
        (departamento) => String(departamento.nombre || ''),
        'No hay departamentos disponibles.'
    );
}

function fillMunicipalityChecklist(departmentIds = [], selectedIds = []) {
    const selectedDepartmentSet = new Set(departmentIds.map((id) => String(id)));
    const container = document.getElementById('municipalityIdsInput');

    if (selectedDepartmentSet.size === 0) {
        renderLocationChecklist(container, [], [], 'municipality-option', () => '', 'Selecciona un departamento para ver sus municipios.');
        return;
    }

    const available = state.municipios.filter((municipio) =>
        selectedDepartmentSet.has(String(municipio.departamento_id || ''))
    );

    renderLocationChecklist(
        container,
        available,
        selectedIds,
        'municipality-option',
        (municipio) => {
            const departamento = state.departamentos.find((item) => Number(item.id) === Number(municipio.departamento_id));
            return `${municipio.nombre || ''} (${departamento?.nombre || 'N/A'})`;
        },
        'No hay municipios para los departamentos seleccionados.'
    );
}

function selectedValues(selectEl) {
    return Array.from(selectEl.selectedOptions || []).map((opt) => Number(opt.value)).filter((value) => value > 0);
}

function fillMaintenanceStateOptions(selectedId = '') {
    const select = document.getElementById('estadoMantenimientoInput');
    select.replaceChildren();

    state.estadosMantenimiento.forEach((maintenanceState) => {
        const option = document.createElement('option');
        option.value = String(maintenanceState.id || '');
        const selected = option.value === String(selectedId || '');
        option.disabled = !Number(maintenanceState.activo) && !selected;
        option.textContent = `${maintenanceState.clave || ''} · ${maintenanceState.nombre || ''}${Number(maintenanceState.activo) ? '' : ' (inactivo)'}`;
        select.appendChild(option);
    });

    if (selectedId) select.value = String(selectedId);
    select.disabled = state.estadosMantenimiento.length === 0;
}

function defaultMaintenanceStateId() {
    return state.estadosMantenimiento.find((maintenanceState) => String(maintenanceState.clave || '').toUpperCase() === 'P')?.id
        || state.estadosMantenimiento[0]?.id
        || '';
}

function departmentIdsFromMunicipalityIds(municipalityIds = []) {
    const municipalitySet = new Set((municipalityIds || []).map((value) => Number(value)));

    return Array.from(new Set(
        state.municipios
            .filter((municipio) => municipalitySet.has(Number(municipio.id || 0)))
            .map((municipio) => Number(municipio.departamento_id || 0))
            .filter((value) => value > 0)
    ));
}

function isoToInput(value) {
    if (!value) return '';
    const normalized = String(value).replace(' ', 'T');
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function formatDate(value) {
    if (!value) return '';
    const normalized = String(value).replace(' ', 'T');
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) return String(value);

    return date.toLocaleString('es-GT', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function escapeMaintenanceStateHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);
}

function mapEventToEditable(eventData) {
    return {
        id: eventData.id,
        title: eventData.title,
        start: eventData.start,
        end: eventData.end,
        color: eventData.color,
        extendedProps: eventData.extendedProps || {},
    };
}

function clearForm() {
    document.getElementById('eventId').value = '';
    document.getElementById('tituloInput').value = '';
    document.getElementById('descripcionInput').value = '';
    document.getElementById('inicioInput').value = '';
    document.getElementById('finInput').value = '';
    fillMaintenanceStateOptions(defaultMaintenanceStateId());
    fillDepartmentChecklist();
    fillMunicipalityChecklist();
    document.getElementById('deleteEventBtn').classList.add('d-none');
}

function openNewModal(prefill = {}) {
    clearForm();
    document.getElementById('corteModalTitle').textContent = 'Nuevo mantenimiento';
    if (prefill.start) document.getElementById('inicioInput').value = isoToInput(prefill.start);
    if (prefill.end) document.getElementById('finInput').value = isoToInput(prefill.end);
    const modal = getCorteModal();
    if (modal) modal.show();
}

function openEditModal(eventData) {
    const props = eventData.extendedProps || {};
    const locations = Array.isArray(props.locations) ? props.locations : [];
    const departmentIds = Array.from(new Set(locations.map((item) => Number(item.departamento_id)).filter((value) => value > 0)));
    const municipalityIds = Array.from(new Set(locations.map((item) => Number(item.municipio_id)).filter((value) => value > 0)));

    document.getElementById('eventId').value = eventData.id || '';
    document.getElementById('tituloInput').value = eventData.title || '';
    document.getElementById('descripcionInput').value = props.descripcion || '';
    document.getElementById('inicioInput').value = isoToInput(eventData.start);
    document.getElementById('finInput').value = isoToInput(eventData.end);
    fillMaintenanceStateOptions(props.estado_mantenimiento_id || defaultMaintenanceStateId());

    fillDepartmentChecklist(departmentIds);
    fillMunicipalityChecklist(departmentIds, municipalityIds);

    document.getElementById('deleteEventBtn').classList.remove('d-none');
    document.getElementById('corteModalTitle').textContent = 'Editar corte';
    const modal = getCorteModal();
    if (modal) modal.show();
}

function renderRows(events) {
    const tbody = document.querySelector('#cortesTable tbody');

    if (!Array.isArray(events) || events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state text-center"><i class="bi bi-calendar-x empty-icon d-block mb-2"></i><span class="text-secondary">Sin cortes para mostrar.</span></div></td></tr>';
        return;
    }

    tbody.innerHTML = events.map((event) => {
        const props = event.extendedProps || {};
        const estado = String(props.estado || 'programado').toLowerCase();
        const maintenanceKey = String(props.estado_mantenimiento_clave || '').toUpperCase();
        const maintenanceName = String(props.estado_mantenimiento_nombre || estado);
        const maintenanceColor = /^#[0-9a-f]{6}$/i.test(props.estado_mantenimiento_color || '')
            ? props.estado_mantenimiento_color
            : '#6c757d';
        const locations = Array.isArray(props.locations) ? props.locations : [];
        const locationsLabel = locations.length > 0
            ? [...new Set(locations.map(item => item.departamento || item.departamento_id || 'N/A'))].join('<br>')
            : 'Sin ubicación';

        return `<tr class="corte-row estado-${estado}">
            <td>
                <div class="fw-semibold">${event.title || ''}</div>
                ${props.descripcion ? `<small class="text-secondary">${props.descripcion}</small>` : ''}
            </td>
            <td><span class="badge" style="background-color:${maintenanceColor}">${maintenanceKey ? `${escapeMaintenanceStateHtml(maintenanceKey)} · ` : ''}${escapeMaintenanceStateHtml(maintenanceName)}</span></td>
            <td><span class="text-nowrap"><i class="bi bi-calendar-event me-1 text-primary opacity-75"></i>${formatDate(event.start)}</span></td>
            <td><span class="text-nowrap"><i class="bi bi-calendar-check me-1 text-danger opacity-75"></i>${formatDate(event.end)}</span></td>
            <td><small class="lh-sm">${locationsLabel}</small></td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-primary" title="Editar" onclick="openEditById(${Number(event.id || 0)})">
                    <i class="bi bi-pencil-square"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

function openEditById(id) {
    const target = state.events.find((item) => Number(item.id) === Number(id));

    if (!target) {
        notify('No fue posible cargar el registro seleccionado.', 'warning');
        return;
    }

    openEditModal(mapEventToEditable(target));
}

async function loadRows() {
    const departamentoId = Number(document.getElementById('filterDepartment').value || 0);
    const municipioId = Number(document.getElementById('filterMunicipality').value || 0);

    const result = await postEncrypted(endpoints.list, {
        departamento_id: departamentoId,
        municipio_id: municipioId,
    });

    state.events = result?.data?.events || [];
    renderRows(state.events);
}

async function refreshCatalogs() {
    const result = await postEncrypted(endpoints.catalogs, {});
    state.departamentos = result?.data?.departamentos || [];
    state.municipios = result?.data?.municipios || [];
    state.estadosMantenimiento = result?.data?.estados_mantenimiento || state.estadosMantenimiento;

    fillMaintenanceStateOptions(document.getElementById('estadoMantenimientoInput').value || defaultMaintenanceStateId());
    const selectedDepartments = selectedCheckboxValues(document.getElementById('departmentIdsInput'));
    const selectedMunicipalities = selectedCheckboxValues(document.getElementById('municipalityIdsInput'));
    fillDepartmentChecklist(selectedDepartments);
    fillMunicipalityChecklist(selectedDepartments, selectedMunicipalities);

    const filterDepartment = document.getElementById('filterDepartment');
    const selectedFilterDepartment = Number(filterDepartment.value || 0);
    fillMunicipalityOptions(
        document.getElementById('filterMunicipality'),
        selectedFilterDepartment > 0 ? [selectedFilterDepartment] : [],
        [],
        { includeAll: true }
    );
}

async function saveEvent(event) {
    event.preventDefault();

    const id = Number(document.getElementById('eventId').value || 0);
    const payload = {
        titulo: document.getElementById('tituloInput').value,
        descripcion: document.getElementById('descripcionInput').value,
        fecha_inicio: document.getElementById('inicioInput').value.replace('T', ' '),
        fecha_fin: document.getElementById('finInput').value.replace('T', ' '),
        estado_mantenimiento_id: Number(document.getElementById('estadoMantenimientoInput').value || 0),
        department_ids: selectedCheckboxValues(document.getElementById('departmentIdsInput')),
        municipality_ids: selectedCheckboxValues(document.getElementById('municipalityIdsInput')),
    };

    const endpoint = id > 0 ? `${endpoints.updateBase}/${id}` : endpoints.store;
    const result = await postEncrypted(endpoint, payload);

    if (result?.ok === false) {
        notify(result?.data?.message || 'No fue posible guardar el corte.', 'danger', 0);
        return;
    }

    notify(result?.data?.message || 'Corte guardado correctamente.', 'success');
    const modal = getCorteModal();
    if (modal) modal.hide();
    await loadRows();
}

async function deleteEvent() {
    const id = Number(document.getElementById('eventId').value || 0);

    if (id <= 0) {
        return;
    }

    const result = await postEncrypted(`${endpoints.updateBase}/${id}/delete`, {});

    if (result?.ok === false) {
        notify(result?.data?.message || 'No fue posible eliminar el corte.', 'danger', 0);
        return;
    }

    notify(result?.data?.message || 'Corte eliminado correctamente.', 'success');
    const modal = getCorteModal();
    if (modal) modal.hide();
    await loadRows();
}

document.getElementById('newEventBtn').addEventListener('click', () => openNewModal());
document.getElementById('applyFiltersBtn').addEventListener('click', () => loadRows());
document.getElementById('corteForm').addEventListener('submit', saveEvent);
document.getElementById('deleteEventBtn').addEventListener('click', () => {
    confirmAction('Esta accion eliminara el corte seleccionado. Deseas continuar?', deleteEvent, {
        title: 'Eliminar corte',
        danger: true,
    });
});

document.getElementById('departmentIdsInput').addEventListener('change', function () {
    const selectedDepartments = selectedCheckboxValues(this);
    const selectedMunicipalities = selectedCheckboxValues(document.getElementById('municipalityIdsInput'));
    const selectedDepartmentSet = new Set(selectedDepartments.map((id) => String(id)));
    const availableMunicipalityIds = state.municipios
        .filter((municipio) => selectedDepartmentSet.has(String(municipio.departamento_id || '')))
        .map((municipio) => Number(municipio.id));
    const availableMunicipalitySet = new Set(availableMunicipalityIds);
    fillMunicipalityChecklist(selectedDepartments, selectedMunicipalities.filter((id) => availableMunicipalitySet.has(id)));
});

document.getElementById('municipalityIdsInput').addEventListener('change', function () {
    const selectedMunicipalities = selectedCheckboxValues(this);
    const selectedDepartments = selectedCheckboxValues(document.getElementById('departmentIdsInput'));
    const departmentsFromMunicipalities = departmentIdsFromMunicipalityIds(selectedMunicipalities);
    const mergedDepartments = Array.from(new Set([...selectedDepartments, ...departmentsFromMunicipalities]));

    fillDepartmentChecklist(mergedDepartments);
    fillMunicipalityChecklist(mergedDepartments, selectedMunicipalities);
});

document.getElementById('filterDepartment').addEventListener('change', function () {
    const departmentIds = Number(this.value || 0) > 0 ? [Number(this.value)] : [];
    fillMunicipalityOptions(document.getElementById('filterMunicipality'), departmentIds, [], { includeAll: true });
});

(async function boot() {
    fillMaintenanceStateOptions(defaultMaintenanceStateId());
    await refreshCatalogs();
    await loadRows();
})();
</script>

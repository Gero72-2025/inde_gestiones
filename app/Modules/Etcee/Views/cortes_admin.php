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
            <h2 class="h4 mb-0 text-white fw-bold">Registro de Cortes ETCEE</h2>
            <small class="text-white-50">Gestión de interrupciones y cortes de energía eléctrica</small>
        </div>
    </div>
    <button class="btn btn-light shadow-sm px-4 fw-semibold" id="newEventBtn">
        <i class="bi bi-plus-circle-fill me-2 text-primary"></i><span class="text-primary">Nuevo corte</span>
    </button>
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
                        <div class="empty-state text-center">
                            <i class="bi bi-calendar-x empty-icon d-block mb-2"></i>
                            <span class="text-secondary">Sin cortes para mostrar.</span>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="corteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <form id="corteForm">
                <input type="hidden" id="eventId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="corteModalTitle"><i class="bi bi-lightning-charge me-2"></i>Nuevo corte</h5>
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
                            <label class="form-label fw-semibold"><i class="bi bi-circle-fill me-1 text-primary"></i>Estado</label>
                            <select class="form-select" id="estadoInput">
                                <option value="programado">🔵 Programado</option>
                                <option value="activo">🟢 Activo</option>
                                <option value="finalizado">⚫ Finalizado</option>
                                <option value="cancelado">🔴 Cancelado</option>
                            </select>
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
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold"><i class="bi bi-palette me-1 text-primary"></i>Color del evento</label>
                            <input type="color" class="form-control form-control-color" id="colorInput" value="#1f6feb" title="Color de identificación en el calendario">
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold"><i class="bi bi-text-paragraph me-1 text-primary"></i>Descripción</label>
                            <textarea class="form-control" id="descripcionInput" rows="2" placeholder="Detalles del corte (opcional)"></textarea>
                        </div>
                        <div class="col-12"><p class="modal-section-divider"><i class="bi bi-geo-alt me-1"></i>Ubicaciones afectadas</p></div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-map me-1 text-primary"></i>Departamentos <span class="text-secondary fw-normal">(múltiple)</span></label>
                            <select class="form-select" id="departmentIdsInput" multiple size="8"></select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-pin-map me-1 text-primary"></i>Municipios <span class="text-secondary fw-normal">(múltiple)</span></label>
                            <select class="form-select" id="municipalityIdsInput" multiple size="8"></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-danger d-none" id="deleteEventBtn">
                        <i class="bi bi-trash me-1"></i>Eliminar
                    </button>
                    <div class="ms-auto d-flex gap-2">
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
};

const state = {
    departamentos: <?= json_encode($departamentos ?? []) ?>,
    municipios: <?= json_encode($municipios ?? []) ?>,
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

function selectedValues(selectEl) {
    return Array.from(selectEl.selectedOptions || []).map((opt) => Number(opt.value)).filter((value) => value > 0);
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
    document.getElementById('estadoInput').value = 'programado';
    document.getElementById('colorInput').value = '#1f6feb';
    fillDepartmentOptions(document.getElementById('departmentIdsInput'));
    fillMunicipalityOptions(document.getElementById('municipalityIdsInput'));
    document.getElementById('deleteEventBtn').classList.add('d-none');
}

function openNewModal(prefill = {}) {
    clearForm();
    document.getElementById('corteModalTitle').textContent = 'Nuevo corte';
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
    document.getElementById('estadoInput').value = props.estado || 'programado';
    document.getElementById('colorInput').value = eventData.backgroundColor || eventData.color || '#1f6feb';

    fillDepartmentOptions(document.getElementById('departmentIdsInput'), departmentIds);
    fillMunicipalityOptions(document.getElementById('municipalityIdsInput'), departmentIds, municipalityIds);

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

    const statusClass = {
        programado: 'text-bg-primary',
        activo: 'text-bg-success',
        finalizado: 'text-bg-secondary',
        cancelado: 'text-bg-danger',
    };

    tbody.innerHTML = events.map((event) => {
        const props = event.extendedProps || {};
        const estado = String(props.estado || 'programado').toLowerCase();
        const locations = Array.isArray(props.locations) ? props.locations : [];
        const locationsLabel = locations.length > 0
            ? locations.map((item) => {
                const dep = item.departamento || item.departamento_id || 'N/A';
                const mun = item.municipio || '';
                return mun ? `${dep} / ${mun}` : `${dep}`;
            }).join('<br>')
            : 'Sin ubicacion';

        const statusIcon = { programado: 'bi-clock-history', activo: 'bi-lightning-charge-fill', finalizado: 'bi-check-circle-fill', cancelado: 'bi-x-circle-fill' };
        return `<tr class="corte-row estado-${estado}">
            <td>
                <div class="fw-semibold">${event.title || ''}</div>
                ${props.descripcion ? `<small class="text-secondary">${props.descripcion}</small>` : ''}
            </td>
            <td><span class="badge ${statusClass[estado] || 'text-bg-secondary'}"><i class="bi ${statusIcon[estado] || 'bi-circle'} me-1"></i>${estado}</span></td>
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

    fillDepartmentOptions(document.getElementById('departmentIdsInput'));
    fillMunicipalityOptions(document.getElementById('municipalityIdsInput'));

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
        estado: document.getElementById('estadoInput').value,
        color: document.getElementById('colorInput').value,
        department_ids: selectedValues(document.getElementById('departmentIdsInput')),
        municipality_ids: selectedValues(document.getElementById('municipalityIdsInput')),
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
    const municipalitySelect = document.getElementById('municipalityIdsInput');
    const selectedDepartments = selectedValues(this);
    const selectedMunicipalities = selectedValues(municipalitySelect);

    fillMunicipalityOptions(municipalitySelect, selectedDepartments, selectedMunicipalities);
});

document.getElementById('municipalityIdsInput').addEventListener('change', function () {
    const departmentSelect = document.getElementById('departmentIdsInput');
    const municipalitySelect = this;
    const selectedMunicipalities = selectedValues(municipalitySelect);

    if (selectedMunicipalities.length === 0) {
        return;
    }

    const selectedDepartments = selectedValues(departmentSelect);
    const departmentsFromMunicipalities = departmentIdsFromMunicipalityIds(selectedMunicipalities);
    const mergedDepartments = Array.from(new Set([...selectedDepartments, ...departmentsFromMunicipalities]));

    fillDepartmentOptions(departmentSelect, mergedDepartments);
    fillMunicipalityOptions(municipalitySelect, mergedDepartments, selectedMunicipalities);
});

document.getElementById('filterDepartment').addEventListener('change', function () {
    const departmentIds = Number(this.value || 0) > 0 ? [Number(this.value)] : [];
    fillMunicipalityOptions(document.getElementById('filterMunicipality'), departmentIds, [], { includeAll: true });
});

(async function boot() {
    await refreshCatalogs();
    await loadRows();
})();
</script>

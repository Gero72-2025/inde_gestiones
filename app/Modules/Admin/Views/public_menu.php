<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Portal Publico - Rutas Visibles</h2>
</div>

<div class="alert alert-info">
    Administra que menus/rutas se publican en el inicio del portal. Si una ruta no esta publicada, no se muestra al publico.
</div>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createMenuModal">
    <i class="bi bi-plus-lg me-1"></i> Nueva Ruta Publica
</button>

<div class="modal fade" id="createMenuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="menuForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-globe2 me-2"></i>Nueva Ruta Publica</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Titulo (ES) <span class="text-danger">*</span></label>
                        <input class="form-control" name="title" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ruta (requerida si no es desplegable)</label>
                        <input class="form-control" id="createRoute" name="route_path" placeholder="consulta/nueva-seccion">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Titulo (EN)</label>
                        <input class="form-control" name="title_en">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Icono Bootstrap</label>
                        <div class="input-group">
                            <input class="form-control icon-picker-input" id="createIcon" name="icon_class" placeholder="bi-grid" value="bi-grid" readonly>
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#iconPickerModal" data-icon-field="createIcon" title="Seleccionar icono">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Padre (opcional)</label>
                        <select class="form-select" id="createParent" name="parent_id">
                            <option value="">Sin padre</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="createIsDropdown" name="is_dropdown">
                            <label class="form-check-label">Es desplegable (padre)</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Imagen de encabezado (solo PNG)</label>
                        <input class="form-control" id="createBackgroundImageFile" name="background_image_file" type="file" accept="image/png">
                        <small class="text-muted">Aplica para padres e hijos. Si un hijo no tiene imagen propia, se usa la del padre.</small>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_header" checked>
                            <label class="form-check-label">Mostrar encabezado</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Imagen de pie de pagina (solo PNG)</label>
                        <input class="form-control" id="createFooterBackgroundImageFile" name="footer_background_image_file" type="file" accept="image/png">
                        <small class="text-muted">Aplica para padres e hijos. Si un hijo no tiene imagen propia, se usa la del padre.</small>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_footer" checked>
                            <label class="form-check-label">Mostrar pie de pagina</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Orden</label>
                        <input class="form-control" name="sort_order" type="number" min="0" value="100">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_published" checked>
                            <label class="form-check-label">Publicado</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion (ES)</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editMenuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editMenuForm" novalidate>
                <input type="hidden" id="editMenuId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Editar Ruta Publica</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Titulo (ES) <span class="text-danger">*</span></label>
                        <input class="form-control" id="editTitle" name="title" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ruta (requerida si no es desplegable)</label>
                        <input class="form-control" id="editRoute" name="route_path">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Titulo (EN)</label>
                        <input class="form-control" id="editTitleEn" name="title_en">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Icono Bootstrap</label>
                        <div class="input-group">
                            <input class="form-control icon-picker-input" id="editIcon" name="icon_class" readonly>
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#iconPickerModal" data-icon-field="editIcon" title="Seleccionar icono">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Padre (opcional)</label>
                        <select class="form-select" id="editParent" name="parent_id">
                            <option value="">Sin padre</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="editIsDropdown" name="is_dropdown">
                            <label class="form-check-label">Es desplegable (padre)</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Imagen de encabezado (solo PNG)</label>
                        <small class="text-muted d-block mb-1">Aplica para padres e hijos. Si un hijo no tiene imagen propia, se usa la del padre.</small>
                        <input type="hidden" id="editBackgroundCurrentPath" name="current_background_image_path">
                        <div id="editBackgroundPreview" class="mb-2 d-none">
                            <div class="d-flex align-items-center gap-2 p-2 rounded border bg-light">
                                <img id="editBackgroundPreviewImg" src="" alt="Vista previa" style="height:48px;width:auto;object-fit:contain;border-radius:4px;">
                                <small class="text-muted flex-grow-1 text-break" id="editBackgroundPathText">(sin imagen)</small>
                            </div>
                        </div>
                        <div id="editBackgroundNoImage" class="mb-2">
                            <small class="text-muted">Ruta actual: <span id="editBackgroundPathTextAlt">(sin imagen)</span></small>
                        </div>
                        <input class="form-control" id="editBackgroundImageFile" name="background_image_file" type="file" accept="image/png">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="editClearBackgroundImage" name="clear_background_image">
                            <label class="form-check-label" for="editClearBackgroundImage">Quitar imagen de fondo actual</label>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="editShowHeader" name="show_header">
                            <label class="form-check-label">Mostrar encabezado</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Imagen de pie de pagina (solo PNG)</label>
                        <small class="text-muted d-block mb-1">Aplica para padres e hijos. Si un hijo no tiene imagen propia, se usa la del padre.</small>
                        <input type="hidden" id="editFooterBackgroundCurrentPath" name="current_footer_background_image_path">
                        <div id="editFooterBackgroundPreview" class="mb-2 d-none">
                            <div class="d-flex align-items-center gap-2 p-2 rounded border bg-light">
                                <img id="editFooterBackgroundPreviewImg" src="" alt="Vista previa" style="height:48px;width:auto;object-fit:contain;border-radius:4px;">
                                <small class="text-muted flex-grow-1 text-break" id="editFooterBackgroundPathText">(sin imagen)</small>
                            </div>
                        </div>
                        <div id="editFooterBackgroundNoImage" class="mb-2">
                            <small class="text-muted">Ruta actual: <span id="editFooterBackgroundPathTextAlt">(sin imagen)</span></small>
                        </div>
                        <input class="form-control" id="editFooterBackgroundImageFile" name="footer_background_image_file" type="file" accept="image/png">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="editClearFooterBackgroundImage" name="clear_footer_background_image">
                            <label class="form-check-label" for="editClearFooterBackgroundImage">Quitar imagen de fondo actual</label>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="editShowFooter" name="show_footer">
                            <label class="form-check-label">Mostrar pie de pagina</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Orden</label>
                        <input class="form-control" id="editSort" name="sort_order" type="number" min="0">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="editPublished" name="is_published">
                            <label class="form-check-label">Publicado</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion (ES)</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="iconPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-palette me-2"></i>Seleccionar Icono Bootstrap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group input-group-sm mb-3">
                    <input type="text" id="iconSearchInput" class="form-control" placeholder="Buscar icono por nombre...">
                    <button class="btn btn-outline-secondary" type="button" id="iconClearSearch"><i class="bi bi-x-circle"></i></button>
                </div>
                <div class="icon-grid" id="iconGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(3.5rem, 1fr)); gap: 0.5rem; max-height: 42rem; overflow-y: auto; padding: .5rem;">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="flex-grow-1 me-2" style="max-width: 30rem;">
        <input type="text" id="menuSearchInput" class="form-control" placeholder="Buscar por titulo, ruta o descripcion...">
    </div>
    <div>
        <small class="text-muted">Resultados: <span id="resultCount">0</span></small>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle" id="publicMenuTable">
        <thead>
            <tr>
                <th style="width:60px">ID</th>
                <th>Titulo</th>
                <th>Ruta</th>
                <th style="width:100px">Tipo</th>
                <th style="width:140px">Padre</th>
                <th style="width:90px">Orden</th>
                <th style="width:100px">Estado</th>
                <th style="width:170px">Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<nav aria-label="Paginacion de menu" class="mt-3">
    <ul class="pagination justify-content-center" id="paginationControls">
    </ul>
</nav>

<script>
let menuData = [];
let filteredData = [];
let currentPage = 1;
const itemsPerPage = 10;

const bootstrapIcons = [
    'bi-house-door', 'bi-house-fill', 'bi-building', 'bi-grid', 'bi-grid-3x3',
    'bi-search', 'bi-compass', 'bi-compass-fill', 'bi-map', 'bi-pin-map',
    'bi-telephone', 'bi-envelope', 'bi-chat', 'bi-chat-fill', 'bi-bell',
    'bi-bell-fill', 'bi-person', 'bi-person-fill', 'bi-people', 'bi-people-fill',
    'bi-user-check', 'bi-user-clock', 'bi-file-text', 'bi-file-pdf', 'bi-document',
    'bi-documents', 'bi-book', 'bi-bookmark', 'bi-calculator', 'bi-calendar',
    'bi-calendar-check', 'bi-calendar-event', 'bi-calendar3', 'bi-clock', 'bi-clock-fill',
    'bi-alarm', 'bi-timer', 'bi-hourglass', 'bi-stopwatch', 'bi-lightning',
    'bi-lightning-charge', 'bi-lightning-charge-fill', 'bi-power', 'bi-plug', 'bi-plug-fill',
    'bi-wifi', 'bi-water', 'bi-droplet', 'bi-droplet-fill', 'bi-fire',
    'bi-sun', 'bi-moon', 'bi-star', 'bi-star-fill', 'bi-heart',
    'bi-heart-fill', 'bi-hand-thumbs-up', 'bi-hand-thumbs-down', 'bi-check', 'bi-check-circle',
    'bi-check-circle-fill', 'bi-x', 'bi-x-circle', 'bi-x-circle-fill', 'bi-exclamation',
    'bi-exclamation-circle', 'bi-exclamation-triangle', 'bi-question-circle', 'bi-info-circle',
    'bi-info-circle-fill', 'bi-folder', 'bi-folder-fill', 'bi-folder2-open', 'bi-files',
    'bi-download', 'bi-upload', 'bi-cloud-download', 'bi-cloud-upload', 'bi-arrow-down',
    'bi-arrow-up', 'bi-arrow-left', 'bi-arrow-right', 'bi-gear', 'bi-gear-fill',
    'bi-sliders', 'bi-sliders2-vertical', 'bi-lock', 'bi-lock-fill', 'bi-unlock',
    'bi-shield', 'bi-shield-fill', 'bi-badge-check', 'bi-badge-check-fill', 'bi-link',
    'bi-link-45deg', 'bi-tag', 'bi-tags', 'bi-tag-fill', 'bi-trash',
    'bi-trash-fill', 'bi-pencil', 'bi-pencil-square', 'bi-pencil-fill', 'bi-plus',
    'bi-plus-circle', 'bi-minus', 'bi-minus-circle', 'bi-table', 'bi-list',
    'bi-list-check', 'bi-list-ol', 'bi-list-ul', 'bi-eye', 'bi-eye-fill',
    'bi-eye-slash', 'bi-ear', 'bi-speaker', 'bi-mic', 'bi-telephone-inbound',
    'bi-telephone-outbound', 'bi-mailbox', 'bi-inbox', 'bi-inbox-fill', 'bi-archive',
    'bi-globe', 'bi-globe2', 'bi-globe-americas', 'bi-globe-europe', 'bi-globe-asia-australia',
    'bi-car-front', 'bi-car-front-fill', 'bi-truck', 'bi-bicycle', 'bi-speedometer',
    'bi-speedometer2', 'bi-fuel-pump', 'bi-fuel-pump-fill', 'bi-tree', 'bi-leaf', 'bi-leaf-fill'
];

let currentIconField = null;
let parentModalInstance = null;
let skipEditReset = false;

function escM(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function renderIconGrid(icons, searchTerm = '') {
    const grid = document.getElementById('iconGrid');
    const filtered = icons.filter((icon) => icon.toLowerCase().includes(searchTerm.toLowerCase()));

    grid.innerHTML = filtered.map((icon) => `
        <button
            type="button"
            class="btn btn-outline-secondary icon-option"
            data-icon="${icon}"
            title="${icon}"
            style="height: 3.5rem; font-size: 1.4rem; padding: 0.35rem; display: flex; align-items: center; justify-content: center;"
        >
            <i class="bi ${icon}"></i>
        </button>
    `).join('');

    document.querySelectorAll('.icon-option').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const iconClass = btn.dataset.icon;

            if (currentIconField === 'createIcon') {
                document.getElementById('createIcon').value = iconClass;
            } else if (currentIconField === 'editIcon') {
                document.getElementById('editIcon').value = iconClass;
            }

            const iconPickerModal = bootstrap.Modal.getInstance(document.getElementById('iconPickerModal'));
            if (iconPickerModal) {
                iconPickerModal.hide();
            }

            if (parentModalInstance) {
                setTimeout(() => {
                    parentModalInstance.show();
                }, 200);
            }
        });
    });
}

function sortMenuByHierarchy(data) {
    const parents = data.filter((item) => Number(item.is_dropdown) === 1);
    const children = data.filter((item) => Number(item.is_dropdown) === 0);

    return [...parents, ...children];
}

function filterAndRenderMenu(searchTerm = '') {
    const searchLower = searchTerm.toLowerCase();

    filteredData = menuData.filter((item) => {
        const titleMatch = (item.title || '').toLowerCase().includes(searchLower);
        const descMatch = (item.description || '').toLowerCase().includes(searchLower);
        const routeMatch = (item.route_path || '').toLowerCase().includes(searchLower);

        return titleMatch || descMatch || routeMatch;
    });

    filteredData = sortMenuByHierarchy(filteredData);

    currentPage = 1;
    document.getElementById('resultCount').textContent = filteredData.length;
    renderMenuRows();
    renderPagination();
}

function renderMenuRows() {
    const tbody = document.querySelector('#publicMenuTable tbody');
    const byId = Object.fromEntries(menuData.map((item) => [Number(item.id), item]));

    const startIdx = (currentPage - 1) * itemsPerPage;
    const endIdx = startIdx + itemsPerPage;
    const pageData = filteredData.slice(startIdx, endIdx);

    tbody.innerHTML = pageData.map((row) => {
        const badge = Number(row.is_published) === 1 ? 'bg-success' : 'bg-secondary';
        const statusText = Number(row.is_published) === 1 ? 'Publicado' : 'Oculto';
        const rowType = Number(row.is_dropdown) === 1 ? 'Padre' : (Number(row.parent_id) > 0 ? 'Hijo' : 'Directo');
        const parentTitle = Number(row.parent_id) > 0 ? (byId[Number(row.parent_id)]?.title || 'Sin padre') : 'Sin padre';
        const indentClass = Number(row.parent_id) > 0 ? 'ps-4' : '';

        return `<tr>
            <td>${row.id}</td>
            <td class="${indentClass}">
                <div class="fw-semibold">${escM(row.title)}</div>
                <small class="text-muted">${escM(row.description || '')}</small>
            </td>
            <td><code>${escM(row.route_path || '(sin ruta)')}</code></td>
            <td><span class="badge text-bg-light">${escM(rowType)}</span></td>
            <td>${escM(parentTitle)}</td>
            <td>${row.sort_order}</td>
            <td><span class="badge ${badge}">${statusText}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditMenu(${row.id})">Editar</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteMenu(${row.id}, '${escM(row.title)}')">Eliminar</button>
            </td>
        </tr>`;
    }).join('');
}

function renderPagination() {
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    const controls = document.getElementById('paginationControls');

    if (totalPages <= 1) {
        controls.innerHTML = '';
        return;
    }

    let html = '';

    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    html += `<li class="page-item ${prevDisabled}"><a class="page-link" href="#" onclick="goToPage(${currentPage - 1})"><i class="bi bi-chevron-left"></i></a></li>`;

    for (let i = 1; i <= totalPages; i++) {
        const isActive = currentPage === i ? 'active' : '';
        html += `<li class="page-item ${isActive}"><a class="page-link" href="#" onclick="goToPage(${i})">${i}</a></li>`;
    }

    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    html += `<li class="page-item ${nextDisabled}"><a class="page-link" href="#" onclick="goToPage(${currentPage + 1})"><i class="bi bi-chevron-right"></i></a></li>`;

    controls.innerHTML = html;
}

function goToPage(page) {
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    if (page < 1 || page > totalPages) return;

    currentPage = page;
    renderMenuRows();
    renderPagination();
    window.scrollTo(0, 0);
}

async function loadMenu() {
    const result = await fetchEncrypted('<?= site_url('admin/api/public-menu') ?>');
    menuData = result?.data?.items || [];
    syncParentOptions('createParent', null);
    filterAndRenderMenu();
}

function syncParentOptions(selectId, currentId = null) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const dropdownParents = menuData.filter((item) => Number(item.is_dropdown) === 1);
    const options = ['<option value="">Sin padre</option>'];

    dropdownParents.forEach((item) => {
        if (currentId !== null && Number(item.id) === Number(currentId)) {
            return;
        }

        options.push(`<option value="${item.id}">${escM(item.title || `Padre #${item.id}`)}</option>`);
    });

    select.innerHTML = options.join('');
}

function toggleDropdownFields(prefix) {
    const isDropdown = document.getElementById(`${prefix}IsDropdown`);
    const routeInput = document.getElementById(`${prefix}Route`);
    const parentSelect = document.getElementById(`${prefix}Parent`);
    if (!isDropdown || !routeInput || !parentSelect) return;

    // La imagen de encabezado ya no depende de ser padre: aplica para padres e hijos.
    if (isDropdown.checked) {
        routeInput.value = '';
        routeInput.disabled = true;
        parentSelect.value = '';
        parentSelect.disabled = true;
        return;
    }

    routeInput.disabled = false;
    parentSelect.disabled = false;
}

function resetCreateMenuForm() {
    const form = document.getElementById('menuForm');
    form.reset();
    document.getElementById('createIcon').value = 'bi-grid';
    document.getElementById('createParent').value = '';
    document.getElementById('createRoute').value = '';
    document.getElementById('createIsDropdown').checked = false;
    document.getElementById('createBackgroundImageFile').value = '';
    document.getElementById('createFooterBackgroundImageFile').value = '';
    toggleDropdownFields('create');
}

function setEditBackgroundPreview(path) {
    const preview = document.getElementById('editBackgroundPreview');
    const noImage = document.getElementById('editBackgroundNoImage');
    const img = document.getElementById('editBackgroundPreviewImg');
    const pathText = document.getElementById('editBackgroundPathText');
    const pathTextAlt = document.getElementById('editBackgroundPathTextAlt');
    if (path) {
        img.src = '<?= base_url() ?>' + path;
        pathText.textContent = path;
        preview.classList.remove('d-none');
        noImage.classList.add('d-none');
    } else {
        img.src = '';
        pathTextAlt.textContent = '(sin imagen)';
        preview.classList.add('d-none');
        noImage.classList.remove('d-none');
    }
}

function setEditFooterBackgroundPreview(path) {
    const preview = document.getElementById('editFooterBackgroundPreview');
    const noImage = document.getElementById('editFooterBackgroundNoImage');
    const img = document.getElementById('editFooterBackgroundPreviewImg');
    const pathText = document.getElementById('editFooterBackgroundPathText');
    const pathTextAlt = document.getElementById('editFooterBackgroundPathTextAlt');
    if (path) {
        img.src = '<?= base_url() ?>' + path;
        pathText.textContent = path;
        preview.classList.remove('d-none');
        noImage.classList.add('d-none');
    } else {
        img.src = '';
        pathTextAlt.textContent = '(sin imagen)';
        preview.classList.add('d-none');
        noImage.classList.remove('d-none');
    }
}

function resetEditMenuForm() {
    const form = document.getElementById('editMenuForm');
    form.reset();
    document.getElementById('editMenuId').value = '';
    document.getElementById('editIcon').value = 'bi-grid';
    document.getElementById('editParent').value = '';
    document.getElementById('editBackgroundCurrentPath').value = '';
    setEditBackgroundPreview(null);
    document.getElementById('editClearBackgroundImage').checked = false;
    document.getElementById('editBackgroundImageFile').value = '';
    document.getElementById('editFooterBackgroundCurrentPath').value = '';
    setEditFooterBackgroundPreview(null);
    document.getElementById('editClearFooterBackgroundImage').checked = false;
    document.getElementById('editFooterBackgroundImageFile').value = '';
    toggleDropdownFields('edit');
}

function openEditMenu(id) {
    const row = menuData.find((item) => Number(item.id) === Number(id));
    if (!row) return;

    resetEditMenuForm();
    syncParentOptions('editParent', row.id);

    document.getElementById('editMenuId').value = row.id;
    document.getElementById('editTitle').value = row.title || '';
    document.getElementById('editRoute').value = row.route_path || '';
    document.getElementById('editTitleEn').value = row.title_en || '';
    document.getElementById('editIcon').value = row.icon_class || 'bi-grid';
    document.getElementById('editBackgroundCurrentPath').value = row.background_image_path || '';
    setEditBackgroundPreview(row.background_image_path || null);
    document.getElementById('editClearBackgroundImage').checked = false;
    document.getElementById('editShowHeader').checked = Number(row.show_header) !== 0;
    document.getElementById('editFooterBackgroundCurrentPath').value = row.footer_background_image_path || '';
    setEditFooterBackgroundPreview(row.footer_background_image_path || null);
    document.getElementById('editClearFooterBackgroundImage').checked = false;
    document.getElementById('editShowFooter').checked = Number(row.show_footer) !== 0;
    document.getElementById('editParent').value = row.parent_id || '';
    document.getElementById('editIsDropdown').checked = Number(row.is_dropdown) === 1;
    document.getElementById('editSort').value = row.sort_order || 100;
    document.getElementById('editDescription').value = row.description || '';
    document.getElementById('editPublished').checked = Number(row.is_published) === 1;
    toggleDropdownFields('edit');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('editMenuModal')).show();
}

async function deleteMenu(id, title) {
    confirmAction(
        `¿Eliminar la ruta publica "${title}"? Esta accion no se puede deshacer.`,
        async () => {
            const result = await fetchEncrypted(`<?= site_url('admin/api/public-menu/') ?>${id}/delete`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            notify(result?.data?.message || 'Registro eliminado.', 'success');
            await loadMenu();
        },
        { title: 'Eliminar Ruta Publica', danger: true }
    );
}

document.getElementById('menuForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await fetchEncrypted('<?= site_url('admin/api/public-menu') ?>', {
        method: 'POST',
        body: new FormData(e.target),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    notify(result?.data?.message || 'Ruta publica creada.', 'success');
    bootstrap.Modal.getInstance(document.getElementById('createMenuModal'))?.hide();
    resetCreateMenuForm();
    await loadMenu();
});

document.getElementById('editMenuForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editMenuId').value;
    const result = await fetchEncrypted(`<?= site_url('admin/api/public-menu/') ?>${id}`, {
        method: 'POST',
        body: new FormData(e.target),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    notify(result?.data?.message || 'Ruta publica actualizada.', 'success');
    bootstrap.Modal.getInstance(document.getElementById('editMenuModal'))?.hide();
    await loadMenu();
});

document.getElementById('createIsDropdown').addEventListener('change', () => toggleDropdownFields('create'));
document.getElementById('editIsDropdown').addEventListener('change', () => toggleDropdownFields('edit'));

document.getElementById('iconPickerModal').addEventListener('show.bs.modal', function (e) {
    renderIconGrid(bootstrapIcons);
    
    const trigger = e.relatedTarget;
    if (trigger && trigger.dataset.iconField) {
        currentIconField = trigger.dataset.iconField;
        
        const parentModal = trigger.closest('.modal');
        if (parentModal) {
            const parentInstance = bootstrap.Modal.getInstance(parentModal);
            if (parentInstance) {
                parentModalInstance = parentInstance;
                if (parentModal.id === 'editMenuModal') {
                    skipEditReset = true;
                }
                parentInstance.hide();
            }
        }
    }
});

document.getElementById('iconPickerModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('iconSearchInput').value = '';
});

document.getElementById('iconSearchInput').addEventListener('input', (e) => {
    renderIconGrid(bootstrapIcons, e.target.value);
});

document.getElementById('iconClearSearch').addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('iconSearchInput').value = '';
    renderIconGrid(bootstrapIcons);
});

document.getElementById('menuSearchInput').addEventListener('input', (e) => {
    filterAndRenderMenu(e.target.value);
});

document.getElementById('createMenuModal').addEventListener('show.bs.modal', () => {
    syncParentOptions('createParent', null);
    resetCreateMenuForm();
});

document.getElementById('createMenuModal').addEventListener('hidden.bs.modal', () => {
    resetCreateMenuForm();
});

document.getElementById('editMenuModal').addEventListener('hidden.bs.modal', () => {
    if (skipEditReset) {
        skipEditReset = false;
        return;
    }
    resetEditMenuForm();
});

toggleDropdownFields('create');

loadMenu();
</script>

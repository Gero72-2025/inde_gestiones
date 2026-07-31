<section class="rounded-4 p-4 p-lg-5 mb-4 text-white" style="background:linear-gradient(135deg,#0b4a66 0%,#1b7c79 56%,#2c9f78 100%);">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <p class="text-uppercase small opacity-75 mb-2">GERO</p>
            <h1 class="h3 mb-1">Comunidades GERO</h1>
            <p class="mb-0 opacity-75">CRUD administrativo con bitacora de fases, protegido con sesion y respuestas cifradas.</p>
        </div>
        <button type="button" class="btn btn-light fw-semibold" id="btnNuevaComunidad">
            <i class="bi bi-plus-circle me-1"></i>Nueva comunidad
        </button>
    </div>
</section>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-7">
                <label class="form-label" for="filtroBusqueda">Buscar por codigo, SNIP o nombre</label>
                <input type="text" id="filtroBusqueda" class="form-control" maxlength="120" placeholder="Ej: COD-123, SNIP-88 o nombre de comunidad">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="filtroFase">Fase actual</label>
                <select id="filtroFase" class="form-select">
                    <option value="">Todas</option>
                    <option value="fase_1">Fase 1</option>
                    <option value="fase_2">Fase 2</option>
                    <option value="fase_3">Fase 3</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button id="btnFiltrar" class="btn btn-outline-primary">Filtrar</button>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0" id="tablaComunidades">
            <thead class="table-light">
            <tr>
                <th>Codigo</th>
                <th>Comunidad</th>
                <th>Municipio</th>
                <th>Fase</th>
                <th>Estado</th>
                <th>Origen</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <tr><td colspan="7" class="text-center text-secondary py-4">Cargando comunidades...</td></tr>
            </tbody>
        </table>
    </div>
    <div id="tablaComunidadesPager" class="d-flex justify-content-between align-items-center px-3 py-2 border-top"></div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Carga Masiva por Division</h2>
            <div class="small text-secondary">DICODER, DOSODEP y DIVOC</div>
        </div>

        <form id="uploadForm" class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="division" class="form-label">Division</label>
                <select id="division" class="form-select" required>
                    <option value="">Selecciona...</option>
                    <option value="DOSODEP">DOSODEP</option>
                    <option value="DICODER">DICODER</option>
                    <option value="DIVOC">DIVOC</option>
                </select>
            </div>
            <div class="col-12 col-md-5">
                <label for="archivo" class="form-label">Archivo (.csv, .xlsx, .xls)</label>
                <input id="archivo" type="file" class="form-control" accept=".csv,.xlsx,.xls" required>
            </div>
            <div class="col-12 col-md-4 d-grid d-md-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-1"></i>Subir y sincronizar</button>
                <button id="refreshSchemasBtn" class="btn btn-outline-secondary" type="button"><i class="bi bi-arrow-repeat me-1"></i>Esquemas</button>
                <button id="cleanupTablesBtn" class="btn btn-outline-danger" type="button"><i class="bi bi-trash me-1"></i>Limpiar</button>
            </div>
        </form>
        <div id="uploadStatus" class="small mt-2"></div>

        <hr>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h3 class="h6 mb-0">Mapeo de Campos para Landing</h3>
            <select id="schemaSourceSelect" class="form-select form-select-sm" style="max-width: 440px;">
                <option value="">Selecciona una division...</option>
            </select>
        </div>

        <div id="schemaTableWrap" class="table-responsive d-none">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                <tr>
                    <th>Columna detectada</th>
                    <th>Etiqueta Landing</th>
                    <th class="text-center">Mostrar</th>
                    <th>Fase</th>
                    <th class="text-center">Hito</th>
                    <th class="text-center">Card</th>
                </tr>
                </thead>
                <tbody id="schemaTableBody"></tbody>
            </table>
            <div class="d-flex justify-content-end">
                <button id="saveMappingBtn" class="btn btn-success btn-sm" type="button"><i class="bi bi-save me-1"></i>Guardar mapeo</button>
            </div>
        </div>
        <div id="schemaEmpty" class="small text-secondary">Sube un archivo o selecciona una fuente para mapear columnas.</div>
        <div id="schemaStatus" class="small mt-2"></div>

        <hr>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h3 class="h6 mb-0">Logs de Carga</h3>
            <button id="refreshLogsBtn" class="btn btn-outline-secondary btn-sm" type="button"><i class="bi bi-arrow-repeat me-1"></i>Recargar logs</button>
        </div>

        <div id="logsWrap" class="table-responsive d-none">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Division</th>
                    <th>Tabla</th>
                    <th class="text-end">Nuevos</th>
                    <th class="text-end">Modificados</th>
                    <th class="text-end">Total Archivo</th>
                </tr>
                </thead>
                <tbody id="logsTableBody"></tbody>
            </table>
        </div>
        <div id="logsEmpty" class="small text-secondary">Aun no hay cargas registradas.</div>
        <div id="logsStatus" class="small mt-2"></div>
    </div>
</div>

<div class="modal fade" id="comunidadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="comunidadForm">
            <div class="modal-header">
                <h5 class="modal-title" id="comunidadModalTitle">Nueva comunidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="comunidadId">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="codigoComunidad">Codigo comunidad</label>
                        <input type="text" class="form-control" id="codigoComunidad" maxlength="60">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="numeroSnip">Numero SNIP</label>
                        <input type="text" class="form-control" id="numeroSnip" maxlength="60">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="divisionOrigen">Division origen</label>
                        <select class="form-select" id="divisionOrigen">
                            <option value="">Sin definir</option>
                            <option value="DOSODEP">DOSODEP</option>
                            <option value="DICODER">DICODER</option>
                            <option value="DIVOC">DIVOC</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label" for="nombreComunidad">Nombre comunidad</label>
                        <input type="text" class="form-control" id="nombreComunidad" maxlength="255" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="faseActual">Fase actual</label>
                        <select class="form-select" id="faseActual">
                            <option value="fase_1">Fase 1</option>
                            <option value="fase_2">Fase 2</option>
                            <option value="fase_3">Fase 3</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="municipio">Municipio</label>
                        <input type="text" class="form-control" id="municipio" maxlength="120" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="departamento">Departamento</label>
                        <input type="text" class="form-control" id="departamento" maxlength="120">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="estadoActual">Estado actual</label>
                        <input type="text" class="form-control" id="estadoActual" maxlength="120" required>
                    </div>
                </div>
                <hr>
                <div class="row g-2">
                    <div class="col-6 col-md-4 form-check">
                        <input class="form-check-input" type="checkbox" id="solicitudFirmada">
                        <label class="form-check-label" for="solicitudFirmada">Solicitud firmada</label>
                    </div>
                    <div class="col-6 col-md-4 form-check">
                        <input class="form-check-input" type="checkbox" id="estudioSocioeconomico">
                        <label class="form-check-label" for="estudioSocioeconomico">Estudio socioeconomico</label>
                    </div>
                    <div class="col-6 col-md-4 form-check">
                        <input class="form-check-input" type="checkbox" id="snipAprobado">
                        <label class="form-check-label" for="snipAprobado">SNIP aprobado</label>
                    </div>
                    <div class="col-6 col-md-4 form-check">
                        <input class="form-check-input" type="checkbox" id="licitacionTerminada">
                        <label class="form-check-label" for="licitacionTerminada">Licitacion terminada</label>
                    </div>
                    <div class="col-6 col-md-4 form-check">
                        <input class="form-check-input" type="checkbox" id="obraEnergizada">
                        <label class="form-check-label" for="obraEnergizada">Obra energizada</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger d-none" id="btnEliminar">Eliminar</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="bitacoraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bitacora de fases</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="bitacoraBody">
                <p class="text-secondary mb-0">Cargando...</p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const endpoints = {
        list: <?= json_encode(site_url('admin/api/gero/comunidades/list')) ?>,
        store: <?= json_encode(site_url('admin/api/gero/comunidades')) ?>,
        updateBase: <?= json_encode(site_url('admin/api/gero/comunidades')) ?>,
        bitacoraBase: <?= json_encode(site_url('admin/api/gero/comunidades')) ?>,
        sourceUpload: <?= json_encode(site_url('admin/api/gero/comunidades/source-upload')) ?>,
        sourceSchema: <?= json_encode(site_url('admin/api/gero/comunidades/source-schema')) ?>,
        sourceSchemaSave: <?= json_encode(site_url('admin/api/gero/comunidades/source-schema/save-mapping')) ?>,
        sourceLogs: <?= json_encode(site_url('admin/api/gero/comunidades/source-logs')) ?>,
        sourceCleanup: <?= json_encode(site_url('admin/api/gero/comunidades/source-cleanup')) ?>,
    };
    const CSRF_TOKEN = window.AdminCipher?.csrfToken || '';
    const CSRF_NAME = window.AdminCipher?.csrfName || 'csrf_test_name';

    const tableBody = document.querySelector('#tablaComunidades tbody');
    const tablePager = document.getElementById('tablaComunidadesPager');
    const modalEl = document.getElementById('comunidadModal');
    const bitacoraEl = document.getElementById('bitacoraModal');
    let modal = null;
    let bitacoraModal = null;
    let cacheItems = [];
    let tablePage = 1;
    const tablePerPage = 10;
    const tableMaxVisiblePages = 7;
    let loadedSources = [];

    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');
    const refreshSchemasBtn = document.getElementById('refreshSchemasBtn');
    const cleanupTablesBtn = document.getElementById('cleanupTablesBtn');
    const refreshLogsBtn = document.getElementById('refreshLogsBtn');
    const logsWrap = document.getElementById('logsWrap');
    const logsTableBody = document.getElementById('logsTableBody');
    const logsEmpty = document.getElementById('logsEmpty');
    const logsStatus = document.getElementById('logsStatus');
    const schemaSourceSelect = document.getElementById('schemaSourceSelect');
    const schemaTableWrap = document.getElementById('schemaTableWrap');
    const schemaTableBody = document.getElementById('schemaTableBody');
    const schemaEmpty = document.getElementById('schemaEmpty');
    const schemaStatus = document.getElementById('schemaStatus');
    const saveMappingBtn = document.getElementById('saveMappingBtn');

    function getModal() {
        if (!modal) modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        return modal;
    }

    function getBitacoraModal() {
        if (!bitacoraModal) bitacoraModal = bootstrap.Modal.getOrCreateInstance(bitacoraEl);
        return bitacoraModal;
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

        const mac = new Uint8Array(await crypto.subtle.sign('HMAC', hmacKey, concatBytes(iv, cipher)));

        return {
            encrypted: true,
            algorithm: 'AES-256-CBC',
            payload: bytesToB64(concatBytes(iv, mac, cipher)),
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

    function asLabelFase(value) {
        if (value === 'fase_2') return 'Fase 2';
        if (value === 'fase_3') return 'Fase 3';
        return 'Fase 1';
    }

    function asBadgeClass(value) {
        if (value === 'fase_2') return 'text-bg-warning';
        if (value === 'fase_3') return 'text-bg-success';
        return 'text-bg-primary';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function prettify(value) {
        return String(value || '')
            .toLowerCase()
            .split('_')
            .filter(Boolean)
            .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' ');
    }

    function appendCsrfToFormData(formData) {
        if (!CSRF_TOKEN || !(formData instanceof FormData)) {
            return formData;
        }

        if (!formData.has(CSRF_NAME)) {
            formData.append(CSRF_NAME, CSRF_TOKEN);
        }

        return formData;
    }

    function renderTablePager(total) {
        if (!tablePager) {
            return;
        }

        const pages = Math.max(1, Math.ceil(total / tablePerPage));

        if (total <= tablePerPage) {
            tablePager.innerHTML = `<small class="text-muted">Mostrando ${total} de ${total}</small>`;
            return;
        }

        if (tablePage > pages) {
            tablePage = pages;
        }

        const from = (tablePage - 1) * tablePerPage + 1;
        const to = Math.min(tablePage * tablePerPage, total);
        let buttons = '';

        const half = Math.floor(tableMaxVisiblePages / 2);
        let startPage = Math.max(1, tablePage - half);
        let endPage = Math.min(pages, startPage + tableMaxVisiblePages - 1);
        startPage = Math.max(1, endPage - tableMaxVisiblePages + 1);

        buttons += `<button type="button" class="btn btn-sm btn-outline-secondary" data-nav="prev" ${tablePage <= 1 ? 'disabled' : ''}>Anterior</button>`;

        if (startPage > 1) {
            buttons += `<button type="button" class="btn btn-sm btn-outline-secondary" data-page="1">1</button>`;
            if (startPage > 2) {
                buttons += '<span class="px-1 text-muted">...</span>';
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            buttons += `<button type="button" class="btn btn-sm ${i === tablePage ? 'btn-primary' : 'btn-outline-secondary'}" data-page="${i}">${i}</button>`;
        }

        if (endPage < pages) {
            if (endPage < pages - 1) {
                buttons += '<span class="px-1 text-muted">...</span>';
            }
            buttons += `<button type="button" class="btn btn-sm btn-outline-secondary" data-page="${pages}">${pages}</button>`;
        }

        buttons += `<button type="button" class="btn btn-sm btn-outline-secondary" data-nav="next" ${tablePage >= pages ? 'disabled' : ''}>Siguiente</button>`;

        tablePager.innerHTML = `
            <small class="text-muted">Mostrando ${from}-${to} de ${total}</small>
            <div class="d-flex flex-wrap gap-1">${buttons}</div>
        `;
    }

    function renderTable(items) {
        if (!Array.isArray(items) || items.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-4">Sin comunidades registradas.</td></tr>';
            if (tablePager) {
                tablePager.innerHTML = '<small class="text-muted">Mostrando 0 de 0</small>';
            }
            return;
        }

        const start = (tablePage - 1) * tablePerPage;
        const pagedItems = items.slice(start, start + tablePerPage);

        tableBody.innerHTML = pagedItems.map((item) => {
            const id = Number(item.id || 0);
            return `
                <tr>
                    <td>${item.codigo_comunidad || '-'}</td>
                    <td>
                        <strong>${item.nombre_comunidad || ''}</strong>
                        <div class="small text-secondary">SNIP: ${item.numero_snip || '-'}</div>
                    </td>
                    <td>${item.municipio || ''}</td>
                    <td><span class="badge ${asBadgeClass(item.fase_actual)}">${asLabelFase(item.fase_actual)}</span></td>
                    <td>${item.estado_actual || ''}</td>
                    <td>${item.division_origen || '-'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary" data-action="bitacora" data-id="${id}"><i class="bi bi-clock-history"></i></button>
                        <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${id}"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
            `;
        }).join('');

        renderTablePager(items.length);
    }

    async function loadItems() {
        const q = (document.getElementById('filtroBusqueda').value || '').trim();
        const fase = document.getElementById('filtroFase').value || '';

        const result = await postEncrypted(endpoints.list, { q, fase_actual: fase });

        if (!result?.ok) {
            notify(result?.data?.message || 'No fue posible cargar comunidades.', 'danger');
            return;
        }

        cacheItems = result.data?.items || [];
        tablePage = 1;
        renderTable(cacheItems);
    }

    function resetForm() {
        document.getElementById('comunidadId').value = '';
        document.getElementById('codigoComunidad').value = '';
        document.getElementById('numeroSnip').value = '';
        document.getElementById('nombreComunidad').value = '';
        document.getElementById('municipio').value = '';
        document.getElementById('departamento').value = '';
        document.getElementById('faseActual').value = 'fase_1';
        document.getElementById('estadoActual').value = 'Ingresado';
        document.getElementById('divisionOrigen').value = '';
        document.getElementById('solicitudFirmada').checked = false;
        document.getElementById('estudioSocioeconomico').checked = false;
        document.getElementById('snipAprobado').checked = false;
        document.getElementById('licitacionTerminada').checked = false;
        document.getElementById('obraEnergizada').checked = false;
        document.getElementById('btnEliminar').classList.add('d-none');
        document.getElementById('comunidadModalTitle').textContent = 'Nueva comunidad';
    }

    function fillForm(item) {
        document.getElementById('comunidadId').value = item.id || '';
        document.getElementById('codigoComunidad').value = item.codigo_comunidad || '';
        document.getElementById('numeroSnip').value = item.numero_snip || '';
        document.getElementById('nombreComunidad').value = item.nombre_comunidad || '';
        document.getElementById('municipio').value = item.municipio || '';
        document.getElementById('departamento').value = item.departamento || '';
        document.getElementById('faseActual').value = item.fase_actual || 'fase_1';
        document.getElementById('estadoActual').value = item.estado_actual || 'Ingresado';
        document.getElementById('divisionOrigen').value = item.division_origen || '';
        document.getElementById('solicitudFirmada').checked = Number(item.solicitud_firmada || 0) === 1;
        document.getElementById('estudioSocioeconomico').checked = Number(item.estudio_socioeconomico || 0) === 1;
        document.getElementById('snipAprobado').checked = Number(item.snip_aprobado || 0) === 1;
        document.getElementById('licitacionTerminada').checked = Number(item.licitacion_terminada || 0) === 1;
        document.getElementById('obraEnergizada').checked = Number(item.obra_energizada || 0) === 1;
        document.getElementById('btnEliminar').classList.remove('d-none');
        document.getElementById('comunidadModalTitle').textContent = 'Editar comunidad';
    }

    async function openBitacora(id) {
        const body = document.getElementById('bitacoraBody');
        body.innerHTML = '<p class="text-secondary mb-0">Cargando bitacora...</p>';
        getBitacoraModal().show();

        const result = await fetchEncrypted(`${endpoints.bitacoraBase}/${id}/bitacora`);

        if (!result?.ok) {
            body.innerHTML = `<p class="text-danger mb-0">${result?.data?.message || 'No fue posible cargar la bitacora.'}</p>`;
            return;
        }

        const items = result?.data?.items || [];

        if (items.length === 0) {
            body.innerHTML = '<p class="text-secondary mb-0">Sin movimientos registrados.</p>';
            return;
        }

        body.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Fase</th>
                            <th>Estado</th>
                            <th>Descripcion</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map((row) => `
                            <tr>
                                <td>${row.fecha_evento || ''}</td>
                                <td>${asLabelFase(row.fase_clave || '')}</td>
                                <td>${row.estado || ''}</td>
                                <td>${row.descripcion || ''}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    document.getElementById('btnNuevaComunidad').addEventListener('click', () => {
        resetForm();
        getModal().show();
    });

    document.getElementById('btnFiltrar').addEventListener('click', loadItems);

    tableBody.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;

        const id = Number(button.getAttribute('data-id') || 0);
        const action = button.getAttribute('data-action') || '';
        const item = cacheItems.find((row) => Number(row.id || 0) === id);

        if (!id || !item) return;

        if (action === 'edit') {
            fillForm(item);
            getModal().show();
            return;
        }

        if (action === 'bitacora') {
            await openBitacora(id);
        }
    });

    tablePager?.addEventListener('click', (event) => {
        const navButton = event.target.closest('button[data-nav]');
        if (navButton) {
            const nav = navButton.getAttribute('data-nav');
            const totalPages = Math.max(1, Math.ceil(cacheItems.length / tablePerPage));

            if (nav === 'prev' && tablePage > 1) {
                tablePage -= 1;
                renderTable(cacheItems);
            }

            if (nav === 'next' && tablePage < totalPages) {
                tablePage += 1;
                renderTable(cacheItems);
            }

            return;
        }

        const button = event.target.closest('button[data-page]');
        if (!button) {
            return;
        }

        const nextPage = Number(button.getAttribute('data-page') || 1);
        if (nextPage <= 0) {
            return;
        }

        tablePage = nextPage;
        renderTable(cacheItems);
    });

    document.getElementById('comunidadForm').addEventListener('submit', async (event) => {
        event.preventDefault();

        const id = Number(document.getElementById('comunidadId').value || 0);
        const payload = {
            codigo_comunidad: document.getElementById('codigoComunidad').value || '',
            numero_snip: document.getElementById('numeroSnip').value || '',
            nombre_comunidad: document.getElementById('nombreComunidad').value || '',
            municipio: document.getElementById('municipio').value || '',
            departamento: document.getElementById('departamento').value || '',
            fase_actual: document.getElementById('faseActual').value || 'fase_1',
            estado_actual: document.getElementById('estadoActual').value || 'Ingresado',
            division_origen: document.getElementById('divisionOrigen').value || '',
            solicitud_firmada: document.getElementById('solicitudFirmada').checked ? 1 : 0,
            estudio_socioeconomico: document.getElementById('estudioSocioeconomico').checked ? 1 : 0,
            snip_aprobado: document.getElementById('snipAprobado').checked ? 1 : 0,
            licitacion_terminada: document.getElementById('licitacionTerminada').checked ? 1 : 0,
            obra_energizada: document.getElementById('obraEnergizada').checked ? 1 : 0,
        };

        const endpoint = id > 0 ? `${endpoints.updateBase}/${id}` : endpoints.store;
        const result = await postEncrypted(endpoint, payload);

        if (!result?.ok) {
            notify(result?.data?.message || 'No fue posible guardar la comunidad.', 'danger');
            return;
        }

        notify(result?.data?.message || 'Comunidad guardada correctamente.');
        getModal().hide();
        await loadItems();
    });

    document.getElementById('btnEliminar').addEventListener('click', () => {
        const id = Number(document.getElementById('comunidadId').value || 0);
        if (!id) return;

        confirmAction('Se eliminara la comunidad y su bitacora asociada. Esta accion no es reversible.', async () => {
            const result = await postEncrypted(`${endpoints.updateBase}/${id}/delete`, { id });
            if (!result?.ok) {
                notify(result?.data?.message || 'No fue posible eliminar la comunidad.', 'danger');
                return;
            }
            notify(result?.data?.message || 'Comunidad eliminada.');
            getModal().hide();
            await loadItems();
        }, { title: 'Confirmar eliminacion', danger: true });
    });

    async function refreshUploadLogs() {
        logsStatus.className = 'small mt-2';
        logsStatus.textContent = 'Consultando logs de carga...';

        try {
            const res = await fetch(endpoints.sourceLogs, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const payload = await res.json();

            if (!payload?.ok) {
                logsStatus.className = 'small mt-2 text-danger';
                logsStatus.textContent = payload?.message || 'No se pudieron consultar los logs.';
                return;
            }

            const rows = Array.isArray(payload.data) ? payload.data : [];
            if (!rows.length) {
                logsTableBody.innerHTML = '';
                logsWrap.classList.add('d-none');
                logsEmpty.classList.remove('d-none');
                logsStatus.textContent = '';
                return;
            }

            logsTableBody.innerHTML = rows.map((item) => `
                <tr>
                    <td>${escapeHtml(String(item.cargado_en || '').replace('T', ' ').slice(0, 19))}</td>
                    <td>${escapeHtml(item.division || '')}</td>
                    <td>${escapeHtml(item.tabla_fisica || '')}</td>
                    <td class="text-end">${escapeHtml(String(item.nuevos || 0))}</td>
                    <td class="text-end">${escapeHtml(String(item.modificados || 0))}</td>
                    <td class="text-end">${escapeHtml(String(item.total_filas || 0))}</td>
                </tr>
            `).join('');

            logsWrap.classList.remove('d-none');
            logsEmpty.classList.add('d-none');
            logsStatus.textContent = '';
        } catch (error) {
            logsStatus.className = 'small mt-2 text-danger';
            logsStatus.textContent = 'No se pudieron consultar los logs.';
        }
    }

    function renderSourceSchema(fuenteId) {
        const source = loadedSources.find((item) => Number(item.id) === Number(fuenteId));
        if (!source || !Array.isArray(source.columnas) || source.columnas.length === 0) {
            schemaTableWrap.classList.add('d-none');
            schemaEmpty.classList.remove('d-none');
            schemaTableBody.innerHTML = '';
            return;
        }

        schemaEmpty.classList.add('d-none');
        schemaTableWrap.classList.remove('d-none');

        schemaTableBody.innerHTML = source.columnas.map((col) => {
            const phase = col.fase_clave || '';
            const show = Number(col.mostrar_landing || 0) === 1;
            const hito = Number(col.usar_para_hito || 0) === 1;
            const card = Number(col.mostrar_card || 0) === 1;
            const label = col.etiqueta_landing || prettify(col.nombre_normalizado);
            const sample = col.muestra_valor ? `<div class="text-muted small">Ejemplo: ${escapeHtml(col.muestra_valor)}</div>` : '';

            return `
                <tr data-schema-row data-col="${escapeHtml(col.nombre_normalizado)}">
                    <td>
                        <div class="fw-semibold">${escapeHtml(col.nombre_original)}</div>
                        <div class="text-muted small">${escapeHtml(col.nombre_normalizado)}</div>
                        ${sample}
                    </td>
                    <td><input type="text" class="form-control form-control-sm" data-field="label" value="${escapeHtml(label)}"></td>
                    <td class="text-center"><input type="checkbox" data-field="show" ${show ? 'checked' : ''}></td>
                    <td>
                        <select class="form-select form-select-sm" data-field="phase">
                            <option value="" ${phase === '' ? 'selected' : ''}>Sin fase</option>
                            <option value="fase_1" ${phase === 'fase_1' ? 'selected' : ''}>Fase 1</option>
                            <option value="fase_2" ${phase === 'fase_2' ? 'selected' : ''}>Fase 2</option>
                            <option value="fase_3" ${phase === 'fase_3' ? 'selected' : ''}>Fase 3</option>
                        </select>
                    </td>
                    <td class="text-center"><input type="checkbox" data-field="hito" ${hito ? 'checked' : ''}></td>
                    <td class="text-center"><input type="checkbox" data-field="card" ${card ? 'checked' : ''}></td>
                </tr>
            `;
        }).join('');
    }

    async function refreshSchemaSources(selectFuenteId = 0) {
        schemaStatus.className = 'small mt-2';
        schemaStatus.textContent = 'Consultando esquemas cargados...';

        try {
            const res = await fetch(endpoints.sourceSchema, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const payload = await res.json();

            if (!payload?.ok) {
                schemaStatus.className = 'small mt-2 text-danger';
                schemaStatus.textContent = payload?.message || 'No se pudieron obtener esquemas.';
                return;
            }

            loadedSources = payload.data || [];
            schemaSourceSelect.innerHTML = '<option value="">Selecciona una division...</option>' + loadedSources.map((source) => {
                const date = String(source.creado_en || source.created_at || '').replace('T', ' ').slice(0, 19);
                return `<option value="${source.id}">${escapeHtml(source.division || '')} · ${escapeHtml(source.nombre_archivo || '')} · ${escapeHtml(source.tabla_fisica || '')} (${escapeHtml(date)})</option>`;
            }).join('');

            if (selectFuenteId) {
                schemaSourceSelect.value = String(selectFuenteId);
            }

            if (schemaSourceSelect.value) {
                renderSourceSchema(Number(schemaSourceSelect.value));
            } else {
                schemaTableWrap.classList.add('d-none');
                schemaEmpty.classList.remove('d-none');
                schemaTableBody.innerHTML = '';
            }

            schemaStatus.textContent = loadedSources.length ? '' : 'No hay archivos fuente cargados aun.';
        } catch (error) {
            schemaStatus.className = 'small mt-2 text-danger';
            schemaStatus.textContent = 'No se pudieron consultar los esquemas.';
        }
    }

    uploadForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const division = document.getElementById('division').value;
        const archivo = document.getElementById('archivo').files[0];

        if (!division || !archivo) {
            return;
        }

        const formData = new FormData();
        formData.append('division', division);
        formData.append('archivo', archivo);
        appendCsrfToFormData(formData);

        uploadStatus.className = 'small mt-2';
        uploadStatus.textContent = 'Subiendo archivo y sincronizando division...';

        try {
            const res = await fetch(endpoints.sourceUpload, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                credentials: 'same-origin',
            });

            const payload = await res.json();
            if (!payload?.ok) {
                uploadStatus.className = 'small mt-2 text-danger';
                uploadStatus.textContent = `Error: ${payload?.message || 'No se pudo procesar la carga.'}`;
                return;
            }

            uploadStatus.className = 'small mt-2 text-success';
            uploadStatus.textContent = `Division ${payload.division} sincronizada. Filas: ${payload.total_filas}. Nuevos: ${payload.created}. Actualizados: ${payload.updated}. Sin cambios: ${payload.unchanged}. Eliminados: ${payload.deleted}.`;

            uploadForm.reset();
            await Promise.all([
                refreshSchemaSources(payload.fuente_id || 0),
                refreshUploadLogs(),
                loadItems(),
            ]);
        } catch (error) {
            uploadStatus.className = 'small mt-2 text-danger';
            uploadStatus.textContent = 'Error de red o backend no disponible.';
        }
    });

    refreshSchemasBtn?.addEventListener('click', async () => {
        await refreshSchemaSources();
    });

    cleanupTablesBtn?.addEventListener('click', () => {
        confirmAction('Se eliminaran tablas legadas detectadas y metadatos historicos de esquemas/logs. Esta accion no elimina las comunidades actuales.', async () => {
            uploadStatus.className = 'small mt-2';
            uploadStatus.textContent = 'Ejecutando limpieza...';

            try {
                const res = await fetch(endpoints.sourceCleanup, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({ [CSRF_NAME]: CSRF_TOKEN }),
                    credentials: 'same-origin',
                });

                const payload = await res.json();
                if (!payload?.ok) {
                    uploadStatus.className = 'small mt-2 text-danger';
                    uploadStatus.textContent = `Error: ${payload?.message || 'No se pudo completar la limpieza.'}`;
                    return;
                }

                uploadStatus.className = 'small mt-2 text-success';
                uploadStatus.textContent = `Limpieza completada. Tablas eliminadas: ${(payload.dropped_tables || []).length}. Registros historicos eliminados: ${payload.deleted_sources || 0}.`;
                await Promise.all([refreshSchemaSources(), refreshUploadLogs()]);
            } catch (error) {
                uploadStatus.className = 'small mt-2 text-danger';
                uploadStatus.textContent = 'No se pudo completar la limpieza.';
            }
        }, { title: 'Confirmar limpieza', danger: true });
    });

    refreshLogsBtn?.addEventListener('click', async () => {
        await refreshUploadLogs();
    });

    schemaSourceSelect?.addEventListener('change', () => {
        const fuenteId = Number(schemaSourceSelect.value || 0);
        renderSourceSchema(fuenteId);
    });

    saveMappingBtn?.addEventListener('click', async () => {
        const fuenteId = Number(schemaSourceSelect?.value || 0);
        if (!fuenteId) {
            return;
        }

        const rows = [...document.querySelectorAll('[data-schema-row]')];
        const mapeos = rows.map((row) => ({
            columna_normalizada: row.getAttribute('data-col'),
            etiqueta_landing: row.querySelector('[data-field="label"]').value,
            mostrar_landing: row.querySelector('[data-field="show"]').checked,
            fase_clave: row.querySelector('[data-field="phase"]').value || null,
            usar_para_hito: row.querySelector('[data-field="hito"]').checked,
            mostrar_card: row.querySelector('[data-field="card"]').checked,
        }));

        schemaStatus.className = 'small mt-2';
        schemaStatus.textContent = 'Guardando mapeo...';

        try {
            const res = await fetch(endpoints.sourceSchemaSave, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({ fuente_id: fuenteId, mapeos, [CSRF_NAME]: CSRF_TOKEN }),
                credentials: 'same-origin',
            });
            const payload = await res.json();

            if (!payload?.ok) {
                schemaStatus.className = 'small mt-2 text-danger';
                schemaStatus.textContent = payload?.message || 'No se pudo guardar el mapeo.';
                return;
            }

            schemaStatus.className = 'small mt-2 text-success';
            schemaStatus.textContent = 'Mapeo guardado correctamente.';
            await refreshSchemaSources(fuenteId);
        } catch (error) {
            schemaStatus.className = 'small mt-2 text-danger';
            schemaStatus.textContent = 'Error de red al guardar mapeo.';
        }
    });

    Promise.all([loadItems(), refreshSchemaSources(), refreshUploadLogs()]).catch(() => {
        notify('No fue posible cargar el estado inicial del modulo.', 'danger');
    });
})();
</script>

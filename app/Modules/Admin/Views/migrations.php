<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="bi bi-database-gear me-2"></i>Control de Migraciones</h2>
        <p class="text-secondary mb-0">Vista administrativa para revisar estado, ejecutar migraciones y auditar errores recientes.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" id="refreshStatusBtn">
            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar estado
        </button>
        <button class="btn btn-primary" id="runLatestBtn">
            <i class="bi bi-play-circle me-1"></i>Ejecutar latest
        </button>
        <button class="btn btn-outline-danger" id="runRollbackBtn">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Rollback ultimo batch
        </button>
    </div>
</div>

<style>
    .migration-errors-scroll {
        max-height: 460px;
        overflow-y: auto;
        padding-right: .25rem;
    }
</style>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-uppercase small text-secondary mb-1">Migraciones aplicadas</p>
                <h3 class="h2 mb-0" id="summaryApplied">0</h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-uppercase small text-secondary mb-1">Pendientes</p>
                <h3 class="h2 mb-0" id="summaryPending">0</h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-uppercase small text-secondary mb-1">Último batch</p>
                <h3 class="h2 mb-0" id="summaryBatch">0</h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h4 class="h6 mb-0">Migraciones pendientes</h4>
                <span class="badge text-bg-warning" id="pendingCountBadge">0</span>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="pendingTable">
                        <thead>
                            <tr>
                                <th style="width:170px">Versión</th>
                                <th>Clase</th>
                                <th style="width:130px">Módulo</th>
                                <th>Namespace</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" class="text-secondary">Sin datos todavía.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                    <small class="text-secondary" id="pendingPageInfo">Mostrando 0 de 0</small>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Paginación pendientes">
                        <button class="btn btn-outline-secondary" id="pendingPrevBtn" type="button">Anterior</button>
                        <button class="btn btn-outline-secondary" id="pendingNextBtn" type="button">Siguiente</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h4 class="h6 mb-0">Historial de migraciones aplicadas</h4>
                <span class="badge text-bg-success" id="appliedCountBadge">0</span>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="appliedTable">
                        <thead>
                            <tr>
                                <th style="width:170px">Versión</th>
                                <th>Clase</th>
                                <th style="width:130px">Módulo</th>
                                <th style="width:90px">Batch</th>
                                <th style="width:220px">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="5" class="text-secondary">Sin datos todavía.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                    <small class="text-secondary" id="appliedPageInfo">Mostrando 0 de 0</small>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Paginación aplicadas">
                        <button class="btn btn-outline-secondary" id="appliedPrevBtn" type="button">Anterior</button>
                        <button class="btn btn-outline-secondary" id="appliedNextBtn" type="button">Siguiente</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h4 class="h6 mb-0">Errores recientes</h4>
                <span class="badge text-bg-danger" id="errorCountBadge">0</span>
            </div>
            <div class="card-body pt-0">
                <div id="recentErrors" class="vstack gap-2 migration-errors-scroll">
                    <div class="text-secondary">Sin datos todavía.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const migrationEndpoints = {
    status: <?= json_encode(site_url('admin/api/migraciones/status')) ?>,
    execute: <?= json_encode(site_url('admin/api/migraciones/execute')) ?>,
};

const tablePageSize = 10;
let pendingRowsAll = [];
let appliedRowsAll = [];
let pendingPage = 1;
let appliedPage = 1;

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function timestampToText(value) {
    const ts = Number(value || 0);
    if (!Number.isFinite(ts) || ts <= 0) {
        return '-';
    }

    try {
        return new Date(ts * 1000).toLocaleString('es-GT');
    } catch {
        return String(ts);
    }
}

function pageMetrics(totalRows, page) {
    const safeTotal = Math.max(0, Number(totalRows || 0));
    const totalPages = Math.max(1, Math.ceil(safeTotal / tablePageSize));
    const safePage = Math.min(Math.max(1, page), totalPages);
    const start = safeTotal === 0 ? 0 : ((safePage - 1) * tablePageSize) + 1;
    const end = safeTotal === 0 ? 0 : Math.min(safePage * tablePageSize, safeTotal);

    return { safeTotal, totalPages, safePage, start, end };
}

function updatePager(prefix, metrics) {
    document.getElementById(prefix + 'PageInfo').textContent = `Mostrando ${metrics.start}-${metrics.end} de ${metrics.safeTotal} · Página ${metrics.safePage}/${metrics.totalPages}`;
    document.getElementById(prefix + 'PrevBtn').disabled = metrics.safePage <= 1;
    document.getElementById(prefix + 'NextBtn').disabled = metrics.safePage >= metrics.totalPages;
}

function renderPending(rows) {
    const tbody = document.querySelector('#pendingTable tbody');
    const badge = document.getElementById('pendingCountBadge');
    pendingRowsAll = Array.isArray(rows) ? rows : [];
    const metrics = pageMetrics(pendingRowsAll.length, pendingPage);
    pendingPage = metrics.safePage;
    badge.textContent = String(metrics.safeTotal);

    if (metrics.safeTotal === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-secondary">No hay migraciones pendientes.</td></tr>';
        updatePager('pending', metrics);
        return;
    }

    const currentRows = pendingRowsAll.slice((pendingPage - 1) * tablePageSize, pendingPage * tablePageSize);

    tbody.innerHTML = currentRows.map(row => `
        <tr>
            <td>${escapeHtml(row.version || '-')}</td>
            <td>${escapeHtml(row.class || row.name || '-')}</td>
            <td><span class="badge text-bg-info">${escapeHtml(row.module || '-')}</span></td>
            <td>${escapeHtml(row.namespace || '-')}</td>
        </tr>
    `).join('');

    updatePager('pending', metrics);
}

function renderApplied(rows) {
    const tbody = document.querySelector('#appliedTable tbody');
    const badge = document.getElementById('appliedCountBadge');
    appliedRowsAll = Array.isArray(rows) ? rows : [];
    const metrics = pageMetrics(appliedRowsAll.length, appliedPage);
    appliedPage = metrics.safePage;
    badge.textContent = String(metrics.safeTotal);

    if (metrics.safeTotal === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-secondary">No hay migraciones aplicadas.</td></tr>';
        updatePager('applied', metrics);
        return;
    }

    const currentRows = appliedRowsAll.slice((appliedPage - 1) * tablePageSize, appliedPage * tablePageSize);

    tbody.innerHTML = currentRows.map(row => `
        <tr>
            <td>${escapeHtml(row.version || '-')}</td>
            <td>${escapeHtml(row.class || '-')}</td>
            <td><span class="badge text-bg-info">${escapeHtml(row.module || '-')}</span></td>
            <td>${escapeHtml(row.batch || '-')}</td>
            <td>${escapeHtml(timestampToText(row.time))}</td>
        </tr>
    `).join('');

    updatePager('applied', metrics);
}

function renderErrors(rows) {
    const box = document.getElementById('recentErrors');
    const badge = document.getElementById('errorCountBadge');
    const safeRows = Array.isArray(rows) ? rows : [];
    badge.textContent = String(safeRows.length);

    if (safeRows.length === 0) {
        box.innerHTML = '<div class="text-secondary">No se detectaron errores recientes de migración en logs.</div>';
        return;
    }

    box.innerHTML = safeRows.map(item => `
        <div class="border rounded-3 p-2 bg-light-subtle">
            <div class="small text-danger fw-semibold mb-1">${escapeHtml(item.file || '-')}</div>
            <div class="small text-secondary">${escapeHtml(item.line || '-')}</div>
        </div>
    `).join('');
}

function renderSummary(summary) {
    document.getElementById('summaryApplied').textContent = String(summary?.applied || 0);
    document.getElementById('summaryPending').textContent = String(summary?.pending || 0);
    document.getElementById('summaryBatch').textContent = String(summary?.last_batch || 0);
}

async function loadMigrationStatus() {
    const result = await fetchEncrypted(migrationEndpoints.status);

    if (!result?.ok) {
        notify(result?.data?.message || 'No fue posible consultar estado de migraciones.', 'danger', 0);
        return;
    }

    renderSummary(result.data?.summary || {});
    renderPending(result.data?.pending || []);
    renderApplied(result.data?.applied || []);
    renderErrors(result.data?.recent_errors || []);
}

async function executeMigrationAction(action) {
    const form = new FormData();
    form.append('action', action);

    const result = await fetchEncrypted(migrationEndpoints.execute, {
        method: 'POST',
        body: form,
    });

    if (!result?.ok) {
        notify(result?.data?.message || 'No fue posible ejecutar la accion.', 'danger', 0);
        if (result?.data?.error) {
            notify(result.data.error, 'warning', 0);
        }
        await loadMigrationStatus();
        return;
    }

    notify(result?.data?.message || 'Accion ejecutada correctamente.', 'success');
    await loadMigrationStatus();
}

document.getElementById('refreshStatusBtn')?.addEventListener('click', () => loadMigrationStatus());

document.getElementById('pendingPrevBtn')?.addEventListener('click', () => {
    pendingPage = Math.max(1, pendingPage - 1);
    renderPending(pendingRowsAll);
});

document.getElementById('pendingNextBtn')?.addEventListener('click', () => {
    pendingPage = pendingPage + 1;
    renderPending(pendingRowsAll);
});

document.getElementById('appliedPrevBtn')?.addEventListener('click', () => {
    appliedPage = Math.max(1, appliedPage - 1);
    renderApplied(appliedRowsAll);
});

document.getElementById('appliedNextBtn')?.addEventListener('click', () => {
    appliedPage = appliedPage + 1;
    renderApplied(appliedRowsAll);
});

document.getElementById('runLatestBtn')?.addEventListener('click', () => {
    confirmAction('Esto ejecutara todas las migraciones pendientes. ¿Deseas continuar?', () => {
        executeMigrationAction('latest');
    }, { title: 'Ejecutar migraciones', danger: false });
});

document.getElementById('runRollbackBtn')?.addEventListener('click', () => {
    confirmAction('Se revertira el ultimo batch de migraciones. Esta accion puede afectar datos. ¿Continuar?', () => {
        executeMigrationAction('rollback');
    }, { title: 'Rollback de migraciones', danger: true });
});

loadMigrationStatus();
</script>

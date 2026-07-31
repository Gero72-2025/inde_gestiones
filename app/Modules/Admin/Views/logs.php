<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-journal-text me-2"></i>Logs de Acciones y Errores</h2>
</div>

<form id="logsFilterForm" class="row g-2 mb-3">
    <div class="col-12 col-md-3">
        <select class="form-select" name="level">
            <option value="">Todos los tipos</option>
            <option value="http.request">http.request</option>
            <option value="auth.login.success">auth.login.success</option>
            <option value="auth.login.failed">auth.login.failed</option>
            <option value="auth.logout">auth.logout</option>
        </select>
    </div>
    <div class="col-12 col-md-6">
        <input class="form-control" name="q" placeholder="Buscar por usuario, endpoint o accion...">
    </div>
    <div class="col-12 col-md-3 d-grid">
        <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped align-middle" id="logsTable">
        <thead>
            <tr>
                <th style="width:70px">ID</th>
                <th style="width:160px">Fecha</th>
                <th style="width:130px">Usuario</th>
                <th style="width:130px">Tipo</th>
                <th>Accion</th>
                <th style="width:90px">Status</th>
                <th style="width:80px">IP</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div id="logsPager" class="d-flex justify-content-between align-items-center mt-2"></div>

<script>
let logsPage = 1;
const logsPerPage = 25;

function escLog(v) {
    return String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

async function loadLogs(page = 1) {
    logsPage = page;
    const form = new FormData(document.getElementById('logsFilterForm'));
    const q = new URLSearchParams({
        page: String(logsPage),
        per_page: String(logsPerPage),
        level: String(form.get('level') || ''),
        q: String(form.get('q') || ''),
    });

    const result = await fetchEncrypted(`<?= site_url('admin/api/logs') ?>?${q.toString()}`);
    const rows = result?.data?.logs || [];
    const pg = result?.data?.pagination || { page: 1, pages: 1, total: 0 };

    const tbody = document.querySelector('#logsTable tbody');
    tbody.innerHTML = rows.map(r => {
        const code = Number(r.status_code || 0);
        const cls = code >= 500 ? 'bg-danger' : code >= 400 ? 'bg-warning text-dark' : 'bg-success';
        return `<tr>
            <td>${r.id}</td>
            <td><small>${escLog(r.created_at)}</small></td>
            <td>${escLog(r.username || 'anonimo')}</td>
            <td><code>${escLog(r.event_type)}</code></td>
            <td>${escLog(r.action)}</td>
            <td><span class="badge ${cls}">${escLog(r.status_code || '')}</span></td>
            <td><small>${escLog(r.ip_address || '')}</small></td>
        </tr>`;
    }).join('');

    renderLogsPager(pg.page || 1, pg.pages || 1, pg.total || 0);
}

function renderLogsPager(page, pages, total) {
    const pager = document.getElementById('logsPager');
    if (pages <= 1) {
        pager.innerHTML = `<small class="text-muted">${total} registro(s)</small>`;
        return;
    }

    let buttons = '';
    for (let i = 1; i <= pages; i++) {
        buttons += `<button class="btn btn-sm ${i === page ? 'btn-primary' : 'btn-outline-secondary'} me-1" onclick="loadLogs(${i})">${i}</button>`;
    }

    pager.innerHTML = `<small class="text-muted">Pagina ${page} de ${pages} (${total} registros)</small><div>${buttons}</div>`;
}

document.getElementById('logsFilterForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    await loadLogs(1);
});

loadLogs(1);
</script>

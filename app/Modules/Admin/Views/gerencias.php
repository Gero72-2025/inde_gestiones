<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Gerencias</h2>
</div>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createGerenciaModal">
    <i class="bi bi-plus-lg me-1"></i> Nueva Gerencia
</button>

<!-- Create Modal -->
<div class="modal fade" id="createGerenciaModal" tabindex="-1" aria-labelledby="createGerenciaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="gerenciaForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="createGerenciaModalLabel"><i class="bi bi-building me-2"></i>Nueva Gerencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input class="form-control" name="nombre" placeholder="Nombre de la gerencia" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                        <input class="form-control" name="slug" placeholder="ej: gero" required>
                        <div class="form-text">Identificador unico en minusculas sin espacios.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion</label>
                        <input class="form-control" name="descripcion" placeholder="Descripcion opcional">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Crear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editGerenciaModal" tabindex="-1" aria-labelledby="editGerenciaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editGerenciaForm" novalidate>
                <input type="hidden" id="editGerenciaId">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGerenciaModalLabel"><i class="bi bi-pencil-square me-2"></i>Editar Gerencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input class="form-control" id="editNombre" name="nombre" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Slug</label>
                        <input class="form-control" id="editSlug" name="slug">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion</label>
                        <input class="form-control" id="editDescripcion" name="descripcion">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Estado</label>
                        <select class="form-select" id="editStatus" name="status">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                        </select>
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
    <table class="table table-striped align-middle" id="gerenciasTable">
        <thead>
            <tr>
                <th style="width:60px">ID</th>
                <th>Nombre</th>
                <th>Slug</th>
                <th style="width:100px">Estado</th>
                <th style="width:140px">Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
<div id="gerenciasPager" class="d-flex justify-content-between align-items-center mt-2 mb-1"></div>

<script>
let gerenciasData = [];
let gerenciasPage = 1;
const gerenciasPerPage = 10;

function escAttr(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function renderGerencias() {
    const start = (gerenciasPage - 1) * gerenciasPerPage;
    const page = gerenciasData.slice(start, start + gerenciasPerPage);
    const tbody = document.querySelector('#gerenciasTable tbody');
    tbody.innerHTML = page.map(row => {
        const badge = row.status === 'active' ? 'bg-success' : 'bg-secondary';
        return `<tr>
            <td>${row.id}</td>
            <td>${escAttr(row.nombre)}</td>
            <td><code>${escAttr(row.slug)}</code></td>
            <td><span class="badge ${badge}">${row.status}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1"
                    data-id="${row.id}"
                    data-nombre="${escAttr(row.nombre)}"
                    data-slug="${escAttr(row.slug)}"
                    data-desc="${escAttr(row.descripcion)}"
                    data-status="${row.status}"
                    onclick="openEditGerencia(this)">Editar</button>
                <button class="btn btn-sm btn-outline-danger"
                    data-id="${row.id}"
                    data-nombre="${escAttr(row.nombre)}"
                    onclick="deleteGerencia(this)">Eliminar</button>
            </td>
        </tr>`;
    }).join('');
    renderGerenciasPager();
}

function renderGerenciasPager() {
    const total = gerenciasData.length;
    const pages = Math.ceil(total / gerenciasPerPage);
    const pager = document.getElementById('gerenciasPager');
    if (pages <= 1) { pager.innerHTML = ''; return; }
    let btns = '';
    for (let i = 1; i <= pages; i++) {
        btns += `<button class="btn btn-sm ${i === gerenciasPage ? 'btn-primary' : 'btn-outline-secondary'} me-1" onclick="goGerenciasPage(${i})">${i}</button>`;
    }
    const from = Math.min((gerenciasPage - 1) * gerenciasPerPage + 1, total);
    const to   = Math.min(gerenciasPage * gerenciasPerPage, total);
    pager.innerHTML = `<small class="text-muted">Mostrando ${from}&ndash;${to} de ${total}</small><div>${btns}</div>`;
}

function goGerenciasPage(p) { gerenciasPage = p; renderGerencias(); }

async function loadGerencias() {
    const result = await fetchEncrypted('<?= site_url('admin/api/gerencias') ?>');
    gerenciasData = result?.data?.gerencias || [];
    gerenciasPage = 1;
    renderGerencias();
}

function openEditGerencia(btn) {
    document.getElementById('editGerenciaId').value  = btn.dataset.id;
    document.getElementById('editNombre').value       = btn.dataset.nombre;
    document.getElementById('editSlug').value         = btn.dataset.slug;
    document.getElementById('editDescripcion').value  = btn.dataset.desc;
    document.getElementById('editStatus').value       = btn.dataset.status;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editGerenciaModal')).show();
}

async function deleteGerencia(btn) {
    confirmAction(
        `¿Eliminar la gerencia "${btn.dataset.nombre}"? Esta accion no se puede deshacer.`,
        async () => {
    const result = await fetchEncrypted(`<?= site_url('admin/api/gerencias/') ?>${btn.dataset.id}/delete`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    notify(result?.data?.message || 'Eliminado.', result?.data?.message ? 'success' : 'danger');
    await loadGerencias();
        },
        { title: 'Eliminar Gerencia', danger: true }
    );
}

document.getElementById('gerenciaForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await fetchEncrypted('<?= site_url('admin/api/gerencias') ?>', {
        method: 'POST',
        body: new FormData(e.target),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (result?.data?.message?.toLowerCase().includes('error') || result?.data?.message?.toLowerCase().includes('ya existe')) {
        notify(result.data.message, 'danger', 0);
    } else {
        notify(result?.data?.message || 'Gerencia creada correctamente.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('createGerenciaModal'))?.hide();
        e.target.reset();
        await loadGerencias();
    }
});

document.getElementById('editGerenciaForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editGerenciaId').value;
    const result = await fetchEncrypted(`<?= site_url('admin/api/gerencias/') ?>${id}`, {
        method: 'POST',
        body: new FormData(e.target),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (result?.data?.message?.toLowerCase().includes('error')) {
        notify(result.data.message, 'danger', 0);
    } else {
        notify(result?.data?.message || 'Gerencia actualizada.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('editGerenciaModal'))?.hide();
        await loadGerencias();
    }
});

loadGerencias();
</script>

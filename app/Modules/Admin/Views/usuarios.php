<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Usuarios por Gerencia</h2>
</div>

<button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createUserModal">
    <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
</button>

<!-- Create Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="userForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel"><i class="bi bi-person-plus me-2"></i>Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-semibold">Usuario <span class="text-danger">*</span></label>
                        <input class="form-control" name="username" placeholder="nombre.usuario" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-semibold">Correo <span class="text-danger">*</span></label>
                        <input class="form-control" type="email" name="email" placeholder="correo@inde.gob.gt" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-semibold">Contrasena <span class="text-danger">*</span></label>
                        <input class="form-control" name="password" type="password" placeholder="Min. 8 caracteres" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label fw-semibold">Gerencia <span class="text-danger">*</span></label>
                        <select class="form-select" name="gerencia_id" required>
                            <?php foreach (($gerencias ?? []) as $gerencia): ?>
                                <option value="<?= esc($gerencia['id']) ?>"><?= esc($gerencia['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Rol</label>
                        <select class="form-select" name="role_nombre">
                            <option>Lector</option>
                            <option>Editor</option>
                            <option>Administrador de Gerencia</option>
                        </select>
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

<!-- Manage Roles Modal -->
<div class="modal fade" id="manageRolesModal" tabindex="-1" aria-labelledby="manageRolesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="manageRolesForm">
                <input type="hidden" id="manageRolesUserId">
                <div class="modal-header">
                    <h5 class="modal-title" id="manageRolesLabel">
                        <i class="bi bi-shield-lock me-2"></i>Roles de: <span id="manageRolesUsername" class="text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Selecciona los roles que tendra este usuario. Los permisos se calculan en base a los roles asignados.</p>
                    <div id="roleCheckboxes" class="vstack gap-2"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar roles</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editUserForm" novalidate>
                <input type="hidden" id="editUserId">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel"><i class="bi bi-pencil-square me-2"></i>Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Usuario</label>
                        <input class="form-control bg-light" id="editUsername" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Gerencia</label>
                        <select class="form-select" id="editUserGerencia" name="gerencia_id">
                            <?php foreach (($gerencias ?? []) as $gerencia): ?>
                                <option value="<?= esc($gerencia['id']) ?>"><?= esc($gerencia['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Estado</label>
                        <select class="form-select" id="editUserStatus" name="status">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nueva contrasena <small class="text-muted fw-normal">(dejar vacio para no cambiar)</small></label>
                        <input class="form-control" name="password" type="password" placeholder="Nueva contrasena">
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
    <table class="table table-striped align-middle" id="usersTable">
        <thead>
            <tr>
                <th style="width:60px">ID</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Gerencia</th>
                <th style="width:140px">Estado</th>
                <th style="width:165px">Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
<div id="usersPager" class="d-flex justify-content-between align-items-center mt-2 mb-1"></div>

<script>
let usersData = [];
let usersPage = 1;
const usersPerPage = 10;

function escAttrU(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function renderUsers() {
    const start = (usersPage - 1) * usersPerPage;
    const page = usersData.slice(start, start + usersPerPage);
    const tbody = document.querySelector('#usersTable tbody');
    tbody.innerHTML = page.map(row => {
        const badge = row.status === 'active' ? 'bg-success' : 'bg-secondary';
        return `<tr>
            <td>${row.id}</td>
            <td>${escAttrU(row.username)}</td>
            <td>${escAttrU(row.email)}</td>
            <td>${escAttrU(row.gerencia_nombre)}</td>
            <td><span class="badge ${badge}">${row.status}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-secondary me-1"
                    data-id="${row.id}"
                    data-username="${escAttrU(row.username)}"
                    onclick="openManageRoles(this)"
                    title="Gestionar Roles"><i class="bi bi-shield-lock"></i></button>
                <button class="btn btn-sm btn-outline-primary me-1"
                    data-id="${row.id}"
                    data-username="${escAttrU(row.username)}"
                    data-gerencia="${row.gerencia_id || ''}"
                    data-status="${row.status}"
                    onclick="openEditUser(this)"><i class="bi bi-pencil-square"></i></button>
                <button class="btn btn-sm btn-outline-danger"
                    data-id="${row.id}"
                    data-username="${escAttrU(row.username)}"
                    onclick="deleteUser(this)"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`;
    }).join('');
    renderUsersPager();
}

function renderUsersPager() {
    const total = usersData.length;
    const pages = Math.ceil(total / usersPerPage);
    const pager = document.getElementById('usersPager');
    if (pages <= 1) { pager.innerHTML = ''; return; }
    let btns = '';
    for (let i = 1; i <= pages; i++) {
        btns += `<button class="btn btn-sm ${i === usersPage ? 'btn-primary' : 'btn-outline-secondary'} me-1" onclick="goUsersPage(${i})">${i}</button>`;
    }
    const from = Math.min((usersPage - 1) * usersPerPage + 1, total);
    const to   = Math.min(usersPage * usersPerPage, total);
    pager.innerHTML = `<small class="text-muted">Mostrando ${from}&ndash;${to} de ${total}</small><div>${btns}</div>`;
}

function goUsersPage(p) { usersPage = p; renderUsers(); }

async function loadUsers() {
    const result = await fetchEncrypted('<?= site_url('admin/api/usuarios') ?>');
    usersData = result?.data?.usuarios || [];
    usersPage = 1;
    renderUsers();
}

async function openManageRoles(btn) {
    const id       = btn.dataset.id;
    const username = btn.dataset.username;
    document.getElementById('manageRolesUserId').value    = id;
    document.getElementById('manageRolesUsername').textContent = username;
    document.getElementById('roleCheckboxes').innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span> Cargando...</div>';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('manageRolesModal')).show();

    const result = await fetchEncrypted(`<?= site_url('admin/api/usuarios/') ?>${id}/roles`);
    const allRoles   = result?.data?.all_roles || [];
    const assignedIds = (result?.data?.assigned_role_ids || []).map(Number);

    document.getElementById('roleCheckboxes').innerHTML = allRoles.map(r => `
        <div class="form-check border rounded p-2">
            <input class="form-check-input" type="checkbox" name="role_ids[]" value="${r.id}" id="role_${r.id}"
                ${assignedIds.includes(Number(r.id)) ? 'checked' : ''}>
            <label class="form-check-label w-100" for="role_${r.id}">
                <span class="fw-semibold">${r.nombre}</span>
                ${r.descripcion ? `<span class="d-block text-muted small">${r.descripcion}</span>` : ''}
            </label>
        </div>
    `).join('');
}

document.getElementById('manageRolesForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('manageRolesUserId').value;
    const result = await fetchEncrypted(`<?= site_url('admin/api/usuarios/') ?>${id}/roles`, {
        method: 'POST',
        body: new FormData(e.target),
    });
    if (result?.ok === false) {
        notify(result?.data?.message || 'No fue posible actualizar roles.', 'danger', 0);
    } else {
        notify(result?.data?.message || 'Roles actualizados.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('manageRolesModal'))?.hide();
    }
});

function openEditUser(btn) {
    document.getElementById('editUserId').value          = btn.dataset.id;
    document.getElementById('editUsername').value        = btn.dataset.username;
    document.getElementById('editUserGerencia').value    = btn.dataset.gerencia;
    document.getElementById('editUserStatus').value      = btn.dataset.status;
    document.querySelector('#editUserForm [name="password"]').value = '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal')).show();
}

async function deleteUser(btn) {
    confirmAction(
        `¿Eliminar al usuario "${btn.dataset.username}"? Esta accion no se puede deshacer.`,
        async () => {
    const result = await fetchEncrypted(`<?= site_url('admin/api/usuarios/') ?>${btn.dataset.id}/delete`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    notify(result?.data?.message || 'Eliminado.', result?.data?.message ? 'success' : 'danger');
    await loadUsers();
        },
        { title: 'Eliminar Usuario', danger: true }
    );
}

document.getElementById('userForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await fetchEncrypted('<?= site_url('admin/api/usuarios') ?>', {
        method: 'POST',
        body: new FormData(e.target),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (result?.ok === false) {
        notify(result?.data?.message || 'No fue posible crear el usuario.', 'danger', 0);
    } else {
        notify(result?.data?.message || 'Usuario creado correctamente.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('createUserModal'))?.hide();
        e.target.reset();
        await loadUsers();
    }
});

document.getElementById('editUserForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editUserId').value;
    const result = await fetchEncrypted(`<?= site_url('admin/api/usuarios/') ?>${id}`, {
        method: 'POST',
        body: new FormData(e.target),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (result?.ok === false) {
        notify(result?.data?.message || 'No fue posible actualizar el usuario.', 'danger', 0);
    } else {
        notify(result?.data?.message || 'Usuario actualizado.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('editUserModal'))?.hide();
        await loadUsers();
    }
});

loadUsers();
</script>

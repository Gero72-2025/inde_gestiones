<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-shield-lock me-2"></i>Roles y Permisos</h2>
    <?php if ($isSuperAdmin ?? false): ?>
    <button class="btn btn-outline-primary" id="syncModulesBtn">
        <i class="bi bi-arrow-repeat me-1"></i>Sincronizar Modulos
    </button>
    <?php endif; ?>
</div>

<!-- Tabs -->
<ul class="nav nav-pills mb-4" id="rolesTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-roles-btn" data-bs-toggle="pill" data-bs-target="#tabRoles" type="button" role="tab">
            <i class="bi bi-person-badge me-1"></i>Roles
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-perms-btn" data-bs-toggle="pill" data-bs-target="#tabPerms" type="button" role="tab">
            <i class="bi bi-key me-1"></i>Permisos
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ── TAB: ROLES ── -->
    <div class="tab-pane fade show active" id="tabRoles" role="tabpanel">
        <?php if ($isSuperAdmin ?? false): ?>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createRoleModal">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Rol
        </button>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped align-middle" id="rolesTable">
                <thead>
                    <tr>
                        <th style="width:60px">ID</th>
                        <th style="width:180px">Nombre</th>
                        <th>Permisos asignados</th>
                        <?php if ($isSuperAdmin ?? false): ?>
                        <th style="width:100px">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- ── TAB: PERMISOS ── -->
    <div class="tab-pane fade" id="tabPerms" role="tabpanel">
        <?php if ($isSuperAdmin ?? false): ?>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createPermModal">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Permiso
        </button>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped align-middle" id="permsTable">
                <thead>
                    <tr>
                        <th style="width:60px">ID</th>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Descripcion</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Create Role Modal -->
<?php if ($isSuperAdmin ?? false): ?>
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="createRoleForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="createRoleLabel"><i class="bi bi-person-badge me-2"></i>Nuevo Rol</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input class="form-control" name="nombre" placeholder="Ej: Auditor" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion</label>
                        <input class="form-control" name="descripcion" placeholder="Descripcion del rol">
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
<?php endif; ?>

<!-- Edit Role Permissions Modal -->
<div class="modal fade" id="editRolePermsModal" tabindex="-1" aria-labelledby="editRolePermsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form id="editRolePermsForm">
                <input type="hidden" id="editRoleId">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRolePermsLabel">
                        <i class="bi bi-shield-check me-2"></i>Permisos del rol: <span id="editRoleName" class="text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Marca los permisos que deseas asignar a este rol. Los cambios aplican a todos los usuarios que tienen este rol.</p>
                    <div id="permCheckboxes" class="row g-2"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar permisos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Permission Modal -->
<?php if ($isSuperAdmin ?? false): ?>
<div class="modal fade" id="createPermModal" tabindex="-1" aria-labelledby="createPermLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="createPermForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="createPermLabel"><i class="bi bi-key me-2"></i>Nuevo Permiso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input class="form-control" name="nombre" placeholder="Ej: Acceso al modulo X" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                        <input class="form-control" name="slug" placeholder="ej: gerencia.x.access" required>
                        <div class="form-text">Identificador unico en formato <code>entidad.accion</code>.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion</label>
                        <input class="form-control" name="descripcion" placeholder="Para que sirve este permiso">
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
<?php endif; ?>

<script>
const isSuperAdmin = <?= json_encode((bool) ($isSuperAdmin ?? false)) ?>;
let allPermissions = [];

<?php if ($isSuperAdmin ?? false): ?>
document.getElementById('createRoleForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await fetchEncrypted('<?= site_url('admin/api/roles') ?>', {
        method: 'POST',
        body: new FormData(e.target),
    });

    if (result?.ok === false) {
        notify(result?.data?.message || 'No fue posible crear el rol.', 'danger', 0);
    } else {
        notify(result?.data?.message || 'Rol creado correctamente.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('createRoleModal'))?.hide();
        e.target.reset();
        await loadRoles();
    }
});

document.getElementById('syncModulesBtn').addEventListener('click', async () => {
    const result = await fetchEncrypted('<?= site_url('admin/api/permissions/sync-modules') ?>', {
        method: 'POST',
    });

    notify(
        `${result?.data?.message || 'Sincronizado.'} Modulos: ${result?.data?.total_modulos || 0}, gerencias nuevas: ${result?.data?.gerencias_created || 0}, permisos nuevos: ${result?.data?.permissions_created || 0}, enlaces superadmin: ${result?.data?.superadmin_links_created || 0}`,
        'success'
    );

    await loadPermissions();
    await loadRoles();
});
<?php endif; ?>

/* ── Render Roles table ── */
function renderRoles(roles) {
    const tbody = document.querySelector('#rolesTable tbody');
    tbody.innerHTML = roles.map(role => {
        const badges = role.permissions.map(p =>
            `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 me-1 mb-1">${p.slug}</span>`
        ).join('') || '<span class="text-muted small">Sin permisos</span>';

        const actions = isSuperAdmin
            ? `<button class="btn btn-sm btn-outline-primary"
                data-id="${role.id}"
                data-nombre="${role.nombre.replace(/"/g, '&quot;')}"
                data-pids="${role.permissions.map(p => p.id).join(',')}"
                onclick="openEditRolePerms(this)">
                <i class="bi bi-pencil-square"></i>
               </button>`
            : '';

        return `<tr>
            <td>${role.id}</td>
            <td><strong>${role.nombre}</strong></td>
            <td>${badges}</td>
            ${isSuperAdmin ? `<td>${actions}</td>` : ''}
        </tr>`;
    }).join('');
}

/* ── Render Permissions table ── */
function renderPermissions(permissions) {
    const tbody = document.querySelector('#permsTable tbody');
    tbody.innerHTML = permissions.map(p => `<tr>
        <td>${p.id}</td>
        <td>${p.nombre}</td>
        <td><code>${p.slug}</code></td>
        <td><small class="text-muted">${p.descripcion || ''}</small></td>
    </tr>`).join('');
}

/* ── Load Roles ── */
async function loadRoles() {
    const result = await fetchEncrypted('<?= site_url('admin/api/roles') ?>');
    renderRoles(result?.data?.roles || []);
}

/* ── Load Permissions ── */
async function loadPermissions() {
    const result = await fetchEncrypted('<?= site_url('admin/api/permissions') ?>');
    allPermissions = result?.data?.permissions || [];
    renderPermissions(allPermissions);
}

/* ── Open edit role permissions modal ── */
async function openEditRolePerms(btn) {
    if (!Array.isArray(allPermissions) || allPermissions.length === 0) {
        await loadPermissions();
    }

    document.getElementById('editRoleId').value = btn.dataset.id;
    document.getElementById('editRoleName').textContent = btn.dataset.nombre;
    const assigned = new Set(
        (btn.dataset.pids ? btn.dataset.pids.split(',') : [])
            .map(value => String(value || '').trim())
            .filter(Boolean)
    );

    document.getElementById('permCheckboxes').innerHTML = allPermissions.map(p => `
        <div class="col-12 col-md-6">
            <div class="form-check border rounded p-2">
                <input class="form-check-input" type="checkbox" name="permission_ids[]"
                    value="${p.id}" id="perm_${p.id}"
                    ${assigned.has(String(p.id)) ? 'checked' : ''}>
                <label class="form-check-label w-100" for="perm_${p.id}">
                    <span class="d-block fw-semibold small">${p.slug}</span>
                    <span class="d-block text-muted" style="font-size:.78rem">${p.nombre}</span>
                </label>
            </div>
        </div>
    `).join('');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('editRolePermsModal')).show();
}

/* ── Save role permissions ── */
document.getElementById('editRolePermsForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editRoleId').value;
    const form = new FormData(e.target);

    const result = await fetchEncrypted(`<?= site_url('admin/api/roles/') ?>${id}/permissions`, {
        method: 'POST',
        body: form,
    });

    if (result?.data?.message?.toLowerCase().includes('error')) {
        notify(result.data.message, 'danger', 0);
    } else {
        notify(result?.data?.message || 'Permisos actualizados.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('editRolePermsModal'))?.hide();
        await loadRoles();
    }
});

/* ── Create permission ── */
<?php if ($isSuperAdmin ?? false): ?>
document.getElementById('createPermForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = await fetchEncrypted('<?= site_url('admin/api/permissions') ?>', {
        method: 'POST',
        body: new FormData(e.target),
    });

    if (result?.data?.message?.toLowerCase().includes('error') || result?.data?.message?.toLowerCase().includes('ya existe')) {
        notify(result.data.message, 'danger', 0);
    } else {
        notify(result?.data?.message || 'Permiso creado.', 'success');
        bootstrap.Modal.getInstance(document.getElementById('createPermModal'))?.hide();
        e.target.reset();
        await loadPermissions();
    }
});
<?php endif; ?>

/* ── Init ── */
Promise.all([loadRoles(), loadPermissions()]);
</script>

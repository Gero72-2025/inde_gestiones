<?php
$rows = (array) ($rows ?? []);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Lineas de Sistema SNI</h1>
        <p class="text-secondary mb-0">Administra las lineas de sistema para asociarlas a lineas electrificadas (rutas y puntos).</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= esc(site_url('admin/etcee/sni')) ?>">
            <i class="bi bi-diagram-3 me-1"></i>Volver a SNI
        </a>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#lineaSistemaModal" onclick="resetLineaSistemaForm()">
            <i class="bi bi-plus-circle me-1"></i>Nueva Linea de Sistema
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h6 mb-0">Lineas registradas</h2>
            <small class="text-secondary d-block">Listado completo de lineas de sistema</small>
        </div>
        <span class="badge text-bg-primary"><?= count($rows) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Nombre</th>
                    <th style="width: 220px;">Slug</th>
                    <th>Descripcion</th>
                    <th style="width: 120px;">Estado</th>
                    <th style="width: 160px;">Registro</th>
                    <th style="width: 170px;" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">No existen lineas de sistema registradas.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?= (int) ($row['id'] ?? 0) ?></strong></td>
                        <td><?= esc((string) ($row['nombre'] ?? '')) ?></td>
                        <td><code><?= esc((string) ($row['slug'] ?? '')) ?></code></td>
                        <td class="small"><?= esc((string) ($row['descripcion'] ?? '')) ?></td>
                        <td>
                            <?php $estado = (string) ($row['estado'] ?? 'inactivo'); ?>
                            <span class="badge <?= $estado === 'activo' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= esc(ucfirst($estado)) ?>
                            </span>
                        </td>
                        <td class="small text-secondary"><?= esc((string) ($row['fecha_registro'] ?? '')) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#lineaSistemaModal" onclick="loadLineaSistemaForEdit(<?= (int) ($row['id'] ?? 0) ?>)">
                                <i class="bi bi-pencil"></i>Editar
                            </button>
                            <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteLineaSistemaConfirm(<?= (int) ($row['id'] ?? 0) ?>)">
                                <i class="bi bi-trash"></i>Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="lineaSistemaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="lineaSistemaModalTitle">Nueva Linea de Sistema</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="lineaSistemaForm" class="needs-validation" novalidate>
                <input type="hidden" id="lineaSistemaId" name="id" value="">
                <input type="hidden" name="action" value="save">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input class="form-control" id="lineaSistemaNombre" name="nombre" required maxlength="180" placeholder="Ej. Lineas Sistema Occidental">
                            <div class="invalid-feedback">El nombre es requerido.</div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">Slug</label>
                            <input class="form-control" id="lineaSistemaSlug" name="slug" maxlength="180" placeholder="Ej. lineas-sistema-occidental">
                            <small class="text-secondary">Si lo dejas vacio se genera desde el nombre.</small>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" id="lineaSistemaEstado" name="estado">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripcion</label>
                            <textarea class="form-control" id="lineaSistemaDescripcion" name="descripcion" rows="3" maxlength="1000" placeholder="Descripcion opcional"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const lineaSistemaConfig = {
        baseUrl: <?= json_encode(site_url('admin/etcee/sni/lineas-sistema')) ?>,
        getUrl: <?= json_encode(site_url('admin/etcee/sni/lineas-sistema/get')) ?>,
        csrfName: <?= json_encode(csrf_token()) ?>,
        csrfValue: <?= json_encode(csrf_hash()) ?>,
    };

    function lineaSistemaNotify(message, type) {
        if (typeof notify === 'function') {
            notify(message, type);
            return;
        }

        alert(message);
    }

    function resetLineaSistemaForm() {
        const form = document.getElementById('lineaSistemaForm');
        form.reset();
        document.getElementById('lineaSistemaId').value = '';
        document.getElementById('lineaSistemaEstado').value = 'activo';
        document.getElementById('lineaSistemaModalTitle').textContent = 'Nueva Linea de Sistema';
        form.classList.remove('was-validated');
    }

    function loadLineaSistemaForEdit(id) {
        if (id <= 0) return;

        fetch(lineaSistemaConfig.getUrl + '/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.ok) {
                lineaSistemaNotify(data?.message || 'No fue posible cargar la linea de sistema.', 'error');
                return;
            }

            const row = data.data || {};
            document.getElementById('lineaSistemaId').value = row.id || '';
            document.getElementById('lineaSistemaNombre').value = row.nombre || '';
            document.getElementById('lineaSistemaSlug').value = row.slug || '';
            document.getElementById('lineaSistemaEstado').value = row.estado || 'activo';
            document.getElementById('lineaSistemaDescripcion').value = row.descripcion || '';
            document.getElementById('lineaSistemaModalTitle').textContent = 'Editar Linea de Sistema';
            document.getElementById('lineaSistemaForm').classList.remove('was-validated');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('lineaSistemaModal')).show();
        })
        .catch(() => lineaSistemaNotify('Error al cargar la linea de sistema.', 'error'));
    }

    function deleteLineaSistemaConfirm(id) {
        if (!confirm('Eliminar esta linea de sistema?')) return;

        const form = new FormData();
        form.append('action', 'delete');
        form.append('id', id);
        form.append(lineaSistemaConfig.csrfName, lineaSistemaConfig.csrfValue);

        fetch(lineaSistemaConfig.baseUrl, {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.ok) {
                lineaSistemaNotify(data.message || 'Linea de sistema eliminada.', 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                lineaSistemaNotify(data?.message || 'No se pudo eliminar la linea de sistema.', 'error');
            }
        })
        .catch(() => lineaSistemaNotify('Error al eliminar la linea de sistema.', 'error'));
    }

    document.getElementById('lineaSistemaForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        const form = new FormData(this);
        form.append(lineaSistemaConfig.csrfName, lineaSistemaConfig.csrfValue);

        try {
            const response = await fetch(lineaSistemaConfig.baseUrl, {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data && data.ok) {
                lineaSistemaNotify(data.message || 'Linea de sistema guardada correctamente.', 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                lineaSistemaNotify(data?.message || 'No se pudo guardar la linea de sistema.', 'error');
            }
        } catch (error) {
            lineaSistemaNotify('Error: ' + error.message, 'error');
        }
    });
</script>

<?php
$records = (array) ($records ?? []);
$empresas = (array) ($empresas ?? []);
$empresasAdmin = (array) ($empresasAdmin ?? []);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-building text-primary me-2"></i>EEM ECOE</h1>
        <p class="text-secondary mb-0">Consulta, rastreo y visualización de respuestas EEM almacenadas en tablas dinámicas.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= esc(site_url('gerencias/ecoe/formularios')) ?>">Volver al motor</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Empresas activas</h2>
                <ul class="mb-0 ps-3">
                    <?php foreach ($empresas as $empresa): ?>
                        <li><?= esc((string) ($empresa['nombre'] ?? '')) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6">Búsqueda rápida</h2>
                <p class="text-secondary mb-0">El formulario público expone la misma consulta por DPI o código de referencia.</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">CRUD de empresas para desplegable EEM</h2>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnEmpresaReset">Nuevo</button>
        </div>

        <form method="post" action="<?= esc(site_url('gerencias/ecoe/eem')) ?>" class="row g-3" id="empresaForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_empresa">
            <input type="hidden" name="empresa_id" id="empresaId" value="0">

            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold">Nombre de empresa</label>
                <input type="text" class="form-control" name="nombre" id="empresaNombre" maxlength="180" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold">Estado</label>
                <select class="form-select" name="estado" id="empresaEstado">
                    <option value="1" selected>Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Descripción</label>
                <input type="text" class="form-control" name="descripcion" id="empresaDescripcion" maxlength="2000">
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnEmpresaCancelar">Cancelar edición</button>
                <button type="submit" class="btn btn-primary" id="btnEmpresaSubmit">Guardar empresa</button>
            </div>
        </form>

        <hr>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($empresasAdmin === []): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-3">No hay empresas registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($empresasAdmin as $empresa): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc((string) ($empresa['nombre'] ?? '')) ?></td>
                                <td class="text-muted"><?= esc((string) ($empresa['descripcion'] ?? '')) ?></td>
                                <td>
                                    <span class="badge <?= (int) ($empresa['estado'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        <?= (int) ($empresa['estado'] ?? 0) === 1 ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary js-editar-empresa"
                                        data-id="<?= (int) ($empresa['id'] ?? 0) ?>"
                                        data-nombre="<?= esc((string) ($empresa['nombre'] ?? ''), 'attr') ?>"
                                        data-descripcion="<?= esc((string) ($empresa['descripcion'] ?? ''), 'attr') ?>"
                                        data-estado="<?= (int) ($empresa['estado'] ?? 0) ?>"
                                    >Editar</button>
                                    <form method="post" action="<?= esc(site_url('gerencias/ecoe/eem')) ?>" class="d-inline js-delete-empresa ms-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_empresa">
                                        <input type="hidden" name="empresa_id" value="<?= (int) ($empresa['id'] ?? 0) ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Formulario</th>
                        <th>Referencia</th>
                        <th>Solicitante</th>
                        <th>DPI</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">Todavía no hay respuestas EEM registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><span class="badge text-bg-dark"><?= esc((string) ($record['formulario_codigo'] ?? '')) ?></span> <?= esc((string) ($record['formulario_nombre'] ?? '')) ?></td>
                                <td><code><?= esc((string) ($record['codigo_referencia'] ?? '-')) ?></code></td>
                                <td><?= esc((string) ($record['nombre_solicitante'] ?? $record['nombre'] ?? '-')) ?></td>
                                <td><?= esc((string) ($record['dpi'] ?? '-')) ?></td>
                                <td><?= esc((string) ($record['created_at'] ?? '-')) ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" type="button"
                                            onclick="openEemDetail('<?= esc((string) ($record['formulario_codigo'] ?? '')) ?>', <?= (int) ($record['id'] ?? 0) ?>)">
                                        Detalle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="eemDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de respuesta EEM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eemDetailBody">
                <div class="text-secondary">Selecciona un registro para ver el detalle.</div>
            </div>
        </div>
    </div>
</div>

<script>
function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function openEemDetail(codigo, id) {
    const form = new FormData();
    form.append('action', 'detalle');
    form.append('formulario_codigo', codigo);
    form.append('registro_id', id);

    const data = await fetchEncrypted(<?= json_encode(site_url('gerencias/ecoe/eem')) ?>, { method: 'POST', body: form });
    const body = document.getElementById('eemDetailBody');

    if (!data.ok) {
        body.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(data.data?.message || 'No fue posible obtener el detalle.') + '</div>';
    } else {
        const registro = data.data.registro || {};
        const campos = data.data.campos || [];
        body.innerHTML = '<div class="row g-3">'
            + '<div class="col-12 col-md-6"><strong>Referencia:</strong> ' + escapeHtml(registro.codigo_referencia || '-') + '</div>'
            + '<div class="col-12 col-md-6"><strong>Estado:</strong> ' + escapeHtml(registro.estado_tramite || 'recibido') + '</div>'
            + '<div class="col-12"><hr></div>'
            + campos.map(field => {
                const slug = field.slug || '';
                const value = registro[slug] || '-';
                return '<div class="col-12 col-md-6"><div class="border rounded-3 p-3 bg-light"><div class="small text-secondary">' + escapeHtml(field.etiqueta || field.nombre || slug) + '</div><div class="fw-semibold">' + escapeHtml(value) + '</div></div></div>';
            }).join('')
            + '</div>';
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('eemDetailModal')).show();
}

(function () {
    const empresaForm = document.getElementById('empresaForm');

    if (!empresaForm) {
        return;
    }

    const empresaId = document.getElementById('empresaId');
    const empresaNombre = document.getElementById('empresaNombre');
    const empresaDescripcion = document.getElementById('empresaDescripcion');
    const empresaEstado = document.getElementById('empresaEstado');
    const btnEmpresaSubmit = document.getElementById('btnEmpresaSubmit');
    const btnEmpresaReset = document.getElementById('btnEmpresaReset');
    const btnEmpresaCancelar = document.getElementById('btnEmpresaCancelar');

    function resetEmpresaForm() {
        empresaId.value = '0';
        empresaForm.reset();
        empresaEstado.value = '1';
        btnEmpresaSubmit.textContent = 'Guardar empresa';
    }

    btnEmpresaReset?.addEventListener('click', resetEmpresaForm);
    btnEmpresaCancelar?.addEventListener('click', resetEmpresaForm);

    empresaForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const endpoint = empresaForm.getAttribute('action') || '';

        if (endpoint === '') {
            notify('No se encontró la ruta para guardar empresas.', 'danger');
            return;
        }

        const response = await fetchEncrypted(endpoint, {
            method: 'POST',
            body: new FormData(empresaForm),
        });

        if (response.ok) {
            notify(response.data?.message || 'Empresa guardada correctamente.', 'success');
            setTimeout(() => window.location.reload(), 450);
        } else {
            notify(response.data?.message || 'No fue posible guardar la empresa.', 'danger');
        }
    });

    document.querySelectorAll('.js-editar-empresa').forEach(button => {
        button.addEventListener('click', function () {
            empresaId.value = this.dataset.id || '0';
            empresaNombre.value = this.dataset.nombre || '';
            empresaDescripcion.value = this.dataset.descripcion || '';
            empresaEstado.value = this.dataset.estado || '1';
            btnEmpresaSubmit.textContent = 'Actualizar empresa';
            empresaForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelectorAll('.js-delete-empresa').forEach(form => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!confirm('¿Eliminar esta empresa del catálogo?')) {
                return;
            }

            const endpoint = form.getAttribute('action') || '';
            const response = await fetchEncrypted(endpoint, {
                method: 'POST',
                body: new FormData(form),
            });

            if (response.ok) {
                notify(response.data?.message || 'Empresa eliminada correctamente.', 'success');
                setTimeout(() => window.location.reload(), 450);
            } else {
                notify(response.data?.message || 'No fue posible eliminar la empresa.', 'danger');
            }
        });
    });
})();
</script>
<?php
$estados = (array) ($estados ?? []);
$oldNombre = old('nombre');
$oldOrden = old('orden_paso');
$oldDescripcion = old('descripcion');
$oldEstadoId = (int) old('estado_id');
$shouldReopenModal = $oldNombre !== null || $oldOrden !== null || $oldDescripcion !== null || $oldEstadoId > 0;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-signpost-split-fill text-primary me-2"></i>Tarifa Social - Estados de Gestion</h1>
        <p class="text-secondary mb-0">Administra el catalogo de estados del flujo operativo.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" id="btnNuevoEstado">
            <i class="bi bi-plus-circle me-1"></i>Nuevo estado
        </button>
        <a class="btn btn-outline-secondary" href="<?= esc(site_url('gerencias/ecoe/tarifa-social')) ?>">
            <i class="bi bi-arrow-left me-1"></i>Volver a solicitudes
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Paso</th>
                        <th>Descripcion</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($estados)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">No hay estados registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($estados as $estado): ?>
                            <?php $estadoId = (int) ($estado['id'] ?? 0); ?>
                            <tr>
                                <td class="text-muted small"><?= $estadoId ?></td>
                                <td class="fw-semibold"><?= esc((string) ($estado['nombre'] ?? '')) ?></td>
                                <td><span class="badge text-bg-secondary"><?= (int) ($estado['orden_paso'] ?? 0) ?></span></td>
                                <td class="text-secondary small"><?= esc((string) ($estado['descripcion'] ?? '')) ?></td>
                                <td class="text-end">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary me-1 btn-editar-estado"
                                            data-id="<?= $estadoId ?>"
                                            data-nombre="<?= esc((string) ($estado['nombre'] ?? ''), 'attr') ?>"
                                            data-orden="<?= (int) ($estado['orden_paso'] ?? 0) ?>"
                                            data-descripcion="<?= esc((string) ($estado['descripcion'] ?? ''), 'attr') ?>"
                                            title="Editar estado">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-eliminar-estado"
                                            data-id="<?= $estadoId ?>"
                                            data-nombre="<?= esc((string) ($estado['nombre'] ?? ''), 'attr') ?>"
                                            title="Eliminar estado">
                                        <i class="bi bi-trash"></i>
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

<form method="post" action="<?= esc(site_url('gerencias/ecoe/tarifa-social/estados')) ?>" id="deleteEstadoForm" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="eliminar">
    <input type="hidden" name="estado_id" id="deleteEstadoId" value="0">
</form>

<div class="modal fade" id="estadoCrudModal" tabindex="-1" aria-labelledby="estadoCrudModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post" action="<?= esc(site_url('gerencias/ecoe/tarifa-social/estados')) ?>" id="estadoCrudForm">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="estadoCrudModalLabel">Nuevo estado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body pt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="guardar">
                    <input type="hidden" name="estado_id" id="estadoIdInput" value="0">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre</label>
                        <input type="text"
                               name="nombre"
                               id="estadoNombreInput"
                               maxlength="60"
                               class="form-control"
                               required
                               placeholder="Ej. En Revision">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Orden de paso</label>
                        <input type="number"
                               name="orden_paso"
                               id="estadoOrdenInput"
                               min="1"
                               max="255"
                               class="form-control"
                               required>
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Descripcion</label>
                        <textarea name="descripcion"
                                  id="estadoDescripcionInput"
                                  class="form-control"
                                  maxlength="255"
                                  rows="3"
                                  placeholder="Detalle breve del estado y su objetivo."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit" id="estadoSubmitBtn">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="estadoDeleteModal" tabindex="-1" aria-labelledby="estadoDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-semibold" id="estadoDeleteModalLabel">Confirmar eliminacion</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0 text-secondary" id="estadoDeleteMessage"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-danger" id="estadoDeleteConfirmBtn">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<p class="small text-secondary mt-3 mb-0">
    Nota: no se pueden eliminar estados que ya fueron usados en tickets o bitacora.
</p>

<script>
(function () {
    'use strict';

    function initEstadoModals() {
        if (typeof window.bootstrap === 'undefined' || !window.bootstrap.Modal) {
            return;
        }

        const crudModalEl = document.getElementById('estadoCrudModal');
        const deleteModalEl = document.getElementById('estadoDeleteModal');
        const crudModal = window.bootstrap.Modal.getOrCreateInstance(crudModalEl);
        const deleteModal = window.bootstrap.Modal.getOrCreateInstance(deleteModalEl);

        const idInput = document.getElementById('estadoIdInput');
        const nombreInput = document.getElementById('estadoNombreInput');
        const ordenInput = document.getElementById('estadoOrdenInput');
        const descripcionInput = document.getElementById('estadoDescripcionInput');
        const titleEl = document.getElementById('estadoCrudModalLabel');
        const submitBtn = document.getElementById('estadoSubmitBtn');

        const deleteIdInput = document.getElementById('deleteEstadoId');
        const deleteMessage = document.getElementById('estadoDeleteMessage');
        const deleteConfirmBtn = document.getElementById('estadoDeleteConfirmBtn');
        const deleteForm = document.getElementById('deleteEstadoForm');

        function openCreateModal() {
            titleEl.textContent = 'Nuevo estado';
            submitBtn.textContent = 'Crear estado';
            idInput.value = '0';
            nombreInput.value = '';
            ordenInput.value = '1';
            descripcionInput.value = '';
            crudModal.show();
        }

        function openEditModal(data) {
            titleEl.textContent = 'Editar estado';
            submitBtn.textContent = 'Actualizar estado';
            idInput.value = String(data.id || 0);
            nombreInput.value = data.nombre || '';
            ordenInput.value = String(data.orden || 1);
            descripcionInput.value = data.descripcion || '';
            crudModal.show();
        }

        document.getElementById('btnNuevoEstado').addEventListener('click', openCreateModal);

        document.querySelectorAll('.btn-editar-estado').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openEditModal({
                    id: this.dataset.id,
                    nombre: this.dataset.nombre,
                    orden: this.dataset.orden,
                    descripcion: this.dataset.descripcion,
                });
            });
        });

        document.querySelectorAll('.btn-eliminar-estado').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.dataset.id || '0';
                const nombre = this.dataset.nombre || 'este estado';

                deleteIdInput.value = id;
                deleteMessage.textContent = 'Se eliminara el estado "' + nombre + '". Esta accion no se puede deshacer.';
                deleteModal.show();
            });
        });

        deleteConfirmBtn.addEventListener('click', function () {
            deleteModal.hide();
            deleteForm.submit();
        });

        const reopenOnValidationError = <?= $shouldReopenModal ? 'true' : 'false' ?>;
        if (reopenOnValidationError) {
            openEditModal({
                id: <?= (int) $oldEstadoId ?>,
                nombre: <?= json_encode((string) ($oldNombre ?? '')) ?>,
                orden: <?= json_encode((string) ($oldOrden ?? '1')) ?>,
                descripcion: <?= json_encode((string) ($oldDescripcion ?? '')) ?>,
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEstadoModals);
    } else {
        initEstadoModals();
    }
})();
</script>

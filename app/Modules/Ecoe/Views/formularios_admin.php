<?php
$formularios = (array) ($formularios ?? []);
$selectedForm = is_array($selectedForm ?? null) ? $selectedForm : null;
$selectedFields = (array) ($selectedFields ?? []);
$moduloOptions = (array) ($moduloOptions ?? []);
$fieldTypeOptions = (array) ($fieldTypeOptions ?? []);
$selectedFormId = (int) ($selectedFormId ?? 0);

$editMode = $selectedForm !== null;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-sliders text-primary me-2"></i>Motor de Formularios ECOE</h1>
        <p class="text-secondary mb-0">Crea formularios, añade campos y mantén la tabla física sincronizada con Forge.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-primary" href="<?= esc(site_url('gerencias/ecoe/grandes-usuarios')) ?>">Grandes Usuarios</a>
        <a class="btn btn-outline-primary" href="<?= esc(site_url('gerencias/ecoe/eem')) ?>">EEM</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Formularios registrados</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Código</th><th>Nombre</th><th class="text-end">Acción</th></tr></thead>
                        <tbody>
                            <?php foreach ($formularios as $form): ?>
                                <tr>
                                    <td><span class="badge text-bg-secondary"><?= esc((string) ($form['codigo'] ?? '')) ?></span></td>
                                    <td>
                                        <div class="fw-semibold"><?= esc((string) ($form['nombre'] ?? '')) ?></div>
                                        <div class="small text-muted"><?= esc((string) ($form['slug'] ?? '')) ?></div>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= esc(site_url('gerencias/ecoe/formularios?form_id=' . (int) ($form['id'] ?? 0))) ?>">Abrir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Campos del formulario</h2>
                <?php if (! $selectedForm): ?>
                    <p class="text-secondary mb-0">Selecciona un formulario para gestionar sus campos.</p>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-semibold"><?= esc((string) $selectedForm['nombre']) ?></div>
                            <div class="small text-muted"><?= esc((string) $selectedForm['codigo']) ?> · <?= esc((string) $selectedForm['slug']) ?></div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Slug</th><th>Tipo</th><th>Estado</th><th class="text-end">Acción</th></tr></thead>
                            <tbody>
                                <?php foreach ($selectedFields as $field): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($field['etiqueta'] ?? $field['nombre'] ?? '')) ?></div>
                                            <div class="small text-muted"><?= esc((string) ($field['slug'] ?? '')) ?></div>
                                        </td>
                                        <td><?= esc((string) ($field['tipo'] ?? '')) ?></td>
                                        <td>
                                            <span class="badge <?= (int) ($field['estado'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                <?= (int) ($field['estado'] ?? 0) === 1 ? 'Activo' : 'Oculto' ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary js-edit-field"
                                                data-id="<?= (int) ($field['id'] ?? 0) ?>"
                                                data-nombre="<?= esc((string) ($field['nombre'] ?? ''), 'attr') ?>"
                                                data-slug="<?= esc((string) ($field['slug'] ?? ''), 'attr') ?>"
                                                data-tipo="<?= esc((string) ($field['tipo'] ?? 'text'), 'attr') ?>"
                                                data-etiqueta="<?= esc((string) ($field['etiqueta'] ?? ''), 'attr') ?>"
                                                data-ayuda="<?= esc((string) ($field['ayuda'] ?? ''), 'attr') ?>"
                                                data-opciones="<?= esc((string) ($field['opciones_json'] ?? ''), 'attr') ?>"
                                                data-orden="<?= (int) ($field['orden'] ?? 0) ?>"
                                                data-obligatorio="<?= (int) ($field['obligatorio'] ?? 0) ?>"
                                                data-visible-pdf="<?= (int) ($field['visible_pdf'] ?? 0) ?>"
                                                data-visible-plantilla="<?= (int) ($field['visible_plantilla'] ?? 0) ?>"
                                                data-estado="<?= (int) ($field['estado'] ?? 0) ?>"
                                            >Editar</button>
                                            <form method="post" action="<?= esc(site_url('gerencias/ecoe/formularios')) ?>" class="d-inline ecoe-ajax-form ms-1">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_field">
                                                <input type="hidden" name="campo_id" value="<?= (int) ($field['id'] ?? 0) ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Ocultar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3"><?= $editMode ? 'Editar formulario' : 'Nuevo formulario' ?></h2>
                <form method="post" action="<?= esc(site_url('gerencias/ecoe/formularios')) ?>" class="row g-3 ecoe-ajax-form" id="formularioForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_form">
                    <input type="hidden" name="id" value="<?= (int) ($selectedForm['id'] ?? 0) ?>">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Nombre</label>
                        <input class="form-control" name="nombre" maxlength="160" required value="<?= esc((string) ($selectedForm['nombre'] ?? '')) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold">Código</label>
                        <input class="form-control" name="codigo" maxlength="40" required value="<?= esc((string) ($selectedForm['codigo'] ?? '')) ?>" <?= $editMode ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold">Slug</label>
                        <input class="form-control" name="slug" maxlength="120" required value="<?= esc((string) ($selectedForm['slug'] ?? '')) ?>" <?= $editMode ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Módulo asignado</label>
                        <select class="form-select" name="modulo_asignado" required>
                            <?php foreach ($moduloOptions as $option): ?>
                                <option value="<?= esc((string) ($option['value'] ?? '')) ?>" <?= (string) ($selectedForm['modulo_asignado'] ?? '') === (string) ($option['value'] ?? '') ? 'selected' : '' ?>>
                                    <?= esc((string) ($option['label'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="1" <?= (int) ($selectedForm['estado'] ?? 1) === 1 ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= (int) ($selectedForm['estado'] ?? 1) === 0 ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"><?= esc((string) ($selectedForm['descripcion'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Plantilla HTML editable</label>
                        <textarea class="form-control font-monospace" name="plantilla_html" rows="10" placeholder="{{titulo}}, {{codigo_referencia}}, {{campos_html}}, {{instrucciones_html}}"><?= esc((string) ($selectedForm['plantilla_html'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Instrucciones HTML</label>
                        <textarea class="form-control font-monospace" name="instrucciones_html" rows="4"><?= esc((string) ($selectedForm['instrucciones_html'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a class="btn btn-outline-secondary" href="<?= esc(site_url('gerencias/ecoe/formularios')) ?>">Limpiar</a>
                        <button class="btn btn-primary" type="submit"><?= $editMode ? 'Actualizar formulario' : 'Crear formulario' ?></button>
                    </div>
                </form>

                <?php if ($editMode): ?>
                    <form method="post" action="<?= esc(site_url('gerencias/ecoe/formularios')) ?>" class="mt-3 d-flex justify-content-end ecoe-ajax-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_form">
                        <input type="hidden" name="formulario_id" value="<?= (int) ($selectedForm['id'] ?? 0) ?>">
                        <button class="btn btn-outline-danger" type="submit">Desactivar formulario</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Agregar campo dinámico</h2>
                <?php if (! $selectedForm): ?>
                    <div class="alert alert-info mb-0">Primero abre un formulario para agregar campos y columnas físicas.</div>
                <?php else: ?>
                    <form method="post" action="<?= esc(site_url('gerencias/ecoe/formularios')) ?>" class="row g-3 ecoe-ajax-form" id="campoForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_field" id="campoAction">
                        <input type="hidden" name="field_id" value="0" id="campoFieldId">
                        <input type="hidden" name="formulario_id" value="<?= (int) ($selectedForm['id'] ?? 0) ?>">
                        <div class="col-12">
                            <div class="alert alert-primary d-none mb-0" id="campoEditAlert">
                                Editando campo existente. Si cambias el slug se renombrará la columna física y se generará una migración.
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input class="form-control" name="nombre" maxlength="160" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Slug</label>
                            <input class="form-control" name="slug" maxlength="120" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select class="form-select" name="tipo" required>
                                <?php foreach ($fieldTypeOptions as $type): ?>
                                    <option value="<?= esc($type) ?>"><?= esc($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Etiqueta</label>
                            <input class="form-control" name="etiqueta" maxlength="160">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">Orden</label>
                            <input class="form-control" name="orden" type="number" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Ayuda</label>
                            <textarea class="form-control" name="ayuda" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Opciones JSON</label>
                            <textarea class="form-control font-monospace" name="opciones_json" rows="3" placeholder='{"allowed_exts":["pdf"],"allowed_mimes":["application/pdf"],"max_mb":8}'></textarea>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="obligatorio" value="1" id="campoObligatorio" checked>
                                <label class="form-check-label" for="campoObligatorio">Obligatorio</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="visible_pdf" value="1" id="campoVisiblePdf" checked>
                                <label class="form-check-label" for="campoVisiblePdf">Visible en PDF</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="visible_plantilla" value="1" id="campoVisiblePlantilla">
                                <label class="form-check-label" for="campoVisiblePlantilla">Visible en plantilla</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="estado" value="1" id="campoEstado" checked>
                                <label class="form-check-label" for="campoEstado">Activo</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-outline-secondary me-2 d-none" type="button" id="campoCancelEdit">Cancelar edición</button>
                            <button class="btn btn-primary" type="submit" id="campoSubmitBtn">Agregar campo y columna</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const forms = document.querySelectorAll('.ecoe-ajax-form');
    forms.forEach(form => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const endpoint = form.getAttribute('action') || '';
            if (endpoint === '') {
                notify('No se encontró la ruta del formulario.', 'danger');
                return;
            }

            try {
                const response = await fetchEncrypted(endpoint, { method: 'POST', body: new FormData(form) });
                const payload = (response && typeof response === 'object')
                    ? (response.data && typeof response.data === 'object' ? response.data : response)
                    : {};

                if (response && response.ok) {
                    notify(payload.message || 'Guardado correctamente.', 'success');
                    setTimeout(() => window.location.reload(), 450);
                } else {
                    notify(payload.message || 'No fue posible guardar.', 'danger');
                }
            } catch (error) {
                notify(error?.message || 'Error inesperado al procesar la solicitud.', 'danger');
            }
        });
    });

    const campoForm = document.getElementById('campoForm');
    if (!campoForm) {
        return;
    }

    const campoAction = document.getElementById('campoAction');
    const campoFieldId = document.getElementById('campoFieldId');
    const campoSubmitBtn = document.getElementById('campoSubmitBtn');
    const campoEditAlert = document.getElementById('campoEditAlert');
    const campoCancelEdit = document.getElementById('campoCancelEdit');

    const inputNombre = campoForm.querySelector('input[name="nombre"]');
    const inputSlug = campoForm.querySelector('input[name="slug"]');
    const inputTipo = campoForm.querySelector('select[name="tipo"]');
    const inputEtiqueta = campoForm.querySelector('input[name="etiqueta"]');
    const inputAyuda = campoForm.querySelector('textarea[name="ayuda"]');
    const inputOpciones = campoForm.querySelector('textarea[name="opciones_json"]');
    const inputOrden = campoForm.querySelector('input[name="orden"]');
    const inputObligatorio = campoForm.querySelector('input[name="obligatorio"]');
    const inputVisiblePdf = campoForm.querySelector('input[name="visible_pdf"]');
    const inputVisiblePlantilla = campoForm.querySelector('input[name="visible_plantilla"]');
    const inputEstado = campoForm.querySelector('input[name="estado"]');

    function resetFieldEditor() {
        campoAction.value = 'save_field';
        campoFieldId.value = '0';
        campoSubmitBtn.textContent = 'Agregar campo y columna';
        campoEditAlert.classList.add('d-none');
        campoCancelEdit.classList.add('d-none');
        campoForm.reset();
        inputObligatorio.checked = true;
        inputVisiblePdf.checked = true;
        inputVisiblePlantilla.checked = false;
        inputEstado.checked = true;
        inputOrden.value = '0';
    }

    campoCancelEdit.addEventListener('click', function () {
        resetFieldEditor();
    });

    document.querySelectorAll('.js-edit-field').forEach(button => {
        button.addEventListener('click', function () {
            campoAction.value = 'update_field';
            campoFieldId.value = this.dataset.id || '0';
            campoSubmitBtn.textContent = 'Actualizar campo';
            campoEditAlert.classList.remove('d-none');
            campoCancelEdit.classList.remove('d-none');

            inputNombre.value = this.dataset.nombre || '';
            inputSlug.value = this.dataset.slug || '';
            inputTipo.value = this.dataset.tipo || 'text';
            inputEtiqueta.value = this.dataset.etiqueta || '';
            inputAyuda.value = this.dataset.ayuda || '';
            inputOpciones.value = this.dataset.opciones || '';
            inputOrden.value = this.dataset.orden || '0';
            inputObligatorio.checked = (this.dataset.obligatorio || '0') === '1';
            inputVisiblePdf.checked = (this.dataset.visiblePdf || '0') === '1';
            inputVisiblePlantilla.checked = (this.dataset.visiblePlantilla || '0') === '1';
            inputEstado.checked = (this.dataset.estado || '0') === '1';

            campoForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
})();
</script>
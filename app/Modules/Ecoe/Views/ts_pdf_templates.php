<?php
/**
 * Vista administrativa - Plantillas PDF Tarifa Social ECOE
 */
$plantillas = (array) ($plantillas ?? []);
$templateEditId = (int) ($templateEditId ?? 0);

$edit = null;
foreach ($plantillas as $tpl) {
    if ((int) ($tpl['id'] ?? 0) === $templateEditId) {
        $edit = $tpl;
        break;
    }
}

$edit = $edit ?: [
    'id' => 0,
    'nombre' => old('nombre', ''),
    'slug' => old('slug', ''),
    'descripcion' => old('descripcion', ''),
    'html_template' => old('html_template', ''),
    'instrucciones_html' => old('instrucciones_html', ''),
    'is_default' => (int) old('is_default', 0),
    'is_active' => (int) old('is_active', 1),
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>Plantillas PDF - Tarifa Social</h1>
        <p class="text-secondary mb-0">Crea, edita, elimina y selecciona la plantilla predeterminada del comprobante PDF.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= esc(site_url('gerencias/ecoe/tarifa-social')) ?>">
            <i class="bi bi-arrow-left me-1"></i>Volver a Solicitudes
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc((string) session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3"><?= (int) $edit['id'] > 0 ? 'Editar plantilla' : 'Nueva plantilla' ?></h2>

                <form method="post" action="<?= esc(site_url('gerencias/ecoe/tarifa-social/plantillas')) ?>" class="row g-3" id="tplForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="guardar">
                    <input type="hidden" name="template_id" value="<?= (int) $edit['id'] ?>">

                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Nombre</label>
                        <input class="form-control" name="nombre" maxlength="120" required value="<?= esc((string) $edit['nombre']) ?>">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Slug</label>
                        <input class="form-control" name="slug" maxlength="120" required value="<?= esc((string) $edit['slug']) ?>" placeholder="ej: base-tarifa-social">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input class="form-control" name="descripcion" maxlength="255" value="<?= esc((string) $edit['descripcion']) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">HTML de plantilla</label>
                        <textarea class="form-control font-monospace" name="html_template" rows="14" required><?= esc((string) $edit['html_template']) ?></textarea>
                        <div class="form-text">Variables disponibles: {{codigo_referencia}}, {{id_solicitud}}, {{nombre}}, {{direccion}}, {{dpi}}, {{telefono}}, {{estado_nombre}}, {{estado_descripcion}}, {{fecha_ingreso}}, {{fecha_emision}}, {{instrucciones_html}}, {{bitacora_html}}</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">HTML de instrucciones</label>
                        <textarea class="form-control font-monospace" name="instrucciones_html" rows="6" required><?= esc((string) $edit['instrucciones_html']) ?></textarea>
                    </div>

                    <div class="col-12 col-lg-3">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefault" <?= (int) $edit['is_default'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isDefault">Predeterminada</label>
                        </div>
                    </div>
                    <div class="col-12 col-lg-3">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= (int) $edit['is_active'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Activa</label>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 d-flex justify-content-end gap-2">
                        <a class="btn btn-outline-secondary" href="<?= esc(site_url('gerencias/ecoe/tarifa-social/plantillas')) ?>">Limpiar</a>
                        <button class="btn btn-outline-dark" type="button" id="btnPreviewDraft">
                            <i class="bi bi-eye me-1"></i>Vista previa
                        </button>
                        <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Guardar plantilla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Plantillas registradas</h2>

                <?php if (empty($plantillas)): ?>
                    <p class="text-secondary mb-0">No hay plantillas registradas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plantillas as $tpl): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) $tpl['nombre']) ?></div>
                                            <div class="small text-muted"><?= esc((string) $tpl['slug']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ((int) ($tpl['is_default'] ?? 0) === 1): ?>
                                                <span class="badge bg-primary">Predeterminada</span>
                                            <?php endif; ?>
                                            <?php if ((int) ($tpl['is_active'] ?? 0) !== 1): ?>
                                                <span class="badge bg-secondary">Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= esc(site_url('gerencias/ecoe/tarifa-social/plantillas/preview/' . (int) $tpl['id'])) ?>" title="Vista previa PDF">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a class="btn btn-sm btn-outline-primary" href="<?= esc(site_url('gerencias/ecoe/tarifa-social/plantillas?edit_id=' . (int) $tpl['id'])) ?>" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <?php if ((int) ($tpl['is_default'] ?? 0) !== 1): ?>
                                                <form method="post" action="<?= esc(site_url('gerencias/ecoe/tarifa-social/plantillas')) ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="set_default">
                                                    <input type="hidden" name="template_id" value="<?= (int) $tpl['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-success" type="submit" title="Marcar predeterminada">
                                                        <i class="bi bi-check2-square"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ((int) ($tpl['is_default'] ?? 0) !== 1): ?>
                                                <form method="post" action="<?= esc(site_url('gerencias/ecoe/tarifa-social/plantillas')) ?>" class="d-inline" onsubmit="return confirm('¿Eliminar esta plantilla?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="eliminar">
                                                    <input type="hidden" name="template_id" value="<?= (int) $tpl['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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
</div>

<script>
(function () {
    'use strict';

    const form = document.getElementById('tplForm');
    const actionInput = form ? form.querySelector('input[name="action"]') : null;
    const previewBtn = document.getElementById('btnPreviewDraft');

    if (!form || !actionInput || !previewBtn) {
        return;
    }

    previewBtn.addEventListener('click', function () {
        const previousAction = actionInput.value;
        const previousTarget = form.target;

        actionInput.value = 'preview';
        form.target = '_blank';
        form.submit();

        actionInput.value = previousAction;
        form.target = previousTarget;
    });
})();
</script>

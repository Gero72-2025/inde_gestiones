<?php
/**
 * Vista administrativa – Distribuidoras ECOE (Form)
 * Formulario para crear y editar distribuidoras.
 */
$record = (array) ($record ?? []);
$isEdit = !empty($record);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1">
            <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2"></i>
            <?= $isEdit ? 'Editar' : 'Crear' ?> Distribuidora
        </h1>
        <p class="text-secondary mb-0">Ingresa los datos de la distribuidora de energía eléctrica.</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc(site_url($isEdit ? 'gerencias/ecoe/distribuidoras/update/' . $record['id'] : 'gerencias/ecoe/distribuidoras/store')) ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <!-- Nombre -->
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold">Nombre de Distribuidora <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset(session()->getFlashdata('errors')['nombre']) ? 'is-invalid' : '' ?>"
                           name="nombre" maxlength="120" required
                           value="<?= esc($record['nombre'] ?? old('nombre')) ?>">
                    <?php if ($error = session()->getFlashdata('errors')['nombre'] ?? null): ?>
                        <div class="invalid-feedback d-block"><?= $error ?></div>
                    <?php endif; ?>
                    <small class="text-secondary">Ejemplo: EEGSA, ENERGUATE, DEORSA</small>
                </div>

                <!-- Estado -->
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                    <select name="status" class="form-select <?= isset(session()->getFlashdata('errors')['status']) ? 'is-invalid' : '' ?>" required>
                        <option value="1" <?= (int) ($record['status'] ?? old('status')) === 1 ? 'selected' : '' ?>>Activa</option>
                        <option value="0" <?= (int) ($record['status'] ?? old('status')) === 0 ? 'selected' : '' ?>>Inactiva</option>
                    </select>
                    <?php if ($error = session()->getFlashdata('errors')['status'] ?? null): ?>
                        <div class="invalid-feedback d-block"><?= $error ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botones -->
            <div class="row g-2 mt-4">
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="<?= esc(site_url('gerencias/ecoe/distribuidoras')) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i><?= $isEdit ? 'Actualizar' : 'Guardar' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

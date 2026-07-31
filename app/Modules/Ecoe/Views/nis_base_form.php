<?php
/**
 * Vista administrativa – Base NIS ECOE (Form)
 * Formulario para crear y editar registros.
 */
$record = (array) ($record ?? []);
$distribuidoras = (array) ($distribuidoras ?? []);
$isEdit = !empty($record);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1">
            <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2"></i>
            <?= $isEdit ? 'Editar' : 'Crear' ?> Registro NIS
        </h1>
        <p class="text-secondary mb-0">Ingresa los datos del usuario NIS.</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc(site_url($isEdit ? 'gerencias/ecoe/nis-base/update/' . $record['id'] : 'gerencias/ecoe/nis-base/store')) ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <!-- ID Usuario -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">ID Usuario <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset(session()->getFlashdata('errors')['id_usuario']) ? 'is-invalid' : '' ?>"
                           name="id_usuario" maxlength="50" required
                           value="<?= esc($record['id_usuario'] ?? old('id_usuario')) ?>">
                    <?php if ($error = session()->getFlashdata('errors')['id_usuario'] ?? null): ?>
                        <div class="invalid-feedback d-block"><?= $error ?></div>
                    <?php endif; ?>
                </div>

                <!-- Nombre Usuario -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nombre Usuario <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset(session()->getFlashdata('errors')['nombre_usuario']) ? 'is-invalid' : '' ?>"
                           name="nombre_usuario" maxlength="180" required
                           value="<?= esc($record['nombre_usuario'] ?? old('nombre_usuario')) ?>">
                    <?php if ($error = session()->getFlashdata('errors')['nombre_usuario'] ?? null): ?>
                        <div class="invalid-feedback d-block"><?= $error ?></div>
                    <?php endif; ?>
                </div>

                <!-- Departamento -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Departamento</label>
                    <input type="text" class="form-control" name="departamento" maxlength="100"
                           value="<?= esc($record['departamento'] ?? old('departamento')) ?>">
                </div>

                <!-- Municipio -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Municipio</label>
                    <input type="text" class="form-control" name="municipio" maxlength="100"
                           value="<?= esc($record['municipio'] ?? old('municipio')) ?>">
                </div>

                <!-- Aldea -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Aldea</label>
                    <input type="text" class="form-control" name="aldea" maxlength="100"
                           value="<?= esc($record['aldea'] ?? old('aldea')) ?>">
                </div>

                <!-- Dirección -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Dirección</label>
                    <input type="text" class="form-control" name="direccion" maxlength="255"
                           value="<?= esc($record['direccion'] ?? old('direccion')) ?>">
                </div>

                <!-- Actividad Económica -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Actividad Económica</label>
                    <input type="text" class="form-control" name="activ_economica" maxlength="100"
                           value="<?= esc($record['activ_economica'] ?? old('activ_economica')) ?>">
                </div>

                <!-- Revisión -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Revisión</label>
                    <input type="text" class="form-control" name="revision" maxlength="50"
                           value="<?= esc($record['revision'] ?? old('revision')) ?>">
                </div>

                <!-- Mes -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Mes</label>
                    <input type="text" class="form-control" name="mes" maxlength="20"
                           value="<?= esc($record['mes'] ?? old('mes')) ?>">
                </div>

                <!-- Consumo kWh -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Consumo (kWh)</label>
                    <input type="number" class="form-control <?= isset(session()->getFlashdata('errors')['consumo_kwh']) ? 'is-invalid' : '' ?>"
                           name="consumo_kwh" step="0.01" value="<?= (float) ($record['consumo_kwh'] ?? old('consumo_kwh')) ?>">
                    <?php if ($error = session()->getFlashdata('errors')['consumo_kwh'] ?? null): ?>
                        <div class="invalid-feedback d-block"><?= $error ?></div>
                    <?php endif; ?>
                </div>

                <!-- Distribuidora -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Distribuidora <span class="text-danger">*</span></label>
                    <select name="distribuidora_id" class="form-select <?= isset(session()->getFlashdata('errors')['distribuidora_id']) ? 'is-invalid' : '' ?>" required>
                        <option value="">-- Selecciona una distribuidora --</option>
                        <?php foreach ($distribuidoras as $dist): ?>
                            <option value="<?= (int) $dist['id'] ?>"
                                <?= (int) ($record['distribuidora_id'] ?? old('distribuidora_id')) === (int) $dist['id'] ? 'selected' : '' ?>>
                                <?= esc($dist['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($error = session()->getFlashdata('errors')['distribuidora_id'] ?? null): ?>
                        <div class="invalid-feedback d-block"><?= $error ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botones -->
            <div class="row g-2 mt-4">
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="<?= esc(site_url('gerencias/ecoe/nis-base')) ?>" class="btn btn-outline-secondary">
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

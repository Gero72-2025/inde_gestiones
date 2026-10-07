<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 mb-1">Estados de Mantenimiento</h2>
        <p class="text-secondary mb-0">Administra el nombre, la clave y el color institucional usados en el calendario.</p>
    </div>
    <a href="<?= esc(site_url('admin/etcee/cortes')) ?>" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Volver a mantenimientos</a>
</div>

<?php if ($message = session()->getFlashdata('success')): ?>
    <div class="alert alert-success" role="status"><?= esc($message) ?></div>
<?php endif; ?>
<?php if ($message = session()->getFlashdata('error')): ?>
    <div class="alert alert-danger" role="alert"><?= esc($message) ?></div>
<?php endif; ?>

<?php $editing = is_array($editing ?? null) ? $editing : []; ?>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <section class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="h6 mb-0"><?= $editing !== [] ? 'Editar estado' : 'Nuevo estado' ?></h3>
            </div>
            <div class="card-body">
                <form method="post" action="<?= esc(site_url('admin/etcee/estados-mantenimiento/save')) ?>" class="vstack gap-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                    <div>
                        <label class="form-label fw-semibold" for="estadoNombre">Nombre del estado</label>
                        <input class="form-control" id="estadoNombre" name="nombre" maxlength="100" required value="<?= esc((string) old('nombre', $editing['nombre'] ?? '')) ?>" placeholder="Ej. Mantenimiento Programado">
                    </div>
                    <div>
                        <label class="form-label fw-semibold" for="estadoClave">Clave (1 o 2 letras)</label>
                        <input class="form-control text-uppercase" id="estadoClave" name="clave" maxlength="2" pattern="[A-Za-z]{1,2}" required value="<?= esc((string) old('clave', $editing['clave'] ?? '')) ?>" placeholder="P" autocomplete="off">
                    </div>
                    <div>
                        <label class="form-label fw-semibold" for="estadoColor">Color institucional</label>
                        <div class="d-flex align-items-center gap-2">
                            <input class="form-control form-control-color" type="color" id="estadoColor" name="color" value="<?= esc((string) old('color', $editing['color'] ?? '#1A56DB')) ?>" title="Seleccionar color">
                            <span class="small text-secondary">Se aplica a la insignia y al evento del calendario.</span>
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="estadoActivo" name="activo" value="1" <?= old('activo', $editing['activo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="estadoActivo">Estado disponible para nuevos mantenimientos</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Guardar</button>
                        <?php if ($editing !== []): ?>
                            <a href="<?= esc(site_url('admin/etcee/estados-mantenimiento')) ?>" class="btn btn-outline-secondary">Cancelar edición</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-8">
        <section class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h3 class="h6 mb-0">Estados registrados</h3>
                <span class="badge text-bg-light border"><?= count($estados ?? []) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr><th>Estado</th><th>Clave</th><th>Color</th><th>Disponibilidad</th><th class="text-end">Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach (($estados ?? []) as $estado): ?>
                        <tr>
                            <td class="fw-semibold"><?= esc((string) ($estado['nombre'] ?? '')) ?></td>
                            <td><span class="badge" style="background-color:<?= esc((string) ($estado['color'] ?? '#1A56DB')) ?>;min-width:2.5rem"><?= esc((string) ($estado['clave'] ?? '')) ?></span></td>
                            <td><span class="d-inline-block rounded border me-1" style="width:1rem;height:1rem;vertical-align:middle;background-color:<?= esc((string) ($estado['color'] ?? '#1A56DB')) ?>"></span><code><?= esc((string) ($estado['color'] ?? '')) ?></code></td>
                            <td><span class="badge <?= ! empty($estado['activo']) ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= ! empty($estado['activo']) ? 'Activo' : 'Inactivo' ?></span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="<?= esc(site_url('admin/etcee/estados-mantenimiento?edit=' . (int) ($estado['id'] ?? 0))) ?>" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                <form method="post" action="<?= esc(site_url('admin/etcee/estados-mantenimiento/' . (int) ($estado['id'] ?? 0) . '/delete')) ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este estado?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (($estados ?? []) === []): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">Aún no hay estados registrados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?php
$records = (array) ($records ?? []);
$pager = $pager ?? null;
$distribuidoras = (array) ($distribuidoras ?? []);
$record = is_array($record ?? null) ? $record : null;
$filterDistributorId = (int) ($filterDistributorId ?? 0);
$months = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];
$yearOptions = array_unique(array_merge(
    range(max(2000, (int) date('Y') - 10), (int) date('Y') + 5),
    array_map(static fn (array $item): int => (int) $item['anio'], $records),
    $record !== null ? [(int) $record['anio']] : []
));
rsort($yearOptions);
$selectedYear = (int) old('anio', $record['anio'] ?? date('Y'));
$selectedMonth = (int) old('mes', $record['mes'] ?? date('n'));
$selectedDistributorId = (int) old('distribuidora_id', $record['distribuidora_id'] ?? 0);
$baseUrl = site_url('gerencias/ecoe/tarifa-social/tarifas');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-currency-exchange text-success me-2"></i>Tarifas mensuales ECOE</h1>
        <p class="text-secondary mb-0">Administra tarifas plenas y sociales por año y mes.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= esc(site_url('gerencias/ecoe/tarifa-social')) ?>">
        <i class="bi bi-arrow-left me-1"></i>Volver a Tarifa Social
    </a>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3"><?= $record !== null ? 'Editar tarifa' : 'Registrar tarifa' ?></h2>
                <form method="post" action="<?= esc($baseUrl) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="guardar">
                    <input type="hidden" name="tarifa_id" value="<?= (int) ($record['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="distribuidora_id">Distribuidora</label>
                        <select class="form-select" id="distribuidora_id" name="distribuidora_id" required>
                            <option value="">Selecciona una distribuidora</option>
                            <?php foreach ($distribuidoras as $distribuidora): ?>
                                <?php $distribuidoraId = (int) $distribuidora['id']; ?>
                                <option value="<?= $distribuidoraId ?>" <?= $selectedDistributorId === $distribuidoraId ? 'selected' : '' ?>>
                                    <?= esc((string) $distribuidora['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="anio">Año</label>
                        <select class="form-select" id="anio" name="anio" required>
                            <?php foreach ($yearOptions as $year): ?>
                                <option value="<?= (int) $year ?>" <?= $selectedYear === (int) $year ? 'selected' : '' ?>><?= (int) $year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="mes">Mes</label>
                        <select class="form-select" id="mes" name="mes" required>
                            <?php foreach ($months as $number => $name): ?>
                                <option value="<?= $number ?>" <?= $selectedMonth === $number ? 'selected' : '' ?>><?= esc($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="tarifa_plena">Tarifa Plena</label>
                        <input class="form-control" id="tarifa_plena" name="tarifa_plena" type="number" min="0" step="0.000001" required
                               value="<?= esc((string) old('tarifa_plena', $record['tarifa_plena'] ?? '')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="tarifa_social">Tarifa Social</label>
                        <input class="form-control" id="tarifa_social" name="tarifa_social" type="number" min="0" step="0.000001" required
                               value="<?= esc((string) old('tarifa_social', $record['tarifa_social'] ?? '')) ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-save me-1"></i><?= $record !== null ? 'Actualizar' : 'Guardar' ?>
                        </button>
                        <?php if ($record !== null): ?>
                            <a class="btn btn-outline-secondary" href="<?= esc($baseUrl) ?>">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <form method="get" action="<?= esc($baseUrl) ?>" class="row g-2 align-items-end mb-3">
            <div class="col-12 col-md-8">
                <label class="form-label fw-semibold" for="filter_distribuidora_id">Filtrar por distribuidora</label>
                <select class="form-select" id="filter_distribuidora_id" name="distribuidora_id">
                    <option value="0">Todas las distribuidoras</option>
                    <?php foreach ($distribuidoras as $distribuidora): ?>
                        <?php $distribuidoraId = (int) $distribuidora['id']; ?>
                        <option value="<?= $distribuidoraId ?>" <?= $filterDistributorId === $distribuidoraId ? 'selected' : '' ?>>
                            <?= esc((string) $distribuidora['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <button class="btn btn-outline-primary w-100" type="submit">
                    <i class="bi bi-funnel me-1"></i>Filtrar tarifas
                </button>
            </div>
        </form>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Distribuidora</th><th>Año</th><th>Mes</th><th>Tarifa Plena</th><th>Tarifa Social</th><th class="text-end">Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($records === []): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Aún no hay tarifas mensuales registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($records as $item): ?>
                                <?php $id = (int) $item['id']; ?>
                                <tr>
                                    <td><?= esc((string) ($item['distribuidora_nombre'] ?? 'Sin asignar')) ?></td>
                                    <td><?= (int) $item['anio'] ?></td>
                                    <td><?= esc($months[(int) $item['mes']] ?? '') ?></td>
                                    <td><?= esc(number_format((float) $item['tarifa_plena'], 6, '.', '')) ?></td>
                                    <td><?= esc(number_format((float) $item['tarifa_social'], 6, '.', '')) ?></td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary" title="Editar"
                                           href="<?= esc($baseUrl . '?edit=' . $id) ?>"><i class="bi bi-pencil-square"></i></a>
                                        <form method="post" action="<?= esc($baseUrl) ?>" class="d-inline"
                                              onsubmit="return confirm('¿Eliminar esta tarifa mensual?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="tarifa_id" value="<?= $id ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($pager !== null && $pager->getPageCount('tarifasMensuales') > 1): ?>
            <div class="d-flex justify-content-center mt-3">
                <?= $pager->links('tarifasMensuales', 'tarifas_bootstrap') ?>
            </div>
        <?php endif; ?>
    </div>
</div>
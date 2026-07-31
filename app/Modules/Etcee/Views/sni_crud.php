<?php
$rows = (array) ($rows ?? []);
$capas = (array) ($capas ?? []);
$editRow = is_array($editRow ?? null) ? $editRow : null;
$editCoords = $editRow ? (string) ($editRow['coordenadas'] ?? '') : '';
$editProps = $editRow ? (string) ($editRow['propiedades'] ?? '') : '';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">CRUD SNI ETCEE</h1>
        <p class="text-secondary mb-0">Gestiona rutas y puntos importados, o crea nuevos registros manuales.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= esc(site_url('admin/etcee/sni')) ?>">Volver a Wizard</a>
        <a class="btn btn-primary" href="<?= esc(site_url('admin/etcee/sni/crud')) ?>">Nuevo Registro</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3"><?= $editRow ? 'Editar registro' : 'Crear registro' ?></h2>
        <form action="<?= esc(site_url('admin/etcee/sni/crud')) ?>" method="post" class="row g-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editRow['id'] ?? 0) ?>">

            <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold">Nombre</label>
                <input class="form-control" name="nombre" value="<?= esc((string) ($editRow['nombre'] ?? '')) ?>" required>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold">Capa</label>
                <select class="form-select" name="categoria_id" required>
                    <option value="">Selecciona...</option>
                    <?php foreach ($capas as $capa): ?>
                        <option value="<?= (int) ($capa['id'] ?? 0) ?>" <?= ((int) ($editRow['categoria_id'] ?? 0) === (int) ($capa['id'] ?? 0)) ? 'selected' : '' ?>>
                            <?= esc((string) ($capa['nombre'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold">Tipo (si creas punto manual)</label>
                <select class="form-select" name="tipo_geojson">
                    <option value="">Usar coordenadas JSON</option>
                    <option value="point">Point</option>
                </select>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label">Latitud (Point)</label>
                <input class="form-control" name="latitud" placeholder="14.6349">
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <label class="form-label">Longitud (Point)</label>
                <input class="form-control" name="longitud" placeholder="-90.5069">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">GeoJSON (coordenadas)</label>
                <textarea class="form-control font-monospace" name="coordenadas_json" rows="4" placeholder='{"type":"Point","coordinates":[-90.5,14.6]}'><?= esc($editCoords) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Propiedades JSON</label>
                <textarea class="form-control font-monospace" name="propiedades_json" rows="3" placeholder='{"nivel_kv":69,"grupo_kml":"LINEAS SISTEMA CENTRAL"}'><?= esc($editProps) ?></textarea>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <?php if ($editRow): ?>
                    <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/etcee/sni/crud')) ?>">Cancelar edicion</a>
                <?php endif; ?>
                <button class="btn btn-success" type="submit">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Registros cargados (ultimos 400)</h2>
        <span class="badge text-bg-primary"><?= count($rows) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Capa</th>
                    <th>Tipo</th>
                    <th>Fecha registro</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-4">No hay registros SNI.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $geo = json_decode((string) ($row['coordenadas'] ?? '{}'), true);
                        $geoType = is_array($geo) ? (string) ($geo['type'] ?? 'N/D') : 'N/D';
                    ?>
                    <tr>
                        <td><?= (int) ($row['id'] ?? 0) ?></td>
                        <td><?= esc((string) ($row['nombre'] ?? '')) ?></td>
                        <td>
                            <span class="badge" style="background:<?= esc((string) ($row['color_default'] ?? '#6c757d')) ?>;">
                                <?= esc((string) ($row['capa_nombre'] ?? 'N/D')) ?>
                            </span>
                        </td>
                        <td><?= esc($geoType) ?></td>
                        <td><?= esc((string) ($row['fecha_registro'] ?? '')) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= esc(site_url('admin/etcee/sni/crud?edit=' . (int) ($row['id'] ?? 0))) ?>">Editar</a>
                            <form action="<?= esc(site_url('admin/etcee/sni/crud')) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminar registro SNI?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

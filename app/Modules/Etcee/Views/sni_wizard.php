<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h1 class="h4 mb-1">Asistente SNI ETCEE</h1>
                <p class="text-secondary mb-0">Carga archivos KMZ, previsualiza registros y ejecuta insercion masiva controlada.</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-primary" href="<?= esc(site_url('admin/etcee/sni')) ?>">
                    <i class="bi bi-list-ul me-1"></i>Ir a SNI
                </a>
                <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/etcee/sni/capas')) ?>">
                    <i class="bi bi-palette me-1"></i>Gestionar Simbologias
                </a>
                <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/etcee/sni/lineas-sistema')) ?>">
                    <i class="bi bi-diagram-2 me-1"></i>Lineas de Sistema
                </a>
                <a class="btn btn-outline-primary" href="<?= esc(site_url('gerencias/etcee/sni/wizard')) ?>">
                    <i class="bi bi-diagram-3 me-1"></i>Ruta gerencia/etcee
                </a>
            </div>
        </div>

        <div class="row g-3 align-items-end mb-2">
            <div class="col-12 col-xl-7">
                <form action="<?= esc(current_url()) ?>" method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_kmz">
                    <div class="col-12 col-lg-8">
                        <label for="kmzFileSave" class="form-label fw-semibold">Guardar nuevo archivo KMZ en servidor</label>
                        <input id="kmzFileSave" name="kmz_file" type="file" class="form-control" accept=".kmz" required>
                    </div>
                    <div class="col-12 col-lg-4 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-folder-symlink me-1"></i> Guardar KMZ
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-12 col-xl-5">
                <form action="<?= esc(current_url()) ?>" method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="preview">
                    <div class="col-12 col-lg-8">
                        <label for="kmzFilePreview" class="form-label fw-semibold">Previsualizar KMZ local (sin guardar)</label>
                        <input id="kmzFilePreview" name="kmz_file" type="file" class="form-control" accept=".kmz" required>
                    </div>
                    <div class="col-12 col-lg-4 d-grid">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i> Previsualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php $storedKmzFiles = (array) ($storedKmzFiles ?? []); ?>
        <?php $activeStoredName = basename((string) ($preview['stored_file_path'] ?? '')); ?>

        <div class="border rounded p-3 bg-light-subtle">
            <h2 class="h6 mb-3">KMZ guardados anteriormente</h2>

            <?php if ($storedKmzFiles === []): ?>
                <p class="text-secondary mb-0">No hay archivos KMZ guardados en servidor para esta gerencia.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th style="width: 140px;">Tamano</th>
                                <th style="width: 180px;">Ultima modificacion</th>
                                <th style="width: 220px;" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($storedKmzFiles as $stored): ?>
                                <?php
                                    $token = (string) ($stored['token'] ?? '');
                                    $nombre = (string) ($stored['nombre'] ?? $token);
                                    $tamano = (int) ($stored['tamano_bytes'] ?? 0);
                                    $fechaMod = (string) ($stored['fecha_modificacion'] ?? '');
                                    $isActive = $activeStoredName !== '' && hash_equals($activeStoredName, $token);
                                ?>
                                <tr class="<?= $isActive ? 'table-success' : '' ?>">
                                    <td>
                                        <span class="fw-semibold"><?= esc($nombre) ?></span>
                                        <?php if ($isActive): ?>
                                            <span class="badge text-bg-success ms-1">En previsualizacion</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc(number_format($tamano / 1024, 2)) ?> KB</td>
                                    <td class="small"><?= esc($fechaMod) ?></td>
                                    <td>
                                        <div class="d-flex justify-content-end gap-2">
                                            <form action="<?= esc(current_url()) ?>" method="post" class="m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="preview">
                                                <input type="hidden" name="stored_kmz" value="<?= esc($token) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye me-1"></i>Usar
                                                </button>
                                            </form>
                                            <form action="<?= esc(current_url()) ?>" method="post" class="m-0" onsubmit="return confirm('Deseas eliminar este archivo KMZ del servidor?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_kmz">
                                                <input type="hidden" name="stored_kmz" value="<?= esc($token) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash me-1"></i>Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <form action="<?= esc(current_url()) ?>" method="post" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" name="action" value="reset" class="btn btn-outline-secondary" formnovalidate>
                    Limpiar previsualizacion
                </button>
            </form>
        </div>
    </div>
</div>

<?php $rows = (array) ($preview['rows'] ?? []); ?>
<?php $stats = (array) ($previewStats ?? []); ?>
<?php $groups = (array) ($stats['groups'] ?? []); ?>
<?php $featureTypes = (array) ($stats['feature_types'] ?? []); ?>
<?php $voltages = (array) ($stats['voltages'] ?? []); ?>
<?php $capas = (array) ($capas ?? []); ?>
<?php $lineasSistema = (array) ($lineasSistema ?? []); ?>

<?php if ($rows !== []): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h5 mb-1">Previsualizacion de Registros</h2>
                <small class="text-secondary d-block">
                    Archivo: <strong><?= esc((string) ($preview['source_file_name'] ?? '')) ?></strong> |
                    Registros detectados: <strong><?= (int) ($previewCount ?? 0) ?></strong>
                </small>
            </div>
        </div>
        <div class="card-body border-bottom bg-light-subtle">
            <form action="<?= esc(current_url()) ?>" method="post" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="import">
                <div class="col-12 col-xl-5">
                    <label class="form-label fw-semibold">Agrupaciones detectadas en el KMZ</label>
                    <div class="border rounded p-2 bg-white" style="max-height: 220px; overflow:auto;">
                        <?php foreach ($groups as $group): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="selected_groups[]" value="<?= esc((string) ($group['slug'] ?? '')) ?>" id="grp<?= esc((string) ($group['slug'] ?? '')) ?>">
                                <label class="form-check-label small" for="grp<?= esc((string) ($group['slug'] ?? '')) ?>">
                                    <strong><?= esc((string) ($group['path'] ?? 'Sin grupo')) ?></strong>
                                    <span class="text-secondary d-block">
                                        Total: <?= (int) ($group['total'] ?? 0) ?> |
                                        Rutas: <?= (int) ($group['ruta'] ?? 0) ?> |
                                        Puntos: <?= (int) ($group['punto'] ?? 0) ?>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-secondary">Si no marcas grupos, se consideran todos.</small>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label fw-semibold">Tipo de elemento</label>
                    <div class="border rounded p-2 bg-white">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="selected_types[]" value="ruta" id="tpRuta">
                            <label class="form-check-label" for="tpRuta">Rutas (<?= (int) ($featureTypes['ruta'] ?? 0) ?>)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="selected_types[]" value="punto" id="tpPunto">
                            <label class="form-check-label" for="tpPunto">Puntos (<?= (int) ($featureTypes['punto'] ?? 0) ?>)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="selected_types[]" value="area" id="tpArea">
                            <label class="form-check-label" for="tpArea">Areas (<?= (int) ($featureTypes['area'] ?? 0) ?>)</label>
                        </div>
                    </div>
                    <small class="text-secondary">Si no marcas tipos, se consideran todos.</small>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label fw-semibold">Nivel kV</label>
                    <div class="border rounded p-2 bg-white">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="selected_kv[]" value="69" id="kv69">
                            <label class="form-check-label" for="kv69">69kV (<?= (int) ($voltages['69'] ?? 0) ?>)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="selected_kv[]" value="138" id="kv138">
                            <label class="form-check-label" for="kv138">138kV (<?= (int) ($voltages['138'] ?? 0) ?>)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="selected_kv[]" value="230" id="kv230">
                            <label class="form-check-label" for="kv230">230kV (<?= (int) ($voltages['230'] ?? 0) ?>)</label>
                        </div>
                    </div>
                    <small class="text-secondary">Si no marcas kV, se consideran todos.</small>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label fw-semibold">Nombre para importacion</label>
                    <input type="text" name="import_nombre" class="form-control" maxlength="220" placeholder="Opcional">
                    <small class="text-secondary">Si defines nombre y hay multiples registros, se numeran automaticamente.</small>
                </div>
                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label fw-semibold">Capa destino</label>
                    <select name="import_categoria_id" class="form-select">
                        <option value="">Automatico por deteccion</option>
                        <?php foreach ($capas as $capa): ?>
                            <option value="<?= (int) ($capa['id'] ?? 0) ?>">
                                <?= esc((string) ($capa['nombre'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-secondary">Opcional: fuerza todos los seleccionados a una capa.</small>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label fw-semibold">Linea de Sistema</label>
                    <select name="import_linea_sistema_id" class="form-select">
                        <option value="">Sin asignar</option>
                        <?php foreach ($lineasSistema as $lineaSistema): ?>
                            <option value="<?= (int) ($lineaSistema['id'] ?? 0) ?>">
                                <?= esc((string) ($lineaSistema['nombre'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-secondary">Opcional: asigna todos los seleccionados a una linea de sistema.</small>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-cloud-upload me-1"></i> Importar Seleccion
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 72px;">#</th>
                            <th>Agrupacion</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Capa</th>
                            <th>Tipo GeoJSON</th>
                            <th>Voltaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <?php
                                $categoria = (array) ($row['categoria'] ?? []);
                                $geojson = (array) ($row['geojson'] ?? []);
                                $propiedades = (array) ($row['propiedades'] ?? []);
                            ?>
                            <tr>
                                <td><?= (int) $index + 1 ?></td>
                                <td class="small"><?= esc((string) ($row['grupo_path'] ?? 'Sin grupo')) ?></td>
                                <td><?= esc((string) ($row['nombre'] ?? '')) ?></td>
                                <td><span class="badge text-bg-secondary"><?= esc((string) ($row['feature_type'] ?? 'otro')) ?></span></td>
                                <td>
                                    <span class="badge" style="background: <?= esc((string) ($categoria['color_default'] ?? '#6c757d')) ?>;">
                                        <?= esc((string) ($categoria['nombre'] ?? 'N/D')) ?>
                                    </span>
                                </td>
                                <td><?= esc((string) ($geojson['type'] ?? 'N/D')) ?></td>
                                <td><?= esc((string) ($propiedades['nivel_kv'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$rows = (array) ($rows ?? []);
$capas = (array) ($capas ?? []);
$lineasSistema = (array) ($lineasSistema ?? []);
$activeFilters = (array) ($activeFilters ?? []);
$lineGroups = (array) ($lineGroups ?? []);
$linePagination = (array) ($linePagination ?? []);
$currentPage = (int) ($linePagination['current_page'] ?? 1);
$totalPages = (int) ($linePagination['total_pages'] ?? 1);
$totalLines = (int) ($linePagination['total'] ?? count($lineGroups));
$activeLineaSistemaId = (int) ($activeFilters['linea_sistema_id'] ?? 0);
$activeCapaId = (int) ($activeFilters['categoria_id'] ?? 0);
$activeQuery = (string) ($activeFilters['q'] ?? '');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">SNI ETCEE</h1>
        <p class="text-secondary mb-0">Gestiona rutas y puntos de la red eléctrica nacional.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= esc(site_url('admin/etcee/sni/wizard')) ?>">
            <i class="bi bi-upload me-1"></i>Asistente KMZ
        </a>
        <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/etcee/sni/capas')) ?>">
            <i class="bi bi-palette me-1"></i>Simbologias
        </a>
        <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/etcee/sni/lineas-sistema')) ?>">
            <i class="bi bi-diagram-2 me-1"></i>Lineas de Sistema
        </a>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#sniEditModal" onclick="resetSniForm()">
            <i class="bi bi-plus-circle me-1"></i>Nuevo Registro
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="<?= esc(site_url('admin/etcee/sni')) ?>" class="row g-2 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold mb-1">Linea de Sistema</label>
                <select name="linea_sistema_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($lineasSistema as $lineaSistema): ?>
                        <?php $lsId = (int) ($lineaSistema['id'] ?? 0); ?>
                        <option value="<?= $lsId ?>" <?= $lsId === $activeLineaSistemaId ? 'selected' : '' ?>>
                            <?= esc((string) ($lineaSistema['nombre'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label fw-semibold mb-1">Simbologia</label>
                <select name="categoria_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($capas as $capa): ?>
                        <?php $capaId = (int) ($capa['id'] ?? 0); ?>
                        <option value="<?= $capaId ?>" <?= $capaId === $activeCapaId ? 'selected' : '' ?>>
                            <?= esc((string) ($capa['nombre'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label fw-semibold mb-1">Buscar</label>
                <input type="text" class="form-control" name="q" value="<?= esc($activeQuery) ?>" maxlength="120" placeholder="Nombre, grupo o texto en propiedades">
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/etcee/sni')) ?>" title="Limpiar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h6 mb-0">Lineas electrificadas (acordeon)</h2>
            <small class="text-secondary d-block">Paginado en bloques de 10 lineas. Encabezado LineString e hijos Point.</small>
        </div>
        <span class="badge text-bg-primary"><?= $totalLines ?></span>
    </div>
    <div class="card-body">
        <?php if ($lineGroups === []): ?>
            <div class="text-center text-secondary py-4">No hay registros SNI importados. Usa el asistente KMZ para cargar datos.</div>
        <?php else: ?>
            <div class="accordion" id="sniLinesAccordion">
                <?php foreach ($lineGroups as $index => $group): ?>
                    <?php
                        $accordionId = 'lineAccordion' . $index;
                        $headingId = 'lineHeading' . $index;
                        $lineId = 'lineCollapse' . $index;
                        $line = is_array($group['line'] ?? null) ? $group['line'] : null;
                        $points = (array) ($group['points'] ?? []);
                        $lineSlug = (string) ($group['linea_slug'] ?? '');
                        $lineName = (string) ($group['linea_nombre'] ?? 'Linea sin nombre');
                    ?>
                    <div class="accordion-item mb-2 border rounded-3" id="<?= esc($accordionId) ?>">
                        <h2 class="accordion-header" id="<?= esc($headingId) ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= esc($lineId) ?>" aria-expanded="false" aria-controls="<?= esc($lineId) ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center pe-3">
                                    <div>
                                        <div class="fw-semibold"><?= esc($lineName) ?></div>
                                        <small class="text-secondary"><?= esc((string) ($group['sistema_linea'] ?? 'Sin sistema')) ?> > <?= esc((string) ($group['subgrupo_linea'] ?? 'Sin subgrupo')) ?></small>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="badge text-bg-primary">LineString: <?= $line ? '1' : '0' ?></span>
                                        <span class="badge text-bg-secondary">Point: <?= count($points) ?></span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="<?= esc($lineId) ?>" class="accordion-collapse collapse" aria-labelledby="<?= esc($headingId) ?>" data-bs-parent="#sniLinesAccordion">
                            <div class="accordion-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="small text-secondary">Linea electrificada agrupada con sus puntos asociados.</div>
                                    <div class="d-flex gap-2">
                                        <?php if ($line): ?>
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#sniEditModal" onclick="loadSniForEdit(<?= (int) ($line['id'] ?? 0) ?>)">
                                                <i class="bi bi-pencil"></i>Editar LineString
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteSniConfirm(<?= (int) ($line['id'] ?? 0) ?>)">
                                                <i class="bi bi-trash"></i>Eliminar LineString
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($lineSlug !== ''): ?>
                                            <button class="btn btn-sm btn-danger" type="button" onclick='deleteLineConfirm(<?= json_encode($lineSlug) ?>, <?= json_encode($lineName) ?>)'>
                                                <i class="bi bi-x-octagon"></i>Eliminar Linea Completa
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 70px;">ID</th>
                                                <th>Nombre Point</th>
                                                <th style="width: 130px;">Capa</th>
                                                <th style="width: 160px;">Fecha Registro</th>
                                                <th style="width: 170px;" class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($points === []): ?>
                                                <tr><td colspan="5" class="text-center text-secondary py-3">Sin puntos asociados en esta linea.</td></tr>
                                            <?php endif; ?>
                                            <?php foreach ($points as $point): ?>
                                                <tr>
                                                    <td><strong><?= (int) ($point['id'] ?? 0) ?></strong></td>
                                                    <td><?= esc((string) ($point['nombre'] ?? '')) ?></td>
                                                    <td>
                                                        <span class="badge text-white" style="background:<?= esc((string) ($point['color_default'] ?? '#6c757d')) ?>;">
                                                            <?= esc(substr((string) ($point['capa_nombre'] ?? 'N/D'), 0, 20)) ?>
                                                        </span>
                                                    </td>
                                                    <td class="small"><?= esc((string) ($point['fecha_registro'] ?? '')) ?></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#sniEditModal" onclick="loadSniForEdit(<?= (int) ($point['id'] ?? 0) ?>)">
                                                            <i class="bi bi-pencil"></i>Editar
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteSniConfirm(<?= (int) ($point['id'] ?? 0) ?>)">
                                                            <i class="bi bi-trash"></i>Eliminar
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <?php
                    $queryBase = [];
                    if ($activeLineaSistemaId > 0) {
                        $queryBase['linea_sistema_id'] = $activeLineaSistemaId;
                    }
                    if ($activeCapaId > 0) {
                        $queryBase['categoria_id'] = $activeCapaId;
                    }
                    if ($activeQuery !== '') {
                        $queryBase['q'] = $activeQuery;
                    }

                    $buildPageUrl = static function (int $page) use ($queryBase): string {
                        $params = $queryBase;
                        $params['page'] = $page;

                        return site_url('admin/etcee/sni?' . http_build_query($params));
                    };
                ?>
                <nav class="mt-3" aria-label="Paginacion de lineas SNI">
                    <ul class="pagination pagination-sm mb-0 justify-content-end">
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc($buildPageUrl(max($currentPage - 1, 1))) ?>">Anterior</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc($buildPageUrl($p)) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc($buildPageUrl(min($currentPage + 1, $totalPages))) ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal CRUD SNI -->
<div class="modal fade" id="sniEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="sniModalTitle">Nuevo Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="sniForm" class="needs-validation" novalidate>
                <input type="hidden" id="sniId" name="id" value="">
                <input type="hidden" name="action" value="save">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input class="form-control" id="sniNombre" name="nombre" placeholder="Nombre del elemento" required>
                            <div class="invalid-feedback">El nombre es requerido.</div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">Capa <span class="text-danger">*</span></label>
                            <select class="form-select" id="sniCapaId" name="categoria_id" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($capas as $capa): ?>
                                    <option value="<?= (int) ($capa['id'] ?? 0) ?>">
                                        <?= esc((string) ($capa['nombre'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Debes seleccionar una capa.</div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">Linea de Sistema</label>
                            <select class="form-select" id="sniLineaSistemaId" name="linea_sistema_id">
                                <option value="">Sin asignar</option>
                                <?php foreach ($lineasSistema as $lineaSistema): ?>
                                    <option value="<?= (int) ($lineaSistema['id'] ?? 0) ?>">
                                        <?= esc((string) ($lineaSistema['nombre'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-secondary">Opcional: relaciona esta linea electrificada con una linea de sistema.</small>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Latitud (para Point)</label>
                            <input class="form-control" id="sniLatitud" name="latitud" placeholder="14.6349" step="0.0001">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Longitud (para Point)</label>
                            <input class="form-control" id="sniLongitud" name="longitud" placeholder="-90.5069" step="0.0001">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" id="sniTipo" name="tipo_geojson">
                                <option value="">JSON personalizado</option>
                                <option value="point">Point</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">GeoJSON (coordenadas)</label>
                            <textarea class="form-control font-monospace" id="sniCoordenadas" name="coordenadas_json" rows="3" placeholder='{"type":"Point","coordinates":[-90.5,14.6]}' spellcheck="false"></textarea>
                            <small class="text-secondary">Formato: {"type":"Point|LineString|Polygon","coordinates":[...] }</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Propiedades JSON</label>
                            <textarea class="form-control font-monospace" id="sniPropiedades" name="propiedades_json" rows="2" placeholder='{"nivel_kv":69,"grupo_kml":"LINEAS"}' spellcheck="false"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="sniSaveBtn">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const sniConfig = {
        saveUrl: <?= json_encode(site_url('admin/etcee/sni/save')) ?>,
        deleteUrl: <?= json_encode(site_url('admin/etcee/sni/delete')) ?>,
        csrfName: <?= json_encode(csrf_token()) ?>,
        csrfValue: <?= json_encode(csrf_hash()) ?>,
    };

    function resetSniForm() {
        document.getElementById('sniForm').reset();
        document.getElementById('sniId').value = '';
        document.getElementById('sniModalTitle').textContent = 'Nuevo Registro';
        document.getElementById('sniForm').classList.remove('was-validated');
    }

    function loadSniForEdit(id) {
        if (id <= 0) return;

        fetch(<?= json_encode(site_url('admin/etcee/sni/get')) ?> + '/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.ok) {
                notify('Error cargando registro', 'error');
                return;
            }

            const row = data.data || {};
            document.getElementById('sniId').value = row.id || '';
            document.getElementById('sniNombre').value = row.nombre || '';
            document.getElementById('sniCapaId').value = row.categoria_id || '';
            document.getElementById('sniLineaSistemaId').value = row.linea_sistema_id || '';
            document.getElementById('sniCoordenadas').value = row.coordenadas || '';
            document.getElementById('sniPropiedades').value = row.propiedades || '';
            document.getElementById('sniModalTitle').textContent = 'Editar: ' + (row.nombre || '');
            document.getElementById('sniForm').classList.remove('was-validated');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('sniEditModal')).show();
        })
        .catch(() => notify('Error cargando registro', 'error'));
    }

    function deleteSniConfirm(id) {
        if (!confirm('¿Eliminar este registro SNI?')) return;

        const form = new FormData();
        form.append('action', 'delete');
        form.append('id', id);
        form.append(sniConfig.csrfName, sniConfig.csrfValue);

        fetch(<?= json_encode(site_url('admin/etcee/sni')) ?>, {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.ok) {
                notify('Registro eliminado', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                notify(data?.message || 'Error al eliminar', 'error');
            }
        })
        .catch(() => notify('Error al eliminar', 'error'));
    }

    function deleteLineConfirm(lineSlug, lineName) {
        if (!lineSlug) {
            notify('Linea invalida', 'error');
            return;
        }

        if (!confirm('¿Eliminar toda la linea "' + (lineName || lineSlug) + '"?\nEsto eliminara rutas y waypoints asociados.')) return;

        const form = new FormData();
        form.append('action', 'delete_line');
        form.append('linea_slug', lineSlug);
        form.append(sniConfig.csrfName, sniConfig.csrfValue);

        fetch(<?= json_encode(site_url('admin/etcee/sni')) ?>, {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.ok) {
                notify(data.message || 'Linea eliminada', 'success');
                setTimeout(() => location.reload(), 900);
            } else {
                notify(data?.message || 'Error al eliminar la linea', 'error');
            }
        })
        .catch(() => notify('Error al eliminar la linea', 'error'));
    }

    document.getElementById('sniForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        const form = new FormData(this);
        form.append(sniConfig.csrfName, sniConfig.csrfValue);

        try {
            const response = await fetch(<?= json_encode(site_url('admin/etcee/sni')) ?>, {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data && data.ok) {
                notify(data.message || 'Guardado correctamente', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                notify(data?.message || 'Error al guardar', 'error');
            }
        } catch (error) {
            notify('Error: ' + error.message, 'error');
        }
    });
</script>

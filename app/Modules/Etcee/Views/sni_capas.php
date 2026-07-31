<?php
$rows = (array) ($rows ?? []);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Simbologias SNI</h1>
        <p class="text-secondary mb-0">Administra las capas y simbologias que se muestran en el mapa del SNI.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= esc(site_url('admin/etcee/sni')) ?>">
            <i class="bi bi-diagram-3 me-1"></i>Volver a Geometrias
        </a>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#capaModal" onclick="resetCapaForm()">
            <i class="bi bi-plus-circle me-1"></i>Nueva Simbologia
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h6 mb-0">Capas registradas</h2>
            <small class="text-secondary d-block">Listado completo de simbologias del SNI</small>
        </div>
        <span class="badge text-bg-primary"><?= count($rows) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Nombre</th>
                    <th style="width: 220px;">Slug</th>
                    <th style="width: 120px;">Color</th>
                    <th style="width: 120px;">Estado</th>
                    <th>Icono</th>
                    <th style="width: 170px;" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">No existen simbologias registradas.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?= (int) ($row['id'] ?? 0) ?></strong></td>
                        <td><?= esc((string) ($row['nombre'] ?? '')) ?></td>
                        <td><code><?= esc((string) ($row['slug'] ?? '')) ?></code></td>
                        <td>
                            <?php $color = (string) ($row['color_default'] ?? '#6c757d'); ?>
                            <span class="d-inline-flex align-items-center gap-2">
                                <span class="rounded-circle border" style="width:16px;height:16px;background:<?= esc($color) ?>;"></span>
                                <span><?= esc($color) ?></span>
                            </span>
                        </td>
                        <td>
                            <?php $estado = (string) ($row['estado'] ?? 'inactivo'); ?>
                            <span class="badge <?= $estado === 'activo' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                <?= esc(ucfirst($estado)) ?>
                            </span>
                        </td>
                        <td>
                            <?php $iconPath = (string) ($row['icono_path'] ?? ''); ?>
                            <?php if ($iconPath !== ''): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= esc(base_url($iconPath)) ?>" alt="" width="24" height="24" style="object-fit:contain;">
                                    <small class="text-secondary"><?= esc($iconPath) ?></small>
                                </div>
                            <?php else: ?>
                                <span class="text-secondary small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#capaModal" onclick="loadCapaForEdit(<?= (int) ($row['id'] ?? 0) ?>)">
                                <i class="bi bi-pencil"></i>Editar
                            </button>
                            <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteCapaConfirm(<?= (int) ($row['id'] ?? 0) ?>)">
                                <i class="bi bi-trash"></i>Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="capaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="capaModalTitle">Nueva Simbologia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="capaForm" class="needs-validation" novalidate>
                <input type="hidden" id="capaId" name="id" value="">
                <input type="hidden" name="action" value="save">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input class="form-control" id="capaNombre" name="nombre" required maxlength="180" placeholder="Ej. Linea 400kV">
                            <div class="invalid-feedback">El nombre es requerido.</div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold">Slug</label>
                            <input class="form-control" id="capaSlug" name="slug" maxlength="180" placeholder="Ej. sni-linea-400kv">
                            <small class="text-secondary">Si lo dejas vacio se genera desde el nombre.</small>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Color <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text p-1" style="cursor:pointer;" title="Abrir paleta de colores">
                                    <input type="color" id="capaColorPicker" value="#1f6feb"
                                        style="width:34px;height:34px;padding:2px;border:0;background:none;cursor:pointer;"
                                        title="Seleccionar color">
                                </span>
                                <input type="text" class="form-control font-monospace" id="capaColor" name="color_default"
                                    value="#1f6feb" pattern="^#[0-9a-fA-F]{6}$" required maxlength="7" placeholder="#1f6feb">
                            </div>
                            <div class="invalid-feedback">Usa formato #RRGGBB.</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" id="capaEstado" name="estado">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold d-flex align-items-center gap-2">
                                Icono
                                <span id="capaIconPreview" class="border rounded p-1" style="width:34px;height:34px;display:none;flex-shrink:0;">
                                    <img id="capaIconPreviewImg" src="" alt="" style="width:24px;height:24px;object-fit:contain;">
                                </span>
                            </label>
                            <div class="input-group">
                                <input class="form-control" id="capaIcono" name="icono_path" maxlength="255" placeholder="assets/img/sni/icono.svg">
                                <button type="button" class="btn btn-outline-secondary" id="btnToggleIconPicker" onclick="toggleIconPicker()">
                                    <i class="bi bi-image"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="clearIconoField()" title="Quitar icono">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <small class="text-secondary">Ruta desde raiz publica, ej: assets/img/sni/subestacion.svg</small>
                            <div id="iconPickerPanel" class="border rounded mt-2 p-2 bg-light" style="display:none;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold small"><i class="bi bi-images me-1"></i>Iconos disponibles</span>
                                    <button type="button" class="btn btn-sm btn-close" onclick="toggleIconPicker()"></button>
                                </div>
                                <div id="iconPickerGrid" class="d-flex flex-wrap gap-2">
                                    <div class="text-secondary small w-100 text-center py-3">
                                        <span class="spinner-border spinner-border-sm me-1"></span> Cargando iconos...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.icon-picker-item {
    background: #fff;
    border: 2px solid #dee2e6 !important;
    border-radius: 6px;
    cursor: pointer;
    padding: 6px 8px;
    text-align: center;
    transition: border-color .15s, background .15s;
    min-width: 68px;
}
.icon-picker-item:hover {
    border-color: #86b7fe !important;
    background: #f0f5ff;
}
.icon-picker-item.selected {
    border-color: #0d6efd !important;
    background: #e8f0fe;
}
.icon-picker-item img { display: block; margin: 0 auto 2px; }
.icon-picker-item span { font-size: 10px; color: #555; display: block; word-break: break-all; }
</style>

<script>
    const capaConfig = {
        baseUrl: <?= json_encode(site_url('admin/etcee/sni/capas')) ?>,
        getUrl: <?= json_encode(site_url('admin/etcee/sni/capas/get')) ?>,
        iconsUrl: <?= json_encode(site_url('admin/etcee/sni/capas/icons')) ?>,
        csrfName: <?= json_encode(csrf_token()) ?>,
        csrfValue: <?= json_encode(csrf_hash()) ?>,
    };

    let iconPickerLoaded = false;
    let iconPickerOpen = false;

    // ============== NOTIFY ==============

    function capaNotify(message, type) {
        if (typeof notify === 'function') { notify(message, type); return; }
        alert(message);
    }

    // ============== COLOR PICKER SYNC ==============

    function initColorSync() {
        const picker = document.getElementById('capaColorPicker');
        const text = document.getElementById('capaColor');
        if (!picker || !text) return;

        picker.addEventListener('input', () => {
            text.value = picker.value;
        });

        text.addEventListener('input', () => {
            const v = text.value.trim();
            if (/^#[0-9a-fA-F]{6}$/.test(v)) {
                picker.value = v;
            }
        });
    }

    function syncColorPicker(hexValue) {
        const picker = document.getElementById('capaColorPicker');
        const text = document.getElementById('capaColor');
        const v = /^#[0-9a-fA-F]{6}$/i.test(hexValue) ? hexValue : '#1f6feb';
        if (picker) picker.value = v;
        if (text) text.value = v;
    }

    // ============== ICON PICKER ==============

    function updateIconPreview(url) {
        const wrap = document.getElementById('capaIconPreview');
        const img = document.getElementById('capaIconPreviewImg');
        if (!wrap || !img) return;
        if (url && url.trim() !== '') {
            img.src = url;
            wrap.style.display = 'inline-flex';
        } else {
            img.src = '';
            wrap.style.display = 'none';
        }
    }

    function clearIconoField() {
        document.getElementById('capaIcono').value = '';
        updateIconPreview('');
        document.querySelectorAll('.icon-picker-item').forEach(b => b.classList.remove('selected'));
    }

    function toggleIconPicker() {
        const panel = document.getElementById('iconPickerPanel');
        if (!panel) return;
        iconPickerOpen = !iconPickerOpen;
        panel.style.display = iconPickerOpen ? 'block' : 'none';
        if (iconPickerOpen && !iconPickerLoaded) {
            loadIconGrid();
        }
    }

    async function loadIconGrid() {
        const grid = document.getElementById('iconPickerGrid');
        if (!grid) return;

        try {
            const resp = await fetch(capaConfig.iconsUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const data = await resp.json();
            const icons = Array.isArray(data.icons) ? data.icons : [];
            iconPickerLoaded = true;
            renderIconGrid(icons);
        } catch (err) {
            grid.innerHTML = '<p class="text-danger small text-center w-100 py-2">Error al cargar iconos: ' + err.message + '</p>';
        }
    }

    function renderIconGrid(icons) {
        const grid = document.getElementById('iconPickerGrid');
        const currentRuta = (document.getElementById('capaIcono').value || '').trim();

        if (icons.length === 0) {
            grid.innerHTML = '<p class="text-secondary small text-center w-100 py-2">No hay iconos en el servidor. Sube archivos a <code>public/assets/img/sni/</code></p>';
            return;
        }

        grid.innerHTML = '';
        icons.forEach(icon => {
            const ruta = String(icon.ruta || '');
            const url = String(icon.url || '');
            const nombre = String(icon.nombre || ruta);
            const isSelected = currentRuta !== '' && currentRuta === ruta;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'icon-picker-item' + (isSelected ? ' selected' : '');
            btn.title = nombre;

            const img = document.createElement('img');
            img.src = url;
            img.alt = nombre;
            img.width = 32;
            img.height = 32;
            img.style.objectFit = 'contain';

            const lbl = document.createElement('span');
            // Show only filename without extension for brevity
            lbl.textContent = nombre.replace(/\.[^.]+$/, '');

            btn.appendChild(img);
            btn.appendChild(lbl);

            btn.addEventListener('click', () => {
                document.getElementById('capaIcono').value = ruta;
                updateIconPreview(url);
                document.querySelectorAll('.icon-picker-item').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
            });

            grid.appendChild(btn);
        });
    }

    // ============== RESET FORM ==============

    function resetCapaForm() {
        const form = document.getElementById('capaForm');
        form.reset();
        document.getElementById('capaId').value = '';
        syncColorPicker('#1f6feb');
        document.getElementById('capaEstado').value = 'activo';
        document.getElementById('capaIcono').value = '';
        document.getElementById('capaModalTitle').textContent = 'Nueva Simbologia';
        form.classList.remove('was-validated');
        updateIconPreview('');
        // Close picker panel
        const panel = document.getElementById('iconPickerPanel');
        if (panel) panel.style.display = 'none';
        iconPickerOpen = false;
        // Re-render to update selection highlight if icons already loaded
        if (iconPickerLoaded) renderIconGrid(
            Array.from(document.querySelectorAll('.icon-picker-item')).map(b => ({
                ruta: document.getElementById('capaIcono').value,
                url: b.querySelector('img')?.src || '',
                nombre: b.title,
            }))
        );
    }

    // ============== LOAD FOR EDIT ==============

    function loadCapaForEdit(id) {
        if (id <= 0) return;

        fetch(capaConfig.getUrl + '/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.ok) {
                capaNotify(data?.message || 'No fue posible cargar la capa.', 'error');
                return;
            }

            const row = data.data || {};
            document.getElementById('capaId').value = row.id || '';
            document.getElementById('capaNombre').value = row.nombre || '';
            document.getElementById('capaSlug').value = row.slug || '';
            syncColorPicker(row.color_default || '#1f6feb');
            document.getElementById('capaEstado').value = row.estado || 'activo';

            const iconPath = row.icono_path || '';
            document.getElementById('capaIcono').value = iconPath;
            document.getElementById('capaModalTitle').textContent = 'Editar Simbologia';
            document.getElementById('capaForm').classList.remove('was-validated');

            // Show preview if icon exists
            const iconUrl = iconPath ? (capaConfig.baseUrl.replace('/admin/etcee/sni/capas', '') + '/' + iconPath).replace(/\/+/, '/') : '';
            // Use base URL + relative path
            updateIconPreview(iconPath ? (window.location.origin + '/' + iconPath) : '');

            // Close picker and mark selection
            const panel = document.getElementById('iconPickerPanel');
            if (panel) panel.style.display = 'none';
            iconPickerOpen = false;
            if (iconPickerLoaded) {
                document.querySelectorAll('.icon-picker-item').forEach(b => {
                    const bRuta = document.getElementById('capaIcono').value;
                    b.classList.toggle('selected', b.title && iconPath !== '' && b.title === (iconPath.split('/').pop() || ''));
                });
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('capaModal')).show();
        })
        .catch(() => capaNotify('Error al cargar la capa.', 'error'));
    }

    // ============== DELETE ==============

    function deleteCapaConfirm(id) {
        if (!confirm('¿Eliminar esta simbologia?')) return;

        const form = new FormData();
        form.append('action', 'delete');
        form.append('id', id);
        form.append(capaConfig.csrfName, capaConfig.csrfValue);

        fetch(capaConfig.baseUrl, {
            method: 'POST',
            body: form,
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.ok) {
                capaNotify(data.message || 'Simbologia eliminada.', 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                capaNotify(data?.message || 'No se pudo eliminar la simbologia.', 'error');
            }
        })
        .catch(() => capaNotify('Error al eliminar la simbologia.', 'error'));
    }

    // ============== SAVE ==============

    document.getElementById('capaForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }

        const form = new FormData(this);
        form.append(capaConfig.csrfName, capaConfig.csrfValue);

        try {
            const response = await fetch(capaConfig.baseUrl, {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data && data.ok) {
                capaNotify(data.message || 'Simbologia guardada correctamente.', 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                capaNotify(data?.message || 'No se pudo guardar la simbologia.', 'error');
            }
        } catch (error) {
            capaNotify('Error: ' + error.message, 'error');
        }
    });

    // ============== MODAL ICON PREVIEW ON MANUAL INPUT ==============

    document.addEventListener('DOMContentLoaded', () => {
        initColorSync();

        const iconInput = document.getElementById('capaIcono');
        if (iconInput) {
            iconInput.addEventListener('input', () => {
                const ruta = iconInput.value.trim();
                if (ruta !== '') {
                    updateIconPreview(window.location.origin + '/' + ruta.replace(/^\/+/, ''));
                } else {
                    updateIconPreview('');
                }
                // Update selection in grid
                document.querySelectorAll('.icon-picker-item').forEach(b => {
                    b.classList.remove('selected');
                });
            });
        }
    });
</script>

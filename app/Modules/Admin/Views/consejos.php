<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0"><i class="bi bi-lightbulb me-2"></i>Consejos de ahorro</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#consejoModal" id="btnNuevoConsejo">
        <i class="bi bi-plus-lg me-1"></i>Nuevo consejo
    </button>
</div>

<p class="text-secondary small">Administra los consejos visibles en la consulta pública de Tarifa Social. Las claves se resuelven mediante el módulo de Idiomas.</p>

<div class="table-responsive">
    <table class="table table-striped align-middle" id="consejosTable">
        <thead>
            <tr>
                <th style="width:60px">Orden</th>
                <th>Clave</th>
                <th>Texto base</th>
                <th>Imagen</th>
                <th>Estado</th>
                <th class="text-end" style="width:140px">Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="modal fade" id="consejoModal" tabindex="-1" aria-labelledby="consejoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="consejoForm" enctype="multipart/form-data" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="consejoModalLabel"><i class="bi bi-lightbulb me-2"></i>Nuevo consejo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="id" id="consejoId" value="0">
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="consejoClave">Clave <span class="text-danger">*</span></label>
                        <input class="form-control" name="clave" id="consejoClave" maxlength="191" required placeholder="tarifaSocial.tips.item5">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="consejoTexto">Texto base <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="texto" id="consejoTexto" maxlength="500" rows="3" required></textarea>
                    </div>
                    <div class="col-8">
                        <label class="form-label fw-semibold" for="consejoImagen">Fotografia</label>
                        <input class="form-control" type="file" name="imagen_file" id="consejoImagen" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">JPG, PNG o WEBP. Maximo 5 MB. En edicion, deja vacio para conservar la imagen actual.</div>
                        <img id="consejoImagenPreview" class="img-thumbnail mt-2 d-none" style="max-height:120px;" alt="Previsualizacion de la imagen">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold" for="consejoOrden">Orden</label>
                        <input class="form-control" type="number" name="orden" id="consejoOrden" min="0" max="65535" value="0">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="activo" id="consejoActivo" value="1" checked>
                            <label class="form-check-label" for="consejoActivo">Visible en el portal público</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    let consejosData = [];
    const tableBody = document.querySelector('#consejosTable tbody');
    const form = document.getElementById('consejoForm');
    const modal = document.getElementById('consejoModal');

    function escAttr(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderConsejos() {
        if (consejosData.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-secondary py-4">No hay consejos registrados.</td></tr>';
            return;
        }

        tableBody.innerHTML = consejosData.map(function (row) {
            const active = Number(row.activo) === 1;
            return `<tr>
                <td>${Number(row.orden) || 0}</td>
                <td><code>${escAttr(row.clave)}</code></td>
                <td>${escAttr(row.texto)}</td>
                <td>${row.imagen ? `<img src="<?= base_url('') ?>${escAttr(row.imagen)}" alt="" class="img-thumbnail" style="width:72px;height:48px;object-fit:cover;">` : '<span class="text-secondary">Sin imagen</span>'}</td>
                <td><span class="badge ${active ? 'bg-success' : 'bg-secondary'}">${active ? 'Visible' : 'Oculto'}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary me-1" data-id="${row.id}" title="Editar" onclick="window.editarConsejo(this)"><i class="bi bi-pencil-square"></i></button>
                    <button class="btn btn-sm btn-outline-danger" data-id="${row.id}" data-clave="${escAttr(row.clave)}" title="Eliminar" onclick="window.eliminarConsejo(this)"><i class="bi bi-trash"></i></button>
                </td>
            </tr>`;
        }).join('');
    }

    async function cargarConsejos() {
        const result = await fetchEncrypted('<?= site_url('admin/consejos/list') ?>');
        consejosData = result?.data?.consejos || [];
        renderConsejos();
    }

    function limpiarFormulario() {
        form.reset();
        document.getElementById('consejoId').value = '0';
        document.getElementById('consejoActivo').checked = true;
        document.getElementById('consejoImagenPreview').removeAttribute('src');
        document.getElementById('consejoImagenPreview').classList.add('d-none');
        document.getElementById('consejoModalLabel').textContent = 'Nuevo consejo';
    }

    window.editarConsejo = function (button) {
        const row = consejosData.find((item) => Number(item.id) === Number(button.dataset.id));
        if (!row) return;

        document.getElementById('consejoId').value = row.id;
        document.getElementById('consejoClave').value = row.clave || '';
        document.getElementById('consejoTexto').value = row.texto || '';
        document.getElementById('consejoImagen').value = '';
        const preview = document.getElementById('consejoImagenPreview');
        if (row.imagen) {
            preview.src = '<?= base_url('') ?>' + row.imagen;
            preview.classList.remove('d-none');
        } else {
            preview.removeAttribute('src');
            preview.classList.add('d-none');
        }
        document.getElementById('consejoOrden').value = row.orden || 0;
        document.getElementById('consejoActivo').checked = Number(row.activo) === 1;
        document.getElementById('consejoModalLabel').textContent = 'Editar consejo';
        bootstrap.Modal.getOrCreateInstance(modal).show();
    };

    window.eliminarConsejo = function (button) {
        confirmAction(
            `¿Eliminar el consejo "${button.dataset.clave}"? Esta accion no se puede deshacer.`,
            async function () {
                const body = new FormData();
                body.append('id', button.dataset.id);
                const result = await fetchEncrypted('<?= site_url('admin/consejos/delete') ?>', {
                    method: 'POST',
                    body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                notify(result?.data?.message || 'Consejo eliminado.', 'success');
                await cargarConsejos();
            },
            { title: 'Eliminar consejo', danger: true }
        );
    };

    document.getElementById('btnNuevoConsejo').addEventListener('click', limpiarFormulario);
    document.getElementById('consejoImagen').addEventListener('change', function () {
        const preview = document.getElementById('consejoImagenPreview');
        const file = this.files && this.files[0];

        if (!file) {
            preview.removeAttribute('src');
            preview.classList.add('d-none');
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const result = await fetchEncrypted('<?= site_url('admin/consejos/save') ?>', {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const message = result?.data?.message || 'No se pudo guardar el consejo.';
        const isError = (result?.status && result.status >= 400) || message.toLowerCase().includes('obligatorio') || message.toLowerCase().includes('existe') || message.toLowerCase().includes('imagen') || message.toLowerCase().includes('solo puede');
        notify(message, isError ? 'danger' : 'success', isError ? 0 : undefined);
        if (!isError) {
            bootstrap.Modal.getInstance(modal)?.hide();
            await cargarConsejos();
        }
    });

    cargarConsejos();
}());
</script>

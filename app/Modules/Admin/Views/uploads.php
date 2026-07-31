<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Carga Masiva</h2>
</div>

<button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
    <i class="bi bi-cloud-upload me-1"></i> Subir Archivo
</button>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="uploadForm" enctype="multipart/form-data" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Carga Masiva de Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Gerencia <span class="text-danger">*</span></label>
                        <select class="form-select" name="gerencia_id" required>
                            <?php foreach (($gerencias ?? []) as $gerencia): ?>
                                <option value="<?= esc($gerencia['id']) ?>"><?= esc($gerencia['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Archivo <span class="text-danger">*</span></label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Formatos permitidos: .xlsx, .xls, .csv</div>
                    </div>
                    <div id="uploadProgress" class="col-12 d-none">
                        <div class="progress" style="height:6px">
                            <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">Procesando archivo...</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="uploadSubmitBtn">
                        <i class="bi bi-cloud-upload me-1"></i>Subir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle" id="uploadsTable">
        <thead>
            <tr>
                <th style="width:60px">ID</th>
                <th>Archivo</th>
                <th>Gerencia</th>
                <th style="width:100px">Registros</th>
                <th style="width:160px">Fecha</th>
                <th style="width:110px"></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
<div id="uploadsPager" class="d-flex justify-content-between align-items-center mt-2 mb-1"></div>
</div>

<script>
let uploadsData = [];
let uploadsPage = 1;
const uploadsPerPage = 10;

function escU(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function renderUploads() {
    const start = (uploadsPage - 1) * uploadsPerPage;
    const page  = uploadsData.slice(start, start + uploadsPerPage);
    const tbody = document.querySelector('#uploadsTable tbody');
    tbody.innerHTML = page.map(row => `
        <tr>
            <td>${row.id}</td>
            <td><i class="bi bi-file-earmark-excel text-success me-1"></i>${escU(row.nombre_archivo)}</td>
            <td>${escU(row.gerencia_nombre)}</td>
            <td><span class="badge bg-primary">${row.registros_procesados}</span></td>
            <td><small class="text-muted">${escU(row.fecha_creacion)}</small></td>
            <td>
                <a href="<?= site_url('admin/uploads') ?>/${row.id}/download"
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i>Descargar
                </a>
            </td>
        </tr>
    `).join('');
    renderUploadsPager();
}

function renderUploadsPager() {
    const total = uploadsData.length;
    const pages = Math.ceil(total / uploadsPerPage);
    const pager = document.getElementById('uploadsPager');
    if (pages <= 1) { pager.innerHTML = ''; return; }
    let btns = '';
    for (let i = 1; i <= pages; i++) {
        btns += `<button class="btn btn-sm ${i === uploadsPage ? 'btn-primary' : 'btn-outline-secondary'} me-1" onclick="goUploadsPage(${i})">${i}</button>`;
    }
    const from = Math.min((uploadsPage - 1) * uploadsPerPage + 1, total);
    const to   = Math.min(uploadsPage * uploadsPerPage, total);
    pager.innerHTML = `<small class="text-muted">Mostrando ${from}&ndash;${to} de ${total}</small><div>${btns}</div>`;
}

function goUploadsPage(p) { uploadsPage = p; renderUploads(); }

async function loadUploads() {
    const result = await fetchEncrypted('<?= site_url('admin/api/uploads') ?>');
    uploadsData = result?.data?.uploads || [];
    uploadsPage = 1;
    renderUploads();
}

document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const progressEl = document.getElementById('uploadProgress');
    const submitBtn  = document.getElementById('uploadSubmitBtn');
    progressEl.classList.remove('d-none');
    submitBtn.disabled = true;

    try {
        const result = await fetchEncrypted('<?= site_url('admin/api/uploads') ?>', {
            method: 'POST',
            body: new FormData(e.target),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (result?.data?.message?.toLowerCase().includes('error')) {
            notify(result.data.message, 'danger', 0);
        } else {
            notify(result?.data?.message || 'Archivo procesado correctamente.', 'success');
            bootstrap.Modal.getInstance(document.getElementById('uploadModal'))?.hide();
            e.target.reset();
            await loadUploads();
        }
    } finally {
        progressEl.classList.add('d-none');
        submitBtn.disabled = false;
    }
});

loadUploads();
</script>

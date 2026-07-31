<?php
$records = (array) ($records ?? []);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Grandes Usuarios ECOE</h1>
        <p class="text-secondary mb-0">Visualización administrativa de respuestas GU1 y GU2 almacenadas en tablas dinámicas.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= esc(site_url('gerencias/ecoe/formularios')) ?>">Volver al motor</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Formulario</th>
                        <th>Referencia</th>
                        <th>Solicitante</th>
                        <th>DPI</th>
                        <th>Continuacion GU1</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-4">Todavía no hay respuestas GU registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><span class="badge text-bg-dark"><?= esc((string) ($record['formulario_codigo'] ?? '')) ?></span> <?= esc((string) ($record['formulario_nombre'] ?? '')) ?></td>
                                <td><code><?= esc((string) ($record['codigo_referencia'] ?? '-')) ?></code></td>
                                <td><?= esc((string) ($record['nombre_solicitante'] ?? $record['nombre'] ?? '-')) ?></td>
                                <td><?= esc((string) ($record['dpi'] ?? '-')) ?></td>
                                <td>
                                    <?php if ((string) ($record['formulario_codigo'] ?? '') !== 'GU1'): ?>
                                        <span class="badge text-bg-secondary">No aplica</span>
                                    <?php elseif (! empty($record['tiene_continuacion'])): ?>
                                        <span class="badge text-bg-success">Recibida</span>
                                        <div class="small text-secondary mt-1"><?= esc((string) ($record['continuacion_fecha_carga'] ?? '')) ?></div>
                                    <?php else: ?>
                                        <span class="badge text-bg-warning">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc((string) ($record['created_at'] ?? '-')) ?></td>
                                <td class="text-end">
                                    <?php if ((string) ($record['formulario_codigo'] ?? '') === 'GU1' && ! empty($record['tiene_continuacion'])): ?>
                                        <a class="btn btn-sm btn-outline-success" href="<?= esc(site_url('gerencias/ecoe/grandes-usuarios/continuacion/' . rawurlencode((string) ($record['codigo_referencia'] ?? '')))) ?>">
                                            Archivo
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-primary" type="button"
                                            onclick="openGuDetail('<?= esc((string) ($record['formulario_codigo'] ?? '')) ?>', <?= (int) ($record['id'] ?? 0) ?>)">
                                        Detalle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="guDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de respuesta GU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="guDetailBody">
                <div class="text-secondary">Selecciona un registro para ver el detalle.</div>
            </div>
        </div>
    </div>
</div>

<script>
function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function openGuDetail(codigo, id) {
    const form = new FormData();
    form.append('action', 'detalle');
    form.append('formulario_codigo', codigo);
    form.append('registro_id', id);

    const data = await fetchEncrypted(<?= json_encode(site_url('gerencias/ecoe/grandes-usuarios')) ?>, { method: 'POST', body: form });
    const body = document.getElementById('guDetailBody');

    if (!data.ok) {
        body.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(data.data?.message || 'No fue posible obtener el detalle.') + '</div>';
    } else {
        const registro = data.data.registro || {};
        const campos = data.data.campos || [];
        let payload = {};

        try {
            payload = registro.payload_json ? JSON.parse(registro.payload_json) : {};
        } catch (error) {
            payload = {};
        }

        const continuation = payload.gu1_continuacion || null;
        const isGu1 = String(codigo || '').toUpperCase() === 'GU1';

        let continuationHtml = '';
        if (isGu1) {
            if (continuation && continuation.archivo_path) {
                const downloadUrl = <?= json_encode(site_url('gerencias/ecoe/grandes-usuarios/continuacion')) ?> + '/' + encodeURIComponent(registro.codigo_referencia || '');
                continuationHtml = '<div class="col-12"><div class="alert alert-success mb-0">'
                    + '<div><strong>Continuacion GU1:</strong> archivo recibido.</div>'
                    + '<div class="small mt-1">Fecha de carga: ' + escapeHtml(continuation.fecha_carga || '-') + '</div>'
                    + '<a class="btn btn-sm btn-outline-success mt-2" href="' + escapeHtml(downloadUrl) + '">Descargar archivo</a>'
                    + '</div></div>';
            } else {
                continuationHtml = '<div class="col-12"><div class="alert alert-warning mb-0">Continuacion GU1 pendiente de carga.</div></div>';
            }
        }

        body.innerHTML = '<div class="row g-3">'
            + '<div class="col-12 col-md-6"><strong>Referencia:</strong> ' + escapeHtml(registro.codigo_referencia || '-') + '</div>'
            + '<div class="col-12 col-md-6"><strong>Estado:</strong> ' + escapeHtml(registro.estado_tramite || 'recibido') + '</div>'
            + continuationHtml
            + '<div class="col-12"><hr></div>'
            + campos.map(field => {
                const slug = field.slug || '';
                const value = registro[slug] || '-';
                return '<div class="col-12 col-md-6"><div class="border rounded-3 p-3 bg-light"><div class="small text-secondary">' + escapeHtml(field.etiqueta || field.nombre || slug) + '</div><div class="fw-semibold">' + escapeHtml(value) + '</div></div></div>';
            }).join('')
            + '</div>';
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('guDetailModal')).show();
}
</script>
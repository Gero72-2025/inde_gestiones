<?php
/**
 * Vista administrativa – Tarifa Social ECOE
 * Index de tickets con filtros, cambio de estado y descarga de adjuntos.
 */
$tickets = (array) ($tickets ?? []);
$estados = (array) ($estados ?? []);
$filters = (array) ($filters ?? []);
$ajaxCipherKey = (string) ($ajaxCipherKey ?? '');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Tarifa Social – Solicitudes</h1>
        <p class="text-secondary mb-0">Gestión de solicitudes ciudadanas de Tarifa Social ECOE.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-dark" href="<?= esc(site_url('gerencias/ecoe/tarifa-social/estados')) ?>">
            <i class="bi bi-signpost-split me-1"></i>Estados
        </a>
        <a class="btn btn-outline-danger" href="<?= esc(site_url('gerencias/ecoe/tarifa-social/plantillas')) ?>">
            <i class="bi bi-file-earmark-pdf me-1"></i>Plantillas PDF
        </a>
        <a class="btn btn-outline-primary" href="<?= esc(site_url('gerencias/ecoe/tarifa-social/nis')) ?>">
            <i class="bi bi-database-up me-1"></i>Base NIS / Carga XLSX
        </a>
        <a class="btn btn-outline-success" href="<?= esc(site_url('gerencias/ecoe/nis-base')) ?>">
            <i class="bi bi-people-fill me-1"></i>Gestionar Base NIS
        </a>
        <a class="btn btn-outline-info" href="<?= esc(site_url('gerencias/ecoe/distribuidoras')) ?>">
            <i class="bi bi-building me-1"></i>Distribuidoras
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= esc(site_url('gerencias/ecoe/tarifa-social')) ?>" class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label fw-semibold mb-1">Buscar</label>
                <input type="text" class="form-control" name="q"
                       value="<?= esc($filters['q'] ?? '') ?>"
                       maxlength="120" placeholder="Nombre, DPI o Código de referencia">
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold mb-1">Estado</label>
                <select name="estado_id" class="form-select">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= (int) $est['id'] ?>"
                            <?= (int) ($filters['estado_id'] ?? 0) === (int) $est['id'] ? 'selected' : '' ?>>
                            <?= esc($est['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a class="btn btn-outline-secondary" href="<?= esc(site_url('gerencias/ecoe/tarifa-social')) ?>" title="Limpiar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de tickets -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Referencia</th>
                        <th>Solicitante</th>
                        <th>DPI</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Fecha Ingreso</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tsTicketsTbody">
                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">
                            No hay solicitudes que coincidan con los filtros.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                        <?php
                        $badgeClass = match ((int) ($t['orden_paso'] ?? 0)) {
                            1 => 'bg-secondary',
                            2 => 'bg-warning text-dark',
                            3 => 'bg-success',
                            4 => 'bg-danger',
                            5 => 'bg-primary',
                            default => 'bg-light text-dark',
                        };
                        ?>
                        <tr id="row-ticket-<?= (int) $t['id'] ?>">
                            <td class="text-muted small"><?= (int) $t['id_solicitud'] ?></td>
                            <td><code><?= esc($t['codigo_referencia']) ?></code></td>
                            <td><?= esc($t['nombre']) ?></td>
                            <td><small><?= esc($t['dpi']) ?></small></td>
                            <td><?= esc($t['telefono']) ?></td>
                            <td>
                                <span class="badge <?= $badgeClass ?>" id="badge-estado-<?= (int) $t['id'] ?>">
                                    <?= esc($t['estado_nombre']) ?>
                                </span>
                            </td>
                            <td><small><?= esc(date('d/m/Y H:i', strtotime($t['fecha_ingreso']))) ?></small></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-info me-1"
                                        onclick="openBitacora(<?= (int) $t['id'] ?>, '<?= esc($t['nombre']) ?>')"
                                        title="Ver bitácora">
                                    <i class="bi bi-journal-text"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary me-1"
                                        onclick="openAdjuntos(<?= (int) $t['id'] ?>, '<?= esc($t['nombre']) ?>')"
                                        title="Ver adjuntos">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick="openCambiarEstado(<?= (int) $t['id'] ?>, <?= (int) $t['estado_id'] ?>, '<?= esc($t['nombre']) ?>')"
                                        title="Cambiar estado">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDelete(<?= (int) $t['id'] ?>, '<?= esc(addslashes($t['nombre'])) ?>')"
                                        title="Eliminar">
                                    <i class="bi bi-trash"></i>
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

<!-- ── Modal Cambiar Estado ──────────────────────────────────────────────── -->
<div class="modal fade" id="tsEstadoModal" tabindex="-1" aria-labelledby="tsEstadoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tsEstadoModalLabel">Cambiar Estado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary mb-3" id="tsEstadoTicketNombre"></p>
                <input type="hidden" id="tsEstadoTicketId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nuevo Estado</label>
                    <select class="form-select" id="tsEstadoSelect">
                        <?php foreach ($estados as $est): ?>
                            <option value="<?= (int) $est['id'] ?>">
                                <?= esc($est['nombre']) ?>
                                <?php if (! empty($est['descripcion'])): ?>
                                    — <?= esc($est['descripcion']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold" for="tsEstadoDescripcion">Descripción / Observación (opcional)</label>
                    <textarea class="form-control" id="tsEstadoDescripcion" rows="3" maxlength="255" placeholder="Ej. Motivo de rechazo, observación del analista, acción requerida, etc."></textarea>
                    <div class="form-text">Este texto se guardará en la bitácora y será visible en el PDF actualizado.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="btnGuardarEstado">
                    <i class="bi bi-check-circle me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Adjuntos ────────────────────────────────────────────────────── -->
<div class="modal fade" id="tsAdjuntosModal" tabindex="-1" aria-labelledby="tsAdjuntosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tsAdjuntosModalLabel">Adjuntos de Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="tsAdjuntosBody">
                <div class="text-center text-secondary py-4">
                    <i class="bi bi-hourglass-split fs-3"></i>
                    <p class="mt-2">Cargando adjuntos…</p>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-outline-primary me-2" id="btnVisualizarAdjunto">
                        <i class="bi bi-eye me-1"></i>Visualizar
                    </button>
                    <button class="btn btn-primary" id="btnDescargarAdjunto">
                        <i class="bi bi-download me-1"></i>Descargar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Bitácora ────────────────────────────────────────────────────── -->
<div class="modal fade" id="tsBitacoraModal" tabindex="-1" aria-labelledby="tsBitacoraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tsBitacoraModalLabel">Bitácora de Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="tsBitacoraBody">
                <div class="text-center text-secondary py-4">
                    <i class="bi bi-hourglass-split fs-3"></i>
                    <p class="mt-2">Cargando bitácora…</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Confirmar Eliminar ─────────────────────────────────────────── -->
<div class="modal fade" id="tsDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Eliminar la solicitud de <strong id="tsDeleteNombre"></strong>?
                Esta acción también borrará todos sus archivos adjuntos y no es reversible.</p>
                <input type="hidden" id="tsDeleteId">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger" id="btnConfirmDelete">
                    <i class="bi bi-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Scripts ───────────────────────────────────────────────────────────── -->
<script>
(function () {
    'use strict';

    const CIPHER_KEY = <?= json_encode($ajaxCipherKey ?? '') ?>;
    const BASE_URL   = <?= json_encode(site_url('gerencias/ecoe/tarifa-social')) ?>;
    const CSRF_TOKEN = window.AdminCipher?.csrfToken ?? '';
    const CSRF_NAME  = window.AdminCipher?.csrfName ?? 'csrf_test_name';

    // ── Cifrado AES-256-CBC (reutiliza helper del proyecto) ──────────────────
    async function encryptPayload(data) {
        if (typeof CryptoHelper !== 'undefined') {
            return CryptoHelper.encrypt(data, CIPHER_KEY);
        }
        return JSON.stringify(data); // fallback si no hay helper
    }

    async function decryptPayload(payload) {
        if (typeof CryptoHelper !== 'undefined' && CryptoHelper.decrypt) {
            return await CryptoHelper.decrypt(payload, CIPHER_KEY);
        }
        return payload;
    }

    function isBase64Like(str) {
        try {
            return /^[A-Za-z0-9+/=]+$/.test(str) && str.length > 10;
        } catch (e) {
            return false;
        }
    }

    async function parseServerResponse(json) {
        if (!json || typeof json !== 'object') {
            throw new Error('Respuesta inválida del servidor.');
        }

        if (typeof json.payload === 'string' && json.payload.trim() !== '') {
            const decrypted = await decryptPayload(json.payload);

            if (typeof decrypted === 'string') {
                const text = decrypted.trim();

                if (text === '') {
                    throw new Error('El servidor devolvió un payload vacío.');
                }

                // Si sigue siendo base64, significa que no se descifró correctamente
                if (isBase64Like(text)) {
                    throw new Error('No se pudo descifrar la respuesta del servidor (problema con clave de cifrado).');
                }

                try {
                    return JSON.parse(text);
                } catch (parseErr) {
                    throw new Error('El payload descifrado no contiene JSON válido: ' + parseErr.message);
                }
            }

            if (decrypted && typeof decrypted === 'object') {
                return decrypted;
            }

            throw new Error('No se pudo descifrar la respuesta del servidor.');
        }

        if (Object.prototype.hasOwnProperty.call(json, 'ok') || Object.prototype.hasOwnProperty.call(json, 'data')) {
            return json;
        }

        const directMessage = [
            json.message,
            json.detail,
            json.title,
            json.error,
        ].find(v => typeof v === 'string' && v.trim() !== '');

        if (directMessage) {
            return {
                ok: false,
                data: {
                    message: directMessage,
                    status: Number(json.status ?? 0) || 0,
                },
            };
        }

        if (json.errors && typeof json.errors === 'object') {
            const firstError = Object.values(json.errors)
                .find(v => typeof v === 'string' && v.trim() !== '');

            if (firstError) {
                return {
                    ok: false,
                    data: {
                        message: firstError,
                        status: Number(json.status ?? 0) || 0,
                    },
                };
            }
        }

        throw new Error('Formato de respuesta no reconocido.');
    }

    // ── Envío AJAX cifrado ───────────────────────────────────────────────────
    async function ajaxPost(url, formData, onSuccess, onError) {
        try {
            if (CSRF_TOKEN && formData instanceof FormData && !formData.has(CSRF_NAME)) {
                formData.append(CSRF_NAME, CSRF_TOKEN);
            }

            const res  = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const json = await res.json();

            const parsed = await parseServerResponse(json);
            const inner  = parsed.data ?? parsed;

            if (parsed.ok) {
                onSuccess(inner);
            } else {
                onError(inner.message ?? 'Error desconocido.');
            }
        } catch (e) {
            onError('Error de red: ' + e.message);
        }
    }

    // ── Modal: Cambiar Estado ────────────────────────────────────────────────
    window.openCambiarEstado = function (ticketId, estadoId, nombre) {
        document.getElementById('tsEstadoTicketId').value    = ticketId;
        document.getElementById('tsEstadoTicketNombre').textContent = 'Solicitud: ' + nombre;
        document.getElementById('tsEstadoSelect').value      = estadoId;
        document.getElementById('tsEstadoDescripcion').value = '';
        new bootstrap.Modal(document.getElementById('tsEstadoModal')).show();
    };

    document.getElementById('btnGuardarEstado').addEventListener('click', async function () {
        const ticketId = document.getElementById('tsEstadoTicketId').value;
        const estadoId = document.getElementById('tsEstadoSelect').value;
        const descripcion = document.getElementById('tsEstadoDescripcion').value.trim();
        const fd       = new FormData();
        fd.append('action',    'cambiar_estado');
        fd.append('ticket_id', ticketId);
        fd.append('estado_id', estadoId);
        if (descripcion !== '') {
            fd.append('descripcion', descripcion);
        }

        this.disabled = true;

        await ajaxPost(BASE_URL, fd,
            (data) => {
                bootstrap.Modal.getInstance(document.getElementById('tsEstadoModal')).hide();
                const badge = document.getElementById('badge-estado-' + ticketId);
                if (badge) badge.textContent = data.estado_nombre ?? '';
                showToast('Estado actualizado.', 'success');
                this.disabled = false;
            },
            (msg) => {
                showToast(msg, 'danger');
                this.disabled = false;
            }
        );
    });

    // ── Modal: Adjuntos ──────────────────────────────────────────────────────
    window.openAdjuntos = function (ticketId, nombre) {
        document.getElementById('tsAdjuntosModalLabel').textContent = 'Adjuntos – ' + nombre;
        const body = document.getElementById('tsAdjuntosBody');
        body.innerHTML = '<div class="text-center text-secondary py-4"><i class="bi bi-hourglass-split fs-3"></i><p class="mt-2">Cargando…</p></div>';
        new bootstrap.Modal(document.getElementById('tsAdjuntosModal')).show();

        // Cargar adjuntos vía GET
        fetch(<?= json_encode(site_url('gerencias/ecoe/tarifa-social/adjuntos')) ?> + '?ticket_id=' + ticketId)
            .then(r => r.json())
            .then(async (json) => {
                const parsed    = await parseServerResponse(json);
                const adjuntos  = parsed.data?.adjuntos ?? [];

                if (adjuntos.length === 0) {
                    body.innerHTML = '<p class="text-secondary text-center py-3">Sin adjuntos.</p>';
                    return;
                }

                let html = '<div class="row g-3">';
                adjuntos.forEach(adj => {
                    html += `
                        <div class="col-6 col-md-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <p class="fw-semibold mb-2 text-capitalize">${adj.tipo_archivo.replace('_',' ')}</p>
                                    <a href="${adj.download_url}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-download me-1"></i>Descargar
                                    </a>
                                </div>
                            </div>
                        </div>`;
                });
                html += '</div>';
                body.innerHTML = html;
            })
            .catch(() => {
                body.innerHTML = '<p class="text-danger text-center py-3">Error al cargar adjuntos.</p>';
            });
    };

    // ── Modal: Bitácora ──────────────────────────────────────────────────────
    window.openBitacora = function (ticketId, nombre) {
        document.getElementById('tsBitacoraModalLabel').textContent = 'Bitácora – ' + nombre;
        const body = document.getElementById('tsBitacoraBody');
        body.innerHTML = '<div class="text-center text-secondary py-4"><i class="bi bi-hourglass-split fs-3"></i><p class="mt-2">Cargando bitácora…</p></div>';
        new bootstrap.Modal(document.getElementById('tsBitacoraModal')).show();

        fetch(<?= json_encode(site_url('gerencias/ecoe/tarifa-social/bitacora')) ?> + '?ticket_id=' + ticketId)
            .then(r => r.json())
            .then(async (json) => {
                const parsed   = await parseServerResponse(json);
                const bitacora = parsed.data?.bitacora ?? [];

                if (bitacora.length === 0) {
                    body.innerHTML = '<p class="text-secondary text-center py-3">Sin movimientos registrados.</p>';
                    return;
                }

                let rows = '';
                bitacora.forEach(item => {
                    rows += `
                        <tr>
                            <td><small>${item.fecha_registro ?? ''}</small></td>
                            <td><span class="badge bg-light text-dark border">${item.estado_nombre ?? ''}</span></td>
                            <td>${item.descripcion ?? ''}</td>
                        </tr>`;
                });

                body.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:170px;">Fecha</th>
                                    <th style="min-width:140px;">Estado</th>
                                    <th>Descripción</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>`;
            })
            .catch(() => {
                body.innerHTML = '<p class="text-danger text-center py-3">Error al cargar la bitácora.</p>';
            });
    };

    // ── Modal: Eliminar ──────────────────────────────────────────────────────
    window.confirmDelete = function (ticketId, nombre) {
        document.getElementById('tsDeleteId').value       = ticketId;
        document.getElementById('tsDeleteNombre').textContent = nombre;
        new bootstrap.Modal(document.getElementById('tsDeleteModal')).show();
    };

    document.getElementById('btnConfirmDelete').addEventListener('click', async function () {
        const ticketId = document.getElementById('tsDeleteId').value;
        const fd       = new FormData();
        fd.append('action',    'delete');
        fd.append('ticket_id', ticketId);

        this.disabled = true;

        await ajaxPost(BASE_URL, fd,
            () => {
                bootstrap.Modal.getInstance(document.getElementById('tsDeleteModal')).hide();
                const row = document.getElementById('row-ticket-' + ticketId);
                if (row) row.remove();
                showToast('Solicitud eliminada.', 'success');
                this.disabled = false;
            },
            (msg) => {
                showToast(msg, 'danger');
                this.disabled = false;
            }
        );
    });

    // ── Toast helper ─────────────────────────────────────────────────────────
    function showToast(msg, type) {
        const container = document.getElementById('toastContainer') ?? (() => {
            const el = document.createElement('div');
            el.id = 'toastContainer';
            el.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(el);
            return el;
        })();

        const id   = 'toast-' + Date.now();
        const html = `
            <div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive">
                <div class="d-flex">
                    <div class="toast-body">${msg}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
        bootstrap.Toast.getOrCreateInstance(document.getElementById(id)).show();
    }
}());
</script>

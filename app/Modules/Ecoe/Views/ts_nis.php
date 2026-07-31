<?php
/**
 * Vista administrativa – Base NIS y Carga Masiva XLSX
 */
$distribuidoras = (array) ($distribuidoras ?? []);
$logs           = (array) ($logs ?? []);
$ajaxCipherKey  = (string) ($ajaxCipherKey ?? '');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-database-up text-primary me-2"></i>Base NIS – Carga Masiva</h1>
        <p class="text-secondary mb-0">Sube archivos XLSX de distribuidoras para actualizar la base de usuarios elegibles.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= esc(site_url('gerencias/ecoe/tarifa-social')) ?>">
        <i class="bi bi-arrow-left me-1"></i>Volver a Solicitudes
    </a>
</div>

<!-- Formulario de carga -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-cloud-upload me-1 text-primary"></i>Cargar archivo XLSX
    </div>
    <div class="card-body">
        <p class="text-secondary small mb-3">
            El archivo debe contener al menos una columna con el encabezado
            <code>correlativo</code>, <code>nis</code> o <code>id_usuario</code>.
            Opcionalmente puede incluir columnas <code>activ_economica</code> y <code>consumo_kwh</code>.
        </p>
        <form id="nisUploadForm" enctype="multipart/form-data">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Distribuidora <span class="text-danger">*</span></label>
                    <select class="form-select" name="distribuidora_id" id="nisDistribuidora" required>
                        <option value="">Selecciona distribuidora…</option>
                        <?php foreach ($distribuidoras as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"><?= esc($d['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold">Archivo XLSX <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="xlsx_file" id="nisFile"
                           accept=".xlsx,.xls" required>
                </div>
                <div class="col-12 col-md-3">
                    <button type="submit" class="btn btn-primary w-100" id="btnNisUpload">
                        <i class="bi bi-upload me-1"></i>Cargar
                    </button>
                </div>
            </div>
        </form>

        <!-- Resultado -->
        <div id="nisResult" class="mt-3 d-none">
            <div class="alert mb-0" id="nisResultAlert" role="alert"></div>
        </div>
    </div>
</div>

<!-- Historial de cargas -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history me-1 text-secondary"></i>Historial de Cargas
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Archivo</th>
                        <th>Registros Procesados</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-secondary py-3">Sin cargas registradas.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $i => $log): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= esc($log['nombre_archivo']) ?></td>
                            <td><?= (int) $log['registros_procesados'] ?></td>
                            <td><small><?= esc($log['fecha_creacion']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const CIPHER_KEY = <?= json_encode($ajaxCipherKey ?? '') ?>;
    const UPLOAD_URL = <?= json_encode(site_url('gerencias/ecoe/tarifa-social/nis')) ?>;
    const CSRF_TOKEN = window.AdminCipher?.csrfToken ?? '';
    const CSRF_NAME  = window.AdminCipher?.csrfName ?? 'csrf_test_name';

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

        // Fallback tolerante para respuestas JSON no cifradas.
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

    document.getElementById('nisUploadForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn     = document.getElementById('btnNisUpload');
        const result  = document.getElementById('nisResult');
        const alert   = document.getElementById('nisResultAlert');

        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
        result.classList.add('d-none');

        const fd = new FormData(this);
        if (CSRF_TOKEN) {
            fd.append(CSRF_NAME, CSRF_TOKEN);
        }

        try {
            const res  = await fetch(UPLOAD_URL, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const json = await res.json();
            const parsed    = await parseServerResponse(json);
            const data      = parsed.data ?? {};

            result.classList.remove('d-none');

            if (parsed.ok) {
                alert.className = 'alert alert-success mb-0';
                alert.innerHTML = `
                    <strong><i class="bi bi-check-circle me-1"></i>${data.message ?? 'Carga completada.'}</strong><br>
                    Insertados: <strong>${data.inserted ?? 0}</strong> &nbsp;|&nbsp;
                    Actualizados: <strong>${data.updated ?? 0}</strong> &nbsp;|&nbsp;
                    Con error: <strong>${data.errors ?? 0}</strong>`;
            } else {
                alert.className = 'alert alert-danger mb-0';
                alert.textContent = data.message ?? 'Ocurrió un error.';
            }
        } catch (err) {
            result.classList.remove('d-none');
            alert.className = 'alert alert-danger mb-0';
            alert.textContent = 'Error de red: ' + err.message;
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-upload me-1"></i>Cargar';
        }
    });
}());
</script>

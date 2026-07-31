<?php
$formularios = (array) ($formularios ?? []);
$camposPorFormulario = (array) ($camposPorFormulario ?? []);

$renderField = static function (array $field, string $formCode): string {
    $slug = (string) ($field['slug'] ?? '');
    $type = strtolower((string) ($field['tipo'] ?? 'text'));
    $label = (string) ($field['etiqueta'] ?? $field['nombre'] ?? $slug);
    $help = trim((string) ($field['ayuda'] ?? ''));
    $required = (int) ($field['obligatorio'] ?? 0) === 1 ? 'required' : '';
    $name = esc($slug);

    if ($slug === '' || $slug === 'empresa_nombre') {
        return '';
    }

    return match ($type) {
        'textarea' => '<div class="col-12"><label class="form-label fw-semibold">' . esc($label) . '</label><textarea class="form-control" name="' . $name . '" rows="3" ' . $required . '></textarea>' . ($help !== '' ? '<div class="form-text">' . esc($help) . '</div>' : '') . '</div>',
        'select' => '<div class="col-12"><label class="form-label fw-semibold">' . esc($label) . '</label><select class="form-select" name="' . $name . '" ' . $required . '><option value="">Selecciona</option></select>' . ($help !== '' ? '<div class="form-text">' . esc($help) . '</div>' : '') . '</div>',
        'checkbox' => '<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="' . $name . '" value="1" id="' . esc($formCode . '_' . $slug) . '"><label class="form-check-label fw-semibold" for="' . esc($formCode . '_' . $slug) . '">' . esc($label) . '</label></div></div>',
        'file' => '<div class="col-12"><label class="form-label fw-semibold">' . esc($label) . '</label><input class="form-control" type="file" name="' . $name . '" ' . $required . '>' . ($help !== '' ? '<div class="form-text">' . esc($help) . '</div>' : '') . '</div>',
        default => '<div class="col-12 col-md-6"><label class="form-label fw-semibold">' . esc($label) . '</label><input class="form-control" type="' . esc($type) . '" name="' . $name . '" ' . $required . '>' . ($help !== '' ? '<div class="form-text">' . esc($help) . '</div>' : '') . '</div>',
    };
};

$offerForm = null;
$complaintForm = null;
foreach ($formularios as $formulario) {
    if ((string) ($formulario['codigo'] ?? '') === 'GU1') {
        $offerForm = $formulario;
    }
    if ((string) ($formulario['codigo'] ?? '') === 'GU2') {
        $complaintForm = $formulario;
    }
}

$gu1TemplateDownloadUrl = site_url('api/ecoe/gu/oferta/plantilla-word');
$gu1ReferenceValidateUrl = site_url('api/ecoe/gu/oferta/validar-referencia');
$gu1ContinueUploadUrl = site_url('api/ecoe/gu/oferta/continuar');
$gu1TemplateUploadPath = 'writable/uploads/ecoe/plantillas/gu1/plantilla_gu1.docx';
?>

<style>
    .eco-gateway {
        background: linear-gradient(135deg, rgba(15,109,143,.08), rgba(26,147,111,.08));
        border: 1px solid rgba(15,109,143,.12);
        border-radius: 1.5rem;
    }
    .eco-card {
        border: 0;
        border-radius: 1.5rem;
        box-shadow: 0 16px 32px rgba(10, 40, 60, .08);
        overflow: hidden;
    }
    .eco-card .card-header {
        background: linear-gradient(135deg, #0f6d8f, #1a936f);
        color: #fff;
    }
    .eco-card--dark .card-header {
        background: linear-gradient(135deg, #1b263b, #415a77);
    }
    .eco-section-hidden { display: none; }
    .eco-swap-col {
        min-height: 100%;
    }
</style>

<div class="eco-gateway p-3 p-lg-4 mb-4 animate__animated animate__fadeIn">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <p class="text-uppercase small mb-1 opacity-75">Grandes Usuarios ECOE</p>
            <h2 class="h3 mb-1">Suministro de energia</h2>
            <p class="mb-0 text-secondary">Selecciona su opcion, completa el formulario y recibe su constancia en PDF.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge text-bg-dark">Gestiones</span>
            <span class="badge text-bg-success">Seguimientos</span>
            <span class="badge text-bg-info">Comentarios</span>
        </div>
    </div>
</div>

<div class="row g-4" id="guInteractiveSwap">
    <div class="col-12 col-xl-6 eco-swap-col">
        <div id="guOfferCard" class="eco-card card h-100 animate__animated animate__fadeInLeft">
            <div class="card-header p-4">
                <h3 class="h4 mb-1"><i class="bi bi-lightning-charge-fill me-2"></i>¿Esta interesado en recibir oferta de suministro de Energia?</h3>
                <p class="mb-0 opacity-75">Formulario GU1</p>
            </div>
            <div class="card-body p-4 d-flex flex-column">
                <p class="text-secondary">Completa la solicitud y el sistema te entregará una constancia en formato PDF</p>
                <div class="mt-auto d-grid gap-2">
                    <button class="btn btn-primary btn-lg w-100" type="button" onclick="showGuSection('offer')">Abrir formulario GU1</button>
                    <button class="btn btn-outline-primary" type="button" onclick="openGuContinueModal()">Continuar proceso GU1</button>
                </div>
            </div>
        </div>

        <div id="guComplaintSection" class="eco-card card eco-card--dark h-100 eco-section-hidden animate__animated animate__fadeInUp">
            <div class="card-header p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="h4 mb-1">Usted ya es Gran Usuario del INDE</h3>
                    <p class="mb-0 opacity-75">¿Desea realizar alguna gestion?</p>
                </div>
                <button class="btn btn-sm btn-light" type="button" onclick="hideGuSection()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="card-body p-4">
                <?php if ($complaintForm): ?>
                    <form method="post" action="<?= esc(site_url('api/ecoe/gu/quejas')) ?>" enctype="multipart/form-data" target="_blank" class="row g-3 js-gu-form" data-gu-code="GU2">
                        <?= csrf_field() ?>
                        <?php foreach (($camposPorFormulario['GU2'] ?? []) as $field): ?>
                            <?= $renderField((array) $field, 'GU2') ?>
                        <?php endforeach; ?>
                        <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                            <button class="btn btn-outline-secondary" type="button" onclick="hideGuSection()">Cancelar</button>
                            <button class="btn btn-dark" type="submit"><i class="bi bi-chat-dots me-1"></i>Enviar y descargar PDF</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">El formulario GU2 todavía no está disponible.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6 eco-swap-col">
        <div id="guComplaintCard" class="eco-card eco-card--dark card h-100 animate__animated animate__fadeInRight">
            <div class="card-header p-4">
                <h3 class="h4 mb-1"><i class="bi bi-chat-square-dots-fill me-2"></i>Usted ya es gran Usuario del INDE</h3>
                <p class="mb-0 opacity-75">¿Desea realizar alguna gestion?</p>
            </div>
            <div class="card-body p-4 d-flex flex-column">
                <p class="text-secondary">Registra tu queja o comentario y descarga un comprobante con instrucciones en pdf.</p>
                <div class="mt-auto">
                    <button class="btn btn-dark btn-lg w-100" type="button" onclick="showGuSection('complaint')">Abrir formulario GU2</button>
                </div>
            </div>
        </div>

        <div id="guOfferSection" class="eco-card card h-100 eco-section-hidden animate__animated animate__fadeInUp">
            <div class="card-header p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="h4 mb-1">Oferta de Suministro</h3>
                    <p class="mb-0 opacity-75">GU1 · formulario dinámico</p>
                </div>
                <button class="btn btn-sm btn-light" type="button" onclick="hideGuSection()"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="card-body p-4">
                <?php if ($offerForm): ?>
                    <form method="post" action="<?= esc(site_url('api/ecoe/gu/oferta')) ?>" enctype="multipart/form-data" target="_blank" class="row g-3 js-gu-form" data-gu-code="GU1">
                        <?= csrf_field() ?>
                        <?php foreach (($camposPorFormulario['GU1'] ?? []) as $field): ?>
                            <?= $renderField((array) $field, 'GU1') ?>
                        <?php endforeach; ?>
                        <div class="col-12 d-flex gap-2 justify-content-end pt-2">
                            <button class="btn btn-outline-secondary" type="button" onclick="hideGuSection()">Cancelar</button>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-file-earmark-pdf me-1"></i>Generar PDF</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">El formulario GU1 todavía no está disponible.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="guSubmitSuccessModal" tabindex="-1" aria-labelledby="guSubmitSuccessModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="guSubmitSuccessModalLabel">Envio completado</h5>
            </div>
            <div class="modal-body" id="guSubmitSuccessModalBody">
                Tus datos se enviaron correctamente. Presiona Aceptar para volver al panel principal y limpiar el formulario.
            </div>
            <div class="modal-body pt-0 eco-section-hidden" id="gu1PostSubmitActions">
                <div class="alert alert-info mb-3">
                    Descarga la plantilla Word, completala y luego usa Continuar proceso GU1 para subir el archivo final con tu referencia.
                </div>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary" id="gu1TemplateDownloadBtn" href="<?= esc($gu1TemplateDownloadUrl) ?>" target="_blank" rel="noopener">
                        <i class="bi bi-file-earmark-word me-1"></i>Descargar plantilla Word GU1
                    </a>
                    <button type="button" class="btn btn-primary" id="gu1OpenContinueFromSuccessBtn">Continuar proceso GU1</button>
                </div>
                <div class="small text-muted mt-2">
                    Ruta para cargar la plantilla en servidor: <?= esc($gu1TemplateUploadPath) ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="guConfirmSubmitModalBtn">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="guContinueProcessModal" tabindex="-1" aria-labelledby="guContinueProcessModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="guContinueProcessModalLabel">Continuar proceso GU1</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="guContinueProcessForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="guContinueReferenceInput">Codigo de referencia</label>
                        <div class="input-group">
                            <input class="form-control" type="text" id="guContinueReferenceInput" name="codigo_referencia" placeholder="Ej. GU1-12345-0000123" required>
                            <button class="btn btn-outline-primary" type="button" id="guValidateReferenceBtn">Validar</button>
                        </div>
                        <div class="form-text">Ingresa la referencia generada en el PDF de GU1.</div>
                    </div>

                    <div class="alert eco-section-hidden" id="guContinueFeedback" role="alert"></div>

                    <div class="mb-3 eco-section-hidden" id="guContinueUploadWrap">
                        <label class="form-label fw-semibold" for="guContinueFileInput">Archivo completado (DOC, DOCX o PDF)</label>
                        <input class="form-control" type="file" id="guContinueFileInput" name="archivo_completado_gu1" accept=".doc,.docx,.pdf" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="guContinueSubmitBtn" disabled>Aceptar archivo lleno</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let pendingGuFormReset = null;
let pendingGuFormCode = '';
let guContinueReferenceValidated = false;

const gu1ReferenceValidateUrl = '<?= esc($gu1ReferenceValidateUrl) ?>';
const gu1ContinueUploadUrl = '<?= esc($gu1ContinueUploadUrl) ?>';

function showGuSection(section) {
    const offerCard = document.getElementById('guOfferCard');
    const complaintCard = document.getElementById('guComplaintCard');
    const offer = document.getElementById('guOfferSection');
    const complaint = document.getElementById('guComplaintSection');

    if (section === 'offer') {
        complaintCard.classList.add('eco-section-hidden');
        offerCard.classList.remove('eco-section-hidden');

        offer.classList.remove('eco-section-hidden');
        offer.classList.add('animate__fadeInRight');
        complaint.classList.add('eco-section-hidden');
    } else {
        offerCard.classList.add('eco-section-hidden');
        complaintCard.classList.remove('eco-section-hidden');

        complaint.classList.remove('eco-section-hidden');
        complaint.classList.add('animate__fadeInLeft');
        offer.classList.add('eco-section-hidden');
    }
}

function hideGuSection() {
    document.getElementById('guOfferCard').classList.remove('eco-section-hidden');
    document.getElementById('guComplaintCard').classList.remove('eco-section-hidden');
    document.getElementById('guOfferSection').classList.add('eco-section-hidden');
    document.getElementById('guComplaintSection').classList.add('eco-section-hidden');
}

function openGuContinueModal(prefillReference = '') {
    const modalElement = document.getElementById('guContinueProcessModal');
    if (!modalElement || !window.bootstrap || typeof window.bootstrap.Modal !== 'function') {
        return;
    }

    const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    modalInstance.show();

    const referenceInput = document.getElementById('guContinueReferenceInput');
    const feedback = document.getElementById('guContinueFeedback');
    const uploadWrap = document.getElementById('guContinueUploadWrap');
    const submitBtn = document.getElementById('guContinueSubmitBtn');
    const form = document.getElementById('guContinueProcessForm');

    guContinueReferenceValidated = false;
    if (form) {
        form.reset();
    }

    if (referenceInput && prefillReference) {
        referenceInput.value = prefillReference;
    }

    if (feedback) {
        feedback.classList.add('eco-section-hidden');
        feedback.classList.remove('alert-success', 'alert-danger', 'alert-info');
        feedback.textContent = '';
    }

    if (uploadWrap) {
        uploadWrap.classList.add('eco-section-hidden');
    }

    if (submitBtn) {
        submitBtn.disabled = true;
    }
}

function setContinueFeedback(type, message) {
    const feedback = document.getElementById('guContinueFeedback');
    if (!feedback) {
        return;
    }

    feedback.classList.remove('eco-section-hidden', 'alert-success', 'alert-danger', 'alert-info');
    feedback.classList.add('alert-' + type);
    feedback.textContent = message;
}

function updateCsrfFromResponse(payload, formElement) {
    if (!payload || !payload.csrf || !formElement) {
        return;
    }

    const csrfName = payload.csrf.name || '';
    const csrfHash = payload.csrf.hash || '';
    if (!csrfName || !csrfHash) {
        return;
    }

    const csrfInput = formElement.querySelector('input[name="' + csrfName + '"]');
    if (csrfInput) {
        csrfInput.value = csrfHash;
    }
}

document.querySelectorAll('.js-gu-form').forEach(function (form) {
    form.addEventListener('submit', function () {
        pendingGuFormReset = form;
        pendingGuFormCode = (form.dataset.guCode || '').toUpperCase();

        const modalElement = document.getElementById('guSubmitSuccessModal');
        if (modalElement && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            const successModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
            window.setTimeout(function () {
                const gu1Actions = document.getElementById('gu1PostSubmitActions');
                const body = document.getElementById('guSubmitSuccessModalBody');
                if (gu1Actions) {
                    if (pendingGuFormCode === 'GU1') {
                        gu1Actions.classList.remove('eco-section-hidden');
                    } else {
                        gu1Actions.classList.add('eco-section-hidden');
                    }
                }

                if (body) {
                    body.textContent = pendingGuFormCode === 'GU1'
                        ? 'Tus datos de GU1 se enviaron correctamente. Puedes descargar la plantilla Word y continuar el proceso cuando la completes.'
                        : 'Tus datos se enviaron correctamente. Presiona Aceptar para volver al panel principal y limpiar el formulario.';
                }

                successModal.show();
            }, 300);
            return;
        }

        window.setTimeout(function () {
            if (window.confirm('Tus datos se enviaron correctamente. Presiona Aceptar para limpiar el formulario.')) {
                if (pendingGuFormReset) {
                    pendingGuFormReset.reset();
                    pendingGuFormReset = null;
                }
                hideGuSection();
            }
        }, 300);
    });
});

const guConfirmSubmitModalBtn = document.getElementById('guConfirmSubmitModalBtn');
if (guConfirmSubmitModalBtn) {
    guConfirmSubmitModalBtn.addEventListener('click', function () {
        if (pendingGuFormReset) {
            pendingGuFormReset.reset();
            pendingGuFormReset = null;
        }

        const modalElement = document.getElementById('guSubmitSuccessModal');
        if (modalElement && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            const successModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
            successModal.hide();
        }

        hideGuSection();
    });
}

const gu1OpenContinueFromSuccessBtn = document.getElementById('gu1OpenContinueFromSuccessBtn');
if (gu1OpenContinueFromSuccessBtn) {
    gu1OpenContinueFromSuccessBtn.addEventListener('click', function () {
        const modalElement = document.getElementById('guSubmitSuccessModal');
        if (modalElement && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            const successModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
            successModal.hide();
        }

        openGuContinueModal();
    });
}

const guValidateReferenceBtn = document.getElementById('guValidateReferenceBtn');
if (guValidateReferenceBtn) {
    guValidateReferenceBtn.addEventListener('click', function () {
        const form = document.getElementById('guContinueProcessForm');
        const referenceInput = document.getElementById('guContinueReferenceInput');
        const uploadWrap = document.getElementById('guContinueUploadWrap');
        const submitBtn = document.getElementById('guContinueSubmitBtn');

        if (!form || !referenceInput) {
            return;
        }

        const reference = referenceInput.value.trim().toUpperCase();
        if (!reference) {
            setContinueFeedback('danger', 'Debes ingresar un codigo de referencia valido.');
            guContinueReferenceValidated = false;
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            if (uploadWrap) {
                uploadWrap.classList.add('eco-section-hidden');
            }
            return;
        }

        const payload = new FormData();
        payload.append('codigo_referencia', reference);

        const csrfInput = form.querySelector('input[type="hidden"]');
        if (csrfInput && csrfInput.name && csrfInput.value) {
            payload.append(csrfInput.name, csrfInput.value);
        }

        fetch(gu1ReferenceValidateUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload,
        }).then(function (response) {
            return response.json().then(function (data) {
                return { status: response.status, data: data };
            });
        }).then(function (result) {
            const data = result.data || {};
            updateCsrfFromResponse(data, form);

            if (result.status >= 200 && result.status < 300 && data.ok && data.found) {
                guContinueReferenceValidated = true;
                setContinueFeedback('success', data.message || 'Referencia encontrada.');

                if (uploadWrap) {
                    uploadWrap.classList.remove('eco-section-hidden');
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
                return;
            }

            guContinueReferenceValidated = false;
            if (uploadWrap) {
                uploadWrap.classList.add('eco-section-hidden');
            }
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            setContinueFeedback('danger', data.message || 'No fue posible validar la referencia.');
        }).catch(function () {
            guContinueReferenceValidated = false;
            if (uploadWrap) {
                uploadWrap.classList.add('eco-section-hidden');
            }
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            setContinueFeedback('danger', 'Error de conexion al validar la referencia.');
        });
    });
}

const guContinueProcessForm = document.getElementById('guContinueProcessForm');
if (guContinueProcessForm) {
    guContinueProcessForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const uploadWrap = document.getElementById('guContinueUploadWrap');
        const submitBtn = document.getElementById('guContinueSubmitBtn');
        const modalElement = document.getElementById('guContinueProcessModal');

        if (!guContinueReferenceValidated) {
            setContinueFeedback('danger', 'Primero debes validar la referencia.');
            if (uploadWrap) {
                uploadWrap.classList.add('eco-section-hidden');
            }
            return;
        }

        const payload = new FormData(guContinueProcessForm);

        if (submitBtn) {
            submitBtn.disabled = true;
        }

        fetch(gu1ContinueUploadUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload,
        }).then(function (response) {
            return response.json().then(function (data) {
                return { status: response.status, data: data };
            });
        }).then(function (result) {
            const data = result.data || {};
            updateCsrfFromResponse(data, guContinueProcessForm);

            if (result.status >= 200 && result.status < 300 && data.ok) {
                setContinueFeedback('success', data.message || 'Archivo recibido correctamente.');
                guContinueReferenceValidated = false;

                window.setTimeout(function () {
                    guContinueProcessForm.reset();
                    if (uploadWrap) {
                        uploadWrap.classList.add('eco-section-hidden');
                    }

                    if (modalElement && window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                        const continueModal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
                        continueModal.hide();
                    }
                }, 850);
                return;
            }

            setContinueFeedback('danger', data.message || 'No fue posible cargar el archivo.');
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }).catch(function () {
            setContinueFeedback('danger', 'Error de conexion al cargar el archivo.');
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        });
    });
}
</script>
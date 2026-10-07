<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurar 2FA | INDE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/century-gothic.css') ?>" rel="stylesheet">
    <style>
        body { background: linear-gradient(145deg, #fff9ec 0%, #f7f9fc 55%, #d9e7ff 100%); min-height: 100vh; }
        .setup-card { border: 0; border-radius: 1.5rem; box-shadow: 0 25px 60px rgba(19, 53, 83, 0.16); }
        .qr-shell svg { width: 100%; height: auto; max-width: 220px; }
    </style>
</head>
<body>
<main class="container min-vh-100 d-flex align-items-center py-5">
    <div class="row justify-content-center w-100">
        <div class="col-12 col-lg-8">
            <div class="card setup-card">
                <div class="card-body p-4 p-lg-5">
                    <div class="row g-4 align-items-center">
                        <div class="col-12 col-lg-5">
                            <p class="text-uppercase small text-secondary mb-2">Seguridad</p>
                            <h1 class="h3 mb-3">Vincular autenticacion TOTP</h1>
                            <p class="text-secondary">No requiere cuenta de Gmail. Cualquier app compatible con TOTP funciona offline despues de la vinculacion inicial.</p>
                            <div class="alert alert-info">Issuer: <strong><?= esc($issuer) ?></strong></div>
                            <p class="small text-secondary mb-0">Escanea el QR con Google Authenticator y luego confirma un codigo de 6 digitos para activar el segundo factor.</p>
                        </div>
                        <div class="col-12 col-lg-7 text-center">
                            <div class="qr-shell d-inline-flex justify-content-center align-items-center p-3 bg-light rounded-4 border">
                                <?php if (! empty($qrSvg)): ?>
                                    <?= $qrSvg ?>
                                <?php else: ?>
                                    <div>
                                        <p class="fw-semibold mb-2">No fue posible generar el codigo QR</p>
                                        <p class="text-secondary small mb-0">Contacta a un administrador del sistema para completar la vinculacion de 2FA.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger mt-4"><?= esc(session()->getFlashdata('error')) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= site_url('2fa/setup') ?>" class="row g-3 align-items-end mt-1">
                        <?= csrf_field() ?>
                        <div class="col-12 col-md-8">
                            <label for="code" class="form-label">Confirma con el codigo actual</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control form-control-lg" id="code" name="code" required>
                        </div>
                        <div class="col-12 col-md-4 d-grid">
                            <button type="submit" class="btn btn-dark btn-lg">Activar 2FA</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
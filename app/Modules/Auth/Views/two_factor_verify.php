<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificacion 2FA | INDE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: radial-gradient(circle at top, #d7f0e7 0%, #f6f8fb 48%, #d9e7ff 100%); min-height: 100vh; }
        .card-shell { border: 0; border-radius: 1.5rem; box-shadow: 0 25px 60px rgba(19, 53, 83, 0.16); }
    </style>
</head>
<body>
<main class="container min-vh-100 d-flex align-items-center py-5">
    <div class="row justify-content-center w-100">
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card card-shell">
                <div class="card-body p-4 p-lg-5">
                    <p class="text-uppercase small text-secondary mb-2">Segundo factor</p>
                    <h1 class="h3 mb-3">Verifica tu identidad</h1>
                    <p class="text-secondary">Ingresa el codigo TOTP de 6 digitos generado por tu app de autenticacion.</p>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('message')): ?>
                        <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= site_url('2fa/verify') ?>" class="vstack gap-3">
                        <?= csrf_field() ?>
                        <div>
                            <label for="code" class="form-label">Codigo 2FA</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="form-control form-control-lg text-center tracking-wide" id="code" name="code" value="<?= esc(old('code')) ?>" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">Validar codigo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingreso Seguro | INDE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/century-gothic.css') ?>" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #d9e7ff 0%, #f7f9fc 45%, #d7f0e7 100%); min-height: 100vh; }
        .auth-shell { min-height: 100vh; }
        .auth-card { border: 0; border-radius: 1.5rem; box-shadow: 0 25px 60px rgba(19, 53, 83, 0.16); }
        .brand-mark { width: 3.25rem; height: 3.25rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 1rem; background: #0f4c81; color: #fff; font-weight: 700; }
    </style>
</head>
<body>
<main class="container auth-shell d-flex align-items-center py-5">
    <div class="row justify-content-center w-100">
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="auth-card card">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="brand-mark">IN</div>
                        <div>
                            <p class="text-uppercase small text-secondary mb-1">Portal informativo</p>
                            <h1 class="h3 mb-0">Ingreso seguro</h1>
                        </div>
                    </div>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('message')): ?>
                        <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= site_url('login') ?>" class="vstack gap-3">
                        <?= csrf_field() ?>
                        <div>
                            <label for="identity" class="form-label">Usuario o correo</label>
                            <input type="text" class="form-control form-control-lg" id="identity" name="identity" value="<?= esc(old('identity')) ?>" required autofocus>
                        </div>
                        <div>
                            <label for="password" class="form-label">Contrasena</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">Continuar</button>
                    </form>

                    <hr class="my-4">

                    <p class="text-secondary small mb-0">Si tu cuenta tiene 2FA habilitado, el sistema pedira un codigo TOTP de 6 digitos despues del login primario.</p>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
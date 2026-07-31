<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= esc((string) ($titleText ?? 'Error de la plataforma')) ?></title>
    <style>
        :root {
            --bg-a: #f2f6fb;
            --bg-b: #e8f0fa;
            --panel: #ffffff;
            --text: #1f2a37;
            --muted: #667085;
            --accent: #0b5cab;
            --danger: #c62828;
            --border: #d7e1ec;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: radial-gradient(1300px 700px at 5% -10%, #dce9f8, transparent 45%), linear-gradient(160deg, var(--bg-a), var(--bg-b));
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            width: min(760px, 100%);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 16px 45px rgba(15, 45, 80, 0.12);
            overflow: hidden;
        }

        .head {
            padding: 24px 28px 14px;
            border-bottom: 1px solid var(--border);
        }

        .code {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--accent);
            background: #eaf3ff;
            border: 1px solid #cfe3fb;
            border-radius: 999px;
            padding: 5px 10px;
            margin-bottom: 10px;
        }

        h1 {
            margin: 0;
            font-size: clamp(1.4rem, 2vw, 2rem);
            line-height: 1.2;
        }

        .summary {
            margin: 12px 0 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .body {
            padding: 18px 28px 26px;
        }

        .hint {
            margin: 0 0 16px;
            color: #334155;
            line-height: 1.6;
        }

        .details {
            margin-top: 14px;
            border: 1px solid #ffd9d9;
            background: #fff6f6;
            color: #7f1d1d;
            border-radius: 10px;
            padding: 12px;
            overflow: auto;
            font-family: Consolas, "Courier New", monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            appearance: none;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 10px 14px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-secondary {
            background: #f8fbff;
            color: #0f3d72;
            border-color: #bfd8f6;
        }

        .inline-form {
            margin: 0;
        }

        .foot {
            border-top: 1px solid var(--border);
            padding: 12px 28px 18px;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
<?php
    $code = (int) ($statusCode ?? 500);
    if ($code < 100 || $code > 599) {
        $code = 500;
    }

    $title = (string) ($titleText ?? 'Error de la plataforma');
    $summary = (string) ($summaryText ?? 'Ocurrio un error al procesar tu solicitud.');
    $hint = (string) ($hintText ?? 'Intenta nuevamente en unos minutos. Si el problema persiste, contacta al administrador.');
    $technical = trim((string) ($technicalMessage ?? ''));
    $showTechnical = ENVIRONMENT !== 'production' && $technical !== '';
    $loginUrl = site_url('login');
    $logoutUrl = site_url('logout');
    $auth = session('auth');
    $hasActiveSession = is_array($auth) && ! empty($auth['user_id']);
?>

<main class="card" role="main" aria-labelledby="error-title">
    <header class="head">
        <span class="code">Error <?= esc((string) $code) ?></span>
        <h1 id="error-title"><?= esc($title) ?></h1>
        <p class="summary"><?= esc($summary) ?></p>
    </header>

    <section class="body">
        <p class="hint"><?= esc($hint) ?></p>

        <?php if ($showTechnical): ?>
            <div class="details"><?= esc($technical) ?></div>
        <?php endif; ?>

        <div class="actions">
            <a class="btn btn-primary" href="<?= esc($loginUrl, 'attr') ?>">Ir a login</a>

            <?php if ($hasActiveSession): ?>
                <form class="inline-form" method="post" action="<?= esc($logoutUrl, 'attr') ?>">
                    <input type="hidden" name="<?= esc(csrf_token(), 'attr') ?>" value="<?= esc(csrf_hash(), 'attr') ?>">
                    <button class="btn btn-secondary" type="submit">Cerrar sesion e ingresar con otra cuenta</button>
                </form>
            <?php endif; ?>

            <button class="btn btn-secondary" type="button" onclick="if (history.length > 1) { history.back(); } else { window.location.href = '<?= esc($loginUrl, 'js') ?>'; }">Regresar</button>
        </div>
    </section>

    <footer class="foot">
        Portal INDE - Mensaje de recuperacion de errores
    </footer>
</main>
</body>
</html>
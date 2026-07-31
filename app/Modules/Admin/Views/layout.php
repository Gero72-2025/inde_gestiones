<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Administrativo INDE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #eef3f8; }
        .shell { min-height: 100vh; }
        .sidebar { background: linear-gradient(180deg, #0b2d4b 0%, #124b78 100%); color: #fff; }
        .sidebar a { color: rgba(255,255,255,.88); text-decoration: none; }
        .sidebar a.active, .sidebar a:hover { color: #fff; }
        .sidebar .gerencia-home-btn {
            background: #f8fbff;
            border: 1px solid rgba(255, 255, 255, .35);
            color: #0b2d4b;
            font-weight: 600;
        }
        .sidebar .gerencia-home-btn:hover,
        .sidebar .gerencia-home-btn:focus {
            background: #ffffff;
            color: #081f34;
        }
        .content-pane { background: #fff; border-radius: 1rem; box-shadow: 0 15px 45px rgba(13, 54, 87, .08); }
        #notifyArea .alert { animation: fadeInDown .25s ease; }
        @keyframes fadeInDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }
    </style>
</head>
<body>
<div class="container-fluid shell py-3 py-lg-4">
    <div class="row g-3">
        <aside class="col-12 col-lg-3 col-xl-2">
            <div class="sidebar rounded-4 p-3 p-lg-4 h-100">
                <p class="small text-uppercase opacity-75 mb-2">INDE</p>
                <h1 class="h5 mb-4">Panel Administrativo</h1>

                <nav class="mb-4">
                    <ul class="list-unstyled vstack gap-2">
                        <?php foreach (($adminNavItems ?? []) as $item): ?>
                            <li>
                                <a href="<?= esc((string) ($item['url'] ?? '#')) ?>">
                                    <i class="bi <?= esc((string) ($item['icon'] ?? 'bi-link')) ?> me-2"></i><?= esc((string) ($item['label'] ?? '')) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>

                <?php if (! empty($auth['gerencia_slug'])): ?>
                <div class="mb-4">
                    <p class="small text-uppercase opacity-75 mb-2">Navegacion de gerencia</p>
                    <a class="btn btn-sm gerencia-home-btn w-100 d-flex align-items-center justify-content-center gap-2"
                       href="<?= site_url('gerencias/' . $auth['gerencia_slug'] . '/dashboard') ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>Inicio de mi gerencia</span>
                    </a>
                </div>
                <?php endif; ?>

                <p class="small text-uppercase opacity-75 mb-2">Modulos permitidos</p>
                <ul class="list-unstyled vstack gap-2 mb-4">
                    <?php foreach (($menuModules ?? []) as $module): ?>
                        <li>
                            <a href="<?= esc($module['url']) ?>">
                                <?= esc($module['nombre']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <hr class="border-light opacity-25">
                <p class="small mb-1">Usuario: <?= esc($auth['username'] ?? '') ?></p>
                <p class="small mb-3">Gerencia: <?= esc($auth['gerencia_nombre'] ?? 'N/A') ?></p>
                <form method="post" action="<?= site_url('logout') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-light w-100">Cerrar sesion</button>
                </form>
            </div>
        </aside>

        <section class="col-12 col-lg-9 col-xl-10">
            <div id="notifyArea" class="mb-3"></div>

            <script>
            window.AdminCipher = {
                key:       <?= json_encode((string) ($ajaxCipherKey ?? '')) ?>,
                csrfToken: <?= json_encode(csrf_hash()) ?>,
                csrfName:  <?= json_encode(csrf_token()) ?>,
            };

            // Exposer CryptoHelper globalmente para vistas que lo necesiten
            window.CryptoHelper = {
                encrypt: async function(data, key) {
                    // Para encriptación, usamos JSON.stringify como fallback
                    return typeof data === 'string' ? data : JSON.stringify(data);
                },
                decrypt: async function(payload, key) {
                    // Usar decryptEnvelope del layout con la key proporcionada
                    const savedKey = window.AdminCipher.key;
                    window.AdminCipher.key = key || savedKey;
                    try {
                        const envelope = {
                            encrypted: true,
                            algorithm: 'AES-256-CBC',
                            payload: payload
                        };
                        return await decryptEnvelope(envelope);
                    } catch (e) {
                        console.error('Error descifrando payload:', e);
                        return payload;
                    } finally {
                        window.AdminCipher.key = savedKey;
                    }
                }
            };

            function notify(message, type = 'success', duration = 4500) {
                const area = document.getElementById('notifyArea');
                if (!area) return;
                const id = '_n' + Date.now();
                area.insertAdjacentHTML('beforeend',
                    `<div id="${id}" class="alert alert-${type} alert-dismissible d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle'}"></i>
                        <span>${message}</span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>`
                );
                if (duration > 0) setTimeout(() => document.getElementById(id)?.remove(), duration);
            }

            function b64ToBytes(base64) {
                const binary = atob(base64);
                const bytes = new Uint8Array(binary.length);
                for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
                return bytes;
            }

            async function deriveKey(rawKey) {
                let material;
                if (rawKey.startsWith('base64:')) {
                    material = b64ToBytes(rawKey.substring(7));
                } else {
                    material = new TextEncoder().encode(rawKey);
                }

                if (material.length !== 32) {
                    material = new Uint8Array(await crypto.subtle.digest('SHA-256', material));
                }

                return crypto.subtle.importKey('raw', material, 'AES-CBC', false, ['decrypt']);
            }

            async function decryptEnvelope(envelope) {
                if (!envelope || !envelope.encrypted) return envelope;
                const payload = b64ToBytes(envelope.payload);
                const iv = payload.slice(0, 16);
                const cipher = payload.slice(48);
                const key = await deriveKey(window.AdminCipher.key || '');
                const plainBuffer = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, key, cipher);
                const plainText = new TextDecoder().decode(plainBuffer);
                return JSON.parse(plainText);
            }

            async function fetchEncrypted(url, options = {}) {
                const headers = Object.assign({}, options.headers || {}, {
                    'X-CSRF-TOKEN': window.AdminCipher.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                });
                const response = await fetch(url, { credentials: 'same-origin', ...options, headers });
                const data = await response.json();
                return decryptEnvelope(data);
            }

            function confirmAction(message, onConfirm, { title = 'Confirmar accion', danger = true } = {}) {
                const el = document.getElementById('globalConfirmModal');
                document.getElementById('globalConfirmTitle').textContent   = title;
                document.getElementById('globalConfirmMessage').textContent = message;
                const btn = document.getElementById('globalConfirmBtn');
                btn.className = `btn btn-sm btn-${danger ? 'danger' : 'primary'}`;
                btn.onclick = () => { bootstrap.Modal.getInstance(el)?.hide(); onConfirm(); };
                bootstrap.Modal.getOrCreateInstance(el).show();
            }
            </script>

            <?php if (session()->getFlashdata('message')): ?>
                <div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <div class="content-pane p-3 p-lg-4">
                <?= view($innerView, $innerData ?? []) ?>
            </div>
        </section>
    </div>
</div>

<footer class="text-center text-muted small py-3">
    &copy; <?= date('Y') ?> AGPT &mdash; GERO &mdash; INDE
</footer>

<!-- Global Confirm Modal -->
<div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-semibold" id="globalConfirmTitle">Confirmar accion</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="mb-0 text-secondary" id="globalConfirmMessage"></p>
            </div>
            <div class="modal-footer border-0 pt-0 gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-danger" id="globalConfirmBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

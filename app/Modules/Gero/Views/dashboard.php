<section class="p-4 p-lg-5 mb-4 rounded-4 text-white" style="background:linear-gradient(135deg,#0f4c81 0%,#1c7c54 100%);">
    <p class="text-uppercase small mb-2 opacity-75">Modulo de gerencia</p>
    <h1 class="display-6 mb-3">Gerencia <?= esc($auth['gerencia_nombre'] ?? 'Gero') ?></h1>
    <p class="mb-0">Acceso unico al modulo de Comunidades GERO.</p>
</section>

<?php
$permissions = array_map('strval', (array) ($auth['permissions'] ?? []));
$canOpenCommunities = in_array('gerencia.gero.comunidades.access', $permissions, true)
    || in_array('gerencia.gero.dashboard.access', $permissions, true)
    || in_array('gerencia.gero.access', $permissions, true);
?>

<div class="row justify-content-center mt-1">
    <?php if ($canOpenCommunities): ?>
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card border-0 shadow-lg overflow-hidden">
            <div class="card-body p-4 p-lg-5" style="background:linear-gradient(135deg,#0b4a66 0%,#1b7c79 100%); color:#fff;">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <span class="badge text-bg-light text-dark mb-3">Modulo activo</span>
                        <h2 class="h2 mb-2">Comunidades GERO</h2>
                        <p class="mb-0 opacity-75">Gestiona solicitudes, fase actual, estado y bitacora de comunidades desde un CRUD unificado.</p>
                    </div>
                    <div class="rounded-circle bg-white bg-opacity-20 d-flex align-items-center justify-content-center" style="width:3.5rem;height:3.5rem;">
                        <i class="bi bi-buildings fs-3"></i>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4 d-sm-flex">
                    <a href="<?= site_url('admin/gero/comunidades') ?>" class="btn btn-light fw-semibold text-primary px-4">
                        <i class="bi bi-gear-fill me-2"></i>Administrar comunidades
                    </a>
                    <a href="<?= site_url('gerencias/gero/comunidades') ?>" class="btn btn-outline-light px-4">
                        <i class="bi bi-diagram-3 me-2"></i>Abrir comunidades (gerencia)
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
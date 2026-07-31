<section class="p-4 p-lg-5 mb-4 rounded-4 text-white" style="background:linear-gradient(135deg,#0f4c81 0%,#1c7c54 100%);">
    <p class="text-uppercase small mb-2 opacity-75">Modulo de gerencia</p>
    <h1 class="display-6 mb-3">Empresa de Transporte y Control de Energia Electrica</h1>
    <p class="mb-0">Panel inicial sobre shell unificado con menu lateral, encabezado y pie compartido.</p>
</section>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="p-2 rounded bg-primary bg-opacity-10">
                        <i class="bi bi-diagram-3 fs-5 text-primary"></i>
                    </div>
                    <div>
                        <h3 class="h6 mb-1">Sistema Nacional Interconectado (SNI)</h3>
                        <p class="text-secondary small mb-2">Gestiona lineas, rutas y subestaciones del SNI.</p>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-primary" href="<?= site_url('gerencias/etcee/sni') ?>">
                        <i class="bi bi-table me-1"></i>Abrir Modulo SNI
                    </a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= site_url('gerencias/etcee/sni/wizard') ?>">
                        <i class="bi bi-upload me-1"></i>Cargar KMZ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="p-2 rounded bg-info bg-opacity-10">
                        <i class="bi bi-speedometer2 fs-5 text-info"></i>
                    </div>
                    <div>
                        <h3 class="h6 mb-1">Modulos Disponibles</h3>
                        <p class="text-secondary small mb-2">Accede a submodulos y funcionalidades avanzadas.</p>
                    </div>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('gerencias/' . ($auth['gerencia_slug'] ?? '')) ?>/modulo/base">
                    <i class="bi bi-grid-3x2 me-1"></i>Abrir modulo base
                </a>
            </div>
        </div>
    </div>
</div>

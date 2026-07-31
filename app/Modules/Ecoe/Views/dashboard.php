<section class="p-4 p-lg-5 mb-4 rounded-4 text-white" style="background:linear-gradient(135deg,#0f4c81 0%,#1c7c54 100%);">
    <p class="text-uppercase small mb-2 opacity-75">Módulo de Gerencia</p>
    <h1 class="display-6 mb-3">Empresa de Comercialización de Energía Eléctrica</h1>
    <p class="mb-0">Panel principal ECOE. Accede a los módulos disponibles desde los accesos rápidos.</p>
</section>

<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-start gap-3">
                <div class="rounded-3 p-2 bg-warning bg-opacity-10">
                    <i class="bi bi-lightning-charge-fill fs-3 text-warning"></i>
                </div>
                <div>
                    <h6 class="mb-1">Tarifa Social</h6>
                    <p class="text-secondary small mb-2">Gestión de solicitudes y base NIS de usuarios elegibles.</p>
                    <a class="btn btn-sm btn-warning text-dark" href="<?= site_url('gerencias/ecoe/tarifa-social') ?>">
                        <i class="bi bi-arrow-right-circle me-1"></i>Abrir módulo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-start gap-3">
                <div class="rounded-3 p-2 bg-primary bg-opacity-10">
                    <i class="bi bi-sliders fs-3 text-primary"></i>
                </div>
                <div>
                    <h6 class="mb-1">Motor de Formularios</h6>
                    <p class="text-secondary small mb-2">Crea y administra los formularios dinámicos que alimentan GU y EEM.</p>
                    <a class="btn btn-sm btn-primary" href="<?= site_url('gerencias/ecoe/formularios') ?>">
                        <i class="bi bi-arrow-right-circle me-1"></i>Abrir módulo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-start gap-3">
                <div class="rounded-3 p-2 bg-info bg-opacity-10">
                    <i class="bi bi-people-fill fs-3 text-info"></i>
                </div>
                <div>
                    <h6 class="mb-1">Grandes Usuarios</h6>
                    <p class="text-secondary small mb-2">Revisa respuestas, constancias y detalle de trámites GU.</p>
                    <a class="btn btn-sm btn-info text-dark" href="<?= site_url('gerencias/ecoe/grandes-usuarios') ?>">
                        <i class="bi bi-arrow-right-circle me-1"></i>Abrir módulo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-start gap-3">
                <div class="rounded-3 p-2 bg-success bg-opacity-10">
                    <i class="bi bi-building fs-3 text-success"></i>
                </div>
                <div>
                    <h6 class="mb-1">EEM</h6>
                    <p class="text-secondary small mb-2">Consulta empresas, expedientes y estado de trámites EEM.</p>
                    <a class="btn btn-sm btn-success" href="<?= site_url('gerencias/ecoe/eem') ?>">
                        <i class="bi bi-arrow-right-circle me-1"></i>Abrir módulo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

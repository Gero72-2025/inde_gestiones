<section class="p-4 p-lg-5 mb-4 rounded-4 text-white" style="background:linear-gradient(135deg,#0f4c81 0%,#1c7c54 100%);">
    <p class="text-uppercase small mb-2 opacity-75">Modulo de gerencia</p>
    <h1 class="display-6 mb-3">Gerencia de Comunicaciones</h1>
    <p class="mb-0">Plantilla de dashboard sobre shell unificado con menu lateral, encabezado y pie.</p>
</section>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <p class="mb-2">Desde aqui puedes abrir submodulos y construir los controladores/modelos de la gerencia.</p>
        <a class="btn btn-outline-primary" href="<?= site_url('gerencias/' . ($auth['gerencia_slug'] ?? '')) ?>/modulo/base">Abrir modulo base</a>
    </div>
</div>

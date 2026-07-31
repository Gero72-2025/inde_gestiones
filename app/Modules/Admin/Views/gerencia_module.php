<h2 class="h4 mb-2"><?= esc($title ?? 'Modulo de gerencia') ?></h2>
<p class="text-secondary mb-3">Ruta dinamica activa para la gerencia <strong><?= esc($moduleSlug ?? '') ?></strong>, seccion <strong><?= esc($section ?? '') ?></strong>.</p>
<div class="alert alert-info mb-3">URL compatible: /admin/{gerencia}/{seccion}. Ejemplo: /admin/gero/dashboard o /admin/divoc/cortes.</div>

<?php if (($moduleSlug ?? '') === 'etcee'): ?>
<div class="card border-0 shadow-sm">
	<div class="card-body">
		<h3 class="h6 mb-2">Acceso rapido ETCEE</h3>
		<p class="text-secondary mb-3">Gestiona cortes y capas del SNI desde el panel administrativo.</p>
		<div class="d-flex flex-wrap gap-2">
			<a class="btn btn-primary" href="<?= site_url('admin/etcee/cortes') ?>">
			<i class="bi bi-calendar3 me-1"></i>Abrir CRUD de Cortes
			</a>
			<a class="btn btn-outline-primary" href="<?= site_url('admin/etcee/sni') ?>">
				<i class="bi bi-diagram-3 me-1"></i>Abrir Wizard SNI
			</a>
			<a class="btn btn-outline-secondary" href="<?= site_url('admin/etcee/sni/capas') ?>">
				<i class="bi bi-palette me-1"></i>Simbologias SNI
			</a>
		</div>
	</div>
</div>
<?php endif; ?>

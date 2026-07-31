<?php
/**
 * Vista administrativa – Distribuidoras ECOE (Index)
 * Listado de distribuidoras con filtros, crear, editar y eliminar.
 */
$records = (array) ($records ?? []);
$filters = (array) ($filters ?? []);
$pager = (array) ($pager ?? []);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-building me-2"></i>Distribuidoras ECOE</h1>
        <p class="text-secondary mb-0">Gestión de empresas distribuidoras de energía eléctrica.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?= esc(site_url('gerencias/ecoe/distribuidoras/create')) ?>">
            <i class="bi bi-plus-circle me-1"></i>Nueva distribuidora
        </a>
        <a class="btn btn-outline-dark" href="<?= esc(site_url('gerencias/ecoe/tarifa-social')) ?>">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= esc(site_url('gerencias/ecoe/distribuidoras')) ?>" class="row g-2 align-items-end">
            <div class="col-12 col-lg-8">
                <label class="form-label fw-semibold mb-1">Buscar</label>
                <input type="text" class="form-control" name="q"
                       value="<?= esc($filters['q'] ?? '') ?>"
                       maxlength="120" placeholder="Nombre de distribuidora">
            </div>
            <div class="col-12 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <a class="btn btn-outline-secondary" href="<?= esc(site_url('gerencias/ecoe/distribuidoras')) ?>" title="Limpiar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de distribuidoras -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-secondary py-4">
                            No hay distribuidoras registradas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <tr id="row-dist-<?= (int) $record['id'] ?>">
                            <td class="fw-semibold"><?= esc($record['nombre']) ?></td>
                            <td>
                                <span class="badge <?= (int) ($record['status'] ?? 0) === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= (int) ($record['status'] ?? 0) === 1 ? 'Activa' : 'Inactiva' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= esc(site_url('gerencias/ecoe/distribuidoras/edit/' . $record['id'])) ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger js-delete-dist"
                                        data-id="<?= (int) $record['id'] ?>"
                                        title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Paginación -->
<?php if ($pager['last_page'] > 1): ?>
    <nav aria-label="Paginación" class="mt-3">
        <ul class="pagination justify-content-center">
            <?php if ($pager['page'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= esc(site_url('gerencias/ecoe/distribuidoras?page=1' . ($filters['q'] ? '&q=' . urlencode($filters['q']) : ''))) ?>">Primera</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="<?= esc(site_url('gerencias/ecoe/distribuidoras?page=' . ($pager['page'] - 1) . ($filters['q'] ? '&q=' . urlencode($filters['q']) : ''))) ?>">Anterior</a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pager['last_page']; $i++): ?>
                <?php if ($i >= $pager['page'] - 2 && $i <= $pager['page'] + 2): ?>
                    <li class="page-item <?= $i === $pager['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= esc(site_url('gerencias/ecoe/distribuidoras?page=' . $i . ($filters['q'] ? '&q=' . urlencode($filters['q']) : ''))) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pager['page'] < $pager['last_page']): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= esc(site_url('gerencias/ecoe/distribuidoras?page=' . ($pager['page'] + 1) . ($filters['q'] ? '&q=' . urlencode($filters['q']) : ''))) ?>">Siguiente</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="<?= esc(site_url('gerencias/ecoe/distribuidoras?page=' . $pager['last_page'] . ($filters['q'] ? '&q=' . urlencode($filters['q']) : ''))) ?>">Última</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Script de eliminación -->
<script>
document.querySelectorAll('.js-delete-dist').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const id = btn.dataset.id;

        if (!confirm('¿Estás seguro de que deseas eliminar esta distribuidora?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        try {
            const response = await fetch('<?= esc(site_url('gerencias/ecoe/distribuidoras')) ?>', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (data.ok) {
                document.getElementById('row-dist-' + id)?.remove();
                alert('Distribuidora eliminada correctamente.');
            } else {
                alert(data.message || 'Error al eliminar la distribuidora.');
            }
        } catch (error) {
            console.error(error);
            alert('Error al eliminar la distribuidora.');
        }
    });
});
</script>

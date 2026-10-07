<?php

use CodeIgniter\Pager\PagerRenderer;

/** @var PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<nav aria-label="Paginación de tarifas mensuales">
    <ul class="pagination justify-content-center mb-0">
        <?php if ($pager->hasPreviousPage()): ?>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getPreviousPage()) ?>" aria-label="Página anterior">Anterior</a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link" aria-disabled="true">Anterior</span>
            </li>
        <?php endif; ?>

        <?php foreach ($pager->links() as $link): ?>
            <li class="page-item<?= $link['active'] ? ' active' : '' ?>">
                <a class="page-link" href="<?= esc($link['uri']) ?>"<?= $link['active'] ? ' aria-current="page"' : '' ?>>
                    <?= esc($link['title']) ?>
                </a>
            </li>
        <?php endforeach; ?>

        <?php if ($pager->hasNextPage()): ?>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getNextPage()) ?>" aria-label="Página siguiente">Siguiente</a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link" aria-disabled="true">Siguiente</span>
            </li>
        <?php endif; ?>
    </ul>
</nav>
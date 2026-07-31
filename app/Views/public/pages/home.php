<div class="row g-3 g-lg-4">
    <div class="col-12">
        <p class="lead mb-2"><?= esc(lang('Portal.demoSplitNotice')) ?></p>
    </div>

    <?php
        $cards = [];

        foreach (($publicMenuItems ?? []) as $item) {
            $children = is_array($item['children'] ?? null) ? $item['children'] : [];
            $isDropdown = (int) ($item['is_dropdown'] ?? 0) === 1;

            if ($isDropdown && $children !== []) {
                foreach ($children as $child) {
                    $cards[] = $child;
                }

                continue;
            }

            if (trim((string) ($item['route_path'] ?? '')) === '') {
                continue;
            }

            $cards[] = $item;
        }
    ?>

    <?php foreach ($cards as $item): ?>
        <div class="col-12 col-lg-4">
            <article class="option-card bg-white p-4 h-100">
                <h2 class="h4 mb-3 d-flex align-items-center gap-2">
                    <i class="bi <?= esc($item['icon_class'] ?? 'bi-grid') ?>"></i>
                    <span><?= esc($item['title'] ?? '') ?></span>
                </h2>
                <p class="text-secondary mb-4"><?= esc($item['description'] ?? '') ?></p>
                <a class="btn btn-primary" href="<?= esc(site_url((string) ($item['route_path'] ?? ''))) ?>"><?= esc(lang('Portal.goSection')) ?></a>
            </article>
        </div>
    <?php endforeach; ?>
</div>

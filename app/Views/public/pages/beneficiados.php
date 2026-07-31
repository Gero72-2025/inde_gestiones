<section class="bg-white border rounded-4 p-3 p-lg-4">
    <h2 class="h3 mb-2"><?= esc(lang('Portal.beneficiadosTitle')) ?></h2>
    <p class="text-secondary mb-4"><?= esc(lang('Portal.beneficiadosHelp')) ?></p>

    <form id="dpiForm" class="row g-3" novalidate>
        <?= csrf_field() ?>
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold" for="dpiInput"><?= esc(lang('Portal.dpiLabel')) ?></label>
            <input id="dpiInput" class="form-control" maxlength="13" inputmode="numeric" pattern="[0-9]{13}" placeholder="<?= esc(lang('Portal.dpiPlaceholder')) ?>" required>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label fw-semibold" for="captchaAnswer"><?= esc(lang('Portal.captchaLabel')) ?></label>
            <input id="captchaAnswer" class="form-control" inputmode="numeric" required>
        </div>
        <div class="col-12 col-md-3 d-flex align-items-end gap-2">
            <span id="captchaQuestion" class="badge text-bg-light border w-100 text-dark py-2"><?= esc($captcha['question'] ?? '') ?></span>
            <button id="captchaRefresh" type="button" class="btn btn-outline-secondary" aria-label="<?= esc(lang('Portal.captchaRefresh')) ?>">
                <i class="bi bi-arrow-repeat"></i>
            </button>
        </div>
        <input id="captchaToken" type="hidden" value="<?= esc($captcha['token'] ?? '') ?>">
        <div class="col-12">
            <button class="btn btn-primary px-4" type="submit"><?= esc(lang('Portal.consultButton')) ?></button>
        </div>
    </form>
</section>

<div class="modal fade" id="dpiResultModal" tabindex="-1" aria-labelledby="dpiResultLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dpiResultLabel"><?= esc(lang('Portal.modalTitle')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><p id="dpiResultText" class="mb-0"></p></div>
        </div>
    </div>
</div>

<section class="hero-panel rounded-4 p-4 p-lg-5 mb-4">
    <div class="row justify-content-center text-center">
        <div class="col-lg-10">
            <h2 class="hero-title h1 mb-3"><?= esc(__('comunidades.hero.title1', '¿Cómo va el proyecto de')) ?></h2>
            <h2 class="hero-title h1 mb-3"><?= esc(__('comunidades.hero.title2', 'electrificación en tu comunidad?')) ?></h2>
            <p class="hero-subtitle mb-1 fw-bold"><?= esc(__('comunidades.hero.subtitle', 'Revisa el estado y las etapas completadas.')) ?></p>
            <p class="mb-3"><?= esc(__('comunidades.hero.instructions', 'Ingresa el nombre de tu comunidad o tu numero de código.')) ?></p>

            <div class="mx-auto">
                <form id="comunidadesSearchForm" class="input-group input-group-lg position-relative" novalidate>
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                    <input id="comunidadSearchInput" type="text" class="form-control border-0" maxlength="120" placeholder="<?= esc(__('comunidades.search.placeholder', 'Ejemplo: GT-ALT-001 o Aldea El Pinar')) ?>" autocomplete="off" />
                    <button id="clearSearchBtn" type="button" class="btn btn-light bg-white text-secondary border-0 d-none" aria-label="<?= esc(__('comunidades.search.clear.aria', 'Limpiar búsqueda')) ?>">
                        <i class="bi bi-x-lg me-1"></i><?= esc(__('comunidades.search.clear', 'Limpiar')) ?>
                    </button>
                    <button class="btn btn-warning fw-semibold" type="submit"><?= esc(__('comunidades.search.submit', 'Consultar')) ?></button>
                </form>
                <div id="searchHint" class="search-hint mt-2"></div>
            </div>
        </div>
    </div>
</section>

<section id="resultPanel" class="card shadow-lg border-0 d-none mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h3 id="communityName" class="h3 mb-1"></h3>
                <p id="communityMeta" class="text-muted mb-0"></p>
            </div>
            <span id="phaseBadge" class="badge phase-badge"></span>
        </div>

        <div class="stepper-wrap mb-4">
            <div class="stepper-line"></div>
            <div class="stepper-grid">
                <div class="step-item" data-phase="fase_1">
                    <div class="step-dot">1</div>
                    <h6><?= esc(__('comunidades.phase.fase_1.short', 'Fase 1')) ?></h6>
                    <small><?= esc(__('comunidades.common.solicitud', 'Solicitud')) ?></small>
                </div>
                <div class="step-item" data-phase="fase_2">
                    <div class="step-dot">2</div>
                    <h6><?= esc(__('comunidades.phase.fase_2.short', 'Fase 2')) ?></h6>
                    <small><?= esc(__('comunidades.common.preinversion', 'Pre-Inversion')) ?></small>
                </div>
                <div class="step-item" data-phase="fase_3">
                    <div class="step-dot">3</div>
                    <h6><?= esc(__('comunidades.phase.fase_3.short', 'Fase 3')) ?></h6>
                    <small><?= esc(__('comunidades.phase3.label', 'Contratación y Ejecución')) ?></small>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <h5 class="mb-3"><?= esc(__('comunidades.timeline.title', 'Linea de Tiempo de Hitos')) ?></h5>
                <ul id="milestonesList" class="list-group timeline-list"></ul>
            </div>
            <div class="col-lg-5">
                <div class="card requirement-card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= esc(__('comunidades.requirements.title', 'Requisitos de la Fase Actual')) ?></h5>
                        <ul id="requirementsList" class="requirement-list"></ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h5 class="mb-3"><?= esc(__('comunidades.mappedFields.title', 'Campos Mapeados por Fase')) ?></h5>
            <div id="mappedFieldsPanel" class="row g-3"></div>
        </div>
    </div>
</section>

<div id="emptyPanel" class="text-center py-5"></div>

<section class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm public-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="public-icon-circle"><i class="bi bi-clipboard2-check"></i></span>
                    <h4 class="h5 mb-0"><?= esc(__('comunidades.initialRequirements.title', 'Requisitos Iniciales')) ?></h4>
                </div>
                <p class="text-muted small mb-2"><?= esc(__('comunidades.initialRequirements.intro', 'Para presentar una solicitud de electrificación rural:')) ?></p>
                <ul class="mb-0 public-check-list">
                    <li><i class="bi bi-check2-circle"></i> <?= esc(__('comunidades.initialRequirements.req1', 'Solicitud firmada y sellada por COCODE vigente.')) ?></li>
                    <li><i class="bi bi-check2-circle"></i> <?= esc(__('comunidades.initialRequirements.req2', 'Datos generales, listado de usuarios y croquis.')) ?></li>
                    <li><i class="bi bi-check2-circle"></i> <?= esc(__('comunidades.initialRequirements.req3', 'Ubicación en coordenadas GTM y UTM.')) ?></li>
                    <li><i class="bi bi-check2-circle"></i> <?= esc(__('comunidades.initialRequirements.req4', 'Certificación de resolucion municipal.')) ?></li>
                    <li><i class="bi bi-check2-circle"></i> <?= esc(__('comunidades.initialRequirements.req5', 'Categorización municipal de la comunidad.')) ?></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-8 position-relative" id="pasosComunidad">
        <p class="mb-0 px-3 py-2 position-absolute top-0 end-0 rounded-3 text-white fw-semibold" style="background:#181630; transform: translateY(-50%); z-index: 2;"><?= esc(__('comunidades.phasesHint', '¿Cómo funcionan las fases?')) ?></p>
        <div class="card border-0 shadow-sm public-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="public-icon-circle"><i class="bi bi-diagram-3"></i></span>
                    <h4 class="h5 mb-0"><?= esc(__('comunidades.processExplain.title', 'Como se electrifica una comunidad rural')) ?></h4>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="phase-mini phase-mini-f1">
                            <h6><i class="bi bi-file-earmark-text me-1"></i><?= esc(__('comunidades.processExplain.fase1.title', 'Fase 1 · Solicitud')) ?></h6>
                            <p><?= esc(__('comunidades.processExplain.fase1.desc', 'Recepción del expediente, visita técnica e inicio del estudio socioeconómico.')) ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="phase-mini phase-mini-f2">
                            <h6><i class="bi bi-graph-up-arrow me-1"></i><?= esc(__('comunidades.processExplain.fase2.title', 'Fase 2 · Pre-Inversion')) ?></h6>
                            <p><?= esc(__('comunidades.processExplain.fase2.desc', 'Diseños eléctricos, gestión ambiental ante MARN y aprobación SNIP en SEGEPLAN.')) ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="phase-mini phase-mini-f3">
                            <h6><i class="bi bi-lightning-charge me-1"></i><?= esc(__('comunidades.processExplain.fase3.title', 'Fase 3 · Ejecución')) ?></h6>
                            <p><?= esc(__('comunidades.processExplain.fase3.desc', 'Licitación, contratación, construction de obra y energizacion de la comunidad.')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm public-card position-relative">
    <p class="mb-0 px-3 py-2 position-absolute top-0 end-0 rounded-3 text-white fw-semibold" style="background:#181630; transform: translateY(-50%); z-index: 2;"><?= esc(__('comunidades.downloadForm.badge', 'Descarga el formulario de solicitud')) ?></p>
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="public-icon-circle"><i class="bi bi-folder2-open"></i></span>
                <h4 class="h5 mb-0"><?= esc(__('comunidades.downloadForm.title', 'Descarga de Documentation')) ?></h4>
            </div>
        </div>
        <div id="downloadsStatus" class="small text-muted mb-2"><?= esc(__('comunidades.downloadForm.loading', 'Cargando documentos...')) ?></div>
        <div id="downloadsList" class="downloads-list"></div>
    </div>
</section>
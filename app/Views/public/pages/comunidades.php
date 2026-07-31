<section class="hero-panel rounded-4 p-4 p-lg-5 mb-4">
    <div class="row justify-content-center text-center">
        <div class="col-lg-10">
            <p class="eyebrow mb-2">Guatemala · Comunidades GERO</p>
            <h2 class="hero-title h1 mb-3">Consulta de Progreso por Comunidad</h2>
            <p class="hero-subtitle mb-4">Busca por nombre o codigo y visualiza el avance oficial en las 3 fases del proceso.</p>

            <div class="search-shell mx-auto">
                <form id="comunidadesSearchForm" class="input-group input-group-lg position-relative" novalidate>
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                    <input id="comunidadSearchInput" type="text" class="form-control border-0" maxlength="120" placeholder="Ejemplo: GT-ALT-001 o Aldea El Pinar" autocomplete="off" />
                    <button id="clearSearchBtn" type="button" class="btn btn-outline-secondary border-0 d-none" aria-label="Limpiar busqueda">
                        <i class="bi bi-x-lg me-1"></i>Limpiar
                    </button>
                    <button class="btn btn-warning fw-semibold" type="submit">Consultar</button>
                </form>
                <div id="searchHint" class="search-hint mt-2">Escribe un codigo o nombre para consultar la fase actual.</div>
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
                    <h6>Fase 1</h6>
                    <small>Solicitud</small>
                </div>
                <div class="step-item" data-phase="fase_2">
                    <div class="step-dot">2</div>
                    <h6>Fase 2</h6>
                    <small>Pre-Inversion</small>
                </div>
                <div class="step-item" data-phase="fase_3">
                    <div class="step-dot">3</div>
                    <h6>Fase 3</h6>
                    <small>Contratacion y Ejecucion</small>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <h5 class="mb-3">Linea de Tiempo de Hitos</h5>
                <ul id="milestonesList" class="list-group timeline-list"></ul>
            </div>
            <div class="col-lg-5">
                <div class="card requirement-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Requisitos de la Fase Actual</h5>
                        <ul id="requirementsList" class="requirement-list"></ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h5 class="mb-3">Campos Mapeados por Fase</h5>
            <div id="mappedFieldsPanel" class="row g-3"></div>
        </div>
    </div>
</section>

<div id="emptyPanel" class="text-center py-5">
    <p class="lead mb-1">Aun no hay una comunidad seleccionada.</p>
    <p class="text-muted mb-0">Utiliza el buscador para comenzar la consulta.</p>
</div>

<section class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm public-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="public-icon-circle"><i class="bi bi-clipboard2-check"></i></span>
                    <h4 class="h5 mb-0">Requisitos Iniciales</h4>
                </div>
                <p class="text-muted small mb-2">Para presentar una solicitud de electrificacion rural:</p>
                <ul class="mb-0 public-check-list">
                    <li><i class="bi bi-check2-circle"></i> Solicitud firmada y sellada por COCODE vigente.</li>
                    <li><i class="bi bi-check2-circle"></i> Datos generales, listado de usuarios y croquis.</li>
                    <li><i class="bi bi-check2-circle"></i> Ubicacion en coordenadas GTM y UTM.</li>
                    <li><i class="bi bi-check2-circle"></i> Certificacion de resolucion municipal.</li>
                    <li><i class="bi bi-check2-circle"></i> Categorizacion municipal de la comunidad.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm public-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="public-icon-circle"><i class="bi bi-diagram-3"></i></span>
                    <h4 class="h5 mb-0">Como se electrifica una comunidad rural</h4>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="phase-mini phase-mini-f1">
                            <h6><i class="bi bi-file-earmark-text me-1"></i>Fase 1 · Solicitud</h6>
                            <p>Recepcion del expediente, visita tecnica e inicio del estudio socioeconomico.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="phase-mini phase-mini-f2">
                            <h6><i class="bi bi-graph-up-arrow me-1"></i>Fase 2 · Pre-Inversion</h6>
                            <p>Disenos electricos, gestion ambiental ante MARN y aprobacion SNIP en SEGEPLAN.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="phase-mini phase-mini-f3">
                            <h6><i class="bi bi-lightning-charge me-1"></i>Fase 3 · Ejecucion</h6>
                            <p>Licitacion, contratacion, construccion de obra y energizacion de la comunidad.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="card border-0 shadow-sm public-card">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="public-icon-circle"><i class="bi bi-folder2-open"></i></span>
                <h4 class="h5 mb-0">Descarga de Documentacion</h4>
            </div>
        </div>
        <div id="downloadsStatus" class="small text-muted mb-2">Cargando documentos...</div>
        <div id="downloadsList" class="downloads-list"></div>
    </div>
</section>

<script>
(function () {
    'use strict';

    let cryptoCache = null;
    const state = {
        currentItem: null,
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatBytes(bytes) {
        const size = Number(bytes || 0);
        if (size <= 0) return '0 B';
        if (size < 1024) return `${size} B`;
        if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    }

    function fileIcon(name) {
        const ext = (String(name).split('.').pop() || '').toLowerCase();
        if (ext === 'pdf') return 'bi-file-earmark-pdf';
        if (ext === 'doc' || ext === 'docx') return 'bi-file-earmark-word';
        if (ext === 'xls' || ext === 'xlsx' || ext === 'csv') return 'bi-file-earmark-excel';
        if (ext === 'zip' || ext === 'rar') return 'bi-file-earmark-zip';
        return 'bi-file-earmark-text';
    }

    function i18n() {
        return window.PortalConfig?.i18n?.comunidades || {};
    }

    function renderDownloads(items) {
        const status = document.getElementById('downloadsStatus');
        const list = document.getElementById('downloadsList');
        const dict = i18n().downloads || {};

        if (!Array.isArray(items) || items.length === 0) {
            status.textContent = dict.none || 'No hay documentos disponibles en /assets/archivos.';
            list.innerHTML = '';
            return;
        }

        status.textContent = `${items.length} ${dict.availableSuffix || 'documento(s) disponible(s).'}`;
        list.innerHTML = items.map((item) => {
            const name = escapeHtml(item.name || dict.defaultName || 'Documento');
            const url = escapeHtml(item.url || '#');
            const size = escapeHtml(formatBytes(item.size || 0));
            const updated = escapeHtml(item.updated_at || '');
            const icon = fileIcon(item.name || '');

            return `
                <a class="download-item d-flex align-items-center justify-content-between gap-3 text-decoration-none" href="${url}" target="_blank" rel="noopener">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi ${icon} fs-5"></i>
                        <div>
                            <div class="fw-semibold text-dark">${name}</div>
                            <div class="small text-muted">${escapeHtml(dict.updatedLabel || 'Actualizado')}: ${updated}</div>
                        </div>
                    </div>
                    <span class="map-pill small text-dark">${size}</span>
                </a>
            `;
        }).join('');
    }

    async function loadDownloads() {
        const status = document.getElementById('downloadsStatus');
        const dict = i18n().downloads || {};
        const endpoint = window?.PortalConfig?.endpoints?.comunidadesArchivos || '';

        if (!endpoint) {
            status.textContent = dict.endpointMissing || 'No se configuro el endpoint de descargas.';
            return;
        }

        try {
            const response = await fetch(endpoint, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json();
            if (!payload?.ok) {
                status.textContent = dict.loadError || 'No fue posible cargar los documentos en este momento.';
                return;
            }

            renderDownloads(payload?.data?.items || []);
        } catch (error) {
            status.textContent = dict.fetchError || 'Error al cargar documentos. Intenta nuevamente.';
        }
    }

    function b64ToBytes(base64) {
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes;
    }

    function bytesToB64(bytes) {
        let binary = '';
        for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
        return btoa(binary);
    }

    function concatBytes(...chunks) {
        const total = chunks.reduce((sum, chunk) => sum + chunk.length, 0);
        const merged = new Uint8Array(total);
        let offset = 0;
        chunks.forEach((chunk) => {
            merged.set(chunk, offset);
            offset += chunk.length;
        });
        return merged;
    }

    async function getCryptoMaterials() {
        if (cryptoCache) return cryptoCache;

        let material;
        if ((window.PortalConfig.cipherKey || '').startsWith('base64:')) {
            material = b64ToBytes(window.PortalConfig.cipherKey.substring(7));
        } else {
            material = new TextEncoder().encode(window.PortalConfig.cipherKey || '');
        }

        if (material.length !== 32) {
            material = new Uint8Array(await crypto.subtle.digest('SHA-256', material));
        }

        const aesKey = await crypto.subtle.importKey('raw', material, 'AES-CBC', false, ['encrypt', 'decrypt']);
        const hmacKey = await crypto.subtle.importKey('raw', material, { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
        cryptoCache = { aesKey, hmacKey };

        return cryptoCache;
    }

    async function encryptEnvelope(payload) {
        const { aesKey, hmacKey } = await getCryptoMaterials();
        const iv = crypto.getRandomValues(new Uint8Array(16));
        const plain = new TextEncoder().encode(JSON.stringify(payload));
        const cipher = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-CBC', iv }, aesKey, plain));
        const mac = new Uint8Array(await crypto.subtle.sign('HMAC', hmacKey, concatBytes(iv, cipher)));

        return {
            encrypted: true,
            algorithm: 'AES-256-CBC',
            payload: bytesToB64(concatBytes(iv, mac, cipher)),
        };
    }

    async function decryptEnvelope(envelope) {
        if (!envelope || !envelope.encrypted) return envelope;

        const { aesKey } = await getCryptoMaterials();
        const bytes = b64ToBytes(envelope.payload || '');
        const iv = bytes.slice(0, 16);
        const cipher = bytes.slice(48);
        const plain = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, aesKey, cipher);

        return JSON.parse(new TextDecoder().decode(plain));
    }

    function phaseLabel(value) {
        const dict = i18n().phase || {};
        if (value === 'fase_2') return dict.fase_2 || 'Fase 2 - Pre-Inversion';
        if (value === 'fase_3') return dict.fase_3 || 'Fase 3 - Ejecucion';
        return dict.fase_1 || 'Fase 1 - Solicitud';
    }

    function normalizePhase(value) {
        if (value === 'fase_2' || value === 'fase_3') return value;
        return 'fase_1';
    }

    function requirementsForPhase(value) {
        const dict = i18n().requirements || {};

        if (value === 'fase_3') {
            return dict.fase_3 || [
                'Licitacion aprobada y adjudicada.',
                'Contratista asignado y orden de inicio.',
                'Plan de ejecucion y supervision activos.',
                'Cierre tecnico y energizacion programada.',
            ];
        }

        if (value === 'fase_2') {
            return dict.fase_2 || [
                'Estudio socioeconomico finalizado.',
                'Diseno electrico en validacion.',
                'Gestion ambiental y aprobacion SNIP en curso.',
                'Verificacion de presupuesto y alcances.',
            ];
        }

        return dict.fase_1 || [
            'Solicitud firmada y sellada por COCODE.',
            'Listado de usuarios y croquis de ubicacion.',
            'Coordenadas GTM / UTM y resolucion municipal.',
            'Clasificacion y registro inicial del expediente.',
        ];
    }

    function renderStepState(phase) {
        document.querySelectorAll('.step-item').forEach((item) => {
            const itemPhase = item.getAttribute('data-phase');
            item.classList.remove('active', 'completed');

            if (itemPhase === phase) {
                item.classList.add('active');
            }

            if (phase === 'fase_2' && itemPhase === 'fase_1') {
                item.classList.add('completed');
            }

            if (phase === 'fase_3' && (itemPhase === 'fase_1' || itemPhase === 'fase_2')) {
                item.classList.add('completed');
            }
        });
    }

    function renderRequirements(phase) {
        const list = document.getElementById('requirementsList');
        const items = requirementsForPhase(phase);
        list.innerHTML = items.map((item) => `<li>${escapeHtml(item)}</li>`).join('');
    }

    function renderMilestones(item) {
        const list = document.getElementById('milestonesList');
        const dict = i18n().milestone || {};
        const dynamicMilestones = item?.landing_dynamic?.milestones;
        const entries = Array.isArray(dynamicMilestones) && dynamicMilestones.length > 0
            ? dynamicMilestones.map((row) => ({
                label: row?.label || dict.campo || 'Campo',
                value: row?.value || dict.pendiente || 'Pendiente',
            }))
            : [
                { label: dict.registro || 'Registro de solicitud', value: item.codigo_comunidad || item.nombre_comunidad || (dict.pendiente || 'Pendiente') },
                { label: dict.faseActual || 'Fase actual', value: phaseLabel(normalizePhase(item.fase_actual || 'fase_1')) },
                { label: dict.estadoActual || 'Estado actual', value: item.estado_actual || (dict.pendiente || 'Pendiente') },
                { label: dict.ultimaActualizacion || 'Ultima actualizacion', value: item.updated_at || (dict.sinActualizar || 'Sin actualizar') },
            ];

        list.innerHTML = entries.map((entry) => `
            <li class="list-group-item timeline-item">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="fw-semibold">${escapeHtml(entry.label)}</div>
                        <div class="small text-secondary">${escapeHtml(entry.value)}</div>
                    </div>
                    <i class="bi bi-check-circle-fill text-success"></i>
                </div>
            </li>
        `).join('');
    }

    function renderMappedFields(item) {
        const panel = document.getElementById('mappedFieldsPanel');
        const dynamic = item?.landing_dynamic || {};
        const mapped = Array.isArray(dynamic.mapped_fields) ? dynamic.mapped_fields : [];
        const cardMap = (dynamic.cards && typeof dynamic.cards === 'object') ? dynamic.cards : {};

        if (mapped.length === 0) {
            const phase = normalizePhase(item.fase_actual || 'fase_1');
            const dict = i18n().mappedFields || {};
            const fallbackBlocks = [
                { phase: i18n().phaseShort?.fase_1 || 'Fase 1', title: dict.fallbackSolicitud || 'Solicitud', value: phase === 'fase_1' ? (dict.estadoActiva || 'Activa') : (dict.estadoRegistrada || 'Registrada') },
                { phase: i18n().phaseShort?.fase_2 || 'Fase 2', title: dict.fallbackPreinversion || 'Pre-Inversion', value: phase === 'fase_2' || phase === 'fase_3' ? (dict.estadoEnProceso || 'En proceso') : (dict.estadoPendiente || 'Pendiente') },
                { phase: i18n().phaseShort?.fase_3 || 'Fase 3', title: dict.fallbackEjecucion || 'Ejecucion', value: phase === 'fase_3' ? (dict.estadoEnEjecucion || 'En ejecucion') : (dict.estadoPendiente || 'Pendiente') },
            ];

            panel.innerHTML = fallbackBlocks.map((block) => `
                <div class="col-md-4">
                    <div class="map-card h-100">
                        <div class="small text-uppercase text-secondary fw-semibold mb-1">${escapeHtml(block.phase)}</div>
                        <div class="fw-semibold mb-1">${escapeHtml(block.title)}</div>
                        <div class="text-muted">${escapeHtml(block.value)}</div>
                    </div>
                </div>
            `).join('');
            return;
        }

        const byPhase = {
            fase_1: [],
            fase_2: [],
            fase_3: [],
        };

        mapped.forEach((row) => {
            const phase = row?.phase;
            if (phase === 'fase_1' || phase === 'fase_2' || phase === 'fase_3') {
                byPhase[phase].push(row);
            } else {
                byPhase.fase_1.push(row);
            }
        });

        const blockDefs = [
            { key: 'fase_1', title: i18n().phase?.fase_1 || 'Fase 1 - Solicitud' },
            { key: 'fase_2', title: i18n().phase?.fase_2 || 'Fase 2 - Pre-Inversion' },
            { key: 'fase_3', title: i18n().phase?.fase_3 || 'Fase 3 - Ejecucion' },
        ];

        panel.innerHTML = blockDefs.map((def) => {
            const items = byPhase[def.key];
            const cards = Array.isArray(cardMap[def.key]) ? cardMap[def.key] : [];
            const dict = i18n().mappedFields || {};

            const lines = items.length > 0
                ? items.map((line) => `<li><strong>${escapeHtml(line.label || (i18n().milestone?.campo || 'Campo'))}:</strong> ${escapeHtml(line.value || '-')}</li>`).join('')
                : `<li class="text-muted">${escapeHtml(dict.noFieldsForPhase || 'Sin campos configurados para esta fase.')}</li>`;

            const cardLines = cards.length > 0
                ? cards.map((line) => `<li><strong>${escapeHtml(line.label || (i18n().milestone?.campo || 'Campo'))}:</strong> ${escapeHtml(line.value || '-')}</li>`).join('')
                : '';

            return `
                <div class="col-md-4">
                    <div class="map-card h-100">
                        <div class="small text-uppercase text-secondary fw-semibold mb-1">${escapeHtml(def.title)}</div>
                        <ul class="mb-0 small ps-3">${lines}</ul>
                        ${cardLines ? `<hr class="my-2"><div class="small text-secondary fw-semibold mb-1">${escapeHtml(dict.cardsTitle || 'Cards')}</div><ul class="mb-0 small ps-3">${cardLines}</ul>` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderResult(item) {
        state.currentItem = item;
        const resultPanel = document.getElementById('resultPanel');
        const emptyPanel = document.getElementById('emptyPanel');
        const phase = normalizePhase(item.fase_actual || 'fase_1');

        document.getElementById('communityName').textContent = item.nombre_comunidad || 'Comunidad';
        document.getElementById('communityMeta').textContent = `${item.codigo_comunidad || '-'} · ${item.municipio || ''}${item.departamento ? ' / ' + item.departamento : ''}`;
        const badge = document.getElementById('phaseBadge');
        badge.textContent = phaseLabel(phase);
        badge.className = `badge phase-badge phase-${phase}`;

        renderStepState(phase);
        renderRequirements(phase);
        renderMilestones(item);
        renderMappedFields(item);

        resultPanel.classList.remove('d-none');
        emptyPanel.classList.add('d-none');
    }

    function faseLabel(value) {
        const dict = i18n().phaseShort || {};
        if (value === 'fase_2') return dict.fase_2 || 'Fase 2';
        if (value === 'fase_3') return dict.fase_3 || 'Fase 3';
        return dict.fase_1 || 'Fase 1';
    }

    function renderRows(items) {
        const dict = i18n().search || {};
        if (!Array.isArray(items) || items.length === 0) {
            document.getElementById('searchHint').textContent = dict.noResults || 'No se encontraron comunidades con ese criterio.';
            document.getElementById('resultPanel').classList.add('d-none');
            document.getElementById('emptyPanel').classList.remove('d-none');
            return;
        }

        renderResult(items[0]);
        document.getElementById('searchHint').textContent = `${dict.resultsFoundPrefix || 'Se encontraron'} ${items.length} ${dict.resultsFoundSuffix || 'resultado(s). Mostrando el primero.'}`;
    }

    document.getElementById('comunidadesSearchForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const term = (document.getElementById('comunidadSearchInput').value || '').trim();

        if (term === '') {
            renderRows([]);
            return;
        }

        const envelope = await encryptEnvelope({ term });

        const response = await fetch(<?= json_encode(site_url('api/gero/comunidades/consultar')) ?>, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.PortalConfig.csrfToken,
            },
            body: JSON.stringify(envelope),
        });

        const raw = await response.json();
        const payload = await decryptEnvelope(raw);

        if (!payload?.ok) {
            renderRows([]);
            return;
        }

        renderRows(payload?.data?.items || []);
    });

    document.getElementById('clearSearchBtn').addEventListener('click', function () {
        document.getElementById('comunidadSearchInput').value = '';
        document.getElementById('resultPanel').classList.add('d-none');
        document.getElementById('emptyPanel').classList.remove('d-none');
        document.getElementById('mappedFieldsPanel').innerHTML = '';
        document.getElementById('milestonesList').innerHTML = '';
        document.getElementById('requirementsList').innerHTML = '';
        this.classList.add('d-none');
    });

    document.getElementById('comunidadSearchInput').addEventListener('input', function () {
        document.getElementById('clearSearchBtn').classList.toggle('d-none', this.value.trim() === '');
    });

    loadDownloads();
})();
</script>

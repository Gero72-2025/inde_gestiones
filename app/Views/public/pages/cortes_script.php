<script>
    const state = {
        calendar: null,
        detailModal: null,
    };

    function formatScheduleDate(value) {
        if (!value) return '';
        const date = value instanceof Date ? value : new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return String(value);
        return date.toLocaleDateString('es-GT', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    function formatScheduleTime(value) {
        if (!value) return '';
        const date = value instanceof Date ? value : new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return '';
        return date.toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    function statusColor(estado) {
        const value = String(estado || '').toLowerCase();
        if (value === 'activo') return '#198754';
        if (value === 'finalizado') return '#6c757d';
        if (value === 'cancelado') return '#dc3545';
        return '#0d6efd';
    }

    function rowsToEvents(rows) {
        const grouped = new Map();

        (rows || []).forEach((row) => {
            const id = Number(row.id || 0);
            if (id <= 0) return;

            const key = id;
            const departamento = String(row.departamento || '').trim();
            const municipio = String(row.municipio || '').trim();

            if (!grouped.has(key)) {
                grouped.set(key, {
                    id,
                    title: row.motivo || (window.PortalConfig?.i18n?.cortes?.eventDefaultTitle || 'Corte de energia'),
                    start: String(row.fecha_inicio || '').replace(' ', 'T'),
                    end: String(row.fecha_fin || '').replace(' ', 'T'),
                    color: /^#[0-9a-f]{6}$/i.test(String(row.estado_mantenimiento_color || ''))
                        ? row.estado_mantenimiento_color
                        : statusColor(row.estado),
                    extendedProps: {
                        estado: row.estado || 'programado',
                        estadoMantenimiento: {
                            id: Number(row.estado_mantenimiento_id || 0),
                            nombre: String(row.estado_mantenimiento_nombre || ''),
                            clave: String(row.estado_mantenimiento_clave || ''),
                            color: String(row.estado_mantenimiento_color || ''),
                        },
                        descripcion: String(row.descripcion || '').trim(),
                        departamentos: [],
                        municipios: [],
                    },
                });
            }

            const event = grouped.get(key);
            if (!event.extendedProps.descripcion && row.descripcion) {
                event.extendedProps.descripcion = String(row.descripcion).trim();
            }
            if (departamento && !event.extendedProps.departamentos.includes(departamento)) {
                event.extendedProps.departamentos.push(departamento);
            }
            if (municipio && !event.extendedProps.municipios.includes(municipio)) {
                event.extendedProps.municipios.push(municipio);
            }
        });

        return Array.from(grouped.values());
    }

    const statusConfig = {
        activo:     { color: '#198754', label: '🟢 ' + (window.PortalConfig?.i18n?.cortes?.status?.activo || 'Activo') },
        programado: { color: '#1a56db', label: '🔵 ' + (window.PortalConfig?.i18n?.cortes?.status?.programado || 'Programado') },
        finalizado: { color: '#6c757d', label: '⚫ ' + (window.PortalConfig?.i18n?.cortes?.status?.finalizado || 'Finalizado') },
        cancelado:  { color: '#dc3545', label: '🔴 ' + (window.PortalConfig?.i18n?.cortes?.status?.cancelado || 'Cancelado') },
    };

    function renderEventDetail(event) {
        const props = event.extendedProps || {};
        const estado = String(props.estado || 'programado').toLowerCase();
        const cfg = statusConfig[estado] || statusConfig.programado;
        const maintenanceState = props.estadoMantenimiento || {};
        const stateColor = /^#[0-9a-f]{6}$/i.test(maintenanceState.color || '') ? maintenanceState.color : cfg.color;
        const stateKey = String(maintenanceState.clave || ({ programado: 'P', activo: 'A', finalizado: 'FP', cancelado: 'C' }[estado] || '')).toUpperCase();
        const stateName = maintenanceState.nombre || cfg.label.replace(/^\S+\s/, '');

        const titleElement = document.getElementById('corteDetailTitle');
        if (titleElement) titleElement.textContent = event.title || '';
        const statusBadge = document.getElementById('corteDetailStatusBadge');
        statusBadge.textContent = stateKey || '—';
        statusBadge.title = stateName;
        statusBadge.setAttribute('aria-label', stateName);
        statusBadge.style.setProperty('background-color', stateColor, 'important');
        document.getElementById('corteDetailDepartment').textContent = (props.departamentos || []).join(', ') || '—';
        document.getElementById('corteDetailLocations').textContent = (props.municipios || []).join(', ')
            || window.PortalConfig?.i18n?.cortes?.detailNoLocations
            || 'Sin ubicaciones registradas';
        document.getElementById('corteDetailDate').textContent = [formatScheduleDate(event.start), formatScheduleDate(event.end)]
            .filter(Boolean)
            .filter((date, index, dates) => index === 0 || date !== dates[0])
            .join(' - ');
        document.getElementById('corteDetailTime').textContent = [formatScheduleTime(event.start), formatScheduleTime(event.end)]
            .filter(Boolean)
            .join(' - ');

        const descEl = document.getElementById('corteDetailDescription');
        if (props.descripcion) {
            descEl.textContent = props.descripcion;
        } else {
            descEl.textContent = window.PortalConfig?.i18n?.cortes?.detailNoDescription || 'Sin descripción';
        }

        if (!state.detailModal && window.bootstrap && window.bootstrap.Modal) {
            state.detailModal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('corteDetailModal'));
        }

        state.detailModal?.show();
    }

    function initCalendar() {
        if (state.calendar) {
            return;
        }

        state.calendar = new FullCalendar.Calendar(document.getElementById('cortesCalendar'), {
            locale: 'es',
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay',
            },
            eventClick: function (info) {
                renderEventDetail(info.event);
            },
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            },
        });

        state.calendar.render();
    }

    async function loadMunicipios(departamentoId) {
        const select = document.getElementById('municipioSelect');
        select.innerHTML = `<option value="">${window.PortalConfig?.i18n?.cortes?.allMunicipalities || 'Todos los municipios'}</option>`;

        if (!departamentoId) {
            return;
        }

        const response = await fetch(
            window.PortalConfig.endpoints.cortesMunicipios + '?departamento_id=' + encodeURIComponent(departamentoId),
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        );
        const result = await response.json();

        (result.data || []).forEach((municipio) => {
            const option = document.createElement('option');
            option.value = municipio.id;
            option.textContent = municipio.nombre;
            select.appendChild(option);
        });
    }

    async function loadCortes() {
        const departamentoId = document.getElementById('departamentoSelect').value || '';
        const municipioId = document.getElementById('municipioSelect').value || '';
        const response = await fetch(
            window.PortalConfig.endpoints.cortes
                + '?departamento_id=' + encodeURIComponent(departamentoId)
                + '&municipio_id=' + encodeURIComponent(municipioId),
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        );

        const result = await response.json();
        const events = rowsToEvents(result.data || []);
        state.calendar.removeAllEvents();
        events.forEach((event) => state.calendar.addEvent(event));

        if (events.length === 0 && state.detailModal) {
            state.detailModal.hide();
        }
    }

    document.getElementById('departamentoSelect').addEventListener('change', async function (event) {
        await loadMunicipios(event.target.value);
        await loadCortes();
    });

    document.getElementById('municipioSelect').addEventListener('change', async function () {
        await loadCortes();
    });

    document.getElementById('cortesForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        await loadCortes();
    });

    initCalendar();
    loadCortes();
</script>

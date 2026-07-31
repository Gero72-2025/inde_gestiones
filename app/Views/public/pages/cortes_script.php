<script>
    const state = {
        calendar: null,
        detailModal: null,
    };

    function formatDate(value) {
        if (!value) return '';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return String(value);
        return date.toLocaleString('es-GT', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
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
            const loc = [row.departamento || '', row.municipio || ''].filter(Boolean).join(' / ');

            if (!grouped.has(key)) {
                grouped.set(key, {
                    id,
                    title: row.motivo || 'Corte de energia',
                    start: String(row.fecha_inicio || '').replace(' ', 'T'),
                    end: String(row.fecha_fin || '').replace(' ', 'T'),
                    color: statusColor(row.estado),
                    extendedProps: {
                        estado: row.estado || 'programado',
                        descripcion: String(row.descripcion || '').trim(),
                        locations: [],
                    },
                });
            }

            const event = grouped.get(key);
            if (!event.extendedProps.descripcion && row.descripcion) {
                event.extendedProps.descripcion = String(row.descripcion).trim();
            }
            if (loc && !event.extendedProps.locations.includes(loc)) {
                event.extendedProps.locations.push(loc);
            }
        });

        return Array.from(grouped.values());
    }

    const statusConfig = {
        activo:     { color: '#198754', label: '🟢 Activo' },
        programado: { color: '#1a56db', label: '🔵 Programado' },
        finalizado: { color: '#6c757d', label: '⚫ Finalizado' },
        cancelado:  { color: '#dc3545', label: '🔴 Cancelado' },
    };

    function renderEventDetail(event) {
        const props = event.extendedProps || {};
        const locations = Array.isArray(props.locations) ? props.locations : [];
        const estado = String(props.estado || 'programado').toLowerCase();
        const cfg = statusConfig[estado] || statusConfig.programado;

        document.getElementById('corteDetailTitle').textContent = event.title || '';
        document.getElementById('corteDetailStatusBadge').textContent = cfg.label;
        document.getElementById('corteDetailHeader').style.background = cfg.color;
        document.getElementById('corteDetailStart').textContent = formatDate(event.start);
        document.getElementById('corteDetailEnd').textContent = formatDate(event.end);

        const locEl = document.getElementById('corteDetailLocations');
        if (locations.length > 0) {
            locEl.innerHTML = locations.map((loc) => `<span class="badge bg-light text-dark border me-1 mb-1">${loc}</span>`).join('');
        } else {
            locEl.textContent = 'Sin ubicaciones registradas';
        }

        // Mostrar descripción
        const descEl = document.getElementById('corteDetailDescription');
        if (props.descripcion) {
            descEl.textContent = props.descripcion;
        } else {
            descEl.textContent = 'Sin descripción';
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

    async function loadCortes() {
        const departamento = document.getElementById('departamentoInput').value || '';
        const municipio = document.getElementById('municipioInput').value || '';
        const response = await fetch(
            window.PortalConfig.endpoints.cortes
                + '?departamento=' + encodeURIComponent(departamento)
                + '&municipio=' + encodeURIComponent(municipio),
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

    document.getElementById('cortesForm').addEventListener('submit', async function (event) {
        event.preventDefault();
        await loadCortes();
    });

    initCalendar();
    loadCortes();
</script>

<div id="autoUpdateWizard"
     data-analyze-url="<?= esc(site_url('admin/update/analyze')) ?>"
     data-attachments-url="<?= esc(site_url('admin/update/attachments')) ?>"
     data-export-url="<?= esc(site_url('admin/update/export-selected')) ?>">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-success mb-1">Administracion del sistema</p>
            <h2 class="h3 mb-1">Empaquetado inteligente</h2>
        </div>
        <span class="badge text-bg-warning">Solo superadministrador</span>
    </div>

    <nav class="nav nav-pills border-bottom pb-3 mb-4 gap-2" aria-label="Pasos del asistente">
        <button type="button" class="nav-link active" data-step-nav="1" aria-current="step">1. Módulo y datos</button>
        <button type="button" class="nav-link" data-step-nav="2" disabled>2. Archivos</button>
        <button type="button" class="nav-link" data-step-nav="3" disabled>3. Resumen</button>
    </nav>

    <div id="wizardAlert" class="alert d-none" role="alert"></div>

    <section class="wizard-step" data-step="1" aria-labelledby="stepOneTitle">
        <h3 class="h5 mb-3" id="stepOneTitle">Seleccionar módulo y registros</h3>
        <?php if (($modules ?? []) === []): ?>
            <div class="alert alert-warning">No se encontraron módulos en <code>app/Modules</code>.</div>
        <?php else: ?>
            <div class="row g-3 align-items-end mb-4">
                <div class="col-12 col-lg-6">
                    <label class="form-label" for="moduleSelect">Módulo</label>
                    <select class="form-select" id="moduleSelect" required>
                        <?php foreach (($modules ?? []) as $module): ?>
                            <option value="<?= esc($module['id']) ?>"><?= esc($module['label'] ?? $module['name']) ?> (<?= (int) ($module['table_count'] ?? 0) ?> tablas)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-success" id="analyzeModuleBtn">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Analizar módulo
                    </button>
                </div>
                <div class="col-12"><p class="small text-secondary mb-0">Se muestran hasta 30 filas por tabla. Las columnas sensibles y las tablas sin clave primaria simple no se exportan.</p></div>
            </div>
            <div id="tablePreview" aria-live="polite"></div>
            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-primary" id="toAttachmentsBtn" disabled>Siguiente: archivos <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button>
            </div>
        <?php endif; ?>
    </section>

    <section class="wizard-step d-none" data-step="2" aria-labelledby="stepTwoTitle">
        <h3 class="h5 mb-3" id="stepTwoTitle">Archivos referenciados</h3>
        <p class="text-secondary">Las referencias se revisan únicamente para los registros seleccionados y dentro de las rutas permitidas para el módulo.</p>
        <div class="d-flex align-items-center gap-3 mb-3">
            <button type="button" class="btn btn-outline-primary" id="rescanAttachmentsBtn">
                <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Volver a escanear
            </button>
            <span class="small text-secondary" id="attachmentStatus" aria-live="polite"></span>
        </div>
        <div id="attachmentPreview"></div>
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary" data-step-back="1"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Atrás</button>
            <button type="button" class="btn btn-primary" id="toSummaryBtn" disabled>Siguiente: resumen <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button>
        </div>
    </section>

    <section class="wizard-step d-none" data-step="3" aria-labelledby="stepThreeTitle">
        <h3 class="h5 mb-3" id="stepThreeTitle">Validar paquete</h3>
        <div id="packageSummary" class="mb-3"></div>
        <div class="alert alert-warning" role="note">El paquete puede contener registros de producción. Revisa la selección antes de compartirlo.</div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="confirmSensitivePackage">
            <label class="form-check-label" for="confirmSensitivePackage">Confirmo que revisé los datos seleccionados y su destino.</label>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-outline-secondary" data-step-back="2"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Atrás</button>
            <button type="button" class="btn btn-success" id="generatePackageBtn" disabled>
                <i class="bi bi-download me-1" aria-hidden="true"></i>Confirmar y generar ZIP
            </button>
        </div>
    </section>

    <hr class="my-5">

    <section aria-labelledby="importTitle">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-box-arrow-in-down fs-5 text-primary" aria-hidden="true"></i>
            <h3 class="h5 mb-0" id="importTitle">Instalar o actualizar</h3>
        </div>
        <form method="post" action="<?= site_url('admin/update/import') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label class="form-label" for="packageFile">Paquete ZIP de INDE (máximo 25 MB)</label>
            <div class="d-flex flex-wrap gap-2">
                <input class="form-control flex-grow-1" type="file" id="packageFile" name="package" accept=".zip,application/zip" required>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-cloud-arrow-up me-1" aria-hidden="true"></i>Validar e instalar
                </button>
            </div>
        </form>
        <div class="alert alert-warning mt-3 mb-0" role="note">Instala únicamente paquetes de una fuente confiable. Las migraciones contienen código PHP ejecutable.</div>
    </section>

    <?php if (($report ?? []) !== []): ?>
        <section class="mt-4" aria-labelledby="reportTitle">
            <h3 class="h5 mb-3" id="reportTitle">Reporte de procesamiento</h3>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead><tr><th scope="col">Estado</th><th scope="col">Componente</th><th scope="col">Detalle</th></tr></thead>
                    <tbody>
                    <?php foreach ($report as $item): ?>
                        <?php
                        $status = (string) ($item['status'] ?? 'error');
                        $badgeClass = match ($status) {
                            'success' => 'text-bg-success',
                            'omitted' => 'text-bg-secondary',
                            default => 'text-bg-danger',
                        };
                        ?>
                        <tr>
                            <td><span class="badge <?= esc($badgeClass) ?>"><?= esc($status) ?></span></td>
                            <td class="text-break"><code><?= esc((string) ($item['path'] ?? '')) ?></code></td>
                            <td><?= esc((string) ($item['message'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
(() => {
    const wizard = document.getElementById('autoUpdateWizard');
    if (!wizard) return;

    const alertBox = document.getElementById('wizardAlert');
    const moduleSelect = document.getElementById('moduleSelect');
    const tablePreview = document.getElementById('tablePreview');
    const attachmentPreview = document.getElementById('attachmentPreview');
    const attachmentStatus = document.getElementById('attachmentStatus');
    const summary = document.getElementById('packageSummary');
    const analyzeButton = document.getElementById('analyzeModuleBtn');
    const attachmentButton = document.getElementById('rescanAttachmentsBtn');
    const generateButton = document.getElementById('generatePackageBtn');
    let analysis = null;
    let scan = null;
    const selectedRecords = new Map();

    function showAlert(message, type = 'danger') {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
    }

    function clearAlert() {
        alertBox.className = 'alert d-none';
        alertBox.textContent = '';
    }

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = String(text);
        return node;
    }

    async function postEncrypted(url, values) {
        const body = new FormData();
        Object.entries(values).forEach(([key, value]) => body.append(key, value));
        const response = await fetchEncrypted(url, { method: 'POST', body });
        if (!response?.ok) throw new Error(response?.data?.message || 'No fue posible completar la solicitud.');
        return response.data;
    }

    function selection() {
        const result = {};
        selectedRecords.forEach((keys, table) => {
            if (keys.size) result[table] = keys.has('*') ? ['*'] : Array.from(keys);
        });
        return result;
    }

    function selectionCount() {
        let total = 0;
        selectedRecords.forEach((keys, tableName) => {
            if (keys.has('*')) {
                const table = analysis?.tables.find((item) => item.name === tableName);
                total += Number(table?.total_rows || 0);
            } else {
                total += keys.size;
            }
        });
        return total;
    }

    function setStep(step) {
        document.querySelectorAll('.wizard-step').forEach((section) => {
            section.classList.toggle('d-none', Number(section.dataset.step) !== step);
        });
        document.querySelectorAll('[data-step-nav]').forEach((button) => {
            const active = Number(button.dataset.stepNav) === step;
            button.classList.toggle('active', active);
            if (active) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
        });
    }

    function renderAnalysis(data) {
        tablePreview.replaceChildren();
        const count = element('p', 'small text-secondary', `${data.tables.length} tablas; selección máxima ${Number(data.max_selected_records).toLocaleString()} registros.`);
        tablePreview.append(count);

        if (data.tables.length === 0) {
            tablePreview.append(element('div', 'alert alert-info', 'No se encontraron tablas con el prefijo de este módulo.'));
            return;
        }

        selectedRecords.clear();
        data.tables.forEach((table) => tablePreview.append(renderTableSection(table)));
        updateSelectionCount();
        document.querySelectorAll('[data-step-nav="2"], [data-step-nav="3"]').forEach((button) => { button.disabled = false; });
        document.getElementById('toAttachmentsBtn').disabled = false;
    }

    function renderTableSection(table) {
        const section = element('section', 'border-top py-3');
        section.dataset.table = table.name;
        const header = element('div', 'd-flex flex-wrap justify-content-between align-items-center gap-2 mb-2');
        const title = element('h4', 'h6 mb-0', table.name);
        const rowInfo = element('span', 'small text-secondary', `${table.total_rows} registros; página ${table.page} de ${table.pages}`);
        const selected = selectedRecords.get(table.name) || new Set();
        const selectAllLabel = element('label', 'form-check d-flex align-items-center gap-2 mb-0');
        const selectAll = document.createElement('input');
        selectAll.type = 'checkbox';
        selectAll.className = 'form-check-input table-select-all mt-0';
        selectAll.checked = selected.has('*');
        selectAll.disabled = !table.selectable || (table.total_rows === 0 && !selectAll.checked);
        selectAll.dataset.table = table.name;
        selectAll.dataset.selectable = table.selectable ? '1' : '0';
        selectAll.setAttribute('aria-label', `Seleccionar tabla completa ${table.name}`);
        selectAllLabel.append(selectAll, element('span', 'small', 'Seleccionar tabla completa'));
        selectAll.addEventListener('change', () => {
            const previous = new Set(selectedRecords.get(table.name) || []);
            if (selectAll.checked) selectedRecords.set(table.name, new Set(['*']));
            else selectedRecords.delete(table.name);

            const max = analysis?.max_selected_records || 50_000;
            if (selectionCount() > max) {
                selectedRecords.set(table.name, previous);
                selectAll.checked = false;
                showAlert(`La selección completa excede el límite de ${Number(max).toLocaleString()} registros por paquete.`);
            } else {
                clearAlert();
            }
            section.replaceWith(renderTableSection(table));
            updateSelectionCount();
        });
        header.append(title, selectAllLabel, rowInfo);
        if (table.excluded_columns?.length) {
            header.append(element('span', 'badge text-bg-info', `Campos secretos excluidos: ${table.excluded_columns.join(', ')}`));
        } else if (table.sensitive_columns.length) {
            header.append(element('span', 'badge text-bg-warning', `No exportable: ${table.sensitive_columns.join(', ')}`));
        } else if (!table.selectable) {
            header.append(element('span', 'badge text-bg-secondary', 'Sin clave primaria simple'));
        }
        section.append(header);

        if (!table.rows.length) {
            section.append(element('p', 'small text-secondary mb-0', 'Sin registros para previsualizar.'));
        } else {
            const responsive = element('div', 'table-responsive');
            const tableElement = element('table', 'table table-sm table-hover align-middle mb-0');
            const thead = document.createElement('thead');
            const heading = document.createElement('tr');
            const selectHeading = element('th', '', 'Incluir');
            selectHeading.scope = 'col';
            const keyHeading = element('th', '', table.primary_key || 'Clave');
            keyHeading.scope = 'col';
            const previewHeading = element('th', '', 'Registro');
            previewHeading.scope = 'col';
            heading.append(selectHeading, keyHeading, previewHeading);
            thead.append(heading);
            const tbody = document.createElement('tbody');
            table.rows.forEach((row) => {
                const tr = document.createElement('tr');
                const checkboxCell = document.createElement('td');
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'form-check-input record-select';
                checkbox.setAttribute('aria-label', `Incluir ${table.name} ${row.key}`);
                checkbox.dataset.table = table.name;
                checkbox.dataset.key = row.key || '';
                checkbox.checked = selected.has('*') || selected.has(String(row.key));
                checkbox.dataset.selectable = table.selectable ? '1' : '0';
                checkbox.disabled = !table.selectable || row.key === null || selected.has('*');
                checkbox.addEventListener('change', () => {
                    const keys = selectedRecords.get(table.name) || new Set();
                    checkbox.checked ? keys.add(String(row.key)) : keys.delete(String(row.key));
                    selectedRecords.set(table.name, keys);
                    updateSelectionCount();
                });
                checkboxCell.append(checkbox);

                const keyCell = element('td', 'text-nowrap', row.key ?? 'No disponible');
                const valueCell = document.createElement('td');
                const details = document.createElement('details');
                details.append(element('summary', 'small text-primary', 'Ver contenido'));
                const pre = element('pre', 'small bg-light border rounded p-2 mt-2 mb-0');
                pre.textContent = JSON.stringify(row.values, null, 2);
                details.append(pre);
                valueCell.append(details);
                tr.append(checkboxCell, keyCell, valueCell);
                tbody.append(tr);
            });

            tableElement.append(thead, tbody);
            responsive.append(tableElement);
            section.append(responsive);
        }

        if (table.pages > 1) {
            const pager = element('div', 'd-flex align-items-center justify-content-end gap-2 mt-2');
            const previous = element('button', 'btn btn-sm btn-outline-secondary', 'Anterior');
            previous.type = 'button';
            previous.disabled = table.page <= 1;
            previous.addEventListener('click', () => loadTablePage(table.name, table.page - 1, section));
            const next = element('button', 'btn btn-sm btn-outline-secondary', 'Siguiente');
            next.type = 'button';
            next.disabled = table.page >= table.pages;
            next.addEventListener('click', () => loadTablePage(table.name, table.page + 1, section));
            pager.append(previous, next);
            section.append(pager);
        }
        return section;
    }

    async function loadTablePage(tableName, page, section) {
        clearAlert();
        const controls = section.querySelectorAll('button');
        controls.forEach((button) => { button.disabled = true; });
        try {
            const result = await postEncrypted('<?= site_url('admin/update/table-preview') ?>', {
                module_id: moduleSelect.value,
                table: tableName,
                page: String(page),
            });
            const updated = result.table;
            analysis.tables = analysis.tables.map((table) => table.name === tableName ? updated : table);
            section.replaceWith(renderTableSection(updated));
            updateSelectionCount();
        } catch (error) {
            showAlert(error.message);
            controls.forEach((button) => { button.disabled = false; });
        }
    }

    function updateSelectionCount() {
        const total = selectionCount();
        const max = analysis?.max_selected_records || 500;
        document.getElementById('toAttachmentsBtn').disabled = !analysis || total > max;
        tablePreview.querySelectorAll('.table-select-all:not(:checked)').forEach((checkbox) => {
            checkbox.disabled = checkbox.dataset.selectable !== '1' || total >= max;
        });
        tablePreview.querySelectorAll('.record-select:not(:checked)').forEach((checkbox) => {
            checkbox.disabled = checkbox.dataset.selectable !== '1' || checkbox.dataset.key === '' || total >= max;
        });
        if (total > max) showAlert(`Selecciona como máximo ${max} registros.`);
        else clearAlert();
    }

    function renderScan(data) {
        attachmentPreview.replaceChildren();
        const list = document.createElement('ul');
        list.className = 'list-group list-group-flush';
        data.files.forEach((file) => {
            const item = element('li', 'list-group-item d-flex flex-wrap justify-content-between gap-2');
            item.append(element('code', 'text-break', file.path));
            item.append(element('span', 'small text-secondary', `${file.size} bytes`));
            list.append(item);
        });
        if (data.files.length) attachmentPreview.append(list);
        else attachmentPreview.append(element('p', 'text-secondary', 'No se encontraron archivos físicos asociados a los registros seleccionados.'));

        if (data.missing_files.length) {
            const warning = element('div', 'alert alert-danger mt-3');
            warning.append(element('strong', '', 'Hay referencias cuyo archivo no existe.'));
            const missingList = document.createElement('ul');
            missingList.className = 'mb-0 mt-2';
            data.missing_files.forEach((file) => missingList.append(element('li', 'text-break', `${file.path} (${file.table}.${file.column})`)));
            warning.append(missingList);
            attachmentPreview.append(warning);
        }
        if (data.unresolved_files.length) {
            const warning = element('div', 'alert alert-warning mt-3');
            warning.append(element('strong', '', 'Hay referencias locales fuera de las rutas autorizadas.'));
            const unresolvedList = document.createElement('ul');
            unresolvedList.className = 'mb-0 mt-2';
            data.unresolved_files.forEach((file) => unresolvedList.append(element('li', 'text-break', `${file.path} (${file.table}.${file.column})`)));
            warning.append(unresolvedList);
            attachmentPreview.append(warning);
        }
        attachmentStatus.textContent = `${data.record_count} registros seleccionados; ${data.files.length} archivos encontrados.`;
        document.getElementById('toSummaryBtn').disabled = data.missing_files.length > 0 || data.unresolved_files.length > 0;
    }

    function renderSummary() {
        summary.replaceChildren();
        const selected = selection();
        const selectedTables = Object.keys(selected).length;
        const records = selectionCount();
        const summaryTable = document.createElement('table');
        summaryTable.className = 'table table-sm';
        const tbody = document.createElement('tbody');
        [
            ['Módulo', analysis.module.label || analysis.module.name],
            ['Tablas seleccionadas', selectedTables],
            ['Registros seleccionados', records],
            ['Archivos físicos', scan.files.length],
            ['Migraciones incluidas', analysis.migrations],
            ['Modo de datos', analysis.update_only ? 'Actualización de existentes' : 'Insertar y omitir duplicados'],
        ].forEach(([label, value]) => {
            const row = document.createElement('tr');
            row.append(element('th', 'w-50', label), element('td', '', value));
            tbody.append(row);
        });
        summaryTable.append(tbody);
        summary.append(summaryTable);
        if (analysis.update_only) {
            summary.append(element('div', 'alert alert-info', 'Este paquete solo actualiza registros que ya existen en el destino. Los registros faltantes se omiten; no se crean configuraciones iniciales.'));
        }

        if (selectedTables) {
            const selectedList = document.createElement('ul');
            selectedList.className = 'small';
            Object.entries(selected).forEach(([tableName, keys]) => {
                const table = analysis.tables.find((item) => item.name === tableName);
                const count = keys[0] === '*' ? Number(table?.total_rows || 0) : keys.length;
                selectedList.append(element('li', '', `${tableName}: ${count.toLocaleString()} registros`));
            });
            summary.append(element('h4', 'h6 mt-3', 'Tablas y registros'), selectedList);
        }

        const migrationList = document.createElement('ul');
        migrationList.className = 'small';
        (analysis.migration_files || []).forEach((migration) => migrationList.append(element('li', '', migration)));
        summary.append(element('h4', 'h6 mt-3', 'Migraciones'), migrationList);

        if (scan.files.length) {
            const fileList = document.createElement('ul');
            fileList.className = 'small';
            scan.files.forEach((file) => fileList.append(element('li', '', `${file.path} (${file.size} bytes)`)));
            summary.append(element('h4', 'h6 mt-3', 'Archivos físicos'), fileList);
        } else {
            summary.append(element('p', 'small text-secondary', 'Sin archivos físicos asociados.'));
        }
        generateButton.disabled = scan.missing_files.length > 0 || scan.unresolved_files.length > 0 || !document.getElementById('confirmSensitivePackage').checked;
    }

    analyzeButton?.addEventListener('click', async () => {
        clearAlert();
        analyzeButton.disabled = true;
        analyzeButton.textContent = 'Analizando…';
        tablePreview.replaceChildren();
        analysis = null;
        scan = null;
        try {
            const result = await postEncrypted(wizard.dataset.analyzeUrl, { module_id: moduleSelect.value });
            analysis = result.analysis;
            renderAnalysis(analysis);
        } catch (error) {
            showAlert(error.message);
        } finally {
            analyzeButton.disabled = false;
            analyzeButton.innerHTML = '<i class="bi bi-search me-1" aria-hidden="true"></i>Analizar módulo';
        }
    });

    async function scanAttachments() {
        clearAlert();
        attachmentButton.disabled = true;
        document.getElementById('toSummaryBtn').disabled = true;
        attachmentStatus.textContent = 'Escaneando…';
        try {
            const result = await postEncrypted(wizard.dataset.attachmentsUrl, {
                module_id: moduleSelect.value,
                selection: JSON.stringify(selection()),
            });
            scan = result.scan;
            renderScan(scan);
        } catch (error) {
            attachmentStatus.textContent = '';
            showAlert(error.message);
        } finally {
            attachmentButton.disabled = false;
        }
    }

    document.getElementById('toAttachmentsBtn')?.addEventListener('click', async () => {
        if (!analysis) return;
        setStep(2);
        await scanAttachments();
    });
    attachmentButton?.addEventListener('click', scanAttachments);
    document.getElementById('toSummaryBtn')?.addEventListener('click', () => {
        if (!scan || scan.missing_files.length || scan.unresolved_files.length) return;
        renderSummary();
        setStep(3);
    });
    document.querySelectorAll('[data-step-back]').forEach((button) => button.addEventListener('click', () => setStep(Number(button.dataset.stepBack))));

    generateButton?.addEventListener('click', async () => {
        clearAlert();
        generateButton.disabled = true;
        const body = new FormData();
        body.append('module_id', moduleSelect.value);
        body.append('selection', JSON.stringify(selection()));
        body.append(window.AdminCipher.csrfName, window.AdminCipher.csrfToken);
        try {
            const response = await fetch(wizard.dataset.exportUrl, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': window.AdminCipher.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            });
            const contentType = response.headers.get('content-type') || '';
            const contentDisposition = response.headers.get('content-disposition') || '';
            const isZip = /application\/(?:x-)?zip(?:-compressed)?/i.test(contentType)
                || (/application\/octet-stream/i.test(contentType) && /\.zip(?:[";]|$)/i.test(contentDisposition));
            if (!response.ok || !isZip) {
                let message = `No se recibió un ZIP válido (HTTP ${response.status}, ${contentType || 'sin Content-Type'}).`;
                if (/application\/json/i.test(contentType)) {
                    const envelope = await response.json();
                    const decoded = await decryptEnvelope(envelope);
                    message = decoded?.data?.message || message;
                }
                throw new Error(message);
            }
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            const filenameMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)|filename="?([^";]+)/i);
            const responseFilename = filenameMatch?.[1] || filenameMatch?.[2];
            link.download = responseFilename ? decodeURIComponent(responseFilename) : `${analysis.module.id}-module-package.zip`;
            link.click();
            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
            showAlert('El paquete se generó correctamente.', 'success');
        } catch (error) {
            showAlert(error.message);
        } finally {
            generateButton.disabled = !document.getElementById('confirmSensitivePackage').checked;
        }
    });
    document.getElementById('confirmSensitivePackage')?.addEventListener('change', (event) => {
        generateButton.disabled = !event.target.checked || !scan || scan.missing_files.length > 0 || scan.unresolved_files.length > 0;
    });
})();
</script>
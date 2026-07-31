// SNI Public Map - Cesium Integration
(function() {
    'use strict';
    console.log('[SNI] Script initializing');
    
    const config = window.PortalConfig || {};
    const sniText = config.sni || {};
    const endpoint = config.endpoints?.sniGeometrias;
    let viewer = null;
    const legendState = {
        entities: [],
        activeSlugs: new Set(),
        allSlugs: new Set(),
        activeSystems: new Set(),
        activePairs: new Set(),
        collapsedSystems: new Set(),
    };
    
    // Expose globally for debugging
    window.__sniDebug = {
        getEntities: () => viewer ? viewer.entities.values.length : 'No viewer',
        getViewer: () => viewer
    };
    
    // ============== UTILITIES ==============
    
    function log(msg) {
        console.log('[SNI] ' + msg);
    }
    
    function status(msg) {
        const el = document.getElementById('sniStatusMessage');
        if (el) el.textContent = msg || '';
        if (msg) log('Status: ' + msg);
    }
    
    function b64ToBytes(b64) {
        const bin = atob(b64);
        const bytes = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        return bytes;
    }
    
    function concatBytes(a, b) {
        const m = new Uint8Array(a.length + b.length);
        m.set(a, 0);
        m.set(b, a.length);
        return m;
    }
    
    async function getCrypto() {
        let mat = config.cipherKey || '';
        if (mat.startsWith('base64:')) mat = b64ToBytes(mat.substring(7));
        else mat = new TextEncoder().encode(mat);
        
        if (mat.length !== 32) {
            mat = new Uint8Array(await crypto.subtle.digest('SHA-256', mat));
        }
        
        return {
            aesKey: await crypto.subtle.importKey('raw', mat, 'AES-CBC', false, ['decrypt']),
            hmacKey: await crypto.subtle.importKey('raw', mat, { name: 'HMAC', hash: 'SHA-256' }, false, ['verify'])
        };
    }
    
    async function decrypt(env) {
        if (!env?.encrypted || !env.payload) return env || {};
        
        const bytes = b64ToBytes(String(env.payload));
        if (bytes.length <= 48) throw new Error('Payload too short');
        
        const iv = bytes.slice(0, 16);
        const mac = bytes.slice(16, 48);
        const cipher = bytes.slice(48);
        const { aesKey, hmacKey } = await getCrypto();
        
        const valid = await crypto.subtle.verify('HMAC', hmacKey, mac, concatBytes(iv, cipher));
        if (!valid) throw new Error('MAC invalid');
        
        const plain = await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, aesKey, cipher);
        return JSON.parse(new TextDecoder().decode(plain));
    }
    
    // ============== VIEWER SETUP ==============
    
    function setupViewer() {
        log('Creating viewer with basemap');
        
        viewer = new Cesium.Viewer('sniMapCanvas', {
            sceneMode: Cesium.SceneMode.SCENE2D,
            animation: false,
            timeline: false,
            geocoder: false,
            homeButton: true,
            fullscreenButton: true,
            sceneModePicker: true,
            baseLayerPicker: false,
            navigationHelpButton: false,
            navigationInstructionsInitiallyVisible: false,
            infoBox: true,
            selectionIndicator: true,
        });
        
        // Use OpenStreetMap imagery
        viewer.imageryLayers.removeAll();
        viewer.imageryLayers.addImageryProvider(
            new Cesium.OpenStreetMapImageryProvider({
                url: 'https://a.tile.openstreetmap.org/'
            })
        );
        
        // Set initial 2D view to Guatemala area
        viewer.camera.setView({
            destination: Cesium.Rectangle.fromDegrees(-92.5, 13.2, -88.0, 18.2)
        });
        
        log('Viewer created with OSM imagery');
    }
    
    // ============== GEOMETRY RENDERING ==============
    
    function getVoltage(props) {
        const v = Number(props.nivel_kv || props.voltaje_kv || 0);
        if ([69, 138, 230].includes(v)) return v;
        return 0;
    }
    
    function getColor(props) {
        const v = getVoltage(props);
        if (v === 69) return '#2e7d32';
        if (v === 138) return '#c62828';
        if (v === 230) return '#1565c0';
        return '#455a64';
    }
    
    function toColor(hex, alpha) {
        try {
            return Cesium.Color.fromCssColorString(hex || '#1f6feb').withAlpha(alpha || 1);
        } catch {
            return Cesium.Color.GRAY.withAlpha(alpha || 1);
        }
    }
    
    function toCartesians(coords) {
        const flat = [];
        (coords || []).forEach(c => {
            if (Array.isArray(c) && c.length >= 2) {
                flat.push(Number(c[0]), Number(c[1]));
            }
        });
        return flat.length >= 4 ? Cesium.Cartesian3.fromDegreesArray(flat) : null;
    }

    function buildLegendFromFeatures(features) {
        const map = new Map();

        (features || []).forEach((f) => {
            const props = f?.properties || {};
            const slug = String(props.capa_slug || '').trim();

            if (slug === '' || map.has(slug)) {
                return;
            }

            map.set(slug, {
                slug,
                nombre: String(props.capa_nombre || slug),
                color_default: String(props.color_default || '#455a64'),
                icono_url: String(props.icono_url || ''),
            });
        });

        return Array.from(map.values());
    }

    function resolveSystemInfo(props) {
        const rawId = Number(props.linea_sistema_id || 0);
        const safeId = Number.isFinite(rawId) && rawId > 0 ? rawId : 0;
        const name = String(props.linea_sistema_nombre || '').trim() || 'Sin linea de sistema';

        return {
            key: safeId > 0 ? ('ls-' + safeId) : 'ls-0',
            name,
        };
    }

    function buildLegendHierarchy(symbols, features) {
        const symbolMap = new Map();
        const systemsMap = new Map();

        (Array.isArray(symbols) ? symbols : []).forEach((s) => {
            const slug = String(s.slug || '').trim();
            if (slug === '') return;

            symbolMap.set(slug, {
                slug,
                nombre: String(s.nombre || slug),
                color_default: String(s.color_default || '#455a64'),
                icono_url: String(s.icono_url || ''),
            });
        });

        (Array.isArray(features) ? features : []).forEach((f) => {
            const props = f?.properties || {};
            const slug = String(props.capa_slug || '').trim();

            if (slug === '') return;

            if (!symbolMap.has(slug)) {
                symbolMap.set(slug, {
                    slug,
                    nombre: String(props.capa_nombre || slug),
                    color_default: String(props.color_default || '#455a64'),
                    icono_url: String(props.icono_url || ''),
                });
            }

            const system = resolveSystemInfo(props);

            if (!systemsMap.has(system.key)) {
                systemsMap.set(system.key, {
                    key: system.key,
                    nombre: system.name,
                    items: new Map(),
                });
            }

            const systemEntry = systemsMap.get(system.key);

            if (!systemEntry.items.has(slug)) {
                systemEntry.items.set(slug, symbolMap.get(slug));
            }
        });

        return Array.from(systemsMap.values())
            .map((system) => ({
                key: system.key,
                nombre: system.nombre,
                items: Array.from(system.items.values()).sort((a, b) => a.nombre.localeCompare(b.nombre, 'es')),
            }))
            .sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));
    }

    function applyLegendFilter() {
        legendState.entities.forEach((entity) => {
            const slug = String(entity.sniCapaSlug || '');
            const systemKey = String(entity.sniSystemKey || 'ls-0');
            const pairKey = systemKey + '|' + slug;

            entity.show = slug === '' || (
                legendState.activeSystems.has(systemKey)
                && legendState.activePairs.has(pairKey)
            );
        });
    }

    function toggleLegendSlug(slug, button) {
        if (!slug) return;

        if (legendState.activeSlugs.has(slug)) {
            legendState.activeSlugs.delete(slug);
            if (button) button.classList.add('is-off');
        } else {
            legendState.activeSlugs.add(slug);
            if (button) button.classList.remove('is-off');
        }

        applyLegendFilter();
    }

    function toggleLegendPair(systemKey, slug, button) {
        if (!systemKey || !slug) return;

        const pairKey = systemKey + '|' + slug;

        if (legendState.activePairs.has(pairKey)) {
            legendState.activePairs.delete(pairKey);
            if (button) button.classList.add('is-off');
        } else {
            legendState.activePairs.add(pairKey);
            legendState.activeSystems.add(systemKey);
            if (button) button.classList.remove('is-off');
        }

        applyLegendFilter();
    }

    function toggleLegendSystem(systemKey, button) {
        if (!systemKey) return;

        if (legendState.activeSystems.has(systemKey)) {
            legendState.activeSystems.delete(systemKey);
            if (button) button.classList.add('is-off');
        } else {
            legendState.activeSystems.add(systemKey);
            if (button) button.classList.remove('is-off');
        }

        applyLegendFilter();
    }

    function toggleSystemCollapse(systemKey, wrapper, button) {
        if (!systemKey || !wrapper) return;

        if (legendState.collapsedSystems.has(systemKey)) {
            legendState.collapsedSystems.delete(systemKey);
            wrapper.classList.remove('is-collapsed');
            if (button) button.textContent = '-';
            return;
        }

        legendState.collapsedSystems.add(systemKey);
        wrapper.classList.add('is-collapsed');
        if (button) button.textContent = '+';
    }

    function renderLegend(symbols, features) {
        const list = document.getElementById('sniLegendList');

        if (!list) return;

        const normalized = buildLegendHierarchy(symbols, features);

        list.innerHTML = '';
        legendState.allSlugs.clear();
        legendState.activeSlugs.clear();
        legendState.activeSystems.clear();
        legendState.activePairs.clear();
        legendState.collapsedSystems.clear();

        if (!Array.isArray(normalized) || normalized.length === 0) {
            list.innerHTML = '<div class="small text-secondary">Sin simbologias activas.</div>';
            return;
        }

        normalized.forEach((system) => {
            const systemKey = String(system.key || '').trim();
            if (systemKey === '') return;

            legendState.activeSystems.add(systemKey);

            const wrapper = document.createElement('div');
            wrapper.className = 'sni-legend-system';

            const systemBtn = document.createElement('button');
            systemBtn.type = 'button';
            systemBtn.className = 'sni-legend-system-header';
            systemBtn.innerHTML = '<span class="sni-legend-system-title">' + String(system.nombre || 'Sin linea de sistema') + '</span>'
                + '<span class="sni-legend-system-tools">'
                + '<span class="badge text-bg-light border">' + (Array.isArray(system.items) ? system.items.length : 0) + '</span>'
                + '<button type="button" class="sni-legend-system-collapse" title="Expandir/Colapsar">+</button>'
                + '</span>';
            systemBtn.addEventListener('click', () => toggleLegendSystem(systemKey, systemBtn));

            const collapseBtn = systemBtn.querySelector('.sni-legend-system-collapse');
            if (collapseBtn) {
                collapseBtn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    toggleSystemCollapse(systemKey, wrapper, collapseBtn);
                });
            }

            wrapper.appendChild(systemBtn);

            const children = document.createElement('div');
            children.className = 'sni-legend-system-children';

            (Array.isArray(system.items) ? system.items : []).forEach((s) => {
                const slug = String(s.slug || '').trim();
                if (slug === '') return;

                const name = String(s.nombre || slug);
                const color = String(s.color_default || '#455a64');
                const iconUrl = String(s.icono_url || '');
                const pairKey = systemKey + '|' + slug;

                legendState.allSlugs.add(slug);
                legendState.activeSlugs.add(slug);
                legendState.activePairs.add(pairKey);

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'sni-legend-item sni-legend-toggle sni-legend-child';
                btn.dataset.slug = slug;
                btn.dataset.systemKey = systemKey;

                btn.innerHTML = iconUrl !== ''
                    ? '<img class="sni-legend-icon" src="' + iconUrl + '" alt="" loading="lazy">'
                        + '<span>' + name + '</span>'
                    : '<span class="sni-line-chip" style="background:' + color + ';"></span>'
                        + '<span>' + name + '</span>';

                btn.addEventListener('click', () => toggleLegendPair(systemKey, slug, btn));
                children.appendChild(btn);
            });

            wrapper.appendChild(children);

            // Start collapsed by default to keep large legends readable.
            if (collapseBtn) {
                toggleSystemCollapse(systemKey, wrapper, collapseBtn);
            }

            list.appendChild(wrapper);
        });
    }
    
    function addLine(f) {
        const pos = toCartesians(f.geometry?.coordinates || []);
        if (!pos) return;

        const props = f.properties || {};
        const entity = viewer.entities.add({
            name: String(f.properties?.nombre || 'Línea'),
            polyline: {
                positions: pos,
                width: 4,
                material: toColor(String(props.color_default || getColor(props)), 0.8),
                clampToGround: true,
            },
        });

        entity.sniCapaSlug = String(props.capa_slug || '');
        entity.sniSystemKey = resolveSystemInfo(props).key;
        legendState.entities.push(entity);
    }
    
    function addPoint(f) {
        const c = f.geometry?.coordinates || [];
        if (c.length < 2) return;

        const props = f.properties || {};
        const pointColor = String(props.color_default || '#f59f00');
        const pointIcon = String(props.icono_url || '').trim();
        const fallbackIcon = 'data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Ccircle%20cx%3D%2216%22%20cy%3D%2216%22%20r%3D%2210%22%20fill%3D%22'
            + encodeURIComponent(pointColor)
            + '%22%2F%3E%3C%2Fsvg%3E';

        const entity = viewer.entities.add({
            name: String(f.properties?.nombre || 'Subestación'),
            position: Cesium.Cartesian3.fromDegrees(Number(c[0]), Number(c[1]), Number(c[2] || 0)),
            billboard: {
                image: pointIcon !== '' ? pointIcon : fallbackIcon,
                width: 24,
                height: 24,
                verticalOrigin: Cesium.VerticalOrigin.BOTTOM,
            },
        });

        entity.sniCapaSlug = String(props.capa_slug || '');
        entity.sniSystemKey = resolveSystemInfo(props).key;
        legendState.entities.push(entity);
    }
    
    function addPolygon(f) {
        const rings = f.geometry?.coordinates || [];
        if (!Array.isArray(rings[0])) return;

        const props = f.properties || {};
        const fill = String(props.color_default || getColor(props));
        const pos = toCartesians(rings[0]);
        if (!pos) return;

        const entity = viewer.entities.add({
            name: String(f.properties?.nombre || 'Área'),
            polygon: {
                hierarchy: new Cesium.PolygonHierarchy(pos),
                material: toColor(fill, 0.4),
                outline: true,
                outlineColor: toColor(fill, 0.8),
            },
        });

        entity.sniCapaSlug = String(props.capa_slug || '');
        entity.sniSystemKey = resolveSystemInfo(props).key;
        legendState.entities.push(entity);
    }
    
    function addFeature(f) {
        const type = String(f.geometry?.type || '');
        if (type === 'LineString') addLine(f);
        else if (type === 'Point') addPoint(f);
        else if (type === 'Polygon') addPolygon(f);
    }
    
    // ============== DATA LOADING ==============
    
    async function loadData() {
        try {
            log('Fetching data from: ' + endpoint);
            status('Cargando geometrías...');
            
            const resp = await fetch(endpoint, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            
            const raw = await resp.json();
            log('Raw data received, payload length: ' + (raw.payload ? raw.payload.length : 0));
            
            const payload = await decrypt(raw);
            log('Payload decrypted, ok=' + payload.ok);
            
            if (!payload?.ok) {
                throw new Error(payload?.message || 'Invalid payload');
            }

            const data = payload.data || {};
            const features = Array.isArray(data.features) ? data.features : [];
            const symbols = Array.isArray(data.simbologias) ? data.simbologias : [];
            log('Features loaded: ' + features.length);

            viewer.entities.removeAll();
            legendState.entities = [];
            
            features.forEach((f, i) => {
                log('Adding feature ' + (i + 1) + ': ' + (f.properties?.nombre || 'unnamed'));
                addFeature(f);
            });

            renderLegend(symbols, features);
            applyLegendFilter();

            if (features.length === 0) {
                status(sniText.noData || 'Sin datos disponibles');
                log('No features to display');
                return;
            }
            
            log('Entities added: ' + viewer.entities.values.length);
            
            if (viewer.entities.values.length > 0) {
                log('Zooming to entities...');
                try {
                        viewer.zoomTo(viewer.entities).then(function() {
                        log('Zoom completed successfully');
                    }).catch(function(err) {
                        log('zoomTo failed: ' + err.message + ', using fallback');
                            if (viewer.scene.mode === Cesium.SceneMode.SCENE2D) {
                                viewer.camera.setView({
                                    destination: Cesium.Rectangle.fromDegrees(-92.5, 13.2, -88.0, 18.2)
                                });
                            } else {
                                viewer.camera.setView({
                                    destination: Cesium.Cartesian3.fromDegrees(-90.72, 14.3, 5000),
                                    orientation: {
                                        heading: 0,
                                        pitch: Cesium.Math.toRadians(-45),
                                        roll: 0
                                    }
                                });
                            }
                    });
                } catch (e) {
                    log('Zoom attempt failed: ' + e.message);
                        if (viewer.scene.mode === Cesium.SceneMode.SCENE2D) {
                            viewer.camera.setView({
                                destination: Cesium.Rectangle.fromDegrees(-92.5, 13.2, -88.0, 18.2)
                            });
                        } else {
                            viewer.camera.setView({
                                destination: Cesium.Cartesian3.fromDegrees(-90.72, 14.3, 5000),
                                orientation: {
                                    heading: 0,
                                    pitch: Cesium.Math.toRadians(-45),
                                    roll: 0
                                }
                            });
                        }
                }
            }
            
            status('');
            log('Data loading complete');
            
        } catch (err) {
            log('Data error: ' + err.message);
            console.error('[SNI] Full error:', err);
            status(sniText.loadError || 'Error cargando datos');
        }
    }
    
    // ============== BUTTONS ==============
    
    function setupButtons() {
        const btn2d = document.getElementById('sniMode2dBtn');
        const btn3d = document.getElementById('sniMode3dBtn');
        const reload = document.getElementById('sniReloadBtn');
        const zoomIn = document.getElementById('sniZoomInBtn');
        const zoomOut = document.getElementById('sniZoomOutBtn');
        let pendingModeFocus = null;

        function focusGuatemala2D() {
            viewer.camera.setView({
                // In 2D, Rectangle gives stable center/scale across morph transitions.
                destination: Cesium.Rectangle.fromDegrees(-92.5, 13.2, -88.0, 18.2)
            });
            log('2D focus set to Guatemala');
        }

        function focusGuatemala3D() {
            viewer.camera.setView({
                destination: Cesium.Cartesian3.fromDegrees(-90.72, 14.3, 5000),
                orientation: {
                    heading: 0,
                    pitch: Cesium.Math.toRadians(-45),
                    roll: 0
                }
            });
            log('3D focus set to Guatemala');
        }

        viewer.scene.morphComplete.addEventListener(() => {
            try {
                if (pendingModeFocus === '2d' && viewer.scene.mode === Cesium.SceneMode.SCENE2D) {
                    focusGuatemala2D();
                }
                if (pendingModeFocus === '3d' && viewer.scene.mode === Cesium.SceneMode.SCENE3D) {
                    focusGuatemala3D();
                }
            } catch (err) {
                log('Morph complete focus error: ' + err.message);
                console.error('[SNI] Morph complete focus error:', err);
            } finally {
                pendingModeFocus = null;
            }
        });

        if (btn2d) {
            btn2d.addEventListener('click', () => {
                pendingModeFocus = '2d';
                viewer.scene.morphTo2D(1);
            });
        }
        
        if (btn3d) {
            btn3d.addEventListener('click', () => {
                pendingModeFocus = '3d';
                viewer.scene.morphTo3D(1);
            });
        }
        
        if (reload) {
            reload.addEventListener('click', () => {
                loadData();
            });
        }
        
        if (zoomIn) {
            zoomIn.addEventListener('click', () => {
                try {
                    const camera = viewer.camera;
                    if (viewer.scene.mode === Cesium.SceneMode.SCENE2D) {
                        camera.zoomIn();
                        log('Zoom in (2D native)');
                        return;
                    }
                    const cartographic = Cesium.Cartographic.fromCartesian(camera.position);
                    const newHeight = Math.max(cartographic.height / 1.5, 100);
                    
                    camera.setView({
                        destination: Cesium.Cartesian3.fromRadians(
                            cartographic.longitude,
                            cartographic.latitude,
                            newHeight
                        ),
                        orientation: {
                            heading: camera.heading,
                            pitch: camera.pitch,
                            roll: camera.roll
                        },
                        duration: 0.5
                    });
                    
                    log('Zoom in: ' + (cartographic.height / 1000).toFixed(1) + 'km');
                } catch (err) {
                    log('Zoom in error: ' + err.message);
                    console.error('[SNI] Zoom in error:', err);
                }
            });
        }
        
        if (zoomOut) {
            zoomOut.addEventListener('click', () => {
                try {
                    const camera = viewer.camera;
                    if (viewer.scene.mode === Cesium.SceneMode.SCENE2D) {
                        camera.zoomOut();
                        log('Zoom out (2D native)');
                        return;
                    }
                    const cartographic = Cesium.Cartographic.fromCartesian(camera.position);
                    const newHeight = cartographic.height * 1.5;
                    
                    camera.setView({
                        destination: Cesium.Cartesian3.fromRadians(
                            cartographic.longitude,
                            cartographic.latitude,
                            newHeight
                        ),
                        orientation: {
                            heading: camera.heading,
                            pitch: camera.pitch,
                            roll: camera.roll
                        },
                        duration: 0.5
                    });
                    
                    log('Zoom out: ' + (cartographic.height / 1000).toFixed(1) + 'km');
                } catch (err) {
                    log('Zoom out error: ' + err.message);
                    console.error('[SNI] Zoom out error:', err);
                }
            });
        }
        
        log('Buttons setup complete');
    }
    
    // ============== INIT ==============
    
    function init() {
        log('Init called');
        
        if (!window.Cesium) {
            status('Cesium not available');
            return;
        }
        
        if (!endpoint) {
            status('No endpoint configured');
            return;
        }
        
        try {
            setupViewer();
            setupButtons();
            loadData();
            log('Init complete');
        } catch (err) {
            log('Fatal error: ' + err.message);
            status(sniText.loadError || 'Error initializing map');
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

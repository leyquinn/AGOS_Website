import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const DEFAULT_BOUNDARY_URL = '/data/davao-barangays.geojson';
const DAVAO_CENTER = [7.0731, 125.6128];
const defaultHeatPoints = [
    [7.0731, 125.6128, 0.92], [7.0668, 125.6082, 0.8], [7.0814, 125.6049, 0.66],
    [7.0534, 125.5904, 0.72], [7.0458, 125.6185, 0.86], [7.0972, 125.6324, 0.78],
    [7.1141, 125.6458, 0.55], [7.0288, 125.6534, 0.62], [7.0153, 125.5937, 0.48],
    [7.1283, 125.6088, 0.71], [7.1573, 125.6562, 0.44], [7.0042, 125.6356, 0.74],
];

const getFeatureName = (feature) => {
    const properties = feature?.properties ?? {};
    return String(
        properties.name
        ?? properties.NAME
        ?? properties.Name
        ?? properties.barangay
        ?? properties.barangay_name
        ?? properties.BRGY_NAME
        ?? 'Unnamed barangay',
    ).trim();
};

const pointInRing = (point, ring) => {
    const isOnSegment = (start, end) => {
        const crossProduct = ((point.lat - start[1]) * (end[0] - start[0]))
            - ((point.lng - start[0]) * (end[1] - start[1]));
        if (Math.abs(crossProduct) > 1e-10) return false;

        return point.lng >= Math.min(start[0], end[0])
            && point.lng <= Math.max(start[0], end[0])
            && point.lat >= Math.min(start[1], end[1])
            && point.lat <= Math.max(start[1], end[1]);
    };

    let inside = false;
    for (let index = 0, previous = ring.length - 1; index < ring.length; previous = index++) {
        const [longitude, latitude] = ring[index];
        const [previousLongitude, previousLatitude] = ring[previous];
        if (isOnSegment([longitude, latitude], [previousLongitude, previousLatitude])) return true;
        const intersects = ((latitude > point.lat) !== (previousLatitude > point.lat))
            && point.lng < ((previousLongitude - longitude) * (point.lat - latitude)) / (previousLatitude - latitude) + longitude;
        if (intersects) inside = !inside;
    }
    return inside;
};

const pointInPolygon = (point, polygon) => {
    if (!pointInRing(point, polygon[0])) return false;
    return !polygon.slice(1).some((hole) => pointInRing(point, hole));
};

const pointInGeometry = (point, geometry) => {
    if (!geometry) return false;
    if (geometry.type === 'Polygon') return pointInPolygon(point, geometry.coordinates);
    if (geometry.type === 'MultiPolygon') return geometry.coordinates.some((polygon) => pointInPolygon(point, polygon));
    return false;
};

const featureContainsPoint = (point, feature) => pointInGeometry(point, feature.geometry);
const pointsInsideFeatures = (points, features) => points.filter(([latitude, longitude]) => {
    const point = L.latLng(latitude, longitude);
    return features.some((feature) => featureContainsPoint(point, feature));
});

const normalizeHeatPoints = (points) => (Array.isArray(points) ? points : []).map((point) => (
    Array.isArray(point)
        ? point
        : [Number(point.latitude), Number(point.longitude), Number(point.intensity), point.barangay]
)).filter(([latitude, longitude]) => Number.isFinite(latitude) && Number.isFinite(longitude));

const boundaryPromises = new Map();
const loadBoundaryFeatures = async (boundaryUrl = DEFAULT_BOUNDARY_URL) => {
    if (window._davaoGeoJsonCache) {
        const geoJson = window._davaoGeoJsonCache;
        const features = geoJson.type === 'FeatureCollection'
            ? geoJson.features
            : [{ type: 'Feature', properties: {}, geometry: geoJson }];
        return { geoJson, features };
    }

    if (!boundaryPromises.has(boundaryUrl)) {
        boundaryPromises.set(boundaryUrl, (async () => {
            const response = await fetch(boundaryUrl, { headers: { Accept: 'application/geo+json, application/json' } });
            if (!response.ok) throw new Error(`Boundary request failed with status ${response.status}`);
            const geoJson = await response.json();
            window._davaoGeoJsonCache = geoJson;
            return geoJson;
        })().catch((error) => {
            boundaryPromises.delete(boundaryUrl);
            throw error;
        }));
    }

    const geoJson = await boundaryPromises.get(boundaryUrl);
    const features = geoJson.type === 'FeatureCollection'
        ? geoJson.features
        : [{ type: 'Feature', properties: {}, geometry: geoJson }];
    if (!features.length) throw new Error('Boundary GeoJSON contains no features.');
    return { geoJson, features };
};

const citizenMapPromises = new Map();
const fetchCitizenMapBundle = (reportsUrl) => {
    if (!citizenMapPromises.has(reportsUrl)) {
        citizenMapPromises.set(reportsUrl, (async () => {
            const response = await fetch(reportsUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) throw new Error(`Map reports failed with status ${response.status}`);
            return response.json();
        })().catch((error) => {
            citizenMapPromises.delete(reportsUrl);
            throw error;
        }));
    }
    return citizenMapPromises.get(reportsUrl);
};

const createHeatLayer = (map, points) => {
    const layer = L.Layer.extend({
        initialize(points) {
            this.points = points;
            this.activeFeatures = [];
        },
        onAdd(targetMap) {
            this._map = targetMap;
            this._canvas = L.DomUtil.create('canvas', 'agos-heatmap-canvas');
            this._canvas.setAttribute('aria-label', 'Flood intensity heatmap');
            this._canvas.style.pointerEvents = 'none';
            targetMap.getPanes().overlayPane.appendChild(this._canvas);
            targetMap.on('moveend zoom resize viewreset', this._reset, this);
            this._reset();
        },
        onRemove(targetMap) {
            targetMap.off('moveend zoom resize viewreset', this._reset, this);
            this._canvas?.remove();
        },
        setBoundary(features) {
            this.activeFeatures = features;
            this._draw();
        },
        setPoints(nextPoints) {
            this.points = nextPoints;
            this._draw();
        },
        _reset() {
            const size = this._map.getSize();
            this._topLeft = this._map.containerPointToLayerPoint([0, 0]);
            L.DomUtil.setPosition(this._canvas, this._topLeft);
            this._canvas.width = size.x;
            this._canvas.height = size.y;
            this._draw();
        },
        _clipPath(context) {
            this.activeFeatures.forEach((feature) => {
                const geometry = feature.geometry;
                const polygons = geometry.type === 'Polygon' ? [geometry.coordinates] : geometry.coordinates;
                polygons.forEach((polygon) => polygon.forEach((ring) => {
                    ring.forEach(([longitude, latitude], index) => {
                        const point = this._map.latLngToLayerPoint([latitude, longitude]).subtract(this._topLeft);
                        if (index === 0) context.moveTo(point.x, point.y);
                        else context.lineTo(point.x, point.y);
                    });
                    context.closePath();
                }));
            });
        },
        _draw() {
            if (!this._canvas || !this._map) return;
            const context = this._canvas.getContext('2d');
            context.clearRect(0, 0, this._canvas.width, this._canvas.height);
            if (this.activeFeatures.length) {
                context.save();
                context.beginPath();
                this._clipPath(context);
                context.clip('evenodd');
            }

            this.points.forEach(([latitude, longitude, intensity]) => {
                const point = this._map.latLngToLayerPoint([latitude, longitude]).subtract(this._topLeft);
                const radius = 46 + (intensity * 22);
                const gradient = context.createRadialGradient(point.x, point.y, 0, point.x, point.y, radius);
                gradient.addColorStop(0, `rgba(220, 38, 38, ${0.68 * intensity})`);
                gradient.addColorStop(0.35, `rgba(217, 119, 6, ${0.42 * intensity})`);
                gradient.addColorStop(0.7, `rgba(2, 132, 199, ${0.2 * intensity})`);
                gradient.addColorStop(1, 'rgba(2, 132, 199, 0)');
                context.fillStyle = gradient;
                context.fillRect(point.x - radius, point.y - radius, radius * 2, radius * 2);
            });
            if (this.activeFeatures.length) context.restore();
        },
    });

    return new layer(points).addTo(map);
};

const initOperationsMap = async () => {
    const mapElement = document.querySelector('[data-flood-map]');
    if (!mapElement) return;

    const map = L.map(mapElement, { preferCanvas: true }).setView(DAVAO_CENTER, 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
        keepBuffer: 2,
        updateWhenZooming: false,
    }).addTo(map);

    const status = document.querySelector('[data-map-status]');
    const selector = document.querySelector('[data-barangay-filter]');
    const boundaryUrl = mapElement.dataset.boundaryUrl || DEFAULT_BOUNDARY_URL;
    let boundaryLayer;
    let features = [];
    let heatPoints = defaultHeatPoints;
    let barangayCounts = {};
    try {
        heatPoints = JSON.parse(mapElement.dataset.heatPoints || 'null') || defaultHeatPoints;
        barangayCounts = JSON.parse(mapElement.dataset.barangayCounts || '{}') || {};
    } catch (error) {
        console.error('Unable to parse report heatmap points.', error);
    }
    heatPoints = normalizeHeatPoints(heatPoints);
    const heatLayer = createHeatLayer(map, heatPoints);
    const reportBarangays = [...new Set(heatPoints.map((point) => point[3]).filter(Boolean))].sort();
    const populateSelector = (names) => {
        if (!selector) return;
        selector.innerHTML = '<option value="">All barangays</option>';
        names.forEach((name) => {
            const count = barangayCounts[name] || 0;
            const label = count > 0 ? `${name} (${count} verified)` : name;
            selector.add(new Option(label, name));
        });
        selector.disabled = false;
    };
    const updateFallbackFilter = (event) => {
        const selectedName = event.target.value;
        const selectedPoints = selectedName ? heatPoints.filter((point) => point[3] === selectedName) : heatPoints;
        heatLayer.setPoints(selectedPoints);
        status.textContent = selectedName ? `Showing reports for ${selectedName}` : 'Showing all verified flood report locations';
    };

    try {
        const { geoJson, features: loadedFeatures } = await loadBoundaryFeatures(boundaryUrl);
        features = loadedFeatures;
        heatPoints = pointsInsideFeatures(heatPoints, features);
        heatLayer.setPoints(heatPoints);

        boundaryLayer = L.geoJSON(geoJson, {
            interactive: false,
            style: { color: '#38bdf8', weight: 1, opacity: 0.7, fillColor: '#0ea5e9', fillOpacity: 0.04 },
        }).addTo(map);
        map.fitBounds(boundaryLayer.getBounds(), { padding: [18, 18] });

        selector.innerHTML = '<option value="">All barangays</option>';
        features
            .map((feature) => getFeatureName(feature).replace(/\ufffd/g, 'ñ'))
            .sort((first, second) => {
                const c1 = barangayCounts[first] || 0;
                const c2 = barangayCounts[second] || 0;
                if ((c1 > 0) !== (c2 > 0)) return c2 - c1;
                if (c1 !== c2) return c2 - c1;
                return first.localeCompare(second);
            })
            .forEach((name) => {
                const count = barangayCounts[name] || 0;
                const label = count > 0 ? `🚨 ${name} (${count} verified)` : name;
                selector.add(new Option(label, name));
            });

        const updateBoundary = (selectedName = '') => {
            const activeFeatures = selectedName
                ? features.filter((feature) => getFeatureName(feature).replace(/\ufffd/g, 'ñ') === selectedName)
                : features;
            const activePoints = selectedName
                ? heatPoints.filter((pt) => pt[3] === selectedName || pointsInsideFeatures([pt], activeFeatures).length > 0)
                : heatPoints;
            boundaryLayer.setStyle((feature) => {
                const fname = getFeatureName(feature).replace(/\ufffd/g, 'ñ');
                const isSelected = fname === selectedName;
                const hasReports = (barangayCounts[fname] || 0) > 0;
                return {
                    color: isSelected ? '#fbbf24' : (hasReports ? '#22d3ee' : '#38bdf8'),
                    weight: isSelected ? 2.5 : (hasReports ? 1.4 : 0.8),
                    fillOpacity: isSelected ? 0.14 : (hasReports ? 0.07 : 0.02),
                };
            });
            heatLayer.setPoints(activePoints);
            heatLayer.setBoundary(activeFeatures);
            if (selectedName && activeFeatures.length) {
                map.fitBounds(L.geoJSON({ type: 'FeatureCollection', features: activeFeatures }).getBounds(), { padding: [30, 30] });
            } else {
                map.fitBounds(boundaryLayer.getBounds(), { padding: [18, 18] });
            }
            const selCount = selectedName ? (barangayCounts[selectedName] || activePoints.length) : Object.values(barangayCounts).reduce((a, b) => a + b, 0);
            status.textContent = selectedName
                ? `Showing ${selCount} verified flood report(s) clipped to Brgy. ${selectedName}`
                : `Showing ${selCount.toLocaleString()} verified flood reports across ${Object.keys(barangayCounts).length} Davao City barangays`;
        };

        selector.addEventListener('change', (event) => updateBoundary(event.target.value));
        updateBoundary();
    } catch (error) {
        console.error('Unable to load Davao barangay boundaries.', error);
        status.textContent = 'Barangay boundaries could not be loaded. Add the GeoJSON file to public/data.';
        populateSelector(reportBarangays);
        selector?.addEventListener('change', updateFallbackFilter);
    }
};

const initCitizenHomeMap = async () => {
    const mapElement = document.querySelector('[data-citizen-map]');
    if (!mapElement) return;

    const map = L.map(mapElement, { zoomControl: false, preferCanvas: true }).setView(DAVAO_CENTER, 12);
    L.control.zoom({ position: 'bottomright' }).addTo(map);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
        keepBuffer: 2,
        updateWhenZooming: false,
    }).addTo(map);

    const statusBadge = document.querySelector('[data-citizen-heatmap-status]');
    const boundaryUrl = mapElement.dataset.boundaryUrl || DEFAULT_BOUNDARY_URL;
    const heatLayer = createHeatLayer(map, []);
    const markers = L.layerGroup().addTo(map);
    let boundaryFeatures = [];
    let rawHeatPoints = [];

    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));
    const iconFor = (severity) => L.divIcon({
        className: 'citizen-live-marker',
        html: `<span class="marker-${severity}"></span>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7],
    });

    const syncHeatmapPoints = () => {
        const points = boundaryFeatures.length
            ? pointsInsideFeatures(rawHeatPoints, boundaryFeatures)
            : rawHeatPoints;
        heatLayer.setPoints(points);
        if (boundaryFeatures.length) {
            heatLayer.setBoundary(boundaryFeatures);
        }
    };

    const syncMapLayout = () => {
        if (mapElement.offsetWidth <= 0 || mapElement.offsetHeight <= 0) return;
        map.invalidateSize();
        heatLayer._reset();
    };

    const loadBoundaries = async () => {
        try {
            const { geoJson, features } = await loadBoundaryFeatures(boundaryUrl);
            boundaryFeatures = features;
            const boundaryLayer = L.geoJSON(geoJson, {
                interactive: false,
                style: { color: '#38bdf8', weight: 0.9, opacity: 0.65, fillColor: '#0ea5e9', fillOpacity: 0.03 },
            }).addTo(map);
            boundaryLayer.bringToBack();
            syncHeatmapPoints();
            syncMapLayout();
        } catch (error) {
            console.error('Unable to load Davao barangay boundaries for citizen map.', error);
        }
    };

    const loadReports = async () => {
        try {
            const data = await fetchCitizenMapBundle(mapElement.dataset.reportsUrl);
            const reports = Array.isArray(data.reports) ? data.reports : [];
            rawHeatPoints = normalizeHeatPoints(data.heatPoints);
            syncHeatmapPoints();

            markers.clearLayers();
            reports.forEach((report) => L.marker([report.latitude, report.longitude], { icon: iconFor(report.severity) })
                .bindPopup(`<strong>${escapeHtml(report.location)}</strong><br>${escapeHtml(report.water_level)}<br><small>${escapeHtml(report.reported_at)} · ${escapeHtml(report.validation_status)}</small>`)
                .addTo(markers));

            if (statusBadge) {
                const verifiedCount = Number(data.validatedEventsCount || 0);
                const barangayCount = Number(data.barangaysAffectedCount || 0);
                statusBadge.textContent = verifiedCount > 0
                    ? `Verified Heatmap: ${verifiedCount.toLocaleString()} reports · ${barangayCount} barangays`
                    : 'Verified Heatmap: No active verified flood reports';
            }
        } catch (error) {
            console.error('Unable to load citizen map reports.', error);
            if (statusBadge) {
                statusBadge.textContent = 'Unable to load verified flood heatmap';
            }
        }
    };

    const showLocation = ({ latitude, longitude }, label = 'You are here') => {
        L.marker([latitude, longitude], {
            icon: L.divIcon({ className: 'citizen-live-marker', html: '<span class="marker-user"></span>', iconSize: [14, 14], iconAnchor: [7, 7] }),
        }).bindPopup(`<strong>${label}</strong>`).addTo(map).openPopup();
        map.setView([latitude, longitude], 15);
    };

    document.querySelector('[data-map-location]')?.addEventListener('click', () => {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(({ coords }) => showLocation(coords), () => {});
    });

    const searchInput = document.querySelector('[data-map-search]');
    const results = document.querySelector('[data-map-search-results]');
    let searchTimer;
    searchInput?.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        const query = searchInput.value.trim();
        if (query.length < 2) {
            results.innerHTML = '';
            return;
        }
        searchTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(`${mapElement.dataset.searchUrl}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                results.replaceChildren(...data.results.map((result) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.lat = result.latitude;
                    button.dataset.lng = result.longitude;
                    button.textContent = result.label;
                    return button;
                }));
            } catch (error) {
                console.error('Unable to search map locations.', error);
                results.innerHTML = '<div class="map-search-empty">No locations found</div>';
            }
        }, 300);
    });
    results?.addEventListener('click', (event) => {
        const result = event.target.closest('button');
        if (!result) return;
        map.setView([Number(result.dataset.lat), Number(result.dataset.lng)], 16);
        searchInput.value = result.textContent;
        results.innerHTML = '';
    });

    if (typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(() => syncMapLayout());
        observer.observe(mapElement);
    }
    document.querySelector('[data-citizen-screen="map"]')?.addEventListener('citizen-map-shown', () => window.setTimeout(syncMapLayout, 50));

    await Promise.all([loadBoundaries(), loadReports()]);
};

const initCitizenPreviewMap = async () => {
    const mapElement = document.querySelector('[data-citizen-preview-map]');
    if (!mapElement) return;

    const map = L.map(mapElement, {
        attributionControl: false,
        zoomControl: false,
        dragging: false,
        doubleClickZoom: false,
        scrollWheelZoom: false,
        touchZoom: false,
        zoomSnap: 0.5,
        preferCanvas: true,
    }).setView(DAVAO_CENTER, 11.5);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        keepBuffer: 2,
        updateWhenZooming: false,
    }).addTo(map);

    const boundaryUrl = mapElement.dataset.boundaryUrl || DEFAULT_BOUNDARY_URL;
    const heatLayer = createHeatLayer(map, []);

    try {
        const [boundaryResult, data] = await Promise.allSettled([
            loadBoundaryFeatures(boundaryUrl),
            fetchCitizenMapBundle(mapElement.dataset.reportsUrl),
        ]);

        let boundaryFeatures = [];
        if (boundaryResult.status === 'fulfilled') {
            const { geoJson, features } = boundaryResult.value;
            boundaryFeatures = features;
            const previewBoundary = L.geoJSON(geoJson, {
                interactive: false,
                style: { color: '#38bdf8', weight: 0.7, opacity: 0.5, fillColor: '#0ea5e9', fillOpacity: 0.03 },
            }).addTo(map);
            previewBoundary.bringToBack();
            heatLayer.setBoundary(boundaryFeatures);
        }

        if (data.status === 'fulfilled') {
            const payload = data.value;
            const rawPoints = normalizeHeatPoints(payload.heatPoints);
            const clippedPoints = boundaryFeatures.length
                ? pointsInsideFeatures(rawPoints, boundaryFeatures)
                : rawPoints;
            heatLayer.setPoints(clippedPoints);

            (Array.isArray(payload.reports) ? payload.reports : []).slice(0, 12).forEach((report) => {
                L.circleMarker([report.latitude, report.longitude], {
                    radius: 5,
                    color: '#ffffff',
                    weight: 2,
                    fillColor: report.severity === 'critical'
                        ? '#dc2626'
                        : report.severity === 'moderate' ? '#f59e0b' : '#16a34a',
                    fillOpacity: 1,
                }).addTo(map);
            });
        }
    } catch (error) {
        console.error('Unable to load citizen map preview reports.', error);
    }
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initOperationsMap);
else initOperationsMap();
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCitizenHomeMap);
else initCitizenHomeMap();
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCitizenPreviewMap);
else initCitizenPreviewMap();

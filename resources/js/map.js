import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const DEFAULT_BOUNDARY_URL = '/data/davao-barangays.geojson';
const DAVAO_CENTER = [7.0731, 125.6128];
const heatPoints = [
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
    let inside = false;
    for (let index = 0, previous = ring.length - 1; index < ring.length; previous = index++) {
        const [longitude, latitude] = ring[index];
        const [previousLongitude, previousLatitude] = ring[previous];
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

const createHeatLayer = (map) => {
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
        _reset() {
            const size = this._map.getSize();
            const topLeft = this._map.containerPointToLayerPoint([0, 0]);
            L.DomUtil.setPosition(this._canvas, topLeft);
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
                        const point = this._map.latLngToLayerPoint([latitude, longitude]);
                        if (index === 0) context.moveTo(point.x, point.y);
                        else context.lineTo(point.x, point.y);
                    });
                    context.closePath();
                }));
            });
        },
        _draw() {
            if (!this._canvas || !this._map || !this.activeFeatures.length) return;
            const context = this._canvas.getContext('2d');
            context.clearRect(0, 0, this._canvas.width, this._canvas.height);
            context.save();
            context.beginPath();
            this._clipPath(context);
            context.clip('evenodd');

            const visiblePoints = this.points.filter(([latitude, longitude]) => {
                const point = L.latLng(latitude, longitude);
                return this.activeFeatures.some((feature) => featureContainsPoint(point, feature));
            });
            visiblePoints.forEach(([latitude, longitude, intensity]) => {
                const point = this._map.latLngToLayerPoint([latitude, longitude]);
                const radius = 46 + (intensity * 22);
                const gradient = context.createRadialGradient(point.x, point.y, 0, point.x, point.y, radius);
                gradient.addColorStop(0, `rgba(220, 38, 38, ${0.68 * intensity})`);
                gradient.addColorStop(0.35, `rgba(217, 119, 6, ${0.42 * intensity})`);
                gradient.addColorStop(0.7, `rgba(2, 132, 199, ${0.2 * intensity})`);
                gradient.addColorStop(1, 'rgba(2, 132, 199, 0)');
                context.fillStyle = gradient;
                context.fillRect(point.x - radius, point.y - radius, radius * 2, radius * 2);
            });
            context.restore();
        },
    });

    return new layer(heatPoints).addTo(map);
};

const initOperationsMap = async () => {
    const mapElement = document.querySelector('[data-flood-map]');
    if (!mapElement) return;

    const map = L.map(mapElement, { preferCanvas: true }).setView(DAVAO_CENTER, 12);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    const status = document.querySelector('[data-map-status]');
    const selector = document.querySelector('[data-barangay-filter]');
    const boundaryUrl = mapElement.dataset.boundaryUrl || DEFAULT_BOUNDARY_URL;
    let boundaryLayer;
    let features = [];
    const heatLayer = createHeatLayer(map);

    try {
        const response = await fetch(boundaryUrl, { headers: { Accept: 'application/geo+json, application/json' } });
        if (!response.ok) throw new Error(`Boundary request failed with status ${response.status}`);
        const geoJson = await response.json();
        features = geoJson.type === 'FeatureCollection' ? geoJson.features : [{ type: 'Feature', properties: {}, geometry: geoJson }];
        if (!features.length) throw new Error('Boundary GeoJSON contains no features.');

        boundaryLayer = L.geoJSON(geoJson, {
            interactive: false,
            style: { color: '#38bdf8', weight: 1, opacity: 0.7, fillColor: '#0ea5e9', fillOpacity: 0.04 },
        }).addTo(map);
        map.fitBounds(boundaryLayer.getBounds(), { padding: [18, 18] });

        selector.innerHTML = '<option value="">All barangays</option>';
        features
            .map((feature) => getFeatureName(feature))
            .sort((first, second) => first.localeCompare(second))
            .forEach((name) => selector.add(new Option(name, name)));

        const updateBoundary = (selectedName = '') => {
            const activeFeatures = selectedName
                ? features.filter((feature) => getFeatureName(feature) === selectedName)
                : features;
            boundaryLayer.setStyle((feature) => ({
                color: getFeatureName(feature) === selectedName ? '#fbbf24' : '#38bdf8',
                weight: getFeatureName(feature) === selectedName ? 2 : 1,
                fillOpacity: getFeatureName(feature) === selectedName ? 0.14 : 0.04,
            }));
            heatLayer.setBoundary(activeFeatures);
            if (selectedName && activeFeatures.length) {
                map.fitBounds(L.geoJSON({ type: 'FeatureCollection', features: activeFeatures }).getBounds(), { padding: [30, 30] });
            } else {
                map.fitBounds(boundaryLayer.getBounds(), { padding: [18, 18] });
            }
            status.textContent = selectedName ? `Showing clipped intensity for ${selectedName}` : 'Showing clipped intensity across Davao City barangays';
        };

        selector.addEventListener('change', (event) => updateBoundary(event.target.value));
        updateBoundary();
    } catch (error) {
        console.error('Unable to load Davao barangay boundaries.', error);
        status.textContent = 'Barangay boundaries could not be loaded. Add the GeoJSON file to public/data.';
        selector.disabled = true;
    }
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initOperationsMap);
else initOperationsMap();

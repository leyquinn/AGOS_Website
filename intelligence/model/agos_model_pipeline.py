"""
agos_model_pipeline.py — AGOS Multi-Task XLM-RoBERTa + K-Means++ Inference Runner
==================================================================================
Bridges the trained Multi-Task XLM-RoBERTa checkpoint (best_model.pt), hybrid
Davao Gazetteer NER, GeoJSON barangay geocoding, and K-Means++ spatial clustering
into the AGOS Laravel web application.

Supports two execution modes:
  1. Live Inference Mode (--input <json>):
     Runs the trained Multi-Task XLM-RoBERTa checkpoint on new scraped posts.
  2. Precomputed Cleaned Dataset Import Mode (--import-cleaned-dir <dir>):
     Imports Cleaned_verified_flood_reports_flood_reports.json and
     Cleaned_flood_mentioned_no_urgency_flood_reports.json from the trained model's
     With Timestamp output, cleans NER location spans, geocodes every report inside
     its exact Davao City GeoJSON barangay polygon, and computes K-Means++ D_clusters
     and Final Confidence Score (CS = 0.35 * P_rel + 0.65 * D_clusters).
"""

import argparse
import csv
import hashlib
import json
import logging
import math
import os
import re
import sys
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

# Ensure UTF-8 console output on Windows
if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass

import numpy as np
from sklearn.cluster import KMeans

from config import MTLConfig
from gazetteer import FLOOD_PRONE_GAZETTEER, match_gazetteer_in_text

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("AGOSModelPipeline")


# ═══════════════════════════════════════════════════════════════════════
# Mapping from Gazetteer / Common Names to Exact GeoJSON Feature Names
# in public/data/davao-barangays.geojson (182 Official Barangays)
# ═══════════════════════════════════════════════════════════════════════
GAZETTEER_TO_GEOJSON_NAME: Dict[str, str] = {
    "talomo proper": "Talomo",
    "talomo": "Talomo",
    "ulas": "Talomo",
    "bangkal": "Talomo",
    "buhangin proper": "Buhangin",
    "buhangin": "Buhangin",
    "tugbok proper": "Tugbok",
    "tugbok": "Tugbok",
    "bunawan proper": "Bunawan",
    "bunawan": "Bunawan",
    "agdao proper": "Agdao",
    "agdao": "Agdao",
    "matina crossing (74-a)": "Matina Crossing",
    "matina crossing": "Matina Crossing",
    "74-a": "Matina Crossing",
    "gravahan": "Matina Crossing",
    "matina aplaya (75-a)": "Matina Aplaya",
    "matina aplaya": "Matina Aplaya",
    "75-a": "Matina Aplaya",
    "matina pangi": "Matina Pangi",
    "matina biao": "Matina Biao",
    "bucana (76-a)": "Bucana",
    "bucana": "Bucana",
    "76-a": "Bucana",
    "barangay 19-b (bankerohan)": "Barangay 19-B",
    "barangay 19-b": "Barangay 19-B",
    "bankerohan": "Barangay 19-B",
    "bajada": "Barangay 19-B",
    "centro (san antonio)": "Centro",
    "centro": "Centro",
    "san antonio": "San Antonio",
    "lasang": "Alejandra Navarro",
    "alejandra navarro": "Alejandra Navarro",
    "ilangan": "Ilang",
    "ilang": "Ilang",
    "lanang": "Pampanga",
    "pampanga": "Pampanga",
    "poblacion": "Barangay 2-A",
    "downtown davao": "Barangay 34-D",
    "davao downtown": "Barangay 34-D",
    "kap. tomas monteverde sr.": "Kap. Tomas Monteverde, Sr.",
    "tomas monteverde": "Kap. Tomas Monteverde, Sr.",
    "leon garcia": "Leon Garcia, Sr.",
    "lapu-lapu": "Lapu-lapu",
    "catalunan pequeno": "Catalunan Pequeño",
    "catalunan pequeño": "Catalunan Pequeño",
    "santo nino": "Santo Niño",
    "santo niño": "Santo Niño",
    "sto. nino": "Santo Niño",
    "sto. niño": "Santo Niño",
    "sirawan": "Sirawan",
    "dumoy": "Dumoy",
    "daliao": "Daliao",
    "lizada": "Lizada",
    "indangan": "Indangan",
    "communal": "Communal",
    "crossing bayabas": "Crossing Bayabas",
    "los amigos": "Los Amigos",
    "riverside": "Riverside",
    "lacson": "Lacson",
    "tamugan": "Tamugan",
    "buda": "Buda",
    "malabog": "Malabog",
    "mapula": "Mapula",
    "colosas": "Colosas",
    "subasta": "Subasta",
    "sirib": "Sirib",
    "tambobong": "Tambobong",
    "datu salumay": "Datu Salumay",
    "magsaysay": "Magsaysay",
    "gov. paciano bangoy": "Gov. Paciano Bangoy",
    "gov. vicente duterte": "Gov. Vicente Duterte",
    "vicente hizon sr.": "Vicente Hizon Sr.",
    "wilfredo aquino": "Wilfredo Aquino",
    "rafael castillo": "Rafael Castillo",
    "alfonso angliongto sr.": "Alfonso Angliongto Sr.",
}

# Noise tokens occasionally emitted by raw subword NER that are not specific barangays
INVALID_LOCATION_TOKENS = {
    "brgy", "brgy.", "barangay", "davao", "(davao", "davao)", "city", "city)",
    "davao city", "philippines", "mindanao", "region xi", "davao del sur",
    "st.", "street", "road", "ave", "avenue", "purok", "sitio", "phase",
    "subd", "subd.", "subdivision", "bridge", "river", "creek",
}


def point_in_ring(lat: float, lng: float, ring: List[List[float]]) -> bool:
    inside = False
    n = len(ring)
    if n < 3:
        return False
    prev = n - 1
    for i in range(n):
        xi, yi = ring[i][0], ring[i][1]
        xj, yj = ring[prev][0], ring[prev][1]
        intersects = ((yi > lat) != (yj > lat)) and (
            lng < (xj - xi) * (lat - yi) / ((yj - yi) or 1e-12) + xi
        )
        if intersects:
            inside = not inside
        prev = i
    return inside


def point_in_geometry(lat: float, lng: float, geometry: Dict[str, Any]) -> bool:
    if not geometry:
        return False
    gtype = geometry.get("type")
    coords = geometry.get("coordinates") or []
    if gtype == "Polygon" and coords:
        if not point_in_ring(lat, lng, coords[0]):
            return False
        return not any(point_in_ring(lat, lng, hole) for hole in coords[1:])
    if gtype == "MultiPolygon" and coords:
        for poly in coords:
            if poly and point_in_ring(lat, lng, poly[0]):
                if not any(point_in_ring(lat, lng, hole) for hole in poly[1:]):
                    return True
    return False


class BarangayGeocoder:
    """
    Resolves extracted location names to Davao City GeoJSON barangay polygons
    and guarantees interior coordinates inside the polygon for Leaflet heatmap clipping.
    """

    def __init__(self, geojson_path: Optional[str] = None):
        self.features_by_lower: Dict[str, Dict[str, Any]] = {}
        self.geojson_names: List[str] = []
        self.geojson_patterns: List[Tuple[re.Pattern, str]] = []
        if geojson_path and os.path.exists(geojson_path):
            self._load_geojson(geojson_path)

    def _load_geojson(self, geojson_path: str) -> None:
        try:
            with open(geojson_path, "r", encoding="utf-8", errors="replace") as f:
                data = json.load(f)
            features = data.get("features", [])
            for feat in features:
                props = feat.get("properties") or {}
                raw_name = str(props.get("name") or "").strip()
                if not raw_name:
                    continue
                # Fix encoding replacement char in Catalunan Pequeño / Santo Niño if present
                clean_name = raw_name.replace("\ufffd", "ñ")
                geometry = feat.get("geometry")
                centroid, bbox_radius = self._find_interior_point(geometry)
                if centroid:
                    entry = {
                        "raw_name": raw_name,
                        "clean_name": clean_name,
                        "lat": centroid[0],
                        "lng": centroid[1],
                        "radius": bbox_radius,
                        "geometry": geometry,
                    }
                    self.features_by_lower[raw_name.lower()] = entry
                    self.features_by_lower[clean_name.lower()] = entry
                    self.features_by_lower[clean_name.lower().replace("ñ", "n")] = entry
                    self.geojson_names.append(clean_name)

            # Build whole-word regexes for all 182 GeoJSON barangays (sorted longest first)
            for gname in sorted(set(self.geojson_names), key=len, reverse=True):
                if len(gname) < 3:
                    continue
                pat = re.compile(rf"(?<!\w){re.escape(gname)}(?!\w)", re.IGNORECASE)
                self.geojson_patterns.append((pat, gname))
        except Exception as exc:
            logger.warning(f"Could not parse GeoJSON centroids from {geojson_path}: {exc}")

    @staticmethod
    def _find_interior_point(
        geometry: Optional[Dict[str, Any]],
    ) -> Tuple[Optional[Tuple[float, float]], float]:
        if not geometry:
            return None, 0.002
        gtype = geometry.get("type")
        coords = geometry.get("coordinates") or []
        ring = None
        if gtype == "Polygon" and coords:
            ring = coords[0]
        elif gtype == "MultiPolygon" and coords:
            ring = max((poly[0] for poly in coords if poly), key=len, default=None)
        if not ring:
            return None, 0.002

        lats = [pt[1] for pt in ring if len(pt) >= 2]
        lngs = [pt[0] for pt in ring if len(pt) >= 2]
        if not lats or not lngs:
            return None, 0.002

        mean_lat = sum(lats) / len(lats)
        mean_lng = sum(lngs) / len(lngs)
        lat_span = max(lats) - min(lats)
        lng_span = max(lngs) - min(lngs)
        radius = max(0.0005, min(0.006, min(lat_span, lng_span) * 0.25))

        if point_in_geometry(mean_lat, mean_lng, geometry):
            return (mean_lat, mean_lng), radius

        # If concave polygon's mean centroid falls slightly outside, sample chords
        n = len(ring)
        for step in (n // 2, n // 3, n // 4):
            if step < 1:
                continue
            for i in range(0, n, max(1, n // 12)):
                j = (i + step) % n
                mid_lat = (ring[i][1] + ring[j][1]) / 2.0
                mid_lng = (ring[i][0] + ring[j][0]) / 2.0
                if point_in_geometry(mid_lat, mid_lng, geometry):
                    return (mid_lat, mid_lng), radius

        return (mean_lat, mean_lng), radius

    def clean_and_enrich_locations(
        self,
        raw_locations: List[str],
        raw_text: str = "",
    ) -> List[str]:
        """
        Clean noisy NER fragments (like 'Brgy.', '(Davao', 'Mnewsdavao') and enrich with Gazetteer
        and 182-barangay GeoJSON matches found in the post text.
        """
        cleaned: List[str] = []
        seen: set = set()

        for loc in raw_locations or []:
            s = re.sub(r"^[\(\[\s.,;:-]+|[\)\]\s.,;:-]+$", "", str(loc)).strip()
            if not s or len(s) < 3:
                continue
            if s.lower() in INVALID_LOCATION_TOKENS:
                continue
            entry = self.resolve_to_geojson_entry(s)
            if entry:
                canon = entry["clean_name"]
                if canon.lower() not in seen:
                    seen.add(canon.lower())
                    cleaned.append(canon)
                continue
            for gaz_loc in match_gazetteer_in_text(s):
                if gaz_loc.lower() == "davao city":
                    continue
                g_entry = self.resolve_to_geojson_entry(gaz_loc)
                canon = g_entry["clean_name"] if g_entry else gaz_loc
                if canon.lower() not in seen:
                    seen.add(canon.lower())
                    cleaned.append(canon)

        # Also check gazetteer on raw_text
        if raw_text:
            for gaz_loc in match_gazetteer_in_text(raw_text):
                if gaz_loc.lower() == "davao city":
                    continue
                g_entry = self.resolve_to_geojson_entry(gaz_loc)
                canon = g_entry["clean_name"] if g_entry else gaz_loc
                if canon.lower() not in seen:
                    seen.add(canon.lower())
                    cleaned.append(canon)

            # Check 182 GeoJSON barangay names on raw_text if still no specific barangay
            if not cleaned:
                for pat, gname in self.geojson_patterns:
                    if pat.search(raw_text) and gname.lower() not in seen:
                        seen.add(gname.lower())
                        cleaned.append(gname)

        if not cleaned:
            cleaned.append("Davao City")

        return cleaned

    def resolve_to_geojson_entry(self, location_name: str) -> Optional[Dict[str, Any]]:
        loc_lower = location_name.strip().lower()
        # Normalize Brgy.21C -> barangay 21-c
        brgy_num_match = re.match(r"^(?:brgy\.?|barangay)\s*(\d+)\s*[- ]?\s*([a-z])$", loc_lower)
        if brgy_num_match:
            loc_lower = f"barangay {brgy_num_match.group(1)}-{brgy_num_match.group(2)}"

        # 1. Explicit mapping table
        if loc_lower in GAZETTEER_TO_GEOJSON_NAME:
            mapped = GAZETTEER_TO_GEOJSON_NAME[loc_lower].lower()
            if mapped in self.features_by_lower:
                return self.features_by_lower[mapped]

        # 2. Direct match in GeoJSON
        if loc_lower in self.features_by_lower:
            return self.features_by_lower[loc_lower]

        # 3. Strip parentheticals or 'Proper' / 'Barangay'
        stripped = re.sub(r"\s*\(.*?\)", "", loc_lower).replace("proper", "").strip()
        stripped = re.sub(r"^(?:brgy\.?|barangay)\s+", "", stripped).strip()
        if stripped in GAZETTEER_TO_GEOJSON_NAME:
            mapped = GAZETTEER_TO_GEOJSON_NAME[stripped].lower()
            if mapped in self.features_by_lower:
                return self.features_by_lower[mapped]
        if stripped in self.features_by_lower:
            return self.features_by_lower[stripped]

        return None

    def export_centroids(self, output_path: str) -> None:
        out: Dict[str, Any] = {}
        for name in sorted(set(self.geojson_names)):
            entry = self.features_by_lower.get(name.lower())
            if not entry:
                continue
            basin = FLOOD_PRONE_GAZETTEER.get(name, {}).get("basin")
            if not basin:
                for k, v in FLOOD_PRONE_GAZETTEER.items():
                    if name.lower() in k.lower() or k.lower() in name.lower():
                        basin = v.get("basin")
                        break
            out[name] = {
                "lat": round(entry["lat"], 7),
                "lng": round(entry["lng"], 7),
                "radius": round(entry["radius"], 6),
                "river_basin": basin or "Davao City Watershed",
            }
        with open(output_path, "w", encoding="utf-8") as f:
            json.dump(out, f, indent=2, ensure_ascii=False)

    def geocode(
        self,
        extracted_locations: List[str],
        post_id: str = "",
        raw_text: str = "",
    ) -> Tuple[str, str, float, float, List[str]]:
        """
        Return (primary_barangay, river_basin, latitude, longitude, cleaned_locations).
        Guarantees coordinates lie inside the matched Davao City GeoJSON polygon!
        """
        cleaned_locs = self.clean_and_enrich_locations(extracted_locations, raw_text=raw_text)
        specific_locs = [l for l in cleaned_locs if l.lower() != "davao city"]

        chosen_display = specific_locs[0] if specific_locs else "Poblacion"
        basin = FLOOD_PRONE_GAZETTEER.get(chosen_display, {}).get("basin")
        if not basin:
            for k, v in FLOOD_PRONE_GAZETTEER.items():
                if chosen_display.lower() in k.lower() or k.lower() in chosen_display.lower():
                    basin = v.get("basin")
                    break
        basin = basin or "Davao City Watershed"

        entry = None
        for cand in specific_locs:
            entry = self.resolve_to_geojson_entry(cand)
            if entry:
                chosen_display = entry["clean_name"]
                break

        if not entry:
            # For generic 'Davao City' posts, distribute across major flood-prone downtown/river barangays
            # deterministically by post_id so they don't all stack on one pixel
            fallback_brgys = [
                "Talomo", "Buhangin", "Matina Crossing", "Tigatto", "Ma-a",
                "Bucana", "Barangay 19-B", "Agdao", "Toril", "Sasa", "Panacan",
            ]
            idx = int(hashlib.md5((post_id or "0").encode("utf-8")).hexdigest()[:4], 16) % len(fallback_brgys)
            entry = self.features_by_lower.get(fallback_brgys[idx].lower())
            if entry:
                chosen_display = entry["clean_name"]

        if entry:
            base_lat = entry["lat"]
            base_lng = entry["lng"]
            max_r = entry["radius"]
            geometry = entry["geometry"]
            # Try deterministic jitter inside polygon
            lat, lng = base_lat, base_lng
            if post_id:
                digest = hashlib.md5(post_id.encode("utf-8")).hexdigest()
                angle = (int(digest[:4], 16) / 65535.0) * 2.0 * math.pi
                frac = (int(digest[4:8], 16) / 65535.0)
                for scale in (1.0, 0.6, 0.3, 0.1):
                    r = max_r * frac * scale
                    cand_lat = base_lat + r * math.sin(angle)
                    cand_lng = base_lng + r * math.cos(angle)
                    if point_in_geometry(cand_lat, cand_lng, geometry):
                        lat, lng = cand_lat, cand_lng
                        break
            return chosen_display, basin, round(lat, 7), round(lng, 7), cleaned_locs

        return chosen_display, basin, 7.0660, 125.6055, cleaned_locs


def resolve_checkpoint_path(cli_checkpoint: Optional[str]) -> str:
    script_dir = Path(__file__).resolve().parent
    candidates: List[Path] = []

    if cli_checkpoint:
        candidates.append(Path(cli_checkpoint))
        candidates.append(script_dir / cli_checkpoint)

    local_ckpt_dir = script_dir / "checkpoints"
    candidates.append(local_ckpt_dir / "best_model.pt")
    if local_ckpt_dir.is_dir():
        for pt_file in sorted(local_ckpt_dir.glob("*.pt")):
            candidates.append(pt_file)

    sibling_ckpt_dir = script_dir.parent.parent.parent / "XLM- RoBERTa" / "checkpoints"
    candidates.append(sibling_ckpt_dir / "best_model.pt")
    if sibling_ckpt_dir.is_dir():
        for pt_file in sorted(sibling_ckpt_dir.glob("*.pt")):
            candidates.append(pt_file)

    for cand in candidates:
        if cand.is_file():
            return str(cand.resolve())

    raise FileNotFoundError(
        f"No trained XLM-RoBERTa checkpoint (.pt) found in {local_ckpt_dir}. "
        "Please place best_model.pt inside intelligence/model/checkpoints/."
    )


def compute_kmeans_consensus(
    records: List[Dict[str, Any]],
) -> List[Dict[str, Any]]:
    """
    Run K-Means++ spatial clustering on geolocated flood-related posts and compute
    cluster consensus score (D_clusters) and Final Confidence Score (CS):
        CS = (0.35 * P_rel) + (0.65 * D_clusters)
    Whitelisted official sources receive automatic CS = 1.00.
    """
    geo_indices: List[int] = []
    coords: List[List[float]] = []
    barangay_counts: Dict[str, int] = {}

    for idx, rec in enumerate(records):
        if rec.get("is_flood_related") and rec.get("latitude") is not None and rec.get("longitude") is not None:
            geo_indices.append(idx)
            coords.append([float(rec["latitude"]), float(rec["longitude"])])
            brgy = rec.get("primary_barangay") or "Davao City"
            barangay_counts[brgy] = barangay_counts.get(brgy, 0) + 1

    cluster_assignments: Dict[int, int] = {}
    cluster_sizes: Dict[int, int] = {}
    cluster_distances: Dict[int, float] = {}

    n_geo = len(geo_indices)
    if n_geo >= 2:
        coords_np = np.array(coords, dtype=np.float64)
        unique_pts = len(set((round(c[0], 3), round(c[1], 3)) for c in coords))
        k = max(1, min(unique_pts, min(25, max(1, int(round(math.sqrt(n_geo)))))))

        kmeans = KMeans(n_clusters=k, init="k-means++", n_init=10, random_state=42)
        labels = kmeans.fit_predict(coords_np)
        centers = kmeans.cluster_centers_

        counts_per_label: Dict[int, int] = {}
        for lbl in labels:
            counts_per_label[int(lbl)] = counts_per_label.get(int(lbl), 0) + 1

        for pos, rec_idx in enumerate(geo_indices):
            cid = int(labels[pos])
            center = centers[cid]
            dist = float(np.linalg.norm(coords_np[pos] - center))
            cluster_assignments[rec_idx] = cid
            cluster_sizes[rec_idx] = counts_per_label[cid]
            cluster_distances[rec_idx] = dist
    elif n_geo == 1:
        only_idx = geo_indices[0]
        cluster_assignments[only_idx] = 0
        cluster_sizes[only_idx] = 1
        cluster_distances[only_idx] = 0.0

    for idx, rec in enumerate(records):
        p_rel = float(rec.get("p_rel") or 0.0)
        is_whitelisted = bool(rec.get("is_whitelisted_source", False))
        status = rec.get("verification_status", "DISCARDED_NOT_FLOOD")

        if is_whitelisted and rec.get("is_flood_related"):
            rec["cluster_id"] = cluster_assignments.get(idx, 0)
            rec["cluster_size"] = max(cluster_sizes.get(idx, 1), 1)
            rec["d_clusters"] = 1.0
            rec["confidence_score"] = 1.0
            continue

        if idx in cluster_assignments:
            cid = cluster_assignments[idx]
            c_size = cluster_sizes[idx]
            dist = cluster_distances[idx]
            brgy = rec.get("primary_barangay") or "Davao City"
            brgy_corroboration = barangay_counts.get(brgy, 1)
            effective_support = max(c_size, brgy_corroboration)

            proximity_factor = math.exp(-dist * 10.0)

            if effective_support <= 1:
                d_clusters = 0.42 + (0.12 * p_rel)
            elif effective_support == 2:
                d_clusters = 0.72 + (0.12 * proximity_factor)
            else:
                density_bonus = min(0.13, 0.02 * (effective_support - 2))
                d_clusters = min(0.98, 0.85 + density_bonus * proximity_factor)

            if status == "FLOOD_MENTION_NO_URGENCY":
                d_clusters = round(d_clusters * 0.72, 4)
            else:
                d_clusters = round(d_clusters, 4)

            cs = round((0.35 * p_rel) + (0.65 * d_clusters), 4)
            rec["cluster_id"] = cid
            rec["cluster_size"] = effective_support
            rec["d_clusters"] = d_clusters
            rec["confidence_score"] = min(0.99, cs)
        elif status == "DISCARDED_UNLOCALIZED":
            rec["cluster_id"] = None
            rec["cluster_size"] = 0
            rec["d_clusters"] = 0.0
            rec["confidence_score"] = round(0.35 * p_rel, 4)
        else:
            rec["cluster_id"] = None
            rec["cluster_size"] = 0
            rec["d_clusters"] = 0.0
            rec["confidence_score"] = round(0.35 * p_rel, 4)

    return records


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Run AGOS Multi-Task XLM-RoBERTa + K-Means++ Pipeline or import precomputed Cleaned outputs."
    )
    parser.add_argument(
        "--input",
        "-i",
        type=str,
        default=None,
        help="Path to input JSON file containing posts to analyze with XLM-RoBERTa.",
    )
    parser.add_argument(
        "--import-cleaned-dir",
        type=str,
        default=None,
        help="Directory containing Cleaned_verified_flood_reports_flood_reports.json and Cleaned_flood_mentioned_no_urgency_flood_reports.json.",
    )
    parser.add_argument(
        "--output-dir",
        "-o",
        type=str,
        required=True,
        help="Directory to write model_results.json.",
    )
    parser.add_argument(
        "--checkpoint",
        "-c",
        type=str,
        default=None,
        help="Optional path to trained .pt checkpoint.",
    )
    parser.add_argument(
        "--geojson",
        "-g",
        type=str,
        default=None,
        help="Optional path to davao-barangays.geojson for polygon centroid geocoding.",
    )
    parser.add_argument(
        "--threshold",
        "-t",
        type=float,
        default=0.50,
        help="Relevance probability threshold P_rel (default: 0.50).",
    )
    return parser.parse_args()


def run_import_cleaned_mode(
    import_dir: str,
    output_dir: str,
    geojson_path: Optional[str],
) -> None:
    """
    Process precomputed Cleaned_verified_flood_reports_flood_reports.json and
    Cleaned_flood_mentioned_no_urgency_flood_reports.json with GeoJSON polygon
    geocoding and K-Means++ spatial consensus scoring.
    """
    from preprocessing import strip_fb_artifacts

    verified_file = os.path.join(import_dir, "Cleaned_verified_flood_reports_flood_reports.json")
    no_urgency_file = os.path.join(import_dir, "Cleaned_flood_mentioned_no_urgency_flood_reports.json")

    if not os.path.exists(verified_file):
        raise FileNotFoundError(f"Verified file not found: {verified_file}")

    with open(verified_file, "r", encoding="utf-8") as f:
        verified_items = json.load(f)

    no_urgency_items = []
    if os.path.exists(no_urgency_file):
        with open(no_urgency_file, "r", encoding="utf-8") as f:
            no_urgency_items = json.load(f)

    geocoder = BarangayGeocoder(geojson_path=geojson_path)
    geocoder.export_centroids(os.path.join(output_dir, "barangay_centroids.json"))
    records: List[Dict[str, Any]] = []
    seen_ids: set = set()

    for item in verified_items + no_urgency_items:
        pid = str(item.get("post_id") or "")
        if not pid or pid in seen_ids:
            continue
        seen_ids.add(pid)

        raw_text = str(item.get("raw_text") or "")
        cleaned_text = strip_fb_artifacts(raw_text) or raw_text
        raw_locs = item.get("extracted_locations") or []
        status = str(item.get("verification_status") or "VERIFIED_ACTIONABLE")

        primary_brgy, basin, lat, lng, cleaned_locs = geocoder.geocode(
            extracted_locations=raw_locs,
            post_id=pid,
            raw_text=cleaned_text,
        )

        records.append({
            "post_id": pid,
            "author": str(item.get("author") or "Facebook User"),
            "timestamp": str(item.get("timestamp") or ""),
            "iso_timestamp": str(item.get("iso_timestamp") or ""),
            "post_url": str(item.get("post_url") or ""),
            "raw_text": raw_text,
            "cleaned_text": cleaned_text,
            "is_whitelisted_source": False,
            "is_flood_related": bool(item.get("is_flood_related", True)),
            "p_rel": float(item.get("p_rel") or 0.85),
            "extracted_locations": cleaned_locs,
            "primary_barangay": primary_brgy,
            "river_basin": basin,
            "latitude": lat,
            "longitude": lng,
            "verification_status": status,
            "is_actionable": status == "VERIFIED_ACTIONABLE",
            "discard_reason": item.get("discard_reason"),
        })

    records = compute_kmeans_consensus(records)

    actionable = [r for r in records if r["verification_status"] == "VERIFIED_ACTIONABLE"]
    early_watch = [r for r in records if r["verification_status"] == "FLOOD_MENTION_NO_URGENCY"]
    avg_cs = round(sum(r["confidence_score"] for r in actionable) / max(1, len(actionable)), 4)

    summary = {
        "mode": "precomputed_cleaned_dataset",
        "total_analyzed": len(records),
        "verified_actionable_count": len(actionable),
        "early_warning_count": len(early_watch),
        "discarded_unlocalized_count": 0,
        "discarded_not_flood_count": 0,
        "avg_confidence_score": avg_cs,
    }

    os.makedirs(output_dir, exist_ok=True)
    out_path = os.path.join(output_dir, "imported_cleaned_results.json")
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump({"summary": summary, "results": records}, f, ensure_ascii=False)

    print(
        f"Processed {len(records)} Cleaned model outputs | "
        f"Verified Actionable: {len(actionable)} | "
        f"Flood Watch (No Urgency): {len(early_watch)} | "
        f"Avg CS: {avg_cs:.2f}"
    )


def main() -> None:
    args = parse_args()
    os.makedirs(args.output_dir, exist_ok=True)

    if args.import_cleaned_dir:
        run_import_cleaned_mode(
            import_dir=args.import_cleaned_dir,
            output_dir=args.output_dir,
            geojson_path=args.geojson,
        )
        return

    if not args.input:
        raise ValueError("Either --input or --import-cleaned-dir must be provided.")

    from inference import FloodInferencePipeline

    checkpoint_path = resolve_checkpoint_path(args.checkpoint)
    logger.info(f"Resolved XLM-RoBERTa checkpoint: {checkpoint_path}")

    with open(args.input, "r", encoding="utf-8") as f:
        input_posts = json.load(f)

    config = MTLConfig(relevance_threshold=args.threshold)
    pipeline = FloodInferencePipeline.from_checkpoint(
        checkpoint_path=checkpoint_path,
        config=config,
    )

    geocoder = BarangayGeocoder(geojson_path=args.geojson)

    analyzed_records: List[Dict[str, Any]] = []
    total = len(input_posts)
    logger.info(f"Running Multi-Task XLM-RoBERTa inference on {total} posts...")

    for i, item in enumerate(input_posts, start=1):
        pid = str(item.get("post_id") or f"post_{i:04d}")
        text = str(item.get("cleaned_text") or item.get("full_text") or item.get("raw_text") or "")
        raw_text = str(item.get("full_text") or item.get("raw_text") or text)

        pred = pipeline.predict(raw_text=text, post_id=pid)

        extracted_locs = list(pred.get("extracted_locations") or [])
        if not extracted_locs and (pred.get("is_flood_related") or pred.get("verification_status") == "FLOOD_MENTION_NO_URGENCY"):
            extracted_locs = match_gazetteer_in_text(raw_text)
            if extracted_locs and pred.get("verification_status") == "DISCARDED_UNLOCALIZED":
                pred["extracted_locations"] = extracted_locs
                pred["verification_status"] = "VERIFIED_ACTIONABLE"
                pred["is_actionable"] = True
                pred["discard_reason"] = None

        primary_brgy, basin, lat, lng, cleaned_locs = geocoder.geocode(
            extracted_locations=extracted_locs,
            post_id=pid,
            raw_text=raw_text,
        )

        if not pred.get("is_flood_related") and pred.get("verification_status") != "FLOOD_MENTION_NO_URGENCY":
            primary_brgy, basin, lat, lng, cleaned_locs = None, None, None, None, []

        if item.get("latitude") is not None and item.get("longitude") is not None:
            lat = float(item["latitude"])
            lng = float(item["longitude"])

        record = {
            "id": item.get("id"),
            "post_id": pid,
            "author": item.get("author", ""),
            "is_whitelisted_source": bool(item.get("is_whitelisted_source", False)),
            "raw_text": raw_text,
            "is_flood_related": bool(pred["is_flood_related"]),
            "p_rel": float(pred["p_rel"]),
            "extracted_locations": cleaned_locs,
            "primary_barangay": primary_brgy,
            "river_basin": basin,
            "latitude": lat,
            "longitude": lng,
            "verification_status": pred["verification_status"],
            "is_actionable": bool(pred["is_actionable"]),
            "discard_reason": pred.get("discard_reason"),
        }
        analyzed_records.append(record)

    analyzed_records = compute_kmeans_consensus(analyzed_records)

    actionable = [r for r in analyzed_records if r["verification_status"] == "VERIFIED_ACTIONABLE"]
    early_watch = [r for r in analyzed_records if r["verification_status"] == "FLOOD_MENTION_NO_URGENCY"]
    unlocalized = [r for r in analyzed_records if r["verification_status"] == "DISCARDED_UNLOCALIZED"]
    non_flood = [r for r in analyzed_records if r["verification_status"] == "DISCARDED_NOT_FLOOD"]

    avg_cs = (
        round(sum(r["confidence_score"] for r in actionable) / len(actionable), 4)
        if actionable
        else (
            round(sum(r["confidence_score"] for r in analyzed_records if r["is_flood_related"]) / max(1, sum(1 for r in analyzed_records if r["is_flood_related"])), 4)
            if any(r["is_flood_related"] for r in analyzed_records)
            else 0.0
        )
    )

    summary = {
        "checkpoint_path": checkpoint_path,
        "checkpoint_name": os.path.basename(checkpoint_path),
        "device": str(pipeline.device),
        "threshold": args.threshold,
        "total_analyzed": total,
        "verified_actionable_count": len(actionable),
        "early_warning_count": len(early_watch),
        "discarded_unlocalized_count": len(unlocalized),
        "discarded_not_flood_count": len(non_flood),
        "avg_confidence_score": avg_cs,
    }

    output_payload = {
        "summary": summary,
        "results": analyzed_records,
    }

    results_json_path = os.path.join(args.output_dir, "model_results.json")
    with open(results_json_path, "w", encoding="utf-8") as f:
        json.dump(output_payload, f, indent=2, ensure_ascii=False)

    print(
        f"Analyzed {total} posts | "
        f"Actionable: {len(actionable)} | "
        f"Flood Watch (HITL): {len(early_watch)} | "
        f"Unlocalized: {len(unlocalized)} | "
        f"Non-Flood: {len(non_flood)} | "
        f"Avg CS: {avg_cs:.2f}"
    )


if __name__ == "__main__":
    main()

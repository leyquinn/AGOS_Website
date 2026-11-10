"""
gazetteer.py — Davao City Flood-Prone Areas Gazetteer & Location Normalizer
============================================================================
Defines the official gazetteer of flood-prone barangays, sub-areas, river
basins, and subdivisions in Davao City, along with regex patterns and
normalization utilities for hybrid location extraction.
"""

import re
from typing import Dict, List, Optional, Set, Tuple

# ═══════════════════════════════════════════════════════════════════════
# Official Davao City Flood-Prone Gazetteer by Catchment / Basin
# ═══════════════════════════════════════════════════════════════════════

FLOOD_PRONE_GAZETTEER: Dict[str, Dict] = {
    # ── 1. Davao River Basin Network (Riverine Overflow) ────────────────
    "Tigatto": {
        "basin": "Davao River Basin Network",
        "aliases": ["tigatto", "brgy tigatto", "barangay tigatto"],
        "sub_areas": ["jade valley", "jade valley subdivision", "jade valley subd"],
    },
    "Waan": {
        "basin": "Davao River Basin Network",
        "aliases": ["waan", "brgy waan", "barangay waan"],
        "sub_areas": [],
    },
    "Ma-a": {
        "basin": "Davao River Basin Network",
        "aliases": ["ma-a", "maa", "brgy ma-a", "brgy maa", "barangay ma-a", "barangay maa"],
        "sub_areas": ["juliville", "juliville subdivision", "el rio", "el rio vista", "san rafael"],
    },
    "Bucana (76-A)": {
        "basin": "Davao River Basin Network",
        "aliases": ["bucana", "76-a", "brgy 76-a", "barangay 76-a", "brgy bucana", "barangay bucana"],
        "sub_areas": [],
    },
    "Mandug": {
        "basin": "Davao River Basin Network",
        "aliases": ["mandug", "brgy mandug", "barangay mandug"],
        "sub_areas": [],
    },
    "Barangay 19-B (Bankerohan)": {
        "basin": "Davao River Basin Network",
        "aliases": ["19-b", "brgy 19-b", "barangay 19-b", "bankerohan", "bankerohan river"],
        "sub_areas": [],
    },
    "Barangay 5-A": {
        "basin": "Davao River Basin Network",
        "aliases": ["5-a", "brgy 5-a", "barangay 5-a", "brgy. 5-a"],
        "sub_areas": [],
    },
    "Barangay 8-A": {
        "basin": "Davao River Basin Network",
        "aliases": ["8-a", "brgy 8-a", "barangay 8-a", "brgy. 8-a"],
        "sub_areas": [],
    },
    "Barangay 9-A": {
        "basin": "Davao River Basin Network",
        "aliases": ["9-a", "brgy 9-a", "barangay 9-a", "brgy. 9-a"],
        "sub_areas": [],
    },
    "Barangay 10-A": {
        "basin": "Davao River Basin Network",
        "aliases": ["10-a", "brgy 10-a", "barangay 10-a", "brgy. 10-a"],
        "sub_areas": [],
    },
    "Barangay 2-A": {
        "basin": "Davao River Basin Network",
        "aliases": ["2-a", "brgy 2-a", "barangay 2-a", "brgy. 2-a"],
        "sub_areas": [],
    },
    "Barangay 1-A": {
        "basin": "Davao River Basin Network",
        "aliases": ["1-a", "brgy 1-a", "barangay 1-a", "brgy. 1-a"],
        "sub_areas": [],
    },

    # ── 2. Matina River Basin Catchment (Flash Flood Zones) ─────────────
    "Matina Pangi": {
        "basin": "Matina River Basin Catchment",
        "aliases": ["matina pangi", "pangi", "brgy matina pangi", "barangay matina pangi"],
        "sub_areas": [],
    },
    "Matina Crossing (74-A)": {
        "basin": "Matina River Basin Catchment",
        "aliases": [
            "matina crossing", "74-a", "brgy 74-a", "barangay 74-a",
            "gravahan", "matina crossing 74-a"
        ],
        "sub_areas": [],
    },
    "Matina Aplaya (75-A)": {
        "basin": "Matina River Basin Catchment",
        "aliases": ["matina aplaya", "75-a", "brgy 75-a", "barangay 75-a", "brgy matina aplaya"],
        "sub_areas": [],
    },
    "Matina Biao": {
        "basin": "Matina River Basin Catchment",
        "aliases": ["matina biao", "brgy matina biao", "barangay matina biao"],
        "sub_areas": [],
    },

    # ── 3. Talomo & Lipadas River Catchments ────────────────────────────
    "Talomo Proper": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["talomo", "talomo proper", "brgy talomo", "barangay talomo"],
        "sub_areas": [],
    },
    "Bago Aplaya": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["bago aplaya", "brgy bago aplaya", "barangay bago aplaya"],
        "sub_areas": [],
    },
    "Bago Gallera": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["bago gallera", "brgy bago gallera", "barangay bago gallera"],
        "sub_areas": [],
    },
    "Catalunan Pequeño": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": [
            "catalunan pequeno", "catalunan peque\u00f1o", "catalunan peque\u00f1o",
            "brgy catalunan pequeno", "brgy catalunan peque\u00f1o"
        ],
        "sub_areas": ["wellspring", "wellspring subdivision", "sincere", "sincere subdivision", "sincere subd"],
    },
    "Catalunan Grande": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["catalunan grande", "brgy catalunan grande", "barangay catalunan grande"],
        "sub_areas": [],
    },
    "Mintal": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["mintal", "brgy mintal", "barangay mintal", "tugbok mintal"],
        "sub_areas": [],
    },
    "Tacunan": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["tacunan", "brgy tacunan", "barangay tacunan"],
        "sub_areas": [],
    },
    "Tugbok Proper": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["tugbok", "tugbok proper", "brgy tugbok", "barangay tugbok"],
        "sub_areas": [],
    },
    "Bago Oshiro": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["bago oshiro", "brgy bago oshiro", "barangay bago oshiro"],
        "sub_areas": [],
    },
    "Ulas": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["ulas", "crossing ulas", "brgy ulas"],
        "sub_areas": [],
    },
    "Baliok": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["baliok", "brgy baliok", "barangay baliok"],
        "sub_areas": [],
    },
    "Bangkal": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["bangkal", "brgy bangkal", "barangay bangkal"],
        "sub_areas": [],
    },
    "Baguio": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["baguio", "baguio proper", "brgy baguio", "barangay baguio", "baguio district"],
        "sub_areas": ["tambobong", "brgy tambobong", "sitio mangas as"],
    },
    "Marilog": {
        "basin": "Rural & Highland Catchments",
        "aliases": [
            "marilog", "marilog proper", "brgy marilog", "barangay marilog",
            "marilog district", "magsaysay marilog", "buda", "datu salumay"
        ],
        "sub_areas": ["kulafo", "sitio kulafo", "brgy magsaysay"],
    },
    "Paquibato": {
        "basin": "Rural & Highland Catchments",
        "aliases": [
            "paquibato", "paquibato proper", "brgy paquibato", "barangay paquibato",
            "paquibato district"
        ],
        "sub_areas": ["malabog", "mapula", "colosas"],
    },
    "Calinan": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["calinan", "calinan proper", "brgy calinan", "barangay calinan"],
        "sub_areas": ["subasta", "sirib", "tamugan"],
    },
    "Toril": {
        "basin": "Talomo & Lipadas River Catchments",
        "aliases": ["toril", "toril proper", "brgy toril", "barangay toril"],
        "sub_areas": [],
    },

    # ── 4. Bunawan & Lasang River Basins (District 2) ───────────────────
    "Bunawan Proper": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["bunawan", "bunawan proper", "brgy bunawan", "barangay bunawan"],
        "sub_areas": [],
    },
    "Lasang": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["lasang", "brgy lasang", "barangay lasang"],
        "sub_areas": [],
    },
    "Mudiang": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["mudiang", "brgy mudiang", "barangay mudiang"],
        "sub_areas": [],
    },
    "Ilangan": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["ilangan", "brgy ilangan", "barangay ilangan"],
        "sub_areas": [],
    },
    "San Isidro": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["san isidro", "new san isidro", "brgy san isidro", "barangay san isidro"],
        "sub_areas": [],
    },
    "Cabantian": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["cabantian", "brgy cabantian", "barangay cabantian"],
        "sub_areas": ["suraya homes", "suraya", "greenland", "greenland subdivision", "greenland subd"],
    },
    "Tibungco": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["tibungco", "brgy tibungco", "barangay tibungco"],
        "sub_areas": [],
    },
    "Mahayag": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["mahayag", "brgy mahayag", "barangay mahayag"],
        "sub_areas": [],
    },
    "Gatungan": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["gatungan", "brgy gatungan", "barangay gatungan"],
        "sub_areas": [],
    },
    "Alejal": {
        "basin": "Bunawan & Lasang River Basins",
        "aliases": ["alejal", "brgy alejal", "barangay alejal"],
        "sub_areas": [],
    },

    # ── 5. Low-Lying Coastal & Urban Drainage Bottlenecks ───────────────
    "Agdao Proper": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["agdao", "agdao proper", "agdao creek", "brgy agdao", "barangay agdao"],
        "sub_areas": [],
    },
    "Sasa": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["sasa", "brgy sasa", "barangay sasa"],
        "sub_areas": ["davao impress", "davao impress subdivision"],
    },
    "Panacan": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["panacan", "brgy panacan", "barangay panacan"],
        "sub_areas": [],
    },
    "Pampanga": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["pampanga", "brgy pampanga", "barangay pampanga"],
        "sub_areas": [],
    },
    "Centro (San Antonio)": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["centro", "san antonio", "brgy centro", "barangay centro"],
        "sub_areas": [],
    },
    "Gov. Vicente Duterte": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["gov. vicente duterte", "gov vicente duterte", "vicente duterte"],
        "sub_areas": [],
    },
    "Leon Garcia": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["leon garcia", "brgy leon garcia", "barangay leon garcia"],
        "sub_areas": [],
    },
    "Lapu-Lapu": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["lapu-lapu", "lapu lapu", "brgy lapu-lapu", "barangay lapu-lapu"],
        "sub_areas": [],
    },
    "Kap. Tomas Monteverde Sr.": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["tomas monteverde", "monteverde", "kap tomas monteverde"],
        "sub_areas": [],
    },
    "Barangay 21-C": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["21-c", "brgy 21-c", "barangay 21-c", "brgy. 21-c"],
        "sub_areas": [],
    },
    "Barangay 22-C": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["22-c", "brgy 22-c", "barangay 22-c", "brgy. 22-c"],
        "sub_areas": [],
    },
    "Barangay 23-C": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["23-c", "brgy 23-c", "barangay 23-c", "brgy. 23-c"],
        "sub_areas": [],
    },
    "Barangay 27-C": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["27-c", "brgy 27-c", "barangay 27-c", "brgy. 27-c"],
        "sub_areas": [],
    },
    "Barangay 31-D": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["31-d", "brgy 31-d", "barangay 31-d", "brgy. 31-d"],
        "sub_areas": [],
    },
    "Barangay 37-D": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["37-d", "brgy 37-d", "barangay 37-d", "brgy. 37-d"],
        "sub_areas": [],
    },
    "Barangay 38-D": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["38-d", "brgy 38-d", "barangay 38-d", "brgy. 38-d"],
        "sub_areas": [],
    },
    "Barangay 39-D": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["39-d", "brgy 39-d", "barangay 39-d", "brgy. 39-d"],
        "sub_areas": [],
    },
    "Barangay 40-D": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["40-d", "brgy 40-d", "barangay 40-d", "brgy. 40-d"],
        "sub_areas": [],
    },
    "Bajada": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["bajada", "j.p. laurel", "jp laurel"],
        "sub_areas": [],
    },
    "Lanang": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["lanang", "brgy lanang", "barangay lanang"],
        "sub_areas": [],
    },
    "Buhangin Proper": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["buhangin", "buhangin proper", "brgy buhangin", "barangay buhangin"],
        "sub_areas": ["laverna", "la verna", "laverna hills", "country homes"],
    },
    "Poblacion": {
        "basin": "Low-Lying Coastal & Urban Drainage Bottlenecks",
        "aliases": ["poblacion", "davao downtown", "downtown davao"],
        "sub_areas": [],
    },
    "Davao City": {
        "basin": "General Scope",
        "aliases": ["davao city", "davao"],
        "sub_areas": [],
    },
}


# ═══════════════════════════════════════════════════════════════════════
# Compiled Regex Search Patterns
# ═══════════════════════════════════════════════════════════════════════

# Build pattern mappings: (compiled_regex, canonical_name)
_GAZETTEER_PATTERNS: List[Tuple[re.Pattern, str]] = []

for canonical_name, data in FLOOD_PRONE_GAZETTEER.items():
    terms = set(data["aliases"] + data["sub_areas"])
    # Sort terms by descending length to match longest matches first (e.g. "Matina Crossing" before "Matina")
    sorted_terms = sorted(terms, key=len, reverse=True)
    for term in sorted_terms:
        # Match as whole word / phrase boundaries
        escaped = re.escape(term)
        pattern = re.compile(rf"(?<!\w){escaped}(?!\w)", re.IGNORECASE)
        _GAZETTEER_PATTERNS.append((pattern, canonical_name))


def match_gazetteer_in_text(text: str) -> List[str]:
    """
    Search raw text for known flood-prone locations in Davao City.

    Returns:
        List of unique canonical location names found in text in order of appearance.
    """
    if not text:
        return []

    found = []
    seen = set()

    for pattern, canonical in _GAZETTEER_PATTERNS:
        match = pattern.search(text)
        if match and canonical not in seen:
            # Exclude generic "Davao City" if more specific barangays were found
            seen.add(canonical)
            found.append((match.start(), canonical))

    # Sort by appearance in text
    found.sort(key=lambda x: x[0])
    results = [canonical for _, canonical in found]

    # If specific locations were found, keep "Davao City" only if explicitly desired
    # (keeps output focused on localized barangay level)
    if len(results) > 1 and "Davao City" in results:
        results.remove("Davao City")
        results.append("Davao City")

    return results


def resolve_model_prediction(
    predicted_text: str,
    original_text: str = "",
) -> Optional[str]:
    """
    Resolve and repair a model-extracted location piece (such as 'buna', 'dava')
    against the gazetteer.

    Args:
        predicted_text: Substring predicted by XLM-RoBERTa (e.g. 'buna', 'dava').
        original_text: The full post text for context.

    Returns:
        Canonical location name if matched, or the cleaned predicted_text if unlisted.
    """
    pred_clean = predicted_text.strip().lower()
    if not pred_clean or len(pred_clean) < 2:
        return None

    # 1. Exact match against gazetteer aliases
    for canonical, data in FLOOD_PRONE_GAZETTEER.items():
        all_terms = data["aliases"] + data["sub_areas"] + [canonical.lower()]
        for term in all_terms:
            if pred_clean == term.lower():
                return canonical

    # 2. Check for prefix cutoffs (e.g., 'buna' for 'bunawan', 'dava' for 'davao')
    for canonical, data in FLOOD_PRONE_GAZETTEER.items():
        all_terms = data["aliases"] + data["sub_areas"] + [canonical.lower()]
        for term in all_terms:
            # If the predicted text is a prefix of a known term (len >= 3)
            # or if the term starts with the prediction
            if len(pred_clean) >= 3 and (term.lower().startswith(pred_clean) or pred_clean.startswith(term.lower())):
                # If original_text is provided, verify the full term exists in text
                if original_text and term.lower() in original_text.lower():
                    return canonical
                elif not original_text:
                    return canonical

    # 3. If no gazetteer match, return Title Case of cleaned predicted span
    return predicted_text.strip().title()


def extract_hybrid_locations(
    model_locations: List[str],
    raw_text: str,
) -> List[str]:
    """
    Hybrid extraction combining Gazetteer matching with model predictions:
      1. Finds all known flood-prone locations mentioned in the post via Gazetteer
      2. Resolves and normalizes any model-extracted spans (fixing truncations)
      3. Preserves novel/unlisted streets or landmarks extracted by the model
      4. Returns deduplicated canonical locations

    Args:
        model_locations: List of locations extracted by XLM-RoBERTa NER head.
        raw_text: Raw social media post text.

    Returns:
        List of finalized, clean location strings.
    """
    final_locations: List[str] = []
    seen: Set[str] = set()

    # Step 1: Direct Gazetteer extraction from text (High Precision)
    gazetteer_matches = match_gazetteer_in_text(raw_text)
    for loc in gazetteer_matches:
        if loc.lower() not in seen:
            seen.add(loc.lower())
            final_locations.append(loc)

    # Step 2: Normalize and integrate model-extracted spans (High Recall)
    for model_loc in model_locations:
        resolved = resolve_model_prediction(model_loc, original_text=raw_text)
        if resolved and resolved.lower() not in seen:
            # Filter out non-location noise (e.g. single letters or pronouns)
            if len(resolved) > 2 and resolved.lower() not in {"the", "and", "city", "barangay", "brgy"}:
                seen.add(resolved.lower())
                final_locations.append(resolved)

    return final_locations


def get_location_search_terms(locations: List[str]) -> List[str]:
    """
    Expand a list of location names to include their aliases and sub-terms for robust matching.
    Returns search strings sorted by descending length.
    """
    terms = set()
    for loc in locations:
        loc_clean = loc.strip()
        if not loc_clean:
            continue
        terms.add(loc_clean)
        # If in gazetteer, add all aliases and sub-areas
        for canonical, data in FLOOD_PRONE_GAZETTEER.items():
            if loc_clean.lower() == canonical.lower() or any(loc_clean.lower() == a.lower() for a in data["aliases"]):
                terms.add(canonical)
                for a in data["aliases"]:
                    terms.add(a)
                for s in data["sub_areas"]:
                    terms.add(s)
    return sorted(terms, key=len, reverse=True)

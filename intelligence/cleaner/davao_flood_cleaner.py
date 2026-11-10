"""
Davao City Flood Report Data Cleaner
=====================================
Filters scraped Facebook flood data to keep only genuine flood reports
about Davao City, removing false positives caught by keyword matching.

Usage:
    python davao_flood_cleaner.py
    python davao_flood_cleaner.py --input davao_fb_flood_reports.json
    python davao_flood_cleaner.py --input davao_fb_flood_reports.csv
    python davao_flood_cleaner.py --input data.json --output-dir ./cleaned
    python davao_flood_cleaner.py --strict   # More aggressive filtering
"""

import argparse
import csv
import json
import os
import re
import sys
import unicodedata
from collections import Counter, defaultdict
from datetime import datetime
from difflib import SequenceMatcher


# ═══════════════════════════════════════════════════════════════════════════════
# CONFIGURATION
# ═══════════════════════════════════════════════════════════════════════════════

# Similarity threshold for near-deduplication (0.0 to 1.0)
DEDUP_SIMILARITY_THRESHOLD = 0.85

# Minimum text length after cleaning to be considered a valid post
MIN_TEXT_LENGTH = 15

# ── Davao City locations ──────────────────────────────────────────────────────
# Barangays and areas within Davao City proper that the scraper monitors
DAVAO_LOCATIONS = [
    # District 1
    "matina pangi", "matina crossing", "matina aplaya", "matina gravahan",
    "matina", "bucana", "brgy 76-a", "bangkal", "ma-a", "maa", "m a-a",
    "bankerohan", "agdao", "talomo", "quimpo", "sandawa", "r. castillo",
    "r castillo",
    # District 2
    "buhangin", "tigatto", "jade valley", "cabantian", "bunawan", "sasa",
    "lasang", "pampanga", "communal", "la verna", "laverna", "mandug",
    "indangan", "panacan", "lanang", "bajada",
    # District 3
    "calinan", "waan", "new carmen", "toril", "tugbok", "catalunan grande",
    "catalunan pequeño", "catalunan pequeno", "catalunan", "mintal",
    "bago aplaya", "daliaon", "sirawan", "crossing bayabas", "bayabas",
    "eden", "talandang",
    # General Davao references
    "davao city", "davao del sur",
    # Rivers and landmarks
    "davao river", "matina river", "talomo river", "lasang river",
    # Subdivisions / areas frequently flooded
    "susana homes", "nha buhangin", "relocation site",
]

# ── Non-Davao locations ──────────────────────────────────────────────────────
# Cities/provinces that are NOT Davao City — used to detect off-topic flood posts
NON_DAVAO_LOCATIONS = [
    # Cebu
    "cebu", "catmon", "mandaue", "lapu-lapu", "talisay cebu",
    # Agusan / Caraga
    "agusan del sur", "agusan del norte", "butuan", "bayugan",
    # Cagayan / Northern Luzon
    "cagayan valley", "cagayan de oro", "tuguegarao", "isabela",
    # Manila / NCR
    "manila", "quezon city", "marikina", "pasig", "taguig", "makati",
    "caloocan", "malabon", "navotas", "valenzuela",
    # Davao Region but not Davao City
    "davao del norte", "davao oriental", "davao occidental",
    "davao de oro", "compostela valley",
    "tagum", "panabo", "carmen davao del norte", "digos",
    # Cotabato / Maguindanao
    "cotabato", "maguindanao", "sultan kudarat",
    # Other Mindanao
    "zamboanga", "iligan", "marawi", "general santos", "gensan",
    "koronadal", "kidapawan", "surigao",
    # Visayas
    "iloilo", "bacolod", "tacloban", "leyte", "samar", "bohol",
    # International
    "hyderabad", "india", "bangladesh", "myanmar",
]

# ── Flood indicator keywords ─────────────────────────────────────────────────
# Words that suggest a post is about an actual flood event
FLOOD_INDICATORS = [
    # Cebuano / Bisaya
    "baha", "binaha", "nabaha", "gibaha", "lunop", "nalunod",
    "naanud", "tubig sapa", "taas tubig", "taas ang tubig",
    "tubig baha", "nagbaha", "pag-apaw", "miapaw", "mibundak",
    "nagasaka ang tubig", "saka tubig", "nagasaka", "nagtaas ang tubig",
    "kusog nga ulan", "nagdako ang tubig",
    # Tagalog
    "baha", "bumaha", "binaha", "nabaha", "pagbaha",
    "taas ng tubig", "mataas ang tubig", "tubig baha",
    "apaw", "umapaw", "pagapaw", "umaapaw",
    "lumubog", "nalubog", "naanod",
    # English
    "flood", "flooded", "flooding", "floodwater", "flood water",
    "flash flood", "submerged", "inundated", "inundation",
    "water level", "rising water", "overflowed", "overflow",
]

# ── Negation patterns ────────────────────────────────────────────────────────
# Phrases that NEGATE flood presence — usually in real estate or promotional context
FLOOD_NEGATION_PATTERNS = [
    r"layo\s+sa\s+baha",           # far from flood
    r"walay\s+baha",               # no flood
    r"walang\s+baha",              # no flood (Tagalog)
    r"wala\s+(?:nay?\s+)?baha",    # no flood (Bisaya variations)
    r"dili\s+(?:ma)?bahaan",       # won't flood
    r"hindi\s+(?:ma)?bahain",      # won't flood (Tagalog)
    r"flood[\s-]*free",            # flood-free
    r"no\s+flood",                 # no flood
    r"not\s+flood(?:ed|ing)?",     # not flooded/flooding
    r"(?:di|dili)\s+mabaha",       # won't flood
    r"safe\s+(?:sa|from)\s+(?:baha|flood)", # safe from flood
]

# ── Real estate / property sale patterns ──────────────────────────────────────
REAL_ESTATE_PATTERNS = [
    r"\b(?:for\s+)?(?:sale|rent|lease)\b",
    r"\b(?:lot|house\s+and\s+lot|townhouse|condo|apartment)\s+(?:for|only)\b",
    r"\bsqm\b",
    r"\bsq(?:uare)?\s*m(?:eter)?s?\b",
    r"\bspotcash\b",
    r"\bspot\s*cash\b",
    r"\bdownpayment\b",
    r"\bdown\s*payment\b",
    r"\binstallment\b",
    r"\bmonthly\s+(?:amortization|payment)\b",
    r"\bhulugang?\b",                        # installment (Tagalog)
    r"\bpag[\s-]?ibig\s+(?:fund|financing|housing)\b",
    r"\brfo\b",                              # ready for occupancy
    r"\bready\s+for\s+occupancy\b",
    r"\bclean\s+title\b",
    r"\btax\s+dec(?:laration)?\b",
    r"\btitle\s+(?:transfer|ready)\b",
    r"\bsite\s+viewing\b",
    r"\bfree\s+site\s+viewing\b",
    r"\bsubdi(?:vision)?\b",
    r"\b(?:corner|inner|prime)\s+lot\b",
    r"\b\d+\s*k\s+(?:only|spot|per)\b",      # price patterns like "50k only"
    r"\b(?:price|asking|selling)\s*:\s*\d",
    r"\bavail\s+na(?:mo)?\b",                # "avail now"
    r"\blot\s+(?:area|size|only)\b",
    r"\bcommercial\s+lot\b",
    r"\bresidential\s+lot\b",
    r"\bfree\s+(?:tripping|viewing)\b",
]

# ── Business / food advertisement patterns ────────────────────────────────────
BUSINESS_AD_PATTERNS = [
    r"\bopen\s+(?:monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b",
    r"\b(?:monday|mon)\s+to\s+(?:sunday|sun|saturday|sat)\b",
    r"\bdelivery\s+(?:service|available|nationwide)\b",
    r"\border\s+(?:now|here|online)\b",
    r"\bmenu\b.*\b(?:price|peso|php)\b",
    r"\bfree\s+delivery\b",
    r"\b(?:sisig|lechon|bbq|burger|pizza|milktea|milk\s*tea|food|kainan)\b"
    r".*\b(?:order|delivery|promo|price|menu)\b",
    r"\bam\s+to\s+\d+\s*pm\b",
    r"\b\d+\s*am\s+to\s+\d+\s*pm\b",
    r"\b(?:call|text|viber|order)\s*(?::|/)\s*\d{4}",
    r"\bgcash\b",
    r"\bcod\s+(?:available|accepted)\b",
    r"\bpromo\s+(?:price|rate)\b",
]

# ── Promotional / institutional patterns ──────────────────────────────────────
PROMOTIONAL_PATTERNS = [
    r"\b(?:anibersaryo|anniversary|aniversary)\b",
    r"\binaugurat(?:ion|ed)\b",
    r"\blaunching\b",
    r"\bribbon\s+cutting\b",
    r"\bgrand\s+opening\b",
    r"\bcelebrat(?:ion|ing|es?)\b.*\b(?:year|founding|birthday)\b",
    r"\bhappy\s+(?:\d+\s*)?(?:st|nd|rd|th)\b",
    r"\bsoon\s+to\s+open\b",
    r"\bnow\s+(?:hiring|accepting|open(?:ing)?)\b",
    r"\bjob\s+(?:hiring|opening|vacancy)\b",
]


# ═══════════════════════════════════════════════════════════════════════════════
# TEXT PREPROCESSING
# ═══════════════════════════════════════════════════════════════════════════════

def clean_text(text: str) -> str:
    """
    Strip Facebook UI artifacts and scraper noise from post text,
    returning the meaningful content for classification.
    """
    if not text:
        return ""

    # Remove "Facebook\n" prefix that the scraper prepends
    text = re.sub(r"^Facebook\n", "", text)

    # Remove "See translation" prompts
    text = re.sub(r"\bSee translation\b", "", text, flags=re.IGNORECASE)

    # Remove m.me links
    text = re.sub(r"\bm\.me\b", "", text)

    # Remove "Photos from ...'s post" lines
    text = re.sub(r"Photos from .+?'s post", "", text)

    # Remove encoded/garbled strings (long sequences of alphanumeric gibberish)
    # These are Facebook-internal tracking parameters
    text = re.sub(r"\b[A-Za-z0-9]{30,}\b", "", text)

    # Remove zero-width and invisible Unicode characters
    text = re.sub(r"[\u200b\u200c\u200d\u200e\u200f\u034f\ufeff]", "", text)

    # Remove the "sponsored" / engagement count lines (e.g., "554\n362")
    text = re.sub(r"\n\d+\n\d+\s*$", "", text)

    # Remove "d͏s͏r͏n͏t͏..." style obfuscated text (Unicode combining chars)
    text = re.sub(r"(?:[a-zA-Z0-9]͏)+[a-zA-Z0-9]?", "", text)

    # Remove "See less" and deduplicate content that appears twice
    # The scraper often captures the same text before and after "See less"
    text = re.sub(r"\s*See less\s*", "\n", text, flags=re.IGNORECASE)

    # Collapse multiple newlines and trim
    text = re.sub(r"\n{2,}", "\n", text)
    text = text.strip()

    # Deduplicate: if the text is the same content repeated, keep one copy
    text = _deduplicate_repeated_content(text)

    return text


def _deduplicate_repeated_content(text: str) -> str:
    """
    The scraper sometimes captures content twice (before/after 'See less').
    If the first half ≈ second half, keep only one copy.
    """
    lines = text.split("\n")
    if len(lines) < 4:
        return text

    mid = len(lines) // 2
    first_half = "\n".join(lines[:mid]).strip()
    second_half = "\n".join(lines[mid:]).strip()

    if not first_half or not second_half:
        return text

    ratio = SequenceMatcher(None, first_half, second_half).ratio()
    if ratio > 0.80:
        # Keep the longer half (usually has slightly more context)
        return first_half if len(first_half) >= len(second_half) else second_half

    return text


def get_text_without_author(post: dict) -> str:
    """
    Return cleaned text with the author name removed,
    so we don't falsely match on author-name-only mentions of locations.
    """
    text = clean_text(post.get("full_text", ""))
    author = post.get("author", "")
    if author and author != "Unknown":
        # Remove author name from text to avoid false location matches
        text = text.replace(author, "")
    return text


# ═══════════════════════════════════════════════════════════════════════════════
# FILTER FUNCTIONS
# ═══════════════════════════════════════════════════════════════════════════════

def _matches_any_pattern(text: str, patterns: list[str]) -> bool:
    """Check if text matches any regex pattern in the list."""
    text_lower = text.lower()
    for pattern in patterns:
        if re.search(pattern, text_lower, re.IGNORECASE):
            return True
    return False


def _count_pattern_matches(text: str, patterns: list[str]) -> int:
    """Count how many patterns from the list match the text."""
    text_lower = text.lower()
    count = 0
    for pattern in patterns:
        if re.search(pattern, text_lower, re.IGNORECASE):
            count += 1
    return count


# ── Filter Layer 1a: Real Estate Ads ─────────────────────────────────────────

def is_real_estate_ad(text: str) -> tuple[bool, str]:
    """
    Detect property sale / real estate posts.
    These commonly mention 'baha' to say "layo sa baha" (far from flood).
    Returns (is_ad, reason).
    """
    matches = _count_pattern_matches(text, REAL_ESTATE_PATTERNS)
    if matches >= 3:
        return True, f"Real estate ad ({matches} property-sale indicators)"
    return False, ""


# ── Filter Layer 1b: Business / Food Ads ─────────────────────────────────────

def is_business_ad(text: str) -> tuple[bool, str]:
    """
    Detect business or food service advertisements.
    Returns (is_ad, reason).
    """
    matches = _count_pattern_matches(text, BUSINESS_AD_PATTERNS)
    if matches >= 2:
        return True, f"Business/food ad ({matches} commercial indicators)"
    return False, ""


# ── Filter Layer 1c: Promotional / Institutional ─────────────────────────────

def is_promotional(text: str) -> tuple[bool, str]:
    """
    Detect anniversary celebrations, grand openings, job postings, etc.
    Returns (is_promotional, reason).
    """
    if _matches_any_pattern(text, PROMOTIONAL_PATTERNS):
        # Extra check: if the post also has flood indicators in reporting context,
        # it might be a legitimate post mentioning "celebrating" something else
        flood_hits = _count_pattern_matches(text, [
            re.escape(kw) for kw in FLOOD_INDICATORS
        ])
        if flood_hits == 0:
            return True, "Promotional/institutional post (no flood content)"
    return False, ""


# ── Filter Layer 1d: Foreign Language ─────────────────────────────────────────

def is_foreign_language(text: str) -> tuple[bool, str]:
    """
    Detect posts in non-Filipino/English scripts (Telugu, Hindi, etc.).
    These get caught by queries like 'TV5 news davao flood'.
    Returns (is_foreign, reason).
    """
    # Count characters by Unicode script category
    total_alpha = 0
    non_latin = 0

    for char in text:
        if unicodedata.category(char).startswith("L"):  # Letter
            total_alpha += 1
            try:
                name = unicodedata.name(char, "")
            except ValueError:
                name = ""
            # Check for non-Latin scripts
            if any(script in name for script in [
                "TELUGU", "DEVANAGARI", "BENGALI", "TAMIL", "KANNADA",
                "MALAYALAM", "GUJARATI", "ORIYA", "GURMUKHI", "ARABIC",
                "THAI", "CHINESE", "JAPANESE", "KOREAN",
            ]):
                non_latin += 1

    if total_alpha > 0 and (non_latin / total_alpha) > 0.30:
        return True, f"Foreign language script ({non_latin}/{total_alpha} non-Latin characters)"
    return False, ""


# ── Filter Layer 2: Location Relevance ────────────────────────────────────────

def is_non_davao_flood(post: dict, cleaned_text: str) -> tuple[bool, str]:
    """
    Check if a flood post is about a location OUTSIDE Davao City.
    A post is considered non-Davao if:
    - It mentions a non-Davao location AND
    - It does NOT mention any Davao City barangay/area in the text body
      (excluding author name which might contain 'Davao')
    Returns (is_non_davao, reason).
    """
    text_lower = cleaned_text.lower()
    text_no_author = get_text_without_author(post).lower()

    # Check for non-Davao locations in the text
    found_non_davao = []
    for loc in NON_DAVAO_LOCATIONS:
        if loc.lower() in text_lower:
            found_non_davao.append(loc)

    if not found_non_davao:
        return False, ""

    # Check if any Davao City location is mentioned in the text body
    # (excluding author name to avoid false positives from page names like "Brigada Davao")
    found_davao = []
    for loc in DAVAO_LOCATIONS:
        if loc.lower() in text_no_author:
            found_davao.append(loc)

    # Also check for generic "davao" in text body (not just author name)
    if "davao" in text_no_author:
        # But exclude cases where "davao" only appears as part of a non-Davao location
        davao_in_text = True
        for non_dav in found_non_davao:
            if "davao" in non_dav.lower():
                # "davao del norte", "davao oriental", etc.
                temp_text = text_no_author
                for nd in found_non_davao:
                    temp_text = temp_text.replace(nd.lower(), "")
                if "davao" not in temp_text:
                    davao_in_text = False
                break
        if davao_in_text:
            found_davao.append("davao (generic)")

    if found_non_davao and not found_davao:
        return True, f"Non-Davao flood (mentions: {', '.join(found_non_davao)})"

    return False, ""


# ── Filter Layer 3: Negated Flood References ──────────────────────────────────

def is_negated_flood(text: str) -> tuple[bool, str]:
    """
    Detect posts where flood is mentioned in a NEGATED context,
    e.g., 'layo sa baha' (far from flood), 'walang baha' (no flood).
    Only triggers if the negation is the PRIMARY flood reference.
    Returns (is_negated, reason).
    """
    text_lower = text.lower()

    negation_count = 0
    for pattern in FLOOD_NEGATION_PATTERNS:
        if re.search(pattern, text_lower, re.IGNORECASE):
            negation_count += 1

    if negation_count == 0:
        return False, ""

    # Count actual (non-negated) flood indicators
    # Remove negation phrases first, then check for remaining flood keywords
    text_without_negations = text_lower
    for pattern in FLOOD_NEGATION_PATTERNS:
        text_without_negations = re.sub(pattern, "", text_without_negations, flags=re.IGNORECASE)

    actual_flood_hits = 0
    for kw in FLOOD_INDICATORS:
        if kw.lower() in text_without_negations:
            actual_flood_hits += 1

    # If all flood references are negated, it's not a flood report
    if actual_flood_hits == 0:
        return True, f"Flood reference is negated ({negation_count} negation pattern(s), 0 actual flood indicators)"

    return False, ""


# ── Filter Layer 4: Flood Event Confirmation ──────────────────────────────────

def has_flood_content(text: str) -> tuple[bool, str]:
    """
    Verify the post actually contains flood-related content.
    Some posts get scraped due to page names or hashtags but have
    no actual flood information.
    Returns (has_content, reason_if_no).
    """
    text_lower = text.lower()

    for kw in FLOOD_INDICATORS:
        if kw.lower() in text_lower:
            return True, ""

    # Also check for flood-adjacent content that's still relevant:
    # weather advisories, DCRRMO updates, evacuation, rescue
    secondary_indicators = [
        "ulan", "rain", "heavy rain", "kusog nga ulan",
        "evacuation", "evacuate", "bakwit",
        "rescue", "stranded", "trapped",
        "water level", "lebel sa tubig",
        "landslide", "pagdahili",
        "storm", "bagyo", "typhoon", "tropical",
        "dcrrmo", "disaster", "calamity",
    ]
    for kw in secondary_indicators:
        if kw in text_lower:
            return True, ""

    return False, "No flood or disaster content detected"


# ── Filter Layer 5: Minimum Content ──────────────────────────────────────────

def has_sufficient_content(text: str) -> tuple[bool, str]:
    """
    Reject posts with too little text to be meaningful after cleaning.
    Returns (has_content, reason_if_no).
    """
    if len(text.strip()) < MIN_TEXT_LENGTH:
        return False, f"Insufficient content ({len(text.strip())} chars, minimum {MIN_TEXT_LENGTH})"
    return True, ""


# ═══════════════════════════════════════════════════════════════════════════════
# DEDUPLICATION
# ═══════════════════════════════════════════════════════════════════════════════

def _text_to_shingles(text: str, k: int = 4) -> set[str]:
    """Convert text to a set of word-level k-shingles for fast bucketing."""
    words = text.lower().split()
    if len(words) < k:
        return {" ".join(words)}
    return {" ".join(words[i:i+k]) for i in range(len(words) - k + 1)}


def deduplicate_posts(posts: list[dict]) -> tuple[list[dict], list[dict]]:
    """
    Remove near-duplicate posts using hash-bucketed shingling.
    Only compares posts that share common text shingles, making it
    dramatically faster than brute-force pairwise comparison.
    Returns (unique_posts, duplicates_removed).
    """
    unique = []
    duplicates = []

    # Store (index_in_unique, cleaned_text, shingle_set) for each unique post
    unique_entries: list[tuple[int, str, set[str]]] = []
    # Map shingles -> indices into unique_entries for fast candidate lookup
    shingle_index: dict[str, list[int]] = defaultdict(list)

    for post in posts:
        text = clean_text(post.get("full_text", ""))
        if not text:
            duplicates.append((post, "Empty text after cleaning"))
            continue

        shingles = _text_to_shingles(text)

        # Find candidate duplicates: entries that share at least one shingle
        candidate_indices: set[int] = set()
        for shingle in shingles:
            if shingle in shingle_index:
                candidate_indices.update(shingle_index[shingle])

        is_dup = False
        for idx in candidate_indices:
            _, seen_text, seen_shingles = unique_entries[idx]
            # Quick Jaccard pre-check before expensive SequenceMatcher
            intersection = len(shingles & seen_shingles)
            union = len(shingles | seen_shingles)
            if union > 0 and (intersection / union) > 0.3:
                ratio = SequenceMatcher(None, text, seen_text).ratio()
                if ratio >= DEDUP_SIMILARITY_THRESHOLD:
                    is_dup = True
                    duplicates.append((post, f"Near-duplicate (similarity: {ratio:.2f})"))
                    break

        if not is_dup:
            entry_idx = len(unique_entries)
            unique_entries.append((entry_idx, text, shingles))
            # Only index a sample of shingles to keep memory bounded
            sample_shingles = list(shingles)[:20]
            for shingle in sample_shingles:
                shingle_index[shingle].append(entry_idx)
            unique.append(post)

    return unique, duplicates


# ═══════════════════════════════════════════════════════════════════════════════
# MAIN CLEANING PIPELINE
# ═══════════════════════════════════════════════════════════════════════════════

def classify_post(post: dict, strict: bool = False) -> tuple[str, str]:
    """
    Run a post through all filter layers.
    Returns:
        ("keep", "") if the post should be kept
        ("remove", reason) if the post should be removed
    """
    raw_text = post.get("full_text", "")
    text = clean_text(raw_text)

    # ── Layer 0: Minimum content check ────────────────────────────────────
    ok, reason = has_sufficient_content(text)
    if not ok:
        return "remove", reason

    # ── Layer 1a: Foreign language ────────────────────────────────────────
    is_foreign, reason = is_foreign_language(text)
    if is_foreign:
        return "remove", reason

    # ── Layer 1b: Real estate ad ──────────────────────────────────────────
    is_realestate, reason = is_real_estate_ad(text)
    if is_realestate:
        return "remove", reason

    # ── Layer 1c: Business / food ad ──────────────────────────────────────
    is_bizad, reason = is_business_ad(text)
    if is_bizad:
        return "remove", reason

    # ── Layer 1d: Promotional / institutional ─────────────────────────────
    is_promo, reason = is_promotional(text)
    if is_promo:
        return "remove", reason

    # ── Layer 2: Location relevance ───────────────────────────────────────
    is_outside, reason = is_non_davao_flood(post, text)
    if is_outside:
        return "remove", reason

    # ── Layer 3: Negated flood reference ──────────────────────────────────
    is_neg, reason = is_negated_flood(text)
    if is_neg:
        return "remove", reason

    # ── Layer 4: Flood event confirmation ─────────────────────────────────
    has_flood, reason = has_flood_content(text)
    if not has_flood:
        return "remove", reason

    return "keep", ""


def clean_data(
    posts: list[dict],
    strict: bool = False,
) -> tuple[list[dict], dict]:
    """
    Run the full cleaning pipeline on a list of posts.

    Returns:
        (cleaned_posts, report_data)
    """
    report = {
        "total_input": len(posts),
        "removed_by_layer": defaultdict(list),
        "kept": 0,
        "removed_total": 0,
        "audit_by_post_id": {},
    }

    # Phase 1: Run classification filters
    passed_posts = []
    for post in posts:
        pid = post.get("post_id", "?")
        cleaned = clean_text(post.get("full_text", ""))
        post["cleaned_text"] = cleaned

        decision, reason = classify_post(post, strict=strict)
        if decision == "remove":
            # Categorize the removal reason
            category = reason.split("(")[0].strip()
            report["removed_by_layer"][category].append({
                "post_id": pid,
                "author": post.get("author", "?"),
                "reason": reason,
                "text_preview": cleaned[:150],
            })
            report["audit_by_post_id"][pid] = {
                "post_id": pid,
                "cleaned_text": cleaned,
                "clean_status": "removed",
                "removal_category": category,
                "removal_reason": reason,
            }
        else:
            passed_posts.append(post)

    # Phase 2: Deduplication
    unique_posts, duplicates = deduplicate_posts(passed_posts)
    for dup_post, dup_reason in duplicates:
        pid = dup_post.get("post_id", "?")
        cleaned = dup_post.get("cleaned_text") or clean_text(dup_post.get("full_text", ""))
        report["removed_by_layer"]["Near-duplicate"].append({
            "post_id": pid,
            "author": dup_post.get("author", "?"),
            "reason": dup_reason,
            "text_preview": cleaned[:150],
        })
        report["audit_by_post_id"][pid] = {
            "post_id": pid,
            "cleaned_text": cleaned,
            "clean_status": "removed",
            "removal_category": "Near-duplicate",
            "removal_reason": dup_reason,
        }

    for kept_post in unique_posts:
        pid = kept_post.get("post_id", "?")
        cleaned = kept_post.get("cleaned_text") or clean_text(kept_post.get("full_text", ""))
        report["audit_by_post_id"][pid] = {
            "post_id": pid,
            "cleaned_text": cleaned,
            "clean_status": "kept",
            "removal_category": None,
            "removal_reason": None,
        }

    report["kept"] = len(unique_posts)
    report["removed_total"] = report["total_input"] - len(unique_posts)

    return unique_posts, report


# ═══════════════════════════════════════════════════════════════════════════════
# INPUT / OUTPUT
# ═══════════════════════════════════════════════════════════════════════════════

def load_data(filepath: str) -> list[dict]:
    """
    Load posts from either JSON or CSV file.
    Automatically parses list fields and handles UTF-8 BOM if present.
    """
    ext = os.path.splitext(filepath)[1].lower()
    if ext == ".csv":
        posts = []
        with open(filepath, "r", encoding="utf-8-sig", newline="") as f:
            reader = csv.DictReader(f)
            for row in reader:
                post = dict(row)
                # Convert semicolon-separated fields back to lists
                if "detected_locations" in post and isinstance(post["detected_locations"], str):
                    val = post["detected_locations"].strip()
                    post["detected_locations"] = [x.strip() for x in val.split(";") if x.strip()] if val else []
                if "media_urls" in post and isinstance(post["media_urls"], str):
                    val = post["media_urls"].strip()
                    post["media_urls"] = [x.strip() for x in val.split(";") if x.strip()] if val else []
                for num_key in ("relative_seconds", "relative_minutes", "relative_hours"):
                    if num_key in post and post[num_key] is not None and post[num_key] != "":
                        try:
                            post[num_key] = float(post[num_key]) if "." in post[num_key] else int(post[num_key])
                        except (ValueError, TypeError):
                            pass
                posts.append(post)
        return posts
    else:
        with open(filepath, "r", encoding="utf-8") as f:
            return json.load(f)


def save_json(posts: list[dict], filepath: str) -> None:
    """Save posts to a JSON file."""
    with open(filepath, "w", encoding="utf-8") as f:
        json.dump(posts, f, indent=2, ensure_ascii=False)
    print(f"  [OK] JSON saved: {filepath} ({len(posts)} posts)")


def save_csv(posts: list[dict], filepath: str) -> None:
    """Save posts to a CSV file."""
    if not posts:
        print(f"  [!] No posts to save to CSV")
        return

    fieldnames = list(posts[0].keys())
    with open(filepath, "w", encoding="utf-8", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        for post in posts:
            # Convert lists to strings for CSV compatibility
            row = {}
            for k, v in post.items():
                if isinstance(v, list):
                    row[k] = "; ".join(str(item) for item in v)
                else:
                    row[k] = v
            writer.writerow(row)
    print(f"  [OK] CSV saved:  {filepath} ({len(posts)} posts)")


def generate_report(report: dict, filepath: str) -> None:
    """Generate a human-readable cleaning report."""
    lines = []
    lines.append("=" * 70)
    lines.append("  DAVAO FLOOD DATA CLEANER - CLEANING REPORT")
    lines.append("=" * 70)
    lines.append(f"  Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    lines.append("")
    lines.append(f"  Total input posts:    {report['total_input']}")
    lines.append(f"  Posts kept:           {report['kept']}")
    lines.append(f"  Posts removed:        {report['removed_total']}")

    if report["total_input"] > 0:
        keep_pct = (report["kept"] / report["total_input"]) * 100
        lines.append(f"  Retention rate:       {keep_pct:.1f}%")
    lines.append("")

    # Breakdown by filter layer
    lines.append("-" * 70)
    lines.append("  REMOVAL BREAKDOWN BY FILTER")
    lines.append("-" * 70)

    sorted_layers = sorted(
        report["removed_by_layer"].items(),
        key=lambda x: len(x[1]),
        reverse=True,
    )

    for category, removed_posts in sorted_layers:
        lines.append(f"\n  > {category}: {len(removed_posts)} posts removed")
        lines.append("")
        # Show up to 5 examples per category
        for i, item in enumerate(removed_posts[:5]):
            lines.append(f"    [{i+1}] ID: {item['post_id']} | Author: {item['author']}")
            lines.append(f"        Reason: {item['reason']}")
            preview = item['text_preview'].replace('\n', ' ')
            lines.append(f"        Text: {preview[:120]}...")
            lines.append("")
        if len(removed_posts) > 5:
            lines.append(f"    ... and {len(removed_posts) - 5} more\n")

    lines.append("=" * 70)
    lines.append("  END OF REPORT")
    lines.append("=" * 70)

    report_text = "\n".join(lines)

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(report_text)

    print(f"  [OK] Report: {filepath}")

    # Also print summary to console
    print(f"\n{'=' * 50}")
    print(f"  Cleaning Summary")
    print(f"{'=' * 50}")
    print(f"  Input:   {report['total_input']} posts")
    print(f"  Kept:    {report['kept']} posts")
    print(f"  Removed: {report['removed_total']} posts")
    if report["total_input"] > 0:
        print(f"  Rate:    {keep_pct:.1f}% retained")
    print()
    for category, removed_posts in sorted_layers:
        print(f"    {category}: -{len(removed_posts)}")
    print(f"{'=' * 50}")


# ═══════════════════════════════════════════════════════════════════════════════
# CLI ENTRY POINT
# ═══════════════════════════════════════════════════════════════════════════════

def main():
    try:
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass

    parser = argparse.ArgumentParser(
        description="Clean Davao City flood report data - remove irrelevant posts",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python davao_flood_cleaner.py
  python davao_flood_cleaner.py --input davao_fb_flood_reports.json
  python davao_flood_cleaner.py --input davao_fb_flood_reports.csv
  python davao_flood_cleaner.py --output-dir ./cleaned
  python davao_flood_cleaner.py --strict
        """,
    )

    parser.add_argument(
        "--input", "-i",
        default=None,
        help="Path to input JSON or CSV file (default: autodetected from current directory or Sample Data/)",
    )
    parser.add_argument(
        "--output-dir", "-o",
        default=".",
        help="Directory to save cleaned output files (default: current directory)",
    )
    parser.add_argument(
        "--strict",
        action="store_true",
        help="Use stricter filtering (may remove borderline posts)",
    )

    args = parser.parse_args()

    # ── Resolve input data path ───────────────────────────────────────────
    input_path = args.input
    if not input_path:
        candidates = [
            "davao_fb_flood_reports.json",
            "davao_fb_flood_reports.csv",
            "Sample Data/davao_fb_flood_reports.json",
            "Sample Data/davao_fb_flood_reports.csv",
        ]
        for candidate in candidates:
            if os.path.exists(candidate):
                input_path = candidate
                break

    if not input_path or not os.path.exists(input_path):
        print(f"Error: Input file not found. Specified/Searched: {input_path or candidates}")
        sys.exit(1)

    print(f"\n  Loading data from: {input_path}")
    posts = load_data(input_path)
    print(f"  Loaded {len(posts)} posts\n")

    # ── Run cleaning pipeline ─────────────────────────────────────────────
    print("  Running cleaning pipeline...")
    cleaned_posts, report = clean_data(posts, strict=args.strict)

    # ── Save output ───────────────────────────────────────────────────────
    output_dir = args.output_dir
    os.makedirs(output_dir, exist_ok=True)

    json_out = os.path.join(output_dir, "cleaned_davao_flood_reports.json")
    csv_out = os.path.join(output_dir, "cleaned_davao_flood_reports.csv")
    report_out = os.path.join(output_dir, "cleaning_report.txt")
    audit_out = os.path.join(output_dir, "cleaning_results.json")

    print(f"\n  Saving output to: {output_dir}/")
    save_json(cleaned_posts, json_out)
    save_csv(cleaned_posts, csv_out)
    generate_report(report, report_out)

    retention_rate = round((report["kept"] / report["total_input"]) * 100, 1) if report["total_input"] > 0 else 0.0
    layer_counts = {
        cat: len(items)
        for cat, items in sorted(report["removed_by_layer"].items(), key=lambda x: len(x[1]), reverse=True)
    }
    audit_payload = {
        "total_input": report["total_input"],
        "kept": report["kept"],
        "removed_total": report["removed_total"],
        "retention_rate": retention_rate,
        "removed_by_layer_counts": layer_counts,
        "audit_records": list(report.get("audit_by_post_id", {}).values()),
    }
    with open(audit_out, "w", encoding="utf-8") as f:
        json.dump(audit_payload, f, indent=2, ensure_ascii=False)
    print(f"  [OK] Audit JSON: {audit_out}")
    print()


if __name__ == "__main__":
    main()

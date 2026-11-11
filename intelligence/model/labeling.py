"""
labeling.py — Context-Aware Weak Labeling and Validation for Flood Posts
========================================================================
Implements whole-word flood lexicon matching, lookalike disambiguation
(e.g., distinguishing 'bahay' / 'kapitbahay' from 'baha'), context cue
extraction (water levels, evacuations, rescue ops), and figurative/ad filtering.

This module is used to:
  1. Generate high-quality training labels without substring false-positives
  2. Flag ambiguous posts as 'needs_review' rather than guessing
  3. Validate post relevance based on contextual semantics rather than naive text matching
"""

import re
from dataclasses import dataclass, field
from typing import List, Optional, Tuple, Dict


@dataclass
class LabelResult:
    label: int                          # 1 = Flood, 0 = Non-Flood, -1 = Needs Review
    confidence: float                   # 0.0 to 1.0
    reason: str                         # Concise diagnostic explanation
    matched_flood_words: List[str] = field(default_factory=list)
    matched_lookalikes: List[str] = field(default_factory=list)
    matched_context_cues: List[str] = field(default_factory=list)
    matched_negative_cues: List[str] = field(default_factory=list)

    @property
    def is_flood(self) -> bool:
        return self.label == 1

    @property
    def needs_review(self) -> bool:
        return self.label == -1


# ═══════════════════════════════════════════════════════════════════════
# 1. Exact Whole-Word Flood Lexicon (Cebuano, Tagalog, English)
# ═══════════════════════════════════════════════════════════════════════
# Note user corrections:
# - nibaha (not mibata)
# - lunod & lumos, nalumos & nalunod
# - lapaw & milapaw, lapas & nilapas
# - excluded: binahang, gilunopan, bul-og
FLOOD_TERMS = [
    # Root & morphological variants of 'baha'
    r"\bbaha(?!['’]?u)\b",
    r"\bbumaha\b",
    r"\bbinaha\b",
    r"\bnagbaha\b",
    r"\bnag-baha\b",
    r"\bnag\s+baha\b",
    r"\bnagabaha\b",
    r"\bgibaha\b",
    r"\bpagbaha\b",
    r"\bpag-baha\b",
    r"\bnibaha\b",
    r"\bni-baha\b",
    r"\bni\s+baha\b",
    r"\bnabahaan\b",
    r"\btubig[- ]baha\b",
    # Drowning / deep water / overflow variants
    r"\blunod\b",
    r"\bnalunod\b",
    r"\blumos\b",
    r"\bnalumos\b",
    r"\blapaw\b",
    r"\bmilapaw\b",
    r"\blapas\b",
    r"\bnilapas\b",
    # English terms
    r"\bflood\b",
    r"\bfloods\b",
    r"\bflooded\b",
    r"\bflooding\b",
    r"\bflash\s+flood\b",
    r"\bfloodwater\b",
    r"\bfloodwaters\b",
]

_FLOOD_REGEX = re.compile(r"|".join(FLOOD_TERMS), re.IGNORECASE)


# ═══════════════════════════════════════════════════════════════════════
# 2. Excluded Lookalikes (Words that contain 'baha' but are NOT flood)
# ═══════════════════════════════════════════════════════════════════════
LOOKALIKE_TERMS = [
    r"\bbahay\b",
    r"\bkapitbahay\b",
    r"\bkapit-bahay\b",
    r"\bkapit\s+bahay\b",
    r"\bkabahayan\b",
    r"\bpamahay\b",
    r"\bpanimalay\b",        # Cebuano house/household
    r"\bbahala\b",
    r"\bnabahala\b",
    r"\bpagkabahala\b",
    r"\bikabahala\b",
    r"\bbahagi\b",
    r"\bibahagi\b",
    r"\bpagbahagi\b",
    r"\bbahagya\b",
    r"\bbahaghari\b",
    r"\bbahamas\b",
    r"\bbaha['’]?u\b",       # Sama ritual term (e.g. "pai baha'u" in Tawi-Tawi)
]

_LOOKALIKE_REGEX = re.compile(r"|".join(LOOKALIKE_TERMS), re.IGNORECASE)


# ═══════════════════════════════════════════════════════════════════════
# 3. Contextual Cues: Water Levels, Flow, and Impact Conditions
# ═══════════════════════════════════════════════════════════════════════
WATER_LEVEL_CUES = [
    r"taas\s+(?:kaayo\s+)?(?:ang\s+)?tubig",
    r"pagtaas\s+sa\s+tubig",
    r"(?:misaka|nisaka|tumaas)\s+(?:na\s+)?(?:ang\s+)?tubig",
    r"kataas\s+sa\s+tubig",
    r"kalit\s+nga\s+pagtaas",
    r"ab-?ot[- ]tuhod",
    r"ab-?ot[- ]hawak",
    r"ab-?ot[- ]dughan",
    r"taga[- ]dughan",
    r"taga[- ]tuhod",
    r"taga[- ]hawak",
    r"hanggang\s+(?:baywang|tuhod|dibdib)",
    r"ankle[- ]deep",
    r"knee[- ]deep",
    r"waist[- ]deep",
    r"chest[- ]deep",
    r"submerged",
    r"(?:nag|pag)[- ]overflow",
    r"overflowing",
    r"spillover",
    r"nanglutaw",
    r"gilunopan",
    r"water\s+level(?:\s+increased)?",
    r"water\s+rising",
    r"rising\s+water",
    r"river\s+water",
    r"riverbanks?",
    r"(?:misulod|nisulod|nisud|misud)\s+sa\s+(?:ilang\s+|among\s+|kabalayan|balay)",
    r"pumasok\s+sa\s+(?:loob\s+ng\s+)?balay",
    r"nasudlan\s+og\s+tubig",
    r"lubog\s+sa\s+(?:tubig|baha)",
    r"nalunod\s+(?:ang\s+)?(?:mga\s+)?(?:balay|sakyanan|panimalay)",
    r"nalunod\s+sa\s+(?:baha|tubig)",
    r"lunod\s+(?:ang\s+)?(?:mga\s+)?sakyanan",
    r"lawom\s+(?:kaayo\s+)?ang\s+tubig",
    r"nilapas\s+na\s+ang\s+tubig",
    r"(?:milapaw|nilapas|milapas)\s+ang\s+tubig",
    r"kalit\s+nga\s+pagbaha",
    r"street\s+flooding",
    r"urban\s+flooding",
]

_WATER_LEVEL_REGEX = re.compile(r"|".join(WATER_LEVEL_CUES), re.IGNORECASE)


# ═══════════════════════════════════════════════════════════════════════
# 4. Disaster & Emergency Response Cues (Rescue, Evacuation, Stranded)
# ═══════════════════════════════════════════════════════════════════════
RESPONSE_CUES = [
    r"pre[- ]?emptive\s+evacuation",
    r"force\s+evacuation",
    r"forced\s+evacuation",
    r"evacuat(?:e|ion|ed)",
    r"gipahikling",
    r"mihikling",
    r"nagbakwit",
    r"gibakwit",
    r"bakwit",
    r"evacuation\s+center",
    r"reskyu",
    r"pag\s+reskyu",
    r"rescue(?:\s+ops|\s+operation|\s+team)?",
    r"giluwas",
    r"rescue\s+boat",
    r"rubber\s+boat",
    r"stranded",
    r"natrap",
    r"na-trap",
    r"trapped",
    r"impassable",
    r"di\s+na\s+maagian",
    r"dili\s+maagian",
    r"hindi\s+madaanan",
    r"naputol\s+(?:ang\s+)?(?:kalsada|dalan)",
    r"giputol\s+sa\s+baha",
    r"dalan\s+.*?\s+naputol",
    r"naguba\s+ang\s+tulay",
    r"nagtinabangay\s+(?:ang\s+mga\s+residente|sa\s+pag\s*reskyu|sa\s+pagtulak)",
    r"tulong\s+po",
    r"tabang\s+(?:intawon|mo)?",
    r"paki[- ]reskyu",
    r"paki[- ]evacuate",
    r"state\s+of\s+calamity",
    r"cdrrmo",
    r"pdrrmo",
    r"mdrrmo",
    r"opcen\s+advisory",
]

_RESPONSE_REGEX = re.compile(r"|".join(RESPONSE_CUES), re.IGNORECASE)


# ═══════════════════════════════════════════════════════════════════════
# 5. Secondary Environmental Triggers (Weather)
# ═══════════════════════════════════════════════════════════════════════
WEATHER_CUES = [
    r"kusog\s+nga\s+ulan",
    r"sagunsong\s+pagbunok",
    r"pagbundak\s+sa\s+ulan",
    r"walay\s+puas\s+nga\s+pag[- ]?ulan",
    r"bunok\s+sa\s+ulan",
    r"continuous\s+rain",
    r"heavy\s+downpour",
    r"torrential\s+rain",
    r"low\s+pressure\s+area",
    r"\blpa\b",
    r"\bitcz\b",
    r"\bbagyo\b",
    r"tropical\s+depression",
]

_WEATHER_REGEX = re.compile(r"|".join(WEATHER_CUES), re.IGNORECASE)


# ═══════════════════════════════════════════════════════════════════════
# 6. Commercial, Figurative, and False-Positive Indicators
# ═══════════════════════════════════════════════════════════════════════
FIGURATIVE_OR_AD_CUES = [
    r"baha\s+(?:sa|ng|ug)\s+orders?",
    r"bumaha\s+ng\s+(?:order|orders|pagkain|biyaya|gifts?)",
    r"flood\s+of\s+orders?",
    r"flood[- ]free",
    r"flood[- ]proof",
    r"baha\??[^\w\n]*.*?\b(?:no\s+problem|walay\s+problema|bentot|motorcycle|motor)\b",
    r"for\s+sale",
    r"open\s+for\s+orders?",
    r"dm\s+for\s+(?:price|orders?)",
    r"buy\s+1\s+take\s+1",
    r"promo\s+price",
    r"free\s+taste",
    r"avail\s+our",
    r"rent\s+to\s+own",
    r"looking\s+for\s+roommates?",
]

_FIGURATIVE_OR_AD_REGEX = re.compile(r"|".join(FIGURATIVE_OR_AD_CUES), re.IGNORECASE)

# Hypothetical advisory / educational / retrospective cues
HYPOTHETICAL_OR_HISTORICAL_CUES = [
    r"tips\s+(?:kung|in\s+case|para)\s+sa\s+baha",
    r"in\s+case\s+of\s+flood",
    r"flood\s+safety\s+tips",
    r"unsaon\s+paglikay\s+sa\s+baha",
    r"niadtong\s+(?:19\d\d|200\d|201\d)",     # e.g., "niadtong 2011"
    r"noong\s+(?:19\d\d|200\d|201\d)",
    r"flashback\s+friday",
    r"throwback",
]

_HYPOTHETICAL_OR_HISTORICAL_REGEX = re.compile(r"|".join(HYPOTHETICAL_OR_HISTORICAL_CUES), re.IGNORECASE)

# Political speeches, election campaigns, and non-emergency civic commentary
POLITICAL_OR_CIVIC_CUES = [
    r"\bbalota\b",
    r"\belek[s|y]on\b",
    r"\belection\b",
    r"\bkandidato\b",
    r"\bkonsehal\b",
    r"\bmayor\b",
    r"\bvice\s+mayor\b",
    r"\bvoters?\b",
    r"\bpagboto\b",
    r"\biboto\b",
    r"\bpartido\b",
    r"\bkongreso\b",
    r"\bdistrito\s+dos\b",
    r"\bdistrito\s+uno\b",
    r"\bdistrito\s+tres\b",
    r"\bmiting\s+de\s+avance\b",
    r"\bkampanya\b",
    r"\bhalalan\b",
    r"number\s+\d+\s+sa\s+balota",
    r"administartion\s+party",
    r"administration\s+party",
]

_POLITICAL_REGEX = re.compile(r"|".join(POLITICAL_OR_CIVIC_CUES), re.IGNORECASE)


# ═══════════════════════════════════════════════════════════════════════
# 7. Core Classification / Disambiguation Function
# ═══════════════════════════════════════════════════════════════════════

def label_post(
    text: str,
    detected_locations: Optional[List[str]] = None,
) -> LabelResult:
    """
    Classify a post with full contextual disambiguation.

    Args:
        text: Normalized or raw text string of the post.
        detected_locations: Optional list of NER/gazetteer detected locations.
                            (Note: Having a location NEVER blindly triggers label 1!)

    Returns:
        LabelResult with label (1, 0, or -1), confidence, and diagnostic matches.
    """
    if not text or not text.strip():
        return LabelResult(label=0, confidence=1.0, reason="empty_text")

    lower = text.lower()

    # Find matches
    flood_matches = list(set(_FLOOD_REGEX.findall(lower)))
    lookalike_matches = list(set(_LOOKALIKE_REGEX.findall(lower)))
    water_matches = list(set(_WATER_LEVEL_REGEX.findall(lower)))
    response_matches = list(set(_RESPONSE_REGEX.findall(lower)))
    weather_matches = list(set(_WEATHER_REGEX.findall(lower)))
    fig_matches = list(set(_FIGURATIVE_OR_AD_REGEX.findall(lower)))
    hypo_matches = list(set(_HYPOTHETICAL_OR_HISTORICAL_REGEX.findall(lower)))
    political_matches = list(set(_POLITICAL_REGEX.findall(lower)))

    has_flood_term = len(flood_matches) > 0
    has_water_cue = len(water_matches) > 0
    has_response_cue = len(response_matches) > 0
    has_weather_cue = len(weather_matches) > 0
    has_fig_ad = len(fig_matches) > 0
    has_hypo_hist = len(hypo_matches) > 0
    has_political = len(political_matches) > 0
    has_lookalikes = len(lookalike_matches) > 0

    all_context_cues = water_matches + response_matches + weather_matches
    negative_cues = fig_matches + hypo_matches + political_matches

    # ── Rule 1: Clear Figurative / Commercial / Ad match ────────────────
    if has_fig_ad:
        return LabelResult(
            label=0,
            confidence=0.95,
            reason="figurative_or_advertisement",
            matched_flood_words=flood_matches,
            matched_lookalikes=lookalike_matches,
            matched_context_cues=all_context_cues,
            matched_negative_cues=negative_cues,
        )

    # ── Rule 1b: Political Campaign Speeches & Electoral Discourse ──────
    # Rejects campaign promises, ballot solicitations, and political talking points
    if has_political and not (has_water_cue and has_response_cue):
        return LabelResult(
            label=0,
            confidence=0.96,
            reason="political_campaign_speech",
            matched_flood_words=flood_matches,
            matched_lookalikes=lookalike_matches,
            matched_context_cues=all_context_cues,
            matched_negative_cues=negative_cues,
        )

    # ── Rule 2: Pure Lookalike (e.g. 'bahay', 'kapitbahay', 'nabahala') ──
    # If lookalike is present and NO genuine flood term or emergency cues are present
    if has_lookalikes and not has_flood_term and not (has_water_cue or has_response_cue):
        return LabelResult(
            label=0,
            confidence=0.99,
            reason="lookalike_nonflood_word",
            matched_flood_words=[],
            matched_lookalikes=lookalike_matches,
            matched_context_cues=all_context_cues,
            matched_negative_cues=negative_cues,
        )

    # ── Rule 3: Genuine flood term + strong contextual corroboration ────
    # (Water level, flooding impact, rescue, evacuation)
    if has_flood_term and (has_water_cue or has_response_cue):
        return LabelResult(
            label=1,
            confidence=0.98,
            reason="flood_term_with_contextual_cues",
            matched_flood_words=flood_matches,
            matched_lookalikes=lookalike_matches,
            matched_context_cues=all_context_cues,
            matched_negative_cues=negative_cues,
        )

    # ── Rule 4: Context-only flood / evacuation report (NO explicit 'baha' term) ──
    # E.g. "pre-emptive evacuation sa riverbanks", "taas na kaayo ang tubig", "submerged"
    if (has_water_cue and has_response_cue) or (has_water_cue and has_weather_cue) or (
        has_response_cue and any("evacuation" in c.lower() or "reskyu" in c.lower() or "rescue" in c.lower() for c in response_matches) and (has_water_cue or has_weather_cue or "river" in lower or "sapa" in lower)
    ):
        return LabelResult(
            label=1,
            confidence=0.92,
            reason="context_only_flood_or_evacuation_report",
            matched_flood_words=flood_matches,
            matched_lookalikes=lookalike_matches,
            matched_context_cues=all_context_cues,
            matched_negative_cues=negative_cues,
        )

    # ── Rule 5: Historical or purely hypothetical educational post ──────
    if has_hypo_hist and not (has_water_cue and has_response_cue):
        return LabelResult(
            label=0,
            confidence=0.88,
            reason="historical_or_hypothetical_tips",
            matched_flood_words=flood_matches,
            matched_lookalikes=lookalike_matches,
            matched_context_cues=all_context_cues,
            matched_negative_cues=negative_cues,
        )

    # ── Rule 6: Isolated flood term with weather cue ────────────────────
    if has_flood_term and has_weather_cue:
        return LabelResult(
            label=1,
            confidence=0.85,
            reason="flood_term_with_weather_context",
            matched_flood_words=flood_matches,
            matched_lookalikes=lookalike_matches,
            matched_context_cues=all_context_cues,
            matched_negative_cues=negative_cues,
        )

    # ── Rule 7: Isolated flood term without ANY contextual corroboration ─
    # A post that merely contains "baha" with 0 water depth cues, 0 response cues, and 0 weather cues
    # is casual non-emergency chatter, commentary, or municipal discourse.
    if has_flood_term:
        return LabelResult(
            label=0,
            confidence=0.85,
            reason="isolated_flood_mention_no_urgency_cues",
            matched_flood_words=flood_matches,
            matched_lookalikes=lookalike_matches,
            matched_context_cues=all_context_cues,
            matched_negative_cues=negative_cues,
        )

    # ── Rule 8: Weather-only (rain, heat) or location-only without flood ─
    # Critical: Having detected locations or rain does NOT make it a flood!
    return LabelResult(
        label=0,
        confidence=0.95,
        reason="no_flood_terms_or_flood_context",
        matched_flood_words=[],
        matched_lookalikes=lookalike_matches,
        matched_context_cues=all_context_cues,
        matched_negative_cues=negative_cues,
    )

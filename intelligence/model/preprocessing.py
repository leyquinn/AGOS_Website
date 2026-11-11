"""
preprocessing.py — Text Normalization Pipeline for Social Media Flood Posts
============================================================================
Handles multilingual, code-switched text (English, Tagalog, Cebuano, Taglish,
Bislish) with the following operations:
  - Lowercasing
  - URL removal
  - @handle removal
  - Hashtag cleaning (strips '#' prefix, retains the word)
  - Platform noise removal (RT, via, etc.)
  - Emoji and special character filtering
  - Whitespace normalization
  - Diacritic preservation (ñ, ó, etc. critical for Filipino/Cebuano)
  - Duplicate post filtering
"""

import re
import hashlib
import unicodedata
from typing import List, Optional, Set

# ═══════════════════════════════════════════════════════════════════════
# Compiled Regex Patterns (compiled once for performance)
# ═══════════════════════════════════════════════════════════════════════

# URLs: http(s), www, and common TLDs
_URL_PATTERN = re.compile(
    r"https?://\S+|www\.\S+|[\w.-]+\.(?:com|org|net|ph|gov)\S*",
    re.IGNORECASE,
)

# @mentions / handles
_HANDLE_PATTERN = re.compile(r"@\w+")

# Hashtags: strip the '#' but keep the word (may contain location info)
_HASHTAG_PATTERN = re.compile(r"#(\w+)")

# Retweet / platform artifacts
_RT_PATTERN = re.compile(r"\bRT\b\s*:?\s*", re.IGNORECASE)
_VIA_PATTERN = re.compile(r"\bvia\b\s+@?\w+", re.IGNORECASE)

# Emojis and misc. non-text symbols (preserves Filipino diacritics)
# This pattern keeps: letters (including accented), digits, whitespace,
# and common punctuation needed for location parsing (., -, /, ')
_EMOJI_PATTERN = re.compile(
    r"[^\w\s.,!?;:'\-/()#ñÑóÓáÁéÉíÍúÚàÀèÈìÌùÙ]",
    re.UNICODE,
)

# Collapse multiple whitespace into single space
_WHITESPACE_PATTERN = re.compile(r"\s+")

# Repeated characters (3+ of the same char → 2): e.g., "baaaaha" → "baaha"
_REPEATED_CHARS_PATTERN = re.compile(r"(.)\1{2,}")


def strip_fb_artifacts(text: str) -> str:
    """
    Remove Facebook scraper noise, duplicated text blocks from 'See less',
    and platform boilerplate ('See translation', 'm.me', hash junk, etc.).
    """
    if not text or not isinstance(text, str):
        return ""

    # Remove leading 'Facebook' banner
    text = re.sub(r"^\s*Facebook\s*", "", text, flags=re.IGNORECASE)

    # Handle 'See less' duplication (where post body is repeated twice by scraper)
    if "See less" in text:
        parts = [p.strip() for p in text.split("See less") if p.strip()]
        if len(parts) >= 2:
            p0 = re.sub(r"\s+", " ", parts[0]).strip().lower()
            p1 = re.sub(r"\s+", " ", parts[1]).strip().lower()
            if p0 in p1 or p1 in p0 or (len(p0) > 10 and len(p1) > 10 and (p0[:40] == p1[:40])):
                text = parts[0]
            else:
                text = parts[0] + " " + parts[1]
        elif len(parts) == 1:
            text = parts[0]

    # Strip trailing boilerplate and links
    text = re.sub(r"See translation.*$", "", text, flags=re.IGNORECASE | re.DOTALL)
    text = re.sub(r"Photos from .*? post.*$", "", text, flags=re.IGNORECASE | re.DOTALL)
    text = re.sub(r"\bm\.me\b.*$", "", text, flags=re.IGNORECASE | re.DOTALL)

    # Remove random long scrape hash strings (alphanumeric sequences >= 20 chars)
    text = re.sub(r"\b[A-Za-z0-9_-]{20,}\b", " ", text)

    # Remove trailing standalone numbers (scrape engagement counts)
    text = re.sub(r"\n\s*\d+\s*$", "", text)

    return text.strip()


# ═══════════════════════════════════════════════════════════════════════
# Core Normalization Function
# ═══════════════════════════════════════════════════════════════════════

def normalize_text(text: str) -> str:
    """
    Apply the full social media text normalization pipeline.

    Processing order is deliberate:
      0. Strip Facebook scrape artifacts & duplicate blocks
      1. Remove URLs first (they may contain @, #, etc.)
      2. Remove handles
      3. Clean hashtags (keep word, remove '#')
      4. Remove RT/via noise
      5. Remove emojis (preserving diacritics)
      6. Reduce repeated characters
      7. Lowercase
      8. Normalize whitespace

    Args:
        text: Raw social media post text.

    Returns:
        Cleaned, normalized text string.
    """
    if not text or not isinstance(text, str):
        return ""

    # Normalize unicode stylized fonts (e.g., Facebook bold headers 𝗕𝗔𝗛𝗔 -> BAHA)
    text = unicodedata.normalize("NFKD", text)

    # Step 0: Strip Facebook scrape noise and duplicated text
    text = strip_fb_artifacts(text)

    # Step 1: Remove URLs
    text = _URL_PATTERN.sub("", text)

    # Step 2: Remove @handles
    text = _HANDLE_PATTERN.sub("", text)

    # Step 3: Strip '#' from hashtags but keep the word
    text = _HASHTAG_PATTERN.sub(r"\1", text)

    # Step 4: Remove RT and via artifacts
    text = _RT_PATTERN.sub("", text)
    text = _VIA_PATTERN.sub("", text)

    # Step 5: Remove emojis and non-text symbols (preserving diacritics)
    text = _EMOJI_PATTERN.sub(" ", text)

    # Step 6: Reduce repeated characters (e.g., "tuloooong" → "tuloong")
    text = _REPEATED_CHARS_PATTERN.sub(r"\1\1", text)

    # Step 7: Lowercase
    text = text.lower()

    # Step 8: Normalize whitespace
    text = _WHITESPACE_PATTERN.sub(" ", text).strip()

    return text


# ═══════════════════════════════════════════════════════════════════════
# Batch Processing with Deduplication
# ═══════════════════════════════════════════════════════════════════════

def preprocess_batch(
    texts: List[str],
    deduplicate: bool = True,
) -> List[str]:
    """
    Normalize a batch of texts with optional deduplication.

    Deduplication uses content hashing on the normalized text so that
    posts differing only in whitespace, casing, or noise are caught.

    Args:
        texts:       List of raw social media post strings.
        deduplicate: If True, remove duplicate normalized texts.

    Returns:
        List of cleaned, unique texts.
    """
    cleaned = []
    seen_hashes: Set[str] = set()

    for text in texts:
        normalized = normalize_text(text)

        # Skip empty results
        if not normalized:
            continue

        if deduplicate:
            # Hash the normalized text for efficient duplicate detection
            text_hash = hashlib.md5(normalized.encode("utf-8")).hexdigest()
            if text_hash in seen_hashes:
                continue
            seen_hashes.add(text_hash)

        cleaned.append(normalized)

    return cleaned


# ═══════════════════════════════════════════════════════════════════════
# Word Tokenization (for NER label alignment)
# ═══════════════════════════════════════════════════════════════════════

def tokenize_to_words(text: str) -> List[str]:
    """
    Simple whitespace tokenization of normalized text.

    This produces word-level tokens that align with NER annotations.
    The tokenizer's `is_split_into_words=True` will further split these
    into subwords.

    Args:
        text: Normalized text string.

    Returns:
        List of word tokens.
    """
    if not text:
        return []
    return text.split()

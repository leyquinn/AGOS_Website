#!/usr/bin/env python3
"""
davao_flood_scraper.py
======================
Scrapes public Facebook posts & search results related to flooding in
Davao City, Philippines.  Uses undetected-chromedriver for stealth and a
persistent Chrome profile so the operator only needs to log in once.

Requirements
------------
    pip install undetected-chromedriver selenium pandas

Usage
-----
    # First run – a visible Chrome window opens so you can log in manually.
    python davao_flood_scraper.py --login

    # Subsequent runs – headless scraping using the saved session.
    python davao_flood_scraper.py

    # Control how far to scroll (default 50 scroll cycles per query).
    python davao_flood_scraper.py --scrolls 80

    # Specify output directory.
    python davao_flood_scraper.py --output-dir ./results
"""

from __future__ import annotations

import argparse
import atexit
import base64
import csv
import hashlib
import json
import logging
import os
import random
import re
import signal
import sys
import time
from dataclasses import dataclass, field, asdict, fields
from datetime import datetime, date, timezone, timedelta
from pathlib import Path
from typing import List, Optional, Set
from urllib.parse import quote_plus, urljoin, urlparse, parse_qs, urlencode, urlunparse

import undetected_chromedriver as uc
from selenium.webdriver.common.by import By
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import (
    NoSuchElementException,
    StaleElementReferenceException,
    TimeoutException,
    ElementClickInterceptedException,
    WebDriverException,
)

# ──────────────────────────────────────────────
# Date Filtering Configuration
# ──────────────────────────────────────────────
# Configure date criteria to filter posts during extraction.
# Leave as None to disable specific filters.
#
# Examples:
#   FILTER_YEAR = 2026                 # Keep posts from 2026 only
#   FILTER_MONTHS = [7]                # 7 = July (e.g. [7] or [7, 8] for July & August)
#   FILTER_START_DATE = "2026-07-01"   # Inclusive start date (YYYY-MM-DD)
#   FILTER_END_DATE = "2026-07-31"     # Inclusive end date (YYYY-MM-DD)
#   APPLY_FB_YEAR_FILTER = True        # Use Facebook's search URL filter to restrict search to FILTER_YEAR
#   ALLOW_UNDATED_POSTS = False        # When filtering by date, whether to keep posts whose date couldn't be parsed
FILTER_YEAR: int | None = None
FILTER_MONTHS: list[int] | None = None   # e.g. [7] for July, [8] for August, [7, 8] for July & August
FILTER_START_DATE: str | None = None     # e.g. "2026-07-01"
FILTER_END_DATE: str | None = None       # e.g. "2026-07-31"
APPLY_FB_YEAR_FILTER: bool = True
ALLOW_UNDATED_POSTS: bool = False

# ──────────────────────────────────────────────
# Configuration constants
# ──────────────────────────────────────────────

FLOOD_KEYWORDS: list[str] = [
    "baha", "nagbaha", "lunod", "nalunod", "taas ang tubig", "suba", 
    "sapa", "overflow", "lapok", "baha sa davao", "bahaon", "binaha",
    "flood", "flooding", "flooded", "tubig", "unod", "naunod", "water level", 
    "lubog", "nalubog", "lapas tuhod","lapas hawak", "lapas ulo", "kusog ang tubig", 
    "kusog", "lapas tao", "dili maagian", "not passable","spillway", "creek", "ilog", 
    "drainage", "culvert", "landslide", "ulan","kusog ang ulan", "heavy rain", 
    "downpour", "thunderstorm", "pag-ulan","flashflood", "flash flood", "river", 
    "davao river", "matina river","calamity", "disaster", "water rising", "nisaka ang tubig",
]

URGENCY_KEYWORDS: list[str] = [
    "tabang", "rescue", "stranded", "trap", "trapped", "atop",
    "emergency", "tulong", "evacuation", "evacuate", "evacuee",
    "nalumos", "saklolo", "help", "relief", "CDRRMO", "OCD",
    "forced evacuation", "preemptive", "alert level", "red warning",
]

DAVAO_AREAS: list[str] = [
    "Jade Valley", "Matina Pangi", "Matina Crossing",
    "Maa", "Ma-a", "Buhangin", "Bangkal", "Tigatto",
    "Bucana", "Talomo", "Bunawan", "Waan", "Juliville",
    "San Rafael", "El Rio", "Davao", "Toril", "Calinan",
    "Panacan", "Sasa", "Agdao", "Poblacion",
    "Catalunan", "Mintal", "Tugbok", "Bago Aplaya",
    "Lanang", "Bajada",
]

# ==============================================================================
# HARDCODED SEARCH QUERIES (FALLBACK ONLY)
# NOTICE: Only used if running python davao_flood_scraper.py directly in terminal
# without --queries or --queries-file arguments. When invoked from the AGOS
# website / Laravel portal, queries are dynamically supplied by the user.
# ==============================================================================
SEARCH_QUERIES: list[str] = [
    # ── 1. Primary Local Dialects & Slang ──────────────────────
    # Broad multilingual flood queries (English, Tagalog, Cebuano)
    "baha davao",
    "flood davao",

    # ── 2. Rescue & Urgency Triggers ───────────────────────────
    # Keywords that surface posts with high urgency / rescue needs
    "rescue flood davao",
    "stranded baha davao",
    "evacuation baha davao",
    "tabang baha davao",

    # ── 3. High-Risk Barangay & Hotspot Combinations ───────────
    # District 1 — Matina corridor, river basins, central flood zones
    "baha Matina Pangi",
    "baha Matina Crossing",
    "baha Matina Aplaya",
    "baha Bucana davao",
    "baha Bangkal davao",
    "baha Ma-a davao",
    "baha Bankerohan",
    "baha Agdao davao",
    "baha Talomo davao",
    "baha Quimpo Boulevard",
    "baha Sandawa davao",
    "baha R. Castillo davao",
    "baha Mintal davao",

    # District 2 — Northern & coastal corridors
    "baha Agdao davao",
    "baha Buhangin davao",
    "baha Lanang davao",
    "baha Cabantian davao",
    "baha Sasa davao",
    "baha Panacan davao",
    "baha Tibungco davao",
    "baha Bunawan davao",
    "baha Lasang davao",
    "baha Jade Valley davao",
    "baha Communal davao",
    "baha La Verna davao",

    # District 3 — Inland & upstream sectors
    "baha Waan davao",
    "baha Mandug davao",
    "baha Tigatto davao",
    "baha Tugbok davao",
    "baha Toril davao",
    "baha Calinan davao",
    "baha Catalunan davao",

    # ── 4. Situational & News / LGU Comment Triggers ──────────
    # Targets official advisories and regional news post comments
    "CDRRMO davao flood",
    "City Government of Davao weather advisory",
    "GMA Regional TV One Mindanao baha",
    "TV5 news davao flood",
    "ABS-CBN news regional davao baha",
    "SunStar Davao baha",
]

# "See more" button labels (includes Bisaya/Cebuano localizations)
SEE_MORE_LABELS: list[str] = [
    "See more", "See More",
]

# ──────────────────────────────────────────────
# Data model
# ──────────────────────────────────────────────

@dataclass
class FloodReport:
    post_id: str = ""
    search_query: str = ""
    post_url: str = ""
    author: str = "Unknown"
    timestamp: str = ""                      # Normalized formatted date/time string (e.g. 'October 21, 2026 at 3:19 PM')
    raw_timestamp: str = ""                  # Raw captured string ('29m', 'Wednesday, October 21, 2026 at 3:19 PM', etc.)
    iso_timestamp: str = ""                  # Normalized ISO 8601 datetime (e.g. '2026-10-21T15:19:00+08:00')
    relative_time: str = ""                  # Relative representation ('29m', '45s', '3h', '39m ago')
    relative_seconds: Optional[int] = None   # Sub-day integer seconds for active flood validation
    relative_minutes: Optional[float] = None # Sub-day float minutes
    relative_hours: Optional[float] = None   # Sub-day float hours
    full_text: str = ""
    detected_locations: List[str] = field(default_factory=list)
    urgency_level: str = "NORMAL"          # NORMAL | HIGH | SITREP
    media_urls: List[str] = field(default_factory=list)
    scraped_at: str = ""

# ──────────────────────────────────────────────
# Logging
# ──────────────────────────────────────────────

LOG_FMT = "%(asctime)s | %(levelname)-7s | %(message)s"
logging.basicConfig(level=logging.INFO, format=LOG_FMT)
log = logging.getLogger("davao_scraper")

# ──────────────────────────────────────────────
# Helpers
# ──────────────────────────────────────────────

def _hash_text(text: str) -> str:
    """Return an MD5 fingerprint to help deduplicate posts."""
    return hashlib.md5(text.encode("utf-8", errors="replace")).hexdigest()


def _detect_locations(text: str) -> list[str]:
    """Return every monitored Davao area mentioned in *text*."""
    found: list[str] = []
    lowered = text.lower()
    for area in DAVAO_AREAS:
        # Word-boundary-aware match to avoid false positives.
        pattern = re.compile(rf"\b{re.escape(area)}\b", re.IGNORECASE)
        if pattern.search(text):
            found.append(area)
    return list(dict.fromkeys(found))  # deduplicate, preserve order


def _classify_urgency(text: str) -> str:
    """Classify the urgency level of a post based on keyword density."""
    lowered = text.lower()
    urgency_hits = sum(1 for kw in URGENCY_KEYWORDS if kw.lower() in lowered)
    flood_hits = sum(1 for kw in FLOOD_KEYWORDS if kw.lower() in lowered)

    if urgency_hits >= 2:
        return "SITREP"       # Multiple rescue / emergency terms → critical
    if urgency_hits >= 1 or flood_hits >= 2:
        return "HIGH"
    return "NORMAL"


def _is_flood_relevant(text: str, active_query: str = "") -> bool:
    """Check if the text mentions flood, urgency, or user-configured query keywords."""
    if not text or len(text.strip()) < 3:
        return False
    lowered = text.lower()
    if any(kw.lower() in lowered for kw in FLOOD_KEYWORDS) or any(kw.lower() in lowered for kw in URGENCY_KEYWORDS):
        return True
    if active_query:
        cleaned_query = active_query.lstrip("#").strip().lower()
        if cleaned_query and cleaned_query in lowered:
            return True
        tokens = [t for t in re.split(r"\s+", cleaned_query) if len(t) >= 3]
        if tokens and any(t in lowered for t in tokens):
            return True
    return False


def _human_pause(lo: float = 1.0, hi: float = 2.5) -> None:
    """Randomized sleep to emulate human browsing cadence."""
    time.sleep(random.uniform(lo, hi))


def _fast_scroll(driver, pixels: int = 1000) -> None:
    """Scroll down smoothly in browser without multi-step Python sleep loops."""
    driver.execute_script(f"window.scrollBy({{top: {pixels}, behavior: 'smooth'}});")


MONTH_NAME_TO_INT: dict[str, int] = {
    'january': 1, 'jan': 1, 'enero': 1,
    'february': 2, 'feb': 2, 'pebrero': 2,
    'march': 3, 'mar': 3, 'marso': 3,
    'april': 4, 'apr': 4, 'abril': 4,
    'may': 5, 'mayo': 5,
    'june': 6, 'jun': 6, 'hunyo': 6,
    'july': 7, 'jul': 7, 'hulyo': 7,
    'august': 8, 'aug': 8, 'agosto': 8,
    'september': 9, 'sep': 9, 'sept': 9, 'setyembre': 9,
    'october': 10, 'oct': 10, 'oktubre': 10,
    'november': 11, 'nov': 11, 'nobiyembre': 11, 'nobyembre': 11,
    'december': 12, 'dec': 12, 'disyembre': 12,
}


MONTH_NAMES_LIST: list[str] = [
    'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august',
    'september', 'october', 'november', 'december', 'jan', 'feb', 'mar', 'apr',
    'jun', 'jul', 'aug', 'sep', 'sept', 'oct', 'nov', 'dec', 'enero', 'pebrero',
    'marso', 'abril', 'mayo', 'hunyo', 'hulyo', 'agosto', 'setyembre', 'oktubre',
    'nobiyembre', 'nobyembre', 'disyembre'
]
_MONTH_NAMES_RE: str = '|'.join(MONTH_NAMES_LIST)


def _parse_date_str(date_str: str | None) -> date | None:
    """Parse 'YYYY-MM-DD' string to a date object."""
    if not date_str:
        return None
    try:
        return datetime.strptime(date_str.strip(), "%Y-%m-%d").date()
    except Exception:
        return None


def _parse_timestamp_to_datetime(ts: str, default_year: int | None = None) -> datetime | None:
    """Parse various Facebook timestamp formats into a datetime object for any year/month.
    Supports ISO formats, full tooltips ('Wednesday, October 21, 2026 at 3:19 PM'),
    seconds precision ('3:19:45 PM'), and Filipino/Bisaya dates.
    """
    if not ts:
        return None
    s = ts.strip()
    # Reject video player progress counters e.g. "0:00 / 0:30"
    if re.search(r'\b\d+:\d+\s*/\s*\d+:\d+\b', s):
        return None

    target_default_year = default_year or FILTER_YEAR or datetime.now().year

    # 1. ISO format: 'YYYY-MM-DDTHH:MM:SS' or 'YYYY-MM-DD HH:MM:SS' or 'YYYY-MM-DD'
    try:
        if "T" in s:
            return datetime.fromisoformat(s.replace("Z", "+00:00"))
        if re.match(r'^\d{4}-\d{2}-\d{2}', s):
            clean_iso = s[:19].replace("T", " ")
            if len(clean_iso) >= 19:
                return datetime.strptime(clean_iso, "%Y-%m-%d %H:%M:%S")
            return datetime.strptime(s[:10], "%Y-%m-%d")
    except Exception:
        pass

    # 2. 'Weekday, Month Day, Year at Time' (e.g. 'Wednesday, October 21, 2026 at 3:19 PM', 'Oct 21, 2026 at 3:19:45 PM')
    m = re.search(
        rf'(?:(?:Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)[a-z]*,?\s+)?'
        rf'\b({_MONTH_NAMES_RE})\.?\s+([12]\d|3[01]|0?[1-9])(?:st|nd|rd|th)?,?\s*(\d{{4}})?'
        rf'(?:\s+(?:at|sa|nang|ng|@)\s+(\d{{1,2}}):(\d{{2}})(?::(\d{{2}}))?\s*(AM|PM|am|pm)?)?\b',
        s,
        re.I
    )
    if m:
        month_name = m.group(1).lower()
        month = MONTH_NAME_TO_INT.get(month_name)
        if month:
            day = int(m.group(2))
            year = int(m.group(3)) if m.group(3) else target_default_year
            hour = int(m.group(4)) if m.group(4) else 0
            minute = int(m.group(5)) if m.group(5) else 0
            second = int(m.group(6)) if m.group(6) else 0
            ampm = (m.group(7) or "").upper()
            if ampm == "PM" and hour < 12:
                hour += 12
            elif ampm == "AM" and hour == 12:
                hour = 0
            try:
                return datetime(year, month, day, hour, minute, second)
            except Exception:
                pass

    # 3. 'Day Month Year' (e.g. '21 October 2026 at 3:19 PM', '24 July 2022', '15 Pebrero 2020')
    m2 = re.search(
        rf'\b([12]\d|3[01]|0?[1-9])(?:st|nd|rd|th)?\s+({_MONTH_NAMES_RE})\.?\s*,?\s*(\d{{4}})?'
        rf'(?:\s+(?:at|sa|nang|ng|@)\s+(\d{{1,2}}):(\d{{2}})(?::(\d{{2}}))?\s*(AM|PM|am|pm)?)?\b',
        s,
        re.I
    )
    if m2:
        day = int(m2.group(1))
        month_name = m2.group(2).lower()
        month = MONTH_NAME_TO_INT.get(month_name)
        if month:
            year = int(m2.group(3)) if m2.group(3) else target_default_year
            hour = int(m2.group(4)) if m2.group(4) else 0
            minute = int(m2.group(5)) if m2.group(5) else 0
            second = int(m2.group(6)) if m2.group(6) else 0
            ampm = (m2.group(7) or "").upper()
            if ampm == "PM" and hour < 12:
                hour += 12
            elif ampm == "AM" and hour == 12:
                hour = 0
            try:
                return datetime(year, month, day, hour, minute, second)
            except Exception:
                pass

    # 4. 'Month Year' without day (e.g. 'February 2023', 'August 2019', 'Pebrero 2021')
    m3 = re.search(rf'\b({_MONTH_NAMES_RE})\.?\s*,?\s*(\d{{4}})\b', s, re.I)
    if m3:
        month_name = m3.group(1).lower()
        month = MONTH_NAME_TO_INT.get(month_name)
        if month:
            year = int(m3.group(2))
            try:
                return datetime(year, month, 1)
            except Exception:
                pass

    # 5. Numeric dates: MM/DD/YYYY or MM/DD/YY with optional time (e.g. '8/28/19', '8/28/19 6:03 pm', '08/28/2019 at 18:03')
    m_num = re.search(
        r'\b(\d{1,2})/(\d{1,2})/(\d{2,4})'
        r'(?:\s+(?:at|sa|nang|ng|@)?\s*(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM|am|pm)?)?\b',
        s,
        re.I
    )
    if m_num:
        try:
            month = int(m_num.group(1))
            day = int(m_num.group(2))
            yr_val = int(m_num.group(3))
            if yr_val < 100:
                yr_val += 2000
            hour = int(m_num.group(4)) if m_num.group(4) else 0
            minute = int(m_num.group(5)) if m_num.group(5) else 0
            second = int(m_num.group(6)) if m_num.group(6) else 0
            ampm = (m_num.group(7) or "").upper()
            if ampm == "PM" and hour < 12:
                hour += 12
            elif ampm == "AM" and hour == 12:
                hour = 0
            return datetime(yr_val, month, day, hour, minute, second)
        except Exception:
            pass

    # 6. Pure 4-digit year (e.g. '2019')
    if re.match(r'^\d{4}$', s):
        try:
            return datetime(int(s), 1, 1)
        except Exception:
            pass

    return None


def _is_within_date_filter(
    dt: datetime | None,
    filter_year: int | None = None,
    filter_months: list[int] | None = None,
    start_date: date | None = None,
    end_date: date | None = None,
    allow_undated: bool = False
) -> bool:
    """Check if a parsed datetime satisfies the active date filtering rules."""
    has_filter = (filter_year is not None or filter_months or start_date or end_date)
    if not has_filter:
        return True

    if dt is None:
        # When Facebook's native UI / year filter is active, posts returned on the page
        # are already pre-filtered by Facebook to the target time period.
        if filter_year is not None or allow_undated:
            return True
        return False

    if filter_year is not None and dt.year != filter_year:
        return False

    if filter_months and dt.month not in filter_months:
        return False

    if start_date is not None and dt.date() < start_date:
        return False

    if end_date is not None and dt.date() > end_date:
        return False

    return True


def _build_search_url(query: str, year: int | None = None, months: list[int] | None = None) -> str:
    """Build Facebook search URL (All section) with Facebook's native Date Posted filter."""
    target_year = year if year is not None else FILTER_YEAR
    target_months = months if months is not None else FILTER_MONTHS

    base = f"https://www.facebook.com/search/top/?q={quote_plus(query.strip())}"

    if target_year and APPLY_FB_YEAR_FILTER:
        if target_months and len(target_months) == 1:
            m = target_months[0]
            payload = {
                "rp_creation_time:0": json.dumps({
                    "name": "creation_time",
                    "args": json.dumps({
                        "start_year": str(target_year),
                        "start_month": f"{target_year}-{m}",
                        "end_year": str(target_year),
                        "end_month": f"{target_year}-{m}"
                    })
                })
            }
        else:
            payload = {
                "rp_creation_time:0": json.dumps({
                    "name": "creation_time",
                    "args": json.dumps({"year": str(target_year)})
                })
            }
        encoded = base64.b64encode(json.dumps(payload).encode('utf-8')).decode('utf-8')
        return f"{base}&filters={quote_plus(encoded)}"

    return base


def _apply_fb_ui_date_filter(driver, target_year: int | None, target_months: list[int] | None = None) -> bool:
    """Open Facebook's sidebar 'Date posted' dropdown in the 'All' search section and select target year."""
    if not target_year:
        return False
    year_str = str(target_year)
    log.info(f"  Attempting to set Facebook Date posted filter to '{year_str}'...")

    js_script = """
    const targetYear = arguments[0];
    
    // 1. Check if target year is already selected
    const allSpans = Array.from(document.querySelectorAll('span, div'));
    const isAlreadySelected = allSpans.some(el => 
        (el.textContent.trim() === targetYear) && 
        el.closest('div[aria-haspopup="listbox"], div[role="combobox"], div[aria-expanded="false"]')
    );
    if (isAlreadySelected) return { status: 'already_selected' };

    // 2. Locate the "Date posted" dropdown button in the sidebar
    let dropdownBtn = null;
    for (const el of allSpans) {
        const txt = el.textContent.trim().toLowerCase();
        if (txt === 'date posted' || txt === 'date' || txt === 'petsa') {
            const btn = el.closest('div[role="button"], div[tabindex], div[aria-haspopup], div[role="combobox"]');
            if (btn) {
                dropdownBtn = btn;
                break;
            }
        }
    }
    
    // Fallback: search by Any date if dropdown already shows 'Any date'
    if (!dropdownBtn) {
        for (const el of allSpans) {
            const txt = el.textContent.trim().toLowerCase();
            if (txt === 'any date' || txt === 'lahat ng petsa') {
                const btn = el.closest('div[role="button"], div[tabindex], div[aria-haspopup], div[role="combobox"]');
                if (btn) {
                    dropdownBtn = btn;
                    break;
                }
            }
        }
    }

    if (dropdownBtn) {
        dropdownBtn.click();
        return { status: 'opened_dropdown' };
    }
    return { status: 'not_found' };
    """

    try:
        res = driver.execute_script(js_script, year_str) or {}
        status = res.get("status")
        if status == 'already_selected':
            log.info(f"  ✓ Facebook Date posted filter is already set to {year_str}")
            return True

        if status == 'opened_dropdown':
            time.sleep(1.0)  # Wait for the dropdown options to render in DOM

            # Click the target year from the opened list
            js_click_year = """
            const targetYear = arguments[0];
            const allItems = Array.from(document.querySelectorAll('div[role="menuitemradio"], div[role="option"], div[role="menuitem"], div[role="button"], span'));
            for (const item of allItems) {
                if (item.textContent.trim() === targetYear) {
                    const clickable = item.closest('div[role="menuitemradio"], div[role="option"], div[role="menuitem"], div[role="button"]') || item;
                    clickable.click();
                    return true;
                }
            }
            return false;
            """
            clicked = driver.execute_script(js_click_year, year_str)
            if clicked:
                time.sleep(2.5)  # Wait for search feed to reload with year filter
                log.info(f"  ✓ Successfully selected '{year_str}' from Facebook 'Date posted' dropdown!")
                return True

        # Fallback via Selenium explicit elements
        dropdown_elements = driver.find_elements(
            By.XPATH,
            "//span[contains(text(), 'Date posted') or contains(text(), 'Date') or contains(text(), 'Any date')]/ancestor::div[@role='button' or @tabindex or @aria-haspopup][1]"
        )
        for d_btn in dropdown_elements:
            try:
                driver.execute_script("arguments[0].click();", d_btn)
                time.sleep(1.0)
                year_opt = driver.find_elements(
                    By.XPATH,
                    f"//span[text()='{year_str}']/ancestor::div[@role='menuitemradio' or @role='option' or @role='button' or @role='menuitem'][1] | //span[text()='{year_str}']"
                )
                for y_btn in year_opt:
                    if y_btn.is_displayed():
                        driver.execute_script("arguments[0].click();", y_btn)
                        time.sleep(2.5)
                        log.info(f"  ✓ Successfully selected '{year_str}' from Facebook 'Date posted' dropdown (Selenium fallback)!")
                        return True
            except Exception:
                pass
    except Exception as exc:
        log.debug(f"  UI date filter note: {exc}")
    return False

# ──────────────────────────────────────────────
# Browser setup
# ──────────────────────────────────────────────

def create_driver(profile_dir: str, *, headless: bool = False, block_images: bool = False) -> uc.Chrome:
    """Instantiate an undetected-chromedriver instance with a persistent
    Chrome profile so the Facebook session survives across runs."""

    options = uc.ChromeOptions()
    abs_profile_dir = os.path.abspath(profile_dir)
    options.add_argument(f"--user-data-dir={abs_profile_dir}")
    options.add_argument("--profile-directory=Default")
    options.add_argument("--disable-notifications")
    options.add_argument("--disable-popup-blocking")
    options.add_argument("--lang=en-US")

    if block_images:
        prefs = {
            "profile.managed_default_content_settings.images": 2,
            "profile.default_content_setting_values.notifications": 2,
        }
        options.add_experimental_option("prefs", prefs)
        options.add_argument("--blink-settings=imagesEnabled=false")

    if headless:
        options.add_argument("--headless=new")
        options.add_argument("--window-size=1920,1080")

    # Dynamically locate chromedriver matching installed Chrome via Selenium Manager
    # to prevent undetected_chromedriver from hanging while attempting to download older versions
    version_main = None
    driver_executable_path = None
    try:
        from selenium.webdriver.common.selenium_manager import SeleniumManager
        res = SeleniumManager().binary_paths(["--browser", "chrome"])
        driver_executable_path = res.get("driver_path")
        if driver_executable_path:
            v_match = re.search(r'[\\/](\d+)\.\d+\.\d+\.\d+[\\/]', driver_executable_path)
            if v_match:
                version_main = int(v_match.group(1))
    except Exception as exc:
        log.debug(f"SeleniumManager driver lookup warning: {exc}")

    driver_kwargs = {
        "options": options,
        "use_subprocess": False,
    }
    if version_main:
        driver_kwargs["version_main"] = version_main
    if driver_executable_path and os.path.exists(driver_executable_path):
        driver_kwargs["driver_executable_path"] = driver_executable_path

    try:
        driver = uc.Chrome(**driver_kwargs)
    except Exception as exc:
        log.warning(f"Driver launch with specific executable failed: {exc}. Retrying standard...")
        driver_kwargs.pop("driver_executable_path", None)
        driver_kwargs.pop("version_main", None)
        driver = uc.Chrome(**driver_kwargs)

    driver.set_page_load_timeout(60)
    driver.implicitly_wait(5)
    return driver


def ensure_logged_in(driver) -> bool:
    """Navigate to Facebook and return True if already authenticated."""
    curr_url = driver.current_url.lower()
    if "facebook.com" not in curr_url or "login" in curr_url:
        driver.get("https://www.facebook.com/")
        _human_pause(2.5, 4.0)

    # Detect the logged-in state by looking for profile nav, composer, or main feed
    try:
        WebDriverWait(driver, 8).until(
            EC.presence_of_element_located(
                (By.CSS_SELECTOR, '[aria-label="Your profile"], [aria-label="Create a post"], [role="navigation"], [role="feed"], [data-pagelet="Feed"], div[role="main"]')
            )
        )
        log.info("✓ Active Facebook session detected.")
        return True
    except TimeoutException:
        log.warning("✗ No active session – manual or automated login required.")
        return False


def login_with_credentials(driver, email: str, passw: str) -> bool:
    """Automate Facebook login with given credentials, waiting gracefully if a CAPTCHA or 2FA occurs."""
    if ensure_logged_in(driver):
        return True

    log.info("Navigating to Facebook login page…")
    driver.get("https://www.facebook.com/login")
    _human_pause(2.0, 3.5)

    try:
        email_input = WebDriverWait(driver, 6).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "input#email, input[name='email']"))
        )
        pass_input = driver.find_element(By.CSS_SELECTOR, "input#pass, input[name='pass']")
        
        email_input.clear()
        email_input.send_keys(email)
        _human_pause(0.5, 1.0)
        pass_input.clear()
        pass_input.send_keys(passw)
        _human_pause(0.5, 1.0)

        login_btn = driver.find_element(By.CSS_SELECTOR, "button#loginbutton, button[name='login'], button[type='submit']")
        login_btn.click()
        log.info("Submitted login credentials.")
    except Exception as exc:
        log.debug(f"Form submission note: {exc}")

    log.info("Waiting for session confirmation… If a CAPTCHA or Two-Factor Authentication appears, please solve it in the browser window.")
    start_t = time.time()
    while time.time() - start_t < 180:
        if ensure_logged_in(driver):
            log.info("✓ Successfully authenticated to Facebook!")
            return True
        time.sleep(3.5)

    return False

# ──────────────────────────────────────────────
# Post extraction logic
# ──────────────────────────────────────────────

JS_BATCH_EXTRACT_POSTS = """
function cleanTimestampString(s) {
    if (!s) return '';
    let t = s.replace(/[\\r\\n\\t\\u00a0]+/g, ' ').trim();
    // Strip emojis, zero-width characters, combining grapheme joiner (0x034F), and Unicode private-use area characters
    t = t.replace(/[\\uE000-\\uF8FF\\uD800-\\uDFFF\\uFFF0-\\uFFFF\\u200B-\\u200F\\u202A-\\u202E\\uFEFF\\u0300-\\u036F]/g, '');
    // Reject video player progress counters e.g. "0:00 / 0:30"
    if (/\\b\\d+:\\d+\\s*\\/\\s*\\d+:\\d+\\b/.test(t)) return '';
    // Reject photo description alt texts e.g. "May be an image of..."
    if (/^(?:may be an image|photo description|no photo description)/i.test(t)) return '';
    // Strip privacy indicators like "· Public", "· Shared with Public", "· Friends", "· Custom", "·"
    t = t.replace(/\\s*[·•|]\\s*(?:public|shared with[^\\n\\r]*|friends|custom|only me|kaniadto|tanan|publiko|mga higala)?\\s*$/i, '');
    t = t.replace(/^\\s*[·•|]\\s*/, '');
    t = t.replace(/\\s+(?:public|shared with\\s+public|shared with:?\\s*public)$/i, '');
    t = t.replace(/\\s*[·•|]\\s*$/, '');
    return t.trim();
}

function cleanAuthorName(name) {
    if (!name) return '';
    let t = name.trim();
    let lines = t.split(/[\\r\\n]+/);
    let first = lines[0].trim();
    first = first.replace(/\\s*[·•|]\\s*(?:follow|following|active|joined|top fan|admin|moderator|author|public)?\\s*$/i, '');
    first = first.replace(/\\s+(?:is with|is in|is at|nasa|kasama si|kasama ang)\\s+.*$/i, '');
    first = first.replace(/\\s*[·•|]\\s*\\d+\\s*(?:h|hr|hrs|m|min|mins|d|w|y)?\\s*$/i, '');
    first = first.replace(/\\s*[·•|]\\s*$/, '');
    return first.trim();
}

function isValidAuthor(t) {
    if (!t || t.length < 2 || t.length > 90) return false;
    t = t.trim();
    if (t.startsWith('#') || t.startsWith('http') || t.startsWith('@')) return false;
    const low = t.toLowerCase();
    const bad = [
        'see more', 'see less', 'like', 'comment', 'share', 'follow', 'facebook',
        'public', 'joined', 'replies', 'reaction', 'suggested', 'sponsored',
        'tan-awa', 'tingnan', 'photos', 'videos', 'groups', 'reels',
        'top fans', 'following', 'admin', 'moderator', 'unknown', 'shared with',
        'news feed', 'write a comment', 'log in', 'sign up', 'notifications'
    ];
    return !bad.some(b => low === b || low.startsWith(b));
}

function extractTimestamp(t) {
    if (!t || t.length > 90) return '';
    let cleaned = cleanTimestampString(t);
    if (!cleaned) return '';
    if (cleaned.startsWith('#') || cleaned.startsWith('http') || cleaned.startsWith('@')) return '';
    if (/\\b\\d+:\\d+\\s*\\/\\s*\\d+:\\d+\\b/.test(cleaned)) return '';
    const low = cleaned.toLowerCase();
    if (low.startsWith('may be an image') || low.startsWith('no photo')) return '';
    
    // Ignore pure UI buttons (exact match or prefix)
    const exactBad = ['see more', 'see less', 'like', 'comment', 'share', 'follow', 'facebook', 'public', 'joined', 'replies', 'reaction', 'author', 'suggested', 'sponsored', 'view more', 'hide', 'unknown', 'write a comment', 'log in', 'sign up'];
    if (exactBad.some(b => low === b || low.startsWith(b + ' '))) return '';

    const patterns = [
        /\\b(?:just now|now|yesterday|today|kahapon|karon|kagahapon|bag-o lang|ngayon)\\b/i,
        /\\b\\d+\\s*(?:s|sec|secs|second|seconds|segundo)\\b/i,
        /\\b\\d+\\s*(?:m|min|mins|minute|minutes|minuto)\\b/i,
        /\\b\\d+\\s*(?:h|hr|hrs|hour|hours|oras)\\b/i,
        /\\b\\d+\\s*(?:d|day|days|adlaw|araw)\\b/i,
        /\\b\\d+\\s*(?:w|wk|wks|week|weeks|semana|linggo)\\b/i,
        /\\b\\d+\\s*(?:y|yr|yrs|year|years)\\b/i,
        /\\b\\d+\\s*(?:ka\\s*)?(?:segundo|minuto|oras|adlaw|araw|semana|linggo)(?:\\s+(?:ang\\s+)?(?:nakalipas|milabay|nakalabay))?\\b/i,
        /\\b(?:(?:monday|tuesday|wednesday|thursday|friday|saturday|sunday)[a-z]*,?\\s+)?(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|enero|pebrero|marso|abril|mayo|hunyo|hulyo|agosto|setyembre|oktubre|nobiyembre|nobyembre|disyembre)[a-z]*\\.?\\s+\\d{1,2}(?:st|nd|rd|th)?(?:,?\\s+\\d{4})?(?:\\s+(?:at|sa|nang|ng|@)\\s+\\d{1,2}:\\d{2}(?::\\d{2})?\\s*(?:am|pm)?)?\\b/i,
        /\\b\\d{1,2}(?:st|nd|rd|th)?\\s+(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|enero|pebrero|marso|abril|mayo|hunyo|hulyo|agosto|setyembre|oktubre|nobiyembre|nobyembre|disyembre)[a-z]*\\.?(?:,?\\s+\\d{4})?(?:\\s+(?:at|sa|nang|ng|@)\\s+\\d{1,2}:\\d{2}(?::\\d{2})?\\s*(?:am|pm)?)?\\b/i,
        /\\b(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|enero|pebrero|marso|abril|mayo|hunyo|hulyo|agosto|setyembre|oktubre|nobiyembre|nobyembre|disyembre)[a-z]*\\.?\\s+\\d{4}\\b/i,
        /\\b\\d{1,2}:\\d{2}\\s*(?:am|pm|AM|PM)?\\b/i,
        /\\b\\d{1,2}\\/\\d{1,2}\\/\\d{2,4}\\b/,
        /\\b\\d{4}-\\d{2}-\\d{2}\\b/
    ];
    for (let p of patterns) {
        let m = cleaned.match(p);
        if (m) {
            if (cleaned.length <= 60) return cleaned;
            return m[0].trim();
        }
    }
    return '';
}

function isAbsoluteTimestampString(s) {
    if (!s) return false;
    const monthRegex = /(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|enero|pebrero|marso|abril|mayo|hunyo|hulyo|agosto|setyembre|oktubre|nobiyembre|nobyembre|disyembre|\\d{4}-\\d{2}-\\d{2}|\\b\\d{1,2}\\/\\d{1,2}\\/\\d{2,4}\\b)/i;
    return monthRegex.test(s);
}

function triggerHover(el) {
    if (!el) return;
    try {
        const mouseOpts = { bubbles: true, cancelable: true, view: window, composed: true };
        el.dispatchEvent(new MouseEvent('mouseenter', mouseOpts));
        el.dispatchEvent(new MouseEvent('mouseover', mouseOpts));
        el.dispatchEvent(new MouseEvent('mousemove', mouseOpts));
    } catch(e) {}
}

function resolveAriaLabelledBy(el) {
    if (!el) return '';
    try {
        const targets = [el, ...Array.from(el.querySelectorAll('[aria-labelledby]'))];
        for (let target of targets) {
            const idAttr = target.getAttribute('aria-labelledby');
            if (idAttr) {
                const ids = idAttr.split(/\\s+/);
                for (let id of ids) {
                    const ref = document.getElementById(id);
                    if (ref) {
                        const txt = cleanTimestampString(ref.textContent || ref.innerText || '');
                        if (txt && extractTimestamp(txt)) return extractTimestamp(txt);
                    }
                }
            }
        }
    } catch(e) {}
    return '';
}

function resolveSvgUse(el) {
    if (!el) return '';
    try {
        const uses = Array.from(el.querySelectorAll('svg use'));
        for (let u of uses) {
            const href = u.getAttribute('xlink:href') || u.getAttribute('href') || '';
            if (href && href.startsWith('#')) {
                let curr = document.querySelector(href);
                let depth = 0;
                while (curr && depth < 6) {
                    const txt = cleanTimestampString(curr.textContent || '');
                    if (txt && extractTimestamp(txt)) return extractTimestamp(txt);
                    const nestedUse = curr.querySelector('use');
                    if (nestedUse) {
                        const nHref = nestedUse.getAttribute('xlink:href') || nestedUse.getAttribute('href') || '';
                        if (nHref && nHref.startsWith('#')) {
                            curr = document.querySelector(nHref);
                            depth++;
                            continue;
                        }
                    }
                    break;
                }
            }
        }
    } catch(e) {}
    return '';
}

function unscrambleElementText(rootEl) {
    if (!rootEl) return '';
    try {
        const rootRect = rootEl.getBoundingClientRect();
        if (rootRect.width === 0 || rootRect.height === 0) {
            return cleanTimestampString(rootEl.innerText || rootEl.textContent || '');
        }

        const leafSpans = Array.from(rootEl.querySelectorAll('span')).filter(s => s.children.length === 0);
        if (leafSpans.length >= 2) {
            const visible = [];
            for (const el of leafSpans) {
                const txt = (el.textContent || '').replace(/[\\u0300-\\u036F\\u200B-\\u200F\\u202A-\\u202E\\uFEFF]/g, '');
                if (!txt) continue;

                const rect = el.getBoundingClientRect();
                // Must have real physical dimensions on screen
                if (rect.width <= 0.5 || rect.height <= 0.5) continue;

                // Must sit inside the parent timestamp link's bounding box (filters out top: 3em decoy spans)
                if (rect.top < rootRect.top - 4 || rect.bottom > rootRect.bottom + 6) continue;
                if (rect.left < rootRect.left - 4 || rect.right > rootRect.right + 6) continue;

                // Check computed styles on the leaf span and its ancestors up to rootEl
                let hidden = false;
                let curr = el;
                while (curr && curr !== rootEl) {
                    const st = window.getComputedStyle(curr);
                    const topPx = parseFloat(st.top) || 0;
                    const fontPx = parseFloat(st.fontSize) || 12;
                    if (
                        st.display === 'none' ||
                        st.visibility === 'hidden' ||
                        parseFloat(st.opacity || '1') <= 0.01 ||
                        fontPx <= 1 ||
                        Math.abs(topPx) > 6 ||
                        (st.clip && st.clip !== 'auto' && st.clip.includes('0px')) ||
                        (st.clipPath && st.clipPath !== 'none')
                    ) {
                        hidden = true;
                        break;
                    }
                    curr = curr.parentElement;
                }
                if (hidden) continue;

                visible.push({ text: txt, x: rect.left, y: rect.top });
            }

            if (visible.length > 0) {
                // Keep only characters on the primary vertical baseline
                const minTop = Math.min(...visible.map(v => v.y));
                const sameLine = visible.filter(v => Math.abs(v.y - minTop) <= 6);
                sameLine.sort((a, b) => a.x - b.x);
                const reconstructed = sameLine.map(v => v.text).join('').trim();
                if (reconstructed) return cleanTimestampString(reconstructed);
            }
        }
    } catch(e) {}
    return cleanTimestampString(rootEl.innerText || rootEl.textContent || '');
}

function extractReactFiberTimestamp(rootEl) {
    if (!rootEl) return '';
    try {
        const nodes = [rootEl, ...Array.from(rootEl.querySelectorAll('a[role="link"], a[href], span[role="link"]'))];
        for (const node of nodes) {
            for (const key in node) {
                if (key.startsWith('__reactFiber') || key.startsWith('__reactProps')) {
                    let curr = node[key];
                    for (let depth = 0; depth < 9 && curr; depth++) {
                        const props = curr.memoizedProps || curr.pendingProps || curr;
                        if (props && typeof props === 'object') {
                            // Check for Unix epoch seconds in React props
                            const epoch = props.creation_time || props.publish_time || props.time ||
                                          (props.story && (props.story.creation_time || props.story.publish_time));
                            if (epoch && typeof epoch === 'number' && epoch > 1200000000 && epoch < 2500000000) {
                                return new Date(epoch * 1000).toISOString();
                            }
                            // Check for tooltip / label strings in CometTooltip props
                            const tip = props.tooltip || props.label || props['aria-label'];
                            if (tip && typeof tip === 'string') {
                                const ts = extractTimestamp(tip);
                                if (ts && isAbsoluteTimestampString(ts)) return ts;
                            }
                        }
                        curr = curr.return;
                    }
                }
            }
        }
    } catch(e) {}
    return '';
}

function getElementTimestampAndTrigger(el) {
    if (!el) return { timestamp: '', rawTimestamp: '', isAbsolute: false };

    // 0. Pre-hovered tooltip cached by Python ActionChains pass
    let hoveredTs = cleanTimestampString(el.getAttribute('data-hovered-ts') || '');
    if (hoveredTs && extractTimestamp(hoveredTs)) {
        let ts = extractTimestamp(hoveredTs);
        return { timestamp: ts, rawTimestamp: hoveredTs, isAbsolute: isAbsoluteTimestampString(ts) };
    }

    // 1. Primary: Extract from aria-label
    let aria = cleanTimestampString(el.getAttribute('aria-label') || '');
    if (aria && extractTimestamp(aria)) {
        let ts = extractTimestamp(aria);
        if (isAbsoluteTimestampString(ts)) {
            return { timestamp: ts, rawTimestamp: ts, isAbsolute: true };
        }
    }

    // 2. Primary: Extract via aria-labelledby
    let byLabel = resolveAriaLabelledBy(el);
    if (byLabel) {
        return { timestamp: byLabel, rawTimestamp: byLabel, isAbsolute: isAbsoluteTimestampString(byLabel) };
    }

    // 3. Primary: Extract via SVG use reference
    let bySvg = resolveSvgUse(el);
    if (bySvg) {
        return { timestamp: bySvg, rawTimestamp: bySvg, isAbsolute: isAbsoluteTimestampString(bySvg) };
    }

    // 4. Primary: Unscramble CSS child spans (Facebook anti-scraping bypass)
    let unscrambled = unscrambleElementText(el);
    if (unscrambled && extractTimestamp(unscrambled)) {
        let ts = extractTimestamp(unscrambled);
        if (isAbsoluteTimestampString(ts)) {
            return { timestamp: ts, rawTimestamp: ts, isAbsolute: true };
        }
    }

    // 5. Title attribute & data-utime
    let title = cleanTimestampString(el.getAttribute('title') || '');
    if (title && extractTimestamp(title)) {
        let ts = extractTimestamp(title);
        if (isAbsoluteTimestampString(ts)) {
            return { timestamp: ts, rawTimestamp: ts, isAbsolute: true };
        }
    }

    let utime = el.getAttribute('data-utime') || el.getAttribute('datetime') || el.getAttribute('data-time') || el.getAttribute('data-timestamp');
    if (utime && /^\\d+$/.test(utime)) {
        try {
            let iso = new Date(parseInt(utime) * 1000).toISOString();
            return { timestamp: iso, rawTimestamp: utime, isAbsolute: true };
        } catch(e) {}
    }

    // 6. Relative fallbacks (without triggering synchronous hover on random DOM nodes)
    if (unscrambled && extractTimestamp(unscrambled)) {
        let ts = extractTimestamp(unscrambled);
        return { timestamp: ts, rawTimestamp: ts, isAbsolute: false };
    }
    if (aria && extractTimestamp(aria)) {
        let ts = extractTimestamp(aria);
        return { timestamp: ts, rawTimestamp: ts, isAbsolute: false };
    }
    return { timestamp: '', rawTimestamp: '', isAbsolute: false };
}

function cleanFacebookUrl(href) {
    if (!href) return '';
    if (href.startsWith('/')) href = 'https://www.facebook.com' + href;
    try {
        let u = new URL(href, 'https://www.facebook.com');
        let pathLower = u.pathname.toLowerCase();
        let needsParams = ['permalink.php', 'story.php', 'photo.php', 'video.php', 'photo', 'watch', 'media/set', 'profile.php'].some(k => pathLower.includes(k));
        let essential = ['story_fbid', 'id', 'fbid', 'v', 'set', 'post_id', 'theater'];
        let params = new URLSearchParams(u.search);
        let newParams = new URLSearchParams();
        if (needsParams) {
            for (let k of essential) {
                if (params.has(k)) newParams.set(k, params.get(k));
            }
            let qs = newParams.toString();
            return u.origin + u.pathname.replace(/\\/+$/, '') + (qs ? '?' + qs : '');
        } else {
            return u.origin + u.pathname.replace(/\\/+$/, '');
        }
    } catch(e) {
        return href.split('?')[0];
    }
}

function isPostUrl(href) {
    if (!href) return false;
    let path = '';
    try {
        path = new URL(href, 'https://www.facebook.com').pathname.toLowerCase().replace(/\\/+$/, '');
    } catch(e) {
        path = href.split('?')[0].split('#')[0].toLowerCase().replace(/\\/+$/, '');
    }

    // Exclude non-post and generic UI links by PATH (never by query params)
    const badPaths = [
        '/hashtag', '/friends', '/login', '/recover', '/help',
        '/settings', '/messages', '/marketplace', '/gaming',
        '/events', '/policies', '/sharer', '/home.php',
        '/search/posts', '/search/top', '/search', '/notifications'
    ];
    if (badPaths.some(b => path === b || path.startsWith(b + '/'))) return false;

    // Check root paths that require query parameters
    if (['', '/', '/watch', '/reels', '/photos', '/videos', '/groups', '/stories', '/photo'].includes(path)) {
        if (!/[?&](?:v|fbid|story_fbid|set)=\\w+/i.test(href)) return false;
    }

    const patterns = [
        /\\/posts\\//i, /\\/permalink\\//i, /permalink\\.php/i, /story\\.php/i, /story_fbid=/i,
        /\\/photos?\\//i, /photo\\.php/i, /\\/photo\\?/i, /[?&]fbid=\\d+/i, /\\/videos?\\//i, /video\\.php/i,
        /\\/watch\\/?(\\?|.*v=)/i, /\\/watch\\b/i, /\\/reel\\//i, /\\/reels\\//i,
        /\\/share\\/[pvr]\\//i, /\\/share\\/[A-Za-z0-9_-]{5,}/i,
        /\\/groups\\/[^/]+\\/posts\\/\\d+/i, /\\/groups\\/[^/]+\\/permalink\\/\\d+/i,
        /\\/groups\\/[^/]+\\/user\\/\\d+/i,
        /\\/multi_permalinks\\//i,
        /pfbid[A-Za-z0-9]+/i
    ];
    return patterns.some(p => p.test(href));
}

function cleanPostText(text) {
    if (!text) return '';
    let t = text.replace(/\\s*…\\s*(?:See more|See More|see more|Tan-awa pa|Tingnan pa|Mas marami)\\s*$/ig, '');
    t = t.replace(/\\s*…\\s*$/g, '');
    let lines = t.split(/[\\r\\n]+/);
    let kept = [];
    for (let l of lines) {
        let trimmed = l.trim();
        if (!trimmed) continue;
        if (/^[A-Za-z0-9_-]{20,}$/.test(trimmed)) continue; // Decoy anti-scraping character token
        if (/^(?:m\\.me|send message|message|follow|share|subscribe)$/i.test(trimmed)) continue;
        kept.push(trimmed);
    }
    return kept.join('\\n').trim();
}

function extractSingleCard(card) {
    let root = card;
    let articleParent = card.closest ? card.closest('[role="article"], [data-ad-preview], .userContentWrapper') : null;
    if (articleParent) {
        root = articleParent;
    }

    // 1. Text
    let textDivs = root.querySelectorAll('div[dir="auto"], span[dir="auto"], [data-ad-preview="message"], [data-ad-comet-preview="message"], [data-ad-rendering-role="story_message"], p');
    let parts = [];
    for (let d of textDivs) {
        if (d.closest('div[role="dialog"], [role="tooltip"], div[data-visualcompletion="tooltip-target"]')) continue;
        let t = (d.innerText || d.textContent || '').trim();
        t = cleanPostText(t);
        if (t.length > 2 && !parts.includes(t)) parts.push(t);
    }
    let fullText = parts.join('\\n');
    if (!fullText || fullText.length < 3) {
        let raw = (root.innerText || root.textContent || '').trim();
        fullText = cleanPostText(raw);
    }

    // 2. Author
    let author = '';
    let authorCandidates = root.querySelectorAll('h2 a, h3 a, h4 a, [role="heading"] a, strong a, a strong, span > strong, [data-ad-rendering-role="profile_name"], h2, h3, h4, [role="heading"]');
    for (let el of authorCandidates) {
        let raw = (el.innerText || el.textContent || '').trim();
        let cleaned = cleanAuthorName(raw);
        if (isValidAuthor(cleaned)) {
            author = cleaned;
            break;
        }
    }
    if (!author) {
        let links = root.querySelectorAll('a[role="link"], a[href]');
        for (let a of links) {
            let href = a.href || a.getAttribute('href') || '';
            if (isPostUrl(href) || href.includes('/hashtag/')) continue;
            let raw = (a.innerText || a.textContent || '').trim();
            let cleaned = cleanAuthorName(raw);
            if (isValidAuthor(cleaned)) {
                author = cleaned;
                break;
            }
        }
    }
    if (!author) {
        let imgs = root.querySelectorAll('img[alt*="profile" i], img[alt*="profile picture" i], img[alt*="avatar" i]');
        for (let img of imgs) {
            let alt = (img.getAttribute('alt') || '').replace(/(?:'s\\s+profile\\s+picture|profile\\s+picture\\s+of\\s+|profile\\s+of\\s+|avatar)/ig, '').trim();
            let cleaned = cleanAuthorName(alt);
            if (isValidAuthor(cleaned)) {
                author = cleaned;
                break;
            }
        }
    }

    // 3. Post URL and Timestamp Extraction
    let timestamp = '';
    let rawTimestamp = '';
    let postUrl = '';
    let foundAbsolute = false;

    // Step 3.0a: Direct tooltip timestamp cached during Python ActionChains hover pass
    let preHovered = card.getAttribute('data-hovered-ts') || (root && root.getAttribute ? root.getAttribute('data-hovered-ts') : '') || '';
    if (preHovered) {
        let cleanHover = cleanTimestampString(preHovered);
        let extracted = extractTimestamp(cleanHover);
        if (extracted) {
            timestamp = extracted;
            rawTimestamp = cleanHover;
            foundAbsolute = isAbsoluteTimestampString(extracted);
        }
    }

    // Step 3.0b: Check React Fiber tree for exact Unix creation_time or CometTooltip prop
    if (!foundAbsolute) {
        let fiberTs = extractReactFiberTimestamp(root);
        if (fiberTs) {
            timestamp = fiberTs;
            rawTimestamp = fiberTs;
            foundAbsolute = true;
        }
    }

    // Step 3a: Priority search on header containers (under headings / author)
    let headerCandidates = root.querySelectorAll('h2 ~ div a, h3 ~ div a, h4 ~ div a, [role="heading"] ~ div a, [data-ad-rendering-role="profile_name"] ~ div a, a[role="link"], span[role="link"], a[href*="/posts/"], a[href*="/permalink"], a[href*="story.php"], a[href*="story_fbid="], a[href*="pfbid"], a[href*="/videos/"], a[href*="/watch"], a[href*="/reel/"], a[href*="/reels/"], a[href*="/photo"], a[href*="fbid="], a[href*="/share/"], a[href*="/groups/"], a[href]');
    for (let el of headerCandidates) {
        let href = el.href || el.getAttribute('href') || '';
        if (href && href.includes('/hashtag/')) continue;

        if (isPostUrl(href) && !postUrl) {
            postUrl = cleanFacebookUrl(href);
        }

        // Strictly skip author links and avatars
        let elText = (el.innerText || el.textContent || '').trim().toLowerCase();
        if (author && (elText === author.toLowerCase() || elText.startsWith(author.toLowerCase()))) continue;
        if (el.getAttribute('data-ad-rendering-role') === 'profile_name') continue;
        if (el.querySelector('strong, h2, h3, h4, svg image, img[alt*="profile" i], img[alt*="avatar" i]')) continue;

        if (!foundAbsolute) {
            let res = getElementTimestampAndTrigger(el);
            if (res.isAbsolute) {
                timestamp = res.timestamp;
                rawTimestamp = res.rawTimestamp;
                foundAbsolute = true;
            } else if (!timestamp && res.timestamp) {
                timestamp = res.timestamp;
                rawTimestamp = res.rawTimestamp;
            }
        }

        if (postUrl && foundAbsolute) break;
    }

    // Step 3b: Search other candidates if timestamp still missing
    if (!timestamp) {
        let otherCandidates = root.querySelectorAll('span[tabindex], div[role="button"], [aria-label], [aria-labelledby], abbr, time, svg text, span[dir="auto"], span');
        for (let el of otherCandidates) {
            let elText = (el.innerText || el.textContent || '').trim().toLowerCase();
            if (author && (elText === author.toLowerCase() || elText.startsWith(author.toLowerCase()))) continue;
            if (el.closest('h2, h3, h4, [role="heading"]')) continue;
            if (el.getAttribute('data-ad-rendering-role') === 'profile_name') continue;

            let res = getElementTimestampAndTrigger(el);
            if (res.timestamp) {
                timestamp = res.timestamp;
                rawTimestamp = res.rawTimestamp;
                if (res.isAbsolute) break;
            }
        }
    }

    // Dismiss any stray Facebook profile hovercards so they never obstruct subsequent posts
    try {
        document.querySelectorAll('div[role="dialog"]').forEach(d => {
            const txt = (d.innerText || '');
            if ((txt.includes('Followers') || txt.includes('Subscribe') || txt.includes('Message')) && !d.querySelector('video, img[src*="scontent"]')) {
                d.remove();
            }
        });
    } catch(e) {}

    // Step 3c: Fallback dataset & video attributes for postUrl
    if (!postUrl) {
        let dataEls = root.querySelectorAll('[data-ft], [data-video-id], [data-id], [data-lynx-uri], [data-href]');
        for (let el of dataEls) {
            let ft = el.getAttribute('data-ft');
            if (ft) {
                try {
                    let obj = JSON.parse(ft);
                    let storyKey = obj.mf_story_key || obj.top_level_post_id || obj.post_id;
                    if (storyKey) {
                        let pageId = obj.page_id || obj.content_owner_id_new;
                        postUrl = pageId ? ('https://www.facebook.com/' + pageId + '/posts/' + storyKey) : ('https://www.facebook.com/' + storyKey);
                        break;
                    }
                } catch(e) {}
            }
            let vid = el.getAttribute('data-video-id');
            if (vid && /^\\d+$/.test(vid)) {
                postUrl = 'https://www.facebook.com/watch/?v=' + vid;
                break;
            }
            let lynx = el.getAttribute('data-lynx-uri') || el.getAttribute('data-href');
            if (lynx && isPostUrl(lynx)) {
                postUrl = cleanFacebookUrl(lynx);
                break;
            }
        }
    }

    // Step 3d: React internal fiber tree props inspection
    if (!postUrl) {
        let checkNodes = [root];
        let interactiveEls = root.querySelectorAll('a, [role="link"], [role="button"], video, div[data-video-id]');
        for (let el of interactiveEls) checkNodes.push(el);

        for (let node of checkNodes) {
            if (!node) continue;
            for (let k in node) {
                if (k.startsWith('__reactProps') || k.startsWith('__reactFiber')) {
                    try {
                        let p = node[k];
                        let curr = p;
                        for (let d = 0; d < 6 && curr; d++) {
                            let m = curr.memoizedProps || curr.pendingProps || curr;
                            if (m) {
                                let candidate = m.url || m.href || m.permalink || (m.post_id ? 'https://www.facebook.com/' + m.post_id : '') || (m.story_fbid ? 'https://www.facebook.com/story.php?story_fbid=' + m.story_fbid : '') || (m.video_id ? 'https://www.facebook.com/watch/?v=' + m.video_id : '');
                                if (candidate && typeof candidate === 'string' && isPostUrl(candidate)) {
                                    postUrl = cleanFacebookUrl(candidate);
                                    break;
                                }
                            }
                            curr = curr.return || curr.child;
                        }
                    } catch(e) {}
                }
                if (postUrl) break;
            }
            if (postUrl) break;
        }
    }

    // Step 3e: Check ancestor container links if still missing post URL
    if (!postUrl && root.parentElement && root.parentElement.tagName !== 'BODY') {
        let p = root.parentElement;
        for (let depth = 0; depth < 3 && p && p.tagName !== 'BODY'; depth++) {
            let parentLinks = Array.from(p.querySelectorAll('a[href]'));
            for (let a of parentLinks) {
                let href = a.href || a.getAttribute('href') || '';
                if (href && !href.includes('/hashtag/') && isPostUrl(href)) {
                    postUrl = cleanFacebookUrl(href);
                    break;
                }
            }
            if (postUrl) break;
            p = p.parentElement;
        }
    }

    // Step 3f: Fallback date extraction from fullText if timestamp is still empty
    if (!timestamp && fullText) {
        const textDatePatterns = [
            /(?:as of\\s+)?(?:\\d{1,2}:\\d{2}(?::\\d{2})?\\s*(?:am|pm)?\\s+)?\\b\\d{1,2}\\/\\d{1,2}\\/\\d{2,4}(?:\\s+\\d{1,2}:\\d{2}(?::\\d{2})?\\s*(?:am|pm)?)?\\b/i,
            /\\b(?:january|february|march|april|may|june|july|august|september|october|november|december|enero|pebrero|marso|abril|mayo|hunyo|hulyo|agosto|setyembre|oktubre|nobiyembre|nobyembre|disyembre)\\s+\\d{1,2}(?:st|nd|rd|th)?(?:,?\\s+\\d{4})?(?:\\s+(?:at|sa|nang|ng|@)\\s+\\d{1,2}:\\d{2}\\s*(?:am|pm)?)?\\b/i,
            /\\b\\d{1,2}(?:st|nd|rd|th)?\\s+(?:january|february|march|april|may|june|july|august|september|october|november|december|enero|pebrero|marso|abril|mayo|hunyo|hulyo|agosto|setyembre|oktubre|nobiyembre|nobyembre|disyembre)(?:,?\\s+\\d{4})?\\b/i,
            /\\b\\d{4}-\\d{2}-\\d{2}\\b/
        ];
        for (let p of textDatePatterns) {
            let m = fullText.match(p);
            if (m) {
                timestamp = m[0].trim();
                break;
            }
        }
    }

    // 4. Media URLs
    let mediaUrls = [];
    let imgs = root.querySelectorAll('img');
    for (let img of imgs) {
        let src = img.src || img.getAttribute('src') || '';
        if (src && (src.includes('scontent') || src.includes('fbcdn'))) {
            let w = img.width || img.naturalWidth || 0;
            if (w >= 50 || (!img.width && !img.naturalWidth)) {
                mediaUrls.push(src);
            }
        }
    }

    return {
        fullText: fullText,
        postUrl: postUrl,
        timestamp: timestamp,
        rawTimestamp: rawTimestamp || timestamp,
        author: author,
        mediaUrls: Array.from(new Set(mediaUrls))
    };
}

window.__fb_extractSingleCard = extractSingleCard;
window.__fb_findTimestampElement = function(card) {
    // IMPORTANT: Facebook renders the header timestamp link with href="#" and only
    // swaps in the real permalink on mouseover. Matching by href therefore picks the
    // PHOTO attachment link instead. We locate the timestamp by GEOMETRY instead:
    // it is the small link sitting directly underneath the author name.
    if (!card) return null;
    const cardRect = card.getBoundingClientRect();

    // 1. Find the author-name anchor element (top-most heading/profile name in card)
    let authorEl = null;
    const authorSel = '[data-ad-rendering-role="profile_name"], h2, h3, h4, [role="heading"], strong';
    for (const el of card.querySelectorAll(authorSel)) {
        const r = el.getBoundingClientRect();
        if (r.width < 5 || r.height < 5) continue;
        if (r.top - cardRect.top > 250) continue;           // must be in card header
        if (el.closest('[role="dialog"], [role="tooltip"]')) continue;
        authorEl = el;
        break;
    }

    const isImageLink = (a) => !!a.querySelector('img, svg image, video');
    const isHashtag = (a) => /\\/hashtag\\//.test(a.getAttribute('href') || '');
    const inAuthor = (a) => authorEl && (authorEl.contains(a) || a.contains(authorEl));

    const candidates = Array.from(card.querySelectorAll('a, span[role="link"], [role="link"][tabindex]'))
        .filter(a => !isImageLink(a) && !isHashtag(a) && !inAuthor(a))
        .filter(a => !a.closest('[role="dialog"], [role="tooltip"]'));

    // 2. Geometry match: small link just below the author name, horizontally aligned
    if (authorEl) {
        const ar = authorEl.getBoundingClientRect();
        let best = null, bestScore = Infinity;
        for (const a of candidates) {
            const r = a.getBoundingClientRect();
            if (r.width < 8 || r.height < 6 || r.height > 40 || r.width > 350) continue;
            const dy = r.top - ar.bottom;                    // vertical gap below author
            if (dy < -6 || dy > 40) continue;
            const dx = r.left - ar.left;                     // horizontal offset from author start
            if (dx < -20 || dx > 260) continue;
            const score = Math.abs(dy) * 3 + Math.abs(dx);
            if (score < bestScore) { bestScore = score; best = a; }
        }
        if (best) return best;
    }

    // 3. Placeholder-href match: header anchor with href="#" (Facebook's lazy timestamp link)
    for (const a of candidates) {
        const raw = a.getAttribute('href') || '';
        if (!(raw === '#' || raw.startsWith('#') || raw === '')) continue;
        const r = a.getBoundingClientRect();
        if (r.height <= 0 || r.height > 40) continue;
        if (r.top - cardRect.top > 220) continue;
        return a;
    }

    // 4. Aria-based match: link whose aria-label already holds a date
    const dateRe = /\\b(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\\.?\\s+\\d{1,2}|\\b\\d{1,2}:\\d{2}\\b|\\b\\d+\\s*(?:m|h|d|w|y)\\b/i;
    for (const a of candidates) {
        const aria = a.getAttribute('aria-label') || '';
        if (aria && dateRe.test(aria)) return a;
    }

    return null;
};

// ── BATCH EXECUTION ──
const results = [];
const seeMoreLabels = ['see more', 'tan-awa pa', 'tingnan pa', 'mas marami'];

// 1. Collect candidate cards
const rawCandidates = Array.from(document.querySelectorAll(
    'div[role="feed"] > div, div[role="article"], [data-ad-preview], .userContentWrapper'
));

// Filter to unique un-scraped cards
const unScraped = [];
const seenNodes = new Set();

for (const card of rawCandidates) {
    if (!card) continue;
    
    // Resolve to top-level card container in feed or article
    let target = card;
    const feedCard = card.closest ? card.closest('div[role="feed"] > div') : null;
    if (feedCard) {
        target = feedCard;
    } else {
        let art = card.closest ? card.closest('[role="article"], [data-ad-preview], .userContentWrapper') : null;
        if (art) target = art;
    }

    if (seenNodes.has(target)) continue;
    seenNodes.add(target);

    if (target.getAttribute('data-scraped') === 'true' || card.getAttribute('data-scraped') === 'true') continue;

    // Viewport guard: only extract cards that are in or near the current viewport.
    // Off-screen cards will be processed on subsequent scroll cycles once visible.
    try {
        const rect = target.getBoundingClientRect();
        const vh = window.innerHeight || document.documentElement.clientHeight || 1000;
        if (rect.top > (vh + 600)) {
            continue;
        }
    } catch(e) {}
    
    // Tag both candidate and resolved target so neither is processed twice
    target.setAttribute('data-scraped', 'true');
    card.setAttribute('data-scraped', 'true');
    unScraped.push(target);
}

// 2. Expand all 'See more' buttons in un-scraped cards
for (const card of unScraped) {
    try {
        const buttons = card.querySelectorAll('div[role="button"], span[role="button"], a[role="button"], span, div, a');
        for (const btn of buttons) {
            if (btn.children.length <= 1) {
                const txt = (btn.innerText || btn.textContent || '').trim().toLowerCase();
                if (seeMoreLabels.includes(txt)) {
                    try { btn.click(); } catch(e) {}
                }
            }
        }
    } catch(e) {}
}

// 3. Extract data from each card
for (const card of unScraped) {
    try {
        // Bring card into view to ensure React hydrates timestamp and layout metrics
        try { card.scrollIntoView({ behavior: 'instant', block: 'nearest' }); } catch(e) {}
        const data = extractSingleCard(card);
        if (data && data.fullText && data.fullText.length > 2) {
            results.push(data);
        }
    } catch(e) {}
}

return results;
"""


def _expand_see_more(driver, article) -> None:
    """Click every 'See more' expander inside an article so the full
    text (including addresses and phone numbers) is captured."""
    for label in SEE_MORE_LABELS:
        try:
            # Use XPath to find spans/divs whose text matches the label
            buttons = article.find_elements(
                By.XPATH,
                f".//*[self::div or self::span or self::a]"
                f"[contains(translate(text(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ',"
                f"'abcdefghijklmnopqrstuvwxyz'), '{label.lower()}')]"
            )
            for btn in buttons:
                try:
                    driver.execute_script(
                        "arguments[0].scrollIntoView({block:'center'});", btn
                    )
                    time.sleep(0.3)
                    btn.click()
                    time.sleep(0.8)
                except (ElementClickInterceptedException, StaleElementReferenceException):
                    try:
                        driver.execute_script("arguments[0].click();", btn)
                        time.sleep(0.8)
                    except Exception:
                        pass
        except Exception:
            continue


def _extract_text(article) -> str:
    """Pull all visible text from the post container."""
    try:
        # Facebook wraps user-generated content in dir="auto" divs
        text_divs = article.find_elements(By.CSS_SELECTOR, 'div[dir="auto"]')
        parts = []
        for div in text_divs:
            t = div.text.strip()
            if t and len(t) > 2:
                parts.append(t)
        return "\n".join(parts)
    except Exception:
        return article.text.strip() if article.text else ""


def _extract_media_urls(article) -> list[str]:
    """Return direct `src` URLs for images inside the post."""
    urls: list[str] = []
    try:
        imgs = article.find_elements(By.CSS_SELECTOR, "img")
        for img in imgs:
            src = img.get_attribute("src") or ""
            # Filter out tiny icons, emoji, profile pics (usually <100px wide)
            if src and ("scontent" in src or "fbcdn" in src):
                width = img.get_attribute("width")
                natural = img.get_attribute("naturalWidth")
                # Only keep images likely to be content photos
                if width and int(width) < 50:
                    continue
                if natural and int(natural) < 80:
                    continue
                urls.append(src)
    except Exception:
        pass
    return list(dict.fromkeys(urls))  # deduplicate


def _clean_facebook_url(url: str) -> str:
    """Clean tracking params from Facebook URL while preserving essential post identifiers."""
    if not url:
        return ""
    if url.startswith("/"):
        url = "https://www.facebook.com" + url

    # Unwrap Facebook redirects
    if "facebook.com/l.php?" in url or "facebook.com/flx/warn/?" in url:
        parsed = urlparse(url)
        qs = parse_qs(parsed.query)
        if "u" in qs:
            url = qs["u"][0]

    try:
        parsed = urlparse(url)
        path_lower = parsed.path.lower()

        # Check if URL relies on query parameters for post/media identification
        needs_query_params = any(k in path_lower for k in [
            "permalink.php", "story.php", "photo.php", "video.php", 
            "photo", "watch", "media/set", "profile.php"
        ])

        query_params = parse_qs(parsed.query)
        essential_keys = ["story_fbid", "id", "fbid", "v", "set", "post_id", "theater"]
        kept = {k: v[0] for k, v in query_params.items() if k in essential_keys and v}

        if needs_query_params and kept:
            clean_query = urlencode(kept)
            clean_path = parsed.path.rstrip("/") if parsed.path != "/" else "/"
            return urlunparse((parsed.scheme, parsed.netloc, clean_path, "", clean_query, ""))
        else:
            clean_path = parsed.path.rstrip("/")
            return urlunparse((parsed.scheme, parsed.netloc, clean_path, "", "", ""))
    except Exception:
        return url.split("?")[0]


def _is_post_url(href: str) -> bool:
    """Check if a URL points directly to a post, video, reel, photo, or share permalink."""
    if not href:
        return False

    try:
        parsed = urlparse(href)
        path = parsed.path.lower().rstrip("/")
    except Exception:
        return False

    # Exclude non-post and generic UI links by PATH (never by query params)
    bad_paths = [
        "/hashtag", "/friends", "/login", "/recover", "/help", 
        "/settings", "/messages", "/marketplace", "/gaming", 
        "/events", "/policies", "/sharer", "/home.php",
        "/search/posts", "/search/top", "/search", "/notifications"
    ]
    if any(path == bp or path.startswith(bp + "/") for bp in bad_paths):
        return False

    # Check root paths that require query parameters
    if path in ["", "/", "/watch", "/reels", "/photos", "/videos", "/groups", "/stories", "/photo"]:
        if not parsed.query or not any(k in parsed.query for k in ["v=", "fbid=", "story_fbid=", "set="]):
            return False

    post_patterns = [
        r"/posts/",
        r"/permalink/",
        r"permalink\.php",
        r"story\.php",
        r"story_fbid=",
        r"/photos?/",
        r"/photo\.php",
        r"/photo\?",
        r"[?&]fbid=\d+",
        r"/videos?/",
        r"/video\.php",
        r"/watch/?\?v=",
        r"/reel/",
        r"/reels/[\w]",
        r"/share/[pvr]/",
        r"/share/[A-Za-z0-9_-]{5,}",
        r"/multi_permalinks/",
        r"/groups/[^/]+/posts/\d+",
        r"/groups/[^/]+/permalink/\d+",
        r"pfbid[A-Za-z0-9]+",
    ]
    return any(re.search(pat, href, re.IGNORECASE) for pat in post_patterns)


def _is_valid_author(name: str) -> bool:
    """Validate that the string is a real poster/page name, not a hashtag or UI label."""
    if not name or len(name) < 2 or len(name) > 90:
        return False
    t = name.strip()
    if t.startswith("#") or t.startswith("http") or t.startswith("@"):
        return False

    lowered = t.lower()
    invalid_keywords = [
        "see more", "see less", "like", "comment", "share", "follow", "facebook",
        "public", "joined", "replies", "reaction", "suggested", "sponsored",
        "tan-awa", "tingnan", "photos", "videos", "groups", "reels",
        "top fans", "following", "admin", "moderator", "unknown", "shared with",
        "news feed", "write a comment", "log in", "sign up", "notifications"
    ]
    if any(bad == lowered or lowered.startswith(bad) for bad in invalid_keywords):
        return False

    return True


def _clean_timestamp_text(text: str) -> str:
    """Strip common Facebook UI noise (privacy indicators, bullet separators, icon fonts) from timestamp text."""
    if not text:
        return ""
    s = re.sub(r'[\r\n\t\u00a0]+', ' ', text).strip()
    # Strip emojis, zero-width spaces, combining graphemes (0x034F), and Unicode private-use area characters
    s = re.sub(r'[\ue000-\uf8ff\ufffd\U00010000-\U0010ffff\u200b-\u200f\u202a-\u202e\ufeff\u0300-\u036f]', '', s)
    # Reject photo description alt texts e.g. "May be an image of..."
    if re.search(r'^(?:may be an image|photo description|no photo description)', s, re.I):
        return ""
    # Reject video player progress bar timestamps e.g. "0:00 / 0:30"
    if re.search(r'\b\d+:\d+\s*/\s*\d+:\d+\b', s):
        return ""
    # Strip privacy indicators and trailing bullets / separators
    s = re.sub(r'\s*[·•|]\s*(?:public|shared with[^\n\r]*|friends|custom|only me|kaniadto|tanan|publiko|mga higala)?\s*$', '', s, flags=re.I)
    s = re.sub(r'^\s*[·•|]\s*', '', s)
    s = re.sub(r'\s+(?:public|shared with\s+public|shared with:?\s*public)$', '', s, flags=re.I)
    s = re.sub(r'\s*[·•|]\s*$', '', s)
    return s.strip()


def _is_valid_timestamp(text: str) -> bool:
    """Validate whether text is a plausible date/time string and NOT a hashtag or UI button."""
    if not text or len(text) > 90:
        return False
    cleaned = _clean_timestamp_text(text)
    if not cleaned or cleaned.startswith("#") or cleaned.startswith("http") or cleaned.startswith("@"):
        return False
    if re.search(r'\b\d+:\d+\s*/\s*\d+:\d+\b', cleaned):
        return False

    lowered = cleaned.lower()
    if lowered.startswith("may be an image") or lowered.startswith("no photo"):
        return False

    # Reject only exact UI labels
    exact_bad = [
        "see more", "see less", "like", "comment", "share", "follow", "facebook",
        "public", "joined", "replies", "reaction", "author", "suggested",
        "sponsored", "view more", "hide", "tan-awa", "tingnan", "unknown",
        "write a comment", "log in", "sign up"
    ]
    if any(lowered == bad or lowered.startswith(bad + " ") for bad in exact_bad):
        return False

    # Check for date / time patterns (including high-precision seconds, minutes, hours, days)
    date_patterns = [
        r'\b(?:just now|now|yesterday|today|kahapon|karon|kagahapon|bag-o lang|ngayon)\b',
        r'\b\d+\s*(?:s|sec|secs|second|seconds|segundo)\b',
        r'\b\d+\s*(?:m|min|mins|minute|minutes|minuto)\b',
        r'\b\d+\s*(?:h|hr|hrs|hour|hours|oras)\b',
        r'\b\d+\s*(?:d|day|days|adlaw|araw)\b',
        r'\b\d+\s*(?:w|wk|wks|week|weeks|semana|linggo)\b',
        r'\b\d+\s*(?:y|yr|yrs|year|years)\b',
        r'\b\d+\s*(?:ka\s*)?(?:segundo|minuto|oras|adlaw|araw|semana|linggo)(?:\s+(?:ang\s+)?(?:nakalipas|milabay|nakalabay))?\b',
        rf'(?:(?:Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)[a-z]*,?\s+)?\b({_MONTH_NAMES_RE})\.?\s+([12]\d|3[01]|0?[1-9])(?:st|nd|rd|th)?(?:,?\s+\d{{4}})?(?:\s+(?:at|sa|nang|ng|@)\s+\d{{1,2}}:\d{{2}}(?::\d{{2}})?\s*(?:am|pm)?)?\b',
        rf'\b([12]\d|3[01]|0?[1-9])(?:st|nd|rd|th)?\s+({_MONTH_NAMES_RE})\.?(?:,?\s+\d{{4}})?(?:\s+(?:at|sa|nang|ng|@)\s+\d{{1,2}}:\d{{2}}(?::\d{{2}})?\s*(?:am|pm)?)?\b',
        rf'\b({_MONTH_NAMES_RE})\.?\s+\d{{4}}\b',
        r'\b\d{1,2}:\d{2}(?::\d{2})?\s*(?:am|pm|AM|PM)?\b',
        r'\b\d{1,2}/\d{1,2}/\d{2,4}\b',
        r'\b\d{4}-\d{2}-\d{2}\b',
        r'^\d{4}$',
    ]
    return any(re.search(pat, cleaned, re.IGNORECASE) for pat in date_patterns)


def _is_relative_timestamp(ts: str) -> bool:
    """Check if a timestamp string is a relative time (e.g. '5s', '29m', '4h', '30m ago', 'yesterday')
    rather than an absolute date."""
    if not ts:
        return False
    cleaned = _clean_timestamp_text(ts)
    if not cleaned:
        return False
    lowered = cleaned.lower()
    relative_patterns = [
        r'\b\d+\s*(?:s|sec|secs|second|seconds|segundo)\b',
        r'\b\d+\s*(?:m|min|mins|minute|minutes|minuto)\b',
        r'\b\d+\s*(?:h|hr|hrs|hour|hours|oras)\b',
        r'\b\d+\s*(?:d|day|days|adlaw|araw)\b',
        r'\b\d+\s*(?:w|wk|wks|week|weeks|semana|linggo)\b',
        r'\b\d+\s*(?:y|yr|yrs|year|years)\b',
        r'\b\d+\s*(?:ka\s*)?(?:segundo|minuto|oras|adlaw|araw|semana|linggo)(?:\s+(?:ang\s+)?(?:nakalipas|milabay|nakalabay))?\b',
        r'\b(?:just now|now|yesterday|today|kahapon|karon|kagahapon|bag-o lang|ngayon)\b',
    ]
    return any(re.search(pat, lowered) for pat in relative_patterns)


def _parse_relative_timedelta(ts: str, reference_now: datetime | None = None) -> tuple[timedelta, int, float, float] | None:
    """Parse relative time string into (timedelta, total_seconds, total_minutes, total_hours).
    Provides sub-day precision (seconds/minutes/hours) for active flood validation.
    """
    if not ts:
        return None
    cleaned = _clean_timestamp_text(ts)
    if not cleaned:
        return None
    now = reference_now or datetime.now()

    # 1. 'just now' / 'now' / 'bag-o lang' / 'ngayon'
    if re.search(r'\b(?:just now|now|bag-o lang|ngayon)\b', cleaned, re.I):
        return timedelta(seconds=0), 0, 0.0, 0.0

    # 2. English numeric relative units: seconds, minutes, hours, days, weeks, years
    m = re.search(
        r'\b(\d+)\s*(s|sec|secs|second|seconds|m|min|mins|minute|minutes|h|hr|hrs|hour|hours|d|day|days|w|wk|wks|week|weeks|y|yr|yrs|year|years)(?:\s+ago)?\b',
        cleaned,
        re.I
    )
    if m:
        val = int(m.group(1))
        unit = m.group(2).lower()
        if unit in ("s", "sec", "secs", "second", "seconds"):
            delta = timedelta(seconds=val)
        elif unit in ("m", "min", "mins", "minute", "minutes"):
            delta = timedelta(minutes=val)
        elif unit in ("h", "hr", "hrs", "hour", "hours"):
            delta = timedelta(hours=val)
        elif unit in ("d", "day", "days"):
            delta = timedelta(days=val)
        elif unit in ("w", "wk", "wks", "week", "weeks"):
            delta = timedelta(weeks=val)
        elif unit in ("y", "yr", "yrs", "year", "years"):
            delta = timedelta(days=val * 365)
        else:
            delta = timedelta(0)

        tot_sec = int(delta.total_seconds())
        tot_min = round(tot_sec / 60.0, 2)
        tot_hr = round(tot_sec / 3600.0, 2)
        return delta, tot_sec, tot_min, tot_hr

    # 3. Filipino / Bisaya numeric relative units
    m_ph = re.search(
        r'\b(\d+)\s*(?:ka\s*)?(segundo|minuto|oras|adlaw|araw|semana|linggo)(?:\s+(?:ang\s+)?(?:nakalipas|milabay|nakalabay))?\b',
        cleaned,
        re.I
    )
    if m_ph:
        val = int(m_ph.group(1))
        unit = m_ph.group(2).lower()
        if unit in ("segundo",):
            delta = timedelta(seconds=val)
        elif unit in ("minuto",):
            delta = timedelta(minutes=val)
        elif unit in ("oras",):
            delta = timedelta(hours=val)
        elif unit in ("adlaw", "araw"):
            delta = timedelta(days=val)
        elif unit in ("semana", "linggo"):
            delta = timedelta(weeks=val)
        else:
            delta = timedelta(0)

        tot_sec = int(delta.total_seconds())
        tot_min = round(tot_sec / 60.0, 2)
        tot_hr = round(tot_sec / 3600.0, 2)
        return delta, tot_sec, tot_min, tot_hr

    # 4. Yesterday / Today with optional time
    if re.search(r'\b(?:yesterday|kahapon|kagahapon)\b', cleaned, re.I):
        tm = re.search(r'(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)?', cleaned, re.I)
        target_day = (now - timedelta(days=1)).date()
        if tm:
            h = int(tm.group(1))
            mn = int(tm.group(2))
            s_val = int(tm.group(3)) if tm.group(3) else 0
            ampm = (tm.group(4) or "").upper()
            if ampm == "PM" and h < 12: h += 12
            elif ampm == "AM" and h == 12: h = 0
            target_dt = datetime.combine(target_day, datetime.min.time()).replace(hour=h, minute=mn, second=s_val)
        else:
            target_dt = datetime.combine(target_day, datetime.min.time())
        delta = max(timedelta(0), now - target_dt)
        tot_sec = int(delta.total_seconds())
        return delta, tot_sec, round(tot_sec / 60.0, 2), round(tot_sec / 3600.0, 2)

    if re.search(r'\b(?:today|karon)\b', cleaned, re.I):
        tm = re.search(r'(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)?', cleaned, re.I)
        target_day = now.date()
        if tm:
            h = int(tm.group(1))
            mn = int(tm.group(2))
            s_val = int(tm.group(3)) if tm.group(3) else 0
            ampm = (tm.group(4) or "").upper()
            if ampm == "PM" and h < 12: h += 12
            elif ampm == "AM" and h == 12: h = 0
            target_dt = datetime.combine(target_day, datetime.min.time()).replace(hour=h, minute=mn, second=s_val)
        else:
            target_dt = datetime.combine(target_day, datetime.min.time())
        delta = max(timedelta(0), now - target_dt)
        tot_sec = int(delta.total_seconds())
        return delta, tot_sec, round(tot_sec / 60.0, 2), round(tot_sec / 3600.0, 2)

    return None


def _normalize_timestamp_info(
    raw_ts: str,
    reference_now: datetime | None = None,
    default_year: int | None = None
) -> dict:
    """Normalize raw Facebook timestamp into high-precision sub-day metrics and ISO datetime.
    Returns:
        dict with keys:
            raw_timestamp: original captured string
            timestamp: formatted display string (e.g. 'October 21, 2026 at 3:19 PM')
            iso_timestamp: ISO 8601 string (e.g. '2026-10-21T15:19:00')
            relative_time: relative string (e.g. '29m', '45s', '2h')
            relative_seconds: integer seconds (sub-day precision)
            relative_minutes: float minutes
            relative_hours: float hours
            parsed_dt: datetime object or None
    """
    if not raw_ts:
        return {
            "raw_timestamp": "",
            "timestamp": "",
            "iso_timestamp": "",
            "relative_time": "",
            "relative_seconds": None,
            "relative_minutes": None,
            "relative_hours": None,
            "parsed_dt": None,
        }

    cleaned = _clean_timestamp_text(raw_ts)
    now = reference_now or datetime.now()
    target_year = default_year or FILTER_YEAR or now.year

    parsed_dt: datetime | None = None
    rel_seconds: int | None = None
    rel_minutes: float | None = None
    rel_hours: float | None = None
    rel_time: str = ""

    # Check relative parsing first
    rel_parsed = _parse_relative_timedelta(cleaned, reference_now=now)
    if rel_parsed:
        delta, rel_seconds, rel_minutes, rel_hours = rel_parsed
        parsed_dt = now - delta
        rel_time = cleaned
    else:
        # Check absolute datetime parsing
        parsed_dt = _parse_timestamp_to_datetime(cleaned, default_year=target_year)
        if parsed_dt:
            # If parsed date is in the past, calculate relative delta from reference time
            if now >= parsed_dt:
                delta = now - parsed_dt
                rel_seconds = int(delta.total_seconds())
                rel_minutes = round(rel_seconds / 60.0, 2)
                rel_hours = round(rel_seconds / 3600.0, 2)
                if rel_seconds < 60:
                    rel_time = f"{rel_seconds}s ago"
                elif rel_seconds < 3600:
                    rel_time = f"{int(rel_minutes)}m ago"
                elif rel_seconds < 86400:
                    rel_time = f"{int(rel_hours)}h ago"
                else:
                    rel_time = f"{delta.days}d ago"

    iso_timestamp = parsed_dt.isoformat() if parsed_dt else ""
    if parsed_dt:
        if parsed_dt.second > 0:
            formatted_display = parsed_dt.strftime("%B %d, %Y at %I:%M:%S %p")
        elif parsed_dt.hour > 0 or parsed_dt.minute > 0:
            formatted_display = parsed_dt.strftime("%B %d, %Y at %I:%M %p")
        else:
            formatted_display = parsed_dt.strftime("%B %d, %Y")
    else:
        formatted_display = cleaned

    return {
        "raw_timestamp": raw_ts,
        "timestamp": formatted_display,
        "iso_timestamp": iso_timestamp,
        "relative_time": rel_time,
        "relative_seconds": rel_seconds,
        "relative_minutes": rel_minutes,
        "relative_hours": rel_hours,
        "parsed_dt": parsed_dt,
    }


def _resolve_relative_timestamp(ts: str, default_year: int | None = None) -> str:
    """Convert a relative or raw timestamp into an absolute formatted date/time string.
    Backwards-compatible wrapper around _normalize_timestamp_info.
    """
    if not ts:
        return ""
    info = _normalize_timestamp_info(ts, default_year=default_year)
    return info["timestamp"] or ts


def _extract_timestamp_from_text(text: str) -> str:
    """Extract explicit date mentions from post body text as a fallback."""
    if not text:
        return ""
    patterns = [
        r'\b(?:January|February|March|April|May|June|July|August|September|October|November|December|Enero|Pebrero|Marso|Abril|Mayo|Hunyo|Hulyo|Agosto|Setyembre|Oktubre|Nobiyembre|Nobyembre|Disyembre)\s+\d{1,2}(?:st|nd|rd|th)?,?\s+\d{4}\b',
        r'\b\d{1,2}(?:st|nd|rd|th)?\s+(?:January|February|March|April|May|June|July|August|September|October|November|December|Enero|Pebrero|Marso|Abril|Mayo|Hunyo|Hulyo|Agosto|Setyembre|Oktubre|Nobiyembre|Nobyembre|Disyembre),?\s+\d{4}\b',
        r'\b(?:January|February|March|April|May|June|July|August|September|October|November|December|Enero|Pebrero|Marso|Abril|Mayo|Hunyo|Hulyo|Agosto|Setyembre|Oktubre|Nobiyembre|Nobyembre|Disyembre)\s+\d{4}\b',
        r'\b\d{4}-\d{2}-\d{2}\b',
    ]
    for p in patterns:
        m = re.search(p, text, re.IGNORECASE)
        if m:
            return m.group(0).strip()
    return ""


def _clean_author_name(name: str) -> str:
    """Clean author name string by removing trailing badges, locations, and actions."""
    if not name:
        return "Unknown"
    t = name.strip()
    lines = [l.strip() for l in re.split(r'[\r\n]+', t) if l.strip()]
    if not lines:
        return "Unknown"
    first = lines[0]
    first = re.sub(r'\s*[·•|]\s*(?:follow|following|active|joined|top fan|admin|moderator|author|public)?\s*$', '', first, flags=re.I)
    first = re.sub(r'\s+(?:is with|is in|is at|nasa|kasama si|kasama ang)\s+.*$', '', first, flags=re.I)
    first = re.sub(r'\s*[·•|]\s*\d+\s*(?:h|hr|hrs|m|min|mins|d|w|y)?\s*$', '', first, flags=re.I)
    first = re.sub(r'\s*[·•|]\s*$', '', first)
    return first.strip() or "Unknown"


def _extract_author(driver, article) -> str:
    """Best-effort extraction of the posting author's name, avoiding hashtags."""
    # 1. Look for headings (h2, h3, h4, h5, [role="heading"]) in element and ancestors
    try:
        headings = article.find_elements(By.CSS_SELECTOR, 'h2, h3, h4, h5, [role="heading"]')
        if not headings:
            headings = article.find_elements(By.XPATH, "./ancestor::div[position() <= 4]//h2 | ./ancestor::div[position() <= 4]//h3 | ./ancestor::div[position() <= 4]//h4 | ./ancestor::div[position() <= 4]//*[@role='heading']")

        for heading in headings:
            links = heading.find_elements(By.CSS_SELECTOR, "a, strong, span")
            for link in links:
                txt = link.text.strip() or (link.get_attribute("innerText") or "").strip()
                cleaned = _clean_author_name(txt)
                if _is_valid_author(cleaned):
                    return cleaned
            txt = heading.text.strip() or (heading.get_attribute("innerText") or "").strip()
            cleaned = _clean_author_name(txt)
            if _is_valid_author(cleaned):
                return cleaned
    except Exception:
        pass

    # 2. Look for profile link / strong tag in the header area
    try:
        selectors = [
            '[data-ad-rendering-role="profile_name"]',
            'strong a',
            'a strong',
            'span > strong',
            'a[role="link"] > span > strong',
            'a[role="link"][href]:not([href*="/posts/"]):not([href*="/photo"]):not([href*="/video"]):not([href*="/hashtag/"])',
        ]
        for sel in selectors:
            links = article.find_elements(By.CSS_SELECTOR, sel)
            for link in links:
                txt = link.text.strip() or (link.get_attribute("innerText") or "").strip()
                cleaned = _clean_author_name(txt)
                if _is_valid_author(cleaned):
                    return cleaned
                aria = (link.get_attribute("aria-label") or "").strip()
                cleaned_aria = _clean_author_name(aria)
                if _is_valid_author(cleaned_aria):
                    return cleaned_aria
    except Exception:
        pass

    # 3. Look for Profile picture alt text / aria-label
    try:
        imgs = article.find_elements(By.CSS_SELECTOR, 'img[alt*="profile" i], img[alt*="profile picture" i]')
        for img in imgs:
            alt = (img.get_attribute("alt") or "").strip()
            cleaned = re.sub(r"(?:'s\s+profile\s+picture|profile\s+picture\s+of\s+|profile\s+of\s+)", "", alt, flags=re.I).strip()
            cleaned = _clean_author_name(cleaned)
            if _is_valid_author(cleaned):
                return cleaned
    except Exception:
        pass

    return "Unknown"


class TimestampUrlResult(tuple):
    """Backwards-compatible tuple containing (timestamp, post_url), with extra high-precision timestamp metadata attributes."""
    def __new__(cls, timestamp: str, post_url: str, raw_timestamp: str = "", ts_info: dict | None = None):
        return super().__new__(cls, (timestamp, post_url))

    def __init__(self, timestamp: str, post_url: str, raw_timestamp: str = "", ts_info: dict | None = None):
        self.timestamp = timestamp
        self.post_url = post_url
        self.raw_timestamp = raw_timestamp
        self.ts_info = ts_info or {}


def _extract_timestamp_and_url(driver, article, full_text: str = "") -> TimestampUrlResult:
    """Extract publish timestamp and direct post permalink.
    Implements:
      1. Target Element Identification: Header timestamp container (<a> / <span> under headings).
      2. Attribute Extraction (Primary): Absolute timestamp from aria-label or title (e.g. 'Wednesday, October 21, 2026 at 3:19 PM').
      3. Fallback Text Parsing (Secondary): Visible inner text ('29m', '45s', '3h', 'Just now') mapped to relative timedelta.
      4. Hover Trigger Fallback: ActionChains hover + JS mouse events to reveal dynamic tooltip.
      5. Output Formatting: High-precision sub-day metrics (seconds/minutes/hours) + ISO timestamp.
    """
    raw_timestamp = ""
    post_url = ""
    best_candidate_el = None

    def _is_absolute_string(s: str) -> bool:
        if not s:
            return False
        return bool(re.search(rf'\b({_MONTH_NAMES_RE})\b|\b\d{{4}}-\d{{2}}-\d{{2}}\b', s, re.I))

    # Strategy 1: Target header links/spans specifically for timestamp & direct post URL
    try:
        header_links = article.find_elements(
            By.CSS_SELECTOR,
            'h2 ~ div a, h3 ~ div a, h4 ~ div a, [role="heading"] ~ div a, [data-ad-rendering-role="profile_name"] ~ div a, a[role="link"], span[role="link"], span[tabindex], div[role="button"], a'
        )
        for link in header_links:
            href = link.get_attribute("href") or ""
            if href and "/hashtag/" in href:
                continue

            if href and _is_post_url(href) and not post_url:
                post_url = _clean_facebook_url(href)

            aria = _clean_timestamp_text(link.get_attribute("aria-label") or "")
            title = _clean_timestamp_text(link.get_attribute("title") or "")
            inner = _clean_timestamp_text(link.get_attribute("innerText") or link.text or "")
            data_utime = link.get_attribute("data-utime")

            # Check if this link looks like a timestamp container
            if not best_candidate_el and (_is_valid_timestamp(aria) or _is_valid_timestamp(title) or _is_valid_timestamp(inner)):
                best_candidate_el = link

            # Primary: Attribute extraction (aria-label or title)
            if aria and _is_valid_timestamp(aria) and _is_absolute_string(aria):
                raw_timestamp = aria
                break
            if title and _is_valid_timestamp(title) and _is_absolute_string(title):
                raw_timestamp = title
                break
            if data_utime and data_utime.isdigit():
                try:
                    raw_timestamp = datetime.fromtimestamp(int(data_utime), tz=timezone.utc).strftime("%Y-%m-%d %H:%M:%S")
                    break
                except Exception:
                    pass

            if not raw_timestamp:
                if _is_valid_timestamp(aria):
                    raw_timestamp = aria
                elif _is_valid_timestamp(title):
                    raw_timestamp = title
                elif _is_valid_timestamp(inner):
                    raw_timestamp = inner
    except Exception:
        pass

    # Strategy 2: Hover Trigger Fallback (Requirement 4)
    if (not raw_timestamp or _is_relative_timestamp(raw_timestamp)) and best_candidate_el:
        try:
            # 1. ActionChains hover
            ActionChains(driver).move_to_element(best_candidate_el).perform()
            # 2. Synthetic JS mouse event dispatch
            driver.execute_script("""
                const el = arguments[0];
                if (el) {
                    const opts = { bubbles: true, cancelable: true, view: window, composed: true };
                    el.dispatchEvent(new MouseEvent('mouseenter', opts));
                    el.dispatchEvent(new MouseEvent('mouseover', opts));
                    el.dispatchEvent(new MouseEvent('mousemove', opts));
                }
            """, best_candidate_el)
            time.sleep(0.5)

            # Re-check aria-label and title post-hover
            post_aria = _clean_timestamp_text(best_candidate_el.get_attribute("aria-label") or "")
            post_title = _clean_timestamp_text(best_candidate_el.get_attribute("title") or "")
            if post_aria and _is_valid_timestamp(post_aria) and _is_absolute_string(post_aria):
                raw_timestamp = post_aria
            elif post_title and _is_valid_timestamp(post_title) and _is_absolute_string(post_title):
                raw_timestamp = post_title
            else:
                # Check rendered tooltip
                tooltips = driver.find_elements(By.CSS_SELECTOR, 'div[role="tooltip"], [role="tooltip"], div[data-visualcompletion="tooltip-target"]')
                for tt in tooltips:
                    t = _clean_timestamp_text(tt.text or "")
                    if _is_valid_timestamp(t) and _is_absolute_string(t):
                        raw_timestamp = t
                        break
        except Exception:
            pass

    # Strategy 3: Media links fallback for post_url
    if not post_url:
        try:
            media_links = article.find_elements(
                By.CSS_SELECTOR,
                'a[href*="/photo"], a[href*="/photos/"], a[href*="/videos/"], a[href*="/video.php"], a[href*="/watch"], a[href*="/reel/"], a[href*="/reels/"], a[href*="fbid="], a[href*="story_fbid="]'
            )
            for link in media_links:
                href = link.get_attribute("href") or ""
                if href and _is_post_url(href):
                    post_url = _clean_facebook_url(href)
                    break
        except Exception:
            pass

    # Strategy 4: Fallback to <abbr> and <time> tags
    if not raw_timestamp:
        try:
            time_tags = article.find_elements(By.CSS_SELECTOR, "abbr, time")
            for el in time_tags:
                ts = el.get_attribute("data-utime") or el.get_attribute("datetime") or el.get_attribute("title") or el.text
                if ts:
                    cleaned = _clean_timestamp_text(ts)
                    if _is_valid_timestamp(cleaned):
                        raw_timestamp = cleaned
                        break
        except Exception:
            pass

    # Strategy 5: Fallback date extraction from post body text
    if not raw_timestamp and full_text:
        raw_timestamp = _extract_timestamp_from_text(full_text)

    # Normalize high-precision output
    ts_info = _normalize_timestamp_info(raw_timestamp, default_year=FILTER_YEAR)
    timestamp = ts_info["timestamp"]

    return TimestampUrlResult(timestamp, post_url, raw_timestamp=raw_timestamp, ts_info=ts_info)


def _expand_all_visible_see_more(driver) -> int:
    """Find all 'See more' expanders on visible posts and click them with a small pause
    to let React update the DOM with full un-truncated text."""
    js_click_see_more = """
    const seeMoreLabels = ['see more', 'tan-awa pa', 'tingnan pa', 'mas marami', 'see more…', 'tingnan ang higit pa'];
    let count = 0;
    const buttons = document.querySelectorAll('div[role="button"], span[role="button"], a[role="button"], span, div, a');
    for (const btn of buttons) {
        if (btn.children.length <= 1) {
            const txt = (btn.innerText || btn.textContent || '').trim().toLowerCase();
            if (seeMoreLabels.includes(txt) && btn.getAttribute('data-expanded') !== 'true') {
                btn.setAttribute('data-expanded', 'true');
                try {
                    btn.click();
                    count++;
                } catch(e) {}
            }
        }
    }
    return count;
    """
    try:
        count = driver.execute_script(js_click_see_more) or 0
        if count > 0:
            time.sleep(0.8)  # Give React time to expand the full text in DOM
        return count
    except Exception:
        return 0


def _dismiss_author_hovercards(driver) -> None:
    """Remove any author profile hovercard popups that may obstruct the page,
    and ensure harmful style overrides are cleaned up."""
    js_cleanup = """
    const oldStyle = document.getElementById('fb-no-author-hover');
    if (oldStyle) oldStyle.remove();

    // Remove any open profile preview hovercards (but keep date tooltips)
    document.querySelectorAll('div[role="dialog"]').forEach(d => {
        const txt = (d.innerText || '');
        if ((txt.includes('Followers') || txt.includes('Send message') || txt.includes('Message') || txt.includes('Broadcasting')) 
            && !d.querySelector('video')) {
            d.remove();
        }
    });
    """
    try:
        driver.execute_script(js_cleanup)
    except Exception:
        pass


def _hover_and_cache_timestamps(driver) -> int:
    """Locate the timestamp element in the header of visible unscraped post cards,
    hover over it using ActionChains to trigger Facebook's date tooltip,
    and cache the resolved tooltip text in `data-hovered-ts` on both the link and card.
    """
    js_tag_targets = """
    function findTimestampElement(card) {
        if (!card) return null;
        const cardRect = card.getBoundingClientRect();

        let authorText = '';
        const heading = card.querySelector('h2, h3, h4, [role="heading"], [data-ad-rendering-role="profile_name"], strong');
        if (heading) {
            authorText = (heading.innerText || heading.textContent || '').trim().toLowerCase();
        }

        const links = Array.from(card.querySelectorAll('a[href], a[role="link"], span[role="link"]'));

        // Strategy 1: Post permalink link in header (within the first 260px of the card)
        for (const a of links) {
            const href = a.href || a.getAttribute('href') || '';
            if (!href) continue;
            if (href.includes('/hashtag/') || href.includes('/user/') || href.includes('/friends/') || href.includes('/messages/')) continue;

            const text = (a.innerText || a.textContent || '').trim().toLowerCase();
            if (authorText && (text === authorText || authorText.startsWith(text))) continue;
            if (a.getAttribute('data-ad-rendering-role') === 'profile_name') continue;
            if (a.querySelector('img, svg image')) continue;

            const isPost = /\\/(?:posts|permalink|photo|photos|videos|video|watch|reel|reels|share)\\b|story_fbid=|fbid=|pfbid|pcb\\./i.test(href);
            if (isPost) {
                const r = a.getBoundingClientRect();
                const relY = r.top - cardRect.top;
                if (relY >= -15 && relY <= 260 && r.height > 0 && r.height < 65 && r.width > 5 && r.width < 450) {
                    return a;
                }
            }
        }

        // Strategy 2: Link with date aria-label or aria-labelledby in header
        for (const a of links) {
            if (a.querySelector('img, svg image')) continue;
            const aria = a.getAttribute('aria-label') || '';
            if (aria && (/\\b\\d{1,2}:\\d{2}\\b/.test(aria) || /\\b(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|enero|pebrero|marso|abril|mayo|hunyo|hulyo|agosto|setyembre|oktubre|nobiyembre|nobyembre|disyembre)\\b/i.test(aria))) {
                return a;
            }
            if (a.hasAttribute('aria-labelledby')) {
                const r = a.getBoundingClientRect();
                const relY = r.top - cardRect.top;
                if (relY >= -15 && relY <= 260 && r.height > 0 && r.height < 65 && r.width > 5) {
                    return a;
                }
            }
        }

        // Strategy 3: Subheader links directly under heading
        const subheaderLinks = Array.from(card.querySelectorAll('h2 ~ div a, h3 ~ div a, h4 ~ div a, [role="heading"] ~ div a, [data-ad-rendering-role="profile_name"] ~ div a'));
        for (const a of subheaderLinks) {
            if (a.querySelector('img, svg image')) continue;
            const text = (a.innerText || a.textContent || '').trim().toLowerCase();
            if (authorText && text === authorText) continue;
            const href = a.href || a.getAttribute('href') || '';
            if (href.includes('/hashtag/')) continue;
            const r = a.getBoundingClientRect();
            if (r.height > 0 && r.height < 65 && r.width > 5) {
                return a;
            }
        }

        return null;
    }

    const rawCandidates = Array.from(document.querySelectorAll(
        'div[role="feed"] > div, div[role="article"], [data-ad-preview], .userContentWrapper'
    ));
    const seen = new Set();
    let tagIdx = 0;
    const pairs = [];

    for (const c of rawCandidates) {
        if (!c) continue;
        let card = c.closest('div[role="feed"] > div') || c.closest('[role="article"], [data-ad-preview], .userContentWrapper') || c;
        if (seen.has(card) || card.getAttribute('data-scraped') === 'true') continue;
        seen.add(card);

        const rect = card.getBoundingClientRect();
        const vh = window.innerHeight || document.documentElement.clientHeight || 1000;
        if (rect.top > (vh + 600) || rect.bottom < -200) continue;

        if (card.getAttribute('data-hovered-ts')) continue;

        const tsEl = findTimestampElement(card);
        if (tsEl) {
            const tsUid = 'ts_target_' + (++tagIdx) + '_' + Date.now();
            const cardUid = 'card_target_' + tagIdx + '_' + Date.now();
            tsEl.setAttribute('data-ts-uid', tsUid);
            card.setAttribute('data-card-uid', cardUid);
            pairs.push({ tsUid: tsUid, cardUid: cardUid });
        }
    }
    return pairs;
    """
    try:
        pairs = driver.execute_script(js_tag_targets) or []
        cached_count = 0
        for pair in pairs:
            ts_uid = pair.get("tsUid")
            card_uid = pair.get("cardUid")
            if not ts_uid or not card_uid:
                continue

            try:
                el = driver.find_element(By.CSS_SELECTOR, f'[data-ts-uid="{ts_uid}"]')
                driver.execute_script("arguments[0].scrollIntoView({behavior: 'instant', block: 'center'});", el)
                time.sleep(0.12)

                # Clear stale tooltips before hovering
                driver.execute_script("""
                    document.querySelectorAll('div[role="tooltip"], [role="tooltip"]').forEach(t => t.remove());
                """)

                # Move directly to the timestamp element
                ActionChains(driver).move_to_element(el).perform()
                driver.execute_script("""
                    const el = arguments[0];
                    const opts = { bubbles: true, cancelable: true, view: window, composed: true };
                    el.dispatchEvent(new MouseEvent('mouseenter', opts));
                    el.dispatchEvent(new MouseEvent('mouseover', opts));
                    el.dispatchEvent(new MouseEvent('mousemove', opts));
                """, el)

                # Wait in Python for React to asynchronously render the tooltip
                time.sleep(0.4)

                # Capture tooltip text and attach it to both element and card
                captured = driver.execute_script("""
                    const el = arguments[0];
                    const cardUid = arguments[1];
                    const card = document.querySelector(`[data-card-uid="${cardUid}"]`);

                    let found = '';
                    const tooltips = Array.from(document.querySelectorAll('div[role="tooltip"], [role="tooltip"], div[data-visualcompletion="tooltip-target"]'));
                    for (const tt of tooltips) {
                        const txt = (tt.innerText || tt.textContent || '').trim();
                        if (txt && /\\d/.test(txt) && !txt.includes('Followers') && !txt.includes('Send message') && !txt.includes('Message')) {
                            found = txt;
                            break;
                        }
                    }

                    if (!found) {
                        const aria = (el.getAttribute('aria-label') || '').trim();
                        if (aria && /\\d/.test(aria) && !aria.includes('profile')) {
                            found = aria;
                        }
                    }

                    if (found) {
                        el.setAttribute('data-hovered-ts', found);
                        if (card) card.setAttribute('data-hovered-ts', found);
                    }

                    const opts = { bubbles: true, cancelable: true, view: window, composed: true };
                    el.dispatchEvent(new MouseEvent('mouseleave', opts));
                    el.dispatchEvent(new MouseEvent('mouseout', opts));
                    return found;
                """, el, card_uid)

                if captured:
                    cached_count += 1
                    log.info(f"    [Hover Timestamp] Captured: '{captured}'")
            except Exception:
                continue

        # Park mouse cursor at top-left (0, 0) so no hovercards remain active
        try:
            body = driver.find_element(By.TAG_NAME, "body")
            ActionChains(driver).move_to_element_with_offset(body, 0, 0).perform()
        except Exception:
            pass

        _dismiss_author_hovercards(driver)
        return cached_count
    except Exception as exc:
        log.debug(f"  Hover timestamp pre-pass note: {exc}")
        return 0


def extract_posts_from_page(
    driver,
    seen_hashes: set[str],
    seen_urls: set[str],
    all_reports: list[FloodReport],
    json_path: Path,
    csv_path: Path,
    max_scrolls: int = 50,
    scroll_delay: float = 5.0,
    scroll_pixels: int = 800,
    active_query: str = "",
) -> int:
    """Scroll the current page, hover over each post's timestamp link individually to reveal
    Facebook's full date tooltip, extract the data, and save to disk immediately.

    Returns the number of *new* posts found on this page.
    """

    new_count = 0

    # Ensure in-browser helper functions are attached to window
    try:
        driver.execute_script(JS_BATCH_EXTRACT_POSTS)
    except Exception as exc:
        log.debug(f"  JS helper initialization note: {exc}")

    for scroll_i in range(max_scrolls):
        # 1. On subsequent scroll passes (scroll_i > 0), scroll down to load more content
        if scroll_i > 0:
            _fast_scroll(driver, pixels=random.randint(max(500, scroll_pixels - 150), scroll_pixels + 150))
            time.sleep(scroll_delay)

        # 2. Dismiss any stray profile hovercard dialogs
        _dismiss_author_hovercards(driver)

        # 3. Collect all unscraped card elements currently in the DOM
        try:
            cards = driver.execute_script("""
                const rawCandidates = Array.from(document.querySelectorAll(
                    'div[role="feed"] > div, div[role="article"], [data-ad-preview], .userContentWrapper'
                ));
                const seen = new Set();
                const unScraped = [];
                for (const c of rawCandidates) {
                    if (!c) continue;
                    let target = c.closest ? (c.closest('div[role="feed"] > div') || c.closest('[role="article"], [data-ad-preview], .userContentWrapper') || c) : c;
                    if (seen.has(target)) continue;
                    seen.add(target);
                    if (target.getAttribute('data-scraped') === 'true' || c.getAttribute('data-scraped') === 'true') continue;
                    unScraped.push(target);
                }
                return unScraped;
            """) or []
        except Exception as exc:
            log.debug(f"  Error querying unscraped cards: {exc}")
            cards = []

        cycle_new_count = 0

        # 4. Process each unscraped card one by one:
        #    - Scroll card so its header is positioned 120px below top (clearing Facebook's 56px sticky navbar)
        #    - Hydrate layout & expand 'See more'
        #    - Find exact timestamp link in header
        #    - Hover directly over timestamp link to trigger Facebook's date tooltip
        #    - Capture tooltip text & extract post data
        for card in cards:
            try:
                # Scroll card into clear viewing area (120px below top)
                driver.execute_script("""
                    const card = arguments[0];
                    card.scrollIntoView({ behavior: 'instant', block: 'start' });
                    window.scrollBy(0, -120);
                """, card)
                time.sleep(0.15)

                # Expand 'See more' if present in this card
                driver.execute_script("""
                    const card = arguments[0];
                    const buttons = card.querySelectorAll('div[role="button"], span[role="button"], a[role="button"], span, div, a');
                    const labels = ['see more', 'tan-awa pa', 'tingnan pa', 'mas marami'];
                    for (const btn of buttons) {
                        if (btn.children.length <= 1) {
                            const txt = (btn.innerText || btn.textContent || '').trim().toLowerCase();
                            if (labels.includes(txt)) {
                                try { btn.click(); } catch(e) {}
                            }
                        }
                    }
                """, card)

                # Find the timestamp element inside this card
                ts_el = driver.execute_script("""
                    const card = arguments[0];
                    return (window.__fb_findTimestampElement ? window.__fb_findTimestampElement(card) : null);
                """, card)

                captured = ""
                hovered_url = ""
                if ts_el:
                    try:
                        # Center the timestamp in the viewport (well clear of the sticky navbar)
                        driver.execute_script(
                            "arguments[0].scrollIntoView({behavior: 'instant', block: 'center'});", ts_el
                        )
                        time.sleep(0.15)

                        # Remove stale tooltips from the previous post
                        driver.execute_script(
                            "document.querySelectorAll('[role=\"tooltip\"]').forEach(t => t.remove());"
                        )

                        # Real mouse hover (Facebook ignores most synthetic events)
                        ActionChains(driver).move_to_element(ts_el).perform()

                        # Facebook shows the date tooltip after a ~0.5–1.5s delay: poll for it
                        js_read_tooltip = """
                            const el = arguments[0];
                            const dateRe = /(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\\.?\\s+\\d{1,2}|\\d{1,2}:\\d{2}|\\d{4}/i;
                            const descId = el.getAttribute('aria-describedby');
                            if (descId) {
                                const tip = document.getElementById(descId);
                                const txt = tip ? (tip.innerText || tip.textContent || '').trim() : '';
                                if (txt && dateRe.test(txt)) return txt;
                            }
                            for (const tt of document.querySelectorAll('[role="tooltip"]')) {
                                const txt = (tt.innerText || tt.textContent || '').trim();
                                if (txt && txt.length < 90 && dateRe.test(txt)) return txt;
                            }
                            const aria = (el.getAttribute('aria-label') || '').trim();
                            if (aria && aria.length < 90 && dateRe.test(aria)) return aria;
                            return '';
                        """
                        deadline = time.time() + 2.5
                        while time.time() < deadline:
                            captured = driver.execute_script(js_read_tooltip, ts_el) or ""
                            if captured:
                                break
                            time.sleep(0.15)

                        # On hover Facebook swaps href="#" for the real permalink
                        hovered_url = driver.execute_script(
                            "return arguments[0].href || arguments[0].getAttribute('href') || '';", ts_el
                        ) or ""
                        if not _is_post_url(hovered_url):
                            hovered_url = ""

                        if captured:
                            driver.execute_script(
                                "arguments[0].setAttribute('data-hovered-ts', arguments[2]);"
                                "arguments[1].setAttribute('data-hovered-ts', arguments[2]);",
                                ts_el, card, captured,
                            )
                            log.info(f"    [Hover Timestamp] Captured: '{captured}'")
                        else:
                            log.info("    [Hover Timestamp] No tooltip appeared for this post")
                    except Exception as h_err:
                        log.debug(f"  Hover note: {h_err}")
                else:
                    log.info("    [Hover Timestamp] Timestamp link not found in post header")

                # Extract the post card data and mark as scraped
                item = driver.execute_script("""
                    const card = arguments[0];
                    card.setAttribute('data-scraped', 'true');
                    return (window.__fb_extractSingleCard ? window.__fb_extractSingleCard(card) : null);
                """, card)

                # Close any author hovercard. (We intentionally do NOT "park" the mouse:
                # Selenium 4 offsets are from the element CENTER, so parking at body(0,0)
                # landed mid-page and opened hovercards over the next post.)
                _dismiss_author_hovercards(driver)

                if not item:
                    continue

                # The tooltip we hovered is the most reliable timestamp source
                if captured:
                    item["rawTimestamp"] = captured
                    item["timestamp"] = captured

                full_text = (item.get("fullText") or "").strip()
                if not full_text or len(full_text) < 3:
                    continue

                # Deduplicate by content hash
                text_hash = _hash_text(full_text[:500])
                if text_hash in seen_hashes:
                    continue

                # Relevance filter
                if not _is_flood_relevant(full_text, active_query=active_query):
                    continue

                author = item.get("author") or "Unknown"
                if author != "Unknown":
                    author = _clean_author_name(author)

                # Prefer the real permalink revealed by hovering the timestamp
                post_url = hovered_url or item.get("postUrl") or ""
                if post_url:
                    post_url = _clean_facebook_url(post_url)

                if post_url and post_url in seen_urls:
                    continue

                raw_ts = item.get("rawTimestamp") or item.get("timestamp") or ""
                # Fallback: extract date from body text if timestamp is missing
                if not raw_ts and full_text:
                    raw_ts = _extract_timestamp_from_text(full_text)

                ts_info = _normalize_timestamp_info(raw_ts, default_year=FILTER_YEAR)
                timestamp = ts_info["timestamp"]
                raw_timestamp = ts_info["raw_timestamp"]
                iso_timestamp = ts_info["iso_timestamp"]
                relative_time = ts_info["relative_time"]
                relative_seconds = ts_info["relative_seconds"]
                relative_minutes = ts_info["relative_minutes"]
                relative_hours = ts_info["relative_hours"]

                # ── Date Filtering ──
                parsed_dt = ts_info.get("parsed_dt") or _parse_timestamp_to_datetime(timestamp, default_year=FILTER_YEAR or datetime.now().year)
                if not _is_within_date_filter(
                    parsed_dt,
                    filter_year=FILTER_YEAR,
                    filter_months=FILTER_MONTHS,
                    start_date=_parse_date_str(FILTER_START_DATE),
                    end_date=_parse_date_str(FILTER_END_DATE),
                    allow_undated=ALLOW_UNDATED_POSTS
                ):
                    dt_label = parsed_dt.strftime("%Y-%m-%d %H:%M:%S") if parsed_dt else (timestamp or "Undated")
                    log.info(f"    ⊘ Skipped (post date '{dt_label}' outside active filter {FILTER_YEAR or ''}/{FILTER_MONTHS or ''})")
                    continue

                media_urls = item.get("mediaUrls") or []

                # ── Register seen & build report ──
                seen_hashes.add(text_hash)
                if post_url:
                    seen_urls.add(post_url)

                report = FloodReport(
                    post_id=text_hash[:12],
                    search_query=active_query,
                    post_url=post_url,
                    author=author or "Unknown",
                    timestamp=timestamp,
                    raw_timestamp=raw_timestamp,
                    iso_timestamp=iso_timestamp,
                    relative_time=relative_time,
                    relative_seconds=relative_seconds,
                    relative_minutes=relative_minutes,
                    relative_hours=relative_hours,
                    full_text=full_text,
                    detected_locations=_detect_locations(full_text),
                    urgency_level=_classify_urgency(full_text),
                    media_urls=media_urls,
                    scraped_at=datetime.now(timezone.utc).isoformat(),
                )
                all_reports.append(report)
                new_count += 1
                cycle_new_count += 1
                rel_label = f" ({report.relative_time})" if report.relative_time else (f" ({report.relative_seconds}s ago)" if report.relative_seconds is not None else "")
                log.info(
                    f"    ★ [{report.urgency_level}] {report.author[:25]} | "
                    f"Date: {report.timestamp or 'N/A'}{rel_label} | "
                    f"Link: {report.post_url or 'N/A'}  "
                    f"(#{len(all_reports)} total)"
                )

            except Exception as exc:
                log.debug(f"    ⚠ Article parse error: {exc}")
                continue

        log.info(f"  scroll {scroll_i + 1}/{max_scrolls} — +{cycle_new_count} new  |  total saved: {len(all_reports)}")

        # Batch flush to disk once per scroll if new posts were discovered
        if cycle_new_count > 0:
            save_incremental(all_reports, json_path, csv_path)

    return new_count

# ──────────────────────────────────────────────
# Output writers
# ──────────────────────────────────────────────

def save_json(reports: list[FloodReport], path: Path) -> None:
    """Write the full reports list to JSON (overwrites)."""
    data = [asdict(r) for r in reports]
    tmp_path = path.with_suffix(".json.tmp")
    with open(tmp_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    tmp_path.replace(path)
    log.info(f"💾 Saved {len(reports)} reports → {path}")


def save_csv(reports: list[FloodReport], path: Path) -> None:
    """Write the full reports list to CSV (overwrites)."""
    fieldnames = [
        "post_id",
        "search_query",
        "post_url",
        "author",
        "timestamp",
        "raw_timestamp",
        "iso_timestamp",
        "relative_time",
        "relative_seconds",
        "relative_minutes",
        "relative_hours",
        "full_text",
        "detected_locations",
        "urgency_level",
        "media_urls",
        "scraped_at",
    ]
    tmp_path = path.with_suffix(".csv.tmp")
    with open(tmp_path, "w", newline="", encoding="utf-8-sig") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction="ignore")
        writer.writeheader()
        for r in reports:
            row = asdict(r)
            # Flatten lists to semicolon-separated strings for CSV
            row["media_urls"] = "; ".join(row["media_urls"])
            row["detected_locations"] = "; ".join(row["detected_locations"])
            writer.writerow(row)
    tmp_path.replace(path)
    log.info(f"💾 Saved {len(reports)} reports → {path}")


def save_incremental(all_reports: list[FloodReport], json_path: Path, csv_path: Path) -> None:
    """Save all accumulated reports to both JSON and CSV right now."""
    save_json(all_reports, json_path)
    save_csv(all_reports, csv_path)


def load_existing_reports(json_path: Path) -> tuple[list[FloodReport], set[str], set[str]]:
    """Load previously saved reports so the scraper can resume after a
    crash, power loss, or manual stop without losing data."""
    reports: list[FloodReport] = []
    seen_hashes: set[str] = set()
    seen_urls: set[str] = set()

    if not json_path.exists():
        return reports, seen_hashes, seen_urls

    try:
        valid_keys = {f.name for f in fields(FloodReport)}
        with open(json_path, "r", encoding="utf-8") as f:
            data = json.load(f)
        for item in data:
            # Clean up corrupted entries from previous buggy runs
            if item.get("author", "").startswith("#"):
                item["author"] = "Unknown"
            if item.get("timestamp", "").startswith("#"):
                item["timestamp"] = ""

            filtered_item = {k: v for k, v in item.items() if k in valid_keys}
            report = FloodReport(**filtered_item)
            # Only consider a report already "seen" if it already has a valid timestamp.
            # If a report had an empty timestamp from a previous failed run, don't add it
            # to seen_hashes so the scraper can re-scrape it and properly capture its timestamp!
            if report.timestamp:
                reports.append(report)
                seen_hashes.add(report.post_id)  # post_id is the hash[:12]
                seen_hashes.add(_hash_text(report.full_text[:500]))
                if report.post_url:
                    seen_urls.add(report.post_url)
        log.info(f"📂 Loaded {len(reports)} existing reports from {json_path} — will resume from where we left off.")
    except (json.JSONDecodeError, TypeError, KeyError) as exc:
        log.warning(f"⚠ Could not load existing reports ({exc}). Starting fresh.")
        reports, seen_hashes, seen_urls = [], set(), set()

    return reports, seen_hashes, seen_urls

# ──────────────────────────────────────────────
# Orchestration
# ──────────────────────────────────────────────

def scrape_search_queries(
    driver,
    queries: list[str],
    seen_hashes: set[str],
    seen_urls: set[str],
    max_scrolls: int,
    all_reports: list[FloodReport],
    json_path: Path,
    csv_path: Path,
    scroll_delay: float = 5.0,
    scroll_pixels: int = 1000,
) -> None:
    """Iterate through Facebook search-post URLs for each query.
    Posts are saved incrementally inside extract_posts_from_page."""

    for i, query in enumerate(queries, 1):
        url = _build_search_url(query)
        log.info(f"▶ [{i}/{len(queries)}] Searching: \"{query}\"  →  {url}")
        try:
            driver.get(url)
            _human_pause(2.0, 3.5)

            # Dismiss login overlay if present
            _dismiss_login_overlay(driver)

            # Apply UI Date Posted filter if year/month is configured
            if FILTER_YEAR:
                _apply_fb_ui_date_filter(driver, FILTER_YEAR, FILTER_MONTHS)

            new_count = extract_posts_from_page(
                driver, seen_hashes, seen_urls,
                all_reports, json_path, csv_path,
                max_scrolls,
                scroll_delay=scroll_delay,
                scroll_pixels=scroll_pixels,
                active_query=query,
            )
            log.info(f"  ✓ +{new_count} new posts for \"{query}\"  |  Grand total: {len(all_reports)}")

        except KeyboardInterrupt:
            log.warning("\n[!] Ctrl+C detected. Data already saved incrementally.")
            raise
        except WebDriverException as exc:
            log.error(f"  ✗ Failed on query \"{query}\": {exc}")
            # Even on failure, data so far is already on disk
        _human_pause(1.5, 2.5)


def _dismiss_login_overlay(driver) -> None:
    """Try to close modal login prompts or cookie banners."""
    selectors = [
        '[aria-label="Close"]',
        '[data-testid="cookie-policy-manage-dialog-accept-button"]',
        'div[role="dialog"] button',
    ]
    for sel in selectors:
        try:
            btns = driver.find_elements(By.CSS_SELECTOR, sel)
            for btn in btns:
                if btn.is_displayed():
                    btn.click()
                    time.sleep(0.3)
        except Exception:
            pass

# ──────────────────────────────────────────────
# CLI
# ──────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Davao City Flood Report Scraper — Facebook",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    parser.add_argument(
        "--login",
        action="store_true",
        help="Open a visible browser window so you can log in manually. "
             "After logging in, close this script (Ctrl+C) and re-run without --login.",
    )
    parser.add_argument(
        "--headless",
        action="store_true",
        default=False,
        help="Run Chrome in headless mode (requires prior session via --login).",
    )
    parser.add_argument(
        "--scrolls",
        type=int,
        default=50,
        help="Number of scroll iterations per query / page (default: 50).",
    )
    parser.add_argument(
        "--scroll-delay",
        type=float,
        default=5.0,
        help="Delay in seconds after each scroll (default: 5.0).",
    )
    parser.add_argument(
        "--scroll-pixels",
        type=int,
        default=800,
        help="Pixels to scroll per cycle (default: 800).",
    )
    parser.add_argument(
        "--block-images",
        action="store_true",
        help="Block loading images and media in browser to accelerate scraping and save RAM.",
    )
    parser.add_argument(
        "--output-dir",
        type=str,
        default=".",
        help="Directory to write output files (default: current directory).",
    )
    parser.add_argument(
        "--profile-dir",
        type=str,
        default=None,
        help="Path to the Chrome user-data-dir for session persistence. "
             "Defaults to ./fb_chrome_profile in the script's directory.",
    )
    parser.add_argument(
        "--year",
        type=int,
        default=None,
        help="Filter posts by year (e.g. 2026). Also applies Facebook URL year filter.",
    )
    parser.add_argument(
        "--month",
        "--months",
        type=str,
        default=None,
        dest="months",
        help="Filter posts by month(s). Pass number (7), name (July), or comma-separated (7,8 / July,August).",
    )
    parser.add_argument(
        "--start-date",
        type=str,
        default=None,
        help="Filter posts starting from date inclusive (YYYY-MM-DD, e.g. 2026-07-01).",
    )
    parser.add_argument(
        "--end-date",
        type=str,
        default=None,
        help="Filter posts up to date inclusive (YYYY-MM-DD, e.g. 2026-07-31).",
    )
    parser.add_argument(
        "--fb-year-filter",
        action="store_true",
        help="Apply Facebook search URL base64 year filter in addition to search query keywords.",
    )
    parser.add_argument(
        "--include-undated",
        action="store_true",
        help="Keep posts with unparsed/missing timestamps when date filtering is active.",
    )
    parser.add_argument(
        "--email",
        type=str,
        default=os.environ.get("FB_EMAIL", "znarpnarp@gmail.com"),
        help="Facebook login email for automated authentication.",
    )
    parser.add_argument(
        "--password",
        type=str,
        default=os.environ.get("FB_PASSWORD", "SmithJohn123"),
        help="Facebook login password for automated authentication.",
    )
    parser.add_argument(
        "--queries",
        type=str,
        default=None,
        help="JSON list or comma-separated search queries from AGOS portal.",
    )
    parser.add_argument(
        "--queries-file",
        type=str,
        default=None,
        help="Path to JSON or newline-delimited file containing search queries.",
    )

    return parser.parse_args()


def main() -> None:
    global FILTER_YEAR, FILTER_MONTHS, FILTER_START_DATE, FILTER_END_DATE, APPLY_FB_YEAR_FILTER, ALLOW_UNDATED_POSTS
    try:
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass
    args = parse_args()

    # ── CLI Overrides for Date Filtering ──
    if args.year is not None:
        FILTER_YEAR = args.year
    if args.months is not None:
        parsed_months = []
        for m_str in args.months.split(","):
            m_clean = m_str.strip().lower()
            if m_clean.isdigit():
                parsed_months.append(int(m_clean))
            elif m_clean in MONTH_NAME_TO_INT:
                parsed_months.append(MONTH_NAME_TO_INT[m_clean])
        if parsed_months:
            FILTER_MONTHS = parsed_months
    if args.start_date is not None:
        FILTER_START_DATE = args.start_date
    if args.end_date is not None:
        FILTER_END_DATE = args.end_date
    if args.fb_year_filter:
        APPLY_FB_YEAR_FILTER = True
    if args.include_undated:
        ALLOW_UNDATED_POSTS = True

    # ── Resolve paths ──
    script_dir = Path(__file__).resolve().parent
    default_local_profile = script_dir / "fb_chrome_profile"
    thesis_fallback_profile = Path(r"c:\Users\narp\Desktop\Coding Projects\Thesis\fb_chrome_profile")
    if args.profile_dir:
        profile_dir = args.profile_dir
    elif default_local_profile.exists():
        profile_dir = str(default_local_profile)
    elif thesis_fallback_profile.exists():
        profile_dir = str(thesis_fallback_profile)
    else:
        profile_dir = str(default_local_profile)

    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    # ── Resolve search queries (AGOS dynamic queries vs terminal default fallback) ──
    # NOTICE: Only falls back to SEARCH_QUERIES if run directly from terminal without arguments.
    active_queries: list[str] = list(SEARCH_QUERIES)
    if args.queries_file and Path(args.queries_file).exists():
        try:
            raw_q = Path(args.queries_file).read_text(encoding="utf-8").strip()
            if raw_q.startswith("["):
                loaded = json.loads(raw_q)
                active_queries = [str(q).strip() for q in loaded if str(q).strip()]
            else:
                active_queries = [line.strip() for line in raw_q.splitlines() if line.strip()]
        except Exception as q_err:
            log.warning(f"Failed to read --queries-file ({q_err}); falling back to default queries.")
    elif args.queries:
        raw_q = args.queries.strip()
        if raw_q.startswith("["):
            try:
                loaded = json.loads(raw_q)
                active_queries = [str(q).strip() for q in loaded if str(q).strip()]
            except Exception:
                active_queries = [q.strip() for q in raw_q.split(",") if q.strip()]
        else:
            active_queries = [q.strip() for q in raw_q.split(",") if q.strip()]

    json_path = output_dir / "davao_fb_flood_reports.json"
    csv_path = output_dir / "davao_fb_flood_reports.csv"

    # Display active date filtering summary
    month_names_str = ""
    if FILTER_MONTHS:
        inv_map = {1: "Jan", 2: "Feb", 3: "Mar", 4: "Apr", 5: "May", 6: "Jun", 7: "Jul", 8: "Aug", 9: "Sep", 10: "Oct", 11: "Nov", 12: "Dec"}
        month_names_str = ", ".join([inv_map.get(m, str(m)) for m in FILTER_MONTHS])

    log.info("=" * 60)
    log.info("  Davao City Flood Report Scraper — Facebook (Fast Mode)")
    log.info(f"  Profile dir  : {profile_dir}")
    log.info(f"  Output dir   : {output_dir.resolve()}")
    log.info(f"  Scrolls/page : {args.scrolls}")
    log.info(f"  Scroll delay : {args.scroll_delay}s")
    log.info(f"  Block images : {args.block_images}")
    log.info(f"  Filter Year  : {FILTER_YEAR or 'Any'}")
    log.info(f"  Filter Months: {month_names_str or 'Any'}")
    log.info(f"  Date Range   : {FILTER_START_DATE or 'Start'} → {FILTER_END_DATE or 'End'}")
    log.info(f"  FB Year URL  : {'Enabled' if (FILTER_YEAR and APPLY_FB_YEAR_FILTER) else 'Disabled'}")
    log.info("=" * 60)

    # ── Load any previously saved reports (resume support) ──
    all_reports, seen_hashes, seen_urls = load_existing_reports(json_path)
    if all_reports:
        log.info(f"  Resuming with {len(all_reports)} previously saved reports.")
    else:
        # Create empty output files in the target directory immediately
        save_incremental(all_reports, json_path, csv_path)

    # ── Launch browser ──
    headless = args.headless and not args.login
    driver = create_driver(profile_dir, headless=headless, block_images=args.block_images)

    # Register an emergency save handler so that even if the process is
    # killed (e.g. terminal closed, SIGTERM), we attempt a final save.
    def _emergency_save(*_args):
        log.warning("\n🚨 Emergency save triggered (signal/atexit)…")
        save_incremental(all_reports, json_path, csv_path)

    atexit.register(_emergency_save)
    # SIGTERM (sent when terminal is closed on some systems)
    try:
        signal.signal(signal.SIGTERM, lambda *_: (_emergency_save(), sys.exit(0)))
    except (OSError, ValueError):
        pass  # Not available on all platforms / threads

    try:
        # ── Login flow ──
        if args.login:
            if args.email and args.password:
                log.info(f"Attempting automated login for {args.email}…")
                if login_with_credentials(driver, args.email, args.password):
                    log.info("Login session saved. You can now run the scraper.")
                    return
            log.info("Opening Facebook for manual login…")
            log.info("Log in to your account, then press Ctrl+C to close this session.")
            log.info("After that, re-run the script WITHOUT --login to scrape.")
            driver.get("https://www.facebook.com/login")
            try:
                while True:
                    time.sleep(5)
            except KeyboardInterrupt:
                log.info("Login session saved. You can now run the scraper.")
                return
        else:
            if not ensure_logged_in(driver):
                if args.email and args.password:
                    log.info(f"No active session found. Attempting automated login for {args.email}…")
                    if not login_with_credentials(driver, args.email, args.password):
                        log.error(
                            "Failed to authenticate automatically.\n"
                            "Run with --login to authenticate manually:\n"
                            "    python davao_flood_scraper.py --login"
                        )
                        return
                else:
                    log.error(
                        "No active Facebook session found.\n"
                        "Run with --login first to authenticate:\n"
                        "    python davao_flood_scraper.py --login"
                    )
                    return

        # ── Search-based discovery ──
        log.info(f"\n━━━ Searching for Davao flood posts ({len(active_queries)} queries) ━━━")
        scrape_search_queries(
            driver, active_queries, seen_hashes, seen_urls,
            args.scrolls, all_reports, json_path, csv_path,
            scroll_delay=args.scroll_delay,
            scroll_pixels=args.scroll_pixels,
        )

        # ── Final summary ──
        log.info("\n" + "=" * 60)
        log.info(f"  Total flood reports collected: {len(all_reports)}")
        sitrep = sum(1 for r in all_reports if r.urgency_level == "SITREP")
        high = sum(1 for r in all_reports if r.urgency_level == "HIGH")
        log.info(f"  SITREP: {sitrep}  |  HIGH: {high}  |  NORMAL: {len(all_reports) - sitrep - high}")
        log.info("=" * 60)

        # One last save to be sure
        save_incremental(all_reports, json_path, csv_path)

        if not all_reports:
            log.warning("No flood-related posts were found during this run.")

    except KeyboardInterrupt:
        log.info("\n⏹ Interrupted by user. Data already saved incrementally — nothing lost.")
        save_incremental(all_reports, json_path, csv_path)
    except Exception as exc:
        log.exception(f"Unexpected error: {exc}")
        # Save whatever we have before crashing
        save_incremental(all_reports, json_path, csv_path)
    finally:
        try:
            driver.quit()
        except Exception:
            pass
        log.info(f"Browser closed. Done. Total reports on disk: {len(all_reports)}")


if __name__ == "__main__":
    main()


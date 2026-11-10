"""
verify_scraped_stream.py
========================
Verification & Actionable Location Gating Tool for Scraped Flood Reports.

Takes scraped social media posts from Selenium (Phase 1) and Preprocessing (Phase 2),
runs them through the Multi-Task XLM-RoBERTa pipeline (Phase 3), and outputs:
  1. Primary Feed: Only verified, geocoded reports (for K-Means++ clustering & AGOS LGU Dashboard).
  2. Audit Log: All posts with explicit discard reasons (for transparency & research metrics).

Usage:
  python verify_scraped_stream.py --input data/Cleaned/2022/cleaned_davao_flood_reports.json
  python verify_scraped_stream.py --input data/Uncleaned/2022/davao_fb_flood_reports.csv --threshold 0.5
"""

import os
import sys
import json
import csv
import logging
import argparse
from typing import Dict, List, Optional, Any

import torch
from transformers import XLMRobertaTokenizerFast

from config import MTLConfig
from model import MultiTaskXLMRoBERTa
from inference import FloodInferencePipeline

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("FloodVerifier")


def parse_args():
    parser = argparse.ArgumentParser(
        description="Verify scraped social media posts and extract actionable flood reports."
    )
    parser.add_argument(
        "--input",
        "-i",
        type=str,
        default=None,
        help="Path to scraped JSON or CSV file to verify (optional if --year is provided).",
    )
    parser.add_argument(
        "--year",
        "-y",
        type=str,
        default=None,
        help="Year to verify (e.g. 2020, 2021, ... 2026) or 'ALL' to verify all years.",
    )
    parser.add_argument(
        "--folder",
        "-f",
        type=str,
        default="Cleaned",
        choices=["Cleaned", "Uncleaned", "Merged"],
        help="Folder category when using --year: Cleaned (default), Uncleaned, or Merged.",
    )
    parser.add_argument(
        "--checkpoint",
        "-c",
        type=str,
        default="checkpoints/best_model.pt",
        help="Path to trained Multi-Task XLM-RoBERTa checkpoint (.pt). Defaults to checkpoints/best_model.pt.",
    )
    parser.add_argument(
        "--output-dir",
        "-o",
        type=str,
        default="data/Output",
        help="Directory where verified reports and audit logs will be saved.",
    )
    parser.add_argument(
        "--threshold",
        "-t",
        type=float,
        default=0.50,
        help="Relevance probability threshold (P_rel) for flood classification.",
    )
    return parser.parse_args()


def load_input_posts(input_path: str) -> List[Dict[str, Any]]:
    """
    Load raw posts from a JSON or CSV file.
    Returns list of dicts with at least 'post_id' and 'raw_text'.
    """
    if not os.path.exists(input_path):
        raise FileNotFoundError(f"Input file not found: {input_path}")

    posts = []
    if input_path.endswith(".json"):
        with open(input_path, "r", encoding="utf-8") as f:
            data = json.load(f)
    elif input_path.endswith(".csv"):
        with open(input_path, "r", encoding="utf-8-sig") as f:
            data = list(csv.DictReader(f))
    else:
        raise ValueError(f"Unsupported file format: {input_path}. Must be .json or .csv")

    for idx, item in enumerate(data):
        pid = item.get("post_id") or f"post_{idx:04d}"
        text = (
            item.get("full_text")
            or item.get("text")
            or item.get("raw_text")
            or ""
        )
        if not text.strip():
            continue

        posts.append({
            "post_id": pid,
            "raw_text": text,
            "author": item.get("author", ""),
            "timestamp": item.get("timestamp") or item.get("raw_timestamp") or item.get("scraped_at") or "",
            "iso_timestamp": item.get("iso_timestamp", ""),
            "post_url": item.get("post_url", ""),
            "urgency_level": item.get("urgency_level", ""),
        })

    logger.info(f"Loaded {len(posts)} valid text posts from: {input_path}")
    return posts


def run_verification(
    input_posts: List[Dict[str, Any]],
    pipeline: FloodInferencePipeline,
    output_dir: str,
    base_name: str,
):
    """
    Run the two-stage verification gate and save separated outputs.
    """
    os.makedirs(output_dir, exist_ok=True)
    total = len(input_posts)
    logger.info(f"Running inference and verification on {total} posts...")

    all_results = []
    verified_results = []
    no_urgency_results = []

    for i, post in enumerate(input_posts, start=1):
        pid = post["post_id"]
        raw_text = post["raw_text"]
        pred = pipeline.predict(raw_text=raw_text, post_id=pid)

        # Merge scraper metadata with model prediction
        combined = {
            "post_id": pid,
            "raw_text": raw_text,
            "author": post.get("author", ""),
            "timestamp": post.get("timestamp", ""),
            "iso_timestamp": post.get("iso_timestamp", ""),
            "post_url": post.get("post_url", ""),
            "is_flood_related": pred["is_flood_related"],
            "p_rel": pred["p_rel"],
            "extracted_locations": pred["extracted_locations"],
            "verification_status": pred["verification_status"],
            "is_actionable": pred["is_actionable"],
            "discard_reason": pred.get("discard_reason", ""),
        }

        all_results.append(combined)
        if combined["is_actionable"]:
            verified_results.append(combined)
        elif combined["verification_status"] == "FLOOD_MENTION_NO_URGENCY":
            no_urgency_results.append(combined)

        if i % 100 == 0 or i == total:
            logger.info(
                f"Progress: {i}/{total} posts processed "
                f"({len(verified_results)} actionable, {len(no_urgency_results)} early warning so far)"
            )

    # ── Separate Discard Reasons & Counts ───────────────────────────────
    n_non_flood = sum(1 for r in all_results if r["verification_status"] == "DISCARDED_NOT_FLOOD")
    n_unlocalized = sum(1 for r in all_results if r["verification_status"] == "DISCARDED_UNLOCALIZED")
    n_actionable = len(verified_results)
    n_no_urgency = len(no_urgency_results)

    # ── 1. Save Primary Verified Feed (Phase 4 / K-Means++ Handoff) ─────
    verified_json = os.path.join(output_dir, f"verified_flood_reports_{base_name}.json")
    verified_csv = os.path.join(output_dir, f"verified_flood_reports_{base_name}.csv")

    with open(verified_json, "w", encoding="utf-8") as f:
        json.dump(verified_results, f, indent=2, ensure_ascii=False)

    fieldnames = [
        "post_id", "author", "timestamp", "iso_timestamp", "p_rel", "extracted_locations",
        "verification_status", "raw_text", "post_url"
    ]

    with open(verified_csv, "w", encoding="utf-8-sig", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        for r in verified_results:
            writer.writerow({
                "post_id": r["post_id"],
                "author": r["author"],
                "timestamp": r["timestamp"],
                "iso_timestamp": r.get("iso_timestamp", ""),
                "p_rel": r["p_rel"],
                "extracted_locations": ", ".join(r["extracted_locations"]),
                "verification_status": r["verification_status"],
                "raw_text": r["raw_text"],
                "post_url": r["post_url"],
            })

    # ── 2. Save Flood Mentioned No Urgency (Human-in-the-Loop / Early Detection) ──
    no_urgency_json = os.path.join(output_dir, f"flood_mentioned_no_urgency_{base_name}.json")
    no_urgency_csv = os.path.join(output_dir, f"flood_mentioned_no_urgency_{base_name}.csv")

    with open(no_urgency_json, "w", encoding="utf-8") as f:
        json.dump(no_urgency_results, f, indent=2, ensure_ascii=False)

    fieldnames_no_urgency = [
        "post_id", "author", "timestamp", "iso_timestamp", "p_rel", "extracted_locations",
        "verification_status", "discard_reason", "raw_text", "post_url"
    ]

    with open(no_urgency_csv, "w", encoding="utf-8-sig", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames_no_urgency)
        writer.writeheader()
        for r in no_urgency_results:
            writer.writerow({
                "post_id": r["post_id"],
                "author": r["author"],
                "timestamp": r["timestamp"],
                "iso_timestamp": r.get("iso_timestamp", ""),
                "p_rel": r["p_rel"],
                "extracted_locations": ", ".join(r["extracted_locations"]),
                "verification_status": r["verification_status"],
                "discard_reason": r.get("discard_reason", ""),
                "raw_text": r["raw_text"],
                "post_url": r["post_url"],
            })

    # ── 3. Save Comprehensive Audit Log ─────────────────────────────────
    audit_csv = os.path.join(output_dir, f"pipeline_audit_log_{base_name}.csv")
    fieldnames_audit = [
        "post_id", "author", "timestamp", "iso_timestamp", "p_rel", "extracted_locations",
        "verification_status", "discard_reason", "raw_text", "post_url"
    ]
    with open(audit_csv, "w", encoding="utf-8-sig", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames_audit)
        writer.writeheader()
        for r in all_results:
            writer.writerow({
                "post_id": r["post_id"],
                "author": r["author"],
                "timestamp": r["timestamp"],
                "iso_timestamp": r.get("iso_timestamp", ""),
                "p_rel": r["p_rel"],
                "extracted_locations": ", ".join(r["extracted_locations"]),
                "verification_status": r["verification_status"],
                "discard_reason": r.get("discard_reason", ""),
                "raw_text": r["raw_text"],
                "post_url": r["post_url"],
            })

    # ── Print Operational Report ────────────────────────────────────────
    print("\n" + "=" * 75)
    print("FLOOD REPORT VERIFICATION & ACTIONABLE GATING SUMMARY")
    print("=" * 75)
    print(f"Total Scraped Posts Analyzed         : {total}")
    print(f"  [+] VERIFIED ACTIONABLE (Emergency): {n_actionable:4d} ({n_actionable / max(total, 1):.1%}) [P_rel >= {pipeline.config.relevance_threshold} & Geolocated]")
    print(f"  [!] FLOOD MENTION (No Urgency/HITL): {n_no_urgency:4d} ({n_no_urgency / max(total, 1):.1%}) [Emerging flood watch / Start of flooding]")
    print(f"  [-] DISCARDED: Non-Flood Noise     : {n_non_flood:4d} ({n_non_flood / max(total, 1):.1%}) [Ads, politics, idioms, lookalikes]")
    print(f"  [-] DISCARDED: Unlocalized Flood   : {n_unlocalized:4d} ({n_unlocalized / max(total, 1):.1%}) [Real flood, but no location]")
    print("-" * 75)
    print("Generated Output Feeds in data/Output/:")
    print(f"  1. Actionable Reports (K-Means++ & AGOS LGU Alerts):")
    print(f"     -> JSON: {verified_json}")
    print(f"     -> CSV : {verified_csv}")
    print(f"  2. Flood Mentioned No Urgency (Human-in-the-Loop Early Warning):")
    print(f"     -> JSON: {no_urgency_json}")
    print(f"     -> CSV : {no_urgency_csv}")
    print(f"  3. Complete Pipeline Audit Log (All posts + drop reasons):")
    print(f"     -> CSV : {audit_csv}")
    print("=" * 75 + "\n")


def main():
    args = parse_args()

    # 1. Resolve checkpoint path
    checkpoint_path = args.checkpoint
    if not os.path.exists(checkpoint_path):
        cand = os.path.join(os.path.dirname(__file__), checkpoint_path)
        if os.path.exists(cand):
            checkpoint_path = cand
        else:
            raise FileNotFoundError(f"Checkpoint not found at '{args.checkpoint}'")

    # 2. Resolve input files
    target_files = []
    base_dir = os.path.dirname(__file__)

    if args.input:
        target_files.append(args.input)
    elif args.year:
        folder_dir = os.path.join(base_dir, "data", args.folder)
        if args.year.upper() == "ALL":
            # Discover all year directories (e.g. 2020..2026)
            year_dirs = sorted([d for d in os.listdir(folder_dir) if os.path.isdir(os.path.join(folder_dir, d)) and d.isdigit()])
            for yd in year_dirs:
                yd_path = os.path.join(folder_dir, yd)
                files = [f for f in os.listdir(yd_path) if f.endswith((".json", ".csv")) and not f.endswith("report.txt")]
                if files:
                    target_files.append(os.path.join(yd_path, files[0]))
        else:
            yd_path = os.path.join(folder_dir, str(args.year))
            if os.path.exists(yd_path):
                files = [f for f in os.listdir(yd_path) if f.endswith((".json", ".csv")) and not f.endswith("report.txt")]
                if files:
                    target_files.append(os.path.join(yd_path, files[0]))
            else:
                raise FileNotFoundError(f"Year directory not found: {yd_path}")
    else:
        # Default fallback to merged cleaned dataset
        default_merged = os.path.join(base_dir, "data", "Merged", "cleaned_data_merged.json")
        if os.path.exists(default_merged):
            logger.info(f"No --input or --year specified. Defaulting to merged dataset: {default_merged}")
            target_files.append(default_merged)
        else:
            raise ValueError("Please provide either --input <file> or --year <year/ALL>.")

    if not target_files:
        raise FileNotFoundError("No valid input files found for verification.")

    config = MTLConfig(relevance_threshold=args.threshold)

    logger.info(f"Loading pipeline from checkpoint: {checkpoint_path}")
    pipeline = FloodInferencePipeline.from_checkpoint(
        checkpoint_path=checkpoint_path,
        config=config,
    )

    # Process all selected files
    for input_file in target_files:
        logger.info(f"\nProcessing file: {input_file}")
        
        # Determine clean base name for output files
        input_norm = os.path.normpath(input_file)
        parts = input_norm.split(os.sep)
        year_part = next((p for p in parts if p.isdigit() and len(p) == 4), "")

        base_file = os.path.splitext(os.path.basename(input_file))[0]
        base_file = base_file.replace("cleaned_davao_", "").replace("davao_fb_", "")
        if year_part and year_part not in base_file:
            base_file = f"{year_part}_{base_file}"

        posts = load_input_posts(input_file)
        run_verification(
            input_posts=posts,
            pipeline=pipeline,
            output_dir=args.output_dir,
            base_name=base_file,
        )


if __name__ == "__main__":
    main()

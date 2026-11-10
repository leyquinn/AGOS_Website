"""
utils.py — Shared Utilities for Multi-Task XLM-RoBERTa Flood Monitoring
========================================================================
Provides:
  - IOB tag decoding and entity span reconstruction
  - Metric computation helpers (classification + NER)
  - Reproducibility seeding
"""

import random
from typing import Dict, List, Tuple

import numpy as np
import torch


# ═══════════════════════════════════════════════════════════════════════
# Reproducibility
# ═══════════════════════════════════════════════════════════════════════

def set_seed(seed: int) -> None:
    """
    Set random seeds across all libraries for reproducible results.

    Args:
        seed: Integer seed value.
    """
    random.seed(seed)
    np.random.seed(seed)
    torch.manual_seed(seed)
    torch.cuda.manual_seed_all(seed)
    # Deterministic operations (may reduce performance)
    torch.backends.cudnn.deterministic = True
    torch.backends.cudnn.benchmark = False


# ═══════════════════════════════════════════════════════════════════════
# IOB Tag Decoding
# ═══════════════════════════════════════════════════════════════════════

def decode_iob_tags(
    tokens: List[str],
    tag_ids: List[int],
    id2label: Dict[int, str],
    ignore_index: int = -100,
) -> List[str]:
    """
    Reconstruct named entity spans from IOB-tagged subword tokens.

    This function:
      1. Filters out tokens with the ignore_index label (special tokens,
         subword continuations).
      2. Merges consecutive B-LOC / I-LOC tags into full entity strings.
      3. Handles edge cases like I-LOC appearing without a preceding B-LOC
         (treats it as a new entity start).

    Args:
        tokens:       List of subword token strings (from tokenizer).
        tag_ids:      Corresponding predicted label IDs per token.
        id2label:     Mapping from label ID to label string.
        ignore_index: Label ID to skip (default: -100).

    Returns:
        List of extracted entity strings (e.g., ["Barangay Bucana"]).
    """
    entities = []
    current_entity_tokens = []

    for token, tag_id in zip(tokens, tag_ids):
        # Handle special tokens (like <s>, </s>, <pad>)
        if tag_id == ignore_index:
            # If a genuine subword continuation is marked -100, preserve it (exclude special tokens)
            if current_entity_tokens and not token.startswith("\u2581") and not token.startswith("<"):
                current_entity_tokens.append(token)
            continue

        # SentencePiece subword continuation (does not start with ' ')
        # If an entity is currently in progress, this subword completes the word
        if current_entity_tokens and not token.startswith("\u2581"):
            current_entity_tokens.append(token)
            continue

        label = id2label.get(tag_id, "O")

        if label == "B-LOC":
            # Save any in-progress entity before starting a new one
            if current_entity_tokens:
                entities.append(_merge_subword_tokens(current_entity_tokens))
            current_entity_tokens = [token]

        elif label == "I-LOC":
            if current_entity_tokens:
                # Continue the current entity
                current_entity_tokens.append(token)
            else:
                # I-LOC without B-LOC — treat as new entity start (robustness)
                current_entity_tokens = [token]

        else:  # "O" tag
            # Flush any in-progress entity
            if current_entity_tokens:
                entities.append(_merge_subword_tokens(current_entity_tokens))
                current_entity_tokens = []

    # Flush final entity if the sequence ends mid-entity
    if current_entity_tokens:
        entities.append(_merge_subword_tokens(current_entity_tokens))

    return entities


def _merge_subword_tokens(tokens: List[str]) -> str:
    """
    Merge SentencePiece subword tokens back into a readable string.

    XLM-RoBERTa uses the SentencePiece '▁' (U+2581) prefix to denote
    word boundaries. This function:
      - Strips the '▁' prefix from each token
      - Joins tokens with spaces where word boundaries exist
      - Cleans up extra whitespace

    Args:
        tokens: List of subword tokens to merge.

    Returns:
        Reconstructed entity string.
    """
    result = ""
    for token in tokens:
        if token.startswith("▁"):
            # Word boundary — add space before (unless it's the first token)
            if result:
                result += " "
            result += token[1:]  # Strip the ▁ prefix
        else:
            # Continuation of previous word — concatenate directly
            result += token

    return result.strip()


# ═══════════════════════════════════════════════════════════════════════
# Entity Extraction from Word-Level Predictions
# ═══════════════════════════════════════════════════════════════════════

def extract_entities_from_word_predictions(
    words: List[str],
    word_labels: List[str],
) -> List[str]:
    """
    Extract entity spans from word-level (not subword) predictions.

    This is a higher-level alternative to decode_iob_tags, operating on
    pre-aligned word-level labels (after subword → word aggregation).

    Args:
        words:       Original whitespace-split words.
        word_labels: IOB label string per word (e.g., "B-LOC", "I-LOC", "O").

    Returns:
        List of extracted entity strings.
    """
    entities = []
    current_entity_words = []

    for word, label in zip(words, word_labels):
        if label == "B-LOC":
            if current_entity_words:
                entities.append(" ".join(current_entity_words))
            current_entity_words = [word]
        elif label == "I-LOC":
            if current_entity_words:
                current_entity_words.append(word)
            else:
                current_entity_words = [word]
        else:
            if current_entity_words:
                entities.append(" ".join(current_entity_words))
                current_entity_words = []

    if current_entity_words:
        entities.append(" ".join(current_entity_words))

    return entities


# ═══════════════════════════════════════════════════════════════════════
# Metric Computation
# ═══════════════════════════════════════════════════════════════════════

def compute_classification_metrics(
    predictions: np.ndarray,
    labels: np.ndarray,
) -> Dict[str, float]:
    """
    Compute binary classification metrics.

    Args:
        predictions: Predicted class indices (0 or 1).
        labels:      Ground truth class indices (0 or 1).

    Returns:
        Dictionary with accuracy, precision, recall, and F1.
    """
    tp = np.sum((predictions == 1) & (labels == 1))
    fp = np.sum((predictions == 1) & (labels == 0))
    fn = np.sum((predictions == 0) & (labels == 1))

    accuracy = np.mean(predictions == labels)
    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1 = (2 * precision * recall / (precision + recall)
          if (precision + recall) > 0 else 0.0)

    return {
        "accuracy": float(accuracy),
        "precision": float(precision),
        "recall": float(recall),
        "f1": float(f1),
    }


def compute_ner_metrics(
    pred_tag_ids: List[List[int]],
    true_tag_ids: List[List[int]],
    id2label: Dict[int, str],
    ignore_index: int = -100,
) -> Dict[str, float]:
    """
    Compute token-level NER metrics (precision, recall, F1).

    Filters out tokens with ignore_index before computing.

    Args:
        pred_tag_ids: Nested list of predicted label IDs per sequence.
        true_tag_ids: Nested list of ground-truth label IDs per sequence.
        id2label:     ID-to-label mapping.
        ignore_index: Label ID to exclude from evaluation.

    Returns:
        Dictionary with token-level precision, recall, and F1.
    """
    true_flat = []
    pred_flat = []

    for true_seq, pred_seq in zip(true_tag_ids, pred_tag_ids):
        for t, p in zip(true_seq, pred_seq):
            if t != ignore_index:
                true_flat.append(id2label.get(t, "O"))
                pred_flat.append(id2label.get(p, "O"))

    # Compute per-label metrics, focused on LOC entities
    true_flat_np = np.array(true_flat)
    pred_flat_np = np.array(pred_flat)

    # Entity-relevant labels (B-LOC, I-LOC)
    loc_labels = {"B-LOC", "I-LOC"}

    tp = np.sum(
        [(t in loc_labels) and (t == p)
         for t, p in zip(true_flat, pred_flat)]
    )
    fp = np.sum(
        [(p in loc_labels) and (t != p)
         for t, p in zip(true_flat, pred_flat)]
    )
    fn = np.sum(
        [(t in loc_labels) and (t != p)
         for t, p in zip(true_flat, pred_flat)]
    )

    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1 = (2 * precision * recall / (precision + recall)
          if (precision + recall) > 0 else 0.0)

    return {
        "ner_precision": float(precision),
        "ner_recall": float(recall),
        "ner_f1": float(f1),
    }

"""
config.py — Centralized Configuration for Multi-Task XLM-RoBERTa Flood Monitoring
==================================================================================
All hyperparameters, paths, label mappings, and training settings are defined here
as a single dataclass to ensure consistency across all modules.
"""

from dataclasses import dataclass, field
from typing import Dict, List


@dataclass
class MTLConfig:
    """
    Master configuration for the Multi-Task XLM-RoBERTa flood monitoring system.

    Attributes are grouped by concern:
      - Model architecture
      - Training hyperparameters
      - Loss weighting
      - NER label schema
      - Inference settings
      - Paths
    """

    # ── Model Architecture ──────────────────────────────────────────────
    model_name: str = "xlm-roberta-base"
    hidden_size: int = 768                  # XLM-R base hidden dimension
    num_classification_labels: int = 2      # Binary: 0=Non-flood, 1=Flood
    dropout_rate: float = 0.1               # Dropout before task heads

    # ── NER Label Schema (IOB2 format) ──────────────────────────────────
    # Labels for barangay-level location entities
    ner_labels: List[str] = field(default_factory=lambda: [
        "O",        # Outside any entity
        "B-LOC",    # Beginning of a location entity
        "I-LOC",    # Inside (continuation) of a location entity
    ])

    # ── Training Hyperparameters ────────────────────────────────────────
    max_seq_length: int = 256               # Max subword tokens per input (raised from 128)
    batch_size: int = 2                     # Fits within 4 GB VRAM limit at 256 seq_len
    num_epochs: int = 5                     # Optimal epochs identified via grid search
    learning_rate: float = 2e-5             # AdamW LR (optimal from grid: 2e-5)
    weight_decay: float = 0.01              # AdamW weight decay
    warmup_ratio: float = 0.1               # Fraction of total steps for LR warmup
    max_grad_norm: float = 1.0              # Gradient clipping threshold
    gradient_accumulation_steps: int = 4    # Effective batch = 2 * 4 = 8
    use_fp16: bool = True                   # Mixed precision for VRAM savings (especially on 4GB GPUs)

    # ── Multi-Task Loss Weights ─────────────────────────────────────────
    # L_total = alpha * L_classification + beta * L_ner
    alpha: float = 1.0                      # Weight for classification loss
    beta: float = 1.0                       # Weight for NER loss

    # ── Inference Settings ──────────────────────────────────────────────
    relevance_threshold: float = 0.5        # P_rel threshold for flood relevance
    device: str = "cuda"                    # "cuda" or "cpu"

    # ── Paths ───────────────────────────────────────────────────────────
    output_dir: str = "./checkpoints"
    best_model_path: str = "./checkpoints/best_model.pt"

    # ── Data Split (Train / Validation / Test: 80% / 10% / 10%) ─────────
    val_split: float = 0.10                 # Fraction held out for validation / model selection
    test_split: float = 0.10                # Fraction held out for final unbiased testing

    # ── Seed ────────────────────────────────────────────────────────────
    seed: int = 42

    # ── Derived Properties ──────────────────────────────────────────────
    @property
    def num_ner_labels(self) -> int:
        """Number of NER labels (O, B-LOC, I-LOC)."""
        return len(self.ner_labels)

    @property
    def ner_label2id(self) -> Dict[str, int]:
        """Mapping from NER label string to integer ID."""
        return {label: idx for idx, label in enumerate(self.ner_labels)}

    @property
    def ner_id2label(self) -> Dict[int, str]:
        """Mapping from integer ID to NER label string."""
        return {idx: label for idx, label in enumerate(self.ner_labels)}

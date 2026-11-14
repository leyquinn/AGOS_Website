"""
model.py — Multi-Task XLM-RoBERTa Model Architecture
======================================================
Implements a shared-encoder, dual-head architecture:

    ┌─────────────────────────────────────────────────┐
    │           XLM-RoBERTa Shared Encoder            │
    │         (xlm-roberta-base, 768-dim)             │
    └──────────────┬──────────────────┬───────────────┘
                   │                  │
         [CLS] hidden state    All token hidden states
                   │                  │
         ┌────────┴────────┐  ┌──────┴──────────┐
         │  Task Head A    │  │   Task Head B    │
         │  (Relevance     │  │   (Barangay NER) │
         │  Classification)│  │                  │
         │  Dropout→Linear │  │  Dropout→Linear  │
         │  768 → 2        │  │  768 → num_tags  │
         └────────┬────────┘  └──────┬──────────┘
                  │                   │
           cls_logits [B,2]     ner_logits [B,T,num_tags]
"""

from typing import Dict, Optional, Tuple

import torch
import torch.nn as nn
from transformers import XLMRobertaModel

from config import MTLConfig


class MultiTaskXLMRoBERTa(nn.Module):
    """
    Multi-Task Learning model with shared XLM-RoBERTa encoder and two
    task-specific heads for flood monitoring.

    Task Head A — Relevance Classification:
        Binary classification (flood-related vs. non-flood-related).
        Operates on the [CLS] token's hidden state.
        Outputs logits and softmax probabilities.

    Task Head B — Barangay-Level NER:
        Token classification with IOB tags (O, B-LOC, I-LOC).
        Operates on ALL token hidden states.
        Outputs per-token logits.

    Args:
        config: MTLConfig instance with architecture and label settings.
    """

    def __init__(self, config: MTLConfig):
        super().__init__()
        self.config = config

        # ── Shared Encoder ──────────────────────────────────────────────
        # Load pretrained XLM-RoBERTa base (12 layers, 768 hidden, 12 heads)
        # This encoder processes multilingual, code-switched text and
        # produces contextual representations shared by both task heads.
        self.encoder = XLMRobertaModel.from_pretrained(config.model_name)

        # ── Task Head A: Relevance Classification ───────────────────────
        # Architecture: Dropout → Linear(768, 2)
        # Input: [CLS] token hidden state (first token, index 0)
        # Output: 2-class logits (non-flood, flood)
        self.cls_dropout = nn.Dropout(config.dropout_rate)
        self.cls_head = nn.Linear(config.hidden_size, config.num_classification_labels)

        # ── Task Head B: Barangay-Level NER ─────────────────────────────
        # Architecture: Dropout → Linear(768, num_ner_labels)
        # Input: All token hidden states [batch, seq_len, 768]
        # Output: Per-token logits [batch, seq_len, num_ner_labels]
        self.ner_dropout = nn.Dropout(config.dropout_rate)
        self.ner_head = nn.Linear(config.hidden_size, config.num_ner_labels)

        # ── Loss Functions ──────────────────────────────────────────────
        # Classification: Standard Cross-Entropy for 2-class softmax
        self.cls_loss_fn = nn.CrossEntropyLoss()

        # NER: Cross-Entropy with ignore_index=-100 to mask special tokens
        # and subword continuations (set to -100 during label alignment)
        self.ner_loss_fn = nn.CrossEntropyLoss(ignore_index=-100)

    def forward(
        self,
        input_ids: torch.Tensor,
        attention_mask: torch.Tensor,
    ) -> Dict[str, torch.Tensor]:
        """
        Forward pass through shared encoder and both task heads.

        This method is kept pure (no loss computation) so it can be used
        directly during inference without requiring labels.

        Args:
            input_ids:      Token IDs [batch_size, seq_len]
            attention_mask: Attention mask [batch_size, seq_len]

        Returns:
            Dictionary containing:
              - "cls_logits":  Classification logits [batch_size, 2]
              - "cls_probs":   Classification probabilities [batch_size, 2]
              - "ner_logits":  NER logits [batch_size, seq_len, num_ner_labels]
        """
        # ── Shared Encoder Pass ─────────────────────────────────────────
        # Returns last_hidden_state [B, T, 768] and pooler_output [B, 768]
        encoder_output = self.encoder(
            input_ids=input_ids,
            attention_mask=attention_mask,
        )
        # Full sequence hidden states: [batch_size, seq_len, hidden_size]
        sequence_output = encoder_output.last_hidden_state

        # ── Task Head A: Classification ─────────────────────────────────
        # Extract [CLS] token (index 0) representation for classification
        cls_hidden = sequence_output[:, 0, :]        # [B, 768]
        cls_hidden = self.cls_dropout(cls_hidden)     # [B, 768]
        cls_logits = self.cls_head(cls_hidden)        # [B, 2]
        cls_probs = torch.softmax(cls_logits, dim=-1) # [B, 2]

        # ── Task Head B: NER ────────────────────────────────────────────
        # Apply dropout and project all token hidden states to NER labels
        ner_hidden = self.ner_dropout(sequence_output) # [B, T, 768]
        ner_logits = self.ner_head(ner_hidden)         # [B, T, num_ner_labels]

        return {
            "cls_logits": cls_logits,
            "cls_probs": cls_probs,
            "ner_logits": ner_logits,
        }

    def compute_loss(
        self,
        outputs: Dict[str, torch.Tensor],
        classification_labels: torch.Tensor,
        ner_labels: torch.Tensor,
        alpha: Optional[float] = None,
        beta: Optional[float] = None,
    ) -> Dict[str, torch.Tensor]:
        """
        Compute the multi-task loss:
            L_total = alpha * L_classification + beta * L_ner

        Args:
            outputs:                Model forward() output dictionary.
            classification_labels:  Ground truth binary labels [batch_size].
            ner_labels:             Ground truth NER label IDs [batch_size, seq_len]
                                    with -100 for ignored positions.
            alpha:                  Weight for classification loss (default: config).
            beta:                   Weight for NER loss (default: config).

        Returns:
            Dictionary containing:
              - "total_loss":  Weighted sum of both task losses.
              - "cls_loss":    Classification loss (for logging).
              - "ner_loss":    NER loss (for logging).
        """
        alpha = alpha if alpha is not None else self.config.alpha
        beta = beta if beta is not None else self.config.beta

        # ── Classification Loss ─────────────────────────────────────────
        # Standard cross-entropy between cls_logits [B, 2] and labels [B]
        cls_loss = self.cls_loss_fn(
            outputs["cls_logits"],
            classification_labels,
        )

        # ── NER Loss ────────────────────────────────────────────────────
        # Reshape for cross-entropy: logits [B*T, C], labels [B*T]
        # ignore_index=-100 ensures only first-subword positions contribute
        ner_logits = outputs["ner_logits"]
        batch_size, seq_len, num_labels = ner_logits.shape
        ner_loss = self.ner_loss_fn(
            ner_logits.view(-1, num_labels),  # [B*T, C]
            ner_labels.view(-1),              # [B*T]
        )

        # ── Weighted Multi-Task Loss Weighted vales located at config line 53-54──
        total_loss = (alpha * cls_loss) + (beta * ner_loss)

        return {
            "total_loss": total_loss,
            "cls_loss": cls_loss,
            "ner_loss": ner_loss,
        }

    def get_encoder_parameters(self):
        """Return shared encoder parameters (for differential LR if needed)."""
        return self.encoder.parameters()

    def get_head_parameters(self):
        """Return task head parameters (for differential LR if needed)."""
        head_params = list(self.cls_head.parameters()) + \
                      list(self.ner_head.parameters())
        return head_params

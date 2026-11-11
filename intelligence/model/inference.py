"""
inference.py — Inference Pipeline for Flood Monitoring
=======================================================
Provides a structured inference pipeline that:
  1. Accepts raw social media posts
  2. Preprocesses and tokenizes them
  3. Runs the multi-task model
  4. Applies relevance gating (p_rel threshold)
  5. Reconstructs location entities from IOB predictions
  6. Returns structured JSON output per post

Output Schema:
  {
      "post_id": str,
      "raw_text": str,
      "is_flood_related": bool,
      "p_rel": float,
      "extracted_locations": list[str]
  }
"""

import uuid
import logging
from typing import Dict, List, Optional, Union

import torch
from transformers import XLMRobertaTokenizerFast

from config import MTLConfig
from model import MultiTaskXLMRoBERTa
from preprocessing import normalize_text
from utils import decode_iob_tags
from gazetteer import extract_hybrid_locations
from labeling import label_post

logger = logging.getLogger(__name__)


class FloodInferencePipeline:
    """
    End-to-end inference pipeline for flood monitoring posts.

    This pipeline:
      1. Normalizes raw social media text (handles, URLs, noise removal)
      2. Tokenizes with XLM-RoBERTa's SentencePiece tokenizer
      3. Runs both task heads in a single forward pass
      4. Gates NER extraction on the relevance probability:
         - If p_rel >= threshold → extract locations via IOB decoding
         - If p_rel < threshold  → skip NER, flag as non-pertinent
      5. Returns structured results matching the specified output schema

    Args:
        model:     Trained MultiTaskXLMRoBERTa instance.
        tokenizer: XLMRobertaTokenizerFast instance.
        config:    MTLConfig with inference settings (threshold, device).
    """

    def __init__(
        self,
        model: MultiTaskXLMRoBERTa,
        tokenizer: XLMRobertaTokenizerFast,
        config: MTLConfig,
    ):
        self.config = config
        self.tokenizer = tokenizer
        self.device = torch.device(
            config.device if torch.cuda.is_available() else "cpu"
        )

        # Move model to device and set to evaluation mode
        self.model = model.to(self.device)
        self.model.eval()

        logger.info(
            f"Inference pipeline initialized | "
            f"Device: {self.device} | "
            f"Threshold: {config.relevance_threshold}"
        )

    @classmethod
    def from_checkpoint(
        cls,
        checkpoint_path: str,
        config: Optional[MTLConfig] = None,
    ) -> "FloodInferencePipeline":
        """
        Factory method to load a pipeline from a saved checkpoint.

        Args:
            checkpoint_path: Path to the saved model checkpoint (.pt).
            config:          MTLConfig instance. If None, uses defaults.

        Returns:
            Initialized FloodInferencePipeline.
        """
        config = config or MTLConfig()

        # Load tokenizer
        tokenizer = XLMRobertaTokenizerFast.from_pretrained(config.model_name)

        # Initialize model architecture
        model = MultiTaskXLMRoBERTa(config)

        # Load trained weights (load to CPU first to avoid VRAM exhaustion from optimizer states)
        checkpoint = torch.load(checkpoint_path, map_location="cpu")
        model.load_state_dict(checkpoint["model_state_dict"])
        del checkpoint
        if torch.cuda.is_available():
            torch.cuda.empty_cache()

        logger.info(f"Model loaded from {checkpoint_path}")

        return cls(model=model, tokenizer=tokenizer, config=config)

    @torch.no_grad()
    def predict(
        self,
        raw_text: str,
        post_id: Optional[str] = None,
    ) -> Dict[str, Union[str, bool, float, List[str]]]:
        """
        Run inference on a single social media post.

        Processing Pipeline:
          1. Normalize the raw text
          2. Tokenize with SentencePiece
          3. Forward pass through both task heads
          4. Extract classification prediction and probability
          5. If flood-related (p_rel >= threshold):
             - Decode IOB tags → location entity strings
          6. If not flood-related (p_rel < threshold):
             - Return empty locations list (noise filtering)

        Args:
            raw_text: Raw social media post string.
            post_id:  Optional identifier. If None, auto-generates a UUID.

        Returns:
            Structured dictionary matching the research output schema:
            {
                "post_id": str,
                "raw_text": str,
                "is_flood_related": bool,
                "p_rel": float,
                "extracted_locations": list[str]
            }
        """
        post_id = post_id or str(uuid.uuid4())[:8]

        # ── Step 1: Text Normalization ──────────────────────────────────
        normalized_text = normalize_text(raw_text)
        if not normalized_text:
            return self._empty_result(post_id, raw_text)

        # ── Step 2: Tokenization ────────────────────────────────────────
        encoding = self.tokenizer(
            normalized_text,
            max_length=self.config.max_seq_length,
            padding="max_length",
            truncation=True,
            return_tensors="pt",
        )

        input_ids = encoding["input_ids"].to(self.device)
        attention_mask = encoding["attention_mask"].to(self.device)

        # ── Step 3: Forward Pass ────────────────────────────────────────
        outputs = self.model(
            input_ids=input_ids,
            attention_mask=attention_mask,
        )

        # ── Step 4: Classification Result & Contextual Disambiguation ───
        # cls_probs shape: [1, 2] — index 1 is the "flood-related" probability
        neural_p_rel = outputs["cls_probs"][0, 1].item()

        # Contextual verification gate (disambiguates political campaigns, lookalikes, idioms, and non-emergency chatter)
        label_res = label_post(normalized_text)
        EXPLICIT_DISQUALIFICATIONS = {
            "political_campaign_speech",
            "figurative_or_advertisement",
            "figurative_expression_or_commercial",
            "lookalike_nonflood_word",
            "historical_or_hypothetical_tips",
        }

        has_any_signal = len(label_res.matched_flood_words) > 0 or len(label_res.matched_context_cues) > 0
        is_isolated_flood = (label_res.reason == "isolated_flood_mention_no_urgency_cues")

        if is_isolated_flood:
            # Genuine flood keyword detected without high-urgency context cues (water depth, rescue, evacuation).
            # Interpreted as early-stage flood detection / watch post for Human-in-the-Loop validation.
            is_flood_related = True
            p_rel = neural_p_rel
            discard_reason = "isolated_flood_mention_no_urgency_cues"
        elif label_res.label == 0 and (label_res.reason in EXPLICIT_DISQUALIFICATIONS or not has_any_signal):
            # Explicitly disqualified by situational context or total absence of flood/urgency signals
            is_flood_related = False
            p_rel = min(neural_p_rel, round(1.0 - label_res.confidence, 4))
            discard_reason = label_res.reason
        elif label_res.label == 1:
            # Positive contextual corroboration
            is_flood_related = True
            p_rel = max(neural_p_rel, round(label_res.confidence, 4))
            discard_reason = None
        else:
            # Neutral context with some signal: rely on neural classification head
            is_flood_related = neural_p_rel >= self.config.relevance_threshold
            p_rel = neural_p_rel
            discard_reason = None if is_flood_related else "neural_relevance_below_threshold"

        # ── Step 5: Relevance Gating for NER ────────────────────────────
        # Extract locations for confirmed floods and early flood mentions (so human review has locations)
        extracted_locations = []

        if is_flood_related or is_isolated_flood:
            extracted_locations = self._extract_locations(
                input_ids=input_ids[0],
                attention_mask=attention_mask[0],
                ner_logits=outputs["ner_logits"][0],
                raw_text=raw_text,
            )

        # ── Step 6: Actionability Gating ────────────────────────────────
        # A post is only actionable if verified as a real flood emergency
        # AND contains at least one localized spatial entity for K-Means++
        if is_isolated_flood:
            verification_status = "FLOOD_MENTION_NO_URGENCY"
            is_actionable = False
            discard_reason = "isolated_flood_mention_no_urgency_cues"
        elif is_flood_related and len(extracted_locations) > 0:
            verification_status = "VERIFIED_ACTIONABLE"
            is_actionable = True
            discard_reason = None
        elif is_flood_related and len(extracted_locations) == 0:
            verification_status = "DISCARDED_UNLOCALIZED"
            is_actionable = False
            discard_reason = "missing_davao_location"
        else:
            verification_status = "DISCARDED_NOT_FLOOD"
            is_actionable = False
            discard_reason = discard_reason or "not_flood_related"

        return {
            "post_id": post_id,
            "raw_text": raw_text,
            "is_flood_related": is_flood_related,
            "p_rel": round(p_rel, 4),
            "extracted_locations": extracted_locations,
            "verification_status": verification_status,
            "is_actionable": is_actionable,
            "discard_reason": discard_reason,
        }

    @torch.no_grad()
    def batch_predict(
        self,
        raw_texts: List[str],
        post_ids: Optional[List[str]] = None,
        filter_actionable: bool = False,
    ) -> List[Dict[str, Union[str, bool, float, List[str]]]]:
        """
        Run inference on a batch of social media posts.

        Processes posts individually (simple batching) for correctness
        in entity reconstruction.

        Args:
            raw_texts:         List of raw social media post strings.
            post_ids:          Optional list of identifiers (parallel to raw_texts).
            filter_actionable: If True, only returns posts that are verified flood
                               emergencies with at least one extracted location.

        Returns:
            List of structured result dictionaries.
        """
        if post_ids is None:
            post_ids = [f"post_{i:04d}" for i in range(len(raw_texts))]

        results = []
        for text, pid in zip(raw_texts, post_ids):
            result = self.predict(raw_text=text, post_id=pid)
            if filter_actionable and not result["is_actionable"]:
                continue
            results.append(result)

        return results

    def _extract_locations(
        self,
        input_ids: torch.Tensor,
        attention_mask: torch.Tensor,
        ner_logits: torch.Tensor,
        raw_text: str = "",
    ) -> List[str]:
        """
        Extract location entities from NER head predictions with gazetteer normalization.

        Process:
          1. Get predicted tag IDs via argmax on NER logits
          2. Convert input_ids back to token strings
          3. Build aligned tag_id list (mark padding as -100)
          4. Decode IOB tags to extract entity spans
          5. Merge subword tokens back into readable location strings
          6. Apply gazetteer matching and resolution to repair cutoffs and canonicalize names

        Args:
            input_ids:      Token IDs for a single sample [seq_len].
            attention_mask: Attention mask for a single sample [seq_len].
            ner_logits:     NER logits for a single sample [seq_len, num_labels].
            raw_text:       Original post text for gazetteer context.

        Returns:
            List of extracted location strings (e.g., ["Barangay Bucana"]).
        """
        # Predicted NER tag IDs
        pred_tag_ids = torch.argmax(ner_logits, dim=-1).cpu().tolist()

        # Convert input IDs back to subword tokens
        tokens = self.tokenizer.convert_ids_to_tokens(
            input_ids.cpu().tolist()
        )

        # Build aligned tag list: mark padding/special tokens as -100
        mask = attention_mask.cpu().tolist()
        aligned_tags = []
        for i, (tag_id, m) in enumerate(zip(pred_tag_ids, mask)):
            if m == 0:
                # Padding token → ignore
                aligned_tags.append(-100)
            elif tokens[i] in self.tokenizer.all_special_tokens:
                # Special token (<s>, </s>, <pad>) → ignore
                aligned_tags.append(-100)
            else:
                aligned_tags.append(tag_id)

        # Decode IOB tags to entity spans
        entities = decode_iob_tags(
            tokens=tokens,
            tag_ids=aligned_tags,
            id2label=self.config.ner_id2label,
            ignore_index=-100,
        )

        # Deduplicate model entities while preserving order
        seen = set()
        unique_entities = []
        for entity in entities:
            entity_clean = entity.strip()
            if entity_clean and entity_clean.lower() not in seen:
                seen.add(entity_clean.lower())
                unique_entities.append(entity_clean)

        # Apply Gazetteer-guided hybrid extraction & resolution
        final_locations = extract_hybrid_locations(
            model_locations=unique_entities,
            raw_text=raw_text,
        )

        return final_locations

    @staticmethod
    def _empty_result(
        post_id: str,
        raw_text: str,
    ) -> Dict[str, Union[str, bool, float, List[str]]]:
        """
        Return a default result for empty/unparseable input.

        Args:
            post_id:  Post identifier.
            raw_text: Original raw text.

        Returns:
            Result dict with is_flood_related=False and empty locations.
        """
        return {
            "post_id": post_id,
            "raw_text": raw_text,
            "is_flood_related": False,
            "p_rel": 0.0,
            "extracted_locations": [],
            "verification_status": "DISCARDED_NOT_FLOOD",
            "is_actionable": False,
            "discard_reason": "empty_or_unparseable_text",
        }

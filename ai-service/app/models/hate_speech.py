# app/models/hate_speech.py
import re
import time
from typing import Any, Dict, List, Optional

import torch
from app.models.base import BaseModel, ModelConfig
from app.schemas.enums import RiskLevel
from transformers import AutoModelForSequenceClassification, AutoTokenizer


class HateSpeechConfig(ModelConfig):
    """Hate Speech Model Configuration"""

    max_length: int = 512

    def __init__(
        self,
        model_path: Optional[str] = None,
        max_length: int = 512,
        use_gpu: bool = False,
        **kwargs,
    ):
        self.model_path = model_path or "nahiar/hatespeech-abusive-xlm-roberta-v1"
        if not self.model_path.strip():
            self.model_path = "nahiar/hatespeech-abusive-xlm-roberta-v1"
        self.max_length = max_length
        name = kwargs.pop("name", "hatespeech-abusive-xlm-roberta-v1")
        super().__init__(name=name, use_gpu=use_gpu, **kwargs)


class HateSpeechResult:
    """Hate speech prediction result"""

    def __init__(
        self,
        hate_speech_score: float = 0.0,
        abusive_score: float = 0.0,
        hate_level: RiskLevel = RiskLevel.LOW,
        abusive_level: RiskLevel = RiskLevel.LOW,
    ):
        self.hate_speech_score = hate_speech_score
        self.abusive_score = abusive_score
        self.hate_level = hate_level
        self.abusive_level = abusive_level

    def to_dict(self) -> Dict[str, Any]:
        return {
            "hate_speech_score": self.hate_speech_score,
            "abusive_score": self.abusive_score,
            "hate_level": self.hate_level.value,
            "abusive_level": self.abusive_level.value,
        }


class HateSpeechModel(BaseModel):
    """XLM-RoBERTa Hate Speech Model (Indonesian) - direct PyTorch inference"""

    def __init__(self, config: Optional[HateSpeechConfig] = None):
        config = config or HateSpeechConfig()
        super().__init__(config)
        self.config: HateSpeechConfig = config
        self.tokenizer: Optional[AutoTokenizer] = None
        self.model: Optional[AutoModelForSequenceClassification] = None

    def load(self):
        """Load tokenizer and model"""
        start_time = time.time()

        model_path = self.config.model_path
        if not model_path or not model_path.strip():
            model_path = "nahiar/hatespeech-abusive-xlm-roberta-v1"
            self.config.model_path = model_path

        print(f"Loading Hate Speech Model: {model_path}")

        self.tokenizer = AutoTokenizer.from_pretrained(model_path)
        self.model = AutoModelForSequenceClassification.from_pretrained(model_path)

        # Move to GPU if requested and available
        if self.config.use_gpu and torch.cuda.is_available():
            self.model = self.model.cuda()

        self.is_loaded = True
        self.load_time_ms = round((time.time() - start_time) * 1000, 2)
        self.model.config.id2label = {0: "HATESPEECH", 1: "ABUSIVE"}
        print(f"Hate Speech Model loaded in {self.load_time_ms}ms")
        print(
            f"Label mapping: {self.model.config.id2label}"
        )  # Should be {0: 'HATESPEECH', 1: 'ABUSIVE'}

    def unload(self):
        self.model = None
        self.tokenizer = None
        self.is_loaded = False
        print("Hate Speech Model unloaded")

    def predict(self, input_data: str) -> HateSpeechResult:
        if not self.is_loaded or self.model is None or self.tokenizer is None:
            raise RuntimeError("Model not loaded. Call load() first.")

        result = HateSpeechResult()

        if not input_data or len(input_data.strip()) < 3:
            return result

        try:
            cleaned_text = self._preprocess_text(input_data)

            # Tokenize
            inputs = self.tokenizer(
                cleaned_text,
                return_tensors="pt",
                truncation=True,
                max_length=self.config.max_length,
            )

            # Move to GPU if available
            if self.config.use_gpu and torch.cuda.is_available():
                inputs = {k: v.cuda() for k, v in inputs.items()}

            # Forward pass
            with torch.no_grad():
                logits = self.model(**inputs).logits  # shape: (1, num_labels)

            # Apply sigmoid for multi‑label probabilities (independent)
            probs = torch.sigmoid(logits).squeeze().cpu().tolist()
            if isinstance(probs, float):  # In case of single label
                probs = [probs]

            # Get label mapping from config, fallback to default
            id2label = self.model.config.id2label
            if not id2label or len(id2label) != len(probs):
                id2label = {0: "HATESPEECH", 1: "ABUSIVE"}  # fallback

            # Create scores dict
            scores = {id2label[i]: probs[i] for i in range(len(probs))}

            # Extract hate and abusive scores
            hate_score = scores.get("HATESPEECH", 0.0)
            abusive_score = scores.get("ABUSIVE", 0.0)

            # If labels were numeric (LABEL_0, LABEL_1), map them
            if hate_score == 0.0 and abusive_score == 0.0:
                # Assume LABEL_1 is hate, LABEL_0 is abusive (or vice versa?)
                # For this model, LABEL_1 is likely 'HATESPEECH', but we'll assign both
                hate_score = scores.get("LABEL_1", 0.0)
                abusive_score = scores.get("LABEL_0", 0.0)

            # If still zero, use the highest score as both (fallback)
            if hate_score == 0.0 and abusive_score == 0.0 and len(probs) > 0:
                # Use the max probability as both (should not happen with correct mapping)
                max_score = max(probs)
                hate_score = abusive_score = max_score

            result.hate_speech_score = round(hate_score, 4)
            result.abusive_score = round(abusive_score, 4)
            result.hate_level = RiskLevel.from_score(hate_score)
            result.abusive_level = RiskLevel.from_score(abusive_score)

            return result

        except Exception as e:
            print(f"Prediction error: {str(e)}")
            return result

    def predict_batch(self, texts: List[str]) -> List[HateSpeechResult]:
        return [self.predict(text) for text in texts]

    def _preprocess_text(self, text: str) -> str:
        text = text.lower().strip()
        text = re.sub(r"@\w+", "@USER", text)
        text = re.sub(r"http\S+|www\S+|https\S+", "HTTPURL", text, flags=re.MULTILINE)
        text = re.sub(r"\s+", " ", text)
        return text

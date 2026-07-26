# app/models/context.py
import json
import os
import re
import time
from dataclasses import dataclass
from typing import Any, Dict, List, Optional

import ollama
from app.models.base import BaseModel, ModelConfig
from app.schemas.enums import RiskLevel
from dotenv import load_dotenv

# Load environment variables
load_dotenv()


@dataclass
class ContextScores:
    """Scores for context analysis"""

    hate: float = 0.0
    abusive: float = 0.0


@dataclass
class ContextLevels:
    """Risk levels for context analysis"""

    hate: RiskLevel = RiskLevel.LOW
    abusive: RiskLevel = RiskLevel.LOW


@dataclass
class ContextInput:
    """Input for context analysis"""

    text: str = ""
    scores: ContextScores = ContextScores()
    levels: ContextLevels = ContextLevels()


class ContextConfig(ModelConfig):
    """Context Analysis Model Configuration"""

    def __init__(
        self,
        model_name: Optional[str] = None,
        temperature: Optional[float] = None,
        max_tokens: Optional[int] = None,
        timeout: Optional[int] = None,
        use_gpu: bool = False,
        language: str = "id",
        **kwargs,
    ):
        # Load from env or use defaults
        self.model_name = model_name or os.getenv("OLLAMA_MODEL", "sailor2:1b")
        self.temperature = (
            temperature
            if temperature is not None
            else float(os.getenv("OLLAMA_TEMPERATURE", "0.1"))
        )
        self.max_tokens = (
            max_tokens
            if max_tokens is not None
            else int(os.getenv("OLLAMA_MAX_TOKENS", "300"))
        )
        self.timeout = (
            timeout if timeout is not None else int(os.getenv("OLLAMA_TIMEOUT", "30"))
        )
        self.language = language or os.getenv("CONTEXT_LANGUAGE", "id")

        # Set name from model_name
        name = kwargs.pop("name", self.model_name)

        super().__init__(name=name, use_gpu=use_gpu, **kwargs)


class ContextResult:
    """Context analysis result - simple version"""

    def __init__(self):
        self.category: str = ""
        self.explanation: str = ""
        self.processing_time_ms: float = 0.0
        self.raw_response: str = ""


class ContextModel(BaseModel):
    """Ollama LLM for context analysis - Indonesian"""

    def __init__(self, config: Optional[ContextConfig] = None):
        config = config or ContextConfig()
        super().__init__(config)
        self.config: ContextConfig = config
        self.is_available = False
        self.language = self.config.language

    def load(self):
        """Check availability of Ollama"""
        start_time = time.time()

        try:
            # Check if ollama is available
            ollama.list()
            self.is_available = True

            # Check if model exists
            try:
                ollama.show(self.config.model_name)
            except Exception:
                print(f"Peringatan: Model '{self.config.model_name}' tidak ditemukan")
                print(f"Pull model dengan: ollama pull {self.config.model_name}")
                self.is_available = False

            self.is_loaded = True
            self.load_time_ms = round((time.time() - start_time) * 1000, 2)
            print(
                f"Model Konteks dimuat: {self.config.model_name} dalam {self.load_time_ms}ms"
            )
            print(f"Ollama tersedia: {self.is_available}")

        except Exception as e:
            print(f"Peringatan: Ollama tidak tersedia - {str(e)}")
            self.is_loaded = True
            self.load_time_ms = round((time.time() - start_time) * 1000, 2)
            self.is_available = False

    def unload(self):
        """Unload (nothing to unload for API-based model)"""
        self.is_loaded = False
        print(f"Model Konteks dibongkar: {self.config.model_name}")

    def predict(self, input_data: ContextInput) -> ContextResult:
        """
        Analyze context using Ollama LLM - Indonesian Language

        Args:
            input_data: ContextInput object containing text and scores

        Returns:
            ContextResult with category and explanation in Indonesian
        """
        result = ContextResult()
        start_time = time.time()

        if not self.is_available:
            result.category = "tidak_tersedia"
            result.explanation = "Ollama tidak tersedia"
            result.processing_time_ms = round((time.time() - start_time) * 1000, 2)
            return result

        if not input_data.text or len(input_data.text.strip()) < 3:
            result.category = "netral"
            result.explanation = "Tidak ada teks untuk dianalisis"
            result.processing_time_ms = round((time.time() - start_time) * 1000, 2)
            return result

        try:
            # Build prompt in Indonesian
            prompt = self._build_prompt(
                input_data.text,
                input_data.scores.hate,
                input_data.scores.abusive,
                input_data.levels.hate.value,
                input_data.levels.abusive.value,
            )

            # Call Ollama using the library
            response = self._call_ollama(prompt)

            result.raw_response = response
            result.processing_time_ms = round((time.time() - start_time) * 1000, 2)

            # Parse response
            parsed = self._parse_response(response)
            result.category = parsed.get("kategori", "tidak_diketahui")
            result.explanation = parsed.get(
                "penjelasan", "Tidak ada penjelasan yang diberikan"
            )

            return result

        except Exception as e:
            result.category = "error"
            result.explanation = f"Analisis konteks gagal: {str(e)}"
            result.processing_time_ms = round((time.time() - start_time) * 1000, 2)
            return result

    def _build_prompt(
        self,
        text: str,
        hate_score: float,
        abusive_score: float,
        hate_level: str,
        abusive_level: str,
    ) -> str:
        """
        Build prompt in Indonesian for Ollama
        """
        # Determine primary concern
        if hate_score >= abusive_score:
            primary = "UCAPAN KEBENCIAN"
            level = hate_level
        else:
            primary = "KONTEN ABUSIF"
            level = abusive_level

        # Map risk level to Indonesian
        level_map = {
            "LOW": "RENDAH",
            "MEDIUM": "SEDANG",
            "HIGH": "TINGGI",
            "CRITICAL": "KRITIS",
        }
        level_id = level_map.get(level, level)

        prompt = f"""Analisis konten berikut dan berikan konteksnya:

Teks: "{text}"

Skor:
- Skor Ujaran Kebencian: {hate_score:.2f}
- Skor Abusif: {abusive_score:.2f}
- Isu Utama: {primary}
- Tingkat Risiko: {level_id}

Berikan respons JSON dengan 2 field ini saja:
{{
    "kategori": "kategori (misal: rasial, agama, gender, politik, pelecehan, hinaan, ancaman, dll.)",
    "penjelasan": "penjelasan singkat mengapa konten ini mengkhawatirkan"
}}

Respons (JSON only):"""

        return prompt

    def _call_ollama(self, prompt: str) -> str:
        """Call Ollama using the Python library"""
        try:
            response = ollama.generate(
                model=self.config.model_name,
                prompt=prompt,
                options={
                    "temperature": self.config.temperature,
                    "num_predict": self.config.max_tokens,
                },
                stream=False,
            )

            return response.get("response", "")

        except Exception as e:
            return f'{{"kategori": "error", "penjelasan": "Error: {str(e)}"}}'

    def _parse_response(self, response: str) -> Dict[str, Any]:
        """
        Parse JSON response from Ollama
        """
        default = {
            "kategori": "tidak_diketahui",
            "penjelasan": "Tidak dapat memparse respons",
        }

        if not response or not response.strip():
            return default

        try:
            # Find JSON-like content
            json_pattern = r"\{[^{}]*\}"
            match = re.search(json_pattern, response)

            if not match:
                return default

            parsed = json.loads(match.group())

            return {
                "kategori": str(
                    parsed.get("kategori", parsed.get("category", "tidak_diketahui"))
                ).strip(),
                "penjelasan": str(
                    parsed.get(
                        "penjelasan", parsed.get("explanation", "Tidak ada penjelasan")
                    )
                ).strip(),
            }

        except (json.JSONDecodeError, ValueError, AttributeError):
            return default

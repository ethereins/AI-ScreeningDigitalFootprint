import io
import re
import time
from pathlib import Path
from typing import Any, Dict, List, Optional

import numpy as np
import requests
from app.models.base import BaseModel, ModelConfig
from paddleocr import PaddleOCR
from PIL import Image
from typing_extensions import Union


def load_image_bytes(source: Union[str, Path]) -> bytes:
    """
    Load image data from a URL or local file path.
    Returns the image content as bytes.
    Raises ValueError if the source is invalid or cannot be retrieved.
    """
    source_str = str(source)

    # URL (starts with http:// or https://)
    if source_str.startswith(("http://", "https://")):
        try:
            response = requests.get(source_str, timeout=30)
            response.raise_for_status()  # Raise HTTPError for bad responses
            # Check content type to ensure it's an image (optional)
            content_type = response.headers.get("Content-Type", "")
            if not content_type.startswith("image/"):
                raise ValueError(
                    f"URL does not point to an image (Content-Type: {content_type})"
                )
            return response.content
        except requests.RequestException as e:
            raise ValueError(f"Failed to download image from {source_str}: {e}")

    # Otherwise treat as a local file path
    path = Path(source_str)
    if not path.exists():
        raise FileNotFoundError(f"Image file not found: {path}")
    if not path.is_file():
        raise ValueError(f"Path is not a file: {path}")
    try:
        with open(path, "rb") as f:
            return f.read()
    except OSError as e:
        raise ValueError(f"Failed to read image file {path}: {e}")


class OCRConfig(ModelConfig):
    """OCR Model Configuration"""

    lang: str = "id"
    use_angle_cls: bool = True
    det_db_thresh: float = 0.3
    det_db_box_thresh: float = 0.5
    det_db_unclip_ratio: float = 1.8
    max_batch_size: int = 1
    max_image_width: int = 1280
    use_dilation: bool = False
    show_log: bool = False

    # New parameters for PaddleOCR
    text_det_limit_side_len: int = 960
    text_det_limit_type: str = "max"
    text_det_thresh: float = 0.3
    text_det_box_thresh: float = 0.5
    text_det_unclip_ratio: float = 1.8
    text_rec_score_thresh: float = 0.5
    return_word_box: bool = True
    text_rec_input_shape: str = "3, 48, 320"
    ocr_version: str = "PP-OCRv4"


# Will be fed to hate speech model
class OCRResult:
    """OCR prediction result - simplified to string only"""

    def __init__(self):
        self.text: str = ""  # Extracted text as string
        self.confidence: float = 0.0  # Average confidence
        self.word_count: int = 0  # Number of words detected
        self.processing_time_ms: float = 0.0  # Processing time

    def __str__(self):
        return self.text

    def __repr__(self):
        return f"OCRResult(text='{self.text[:50]}...', confidence={self.confidence})"


class OCRModel(BaseModel):
    """PaddleOCR Model Implementation"""

    def __init__(self, config: Optional[OCRConfig] = None):
        config = config or OCRConfig(name="PaddleOCR")
        super().__init__(config)
        self.config: OCRConfig = config
        self.model = None

    def load(self):
        """Load PaddleOCR model"""
        start_time = time.time()

        try:
            self.model = PaddleOCR()

            self.is_loaded = True
            self.load_time_ms = round((time.time() - start_time) * 1000, 2)
            print(f"OCR Model loaded: {self.config.name} in {self.load_time_ms}ms")

        except Exception as e:
            raise RuntimeError(f"Failed to load OCR model: {str(e)}")

    def unload(self):
        """Unload the model"""
        self.model = None
        self.is_loaded = False

    def predict(self, input_data: str) -> OCRResult:
        """
        Extract text from image - returns clean text for hate model
        """
        if not self.is_loaded or self.model is None:
            raise RuntimeError(
                "OCR model not properly loaded. Call load() first or check if model initialization failed."
            )

        result = OCRResult()
        start_time = time.time()

        try:
            img_data = load_image_bytes(input_data)
            # Preprocess image
            img = self._preprocess_image(img_data)

            # Run OCR - PaddleOCR expects numpy array directly
            # The model was initialized with ocr() so we can call it directly
            ocr_result = self.model.ocr(img, cls=True)

            result.processing_time_ms = round((time.time() - start_time) * 1000, 2)

            if not ocr_result or not ocr_result[0]:
                return result

            # Extract text only - clean and ready for hate model
            text_parts = []
            total_confidence = 0

            for line in ocr_result[0]:
                if line:
                    text = line[1][0]
                    confidence = line[1][1]

                    if text.strip() and confidence > 0.3:
                        text_parts.append(text)
                        total_confidence += confidence

            # Combine all text into clean string
            full_text = " ".join(text_parts)

            result.text = full_text
            result.word_count = len(full_text.split())
            result.confidence = (
                round(total_confidence / len(text_parts), 4) if text_parts else 0
            )

            return result

        except Exception as e:
            result.processing_time_ms = round((time.time() - start_time) * 1000, 2)
            print(f"OCR prediction error: {str(e)}")
            return result

    def _preprocess_image(self, image_bytes: bytes) -> np.ndarray:
        """Preprocess image"""
        img = Image.open(io.BytesIO(image_bytes))

        if max(img.size) > self.config.max_image_width:
            ratio = self.config.max_image_width / max(img.size)
            new_width = int(img.width * ratio)
            new_height = int(img.height * ratio)
            img = img.resize((new_width, new_height), Image.Resampling.LANCZOS)

        return np.array(img)

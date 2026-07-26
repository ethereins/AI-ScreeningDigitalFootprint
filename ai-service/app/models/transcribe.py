import glob
import os
import subprocess
import tempfile
import time
from typing import Any, Optional

import requests
from faster_whisper import WhisperModel as FasterWhisper

from .base import BaseModel, ModelConfig


def convert_mp4_url_to_wav(url, output_wav_filename="output.wav") -> str | None:
    """
    Downloads an MP4 and extracts audio as 16kHz MONO WAV.
    WAV is the MOST reliable format for faster-whisper.
    """
    temp_fd, temp_mp4 = tempfile.mkstemp(suffix=".mp4")
    os.close(temp_fd)

    try:
        print(f"Downloading MP4 from {url}...")
        response = requests.get(url, stream=True, timeout=30)
        response.raise_for_status()
        with open(temp_mp4, "wb") as f:
            for chunk in response.iter_content(chunk_size=8192):
                if chunk:
                    f.write(chunk)
        print("Download complete.")

        # Extract audio as 16kHz Mono WAV (Uncompressed PCM)
        print("Converting to 16kHz Mono WAV for Whisper...")
        cmd = [
            "ffmpeg",
            "-i", temp_mp4,
            "-vn",           # No video
            "-ac", "1",      # Mono
            "-ar", "16000",  # 16kHz
            "-c:a", "pcm_s16le",  # <-- WAV codec (uncompressed)
            "-y",            # Overwrite
            output_wav_filename,
        ]
        result = subprocess.run(cmd, capture_output=True, text=True)
        if result.returncode != 0:
            print(f"FFmpeg conversion failed:\n{result.stderr}")
            return None

        # Validate the WAV file is not empty
        if os.path.getsize(output_wav_filename) < 1000:
            print("WAV file is too small (likely corrupted or silent).")
            return None

        print(f"Successfully saved WAV: {output_wav_filename}")
        return output_wav_filename

    except Exception as e:
        print(f"Error: {e}")
        return None
    finally:
        if os.path.exists(temp_mp4):
            os.remove(temp_mp4)
            print("Temporary MP4 cleaned up.")


class TranscriptionResult:
    """Response schema for transcription results"""

    def __init__(self, text: str, language: str, language_probability: float):
        self.text = text
        self.language = language
        self.language_probability = language_probability

    text: str
    language: str
    language_probability: float


class WhisperModelWrapper(BaseModel):
    """
    Wrapper for faster-whisper that conforms to the BaseModel interface.
    """

    def __init__(
        self,
        config: ModelConfig,
        model_size: str = "base",
        device: str = "cpu",
        compute_type: str = "int8",
    ):
        super().__init__(config)
        self.model_size = model_size
        self.device = device
        self.compute_type = compute_type
        self._model: Optional[FasterWhisper] = None  # explicit type hint

    def load(self):
        """Load the whisper model."""
        start = time.perf_counter()
        self._model = FasterWhisper(
            self.model_size, device=self.device, compute_type=self.compute_type
        )
        self.is_loaded = True
        self.load_time_ms = int((time.perf_counter() - start) * 1000)

    def unload(self):
        """Unload the model (release resources)."""
        self._model = None
        self.is_loaded = False

    def predict(self, input_data: Any) -> TranscriptionResult:
        """
        Transcribe audio from the given file path.
        input_data: path to audio file (str)
        Returns a dict with 'text', 'language', 'language_probability'.
        """
        # Explicit check to help type checker narrow the type
        if self._model is None:
            raise RuntimeError("Model not loaded. Call load() first.")
        if not input_data:
            print("Input data is empty!")
            return TranscriptionResult(text="", language="", language_probability=0.0)
        try:
            segments, info = self._model.transcribe(input_data, beam_size=5)
            text = " ".join([segment.text for segment in segments])
            print(
                f"Transcribed Text: {text}\nDetected Language: {info.language}\nLanguage Probability: {info.language_probability}\n"
            )
            return TranscriptionResult(
                text=text,
                language=info.language,
                language_probability=info.language_probability,
            )
        except Exception as e:
            print(f"Error during transcription: {e}")
            return TranscriptionResult(text="", language="", language_probability=0.0)

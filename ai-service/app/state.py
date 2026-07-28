from app.models.base import ModelConfig
from app.models.context_analysis import ContextConfig, ContextModel
from app.models.hate_speech import HateSpeechConfig, HateSpeechModel
from app.models.ocr import OCRConfig, OCRModel
from app.models.transcribe import WhisperModelWrapper
from fastapi import Request


class AppState:
    def __init__(
        self,
    ):

        ocr_config = OCRConfig()
        ocr_model = OCRModel(ocr_config)
        ocr_model.load()

        hate_speech_config = HateSpeechConfig()
        hate_speech_model = HateSpeechModel(hate_speech_config)
        hate_speech_model.load()

        context_config = ContextConfig()
        context_model = ContextModel(context_config)
        context_model.load()
        transcribe_config = ModelConfig()
        transcribe_model = WhisperModelWrapper(transcribe_config)
        transcribe_model.load()

        self.ocr_model = ocr_model
        self.hate_speech_model = hate_speech_model
        self.context_model = context_model
        self.transcribe_model = transcribe_model


def get_app_state(request: Request) -> AppState:
    return request.app.state.app_state  # type: ignore[no-any-return]

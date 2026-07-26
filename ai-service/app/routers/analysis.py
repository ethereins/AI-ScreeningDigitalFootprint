import os
import uuid
from datetime import datetime
from typing import List, Optional

from app.models.context_analysis import ContextInput, ContextLevels, ContextScores
from app.models.transcribe import WhisperModelWrapper, convert_mp4_url_to_wav
from app.schemas.requests import (
    AnalyzePostRequest,
    AnalyzeRequest,
    AnalyzeTextRequest,
    OCRRequest,
    TranscribeRequest,
)
from app.schemas.responses import (
    AnalyzePostResponse,
    AnalyzeResponse,
    AnalyzeTextResponse,
    Context,
    OcrResponse,
    PostAnalysis,
    PostContent,
    PostContext,
    PostResponse,
    RiskLevel,
    ScoreDetail,
    Scores,
    SocialMediaResponse,
    SummaryStats,
    TranscribeResponse,
)
from app.state import get_app_state
from fastapi import APIRouter, Request

router = APIRouter()


@router.post("/analyze-persona")
async def analyze_persona(request: AnalyzeRequest):
    return AnalyzeResponse(
        name=request.name,
        overall_risk_score=85,
        overall_risk_level=RiskLevel.MEDIUM,
        aggregated_scores=ScoreDetail(
            toxicity=0.85, threat=0.75, insult=0.65, hate_speech=0.55
        ),
        summary=SummaryStats(total_posts_analyzed=10, high_risk_posts_count=2),
        social_media=[
            SocialMediaResponse(
                platform="Twitter",
                username="example_user",
                platform_risk_score=85,
                platform_risk_level=RiskLevel.MEDIUM,
                posts=[
                    PostResponse(
                        post_content=PostContent(
                            url="https://example.com/post/1",
                            text="This is a sample post",
                            date=datetime.now(),
                            image_url=None,
                            video_url=None,
                        ),
                        analysis=PostAnalysis(
                            risk_score=85,
                            risk_level=RiskLevel.MEDIUM,
                            scores=ScoreDetail(
                                toxicity=0.85,
                                threat=0.75,
                                insult=0.65,
                                hate_speech=0.55,
                            ),
                            context=PostContext(
                                category="General",
                                explanation="This post contains some potentially harmful content",
                            ),
                        ),
                    )
                ],
            )
        ],
    )

AUDIO_BASE_DIR = "app/audio"
@router.post("/analyze-post", response_model=AnalyzePostResponse)
async def analyze_post(payload: AnalyzePostRequest, req: Request):
    request_id = uuid.uuid4().hex
    audio_path =  os.path.join(AUDIO_BASE_DIR, f"temp_audio_{request_id}.wav")
    video_url = convert_mp4_url_to_wav(str(payload.video_url), audio_path)  # type: ignore
    app_state = get_app_state(req)
    transcription = app_state.transcribe_model.predict(video_url)
    ocr_result = app_state.ocr_model.predict(str(payload.image_url))
    hate_speech_score: List[float] = []
    abusive_score: List[float] = []

    combined_text = f"Transkripsi Audio: {transcription.text}\nTranskripsi Gambar: {ocr_result.text}\nTeks utama: {payload.text}"
    for text in [transcription.text, ocr_result.text, payload.text]:
        if text is None or text == "":
            continue
        result = app_state.hate_speech_model.predict(text)
        hate_speech_score.append(result.hate_speech_score)
        abusive_score.append(result.abusive_score)
    hate = sum(hate_speech_score) / len(hate_speech_score)
    abusive = sum(abusive_score) / len(abusive_score)
    scores = Scores(hate_speech=hate, abusive=abusive)
    average_score = (hate + abusive) / 2
    risk_level = RiskLevel.from_score(average_score)
    context_input = ContextInput(
        text=combined_text,
        scores=ContextScores(
            hate=hate,
            abusive=abusive,
        ),
        levels=ContextLevels(
            hate=RiskLevel.from_score(hate),
            abusive=RiskLevel.from_score(abusive),
        ),
    )

    context = app_state.context_model.predict(context_input)
    context_to_send = Context(
        category=context.category, explanation=context.explanation
    )

    # TODO: orchestrate OCR and transcription calls, then run combined classifier
    return AnalyzePostResponse(
        risk_score=average_score,
        risk_level=risk_level,
        scores=scores,
        context=context_to_send,
    )


@router.post("/analyze-text", response_model=AnalyzeTextResponse)
async def analyze_text(payload: AnalyzeTextRequest):
    # TODO: run text classifier only
    return AnalyzeTextResponse(
        risk_score=10.0,
        risk_level=RiskLevel.LOW,
        scores=Scores(toxicity=0.01),
        context=Context(category="safe"),
    )


@router.post("/ocr", response_model=OcrResponse)
async def ocr_image(payload: OCRRequest):
    # TODO: download image, run OCR (e.g., Tesseract, EasyOCR)
    return OcrResponse(text="Extracted text from image")


@router.post("/transcribe", response_model=TranscribeResponse)
async def transcribe_video(payload: TranscribeRequest):
    # TODO: use your Whisper model (as in your previous code)
    return TranscribeResponse(
        text="Transcribed text from audio",
        detected_language="en",
        language_probability=0.95,
    )

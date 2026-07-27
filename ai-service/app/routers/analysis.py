import os
import uuid
from datetime import datetime
from typing import List, Optional, Tuple

from app.models.context_analysis import ContextInput, ContextLevels, ContextScores
from app.models.hate_speech import HateSpeechResult
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


@router.post("/analyze-persona", response_model=AnalyzeResponse)
async def analyze_persona(request: AnalyzeRequest, req: Request):
    """
    Analyze a candidate's social media persona across multiple platforms.
    """
    persona = request.persona
    app_state = get_app_state(req)

    # Initialize variables for aggregated results
    total_posts = 0
    high_risk_posts = 0
    all_hate_scores = []
    all_abusive_scores = []
    all_threat_scores = []
    all_toxicity_scores = []
    social_media_responses = []

    # Process each social media account
    for social_media in persona.social_media:
        platform_posts = []

        # Process each post in the account
        for post in social_media.posts:
            total_posts += 1

            # Initialize post analysis
            post_analysis = PostAnalysis(
                risk_score=0,
                risk_level=RiskLevel.LOW,
                scores=ScoreDetail(
                    toxicity=0.0, threat=0.0, insult=0.0, hate_speech=0.0
                ),
                context=PostContext(
                    category="neutral", explanation="No analysis performed"
                ),
            )
            list_results: List[Tuple[HateSpeechResult, str]] = []

            # Process text content if available
            if post.text:
                try:
                    # Analyze text with hate speech model
                    hate_result = app_state.hate_speech_model.predict(post.text)

                    list_results.append((hate_result, post.text))

                except Exception as e:
                    print(f"Error analyzing post text: {str(e)}")

            # Process image if available
            if post.image_url:
                try:
                    # Run OCR on the image
                    ocr_result = app_state.ocr_model.predict(post.image_url)

                    # Analyze OCR text with hate speech model
                    if ocr_result.text:
                        ocr_hate_result = app_state.hate_speech_model.predict(
                            ocr_result.text
                        )

                        list_results.append((ocr_hate_result, ocr_result.text))

                except Exception as e:
                    print(f"Error analyzing image: {str(e)}")

            # Process video if available
            if post.video_url:
                try:
                    # Create temporary file for audio
                    request_id = uuid.uuid4().hex
                    audio_path = os.path.join(
                        AUDIO_BASE_DIR, f"temp_audio_{request_id}.wav"
                    )

                    # Convert video to WAV
                    video_path = convert_mp4_url_to_wav(post.video_url, audio_path)

                    if video_path:
                        # Transcribe audio
                        transcription = app_state.transcribe_model.predict(video_path)

                        # Analyze transcription with hate speech model
                        if transcription.text:
                            transcribe_hate_result = (
                                app_state.hate_speech_model.predict(transcription.text)
                            )

                            list_results.append(
                                (transcribe_hate_result, transcription.text)
                            )

                        # Clean up temporary file
                        if os.path.exists(video_path):
                            os.remove(video_path)

                except Exception as e:
                    print(f"Error analyzing video: {str(e)}")

            # Analyze context if we have text
            final_text = ""
            hate_speech_arr = []
            abusive_arr = []
            hate_speech_score = 0
            abusive_score = 0
            for hate_result, text in list_results:
                if text is not None and text != "":
                    final_text += text + "\n"
                    post.text = final_text
                    hate_speech_arr.append(hate_result.hate_speech_score)
                    abusive_arr.append(hate_result.abusive_score)
            hate_speech_score = sum(hate_speech_arr) / len(hate_speech_arr)
            abusive_score = sum(abusive_arr) / len(abusive_arr)
            hate_level = RiskLevel.from_score(hate_speech_score)
            abusive_level = RiskLevel.from_score(abusive_score)
            score_detail = ScoreDetail(
                hate_speech=hate_speech_score,
                insult=abusive_score,
                threat=(hate_speech_score * 0.7 + abusive_score * 0.3) / 2,
                toxicity=(hate_speech_score * 0.3 + abusive_score * 0.7) / 2,
            )
            all_scorers = [
                score_detail.hate_speech,
                score_detail.toxicity,
                score_detail.threat,
                score_detail.insult,
            ]
            risk_level = max(all_scorers)
            risk_level = RiskLevel.from_score(risk_level)
            post_analysis.scores = score_detail
            post_analysis.risk_level = risk_level

            if final_text != "":
                try:
                    # Prepare context input
                    context_input = ContextInput(
                        text=final_text or "",
                        scores=ContextScores(
                            hate=hate_speech_score,
                            abusive=abusive_score,
                        ),
                        levels=ContextLevels(
                            hate=hate_level,
                            abusive=abusive_level,
                        ),
                    )

                    # Analyze context
                    context_result = app_state.context_model.predict(context_input)

                    # Update post analysis
                    post_analysis.context.category = context_result.category
                    post_analysis.context.explanation = context_result.explanation

                except Exception as e:
                    print(f"Error analyzing context: {str(e)}")

            # Add post to platform posts
            platform_posts.append(
                PostResponse(
                    post_content=PostContent(
                        url=post.url,
                        text=post.text,
                        date=datetime.now(),
                        image_url=post.image_url,
                        video_url=post.video_url,
                    ),
                    analysis=post_analysis,
                )
            )
        platform_hate_scores = []
        platform_abusive_scores = []
        platform_threat_scores = []
        platform_toxicity_scores = []

        for post in platform_posts:
            platform_hate_scores.append(post.analysis.scores.hate_speech)
            platform_abusive_scores.append(post.analysis.scores.insult)
            platform_threat_scores.append(post.analysis.scores.threat)
            platform_toxicity_scores.append(post.analysis.scores.toxicity)
        # Calculate platform-level scores
        platform_hate = (
            sum(platform_hate_scores) / len(platform_hate_scores)
            if platform_hate_scores
            else 0.0
        )
        platform_abusive = (
            sum(platform_abusive_scores) / len(platform_abusive_scores)
            if platform_abusive_scores
            else 0.0
        )
        platform_threat = (
            sum(platform_threat_scores) / len(platform_threat_scores)
            if platform_threat_scores
            else 0.0
        )
        platform_toxicity = (
            sum(platform_toxicity_scores) / len(platform_toxicity_scores)
            if platform_toxicity_scores
            else 0.0
        )
        platform_score = (
            platform_hate + platform_abusive + platform_threat + platform_toxicity
        ) / 4

        # Add to all scores
        all_hate_scores.extend(platform_hate_scores)
        all_abusive_scores.extend(platform_abusive_scores)
        all_threat_scores.extend(platform_threat_scores)
        all_toxicity_scores.extend(platform_toxicity_scores)

        # Create social media response
        social_media_responses.append(
            SocialMediaResponse(
                platform=social_media.platform,
                username=social_media.username,
                platform_risk_score=int(platform_score * 100),
                platform_risk_level=RiskLevel.from_score(platform_score),
                posts=platform_posts,
            )
        )

    # Calculate overall scores
    overall_hate = (
        sum(all_hate_scores) / len(all_hate_scores) if all_hate_scores else 0.0
    )
    overall_abusive = (
        sum(all_abusive_scores) / len(all_abusive_scores) if all_abusive_scores else 0.0
    )
    overall_threat = (
        sum(all_threat_scores) / len(all_threat_scores) if all_threat_scores else 0.0
    )
    overall_toxicity = (
        sum(all_toxicity_scores) / len(all_toxicity_scores)
        if all_toxicity_scores
        else 0.0
    )
    overall_score = (
        overall_hate + overall_abusive + overall_threat + overall_toxicity
    ) / 4

    # Create final response
    return AnalyzeResponse(
        name=persona.name,
        overall_risk_score=int(overall_score * 100),
        overall_risk_level=RiskLevel.from_score(overall_score),
        aggregated_scores=ScoreDetail(
            toxicity=overall_abusive,
            threat=overall_abusive,
            insult=overall_abusive,
            hate_speech=overall_hate,
        ),
        summary=SummaryStats(
            total_posts_analyzed=total_posts, high_risk_posts_count=high_risk_posts
        ),
        social_media=social_media_responses,
    )


AUDIO_BASE_DIR = "app/audio"


@router.post("/analyze-post", response_model=AnalyzePostResponse)
async def analyze_post(payload: AnalyzePostRequest, req: Request):
    request_id = uuid.uuid4().hex
    audio_path = os.path.join(AUDIO_BASE_DIR, f"temp_audio_{request_id}.wav")
    audio_path = convert_mp4_url_to_wav(str(payload.video_url), audio_path)  # type: ignore
    app_state = get_app_state(req)
    transcription = app_state.transcribe_model.predict(audio_path)
    if audio_path and os.path.exists(audio_path):
        try:
            os.remove(audio_path)
            print(f"Temporary file {audio_path} removed successfully")
        except OSError as e:
            print(f"Error removing temporary file {audio_path}: {e}")
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

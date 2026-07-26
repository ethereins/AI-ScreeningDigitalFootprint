from datetime import datetime
from enum import Enum
from typing import Any, Dict, List, Optional

# Import RiskLevel from enums
from app.schemas.enums import RiskLevel
from pydantic import BaseModel, Field


class ScoreDetail(BaseModel):
    toxicity: float = Field(..., ge=0, le=1)
    threat: float = Field(..., ge=0, le=1)
    insult: float = Field(..., ge=0, le=1)
    hate_speech: float = Field(..., ge=0, le=1)


class PostContext(BaseModel):
    category: str
    explanation: str


class PostAnalysis(BaseModel):
    risk_score: int = Field(..., ge=0, le=100)
    risk_level: RiskLevel
    scores: ScoreDetail
    context: PostContext


class PostContent(BaseModel):
    url: str = Field(min_length=1, description="Post URL")
    text: Optional[str | None] = None
    date: Optional[datetime] = None
    image_url: Optional[str | None] = None
    video_url: Optional[str] = None


class PostResponse(BaseModel):
    post_content: PostContent
    analysis: PostAnalysis


class SocialMediaResponse(BaseModel):
    platform: str
    username: str
    platform_risk_score: int = Field(..., ge=0, le=100)
    platform_risk_level: RiskLevel
    posts: List[PostResponse]


class SummaryStats(BaseModel):
    total_posts_analyzed: int = Field(..., ge=0)
    high_risk_posts_count: int = Field(..., ge=0)


class AnalyzeResponse(BaseModel):
    name: str
    overall_risk_score: int = Field(..., ge=0, le=100)
    overall_risk_level: RiskLevel
    aggregated_scores: ScoreDetail
    summary: SummaryStats
    social_media: List[SocialMediaResponse]


# analyze-post
class Scores(BaseModel):
    toxicity: Optional[float] = None
    threat: Optional[float] = None
    insult: Optional[float] = None
    obscene: Optional[float] = None
    identity_attack: Optional[float] = None
    sexual_explicit: Optional[float] = None
    hate_speech: Optional[float] = None
    offensive: Optional[float] = None
    abusive: Optional[float] = None


class Context(BaseModel):
    category: Optional[str] = None
    explanation: Optional[str] = None
    extra: Dict[str, Any] = {}  # for any additional context fields


class AnalyzePostResponse(BaseModel):
    risk_score: float  # 0-100
    risk_level: RiskLevel
    scores: Scores
    context: Context


from pydantic import BaseModel, Field




# Analyze texts
class AnalyzeTextResponse(BaseModel):
    risk_score: float
    risk_level: RiskLevel
    scores: Scores  # can reuse the same Scores model
    context: Context


class OcrResponse(BaseModel):
    text: str
    confidence: Optional[float] = None  # optional, if OCR returns confidence


class TranscribeResponse(BaseModel):
    text: str
    detected_language: Optional[str] = None
    language_probability: Optional[float] = None

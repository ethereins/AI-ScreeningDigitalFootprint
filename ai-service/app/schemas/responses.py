from datetime import datetime
from enum import Enum
from typing import List, Optional

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

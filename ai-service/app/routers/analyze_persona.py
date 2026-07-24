from datetime import datetime

from app.schemas.requests import AnalyzeRequest
from app.schemas.responses import (
    AnalyzeResponse,
    PostAnalysis,
    PostContent,
    PostContext,
    PostResponse,
    RiskLevel,
    ScoreDetail,
    SocialMediaResponse,
    SummaryStats,
)
from fastapi import APIRouter


router = APIRouter()


@router.post("") 
async def analyze_post(request: AnalyzeRequest):
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

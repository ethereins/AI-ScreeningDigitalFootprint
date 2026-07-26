from datetime import datetime
from typing import List, Optional

from pydantic import BaseModel, Field, HttpUrl


class PostRequest(BaseModel):
    """Request schema for /analyze-persona endpoint"""

    url: str = Field(min_length=1, description="Post URL")
    text: Optional[str] = None
    image_url: Optional[str] = None
    video_url: Optional[str] = None


class SocialMediaRequest(BaseModel):
    platform: str = Field(min_length=1, description="Platform name")
    username: str = Field(min_length=1, description="Username on this platform")
    email: Optional[str] = None
    post_count: int = Field(default=0, ge=0, description="Total posts count")
    profile_url: Optional[str] = None
    avatar_url: Optional[str] = None
    created_at: datetime = Field(description="ISO 8601 timestamp")
    updated_at: datetime = Field(description="ISO 8601 timestamp")
    posts: List[PostRequest] = Field(default_factory=list)


class AnalyzeRequest(BaseModel):
    name: str = Field(min_length=1, max_length=255, description="Candidate name")
    # ✅ Fixed: min_items → min_length
    social_media: List[SocialMediaRequest] = Field(
        min_length=1, description="Social media accounts"
    )


class AnalyzePostRequest(BaseModel):
    """Request schema for /analyze-post endpoint"""

    text: str = Field(..., min_length=1, description="Text content to analyze")
    image_url: Optional[HttpUrl] = Field(
        None, description="URL of the image to analyze"
    )
    video_url: Optional[HttpUrl] = Field(
        None, description="URL of the video to analyze"
    )
    platform: str = Field(..., min_length=1, description="Platform name")
    post_id: str = Field(..., min_length=1, description="Post ID from the platform")


class AnalyzeTextRequest(BaseModel):
    """Request schema for /analyze-text endpoint"""

    text: str = Field(..., min_length=1, description="Text content to analyze")


class OCRRequest(BaseModel):
    """Request schema for /ocr endpoint"""

    image_url: HttpUrl = Field(..., description="URL of the image to perform OCR on")


class TranscribeRequest(BaseModel):
    """Request schema for /transcribe endpoint"""

    video_url: HttpUrl = Field(..., description="URL of the video to transcribe")

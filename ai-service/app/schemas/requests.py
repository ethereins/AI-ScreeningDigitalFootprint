from datetime import datetime
from typing import List, Optional

from pydantic import BaseModel, Field


class PostRequest(BaseModel):
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

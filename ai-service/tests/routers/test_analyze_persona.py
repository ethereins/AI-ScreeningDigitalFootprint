import json
import sys
from datetime import datetime
from pathlib import Path

# Add the project directory to the Python path
sys.path.append(str(Path(__file__).parent.parent.parent))

import pytest
from app.main import app
from app.schemas.requests import AnalyzeRequest, PostRequest, SocialMediaRequest
from fastapi.testclient import TestClient

client = TestClient(app)


@pytest.fixture
def sample_analyze_request():
    return AnalyzeRequest(
        name="John Doe",
        social_media=[
            SocialMediaRequest(
                platform="Twitter",
                username="johndoe",
                email="john.doe@example.com",
                post_count=10,
                profile_url="https://twitter.com/johndoe",
                avatar_url="https://twitter.com/johndoe/avatar",
                created_at=datetime.now(),
                updated_at=datetime.now(),
                posts=[
                    PostRequest(
                        url="https://twitter.com/johndoe/status/1",
                        text="This is a sample post",
                        image_url=None,
                        video_url=None,
                    )
                ],
            )
        ],
    )


def test_analyze_persona_response_structure(sample_analyze_request):
    # Convert datetime objects to ISO format strings
    request_data = sample_analyze_request.model_dump()
    for sm in request_data["social_media"]:
        sm["created_at"] = sm["created_at"].isoformat()
        sm["updated_at"] = sm["updated_at"].isoformat()

    response = client.post("/analyze-persona", json=request_data)
    assert response.status_code == 200
    response_data = response.json()

    # Verify top-level structure
    assert "name" in response_data
    assert "overall_risk_score" in response_data
    assert "overall_risk_level" in response_data
    assert "aggregated_scores" in response_data
    assert "summary" in response_data
    assert "social_media" in response_data

    # Verify aggregated_scores structure
    assert "toxicity" in response_data["aggregated_scores"]
    assert "threat" in response_data["aggregated_scores"]
    assert "insult" in response_data["aggregated_scores"]
    assert "hate_speech" in response_data["aggregated_scores"]

    # Verify summary structure
    assert "total_posts_analyzed" in response_data["summary"]
    assert "high_risk_posts_count" in response_data["summary"]

    # Verify social_media structure
    for platform in response_data["social_media"]:
        assert "platform" in platform
        assert "username" in platform
        assert "platform_risk_score" in platform
        assert "platform_risk_level" in platform
        assert "posts" in platform

        for post in platform["posts"]:
            assert "post_content" in post
            assert "analysis" in post

            # Verify post_content structure
            assert "url" in post["post_content"]
            assert "text" in post["post_content"]
            assert "date" in post["post_content"]
            assert "image_url" in post["post_content"]
            assert "video_url" in post["post_content"]

            # Verify analysis structure
            assert "risk_score" in post["analysis"]
            assert "risk_level" in post["analysis"]
            assert "scores" in post["analysis"]
            assert "context" in post["analysis"]

            # Verify scores structure
            assert "toxicity" in post["analysis"]["scores"]
            assert "threat" in post["analysis"]["scores"]
            assert "insult" in post["analysis"]["scores"]
            assert "hate_speech" in post["analysis"]["scores"]

            # Verify context structure
            assert "category" in post["analysis"]["context"]
            assert "explanation" in post["analysis"]["context"]


def test_analyze_persona_missing_name():
    request_data = {
        "social_media": [
            {
                "platform": "Twitter",
                "username": "johndoe",
                "email": "john.doe@example.com",
                "post_count": 10,
                "profile_url": "https://twitter.com/johndoe",
                "avatar_url": "https://twitter.com/johndoe/avatar",
                "created_at": datetime.now().isoformat(),
                "updated_at": datetime.now().isoformat(),
                "posts": [
                    {
                        "url": "https://twitter.com/johndoe/status/1",
                        "text": "This is a sample post",
                        "image_url": None,
                        "video_url": None,
                    }
                ],
            }
        ]
    }
    response = client.post("/api/analyze-persona", json=request_data)
    assert response.status_code == 422
    assert "name" in response.json()["detail"][0]["loc"]


def test_analyze_persona_empty_social_media():
    request_data = {"name": "John Doe", "social_media": []}
    response = client.post("/api/analyze-persona", json=request_data)
    assert response.status_code == 422
    assert "social_media" in response.json()["detail"][0]["loc"]

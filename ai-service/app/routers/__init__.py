from app.routers import analysis
from fastapi import APIRouter

# Create the main API router with global prefix
api_router = APIRouter()

# Include your feature routers
api_router.include_router(
    analysis.router
)  # /api/analyze-persona/...


# Health check – can be a simple endpoint directly on the api_router
@api_router.get("/health", tags=["Health"])
async def health_check():
    return {"status": "OK"}

import os
import sys
from pathlib import Path

# Add the project directory to the Python path
sys.path.append(str(Path(__file__).parent.parent))
import os

from app.models.context_analysis import ContextConfig, ContextModel
from app.models.hate_speech import HateSpeechConfig, HateSpeechModel

# Import model classes
from app.models.ocr import OCRConfig, OCRModel
from app.routers import api_router
from dotenv import load_dotenv
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from state import AppState

load_dotenv()
app = FastAPI(title="Feather Verifier", version="0.1.0")
app.include_router(api_router)
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:5173",
        "http://127.0.0.1:5173",
        "http://localhost:3000",
        "http://127.0.0.1:3000",
        "http://localhost:5173/",
        os.getenv("FRONTEND_URL", "http://localhost:5173"),
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize models
app.state.app_state = AppState()

if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host="127.0.0.1", port=7000)

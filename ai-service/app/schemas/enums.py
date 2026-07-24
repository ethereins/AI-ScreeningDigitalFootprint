from typing import Optional, List
from datetime import datetime
from pydantic import BaseModel, Field, HttpUrl
from enum import Enum

class RiskLevel(str, Enum):
    LOW = "LOW"
    MEDIUM = "MEDIUM"
    HIGH = "HIGH"
    CRITICAL = "CRITICAL"
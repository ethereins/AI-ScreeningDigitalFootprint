# models/risk.py
from enum import Enum


class RiskLevel(str, Enum):
    """Risk level classification"""

    LOW = "LOW"
    MEDIUM = "MEDIUM"
    HIGH = "HIGH"
    CRITICAL = "CRITICAL"

    @classmethod
    def from_score(cls, score: float) -> "RiskLevel":
        """
        Convert a risk score (0-1) to RiskLevel

        Args:
            score: Risk score between 0 and 1

        Returns:
            RiskLevel enum value
        """
        if score >= 0.75:
            return cls.CRITICAL
        elif score >= 0.50:
            return cls.HIGH
        elif score >= 0.25:
            return cls.MEDIUM
        else:
            return cls.LOW

    @classmethod
    def from_confidence(cls, confidence: float, classification: str) -> "RiskLevel":
        """
        Convert hate speech classification and confidence to RiskLevel

        Args:
            confidence: Confidence score (0-1)
            classification: Classification string (HATE_SPEECH, ABUSIVE, NEUTRAL)

        Returns:
            RiskLevel enum value
        """
        if classification == "HATE_SPEECH":
            if confidence >= 0.8:
                return cls.CRITICAL
            elif confidence >= 0.6:
                return cls.HIGH
            else:
                return cls.MEDIUM
        elif classification == "ABUSIVE":
            if confidence >= 0.8:
                return cls.HIGH
            elif confidence >= 0.6:
                return cls.MEDIUM
            else:
                return cls.LOW
        else:  # NEUTRAL or other
            return cls.LOW

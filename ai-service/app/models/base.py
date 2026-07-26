# models/base.py
from abc import ABC, abstractmethod
from dataclasses import dataclass
from typing import Any, Optional


@dataclass
class ModelConfig:
    """Base configuration for models"""

    name: str = "base_model"
    version: str = "1.0.0"
    use_gpu: bool = False
    model_path: str = ""


class BaseModel(ABC):
    """Abstract base class for all models"""

    def __init__(self, config: ModelConfig):
        self.config = config
        self.is_loaded = False
        self.load_time_ms = 0

    @abstractmethod
    def load(self):
        """Load the model"""
        pass

    @abstractmethod
    def unload(self):
        """Unload the model"""
        pass

    @abstractmethod
    def predict(self, input_data: Any) -> Any:
        """Make prediction"""
        pass

    def get_info(self) -> dict:
        """Get model information"""
        return {
            "name": self.config.name,
            "version": self.config.version,
            "is_loaded": self.is_loaded,
            "load_time_ms": self.load_time_ms,
        }

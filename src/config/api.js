const API_CONFIG = {
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    aiURL: import.meta.env.VITE_AI_URL || 'http://localhost:8001',
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    }
};

export default API_CONFIG;
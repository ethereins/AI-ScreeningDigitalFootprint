import axios from 'axios';
import API_CONFIG from '../config/api';

const api = axios.create({
    baseURL: API_CONFIG.baseURL,
    timeout: API_CONFIG.timeout,
    headers: API_CONFIG.headers,
});

api.interceptors.response.use(
    response => response,
    error => {
        const message = error.response?.data?.message || error.message || 'Terjadi kesalahan';
        console.error('API Error:', message);
        return Promise.reject({
            message,
            status: error.response?.status,
            data: error.response?.data
        });
    }
);

export const candidateService = {
    getAll: (params = {}) => api.get('/candidates', { params }),
    getById: (id) => api.get(`/candidates/${id}`),
    create: (data) => api.post('/candidates', data),
    getPosts: (id, params = {}) => api.get(`/candidates/${id}/posts`, { params }),
    getRiskSummary: (id) => api.get(`/candidates/${id}/risk`),
    rescan: (id) => api.post(`/candidates/${id}/rescan`),
};

export default api;
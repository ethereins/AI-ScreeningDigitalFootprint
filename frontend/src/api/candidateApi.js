import axiosInstance from './axiosConfig';

export const candidateApi = {
  // Get all candidates with filters
  getAll: (params = {}) => axiosInstance.get('/candidates', { params }),
  
  // Get single candidate
  getById: (id) => axiosInstance.get(`/candidates/${id}`),
  
  // Create new candidate
  create: (data) => axiosInstance.post('/candidates', data),
  
  // Update candidate
  update: (id, data) => axiosInstance.put(`/candidates/${id}`, data),
  
  // Delete candidate
  delete: (id) => axiosInstance.delete(`/candidates/${id}`),
  
  // Get candidate posts
  getPosts: (id, params = {}) => axiosInstance.get(`/candidates/${id}/posts`, { params }),
  
  // Get risk summary
  getRiskSummary: (id) => axiosInstance.get(`/candidates/${id}/risk`),
  
  // Rescan candidate
  rescan: (id) => axiosInstance.post(`/candidates/${id}/rescan`),
};
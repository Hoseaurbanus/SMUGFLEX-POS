import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8080/api/v1';

const api = axios.create({
  baseURL: API_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('sf_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        const refreshToken = localStorage.getItem('sf_refresh_token');
        if (refreshToken) {
          const response = await axios.post(`${API_URL}/auth/refresh`, {}, {
            headers: { Authorization: `Bearer ${localStorage.getItem('sf_token')}` },
          });

          if (response.data.success) {
            const { token } = response.data.data;
            localStorage.setItem('sf_token', token);
            originalRequest.headers.Authorization = `Bearer ${token}`;
            return api(originalRequest);
          }
        }
      } catch (refreshError) {
        localStorage.removeItem('sf_token');
        localStorage.removeItem('sf_refresh_token');
        localStorage.removeItem('sf_user');
        window.location.href = '/login';
        return Promise.reject(refreshError);
      }

      localStorage.removeItem('sf_token');
      localStorage.removeItem('sf_refresh_token');
      localStorage.removeItem('sf_user');
      window.location.href = '/login';
    }

    const message = error.response?.data?.message || error.message || 'An error occurred';
    return Promise.reject(new Error(message));
  }
);

export default api;

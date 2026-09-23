import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api/v1',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
  timeout: 10000,
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Clear invalid local session
      localStorage.removeItem('token');
      localStorage.removeItem('user');

      // Identify request and path context to avoid loops and preserve public portal
      const requestUrl = error.config?.url || '';
      const isAuthProbe = requestUrl.includes('/auth/login') || requestUrl.includes('/auth/me');

      if (typeof window !== 'undefined' && !isAuthProbe) {
        const pathname = window.location.pathname;
        const isPublicCustomerPortal = pathname.startsWith('/track/');
        const isAlreadyOnLogin = pathname === '/login';

        if (!isPublicCustomerPortal && !isAlreadyOnLogin) {
          window.location.href = '/login';
        }
      }
    }
    return Promise.reject(error);
  }
);

export const getHealthStatus = async () => {
  const startTime = performance.now();
  const response = await api.get('/health');
  const durationMs = Math.round(performance.now() - startTime);

  return {
    ...response.data,
    clientLatencyMs: durationMs,
  };
};

export default api;

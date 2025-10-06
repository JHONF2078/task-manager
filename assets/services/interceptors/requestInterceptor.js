// Interceptor para peticiones - maneja autenticación y CSRF
import { useAuthStore } from '../../stores/authStore';
import { ensureCsrf, getCsrfToken } from '../csrfService';

const AUTH_PUBLIC_ENDPOINTS = [
  /^\/api\/login$/,
  /^\/api\/register$/,
  /^\/api\/auth\/password\/forgot$/,
  /^\/api\/auth\/password\/reset$/,
  /^\/api\/auth\/token\/refresh$/,
  /^\/api\/auth\/logout$/,
  /^\/api\/csrf$/
];

// Interceptor para peticiones - maneja autenticación y CSRF
export const requestInterceptor = async (config) => {
  const auth = useAuthStore();
  const isPublicAuth = AUTH_PUBLIC_ENDPOINTS.some(rx => rx.test(config.url));

  // Adjuntar Authorization solo si no es endpoint público
  if (auth.token && !isPublicAuth) {
    config.headers.Authorization = `${auth.tokenType || 'Bearer'} ${auth.token}`;
  }

  // Manejo de CSRF para métodos que lo necesitan
  const needsCsrf = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(config.method?.toUpperCase()) && !isPublicAuth;
  if (needsCsrf) {
    if (!getCsrfToken()) {
      await ensureCsrf();
    }
    const token = getCsrfToken();
    if (token) {
      config.headers['X-CSRF-Token'] = token;
    }
  }

  return config;
};

export const requestErrorInterceptor = (error) => {
  return Promise.reject(error);
};

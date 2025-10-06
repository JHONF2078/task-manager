// Interceptor para respuestas - maneja errores, refresh tokens y retry automático
import { useAuthStore } from '../../stores/authStore';
import { ensureCsrf } from '../csrfService';
import { refreshTokenApi } from '../authService';
import router from '../../router';

const AUTH_PUBLIC_ENDPOINTS = [
  /^\/api\/login$/,
  /^\/api\/register$/,
  /^\/api\/auth\/password\/forgot$/,
  /^\/api\/auth\/password\/reset$/,
  /^\/api\/auth\/token\/refresh$/,
  /^\/api\/auth\/logout$/,
  /^\/api\/csrf$/
];

// Función para crear errores formateados
function createFormattedError(data, fallbackMessage) {
  let errorMessage = fallbackMessage;
  let errorDetails = {};

  if (data) {
    if (data.message) {
      errorMessage = data.message;
    } else if (data.violations && data.violations.length > 0) {
      errorMessage = data.violations[0].message;
      errorDetails.violations = data.violations;
    } else if (data.error) {
      errorMessage = data.error;
    }

    errorDetails = {
      ...errorDetails,
      status: data.status,
      code: data.code,
      type: data.type,
      details: data.details,
      timestamp: data.timestamp
    };
  }

  const error = new Error(errorMessage);
  Object.assign(error, errorDetails);
  return error;
}

export const responseInterceptor = (response) => {
  // Para respuestas blob (archivos), retornar la respuesta completa para acceder a headers
  if (response.config.responseType === 'blob') {
    return response;
  }
  // Para respuestas JSON normales, retornar solo los datos
  return response.data;
};

export const responseErrorInterceptor = async (error, httpInstance) => {
  const auth = useAuthStore();
  const originalRequest = error.config;

  if (error.response) {
    const { data, status } = error.response;
    const isPublicAuth = AUTH_PUBLIC_ENDPOINTS.some(rx => rx.test(originalRequest.url));

    // Log para debugging
    console.error('[HTTP]', originalRequest.method?.toUpperCase(), originalRequest.url, 'status', status, 'body', data);

    // Manejo de 401 - No autorizado
    if (status === 401) {
      if (!isPublicAuth && !originalRequest._retry) {
        originalRequest._retry = true;
        try {
          const refreshed = await refreshTokenApi();
          auth.initializeSession(refreshed);
          // Reintentar la petición original
          return httpInstance(originalRequest);
        } catch (refreshError) {
          auth.logout();
          router.push('/login');
          throw createFormattedError(data, 'Sesión expirada');
        }
      } else if (!isPublicAuth) {
        auth.logout();
        router.push('/login');
      }
      throw createFormattedError(data, 'No autorizado');
    }

    // Manejo de 419 - CSRF inválido
    if (status === 419) {
      if (!originalRequest._csrfRetry) {
        originalRequest._csrfRetry = true;
        await ensureCsrf();
        return httpInstance(originalRequest);
      }
      throw createFormattedError(data, 'CSRF inválido');
    }

    // Para otros errores HTTP
    throw createFormattedError(data, 'Error en la petición');
  }

  // Error de red o timeout
  if (error.request) {
    throw new Error('No se pudo conectar con el servidor');
  }

  // Error de configuración de Axios
  throw new Error(error.message || 'Error inesperado');
};

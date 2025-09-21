// HTTP wrapper centralizado para peticiones autenticadas con soporte CSRF & refresh silencioso
import { useAuthStore } from '../stores/authStore';
import { ensureCsrf, getCsrfToken } from './csrfService';
import { refreshTokenApi } from './authService';

const AUTH_PUBLIC_ENDPOINTS = [
  /^\/api\/login$/,
  /^\/api\/register$/,
  /^\/api\/auth\/password\/forgot$/,
  /^\/api\/auth\/password\/reset$/,
  /^\/api\/auth\/token\/refresh$/,
  /^\/api\/auth\/logout$/,
  /^\/api\/csrf$/
];

async function baseFetch(url, options = {}, retry = false) {
  const auth = useAuthStore();
  const method = (options.method || 'GET').toUpperCase();
  const headers = new Headers(options.headers || {});
  const isPublicAuth = AUTH_PUBLIC_ENDPOINTS.some(rx => rx.test(url));

  // Adjuntar Authorization solo si no es endpoint público
  if (auth.token && !isPublicAuth) {
    headers.set('Authorization', `${auth.tokenType || 'Bearer'} ${auth.token}`);
  }
  if (!headers.has('Content-Type') && !(options.body instanceof FormData) && !['GET','HEAD'].includes(method)) {
    headers.set('Content-Type', 'application/json');
  }
  if (!headers.has('Accept')) headers.set('Accept','application/json');

  const needsCsrf = ['POST','PUT','PATCH','DELETE'].includes(method) && !isPublicAuth;
  if (needsCsrf) {
    if (!getCsrfToken()) { await ensureCsrf(); }
    const token = getCsrfToken();
    if (token) headers.set('X-CSRF-Token', token);
  }

  const finalOptions = { ...options, method, headers, credentials: 'include' };
  const response = await fetch(url, finalOptions);

  let data = null;
  try { data = await response.clone().json(); } catch(_){ /* puede no tener body */ }

  if (!response.ok) {
    // Log diagnóstico antes de procesar lógica específica
    try { console.error('[HTTP]', method, url, 'status', response.status, 'body', data || await response.clone().text()); } catch(e){}
  }

  if (response.status === 401) {
    // Reutilizamos isPublicAuth ya calculado
    if (!isPublicAuth && !retry) {
      try {
        const refreshed = await refreshTokenApi();
        auth.initializeSession(refreshed);
        // Reintentar original
        return await baseFetch(url, options, true);
      } catch(e) {
        auth.logout();
        throw new Error((data && (data.error || data.message)) || 'No autorizado');
      }
    } else if (!isPublicAuth) {
      auth.logout();
    }
    throw new Error((data && (data.error || data.message)) || 'No autorizado');
  }

  if (response.status === 419) {
    // CSRF inválido: forzar reobtención y no retry automático por seguridad excepto primera vez
    if (!retry) {
      await ensureCsrf();
      return baseFetch(url, options, true);
    }
    throw new Error((data && (data.error || data.message)) || 'CSRF inválido');
  }

  if (!response.ok) {
    throw new Error((data && (data.error || data.message)) || 'Error en la petición');
  }
  return data;
}

export function httpGet(url, options) { return baseFetch(url, { method: 'GET', ...(options||{}) }); }
export function httpPost(url, body, options) { return baseFetch(url, { method: 'POST', body: JSON.stringify(body ?? {}), ...(options||{}) }); }
export function httpPut(url, body, options) { return baseFetch(url, { method: 'PUT', body: JSON.stringify(body ?? {}), ...(options||{}) }); }
export function httpDelete(url, options) { return baseFetch(url, { method: 'DELETE', ...(options||{}) }); }

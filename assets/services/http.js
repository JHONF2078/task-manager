// HTTP wrapper centralizado para peticiones autenticadas con soporte CSRF & refresh silencioso
import axios from 'axios';
import { requestInterceptor, requestErrorInterceptor } from './interceptors/requestInterceptor';
import { responseInterceptor, responseErrorInterceptor } from './interceptors/responseInterceptor';

// Crear instancia de Axios
const http = axios.create({
  baseURL: '',
  timeout: 30000,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Configurar interceptores
http.interceptors.request.use(requestInterceptor, requestErrorInterceptor);
http.interceptors.response.use(
  responseInterceptor,
  (error) => responseErrorInterceptor(error, http)
);

// Funciones de conveniencia
export function httpGet(url, config = {}) {
  return http.get(url, config);
}

export function httpPost(url, data = {}, config = {}) {
  return http.post(url, data, config);
}

export function httpPut(url, data = {}, config = {}) {
  return http.put(url, data, config);
}

export function httpPatch(url, data = {}, config = {}) {
  return http.patch(url, data, config);
}

export function httpDelete(url, config = {}) {
  return http.delete(url, config);
}

export default http;

// Servicio simple para gestionar el token CSRF obtenido desde /api/csrf
let csrfToken = null;
let pending = null;

export async function ensureCsrf(force = false) {
  if (!force && csrfToken) return csrfToken;
  if (pending) return pending; // evita llamadas duplicadas
  let attempts = 0;
  const maxAttempts = 2;
  pending = (async () => {
    while (attempts < maxAttempts) {
      attempts++;
      try {
        const resp = await fetch('/api/csrf', {
          method: 'GET',
            credentials: 'include',
            headers: {
              'Accept': 'application/json',
              'Cache-Control': 'no-cache'
            }
          });
        if (!resp.ok) {
          const text = await resp.text().catch(()=> '');
          console.error('[CSRF] fallo intento', attempts, 'status', resp.status, 'body', text);
          if (attempts >= maxAttempts) {
            throw new Error(`Respuesta no OK (${resp.status})`);
          }
          continue; // reintentar
        }
        const data = await resp.json();
        if (!data || !data.token) {
          console.error('[CSRF] respuesta sin token', data);
          if (attempts >= maxAttempts) {
            throw new Error('Respuesta sin token');
          }
          continue;
        }
        csrfToken = data.token;
        return csrfToken;
      } catch (e) {
        console.warn('[CSRF] error intento', attempts, e);
        if (attempts >= maxAttempts) {
          throw e;
        }
      }
    }
    throw new Error('No se pudo obtener CSRF tras reintentos');
  })().finally(() => { pending = null; });
  return pending;
}

export function getCsrfToken() { return csrfToken; }
export function clearCsrfToken() { csrfToken = null; }

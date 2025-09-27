// Servicio simple para gestionar el token CSRF obtenido desde /api/csrf
// csrfToken variable singleton en memoria, ya que esta definida fuera de la funcion
let csrfToken = null;
let pending = null;

function generateRandomToken(bytes = 18) {
  const arr = new Uint8Array(bytes);
  (window.crypto || window.msCrypto).getRandomValues(arr);
  let str = btoa(String.fromCharCode.apply(null, arr));
  str = str.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  if (str.length < 24) {
    const more = btoa(String.fromCharCode.apply(null, new Uint8Array(6))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    str = (str + more).slice(0, 24);
  }
  return str;
}

function setCsrfDoubleSubmitCookie(cookieName, token) {
  const cookieKey = (window.location.protocol === 'https:' ? '__Host-' : '') + cookieName + '_' + token;
  const cookieValue = cookieName;
  const secure = window.location.protocol === 'https:';
  let cookie = `${cookieKey}=${cookieValue}; path=/; samesite=strict`;
  if (secure) cookie += '; secure';
  document.cookie = cookie;
}

export async function ensureCsrf(force = false) {
  if (!force && csrfToken) return csrfToken;
  if (pending) return pending;
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
          continue;
        }
        const data = await resp.json();
        if (!data || !data.token) {
          console.error('[CSRF] respuesta sin token', data);
          if (attempts >= maxAttempts) {
            throw new Error('Respuesta sin token');
          }
          continue;
        }
        // Modo double-submit: data.token es el nombre de la cookie (ej: 'csrf-token')
        const cookieName = data.token;
        const randomToken = generateRandomToken();
        try {
          setCsrfDoubleSubmitCookie(cookieName, randomToken);
        } catch (e) {
          console.warn('[CSRF] no se pudo crear cookie, comprobar entorno', e);
        }
        csrfToken = randomToken;
        console.log('entre a csrf');
        console.log(csrfToken);
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
export function clearCsrfCookie() {
  if (!csrfToken) return;
  // Elimina la cookie csrf-token_<token> (o __Host-csrf-token_<token> si HTTPS)
  const cookieName = 'csrf-token_' + csrfToken;
  const hostPrefix = window.location.protocol === 'https:' ? '__Host-' : '';
  document.cookie = `${hostPrefix}${cookieName}=; path=/; samesite=strict; max-age=0`;
}

export function clearCsrfToken() {
  clearCsrfCookie();
  csrfToken = null;
}

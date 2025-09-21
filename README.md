# MiApp - Estrategia de Autenticación, Refresh Token y CSRF

Este documento describe la arquitectura implementada para autenticación JWT (access token en memoria/localStorage + refresh token en cookie HttpOnly), obtención y uso de CSRF tokens, y manejo centralizado de peticiones HTTP en el frontend (Vue 3 + Pinia) y backend (Symfony).

## Resumen de la Estrategia

- **Access Token (JWT)**: Se guarda en `localStorage` (clave `auth_token`) junto con metadatos (`token_type`, `expires_at`, `issued_at`, `auth_user`).
- **Refresh Token**: Nunca se expone al frontend. El backend lo envía y almacena en una cookie HttpOnly/SameSite (asumido) y se usa en `/api/auth/token/refresh`.
- **CSRF Token**: Se obtiene mediante GET a `/api/csrf` y se cachea en memoria (no en localStorage). Se adjunta en el header `X-CSRF-Token` únicamente en métodos mutadores (POST, PUT, PATCH, DELETE) que no estén en la lista de endpoints públicos.
- **Wrapper HTTP**: `assets/services/http.js` añade automáticamente Authorization, CSRF y maneja 401/419 con reintentos controlados.
- **Refresh Silencioso**: Ante un 401 en un endpoint protegido se intenta automáticamente un refresh (una sola vez) y luego se re-lanza la petición original. Si falla, se fuerza logout.
- **Bootstrap Inicial**: Al iniciar la app se:
  1. Lanza `ensureCsrf()` de forma temprana (no bloqueante) para tener el token disponible cuanto antes.
  2. Ejecuta `auth.bootstrap()` que, si no hay access token válido o está expirado, intenta `silentRefresh()`.

## Flujo de Inicio de Sesión
1. Usuario envía credenciales a `/api/login` (con POST JSON).
2. Respuesta incluye `{ token, token_type, expires_at, issued_at, user }`.
3. Se persiste en el store (`authStore`) y `localStorage`.
4. `Authorization: <token_type> <token>` se adjuntará automáticamente desde el wrapper HTTP en futuras peticiones.

## Refresco de Token
- Implícito: un 401 en un endpoint protegido dispara `refreshTokenApi()` y reintenta una sola vez.
- Expiración Proactiva: `auth.bootstrap()` intenta un refresh silencioso si el token inicial falta o está expirado.
- No se usa `setInterval`; el refresh se hace on-demand, reduciendo peticiones innecesarias.

## Manejo de CSRF
- `ensureCsrf()` evita llamadas duplicadas mediante una promesa `pending` en `csrfService`.
- Sólo se envía `X-CSRF-Token` en métodos mutadores y para endpoints NO públicos.
- Código de respuesta 419 (CSRF inválido) induce una re-obtención y un único reintento automático.

## Lista de Endpoints Públicos (no requieren Authorization / CSRF)
Definidos en `AUTH_PUBLIC_ENDPOINTS` (regex) dentro de `http.js`:
```
/api/login
/api/register
/api/auth/password/forgot
/api/auth/password/reset
/api/auth/token/refresh
/api/auth/logout
/api/csrf
```

## Store de Autenticación (`authStore.js`)
Responsabilidades:
- Persistencia local de datos de sesión.
- Cálculo de `isAuthenticated`, expiración y `expiresInMs`.
- `initializeSession(payload)` para normalizar la respuesta del backend.
- `silentRefresh()` no fuerza logout en caso de error; la siguiente petición 401 se encargará.
- `logout()` limpia estado y storage, y notifica al backend.

## Seguridad & Consideraciones
- El refresh token NO se expone al JS (cookie HttpOnly -> mitigación XSS).
- CSRF token se mantiene sólo en memoria -> si la pestaña se cierra se obtiene de nuevo.
- Access token en `localStorage`: facilita tabs múltiples; riesgo XSS mitigado parcialmente con buenas prácticas (CSP recomendable, sanitización y no inlining de datos peligrosos).
- Logout siempre intenta invalidar en backend, pero limpia local aunque falle.

## Errores y Mensajes
- 401 tras fallo de refresh => `logout()` y propagación de error.
- 419 tras segundo intento => error explícito "CSRF inválido".
- Otros códigos no `ok` => mensaje derivado de `data.error || data.message` o fallback genérico.

## Cómo Extender
- Añadir endpoint público: insertar regex en `AUTH_PUBLIC_ENDPOINTS`.
- Añadir cabeceras comunes: modificar `baseFetch` antes del `fetch`.
- Forzar refresh manual: llamar a `refreshTokenApi()` y `auth.initializeSession()`.

## Secuencia Resumida de una Petición Protegida
1. Componente llama `httpPost('/api/tasks', {...})`.
2. `baseFetch` añade Authorization y, al ser POST y no público, asegura CSRF.
3. Backend valida CSRF y JWT.
4. Si JWT expiró -> backend 401 -> `baseFetch` intenta refresh -> reintenta.
5. Si refresh falla -> logout -> error hacia el componente.

## Scripts Útiles
Frontend (Webpack Encore):
```
npm run dev        # build de desarrollo
npm run watch      # watch
npm run dev-server # dev-server con HMR
npm run build      # build producción
```

Symfony:
```
php bin/console cache:clear
php bin/console doctrine:migrations:migrate
php bin/console server:dump  # si se usa symfony local server (ajustar según setup)
```

## Próximas Mejoras (Ideas)
- Añadir almacenamiento cifrado del access token (ej: WebCrypto) si se considera necesario.
- Implementar expiración inminente -> pre-refresh (ej: si < 30s restante).
- Integrar cola de peticiones dobladas durante refresh para evitar tormenta de 401.
- CSP estricta y Subresource Integrity para mejorar mitigación XSS.

---
Cualquier cambio adicional en la estrategia debe documentarse aquí para mantener consistencia.


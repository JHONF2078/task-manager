// assets/router/guards/authGuard.js
import { useAuthStore } from '../../stores/authStore';

const publicAuthRoutes = new Set(['login','register','forgot-password']);

export function authGuard(to, from, next) {
  const auth = useAuthStore();

  if (to.meta.catchAll) {
    return next(auth.token ? { name: 'home' } : { name: 'login' });
  }

  if (to.meta.requiresAuth && !auth.token) {
    return next({ name: 'login' });
  }

  if (auth.token && publicAuthRoutes.has(String(to.name))) {
    return next({ name: 'home' });
  }

  // Ruta de reset-password siempre accesible (autenticado o no) para permitir cambiar contraseña.
  next();
}


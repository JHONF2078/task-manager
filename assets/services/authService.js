import { httpPost } from './http';

export async function loginApi(email, password) {
  const data = await httpPost('/api/login', { email: email?.trim(), password });
  return data; // { token, token_type, expires_at, issued_at, user }
}

export async function registerApi(data) {
  const payload = { email: data.email?.trim(), password: data.password, name: data.name?.trim() };
  return await httpPost('/api/register', payload);
}

export async function refreshTokenApi(){
  // Ya no se envía refresh_token; el backend lo toma de la cookie HttpOnly
  return await httpPost('/api/auth/token/refresh', {});
}

export async function logoutApi(){
  return await httpPost('/api/auth/logout', {});
}

export async function requestPasswordReset(email){
  return await httpPost('/api/auth/password/forgot', { email: email?.trim() });
}

export async function confirmPasswordReset(token, newPassword){
  return await httpPost('/api/auth/password/reset', { token, new_password: newPassword });
}

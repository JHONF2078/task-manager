import { httpGet, httpPost, httpDelete } from './http';

export async function fetchUsersApi(filters = {}) {
  let url = '/api/users';
  if (filters.email) {
    const qs = new URLSearchParams({ email: filters.email }).toString();
    url += '?' + qs;
  }
  return await httpGet(url);
}

export async function createUserApi(payload){
  // payload: { email, password, name, roles }
  return await httpPost('/api/users', payload);
}

export async function updateUserRolesApi(userId, roles){
  return await httpPost(`/api/users/${userId}/roles`, { roles });
}

export async function deleteUserApi(userId){
  return await httpDelete(`/api/users/${userId}`);
}

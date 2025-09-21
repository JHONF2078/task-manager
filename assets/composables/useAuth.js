import { computed } from 'vue';
import { useAuthStore } from '../stores/authStore';

export function useAuthHelpers(){
  // funcion de pinia useAuthStore
  const auth = useAuthStore();
  const isAdmin = computed(()=> Array.isArray(auth.user?.roles) && auth.user.roles.includes('ROLE_ADMIN'));
  const roles = computed(()=> auth.user?.roles || []);
  return { auth, isAdmin, roles };
}


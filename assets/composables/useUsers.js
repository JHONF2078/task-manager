import { ref, watch, onMounted } from 'vue';
import { fetchUsersApi } from '../services/userService';
import { useUserStore } from '../stores/userStore';
import { storeToRefs } from 'pinia';

export function useUsers() {
  const store = useUserStore();
  const { users, loading, error, filters } = storeToRefs(store);
  let lastHash = '';
  let inFlight = false;
  let pending = false;

  function normalizedEmail(v){ return (v || '').trim().toLowerCase(); }

  function filtersHash(f){
    return JSON.stringify([
      normalizedEmail(f.email),
      f.page, // por si luego agregas paginación
      f.limit,
      f.sortBy,
      f.sortDir
    ]);
  }

  async function fetchUsers() {
    const currentHash = filtersHash(filters.value);
    if(currentHash === lastHash) return;
    if(inFlight){ pending = true; return; }
    inFlight = true;
    store.setLoading(true);
    store.setError('');
    try {
      const data = await fetchUsersApi({ email: normalizedEmail(filters.value.email) });
      store.setUsers(data);
      lastHash = currentHash;
    } catch (e) {
      store.setError(e.message || 'Error al cargar usuarios');
    } finally {
      store.setLoading(false);
      inFlight = false;
      if(pending){ pending = false; setTimeout(()=>fetchUsers(),0); }
    }
  }

  // Observa sólo email (y futuras props si las añades). "deep" innecesario.
  watch(() => filters.value.email, () => {
    // Reseteamos el hash para forzar comparación distinta si el usuario escribe mismo valor con mayúsculas
    lastHash = lastHash === filtersHash(filters.value) ? '' : lastHash;
    fetchUsers();
  });

  // Carga inicial una sola vez
  onMounted(()=> fetchUsers());

  return {
    users,
    loading,
    error,
    filters,
    setFilters: store.setFilters,
    fetchUsers
  };
}

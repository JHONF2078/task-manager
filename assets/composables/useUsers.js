import { ref, watch, onMounted } from 'vue';
import { fetchUsersApi } from '../services/userService';
import { useUserStore } from '../stores/userStore';
import { storeToRefs } from 'pinia';

export function useUsers() {
  const store = useUserStore();
  const { users, loading, error, filters } = storeToRefs(store);

  async function fetchUsers() {
    store.setLoading(true);
    store.setError('');
    try {
      const data = await fetchUsersApi(filters.value);
      store.setUsers(data);
    } catch (e) {
      store.setError(e.message || 'Error al cargar usuarios');
    } finally {
      store.setLoading(false);
    }
  }

  onMounted(fetchUsers);
  watch(() => filters.value, fetchUsers, { deep: true });

  return {
    users,
    loading,
    error,
    filters,
    setFilters: store.setFilters
  };
}

import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [],
    loading: false,
    error: '',
    filters: {
      email: '' // único filtro activo
    }
  }),
  actions: {
    setUsers(users) {
      this.users = users;
    },
    addUser(u){
      this.users = [u, ...this.users];
    },
    setLoading(val) {
      this.loading = val;
    },
    setError(msg) {
      this.error = msg;
    },
    setFilters(filters) {
      this.filters = { ...this.filters, ...filters };
    },
    updateUser(updated){
      const idx = this.users.findIndex(u => u.id === updated.id);
      if (idx !== -1) {
        this.users.splice(idx, 1, { ...this.users[idx], ...updated });
      }
    },
    removeUser(id){
      this.users = this.users.filter(u => u.id !== id);
    }
  }
});

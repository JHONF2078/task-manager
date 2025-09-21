import { defineStore } from 'pinia';

export const useTaskStore = defineStore('task', {
  state: () => ({
    tasks: [],
    meta: { page:1, limit:10, total:0, pages:0 },
    loading: false,
    error: '',
    filters: {
      search: '',
      status: '',
      priority: '',
      assignee: '', // user id
      tags: [], // array de strings
      dueFrom: '',
      dueTo: '',
      sortBy: 'createdAt',
      sortDir: 'desc',
      page: 1,
      limit: 10
    }
  }),
  actions: {
    setTasks(tasks){ this.tasks = tasks; },
    setMeta(meta){ if(meta) this.meta = { ...this.meta, ...meta }; },
    addTask(task){ this.tasks.unshift(task); },
    updateTask(task){
      const idx = this.tasks.findIndex(t=> t.id === task.id);
      if(idx !== -1){ this.tasks.splice(idx,1,{...this.tasks[idx], ...task}); }
    },
    removeTask(id){ this.tasks = this.tasks.filter(t=> t.id !== id); },
    setLoading(v){ this.loading = v; },
    setError(msg){ this.error = msg; },
    setFilters(f){ this.filters = { ...this.filters, ...f }; },
    setPage(page){ this.filters.page = page; },
    setLimit(limit){ this.filters.limit = limit; },
    resetFilters(){ this.filters = { search:'', status:'', priority:'', assignee:'', tags:[], dueFrom:'', dueTo:'', sortBy:'createdAt', sortDir:'desc', page:1, limit:10 }; }
  }
});

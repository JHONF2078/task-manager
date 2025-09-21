import { ref, watch, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useTaskStore } from '../stores/taskStore';
import { fetchTasksApi, createTaskApi, updateTaskApi, deleteTaskApi } from '../services/taskService';

// Mapea estados y prioridades a etiquetas visibles (ajústalo si backend usa otros valores)
export const TASK_STATUSES = [
  { value: 'pendiente', label: 'Pendiente' },
  { value: 'en_progreso', label: 'En progreso' },
  { value: 'completada', label: 'Completada' }
];

export const TASK_PRIORITIES = [
  { value: 'baja', label: 'Baja' },
  { value: 'media', label: 'Media' },
  { value: 'alta', label: 'Alta' }
];

export function useTasks(){
  const store = useTaskStore();
  const { tasks, loading, error, filters, meta } = storeToRefs(store);
  const saving = ref(false);
  const pendingTimer = ref(null);
  let lastHash = '';
  let requestCounter = 0;

  function filtersHash(f){
    return JSON.stringify([
      f.search, f.status, f.priority, f.assignee,
      [...(f.tags||[])].sort(), f.dueFrom, f.dueTo,
      f.sortBy, f.sortDir, f.page, f.limit
    ]);
  }

  async function fetchTasks(){
    const currentHash = filtersHash(filters.value);
    if(currentHash === lastHash){
      return; // evita petición redundante idéntica
    }
    const reqId = ++requestCounter;
    store.setLoading(true);
    store.setError('');
    try {
      const { meta: m, data } = await fetchTasksApi(filters.value);
      // Si llegó una respuesta antigua la descartamos
      if(reqId !== requestCounter) return;
      store.setTasks(data);
      store.setMeta(m);
      lastHash = currentHash;
    } catch(e){
      if(reqId !== requestCounter) return; // ignorar errores de peticiones obsoletas
      store.setError(e.message || 'Error al cargar tareas');
    } finally {
      if(reqId === requestCounter) store.setLoading(false);
    }
  }

  function scheduleFetch(){
    if(pendingTimer.value) clearTimeout(pendingTimer.value);
    pendingTimer.value = setTimeout(()=>{
      fetchTasks();
    }, 180); // debounce 180ms
  }

  // Reemplazamos watch directo de fetch por uno con debounce + hash
  onMounted(()=> { fetchTasks(); });
  watch(()=> filters.value, scheduleFetch, { deep: true });

  async function createTask(payload){
    saving.value = true;
    try {
      const created = await createTaskApi(payload);
      store.addTask(created);
      return created;
    } finally { saving.value = false; }
  }

  async function updateTask(id, payload){
    saving.value = true;
    try {
      const updated = await updateTaskApi(id, payload);
      store.updateTask(updated);
      return updated;
    } finally { saving.value = false; }
  }

  async function deleteTask(id){
    saving.value = true;
    try {
      await deleteTaskApi(id);
      store.removeTask(id);
    } finally { saving.value = false; }
  }

  function setFilters(f){
    store.setFilters(f);
    // scheduleFetch(); // el watch ya lo hará
  }
  function resetFilters(){ store.resetFilters(); /* watch disparará */ }
  function setPage(p){ store.setPage(p); }
  function setLimit(l){ store.setLimit(l); }

  return { tasks, loading, error, filters, meta, saving, fetchTasks, createTask, updateTask, deleteTask, setFilters, resetFilters, setPage, setLimit, TASK_STATUSES, TASK_PRIORITIES };
}

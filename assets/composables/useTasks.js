import { ref } from 'vue';
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
  let lastHash = '';
  let requestCounter = 0;
  let inFlight = false;      // indica si hay una petición en curso
  let pending = false;       // indica que hubo cambios mientras había una petición en curso

  function filtersHash(f){
    return JSON.stringify([
      f.search, f.status, f.priority, f.assignee,
      [...(f.tags||[])].sort(), f.dueFrom, f.dueTo,
      f.sortBy, f.sortDir, f.page, f.limit
    ]);
  }

  // Helper: determina si el rango de fechas está incompleto (sólo uno de los dos valores)
  function isIncompleteDateRange(f){
    const from = (f.dueFrom || '').trim();
    const to   = (f.dueTo || '').trim();
    return (from && !to) || (!from && to); // verdadero si sólo uno está seteado
  }

  async function fetchTasks(){
    console.log('[tasks] fetchTasks called', { filters: { ...filters.value } });
    // Evitar llamada si el rango está incompleto (requisito solicitado)
    if (isIncompleteDateRange(filters.value)) return; // esperar a rango completo

    const currentHash = filtersHash(filters.value);
    if(currentHash === lastHash){
      return; // ya obtenidos para este hash
    }

    // Si ya hay una petición en curso, marcamos que hay cambios pendientes y salimos.
    if (inFlight) {
      pending = true;
      return;
    }

    inFlight = true;
    const startHash = currentHash; // hash de esta ejecución
    const reqId = ++requestCounter;
    store.setLoading(true);
    store.setError('');

    try {
      const { meta: m, data } = await fetchTasksApi(filters.value);
      if(reqId !== requestCounter) return; // respuesta obsoleta
      store.setTasks(data);
      store.setMeta(m);
      lastHash = startHash; // sólo confirmamos hash cuando termina correctamente
    } catch(e){
      if(reqId !== requestCounter) return;
      store.setError(e.message || 'Error al cargar tareas');
    } finally {
      if(reqId === requestCounter) store.setLoading(false);
      inFlight = false;
      if (pending) { // ejecutar última petición pendiente (agrupa múltiples cambios en una sola)
        pending = false;
        // Usamos setTimeout 0 para permitir que se apliquen nuevas mutaciones antes del siguiente cálculo
        setTimeout(()=> fetchTasks(), 0);
      }
    }
  }

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
    const before = JSON.stringify(store.filters);
    store.setFilters(f);
    const after = JSON.stringify(store.filters);
    if(before !== after){
      console.log('[tasks] setFilters changed', f);
    } else {
      console.log('[tasks] setFilters ignored (no changes)');
    }
  }
  function resetFilters(){ store.resetFilters(); }
  function setPage(p){ store.setPage(p); }
  function setLimit(l){ store.setLimit(l); }

  return { tasks, loading, error, filters, meta, saving, fetchTasks, createTask, updateTask, deleteTask, setFilters, resetFilters, setPage, setLimit, TASK_STATUSES, TASK_PRIORITIES };
}

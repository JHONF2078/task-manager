<template>
  <v-card>
    <v-card-title class="d-flex align-center justify-space-between">
      <span>Listado de tareas</span>
      <div class="d-flex gap-2">
        <v-btn color="secondary" prepend-icon="mdi-download" @click="openExport">Exportar</v-btn>
        <v-btn v-if="isAdmin" color="primary" prepend-icon="mdi-plus" @click="openCreate">Nueva</v-btn>
      </div>
    </v-card-title>
    <v-data-table
      :headers="headers"
      :items="tasksForTable"
      :loading="loading"
      item-key="id"
      :items-per-page="10"
      v-model:sort-by="sortState"
      loading-text="Cargando tareas..."
      class="elevation-1"
      density="comfortable"
      @click:row="rowClick"
    >
      <template #item.title="{ item }">
        <div class="d-flex flex-column">
          <strong class="text-link" @click.stop="openShow(item)" style="cursor:pointer">{{ item.title }}</strong>
          <small class="text-grey" v-if="item.description">{{ truncate(item.description) }}</small>
        </div>
      </template>
      <template #item.status="{ item }">
        <v-chip :color="statusColor(item.status)" size="x-small" label text-color="white">{{ statusLabel(item.status) }}</v-chip>
      </template>
      <template #item.priority="{ item }">
        <v-chip :color="priorityColor(item.priority)" size="x-small" label text-color="white">{{ priorityLabel(item.priority) }}</v-chip>
      </template>
      <template #item.dueDate="{ item }">
        <span :class="{ 'text-error': isOverdue(item) }">{{ formatDateShort(item.dueDate) }}</span>
      </template>
      <template #item.assigneeDisplay="{ item }">
        <span>{{ item.assigneeDisplay }}</span>
      </template>
      <template #item.actions="{ item }">
        <v-btn icon variant="text" size="small" color="info" @click.stop="openShow(item)" title="Ver">
          <v-icon size="18">mdi-eye</v-icon>
        </v-btn>
        <template v-if="isAdmin">
          <v-btn icon variant="text" size="small" color="primary" @click.stop="openEdit(item)" title="Editar">
            <v-icon size="18">mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="error" @click.stop="openDelete(item)" title="Eliminar">
            <v-icon size="18">mdi-delete</v-icon>
          </v-btn>
        </template>
      </template>
      <template #no-data>
        <div class="pa-4 text-grey">No hay tareas</div>
      </template>
    </v-data-table>

    <!-- Dialogos -->
    <TaskForm v-model="dialogForm" :task="selected" @created="onCreated" @updated="onUpdated" />
    <TaskDeleteDialog v-model="dialogDelete" :task="selected" @deleted="onDeleted" />
    <TaskShow v-model="dialogShow" :task="selected" />
    <TaskExportDialog v-model="dialogExport" @exported="onExported" />

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="2500">{{ snackbar.text }}</v-snackbar>
  </v-card>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useTasks, TASK_STATUSES, TASK_PRIORITIES } from '../../composables/useTasks';
import { useAuthHelpers } from '../../composables/useAuth';
import TaskForm from './TaskForm.vue';
import TaskDeleteDialog from './TaskDeleteDialog.vue';
import TaskShow from './TaskShow.vue';
import TaskExportDialog from './TaskExportDialog.vue';

const { tasks, loading, filters, setFilters } = useTasks();
const { auth, isAdmin } = useAuthHelpers();

// Columnas sin etiquetas ni actualizado (se muestran en vista detalle)
const headers = computed(()=> [
  { title: 'Título', key: 'title', sortable: true },
  { title: 'Estado', key: 'status', sortable: true },
  { title: 'Prioridad', key: 'priority', sortable: true },
  { title: 'Vencimiento', key: 'dueDate', sortable: true },
  { title: 'Asignado', key: 'assigneeDisplay', sortable: true },
  { title: 'Acciones', key: 'actions', sortable: false }
]);

// Dataset transformado para orden estable por email
const tasksForTable = computed(()=> (tasks.value || []).map(t => ({
  ...t,
  assigneeDisplay: t.assignee?.email || (typeof t.assignee === 'object' ? t.assignee?.email : (t.assignee?.name || t.assignee || '-')) || '-' // fallback defensivo
})));

// Estado de ordenamiento local
const sortState = ref([
  { key: filters.value.sortBy === 'assignedTo' ? 'assigneeDisplay' : (filters.value.sortBy || 'dueDate'), order: (filters.value.sortDir || 'asc') }
]);

// Mapeo UI -> backend
const sortKeyMap = { assigneeDisplay: 'assignedTo', assignee: 'assignedTo', title: 'title', status: 'status', priority: 'priority', dueDate: 'dueDate' };

watch(sortState, (val)=> {
  const first = Array.isArray(val) && val.length ? val[0] : null;
  if(!first) return;
  const backendKey = sortKeyMap[first.key] || first.key;
  if(backendKey !== filters.value.sortBy || first.order !== filters.value.sortDir){
    setFilters({ sortBy: backendKey, sortDir: first.order });
  }
}, { deep: true });

function statusLabel(v){ return TASK_STATUSES.find(s=> s.value===v)?.label || v; }
function priorityLabel(v){ return TASK_PRIORITIES.find(p=> p.value===v)?.label || v; }
function statusColor(v){
  if(v==='completada') return 'green-darken-2';
  if(v==='en_progreso') return 'blue-darken-2';
  return 'grey-darken-1';
}
function priorityColor(v){
  if(v==='alta') return 'red-darken-3';
  if(v==='media') return 'amber-darken-3';
  return 'teal-darken-3';
}
function rawDateToYMD(d){
  if(!d) return '';
  // Si ya es Date object devolver formateado
  if(d instanceof Date) return d.toISOString().substring(0,10);
  if(typeof d === 'string'){
    // Formatos: YYYY-MM-DD o YYYY-MM-DDTHH:MM:SS(+offset)
    const m = d.match(/^(\d{4}-\d{2}-\d{2})/);
    if(m) return m[1];
  }
  return '';
}
function formatDateShort(d){
  const ymd = rawDateToYMD(d);
  if(!ymd) return '-';
  const [y,m,day] = ymd.split('-');
  return `${parseInt(day)}/${parseInt(m)}/${y}`; // dd/mm/yyyy
}
function isOverdue(t){ if(!t.dueDate) return false; return new Date(t.dueDate) < new Date() && t.status !== 'completada'; }
function truncate(text, len=80){ if(!text) return ''; return text.length>len? text.slice(0,len)+'…': text; }

function canManage(task){
  if(isAdmin.value) return true;
  if(!task) return false;
  const uid = auth.user?.id;
  return task.assignee?.id === uid || task.assignee === uid;
}

const dialogForm = ref(false);
const dialogDelete = ref(false);
const dialogShow = ref(false);
const dialogExport = ref(false);
const selected = ref(null);

function openCreate(){ selected.value = null; dialogForm.value = true; }
function openEdit(task){ selected.value = task; dialogForm.value = true; }
function openDelete(task){ selected.value = task; dialogDelete.value = true; }
function openShow(task){ selected.value = task; dialogShow.value = true; }
function openExport(){ dialogExport.value = true; }
function rowClick(event, { item }){ if(item) openShow(item); }

const snackbar = ref({ show:false, text:'', color:'success' });
function flash(text, color='success'){ snackbar.value = { show:true, text, color }; }
function onCreated(){ flash('Tarea creada'); }
function onUpdated(){ flash('Tarea actualizada'); }
function onDeleted(){ flash('Tarea eliminada','info'); }
function onExported(e){ flash('Exportado: '+e.filename); }
</script>

<style scoped>
.gap-2 { gap: .5rem; }
.text-link:hover { text-decoration: underline; }
</style>

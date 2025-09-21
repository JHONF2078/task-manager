<template>
  <v-card class="mb-4" outlined>
    <v-card-text>
      <v-row dense>
        <v-col cols="12" md="6">
          <v-text-field v-model="local.search" label="Buscar (título o descripción)"  density="comfortable" clearable @keyup.enter="apply"  persistent-hint />
        </v-col>
        <v-col cols="6" md="3">
          <v-select v-model="local.status" :items="statusItems" label="Estado" density="comfortable" clearable @update:model-value="apply" />
        </v-col>
        <v-col cols="6" md="3">
          <v-select v-model="local.priority" :items="priorityItems" label="Prioridad" density="comfortable" clearable @update:model-value="apply" />
        </v-col>
        <v-col cols="6" md="4">
          <v-select v-model="local.assignee" :items="assigneeOptions" label="Asignado a" item-title="label" item-value="value" density="comfortable" clearable @update:model-value="apply" />
        </v-col>
        <v-col cols="6" md="4">
          <v-text-field v-model="local.dueFrom" label="Venc. desde" type="date" density="comfortable" @change="apply" />
        </v-col>
        <v-col cols="6" md="4">
          <v-text-field v-model="local.dueTo" label="Venc. hasta" type="date" density="comfortable" @change="apply" />
        </v-col>
        <v-col cols="6" md="6">
          <v-combobox v-model="tagInput" label="Etiquetas" multiple chips hide-selected clearable density="comfortable" @update:model-value="updateTags" />
        </v-col>
        <v-col cols="12" class="filter-actions">
          <v-btn color="primary" class="me-2" @click="apply" prepend-icon="mdi-magnify">Filtrar</v-btn>
          <v-btn variant="tonal" color="grey" @click="reset">Reset</v-btn>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
// Recibe los props desde TasksView.vue
const props = defineProps({
  filters: Object,
  setFilters: Function,
  resetFilters: Function,
  fetchTasks: Function,
  statuses: Array,
  priorities: Array,
  users: { type: Array, default: () => [] }
});

import { reactive, ref, watch, computed, onBeforeUnmount, onMounted, nextTick } from 'vue';

let ignoreInitial = true;
const local = reactive({ ...props.filters });
const tagInput = ref(props.filters.tags || []);

onMounted(async () => {
  Object.assign(local, { ...props.filters });
  tagInput.value = props.filters.tags || [];
  await nextTick();
  ignoreInitial = false;
});

// Items para selects de estado y prioridad (Vuetify reconoce title/value)
const statusItems = computed(() => (props.statuses || []).map(s => ({ title: s.label || s.title || s.value, value: s.value })));
const priorityItems = computed(() => (props.priorities || []).map(p => ({ title: p.label || p.title || p.value, value: p.value })));

// Opciones formateadas para el select de asignado: siempre email
const assigneeOptions = computed(() => (props.users || [])
  .map(u => ({ value: u.id, label: u.email }))
  .sort((a,b)=> a.label.localeCompare(b.label, 'es')));

const searchDebounceId = ref(null);
const dateDebounceId = ref(null);
let lastAppliedSignature = '';

function signature(obj){
  return JSON.stringify([
    obj.search,obj.status,obj.priority,obj.assignee,[...(obj.tags||[])].sort(),obj.dueFrom,obj.dueTo,obj.sortBy,obj.sortDir
  ]);
}

watch(()=> props.users, ()=>{/* trigger UI refresh */});
watch(()=> local.search, () => {
  if(ignoreInitial) return;
  if(searchDebounceId.value) clearTimeout(searchDebounceId.value);
  searchDebounceId.value = setTimeout(()=> apply(), 400);
});

// Nuevo: observar rango de fechas de forma combinada con debounce.
watch([() => local.dueFrom, () => local.dueTo], () => {
  if(ignoreInitial) return;
  if(dateDebounceId.value) clearTimeout(dateDebounceId.value);
  dateDebounceId.value = setTimeout(() => {
    const from = (local.dueFrom || '').trim();
    const to   = (local.dueTo || '').trim();
    // Solo aplicamos si ambos están vacíos (limpieza) o ambos llenos (rango completo)
    if((from && to) || (!from && !to)){
      // Normalizamos: si solo uno se limpió, aseguramos limpiar el otro para mantener coherencia
      if(!from && !to){ local.dueFrom=''; local.dueTo=''; }
      apply();
    }
  }, 500);
});

onBeforeUnmount(()=> {
  if(searchDebounceId.value) clearTimeout(searchDebounceId.value);
  if(dateDebounceId.value) clearTimeout(dateDebounceId.value);
});

function apply(){
  const from = (local.dueFrom || '').trim();
  const to   = (local.dueTo || '').trim();
  if( (from && !to) || (!from && to) ){
    return; // rango incompleto, no aplicar todavía
  }
  const payload = { ...local, tags: tagInput.value };
  const sig = signature(payload);
  if(sig === lastAppliedSignature){
    return; // filtros idénticos, no refetch
  }
  lastAppliedSignature = sig;
  props.setFilters(payload);
  props.fetchTasks(); // una sola petición por cambio aplicado
}
function reset(){
  props.resetFilters();
  Object.assign(local, { ...props.filters });
  tagInput.value = [];
  lastAppliedSignature = '';
  // Ya no llamamos a apply aquí, solo actualizamos los filtros locales
}
function updateTags(){
  apply();
}
</script>

<style scoped>
.filter-actions { display:flex; align-items:center; gap:12px; padding-top:4px; }
</style>

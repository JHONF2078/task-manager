<template>
  <v-card class="mb-4" outlined>
    <v-card-text>
      <v-row dense>
        <v-col cols="12" md="6">
          <v-text-field v-model="local.search" label="Buscar (título o descripción)"  density="comfortable" clearable @keyup.enter="apply" hint="Coincide en título o descripción" persistent-hint />
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
import { reactive, ref, watch, computed, onBeforeUnmount } from 'vue';
import { useTasks, TASK_STATUSES, TASK_PRIORITIES } from '../../composables/useTasks';
import { useUsers } from '../../composables/useUsers';

const { filters, setFilters, resetFilters } = useTasks();
const { users } = useUsers(); // para asignados

const statusItems = TASK_STATUSES.map(s=> ({ title: s.label, value: s.value }));
const priorityItems = TASK_PRIORITIES.map(p=> ({ title: p.label, value: p.value }));

// Opciones formateadas para el select de asignado: ahora siempre email
const assigneeOptions = computed(() => (users.value || [])
  .map(u => ({ value: u.id, label: u.email }))
  .sort((a,b)=> a.label.localeCompare(b.label, 'es')));

const local = reactive({ ...filters.value });
const tagInput = ref(filters.value.tags || []);
const debounceId = ref(null);

watch(users, ()=>{/* trigger UI refresh */});
watch(()=> local.search, () => {
  if(debounceId.value) clearTimeout(debounceId.value);
  debounceId.value = setTimeout(()=> apply(), 400);
});

onBeforeUnmount(()=> { if(debounceId.value) clearTimeout(debounceId.value); });

function apply(){
  setFilters({ ...local, tags: tagInput.value });
}
function reset(){
  resetFilters();
  Object.assign(local, { ...filters.value });
  tagInput.value = [];
  apply();
}
function updateTags(){
  // ya está en tagInput, sólo aplicamos diferido
  apply();
}
</script>

<style scoped>
.filter-actions { display:flex; align-items:center; gap:12px; padding-top:4px; }
</style>

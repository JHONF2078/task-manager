<template>
  <v-dialog v-model="model" max-width="720px">
    <v-card v-if="task">
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="d-flex flex-column">
          <span class="text-h6 mb-1">{{ task.title }}</span>
          <div class="d-flex flex-wrap gap-1">
            <v-chip size="x-small" :color="statusColor(task.status)" text-color="white" label>{{ statusLabel(task.status) }}</v-chip>
            <v-chip size="x-small" :color="priorityColor(task.priority)" text-color="white" label>{{ priorityLabel(task.priority) }}</v-chip>
            <v-chip v-for="tg in task.tags || []" :key="tg" size="x-small" variant="tonal">{{ tg }}</v-chip>
          </div>
        </div>
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="6">
            <strong>Estado:</strong> {{ statusLabel(task.status) }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Prioridad:</strong> {{ priorityLabel(task.priority) }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Vencimiento:</strong> {{ formatDate(task.dueDate) }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Asignado a:</strong> {{ task.assignee?.name || task.assignee?.email || '—' }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Creada:</strong> {{ formatDateTime(task.createdAt) }}
          </v-col>
          <v-col cols="12" md="6">
            <strong>Actualizada:</strong> {{ formatDateTime(task.updatedAt) }}
          </v-col>
          <v-col cols="12" v-if="task.description">
            <strong>Descripción:</strong>
            <div class="mt-1 white-space-pre-line">{{ task.description }}</div>
          </v-col>
          <v-col cols="12" v-if="(task.tags||[]).length">
            <strong>Etiquetas:</strong>
            <div class="d-flex flex-wrap gap-1 mt-1">
              <v-chip v-for="tg in task.tags" :key="tg" size="x-small" variant="elevated">{{ tg }}</v-chip>
            </div>
          </v-col>
        </v-row>
      </v-card-text>
      <v-divider />
      <v-card-actions>
        <v-spacer />
        <v-btn variant="tonal" color="grey" @click="close">Cerrar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed } from 'vue';
import { TASK_STATUSES, TASK_PRIORITIES } from '../../composables/useTasks';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  task: { type: Object, default: null }
});
const emit = defineEmits(['update:modelValue']);

const model = computed({
  get:()=> props.modelValue,
  set:v => emit('update:modelValue', v)
});

function close(){ model.value = false; }

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
function formatDate(d) {
  if (!d) return '—';
  // Extrae la fecha en formato dd/mm/yyyy igual que en el listado, pero muestra en formato largo
  const m = /^([0-9]{4})-([0-9]{2})-([0-9]{2})/.exec(d);
  if (m) {
    // Construye la fecha con hora 00:00:00 para formato largo
    const dateObj = new Date(`${m[1]}-${m[2]}-${m[3]}T00:00:00`);
    return dateObj.toLocaleString('es-ES', {
      day: 'numeric',
      month: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    });
  }
  return '—';
}
function formatDateTime(d){ if(!d) return '—'; try { return new Date(d).toLocaleString(); } catch { return d; } }
</script>

<style scoped>
.white-space-pre-line { white-space: pre-line; }
.gap-1 { gap: .25rem; }
</style>
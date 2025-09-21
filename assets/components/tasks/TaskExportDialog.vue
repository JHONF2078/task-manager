<template>
  <v-dialog v-model="model" max-width="420px">
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <span>Exportar tareas</span>
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-alert type="info" density="compact" class="mb-3" variant="tonal">
          Se usarán los filtros actuales.
        </v-alert>
        <v-radio-group v-model="format" inline>
          <v-radio value="csv" label="CSV" />
          <v-radio value="pdf" label="PDF" />
        </v-radio-group>
        <div class="mt-2 text-caption">
          <strong>Resumen filtros:</strong><br>
          <span>Estado: {{ filters.status || 'Todos' }}</span><br>
          <span>Prioridad: {{ filters.priority || 'Todas' }}</span><br>
          <span>Asignado: {{ filters.assignee || 'Todos' }}</span><br>
          <span>Venc. desde: {{ filters.dueFrom || '-' }} | hasta: {{ filters.dueTo || '-' }}</span>
        </div>
        <v-progress-linear v-if="downloading" indeterminate color="primary" class="mt-4" />
        <v-alert v-if="error" type="error" density="compact" class="mt-3">{{ error }}</v-alert>
      </v-card-text>
      <v-divider />
      <v-card-actions>
        <v-spacer />
        <v-btn variant="tonal" color="grey" @click="close">Cancelar</v-btn>
        <v-btn :disabled="downloading" color="primary" @click="doExport" :loading="downloading">Descargar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useTasks } from '../../composables/useTasks';
import { exportTasksReport } from '../../services/reportService';

const props = defineProps({ modelValue: { type:Boolean, default:false } });
const emit  = defineEmits(['update:modelValue','exported']);
const model = computed({ get:()=>props.modelValue, set:v=> emit('update:modelValue', v) });

const { filters } = useTasks();
const format = ref('csv');
const downloading = ref(false);
const error = ref('');

function close(){ if(!downloading.value) model.value=false; }

async function doExport(){
  error.value='';
  downloading.value=true;
  try {
    const { blob, filename } = await exportTasksReport(format.value, filters.value);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename; document.body.appendChild(a); a.click(); a.remove();
    URL.revokeObjectURL(url);
    emit('exported', { filename, format: format.value });
    close();
  } catch(e){
    error.value = e.message || 'Error exportando';
  } finally { downloading.value=false; }
}
</script>

<style scoped>
</style>


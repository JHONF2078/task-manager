<template>
  <v-dialog v-model="model" max-width="700px">
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <span>{{ isEdit ? 'Editar tarea' : 'Nueva tarea' }}</span>
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-form ref="formRef" @submit.prevent="submit">
          <v-row dense>
            <v-col cols="12" md="8">
              <v-text-field v-model="form.title" label="Título" :rules="[v=>!!v || 'Requerido']" required />
            </v-col>
            <v-col cols="12" md="4">
              <v-select v-model="form.status" :items="statusItems" label="Estado" />
            </v-col>
            <v-col cols="12" md="4">
              <v-select v-model="form.priority" :items="priorityItems" label="Prioridad" />
            </v-col>
            <v-col cols="12" md="4">
              <v-select v-model="form.assignee" :items="assigneeItems" item-title="label" item-value="value" label="Asignado a" clearable />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="form.dueDate" type="date" label="Vencimiento" />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="form.description" label="Descripción" rows="3" auto-grow />
            </v-col>
            <v-col cols="12">
              <v-combobox v-model="form.tags" label="Etiquetas" multiple chips hide-selected clearable />
            </v-col>
          </v-row>
        </v-form>
        <div class="text-caption text-grey mt-2" v-if="isEdit">
          Creada: {{ formatDate(task.createdAt) }} | Última modificación: {{ formatDate(task.updatedAt) }}
        </div>
      </v-card-text>
      <v-divider />
      <v-card-actions>
        <v-spacer />
        <v-btn variant="tonal" color="grey" @click="close">Cancelar</v-btn>
        <v-btn color="primary" :loading="saving" @click="submit" :disabled="!form.title">{{ isEdit? 'Guardar' : 'Crear' }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { useTasks, TASK_STATUSES, TASK_PRIORITIES } from '../../composables/useTasks';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  task: { type: Object, default: null },
  users: { type: Array, default: () => [] } // nueva prop con usuarios ya cargados
});
const emit = defineEmits(['update:modelValue','created','updated']);

const model = computed({
  get:()=>props.modelValue,
  set:v=> emit('update:modelValue', v)
});

const { createTask, updateTask, saving } = useTasks();

const statusItems = TASK_STATUSES.map(s=> ({ title: s.label, value: s.value }));
const priorityItems = TASK_PRIORITIES.map(p=> ({ title: p.label, value: p.value }));
const assigneeItems = computed(()=> (props.users || []).map(u=>({ label: u.name || u.email, value: u.id })));

const blank = ()=>({
  title: '',
  description: '',
  status: 'pendiente',
  priority: 'media',
  dueDate: '',
  assignee: '',
  tags: []
});

const form = reactive(blank());

const isEdit = computed(()=> !!props.task && !!props.task.id);

function close(){ model.value = false; }

function onlyDate(value){
  if(!value) return '';
  // Acepta formatos ISO con zona u hora; recorta a YYYY-MM-DD
  // Ej: 2025-09-20T10:22:33+00:00 -> 2025-09-20
  const m = /^(\d{4}-\d{2}-\d{2})/.exec(value);
  return m ? m[1] : '';
}

watch(()=> props.task, (t)=>{
  if(t){
    const base = blank();
    Object.assign(base, t, {
      assignee: t.assignee?.id || t.assignee || '',
      dueDate: onlyDate(t.dueDate)
    });
    Object.assign(form, base);
  } else {
    Object.assign(form, blank());
  }
}, { immediate: true });

async function submit(){
  if(!form.title) return;
  const payload = { ...form };
  // Normalizar dueDate antes de enviar
  if(!payload.dueDate){
    payload.dueDate = null; // que el backend la limpie
  } else if(!/^\d{4}-\d{2}-\d{2}$/.test(payload.dueDate)) {
    // Si por algún motivo llegó otro formato, intentar recortar
    payload.dueDate = onlyDate(payload.dueDate) || null;
  }
  if(isEdit.value){
    const updated = await updateTask(props.task.id, payload);
    emit('updated', updated);
  } else {
    const created = await createTask(payload);
    emit('created', created);
  }
  close();
}

function formatDate(d){
  if(!d) return '-';
  try { return new Date(d).toLocaleString(); } catch { return d; }
}
</script>

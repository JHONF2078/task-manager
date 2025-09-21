<template>
  <v-dialog v-model="model" max-width="460">
    <v-card>
      <v-card-title class="text-h6">Confirmar eliminación</v-card-title>
      <v-card-text>
        ¿Seguro que deseas eliminar la tarea <strong>{{ task?.title }}</strong>? Esta acción no se puede deshacer.
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="tonal" color="grey" @click="close">Cancelar</v-btn>
        <v-btn color="error" :loading="saving" @click="confirm">Eliminar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed } from 'vue';
import { useTasks } from '../../composables/useTasks';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  task: { type: Object, default: null }
});
const emit = defineEmits(['update:modelValue','deleted']);

const { deleteTask, saving } = useTasks();

const model = computed({ get:()=> props.modelValue, set:v=> emit('update:modelValue', v) });

function close(){ model.value = false; }
async function confirm(){
  if(!props.task) return;
  await deleteTask(props.task.id);
  emit('deleted', props.task.id);
  close();
}
</script>


<script setup>
import { ref, watch, computed } from 'vue';
import { deleteUserApi } from '../../services/userService';
import { useAuthStore } from '../../stores/authStore';
import { useUserStore } from '../../stores/userStore';

const props = defineProps({ modelValue: { type: Boolean, required: true }, user: { type: Object, default: null } });
const emit = defineEmits(['update:modelValue','deleted']);

const auth = useAuthStore();
const userStore = useUserStore();

const internal = ref(props.modelValue);
watch(()=>props.modelValue, v=> internal.value = v);
watch(internal, v=> emit('update:modelValue', v));

const loading = ref(false);
const error = ref('');

const isAdmin = computed(() => Array.isArray(auth.user?.roles) && auth.user?.roles?.includes('ROLE_ADMIN'));
const isSelf = computed(()=> props.user && auth.user && props.user.id === auth.user.id);

function close(){ if(!loading.value) internal.value=false; }

async function confirmDelete(){
  if(!props.user) return;
  if(!isAdmin.value){ error.value='No autorizado'; return; }
  if(isSelf.value){ error.value='No puedes eliminar tu propio usuario'; return; }
  loading.value=true; error.value='';
  try {
    await deleteUserApi(props.user.id);
    userStore.removeUser(props.user.id);
    emit('deleted', props.user.id);
    internal.value=false;
  } catch(e){
    error.value = e && e.message ? e.message : 'Error al eliminar usuario';
  } finally { loading.value=false; }
}
</script>

<template>
  <v-dialog v-model="internal" max-width="480" persistent>
    <v-card>
      <v-card-title class="text-h6">Eliminar usuario</v-card-title>
      <v-card-text>
        <div v-if="!props.user" class="text-grey">No hay usuario seleccionado.</div>
        <div v-else class="d-flex flex-column ga-2">
          <p>¿Seguro que deseas eliminar al usuario <strong>{{ props.user.email }}</strong>?</p>
          <p class="text-caption mb-0">Esta acción no elimina definitivamente ¿Esta seguro de inactivar el usuario?.</p>
          <v-alert v-if="isSelf" type="warning" density="compact" variant="tonal" class="mt-2">No puedes eliminar tu propio usuario.</v-alert>
          <v-alert v-if="!isAdmin" type="info" density="compact" variant="tonal" class="mt-2">Solo un administrador puede eliminar usuarios.</v-alert>
          <v-alert v-if="error" type="error" density="compact" class="mt-2">{{ error }}</v-alert>
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="close" :disabled="loading">Cancelar</v-btn>
        <v-btn color="error" @click="confirmDelete" :disabled="!isAdmin || isSelf || loading || !props.user" :loading="loading">Eliminar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>

</style>
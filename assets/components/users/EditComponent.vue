<script setup>
import { ref, computed, watch } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import { useUserStore } from '../../stores/userStore';
import { updateUserRolesApi } from '../../services/userService';

const props = defineProps({ modelValue: { type: Boolean, required: true }, user: { type: Object, default: null } });
const emit = defineEmits(['update:modelValue','updated']);

const auth = useAuthStore();
const userStore = useUserStore();

const internalDialog = ref(props.modelValue);
watch(() => props.modelValue, v => internalDialog.value = v);
watch(internalDialog, v => emit('update:modelValue', v));

const isAdmin = computed(()=> Array.isArray(auth.user?.roles) && auth.user.roles.includes('ROLE_ADMIN'));
const roleOptions = [
  { title: 'Usuario', value: 'ROLE_USER' },
  { title: 'Administrador', value: 'ROLE_ADMIN' }
];
const selectedRole = ref('ROLE_USER');
const loading = ref(false);
const error = ref('');

watch(()=>props.user, (u)=>{
  if (u && Array.isArray(u.roles) && u.roles.length) {
    selectedRole.value = u.roles.includes('ROLE_ADMIN') ? 'ROLE_ADMIN' : 'ROLE_USER';
  } else {
    selectedRole.value = 'ROLE_USER';
  }
  error.value='';
});

function close(){ internalDialog.value = false; }

async function save(){
  if(!props.user) return;
  if(!isAdmin.value){
    error.value = 'No tienes permisos para modificar roles';
    return;
  }
  loading.value = true; error.value='';
  try {
    const updated = await updateUserRolesApi(props.user.id, [selectedRole.value]);
    userStore.updateUser(updated);
    emit('updated', updated);
    close();
  } catch(e){
    error.value = e?.message || 'Error al actualizar roles';
  } finally { loading.value=false; }
}
</script>

<template>
  <v-dialog v-model="internalDialog" max-width="520" persistent>
    <v-card>
      <v-card-title class="text-h6">Editar usuario</v-card-title>
      <v-card-subtitle v-if="props.user">ID #{{ props.user.id }}</v-card-subtitle>
      <v-card-text>
        <div v-if="!props.user" class="text-grey">No se seleccionó usuario.</div>
        <div v-else class="d-flex flex-column ga-3">
          <v-text-field :model-value="props.user.name || ''" label="Nombre" readonly density="comfortable" />
            <v-text-field :model-value="props.user.email" label="Email" readonly density="comfortable" />
          <v-select
            v-model="selectedRole"
            :items="roleOptions"
            label="Rol"
            density="comfortable"
            :disabled="!isAdmin"
            item-title="title"
            item-value="value"
            persistent-hint
            :hint="isAdmin ? 'Selecciona el rol del usuario' : 'Solo un administrador puede cambiar el rol'"
          />
          <v-alert v-if="!isAdmin" type="info" density="compact" variant="tonal" class="mt-1">
            Solo un usuario con rol administrador puede modificar roles.
          </v-alert>
          <v-alert v-if="error" type="error" density="compact" class="mt-1">{{ error }}</v-alert>
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="close" :disabled="loading">Cancelar</v-btn>
        <v-btn color="primary" @click="save" :loading="loading" :disabled="!isAdmin || loading || !props.user">Guardar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>

</style>
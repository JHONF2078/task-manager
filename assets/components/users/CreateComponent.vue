<template>
  <v-dialog v-model="model" max-width="520px" persistent>
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <span>Nuevo usuario</span>
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-form ref="formRef" @submit.prevent="submit">
          <v-text-field v-model="form.email" label="Email" type="email" :rules="[v=>!!v||'Requerido']" required density="comfortable" />
          <v-text-field v-model="form.name" label="Nombre" density="comfortable" />
          <v-text-field
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            label="Password"
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showPassword = !showPassword"
            :rules="[v=>v && v.length>=6 || 'Mínimo 6 caracteres']"
            required
            density="comfortable"
          />
          <v-select v-if="isAdmin" v-model="form.role" :items="roleItems" label="Rol" density="comfortable" />
          <v-alert v-if="error" type="error" density="compact" class="mt-2">{{ error }}</v-alert>
        </v-form>
      </v-card-text>
      <v-divider />
      <v-card-actions>
        <v-spacer />
        <v-btn variant="tonal" color="grey" @click="close" :disabled="loading">Cancelar</v-btn>
        <v-btn color="primary" :loading="loading" :disabled="!canSubmit" @click="submit">Crear</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { createUserApi } from '../../services/userService';
import { useAuthStore } from '../../stores/authStore';
import { useUserStore } from '../../stores/userStore';

const props = defineProps({ modelValue: { type: Boolean, default: false } });
const emit = defineEmits(['update:modelValue','created']);

const auth = useAuthStore();
const userStore = useUserStore();

const model = computed({
  get:()=> props.modelValue,
  set:v => emit('update:modelValue', v)
});

const form = reactive({ email:'', name:'', password:'', role:'ROLE_USER' });
const loading = ref(false);
const error = ref('');
const formRef = ref(null);
const showPassword = ref(false);

const isAdmin = computed(()=> Array.isArray(auth.user?.roles) && auth.user.roles.includes('ROLE_ADMIN'));
const roleItems = [
  { title: 'Usuario', value: 'ROLE_USER' },
  { title: 'Administrador', value: 'ROLE_ADMIN' }
];

const canSubmit = computed(()=> !!form.email && !!form.password && form.password.length>=6);

watch(()=> model.value, v => { if(v){ reset(); } });

function reset(){
  form.email=''; form.name=''; form.password=''; form.role='ROLE_USER'; error.value=''; showPassword.value=false;
}

function close(){ model.value = false; }

async function submit(){
  if(!canSubmit.value) return;
  loading.value=true; error.value='';
  try {
    const payload = {
      email: form.email.trim(),
      password: form.password,
      name: form.name.trim(),
      roles: [ isAdmin.value && form.role === 'ROLE_ADMIN' ? 'ROLE_ADMIN':'ROLE_USER' ]
    };
    const created = await createUserApi(payload);
    userStore.addUser(created);
    emit('created', created);
    close();
  } catch(e){
    error.value = e?.message || 'Error creando usuario';
  } finally { loading.value=false; }
}
</script>

<style scoped>
</style>

<template>
  <div class="auth-wrapper">
    <v-card class="mx-auto auth-card" elevation="4">
      <v-card-title class="text-h6 font-weight-medium pb-0">Restablecer contraseña</v-card-title>
      <v-card-text class="pt-2">
        <v-form @submit.prevent="onSubmit" :disabled="loading">
          <v-text-field v-if="!prefilledToken" v-model="token" label="Token" required />
          <v-text-field v-model="password" :type="show ? 'text':'password'" label="Nueva contraseña" required :append-inner-icon="show ? 'mdi-eye-off':'mdi-eye'" @click:append-inner="show=!show" />
          <v-text-field v-model="password2" :type="show ? 'text':'password'" label="Confirmar contraseña" required />
          <v-btn type="submit" color="primary" :loading="loading" block class="mt-2" size="large">Actualizar</v-btn>
          <v-alert v-if="success" type="success" class="mt-4" density="comfortable">{{ success }}</v-alert>
          <v-alert v-if="error" type="error" class="mt-4" density="comfortable">{{ error }}</v-alert>
        </v-form>
        <div class="mt-6 small-link text-center links-stack">
          <router-link to="/login">Volver a Login</router-link>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { confirmPasswordReset } from '../../services/authService';

const route = useRoute();
const router = useRouter();
const token = ref(route.params.token || route.query.token || '');
const password = ref('');
const password2 = ref('');
const show = ref(false);
const loading = ref(false);
const error = ref('');
const success = ref('');

const prefilledToken = computed(()=> !!(route.params.token || route.query.token));

async function onSubmit(){
  error.value=''; success.value='';
  if(password.value.length < 6){ error.value = 'La contraseña debe tener al menos 6 caracteres'; return; }
  if(password.value !== password2.value){ error.value='Las contraseñas no coinciden'; return; }
  if(!token.value){ error.value='Falta el token'; return; }
  loading.value=true;
  try {
    const res = await confirmPasswordReset(token.value, password.value);
    success.value = res.message || 'Contraseña actualizada';
    setTimeout(()=> router.push('/login'), 1500);
  } catch(e){
    error.value = e.message || 'No se pudo actualizar la contraseña';
  } finally { loading.value=false; }
}
</script>
<style scoped>
.auth-wrapper { display:flex; min-height:100vh; align-items:center; justify-content:center; padding:32px; }
.auth-card { width:100%; max-width:420px; padding:8px 4px; }
.links-stack { display:flex; flex-direction:column; gap:6px; }
@media (max-width: 720px) { .auth-card { max-width:100%; } }
</style>


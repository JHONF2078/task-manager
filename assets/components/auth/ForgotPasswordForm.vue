<template>
  <div class="auth-wrapper">
    <v-card class="mx-auto auth-card" elevation="4">
      <v-card-title class="text-h6 font-weight-medium pb-0">Recuperar contraseña</v-card-title>
      <v-card-text class="pt-2">
        <p class="mb-4 text-body-2">Ingresa tu email y, si existe una cuenta asociada, te enviaremos un enlace para restablecer tu contraseña.</p>
        <v-form @submit.prevent="onSubmit" :disabled="loading">
          <v-text-field v-model="email" label="Email" type="email" required autocomplete="email" />
          <v-btn type="submit" color="primary" :loading="loading" block class="mt-2" size="large">Enviar</v-btn>
          <v-alert v-if="success" type="success" class="mt-4" density="comfortable">{{ success }}</v-alert>
          <v-alert v-if="error" type="error" class="mt-4" density="comfortable">{{ error }}</v-alert>
        </v-form>
        <div class="mt-6 small-link text-center links-stack">
          <router-link to="/login">Volver a Login</router-link>
        </div>
        <div v-if="devToken" class="dev-token mt-4">
          <strong>Token (dev):</strong>
          <code>{{ devToken }}</code>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import { requestPasswordReset } from '../../services/authService';

const email = ref('');
const loading = ref(false);
const error = ref('');
const success = ref('');
const devToken = ref('');

async function onSubmit(){
  loading.value = true; error.value=''; success.value=''; devToken.value='';
  try {
    const res = await requestPasswordReset(email.value);
    success.value = res.message || 'Si el email existe se ha enviado un enlace.';
    if(res.dev_reset_token){ devToken.value = res.dev_reset_token; }
  } catch(e){
    error.value = e.message || 'Error en la solicitud';
  } finally { loading.value=false; }
}
</script>
<style scoped>
.auth-wrapper { display:flex; min-height:100vh; align-items:center; justify-content:center; padding:32px; }
.auth-card { width:100%; max-width:420px; padding:8px 4px; }
.links-stack { display:flex; flex-direction:column; gap:6px; }
.dev-token { font-size:.75rem; word-break:break-all; opacity:.8; }
@media (max-width: 720px) { .auth-card { max-width:100%; } }
</style>


<template>
  <div class="auth-wrapper">
    <v-card class="mx-auto auth-card" elevation="4">
      <v-card-title class="text-h5 font-weight-medium pb-0">Registro</v-card-title>
      <v-card-text class="pt-2">
        <v-form @submit.prevent="onRegister" :disabled="loading">
          <div class="form-grid">
            <v-text-field v-model="name" label="Nombre" required />
            <v-text-field v-model="email" label="Email" type="email" required />
            <v-text-field
              v-model="password"
              :type="showPassword ? 'text':'password'"
              label="Contraseña"
              required
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="togglePassword"
            />
          </div>
          <v-btn type="submit" color="primary" :loading="loading" block class="mt-4" size="large" elevated>
            Registrarse
          </v-btn>
          <v-alert v-if="error" type="error" class="mt-6" density="comfortable">{{ error }}</v-alert>
          <v-alert v-if="warn" type="warning" class="mt-6" density="comfortable">{{ warn }}</v-alert>
          <v-alert v-if="success" type="success" class="mt-6" density="comfortable">{{ success }}</v-alert>
        </v-form>
        <div class="mt-6 small-link text-center links-stack">
          <router-link to="/login">¿Ya tienes cuenta? Inicia sesión</router-link>
          <router-link to="/forgot-password" class="forgot">¿Olvidaste tu contraseña?</router-link>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';
import { registerApi, loginApi } from '../../services/authService';

const name = ref('');
const email = ref('');
const password = ref('');
const showPassword = ref(false);
const loading = ref(false);
const error = ref('');
const warn = ref('');
const success = ref('');
const router = useRouter();
const auth = useAuthStore();

function togglePassword(){ showPassword.value = !showPassword.value; }

async function onRegister() {
  loading.value = true;
  error.value = ''; warn.value=''; success.value='';
  try {
    const regRes = await registerApi({ name: name.value, email: email.value, password: password.value });
    success.value = 'Registro exitoso';
    // Intento de autologin separado
    try {
      const loginRes = await loginApi(email.value, password.value);
      auth.setUser(loginRes.user);
      auth.setToken(loginRes.token);
      router.push('/home');
    } catch (e2) {
      // No marcar como error de registro: sólo advertir
      warn.value = 'Tu cuenta se creó, pero hubo un problema al iniciar sesión automáticamente. Inicia sesión manualmente.' + (e2?.message ? ' ('+ e2.message +')' : '');
    }
  } catch (e) {
    // Gestión robusta de errores de validación
    if (Array.isArray(e?.response?.data?.errors) && e.response.data.errors.length > 0) {
      error.value = e.response.data.errors.map(err => err.message).join(' | ');
    } else if (Array.isArray(e?.response?.data?.violations) && e.response.data.violations.length > 0) {
      error.value = e.response.data.violations.map(err => err.message).join(' | ');
    } else if (e?.response?.data?.error) {
      error.value = e.response.data.error;
    } else if (Array.isArray(e?.response?.data)) {
      error.value = e.response.data.map(err => err.message).join(' | ');
    } else if (e?.message) {
      error.value = e.message;
    } else {
      error.value = 'Error al registrar';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.auth-wrapper { display:flex; min-height:100vh; align-items:center; justify-content:center; padding:32px; }
.auth-card { width:100%; max-width:420px; padding:8px 4px; }
.form-grid { display:grid; gap:18px; grid-template-columns:1fr; }
.small-link { font-size:.9rem; }
.links-stack { display:flex; flex-direction:column; gap:6px; }
.links-stack a.forgot { font-size:.8rem; opacity:.85; }
@media (max-width: 720px) { .auth-card { max-width:100%; } }
</style>

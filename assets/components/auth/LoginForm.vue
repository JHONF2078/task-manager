<template>
  <div class="auth-wrapper">
    <v-card class="mx-auto auth-card" elevation="4">
      <v-card-title class="text-h5 font-weight-medium pb-0">Login</v-card-title>
      <v-card-text class="pt-2">
        <v-form @submit.prevent="onLogin" :disabled="loading">
          <div class="form-grid">
            <v-text-field v-model="email" label="Email" type="email" autocomplete="username" required />
            <v-text-field
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              label="Contraseña"
              autocomplete="current-password"
              required
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="togglePassword"
            />
          </div>
          <v-btn type="submit" color="primary" :loading="loading" block class="mt-4" size="large" elevated>
            Ingresar
          </v-btn>
          <v-alert v-if="error" type="error" class="mt-6" density="comfortable">{{ error }}</v-alert>
        </v-form>
        <div class="mt-6 small-link text-center links-stack">
          <router-link to="/register">¿No tienes cuenta? Regístrate</router-link>
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
import { loginApi } from '../../services/authService';

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const loading = ref(false);
const error = ref('');
const router = useRouter();
const auth = useAuthStore();

function togglePassword(){ showPassword.value = !showPassword.value; }

async function onLogin() {
  loading.value = true;
  error.value = '';
  try {
    const res = await loginApi(email.value, password.value);
    auth.setUser(res.user);
    auth.setToken(res.token);
    router.push('/home');
  } catch (e) {
    error.value = e.message || 'Error al iniciar sesión';
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
.links-stack a { text-decoration:none; }
.links-stack a.forgot { font-size:.8rem; opacity:.85; }
@media (max-width: 720px) { .auth-card { max-width:100%; } }
</style>

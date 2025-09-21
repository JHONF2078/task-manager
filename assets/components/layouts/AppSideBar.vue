<template>
  <!-- El ancho/padding/borde los maneja App.vue según estado (open/closed) -->
  <aside class="sidebar" role="navigation" aria-label="Menú lateral">
    <div class="sidebar__brand">
      <RouterLink to="/home" class="sidebar__brand-link" @click="$emit('navigate')">
        <img class="sidebar__logo" src="/images/logo.png" alt="Logo"/>
        <span class="sidebar__brand-text">Taks App</span>
      </RouterLink>
    </div>
    <nav class="sidebar__nav">
      <RouterLink class="sidebar__item" to="/home"  @click="$emit('navigate')">Home</RouterLink>
      <RouterLink class="sidebar__item" to="/users" @click="$emit('navigate')">Users</RouterLink>
      <RouterLink class="sidebar__item" to="/tasks" @click="$emit('navigate')">Tasks</RouterLink>
      <button type="button" class="sidebar__item sidebar__logout" @click="handleLogout">Logout</button>
    </nav>
  </aside>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/authStore';

const router = useRouter();
const auth = useAuthStore();

async function handleLogout(){
  // Esperar a que se complete el proceso de logout (limpieza de token) antes de navegar
  try { await auth.logout(); } catch(_) {}
  // Limpieza extra defensiva (puede mantenerse o eliminarse si ya lo hace la store)
  try { localStorage.removeItem('auth_token'); localStorage.removeItem('auth_user'); } catch(_) {}
  router.push('/login');
}
</script>

<style scoped>
.sidebar {
  inline-size: 100%;
  block-size: 100%;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  gap: 12px;
  padding: 10px 10px 16px;
  background: var(--sidebar-bg, #101722);
  color: #eef2f6;
}

/* ===== Brand Header ===== */
.sidebar__brand { display:flex; align-items:center; justify-content:center; padding:6px 4px 10px; }
.sidebar__brand-link { display:flex; flex-direction:column; align-items:center; gap:6px; text-decoration:none; }
.sidebar__logo { width:48px; height:48px; object-fit:contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,.35)); }
.sidebar__brand-text { font-size:.85rem; font-weight:600; letter-spacing:.5px; color:#fff; text-align:center; font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; }

.sidebar__nav { display:flex; flex-direction:column; gap:6px; margin-top:4px; }

.sidebar__item { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:12px; color:#c3ced9; text-decoration:none; line-height:1.2; font-size:.78rem; font-weight:500; letter-spacing:.3px; transition: background-color .15s ease, color .15s ease; }
.sidebar__item:hover { background:rgba(255,255,255,.08); color:#fff; }
.sidebar__item.router-link-active, .sidebar__item.router-link-exact-active { background:rgba(255,255,255,.15); color:#fff; }
.sidebar__item:focus-visible { outline:2px solid #3b82f6; outline-offset:2px; }
.sidebar__item:active { background:rgba(255,255,255,.22); }

.sidebar__logout { text-align:left; cursor:pointer; background:none; border:0; font:inherit; }
.sidebar__logout:hover { background:rgba(255,255,255,.08); }

/* Scroll estilizado (solo visible cuando overflow) */
.sidebar::-webkit-scrollbar { width:8px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.12); border-radius: 20px; }
.sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.25); }

@media (max-width: 768px){
  .sidebar { padding-top:18px; }
  .sidebar__brand-link { flex-direction: row; }
  .sidebar__brand-text { font-size:.9rem; }
}
</style>

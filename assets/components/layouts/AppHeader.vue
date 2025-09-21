<template>
  <header class="header">
    <div class="header__inner">
      <div class="header__left">
        <button
            class="header__menu"
            :aria-expanded="String(expanded)"
            :aria-controls="ariaControls"
            aria-label="Abrir menú"
            @click="$emit('toggle-sidebar')"
        >
          ☰
        </button>
        <strong class="header__brand">Tasks App</strong>
      </div>
      <div class="header__spacer" />
      <div class="header__right">
        <UserAvatar v-if="displayName" :name="displayName" />
        <span v-if="displayName" class="header__user">Hola, {{ displayName }}</span>
        <slot />
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/authStore';
import UserAvatar from '../base/UserAvatar.vue';

const auth = useAuthStore();
const displayName = computed(()=> auth.displayName);

defineProps({
  expanded: { type: Boolean, default: false },
  ariaControls: { type: String, default: 'app-sidebar' }
});
</script>

<style scoped>
.header { background: var(--color-primary, #0d47a1); color:#fff; box-shadow: var(--shadow-1, 0 2px 4px rgba(0,0,0,.12)); }
.header__inner { display:flex; align-items:flex-end; width:100%; max-width:1200px; margin:0 auto; padding:10px 28px; gap:32px; }
.header__left { display:flex; gap:14px; align-items:center; min-width:0; }
.header__spacer { flex:1 1 auto; }
.header__brand { font-weight:600; letter-spacing:.5px; white-space:nowrap; }
.header__menu { background:transparent; border:0; color:inherit; font:inherit; cursor:pointer; font-size:22px; line-height:1; padding:4px 8px; border-radius:6px; transition:background .15s; }
.header__menu:hover { background: rgba(255,255,255,.08); }
.header__menu:focus-visible { outline:2px solid #fff; outline-offset:2px; }
.header__right { display:flex; align-items:center; gap:18px; }
.header__user { font-size:.9rem; opacity:.9; }

@media (max-width: 900px){ .header__inner { padding:8px 20px; gap:20px; } }
@media (max-width: 600px){ .header__inner { padding:8px 14px; gap:14px; } .header__brand { font-size:.9rem; } }
</style>

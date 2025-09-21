<template>
  <div class="layout" :class="isSidebarOpen ? 'sidebar-open' : 'sidebar-closed'">
    <!-- Sidebar (el ancho lo manda el grid en desktop y se vuelve off-canvas en móvil) -->
    <AppSideBar
        id="app-sidebar"
        class="layout__sidebar"
        @navigate="handleNavigate"
    />

    <!-- Header -->
    <AppHeader
        class="layout__header"
        :expanded="isSidebarOpen"
        aria-controls="app-sidebar"
        @toggle-sidebar="toggleSidebar"
    />

    <!-- Contenido (el scroll vive aquí) -->
    <main class="layout__content" :aria-hidden="isMobileOpen ? 'true' : 'false'">
      <router-view />
    </main>

    <!-- Footer -->
    <AppFooter class="layout__footer" :aria-hidden="isMobileOpen ? 'true' : 'false'" />

    <!-- Backdrop (sólo móvil y solo cuando está abierto) -->
    <div v-if="isMobileOpen" class="backdrop" @click="closeSidebar"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AppSideBar from './AppSideBar.vue'
import AppHeader  from './AppHeader.vue'
import AppFooter  from './AppFooter.vue'

/* ===== se considera movil si hasta 768px ===== */
const mql = window.matchMedia('(max-width: 768px)')

//ref convierte el valor en reactivo
const isMobile = ref(mql.matches)

/* abierto por defecto en desktop, cerrado en móvil */
/* si abre en desktop isMobile=false , entonces isSidebarOpen=true */
const isSidebarOpen = ref(!isMobile.value)

/*onMqlChange es el handler del media query. Se ejecuta cada vez que el viewport
 cruza el umbral de la media query ((max-width: 768px)).*/
const onMqlChange = (e) => {
  isMobile.value = e.matches
  // al entrar a móvil -> cerrar; volver a desktop -> abrir
  isSidebarOpen.value = !isMobile.value
}

onMounted(() => {
  //se suscribe a cambios, // true si la pantalla es <= 768px, false si es más ancha
  mql.addEventListener?.('change', onMqlChange) ?? mql.addListener(onMqlChange)
  window.addEventListener('keydown', onKeydown)
})
onUnmounted(() => {
  mql.removeEventListener?.('change', onMqlChange) ?? mql.removeListener(onMqlChange)
  window.removeEventListener('keydown', onKeydown)
})

const isMobileOpen = computed(() => isMobile.value && isSidebarOpen.value)

function toggleSidebar() {
  console.log('entre')
  isSidebarOpen.value = !isSidebarOpen.value
}
function closeSidebar()  { isSidebarOpen.value = false }
function onKeydown(e)    { if (e.key === 'Escape' && isSidebarOpen.value) closeSidebar() }
function handleNavigate(){ if (isMobile.value) closeSidebar() }
</script>

<!-- IMPORTANTE: sin "scoped" para que el layout afecte a los hijos -->
<style>
:root { --sidebar-w: 120px; --layout-x: 32px; --sidebar-bg: #101722; }

/* ===== Grid base (desktop): 2 columnas x 3 filas ===== */
.layout {
  display: grid;
  grid-template-columns: var(--sidebar-w, 120px) minmax(0, 1fr);
  grid-template-rows: auto 1fr auto;
  grid-template-areas:
    "sidebar header"
    "sidebar content"
    "sidebar footer";
  min-height: 100vh;
  background: var(--color-bg, #fafafa);
}

.layout__sidebar {
  grid-area: sidebar;
  overflow: hidden;
  position: relative;
}

.layout.sidebar-open .layout__sidebar::after {
  content: "";
  position: absolute; inset: 0 0 0 auto;
  width: 1px; background: var(--border, rgba(0,0,0,.08));
  pointer-events: none;
}

.layout__header  { grid-area: header; position: sticky; top: 0; z-index: 1000; }
.layout__content { grid-area: content; overflow: auto; padding: var(--layout-x); }
.layout__footer  { grid-area: footer; }

.layout.sidebar-open  .layout__sidebar .sidebar { background: var(--color-surface, #fff); padding: 12px; }
.layout.sidebar-closed .layout__sidebar .sidebar { background: transparent; padding: 0; }

.layout.sidebar-closed { grid-template-columns: 0 minmax(0, 1fr); }
.layout.sidebar-closed .layout__sidebar {
  display:none !important;
  width:0 !important;
  min-width:0 !important;
  padding:0 !important;
}
.layout.sidebar-closed .layout__sidebar { pointer-events: none; }

@media (max-width: 768px){
  :root { --layout-x:16px; }
  .layout{
    grid-template-columns: 1fr !important;
    grid-template-areas:
      "header"
      "content"
      "footer" !important;
  }
  .layout__header,
  .layout__content,
  .layout__footer{ grid-column: 1 / -1 !important; }

  .layout__sidebar{
    position: fixed !important;
    inset: 0 auto 0 0 !important;
    inline-size: var(--sidebar-w,120px);
    max-inline-size: 90vw;
    block-size: 100vh;
    transform: translateX(-100%) !important;
    transition: transform .2s ease;
    z-index: 1200;
    overflow: auto;
    background: var(--color-surface, #fff);
    will-change: transform;
  }
  .layout.sidebar-open .layout__sidebar{ transform: translateX(0) !important; }

  .backdrop{
    position: fixed; inset: 0;
    background: rgba(0,0,0,.35);
    z-index: 1100;
    -webkit-tap-highlight-color: transparent;
  }

  .layout.sidebar-open{
    height: 100vh;
    overflow: hidden;
  }
  .layout.sidebar-open .layout__content,
  .layout.sidebar-open .layout__footer{ pointer-events: none; }
}

@media (prefers-reduced-motion: reduce){
  .layout__sidebar{ transition: none; }
}
</style>

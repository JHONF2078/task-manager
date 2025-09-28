<template>
  <v-card>
    <v-card-title class="d-flex align-center justify-space-between">
      <span>Lista de usuarios</span>
      <div class="d-flex gap-2" v-if="isAdmin">
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">Nuevo</v-btn>
      </div>
    </v-card-title>
    <v-data-table
      :key="tableKey"
      :headers="displayedHeaders"
      :items="users"
      :loading="loading"
      :items-per-page="10"
      class="elevation-1"
      loading-text="Cargando..."
      item-key="id"
      density="comfortable"
      @click:row="rowClick"
    >
      <template #item.name="{ item }">
        <span>{{ item.name || '(Sin nombre)' }}</span>
      </template>
      <template #item.roles="{ item }">
        <div class="d-flex flex-wrap gap-1">
          <v-chip
            v-for="r in (Array.isArray(item.roles) ? item.roles : [item.roles])"
            :key="r"
            size="x-small"
            :color="r==='ROLE_ADMIN' ? 'deep-purple-darken-1' : 'grey-darken-1'"
            text-color="white"
            label
          >{{ r.replace('ROLE_','') }}</v-chip>
        </div>
      </template>
      <template #item.email="{ item }">
        <span v-html="highlightEmail(item.email)"></span>
      </template>
      <template #item.actions="{ item }">
        <v-btn icon variant="text" size="small" color="info" @click.stop="openShow(item)" title="Ver">
          <v-icon size="18">mdi-eye</v-icon>
        </v-btn>
        <v-btn v-if="isAdmin" icon variant="text" size="small" color="primary" title="Editar" @click.stop="openEdit(item)">
          <v-icon size="18">mdi-pencil</v-icon>
        </v-btn>
        <v-btn v-if="isAdmin" icon variant="text" size="small" color="error" title="Eliminar" @click.stop="openDelete(item)">
          <v-icon size="18">mdi-delete</v-icon>
        </v-btn>
      </template>
      <template #no-data>
        <div class="pa-4 text-grey">No hay usuarios para mostrar</div>
      </template>
      <template #loading>
        <div class="pa-6 text-center text-grey">Cargando usuarios...</div>
      </template>
    </v-data-table>
    <v-alert v-if="error" type="error" class="mt-2" density="comfortable">{{ error }}</v-alert>

    <!-- Diálogos -->
    <CreateComponent v-model="dialogCreate" @created="onCreated" />
    <EditComponent v-model="dialogEdit" :user="selectedUser" @updated="onUpdated" />
    <DeleteComponent v-model="dialogDelete" :user="selectedUser" @deleted="onDeleted" />
    <ShowComponent v-model="dialogShow" :user="selectedUser" />

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="2500">{{ snackbar.text }}</v-snackbar>
  </v-card>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useUsers } from '../../composables/useUsers';
import { useAuthHelpers } from '../../composables/useAuth';
import CreateComponent from './CreateComponent.vue';
import EditComponent from './EditComponent.vue';
import DeleteComponent from './DeleteComponent.vue';
import ShowComponent from './ShowComponent.vue';
import { useUserStore } from '../../stores/userStore';
import { storeToRefs } from 'pinia';

const { users, loading, error } = useUsers();
const { isAdmin } = useAuthHelpers();
const userStore = useUserStore();
const { filters } = storeToRefs(userStore);

// Headers base
const baseHeaders = [
  { title: 'Nombre', key: 'name', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Rol', key: 'roles', sortable: false }
];

// Siempre mostramos Acciones (para permitir Ver) pero limitamos botones según permisos
const displayedHeaders = computed(() => [
  ...baseHeaders,
  { title: 'Acciones', key: 'actions', sortable: false }
]);

const tableKey = computed(()=> (isAdmin.value ? 'users-admin' : 'users-basic'));

const dialogCreate = ref(false);
const dialogEdit = ref(false);
const dialogDelete = ref(false);
const dialogShow = ref(false);
const selectedUser = ref(null);

function getRaw(u){
  return u && typeof u === 'object' && 'raw' in u ? u.raw : u;
}
function openCreate(){ selectedUser.value = null; dialogCreate.value = true; }
function openEdit(user){ selectedUser.value = getRaw(user); dialogEdit.value = true; }
function openDelete(user){ selectedUser.value = getRaw(user); dialogDelete.value = true; }
function openShow(user){ selectedUser.value = getRaw(user); dialogShow.value = true; }
function rowClick(event, { item }){ if(item) openShow(item); }

const snackbar = ref({ show:false, text:'', color:'success' });
function flash(text, color='success'){ snackbar.value = { show:true, text, color }; }

function onCreated(){ flash('Usuario creado'); }
function onUpdated(){ flash('Usuario actualizado'); }
function onDeleted(){ flash('Usuario eliminado', 'info'); }

function escapeHtml(str){
  return str
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#39;');
}

function highlightEmail(email){
  const term = (filters.value.email || '').trim();
  if(!term) return escapeHtml(email || '');
  try {
    // Primero escapamos el email para evitar XSS, pero luego revertimos el escape solo en el span
    const pattern = term.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
    const re = new RegExp(pattern, 'ig');
    // Resaltamos el término en el email original, sin escapar
    return (email || '').replace(re, match => `<span class=\"hl-email\">${escapeHtml(match)}</span>`);
  } catch { return escapeHtml(email || ''); }
}
</script>

<style scoped>
.gap-2 { gap: .5rem; }
</style>
<style>
.hl-email { background: #ffe9a8; padding:0 2px; border-radius:3px; }
</style>

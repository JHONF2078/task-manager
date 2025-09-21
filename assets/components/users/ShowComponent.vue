<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  user: { type: Object, default: null }
});
const emit = defineEmits(['update:modelValue']);

const model = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v)
});

function close(){ model.value = false; }

const normalizedRoles = computed(() => {
  if(!props.user) return [];
  const r = props.user.roles;
  const arr = Array.isArray(r) ? r : (r ? [r] : []);
  return arr.map(x => x.replace(/^ROLE_/,'')).map(x => x.toUpperCase());
});

function formatDateTime(val){
  if(!val) return '—';
  try { const d = (val instanceof Date)? val : new Date(val); return d.toLocaleString(); }
  catch { return String(val); }
}

const extraFields = computed(()=> {
  if(!props.user) return [];
  const base = ['id','name','email','roles','createdAt','updatedAt'];
  return Object.entries(props.user)
    .filter(([k,v]) => !base.includes(k) && v !== null && typeof v !== 'object')
    .map(([key,value]) => ({ key, value }));
});
</script>

<template>
  <v-dialog v-model="model" max-width="640px">
    <v-card v-if="user">
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="d-flex flex-column">
          <span class="text-h6 mb-1">{{ user.name || user.email || 'Usuario' }}</span>
          <div class="d-flex flex-wrap gap-1">
            <v-chip
              v-for="r in normalizedRoles"
              :key="r"
              size="x-small"
              :color="r==='ADMIN' ? 'deep-purple-darken-1':'grey-darken-1'"
              text-color="white"
              label
            >{{ r }}</v-chip>
          </div>
        </div>
        <v-btn icon="mdi-close" variant="text" @click="close" />
      </v-card-title>
      <v-divider />
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="6"><strong>ID:</strong> {{ user.id ?? '—' }}</v-col>
          <v-col cols="12" md="6"><strong>Email:</strong> {{ user.email || '—' }}</v-col>
          <v-col cols="12" md="6"><strong>Nombre:</strong> {{ user.name || '(Sin nombre)' }}</v-col>
          <v-col cols="12" md="6" v-if="user.createdAt"><strong>Creado:</strong> {{ formatDateTime(user.createdAt) }}</v-col>
          <v-col cols="12" md="6" v-if="user.updatedAt"><strong>Actualizado:</strong> {{ formatDateTime(user.updatedAt) }}</v-col>
          <v-col cols="12" v-if="extraFields.length">
            <strong>Extras:</strong>
            <ul class="mt-1 no-bullets">
              <li v-for="f in extraFields" :key="f.key"><em>{{ f.key }}:</em> {{ f.value }}</li>
            </ul>
          </v-col>
        </v-row>
      </v-card-text>
      <v-divider />
      <v-card-actions>
        <v-spacer />
        <v-btn variant="tonal" color="grey" @click="close">Cerrar</v-btn>
      </v-card-actions>
    </v-card>
    <v-card v-else>
      <v-card-text class="text-center py-8">Cargando...</v-card-text>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.gap-1 { gap: .25rem; }
.no-bullets { list-style: none; margin:0; padding:0; }
</style>
<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useUsers } from '../../composables/useUsers';

const { filters, setFilters } = useUsers();

const emailTerm = ref('');
let debounceId = null;

onMounted(()=> { emailTerm.value = filters.value.email || ''; });

watch(emailTerm, (val)=> {
  if (debounceId) clearTimeout(debounceId);
  debounceId = setTimeout(()=> { setFilters({ email: val }); }, 300);
});

function clear(){ emailTerm.value=''; }
</script>

<template>
  <div class="user-filter d-flex align-center gap-2">
    <v-text-field
      v-model="emailTerm"
      label="Buscar por email"
      variant="outlined"
      density="comfortable"
      prepend-inner-icon="mdi-magnify"
      clearable
      hide-details
      @click:clear="clear"
    />
  </div>
</template>

<style scoped>
.user-filter { padding: .5rem 1rem .25rem; }
.gap-2 { gap: .5rem; }
</style>
<template>
  <v-row class="mb-4" align="center">
    <v-col cols="12" sm="6" md="4">
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
    </v-col>
  </v-row>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { useUserStore } from '../../stores/userStore';
import { storeToRefs } from 'pinia';

const store = useUserStore();
const { filters } = storeToRefs(store);

const emailTerm = ref('');
let timer = null;

onMounted(()=> { emailTerm.value = filters.value.email || ''; });
onBeforeUnmount(()=> { if(timer) clearTimeout(timer); });

watch(emailTerm, (val, oldVal)=> {
  if (val === oldVal) return;
  if (timer) clearTimeout(timer);
  timer = setTimeout(()=> {
    if (filters.value.email !== val) {
      store.setFilters({ email: val });
    }
  }, 350);
});

function clear(){ emailTerm.value=''; }
</script>

<style scoped>
</style>

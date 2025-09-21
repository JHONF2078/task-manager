<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <UserFilter />
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12">
        <UserList />
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { onUnmounted } from 'vue';
import UserFilter from '../components/users/UserFilter.vue';
import UserList from '../components/users/UserList.vue';
import { useUserStore } from '../stores/userStore';

const userStore = useUserStore();
onUnmounted(() => {
  userStore.setFilters({ email: '' }); // Limpiar filtro de email al salir de la vista
});
</script>

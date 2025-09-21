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
import UserFilter from '../components/users/UserFilter.vue';
import UserList from '../components/users/UserList.vue';
</script>


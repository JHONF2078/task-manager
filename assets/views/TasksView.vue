<script setup>
import TaskFilter from '../components/tasks/TaskFilter.vue';
import TaskList from '../components/tasks/TaskList.vue';
</script>

<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <TaskFilter />
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12">
        <TaskList />
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>

</style>
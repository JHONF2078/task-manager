<script setup>
import { onMounted } from 'vue';
import { useTasks, TASK_STATUSES, TASK_PRIORITIES } from '../composables/useTasks';
import { useUsers } from '../composables/useUsers';
import TaskFilter from '../components/tasks/TaskFilter.vue';
import TaskList from '../components/tasks/TaskList.vue';

const {
  tasks,
  loading,
  error,
  filters,
  setFilters,
  resetFilters,
  fetchTasks,
  meta,
  createTask,
  updateTask,
  deleteTask,
  saving
} = useTasks();

// Cargar usuarios para el filtro "Asignado a"
const { users } = useUsers();

onMounted(() => {
  fetchTasks(); // Solo una vez al montar la vista
});
</script>

<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <TaskFilter
          :filters="filters"
          :setFilters="setFilters"
          :resetFilters="resetFilters"
          :fetchTasks="fetchTasks"
          :statuses="TASK_STATUSES"
          :priorities="TASK_PRIORITIES"
          :users="users"
        />
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12">
        <TaskList
          :tasks="tasks"
          :loading="loading"
          :filters="filters"
          :setFilters="setFilters"
          :meta="meta"
          :users="users"
          :create-task-fn="createTask"
          :update-task-fn="updateTask"
          :delete-task-fn="deleteTask"
          :saving="saving"
        />
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>

</style>
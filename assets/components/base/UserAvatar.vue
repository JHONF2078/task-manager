<template>
  <div class="avatar" :title="name">
    <span>{{ initials }}</span>
  </div>
</template>
<script setup>
import { computed } from 'vue';
const props = defineProps({ name: { type: String, required: true } });
const initials = computed(()=> props.name.trim().split(/\s+/).slice(0,2).map(p=>p[0]?.toUpperCase()).join(''));
</script>
<style scoped>
.avatar { width:36px; height:36px; border-radius:50%; background:#fff2; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:.9rem; box-shadow:0 0 0 1px #ffffff33 inset; }
</style>


<script lang="ts" setup>
import SpacecraftsCard from '@/Modules/Spacecrafts/SpacecraftsCard.vue';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';

const { spacecrafts } = useSpacecraftStore();
</script>

<template>
  <div class="grid gap-4 lg:gap-x-8">
    <SpacecraftsCard v-for="spacecraft in spacecrafts" :key="spacecraft.id" :spacecraft="spacecraft" />
  </div>
</template>

<style scoped>
.grid {
  --grid-min-col-size: 265px;

  grid-template-columns: repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}
</style>

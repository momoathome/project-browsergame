<script lang="ts" setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SpacecraftsCard from '@/Modules/Spacecrafts/SpacecraftsCard.vue';
import type { Spacecraft } from '@/types/types';

const props = defineProps<{
  spacecrafts: Spacecraft[]
}>()
</script>

<template>
  <AppLayout title="spacecrafts">
    <div class="grid gap-4 lg:gap-x-8">
      <SpacecraftsCard v-for="spacecraft in spacecrafts" :key="spacecraft.id" :spacecraft="spacecraft" />
    </div>
  </AppLayout>
</template>

<style scoped>
.grid {
  --grid-min-col-size: 280px;

  grid-template-columns: repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}

@media (min-width: 2600px) {
  .grid {
    grid-template-columns: repeat(6, 1fr);
    max-width: 2600px;
    margin-left: auto;
    margin-right: auto;
  }
}
</style>

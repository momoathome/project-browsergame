<script lang="ts" setup>
import BuildingsCard from '@/Modules/Buildings/BuildingsCard.vue';
import { useBuildingStore } from '@/Composables/useBuildingStore';

const { buildings } = useBuildingStore();
</script>

<template>
  <div class="grid gap-4 lg:gap-8">
    <BuildingsCard v-for="building in buildings" :key="building.id" :building="building" />
  </div>
</template>

<style scoped>
.grid {
  --grid-min-col-size: 280px;

  grid-template-columns: repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}
</style>

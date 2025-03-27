<script lang="ts" setup>
import { computed } from 'vue';
import type { Spacecraft } from '@/types/types';
import MapModalUnitCard from './MapModalUnitCard.vue';

const props = defineProps<{
  spacecrafts: Spacecraft[];
}>();

const form = defineModel({ required: false, type: Object })

// filter spacecrafts based on is unlocked status
const unlockedSpacecrafts = computed(() => {
  return props.spacecrafts.filter((spacecraft) => spacecraft.unlocked);
});

const gridColumnsCount = computed(() => {
  const count = Math.max(unlockedSpacecrafts.value.length, 3);
  return Math.min(count, 5);
});
</script>

<template>
  <div class="gap-4 dynamic-grid" :style="{ '--grid-cols': gridColumnsCount }">
    <div v-for="spacecraft in unlockedSpacecrafts" :key="spacecraft.name">
      <MapModalUnitCard :spacecraft="spacecraft" v-model="form[spacecraft.name]" />
    </div>
  </div>
</template>

<style scoped>
.dynamic-grid {
  display: grid;
  grid-template-columns: repeat(var(--grid-cols, 5), 1fr);
}
</style>

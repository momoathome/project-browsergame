<script lang="ts" setup>
import { computed } from 'vue';
import type { Asteroid } from '@/types/types';

const props = defineProps<{
  asteroid: Asteroid;
  showResources?: boolean;
}>();

const resourceColors: Record<string, string> = {
  Carbon: 'darkred',
  Titanium: 'silver',
  Hydrogenium: 'cyan',
  Kyberkristall: 'deeppink',
  Cobalt: 'dodgerblue',
  Iridium: 'slategray',
  Uraninite: 'green',
  Thorium: 'yellowgreen',
  Astatine: 'gold',
  Hyperdiamond: 'navy',
  Dilithium: 'purple',
  Deuterium: 'orangered',
};

const circleRadius = 40;
const circumference = 2 * Math.PI * circleRadius;

const filteredResources = computed(() => {
  if (!props.asteroid || !props.asteroid.resources) return [];
  return props.asteroid.resources
    .filter(res => res.amount > 0)
    .map(res => ({
      name: res.resource_type,
      amount: res.amount,
      color: props.showResources
        ? resourceColors[res.resource_type] || 'grey'
        : 'grey',
    }));
});

const totalResources = computed(() =>
  filteredResources.value.reduce((sum, res) => sum + res.amount, 0)
);

const ringData = computed(() => {
  const gap = 2;
  let offset = filteredResources.value.length <= 2 ? 0 : 8;
  return filteredResources.value.map(res => {
    const dash = Math.round((res.amount / totalResources.value) * circumference);
    const data = {
      color: res.color,
      dashArray: `${dash},${circumference}`,
      dashOffset: offset,
    };
    offset -= dash + gap;
    return data;
  });
});

const asteroidResourceStrokeWidth = computed(() => {
  const total = totalResources.value;
  if (total >= 10000) return 4;
  if (total >= 5500) return 3;
  if (total >= 2500) return 2;
  if (total >= 1000) return 1.5;
  return 1;
});
</script>

<template>
  <div class="flex items-center justify-center w-full h-full">
    <svg :width="500" :height="500" viewBox="0 0 100 100">
      <circle
        v-for="(ring, idx) in ringData"
        :key="idx"
        cx="50"
        cy="50"
        :r="circleRadius"
        :stroke="ring.color"
        :stroke-dasharray="ring.dashArray"
        :stroke-dashoffset="ring.dashOffset"
        :stroke-width="asteroidResourceStrokeWidth"
        fill="transparent"
      />
    </svg>
  </div>
</template>

<style scoped>
svg {
  display: block;
}
</style>

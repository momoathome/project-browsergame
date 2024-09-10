<script lang="ts" setup>
import { ref, computed, onMounted } from 'vue';
import type { Asteroid } from '@/types/types';

interface Resource {
  name: string;
  amount: number;
  color: string;
}

const props = defineProps<{
  asteroid: Asteroid;
}>();

const resourceColors: Record<string, string> = {
  Titanium: 'silver',
  Carbon: 'darkred',
  Kyberkristall: 'deeppink',
  Hydrogenium: 'cyan',
  Uraninite: 'green',
  Cobalt: 'dodgerblue',
  Iridium: 'slategray',
  Astatine: 'gold',
  Thorium: 'yellowgreen',
  Hyperdiamond: 'navy',
  Dilithium: 'purple',
  Deuterium: 'orangered',
  // Weitere Ressourcenfarben hier hinzufügen
};

const formattedResources = computed<Resource[]>(() => {
  return props.asteroid.resources.map(resource => {
    return {
      name: resource.resource_type,
      amount: resource.amount,
      color: resourceColors[resource.resource_type] || 'grey',
    };
  });
});

const filteredResources = computed(() => formattedResources.value.filter(res => res.amount > 0));
const totalResources = computed(() => filteredResources.value.reduce((total, res) => total + res.amount, 0));

const dashArrays = ref<number[]>([]);
const dashOffsets = ref<number[]>([]);

const circleRadius = 30;
const circumference = Math.floor(2 * Math.PI * circleRadius / 2);
const asteroidRessourceStrokeWidth = ref(2);

const calcDashArrayAndOffset = () => {
  let currentOffset = 0;
  const gap = 2;

  dashArrays.value = filteredResources.value.map(res => {
    const dashArray = Math.round((res.amount / totalResources.value) * circumference);
    dashOffsets.value.push(currentOffset);
    currentOffset -= dashArray + gap;
    return dashArray;
  });
};

const getAsteroidResourceStrokeWidth = () => {
  const total = totalResources.value;
  if (total >= 10000) {
    return 4;
  } else if (total >= 5500) {
    return 3;
  } else if (total >= 2500) {
    return 2;
  } else if (total >= 1000) {
    return 1.5;
  } else {
    return 1; // Standardwert
  }
};

onMounted(() => {
  calcDashArrayAndOffset();
  asteroidRessourceStrokeWidth.value = getAsteroidResourceStrokeWidth()
});
</script>

<template>
  <div class="absolute -top-[130px] -left-[120px]">
    <svg viewBox="0 0 100 100">
      <circle v-for="(res, index) in filteredResources"
        :key="res.name" 
        cx="50" 
        cy="50" 
        :r="circleRadius"
        :stroke="res.color" 
        :stroke-dasharray="dashArrays[index] + ', 284'" 
        :stroke-dashoffset="dashOffsets[index]">
      </circle>
    </svg>
  </div>
</template>

<style scoped>
svg {
  width: 500px;
  height: 500px;
  transform: rotate(180deg);
}

circle {
  stroke-width: v-bind(asteroidRessourceStrokeWidth + 'px');
  fill: transparent;
}

@keyframes animateRessource {
  from {
    stroke-dasharray: 0, 284;
  }

  to {
    opacity: 1;
  }
}

@keyframes animateTitanium {
  from {
    stroke-dasharray: 0, 284;
  }

  to {
    stroke-dasharray: var(--dash-array), 284;
    opacity: 1;
  }
}
</style>

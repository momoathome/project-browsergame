<script lang="ts" setup>
import { ref, computed, onMounted } from 'vue';
import type { Asteroid } from '@/types/types';

// Datenstruktur anpassen
interface Ressource {
  name: string;
  value: number;
  color: string;
}

const props = defineProps<{
  asteroid: Asteroid;
}>();

const ressources = ref<Ressource[]>([
  { name: 'titanium', value: props.asteroid.resources.titanium ?? 0, color: 'silver' },
  { name: 'carbon', value: props.asteroid.resources.carbon ?? 0, color: 'darkred' },
  { name: 'kyberkristall', value: props.asteroid.resources.kyberkristall ?? 0, color: 'deeppink' },
  { name: 'hydrogenium', value: props.asteroid.resources.hydrogenium ?? 0, color: 'cyan' },
  { name: 'uraninite', value: props.asteroid.resources.uraninite ?? 0, color: 'green' },
  { name: 'cobalt', value: props.asteroid.resources.cobalt ?? 0, color: 'dodgerblue' },
  { name: 'iridium', value: props.asteroid.resources.iridium ?? 0, color: 'gray' },
  { name: 'astatine', value: props.asteroid.resources.astatine ?? 0, color: 'gold' },
  { name: 'thorium', value: props.asteroid.resources.thorium ?? 0, color: 'yellowgreen' },
  { name: 'hyperdiamond', value: props.asteroid.resources.hyperdiamond ?? 0, color: 'mediumblue' },
  { name: 'dilithium', value: props.asteroid.resources.dilithium ?? 0, color: 'purple' },
  { name: 'deuterium', value: props.asteroid.resources.deuterium ?? 0, color: 'orangered' },
  // Weitere Ressourcen können hier hinzugefügt werden
]);

const filteredRessources = computed(() => ressources.value.filter(res => res.value > 0));

const circleRadius = 35;
const circumference = Math.floor(2 * Math.PI * circleRadius / 2);

const totalRessources = computed(() => ressources.value.reduce((total, res) => total + res.value, 0));

const dashArrays = ref<number[]>([]);
const dashOffsets = ref<number[]>([]);

const calcDashArrayAndOffset = () => {
  let currentOffset = 0;
  const gap = 2;

  dashArrays.value = filteredRessources.value
  .map((res) => {
    const dashArray = Math.round((res.value / totalRessources.value) * circumference);
    dashOffsets.value.push(currentOffset);
    currentOffset -= dashArray + gap;
    return dashArray;
  });
};

onMounted(() => {
  calcDashArrayAndOffset();
  console.log(dashArrays.value);
  console.log(dashOffsets.value);
});
</script>

<template>
  <div class="absolute -bottom-[60px] -left-[60px]"> 
    <svg viewBox="0 0 100 100">
      <circle
        v-for="(res, index) in filteredRessources"
        :key="res.name"
        cx="50"
        cy="50"
        r="35"
        :stroke="res.color"
        :stroke-dasharray="dashArrays[index] + ', 284'"
        :stroke-dashoffset="dashOffsets[index]"
      />
    </svg>
  </div>
</template>

<style scoped>
svg {
  height: 250px;
  width: 250px;
  transform: rotate(177deg);
}

circle {
  stroke-width: 2px;
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

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
  { name: 'Titanium', value: props.asteroid.resources.Titanium ?? 0, color: 'silver' },
  { name: 'Carbon', value: props.asteroid.resources.Carbon ?? 0, color: 'darkred' },
  { name: 'Kyberkristall', value: props.asteroid.resources.Kyberkristall ?? 0, color: 'deeppink' },
  { name: 'Hydrogenium', value: props.asteroid.resources.Hydrogenium ?? 0, color: 'cyan' },
  { name: 'Uraninite', value: props.asteroid.resources.Uraninite ?? 0, color: 'green' },
  { name: 'Cobalt', value: props.asteroid.resources.Cobalt ?? 0, color: 'dodgerblue' },
  { name: 'Iridium', value: props.asteroid.resources.Iridium ?? 0, color: 'slategray' },
  { name: 'Astatine', value: props.asteroid.resources.Astatine ?? 0, color: 'gold' },
  { name: 'Thorium', value: props.asteroid.resources.Thorium ?? 0, color: 'yellowgreen' },
  { name: 'Hyperdiamond', value: props.asteroid.resources.Hyperdiamond ?? 0, color: 'navy' },
  { name: 'Dilithium', value: props.asteroid.resources.Dilithium ?? 0, color: 'purple' },
  { name: 'Deuterium', value: props.asteroid.resources.Deuterium ?? 0, color: 'orangered' },
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
        >
        <!-- <title>{{ res.name }}</title> -->
      </circle>
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

<script lang="ts" setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue';

interface Resource {
  name: string;
  amount: number;
  color: string;
}

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
  // Weitere Ressourcenfarben hier hinzufügen
};

const props = defineProps<{
  universeResources: Resource[];
}>()

const formattedResources = computed(() => {
  return Object.entries(props.universeResources)
    .map(([name, amounts]) => {
      const totalAmount = amounts.reduce((acc, amount) => acc + amount, 0);
      const percentage = Math.round((totalAmount / 1000000) * 100);
      const color = resourceColors[name] || 'grey';

      return {
        name,
        amount: totalAmount,
        percentage,
        color,
      };
    })
    .sort((a, b) => {
      const colorKeysOrder = Object.keys(resourceColors);
      return colorKeysOrder.indexOf(a.name) - colorKeysOrder.indexOf(b.name);
    });
});

</script>

<template>
  <AppLayout title="dashboard">

    <h1 class="text-3xl font-bold">
      Dashboard
    </h1>

    <div class="flex flex-col gap-6 py-4 ms-12">
      <div v-for="res in formattedResources" :key="res.name">
        <div class="flex gap-2">
          <span>{{ res.name }}</span>
          <span>{{ res.amount }}</span>
          <span>{{ res.percentage }}%</span>
        </div>
        <div :style="`background-color: ${res.color}; width: ${res.percentage + '%'}`"
          class="progress-bar h-2 rounded-lg transition transition-duration-300"></div>
      </div>
    </div>
  </AppLayout>
</template>

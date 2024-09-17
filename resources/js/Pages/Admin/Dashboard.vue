<script lang="ts" setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue';
import { numberFormat } from '@/Utils/format';

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
  const resources = Object.entries(props.universeResources);
  const totalResourceAmount = resources.reduce((acc, [_, amounts]) => 
    acc + amounts.reduce((sum, amount) => sum + amount, 0), 0);

  const formattedData = resources
    .map(([name, amounts]) => {
      const amount = amounts.reduce((acc, amount) => acc + amount, 0);
      const percentage = ((amount / totalResourceAmount) * 100).toFixed(2);
      const color = resourceColors[name] || 'grey';

      return {
        name,
        amount,
        percentage,
        color,
      };
    })
    .sort((a, b) => {
      const colorKeysOrder = Object.keys(resourceColors);
      return colorKeysOrder.indexOf(a.name) - colorKeysOrder.indexOf(b.name);
    });

  return {
    resources: formattedData,
    totalResourceAmount
  };
});

</script>

<template>
  <AppLayout title="dashboard">

    <h1 class="text-3xl font-bold">
      Dashboard
    </h1>

    <div class="flex flex-col gap-2 py-4 ms-12">
      <div class="flex gap-2">
        <span>Total Resources</span>
        <span>{{ numberFormat(formattedResources.totalResourceAmount) }}</span>
      </div>
      <div v-for="res in formattedResources.resources" :key="res.name">
        <div class="flex gap-2 text-sm">
          <span>{{ res.name }}</span>
          <span>{{ numberFormat(res.amount) }}</span>
          <span>{{ res.percentage }}%</span>
        </div>
        <div :style="`background-color: ${res.color}; width: ${res.percentage + '%'}`"
          class="h-2 rounded-lg"></div>
      </div>
    </div>
  </AppLayout>
</template>

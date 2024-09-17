<script lang="ts" setup>
import { computed } from 'vue'
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
      const percentage = Number(((amount / totalResourceAmount) * 100).toFixed(2));
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
  <div class="bg-primary rounded-xl p-4 text-light font-medium">
    <div class="flex gap-2 font-bold">
      <span>Total Resources</span>
      <span>{{ numberFormat(formattedResources.totalResourceAmount) }}</span>
    </div>
    <div class="flex flex-col py-4">
      <div v-for="res in formattedResources.resources" :key="res.name" class="hover:bg-base transition duration-300 px-4 py-1 rounded-3xl">
        <div class="flex gap-2 text-sm">
          <span>{{ res.name }}</span>
          <span>{{ numberFormat(res.amount) }}</span>
          <span>{{ res.percentage }}%</span>
        </div>
        <div :style="`background-color: ${res.color}; width: ${res.percentage * 2}%`" class="h-2 rounded-lg"></div>
      </div>
    </div>
  </div>
</template>

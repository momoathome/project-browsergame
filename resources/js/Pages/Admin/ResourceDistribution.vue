<script lang="ts" setup>
import { computed, onMounted } from 'vue'
import { usePage, Link } from '@inertiajs/vue3';
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
  const resources = props.universeResources;
  const totalResourceAmount = resources.reduce((acc, res) => acc + res.amount, 0);

  const formattedData = resources
    .map(res => {
      const percentage = Number(((res.amount / totalResourceAmount) * 100).toFixed(2));
      const color = resourceColors[res.name] || 'grey';

      return {
        name: res.name,
        amount: res.amount,
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
  <div class="bg-base h-dvh p-4 text-light font-medium">
    <div class="flex items-center mb-4">
      <Link :href="route('admin.dashboard')"
          class="bg-primary text-white py-2 px-4 rounded-md hover:bg-base-dark transition">
      Zurück
      </Link>
    </div>

    <div class="flex gap-2 font-bold">
      <span>Total Resources</span>
      <span>{{ numberFormat(formattedResources.totalResourceAmount) }}</span>
    </div>
    <div class="flex flex-col py-4">
      <div
        v-for="res in formattedResources.resources"
        :key="res.name"
        class="relative mb-2"
        style="height: 32px;"
      >
        <div
          :style="`background-color: ${res.color}; width: ${res.percentage * 2}%`"
          class="h-full rounded-lg transition-all duration-300"
          :title="`${res.name}: ${numberFormat(res.amount)} (${res.percentage}%)`"
        ></div>
        <div class="absolute left-2 top-1 text-sm font-bold flex gap-2 items-center">
          <span>{{ res.name }}</span>
          <span>{{ numberFormat(res.amount) }}</span>
          <span>({{ res.percentage }}%)</span>
        </div>
      </div>
    </div>
  </div>
</template>

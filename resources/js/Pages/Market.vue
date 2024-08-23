<script lang="ts" setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import MarketCard from '@/Modules/Market/MarketCard.vue';

const props = defineProps(['market']);

const formattedResources = computed(() => {
  return props.market.map((market: market) => {
    return {
      id: market.id,
      resource_id: market.resource_id,
      name: market.resource.name,
      description: market.resource.description,
      image: market.resource.image,
      cost: market.cost,
      stock: market.stock,
    };
  });
});

function sellResource(params:type) {
  // TODO: Implement
}
function buyResource(params:type) {
  // TODO: Implement
}
</script>

<template>
  <AppLayout title="market">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Market
      </h2>
    </template>

    <div class="grid gap-4 lg:gap-8 p-16">
      <MarketCard v-for="data in formattedResources" :key="data.id" :marketData="data" @buy="buyResource" @sell="sellResource" />
    </div>
  </AppLayout>
</template>

<style scoped>
.grid {
  grid-template-columns: repeat(auto-fit, 220px)
}
</style>

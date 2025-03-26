<script lang="ts" setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import MarketCard from '@/Modules/Market/MarketCard.vue';
import type { Market, formattedMarketResource } from '@/types/types';

const props = defineProps<{
  market: Market[]
}>()

const formattedResources = computed(() => {
  return props.market.map((market: Market): formattedMarketResource => {
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
</script>

<template>
  <AppLayout title="market">
    <div class="grid gap-4 lg:gap-8">
      <MarketCard v-for="resource in formattedResources" :key="resource.id" :marketData="resource" />
    </div>
  </AppLayout>
</template>

<style scoped>
.grid {
  grid-template-columns: repeat(auto-fit, minmax(230px, 250px)); 
}
</style>

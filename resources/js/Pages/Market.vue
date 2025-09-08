<script lang="ts" setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import MarketCard from '@/Modules/Market/MarketCard.vue';
import type { Market, formattedMarketResource } from '@/types/types';

const props = defineProps<{
  market: Market[],
  prefill_resource_ids?: string,
  prefill_amounts?: string
}>()

const prefillMap = computed(() => {
  if (!props.prefill_resource_ids || !props.prefill_amounts) return {};
  const ids = props.prefill_resource_ids.split(',').map(Number);
  const amounts = props.prefill_amounts.split(',').map(Number);
  return ids.reduce((acc, id, idx) => {
    acc[id] = amounts[idx] || 0;
    return acc;
  }, {} as Record<number, number>);
});

const formattedResources = computed(() => {
  return props.market.map((market: Market): formattedMarketResource => ({
    id: market.id,
    resource_id: market.resource_id,
    name: market.resource.name,
    description: market.resource.description,
    image: market.resource.image,
    cost: market.cost,
    stock: market.stock,
    prefill: prefillMap.value[market.resource_id] || undefined,
  }));
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
  --grid-min-col-size: 260px;

  grid-template-columns: repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}
</style>

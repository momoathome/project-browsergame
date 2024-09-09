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
</script>

<template>
  <AppLayout title="market">
    <div class="grid gap-4 lg:gap-8 ps-4 py-8 me-20">
      <MarketCard v-for="data in formattedResources" :key="data.id" :marketData="data" />
    </div>
  </AppLayout>
</template>

<style scoped>
.grid {
  grid-template-columns: repeat(auto-fit, minmax(230px, 250px)); 
}
</style>

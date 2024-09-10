<script lang="ts" setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SpacecraftsCard from '@/Modules/Spacecrafts/SpacecraftsCard.vue';
import type { Spacecraft } from '@/types/types';

const props = defineProps(['spacecrafts']);

const formattedSpacecrafts = computed(() => {
  return props.spacecrafts.map((spacecraft: Spacecraft) => {
    return {
      id: spacecraft.id,
      image: spacecraft.details.image,
      name: spacecraft.details.name,
      description: spacecraft.details.description,
      type: spacecraft.details.type,
      combat: spacecraft.combat,
      count: spacecraft.count,
      cargo: spacecraft.cargo,
      build_time: spacecraft.build_time,
      unit_limit: spacecraft.unit_limit,
      unlocked: spacecraft.unlocked,
      research_cost: spacecraft.research_cost,
      resources: spacecraft.resources.map((resource) => ({
        id: resource.id,
        name: resource.name,
        image: resource.image,
        amount: resource.pivot.amount
      }))
    };
  });
});
</script>

<template>
  <AppLayout title="spacecrafts">
    <div class="grid gap-4 lg:gap-8 ps-4 py-8 me-20">
      <SpacecraftsCard v-for="data in formattedSpacecrafts" :key="data.id" :spacecraft="data" />
    </div>
  </AppLayout>
</template>

<style scoped>
.grid {
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

@media (min-width: 2600px) {
  .grid {
    grid-template-columns: repeat(6, 1fr);
    max-width: 2600px; 
    margin-left: auto;
    margin-right: auto;
  }
}
</style>

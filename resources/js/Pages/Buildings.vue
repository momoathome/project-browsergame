<script lang="ts" setup>

import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BuildingsCard from '@/Modules/Buildings/BuildingsCard.vue';
import type { Building } from '@/types/types';

const props = defineProps(['buildings']);

const formattedBuildings = computed(() => {
  return props.buildings.map((building: Building) => {
    return {
      id: building.id,
      image: building.details.image,
      name: building.details.name,
      description: building.details.description,
      level: building.level,
      build_time: building.build_time,
      resources: building.resources.map((resource) => ({
        name: resource.name,
        image: resource.image,
        amount: resource.pivot.amount
      }))
        .sort((a, b) => a.name.localeCompare(b.name))
    };
  });
});

</script>

<template>
  <AppLayout title="buildings">
    <div class="grid gap-4 lg:gap-8 ps-4 py-8 me-20">
      <BuildingsCard v-for="data in formattedBuildings" :key="data.id" :moduleData="data" />
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

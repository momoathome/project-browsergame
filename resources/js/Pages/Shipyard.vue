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
      buildTime: spacecraft.buildTime,
      resources: spacecraft.resources.map((resource) => ({
        name: resource.name,
        image: resource.image,
        amount: resource.pivot.amount
      }))
    };
  });
});

function produceSpacecraft() {
  // TODO: implement
}
</script>

<template>
  <AppLayout title="spacecrafts">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Spacecrafts
      </h2>
    </template>

    <div class="grid gap-4 lg:gap-8 p-16">
      <SpacecraftsCard v-for="data in formattedSpacecrafts" :key="data.id" :spacecraftData="data" @produce="produceSpacecraft" />
    </div>
  </AppLayout>
</template>

<style scoped>
.grid {
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}
</style>

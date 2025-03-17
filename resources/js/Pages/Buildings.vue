<script lang="ts" setup>

import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BuildingsCard from '@/Modules/Buildings/BuildingsCard.vue';
import type { Building} from '@/types/types';
import { useBuildingFormatting } from '@/Composables/useBuildingFormatting';

const props = defineProps<{
  buildings: Building[]
}>()

const { formatBuilding } = useBuildingFormatting();

const formattedBuildings = computed(() => {
  return props.buildings.map(building => formatBuilding(building));
});

</script>

<template>
  <AppLayout title="buildings">
    <div class="grid gap-4 lg:gap-8 ps-4 py-8 me-20">
      <BuildingsCard v-for="building in formattedBuildings" :key="building.id" :building="building" />
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

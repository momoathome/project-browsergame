<script lang="ts" setup>

import AppLayout from '@/Layouts/AppLayout.vue';
import BuildingsCard from '@/Modules/Buildings/BuildingsCard.vue';
import type { Building} from '@/types/types';

const props = defineProps<{
  buildings: Building[]
}>()

</script>

<template>
  <AppLayout title="buildings">
    <div class="grid gap-4 lg:gap-8">
      <BuildingsCard v-for="building in buildings" :key="building.id" :building="building" />
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

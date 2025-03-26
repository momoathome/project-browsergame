<script lang="ts" setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SpacecraftsCard from '@/Modules/Spacecrafts/SpacecraftsCard.vue';
import type { Spacecraft } from '@/types/types';
import { useSpacecraftFormatting } from '@/Composables/useSpacecraftFormatting';

const props = defineProps<{
  spacecrafts: Spacecraft[]
}>()

const { formatSpacecraft } = useSpacecraftFormatting();

const formattedSpacecrafts = computed(() => {
  return props.spacecrafts.map(formatSpacecraft);
});
</script>

<template>
  <AppLayout title="spacecrafts">
    <div class="grid gap-4 lg:gap-8 px-4 py-8">
      <SpacecraftsCard v-for="spacecraft in formattedSpacecrafts" :key="spacecraft.id" :spacecraft="spacecraft" />
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

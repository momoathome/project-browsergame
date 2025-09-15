<script lang="ts" setup>
import { computed, ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SpacecraftsCard from '@/Modules/Spacecrafts/SpacecraftsCard.vue';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';
import type { Spacecraft } from '@/types/types';

const spacecraftStore = useSpacecraftStore();
const { spacecrafts } = spacecraftStore;
const page = usePage();

// Store immer mit aktuellen Props initialisieren
onMounted(() => {
  if (Array.isArray(page.props.spacecrafts)) {
    spacecrafts.value = page.props.spacecrafts;
  }
});
</script>

<template>
  <AppLayout title="spacecrafts">
    <div class="grid gap-4 lg:gap-x-8">
      <SpacecraftsCard v-for="spacecraft in spacecrafts" :key="spacecraft.id" :spacecraft="spacecraft" />
    </div>
  </AppLayout>
</template>

<style scoped>
.grid {
  --grid-min-col-size: 265px;

  grid-template-columns: repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr));
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

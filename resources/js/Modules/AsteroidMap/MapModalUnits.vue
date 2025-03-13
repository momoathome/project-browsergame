<script lang="ts" setup>
import { computed } from 'vue';
import type { Spacecraft } from '@/types/types';
import MapModalUnitCard from './MapModalUnitCard.vue';

const props = defineProps<{
  spacecrafts: Spacecraft[];
}>();

const form = defineModel({ required: false, type: Object })

// filter spacecrafts based on is unlocked status

const unlockedSpacecrafts = computed(() => {
  return props.spacecrafts.filter((spacecraft) => spacecraft.unlocked);
});
</script>

<template>
  <div class="flex items-center text-base">
    <div class="flex flex-col gap-6">
      <div class="flex flex-col gap-6">
        <div class="grid grid-cols-5 gap-4">
          <div class="flex items-center relative" v-for="spacecraft in unlockedSpacecrafts" :key="spacecraft.details.name">
            <MapModalUnitCard :spacecraft="spacecraft" v-model="form[spacecraft.details.name]" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

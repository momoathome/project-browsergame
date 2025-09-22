<script lang="ts" setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import SpacecraftsCard from '@/Modules/Spacecrafts/SpacecraftsCard.vue';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';
import { useQueueStore } from '@/Composables/useQueueStore';
import type { Spacecraft } from '@/types/types';

import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TertiaryButton from '@/Components/TertiaryButton.vue';

const { spacecrafts, refreshSpacecrafts } = useSpacecraftStore();
const { queueData, refreshQueue } = useQueueStore();

const unlockedSpacecrafts = computed(() => {
  return spacecrafts.value.filter(spacecraft => spacecraft.unlocked);
});

const showCancelModal = ref(false);
const selectedSpacecraft = ref<Spacecraft | null>(null);

function getActiveProduction(spacecraft: Spacecraft | null) {
  if (!spacecraft) return 0;
  if (spacecraft.is_producing && spacecraft.currently_producing) {
    return spacecraft.currently_producing;
  }
  return 0;
}

const queuedCrewMap = computed(() => {
  const map: Record<number, number> = {};
  (queueData.value ?? []).forEach(item => {
    if (
      item.actionType === 'produce' &&
      item.details?.quantity &&
      (item.status === 'in_progress' || item.status === 'pending') &&
      typeof item.targetId === 'number'
    ) {
      // Das passende Spacecraft suchen:
      const spacecraft = spacecrafts.value.find(sc => sc.id === item.targetId);
      if (spacecraft) {
        map[item.targetId] = (map[item.targetId] || 0) + (spacecraft.crew_limit * item.details.quantity);
      }
    }
  });
  return map;
});

function handleOpenCancelModal(spacecraft: Spacecraft) {
  selectedSpacecraft.value = spacecraft;
  showCancelModal.value = true;
}

function handleCancelProduction() {
  if (!selectedSpacecraft.value) return;
  
  router.delete(route('shipyard.cancel', selectedSpacecraft.value.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      refreshQueue();
      refreshSpacecrafts();
      showCancelModal.value = false;
      selectedSpacecraft.value = null;
    }
  });
}

function handleProduceSpacecraft() {
  refreshQueue(); 
  refreshSpacecrafts();
}
</script>

<template>
  <div class="grid gap-6 lg:gap-x-10 lg:gap-y-6">
    <SpacecraftsCard v-for="spacecraft in unlockedSpacecrafts" 
    :key="spacecraft.id" 
    :spacecraft="spacecraft"
    :queued-crew="queuedCrewMap[spacecraft.id] || 0"
    @open-cancel-modal="handleOpenCancelModal"
    @produce-spacecraft="handleProduceSpacecraft" 
    />
  </div>

  <DialogModal :show="showCancelModal" @close="showCancelModal = false" class="bg-slate-950/70 backdrop-blur-sm">
    <template #title>Cancel Production</template>
    <template #content>
      <p v-if="selectedSpacecraft">Are you sure you want to cancel the production of
        <span class="font-semibold">
          {{ getActiveProduction(selectedSpacecraft) }} {{ selectedSpacecraft.name }}
        </span> 
          ?
        </p>
      <p class="text-gray-400 mt-2">You will lose all progress and 80% of resources spent on this production.</p>
    </template>
    <template #footer>
      <div class="flex justify-end gap-4">
        <TertiaryButton @click="showCancelModal = false">No, Keep Producing</TertiaryButton>
        <SecondaryButton @click="handleCancelProduction">Yes, Cancel Production</SecondaryButton>
      </div>
    </template>
  </DialogModal>
</template>

<style scoped>
.grid {
  --grid-min-col-size: 270px;

  grid-template-columns: repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}
</style>

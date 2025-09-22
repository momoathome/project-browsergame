<script lang="ts" setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import BuildingsCard from '@/Modules/Buildings/BuildingsCard.vue';
import { useBuildingStore } from '@/Composables/useBuildingStore';
import { useQueueStore } from '@/Composables/useQueueStore';
import type { Building } from '@/types/types';

import SecondaryButton from '@/Components/SecondaryButton.vue';
import TertiaryButton from '@/Components/TertiaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import BuildingsInfoModal from '@/Modules/Buildings/BuildingsInfoModal.vue';

const { buildings, refreshBuildings } = useBuildingStore();
const { queueData, refreshQueue } = useQueueStore();

const coreBuilding = computed(() => buildings.value.find(b => b.name === 'Core'));

function buildingQueueCount(building: Building | null) {
  if (!building) return 0;
  return (queueData.value ?? []).filter(q =>
    q.targetId === building.id &&
    q.actionType === 'building'
  ).length;
}

function nextUpgradeLevel(building: Building) {
  return building.level + buildingQueueCount(building) + 1;
}

function isUpgradeBlocked(building: Building) {
  if (!coreBuilding.value) return false;
  return building.name !== 'Core' && nextUpgradeLevel(building) > coreBuilding.value.level;
}

const showCancelModal = ref(false);
const showInfoModal = ref(false);
const selectedBuilding = ref<Building | null>(null);

function handleOpenCancelModal(building: Building) {
  selectedBuilding.value = building;
  showCancelModal.value = true;
}

function handleCancelUpgrade() {
  if (!selectedBuilding.value) return;

  router.delete(route('buildings.cancel', selectedBuilding.value.id), {
    preserveState: true,
    onFinish: () => {
      refreshBuildings();
      refreshQueue();
      showCancelModal.value = false;
      selectedBuilding.value = null;
    },
    onError: () => { }
  });
}

const isSubmitting = ref(false);

function handleUpgradeBuilding(building: Building) {
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  selectedBuilding.value = building;

  upgradeBuilding();
}

function upgradeBuilding() {
  if (!selectedBuilding.value) return;

  router.post(route('buildings.update', selectedBuilding.value.id),
    {},
    {
      preserveState: true,
      onFinish: () => { 
        refreshQueue();
        refreshBuildings();
        selectedBuilding.value = null;
        isSubmitting.value = false;
      },
      onError: () => { 
        isSubmitting.value = false;
        selectedBuilding.value = null;
      }
    }
  );
}

function handleOpenInfoModal(building: Building) {
  showInfoModal.value = true;
  selectedBuilding.value = building;
}

function handleCloseInfoModal() {
  showInfoModal.value = false;
  selectedBuilding.value = null;
}

</script>

<template>
  <div class="grid gap-6 lg:gap-x-10 lg:gap-y-6">
    <BuildingsCard 
      v-for="building in buildings" 
      :key="building.id" 
      :building="building"
      :is-core-upgrade-blocked="isUpgradeBlocked(building)"
      :next-upgrade-level="nextUpgradeLevel(building)"
      @upgrade-building="handleUpgradeBuilding" 
      @open-cancel-modal="handleOpenCancelModal"
      @open-info-modal="handleOpenInfoModal"
      />
  </div>

  <BuildingsInfoModal 
    :show="showInfoModal" 
    :buildings="buildings ?? []" 
    :initial-building-id="selectedBuilding ? selectedBuilding.id : undefined"
    @close="handleCloseInfoModal"
    />

  <DialogModal :show="showCancelModal" @close="showCancelModal = false" class="bg-slate-950/70 backdrop-blur-sm">
    <template #title>Cancel Upgrade</template>
    <template #content>
      <p v-if="selectedBuilding">
        Are you sure you want to cancel
        <span v-if="buildingQueueCount(selectedBuilding) > 1">
          upgrades? This will cancel <b>all {{ buildingQueueCount(selectedBuilding) }}</b> planned upgrades for this building.
        </span>
        <span v-else>
          the upgrade of <b>{{ selectedBuilding.name }}</b>?
        </span>
      </p>
      <p class="text-gray-400 mt-2">
        <span v-if="buildingQueueCount(selectedBuilding) > 1">
          You will lose all progress and get 80% of resources back for the active upgrade, and 100% for pending upgrades.
        </span>
        <span v-else>
          You will lose all progress and 80% of resources spent on this upgrade.
        </span>
      </p>
    </template>
    <template #footer>
      <div class="flex justify-end gap-4">
        <TertiaryButton @click="showCancelModal = false">No, Keep Upgrading</TertiaryButton>
        <SecondaryButton @click="handleCancelUpgrade">Yes, Cancel Upgrade</SecondaryButton>
      </div>
    </template>
  </DialogModal>

</template>

<style scoped>
.grid {
  --grid-min-col-size: 272px;

  grid-template-columns: repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}
</style>

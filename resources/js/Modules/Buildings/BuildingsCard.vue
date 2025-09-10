<script lang="ts" setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Divider from '@/Components/Divider.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TertiaryButton from '@/Components/TertiaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import AppCardTimer from '@/Modules/Shared/AppCardTimer.vue';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import { useQueueStore } from '@/Composables/useQueueStore';
import { numberFormat } from '@/Utils/format';
import type { Building } from '@/types/types';

const { queueData, refreshQueue } = useQueueStore();

const props = defineProps<{
  building: Building
}>();

const isUpgrading = computed(() => props.building.is_upgrading || false);
const upgradeEndTime = computed(() => props.building.end_time || null);
const isSubmitting = ref(false)
const showCancelModal = ref(false);

const currentEffect = computed(() => {
  const effects = props.building.effect?.current;
  if (!effects || effects.length === 0) return null;
  return effects[0].effect;
});

const nextLevelEffect = computed(() => {
  const effects = props.building.effect?.next_level;
  if (!effects || effects.length === 0) return null;
  return effects[0].effect;
});

const formattedEffectText = computed(() => {
  return currentEffect.value ? currentEffect.value.text : '';
});

const formattedEffectValue = computed(() => {
  return currentEffect.value ? currentEffect.value.value : '';
});

const formattedNextLevelValue = computed(() => {
  return nextLevelEffect.value ? nextLevelEffect.value.value : '';
});

const insufficientResources = computed(() => {
  const userResources = usePage().props.userResources;
  const buildingResources = props.building.resources;

  return buildingResources.map(resource => {
    const userResource = userResources.find(ur => ur.resource_id === resource.id);
    if (!userResource) return { id: resource.id, sufficient: false };
    return {
      id: resource.id,
      sufficient: userResource.amount >= resource.amount
    };
  });
});

function isResourceSufficient(resourceId: number): boolean {
  const resourceStatus = insufficientResources.value.find(res => res.id === resourceId);
  return resourceStatus ? resourceStatus.sufficient : false;
}

function goToMarketWithMissingResources() {
  const userResources = usePage().props.userResources;
  const missing = props.building.resources
    .map(resource => {
      const userResource = userResources.find((ur: any) => ur.id === resource.id);
      const missingAmount = resource.amount - (userResource?.amount || 0);
      return missingAmount > 0
        ? { id: resource.id, amount: missingAmount }
        : null;
    })
    .filter((item): item is { id: number; amount: number } => item !== null);

  if (missing.length === 0) return;

  // Übergib Arrays als Query-Parameter (z.B. resource_ids=1,2&amounts=5,10)
  router.get(route('market', {
    resource_ids: missing.map(r => r.id).join(','),
    amounts: missing.map(r => r.amount).join(','),
  }));
}

const buildingQueueCount = computed(() => {
  return (queueData.value ?? []).filter(q =>
    q.targetId === props.building.id &&
    q.actionType === 'building'
  ).length;
});

const nextUpgradeLevel = computed(() => props.building.level + buildingQueueCount.value + 1);

const canUpgrade = computed(() => {
  // Ressourcen müssen reichen UND Core-Check muss bestanden sein
  console.log('insufficientResources', insufficientResources.value);
  console.log('userResources', usePage().props.userResources);
  console.log('buildingResources', props.building.resources);
  return insufficientResources.value.every(resource => resource.sufficient) && !isCoreUpgradeBlocked.value;
});

const userBuildings = usePage().props.buildings as Building[];
const coreBuilding = computed(() => userBuildings.find(b => b.name === 'Core'));

const isCoreUpgradeBlocked = computed(() => {
  if (!coreBuilding.value) return false;
  return props.building.name !== 'Core' && nextUpgradeLevel.value > coreBuilding.value.level;
});

function upgradeBuilding() {
  if (isSubmitting.value && isCoreUpgradeBlocked.value) return;
  isSubmitting.value = true;
  router.post(
    route('buildings.update', props.building.id),
    {},
    {
      preserveState: true,
      onFinish: () => { isSubmitting.value = false, refreshQueue(); },
      onError: () => { isSubmitting.value = false; }
    }
  );
}

function handleUpgradeComplete() {
  setTimeout(() => {
    router.reload({ only: ['buildings', 'userAttributes'] });
    refreshQueue();
  }, 500);
}

function handleCancelUpgrade() {
  if (isSubmitting.value) return;
  isSubmitting.value = true;

  router.delete(route('buildings.cancel', props.building.id), {
    preserveState: true,
    onFinish: () => {
      isSubmitting.value = false;
      showCancelModal.value = false;
      refreshQueue();
      router.reload({ only: ['buildings', 'userAttributes'] });
    },
    onError: () => { isSubmitting.value = false; }
  });
}
</script>

<template>
  <div class="flex flex-col rounded-xl bg-base content_card text-light">
    <div class="flex justify-between items-center">
      <div class="flex justify-center px-3 py-2">
        <p class="font-semibold text-xl">{{ building.name }}</p>
      </div>
      <div class="flex items-center h-full px-4 rounded-tr-xl bg-primary/25">
        <span class="text-sm font-medium mt-2 me-1 text-secondary">lv.</span>
        <p class="text-xl">{{ building.level }}</p>
      </div>
    </div>

    <div class="image relative">
      <img :src="building.image" class="object-cover aspect-[2/1] h-48" alt="" />
    </div>

    <div class="flex flex-col h-full">
      <div class="flex flex-col h-full">
        <div class="flex flex-col gap-1 px-3 py-2 bg-primary/25">

          <p class="text-sm text-gray">{{ building.description }}</p>

          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-1">
              <span class="text-sm text-secondary">{{ formattedEffectText }}:</span>
              <span class="font-medium text-sm">{{ formattedEffectValue }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                <path fill="currentColor" d="M16.15 13H5q-.425 0-.712-.288T4 12t.288-.712T5 11h11.15L13.3 8.15q-.3-.3-.288-.7t.288-.7q.3-.3.713-.312t.712.287L19.3 11.3q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.575 4.575q-.3.3-.712.288t-.713-.313q-.275-.3-.288-.7t.288-.7z"/>
              </svg>
              <span class="text-green-500 text-sm">{{ formattedNextLevelValue }}</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-4 gap-2 px-2 py-6 min-h-36">
            <div class="relative group flex flex-col gap-1 items-center p-1 h-min" v-for="resource in building.resources"
            :key="resource.name" :class="{ 'cursor-pointer': !isResourceSufficient(resource.id) }"
            @click="!isResourceSufficient(resource.id) && goToMarketWithMissingResources()">
            <img :src="resource.image" class="h-7" alt="resource" />
            <p class="font-medium text-sm" :class="{ 'text-red-600': !isResourceSufficient(resource.id) }">
              {{ numberFormat(resource.amount) }}
            </p>
            <AppTooltip :label="resource.name" position="bottom" class="!mt-1" />
          </div>
        </div>
      </div>

      <div class="flex border-t border-primary/50">
        <AppCardTimer :buildTime="building.build_time" :endTime="upgradeEndTime" :isInProgress="isUpgrading"
          @upgrade-complete="handleUpgradeComplete" @cancel-upgrade="showCancelModal = true"
          :description="`Up to lv. ${building.level + 1}`" />
        <button
          class="px-4 py-2 w-full rounded-br-xl border-t border-primary/40 bg-primary/40 text-light font-semibold transition hover:bg-primary focus:outline-none disabled:hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed"
          @click="upgradeBuilding" :disabled="!canUpgrade || isCoreUpgradeBlocked" type="button">
          <span v-if="isCoreUpgradeBlocked">Upgrade Core first</span>
          <span v-else>Upgrade to lv. {{ nextUpgradeLevel }}</span>
        </button>
      </div>
    </div>

  </div>

    <DialogModal :show="showCancelModal" @close="showCancelModal = false" class="bg-slate-950/70 backdrop-blur-sm">
      <template #title>Cancel Upgrade</template>
      <template #content>
        <p>
          Are you sure you want to cancel
          <span class="font-semibold">{{ building.name }}</span>
          <span v-if="buildingQueueCount > 1">
            upgrades? This will cancel <b>all {{ buildingQueueCount }}</b> planned upgrades for this building.
          </span>
          <span v-else>
            the upgrade of <b>{{ building.name }}</b>?
          </span>
        </p>
        <p class="text-gray-400 mt-2">
          <span v-if="buildingQueueCount > 1">
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
.content_card {
  --shadow-color: 210deg 30% 15%;
  --glow-color: 210deg 70% 50%;

  box-shadow: 1px 1px 1.6px hsl(var(--shadow-color) / 0.3),
    3.5px 3.5px 5.6px -0.8px hsl(var(--shadow-color) / 0.3),
    8.8px 8.8px 14px -1.7px hsl(var(--shadow-color) / 0.35),
    0 0 12px -2px hsl(var(--glow-color) / 0.15);
  border: 1px solid hsl(210deg 30% 25% / 0.5);
}

.image::after {
  content: '';
  position: absolute;
  inset: 0;
  box-shadow: inset 0px -20px 20px 4px #1E2D3B,
    inset 0px -30px 45px 0px #1E2D3B;
  border-radius: 24px 24px 0 0;
}
</style>

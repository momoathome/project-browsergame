<script lang="ts" setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Divider from '@/Components/Divider.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AppCardTimer from '@/Modules/Shared/AppCardTimer.vue';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import type { Building } from '@/types/types';

const props = defineProps<{
  building: Building
}>();

const isUpgrading = computed(() => props.building.is_upgrading || false);
const upgradeEndTime = computed(() => props.building.end_time || null);

const currentEffect = computed(() => {
  if (!props.building.current_effects || props.building.current_effects.length === 0) {
    return null;
  }
  return props.building.current_effects[0];
});

const nextLevelEffect = computed(() => {
  if (!props.building.next_level_effects || props.building.next_level_effects.length === 0) {
    return null;
  }
  return props.building.next_level_effects[0];
});

const formattedEffectValue = computed(() => {
  if (!currentEffect.value) {
    return '';
  }

  return currentEffect.value.display;
});

const formattedNextLevelValue = computed(() => {
  if (!nextLevelEffect.value) {
    return '';
  }

  return nextLevelEffect.value.display;
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
      const userResource = userResources.find((ur: any) => ur.resource_id === resource.id);
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

const canUpgrade = computed(() => {
  return insufficientResources.value.every(resource => resource.sufficient);
});

function upgradeBuilding() {
  if (isUpgrading.value) return;

  router.post(route('buildings.update', props.building.id), {
    preserveState: true,
  });
}

function handleUpgradeComplete() {
  setTimeout(() => {
    router.reload({ only: ['buildings', 'queue', 'userAttributes'] });
  }, 500);
}
</script>

<template>
  <div class="flex flex-col rounded-3xl bg-base content_card text-light">
    <div class="image relative">
      <img :src="building.image" class="rounded-t-3xl object-cover aspect-[2/1] h-[195px]" alt="" />
    </div>
    <div class="px-6 pt-0 pb-6 flex flex-col gap-4 h-full">
      <div class="flex flex-col gap-4">
        <div class="flex justify-between">
          <p class="font-semibold text-2xl">{{ building.name }}</p>
          <div class="flex">
            <span class="text-sm font-medium mt-2 me-1 text-secondary">lv.</span>
            <p class="text-xl">{{ building.level }}</p>
          </div>
        </div>
        <p class="text-gray text-sm">{{ building.description }}</p>
      </div>

      <!-- Neue Effektanzeige basierend auf Backend-Daten -->
      <div v-if="currentEffect" class="flex flex-col gap-1">
        <div class="flex gap-1">
          <span class="text-sm text-secondary">Current Effect:</span>
          <span class="font-medium text-sm">{{ formattedEffectValue }}</span>
        </div>
        <div v-if="nextLevelEffect" class="flex gap-1">
          <span class="text-sm text-secondary">Next Level:</span>
          <span class="font-medium text-sm text-green-400">{{ formattedNextLevelValue }}</span>
        </div>
      </div>

      <Divider />

      <div class="grid grid-cols-4 gap-4 items-center">
        <div
          class="relative group flex flex-col gap-1 items-center"
          v-for="resource in building.resources"
          :key="resource.name"
          :class="{ 'cursor-pointer': !isResourceSufficient(resource.id) }"
          @click="!isResourceSufficient(resource.id) && goToMarketWithMissingResources()"
        >
          <img :src="resource.image" class="h-7" alt="resource" />
          <p class="font-medium text-sm" :class="{ 'text-red-600': !isResourceSufficient(resource.id) }">
            {{ resource.amount }}
          </p>
          <AppTooltip :label="resource.name" position="bottom" class="!mt-1" />
        </div>
      </div>
      <div class="flex flex-col gap-4 mt-auto">
        <div class="flex justify-center my-2">
          <PrimaryButton @click="upgradeBuilding" :disabled="isUpgrading || !canUpgrade">
            <span v-if="isUpgrading" class="flex gap-1 items-center">
              Upgrading
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24">
                <path fill="currentColor" d="M6 17.59L7.41 19L12 14.42L16.59 19L18 17.59l-6-6z" />
                <path fill="currentColor" d="m6 11l1.41 1.41L12 7.83l4.59 4.58L18 11l-6-6z" />
              </svg>
            </span>
            <span v-else>Upgrade</span>
          </PrimaryButton>
        </div>
        <AppCardTimer :buildTime="building.build_time" :endTime="upgradeEndTime" :isInProgress="isUpgrading"
          @upgrade-complete="handleUpgradeComplete" :description="`upgrade to lv. ${building.level + 1}`" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.content_card {
  --shadow-color: 210deg 30% 15%;
  --glow-color: 210deg 70% 50%;

  box-shadow: 1px 1px 1.6px hsl(var(--shadow-color) / 0.3),
    3.5px 3.5px 5.6px -0.8px hsl(var(--shadow-color) / 0.3),
    8.8px 8.8px 14px -1.7px hsl(var(--shadow-color) / 0.35),
    0 0 20px -2px hsl(var(--glow-color) / 0.15);
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

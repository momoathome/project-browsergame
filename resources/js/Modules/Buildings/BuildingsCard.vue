<script lang="ts" setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppCardTimer from '@/Modules/Shared/AppCardTimer.vue';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import { numberFormat } from '@/Utils/format';
import type { Building, UserResources } from '@/types/types';

const props = defineProps<{
  building: Building,
  isCoreUpgradeBlocked: boolean,
  nextUpgradeLevel: number,
  userResources: UserResources[]
}>();

const emit = defineEmits<{
  (e: 'upgrade-building', building: Building): void,
  (e: 'open-cancel-modal', building: Building): void,
  (e: 'open-info-modal', building: Building): void
}>();

// --- Computed Properties ---
const isUpgrading = computed(() => props.building.is_upgrading || false);
const upgradeEndTime = computed(() => props.building.end_time || null);
const userResources = computed(() => props.userResources);

const canUpgrade = computed(() => {
  return insufficientResources.value.every(resource => resource.sufficient) && !props.isCoreUpgradeBlocked;
});

const currentEffect = computed(() => {
  return props.building.effect?.current ?? null;
});

const nextLevelEffect = computed(() => {
  return props.building.effect?.next_level ?? null;
});

const effectKey = computed(() => {
  // Hole den ersten Key aus current (z.B. "production_speed")
  return currentEffect.value ? Object.keys(currentEffect.value)[0] : '';
});

const formattedEffectText = computed(() => {
  // Ersetze "_" durch Leerzeichen und mache den ersten Buchstaben groß
  if (!effectKey.value) return '';
  return effectKey.value.replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase());
});

const formattedEffectValue = computed(() => {
  return currentEffect.value && effectKey.value && Number.isFinite(currentEffect.value[effectKey.value])
    ? numberFormat(currentEffect.value[effectKey.value])
    : currentEffect.value[effectKey.value];
});

const formattedNextLevelValue = computed(() => {
  return nextLevelEffect.value && effectKey.value && Number.isFinite(currentEffect.value[effectKey.value])
    ? numberFormat(nextLevelEffect.value[effectKey.value])
    : nextLevelEffect.value[effectKey.value];
});

const insufficientResources = computed(() => {
  const buildingResources = props.building.resources;

  return buildingResources.map(resource => {
    const userResource = userResources.value.find(ur => ur.resource_id === resource.id);
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
  const missing = props.building.resources
    .map(resource => {
      const userResource = userResources.value.find((ur: any) => ur.resource_id === resource.id);
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

function openCancelModal() {
  if (isUpgrading.value) {
    emit('open-cancel-modal', props.building);
  }
}

function upgradeBuilding() {
  if (!canUpgrade || props.isCoreUpgradeBlocked) return;

  emit('upgrade-building', props.building);
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
        <div class="flex justify-between items-center gap-1 px-3 py-2 bg-primary/25">

           <p class="text-sm text-gray">{{ building.description }}</p>
<!--
          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-1">
              <span class="text-sm text-secondary">{{ formattedEffectText }}:</span>
              <span class="font-medium text-sm">{{ formattedEffectValue }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                <path fill="currentColor" d="M16.15 13H5q-.425 0-.712-.288T4 12t.288-.712T5 11h11.15L13.3 8.15q-.3-.3-.288-.7t.288-.7q.3-.3.713-.312t.712.287L19.3 11.3q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.575 4.575q-.3.3-.712.288t-.713-.313q-.275-.3-.288-.7t.288-.7z"/>
              </svg>
              <span class="text-green-500 text-sm">{{ formattedNextLevelValue }}</span>
            </div>
          </div> -->

          <button class="flex items-center gap-1 cursor-pointer hover:bg-primary/40 group rounded-md px-2 py-1 w-max transition border-transparent border-solid outline-none" 
            @click="$emit('open-info-modal', building)"
            type="button"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 text-secondary" width="20" height="20" viewBox="0 0 24 24">
              <path fill="currentColor" d="M12 17q.425 0 .713-.288T13 16v-4q0-.425-.288-.712T12 11t-.712.288T11 12v4q0 .425.288.713T12 17m0-8q.425 0 .713-.288T13 8t-.288-.712T12 7t-.712.288T11 8t.288.713T12 9m0 13q-2.075 0-3.9-.788t-3.175-2.137T2.788 15.9T2 12t.788-3.9t2.137-3.175T8.1 2.788T12 2t3.9.788t3.175 2.137T21.213 8.1T22 12t-.788 3.9t-2.137 3.175t-3.175 2.138T12 22m0-2q3.35 0 5.675-2.325T20 12t-2.325-5.675T12 4T6.325 6.325T4 12t2.325 5.675T12 20m0-8"/>
            </svg>
<!--             <p class="text-gray text-sm group-hover:text-light transition-colors">details</p> -->
          </button>
        </div>

        <div class="grid grid-cols-4 gap-2 px-2 py-4 min-h-36">
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
        <AppCardTimer 
          :buildTime="isUpgrading ? building.old_build_time : building.build_time" 
          :endTime="upgradeEndTime" 
          :isInProgress="isUpgrading"
          @cancel-upgrade="openCancelModal"
          :description="`Up to lv. ${building.level + 1}`" />
        <button
          class="px-4 py-2 w-full rounded-br-xl border-t border-primary/40 bg-primary/40 text-light font-semibold transition hover:bg-primary focus:outline-none disabled:hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed"
          @click="upgradeBuilding" :disabled="!canUpgrade || isCoreUpgradeBlocked" type="button">
          <span v-if="isCoreUpgradeBlocked">Upgrade Core</span>
          <span v-else>Upgrade to lv. {{ nextUpgradeLevel }}</span>
        </button>
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
    0 0 12px -2px hsl(var(--glow-color) / 0.15);
  border: 1px solid hsl(210deg 30% 25% / 0.5);
}

.image::after {
  content: '';
  position: absolute;
  inset: 0;
  box-shadow: inset 0px -20px 20px 4px #1E2D3B,
    inset 0px -30px 45px 0px #1E2D3B;
}
</style>

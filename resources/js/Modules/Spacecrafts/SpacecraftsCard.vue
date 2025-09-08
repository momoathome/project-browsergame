<script lang="ts" setup>
import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { timeFormat, numberFormat } from '@/Utils/format';
import Divider from '@/Components/Divider.vue';
import AppInput from '@/Modules/Shared/AppInput.vue';
import AppCardTimer from '@/Modules/Shared/AppCardTimer.vue';
import TertiaryButton from '@/Components/TertiaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { useQueueStore } from '@/Composables/useQueueStore';
import type { Spacecraft } from '@/types/types';

const { queueData, refreshQueue } = useQueueStore();

const props = defineProps<{
  spacecraft: Spacecraft
}>();

// --- State & Helpers ---
const isSubmitting = ref(false);
const form = useForm({ amount: 0 });
const showCancelModal = ref(false);

// --- Computed Properties ---
const userAttributes = computed(() => usePage().props.userAttributes);
const userResources = computed(() => usePage().props.userResources);
// Typisierung für bessere IDE-Unterstützung und Fehlervermeidung
const spacecrafts = computed<any[]>(() => usePage().props.spacecrafts as any[]);

const getAttribute = (name: string) => {
  const attr = userAttributes.value.find((a: any) => a.attribute_name === name);
  return attr ? Number(attr.attribute_value) : 0;
};

const isProducing = computed(() => props.spacecraft.is_producing || false);
const productionEndTime = computed(() => props.spacecraft.end_time || null);

const formattedCombat = computed(() => numberFormat(props.spacecraft.combat));
const formattedCargo = computed(() => numberFormat(props.spacecraft.cargo));
const formattedBuildTime = computed(() => {
  const speed = getAttribute('production_speed') || 1;
  return timeFormat(Math.floor(props.spacecraft.build_time / speed));
});

const actualBuildTime = computed(() => {
  const speed = getAttribute('production_speed') || 1;
  const trueBuildTime = Math.floor(props.spacecraft.build_time / speed);
  if (props.spacecraft.is_producing && props.spacecraft.currently_producing) {
    return trueBuildTime * props.spacecraft.currently_producing;
  }
  return trueBuildTime * form.amount || trueBuildTime;
});

const activeProduction = computed(() => {
  if (props.spacecraft.is_producing && props.spacecraft.currently_producing) {
    return props.spacecraft.currently_producing;
  }
  return form.amount;
});

const resourceStatus = computed(() => {
  return props.spacecraft.resources.map(resource => {
    const userResource = userResources.value.find((ur: any) => ur.resource_id === resource.id);
    const userAmount = userResource ? userResource.amount : 0;
    return {
      id: resource.id,
      sufficient: userAmount >= resource.amount,
      userAmount,
      required: resource.amount,
      maxCount: Math.floor(userAmount / resource.amount)
    };
  });
});

function isResourceSufficient(resourceId: number): boolean {
  const status = resourceStatus.value.find(res => res.id === resourceId);
  return status ? status.sufficient : false;
}

const crewStatus = computed(() => {
  const crewLimit = getAttribute('crew_limit');
  const totalUnits = getAttribute('total_units');
  const queuedCrew = queueData.value.reduce((acc: number, item: any) => {
    if (item.actionType === 'produce' && item.details?.quantity && item.status === 'in_progress') {
      const queuedSpacecraft = spacecrafts.value.find((s: any) => s.id === item.targetId);
      if (queuedSpacecraft) {
        return acc + (queuedSpacecraft.crew_limit * item.details.quantity);
      }
    }
    return acc;
  }, 0);
  const availableUnitSlots = crewLimit - totalUnits - queuedCrew;
  const maxCrewCount = Math.floor(availableUnitSlots / props.spacecraft.crew_limit);
  return {
    available: availableUnitSlots,
    required: props.spacecraft.crew_limit,
    maxCount: Math.max(0, maxCrewCount),
    sufficient: availableUnitSlots >= props.spacecraft.crew_limit
  };
});

const maxSpacecraftCount = computed(() => {
  const resourceLimits = resourceStatus.value.map(res => res.maxCount || 0);
  const crewLimit = crewStatus.value.maxCount || 0;
  return Math.min(...resourceLimits, crewLimit);
});

const canProduce = computed(() => {
  return resourceStatus.value.every(resource => resource.sufficient) && crewStatus.value.sufficient;
});

const canUnlockSpacecraft = computed(() => {
  const researchPoints = getAttribute('research_points');
  return researchPoints >= props.spacecraft.research_cost;
});

function isResourceSufficientForNext(resourceId: number): boolean {
  const status = resourceStatus.value.find(res => res.id === resourceId);
  if (!status) return false;
  return status.userAmount >= status.required * (form.amount + 1);
}

const crewLimitReachedNext = computed(() => {
  return crewStatus.value.available < crewStatus.value.required * (form.amount + 1);
});

// --- Actions ---
function produceSpacecraft() {
  if (form.amount <= 0 || isProducing.value || isSubmitting.value) return;
  isSubmitting.value = true;
  form.post(
    route('shipyard.update', props.spacecraft.id),
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => form.reset(),
      onFinish: () => { isSubmitting.value = false, refreshQueue(); },
      onError: () => { isSubmitting.value = false; }
    }
  );
}

function handleProduceComplete() {
  setTimeout(() => {
    router.reload({ only: ['spacecrafts', 'userAttributes'] });
    refreshQueue();
  }, 500);
}

function goToMarketWithMissingResources() {
  const missing = props.spacecraft.resources
    .map(resource => {
      const userResource = userResources.value.find((ur: any) => ur.resource_id === resource.id);
      const missingAmount = resource.amount - (userResource?.amount || 0);
      return missingAmount > 0 ? { id: resource.id, amount: missingAmount } : null;
    })
    .filter((item): item is { id: number; amount: number } => item !== null);
  if (missing.length === 0) return;
  router.get(route('market', {
    resource_ids: missing.map(r => r.id).join(','),
    amounts: missing.map(r => r.amount).join(',')
  }));
}

function increment() {
  if (form.amount < maxSpacecraftCount.value) form.amount++;
}
function incrementBy10() {
  if (form.amount < maxSpacecraftCount.value - 9) form.amount += 10;
  else form.amount = maxSpacecraftCount.value;
}
function decrement() {
  if (form.amount > 0) form.amount--;
}
function decrementBy10() {
  if (form.amount > 10) form.amount -= 10;
  else form.amount = 0;
}

function unlockSpacecraft() {
  if (!canUnlockSpacecraft.value) return;
  router.post(route('shipyard.unlock', props.spacecraft.id), {
    preserveState: true,
    preserveScroll: true
  });
}

function handleCancelProduction() {
  if (!isProducing.value) return;

  router.delete(route('shipyard.cancel', props.spacecraft.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => refreshQueue()
  });
  form.reset();
  showCancelModal.value = false;
}
</script>

<template>
  <div class="flex relative">
    <div class="flex flex-col w-full rounded-xl bg-base content_card text-light" :class="{ 'locked': !spacecraft.unlocked }">
      <div class="flex justify-between items-center border-b-primary border-b-2">
        <div class="flex items-center gap-2 px-3 py-2">
          <div class="relative group">
            <img :src="`/images/spacecraftTypes/${spacecraft.type}.png`" class="h-5" alt="combat" />
            <AppTooltip :label="spacecraft.type" position="bottom" class="!mt-1" />
          </div>
          <p class="font-semibold text-lg">{{ spacecraft.name }}</p>

          <!-- <p class="text-[12px] text-gray">{{ spacecraft.type }}</p> -->
          <!-- <p class="text-gray text-sm mt-1">{{ spacecraft.description }}</p> -->
        </div>
        <div class="flex items-center h-full px-4 rounded-tr-xl bg-primary-dark">
          <p class="text-lg">{{ spacecraft.count }}</p>
        </div>
      </div>

      <div class="image relative">
        <img :src="spacecraft.image" class="h-32 w-full" alt="spacecraft" />
      </div>

      <div class="flex flex-col h-full">
        <div class="flex flex-col gap-4 h-full mb-4">

          <div class="flex justify-between gap-4 px-4 pt-4">
            <div class="flex relative group items-center gap-1">
              <img src="/images/combat.png" class="h-5" alt="combat" />
              <p class="font-medium text-sm">{{ formattedCombat }}</p>
              <AppTooltip :label="'combat'" position="bottom" class="!mt-1" />
            </div>
            <div class="flex relative group items-center gap-1">
              <img src="/images/cargo.png" class="h-5" alt="cargo" />
              <p class="font-medium text-sm">{{ formattedCargo }}</p>
              <AppTooltip :label="'cargo capacity'" position="bottom" class="!mt-1" />
            </div>
            <div class="flex relative group items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg"
                :class="{
                  'text-red-600': form.amount === 0 && !canProduce,
                  'text-orange-500': form.amount > 0 && crewLimitReachedNext
                }"
               alt="unit limit"
               width="20" height="20" viewBox="0 0 24 24">
                <g fill="none">
                  <path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/>
                  <path fill="currentColor" d="M12 13c2.396 0 4.575.694 6.178 1.672c.8.488 1.484 1.064 1.978 1.69c.486.615.844 1.351.844 2.138c0 .845-.411 1.511-1.003 1.986c-.56.45-1.299.748-2.084.956c-1.578.417-3.684.558-5.913.558s-4.335-.14-5.913-.558c-.785-.208-1.524-.506-2.084-.956C3.41 20.01 3 19.345 3 18.5c0-.787.358-1.523.844-2.139c.494-.625 1.177-1.2 1.978-1.69C7.425 13.695 9.605 13 12 13m0-11a5 5 0 1 1 0 10a5 5 0 0 1 0-10"/>
                </g>
              </svg>
              <p
                class="font-medium text-sm"
                :class="{
                  'text-red-600': form.amount === 0 && !canProduce,
                  'text-orange-500': form.amount > 0 && crewLimitReachedNext
                }"
              >
                {{ spacecraft.crew_limit }}
              </p>
              <AppTooltip :label="'crew limit'" position="bottom" class="!mt-1" />
            </div>
            <div class="flex relative group items-center gap-1">
              <img src="/images/speed.png" class="h-5" alt="speed" />
              <p class="font-medium text-sm">{{ spacecraft.speed }}</p>
              <AppTooltip :label="'speed'" position="bottom" class="!mt-1" />
            </div>
          </div>

          <Divider />

          <div class="grid grid-cols-4 gap-4 items-center px-2">
            <div class="relative group flex flex-col gap-1 items-center"
                v-for="resource in spacecraft.resources"
                :key="resource.name"
                :class="{ 'cursor-pointer': !isResourceSufficient(resource.id) && spacecraft.unlocked }"
                @click="!isResourceSufficient(resource.id) && spacecraft.unlocked && goToMarketWithMissingResources()"
            >
              <img :src="resource.image" class="h-7" alt="resource" />
              <p
                class="font-medium text-sm"
                :class="{
                  'text-red-600': form.amount === 0 && !isResourceSufficient(resource.id),
                  'text-orange-500': form.amount > 0 && !isResourceSufficientForNext(resource.id) && spacecraft.unlocked
                }"
              >
                {{ form.amount ? numberFormat(resource.amount * form.amount) : numberFormat(resource.amount) }}
              </p>
              <AppTooltip :label="resource.name" position="bottom" class="!mt-1" />
            </div>
          </div>
        </div>

        <div v-if="spacecraft.unlocked" class="flex flex-col border-t border-primary">
          <form @submit.prevent="produceSpacecraft" @keypress.enter="produceSpacecraft" >
            <div class="flex items-center justify-between">
              <button
                class="px-2 py-2 bg-primary-dark text-light hover:bg-primary transition font-semibold border-r border-primary focus:outline-none disabled:hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed"
                @click="form.amount = 0"
                :disabled="maxSpacecraftCount == 0 || isProducing || form.amount <= 0"
                 aria-label="Minimum"
                type="button"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 21 21">
                  <g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" transform="translate(2 2)">
                    <circle cx="8.5" cy="8.5" r="8"/>
                    <path d="m5.5 5.5l6 6m0-6l-6 6"/>
                  </g>
                </svg>
              </button>
              <button
                class="px-2 py-2 bg-primary-dark text-light hover:bg-primary transition font-semibold border-r border-primary focus:outline-none disabled:hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed"
                @click="decrement"
                @click.shift="decrementBy10"
                :disabled="maxSpacecraftCount == 0 || isProducing || form.amount <= 0"
                type="button"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M18 12.998H6a1 1 0 0 1 0-2h12a1 1 0 0 1 0 2"/>
                </svg>
            </button>
              <AppInput
                :id="spacecraft.name"
                :maxlength="4"
                :maxInputValue="maxSpacecraftCount"
                v-model="form.amount"
                :disabled="isProducing || !canProduce"
                class="!py-2 !px-0 !w-full !rounded-none !border-0 !bg-primary-dark text-center focus:!ring-0 focus:!border-x-2 transition-colors"
              />
              <button
                class="px-2 py-2 bg-primary-dark text-light hover:bg-primary transition font-semibold border-l border-primary focus:outline-none disabled:hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed"
                @click="increment"
                @click.shift="incrementBy10"
                :disabled="maxSpacecraftCount == 0 || isProducing || form.amount >= maxSpacecraftCount"
                type="button"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M18 12.998h-5v5a1 1 0 0 1-2 0v-5H6a1 1 0 0 1 0-2h5v-5a1 1 0 0 1 2 0v5h5a1 1 0 0 1 0 2"/>
                </svg>
            </button>
              <button
                class="px-2 py-2 bg-primary-dark text-light hover:bg-primary transition font-semibold border-l border-primary focus:outline-none disabled:hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed"
                :disabled="maxSpacecraftCount == 0 || isProducing"
                @click="form.amount = maxSpacecraftCount"
                type="button"
                aria-label="Maximum"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"> 
                  <path fill="currentColor" d="M9.575 12L5.7 8.1q-.275-.275-.288-.687T5.7 6.7q.275-.275.7-.275t.7.275l4.6 4.6q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.6 4.6q-.275.275-.687.288T5.7 17.3q-.275-.275-.275-.7t.275-.7zm6.6 0L12.3 8.1q-.275-.275-.288-.687T12.3 6.7q.275-.275.7-.275t.7.275l4.6 4.6q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.6 4.6q-.275.275-.687.288T12.3 17.3q-.275-.275-.275-.7t.275-.7z"/>
                </svg>
              </button>
            </div>

            <div class="flex">

            <AppCardTimer
              :buildTime="actualBuildTime"
              @upgrade-complete="handleProduceComplete"
              :isInProgress="isProducing"
              :endTime="productionEndTime"
              :description="`${activeProduction} Units`"
              @cancel-upgrade="showCancelModal = true"
              :disabled="!canProduce && !isProducing"
            />

            <button
              class="px-4 py-2 w-full bg-primary-dark text-light font-semibold rounded-br-xl transition border-t border-primary hover:bg-primary focus:outline-none disabled:hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="isProducing || form.amount == 0 || !canProduce"
              @click="produceSpacecraft"
              type="button"
            >
              <span v-if="isProducing">Producing</span>
              <span v-else>Produce</span>
            </button>
            </div>

          </form>

        </div>

      </div>
    </div>

    <TertiaryButton v-if="!spacecraft.unlocked" @click="unlockSpacecraft"
      class="mt-4 gap-4 w-36 absolute left-1/2 -translate-x-1/2 bottom-12"
      :disabled="!canUnlockSpacecraft">
      Unlock
      <div class="flex gap-1">
        <img src="/images/attributes/research_points.png" class="h-5" alt="research icon">
        <span>{{ spacecraft.research_cost }}</span>
      </div>
    </TertiaryButton>
    
  </div>

    <DialogModal :show="showCancelModal" @close="showCancelModal = false" class="bg-slate-950/70 backdrop-blur-sm">
      <template #title>Cancel Production</template>
      <template #content>
        <p>Are you sure you want to cancel the production of
          <span class="font-semibold">
            {{ activeProduction }} {{ spacecraft.name }}
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
.content_card {
  --shadow-color: 210deg 30% 15%;
  --glow-color: 210deg 70% 50%;

  box-shadow: 1px 1px 1.6px hsl(var(--shadow-color) / 0.3),
    3.5px 3.5px 5.6px -0.8px hsl(var(--shadow-color) / 0.3),
    8.8px 8.8px 14px -1.7px hsl(var(--shadow-color) / 0.35),
    0 0 12px -2px hsl(var(--glow-color) / 0.15);
  border: 1px solid hsl(210deg 30% 25% / 0.5);
}

.custom-border {
  border: 1px solid hsl(210deg 30% 25% / 0.5);
}

.image::after {
  content: '';
  position: absolute;
  inset: 0;
  box-shadow: inset 0px -20px 20px 4px #1E2D3B,
    inset 0px -30px 45px 0px #1E2D3B;
}

.locked {
  filter: brightness(0.7) grayscale(1);
  pointer-events: none;
}

</style>

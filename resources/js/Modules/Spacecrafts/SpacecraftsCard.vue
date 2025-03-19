<script lang="ts" setup>
import { computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { timeFormat, numberFormat } from '@/Utils/format';
import Divider from '@/Components/Divider.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AppInput from '@/Components/AppInput.vue';
import type { FormattedSpacecraft } from '@/types/types';
import AppCardTimer from '@/Components/AppCardTimer.vue';
import TertiaryButton from '@/Components/TertiaryButton.vue';

const props = defineProps<{
  spacecraft: FormattedSpacecraft
}>();

const isProducing = computed(() => props.spacecraft.is_producing || false);
const productionEndTime = computed(() => props.spacecraft.end_time || null);

const formattedCombat = computed(() => numberFormat(props.spacecraft.combat));
const formattedCargo = computed(() => numberFormat(props.spacecraft.cargo));
const formattedBuildTime = computed(() => timeFormat(props.spacecraft.build_time));

const form = useForm({
  amount: 0
});

function produceSpacecraft() {
  if (form.amount <= 0) {
    return
  }

  form.post(route('shipyard.update', props.spacecraft.id), {
    preserveState: true,
    preserveScroll: true,

    onSuccess: () => {
      form.reset();
    },
    onError: () => {
      //
    },
  });
}

function handleProduceComplete() {
  setTimeout(() => {
    router.reload({ only: ['spacecrafts', 'queue', 'userAttributes'] });
  }, 500);
}

const actualBuildTime = computed(() => {
  if (props.spacecraft.is_producing && props.spacecraft.currently_producing) {
    return props.spacecraft.build_time * props.spacecraft.currently_producing;
  }
  return props.spacecraft.build_time * form.amount;
});

const activeProduction = computed(() => {
  if (props.spacecraft.is_producing && props.spacecraft.currently_producing) {
    return props.spacecraft.currently_producing;
  }
  return form.amount;
});

const resourceStatus = computed(() => {
  const userResources = usePage().props.userResources;
  const spacecraftResources = props.spacecraft.resources;
  
  return spacecraftResources.map(resource => {
    const userResource = userResources.find(ur => ur.resource_id === resource.id);
    if (!userResource) return { id: resource.id, sufficient: false };
    
    // Prüfe ob genug Ressourcen für EIN Raumschiff vorhanden sind
    return { 
      id: resource.id, 
      sufficient: userResource.amount >= resource.amount,
      userAmount: userResource.amount,
      required: resource.amount,
      maxCount: Math.floor(userResource.amount / resource.amount)
    };
  });
});

function isResourceSufficient(resourceId: number): boolean {
  const status = resourceStatus.value.find(res => res.id === resourceId);
  return status ? status.sufficient : false;
}

const crewStatus = computed(() => {
  const userAttributes = usePage().props.userAttributes;
  const userCrewLimit = userAttributes.find(attr => attr.attribute_name === 'crew_limit')?.attribute_value || 0;
  const userTotalUnits = userAttributes.find(attr => attr.attribute_name === 'total_units')?.attribute_value || 0;
  const availableUnitSlots = userCrewLimit - userTotalUnits;
  
  const hasEnoughCrewSlots = availableUnitSlots >= props.spacecraft.crew_limit;
  const maxCrewCount = Math.floor(availableUnitSlots / props.spacecraft.crew_limit);
  
  return {
    sufficient: hasEnoughCrewSlots,
    available: availableUnitSlots,
    required: props.spacecraft.crew_limit,
    maxCount: maxCrewCount
  };
});

const maxSpacecraftCount = computed(() => {
  const resourceLimits = resourceStatus.value.map(res => res.maxCount || 0);
  const crewLimit = crewStatus.value.maxCount || 0;
  
  return Math.min(...resourceLimits, crewLimit);
});

const canProduce = computed(() => {
  const hasEnoughResources = resourceStatus.value.every(resource => resource.sufficient);
  return hasEnoughResources && crewStatus.value.sufficient;
});

const increment = () => {
  if (form.amount < maxSpacecraftCount.value) {
    form.amount++
  }
}
const incrementBy10 = () => {
  if (form.amount < maxSpacecraftCount.value - 10) {
    form.amount += 10
  }
}
const decrement = () => {
  if (form.amount > 0) {
    form.amount--
  }
}
const decrementBy10 = () => {
  if (form.amount > 10) {
    form.amount -= 10
  }
}

function unlockSpacecraft() {
  router.post(route('shipyard.unlock', props.spacecraft.id), {
    preserveState: true,
    preserveScroll: true,
  });
}
</script>

<template>
  <div class="flex relative min-h-[517px]">
    <div class="flex flex-col rounded-3xl bg-base content_card text-light" :class="{ 'locked': !spacecraft.unlocked }">
      <div class="image relative">
        <img :src="spacecraft.image" class="rounded-t-3xl h-[144px]" alt="spacecraft" />
      </div>
      <div class="px-6 pt-0 pb-6 flex flex-col gap-4 h-full">
        <div class="flex flex-col gap-4">
          <div class="flex justify-between">
            <div class="flex flex-col">
              <p class="font-semibold text-2xl -mb-1">{{ spacecraft.name }}</p>
              <p class="text-[12px] font-medium text-gray">{{ spacecraft.type }}</p>
            </div>
            <div class="flex">
              <span class="text-sm font-medium mt-2 me-1 text-secondary">count</span>
              <p class="text-xl">{{ spacecraft.count }}</p>
            </div>
          </div>
          <p class="text-gray text-sm">{{ spacecraft.description }}</p>
        </div>

        <div class="flex w-full justify-between">
          <div class="flex flex-col items-center">
            <span class="text-sm text-secondary">Combat</span>
            <p class="font-medium text-sm">{{ formattedCombat }}</p>
          </div>
          <div class="flex flex-col items-center">
            <span class="text-sm text-secondary">Cargo</span>
            <p class="font-medium text-sm">{{ formattedCargo }}</p>
          </div>
          <div class="flex flex-col items-center">
            <span class="text-sm text-secondary">Crew</span>
            <p class="font-medium text-sm" :class="{'text-red-600': !crewStatus.sufficient}">{{ spacecraft.crew_limit }}</p>
          </div>
          <div class="flex flex-col items-center">
            <span class="text-sm text-secondary">Build Time</span>
            <p class="font-medium text-sm">{{ formattedBuildTime }}</p>
          </div>
        </div>

        <Divider />

        <div class="grid grid-cols-4 gap-4 items-center">
          <div class="flex flex-col gap-1 items-center" v-for="resource in spacecraft.resources" :key="resource.name">
            <img :src="resource.image" class="h-7" alt="resource" />
            <!-- <span class="text-sm font-medium text-secondary">{{ resource.name }}</span> -->
            <p class="font-medium text-sm" :class="{'text-red-600': !isResourceSufficient(resource.id) && spacecraft.unlocked}">{{ resource.amount }}</p>
            <span v-show="form.amount > 0" class="text-xs -mt-2">({{ resource.amount * form.amount }})</span>
          </div>
        </div>

        <div class="flex flex-col gap-4 mt-auto">
          <form v-if="spacecraft.unlocked" @submit.prevent="produceSpacecraft" @keypress.enter="produceSpacecraft">
            <div class="flex justify-between gap-4">
              <div class="flex items-center">
                <button @click="decrement" @click.shift="decrementBy10" type="button"
                  :disabled="maxSpacecraftCount == 0 || isProducing || form.amount <= 0" class="border-none p-0 disabled:opacity-50 disabled:pointer-events-none">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="25" viewBox="0 0 320 512">
                    <path fill="currentColor"
                      d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256l137.3-137.4c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z" />
                  </svg>
                </button>

                <AppInput :id="spacecraft.name" :maxlength="4" :maxInputValue="maxSpacecraftCount" v-model="form.amount" :disabled="isProducing || !canProduce" class="h-10" />

                <button @click="increment" @click.shift="incrementBy10" type="button"
                  :disabled="maxSpacecraftCount == 0 || isProducing || form.amount >= maxSpacecraftCount" class="border-none p-0 disabled:opacity-50 disabled:pointer-events-none">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="25" viewBox="0 0 320 512">
                    <path fill="currentColor"
                      d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256L73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z" />
                  </svg>
                </button>
              </div>

              <PrimaryButton :disabled="isProducing || form.amount == 0 || !canProduce">
                <span v-if="isProducing">Producing...</span>
                <span v-else>Produce</span>
              </PrimaryButton>
            </div>
          </form>

          <AppCardTimer v-if="spacecraft.unlocked" :buildTime="actualBuildTime"
            @upgrade-complete="handleProduceComplete" :isInProgress="isProducing" :endTime="productionEndTime"
            :description="`produce ${activeProduction}`" />
        </div>

      </div>
    </div>

    <TertiaryButton v-if="!spacecraft.unlocked" @click="unlockSpacecraft"
      class="mt-4 gap-4 w-36 absolute left-1/2 -translate-x-1/2 bottom-12">
      Unlock
      <div class="flex gap-1">
        <img src="/storage/attributes/research_points.png" class="h-5" alt="research icon">
        <span>{{ spacecraft.research_cost }}</span>
      </div>
    </TertiaryButton>
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

.locked {
  filter: brightness(0.7) grayscale(1);
  pointer-events: none;
}

:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}
</style>

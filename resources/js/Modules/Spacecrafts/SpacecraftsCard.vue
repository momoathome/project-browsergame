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

  form.post(`/shipyard/${props.spacecraft.id}/update`, {
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

const maxSpacecraftCount = computed(() => {
  const userResources = usePage().props.userResources;
  const spacecraftResources = props.spacecraft.resources;
  const userAttributes = usePage().props.userAttributes;
  const userUnitLimit = userAttributes.find(attr => attr.attribute_name === 'unit_limit')?.attribute_value || 0;
  const userTotalUnits = userAttributes.find(attr => attr.attribute_name === 'total_units')?.attribute_value || 0;
  const availableUnitSlots = userUnitLimit - userTotalUnits;

  return Math.min(
    ...spacecraftResources.map(resource => {
      const userResource = userResources.find(ur => ur.resource_id === resource.id);
      if (!userResource) return 0;
      return Math.floor(userResource.amount / resource.amount);
    }),
    Math.floor(availableUnitSlots / props.spacecraft.unit_limit)
  );
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
  router.post(`/shipyard/${props.spacecraft.id}/unlock`, {
    preserveState: true,
    preserveSCroll: true,
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
            <p class="font-medium text-sm">{{ spacecraft.unit_limit }}</p>
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
            <p class="font-medium text-sm">{{ resource.amount }}</p>
            <span v-show="form.amount > 0" class="text-xs -mt-2">({{ resource.amount * form.amount}})</span>
          </div>
        </div>

        <div class="flex flex-col gap-4 mt-auto">
          <form v-if="spacecraft.unlocked" @submit.prevent="produceSpacecraft" @keypress.enter="produceSpacecraft">
            <div class="flex justify-between gap-4">
              <div class="flex items-center">
                <button @click="decrement" @click.shift="decrementBy10" type="button" :disabled="maxSpacecraftCount == 0" class="border-none p-0">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="25" viewBox="0 0 320 512">
                    <path fill="currentColor"
                      d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256l137.3-137.4c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z" />
                  </svg>
                </button>

                <AppInput :maxlength="4" :maxInputValue="maxSpacecraftCount" v-model="form.amount" class="h-10" />

                <button @click="increment" @click.shift="incrementBy10" type="button" :disabled="maxSpacecraftCount == 0" class="border-none p-0">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="25" viewBox="0 0 320 512">
                    <path fill="currentColor"
                      d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256L73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z" />
                  </svg>
                </button>
              </div>

              <PrimaryButton>
                Produce
              </PrimaryButton>
            </div>
          </form>

          <AppCardTimer v-if="spacecraft.unlocked" :time="spacecraft.build_time * form.amount"
            :description="`produce ${form.amount} Spacecrafts`" />
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
  --shadow-color: 0deg 0% 57%;

  box-shadow: 1px 1px 1.6px hsl(var(--shadow-color) / 0.42),
    3.5px 3.5px 5.6px -0.8px hsl(var(--shadow-color) / 0.42),
    8.8px 8.8px 14px -1.7px hsl(var(--shadow-color) / 0.42),
    12.5px 15.5px 25.2px -2.5px hsl(var(--shadow-color) / 0.42);
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

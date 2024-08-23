<script lang="ts" setup>
import { type PropType, computed } from 'vue';
import { numberFormat, timeFormat } from '@/Utils/format';
import Divider from '@/Components/Divider.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AppInput from '@/Components/AppInput.vue';

interface Resource {
  name: string;
  image: string;
  amount: number;
}

interface SpacecraftCardProps {
  id: number;
  image: string
  name: string
  description: string
  type: string
  combat: number
  count: number
  cargo: number
  unitLimit: number
  buildTime: number
  resources: Resource[];

}

const props = defineProps({
  spacecraftData: {
    type: Object as PropType<SpacecraftCardProps>,
    required: true
  }
});

const formattedBuildTime = computed(() => timeFormat(props.spacecraftData.buildTime));

const emit = defineEmits(['produce']);

function produceSpacecraft() {
  emit('produce');
}
</script>

<template>
  <div class="flex flex-col rounded-3xl bg-base content_card text-[#DADCE5]">
    <div class="image relative">
      <!-- <img :src="spacecraftData.image" class="object-cover rounded-t-3xl" /> -->
      <img src="https://via.placeholder.com/320x180" class="object-cover rounded-t-3xl w-full h-full" />
    </div>
    <div class="px-6 pt-0 pb-6 flex flex-col gap-4">
      <div class="flex flex-col gap-4">
        <div class="flex justify-between">
          <div class="flex flex-col">
            <p class="font-semibold text-2xl -mb-1">{{ spacecraftData.name }}</p>
            <p class="text-[12px] font-medium text-gray">{{ spacecraftData.type }}</p>
          </div>
          <div class="flex">
            <span class="text-sm font-medium mt-2 text-secondary">stk.</span>
            <p class="text-xl line-height-loose">{{ spacecraftData.count }}</p>
          </div>
        </div>
        <p class="text-gray text-sm">{{ spacecraftData.description }}</p>
      </div>

      <div class="flex w-full justify-between">
        <div class="flex flex-col items-center">
          <span class="text-sm text-secondary">Combat</span>
          <p class="font-medium text-sm">{{ spacecraftData.combat }}</p>
        </div>
        <div class="flex flex-col items-center">
          <span class="text-sm text-secondary">Cargo</span>
          <p class="font-medium text-sm">{{ spacecraftData.cargo }}</p>
        </div>
        <div class="flex flex-col items-center">
          <span class="text-sm text-secondary">Unit Limit</span>
          <p class="font-medium text-sm">{{ spacecraftData.unitLimit }}</p>
        </div>
        <div class="flex flex-col items-center">
          <span class="text-sm text-secondary">Build Time</span>
          <p class="font-medium text-sm">{{ formattedBuildTime }}</p>
        </div>
      </div>

      <Divider />

      <div class="grid grid-cols-4 gap-4 items-center">
        <div class="flex flex-col items-center" v-for="resource in spacecraftData.resources" :key="resource.name">
          <img :src="resource.image" class="h-8 w-8" />
          <!-- <span class="text-sm font-medium text-secondary">{{ resource.name }}</span> -->
          <p class="font-medium text-sm">{{ resource.amount }}</p>
        </div>
      </div>

      <form @submit.prevent="produceSpacecraft" @keypress.enter="produceSpacecraft">
        <div class="flex justify-between gap-4">
          <div class="flex items-center">
            <button @click="decrement" type="button" class="border-none p-0">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="25" viewBox="0 0 320 512">
                <path fill="currentColor"
                  d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256l137.3-137.4c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z" />
              </svg>
            </button>

            <AppInput :maxlength="4" />

            <button @click="increment" type="button" class="border-none p-0">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="25" viewBox="0 0 320 512">
                <path fill="currentColor"
                  d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256L73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z" />
              </svg>
            </button>
          </div>
          <PrimaryButton @click="produceSpacecraft">
            Produce
          </PrimaryButton>
        </div>
      </form>

    </div>
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
</style>

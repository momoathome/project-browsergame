<script lang="ts" setup>
import { computed, ref } from 'vue';
import { numberFormat } from '@/Utils/format';
import Divider from '@/Components/Divider.vue';
import AppInput from '@/Components/AppInput.vue';
import type { Spacecraft } from '@/types/types';

const props = defineProps<{
  spacecraft: Spacecraft;
}>();

const formattedCombat = computed(() => numberFormat(props.spacecraft.combat));
const formattedCargo = computed(() => numberFormat(props.spacecraft.cargo));

const count = defineModel({default: 0});

const increment = () => {
  if (count.value >= props.spacecraft.count) {
    return
  }
  count.value++
}
const incrementBy10 = () => {
  if (count.value >= props.spacecraft.count - 10) {
    count.value += 10
  }
}
const decrement = () => {
  if (count.value > 0) {
    count.value--
  }
}
const decrementBy10 = () => {
  if (count.value > 10) {
    count.value -= 10
  }
}

</script>

<template>
  <div class="flex flex-col rounded-3xl bg-base content_card text-[#DADCE5]">
    <div class="image relative">
      <img :src="spacecraft.details.image" class="rounded-t-3xl h-[65px]" alt="" />
    </div>
    <div class="px-4 pt-0 pb-4 flex flex-col gap-2">
      <div class="flex flex-col gap-2">
        <div class="flex justify-between">
          <div class="flex flex-col">
            <p class="font-semibold text-lg -mb-2">{{ spacecraft.details.name }}</p>
            <p class="text-[10px] font-medium text-gray">{{ spacecraft.details.type }}</p>
          </div>
          <div class="flex">
            <span class="text-sm font-medium mt-2 me-1 text-secondary"></span>
            <p class="text-lg">{{ spacecraft.count }}</p>
          </div>
        </div>
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
      </div>

      <Divider />

      <div class="flex justify-center gap-2">
        <div class="flex items-center">
          <button @click="decrement" @click.shift="decrementBy10" type="button" class="border-none p-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="20" viewBox="0 0 320 512">
              <path fill="currentColor"
                d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256l137.3-137.4c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z" />
            </svg>
          </button>

          <AppInput class="!py-1 !px-0 !w-14" :maxlength="4" v-model="count" :maxInputValue="spacecraft.count" />

          <button @click="increment" @click.shift="incrementBy10" type="button" class="border-none p-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="20" viewBox="0 0 320 512">
              <path fill="currentColor"
                d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256L73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z" />
            </svg>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.content_card {
  --shadow-color: 209deg 33% 10%;

  box-shadow: 1px 1px 1.6px hsl(var(--shadow-color) / 0.42),
    3.5px 3.5px 5.6px -0.8px hsl(var(--shadow-color) / 0.42),
    8.8px 8.8px 14px -1.7px hsl(var(--shadow-color) / 0.42),
    12.5px 15.5px 25.2px -2.5px hsl(var(--shadow-color) / 0.42);

}

.image::after {
  content: '';
  position: absolute;
  inset: 0;
  box-shadow: inset 0px -20px 20px 4px #1E2D3B;
  border-radius: 24px 24px 0 0;
}
</style>

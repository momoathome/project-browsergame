<script lang="ts" setup>
import { computed } from 'vue';
import { numberFormat } from '@/Utils/format';
import type { formattedMarketResource } from '@/types/types';

const props = defineProps<{
  marketData: formattedMarketResource,
  selected?: boolean,
  showStock?: boolean,
  showAmount?: boolean,
  receive?: boolean
}>();

const formattedStock = computed(() => numberFormat(props.marketData.stock));
const formattedAmount = computed(() => numberFormat(props.marketData.amount ?? 0));
</script>

<template>
  <div
    class="aspect-square max-w-52 rounded-xl flex flex-col justify-between bg-base text-light cursor-pointer transition hover:bg-base/80 p-3"
    :class="{
      'disabled': (showAmount && marketData.amount === 0) || (showStock && marketData.stock === 0),
      '!border-secondary !ring-2 !ring-secondary': selected,
      'bg-secondary/10': receive,
    }"
    @click="$emit('select', marketData)"
  >
    <!-- Name oben -->
    <div class="relative flex items-center border-b-primary/40 border-b h-[15%]" :class="{'border-b-secondary/40': receive}">
      <p class="font-semibold text-sm sm:text-base !text-light truncate">{{ marketData.name }}</p>
    </div>

    <!-- Bild mittig, skaliert mit Container -->
    <div class="flex justify-center items-center flex-1">
      <img :src="marketData.image" class="max-h-[70%] max-w-[70%] object-contain" alt="resource" />
    </div>

    <!-- Stock/Amount unten als Badges -->
    <div class="flex justify-between gap-1 bg-primary/25 rounded-md px-3 py-2 mt-2" :class="{'bg-secondary/25': receive}">
      <span v-if="showStock" :class="{'!text-light': receive}" class="flex gap-1 items-center text-light text-xs sm:text-sm text-pretty">
        <img src="/images/navigation/market.png" height="22" width="22" alt="">
        {{ formattedStock }}</span>
      <span v-if="showAmount" class="flex gap-1 items-center text-light text-xs sm:text-sm text-pretty">
        <img src="images/navigation/profile.png" height="20" width="20" alt="">
        {{ formattedAmount }}</span>
    </div>
  </div>
</template>

<style scoped>
.disabled {
  pointer-events: none;
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
}
</style>

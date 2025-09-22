<script lang="ts" setup>
import { computed } from 'vue';
import { numberFormat } from '@/Utils/format';
import type { formattedMarketResource } from '@/types/types';

const props = defineProps<{
  marketData: formattedMarketResource,
  selected?: boolean,
  showStock?: boolean,
  showAmount?: boolean
}>();

const formattedStock = computed(() => numberFormat(props.marketData.stock));
const formattedAmount = computed(() => numberFormat(props.marketData.amount ?? 0));
</script>

<template>
  <div
    class="aspect-square max-w-52 rounded-xl flex flex-col justify-between bg-base text-light cursor-pointer transition hover:bg-base/80 p-3"
    :class="{
      'disabled': (showAmount && marketData.amount === 0) || (showStock && marketData.stock === 0),
      '!border-secondary !ring-2 !ring-secondary': selected
    }"
    @click="$emit('select', marketData)"
  >
    <!-- Name oben -->
    <div class="relative flex items-center border-b-primary/40 border-b h-[15%]">
      <p class="font-semibold text-sm sm:text-base !text-light truncate">{{ marketData.name }}</p>
    </div>

    <!-- Bild mittig, skaliert mit Container -->
    <div class="flex justify-center items-center flex-1">
      <img :src="marketData.image" class="max-h-[70%] max-w-[70%] object-contain" alt="resource" />
    </div>

    <!-- Stock/Amount unten als Badges -->
    <div class="flex justify-center gap-2">
      <span v-if="showStock" class="bg-primary/20 text-secondary text-xs sm:text-sm px-2 sm:px-3 py-0.5 rounded-full">Stock: {{ formattedStock }}</span>
      <span v-if="showAmount" class="bg-primary/20 text-secondary text-xs sm:text-sm px-2 sm:px-3 py-0.5 rounded-full">Amount: {{ formattedAmount }}</span>
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

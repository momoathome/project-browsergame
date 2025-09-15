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
    class="h-44 w-44 rounded-xl flex flex-col justify-between bg-base text-light content_card cursor-pointer transition hover:scale-[1.03] hover:shadow-lg hover:bg-base/80"
    :class="{ '!border-secondary !ring-2 !ring-secondary': selected }"
    @click="$emit('select', marketData)"
  >
    <!-- Name oben -->
    <div class="relative flex items-center border-b-primary/40 border-b-2 h-12">
      <p class="font-semibold px-3 py-2">{{ marketData.name }}</p>
    </div>
    <!-- Bild mittig -->
    <div class="flex justify-center items-center flex-1">
      <img :src="marketData.image" class="h-14 w-14 object-contain" alt="resource" />
    </div>
    <!-- Stock/Amount unten als Badges -->
    <div class="flex justify-center gap-2 pb-3">
      <span v-if="showStock" class="bg-primary/20 text-secondary text-sm px-3 py-1 rounded-full">Stock: {{ formattedStock }}</span>
      <span v-if="showAmount" class="bg-primary/20 text-secondary text-sm px-3 py-1 rounded-full">Amount: {{ formattedAmount }}</span>
    </div>
  </div>
</template>



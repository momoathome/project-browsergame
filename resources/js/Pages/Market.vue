<script lang="ts" setup>
import { computed, ref } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MarketCard from '@/Modules/Market/MarketCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AppInput from '@/Modules/Shared/AppInput.vue';
import MarketPlaceholder from '@/Modules/Market/MarketPlaceholder.vue';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import type { Market, formattedMarketResource } from '@/types/types';
import { numberFormat } from '@/Utils/format';

const props = defineProps<{
  market: Market[],
  categoryValues: Record<string, number>
}>();

const selectedGive = ref<formattedMarketResource | null>(null);
const selectedReceive = ref<formattedMarketResource | null>(null);
const tradeForm = useForm({
  give_resource_id: null as number | null,
  receive_resource_id: null as number | null,
  give_amount: 0
});

const formattedResources = computed(() => props.market.map(m => ({
  id: m.id,
  resource_id: m.resource_id,
  name: m.resource.name,
  description: m.resource.description ?? '',
  image: m.resource.image,
  stock: m.stock,
  cost: m.cost,
  category: m.category // Add category property
})));
const userFormattedResources = computed(() => {
  const userResources = usePage().props.userResources;
  return formattedResources.value.map(r => ({
    ...r,
    amount: userResources.find(u => u.resource_id === r.resource_id)?.amount ?? 0,
    description: r.description ?? '',
    category: r.category
  }));
});

const getCategory = (resource: formattedMarketResource) => {
  return resource.category;
};

const calculatedReceiveAmount = computed(() => {
  if (!selectedGive.value || !selectedReceive.value) return 0;
  const giveCategory = getCategory(selectedGive.value);
  const receiveCategory = getCategory(selectedReceive.value);

  if (!giveCategory || !receiveCategory) return 0;

  const giveValue = tradeForm.give_amount * props.categoryValues[giveCategory];
  const receiveQtyRaw = giveValue / props.categoryValues[receiveCategory];
  const fee = Math.floor(receiveQtyRaw * 0.05);
  return Math.max(Math.floor(receiveQtyRaw) - fee, 0);
});

const tradeRatio = computed(() => {
  if (!selectedGive.value || !selectedReceive.value) return null;
  const giveCategory = getCategory(selectedGive.value);
  const receiveCategory = getCategory(selectedReceive.value);

  if (!giveCategory || !receiveCategory) return null;

  const giveValue = props.categoryValues[giveCategory];
  const receiveValue = props.categoryValues[receiveCategory];

  return `${receiveValue} : ${giveValue}`;
});

function executeTrade() {
  // route = market.trade
  if (!selectedGive.value || !selectedReceive.value) return;
  tradeForm.give_resource_id = selectedGive.value.id;
  tradeForm.receive_resource_id = selectedReceive.value.id;

  tradeForm.post(route('market.trade'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      tradeForm.reset('give_resource_id', 'receive_resource_id', 'give_amount');
      selectedGive.value = null;
      selectedReceive.value = null;
    },
    onError: () => {
      // Fehlerbehandlung
    },
  });
}

const tradeTooltip = computed(() => {
  return 'You pay ' + tradeForm.give_amount + ' ' + (selectedGive.value?.name || '') + ' for ' + calculatedReceiveAmount.value + ' ' + (selectedReceive.value?.name || '') + '. A 5% fee is applied on each trade.';
});
</script>

<template>
  <AppLayout title="market">
    <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr_2fr] gap-16 text-light">

      <!-- Left: User Resources -->
      <div class="bg-base/20 pt-4 pb-6 px-8 rounded-lg">
        <h2 class="text-xl font-bold mb-4">Your Resources</h2>
        <div class="market-grid gap-4">
          <MarketCard v-for="userRes in userFormattedResources" :key="userRes.id" :marketData="userRes"
            :selected="selectedGive?.id === userRes.id" :showAmount="true" @select="selectedGive = userRes" />
        </div>
      </div>

      <!-- Middle: Trade Panel mit Platzhaltern -->
      <div class="flex flex-col justify-center items-center p-8">
        <div class="flex gap-8 items-center mb-8">
          <div class="flex flex-col items-center gap-2">
            <MarketCard v-if="selectedGive" :marketData="selectedGive" :showAmount="true"
              style="pointer-events: none;" />

            <MarketPlaceholder class="!border-primary/30" v-else>
              <span class="text-xs text-nowrap">Select Resource</span>
            </MarketPlaceholder>
          </div>

          <div class="relative group flex flex-col items-center gap-1 min-w-[70px]">
            <span v-if="tradeRatio" class="text-lg font-bold text-light text-nowrap bg-primary/10 px-3 py-2 rounded-md">{{ tradeRatio }}</span>
            <span class="text-3xl font-bold text-secondary">→</span>

            <AppTooltip 
              :label="tradeTooltip" 
              position="top">
            </AppTooltip>
          </div>

          <div class="flex flex-col items-center gap-2">
            <MarketCard v-if="selectedReceive" :marketData="selectedReceive" :showStock="true"
              style="pointer-events: none;" />

            <MarketPlaceholder v-else class="!border-secondary/20">
              <span class="text-xs text-nowrap">Select Resource</span>
            </MarketPlaceholder>
          </div>

        </div>

        <hr class="w-full border-t border-primary/30 mb-6" />

        <div v-if="selectedGive && selectedReceive" class="flex flex-col gap-6 items-center w-full">
          <div class="flex items-center">
            <button
              class="px-2 py-2 h-11 bg-primary/30 text-light hover:bg-primary/60 transition font-semibold border-r border-primary/60 rounded-l-md focus:outline-none disabled:hover:bg-primary/40 disabled:opacity-40 disabled:cursor-not-allowed"
              @click="tradeForm.give_amount = 0"
              :disabled="selectedGive.amount == 0 || tradeForm.give_amount <= 0"
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

            <AppInput v-model="tradeForm.give_amount" :maxlength="5" :maxInputValue="selectedGive.amount"
              class="!bg-primary/30 text-center py-2 !w-28 text-lg font-semibold" />
            
            <button
              class="px-2 py-2 h-11 bg-primary/30 text-light hover:bg-primary/60 transition font-semibold border-l border-primary/60 focus:outline-none disabled:hover:bg-primary/40 disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="selectedGive.amount == 0 || tradeForm.give_amount >= selectedGive.amount"
              @click="tradeForm.give_amount = selectedGive.amount"
              type="button"
              aria-label="Maximum"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"> 
                <path fill="currentColor" d="M9.575 12L5.7 8.1q-.275-.275-.288-.687T5.7 6.7q.275-.275.7-.275t.7.275l4.6 4.6q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.6 4.6q-.275.275-.687.288T5.7 17.3q-.275-.275-.275-.7t.275-.7zm6.6 0L12.3 8.1q-.275-.275-.288-.687T12.3 6.7q.275-.275.7-.275t.7.275l4.6 4.6q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.6 4.6q-.275.275-.687.288T12.3 17.3q-.275-.275-.275-.7t.275-.7z"/>
              </svg>
            </button>

            <button
              class="px-4 py-2 h-11 bg-primary/30 text-light font-semibold rounded-r-md transition border-l border-primary/60 hover:bg-primary/60 focus:outline-none disabled:hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="tradeForm.give_amount <= 0 || calculatedReceiveAmount <= 0 || tradeForm.give_amount > selectedGive.amount"
              @click="executeTrade"
              type="button"
            >
              <span>Exchange</span>
            </button>
          </div>


          <div class="flex flex-col items-center">
            <p class="text-sm">You receive</p>
            <p class="text-xl font-bold text-secondary">
              {{ numberFormat(calculatedReceiveAmount) }} {{ selectedReceive.name }}
            </p>
          </div>

        </div>
        <p v-else class="text-secondary mt-4">Select Ressources you want to exchange </p>
      </div>

      <!-- Right: Market Resources -->
      <div class="bg-base/20 pt-4 pb-6 px-8 rounded-lg">
        <h2 class="text-xl font-bold mb-4">Market Resources</h2>
        <div class="market-grid gap-4">
          <MarketCard v-for="marketRes in formattedResources" :key="marketRes.id" :marketData="marketRes"
            :selected="selectedReceive?.id === marketRes.id" :showStock="true" @select="selectedReceive = marketRes" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.market-grid {
  --grid-min-col-size: 176px;

  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}

@media (min-width: 2600px) {
  .market-grid {
    grid-template-columns: repeat(6, 1fr);
    max-width: 2600px;
    margin-left: auto;
    margin-right: auto;
  }
}
</style>

<script lang="ts" setup>
import { computed, ref, onMounted, watch } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import MarketCard from '@/Modules/Market/MarketCard.vue';
import AppInput from '@/Modules/Shared/AppInput.vue';
import MarketPlaceholder from '@/Modules/Market/MarketPlaceholder.vue';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import { useResourceStore } from '@/Composables/useResourceStore';
import type { Market, formattedMarketResource } from '@/types/types';
import { numberFormat } from '@/Utils/format';

const props = defineProps<{
  market: Market[],
  categoryValues: Record<string, number>,
  prefill_resource_ids?: string,
  prefill_amounts?: string,
}>();

const { userResources } = useResourceStore();

const selectedGive = ref<formattedMarketResource | null>(null);
const selectedReceive = ref<formattedMarketResource | null>(null);
const draggedResource = ref<formattedMarketResource | null>(null);
const prefillReceiveAmount = ref<number | null>(null);
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
  category: m.category
})));

const userFormattedResources = computed(() => {
  return formattedResources.value.map(r => ({
    ...r,
    amount: userResources.value.find(u => u.resource_id === r.resource_id)?.amount ?? 0,
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

  const giveValue = tradeForm.give_amount * (props.categoryValues[giveCategory] ?? 0);
  const receiveQtyRaw = giveValue / (props.categoryValues[receiveCategory] ?? 1);
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

  return {give: giveValue, receive: receiveValue}
});

const tradeTooltip = computed(() => {
  return 'You exchange ' + tradeForm.give_amount + ' ' + (selectedGive.value?.name || '') + ' for ' + calculatedReceiveAmount.value + ' ' + (selectedReceive.value?.name || '') + '. A 5% fee is applied on each trade.';
});

onMounted(() => {
  if (props.prefill_resource_ids && props.prefill_amounts) {
    const ids = props.prefill_resource_ids.split(',').map(Number);
    const amounts = props.prefill_amounts.split(',').map(Number);

    const receiveId = ids[0];
    const receiveAmount = amounts[0];

    const receiveResource = formattedResources.value.find(r => r.resource_id === receiveId);
    if (receiveResource) {
      selectedReceive.value = receiveResource;
      prefillReceiveAmount.value = receiveAmount;
    }
  }
});

// Watcher für automatische Anpassung
watch([selectedGive, selectedReceive], ([give, receive]) => {
  if (give && receive && prefillReceiveAmount.value) {
    const giveCatValue = props.categoryValues[give.category] ?? 1;
    const receiveCatValue = props.categoryValues[receive.category] ?? 1;
    // Formel: gewünschte Menge * Kategorie-Wert / eigener Kategorie-Wert / 0.95 (Fee)
    tradeForm.give_amount = Math.ceil((prefillReceiveAmount.value * receiveCatValue) / giveCatValue / 0.95);
  }
});

// Drag & Drop Functions
function startDrag(resource: formattedMarketResource) {
  draggedResource.value = resource;
}

function onDragOver(event: DragEvent) {
  event.preventDefault();
}

function onDropGive(event: DragEvent) {
  event.preventDefault();
  if (draggedResource.value && draggedResource.value.amount > 0) {
    selectedGive.value = draggedResource.value;
  }
  draggedResource.value = null;
}

function onDropReceive(event: DragEvent) {
  event.preventDefault();
  if (draggedResource.value && draggedResource.value.stock > 0) {
    selectedReceive.value = draggedResource.value;
  }
  draggedResource.value = null;
}

function clearGive() {
  selectedGive.value = null;
  if (tradeForm.give_amount > 0) {
    tradeForm.give_amount = 0;
  }
}

function clearReceive() {
  selectedReceive.value = null;
}

function executeTrade() {
  if (!selectedGive.value || !selectedReceive.value) return;
  tradeForm.give_resource_id = selectedGive.value.id;
  tradeForm.receive_resource_id = selectedReceive.value.id;

  tradeForm.post(route('market.trade'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      tradeForm.reset('give_resource_id', 'receive_resource_id', 'give_amount');
      selectedReceive.value = null;
    },
    onError: () => {
      // Fehlerbehandlung
    },
  });
}
</script>

<template>
  <div class="grid grid-cols-1 xl:grid-cols-[3fr_1fr] gap-8 text-light overflow-x-hidden">
    
    <!-- Resource Inventory -->
    <div class="bg-base/20 pt-4 pb-6 px-8 rounded-lg">
      <h2 class="text-xl font-bold mb-4">Resources</h2>
      <p class="text-sm text-secondary mb-4">Drag resources to the trading panel to start trading</p>
      
      <div class="market-grid gap-4">
        <div
          v-for="resource in userFormattedResources"
          :key="resource.id"
          :draggable="resource.amount > 0 || resource.stock > 0"
          @dragstart="startDrag(resource)"
          @dragend="draggedResource = null"
          class="cursor-grab active:cursor-grabbing"
          :class="{
            'opacity-50 cursor-not-allowed': resource.amount <= 0 && resource.stock <= 0
          }"
        >
          <MarketCard 
            :marketData="resource"
            :selected="selectedGive?.id === resource.id || selectedReceive?.id === resource.id"
            :showStock="true"
            :showAmount="true"
            class="transition-all duration-200"
            :class="{
              'ring-2 ring-primary shadow-lg shadow-primary/20': selectedGive?.id === resource.id,
              'ring-2 ring-secondary shadow-lg shadow-secondary/20': selectedReceive?.id === resource.id,
            }"
          />
        </div>
      </div>
    </div>

    <!-- Trade Panel -->
    <div class="bg-base/20 pt-4 pb-6 px-8 rounded-lg flex flex-col">
      <h2 class="text-xl font-bold mb-6">Trading Panel</h2>
      
      <!-- Give Card -->
      <div class="flex items-center justify-center relative bg-base/30 border border-primary/20 rounded-lg p-4">
        <button
          v-if="selectedGive"
          @click="clearGive"
          class="text-primary/60 hover:text-primary transition-colors absolute top-3 right-3"
          type="button"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
        
        <div
          class="border-2 border-dashed border-primary/30 w-48 h-48 flex items-center justify-center rounded-xl shadow-md transition p-1"
          :class="{
            'border-primary-lighter bg-primary/5': draggedResource && draggedResource.amount > 0
          }"
          @dragover="onDragOver"
          @drop="onDropGive"
        >
          <Transition name="drop-in" mode="out-in">
            <MarketCard
              v-if="selectedGive"
              :key="selectedGive.id"
              :marketData="selectedGive"
              :showAmount="true"
              class="w-full h-full pointer-events-none"
            />
            <div v-else class="text-center text-primary-light p-2">
              <div class="mb-2">
                <svg class="w-8 h-8 mx-auto opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
              </div>
              <p class="text-sm">Drag resource here to give</p>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Trade Arrow & Ratio -->
      <div class="flex items-center justify-center my-4">
        <div class="relative group flex items-center gap-2">
          <span v-if="tradeRatio" class="text-lg font-bold text-light text-nowrap bg-primary/10 px-3 py-1 rounded-md">{{ tradeRatio.receive }}</span>
          <span class="text-3xl font-bold text-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24"><
              <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2"><path d="M8 20V7m8-3v13"/><path stroke-linejoin="round" d="m4 16l4 4l4-4m8-8l-4-4l-4 4"/></g>
            </svg>
          </span>
          <span v-if="tradeRatio" class=" text-lg font-bold text-light text-nowrap bg-secondary/10 px-3 py-1 rounded-md">{{ tradeRatio.give }}</span>
          <AppTooltip :label="tradeTooltip" position="top"></AppTooltip>
        </div>
      </div>

      <!-- Receive Card -->
      <div class="flex items-center justify-center relative bg-secondary/10 border border-secondary/20 rounded-lg p-4">
        <button
          v-if="selectedReceive"
          @click="clearReceive"
          class="text-secondary/60 hover:text-secondary transition-colors absolute top-3 right-3"
          type="button"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
        
        <div
          class="border-2 border-dashed border-secondary/30 w-48 h-48 flex items-center justify-center rounded-xl shadow-md transition p-1"
          :class="{
            '!border-secondary bg-secondary/5': draggedResource && draggedResource.stock > 0
          }"
          @dragover="onDragOver"
          @drop="onDropReceive"
        >
          <Transition name="drop-in" mode="out-in">
            <MarketCard
              v-if="selectedReceive"
              :key="selectedReceive.id"
              :marketData="selectedReceive"
              :showStock="true"
              :receive="true"
              class="w-full h-full pointer-events-none"
            />
            <div v-else class="text-center text-secondary-light p-2">
              <div class="mb-2">
                <svg class="w-8 h-8 mx-auto opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                </svg>
              </div>
              <p class="text-sm">Drag resource here to receive</p>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Trade Controls -->
      <div v-if="selectedGive && selectedReceive" class="flex flex-col gap-6 items-center mt-8 ">
        <div class="flex items-center w-full justify-center">
          <button
            class="px-2 py-2 h-11 bg-primary/30 text-light hover:bg-primary/60 transition font-semibold border-r border-primary/60 rounded-l-md focus:outline-none disabled:hover:bg-primary/40 disabled:opacity-40 disabled:cursor-not-allowed"
            @click="tradeForm.give_amount = 0"
            :disabled="selectedGive.amount == 0 || tradeForm.give_amount <= 0"
            aria-label="Clear"
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
          <p class="text-sm text-secondary">You receive</p>
          <p class="text-xl font-bold text-secondary">
            {{ numberFormat(calculatedReceiveAmount) }} {{ selectedReceive.name }}
          </p>
        </div>
      </div>
      
      <div v-else class="text-center text-secondary mt-auto py-8">
        <p class="text-lg">Select resources to start trading</p>
        <p class="text-sm opacity-70 mt-2">Drag resources to the trading slots above</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.market-grid {
  --grid-min-col-size: 180px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}

.drop-in-enter-active,
.drop-in-leave-active {
  transition:
    opacity 0.15s cubic-bezier(.34,1.56,.64,1),
    transform 0.15s cubic-bezier(.34,1.56,.64,1);
}
.drop-in-enter-from,
.drop-in-leave-to {
  opacity: 0;
  transform: scale(1.10) translateY(-8px);
}
.drop-in-enter-to,
.drop-in-leave-from {
  opacity: 1;
  transform: scale(1) translateY(0);
}

/* Drag and Drop visual feedback */
.cursor-grab {
  cursor: grab;
}

.cursor-grabbing {
  cursor: grabbing;
}

[draggable="true"]:hover {
  transform: scale(1.02);
  transition: transform 0.2s ease;
}
</style>

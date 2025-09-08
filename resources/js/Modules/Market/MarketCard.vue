<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { numberFormat } from '@/Utils/format';
import Divider from '@/Components/Divider.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AppInput from '@/Modules/Shared/AppInput.vue';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import type { formattedMarketResource } from '@/types/types';

const props = defineProps<{
  marketData: formattedMarketResource
}>()

const formattedCost = computed(() => numberFormat(props.marketData.cost));
const formattedStock = computed(() => numberFormat(props.marketData.stock));
const formattedTotalCost = computed(() => numberFormat(props.marketData.cost * form.amount));

const userCredits = computed(() => {
  return Number(usePage().props.userAttributes.find((a) => a.attribute_name === 'credits')?.attribute_value || 0);
});
const userStorage = computed(() => {
  return Number(usePage().props.userAttributes.find((a) => a.attribute_name === 'storage')?.attribute_value || 0);
});
const userResourceAmount = computed(() => {
  return Number(usePage().props.userResources.find((r) => r.resource_id === props.marketData.resource_id)?.amount || 0);
});
const willExceedStorage = computed(() => {
  return userResourceAmount.value + Number(form.amount) > userStorage.value;
});
const notEnoughCredits = computed(() => {
  return Number(form.amount) * props.marketData.cost > userCredits.value;
});
const notEnoughToSell = computed(() => {
  return Number(form.amount) > userResourceAmount.value;
});
const isBuyDisabled = computed(() => {
  return willExceedStorage.value || notEnoughCredits.value || Number(form.amount) <= 0;
});
const isSellDisabled = computed(() => {
  return notEnoughToSell.value || Number(form.amount) <= 0;
});

const localPrefill = ref(props.marketData.prefill);

onMounted(() => {
  if (props.marketData.prefill) {
    form.amount = props.marketData.prefill;
  }
});

const form = useForm({
  resource_id: props.marketData.id,
  amount: 0
});

function buyResource() {
  if (form.amount <= 0) {
    return;
  }

  form.post(route('market.buy', props.marketData.id), {
    preserveState: true,

    onSuccess: () => {
      form.reset();
      localPrefill.value = undefined;
    },
  });
}

function sellResource() {
  if (form.amount <= 0) {
    return;
  }

  form.post(route('market.sell', props.marketData.id), {
    onSuccess: () => {
      form.reset();
    },
  });
}

function setUserResourcesInput() {
  const userResource = usePage().props.userResources.find((resource) => resource.resource_id === props.marketData.resource_id);

  if (userResource) {
    form.amount = userResource.amount;
  } else {
    form.amount = 0;
  }
}

function setMaxAmount() {
  const maxAmount = Math.min(Math.floor(userCredits.value / props.marketData.cost), Math.floor(userStorage.value - userResourceAmount.value));
  form.amount = maxAmount;
}
</script>

<template>
  <div class="rounded-xl flex flex-col bg-base text-light content_card"
    :class="{ '!border-secondary': !!localPrefill }">
    <div class="gap-4 flex flex-col">
      <div class="flex justify-between items-center border-b-primary/40 border-b-2">
        <div class="flex flex-col justify-center px-3 py-2">
          <!-- <span class="text-xs text-gray">ressource</span> -->
          <p class="font-semibold text-lg">{{ marketData.name }}</p>
        </div>
        <div class="flex flex-col justify-center h-full px-3 rounded-tr-xl bg-primary/25">
          <div class="flex gap-1">
            <img src="/images/attributes/credits.png" class="h-5" alt="credits" />
            <p class="font-medium">{{ formattedCost }}</p>
          </div>
        </div>
      </div>

      <div class="flex justify-center items-center py-6">
        <img :src="marketData.image" class="h-16" alt="resource" />
      </div>

      <div class="flex w-full justify-between px-3 pb-3">
        <div class="flex flex-col">
          <span class="text-xs text-secondary">stock</span>
          <p class="font-medium">{{ formattedStock }}</p>
        </div>
        <div class="flex flex-col justify-center">
          <span class="text-xs text-secondary">total</span>
          <div class="flex gap-1">
            <img src="/images/attributes/credits.png" class="h-5" alt="credits" />
            <p :class="{ 'text-red-600': notEnoughCredits }" class="font-medium text-sm">{{ formattedTotalCost }}</p>
          </div>
        </div>
      </div>
    </div>

    <form @submit.prevent class="flex flex-col border-t border-primary/50">
      <div class="flex items-center justify-between">
        <!-- Sell Button -->
        <button
          class="px-4 py-3 bg-tertiary text-light font-semibold transition rounded-bl-xl border-r border-tertiary-light hover:bg-tertiary-dark focus:outline-none disabled:hover:bg-tertiary disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="isSellDisabled" @click="sellResource" type="button">
          Sell
        </button>
        <button
          class="px-2 py-3 bg-primary/40 text-light hover:bg-primary transition font-semibold border-r border-primary focus:outline-none disabled:hover:bg-primary/40 disabled:opacity-40 disabled:cursor-not-allowed"
          @click="setUserResourcesInput" type="button" aria-label="Maximum sellable amount">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="w-6 h-6" viewBox="0 0 24 24">
            <path fill="currentColor"
              d="m7.825 12l3.875 3.9q.275.275.288.688t-.288.712q-.275.275-.7.275t-.7-.275l-4.6-4.6q-.15-.15-.213-.325T5.426 12t.063-.375t.212-.325l4.6-4.6q.275-.275.688-.287t.712.287q.275.275.275.7t-.275.7zm6.6 0l3.875 3.9q.275.275.288.688t-.288.712q-.275.275-.7.275t-.7-.275l-4.6-4.6q-.15-.15-.213-.325T12.026 12t.063-.375t.212-.325l4.6-4.6q.275-.275.688-.287t.712.287q.275.275.275.7t-.275.7z" />
          </svg>
        </button>
        <!-- Input -->
        <AppInput :maxlength="7" v-model="form.amount" name="amount"
          class="!py-3 !px-0 !w-full !rounded-none !border-0 !bg-primary/40 text-center focus:!ring-0 focus:!border-x-2 transition-colors" />
        <button
          class="px-2 py-3 bg-primary/40 text-light hover:bg-primary transition font-semibold border-l border-primary focus:outline-none disabled:hover:bg-primary/40 disabled:opacity-40 disabled:cursor-not-allowed"
          @click="setMaxAmount" type="button" aria-label="Maximum buyable amount">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="w-6 h-6" viewBox="0 0 24 24">
            <path fill="currentColor"
              d="M9.575 12L5.7 8.1q-.275-.275-.288-.687T5.7 6.7q.275-.275.7-.275t.7.275l4.6 4.6q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.6 4.6q-.275.275-.687.288T5.7 17.3q-.275-.275-.275-.7t.275-.7zm6.6 0L12.3 8.1q-.275-.275-.288-.687T12.3 6.7q.275-.275.7-.275t.7.275l4.6 4.6q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.6 4.6q-.275.275-.687.288T12.3 17.3q-.275-.275-.275-.7t.275-.7z" />
          </svg>
        </button>
        <!-- Buy Button -->
        <button
          class="px-4 py-3 bg-primary/40 text-light font-semibold transition rounded-br-xl border-l border-primary hover:bg-primary focus:outline-none disabled:hover:bg-primary/40 disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="isBuyDisabled" @click="buyResource" type="button">
          Buy
        </button>
      </div>
    </form>
  </div>
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
</style>

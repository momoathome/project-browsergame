<script lang="ts" setup>
import { computed } from 'vue';
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

const form = useForm({
  resource_id: props.marketData.id,
  amount: 0
});

function buyResource() {
  if (form.amount <= 0) {
    return;
  }

  form.post(route('market.buy'), {
    preserveState: true,

    onSuccess: () => {
      form.reset();
    },
  });
}

function sellResource() {
  if (form.amount <= 0) {
    return;
  }

  form.post(route('market.sell'), {
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
  const userCredits = usePage().props.userAttributes.find((attribute) => attribute.attribute_name === 'credits')?.attribute_value || 0;
  const userStorage = usePage().props.userAttributes.find((attribute) => attribute.attribute_name === 'storage')?.attribute_value || 0;
  const maxAmount = Math.min(Math.floor(userCredits / props.marketData.cost), userStorage);
  form.amount = maxAmount;
}
</script>

<template>
  <div class="rounded-3xl flex flex-col bg-base text-light content_card px-4 py-4 gap-4">
    <div class="flex justify-between items-end">
      <div class="flex flex-col">
        <span class="text-xs text-gray">ressource</span>
        <p class="font-semibold text-xl">{{ marketData.name }}</p>
      </div>
      <div class="flex items-center gap-x-1">
        <img src="/images/attributes/credits.png" class="h-6" alt="credits" />
        <div class="flex flex-col">
          <span class="text-xs text-secondary">price</span>
          <p class="font-medium">{{ formattedCost }}</p>
        </div>
      </div>
    </div>

    <div class="relative flex justify-center items-center py-4">
      <div class="group relative">
        <img :src="marketData.image" class="h-[64px] cursor-pointer" @click="setUserResourcesInput" @click.shift="setMaxAmount" alt="resource" />
        <AppTooltip class="py-2 px-3" label="click to add all sellable resources<br>shift click to add all buyable resources" position="bottom" />
      </div>
    </div>

    <Divider />

    <form @submit.prevent class="flex flex-col gap-4">
      <div class="flex w-full justify-between">
        <div class="flex flex-col ms-2">
          <span class="text-xs text-secondary">stock</span>
          <p class="font-medium">{{ formattedStock }}</p>
        </div>
        <div class="flex items-center gap-x-1">
          <img src="/images/attributes/credits.png" class="h-6" alt="credits" />
          <div class="flex flex-col me-1">
            <span class="text-xs text-secondary">total</span>
            <p class="font-medium text-sm">{{ formattedTotalCost }}</p>
          </div>
        </div>
      </div>
      <div class="flex justify-between gap-2">
        <SecondaryButton @click="sellResource">
          Sell
        </SecondaryButton>
        <AppInput :maxlength="5" v-model="form.amount" class="px-1" />
        <PrimaryButton @click="buyResource">
          Buy
        </PrimaryButton>
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
    0 0 20px -2px hsl(var(--glow-color) / 0.15);
  border: 1px solid hsl(210deg 30% 25% / 0.5);
}
</style>

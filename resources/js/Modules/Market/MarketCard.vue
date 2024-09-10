<script lang="ts" setup>
import { type PropType, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { numberFormat } from '@/Utils/format';
import Divider from '@/Components/Divider.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AppInput from '@/Components/AppInput.vue';

interface Props {
  id: number
  name: string
  description: string
  image: string
  cost: number
  stock: number
}

const props = defineProps({
  marketData: {
    type: Object as PropType<Props>,
    required: true
  }
});

const formattedCost = computed(() => numberFormat(props.marketData.cost));
const formattedStock = computed(() => numberFormat(props.marketData.stock));

const form = useForm({
  resource_id: props.marketData.id,
  amount: 0
});

function buyResource() {
  if (form.amount <= 0) {
    return;
  }

  form.post(`/market/buy`, {
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

  form.post(`/market/sell`, {
    onSuccess: () => {
      form.reset();
    },
  });
}

</script>

<template>
  <div class="rounded-3xl flex flex-col bg-base text-light content_card px-4 py-6 gap-4">
    <div class="flex justify-between items-end">
      <div class="flex flex-col">
        <span class="text-xs text-gray">ressource</span>
        <p class="font-semibold text-xl">{{ marketData.name }}</p>
      </div>
      <div class="flex items-center gap-x-1">
        <img src="/storage/attributes/credits.png" class="h-6" alt="credits" />
        <div class="flex flex-col">
          <span class="text-xs text-secondary">price</span>
          <p class="font-medium">{{ formattedCost }}</p>
        </div>
      </div>
    </div>

    <div class="relative flex justify-center items-center py-2">
      <img :src="marketData.image" class="h-[56px]" alt="resource" />
    </div>

    <Divider />

    <form @submit.prevent="sellResource; buyResource" class="flex flex-col gap-4">
      <div class="flex w-full justify-between">
        <div class="flex flex-col">
          <span class="text-xs text-secondary">stock</span>
          <p class="font-medium">{{ formattedStock }}</p>
        </div>

        <AppInput :maxlength="6" v-model="form.amount" class="w-20 me-2" />

      </div>
      <div class="flex justify-between gap-6">
        <SecondaryButton @click="sellResource" class="w-full">
          Sell
        </SecondaryButton>
        <PrimaryButton @click="buyResource" class="w-full">
          Buy
        </PrimaryButton>
      </div>
    </form>
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
</style>

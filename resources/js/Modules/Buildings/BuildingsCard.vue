<script lang="ts" setup>
import { computed } from 'vue';
import { timeFormat } from '@/Utils/format';
import Divider from '@/Components/Divider.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AppCardTimer from '@/Components/AppCardTimer.vue';
import type { FormattedBuilding } from '@/types/types';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
  moduleData: FormattedBuilding
}>();

const formattedBuildTime = computed(() => timeFormat(props.moduleData.build_time));
// const formattedEnergy = computed(() => numberFormat(props.moduleData.energy!));

const form = useForm({
  buildingId: props.moduleData.id
});

function upgradeModule() {
  form.post(`/buildings/${props.moduleData.id}/update`, {
    preserveState: true,
    onSuccess: () => {
      // 
    },
  });
}
</script>

<template>
  <div class="flex flex-col rounded-3xl bg-base content_card text-[#DADCE5]">
    <div class="image relative">
      <img :src="moduleData.image" class="rounded-t-3xl object-fit aspect-[5/3]" />
    </div>
    <div class="px-6 pt-0 pb-6 flex flex-col gap-4">
      <div class="flex flex-col gap-4">
        <div class="flex justify-between">
          <p class="font-semibold text-2xl">{{ moduleData.name }}</p>
          <div class="flex flex-col items-center">
            <span class="text-sm font-medium text-secondary">Build Time</span>
            <p class="font-medium text-sm">{{ formattedBuildTime }}</p>
          </div>
        </div>
        <p class="text-gray text-sm">{{ moduleData.description }}</p>
      </div>

      <Divider />

      <div class="grid grid-cols-4 gap-4 items-center">
        <div class="flex flex-col gap-1 items-center" v-for="resource in moduleData.resources" :key="resource.name">
          <img :src="resource.image" class="h-8 w-8" />
          <p class="font-medium text-sm">{{ resource.amount }}</p>
        </div>
      </div>
      <div class="flex justify-center">
        <PrimaryButton @click="upgradeModule">
          Upgrade
        </PrimaryButton>
      </div>
      <AppCardTimer :time="moduleData.build_time" description="upgrade to lv. 2" />
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

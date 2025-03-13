<script lang="ts" setup>
import { computed, ref } from 'vue';
import { timeFormat } from '@/Utils/format';
import Divider from '@/Components/Divider.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AppCardTimer from '@/Components/AppCardTimer.vue';
import type { FormattedBuilding } from '@/types/types';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
  building: FormattedBuilding
}>();


const isUpgrading = ref(props.building.is_upgrading || false);
const upgradeEndTime = ref(props.building.upgrade_end_time || null);
const formattedBuildTime = computed(() => timeFormat(props.building.build_time));
// const formattedEnergy = computed(() => numberFormat(props.building.energy!));

function upgradeBuilding() {
  router.post(route('buildings.update', props.building.id), {
    preserveState: true,
    onSuccess: (page) => {
      if (page.props.flash.success) {
        isUpgrading.value = true;
        // Du könntest hier die Endzeit setzen, wenn sie vom Server zurückgegeben wird
      }
    },
    onError: (error) => {
      console.error('Building upgrade failed:', error);
    }
  });
}


</script>

<template>
  <div class="flex flex-col rounded-3xl bg-base content_card text-light">
    <div class="image relative">
      <img :src="building.image" class="rounded-t-3xl object-cover aspect-[5/3] min-h-[195px]" alt="" />
    </div>
    <div class="px-6 pt-0 pb-6 flex flex-col gap-4">
      <div class="flex flex-col gap-4">
        <div class="flex justify-between">
          <p class="font-semibold text-2xl">{{ building.name }}</p>
          <div class="flex">
            <span class="text-sm font-medium mt-2 me-1 text-secondary">lv.</span>
            <p class="text-xl">{{ building.level }}</p>
          </div>
        </div>
        <p class="text-gray text-sm">{{ building.description }}</p>
      </div>

      <Divider />

      <div class="grid grid-cols-4 gap-4 items-center">
        <div class="flex flex-col gap-1 items-center" v-for="resource in building.resources" :key="resource.name">
          <img :src="resource.image" class="h-7 w-7" alt="resource" />
          <p class="font-medium text-sm">{{ resource.amount }}</p>
        </div>
      </div>
      <div class="flex justify-center my-2">
        <PrimaryButton @click="upgradeBuilding">
          Upgrade
        </PrimaryButton>
      </div>
      <AppCardTimer :time="building.build_time" :description="`upgrade to lv. ${building.level + 1}`" />
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

<script lang="ts" setup>
import { computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import UserResources from '@/Modules/App/UserResources.vue';
import UserQueue from '@/Modules/App/UserQueue.vue';
import AppNavigation from '@/Modules/App/AppNavigation.vue';
import Divider from '@/Components/Divider.vue';
import AppTooltip from '@/Components/AppTooltip.vue';
import { numberFormat } from '@/Utils/format';

const props = defineProps<{
  title: string
}>()

const logout = () => {
  router.post(route('logout'));
};

const attributeLabels = {
  storage: 'Resource storage',
  research_points: 'Research Points',
  energy: 'Energy',
  influence: 'Influence',
  credits: 'Credits',
  unit_limit: 'Crew Limit',
  total_units: 'Total Units',
  scan_range: 'Scan Range',
};

const formattedAttributesNames = computed(() => {
  return usePage().props.userAttributes.map((attribute) => {
    const attributeName = attribute ? attribute.attribute_name : null;
    return {
      name: attributeName,
      label: attributeName ? (attributeLabels[attributeName] || attributeName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())) : null,
    };
  });
});

const formattedAttributes = computed(() => {
  return usePage().props.userAttributes.map((attribute) => {
    return {
      // attribute name from formattedAttributesNames
      name: attribute ? formattedAttributesNames.value.find((item) => item.name === attribute.attribute_name)?.name : null,
      label: attribute ? formattedAttributesNames.value.find((item) => item.name === attribute.attribute_name)?.label : null,
      amount: computed(() => numberFormat(attribute.attribute_value)),
      image: attribute ? attribute.image : null,
    };
  });
});

const unitsRatio = computed(() => {
  const totalUnits = usePage().props.userAttributes.find(attr => attr.attribute_name === 'total_units')?.attribute_value || 0;
  const unitLimit = usePage().props.userAttributes.find(attr => attr.attribute_name === 'unit_limit')?.attribute_value || 0;
  return `${numberFormat(totalUnits)} / ${numberFormat(unitLimit)}`;
});
</script>

<template>
  <div>

    <Head :title="title" />

    <Banner />

    <AppNavigation />

    <div class="min-h-screen bg-gray-200">

      <!-- Page Heading -->
      <header class="bg-[hsl(263,45%,7%)] flex flex-col gap-2 py-2 px-4">
        <div class="flex justify-between items-center">

          <UserResources />

          <div class="flex gap-4 items-center">
            <!-- units Ratio -->
            <div class="relative group flex flex-col gap-1 items-center">
              <img src="/storage/attributes/unit_limit.png" class="h-7" alt="Units">
              <span class="text-sm font-medium text-white">
                {{ unitsRatio }}
              </span>
              <AppTooltip label="Crew limit" position="bottom" class="!mt-3" />
            </div>

            <!-- total resources -->
            <div class="relative group flex flex-col gap-1 items-center"
              v-for="attribute in formattedAttributes.filter(attr => !['total_units', 'unit_limit', 'scan_range'].includes(attr.name))"
              :key="attribute.name">
              <img :src="`/storage/attributes/${attribute.name}.png`" class="h-7" alt="">
              <span class="text-sm font-medium text-white">
                {{ attribute.amount }}
              </span>
              <AppTooltip :label="attribute.label" position="bottom" class="!mt-3" />
            </div>

            <Divider class="!w-[2px] h-[24px] bg-primary/50" />
            <!-- onClick open settings Menu/Modal -->
            <!-- <img src="/storage/MenuFilled.svg" alt="Menu" @click="logout" /> -->
             <p class="text-white cursor-pointer">logout</p>
          </div>
        </div>

        <Divider class="bg-primary/50" />
        <!-- userQueue container -->
        <div class="flex gap-2">
          <UserQueue />
        </div>
      </header>

      <!-- Page Content -->
      <main>
        <slot />
      </main>

      <pre>{{ $page.props }}</pre>

    </div>
  </div>
</template>

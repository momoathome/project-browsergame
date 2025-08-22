<script lang="ts" setup>
import { numberFormat } from '@/Utils/format';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';

const page = usePage();

const attributeLabels = {
  storage: 'Resource storage',
  research_points: 'Research Points',
  energy: 'Energy',
  influence: 'Influence',
  credits: 'Credits',
  crew_limit: 'Crew Limit',
  total_units: 'Total Units',
  scan_range: 'Scan Range',
};

const formattedAttributesNames = computed(() => {
  return page.props.userAttributes.map((attribute) => {
    const attributeName = attribute ? attribute.attribute_name : null;
    return {
      name: attributeName,
      label: attributeName ? (attributeLabels[attributeName] || attributeName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())) : null,
    };
  });
});

const formattedAttributes = computed(() => {
  return page.props.userAttributes.map((attribute) => {
    return {
      // attribute name from formattedAttributesNames
      name: attribute ? formattedAttributesNames.value.find((item) => item.name === attribute.attribute_name)?.name : null,
      label: attribute ? formattedAttributesNames.value.find((item) => item.name === attribute.attribute_name)?.label : null,
      amount: computed(() => numberFormat(attribute.attribute_value))
    };
  });
});

const unitsRatio = computed(() => {
  const totalUnits = page.props.userAttributes.find(attr => attr.attribute_name === 'total_units')?.attribute_value || 0;
  const crewLimit = page.props.userAttributes.find(attr => attr.attribute_name === 'crew_limit')?.attribute_value || 0;
  return `${numberFormat(totalUnits)} / ${numberFormat(crewLimit)}`;
});
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5 gap-1 rounded-sm">
    <div class="relative group flex gap-1 items-center p-2 border-base border rounded-md">
      <span class="flex items-center justify-center">
        <img src="/images/attributes/unit_limit.png" class="max-h-5" alt="Units">
      </span>
      <span class="text-sm font-medium text-white text-nowrap">
        {{ unitsRatio }}
      </span>
      <AppTooltip label="Crew limit" position="bottom" class="!mt-3" />
    </div>

    <!-- total resources -->
    <div class="relative group flex gap-1 items-center p-2 border-base border rounded-md"
      v-for="attribute in formattedAttributes.filter(attr => !['total_units', 'crew_limit', 'scan_range', 'production_speed', 'base_defense', 'energy'].includes(attr.name))"
      :key="attribute.name">
      <span class="flex items-center justify-center">
        <img :src="`/images/attributes/${attribute.name}.png`" class="max-h-5" alt="">
      </span>
      <span class="text-sm font-medium text-white">
        {{ attribute.amount }}
      </span>
      <AppTooltip :label="attribute.label" position="bottom" class="!mt-3" />
    </div>
  </div>

</template>

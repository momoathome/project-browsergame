<script lang="ts" setup>
import { numberFormat } from '@/Utils/format';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppTooltip from '@/Components/AppTooltip.vue';

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
      amount: computed(() => numberFormat(attribute.attribute_value))
    };
  });
});

const unitsRatio = computed(() => {
  const totalUnits = usePage().props.userAttributes.find(attr => attr.attribute_name === 'total_units')?.attribute_value || 0;
  const crewLimit = usePage().props.userAttributes.find(attr => attr.attribute_name === 'crew_limit')?.attribute_value || 0;
  return `${numberFormat(totalUnits)} / ${numberFormat(crewLimit)}`;
});
</script>

<template>
    <div class="relative group flex flex-col gap-1 items-center">
        <img src="/storage/attributes/unit_limit.png" class="h-7" alt="Units">
        <span class="text-sm font-medium text-white">
            {{ unitsRatio }}
        </span>
        <AppTooltip label="Crew limit" position="bottom" class="!mt-3" />
    </div>

    <!-- total resources -->
    <div class="relative group flex flex-col gap-1 items-center"
        v-for="attribute in formattedAttributes.filter(attr => !['total_units', 'crew_limit', 'scan_range', 'production_speed', 'base_defense', 'energy'].includes(attr.name))"
        :key="attribute.name">
        <img :src="`/storage/attributes/${attribute.name}.png`" class="h-7" alt="">
        <span class="text-sm font-medium text-white">
            {{ attribute.amount }}
        </span>
        <AppTooltip :label="attribute.label" position="bottom" class="!mt-3" />
    </div>
</template>

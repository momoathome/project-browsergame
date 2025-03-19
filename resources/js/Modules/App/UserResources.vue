<script lang="ts" setup>
import { usePage, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { numberFormat } from '@/Utils/format';
import AppTooltip from '@/Components/AppTooltip.vue';

const page = usePage();
const form = useForm({
  resource_id: null,
  amount: 500
});

const formattedResources = computed(() => {
  return page.props.userResources.map((resource) => {
    return {
      resource_id: resource.resource_id || null,
      name: resource.resource ? resource.resource.name : null,
      description: resource.resource ? resource.resource.description : null,
      image: resource.resource ? resource.resource.image : null,
      amount: computed(() => numberFormat(resource.amount)),
    };
  });
});

function addResource(resourceId) {
  form.resource_id = resourceId;
  form.post('/resources/add', {
    onSuccess: () => {
      //
    },
  });
}
</script>

<template>
  <div class="grid gap-4 grid-cols-12 w-max">
    <div class="relative group flex flex-col gap-1 items-center" v-for="resource in formattedResources" :key="resource.name">
      <span @click="addResource(resource.resource_id)">
        <img :src="resource.image" class="max-h-6 cursor-pointer" />
      </span>
      <span class="text-sm font-medium text-white">
        {{ resource.amount }}
      </span>

      <AppTooltip :label="resource.name" position="bottom" class="!mt-3" />
    </div>
  </div>
</template>

<style scoped>
.grid {
  grid-template-columns: repeat(13, minmax(0, 1fr));
}
</style>

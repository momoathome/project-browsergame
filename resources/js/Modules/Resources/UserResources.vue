<script lang="ts" setup>
import { usePage, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { numberFormat } from '@/Utils/format';

const page = usePage();
const form = useForm({
  resource_id: null,
  amount: 500
});

const formattedResources = computed(() => {
  return page.props.userResources.map((resource) => {
    return {
      resource_id: resource.resource_id || null,
      name: resource.resources ? resource.resources.name : null,
      description: resource.resources ? resource.resources.description : null,
      image: resource.resources ? resource.resources.image : null,
      count: computed(() => numberFormat(resource.count)),
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
  <div class="grid gap-2 grid-cols-12 w-max">
    <div class="flex flex-col gap-1 items-center" v-for="resource in formattedResources" :key="resource.name">
      <span @click="addResource(resource.resource_id)">
        <img :src="resource.image" class="max-h-6 cursor-pointer" />
      </span>
      <span class="text-sm font-medium text-white">
        {{ resource.count }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.grid {
  grid-template-columns: repeat(13, minmax(0, 1fr));
}
</style>

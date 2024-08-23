<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { numberFormat } from '@/Utils/format';

const formattedResources = computed(() => {
  return usePage().props.userResources.map((resource) => {
    return {
      resource_id: resource.resource_id || null,
      name: resource.name || (resource.resource ? resource.resource.name : null),
      description: resource.resource ? resource.resource.description : null,
      image: resource.image || (resource.resource ? resource.resource.image : null),
      count: computed(() => numberFormat(resource.count)),
    };
  });
});


</script>

<template>
  <div class="grid gap-2 grid-cols-12 max-w-fit">
    <div class="flex flex-col gap-1 items-center" v-for="resource in formattedResources" :key="resource.name">
      <span>
        <img :src="resource.image" class="max-h-6" />
      </span>
      <span class="text-sm font-medium">
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

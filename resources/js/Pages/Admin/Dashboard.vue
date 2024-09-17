<script lang="ts" setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ResourceDistribution from '@/Modules/Admin/ResourceDistribution.vue';
import type { Resource } from '@/types/types';


const props = defineProps<{
  universeResources: Resource[];
}>()
</script>

<template>
  <AppLayout title="dashboard">
    <div class="mx-8 my-8">
      <h1 class="text-3xl mb-4 font-bold">
        Dashboard
      </h1>
      <div class="grid grid-cols-3">
        <ResourceDistribution :universeResources="universeResources" />
        <!-- other cards -->
      </div>
    </div>
  </AppLayout>
</template>

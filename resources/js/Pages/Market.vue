<script lang="ts" setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps(['market']);

</script>

<template>
  <AppLayout title="market">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        market
      </h2>
    </template>

    <h1 class="text-3xl font-bold">
      Hello world!
    </h1>
  </AppLayout>
</template>

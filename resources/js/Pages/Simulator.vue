<script lang="ts" setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BattleCalcTest from '@/Modules/Simulator/BattleCalcTest.vue';

</script>

<template>
  <AppLayout title="simulator">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        simulator
      </h2>
    </template>

    <BattleCalcTest />

  </AppLayout>
</template>

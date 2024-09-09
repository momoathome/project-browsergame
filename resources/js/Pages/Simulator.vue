<script lang="ts" setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BattleSimulator from '@/Modules/Simulator/BattleSimulator.vue';

const props = defineProps({
  result: {
    type: Object,
  }
});
</script>

<template>
  <AppLayout title="simulator">
    <BattleSimulator :result="result" />

  </AppLayout>
</template>

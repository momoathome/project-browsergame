<script lang="ts" setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CombatLogs from '@/Modules/Logbook/CombatLogs.vue';
import AsteroidMiningLogs from '@/Modules/Logbook/AsteroidMiningLogs.vue';

const props = defineProps<{
  combatLogs: any[],
  asteroidMinesLogs: any[],
  userResources: any[],
  spacecrafts: any[],
}>()

</script>

<template>
  <AppLayout title="logbook">


    <div class="space-y-8">
      <CombatLogs
        :logs="props.combatLogs"
        :user-resources="props.userResources"
        :spacecrafts="props.spacecrafts"
      />
      <AsteroidMiningLogs
        :logs="props.asteroidMinesLogs"
        :user-resources="props.userResources"
        :spacecrafts="props.spacecrafts"
      />
    </div>

  </AppLayout>
</template>

<style scoped>
.content_card {
  --shadow-color: 210deg 30% 15%;
  --glow-color: 210deg 70% 50%;

  box-shadow: 1px 1px 1.6px hsl(var(--shadow-color) / 0.3),
    3.5px 3.5px 5.6px -0.8px hsl(var(--shadow-color) / 0.3),
    8.8px 8.8px 14px -1.7px hsl(var(--shadow-color) / 0.35),
    0 0 20px -2px hsl(var(--glow-color) / 0.15);
}
</style>

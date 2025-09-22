<script lang="ts" setup>
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
</template>

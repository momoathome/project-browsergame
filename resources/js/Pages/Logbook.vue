<script lang="ts" setup>
import CombatLogs from '@/Modules/Logbook/CombatLogs.vue';
import AsteroidMiningLogs from '@/Modules/Logbook/AsteroidMiningLogs.vue';
import { useResourceStore } from '@/Composables/useResourceStore';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';

const props = defineProps<{
  combatLogs: any[],
  asteroidMinesLogs: any[],
}>()

const { userResources } = useResourceStore();
const { spacecrafts } = useSpacecraftStore();

</script>

<template>
  <div class="space-y-8">
    <CombatLogs
      :logs="props.combatLogs"
      :user-resources="userResources ?? []"
      :spacecrafts="spacecrafts ?? []"
    />
    <AsteroidMiningLogs
      :logs="props.asteroidMinesLogs"
      :user-resources="userResources ?? []"
      :spacecrafts="spacecrafts ?? []"
    />
  </div>
</template>

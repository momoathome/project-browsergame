<script lang="ts" setup>
import { ref, computed, onMounted } from 'vue';
import { numberFormat } from '@/Utils/format';
import type { Spacecraft, UserResources } from '@/types/types';

const props = defineProps<{
  logs: any[],
  userResources: UserResources[],
  spacecrafts: Spacecraft[],
}>()

onMounted(() => {
  console.log(props.logs);
  console.log(props.userResources);
  console.log(props.spacecrafts);
});

const selectedLog = ref<any | null>(null);

const resourceMap = computed(() => {
  const map = {};
  props.userResources.forEach(res => {
    map[res.resource_id] = res.resource;
  });
  return map;
});

const spacecraftMap = computed(() => {
  const map = {};
  props.spacecrafts.forEach(sc => {
    map[sc.name] = {
      type: sc.type,
      image: sc.image,
      attack: sc.attack,
      defense: sc.defense,
    };
  });
  return map;
});

const getTypeIcon = (type) => {
  switch (type) {
    case 'Fighter': return '/images/navigation/simulator.png';
    case 'Miner': return '/images/attributes/storage.png';
    case 'Transporter': return '/images/cargo.png';
    case 'mining': return '/images/navigation/asteroidmap.png';
    case 'combat': return '/images/navigation/simulator.png';
    default: return '';
  }
};

</script>
<template>
  <div class="space-y-4">
    <div v-for="log in props.logs" :key="log.id" class="bg-base rounded-xl border-primary/25 border-2 border-solid">
      <button
        class="w-full flex flex-row items-center justify-between px-4 py-3 text-left bg-base-dark hover:bg-base-dark/60 rounded-xl transition-colors duration-100 focus:outline-none"
        @click="selectedLog && selectedLog.id === log.id ? selectedLog = null : selectedLog = log"
      >
        <div class="flex flex-row gap-4 items-center flex-1">
          <span class="font-bold text-secondary">{{ log.attacker?.name || log.attacker_name }}</span>
          <span class="text-light">attacks</span>
          <span class="font-bold text-primary-light">{{ log.defender?.name || log.defender_name }}</span>
          <span class="text-light">Winner:
            <span v-if="log.winner === 'attacker'" class="font-bold text-secondary">
              {{ log.attacker?.name || log.attacker_name }}
            </span>
            <span v-else class="font-bold text-primary">
              {{ log.defender?.name || log.defender_name }}
            </span>
          </span>
          <span class="ml-2 text-light">{{ new Date(log.date).toLocaleString() }}</span>
        </div>
        <svg :class="['transition-transform text-light', selectedLog && selectedLog.id === log.id ? 'rotate-90' : '']" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
      </button>
      <transition name="fade">
        <div v-if="selectedLog && selectedLog.id === log.id" class="px-4 pb-4 pt-2">
          <div class="flex flex-col md:flex-row gap-8 mb-6">
            <!-- Angreifer Tabelle -->
            <div class="flex-1">
              <h3 class="text-lg font-bold mb-2 text-secondary">{{ log.attacker?.name || log.attacker_name }}</h3>
              <table class="w-full bg-base text-light rounded-lg overflow-hidden">
                <thead>
                  <tr>
                    <th class="px-3 py-2 text-start">Spacecraft</th>
                    <th class="px-3 py-2 text-start">Type</th>
                    <th class="px-3 py-2 text-start">Total Combat</th>
                    <th class="px-3 py-2 text-start">Quantity</th>
                    <th class="px-3 py-2 text-start">Losses</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ship in selectedLog.attacker_losses" :key="ship.name">
                    <td class="px-3 py-2 flex items-center gap-2">
                      {{ ship.name }}
                    </td>
                    <td class="px-3 py-2">
                      <img v-if="getTypeIcon(spacecraftMap[ship.name]?.type)" :src="getTypeIcon(spacecraftMap[ship.name]?.type)" class="w-6 h-6" />
                    </td>
                    <td class="px-3 py-2">{{ numberFormat((ship.combat ?? spacecraftMap[ship.name]?.combat ?? 0) * ship.count) }}</td>
                    <td class="px-3 py-2">{{ ship.count }}</td>
                    <td class="px-3 py-2 text-red-400">{{ ship.losses }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Verteidiger Tabelle -->
            <div class="flex-1">
              <h3 class="text-lg font-bold mb-2 text-primary-light">{{ log.defender?.name || log.defender_name }}</h3>
              <table class="w-full bg-base text-light rounded-lg overflow-hidden">
                <thead>
                  <tr>
                    <th class="px-3 py-2 text-start">Spacecraft</th>
                    <th class="px-3 py-2 text-start">Type</th>
                    <th class="px-3 py-2 text-start">Total Combat</th>
                    <th class="px-3 py-2 text-start">Quantity</th>
                    <th class="px-3 py-2 text-start">Losses</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ship in selectedLog.defender_losses" :key="ship.name">
                    <td class="px-3 py-2 flex items-center gap-2">
                      {{ ship.name }}
                    </td>
                    <td class="px-3 py-2">
                      <img v-if="getTypeIcon(spacecraftMap[ship.name]?.type)" :src="getTypeIcon(spacecraftMap[ship.name]?.type)" class="w-6 h-6" />
                    </td>
                    <td class="px-3 py-2">{{ numberFormat((ship.combat ?? spacecraftMap[ship.name]?.combat ?? 0) * ship.count) }}</td>
                    <td class="px-3 py-2">{{ ship.count }}</td>
                    <td class="px-3 py-2 text-red-400">{{ ship.losses }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <!-- Ressourcenübersicht -->
          <div v-if="selectedLog.plundered_resources && Object.keys(selectedLog.plundered_resources).length">
            <h3 class="text-lg font-bold mb-2 text-light">
              {{ $page.props.auth?.user?.id === selectedLog.defender_id ? 'Ressources lost' : 'Ressources gained' }}
            </h3>
            <ul class="flex flex-wrap gap-4 text-light">
              <li v-for="(amount, resourceId) in selectedLog.plundered_resources" :key="resourceId" class="flex items-center gap-2 bg-primary/10 rounded px-3 py-2">
                <img :src="resourceMap[resourceId]?.image" class="w-6 h-6" v-if="resourceMap[resourceId]?.image" />
                <span :class="[$page.props.auth?.user?.id === selectedLog.defender_id ? 'font-bold text-red-400' : 'font-bold text-light']">{{ numberFormat(amount) }}</span>
              </li>
            </ul>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>


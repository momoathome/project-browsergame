<script lang="ts" setup>
import { ref, computed } from 'vue';
import { numberFormat } from '@/Utils/format';

const props = defineProps<{
  logs: any[],
  userResources: any[],
  spacecrafts: any[],
}>()

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
    map[sc.details.name] = {
      type: sc.details.type,
      image: sc.details.image,
      combat: sc.combat
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
    <div v-for="log in props.logs" :key="log.id" class="bg-base rounded-xl border-primary border-2 border-solid">
      <button
        class="w-full flex flex-row items-center justify-between px-4 py-3 text-left bg-base-dark hover:bg-base-dark/60 rounded-xl transition-colors duration-100 focus:outline-none"
        @click="selectedLog && selectedLog.id === log.id ? selectedLog = null : selectedLog = log"
      >
        <div class="flex flex-row gap-4 items-center flex-1">
          <span class="font-bold text-yellow-300">Asteroid Mining</span>
          <span class="text-light">on</span>
          <span class="font-bold text-yellow-200">{{ log.asteroid_info?.name }}</span>
          <span class="text-light">({{ log.asteroid_info?.x }}, {{ log.asteroid_info?.y }})</span>
          <span class="text-light">Size: <span class="font-bold">{{ log.asteroid_info?.size }}</span></span>
          <span class="ml-2 text-light">{{ new Date(log.created_at).toLocaleString() }}</span>
        </div>
        <svg :class="['transition-transform text-light', selectedLog && selectedLog.id === log.id ? 'rotate-90' : '']" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
      </button>
      <transition name="fade">
        <div v-if="selectedLog && selectedLog.id === log.id" class="px-4 pb-4 pt-2">
          <div class="flex flex-col md:flex-row gap-8 mb-6">
            <!-- Eingesetzte Schiffe -->
            <div class="flex-1">
              <h3 class="text-lg font-bold mb-2 text-yellow-300">Spacecrafts Used</h3>
              <table class="w-full bg-base text-light rounded-lg overflow-hidden">
                <thead>
                  <tr>
                    <th class="px-3 py-2 text-start">Spacecraft</th>
                    <th class="px-3 py-2 text-start">Type</th>
                    <th class="px-3 py-2 text-start">Quantity</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(count, name) in selectedLog.spacecrafts_used" :key="name">
                    <td class="px-3 py-2 flex items-center gap-2">
                      {{ name }}
                    </td>
                    <td class="px-3 py-2">
                      <img v-if="getTypeIcon(spacecraftMap[name]?.type)" :src="getTypeIcon(spacecraftMap[name]?.type)" class="w-6 h-6" />
                    </td>
                    <td class="px-3 py-2">{{ count }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Extrahierte Ressourcen -->
            <div class="flex-1">
              <h3 class="text-lg font-bold mb-2 text-yellow-200">Resources Extracted</h3>
              <ul class="flex flex-wrap gap-4 text-light">
                <li v-for="(amount, resourceId) in selectedLog.resources_extracted" :key="resourceId" class="flex items-center gap-2 bg-yellow-400/10 rounded px-3 py-2">
                  <img :src="resourceMap[resourceId]?.image" class="w-6 h-6" v-if="resourceMap[resourceId]?.image" />
                  <span class="font-bold text-yellow-200">{{ numberFormat(amount) }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

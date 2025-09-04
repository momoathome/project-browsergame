<script lang="ts" setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useQueueStore } from '@/Composables/useQueueStore';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';
import { timeFormat, numberFormat } from '@/Utils/format';
import type { AsteroidAutoMineMission, AsteroidAutoMineResponse } from '@/types/api';
import { api } from '@/Services/api';
import axios from 'axios';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits(['close', 'redraw']);

const missions = ref<AsteroidAutoMineMission[]>([]);
const filter = ref('overflow');
const filterOptions = [
  { value: 'overflow', label: 'Overflow' },
  /* { value: 'smart', label: 'Smart' }, */
  { value: 'minimal', label: 'Minimal' },
];

async function getAutoMineMissions() {
  try {
    const response = await api.asteroids.autoMine({ filter: filter.value });
    missions.value = (response.data as AsteroidAutoMineResponse).missions ?? [];
  } catch (error) {
    console.error('Fehler beim AutoMine:', error);
  }
}

function onChangeFilter(newFilter: string) {
  filter.value = newFilter;
  getAutoMineMissions();
}

const selectedMissions = ref(new Set<number>(missions.value.map(m => m.asteroid.id)));

function toggleMission(id: number) {
  if (selectedMissions.value.has(id)) {
    selectedMissions.value.delete(id);
  } else {
    selectedMissions.value.add(id);
  }
}

function close() {
  if (isSubmitting.value) return;
  emit('close');
}


const selectedMissionList = computed(() =>
  missions.value.filter(m => selectedMissions.value.has(m.asteroid.id))
);
const totalOperations = computed(() => selectedMissionList.value.length);

const totalUnits = computed(() => {
  let sum = 0;
  selectedMissionList.value.forEach(mission => {
    Object.values(mission.spacecrafts).forEach(count => {
      sum += count;
    });
  });
  return sum;
});

const totalResources = computed(() => {
  const res: Record<string, number> = {};
  selectedMissionList.value.forEach(m => {
    Object.entries(m.resources).forEach(([type, amount]) => {
      res[type] = (res[type] || 0) + amount;
    });
  });
  return res;
});

const { refreshQueue } = useQueueStore();
const { refreshSpacecrafts } = useSpacecraftStore();
const isSubmitting = ref(false);
async function startAutoMine() {
  if (isSubmitting.value) return;
  if (selectedMissions.value.size === 0) return;

  isSubmitting.value = true;
  const missionsToStart = missions.value.filter(m => selectedMissions.value.has(m.asteroid.id));
  try {
    await axios.post(route('asteroidMap.autoMineStart'), {
      missions: missionsToStart.map(m => ({
        asteroid_id: m.asteroid.id,
        spacecrafts: m.spacecrafts,
      }))
    });
    // Optional: Queue und Spacecrafts aktualisieren
    await refreshQueue();
    await refreshSpacecrafts();
  } catch (error) {
    // Fehlerbehandlung
    console.error(error);
  } finally {
    isSubmitting.value = false;
    emit('redraw');
    close();
  }
}

watch(() => props.open, (val) => {
  if (val) getAutoMineMissions();
});

watch(
  () => missions.value,
  (missions) => {
    selectedMissions.value = new Set(missions.map(m => m.asteroid.id));
  },
  { immediate: true }
);

watch(() => props.open, (open) => {
  if (open) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

const closeOnEscape = (e) => {
  if (e.key === 'Escape') {
    e.preventDefault();
    if (props.open) close();
  }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape);
  document.body.style.overflow = 'visible';
});
</script>

<template>
  <transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" @click="close"></div>

      <!-- Modal -->
      <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2
                  w-[1200px] max-w-[95vw] rounded-3xl border border-cyan-400/20
                  shadow-2xl">
        <div class="rounded-3xl bg-[#0b1623]/95" :class="isSubmitting ? 'opacity-80 pointer-events-none' : ''">
          <button type="button" class="absolute top-3 right-3 px-1 py-1 rounded-xl text-white font-medium border-transparent hover:bg-cyan-900/30 outline-none transition" @click="close">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path fill="currentColor" d="m8.382 17.025l-1.407-1.4L10.593 12L6.975 8.4L8.382 7L12 10.615L15.593 7L17 8.4L13.382 12L17 15.625l-1.407 1.4L12 13.41z"/></svg>
          </button>

          <div class="p-6">
            <h1 class="text-2xl text-white mb-6">Mining Operations</h1>

            <!-- Filter-Auswahl -->
            <div class="mb-6 flex gap-6 items-center pb-4 border-b border-white/5">
              <label class="text-light font-semibold">Mining-Filter:</label>
              <div class="flex gap-4">
                <label v-for="opt in filterOptions" :key="opt.value" class="flex items-center cursor-pointer select-none">
                    <input
                        type="radio"
                        :value="opt.value"
                        :checked="filter === opt.value"
                        @change="onChangeFilter(opt.value)"
                        class="hidden"
                    />
                  <span
                    class="w-5 h-5 rounded-full border-2 flex items-center justify-center mr-2 transition"
                    :class="filter === opt.value ? 'bg-cyan-500 border-cyan-500 ring-2 ring-cyan-500' : 'bg-slate-900 border-slate-500'"
                  >
                    <svg v-if="filter === opt.value" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24">
                      <circle cx="12" cy="12" r="6" fill="#fff"/>
                    </svg>
                  </span>
                  <span class="text-cyan-200 font-medium">{{ opt.label }}</span>
                </label>
              </div>
            </div>

            <!-- Zusammenfassung -->
            <div class="mb-6 flex flex-wrap gap-8 text-cyan-200">
                <div>
                    <span class="font-semibold text-light">Operations:</span> {{ totalOperations }}
                </div>
                <div>
                    <span class="font-semibold text-light">Total Units:</span> {{ totalUnits }}
                </div>
              <div>
                <span class="font-semibold text-light">Total Resources:</span>
                <span v-for="(amount, type) in totalResources" :key="type" class="mx-2">
                <img :src="`/images/resources/${type}.png`" class="h-5 inline" />
                {{ numberFormat(amount) }}
                </span>
              </div>
            </div>

            <!-- Missions-Liste -->
            <div class="max-h-[520px] overflow-y-auto fancy-scroll grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div v-for="mission in missions"
                    :key="mission.asteroid.id"
                    class="rounded-2xl border shadow-lg p-4 flex flex-col gap-2 relative cursor-pointer transition"
                    :class="selectedMissions.has(mission.asteroid.id)
                        ? 'border-cyan-400 bg-cyan-900/30'
                        : 'border-white/5 bg-slate-800/40'"
                    @click="toggleMission(mission.asteroid.id)"
                >
                <div class="absolute top-2 right-2">
                    <span
                        class="inline-block w-5 h-5 rounded-full border-2"
                        :class="selectedMissions.has(mission.asteroid.id)
                        ? 'bg-cyan-400 border-cyan-400'
                        : 'bg-slate-700 border-slate-500'"
                    ></span>
                </div>
                <div class="flex items-center gap-3">
                  <img :src="`/images/asteroids/Asteroid${(mission.asteroid.id % 7) + 2}.webp`" class="h-14 w-14 rounded-xl" />
                  <div>
                    <div class="font-semibold text-white">{{ mission.asteroid.name }}</div>
                    <div class="text-xs text-cyan-200">Size: {{ mission.asteroid.size }}</div>
                    <div class="text-xs text-cyan-200">Duration: {{ timeFormat(mission.duration) }} min</div>
                  </div>
                </div>
                <div class="flex flex-wrap gap-2 mt-2">
                    <span v-for="(amount, type) in mission.resources" :key="type" class="flex items-center gap-1 text-cyan-100">
                    <img :src="`/images/resources/${type}.png`" class="h-5" />
                    {{ numberFormat(amount) }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 mt-2 text-slate-300 text-sm">
                  <span v-for="(count, name) in mission.spacecrafts" :key="name" class="flex items-end gap-1">
                    <img :src="`/images/spacecraftTypes/Miner.png`" class="h-5" /> {{ name }}: {{ count }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Aktionen -->
            <div class="mt-8 flex justify-end gap-4">
              <button class="px-6 py-2 rounded-full transition bg-cyan-700 text-cyan-200 border border-cyan-700/30 hover:scale-105 hover:text-cyan-100 font-semibold text-base shadow disabled:cursor-not-allowed disabled:opacity-40"
                @click="startAutoMine"
                :disabled="selectedMissions.size === 0 || isSubmitting"
              >
                Start Operations
              </button>
              <button class="px-6 py-2 rounded-full bg-slate-900/80 text-white border border-cyan-700/30 hover:bg-cyan-900/30 font-semibold text-base shadow"
                @click="close">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.fancy-scroll::-webkit-scrollbar { width: 6px; }
.fancy-scroll::-webkit-scrollbar-thumb {
  background: #67e8f950;
  border-radius: 9999px;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.fade-enter-to, .fade-leave-from {
  opacity: 1;
}
</style>

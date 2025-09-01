<script setup lang="ts">
import axios from 'axios';
import { computed, reactive, ref, watch, onMounted, onUnmounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3';
import AsteroidModalResourceSvg from './AsteroidModalResourceSvg.vue'
import AppInput from '@/Modules/Shared/AppInput.vue';
import { QueueActionType } from '@/types/actionTypes';
import { numberFormat } from '@/Utils/format';
import { useAsteroidMining } from '@/Composables/useAsteroidMining';
import { useQueueStore } from '@/Composables/useQueueStore';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';
import { useSpacecraftUtils } from '@/Composables/useSpacecraftUtils';
import type { Station, SpacecraftSimple, Asteroid } from '@/types/types';

type Role = 'Fighter' | 'Miner' | 'Transporter'

export type ModalContent = {
  type: 'asteroid' | 'station' | 'undefined';
  data: Asteroid | Station;
  title: string;
}

const props = defineProps<{
  open: boolean,
  content: ModalContent,
  spacecrafts: SpacecraftSimple[] | undefined,
  userScanRange: number,
}>();

const emit = defineEmits(['close', 'redraw']);

const asteroidImages = [
  '/images/asteroids/Asteroid_full.webp',
  '/images/asteroids/Asteroid3_full.webp',
  '/images/asteroids/Asteroid4_full.webp',
  '/images/asteroids/Asteroid5_full.webp',
  '/images/asteroids/Asteroid6_full.webp',
  '/images/asteroids/Asteroid7_full.webp',
  '/images/asteroids/Asteroid8_full.webp',
  // ...weitere Bilder
];

const asteroidImageIndex = computed(() =>
  asteroid.value?.id !== undefined
    ? asteroid.value.id % asteroidImages.length
    : 0
);

const asteroidImageSrc = computed(() =>
  asteroidImages[asteroidImageIndex.value]
);

const asteroid = computed<Asteroid>(() => props.content.data as Asteroid);
const station = computed<Station>(() => props.content.data as Station);
const userStation = usePage().props.stations.find(station =>
  station.user_id === usePage().props.auth.user.id
);

// Auswahl (Mengen pro Schiff)
const form = useForm({
  asteroid_id: null as number | null,
  station_user_id: null as number | null,
  spacecrafts: {} as Record<string, number>
});

(props.spacecrafts ?? []).forEach(s => (form.spacecrafts[s.name] = 0))

const actionType = computed(() =>
  props.content.type === 'asteroid'
    ? QueueActionType.MINING
    : QueueActionType.COMBAT
);

const { miningDuration } = useAsteroidMining(
  asteroid,
  form.spacecrafts,
  props.spacecrafts,
  actionType
);

const {
  setMaxAvailableUnits,
  setMinNeededUnits, 
} = useSpacecraftUtils(
  computed(() => props.spacecrafts),
  form.spacecrafts,
  computed(() => props.content),
  actionType,
);

const tabs: Role[] = ['Fighter', 'Miner', 'Transporter']
const activeTab = ref<Role>(
  actionType.value === QueueActionType.MINING ? 'Miner' : 'Fighter'
)

// Reaktiv auf actionType-Änderung reagieren (z.B. wenn Modal für anderen Typ geöffnet wird)
watch(actionType, (val) => {
  activeTab.value = val === QueueActionType.MINING ? 'Miner' : 'Fighter';
});

const filtered = computed(() => props.spacecrafts.filter(s => s.type === activeTab.value))

const totals = computed(() => {
  let combat = 0, cargo = 0, speedWeighted = 0, unitCount = 0
  for (const s of props.spacecrafts) {
    const qty = form.spacecrafts[s.name] || 0
    combat += s.combat * qty
    cargo  += s.cargo  * qty
    speedWeighted += s.speed * qty
    unitCount += qty
  }
  const avgSpeed = unitCount ? Math.round(speedWeighted / unitCount) : 0
  return { combat, cargo, avgSpeed }
})

function inc(name: string, max: number) {
  if ((form.spacecrafts[name] || 0) >= max) return;
  form.spacecrafts[name] = (form.spacecrafts[name] || 0) + 1;
}
function dec(name: string) {
  form.spacecrafts[name] = Math.max(0, (form.spacecrafts[name] || 0) - 1);
}
function setMax(name: string, max: number) {
  form.spacecrafts[name] = max;
}

const distance = computed(() => {
  if (asteroid.value) {
    const userX = userStation.x;
    const userY = userStation.y;
    const asteroidX = asteroid.value.x;
    const asteroidY = asteroid.value.y;
    return Math.round(Math.sqrt(Math.pow(userX - asteroidX, 2) + Math.pow(userY - asteroidY, 2)));
  }
  return 0;
});

const canScanAsteroid = computed(() => {
  if (asteroid.value && distance.value) {
    return distance.value <= props.userScanRange;
  }
  return false;
});

const { refreshQueue } = useQueueStore();
const { refreshSpacecrafts } = useSpacecraftStore();
const isSubmitting = ref(false);

async function exploreAsteroid() {
  if (isSubmitting.value) return;
  if (asteroid.value) {
    form.asteroid_id = asteroid.value.id;
  }

  const noSpacecraftSelected = Object.values(form.spacecrafts).every((value) => value === 0);
  if (noSpacecraftSelected) return;

  isSubmitting.value = true;
  try {
    const { data } = await axios.post('/asteroidMap/update', form);
    // Zeige Erfolg/Fehler
    // Aktualisiere gezielt die Queue und ggf. den Asteroiden
    await refreshQueue();
    await refreshSpacecrafts();
    close();
  } catch (error) {
    // Fehlerbehandlung
  } finally {
    isSubmitting.value = false;
    emit('redraw');
  }
}

function fastExploreAsteroid() {
  const minUnits = setMinNeededUnits();
  Object.keys(form.spacecrafts).forEach(key => {
    form.spacecrafts[key] = minUnits[key] || 0;
  });
  exploreAsteroid();
}

function setMaxUnits() {
  const maxUnits = setMaxAvailableUnits();
  Object.keys(form.spacecrafts).forEach(key => {
    form.spacecrafts[key] = maxUnits[key] || 0;
  });
}

function setMinUnits() {
  const minUnits = setMinNeededUnits();
  Object.keys(form.spacecrafts).forEach(key => {
    form.spacecrafts[key] = minUnits[key] || 0;
  });
}

function resetSpacecraftsForm() {
  Object.keys(form.spacecrafts).forEach(key => {
    form.spacecrafts[key] = 0;
  });
}

async function attackUser() {
  if (isSubmitting.value) return;
  if (station.value) {
    form.station_user_id = station.value.user_id;
  }

  const noSpacecraftSelected = Object.values(form.spacecrafts).every((value) => value === 0);
  if (noSpacecraftSelected) return;

  isSubmitting.value = true;
    try {
    const { data } = await axios.post('/asteroidMap/combat', form);
    // Zeige Erfolg/Fehler
    // Aktualisiere gezielt die Queue und ggf. den Asteroiden
    await refreshQueue();
    await refreshSpacecrafts();
    close();
  } catch (error) {
    // Fehlerbehandlung
  } finally {
    isSubmitting.value = false;
    emit('redraw');
  }
}

async function startMission() {
  if (actionType.value === QueueActionType.MINING) {
    await exploreAsteroid();
  } else if (actionType.value === QueueActionType.COMBAT) {
    await attackUser();
  }
}

const resetForm = () => {
  resetSpacecraftsForm();
  form.asteroid_id = null;
  form.station_user_id = null;
};

const close = () => {
  resetForm();

  emit('close');
};

watch(() => props.open, (open) => {
  if (open) {
    resetForm();
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

function availableCount(s) {
  return s.count - (s.locked_count || 0)
}

// Extraktions-Schätzung für Asteroiden (Frontend-Logik analog Backend)
/* const estimatedExtraction = computed(() => {
  if (actionType.value !== QueueActionType.MINING || !asteroid.value || !asteroid.value.resources || !canScanAsteroid.value) return [];
  // Cargo berechnen
  let totalCargo = 0;
  let hasMiner = false;
  (props.spacecrafts ?? []).forEach(s => {
    const qty = form.spacecrafts[s.name] || 0;
    totalCargo += s.cargo * qty;
    if (s.type === 'Miner' && qty > 0) hasMiner = true;
  });
  if (totalCargo === 0) return asteroid.value.resources.map(r => ({ resource_type: r.resource_type, amount: 0 }));
  const extractionMultiplier = hasMiner ? 1 : 0.5;
  // Schritt 1: initial extraction (maximal pro Ressource, aber nicht mehr als cargo*multiplier)
  const initialExtraction = asteroid.value.resources.map(r => {
    const maxExtract = Math.min(r.amount, Math.floor(totalCargo * extractionMultiplier));
    return { resource_type: r.resource_type, amount: maxExtract };
  });
  const totalAvailable = initialExtraction.reduce((sum, r) => sum + r.amount, 0);
  // Schritt 2: extraction ratio
  const extractionRatio = totalAvailable > totalCargo ? totalCargo / totalAvailable : 1;
  // Schritt 3: finale Extraktion
  return initialExtraction.map(r => ({
    resource_type: r.resource_type,
    amount: Math.floor(r.amount * extractionRatio)
  }));
}); */
</script>

<template>
  <transition name="fade">
    <div v-if="open" class="fixed inset-0 z-50">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" @click="close"></div>

      <!-- Modal -->
      <div
        class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2
               w-[1200px] max-w-[95vw] rounded-3xl border border-cyan-400/20
               shadow-2xl"
      >
        <!-- Outer Glow -->
        <div class="rounded-3xl">
          <div class="rounded-3xl bg-[#0b1623]/95">

            <button type="button" class="absolute top-3 right-3 px-1 py-1 rounded-xl text-white font-medium border-transparent border-solid hover:border-solid outline-none transition hover:bg-cyan-900/30" @click="close">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                <path fill="currentColor" d="m8.382 17.025l-1.407-1.4L10.593 12L6.975 8.4L8.382 7L12 10.615L15.593 7L17 8.4L13.382 12L17 15.625l-1.407 1.4L12 13.41z"/>
              </svg>
            </button>

            <!-- Body -->
            <div class="grid grid-cols-[420px_1fr] min-h-[600px] gap-6 p-6">

              <!-- Left Asteroid -->
              <div class="relative text-center">
                <h1 class="text-2xl flex justify-center text-white mt-2">{{ content.title }}</h1>

                <div class="relative flex items-center justify-center w-[360px] h-[360px] mx-auto">
                  <img
                    v-if="actionType === QueueActionType.MINING"
                    :src="asteroidImageSrc"
                    class="z-10"
                  />
                  <img
                    v-else
                    src="/images/station_full.webp"
                    class="z-10"
                  />
                  <AsteroidModalResourceSvg v-if="actionType === QueueActionType.MINING" :asteroid="asteroid" :showResources="canScanAsteroid" class="absolute inset-0" />
                </div>

                <div v-if="actionType === QueueActionType.MINING && canScanAsteroid" class="flex items-center justify-center gap-6 text-gray-300">
                  <span v-for="{ resource_type, amount } in asteroid.resources" :key="resource_type" class="flex gap-2">
                      <img :src="`/images/resources/${resource_type}.png`" class="h-6" alt="" />
                      {{ amount }}
                  </span>
                </div>

                <!-- Info/Fehlermeldungen/Estimation -->
                <div class="mt-8 px-4 min-h-[48px] flex flex-col text-start text-gray-300">
                  <template v-if="actionType === QueueActionType.COMBAT">
                    <span class="italic text-slate-400">no Informations available</span>
                  </template>
                  <template v-else-if="actionType === QueueActionType.MINING">
                    <span
                      v-if="asteroid.size === 'extreme' && (!form.spacecrafts['Titan'] || form.spacecrafts['Titan'] === 0)"
                      class="text-yellow-300 text-sm text-pretty"
                    >
                      Info: Extreme asteroids can only be mined by Massive Miners.
                    </span>
                  </template>
                </div>
              </div>

              <!-- Right: Tabs + Grid -->
              <div class="flex flex-col border border-solid rounded-3xl border-white/5 px-6 pb-4 pt-2">
                <!-- Header -->
                <div class="flex items-center justify-between py-4 border-b border-white/5 mb-4">
                    <div class="flex items-center gap-6">
                        <div class="px-3 py-1 text-light">Combat: <span class="text-cyan-300">{{ numberFormat(totals.combat) }}</span></div>
                        <div class="px-3 py-1 text-light">Cargo: <span class="text-cyan-300">{{ numberFormat(totals.cargo) }}</span></div>
                        <div class="px-3 py-1 text-light">
                            Travel Time: <span class="text-cyan-300">{{ miningDuration }}</span>
                        </div>
                    </div>

                    <button
                      @click="startMission" @shift.click="fastExploreAsteroid"
                      class="px-6 py-2 rounded-full bg-slate-900/80 text-cyan-200 border border-cyan-700/30 hover:bg-cyan-900/30 hover:text-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-400/60 focus:border-cyan-400 transition font-semibold text-base shadow flex items-center gap-2"
                    >
                      <span v-if="actionType === QueueActionType.MINING">Start Mining</span>
                      <span v-else>Start Attack</span>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="relative flex justify-between pb-4 border-b border-white/10 mb-4">
                  <div class="flex gap-1">
                    <button
                      v-for="t in tabs"
                      :key="t"
                      class="relative px-5 py-2 font-semibold text-sm transition
                        rounded-t-xl
                        flex items-center gap-2
                        focus:outline-none
                        hover:bg-cyan-400/10
                        text-cyan-100
                        "
                      :class="{
                        'bg-cyan-500/10 shadow text-cyan-200': t === activeTab,
                        '': t !== activeTab
                      }"
                      @click="activeTab = t"
                    >
                    <img :src="`/images/spacecraftTypes/${t}.png`" alt="" width="20" height="20" />
                      {{ t }}
                      <span
                        v-if="t === activeTab"
                        class="absolute left-0 -bottom-[2px] w-full h-1 bg-cyan-400 rounded-b-xl transition-all"
                      ></span>
                    </button>
                  </div>

                  <div class="flex gap-2 z-10">
                    <button
                      class="flex items-center gap-1 px-3 py-1 rounded-full bg-slate-900/80 text-cyan-300 border border-cyan-700/30 hover:bg-cyan-900/30 hover:text-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-400/60 focus:border-cyan-400 transition font-semibold text-sm shadow"
                      @click="setMaxUnits"
                      title="choose all available units for this category"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M12 4v16"/></svg>
                      Max
                    </button>
                    <button
                      class="flex items-center gap-1 px-3 py-1 rounded-full bg-slate-900/80 text-cyan-300 border border-cyan-700/30 hover:bg-cyan-900/30 hover:text-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-400/60 focus:border-cyan-400 transition font-semibold text-sm shadow"
                      @click="setMinUnits"
                      title="Set the minimum required units for this category"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16"/></svg>
                      Min
                    </button>
                    <button
                      class="flex items-center gap-1 px-3 py-1 rounded-full bg-slate-900/80 text-cyan-300 border border-cyan-700/30 hover:bg-cyan-900/30 hover:text-cyan-100 focus:outline-none focus:ring-2 focus:ring-cyan-400/60 focus:border-cyan-400 transition font-semibold text-sm shadow"
                      @click="resetSpacecraftsForm"
                      title="Set all units to 0"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                      Reset
                    </button>
                  </div>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-2 xl:grid-cols-3 gap-4 max-h-[520px] overflow-y-auto pr-2 fancy-scroll">
                  <div
                    v-for="s in filtered"
                    :key="s.id"
                    class="rounded-2xl border border-white/5 bg-slate-800/40 shadow-lg"
                  >
                    <div class="spacecraftImage relative bg-[#101d2c] flex items-center justify-center rounded-t-2xl">
                      <img :src="s.image" class="h-[90px] w-full rounded-t-2xl" />
                    </div>
                    <div class="px-4 pt-3">
                        <div class="flex justify-between items-center mb-1">
                            <h3 class="text-light font-semibold">{{ s.name }}</h3>
                            <span
                              class="text-cyan-100 cursor-pointer"
                              @click="setMax(s.name, availableCount(s))"
                              :title="`${availableCount(s)} verfügbar von ${s.count}`"
                            >
                              {{ availableCount(s) }}
                            </span>
                        </div>
                      <div class="flex justify-between">
                          <span class="text-sm text-slate-400">Combat: {{ numberFormat(s.combat) }}</span>
                          <span class="text-sm text-slate-400">Cargo: {{ numberFormat(s.cargo) }}</span>
                      </div>
                    </div>
                    <div class="px-4 pb-4 mt-2">
                      <div class="flex items-center justify-between rounded-xl bg-white/5 ring-1 ring-white/10 shadow-inner overflow-hidden">
                        <button
                          class="h-8 w-10 rounded-l-xl bg-slate-900/80 text-cyan-100 hover:bg-cyan-900/30 transition font-semibold border-r border-cyan-700/30 focus:outline-none disabled:hover:bg-slate-900/80 disabled:opacity-40 disabled:cursor-not-allowed"
                          @click="dec(s.name)"
                          @click.shift="form.spacecrafts[s.name] = Math.max(0, (form.spacecrafts[s.name] || 0) - 10)"
                          :disabled="availableCount(s) === 0"
                          type="button"
                        >−</button>
                        <AppInput
                          class="!py-1 !px-0 !w-14 !rounded-none !border-0 !bg-transparent text-center focus:!ring-0 focus:!border-cyan-400/80 focus:!border-x-2 transition-colors"
                          v-model="form.spacecrafts[s.name]"
                          :maxInputValue="availableCount(s)"
                          :maxlength="4"
                        />
                        <button
                          class="h-8 w-10 rounded-r-xl bg-slate-900/80 text-cyan-100 hover:bg-cyan-900/30 transition font-semibold border-l border-cyan-700/30 focus:outline-none disabled:hover:bg-slate-900/80 disabled:opacity-40 disabled:cursor-not-allowed"
                          @click="inc(s.name, availableCount(s))"
                          @click.shift="form.spacecrafts[s.name] = Math.min((form.spacecrafts[s.name] || 0) + 10, availableCount(s))"
                          :disabled="availableCount(s) === 0"
                          type="button"
                        >＋</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.asteroid-spin { animation: asteroid 40s linear infinite; }
@keyframes asteroid { to { transform: rotate(360deg); } }

/* Scrollbar */
.fancy-scroll::-webkit-scrollbar { width: 6px; }
.fancy-scroll::-webkit-scrollbar-thumb {
  background: #67e8f950;
  border-radius: 9999px;
}

.spacecraftImage::after {
  content: '';
  position: absolute;
  inset: 0;
  box-shadow: inset 0px -15px 20px 4px #101d2c,
    inset 0px -20px 45px 0px #101d2c;
  border-radius: 16px 16px 0 0;
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

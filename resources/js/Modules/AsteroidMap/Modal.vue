<script setup lang="ts">
import axios from 'axios';
import { computed, reactive, ref, watch, onMounted, onUnmounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3';
import AsteroidModalResourceSvg from './AsteroidModalResourceSvg.vue'
import AppInput from '@/Modules/Shared/AppInput.vue';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import { QueueActionType } from '@/types/actionTypes';
import { numberFormat } from '@/Utils/format';
import { useAsteroidMining } from '@/Composables/useAsteroidMining';
import { useQueueStore } from '@/Composables/useQueueStore';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';
import { useSpacecraftUtils } from '@/Composables/useSpacecraftUtils';
import { useBuildingStore } from '@/Composables/useBuildingStore';
import { useQueue } from '@/Composables/useQueue'
import type { Station, Spacecraft, Asteroid, Rebel } from '@/types/types';

type Role = 'Fighter' | 'Miner' | 'Transporter'

export type ModalContent = {
  type: 'asteroid' | 'station' | 'rebel' | 'undefined';
  data: Asteroid | Station | Rebel;
  title: string;
}

const props = defineProps<{
  open: boolean,
  content: ModalContent,
  spacecrafts: Spacecraft[] | undefined,
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

const rebelFactionImageMap: Record<string, string> = {
  'Standard': '/images/rebel_station_full.webp',
  'Rostwölfe':   '/images/stations/stationRed_full.webp',
  'Kult der Leere':  '/images/stations/stationViolet_full.webp',
  'Sternenplünderer': '/images/stations/stationBlue_full.webp',
  'Gravbrecher': '/images/stations/stationGreen_full.webp',
};

const asteroidImageIndex = computed(() =>
  asteroid.value?.id !== undefined
    ? asteroid.value.id % asteroidImages.length
    : 0
);

const asteroidImageSrc = computed(() =>
  asteroidImages[asteroidImageIndex.value]
);

const rebelImageSrc = computed(() => {
  // Fraktion auslesen, falls vorhanden
  const faction: string = (rebel.value && 'faction' in rebel.value) ? String(rebel.value.faction) : 'Standard';
  return rebelFactionImageMap[faction] || rebelFactionImageMap['Standard'];
});

const asteroid = computed<Asteroid>(() => props.content.data as Asteroid);
const station = computed<Station>(() => props.content.data as Station);
const rebel = computed<Rebel>(() => props.content.data as Rebel);
const userStation = usePage().props.stations.find(station =>
  station.user_id === usePage().props.auth.user.id
);
const { refreshQueue } = useQueueStore();
const { refreshSpacecrafts } = useSpacecraftStore();
const { buildings } = useBuildingStore();

const { processedQueueItems } = useQueue(usePage().props.auth.user.id)

const dockSlots = computed(() => {
  const hangar = buildings.value.find(b => b.effect?.current?.dock_slots);
  return hangar?.effect?.current?.dock_slots ?? 0;
});
const totalMiningOperations = computed(() => (processedQueueItems.value ?? []).reduce((acc, item) => {
  if (item.rawData.actionType === 'mining') {
    acc++;
  }
  return acc;
}, 0));

// Auswahl (Mengen pro Schiff)
const form = useForm({
  asteroid_id: null as number | null,
  station_user_id: null as number | null,
  rebel_id: null as number | null,
  spacecrafts: {} as Record<string, number>
});

const actionType = computed(() =>
  props.content.type === 'asteroid'
    ? QueueActionType.MINING
    : QueueActionType.COMBAT
);

const { miningDuration } = useAsteroidMining(
  asteroid,
  form.spacecrafts,
  actionType
);

const {
  setMaxAvailableUnits,
  setMinNeededUnits,
} = useSpacecraftUtils(
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
  let attack = 0, defense = 0, cargo = 0, speedWeighted = 0, unitCount = 0
  for (const s of props.spacecrafts) {
    const qty = form.spacecrafts[s.name] || 0
    attack += s.attack * qty
    defense += s.defense * qty
    cargo += s.cargo * qty
    speedWeighted += s.speed * qty
    unitCount += qty
  }
  const avgSpeed = unitCount ? Math.round(speedWeighted / unitCount) : 0
  return { attack, defense, cargo, avgSpeed }
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
  Object.keys(maxUnits).forEach(key => {
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

async function attackRebel() {
  if (isSubmitting.value) return;
  if (rebel.value) {
    form.rebel_id = rebel.value.id;
  }

  const noSpacecraftSelected = Object.values(form.spacecrafts).every((value) => value === 0);
  if (noSpacecraftSelected) return;

  isSubmitting.value = true;
  try {
    const { data } = await axios.post('/asteroidMap/combatRebel', form); // Angepasster Endpunkt für Rebellen
    // Zeige Erfolg/Fehler
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
  } else if (actionType.value === QueueActionType.COMBAT && props.content.type === 'rebel') {
    await attackRebel();
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
    if (props.content.data && props.content.type === 'asteroid' && props.spacecrafts) {
      (props.spacecrafts ?? []).forEach(s => (form.spacecrafts[s.name] = 0));
      setMinUnits();
    }
    document.body.style.overflow = '';
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

onMounted(() => {
  document.addEventListener('keydown', closeOnEscape);
});

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
  (spacecrafts.value ?? []).forEach(s => {
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
      <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2
               w-[1200px] max-w-[95vw] rounded-3xl border border-cyan-400/20
               shadow-2xl">
        <!-- Outer Glow -->
        <div class="rounded-3xl">
          <div class="rounded-3xl bg-[#0b1623]/95">

            <button type="button"
              class="absolute top-3 right-3 px-1 py-1 rounded-xl text-white font-medium border-transparent border-solid hover:border-solid outline-none transition hover:bg-cyan-900/30"
              @click="close">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                <path fill="currentColor"
                  d="m8.382 17.025l-1.407-1.4L10.593 12L6.975 8.4L8.382 7L12 10.615L15.593 7L17 8.4L13.382 12L17 15.625l-1.407 1.4L12 13.41z" />
              </svg>
            </button>

            <!-- Body -->
            <div class="grid grid-cols-[420px_1fr] min-h-[600px] gap-6 p-6">

              <!-- Left Asteroid -->
              <div class="relative text-center">
                <h1 class="text-2xl flex justify-center text-white mt-2">{{ content.title }}</h1>
                <p v-if="props.content.type === 'rebel'" class="text-light">{{ content.data.faction }}</p>

                <div class="relative flex items-center justify-center w-[360px] h-[360px] mx-auto">
                  <img v-if="props.content.type === 'asteroid'" :src="asteroidImageSrc" class="z-10" />
                  <img v-else-if="props.content.type === 'station'" src="/images/stations/station_full.webp" class="z-10" />
                  <img v-else :src="rebelImageSrc" class="z-10"  />
                  <AsteroidModalResourceSvg v-if="actionType === QueueActionType.MINING" :asteroid="asteroid"
                    :showResources="canScanAsteroid" class="absolute inset-0" />
                </div>

                <div v-if="actionType === QueueActionType.MINING && canScanAsteroid"
                  class="flex items-center justify-center gap-6 text-gray-300">
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
                      class="text-yellow-300 text-sm text-pretty">
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
                    <div class="flex relative group items-center gap-2">
                      <img src="/images/combat.png" class="h-5" alt="combat" />
                      <p class="font-medium text-sm text-slate-400">{{ numberFormat(totals.attack) }}</p>
                      <AppTooltip :label="'combat'" position="bottom" class="!mt-1" />
                    </div>
                    <div class="flex relative group items-center gap-2">
                      <img src="/images/defense.png" class="h-5" alt="defense" />
                      <p class="font-medium text-sm text-slate-400">{{ numberFormat(totals.defense) }}</p>
                      <AppTooltip :label="'defense'" position="bottom" class="!mt-1" />
                    </div>
                    <div class="flex relative group items-center gap-2">
                      <img src="/images/cargo.png" class="h-5" alt="cargo" />
                      <p class="font-medium text-sm text-slate-400">{{ numberFormat(totals.cargo) }}</p>
                      <AppTooltip :label="'cargo'" position="bottom" class="!mt-1" />
                    </div>
                    <div class="flex relative group items-center gap-2">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 text-light" width="20" height="20" viewBox="0 0 24 24">
                        <g fill="none" fill-rule="evenodd">
                          <path d="m12.594 23.258l-.012.002l-.071.035l-.02.004l-.014-.004l-.071-.036q-.016-.004-.024.006l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.016-.018m.264-.113l-.014.002l-.184.093l-.01.01l-.003.011l.018.43l.005.012l.008.008l.201.092q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.003-.011l.018-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M10.975 3.002a1 1 0 0 1-.754 1.196a8 8 0 0 0-.583.156a1 1 0 0 1-.59-1.911q.36-.112.73-.195a1 1 0 0 1 1.197.754m2.05 0a1 1 0 0 1 1.196-.754c4.454 1.01 7.78 4.992 7.78 9.752c0 5.523-4.478 10-10 10c-4.761 0-8.743-3.325-9.753-7.779a1 1 0 0 1 1.95-.442a8 8 0 1 0 9.58-9.58a1 1 0 0 1-.753-1.197M6.614 4.72a1 1 0 0 1-.053 1.414q-.222.205-.427.426A1 1 0 0 1 4.668 5.2q.255-.276.532-.533a1 1 0 0 1 1.414.053M12 6a1 1 0 0 1 1 1v4.586l2.707 2.707a1 1 0 0 1-1.414 1.414l-3-3A1 1 0 0 1 11 12V7a1 1 0 0 1 1-1M3.693 8.388a1 1 0 0 1 .661 1.25a8 8 0 0 0-.156.583a1 1 0 0 1-1.95-.442q.084-.37.195-.73a1 1 0 0 1 1.25-.661"/></g>
                      </svg>
                      <p class="font-medium text-sm text-slate-400">{{ miningDuration }}</p>
                      <AppTooltip :label="'mining duration'" position="bottom" class="!mt-1" />
                    </div>
                  </div>

                  <div class="flex gap-2">
                    <div class="flex items-center gap-2">
                    <!-- img -->
                     <img src="/images/asteroid.png" alt="asteroid" class="h-5" />
                      <span class="text-slate-400 text-sm font-medium">
                      {{ totalMiningOperations }} / {{ dockSlots }}
                      </span>
                    </div>
                    <button @click="startMission" @shift.click="fastExploreAsteroid"
                      :disabled="isSubmitting || (Number(dockSlots) > 0 && totalMiningOperations >= Number(dockSlots))"
                      class="px-4 py-2 bg-cyan-700 text-light rounded-xl font-semibold transition border border-cyan-700/30 hover:bg-cyan-600 hover:text-cyan-100 text-base shadow disabled:cursor-not-allowed disabled:opacity-40">
                      <span v-if="actionType === QueueActionType.MINING">Start Mining</span>
                      <span v-else>Start Attack</span>
                    </button>
                  </div>
                </div>

                <!-- Tabs -->
                <div class="relative flex justify-between pb-4 border-b border-white/10 mb-4">
                  <div class="flex gap-1">
                    <button v-for="t in tabs" :key="t" class="relative px-5 py-2 font-semibold text-sm transition rounded-t-xl flex items-center gap-2 focus:outline-none hover:bg-cyan-400/10 text-light" 
                      :class="{'bg-cyan-500/10 shadow text-cyan-200': t === activeTab, '': t !== activeTab }" 
                      @click="activeTab = t">
                      <img :src="`/images/spacecraftTypes/${t}.png`" alt="" width="20" height="20" />
                      {{ t }}
                      <span v-if="t === activeTab"
                        class="absolute left-0 -bottom-[2px] w-full h-1 bg-cyan-400 rounded-b-xl transition-all"></span>
                    </button>
                  </div>

                  <div class="flex gap-2 z-10">
                    <button
                      class="flex items-center px-3 py-1 gap-1 rounded-xl bg-slate-900/10 text-white border border-cyan-700/30 hover:bg-cyan-900/30 font-semibold text-base shadow"
                      @click="setMaxUnits" title="choose all available units for this category">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M12 4v16" />
                      </svg>
                      Max
                    </button>
                    <button
                      class="flex items-center px-3 py-1 gap-1 rounded-xl bg-slate-900/10 text-white border border-cyan-700/30 hover:bg-cyan-900/30 font-semibold text-base shadow"
                      @click="setMinUnits" title="Set the minimum required units for this category">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16" />
                      </svg>
                      Min
                    </button>
                    <button
                      class="flex items-center px-3 py-1 gap-1 rounded-xl bg-slate-900/10 text-white border border-cyan-700/30 hover:bg-cyan-900/30 font-semibold text-base shadow"
                      @click="resetSpacecraftsForm" title="Set all units to 0">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      Reset
                    </button>
                  </div>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-2 xl:grid-cols-3 gap-4 max-h-[520px] overflow-y-auto overflow-x-hidden pr-2 fancy-scroll">
                  <div v-for="s in filtered" :key="s.id"
                    class="rounded-xl border border-white/5 bg-slate-900/5 shadow-lg">
                    <div class="flex justify-between items-center px-3 py-1 border-b-slate-950 border-b">
                      <h3 class="text-light font-semibold">{{ s.name }}</h3>
                      <span class="text-light cursor-pointer" @click="setMax(s.name, availableCount(s))"
                        :title="`${availableCount(s)} verfügbar von ${s.count}`">
                        {{ availableCount(s) }}
                      </span>
                    </div>
                    <div class="spacecraftImage relative bg-[#101d2c] flex items-center justify-center">
                      <img :src="s.image" class="h-[90px] w-full" />
                    </div>
                    <div class="flex justify-between px-2 pt-3">
                      <div class="flex relative group items-center gap-1">
                        <img src="/images/combat.png" class="h-5" alt="combat" />
                        <p class="font-medium text-sm text-slate-400">{{ numberFormat(s.attack) }}</p>
                        <AppTooltip :label="'combat'" position="bottom" class="!mt-1" />
                      </div>
                      <div class="flex relative group items-center gap-1">
                        <img src="/images/defense.png" class="h-5" alt="defense" />
                        <p class="font-medium text-sm text-slate-400">{{ numberFormat(s.defense) }}</p>
                        <AppTooltip :label="'defense'" position="bottom" class="!mt-1" />
                      </div>
                      <div class="flex relative group items-center gap-1">
                        <img src="/images/cargo.png" class="h-5" alt="cargo" />
                        <p class="font-medium text-sm text-slate-400">{{ numberFormat(s.cargo) }}</p>
                        <AppTooltip :label="'cargo capacity'" position="bottom" class="!mt-1" />
                      </div>
                    </div>

                    <div class="mt-4">
                      <div class="flex items-center justify-between">
                        <button
                          class="px-2 py-2 rounded-bl-xl bg-slate-900/10 text-light hover:bg-slate-950 transition font-semibold border-r border-slate-950 focus:outline-none disabled:hover:bg-slate-900/10 disabled:opacity-40 disabled:cursor-not-allowed"
                          @click="dec(s.name)"
                          @click.shift="form.spacecrafts[s.name] = Math.max(0, (form.spacecrafts[s.name] || 0) - 10)"
                          :disabled="availableCount(s) === 0" type="button">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M18 12.998H6a1 1 0 0 1 0-2h12a1 1 0 0 1 0 2" />
                          </svg>
                        </button>
                        <AppInput v-model="form.spacecrafts[s.name]" :maxInputValue="availableCount(s)" :maxlength="5"
                          class="!py-2 !px-0 !w-full !rounded-none !border-0 !bg-slate-900/10 text-center focus:!ring-0 focus:!border-x-2 transition-colors" />
                        <button
                          class="px-2 py-2 rounded-br-xl bg-slate-900/10 text-light hover:bg-slate-950 transition font-semibold border-l border-slate-950 focus:outline-none disabled:hover:bg-slate-900/10 disabled:opacity-40 disabled:cursor-not-allowed"
                          @click="inc(s.name, availableCount(s))"
                          @click.shift="form.spacecrafts[s.name] = Math.min((form.spacecrafts[s.name] || 0) + 10, availableCount(s))"
                          :disabled="availableCount(s) === 0" type="button">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="currentColor"
                              d="M18 12.998h-5v5a1 1 0 0 1-2 0v-5H6a1 1 0 0 1 0-2h5v-5a1 1 0 0 1 2 0v5h5a1 1 0 0 1 0 2" />
                          </svg>
                        </button>
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
.asteroid-spin {
  animation: asteroid 40s linear infinite;
}

@keyframes asteroid {
  to {
    transform: rotate(360deg);
  }
}

/* Scrollbar */
.fancy-scroll::-webkit-scrollbar {
  width: 6px;
}

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
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-enter-to,
.fade-leave-from {
  opacity: 1;
}
</style>

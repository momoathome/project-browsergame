<script lang="ts" setup>
import { onMounted, onUnmounted, ref, watch, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { numberFormat } from '@/Utils/format';
import AsteroidModalResourceSvg from './AsteroidModalResourceSvg.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import MapModalUnits from './MapModalUnits.vue';
import type { Station, SpacecraftSimple, Asteroid } from '@/types/types';
import { useAsteroidMining } from '@/Composables/useAsteroidMining';
import { useSpacecraftUtils } from '@/Composables/useSpacecraftUtils';

export type ModalContent = {
  type: 'asteroid' | 'station' | 'undefined';
  data: Asteroid | Station;
  imageSrc: string;
}

const props = defineProps<{
  show: boolean,
  title?: string,
  content: ModalContent,
  spacecrafts: SpacecraftSimple[],
  userScanRange: number,
}>();

const emit = defineEmits(['close']);

const dialog = ref();
const userStation = usePage().props.stations.find(station =>
  station.user_id === usePage().props.auth.user.id
);

// Computed properties für Asteroid und Station
const asteroid = computed<Asteroid>(() => props.content.data as Asteroid);
const station = computed<Station>(() => props.content.data as Station);

// Formular Initialisierung
const form = useForm({
  asteroid_id: null as number | null,
  station_user_id: null as number | null,
  spacecrafts: {
    Merlin: 0,
    Comet: 0, 
    Javelin: 0,
    Sentinel: 0, 
    Probe: 0,
    Ares: 0, 
    Nova: 0, 
    Horus: 0, 
    Reaper: 0, 
    Mole: 0,
    Titan: 0, 
    Nomad: 0, 
    Hercules: 0,
  }
});

const {
  setMaxAvailableUnits,
  setMinNeededUnits, 
  calculateTotalCombatPower,
  calculateTotalCargoCapacity
} = useSpacecraftUtils(
  computed(() => props.spacecrafts),
  form.spacecrafts,
  computed(() => props.content)
);

const { miningDuration } = useAsteroidMining(asteroid, form.spacecrafts, props.spacecrafts);
const totalCombatPower = computed(() => calculateTotalCombatPower());
const totalCargoCapacity = computed(() => calculateTotalCargoCapacity());
const formattedDuration = miningDuration;

const calculateCargoPercentage = computed(() => {
  if (!asteroid.value || !asteroid.value.resources) return 0;
  const totalResources = asteroid.value.resources.reduce((total, resource) => total + resource.amount, 0);
  return Math.round((totalCargoCapacity.value / totalResources) * 100);
});

// Berechnung der Distanz zum Asteroid
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

// Aktionsfunktionen
function exploreAsteroid() {
  if (asteroid.value) {
    form.asteroid_id = asteroid.value.id;
  }

  // Wenn keine Raumschiffe ausgewählt sind, abbrechen
  const noSpacecraftSelected = Object.values(form.spacecrafts).every((value) => value === 0);
  if (noSpacecraftSelected) return;

  form.post(route('asteroidMap.update'), {
    onSuccess: () => close()
  });
}

function fastExploreAsteroid() {
  const minUnits = setMinNeededUnits();
  Object.keys(form.spacecrafts).forEach(key => {
    form.spacecrafts[key] = minUnits[key] || 0;
  });
  exploreAsteroid();
}

function attackUser() {
  if (station.value) {
    form.station_user_id = station.value.user_id;
  }

  // Wenn keine Raumschiffe ausgewählt sind, abbrechen
  const noSpacecraftSelected = Object.values(form.spacecrafts).every((value) => value === 0);
  if (noSpacecraftSelected) return;

  form.post(route('asteroidMap.combat'), {
    onSuccess: () => close()
  });
}

// Modal schließen
const close = () => {
  // Reset jedes Feldes einzeln statt form.reset() zu verwenden
  Object.keys(form.spacecrafts).forEach(key => {
    form.spacecrafts[key] = 0;
  });
  form.asteroid_id = null;
  form.station_user_id = null;

  emit('close');
};

// UI Aktionen
function setMaxUnits() {
  const maxUnits = setMaxAvailableUnits();
  Object.keys(maxUnits).forEach(key => {
    form.spacecrafts[key] = maxUnits[key];
  });
}

function setMinUnits() {
  const minUnits = setMinNeededUnits();
  Object.keys(form.spacecrafts).forEach(key => {
    form.spacecrafts[key] = minUnits[key] || 0;
  });
}

// Modal Anzeige/Verbergen Logik
watch(() => props.show, () => {
  if (props.show) {
    document.body.style.overflow = 'hidden';
    dialog.value?.showModal();
  } else {
    document.body.style.overflow = 'visible';
    setTimeout(() => {
      dialog.value?.close();
    }, 200);
  }
});

// Escape-Taste zum Schließen
const closeOnEscape = (e) => {
  if (e.key === 'Escape') {
    e.preventDefault();
    if (props.show) close();
  }
};

// Lifecycle Hooks
onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape);
  document.body.style.overflow = 'visible';
});
</script>

<template>
  <dialog class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent" ref="dialog">
    <div class="fixed inset-0 overflow-y-auto px-12 2xl:px-24 py-16 z-50" scroll-region>
      <transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
          <div class="absolute inset-0 bg-slate-900 opacity-75" />
        </div>
      </transition>

      <transition enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-80">
        <div v-show="show" class="flex h-full items-center justify-around gap-24">

          <!-- Asteroid Image with resource svg -->
          <div v-if="content?.type === 'asteroid'" class="flex flex-col justify-center relative">
            <div class="flex flex-col items-center">
              <h1 class="text-2xl flex justify-center mb-20 text-white relative z-10">{{ title }}</h1>
              <div class="relative">
                <img :src="content.imageSrc" alt="Asteroid" width="256px" class="" />
                <AsteroidModalResourceSvg :asteroid="content.data" :showResources="canScanAsteroid" />
              </div>
              <div class="text-gray-300">
              </div>
              <div v-if="canScanAsteroid" class="text-gray-300 flex items-center justify-center mt-8">
                <div class="flex gap-6">
                  <span v-for="{ resource_type, amount } in asteroid.resources" :key="resource_type" class="flex gap-2">
                    <img :src="`/images/resources/${resource_type}.png`" class="h-6" alt="" />
                    {{ amount }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Station Image -->
          <div v-if="content?.type === 'station'" class="flex flex-col justify-center relative">
            <div class="flex flex-col items-center gap-12">

              <h1 class="text-2xl flex justify-center text-white relative z-10">{{ title }}</h1>

              <img :src="content.imageSrc" alt="Station" width="256px" />

              <p class="text-gray-300">This is the space station of {{ title }}. No further details available.</p>
            </div>
          </div>

          <!-- Units, Unit Information and action Buttons  -->
          <div class="px-12 py-12 flex flex-col gap-4 bg-gray-800 rounded-3xl text-white relative">
            <button class="absolute top-3 right-3 p-2" @click="close">X</button>
            <div class="bg-base rounded-lg px-4 pt-4 pb-2 flex flex-col gap-2">
              <div class="flex justify-between gap-2">
                <div class="flex gap-4 items-center">
                  <p class="text-secondary">Combat: <span class="text-white">{{ numberFormat(totalCombatPower) }}</span>
                  </p>
                  <p class="text-secondary">Cargo: <span class="text-white">
                      {{ numberFormat(totalCargoCapacity) }} 
                      <span v-if="canScanAsteroid">({{ calculateCargoPercentage }}%)</span>
                    </span>
                  </p>
                  <p class="text-secondary">Travel Time: <span class="text-white">{{ formattedDuration }}</span></p>
                </div>
                <div class="flex gap-2">
                  <div class="relative group z-10" v-if="content?.type === 'asteroid' && canScanAsteroid">
                    <SecondaryButton @click="setMinUnits">Min</SecondaryButton>
                    <AppTooltip label="set minimum needed Miners and Cargo" position="bottom"
                      class="!mt-2 text-pretty w-40" />
                  </div>
                  <div class="relative group z-10" v-if="content?.type === 'station'">
                    <SecondaryButton @click="setMaxUnits">Max</SecondaryButton>
                    <AppTooltip label="set all available Fighters" position="bottom" class="!mt-3" />
                  </div>
                  <div class="relative group z-10" v-if="content?.type === 'asteroid'">
                    <PrimaryButton v-if="content?.type === 'asteroid'" @click="exploreAsteroid"
                      @click.shift="fastExploreAsteroid">Explore</PrimaryButton>
                    <!-- <AppTooltip label="shift + left click for quick send" position="bottom" class="!mt-3" /> -->
                  </div>

                  <PrimaryButton v-else @click="attackUser">Attack</PrimaryButton>
                </div>
              </div>
              <!--               <div class="flex flex-col text-sm">
                <span>
                  i: a miner should be selected
                </span>
                <span>
                  i: only large miners can mine extremely large asteroids
                </span>
              </div> -->
            </div>
            <MapModalUnits :spacecrafts="spacecrafts" v-model="form.spacecrafts" />
          </div>
        </div>
      </transition>
    </div>
  </dialog>
</template>

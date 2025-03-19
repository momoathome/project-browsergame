<script lang="ts" setup>
import { onMounted, onUnmounted, ref, watch, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { numberFormat } from '@/Utils/format';
import AsteroidModalResourceSvg from './AsteroidModalResourceSvg.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AppTooltip from '@/Components/AppTooltip.vue';
import MapModalUnits from './MapModalUnits.vue';
import type { Station, Spacecraft, Asteroid } from '@/types/types';
import { timeFormat } from '@/Utils/format';

interface Content {
  data: Asteroid | Station;
  imageSrc: string;
  type: 'asteroid' | 'station' | undefined | null;
}

const emit = defineEmits(['close']);

const props = defineProps<{
  show: boolean,
  title: string | undefined,
  content: Content | undefined,
  spacecrafts: Spacecraft[],
}>();

const asteroid = computed<Asteroid>(() => props.content?.data);
const station = computed<Station>(() => props.content?.data);
const formattedDuration = computed(() => calculateMiningDuration());

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

function exploreAsteroid() {
  if (asteroid.value) {
    form.asteroid_id = asteroid.value.id;
  }

  // if form spacecrafts are all 0, return
  const noSpacecraftSelected = Object.values(form.spacecrafts).every((value) => value === 0);
  if (noSpacecraftSelected) {
    return;
  }

  form.post(route('asteroidMap.update'), {
    onSuccess: () => {
      close();
    },
  });
}

function fastExploreAsteroid() {
  setMinNeededUnits();
  exploreAsteroid();
}

function attackUser() {
  if (station.value) {
    form.station_user_id = station.value.user_id;
  }

  // if form spacecrafts are all 0, return
  const noSpacecraftSelected = Object.values(form.spacecrafts).every((value) => value === 0);
  if (noSpacecraftSelected) {
    return;
  }

  form.post(route('asteroidMap.combat'), {
    onSuccess: () => {
      close();
    },
  });
}

const close = () => {
  form.reset();
  emit('close');
};

const dialog = ref();

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

const closeOnEscape = (e) => {
  if (e.key === 'Escape') {
    e.preventDefault();

    if (props.show) {
      close();
    }
  }
};

const totalCombatPower = computed(() => {
  let total = 0;

  for (const spacecraft in form.spacecrafts) {
    const combat = props.spacecrafts.find((s: Spacecraft) => s.details.name === spacecraft)?.combat;
    if (combat !== undefined) {
      total += combat * form.spacecrafts[spacecraft];
    }
  }

  return total;
});

const totalCargoCapacity = computed(() => {
  let total = 0;

  for (const spacecraft in form.spacecrafts) {
    const cargo = props.spacecrafts.find((s: Spacecraft) => s.details.name === spacecraft)?.cargo;
    if (cargo !== undefined) {
      total += cargo * form.spacecrafts[spacecraft];
    }
  }

  return total;
});

const calculateCargoPercentage = computed(() => {
  if (!asteroid.value) return 0;

  const totalResources = asteroid.value.resources.reduce((total, resource) => total + resource.amount, 0);
  
  return Math.round((totalCargoCapacity.value / totalResources) * 100);
});

const calculateMiningDuration = () => {
  if (!asteroid.value) return '00:00';
  
  const anySpacecraftSelected = Object.values(form.spacecrafts).some(value => value > 0);
  if (!anySpacecraftSelected) return '00:00';
  
  let lowestSpeed = 0;
  
  for (const spacecraftName in form.spacecrafts) {
    const count = form.spacecrafts[spacecraftName];
    if (count > 0) {
      const spacecraft = props.spacecrafts.find(s => s.details.name === spacecraftName);
      if (spacecraft && spacecraft.speed > 0 && (lowestSpeed === 0 || spacecraft.speed < lowestSpeed)) {
        lowestSpeed = spacecraft.speed;
      }
    }
  }
  
  const userStation = usePage().props.stations.find(station => 
    station.user_id === usePage().props.auth.user.id
  );
  
  // Distanz berechnen
  const distance = Math.sqrt(
    Math.pow(userStation.x - asteroid.value.x, 2) + 
    Math.pow(userStation.y - asteroid.value.y, 2)
  );
  
  const baseDuration = Math.max(10, Math.round(distance / (lowestSpeed > 0 ? lowestSpeed : 1)));
  const travelFactor = 1;
  const calculatedDuration = Math.floor(Math.max(
    baseDuration, 
    distance / (lowestSpeed > 0 ? lowestSpeed : 1) * travelFactor
  ));
  
  return timeFormat(calculatedDuration);
};

const setMaxAvailableUnits = () => {
  const MaxAvailableUnits = {};

  props.spacecrafts.forEach((spacecraft: Spacecraft) => {
    if (spacecraft.details.type !== "Miner") {
      MaxAvailableUnits[spacecraft.details.name] = spacecraft.count;
    }
  });

  form.spacecrafts = MaxAvailableUnits;
}

const setMinNeededUnits = () => {
  const MinNeededUnits = {};
  const totalAsteroidResources = props.content!.data.resources.reduce((total, resource) => total + resource.amount, 0);
  let remainingResources = totalAsteroidResources;

  // Funktion zum Verarbeiten von Raumschiffen eines bestimmten Typs
  const processSpacecraftType = (type: string) => {
    props.spacecrafts.forEach((spacecraft: Spacecraft) => {
      if (remainingResources <= 0) {
        MinNeededUnits[spacecraft.details.name] = MinNeededUnits[spacecraft.details.name] || 0;
        return;
      }

      if (spacecraft.details.type === type) {
        const neededUnits = Math.ceil(remainingResources / spacecraft.cargo);
        const usedUnits = Math.min(neededUnits, spacecraft.count);

        MinNeededUnits[spacecraft.details.name] = usedUnits;
        remainingResources -= usedUnits * spacecraft.cargo;
      }
    });
  };

  // Verarbeite zuerst Miner und transporter
  processSpacecraftType("Miner");
  processSpacecraftType("Transporter");

  // Schließlich alle anderen Raumschifftypen
  props.spacecrafts.forEach((spacecraft: Spacecraft) => {
    if (remainingResources <= 0) {
      MinNeededUnits[spacecraft.details.name] = MinNeededUnits[spacecraft.details.name] || 0;
      return;
    }

    if (spacecraft.details.type !== "Miner" && spacecraft.details.type !== "Transporter") {
      const neededUnits = Math.ceil(remainingResources / spacecraft.cargo);
      const usedUnits = Math.min(neededUnits, spacecraft.count);

      MinNeededUnits[spacecraft.details.name] = usedUnits;
      remainingResources -= usedUnits * spacecraft.cargo;
    }
  });

  form.spacecrafts = MinNeededUnits;
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape);
  document.body.style.overflow = 'visible';
});

const userScanRange = computed(() => {
  const scanRangeAttribute = usePage().props.userAttributes.find(
    (attr) => attr.attribute_name === 'scan_range'
  );
  return scanRangeAttribute ? scanRangeAttribute.attribute_value : 5000;
});
const userStation = usePage().props.stations.find(station => station.user_id === usePage().props.auth.user.id);

// calculate the distance between the userstation x,y and the asteroid x,y if asteroid is not undefined
const distance = computed(() => {
  if (asteroid.value) {
    const userX = userStation.x;
    const userY = userStation.y;
    const asteroidX = asteroid.value.x;
    const asteroidY = asteroid.value.y;
    return Math.round(Math.sqrt(Math.pow(userX - asteroidX, 2) + Math.pow(userY - asteroidY, 2)));
  }
});

const canScanAsteroid = computed(() => {
  if (asteroid.value && distance.value) {
    return distance.value <= userScanRange.value;
  }
});
const canAttackUser = computed(() => userStation && distance <= userScanRange.value);
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
                    <img :src="`/storage/resources/${resource_type}.png`" class="h-6" alt="" />
                    {{ amount }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div v-if="content?.type === 'station'" class="flex flex-col justify-center relative">
            <div class="flex flex-col items-center gap-12">

              <h1 class="text-2xl flex justify-center text-white relative z-10">{{ title }}</h1>

              <img :src="content.imageSrc" alt="Station" width="256px" />

              <p class="text-gray-300">This is the space station of {{ title }}. No further details available.</p>
            </div>
          </div>

          <div class="px-12 py-12 flex flex-col gap-4 bg-gray-800 rounded-3xl text-white relative">
            <button class="absolute top-3 right-3 p-2" @click="close">X</button>
            <div class="bg-base rounded-lg px-4 pt-4 pb-2 flex flex-col gap-2">
              <div class="flex justify-between gap-2">
                <div class="flex gap-4 items-center">
                  <p class="text-secondary">Combat: <span class="text-white">{{ numberFormat(totalCombatPower) }}</span>
                  </p>
                  <p class="text-secondary">Cargo: <span class="text-white">
                    {{ numberFormat(totalCargoCapacity) }} ({{ calculateCargoPercentage }}%)
                  </span>
                  </p>
                  <p class="text-secondary">Travel Time: <span class="text-white">{{ formattedDuration }}</span></p>
                </div>
                <div class="flex gap-2">
                  <div class="relative group z-10" v-if="content?.type === 'asteroid' && canScanAsteroid">
                    <SecondaryButton @click="setMinNeededUnits">Min</SecondaryButton>
                    <AppTooltip label="set the minimum needed Spacecrafts to mine all resources" position="bottom"
                      class="!mt-2 text-pretty w-40" />
                  </div>
                  <div class="relative group z-10" v-if="content?.type === 'station'">
                    <SecondaryButton @click="setMaxAvailableUnits">Max</SecondaryButton>
                    <AppTooltip label="set all available Spacecrafts" position="bottom" class="!mt-3" />
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

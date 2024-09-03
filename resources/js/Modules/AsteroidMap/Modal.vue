<script lang="ts" setup>
import { onMounted, onUnmounted, ref, watch, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { numberFormat } from '@/Utils/format';
import AsteroidModalResourceSvg from './AsteroidModalResourceSvg.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import MapModalUnits from './MapModalUnits.vue';
import type { Station, Spacecraft, Asteroid } from '@/types/types';

interface Content {
  data: Asteroid;
  imageSrc: string;
  type: 'asteroid' | 'station' | undefined;
}

const emit = defineEmits(['close']);

const props = defineProps<{
  show: boolean,
  title: string | undefined,
  content: Content | undefined,
  spacecrafts: Spacecraft[],
}>();

const asteroid = computed<Asteroid>(() => props.content!.data);

const form = useForm({
  asteroid_id: props.content?.data?.id ?? null,
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
  form.asteroid_id = asteroid.value.id;

  // if form spacecrafts are all 0, return
  const noSpacecraftSelected = Object.values(form.spacecrafts).every((value) => value === 0);
  if (noSpacecraftSelected) {
    return;
  }

  form.post(`/asteroidMap/update`, {
    onSuccess: () => {
      close();
    },
  });
}

function attackUser() {
  // implement attack logic here
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
    document.body.style.overflow = null;
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
    total += props.spacecrafts.find((s: Spacecraft) => s.details.name === spacecraft)?.combat * form.spacecrafts[spacecraft];
  }

  return total;
});

const totalCargoCapacity = computed(() => {
  let total = 0;

  for (const spacecraft in form.spacecrafts) {
    total += props.spacecrafts.find((s: Spacecraft) => s.details.name === spacecraft)?.cargo * form.spacecrafts[spacecraft];
  }

  return total;
});

const setMaxAvailableUnits = () => {
  const MaxAvailableUnits = {};

  props.spacecrafts.forEach((spacecraft: Spacecraft) => {
    MaxAvailableUnits[spacecraft.details.name] = spacecraft.count;
  });

  form.spacecrafts = MaxAvailableUnits;
}

const setMinNeededUnits = () => {
  const MinNeededUnits = {};
  const totalAsteroidResources = props.content!.data.resources.reduce((total, resource) => total + resource.amount, 0);

  let remainingResources = totalAsteroidResources;

  // Zuerst alle "mining"-Raumschiffe auswählen
  props.spacecrafts.forEach((spacecraft: Spacecraft) => {
    const spacecraftName = spacecraft.details.name;
    const spacecraftCargo = spacecraft.cargo;
    const spacecraftType = spacecraft.details.type;

    if (remainingResources <= 0) {
      MinNeededUnits[spacecraftName] = 0;
      return;
    }

    if (spacecraftType === "Miner") {
      const neededUnits = Math.ceil(remainingResources / spacecraftCargo);
      const usedUnits = Math.min(neededUnits, spacecraft.count);

      MinNeededUnits[spacecraftName] = usedUnits;
      remainingResources -= usedUnits * spacecraftCargo;
    }
  });

  // Falls noch Ressourcen übrig sind, andere Raumschiffe verwenden
  props.spacecrafts.forEach((spacecraft: Spacecraft) => {
    const spacecraftName = spacecraft.details.name;
    const spacecraftCargo = spacecraft.cargo;
    const spacecraftType = spacecraft.details.type;

    if (remainingResources <= 0) {
      if (!(spacecraftName in MinNeededUnits)) {
        MinNeededUnits[spacecraftName] = 0;
      }
      return;
    }

    if (spacecraftType !== "Miner") {
      const neededUnits = Math.ceil(remainingResources / spacecraftCargo);
      const usedUnits = Math.min(neededUnits, spacecraft.count);

      MinNeededUnits[spacecraftName] = usedUnits;
      remainingResources -= usedUnits * spacecraftCargo;
    }
  });

  form.spacecrafts = MinNeededUnits;
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape);
  document.body.style.overflow = null;
});
</script>

<template>
  <dialog class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent" ref="dialog">
    <div class="fixed inset-0 overflow-y-auto px-24 py-16 z-50" scroll-region>
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
        <div v-show="show" class="flex h-full items-center justify-between gap-24">

          <div v-if="content?.type === 'asteroid'" class="flex flex-col justify-center relative">
            <div class="flex flex-col items-center">
              <h1 class="text-2xl flex justify-center mb-20 text-white relative z-10">{{ title }}</h1>
              <div class="relative">
                <img :src="content.imageSrc" alt="Asteroid" width="256px" class="" />
                <AsteroidModalResourceSvg :asteroid="content.data" />
              </div>
              <div class="text-gray-300 flex items-center justify-center mt-8">
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
            <div class="bg-base rounded-lg px-4 pt-4 pb-2 flex justify-between gap-2">
              <div class="flex gap-4 items-center">
                <p class="text-secondary">Combat: <span class="text-white">{{ numberFormat(totalCombatPower) }}</span>
                </p>
                <p class="text-secondary">Cargo: <span class="text-white">{{ numberFormat(totalCargoCapacity) }}</span>
                </p>
                <p class="text-secondary">Tavel Time: <span class="text-white">00:00</span></p>
              </div>
              <div class="flex gap-2">
                <SecondaryButton v-if="content?.type === 'asteroid'" @click="setMinNeededUnits">Min</SecondaryButton>
                <SecondaryButton @click="setMaxAvailableUnits">Max</SecondaryButton>
                <PrimaryButton v-if="content?.type === 'asteroid'" @click="exploreAsteroid">Explore</PrimaryButton>
                <PrimaryButton v-else @click="attackUser">Attack</PrimaryButton>
              </div>
            </div>

            <MapModalUnits :spacecrafts="spacecrafts" v-model="form.spacecrafts" />
          </div>
        </div>
      </transition>
    </div>
  </dialog>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import AsteroidModalResourceSvg from './AsteroidModalResourceSvg.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import MapModalUnits from './MapModalUnits.vue';

const emit = defineEmits(['close']);

const props =defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  closeable: {
    type: Boolean,
    default: true,
  },
  title: {
    type: String,
    default: '',
  },
  content: {
    type: Object,
    default: '',
  },
  spacecrafts: {
    type: Array,
    default: '',
  },
});

const close = () => {
  emit('close');
  form.reset();
};

const showDetails = ref(false);

const toggleDetails = () => {
  showDetails.value = !showDetails.value;
}

const modalUnits = ref(null)

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

function updateSpacecraftsInForm(spacecrafts) {
  form.spacecrafts = spacecrafts;
}

function exploreAsteroid() {
  console.log('success');
  modalUnits.value.onSubmit();
  form.asteroid_id = props.content.data.id;
/*
  form.post(`/asteroidMap/update`, {
    onSuccess: () => {
      console.log('success');
    },
  }); */
}
</script>

<template>
  <Modal :show="show" :closeable="closeable" @close="close">
    <div class="px-8 pb-8 pt-8 h-full flex flex-col bg-gray-800 text-white">

      <button class="absolute top-4 right-4 text-white" @click="close">X</button>
      <h3 class="text-2xl flex justify-center mb-16">{{ title }}</h3>

      <!-- if station is selected -->
      <div v-if="content.type === 'station'">
        <img :src="content.imageSrc" alt="Station" class="w-32 h-32 mx-auto" />

        <p class="text-gray-700 dark:text-gray-300">This is a space station. No further details available.
        </p>
      </div>

      <!-- if asteroid is selected -->
      <div v-if="content.type === 'asteroid'" class="flex flex-col relative gap-12">
        <div class="relative w-32 h-32 mx-auto">
          <AsteroidModalResourceSvg :asteroid="content.data" />
          <img :src="content.imageSrc" alt="Asteroid" class="w-full h-full absolute inset-0" />
        </div>

        <MapModalUnits :spacecrafts="spacecrafts" @emitForm="updateSpacecraftsInForm" ref="modalUnits" />

        <div class="flex justify-center gap-6">
          <SecondaryButton @click="toggleDetails">
            {{ showDetails ? 'Hide resources' : 'Show resources' }}
          </SecondaryButton>
          <PrimaryButton @click="exploreAsteroid">Explore</PrimaryButton>
        </div>

        <div v-if="showDetails" class="text-gray-300 absolute top-0 left-6">
          <!-- if user is admin -->
          <!--                     <p><strong>Rarity:</strong> {{ content.data.rarity }}</p>
                    <p><strong>Base Value:</strong> {{ content.data.base }}</p>
                    <p><strong>Multiplier:</strong> {{ content.data.multiplier }}</p>
                    <p><strong>Value:</strong> {{ content.data.value }}</p>
                    <p><strong>Pool:</strong> {{ content.data.resource_pool }}</p> -->
          <!-- else -->
          <div class="flex flex-col gap-2">
            <p><strong>Resources:</strong></p>
            <span v-for="(value, key) in content.data.resources" :key="key" class="flex gap-4">
              <img :src="`/storage/resources/${key}.png`" class="h-6" />
              {{ value }}
            </span>
          </div>
        </div>
      </div>

      <pre class="text-white">
        {{ form }}
      </pre>
    </div>

  </Modal>
</template>

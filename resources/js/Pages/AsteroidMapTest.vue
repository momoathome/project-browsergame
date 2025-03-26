<script lang="ts" setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AsteroidMapCanvas from '@/Modules/AsteroidMap/AsteroidMapCanvas.vue';
import AsteroidMapSearch from '@/Modules/AsteroidMap/AsteroidMapSearch.vue';
import AsteroidMapDropdown from '@/Modules/AsteroidMap/AsteroidMapDropdown.vue';
// import AsteroidMapModal from '@/Modules/AsteroidMap/AsteroidMapModal.vue';
import useAsteroidSearch from '@/Composables/useAsteroidSearch';
import type { Asteroid, Station, Spacecraft } from '@/types/types';

const props = defineProps<{
  asteroids: Asteroid[];
  stations: Station[];
  spacecrafts: Spacecraft[];
  searched_asteroids: Asteroid[];
  searched_stations: Station[];
  selected_asteroid: Asteroid | null;
}>();

const onMouseClick = () => {
  // do something
}

const {
  searchForm,
  performSearch,
  clearSearch,
} = useAsteroidSearch();

const selectedObject = ref<{ type: 'station' | 'asteroid' | null; data: Asteroid | Station | undefined | null } | null>(null);

const selectedAsteroid = ref<Asteroid>();
function selectAsteroid(asteroid: Asteroid) {
  // focusOnObject(asteroid);
  selectedAsteroid.value = asteroid;
}
</script>

<template>
  <AppLayout title="AsteroidMap">
    <div class="relative" @click.prevent="">

    <AsteroidMapCanvas :asteroids="asteroids" :stations="stations" @click="onMouseClick" />

    <div class="absolute top-0 left-0 z-100 flex gap-2 ms-4 bg-root">
      <AsteroidMapSearch v-model="searchForm.query" @clear="clearSearch" @search="performSearch" />
    </div>

    <AsteroidMapDropdown v-if="searched_asteroids.length > 0" :searched-asteroids="searched_asteroids"
      :selected-asteroid="selectedAsteroid" @select-asteroid="selectAsteroid" />

<!--     <AsteroidMapModal :show="isModalOpen" :title="selectedObject?.data?.name" :content="selectedObject"
      @close="closeModal" />

    <Modal :spacecrafts="spacecrafts" @close="closeModal" :show="isModalOpen" :title="selectedObject?.data?.name"
      :content="{
        type: selectedObject?.type,
        imageSrc: selectedObject?.type === 'station' ? stationImageSrc : asteroidImageSrc,
        data: selectedObject?.data
      }" /> -->
    </div>
  </AppLayout>
</template>

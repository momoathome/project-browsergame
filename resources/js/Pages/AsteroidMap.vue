<script lang="ts" setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Modules/AsteroidMap/Modal.vue';
import Search from '@/Modules/AsteroidMap/AsteroidMapSearch.vue';
import AsteroidMapDropdown from '@/Modules/AsteroidMap/AsteroidMapDropdown.vue';
import useAsteroidSearch from '@/Composables/useAsteroidSearch';
import useAnimateView from '@/Composables/useAnimateView';
import type { Asteroid, Station, Spacecraft } from '@/types/types';
import * as config from '@/config';

const props = defineProps<{
  asteroids: Asteroid[];
  stations: Station[];
  spacecrafts: Spacecraft[];
  searched_asteroids: Asteroid[];
  searched_stations: Station[];
  selected_asteroid: Asteroid | null;
}>();

const stationImageSrc = '/storage/space-station.png';
const asteroidImageSrc = '/storage/asteroid-light.webp';

const stationImage = new Image();
const asteroidImage = new Image();

const asteroidBaseSize = config.asteroidImageBaseSize;
const stationBaseSize = config.stationImageBaseSize;

const canvasRef = ref<HTMLCanvasElement | null>(null);
const ctx = ref<CanvasRenderingContext2D | null>(null);

// config
const maxOuterZoomLevel = ref(config.maxOuterZoomLevel);
const maxInnerZoomLevel = ref(config.maxInnerZoomLevel);
const zoomLevel = ref(config.baseZoomLevel);
const zoomDelta = ref(config.zoomDelta);
const pointX = ref(0);
const pointY = ref(0);
const startDrag = { x: 0, y: 0 };
const isDragging = ref(false);

const selectedObject = ref<{ type: 'station' | 'asteroid' | null; data: Asteroid | Station | undefined | null } | null>(null);

function adjustCanvasSize() {
  if (canvasRef.value && ctx.value) {
    canvasRef.value.width = window.innerWidth;
    canvasRef.value.height = window.innerHeight - 70;
    drawScene();
  }
}

const {
  searchForm,
  performSearch,
  clearSearch,
  highlightedAsteroids,
  highlightedStations,
} = useAsteroidSearch(drawScene);

onMounted(() => {
  if (props.searched_asteroids && props.searched_asteroids.length) {
    highlightedAsteroids.value = props.searched_asteroids.map((asteroid: Asteroid) => asteroid.id);
  }
  if (props.searched_stations && props.searched_stations.length) {
    highlightedStations.value = props.searched_stations.map((station: Station) => station.id);
  }

  if (canvasRef.value) {
    ctx.value = canvasRef.value.getContext('2d');
    adjustCanvasSize();

    stationImage.src = stationImageSrc;
    asteroidImage.src = asteroidImageSrc;

    stationImage.onload = asteroidImage.onload = () => {
      drawScene();
      const userId = usePage().props.auth.user.id;
      focusUserStationOnInitialLoad(userId);
    };
  }

  window.addEventListener('resize', adjustCanvasSize);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', adjustCanvasSize);
});

function drawScene() {
  if (!ctx.value || !canvasRef.value) return;

  const { width, height } = canvasRef.value;
  ctx.value.clearRect(0, 0, width, height);

  ctx.value.save();
  ctx.value.translate(pointX.value, pointY.value);
  ctx.value.scale(zoomLevel.value, zoomLevel.value);

  const visibleArea = {
    left: -pointX.value / zoomLevel.value,
    top: -pointY.value / zoomLevel.value,
    right: (width - pointX.value) / zoomLevel.value,
    bottom: (height - pointY.value) / zoomLevel.value,
  };

  drawUserScanRange()
  drawStationsAndAsteroids(visibleArea);

  ctx.value.restore();
}

const userScanRange = computed(() => {
  const scanRangeAttribute = usePage().props.userAttributes.find(
    (attr) => attr.attribute_name === 'scan_range'
  );
  return scanRangeAttribute ? +scanRangeAttribute.attribute_value : 5000;
});

function drawUserScanRange() {
  const userStation = props.stations.find(station => station.user_id === usePage().props.auth.user.id);
  if (userStation && ctx.value) {
    ctx.value.beginPath();
    ctx.value.arc(userStation.x, userStation.y, userScanRange.value, 0, 2 * Math.PI);
    ctx.value.fillStyle = 'rgba(36, 36, 36, 0.2)';
    ctx.value.fill();
    ctx.value.stroke();
  }
}

function isObjectVisible(object: { x: number; y: number }, visibleArea: { left: number; top: number; right: number; bottom: number }) {
  return object.x >= visibleArea.left &&
    object.x <= visibleArea.right &&
    object.y >= visibleArea.top &&
    object.y <= visibleArea.bottom;
}

function drawStationsAndAsteroids(visibleArea: { left: number; top: number; right: number; bottom: number }) {
  props.stations.forEach(station => {
    if (isObjectVisible(station, visibleArea)) {
      drawStation(station.x, station.y, station.name, station.id);
    }
  });

  props.asteroids.forEach(asteroid => {
    if (isObjectVisible(asteroid, visibleArea)) {
      drawAsteroid(asteroid.x, asteroid.y, asteroid.id, asteroid.pixel_size);
    }
  });
}

const scale = computed(() => {
  const minZoom = config.maxOuterZoomLevel;
  const maxZoom = config.maxInnerZoomLevel;
  const normalizedZoom = (zoomLevel.value - minZoom) / (maxZoom - minZoom);
  return 1 + Math.pow(1 - normalizedZoom, 2) * 1.5;
});

function drawStation(x: number, y: number, name: string, id: number) {
  if (ctx.value) {
    const scaledSize = stationBaseSize * scale.value;
    const imageX = x - (scaledSize / 2);
    const imageY = y - (scaledSize / 2);

    if (highlightedStations.value.includes(id)) {
      drawHighlight(x, y, scaledSize);
    }

    ctx.value.drawImage(
      stationImage,
      0, 0,
      stationImage.width,
      stationImage.height,
      imageX, imageY,
      scaledSize, scaledSize
    );

    function drawStationName(ctx) {
      ctx.fillStyle = 'white';
      ctx.font = `${config.stationNameFontSize * scale.value}px Arial`;
      const textWidth = ctx.measureText(name).width;
      const textX = x - textWidth / 2;
      const textY = y - scaledSize / 2 - 24 * scale.value;
      ctx.fillText(name, textX, textY);
    }

    drawStationName(ctx.value);
  }
}

function drawAsteroid(x: number, y: number, id: number, size: number) {
  if (ctx.value) {
    const scaledSize = (asteroidBaseSize * size) * scale.value;
    const imageX = x - (scaledSize / 2);
    const imageY = y - (scaledSize / 2);

    if (highlightedAsteroids.value.includes(id)) {
      drawHighlight(x, y, scaledSize);
    }

    ctx.value.drawImage(
      asteroidImage,
      0, 0,
      asteroidImage.width,
      asteroidImage.height,
      imageX, imageY,
      scaledSize, scaledSize);
  }
}

function drawHighlight(x: number, y: number, scaledSize: number, type: 'station' | 'asteroid' = 'asteroid') {
  if (!ctx.value) return;

  const padding = 15 * scale.value;
  const adjustedRadius = scaledSize + padding;

  // Typ-spezifische Anpassungen
  if (type === 'station') {
    ctx.value.strokeStyle = 'yellow';
  } else {
    ctx.value.strokeStyle = 'yellow';
  }

  ctx.value.lineWidth = 5 * scale.value;
  ctx.value.beginPath();
  ctx.value.arc(x, y, adjustedRadius, 0, 2 * Math.PI);
  ctx.value.stroke();
}

function onMouseDown(e: MouseEvent) {
  isDragging.value = true;
  startDrag.x = e.clientX - pointX.value;
  startDrag.y = e.clientY - pointY.value;
}

function onMouseUp() {
  isDragging.value = false;
}

function onMouseMove(e: MouseEvent) {
  const rect = canvasRef.value?.getBoundingClientRect();
  if (!rect || !ctx.value || !canvasRef.value) return;

  if (isDragging.value) {
    pointX.value = e.clientX - startDrag.x;
    pointY.value = e.clientY - startDrag.y;
    requestAnimationFrame(drawScene);
  }
}

function onMouseClick(e: MouseEvent) {
  const rect = canvasRef.value?.getBoundingClientRect();
  if (!rect || !ctx.value || !canvasRef.value) return;

  const scaleX = canvasRef.value.width / rect.width;
  const scaleY = canvasRef.value.height / rect.height;

  const x = (e.clientX - rect.left) * scaleX;
  const y = (e.clientY - rect.top) * scaleY;

  const zoomedX = (x - pointX.value) / zoomLevel.value;
  const zoomedY = (y - pointY.value) / zoomLevel.value;

  // Strg+Linksklick zur Anzeige von Koordinaten
  if (e.ctrlKey) {
    console.log(`Koordinaten: x=${Math.round(zoomedX)}, y=${Math.round(zoomedY)}`);
    // showCoordinatesOverlay(zoomedX, zoomedY);
    e.stopPropagation();
    return;
  }

  let clickedObject: { type: 'station' | 'asteroid' | null; data: Asteroid | Station | undefined | null } = { type: null, data: null };

  for (const station of props.stations) {
    if (Math.abs(zoomedX - station.x) < stationBaseSize * scale.value / 2 &&
      Math.abs(zoomedY - station.y) < stationBaseSize * scale.value / 2) {
      clickedObject = { type: 'station', data: station };
      break;
    }
  }

  for (const asteroid of props.asteroids) {
    const scaledSize = (asteroidBaseSize * asteroid.pixel_size) * scale.value;

    if (Math.abs(zoomedX - asteroid.x) < scaledSize / 2 &&
      Math.abs(zoomedY - asteroid.y) < scaledSize / 2) {
      clickedObject = { type: 'asteroid', data: asteroid };
      break;
    }
  }

  if (clickedObject.data) {
    const isOtherUserStation = clickedObject.type === 'station' &&
      'user_id' in clickedObject.data &&
      clickedObject.data.user_id !== usePage().props.auth.user.id;

    if (isOtherUserStation) {
      selectedObject.value = clickedObject;
      isModalOpen.value = true;
    } else if (clickedObject.type === 'asteroid') {
      if (clickedObject.type === 'asteroid' && clickedObject.data) {
        getAsteroidResources(clickedObject.data as Asteroid);
      }
    }
  }
}

function onWheel(e: WheelEvent) {
  e.preventDefault();

  const delta = e.deltaY < 0 ? zoomDelta.value : -zoomDelta.value;
  const newZoomLevel = Math.min(Math.max(zoomLevel.value + delta, maxOuterZoomLevel.value), maxInnerZoomLevel.value);

  if (newZoomLevel !== zoomLevel.value) {
    const rect = canvasRef.value?.getBoundingClientRect();
    if (rect) {
      const scaleChange = newZoomLevel / zoomLevel.value;
      const mouseX = e.clientX - rect.left;
      const mouseY = e.clientY - rect.top;

      pointX.value += (mouseX - pointX.value) * (1 - scaleChange);
      pointY.value += (mouseY - pointY.value) * (1 - scaleChange);
      zoomLevel.value = newZoomLevel;

      requestAnimationFrame(drawScene);
    }
  }
}

/* function showCoordinatesOverlay(x: number, y: number) {
  if (!canvasRef.value) return;
  
  const overlay = document.createElement('div');
  const roundedX = Math.round(x);
  const roundedY = Math.round(y);
  
  overlay.textContent = `X: ${roundedX}, Y: ${roundedY}`;
  overlay.style.position = 'absolute';
  overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
  overlay.style.color = 'white';
  overlay.style.padding = '8px 12px';
  overlay.style.borderRadius = '4px';
  overlay.style.fontSize = '14px';
  overlay.style.pointerEvents = 'none';
  overlay.style.zIndex = '1000';
  
  // Positionierung direkt am Mauszeiger (mit leichtem Offset)
  const mousePosX = (x * zoomLevel.value) + pointX.value;
  const mousePosY = (y * zoomLevel.value) + pointY.value;
  overlay.style.left = `${mousePosX}px`;
  overlay.style.top = `${mousePosY}px`;
  
  document.body.appendChild(overlay);
  
  // Overlay nach 3 Sekunden wieder entfernen
  setTimeout(() => {
    document.body.removeChild(overlay);
  }, 3000);
} */

function getAsteroidResources(asteroid: Asteroid) {
  const asteroidId = useForm({
    asteroid: asteroid.id,
  })

  asteroidId.get(route('asteroidMap.asteroid', { asteroid: asteroid.id }), {
    preserveState: true,
    only: ['selected_asteroid'],
    onSuccess: () => {
      selectedObject.value = {
        data: props.selected_asteroid,
        type: 'asteroid',
      }
      isModalOpen.value = true;
    },
  });
}

const { animateView } = useAnimateView(pointX, pointY, zoomLevel, drawScene);

function focusOnObject(object: Station | Asteroid, userId?: number) {
  const targetObject = userId
    ? props.stations.find(station => station.user_id === userId)
    : props.asteroids.find(asteroid => asteroid.id === object.id);

  if (!targetObject || !canvasRef.value) return;

  const targetX = -(targetObject.x * zoomLevel.value - canvasRef.value.width / 2);
  const targetY = -(targetObject.y * zoomLevel.value - canvasRef.value.height / 2);
  // const targetZoomLevel = config.baseZoomLevel;
  const targetZoomLevel = zoomLevel.value;

  animateView(targetX, targetY, targetZoomLevel);
}

function focusUserStationOnInitialLoad(userId: number) {
  const userStation = props.stations.find(station => station.user_id === userId);
  if (!userStation || !canvasRef.value) return;

  pointX.value = -(userStation.x * config.initialZoom - canvasRef.value.width / 2);
  pointY.value = -(userStation.y * config.initialZoom - canvasRef.value.height / 2);
  zoomLevel.value = config.initialZoom;
}

function searchAndFocus() {
  performSearch(() => {
    focusOnSingleResult();
    drawScene();
  });
}

function clearSearchAndUpdate() {
  clearSearch();
  drawScene();
}

const focusOnSingleResult = () => {
  if (highlightedAsteroids.value.length === 1) {
    const asteroid = props.asteroids.find(a => a.id === highlightedAsteroids.value[0]);
    if (asteroid) {
      focusOnObject(asteroid);
    }
  } else if (highlightedStations.value.length === 1) {
    const station = props.stations.find(s => s.id === highlightedStations.value[0]);
    if (station) {
      focusOnObject(station, station.user_id);
    }
  }
};

const isModalOpen = ref(false)
function closeModal() {

  if (searchForm.query) {
    router.visit(route('asteroidMap.search', { query: searchForm.query }), {
      preserveScroll: true,
      preserveState: true
    });
  } else {
    router.visit(route('asteroidMap'), {
      preserveScroll: true,
      preserveState: true
    });
  }


  isModalOpen.value = false;
  setTimeout(() => {
    selectedObject.value = null;
  }, 300);
}

const selectedAsteroid = ref<Asteroid>();
function selectAsteroid(asteroid: Asteroid) {
  focusOnObject(asteroid);
  selectedAsteroid.value = asteroid;
}

</script>

<template>
  <AppLayout title="AsteroidMap">
    <div class="relative" @click.prevent="">
      <canvas ref="canvasRef" class="block w-full bg-[hsl(263,45%,7%)]" @mousedown="onMouseDown"
        @mousemove="onMouseMove" @mouseup="onMouseUp" @wheel="onWheel" @click="onMouseClick">
      </canvas>

      <div class="absolute top-2 left-0 z-100 flex gap-2 ms-4 bg-[hsl(263,45%,7%)]">
        <Search v-model="searchForm.query" @clear="clearSearchAndUpdate" @search="searchAndFocus" />
      </div>

      <AsteroidMapDropdown v-if="searched_asteroids && searched_asteroids.length > 0"
        class="absolute top-2 left-64 ms-2 w-44" :searched-asteroids="searched_asteroids"
        :selected-asteroid="selectedAsteroid" @select-asteroid="selectAsteroid" />

      <span class="absolute top-0 right-0 z-100 text-white me-2">zoom: {{ Math.round(zoomLevel * 1000 / 5) }}%</span>
      <span @click="focusOnObject(undefined, usePage().props.auth.user.id)"
        class="cursor-pointer absolute top-6 right-0 z-100 text-white me-2">
        reset
      </span>
    </div>

    <Modal :spacecrafts="spacecrafts" :user-scan-range="userScanRange" @close="closeModal" :show="isModalOpen"
      :title="selectedObject?.data?.name" :content="{
        type: selectedObject?.type,
        imageSrc: selectedObject?.type === 'station' ? stationImageSrc : asteroidImageSrc,
        data: selectedObject?.data as Asteroid | Station,
      }" />
  </AppLayout>
</template>

<style scoped>
ul::-webkit-scrollbar-track {
  border-radius: 16px;
  background-color: hsl(263, 45%, 7%);
}

ul::-webkit-scrollbar {
  width: 3px;
  background-color: hsl(263, 45%, 7%);
}

ul::-webkit-scrollbar-thumb {
  border-radius: 10px;
  -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, .3);
  background-color: #bfbfbf;
}
</style>

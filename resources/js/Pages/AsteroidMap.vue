<script lang="ts" setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Modules/AsteroidMap/Modal.vue';
import Search from '@/Modules/AsteroidMap/AsteroidMapSearch.vue';
import AsteroidMapDropdown from '@/Modules/AsteroidMap/AsteroidMapDropdown.vue';
import { usePage, useForm } from '@inertiajs/vue3';
import type { Asteroid, Station, Spacecraft } from '@/types/types';
import * as config from '@/config';
import { Quadtree } from '@/quadTree.js'

const props = defineProps<{
  asteroids: Asteroid[];
  stations: Station[];
  spacecrafts: Spacecraft[];
  searched_asteroids: Asteroid[];
  searched_stations: Station[];
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

const quadtree = ref<Quadtree | null>(null);
const hoveredObject = ref<{ type: 'station' | 'asteroid'; id: number } | null>(null);
const selectedObject = ref<{ type: 'station' | 'asteroid' | null; data: Asteroid | Station | undefined | null } | null>(null);

function adjustCanvasSize() {
  if (canvasRef.value && ctx.value) {
    canvasRef.value.width = window.innerWidth;
    canvasRef.value.height = window.innerHeight - 125;
    drawScene();
  }
}

const highlightedAsteroids = ref<number[]>([]);
const highlightedStations = ref<number[]>([]);

onMounted(() => {
  if (props.searched_asteroids && props.searched_asteroids.length) {
    highlightedAsteroids.value = props.searched_asteroids.map((asteroid: Asteroid) => asteroid.id);
  }
  if (props.searched_stations && props.searched_stations.length) {
    highlightedStations.value = props.searched_stations.map((station: Station) => station.id);
  }

  if (canvasRef.value) {
    initializeQuadtree();
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

function initializeQuadtree() {
  const universeSize = 150_000;
  quadtree.value = new Quadtree(0, 0, universeSize, universeSize);

  props.asteroids.forEach(asteroid => {
    quadtree.value?.insert({
      x: asteroid.x,
      y: asteroid.y,
      data: { type: 'asteroid', ...asteroid }
    });
  });

  props.stations.forEach(station => {
    quadtree.value?.insert({
      x: station.x,
      y: station.y,
      data: { type: 'station', ...station }
    });
  });
}

watch(() => props.asteroids, () => {
  initializeQuadtree();
});

watch(() => props.stations, () => {
  initializeQuadtree();
});

function drawScene() {
  if (!ctx.value || !canvasRef.value) return;

  const { width, height } = canvasRef.value;
  ctx.value.clearRect(0, 0, width, height);

  ctx.value.save();
  ctx.value.translate(pointX.value, pointY.value);
  ctx.value.scale(zoomLevel.value, zoomLevel.value);

  const visibleArea = calculateVisibleArea();
  if (!visibleArea) return;

  // Query the quadtree for objects in the visible area
  const objectsInView = quadtree.value?.query(visibleArea);

  // Draw user's view range
  const userStation = props.stations.find(station => station.user_id === usePage().props.auth.user.id);
  if (userStation) {
    ctx.value.beginPath();
    ctx.value.arc(userStation.x, userStation.y, userScanRange.value, 0, 2 * Math.PI);
    ctx.value.fillStyle = 'rgba(36, 36, 36, 0.2)';
    ctx.value.fill();
    ctx.value.stroke();
  }

  // Draw only the objects in view
  objectsInView.forEach(object => {
    if (object.data.type === 'station') {
      drawStation(object.x, object.y, object.data.name, object.data.id);
    } else if (object.data.type === 'asteroid') {
      drawAsteroid(object.x, object.y, object.data.id, object.data.pixel_size);
    }
  });

  ctx.value.restore();
}

function calculateVisibleArea() {
  if (!ctx.value || !canvasRef.value) return;

  const { width, height } = canvasRef.value;
  const scale = 1 / zoomLevel.value;
  return {
    x: -pointX.value * scale,
    y: -pointY.value * scale,
    width: (-pointX.value + width) * scale,
    height: (-pointY.value + height) * scale,
  };
}

/* function drawDistanceZones() {
  if (ctx.value) {
    ctx.value.strokeStyle = 'yellow';
    ctx.value.lineWidth = 2;

    const initialRadius = 1000;
    const maxRadius = 50_000;
    const step = 1000;

    props.stations.forEach(station => {
      const x = station.x;
      const y = station.y;

      for (let radius = initialRadius; radius <= maxRadius; radius += step) {
        ctx.value.beginPath();
        ctx.value.arc(x, y, radius, 0, 2 * Math.PI);
        ctx.value.stroke();
      }
    });
  }
} */

function drawStation(x: number, y: number, name: string, id: number) {
  if (ctx.value) {
    const imageX = x - (stationBaseSize / 2);
    const imageY = y - (stationBaseSize / 2);

    if (highlightedStations.value.includes(id)) {
      const padding = 20;
      const adjustedRadius = stationBaseSize + padding;
      ctx.value.strokeStyle = 'orange';
      ctx.value.lineWidth = 5;
      ctx.value.beginPath();
      ctx.value.arc(x, y, adjustedRadius, 0, 2 * Math.PI);
      ctx.value.stroke();
    }

    ctx.value.drawImage(
      stationImage,
      0, 0,
      stationImage.width, stationImage.height,
      imageX, imageY,
      stationBaseSize, stationBaseSize
    );

    ctx.value.fillStyle = 'white';
    ctx.value.font = `${config.stationNameFontSize}px Arial`;

    const textWidth = ctx.value.measureText(name).width;
    const textX = x - textWidth / 2;
    const textY = y - stationBaseSize / 2 - 24;
    ctx.value.fillText(name, textX, textY);
  }
}

function drawAsteroid(x: number, y: number, id: number, size: number) {
  if (ctx.value) {
    const scaledSize = asteroidBaseSize * size;

    const imageX = x - (scaledSize / 2);
    const imageY = y - (scaledSize / 2);

    if (highlightedAsteroids.value.includes(id)) {
      const padding = 15;
      const adjustedRadius = scaledSize + padding;
      ctx.value.strokeStyle = 'yellow';
      ctx.value.lineWidth = 5;
      ctx.value.beginPath();
      ctx.value.arc(x, y, adjustedRadius, 0, 2 * Math.PI);
      ctx.value.stroke();
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
    drawScene();
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

  let clickedObject: { type: 'station' | 'asteroid' | null; data: Asteroid | Station | undefined | null } = { type: null, data: null };

  for (const station of props.stations) {
    if (Math.abs(zoomedX - station.x) < stationBaseSize / 2 &&
      Math.abs(zoomedY - station.y) < stationBaseSize / 2) {
      clickedObject = { type: 'station', data: station };
      break;
    }
  }

  for (const asteroid of props.asteroids) {
    const scaledSize = asteroidBaseSize * asteroid.pixel_size;

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

    if (isOtherUserStation || clickedObject.type === 'asteroid') {
      selectedObject.value = clickedObject;
      isModalOpen.value = true;
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

function animateView(
  targetX: number,
  targetY: number,
  targetZoomLevel: number
) {
  const startPointX = pointX.value;
  const startPointY = pointY.value;
  const startZoomLevel = zoomLevel.value;

  const endPointX = targetX;
  const endPointY = targetY;
  const endZoomLevel = targetZoomLevel;

  const distance = Math.sqrt(
    Math.pow(endPointX - startPointX, 2) + Math.pow(endPointY - startPointY, 2)
  );

  const maxAnimationDuration = 1500;
  const minAnimationDuration = 300;
  const animationDuration = Math.round(Math.max(
    minAnimationDuration,
    Math.min(maxAnimationDuration, distance / 8)
  ));

  const startTime = performance.now();

  function animate(time: number) {
    const elapsedTime = time - startTime;
    const progress = Math.min(elapsedTime / animationDuration, 1);

    pointX.value = startPointX + (endPointX - startPointX) * progress;
    pointY.value = startPointY + (endPointY - startPointY) * progress;
    zoomLevel.value = startZoomLevel + (endZoomLevel - startZoomLevel) * progress;

    drawScene();

    if (progress < 1) {
      requestAnimationFrame(animate);
    }
  }

  requestAnimationFrame(animate);
}

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

const form = useForm({
  query: '',
});

function performSearch() {
  form.get('/asteroidMap/search', {
    preserveState: true,
    only: ['searched_asteroids', 'searched_stations'],
    onSuccess: () => {
      const updateHighlightedItems = (items, highlightedRef) => {
        highlightedRef.value = items?.length ? items.map(item => item.id) : [];
      };

      updateHighlightedItems(props.searched_asteroids, highlightedAsteroids);
      updateHighlightedItems(props.searched_stations, highlightedStations);

      drawScene();

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

      focusOnSingleResult();
    },
    onError: (errors) => {
      console.error('Error during search:', errors);
    }
  });
}

function clearSearch() {
  const url = new URL(window.location.href);
  url.searchParams.delete('query');
  window.history.pushState({}, '', url);
  usePage().props.searched_asteroids = [];

  form.query = '';
  highlightedAsteroids.value = [];
  highlightedStations.value = [];
  drawScene();
}

const isModalOpen = ref(false)
function closeModal() {
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

const userScanRange = computed(() => {
  const scanRangeAttribute = usePage().props.userAttributes.find(
    (attr) => attr.attribute_name === 'scan_range'
  );
  return scanRangeAttribute ? scanRangeAttribute.attribute_value : 5000;
});
</script>

<template>
  <AppLayout title="AsteroidMap">
    <div class="relative" @click.prevent="">
      <canvas ref="canvasRef" class="block w-full bg-[hsl(263,45%,7%)]" @mousedown="onMouseDown"
        @mousemove="onMouseMove" @mouseup="onMouseUp" @wheel="onWheel" @click="onMouseClick">
      </canvas>

      <form class="absolute top-0 left-0 z-100 flex gap-2 ms-4 bg-[hsl(263,45%,7%)]">
        <Search v-model="form.query" @clear="clearSearch" @search="performSearch" />
      </form>

      <AsteroidMapDropdown v-if="searched_asteroids && searched_asteroids.length > 0"
        :searched-asteroids="searched_asteroids" :selected-asteroid="selectedAsteroid" @select-asteroid="selectAsteroid"
        class="absolute top-0 left-64 ms-2 w-44" />

      <span class="absolute top-0 right-0 z-100 text-white me-2">zoom: {{ Math.round(zoomLevel * 1000 / 5) }}%</span>
      <span @click="focusOnObject(undefined, usePage().props.auth.user.id)"
        class="cursor-pointer absolute top-6 right-0 z-100 text-white me-2">
        reset
      </span>
    </div>

    <Modal :spacecrafts="spacecrafts" @close="closeModal" :show="isModalOpen" :title="selectedObject?.data?.name"
      :content="{
        type: selectedObject?.type,
        imageSrc: selectedObject?.type === 'station' ? stationImageSrc : asteroidImageSrc,
        data: selectedObject?.data
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

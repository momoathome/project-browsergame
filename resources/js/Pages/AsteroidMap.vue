<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Modules/AsteroidMap/Modal.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import * as config from '@/config';
import type { Asteroid, Station, Spacecraft } from '@/types/types';
import { usePage, useForm } from '@inertiajs/vue3';
import Search from '@/Modules/AsteroidMap/AsteroidMapSearch.vue';

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

const hoveredObject = ref<{ type: 'station' | 'asteroid'; id: number } | null>(null);
const selectedObject = ref<{ type: 'station' | 'asteroid'; data: Asteroid | Station | undefined } | null>(null);

function adjustCanvasSize() {
  if (canvasRef.value && ctx.value) {
    const devicePixelRatio = window.devicePixelRatio || 1;

    canvasRef.value.width = window.innerWidth * devicePixelRatio;
    canvasRef.value.height = window.innerHeight * devicePixelRatio;

    ctx.value.scale(devicePixelRatio, devicePixelRatio);
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
    ctx.value = canvasRef.value.getContext('2d');
    adjustCanvasSize();

    stationImage.src = stationImageSrc;
    asteroidImage.src = asteroidImageSrc;

    stationImage.onload = asteroidImage.onload = () => {
      drawScene();
      const userId = usePage().props.auth.user.id;
      // resetViewToUserStation(userId);
      focusUserStationOnInitialLoad(userId);
    };
  }

  window.addEventListener('resize', adjustCanvasSize);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', adjustCanvasSize);
});

function drawScene() {
  if (ctx.value && canvasRef.value) {
    ctx.value.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);

    ctx.value.save();
    ctx.value.translate(pointX.value, pointY.value);
    ctx.value.scale(zoomLevel.value, zoomLevel.value);

    // drawDistanceZones();
    props.stations.forEach(station => {
      drawStation(station.x, station.y, station.name, station.id);
    });

    props.asteroids.forEach(asteroid => {
      drawAsteroid(asteroid.x, asteroid.y, asteroid.id, asteroid.pixel_size);
    });

    ctx.value.restore();
  }
}

function drawDistanceZones() {
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
}


function drawStation(x: number, y: number, name: string, id: number) {
  if (ctx.value) {
    const imageX = x - stationBaseSize / 2 * devicePixelRatio;
    const imageY = y - stationBaseSize / 2 * devicePixelRatio;


    if (highlightedStations.value.includes(id)) {
      const padding = 20;
      const adjustedRadius = stationBaseSize + padding;
      ctx.value.strokeStyle = 'orange';
      ctx.value.lineWidth = 5;
      ctx.value.beginPath();
      ctx.value.arc(x, y, adjustedRadius, 0, 2 * Math.PI);
      ctx.value.stroke();
    }

    // Zeichne das Station-Icon
    ctx.value.drawImage(
      stationImage,
      0, 0,
      stationImage.width, stationImage.height,
      imageX, imageY,
      stationBaseSize, stationBaseSize
    );

    // Setze die Schriftart und -größe
    ctx.value.fillStyle = 'white';
    ctx.value.font = `${config.stationNameFontSize}px Arial`;

    // Berechne die Breite des Textes
    const textWidth = ctx.value.measureText(name).width;

    // Zentriere den Text über dem Station-Icon
    const textX = x - textWidth / 2;
    const textY = y - stationBaseSize / 2 - 24;

    // Zeichne den Text
    ctx.value.fillText(name, textX, textY);
  }
}

function drawAsteroid(x: number, y: number, id: number, size: number) {
  if (ctx.value) {
    const scaledWidth = asteroidBaseSize * size * devicePixelRatio;
    const scaledHeight = asteroidBaseSize * size * devicePixelRatio;

    const imageX = x - scaledWidth / 2;
    const imageY = y - scaledHeight / 2;

    // draw highlighted asteroids
    if (highlightedAsteroids.value.includes(id)) {
      const padding = 15;
      const adjustedRadius = scaledWidth + padding;
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
      scaledWidth, scaledHeight);
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
  if (!rect || !ctx.value) return;

  const x = (e.clientX - rect.left) * (canvasRef.value!.width / rect.width);
  const y = (e.clientY - rect.top) * (canvasRef.value!.height / rect.height);

  const zoomedX = (x - pointX.value) / zoomLevel.value;
  const zoomedY = (y - pointY.value) / zoomLevel.value;

  hoveredObject.value = null;

  props.stations.forEach(station => {
    if (Math.abs(zoomedX - station.x) < stationBaseSize / 2 &&
      Math.abs(zoomedY - station.y) < stationBaseSize / 2) {
      hoveredObject.value = { type: 'station', id: station.id };
    }
  });

  props.asteroids.forEach(asteroid => {
    const scaledWidth = asteroidBaseSize * asteroid.pixel_size;
    const scaledHeight = asteroidBaseSize * asteroid.pixel_size;

    if (Math.abs(zoomedX - asteroid.x) < scaledWidth / 2 &&
      Math.abs(zoomedY - asteroid.y) < scaledHeight / 2) {
      hoveredObject.value = { type: 'asteroid', id: asteroid.id };
    }
  });

  if (isDragging.value) {
    pointX.value = e.clientX - startDrag.x;
    pointY.value = e.clientY - startDrag.y;
    drawScene();
  }
}

function onMouseClick(e: MouseEvent) {
  const rect = canvasRef.value?.getBoundingClientRect();
  if (!rect || !hoveredObject.value) return;

  if (hoveredObject.value.type === 'station') {
    const station = props.stations.find(station => station.id === hoveredObject.value?.id);
    if (station && station.user_id !== usePage().props.auth.user.id) {
      selectedObject.value = { type: 'station', data: station };
      isModalOpen.value = true;
    }
  } else if (hoveredObject.value.type === 'asteroid') {
    const asteroid = props.asteroids.find(asteroid => asteroid.id === hoveredObject.value?.id);
    if (asteroid) {
      selectedObject.value = { type: 'asteroid', data: asteroid };
      isModalOpen.value = true;
    }
  }
}

function onWheel(e: WheelEvent) {
  e.preventDefault();

  const xs = (e.clientX - pointX.value) / zoomLevel.value;
  const ys = (e.clientY - pointY.value) / zoomLevel.value;

  const delta = e.deltaY < 0 ? zoomDelta.value : -zoomDelta.value;

  const newZoomLevel = Math.min(Math.max(zoomLevel.value + delta, maxOuterZoomLevel.value), maxInnerZoomLevel.value);

  pointX.value = e.clientX - xs * newZoomLevel;
  pointY.value = e.clientY - ys * newZoomLevel;

  zoomLevel.value = newZoomLevel;
  drawScene();
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

function resetViewToUserStation(userId: number) {
  const userStation = props.stations.find(station => station.user_id === userId);
  if (!userStation || !canvasRef.value) return;

  const targetX = -(userStation.x * config.baseZoomLevel - canvasRef.value.width / 2);
  const targetY = -(userStation.y * config.baseZoomLevel - canvasRef.value.height / 2);
  const targetZoomLevel = config.baseZoomLevel;

  animateView(targetX, targetY, targetZoomLevel);
}

function focusOnAsteroid(asteroidId: number) {
  const asteroid = props.asteroids.find(asteroid => asteroid.id === asteroidId);
  if (!asteroid || !canvasRef.value) return;

  const targetX = -(asteroid.x * config.baseZoomLevel - canvasRef.value.width / 2);
  const targetY = -(asteroid.y * config.baseZoomLevel - canvasRef.value.height / 2);
  const targetZoomLevel = config.baseZoomLevel;

  animateView(targetX, targetY, targetZoomLevel);
}

function focusUserStationOnInitialLoad(userId: number) {
  const userStation = props.stations.find(station => station.user_id === userId);
  if (!userStation || !canvasRef.value) return;

  pointX.value = -(userStation.x * config.initialZoom - canvasRef.value.width / 2);
  pointY.value = -(userStation.y * config.initialZoom - canvasRef.value.height / 2);
  zoomLevel.value = config.initialZoom;

  drawScene();
}

const form = useForm({
  query: '',
});

function performSearch() {
  form.get('/asteroidMap/search', {
    preserveState: true,
    only: ['searched_asteroids', 'searched_stations'],
    onSuccess: () => {
      if (props.searched_asteroids && props.searched_asteroids.length) {
        highlightedAsteroids.value = props.searched_asteroids.map((asteroid: Asteroid) => asteroid.id);
      } else {
        highlightedAsteroids.value = [];
      }

      if (props.searched_stations && props.searched_stations.length) {
        highlightedStations.value = props.searched_stations.map((station: Station) => station.id);
      } else {
        highlightedStations.value = [];
      }
      drawScene();

      if (highlightedAsteroids.value.length === 1) {
        const asteroidId = highlightedAsteroids.value[0];
        focusOnAsteroid(asteroidId);
      } else if (highlightedStations.value.length === 1) {
        const stationId = highlightedStations.value[0];
        resetViewToUserStation(stationId);
      }
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
</script>

<template>
  <AppLayout title="AsteroidMap">
    <div class="relative" @click.prevent="">
      <canvas ref="canvasRef" class="block w-full bg-[hsl(263,45%,7%)]" @mousedown="onMouseDown"
        @mousemove="onMouseMove" @mouseup="onMouseUp" @wheel="onWheel" @click="onMouseClick">
      </canvas>

      <form class="absolute top-0 left-0 z-100 flex gap-2 ms-4">
        <Search v-model="form.query" @clear="clearSearch" @search="performSearch" />
      </form>

      <span class="absolute top-0 right-0 z-100 text-white p-2">zoom: {{ Math.round(zoomLevel * 1000 / 5) }}%</span>
      <span @click="resetViewToUserStation(usePage().props.auth.user.id)"
        class="cursor-pointer absolute top-6 right-0 z-100 text-white p-2">reset</span>
    </div>

    <Modal :spacecrafts="spacecrafts" @close="closeModal" :show="isModalOpen" :title="selectedObject?.data?.name"
      :content="{
        type: selectedObject?.type,
        imageSrc: selectedObject?.type === 'station' ? stationImageSrc : asteroidImageSrc,
        data: selectedObject?.data
      }" />
  </AppLayout>
</template>

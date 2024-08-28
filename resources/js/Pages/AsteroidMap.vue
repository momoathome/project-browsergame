<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Modules/AsteroidMap/Modal.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import * as config from '@/config';
import type { Asteroid, Station, Spacecraft } from '@/types/types';

const props = defineProps<{
  asteroids: Asteroid[];
  spacecrafts: Spacecraft[];
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

// TODO: auslagern
const stations = [
  { id: 1, x: 15000, y: 10000, name: 'Station 1' },
  { id: 2, x: 30000, y: 30000, name: 'Station 2' },
  { id: 3, x: 40000, y: 20000, name: 'Station 3' },
];

const hoveredObject = ref<{ type: 'station' | 'asteroid'; id: number } | null>(null);
const selectedObject = ref<{ type: 'station' | 'asteroid'; data: Asteroid | Station } | null>(null);

function adjustCanvasSize() {
  if (canvasRef.value && ctx.value) {
    const devicePixelRatio = window.devicePixelRatio || 1;
    const rect = canvasRef.value.getBoundingClientRect();

    canvasRef.value.width = rect.width * devicePixelRatio;
    canvasRef.value.height = rect.height * devicePixelRatio;

    ctx.value.scale(devicePixelRatio, devicePixelRatio);
    drawScene();
  }
}

onMounted(() => {
  if (canvasRef.value) {
    ctx.value = canvasRef.value.getContext('2d');
    adjustCanvasSize();

    stationImage.src = stationImageSrc;
    asteroidImage.src = asteroidImageSrc;

    stationImage.onload = asteroidImage.onload = () => {
      drawScene();
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

    stations.forEach(station => {
      drawStation(station.x, station.y, station.name, station.id);
    });

    props.asteroids.forEach(asteroid => {
      drawAsteroid(asteroid.x, asteroid.y, asteroid.id, asteroid.pixel_size);
    });

    ctx.value.restore();
  }
}

function drawStation(x: number, y: number, name: string, id: number) {
  if (ctx.value) {
    const imageX = x - stationBaseSize / 2;
    const imageY = y - stationBaseSize / 2;

    ctx.value.drawImage(stationImage, 0, 0, stationImage.width, stationImage.height, imageX, imageY, stationBaseSize, stationBaseSize);

    ctx.value.fillStyle = 'white';
    ctx.value.font = config.stationNameFontSize + 'px Arial';
    ctx.value.fillText(name, x - 196, y - stationBaseSize / 2 - 64);
  }
}

function drawAsteroid(x: number, y: number, id: number, size: number) {
  if (ctx.value) {
    const scaledWidth = asteroidBaseSize * size;
    const scaledHeight = asteroidBaseSize * size;

    const imageX = x - scaledWidth / 2;
    const imageY = y - scaledHeight / 2;
    ctx.value.drawImage(asteroidImage, 0, 0, asteroidImage.width, asteroidImage.height, imageX, imageY, scaledWidth, scaledHeight);
  }
}

function onMouseDown(e: MouseEvent) {
  isDragging.value = true;
  startDrag.x = e.clientX - pointX.value;
  startDrag.y = e.clientY - pointY.value;
}

function onMouseMove(e: MouseEvent) {
  const rect = canvasRef.value?.getBoundingClientRect();
  if (!rect || !ctx.value) return;

  const x = (e.clientX - rect.left) * (canvasRef.value!.width / rect.width);
  const y = (e.clientY - rect.top) * (canvasRef.value!.height / rect.height);

  const zoomedX = (x - pointX.value) / zoomLevel.value;
  const zoomedY = (y - pointY.value) / zoomLevel.value;

  hoveredObject.value = null;

  stations.forEach(station => {
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

  drawScene();

  if (isDragging.value) {
    pointX.value = e.clientX - startDrag.x;
    pointY.value = e.clientY - startDrag.y;
    drawScene();
  }
}

function onMouseUp() {
  isDragging.value = false;
}

function onMouseClick(e: MouseEvent) {
  const rect = canvasRef.value?.getBoundingClientRect();
  if (!rect || !hoveredObject.value) return;

  if (hoveredObject.value.type === 'station') {
    const station = stations.find(station => station.id === hoveredObject.value?.id);
    if (station) {
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

function resetView() {
  const animationDuration = 1000; // Dauer der Animation in Millisekunden
  const startTime = performance.now();

  const startPointX = pointX.value;
  const startPointY = pointY.value;
  const startZoomLevel = zoomLevel.value;

  const endPointX = 0;
  const endPointY = 0;
  const endZoomLevel = config.baseZoomLevel;

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

const isModalOpen = ref(false)

function closeModal() {
  isModalOpen.value = false;
  selectedObject.value = null;
}
</script>

<template>
  <AppLayout title="AsteroidMap">
    <div class="relative">
      <canvas ref="canvasRef" class="block w-full bg-[hsl(263,45%,7%)]" @mousedown="onMouseDown"
        @mousemove="onMouseMove" @mouseup="onMouseUp" @wheel="onWheel" @click="onMouseClick">
      </canvas>

      <span class="absolute top-0 right-0 z-100 text-white p-2">zoom: {{ Math.round(zoomLevel * 1000) }}%</span>
      <span @click="resetView" class="cursor-pointer absolute top-6 right-0 z-100 text-white p-2">reset</span>
    </div>

      <Modal
        :spacecrafts="spacecrafts"
        @close="closeModal"
        :show="isModalOpen"
        :title="selectedObject?.data.name" 
        :content="{
          type: selectedObject?.type,
          imageSrc: selectedObject?.type === 'station' ? stationImageSrc : asteroidImageSrc,
          data: selectedObject?.data
        }" 
        />
  </AppLayout>
</template>

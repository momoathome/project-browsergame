<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { createAsteroids } from '@/Utils/createAsteroids';
import { createAsteroidCoordinates } from '@/Utils/createAsteroidCoordinates';
import * as config from '@/config';

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

const asteroidsData = createAsteroids(config.asteroidCount);
const asteroidWithCoords = createAsteroidCoordinates(asteroidsData, stations);

const hoveredObject = ref<{ type: 'station' | 'asteroid'; id: number } | null>(null);

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

    asteroidWithCoords.forEach(asteroid => {
      drawAsteroid(asteroid.x, asteroid.y, asteroid.id, asteroid.pixelSize);
    });

    ctx.value.restore();
  }
}

function drawStation(x: number, y: number, name: string, id: number) {
  if (ctx.value) {
    const imageX = x - stationBaseSize / 2;
    const imageY = y - stationBaseSize / 2;

    ctx.value.drawImage(stationImage, 0, 0, stationImage.width, stationImage.height, imageX, imageY, stationBaseSize, stationBaseSize);

    if (hoveredObject.value?.type === 'station' && hoveredObject.value.id === id) {
      ctx.value.strokeStyle = 'yellow';
      ctx.value.lineWidth = 2;
      ctx.value.strokeRect(imageX, imageY, stationBaseSize, stationBaseSize);
    }

    ctx.value.fillStyle = 'white';
    ctx.value.font = '36px Arial';
    ctx.value.fillText(name, x - 60, y - stationBaseSize / 2 - 30); // 
  }
}

function drawAsteroid(x: number, y: number, id: number, size: number) {
  if (ctx.value) {
    const scaledWidth = asteroidBaseSize * size;
    const scaledHeight = asteroidBaseSize * size;

    const imageX = x - scaledWidth / 2;
    const imageY = y - scaledHeight / 2;
    ctx.value.drawImage(asteroidImage, 0, 0, asteroidImage.width, asteroidImage.height, imageX, imageY, scaledWidth, scaledHeight);

    if (hoveredObject.value?.type === 'asteroid' && hoveredObject.value.id === id) {
      ctx.value.strokeStyle = 'yellow';
      ctx.value.lineWidth = 2;
      ctx.value.strokeRect(imageX, imageY, scaledWidth, scaledHeight);
    }
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

  asteroidWithCoords.forEach(asteroid => {
    const scaledWidth = asteroidBaseSize * asteroid.pixelSize;
    const scaledHeight = asteroidBaseSize * asteroid.pixelSize;

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
      console.log(`Clicked on station: ${station.name}, data: ${JSON.stringify(station, null, 2)}`);
      // Show modal or other UI element
    }
  } else if (hoveredObject.value.type === 'asteroid') {
    const asteroid = asteroidWithCoords.find(asteroid => asteroid.id === hoveredObject.value?.id);
    if (asteroid) {
      console.log(`Clicked on asteroid with id: ${asteroid.id}, data: ${JSON.stringify(asteroid, null, 2)}`);
      // Show modal or other UI element
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
</script>

<template>
  <AppLayout title="AsteroidMap">
    <div class="relative">
      <canvas ref="canvasRef" class="block w-full bg-[hsl(263,45%,7%)]"
      @mousedown="onMouseDown"
      @mousemove="onMouseMove"
      @mouseup="onMouseUp"
      @wheel="onWheel"
      @click="onMouseClick">
      </canvas>

      <span class="absolute top-0 right-0 z-100 text-white p-2">zoom: {{ Math.round(zoomLevel * 1000) }}%</span>
    </div>
  </AppLayout>
</template>

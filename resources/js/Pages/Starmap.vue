<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';

const stationImageSrc = '/storage/space-station-light.png';
const asteroidImageSrc = '/storage/asteroid-light.webp';

const stationImage = new Image();
const asteroidImage = new Image();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const ctx = ref<CanvasRenderingContext2D | null>(null);

const zoomLevel = ref(1);
const pointX = ref(0);
const pointY = ref(0);
const isDragging = ref(false);
const startDrag = { x: 0, y: 0 };

const stations = [
  { x: 100, y: 100, name: 'Station 1' },
  { x: 200, y: 300, name: 'Station 2' },
];

const asteroids = [
  { x: 400, y: 200, id: 1 },
  { x: 600, y: 400, id: 2 },
];

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
      drawStation(station.x, station.y, station.name);
    });

    asteroids.forEach(asteroid => {
      drawAsteroid(asteroid.x, asteroid.y);
    });

    ctx.value.restore();
  }
}

function drawStation(x: number, y: number, name: string) {
  if (ctx.value) {
    ctx.value.drawImage(stationImage, x - stationImage.width / 2, y - stationImage.height / 2);

    ctx.value.fillStyle = 'white';
    ctx.value.font = '12px Arial';
    ctx.value.fillText(name, x - 20, y - 45);
  }
}

function drawAsteroid(x: number, y: number) {
  if (ctx.value) {
    ctx.value.drawImage(asteroidImage, x - asteroidImage.width / 2, y - asteroidImage.height / 2);
  }
}

function onMouseDown(e: MouseEvent) {
  isDragging.value = true;
  startDrag.x = e.clientX - pointX.value;
  startDrag.y = e.clientY - pointY.value;
}

function onMouseMove(e: MouseEvent) {
  if (isDragging.value) {
    pointX.value = e.clientX - startDrag.x;
    pointY.value = e.clientY - startDrag.y;
    drawScene();
  }
}

function onMouseUp() {
  isDragging.value = false;
}

function onWheel(e: WheelEvent) {
  e.preventDefault();

  const xs = (e.clientX - pointX.value) / zoomLevel.value;
  const ys = (e.clientY - pointY.value) / zoomLevel.value;

  const delta = e.deltaY < 0 ? 0.1 : -0.1;

  const newZoomLevel = Math.min(Math.max(zoomLevel.value + delta, 0.1), 2);

  pointX.value = e.clientX - xs * newZoomLevel;
  pointY.value = e.clientY - ys * newZoomLevel;

  zoomLevel.value = newZoomLevel;
  drawScene();
}
</script>

<template>
  <AppLayout title="Starmap">
    <canvas ref="canvasRef" class="block w-full bg-[hsl(263,45%,7%)]" @mousedown="onMouseDown" @mousemove="onMouseMove"
      @mouseup="onMouseUp" @wheel="onWheel">
    </canvas>
  </AppLayout>
</template>

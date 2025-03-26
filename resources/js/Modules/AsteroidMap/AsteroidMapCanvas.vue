<script lang="ts" setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Asteroid, Station } from '@/types/types';
import * as config from '@/config';

const props = defineProps<{
  asteroids: Asteroid[];
  stations: Station[];
}>();

const emit = defineEmits<{
  (e: 'mousedown', event: MouseEvent): void;
  (e: 'mousemove', event: MouseEvent): void;
  (e: 'mouseup', event: MouseEvent): void;
  (e: 'wheel', event: WheelEvent): void;
  (e: 'click', event: MouseEvent): void;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const ctx = ref<CanvasRenderingContext2D | null>(null);

const stationImage = new Image();
const asteroidImage = new Image();
const stationImageSrc = '/storage/space-station.png';
const asteroidImageSrc = '/storage/asteroid-light.webp';

const asteroidBaseSize = config.asteroidImageBaseSize;
const stationBaseSize = config.stationImageBaseSize;

// config
const maxOuterZoomLevel = ref(config.maxOuterZoomLevel);
const maxInnerZoomLevel = ref(config.maxInnerZoomLevel);
const zoomLevel = ref(config.baseZoomLevel);
const zoomDelta = ref(config.zoomDelta);
const pointX = ref(0);
const pointY = ref(0);
const startDrag = { x: 0, y: 0 };
const isDragging = ref(false);

const highlightedAsteroids = ref<number[]>([]);
const highlightedStations = ref<number[]>([]);

onMounted(() => {
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

    window.addEventListener('resize', adjustCanvasSize);
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', adjustCanvasSize);
});

function adjustCanvasSize() {
  if (canvasRef.value) {
    canvasRef.value.width = window.innerWidth;
    canvasRef.value.height = window.innerHeight - 125;
    drawScene();
  }
}

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
  return scanRangeAttribute ? scanRangeAttribute.attribute_value : 5000;
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
      highlightObjet(scaledSize, ctx, imageX, imageY);
    }

    ctx.value.drawImage(
      stationImage,
      0, 0,
      stationImage.width, stationImage.height,
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
      highlightObjet(scaledSize, ctx, imageX, imageY);
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

function highlightObjet(size: number, ctx: any, x: number, y: number) {
  const padding = 15 * scale.value;
  const adjustedRadius = size + padding;
  ctx.strokeStyle = 'yellow';
  ctx.lineWidth = 5 * scale.value;
  ctx.beginPath();
  ctx.arc(x, y, adjustedRadius, 0, 2 * Math.PI);
  ctx.stroke();
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

function focusUserStationOnInitialLoad(userId: number) {
  const userStation = props.stations.find(station => station.user_id === userId);
  if (!userStation || !canvasRef.value) return;

  pointX.value = -(userStation.x * config.initialZoom - canvasRef.value.width / 2);
  pointY.value = -(userStation.y * config.initialZoom - canvasRef.value.height / 2);
  zoomLevel.value = config.initialZoom;
}

// event handlers
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

function onClick(event: MouseEvent) {
  emit('click', event);
}
</script>

<template>
  <canvas ref="canvasRef" class="block w-full bg-root" @mousedown="onMouseDown" @mousemove="onMouseMove" @mouseup="onMouseUp" @wheel="onWheel"
    @click="onClick"></canvas>
</template>

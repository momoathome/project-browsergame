<script lang="ts" setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { usePage, useForm, router } from '@inertiajs/vue3';
import Modal from '@/Modules/AsteroidMap/Modal.vue';
import AutoMineModal from '@/Modules/AsteroidMap/AutoMineModal.vue';
import AsteroidMapSearch from '@/Modules/AsteroidMap/AsteroidMapSearch.vue';
import AsteroidMapDropdown from '@/Modules/AsteroidMap/AsteroidMapDropdown.vue';
import AsteroidMapInfluence from '@/Modules/AsteroidMap/AsteroidMapInfluence.vue';
import AppSideOverview from '@/Modules/App/AppSideOverview.vue';
import useAsteroidSearch from '@/Composables/useAsteroidSearch';
import useAnimateView from '@/Composables/useAnimateView';
import useAsteroidMapClick from '@/Composables/useAsteroidMapClick';
import useShipPool from '@/Composables/useShipPool';
import useInfluence from '@/Composables/useInfluence';
import useSidebarOverview from '@/Composables/useSidebarOverview';
import { api } from '@/Services/api';
import { Quadtree } from '@/Utils/quadTree';
import { useQueueStore } from '@/Composables/useQueueStore';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';
import { useBuildingStore } from '@/Composables/useBuildingStore';
import { useAttributeStore } from '@/Composables/useAttributeStore';
import { useResourceStore } from '@/Composables/useResourceStore';
import * as config from '@/config';
import type { Asteroid, Station, Rebel } from '@/types/types';
import {
  asteroidImages,
  rebelImageMap,
  asteroidImageElements,
  calculateVisibleArea,
  isObjectVisible,
} from '@/Utils/asteroidMapHelper';

const props = defineProps<{
  asteroids: Asteroid[];
  stations: Station[];
  rebels: Rebel[];
  influenceOfAllUsers: { user_id: number; attribute_value: string; name: string }[];
}>();

const { queueData, refreshQueue } = useQueueStore();
const { spacecrafts, refreshSpacecrafts } = useSpacecraftStore();
const { buildings, refreshBuildings } = useBuildingStore();
const { userAttributes, refreshAttributes } = useAttributeStore();
const { userResources, refreshResources } = useResourceStore();
const autoMiningUnlocked = ref(false)

// Store immer mit aktuellen Props initialisieren
onMounted(async () => {
  await refreshQueue();
  await refreshSpacecrafts();
  await refreshBuildings();
  await refreshAttributes();
  await refreshResources();

  const hangar = buildings.value.find(b => b.name.toLowerCase() === 'hangar');
  const unlocks = hangar?.effect?.current?.unlock;

  autoMiningUnlocked.value = Array.isArray(unlocks)
  ? unlocks.includes('auto_mining')
  : unlocks === 'auto_mining';
});

const localAsteroids = ref([...props.asteroids]);
const asteroidsWithImages = computed(() =>
  localAsteroids.value.map(asteroid => ({
    ...asteroid,
    imageIndex: asteroid.id % asteroidImages.length
  }))
);

const stationImageSrc = '/images/stations/station2.webp';
const asteroidImageSrc = '/images/asteroids/Asteroid2.webp';

const stationImage = new Image();
const asteroidImage = new Image();

const asteroidBaseSize = config.asteroidImageBaseSize;
const stationBaseSize = config.stationImageBaseSize;
const rebelBaseSize = config.rebelImageBaseSize;

const canvasRef = ref<HTMLCanvasElement | null>(null);
const ctx = ref<CanvasRenderingContext2D | null>(null);

const canvasStaticRef = ref<HTMLCanvasElement | null>(null);
const canvasStaticCtx = ref<CanvasRenderingContext2D | null>(null);

const canvasInfluenceRef = ref<HTMLCanvasElement | null>(null);
const canvasInfluenceCtx = ref<CanvasRenderingContext2D | null>(null);

const asteroidsQuadtree = ref<Quadtree | null>(null);
const stationsQuadtree = ref<Quadtree | null>(null);
const rebelsQuadtree = ref<Quadtree | null>(null);

// config
const maxOuterZoomLevel = ref(config.maxOuterZoomLevel);
const maxInnerZoomLevel = ref(config.maxInnerZoomLevel);
const zoomLevel = ref(config.baseZoomLevel);
const zoomDelta = ref(config.zoomDelta);
const pointX = ref(0);
const pointY = ref(0);
const startDrag = { x: 0, y: 0 };
const isDragging = ref(false);
const moveSpeed = ref(10);
const pendingDraw = ref(false);

const userStation = computed(() => {
  return props.stations.find(station => station.user_id === usePage().props.auth.user.id);
});
const unlockedSpacecrafts = computed(() => {
  return spacecrafts.value.filter(spacecraft => spacecraft.unlocked);
});
const userScanRange = computed(() => {
  const scanRangeAttribute = userAttributes.value.find(
    (attr) => attr.attribute_name === 'scan_range'
  );
  return scanRangeAttribute ? +scanRangeAttribute.attribute_value : 4000;
});

const selectedObject = ref<{ 
  type: 'station' | 'asteroid' | 'rebel'; 
  data: Asteroid | Station | Rebel 
} | null>(null);

const {
  shipPool,
  updateShipPool,
  renderVisibleShips
} = useShipPool(queueData, userStation);

watch(() => queueData.value, () => {
  updateShipPool();
  scheduleDraw();
}, { deep: true });

let frameCounter = 0;
let animationFrameId: number | undefined;

function animateShips() {
  frameCounter++;

  // Aktualisiere nur jeden zweiten Frame (30 FPS statt 60 FPS)
  if (frameCounter % 2 === 0) {
    // Überprüfe, ob Mining-Missionen existieren über den Pool-Status
    if (shipPool.value.size > 0) {
      drawMissionLayer();
    } else {
      // Pool möglicherweise aktualisieren für neue Missionen
      updateShipPool();
      if (shipPool.value.size > 0) {
        drawMissionLayer();
      }
    }
  }

  animationFrameId = requestAnimationFrame(animateShips);
}

// dropdown / selection
const selectedAsteroid = ref<Asteroid>();
function selectAsteroid(asteroid: Asteroid) {
  focusOnObject(asteroid);
  selectedAsteroid.value = asteroid;
}

const { focusOnObject } = useAnimateView(
  pointX,
  pointY,
  zoomLevel,
  drawScene,
  props,
  canvasRef,
  config
);

const {
  searchForm,
  highlightedAsteroids,
  highlightedStations,
  highlightedRebels,
  scanAnimation,
  searchAndFocus,
  clearSearchAndUpdate,
  currentlyHighlightedAsteroidIds,
} = useAsteroidSearch(drawScene, props, userStation, userScanRange, focusOnObject);

onMounted(() => {
  if (canvasRef.value) {
    ctx.value = canvasRef.value.getContext('2d');

    // Initialisiere den Schiffs-Canvas
    if (canvasStaticRef.value) {
      canvasStaticCtx.value = canvasStaticRef.value.getContext('2d');
    }

    if (canvasInfluenceRef.value) {
      canvasInfluenceCtx.value = canvasInfluenceRef.value.getContext('2d');
    }

    adjustCanvasSize();

    animationFrameId = requestAnimationFrame(animateShips);

    stationImage.src = stationImageSrc;
    asteroidImage.src = asteroidImageSrc;

    stationImage.onload = asteroidImage.onload = () => {
      focusUserStationOnInitialLoad();
      drawScene();
    };
  }

  initQuadtree();

  window.addEventListener('resize', adjustCanvasSize);
  /*   window.addEventListener('keydown', onKeyDown); */

  window.Echo.channel('canvas')
    .listen('.reload.canvas', (data) => {
      if (Array.isArray(data.mined_asteroids)) {
        // delete from asteroidsQuadtree
        console.log('Deleting asteroid from quadtree:', data.mined_asteroids);
        data.mined_asteroids.forEach(asteroid => {
          asteroidsQuadtree.value?.remove({ x: asteroid.x, y: asteroid.y });
          localAsteroids.value = localAsteroids.value.filter(a => a.id !== asteroid.id);
        });
      }
      // Neue Asteroiden hinzufügen
      if (Array.isArray(data.new_asteroids)) {
        console.log('Adding new asteroids to quadtree:', data.new_asteroids);
        data.new_asteroids.forEach(newAsteroid => {
          asteroidsQuadtree.value?.insert({ x: newAsteroid.x, y: newAsteroid.y, data: newAsteroid });
          localAsteroids.value.push(newAsteroid);
        });
      }
      drawScene();
    })
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', adjustCanvasSize);
  /*   window.removeEventListener('keydown', onKeyDown); */

  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
  }
});

function initQuadtree() {
  const universeBounds = { x: config.size / 2, y: config.size / 2, width: config.size, height: config.size };
  asteroidsQuadtree.value = new Quadtree(universeBounds);
  stationsQuadtree.value = new Quadtree(universeBounds);
  rebelsQuadtree.value = new Quadtree(universeBounds);

  // Füge Asteroiden und Stationen zum Quadtree hinzu
  props.asteroids.forEach(asteroid => {
    asteroidsQuadtree.value?.insert({ x: asteroid.x, y: asteroid.y, data: asteroid });
  });

  props.stations.forEach(station => {
    stationsQuadtree.value?.insert({ x: station.x, y: station.y, data: station });
  });

  props.rebels.forEach(rebel => {
    rebelsQuadtree.value?.insert({ x: rebel.x, y: rebel.y, data: rebel });
  });
}

function focusUserStationOnInitialLoad() {
  if (!userStation.value || !canvasRef.value) return;

  pointX.value = -(userStation.value.x * config.initialZoom - canvasRef.value.width / 2);
  pointY.value = -(userStation.value.y * config.initialZoom - canvasRef.value.height / 2);
  zoomLevel.value = config.initialZoom;
}
function adjustStaticCanvasSize() {
  if (canvasStaticRef.value && canvasStaticCtx.value) {
    canvasStaticRef.value.width = window.innerWidth;
    canvasStaticRef.value.height = window.innerHeight - 20;
  }
}
function adjustInfluenceCanvasSize() {
  if (canvasInfluenceRef.value && canvasInfluenceCtx.value) {
    canvasInfluenceRef.value.width = window.innerWidth;
    canvasInfluenceRef.value.height = window.innerHeight - 20;
  }
}
function adjustCanvasSize() {
  if (canvasRef.value && ctx.value) {
    canvasRef.value.width = window.innerWidth;
    canvasRef.value.height = window.innerHeight - 20;

    adjustStaticCanvasSize();
    adjustInfluenceCanvasSize();
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

  const visibleArea = calculateVisibleArea(width, height, pointX, pointY, zoomLevel);

  drawUserScanRange();
  drawScanWave();
  drawFlightPaths();
  drawStationsAndAsteroids(visibleArea);
  drawMissionLayer();
  if (showInfluence.value) {
    drawInfluenceLayer();
  }

  ctx.value.restore();
}

function scheduleDraw() {
  if (!pendingDraw.value) {
    pendingDraw.value = true;
    requestAnimationFrame(() => {
      drawScene();
      pendingDraw.value = false;
    });
  }
}

function drawUserScanRange() {
  if (showInfluence.value) return;

  if (userStation.value && ctx.value) {
    ctx.value.beginPath();
    ctx.value.arc(userStation.value.x, userStation.value.y, userScanRange.value, 0, 2 * Math.PI);
    ctx.value.fillStyle = 'rgba(36, 36, 36, 0.2)';
    ctx.value.fill();
    ctx.value.stroke();
  }
}

function drawScanWave() {
  if (scanAnimation.value.active && userStation.value && ctx.value) {
    ctx.value.beginPath();
    ctx.value.arc(
      userStation.value.x,
      userStation.value.y,
      scanAnimation.value.radius,
      0,
      2 * Math.PI
    );
    ctx.value.fillStyle = 'rgba(0,255,255,0.025)';
    ctx.value.fill();
    ctx.value.strokeStyle = 'rgba(0,255,255,0.5)';
    ctx.value.lineWidth = 8;
    ctx.value.stroke();
  }
}

function drawStationsAndAsteroids(visibleArea: { left: number; top: number; right: number; bottom: number }) {
  if (!asteroidsQuadtree.value || !stationsQuadtree.value || !rebelsQuadtree.value) return;

  const queryRange = {
    x: (visibleArea.left + visibleArea.right) / 2,
    y: (visibleArea.top + visibleArea.bottom) / 2,
    width: (visibleArea.right - visibleArea.left) / 2,
    height: (visibleArea.bottom - visibleArea.top) / 2
  };

  // Nur sichtbare Stationen rendern
  const potentiallyVisibleStations = stationsQuadtree.value.query(queryRange);
  potentiallyVisibleStations.forEach(item => {
    const station = item.data;
    if (isObjectVisible(station, visibleArea, stationBaseSize, scale)) {
      drawStation(station.x, station.y, station.name, station.id);
    }
  });

  // Nur sichtbare Asteroiden rendern
  const potentiallyVisibleAsteroids = asteroidsQuadtree.value.query(queryRange);
  potentiallyVisibleAsteroids.forEach(item => {
    const asteroid = item.data;
    if (isObjectVisible(asteroid, visibleArea, asteroidBaseSize, scale)) {
      drawAsteroid(asteroid.x, asteroid.y, asteroid.id, asteroid.pixel_size);
    }
  });

  // Nur sichtbare Rebellen rendern
  const potentiallyVisibleRebels = rebelsQuadtree.value.query(queryRange);
  potentiallyVisibleRebels.forEach(item => {
    const rebel = item.data;
    if (isObjectVisible(rebel, visibleArea, rebelBaseSize, scale)) {
      drawRebel(rebel.x, rebel.y, rebel.name, rebel.id, rebel.faction);
    }
  });
}

const scale = computed(() => {
  const minZoom = config.maxOuterZoomLevel;
  const maxZoom = config.maxInnerZoomLevel;
  const normalizedZoom = (zoomLevel.value - minZoom) / (maxZoom - minZoom);
  return 1 + Math.pow(1 - normalizedZoom, 2) * 1;
});

function drawStation(x: number, y: number, name: string, id: number) {
  if (ctx.value) {
    const scaledSize = stationBaseSize * scale.value;
    const imageX = x - (scaledSize / 2);
    const imageY = y - (scaledSize / 2);

    if (highlightedStations.value.includes(id)) {
      const isFocused = selectedObject.value?.type === 'station' && selectedObject.value.data.id === id;
      drawHighlight(x, y, scaledSize, 'station', isFocused);
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
      // Sanfte Skalierung: z.B. exponentiell
      const zoomBoost = Math.max(1, 0.2 / zoomLevel.value);
      ctx.font = `${config.stationNameFontSize * scale.value * zoomBoost}px Arial`;
      const textWidth = ctx.measureText(name).width;
      const textX = x - textWidth / 2;
      const textY = y - scaledSize / 2 - 24 * scale.value * zoomBoost;
      ctx.fillText(name, textX, textY);
    }

    drawStationName(ctx.value);
  }
}

function drawAsteroid(x: number, y: number, id: number, size: number) {
  if (ctx.value) {
    const asteroid = asteroidsWithImages.value.find(a => a.id === id);
    const imageIndex = asteroid?.imageIndex ?? 0;
    const asteroidImg = asteroidImageElements[imageIndex] ?? asteroidImageElements[0];

    const scaledSize = (asteroidBaseSize * size) * scale.value;
    const imageX = x - (scaledSize / 2);
    const imageY = y - (scaledSize / 2);

    if (currentlyHighlightedAsteroidIds.value.includes(id)) {
      const isFocused = selectedAsteroid.value?.id === id;
      drawHighlight(x, y, scaledSize, 'asteroid', isFocused);
    }

    if (asteroidImg) {
      ctx.value.drawImage(
        asteroidImg,
        0, 0,
        asteroidImg.width,
        asteroidImg.height,
        imageX, imageY,
        scaledSize, scaledSize
      );
    }
  }
}

function drawRebel(x: number, y: number, name: string, id: number, faction: string) {
  if (ctx.value) {
    const scaledSize = rebelBaseSize * scale.value;
    const imageX = x - (scaledSize / 2);
    const imageY = y - (scaledSize / 2);

    const rebelImage = rebelImageMap[faction] ?? rebelImageMap['Rostwölfe'];

    if (highlightedRebels.value.includes(id)) {
      const isFocused = selectedObject.value?.type === 'rebel' && selectedObject.value.data.id === id;
      drawHighlight(x, y, scaledSize, 'rebel', isFocused);
    }

    if (rebelImage) {
      ctx.value.drawImage(
        rebelImage,
        0, 0,
        rebelImage.width,
        rebelImage.height,
        imageX, imageY,
        scaledSize, scaledSize
      );
    }

    const rebelColors = {
      'Rostwölfe': 'IndianRed',
      'Kult der Leere': 'BlueViolet',
      'Sternenplünderer': 'SteelBlue',
      'Gravbrecher': 'GreenYellow',
    };

    function drawRebelName(ctx) {
      ctx.fillStyle = rebelColors[faction] || 'white';
      const zoomBoost = Math.max(1, 0.15 / zoomLevel.value);
      ctx.font = `${config.stationNameFontSize * scale.value * zoomBoost}px Arial`;
      const textWidth = ctx.measureText(name).width;
      const textX = x - textWidth / 2;
      const textY = y - scaledSize / 2 - 24 * scale.value * zoomBoost;
      ctx.fillText(name, textX, textY);
    }

    drawRebelName(ctx.value);
  }
}

function drawHighlight(
  x: number,
  y: number,
  scaledSize: number,
  type: 'station' | 'asteroid' | 'rebel' = 'asteroid',
  isFocused: boolean = false
) {
  if (!ctx.value) return;

  const padding = 6 * scale.value;
  const adjustedRadius = scaledSize + padding;
  const tintColor = isFocused
    ? 'rgba(255, 80, 80, 0.15)'
    : 'rgba(0, 255, 255, 0.15)';

  // 1. Farb-Tint als gefüllter Kreis
  ctx.value.save();
  ctx.value.beginPath();
  ctx.value.arc(x, y, adjustedRadius - 2, 0, 2 * Math.PI);
  ctx.value.fillStyle = tintColor;
  ctx.value.globalAlpha = 1;
  ctx.value.fill();
  ctx.value.restore();
}

function drawFlightPaths() {
  if (!userStation.value || !ctx.value) return;

  const context = ctx.value;
  const queueItems = queueData.value || [];

  // Sammle alle relevanten Missionen
  const missions = queueItems.filter(item =>
    (item.actionType === 'mining' || item.actionType === 'combat') &&
    item.details?.target_coordinates
  );

  if (missions.length === 0) return;

  context.setLineDash([10 * scale.value * 4, 12 * scale.value * 4]);
  context.lineWidth = 2 * scale.value;

  // Zeichne Missionslinien
  missions.forEach(mission => {
    const targetCoords = mission.details.target_coordinates;
    const attackerCoords = mission.details.attacker_coordinates;

    // Prüfe, ob das Ziel die eigene Station ist (Angriff auf mich)
    const isAttackOnMe = mission.actionType === 'combat'
      && mission.targetId === usePage().props.auth.user.id;

    // Setze Farbe je nach Missionstyp und Ziel
    context.strokeStyle = isAttackOnMe
      ? 'rgba(255, 0, 0, 0.7)' // rot für Angriffe auf mich
      : mission.actionType === 'combat'
        ? 'rgba(0, 255, 255, 0.4)' // cyan für andere Kampfmissionen
        : 'rgba(255, 255, 255, 0.2)'; // weiß für Mining-Missionen

    context.beginPath();

    if (isAttackOnMe && attackerCoords) {
      context.moveTo(attackerCoords.x, attackerCoords.y);
      context.lineTo(targetCoords.x, targetCoords.y);
    } else {
      context.moveTo(userStation.value!.x, userStation.value!.y);
      context.lineTo(targetCoords.x, targetCoords.y);
    }

    context.stroke();
  });

  context.setLineDash([]);
}

function drawMissionLayer() {
  if (!canvasStaticCtx.value || !canvasStaticRef.value) return;

  updateShipPool();

  if (shipPool.value.size === 0) return;

  const { width, height } = canvasStaticRef.value;
  const ctx = canvasStaticCtx.value;
  const currentScale = scale.value;

  // Canvas leeren
  ctx.clearRect(0, 0, width, height);

  // Transformationen anwenden
  ctx.save();
  ctx.translate(pointX.value, pointY.value);
  ctx.scale(zoomLevel.value, zoomLevel.value);

  const visibleArea = calculateVisibleArea(width, height, pointX, pointY, zoomLevel);

  // Textformatierung einstellen
  ctx.fillStyle = 'white';
  ctx.font = `${20 * currentScale}px Arial`;

  // Nur sichtbare Schiffe rendern
  renderVisibleShips(ctx, visibleArea, currentScale);

  ctx.restore();
}

function drawInfluenceLayer() {
  if (!canvasInfluenceCtx.value) return;
  const ctx = canvasInfluenceCtx.value;
  ctx.clearRect(0, 0, canvasInfluenceRef.value!.width, canvasInfluenceRef.value!.height);

  if (!showInfluence.value) return;

  ctx.save();
  ctx.translate(pointX.value, pointY.value);
  ctx.scale(zoomLevel.value, zoomLevel.value);

  const influenceScale = 25;

  playerInfluences.value.forEach(player => {
    const radius = Math.max(20, Math.sqrt(player.influence) * influenceScale);
    ctx.beginPath();
    ctx.arc(player.station.x, player.station.y, radius, 0, 2 * Math.PI);
    ctx.fillStyle = getInfluenceColor(player.userId);
    ctx.fill();
  });

  ctx.restore();
}

const {
  getClickCoordinates,
  findClickedAsteroid,
  findClickedStation,
  findClickedRebel,
  onMouseDown,
  onMouseUp,
  onMouseMove,
} = useAsteroidMapClick(
  canvasRef,
  ctx,
  pointX,
  pointY,
  zoomLevel,
  scale,
  asteroidBaseSize,
  stationBaseSize,
  rebelBaseSize,
  props,
  startDrag,
  isDragging,
  scheduleDraw
);

function handleCoordinateDisplay(coords: {x: number, y: number}, e: MouseEvent) {
  console.log(`Koordinaten: x=${Math.round(coords.x)}, y=${Math.round(coords.y)}`);
  // showCoordinatesOverlay(coords.x, coords.y);
  e.stopPropagation();
  return true;
}

function handleClickedObject(clickedObject: { type: 'station' | 'asteroid' | 'rebel'; data: Station | Asteroid | Rebel }) {
  const isOtherUserStation = clickedObject.type === 'station' &&
    'user_id' in clickedObject.data &&
    clickedObject.data.user_id !== usePage().props.auth.user.id;

  if (isOtherUserStation || clickedObject.type === 'rebel') {
    selectedObject.value = clickedObject;
    isModalOpen.value = true;
  } else if (clickedObject.type === 'asteroid') {
    getAsteroidResources(clickedObject.data as Asteroid);
  }
}

async function getAsteroidResources(asteroid: Asteroid) {
  const { data, error } = await api.asteroids.getResources(asteroid.id);

  if (!error) {
    selectedObject.value = {
      data: data.asteroid,
      type: 'asteroid',
    }
    isModalOpen.value = true;
  } else {
    console.error('Fehler beim Abrufen der Asteroidenressourcen:', error);
  }
}

function onMouseClick(e: MouseEvent) {
  const coords = getClickCoordinates(e);
  if (!coords) return;

  if (e.shiftKey && handleCoordinateDisplay(coords, e)) return;

  const clickedStation = findClickedStation(coords);
  if (clickedStation) {
    handleClickedObject({ type: 'station', data: clickedStation });
    return;
  }

  const clickedRebel = findClickedRebel(coords);
  if (clickedRebel) {
    handleClickedObject({ type: 'rebel', data: clickedRebel });
    return;
  }

  const clickedAsteroid = findClickedAsteroid(coords);
  if (clickedAsteroid) {
    handleClickedObject({ type: 'asteroid', data: clickedAsteroid });
    return;
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

const isAutoMineModalOpen = ref(false);
const isModalOpen = ref(false)

function closeModal() {
  isModalOpen.value = false;
  setTimeout(() => {
    selectedObject.value = null;
  }, 300);
}
function closeAutoMineModal() {
  isAutoMineModalOpen.value = false;
}

const activeSidebar = ref<'influence' | 'overview' | null>(null);

const {
  showInfluence,
  showInfluenceSidebar,
  playerInfluences,
  getInfluenceColor,
  toggleInfluence,
  openInfluenceSidebar,
  closeInfluenceSidebar,
} = useInfluence(props, props.stations, activeSidebar, drawInfluenceLayer, scheduleDraw);

const {
  showSidebarOverview,
  openSidebarOverview,
  closeSidebarOverview,
} = useSidebarOverview(activeSidebar);
</script>

<template>
  <div class="relative overflow-hidden" @click.prevent="">
    <canvas ref="canvasRef" class="block w-full bg-[hsl(263,45%,7%)]" @mousedown="onMouseDown" @mousemove="onMouseMove"
      @mouseup="onMouseUp" @wheel="onWheel" @click="onMouseClick">
    </canvas>
    <canvas ref="canvasStaticRef" class="block w-full absolute top-0 left-0 pointer-events-none">
    </canvas>
    <canvas ref="canvasInfluenceRef" class="block w-full absolute top-0 left-0 pointer-events-none">
    </canvas>

    <div class="absolute top-2 left-0 z-100 flex gap-2 ms-4 bg-[hsl(263,45%,7%)]">
      <AsteroidMapSearch v-model="searchForm.query" @clear="clearSearchAndUpdate" @search="searchAndFocus" />
    </div>

    <AsteroidMapDropdown v-if="highlightedAsteroids && highlightedAsteroids.length > 0"
      class="absolute top-2 left-64 ms-2 w-44" :searched-asteroids="highlightedAsteroids"
      :selected-asteroid="selectedAsteroid" @select-asteroid="selectAsteroid" />

    <button type="button" v-if="autoMiningUnlocked"
      class="absolute top-2 left-64 ms-2 text-light bg-[hsl(263,45%,7%)] hover:bg-[hsl(263,20%,8%)] ring-[#bfbfbf] border border-[#6b7280] px-4 py-2 rounded-lg transition-transform duration-200"
      :class="{ 'translate-x-48 -ms-0': highlightedAsteroids && highlightedAsteroids.length > 0 }"
      @click="isAutoMineModalOpen = true">
      auto mine
    </button>

    <div class="absolute top-2 right-2 z-100 flex min-w-28 transition-all duration-300"
      :class="{ 'right-[18vw] 3xl:right-[12vw] me-2': showInfluenceSidebar || showSidebarOverview }">
      <span class="text-light px-3 py-2 rounded-lg transition-transform duration-200">
        zoom: {{ Math.round(zoomLevel * 1000 / 5) }}%
      </span>
      <button type="button"
        class="relative z-10 text-light bg-[hsl(263,45%,7%)] hover:bg-[hsl(263,20%,8%)] ring-[#bfbfbf] border border-[#6b7280] px-4 py-2 rounded-lg transition-transform duration-200"
        @click="focusOnObject(null, usePage().props.auth.user.id)">
        reset
      </button>
    </div>

    <!-- Influence Toggle Button -->
    <button
      class="fixed top-48 right-0 z-[40] h-16 w-16 px-3 flex items-center justify-center border border-r-0 border-[#6b7280]/40 bg-root text-white rounded-l-md hover:bg-[hsl(217,24%,6%)] transition duration-300"
      :class="(showInfluenceSidebar || showSidebarOverview) ? 'transformed-button' : ''"
      @click="[showInfluenceSidebar ? closeInfluenceSidebar() : openInfluenceSidebar(), toggleInfluence()]"
    >
      <img v-show="!showInfluenceSidebar" src="/images/attributes/influence.png" alt="Toggle Influence" class="h-8 w-8" />
      <svg v-show="showInfluenceSidebar" xmlns="http://www.w3.org/2000/svg" width="32" height="32" class="text-slate-200" viewBox="0 0 24 24">
        <path fill="currentColor" d="M16.95 8.464a1 1 0 0 0-1.414-1.414L12 10.586L8.464 7.05A1 1 0 1 0 7.05 8.464L10.586 12L7.05 15.536a1 1 0 1 0 1.414 1.414L12 13.414l3.536 3.536a1 1 0 1 0 1.414-1.414L13.414 12z"/>
      </svg>
    </button>

    <!-- Overview Toggle Button -->
    <button
      class="fixed top-[272px] right-0 z-[40] h-16 w-16 px-3 flex items-center justify-center border border-r-0 border-[#6b7280]/40 bg-root text-white rounded-l-md hover:bg-[hsl(217,24%,6%)] transition duration-300"
      :class="(showSidebarOverview || showInfluenceSidebar) ? 'transformed-button' : ''"
      @click="showSidebarOverview ? closeSidebarOverview() : openSidebarOverview()"
    >
      <img v-show="!showSidebarOverview" src="/images/navigation/overview.png" alt="Toggle overview" class="h-8 w-8" />
      <svg v-show="showSidebarOverview" xmlns="http://www.w3.org/2000/svg" width="32" height="32" class="text-slate-200" viewBox="0 0 24 24">
        <path fill="currentColor" d="M16.95 8.464a1 1 0 0 0-1.414-1.414L12 10.586L8.464 7.05A1 1 0 1 0 7.05 8.464L10.586 12L7.05 15.536a1 1 0 1 0 1.414 1.414L12 13.414l3.536 3.536a1 1 0 1 0 1.414-1.414L13.414 12z"/>
      </svg>
    </button>

    <!-- Sidebar -->
    <AsteroidMapInfluence
      :influence-of-all-users="props.influenceOfAllUsers"
      :show="showInfluenceSidebar"
      @toggle="showInfluenceSidebar ? closeInfluenceSidebar() : openInfluenceSidebar()"
      @focus-player="focusOnObject(null, $event)"
      :style="{
        zIndex: activeSidebar === 'influence' ? 30 : 20
      }"
    />

    <!-- Sidebar Overview -->
    <div
      class="fixed right-0 top-[56px] h-[calc(100vh-56px)] flex transition-transform duration-300"
      :style="[
        showSidebarOverview ? 'transform: translateX(0)' : 'transform: translateX(100%)',
        { zIndex: activeSidebar === 'overview' ? 30 : 20 }
      ]"
    >
      <AppSideOverview />
    </div>

  </div>

  <Modal :content="{
    type: selectedObject?.type,
    data: selectedObject?.data as Asteroid | Station | Rebel,
    title: selectedObject?.data?.name,
  }" :open="isModalOpen" @close="closeModal" :spacecrafts="unlockedSpacecrafts" :user-scan-range="userScanRange"
    @redraw="drawScene" />

  <AutoMineModal :open="isAutoMineModalOpen" @close="closeAutoMineModal" @redraw="drawScene" />
</template>

<style scoped>
.transformed-button {
  transform: translateX(-16vw);
}

@media (max-width: 1700px) {
  .transformed-button {
    transform: translateX(-18vw);
  }
}

@media (min-width: 2160px) {
  .transformed-button {
    transform: translateX(-12vw);
  }
}

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

.fancy-scroll::-webkit-scrollbar { width: 6px; }
.fancy-scroll::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
}
</style>

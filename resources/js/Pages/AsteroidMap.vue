<script lang="ts" setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Modules/AsteroidMap/Modal.vue';
import AsteroidMapSearch from '@/Modules/AsteroidMap/AsteroidMapSearch.vue';
import AsteroidMapDropdown from '@/Modules/AsteroidMap/AsteroidMapDropdown.vue';
import useAsteroidSearch from '@/Composables/useAsteroidSearch';
import useAnimateView from '@/Composables/useAnimateView';
import { api } from '@/Services/api';
import type { Asteroid, Station, Spacecraft, ShipRenderObject, QueueItem, SavedQueueItemState, SpacecraftFleet, MiningMissionDetails, MissionDetails } from '@/types/types';
import { Quadtree } from '@/Utils/quadTree';
import * as config from '@/config';

const props = defineProps<{
  asteroids: Asteroid[];
  stations: Station[];
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

const canvasStaticRef = ref<HTMLCanvasElement | null>(null);
const canvasStaticCtx = ref<CanvasRenderingContext2D | null>(null);

const asteroidsQuadtree = ref<Quadtree | null>(null);
const stationsQuadtree = ref<Quadtree | null>(null);

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

// static values
const userStation = computed(() => {
  const userId = usePage().props.auth.user.id;
  return props.stations.find(station => station.user_id === userId);
});

const selectedObject = ref<{ type: 'station' | 'asteroid'; data: Asteroid | Station } | null>(null);
const shipPool = ref<Map<number, ShipRenderObject>>(new Map());

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

const {
  searchForm,
  performSearch,
  clearSearch,
  highlightedAsteroids,
  highlightedStations,
} = useAsteroidSearch(drawScene);

onMounted(() => {
  if (canvasRef.value) {
    ctx.value = canvasRef.value.getContext('2d');

    // Initialisiere den Schiffs-Canvas
    if (canvasStaticRef.value) {
      canvasStaticCtx.value = canvasStaticRef.value.getContext('2d');
      adjustStaticCanvasSize();
    }

    adjustCanvasSize();

    animationFrameId = requestAnimationFrame(animateShips);

    stationImage.src = stationImageSrc;
    asteroidImage.src = asteroidImageSrc;

    stationImage.onload = asteroidImage.onload = () => {
      drawScene();
      const userId = usePage().props.auth.user.id;
      focusUserStationOnInitialLoad(userId);
    };
  }

  initQuadtree();
  window.addEventListener('resize', adjustCanvasSize);
  window.addEventListener('keydown', onKeyDown);

  window.Echo.channel('canvas')
    .listen('.reload.canvas', (data) => {
      const asteroidData = data.asteroid
      if (asteroidData.resources.length === 0) {
        // delete from asteroidsQuadtree
        asteroidsQuadtree.value?.remove({ x: asteroidData.x, y: asteroidData.y });
      }
      drawScene();
    })
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', adjustCanvasSize);
  window.removeEventListener('keydown', onKeyDown);

  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
  }
});

function initQuadtree() {
  const universeBounds = { x: config.size / 2, y: config.size / 2, width: config.size / 2, height: config.size / 2 };
  asteroidsQuadtree.value = new Quadtree(universeBounds);
  stationsQuadtree.value = new Quadtree(universeBounds);

  // Füge Asteroiden und Stationen zum Quadtree hinzu
  props.asteroids.forEach(asteroid => {
    asteroidsQuadtree.value?.insert({ x: asteroid.x, y: asteroid.y, data: asteroid });
  });

  props.stations.forEach(station => {
    stationsQuadtree.value?.insert({ x: station.x, y: station.y, data: station });
  });
}

function focusUserStationOnInitialLoad(userId: number) {
  if (!userStation.value || !canvasRef.value) return;

  pointX.value = -(userStation.value.x * config.initialZoom - canvasRef.value.width / 2);
  pointY.value = -(userStation.value.y * config.initialZoom - canvasRef.value.height / 2);
  zoomLevel.value = config.initialZoom;
}

function adjustStaticCanvasSize() {
  if (canvasStaticRef.value && canvasStaticCtx.value) {
    canvasStaticRef.value.width = window.innerWidth;
    canvasStaticRef.value.height = window.innerHeight - 72;
  }
}
function adjustCanvasSize() {
  if (canvasRef.value && ctx.value) {
    canvasRef.value.width = window.innerWidth;
    canvasRef.value.height = window.innerHeight - 72;

    adjustStaticCanvasSize();
    drawScene();
    drawMissionLayer();
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

  drawUserScanRange();
  drawFlightPaths();
  drawStationsAndAsteroids(visibleArea);

  ctx.value.restore();
}

function scheduleDraw() {
  if (!pendingDraw.value) {
    pendingDraw.value = true;
    requestAnimationFrame(() => {
      drawScene();
      drawMissionLayer();
      pendingDraw.value = false;
    });
  }
}

const userScanRange = computed(() => {
  const scanRangeAttribute = usePage().props.userAttributes.find(
    (attr) => attr.attribute_name === 'scan_range'
  );
  return scanRangeAttribute ? +scanRangeAttribute.attribute_value : 5000;
});

function drawUserScanRange() {
  if (userStation.value && ctx.value) {
    ctx.value.beginPath();
    ctx.value.arc(userStation.value.x, userStation.value.y, userScanRange.value, 0, 2 * Math.PI);
    ctx.value.fillStyle = 'rgba(36, 36, 36, 0.2)';
    ctx.value.fill();
    ctx.value.stroke();
  }
}

function isObjectVisible(object: { x: number; y: number; pixel_size?: number }, visibleArea: { left: number; top: number; right: number; bottom: number }) {
  // Berechne den Abstand zum sichtbaren Bereich basierend auf der Pixelgröße
  const buffer = object.pixel_size ? object.pixel_size * asteroidBaseSize * scale.value : 100;

  if (object.x < visibleArea.left - buffer ||
    object.x > visibleArea.right + buffer ||
    object.y < visibleArea.top - buffer ||
    object.y > visibleArea.bottom + buffer) {
    return false;
  }

  return true;
}

function drawStationsAndAsteroids(visibleArea: { left: number; top: number; right: number; bottom: number }) {
  if (!asteroidsQuadtree.value || !stationsQuadtree.value) return;

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
    // Nutze isObjectVisible für eine präzisere Sichtbarkeitsbestimmung
    if (isObjectVisible(station, visibleArea)) {
      drawStation(station.x, station.y, station.name, station.id);
    }
  });

  // Nur sichtbare Asteroiden rendern
  const potentiallyVisibleAsteroids = asteroidsQuadtree.value.query(queryRange);
  potentiallyVisibleAsteroids.forEach(item => {
    const asteroid = item.data;
    // Nutze isObjectVisible für eine präzisere Sichtbarkeitsbestimmung
    if (isObjectVisible(asteroid, visibleArea)) {
      drawAsteroid(asteroid.x, asteroid.y, asteroid.id, asteroid.pixel_size);
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

    if (highlightedAsteroids.value.length > 0) {
      const highlightedAsteroidIds = new Set(highlightedAsteroids.value.map(a => a.id));
      const isFocused = selectedAsteroid.value?.id === id;
      if (highlightedAsteroidIds.has(id)) {
        drawHighlight(x, y, scaledSize, 'asteroid', isFocused);
      }
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

function drawHighlight(x: number, y: number, scaledSize: number, type: 'station' | 'asteroid' = 'asteroid', isFocused: boolean = false) {
  if (!ctx.value) return;

  const padding = 10 * scale.value;
  const adjustedRadius = scaledSize + padding;

  // Farbe basierend auf Status (fokussiert oder hervorgehoben)
  ctx.value.strokeStyle = isFocused ? 'red' : 'yellow';
  ctx.value.lineWidth = 3 * scale.value;
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
    scheduleDraw();
  }
}
function updateShipPool() {
  const queueItems = usePage().props.queue || [];
  const currentTime = new Date().getTime();
  const activeMissionIds = new Set<number>();

  // 1. Sammle alle relevanten Missionen
  const missions = queueItems.filter((item: QueueItem) => 
    (item.actionType === 'mining' || item.actionType === 'combat') && 
    item.details?.target_coordinates !== undefined
  );

  // 2. Neue Missionen hinzufügen und bestehende aktualisieren
  if (missions.length > 0 && userStation.value) {
    missions.forEach(mission => {
      const missionId = mission.id;
      const missionType = mission.actionType as 'mining' | 'combat';
      activeMissionIds.add(missionId);

      // Nur neue Missionen initialisieren
      if (!shipPool.value.has(missionId)) {
        initializeNewMission(mission, missionId, missionType);
      }
    });
  }

  // 3. Bestehende Missionen aktualisieren oder entfernen
  for (const missionId of shipPool.value.keys()) {
    if (!activeMissionIds.has(missionId)) {
      shipPool.value.delete(missionId);
    } else {
      updateMissionPosition(missionId, currentTime);
    }
  }
}

function initializeNewMission(mission: QueueItem, missionId: number, missionType: 'mining' | 'combat') {
  const targetCoords = mission.details.target_coordinates;
  const startTime = new Date(mission.startTime).getTime();
  const endTime = new Date(mission.endTime).getTime();

  // Zähle die Gesamtzahl der Schiffe basierend auf dem Missionstyp
  let totalShips = 0;
  
  if (missionType === 'mining') {
    const spacecrafts: SpacecraftFleet = mission.details.spacecrafts || {};
    totalShips = Object.values(spacecrafts).reduce(
      (sum, count) => sum + Number(count), 0
    );
  } else { // combat
    const attackerSpacecrafts = mission.details.attacker_formatted || [];
    totalShips = attackerSpacecrafts.reduce(
      (sum, spacecraft) => sum + Number(spacecraft.count), 0
    );
  }

  // Ermittle den Zielnamen je nach Missionstyp
  const targetName = missionType === 'mining' 
    ? mission.details.asteroid_name || '' 
    : mission.details.defender_name || 'Gegner';

  // Erstelle neues Objekt im Pool
  shipPool.value.set(missionId, {
    shipX: userStation.value!.x,
    shipY: userStation.value!.y,
    exactX: userStation.value!.x,
    exactY: userStation.value!.y,
    missionId,
    targetName,
    totalShips,
    targetX: targetCoords!.x,
    targetY: targetCoords!.y,
    startX: userStation.value!.x,
    startY: userStation.value!.y,
    startTime,
    endTime,
    completed: false,
    textOffsetY: -25,
    missionType
  });
}

function updateMissionPosition(missionId, currentTime) {
  const shipObject = shipPool.value.get(missionId);
  if (!shipObject) return;

  const totalDuration = shipObject.endTime - shipObject.startTime;
  const elapsedDuration = currentTime - shipObject.startTime;
  const progressPercent = Math.min(Math.max(elapsedDuration / totalDuration, 0), 1);

  // Wenn die Mission abgeschlossen ist (100% Fortschritt)
  if (progressPercent >= 1) {
    if (currentTime >= shipObject.endTime + 1000) {
      shipPool.value.delete(missionId);
      return;
    } else {
      // Sicherstelle, dass Schiffe exakt am Ziel sind
      shipObject.exactX = shipObject.targetX;
      shipObject.exactY = shipObject.targetY;
      shipObject.shipX = shipObject.targetX;
      shipObject.shipY = shipObject.targetY;
      shipObject.completed = true;
    }
  } else {
    // Aktualisiere Position während des Fluges
    shipObject.exactX = shipObject.startX + (shipObject.targetX - shipObject.startX) * progressPercent;
    shipObject.exactY = shipObject.startY + (shipObject.targetY - shipObject.startY) * progressPercent;
    shipObject.shipX = shipObject.exactX;
    shipObject.shipY = shipObject.exactY;
  }
}

function drawFlightPaths() {
  if (!userStation.value || !ctx.value) return;

  const context = ctx.value;
  const queueItems = usePage().props.queue || [];
  
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
    
    // Setze Farbe je nach Missionstyp
    context.strokeStyle = mission.actionType === 'combat' 
      ? 'rgba(0, 255, 255, 0.4)' // cyan für Kampfmissionen
      : 'rgba(255, 255, 255, 0.4)'; // weiß für Mining-Missionen
    
    context.beginPath();
    // Zeichne Linie von Station zum Ziel
    context.moveTo(userStation.value!.x, userStation.value!.y);
    context.lineTo(targetCoords.x, targetCoords.y);
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

  // Sichtbaren Bereich berechnen
  const visibleArea = calculateVisibleArea(width, height);

  // Textformatierung einstellen
  ctx.fillStyle = 'white';
  ctx.font = `${20 * currentScale}px Arial`;

  // Nur sichtbare Schiffe rendern
  renderVisibleShips(ctx, visibleArea, currentScale);

  ctx.restore();
}

function calculateVisibleArea(width, height) {
  return {
    left: -pointX.value / zoomLevel.value,
    top: -pointY.value / zoomLevel.value,
    right: (width - pointX.value) / zoomLevel.value,
    bottom: (height - pointY.value) / zoomLevel.value,
  };
}

function renderVisibleShips(ctx, visibleArea, currentScale) {
  const buffer = 50 * currentScale;

  for (const ship of shipPool.value.values()) {
    // Prüfe, ob das Schiff im sichtbaren Bereich liegt
    if (!isShipVisible(ship, visibleArea, buffer)) continue;

    // Nur nicht abgeschlossene Schiffe zeichnen
    if (!ship.completed) {
      // Zeichne das Schiff
      drawShip(ctx, ship, currentScale);
      drawShipLabel(ctx, ship, currentScale);
    }
  }
}

function isShipVisible(ship, visibleArea, buffer) {
  return ship.shipX >= visibleArea.left - buffer &&
    ship.shipX <= visibleArea.right + buffer &&
    ship.shipY >= visibleArea.top - buffer &&
    ship.shipY <= visibleArea.bottom + buffer;
}

function drawShip(ctx, ship, currentScale) {
  const displayX = Math.round(ship.shipX);
  const displayY = Math.round(ship.shipY);

  // Schifffarbe basierend auf Missionstyp
  if (ship.missionType === 'combat') {
    ctx.fillStyle = 'rgba(0, 255, 255, 0.8)'; // cyan für Kampfmissionen
  } else {
    ctx.fillStyle = 'white';
  }

  // Schiff als Kreis darstellen
  ctx.beginPath();
  ctx.arc(displayX, displayY, 10 * currentScale, 0, 2 * Math.PI);
  ctx.fill();
}

// Angepasste drawShipLabel-Funktion
function drawShipLabel(ctx, ship, currentScale) {
  const labelText = ship.missionType === 'combat' 
    ? `${ship.targetName} (Angriff)` 
    : ship.targetName;
  
  const textWidth = ctx.measureText(labelText).width;
  // Verwende exactX/Y für glattere Animationen
  const textX = ship.exactX - textWidth / 2;
  const textY = ship.exactY + ship.textOffsetY * currentScale;
  
  // Kampfmission-Text in Rot darstellen
  if (ship.missionType === 'combat') {
    ctx.fillStyle = 'rgba(0, 255, 255, 1)'; // cyan für Kampfmissionen
  } else {
    ctx.fillStyle = 'white';
  }
  
  ctx.fillText(labelText, textX, textY);
}

interface ClickCoordinates {
  x: number;
  y: number;
}

function getClickCoordinates(e: MouseEvent): ClickCoordinates | null {
  const rect = canvasRef.value?.getBoundingClientRect();
  if (!rect || !ctx.value || !canvasRef.value) return null;

  const scaleX = canvasRef.value.width / rect.width;
  const scaleY = canvasRef.value.height / rect.height;

  const x = (e.clientX - rect.left) * scaleX;
  const y = (e.clientY - rect.top) * scaleY;

  return {
    x: (x - pointX.value) / zoomLevel.value,
    y: (y - pointY.value) / zoomLevel.value
  };
}

function handleCoordinateDisplay(coords: ClickCoordinates, e: MouseEvent) {
  console.log(`Koordinaten: x=${Math.round(coords.x)}, y=${Math.round(coords.y)}`);
  // showCoordinatesOverlay(coords.x, coords.y);
  e.stopPropagation();
  return true;
}

function findClickedStation(coords: ClickCoordinates) {
  return props.stations.find(station =>
    Math.abs(coords.x - station.x) < stationBaseSize * scale.value / 2 &&
    Math.abs(coords.y - station.y) < stationBaseSize * scale.value / 2
  );
}

function findClickedAsteroid(coords: ClickCoordinates) {
  return props.asteroids.find(asteroid => {
    const scaledSize = (asteroidBaseSize * asteroid.pixel_size) * scale.value;
    return Math.abs(coords.x - asteroid.x) < scaledSize / 2 &&
      Math.abs(coords.y - asteroid.y) < scaledSize / 2;
  });
}

function handleClickedObject(clickedObject: { type: 'station' | 'asteroid'; data: Station | Asteroid }) {
  const isOtherUserStation = clickedObject.type === 'station' &&
    'user_id' in clickedObject.data &&
    clickedObject.data.user_id !== usePage().props.auth.user.id;

  if (isOtherUserStation) {
    selectedObject.value = clickedObject;
    isModalOpen.value = true;
  } else if (clickedObject.type === 'asteroid') {
    getAsteroidResources(clickedObject.data as Asteroid);
  }
}

function onMouseClick(e: MouseEvent) {
  const coords = getClickCoordinates(e);
  if (!coords) return;

  if (e.ctrlKey && handleCoordinateDisplay(coords, e)) return;

  const clickedStation = findClickedStation(coords);
  if (clickedStation) {
    handleClickedObject({ type: 'station', data: clickedStation });
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

// Funktion zum Verschieben der Karte mit den Pfeiltasten
function onKeyDown(e: KeyboardEvent) {
  // Keine Aktion, wenn in Eingabefeldern oder Modals
  if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) {
    return;
  }

  if (isModalOpen.value) {
    return;
  }
  if (!canvasRef.value || !ctx.value) return;

  // Geschwindigkeit vom Zoomlevel abhängig machen
  const speedFactor = 2 / zoomLevel.value;
  const currentSpeed = moveSpeed.value * speedFactor;

  switch (e.key) {
    case 'ArrowUp':
      pointY.value += currentSpeed;
      e.preventDefault();
      break;
    case 'ArrowDown':
      pointY.value -= currentSpeed;
      e.preventDefault();
      break;
    case 'ArrowLeft':
      pointX.value += currentSpeed;
      e.preventDefault();
      break;
    case 'ArrowRight':
      pointX.value -= currentSpeed;
      e.preventDefault();
      break;
  }

  if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
    requestAnimationFrame(drawScene);
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
    const asteroid = props.asteroids.find(a => a.id === highlightedAsteroids.value[0]?.id);
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

watch(() => usePage().props.queue, () => {
  updateShipPool();
  scheduleDraw();
}, { deep: true });

</script>

<template>
  <AppLayout title="AsteroidMap">
    <div class="relative" @click.prevent="">
      <canvas ref="canvasRef" class="block w-full bg-[hsl(263,45%,7%)]" @mousedown="onMouseDown"
        @mousemove="onMouseMove" @mouseup="onMouseUp" @wheel="onWheel" @click="onMouseClick">
      </canvas>
      <canvas ref="canvasStaticRef" class="block w-full absolute z-10 top-0 left-0 pointer-events-none">
      </canvas>

      <div class="absolute top-2 left-0 z-100 flex gap-2 ms-4 bg-[hsl(263,45%,7%)]">
        <AsteroidMapSearch v-model="searchForm.query" @clear="clearSearchAndUpdate" @search="searchAndFocus" />
      </div>

      <AsteroidMapDropdown v-if="highlightedAsteroids && highlightedAsteroids.length > 0"
        class="absolute top-2 left-64 ms-2 w-44" :searched-asteroids="highlightedAsteroids"
        :selected-asteroid="selectedAsteroid" @select-asteroid="selectAsteroid" />

      <span class="absolute top-0 right-0 z-100 text-white me-2">zoom: {{ Math.round(zoomLevel * 1000 / 5) }}%</span>
      <span @click="focusOnObject(null, usePage().props.auth.user.id)"
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

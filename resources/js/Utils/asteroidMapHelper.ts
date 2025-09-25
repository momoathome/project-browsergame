import { computed } from 'vue';
import type { Ref } from 'vue';
import type { Asteroid, Station } from '@/types/types';

export const asteroidImages = [
  '/images/asteroids/Asteroid2.webp',
  '/images/asteroids/Asteroid3.webp',
  '/images/asteroids/Asteroid4.webp',
  '/images/asteroids/Asteroid5.webp',
  '/images/asteroids/Asteroid6.webp',
  '/images/asteroids/Asteroid7.webp',
  '/images/asteroids/Asteroid8.webp',
  // ...weitere Bilder
];

export const rebelFactionImageMap: Record<string, string> = {
  'Rostwölfe':   '/images/stations/stationRed.webp',
  'Kult der Leere':  '/images/stations/stationViolet.webp',
  'Sternenplünderer': '/images/stations/stationBlue.webp',
  'Gravbrecher': '/images/stations/stationGreen.webp',
  // ggf. weitere Fraktionen
};

export const asteroidImageElements = asteroidImages.map(src => {
  const img = new Image();
  img.src = src;
  return img;
});

export const rebelImageMap: Record<string, HTMLImageElement> = {};
Object.entries(rebelFactionImageMap).forEach(([faction, src]) => {
  const img = new Image();
  img.src = src;
  rebelImageMap[faction] = img;
});

export function calculateVisibleArea(width: number, height: number, pointX: Ref<number>, pointY: Ref<number>, zoomLevel: Ref<number>) {
  return {
    left: -pointX.value / zoomLevel.value,
    top: -pointY.value / zoomLevel.value,
    right: (width - pointX.value) / zoomLevel.value,
    bottom: (height - pointY.value) / zoomLevel.value,
  };
}

export function isObjectVisible(
    object: { x: number; y: number; pixel_size?: number }, 
    visibleArea: { left: number; top: number; right: number; bottom: number },
    objectBaseSize: number,
    scale: Ref<number>
): boolean {
  if (!object || typeof object.x !== 'number' || typeof object.y !== 'number') {
    return false;
  }
  // Berechne den Abstand zum sichtbaren Bereich basierend auf der Pixelgröße
  const buffer = object.pixel_size ? object.pixel_size * objectBaseSize * scale.value : 100;

  if (object.x < visibleArea.left - buffer ||
    object.x > visibleArea.right + buffer ||
    object.y < visibleArea.top - buffer ||
    object.y > visibleArea.bottom + buffer) {
    return false;
  }

  return true;
}

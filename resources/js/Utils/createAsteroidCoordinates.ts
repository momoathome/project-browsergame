import { generateRandomInteger } from '@/Utils/generator';
import * as config from '@/config';

interface Station {
  id: number;
  x: number;
  y: number;
  name: string;
}

interface Asteroid {
  id: number;
  name: string;
  size: string;
  base: number;
  multiplier: number;
  value: number;
  resources: Partial<Record<string, number>>;
}

// Asteroid-Interface mit Koordinaten
interface AsteroidWithCoords extends Asteroid {
  x: number;
  y: number;
  pixelSize: number;
}

// AsteroidData-Typdefinition ohne Koordinaten
type AsteroidData = {
  [key: string]: Asteroid;
};

const stationRadius = config.stationRadius;
const minDistance = config.minDistance;
const universeSize = config.universeSize;

export function createAsteroidCoordinates(asteroidsData: AsteroidData, stations: Station[] = []): AsteroidWithCoords[] {
  let x: number;
  let y: number;
  const asteroidsCoords: AsteroidWithCoords[] = [];

  for (const asteroidId in asteroidsData) {
    if (asteroidsData.hasOwnProperty(asteroidId)) {
      const asteroid = asteroidsData[asteroidId];
      const distanceModifier = config.distanceModifiers[asteroid.size] || minDistance;

      do {
        x = generateRandomInteger(distanceModifier, universeSize);
        y = generateRandomInteger(distanceModifier, universeSize);
      } while (isCollidingWithStation(x, y, distanceModifier) || isCollidingWithAsteroid(x, y));

      const asteroidRiskToImgSize = transformAsteroidRarityToImgSize(asteroidsData[asteroidId].size);

      asteroidsCoords.push({
        ...asteroid,
        x,
        y,
        pixelSize: asteroidRiskToImgSize,
      });
    }
  }

  function isCollidingWithAsteroid(x: number, y: number): boolean {
    return asteroidsCoords.some(
      asteroid => Math.abs(asteroid.x - x) < minDistance && Math.abs(asteroid.y - y) < minDistance
    );
  }

  function isCollidingWithStation(x: number, y: number, distanceModifier: number): boolean {
    return stations.some(
      station => Math.abs(station.x - x) < distanceModifier + stationRadius && Math.abs(station.y - y) < distanceModifier + stationRadius
    );
  }

  return asteroidsCoords;
}

function transformAsteroidRarityToImgSize(risk: string): number {
  switch (risk) {
    case 'common':
      return config.asteroidSize.common;
    case 'uncommen':
      return config.asteroidSize.uncommen;
    case 'rare':
      return config.asteroidSize.rare;
    case 'extrem':
      return config.asteroidSize.extrem;
    default:
      return config.asteroidSize.uncommen;
  }
}

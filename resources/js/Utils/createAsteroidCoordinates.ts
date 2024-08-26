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
  type: string;
  rarity: string;
  faktor: number;
  size: number;
  value: number;
  titanium: number;
  carbon: number;
  kyberkristall: number;
  hydrogenium: number;
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


// config
const radius = 100;
const stationRadius = config.stationRadius;
const minDistance = config.minDistance;
const universeSize = config.universeSize;

export function createAsteroidCoordinates(asteroidsData: AsteroidData, stations: Station[] = []): AsteroidWithCoords[] {
  let x: number;
  let y: number;
  const asteroidsCoords: AsteroidWithCoords[] = [];

  for (const asteroidId in asteroidsData) {
    if (asteroidsData.hasOwnProperty(asteroidId)) {
      do {
        x = generateRandomInteger(minDistance, universeSize);
        y = generateRandomInteger(minDistance, universeSize);
      } while (isCollidingWithStation(x, y) || isCollidingWithAsteroid(x, y));

      const asteroidRiskToImgSize = transformAsteroidRarityToImgSize(asteroidsData[asteroidId].rarity);

      asteroidsCoords.push({
        ...asteroidsData[asteroidId],
        x,
        y,
        pixelSize: asteroidRiskToImgSize,
      });
    }
  }

  function isCollidingWithAsteroid(x: number, y: number): boolean {
    return asteroidsCoords.some(
      asteroid => Math.abs(asteroid.x - x) < minDistance + radius && Math.abs(asteroid.y - y) < minDistance + radius
    );
  }

  function isCollidingWithStation(x: number, y: number): boolean {
    return stations.some(
      station => Math.abs(station.x - x) < minDistance + stationRadius && Math.abs(station.y - y) < minDistance + stationRadius
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

import { generateRandomInteger, generateRandomString } from '@/Utils/generator';
import * as config from '@/config';

interface Asteroid {
  id: number;
  name: string;
  rarity: string;
  base: number;
  multiplier: number;
  value: number;
  resources: Partial<Record<string, number>>;
}

interface AsteroidRarity {
  [key: string]: number;
}

type AsteroidData = {
  [key: string]: Asteroid;
};

export function createAsteroids(asteroidCount: number): AsteroidData {
  // enthält alle Asteroiden
  const asteroidsList = (): AsteroidData => {
    const asteroids: AsteroidData = {}

    for (let i = 0; i < asteroidCount; i++) {
      const asteroid = generateAsteroid();
      asteroids[asteroid.id.toString()] = asteroid;
    }

    return asteroids
  }

  return asteroidsList()
}

function generateAsteroid(): Asteroid {
  const asteroidID: number = Math.floor(Math.random() * Math.floor(Math.random() * Date.now()));
  const asteroidBaseFaktor = generateAsteroidBaseFaktorValue(config.asteroidFaktor.min, config.asteroidFaktor.max);
  const asteroidRarity = generateAsteroidRarity(config.asteroidRarity);
  const asteroidRarityMultiplier = generateasteroidRarityMultiplier(asteroidRarity);
  const asteroidBaseMultiplier = generateAsteroidBaseMultiplier(asteroidRarityMultiplier);
  const asteroidValue = generateAsteroidValue(asteroidBaseFaktor, asteroidBaseMultiplier);
  const resources = generateResourcesFromPools(asteroidValue);
  const asteroidName = generateAsteroidName(asteroidRarity, asteroidValue, asteroidBaseMultiplier);

  const asteroid: Asteroid = {
    id: asteroidID,
    name: asteroidName,
    rarity: asteroidRarity,
    base: asteroidBaseFaktor,
    multiplier: asteroidBaseMultiplier,
    value: asteroidValue,
    resources,
  };

  return asteroid;
}

function generateAsteroidRarity(asteroidRarity: AsteroidRarity): string {
  const totalWeight: number = Object.values(asteroidRarity).reduce((acc, value): number => acc + value, 0);
  const randomValue: number = generateRandomInteger(0, totalWeight - 1);

  let cumulativeWeight: number = 0;
  for (const [rarity, weight] of Object.entries(asteroidRarity)) {
    cumulativeWeight += weight;
    if (randomValue < cumulativeWeight) {
      return rarity;
    }
  }

  // Fallback (sollte normalerweise nicht erreicht werden)
  return 'common';
}

// Bestimmt den Multiplikator für die Größe des Asteroiden anhand der asteroidRarityMultiplier
function generateasteroidRarityMultiplier(rarity: string) {
  let rarityMultiplier = { min: 0, max: 0 };

  switch (rarity) {
    case 'common':
      rarityMultiplier = config.asteroidRarityMultiplier.common
      break;
    case 'uncommen':
      rarityMultiplier = config.asteroidRarityMultiplier.uncommen
      break;
    case 'rare':
      rarityMultiplier = config.asteroidRarityMultiplier.rare
      break;
    case 'extrem':
      rarityMultiplier = config.asteroidRarityMultiplier.extrem
      break;
  }

  return rarityMultiplier
}

function generateAsteroidBaseMultiplier(asteroidRarityMultiplier: { min: number; max: number; }): number {
  const asteroidBaseMultiplier = Math.random() * (asteroidRarityMultiplier.max - asteroidRarityMultiplier.min) + asteroidRarityMultiplier.min;
  return parseFloat(asteroidBaseMultiplier.toFixed(4))
}

function generateResourcesFromPools(asteroidValue: number): Partial<Record<string, number>> {
  const resources: Partial<Record<string, number>> = {};

  const selectedPool = Object.keys(config.resourcePools)[Math.floor(Math.random() * Object.keys(config.resourcePools).length)];
  const poolResources = config.resourcePools[selectedPool];
  const resourceWeights = config.poolResourceWeights[selectedPool];
  const totalWeight = Object.values(resourceWeights).reduce((sum, weight) => sum + weight, 0);

  poolResources.forEach(resource => {
    const weight = resourceWeights[resource];
    const normalizedWeight = weight / totalWeight;
    resources[resource] = Math.floor(normalizedWeight * asteroidValue);
  });
  

  return resources;
}


function generateAsteroidValue(asteroidBaseFaktor: number, asteroidBaseMultiplier: number): number {
  return Math.floor(asteroidBaseFaktor * asteroidBaseMultiplier)
}

function generateAsteroidBaseFaktorValue(asteroidFaktorMin: number, asteroidFaktorMax: number): number {
  const min = Math.ceil(asteroidFaktorMin);
  const max = Math.floor(asteroidFaktorMax);
  return generateRandomInteger(min, max)
}


function generateAsteroidName(asteroidRarity: string, asteroidValue: number, asteroidBaseMultiplier: number) {
  const rarity = asteroidRarity.slice(0, 1)
  const value = asteroidValue
  const multiplier = Math.floor(asteroidBaseMultiplier)
  const asteroidName = generateRandomString(2) + rarity + generateRandomString(2) + value.toString() + '-' + multiplier.toString()

  return asteroidName
}

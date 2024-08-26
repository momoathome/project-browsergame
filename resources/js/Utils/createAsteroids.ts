import { generateRandomInteger, generateRandomString } from '@/Utils/generator';
import * as config from '@/config';

interface AsteroidTypeMatrix {
  [key: string]: number[];
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

type AsteroidData = {
  [key: string]: Asteroid;
};

export function createAsteroids(asteroidCount: number): AsteroidData {
  // config
  // Base Faktor für die Anzahl der Rohstoffe des Asteroiden
  const asteroidFaktor = config.asteroidFaktor;
  const asteroidSizeFaktorMatrix = config.asteroidSizeFaktorMatrix;
  const asteroidTypeMatrix: AsteroidTypeMatrix = config.asteroidTypeMatrix;

  // enthält alle Asteroiden
  const asteroidsList = (): AsteroidData => {
    const asteroids: AsteroidData = {}

    for (let i = 0; i < asteroidCount; i++) {
      const asteroid = generateAsteroid();
      asteroids[asteroid.id.toString()] = asteroid;
    }

    return asteroids
  }

  function generateAsteroid(): Asteroid {
    const asteroidID: number = Math.floor(Math.random() * Math.floor(Math.random() * Date.now()))
    const asteroidBaseFaktor = generateAsteroidBaseFaktorValue(asteroidFaktor.min, asteroidFaktor.max)
    const asteroidRarity = generateAsteroidRarity()
    const asteroidSizeFaktor = generateAsteroidSizeFaktor(asteroidRarity)
    const asteroidSize = generateAsteroidSize(asteroidSizeFaktor)
    const asteroidBaseValue = generateAsteroidBaseValue(asteroidBaseFaktor, asteroidSize)
    const asteroidType = generateAsteroidType()
    const asteroidName = generateAsteroidName()
    const asteroidTitaniumValue = generateIndividualRessourceValue(asteroidBaseValue, asteroidType, 0)
    const asteroidCarbonValue = generateIndividualRessourceValue(asteroidBaseValue, asteroidType, 1)
    const asteroidKristallValue = generateIndividualRessourceValue(asteroidBaseValue, asteroidType, 2)
    const asteroidHydroValue = generateIndividualRessourceValue(asteroidBaseValue, asteroidType, 3)

    function generateAsteroidName() {
      const type = asteroidType.slice(0, 1)
      const rarity = asteroidRarity.slice(0, 1)
      const value = asteroidBaseValue
      const size = Math.floor(asteroidSize)
      const asteroidName = generateRandomString(2) + type + rarity + generateRandomString(2) + value.toString() + '-' + size.toString()

      return asteroidName
    }

    const asteroid: Asteroid = {
      id: asteroidID,
      name: asteroidName,
      type: asteroidType,
      rarity: asteroidRarity,
      faktor: asteroidBaseFaktor,
      size: asteroidSize,
      value: asteroidBaseValue,
      titanium: asteroidTitaniumValue,
      carbon: asteroidCarbonValue,
      kyberkristall: asteroidKristallValue,
      hydrogenium: asteroidHydroValue,
    };

    return asteroid
  }

  function generateAsteroidRarity(): string {
    const rarity = generateRandomInteger(0, 100);

    if (rarity >= 50) return 'common';
    if (rarity >= 20) return 'uncommen';
    if (rarity >= 4) return 'rare';
    return 'extrem';
  }

  // Bestimmt den Multiplikator für die Größe des Asteroiden anhand der asteroidSizeFaktorMatrix
  function generateAsteroidSizeFaktor(rarity: string) {
    let sizeFaktor = { min: 0, max: 0 };

    switch (rarity) {
      case 'common':
        sizeFaktor = asteroidSizeFaktorMatrix.common
        break;
      case 'uncommen':
        sizeFaktor = asteroidSizeFaktorMatrix.uncommen
        break;
      case 'rare':
        sizeFaktor = asteroidSizeFaktorMatrix.rare
        break;
      case 'extrem':
        sizeFaktor = asteroidSizeFaktorMatrix.extrem
        break;
    }

    return sizeFaktor
  }

  // Bestimmt welcher Rohstoff häufiger vorkommt anhand der asteroidTypeMatrix
  function generateAsteroidType(): string {
    const asteroidType = generateRandomInteger(0, 100);

    if (asteroidType >= 40) return 'default';
    if (asteroidType >= 25) return 'titanium';
    if (asteroidType >= 10) return 'carbon';
    if (asteroidType >= 5) return 'hydrogenium';
    return 'kyberkristall';
  }

  function generateIndividualRessourceValue(asteroidBaseValue: number, asteroidType: string, ressourceIndex: number): number {
    const ressourceValue = asteroidBaseValue / 4 * asteroidTypeMatrix[asteroidType][ressourceIndex]
    return Math.floor(ressourceValue)
  }

  function generateAsteroidBaseValue(asteroidBaseFaktor: number, asteroidSize: number): number {
    return Math.floor(asteroidBaseFaktor * asteroidSize)
  }

  function generateAsteroidBaseFaktorValue(asteroidFaktorMin: number, asteroidFaktorMax: number): number {
    const min = Math.ceil(asteroidFaktorMin);
    const max = Math.floor(asteroidFaktorMax);
    return generateRandomInteger(min, max)
  }

  function generateAsteroidSize(asteroidSizeFaktor: { min: number; max: number; }): number {
    const asteroidSize = Math.random() * (asteroidSizeFaktor.max - asteroidSizeFaktor.min) + asteroidSizeFaktor.min;
    return parseFloat(asteroidSize.toFixed(4))
  }

  return asteroidsList()
}

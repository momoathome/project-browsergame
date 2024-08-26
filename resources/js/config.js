export const asteroidCount = 5000;
export const asteroidDensity = 35; // lower asteroid density = more asteroids per square
export const stationRadius = 1500;
export const minDistance = 500;
export const universeSize = asteroidCount * asteroidDensity;

export const asteroidImageBaseSize = 64;
export const stationImageBaseSize = 256;

export const asteroidSize = {
  common: 0.75,
  uncommen: 1,
  rare: 1.5,
  extrem: 2,
}

export const asteroidFaktor = {
  min: 200,
  max: 250
}

export const asteroidSizeFaktorMatrix = {
  common: { min: 4, max: 8 },
  uncommen: { min: 13, max: 21 },
  rare: { min: 34, max: 55 },
  extrem: { min: 89, max: 144 },
}

export const asteroidTypeMatrix = {
  default: [1.3, 1.75, 0.25, 0.7],
  titanium: [3.0, 0.6, 0.1, 0.3],
  carbon: [0.5, 3.0, 0.2, 0.3],
  kyberkristall: [1.3, 2.0, 0.6, 0.1],
  hydrogenium: [0.8, 1.5, 0.1, 1.6],
}

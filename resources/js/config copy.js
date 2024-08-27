// universe Stats
export const asteroidCount = 2000;
export const asteroidDensity = 50; // lower asteroid density = more asteroids per square
export const minDistance = 1000;
export const stationRadius = 2000;
export const universeSize = asteroidCount * asteroidDensity;

export const asteroidImageBaseSize = 64;
export const stationImageBaseSize = 512;

// zoom
export const maxOuterZoomLevel = 0.01;
export const maxInnerZoomLevel = 1;
export const baseZoomLevel = 0.1;
export const zoomDelta = 0.01;

// asteroid
export const asteroidSize = {
  common: 1,
  uncommen: 2,
  rare: 4,
  extrem: 8,
}

export const asteroidFaktor = {
  min: 55,
  max: 90
}

export const asteroidRarity = {
  common: 800,
  uncommen: 250,
  rare: 50,
  extrem: 3,
}

export const asteroidRarityMultiplier = {
  common: { min: 5, max: 8 },
  uncommen: { min: 13, max: 21 },
  rare: { min: 34, max: 55 },
  extrem: { min: 89, max: 144 },
}

export const distanceModifiers= {
  common: minDistance,
  uncommen: minDistance,
  rare: 3 * minDistance,
  extrem: 10 * minDistance,
};

export const resourcePools = {
  legacy: ['titanium', 'carbon', 'hydrogenium', 'kyberkristall'],
  metal: ['titanium', 'cobalt', 'iridium'],
  crystal: ['carbon', 'kyberkristall', 'hyperdiamond'],
  radioactive: ['uraninite', 'thorium', 'astatine'],
  exotic: ['hydrogenium', 'dilithium', 'deuterium'],
};

export const poolResourceWeights = {
  legacy: {
    titanium: 0.25,
    carbon: 0.45,
    hydrogenium: 0.2,
    kyberkristall: 0.1,
  },
  metal: {
    titanium: 0.7,
    cobalt: 0.2,
    iridium: 0.1,
  },
  metal2: {
    titanium: 0.4,
    cobalt: 0.4,
    iridium: 0.2,
  },
  crystal: {
    carbon: 0.75,
    kyberkristall: 0.2,
    hyperdiamond: 0.05,
  },
  crystal2: {
    carbon: 0.55,
    kyberkristall: 0.35,
    hyperdiamond: 0.1,
  },
  radioactive: {
    hydrogenium: 0.6,
    uraninite: 0.25,
    thorium: 0.1,
    astatine: 0.05,
  },
  radioactive2: {
    hydrogenium: 0.4,
    uraninite: 0.3,
    thorium: 0.2,
    astatine: 0.1,
  },
  exotic: {
    hydrogenium: 0.85,
    dilithium: 0.1,
    deuterium: 0.05,
  },
};

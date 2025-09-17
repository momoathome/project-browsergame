import { AsteroidService } from './AsteroidService';
import { QueueService } from './QueueService';
import { SpacecraftService } from './SpacecraftService';
import { BuildingService } from './BuildingService';
// Weitere Services hier importieren

export const api = {
  asteroids: new AsteroidService(),
  queue: new QueueService(),
  buildings: new BuildingService(),
  spacecrafts: new SpacecraftService(),
  // Weitere Services hier hinzufügen
};

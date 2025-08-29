import { AsteroidService } from './AsteroidService';
import { QueueService } from './QueueService';
import { SpacecraftService } from './SpacecraftService';
// Weitere Services hier importieren

export const api = {
  asteroids: new AsteroidService(),
  queue: new QueueService(),
  spacecrafts: new SpacecraftService(),
  // Weitere Services hier hinzufügen
};

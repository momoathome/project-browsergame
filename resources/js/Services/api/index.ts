import { AsteroidService } from './AsteroidService';
import { QueueService } from './QueueService';
// Weitere Services hier importieren

export const api = {
  asteroids: new AsteroidService(),
  queue: new QueueService(),
  // Weitere Services hier hinzufügen
};

import { AsteroidService } from './AsteroidService';
import { QueueService } from './QueueService';
import { SpacecraftService } from './SpacecraftService';
import { BuildingService } from './BuildingService';
import { UserAttributeService } from './UserAttributeService';
import { UserResourceService } from './UserResourceService';
// Weitere Services hier importieren

export const api = {
  asteroids: new AsteroidService(),
  queue: new QueueService(),
  buildings: new BuildingService(),
  spacecrafts: new SpacecraftService(),
  userAttributes: new UserAttributeService(),
  userResources: new UserResourceService(),
  // Weitere Services hier hinzufügen
};

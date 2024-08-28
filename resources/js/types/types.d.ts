export interface BuildingDetails {
  id: number;
  name: string;
  description: string;
  image: string;
  effect: string;
}

export interface Building {
  id: number;
  details: BuildingDetails;
  level: number;
  buildTime: number;
  resources: Resource[];
}

export interface ResourcePivot {
  resource_id: number;
  amount: number;
}

export interface BuildingResourcePivot extends ResourcePivot {
  building_id: number;
}

export interface SpacecraftResourcePivot extends ResourcePivot {
  spacecraft_id: number;
}

export interface Resource {
  id: number;
  name: string;
  description: string;
  image: string;
  pivot: BuildingResourcePivot | SpacecraftResourcePivot;
}

export interface SpacecraftDetails {
  id: number;
  name: string;
  description: string;
  image: string;
  type: string;
}

export interface Spacecraft {
  id: number;
  details: SpacecraftDetails;
  combat: number;
  count: number;
  cargo: number;
  buildTime: number;
  unitLimit: number;
  unlocked: boolean;
  resources: Resource[];
}

export interface BuildingCardProps {
  moduleData: Building[];
}

export interface SpacecraftCardProps {
  moduleData: Spacecraft[];
}

export interface Asteroid {
  id: number;
  name: string;
  rarity: string;
  base: number;
  multiplier: number;
  value: number;
  resource_pool: string;
  resources: Record<string, number>;
  x: number;
  y: number;
  pixel_size: number;
}

export interface Station {
  id: number;
  name: string;
  x: number;
  y: number;
}

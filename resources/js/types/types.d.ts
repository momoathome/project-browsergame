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
  build_time: number;
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
  build_time: number;
  unit_limit: number;
  unlocked: boolean;
  resources: Resource[];
}

export interface SimpleResource {
  name: string;
  image: string;
  amount: number;
}
export interface FormattedBuilding {
  id: number;
  image: string;
  name: string;
  description: string;
  level: number;
  build_time: number;
  resources: SimpleResource[];
};
export interface FormattedSpacecraft {
  id: number;
  image: string;
  name: string;
  description: string;
  type: string;
  combat: number;
  count: number;
  cargo: number;
  build_time: number;
  unit_limit: number;
  resources: SimpleResource[];
};

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

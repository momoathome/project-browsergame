export interface BuildingDetails {
  id: number;
  name: string;
  description: string;
  image: string;
  effect: string;
}

export interface Building {
  id: number;
  user_id: number;
  details: BuildingDetails;
  level: number;
  build_time: number;
  effect: string;
  effect_value: number;
  is_upgrading: boolean;
  upgrade_end_time: string;
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

export interface UserResources {
  id: number;
  user_id: number;
  resource_id: number;
  amount: number;
  created_at: string;
  updated_at: string;
  resources: Resource;
}

export interface UserAttributes {
  id: number;
  user_id: number;
  attribute_name: string;
  attribute_value: number;
  created_at: string;
  updated_at: string;
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
  cargo: number;
  speed: number;
  count: number;
  build_time: number;
  crew_limit: number;
  unlocked: boolean;
  research_cost: number;
  is_producing: boolean;
  production_end_time: string;
  currently_producing: number;
  resources: Resource[];
}

export interface SimpleResource {
  id: number;
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
  effect: string;
  effect_value: number;
  is_upgrading: boolean;
  upgrade_end_time: string;
  resources: SimpleResource[];
};

export interface FormattedSpacecraft {
  id: number;
  image: string;
  name: string;
  description: string;
  type: string;
  combat: number;
  cargo: number;
  speed: number;
  count: number;
  build_time: number;
  crew_limit: number;
  unlocked: boolean;
  research_cost: number;
  is_producing: boolean;
  production_end_time: string;
  currently_producing: number;
  resources: SimpleResource[];
};

export interface AsteroidResource {
  id: number;
  asteroid_id: number;
  resource_type: string;
  amount: number;
};

export interface Asteroid {
  id: number;
  name: string;
  size: string;
  base: string;
  multiplier: string;
  value: number;
  resources: AsteroidResource[];
  x: number;
  y: number;
  pixel_size: number;
};

export interface Station {
  id: number;
  user_id: number;
  name: string;
  x: number;
  y: number;
}

export interface Market {
  id: number
  resource_id: number
  cost: number
  stock: number
  resource: Resource;
}

export interface formattedMarketResource {
  id: number
  resource_id: number
  name: string
  description: string
  image: string
  cost: number
  stock: number;
}

export type BattleResult = {
  winner: string;
  attackerLosses: {
    name: string;
    count: number;
    losses: number;
  }[];
  defenderLosses: {
    name: string;
    count: number;
    losses: number;
  }[];
};

export interface Building {
  id: number;
  name: string;
  description: string;
  image: string;
  level: number;
  build_time: number;
  effect: string;
  is_upgrading: boolean;
  end_time: string;
  resources: Array<{
    id: number;
    name: string;
    image: string;
    amount: number;
  }>;
  current_effects: BuildingEffect[];
  next_level_effects: BuildingEffect[];
}

export interface BuildingEffect {
  attribute: string;
  value: number;
  display: string;
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
  resource: Resource;
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
  locked_count: number;
  build_time: number;
  crew_limit: number;
  unlocked: boolean;
  research_cost: number;
  is_producing: boolean;
  end_time: string;
  currently_producing: number;
  resources: Resource[];
}

export interface SimpleResource {
  id: number;
  name: string;
  image: string;
  amount: number;
}

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
  locked_count: number;
  build_time: number;
  crew_limit: number;
  unlocked: boolean;
  research_cost: number;
  is_producing: boolean;
  end_time: string;
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

export interface QueueItemDetails {
  building_name?: string;
  spacecraft_name?: string;
  asteroid_name?: string;
  next_level?: number;
  quantity?: number;
  spacecrafts?: Record<string, number>;
  attacker_name?: string;
  defender_name?: string;
  attacker_formatted?: array;
  defender_formatted?: array;
}

export interface RawQueueItem {
  id: number;
  action_type: 'building' | 'produce' | 'mining' | string;
  details: QueueItemDetails;
  user_id?: number;
  target_id?: number;
  start_time?: string;
  end_time?: string;
  status?: string;
}

export interface ProcessedQueueItem {
  id: number;
  name: string;
  image: string;
  details: string | number;
  showInfos: boolean;
  isNew: boolean;
  rawData: RawQueueItem;
  remainingTime?: number;
  formattedTime?: string;
  completed: boolean;
}

export interface SavedQueueItemState {
  id: number;
  seen: boolean;
  showInfos: boolean;
}

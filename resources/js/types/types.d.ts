export type BuildingEffectLevel = {
  [key: string]: string | number;
};

export interface BuildingEffect {
  current: BuildingEffectLevel;
  next_level: BuildingEffectLevel;
}

export interface Building {
  id: number;
  name: string;
  description: string;
  image: string;
  level: number;
  build_time: number;
  old_build_time: number;
  effect: BuildingEffect | null;
  is_upgrading: boolean;
  end_time: string;
  resources: Array<{
    id: number;
    name: string;
    image: string;
    amount: number;
  }>;
}

export interface Spacecraft {
  id: number;
  name: string;
  description: string;
  image: string;
  type: string;
  attack: number;
  defense: number;
  cargo: number;
  speed: number;
  operation_speed: number;
  count: number;
  locked_count: number;
  build_time: number;
  crew_limit: number;
  unlocked: boolean;
  research_cost: number;
  is_producing: boolean;
  end_time: string;
  currently_producing: number;
  resources: Array<{
    id: number;
    name: string;
    image: string;
    amount: number;
  }>;
}

export interface ShipRenderObject {
  shipX: number;
  shipY: number;
  missionId: number;
  targetName: string;
  isAttackOnMe: boolean;
  totalShips: number;
  targetX: number;
  targetY: number;
  startX: number;
  startY: number;
  exactX: number;
  exactY: number;
  startTime: number;
  endTime: number;
  completed: boolean;
  textOffsetY: number;
  missionType: string;
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
  attack: number;
  defense: number;
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
  stock: number
  category: string
  resource: Resource;
}

export interface formattedMarketResource {
  id: number
  resource_id: number
  name: string
  description: string
  image: string
  category: string
  stock?: number;
  amount?: number;
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
  attacker_id?: number;
  defender_id?: number;
  attacker_formatted?: array;
  defender_formatted?: array;
  target_coordinates?: {
    x: number;
    y: number;
  };
  attacker_coordinates?: {
    x: number;
    y: number;
  };
}

export interface RawQueueItem {
  id: number;
  actionType: 'building' | 'produce' | 'mining' | 'combat' | string;
  details: QueueItemDetails;
  userId?: number;
  targetId?: number;
  startTime?: string;
  endTime?: string;
  status?: string;
}

export interface ProcessedQueueItem {
  id: number;
  name: string;
  image: string;
  details: string | number;
  rawData: RawQueueItem;
  remainingTime?: number;
  formattedTime?: string;
  completed: boolean;
  processing: boolean;
  status: string;
  _callbackFired?: boolean;
}

// Typ für Spacecraft-Flotten in Missionen
export interface SpacecraftFleet {
  [spacecraftName: string]: number; // z.B. { "Mole": 1}
}

// Typ für Asteroid-Koordinaten
export interface AsteroidCoordinates {
  x: number;
  y: number;
}

// Typ für Mining-Mission-Details
export interface MiningMissionDetails {
  target_coordinates: AsteroidCoordinates;
  asteroid_name?: string;
  spacecrafts: SpacecraftFleet;
}

export interface CombatMissionDetails {
  target_coordinates: AsteroidCoordinates;
  defender_name?: string;
  attacker_formatted?: Array<{ name: string; count: number }>;
}

// Generischer Typ für alle Missionsarten
export interface MissionDetails {
  [key: string]: any;
  target_coordinates?: AsteroidCoordinates;
  asteroid_name?: string;
  spacecrafts?: SpacecraftFleet;
}

// Typ für Missionen in der Queue
export interface QueueItem {
  id: number;
  targetId: number;
  actionType: string;
  startTime: string;
  endTime: string;
  details: MissionDetails;
}

export interface Rebel {
  id: number;
  name: string;
  faction: string;
  x: number;
  y: number;
  difficulty_level: number;
  last_interaction: string;
  defeated_count: number;
  fleet_cap: number;
  fleet_growth_rate: number;
  loot_multiplier: number;
  adaptation_level: number;
  behavior: string;
  base_chance: number;
}

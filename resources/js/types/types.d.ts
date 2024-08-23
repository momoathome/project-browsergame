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
  effect_value: number;
  cost: number;
  buildTime: number;
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
  cost: number;
  unitLimit: number;
  unlocked: boolean;
}

export interface BuildingCardProps {
  moduleData: Building[];
}

export interface SpacecraftCardProps {
  moduleData: Spacecraft[];
}

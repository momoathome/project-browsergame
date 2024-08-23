export interface BuildingDetails {
  id: number;
  name: string;
  description: string;
  image: string;
  effect: string;
}

interface Building {
  id: number;
  details: BuildingDetails;
  level: number;
  buildTime: number;
  resources: Resource[];
}

export interface Resource {
  id: number;
  name: string;
  description: string;
  image: string;
  pivot: {
    building_id: number;
    resource_id: number;
    amount: number;
    created_at: string;
    updated_at: string;
  };
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

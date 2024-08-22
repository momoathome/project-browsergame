export interface BuildingSchema {
  id: number;
  name: string;
  description: string;
  image: string;
  effect: string;
}

export interface Building {
  id: number;
  schema: BuildingSchema;
  level: number;
  effect_value: number;
  cost: number;
  buildTime: number;
}

export interface BuildingCardProps {
  moduleData: Building[];
}

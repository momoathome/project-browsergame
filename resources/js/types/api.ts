import type { Asteroid } from './types';

export interface ApiResponse<T> {
  data: T;
  error?: string;
}

export interface AsteroidResourceResponse {
  asteroid: Asteroid;
}

export interface AsteroidSearchResponse {
  searched_asteroids: { id: number; name: string; }[];
  searched_stations: number[];
  searched_rebels: number[];
}

export interface AsteroidAutoMineMission {
  asteroid: {
    id: number;
    name: string;
    x: number;
    y: number;
    size: string;
    resources: Array<{
      resource_type: string;
      amount: number;
    }>;
    // ggf. weitere Felder
  };
  spacecrafts: Record<string, number>; // z.B. { "Miner": 2, "Titan": 1 }
  resources: Record<string, number>;   // z.B. { "Carbon": 100, "Hydrogenium": 50 }
  duration: number;
}

export interface AsteroidAutoMineResponse {
  missions: AsteroidAutoMineMission[];
}

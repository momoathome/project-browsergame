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
}

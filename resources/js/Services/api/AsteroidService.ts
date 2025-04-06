import { BaseApiService } from './BaseApiService';
import type { ApiResponse, AsteroidResourceResponse, AsteroidSearchResponse } from '@/types/api';

export class AsteroidService extends BaseApiService {
  async getResources(asteroidId: number): Promise<ApiResponse<AsteroidResourceResponse>> {
    return this.fetchWithError<AsteroidResourceResponse>(
      route('asteroidMap.asteroid', { asteroid: asteroidId }),
      { method: 'POST' }
    );
  }

  async search(query: string): Promise<ApiResponse<AsteroidSearchResponse>> {
    return this.fetchWithError<AsteroidSearchResponse>(
      route('asteroidMap.search'),
      {
        method: 'POST',
        body: JSON.stringify({ query })
      }
    );
  }
}

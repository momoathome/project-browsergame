import { BaseApiService } from './BaseApiService'
import type { ApiResponse } from '@/types/api'
import type { Building } from '@/types/types'

export class BuildingService extends BaseApiService {
  async getBuildings(): Promise<ApiResponse<Building[]>> {
    return this.fetchWithError<Building[]>(
      route('buildings.api'),
      {
        method: 'GET',
      }
    )
  }
}

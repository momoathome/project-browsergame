import { BaseApiService } from './BaseApiService'
import type { ApiResponse } from '@/types/api'
import type { Spacecraft } from '@/types/types'

export class SpacecraftService extends BaseApiService {
  async getSpacecrafts(): Promise<ApiResponse<Spacecraft[]>> {
    return this.fetchWithError<Spacecraft[]>(
      route('shipyard.api'),
      {
        method: 'GET',
      }
    )
  }
}

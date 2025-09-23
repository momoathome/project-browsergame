import { BaseApiService } from './BaseApiService'
import type { ApiResponse } from '@/types/api'
import type { UserResources } from '@/types/types'

export class UserResourceService extends BaseApiService {
  async getUserResources(): Promise<ApiResponse<UserResources[]>> {
    return this.fetchWithError<UserResources[]>(
      route('resources.api'),
      {
        method: 'GET',
      }
    )
  }
}

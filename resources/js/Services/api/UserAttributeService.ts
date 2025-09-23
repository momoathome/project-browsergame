import { BaseApiService } from './BaseApiService'
import type { ApiResponse } from '@/types/api'
import type { UserAttributes } from '@/types/types'

export class UserAttributeService extends BaseApiService {
  async getUserAttributes(): Promise<ApiResponse<UserAttributes[]>> {
    return this.fetchWithError<UserAttributes[]>(
      route('attributes.api'),
      {
        method: 'GET',
      }
    )
  }
}

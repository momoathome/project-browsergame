import { BaseApiService } from './BaseApiService'
import type { ApiResponse } from '@/types/api'
import type { RawQueueItem } from '@/types/types'

export class QueueService extends BaseApiService {
  async getQueue(): Promise<ApiResponse<RawQueueItem[]>> {
    return this.fetchWithError<RawQueueItem[]>(
      route('queue'),
      {
        method: 'GET',
      }
    )
  }
}

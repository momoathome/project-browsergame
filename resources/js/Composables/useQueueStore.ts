import { ref, watch } from 'vue'
import { api } from '@/Services/api'
import { usePage } from '@inertiajs/vue3'
import type { RawQueueItem } from '@/types/types'

let queueData: ReturnType<typeof ref<RawQueueItem[]>> | null = null

export function useQueueStore() {
  const page = usePage()
  if (!queueData) {
    queueData = ref<RawQueueItem[]>([])
  }

  async function refreshQueue() {
    const { data, error } = await api.queue.getQueue()
    if (!error) {
      // Falls data ein Objekt mit .queue ist, nimm das Array, sonst nimm data direkt (wenn es ein Array ist)
      if (Array.isArray(data)) {
        queueData!.value = data
      } else if (data && Array.isArray(data.queue)) {
        queueData!.value = data.queue
      } else {
        queueData!.value = []
      }
    }
  }

  return {
    queueData: queueData!,
    refreshQueue,
  }
}

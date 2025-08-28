import { ref, watch } from 'vue'
import { api } from '@/Services/api'
import { usePage } from '@inertiajs/vue3'
import type { RawQueueItem } from '@/types/types'

let queueData: ReturnType<typeof ref<RawQueueItem[]>> | null = null

export function useQueueStore() {
  const page = usePage()
  if (!queueData) {
    queueData = ref<RawQueueItem[]>(Array.isArray(page.props.queue) ? page.props.queue : [])
  }

  // Watch auf page.props.queue, um queueData bei jedem Inertia-Redirect zu aktualisieren
  watch(
    () => page.props.queue,
    (newQueue) => {
      queueData!.value = Array.isArray(newQueue) ? newQueue : []
    }
  )

  async function refreshQueue() {
    const { data, error } = await api.queue.getQueue()
    if (!error) queueData!.value = data
  }

  return {
    queueData: queueData!,
    refreshQueue,
  }
}

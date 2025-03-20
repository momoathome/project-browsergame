<script lang="ts" setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { timeFormat } from '@/Utils/format'
import type { SavedQueueItemState, RawQueueItem, ProcessedQueueItem, QueueItemDetails } from '@/types/types'

const loadQueueItemStates = (): Map<number, SavedQueueItemState> => {
  try {
    const savedItems = localStorage.getItem('queueItemStates')
    if (savedItems) {
      const parsedItems = JSON.parse(savedItems) as SavedQueueItemState[]
      const itemMap = new Map<number, SavedQueueItemState>()
      parsedItems.forEach(item => itemMap.set(item.id, item))
      return itemMap
    }
  } catch (e) {
    console.error('Fehler beim Laden der Item-Status:', e)
  }
  return new Map()
}

const saveQueueItemStates = (itemStates: Map<number, SavedQueueItemState>): void => {
  try {
    const itemArray = Array.from(itemStates.values())
    localStorage.setItem('queueItemStates', JSON.stringify(itemArray))
  } catch (e) {
    console.error('Fehler beim Speichern der Item-Status:', e)
  }
}

const queueItemStates = ref<Map<number, SavedQueueItemState>>(loadQueueItemStates())
const page = usePage()
const rawQueueData = computed<RawQueueItem[]>(() => page.props.queue || [])

const processedQueueItems = ref<ProcessedQueueItem[]>([])

const getImageByActionType = (actionType: string): string => {
  switch (actionType) {
    case 'building':
      return '/storage/navigation/buildings.png'
    case 'produce':
      return '/storage/navigation/shipyard.png'
    case 'mining':
      return '/storage/navigation/asteroidmap.png'
    case 'research':
      return '/storage/navigation/research.png'
    case 'combat':
      return '/storage/navigation/simulator.png'
    default:
      return '/storage/navigation/buildings.png'
  }
}

const getNameByActionType = (actionType: string, details: QueueItemDetails | undefined): string => {
  if (!details) return actionType

  switch (actionType) {
    case 'building':
      return details.building_name || 'Building'
    case 'produce':
      return details.spacecraft_name || 'Spacecraft'
    case 'mining':
      return 'Mining'
    case 'combat':
      return 'Combat'
    default:
      return actionType
  }
}

const getDetailsByActionType = (actionType: string, details: QueueItemDetails | undefined): string | number => {
  if (!details) return ''

  switch (actionType) {
    case 'building':
      return details.next_level || 1
    case 'produce':
      return details.quantity || 1
    case 'mining':
      return details.asteroid_name || 'Asteroid'
    case 'combat':
      return 'attack ' + details.defender_name || 'Defender'
    default:
      return ''
  }
}

const getRemainingTime = (item: RawQueueItem): number => {
  if (!item.end_time) return 0

  const endTime = new Date(item.end_time).getTime()
  const currentTime = new Date().getTime()

  return Math.max(0, endTime - currentTime)
}

const processQueueData = (): void => {
  if (!rawQueueData.value || rawQueueData.value.length === 0) {
    processedQueueItems.value = []
    return
  }

  let hasStateChanges = false

  const newItems: ProcessedQueueItem[] = rawQueueData.value.map(item => {
    let itemState = queueItemStates.value.get(item.id)

    if (!itemState) {
      // Neues Item gefunden
      itemState = {
        id: item.id,
        seen: false,
        showInfos: false
      }
      queueItemStates.value.set(item.id, itemState)
      hasStateChanges = true
    }

    const isItemNew = !itemState.seen

    if (isItemNew) {
      itemState.seen = true
      hasStateChanges = true
    }

    const processedItem: ProcessedQueueItem = {
      id: item.id,
      name: getNameByActionType(item.action_type, item.details),
      image: getImageByActionType(item.action_type),
      details: getDetailsByActionType(item.action_type, item.details),
      showInfos: itemState.showInfos,
      isNew: isItemNew,
      rawData: item,
      completed: false
    }

    if (item.end_time) {
      const remainingTimeMs = getRemainingTime(item)
      processedItem.remainingTime = remainingTimeMs
      processedItem.formattedTime = timeFormat(Math.floor(remainingTimeMs / 1000))

      if (remainingTimeMs <= 0) {
        processedItem.completed = true
        processedItem.formattedTime = '00:00'
      }
    }

    return processedItem
  })

  if (hasStateChanges) {
    saveQueueItemStates(queueItemStates.value)
  }

  newItems.forEach(item => {
    if (item.isNew) {
      setTimeout(() => {
        const foundItem = processedQueueItems.value.find(i => i.id === item.id)
        if (foundItem) {
          foundItem.isNew = false
        }
      }, 1000)
    }
  })

  processedQueueItems.value = newItems
}

const toggleInfo = (item: ProcessedQueueItem): void => {
  const foundItem = processedQueueItems.value.find(i => i.id === item.id)
  if (foundItem) {
    foundItem.showInfos = !foundItem.showInfos

    const itemState = queueItemStates.value.get(item.id)
    if (itemState) {
      itemState.showInfos = foundItem.showInfos
      saveQueueItemStates(queueItemStates.value)
    }
  }
}

const updateTimers = (): void => {
  if (!processedQueueItems.value.length) return

  processedQueueItems.value.forEach(item => {
    if (item.rawData.end_time && !item.completed) {
      const endTime = new Date(item.rawData.end_time).getTime();
      const currentTime = new Date().getTime();
      const diff = endTime - currentTime;

      if (diff <= 0) {
        // Mining ist abgeschlossen
        item.remainingTime = 0;
        item.formattedTime = '00:00';
        item.completed = true;

        handleTimerComplete(item);
        return;
      }

      // Aktualisiere die verbleibende Zeit
      item.remainingTime = diff;
      item.formattedTime = timeFormat(Math.floor(diff / 1000));
    }
  });

  const hasActiveItems = processedQueueItems.value.some(
    item => !item.completed
  );

  if (!hasActiveItems && timerInterval) {
    clearInterval(timerInterval);
    timerInterval = undefined;
  }
}

function handleTimerComplete(item: ProcessedQueueItem): void {
  queueItemStates.value.delete(item.id);
  saveQueueItemStates(queueItemStates.value);

  setTimeout(() => {
    router.reload();
  }, 3000);
}

let timerInterval: number | undefined
onMounted(() => {
  processQueueData()
  timerInterval = setInterval(updateTimers, 1000)
})

watch(() => rawQueueData.value, () => {
  processQueueData()
}, { deep: true })

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})
</script>

<template>
  <div class="flex items-center gap-2 min-h-10">
    <div v-if="processedQueueItems.length === 0" class="queue-empty text-gray-400 text-xs p-1">
      <span>no active items in queue</span>
    </div>

    <div v-for="item in processedQueueItems" :key="item.id">
      <div @click="toggleInfo(item)"
        class="flex items-center h-10 gap-2 p-1.5 bg-slate-900 rounded-xl cursor-pointer hover:bg-slate-800 transition"
        :class="{ 'fade-in': item.isNew }">
        <img :src="item.image" width="24px" height="24px" alt="Item icon" class="w-6 h-6" />
        <transition name="expand">
          <div class="flex">
            <div v-if="item.showInfos" class="flex flex-col justify-center">
              <h3 class="text-xs font-medium text-white whitespace-nowrap">{{ item.name }}</h3>
              <p class="text-xs text-gray-400 whitespace-nowrap">
                <span v-if="item.rawData.action_type === 'building'">upgrade to lv. {{ item.details }}</span>
                <span v-else-if="item.rawData.action_type === 'produce'">
                  <span>quantity: {{ item.details }}</span>
                </span>
                <span v-else-if="item.rawData.action_type === 'mining'" class="flex justify-between w-full">
                  <span>{{ item.details }}</span>
                </span>
                <span v-else>{{ item.details }}</span>
              </p>
            </div>
            <p class="text-xs text-gray-400 ml-2 self-end">{{ item.formattedTime }}</p>
          </div>

        </transition>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-in {
  animation: slideInFromRight 1s ease-out;
}

@keyframes slideInFromRight {
  from {
    opacity: 0;
    transform: translateX(100vw);
  }

  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>

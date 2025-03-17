<script lang="ts" setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { timeFormat } from '@/Utils/format'

// Typdefinitionen
interface QueueItemDetails {
  building_name?: string;
  spacecraft_name?: string;
  asteroid_name?: string;
  next_level?: number;
  quantity?: number;
}

interface RawQueueItem {
  id: number;
  action_type: 'building' | 'produce' | 'mining' | string;
  details: QueueItemDetails;
  user_id?: number;
  target_id?: number;
  start_time?: string;
  end_time?: string;
  status?: string;
}

interface ProcessedQueueItem {
  id: number;
  name: string;
  image: string;
  details: string | number;
  showInfos: boolean;
  isNew: boolean;
  rawData: RawQueueItem;
  remainingTime?: number;
  formattedTime?: string;
  completed: boolean;
}

const loadSeenItems = (): Set<number> => {
  try {
    const savedItems = localStorage.getItem('seenQueueItems')
    if (savedItems) {
      return new Set(JSON.parse(savedItems))
    }
  } catch (e) {
    console.error('Fehler beim Laden gesehener Items:', e)
  }
  return new Set()
}

const saveSeenItems = (itemIds: Set<number>): void => {
  try {
    localStorage.setItem('seenQueueItems', JSON.stringify(Array.from(itemIds)))
  } catch (e) {
    console.error('Fehler beim Speichern gesehener Items:', e)
  }
}

const seenItemIds = ref<Set<number>>(loadSeenItems())
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
    default:
      return ''
  }
}

const getMiningTime = (item: RawQueueItem): number => {
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

  let hasNewItems = false

  const newItems: ProcessedQueueItem[] = rawQueueData.value.map(item => {
    const isItemNew = !seenItemIds.value.has(item.id)

    if (isItemNew) {
      seenItemIds.value.add(item.id)
      hasNewItems = true
    }

    const processedItem: ProcessedQueueItem = {
      id: item.id,
      name: getNameByActionType(item.action_type, item.details),
      image: getImageByActionType(item.action_type),
      details: getDetailsByActionType(item.action_type, item.details),
      showInfos: false,
      isNew: isItemNew,
      rawData: item,
      completed: false
    }

    // Füge Mining-Zeit für Asteroiden hinzu
    if (item.action_type === 'mining' && item.end_time) {
      const remainingTimeMs = getMiningTime(item)
      processedItem.remainingTime = remainingTimeMs
      processedItem.formattedTime = timeFormat(Math.floor(remainingTimeMs / 1000))

      if (remainingTimeMs <= 0) {
        processedItem.completed = true
        processedItem.formattedTime = '00:00'
      }
    }

    return processedItem
  })

  if (hasNewItems) {
    saveSeenItems(seenItemIds.value)
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
  }
}

const updateMiningTimers = (): void => {
  if (!processedQueueItems.value.length) return

  processedQueueItems.value.forEach(item => {
    if (item.rawData.action_type === 'mining' && item.rawData.end_time && !item.completed) {
      const endTime = new Date(item.rawData.end_time).getTime();
      const now = new Date().getTime();
      const diff = endTime - now;

      if (diff <= 0) {
        // Mining ist abgeschlossen
        item.remainingTime = 0;
        item.formattedTime = '00:00';
        item.completed = true;

        handleMiningComplete(item);
        return;
      }

      // Aktualisiere die verbleibende Zeit
      item.remainingTime = diff;
      item.formattedTime = timeFormat(Math.floor(diff / 1000));
    }
  });

  const hasActiveMiningItems = processedQueueItems.value.some(
    item => item.rawData.action_type === 'mining' && !item.completed
  );

  if (!hasActiveMiningItems && timerInterval) {
    clearInterval(timerInterval);
    timerInterval = undefined;
  }
}

function handleMiningComplete(item) {
  setTimeout(() => {
    router.reload({ only: ['queue', 'userResources'] });
  }, 1000);
}

let timerInterval: number | undefined
onMounted(() => {
  processQueueData()
  timerInterval = setInterval(updateMiningTimers, 1000)
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
  <div class="flex gap-2">
    <div v-if="processedQueueItems.length === 0" class="queue-empty text-gray-400 text-xs p-1">
      <span>no active items in queue</span>
    </div>

    <div v-for="item in processedQueueItems" :key="item.id">
      <div @click="toggleInfo(item)"
        class="flex h-10 gap-2 p-2 bg-slate-900 rounded-xl cursor-pointer hover:bg-slate-800 transition"
        :class="{ 'fade-in': item.isNew }">
        <img :src="item.image" width="24px" height="24px" alt="Item icon" class="queue-item-icon">
        <transition name="expand">
          <div v-if="item.showInfos" class="flex flex-col justify-center">
            <h3 class="text-xs font-medium text-white whitespace-nowrap">{{ item.name }}</h3>
            <p class="text-xs text-gray-400 whitespace-nowrap">
              <span v-if="item.rawData.action_type === 'building'">Upgrade to Level {{ item.details }}</span>
              <span v-else-if="item.rawData.action_type === 'produce'">Quantity: {{ item.details }}</span>
              <span v-else-if="item.rawData.action_type === 'mining'" class="flex justify-between w-full">
                <span>{{ item.details }}</span>
                <span class="ml-4 font-medium">{{ item.formattedTime }}</span>
              </span>
              <span v-else>{{ item.details }}</span>
            </p>
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

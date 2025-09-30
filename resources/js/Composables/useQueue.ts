import { ref, watch } from 'vue'
import { useQueueStore } from '@/Composables/useQueueStore'
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore'
import { useBuildingStore } from '@/Composables/useBuildingStore'
import { useAttributeStore } from './useAttributeStore'
import { useResourceStore } from './useResourceStore'
import { timeFormat } from '@/Utils/format'
import type { RawQueueItem, ProcessedQueueItem, QueueItemDetails } from '@/types/types'
import { api } from '@/Services/api'

const PROCESS_DEBOUNCE = 5000 // ms
const POLL_INTERVAL = 30_000 // 30 Sekunden als Fallback

// ===== Singleton State =====
const queueState = {
    initialized: false,
    timerInterval: undefined as number | undefined,
    pollInterval: undefined as number | undefined,
    debounceTimeout: undefined as number | undefined,
    processRunning: false,
    processTriggerPending: false,
    processingLock: new Set<number>(),
    processedQueueItems: ref<ProcessedQueueItem[]>([])
}

// ===== ActionType Mapping =====
const actionTypeConfig: Record<
    string,
    {
        image: string
        getName: (details?: QueueItemDetails) => string
        getDetails: (details?: QueueItemDetails) => string | number
    }
> = {
    building: {
        image: '/images/navigation/buildings.png',
        getName: d => d?.building_name || 'Building',
        getDetails: d => d?.next_level ?? 1
    },
    produce: {
        image: '/images/navigation/shipyard.png',
        getName: d => d?.spacecraft_name || 'Spacecraft',
        getDetails: d => d?.quantity ?? 1
    },
    mining: {
        image: '/images/navigation/asteroidmap.png',
        getName: () => 'Mining',
        getDetails: d => d?.asteroid_name || 'Asteroid'
    },
    combat: {
        image: '/images/navigation/simulator.png',
        getName: () => 'Combat',
        getDetails: d => `attack ${d?.defender_name || 'Defender'}`
    },
    research: {
        image: '/images/navigation/research.png',
        getName: () => 'Research',
        getDetails: () => ''
    }
}

// ===== Helpers =====
function calculateItemState(rawItem: RawQueueItem): ProcessedQueueItem {
    const config = actionTypeConfig[rawItem.actionType] ?? actionTypeConfig['building']
    const processedItem: ProcessedQueueItem = {
        id: rawItem.id,
        name: config.getName(rawItem.details),
        image: config.image,
        details: config.getDetails(rawItem.details),
        completed: false,
        rawData: rawItem,
        remainingTime: 0,
        formattedTime: '00:00',
        processing: false,
        status: rawItem.status ?? 'pending'
    }

    if (rawItem.endTime) {
        const endTime = new Date(rawItem.endTime).getTime()
        const currentTime = Date.now()
        const diff = Math.max(0, endTime - currentTime)
        processedItem.remainingTime = diff
        processedItem.formattedTime = diff > 0 ? timeFormat(Math.floor(diff / 1000)) : '00:00'
        processedItem.completed = diff <= 0
    }

    return processedItem
}

function clearAllTimers() {
    if (queueState.timerInterval) clearInterval(queueState.timerInterval)
    if (queueState.pollInterval) clearInterval(queueState.pollInterval)
    if (queueState.debounceTimeout) clearTimeout(queueState.debounceTimeout)
    queueState.timerInterval = queueState.pollInterval = queueState.debounceTimeout = undefined
}

// ===== Main Composable =====
export function useQueue() {
    const { queueData, refreshQueue } = useQueueStore()
    const { refreshSpacecrafts } = useSpacecraftStore()
    const { refreshBuildings } = useBuildingStore()
    const { refreshAttributes } = useAttributeStore()
    const { refreshResources } = useResourceStore()

    // ---- Initialization ----
    queueState.timerInterval = window.setInterval(updateTimers, 1000)
    startPollInterval()
    processQueueData()

    watch(queueData, processQueueData, { deep: true })

    // ---- API: Debounced & Locked ----
    function processQueueDebounced() {
        if (queueState.processTriggerPending) return
        queueState.processTriggerPending = true

        queueState.debounceTimeout = window.setTimeout(async () => {
            queueState.debounceTimeout = undefined
            await processQueueLocked()
            queueState.processTriggerPending = false
        }, PROCESS_DEBOUNCE)
    }

    async function processQueueLocked() {
        if (queueState.processRunning) return
        queueState.processRunning = true
        try {
            await api.queue.processQueue()
            await Promise.all([
                refreshQueue(),
                refreshSpacecrafts(),
                refreshBuildings(),
                refreshAttributes(),
                refreshResources()
            ])
        } finally {
            queueState.processRunning = false
        }
    }

    // ---- Timer Handling ----
    function updateItemTimer(item: ProcessedQueueItem) {
        if (!item.rawData.endTime || item.completed || item.status === 'pending') return

        const updated = calculateItemState(item.rawData)
        item.remainingTime = updated.remainingTime
        item.formattedTime = updated.formattedTime
        item.completed = updated.completed

        if (item.completed) {
            item.processing = true
            if (queueState.processingLock.has(item.id)) return

            queueState.processingLock.add(item.id)
            try {
                processQueueDebounced()
            } finally {
                setTimeout(() => queueState.processingLock.delete(item.id), 200)
            }
        }
    }

    function updateTimers() {
        if (!queueState.processedQueueItems.value.length) return
        queueState.processedQueueItems.value.forEach(updateItemTimer)

        const hasActiveItems = queueState.processedQueueItems.value.some(item => !item.completed)
        if (!hasActiveItems && queueState.timerInterval) {
            clearInterval(queueState.timerInterval)
            queueState.timerInterval = undefined
        }
    }

    // ---- Queue Refresh ----
    async function refresh() {
        processQueueData()
        await refreshQueue()
    }

    // ---- Polling ----
    function startPollInterval() {
        stopPollInterval()
        queueState.pollInterval = window.setInterval(async () => {
            await refresh()
        }, POLL_INTERVAL)
    }

    function stopPollInterval() {
        if (queueState.pollInterval) clearInterval(queueState.pollInterval)
        queueState.pollInterval = undefined
    }

    // ---- Data Processing ----
    function processQueueData(): void {
        if (!(queueData.value?.length)) {
            queueState.processedQueueItems.value = []
            return
        }

        const newItems: ProcessedQueueItem[] = []
        queueData.value.forEach(rawItem => {
            newItems.push(calculateItemState(rawItem))
        })

        queueState.processedQueueItems.value = newItems

        // Falls Timer nicht läuft → neu starten
        if (!queueState.timerInterval && newItems.length > 0) {
            queueState.timerInterval = window.setInterval(updateTimers, 1000)
        }
    }

    function removeQueueItem(id: number) {
        queueState.processedQueueItems.value = queueState.processedQueueItems.value.filter(i => i.id !== id)
    }

    return {
        processedQueueItems: queueState.processedQueueItems,
        processQueueData,
        processQueueDebounced,
        startPollInterval,
        stopPollInterval,
        refresh,
        updateTimers,
        updateItemTimer,
        removeQueueItem
    }
}

// ===== Cleanup =====
export function resetQueueSingleton() {
    queueState.initialized = false
    clearAllTimers()
    queueState.processRunning = false
    queueState.processTriggerPending = false
    queueState.processingLock.clear()
    queueState.processedQueueItems.value = []
}

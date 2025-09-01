import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useQueueStore } from '@/Composables/useQueueStore'
import { timeFormat } from '@/Utils/format'
import type { SavedQueueItemState, RawQueueItem, ProcessedQueueItem, QueueItemDetails } from '@/types/types'

function loadQueueItemStates(userId: number): SavedQueueItemState[] {
    const key = `queueItemStates_${userId}`
    const raw = localStorage.getItem(key)
    return raw ? JSON.parse(raw) : []
}

function saveQueueItemStates(userId: number, states: SavedQueueItemState[]) {
    const key = `queueItemStates_${userId}`
    localStorage.setItem(key, JSON.stringify(states))
}

export function useQueue(userId: number) {
    const { queueData } = useQueueStore()
    const queueItemStates = ref<SavedQueueItemState[]>(loadQueueItemStates(userId))
    const processedQueueItems = ref<ProcessedQueueItem[]>([])
    let timerInterval: number | undefined

    // Callback-Handling für Timer-Ende
    const timerCompleteCallbacks: ((item: ProcessedQueueItem) => void)[] = []
    const onTimerComplete = (cb: (item: ProcessedQueueItem) => void) => {
        timerCompleteCallbacks.push(cb)
    }

    let fallbackInterval: number | undefined
    
    const fallbackCheck = () => {
        processedQueueItems.value.forEach(item => {
            if (item.remainingTime <= 0 && !item.timerCompletedFired) {
                console.log('Fallback greift für Item:', item);
                item.completed = true
                item.timerCompletedFired = true
                timerCompleteCallbacks.forEach(cb => cb(item))
            }
        })
    }

    const findState = (id: number) => queueItemStates.value.find(s => s.id === id)
    const upsertState = (state: SavedQueueItemState) => {
        const idx = queueItemStates.value.findIndex(s => s.id === state.id)
        if (idx === -1) queueItemStates.value.push(state)
        else queueItemStates.value[idx] = state
        saveQueueItemStates(userId, queueItemStates.value)
    }
    const removeState = (id: number) => {
        queueItemStates.value = queueItemStates.value.filter(s => s.id !== id)
        saveQueueItemStates(userId, queueItemStates.value)
    }

    const createOrUpdateItemState = (itemId: number): { itemState: SavedQueueItemState; isNew: boolean; hasChanged: boolean } => {
        let itemState = findState(itemId)
        let hasChanged = false

        if (!itemState) {
            itemState = { id: itemId, seen: false, showInfos: false }
            upsertState(itemState)
            hasChanged = true
        }

        const isNew = !itemState.seen
        if (isNew) {
            itemState.seen = true
            hasChanged = true
            upsertState(itemState)
        }

        return { itemState, isNew, hasChanged }
    }

    const getImageByActionType = (actionType: string): string => {
        const images: Record<string, string> = {
            building: '/images/navigation/buildings.png',
            produce: '/images/navigation/shipyard.png',
            mining: '/images/navigation/asteroidmap.png',
            research: '/images/navigation/research.png',
            combat: '/images/navigation/simulator.png',
        }
        return images[actionType] ?? images['building'] ?? ''
    }

    const getNameByActionType = (actionType: string, details?: QueueItemDetails): string => {
        if (!details) return actionType

        if (actionType === 'building') return details.building_name || 'Building'
        if (actionType === 'produce') return details.spacecraft_name || 'Spacecraft'
        if (actionType === 'mining') return 'Mining'
        if (actionType === 'combat') return 'Combat'
        return actionType
    }

    const getDetailsByActionType = (actionType: string, details?: QueueItemDetails): string | number => {
        if (!details) return ''

        if (actionType === 'building') return details.next_level ?? 1
        if (actionType === 'produce') return details.quantity ?? 1
        if (actionType === 'mining') return details.asteroid_name || 'Asteroid'
        if (actionType === 'combat') return `attack ${details.defender_name || 'Defender'}`
        return ''
    }

    const createProcessedItem = (
        rawItem: RawQueueItem,
        itemState: SavedQueueItemState,
        isNew: boolean
    ): ProcessedQueueItem => {
        return {
            id: rawItem.id,
            name: getNameByActionType(rawItem.actionType, rawItem.details),
            image: getImageByActionType(rawItem.actionType),
            details: getDetailsByActionType(rawItem.actionType, rawItem.details),
            showInfos: itemState.showInfos,
            isNew,
            rawData: rawItem,
            completed: false,
            remainingTime: 0,
            formattedTime: '00:00',
            timerCompletedFired: false, // <--- NEU: Flag, damit Callback nur einmal feuert
        }
    }

    // Timer-Logik
    const updateItemTimer = (item: ProcessedQueueItem) => {
        if (!item.rawData.endTime || item.completed) return
        const endTime = new Date(item.rawData.endTime).getTime()
        const currentTime = new Date().getTime()
        const diff = endTime - currentTime
        if (diff <= 0) {
            item.remainingTime = 0
            item.formattedTime = '00:00'
            item.completed = true
            if (!item.timerCompletedFired) {
                item.timerCompletedFired = true
                timerCompleteCallbacks.forEach(cb => cb(item))
            }
            return
        }
        item.remainingTime = diff
        item.formattedTime = timeFormat(Math.floor(diff / 1000))
    }

    const updateTimers = () => {
        if (!processedQueueItems.value.length) return
        processedQueueItems.value.forEach(updateItemTimer)
        const hasActiveItems = processedQueueItems.value.some(item => !item.completed)
        if (!hasActiveItems && timerInterval) {
            clearInterval(timerInterval)
            timerInterval = undefined
        }
    }

    // Items verarbeiten
    const processQueueData = (): void => {
        if (!(queueData.value?.length)) {
            processedQueueItems.value = []
            return
        }

        let hasStateChanges = false
        const newItems: ProcessedQueueItem[] = []

        queueData.value.forEach(rawItem => {
            const { itemState, isNew, hasChanged } = createOrUpdateItemState(rawItem.id)
            hasStateChanges = hasStateChanges || hasChanged

            const processedItem = createProcessedItem(rawItem, itemState, isNew)
            if (rawItem.endTime) {
                updateItemTimer(processedItem)
            }

            if (processedItem.completed && !processedItem.timerCompletedFired) {
                processedItem.timerCompletedFired = true
                timerCompleteCallbacks.forEach(cb => cb(processedItem))
            }
            
            newItems.push(processedItem)
        })

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
        processedQueueItems.value = newItems.filter(item => !(item.completed && item.timerCompletedFired))
    }

    const toggleInfo = (item: ProcessedQueueItem): void => {
        const foundItem = processedQueueItems.value.find(i => i.id === item.id)
        if (foundItem) {
            foundItem.showInfos = !foundItem.showInfos
            const itemState = findState(item.id)
            if (itemState) {
                itemState.showInfos = foundItem.showInfos
                upsertState(itemState)
            }
        }
    }

    const removeQueueItem = (id: number) => {
        removeState(id)
        processedQueueItems.value = processedQueueItems.value.filter(i => i.id !== id)
    }

    const isDefendCombatAction = (item: RawQueueItem): boolean => {
        return item.actionType === 'combat' && item.details?.defender_id === userId
    }

    // Lifecycle
    onMounted(() => {
        processQueueData()
        timerInterval = setInterval(updateTimers, 1000)
        fallbackInterval = setInterval(fallbackCheck, 5000)
    })
    onUnmounted(() => {
        if (timerInterval) clearInterval(timerInterval)
        if (fallbackInterval) clearInterval(fallbackInterval)
    })

    watch(queueData, processQueueData, { deep: true })

    return {
        queueItemStates,
        processedQueueItems,
        processQueueData,
        updateTimers,
        updateItemTimer,
        toggleInfo,
        removeQueueItem,
        isDefendCombatAction,
        onTimerComplete, // <--- Callback registrieren
    }
}

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
const localQueueData = ref<RawQueueItem[]>([])
const rawQueueData = computed<RawQueueItem[]>(() => {
    return [...(page.props.queue || []), ...localQueueData.value]
})

const processedQueueItems = ref<ProcessedQueueItem[]>([])

const getImageByActionType = (actionType: string): string => {
    switch (actionType) {
        case 'building':
            return '/images/navigation/buildings.png'
        case 'produce':
            return '/images/navigation/shipyard.png'
        case 'mining':
            return '/images/navigation/asteroidmap.png'
        case 'research':
            return '/images/navigation/research.png'
        case 'combat':
            return '/images/navigation/simulator.png'
        default:
            return '/images/navigation/buildings.png'
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
    if (!item.endTime) return 0

    const endTime = new Date(item.endTime).getTime()
    const currentTime = new Date().getTime()

    return Math.max(0, endTime - currentTime)
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

const createOrUpdateItemState = (
    itemId: number,
    existingStates: Map<number, SavedQueueItemState>
): { itemState: SavedQueueItemState; isNew: boolean; hasChanged: boolean } => {
    let itemState = existingStates.get(itemId)
    let hasChanged = false

    if (!itemState) {
        itemState = {
            id: itemId,
            seen: false,
            showInfos: false
        }
        existingStates.set(itemId, itemState)
        hasChanged = true
    }

    const isNew = !itemState.seen
    if (isNew) {
        itemState.seen = true
        hasChanged = true
    }

    return { itemState, isNew, hasChanged }
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
        formattedTime: '00:00'
    }
}

const processQueueData = (): void => {
    if (!rawQueueData.value?.length) {
        processedQueueItems.value = []
        return
    }

    let hasStateChanges = false
    const newItems: ProcessedQueueItem[] = []

    // Items verarbeiten
    rawQueueData.value.forEach(rawItem => {
        // State Management
        const { itemState, isNew, hasChanged } = createOrUpdateItemState(
            rawItem.id,
            queueItemStates.value
        )
        hasStateChanges = hasStateChanges || hasChanged

        // Item Verarbeitung
        const processedItem = createProcessedItem(rawItem, itemState, isNew)

        // Timer Initialisierung
        if (rawItem.endTime) {
            updateItemTimer(processedItem)
        }

        newItems.push(processedItem)
    })

    // State Speicherung
    if (hasStateChanges) {
        saveQueueItemStates(queueItemStates.value)
    }

    // Animation Handler
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

const updateItemTimer = (item: ProcessedQueueItem): void => {
    if (!item.rawData.endTime || item.completed) return

    const endTime = new Date(item.rawData.endTime).getTime()
    const currentTime = new Date().getTime()
    const diff = endTime - currentTime

    if (diff <= 0) {
        item.remainingTime = 0
        item.formattedTime = '00:00'
        item.completed = true
        handleTimerComplete(item)
        return
    }

    item.remainingTime = diff
    item.formattedTime = timeFormat(Math.floor(diff / 1000))
}

const updateTimers = (): void => {
    if (!processedQueueItems.value.length) return

    processedQueueItems.value.forEach(updateItemTimer)

    const hasActiveItems = processedQueueItems.value.some(item => !item.completed)

    if (!hasActiveItems && timerInterval) {
        clearInterval(timerInterval)
        timerInterval = undefined
    }
}

function handleTimerComplete(item: ProcessedQueueItem): void {
    queueItemStates.value.delete(item.id);
    saveQueueItemStates(queueItemStates.value);

    /* attacker */
    if (item.rawData.targetId !== page.props.auth.user.id) {
        router.patch(route('queue.process'), {
            preserveState: true,
            preserveScroll: true,
        })
    } else {
        /* defender */
        localQueueData.value = localQueueData.value.filter(i => i.id !== item.rawData.id)
        setTimeout(() => {
            router.reload()
        }, 1000)
    }
}

const isDefendCombatAction = (item: RawQueueItem): boolean => {
    return item.actionType === 'combat' && item.details?.defender_id === page.props.auth.user.id
}

let timerInterval: number | undefined
onMounted(() => {
    processQueueData()
    timerInterval = setInterval(updateTimers, 1000)

    window.Echo.private(`user.combat.${page.props.auth.user.id}`)
        .listen('.user.attacked', (data) => {
            console.log('Angriff erkannt:', data);

            if (data.attackData) {
                // Prüfe ob der Angriff bereits in einer der Queues ist
                const existingInServerQueue = page.props.queue?.some(
                    item => item.id === data.attackData.id
                );
                const existingInLocalQueue = localQueueData.value.some(
                    item => item.id === data.attackData.id
                );

                if (!existingInServerQueue && !existingInLocalQueue) {
                    // Nur hinzufügen wenn noch nicht vorhanden
                    localQueueData.value = [...localQueueData.value, data.attackData];
                }
            }
        });
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
    <div class="flex items-center gap-2">
        <div v-if="processedQueueItems.length === 0" class="queue-empty text-gray-400 text-xs px-1">
            <span>no active items in queue</span>
        </div>

        <div v-for="item in processedQueueItems" :key="item.id">
            <div @click="toggleInfo(item)"
                class="flex items-center min-w-24 h-10 gap-2 p-1.5 bg-base-dark rounded-lg cursor-pointer hover:bg-base transition"
                :class="{ 'fade-in': item.isNew, 'bg-red-900 hover:bg-red-800': isDefendCombatAction(item.rawData) }">
                <img :src="item.image" width="24px" height="24px" alt="Item icon" class="w-6 h-6" />
                <transition name="expand">
                    <div class="flex">
                        <div v-if="item.showInfos" class="flex flex-col justify-center">
                            <h3 class="text-xs font-medium text-white whitespace-nowrap">{{ item.name }}</h3>
                            <p class="text-xs text-gray-400 whitespace-nowrap">
                                <span v-if="item.rawData.actionType === 'building'">upgrade to lv. {{ item.details
                                }}</span>
                                <span v-else-if="item.rawData.actionType === 'produce'">
                                    <span>quantity: {{ item.details }}</span>
                                </span>
                                <span v-else-if="item.rawData.actionType === 'mining'"
                                    class="flex justify-between w-full">
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

<script lang="ts" setup>
import { usePage, router } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { useQueue } from '@/Composables/useQueue'
import type { ProcessedQueueItem } from '@/types/types'

declare global {
    interface Window {
        Echo: any
    }
}

const page = usePage()
const userId = page.props.auth.user.id

const {
    processedQueueItems,
    toggleInfo,
    removeQueueItem,
    isDefendCombatAction,
    onTimerComplete,
} = useQueue(userId)

function handleTimerComplete(item: ProcessedQueueItem): void {
    removeQueueItem(item.id)
    router.patch(route('queue.process'), {
        preserveState: true,
        preserveScroll: true,
    })
}

onMounted(() => {
    onTimerComplete(handleTimerComplete)
    window.Echo.private(`user.combat.${userId}`)
        .listen('.user.attacked', () => {
            router.reload({ only: ['queue'] });
        })
})
</script>

<template>
    <div class="flex items-center gap-2">
        <div v-if="processedQueueItems.length === 0" class="queue-empty text-gray-400 text-xs px-1">
            <span>no active items in queue</span>
        </div>
        <div v-for="item in processedQueueItems" :key="item.id">
            <div @click="toggleInfo(item)"
                class="flex items-center min-w-20 h-10 gap-2 p-1.5 bg-base-dark rounded-lg cursor-pointer hover:bg-base transition"
                :class="{ 'fade-in': item.isNew, 'bg-red-900 hover:bg-red-800': isDefendCombatAction(item.rawData) }">
                <img :src="item.image" width="20px" height="20px" alt="Item icon" class="w-5 h-5" />
                <transition name="expand">
                    <div class="flex">
                        <div v-if="item.showInfos" class="flex flex-col justify-center">
                            <h3 class="text-xs font-medium text-white whitespace-nowrap">{{ item.name }}</h3>
                            <p class="text-xs text-gray-400 whitespace-nowrap">
                                <span v-if="item.rawData.actionType === 'building'">upgrade to lv. {{ item.details }}</span>
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

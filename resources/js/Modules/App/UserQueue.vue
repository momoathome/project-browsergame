<script lang="ts" setup>
import { usePage, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
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
  refresh,
  processQueueThrottled, // throttled API call
  startProcessInterval,
  stopProcessInterval
} = useQueue(userId)

onMounted(async () => {
  // wenn Timer von einem Item endet → Composable triggert Refresh selbst
  onTimerComplete(async (item) => {
    //
  })

  await refresh()

  window.Echo.private(`user.combat.${userId}`)
    .listen('.user.attacked', refresh)

  // Falls schon aktive Items → sofort manuell anstoßen
  if (processedQueueItems.value.some(i => i.processing && i.rawData.actionType !== 'mining')) {
    await processQueueThrottled()
  }

  startProcessInterval()
})

onUnmounted(() => {
  stopProcessInterval()
})

const sortedQueueItems = computed(() =>
  processedQueueItems.value.slice().sort((a, b) => {
    const statusOrder = { in_progress: 0, processing: 1, pending: 2 }
    return statusOrder[a.status] - statusOrder[b.status]
  })
)

const miningItems = computed(() =>
    sortedQueueItems.value.filter(item => item.rawData.actionType === 'mining')
)

const visibleMiningItems = computed(() => {
  const notProcessing = miningItems.value
    .filter(item => !item.processing)
    .sort((a, b) => a.rawData.endTime - b.rawData.endTime)
  return notProcessing.length
    ? [notProcessing[0]]
    : miningItems.value.length
      ? [miningItems.value[0]]
      : []
})

const defendItems = computed(() =>
    sortedQueueItems.value.filter(item => isDefendCombatAction(item.rawData))
)

const nonMiningNonDefendItems = computed(() =>
    sortedQueueItems.value.filter(
        item => item.rawData.actionType !== 'mining' && !isDefendCombatAction(item.rawData)
    )
)
</script>

<template>
    <div class="flex items-center gap-2">
        <div v-if="processedQueueItems.length === 0" class="queue-empty text-gray-400 text-xs px-1">
            <span>no active items in queue</span>
        </div>

        <div v-for="item in defendItems" :key="item.id">
            <div @click="toggleInfo(item)"
                class="flex items-center min-w-20 h-10 gap-2 p-1.5 bg-red-900 rounded-lg cursor-pointer hover:bg-red-800 transition"
                :class="{ 'fade-in': item.isNew }">
                <img :src="item.image" width="20px" height="20px" alt="Defend icon" class="w-5 h-5" />
                <transition name="expand">
                    <div class="flex">
                        <div v-if="item.showInfos" class="flex flex-col justify-center">
                            <h3 class="text-xs font-medium text-white whitespace-nowrap">{{ item.name }}</h3>
                            <p class="text-xs text-gray-400 whitespace-nowrap">{{ item.details }}</p>
                        </div>
                        <p class="text-xs text-gray-400 ml-2 self-end">
                            <span v-if="item.status === 'pending'">pending...</span>
                            <span v-else-if="item.status === 'processing'">processing...</span>
                            <span v-else>{{ item.formattedTime }}</span>
                        </p>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Alle anderen Items wie gehabt -->
        <div v-for="item in nonMiningNonDefendItems" :key="item.id">
            <div @click="toggleInfo(item)"
                class="flex items-center min-w-20 h-10 gap-2 p-1.5 bg-base-dark rounded-lg cursor-pointer hover:bg-base transition">
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
                                <span v-else>{{ item.details }}</span>
                            </p>
                        </div>
                        <p class="text-xs text-gray-400 ml-2 self-end">
                            <span v-if="item.status === 'pending'">pending...</span>
                            <span v-else-if="item.status === 'processing'">processing...</span>
                            <span v-else>{{ item.formattedTime }}</span>
                        </p>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Mining-Items gestapelt anzeigen -->
        <template v-if="miningItems.length">
            <div class="flex items-center">
                <div v-for="(item, idx) in visibleMiningItems" :key="item.id">
                    <div @click="toggleInfo(item)"
                        class="flex items-center min-w-20 h-10 gap-2 p-1.5 bg-base-dark rounded-l-lg cursor-pointer hover:bg-base transition"
                            :class="[
                                miningItems.length > 1 ? 'rounded-l-lg' : 'rounded-lg',
                                item.isNew ? 'fade-in' : ''
                            ]">
                        <img :src="item.image" width="20px" height="20px" alt="Mining icon" class="w-5 h-5" />
                        <div class="flex">
                            <div v-if="item.showInfos" class="flex flex-col justify-center">
                                <h3 class="text-xs font-medium text-white whitespace-nowrap">{{ item.name }}</h3>
                                <p class="text-xs text-gray-400 whitespace-nowrap">{{ item.details }}</p>
                            </div>
                            <p class="text-xs text-gray-400 ml-2 self-end">
                                <span v-if="item.status === 'pending'">pending...</span>
                                <span v-else-if="item.status === 'processing'">processing...</span>
                                <span v-else>{{ item.formattedTime }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Stack-Anzeige für weitere Mining-Items -->
                <div v-if="miningItems.length > 1" class="mining-stack-indicator flex items-center h-10 text-xs pr-2 py-2 bg-base-dark text-gray-500 rounded-r-lg">
                    +{{ miningItems.length - 1 }} more
                </div>
            </div>
        </template>
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

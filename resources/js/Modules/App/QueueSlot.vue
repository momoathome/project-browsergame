<script lang="ts" setup>
const props = defineProps<{
  activeItem: any
  icon: string
  emptyIcon: string
  slotNumber: number
  title: string
  subtitle?: string
  pendingCount?: number
  transitionName?: string
  displayQueueTime: (item: any) => string
}>()

const pendingCount = props.pendingCount ?? 0
</script>

<template>
  <div class="bg-root rounded-lg w-full min-h-[56px]">
    <div class="border border-white/10 border-dashed h-full rounded-md flex gap-2 justify-center items-center relative">
      <transition :name="transitionName" mode="out-in">
        <div v-if="activeItem" class="w-full h-full flex">
          <div class="flex items-center space-x-3 rounded-md px-3 w-full">
            <img :src="icon" alt="type icon" class="h-6 w-6" />
            <div class="flex-1">
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium leading-none">
                  {{ title }}
                </p>
                <span class="text-sm">{{ displayQueueTime(activeItem) }}</span>
              </div>
              <p v-if="subtitle" class="text-sm text-muted-foreground flex items-center gap-1">
                {{ subtitle }}
              </p>
            </div>
            <template v-if="pendingCount > 0">
              <span class="absolute bottom-1 right-2 bg-primary/50 text-light rounded-full px-2 py-1 text-xs font-medium">
                +{{ pendingCount }}
              </span>
            </template>
          </div>
        </div>
        <div v-else class="w-full h-full flex gap-2 justify-center items-center">
          <img :src="emptyIcon" class="opacity-40" width="26" height="26" alt="">
          <p class="text-gray-500 font-semibold">Slot {{ slotNumber }}</p>
        </div>
      </transition>
    </div>
  </div>
</template>

<style scoped>
.fadePulse-enter-active {
  animation: pulse 1s ease;
}
.fadePulse-leave-active {
  animation: pulse 1s reverse ease;
}
.fadePulse-enter-from,
.fadePulse-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
.fadePulse-enter-to,
.fadePulse-leave-from {
  opacity: 1;
  transform: scale(1.10);
}

@keyframes pulse {
  0% {
    opacity: 0;
    transform: scale(0.95);
  }
  50% {
    opacity: 1;
    transform: scale(1.05);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}
</style>

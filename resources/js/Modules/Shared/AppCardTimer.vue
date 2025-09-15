<script lang="ts" setup>
import { timeFormat } from '@/Utils/format';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps<{
  buildTime: number,
  description: string,
  endTime?: string | null,
  isInProgress?: boolean
  disabled?: boolean
}>();

const emit = defineEmits<{
  'cancel-upgrade': []
}>();

const remainingTime = ref(0);
const progress = ref(0);
const timer = ref();

const totalDuration = computed(() => props.buildTime * 1000);

function updateTimerAndProgress() {
  if (!props.isInProgress || !props.endTime) {
    remainingTime.value = 0;
    progress.value = 0;
    return;
  }

  const endTime = new Date(props.endTime).getTime();
  const now = new Date().getTime();
  const diff = endTime - now;

  if (diff <= 0) {
    remainingTime.value = 0;
    progress.value = 100;
    clearInterval(timer.value);
    return;
  }

  remainingTime.value = diff;

  // Berechne die verstrichene Zeit und den Fortschritt
  const startTime = endTime - totalDuration.value;
  const elapsed = now - startTime;
  progress.value = Math.min(100, Math.floor((elapsed / totalDuration.value) * 100));
}

const formattedRemainingTime = computed(() => {
  if (!props.isInProgress) return timeFormat(props.buildTime);

  if (remainingTime.value <= 0) return '00:00';

  const remainingSeconds = Math.floor(remainingTime.value / 1000);
  return timeFormat(remainingSeconds);
});

function startTimer() {
  updateTimerAndProgress();
  if (!timer.value) {
    timer.value = setInterval(updateTimerAndProgress, 1000);
  }
}

function stopTimer() {
  if (timer.value) {
    clearInterval(timer.value);
    timer.value = undefined;
  }
}

onMounted(() => {
  if (props.isInProgress && props.endTime) {
    startTimer();
  }
});

onUnmounted(() => {
  stopTimer();
});

watch(() => props.isInProgress, (newValue) => {
  if (newValue && props.endTime) {
    startTimer();
  } else {
    stopTimer();
    progress.value = 0; // Fortschritt zurücksetzen!
    remainingTime.value = 0;
  }
});

watch(() => props.endTime, (newValue) => {
  if (newValue && props.isInProgress) {
    updateTimerAndProgress();
    if (!timer.value) {
      timer.value = setInterval(updateTimerAndProgress, 1000);
    }
  } else if (!newValue && timer.value) {
    clearInterval(timer.value);
  }
});

const isHovered = ref(false);

function handleCancelUpgrade() {
  if (!props.isInProgress) {
    return;
  }
  emit('cancel-upgrade');
}
</script>

<template>
  <div class="flex items-center rounded-bl-xl border-solid border-t border-r-2 border-primary/50 bg-primary/40"
    :class="{ 'opacity-40': disabled }">
    <div
      @click="handleCancelUpgrade"
      class="flex items-center h-11 overflow-hidden transition rounded-bl-xl relative gap-2 min-w-28 max-w-1/2 py-2 px-2"
      :class="{ 'bg-red-600 cursor-pointer': isInProgress && isHovered }" @mouseenter="isHovered = true"
      @mouseleave="isHovered = false">
      <svg v-if="!isHovered || !isInProgress" xmlns="http://www.w3.org/2000/svg"
        class="relative z-10 text-[#AA9C87] transition-all duration-300" :class="{ '!text-white': isInProgress }"
        width="24" height="28" viewBox="0 0 24 24">
        <path fill="currentColor"
          d="M5.198 3.3C5.8 2 7.867 2 12 2c4.133 0 6.2 0 6.802 1.3c.052.11.095.227.13.346c.41 1.387-1.052 2.995-3.974 6.21L13 12l1.958 2.143c2.922 3.216 4.383 4.824 3.974 6.21a2.51 2.51 0 0 1-.13.348C18.2 22 16.133 22 12 22c-4.133 0-6.2 0-6.802-1.3a2.524 2.524 0 0 1-.13-.346c-.41-1.387 1.052-2.995 3.974-6.21L11 12L9.042 9.857C6.12 6.64 4.66 5.033 5.068 3.647a2.46 2.46 0 0 1 .13-.348Z" />
      </svg>
      <svg v-if="isHovered && isInProgress" xmlns="http://www.w3.org/2000/svg"
        class="text-light relative z-10 transition-all duration-300" width="24" height="24" viewBox="0 0 24 24">
        <path fill="currentColor"
          d="m12 13.4l2.9 2.9q.275.275.7.275t.7-.275t.275-.7t-.275-.7L13.4 12l2.9-2.9q.275-.275.275-.7t-.275-.7t-.7-.275t-.7.275L12 10.6L9.1 7.7q-.275-.275-.7-.275t-.7.275t-.275.7t.275.7l2.9 2.9l-2.9 2.9q-.275.275-.275.7t.275.7t.7.275t.7-.275zm0 8.6q-2.075 0-3.9-.788t-3.175-2.137T2.788 15.9T2 12t.788-3.9t2.137-3.175T8.1 2.788T12 2t3.9.788t3.175 2.137T21.213 8.1T22 12t-.788 3.9t-2.137 3.175t-3.175 2.138T12 22" />
      </svg>
      <div class="flex flex-col justify-center relative z-10"
        :class="{ '': isInProgress }">
        <p class="font-medium text-sm text-light">{{ formattedRemainingTime }}</p>
        <p v-show="isInProgress" class="font-semibold text-xs text-light">{{ description }}</p>
      </div>
      <div class="absolute top-0 transition left-0 h-full w-full bg-secondary rounded-bl-xl progress-bar"
        :class="{ '!bg-red-600': isInProgress && isHovered }"
        :style="{ width: progress + '%' }">
      </div>
    </div>
  </div>
</template>

<style scoped>
.progress-bar {
  /* animation: progressAnimation 3s forwards; */
  transition: width color 1s linear;
  transform: translateZ(0);
  will-change: width;
}

/* @keyframes progressAnimation {
  0% {
    width: 0%;
  }
  100% {
    width: v-bind(progress + '%');
  }
} */
</style>

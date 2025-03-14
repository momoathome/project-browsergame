<script lang="ts" setup>
import { timeFormat } from '@/Utils/format';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps<{
  buildTime: number,
  description: string,
  endTime?: string | null,
  isInProgress?: boolean
}>();

const emit = defineEmits<{
  'upgrade-complete': []
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

    emit('upgrade-complete');
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

</script>

<template>
  <div class="flex gap-1">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
      <path fill="#AA9C78"
        d="M5.198 3.3C5.8 2 7.867 2 12 2c4.133 0 6.2 0 6.802 1.3c.052.11.095.227.13.346c.41 1.387-1.052 2.995-3.974 6.21L13 12l1.958 2.143c2.922 3.216 4.383 4.824 3.974 6.21a2.51 2.51 0 0 1-.13.348C18.2 22 16.133 22 12 22c-4.133 0-6.2 0-6.802-1.3a2.524 2.524 0 0 1-.13-.346c-.41-1.387 1.052-2.995 3.974-6.21L11 12L9.042 9.857C6.12 6.64 4.66 5.033 5.068 3.647a2.46 2.46 0 0 1 .13-.348Z" />
    </svg>
    <div class="flex flex-col flex-1">
      <div class="flex justify-between">
        <p class="font-medium text-sm">{{ description }}</p>
        <p class="font-medium text-sm">{{ formattedRemainingTime }}</p>
      </div>
      <div v-if="isInProgress" class="rounded-lg h-2 bg-primary">
        <div 
          class="h-2 rounded-lg bg-secondary progress-bar" 
          :style="{ width: progress + '%' }"
        >
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.progress-bar {
  /* animation: progressAnimation 3s forwards; */
  transition: width 1s linear;
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

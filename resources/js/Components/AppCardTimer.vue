<script lang="ts" setup>
import { timeFormat } from '@/Utils/format';
import { ref} from 'vue';

const props = defineProps<{
  time: number
  progress?: number
  description: string
}>()

const progress = ref(85)

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
        <p class="font-medium text-sm">{{ timeFormat(time) }}</p>
      </div>
      <div class="rounded-lg h-2 bg-primary">
        <div class="progress-bar h-2 rounded-lg bg-secondary transition transition-duration-300">
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.progress-bar {
  animation: progressAnimation 3s forwards;
}
@keyframes progressAnimation {
  0% {
    width: 0%;
  }
  100% {
    width: v-bind(progress + '%');
    /* width: 85%; */
  }
}
</style>

<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';

defineProps<{
  title: string;
  iconSrc: string;
  route?: string;
  isPrimary?: boolean;
}>();
</script>

<template>
  <div v-if="route" 
       :class="[
         'flex group items-center gap-4 px-4 py-2 transition-colors', 
         isPrimary ? 'bg-base-dark hover:bg-base-dark/60 rounded-t-xl' : ''
       ]">
    <Link :href="route" class="flex group items-center gap-4 w-full">
      <img :src="iconSrc" :alt="`${title} Image`" class="w-12 h-12">
      <span class="font-semibold text-2xl text-light">{{ title }}</span>
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
        class="group-hover:text-light text-transparent transition-colors" viewBox="0 0 24 24">
        <path fill="currentColor"
          d="M16.15 13H5q-.425 0-.712-.288T4 12t.288-.712T5 11h11.15L13.3 8.15q-.3-.3-.288-.7t.288-.7q.3-.3.713-.312t.712.287L19.3 11.3q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.575 4.575q-.3.3-.712.288t-.713-.313q-.275-.3-.288-.7t.288-.7z" />
      </svg>
    </Link>
  </div>
  <div v-else class="flex items-center gap-4 px-4 py-2">
    <img v-if="iconSrc" :src="iconSrc" :alt="`${title} Image`" class="w-12 h-12">
    <span class="font-semibold text-2xl text-light">{{ title }}</span>
  </div>
</template>

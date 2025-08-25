<script lang="ts" setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import type { Asteroid } from '@/types/types';

interface SimpleAsteroid {
  id: number;
  name: string;
}

const props = defineProps<{
  searchedAsteroids: SimpleAsteroid[]
  selectedAsteroid?: Asteroid 
}>()

const emit = defineEmits(['selectAsteroid']);

const isDropdownOpen = ref(false);
const highlightedIndex = ref(0);
const dropdownListRef = ref<HTMLUListElement | null>(null);
const itemRefs = ref<(HTMLElement | null)[]>([]);

watch(isDropdownOpen, (open) => {
  if (open) {
    highlightedIndex.value = 0;
    // Fokus nach dem nächsten Tick setzen
    nextTick(() => {
      dropdownListRef.value?.focus();
    });
  }
});

watch(highlightedIndex, (newIdx) => {
  nextTick(() => {
    const el = itemRefs.value[newIdx];
    if (el) {
      el.scrollIntoView({ block: "nearest" });
    }
  });
});

function onKeyDown(e: KeyboardEvent) {
  if (!isDropdownOpen.value) return;

  if (e.key === 'ArrowDown') {
    highlightedIndex.value = (highlightedIndex.value + 1) % props.searchedAsteroids.length;
    e.preventDefault();
  } else if (e.key === 'ArrowUp') {
    highlightedIndex.value = (highlightedIndex.value - 1 + props.searchedAsteroids.length) % props.searchedAsteroids.length;
    e.preventDefault();
  } else if (e.key === 'Enter') {
    selectAsteroid(props.searchedAsteroids[highlightedIndex.value]);
    e.preventDefault();
  } else if (e.key === 'Escape') {
    isDropdownOpen.value = false;
    e.preventDefault();
  }
}

function toggleDropdown() {
  isDropdownOpen.value = !isDropdownOpen.value;
}

function selectAsteroid(asteroid) {
  emit('selectAsteroid', asteroid);
  isDropdownOpen.value = false;
}

function closeDropdown(event) {
  if (isDropdownOpen.value && !event.target?.closest('.dropdown-container')) {
    isDropdownOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
});

</script>

<template>
  <div class="dropdown-container">
    <button @click="toggleDropdown" @keydown="onKeyDown"
      class="bg-root flex items-center justify-between text-light text-start text-nowrap ps-3 pe-1 py-2 w-full rounded-md ring-[#bfbfbf] border border-[#6b7280]">
      {{ selectedAsteroid ? selectedAsteroid.name : searchedAsteroids[0].name }}
      <span>
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24">
          <path fill="currentColor"
            d="M11.475 14.475L7.85 10.85q-.075-.075-.112-.162T7.7 10.5q0-.2.138-.35T8.2 10h7.6q.225 0 .363.15t.137.35q0 .05-.15.35l-3.625 3.625q-.125.125-.25.175T12 14.7t-.275-.05t-.25-.175" />
        </svg>
      </span>
    </button>
    <ul v-if="isDropdownOpen"
      tabindex="0" 
      @keydown="onKeyDown"
      ref="dropdownListRef"
      class="absolute top-full left-0 mt-1 max-h-48 w-full overflow-y-auto bg-root rounded-md py-2 no-scrollbar list-none ring-[#bfbfbf] border border-[#6b7280]">
      <li v-for="(asteroid, index) in searchedAsteroids"
        @click="selectAsteroid(asteroid)" 
        :key="asteroid.name"
        :ref="el => itemRefs[index] = el as HTMLElement"
        class="py-1 px-3 cursor-pointer text-light text-sm text-nowrap hover:bg-slate-900"
        :class="{'bg-slate-900': highlightedIndex === index}">
        <span>
          {{ asteroid.name }}
        </span>
<!--         <span>
          {{ asteroid.resources }}
        </span> -->
      </li>
    </ul>
  </div>
</template>

<style scoped>
ul::-webkit-scrollbar-track {
  border-radius: 16px;
  background-color: hsl(263, 45%, 7%);
}

ul::-webkit-scrollbar {
  width: 3px;
  background-color: hsl(263, 45%, 7%);
}

ul::-webkit-scrollbar-thumb {
  border-radius: 10px;
  -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, .3);
  background-color: #bfbfbf;
}
</style>

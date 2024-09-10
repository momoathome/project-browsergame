<script lang="ts" setup>
import { ref } from 'vue'

const items = ref([
  { id: 1, name: 'Shipyard', image: '/storage/navigation/buildings.png', level: 5, showInfos: true, isNew: false },
  { id: 2, name: 'Merlin', image: '/storage/navigation/shipyard.png', level: 7, showInfos: false, isNew: false },
  { id: 3, name: 'Mining', image: '/storage/navigation/asteroidmap.png', level: 3, showInfos: false, isNew: false },
  //...
])

function toggleInfo(item) {
  item.showInfos = !item.showInfos
}

function addItem() {
  const newId = items.value.length + 1
  items.value.push({
    id: newId,
    name: `New Item ${newId}`,
    image: '/storage/navigation/shipyard.png',
    level: 1,
    showInfos: false,
    isNew: true
  })
  setTimeout(() => {
    items.value[items.value.length - 1].isNew = false
  }, 1000)
}
</script>

<template>
  <div v-for="item in items" :key="item.id">
    <div @click="toggleInfo(item)"
      class="h-10 flex gap-2 p-2 bg-slate-900 rounded-xl cursor-pointer hover:bg-slate-800 transition"
      :class="{ 'fade-in': item.isNew }">
      <img :src="item.image" alt="buildings">
      <transition name="expand">
        <div v-if="item.showInfos" class="flex flex-col justify-center">
          <h3 class="text-xs font-medium text-white whitespace-nowrap">{{ item.name }}</h3>
          <p class="text-xs text-gray-400 whitespace-nowrap">upgrade to lv. {{ item.level }}</p>
        </div>
      </transition>
    </div>
  </div>
  <div @click="addItem" class="h-10 flex items-center justify-center p-2 bg-slate-900 rounded-xl cursor-pointer hover:bg-slate-800 transition">
    <span class="text-2xl text-white">+</span>
  </div>
</template>

<style scoped>
.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease-out;
  max-width: 200px;
}

.expand-enter-from,
.expand-leave-to {
  max-width: 0;
  opacity: 0;
}

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

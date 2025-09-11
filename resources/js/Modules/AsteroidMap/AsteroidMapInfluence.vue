<script setup lang="ts">
import { ref, computed } from "vue";

const props = defineProps<{
  influenceOfAllUsers: { user_id: number; attribute_value: string; name: string }[];
  show: boolean;
}>();

const emit = defineEmits<{
    (e: "toggle"): void;
    (e: "focus-player", userId: number): void;
}>();

const search = ref("");

const sortedPlayers = computed(() => {
  return [...props.influenceOfAllUsers]
    .sort((a, b) => parseFloat(b.attribute_value) - parseFloat(a.attribute_value))
    .filter((player) =>
      player.name.toLowerCase().includes(search.value.toLowerCase())
    )
    .slice(0, 10);
});
</script>

<template>
  <div
    class="fixed right-0 top-[104px] h-[calc(100vh-104px)] flex z-100 transition-transform duration-300"
    :style="show ? 'transform: translateX(0)' : 'transform: translateX(calc(100% - 64px))'"
  >
      <!-- Toggle Button (immer sichtbar, fährt mit dem Container) -->
    <button
      class="h-16 w-16 px-3 mt-28 flex items-center justify-center border border-r-0 border-[#6b7280]/40 bg-root text-white rounded-l-md hover:bg-[hsl(263,20%,8%)]"
      @click="emit('toggle')"
    >
        <img v-show="!show" src="/images/attributes/influence.png" alt="Toggle Influence" class="h-8 w-8" />
        <svg v-show="show" xmlns="http://www.w3.org/2000/svg" width="32" height="32" class="text-slate-200" viewBox="0 0 24 24">
            <path fill="currentColor" d="M16.95 8.464a1 1 0 0 0-1.414-1.414L12 10.586L8.464 7.05A1 1 0 1 0 7.05 8.464L10.586 12L7.05 15.536a1 1 0 1 0 1.414 1.414L12 13.414l3.536 3.536a1 1 0 1 0 1.414-1.414L13.414 12z"/>
        </svg>
    </button>

    <!-- Sidebar -->
    <div
      class="w-60 xl:w-72 bg-root text-light p-4 fancy-scroll overflow-y-auto"
    >
      <div class="flex items-center mb-6 gap-3">
        <img src="/images/attributes/influence.png" alt="Influence" class="h-8 w-8" />
        <h2 class="text-xl font-semibold">Influence</h2>
      </div>

      <!-- Suchfeld -->
      <input
        type="text"
        v-model="search"
        placeholder="Search player..."
        id="influence-search"
        class="w-full mb-4 px-3 py-2 rounded-md bg-base/20 border border-white/10 text-sm ring-[#bfbfbf] focus:border-primary/20 focus:outline-none focus:ring-2 focus:ring-primary/20 transition"
      />

      <!-- Ranking Liste -->
      <div
        v-for="(player, index) in sortedPlayers"
        :key="player.user_id"
        class="flex items-center justify-between p-2 rounded-md hover:bg-primary/10 cursor-pointer"
        @click="emit('focus-player', player.user_id)"
      >
        <div class="flex items-center gap-2">
          <span class="text-sm text-muted w-4 text-right">{{ index + 1 }}.</span>
          <span class="font-medium">{{ player.name }}</span>
        </div>
        <span class="text-sm text-light">
          {{ Number(player.attribute_value).toLocaleString() }}
        </span>
      </div>

      <p v-if="sortedPlayers.length === 0" class="text-sm text-muted mt-4 text-center">
        No players found.
      </p>
    </div>


  </div>
</template>


<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from {
  transform: translateX(100%);
}
.slide-leave-to {
  transform: translateX(100%);
}
</style>

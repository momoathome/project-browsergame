<script lang="ts" setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import AppHeader from '@/Modules/App/AppHeader.vue';
import AppSidebar from '@/Modules/App/AppSidebar.vue';
import AppSideOverview from '@/Modules/App/AppSideOverview.vue';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';
import { useBuildingStore } from '@/Composables/useBuildingStore';
import { useQueue } from '@/Composables/useQueue'

const props = defineProps<{
  title: string
}>()

declare global {
    interface Window {
        Echo: any
    }
}

const page = usePage()
const userId = page.props.auth?.user?.id ?? null;
const mainRef = ref<HTMLElement | null>(null)
const showSideOverview = ref(true)
let observer: MutationObserver | null = null

const {
  processedQueueItems,
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
  await useSpacecraftStore().refreshSpacecrafts();
  await useBuildingStore().refreshBuildings();

  window.Echo.private(`user.combat.${userId}`)
    .listen('.user.attacked', refresh)

  // Falls schon aktive Items → sofort manuell anstoßen
  if (processedQueueItems.value.some(i => i.processing && i.rawData.actionType !== 'mining')) {
    await processQueueThrottled()
  }

  startProcessInterval()
})

function checkCanvas() {
  showSideOverview.value = !(mainRef.value?.querySelector('canvas'))
}

onMounted(() => {
  checkCanvas()
  observer = new MutationObserver(checkCanvas)
  if (mainRef.value) {
    observer.observe(mainRef.value, { childList: true, subtree: true })
  }
})

onUnmounted(() => {
  stopProcessInterval()
  observer?.disconnect()
})
</script>

<template>
  <div>
    <Head :title="title" />

    <Banner class="absolute bottom-0 right-0 z-50" />

    <div class="layout-grid min-h-screen">

      <!-- Page Heading -->
      <AppHeader class="header" />
      <AppSidebar class="sidebar" />

      <!-- Page Content -->
      <main ref="mainRef" class="background main fancy-scroll">
        <slot />
      </main>

      <AppSideOverview v-if="showSideOverview" class="sideoverview" />

    </div>
  </div>
</template>

<style scoped>
.background {
  background: radial-gradient(circle at 50% 70%, #1c222c, #1b202a, #191f28, #181d26, #161c24, #151a22, #141920, #12171e, #11161c);
  /* background: #151a22; */
}

.layout-grid {  
  display: grid;
  grid-template-columns: 70px 1fr;
  grid-template-rows: auto 1fr;
  grid-template-areas:
    "appheader appheader appheader"
    "sidebar main sideoverview";
  height: 100vh;
  overflow: hidden;
}

.header { 
  grid-area: appheader;
  position: sticky;
  top: 0;
  z-index: 50;
}

.sideoverview {
  grid-area: sideoverview;
  top: 0;
  right: 0;
  height: 100%;
  z-index: 10;
}

.sidebar { 
  grid-area: sidebar;
  position: sticky;
  top: 0;
  height: 100%;
  z-index: 10;
}

.main { 
  grid-area: main;
  overflow-y: auto;
  height: 100%;
  padding: 1.5rem;
}

.main:has(canvas) {
  padding: 0;
}

.fancy-scroll::-webkit-scrollbar { width: 6px; }
.fancy-scroll::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
}
</style>

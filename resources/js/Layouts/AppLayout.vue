<script lang="ts" setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import AppHeader from '@/Modules/App/AppHeader.vue';
import AppSidebar from '@/Modules/App/AppSidebar.vue';

const props = defineProps<{
  title: string
}>()


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
      <main class="background main">
        <slot />
      </main>

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
    "appheader appheader"
    "sidebar main";
  height: 100vh;
  overflow: hidden;
}

.header { 
  grid-area: appheader;
  position: sticky;
  top: 0;
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
  padding: 1rem;
}

.main:has(canvas) {
  padding: 0;
}

</style>

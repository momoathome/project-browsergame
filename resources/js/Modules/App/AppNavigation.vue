<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';

const NavigationLinks = [
  { name: 'overview', label: 'Overview', image: '/storage/navigation/overview.png' },
  { name: 'buildings', label: 'Buildings', image: '/storage/navigation/buildings.png' },
  { name: 'shipyard', label: 'Shipyard', image: '/storage/navigation/shipyard.png' },
  // { name: 'research', label: 'Research', image: '/storage/navigation/research.png' },
  { name: 'asteroidMap', label: 'AsteroidMap', image: '/storage/navigation/asteroidmap.png' },
  { name: 'market', label: 'Market', image: '/storage/navigation/market.png' },
  // { name: 'logbook', label: 'Logbook', image: '/storage/navigation/logbook.png' },
  { name: 'simulator', label: 'Simulator', image: '/storage/navigation/simulator.png' },
];
</script>

<template>
  <div class="w-fit rounded-e-lg">
    <nav class="flex flex-col items-center">
      <Link v-for="link in NavigationLinks" :key="link.name" :href="route(link.name)"
        :class="[route().current(link.name) ? 'border-opacity-100 ' : '']"
        class="relative group inline-flex items-center w-full p-2 2xl:px-4 2xl:py-3 hover:border-opacity-100 transition border-l-2 border-opacity-0 border-l-[#bfbfbf]">
      <img :src="link.image" class="w-6 h-6 2xl:w-8 2xl:h-8" alt="" />

      <AppTooltip :label="link.label" position="right" />
      </Link>

      <!-- Admin Dashboard -->
      <Link
        class="group inline-flex items-center p-2 2xl:px-4 2xl:py-3 hover:border-opacity-100 transition active:border-opacity-100 border-l-2 border-opacity-0 border-l-[#bfbfbf]"
        :href="route('admin.dashboard')"
        :class="[route().current('admin.dashboard') ? 'border-opacity-100 ' : '']">
      <img src="/storage/navigation/dashboard.png" class="w-6 h-6 2xl:w-8 2xl:h-8" alt="Dashboard" />

      <AppTooltip :label="'Dashboard'" position="right" />
      </Link>
    </nav>
  </div>
</template>

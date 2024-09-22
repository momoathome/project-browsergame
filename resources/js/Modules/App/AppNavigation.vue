<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import AppTooltip from '@/Components/AppTooltip.vue';

const NavigationLinks = [
  { name: 'overview', label: 'Overview', image: '/storage/navigation/overview.png' },
  { name: 'buildings', label: 'Buildings', image: '/storage/navigation/buildings.png' },
  { name: 'shipyard', label: 'Shipyard', image: '/storage/navigation/shipyard.png' },
  { name: 'research', label: 'Research', image: '/storage/navigation/research.png' },
  { name: 'asteroidMap', label: 'AsteroidMap', image: '/storage/navigation/asteroidmap.png' },
  { name: 'market', label: 'Market', image: '/storage/navigation/market.png' },
  { name: 'logbook', label: 'Logbook', image: '/storage/navigation/logbook.png' },
  { name: 'simulator', label: 'Simulator', image: '/storage/navigation/simulator.png' },
  { name: 'profile.show', label: 'Profile', image: '/storage/navigation/profile.png' },
];
</script>

<template>
  <div class="fixed top-[25vh] right-0 z-50 w-fit bg-base rounded-s-lg">
    <nav class="flex flex-col items-center">
      <Link v-for="link in NavigationLinks" :key="link.name" :href="route(link.name)"
        :class="[route().current(link.name) ? 'border-opacity-100 bg-primary-dark' : '']"
        class="relative group inline-flex items-center w-full p-2 2xl:p-3 hover:bg-primary-dark transition border-r-2 border-opacity-0 border-r-[#bfbfbf] first-of-type:rounded-tl-lg last-of-type:rounded-bl-lg">
        <img :src="link.image" class="w-6 h-6 2xl:w-8 2xl:h-8" alt="" />

        <AppTooltip :label="link.label" position="left" />
      </Link>

      <!-- Admin Dashboard -->
      <Link class="text-red-500 relative group inline-flex items-center p-2 2xl:p-3 hover:bg-primary-dark transition active:border-opacity-100 border-r border-opacity-0 border-primary-light rounded-bl-lg" :href="route('admin.dashboard')"
        :class="[route().current('admin.dashboard') ? 'border-opacity-100 bg-primary-dark' : '']">
        <img src="/storage/navigation/dashboard.png" class="w-6 h-6 2xl:w-8 2xl:h-8" alt="Dashboard" />

        <span
          class="pointer-events-none absolute right-full mr-2 text-center bg-gray-800 text-white text-sm py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300">
          Dashboard
        </span>
      </Link>
    </nav>
  </div>
</template>

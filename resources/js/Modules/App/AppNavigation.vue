<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import { is, can } from 'laravel-permission-to-vuejs'

const NavigationLinks = [
  { name: 'overview', label: 'Overview', image: '/images/navigation/overview.png' },
  { name: 'buildings', label: 'Buildings', image: '/images/navigation/buildings.png' },
  { name: 'shipyard', label: 'Shipyard', image: '/images/navigation/shipyard.png' },
  // { name: 'research', label: 'Research', image: '/images/navigation/research.png' },
  { name: 'asteroidMap', label: 'AsteroidMap', image: '/images/navigation/asteroidmap.png' },
  { name: 'market', label: 'Market', image: '/images/navigation/market.png' },
  { name: 'logbook', label: 'Logbook', image: '/images/navigation/logbook.png' },
  // { name: 'influence', label: 'Influence', image: '/images/attributes/influence.png' },
  { name: 'simulator', label: 'Simulator', image: '/images/navigation/simulator.png' },
];
</script>

<template>
  <div>
    <nav class="flex flex-col">
      <Link v-for="link in NavigationLinks" :key="link.name" :href="route(link.name)"
        :class="[route().current(link.name) ? 'border-opacity-100 ' : '']"
        class="relative group inline-flex justify-center px-4 py-3 hover:border-opacity-100 transition border-l-2 border-opacity-0 border-l-[#bfbfbf]">
      <img :src="link.image" class="w-8 h-8" alt="" />

      <AppTooltip :label="link.label" position="right" />
      </Link>

      <!-- Admin Dashboard -->
      <Link v-if="is('admin')"
        class="group inline-flex justify-center px-4 py-3 hover:border-opacity-100 transition active:border-opacity-100 border-l-2 border-opacity-0 border-l-[#bfbfbf]"
        :href="route('admin.dashboard')" 
        :class="[route().current('admin.dashboard') ? 'border-opacity-100 ' : '']">
        <img src="/images/navigation/gear.png" class="w-8 h-8" alt="Dashboard" />

        <AppTooltip :label="'Dashboard'" position="right" />
      </Link>
    </nav>
  </div>
</template>

<script lang="ts" setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import AppHeader from '@/Modules/App/AppHeader.vue';
import AppNavigation from '@/Modules/App/AppNavigation.vue';

const props = defineProps<{
  title: string
}>()


</script>

<template>
  <div>

    <Head :title="title" />

    <Banner />

    <AppNavigation />

    <div class="min-h-screen bg-[#1E2530]">

      <!-- Page Heading -->
      <AppHeader class="sticky top-0 z-50" />

      <!-- Page Content -->
      <main>
        <slot />
      </main>

      <pre class="text-light">{{ $page.props }}</pre>

    </div>
  </div>
</template>

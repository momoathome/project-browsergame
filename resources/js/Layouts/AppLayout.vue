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

    <Banner />

    <div class="min-h-screen">

      <!-- Page Heading -->
      <AppHeader class="sticky top-0 z-50" />
      <AppSidebar />

      <!-- Page Content -->
      <main class="ms-16 background h-[calc(100vh-102px)]">
        <slot />
      </main>

    </div>

    <pre>{{ $page.props }}</pre>

  </div>
</template>

<style>
.background {
  background: radial-gradient(circle at 50% 70%, #1c222c, #1b202a, #191f28, #181d26, #161c24, #151a22, #141920, #12171e, #11161c);
  /* background: #151a22; */
}
</style>

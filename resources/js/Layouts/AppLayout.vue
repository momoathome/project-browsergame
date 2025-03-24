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

    <div class="min-h-screen background">

      <!-- Page Heading -->
      <AppHeader class="sticky top-0 z-50" />

      <!-- Page Content -->
      <main>
        <slot />
      </main>

    </div>

    <!-- <pre>{{ $page.props }}</pre> -->

  </div>
</template>

<style>
.background {
  background: linear-gradient(30deg, #1e2530, #1d242e, #1c222c, #1b212b, #1a1f29, #181e27, #171d25, #161b24, #151a22, #141920, #13171f, #12161d);
}
</style>

<script setup>
import { Head } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import UserResources from '@/Modules/Resources/UserResources.vue';
import AppNavigation from '@/Components/AppNavigation.vue';

defineProps({
    title: String,
});
</script>

<template>
    <div>

        <Head :title="title" />

        <Banner />

        <AppNavigation />

        <div class="min-h-screen bg-gray-100">

            <!-- Page Heading -->
            <header class="shadow py-1">
                <UserResources />
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <pre>{{ $page.props }}</pre>

        </div>
    </div>
</template>

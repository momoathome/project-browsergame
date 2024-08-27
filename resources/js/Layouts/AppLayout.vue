<script setup>
import { Head } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import UserResources from '@/Modules/Resources/UserResources.vue';
import AppNavigation from '@/Components/AppNavigation.vue';
import Divider from '@/Components/Divider.vue';

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
            <header class="flex justify-between items-center p-4 shadow">
                <UserResources />
                <!-- <img src="/storage/userInfluence.png" class="" alt=""> -->

                <div class="flex gap-2 items-center">
                    <img src="/storage/ressource_light.png" class="h-8 w-8" alt=""> 

                    <Divider class="!w-[2px] h-[24px] bg-primary/20" />
                    <!-- onClick open settings Menu/Modal -->
                    <img src="/storage/MenuFilled.svg" class="" alt="">
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <pre>{{ $page.props }}</pre>

        </div>
    </div>
</template>

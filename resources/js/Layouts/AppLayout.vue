<script setup>
import { Head } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import UserResources from '@/Modules/Resources/UserResources.vue';
import AppNavigation from '@/Components/AppNavigation.vue';
import Divider from '@/Components/Divider.vue';
import { router } from '@inertiajs/vue3';

defineProps({
    title: String,
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>

        <Head :title="title" />

        <Banner />

        <AppNavigation />

        <div class="min-h-screen bg-gray-200">

            <!-- Page Heading -->
            <header class="bg-[hsl(263,45%,7%)] flex justify-between items-center p-2 shadow">
                <UserResources />
                <!-- <img src="/storage/userInfluence.png" class="" alt=""> -->

                <div class="flex gap-2 items-center">
                    <img src="/storage/ressource_light.png" class="h-8 w-8" alt=""> 

                    <Divider class="!w-[2px] h-[24px] bg-primary/50" />
                    <!-- onClick open settings Menu/Modal -->
                    <img src="/storage/MenuFilled.svg" alt="Menu" @click="logout" />
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

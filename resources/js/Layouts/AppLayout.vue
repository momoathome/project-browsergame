<script setup>
import { Head, router } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import UserResources from '@/Modules/App/UserResources.vue';
import UserQueue from '@/Modules/App/UserQueue.vue';
import AppNavigation from '@/Modules/App/AppNavigation.vue';
import Divider from '@/Components/Divider.vue';

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
            <header class="bg-[hsl(263,45%,7%)] flex flex-col gap-2 p-2">
                <div class="flex justify-between items-center">

                    <UserResources />
                    <!-- <img src="/storage/userInfluence.png" class="" alt=""> -->
                    
                    <div class="flex gap-2 items-center">
                        <!-- <img src="/storage/ressource_light.png" class="h-8 w-8" alt="">  -->
                        
                        <!-- <Divider class="!w-[2px] h-[24px] bg-primary/50" /> -->
                        <!-- onClick open settings Menu/Modal -->
                        <img src="/storage/MenuFilled.svg" alt="Menu" @click="logout" />
                    </div>
                </div>

                <Divider class="bg-primary/50" />
                <!-- userQueue container -->
                 <div class="flex gap-2 px-2">
                    <UserQueue />
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

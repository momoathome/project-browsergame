<script lang="ts" setup>
import { router, Link } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';

import AppNavigation from '@/Modules/App/AppNavigation.vue';
import Divider from '@/Components/Divider.vue';

const logout = () => {
    router.post(route('logout'));
};

const menu = [
    { name: 'profile.show', label: 'Profile', image: '/storage/navigation/profile.png' },
]

const isMenuOpen = ref(false);

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = (event) => {
    // Überprüfe, ob der Klick außerhalb des Menüs war
    const menuElement = document.getElementById('dropdown-menu');
    const menuTrigger = document.getElementById('menu-trigger');

    if (menuElement && !menuElement.contains(event.target) &&
        menuTrigger && !menuTrigger.contains(event.target)) {
        isMenuOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeMenu);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeMenu);
});
</script>

<template>
    <aside class="bg-root flex flex-col items-center justify-between h-[calc(100vh-102px)] fixed top-[102px] left-0 z-10">

        <AppNavigation />

        <div class="flex flex-col items-center w-full">
            <Divider class="!w-[calc(100%-1rem)] bg-primary/50" />
            <!-- Menu  -->
            <div class="relative p-2 2xl:px-4 2xl:py-3">
                <img id="menu-trigger" src="/storage/navigation/profile.png" alt="Profile Menu"
                    class="cursor-pointer w-6 h-6 2xl:w-8 2xl:h-8" @click.stop="toggleMenu" />

                <nav id="dropdown-menu" v-if="isMenuOpen"
                    class="absolute bottom-2 left-16 w-48 bg-root rounded-md shadow-lg z-50">
                    <Link :href="route('profile.show')"
                        class="flex items-center gap-2 px-4 py-2 transition-colors hover:bg-root-light text-white">
                    <span>Profile</span>
                    </Link>
                    <div>
                        <button @click="logout"
                            class="flex w-full items-center gap-2 px-4 py-2 transition-colors text-red-600 hover:text-white hover:bg-red-800">
                            <span>Logout</span>
                        </button>
                    </div>
                </nav>
            </div>
        </div>

    </aside>
</template>

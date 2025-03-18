<script lang="ts" setup>
import { router, usePage } from '@inertiajs/vue3';
import UserResources from '@/Modules/App/UserResources.vue';
import UserAttributes from '@/Modules/App/UserAttributes.vue';
import UserQueue from '@/Modules/App/UserQueue.vue';
import Divider from '@/Components/Divider.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';

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
    <header class="bg-[hsl(263,45%,7%)] flex flex-col gap-2 py-2 px-4">
        <div class="flex justify-between items-center">

            <UserResources />

            <div class="flex gap-4 items-center">
                <UserAttributes />

                <Divider class="!w-[2px] h-[24px] bg-primary/50" />
                <!-- Menu  -->
                <div class="relative">
                    <img id="menu-trigger" src="/storage/MenuFilled.svg" alt="Menu" class="cursor-pointer"
                        @click.stop="toggleMenu" />

                    <nav id="dropdown-menu" v-if="isMenuOpen"
                        class="absolute right-0 mt-5 w-48 bg-[hsl(263,45%,7%)] rounded-md shadow-lg z-10">
                        <ul class="py-2">
                            <li>
                                <a :href="route('profile.show')"
                                    class="flex items-center gap-2 px-4 py-2 transition-colors hover:bg-[hsl(263,45%,12%)] text-white">
                                    <img src="/storage/navigation/profile.png" alt="Profile" class="w-5 h-5" />
                                    <span>Profile</span>
                                </a>
                            </li>
                            <li>
                                <button @click="logout"
                                    class="flex w-full items-center gap-2 px-4 py-2 transition-colors text-red-600 hover:text-white hover:bg-red-800">
                                    <span>Logout</span>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <Divider class="bg-primary/50" />

        <UserQueue />
    </header>
</template>

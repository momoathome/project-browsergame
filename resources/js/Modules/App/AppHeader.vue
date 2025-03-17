<script lang="ts" setup>
import { router, usePage } from '@inertiajs/vue3';
import UserResources from '@/Modules/App/UserResources.vue';
import UserAttributes from '@/Modules/App/UserAttributes.vue';
import UserQueue from '@/Modules/App/UserQueue.vue';
import Divider from '@/Components/Divider.vue';

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <header class="bg-[hsl(263,45%,7%)] flex flex-col gap-2 py-2 px-4">
        <div class="flex justify-between items-center">

            <UserResources />

            <div class="flex gap-4 items-center">
                <UserAttributes />

                <Divider class="!w-[2px] h-[24px] bg-primary/50" />
                <!-- onClick open settings Menu/Modal -->
                <!-- <img src="/storage/MenuFilled.svg" alt="Menu" @click="logout" /> -->
                <p class="text-white cursor-pointer" @click="logout">logout</p>
            </div>
        </div>

        <Divider class="bg-primary/50" />

        <UserQueue />
    </header>
</template>

<script lang="ts" setup>
import { defineProps } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import type { User, Spacecraft, Building, UserResources, UserAttributes } from '@/types/types';
import UserDetailStation from '@/Modules/Admin/UserDetailStation.vue';
import UserDetailBuilding from '@/Modules/Admin/UserDetailBuilding.vue';
import UserDetailSpacecraft from '@/Modules/Admin/UserDetailSpacecraft.vue';
import UserDetailResource from '@/Modules/Admin/UserDetailResource.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

const props = defineProps<{
    user: User;
    buildings: Building[];
    spacecrafts: Spacecraft[];
    resources: UserResources[];
    attributes: UserAttributes[];
}>();

async function finishQueue() {
    try {
        await axios.post(route('admin.queue.finish', { userId: props.user.id }));
    } catch (error) {
        // Fehlerbehandlung
        console.error(error);
    }
}

</script>

<template>
    <div class="text-light">
        <div class="flex flex-col mb-6">
            <!-- breadcrumb with back button -->
            <div class="flex items-center">
                <Link :href="route('admin.dashboard')"
                    class="bg-primary text-white py-2 px-4 rounded-md hover:bg-base-dark transition">
                Zurück
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 3xl:grid-cols-4 gap-6 w-full mb-6">
            <!-- Basisinformationen als Card -->
            <div class="flex flex-col gap-4 bg-base rounded-xl border border-primary/40 shadow-xl p-6 min-w-[320px]">
                <div class="flex items-center gap-4 mb-2">
                    <div class="bg-primary/20 rounded-full h-16 w-16 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24">
                            <path fill="currentColor"
                                d="M12 12a5 5 0 1 0 0-10a5 5 0 0 0 0 10Zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-light">{{ user.name }}</h2>
                        <span class="text-secondary text-sm">ID: {{ user.id }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <p><span class="font-medium text-secondary">Rolle:</span> {{ user.role }}</p>
                    <p><span class="font-medium text-secondary">E-Mail:</span> {{ user.email }}</p>
                    <p><span class="font-medium text-secondary">Status:</span> {{ user.status }}</p>
                    <p><span class="font-medium text-secondary">Last Login:</span> {{ user.last_login }}</p>
                </div>
                <div class="mt-4">
                    <SecondaryButton @click="finishQueue">
                        Process Action Queue
                    </SecondaryButton>
                </div>
            </div>

            <!-- Stationen als Card -->
            <div class="flex flex-col gap-4 bg-base rounded-xl border border-primary/40 shadow-xl p-6 min-w-[320px]">
                <UserDetailStation :user="user" />
            </div>
        </div>

        <!-- Buildings Grid -->
        <div class="mb-6">
            <UserDetailBuilding :buildings="buildings" :user="user" />
        </div>

        <!-- Ressourcen Grid -->
        <div class="mb-6">
            <UserDetailResource :resources="resources" :attributes="attributes" :user="user" />
        </div>

        <!-- Spacecrafts Grid -->
        <div class="mb-6">
            <UserDetailSpacecraft :spacecrafts="spacecrafts" :user="user" />
        </div>
    </div>
</template>

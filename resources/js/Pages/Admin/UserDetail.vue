<script lang="ts" setup>
import { defineProps } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import type { User, Spacecraft, Building, UserResources, UserAttributes } from '@/types/types';
import AppLayout from '@/Layouts/AppLayout.vue';
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
    <AppLayout title="User Details">
        <div class="text-light">
            <div class="flex flex-col mb-6">
                <!-- breadcrumb with back button -->
                <div class="flex items">
                    <Link :href="route('admin.dashboard')"
                        class="bg-primary text-white py-2 px-4 rounded-md hover:bg-base-dark transition">
                    Zurück
                    </Link>
                </div>
            </div>

            <section class="flex gap-6 w-full">
                <!-- Benutzer-Basisinformationen -->
                <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
                    <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">
                        Basisinformationen
                    </h2>
                    <div class="grid grid-cols-2 gap-4 p-4">
                        <p><span class="font-medium">Name:</span> {{ user.name }}</p>
                        <p><span class="font-medium">ID:</span> {{ user.id }}</p>
                        <p><span class="font-medium">Rolle:</span> {{ user.role }}</p>
                        <p><span class="font-medium">E-Mail:</span> {{ user.email }}</p>
                        <p><span class="font-medium">Status:</span> {{ user.status }}</p>
                        <p><span class="font-medium">Last Login:</span> {{ user.last_login }}</p>
                    </div>
                </div>

                <!-- Stationen -->
                <UserDetailStation :user="user" />

                <!-- Actions -->
                <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
                    <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">Actions</h2>
                    <div class="p-4">
                        <SecondaryButton @click="finishQueue">
                            Process Action Queue
                        </SecondaryButton>
                    </div>
                </div>
            </section>

            <section class="flex gap-6 mt-6">
                <!-- Buildings -->
                <UserDetailBuilding :buildings="buildings" :user="user" />

                <!-- Ressourcen -->
                <UserDetailResource :resources="resources" :attributes="attributes" :user="user" />
            </section>

            <section class="flex gap-6 mt-6">
                <!-- Spacecrafts -->
                <UserDetailSpacecraft :spacecrafts="spacecrafts" :user="user" />

            </section>
        </div>
    </AppLayout>
</template>

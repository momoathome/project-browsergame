<script lang="ts" setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ResourceDistribution from '@/Modules/Admin/ResourceDistribution.vue';
import type { Resource } from '@/types/types';
import type { User } from '@/types/types';
import { Link } from '@inertiajs/vue3';


const props = defineProps<{
    universeResources: Resource[];
    users: User[];
}>()
</script>

<template>
    <AppLayout title="dashboard">
        <div class="mx-8 my-8">
            <h1 class="text-3xl mb-4 font-bold">
                Dashboard
            </h1>
            <div class="grid grid-cols-3 gap-4">
                <ResourceDistribution :universeResources="universeResources" />

                <div class="bg-slate-100 rounded-lg p-6 mb-6 shadow-sm">
                    <h2 class="text-xl font-semibold mb-4">Users ({{ users.length }})</h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="text-left p-2">ID</th>
                                <th class="text-left p-2">Name</th>
                                <th class="text-left p-2">Email</th>
                                <th class="text-left p-2">Role</th>
                                <th class="text-left p-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id" class="border-b">
                                <td class="p-2">{{ user.id }}</td>
                                <td class="p-2">{{ user.name }}</td>
                                <td class="p-2">{{ user.email }}</td>
                                <td class="p-2">{{ user.role }}</td>
                                <td class="p-2">
                                    <Link :href="route('admin.user.show', { id: user.id })">View Details</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- other cards -->
            </div>
        </div>
    </AppLayout>
</template>

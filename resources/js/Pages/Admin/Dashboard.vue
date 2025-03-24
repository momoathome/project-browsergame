<script lang="ts" setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ResourceDistribution from '@/Modules/Admin/ResourceDistribution.vue';
import type { Resource } from '@/types/types';
import type { User } from '@/types/types';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    universeResources: Resource[];
    users: User[];
}>()
</script>

<template>
    <AppLayout title="dashboard">
        <div class="mx-8 my-8">
            <h1 class="text-3xl mb-4 font-bold text-light">
                Dashboard
            </h1>
            <div class="grid grid-cols-2 gap-4">
                <!-- <ResourceDistribution :universeResources="universeResources" /> -->

                <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
                    <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark text-light rounded-t-xl">
                        Users</h2>
                    <table class="w-full text-light mt-1">
                        <thead class="text-gray-400 border-b border-primary">
                            <tr>
                                <th class="text-left p-2">ID</th>
                                <th class="text-left p-2">Name</th>
                                <th class="text-left p-2">Email</th>
                                <th class="text-left p-2">Role</th>
                                <th class="text-left p-2">Status</th>
                                <th class="text-left p-2">Last Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id" class="cursor-pointer hover:bg-base-dark transition-colors"
                                @click="() => { router.visit(route('admin.user.show', { id: user.id })) }">
                                <td class="p-2">{{ user.id }}</td>
                                <td class="p-2">{{ user.name }}</td>
                                <td class="p-2">{{ user.email }}</td>
                                <td class="p-2">{{ user.role }}</td>
                                <td class="p-2">{{ user.status }}</td>
                                <td class="p-2">{{ user.last_login }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-primary bg-primary rounded-b-xl">
                                <td class="px-2 py-3" colspan="6">
                                    Total Users: {{ users.length }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- other cards -->
            </div>
        </div>
    </AppLayout>
</template>

<script lang="ts" setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ResourceDistribution from '@/Modules/Admin/ResourceDistribution.vue';
import DashboardUserOverview from '@/Modules/Admin/DashboardUserOverview.vue';
import DashboardMarket from '@/Modules/Admin/DashboardMarket.vue';
import type { Resource, Market } from '@/types/types';
import type { User } from '@/types/types';

const props = defineProps<{
    universeResources?: Resource[];
    market: Market[];
    users: User[];
}>()
</script>

<template>
    <AppLayout title="dashboard">
        <div>
            <h1 class="text-3xl mb-4 font-bold text-light">
                Dashboard
            </h1>

            <div class="w-1/3 mb-4">
                <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
                    <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl text-light">
                        Module
                    </h2>
                    <div class="grid grid-cols-2 gap-4 p-4 text-light">
                        <Link :href="route('admin.progression')" class="font-medium text-lg">Building Progression</Link>
                        <!-- <Link :href="route('admin.resources')" class="font-medium text-lg">Resource Management</Link> -->
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- <ResourceDistribution :universeResources="universeResources" /> -->

                <DashboardUserOverview :users="users" />
                <DashboardMarket :market="market" />
            </div>
        </div>
    </AppLayout>
</template>

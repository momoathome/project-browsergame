<script lang="ts" setup>
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
            <div class="grid grid-cols-2 gap-4">
                <!-- <ResourceDistribution :universeResources="universeResources" /> -->

                <DashboardUserOverview :users="users" />
                <DashboardMarket :market="market" />
            </div>
        </div>
    </AppLayout>
</template>

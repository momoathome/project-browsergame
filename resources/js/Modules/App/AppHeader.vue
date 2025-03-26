<script lang="ts" setup>
import UserResources from '@/Modules/App/UserResources.vue';
import UserAttributes from '@/Modules/App/UserAttributes.vue';
import UserQueue from '@/Modules/App/UserQueue.vue';
import Divider from '@/Components/Divider.vue';

</script>

<template>
    <header class="bg-root flex flex-col gap-2 py-2 px-2">
        <div class="flex justify-between gap-1 items-center">

            <UserResources />

            <div class="flex gap-2 2xl:gap-4 items-center">
                <UserAttributes />

            </div>
        </div>

        <!-- <Divider class="bg-primary/50" /> -->

        <UserQueue />
    </header>
</template>

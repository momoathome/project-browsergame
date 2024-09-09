<script setup>
import { computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import UserResources from '@/Modules/App/UserResources.vue';
import UserQueue from '@/Modules/App/UserQueue.vue';
import AppNavigation from '@/Modules/App/AppNavigation.vue';
import Divider from '@/Components/Divider.vue';
import AppTooltip from '@/Components/AppTooltip.vue';
import { numberFormat } from '@/Utils/format';

defineProps({
    title: String,
});

const logout = () => {
    router.post(route('logout'));
};

// formatAttributesNames to human readable names
const formattedAttributesNames = computed(() => {
    return usePage().props.userAttributes.map((attribute) => {
        return {
            name: attribute ? attribute.attribute_name : null,
            // label with first letter capitalized
            label: attribute ? attribute.attribute_name.replace(/_/g,'') : null,
        };
    });
});

const formattedAttributes = computed(() => {
    return usePage().props.userAttributes.map((attribute) => {
        return {
            // attribute name from formattedAttributesNames
            name: attribute ? formattedAttributesNames.value.find((item) => item.name === attribute.attribute_name)?.name : null,
            label: attribute ? formattedAttributesNames.value.find((item) => item.name === attribute.attribute_name)?.label : null,
            amount: computed(() => numberFormat(attribute.attribute_value)),
            image: attribute ? attribute.image : null,
        };
    });
});
</script>

<template>
    <div>

        <Head :title="title" />

        <Banner />

        <AppNavigation />

        <div class="min-h-screen bg-gray-200">

            <!-- Page Heading -->
            <header class="bg-[hsl(263,45%,7%)] flex flex-col gap-2 p-2">
                <div class="flex justify-between items-center">

                    <UserResources />

                    <div class="flex gap-4 items-center">
                        <!-- total resources -->
                        <div class="relative group flex flex-col gap-1 items-center" v-for="attribute in formattedAttributes" :key="attribute.name">
                            <img :src="`/storage/attributes/${attribute.name}.png`" class="h-7" alt="">
                            <span class="text-sm font-medium text-white">
                                {{ attribute.amount }}
                            </span>

                            <AppTooltip :label="attribute.label" position="bottom" class="!mt-3" />
                        </div>

                        <Divider class="!w-[2px] h-[24px] bg-primary/50" />
                        <!-- onClick open settings Menu/Modal -->
                        <img src="/storage/MenuFilled.svg" alt="Menu" @click="logout" />
                    </div>
                </div>

                <Divider class="bg-primary/50" />
                <!-- userQueue container -->
                <div class="flex gap-2 px-2">
                    <UserQueue />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <pre>{{ $page.props }}</pre>

        </div>
    </div>
</template>

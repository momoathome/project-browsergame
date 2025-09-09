<script lang="ts" setup>
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { UserResources, UserAttributes } from '@/types/types';
import { numberFormat } from '@/Utils/format';

const props = defineProps<{
    resources: UserResources[];
    attributes: UserAttributes[];
    user: { id: number };
}>();

const editingResource = ref<number | null>(null);
const resourceForms = ref(props.resources.map(resource => useForm({
    amount: resource.amount,
    user_id: props.user.id
})));

const toggleEditResource = (index: number | null) => {
    editingResource.value = editingResource.value === index ? null : index;
};

const updateResourceAmount = (index: number, resourceId: number) => {
    resourceForms.value[index].put(route('admin.resources.update', { id: resourceId }), {
        preserveScroll: true,
        onSuccess: () => {
            editingResource.value = null;
            router.reload({ only: ['resources'] });
        }
    });
};

const credits = props.attributes.find(attr => attr.attribute_name === 'credits')?.attribute_value || 0;
</script>

<template>
    <div class=" w-full p-2">
        <div class="grid gap-4 grid-cols-2 xl:grid-cols-9">
            <div v-for="(resource, index) in resources" :key="resource.id" class="flex flex-col rounded-xl bg-base content_card text-light">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2 px-2 py-2">
                        <img :src="resource.resource.image" class="h-8 w-8 rounded-full object-cover" alt="{{ resource.resource.name }}" />
                        <p class="font-semibold text-lg">{{ resource.resource.name }}</p>
                    </div>
                </div>
                <div class="flex flex-col h-full">
                    <div class="flex flex-col gap-1 px-3 py-2 h-full bg-primary/25">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-secondary">Amount:</span>
                            <span class="font-medium text-sm">{{ numberFormat(resourceForms[index] ? resourceForms[index].amount : resource.amount) }}</span>
                        </div>
                    </div>
                    <div class="flex px-3 py-2 mt-2">
                        <template v-if="editingResource === index && resourceForms[index]">
                            <div class="flex flex-wrap gap-1">
                                <input v-model="resourceForms[index].amount" type="number" min="0" class="w-24 px-2 py-1 border rounded-md bg-base-dark text-light mr-2" />
                                <button @click="updateResourceAmount(index, resource.resource_id)" class="bg-primary text-white py-1 px-3 rounded-md hover:bg-primary-dark text-sm mr-2">Speichern</button>
                                <button @click="toggleEditResource(null)" class="bg-gray-600 text-white py-1 px-3 rounded-md hover:bg-gray-700 text-sm">Abbrechen</button>
                            </div>
                        </template>
                        <template v-else>
                            <button @click="toggleEditResource(index)" class="text-secondary bg-primary/25 w-full py-1 px-2 rounded-md hover:bg-primary/40 transition">Bearbeiten</button>
                        </template>
                    </div>
                </div>
            </div>
            <!-- Credits als Card -->
            <div class="flex flex-col rounded-xl bg-base content_card text-light">
                <div class="flex items-center gap-2 px-2 py-2">
                    <p class="font-semibold text-lg">Credits</p>
                </div>
                <div class="px-3 py-2 bg-primary/25">
                    <span class="font-medium">{{ numberFormat(credits) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

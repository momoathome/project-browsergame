<script lang="ts" setup>
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Market } from '@/types/types';
import { numberFormat } from '@/Utils/format';

const props = defineProps<{
    market: Market[];
}>();

const editingResource = ref<number | null>(null);
const resourceForms = ref(props.market.map(resource => useForm({
    stock: resource.stock,
    cost: resource.cost
})));

const toggleEditResource = (index: number) => {
    editingResource.value = editingResource.value === index ? null : index;
};

const updateMarketResource = (index: number, resourceId: number) => {
    resourceForms.value[index].put(route('admin.market.update', { id: resourceId }), {
        preserveScroll: true,
        onSuccess: () => {
            editingResource.value = null;
            router.reload({ only: ['market'] });
        }
    });
};
</script>

<template>
    <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
        <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl text-light">
            Market
        </h2>
        <table class="w-full text-light mt-1">
            <thead class="text-gray-400 border-b border-primary">
                <tr>
                    <th class="text-left p-2">Ressource</th>
                    <th class="text-left p-2">Price</th>
                    <th class="text-left p-2">Stock</th>
                    <th class="text-left p-2">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(resource, index) in market" :key="resource.id">
                    <td class="p-2 font-medium">{{ resource.resource.name }}</td>
                    <td class="p-2 font-medium">
                        <span v-if="editingResource !== index">{{ numberFormat(resourceForms[index].cost)}}</span>
                        <input v-else v-model="resourceForms[index].cost" type="number" min="0"
                            class="w-full px-2 py-1 border rounded-md bg-base-dark text-light" />
                    </td>
                    <td class="p-2">
                        <span v-if="editingResource !== index">{{ numberFormat(resourceForms[index].stock) }}</span>
                        <input v-else v-model="resourceForms[index].stock" type="number" min="0"
                            class="w-full px-2 py-1 border rounded-md bg-base-dark text-light" />
                    </td>
                    <td class="p-2">
                        <button v-if="editingResource !== index" @click="toggleEditResource(index)"
                            class="text-primary-light hover:text-secondary">
                            Bearbeiten
                        </button>
                        <div v-else class="flex gap-2">
                            <button @click="updateMarketResource(index, resource.resource_id)"
                                class="bg-primary text-white py-1 px-3 rounded-md hover:bg-primary-dark text-sm">
                                Speichern
                            </button>
                            <button @click="toggleEditResource(null)"
                                class="bg-gray-600 text-white py-1 px-3 rounded-md hover:bg-gray-700 text-sm">
                                Abbrechen
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="border-t border-primary bg-primary rounded-b-xl">
                    <td class="px-2 py-3">
                        Ressources Total:
                    </td>
                    <td></td>
                    <td class="px-2 py-3">
                        {{numberFormat(market.reduce((sum, resource) => sum + resource.stock, 0))}}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>

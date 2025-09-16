<script lang="ts" setup>
import { useForm, router } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';
import type { Market } from '@/types/types';
import { numberFormat } from '@/Utils/format';

const props = defineProps<{
    market: Market[];
}>();

const editingResource = ref<number | null>(null);
const resourceForms = ref(props.market.map(resource => useForm({
    stock: resource.stock,
})));

const toggleEditResource = (index: number | null) => {
    editingResource.value = editingResource.value === index ? null : index;
};

const updateMarketResource = (index: number, resourceId: number) => {
    if (!resourceForms.value[index]) return;
    resourceForms.value[index].put(route('admin.market.update', { id: resourceId }), {
        preserveScroll: true,
        onSuccess: () => {
            editingResource.value = null;
            router.reload({ only: ['market'] });
        }
    });
};

const showResetMarketModal = ref(false);

function resetMarketData() {
  router.post(route('admin.market.reset'), {
    preserveState: true,
    preserveScroll: true,
  });
  showResetMarketModal.value = false;
}

</script>

<template>
    <div class="bg-base rounded-xl w-full border border-primary/40 shadow-xl">
        <div class="flex justify-between items-center p-6 border-b border-primary/30 bg-base-dark rounded-t-xl">
            <h2 class="text-xl font-semibold text-light">Market</h2>
            <SecondaryButton type="button" @click="showResetMarketModal = true">
                Alle Markt-Daten zurücksetzen
            </SecondaryButton>
        </div>
        <div class="grid gap-8 p-6">
            <div v-for="(resource, index) in market" :key="resource.id" class="flex flex-col rounded-xl bg-primary/25 text-light shadow-md">
                <div class="flex justify-between items-center border-b border-primary/40">
                    <div class="flex items-center gap-2 px-2 py-2">
                        <p class="font-semibold">{{ resource.resource.name }}</p>
                    </div>
                </div>
                <div class="flex justify-center items-center py-6">
                    <img :src="resource.resource.image" class="h-16" alt="resource" />
                </div>
                <div class="flex flex-col h-full">
                    <div class="flex flex-col gap-1 px-3 py-2 h-full bg-primary/25">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-secondary">Stock:</span>
                            <span class="">{{ resourceForms[index] ? numberFormat(resourceForms[index].stock) : numberFormat(resource.stock) }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 px-3 py-2 mt-1">
                        <template v-if="editingResource === index && resourceForms[index]">
                            <div class="flex justify-between items-center gap-1">
                                <input v-model="resourceForms[index].stock" type="number" min="0" class="w-20 px-1 py-1 border rounded-md bg-base-dark text-light" />
                            </div>
                            <div class="flex gap-2">
                                <button @click="updateMarketResource(index, resource.resource_id)" class="bg-primary text-white py-1 px-3 rounded-md hover:bg-primary-dark text-sm mr-2">Speichern</button>
                                <button @click="toggleEditResource(null)" class="bg-gray-600 text-white py-1 px-3 rounded-md hover:bg-gray-700 text-sm">Abbrechen</button>
                            </div>
                        </template>
                        <template v-else>
                            <button @click="toggleEditResource(index)" class="text-secondary bg-base py-1 px-2 rounded-md hover:bg-primary/40 transition">Bearbeiten</button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal :show="showResetMarketModal" @close="showResetMarketModal = false">
            <template #title>
                Alle Markt-Daten zurücksetzen
            </template>
            <template #content>
                Bist du sicher, dass du <b>alle</b> Markt-Daten zurücksetzen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.
            </template>
            <template #footer>
                <div class="flex gap-2">
                    <SecondaryButton @click="showResetMarketModal = false">Abbrechen</SecondaryButton>
                    <PrimaryButton @click="resetMarketData">Bestätigen</PrimaryButton>
                </div>
            </template>
        </ConfirmationModal>
    </div>

</template>

<style scoped>
.grid {
  --grid-min-col-size: 200px;

  grid-template-columns: repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}
</style>

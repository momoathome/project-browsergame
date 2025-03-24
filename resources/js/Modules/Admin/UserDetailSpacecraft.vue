<script lang="ts" setup>
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { UserResources, UserAttributes, Spacecraft } from '@/types/types';

const props = defineProps<{
    spacecrafts: Spacecraft[];
    user: { id: number };
}>();

const editingSpacecraft = ref<number | null>(null);
const spacecraftForms = ref(props.spacecrafts.map(spacecraft => useForm({
    count: spacecraft.count,
    id: spacecraft.id,
    user_id: props.user.id
})));

const toggleEditSpacecraft = (index: number) => {
    editingSpacecraft.value = editingSpacecraft.value === index ? null : index;
};

const updateSpacecraftCount = (index: number, spacecraftId: number) => {
    spacecraftForms.value[index].put(route('admin.spacecrafts.update', { id: spacecraftId }), {
        preserveScroll: true,
        onSuccess: () => {
            editingSpacecraft.value = null;
            router.reload({ only: ['spacecrafts'] });
        }
    });
};
</script>

<template>
    <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
        <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">
            Spacecrafts
        </h2>
        <table class="w-full text-light mt-1">
            <thead class="text-gray-400 border-b border-primary">
                <tr>
                    <th class="text-left p-2">Name</th>
                    <th class="text-left p-2">Anzahl</th>
                    <th class="text-left p-2">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(spacecraft, index) in spacecrafts" :key="spacecraft.id">
                    <td class="p-2">{{ spacecraft.details.name }}</td>
                    <td class="p-2">
                        <span v-if="editingSpacecraft !== index">{{ spacecraft.count }}</span>
                        <input v-else v-model="spacecraftForms[index].count" type="number" min="0"
                            class="w-full px-2 py-1 border rounded-md bg-base-dark text-light" />
                    </td>
                    <td class="p-2">
                        <button v-if="editingSpacecraft !== index" @click="toggleEditSpacecraft(index)"
                            class="text-primary-light hover:text-secondary">
                            Bearbeiten
                        </button>
                        <div v-else class="flex gap-2">
                            <button @click="updateSpacecraftCount(index, spacecraft.id)"
                                class="bg-primary text-white py-1 px-3 rounded-md hover:bg-primary-dark text-sm">
                                Speichern
                            </button>
                            <button @click="toggleEditSpacecraft(null)"
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
                        Spacecrafts total:
                    </td>
                    <td class="px-2 py-3">
                        {{spacecrafts.reduce((sum, spacecraft) => sum + spacecraft.count, 0)}}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>

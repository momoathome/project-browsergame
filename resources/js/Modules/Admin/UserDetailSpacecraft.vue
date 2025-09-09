<script lang="ts" setup>
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Spacecraft } from '@/types/types';
import TertiaryButton from '@/Components/TertiaryButton.vue';

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

const toggleEditSpacecraft = (index: number | null) => {
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

const unlockSpacecraft = (spacecraftId: number) => {
    router.post(route('admin.spacecrafts.unlock', { spacecraft_id: spacecraftId, user_id: props.user.id }), {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="w-full  p-2">
        <div class="grid gap-8 grid-cols-2 xl:grid-cols-8">
            <div v-for="(spacecraft, index) in spacecrafts" :key="spacecraft.id" class="flex flex-col rounded-xl bg-base py-2 px-4 text-light">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2 px-2 py-2">
                        <p class="font-semibold text-lg">{{ spacecraft.details.name }}</p>
                    </div>
                    <div class="flex items-center h-full px-3">
                        <p class="text-lg">{{ spacecraftForms[index] ? spacecraftForms[index].count : spacecraft.count }}</p>
                    </div>
                </div>
                <div class="image relative">
                    <img :src="spacecraft.details.image" class="object-cover aspect-[2/1] h-28 w-full rounded-xl" alt="" />
                </div>
                <div class="flex flex-col h-full">
                    <div class="flex flex-col gap-1 px-3 py-2 h-full mt-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-secondary">Locked:</span>
                            <span class="font-medium text-sm">{{ spacecraft.locked_count }}</span>
                        </div>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-sm text-secondary">Status:</span>
                            <span class="font-medium text-sm">
                                <template v-if="spacecraft.unlocked">
                                    <span class="text-green-500">Unlocked</span>
                                </template>
                                <template v-else>
                                    <TertiaryButton v-if="!spacecraft.unlocked"
                                    class="!py-1 !px-2"
                                    @click="unlockSpacecraft(spacecraft.id)">
                                        Unlock
                                    </TertiaryButton>
                                </template>
                            </span>
                        </div>
                    </div>
                    <div class="flex px-3 py-2">
                        <template v-if="editingSpacecraft === index && spacecraftForms[index]">
                            <div class="flex flex-wrap gap-1">
                                <input v-model="spacecraftForms[index].count" type="number" min="0" class="w-24 px-2 py-1 border rounded-md bg-base-dark text-light mr-2" />
                                <button @click="updateSpacecraftCount(index, spacecraft.id)" class="bg-primary text-white py-1 px-3 rounded-md hover:bg-primary-dark text-sm mr-2">Speichern</button>
                                <button @click="toggleEditSpacecraft(null)" class="bg-gray-600 text-white py-1 px-3 rounded-md hover:bg-gray-700 text-sm">Abbrechen</button>
                            </div>
                        </template>
                        <template v-else>
                            <button @click="toggleEditSpacecraft(index)" class="text-secondary bg-primary/25 w-full py-1 px-2 rounded-md hover:bg-primary/40 transition">Bearbeiten</button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { useForm, router } from '@inertiajs/vue3';
import type { Building } from '@/types/types';

const props = defineProps<{
    buildings: Building[];
    user: { id: number };
}>();

const getBuildingEffectDisplay = (building) => {
    if (building.current_effects && building.current_effects.length > 0) {
        return building.current_effects[0].display;
    }
};

const updateBuildingLevel = (building) => {
    const form = useForm({
        building_id: building.id,
        user_id: props.user.id
    });

    form.put(route('admin.buildings.update', { id: building.id }), {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['buildings'] });
        }
    });
};
</script>

<template>
    <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
        <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">Buildings
        </h2>
        <table class="w-full text-light mt-1">
            <thead class="text-gray-400 border-b border-primary">
                <tr>
                    <th class="text-left p-2">Name</th>
                    <th class="text-left p-2">Level</th>
                    <th class="text-left p-2">Effect value</th>
                    <th class="text-left p-2">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="building in buildings" :key="building.id">
                    <td class="p-2">{{ building.name }}</td>
                    <td class="p-2">{{ building.level || 'Unbekannt' }}</td>
                    <td class="p-2">{{ getBuildingEffectDisplay(building) }}</td>
                    <td class="p-2">
                        <button @click="updateBuildingLevel(building)"
                            class="bg-primary text-white py-1 px-4 rounded-md hover:bg-primary-dark transition">
                            Level up
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

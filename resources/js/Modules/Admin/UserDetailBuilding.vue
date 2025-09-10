<script lang="ts" setup>
import { useForm, router } from '@inertiajs/vue3';
import type { Building } from '@/types/types';

const props = defineProps<{
    buildings: Building[];
    user: { id: number };
}>();

const getBuildingEffectDisplay = (building) => {
  if (building.effect?.current) {
    return building.effect.current[0].effect.text + ': ' + building.effect.current[0].effect.value;
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
    <div class=" w-full p-2">
        <div class="grid gap-8 grid-cols-2 md:grid-cols-3 2xl:grid-cols-7">
            <div v-for="building in buildings" :key="building.id" class="flex flex-col rounded-xl bg-base py-2 px-4 text-light">
                <div class="flex justify-between items-center">
                    <div class="flex justify-center px-2 py-2">
                        <p class="font-semibold text-lg">{{ building.name }}</p>
                    </div>
                    <div class="flex items-center h-full px-2">
                        <span class="text-sm font-medium mt-1 me-1 text-secondary">lv.</span>
                        <p class="text-lg">{{ building.level }}</p>
                    </div>
                </div>
                <div class="image relative">
                    <img :src="building.image" class="object-cover aspect-[2/1] h-28 w-full rounded-xl" alt="" />
                </div>
                <div class="flex flex-col h-full">
                    <div class="flex flex-col gap-1 px-3 py-2 mt-1 h-full">
                        <div class="flex items-center gap-1">
                            <span class="text-sm text-secondary">
                                {{ building.effect && building.effect.current && building.effect.current[0] ? building.effect.current[0].effect.text : '' }}:
                            </span>
                            <span class="font-medium text-sm">
                                {{ building.effect && building.effect.current && building.effect.current[0] ? building.effect.current[0].effect.value : '' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex px-3 py-2">
                        <button @click="updateBuildingLevel(building)"
                            class="text-secondary bg-primary/25 w-full py-1 px-2 rounded-md hover:bg-primary/40 transition">
                            Level up
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

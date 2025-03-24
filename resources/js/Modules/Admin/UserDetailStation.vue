<script lang="ts" setup>
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    user: { stations: { id: number, name: string, x: number, y: number }[] };
}>();

const editingStation = ref<number | null>(null);
const stationForms = ref(props.user.stations.map(station => useForm({
    x: station.x,
    y: station.y,
    id: station.id
})));

const toggleEditStation = (index: number) => {
    editingStation.value = editingStation.value === index ? null : index;
};

const updateStationCoordinates = (index: number) => {
    stationForms.value[index].put(route('admin.stations.update', { id: stationForms.value[index].id }), {
        preserveScroll: true,
        onSuccess: () => {
            stationForms.value[index].reset();
            editingStation.value = null;
            router.reload({ only: ['stations'] });
        }
    });
};
</script>

<template>
    <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
        <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">
            Stations
        </h2>
        <div class="p-4">
            <div v-for="(station, index) in user.stations" :key="station.id"
                class="mb-4 p-4 border border-gray-500 rounded-md">

                <div class="flex justify-between mb-2">
                    <h3 class="text-lg font-medium">{{ station.name }} (ID: {{ station.id }})</h3>
                    <button @click="toggleEditStation(index)" class="text-primary-light hover:text-secondary">
                        {{ editingStation === index ? 'Schließen' : 'Bearbeiten' }}
                    </button>
                </div>

                <!-- Normale Anzeige -->
                <div v-if="editingStation !== index">
                    <p>Position: x:{{ station.x }}, y:{{ station.y }}</p>
                </div>

                <!-- Bearbeitungsmodus -->
                <form v-else @submit.prevent="updateStationCoordinates(index)" class="mt-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">X-Koordinate</label>
                            <input v-model="stationForms[index].x" type="number"
                                class="w-full px-3 py-2 border rounded-md bg-base-dark text-light" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Y-Koordinate</label>
                            <input v-model="stationForms[index].y" type="number"
                                class="w-full px-3 py-2 border rounded-md bg-base-dark text-light" />
                        </div>
                    </div>
                    <button type="submit" class="mt-3 bg-primary text-white py-1 px-4 rounded-md hover:bg-primary-dark">
                        Speichern
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

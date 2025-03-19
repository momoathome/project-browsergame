<script lang="ts" setup>
import { defineProps, ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import type { User, Station, Spacecraft, Building, Resource } from '@/types/types';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{
    user: User;
    buildings: Building[];
    spacecrafts: Spacecraft[];
    ressources: Resource[];
}>();

const stationForms = ref(props.user.stations.map(station => useForm({
    x: station.x,
    y: station.y,
    id: station.id
})));

const updateStationCoordinates = (index: number) => {
    stationForms.value[index].put(route('admin.stations.update', { id: stationForms.value[index].id }), {
        preserveScroll: true,
        onSuccess: () => {
            stationForms.value[index].reset();
        }
    });
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

const finishQueue = () => {
    router.post(route('admin.queue.finish', { userId: props.user.id }), {
        preserveScroll: true,
    });
};

// Editiermodus für Stationen
const editingStation = ref<number | null>(null);
const toggleEditStation = (index: number) => {
    editingStation.value = editingStation.value === index ? null : index;
};

// Editiermodus für Ressourcen
const editingResource = ref<number | null>(null);
const resourceForms = ref(props.ressources.map(resource => useForm({
    amount: resource.pivot.amount,
    user_id: props.user.id
})));

const toggleEditResource = (index: number) => {
    editingResource.value = editingResource.value === index ? null : index;
};

const updateResourceAmount = (index: number, resourceId: number) => {
    resourceForms.value[index].put(route('admin.resources.update', { id: resourceId }), {
        preserveScroll: true,
        onSuccess: () => {
            editingResource.value = null;
            router.reload({ only: ['ressources'] });
        }
    });
};
</script>

<template>
    <AppLayout title="User Details">
        <div class="mx-8 my-8 text-light">
            <div class="flex flex-col mb-6">
                <!-- breadcrumb with back button -->
                <div class="flex justify-between">
                    <div class="flex items">
                        <Link :href="route('admin.dashboard')"
                            class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                        Zurück
                        </Link>
                    </div>

                    <div class="flex gap-4">
                        <button @click="finishQueue"
                            class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
                            Warteschlange sofort beenden
                        </button>
                    </div>
                </div>
                <h1 class="text-3xl font-bold mt-4">
                    Benutzerdetails: {{ user.name }}
                </h1>
            </div>

            <div class="flex gap-8">

                <!-- Benutzer-Basisinformationen -->
                <div class="bg-base rounded-lg p-6 mb-6 shadow-sm">
                    <h2 class="text-xl font-semibold mb-4">Basisinformationen</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <p><span class="font-medium">ID:</span> {{ user.id }}</p>
                        <p><span class="font-medium">Rolle:</span> {{ user.role }}</p>
                        <p><span class="font-medium">Name:</span> {{ user.name }}</p>
                        <p><span class="font-medium">E-Mail:</span> {{ user.email }}</p>
                    </div>
                </div>

                <!-- Stationen -->
                <div class="bg-base rounded-lg p-6 mb-6 shadow-sm">
                    <h2 class="text-xl font-semibold mb-4">Stationen ({{ user.stations.length }})</h2>
                    <div v-for="(station, index) in user.stations" :key="station.id"
                        class="mb-4 p-4 border border-gray-300 rounded-md">

                        <div class="flex justify-between mb-2">
                            <h3 class="text-lg font-medium">{{ station.name }} (ID: {{ station.id }})</h3>
                            <button @click="toggleEditStation(index)" class="text-blue-600 hover:text-blue-800">
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
                                        class="w-full px-3 py-2 border rounded-md bg-base-dark" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Y-Koordinate</label>
                                    <input v-model="stationForms[index].y" type="number"
                                        class="w-full px-3 py-2 border rounded-md bg-base-dark" />
                                </div>
                            </div>
                            <button type="submit"
                                class="mt-3 bg-blue-600 text-white py-1 px-4 rounded-md hover:bg-blue-700">
                                Speichern
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="flex gap-8">

                <!-- Buildings -->
                <div class="bg-base rounded-lg p-6 mb-6 shadow-sm">
                    <h2 class="text-xl font-semibold mb-4">Gebäude ({{ buildings.length }})</h2>
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left p-2">Name</th>
                                <th class="text-left p-2">Level</th>
                                <th class="text-left p-2">Effect name</th>
                                <th class="text-left p-2">Effect value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="building in buildings" :key="building.id">
                                <td class="p-2">{{ building.details.name }}</td>
                                <td class="p-2">{{ building.level || 'Unbekannt' }}</td>
                                <td class="p-2">{{ building.details.effect }}</td>
                                <td class="p-2">{{ building.effect_value }}</td>
                                <td>
                                    <button @click="updateBuildingLevel(building)"
                                        class="bg-blue-600 text-white py-1 px-4 rounded-md hover:bg-blue-700">
                                        Level up
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Raumschiffe -->
                <div class="bg-base rounded-lg p-6 mb-6 shadow-sm w-1/5">
                    <h2 class="text-xl font-semibold mb-4">Raumschiffe ({{ spacecrafts.length }})</h2>
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left p-2">Name</th>
                                <th class="text-left p-2">Anzahl</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="spacecraft in spacecrafts" :key="spacecraft.id">
                                <td class="p-2">{{ spacecraft.details.name }}</td>
                                <td class="p-2">{{ spacecraft.count || 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Ressources -->
                <div class="bg-base rounded-lg p-6 mb-6 shadow-sm w-1/5">
                    <h2 class="text-xl font-semibold mb-4">Ressourcen</h2>
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left p-2">Ressource</th>
                                <th class="text-left p-2">Menge</th>
                                <th class="text-left p-2">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(resource, index) in ressources" :key="resource.id">
                                <td class="p-2 font-medium">{{ resource.name }}</td>
                                <td class="p-2">
                                    <span v-if="editingResource !== index">{{ resource.pivot.amount }}</span>
                                    <input v-else v-model="resourceForms[index].amount" type="number" min="0"
                                        class="w-full px-2 py-1 border rounded-md bg-base-dark" />
                                </td>
                                <td class="p-2">
                                    <button v-if="editingResource !== index" @click="toggleEditResource(index)"
                                        class="text-blue-600 hover:text-blue-800">
                                        Bearbeiten
                                    </button>
                                    <div v-else class="flex gap-2">
                                        <button @click="updateResourceAmount(index, resource.id)"
                                            class="bg-blue-600 text-white py-1 px-3 rounded-md hover:bg-blue-700 text-sm">
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
                    </table>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

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

// Editiermodus für Raumschiffe
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
    <AppLayout title="User Details">
        <div class="mx-8 my-8 text-light">
            <div class="flex flex-col mb-6">
                <!-- breadcrumb with back button -->
                <div class="flex items">
                    <Link :href="route('admin.dashboard')"
                        class="bg-primary text-white py-2 px-4 rounded-md hover:bg-base-dark transition">
                    Zurück
                    </Link>
                </div>
                <h1 class="text-3xl font-bold mt-4">
                    Benutzerdetails: {{ user.name }}
                </h1>
            </div>

            <div class="flex gap-8 w-2/3">
                <!-- Benutzer-Basisinformationen -->
                <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
                    <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">
                        Basisinformationen</h2>
                    <div class="grid grid-cols-2 gap-4 p-4">
                        <p><span class="font-medium">ID:</span> {{ user.id }}</p>
                        <p><span class="font-medium">Rolle:</span> {{ user.role }}</p>
                        <p><span class="font-medium">Name:</span> {{ user.name }}</p>
                        <p><span class="font-medium">E-Mail:</span> {{ user.email }}</p>
                        <p><span class="font-medium">Status:</span> {{ user.status }}</p>
                        <p><span class="font-medium">Last Login:</span> {{ user.last_login }}</p>
                    </div>
                </div>

                <!-- Stationen -->
                <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
                    <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">
                        Stations
                    </h2>
                    <div class="p-4">
                        <div v-for="(station, index) in user.stations" :key="station.id"
                            class="mb-4 p-4 border border-gray-500 rounded-md">

                            <div class="flex justify-between mb-2">
                                <h3 class="text-lg font-medium">{{ station.name }} (ID: {{ station.id }})</h3>
                                <button @click="toggleEditStation(index)"
                                    class="text-primary-light hover:text-secondary">
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
                                <button type="submit"
                                    class="mt-3 bg-primary text-white py-1 px-4 rounded-md hover:bg-primary-dark">
                                    Speichern
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
                    <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">Actions</h2>
                    <div class="p-4">
                        <button @click="finishQueue"
                            class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
                            Warteschlange sofort beenden
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex gap-8 mt-8">
                <!-- Buildings -->
                <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
                    <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">Buildings
                    </h2>
                    <table class="w-full text-light mt-1">
                        <thead class="text-gray-400 border-b border-primary">
                            <tr>
                                <th class="text-left p-2">Name</th>
                                <th class="text-left p-2">Level</th>
                                <th class="text-left p-2">Effect name</th>
                                <th class="text-left p-2">Effect value</th>
                                <th class="text-left p-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="building in buildings" :key="building.id">
                                <td class="p-2">{{ building.details.name }}</td>
                                <td class="p-2">{{ building.level || 'Unbekannt' }}</td>
                                <td class="p-2">{{ building.details.effect }}</td>
                                <td class="p-2">{{ building.effect_value }}</td>
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
            </div>

            <div class="flex gap-8 mt-8">
                <!-- Raumschiffe -->
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

                <!-- Ressourcen -->
                <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
                    <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl">Ressources
                    </h2>
                    <table class="w-full text-light mt-1">
                        <thead class="text-gray-400 border-b border-primary">
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
                                        class="w-full px-2 py-1 border rounded-md bg-base-dark text-light" />
                                </td>
                                <td class="p-2">
                                    <button v-if="editingResource !== index" @click="toggleEditResource(index)"
                                        class="text-primary-light hover:text-secondary">
                                        Bearbeiten
                                    </button>
                                    <div v-else class="flex gap-2">
                                        <button @click="updateResourceAmount(index, resource.id)"
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
                            <tr class="h-10"></tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-primary bg-primary rounded-b-xl">
                                <td class="px-2 py-3">
                                    Ressources Total:
                                </td>
                                <td class="px-2 py-3">
                                    {{ressources.reduce((sum, resource) => sum + resource.pivot.amount, 0)}}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import type { Rebel } from '@/types/types';

const props = defineProps<{
  rebels: Array<Rebel>;
}>()

</script>

<template>
    <div>
        <h1 class="text-2xl font-semibold mb-4 text-light">Rebellen Übersicht</h1>
        <div class="bg-base rounded-xl w-full h-max border border-primary/40 shadow-xl overflow-hidden">
            <table class="min-w-full divide-y divide-primary/20">
            <thead class="bg-base-dark">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Faction</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Behavior</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Difficulty</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Defeated Count</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Fleet cap</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Last interaction</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Position</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Ressourcen</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-light uppercase tracking-wider">Raumschiffe</th>
                </tr>
            </thead>
            <tbody class="bg-base divide-y divide-primary/20">
                <tr v-for="rebel in rebels" :key="rebel.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">{{ rebel.id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">{{ rebel.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">{{ rebel.faction }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">{{ rebel.behavior }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">{{ rebel.difficulty_level }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">{{ rebel.defeated_count }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">{{ rebel.fleet_cap }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">{{ rebel.last_interaction ? new Date(rebel.last_interaction).toLocaleString() : 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">(x {{ rebel.x }}, y {{ rebel.y }})</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">
                        <ul>
                            <li v-for="res in rebel.resources" :key="res.id">{{ res.resource.name }}: {{ res.amount }}</li>
                        </ul>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-light">
                        <ul>
                            <li v-for="ship in rebel.spacecrafts" :key="ship.id">{{ ship.details.name }}: {{ ship.count }}</li>
                        </ul>
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
    </div>
</template>

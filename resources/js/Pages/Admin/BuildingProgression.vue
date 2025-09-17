<script lang="ts" setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

// Daten aus dem Backend empfangen
const props = defineProps({
    buildingTypes: {
        type: Array,
        default: () => []
    },
    progressionData: {
        type: Object,
        default: () => ({})
    },
    resourceRequirements: {
        type: Object,
        default: () => ({})
    }
});

// UI-State
const selectedBuilding = ref(props.buildingTypes.length > 0 ? props.buildingTypes[0].name : '');
const maxLevel = ref(30);

// Berechnungsfunktionen für Effektwerte basierend auf deinem Calculator
const calculateEffectValue = (building, level) => {
    const config = props.progressionData.buildingConfigs[building];
    if (!config) return 0;

    const { baseValue, increment, type } = config;

    switch (type) {
        case 'ADDITIVE':
            return baseValue + (level - 1) * increment;
        case 'MULTIPLICATIVE':
            return baseValue * (1 + (level - 1) * increment);
        case 'EXPONENTIAL':
            return baseValue * Math.pow(increment, level - 1);
        case 'LOGARITHMIC':
            return baseValue * (1 + Math.log(level) * increment);
        default:
            return baseValue + (level - 1) * increment;
    }
};

// Funktion zur Berechnung der Bauzeit
const calculateBuildTime = (level) => {
    return Math.floor(60 * Math.pow(props.progressionData.buildTimeMultiplier, level - 1));
};

// Funktion zur Berechnung der Kosten
const calculateCosts = (building, level) => {
    const baseCosts = props.progressionData.baseCosts[building];
    const growthFactor = props.progressionData.growthFactors[building] || 1.3;

    if (!baseCosts) return [];

    // Grundkosten-Multiplikator basierend auf Level
    let costMultiplier = Math.pow(growthFactor, level - 1);

    // Meilenstein-Multiplikatoren anwenden
    for (const [milestoneLevel, multiplier] of Object.entries(props.progressionData.milestoneMultipliers)) {
        if (level >= parseInt(milestoneLevel)) {
            costMultiplier *= multiplier;
        }
    }

    // Basis-Kosten für jede Ressource berechnen
    const costs = baseCosts.map(resource => ({
        resource: resource.resource,
        amount: Math.floor(resource.amount * costMultiplier),
        isBase: true
    }));

    // Zusätzliche Ressourcen hinzufügen
    const additionalResources = getAdditionalResourcesForLevel(building, level);
    const additionalResourceCosts = additionalResources.map(resource => ({
        resource,
        amount: Math.floor(175 * costMultiplier), // Beispielwert für zusätzliche Ressourcen
        isSpecial: true
    }));

    return [...costs, ...additionalResourceCosts];
};

// Bestimmung zusätzlicher Ressourcen basierend auf Level
const getAdditionalResourcesForLevel = (building, level) => {
    const buildingReqs = props.resourceRequirements[building];
    if (!buildingReqs) return [];

    let additionalResources = [];

    // Zusätzliche Ressourcen bei bestimmten Levels hinzufügen
    Object.entries(buildingReqs).forEach(([key, value]) => {
        if (key !== 'base' && key.startsWith('level_')) {
            const reqLevel = parseInt(key.split('_')[1]);
            if (level >= reqLevel) {
                additionalResources = [...additionalResources, ...value];
            }
        }
    });

    // Basisressourcen entfernen und Duplikate ausschließen
    const baseResources = buildingReqs.base || [];
    return [...new Set(additionalResources)].filter(res => !baseResources.includes(res));
};

// Generiert die Level-Progression-Daten für das ausgewählte Gebäude
const buildingProgression = computed(() => {
    if (!selectedBuilding.value) return [];
    
    const levels = [];
    for (let level = 1; level <= maxLevel.value; level++) {
        const effectValue = calculateEffectValue(selectedBuilding.value, level);
        const costs = calculateCosts(selectedBuilding.value, level);
        const buildTime = calculateBuildTime(level);

        levels.push({
            level,
            effectValue,
            costs,
            buildTime
        });
    }
    return levels;
});

// Format-Funktion für Zahlen
const formatNumber = (value) => {
    if (value >= 1_000_000) {
        return `${(value / 1_000_000).toFixed(1)}M`;
    } else if (value >= 1_000) {
        return `${(value / 1_000).toFixed(1)}K`;
    } else {
        return value.toFixed(isInteger(value) ? 0 : 2);
    }
};

// Hilfsfunktion zur Erkennung, ob eine Zahl ganzzahlig ist
const isInteger = (value) => {
    return Math.floor(value) === value;
};

// Formatiert den Effekttext für Anzeige
const formatEffectText = (building, value) => {
    const config = props.buildingTypes.find(b => b.name === building);
    if (!config) return `${formatNumber(value)}`;

    switch (config.effect) {
        case 'production_speed':
            return `x${formatNumber(value)}`;
        case 'storage':
        case 'scan_range':
        case 'crew_limit':
        case 'research_points':
            return `+${formatNumber(value)}`;
        case 'base_defense':
            return `x${formatNumber(value)}`;
        default:
            return formatNumber(value);
    }
};

// Formatiert Zeit in Stunden:Minuten:Sekunden
const formatTime = (timeInSeconds) => {
    const hours = Math.floor(timeInSeconds / 3600);
    const minutes = Math.floor((timeInSeconds % 3600) / 60);
    const seconds = timeInSeconds % 60;

    const pad = (num) => num.toString().padStart(2, '0');
    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
};
</script>

<template>
        <Head title="Building Progression" />

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-base text-light shadow-xl sm:rounded-lg p-6">
                    <h1 class="text-2xl font-semibold mb-6">Gebäude-Progression</h1>

                    <div class="mb-6 flex gap-4">
                        <!-- Gebäudeauswahl -->
                        <div class="mb-4 md:mb-0">
                            <label for="building-select" class="block text-sm font-medium mb-2">Gebäude auswählen</label>
                            <select id="building-select" v-model="selectedBuilding"
                                class="w-full px-3 py-2 border rounded-md bg-base-dark">
                                <option v-for="building in buildingTypes" :key="building.name" :value="building.name">
                                    {{ building.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Level-Auswahl -->
                        <div>
                            <label for="level-select" class="block text-sm font-medium mb-2">Max. Level</label>
                            <select id="level-select" v-model="maxLevel"
                                class="w-full px-3 py-2 border rounded-md bg-base-dark">
                                <option v-for="level in [10, 15, 20, 25, 30, 35, 40, 50]" :key="level" :value="level">
                                    Level {{ level }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Gebäudedetails -->
                    <div v-if="selectedBuilding" class="mb-6">
                        <h2 class="text-xl font-semibold mb-2">{{ selectedBuilding }}</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <span class="font-medium">Effekttyp:</span>
                                {{ buildingTypes.find(b => b.name === selectedBuilding)?.effectType }}
                            </div>
                            <div>
                                <span class="font-medium">Effekt:</span>
                                {{ buildingTypes.find(b => b.name === selectedBuilding)?.effect }}
                            </div>
                        </div>
                    </div>

                    <!-- Progressionstabelle -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-base-dark text-light">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Level
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Effektwert
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Bauzeit
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Ressourcen
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-base divide-y divide-gray-200 text-light">
                                <tr v-for="item in buildingProgression" :key="item.level"
                                    :class="{ 'bg-base-light': item.level % 5 === 0 }">
                                    <td class="px-4 py-2 whitespace-nowrap font-medium">
                                        {{ item.level }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        {{ formatEffectText(selectedBuilding, item.effectValue) }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        {{ formatTime(item.buildTime) }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex flex-wrap gap-2">
                                            <div v-for="cost in item.costs" :key="cost.resource" 
                                                 class="text-sm px-2 py-1 border rounded">
                                                {{ cost.resource }}: {{ formatNumber(cost.amount) }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</template>

<style scoped>
/* Hervorhebung von Meilensteinlevels */
tbody tr:nth-child(5n) {
    font-weight: 500;
}
</style>

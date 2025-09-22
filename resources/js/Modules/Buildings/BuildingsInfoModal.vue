<script lang="ts" setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Building } from '@/types/types';
import { numberFormat } from '@/Utils/format';

// Props
const props = defineProps<{
    show: boolean,
    buildings: Building[],
    initialBuildingId?: number
}>();
const emit = defineEmits<{
    (e: 'close'): void
}>();

const tabIndex = ref(0);

// API-Daten
const buildingInfo = ref<{
    name: string,
    image: string,
    description: string,
    costs: Record<number, any[]>,
    effects: Record<number, Record<string, number | string>>
} | null>(null);

const isLoading = ref(false);

const unlockDiffs = computed(() => {
    if (!buildingInfo.value) return {};
    const effects = buildingInfo.value.effects;
    const diffs: Record<number, string[]> = {};
    const levels = Object.keys(effects).map(Number).sort((a, b) => a - b);
    levels.forEach(lvl => {
        const current = effects[lvl]?.unlock ?? [];
        const prev = effects[lvl - 1]?.unlock ?? [];
        diffs[lvl] = current.filter((u: string) => !prev.includes(u));
    });
    return diffs;
});

function formatKey(key: string): string {
    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
}

function formatEffectValue(key: string, value: number | string): string {
    // Prozent-Effekte
    const percentKeys = ['upgrade_speed', 'production_speed', 'base_defense'];
    if (percentKeys.includes(key) && typeof value === 'number') {
        return `${Math.round((value as number - 1) * 100)}%`;
    }
    // Zahlen mit numberFormat
    const numberKeys = ['scan_range', 'storage', 'crew_limit'];
    if (numberKeys.includes(key) && typeof value === 'number') {
        return numberFormat(value as number);
    }
    // Standard
    return value?.toString() ?? '-';
}

async function fetchBuildingInfo(building: Building) {
    if (!building) return;
    isLoading.value = true;
    try {
        const res = await axios.get(route('buildings.info', building));
        buildingInfo.value = res.data;
    } catch (e) {
        buildingInfo.value = null;
    }
    isLoading.value = false;
}

// Tab auf initialBuildingId setzen, wenn Modal geöffnet wird
watch(() => props.show, (val) => {
    if (val && props.initialBuildingId) {
        const idx = props.buildings.findIndex(b => b.id === props.initialBuildingId);
        tabIndex.value = idx >= 0 ? idx : 0;
        fetchBuildingInfo(props.buildings[tabIndex.value]);
    }
});

// Bei Tab-Wechsel API neu abfragen
watch(tabIndex, (idx) => {
    fetchBuildingInfo(props.buildings[idx]);
});

</script>

<template>
    <DialogModal :show="props.show" @close="emit('close')" :max-width="'max'">
        <template #title>
            <div class="flex border-b border-primary-dark mb-4 relative">
                <button v-for="(b, idx) in props.buildings" :key="b.id" @click="tabIndex = idx"
                    class="relative px-4 py-2 font-medium text-base border-transparent outline-none transition" :class="[
                        tabIndex === idx
                            ? 'text-secondary font-bold bg-primary/20 rounded-t-xl shadow'
                            : 'text-light hover:text-secondary'
                    ]">
                    {{ b.name }}
                </button>

                <button type="button"
                    class="absolute top-0 right-0 px-1 py-1 rounded-xl text-white font-medium border-transparent border-solid hover:border-solid outline-none transition hover:bg-cyan-900/30"
                    @click="emit('close')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <path fill="currentColor"
                            d="m8.382 17.025l-1.407-1.4L10.593 12L6.975 8.4L8.382 7L12 10.615L15.593 7L17 8.4L13.382 12L17 15.625l-1.407 1.4L12 13.41z" />
                    </svg>
                </button>
            </div>
        </template>
        <template #content>
            <div style="min-height: 400px; position: relative;">
                <transition name="fade" mode="out-in">
                    <div v-if="!buildingInfo" key="loading"
                        class="absolute inset-0 flex items-center justify-center bg-primary/10 z-10">
                        Loading Building Info...
                    </div>
                    <div v-else="buildingInfo" key="content">
                        <div class="flex gap-6 items-center my-6 bg-primary/20 p-4 rounded-xl shadow-sm">
                            <img :src="buildingInfo.image" class="h-28 w-28 object-cover rounded-lg shadow" />
                            <div>
                                <p class="font-bold text-2xl text-light">{{ buildingInfo.name }}</p>
                                <p class="text-gray leading-relaxed">{{ buildingInfo.description }}</p>
                            </div>
                        </div>
                        <h3 class="font-semibold mb-2">Ressources & Effects</h3>
                        <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-primary/20">
                                <tr>
                                    <th class="p-2 text-center">Lv.</th>
                                    <th class="text-center p-2"
                                        v-for="res in buildingInfo.costs[Math.max(...Object.keys(buildingInfo.costs).map(Number))]"
                                        :key="'cost-' + res.id">
                                        <img :src="`/images/resources/${res.name}.png`" :alt="res.name"
                                            class="h-6 w-6 mx-auto" />
                                    </th>
                                    <th class="p-2 text-center"
                                        v-for="(val, key) in buildingInfo.effects[Math.max(...Object.keys(buildingInfo.effects).map(Number))] || {}"
                                        :key="'effect-' + key">
                                        {{ formatKey(key) }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(lvl, idx) in Object.keys(buildingInfo.costs).map(Number).sort((a, b) => a - b)"
                                    :key="lvl" 
                                    :class="[
                                        idx % 2 === 0 ? '' : 'bg-primary/20',
                                        lvl === props.buildings[tabIndex].level ? 'bg-secondary/30 font-bold ring-1 ring-secondary hover:bg-secondary/40' : '',
                                    ]"
                                    class="hover:bg-primary/30 transition">
                                    <td class="text-center">{{ lvl }}</td>
                                    <td v-for="res in buildingInfo.costs[Math.max(...Object.keys(buildingInfo.costs).map(Number))]"
                                        :key="'cost-' + res.id" class="text-center p-2">
                                        {{
                                            (() => {
                                                const amount = buildingInfo.costs[lvl]?.find(r => r.id === res.id)?.amount;
                                                return amount !== undefined ? numberFormat(amount) : '-';
                                            })()
                                        }}
                                    </td>
                                    <td v-for="(val, key) in buildingInfo.effects[Math.max(...Object.keys(buildingInfo.effects).map(Number))] || {}"
                                        :key="'effect-' + key" class="text-center p-2">
                                        <template v-if="key === 'unlock'">
                                            {{ unlockDiffs[lvl]?.join(', ') || '-' }}
                                        </template>
                                        <template v-else>
                                            {{
                                                buildingInfo.effects[lvl] && key in buildingInfo.effects[lvl]
                                                    ? formatEffectValue(key, buildingInfo.effects[lvl][key])
                                                    : '-'
                                            }}
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </transition>

            </div>
        </template>
    </DialogModal>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
}
</style>

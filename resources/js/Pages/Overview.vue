<script lang="ts" setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Building, Spacecraft, RawQueueItem } from '@/types/types';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import SectionHeader from '@/Components/SectionHeader.vue';
import { timeFormat, numberFormat } from '@/Utils/format';

const props = defineProps<{
  buildings: Building[],
  spacecrafts: Spacecraft[],
  queue: RawQueueItem[],
}>()

const page = usePage()

const getTypeIcon = (type) => {
  switch (type) {
    case 'Fighter': return '/storage/navigation/simulator.png';
    case 'Miner': return '/storage/attributes/storage.png';
    case 'Transporter': return '/storage/supply-chain_light.png';
    case 'mining': return '/storage/navigation/asteroidmap.png';
    case 'combat': return '/storage/navigation/simulator.png';
    default: return '';
  }
};

const unlockedSpacecrafts = computed(() => props.spacecrafts.filter(spacecraft => spacecraft.unlocked));

const currentTime = ref(new Date().getTime());

const getRemainingTime = (item: RawQueueItem): number => {
  if (!item.end_time) return 0

  const endTime = new Date(item.end_time).getTime()

  return Math.max(0, endTime - currentTime.value)
}

const FormattedTime = (item) => {
  const remainingTimeMs = getRemainingTime(item)
  return timeFormat(Math.floor(remainingTimeMs / 1000));
}

const queueBuildings = computed(() => props.queue.filter(item => item.action_type === 'building'));
const queueSpacecrafts = computed(() => props.queue.filter(item => item.action_type === 'produce'));
const queueMining = computed(() => props.queue.filter(item => item.action_type === 'mining'));
const queueCombat = computed(() => props.queue.filter(item => item.action_type === 'combat'));

const totalSpacecraftsInOrbit = computed(() => props.queue.reduce((acc, item) => {
  if (item.action_type === 'combat') {
    const totalSpacecrafts = item.details.attacker_formatted.reduce((acc, spacecraft) => acc + spacecraft.count, 0);
    acc += totalSpacecrafts;
  }
  if (item.action_type === 'mining') {
    const totalSpacecrafts = Object.values(item.details.spacecrafts as Record<string, number>).reduce((acc, count) => acc + count, 0);
    acc += totalSpacecrafts;
  }
  return acc;
}, 0));

const totalMiningOperations = computed(() => props.queue.reduce((acc, item) => {
  if (item.action_type === 'mining') {
    acc++;
  }
  return acc;
}, 0));

const getBuildingQueueItem = computed(() => (buildingId: number) => {
  return queueBuildings.value.find(item => item.target_id === buildingId);
});

const getSpacecraftsQueueItem = computed(() => (spacecraftId: number) => {
  return queueSpacecrafts.value.find(item => item.target_id === spacecraftId);
});

// Neue Funktion zur Anzeige formatierter Effekte
const getBuildingEffectDisplay = (building) => {
  if (building.current_effects && building.current_effects.length > 0) {
    return building.current_effects[0].display;
  }
};

const fleetSummary = computed(() => ({
  totalCount: props.spacecrafts.reduce((acc, spacecraft) => acc + spacecraft.count, 0),
  totalCrew: page.props.userAttributes?.find(item => item.attribute_name === 'total_units')?.attribute_value || 0,
  totalCombat: props.spacecrafts.reduce((acc, spacecraft) => acc + (spacecraft.combat * spacecraft.count), 0),
  totalCargo: props.spacecrafts.reduce((acc, spacecraft) => acc + (spacecraft.cargo * spacecraft.count), 0),
}));

const crewLimit = computed(() => {
  const userAttributes = page.props.userAttributes;
  if (!userAttributes) return 0;

  const crewLimitAttribute = userAttributes.find(item => item.attribute_name === 'crew_limit');
  return crewLimitAttribute ? crewLimitAttribute.attribute_value : 0;
});

let timerInterval: number | undefined
onMounted(() => {
  timerInterval = setInterval(() => {
    currentTime.value = new Date().getTime();
  }, 1000);
})

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})
</script>

<template>
  <AppLayout title="overview">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 2xl:gap-8">

      <!-- Buildings -->
      <div class="bg-base rounded-xl w-full border-primary border-4 border-solid content_card">
        <SectionHeader title="Buildings" iconSrc="/storage/navigation/buildings.png" :route="route('buildings')"
          :isPrimary="true" />
        <table class="w-full text-light mt-1">
          <thead class="text-gray-400 border-b border-primary">
            <tr>
              <th class="text-left p-2">Name</th>
              <th class="text-left p-2">Level</th>
              <th class="text-left p-2">Effect</th>
              <th class="text-left p-2">Upgrade</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="building in buildings" :key="building.id">
              <td class="p-2">{{ building.name }}</td>
              <td class="p-2">{{ building.level }}</td>
              <td class="p-2">{{ getBuildingEffectDisplay(building) }}</td>
              <td class="p-2">
                <template v-if="getBuildingQueueItem(building.id)">
                  {{ FormattedTime(getBuildingQueueItem(building.id)) }}
                </template>
                <template v-else>
                  -
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Rest des Templates bleibt gleich -->
      <!-- Spacecrafts -->
      <div class="bg-base rounded-xl w-full border-primary border-4 border-solid content_card">
        <SectionHeader title="Shipyard" iconSrc="/storage/navigation/shipyard.png" :route="route('shipyard')"
          :isPrimary="true" />

        <table class="w-full text-light mt-1">
          <thead class="text-gray-400 border-b border-primary">
            <tr>
              <th class="text-left p-2">Name</th>
              <th class="text-left p-2">Type</th>
              <th class="text-left p-2">Count</th>
              <th class="text-left p-2">Crew</th>
              <th class="text-left p-2">Combat</th>
              <th class="text-left p-2">Cargo</th>
              <th class="text-left p-2">Produce</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="spacecraft in unlockedSpacecrafts" :key="spacecraft.id">
              <td class="p-2">{{ spacecraft.name }}</td>
              <td class="p-2">
                <div class="relative group flex">
                  <img :src="getTypeIcon(spacecraft.type)" alt="Type Icon" class="w-6 h-6">
                  <AppTooltip :label="spacecraft.type" position="left" />
                </div>
              </td>
              <td class="p-2">{{ spacecraft.count }}</td>
              <td class="p-2">{{ spacecraft.crew_limit }}</td>
              <td class="p-2">{{ spacecraft.combat }}</td>
              <td class="p-2">{{ spacecraft.cargo }}</td>
              <td class="p-2">
                <template v-if="getSpacecraftsQueueItem(spacecraft.id)">
                  {{ getSpacecraftsQueueItem(spacecraft.id)?.details.quantity }} - {{
                    FormattedTime(getSpacecraftsQueueItem(spacecraft.id)) }}
                </template>
                <template v-else>
                  -
                </template>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="border-t border-primary bg-primary rounded-b-xl">
              <td class="px-2 py-3" colspan="2">Fleet Summary</td>
              <td class="p-2">
                {{ fleetSummary.totalCount }}
              </td>
              <td class="p-2 text-nowrap">
                {{ fleetSummary.totalCrew }} / {{ crewLimit }}
              </td>
              <td class="p-2">
                {{ fleetSummary.totalCombat }}
              </td>
              <td class="p-2">
                {{ fleetSummary.totalCargo }}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- AsteroidMap -->
      <div class="bg-base rounded-xl w-full border-primary border-4 border-solid content_card">
        <SectionHeader title="Asteroid Map" iconSrc="/storage/navigation/asteroidmap.png" :route="route('asteroidMap')"
          :isPrimary="true" />

        <table class="w-full text-light mt-1">
          <thead class="text-gray-400 border-b border-primary">
            <tr>
              <th class="text-left p-2">Name</th>
              <th class="text-left p-2">Type</th>
              <th class="text-left p-2">Spacecrafts</th>
              <th class="text-left p-2">End Time</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="queueCombat.length > 0 || queueMining.length > 0">
              <tr v-for="combat in queueCombat" :key="combat.id">
                <td class="p-2"><span class="text-secondary">attack</span> {{ combat.details.defender_name }}</td>
                <td class="p-2">
                  <div class="relative group flex">
                    <img :src="getTypeIcon(combat.action_type)" alt="Type Icon" class="w-6 h-6">
                    <AppTooltip :label="combat.action_type" position="left" />
                  </div>
                </td>
                <td class="p-2">
                  {{combat.details.attacker_formatted.reduce((acc, spacecraft) => acc + spacecraft.count, 0)}}
                </td>
                <td class="p-2">{{ FormattedTime(combat) }}</td>
              </tr>

              <tr v-for="mining in queueMining" :key="mining.id">
                <td class="p-2">{{ mining.details.asteroid_name }}</td>
                <td class="p-2">
                  <div class="relative group flex">
                    <img :src="getTypeIcon(mining.action_type)" alt="Type Icon" class="w-6 h-6">
                    <AppTooltip :label="mining.action_type" position="left" />
                  </div>
                </td>
                <td class="p-2">
                  {{Object.values(mining.details.spacecrafts as Record<string, number>).reduce((acc, count) => acc +
                    count, 0) }}
                </td>
                <td class="p-2">{{ FormattedTime(mining) }}</td>
              </tr>
            </template>
            <template v-else>
              <tr>
                <td class="p-2">-</td>
                <td class="p-2">-</td>
                <td class="p-2">-</td>
                <td class="p-2">-</td>
              </tr>
            </template>
          </tbody>
          <tfoot>
            <tr class="border-t border-primary bg-primary rounded-b-xl">
              <td class="px-2 py-3" colspan="3">
                {{ totalSpacecraftsInOrbit }} Spacecrafts in Orbit • {{ totalMiningOperations }} Mining Operations
              </td>
              <td></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

    </div>
  </AppLayout>
</template>

<style scoped>
.content_card {
  --shadow-color: 210deg 30% 15%;
  --glow-color: 210deg 70% 50%;

  box-shadow: 1px 1px 1.6px hsl(var(--shadow-color) / 0.3),
    3.5px 3.5px 5.6px -0.8px hsl(var(--shadow-color) / 0.3),
    8.8px 8.8px 14px -1.7px hsl(var(--shadow-color) / 0.35),
    0 0 20px -2px hsl(var(--glow-color) / 0.15);
}
</style>

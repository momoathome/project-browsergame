<script lang="ts" setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Building, Spacecraft, RawQueueItem } from '@/types/types';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import SectionHeader from '@/Components/SectionHeader.vue';
import { timeFormat, numberFormat } from '@/Utils/format';
import { useQueueStore } from '@/Composables/useQueueStore';

const { queueData } = useQueueStore();

const props = defineProps<{
  buildings: Building[],
  spacecrafts: Spacecraft[],
}>()

const page = usePage()

const getTypeIcon = (type) => {
  switch (type) {
    case 'Fighter': return '/images/navigation/simulator.png';
    case 'Miner': return '/images/attributes/storage.png';
    case 'Transporter': return '/images/cargo.png';
    case 'mining': return '/images/navigation/asteroidmap.png';
    case 'combat': return '/images/navigation/simulator.png';
    default: return '';
  }
};

const unlockedSpacecrafts = computed(() => props.spacecrafts.filter(spacecraft => spacecraft.unlocked));

const currentTime = ref(new Date().getTime());

const getRemainingTime = (item: RawQueueItem): number => {
  if (!item.endTime) return 0

  const endTime = new Date(item.endTime).getTime()

  return Math.max(0, endTime - currentTime.value)
}

const FormattedTime = (item) => {
  const remainingTimeMs = getRemainingTime(item)
  return timeFormat(Math.floor(remainingTimeMs / 1000));
}

const queueBuildings = computed(() => queueData.value.filter(item => item.actionType === 'building'));
const queueSpacecrafts = computed(() => queueData.value.filter(item => item.actionType === 'produce'));
const queueMining = computed(() => queueData.value.filter(item => item.actionType === 'mining'));
const queueCombat = computed(() => queueData.value.filter(item => item.actionType === 'combat'));

const totalSpacecraftsInOrbit = computed(() => queueData.value.reduce((acc, item) => {
  if (item.actionType === 'combat') {
    const totalSpacecrafts = item.details.attacker_formatted.reduce((acc, spacecraft) => acc + spacecraft.count, 0);
    acc += totalSpacecrafts;
  }
  if (item.actionType === 'mining') {
    const totalSpacecrafts = Object.values(item.details.spacecrafts as Record<string, number>).reduce((acc, count) => acc + count, 0);
    acc += totalSpacecrafts;
  }
  return acc;
}, 0));

const spacecraftsInOrbit = computed(() => {
  const result: Record<string, number> = {};
  props.spacecrafts.forEach(sc => {
    result[sc.id] = 0;
  });

  queueData.value.forEach(item => {
    if (item.actionType === 'combat' && item.details.attacker_formatted) {
      item.details.attacker_formatted.forEach((sc: any) => {
        if (result[sc.id] !== undefined) {
          result[sc.id] += sc.count;
        } else {
          result[sc.id] = sc.count;
        }
      });
    }
    if (item.actionType === 'mining' && item.details.spacecrafts) {
      Object.entries(item.details.spacecrafts as Record<string, number>).forEach(([name, count]) => {
        if (result[name] !== undefined) {
          result[name] += count;
        } else {
          result[name] = count;
        }
      });
    }
  });

  return result;
});

const totalMiningOperations = computed(() => queueData.value.reduce((acc, item) => {
  if (item.actionType === 'mining') {
    acc++;
  }
  return acc;
}, 0));

const getBuildingQueueItem = computed(() => (buildingId: number) => {
  return queueBuildings.value.find(item => item.targetId === buildingId);
});

const getSpacecraftsQueueItem = computed(() => (spacecraftId: number) => {
  return queueSpacecrafts.value.find(item => item.targetId === spacecraftId);
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

const displayQueueTime = (item: RawQueueItem) => {
  return getRemainingTime(item) === 0 ? 'processing...' : FormattedTime(item)
}
</script>

<template>
  <AppLayout title="overview">
    <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4 2xl:gap-8">

      <!-- Buildings -->
      <div class="bg-base rounded-xl w-full border-primary border-4 border-solid content_card">
        <SectionHeader title="Buildings" iconSrc="/images/navigation/buildings.png" :route="route('buildings')"
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
                  <span>{{ displayQueueTime(getBuildingQueueItem(building.id)) }}</span>
                </template>
                <template v-else>
                  -
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Spacecrafts -->
      <div class="bg-base rounded-xl w-full border-primary border-4 border-solid content_card">
        <SectionHeader title="Shipyard" iconSrc="/images/navigation/shipyard.png" :route="route('shipyard')"
          :isPrimary="true" />

        <table class="w-full text-light mt-1">
          <thead class="text-gray-400 border-b border-primary">
            <tr>
              <th class="text-left p-2">Name</th>
              <th class="text-left p-2">Type</th>
              <th class="text-left p-2">Crew</th>
              <th class="text-left p-2">Combat</th>
              <th class="text-left p-2">Quantity</th>
              <th class="text-left p-2">InOrbit</th>
              <th class="text-left p-2">Production</th>
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
              <td class="p-2">{{ spacecraft.crew_limit }}</td>
              <td class="p-2">{{ numberFormat(spacecraft.combat) }}</td>
              <td class="p-2">{{ spacecraft.count }}</td>
              <td class="p-2">{{ spacecraftsInOrbit[spacecraft.name] || 0 }}</td>
              <td class="p-2">
                <template v-if="getSpacecraftsQueueItem(spacecraft.id)">
                  <span>{{ displayQueueTime(getSpacecraftsQueueItem(spacecraft.id)) }}</span>
                </template>
                <template v-else>
                  -
                </template>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="border-t border-primary bg-primary rounded-b-xl">
              <td class="px-2 py-3" colspan="2">Summary</td>
              <td class="p-2 text-nowrap">
                {{ Math.floor(fleetSummary.totalCrew) }} / {{ Math.floor(crewLimit) }}
              </td>
              <td class="p-2">
                {{ numberFormat(fleetSummary.totalCombat) }}
              </td>
              <td class="p-2">
                {{ fleetSummary.totalCount }}
              </td>
              <td class="p-2">
                {{ totalSpacecraftsInOrbit }}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- AsteroidMap -->
      <div class="bg-base rounded-xl w-full border-primary border-4 border-solid content_card">
        <SectionHeader title="Asteroid Map" iconSrc="/images/navigation/asteroidmap.png" :route="route('asteroidMap')"
          :isPrimary="true" />

        <table class="w-full text-light mt-1">
          <thead class="text-gray-400 border-b border-primary">
            <tr>
              <th class="text-left p-2">Name</th>
              <th class="text-left p-2">Type</th>
              <th class="text-left p-2">Spacecrafts</th>
              <th class="text-left p-2">Duration</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="queueCombat.length > 0 || queueMining.length > 0">
              <tr v-for="combat in queueCombat" :key="combat.id">
                <td class="p-2"><span class="text-secondary">attack</span> {{ combat.details.defender_name }}</td>
                <td class="p-2">
                  <div class="relative group flex">
                    <img :src="getTypeIcon(combat.actionType)" alt="Type Icon" class="w-6 h-6">
                    <AppTooltip :label="combat.actionType" position="left" />
                  </div>
                </td>
                <td class="p-2">
                  {{combat.details.attacker_formatted.reduce((acc, spacecraft) => acc + spacecraft.count, 0)}}
                </td>
                <td class="p-2">
                    <span>
                      {{ displayQueueTime(combat) }}
                    </span>
                </td>
              </tr>

              <tr v-for="mining in queueMining" :key="mining.id">
                <td class="p-2">{{ mining.details.asteroid_name }}</td>
                <td class="p-2">
                  <div class="relative group flex items-center">
                    <img :src="getTypeIcon(mining.actionType)" alt="Type Icon" class="w-6 h-6">
                    <AppTooltip :label="mining.actionType" position="left" />
                  </div>
                </td>
                <td class="p-2 flex group">
                  <div class="relative w-max flex items-center">
                    {{ Object.values(mining.details.spacecrafts as Record<string, number>).reduce((acc, count) => acc + count, 0) }}
                    <AppTooltip class="!ml-2" position="right"
                      :label="Object.entries(mining.details.spacecrafts as Record<string, number>)
                        .map(([name, count]) => `${name}: ${count}`)
                        .join('\n')"
                    />
                  </div>
                </td>
                <td class="p-2">
                  {{ displayQueueTime(mining) }}
                </td>
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

<script lang="ts" setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';
import { useBuildingStore } from '@/Composables/useBuildingStore';
import { useQueue } from '@/Composables/useQueue'
import QueueSlot from '@/Modules/App/QueueSlot.vue';
import { timeFormat, numberFormat } from '@/Utils/format';

const page = usePage()
const userId = page.props.auth?.user?.id ?? null;
const { spacecrafts } = useSpacecraftStore();
const { buildings } = useBuildingStore();
const { processedQueueItems, refresh } = useQueue()

const getTypeIcon = (type) => {
  switch (type) {
    case 'Fighter': return '/images/spacecraftTypes/Fighter.png';
    case 'Miner': return '/images/spacecraftTypes/Miner.png';
    case 'Transporter': return '/images/spacecraftTypes/Transporter.png';
    case 'mining': return '/images/navigation/asteroidmap.png';
    case 'combat': return '/images/navigation/simulator.png';
    case 'building': return '/images/navigation/buildings.png';
    case 'produce': return '/images/space-craft.png';
    default: return '';
  }
};

const sortedQueueItems = computed(() =>
  processedQueueItems.value?.slice().sort((a, b) => {
    const statusOrder = { in_progress: 0, processing: 1, pending: 2 }
    const statusDiff = statusOrder[a.status] - statusOrder[b.status]
    if (statusDiff !== 0) return statusDiff

    // end_time als Fallback (frühere zuerst)
    const aEnd = new Date(a.rawData.endTime ?? 0).getTime()
    const bEnd = new Date(b.rawData.endTime ?? 0).getTime()
    return aEnd - bEnd
  })
)

const queueBuildings = computed(() => (sortedQueueItems.value ?? []).filter(item => item.rawData.actionType === 'building'));
const queueSpacecrafts = computed(() => (sortedQueueItems.value ?? []).filter(item => item.rawData.actionType === 'produce'))
const queueMining = computed(() => (sortedQueueItems.value ?? []).filter(item => item.rawData.actionType === 'mining'));
const queueCombat = computed(() => (sortedQueueItems.value ?? []).filter(item => item.rawData.actionType === 'combat'));

const activeBuildingQueue = computed(() =>
  queueBuildings.value.filter(q => q.status === 'in_progress' || q.status === 'processing')
);
const pendingBuildingQueue = computed(() =>
  queueBuildings.value.filter(q => q.status === 'pending')
);

const activeProductionQueue = computed(() =>
  queueSpacecrafts.value.filter(q => q.status === 'in_progress' || q.status === 'processing')
);
const pendingProductionQueue = computed(() =>
  queueSpacecrafts.value.filter(q => q.status === 'pending')
);

const activeDockQueue = computed(() =>
  queueMining.value.filter(q => q.status === 'in_progress' || q.status === 'processing')
);
const pendingDockQueue = computed(() =>
  queueMining.value.filter(q => q.status === 'pending')
);

const totalMiningOperations = computed(() => (sortedQueueItems.value ?? []).reduce((acc, item) => {
  if (item.rawData.actionType === 'mining') {
    acc++;
  }
  return acc;
}, 0));
const totalCombatOperations = computed(() => (sortedQueueItems.value ?? []).reduce((acc, item) => {
  if (item.rawData.actionType === 'combat') {
    acc++;
  }
  return acc;
}, 0));

const buildingSlots = computed(() => {
  const core = buildings.value?.find(b => b.effect?.current?.building_slots);
  return core?.effect?.current?.building_slots ?? 0;
});
const productionSlots = computed(() => {
  const shipyard = buildings.value?.find(b => b.effect?.current?.production_slots);
  return shipyard?.effect?.current?.production_slots ?? 0;
});
const dockSlots = computed(() => {
  const hangar = buildings.value?.find(b => b.effect?.current?.dock_slots);
  return hangar?.effect?.current?.dock_slots ?? 0;
});

const miningSlotsCollapsed = ref(true);
function handleMiningCollapse() {
  miningSlotsCollapsed.value = !miningSlotsCollapsed.value;
  localStorage.setItem('miningSlotsCollapsed', miningSlotsCollapsed.value.toString());
}

onMounted(() => {
  const stored = localStorage.getItem('miningSlotsCollapsed');
  if (stored !== null) {
    miningSlotsCollapsed.value = stored === 'true';
  }

  window.Echo.private(`user.combat.${userId}`)
    .listen('.user.attacked', refresh)
});

const fleetSummary = computed(() => ({
  totalCount: spacecrafts.value?.reduce((acc, spacecraft) => acc + spacecraft.count, 0),
  totalAttack: spacecrafts.value?.reduce((acc, spacecraft) => acc + (spacecraft.attack * spacecraft.count), 0),
  totalDefense: spacecrafts.value?.reduce((acc, spacecraft) => acc + (spacecraft.defense * spacecraft.count), 0),
  totalCargo: spacecrafts.value?.reduce((acc, spacecraft) => acc + (spacecraft.cargo * spacecraft.count), 0),
  totalMiner: spacecrafts.value?.reduce((acc, spacecraft) => acc + (spacecraft.type === 'Miner' ? spacecraft.count : 0), 0),
  totalInOrbit: totalInCombat.value + totalMinersInOperation.value,
  totalCrew: page.props.userAttributes?.find(item => item.attribute_name === 'total_units')?.attribute_value || 0,
}));

const totalInCombat = computed(() => {
  return (processedQueueItems.value ?? [])
    .filter(item => item.name === 'Combat' && item.rawData?.details?.attacker_formatted)
    .reduce((sum, item) => {
      const attackerUnits = item.rawData.details.attacker_formatted;
      return sum + attackerUnits.reduce((acc, unit) => acc + (unit.count || 0), 0);
    }, 0);
});

const totalMinersInOperation = computed(() => {
  return (processedQueueItems.value ?? [])
    .filter(item => item.name === 'Mining' && item.rawData?.details?.spacecrafts)
    .reduce((sum, item) => {
      const spacecrafts = item.rawData.details.spacecrafts;
      // Summe aller Werte im spacecrafts-Objekt
      return sum + Object.values(spacecrafts ?? {}).reduce((acc, val) => acc + (val || 0), 0);
    }, 0);
});

const displayQueueTime = (item) => {
  if (item.status === 'pending') return 'pending...'
  return item.formattedTime
}
</script>

<template>
  <div class="flex flex-col gap-2 fancy-scroll sidebarOverview overflow-y-auto bg-root px-4 py-6 text-light">
    <div class="flex items-center gap-2 mb-1">
      <img src="/images/navigation/overview.png" alt="overview" class="h-7" />
      <h1 class="font-semibold text-lg">Overview</h1>
    </div>

    <div class="flex flex-col gap-2 border-y border-primary/30 py-3">
      <div class="flex items-center gap-2">
        <img src="/images/space-craft.png" alt="spacecraft" class="h-5" />
        <h2 class="font-semibold text-sm">in Orbit • {{ fleetSummary.totalInOrbit }} / {{ fleetSummary.totalCount }}</h2>
      </div>
      <div class="flex items-center gap-2">
        <img src="/images/asteroid.png" alt="asteroid" class="h-5" />
        <h2 class="font-semibold text-sm">active Miner • {{ totalMinersInOperation }} / {{ fleetSummary.totalMiner }}</h2>
      </div>
      <div class="flex items-center gap-2">
        <img src="/images/combat.png" alt="combat" class="h-5" />
        <h2 class="font-semibold text-sm">in Combat • {{ totalInCombat }}</h2>
      </div>
    </div>

    <div v-for="combat in queueCombat" :key="combat.id"
      class="flex items-center space-x-4 rounded-md bg-root border border-white/10 px-3 py-3 mb-2 transition"
      :class="{ 'border-red-600 !bg-red-900': combat.rawData.details.defender_name === page.props.auth.user.name }">
      <img :src="getTypeIcon(combat.rawData.actionType)" alt="type icon" class="h-6 w-6" />
      <div class="flex-1">
        <div class="flex items-center justify-between gap-2">
          <p class="text-sm font-medium leading-none">
            Attack {{ combat.rawData.details.defender_name === page.props.auth.user.name ? 'on you' : 'on ' +
            combat.rawData.details.defender_name }}
          </p>
          <span class="text-sm">{{ displayQueueTime(combat) }}</span>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-2">
      <template v-for="n in buildingSlots" :key="n">
        <QueueSlot
          :activeItem="activeBuildingQueue[n - 1]"
          :icon="activeBuildingQueue[n - 1] ? getTypeIcon(activeBuildingQueue[n - 1].rawData.actionType) : '/images/buildings.png'"
          emptyIcon="/images/buildings.png"
          :slotNumber="Number(n)"
          :title="activeBuildingQueue[n - 1]?.rawData.details.building_name || ''"
          :subtitle="activeBuildingQueue[n - 1] ? 'Upgrade to lv. ' + activeBuildingQueue[n - 1].rawData.details.next_level : ''"
          :pendingCount="pendingBuildingQueue.length"
          transitionName="fadePulse"
          :displayQueueTime="displayQueueTime"
        />
      </template>
    </div>

    <div class="flex flex-col gap-2">
      <template v-for="n in productionSlots" :key="n">
        <QueueSlot
          :activeItem="activeProductionQueue[n - 1]"
          :icon="activeProductionQueue[n - 1] ? getTypeIcon(activeProductionQueue[n - 1].rawData.actionType) : '/images/space-craft.png'"
          emptyIcon="/images/space-craft.png"
          :slotNumber="Number(n)"
          :title="activeProductionQueue[n - 1]?.rawData.details.spacecraft_name || ''"
          :subtitle="activeProductionQueue[n - 1] ? 'Quantity: ' + activeProductionQueue[n - 1].rawData.details.quantity : ''"
          :pendingCount="pendingProductionQueue.length"
          transitionName="fadePulse"
          :displayQueueTime="displayQueueTime"
        />
      </template>
    </div>

    <div class="flex flex-col gap-2 mt-4 relative">
      <div class="flex justify-between items-center gap-2 mb-1">
        <img src="/images/asteroid.png" alt="asteroid" class="h-5" />
        <h2 class="font-semibold text-sm">Mining Operations</h2>
        <span class="text-xs text-muted-foreground">
          ({{ totalMiningOperations }}/{{ dockSlots }})
        </span>
        <button @click.stop="handleMiningCollapse"
          class="ml-auto rounded-full bg-white/10 hover:bg-primary/30 transition p-1 flex items-center"
          style="opacity:0.7;" aria-label="Mining ein-/ausklappen">
          <svg :style="{ transform: miningSlotsCollapsed ? 'rotate(-90deg)' : 'rotate(0deg)' }" class="transition"
            width="16" height="16" viewBox="0 0 20 20" fill="none">
            <path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round" />
          </svg>
        </button>
      </div>
      <transition name="fade" mode="default">
        <div v-show="!miningSlotsCollapsed" class="flex flex-col gap-2">
          <template v-for="n in dockSlots" :key="Number(n)">
            <QueueSlot
              :activeItem="activeDockQueue[n - 1]"
              :icon="activeDockQueue[n - 1] ? getTypeIcon(activeDockQueue[n - 1].rawData.actionType) : '/images/asteroid.png'"
              emptyIcon="/images/asteroid.png"
              :slotNumber="Number(n)"
              :title="activeDockQueue[n - 1]?.rawData.actionType || ''"
              :subtitle="activeDockQueue[n - 1]?.rawData.details.asteroid_name ? 'Asteroid: ' + activeDockQueue[n - 1].rawData.details.asteroid_name : ''"
              :pendingCount="pendingDockQueue.length"
              transitionName="fadePulse"
              :displayQueueTime="displayQueueTime"
            />
          </template>
        </div>
      </transition>
    </div>

  </div>
</template>

<style scoped>
.sidebarOverview {
  width: 16vw;
}

@media (max-width: 1700px) {
  .sidebarOverview {
    width: 18vw;
  }
}

@media (min-width: 2160px) {
  .sidebarOverview {
    width: 12vw;
  }
}

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

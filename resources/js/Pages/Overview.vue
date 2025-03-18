<script lang="ts" setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Building, Spacecraft, RawQueueItem } from '@/types/types';
import AppTooltip from '@/Components/AppTooltip.vue';
import SectionHeader from '@/Components/SectionHeader.vue';

const props = defineProps<{
  buildings: Building[],
  spacecrafts: Spacecraft[],
  queue: RawQueueItem[],
}>()

const getTypeIcon = (type) => {
  switch (type) {
    case 'Fighter': return '/storage/navigation/simulator.png';
    case 'Miner': return '/storage/attributes/storage.png';
    case 'Transporter': return '/storage/supply-chain_light.png';
    default: return '';
  }
};

</script>

<template>
  <AppLayout title="overview">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">

      <div class="flex flex-col gap-4">
        <!-- Buildings -->
        <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
          <SectionHeader title="Buildings" iconSrc="/storage/navigation/buildings.png" :route="route('buildings')"
            :isPrimary="true" />
          <!-- List of Buildings -->
          <table class="w-full text-light mt-1">
            <thead class="text-gray-400 border-b border-primary">
              <tr>
                <th class="text-left p-2">Name</th>
                <th class="text-left p-2">Level</th>
                <th class="text-left p-2">Effect</th>
                <th class="text-left p-2">Effect Value</th>
                <th class="text-left p-2">Upgrade</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="building in buildings" :key="building.id">
                <td class="p-2">{{ building.details.name }}</td>
                <td class="p-2">{{ building.level }}</td>
                <td class="p-2">{{ building.details.effect }}</td>
                <td class="p-2">{{ building.effect_value }}</td>
                <td class="p-2">{{ building.is_upgrading ? 'Upgrading' : 'Not Upgrading' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- UserQueue -->
        <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
          <SectionHeader title="Queue" iconSrc="/storage/hourglass.svg" />
          <div class="px-4 py-2">
            <span v-if="queue.length === 0" class="text-gray-400 text-xs p-1">no active items in queue</span>
            <!-- List of queue -->
            <ul class="text-light mt-1">
              <li v-for="item in queue" :key="item.id">
                <span v-if="item.action_type === 'building'">
                  {{ item.details.building_name }} upgrade to lv. {{ item.details.next_level }}
                </span>
                <span v-else-if="item.action_type === 'produce'">
                  {{ item.details.spacecraft_name }} produce quantity: {{ item.details.quantity }}
                </span>
                <span v-else-if="item.action_type === 'mining'" class="flex justify-between w-full">
                  Mining: {{ item.details.asteroid_name }}
                </span>
                <span v-else>{{ item.details }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Spacecrafts -->
      <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
        <SectionHeader title="Shipyard" iconSrc="/storage/navigation/shipyard.png" :route="route('shipyard')"
          :isPrimary="true" />

        <!-- List of Spacecrafts -->
        <table class="w-full text-light mt-1">
          <thead class="text-gray-400 border-b border-primary">
            <tr>
              <th class="text-left p-2">Name</th>
              <th class="text-left p-2">Type</th>
              <th class="text-left p-2">Count</th>
              <th class="text-left p-2">Combat</th>
              <th class="text-left p-2">Cargo</th>
              <th class="text-left p-2">Upgrade</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="spacecraft in spacecrafts" :key="spacecraft.id">
              <td class="p-2">{{ spacecraft.details.name }}</td>
              <td class="p-2">
                <div class="relative group flex">
                  <img :src="getTypeIcon(spacecraft.details.type)" alt="Type Icon" class="w-6 h-6">
                  <AppTooltip :label="spacecraft.details.type" position="left" />
                </div>
              </td>
              <td class="p-2">{{ spacecraft.count }}</td>
              <td class="p-2">{{ spacecraft.combat }}</td>
              <td class="p-2">{{ spacecraft.cargo }}</td>
              <td class="p-2">{{ spacecraft.is_producing ? 'Producing' : 'Not Producing' }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="border-t border-primary bg-primary rounded-b-xl pb-4">
              <td class="p-2" colspan="2">Fleet Summary</td>
              <td class="p-2">
                {{spacecrafts.reduce((acc, spacecraft) => acc + spacecraft.count, 0)}}
              </td>
              <td class="p-2">
                {{spacecrafts.filter(spacecraft => spacecraft.count > 0).reduce((acc, spacecraft) => acc +
                  spacecraft.combat, 0)}}
              </td>
              <td class="p-2">
                {{spacecrafts.filter(spacecraft => spacecraft.count > 0).reduce((acc, spacecraft) => acc +
                  spacecraft.cargo, 0)}}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

    </div>
  </AppLayout>
</template>

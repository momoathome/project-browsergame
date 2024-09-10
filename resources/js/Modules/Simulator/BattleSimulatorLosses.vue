<!-- BattleSimulatorLosses.vue -->
<script lang="ts" setup>
const props = defineProps({
  result: {
    type: Object,
    required: true,
  },
});

const tableLossesProperties = ["name", "count", "losses"];
</script>

<template>
  <div>
    <h2 class="text-2xl mb-4">Outcome: <span class="font-bold ">{{ result.winner }} won</span></h2>

    <div class="flex flex-col gap-4">
      <div>
        <h3 class="text-2xl font-bold">Attacker Losses</h3>
        <table class="w-full bg-base text-light rounded-lg overflow-hidden shadow-xl text-center">
          <tbody>
            <tr  v-for="property in tableLossesProperties"
              :key="property">
              <th class="text-left px-4 py-3 bg-primary-dark font-semibold uppercase text-sm">{{ property }}</th>
              <td v-for="ship in result.attackerLosses" :key="ship.name" class="px-4 py-3 border-t border-primary/50">
                <span>{{ ship[property] }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div>
        <h3 class="text-2xl font-bold">Defender Losses</h3>
        <table class="w-full bg-base text-light rounded-lg overflow-hidden shadow-xl text-center">
          <tbody>
            <tr  v-for="property in tableLossesProperties"
              :key="property">
              <th class="text-left px-4 py-3 bg-primary-dark font-semibold uppercase text-sm">{{ property }}</th>
              <td v-for="ship in result.defenderLosses" :key="ship.name" class="px-4 py-3 border-t border-primary/50">
                <span>{{ ship[property] }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<!-- BattleSimulatorLosses.vue -->
<script lang="ts" setup>
const props = defineProps({
  result: {
    type: Object,
    required: true,
  },
});

const tableLossesProperties = ["name", "quantity", "losses"];
</script>

<template>
  <div>
    <h2 class="mt-0 text-2xl mb-4">Outcome: <span class="font-bold">{{ result.winner }} won</span></h2>

    <div class="animated animated-fade-in animated-slow flex flex-col gap-4">
      <h3 class="text-xl">Attacker Losses</h3>
      <table class="text-center border border-solid border-neutral-500">
        <tbody>
          <tr class="border border-solid border-neutral-500" v-for="property in tableLossesProperties" :key="property">
            <th class="text-start border border-solid border-neutral-500 px-2 py-2">{{ property }}</th>
            <td v-for="ship in result.attackerLosses" :key="ship.name"
              class="whitespace-nowrap border border-solid border-neutral-500 px-4 py-2">
              <span>{{ ship[property] }}</span>
            </td>
          </tr>
        </tbody>
      </table>

      <h3 class="text-xl">Defender Losses</h3>
      <table class="text-center border border-solid border-neutral-500">
        <tbody>
          <tr class="border border-solid border-neutral-500" v-for="property in tableLossesProperties" :key="property">
            <th class="text-start border border-solid border-neutral-500 px-2 py-2">{{ property }}</th>
            <td v-for="ship in result.defenderLosses" :key="ship.name"
              class="whitespace-nowrap border border-solid border-neutral-500 px-4 py-2">
              <span>{{ ship[property] }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

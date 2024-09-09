<script lang="ts" setup>
import { ref } from 'vue';
import BattleCalcTable from '@/Modules/Simulator/BattleCalcTable.vue';
import BattleCalcLossesTable from '@/Modules/Simulator/BattleCalcLossesTable.vue';
import { numberFormat } from '@/Utils/format';
import { useForm } from '@inertiajs/vue3'

const battleData = {
  attacker: [
    { "name": "Merlin", "combatPower": 50, "Quantity": 50 },
    { "name": "Comet", "combatPower": 150, "Quantity": 20 },
    { "name": "Javelin", "combatPower": 400, "Quantity": 0 },
    { "name": "Sentinel", "combatPower": 1_000, "Quantity": 0 },
    { "name": "Probe", "combatPower": 2_500, "Quantity": 0 },
    { "name": "Ares", "combatPower": 7_000, "Quantity": 0 },
    { "name": "Nova", "combatPower": 20_000, "Quantity": 0 },
    { "name": "Horus", "combatPower": 60_000, "Quantity": 0 },
    { "name": "Reaper", "combatPower": 200_000, "Quantity": 0 }
  ],
  defender: [
    { "name": "Merlin", "combatPower": 50, "Quantity": 20 },
    { "name": "Comet", "combatPower": 150, "Quantity": 10 },
    { "name": "Javelin", "combatPower": 400, "Quantity": 0 },
    { "name": "Sentinel", "combatPower": 1_000, "Quantity": 0 },
    { "name": "Probe", "combatPower": 2_500, "Quantity": 0 },
    { "name": "Ares", "combatPower": 7_000, "Quantity": 0 },
    { "name": "Nova", "combatPower": 20_000, "Quantity": 0 },
    { "name": "Horus", "combatPower": 60_000, "Quantity": 0 },
    { "name": "Reaper", "combatPower": 200_000, "Quantity": 0 }
  ],
}

const props = defineProps({
  result: {
    type: Object,
  }
});

interface Ship {
  name: string;
  combatPower: number;
  Quantity: number;
}

type Role = "attacker" | "defender";

const form = useForm({
  attacker: 0,
  defender: 0,
});

const attacker = ref()
const defender = ref()
const lossesTable = ref()

if (battleData) {
  // Berechne die TotalCombatPower für jede Einheit und 
  // füge das gesamte Objekt dem Attacker/Defender Array hinzu
  function addShipTotalCombatPower(role: Role) {
    return battleData?.[role].map((ship: Ship) => {
      const totalCombatPower = calculateShipTotalCombatPower(ship)
      return { ...ship, totalCombatPower };
    });
  }
  attacker.value = addShipTotalCombatPower('attacker');
  defender.value = addShipTotalCombatPower('defender');
}

function updateShipTotalCombatPower(role: Role) {
  let selectedArray = role === "attacker" ? attacker.value : defender.value;
  selectedArray = selectedArray.map((ship: Ship) => {
    const totalCombatPower = calculateShipTotalCombatPower(ship);
    return { ...ship, totalCombatPower };
  });

  if (role === "attacker") {
    attacker.value = selectedArray;
  } else if (role === "defender") {
    defender.value = selectedArray;
  }
}

function calculateShipTotalCombatPower(ship: Ship) {
  const totalCombatPower = ship.combatPower * ship.Quantity;
  return numberFormat(totalCombatPower);
}

const resetCountersOnlossesTable = () => lossesTable.value.resetCounters()

function simulateBattle() {
  form.attacker = attacker.value
  form.defender = defender.value

  form.post('/battle/simulate', {
    onSuccess: () => {
      alert('Battle Successful');
    },
  })
}
</script>

<template>
  <div class="flex flex-col">
    <div class="min-w-full">
      <div class="p-6">
        <h1 class="text-3xl mb-4">Battle Simulator</h1>
        <div class="flex flex-col gap-4">
          <h3 class="text-xl">Attacker</h3>
          <BattleCalcTable role="attacker" :dataObj="attacker"
            @updateShipTotalCombatPower="updateShipTotalCombatPower" />
          <h3 class="text-xl">Defender</h3>
          <BattleCalcTable role="defender" :dataObj="defender"
            @updateShipTotalCombatPower="updateShipTotalCombatPower" />
        </div>

        <button
          class="mt-4 flex px-4 py-2 rounded-lg text-white bg-[#325166] border-[#3E6580] border-solid outline-none transition hover:bg-[#253D4D] disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none"
          @click="simulateBattle(); resetCountersOnlossesTable()">
          Simulate Battle
        </button>
      </div>

      <div>
        <div class="bg-slate-200 max-w-930px p-6 rounded-3 mt-4">
          <BattleCalcLossesTable ref="lossesTable" :dataObj="attacker" />
        </div>
      </div>
    </div>
  </div>
</template>

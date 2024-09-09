<script lang="ts" setup>
import { ref, computed } from 'vue';
import BattleSimulatorTable from '@/Modules/Simulator/BattleSimulatorTable.vue';
import BattleSimulatorLosses from '@/Modules/Simulator/BattleSimulatorLosses.vue';
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
  attacker: [],
  defender: [],
});

// check if props.result array is empty
const isResultEmpty = computed(() => {
  return props.result.length === 0;
});

const attacker = ref(battleData.attacker.map(addTotalCombatPower));
const defender = ref(battleData.defender.map(addTotalCombatPower));

function addTotalCombatPower(ship: Ship) {
  return {
    ...ship,
    totalCombatPower: numberFormat(ship.combatPower * ship.Quantity)
  };
}

function updateShipQuantity(role: Role, index: number, newQuantity: number) {
  const target = role === 'attacker' ? attacker : defender;
  target.value[index].Quantity = newQuantity;
  target.value[index].totalCombatPower = numberFormat(target.value[index].combatPower * newQuantity);
}

function simulateBattle() {
  form.attacker = attacker.value;
  form.defender = defender.value;

  form.post('/battle/simulate', {
    onSuccess: () => {
      //
    },
  });
}
</script>

<template>
  <div class="flex flex-col">
    <div class="min-w-full p-6">
      <h1 class="text-3xl mb-4">Battle Simulator</h1>
      <div class="flex flex-col gap-4">
        <h3 class="text-xl">Attacker</h3>
        <BattleSimulatorTable :role="'attacker'" :dataObj="attacker"
          @update:quantity="(index, newQuantity) => updateShipQuantity('attacker', index, newQuantity)" />
        <h3 class="text-xl">Defender</h3>
        <BattleSimulatorTable :role="'defender'" :dataObj="defender"
          @update:quantity="(index, newQuantity) => updateShipQuantity('defender', index, newQuantity)" />
      </div>

      <button
        class="mt-4 flex px-4 py-2 rounded-lg text-white bg-[#325166] border-[#3E6580] border-solid outline-none transition hover:bg-[#253D4D] disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none"
        @click="simulateBattle">
        Simulate Battle
      </button>
    </div>

    <div v-if="!isResultEmpty" class="bg-slate-200 p-6 rounded-lg mt-4">
      <BattleSimulatorLosses :result="result" />
    </div>
  </div>
</template>

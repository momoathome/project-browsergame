<script lang="ts" setup>
import { ref, computed } from 'vue';
import BattleSimulatorTable from '@/Modules/Simulator/BattleSimulatorTable.vue';
import BattleSimulatorLosses from '@/Modules/Simulator/BattleSimulatorLosses.vue';
import { numberFormat } from '@/Utils/format';
import { useForm } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue';
import type { BattleResult, Spacecraft } from '@/types/types';

type Role = "attacker" | "defender";

const props = defineProps<{
  result: BattleResult
  spacecrafts?: Array<{ details: { name: string; combat: number }, attack: number, defense: number }>
}>()

const form = useForm({
  attacker: [],
  defender: [],
});

function transformSpacecrafts(spacecrafts) {
  return spacecrafts.map(spacecraft => ({
    name: spacecraft.details.name,
    attack: spacecraft.attack,
    defense: spacecraft.defense,
    count: 0,
    totalAttack: 0,
    totalDefense: 0
  }));
}

const attacker = ref(transformSpacecrafts(props.spacecrafts));
const defender = ref(transformSpacecrafts(props.spacecrafts));

function updateTotals(list) {
  list.forEach(ship => {
    ship.totalAttack = ship.attack * ship.count;
    ship.totalDefense = ship.defense * ship.count;
  });
}

function updateShipQuantity(role: Role, index: number, newCount: number) {
  const target = role === 'attacker' ? attacker : defender;
  target.value[index].count = newCount;
  updateTotals(target.value);
}

function simulateBattle() {
  if (!attacker.value.some(ship => ship.count > 0) || !defender.value.some(ship => ship.count > 0)) {
    return;
  }

  form.attacker = attacker.value;
  form.defender = defender.value;

  form.post(route('simulator.simulate'), {
    preserveState: true,
    only: ['result'],
    onSuccess: () => {
      //
    },
  });
}

const isResultEmpty = computed(() => {
  return props.result.length === 0;
});

// Initial totals setzen
updateTotals(attacker.value);
updateTotals(defender.value);
</script>

<template>
    <div class="flex flex-col gap-12 text-light">
      <div>
        <div class="mb-4">
          <h1 class="text-4xl font-black">Battle Simulator</h1>
          <p class="text-light">Simulate epic battles</p>
        </div>
        <div class="flex flex-col gap-4">
          <div>
            <h2 class="text-2xl font-bold">Attacker</h2>
            <BattleSimulatorTable :role="'attacker'" :ships="attacker"
              @update:quantity="(index, newCount) => updateShipQuantity('attacker', index, newCount)" />
          </div>
          <div>
            <h2 class="text-2xl font-bold">Defender</h2>
            <BattleSimulatorTable :role="'defender'" :ships="defender"
              @update:quantity="(index, newCount) => updateShipQuantity('defender', index, newCount)" />
          </div>
        </div>

        <PrimaryButton @click="simulateBattle" class="mt-6">
          Simulate Battle
        </PrimaryButton>
      </div>

      <div v-if="!isResultEmpty" class="rounded-lg">
        <BattleSimulatorLosses :result="result" />
      </div>
    </div>
</template>

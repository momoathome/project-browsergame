<script lang="ts" setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BattleSimulatorTable from '@/Modules/Simulator/BattleSimulatorTable.vue';
import BattleSimulatorLosses from '@/Modules/Simulator/BattleSimulatorLosses.vue';
import { numberFormat } from '@/Utils/format';
import { useForm } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue';
import type { BattleResult } from '@/types/types';

type Role = "attacker" | "defender";

interface SimpleSpacecraft {
  name: string;
  combatPower: number;
  count: number;
  totalCombatPower: string;
}

const props = defineProps<{
  result: BattleResult
  spacecrafts?: Array<{ details: { name: string; combat: number } }>
}>()

const form = useForm({
  attacker: [],
  defender: [],
});

function transformSpacecrafts(spacecrafts) {
  return spacecrafts.map(spacecraft => ({
    name: spacecraft.details.name,
    combatPower: spacecraft.combat,
    count: 0
  }));
}

const attacker = ref(transformSpacecrafts(props.spacecrafts).map(addTotalCombatPower));
const defender = ref(transformSpacecrafts(props.spacecrafts).map(addTotalCombatPower));

function addTotalCombatPower(ship: SimpleSpacecraft) {
  return {
    ...ship,
    totalCombatPower: numberFormat(ship.combatPower * ship.count)
  };
}

function updateShipQuantity(role: Role, index: number, newCount: number) {
  const target = role === 'attacker' ? attacker : defender;
  target.value[index].count = newCount;
  target.value[index].totalCombatPower = numberFormat(target.value[index].combatPower * newCount);
}

function simulateBattle() {
  if (!attacker.value.some(ship => ship.count > 0) || !defender.value.some(ship => ship.count > 0)) {
    return;
  }

  form.attacker = attacker.value;
  form.defender = defender.value;

  form.post('/simulator', {
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
</script>

<template>
  <AppLayout title="simulator">
    <div class="flex flex-col gap-12 p-4 me-20">
      <div class="">
        <h1 class="text-4xl font-black mb-4">Battle Simulator</h1>
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

      <div v-if="!isResultEmpty" class="bg-slate-200 rounded-lg">
        <BattleSimulatorLosses :result="result" />
      </div>
    </div>

  </AppLayout>
</template>

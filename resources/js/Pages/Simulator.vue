<script lang="ts" setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BattleSimulatorTable from '@/Modules/Simulator/BattleSimulatorTable.vue';
import BattleSimulatorLosses from '@/Modules/Simulator/BattleSimulatorLosses.vue';
import { numberFormat } from '@/Utils/format';
import { useForm } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
  result: {
    type: Object,
  },
  spacecrafts: {
    type: Array,
  }
});

interface Ship {
  name: string;
  combatPower: number;
  count: number;
}

type Role = "attacker" | "defender";

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

function addTotalCombatPower(ship: Ship) {
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
    <div class="flex flex-col">
      <div class="p-4 me-20">
        <h1 class="text-3xl mb-4">Battle Simulator</h1>
        <div class="flex flex-col gap-4">
          <h3 class="text-xl">Attacker</h3>
          <BattleSimulatorTable :role="'attacker'" :dataObj="attacker"
            @update:quantity="(index, newCount) => updateShipQuantity('attacker', index, newCount)" />
          <h3 class="text-xl">Defender</h3>
          <BattleSimulatorTable :role="'defender'" :dataObj="defender"
            @update:quantity="(index, newCount) => updateShipQuantity('defender', index, newCount)" />
        </div>

        <PrimaryButton @click="simulateBattle" class="mt-4">
          Simulate Battle
        </PrimaryButton>
      </div>

      <div v-if="!isResultEmpty" class="bg-slate-200 p-6 rounded-lg mt-4">
        <BattleSimulatorLosses :result="result" />
      </div>
    </div>

  </AppLayout>
</template>

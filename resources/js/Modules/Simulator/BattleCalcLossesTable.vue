<script lang="ts" setup>
// import { toast } from 'vue3-toastify';
import { PropType, ref, Ref } from 'vue';

interface Losses {
  name: string;
  Quantity: number;
  Losses: number;
}
interface Ship {
  name: string;
  combatPower: number;
  Quantity: number;
}

interface Counters {
  [key: string]: Ref<number>;
}

defineProps({
  dataObj: {
    type: Array as PropType<Losses[]>,
    required: true,
  },
});
defineExpose({ simulateBattle, resetCounters })

const tableLossesProperties = ["name", "Quantity", "Losses"];

const attackerLossesObj = ref()
const defenderLossesObj = ref()
const isCalculated = ref(false)
const calculationPending = ref(false)
const calculateTimeout = ref(1000) // in ms
const winner = ref('')

function getRandomArbitrary(min: number, max: number) {
  let number = Math.random() * (max - min) + min;
  return parseFloat(number.toFixed(3));
}

function simulateBattle(attacker: Ship[], defender: Ship[]) {
  calculationPending.value = true
  isCalculated.value = false

  const { attackerTotalCombatPower, defenderTotalCombatPower } = calculateTotalCombatPower(attacker, defender);
  winner.value = defineWinner(attackerTotalCombatPower, defenderTotalCombatPower);
  const { attackerLosses, defenderLosses } = calculateLosses(attacker, defender, attackerTotalCombatPower, defenderTotalCombatPower);
  attackerLossesObj.value = attackerLosses
  defenderLossesObj.value = defenderLosses
  console.log(attackerLossesObj.value, defenderLossesObj.value);

  setTimeout(() => {
    calculationPending.value = false
    isCalculated.value = true
    // toast.info(winner.value + ' won');
  }, calculateTimeout.value);
}

function calculateTotalCombatPower(attacker: Ship[], defender: Ship[]) {
  let attackerTotalCombatPower = 0;
  let defenderTotalCombatPower = 0;

  for (let i = 0; i < attacker.length; i++) {
    attackerTotalCombatPower += attacker[i].combatPower * attacker[i].Quantity;
    defenderTotalCombatPower += defender[i].combatPower * defender[i].Quantity;
  }

  return { attackerTotalCombatPower, defenderTotalCombatPower };
}

function defineWinner(attackerTotalCombatPower: number, defenderTotalCombatPower: number) {
  return attackerTotalCombatPower > defenderTotalCombatPower ? 'attacker' : 'defender';
}

function calculateLuckModifier(winnerCombatValue: number, looserCombatValue: number) {
  const ranges = [
    { min: 5, max: 10, minModifier: 0.7, maxModifier: 1.2 },
    { min: 10, max: 20, minModifier: 0.4, maxModifier: 0.8 },
    { min: 20, max: 50, minModifier: 0.2, maxModifier: 0.5 },
    { min: 50, max: Infinity, minModifier: 0.05, maxModifier: 0.15 }
  ];

  let luckModifier = getRandomArbitrary(0.8, 1.4); // Default value for the normal case

  for (const range of ranges) {
    if (winnerCombatValue >= looserCombatValue * range.min && winnerCombatValue < looserCombatValue * range.max) {
      luckModifier = getRandomArbitrary(range.minModifier, range.maxModifier);
      break;
    }
  }

  return luckModifier;
}

function calculateLossRatio(attackerTotalCombatPower: number, defenderTotalCombatPower: number) {
  const [winnerCombatValue, looserCombatValue] = winner.value === 'attacker' ? [attackerTotalCombatPower, defenderTotalCombatPower] : [defenderTotalCombatPower, attackerTotalCombatPower];
  const luckModifier = calculateLuckModifier(winnerCombatValue, looserCombatValue);
  const looserWinnerRatio = parseFloat((looserCombatValue / winnerCombatValue).toFixed(3));
  let lossRatio = parseFloat((looserWinnerRatio * luckModifier).toFixed(3));
  lossRatio = lossRatio > 1 ? 1 : lossRatio < 0 ? 0 : lossRatio;

  return lossRatio;
}

function calculateLosses(attacker: Ship[], defender: Ship[], attackerTotalCombatPower: number, defenderTotalCombatPower: number): { attackerLosses: Losses[], defenderLosses: Losses[] } {
  const attackerLosses: Losses[] = [];
  const defenderLosses: Losses[] = [];

  for (let i = 0; i < attacker.length; i++) {
    const att = attacker[i];
    const def = defender[i];
    // console.log(att, def);
    let lossRatio: number;

    if (att.Quantity === 0 || def.Quantity === 0) {
      lossRatio = 0;
    } else {
      lossRatio = calculateLossRatio(attackerTotalCombatPower, defenderTotalCombatPower);
    }

    const lossQuantityAtt = att.Quantity === 0 ? 0 : Math.round(att.Quantity * lossRatio);
    const lossQuantityDef = def.Quantity === 0 ? 0 : Math.round(def.Quantity * lossRatio);

    attackerLosses.push({ name: att.name, Quantity: att.Quantity, Losses: winner.value === 'attacker' ? lossQuantityAtt : att.Quantity });
    defenderLosses.push({ name: def.name, Quantity: def.Quantity, Losses: winner.value === 'attacker' ? def.Quantity : lossQuantityDef });
  }
  return { attackerLosses, defenderLosses };
}

let attackerCounters: Counters = {};
let defenderCounters: Counters = {};

function resetCounters() {
  resetCountersForShip(attackerCounters);
  resetCountersForShip(defenderCounters);
}

function resetCountersForShip(counters: Counters) {
  for (const shipName in counters) {
    counters[shipName].value = 0;
  }
}

function lossesCounterAnimation(ship: Losses, counters: Counters) {
  // Erstelle einen Counter für das Schiff, falls noch nicht vorhanden
  if (!counters[ship.name]) {
    counters[ship.name] = ref(0);
  }

  const counter = counters[ship.name];
  const interval = 500; // Intervall in Millisekunden, höher = längere Animation
  const increment = 1; // Zählschritte

  const timer = setInterval(() => {
    if (counter.value < ship.Losses) {
      counter.value += increment;
    } else {
      clearInterval(timer); // Stoppt den Timer
    }
  }, interval);

  return counter;
}

</script>

<template>
  <h2 class="mt-0 text-2xl mb-4">Outcome: <span class="font-bold" v-if="isCalculated">{{ winner }} won</span></h2>

  <div class="animated animated-fade-in animated-slow flex flex-col gap-4" v-if="isCalculated">
    <h3 class="text-xl">Attacker</h3>
    <table class="text-center border border-solid border-neutral-500">
      <tbody>
        <tr class="border border-solid border-neutral-500" v-for="property in tableLossesProperties"
          :key="property">
          <th class="text-start border border-solid border-neutral-500 px-2 py-2">{{ property }}</th>
          <template v-for="ship in attackerLossesObj">
            <td class="whitespace-nowrap border border-solid border-neutral-500 px-4 py-2">
              <span v-if="property === 'Losses'">{{ lossesCounterAnimation(ship, attackerCounters) }}</span>
              <span v-else>{{ ship[property] }}</span>
            </td>
          </template>
        </tr>
      </tbody>
    </table>
    <h3 class="text-xl">Defender</h3>
    <table class="text-center border border-solid border-neutral-500">
      <tbody>
        <tr class="border border-solid border-neutral-500" v-for="property in tableLossesProperties"
          :key="property">
          <th class="text-start border border-solid border-neutral-500 px-2 py-2">{{ property }}</th>
          <template v-for="ship in defenderLossesObj">
            <td class="whitespace-nowrap border border-solid border-neutral-500 px-4 py-2">
              <span v-if="property === 'Losses'">{{ lossesCounterAnimation(ship, defenderCounters) }}</span>
              <span v-else>{{ ship[property] }}</span>
            </td>
          </template>
        </tr>
      </tbody>
    </table>
  </div>
  <div v-if="calculationPending">
    <div i-tabler-loader class="size-6 animate-spin" />

    <span class="ms-2 align-middle">Calculating...</span>
  </div>
</template>

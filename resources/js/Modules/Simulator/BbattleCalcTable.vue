<script lang="ts" setup>
import { PropType, ref } from 'vue';
import { numberFormat } from '@/Utils/NumberFormat';

type Role = "attacker" | "defender";

interface RoleProps {
  name: string;
  combatPower: number;
  Quantity: number;
  totalCombatPower: number;
}
const props = defineProps({
  role: {
    type: String as PropType<Role>,
    required: true,
  },
  dataObj: {
    type: Array as PropType<RoleProps[]>,
    required: true,
  },
});
const emit = defineEmits(['updateShipTotalCombatPower'])

const tableProperties = ref()
tableProperties.value = generateTableProperties();

function generateTableProperties() {
  // Nehme das erste Element aus dem attacker-Objekt als Beispiel
  const exampleUnit = props.dataObj?.[0];
  if (!exampleUnit) {
    return [];
  }

  const properties = Object.keys(exampleUnit);
  return properties;
}
</script>

<template>
  <table class="text-center border border-solid border-neutral-500">
    <tbody>
      <tr class="border border-solid border-neutral-500" v-for="property in tableProperties" :key="property">
        <th scope="col" class="text-start border border-solid border-neutral-500 px-2 py-2">
          {{ property }}
        </th>
        <template v-for="(ship, shipIndex) in dataObj" :key="shipIndex">
          <td class="whitespace-nowrap border border-solid border-neutral-500 px-4 py-2"
            v-if="property !== 'totalCombatPower'">
            <span v-if="property === 'name'">{{ ship['name'] }}</span>
            <span v-if="property === 'combatPower'">{{ numberFormat(ship['combatPower']) }}</span>
            <span v-if="property === 'Quantity'">
              <input class="inline-flex h-full w-16 items-center justify-center px-1" name="battleCalcInput" v-model="ship['Quantity']"
                type="text" min="0" inputmode="numeric" pattern="[0-9]*" maxlength="4"
                onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                @input="$emit('updateShipTotalCombatPower', role)">
            </span>
          </td>
          <td class="whitespace-nowrap border border-solid border-neutral-500 px-4 py-2" v-else>
            {{ ship[property] }}
          </td>
        </template>
      </tr>
    </tbody>
  </table>
</template>

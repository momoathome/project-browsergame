<script lang="ts" setup>
import { type PropType, computed } from 'vue';
import { numberFormat } from '@/Utils/format';

type Role = "attacker" | "defender";

interface Ship {
  name: string;
  combatPower: number;
  Quantity: number;
  totalCombatPower: string;
}

const props = defineProps({
  role: {
    type: String as PropType<Role>,
    required: true,
  },
  dataObj: {
    type: Array as PropType<Ship[]>,
    required: true,
  },
});

const emit = defineEmits(['update:quantity'])

const tableProperties = computed(() => {
  const exampleUnit = props.dataObj?.[0];
  if (!exampleUnit) {
    return [];
  }
  return Object.keys(exampleUnit);
});

function updateQuantity(index: number, event: Event) {
  const target = event.target as HTMLInputElement;
  const newQuantity = parseInt(target.value) || 0;
  emit('update:quantity', index, newQuantity);
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
          <td class="whitespace-nowrap border border-solid border-neutral-500 px-4 py-2">
            <span v-if="property === 'name'">{{ ship.name }}</span>
            <span v-else-if="property === 'combatPower'">{{ numberFormat(ship.combatPower) }}</span>
            <input v-else-if="property === 'Quantity'"
              class="inline-flex h-full w-16 items-center justify-center px-1"
              type="text"
              v-model="ship.Quantity"
              min="0"
              inputmode="numeric"
              pattern="[0-9]*"
              maxlength="4"
              onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
              @input="updateQuantity(shipIndex, $event)"
            >
            <span v-else>{{ ship[property] }}</span>
          </td>
        </template>
      </tr>
    </tbody>
  </table>
</template>

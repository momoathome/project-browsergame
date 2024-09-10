<script lang="ts" setup>
import { type PropType, computed } from 'vue';
import { numberFormat } from '@/Utils/format';
import AppInput from '@/Components/AppInput.vue';

type Role = "attacker" | "defender";

interface Ship {
  name: string;
  combatPower: number;
  count: number;
  totalCombatPower: string;
}

const props = defineProps({
  role: {
    type: String as PropType<Role>,
    required: true,
  },
  ships: {
    type: Array as PropType<Ship[]>,
    required: true,
  },
});

const emit = defineEmits(['update:quantity'])

const tableProperties = computed(() => {
  const exampleUnit = props.ships?.[0];
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
  <table class="w-full bg-base text-light rounded-lg overflow-hidden shadow-xl text-center">
    <tbody>
      <tr v-for="property in tableProperties" :key="property">
        <th scope="col" class="text-left px-4 py-3 bg-primary-dark font-semibold uppercase text-sm">
          {{ property }}
        </th>
        <template v-for="(ship, shipIndex) in ships" :key="shipIndex">
          <td class="px-4 py-3 border-t border-primary/50">
            <span v-if="property === 'name'" class="font-medium">{{ ship.name }}</span>
            <span v-else-if="property === 'combatPower'" class="">{{ numberFormat(ship.combatPower) }}</span>
            <AppInput v-else-if="property === 'count'"
              class="h-10"
              v-model="ship.count"
              :maxlength="4"
              @input="updateQuantity(shipIndex, $event)"
            />
            <span v-else class="">{{ ship[property] }}</span>
          </td>
        </template>
      </tr>
    </tbody>
  </table>
</template>

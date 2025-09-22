<script lang="ts" setup>
import { computed } from 'vue';
import { numberFormat } from '@/Utils/format';
import AppInput from '@/Modules/Shared/AppInput.vue';

type Role = "attacker" | "defender";

interface SimpleSpacecraft {
  name: string;
  attack: number;
  defense: number;
  count: number;
  totalAttack: number;
  totalDefense: number;
}

const props = defineProps<{
  role: Role
  ships: SimpleSpacecraft[]
}>()

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
            <span v-else-if="property === 'attack'" class="">{{ numberFormat(ship.attack) }}</span>
            <span v-else-if="property === 'defense'" class="">{{ numberFormat(ship.defense) }}</span>
            <AppInput v-else-if="property === 'count'"
              class="h-10 !bg-primary/20"
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

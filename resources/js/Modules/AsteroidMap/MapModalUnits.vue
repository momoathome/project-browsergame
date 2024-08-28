<script lang="ts" setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { numberFormat } from '@/Utils/format';
import type { Spacecraft } from '@/types/types';
import AppInput from '@/Components/AppInput.vue';

const props = defineProps<{
  spacecrafts: Spacecraft[];
}>();

/* totalCombat power of user's spacecrafts */
const totalCombat = computed(() => {
  let combat = 0;

})

/* totalCargo capacity of user's spacecrafts */
const totalCargo = computed(() => {
  let cargoCapacity = 0;
})

const form = useForm({
  Merlin: 0,
  Comet: 0,
  Javelin: 0,
  Sentinel: 0,
  Probe: 0,
  Ares: 0,
  Nova: 0,
  Horus: 0,
  Reaper: 0,
  Mole: 0,
  Titan: 0,
  Nomad: 0,
  Hercules: 0,
})

const emit =defineEmits(['emitForm'])

const onSubmit = () => {
  emit('emitForm', form.data())
}
const onExplore = () => {
  form.reset()
}

defineExpose({ onSubmit, onExplore })
</script>

<template>
  <div class="flex items-center text-base">
    <div class="py-8 px-12 flex flex-col gap-6">
      <form class="flex flex-col gap-6" name="farmAsteroid">
        <div class="grid grid-cols-3 gap-x-4 gap-y-2">
          <div class="flex items-center relative group" v-for="spacecraft in spacecrafts" :key="spacecraft.details.name">
            <img :src="spacecraft.details.image" class="h-8" @click="form[spacecraft.details.name] = spacecraft.count" />
            <AppInput v-model.number="form[spacecraft.details.name]" :maxInputValue="spacecraft.count"
              :name="spacecraft.details.name" class="text-lg mx-1" />
            <span
              class="pointer-events-none absolute -top-6 left-4 text-center bg-base text-white text-sm py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              {{ spacecraft.details.name }}
            </span>
          </div>
        </div>
<!--         <div class="grid grid-cols-2">
          <div class="flex flex-col items-center">
            <span class="text-sm text-secondary">Combat</span>
            <p class="font-medium text-lg text-white">{{ numberFormat(totalCombat) }}</p>
          </div>
          <div class="flex flex-col items-center">
            <span class="text-sm text-secondary">Cargo</span>
            <p class="font-medium text-lg text-white">{{ numberFormat(totalCargo) }}</p>
          </div>
        </div> -->
      </form>
    </div>
  </div>
</template>

<script lang="ts" setup>

import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BuildingsCard from '@/Modules/Buildings/BuildingsCard.vue';
import type { Building } from '@/types/types';

const props = defineProps(['buildings']);

const formattedBuildings = computed(() => {
  return props.buildings.map((building: Building) => {
    return {
      id: building.id,
      image: building.details.image,
      name: building.details.name,
      description: building.details.description,
      level: building.level,
      buildTime: building.buildTime,
      resources: building.resources.map((resource) => ({
        name: resource.name,
        image: resource.image,
        amount: resource.pivot.amount
      }))
    };
  });
});

function updateModule(moduleKey: string) {
/*   const module = userStore.user?.modules[moduleKey]

  const hasEnoughCredits = () => {
    const cost = module!.cost
    if (!cost || (userStore.user?.ressources.credits || 0) < cost) {
      return false
    }
    return true
  }

  if (!hasEnoughCredits()) {
    return toast.error('You do not have enough credits')
  }

  function updateModuleLevel() {
    if (module) {
      module.level++;
      module.cost = Math.round(module.cost * 1.35);

      if (module.title === 'Hangar') {
        const newUnitLimit = Math.round(module.effectValue * 1.323);

        const ressources = {
          unitLimit: newUnitLimit
        };

        userStore.updateUserRessources(ressources);
        module.effectValue = newUnitLimit;
      }

      userStore.updateUserModule(moduleKey, module);
    }
  }


  function updateUserRessources() {
    if (module) {
      const cost = module.cost || 0
      const userCredits = userStore.user?.ressources.credits || 0
      const ressources = {
        credits: userCredits - cost,
      }
      userStore.updateUserRessources(ressources)
    }
  }

  updateUserRessources()
  updateModuleLevel() */
} 
</script>

<template>
  <AppLayout title="buildings">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Buildings
      </h2>
    </template>

    <div class="grid gap-4 lg:gap-8 p-16">
      <BuildingsCard v-for="data in formattedBuildings" :key="data.id" :moduleData="data" @upgrade="updateModule" />
    </div>
  </AppLayout>
</template>

<style scoped>
.grid {
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}
</style>

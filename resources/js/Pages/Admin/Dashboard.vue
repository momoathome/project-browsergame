<script lang="ts" setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import DashboardUserOverview from '@/Modules/Admin/DashboardUserOverview.vue';
import DashboardQueue from '@/Modules/Admin/DashboardQueue.vue';
import DashboardMarket from '@/Modules/Admin/DashboardMarket.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { Resource, Market } from '@/types/types';
import type { User } from '@/types/types';

const props = defineProps<{
  universeResources?: Resource[];
  market: Market[];
  users: User[];
}>()

const asteroidCount = useForm({
  count: 7000
});

function regenerateAsteroids() {
  if (asteroidCount.count <= 0) {
    return
  }

  asteroidCount.post(route('admin.asteroids.regenerate', asteroidCount.count), {
    preserveState: true,
    preserveScroll: true,

    onSuccess: () => {
      asteroidCount.reset();
    },
    onError: () => {
      //
    },
  });
}
</script>

<template>
    <div>
      <h1 class="text-3xl mb-4 font-bold text-light">
        Dashboard
      </h1>

<!--       <div class="flex gap-4 w-full">
        <div class="w-1/3 mb-4">
          <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
            <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl text-light">
              Asteroid Management
            </h2>
            <div class="p-4">
              <form @submit.prevent="regenerateAsteroids">
                <div class="mb-4">
                  <label for="count" class="block text-light font-medium mb-2">Anzahl der Asteroiden:</label>
                  <input type="number" id="count" name="count" min="1" max="20000" v-model="asteroidCount.count"
                    class="w-full p-2 border border-gray-300 rounded-md bg-base text-light" required>
                </div>
                <SecondaryButton type="submit">
                  Asteroiden neu generieren
                </SecondaryButton>
              </form>
            </div>
          </div>
        </div>
        <div class="w-1/3 mb-4">
          <Link as="div" :href="route('admin.progression')" class="bg-base rounded-xl w-full cursor-pointer border-primary border-4 border-solid">
            <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl text-light">
              Building Management
            </h2>
          </Link>
        </div>
      </div> -->

      <div class="grid grid-cols-2 gap-4">
        <div class="flex flex-col gap-4">
          <div class="bg-base rounded-xl w-full h-max border border-primary/40 shadow-xl">
            <h2 class="text-xl font-semibold p-4 border-b border-primary bg-base-dark rounded-t-xl text-light">
              Actions
            </h2>
            <Link :href="route('admin.resourceDistribution')" class="text-light block py-4 px-3 hover:underline">Ressourcenverteilung</Link>
          </div>
          <DashboardUserOverview :users="users" />
        </div>
        <DashboardMarket :market="market" />
      </div>
      <!-- <DashboardQueue :action-queue="gameQueue" /> -->
    </div>
</template>

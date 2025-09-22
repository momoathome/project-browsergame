<script lang="ts" setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Building, Spacecraft, RawQueueItem } from '@/types/types';
import AppTooltip from '@/Modules/Shared/AppTooltip.vue';
import { timeFormat, numberFormat } from '@/Utils/format';
import { useQueueStore } from '@/Composables/useQueueStore';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';
import { useBuildingStore } from '@/Composables/useBuildingStore';

const { queueData } = useQueueStore();
const { spacecrafts } = useSpacecraftStore();
const { buildings } = useBuildingStore();
const page = usePage()

const props = defineProps<{
  buildings: Building[],
  spacecrafts: Spacecraft[]
}>()

const unlockedSpacecrafts = computed(() => (spacecrafts.value ?? []).filter(spacecraft => spacecraft.unlocked));

const getBuildingEffectValue = (building) => {
  if (building.effect?.current) {
    const keys = Object.keys(building.effect.current);
    if (keys.length === 0) return '';
    const key = keys[0];
    const value = building.effect.current[key];
    return value;
  }
  return '';
};

const getBuildingEffectText = (building) => {
  if (building.effect?.current) {
    const keys = Object.keys(building.effect.current);
    if (keys.length === 0) return '';
    const key = keys[0];
    // Ersetze "_" durch Leerzeichen und mache den ersten Buchstaben groß
    return key.replace(/_/g, ' ').replace(/^\w/, c => c.toUpperCase());
  }
  return '';
};


onMounted(() => {
  if (!queueData.value) {
    console.log('No queue data, fetching...');
    // fetch queue data
  } else {
    console.log(queueData.value);
  }
});

</script>

<template>
    <div class="flex gap-6 h-full">

      <div class="flex flex-col gap-8 w-full">
        <!-- main -->
         <div class="grid gap-6">
          <div v-for="building in buildings" :key="building.id" class="flex flex-col rounded-xl bg-base content_card text-light">
            <div class="flex justify-between items-center">
              <div class="flex justify-center px-2 py-2">
                <p class="font-semibold text-lg">{{ building.name }}</p>
              </div>
              <div class="flex items-center h-full px-2 rounded-tr-xl bg-primary/25">
                <span class="text-sm font-medium mt-1 me-1 text-secondary">lv.</span>
                <p class="text-lg">{{ building.level }}</p>
              </div>
            </div>

            <div class="image relative">
              <img :src="building.image" class="object-cover aspect-[2/1] h-36" alt="" />
            </div>

            <div class="flex flex-col h-full">
              <div class="flex flex-col gap-1 px-3 py-3 h-full bg-primary/25">
                <div class="flex items-center gap-1">
                  <span class="text-sm text-secondary">{{ getBuildingEffectText(building) }}:</span>
                  <span class="font-medium text-sm">{{ getBuildingEffectValue(building) }}</span>
                </div>
              </div>

              <!-- <div class="flex border-t border-primary/50">
                <AppCardTimer :buildTime="building.build_time" :endTime="upgradeEndTime" :isInProgress="isUpgrading"
                  @upgrade-complete="handleUpgradeComplete" @cancel-upgrade="showCancelModal = true"
                  :description="`Up to lv. ${building.level + 1}`" />
              </div> -->
            </div>
          </div>
         </div>

         <div class="grid gap-6">
           <div v-for="spacecraft in unlockedSpacecrafts" :key="spacecraft.id" class="flex flex-col w-full rounded-xl bg-base content_card text-light">
            <div class="flex justify-between items-center ">
              <div class="flex items-center gap-2 px-2 py-2">
                <div class="relative group">
                  <img :src="`/images/spacecraftTypes/${spacecraft.type}.png`" class="h-5" alt="combat" />
                  <AppTooltip :label="spacecraft.type" position="bottom" class="!mt-1" />
                </div>
                <p class="font-semibold text-lg">{{ spacecraft.name }}</p>

                <!-- <p class="text-[12px] text-gray">{{ spacecraft.type }}</p> -->
                <!-- <p class="text-gray text-sm mt-1">{{ spacecraft.description }}</p> -->
              </div>
              <div class="flex items-center h-full px-4 rounded-tr-xl bg-primary/25">
                <p class="text-lg">{{ spacecraft.count }}</p>
              </div>
            </div>

            <div class="image relative">
              <img :src="spacecraft.image" class="h-24 w-full" alt="spacecraft" />
            </div>

            <div class="flex flex-col h-full">
              <div class="flex flex-col h-full">
                <div class="flex justify-between gap-4 px-4 py-4 bg-primary/25">
                  <div class="flex flex-col gap-2">
                    <div class="flex relative group items-center gap-1">
                      <img src="/images/combat.png" class="h-5" alt="combat" />
                      <p class="font-medium text-sm">{{ numberFormat(spacecraft.combat) }}</p>
                      <AppTooltip :label="'combat'" position="bottom" class="!mt-1" />
                    </div>
                    <div class="flex relative group items-center gap-1">
                      <img src="/images/cargo.png" class="h-5" alt="cargo" />
                      <p class="font-medium text-sm">{{ numberFormat(spacecraft.cargo) }}</p>
                      <AppTooltip :label="'cargo capacity'" position="bottom" class="!mt-1" />
                    </div>
                  </div>

                  <div class="flex flex-col gap-2">
                    <div class="flex relative group items-center gap-1">
                      <img src="/images/speed.png" class="h-5" alt="speed" />
                      <p class="font-medium text-sm">{{ spacecraft.speed }}</p>
                      <AppTooltip :label="'speed'" position="bottom" class="!mt-1" />
                    </div>
                    <div class="flex relative group items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg"
                      alt="unit limit"
                      width="20" height="20" viewBox="0 0 24 24">
                        <g fill="none">
                          <path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/>
                          <path fill="currentColor" d="M12 13c2.396 0 4.575.694 6.178 1.672c.8.488 1.484 1.064 1.978 1.69c.486.615.844 1.351.844 2.138c0 .845-.411 1.511-1.003 1.986c-.56.45-1.299.748-2.084.956c-1.578.417-3.684.558-5.913.558s-4.335-.14-5.913-.558c-.785-.208-1.524-.506-2.084-.956C3.41 20.01 3 19.345 3 18.5c0-.787.358-1.523.844-2.139c.494-.625 1.177-1.2 1.978-1.69C7.425 13.695 9.605 13 12 13m0-11a5 5 0 1 1 0 10a5 5 0 0 1 0-10"/>
                        </g>
                      </svg>
                      <p class="font-medium text-sm">
                        {{ spacecraft.crew_limit }}
                      </p>
                      <AppTooltip :label="'crew limit'" position="bottom" class="!mt-1" />
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex">

                <!--             <AppCardTimer
                :buildTime="actualBuildTime"
                @upgrade-complete="handleProduceComplete"
                :isInProgress="isProducing"
                :endTime="productionEndTime"
                :description="`${activeProduction} Units`"
                @cancel-upgrade="showCancelModal = true"
                :disabled="!canProduce && !isProducing"
              /> -->
              </div>
            </div>
          </div>
         </div>
      </div>
      
    </div>
</template>

<style scoped>
.content_card {
  --shadow-color: 210deg 30% 15%;
  --glow-color: 210deg 70% 50%;

  box-shadow: 1px 1px 1.6px hsl(var(--shadow-color) / 0.3),
    3.5px 3.5px 5.6px -0.8px hsl(var(--shadow-color) / 0.3),
    8.8px 8.8px 14px -1.7px hsl(var(--shadow-color) / 0.35),
    0 0 12px -2px hsl(var(--glow-color) / 0.15);
  border: 1px solid hsl(210deg 30% 25% / 0.5);
}

.image::after {
  content: '';
  position: absolute;
  inset: 0;
  box-shadow: inset 0px -10px 15px 4px #1E2D3B,
    inset 0px -12px 20px 0px #1E2D3B
}

.grid {
  --grid-min-col-size: 180px;

  grid-template-columns: repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr));
}
</style>

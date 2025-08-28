import { usePage } from '@inertiajs/vue3';
import { timeFormat } from '@/Utils/format';
import type { Asteroid, SpacecraftSimple } from '@/types/types';
import { QueueActionType } from '@/types/actionTypes';
import type { Ref, ComputedRef } from 'vue';
import { ref, computed } from 'vue';

export function useAsteroidMining(
  asteroid: Ref<Asteroid> | ComputedRef<Asteroid>,
  spacecraftsForm: any,
  spacecrafts: SpacecraftSimple[],
  actionType: Ref<QueueActionType> | ComputedRef<QueueActionType> = ref(QueueActionType.MINING)
) {
  const applyDiminishingReturns = (speed: number) => {
    const baseValue = Math.min(1, speed);
    let remainingValue = 0;
    if (speed > 1) {
      remainingValue = 0.85 * (Math.log10(speed) + 1);
    }
    return Math.max(1, baseValue + remainingValue);
  };

  const miningDuration = computed(() => {
    if (!asteroid.value) return '00:00';

    const anySpacecraftSelected = Object.values(spacecraftsForm).some(value => value > 0);
    if (!anySpacecraftSelected) return '00:00';

    // Niedrigste Geschwindigkeit finden
    let lowestSpeed = 0;
    for (const spacecraftName in spacecraftsForm) {
      const count = spacecraftsForm[spacecraftName];
      if (count > 0) {
        const spacecraft = spacecrafts.find(s => s.name === spacecraftName);
        if (spacecraft && spacecraft.speed > 0 && (lowestSpeed === 0 || spacecraft.speed < lowestSpeed)) {
          lowestSpeed = spacecraft.speed;
        }
      }
    }

    const userStation = usePage().props.stations.find(station =>
      station.user_id === usePage().props.auth.user.id
    );

    // Distanz berechnen
    const distance = Math.sqrt(
      Math.pow(userStation.x - asteroid.value.x, 2) +
      Math.pow(userStation.y - asteroid.value.y, 2)
    );

    // Grundlegende Reisedauer berechnen
    const travelFactor = 1;
    const spacecraftFlightSpeed = 1;

    const baseDuration = Math.max(10, Math.round(distance / (lowestSpeed > 0 ? lowestSpeed : 1)));
    let calculatedDuration = Math.max(
      baseDuration,
      Math.floor(distance / (lowestSpeed > 0 ? lowestSpeed : 1) * travelFactor)
    );
    calculatedDuration = Math.floor(calculatedDuration / spacecraftFlightSpeed);

    // Prüfen, ob mindestens ein Miner ausgewählt ist
    let totalMiningSpeed = 0;
    let hasMiner = false;

    for (const spacecraftName in spacecraftsForm) {
      const count = spacecraftsForm[spacecraftName];
      if (count > 0) {
        const spacecraft = spacecrafts.find(s => s.name === spacecraftName);
        if (spacecraft && spacecraft.type === 'Miner') {
          const opSpeed = spacecraft.operation_speed || 1;
          totalMiningSpeed += count * opSpeed;
          hasMiner = true;
        }
      }
    }

      console.log('actionType:', actionType.value, 'totalMiningSpeed:', totalMiningSpeed, hasMiner);

    if (
      actionType.value === QueueActionType.MINING &&
      hasMiner &&
      totalMiningSpeed > 0
    ) {
      const effectiveOperationSpeed = applyDiminishingReturns(totalMiningSpeed);
      calculatedDuration = Math.max(10, Math.floor(calculatedDuration / (effectiveOperationSpeed / 5)));
    }

    return timeFormat(calculatedDuration);
  });

  return {
    calculateMiningDuration: () => {
      if (!asteroid.value) return '00:00';
      return miningDuration.value;
    },
    miningDuration
  };
}


import { usePage } from '@inertiajs/vue3';
import { timeFormat } from '@/Utils/format';
import type { Asteroid } from '@/types/types';
import { QueueActionType } from '@/types/actionTypes';
import type { Ref, ComputedRef } from 'vue';
import { ref, computed } from 'vue';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';

export function useAsteroidMining(
  asteroid: Ref<Asteroid> | ComputedRef<Asteroid>,
  spacecraftsForm: any,
  actionType: Ref<QueueActionType> | ComputedRef<QueueActionType> = ref(QueueActionType.MINING)
) {
  const { spacecrafts } = useSpacecraftStore();

  // Backend-Formel: Diminishing Returns
  function applyDiminishingReturns(spacecraftsForm: any, spacecraftsArr: any[]) {
    const miners: number[] = [];
    for (const name in spacecraftsForm) {
      const count = spacecraftsForm[name];
      if (count > 0) {
        const sc = spacecraftsArr.find(s => s.name === name && s.type === 'Miner');
        if (sc) {
          for (let i = 0; i < count; i++) {
            miners.push(sc.operation_speed || 1);
          }
        }
      }
    }
    miners.sort((a, b) => b - a);
    let effectiveSpeed = 0;
    for (let i = 0; i < miners.length; i++) {
      effectiveSpeed += miners[i] * Math.pow(0.85, i);
    }
    return Math.max(1, Math.round(effectiveSpeed * 100) / 100);
  }

  // Backend-Formel: OperationValue je nach Asteroidgröße
  function getOperationValueByAsteroid(size: string): number {
    switch (size) {
      case 'small': return 300;
      case 'medium': return 600;
      case 'large': return 1200;
      case 'extreme': return 2400;
      default: return 300;
    }
  }

  const miningDuration = computed(() => {
    if (!asteroid.value) return '00:00';

    const anySpacecraftSelected = Object.values(spacecraftsForm).some((value: any) => value > 0);
    if (!anySpacecraftSelected) return '00:00';

    // Niedrigste Geschwindigkeit finden
    let lowestSpeed = 0;
    for (const spacecraftName in spacecraftsForm) {
      const count = spacecraftsForm[spacecraftName];
      if (count > 0) {
        const spacecraft = spacecrafts.value.find(s => s.name === spacecraftName);
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

    // Backend-Formel: travelFactor und spacecraftFlightSpeed ggf. aus Config holen
    const travelFactor = 1; // ggf. dynamisch machen
    const spacecraftFlightSpeed = 1; // ggf. dynamisch machen

    const baseDuration = Math.max(60, Math.round(distance / (lowestSpeed > 0 ? lowestSpeed : 1) * travelFactor));
    let calculatedDuration = baseDuration / spacecraftFlightSpeed;

    // Prüfen, ob mindestens ein Miner ausgewählt ist
    let hasMiner = false;
    for (const spacecraftName in spacecraftsForm) {
      const count = spacecraftsForm[spacecraftName];
      if (count > 0) {
        const spacecraft = spacecrafts.value.find(s => s.name === spacecraftName);
        if (spacecraft && spacecraft.type === 'Miner') {
          hasMiner = true;
          break;
        }
      }
    }

    if (actionType.value === QueueActionType.MINING && hasMiner) {
      const effectiveOperationSpeed = applyDiminishingReturns(spacecraftsForm, spacecrafts.value);
      const operationValue = getOperationValueByAsteroid(asteroid.value.size);
      const miningTime = Math.round((operationValue / Math.max(1, effectiveOperationSpeed)) * 60);
      calculatedDuration += Math.max(10, miningTime);
    }

    return timeFormat(Math.round(calculatedDuration));
  });

  return {
    calculateMiningDuration: () => {
      if (!asteroid.value) return '00:00';
      return miningDuration.value;
    },
    miningDuration
  };
}


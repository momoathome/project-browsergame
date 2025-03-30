import { usePage } from '@inertiajs/vue3';
import { timeFormat } from '@/Utils/format';
import type { Asteroid, Spacecraft } from '@/types/types';
import type { Ref, ComputedRef } from 'vue';
import { computed, watch, toRef, toRefs } from 'vue';

export function useAsteroidMining(
  asteroid: Ref<Asteroid> | ComputedRef<Asteroid>,
  spacecraftsForm: any,
  spacecrafts: Spacecraft[]
) {
  // Diminishing returns Funktion - identisch mit Backend
  const applyDiminishingReturns = (speed) => {
    // Basis-Geschwindigkeit (erster Miner hat vollen Effekt)
    const baseValue = Math.min(1, speed);

    // Restliche Geschwindigkeit mit abnehmendem Rückgabewert
    let remainingValue = 0;
    if (speed > 1) {
      // Logarithmische Funktion für abnehmende Rückgabewerte
      remainingValue = 0.85 * (Math.log10(speed) + 1);
    }

    // Kombiniere Basis- und abnehmenden Wert, aber nie unter 1
    return Math.max(1, baseValue + remainingValue);
  };

  // Berechne Mining-Dauer als computed property mit direkter Beobachtung der Form
  const miningDuration = computed(() => {
    if (!asteroid.value) return '00:00';

    // Überprüfen, ob irgendwelche Raumschiffe ausgewählt sind
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
    const baseDuration = Math.max(10, Math.round(distance / (lowestSpeed > 0 ? lowestSpeed : 1)));
    let calculatedDuration = Math.max(
      baseDuration,
      Math.floor(distance / (lowestSpeed > 0 ? lowestSpeed : 1) * travelFactor)
    );

    // Aktionsspezifische Zeitberechnung für Mining
    if (asteroid.value) {
      // Gesamte Mining-Geschwindigkeit berechnen
      let totalMiningSpeed = 0;

      for (const spacecraftName in spacecraftsForm) {
        const count = spacecraftsForm[spacecraftName];
        if (count > 0) {
          const spacecraft = spacecrafts.find(s => s.name === spacecraftName);

          if (spacecraft && spacecraft.type === 'Miner') {
            const opSpeed = spacecraft.operation_speed || 1;
            totalMiningSpeed += count * opSpeed;
          }
        }
      }

      // Diminishing returns auf die Operationsgeschwindigkeit anwenden
      const effectiveOperationSpeed = applyDiminishingReturns(totalMiningSpeed);

      // Dauer basierend auf der effektiven Operationsgeschwindigkeit berechnen
      if (totalMiningSpeed > 0) {
        calculatedDuration = Math.max(10, Math.floor(calculatedDuration / (effectiveOperationSpeed / 5)));
      }
    }

    return timeFormat(calculatedDuration);
  });

  return {
    calculateMiningDuration: () => {
      // Berechnung bei jedem Aufruf erneut durchführen
      if (!asteroid.value) return '00:00';

      return miningDuration.value;
    },
    miningDuration
  };
}

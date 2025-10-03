import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { timeFormat } from '@/Utils/format';
import type { Station, Rebel } from '@/types/types';
import type { Ref, ComputedRef } from 'vue';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';

export function useCombatDuration(
  target: Ref<Station | Rebel>,
  spacecraftsForm: any
) {
  const { spacecrafts } = useSpacecraftStore();

  const combatDuration = computed(() => {
    if (!target.value) return '00:00';

    const anySpacecraftSelected = Object.values(spacecraftsForm).some((value: any) => value > 0);
    if (!anySpacecraftSelected) return '00:00';

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

    const distance = Math.sqrt(
      Math.pow(userStation.x - target.value.x, 2) +
      Math.pow(userStation.y - target.value.y, 2)
    );

    const travelFactor = 1;
    const spacecraftFlightSpeed = 1;

    const baseDuration = Math.max(60, Math.round(distance / (lowestSpeed > 0 ? lowestSpeed : 1)));
    let calculatedDuration = Math.max(
      baseDuration,
      Math.floor(distance / (lowestSpeed > 0 ? lowestSpeed : 1) * travelFactor)
    );

    calculatedDuration = Math.floor(calculatedDuration / spacecraftFlightSpeed);

    return timeFormat(calculatedDuration);
  });

  return {
    combatDuration
  };
}

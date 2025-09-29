import { computed } from 'vue';
import type { Spacecraft, Asteroid, Station } from '@/types/types';
import type { ComputedRef } from 'vue';
import { useSpacecraftStore } from '@/Composables/useSpacecraftStore';

export function useSpacecraftUtils(
  content: ComputedRef<any>,
  actionType: ComputedRef<string>
) {

  const { spacecrafts } = useSpacecraftStore();

  const getAllowedTypes = () => {
    if (actionType.value === 'mining') {
      return ['Miner', 'Transporter'];
    }
    if (actionType.value === 'combat') {
      return ['Fighter', 'Transporter'];
    }
    return [];
  };

  const setMaxAvailableUnits = () => {
    const MaxAvailableUnits = {};
    const allowedTypes = getAllowedTypes();

    spacecrafts.value.forEach((spacecraft: Spacecraft) => {
      if (allowedTypes.includes(spacecraft.type)) {
        MaxAvailableUnits[spacecraft.name] = spacecraft.count - (spacecraft.locked_count || 0);
      }
    });

    return MaxAvailableUnits;
  };

  const setMinNeededUnits = () => {
    const MinNeededUnits: { [key: string]: number } = {};

    if (!content?.value?.data?.resources) {
      return {};
    }

    const asteroid = content.value.data;
    let remainingResources = asteroid.resources.reduce((total, resource) => total + resource.amount, 0);

    const allowedTypes = getAllowedTypes();

    if (asteroid.size === 'extreme') {
      const titan = spacecrafts.value.find(s => s.name === 'Titan');
      if (titan && allowedTypes.includes(titan.type)) {
        const availableCount = Math.max(0, titan.count - (titan.locked_count || 0));
        if (availableCount > 0) {
          MinNeededUnits['Titan'] = 1;
          remainingResources -= titan.cargo;
        }
      }
    }

    const RESOURCE_THRESHOLD = 20;
    
    const processSpacecrafts = (filterFn: (spacecraft: Spacecraft) => boolean) => {
      spacecrafts.value
        .filter(filterFn)
        .forEach((spacecraft: Spacecraft) => {
          if (remainingResources <= RESOURCE_THRESHOLD) {
            MinNeededUnits[spacecraft.name] = MinNeededUnits[spacecraft.name] || 0;
            return;
          }
    
          const availableCount = Math.max(0, spacecraft.count - (spacecraft.locked_count || 0));
          // Berechne, wie viele Einheiten maximal sinnvoll sind
          let neededUnits = Math.ceil(remainingResources / spacecraft.cargo);
    
          // Prüfe, ob nach dem Einsatz einer weiteren Einheit der Rest unter Threshold fällt
          while (
            neededUnits > 0 &&
            remainingResources - (neededUnits - 1) * spacecraft.cargo <= RESOURCE_THRESHOLD
          ) {
            neededUnits--;
          }
    
          const usedUnits = Math.min(neededUnits, availableCount);
    
          MinNeededUnits[spacecraft.name] = usedUnits;
          remainingResources -= usedUnits * spacecraft.cargo;
        });
    };

    // Nur die erlaubten Typen verarbeiten
    processSpacecrafts((spacecraft) => allowedTypes.includes(spacecraft.type));

    return MinNeededUnits;
  };

  return {
    setMaxAvailableUnits,
    setMinNeededUnits
  };
}

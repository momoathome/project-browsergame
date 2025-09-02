import { computed } from 'vue';
import type { SpacecraftSimple, Asteroid, Station } from '@/types/types';
import type { ComputedRef } from 'vue';

export function useSpacecraftUtils(
  spacecrafts: ComputedRef<SpacecraftSimple[]>, 
  formSpacecrafts: any, 
  content: ComputedRef<any>,
  actionType: ComputedRef<string>
) {

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

    spacecrafts.value.forEach((spacecraft: SpacecraftSimple) => {
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

    const processSpacecrafts = (filterFn: (spacecraft: SpacecraftSimple) => boolean) => {
      spacecrafts.value
        .filter(filterFn)
        .forEach((spacecraft: SpacecraftSimple) => {
          if (remainingResources <= 0) {
            MinNeededUnits[spacecraft.name] = MinNeededUnits[spacecraft.name] || 0;
            return;
          }

          if (asteroid.size === 'extreme' && spacecraft.name === 'Titan' && MinNeededUnits['Titan']) return;

          const availableCount = Math.max(0, spacecraft.count - (spacecraft.locked_count || 0));
          const neededUnits = Math.ceil(remainingResources / spacecraft.cargo);
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

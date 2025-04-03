import { computed } from 'vue';
import type { Spacecraft, Asteroid, Station } from '@/types/types';
import type { ComputedRef } from 'vue';

export function useSpacecraftUtils(spacecrafts: ComputedRef<Spacecraft[]>, formSpacecrafts: any, content: ComputedRef<any>) {
  /**
   * Setzt die maximal verfügbaren Einheiten für alle Raumschiffe
   */
  const setMaxAvailableUnits = () => {
    const MaxAvailableUnits = {};

    spacecrafts.value.forEach((spacecraft: Spacecraft) => {
      if (spacecraft.type !== "Miner") {
        // Berücksichtige nur die verfügbaren Schiffe (count - locked_count)
        MaxAvailableUnits[spacecraft.name] = spacecraft.count - (spacecraft.locked_count || 0);
      }
    });

    return MaxAvailableUnits;
  };

    /**
   * Setzt die Mindestanzahl an Einheiten, die benötigt werden, um alle Ressourcen zu transportieren
   */
  const setMinNeededUnits = () => {
    const MinNeededUnits: { [key: string]: number } = {};
    
    if (!content?.value?.data?.resources) {
      return {};
    }
    
    const asteroid = content.value.data;
    let remainingResources = asteroid.resources.reduce((total, resource) => total + resource.amount, 0);
  
    const processSpacecrafts = (filterFn: (spacecraft: Spacecraft) => boolean) => {
      spacecrafts.value
        .filter(filterFn)
        .forEach((spacecraft: Spacecraft) => {
          if (remainingResources <= 0) {
            MinNeededUnits[spacecraft.name] = MinNeededUnits[spacecraft.name] || 0;
            return;
          }
  
          // Verfügbare Einheiten unter Berücksichtigung der gesperrten Einheiten
          const availableCount = Math.max(0, spacecraft.count - (spacecraft.locked_count || 0));
          
          // Benötigte Einheiten basierend auf der verbleibenden Ressourcenmenge
          const neededUnits = Math.ceil(remainingResources / spacecraft.cargo);
          
          // Wir verwenden nicht mehr als verfügbar
          const usedUnits = Math.min(neededUnits, availableCount);
  
          MinNeededUnits[spacecraft.name] = usedUnits;

          console.log(`Spacecraft: ${spacecraft.name}, Available: ${availableCount}, Needed: ${neededUnits}, Used: ${usedUnits}, MinNeeded ${MinNeededUnits}`);
          
          // Aktualisieren der verbleibenden Ressourcen
          remainingResources -= usedUnits * spacecraft.cargo;
        });
    };
  
    // Process spacecrafts by type
    // Miner first, then Transporter, then others
    processSpacecrafts((spacecraft) => spacecraft.type === "Miner");
    processSpacecrafts((spacecraft) => spacecraft.type === "Transporter");
    processSpacecrafts((spacecraft) => spacecraft.type !== "Miner" && spacecraft.type !== "Transporter");
  
    return MinNeededUnits;
  };

  /**
   * Berechnet die Gesamtkampfkraft der ausgewählten Raumschiffe
   */
  const calculateTotalCombatPower = () => {
    let total = 0;

    for (const spacecraft in formSpacecrafts) {
      const combat = spacecrafts.value.find((s: Spacecraft) => s.name === spacecraft)?.combat;
      if (combat !== undefined) {
        total += combat * formSpacecrafts[spacecraft];
      }
    }

    return total;
  };

  /**
   * Berechnet die Gesamtfrachtkapazität der ausgewählten Raumschiffe
   */
  const calculateTotalCargoCapacity = () => {
    let total = 0;

    for (const spacecraft in formSpacecrafts) {
      const cargo = spacecrafts.value.find((s: Spacecraft) => s.name === spacecraft)?.cargo;
      if (cargo !== undefined) {
        total += cargo * formSpacecrafts[spacecraft];
      }
    }

    return total;
  };

  return {
    setMaxAvailableUnits,
    setMinNeededUnits,
    calculateTotalCombatPower,
    calculateTotalCargoCapacity
  };
}

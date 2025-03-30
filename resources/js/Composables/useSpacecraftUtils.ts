import { computed } from 'vue';
import type { Spacecraft, Asteroid, Station } from '@/types/types';
import type { ComputedRef } from 'vue';

export function useSpacecraftUtils(spacecrafts: Readonly<Spacecraft[]>, formSpacecrafts: any, content: ComputedRef<any>) {
  /**
   * Setzt die maximal verfügbaren Einheiten für alle Raumschiffe
   */
  const setMaxAvailableUnits = () => {
    const MaxAvailableUnits = {};

    spacecrafts.forEach((spacecraft: Spacecraft) => {
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
    
    if (!content || !content.value.data || !content.value.data.resources) {
      return {};
    }
    
    const asteroid = content.value.data;
    const totalAsteroidResources = asteroid.resources.reduce((total, resource) => total + resource.amount, 0);
    let remainingResources = totalAsteroidResources;

    // Funktion zum Verarbeiten von Raumschiffen eines bestimmten Typs
    const processSpacecraftType = (type: string) => {
      spacecrafts.forEach((spacecraft: Spacecraft) => {
        if (remainingResources <= 0) {
          MinNeededUnits[spacecraft.name] = MinNeededUnits[spacecraft.name] || 0;
          return;
        }

        if (spacecraft.type === type) {
          const availableCount = spacecraft.count - (spacecraft.locked_count || 0);
          const neededUnits = Math.ceil(remainingResources / spacecraft.cargo);
          const usedUnits = Math.min(neededUnits, availableCount);

          MinNeededUnits[spacecraft.name] = usedUnits;
          remainingResources -= usedUnits * spacecraft.cargo;
        }
      });
    };

    // Verarbeite zuerst Miner und transporter
    processSpacecraftType("Miner");
    processSpacecraftType("Transporter");

    // Schließlich alle anderen Raumschifftypen
    spacecrafts.forEach((spacecraft: Spacecraft) => {
      if (remainingResources <= 0) {
        MinNeededUnits[spacecraft.name] = MinNeededUnits[spacecraft.name] || 0;
        return;
      }

      if (spacecraft.type !== "Miner" && spacecraft.type !== "Transporter") {
        const availableCount = spacecraft.count - (spacecraft.locked_count || 0);
        const neededUnits = Math.ceil(remainingResources / spacecraft.cargo);
        const usedUnits = Math.min(neededUnits, availableCount);

        MinNeededUnits[spacecraft.name] = usedUnits;
        remainingResources -= usedUnits * spacecraft.cargo;
      }
    });

    return MinNeededUnits;
  };

  /**
   * Berechnet die Gesamtkampfkraft der ausgewählten Raumschiffe
   */
  const calculateTotalCombatPower = () => {
    let total = 0;

    for (const spacecraft in formSpacecrafts) {
      const combat = spacecrafts.find((s: Spacecraft) => s.name === spacecraft)?.combat;
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
      const cargo = spacecrafts.find((s: Spacecraft) => s.name === spacecraft)?.cargo;
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

import type { Building, FormattedBuilding, Resource } from '@/types/types';

export function useBuildingFormatting() {
  const formatBuilding = (building: Building): FormattedBuilding => {
    return {
        id: building.id,
        image: building.details.image,
        name: building.details.name,
        description: building.details.description,
        level: building.level,
        build_time: building.build_time,
        effect: building.details.effect,
        effect_value: building.effect_value,
        is_upgrading: building.is_upgrading,
        upgrade_end_time: building.upgrade_end_time,
        resources: building.resources.map((resource: Resource) => ({
          id: resource.id,
          name: resource.name,
          image: resource.image,
          amount: resource.pivot.amount
        }))
          .sort((a, b) => a.name.localeCompare(b.name))
      };
  };

  return {
    formatBuilding
  };
}

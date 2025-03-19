import type { Spacecraft, FormattedSpacecraft } from '@/types/types';

export function useSpacecraftFormatting() {
  const formatSpacecraft = (spacecraft: Spacecraft): FormattedSpacecraft => {
    return {
      id: spacecraft.id,
      image: spacecraft.details.image,
      name: spacecraft.details.name,
      description: spacecraft.details.description,
      type: spacecraft.details.type,
      combat: spacecraft.combat,
      count: spacecraft.count,
      cargo: spacecraft.cargo,
      speed: spacecraft.speed,
      build_time: spacecraft.build_time,
      crew_limit: spacecraft.crew_limit,
      unlocked: spacecraft.unlocked,
      research_cost: spacecraft.research_cost,
      is_producing: spacecraft.is_producing,
      end_time: spacecraft.end_time,
      currently_producing: spacecraft.currently_producing,
      resources: spacecraft.resources.map((resource) => ({
        id: resource.id,
        name: resource.name,
        image: resource.image,
        amount: resource.pivot.amount
      }))
    };
  };

  return {
    formatSpacecraft
  };
}

import { ref, computed } from 'vue';
import type { Station } from '@/types/types';

export interface PlayerInfluence {
  userId: number;
  station: Station;
  influence: number;
  name: string;
}

export default function useInfluence(
    props, 
    stations, 
    activeSidebar,
    drawInfluenceLayer: () => void,
    drawScene: () => void
) {
  const showInfluence = ref(false);
  const showInfluenceSidebar = ref(false);

  const playerInfluences = computed<PlayerInfluence[]>(() => {
    return props.influenceOfAllUsers
      .map(inf => {
        const station = stations.find(s => s.user_id === inf.user_id);
        if (!station) return null;
        return {
          userId: inf.user_id,
          station,
          influence: Number(inf.attribute_value),
          name: inf.name
        } as PlayerInfluence;
      })
      .filter(Boolean) as PlayerInfluence[];
  });

  function getInfluenceColor(userId: number) {
    const hue = (userId * 137) % 360;
    return `hsla(${hue}, 80%, 60%, 0.18)`;
  }

  function toggleInfluence() {
    showInfluence.value = !showInfluence.value;
    drawInfluenceLayer();
    drawScene();
  }

  function openInfluenceSidebar() {
    showInfluenceSidebar.value = true;
    activeSidebar.value = 'influence';
  }

  function closeInfluenceSidebar() {
    showInfluenceSidebar.value = false;
    if (activeSidebar.value === 'influence') activeSidebar.value = null;
  }

  return {
    showInfluence,
    showInfluenceSidebar,
    playerInfluences,
    getInfluenceColor,
    toggleInfluence,
    openInfluenceSidebar,
    closeInfluenceSidebar,
  };
}

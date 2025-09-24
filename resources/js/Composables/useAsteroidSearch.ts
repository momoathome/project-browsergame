import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { api } from '@/Services/api';

interface SimpleAsteroid {
  id: number;
  name: string;
}

export default function useAsteroidSearch(
  drawScene: () => void,
  props: { asteroids: any[]; stations: any[]; rebels: any[] },
  userStation: any,
  userScanRange: any,
  focusOnObject: (object: any, userId?: number) => void
) {
  const searchForm = useForm({ query: '' });
  const highlightedAsteroids = ref<SimpleAsteroid[]>([]);
  const highlightedStations = ref<number[]>([]);
  const highlightedRebels = ref<number[]>([]);

  const scanAnimation = ref({
    active: false,
    radius: 0,
    maxRadius: 0,
    asteroidsToHighlight: [] as number[],
    animationFrame: 0,
    startTime: 0,
    duration: 1000,
  });

  const performSearch = async (onSearchComplete = () => {}) => {
    const { data, error } = await api.asteroids.search(searchForm.query);

    if (!error) {
      highlightedAsteroids.value = data.searched_asteroids;
      highlightedStations.value = data.searched_stations;
      highlightedRebels.value = data.searched_rebels;
      onSearchComplete();
    } else {
      console.error('Error during search:', error);
      onSearchComplete();
    }
  };

  const clearSearch = () => {
    searchForm.query = '';
    highlightedAsteroids.value = [];
    highlightedStations.value = [];
    drawScene();
  };

  function focusOnSingleResult() {
    if (highlightedAsteroids.value.length === 1) {
      const asteroid = props.asteroids.find(a => a.id === highlightedAsteroids.value[0]?.id);
      if (asteroid) focusOnObject(asteroid);
    } else if (highlightedStations.value.length === 1) {
      const station = props.stations.find(s => s.id === highlightedStations.value[0]);
      if (station) focusOnObject(station, station.user_id);
    } else if (highlightedRebels.value.length === 1) {
      const rebel = props.rebels.find(r => r.id === highlightedRebels.value[0]);
      if (rebel) focusOnObject(rebel);
    }
  }

  function animateScanWave() {
    if (!scanAnimation.value.active || !userStation.value) return;

    const now = performance.now();
    const elapsed = now - scanAnimation.value.startTime;
    const progress = Math.min(elapsed / scanAnimation.value.duration, 1);

    scanAnimation.value.radius = scanAnimation.value.maxRadius * progress;
    drawScene();

    if (progress < 1) {
      scanAnimation.value.animationFrame = requestAnimationFrame(animateScanWave);
    } else {
      scanAnimation.value.active = false;
      focusOnSingleResult();
      drawScene();
    }
  }

  function searchAndFocus() {
    performSearch(() => {
      if (highlightedAsteroids.value.length === 0 && highlightedStations.value.length > 0 || highlightedRebels.value.length > 0) {
        focusOnSingleResult();
        drawScene();
        return;
      }

      if (userStation.value) {
        scanAnimation.value.asteroidsToHighlight = highlightedAsteroids.value.map(a => a.id);
        scanAnimation.value.active = true;
        scanAnimation.value.radius = 0;
        scanAnimation.value.maxRadius = userScanRange.value;
        scanAnimation.value.animationFrame = 0;
        scanAnimation.value.startTime = performance.now();
        animateScanWave();
      } else {
        focusOnSingleResult();
        drawScene();
      }
    });
  }

  function clearSearchAndUpdate() {
    clearSearch();
    drawScene();
  }

  const currentlyHighlightedAsteroidIds = computed(() => {
    if (scanAnimation.value.active && userStation.value) {
      return highlightedAsteroids.value
        .filter(a => {
          const asteroid = props.asteroids.find(ast => ast.id === a.id);
          if (!asteroid) return false;
          const dx = asteroid.x - userStation.value.x;
          const dy = asteroid.y - userStation.value.y;
          const dist = Math.sqrt(dx * dx + dy * dy);
          return dist <= scanAnimation.value.radius;
        })
        .map(a => a.id);
    }
    return highlightedAsteroids.value.map(a => a.id);
  });

  return {
    searchForm,
    performSearch,
    clearSearch,
    highlightedAsteroids,
    highlightedStations,
    highlightedRebels,
    scanAnimation,
    searchAndFocus,
    clearSearchAndUpdate,
    animateScanWave,
    focusOnSingleResult,
    currentlyHighlightedAsteroidIds,
  };
}

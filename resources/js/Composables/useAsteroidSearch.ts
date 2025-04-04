import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3';

interface SimpleAsteroid {
  id: number;
  name: string;
}

const useAsteroidSearch = (drawScene: () => void) => {
  const searchForm = useForm({
    query: ''
  });

  const highlightedAsteroids = ref<SimpleAsteroid[]>([]);
  const highlightedStations = ref<number[]>([]);

  const performSearch = async (onSearchComplete = () => { }) => {
    const response = await fetch(route('asteroidMap.search'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ query: searchForm.query })
    })

    const result = await response.json();

    if (response.ok) {
      highlightedAsteroids.value = result.searched_asteroids
      highlightedStations.value = result.searched_stations

      // Callback aufrufen, wenn die Suche abgeschlossen ist
      onSearchComplete();
    } else {
      console.error('Error during search:', result);
      onSearchComplete();
    }
  };

  const clearSearch = () => {
    searchForm.query = '';
    highlightedAsteroids.value = [];
    highlightedStations.value = [];
    drawScene();
  };

  return {
    searchForm,
    performSearch,
    clearSearch,
    highlightedAsteroids,
    highlightedStations
  };
};

export default useAsteroidSearch;

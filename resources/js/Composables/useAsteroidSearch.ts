import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3';
import { api } from '@/Services/api';

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
    const { data, error } = await api.asteroids.search(searchForm.query);

    if (!error) {
      highlightedAsteroids.value = data.searched_asteroids
      highlightedStations.value = data.searched_stations

      // Callback aufrufen, wenn die Suche abgeschlossen ist
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

  return {
    searchForm,
    performSearch,
    clearSearch,
    highlightedAsteroids,
    highlightedStations
  };
};

export default useAsteroidSearch;

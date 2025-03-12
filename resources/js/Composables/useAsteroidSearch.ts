import { ref } from 'vue'
import { usePage, useForm, router } from '@inertiajs/vue3';

const useAsteroidSearch = (drawScene: () => void) => {
  const searchForm = useForm({
    query: ''
  });

  const highlightedAsteroids = ref<number[]>([]);
  const highlightedStations = ref<number[]>([]);

  const performSearch = (onSearchComplete = () => { }) => {
    searchForm.get(route('asteroidMap.search'), {
      preserveState: true,
      preserveScroll: true,
      only: ['searched_asteroids', 'searched_stations'],
      onSuccess: (page) => {
        const updateHighlightedItems = (items, highlightedRef) => {
          highlightedRef.value = items?.length ? items.map(item => item.id) : [];
        };

        updateHighlightedItems(page.props.searched_asteroids, highlightedAsteroids);
        updateHighlightedItems(page.props.searched_stations, highlightedStations);

        // Callback aufrufen, wenn die Suche abgeschlossen ist
        onSearchComplete();
      },
      onError: (errors) => {
        console.error('Error during search:', errors);
        onSearchComplete();
      }
    });
  };

  const clearSearch = () => {
    router.visit(route('asteroidMap'), {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      onSuccess: () => {
        searchForm.query = '';
        highlightedAsteroids.value = [];
        highlightedStations.value = [];
        usePage().props.searched_asteroids = [];
        usePage().props.searched_stations = [];
        drawScene();
      }
    });
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

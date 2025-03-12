import { ref } from 'vue'
import { usePage, useForm } from '@inertiajs/vue3';

const useAsteroidSearch = () => {
  const searchForm = useForm({
    query: ''
  });

  const highlightedAsteroids = ref<number[]>([]);
  const highlightedStations = ref<number[]>([]);

  const performSearch = (onSearchComplete = () => {}) => {
    searchForm.get('/asteroidMap/search', {
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
    const url = new URL(window.location.href);
    url.searchParams.delete('query');
    url.pathname = '/asteroidMap';
    window.history.pushState({}, '', url);
    usePage().props.searched_asteroids = [];

    searchForm.query = '';
    highlightedAsteroids.value = [];
    highlightedStations.value = [];
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

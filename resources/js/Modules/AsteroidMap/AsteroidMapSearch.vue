<script setup>
import { ref, computed, nextTick } from 'vue';

const suggestions = [
  'Common',
  'Uncommon',
  'Rare',
  'Extrem',
  'Carbon',
  'Hydrogenium',
  'Kyberkristall',
  'Titanium',
  'Uraninite',
  'Cobalt',
  'Iridium',
  'Thorium',
  'Hyerdiamond',
  'Astatine',
  'Dilithium',
  'Deuterium'
];

const query = defineModel()
const showSuggestions = ref(false)
const highlightedIndex = ref(-1);
const suggestionRefs = ref([]);

const emit = defineEmits(['input', 'clear', 'search'])

const clearSearch = () => {
  query.value = '';
  showSuggestions.value = false;
  highlightedIndex.value = -1;
  suggestionRefs.value = [0];
  emit('clear');
} 

const filteredSuggestions = computed(() => {
  if (query.value === '') {
    return [];
  }
  return suggestions.filter((suggestion) =>
    suggestion.toLowerCase().includes(query.value.toLowerCase())
  );
});

const onInput = () => {
  highlightedIndex.value = -1;
  // Logik oder API-Aufruf hier falls nötig
};

const moveDown = async () => {
  if (highlightedIndex.value < filteredSuggestions.value.length - 1) {
    highlightedIndex.value++;
  }
  await nextTick();
  scrollToHighlighted();
};

const moveUp = async () => {
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--;
  }
  await nextTick();
  scrollToHighlighted();
};

const scrollToHighlighted = () => {
  const currentElement = suggestionRefs.value[highlightedIndex.value];
  if (currentElement) {
    currentElement.scrollIntoView({
      behavior: 'smooth',
      block: 'nearest',
    });
  }
};

const selectCurrent = () => {
  if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredSuggestions.value.length) {
    selectSuggestion(filteredSuggestions.value[highlightedIndex.value]);
  } else {
    selectSuggestion(query.value);
  }
};

const selectSuggestion = (suggestion) => {
  query.value = suggestion;
  emit('search');
  highlightedIndex.value = -1;
  suggestionRefs.value = [0];
};

const hideSuggestions = () => {
  setTimeout(() => {
    showSuggestions.value = false;
  }, 100);
}

const getSuggestionRef = (index) => {
  return (el) => {
    suggestionRefs.value[index] = el;
  };
};
</script>

<template>
  <div class="relative bg-[hsl(263,45%,7%)]">
    <input 
      type="text"
      v-model="query"
      @input="onInput"
      @keydown.down.prevent="moveDown"
      @keydown.up.prevent="moveUp"
      @keydown.enter.prevent="selectCurrent"
      @focusin="showSuggestions = true" 
      @focusout="hideSuggestions"
      class="peer text-light bg-inherit rounded-lg w-60 ring-[#bfbfbf] focus:border-[#bfbfbf] focus:ring-[#bfbfbf]"
      placeholder="Search by name or resource"
      :class="{ 'rounded-b-none': filteredSuggestions.length > 0 && showSuggestions }"
    />
    <ul v-if="filteredSuggestions.length > 0" 
      class="w-60 max-h-44 py-1 -my-[1px] list-none overflow-y-auto peer-focus:ring-1 ring-[#bfbfbf] border border-[#6b7280] peer-focus-within:border-[#bfbfbf] rounded-b-lg no-scrollbar" 
      :class="{ 'hidden': !showSuggestions }">
        <li v-for="(suggestion, index) in filteredSuggestions" 
          :key="index" 
          @click="selectSuggestion(suggestion)" 
          class="py-1 px-3 cursor-pointer text-white text-sm hover:bg-slate-900"
          :class="{'bg-slate-900': index === highlightedIndex}"
          :ref="getSuggestionRef(index)"
        >
          {{ suggestion }}
        </li>
    </ul>
    <button @click="clearSearch" type="button" class="text-white absolute top-0 right-0 h-[42px] w-8 rounded-lg">x</button>
  </div>
</template>

<style scoped>
ul::-webkit-scrollbar-track
{
	border-radius: 16px;
	background-color: hsl(263,45%,7%);
}

ul::-webkit-scrollbar
{
	width: 3px;
	background-color: hsl(263,45%,7%);
}

ul::-webkit-scrollbar-thumb
{
	border-radius: 10px;
	-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,.3);
	background-color: #bfbfbf;
}
</style>

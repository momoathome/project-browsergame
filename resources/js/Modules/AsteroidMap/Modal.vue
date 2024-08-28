<script setup>
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import AsteroidModalResourceSvg from './AsteroidModalResourceSvg.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    title: {
        type: String,
        default: '',
    },
    content: {
        type: Object,
        default: null,
    },
});

const close = () => {
    emit('close');
};

const showDetails = ref(false);

const toggleDetails = () => {
    showDetails.value = !showDetails.value;
}
</script>

<template>
    <Modal :show="show" :closeable="closeable" @close="close">
        <div class="px-8 pb-8 pt-8 h-full bg-gray-800 text-white">

            <button class="absolute top-4 right-4 text-white" @click="close">X</button>
            <h3 class="text-2xl mb-16 flex justify-center">{{ title }}</h3>
            <div v-if="content" class="relative">
                <img v-if="content.type === 'station'" :src="content.imageSrc" alt="Station"
                    class="w-32 h-32 mx-auto" />

                <div v-if="content.type === 'asteroid'" class="relative w-32 h-32 mx-auto">
                    <AsteroidModalResourceSvg v-if="content.type === 'asteroid'" :asteroid="content.data" />
                    <img :src="content.imageSrc" alt="Asteroid" class="w-full h-full absolute inset-0" />
                </div>

                <div v-if="content.type === 'station'" class="text-gray-700 dark:text-gray-300">
                    <p>This is a space station. No further details available.</p>
                </div>

                <div class="flex justify-center pt-12">
                    <PrimaryButton v-if="content.type === 'asteroid'" @click="toggleDetails">
                        {{ showDetails ? 'Hide Details' : 'Show Details' }}
                    </PrimaryButton>
                </div>

                <div v-if="content.type === 'asteroid' && showDetails" class="text-gray-300 pt-6">
                    <p><strong>Rarity:</strong> {{ content.data.rarity }}</p>
                    <p><strong>Base Value:</strong> {{ content.data.base }}</p>
                    <p><strong>Multiplier:</strong> {{ content.data.multiplier }}</p>
                    <p><strong>Value:</strong> {{ content.data.value }}</p>
                    <p><strong>Pool:</strong> {{ content.data.resource_pool }}</p>
                    <div class="flex flex-col gap-1"><p><strong>Resources:</strong></p>
                        <span v-for="(value, key) in content.data.resources" :key="key" class="flex ms-4">
                            <img :src="`/storage/resources/${key}.png`" class="h-6 mr-2" />
                            {{ key }}: {{ value }}
                        </span></div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script lang="ts" setup>
import type { ButtonHTMLAttributes } from 'vue';

interface Props {
    type?: ButtonHTMLAttributes['type'];
}

const props = withDefaults(defineProps<Props>(), {
    type: 'button',
});
</script>

<template>
    <button :type="type"
        class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-white bg-secondary border-secondary-light font-semibold border border-solid outline-none transition hover:bg-secondary-dark disabled:opacity-70 disabled:shadow-none disabled:pointer-events-none">
        <slot />
    </button>
</template>

<style scoped>
:disabled {
  filter: brightness(0.7) grayscale(0.4);
}
</style>

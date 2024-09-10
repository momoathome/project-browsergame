<script lang="ts" setup>
import type { ButtonHTMLAttributes } from 'vue';

interface Props {
    type: ButtonHTMLAttributes['type'];
}

const props = withDefaults(defineProps < Props > (), {
    type: 'submit',
});
</script>

<template>
    <button :type="type"
        class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-white bg-tertiary border-tertiary-light font-medium border border-solid outline-none transition hover:bg-tertiary-dark disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none">
        <slot />
    </button>
</template>

<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-white bg-secondary border-secondary-light font-medium border border-solid outline-none transition hover:bg-secondary-dark disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none">
        <slot />
    </button>
</template>

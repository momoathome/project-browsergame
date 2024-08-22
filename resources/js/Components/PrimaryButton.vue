<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 rounded-lg text-white bg-primary border-primary-light font-medium border-solid outline-none transition hover:bg-primary-dark disabled:opacity-50 disabled:shadow-none disabled:pointer-events-none">
        <slot />
    </button>
</template>

<script lang="ts" setup>

</script>

<template>
    <div
        class="w-44 h-44 flex items-center justify-center bg-gradient-to-br from-primary/10 to-secondary/10 rounded-xl border-2 border-secondary/30 shadow-md transition hover:scale-105">
        <div class="flex flex-col items-center gap-2 text-secondary">
            <img src="/images/attributes/storage.png" class="h-8 w-8 opacity-60" alt="">
            <slot></slot>
        </div>
    </div>
</template>

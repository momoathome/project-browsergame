<script lang="ts" setup>
interface Props {
  label: string
  position?: string
}

withDefaults(defineProps<Props>(), {
  position: 'bottom',
})
</script>

<template>
  <span
    class="pointer-events-none absolute z-100 whitespace-nowrap text-center bg-gray-800 text-white text-sm py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300"
    :class="position" v-html="label">
  </span>
</template>

<!-- <template>
  <div class="flex relative group">
    <slot />
    <div class="rounded-xl flex flex-col bg-#1E2D3B text-xs w-max opacity-0 py-2 px-3 transition z-10 transition-duration-1000 absolute invisible group-hover:opacity-100 group-hover:visible"
      :class="position">
      <span>{{ title }}</span>
    </div>
  </div>
</template> -->

<style scoped>
.top {
  bottom: 100%;
  margin-bottom: 2px;
  left: 50%;
  transform: translateX(-50%);
}

.bottom {
  top: 100%;
  margin-top: 2px;
  left: 50%;
  transform: translateX(-50%);
}

.left {
  right: 105%;
  margin-right: 2px;
}

.right {
  left: 105%;
  margin-left: 2px;
}
</style>

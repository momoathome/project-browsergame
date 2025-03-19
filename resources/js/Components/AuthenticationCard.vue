<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 background">
        <div>
            <slot name="logo" />
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-base shadow-md overflow-hidden sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>

<style>
.background {
  background: linear-gradient(30deg, #1e2530, #1d242e, #1c222c, #1b212b, #1a1f29, #181e27, #171d25, #161b24, #151a22, #141920, #13171f, #12161d);
}
</style>

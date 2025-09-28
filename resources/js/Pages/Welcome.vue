<template>
    <div class="min-h-screen bg-base text-light">

        <Head title="welcome" />

        <div class="bg-base px-6 py-4 sticky top-0 z-50">
            <div class="container mx-auto flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <!-- Replace with actual logo -->
                    <div class="text-light text-2xl font-bold flex items-center">
                        <!-- <svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="none">
                            <path
                                d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"
                                fill="#AA9C78" stroke="#DADCE5" stroke-width="1" />
                        </svg> -->
                        Orion's Frontier
                    </div>
                </div>
                <nav v-if="canLogin" class="flex items-center space-x-4">
                    <template v-if="$page.props.auth.user">
                        <Link :href="route('buildings')"
                            class="flex px-4 py-2 rounded-lg text-white bg-primary font-medium outline-none transition hover:bg-primary-dark">
                        station
                        </Link>
                        <!-- logout  -->
                        <form @submit.prevent="$inertia.post(route('logout'))">
                            <button type="submit"
                                class="flex px-4 py-2 rounded-lg text-white bg-tertiary font-medium outline-none transition hover:bg-tertiary-dark">
                                Log out
                            </button>
                        </form>
                    </template>

                    <template v-else>
                        <Link :href="route('login')"
                            class="flex px-4 py-2 rounded-lg text-white bg-primary font-medium outline-none transition hover:bg-primary-dark border-primary-light border border-solid">
                        Log in
                        </Link>

                        <Link v-if="canRegister" :href="route('register')"
                            class="flex px-4 py-2 rounded-lg bg-secondary hover:bg-secondary-light text-tertiary-dark font-medium transition border border-solid border-secondary-dark">
                        Register
                        </Link>
                    </template>
                </nav>
            </div>
        </div>

        <div class="relative h-dvh">
            <!-- Background with stars effect -->
            <div class="stars-container absolute inset-0 bg-root">
                <div id="stars"></div>
                <div id="stars2"></div>
                <div id="stars3"></div>
            </div>

            <!-- Hero Content -->
            <div class="container mx-auto px-6 py-20 lg:py-32 2xl:py-48 relative z-10 flex flex-col items-center">
                <h1 class="text-5xl md:text-6xl font-bold text-light text-center leading-tight mb-6">
                    Conquer <span class="text-secondary">Orion's Belt</span>
                </h1>
                <p class="text-xl md:text-2xl text-gray text-center max-w-3xl mb-12">
                    Mine asteroids, build your space station, lead fleets into battle, and become the ultimate galactic
                    ruler.
                </p>
                <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-6">
                    <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')"
                        class="px-8 py-4 bg-secondary hover:bg-secondary-light border border-solid border-secondary-dark text-tertiary-dark font-bold text-lg rounded-lg transition transform hover:scale-105">
                    Play Now
                    </Link>
                </div>

                <!-- <div class="mt-16 relative w-full max-w-6xl">
                    <div class="rounded-lg shadow-2xl">
                        <div class="bg-base p-1 rounded">
                            <div class="aspect-auto rounded overflow-hidden">
                                <img src="/images/game-screenshot.webp" alt="Game Screenshot">
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>

        <!-- Features Section -->
        <section id="features" class="bg-base-dark py-16">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl font-bold text-secondary mb-12 text-center">Game Features</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="bg-base p-6 rounded-lg">
                        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center mb-4">
                            <img src="/images/navigation/asteroidmap.png" alt="Asteroid" class="w-6 h-6">
                        </div>
                        <h3 class="text-xl font-bold text-secondary mb-2">Mine Asteroids</h3>
                        <p class="text-gray">Harvest valuable resources from asteroid fields to fund your galactic
                            ambitions.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-base p-6 rounded-lg">
                        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center mb-4">
                            <img src="/images/navigation/simulator.png" alt="Strategic Battles" class="w-6 h-6">
                        </div>
                        <h3 class="text-xl font-bold text-secondary mb-2">Strategic Battles</h3>
                        <p class="text-gray">Command your fleets in tactical warfare against rival empires.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-base p-6 rounded-lg">
                        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center mb-4">
                            <img src="/images/navigation/buildings.png" alt="Space Station" class="w-6 h-6">
                        </div>
                        <h3 class="text-xl font-bold text-secondary mb-2">Build Your Station</h3>
                        <p class="text-gray">Customize and expand your space station with advanced technology and
                            defenses.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gameplay Preview -->
        <!-- <section id="gameplay" class="py-16 bg-primary-dark">
            <div class="container mx-auto px-6">
                <h2 class="text-3xl font-bold text-secondary mb-12 text-center">Gameplay Preview</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="flex flex-col justify-center">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-secondary mb-3">Space Exploration</h3>
                            <p class="text-gray">Discover new star systems, establish mining colonies, and expand your
                                empire across the galaxy.</p>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-secondary mb-3">Fleet Management</h3>
                            <p class="text-gray">Design and build powerful spacecraft, from nimble fighters to massive
                                capital ships.</p>
                        </div>

                        <div>
                            <h3 class="text-2xl font-bold text-secondary mb-3">Alliance System</h3>
                            <p class="text-gray">Form strategic alliances with other players to dominate sectors and
                                wage epic wars.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="aspect-square bg-tertiary rounded overflow-hidden">
                            <div
                                class="h-full w-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center">
                                <div class="text-light text-opacity-30 text-sm">MINING</div>
                            </div>
                        </div>
                        <div class="aspect-square bg-tertiary rounded overflow-hidden">
                            <div
                                class="h-full w-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center">
                                <div class="text-light text-opacity-30 text-sm">BATTLE</div>
                            </div>
                        </div>
                        <div class="aspect-square bg-tertiary rounded overflow-hidden">
                            <div
                                class="h-full w-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center">
                                <div class="text-light text-opacity-30 text-sm">BUILDING</div>
                            </div>
                        </div>
                        <div class="aspect-square bg-tertiary rounded overflow-hidden">
                            <div
                                class="h-full w-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center">
                                <div class="text-light text-opacity-30 text-sm">RESEARCH</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section> -->

        <!-- Footer -->
        <footer class="bg-base py-8">
            <div class="container mx-auto px-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-center text-gray text-sm">
                        &copy; 2025 Orion's Frontier. All rights reserved.
                    </div>
                    <div class="flex space-x-6">
                        <a href="#" class="text-gray hover:text-secondary transition">Terms</a>
                        <a href="#" class="text-gray hover:text-secondary transition">Privacy</a>
                    </div>
                </div>

            </div>
        </footer>
    </div>
</template>

<script setup>
import { Link, Head } from '@inertiajs/vue3';

defineOptions({
  layout: null
});

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

</script>

<style lang="scss">
// Star Background Function
// n is number of stars required
@use "sass:math";
@use "sass:string";

// Star Background Function
// n is number of stars required
@function multiple-box-shadow($n) {
    $value: '#{math.random(4000)}px #{math.random(4000)}px #FFF';

    @for $i from 2 through $n {
        $value: '#{$value} , #{math.random(4000)}px #{math.random(4000)}px #FFF';
    }

    @return string.unquote($value);
}

$shadows-small: multiple-box-shadow(2000);
$shadows-medium: multiple-box-shadow(300);
$shadows-big: multiple-box-shadow(150);

#stars {
    width: 1px;
    height: 1px;
    background: transparent;
    box-shadow: $shadows-small;
    animation: animStar 50s linear infinite;

    &:after {
        content: '';
        position: absolute;
        top: 2000px;
        width: 1px;
        height: 1px;
        background: transparent;
        box-shadow: $shadows-small;
    }
}

#stars2 {
    width: 2px;
    height: 2px;
    background: transparent;
    box-shadow: $shadows-medium;
    animation: animStar 100s linear infinite;

    &:after {
        content: ' ';
        position: absolute;
        top: 2000px;
        width: 2px;
        height: 2px;
        background: transparent;
        box-shadow: $shadows-medium;
    }
}

#stars3 {
    width: 3px;
    height: 3px;
    background: transparent;
    box-shadow: $shadows-big;
    animation: animStar 150s linear infinite;

    &:after {
        content: ' ';
        position: absolute;
        top: 2000px;
        width: 3px;
        height: 3px;
        background: transparent;
        box-shadow: $shadows-big;
    }
}

.stars-container {
    margin: 0;
    padding: 0;
    overflow: hidden;

}

@keyframes animStar {
    from {
        transform: translateY(0px);
    }

    to {
        transform: translateY(-2000px);
    }
}
</style>

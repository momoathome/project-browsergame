import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import LaravelPermissionToVueJS from 'laravel-permission-to-vuejs';
import AppLayout from '@/Layouts/AppLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Orions Frontier';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        return resolvePageComponent(`./Pages/${name}.vue`, pages).then(page => {
            // Setze das Default Layout, falls nicht explizit gesetzt
            page.default.layout = page.default.layout || AppLayout;
            return page;
        });
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(LaravelPermissionToVueJS)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

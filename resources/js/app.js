import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

const ownerName = import.meta.env.VITE_CV_OWNER_NAME || 'Private CV';

createInertiaApp({
    title: (title) => (title ? `${title} · ${ownerName}` : ownerName),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el);
    },
    progress: { color: '#0f766e' },
});

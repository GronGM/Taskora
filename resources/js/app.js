import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from './Components/Theme/ThemeProvider';

createInertiaApp({
    title: (title) => (title ? `${title} — Таскора` : 'Таскора'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx');
        return pages[`./Pages/${name}.jsx`]();
    },
    setup({ el, App, props }) {
        createRoot(el).render(createElement(ThemeProvider, null, createElement(App, props)));
    },
    progress: {
        color: '#2563eb',
    },
});

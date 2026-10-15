// tailwind.config.js  (raíz del proyecto, Tailwind 3.4.19)
// Es TU archivo (Breeze) con lo mínimo agregado, marcado con ◄◄.
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',   // ◄◄ dashboard.js agrega clases (is-open, hp-badge--*)
    ],

    // ◄◄ Clases que se arman dinámicamente (Blade: hp-badge--{{ status }}) y Tailwind no puede detectar
    safelist: [
        'hp-badge--draft',
        'hp-badge--completed',
        'is-open',
        'is-active',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Tu fuente actual (login y demás vistas) se queda igual
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // ◄◄ Fuente del dashboard HELPER (clase font-helper, usada en styles.css)
                helper: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            // ◄◄ Colores del diseño de Figma
            colors: {
                brand: {
                    DEFAULT: '#523ca6',
                    soft: '#eef2ff',
                },
                success: {
                    bg: '#def7ec',
                    text: '#03543f',
                },
            },
        },
    },

    plugins: [forms],
};
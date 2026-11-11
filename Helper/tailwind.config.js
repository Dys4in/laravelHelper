// tailwind.config.js  (raíz del proyecto, Tailwind 3.4.19)
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    safelist: [
        'hp-badge--draft',
        'hp-badge--completed',
        'is-open',
        'is-active',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                helper: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    DEFAULT: '#6d3ce8',
                    dark: '#5a2fd0',
                    light: '#8b5cf6',
                    soft: '#f0eaff',
                },
                // Sidebar oscuro
                ink: {
                    DEFAULT: '#22222f',
                    raised: '#2c2c3d',
                    line: '#383a4f',
                },
                // Fondo 
                canvas: '#e1dfe9',
                line: '#dcd6ee',
                success: {
                    bg: '#def7ec',
                    text: '#03543f',
                },
            },
            boxShadow: {
                brand: '0 10px 24px -10px rgb(109 60 232 / 0.65)',
                card: '0 1px 2px rgb(34 34 47 / 0.05), 0 4px 14px -8px rgb(34 34 47 / 0.12)',
            },
        },
    },

    plugins: [forms],
};
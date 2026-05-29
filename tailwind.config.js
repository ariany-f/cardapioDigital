import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#f4003a',
                    hover: '#d10032',
                    soft: '#fff1f4',
                    dark: '#1a1a1a',
                    green: '#00a650',
                },
            },
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
                menu: ['Poppins', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                menu: '0 4px 24px -4px rgb(244 0 58 / 0.12)',
                'menu-lg': '0 12px 40px -8px rgb(0 0 0 / 0.12)',
                brand: '0 8px 30px -6px rgb(244 0 58 / 0.25)',
            },
            borderRadius: {
                '4xl': '2rem',
            },
        },
    },

    plugins: [forms],
};

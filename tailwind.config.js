import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#325166',
                    light: '#3E6580',
                    dark: '#253D4D'
                },
                secondary: {
                    DEFAULT: '#AA9C78',
                },
                tertiary:{
                    DEFAULT: '',
                },
                base: {
                    DEFAULT: '#1E2D3B',
                },
                gray: {
                    DEFAULT: '#878A8E'
                }
            },
        },
    },

    plugins: [forms, typography],
};

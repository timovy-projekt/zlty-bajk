import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['selector', '[data-theme="dark"]'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['DM Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50:  '#FFFBE6',
                    100: '#FEF4BF',
                    200: '#FDEA8C',
                    300: '#FBDE55',
                    400: '#F9D12A',
                    500: '#F7C600',
                    600: '#D4A800',
                    700: '#A68300',
                    800: '#786000',
                    900: '#4A3B00',
                },
                gray: {
                    50:  '#FAFAFA',
                    100: '#F2F2F2',
                    200: '#E6E6E6',
                    300: '#CFCFCF',
                    400: '#9A9A9A',
                    500: '#6B6B6B',
                    600: '#4A4A4A',
                    700: '#2A2A2A',
                    800: '#121212',
                    900: '#000000',
                },
            },
        },
    },

    plugins: [forms],
};

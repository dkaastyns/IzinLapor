import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import motion from 'tailwindcss-motion';

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
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'blob-1': 'blob 25s infinite ease-in-out alternate',
                'blob-2': 'blob 22s infinite ease-in-out alternate-reverse',
                'blob-3': 'blob 28s infinite ease-in-out alternate',
                'star': 'star linear infinite',
                'float-particle': 'float-particle ease-in-out infinite alternate',
                'float': 'float 6s ease-in-out infinite',
                'float-smooth': 'float-smooth 8s ease-in-out infinite',
            },
            keyframes: {
                blob: {
                    '0%': { transform: 'translate(0px, 0px) scale(1)' },
                    '33%': { transform: 'translate(10vw, -15vh) scale(1.1)' },
                    '66%': { transform: 'translate(-10vw, 10vh) scale(0.9)' },
                    '100%': { transform: 'translate(0px, 0px) scale(1)' },
                },
                star: {
                    '0%': { transform: 'translateY(0) rotate(0deg)', opacity: '0' },
                    '10%': { opacity: '1' },
                    '90%': { opacity: '1' },
                    '100%': { transform: 'translateY(-100vh) rotate(360deg)', opacity: '0' },
                },
                'float-particle': {
                    '0%, 100%': { transform: 'translate(0, 0)' },
                    '25%': { transform: 'translate(5vw, -5vh)' },
                    '50%': { transform: 'translate(-5vw, 10vh)' },
                    '75%': { transform: 'translate(8vw, 5vh)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-20px)' },
                },
            },
            colors: {
                primary: {
                    50: '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#7c3aed',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    900: '#4c1d95',
                    950: '#2e1065',
                },
                accent: {
                    400: '#f472b6',
                    500: '#ec4899',
                    600: '#db2777',
                },
                cyan: {
                    400: '#22d3ee',
                    500: '#06b6d4',
                    600: '#0891b2',
                },
                teal: {
                    400: '#2dd4bf',
                    500: '#14b8a6',
                    600: '#0d9488',
                },
                fuchsia: {
                    400: '#e879f9',
                    500: '#d946ef',
                    600: '#c026d3',
                },
            },
        },
    },

    plugins: [forms, motion],
};

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['var(--font-sans, Inter)', 'sans-serif'],
                display: ['var(--font-display, Outfit)', 'sans-serif'],
                jdm: ['var(--font-jdm, "Dela Gothic One")', 'Outfit', 'sans-serif'],
            },
            colors: {
                brand: {
                    50: '#fef2f2', 100: '#fee2e2', 200: '#fecaca', 300: '#fca5a5',
                    400: '#f87171', 500: '#ef4444', 600: '#dc2626', 700: '#b91c1c',
                    800: '#991b1b', 900: '#7f1d1d',
                },
                dark: {
                    950: 'rgb(var(--dark-950, 6 9 14) / <alpha-value>)',
                    900: 'rgb(var(--dark-900, 8 11 17) / <alpha-value>)',
                    850: 'rgb(var(--dark-850, 13 18 28) / <alpha-value>)',
                    800: 'rgb(var(--dark-800, 19 25 38) / <alpha-value>)',
                    700: 'rgb(var(--dark-700, 29 37 56) / <alpha-value>)',
                    600: 'rgb(var(--dark-600, 45 58 84) / <alpha-value>)',
                },
            },
        },
    },

    plugins: [],
};

import defaultTheme from 'tailwindcss/defaultTheme';

const plugin = require('tailwindcss/plugin')

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './node_modules/@protonemedia/inertiajs-tables-laravel-query-builder/**/*.{js,vue}',
    ],

    theme: {
        extend: {
            screens: {
                'xs': '400px',
                '3xl': '1600px'
            },
            colors: {
                primary: '#00aeef',
                info: '#2aacbb',
                success: '#16a679',
                waning: '#b77e0b',
                danger: '#c5280c',
            },
            fontFamily: {
                oswald: ['oswald', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                'xs': '.75rem',
                '2xs': '.6rem',
                '3xs': '.5rem',
            },
            maxWidth: {
                '8xl': '1350px',
            }
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        plugin(function ({addUtilities}) {
            addUtilities({
                '.vertical-lr': {
                    writingMode: 'vertical-lr',
                },
            })
        })
    ],
};

import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // Dark mode is toggled by a `dark` class on <html> (see layouts/app.blade.php)
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Public Sans"', ...defaultTheme.fontFamily.sans],
            },

            // Design tokens (Phase 2). Use these instead of raw hex values.
            colors: {
                // Primary brand: CNPPO navy. 800 is the sidebar/top-bar color.
                navy: {
                    50: '#F2F5FA',
                    100: '#E3E9F3',
                    200: '#C3CEE2',
                    300: '#94A6C8',
                    400: '#6079A6',
                    500: '#3E5788',
                    600: '#2C426E',
                    700: '#213458',
                    800: '#16233F',
                    900: '#0F1A30',
                    950: '#0A1222',
                },
                // PNP gold: accent only (active nav, dividers, seal). Use sparingly.
                gold: {
                    50: '#FFFBEB',
                    100: '#FEF3C7',
                    200: '#FDE68A',
                    300: '#FCD34D',
                    400: '#FBBF24',
                    500: '#F59E0B',
                    600: '#D97706',
                },
                // Workflow status colors. Keep in sync with
                // components/status-badge.blade.php and public/vendor/charts/chart-theme.js
                status: {
                    draft: '#64748B',
                    submitted: '#2563EB',
                    review: '#D97706',
                    validated: '#16A34A',
                    returned: '#DC2626',
                },
                canvas: '#F3F5F9', // page background (cool gray)
            },

            boxShadow: {
                card: '0 1px 2px 0 rgba(15, 26, 48, 0.04), 0 1px 3px 0 rgba(15, 26, 48, 0.06)',
                'card-hover': '0 4px 12px -2px rgba(15, 26, 48, 0.10), 0 2px 4px -2px rgba(15, 26, 48, 0.06)',
            },
        },
    },

    plugins: [forms],
};

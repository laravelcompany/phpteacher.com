/** @type {import('tailwindcss').Config} */

export default {
    content: [
        './src/**/*.{astro,html,js,jsx,md,mdx,svelte,ts,tsx,vue}',
        './node_modules/preline/preline.js',
    ],
    darkMode: 'class',
    theme: {
        colors: {
            transparent: 'transparent',
            current: 'currentColor',
            black: '#000000',
            white: '#ffffff',
            gray: {
                100: '#f3f4f6',
                300: '#d1d5db',
                500: '#6b7280',
                600: '#4b5563',
                800: '#1f2937',
            },
            indigo: {
                200: '#c7d2fe',
                300: '#a5b4fc',
            },
            neutral: {
                50: '#f8fafc',
                100: '#f1f5f9',
                200: '#e2e8f0',
                300: '#cbd5e1',
                350: '#b6c2d1',
                400: '#94a3b8',
                500: '#64748b',
                600: '#475569',
                700: '#334155',
                800: '#1e293b',
                900: '#0f172a',
            },
            yellow: {
                50: '#fdf2f8',
                100: '#fce7f3',
                400: '#f472b6',
                500: '#ec4899',
            },
            orange: {
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
            },
            red: {
                400: '#f87171',
                500: '#ef4444',
            },
            zinc: {
                200: '#e4e4e7',
                400: '#a1a1aa',
                500: '#71717a',
                600: '#52525b',
                700: '#3f3f46',
                800: '#27272a',
                900: '#18181b',
            },
        },
        extend: {
            maxWidth: {
                '8xl': '88rem',
            },
            backgroundImage: {
                'gradient-text': 'linear-gradient(to right, #8b5cf6, #ec4899)',
            },
            typography: ({ theme }) => ({
                blog: {
                    css: {
                        '--tw-prose-body': theme('colors.neutral[700]'),
                        '--tw-prose-headings': theme('colors.neutral[900]'),
                        '--tw-prose-lead': theme('colors.neutral[700]'),
                        '--tw-prose-links': theme('colors.orange[300]'),
                        '--tw-prose-bold': theme('colors.neutral[900]'),
                        '--tw-prose-counters': theme('colors.neutral[600]'),
                        '--tw-prose-bullets': theme('colors.neutral[400]'),
                        '--tw-prose-hr': theme('colors.neutral[300]'),
                        '--tw-prose-quotes': theme('colors.neutral[500]'),
                        '--tw-prose-quote-borders': theme('colors.neutral[300]'),
                        '--tw-prose-captions': theme('colors.neutral[700]'),
                        '--tw-prose-code': theme('colors.neutral[700]'),
                        '--tw-prose-pre-code': theme('colors.neutral[900]'),
                        '--tw-prose-pre-bg': theme('colors.white'),
                        '--tw-prose-th-borders': theme('colors.neutral[300]'),
                        '--tw-prose-td-borders': theme('colors.neutral[200]'),

                        '--tw-prose-invert-body': theme('colors.neutral[400]'),
                        '--tw-prose-invert-headings': theme('colors.neutral[200]'),
                        '--tw-prose-invert-lead': theme('colors.neutral[300]'),
                        '--tw-prose-invert-links': theme('colors.orange[300]'),
                        '--tw-prose-invert-bold': theme('colors.neutral[300]'),
                        '--tw-prose-invert-counters': theme('colors.neutral[400]'),
                        '--tw-prose-invert-bullets': theme('colors.neutral[600]'),
                        '--tw-prose-invert-hr': theme('colors.neutral[700]'),
                        '--tw-prose-invert-quotes': theme('colors.neutral[500]'),
                        '--tw-prose-invert-quote-borders': theme('colors.neutral[500]'),
                        '--tw-prose-invert-captions': theme('colors.neutral[400]'),
                        '--tw-prose-invert-code': theme('colors.neutral[350]'),
                        '--tw-prose-invert-pre-code': theme('colors.neutral[300]'),
                        '--tw-prose-invert-th-borders': theme('colors.neutral[600]'),
                        '--tw-prose-invert-td-borders': theme('colors.neutral[700]'),
                    },
                },
                DEFAULT: {
                    css: {
                        blockquote: {
                            fontStyle: 'normal',
                            quotes: 'none',
                        },
                    },
                },
            }),
        },
    },
    plugins: [
        require('tailwindcss/nesting'),
        require('preline/plugin'),
        require('@tailwindcss/typography'),
    ],
}


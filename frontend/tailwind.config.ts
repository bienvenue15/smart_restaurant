import type { Config } from 'tailwindcss';

export default {
  content: [
    './app/components/**/*.{vue,js,ts}',
    './app/layouts/**/*.vue',
    './app/pages/**/*.vue',
    './app/app.vue',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          DEFAULT: '#d98e2b',
          dark: '#a9670e',
          soft: '#e8b05a',
        },
        accent: '#d98e2b',
        forest: {
          DEFAULT: '#0d3b2e',
          mid: '#145c46',
        },
        clay: '#a5361f',
        terra: '#c25b35',
        emerald: {
          DEFAULT: '#17936c',
        },
        mint: '#e3eedd',
        sand: '#efe3c8',
        card: '#fffdf7',
        ink: {
          DEFAULT: '#221a10',
          muted: '#5d5343',
        },
        surface: {
          DEFAULT: '#fffdf7',
          deep: '#0a2c22',
        },
        admin: {
          deep: '#301410',
        },
        line: '#e3d8bf',
      },
      fontFamily: {
        display: ['Fraunces', 'Georgia', 'serif'],
        body: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        brand: '0 18px 44px -26px rgba(70, 40, 15, 0.25)',
        phone: '0 30px 60px -25px rgba(13, 59, 46, 0.45)',
      },
    },
  },
  plugins: [],
} satisfies Config;

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: ['@nuxtjs/tailwindcss', '@nuxtjs/i18n'],

  css: ['~/assets/css/main.css'],

  typescript: {
    strict: true,
  },

  runtimeConfig: {
    public: {
      // Base URL for the Express API — see docs/TARGET_ARCHITECTURE.md.
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:4000/api/v1',
    },
  },

  i18n: {
    // Preserves the legacy app's real, used i18n support
    // (docs/CURRENT_SYSTEM_AUDIT.md §8: en/fr/rw/sw via src/Language.php).
    locales: [
      { code: 'en', name: 'English' },
      { code: 'fr', name: 'Français' },
      { code: 'rw', name: 'Kinyarwanda' },
      { code: 'sw', name: 'Kiswahili' },
    ],
    defaultLocale: 'en',
    strategy: 'no_prefix',
  },
});

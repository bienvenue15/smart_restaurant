import type { Plugin } from 'vite';

const API_ORIGIN = process.env.API_PROXY_TARGET || 'http://127.0.0.1:4000';

function publicApiBaseUrl() {
  const raw = process.env.NUXT_PUBLIC_API_BASE_URL || '/api/v1';
  try {
    const url = new URL(raw);
    if (url.hostname === 'localhost' || url.hostname === '127.0.0.1') return '/api/v1';
    return raw.replace(/\/$/, '');
  } catch {
    return raw.startsWith('/') ? raw : '/api/v1';
  }
}

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: process.env.NODE_ENV === 'development' },

  modules: ['@nuxtjs/tailwindcss', '@nuxtjs/i18n'],

  // Nuxt's default component auto-import prefixes a component's name with
  // its subdirectory (e.g. components/customer/CartDrawer.vue becomes
  // <CustomerCartDrawer>) unless the filename already starts with that
  // directory name. Every usage across this codebase calls components by
  // their bare filename instead (<CartDrawer>, <NotificationBell>, etc.) —
  // disabling the prefix here makes those resolve as originally intended,
  // instead of silently failing to resolve at all.
  components: [{ path: '~/components', pathPrefix: false }],

  css: ['~/assets/css/main.css'],

  app: {
    head: {
      title: 'Smart Restaurant — Complete Restaurant Management',
      htmlAttrs: { lang: 'en' },
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap',
        },
      ],
      meta: [
        {
          name: 'description',
          content:
            'QR ordering, staff tracking, payments, and real-time analytics for restaurants in Rwanda. Start a free trial with Smart Restaurant by Inovasiyo.',
        },
      ],
    },
  },

  typescript: {
    strict: true,
  },

  vite: {
    server: {
      proxy: {
        '/api/v1': { target: API_ORIGIN, changeOrigin: true },
        '/uploads': { target: API_ORIGIN, changeOrigin: true },
      },
    },
    plugins: [
      // Vite 8 workaround: @intlify/unplugin-vue-i18n compiles i18n/locales/*.json
      // into JS ("const resource = ...") but Vite's built-in json plugin then
      // re-processes that output as JSON and fails to parse it. Skip the i18n
      // locale files in vite:json so only the i18n plugin's transform applies.
      // https://github.com/intlify/bundle-tools/issues/553
      {
        name: 'i18n-json-vite8-fix',
        enforce: 'pre',
        configResolved(config) {
          const jsonPlugin = config.plugins.find(
            (p) => p.name === 'vite:json' || p.name === 'builtin:vite-json',
          );
          if (!jsonPlugin?.transform) return;
          const transform = jsonPlugin.transform;
          const original = typeof transform === 'function' ? transform : transform.handler;
          if (typeof original !== 'function') return;
          const patched = function (
            this: unknown,
            code: string,
            id: string,
            ...args: unknown[]
          ) {
            if (/i18n\/locales\/.*\.json$/.test(id)) return;
            return (original as (...a: unknown[]) => unknown).call(this, code, id, ...args);
          };
          if (typeof transform === 'function') {
            jsonPlugin.transform = patched as typeof jsonPlugin.transform;
          } else {
            transform.handler = patched as typeof transform.handler;
          }
        },
      } satisfies Plugin,
    ],
  },

  runtimeConfig: {
    // Server-only — lets SSR-side fetches (auth-restore.ts) reach the backend
    // directly. `public.apiBaseUrl` is deliberately relative in dev so the
    // browser never talks to :4000 directly, but a relative URL only resolves
    // against a page origin; Node's fetch has none during SSR, so those calls
    // need this absolute origin instead. See resolveApiBase.ts.
    apiOrigin: API_ORIGIN,
    public: {
      // Same-origin /api/v1 is proxied to Express so the browser never talks
      // to :4000 directly (which shows as a failed connection on the login page).
      apiBaseUrl: publicApiBaseUrl(),
      i18n: {
        // @nuxtjs/i18n needs an absolute origin to build hreflang/SEO links.
        // Override at runtime with NUXT_PUBLIC_I18N_BASE_URL (docker sets this
        // from FRONTEND_URL).
        baseUrl: process.env.NUXT_PUBLIC_SITE_URL || process.env.FRONTEND_URL || 'http://localhost:3000',
      },
    },
  },

  nitro: {
    routeRules: {
      '/api/v1/**': { proxy: `${API_ORIGIN}/api/v1/**` },
      '/uploads/**': { proxy: `${API_ORIGIN}/uploads/**` },
      '/admin': { redirect: '/admin/dashboard' },
    },
  },

  i18n: {
    // Preserves the legacy app's real, used i18n support
    // (docs/CURRENT_SYSTEM_AUDIT.md §8: en/fr/rw/sw via src/Language.php).
    baseUrl: process.env.NUXT_PUBLIC_SITE_URL || process.env.FRONTEND_URL || 'http://localhost:3000',
    locales: [
      { code: 'en', language: 'en', name: 'English', file: 'en.json' },
      { code: 'fr', language: 'fr', name: 'Français', file: 'fr.json' },
      { code: 'rw', language: 'rw', name: 'Kinyarwanda', file: 'rw.json' },
      { code: 'sw', language: 'sw', name: 'Kiswahili', file: 'sw.json' },
    ],
    defaultLocale: 'en',
    langDir: 'locales',
    strategy: 'no_prefix',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'sr_lang',
      fallbackLocale: 'en',
    },
  },
});

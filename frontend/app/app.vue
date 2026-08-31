<script setup lang="ts">
// Keeps <html lang> in sync with the active i18n locale — without this it
// stays hardcoded to "en" (set in nuxt.config.ts) even after switching
// languages, which is wrong for accessibility/SEO regardless of how
// complete the on-page translations are.
const i18nHead = useLocaleHead();
useHead({
  htmlAttrs: { lang: computed(() => i18nHead.value.htmlAttrs?.lang) },
});

const route = useRoute();
const adminPath = useAdminPath();
const maintenance = useMaintenanceMode();

const hideMaintenanceOverlay = computed(() => {
  const path = route.path;
  if (adminPath.isAdminRoute(path)) return true;
  if (path === '/staff/login' || path.startsWith('/staff/forgot-password') || path.startsWith('/staff/reset-password')) {
    return true;
  }
  return false;
});

const showMaintenance = computed(() => maintenance.active.value && !hideMaintenanceOverlay.value);
</script>

<template>
  <div>
    <NuxtRouteAnnouncer />
    <ToastStack />
    <ConfirmDialog />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
    <MaintenanceScreen v-if="showMaintenance" />
  </div>
</template>

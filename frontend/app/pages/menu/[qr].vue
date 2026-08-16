<script setup lang="ts">
const route = useRoute();
const qr = route.params.qr as string;

// TODO (Phase 4): call POST /api/v1/customer/session/scan with this QR
// value to establish the signed table-session token (see
// docs/TARGET_ARCHITECTURE.md "Auth & Session Model"), then load the menu
// via /api/v1/customer/menu. This page is currently a placeholder.
</script>

<template>
  <div class="p-6">
    <h1 class="text-xl font-semibold text-gray-900">Table menu</h1>
    <p class="mt-1 text-sm text-gray-500">QR code: {{ qr }}</p>
    <p class="mt-4 text-sm text-gray-500">
      Placeholder — see <code>docs/MIGRATION_PROGRESS.md</code> Phase 4 for the customer ordering flow build-out.
    </p>
  </div>
</template>

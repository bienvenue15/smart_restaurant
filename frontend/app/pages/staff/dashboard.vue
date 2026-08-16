<script setup lang="ts">
definePageMeta({ middleware: 'staff-auth' });

const auth = useAuthStore();
</script>

<template>
  <div class="p-6">
    <h1 class="text-xl font-semibold text-gray-900">Welcome, {{ auth.staff.value?.fullName }}</h1>
    <p class="mt-1 text-sm text-gray-600">Role: {{ auth.staff.value?.role }}</p>
    <p class="mt-4 text-sm text-gray-500">
      This is a placeholder — see <code>docs/MIGRATION_PROGRESS.md</code> Phase 4 for the role-aware dashboard build-out.
    </p>
  </div>
</template>

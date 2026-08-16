<template>
  <div class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
    <h1 class="text-3xl font-bold text-gray-900">Smart Restaurant</h1>
    <p class="mt-2 max-w-md text-gray-600">
      The new platform is under active development. See
      <code class="rounded bg-gray-100 px-1.5 py-0.5 text-sm">docs/MIGRATION_PROGRESS.md</code>
      for current status.
    </p>
    <NuxtLink to="/staff/login" class="mt-6">
      <BaseButton>Staff login</BaseButton>
    </NuxtLink>
  </div>
</template>

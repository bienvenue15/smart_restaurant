<script setup lang="ts">
const auth = useAuthStore();
const router = useRouter();

const links = [
  { to: '/admin/dashboard', label: 'Dashboard' },
  { to: '/admin/restaurants', label: 'Restaurants' },
  { to: '/admin/users', label: 'Platform users' },
  { to: '/admin/plans', label: 'Plans' },
];

function logout() {
  auth.clearSession();
  router.push('/staff/login');
}
</script>

<template>
  <div class="flex min-h-screen">
    <aside class="w-56 shrink-0 border-r border-gray-200 bg-gray-900 p-4 text-white">
      <p class="mb-4 text-sm font-semibold">Smart Restaurant — Admin</p>
      <nav class="space-y-1">
        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="block rounded-md px-3 py-2 text-sm text-gray-200 hover:bg-gray-800"
          active-class="bg-gray-800 text-white font-medium"
        >
          {{ link.label }}
        </NuxtLink>
      </nav>
      <button class="mt-6 w-full rounded-md px-3 py-2 text-left text-sm text-red-400 hover:bg-gray-800" @click="logout">Log out</button>
    </aside>
    <main class="flex-1 bg-gray-50">
      <slot />
    </main>
  </div>
</template>

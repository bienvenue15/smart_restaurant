<script setup lang="ts">
const auth = useAuthStore();
const router = useRouter();

const links = [
  { to: '/staff/dashboard', label: 'Dashboard' },
  { to: '/staff/orders', label: 'Orders' },
  { to: '/staff/kitchen', label: 'Kitchen' },
  { to: '/staff/waiter-calls', label: 'Waiter calls' },
  { to: '/staff/tables', label: 'Tables' },
  { to: '/staff/menu', label: 'Menu' },
  { to: '/staff/team', label: 'Team' },
  { to: '/staff/cash', label: 'Cash' },
];

function logout() {
  auth.clearSession();
  router.push('/staff/login');
}
</script>

<template>
  <div class="flex min-h-screen">
    <aside class="w-56 shrink-0 border-r border-gray-200 bg-white p-4">
      <p class="mb-4 text-sm font-semibold text-gray-900">Smart Restaurant</p>
      <nav class="space-y-1">
        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="block rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
          active-class="bg-blue-50 text-blue-700 font-medium"
        >
          {{ link.label }}
        </NuxtLink>
      </nav>
      <button class="mt-6 w-full rounded-md px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50" @click="logout">Log out</button>
    </aside>
    <main class="flex-1 bg-gray-50">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  name: string;
}>();
</script>

<template>
  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
    <!-- grid / dashboard -->
    <template v-if="name === 'grid'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z" />
    </template>
    <template v-else-if="name === 'orders'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h11M9 12h11M9 19h11M4 5h.01M4 12h.01M4 19h.01" />
    </template>
    <template v-else-if="name === 'kitchen'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3M4.93 4.93l2.12 2.12m9.9 9.9 2.12 2.12M3 12h3m12 0h3M4.93 19.07l2.12-2.12m9.9-9.9 2.12-2.12M9 12a3 3 0 106 0 3 3 0 00-6 0z" />
    </template>
    <template v-else-if="name === 'bell'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0" />
    </template>
    <template v-else-if="name === 'tables'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 10h16M6 10v8m12-8v8M8 6h8v4H8V6z" />
    </template>
    <template v-else-if="name === 'menu'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10" />
    </template>
    <template v-else-if="name === 'team'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 10-8 0 4 4 0 008 0zM4 20a6 6 0 0116 0" />
    </template>
    <template v-else-if="name === 'cash'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18v10H3V7zm4 5h.01M12 9v6m5-3h.01" />
    </template>
    <template v-else-if="name === 'reports'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 15v4m4-8v8m4-5v5" />
    </template>
    <template v-else-if="name === 'liability'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 4v6c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V7l8-4zm0 6v4m0 3h.01" />
    </template>
    <template v-else-if="name === 'support'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8 10a4 4 0 118 0c0 2-1.5 3-2.5 3.5S12 15 12 16m0 3h.01" />
    </template>
    <template v-else-if="name === 'approvals'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
    </template>
    <template v-else-if="name === 'log'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
    </template>
    <template v-else-if="name === 'account'">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" />
    </template>
    <template v-else>
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM19.4 15a7.97 7.97 0 00.1-2l2-1.2-2-3.4-2.3.6a8.1 8.1 0 00-1.7-1L15 4h-6l-.5 4a8.1 8.1 0 00-1.7 1L4.5 8.4l-2 3.4 2 1.2a8 8 0 000 2l-2 1.2 2 3.4 2.3-.6a8.1 8.1 0 001.7 1L9 20h6l.5-4a8.1 8.1 0 001.7-1l2.3.6 2-3.4-2-1.2z" />
    </template>
  </svg>
</template>

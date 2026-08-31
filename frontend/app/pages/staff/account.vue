<script setup lang="ts">
definePageMeta({ middleware: 'staff-auth', layout: 'staff' });
</script>

<template>
  <div>
    <StaffPageHeader :title="$t('staff.pages.account')" :subtitle="$t('staff.pages.accountSub')" />
    <div class="max-w-xl">
      <ChangePasswordForm />
    </div>
  </div>
</template>

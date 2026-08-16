export default defineNuxtRouteMiddleware(() => {
  const auth = useAuthStore();
  if (!auth.accessToken.value) {
    return navigateTo('/staff/login');
  }
  if (auth.staff.value?.role !== 'SUPER_ADMIN') {
    return navigateTo('/staff/dashboard');
  }
});
